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
            duration_months INT NOT NULL DEFAULT 12,
            interval_type VARCHAR(30) NOT NULL DEFAULT 'daily',
            start_date DATE NOT NULL,
            notes TEXT,
            google_event_id VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
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

        $existing = $db->fetchOne('SELECT id FROM irrigation_plans WHERE item_id = ?', [$itemId]);
        if ($existing) {
            flash('error', 'An irrigation plan already exists. Edit or delete it first.');
            Response::redirect('/items/' . $itemId);
            return;
        }

        $plan = $this->planFromRequest($request);
        $db->execute(
            'INSERT INTO irrigation_plans (item_id, duration_months, interval_type, start_date, notes, created_at, updated_at)
             VALUES (?,?,?,?,?,NOW(),NOW())',
            [$itemId, $plan['duration_months'], $plan['interval_type'], $plan['start_date'], $plan['notes']]
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
            'UPDATE irrigation_plans SET duration_months=?, interval_type=?, start_date=?, notes=?, google_event_id=NULL, updated_at=NOW() WHERE id=?',
            [$plan['duration_months'], $plan['interval_type'], $plan['start_date'], $plan['notes'], $planId]
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
        $this->ensureTable($db);

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
    // Helpers
    // -------------------------------------------------------------------------

    private function planFromRequest(Request $request): array
    {
        return [
            'duration_months' => max(1, (int)$request->post('duration_months', 12)),
            'interval_type'   => trim($request->post('interval_type', 'daily')),
            'start_date'      => trim($request->post('start_date', date('Y-m-d'))) ?: date('Y-m-d'),
            'notes'           => trim($request->post('notes', '')),
        ];
    }

    public static function intervalLabel(string $interval): string
    {
        return [
            'twice_daily'  => 'Twice daily',
            'daily'        => 'Daily',
            'every_2_days' => 'Every 2 days',
            'weekly'       => 'Weekly',
            'biweekly'     => 'Every 2 weeks',
            'monthly'      => 'Monthly',
        ][$interval] ?? $interval;
    }

    private function rrule(string $interval, string $startDate, int $durationMonths): string
    {
        $end = new \DateTime($startDate);
        $end->modify('+' . $durationMonths . ' months');
        $until = $end->format('Ymd') . 'T235959Z';

        $map = [
            'twice_daily'  => 'FREQ=DAILY;INTERVAL=1',
            'daily'        => 'FREQ=DAILY;INTERVAL=1',
            'every_2_days' => 'FREQ=DAILY;INTERVAL=2',
            'weekly'       => 'FREQ=WEEKLY;INTERVAL=1',
            'biweekly'     => 'FREQ=WEEKLY;INTERVAL=2',
            'monthly'      => 'FREQ=MONTHLY;INTERVAL=1',
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
            if ($plan['interval_type'] === 'twice_daily') {
                $label = 'Twice daily (schedule morning & evening)';
            }

            $start = new \DateTime($plan['start_date'] . ' 07:00:00');
            $end   = clone $start;
            $end->modify('+30 minutes');
            $tz = date_default_timezone_get();

            $event = [
                'summary'    => '💧 Water — ' . $item['name'],
                'description' => $label . ($plan['notes'] ? "\n\n" . $plan['notes'] : '') . "\n\n" . $itemUrl,
                'start'      => ['dateTime' => $start->format(\DateTime::RFC3339), 'timeZone' => $tz],
                'end'        => ['dateTime' => $end->format(\DateTime::RFC3339), 'timeZone' => $tz],
                'recurrence' => [$this->rrule($plan['interval_type'], $plan['start_date'], $plan['duration_months'])],
            ];

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
