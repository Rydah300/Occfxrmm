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
    $credits = (int)($_POST['credits'] ?? 999999);

    if (empty($new_username) || empty($new_password)) {
        $message = '❌ Username and password required';
        $message_type = 'error';
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, is_admin, credits) VALUES (?, ?, ?, ?)");
            $stmt->execute([$new_username, $hash, $is_admin, $credits]);
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

// Update credits
if (isset($_POST['update_credits'])) {
    $user_id = (int)$_POST['user_id'];
    $credits = (int)$_POST['credits'];
    $db->exec("UPDATE users SET credits = $credits WHERE id = $user_id");
    $message = '✅ Credits updated.';
    $message_type = 'success';
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
        .credit-edit { display: flex; gap: 8px; align-items: center; }
        .credit-edit input { width: 80px; padding: 4px 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; color: #f0f4ff; }
        .credit-edit button { background: #10b981; border: none; padding: 4px 12px; border-radius: 20px; color: #fff; cursor: pointer; font-size: 0.75rem; }
        .delete-link { color: #ef4444; text-decoration: none; font-size: 0.8rem; }
        .back-link { color: #94a3b8; text-decoration: none; }
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
                <input type="number" name="credits" placeholder="Credits" value="999999">
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
                    </span>
                    <span style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span style="color:#f59e0b;font-size:0.9rem;">⚡ <?= $u['credits'] ?? 0 ?></span>
                        <form method="POST" class="credit-edit" style="display:inline-flex;">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="number" name="credits" value="<?= $u['credits'] ?? 0 ?>" min="0">
                            <button type="submit" name="update_credits">Update</button>
                        </form>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="?delete=<?= $u['id'] ?>" class="delete-link" onclick="return confirm('Delete?')">🗑️</a>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>            flex: 0;
        }
        .form-grid button {
            background: linear-gradient(135deg, #a855f7, #d946ef);
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.95rem;
            flex: 0 0 auto;
        }
        .form-grid button:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 24px #a855f766;
        }
        .message-box {
            padding: 12px 18px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 0.95rem;
        }
        .message-box.success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        .message-box.error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .user-list {
            margin-top: 24px;
        }
        .user-list-header {
            display: flex;
            justify-content: space-between;
            color: #94a3b8;
            font-size: 0.85rem;
            padding: 8px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .user-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            transition: 0.15s;
        }
        .user-row:hover {
            background: rgba(255,255,255,0.02);
        }
        .user-row .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .user-row .user-info .name {
            font-weight: 500;
        }
        .user-row .user-info .badge {
            font-size: 0.65rem;
            padding: 2px 12px;
            border-radius: 40px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .user-row .user-info .badge.admin {
            color: #c084fc;
            background: rgba(168, 85, 247, 0.15);
        }
        .user-row .user-info .badge.user {
            color: #94a3b8;
            background: rgba(148, 163, 184, 0.1);
        }
        .user-row .user-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .user-row .user-actions .date {
            color: #64748b;
            font-size: 0.75rem;
        }
        .user-row .user-actions .delete-link {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.6;
            transition: 0.2s;
        }
        .user-row .user-actions .delete-link:hover {
            opacity: 1;
        }
        .empty-users {
            text-align: center;
            color: #64748b;
            padding: 40px 0;
            font-size: 0.95rem;
        }
        .admin-footer {
            text-align: center;
            color: #64748b;
            font-size: 0.7rem;
            margin-top: 40px;
            letter-spacing: 1px;
            opacity: 0.5;
        }
        @media (max-width: 600px) {
            .form-grid { flex-direction: column; }
            .form-grid button { width: 100%; }
            .user-row { flex-direction: column; gap: 8px; align-items: flex-start; }
            .admin-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body style="background: radial-gradient(circle at 20% 30%, #0b0e1a, #03050b); min-height: 100vh; padding: 20px;">
    <div class="admin-container">
        <div class="admin-header">
            <h1>🜁 ZerPes · Admin</h1>
            <a href="index.php" class="back-dash">← Back to Dashboard</a>
        </div>

        <div class="glass" style="padding:28px; border-radius:24px; margin-bottom:24px;">
            <h3 style="color:#eef2ff; margin-bottom:16px; font-size:1.1rem;">👤 Create New User</h3>
            <p style="color:#64748b; font-size:0.9rem; margin-bottom:18px;">Only admins can create accounts. No signups.</p>

            <?php if ($message): ?>
                <div class="message-box <?= $message_type ?>"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" class="form-grid">
                <input type="text" name="new_username" placeholder="Username" required>
                <input type="text" name="new_password" placeholder="Password (min 6 chars)" required>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_admin"> Admin
                </label>
                <button type="submit" name="create_user">➕ Create</button>
            </form>
        </div>

        <div class="glass" style="padding:28px; border-radius:24px;">
            <div class="user-list-header">
                <span>Users (<?= $user_count ?>)</span>
                <span>Created</span>
            </div>
            <div class="user-list">
                <?php if (empty($users)): ?>
                    <div class="empty-users">No users yet. Create one above.</div>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <div class="user-row">
                            <div class="user-info">
                                <span class="name"><?= htmlspecialchars($u['username']) ?></span>
                                <?php if ($u['is_admin']): ?>
                                    <span class="badge admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge user">User</span>
                                <?php endif; ?>
                                <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                                    <span style="color:#64748b; font-size:0.7rem;">(you)</span>
                                <?php endif; ?>
                            </div>
                            <div class="user-actions">
                                <span class="date"><?= $u['created_at'] ?></span>
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="?delete=<?= $u['id'] ?>" class="delete-link" onclick="return confirm('Delete user <?= htmlspecialchars($u['username']) ?> permanently?')">🗑️</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-footer">ZerPes · v1.0 · PostgreSQL</div>
    </div>
</body>
</html>
