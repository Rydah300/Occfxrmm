<?php
require_once 'auth.php';
requireLogin();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT username, telegram_connected, platform, platform_username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZerPes · Ads Spreader</title>
    <link rel="stylesheet" href="public/style.css">
    <script src="public/script.js" defer></script>
    <style>
        .credit-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 4px 16px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #fff;
        }
        .telegram-status {
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
        }
        .telegram-status.connected {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
        }
        .telegram-status.disconnected {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }
        .license-section {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .license-section input {
            flex: 1;
            min-width: 200px;
            padding: 10px 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #f0f4ff;
        }
        .license-section input:focus {
            outline: none;
            border-color: #a855f7;
        }
        .license-section button {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .license-section button:hover {
            transform: scale(1.02);
        }
        .platform-display {
            background: rgba(255,255,255,0.04);
            padding: 12px 18px;
            border-radius: 12px;
            border-left: 3px solid #a855f7;
            margin-top: 10px;
            font-size: 0.95rem;
        }
        .platform-active {
            border-left-color: #10b981 !important;
            background: rgba(16, 185, 129, 0.06) !important;
        }
        .telegram-section {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .telegram-section input {
            flex: 1;
            min-width: 150px;
            padding: 10px 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #f0f4ff;
        }
        .telegram-section button {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .processing-status {
            padding: 16px;
            text-align: center;
            background: rgba(168, 85, 247, 0.06);
            border-radius: 12px;
            border: 1px solid rgba(168, 85, 247, 0.1);
        }
        .processing-status .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(168, 85, 247, 0.1);
            border-top: 3px solid #a855f7;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 12px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .status-message {
            font-size: 1rem;
            color: #c084fc;
        }
        .platform-badge {
            color: #10b981;
            font-weight: 600;
        }
        .log-platform {
            color: #10b981;
            font-weight: 500;
        }
        .log-network {
            color: #64748b;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header>
            <div class="header-left">
                <h1>🜁 ZerPes</h1>
                <span class="header-tag">Ads Spreader</span>
            </div>
            <div class="header-right">
                <span class="credit-badge">♾️ Unlimited</span>
                <span class="telegram-status <?= ($user['telegram_connected'] ?? 0) ? 'connected' : 'disconnected' ?>">
                    <?= ($user['telegram_connected'] ?? 0) ? '📡 Bot Connected' : '📡 Bot Disconnected' ?>
                </span>
                <span class="user-badge">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="admin.php" class="admin-link">⚙️ Admin</a>
                <?php endif; ?>
                <a href="?logout" class="logout-link">Logout</a>
            </div>
        </header>

        <!-- License Section -->
        <section class="glass">
            <h3>🔑 License & Platform</h3>
            <div class="license-section">
                <input type="text" id="licenseKey" placeholder="Enter your license key" value="AIO-A0J8-OHA1-WLP3">
                <button onclick="verifyLicense()">⚡ Generate Platform</button>
            </div>
            <div id="platformDisplay" class="platform-display" style="display:none;"></div>
        </section>

        <!-- Telegram Section -->
        <section class="glass">
            <h3>📡 Connect Telegram Bot</h3>
            <p style="color:#64748b;font-size:0.85rem;margin-bottom:10px;">Get your bot token from @BotFather and chat ID from @userinfobot</p>
            <div class="telegram-section">
                <input type="text" id="botToken" placeholder="Bot Token (e.g., 123456:ABC-DEF)">
                <input type="text" id="chatId" placeholder="Chat ID (e.g., 123456789)">
                <button onclick="connectTelegram()">🔗 Connect Bot</button>
            </div>
            <div id="telegramStatus" style="margin-top:10px;font-size:0.9rem;"></div>
        </section>

        <!-- Campaign Form -->
        <section class="campaign-form glass">
            <h2>📢 Launch Campaign</h2>
            <input type="text" id="campaign" placeholder="Campaign Name" value="ZerPes #<?= rand(100, 999) ?>">
            <textarea id="ad_content" placeholder="Your ad copy here... 💥"></textarea>
            <input type="url" id="target_url" placeholder="Target URL (e.g., https://your.site)">
            <button id="fireBtn" onclick="sendAd()">🚀 Spread Ads</button>
            <div id="statusMsg" class="status"></div>
            <div id="processingDetails" style="display:none;margin-top:12px;"></div>
        </section>

        <!-- Stats -->
        <section class="stats glass">
            <div class="stat-card">
                <span class="label">Total Sent</span>
                <span class="value" id="totalSent">0</span>
            </div>
            <div class="stat-card">
                <span class="label">Success Rate</span>
                <span class="value" id="successRate">100%</span>
            </div>
            <div class="stat-card">
                <span class="label">Credits</span>
                <span class="value" style="font-size:1.8rem;">♾️</span>
            </div>
        </section>

        <!-- Logs -->
        <section class="log-section glass">
            <h2>📋 Process Log</h2>
            <div id="logContainer" class="log-container">
                <div class="log-entry empty">No activity yet. Launch a campaign, baddie.</div>
            </div>
        </section>

        <footer class="footer">ZerPes · v2.1 · Clean Platform Spreader</footer>
    </div>

    <script>
    function verifyLicense() {
        const license = document.getElementById('licenseKey').value.trim();
        const display = document.getElementById('platformDisplay');

        fetch('backend.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=verify_license&license=' + encodeURIComponent(license)
        })
        .then(r => r.json())
        .then(data => {
            display.style.display = 'block';
            if (data.status === 'success') {
                display.innerHTML = '✅ ' + data.message;
                display.className = 'platform-display platform-active';
            } else {
                display.innerHTML = '❌ ' + data.error;
                display.className = 'platform-display';
            }
        })
        .catch(err => {
            display.style.display = 'block';
            display.innerHTML = '⚠️ Error: ' + err.message;
            display.className = 'platform-display';
        });
    }

    function connectTelegram() {
        const botToken = document.getElementById('botToken').value.trim();
        const chatId = document.getElementById('chatId').value.trim();
        const statusDiv = document.getElementById('telegramStatus');

        if (!botToken || !chatId) {
            statusDiv.innerHTML = '❌ Bot token and Chat ID required';
            statusDiv.style.color = '#fca5a5';
            return;
        }

        statusDiv.innerHTML = '⏳ Connecting...';
        statusDiv.style.color = '#c084fc';

        fetch('backend.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=connect_telegram&bot_token=' + encodeURIComponent(botToken) + '&chat_id=' + encodeURIComponent(chatId)
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                statusDiv.innerHTML = '✅ ' + data.message;
                statusDiv.style.color = '#86efac';
                location.reload();
            } else {
                statusDiv.innerHTML = '❌ ' + data.error;
                statusDiv.style.color = '#fca5a5';
            }
        })
        .catch(err => {
            statusDiv.innerHTML = '⚠️ Error: ' + err.message;
            statusDiv.style.color = '#fca5a5';
        });
    }
    </script>
</body>
</html>
