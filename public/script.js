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

    // Send Ad — Long processing, clean platform display
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

        // Show processing — simple and clean
        statusDiv.className = 'status loading';
        statusDiv.innerText = '⏳ Processing...';
        statusDiv.style.display = 'block';
        detailsDiv.style.display = 'block';
        detailsDiv.innerHTML = `
            <div class="processing-status">
                <div class="spinner"></div>
                <span class="status-message">Spreading ads across networks...</span>
                <div style="margin-top:8px;font-size:0.8rem;color:#64748b;">This may take several minutes</div>
            </div>
        `;

        btn.disabled = true;
        btn.style.opacity = '0.6';

        const formData = new FormData();
        formData.append('action', 'send_ad');
        formData.append('campaign', campaign);
        formData.append('content', content);
        formData.append('target', target);

        const startTime = Date.now();

        fetch('backend.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.style.opacity = '1';

            if (data.status === 'success') {
                const elapsed = Math.round((Date.now() - startTime) / 60000);
                statusDiv.className = 'status success';
                statusDiv.innerText = '✅ ' + data.message;
                detailsDiv.innerHTML = `
                    <div style="padding:12px 16px;background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.1);border-radius:12px;text-align:center;">
                        <div style="font-size:1rem;color:#86efac;">
                            ✅ Spread ads via <strong style="color:#10b981;">${data.platform}</strong>
                        </div>
                        <div style="font-size:0.8rem;color:#64748b;margin-top:4px;">
                            User Ad Account: ${data.username} • ${data.network} • ${elapsed}m
                        </div>
                    </div>
                `;
            } else {
                statusDiv.className = 'status error';
                statusDiv.innerText = '❌ ' + (data.error || 'Unknown error');
                detailsDiv.style.display = 'none';
            }
            fetchLogs();
        })
        .catch(err => {
            btn.disabled = false;
            btn.style.opacity = '1';
            detailsDiv.style.display = 'none';
            statusDiv.className = 'status error';
            statusDiv.innerText = '⚠️ Network error: ' + err.message;
        });
    };

    // Fetch logs — clean display
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
                const platform = log.platform || 'Unknown';
                const network = log.network || '';
                entry.innerHTML = `
                    <div style="width:100%;">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                            <span>
                                <strong>${escapeHtml(log.campaign_name)}</strong>
                                <span class="log-platform">✅ ${escapeHtml(platform)}</span>
                                <span class="log-network">${escapeHtml(network)}</span>
                            </span>
                            <span style="font-size:0.7rem;color:#64748b;">${escapeHtml(log.sent_at)}</span>
                        </div>
                        <div style="font-size:0.8rem;color:#64748b;margin-top:2px;">
                            ${escapeHtml((log.ad_content || '').substring(0, 60))}${(log.ad_content || '').length > 60 ? '…' : ''}
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
        setInterval(fetchLogs, 10000);
    });
})();
