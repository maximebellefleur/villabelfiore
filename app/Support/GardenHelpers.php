<?php

namespace App\Support;

/**
 * Pure PHP helpers for the Garden Redesign — port of the JSX prototype's data.jsx.
 * No DB calls in this class; callers fetch data, then run helpers on arrays.
 *
 * Conventions:
 *   $crop      = ['id'=>int, 'name'=>str, 'emoji'=>str, 'color'=>'#RRGGBB',
 *                 'spacing_cm'=>int, 'family'=>str, 'season'=>str,
 *                 'days_to_maturity'=>int]
 *   $planting  = ['cropId'=>int, 'plants'=>int, 'sown_at'=>'YYYY-MM-DD']
 *   $line      = ['id'=>int, 'lineNumber'=>int, 'lengthCm'=>int,
 *                 'plantings'=>[$planting,...], 'status'=>str,
 *                 'sown_at'=>str|null, 'empty_since'=>str|null,
 *                 'last_watered_at'=>str|null,
 *                 'succession'=>['cropId'=>int,'startsOn'=>str]|null,
 *                 'rotation_history'=>[{year,season,cropId},...]]
 */
class GardenHelpers
{
    /** Fallback color palette by family (when seed has no color). */
    public const FAMILY_COLORS = [
        'root'   => '#E07A3C',
        'leaf'   => '#5A8C3F',
        'fruit'  => '#C0413A',
        'herb'   => '#7BA84A',
        'allium' => '#B89A6A',
        'legume' => '#9C8049',
        'other'  => '#A66141',
    ];

    /**
     * Distinct color palette used when no explicit color and no family color
     * is set — picked deterministically per seed so each one gets a unique hue.
     */
    public const CATALOG_COLORS = [
        '#E76F51', '#F4A261', '#E9C46A', '#2A9D8F', '#264653',
        '#8AB17D', '#B5838D', '#6D597A', '#355070', '#E07A5F',
        '#81B29A', '#F2CC8F', '#D62828', '#F77F00', '#588157',
        '#9D4EDD', '#6A994E', '#BC4749', '#386641', '#A7C957',
    ];

    /**
     * Pick a color from CATALOG_COLORS deterministically based on a seed's
     * identifier (id or name). Same key → same color across the app.
     */
    public static function defaultCatalogColor(int|string $key): string
    {
        $hash = is_int($key) ? $key : crc32((string)$key);
        return self::CATALOG_COLORS[abs($hash) % count(self::CATALOG_COLORS)];
    }

    /** Fallback emoji by family. */
    public const FAMILY_EMOJI = [
        'root'   => '🥕',
        'leaf'   => '🥬',
        'fruit'  => '🍅',
        'herb'   => '🌿',
        'allium' => '🧅',
        'legume' => '🫘',
        'other'  => '🌱',
    ];

    public static function todayIso(): string
    {
        return (new \DateTime('today'))->format('Y-m-d');
    }

    /**
     * A crop record's display color.
     * Priority:
     *  1. explicit color field on the seed (#RRGGBB)
     *  2. deterministic per-seed pick from CATALOG_COLORS (so each seed in
     *     the same family is still visually distinct in the planting view).
     */
    public static function cropColor(array $crop): string
    {
        $c = $crop['color'] ?? null;
        if ($c && preg_match('/^#[0-9a-f]{6}$/i', $c)) return $c;
        $key = (int)($crop['id'] ?? 0);
        if ($key === 0) $key = $crop['name'] ?? 'other';
        return self::defaultCatalogColor($key);
    }

    /** A crop record's display emoji, with family fallback. */
    public static function cropEmoji(array $crop): string
    {
        $e = $crop['emoji'] ?? null;
        if ($e !== null && $e !== '') return $e;
        $fam = $crop['family'] ?? 'other';
        return self::FAMILY_EMOJI[$fam] ?? '🌱';
    }

    /** Days between two ISO dates (b - a). */
    public static function daysBetween(string $a, string $b): int
    {
        $da = new \DateTime($a);
        $db = new \DateTime($b);
        $diff = $db->getTimestamp() - $da->getTimestamp();
        return (int)round($diff / 86400);
    }

    /** Add N days (may be negative) to an ISO date, return ISO date. */
    public static function addDays(string $iso, int $n): string
    {
        $d = new \DateTime($iso);
        $d->modify(($n >= 0 ? '+' : '') . $n . ' days');
        return $d->format('Y-m-d');
    }

    /** Short display date "Apr 28". Empty string for nulls. */
    public static function fmtDate(?string $iso): string
    {
        if (!$iso) return '';
        try {
            return (new \DateTime($iso))->format('M j');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Year for a given ISO date (or current year). */
    public static function yearOf(?string $iso = null): int
    {
        return (int)(new \DateTime($iso ?: 'today'))->format('Y');
    }

    /** Season for a given month (cool/warm). */
    public static function seasonOfMonth(int $month): string
    {
        // Northern-hemisphere defaults: Apr–Sep = warm, else cool.
        return ($month >= 4 && $month <= 9) ? 'warm' : 'cool';
    }

    /** Used cm summed across plantings, given a $cropsById map keyed by cropId => crop[]. */
    public static function computeFill(array $line, array $cropsById): array
    {
        $used = 0;
        foreach (($line['plantings'] ?? []) as $p) {
            $c = $cropsById[$p['cropId'] ?? 0] ?? null;
            if (!$c) continue;
            $used += (int)($c['spacing_cm'] ?? 0) * (int)($p['plants'] ?? 0);
        }
        $length = (int)($line['lengthCm'] ?? 0);
        $remaining = max(0, $length - $used);
        return [
            'used'      => $used,
            'remaining' => $remaining,
            'pct'       => $length > 0 ? min(1.0, $used / $length) : 0,
        ];
    }

    /** Stripe segments for rendering. */
    public static function computeSegments(array $line, array $cropsById): array
    {
        $length = max(1, (int)($line['lengthCm'] ?? 0));
        $out = [];
        foreach (($line['plantings'] ?? []) as $p) {
            $c = $cropsById[$p['cropId'] ?? 0] ?? null;
            if (!$c) continue;
            $cm = (int)($c['spacing_cm'] ?? 0) * (int)($p['plants'] ?? 0);
            $out[] = [
                'plantingId' => (int)($p['id'] ?? 0),
                'cropId' => (int)$p['cropId'],
                'crop'   => $c,
                'plants' => (int)$p['plants'],
                'cm'     => $cm,
                'pct'    => $cm / $length,
                'sown_at'=> $p['sown_at'] ?? ($line['sown_at'] ?? null),
            ];
        }
        return $out;
    }

    /** Earliest planting harvest date for a line (last crop to mature). Null if no plantings. */
    public static function lineHarvestDate(array $line, array $cropsById): ?string
    {
        $plantings = $line['plantings'] ?? [];
        if (empty($plantings)) return null;
        $dates = [];
        foreach ($plantings as $p) {
            $c = $cropsById[$p['cropId'] ?? 0] ?? null;
            if (!$c) continue;
            $sownAt = $p['sown_at'] ?? ($line['sown_at'] ?? null);
            if (!$sownAt) continue;
            $dth = (int)($c['days_to_maturity'] ?? 60);
            $dates[] = self::addDays($sownAt, $dth);
        }
        if (empty($dates)) return null;
        sort($dates);
        return end($dates);
    }

    public static function lineSownDate(array $line): ?string
    {
        $plantings = $line['plantings'] ?? [];
        if (empty($plantings)) return $line['sown_at'] ?? null;
        $dates = [];
        foreach ($plantings as $p) {
            $d = $p['sown_at'] ?? ($line['sown_at'] ?? null);
            if ($d) $dates[] = $d;
        }
        if (empty($dates)) return null;
        sort($dates);
        return $dates[0];
    }

    /** Days until the line's last planting matures. Negative = overdue. Null = no plantings. */
    public static function daysToLineHarvest(array $line, array $cropsById, ?string $today = null): ?int
    {
        $h = self::lineHarvestDate($line, $cropsById);
        if (!$h) return null;
        return self::daysBetween($today ?: self::todayIso(), $h);
    }

    /** Maturity 0..1 for one planting. */
    public static function maturity(array $planting, array $line, array $crop, ?string $today = null): float
    {
        $sown = $planting['sown_at'] ?? ($line['sown_at'] ?? null);
        if (!$sown) return 0.0;
        $dth = max(1, (int)($crop['days_to_maturity'] ?? 60));
        $elapsed = self::daysBetween($sown, $today ?: self::todayIso());
        return max(0.0, min(1.0, $elapsed / $dth));
    }

    /**
     * Rotation warning. If $candidateCrop's family matches a recent rotation_history entry
     * (within 2 calendar years), return a string for the UI; else null.
     */
    public static function rotationWarning(array $line, array $candidateCrop, array $cropsById, ?string $today = null): ?string
    {
        $hist = $line['rotation_history'] ?? [];
        if (empty($hist)) return null;
        $cutoffYear = self::yearOf($today) - 2;
        $candFam = $candidateCrop['family'] ?? null;
        if (!$candFam) return null;
        foreach ($hist as $h) {
            $year = (int)($h['year'] ?? 0);
            if ($year < $cutoffYear) continue;
            $hCrop = $cropsById[$h['cropId'] ?? 0] ?? null;
            if (!$hCrop) continue;
            if (($hCrop['family'] ?? null) === $candFam) {
                $name = $hCrop['name'] ?? 'previous crop';
                return "Last grown here: " . $name . " (" . $year . ") — same family. Rotate to rest soil.";
            }
        }
        return null;
    }

    /**
     * Suggestions for an empty/partly-empty line — top 3 crops to try.
     * Ranks: not yet planted on this line, complementary family, fits in remaining space.
     * Returns array of crops (full records).
     */
    public static function getSuggestions(array $line, array $catalog, array $cropsById): array
    {
        $planted = [];
        foreach (($line['plantings'] ?? []) as $p) {
            $planted[(int)$p['cropId']] = true;
        }
        $families = [];
        foreach (($line['plantings'] ?? []) as $p) {
            $c = $cropsById[$p['cropId']] ?? null;
            if ($c && !empty($c['family'])) $families[$c['family']] = true;
        }
        $fill = self::computeFill($line, $cropsById);
        $remaining = $fill['remaining'];

        $ranked = [];
        foreach ($catalog as $c) {
            if (isset($planted[(int)$c['id']])) continue;
            // skip rotation-warned (avoid suggesting clashes)
            if (self::rotationWarning($line, $c, $cropsById)) continue;
            $score = 0;
            $fam = $c['family'] ?? 'other';
            if (isset($families['root'])  && $fam === 'leaf')   $score += 3;
            if (isset($families['leaf'])  && $fam === 'root')   $score += 3;
            if (isset($families['fruit']) && $fam === 'herb')   $score += 3;
            if (isset($families['herb'])  && $fam === 'fruit')  $score += 2;
            if (isset($families['legume']) && $fam !== 'legume') $score += 2;
            if (empty($families))                                $score += 1; // bare line: anything OK
            if ((int)($c['spacing_cm'] ?? 0) <= 15)              $score += 1;
            // require it to fit
            $sp = (int)($c['spacing_cm'] ?? 999);
            if ($sp > $remaining)                                $score -= 5;
            $ranked[] = ['crop' => $c, 'score' => $score];
        }
        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($ranked, 0, 3);
        return array_map(fn($r) => $r['crop'], $top);
    }

    /**
     * Default plant count when accepting a suggestion / tap-to-plant.
     * 1 if spacing >= 25cm, else floor(remaining / spacing / 4), min 1.
     */
    public static function defaultPlantCount(array $crop, int $remainingCm): int
    {
        $sp = max(1, (int)($crop['spacing_cm'] ?? 5));
        if ($sp >= 25) return 1;
        $n = (int)floor(max(0, $remainingCm) / $sp / 4);
        return max(1, $n);
    }

    /**
     * Compute the highest-priority Action for one bed.
     *
     * $bed = ['status'=>str, 'lines'=>[$line,...], 'last_watered_at'=>?str, ...]
     * Each line carries 'plantings', 'status', 'last_watered_at'.
     * Returns array of actions; consumer should pick the first (highest urgency).
     */
    public static function bedActions(array $bed, array $cropsById, ?string $today = null): array
    {
        $today = $today ?: self::todayIso();
        $actions = [];

        $bedStatus = $bed['status'] ?? null;

        // Pre-compute aggregate status across lines
        $hasGrowing = false; $hasEmpty = false; $hasPlanned = false; $hasHarvested = false;
        foreach (($bed['lines'] ?? []) as $line) {
            $st = $line['status'] ?? 'empty';
            if ($st === 'growing')   $hasGrowing = true;
            if ($st === 'empty')     $hasEmpty = true;
            if ($st === 'planned')   $hasPlanned = true;
            if ($st === 'harvested') $hasHarvested = true;

            if ($st === 'growing') {
                $watered = $line['last_watered_at'] ?? ($bed['last_watered_at'] ?? null);
                if ($watered) {
                    $dry = self::daysBetween(substr($watered, 0, 10), $today);
                    if ($dry >= 4) {
                        $actions[] = ['kind'=>'water','urgency'=>'high','icon'=>'💧','label'=>"Dry {$dry}d · water today"];
                    }
                }
                $dth = self::daysToLineHarvest($line, $cropsById, $today);
                if ($dth !== null) {
                    if ($dth <= 3 && $dth >= -2) {
                        $actions[] = ['kind'=>'harvest','urgency'=>'high','icon'=>'🌾','label'=>'Ready to harvest'];
                    } elseif ($dth > 3 && $dth <= 10) {
                        $actions[] = ['kind'=>'harvestSoon','urgency'=>'med','icon'=>'⏳','label'=>"Harvest soon · {$dth}d"];
                    }
                }
                // Thin seedlings if any planting is < 20% mature
                foreach (($line['plantings'] ?? []) as $p) {
                    $c = $cropsById[$p['cropId']] ?? null;
                    if (!$c) continue;
                    $m = self::maturity($p, $line, $c, $today);
                    if ($m > 0 && $m < 0.2) {
                        $actions[] = ['kind'=>'thin','urgency'=>'low','icon'=>'✂','label'=>'Thin seedlings'];
                        break;
                    }
                }
            }
            if ($st === 'planned' && !empty($line['succession_starts_on'])) {
                $actions[] = ['kind'=>'sow','urgency'=>'high','icon'=>'🌱','label'=>'Sow scheduled · ' . self::fmtDate($line['succession_starts_on'])];
            } elseif ($st === 'empty') {
                $actions[] = ['kind'=>'plan','urgency'=>'med','icon'=>'🪴','label'=>'Empty · plan a crop'];
            } elseif ($st === 'harvested') {
                $actions[] = ['kind'=>'reset','urgency'=>'low','icon'=>'🔄','label'=>'Reset bed'];
            }
        }

        // Bed-level fallbacks if we have no lines at all
        if (empty($bed['lines'])) {
            if ($bedStatus === 'planned') {
                $actions[] = ['kind'=>'sow','urgency'=>'high','icon'=>'🌱','label'=>'Sow scheduled'];
            } elseif ($bedStatus === 'harvested') {
                $actions[] = ['kind'=>'reset','urgency'=>'low','icon'=>'🔄','label'=>'Reset bed'];
            } else {
                $actions[] = ['kind'=>'plan','urgency'=>'med','icon'=>'🪴','label'=>'Empty · plan a crop'];
            }
        }

        // Sort by urgency rank
        $rank = ['high'=>0, 'med'=>1, 'water'=>2, 'sow'=>3, 'low'=>4];
        usort($actions, fn($a, $b) => ($rank[$a['urgency']] ?? 99) <=> ($rank[$b['urgency']] ?? 99));
        return $actions;
    }

    /**
     * Compass direction (N/NE/E/SE/S/SW/W/NW) of a bed relative to the centroid of
     * its garden's beds. Returns null if at centroid or no GPS.
     */
    public static function bedOrientation(array $bed, array $gardenBeds): ?string
    {
        if (empty($bed['gps_lat']) || empty($bed['gps_lng'])) return null;
        $lats = []; $lngs = [];
        foreach ($gardenBeds as $b) {
            if (!empty($b['gps_lat']) && !empty($b['gps_lng'])) {
                $lats[] = (float)$b['gps_lat'];
                $lngs[] = (float)$b['gps_lng'];
            }
        }
        if (count($lats) < 2) return null;
        $meanLat = array_sum($lats) / count($lats);
        $meanLng = array_sum($lngs) / count($lngs);
        $dLat = (float)$bed['gps_lat'] - $meanLat;
        $dLng = (float)$bed['gps_lng'] - $meanLng;
        $dist = sqrt($dLat * $dLat + $dLng * $dLng);
        if ($dist < 1e-6) return null;
        // atan2(dLng, dLat) → 0=N, +90=E (clockwise from north)
        $angle = atan2($dLng, $dLat) * 180 / M_PI;
        $idx = ((int)round((($angle + 360) % 360) / 45)) % 8;
        $dirs = ['N','NE','E','SE','S','SW','W','NW'];
        return $dirs[$idx];
    }

    /**
     * Compute the 'startsOn' date for a new succession.
     * If the line has plantings, harvestDate + 3 days; else today + 7.
     */
    public static function nextSuccessionStart(array $line, array $cropsById, ?string $today = null): string
    {
        $today = $today ?: self::todayIso();
        $h = self::lineHarvestDate($line, $cropsById);
        if ($h) {
            return self::addDays($h, 3);
        }
        return self::addDays($today, 7);
    }

    /**
     * Property-level summary counts for the Garden hub "This week" strip.
     * Returns array with keys: harvest, water, sow, plan, thin
     */
    public static function propertySummary(array $beds, array $cropsById, ?string $today = null): array
    {
        $sum = ['harvest'=>0, 'water'=>0, 'sow'=>0, 'plan'=>0, 'thin'=>0];
        foreach ($beds as $bed) {
            $actions = self::bedActions($bed, $cropsById, $today);
            $kinds = array_unique(array_map(fn($a) => $a['kind'], $actions));
            foreach ($kinds as $k) {
                if ($k === 'harvest')                                  $sum['harvest']++;
                if ($k === 'water')                                    $sum['water']++;
                if ($k === 'sow')                                      $sum['sow']++;
                if ($k === 'plan')                                     $sum['plan']++;
                if ($k === 'thin')                                     $sum['thin']++;
            }
        }
        return $sum;
    }
}
