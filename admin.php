<?php
require_once 'auth.php';
requireAdmin();

$message = '';
$message_type = '';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (empty($new_username) || empty($new_password)) {
        $message = '❌ Username and password required';
        $message_type = 'error';
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
            $stmt->execute([$new_username, $hash, $is_admin]);
            $message = '✅ User created: ' . htmlspecialchars($new_username);
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = '❌ Username already exists';
            $message_type = 'error';
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)$_SESSION['user_id']) {
        $db->exec("DELETE FROM users WHERE id = $id");
        $message = '🗑️ User deleted.';
        $message_type = 'success';
    } else {
        $message = '❌ Cannot delete yourself.';
        $message_type = 'error';
    }
}

$users = $db->query("SELECT * FROM users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>ZerPes · Admin</title>
    <link rel="stylesheet" href="public/style.css">
    <style>
        .admin-container { max-width: 850px; margin: 30px auto; padding: 0 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 30px; }
        .admin-header h1 { font-size: 1.8rem; background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .form-grid { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .form-grid input { flex: 1; min-width: 120px; padding: 10px 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; color: #f0f4ff; }
        .form-grid input:focus { outline: none; border-color: #a855f7; }
        .form-grid button { background: linear-gradient(135deg, #a855f7, #d946ef); border: none; padding: 10px 24px; border-radius: 40px; color: #fff; font-weight: 700; cursor: pointer; }
        .message-box { padding: 12px 18px; border-radius: 14px; margin-bottom: 16px; }
        .message-box.success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.2); color: #86efac; }
        .message-box.error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; }
        .user-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); flex-wrap: wrap; gap: 8px; }
        .user-row .badge { font-size: 0.65rem; padding: 2px 12px; border-radius: 40px; }
        .user-row .badge.admin { background: rgba(168,85,247,0.15); color: #c084fc; }
        .user-row .badge.user { background: rgba(148,163,184,0.1); color: #94a3b8; }
        .user-row .telegram-badge { font-size: 0.65rem; padding: 2px 10px; border-radius: 40px; }
        .user-row .telegram-badge.connected { background: rgba(34,197,94,0.12); color: #86efac; }
        .user-row .telegram-badge.disconnected { background: rgba(239,68,68,0.12); color: #fca5a5; }
        .delete-link { color: #ef4444; text-decoration: none; font-size: 0.8rem; }
        .back-link { color: #94a3b8; text-decoration: none; }
        .unlimited-badge { color: #10b981; font-size: 0.75rem; }
    </style>
</head>
<body style="background:radial-gradient(circle at 20% 30%, #0b0e1a, #03050b); min-height:100vh; padding:20px;">
    <div class="admin-container">
        <div class="admin-header">
            <h1>🜁 ZerPes · Admin</h1>
            <a href="index.php" class="back-link">← Back to Dashboard</a>
        </div>

        <div class="glass" style="padding:28px; border-radius:24px; margin-bottom:24px;">
            <h3 style="margin-bottom:16px;">👤 Create New User</h3>
            <?php if ($message): ?>
                <div class="message-box <?= $message_type ?>"><?= $message ?></div>
            <?php endif; ?>
            <form method="POST" class="form-grid">
                <input type="text" name="new_username" placeholder="Username" required>
                <input type="text" name="new_password" placeholder="Password" required>
                <label style="color:#94a3b8;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                    <input type="checkbox" name="is_admin"> Admin
                </label>
                <button type="submit" name="create_user">➕ Create</button>
            </form>
        </div>

        <div class="glass" style="padding:28px; border-radius:24px;">
            <h3 style="margin-bottom:16px;">📊 Users (<?= count($users) ?>)</h3>
            <?php foreach ($users as $u): ?>
                <div class="user-row">
                    <span>
                        <strong><?= htmlspecialchars($u['username']) ?></strong>
                        <span class="badge <?= $u['is_admin'] ? 'admin' : 'user' ?>"><?= $u['is_admin'] ? 'ADMIN' : 'user' ?></span>
                        <span class="telegram-badge <?= ($u['telegram_connected'] ?? 0) ? 'connected' : 'disconnected' ?>">
                            <?= ($u['telegram_connected'] ?? 0) ? '📡 Bot' : '📡 No Bot' ?>
                        </span>
                        <?php if ($u['platform']): ?>
                            <span style="color:#c084fc;font-size:0.7rem;">📱 <?= $u['platform'] ?> <?= $u['platform_username'] ?></span>
                        <?php endif; ?>
                        <span class="unlimited-badge">♾️ Unlimited</span>
                    </span>
                    <span style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="?delete=<?= $u['id'] ?>" class="delete-link" onclick="return confirm('Delete?')">🗑️</a>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
