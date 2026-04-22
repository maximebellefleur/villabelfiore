<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\BiodynamicCalendar;

class DashboardController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $db = DB::getInstance();

        $itemCounts = $db->fetchAll(
            'SELECT type, COUNT(*) AS cnt FROM items WHERE status = ? AND deleted_at IS NULL GROUP BY type',
            ['active']
        );

        $recentActivity = $db->fetchAll(
            'SELECT * FROM activity_log ORDER BY performed_at DESC LIMIT 10'
        );

        $upcomingReminders = $db->fetchAll(
            "SELECT r.*, i.name AS item_name FROM reminders r LEFT JOIN items i ON i.id=r.item_id WHERE r.status='pending' AND r.due_at >= NOW() ORDER BY r.due_at ASC LIMIT 10"
        );

        $overdueReminders = $db->fetchAll(
            "SELECT r.*, i.name AS item_name FROM reminders r LEFT JOIN items i ON i.id=r.item_id WHERE r.status='pending' AND r.due_at < NOW() ORDER BY r.due_at ASC LIMIT 10"
        );

        // Harvest totals grouped by item type (current year)
        $harvestByType = $db->fetchAll(
            "SELECT i.type, h.unit, SUM(h.quantity) AS total
             FROM harvest_entries h
             JOIN items i ON i.id = h.item_id
             WHERE YEAR(h.recorded_at) = YEAR(NOW())
             GROUP BY i.type, h.unit
             ORDER BY i.type, h.unit"
        );
        // Reindex: ['olive_tree' => ['kg' => 120.5, ...], ...]
        $harvestByTypeMap = [];
        foreach ($harvestByType as $row) {
            $harvestByTypeMap[$row['type']][$row['unit']] = (float)$row['total'];
        }

        $monthlyHarvest = $db->fetchAll(
            "SELECT MONTH(h.recorded_at) AS mo, SUM(h.quantity) AS total, h.unit
             FROM harvest_entries h
             WHERE YEAR(h.recorded_at) = YEAR(NOW())
             GROUP BY MONTH(h.recorded_at), h.unit
             ORDER BY mo",
            []
        );

        $gpsItems = $db->fetchAll(
            "SELECT i.id, i.name, i.type, i.gps_lat, i.gps_lng,
                    (SELECT a.id FROM attachments a
                     WHERE a.item_id = i.id AND a.category = 'identification_photo'
                     AND (a.status = 'active' OR a.status IS NULL) AND a.mime_type LIKE 'image/%'
                     ORDER BY a.id DESC LIMIT 1) AS photo_id
             FROM items i
             WHERE i.gps_lat IS NOT NULL AND i.gps_lng IS NOT NULL
             AND i.status = 'active' AND i.deleted_at IS NULL
             ORDER BY i.name"
        );

        // Biodynamic data for today + next 7 days
        $tzStr = $this->getSetting($db, 'app.timezone', 'Europe/Rome') ?: 'Europe/Rome';
        $tz    = new \DateTimeZone($tzStr);
        $bioNow  = BiodynamicCalendar::computePoint(new \DateTime('now', $tz));
        $bioWeek = [];
        for ($i = 0; $i < 7; $i++) {
            $dt = new \DateTime('now', $tz);
            $dt->modify("+{$i} days")->setTime(12, 0);
            $bioWeek[$i] = BiodynamicCalendar::computePoint($dt);
        }

        // Next 6 hours biodynamic — for the "what to do next" hourly timeline
        $bioNext6 = [];
        for ($i = 1; $i <= 6; $i++) {
            $dt = new \DateTime('now', $tz);
            $dt->modify("+{$i} hours");
            $pt = BiodynamicCalendar::computePoint($dt);
            $pt['_hour'] = (int)$dt->format('G');
            $pt['_label'] = $dt->format('H:00');
            $bioNext6[] = $pt;
        }
        // Collapse consecutive identical organ segments
        $bioSegments = [];
        foreach ($bioNext6 as $pt) {
            $key = $pt['organ'] . ($pt['is_anomaly'] ? '_anom' : '') . ($pt['is_descending'] ? '_d' : '_a');
            if (empty($bioSegments) || end($bioSegments)['_key'] !== $key) {
                $seg = $pt;
                $seg['_key']      = $key;
                $seg['_from']     = $pt['_label'];
                $seg['_to']       = $pt['_label'];
                $seg['_count']    = 1;
                $bioSegments[]    = $seg;
            } else {
                $bioSegments[count($bioSegments)-1]['_to']    = $pt['_label'];
                $bioSegments[count($bioSegments)-1]['_count']++;
            }
        }

        // Today's irrigation plans (active, not already done today)
        $todayIrrigation = [];
        try {
            $todayIrrigation = $db->fetchAll(
                "SELECT ip.*, i.name AS item_name, i.type AS item_type
                 FROM irrigation_plans ip
                 JOIN items i ON i.id = ip.item_id
                 WHERE i.deleted_at IS NULL AND i.status != 'trashed'
                   AND ip.start_date <= CURDATE()
                   AND (ip.end_date IS NULL OR ip.end_date >= CURDATE())
                   AND (ip.last_done_date IS NULL OR ip.last_done_date < CURDATE())
                 ORDER BY i.name"
            );
        } catch (\Throwable $e) { /* table may not exist yet */ }

        // Tasks widget
        $dashTasks  = [];
        $dashAchats = [];
        try {
            $dashTasks = $db->fetchAll(
                "SELECT * FROM tasks WHERE is_archived = 0 AND list_type = 'todo' AND is_done = 0
                 ORDER BY is_important DESC, sort_order ASC, created_at DESC LIMIT 5"
            );
            $dashAchats = $db->fetchAll(
                "SELECT * FROM tasks WHERE is_archived = 0 AND list_type = 'achat' AND is_done = 0
                 ORDER BY category ASC, sort_order ASC, created_at DESC LIMIT 6"
            );
        } catch (\Throwable $e) {}

        Response::render('dashboard/index', [
            'title'             => 'Dashboard',
            'itemCounts'        => $itemCounts,
            'recentActivity'    => $recentActivity,
            'upcomingReminders' => $upcomingReminders,
            'overdueReminders'  => $overdueReminders,
            'harvestByTypeMap'  => $harvestByTypeMap,
            'monthlyHarvest'    => $monthlyHarvest,
            'gpsItems'          => $gpsItems,
            'weather'           => $this->fetchWeather($db),
            'weatherCity'       => $this->getSetting($db, 'weather.city_name', ''),
            'forecastUrl'       => $this->getSetting($db, 'weather.forecast_url', 'https://www.ilmeteo.it/meteo/rosolini'),
            'ownerName'         => $this->getSetting($db, 'app.owner_name', ''),
            'quote'             => $this->fetchQuote($db),
            'bioNow'            => $bioNow,
            'bioWeek'           => $bioWeek,
            'bioSegments'       => $bioSegments,
            'todayIrrigation'   => $todayIrrigation,
            'dashTasks'         => $dashTasks,
            'dashAchats'        => $dashAchats,
        ]);
    }

    // ── Quote of the day ─────────────────────────────────────────────────────

    private function fetchQuote(\App\Support\DB $db): ?array {
        // 24-hour cache
        $cache = $db->fetchOne("SELECT setting_value_json, updated_at FROM settings WHERE setting_key = 'quote.cache'");
        if ($cache && !empty($cache['setting_value_json'])) {
            $age = time() - (int)strtotime($cache['updated_at'] . ' UTC');
            if ($age < 86400) {
                $d = json_decode($cache['setting_value_json'], true);
                if ($d) return $d;
            }
        }

        $apiUrl = $this->getSetting($db, 'quote.api_url', 'https://zenquotes.io/api/today');
        if (!$apiUrl) return null;

        $json = $this->httpGet($apiUrl, 4);
        if (!$json) return null;
        $data = json_decode($json, true);
        if (empty($data[0]['q'])) return null;

        $result = ['text' => $data[0]['q'], 'author' => $data[0]['a'] ?? ''];
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value_json, value_type, autoload, updated_at)
             VALUES ('quote.cache',?,'json',0,NOW())
             ON DUPLICATE KEY UPDATE setting_value_json=VALUES(setting_value_json),updated_at=NOW()",
            [json_encode($result)]
        );
        return $result;
    }

    // ── Weather helpers ──────────────────────────────────────────────────────

    private function getSetting(\App\Support\DB $db, string $key, string $default = ''): string {
        $row = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = ?", [$key]);
        return $row['setting_value_text'] ?? $default;
    }

    private function fetchWeather(\App\Support\DB $db): ?array {
        if ($this->getSetting($db, 'weather.enabled') !== '1') return null;

        // 30-min cache
        $cache = $db->fetchOne("SELECT setting_value_json, updated_at FROM settings WHERE setting_key = 'weather.cache'");
        if ($cache && !empty($cache['setting_value_json'])) {
            $age = time() - (int)strtotime($cache['updated_at'] . ' UTC');
            if ($age < 1800) {
                $d = json_decode($cache['setting_value_json'], true);
                if ($d) return $d;
            }
        }

        $stationUrl = $this->getSetting($db, 'weather.station_url');
        if ($stationUrl) {
            $json = $this->httpGet($stationUrl);
            if ($json) {
                $raw = json_decode($json, true);
                if ($raw) { $parsed = $this->parseStationData($raw); $this->cacheWeather($db, $parsed); return $parsed; }
            }
            return null;
        }

        $lat = $this->getSetting($db, 'weather.lat', '36.82');
        $lng = $this->getSetting($db, 'weather.lng', '14.95');
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}"
             . "&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,surface_pressure,wind_speed_10m"
             . "&hourly=temperature_2m,weather_code"
             . "&daily=sunrise,sunset,temperature_2m_max,temperature_2m_min,weather_code&timezone=Europe%2FRome&forecast_days=4";

        $json = $this->httpGet($url);
        if (!$json) return null;
        $raw = json_decode($json, true);
        if (!$raw || empty($raw['current'])) return null;

        $parsed = $this->parseOpenMeteo($raw);
        $this->cacheWeather($db, $parsed);
        return $parsed;
    }

    private function parseOpenMeteo(array $raw): array {
        $cur  = $raw['current'];
        $code = (int)($cur['weather_code'] ?? 0);
        [$icon, $desc] = $this->weatherCode($code);

        $nowTs  = time();
        $times  = $raw['hourly']['time'] ?? [];
        $temps  = $raw['hourly']['temperature_2m'] ?? [];
        $codes  = $raw['hourly']['weather_code'] ?? [];
        $hours  = [];
        foreach ($times as $i => $t) {
            $ts = strtotime($t);
            if ($ts !== false && $ts > $nowTs && count($hours) < 4) {
                [$hi] = $this->weatherCode((int)($codes[$i] ?? 0));
                $hours[] = ['time' => substr($t, 11, 5), 'temp' => round($temps[$i] ?? 0), 'icon' => $hi];
            }
        }
        // Build next 2 daily forecasts (tomorrow, day-after)
        $daily = [];
        $dailyTimes  = $raw['daily']['time'] ?? [];
        $dailyMax    = $raw['daily']['temperature_2m_max'] ?? [];
        $dailyMin    = $raw['daily']['temperature_2m_min'] ?? [];
        $dailyCodes  = $raw['daily']['weather_code'] ?? [];
        foreach ([1, 2, 3] as $di) {
            if (empty($dailyTimes[$di])) continue;
            [$di_icon] = $this->weatherCode((int)($dailyCodes[$di] ?? 0));
            $ts = strtotime($dailyTimes[$di]);
            $daily[] = [
                'label' => $di === 1 ? 'Tomorrow' : date('D', $ts),
                'icon'  => $di_icon,
                'max'   => round((float)($dailyMax[$di] ?? 0)),
                'min'   => round((float)($dailyMin[$di] ?? 0)),
            ];
        }

        return [
            'temp'    => round((float)($cur['temperature_2m'] ?? 0)),
            'feels'   => round((float)($cur['apparent_temperature'] ?? 0)),
            'humidity'=> (int)($cur['relative_humidity_2m'] ?? 0),
            'pressure'=> round((float)($cur['surface_pressure'] ?? 0)),
            'wind'    => round((float)($cur['wind_speed_10m'] ?? 0)),
            'icon'    => $icon, 'desc' => $desc,
            'sunset'  => substr($raw['daily']['sunset'][0] ?? '', 11, 5),
            'hours'   => $hours, 'daily' => $daily, 'source' => 'open-meteo',
        ];
    }

    private function parseStationData(array $raw): array {
        $temp = (float)($raw['temp'] ?? $raw['temperature'] ?? $raw['outdoor']['temperature']['value'] ?? 0);
        $code = (int)($raw['weather_code'] ?? 0);
        [$icon, $desc] = $this->weatherCode($code);
        return [
            'temp'    => round($temp), 'feels' => round($temp),
            'humidity'=> (int)($raw['humidity'] ?? $raw['outdoor']['humidity']['value'] ?? 0),
            'pressure'=> round((float)($raw['pressure'] ?? $raw['baromabs'] ?? 0)),
            'wind'    => round((float)($raw['windspeed'] ?? $raw['wind_speed'] ?? 0)),
            'icon'    => $icon, 'desc' => $desc,
            'sunset'  => '', 'hours' => [], 'source' => 'station',
        ];
    }

    private function weatherCode(int $code): array {
        return match(true) {
            $code === 0  => ['☀️', 'Clear sky'],
            $code <= 2   => ['🌤️', 'Partly cloudy'],
            $code === 3  => ['☁️', 'Overcast'],
            $code <= 48  => ['🌫️', 'Foggy'],
            $code <= 55  => ['🌦️', 'Drizzle'],
            $code <= 65  => ['🌧️', 'Rain'],
            $code <= 75  => ['🌨️', 'Snow'],
            $code <= 82  => ['🌦️', 'Showers'],
            $code <= 86  => ['❄️', 'Snow showers'],
            $code <= 99  => ['⛈️', 'Thunderstorm'],
            default      => ['🌡️', 'N/A'],
        };
    }

    private function cacheWeather(\App\Support\DB $db, array $data): void {
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value_json, value_type, autoload, updated_at)
             VALUES ('weather.cache',?,'json',0,NOW())
             ON DUPLICATE KEY UPDATE setting_value_json=VALUES(setting_value_json),updated_at=NOW()",
            [json_encode($data)]
        );
    }

    private function httpGet(string $url, int $timeout = 5): ?string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$timeout,
                CURLOPT_USERAGENT=>'Rooted/1.5',CURLOPT_SSL_VERIFYPEER=>false]);
            $r = curl_exec($ch); curl_close($ch);
            return $r ?: null;
        }
        $ctx = stream_context_create(['http'=>['timeout'=>$timeout,'user_agent'=>'Rooted/1.5']]);
        return @file_get_contents($url, false, $ctx) ?: null;
    }

    public function overview(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $itemCounts = $db->fetchAll(
            'SELECT type, COUNT(*) AS cnt FROM items WHERE status = ? AND deleted_at IS NULL GROUP BY type',
            ['active']
        );
        Response::render('dashboard/overview', ['title' => 'Overview', 'itemCounts' => $itemCounts]);
    }

    public function map(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $db = DB::getInstance();

        $gpsCount = $db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM items WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
             AND status != 'trashed' AND deleted_at IS NULL"
        );

        $center = $db->fetchOne(
            "SELECT AVG(gps_lat) AS lat, AVG(gps_lng) AS lng FROM items
             WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL
             AND status != 'trashed' AND deleted_at IS NULL"
        );

        // Load land boundary and name from settings
        $landBoundarySetting = $db->fetchOne(
            "SELECT setting_value_text FROM settings WHERE setting_key = 'land.boundary_geojson'"
        );
        $landNameSetting = $db->fetchOne(
            "SELECT setting_value_text FROM settings WHERE setting_key = 'app.name'"
        );

        $landBoundaryJson = $landBoundarySetting['setting_value_text'] ?? null;
        $hasLandBoundary  = !empty($landBoundaryJson);

        // If we have a boundary, derive the map center from it instead of item average
        $defaultLat = (float)($center['lat'] ?? 41.9);
        $defaultLng = (float)($center['lng'] ?? 12.5);
        if ($hasLandBoundary) {
            $decoded = json_decode($landBoundaryJson, true);
            $coords  = $decoded['coordinates'][0] ?? [];
            if ($coords) {
                $lats = array_column($coords, 1);
                $lngs = array_column($coords, 0);
                $defaultLat = (array_sum($lats) / count($lats));
                $defaultLng = (array_sum($lngs) / count($lngs));
            }
        }

        $itemTypes = require BASE_PATH . '/config/item_types.php';

        // Configurable boundary types (which item types can draw polygon boundaries)
        $defaultBoundaryTypes = ['garden', 'bed', 'orchard', 'zone', 'prep_zone', 'mobile_coop', 'building'];
        $boundaryTypesRow = $db->fetchOne(
            "SELECT setting_value_json FROM settings WHERE setting_key = 'map.boundary_types'"
        );
        $boundaryTypes = (!empty($boundaryTypesRow['setting_value_json']))
            ? (json_decode($boundaryTypesRow['setting_value_json'], true) ?: $defaultBoundaryTypes)
            : $defaultBoundaryTypes;

        Response::render('dashboard/map', [
            'title'            => 'Land Map',
            'mapEnabled'       => true,
            'gpsCount'         => (int)($gpsCount['cnt'] ?? 0),
            'defaultLat'       => $defaultLat,
            'defaultLng'       => $defaultLng,
            'hasLandBoundary'  => $hasLandBoundary,
            'landBoundaryJson' => $hasLandBoundary ? $landBoundaryJson : 'null',
            'landName'         => $landNameSetting['setting_value_text'] ?? 'My Land',
            'itemTypes'        => $itemTypes,
            'boundaryTypes'    => $boundaryTypes,
        ]);
    }

    public function nearby(Request $request, array $params = []): void
    {
        $this->requireAuth();
        Response::render('dashboard/nearby', ['title' => 'Nearby Items']);
    }

    public function reports(Request $request, array $params = []): void
    {
        $this->requireAuth();
        Response::render('dashboard/reports', ['title' => 'Reports — ' . date('Y')]);
    }

    public function apiSummary(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $db = DB::getInstance();

        $totalItems = $db->fetchOne('SELECT COUNT(*) AS cnt FROM items WHERE deleted_at IS NULL AND status != ?', ['trashed']);
        $overdueCount = $db->fetchOne("SELECT COUNT(*) AS cnt FROM reminders WHERE status = 'pending' AND due_at < NOW()");

        Response::json([
            'success' => true,
            'data' => [
                'total_items'    => (int) ($totalItems['cnt'] ?? 0),
                'overdue_reminders' => (int) ($overdueCount['cnt'] ?? 0),
            ],
        ]);
    }
}
