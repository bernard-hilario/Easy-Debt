import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getAnalytics, isSupported } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-analytics.js";
import {
    getFirestore,
    collection,
    doc,
    getDoc,
    getDocs,
    query,
    where,
    setDoc,
    updateDoc,
    deleteDoc,
    runTransaction
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const firebaseConfig = {
    apiKey: "AIzaSyCC7uLFhcKKLSBPVQjUsX2xkGCk-eS5TQY",
    authDomain: "easy-debt-e7d20.firebaseapp.com",
    projectId: "easy-debt-e7d20",
    storageBucket: "easy-debt-e7d20.firebasestorage.app",
    messagingSenderId: "147419773837",
    appId: "1:147419773837:web:04df802e4cea7c290b9bc4",
    measurementId: "G-EEM9V554H7"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

const SESSION_KEY = "easyDebtSession";
const DEFAULT_USERNAME = "gladys";
const DEFAULT_PASSWORD = "admin";
const LOW_STOCK_THRESHOLD = 5;

function createError(message, status = 400) {
    const error = new Error(message);
    error.status = status;
    return error;
}

function nowIso() {
    return new Date().toISOString();
}

function toNumber(value, fallback = 0) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function getSession() {
    try {
        return JSON.parse(sessionStorage.getItem(SESSION_KEY) || "null");
    } catch (error) {
        return null;
    }
}

function setSession(username) {
    sessionStorage.setItem(
        SESSION_KEY,
        JSON.stringify({
            authenticated: true,
            username,
            loggedInAt: nowIso()
        })
    );
}

function clearSession() {
    sessionStorage.removeItem(SESSION_KEY);
}

function isAuthenticated() {
    return Boolean(getSession()?.authenticated);
}

function requireAuth() {
    if (!isAuthenticated()) {
        throw createError("Unauthorized", 401);
    }
}

function normalizeText(value) {
    return String(value || "").trim();
}

function normalizeKey(value) {
    return normalizeText(value).toLowerCase();
}

function compareAsc(left, right) {
    return left.localeCompare(right, undefined, { sensitivity: "base" });
}

function sortByName(records) {
    return [...records].sort((a, b) => compareAsc(a.name || "", b.name || ""));
}

function sortByDateDesc(records, key) {
    return [...records].sort((a, b) => {
        const left = a[key] || "";
        const right = b[key] || "";
        return right.localeCompare(left);
    });
}

function startOfToday() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
}

function parseDateOnly(value) {
    if (!value) {
        return null;
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return new Date(`${value}T00:00:00`);
    }
    return new Date(value);
}

function calculateDebtMetrics(record) {
    const totalAmount = toNumber(record.total_amount);
    const amountPaid = toNumber(record.amount_paid);
    const balance = Math.max(0, totalAmount - amountPaid);
    const interestRate = toNumber(record.interest_rate);
    const dueDate = parseDateOnly(record.due_date);
    const today = startOfToday();

    if (
        record.status === "unpaid" &&
        interestRate > 0 &&
        dueDate instanceof Date &&
        !Number.isNaN(dueDate.valueOf()) &&
        today > dueDate &&
        balance > 0
    ) {
        const daysOverdue = Math.floor((today.getTime() - dueDate.getTime()) / 86400000);
        const interestAccrued = Number((balance * (interestRate / 100) * daysOverdue).toFixed(2));
        return {
            ...record,
            days_overdue: daysOverdue,
            interest_accrued: interestAccrued,
            balance_with_interest: Number((balance + interestAccrued).toFixed(2))
        };
    }

    return {
        ...record,
        days_overdue: 0,
        interest_accrued: 0,
        balance_with_interest: Number(balance.toFixed(2))
    };
}

async function getNextId(counterName) {
    const counterRef = doc(db, "meta", `counter_${counterName}`);
    return runTransaction(db, async (transaction) => {
        const snapshot = await transaction.get(counterRef);
        const currentValue = snapshot.exists() ? toNumber(snapshot.data().value, 0) : 0;
        const nextValue = currentValue + 1;
        transaction.set(
            counterRef,
            {
                value: nextValue,
                updated_at: nowIso()
            },
            { merge: true }
        );
        return nextValue;
    });
}

async function getAllRecords(collectionName) {
    const snapshot = await getDocs(collection(db, collectionName));
    return snapshot.docs.map((entry) => entry.data());
}

async function getRecord(collectionName, id) {
    const snapshot = await getDoc(doc(db, collectionName, String(id)));
    return snapshot.exists() ? snapshot.data() : null;
}

async function saveRecord(collectionName, id, data) {
    await setDoc(doc(db, collectionName, String(id)), data);
}

async function updateRecord(collectionName, id, data) {
    await updateDoc(doc(db, collectionName, String(id)), data);
}

async function deleteRecord(collectionName, id) {
    await deleteDoc(doc(db, collectionName, String(id)));
}

async function getItems() {
    return sortByName(await getAllRecords("items"));
}

async function getPrices() {
    const [prices, items] = await Promise.all([getAllRecords("prices"), getAllRecords("items")]);
    const itemMap = Object.fromEntries(items.map((item) => [item.id, item]));

    return [...prices]
        .map((price) => ({
            ...price,
            item_name: itemMap[price.item_id]?.name || "Unknown item"
        }))
        .sort((a, b) => compareAsc(a.item_name || "", b.item_name || ""));
}

async function getDebtsByStatus(status) {
    const debtQuery = query(collection(db, "debts"), where("status", "==", status));
    const [debtSnapshot, items] = await Promise.all([getDocs(debtQuery), getAllRecords("items")]);
    const itemMap = Object.fromEntries(items.map((item) => [item.id, item.name]));

    const debts = debtSnapshot.docs.map((entry) => {
        const data = entry.data();
        return calculateDebtMetrics({
            ...data,
            item_name: itemMap[data.item_id] || "Unknown item"
        });
    });

    return sortByDateDesc(debts, status === "paid" ? "paid_at" : "created_at");
}

async function findPriceByItemId(itemId) {
    const priceQuery = query(collection(db, "prices"), where("item_id", "==", itemId));
    const priceSnapshot = await getDocs(priceQuery);
    if (priceSnapshot.empty) {
        return null;
    }
    return priceSnapshot.docs[0].data();
}

async function deletePricesForItem(itemId) {
    const priceQuery = query(collection(db, "prices"), where("item_id", "==", itemId));
    const priceSnapshot = await getDocs(priceQuery);
    await Promise.all(priceSnapshot.docs.map((entry) => deleteDoc(entry.ref)));
}

async function ensureItemExists(itemId) {
    const item = await getRecord("items", itemId);
    if (!item) {
        throw createError("Item not found", 404);
    }
    return item;
}

async function ensureDebtExists(debtId) {
    const debt = await getRecord("debts", debtId);
    if (!debt) {
        throw createError("Debt not found", 404);
    }
    return debt;
}

async function request(action, method = "GET", data = null) {
    switch (action) {
        case "login": {
            const username = normalizeText(data?.username);
            const password = normalizeText(data?.password);

            if (username === DEFAULT_USERNAME && password === DEFAULT_PASSWORD) {
                setSession(username);
                return { success: true, username };
            }

            throw createError("Invalid credentials", 401);
        }

        case "logout":
            clearSession();
            return { success: true };

        case "check_auth": {
            const session = getSession();
            return {
                authenticated: Boolean(session?.authenticated),
                username: session?.username || null
            };
        }

        default:
            requireAuth();
    }

    switch (action) {
        case "get_dashboard": {
            const [items, debts] = await Promise.all([getAllRecords("items"), getAllRecords("debts")]);
            const totalCustomers = new Set(
                debts.map((debt) => normalizeKey(debt.customer_name)).filter(Boolean)
            ).size;
            const totalOutstanding = debts
                .filter((debt) => debt.status === "unpaid")
                .reduce((sum, debt) => sum + toNumber(debt.total_amount), 0);
            const totalPaid = debts.filter((debt) => debt.status === "paid").length;

            return {
                totalCustomers,
                totalOutstanding: Number(totalOutstanding.toFixed(2)),
                totalItems: items.length,
                totalPaid
            };
        }

        case "get_items":
            return getItems();

        case "add_item": {
            const name = normalizeText(data?.name);
            const stock = Math.max(0, toNumber(data?.stock));

            if (!name) {
                throw createError("Item name is required", 400);
            }

            const existingItems = await getItems();
            if (existingItems.some((item) => normalizeKey(item.name) === normalizeKey(name))) {
                throw createError("Item already exists", 409);
            }

            const id = await getNextId("items");
            await saveRecord("items", id, {
                id,
                name,
                stock,
                created_at: nowIso()
            });

            return { success: true, id };
        }

        case "update_item": {
            const id = toNumber(data?.id);
            const name = normalizeText(data?.name);
            const stock = Math.max(0, toNumber(data?.stock));

            if (!id || !name) {
                throw createError("Invalid data", 400);
            }

            const current = await ensureItemExists(id);
            const existingItems = await getItems();
            if (
                existingItems.some(
                    (item) => item.id !== id && normalizeKey(item.name) === normalizeKey(name)
                )
            ) {
                throw createError("Item name already exists", 409);
            }

            await updateRecord("items", id, {
                name,
                stock,
                updated_at: nowIso(),
                created_at: current.created_at || nowIso()
            });

            return { success: true };
        }

        case "delete_item": {
            const id = toNumber(data?.id);
            if (!id) {
                throw createError("Invalid ID", 400);
            }

            const linkedDebts = await getDocs(query(collection(db, "debts"), where("item_id", "==", id)));
            if (!linkedDebts.empty) {
                throw createError("Cannot delete item with existing debt records", 409);
            }

            const item = await getRecord("items", id);
            if (!item) {
                throw createError("Item not found", 404);
            }

            await deletePricesForItem(id);
            await deleteRecord("items", id);
            return { success: true };
        }

        case "get_prices":
            return getPrices();

        case "add_price": {
            const itemId = toNumber(data?.item_id);
            const price = toNumber(data?.price, -1);
            const unit = data?.unit === "kg" ? "kg" : "pcs";

            if (!itemId || price < 0) {
                throw createError("Invalid data", 400);
            }

            await ensureItemExists(itemId);
            const existingPrice = await findPriceByItemId(itemId);

            if (existingPrice) {
                await updateRecord("prices", existingPrice.id, {
                    price,
                    unit,
                    updated_at: nowIso()
                });
                return { success: true, id: existingPrice.id };
            }

            const id = await getNextId("prices");
            await saveRecord("prices", id, {
                id,
                item_id: itemId,
                price,
                unit,
                created_at: nowIso()
            });

            return { success: true, id };
        }

        case "delete_price": {
            const id = toNumber(data?.id);
            if (!id) {
                throw createError("Invalid ID", 400);
            }

            const price = await getRecord("prices", id);
            if (!price) {
                throw createError("Price not found", 404);
            }

            await deleteRecord("prices", id);
            return { success: true };
        }

        case "get_debts":
            return getDebtsByStatus("unpaid");

        case "add_debt": {
            const customerName = normalizeText(data?.customer_name);
            const phone = normalizeText(data?.phone);
            const itemId = toNumber(data?.item_id);
            const quantity = toNumber(data?.quantity);
            const dueDate = normalizeText(data?.due_date);
            const notes = normalizeText(data?.notes);
            const interestRate = Math.max(0, toNumber(data?.interest_rate));

            if (!customerName || !itemId || quantity <= 0 || !dueDate) {
                throw createError("All fields are required", 400);
            }

            const item = await ensureItemExists(itemId);
            const priceRecord = await findPriceByItemId(itemId);
            if (!priceRecord) {
                throw createError("No price set for this item", 400);
            }

            const pricePerUnit = toNumber(priceRecord.price);
            const totalAmount = Number((pricePerUnit * quantity).toFixed(2));
            const id = await getNextId("debts");

            await saveRecord("debts", id, {
                id,
                customer_name: customerName,
                phone,
                item_id: itemId,
                quantity,
                price_per_unit: pricePerUnit,
                total_amount: totalAmount,
                due_date: dueDate,
                notes,
                status: "unpaid",
                amount_paid: 0,
                interest_rate: interestRate,
                paid_at: "",
                created_at: nowIso()
            });

            await updateRecord("items", itemId, {
                stock: Math.max(0, toNumber(item.stock) - quantity),
                updated_at: nowIso()
            });

            return { success: true, id, total_amount: totalAmount };
        }

        case "mark_paid": {
            const id = toNumber(data?.id);
            if (!id) {
                throw createError("Invalid ID", 400);
            }

            const debt = await ensureDebtExists(id);
            if (debt.status !== "unpaid") {
                throw createError("Debt not found or already paid", 404);
            }

            await updateRecord("debts", id, {
                status: "paid",
                amount_paid: toNumber(debt.total_amount),
                paid_at: nowIso(),
                updated_at: nowIso()
            });

            return { success: true };
        }

        case "update_debt": {
            const id = toNumber(data?.id);
            const interestRate = Math.max(0, toNumber(data?.interest_rate));
            const dueDate = normalizeText(data?.due_date);
            const notes = normalizeText(data?.notes);

            if (!id) {
                throw createError("Invalid ID", 400);
            }

            const debt = await ensureDebtExists(id);
            if (debt.status !== "unpaid") {
                throw createError("Debt not found or already paid", 404);
            }

            await updateRecord("debts", id, {
                interest_rate: interestRate,
                due_date: dueDate,
                notes,
                updated_at: nowIso()
            });

            return { success: true };
        }

        case "partial_payment": {
            const id = toNumber(data?.id);
            const payment = toNumber(data?.amount);

            if (!id || payment <= 0) {
                throw createError("Invalid data", 400);
            }

            const debt = await ensureDebtExists(id);
            if (debt.status !== "unpaid") {
                throw createError("Debt not found or already paid", 404);
            }

            const total = toNumber(debt.total_amount);
            const newPaid = Number((toNumber(debt.amount_paid) + payment).toFixed(2));

            if (newPaid >= total) {
                await updateRecord("debts", id, {
                    amount_paid: total,
                    status: "paid",
                    paid_at: nowIso(),
                    updated_at: nowIso()
                });
                return { success: true, fully_paid: true, balance: 0 };
            }

            await updateRecord("debts", id, {
                amount_paid: newPaid,
                updated_at: nowIso()
            });

            return {
                success: true,
                fully_paid: false,
                balance: Number((total - newPaid).toFixed(2)),
                amount_paid: newPaid
            };
        }

        case "get_paid":
            return getDebtsByStatus("paid");

        default:
            throw createError(`Invalid action: ${action}`, 400);
    }
}

window.firebaseReady = (async () => {
    let analytics = null;

    try {
        if (await isSupported()) {
            analytics = getAnalytics(app);
        }
    } catch (error) {
        console.warn("Firebase Analytics is unavailable in this environment.", error);
    }

    const services = {
        app,
        db,
        analytics,
        request,
        metadata: {
            defaultUsername: DEFAULT_USERNAME,
            lowStockThreshold: LOW_STOCK_THRESHOLD
        }
    };

    window.easyDebtDb = services;
    return services;
})();
