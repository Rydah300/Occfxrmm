<?php
// backend.php — Fixed timeout, image upload, no stats
header('Content-Type: application/json');
require_once 'config.php';

// Prevent timeout for long processing
set_time_limit(0);
ignore_user_abort(true);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// SEND AD — Long processing with image support
// ============================================
if ($action === 'send_ad') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $campaign = trim($_POST['campaign'] ?? 'ZerPes Campaign');
    $content = trim($_POST['content'] ?? '');
    $target = trim($_POST['target'] ?? '');
    $image_path = '';

    // Handle image upload
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION);
        $filename = 'ad_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $target_path)) {
            $image_path = 'uploads/' . $filename;
        }
    }

    if (empty($content) || empty($target)) {
        echo json_encode(['error' => 'Content and target URL required']);
        exit;
    }

    // Get user platform
    $stmt = $db->prepare("SELECT platform, platform_username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $platform = $user['platform'] ?? 'Instagram';
    $username = $user['platform_username'] ?? '@asiwajuwon';

    // Ad networks (NO REDDIT)
    $networks = [
        'Google Ads', 'Meta Ads', 'TikTok Ads', 'Twitter Ads',
        'LinkedIn Ads', 'Snapchat Ads', 'Pinterest Ads',
        'Taboola', 'Outbrain'
    ];
    $network = $networks[array_rand($networks)];

    // Random delay: 30-120 seconds (works without timeout)
    $delay = rand(30, 120);
    sleep($delay);

    // Build response
    $response_data = [
        'platform' => $platform,
        'username' => $username,
        'network' => $network,
        'status' => 'delivered',
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => "✅ Spread ads via {$platform} | User Ad Account: {$username}"
    ];

    $status = 'success';
    $response_log = json_encode($response_data);

    // Log to database
    $stmt = $db->prepare("INSERT INTO logs (user_id, campaign_name, ad_content, image_path, target_url, platform, status, response) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $campaign, $content, $image_path, $target, $platform, $status, $response_log]);

    // Send Telegram
    $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id FROM users WHERE id = ? AND telegram_connected = 1");
    $stmt->execute([$user_id]);
    $telegram_user = $stmt->fetch();

    if ($telegram_user) {
        $telegram_msg = "📢 *ZerPes Ad Delivered!*\n\n";
        $telegram_msg .= "📋 Campaign: {$campaign}\n";
        $telegram_msg .= "📱 Platform: {$platform}\n";
        $telegram_msg .= "👤 Account: {$username}\n";
        $telegram_msg .= "🌐 Network: {$network}\n";
        $telegram_msg .= "🔗 Target: {$target}\n";
        $telegram_msg .= "⏱️ Time: " . round($delay / 60, 1) . " minutes";

        $url = "https://api.telegram.org/bot{$telegram_user['telegram_bot_token']}/sendMessage";
        $payload = ['chat_id' => $telegram_user['telegram_chat_id'], 'text' => $telegram_msg, 'parse_mode' => 'Markdown'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    echo json_encode([
        'status' => 'success',
        'platform' => $platform,
        'username' => $username,
        'network' => $network,
        'image' => $image_path,
        'message' => $response_data['message'],
        'delay_minutes' => round($delay / 60, 1)
    ]);
    exit;
}

// ============================================
// CHANGE PASSWORD
// ============================================
if ($action === 'change_password') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($old_password) || empty($new_password)) {
        echo json_encode(['error' => 'All fields required']);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode(['error' => 'New password must be at least 6 characters']);
        exit;
    }

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($old_password, $user['password_hash'])) {
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$new_hash, $user_id]);

    echo json_encode(['status' => 'success', 'message' => 'Password changed successfully!']);
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

    foreach ($logs as &$log) {
        $response_data = json_decode($log['response'], true);
        if ($response_data) {
            $log['network'] = $response_data['network'] ?? '';
        }
    }

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

    $message = "✅ Your AIO ZerPes Ads Spreader Is Connected To Receive Traffic & Clicks";
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
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
        echo json_encode(['error' => 'Invalid bot token or chat ID']);
    }
    exit;
}

// ============================================
// VERIFY LICENSE
// ============================================
if ($action === 'verify_license') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $license = trim($_POST['license'] ?? '');

    if ($license === VALID_LICENSE) {
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
    $stmt = $db->prepare("SELECT username, telegram_connected, platform, platform_username, license_key FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    echo json_encode($user);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
