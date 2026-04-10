<?php
$csrf = e(\App\Support\CSRF::getToken());
$enabled   = ($ws['weather.enabled']      ?? '') === '1';
$lat       = $ws['weather.lat']           ?? '36.82';
$lng       = $ws['weather.lng']           ?? '14.95';
$stationUrl= $ws['weather.station_url']   ?? '';
$forecastUrl=$ws['weather.forecast_url']  ?? 'https://www.ilmeteo.it/meteo/rosolini';
?>

<div class="settings-wrap">

    <!-- Tab nav -->
    <nav class="settings-tab-nav" role="tablist">
        <a href="<?= url('/settings') ?>"               class="settings-tab" role="tab">General</a>
        <a href="<?= url('/settings/harvest') ?>"       class="settings-tab" role="tab">🌾 Harvest</a>
        <a href="<?= url('/settings/storage') ?>"       class="settings-tab" role="tab">Storage</a>
        <a href="<?= url('/settings/action-types') ?>"  class="settings-tab" role="tab">Action Types</a>
        <a href="<?= url('/settings/weather') ?>"       class="settings-tab settings-tab--active" role="tab">🌤️ Weather</a>
        <a href="<?= url('/settings/calendar') ?>"      class="settings-tab" role="tab">📅 Calendar</a>
        <a href="<?= url('/logs/errors') ?>"            class="settings-tab" role="tab">Error Logs</a>
        <a href="<?= url('/settings/upcoming') ?>"      class="settings-tab" role="tab">🗺 Roadmap</a>
        <a href="<?= url('/settings/upgrade') ?>"       class="settings-tab" role="tab">⬆️ Upgrade</a>
    </nav>

    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

    <div class="settings-panel">
        <form method="POST" action="<?= url('/settings/weather') ?>" class="settings-form">
            <input type="hidden" name="_token" value="<?= $csrf ?>">

            <!-- Enable / disable -->
            <div class="settings-group">
                <div class="settings-group-title">Weather Widget</div>

                <div class="settings-field settings-field--toggle">
                    <label class="settings-label" for="weather_enabled">Show weather on Dashboard</label>
                    <label class="toggle-switch">
                        <input type="hidden" name="weather_enabled" value="0">
                        <input type="checkbox" id="weather_enabled" name="weather_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Open-Meteo coordinates -->
            <div class="settings-group">
                <div class="settings-group-title">Location (Open-Meteo)</div>
                <p class="settings-hint">Used when no personal weather station is configured. Open-Meteo is free and requires no API key.</p>

                <div class="settings-field">
                    <label class="settings-label" for="weather_lat">Latitude</label>
                    <input type="text" id="weather_lat" name="weather_lat" class="settings-input"
                           value="<?= e($lat) ?>" placeholder="e.g. 36.82">
                </div>

                <div class="settings-field">
                    <label class="settings-label" for="weather_lng">Longitude</label>
                    <input type="text" id="weather_lng" name="weather_lng" class="settings-input"
                           value="<?= e($lng) ?>" placeholder="e.g. 14.95">
                </div>
            </div>

            <!-- Personal weather station -->
            <div class="settings-group">
                <div class="settings-group-title">Personal Weather Station (optional)</div>
                <p class="settings-hint">If you have a local weather station that exposes a JSON feed (e.g. EcoWitt, Davis, custom), paste its URL here. Leave blank to use Open-Meteo.</p>

                <div class="settings-field">
                    <label class="settings-label" for="weather_station_url">Station JSON URL</label>
                    <input type="url" id="weather_station_url" name="weather_station_url" class="settings-input"
                           value="<?= e($stationUrl) ?>"
                           placeholder="http://192.168.1.x/api/weather or https://…">
                </div>
            </div>

            <!-- Forecast link -->
            <div class="settings-group">
                <div class="settings-group-title">External Forecast Link</div>
                <p class="settings-hint">Shown as "Full Forecast →" in the weather widget. Defaults to ilMeteo Rosolini.</p>

                <div class="settings-field">
                    <label class="settings-label" for="weather_forecast_url">Forecast URL</label>
                    <input type="url" id="weather_forecast_url" name="weather_forecast_url" class="settings-input"
                           value="<?= e($forecastUrl) ?>"
                           placeholder="https://www.ilmeteo.it/meteo/your-city">
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn btn-primary">Save Weather Settings</button>
            </div>
        </form>
    </div>

</div>
