<?php
// config.php — Unlimited credits, SQLite
session_start();

define('DB_PATH', __DIR__ . '/database.sqlite');

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'DB failed: ' . $e->getMessage()]));
}

// Users table
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0,
    telegram_bot_token TEXT,
    telegram_chat_id TEXT,
    telegram_connected INTEGER DEFAULT 0,
    license_key TEXT,
    platform TEXT,
    platform_username TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Logs table
$db->exec("CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    campaign_name TEXT,
    ad_content TEXT,
    target_url TEXT,
    network TEXT,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    ctr TEXT,
    status TEXT,
    response TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Default admin (only if no users exist)
$stmt = $db->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $default_pass = password_hash('Admin2026!', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', '$default_pass', 1)");
}

// License key
define('VALID_LICENSE', 'AIO-A0J8-OHA1-WLP3');

// Session
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
?>
