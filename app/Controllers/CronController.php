<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\GardenHelpers;
use App\Support\GardenSchema;

/**
 * Cron / scheduled task entry points.
 *
 * Each endpoint requires a shared secret in `?key=` matching the value stored
 * in `storage/cron.key`. The key file is auto-generated on first call by an
 * admin who is logged in (so a cron URL can be retrieved from the admin UI).
 *
 * cPanel cron command example (daily at 03:00):
 *   curl -s "https://YOUR-DOMAIN/cron/seed-counts?key=YOUR-KEY" > /dev/null
 */
class CronController
{
    private function keyPath(): string
    {
        return BASE_PATH . '/storage/cron.key';
    }

    private function readOrCreateKey(): string
    {
        $path = $this->keyPath();
        if (is_file($path)) {
            $k = trim((string)@file_get_contents($path));
            if ($k !== '') return $k;
        }
        $k = bin2hex(random_bytes(20));
        @mkdir(dirname($path), 0775, true);
        @file_put_contents($path, $k);
        @chmod($path, 0600);
        return $k;
    }

    private function authorize(Request $request): bool
    {
        $supplied = (string)$request->get('key', '');
        if ($supplied === '') return false;
        $expected = $this->readOrCreateKey();
        return hash_equals($expected, $supplied);
    }

    /**
     * GET /cron/seed-counts?key=…
     *
     * Recomputes seeds.projected_seed_prod_count for every seed based on
     * current garden_plantings (growing/sown OR planned-with-past-date).
     * Replaces the old seed_ground_cache bed-write trigger system.
     */
    public function seedProjectedCounts(Request $request, array $params = []): void
    {
        if (!$this->authorize($request)) {
            Response::json(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }
        $db = DB::getInstance();
        GardenSchema::ensure($db);
        $count = GardenHelpers::recalcAllSeedsProjected($db);
        Response::json([
            'success'         => true,
            'seeds_processed' => $count,
            'ran_at'          => date('c'),
        ]);
    }

    /**
     * GET /settings/cron-info — admin-only view: shows the cron URL with key.
     * Available to logged-in users; key is auto-generated on first visit.
     */
    public function info(Request $request, array $params = []): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
            return;
        }
        $key = $this->readOrCreateKey();
        $base = rtrim(url('/'), '/');
        $url  = $base . '/cron/seed-counts?key=' . $key;
        Response::render('settings/cron_info', [
            'title'    => 'Cron Setup',
            'cronUrl'  => $url,
            'cronKey'  => $key,
        ]);
    }
}
