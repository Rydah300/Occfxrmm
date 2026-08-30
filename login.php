<?php
require_once 'auth.php';
redirectIfLoggedIn();

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '❌ Username and password required.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (int)$user['is_admin'];
                header('Location: index.php');
                exit;
            } else {
                $error = '❌ Invalid credentials.';
            }
        } catch (PDOException $e) {
            $error = '❌ Database error. Try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZerPes · Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: radial-gradient(circle at 20% 30%, #0b0e1a, #03050b); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .login-box { max-width: 400px; width: 100%; padding: 48px 40px; background: rgba(255,255,255,0.04); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.06); border-radius: 28px; box-shadow: 0 30px 80px rgba(0,0,0,0.7); text-align: center; }
        .login-box .brand-icon { font-size: 2.8rem; margin-bottom: 4px; }
        .login-box h1 { font-size: 2.2rem; font-weight: 700; background: linear-gradient(135deg, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -0.5px; }
        .login-box .subtitle { color: #94a3b8; font-size: 0.95rem; margin-bottom: 6px; }
        .login-box .tagline { color: #64748b; font-size: 0.85rem; margin-bottom: 32px; }
        .login-box input { width: 100%; padding: 14px 18px; margin-bottom: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; color: #f0f4ff; font-size: 1rem; transition: 0.25s; }
        .login-box input::placeholder { color: #64748b; }
        .login-box input:focus { outline: none; border-color: #a855f7; background: rgba(168,85,247,0.08); box-shadow: 0 0 20px #a855f733; }
        .login-box button { background: linear-gradient(135deg, #a855f7, #d946ef); border: none; padding: 16px; border-radius: 40px; font-weight: 700; font-size: 1.1rem; color: #fff; cursor: pointer; width: 100%; transition: 0.3s; margin-top: 6px; }
        .login-box button:hover { transform: scale(1.02); box-shadow: 0 8px 30px #a855f766; }
        .error { color: #fca5a5; background: rgba(239,68,68,0.15); padding: 12px 16px; border-radius: 14px; margin-bottom: 18px; font-size: 0.95rem; border: 1px solid rgba(239,68,68,0.2); }
        .brand-foot { color: #a855f744; font-size: 0.65rem; margin-top: 24px; letter-spacing: 2px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="brand-icon">🜁</div>
        <h1>ZerPes</h1>
        <div class="subtitle">Ads Spreader</div>
        <div class="tagline">Login to launch campaigns</div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($username) ?>" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">🚀 Enter</button>
        </form>

        <div class="brand-foot">ZerPes · v2.0</div>
    </div>
</body>
</html>
