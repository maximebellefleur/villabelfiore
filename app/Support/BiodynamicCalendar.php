<?php

namespace App\Support;

/**
 * Biodynamic planting calendar engine based on Maria Thun's method.
 *
 * Uses Jean Meeus "Astronomical Algorithms" (2nd ed.) for lunar position.
 * Sidereal zodiac via Fagan-Bradley ayanamsa.
 *
 * Element/organ mapping:
 *   Earth (Root)   → Taurus, Virgo, Capricorn
 *   Water (Leaf)   → Cancer, Scorpio, Pisces
 *   Air   (Flower) → Gemini, Libra, Aquarius
 *   Fire  (Fruit)  → Aries, Leo, Sagittarius
 *
 * Descending moon (declination decreasing) = planting window
 * Ascending  moon (declination increasing) = harvest window
 * Anomalies (lunar nodes, apogee/perigee ±6 h) = avoid garden work
 */
class BiodynamicCalendar
{
    // Sidereal constellations in 30° equal divisions
    private const SIGNS = [
        ['name' => 'Aries',       'element' => 'Fire',  'organ' => 'Fruit',  'organ_it' => 'Frutti',  'emoji' => '🍎', 'color' => '#c2410c', 'bg' => '#fff7ed'],
        ['name' => 'Taurus',      'element' => 'Earth', 'organ' => 'Root',   'organ_it' => 'Radici',  'emoji' => '🥕', 'color' => '#92400e', 'bg' => '#fef3c7'],
        ['name' => 'Gemini',      'element' => 'Air',   'organ' => 'Flower', 'organ_it' => 'Fiori',   'emoji' => '🌸', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        ['name' => 'Cancer',      'element' => 'Water', 'organ' => 'Leaf',   'organ_it' => 'Foglie',  'emoji' => '🥬', 'color' => '#15803d', 'bg' => '#f0fdf4'],
        ['name' => 'Leo',         'element' => 'Fire',  'organ' => 'Fruit',  'organ_it' => 'Frutti',  'emoji' => '🍎', 'color' => '#c2410c', 'bg' => '#fff7ed'],
        ['name' => 'Virgo',       'element' => 'Earth', 'organ' => 'Root',   'organ_it' => 'Radici',  'emoji' => '🥕', 'color' => '#92400e', 'bg' => '#fef3c7'],
        ['name' => 'Libra',       'element' => 'Air',   'organ' => 'Flower', 'organ_it' => 'Fiori',   'emoji' => '🌸', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        ['name' => 'Scorpio',     'element' => 'Water', 'organ' => 'Leaf',   'organ_it' => 'Foglie',  'emoji' => '🥬', 'color' => '#15803d', 'bg' => '#f0fdf4'],
        ['name' => 'Sagittarius', 'element' => 'Fire',  'organ' => 'Fruit',  'organ_it' => 'Frutti',  'emoji' => '🍎', 'color' => '#c2410c', 'bg' => '#fff7ed'],
        ['name' => 'Capricorn',   'element' => 'Earth', 'organ' => 'Root',   'organ_it' => 'Radici',  'emoji' => '🥕', 'color' => '#92400e', 'bg' => '#fef3c7'],
        ['name' => 'Aquarius',    'element' => 'Air',   'organ' => 'Flower', 'organ_it' => 'Fiori',   'emoji' => '🌸', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        ['name' => 'Pisces',      'element' => 'Water', 'organ' => 'Leaf',   'organ_it' => 'Foglie',  'emoji' => '🥬', 'color' => '#15803d', 'bg' => '#f0fdf4'],
    ];

    // Element colours for the UI
    public const ORGAN_COLOR = [
        'Root'   => '#92400e',
        'Leaf'   => '#15803d',
        'Flower' => '#7c3aed',
        'Fruit'  => '#c2410c',
    ];
    public const ORGAN_BG = [
        'Root'   => '#fef3c7',
        'Leaf'   => '#f0fdf4',
        'Flower' => '#f5f3ff',
        'Fruit'  => '#fff7ed',
    ];
    public const ORGAN_EMOJI = [
        'Root'   => '🥕',
        'Leaf'   => '🥬',
        'Flower' => '🌸',
        'Fruit'  => '🍎',
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Compute one data point for the given DateTime.
     * Includes ascending/descending flag and anomaly flag (±6 h check).
     *
     * @return array{
     *   name:string, element:string, organ:string, organ_it:string,
     *   emoji:string, color:string, bg:string,
     *   longitude:float, latitude:float, declination:float, distance_km:float,
     *   is_ascending:bool, is_descending:bool, is_anomaly:bool
     * }
     */
    public static function computePoint(\DateTime $dt): array
    {
        $jd   = self::dtToJD($dt);
        $data = self::raw($jd);

        // Ascending = declination increasing toward next hour
        $jdNext   = $jd + 1 / 24.0;
        $decNext  = self::moonDeclination($jdNext);
        $data['is_ascending']  = $decNext > $data['declination'];
        $data['is_descending'] = !$data['is_ascending'];

        // Anomaly: check for node or apse within ±6 h
        $data['is_anomaly'] = self::checkAnomaly($jd);

        return $data;
    }

    /**
     * Compute full month data, hourly.
     * Returns [day => [hour => dataPoint], ...]  (day: 1-based, hour: 0-23)
     *
     * @return array<int, array<int, array>>
     */
    public static function forMonth(int $year, int $month, string $tzStr = 'UTC'): array
    {
        $tz   = new \DateTimeZone($tzStr);
        $utcZ = new \DateTimeZone('UTC');
        $days = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

        // Build flat array: index 0 = day 1 hour 0, ... with 2 extra at each end for derivatives
        $totalHours = $days * 24;
        $jds   = [];   // raw JDs
        $raw   = [];   // raw astronomical data

        // compute -1 and +$totalHours for derivative purposes
        for ($i = -1; $i <= $totalHours; $i++) {
            $dt = new \DateTime(
                sprintf('%04d-%02d-%02d %02d:00:00', $year, $month, 1, 0),
                $tz
            );
            $dt->modify('+' . $i . ' hours');
            $dt->setTimezone($utcZ);
            $jd    = self::dtToJD($dt);
            $jds[$i]  = $jd;
            $raw[$i]  = self::raw($jd);
        }

        // --- Detect anomaly indices (nodes + apogee/perigee) ------------------
        $anomalyIdx = [];

        // Nodes: lunar latitude changes sign
        for ($i = 0; $i < $totalHours; $i++) {
            if (($raw[$i - 1]['latitude'] > 0) !== ($raw[$i]['latitude'] > 0)) {
                $anomalyIdx[] = $i;
            }
        }

        // Apogee/Perigee: derivative of distance changes sign
        $prevDeriv = $raw[0]['distance_km'] - $raw[-1]['distance_km'];
        for ($i = 0; $i < $totalHours; $i++) {
            $curDeriv = $raw[$i + 1]['distance_km'] - $raw[$i]['distance_km'];
            if (($prevDeriv > 0) !== ($curDeriv > 0)) {
                $anomalyIdx[] = $i;
            }
            $prevDeriv = $curDeriv;
        }

        // Apply ±6-hour buffer
        $anomalySet = [];
        foreach ($anomalyIdx as $idx) {
            for ($buf = $idx - 6; $buf <= $idx + 6; $buf++) {
                if ($buf >= 0 && $buf < $totalHours) {
                    $anomalySet[$buf] = true;
                }
            }
        }

        // --- Structure by day / hour, add ascending flag ----------------------
        $result = [];
        for ($d = 1; $d <= $days; $d++) {
            $result[$d] = [];
            for ($h = 0; $h < 24; $h++) {
                $i    = ($d - 1) * 24 + $h;
                $pt   = $raw[$i];
                $next = $raw[$i + 1];

                $pt['is_ascending']  = $next['declination'] > $pt['declination'];
                $pt['is_descending'] = !$pt['is_ascending'];
                $pt['is_anomaly']    = isset($anomalySet[$i]);

                $result[$d][$h] = $pt;
            }
        }

        return $result;
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /** Quick anomaly check: any node or apse within ±6 h of $jd */
    private static function checkAnomaly(float $jd): bool
    {
        $prevLat  = self::moonLatitude($jd - 6 / 24.0);
        $midLat   = self::moonLatitude($jd);
        $nextLat  = self::moonLatitude($jd + 6 / 24.0);

        // Node: sign change in latitude within window
        if (($prevLat > 0) !== ($midLat > 0) || ($midLat > 0) !== ($nextLat > 0)) {
            return true;
        }

        // Apse: distance changes direction within window
        $d0 = self::moonDistance($jd - 6 / 24.0);
        $d1 = self::moonDistance($jd);
        $d2 = self::moonDistance($jd + 6 / 24.0);
        if (($d1 < $d0 && $d1 < $d2) || ($d1 > $d0 && $d1 > $d2)) {
            return true;
        }

        return false;
    }

    /** Compute raw data point (no ascending/anomaly) */
    private static function raw(float $jd): array
    {
        $lonSid = self::moonLongitudeSidereal($jd);
        $lat    = self::moonLatitude($jd);
        $dec    = self::moonDeclination($jd);
        $dist   = self::moonDistance($jd);
        $sign   = self::signFromLongitude($lonSid);

        return array_merge($sign, [
            'longitude'   => $lonSid,
            'latitude'    => $lat,
            'declination' => $dec,
            'distance_km' => $dist,
        ]);
    }

    // ── DateTime → Julian Day ─────────────────────────────────────────────────

    private static function dtToJD(\DateTime $dt): float
    {
        $utc = clone $dt;
        $utc->setTimezone(new \DateTimeZone('UTC'));
        return self::julianDay(
            (int) $utc->format('Y'),
            (int) $utc->format('n'),
            (int) $utc->format('j'),
            (float) $utc->format('G') + (float) $utc->format('i') / 60.0
        );
    }

    private static function julianDay(int $year, int $month, int $day, float $hour = 0.0): float
    {
        if ($month <= 2) { $year--; $month += 12; }
        $A = (int) ($year / 100);
        $B = 2 - $A + (int) ($A / 4);
        return (int) (365.25 * ($year + 4716))
             + (int) (30.6001 * ($month + 1))
             + $day + $hour / 24.0
             + $B - 1524.5;
    }

    // ── Ayanamsa (Fagan-Bradley) ──────────────────────────────────────────────

    private static function ayanamsa(float $jd): float
    {
        // Base: 24.044° at J1950.0 = JD 2433282.42345905; rate 50.256"/year
        $yearsFrom1950 = ($jd - 2433282.42345905) / 365.25;
        return 24.044 + 0.013960 * $yearsFrom1950;
    }

    // ── Moon Tropical Longitude (Meeus Ch. 47, ~40 terms) ────────────────────

    private static function moonLongitudeTropical(float $jd): float
    {
        $T = ($jd - 2451545.0) / 36525.0;

        $L0 = 218.3164477 + 481267.88123421 * $T - 0.0015786  * $T * $T;
        $M  = 357.5291092 + 35999.0502909   * $T - 0.0001536  * $T * $T;
        $MP = 134.9633964 + 477198.8675055  * $T + 0.0087414  * $T * $T;
        $D  = 297.8501921 + 445267.1114034  * $T - 0.0018819  * $T * $T;
        $F  = 93.2720950  + 483202.0175233  * $T - 0.0036539  * $T * $T;
        $E  = 1.0 - 0.002516 * $T - 0.0000074 * $T * $T;

        $Mr  = deg2rad(fmod($M  + 720000, 360));
        $MPr = deg2rad(fmod($MP + 720000, 360));
        $Dr  = deg2rad(fmod($D  + 720000, 360));
        $Fr  = deg2rad(fmod($F  + 720000, 360));

        $SL =
            6288774 * sin($MPr)
          + 1274027 * sin(2*$Dr - $MPr)
          +  658314 * sin(2*$Dr)
          +  213618 * sin(2*$MPr)
          -  185116 * $E  * sin($Mr)
          -  114332 * sin(2*$Fr)
          +   58793 * sin(2*$Dr - 2*$MPr)
          +   57066 * $E  * sin(2*$Dr - $Mr - $MPr)
          +   53322 * sin(2*$Dr + $MPr)
          +   45758 * $E  * sin(2*$Dr - $Mr)
          -   40923 * $E  * sin($Mr - $MPr)
          -   34720 * sin($Dr)
          -   30383 * $E  * sin($Mr + $MPr)
          +   15327 * sin(2*$Dr - 2*$Fr)
          -   12528 * sin($MPr + 2*$Fr)
          +   10980 * sin($MPr - 2*$Fr)
          +   10675 * sin(4*$Dr - $MPr)
          +   10034 * sin(3*$MPr)
          +    8548 * sin(4*$Dr - 2*$MPr)
          -    7888 * $E  * sin(2*$Dr + $Mr - $MPr)
          -    6766 * $E  * sin(2*$Dr + $Mr)
          -    5163 * sin($Dr - $MPr)
          +    4987 * $E  * sin($Dr + $Mr)
          +    4036 * $E  * sin(2*$Dr - $Mr + $MPr)
          +    3994 * sin(2*$Dr + 2*$MPr)
          +    3861 * sin(4*$Dr)
          +    3665 * sin(2*$Dr - 3*$MPr)
          -    2689 * $E  * sin($Mr - 2*$MPr)
          -    2602 * sin(2*$Dr - $MPr + 2*$Fr)
          +    2390 * $E  * sin(2*$Dr - $Mr - 2*$MPr)
          -    2348 * sin($Dr + $MPr)
          +    2236 * $E*$E * sin(2*$Dr - 2*$Mr)
          -    2120 * $E  * sin($Mr + 2*$MPr)
          -    2069 * $E*$E * sin(2*$Mr)
          +    2048 * $E*$E * sin(2*$Dr - 2*$Mr - $MPr)
          -    1773 * sin(2*$Dr + $MPr - 2*$Fr)
          -    1595 * sin(2*$Dr + 2*$Fr)
          +    1215 * $E  * sin(4*$Dr - $Mr - $MPr)
          -    1110 * sin(2*$MPr + 2*$Fr)
          -     892 * sin(3*$Dr - $MPr)
          -     810 * $E  * sin(2*$Dr + $Mr + $MPr)
          +     759 * $E  * sin(4*$Dr - $Mr - 2*$MPr)
          -     713 * $E*$E * sin(2*$Mr - $MPr)
          -     700 * $E*$E * sin(2*$Dr + 2*$Mr - $MPr);

        $lon = fmod($L0 + $SL / 1_000_000.0, 360.0);
        if ($lon < 0) $lon += 360.0;
        return $lon;
    }

    /** Sidereal longitude = tropical - ayanamsa */
    private static function moonLongitudeSidereal(float $jd): float
    {
        $sid = self::moonLongitudeTropical($jd) - self::ayanamsa($jd);
        $sid = fmod($sid, 360.0);
        if ($sid < 0) $sid += 360.0;
        return $sid;
    }

    // ── Moon Latitude (Meeus Ch. 47) ──────────────────────────────────────────

    private static function moonLatitude(float $jd): float
    {
        $T = ($jd - 2451545.0) / 36525.0;

        $MP = 134.9633964 + 477198.8675055 * $T + 0.0087414 * $T * $T;
        $D  = 297.8501921 + 445267.1114034 * $T - 0.0018819 * $T * $T;
        $F  = 93.2720950  + 483202.0175233 * $T - 0.0036539 * $T * $T;
        $M  = 357.5291092 + 35999.0502909  * $T - 0.0001536 * $T * $T;
        $E  = 1.0 - 0.002516 * $T - 0.0000074 * $T * $T;

        $MPr = deg2rad(fmod($MP + 720000, 360));
        $Dr  = deg2rad(fmod($D  + 720000, 360));
        $Fr  = deg2rad(fmod($F  + 720000, 360));
        $Mr  = deg2rad(fmod($M  + 720000, 360));

        $SB =
            5128122 * sin($Fr)
          +  280602 * sin($MPr + $Fr)
          +  277693 * sin($MPr - $Fr)
          +  173237 * sin(2*$Dr - $Fr)
          +   55413 * sin(2*$Dr - $MPr + $Fr)
          +   46271 * sin(2*$Dr - $MPr - $Fr)
          +   32573 * sin(2*$Dr + $Fr)
          +   17198 * sin(2*$MPr + $Fr)
          +    9266 * sin(2*$Dr + $MPr - $Fr)
          +    8822 * sin(2*$MPr - $Fr)
          +    8216 * $E * sin(2*$Dr - $Mr - $Fr)
          +    4324 * sin(2*$Dr - 2*$MPr - $Fr)
          +    4200 * sin(2*$Dr + $MPr + $Fr)
          -    3359 * $E * sin(2*$Dr + $Mr - $Fr)
          +    2463 * $E * sin(2*$Dr - $Mr - $MPr + $Fr)
          +    2211 * $E * sin(2*$Dr - $Mr + $Fr)
          +    2065 * $E * sin(2*$Dr - $Mr - $MPr - $Fr)
          -    1870 * $E * sin($Mr - $MPr - $Fr)
          +    1828 * sin(4*$Dr - $MPr - $Fr)
          -    1794 * $E * sin($Mr + $Fr)
          -    1749 * sin(3*$Fr)
          -    1565 * $E * sin($Mr - $MPr + $Fr)
          -    1491 * sin($Dr + $Fr)
          -    1475 * $E * sin($Mr + $MPr + $Fr)
          -    1410 * $E * sin($Mr + $MPr - $Fr)
          -    1344 * $E * sin($Mr - $Fr)
          -    1335 * sin($Dr - $Fr)
          +    1107 * sin(3*$MPr + $Fr)
          +    1021 * sin(4*$Dr - $Fr)
          +     833 * sin(4*$Dr - $MPr + $Fr);

        return $SB / 1_000_000.0; // degrees
    }

    // ── Moon Declination ──────────────────────────────────────────────────────

    private static function moonDeclination(float $jd): float
    {
        $T   = ($jd - 2451545.0) / 36525.0;
        $eps = 23.439291111 - 0.013004167 * $T;  // obliquity

        $lon = self::moonLongitudeTropical($jd);
        $lat = self::moonLatitude($jd);

        $sinDec = sin(deg2rad($lat)) * cos(deg2rad($eps))
                + cos(deg2rad($lat)) * sin(deg2rad($eps)) * sin(deg2rad($lon));

        return rad2deg(asin(max(-1.0, min(1.0, $sinDec))));
    }

    // ── Moon Distance (km) ────────────────────────────────────────────────────

    private static function moonDistance(float $jd): float
    {
        $T = ($jd - 2451545.0) / 36525.0;

        $MP = 134.9633964 + 477198.8675055 * $T;
        $D  = 297.8501921 + 445267.1114034 * $T;
        $M  = 357.5291092 + 35999.0502909  * $T;
        $F  = 93.2720950  + 483202.0175233 * $T;
        $E  = 1.0 - 0.002516 * $T - 0.0000074 * $T * $T;

        $MPr = deg2rad(fmod($MP + 720000, 360));
        $Dr  = deg2rad(fmod($D  + 720000, 360));
        $Mr  = deg2rad(fmod($M  + 720000, 360));
        $Fr  = deg2rad(fmod($F  + 720000, 360));

        $SR =
            -20905355 * cos($MPr)
          -  3699111 * cos(2*$Dr - $MPr)
          -  2955968 * cos(2*$Dr)
          -   569925 * cos(2*$MPr)
          +    48888 * $E  * cos($Mr)
          -     3149 * cos(2*$Fr)
          +   246158 * cos(2*$Dr - 2*$MPr)
          -   152138 * $E  * cos(2*$Dr - $Mr - $MPr)
          -   170733 * cos(2*$Dr + $MPr)
          -   204586 * $E  * cos(2*$Dr - $Mr)
          -   129620 * $E  * cos($Mr - $MPr)
          +   108743 * cos($Dr)
          +   104755 * $E  * cos($Mr + $MPr)
          +    10321 * cos(2*$Dr - 2*$Fr)
          +    79661 * cos($MPr - 2*$Fr)
          -    34782 * cos(4*$Dr - $MPr)
          -    23210 * cos(3*$MPr)
          -    21636 * cos(4*$Dr - 2*$MPr)
          +    24208 * $E  * cos(2*$Dr + $Mr - $MPr)
          +    30824 * $E  * cos(2*$Dr + $Mr)
          -     8379 * cos($Dr - $MPr)
          -    16675 * $E  * cos($Dr + $Mr)
          -    12831 * $E  * cos(2*$Dr - $Mr + $MPr);

        return 385000.56 + $SR / 1000.0;
    }

    // ── Zodiac sign from sidereal longitude ───────────────────────────────────

    private static function signFromLongitude(float $lon): array
    {
        $lon = fmod($lon, 360.0);
        if ($lon < 0) $lon += 360.0;
        return self::SIGNS[(int) ($lon / 30.0) % 12];
    }
}
