<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

/**
 * Google Calendar integration.
 *
 * OAuth 2.0 flow (installed-app / web server):
 *   1. User saves client_id + client_secret in settings.
 *   2. GET /settings/calendar/connect  → redirect to Google consent screen.
 *   3. Google redirects to GET /settings/calendar/callback?code=XXX
 *   4. Exchange code → access_token + refresh_token → store in settings.
 *   5. POST /settings/calendar/sync  → push pending reminders as events.
 *
 * Tokens stored under setting_key:
 *   google_calendar.client_id
 *   google_calendar.client_secret
 *   google_calendar.calendar_id    (default 'primary')
 *   google_calendar.access_token
 *   google_calendar.refresh_token
 *   google_calendar.token_expires_at  (Unix timestamp)
 *   google_calendar.connected_email
 */
class CalendarController
{
    private const OAUTH_AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CALENDAR_API    = 'https://www.googleapis.com/calendar/v3';
    private const SCOPE           = 'https://www.googleapis.com/auth/calendar.events';

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    // -------------------------------------------------------------------------
    // GET /settings/calendar
    // -------------------------------------------------------------------------
    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $settings = $this->loadCalendarSettings();

        Response::render('settings/calendar', [
            'title'       => 'Google Calendar',
            'settings'    => $settings,
            'isConnected' => !empty($settings['google_calendar.refresh_token']),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /settings/calendar/save
    // -------------------------------------------------------------------------
    public function save(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $db = DB::getInstance();
        $keys = ['google_calendar.client_id', 'google_calendar.client_secret', 'google_calendar.calendar_id'];

        foreach ($keys as $key) {
            $postKey = str_replace(['google_calendar.', '.'], ['', '_'], $key);
            $value   = trim($request->post($postKey, ''));
            if ($value !== '') {
                $this->saveSetting($db, $key, $value);
            }
        }

        flash('success', 'Credentials saved. Now click "Connect to Google" to authorise.');
        Response::redirect('/settings/calendar');
    }

    // -------------------------------------------------------------------------
    // GET /settings/calendar/connect
    // -------------------------------------------------------------------------
    public function connect(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $settings = $this->loadCalendarSettings();

        $clientId = $settings['google_calendar.client_id'] ?? '';
        if (!$clientId) {
            flash('error', 'Enter your Google OAuth Client ID first.');
            Response::redirect('/settings/calendar');
        }

        $redirectUri = $this->redirectUri();
        $state       = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $url = self::OAUTH_AUTH_URL . '?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        header('Location: ' . $url);
        exit;
    }

    // -------------------------------------------------------------------------
    // GET /settings/calendar/callback
    // -------------------------------------------------------------------------
    public function callback(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $code  = $request->get('code', '');
        $state = $request->get('state', '');
        $error = $request->get('error', '');

        if ($error) {
            flash('error', 'Google authorisation cancelled or denied: ' . e($error));
            Response::redirect('/settings/calendar');
        }

        // CSRF-like state check
        if (!$state || $state !== ($_SESSION['google_oauth_state'] ?? '')) {
            flash('error', 'Invalid OAuth state. Please try connecting again.');
            Response::redirect('/settings/calendar');
        }
        unset($_SESSION['google_oauth_state']);

        if (!$code) {
            flash('error', 'No authorisation code received from Google.');
            Response::redirect('/settings/calendar');
        }

        $settings    = $this->loadCalendarSettings();
        $clientId    = $settings['google_calendar.client_id']     ?? '';
        $clientSecret= $settings['google_calendar.client_secret'] ?? '';
        $redirectUri = $this->redirectUri();

        // Exchange code for tokens
        $tokenData = $this->httpPost(self::OAUTH_TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (isset($tokenData['error'])) {
            flash('error', 'Token exchange failed: ' . ($tokenData['error_description'] ?? $tokenData['error']));
            Response::redirect('/settings/calendar');
        }

        $db = DB::getInstance();
        $this->saveSetting($db, 'google_calendar.access_token',    $tokenData['access_token']);
        $this->saveSetting($db, 'google_calendar.token_expires_at', (string)(time() + (int)($tokenData['expires_in'] ?? 3600)));
        if (!empty($tokenData['refresh_token'])) {
            $this->saveSetting($db, 'google_calendar.refresh_token', $tokenData['refresh_token']);
        }

        flash('success', 'Google Calendar connected successfully.');
        Response::redirect('/settings/calendar');
    }

    // -------------------------------------------------------------------------
    // POST /settings/calendar/disconnect
    // -------------------------------------------------------------------------
    public function disconnect(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $db = DB::getInstance();
        foreach (['google_calendar.access_token', 'google_calendar.refresh_token',
                  'google_calendar.token_expires_at', 'google_calendar.connected_email'] as $key) {
            $db->execute("DELETE FROM settings WHERE setting_key = ?", [$key]);
        }

        flash('success', 'Google Calendar disconnected.');
        Response::redirect('/settings/calendar');
    }

    // -------------------------------------------------------------------------
    // POST /settings/calendar/sync
    // -------------------------------------------------------------------------
    public function sync(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $settings = $this->loadCalendarSettings();
        if (empty($settings['google_calendar.refresh_token'])) {
            flash('error', 'Google Calendar is not connected. Connect first.');
            Response::redirect('/settings/calendar');
        }

        try {
            $token      = $this->getValidAccessToken($settings);
            $calendarId = $settings['google_calendar.calendar_id'] ?: 'primary';
            $db         = DB::getInstance();

            $reminders = $db->fetchAll(
                "SELECT r.*, i.name AS item_name
                 FROM reminders r
                 LEFT JOIN items i ON i.id = r.item_id
                 WHERE r.status = 'pending' AND r.due_at >= NOW()
                 ORDER BY r.due_at ASC
                 LIMIT 100"
            );

            $created = 0;
            $updated = 0;
            $errors  = 0;

            foreach ($reminders as $reminder) {
                try {
                    $event = $this->buildCalendarEvent($reminder);
                    if (!empty($reminder['google_calendar_event_id'])) {
                        // Update existing event
                        $this->httpPut(
                            self::CALENDAR_API . '/calendars/' . urlencode($calendarId) .
                            '/events/' . urlencode($reminder['google_calendar_event_id']),
                            $event,
                            $token
                        );
                        $updated++;
                    } else {
                        // Create new event
                        $result = $this->httpPost(
                            self::CALENDAR_API . '/calendars/' . urlencode($calendarId) . '/events',
                            $event,
                            $token,
                            true
                        );
                        if (!empty($result['id'])) {
                            $db->execute(
                                'UPDATE reminders SET google_calendar_event_id = ? WHERE id = ?',
                                [$result['id'], $reminder['id']]
                            );
                            $created++;
                        } else {
                            $errors++;
                        }
                    }
                } catch (\Throwable $e) {
                    $errors++;
                }
            }

            $msg = 'Sync complete: ' . $created . ' created, ' . $updated . ' updated';
            if ($errors) { $msg .= ', ' . $errors . ' failed'; }
            flash('success', $msg . '.');
        } catch (\Throwable $e) {
            flash('error', 'Sync failed: ' . $e->getMessage());
        }

        Response::redirect('/settings/calendar');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildCalendarEvent(array $reminder): array
    {
        $title = $reminder['title'];
        if (!empty($reminder['item_name'])) {
            $title .= ' — ' . $reminder['item_name'];
        }

        $due = new \DateTime($reminder['due_at']);

        return [
            'summary'     => $title,
            'description' => $reminder['description'] ?? '',
            'start'       => ['dateTime' => $due->format(\DateTime::RFC3339), 'timeZone' => date_default_timezone_get()],
            'end'         => ['dateTime' => $due->modify('+1 hour')->format(\DateTime::RFC3339), 'timeZone' => date_default_timezone_get()],
            'reminders'   => ['useDefault' => false, 'overrides' => [['method' => 'popup', 'minutes' => 60]]],
        ];
    }

    private function getValidAccessToken(array $settings): string
    {
        $expiresAt = (int)($settings['google_calendar.token_expires_at'] ?? 0);
        // Refresh if expired or expiring within 5 minutes
        if ($expiresAt < time() + 300) {
            return $this->refreshAccessToken($settings);
        }
        return $settings['google_calendar.access_token'] ?? '';
    }

    private function refreshAccessToken(array $settings): string
    {
        $result = $this->httpPost(self::OAUTH_TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $settings['google_calendar.refresh_token'] ?? '',
            'client_id'     => $settings['google_calendar.client_id']     ?? '',
            'client_secret' => $settings['google_calendar.client_secret'] ?? '',
        ]);

        if (isset($result['error'])) {
            throw new \RuntimeException('Token refresh failed: ' . ($result['error_description'] ?? $result['error']));
        }

        $db = DB::getInstance();
        $this->saveSetting($db, 'google_calendar.access_token',    $result['access_token']);
        $this->saveSetting($db, 'google_calendar.token_expires_at', (string)(time() + (int)($result['expires_in'] ?? 3600)));

        return $result['access_token'];
    }

    private function httpPost(string $url, array $data, ?string $bearerToken = null, bool $jsonBody = false): array
    {
        $headers = ['Content-Type: ' . ($jsonBody ? 'application/json' : 'application/x-www-form-urlencoded')];
        if ($bearerToken) { $headers[] = 'Authorization: Bearer ' . $bearerToken; }

        $body = $jsonBody ? json_encode($data) : http_build_query($data);

        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $body,
            'ignore_errors' => true,
        ]]);

        $response = @file_get_contents($url, false, $context);
        return $response ? (json_decode($response, true) ?? []) : [];
    }

    private function httpPut(string $url, array $data, string $bearerToken): array
    {
        $context = stream_context_create(['http' => [
            'method'  => 'PUT',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer " . $bearerToken,
            'content' => json_encode($data),
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        return $response ? (json_decode($response, true) ?? []) : [];
    }

    private function httpGet(string $url, string $bearerToken): array
    {
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $bearerToken,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        return $response ? (json_decode($response, true) ?? []) : [];
    }

    private function loadCalendarSettings(): array
    {
        $db   = DB::getInstance();
        $rows = $db->fetchAll(
            "SELECT setting_key, setting_value_text FROM settings WHERE setting_key LIKE 'google_calendar.%'"
        );
        $out = [];
        foreach ($rows as $r) { $out[$r['setting_key']] = $r['setting_value_text']; }
        return $out;
    }

    private function saveSetting(DB $db, string $key, string $value): void
    {
        $db->execute(
            'INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
             VALUES (?,?,"text",0,NOW())
             ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text), updated_at=NOW()',
            [$key, $value]
        );
    }

    private function redirectUri(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . APP_BASE . '/settings/calendar/callback';
    }
}
