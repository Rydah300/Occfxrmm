<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Cipher Anon RMM</title>

    <script>
        (function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12') { e.preventDefault(); e.stopPropagation(); return false; }
                if (e.ctrlKey && e.shiftKey && (e.key === 'i' || e.key === 'I')) { e.preventDefault(); return false; }
                if (e.ctrlKey && e.shiftKey && (e.key === 'j' || e.key === 'J')) { e.preventDefault(); return false; }
                if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) { e.preventDefault(); return false; }
                if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); return false; }
                if (e.ctrlKey && e.shiftKey && (e.key === 'c' || e.key === 'C')) { e.preventDefault(); return false; }
                if (e.metaKey && (e.key === 'u' || e.key === 'U')) { e.preventDefault(); return false; }
                if (e.metaKey && e.shiftKey && (e.key === 'i' || e.key === 'I')) { e.preventDefault(); return false; }
                if (e.metaKey && e.shiftKey && (e.key === 'j' || e.key === 'J')) { e.preventDefault(); return false; }
            }, true);

            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, true);

            document.onselectstart = function() { return false; };
            document.oncopy = function() { return false; };
            document.oncut = function() { return false; };
            document.onpaste = function() { return false; };

            console.log = function() {};
            console.warn = function() {};
            console.error = function() {};
            console.debug = function() {};
            console.info = function() {};
            console.table = function() {};
            console.trace = function() {};
            console.group = function() {};
            console.groupEnd = function() {};
            console.dir = function() {};
            console.dirxml = function() {};
            console.clear();

            function blockDevtools() {
                try {
                    const start = performance.now();
                    debugger;
                    const end = performance.now();
                    if (end - start > 100) {
                        document.body.innerHTML = '<div style="background:#0a0a0a;color:#ff4444;display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;font-size:24px;">🔒 Access Denied</div>';
                        window.location.reload();
                    }
                } catch {}
            }
            setInterval(blockDevtools, 1000);

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('script').forEach(el => {
                    if (el.src && !el.src.includes('blob:')) {
                        el.removeAttribute('src');
                    }
                });
            });
        })();
    </script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #0b0f1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
            padding: 20px;
            user-select: none;
            -webkit-user-select: none;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: linear-gradient(145deg, #111827, #0f1626);
            border-radius: 20px;
            padding: 40px 32px 32px;
            border: 1px solid #1a2538;
            box-shadow: 0 20px 80px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header .logo-icon {
            font-size: 48px;
            margin-bottom: 8px;
            display: block;
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.5px;
        }

        .login-header h1 .highlight {
            color: #ff4444;
        }

        .login-header p {
            font-size: 13px;
            color: #475569;
            margin-top: 4px;
            font-weight: 400;
        }

        .login-header p .accent {
            color: #00ff88;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 4px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .form-group .input-wrapper {
            position: relative;
            background: #0b0f1a;
            border: 1px solid #1a2538;
            border-radius: 10px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group .input-wrapper:focus-within {
            border-color: #00ff88;
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.08);
        }

        .form-group .input-wrapper .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #475569;
            pointer-events: none;
        }

        .form-group .input-wrapper input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            background: transparent;
            border: none;
            color: #e2e8f0;
            font-size: 14px;
            font-family: inherit;
            outline: none;
        }

        .form-group .input-wrapper input::placeholder {
            color: #334155;
        }

        .form-group .input-wrapper .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: 0.2s;
        }

        .form-group .input-wrapper .toggle-password:hover {
            color: #94a3b8;
        }

        .form-group .error-text {
            font-size: 11px;
            color: #ff4444;
            margin-top: 4px;
            display: none;
            animation: shake 0.4s ease;
        }

        .form-group .error-text.show {
            display: block;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #00ff88, #00cc77);
            border: none;
            border-radius: 10px;
            color: #0b0f1a;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 4px;
            font-family: inherit;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.25);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(11, 15, 26, 0.2);
            border-top-color: #0b0f1a;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .login-btn .spinner.active {
            display: inline-block;
        }

        .login-btn .btn-text {
            transition: 0.3s;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-divider {
            border-top: 1px solid #1a2538;
            margin: 16px 0;
        }

        .login-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #334155;
        }

        .login-footer .powered {
            color: #475569;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        .login-footer .powered .name {
            color: #00ff88;
            font-weight: 600;
        }

        .login-footer .sep {
            color: #1e293b;
        }

        .login-info-hidden {
            display: none;
        }

        @media (max-width: 480px) {
            .login-card { padding: 28px 20px 24px; }
            .login-header h1 { font-size: 18px; }
            .login-header .logo-icon { font-size: 36px; }
            .form-group .input-wrapper input { padding: 10px 10px 10px 36px; font-size: 13px; }
            .login-btn { padding: 10px; font-size: 14px; }
        }

        @media (max-width: 360px) {
            .login-card { padding: 20px 14px; }
            .login-header h1 { font-size: 16px; }
            .login-header .logo-icon { font-size: 30px; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">

            <div class="login-header">
                <span class="logo-icon">📡</span>
                <h1>Cipher <span class="highlight">Anon</span></h1>
                <p><span class="accent">RMM Control</span> Panel · v3.0</p>
            </div>

            <form id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <span class="icon">👤</span>
                        <input type="text" id="username" placeholder="Enter your username" autocomplete="off" />
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <span class="icon">🔒</span>
                        <input type="password" id="password" placeholder="Enter your password" autocomplete="off" />
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                    <div class="error-text" id="loginError">Invalid username or password</div>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="spinner" id="loginSpinner"></span>
                    <span class="btn-text" id="loginBtnText">🔓 Login</span>
                </button>
            </form>

            <div class="login-divider"></div>
            <div class="login-footer">
                <div class="powered">Powered By <span class="name">CipherAnon</span></div>
                <span class="sep">●</span>
                <span class="sep">v3.0</span>
            </div>

        </div>
    </div>

    <script>
        (function() {
            const loginForm = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginSpinner = document.getElementById('loginSpinner');
            const loginError = document.getElementById('loginError');
            const togglePassword = document.getElementById('togglePassword');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
            });

            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const username = usernameInput.value.trim();
                const password = passwordInput.value.trim();

                loginError.classList.remove('show');

                if (!username || !password) {
                    loginError.textContent = 'Please enter both username and password';
                    loginError.classList.add('show');
                    return;
                }

                loginBtn.disabled = true;
                loginSpinner.classList.add('active');
                loginBtnText.textContent = 'Logging in...';

                try {
                    const response = await fetch('/api/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, password })
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'ok') {
                        loginBtnText.textContent = '✅ Success!';
                        window.location.href = '/dashboard';
                    } else {
                        loginError.textContent = data.message || 'Invalid username or password';
                        loginError.classList.add('show');
                        loginBtn.disabled = false;
                        loginSpinner.classList.remove('active');
                        loginBtnText.textContent = '🔓 Login';
                        passwordInput.value = '';
                        passwordInput.focus();
                    }
                } catch (error) {
                    loginError.textContent = 'Connection error. Please try again.';
                    loginError.classList.add('show');
                    loginBtn.disabled = false;
                    loginSpinner.classList.remove('active');
                    loginBtnText.textContent = '🔓 Login';
                }
            });

            passwordInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    loginForm.dispatchEvent(new Event('submit'));
                }
            });

            usernameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    passwordInput.focus();
                }
            });

            usernameInput.focus();
            console.clear();
        })();
    </script>

</body>
</html>
