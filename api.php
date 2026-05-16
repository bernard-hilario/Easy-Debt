<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ==================== AUTH ====================
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            jsonResponse(['success' => true, 'username' => $user['username']]);
        }
        jsonResponse(['error' => 'Invalid credentials'], 401);
        break;

    case 'logout':
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'check_auth':
        if (isset($_SESSION['user_id'])) {
            jsonResponse(['authenticated' => true, 'username' => $_SESSION['username']]);
        }
        jsonResponse(['authenticated' => false]);
        break;

    // ==================== DASHBOARD ====================
    case 'get_dashboard':
        requireAuth();

        // Total unique customers
        $stmt = $pdo->query("SELECT COUNT(DISTINCT customer_name) as total FROM debts");
        $totalCustomers = $stmt->fetch()['total'] ?? 0;

        // Outstanding balance
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM debts WHERE status = 'unpaid'");
        $totalOutstanding = $stmt->fetch()['total'] ?? 0;

        // Total items
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM items");
        $totalItems = $stmt->fetch()['total'] ?? 0;

        // Total paid records
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM debts WHERE status = 'paid'");
        $totalPaid = $stmt->fetch()['total'] ?? 0;

        jsonResponse([
            'totalCustomers' => (int)$totalCustomers,
            'totalOutstanding' => (float)$totalOutstanding,
            'totalItems' => (int)$totalItems,
            'totalPaid' => (int)$totalPaid
        ]);
        break;

    // ==================== ITEMS ====================
    case 'get_items':
        requireAuth();
        $stmt = $pdo->query("SELECT * FROM items ORDER BY name ASC");
        jsonResponse($stmt->fetchAll());
        break;

    case 'add_item':
        requireAuth();
        $data  = json_decode(file_get_contents('php://input'), true);
        $name  = trim($data['name'] ?? '');
        $stock = max(0, (float)($data['stock'] ?? 0));

        if (empty($name)) {
            jsonResponse(['error' => 'Item name is required'], 400);
        }

        $stmt = $pdo->prepare("SELECT id FROM items WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Item already exists'], 409);
        }

        $stmt = $pdo->prepare("INSERT INTO items (name, stock) VALUES (?, ?)");
        $stmt->execute([$name, $stock]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update_item':
        requireAuth();
        $data  = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($data['id'] ?? 0);
        $name  = trim($data['name'] ?? '');
        $stock = isset($data['stock']) ? max(0, (float)$data['stock']) : null;

        if ($id <= 0 || empty($name)) {
            jsonResponse(['error' => 'Invalid data'], 400);
        }

        $stmt = $pdo->prepare("SELECT id FROM items WHERE LOWER(name) = LOWER(?) AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Item name already exists'], 409);
        }

        if ($stock !== null) {
            $stmt = $pdo->prepare("UPDATE items SET name = ?, stock = ? WHERE id = ?");
            $stmt->execute([$name, $stock, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE items SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
        }

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Item not found'], 404);
        break;

    case 'delete_item':
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(['error' => 'Invalid ID'], 400);
        }

        // Prevent deletion if item has associated debts
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM debts WHERE item_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetch()['cnt'] > 0) {
            jsonResponse(['error' => 'Cannot delete item with existing debt records'], 409);
        }

        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Item not found'], 404);
        break;

    // ==================== PRICES ====================
    case 'get_prices':
        requireAuth();
        try {
            $stmt = $pdo->query("
                SELECT p.*, i.name as item_name 
                FROM prices p 
                JOIN items i ON p.item_id = i.id 
                ORDER BY i.name ASC
            ");
            jsonResponse($stmt->fetchAll());
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'add_price':
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $itemId = (int)($data['item_id'] ?? 0);
        $price  = (float)($data['price'] ?? 0);
        $unit   = in_array($data['unit'] ?? '', ['pcs', 'kg']) ? $data['unit'] : 'pcs';

        if ($itemId <= 0 || $price < 0) {
            jsonResponse(['error' => 'Invalid data'], 400);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO prices (item_id, price, unit) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE price = VALUES(price), unit = VALUES(unit)
            ");
            $stmt->execute([$itemId, $price, $unit]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete_price':
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(['error' => 'Invalid ID'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM prices WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Price not found'], 404);
        break;

    // ==================== DEBTS ====================
    case 'get_debts':
        requireAuth();
        $status = $_GET['status'] ?? 'unpaid';
        $stmt = $pdo->prepare("
            SELECT d.*, i.name as item_name 
            FROM debts d 
            JOIN items i ON d.item_id = i.id 
            WHERE d.status = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$status]);
        $debts = $stmt->fetchAll();

        // Compute accrued interest for overdue unpaid debts
        $today = new DateTime('today');
        foreach ($debts as &$debt) {
            $rate = (float)($debt['interest_rate'] ?? 0);
            $dueDate = new DateTime($debt['due_date']);
            $balance = (float)$debt['total_amount'] - (float)$debt['amount_paid'];
            if ($rate > 0 && $debt['status'] === 'unpaid' && $today > $dueDate && $balance > 0) {
                $daysOverdue = (int)$today->diff($dueDate)->days;
                $debt['days_overdue']      = $daysOverdue;
                $debt['interest_accrued']  = round($balance * ($rate / 100) * $daysOverdue, 2);
                $debt['balance_with_interest'] = round($balance + $debt['interest_accrued'], 2);
            } else {
                $debt['days_overdue']          = 0;
                $debt['interest_accrued']      = 0;
                $debt['balance_with_interest'] = round($balance, 2);
            }
        }
        unset($debt);
        jsonResponse($debts);
        break;

    case 'add_debt':
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        $customer = trim($data['customer_name'] ?? '');
        $phone    = trim($data['phone'] ?? '');
        $itemId   = (int)($data['item_id'] ?? 0);
        $quantity = (float)($data['quantity'] ?? 0);
        $dueDate  = $data['due_date'] ?? '';
        $notes    = trim($data['notes'] ?? '');
        $interestRate = max(0, (float)($data['interest_rate'] ?? 0));

        if (empty($customer) || $itemId <= 0 || $quantity <= 0 || empty($dueDate)) {
            jsonResponse(['error' => 'All fields are required'], 400);
        }

        // Get item price
        $stmt = $pdo->prepare("SELECT price FROM prices WHERE item_id = ?");
        $stmt->execute([$itemId]);
        $priceRecord = $stmt->fetch();

        if (!$priceRecord) {
            jsonResponse(['error' => 'No price set for this item'], 400);
        }

        $pricePerUnit = (float)$priceRecord['price'];
        $totalAmount = $pricePerUnit * $quantity;

        $stmt = $pdo->prepare("
            INSERT INTO debts (customer_name, phone, item_id, quantity, price_per_unit, total_amount, due_date, notes, interest_rate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customer, $phone, $itemId, $quantity, $pricePerUnit, $totalAmount, $dueDate, $notes, $interestRate]);

        // Deduct stock
        $pdo->prepare("UPDATE items SET stock = GREATEST(0, stock - ?) WHERE id = ?")->execute([$quantity, $itemId]);

        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'total_amount' => $totalAmount]);
        break;

    case 'mark_paid':
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $debtId = (int)($data['id'] ?? 0);

        if ($debtId <= 0) {
            jsonResponse(['error' => 'Invalid ID'], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE debts 
            SET status = 'paid', amount_paid = total_amount, paid_at = NOW() 
            WHERE id = ? AND status = 'unpaid'
        ");
        $stmt->execute([$debtId]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Debt not found or already paid'], 404);
        break;

    case 'update_debt':
        requireAuth();
        $data         = json_decode(file_get_contents('php://input'), true);
        $id           = (int)($data['id'] ?? 0);
        $interestRate = max(0, (float)($data['interest_rate'] ?? 0));
        $dueDate      = trim($data['due_date'] ?? '');
        $notes        = trim($data['notes'] ?? '');

        if ($id <= 0) {
            jsonResponse(['error' => 'Invalid ID'], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE debts SET interest_rate = ?, due_date = ?, notes = ?
            WHERE id = ? AND status = 'unpaid'
        ");
        $stmt->execute([$interestRate, $dueDate, $notes, $id]);

        jsonResponse(['success' => true]);
        break;

    case 'partial_payment':
        requireAuth();
        $data    = json_decode(file_get_contents('php://input'), true);
        $debtId  = (int)($data['id'] ?? 0);
        $payment = (float)($data['amount'] ?? 0);

        if ($debtId <= 0 || $payment <= 0) {
            jsonResponse(['error' => 'Invalid data'], 400);
        }

        $stmt = $pdo->prepare("SELECT total_amount, amount_paid FROM debts WHERE id = ? AND status = 'unpaid'");
        $stmt->execute([$debtId]);
        $debt = $stmt->fetch();

        if (!$debt) {
            jsonResponse(['error' => 'Debt not found or already paid'], 404);
        }

        $newPaid = (float)$debt['amount_paid'] + $payment;
        $total   = (float)$debt['total_amount'];

        if ($newPaid >= $total) {
            $stmt = $pdo->prepare("UPDATE debts SET amount_paid = ?, status = 'paid', paid_at = NOW() WHERE id = ?");
            $stmt->execute([$total, $debtId]);
            jsonResponse(['success' => true, 'fully_paid' => true, 'balance' => 0]);
        } else {
            $stmt = $pdo->prepare("UPDATE debts SET amount_paid = ? WHERE id = ?");
            $stmt->execute([$newPaid, $debtId]);
            jsonResponse(['success' => true, 'fully_paid' => false, 'balance' => round($total - $newPaid, 2), 'amount_paid' => round($newPaid, 2)]);
        }
        break;

    // ==================== PAID HISTORY ====================
    case 'get_paid':
        requireAuth();
        $stmt = $pdo->query("
            SELECT d.*, i.name as item_name 
            FROM debts d 
            JOIN items i ON d.item_id = i.id 
            WHERE d.status = 'paid'
            ORDER BY d.paid_at DESC
        ");
        jsonResponse($stmt->fetchAll());
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);



        
}