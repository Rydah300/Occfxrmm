(function(){
    'use strict';

    // Anti-devtools
    document.addEventListener('contextmenu', e => e.preventDefault());
    document.addEventListener('keydown', e => {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase()))) {
            e.preventDefault();
            document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;color:#a855f7;font-size:2rem;background:#0b0e1a;">🔒 Nice try, cunt.</div>';
        }
    });

    // Send Ad
    window.sendAd = function() {
        const campaign = document.getElementById('campaign').value.trim() || 'ZerPes Campaign';
        const content = document.getElementById('ad_content').value.trim();
        const target = document.getElementById('target_url').value.trim();
        const statusDiv = document.getElementById('statusMsg');
        const btn = document.getElementById('fireBtn');

        if (!content || !target) {
            statusDiv.className = 'status error';
            statusDiv.innerText = '❌ Content and Target URL required';
            statusDiv.style.display = 'block';
            return;
        }

        try { new URL(target); } catch (_) {
            statusDiv.className = 'status error';
            statusDiv.innerText = '❌ Invalid URL. Include https://';
            statusDiv.style.display = 'block';
            return;
        }

        statusDiv.className = 'status loading';
        statusDiv.innerText = '⏳ Spreading...';
        statusDiv.style.display = 'block';
        btn.disabled = true;
        btn.style.opacity = '0.6';

        const formData = new FormData();
        formData.append('action', 'send_ad');
        formData.append('campaign', campaign);
        formData.append('content', content);
        formData.append('target', target);

        fetch('backend.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.style.opacity = '1';
            if (data.status === 'success') {
                statusDiv.className = 'status success';
                statusDiv.innerText = '✅ ' + data.response;
                if (data.credits_remaining !== undefined) {
                    document.getElementById('creditsLeft').innerText = data.credits_remaining;
                }
            } else {
                statusDiv.className = 'status error';
                statusDiv.innerText = '❌ ' + (data.error || 'Unknown error');
            }
            fetchLogs();
        })
        .catch(err => {
            btn.disabled = false;
            btn.style.opacity = '1';
            statusDiv.className = 'status error';
            statusDiv.innerText = '⚠️ Network error: ' + err.message;
        });
    };

    // Fetch logs
    function fetchLogs() {
        fetch('backend.php?action=get_logs')
        .then(r => r.json())
        .then(logs => {
            const container = document.getElementById('logContainer');
            container.innerHTML = '';
            if (!logs || logs.length === 0) {
                container.innerHTML = '<div class="log-entry empty">No activity yet.</div>';
                document.getElementById('totalSent').innerText = '0';
                document.getElementById('successRate').innerText = '0%';
                return;
            }
            let sent = 0, success = 0;
            logs.forEach(log => {
                sent++;
                if (log.status === 'success') success++;
                const entry = document.createElement('div');
                entry.className = 'log-entry ' + log.status;
                entry.innerHTML = `
                    <span><strong>${escapeHtml(log.campaign_name)}</strong> — ${escapeHtml((log.ad_content || '').substring(0, 50))}${(log.ad_content || '').length > 50 ? '…' : ''}</span>
                    <span style="font-size:0.75rem;color:#64748b;">${escapeHtml(log.sent_at)}</span>
                `;
                container.appendChild(entry);
            });
            document.getElementById('totalSent').innerText = sent;
            document.getElementById('successRate').innerText = sent ? Math.round((success/sent)*100) + '%' : '0%';
        })
        .catch(() => {});
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        fetchLogs();
        setInterval(fetchLogs, 10000);
    });
})();
