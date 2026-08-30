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

    // Send Ad — with realistic processing
    window.sendAd = function() {
        const campaign = document.getElementById('campaign').value.trim() || 'ZerPes Campaign';
        const content = document.getElementById('ad_content').value.trim();
        const target = document.getElementById('target_url').value.trim();
        const statusDiv = document.getElementById('statusMsg');
        const detailsDiv = document.getElementById('processingDetails');
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

        // Show processing
        statusDiv.className = 'status loading';
        statusDiv.innerText = '⏳ Connecting to ad networks...';
        statusDiv.style.display = 'block';
        detailsDiv.style.display = 'block';
        detailsDiv.innerHTML = `
            <div class="processing-status">
                🔄 Scanning ad inventory...<br>
                📡 Connecting to networks...
            </div>
        `;

        btn.disabled = true;
        btn.style.opacity = '0.6';

        // Simulate network progress updates
        let step = 0;
        const steps = [
            '🔄 Scanning ad inventory...',
            '📡 Connecting to networks...',
            '📤 Uploading campaign data...',
            '⚡ Distributing across networks...',
            '📊 Aggregating results...'
        ];

        const interval = setInterval(() => {
            if (step < steps.length) {
                detailsDiv.innerHTML = `<div class="processing-status">${steps[step]}</div>`;
                step++;
            }
        }, 1500);

        const formData = new FormData();
        formData.append('action', 'send_ad');
        formData.append('campaign', campaign);
        formData.append('content', content);
        formData.append('target', target);

        fetch('backend.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            clearInterval(interval);
            btn.disabled = false;
            btn.style.opacity = '1';
            detailsDiv.style.display = 'none';

            if (data.status === 'success') {
                statusDiv.className = 'status success';
                statusDiv.innerText = '✅ ' + data.response;
                // Show detailed stats
                if (data.details) {
                    detailsDiv.style.display = 'block';
                    detailsDiv.innerHTML = `
                        <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:0.85rem;">
                            <span style="color:#c084fc;">🌐 ${data.details.network}</span>
                            <span style="color:#f59e0b;">👁️ ${data.details.impressions.toLocaleString()}</span>
                            <span style="color:#3b82f6;">🖱️ ${data.details.clicks.toLocaleString()}</span>
                            <span style="color:#10b981;">📊 ${data.details.ctr}</span>
                            <span style="color:#94a3b8;">💰 ${data.details.cost || 'N/A'}</span>
                            <span style="color:#64748b;font-size:0.75rem;">⏱️ ${data.delay}s</span>
                        </div>
                    `;
                }
            } else {
                statusDiv.className = 'status error';
                statusDiv.innerText = '❌ ' + (data.error || 'Unknown error');
            }
            fetchLogs();
        })
        .catch(err => {
            clearInterval(interval);
            btn.disabled = false;
            btn.style.opacity = '1';
            detailsDiv.style.display = 'none';
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
                container.innerHTML = '<div class="log-entry empty">No activity yet. Launch a campaign, baddie.</div>';
                document.getElementById('totalSent').innerText = '0';
                document.getElementById('successRate').innerText = '100%';
                return;
            }
            let sent = 0, success = 0;
            logs.forEach(log => {
                sent++;
                if (log.status === 'success') success++;
                const entry = document.createElement('div');
                entry.className = 'log-entry ' + log.status;
                const imps = log.impressions || 0;
                const clks = log.clicks || 0;
                const ctr = log.ctr || '0%';
                const network = log.network || 'Unknown';
                entry.innerHTML = `
                    <div style="width:100%;">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                            <span><strong>${escapeHtml(log.campaign_name)}</strong> — ${escapeHtml((log.ad_content || '').substring(0, 40))}${(log.ad_content || '').length > 40 ? '…' : ''}</span>
                            <span style="font-size:0.7rem;color:#64748b;">${escapeHtml(log.sent_at)}</span>
                        </div>
                        <div class="log-details" style="margin-top:4px;">
                            <span class="net">🌐 ${escapeHtml(network)}</span>
                            <span class="imp">👁️ ${imps.toLocaleString()}</span>
                            <span class="clk">🖱️ ${clks.toLocaleString()}</span>
                            <span class="ctr">📊 ${ctr}</span>
                        </div>
                    </div>
                `;
                container.appendChild(entry);
            });
            document.getElementById('totalSent').innerText = sent;
            document.getElementById('successRate').innerText = sent ? Math.round((success/sent)*100) + '%' : '100%';
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
        setInterval(fetchLogs, 8000);
    });
})();
