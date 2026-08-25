<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed — Cipher Anon RMM</title>

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

        .success-container {
            width: 100%;
            max-width: 440px;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .success-card {
            background: linear-gradient(145deg, #111827, #0f1626);
            border-radius: 20px;
            padding: 40px 32px 32px;
            border: 1px solid #1a2538;
            box-shadow: 0 20px 80px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 12px;
            display: block;
        }

        .success-card h1 {
            font-size: 24px;
            font-weight: 700;
            color: #00ff88;
            margin-bottom: 8px;
        }

        .success-card p {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .success-card .highlight {
            color: #ff4444;
        }

        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #00ff88, #00cc77);
            border: none;
            border-radius: 10px;
            color: #0b0f1a;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.25);
        }

        .countdown {
            color: #475569;
            font-size: 13px;
            margin-top: 16px;
        }

        .countdown .timer {
            color: #00ff88;
            font-weight: 600;
        }

        .powered-footer {
            text-align: center;
            font-size: 11px;
            color: #1e293b;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #1a2538;
        }

        .powered-footer .name {
            color: #00ff88;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .success-card { padding: 28px 20px 24px; }
            .success-card h1 { font-size: 20px; }
            .success-icon { font-size: 48px; }
            .btn { padding: 10px 24px; font-size: 14px; }
        }
    </style>
</head>
<body>

    <div class="success-container">
        <div class="success-card">
            <span class="success-icon">🔒</span>
            <h1>Password Changed</h1>
            <p>
                Your password has been successfully updated.<br />
                You have been logged out for security reasons.<br />
                Please log in again with your <span class="highlight">new password</span>.
            </p>

            <a href="/login.php" class="btn" id="loginBtn">🔓 Login with New Password</a>

            <div class="countdown">
                Redirecting in <span class="timer" id="countdownTimer">5</span> seconds...
            </div>

            <div style="margin-top:20px;">
                <div class="powered-footer">
                    Powered By <span class="name">CipherAnon</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            let seconds = 5;
            const timerEl = document.getElementById('countdownTimer');

            const interval = setInterval(() => {
                seconds--;
                timerEl.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = '/login.php';
                }
            }, 1000);

            document.getElementById('loginBtn').addEventListener('click', function(e) {
                clearInterval(interval);
                window.location.href = '/login.php';
            });

            console.clear();
        })();
    </script>

</body>
</html>