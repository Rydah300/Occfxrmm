<?php
// config.php — SQLite version (no driver needed)
session_start();

// Database file path
define('DB_PATH', __DIR__ . '/database.sqlite');

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
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

// Create default admin user if no users exist
$stmt = $db->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $default_pass = password_hash('Admin2026!', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', '$default_pass', 1)");
}

// Zernio API config
$zernio_key = getenv('ZERNIO_API_KEY');
if (!$zernio_key && file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'ZERNIO_API_KEY') !== false) {
            list(, $value) = explode('=', $line, 2);
            $zernio_key = trim($value);
        }
    }
}

$zernio_url = getenv('ZERNIO_API_URL');
if (!$zernio_url && file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'ZERNIO_API_URL') !== false) {
            list(, $value) = explode('=', $line, 2);
            $zernio_url = trim($value);
        }
    }
}

define('ZERNIO_API_KEY', $zernio_key ?: '');
define('ZERNIO_API_URL', $zernio_url ?: 'https://api.zernio.com/v1');

// Session settings
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
?>
