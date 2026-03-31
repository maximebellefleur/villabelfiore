<?php $layout = 'installer'; ?>
<div class="installer-card">
    <h2>Step 4: Integrations (Optional)</h2>
    <p>These can all be configured later in Settings.</p>
    <form method="POST" action="/install/step/5" class="form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="enable_google_calendar" value="1">
                Enable Google Calendar sync
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="enable_weather" value="1">
                Enable weather integration
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-secondary">Skip</button>
            <button type="submit" class="btn btn-primary">Continue &rarr;</button>
        </div>
    </form>
</div>
