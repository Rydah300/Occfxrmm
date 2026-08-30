<?php
// backend.php — Fake ad network processing, no Zernio
header('Content-Type: application/json');
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// SEND AD — Fake processing with realistic network logs
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

    // Fake ad networks
    $networks = [
        'Google Ads', 'Meta Ads', 'TikTok Ads', 'Twitter Ads', 
        'LinkedIn Ads', 'Snapchat Ads', 'Pinterest Ads', 
        'Reddit Ads', 'Taboola', 'Outbrain'
    ];
    $network = $networks[array_rand($networks)];

    // Generate realistic fake stats
    $impressions = rand(150, 8500);
    $clicks = rand(12, intval($impressions * 0.08));
    $ctr = round(($clicks / $impressions) * 100, 2);
    $cost = round(($clicks * rand(15, 85)) / 100, 2);

    // Simulate processing delay (3-10 seconds) — feels real
    $delay = rand(4, 10);
    sleep($delay);

    // Build realistic response
    $response_data = [
        'network' => $network,
        'impressions' => $impressions,
        'clicks' => $clicks,
        'ctr' => $ctr . '%',
        'cost' => '$' . $cost,
        'status' => 'delivered',
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => "✅ Ad delivered via {$network} — {$impressions} impressions, {$clicks} clicks, CTR {$ctr}%"
    ];

    $status = 'success';
    $response_log = json_encode($response_data);

    // Log to database
    $stmt = $db->prepare("INSERT INTO logs (user_id, campaign_name, ad_content, target_url, network, impressions, clicks, ctr, status, response) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $campaign, $content, $target, $network, $impressions, $clicks, $ctr, $status, $response_log]);

    // Send Telegram notification if connected
    $stmt = $db->prepare("SELECT telegram_bot_token, telegram_chat_id FROM users WHERE id = ? AND telegram_connected = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $telegram_msg = "📢 *ZerPes Ad Delivered!*\n\n";
        $telegram_msg .= "📋 Campaign: {$campaign}\n";
        $telegram_msg .= "🌐 Network: {$network}\n";
        $telegram_msg .= "👁️ Impressions: {$impressions}\n";
        $telegram_msg .= "🖱️ Clicks: {$clicks}\n";
        $telegram_msg .= "📊 CTR: {$ctr}%\n";
        $telegram_msg .= "💰 Cost: \${$cost}\n";
        $telegram_msg .= "🔗 Target: {$target}";

        $url = "https://api.telegram.org/bot{$user['telegram_bot_token']}/sendMessage";
        $payload = ['chat_id' => $user['telegram_chat_id'], 'text' => $telegram_msg, 'parse_mode' => 'Markdown'];

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
        'response' => $response_data['message'],
        'details' => $response_data,
        'delay' => $delay
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

    // Parse response JSON for display
    foreach ($logs as &$log) {
        $response_data = json_decode($log['response'], true);
        if ($response_data) {
            $log['network'] = $response_data['network'] ?? $log['network'] ?? 'Unknown';
            $log['impressions'] = $response_data['impressions'] ?? $log['impressions'] ?? 0;
            $log['clicks'] = $response_data['clicks'] ?? $log['clicks'] ?? 0;
            $log['ctr'] = $response_data['ctr'] ?? $log['ctr'] ?? '0%';
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
// VERIFY LICENSE + GENERATE PLATFORM
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
