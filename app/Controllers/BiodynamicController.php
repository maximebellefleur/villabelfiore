<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\DB;
use App\Support\BiodynamicCalendar;

class BiodynamicController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) { Response::redirect('/login'); }
    }

    public function index(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $year  = (int) $request->get('year',  date('Y'));
        $month = (int) $request->get('month', date('n'));
        $month = max(1, min(12, $month));
        $year  = max(2000, min(2100, $year));

        // Get configured timezone
        $db    = DB::getInstance();
        $tzRow = $db->fetchOne("SELECT setting_value_text FROM settings WHERE setting_key = 'app.timezone'");
        $tzStr = ($tzRow['setting_value_text'] ?? '') ?: 'Europe/Rome';

        $monthData = BiodynamicCalendar::forMonth($year, $month, $tzStr);

        // Summarise each day for quick-scan view
        $daySummary = [];
        foreach ($monthData as $day => $hours) {
            $organs   = array_column($hours, 'organ');
            $dominant = array_search(max(array_count_values($organs)), array_count_values($organs));
            $ascHours = count(array_filter($hours, fn($h) => $h['is_ascending']));
            $anomHrs  = count(array_filter($hours, fn($h) => $h['is_anomaly']));
            $daySummary[$day] = [
                'dominant_organ' => $dominant,
                'ascending_hours'=> $ascHours,
                'anomaly_hours'  => $anomHrs,
                'is_planting_day'=> $ascHours < 12 && $anomHrs < 12, // >half descending, <half anomaly
            ];
        }

        // Prev / next month
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;

        $today = (int) date('j');
        $todayMonth = (int) date('n');
        $todayYear  = (int) date('Y');
        $isCurrentMonth = ($year === $todayYear && $month === $todayMonth);

        Response::render('garden/biodynamic', [
            'title'          => 'Biodynamic Calendar',
            'year'           => $year,
            'month'          => $month,
            'monthData'      => $monthData,
            'daySummary'     => $daySummary,
            'prevMonth'      => $prevMonth,
            'prevYear'       => $prevYear,
            'nextMonth'      => $nextMonth,
            'nextYear'       => $nextYear,
            'today'          => $today,
            'isCurrentMonth' => $isCurrentMonth,
            'tz'             => $tzStr,
        ]);
    }
}
