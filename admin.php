<?php
// admin.php — ZerPes user management (admin only)
require_once 'auth.php';
requireAdmin();

$message = '';
$message_type = '';

// handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (empty($new_username) || empty($new_password)) {
        $message = '❌ Username and password required, cunt.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = '❌ Password must be at least 6 characters.';
        $message_type = 'error';
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
            $stmt->execute([$new_username, $hash, $is_admin]);
            $message = '✅ User created: ' . htmlspecialchars($new_username);
            $message_type = 'success';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                $message = '❌ Username "' . htmlspecialchars($new_username) . '" already exists.';
            } else {
                $message = '❌ Database error: ' . $e->getMessage();
            }
            $message_type = 'error';
        }
    }
}

// handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === (int)$_SESSION['user_id']) {
        $message = '❌ Cannot delete yourself, genius.';
        $message_type = 'error';
    } else {
        try {
            $db->exec("DELETE FROM users WHERE id = $id");
            $message = '🗑️ User deleted.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = '❌ Delete failed: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// fetch all users
$users = $db->query("SELECT * FROM users ORDER BY id")->fetchAll();
$user_count = count($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZerPes · Admin</title>
    <link rel="stylesheet" href="public/style.css">
    <style>
        .admin-container {
            max-width: 780px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-header h1 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .admin-header .back-dash {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 18px;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 40px;
            transition: 0.25s;
        }
        .admin-header .back-dash:hover {
            border-color: #a855f755;
            color: #c084fc;
        }
        .form-grid {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 8px;
        }
        .form-grid input {
            flex: 1;
            min-width: 140px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #f0f4ff;
            font-size: 0.95rem;
        }
        .form-grid input:focus {
            outline: none;
            border-color: #a855f7;
        }
        .form-grid .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .form-grid .checkbox-label input {
            width: auto;
            min-width: unset;
            flex: 0;
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