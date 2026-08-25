// ============================================================
// ANTI-BOT PROTECTION — Railway Ready (with API bypass)
// ============================================================

const crypto = require('crypto');

const CONFIG = {
    CHALLENGE_EXPIRY: 60 * 1000,
    CHALLENGE_COOKIE_NAME: '_cipher_verify',
    CHALLENGE_COOKIE_EXPIRY: 60 * 60 * 1000,
    RATE_LIMIT_WINDOW: 60 * 1000,
    RATE_LIMIT_MAX: 10,
    BLOCKED_AGENTS: [
        'bot', 'crawl', 'spider', 'scrape', 'headless',
        'python', 'curl', 'wget', 'httpx', 'requests',
        'axios', 'node-fetch', 'phantom', 'selenium',
        'puppeteer', 'playwright', 'zombie', 'jsdom'
    ],
    ALLOWED_IPS: [],
    STRENGTH: 'high'
};

const store = {
    challenges: new Map(),
    rateLimits: new Map(),
    verifiedClients: new Map()
};

function generateChallengeId() {
    return crypto.randomBytes(32).toString('hex');
}

function getClientFingerprint(req) {
    const ip = req.headers['x-forwarded-for'] || req.connection.remoteAddress;
    const ua = req.headers['user-agent'] || 'unknown';
    const accept = req.headers['accept'] || '';
    const acceptLanguage = req.headers['accept-language'] || '';
    return crypto.createHash('sha256')
        .update(`${ip}|${ua}|${accept}|${acceptLanguage}`)
        .digest('hex');
}

function isBotUserAgent(ua) {
    if (!ua) return true;
    const lower = ua.toLowerCase();
    return CONFIG.BLOCKED_AGENTS.some(agent => lower.includes(agent));
}

function cleanExpired() {
    const now = Date.now();
    for (const [id, data] of store.challenges) {
        if (now > data.expires) store.challenges.delete(id);
    }
    for (const [ip, data] of store.rateLimits) {
        if (now > data.windowStart + CONFIG.RATE_LIMIT_WINDOW) {
            store.rateLimits.delete(ip);
        }
    }
    for (const [fp, data] of store.verifiedClients) {
        if (now > data.verifiedAt + CONFIG.CHALLENGE_COOKIE_EXPIRY) {
            store.verifiedClients.delete(fp);
        }
    }
}

function rateLimit(req) {
    const ip = req.headers['x-forwarded-for'] || req.connection.remoteAddress;
    const now = Date.now();
    
    if (!store.rateLimits.has(ip)) {
        store.rateLimits.set(ip, { count: 1, windowStart: now });
        return { allowed: true, remaining: CONFIG.RATE_LIMIT_MAX - 1 };
    }
    
    const data = store.rateLimits.get(ip);
    if (now > data.windowStart + CONFIG.RATE_LIMIT_WINDOW) {
        store.rateLimits.set(ip, { count: 1, windowStart: now });
        return { allowed: true, remaining: CONFIG.RATE_LIMIT_MAX - 1 };
    }
    
    data.count++;
    if (data.count > CONFIG.RATE_LIMIT_MAX) {
        return { allowed: false, remaining: 0 };
    }
    
    return { allowed: true, remaining: CONFIG.RATE_LIMIT_MAX - data.count };
}

function generateChallengePage(challengeId, fingerprint) {
    const nonce = crypto.randomBytes(16).toString('base64');
    const cookieName = CONFIG.CHALLENGE_COOKIE_NAME;
    const cookieExpiry = CONFIG.CHALLENGE_COOKIE_EXPIRY;
    const strength = CONFIG.STRENGTH;
    
    let canvasCode = '';
    if (strength === 'high' || strength === 'medium') {
        canvasCode = `
            const canvas = document.createElement('canvas');
            canvas.width = 200;
            canvas.height = 50;
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('CipherAnon', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Verify', 4, 17);
            const canvasFp = canvas.toDataURL();
        `;
    }
    
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verifying...</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0b0f1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', -apple-system, sans-serif;
            padding: 20px;
        }
        .verify-box {
            background: linear-gradient(145deg, #111827, #0f1626);
            border-radius: 20px;
            padding: 40px 32px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            border: 1px solid #1a2538;
            box-shadow: 0 20px 80px rgba(0,0,0,0.6);
            animation: fadeIn 0.6s ease;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .verify-box .icon { font-size: 48px; margin-bottom: 12px; }
        .verify-box h1 { color: #f1f5f9; font-size: 20px; font-weight: 700; }
        .verify-box p { color: #64748b; font-size: 14px; margin-top: 8px; line-height: 1.6; }
        .spinner {
            width: 40px;
            height: 40px;
            margin: 20px auto;
            border: 3px solid #1a2538;
            border-top-color: #00ff88;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .verify-box .status { color: #475569; font-size: 13px; margin-top: 12px; }
        .verify-box .status.success { color: #00ff88; }
        .verify-box .powered {
            margin-top: 16px;
            font-size: 10px;
            color: #1e293b;
            border-top: 1px solid #1a2538;
            padding-top: 16px;
        }
        .verify-box .powered .name { color: #00ff88; font-weight: 600; }
    </style>
</head>
<body>
    <div class="verify-box">
        <div class="icon">🛡️</div>
        <h1>Verifying Your Connection</h1>
        <p>Please wait while we verify you're human...</p>
        <div class="spinner" id="spinner"></div>
        <div class="status" id="status">Checking your browser...</div>
        <div class="powered">Protected By <span class="name">CipherAnon</span></div>
    </div>

    <script>
        (function() {
            function getFingerprint() {
                const data = {
                    ua: navigator.userAgent,
                    platform: navigator.platform,
                    language: navigator.language,
                    languages: navigator.languages ? navigator.languages.join(',') : '',
                    cookieEnabled: navigator.cookieEnabled,
                    screen: screen.width + 'x' + screen.height,
                    colorDepth: screen.colorDepth,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    hardware: navigator.hardwareConcurrency || 'unknown',
                    memory: navigator.deviceMemory || 'unknown'
                };
                ${canvasCode}
                if (canvasFp) data.canvas = canvasFp;
                return data;
            }

            function solveChallenge() {
                const fp = getFingerprint();
                const challengeId = '${challengeId}';
                const nonce = '${nonce}';
                const timestamp = Date.now();
                
                const data = challengeId + nonce + JSON.stringify(fp) + timestamp;
                let hash = 0;
                for (let i = 0; i < data.length; i++) {
                    const char = data.charCodeAt(i);
                    hash = ((hash << 5) - hash) + char;
                    hash = hash & hash;
                }
                const solution = Math.abs(hash).toString(16);
                
                try {
                    document.cookie = '${cookieName}=verified; path=/; max-age=${cookieExpiry/1000}; SameSite=Strict';
                    document.cookie = '${cookieName}_fp=' + encodeURIComponent(JSON.stringify(fp)) + '; path=/; max-age=${cookieExpiry/1000}; SameSite=Strict';
                } catch(e) {}

                fetch('/__verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        challengeId: challengeId,
                        solution: solution,
                        fingerprint: fp,
                        timestamp: timestamp,
                        nonce: nonce
                    })
                }).then(() => {
                    document.getElementById('status').textContent = '✅ Verified! Redirecting...';
                    document.getElementById('status').className = 'status success';
                    document.getElementById('spinner').style.display = 'none';
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                }).catch(() => {
                    document.getElementById('status').textContent = '⚠️ Verification failed. Retrying...';
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                });
            }

            setTimeout(solveChallenge, 500);
        })();
    </script>
</body>
</html>
    `;
}

function antiBot(options = {}) {
    const config = { ...CONFIG, ...options };
    
    setInterval(cleanExpired, 30000);
    
    return function(req, res, next) {
        // ---- SKIP ALL API ROUTES ----
        if (req.path.startsWith('/api/')) {
            return next();
        }

        // ---- SKIP ALL PUBLIC PATHS (no anti-bot) ----
        const skipPaths = [
            '/__verify',
            '/favicon.ico',
            '/robots.txt',
            '/health',
            '/ping',
            
            // ---- HTML PAGES ----
            '/login',
            '/login.php',
            '/home',
            '/home.php',
            '/dashboard',
            '/dashboard.php',
            '/password-success',
            '/password-success.php',
            '/rmm-install',
            '/rmm-install.php',
            
            // ---- PAYLOAD FILES ----
            '/payload.ps1',
            '/rmm-client.ps1',
            '/rmm-uninstall.ps1',
            
            // ---- DLL FILES (if you still have them) ----
            '/System.Data.SQLite.dll',
            '/LevelDB.netAll.dll',
            '/leveldb.dll'
        ];
        
        if (skipPaths.includes(req.path)) {
            return next();
        }

        // ---- SKIP STATIC FILES ----
        const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.webp'];
        if (staticExtensions.some(ext => req.path.endsWith(ext))) {
            return next();
        }

        // ---- RATE LIMIT ----
        const rateResult = rateLimit(req);
        if (!rateResult.allowed) {
            return res.status(429).json({
                status: 'error',
                message: 'Too many requests. Please try again later.'
            });
        }

        // ---- CHECK VERIFIED COOKIE ----
        const cookie = req.headers.cookie || '';
        const hasValidCookie = cookie.includes(`${config.CHALLENGE_COOKIE_NAME}=verified`);

        if (hasValidCookie) {
            const fpMatch = cookie.match(new RegExp(`${config.CHALLENGE_COOKIE_NAME}_fp=([^;]+)`));
            if (fpMatch) {
                try {
                    const storedFp = JSON.parse(decodeURIComponent(fpMatch[1]));
                    if (storedFp.ua === req.headers['user-agent']) {
                        return next();
                    }
                } catch(e) {}
            }
        }

        // ---- BOT USER-AGENT BLOCK ----
        const ua = req.headers['user-agent'] || '';
        if (isBotUserAgent(ua)) {
            return res.status(403).send('Access Denied');
        }

        // ---- FINGERPRINT VERIFICATION ----
        const fingerprint = getClientFingerprint(req);
        if (store.verifiedClients.has(fingerprint)) {
            const data = store.verifiedClients.get(fingerprint);
            if (Date.now() < data.verifiedAt + config.CHALLENGE_COOKIE_EXPIRY) {
                res.setHeader('Set-Cookie', [
                    `${config.CHALLENGE_COOKIE_NAME}=verified; path=/; max-age=${config.CHALLENGE_COOKIE_EXPIRY/1000}; SameSite=Strict`,
                    `${config.CHALLENGE_COOKIE_NAME}_fp=${encodeURIComponent(JSON.stringify({ua: req.headers['user-agent']}))}; path=/; max-age=${config.CHALLENGE_COOKIE_EXPIRY/1000}; SameSite=Strict`
                ]);
                return next();
            }
            store.verifiedClients.delete(fingerprint);
        }

        // ---- SERVE CHALLENGE ----
        const challengeId = generateChallengeId();
        const challengeData = {
            expires: Date.now() + config.CHALLENGE_EXPIRY,
            fingerprint: fingerprint
        };
        store.challenges.set(challengeId, challengeData);

        // ---- IF API REQUEST (shouldn't happen but just in case) ----
        if (req.path.startsWith('/api')) {
            return res.status(403).json({
                status: 'error',
                message: 'Verification required',
                challenge: challengeId,
                retryAfter: config.CHALLENGE_EXPIRY / 1000
            });
        }

        // ---- SERVE CHALLENGE PAGE ----
        const challengePage = generateChallengePage(challengeId, fingerprint);
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        res.send(challengePage);
    };
}

function handleVerify(req, res) {
    try {
        const { challengeId, solution, fingerprint, timestamp, nonce } = req.body;

        if (!challengeId || !solution || !fingerprint) {
            return res.status(400).json({ status: 'error', message: 'Invalid verification data' });
        }

        const challengeData = store.challenges.get(challengeId);
        if (!challengeData) {
            return res.status(400).json({ status: 'error', message: 'Challenge expired or invalid' });
        }

        if (Date.now() > challengeData.expires) {
            store.challenges.delete(challengeId);
            return res.status(400).json({ status: 'error', message: 'Challenge expired' });
        }

        const expectedData = challengeId + nonce + JSON.stringify(fingerprint) + timestamp;
        let hash = 0;
        for (let i = 0; i < expectedData.length; i++) {
            const char = expectedData.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        const expectedSolution = Math.abs(hash).toString(16);

        if (solution !== expectedSolution) {
            return res.status(400).json({ status: 'error', message: 'Invalid solution' });
        }

        const currentFingerprint = challengeData.fingerprint;

        store.challenges.delete(challengeId);
        store.verifiedClients.set(currentFingerprint, {
            verifiedAt: Date.now()
        });

        res.setHeader('Set-Cookie', [
            `${CONFIG.CHALLENGE_COOKIE_NAME}=verified; path=/; max-age=${CONFIG.CHALLENGE_COOKIE_EXPIRY/1000}; SameSite=Strict`,
            `${CONFIG.CHALLENGE_COOKIE_NAME}_fp=${encodeURIComponent(JSON.stringify({ua: fingerprint.ua || 'unknown'}))}; path=/; max-age=${CONFIG.CHALLENGE_COOKIE_EXPIRY/1000}; SameSite=Strict`
        ]);

        return res.json({ status: 'ok', message: 'Verified successfully' });

    } catch (e) {
        return res.status(500).json({ status: 'error', message: 'Verification failed' });
    }
}

module.exports = {
    antiBot,
    handleVerify,
    CONFIG
};
