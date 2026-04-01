<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\CSRF;

class SettingsController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $db       = DB::getInstance();
        $settings = [];
        $rows     = $db->fetchAll('SELECT setting_key, setting_value_text, value_type FROM settings');
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value_text'];
        }
        Response::render('settings/index', ['title' => 'Settings', 'settings' => $settings]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));

        $allowed = [
            'app.name', 'app.currency', 'app.language', 'app.timezone',
            'gps.accuracy_threshold', 'image.refresh_interval_days',
            'reminder.default_lead_days', 'integration.google_calendar',
            'integration.weather',
        ];

        $db = DB::getInstance();
        foreach ($allowed as $key) {
            $value = $request->post(str_replace('.', '_', $key));
            if ($value !== null) {
                $db->execute(
                    'INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
                     VALUES (?,?,?,0,NOW())
                     ON DUPLICATE KEY UPDATE setting_value_text=VALUES(setting_value_text), updated_at=NOW()',
                    [$key, $value, 'text']
                );
            }
        }

        flash('success', 'Settings saved.');
        Response::redirect('/settings');
    }

    public function storage(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $targets = DB::getInstance()->fetchAll('SELECT * FROM storage_targets ORDER BY id ASC');
        Response::render('settings/storage', ['title' => 'Storage Settings', 'targets' => $targets]);
    }

    public function updateStorage(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        flash('success', 'Storage settings saved.');
        Response::redirect('/settings/storage');
    }

    public function actionTypes(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $types = DB::getInstance()->fetchAll('SELECT * FROM action_types ORDER BY action_label ASC');
        Response::render('settings/action-types', ['title' => 'Action Types', 'types' => $types]);
    }

    public function updateActionTypes(Request $request, array $params = []): void
    {
        $this->requireAuth();
        CSRF::validate($request->post('_token', ''));
        flash('success', 'Action types saved.');
        Response::redirect('/settings/action-types');
    }

    public function upcoming(Request $request, array $params = []): void
    {
        $this->requireAuth();
        $roadmap = require BASE_PATH . '/config/roadmap.php';
        Response::render('settings/upcoming', ['title' => 'Upcoming Features', 'roadmap' => $roadmap]);
    }
}
