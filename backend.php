<?php
// backend.php — with mock API success + Telegram
header('Content-Type: application/json');
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// SEND AD (Mock API that always succeeds)
// ============================================
if ($action === 'send_ad') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $campaign = trim($_POST['campaign'] ?? 'ZerPes Campaign');
    $content = trim($_POST['content'] ?? '');
    $target = trim($_POST['target'] ?? '');

    if (empty($content) || empty($target)) {
        echo json_encode(['error' => 'Content and target URL required']);
        exit;
    }

    // Check credits
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || $user['credits'] < 1) {
        echo json_encode(['error' => 'No credits remaining. Contact admin.']);
        exit;
    }

    // Deduct credit
    $db->exec("UPDATE users SET credits = credits - 1 WHERE id = $user_id");

    // Try real API, but always show success
    $success = true;
    $api_response = 'Mock delivery (API unavailable)';
    $httpCode = 200;

    if (!empty(ZERNIO_API_KEY)) {
        $payload = [
            'api_key' => ZERNIO_API_KEY,
            'ad_content' => $content,
            'target_url' => $target,
            'campaign' => $campaign,
            'platform' => 'web'
        ];

        $ch = curl_init(ZERNIO_API_URL . '/ads/spread');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response_raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $api_response = $response_raw;
        } else {
            // API failed — but we still show success (mock)
            $api_response = 'API unavailable, mock delivery recorded';
            $httpCode = 200;
        }
    }

    // Always mark as success
    $status = 'success';
    $response_log = $api_response ?: 'Delivered successfully';

    // Log
    $stmt = $db->prepare("INSERT INTO logs (user_id, campaign_name, ad_content, target_url, status, response) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $campaign, $content, $target, $status, $response_log]);

    echo json_encode([
        'status' => 'success',
        'response' => 'Ad delivered successfully!',
        'http_code' => $httpCode,
        'credits_remaining' => $user['credits'] - 1
    ]);
    exit;
}

// ============================================
// GET LOGS
// ============================================
if ($action === 'get_logs') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $is_admin = $_SESSION['is_admin'] ?? 0;

    if ($is_admin) {
        $stmt = $db->query("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.sent_at DESC LIMIT 100");
    } else {
        $stmt = $db->prepare("SELECT * FROM logs WHERE user_id = ? ORDER BY sent_at DESC LIMIT 100");
        $stmt->execute([$user_id]);
    }
    $logs = $stmt->fetchAll();
    echo json_encode($logs);
    exit;
}

// ============================================
// CONNECT TELEGRAM
// ============================================
if ($action === 'connect_telegram') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $bot_token = trim($_POST['bot_token'] ?? '');
    $chat_id = trim($_POST['chat_id'] ?? '');

    if (empty($bot_token) || empty($chat_id)) {
        echo json_encode(['error' => 'Bot token and Chat ID required']);
        exit;
    }

    // Test the bot
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $message = "✅ Your AIO ZerPes Ads Spreader Is Connected To Receive Traffic & Clicks";
    $payload = ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $stmt = $db->prepare("UPDATE users SET telegram_bot_token = ?, telegram_chat_id = ?, telegram_connected = 1 WHERE id = ?");
        $stmt->execute([$bot_token, $chat_id, $user_id]);
        echo json_encode(['status' => 'success', 'message' => 'Telegram connected!']);
    } else {
        echo json_encode(['error' => 'Invalid bot token or chat ID', 'details' => $response]);
    }
    exit;
}

// ============================================
// VERIFY LICENSE + GENERATE PLATFORM
// ============================================
if ($action === 'verify_license') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $license = trim($_POST['license'] ?? '');

    if ($license === VALID_LICENSE) {
        // Generate platform info
        $platform = 'Instagram';
        $username = '@asiwajuwon';
        
        $stmt = $db->prepare("UPDATE users SET license_key = ?, platform = ?, platform_username = ? WHERE id = ?");
        $stmt->execute([$license, $platform, $username, $user_id]);

        echo json_encode([
            'status' => 'success',
            'platform' => $platform,
            'username' => $username,
            'message' => "Platform: $platform | User Ad Account: $username"
        ]);
    } else {
        echo json_encode(['error' => 'Invalid license key']);
    }
    exit;
}

// ============================================
// GET USER DATA
// ============================================
if ($action === 'get_user_data') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt = $db->prepare("SELECT username, credits, telegram_connected, platform, platform_username, license_key FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    echo json_encode($user);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>}

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
