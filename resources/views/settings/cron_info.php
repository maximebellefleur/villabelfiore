<?php /** @var string $cronUrl */ /** @var string $cronKey */ /** @var string $cliPath */ ?>
<div class="page-header">
    <h1 class="page-title">⏰ Cron Setup</h1>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary">&larr; Back to Settings</a>
</div>

<div class="card" style="margin-bottom:14px">
  <div class="card-body">
    <h3 style="margin:0 0 10px;font-size:1rem">Daily seed-count recompute</h3>
    <p style="font-size:.88rem;color:var(--color-text-muted);margin-bottom:18px">
      Recomputes the projected harvest count for every seed based on what is currently in the ground. Run once a day (recommended: 03:00). Choose either method below.
    </p>

    <!-- ── Option 1: PHP CLI (cPanel preferred) ── -->
    <div style="background:var(--color-primary-soft);border:1.5px solid var(--color-primary);border-radius:10px;padding:12px 14px;margin-bottom:16px">
      <div style="font-size:.8rem;font-weight:800;color:var(--color-primary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Option 1 — PHP CLI &nbsp;<span style="font-weight:400;opacity:.75">(recommended for cPanel)</span></div>

      <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">cPanel → Cron Jobs → Command</label>
      <div style="display:flex;gap:6px;margin-bottom:8px">
        <input type="text" class="form-input" value="/usr/local/bin/php <?= e($cliPath) ?>" readonly id="cronCliInput" style="font-family:monospace;font-size:.78rem">
        <button type="button" class="btn btn-secondary btn-sm" onclick="copyEl('cronCliInput')">Copy</button>
      </div>

      <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Suggested schedule</label>
      <pre style="background:#0f172a;color:#e2e8f0;padding:8px 12px;border-radius:8px;font-size:.78rem;margin:0">0 3 * * *</pre>
    </div>

    <!-- ── Option 2: curl / HTTP ── -->
    <div style="background:var(--color-surface);border:1.5px solid var(--color-border);border-radius:10px;padding:12px 14px;margin-bottom:16px">
      <div style="font-size:.8rem;font-weight:800;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Option 2 — HTTP / curl</div>

      <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Cron URL</label>
      <div style="display:flex;gap:6px;margin-bottom:8px">
        <input type="text" class="form-input" value="<?= e($cronUrl) ?>" readonly id="cronUrlInput" style="font-family:monospace;font-size:.78rem">
        <button type="button" class="btn btn-secondary btn-sm" onclick="copyEl('cronUrlInput')">Copy</button>
      </div>

      <label style="display:block;font-size:.78rem;font-weight:700;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">cPanel cron command</label>
      <pre style="background:#0f172a;color:#e2e8f0;padding:8px 12px;border-radius:8px;font-size:.78rem;margin:0">curl -s "<?= e($cronUrl) ?>" &gt; /dev/null</pre>
    </div>

    <div style="padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e;margin-bottom:14px">
      🔑 The key in the HTTP URL acts as a password. Treat it as secret. To rotate it, delete <code style="background:rgba(0,0,0,.06);padding:1px 4px;border-radius:3px">storage/cron.key</code> on the server and reload this page.
    </div>

    <div style="display:flex;gap:8px">
      <a href="<?= e($cronUrl) ?>" target="_blank" class="btn btn-primary btn-sm">Run via URL (test)</a>
    </div>
  </div>
</div>

<script>
function copyEl(id) {
  var i = document.getElementById(id);
  i.select(); i.setSelectionRange(0, 99999);
  try { document.execCommand('copy'); } catch (e) {}
  if (navigator.clipboard) navigator.clipboard.writeText(i.value);
}
</script>
