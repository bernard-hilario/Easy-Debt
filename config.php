<?php
// Database configuration. Production hosts can inject these as environment variables.
function envOrDefault($key, $default) {
    $value = getenv($key);
    return $value !== false && $value !== '' ? $value : $default;
}

define('DB_HOST', envOrDefault('DB_HOST', 'localhost'));
define('DB_PORT', envOrDefault('DB_PORT', '3306'));
define('DB_USER', envOrDefault('DB_USER', 'root'));
define('DB_PASS', envOrDefault('DB_PASS', ''));
define('DB_NAME', envOrDefault('DB_NAME', 'easydebt_db'));

// Create PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Run any pending schema migrations
try {
    $pdo->exec("ALTER TABLE prices ADD COLUMN IF NOT EXISTS unit ENUM('pcs','kg') NOT NULL DEFAULT 'pcs'");
    $pdo->exec("ALTER TABLE debts MODIFY COLUMN quantity DECIMAL(10,3) NOT NULL");
    $pdo->exec("ALTER TABLE debts ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS stock DECIMAL(10,3) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE debts ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NOT NULL DEFAULT ''");
    $pdo->exec("ALTER TABLE debts ADD COLUMN IF NOT EXISTS interest_rate DECIMAL(5,2) NOT NULL DEFAULT 0");
} catch (PDOException $e) {
    // Migrations are best-effort; ignore errors here
}

// Helper function to send JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to check if user is logged in
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}
