<?php
// Type emoji map
$typeEmoji = [
    'olive_tree'  => '🫒',
    'tree'        => '🌳',
    'vine'        => '🍇',
    'almond_tree' => '🌰',
    'garden'      => '🌿',
    'zone'        => '🛖',
    'orchard'     => '🏕',
    'bed'         => '🌱',
    'line'        => '〰️',
    'prep_zone'   => '🟫',
    'mobile_coop' => '🐓',
    'building'    => '🏠',
    'water_point' => '💧',
];

$totalItems = 0;
foreach ($itemCounts as $c) { $totalItems += (int)$c['cnt']; }
$overdueCount  = count($overdueReminders);
$upcomingCount = count($upcomingReminders);

// Build monthly harvest totals (sum all units by month, pick the primary one)
$monthlyTotals = array_fill(1, 12, 0);
$monthlyUnit   = '';
foreach ($monthlyHarvest as $row) {
    $mo = (int)$row['mo'];
    $monthlyTotals[$mo] = ($monthlyTotals[$mo] ?? 0) + (float)$row['total'];
    if (!$monthlyUnit) $monthlyUnit = $row['unit'];
}
$maxMonthly = max($monthlyTotals) ?: 1;
$hasHarvest = max($monthlyTotals) > 0;
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$currentYear = date('Y');

// ── Lunar / biodynamic calendar ─────────────────────────────────────────
function rooted_moon_info(int $ts): array {
    $knownNewMoon = 947182440; // Jan 6, 2000 18:14 UTC (Unix)
    $synodic = 29.53059;
    $phase = fmod(($ts - $knownNewMoon) / 86400.0, $synodic);
    if ($phase < 0) $phase += $synodic;

    if ($phase < 1.85)      $pn = 'New Moon';
    elseif ($phase < 7.38)  $pn = 'Waxing Crescent';
    elseif ($phase < 9.22)  $pn = 'First Quarter';
    elseif ($phase < 14.77) $pn = 'Waxing Gibbous';
    elseif ($phase < 16.61) $pn = 'Full Moon';
    elseif ($phase < 22.15) $pn = 'Waning Gibbous';
    elseif ($phase < 23.99) $pn = 'Last Quarter';
    else                    $pn = 'Waning Crescent';

    $pe = match($pn) {
        'New Moon' => '🌑', 'Waxing Crescent' => '🌒', 'First Quarter' => '🌓',
        'Waxing Gibbous' => '🌔', 'Full Moon' => '🌕', 'Waning Gibbous' => '🌖',
        'Last Quarter' => '🌗', default => '🌘',
    };

    // Moon longitude (J2000.0 = Jan 1, 2000 12:00 UTC = 946728000)
    $d = ($ts - 946728000) / 86400.0;
    $L  = 218.316 + 13.176396 * $d;
    $M  = deg2rad(134.963 + 13.064993 * $d);
    $D  = deg2rad(297.850 + 12.190749 * $d);
    $Ms = deg2rad(357.529 +  0.985600 * $d);
    $lon = fmod($L + 6.289*sin($M) - 1.274*sin(2*$D-$M) + 0.658*sin(2*$D)
                   - 0.186*sin($Ms) - 0.059*sin(2*$M-2*$D) + 0.053*sin($M+2*$D), 360.0);
    if ($lon < 0) $lon += 360.0;

    $signs = ['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpio','Sagittarius','Capricorn','Aquarius','Pisces'];
    $sign  = $signs[(int)floor($lon / 30)];
    $elem  = match(true) {
        in_array($sign, ['Aries','Leo','Sagittarius'])       => 'Fire',
        in_array($sign, ['Taurus','Virgo','Capricorn'])      => 'Earth',
        in_array($sign, ['Gemini','Libra','Aquarius'])       => 'Air',
        default                                               => 'Water',
    };
    $dt = match($elem) {
        'Fire'  => ['Fruit/Seed', '🍎', 'Best for fruits, seeds, harvesting'],
        'Earth' => ['Root',       '🥕', 'Plant or harvest root crops'],
        'Air'   => ['Flower',     '🌸', 'Tend flowers, light pruning'],
        'Water' => ['Leaf',       '🥬', 'Leafy greens, watering, transplanting'],
    };
    return ['phase'=>$phase,'phaseName'=>$pn,'phaseEmoji'=>$pe,'sign'=>$sign,
            'element'=>$elem,'waxing'=>$phase<14.77,'dayType'=>$dt[0],'dayEmoji'=>$dt[1],'dayDesc'=>$dt[2]];
}
$moonToday = rooted_moon_info((int)date('U', mktime(12,0,0,(int)date('n'),(int)date('j'),(int)date('Y'))));
$moonWeek  = [];
for ($i = 0; $i < 7; $i++) {
    $moonWeek[] = rooted_moon_info((int)mktime(12,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
}
?>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- ============================================================
     WELCOME + QUOTE + WEATHER (seamless)
     ============================================================ -->
<div class="dash-welcome">
    <div class="dash-welcome-greeting">
        <?php if (!empty($ownerName)): ?>Ciao, <?= e($ownerName) ?>!<?php else: ?>Benvenuto!<?php endif; ?>
    </div>
    <?php if (!empty($quote)): ?>
    <div class="dash-welcome-quote">
        "<?= e(mb_strimwidth($quote['text'], 0, 160, '…')) ?>"<?php if (!empty($quote['author'])): ?> — <?= e($quote['author']) ?><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($weather)): ?>
    <div class="dash-weather-widget">
        <div class="dash-weather-main">
            <span class="dash-weather-icon"><?= $weather['icon'] ?></span>
            <div class="dash-weather-temps">
                <span class="dash-weather-temp"><?= $weather['temp'] ?>°</span>
                <span class="dash-weather-feels">Feels <?= $weather['feels'] ?>°</span>
            </div>
            <div class="dash-weather-center">
                <span class="dash-weather-desc"><?= e($weather['desc']) ?></span>
                <?php if (!empty($weatherCity)): ?>
                <span class="dash-weather-city">📍 <?= e($weatherCity) ?></span>
                <?php endif; ?>
            </div>
            <div class="dash-weather-meta">
                <span class="dash-weather-detail">💧 <?= $weather['humidity'] ?>%</span>
                <span class="dash-weather-detail">🌡 <?= $weather['pressure'] ?> hPa</span>
                <?php if (!empty($weather['sunset'])): ?>
                <span class="dash-weather-detail">🌅 <?= e($weather['sunset']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($weather['hours']) || !empty($weather['daily'])): ?>
        <div class="dash-weather-hours">
            <?php foreach (($weather['hours'] ?? []) as $h): ?>
            <div class="dash-weather-hour">
                <span class="dash-weather-hour-time"><?= e($h['time']) ?></span>
                <span class="dash-weather-hour-icon"><?= $h['icon'] ?></span>
                <span class="dash-weather-hour-temp"><?= $h['temp'] ?>°</span>
            </div>
            <?php endforeach; ?>
            <?php if (!empty($weather['hours']) && !empty($weather['daily'])): ?>
            <div class="dash-weather-hours-divider"></div>
            <?php endif; ?>
            <?php foreach (($weather['daily'] ?? []) as $d): ?>
            <div class="dash-weather-hour dash-weather-day">
                <span class="dash-weather-hour-time"><?= e($d['label']) ?></span>
                <span class="dash-weather-hour-icon"><?= $d['icon'] ?></span>
                <span class="dash-weather-hour-temp"><?= $d['max'] ?>°</span>
                <span class="dash-weather-day-min"><?= $d['min'] ?>°</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($forecastUrl)): ?>
        <a href="<?= e($forecastUrl) ?>" target="_blank" rel="noopener" class="dash-weather-forecast-link">Full Forecast →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     BIODYNAMIC NOW PANEL
     ============================================================ -->
<?php
// Actionable advice based on organ + ascending/descending + anomaly
$_bioAdvice = '';
$_bioCrops  = '';
if (!empty($bioNow)) {
    $_organ = $bioNow['organ'] ?? 'Root';
    $_desc  = $bioNow['is_descending'] ?? false;
    $_anom  = $bioNow['is_anomaly']    ?? false;
    if ($_anom) {
        $_bioAdvice = 'Avoid all major garden work today — lunar node or apse period.';
        $_bioCrops  = 'Rest, observe, plan. Resume after the anomaly passes.';
    } elseif ($_organ === 'Root' && $_desc) {
        $_bioAdvice = 'Sow &amp; plant root crops.';
        $_bioCrops  = 'Carrots · Beets · Garlic · Onions · Potatoes · Radishes';
    } elseif ($_organ === 'Root' && !$_desc) {
        $_bioAdvice = 'Harvest root crops — best flavour &amp; storage.';
        $_bioCrops  = 'Carrots · Beets · Garlic · Onions · Potatoes · Radishes';
    } elseif ($_organ === 'Leaf' && $_desc) {
        $_bioAdvice = 'Transplant seedlings, water &amp; feed leafy greens.';
        $_bioCrops  = 'Lettuce · Spinach · Cabbage · Kale · Chard · Herbs';
    } elseif ($_organ === 'Leaf' && !$_desc) {
        $_bioAdvice = 'Harvest leafy greens — crisp &amp; full of vitality.';
        $_bioCrops  = 'Lettuce · Spinach · Cabbage · Kale · Chard · Herbs';
    } elseif ($_organ === 'Flower' && $_desc) {
        $_bioAdvice = 'Sow flowering plants, light pruning for fragrance.';
        $_bioCrops  = 'Roses · Lavender · Chamomile · Sunflowers · Broccoli · Cauliflower';
    } elseif ($_organ === 'Flower' && !$_desc) {
        $_bioAdvice = 'Harvest flowers for drying, cutting, or distillation.';
        $_bioCrops  = 'Roses · Lavender · Chamomile · Sunflowers · Broccoli · Cauliflower';
    } elseif ($_organ === 'Fruit' && $_desc) {
        $_bioAdvice = 'Sow or plant fruiting crops &amp; trees.';
        $_bioCrops  = 'Tomatoes · Peppers · Zucchini · Olives · Almonds · Grapes · Beans';
    } else {
        $_bioAdvice = 'Harvest fruits &amp; seeds — peak ripeness.';
        $_bioCrops  = 'Tomatoes · Peppers · Zucchini · Olives · Almonds · Grapes · Beans';
    }
}
?>
<?php
// Advice lookup helper
function _bioAdvice(string $organ, bool $desc, bool $anom): array {
    if ($anom) return ['Avoid garden work — anomaly period', 'Rest, observe, plan.', '⚠️'];
    $map = [
        'Root_d'   => ['Sow &amp; plant root crops',            'Carrots · Beets · Garlic · Onions · Potatoes',          '🥕'],
        'Root_a'   => ['Harvest root crops — best flavour',     'Carrots · Beets · Garlic · Onions · Potatoes',          '🥕'],
        'Leaf_d'   => ['Transplant &amp; water leafy greens',   'Lettuce · Spinach · Cabbage · Kale · Herbs',            '🥬'],
        'Leaf_a'   => ['Harvest leafy greens — full of life',   'Lettuce · Spinach · Cabbage · Kale · Herbs',            '🥬'],
        'Flower_d' => ['Sow flowers, light pruning',            'Roses · Lavender · Chamomile · Broccoli · Cauliflower', '🌸'],
        'Flower_a' => ['Harvest flowers for drying &amp; cutting','Roses · Lavender · Chamomile · Broccoli · Cauliflower','🌸'],
        'Fruit_d'  => ['Sow &amp; plant fruiting crops',        'Tomatoes · Peppers · Olives · Almonds · Grapes · Beans','🍎'],
        'Fruit_a'  => ['Harvest fruits — peak ripeness',        'Tomatoes · Peppers · Olives · Almonds · Grapes · Beans','🍎'],
    ];
    $k = $organ . ($desc ? '_d' : '_a');
    return $map[$k] ?? ['Garden work', '', '🌿'];
}
?>
<section class="dash-section lunar-section" style="padding-bottom:var(--spacing-3)">
    <div class="lunar-section-head" style="align-items:center">
        <span class="lunar-section-title">🌙 Garden — Right Now</span>
        <div style="display:flex;align-items:center;gap:8px;margin-left:auto">
            <button onclick="document.getElementById('bioInfoModal').style.display='flex'"
                    style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:50%;width:26px;height:26px;color:#fff;font-size:.75rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1"
                    title="How does this work?">ⓘ</button>
            <a href="<?= url('/garden/biodynamic') ?>" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:700;color:#fff;text-decoration:none;white-space:nowrap">Full Calendar →</a>
        </div>
    </div>

    <?php if (!empty($bioSegments)): ?>
    <!-- Next 6 hours segments (grouped by organ) -->
    <div style="display:flex;gap:8px;margin-bottom:var(--spacing-3);flex-wrap:wrap">
        <?php foreach ($bioSegments as $seg):
            [$adv, $crops, $segEmoji] = _bioAdvice($seg['organ'], $seg['is_descending'], $seg['is_anomaly']);
            $segBg    = $seg['is_anomaly'] ? '#fef2f2' : (\App\Support\BiodynamicCalendar::ORGAN_BG[$seg['organ']] ?? '#f0fdf4');
            $segColor = $seg['is_anomaly'] ? '#dc2626' : (\App\Support\BiodynamicCalendar::ORGAN_COLOR[$seg['organ']] ?? '#15803d');
            $timeRange = $seg['_count'] === 1 ? $seg['_from'] : $seg['_from'] . '–' . $seg['_to'];
        ?>
        <div style="flex:1;min-width:140px;background:<?= $segBg ?>;border-radius:var(--radius);padding:12px;<?= count($bioSegments)===1?'width:100%':'' ?>">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span style="font-size:1.6rem"><?= $segEmoji ?></span>
                <div>
                    <div style="font-weight:700;font-size:.85rem;color:<?= $segColor ?>"><?= $seg['organ'] ?> <?= $seg['is_descending'] ? '↓' : '↑' ?></div>
                    <div style="font-size:.68rem;color:rgba(0,0,0,0.4)"><?= $timeRange ?> · <?= $seg['name'] ?></div>
                </div>
            </div>
            <div style="font-size:.78rem;color:<?= $segColor ?>;font-weight:600"><?= $adv ?></div>
            <?php if ($crops): ?>
            <div style="font-size:.68rem;color:rgba(0,0,0,0.45);margin-top:3px"><?= $crops ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (!empty($bioNow)): ?>
    <!-- Fallback: current now card -->
    <?php [$_adv,$_crops,$_segEmoji] = _bioAdvice($bioNow['organ'],$bioNow['is_descending'],$bioNow['is_anomaly']); ?>
    <div style="background:<?= \App\Support\BiodynamicCalendar::ORGAN_BG[$bioNow['organ']] ?? '#f0fdf4' ?>;border-radius:var(--radius);padding:14px;margin-bottom:var(--spacing-3)">
        <div style="font-weight:700;font-size:.95rem;color:<?= \App\Support\BiodynamicCalendar::ORGAN_COLOR[$bioNow['organ']] ?? '#15803d' ?>"><?= $_segEmoji ?> <?= $_adv ?></div>
        <div style="font-size:.75rem;color:rgba(0,0,0,0.45);margin-top:3px"><?= $_crops ?></div>
    </div>
    <?php endif; ?>

    <!-- Moon phase + 7-day strip -->
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <span style="font-size:1.1rem"><?= $moonToday['phaseEmoji'] ?></span>
        <span style="font-size:.78rem;color:var(--color-text-muted);font-weight:500"><?= $moonToday['phaseName'] ?> · <?= $moonToday['sign'] ?></span>
    </div>
    <div class="lunar-week bio-week" style="margin-bottom:0">
        <?php foreach ($bioWeek as $i => $bw):
            $dayLabel = $i === 0 ? 'Today' : date('D', mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
            $dayNum   = date('j',   mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
            $moonInfo = $moonWeek[$i] ?? [];
            $bgStyle  = 'background:' . (\App\Support\BiodynamicCalendar::ORGAN_BG[$bw['organ']] ?? '#f0fdf4');
        ?>
        <div class="lunar-day <?= $i===0?'lunar-day--today':'' ?>" style="<?= $bgStyle ?>">
            <div class="lunar-day-label" style="color:rgba(30,50,30,0.55)"><?= $dayLabel ?></div>
            <div class="lunar-day-num"   style="color:#1a2e1c"><?= $dayNum ?></div>
            <div class="lunar-day-moon"><?= $moonInfo['phaseEmoji'] ?? '🌑' ?></div>
            <div class="lunar-day-emoji"><?= \App\Support\BiodynamicCalendar::ORGAN_EMOJI[$bw['organ']] ?? '🌿' ?></div>
            <div class="lunar-day-type" style="color:<?= \App\Support\BiodynamicCalendar::ORGAN_COLOR[$bw['organ']] ?? '#15803d' ?>;font-size:.6rem"><?= $bw['organ'] ?><?= $bw['is_anomaly'] ? ' ⚠' : '' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     GARDEN PLANTING WIDGET
     ============================================================ -->
<?php
// Build upcoming planting opportunities from bioWeek (descending moon days by organ)
$_gardenDays = [];
foreach ($bioWeek as $i => $bw) {
    if ($bw['is_anomaly']) continue;
    $dayLabel = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D d', mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y'))));
    [$_adv2,$_crops2] = _bioAdvice($bw['organ'], $bw['is_descending'], false);
    $_gardenDays[] = [
        'label'  => $dayLabel,
        'organ'  => $bw['organ'],
        'desc'   => $bw['is_descending'],
        'advice' => $_adv2,
        'crops'  => $_crops2,
        'emoji'  => \App\Support\BiodynamicCalendar::ORGAN_EMOJI[$bw['organ']] ?? '🌿',
        'color'  => \App\Support\BiodynamicCalendar::ORGAN_COLOR[$bw['organ']] ?? '#15803d',
        'bg'     => \App\Support\BiodynamicCalendar::ORGAN_BG[$bw['organ']] ?? '#f0fdf4',
    ];
}
?>
<?php if (!empty($_gardenDays)): ?>
<section class="dash-widget" style="margin-bottom:var(--spacing-4)">
    <div class="dash-widget-header">
        <span>🌱 What to do in the Garden</span>
        <a href="<?= url('/garden') ?>" class="dash-widget-link">Garden →</a>
    </div>
    <div style="display:flex;overflow-x:auto;gap:8px;padding:12px;scrollbar-width:thin">
        <?php foreach ($_gardenDays as $gd): ?>
        <div style="flex-shrink:0;min-width:130px;background:<?= $gd['bg'] ?>;border-radius:var(--radius);padding:10px 12px">
            <div style="font-size:.68rem;font-weight:700;color:rgba(0,0,0,0.4);margin-bottom:4px;text-transform:uppercase"><?= $gd['label'] ?></div>
            <div style="font-size:1.4rem"><?= $gd['emoji'] ?></div>
            <div style="font-size:.78rem;font-weight:700;color:<?= $gd['color'] ?>;margin-top:3px"><?= $gd['organ'] ?></div>
            <div style="font-size:.68rem;color:rgba(0,0,0,0.45);margin-top:2px;line-height:1.3"><?= $gd['desc'] ? 'Sow &amp; plant' : 'Harvest' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="padding:0 12px 12px">
        <a href="<?= url('/garden/biodynamic') ?>" style="font-size:.75rem;color:var(--color-primary);font-weight:600;text-decoration:none">View full Biodynamic Calendar →</a>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     WHAT TO IRRIGATE TODAY
     ============================================================ -->
<?php if (!empty($todayIrrigation)): ?>
<section class="dash-widget" style="margin-bottom:var(--spacing-4)">
    <div class="dash-widget-header">
        <span>💧 Irrigate Today</span>
        <a href="<?= url('/irrigation') ?>" class="dash-widget-link">All plans →</a>
    </div>
    <div class="dash-widget-body" style="padding:0">
        <?php foreach ($todayIrrigation as $ip): ?>
        <div class="irr-dash-row" id="irrRow<?= $ip['id'] ?>" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--color-border)">
            <span style="font-size:1.2rem">💧</span>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:.9rem"><?= e($ip['item_name']) ?></div>
                <div style="font-size:.75rem;color:var(--color-text-muted)">
                    <?= e(\App\Controllers\IrrigationController::intervalLabel($ip['interval_type'])) ?>
                    <?php if (!empty($ip['quantity_liters'])): ?>· <?= (float)$ip['quantity_liters'] ?>L<?php endif; ?>
                    <?php if (!empty($ip['notes'])): ?>· <?= e(mb_strimwidth($ip['notes'], 0, 40, '…')) ?><?php endif; ?>
                </div>
            </div>
            <button
                type="button"
                class="btn btn-sm"
                style="background:#16a34a;color:#fff;flex-shrink:0"
                onclick="irrMarkDone(<?= $ip['id'] ?>, this)"
            >✓ Done</button>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<script>
function irrMarkDone(id, btn) {
    btn.disabled = true;
    btn.textContent = '…';
    fetch('<?= url('/irrigation/') ?>' + id + '/done', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_token=<?= urlencode(\App\Support\CSRF::getToken()) ?>'
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.success) {
            var row = document.getElementById('irrRow' + id);
            row.style.opacity = '0.4';
            row.style.pointerEvents = 'none';
            btn.textContent = '✓';
        } else {
            btn.disabled = false;
            btn.textContent = '✓ Done';
        }
    }).catch(function(){
        btn.disabled = false;
        btn.textContent = '✓ Done';
    });
}
</script>
<?php endif; ?>

<!-- ============================================================
     REMINDERS WIDGET (merged overdue + upcoming)
     ============================================================ -->
<?php
$_allReminders = array_merge(
    array_map(fn($r) => $r + ['_overdue' => true],  $overdueReminders),
    array_map(fn($r) => $r + ['_overdue' => false], $upcomingReminders)
);
usort($_allReminders, fn($a, $b) => strtotime($a['due_at']) <=> strtotime($b['due_at']));
$_remFirst5  = array_slice($_allReminders, 0, 5);
$_remRest    = array_slice($_allReminders, 5);
$_csrfRem    = \App\Support\CSRF::getToken();
?>
<?php if (!empty($_allReminders)): ?>
<section class="dash-widget dash-upcoming-widget" id="dashRemWidget">
    <div class="dash-widget-header">
        <span>🔔 Upcoming Reminders</span>
        <div style="display:flex;gap:8px;align-items:center">
            <?php if (count($_allReminders) > 5): ?>
            <button class="dash-widget-link" style="background:none;border:none;cursor:pointer;padding:0" onclick="document.getElementById('allRemModal').classList.add('open')">All <?= count($_allReminders) ?> &rarr;</button>
            <?php else: ?>
            <a href="<?= url('/reminders') ?>" class="dash-widget-link">All &rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="dash-widget-body" style="padding:0">
        <div class="drem-list" id="dremList">
        <?php foreach ($_remFirst5 as $r):
            $isOD = $r['_overdue'];
            $rid  = (int)$r['id'];
        ?>
        <div class="drem-row <?= $isOD ? 'drem-row--overdue' : '' ?>" id="drem-<?= $rid ?>">
            <div class="drem-body">
                <div class="drem-title"><?= $isOD ? '<span class="drem-overdue-dot">●</span> ' : '' ?><?= e($r['title']) ?></div>
                <div class="drem-meta">
                    <?= $isOD ? '<span style="color:#c0392b;font-weight:700">⚠ ' . e(date('d M', strtotime($r['due_at']))) . '</span>' : e(date('d M Y', strtotime($r['due_at']))) ?>
                    <?php if (!empty($r['item_name'])): ?> · <span class="drem-item"><?= e($r['item_name']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="drem-actions">
                <button class="drem-btn drem-btn--done"  title="Done"    onclick="remAction(<?= $rid ?>,'complete','<?= e($_csrfRem) ?>')">✓</button>
                <button class="drem-btn drem-btn--snooze" title="+1 Day"  onclick="remAction(<?= $rid ?>,'snooze1','<?= e($_csrfRem) ?>')">+1d</button>
                <button class="drem-btn drem-btn--snooze" title="+1 Week" onclick="remAction(<?= $rid ?>,'snooze7','<?= e($_csrfRem) ?>')">+1w</button>
                <button class="drem-btn drem-btn--dismiss" title="Dismiss" onclick="remAction(<?= $rid ?>,'dismiss','<?= e($_csrfRem) ?>')">✕</button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- All Reminders Modal -->
<?php if (count($_allReminders) > 5): ?>
<div class="drem-modal-backdrop" id="allRemModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="drem-modal">
        <div class="drem-modal-head">
            <span>🔔 All Reminders</span>
            <button onclick="document.getElementById('allRemModal').classList.remove('open')" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--color-text-muted)">✕</button>
        </div>
        <div class="drem-modal-body">
        <?php foreach ($_allReminders as $r):
            $isOD = $r['_overdue'];
            $rid  = (int)$r['id'];
        ?>
        <div class="drem-row <?= $isOD ? 'drem-row--overdue' : '' ?>" id="drem-m-<?= $rid ?>">
            <div class="drem-body">
                <div class="drem-title"><?= $isOD ? '<span class="drem-overdue-dot">●</span> ' : '' ?><?= e($r['title']) ?></div>
                <div class="drem-meta">
                    <?= $isOD ? '<span style="color:#c0392b;font-weight:700">⚠ ' . e(date('d M', strtotime($r['due_at']))) . '</span>' : e(date('d M Y', strtotime($r['due_at']))) ?>
                    <?php if (!empty($r['item_name'])): ?> · <span class="drem-item"><?= e($r['item_name']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="drem-actions">
                <button class="drem-btn drem-btn--done"   title="Done"    onclick="remAction(<?= $rid ?>,'complete','<?= e($_csrfRem) ?>',true)">✓</button>
                <button class="drem-btn drem-btn--snooze" title="+1 Day"  onclick="remAction(<?= $rid ?>,'snooze1','<?= e($_csrfRem) ?>',true)">+1d</button>
                <button class="drem-btn drem-btn--snooze" title="+1 Week" onclick="remAction(<?= $rid ?>,'snooze7','<?= e($_csrfRem) ?>',true)">+1w</button>
                <button class="drem-btn drem-btn--dismiss" title="Dismiss" onclick="remAction(<?= $rid ?>,'dismiss','<?= e($_csrfRem) ?>',true)">✕</button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--color-border)">
            <a href="<?= url('/reminders') ?>" class="btn btn-ghost btn-sm btn-full">Manage all reminders →</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function remAction(id, action, token, inModal) {
    var url, body;
    if (action === 'snooze1' || action === 'snooze7') {
        url  = '<?= url('/reminders/') ?>' + id + '/snooze';
        body = '_token=' + encodeURIComponent(token) + '&days=' + (action === 'snooze7' ? 7 : 1) + '&_ajax=1';
    } else {
        url  = '<?= url('/reminders/') ?>' + id + '/' + action;
        body = '_token=' + encodeURIComponent(token) + '&_ajax=1';
    }
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: body })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            // Remove from both the widget list and modal list
            var rowW = document.getElementById('drem-' + id);
            var rowM = document.getElementById('drem-m-' + id);
            if (action === 'snooze1' || action === 'snooze7') {
                // Update date display
                if (data.due_at) {
                    var d = new Date(data.due_at.replace(' ','T'));
                    var label = d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
                    [rowW, rowM].forEach(function(row) {
                        if (!row) return;
                        var meta = row.querySelector('.drem-meta');
                        if (meta) {
                            var item = meta.querySelector('.drem-item');
                            meta.innerHTML = label + (item ? ' · <span class="drem-item">' + item.textContent + '</span>' : '');
                        }
                        row.classList.remove('drem-row--overdue');
                        var dot = row.querySelector('.drem-overdue-dot');
                        if (dot) dot.remove();
                    });
                }
            } else {
                [rowW, rowM].forEach(function(row) {
                    if (row) { row.style.transition='opacity .3s'; row.style.opacity='0'; setTimeout(function(){ row.remove(); },300); }
                });
            }
        });
}
</script>

<style>
.drem-list { display:flex;flex-direction:column; }
.drem-row {
    display:flex;align-items:center;gap:8px;
    padding:9px 14px;border-bottom:1px solid var(--color-border);
}
.drem-row:last-child { border-bottom:none; }
.drem-row--overdue { background:#fff5f5; }
.drem-body { flex:1;min-width:0; }
.drem-title { font-size:.875rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.drem-overdue-dot { color:#c0392b;font-size:.6rem;vertical-align:middle; }
.drem-meta { font-size:.7rem;color:var(--color-text-muted);margin-top:1px; }
.drem-item { font-style:italic; }
.drem-actions { display:flex;gap:3px;flex-shrink:0 }
.drem-btn {
    height:26px;border:none;border-radius:5px;cursor:pointer;font-size:.72rem;font-weight:700;
    padding:0 7px;line-height:26px;transition:opacity .15s;white-space:nowrap;
}
.drem-btn--done    { background:var(--color-primary-soft);color:var(--color-primary); }
.drem-btn--done:hover { background:var(--color-primary);color:#fff; }
.drem-btn--snooze  { background:rgba(0,0,0,.06);color:var(--color-text-muted); }
.drem-btn--snooze:hover { background:rgba(0,0,0,.13); }
.drem-btn--dismiss { background:rgba(0,0,0,.06);color:#c0392b; }
.drem-btn--dismiss:hover { background:#ffd5d5; }
/* All Reminders Modal */
.drem-modal-backdrop {
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000;
    align-items:center;justify-content:center;
}
.drem-modal-backdrop.open { display:flex; }
.drem-modal {
    background:var(--color-surface);border-radius:18px;width:min(540px,92vw);
    max-height:80vh;display:flex;flex-direction:column;overflow:hidden;
    box-shadow:0 8px 40px rgba(0,0,0,.25);
}
.drem-modal-head {
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 18px;border-bottom:1px solid var(--color-border);
    font-weight:700;font-size:1rem;
}
.drem-modal-body { overflow-y:auto;flex:1; }
</style>
<?php endif; ?>

<!-- ============================================================
     TASKS WIDGET
     ============================================================ -->
<?php if (!empty($dashTasks) || !empty($dashAchats)): ?>
<section class="dash-widget dash-tasks-widget" style="margin-bottom:var(--spacing-5)">
    <div class="dash-widget-header">
        <span>✅ Tasks</span>
        <a href="<?= url('/tasks') ?>" class="dash-widget-link">Go to Tasks →</a>
    </div>
<?php $dashCsrf = e(\App\Support\CSRF::getToken()); ?>
    <div class="dash-tasks-layout">
        <!-- Left: tab buttons stacked -->
        <div class="dash-tasks-tabs">
            <button class="dash-tasks-tab active" onclick="switchDashTab('todos', this)">
                ✅<span>To-Do</span>
            </button>
            <button class="dash-tasks-tab" onclick="switchDashTab('achats', this)">
                🛒<span>Achats</span>
            </button>
        </div>
        <!-- Right: content panels -->
        <div class="dash-tasks-content">
            <!-- To-Do panel -->
            <div id="dashTabTodos" class="dash-tasks-panel">
                <div style="display:flex;justify-content:flex-end;padding:4px 12px 0">
                    <button onclick="dashClearDone('todo')" style="font-size:.65rem;font-weight:600;color:var(--color-text-muted);background:none;border:1px solid var(--color-border);border-radius:999px;padding:2px 8px;cursor:pointer">🗑 Clear done</button>
                </div>
                <?php if (empty($dashTasks)): ?>
                <div style="padding:16px;color:var(--color-text-muted);font-size:.85rem;text-align:center">All done! 🎉</div>
                <?php else: ?>
                <?php foreach ($dashTasks as $dt):
                    $isImp = (bool)$dt['is_important'];
                    $tagHtml = '';
                    if (!empty($dt['category'])) {
                        // simple color hash
                        $h = 0; $tag = strtoupper(trim($dt['category']));
                        for ($ci = 0; $ci < strlen($tag); $ci++) $h = (($h*31)+ord($tag[$ci]))&0x7fffffff;
                        $pal = ['#2d6a4f','#4338ca','#c05621','#0369a1','#7e22ce','#0f766e','#be123c','#6b21a8','#1e40af','#854d0e','#065f46','#9d174d'];
                        $tc = $pal[$h % count($pal)];
                        $tagHtml = '<span style="background:'.$tc.';color:#fff;font-size:.6rem;font-weight:700;padding:1px 7px;border-radius:999px;text-transform:uppercase;flex-shrink:0">'.e($tag).'</span>';
                    }
                ?>
                <div class="dash-task-row <?= $isImp ? 'dash-task-row--imp' : '' ?>" id="dtRow<?= $dt['id'] ?>">
                    <button class="dash-task-chk <?= $dt['is_done'] ? 'checked' : '' ?>"
                            onclick="dtToggle(<?= $dt['id'] ?>, '<?= e(\App\Support\CSRF::getToken()) ?>', this)"
                            title="Toggle done">
                        <?= $dt['is_done'] ? '✓' : '' ?>
                    </button>
                    <div style="flex:1;min-width:0;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <?= $tagHtml ?>
                        <span style="font-size:.875rem;font-weight:<?= $isImp ? '700' : '500' ?>;color:<?= $isImp ? '#16a34a' : 'inherit' ?>" ondblclick="dashInlineEdit(<?= $dt['id'] ?>, this)"><?= e($dt['title']) ?></span>
                        <?php if ($isImp): ?><span style="font-size:.8rem" title="Today">☀️</span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <div style="padding:8px 12px;border-top:1px solid var(--color-border)">
                    <a href="<?= url('/tasks?tab=todos') ?>" style="font-size:.75rem;color:var(--color-primary);font-weight:600;text-decoration:none">See all to-dos →</a>
                </div>
            </div>
            <!-- Achats panel -->
            <div id="dashTabAchats" class="dash-tasks-panel" style="display:none">
                <div style="display:flex;justify-content:flex-end;padding:4px 12px 0">
                    <button onclick="dashClearDone('achat')" style="font-size:.65rem;font-weight:600;color:var(--color-text-muted);background:none;border:1px solid var(--color-border);border-radius:999px;padding:2px 8px;cursor:pointer">🗑 Clear done</button>
                </div>
                <?php if (empty($dashAchats)): ?>
                <div style="padding:16px;color:var(--color-text-muted);font-size:.85rem;text-align:center">Shopping list is empty.</div>
                <?php else: ?>
                <?php foreach ($dashAchats as $da):
                    $tagHtml2 = '';
                    if (!empty($da['category']) && $da['category'] !== '__none__') {
                        $h = 0; $tag = strtoupper(trim($da['category']));
                        for ($ci = 0; $ci < strlen($tag); $ci++) $h = (($h*31)+ord($tag[$ci]))&0x7fffffff;
                        $pal = ['#2d6a4f','#4338ca','#c05621','#0369a1','#7e22ce','#0f766e','#be123c','#6b21a8','#1e40af','#854d0e','#065f46','#9d174d'];
                        $tc = $pal[$h % count($pal)];
                        $tagHtml2 = '<span style="background:'.$tc.';color:#fff;font-size:.6rem;font-weight:700;padding:1px 7px;border-radius:999px;text-transform:uppercase;flex-shrink:0">'.e($tag).'</span>';
                    }
                ?>
                <div class="dash-task-row" id="daRow<?= $da['id'] ?>">
                    <button class="dash-task-chk <?= $da['is_done'] ? 'checked' : '' ?>"
                            onclick="dtToggle(<?= $da['id'] ?>, '<?= e(\App\Support\CSRF::getToken()) ?>', this)"
                            title="Toggle">
                        <?= $da['is_done'] ? '✓' : '' ?>
                    </button>
                    <div style="flex:1;min-width:0;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <?= $tagHtml2 ?>
                        <span style="font-size:.875rem" ondblclick="dashInlineEdit(<?= $da['id'] ?>, this)"><?= e($da['title']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <div style="padding:8px 12px;border-top:1px solid var(--color-border)">
                    <a href="<?= url('/tasks?tab=achats') ?>" style="font-size:.75rem;color:var(--color-primary);font-weight:600;text-decoration:none">See shopping list →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.dash-tasks-layout { display:flex; min-height:120px; }
.dash-tasks-tabs { display:flex;flex-direction:column;gap:2px;padding:8px 6px;background:var(--color-surface);border-right:1px solid var(--color-border);flex-shrink:0;min-width:72px; }
.dash-tasks-tab { background:none;border:none;cursor:pointer;padding:8px 6px;border-radius:var(--radius);font-size:.68rem;font-weight:700;color:var(--color-text-muted);display:flex;flex-direction:column;align-items:center;gap:3px;text-transform:uppercase;letter-spacing:.03em;transition:background .12s,color .12s; }
.dash-tasks-tab.active { background:var(--color-primary-soft);color:var(--color-primary); }
.dash-tasks-tab:hover:not(.active) { background:var(--color-border); }
.dash-tasks-content { flex:1;min-width:0; }
.dash-tasks-panel { display:flex;flex-direction:column; }
.dash-task-row { display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid var(--color-border); }
.dash-task-row:last-of-type { border-bottom:none; }
.dash-task-row--imp { border-left:3px solid #f59e0b;background:#fffbeb; }
.dash-task-chk { width:20px;height:20px;border-radius:5px;border:2px solid var(--color-border);background:#fff;flex-shrink:0;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;transition:border-color .12s,background .12s; }
.dash-task-chk.checked { background:var(--color-primary);border-color:var(--color-primary);color:#fff; }
</style>

<script>
function switchDashTab(tab, btn) {
    document.querySelectorAll('.dash-tasks-tab').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.dash-tasks-panel').forEach(function(p){ p.style.display='none'; });
    document.getElementById('dashTab' + tab.charAt(0).toUpperCase() + tab.slice(1)).style.display = 'flex';
    document.getElementById('dashTab' + tab.charAt(0).toUpperCase() + tab.slice(1)).style.flexDirection = 'column';
}
var DASH_CSRF = '<?= $dashCsrf ?>';
var DASH_BASE = '<?= url('/') ?>';

function dtToggle(id, token, btn) {
    fetch(DASH_BASE + 'tasks/' + id + '/toggle', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_token='+encodeURIComponent(token)
    }).then(function(r){return r.json();}).then(function(d){
        if (!d.success) return;
        var row = btn.closest('.dash-task-row');
        if (d.is_done) {
            btn.classList.add('checked'); btn.textContent='✓';
            row.style.opacity='0'; row.style.transition='opacity .4s';
            setTimeout(function(){ row.remove(); }, 420);
        } else {
            btn.classList.remove('checked'); btn.textContent='';
        }
    });
}

function dashInlineEdit(id, titleEl) {
    if (titleEl.querySelector('input')) return;
    var prev = titleEl.textContent.trim();
    var inp = document.createElement('input');
    inp.type = 'text'; inp.value = prev;
    inp.style.cssText = 'border:none;outline:none;background:transparent;width:100%;font:inherit;color:inherit;padding:0;margin:0;';
    titleEl.textContent = ''; titleEl.appendChild(inp);
    inp.focus(); inp.select();
    var saved = false;
    function save() {
        if (saved) return; saved = true;
        var val = inp.value.trim() || prev;
        titleEl.textContent = val;
        if (val === prev) return;
        fetch(DASH_BASE + 'tasks/' + id + '/rename', {
            method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'_token='+encodeURIComponent(DASH_CSRF)+'&title='+encodeURIComponent(val)
        }).then(function(r){return r.json();}).then(function(d){ if(!d.success) titleEl.textContent=prev; })
          .catch(function(){ titleEl.textContent=prev; });
    }
    inp.addEventListener('blur', save);
    inp.addEventListener('keydown', function(e) {
        if (e.key==='Enter') { e.preventDefault(); inp.removeEventListener('blur',save); save(); }
        if (e.key==='Escape') { inp.removeEventListener('blur',save); saved=true; titleEl.textContent=prev; }
    });
}

function dashClearDone(listType) {
    var label = listType === 'achat' ? 'completed achats' : 'completed tasks';
    if (!confirm('Delete all ' + label + '?')) return;
    fetch(DASH_BASE + 'tasks/clear-completed', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_token='+encodeURIComponent(DASH_CSRF)+'&list_type='+encodeURIComponent(listType)
    }).then(function(r){return r.json();}).then(function(d){
        if (!d.success) return;
        var panel = listType === 'achat' ? document.getElementById('dashTabAchats') : document.getElementById('dashTabTodos');
        if (!panel) return;
        panel.querySelectorAll('.dash-task-row').forEach(function(row){
            var chk = row.querySelector('.dash-task-chk');
            if (chk && chk.classList.contains('checked')) {
                row.style.transition='opacity .2s'; row.style.opacity='0';
                setTimeout(function(){ row.remove(); }, 220);
            }
        });
    });
}
</script>
<?php endif; ?>

<!-- ============================================================
     NEAREST TO YOU — hero section
     ============================================================ -->
<section class="nearby-hero" id="nearbySection">
    <div class="nearby-hero-head">
        <div class="nearby-hero-title">📍 Nearest to You</div>
        <button class="nearby-refresh-btn" id="nearbyRefresh" title="Refresh">↺</button>
    </div>
    <div class="nearby-status-bar" id="nearbyStatus" style="display:none">📡 Locating…</div>
    <div class="nearby-cards" id="nearbyList"></div>
    <div id="nearbyEmpty" style="display:none;text-align:center;padding:var(--spacing-6)">
        <div style="font-size:2rem;margin-bottom:var(--spacing-3)">📍</div>
        <div id="nearbyEmptyMsg" style="color:var(--color-text-muted);margin-bottom:var(--spacing-3)">Enable location to see nearby items</div>
        <button class="btn btn-primary" id="nearbyTryBtn">Detect My Location</button>
    </div>
</section>

<script>
(function () {
    var BASE = '<?= url('/') ?>';
    var GPS_ITEMS = <?= json_encode(array_map(fn($i) => [
        'id'       => (int)$i['id'],
        'name'     => $i['name'],
        'type'     => $i['type'],
        'lat'      => (float)$i['gps_lat'],
        'lng'      => (float)$i['gps_lng'],
        'photo_id' => $i['photo_id'] ? (int)$i['photo_id'] : null,
    ], $gpsItems)) ?>;

    var TYPE_EMOJI = { olive_tree:'🫒',tree:'🌳',vine:'🍇',almond_tree:'🌰',garden:'🌿',zone:'🛖',orchard:'🏕',bed:'🌱',line:'〰️',prep_zone:'🟫',mobile_coop:'🐓',building:'🏠',water_point:'💧' };
    var TYPE_COLOR = { olive_tree:'#2d6a4f',tree:'#166534',vine:'#6d28d9',almond_tree:'#92400e',garden:'#0369a1',bed:'#0369a1',orchard:'#c2410c',zone:'#4338ca',prep_zone:'#b45309',mobile_coop:'#991b1b',building:'#374151',water_point:'#0284c7' };

    function haversineM(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
    function fmtDist(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}

    function renderNearest(lat, lng) {
        document.getElementById('nearbyStatus').style.display = 'none';
        document.getElementById('nearbyEmpty').style.display  = 'none';

        var sorted = GPS_ITEMS.map(function(item) {
            return Object.assign({}, item, { dist: haversineM(lat, lng, item.lat, item.lng) });
        }).sort(function(a,b){return a.dist-b.dist;}).slice(0,5);

        var html = '';
        sorted.forEach(function(item) {
            var emoji  = TYPE_EMOJI[item.type] || '📦';
            var color  = TYPE_COLOR[item.type] || '#2d6a4f';
            var label  = item.type.replace(/_/g,' ').replace(/\b\w/g,function(c){return c.toUpperCase();});
            var itemUrl   = BASE + 'items/' + item.id;
            var photosUrl = BASE + 'items/' + item.id + '/photos';
            var harvestUrl= BASE + 'harvest/quick';
            var bgStyle = item.photo_id
                ? 'background-image:url(' + BASE + 'attachments/' + item.photo_id + '/download);background-size:cover;background-position:center;'
                : 'background:' + color + '22;';

            html += '<div class="nearby-card" style="' + bgStyle + '">';
            html += '  <div class="nearby-card-gradient"></div>';
            html += '  <a href="' + itemUrl + '" class="nearby-card-inner">';
            html += '    <div class="nearby-card-emoji" style="background:' + color + '30">' + emoji + '</div>';
            html += '    <div class="nearby-card-info">';
            html += '      <div class="nearby-card-name">' + item.name + '</div>';
            html += '      <div class="nearby-card-sub"><span class="nearby-card-type">' + label + '</span><span class="nearby-card-dist">📍 ' + fmtDist(item.dist) + '</span></div>';
            html += '    </div>';
            html += '  </a>';
            html += '  <div class="nearby-card-btns">';
            if (item.photo_id) {
                html += '    <img src="' + BASE + 'attachments/' + item.photo_id + '/download" alt="" class="nearby-card-photo-badge">';
            }
            html += '    <a href="' + photosUrl + '" class="nearby-card-btn" onclick="event.stopPropagation()" title="Photos">📷</a>';
            html += '    <a href="' + harvestUrl + '" class="nearby-card-btn" onclick="event.stopPropagation()" title="Harvest">🌾</a>';
            html += '    <a href="' + itemUrl + '#actions" class="nearby-card-btn" onclick="event.stopPropagation()" title="Add note">➕</a>';
            html += '  </div>';
            html += '</div>';
        });

        document.getElementById('nearbyList').innerHTML = html;
    }

    function detect(forceRefresh) {
        if (!GPS_ITEMS.length) {
            document.getElementById('nearbyEmptyMsg').textContent = 'Add GPS coordinates to items to see them here.';
            document.getElementById('nearbyEmpty').style.display = 'block';
            document.getElementById('nearbyTryBtn').style.display = 'none';
            return;
        }
        var last = RootedGPS.last();
        // Always render cached position immediately — never make the user wait
        if (last) {
            renderNearest(last.lat, last.lng);
            if (!forceRefresh) return;
            // Background-refresh silently: re-render when fresh fix arrives
            RootedGPS.get(function(pos) {
                if (pos) renderNearest(pos.lat, pos.lng);
            }, 0);
            return;
        }
        // No cached position yet — show spinner and wait
        var statusEl = document.getElementById('nearbyStatus');
        statusEl.textContent = '📡 Finding your location…';
        statusEl.style.display = 'block';
        RootedGPS.get(function(pos) {
            statusEl.style.display = 'none';
            if (!pos) {
                document.getElementById('nearbyEmpty').style.display = 'block';
                return;
            }
            renderNearest(pos.lat, pos.lng);
        }, 0);
    }

    detect(false);
    document.getElementById('nearbyRefresh').addEventListener('click', function() { detect(true); });
    document.getElementById('nearbyTryBtn').addEventListener('click', function() { detect(true); });

    // Auto-refresh nearest cards as GPS accuracy improves
    RootedGPS.onAccuracyImprove(function(pos) {
        if (GPS_ITEMS.length) renderNearest(pos.lat, pos.lng);
    });
}());
</script>

<!-- ============================================================
     BIODYNAMIC INFO MODAL
     ============================================================ -->
<div id="bioInfoModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:16px;max-width:560px;width:100%;max-height:88vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,0.3)">
        <div style="background:linear-gradient(135deg,#0f2d18,#1a3a1c);border-radius:16px 16px 0 0;padding:20px 24px;display:flex;align-items:center;justify-content:space-between">
            <div style="font-weight:800;font-size:1.05rem;color:#fff">🌙 How Rooted computes this</div>
            <button onclick="document.getElementById('bioInfoModal').style.display='none'" style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:30px;height:30px;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <div style="padding:22px 24px;font-size:.875rem;line-height:1.7;color:#374151">

            <p style="margin:0 0 16px;color:#6b7280;font-size:.82rem">These recommendations combine <strong>precise astronomical calculations</strong> with <strong>Maria Thun's biodynamic method</strong>. Here is exactly what is computed and why.</p>

            <div style="background:#f0fdf4;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                <div style="font-weight:700;font-size:.88rem;color:#15803d;margin-bottom:8px">🔭 Astronomy — Jean Meeus algorithms</div>
                <p style="margin:0 0 8px">The moon's position is computed using <strong>Jean Meeus' "Astronomical Algorithms"</strong> (2nd ed., 1998) — the professional standard used by observatories and planetariums worldwide.</p>
                <ul style="margin:0;padding-left:18px;color:#374151">
                    <li><strong>Lunar longitude</strong>: 40+ periodic terms from Meeus Tables 47.A, accurate to ~0.1°</li>
                    <li><strong>Sidereal conversion</strong>: Fagan-Bradley ayanamsa (~25.1° in 2026) — subtracts the precession offset to place the moon in its <em>actual constellation</em>, not the seasonal zodiac</li>
                    <li><strong>Declination</strong>: derived from longitude, latitude and Earth's obliquity — used to detect ascending vs. descending moon</li>
                    <li><strong>Distance</strong>: from parallax terms — detects apogee (furthest) and perigee (closest)</li>
                    <li><strong>Nodes</strong>: detected by latitude sign change — marks when the moon crosses the ecliptic</li>
                </ul>
            </div>

            <div style="background:#fef3c7;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                <div style="font-weight:700;font-size:.88rem;color:#92400e;margin-bottom:8px">🌱 Biodynamic interpretation — Maria Thun's method</div>
                <p style="margin:0 0 8px">Maria Thun (1922–2012) conducted field experiments over decades and published annual sowing calendars from 1963. She observed that crops responded differently depending on the moon's sidereal constellation:</p>
                <ul style="margin:0;padding-left:18px;color:#374151">
                    <li>🥕 <strong>Root days</strong>: Moon in Earth signs (Taurus, Virgo, Capricorn) → root vegetables</li>
                    <li>🥬 <strong>Leaf days</strong>: Moon in Water signs (Cancer, Scorpio, Pisces) → leafy greens</li>
                    <li>🌸 <strong>Flower days</strong>: Moon in Air signs (Gemini, Libra, Aquarius) → flowers & aromatics</li>
                    <li>🍎 <strong>Fruit days</strong>: Moon in Fire signs (Aries, Leo, Sagittarius) → fruits & seeds</li>
                </ul>
                <p style="margin:8px 0 0;font-size:.82rem;color:#78350f">
                    <strong>↓ Descending moon</strong> (declination decreasing): earth forces active → sow &amp; plant.<br>
                    <strong>↑ Ascending moon</strong> (increasing): cosmic forces active → harvest &amp; pick.<br>
                    <strong>⚠ Avoid periods</strong>: ±6 hours around lunar nodes and apogee/perigee — Thun observed disrupted plant responses.
                </p>
            </div>

            <div style="background:#f1f5f9;border-radius:10px;padding:14px 16px;font-size:.8rem;color:#475569">
                <div style="font-weight:700;color:#1e293b;margin-bottom:6px">📋 Scientific status</div>
                The astronomical calculations are <strong>precise and fully verifiable</strong>. The biodynamic interpretation is an <strong>empirical agricultural tradition</strong> — well-documented by practitioners worldwide and used on thousands of farms, but not replicated through conventional double-blind peer review. Treat the calendar as an additional timing layer alongside your own agronomic knowledge and experience.
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     QUICK ACTION STRIP
     ============================================================ -->
<div class="quick-actions-strip">
    <a href="<?= url('/items/create') ?>" class="quick-action-btn">
        <span class="quick-action-icon">➕</span>
        <span class="quick-action-label">Add Item</span>
    </a>
    <a href="<?= url('/dashboard/map') ?>" class="quick-action-btn">
        <span class="quick-action-icon">🗺</span>
        <span class="quick-action-label">Open Map</span>
    </a>
    <a href="<?= url('/harvest/quick') ?>" class="quick-action-btn quick-action-btn--harvest">
        <span class="quick-action-icon">🌾</span>
        <span class="quick-action-label">Harvest</span>
    </a>
    <a href="<?= url('/items?action=photos') ?>" class="quick-action-btn quick-action-btn--upload">
        <span class="quick-action-icon">📷</span>
        <span class="quick-action-label">Photos</span>
    </a>
    <a href="<?= url('/reminders') ?>" class="quick-action-btn">
        <span class="quick-action-icon">🔔</span>
        <span class="quick-action-label">Reminder</span>
    </a>
    <a href="<?= url('/garden') ?>" class="quick-action-btn">
        <span class="quick-action-icon">🌿</span>
        <span class="quick-action-label">Garden</span>
    </a>
    <a href="<?= url('/seeds/family-needs') ?>" class="quick-action-btn">
        <span class="quick-action-icon">🧺</span>
        <span class="quick-action-label">Family Needs</span>
    </a>
    <a href="<?= url('/irrigation') ?>" class="quick-action-btn quick-action-btn--irrigation">
        <span class="quick-action-icon">💧</span>
        <span class="quick-action-label">Irrigation</span>
    </a>
</div>

<!-- ============================================================
     STATS BAR
     ============================================================ -->
<div class="dash-stats-bar">
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= $totalItems ?></div>
        <div class="dash-stat-label">Total Items</div>
    </div>
    <div class="dash-stat-card <?= $overdueCount > 0 ? 'dash-stat-card--warning' : '' ?>">
        <div class="dash-stat-value"><?= $overdueCount ?></div>
        <div class="dash-stat-label">Overdue</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= $upcomingCount ?></div>
        <div class="dash-stat-label">Upcoming</div>
    </div>
</div>


<!-- ============================================================
     HARVEST CHART
     ============================================================ -->
<section class="dash-section">
    <h2 class="dash-section-title">🌾 <?= $currentYear ?> Harvest</h2>
    <?php if (!$hasHarvest): ?>
    <p class="text-muted" style="padding:var(--spacing-5) 0;text-align:center;font-style:italic">No harvests recorded yet this year.</p>
    <?php else: ?>
    <div class="dash-harvest-chart" aria-label="Monthly harvest chart">
        <?php for ($m = 1; $m <= 12; $m++):
            $val     = $monthlyTotals[$m];
            $pct     = $maxMonthly > 0 ? round(($val / $maxMonthly) * 100) : 0;
            $isFuture = $m > (int)date('n');
        ?>
        <div class="dash-bar-col <?= $isFuture ? 'dash-bar-col--future' : '' ?>">
            <div class="dash-bar-wrap">
                <div class="dash-bar" style="height:<?= $pct ?>%" title="<?= $monthNames[$m-1] ?>: <?= number_format($val,1) ?> <?= e($monthlyUnit) ?>">
                    <?php if ($val > 0): ?>
                    <span class="dash-bar-val"><?= number_format($val,0) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dash-bar-label"><?= $monthNames[$m-1] ?></div>
        </div>
        <?php endfor; ?>
    </div>
    <?php if ($monthlyUnit): ?>
    <p class="text-muted text-sm" style="text-align:right;margin-top:var(--spacing-2)">Unit: <?= e($monthlyUnit) ?></p>
    <?php endif; ?>
    <?php endif; ?>
</section>

<!-- ============================================================
     ITEMS BY TYPE
     ============================================================ -->
<?php if (!empty($itemCounts)): ?>
<section class="dash-section">
    <h2 class="dash-section-title">🌿 Items by Type</h2>
    <div class="dash-type-grid">
        <?php foreach ($itemCounts as $c):
            $emoji   = $typeEmoji[$c['type']] ?? '📦';
            $label   = ucwords(str_replace('_', ' ', $c['type']));
            $typeKey = e($c['type']);
        ?>
        <a href="<?= url('/items?type=' . $typeKey) ?>" class="dash-type-card dash-type-card--<?= $typeKey ?>">
            <span class="dash-type-emoji"><?= $emoji ?></span>
            <span class="dash-type-count"><?= (int)$c['cnt'] ?></span>
            <span class="dash-type-name"><?= e($label) ?></span>
            <?php if (!empty($harvestByTypeMap[$c['type']])): ?>
            <span class="dash-type-harvest text-muted">
                <?php
                $parts = [];
                foreach ($harvestByTypeMap[$c['type']] as $unit => $qty) {
                    $parts[] = number_format($qty, 0) . ' ' . e($unit);
                }
                echo implode(', ', $parts) . ' this year';
                ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     RECENT ACTIVITY
     ============================================================ -->
<section class="dash-widget">
    <div class="dash-widget-header">
        <span>⚡ Recent Activity</span>
        <a href="<?= url('/activity-log') ?>" class="dash-widget-link">Full log &rarr;</a>
    </div>
    <div class="dash-widget-body">
        <?php if (empty($recentActivity)): ?>
        <p class="text-muted dash-widget-empty">No recent activity.</p>
        <?php else: ?>
        <ul class="dash-activity-list">
            <?php foreach ($recentActivity as $a): ?>
            <li class="dash-activity-item">
                <span class="dash-activity-badge"><?= e($a['action_label']) ?></span>
                <span class="dash-activity-desc text-muted"><?= e(mb_strimwidth($a['description'], 0, 55, '…')) ?></span>
                <span class="dash-activity-time text-muted text-sm"><?= e(date('d M H:i', strtotime($a['performed_at']))) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>

<style>
/* -----------------------------------------------
   Quick Action Strip
   ----------------------------------------------- */
.quick-actions-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-5);
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    padding: var(--spacing-4) var(--spacing-2);
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    color: var(--color-text);
    font-size: 0.75rem;
    font-weight: 600;
    transition: box-shadow .15s, transform .1s;
    box-shadow: var(--shadow-sm);
    cursor: pointer;
}
.quick-action-btn:hover {
    text-decoration: none;
    box-shadow: var(--shadow);
    transform: translateY(-2px);
    border-color: var(--color-primary);
}
.quick-action-btn--harvest {
    background: linear-gradient(135deg, #f9f7f4, #e8f5e1);
    border-color: rgba(45,90,39,.25);
}
.quick-action-btn--irrigation {
    background: linear-gradient(135deg, #f0f8ff, #ddf0fb);
    border-color: rgba(2,132,199,.25);
}
.quick-action-icon { font-size: 1.75rem; line-height: 1; }
.quick-action-label { text-align: center; line-height: 1.2; white-space: nowrap; }

@media (min-width: 600px) {
    .quick-actions-strip { grid-template-columns: repeat(4, 1fr); }
}

/* -----------------------------------------------
   Stats Bar
   ----------------------------------------------- */
.dash-stats-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-5);
}
.dash-stat-card {
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    text-align: center;
    box-shadow: var(--shadow-sm);
}
.dash-stat-card--warning {
    background: #fff8f0;
    border-color: #f0a040;
}
.dash-stat-value {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--color-primary);
    line-height: 1;
    margin-bottom: var(--spacing-1);
}
.dash-stat-card--warning .dash-stat-value { color: var(--color-warning); }
.dash-stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--color-text-muted);
}

/* -----------------------------------------------
   Section
   ----------------------------------------------- */
.dash-section {
    margin-bottom: var(--spacing-6);
}
.dash-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: var(--spacing-4);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

/* -----------------------------------------------
   Overdue Reminders
   ----------------------------------------------- */
.dash-overdue-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}
.dash-overdue-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-4);
    background: #fff8f0;
    border: 1px solid #f0a040;
    border-left: 4px solid var(--color-warning);
    border-radius: var(--radius);
    flex-wrap: wrap;
}
.dash-overdue-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}
.dash-overdue-actions {
    display: flex;
    gap: var(--spacing-2);
    flex-shrink: 0;
}

/* -----------------------------------------------
   Harvest Bar Chart
   ----------------------------------------------- */
.dash-harvest-chart {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 120px;
    padding: var(--spacing-2) 0;
    border-bottom: 2px solid var(--color-border);
    overflow-x: auto;
}
.dash-bar-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 28px;
    height: 100%;
}
.dash-bar-col--future { opacity: .35; }
.dash-bar-wrap {
    flex: 1;
    width: 100%;
    display: flex;
    align-items: flex-end;
}
.dash-bar {
    width: 100%;
    background: linear-gradient(to top, var(--color-primary), var(--color-primary-light));
    border-radius: 3px 3px 0 0;
    min-height: 3px;
    position: relative;
    transition: opacity .15s;
    cursor: default;
}
.dash-bar:hover { opacity: .8; }
.dash-bar-val {
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.6rem;
    font-weight: 700;
    color: var(--color-primary);
    white-space: nowrap;
}
.dash-bar-label {
    font-size: 0.65rem;
    color: var(--color-text-muted);
    margin-top: 4px;
    text-align: center;
}

/* -----------------------------------------------
   Items by Type Grid
   ----------------------------------------------- */
.dash-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--spacing-3);
}
.dash-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-1);
    padding: var(--spacing-4) var(--spacing-3);
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-left: 4px solid var(--color-border);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--color-text);
    transition: box-shadow .15s, transform .1s;
    box-shadow: var(--shadow-sm);
}
.dash-type-card:hover {
    text-decoration: none;
    box-shadow: var(--shadow);
    transform: translateY(-2px);
}
.dash-type-emoji  { font-size: 1.8rem; line-height: 1; }
.dash-type-count  { font-size: 1.5rem; font-weight: 800; color: var(--color-primary); line-height: 1.1; }
.dash-type-name   { font-size: 0.72rem; font-weight: 600; text-align: center; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.dash-type-harvest{ font-size: 0.65rem; text-align: center; margin-top: 2px; }

/* Type-colored left borders */
.dash-type-card--olive_tree  { border-left-color: #4a7c43; }
.dash-type-card--tree        { border-left-color: #2d5a27; }
.dash-type-card--vine        { border-left-color: #8b3d9e; }
.dash-type-card--almond_tree { border-left-color: #8b5e3c; }
.dash-type-card--garden      { border-left-color: #1a5fa6; }
.dash-type-card--bed         { border-left-color: #1a5fa6; }
.dash-type-card--orchard     { border-left-color: #3d7a3d; }
.dash-type-card--zone        { border-left-color: #888; }
.dash-type-card--line        { border-left-color: #2c5faa; }

/* -----------------------------------------------
   Nearby Hero Section
   ----------------------------------------------- */
.nearby-hero {
    margin-bottom: var(--spacing-5);
    min-height: 80dvh;
    display: flex;
    flex-direction: column;
}
@media (min-width: 768px) {
    .nearby-hero { min-height: unset; }
}
.nearby-hero-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: var(--spacing-3);
}
.nearby-hero-title {
    font-size: 1rem; font-weight: 700; color: var(--color-text);
}
.nearby-refresh-btn {
    background: none; border: none; font-size: 1.1rem;
    color: var(--color-primary); cursor: pointer;
    padding: 4px 8px; border-radius: var(--radius); line-height: 1;
}
.nearby-refresh-btn:hover { background: var(--color-border); }

.nearby-status-bar {
    font-size: 0.85rem; color: var(--color-text-muted);
    padding: var(--spacing-2) 0; text-align: center;
}

.nearby-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-3);
    flex: 1;
}

/* Each nearby card */
.nearby-card {
    position: relative; border-radius: var(--radius-xl); overflow: hidden;
    min-height: 140px; display: flex; flex-direction: column;
    background-color: #111; background-size: cover; background-position: center;
    box-shadow: var(--shadow);
}
.nearby-card-gradient {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.20) 100%);
}
.nearby-card-inner {
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: 8px;
    padding: 10px 10px 6px; flex: 1;
    text-decoration: none; color: #fff;
}
.nearby-card-inner:hover { text-decoration: none; color: #fff; }
.nearby-card-emoji {
    width: 38px; height: 38px; border-radius: var(--radius);
    font-size: 1.25rem; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; backdrop-filter: blur(8px);
}
.nearby-card-photo-badge {
    position: absolute; bottom: 8px; left: 8px;
    width: 30px; height: 30px; border-radius: 50%;
    object-fit: cover; display: block; z-index: 2;
    border: 2px solid rgba(255,255,255,0.9);
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
}
.nearby-card-info { flex: 1; min-width: 0; }
.nearby-card-name {
    font-size: 0.82rem; font-weight: 700; color: #fff;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    line-height: 1.25;
}
.nearby-card-sub  { display: flex; flex-direction: column; gap: 1px; margin-top: 3px; }
.nearby-card-type { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: rgba(255,255,255,0.75); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nearby-card-dist { font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.95); white-space: nowrap; }

.nearby-card-btns {
    position: relative; z-index: 1;
    display: flex; align-items: center;
    padding: 0 8px 8px;
    gap: 5px;
    justify-content: flex-end;
}
.nearby-card-photo-badge {
    width: 34px; height: 34px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.9);
    box-shadow: 0 2px 6px rgba(0,0,0,0.45);
    margin-right: auto;
}
.nearby-card-btn {
    width: 34px; height: 34px; border-radius: var(--radius);
    background: rgba(255,255,255,0.18); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; text-decoration: none; border: 1px solid rgba(255,255,255,0.25);
    transition: background 0.15s;
}
.nearby-card-btn:hover { background: rgba(255,255,255,0.32); text-decoration: none; }

/* -----------------------------------------------
   Nearby Items (old, kept for reference)
   ----------------------------------------------- */
.nearby-status {
    font-size: 0.85rem;
    color: var(--color-text-muted);
    padding: var(--spacing-2) 0;
}
.nearby-refresh-btn {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 1.1rem;
    color: var(--color-primary);
    cursor: pointer;
    padding: 2px 6px;
    border-radius: var(--radius);
    line-height: 1;
}
.nearby-refresh-btn:hover { background: var(--color-border); }
.nearby-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}
.nearby-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    color: var(--color-text);
    box-shadow: var(--shadow-sm);
    transition: box-shadow .15s, transform .1s;
}
.nearby-card:hover { text-decoration: none; box-shadow: var(--shadow); transform: translateY(-2px); }
.nearby-card-icon {
    width: 44px; height: 44px;
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.nearby-card-body { flex: 1; min-width: 0; }
.nearby-card-name { font-size: 0.95rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nearby-card-meta { display: flex; gap: var(--spacing-3); margin-top: 2px; }
.nearby-card-type { font-size: 0.72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--color-text-muted); }
.nearby-card-dist { font-size: 0.75rem; font-weight: 600; color: var(--color-primary); }
.nearby-card-actions {
    display: flex;
    gap: var(--spacing-1);
    flex-shrink: 0;
}
.nearby-action-btn {
    display: flex; align-items: center; justify-content: center;
    width: 36px; height: 36px;
    border-radius: var(--radius);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    font-size: 1.1rem;
    text-decoration: none;
    transition: background .15s;
}
.nearby-action-btn:hover { background: var(--color-border); text-decoration: none; }

/* -----------------------------------------------
   Two-Column Layout
   ----------------------------------------------- */
.dash-two-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-5);
}
@media (min-width: 720px) {
    .dash-two-col { grid-template-columns: 1fr 1fr; }
}

.dash-widget {
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.dash-widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
}
.dash-widget-link {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
    color: var(--color-primary);
}
.dash-widget-body { padding: var(--spacing-3) var(--spacing-4); }
.dash-widget-empty { padding: var(--spacing-4) 0; text-align: center; font-style: italic; }

/* Upcoming widget — sits right under lunar */
.dash-upcoming-widget { margin-bottom: var(--spacing-5); }

/* Reminder List */
.dash-reminder-list { list-style: none; padding: 0; margin: 0; }
.dash-reminder-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-2) 0;
    border-bottom: 1px solid var(--color-border);
}
.dash-reminder-item:last-child { border-bottom: none; }
.dash-reminder-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-primary);
    flex-shrink: 0;
}
.dash-reminder-body { display: flex; flex-direction: column; gap: 1px; flex: 1; min-width: 0; }
.dash-reminder-title { font-size: 0.875rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Activity List */
.dash-activity-list { list-style: none; padding: 0; margin: 0; }
.dash-activity-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: var(--spacing-2) 0;
    border-bottom: 1px solid var(--color-border);
}
.dash-activity-item:last-child { border-bottom: none; }
.dash-activity-badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 999px;
    background: rgba(45,90,39,.1);
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.dash-activity-desc { font-size: 0.83rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-activity-time { font-size: 0.72rem; }

/* Lunar calendar — dark section */
.lunar-section {
    background: #1a2e1f;
    border-radius: var(--radius-xl);
    padding: var(--spacing-4);
}
.lunar-section-head { margin-bottom: var(--spacing-4); }
.lunar-section-title { font-size: 1rem; font-weight: 700; color: #d1fae5; letter-spacing: .01em; }
.lunar-today {
    display: flex; gap: var(--spacing-3); margin-bottom: var(--spacing-4);
    background: rgba(255,255,255,0.07); border-radius: var(--radius-lg);
    border: 1px solid rgba(255,255,255,0.10); padding: var(--spacing-4);
}
.lunar-today-left, .lunar-today-right {
    display: flex; align-items: center; gap: var(--spacing-4); flex: 1; min-width: 0;
}
.lunar-today-right { border-left: 1px solid rgba(255,255,255,0.12); padding-left: var(--spacing-4); }
.lunar-phase-big { font-size: 2.6rem; line-height: 1; flex-shrink: 0; }
.lunar-phase-name { font-size: 0.9rem; font-weight: 700; color: #f0fdf4; }
.lunar-phase-sub  { font-size: 0.72rem; color: rgba(255,255,255,0.55); margin-top: 3px; }
.lunar-type-emoji { font-size: 2rem; line-height: 1; flex-shrink: 0; }
.lunar-type-name  { font-size: 0.9rem; font-weight: 700; color: #f0fdf4; }
.lunar-type-desc  { font-size: 0.72rem; color: rgba(255,255,255,0.55); margin-top: 3px; }
.lunar-week {
    display: grid; grid-template-columns: repeat(7,1fr); gap: 4px;
}
.lunar-day {
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    padding: 7px 2px; border-radius: var(--radius);
    text-align: center; border: 1px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.04);
}
.lunar-day--today {
    border-color: rgba(134,239,172,0.6);
    background: rgba(134,239,172,0.10);
    font-weight: 700;
}
.lunar-day-label  { font-size: 0.58rem; text-transform: uppercase; letter-spacing: .04em; color: rgba(255,255,255,0.45); }
.lunar-day-num    { font-size: 0.8rem; font-weight: 700; color: #f0fdf4; }
.lunar-day-moon   { font-size: 1rem; line-height: 1; }
.lunar-day-emoji  { font-size: 0.9rem; line-height: 1; margin-top: 10px; }
.lunar-day-type   { font-size: 0.58rem; font-weight: 600; }
.lunar-elem-fire  { --elem-color: #fb923c; }
.lunar-elem-earth { --elem-color: #d97706; }
.lunar-elem-air   { --elem-color: #38bdf8; }
.lunar-elem-water { --elem-color: #818cf8; }
.lunar-day-type   { color: var(--elem-color, rgba(255,255,255,0.5)); }

/* -----------------------------------------------
   Welcome greeting + quote + weather (unified)
   ----------------------------------------------- */
.dash-welcome {
    margin-bottom: var(--spacing-5);
    padding: var(--spacing-4) 0 0;
}
.dash-welcome-greeting {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--color-text);
    line-height: 1.1;
    margin-bottom: var(--spacing-2);
}
.dash-welcome-quote {
    font-size: 0.82rem;
    color: var(--color-text-muted);
    font-style: italic;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: var(--spacing-3);
}

/* Weather — no card, flows with welcome */
.dash-weather-widget {
    background: transparent;
    padding: var(--spacing-3) 0 0;
    border-top: 1px solid var(--color-border);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}
.dash-weather-main {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
}
.dash-weather-icon   { font-size: 2rem; line-height: 1; flex-shrink: 0; }
.dash-weather-temps  { display: flex; flex-direction: column; gap: 1px; flex-shrink: 0; }
.dash-weather-temp   { font-size: 1.8rem; font-weight: 800; color: var(--color-text); line-height: 1; }
.dash-weather-feels  { font-size: 0.7rem; color: var(--color-text-muted); }
.dash-weather-center { display: flex; flex-direction: column; gap: 1px; flex: 1; min-width: 0; padding: 0 var(--spacing-2); }
.dash-weather-desc   { font-size: 0.85rem; font-weight: 600; color: var(--color-text); }
.dash-weather-city   { font-size: 0.72rem; color: var(--color-text-muted); }
.dash-weather-meta   { display: flex; flex-direction: column; gap: 2px; text-align: right; flex-shrink: 0; }
.dash-weather-detail { font-size: 0.7rem; color: var(--color-text-muted); white-space: nowrap; }

/* Hourly + daily forecast strip */
.dash-weather-hours {
    display: flex;
    gap: var(--spacing-2);
    border-top: 1px solid var(--color-border);
    padding-top: var(--spacing-2);
    overflow-x: auto;
}
.dash-weather-hour {
    display: flex; flex-direction: column; align-items: center;
    gap: 2px; min-width: 40px;
}
.dash-weather-hour-time { font-size: 0.62rem; color: var(--color-text-muted); }
.dash-weather-hour-icon { font-size: 1rem; line-height: 1; }
.dash-weather-hour-temp { font-size: 0.72rem; font-weight: 700; color: var(--color-text); }

/* Daily forecast items — tinted pill */
.dash-weather-day {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 4px 8px;
    min-width: 54px;
}
.dash-weather-day .dash-weather-hour-time { color: var(--color-text-muted); font-weight: 600; }
.dash-weather-day-min { font-size: 0.65rem; color: var(--color-text-muted); }
.dash-weather-hours-divider {
    width: 1px; background: var(--color-border);
    align-self: stretch; flex-shrink: 0; margin: 0 2px;
}

/* Forecast link */
.dash-weather-forecast-link {
    font-size: 0.72rem; font-weight: 600; color: var(--color-primary);
    text-decoration: none; align-self: flex-end; padding-bottom: var(--spacing-1);
}
.dash-weather-forecast-link:hover { text-decoration: underline; }
</style>
