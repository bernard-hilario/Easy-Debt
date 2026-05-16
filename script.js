
        let pricesCache = [];
        let itemsCache = [];

        // Theme
        function initTheme() {
            const saved = localStorage.getItem('theme');
            if (saved) {
                document.documentElement.setAttribute('data-theme', saved);
                updateThemeIcon(saved);
            }
        }
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        }
        function updateThemeIcon(theme) {
            document.getElementById('themeIcon').textContent = theme === 'light' ? '☀️' : '🌙';
        }

        // Mobile nav
        function toggleMobileNav() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('passwordToggle');
            if (!passwordInput || !toggleButton) return;

            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            toggleButton.textContent = isVisible ? 'Show' : 'Hide';
            toggleButton.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            toggleButton.setAttribute('aria-pressed', String(!isVisible));
        }

        let backendPreference = null;

        async function requestPhpApi(action, method = 'GET', data = null) {
            const options = { method, headers: { 'Content-Type': 'application/json' } };
            if (data) options.body = JSON.stringify(data);
            const response = await fetch(`api.php?action=${action}`, options);
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Request failed');
            return result;
        }

        async function getFirebaseService() {
            if (!window.firebaseReady) return null;
            try {
                const service = await window.firebaseReady;
                return service && typeof service.request === 'function' ? service : null;
            } catch (error) {
                console.warn('Firebase unavailable, falling back to PHP API.', error);
                return null;
            }
        }

        function shouldFallbackToPhp(error) {
            const message = String(error?.message || '').toLowerCase();
            const code = String(error?.code || '').toLowerCase();
            return (
                code.includes('permission-denied') ||
                code.includes('unavailable') ||
                code.includes('network-request-failed') ||
                message.includes('missing or insufficient permissions') ||
                message.includes('offline') ||
                message.includes('failed to get document because the client is offline')
            );
        }

        async function api(action, method = 'GET', data = null) {
            if (backendPreference === 'php') {
                return requestPhpApi(action, method, data);
            }

            const firebaseService = await getFirebaseService();
            if (firebaseService) {
                try {
                    backendPreference = 'firebase';
                    return await firebaseService.request(action, method, data);
                } catch (error) {
                    if (!shouldFallbackToPhp(error)) {
                        throw error;
                    }
                    console.warn('Switching to PHP API fallback after Firebase request error.', error);
                    backendPreference = 'php';
                    return requestPhpApi(action, method, data);
                }
            }

            backendPreference = 'php';
            return requestPhpApi(action, method, data);
        }

        function showPage(pageId) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.getElementById(pageId).classList.add('active');
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('navLinks').classList.remove('show');

            const btnMap = { dashboard: 0, inventory: 1, addDebt: 2, debtList: 3, paidHistory: 4 };
            const idx = btnMap[pageId];
            if (idx !== undefined) document.querySelectorAll('.nav-btn')[idx]?.classList.add('active');

            if (pageId === 'dashboard') updateDashboard();
            if (pageId === 'inventory') { renderInventoryList(); }
            if (pageId === 'addDebt') { loadItemSelect('debtItemSelect'); updateDebtTotal(); }
            if (pageId === 'debtList') renderDebtList();
            if (pageId === 'paidHistory') renderPaidHistory();
        }

        async function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            try {
                await api('login', 'POST', { username, password });
                document.getElementById('loginError').classList.remove('show');
                document.getElementById('navbar').style.display = 'block';
                showPage('dashboard');
            } catch (err) {
                document.getElementById('loginError').classList.add('show');
            }
        }

        async function logout() {
            await api('logout', 'POST');

            // Show logout overlay
            const overlay = document.getElementById('logoutOverlay');
            overlay.style.display = 'flex';

            // After 1.5s switch message to "Redirecting…"
            setTimeout(() => {
                document.getElementById('logoutMsg').textContent = 'Redirecting to login page…';
                document.getElementById('logoutSub').style.opacity = '1';
            }, 1500);

            // After 2.8s hide overlay and go to login
            setTimeout(() => {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlay.style.opacity = '1';
                    document.getElementById('navbar').style.display = 'none';
                    document.getElementById('loginForm').reset();
                    const passwordInput = document.getElementById('password');
                    const toggleButton = document.getElementById('passwordToggle');
                    if (passwordInput) passwordInput.type = 'password';
                    if (toggleButton) {
                        toggleButton.textContent = 'Show';
                        toggleButton.setAttribute('aria-label', 'Show password');
                        toggleButton.setAttribute('aria-pressed', 'false');
                    }
                    showPage('loginPage');
                }, 400);
            }, 2800);
        }

        async function handleAddItem(e) {
            e.preventDefault();
            const name  = document.getElementById('itemName').value.trim();
            const price = parseFloat(document.getElementById('itemPrice').value);
            const unit  = document.getElementById('priceUnit').value;
            const stock = parseFloat(document.getElementById('itemStock').value) || 0;
            try {
                const result = await api('add_item', 'POST', { name, stock });
                await api('add_price', 'POST', { item_id: result.id, price, unit });
                showAlert('itemSuccess');
                resetForm('itemForm');
                setUnit('pcs');
                await renderInventoryList();
                await loadItemSelect('debtItemSelect');
                pricesCache = await api('get_prices');
            } catch (err) {
                showAlert('itemError', err.message);
            }
        }

        function setUnit(unit) {
            document.getElementById('priceUnit').value = unit;
            document.getElementById('unitPcs').style.background = unit === 'pcs' ? 'var(--accent)' : 'var(--bg-tertiary)';
            document.getElementById('unitPcs').style.color = unit === 'pcs' ? 'white' : 'var(--text-secondary)';
            document.getElementById('unitKg').style.background = unit === 'kg' ? 'var(--accent)' : 'var(--bg-tertiary)';
            document.getElementById('unitKg').style.color = unit === 'kg' ? 'white' : 'var(--text-secondary)';
        }

        async function handleAddDebt(e) {
            e.preventDefault();
            const data = {
                customer_name: document.getElementById('debtCustomer').value.trim(),
                phone: document.getElementById('debtPhone').value.trim(),
                item_id: parseInt(document.getElementById('debtItemSelect').value),
                quantity: parseFloat(document.getElementById('debtQuantity').value),
                due_date: document.getElementById('debtDueDate').value,
                notes: document.getElementById('debtNotes').value.trim(),
                interest_rate: parseFloat(document.getElementById('debtInterest').value) || 0
            };
            try {
                await api('add_debt', 'POST', data);
                showAlert('debtSuccess');
                resetForm('debtForm');
                updateDebtTotal();
                checkLowStock();
            } catch (err) {
                showAlert('debtError', err.message);
            }
        }

        async function markAsPaid(debtId) {
            if (!confirm('Mark this debt as fully paid?')) return;
            try {
                await api('mark_paid', 'POST', { id: debtId });
                renderDebtList();
                updateDashboard();
            } catch (err) {
                alert(err.message);
            }
        }

        async function applyPayment(debtId) {
            const input = document.getElementById(`payment-input-${debtId}`);
            const amount = parseFloat(input ? input.value : 0);
            if (!amount || amount <= 0) { input.focus(); return; }
            try {
                const res = await api('partial_payment', 'POST', { id: debtId, amount });
                if (res.fully_paid) {
                    updateDashboard();
                }
                renderDebtList();
                updateDashboard();
            } catch (err) {
                alert(err.message);
            }
        }

        function startEditDebt(id, currentRate, currentDueDate, currentNotes) {
            // Close any other open debt edits first
            const existing = document.getElementById('debt-edit-row');
            if (existing) existing.remove();

            // Find the row and insert an edit row after it
            const btn = document.querySelector(`button[onclick*="startEditDebt(${id},"]`);
            if (!btn) return;
            const row = btn.closest('tr');
            const colCount = row.cells.length;

            const editRow = document.createElement('tr');
            editRow.id = 'debt-edit-row';
            editRow.style.background = 'var(--bg-tertiary)';
            editRow.innerHTML = `
                <td colspan="${colCount}" style="padding:16px 20px">
                    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Interest Rate (% / day)</label>
                            <input type="number" id="edit-debt-rate-${id}" value="${currentRate}" min="0" step="0.01"
                                style="padding:8px 12px;width:140px;background:var(--bg-card);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:'JetBrains Mono',monospace;outline:none">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Due Date</label>
                            <input type="date" id="edit-debt-due-${id}" value="${currentDueDate}"
                                style="padding:8px 12px;background:var(--bg-card);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none">
                        </div>
                        <div style="flex:1;min-width:180px">
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Notes</label>
                            <input type="text" id="edit-debt-notes-${id}" value="${escapeHtml(currentNotes)}"
                                style="padding:8px 12px;width:100%;background:var(--bg-card);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none"
                                onkeydown="if(event.key==='Enter')saveEditDebt(${id});if(event.key==='Escape')document.getElementById('debt-edit-row').remove()">
                        </div>
                        <div style="display:flex;gap:8px">
                            <button class="btn btn-success btn-sm" onclick="saveEditDebt(${id})">Save</button>
                            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('debt-edit-row').remove()">Cancel</button>
                        </div>
                    </div>
                </td>`;
            row.after(editRow);
            document.getElementById(`edit-debt-rate-${id}`).focus();
        }

        async function saveEditDebt(id) {
            const rate    = parseFloat(document.getElementById(`edit-debt-rate-${id}`)?.value) || 0;
            const dueDate = document.getElementById(`edit-debt-due-${id}`)?.value || '';
            const notes   = document.getElementById(`edit-debt-notes-${id}`)?.value.trim() || '';
            try {
                await api('update_debt', 'POST', { id, interest_rate: rate, due_date: dueDate, notes });
                await renderDebtList();
            } catch (err) {
                alert(err.message);
            }
        }

        async function updateDashboard() {
            try {
                const stats = await api('get_dashboard');
                document.getElementById('totalCustomers').textContent = stats.totalCustomers;
                document.getElementById('totalOutstanding').textContent = '₱' + parseFloat(stats.totalOutstanding).toFixed(2);
                document.getElementById('totalItems').textContent = stats.totalItems;
                document.getElementById('totalPaid').textContent = stats.totalPaid;
                checkLowStock();
            } catch (err) {
                console.error('Dashboard error:', err);
            }
        }

        async function checkLowStock() {
            try {
                const items = await api('get_items');
                const LOW_THRESHOLD = 5;
                const low = items.filter(i => parseFloat(i.stock || 0) <= LOW_THRESHOLD);
                const bell  = document.getElementById('stockBell');
                const badge = document.getElementById('stockBadge');
                const list  = document.getElementById('stockPanelList');
                if (!bell) return;

                if (!low.length) {
                    bell.style.display = 'none';
                    return;
                }

                bell.style.display = 'flex';
                badge.textContent = low.length;

                list.innerHTML = low.map(i => {
                    const stock = parseFloat(i.stock || 0);
                    const isOut = stock <= 0;
                    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid var(--border);gap:12px">
                        <span style="font-size:0.875rem;font-weight:500">${escapeHtml(i.name)}</span>
                        <span class="badge ${isOut ? 'badge-danger' : 'badge-warning'}">
                            ${isOut ? 'Out of stock' : stock % 1 === 0 ? stock + ' left' : stock.toFixed(3) + ' left'}
                        </span>
                    </div>`;
                }).join('') +
                `<div style="padding:10px 18px">
                    <button class="btn btn-secondary btn-sm" style="width:100%" onclick="showPage('inventory');closeStockPanel()">
                        Go to Inventory →
                    </button>
                </div>`;
            } catch(e) {}
        }

        function toggleStockPanel() {
            const panel = document.getElementById('stockPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function closeStockPanel() {
            const panel = document.getElementById('stockPanel');
            if (panel) panel.style.display = 'none';
        }

        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('stockBellWrap');
            if (wrap && !wrap.contains(e.target)) closeStockPanel();
        });

        async function updateDebtTotal() {
            const itemId = document.getElementById('debtItemSelect').value;
            const quantity = parseFloat(document.getElementById('debtQuantity').value) || 0;
            if (!pricesCache.length) {
                try { pricesCache = await api('get_prices'); } catch(e) {}
            }
            const record = pricesCache.find(p => p.item_id == itemId);
            const total = record ? record.price * quantity : 0;
            document.getElementById('debtTotalDisplay').textContent = '₱' + total.toFixed(2);

            // Update quantity label with unit
            const unit = record ? (record.unit || 'pcs') : 'pcs';
            const label = document.getElementById('debtQuantityLabel');
            if (label) label.textContent = unit === 'kg' ? 'Quantity (kg)' : 'Quantity (pcs)';
        }

        async function loadItemSelect(selectId) {
            const select = document.getElementById(selectId);
            try {
                const items = await api('get_items');
                itemsCache = items;
                select.innerHTML = '<option value="">-- Choose an item --</option>';
                items.forEach(item => {
                    const stock = parseFloat(item.stock || 0);
                    const isOut = stock <= 0;
                    const isLow = stock > 0 && stock <= 5;
                    const stockLabel = isOut ? ' — Out of stock' : isLow ? ` — Low: ${stock % 1 === 0 ? stock : stock.toFixed(3)} left` : ` (${stock % 1 === 0 ? stock : stock.toFixed(3)} left)`;
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name + stockLabel;
                    if (isOut) opt.style.color = 'var(--danger)';
                    else if (isLow) opt.style.color = 'var(--warning)';
                    select.appendChild(opt);
                });
            } catch (err) {
                select.innerHTML = '<option value="">Error loading items</option>';
            }
        }

        async function renderInventoryList() {
            const container = document.getElementById('inventoryList');
            try {
                const [items, prices] = await Promise.all([api('get_items'), api('get_prices')]);
                pricesCache = prices;

                // Build a map of item_id -> price record for quick lookup
                const priceMap = {};
                prices.forEach(p => { priceMap[p.item_id] = p; });

                if (!items.length) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">📦</div><h3>No items yet</h3><p>Add your first item to get started</p></div>';
                    return;
                }

                let html = `<div class="table-container"><table>
                    <thead><tr>
                        <th>Item Name</th>
                        <th>Price (₱)</th>
                        <th>Unit</th>
                        <th>Stock</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr></thead><tbody>`;

                items.forEach(item => {
                    const p    = priceMap[item.id];
                    const unit = p ? (p.unit || 'pcs') : null;
                    const stock = parseFloat(item.stock || 0);
                    const priceDisplay = p
                        ? `<span style="font-family:'JetBrains Mono',monospace" id="price-val-${p.id}">₱${parseFloat(p.price).toFixed(2)}</span>`
                        : `<span style="color:var(--text-muted);font-size:0.8rem">No price</span>`;
                    const unitBadge = p
                        ? `<span class="badge ${unit === 'kg' ? 'badge-info' : 'badge-success'}">${unit}</span>`
                        : `<span style="color:var(--text-muted);font-size:0.8rem">—</span>`;
                    const stockDisplay = stock <= 0
                        ? `<span class="badge badge-danger">Out of stock</span>`
                        : stock <= 5
                        ? `<span class="badge badge-warning" title="Low stock">${stock % 1 === 0 ? stock : stock.toFixed(3)} ${unit || ''}</span>`
                        : `<span style="font-family:'JetBrains Mono',monospace;color:var(--success)">${stock % 1 === 0 ? stock : stock.toFixed(3)} ${unit || ''}</span>`;

                    html += `<tr>
                        <td id="item-name-${item.id}">${escapeHtml(item.name)}</td>
                        <td>${priceDisplay}</td>
                        <td>${unitBadge}</td>
                        <td>${stockDisplay}</td>
                        <td>${formatDate(item.created_at)}</td>
                        <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <button class="btn btn-secondary btn-sm" onclick="startEditItem(${item.id}, '${escapeHtml(item.name).replace(/'/g, "\\'")}', ${p ? p.id : 'null'}, ${p ? p.item_id : 'null'}, ${p ? parseFloat(p.price) : 0}, '${unit || 'pcs'}', ${stock})">✏️ Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteItem(${item.id})">🗑️ Delete</button>
                            </div>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><h3>Error loading inventory</h3></div>';
            }
        }

        // Keep these as aliases so other code that calls them still works
        async function renderItemsList()  { await renderInventoryList(); }
        async function renderPricesList() { await renderInventoryList(); }

        function startEditItem(itemId, currentName, priceId, priceItemId, currentPrice, currentUnit, currentStock) {
            // Close any open edit rows first, then open this one
            renderInventoryList().then(() => {
                const cell = document.getElementById(`item-name-${itemId}`);
                if (!cell) return;
                cell.innerHTML = `
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <input type="text" id="edit-item-input-${itemId}" value="${escapeHtml(currentName)}"
                        style="padding:6px 10px;background:var(--bg-tertiary);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none;min-width:120px;flex:1"
                        onkeydown="if(event.key==='Enter')saveEditItem(${itemId},${priceId},${priceItemId});if(event.key==='Escape')renderInventoryList()">
                    <input type="number" id="edit-item-price-${itemId}" value="${currentPrice}" min="0" step="0.01"
                        style="padding:6px 10px;background:var(--bg-tertiary);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:'JetBrains Mono',monospace;outline:none;width:90px"
                        placeholder="Price" onkeydown="if(event.key==='Enter')saveEditItem(${itemId},${priceId},${priceItemId});if(event.key==='Escape')renderInventoryList()">
                    <input type="number" id="edit-item-stock-${itemId}" value="${currentStock}" min="0" step="0.001"
                        style="padding:6px 10px;background:var(--bg-tertiary);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:'JetBrains Mono',monospace;outline:none;width:80px"
                        placeholder="Stock" onkeydown="if(event.key==='Enter')saveEditItem(${itemId},${priceId},${priceItemId});if(event.key==='Escape')renderInventoryList()">
                    <div style="display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
                        <button type="button" id="edit-unit-pcs-${itemId}" onclick="setInlineUnit(${itemId},'pcs')"
                            style="padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${currentUnit==='pcs'?'var(--accent)':'var(--bg-tertiary)'};color:${currentUnit==='pcs'?'white':'var(--text-secondary)'}">pcs</button>
                        <button type="button" id="edit-unit-kg-${itemId}" onclick="setInlineUnit(${itemId},'kg')"
                            style="padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${currentUnit==='kg'?'var(--accent)':'var(--bg-tertiary)'};color:${currentUnit==='kg'?'white':'var(--text-secondary)'}">kg</button>
                    </div>
                    <input type="hidden" id="edit-item-unit-${itemId}" value="${currentUnit}">
                    <button class="btn btn-success btn-sm" onclick="saveEditItem(${itemId},${priceId},${priceItemId})">Save</button>
                    <button class="btn btn-secondary btn-sm" onclick="renderInventoryList()">Cancel</button>
                </div>`;
                document.getElementById(`edit-item-input-${itemId}`).focus();
            });
        }

        function setInlineUnit(itemId, unit) {
            document.getElementById(`edit-item-unit-${itemId}`).value = unit;
            const pcsBtn = document.getElementById(`edit-unit-pcs-${itemId}`);
            const kgBtn  = document.getElementById(`edit-unit-kg-${itemId}`);
            pcsBtn.setAttribute('style', `padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${unit==='pcs'?'var(--accent)':'var(--bg-tertiary)'};color:${unit==='pcs'?'white':'var(--text-secondary)'}`);
            kgBtn.setAttribute('style',  `padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${unit==='kg'?'var(--accent)':'var(--bg-tertiary)'};color:${unit==='kg'?'white':'var(--text-secondary)'}`);
        }

        async function saveEditItem(itemId, priceId, priceItemId) {
            const nameInput  = document.getElementById(`edit-item-input-${itemId}`);
            const priceInput = document.getElementById(`edit-item-price-${itemId}`);
            const stockInput = document.getElementById(`edit-item-stock-${itemId}`);
            const unitInput  = document.getElementById(`edit-item-unit-${itemId}`);
            const name  = nameInput  ? nameInput.value.trim() : '';
            const price = priceInput ? parseFloat(priceInput.value) : NaN;
            const stock = stockInput ? parseFloat(stockInput.value) : 0;
            const unit  = unitInput  ? unitInput.value : 'pcs';
            if (!name) return;
            try {
                await api('update_item', 'POST', { id: itemId, name, stock: isNaN(stock) ? 0 : stock });
                if (!isNaN(price) && price >= 0 && priceItemId) {
                    await api('add_price', 'POST', { item_id: priceItemId, price, unit });
                }
                await renderInventoryList();
                await loadItemSelect('debtItemSelect');
                pricesCache = await api('get_prices');
                checkLowStock();
            } catch (err) {
                showAlert('itemError', err.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm('Delete this item? This cannot be undone.')) return;
            try {
                await api('delete_item', 'POST', { id });
                await renderInventoryList();
                await loadItemSelect('debtItemSelect');
                pricesCache = await api('get_prices');
                checkLowStock();
            } catch (err) {
                showAlert('itemError', err.message);
            }
        }

        // Keep startEditPrice/saveEditPrice/setEditUnit/deletePrice working via the unified list
        function startEditPrice(id, itemId, currentPrice, currentUnit) {
            startEditItem(itemId, '', id, itemId, currentPrice, currentUnit);
        }
        async function saveEditPrice(id, itemId) {
            const input     = document.getElementById(`edit-price-input-${id}`);
            const unitInput = document.getElementById(`edit-unit-val-${id}`);
            const price = input ? parseFloat(input.value) : NaN;
            const unit  = unitInput ? unitInput.value : 'pcs';
            if (isNaN(price) || price < 0) { showAlert('itemError', 'Please enter a valid price.'); return; }
            try {
                await api('add_price', 'POST', { item_id: itemId, price, unit });
                pricesCache = await api('get_prices');
                await renderInventoryList();
            } catch (err) { showAlert('itemError', err.message); }
        }
        async function deletePrice(id) {
            if (!confirm('Remove this price?')) return;
            try {
                await api('delete_price', 'POST', { id });
                pricesCache = await api('get_prices');
                await renderInventoryList();
            } catch (err) { showAlert('itemError', err.message); }
        }

        async function renderDebtList() {
            const container = document.getElementById('debtListContainer');
            try {
                debtsCache = await api('get_debts');
                const q = (document.getElementById('debtSearch')?.value || '').toLowerCase().trim();
                renderDebtTable(q ? debtsCache.filter(d =>
                    d.customer_name.toLowerCase().includes(q) ||
                    d.item_name.toLowerCase().includes(q) ||
                    (d.phone || '').toLowerCase().includes(q)
                ) : debtsCache);
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><h3>Error loading debts</h3></div>';
            }
        }

        function renderDebtTable(debts) {
            const container = document.getElementById('debtListContainer');
            if (!debts.length) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📋</div><h3>No results</h3><p>No matching debt records found</p></div>';
                return;
            }
            let html = `<div class="table-container"><table><thead><tr>
                <th>Customer</th><th>Phone</th><th>Item</th><th>Qty</th>
                <th>Total</th><th>Paid</th><th>Balance</th><th>Interest</th>
                <th>Due Date</th><th>Status</th><th>Action</th>
            </tr></thead><tbody>`;
            debts.forEach(debt => {
                const isOverdue      = isPastDue(debt.due_date);
                const dateStyle      = isOverdue ? 'style="color:var(--danger);font-weight:600"' : '';
                const overdueText    = isOverdue ? ' ⚠️' : '';
                const total          = parseFloat(debt.total_amount);
                const paid           = parseFloat(debt.amount_paid || 0);
                const interest       = parseFloat(debt.interest_accrued || 0);
                const balanceWithInt = parseFloat(debt.balance_with_interest || (total - paid));
                const daysOverdue    = parseInt(debt.days_overdue || 0);
                const rate           = parseFloat(debt.interest_rate || 0);
                const hasPartial     = paid > 0;
                const hasInterest    = interest > 0;

                const balanceCell = `<span style="font-family:'JetBrains Mono',monospace;color:${hasInterest ? 'var(--danger)' : hasPartial ? 'var(--warning)' : 'var(--text-primary)'};font-weight:${hasInterest || hasPartial ? '600' : '400'}">₱${balanceWithInt.toFixed(2)}</span>`;

                const paidCell = hasPartial
                    ? `<span style="font-family:'JetBrains Mono',monospace;color:var(--success)">₱${paid.toFixed(2)}</span>`
                    : `<span style="color:var(--text-muted)">—</span>`;

                const interestCell = hasInterest
                    ? `<div style="line-height:1.4">
                        <span style="font-family:'JetBrains Mono',monospace;color:var(--danger);font-weight:600">+₱${interest.toFixed(2)}</span>
                        <div style="font-size:0.7rem;color:var(--text-muted)">${rate}%/day × ${daysOverdue}d</div>
                       </div>`
                    : rate > 0
                        ? `<span style="font-size:0.75rem;color:var(--text-muted)">${rate}%/day</span>`
                        : `<span style="color:var(--text-muted)">—</span>`;

                const statusBadge = hasInterest
                    ? `<span class="badge badge-danger">Overdue</span>`
                    : hasPartial
                        ? `<span class="badge badge-warning">Partial</span>`
                        : `<span class="badge badge-danger">Unpaid</span>`;

                html += `<tr${hasInterest ? ' style="background:rgba(239,68,68,0.04)"' : ''}>
                    <td>${escapeHtml(debt.customer_name)}</td>
                    <td style="font-size:0.8125rem;color:var(--text-secondary)">${debt.phone ? escapeHtml(debt.phone) : '<span style="color:var(--text-muted)">—</span>'}</td>
                    <td>${escapeHtml(debt.item_name)}</td>
                    <td>${debt.quantity}</td>
                    <td style="font-family:'JetBrains Mono',monospace">₱${total.toFixed(2)}</td>
                    <td>${paidCell}</td>
                    <td>${balanceCell}</td>
                    <td>${interestCell}</td>
                    <td ${dateStyle}>${formatDate(debt.due_date)}${overdueText}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <input type="number" id="payment-input-${debt.id}" min="0.01" step="0.01"
                                placeholder="₱ amount"
                                style="padding:5px 8px;width:100px;background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.8125rem;font-family:'JetBrains Mono',monospace;outline:none"
                                onkeydown="if(event.key==='Enter')applyPayment(${debt.id})"
                                onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
                            <button class="btn btn-success btn-sm" onclick="applyPayment(${debt.id})">Pay</button>
                            <button class="btn btn-secondary btn-sm" onclick="markAsPaid(${debt.id})" title="Mark fully paid">✓ Full</button>
                            <button class="btn btn-secondary btn-sm" onclick="startEditDebt(${debt.id}, ${rate}, '${debt.due_date}', ${JSON.stringify(debt.notes || '')})">✏️</button>
                        </div>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        let paidCache = [];
        let debtsCache = [];

        async function renderPaidHistory() {
            const container = document.getElementById('paidListContainer');
            try {
                paidCache = await api('get_paid');
                renderPaidTable(paidCache);
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><h3>Error loading history</h3></div>';
            }
        }

        function renderPaidTable(data) {
            const container = document.getElementById('paidListContainer');
            if (!data.length) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📜</div><h3>No results</h3><p>No matching records found</p></div>';
                return;
            }
            let html = '<div class="table-container"><table><thead><tr><th>Customer</th><th>Item</th><th>Qty</th><th>Amount</th><th>Paid Date</th><th>Status</th></tr></thead><tbody>';
            data.forEach(p => {
                html += `<tr>
                    <td>${escapeHtml(p.customer_name)}</td>
                    <td>${escapeHtml(p.item_name)}</td>
                    <td>${p.quantity}</td>
                    <td style="font-family:'JetBrains Mono',monospace">₱${parseFloat(p.total_amount).toFixed(2)}</td>
                    <td>${formatDate(p.paid_at)}</td>
                    <td><span class="badge badge-success">Paid</span></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function filterHistory() {
            const q = document.getElementById('historySearch').value.toLowerCase().trim();
            if (!q) { renderPaidTable(paidCache); return; }
            renderPaidTable(paidCache.filter(p =>
                p.customer_name.toLowerCase().includes(q) ||
                p.item_name.toLowerCase().includes(q)
            ));
        }

        function filterDebts() {
            const q = document.getElementById('debtSearch').value.toLowerCase().trim();
            if (!q) { renderDebtTable(debtsCache); return; }
            renderDebtTable(debtsCache.filter(d =>
                d.customer_name.toLowerCase().includes(q) ||
                d.item_name.toLowerCase().includes(q) ||
                (d.phone || '').toLowerCase().includes(q)
            ));
        }

        function showAlert(id, msg) {
            const el = document.getElementById(id);
            if (msg) el.innerHTML = '<span>⚠️</span> ' + escapeHtml(msg);
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 3000);
        }

        function resetForm(formId) {
            document.getElementById(formId).reset();
            if (formId === 'debtForm') updateDebtTotal();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function parseDateValue(dateStr) {
            if (!dateStr) return null;
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                return new Date(`${dateStr}T00:00:00`);
            }
            return new Date(dateStr);
        }

        function isPastDue(dateStr) {
            const date = parseDateValue(dateStr);
            if (!date || Number.isNaN(date.valueOf())) return false;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return date < today;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = parseDateValue(dateStr);
            if (!date || Number.isNaN(date.valueOf())) return '-';
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        window.onload = async function() {
            initTheme();
            try {
                const result = await api('check_auth');
                if (result.authenticated) {
                    document.getElementById('navbar').style.display = 'block';
                    showPage('dashboard');
                }
            } catch (e) {}
        };
    
