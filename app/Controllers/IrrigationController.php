<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class IrrigationController
{
    private const CALENDAR_API   = 'https://www.googleapis.com/calendar/v3';
    private const TOKEN_URL      = 'https://oauth2.googleapis.com/token';

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) Response::redirect('/login');
    }

    private function ensureTable(DB $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS irrigation_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            interval_type VARCHAR(30) NOT NULL DEFAULT 'daily',
            start_date DATE NOT NULL,
            end_date DATE,
            quantity_liters DECIMAL(8,2),
            hour_preset VARCHAR(20),
            custom_hour TIME,
            last_done_date DATE,
            notes TEXT,
            google_event_id VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        // Migrate existing tables: add new columns if missing
        foreach ([
            "ALTER TABLE irrigation_plans ADD COLUMN end_date DATE AFTER start_date",
            "ALTER TABLE irrigation_plans ADD COLUMN quantity_liters DECIMAL(8,2) AFTER end_date",
            "ALTER TABLE irrigation_plans ADD COLUMN hour_preset VARCHAR(20) AFTER quantity_liters",
            "ALTER TABLE irrigation_plans ADD COLUMN custom_hour TIME AFTER hour_preset",
            "ALTER TABLE irrigation_plans ADD COLUMN last_done_date DATE AFTER custom_hour",
        ] as $alter) {
            try { $db->execute($alter); } catch (\Throwable $e) { /* column already exists */ }
        }
    }

    // -------------------------------------------------------------------------
    // GET /irrigation
    // -------------------------------------------------------------------------
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db = DB::getInstance();
        $this->ensureTable($db);

        $plans = $db->fetchAll(
            'SELECT ip.*, i.name AS item_name, i.type AS item_type
             FROM irrigation_plans ip
             JOIN items i ON i.id = ip.item_id
             WHERE i.deleted_at IS NULL AND i.status != ?
             ORDER BY i.name',
            ['trashed']
        );

        $withoutPlan = $db->fetchAll(
            "SELECT i.id, i.name, i.type FROM items i
             LEFT JOIN irrigation_plans ip ON ip.item_id = i.id
             WHERE ip.id IS NULL
               AND i.type IN ('tree','olive_tree','almond_tree','vine','garden','bed')
               AND i.deleted_at IS NULL AND i.status != 'trashed'
             ORDER BY i.name"
        );

        Response::render('irrigation/index', [
            'title'       => 'Irrigation Plans',
            'plans'       => $plans,
            'withoutPlan' => $withoutPlan,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /items/{id}/irrigation
    // -------------------------------------------------------------------------
    public function store(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $itemId = (int)($params['id'] ?? 0);
        $db = DB::getInstance();
        $this->ensureTable($db);

        $item = $db->fetchOne('SELECT * FROM items WHERE id = ? AND deleted_at IS NULL', [$itemId]);
        if (!$item) { Response::redirect('/items'); return; }

        $plan = $this->planFromRequest($request);
        $db->execute(
            'INSERT INTO irrigation_plans (item_id, interval_type, start_date, end_date, quantity_liters, hour_preset, custom_hour, notes, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())',
            [$itemId, $plan['interval_type'], $plan['start_date'], $plan['end_date'],
             $plan['quantity_liters'], $plan['hour_preset'], $plan['custom_hour'], $plan['notes']]
        );
        $planId = (int)$db->lastInsertId();

        $eventId = $this->createCalendarEvent($db, $plan, $item);
        if ($eventId) {
            $db->execute('UPDATE irrigation_plans SET google_event_id = ? WHERE id = ?', [$eventId, $planId]);
        }

        flash('success', 'Irrigation plan saved' . ($eventId ? ' and added to Google Calendar.' : '.'));
        Response::redirect('/items/' . $itemId);
    }

    // -------------------------------------------------------------------------
    // POST /irrigation/{id}/update
    // -------------------------------------------------------------------------
    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $planId = (int)($params['id'] ?? 0);
        $db = DB::getInstance();
        $this->ensureTable($db);

        $row = $db->fetchOne('SELECT * FROM irrigation_plans WHERE id = ?', [$planId]);
        if (!$row) { Response::redirect('/items'); return; }

        $itemId = (int)$row['item_id'];
        $item   = $db->fetchOne('SELECT * FROM items WHERE id = ?', [$itemId]);

        if (!empty($row['google_event_id'])) {
            $this->deleteCalendarEvent($db, $row['google_event_id']);
        }

        $plan = $this->planFromRequest($request);
        $db->execute(
            'UPDATE irrigation_plans SET interval_type=?, start_date=?, end_date=?, quantity_liters=?, hour_preset=?, custom_hour=?, notes=?, google_event_id=NULL, updated_at=NOW() WHERE id=?',
            [$plan['interval_type'], $plan['start_date'], $plan['end_date'], $plan['quantity_liters'],
             $plan['hour_preset'], $plan['custom_hour'], $plan['notes'], $planId]
        );

        $eventId = $this->createCalendarEvent($db, $plan, $item);
        if ($eventId) {
            $db->execute('UPDATE irrigation_plans SET google_event_id = ? WHERE id = ?', [$eventId, $planId]);
        }

        flash('success', 'Irrigation plan updated' . ($eventId ? ' and synced to Google Calendar.' : '.'));
        Response::redirect('/items/' . $itemId);
    }

    // -------------------------------------------------------------------------
    // POST /irrigation/{id}/delete
    // -------------------------------------------------------------------------
    public function destroy(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $planId = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $row = $db->fetchOne('SELECT * FROM irrigation_plans WHERE id = ?', [$planId]);
        if (!$row) { Response::redirect('/items'); return; }

        $itemId = (int)$row['item_id'];

        if (!empty($row['google_event_id'])) {
            $this->deleteCalendarEvent($db, $row['google_event_id']);
        }

        $db->execute('DELETE FROM irrigation_plans WHERE id = ?', [$planId]);

        flash('success', 'Irrigation plan removed.');
        Response::redirect('/items/' . $itemId);
    }

    // -------------------------------------------------------------------------
    // POST /irrigation/{id}/done  (AJAX)
    // -------------------------------------------------------------------------
    public function markDone(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        $planId = (int)($params['id'] ?? 0);
        $db = DB::getInstance();

        $row = $db->fetchOne(
            'SELECT ip.*, i.name AS item_name FROM irrigation_plans ip
             JOIN items i ON i.id = ip.item_id WHERE ip.id = ?',
            [$planId]
        );
        if (!$row) { Response::json(['success' => false]); return; }

        $today = date('Y-m-d');
        $db->execute('UPDATE irrigation_plans SET last_done_date = ?, updated_at = NOW() WHERE id = ?', [$today, $planId]);

        try {
            $settings = $this->loadSettings($db);
            if (!empty($settings['google_calendar.refresh_token'])) {
                $token = $this->validToken($settings, $db);
                $calId = $settings['google_calendar.calendar_id'] ?: 'primary';
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $itemUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_BASE . '/items/' . $row['item_id'];
                $title = '✅ Done — ' . $row['item_name'];
                if (!empty($row['quantity_liters'])) {
                    $title .= ' (' . (float)$row['quantity_liters'] . 'L)';
                }
                $this->httpPost(
                    self::CALENDAR_API . '/calendars/' . urlencode($calId) . '/events',
                    [
                        'summary'     => $title,
                        'description' => 'Irrigation completed' . ($row['notes'] ? "\n\n" . $row['notes'] : '') . "\n\n" . $itemUrl,
                        'start'       => ['date' => $today],
                        'end'         => ['date' => $today],
                    ],
                    $token, true
                );
            }
        } catch (\Throwable $e) {
            \App\Support\Logger::warning('Irrigation done calendar failed: ' . $e->getMessage());
        }

        Response::json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function planFromRequest(Request $request): array
    {
        $startDate = trim($request->post('start_date', date('Y-m-d'))) ?: date('Y-m-d');
        $endDate   = trim($request->post('end_date', ''));
        if (!$endDate) {
            $dt = new \DateTime($startDate);
            $dt->modify('+12 months');
            $endDate = $dt->format('Y-m-d');
        }
        $qty = $request->post('quantity_liters', '');
        $preset = trim($request->post('hour_preset', ''));
        return [
            'interval_type'   => trim($request->post('interval_type', 'daily')),
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'quantity_liters' => ($qty !== '' && $qty !== '0') ? (float)$qty : null,
            'hour_preset'     => $preset ?: null,
            'custom_hour'     => ($preset === 'custom') ? (trim($request->post('custom_hour', '')) ?: null) : null,
            'notes'           => trim($request->post('notes', '')),
        ];
    }

    public static function intervalLabel(string $interval): string
    {
        return [
            'twice_daily'   => 'Twice daily',
            'daily'         => 'Daily',
            'every_2_days'  => 'Every 2 days',
            'every_3_days'  => 'Every 3 days',
            'every_5_days'  => 'Every 5 days',
            'every_10_days' => 'Every 10 days',
            'every_20_days' => 'Every 20 days',
            'weekly'        => 'Weekly',
            'biweekly'      => 'Every 2 weeks',
            'monthly'       => 'Monthly',
        ][$interval] ?? $interval;
    }

    private function rrule(string $interval, string $startDate, ?string $endDate): string
    {
        if ($endDate) {
            $end = new \DateTime($endDate);
        } else {
            $end = new \DateTime($startDate);
            $end->modify('+12 months');
        }
        $until = $end->format('Ymd') . 'T235959Z';

        $map = [
            'twice_daily'   => 'FREQ=DAILY;INTERVAL=1',
            'daily'         => 'FREQ=DAILY;INTERVAL=1',
            'every_2_days'  => 'FREQ=DAILY;INTERVAL=2',
            'every_3_days'  => 'FREQ=DAILY;INTERVAL=3',
            'every_5_days'  => 'FREQ=DAILY;INTERVAL=5',
            'every_10_days' => 'FREQ=DAILY;INTERVAL=10',
            'every_20_days' => 'FREQ=DAILY;INTERVAL=20',
            'weekly'        => 'FREQ=WEEKLY;INTERVAL=1',
            'biweekly'      => 'FREQ=WEEKLY;INTERVAL=2',
            'monthly'       => 'FREQ=MONTHLY;INTERVAL=1',
        ];

        return 'RRULE:' . ($map[$interval] ?? 'FREQ=DAILY;INTERVAL=1') . ';UNTIL=' . $until;
    }

    private function createCalendarEvent(DB $db, array $plan, array $item): ?string
    {
        try {
            $settings = $this->loadSettings($db);
            if (empty($settings['google_calendar.refresh_token'])) return null;

            $token = $this->validToken($settings, $db);
            $calId = $settings['google_calendar.calendar_id'] ?: 'primary';

            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $itemUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_BASE . '/items/' . $item['id'];

            $label = self::intervalLabel($plan['interval_type']);

            $title = '💧 Water — ' . $item['name'];
            if (!empty($plan['quantity_liters'])) {
                $title .= ' (' . (float)$plan['quantity_liters'] . 'L)';
            }

            $preset  = $plan['hour_preset'] ?? '';
            $tz      = date_default_timezone_get();
            $rrule   = $this->rrule($plan['interval_type'], $plan['start_date'], $plan['end_date'] ?? null);
            $descStr = $label . ($plan['notes'] ? "\n\n" . $plan['notes'] : '') . "\n\n" . $itemUrl;

            if (!$preset) {
                $event = [
                    'summary'     => $title,
                    'description' => $descStr,
                    'start'       => ['date' => $plan['start_date']],
                    'end'         => ['date' => $plan['start_date']],
                    'recurrence'  => [$rrule],
                ];
            } else {
                $timeStr = match($preset) {
                    'sunrise' => '06:00:00',
                    'midday'  => '12:00:00',
                    'sunset'  => '19:00:00',
                    'night'   => '21:00:00',
                    'custom'  => (!empty($plan['custom_hour']) ? $plan['custom_hour'] : '07:00:00'),
                    default   => '07:00:00',
                };
                $start = new \DateTime($plan['start_date'] . ' ' . $timeStr);
                $end   = clone $start;
                $end->modify('+30 minutes');
                $event = [
                    'summary'     => $title,
                    'description' => $descStr,
                    'start'       => ['dateTime' => $start->format(\DateTime::RFC3339), 'timeZone' => $tz],
                    'end'         => ['dateTime' => $end->format(\DateTime::RFC3339), 'timeZone' => $tz],
                    'recurrence'  => [$rrule],
                ];
            }

            $result = $this->httpPost(
                self::CALENDAR_API . '/calendars/' . urlencode($calId) . '/events',
                $event, $token, true
            );

            return $result['id'] ?? null;
        } catch (\Throwable $e) {
            \App\Support\Logger::warning('Irrigation calendar create failed: ' . $e->getMessage());
            return null;
        }
    }

    private function deleteCalendarEvent(DB $db, string $eventId): void
    {
        try {
            $settings = $this->loadSettings($db);
            if (empty($settings['google_calendar.refresh_token'])) return;
            $token = $this->validToken($settings, $db);
            $calId = $settings['google_calendar.calendar_id'] ?: 'primary';
            $ctx = stream_context_create(['http' => [
                'method' => 'DELETE',
                'header' => 'Authorization: Bearer ' . $token,
                'ignore_errors' => true,
            ]]);
            @file_get_contents(
                self::CALENDAR_API . '/calendars/' . urlencode($calId) . '/events/' . urlencode($eventId),
                false, $ctx
            );
        } catch (\Throwable $e) {
            \App\Support\Logger::warning('Irrigation calendar delete failed: ' . $e->getMessage());
        }
    }

    private function loadSettings(DB $db): array
    {
        $rows = $db->fetchAll("SELECT setting_key, setting_value_text FROM settings WHERE setting_key LIKE 'google_calendar.%'");
        $out = [];
        foreach ($rows as $r) { $out[$r['setting_key']] = $r['setting_value_text']; }
        return $out;
    }

    private function validToken(array $settings, DB $db): string
    {
        $expires = (int)($settings['google_calendar.token_expires_at'] ?? 0);
        if ($expires < time() + 300) {
            $res = $this->httpPost(self::TOKEN_URL, [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $settings['google_calendar.refresh_token'] ?? '',
                'client_id'     => $settings['google_calendar.client_id']     ?? '',
                'client_secret' => $settings['google_calendar.client_secret'] ?? '',
            ]);
            if (!empty($res['access_token'])) {
                foreach (['access_token' => $res['access_token'], 'token_expires_at' => (string)(time() + (int)($res['expires_in'] ?? 3600))] as $k => $v) {
                    $db->execute(
                        "INSERT INTO settings (setting_key,setting_value_text,value_type,autoload,updated_at) VALUES (?,?,'text',0,NOW())
                         ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text),updated_at=NOW()",
                        ['google_calendar.' . $k, $v]
                    );
                }
                return $res['access_token'];
            }
        }
        return $settings['google_calendar.access_token'] ?? '';
    }

    private function httpPost(string $url, array $data, ?string $token = null, bool $json = false): array
    {
        $headers = ['Content-Type: ' . ($json ? 'application/json' : 'application/x-www-form-urlencoded')];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $json ? json_encode($data) : http_build_query($data),
            'ignore_errors' => true,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        return $resp ? (json_decode($resp, true) ?? []) : [];
    }
}
