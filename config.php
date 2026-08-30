<?php
// config.php — PostgreSQL + external DB support
session_start();

// ============================================
// LOAD ENVIRONMENT VARIABLES
// ============================================

// Load from .env file (local dev)
$dotenv = [];
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $dotenv[trim($key)] = trim($value);
        }
    }
}

// Get DATABASE_URL (Railway env OR .env file)
$database_url = getenv('DATABASE_URL');
if (!$database_url && isset($dotenv['DATABASE_URL'])) {
    $database_url = $dotenv['DATABASE_URL'];
}

if (!$database_url) {
    die(json_encode([
        'error' => '❌ DATABASE_URL not set.',
        'fix' => 'Add DATABASE_URL in Railway Variables or .env file'
    ]));
}

// ============================================
// CONNECT TO POSTGRESQL
// ============================================

try {
    $db = new PDO($database_url);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die(json_encode([
        'error' => 'Database connection failed: ' . $e->getMessage(),
        'fix' => 'Check your DATABASE_URL is correct'
    ]));
}

// ============================================
// CREATE TABLES (if they don't exist)
// ============================================

try {
    // users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        is_admin INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // logs table
    $db->exec("CREATE TABLE IF NOT EXISTS logs (
        id SERIAL PRIMARY KEY,
        campaign_name TEXT,
        ad_content TEXT,
        target_url TEXT,
        status TEXT,
        response TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // create default admin if no users exist
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $default_pass = password_hash('Admin2026!', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', '$default_pass', 1)");
        error_log("✅ Default admin user created: admin / Admin2026!");
    }
} catch (PDOException $e) {
    die(json_encode([
        'error' => 'Table creation failed: ' . $e->getMessage()
    ]));
}

// ============================================
// ZERNIO API CONFIG
// ============================================

$zernio_key = getenv('ZERNIO_API_KEY');
if (!$zernio_key && isset($dotenv['ZERNIO_API_KEY'])) {
    $zernio_key = $dotenv['ZERNIO_API_KEY'];
}

$zernio_url = getenv('ZERNIO_API_URL');
if (!$zernio_url && isset($dotenv['ZERNIO_API_URL'])) {
    $zernio_url = $dotenv['ZERNIO_API_URL'];
}

define('ZERNIO_API_KEY', $zernio_key ?: '');
define('ZERNIO_API_URL', $zernio_url ?: 'https://api.zernio.com/v1');

// ============================================
// SESSION SETTINGS
// ============================================

ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// ============================================
// CONNECTION TEST (optional, for debugging)
// ============================================

// Uncomment to test connection:
// error_log("✅ Connected to: " . $db->getAttribute(PDO::ATTR_CONNECTION_STATUS));
?>