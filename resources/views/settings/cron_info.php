<?php /** @var string $cronUrl */ /** @var string $cronKey */ ?>
<div class="page-header">
    <h1 class="page-title">⏰ Cron Setup</h1>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary">&larr; Back to Settings</a>
</div>

<div class="card" style="margin-bottom:14px">
  <div class="card-body">
    <h3 style="margin:0 0 10px;font-size:1rem">Daily seed-count recompute</h3>
    <p style="font-size:.88rem;color:var(--color-text-muted);margin-bottom:14px">
      Recomputes the projected harvest count for every seed based on what is currently in the ground (or planted with a date that has already passed). Add this URL to your cPanel cron jobs and run it once a day (recommended: 03:00).
    </p>

    <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Cron URL</label>
    <div style="display:flex;gap:6px;margin-bottom:14px">
      <input type="text" class="form-input" value="<?= e($cronUrl) ?>" readonly id="cronUrlInput" style="font-family:monospace;font-size:.82rem">
      <button type="button" class="btn btn-secondary btn-sm" onclick="copyCronUrl()">Copy</button>
    </div>

    <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">cPanel cron command</label>
    <pre style="background:#0f172a;color:#e2e8f0;padding:10px 12px;border-radius:8px;font-size:.78rem;overflow-x:auto;margin-bottom:14px">curl -s "<?= e($cronUrl) ?>" &gt; /dev/null</pre>

    <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Suggested schedule</label>
    <pre style="background:#0f172a;color:#e2e8f0;padding:10px 12px;border-radius:8px;font-size:.78rem;overflow-x:auto;margin-bottom:14px">0 3 * * *</pre>

    <div style="padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e">
      🔑 The key in the URL acts as a password. Treat it as secret. To rotate it, delete <code style="background:rgba(0,0,0,.06);padding:1px 4px;border-radius:3px">storage/cron.key</code> on the server and reload this page.
    </div>

    <div style="margin-top:14px;display:flex;gap:8px">
      <a href="<?= e($cronUrl) ?>" target="_blank" class="btn btn-primary btn-sm">Run now (test)</a>
    </div>
  </div>
</div>

<script>
function copyCronUrl() {
  var i = document.getElementById('cronUrlInput');
  i.select(); i.setSelectionRange(0, 99999);
  try { document.execCommand('copy'); } catch (e) {}
  if (navigator.clipboard) navigator.clipboard.writeText(i.value);
}
</script>
