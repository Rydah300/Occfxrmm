<?php
// index.php — ZerPes dashboard (protected)
require_once 'auth.php';
requireLogin();

// handle logout
if (isset($_GET['logout'])) {
    logout();
    header('Location: login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$is_admin = $_SESSION['is_admin'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZerPes · Ads Spreader</title>
    <link rel="stylesheet" href="public/style.css">
    <script src="public/script.js" defer></script>
</head>
<body>
    <div class="dashboard">
        <header>
            <div class="header-left">
                <h1>🜁 ZerPes</h1>
                <span class="header-tag">Ads Spreader</span>
            </div>
            <div class="header-right">
                <span class="user-badge">👤 <?= $username ?></span>
                <?php if ($is_admin): ?>
                    <a href="admin.php" class="admin-link">⚙️ Admin</a>
                <?php endif; ?>
                <a href="?logout" class="logout-link">Logout</a>
            </div>
        </header>

        <section class="campaign-form glass">
            <h2>📢 Launch Campaign</h2>
            <input type="text" id="campaign" placeholder="Campaign Name" value="ZerPes #<?= rand(100, 999) ?>">
            <textarea id="ad_content" placeholder="Your ad copy here... 💥"></textarea>
            <input type="url" id="target_url" placeholder="Target URL (e.g., https://your.site)">
            <button id="fireBtn" onclick="sendAd()">🚀 Spread via Zernio</button>
            <div id="statusMsg" class="status"></div>
        </section>

        <section class="stats glass">
            <div class="stat-card">
                <span class="label">Total Sent</span>
                <span class="value" id="totalSent">0</span>
            </div>
            <div class="stat-card">
                <span class="label">Success Rate</span>
                <span class="value" id="successRate">0%</span>
            </div>
        </section>

        <section class="log-section glass">
            <h2>📋 Process Log</h2>
            <div id="logContainer" class="log-container">
                <div class="log-entry empty">No activity yet. Fire some ads, baddie.</div>
            </div>
        </section>

        <footer class="footer">
            ZerPes · Powered by Zernio API · PostgreSQL
        </footer>
    </div>
</body>
</html>