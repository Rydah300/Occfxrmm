<?php
// config.php — SQLite version (built into PHP)
session_start();

// Database file
define('DB_PATH', __DIR__ . '/database.sqlite');

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'DB failed: ' . $e->getMessage()]));
}

// Create users table
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create logs table
$db->exec("CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_name TEXT,
    ad_content TEXT,
    target_url TEXT,
    status TEXT,
    response TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Default admin user (only if no users exist)
$stmt = $db->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $default_pass = password_hash('Admin2026!', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', '$default_pass', 1)");
}

// Zernio API config (from Railway env)
$zernio_key = getenv('ZERNIO_API_KEY');
$zernio_url = getenv('ZERNIO_API_URL');

define('ZERNIO_API_KEY', $zernio_key ?: '');
define('ZERNIO_API_URL', $zernio_url ?: 'https://api.zernio.com/v1');

// Session
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
?>
