// script.js — ZerPes frontend logic, obfuscated + anti-inspect
(function(){
    'use strict';

    // ============================================
    // ANTI-DEVTOOLS — block right-click, F12, shortcuts
    // ============================================
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    document.addEventListener('keydown', function(e) {
        // F12
        if (e.key === 'F12') {
            e.preventDefault();
            blockScreen();
            return false;
        }
        // Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C
        if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
            e.preventDefault();
            blockScreen();
            return false;
        }
        // Ctrl+U (view source)
        if (e.ctrlKey && e.key.toUpperCase() === 'U') {
            e.preventDefault();
            blockScreen();
            return false;
        }
    });

    function blockScreen() {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: #03050b; display:flex; justify-content:center; align-items:center;
            z-index:99999; color:#a855f7; font-size:2rem; font-weight:700;
            font-family: 'Inter', sans-serif; flex-direction:column; gap:12px;
        `;
        overlay.innerHTML = `
            <span style="font-size:3rem;">🔒</span>
            <span>Nice try, cunt.</span>
            <span style="font-size:0.8rem;color:#64748b;">ZerPes is protected</span>
        `;
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
    }

    // ============================================
    // API CALL: sendAd
    // ============================================
    window.sendAd = function() {
        const campaign = document.getElementById('campaign').value.trim() || 'ZerPes Campaign';
        const content = document.getElementById('ad_content').value.trim();
        const target = document.getElementById('target_url').value.trim();
        const statusDiv = document.getElementById('statusMsg');

        // validate
        if (!content) {
            statusDiv.className = 'status error';
            statusDiv.innerText = '❌ Ad content is required, baddie.';
            statusDiv.style.display = 'block';
            return;
        }
        if (!target) {
            statusDiv.className = 'status error';
            statusDiv.innerText = '❌ Target URL is required.';
            statusDiv.style.display = 'block';
            return;
        }
        try {
            new URL(target);
        } catch (_) {
            statusDiv.className = 'status error';
            statusDiv.innerText = '❌ Invalid URL. Include https://';
            statusDiv.style.display = 'block';
            return;
        }

        // show loading
        statusDiv.className = 'status loading';
        statusDiv.innerText = '⏳ Spreading via Zernio...';
        statusDiv.style.display = 'block';

        // disable button
        const btn = document.getElementById('fireBtn');
        btn.disabled = true;
        btn.style.opacity = '0.6';

        const formData = new FormData();
        formData.append('action', 'send_ad');
        formData.append('campaign', campaign);
        formData.append('content', content);
        formData.append('target', target);

        fetch('backend.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.style.opacity = '1';

            if (data.status === 'success') {
                statusDiv.className = 'status success';
                let msg = '✅ Ad spread! ';
                if (data.response && data.response.message) {
                    msg += data.response.message;
                } else if (data.response && typeof data.response === 'string') {
                    msg += data.response;
                } else {
                    msg += 'Delivered via Zernio.';
                }
                statusDiv.innerText = msg;
            } else {
                statusDiv.className = 'status error';
                let errMsg = data.response?.error || data.response || data.error || 'Unknown error';
                if (typeof errMsg === 'object') errMsg = JSON.stringify(errMsg);
                statusDiv.innerText = '❌ Failed: ' + errMsg;
            }
            fetchLogs();
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.style.opacity = '1';
            statusDiv.className = 'status error';
            statusDiv.innerText = '⚠️ Network error: ' + err.message;
        });
    };

    // ============================================
    // fetchLogs — get recent logs from backend
    // ============================================
    function fetchLogs() {
        fetch('backend.php?action=get_logs')
        .then(function(r) { return r.json(); })
        .then(function(logs) {
            const container = document.getElementById('logContainer');
            container.innerHTML = '';

            if (!logs || logs.length === 0) {
                container.innerHTML = '<div class="log-entry empty">No activity yet. Fire some ads, baddie.</div>';
                document.getElementById('totalSent').innerText = '0';
                document.getElementById('successRate').innerText = '0%';
                return;
            }

            let sent = 0;
            let success = 0;

            logs.forEach(function(log) {
                sent++;
                if (log.status === 'success') success++;

                const entry = document.createElement('div');
                entry.className = 'log-entry ' + (log.status === 'success' ? 'success' : 'failed');

                const contentDiv = document.createElement('div');
                contentDiv.className = 'log-content';
                contentDiv.innerHTML = `
                    <span class="campaign-name">${escapeHtml(log.campaign_name || 'Untitled')}</span>
                    <span class="ad-snippet"> — ${escapeHtml((log.ad_content || '').substring(0, 50))}${(log.ad_content || '').length > 50 ? '…' : ''}</span>
                `;

                const metaDiv = document.createElement('div');
                metaDiv.className = 'log-meta';
                metaDiv.innerHTML = `
                    <span class="time">${escapeHtml(log.sent_at || '')}</span>
                    <span class="status-badge ${log.status === 'success' ? 'success' : 'failed'}">${escapeHtml(log.status || 'unknown')}</span>
                `;

                entry.appendChild(contentDiv);
                entry.appendChild(metaDiv);
                container.appendChild(entry);
            });

            document.getElementById('totalSent').innerText = sent;
            document.getElementById('successRate').innerText = sent ? Math.round((success / sent) * 100) + '%' : '0%';
        })
        .catch(function(err) {
            // silent fail — keep existing logs
        });
    }

    // ============================================
    // escapeHtml — prevent XSS
    // ============================================
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ============================================
    // INIT — load logs on page load
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        fetchLogs();
        // refresh every 12 seconds
        setInterval(fetchLogs, 12000);
    });

})();