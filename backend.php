<?php
// backend.php — Zernio API handler + log management
header('Content-Type: application/json');
require_once 'config.php';

// CORS headers (optional, for local dev)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// ACTION: send_ad — spread ad via Zernio API
// ============================================
if ($action === 'send_ad') {
    $campaign = trim($_POST['campaign'] ?? 'ZerPes Campaign');
    $content = trim($_POST['content'] ?? '');
    $target = trim($_POST['target'] ?? '');

    // validation
    if (empty($content)) {
        echo json_encode(['error' => 'Ad content is required, baddie.']);
        exit;
    }
    if (empty($target)) {
        echo json_encode(['error' => 'Target URL is required.']);
        exit;
    }
    if (!filter_var($target, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => 'Invalid target URL.']);
        exit;
    }

    // check API key
    if (empty(ZERNIO_API_KEY)) {
        echo json_encode(['error' => 'Zernio API key not configured. Add ZERNIO_API_KEY to Railway variables.']);
        exit;
    }

    // build payload for Zernio
    $payload = [
        'api_key' => ZERNIO_API_KEY,
        'ad_content' => $content,
        'target_url' => $target,
        'campaign' => $campaign,
        'platform' => 'web',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // fire to Zernio
    $ch = curl_init(ZERNIO_API_URL . '/ads/spread');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response_raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // determine status
    $status = ($httpCode === 200 || $httpCode === 201) ? 'success' : 'failed';
    $response_decoded = json_decode($response_raw, true);
    $response_log = $response_decoded ? json_encode($response_decoded) : $response_raw;

    // log to database
    try {
        $stmt = $db->prepare("INSERT INTO logs (campaign_name, ad_content, target_url, status, response) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$campaign, $content, $target, $status, $response_log]);
        $log_id = $db->lastInsertId();
    } catch (PDOException $e) {
        // still return API response but log error
        $log_id = null;
    }

    // return response to frontend
    echo json_encode([
        'status' => $status,
        'http_code' => $httpCode,
        'response' => $response_decoded ?: $response_raw,
        'log_id' => $log_id,
        'curl_error' => $curlError ?: null
    ]);
    exit;
}

// ============================================
// ACTION: get_logs — fetch recent logs
// ============================================
if ($action === 'get_logs') {
    try {
        $stmt = $db->query("SELECT * FROM logs ORDER BY sent_at DESC LIMIT 100");
        $logs = $stmt->fetchAll();
        echo json_encode($logs);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Failed to fetch logs: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ACTION: clear_logs — admin only (optional)
// ============================================
if ($action === 'clear_logs') {
    // optional: require admin session
    session_start();
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        echo json_encode(['error' => 'Admin only, cunt.']);
        exit;
    }
    try {
        $db->exec("DELETE FROM logs");
        echo json_encode(['status' => 'cleared', 'message' => 'All logs cleared.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Clear failed: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// ACTION: test_api — test Zernio connection
// ============================================
if ($action === 'test_api') {
    if (empty(ZERNIO_API_KEY)) {
        echo json_encode(['error' => 'ZERNIO_API_KEY not set.']);
        exit;
    }
    echo json_encode([
        'api_key_configured' => !empty(ZERNIO_API_KEY),
        'api_url' => ZERNIO_API_URL,
        'status' => 'Zernio config loaded'
    ]);
    exit;
}

// ============================================
// default: invalid action
// ============================================
echo json_encode(['error' => 'Invalid action. Valid: send_ad, get_logs, clear_logs, test_api']);
?>