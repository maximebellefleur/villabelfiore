<div class="page-header">
    <h1 class="page-title">Settings — Google Calendar</h1>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary">&larr; Back to Settings</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Connection status banner -->
<?php if ($isConnected): ?>
<div class="calendar-status calendar-status--connected">
    <div class="calendar-status-icon">📅</div>
    <div class="calendar-status-body">
        <strong>Connected</strong>
        <?php if (!empty($settings['google_calendar.connected_email'])): ?>
        as <strong><?= e($settings['google_calendar.connected_email']) ?></strong>
        <?php endif; ?>
    </div>
    <div class="calendar-status-actions">
        <form method="POST" action="<?= url('/settings/calendar/sync') ?>" style="display:inline">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button type="submit" class="btn btn-primary btn-sm">🔄 Sync Now</button>
        </form>
        <form method="POST" action="<?= url('/settings/calendar/disconnect') ?>" style="display:inline"
              onsubmit="return confirm('Disconnect Google Calendar? Existing calendar events will remain.')">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button type="submit" class="btn btn-link btn-sm text-danger">Disconnect</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="calendar-status calendar-status--disconnected">
    <div class="calendar-status-icon">📅</div>
    <div class="calendar-status-body">
        <strong>Not connected</strong>
        <span class="text-muted">Save your credentials below, then click Connect.</span>
    </div>
</div>
<?php endif; ?>

<!-- Step 1: Credentials -->
<div class="card" style="margin-top:var(--spacing-4)">
    <div class="card-body">
        <h2 class="card-title">Step 1 — Google Cloud credentials</h2>
        <p class="text-muted text-sm" style="margin-bottom:var(--spacing-4)">
            Create an OAuth 2.0 Client ID in the
            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a>.
            Set the application type to <strong>Web application</strong> and add this as an authorised redirect URI:
        </p>
        <div class="calendar-redirect-uri">
            <code id="redirectUri"><?= e(
                ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') .
                '://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') .
                APP_BASE . '/settings/calendar/callback'
            ) ?></code>
            <button type="button" class="btn btn-secondary btn-sm" id="copyUri">Copy</button>
        </div>

        <form method="POST" action="<?= url('/settings/calendar/save') ?>" class="form" style="margin-top:var(--spacing-4)">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="client_id" class="form-input" autocomplete="off"
                           placeholder="xxxxx.apps.googleusercontent.com"
                           value="<?= e($settings['google_calendar.client_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Client Secret</label>
                    <input type="password" name="client_secret" class="form-input" autocomplete="off"
                           placeholder="<?= !empty($settings['google_calendar.client_secret']) ? '●●●●●●●● (saved)' : 'GOCSPX-…' ?>"
                           value="">
                    <p class="form-hint">Leave blank to keep the saved secret.</p>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Calendar ID <span class="text-muted">(default: primary)</span></label>
                <input type="text" name="calendar_id" class="form-input"
                       placeholder="primary  or  yourname@gmail.com  or a specific calendar ID"
                       value="<?= e($settings['google_calendar.calendar_id'] ?? 'primary') ?>">
                <p class="form-hint">Use <code>primary</code> for your main calendar, or paste a specific calendar ID from Google Calendar settings.</p>
            </div>
            <button type="submit" class="btn btn-primary">Save Credentials</button>
        </form>
    </div>
</div>

<!-- Step 2: Connect -->
<?php if (!empty($settings['google_calendar.client_id'])): ?>
<div class="card" style="margin-top:var(--spacing-4)">
    <div class="card-body">
        <h2 class="card-title">Step 2 — Connect your Google account</h2>
        <?php if ($isConnected): ?>
        <p class="text-muted text-sm">Your account is connected. You can re-connect to refresh permissions.</p>
        <?php else: ?>
        <p class="text-muted text-sm">Click the button below to open Google's authorisation screen. You'll be asked to allow Rooted to create and update calendar events.</p>
        <?php endif; ?>
        <a href="<?= url('/settings/calendar/connect') ?>" class="btn btn-primary" style="margin-top:var(--spacing-3)">
            <?= $isConnected ? '🔄 Re-connect Google Account' : '🔗 Connect Google Account' ?>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- How it works -->
<div class="card" style="margin-top:var(--spacing-4)">
    <div class="card-body">
        <h2 class="card-title">How it works</h2>
        <ul class="text-sm" style="padding-left:var(--spacing-5); color:var(--color-text-muted); line-height:2">
            <li>Every <strong>pending reminder</strong> with a future due date is synced as a calendar event.</li>
            <li>The event title is the reminder title + item name (e.g. <em>"Pruning — Olive #12"</em>).</li>
            <li>Events are created with a 1-hour duration and a 60-minute popup reminder.</li>
            <li>Re-syncing updates existing events — it does not create duplicates.</li>
            <li>Completed or dismissed reminders are <strong>not</strong> removed from Google Calendar automatically — delete them manually if needed.</li>
            <li>Sync runs on demand. Automatic background sync is planned for v1.3.0.</li>
        </ul>
    </div>
</div>

<script>
document.getElementById('copyUri').addEventListener('click', function() {
    var uri = document.getElementById('redirectUri').textContent;
    navigator.clipboard.writeText(uri).then(function() {
        var btn = document.getElementById('copyUri');
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
    });
});
</script>
