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
<section class="dash-section lunar-section" style="padding-bottom:var(--spacing-3)">
    <div class="lunar-section-head">
        <span class="lunar-section-title">🌙 Garden — Right Now</span>
        <a href="<?= url('/garden/biodynamic') ?>" style="font-size:.75rem;color:var(--color-primary);text-decoration:none;font-weight:600">Full Calendar →</a>
    </div>

    <?php if (!empty($bioNow)): ?>
    <?php
        $_organBg    = \App\Support\BiodynamicCalendar::ORGAN_BG[$bioNow['organ']]    ?? '#f0fdf4';
        $_organColor = \App\Support\BiodynamicCalendar::ORGAN_COLOR[$bioNow['organ']] ?? '#15803d';
        $_organEmoji = \App\Support\BiodynamicCalendar::ORGAN_EMOJI[$bioNow['organ']] ?? '🌿';
    ?>
    <!-- Hero: what to do right now -->
    <?php if ($bioNow['is_anomaly']): ?>
    <div style="background:#fef2f2;border-radius:var(--radius);padding:14px 16px;margin-bottom:var(--spacing-3);border-left:4px solid #dc2626">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:2rem">⚠️</span>
            <div>
                <div style="font-weight:700;font-size:1rem;color:#dc2626">Anomaly Period — Rest Today</div>
                <div style="font-size:.82rem;color:#7f1d1d;margin-top:2px"><?= $_bioAdvice ?></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="background:<?= $_organBg ?>;border-radius:var(--radius);padding:14px 16px;margin-bottom:var(--spacing-3)">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span style="font-size:2.4rem;flex-shrink:0"><?= $_organEmoji ?></span>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:1.05rem;color:<?= $_organColor ?>"><?= $_bioAdvice ?></div>
                <div style="font-size:.78rem;color:rgba(0,0,0,0.5);margin-top:3px"><?= $_bioCrops ?></div>
            </div>
            <div style="flex-shrink:0;text-align:right">
                <div style="font-size:.75rem;font-weight:600;color:<?= $_organColor ?>"><?= $bioNow['organ'] ?> day</div>
                <div style="font-size:.7rem;color:rgba(0,0,0,0.4)"><?= $bioNow['name'] ?> · <?= $bioNow['element'] ?></div>
                <div style="font-size:.7rem;color:<?= $bioNow['is_descending'] ? '#1d4ed8' : '#6d28d9' ?>;margin-top:2px">
                    <?= $bioNow['is_descending'] ? '↓ Descending' : '↑ Ascending' ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Moon phase + 7-day strip -->
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
        <span style="font-size:1.2rem"><?= $moonToday['phaseEmoji'] ?></span>
        <span style="font-size:.8rem;color:var(--color-text-muted);font-weight:500"><?= $moonToday['phaseName'] ?> · <?= $moonToday['sign'] ?></span>
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
    <?php else: ?>
    <div class="lunar-week">
        <?php foreach ($moonWeek as $i => $m):
            $dayLabel = $i === 0 ? 'Today' : date('D', mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
            $dayNum   = date('j',   mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
            $elemClass = 'lunar-elem-' . strtolower($m['element']);
        ?>
        <div class="lunar-day <?= $i===0?'lunar-day--today':'' ?> <?= $elemClass ?>">
            <div class="lunar-day-label"><?= $dayLabel ?></div>
            <div class="lunar-day-num"><?= $dayNum ?></div>
            <div class="lunar-day-moon"><?= $m['phaseEmoji'] ?></div>
            <div class="lunar-day-emoji"><?= $m['dayEmoji'] ?></div>
            <div class="lunar-day-type"><?= $m['dayType'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

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
     UPCOMING REMINDERS
     ============================================================ -->
<?php if (!empty($upcomingReminders)): ?>
<section class="dash-widget dash-upcoming-widget">
    <div class="dash-widget-header">
        <span>📅 Upcoming Reminders</span>
        <a href="<?= url('/reminders') ?>" class="dash-widget-link">All &rarr;</a>
    </div>
    <div class="dash-widget-body">
        <ul class="dash-reminder-list">
            <?php foreach ($upcomingReminders as $r): ?>
            <li class="dash-reminder-item">
                <span class="dash-reminder-dot"></span>
                <div class="dash-reminder-body">
                    <span class="dash-reminder-title"><?= e($r['title']) ?></span>
                    <span class="text-muted text-sm"><?= e(date('d M', strtotime($r['due_at']))) ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
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
     OVERDUE REMINDERS (if any)
     ============================================================ -->
<?php if (!empty($overdueReminders)): ?>
<section class="dash-section">
    <h2 class="dash-section-title">⚠️ Overdue Reminders</h2>
    <div class="dash-overdue-list">
        <?php foreach ($overdueReminders as $r): ?>
        <div class="dash-overdue-item">
            <div class="dash-overdue-info">
                <strong><?= e($r['title']) ?></strong>
                <span class="text-muted text-sm"><?= e(date('d M Y', strtotime($r['due_at']))) ?></span>
                <?php if ($r['item_id']): ?>
                <a href="<?= url('/items/' . ((int)$r['item_id'])) ?>" class="text-sm">View Item</a>
                <?php endif; ?>
            </div>
            <div class="dash-overdue-actions">
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/complete') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-success">✓ Done</button>
                </form>
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/dismiss') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-secondary">Dismiss</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

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
    display: flex; flex-direction: column; gap: var(--spacing-3); flex: 1;
}

/* Each nearby card */
.nearby-card {
    position: relative; border-radius: var(--radius-xl); overflow: hidden;
    min-height: 180px; display: flex; flex-direction: column;
    background-color: #111; background-size: cover; background-position: center;
    box-shadow: var(--shadow); flex: 1;
}
.nearby-card-gradient {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.20) 100%);
}
.nearby-card-inner {
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: var(--spacing-3);
    padding: var(--spacing-4); flex: 1;
    text-decoration: none; color: #fff;
}
.nearby-card-inner:hover { text-decoration: none; color: #fff; }
.nearby-card-emoji {
    width: 52px; height: 52px; border-radius: var(--radius);
    font-size: 1.6rem; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; backdrop-filter: blur(8px);
}
.nearby-card-photo-badge {
    position: absolute; bottom: 10px; left: 10px;
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; display: block; z-index: 2;
    border: 2.5px solid rgba(255,255,255,0.9);
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
}
.nearby-card-info { flex: 1; min-width: 0; }
.nearby-card-name { font-size: 1.05rem; font-weight: 700; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.4); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nearby-card-sub  { display: flex; gap: var(--spacing-3); margin-top: 3px; align-items: center; }
.nearby-card-type { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: rgba(255,255,255,0.8); }
.nearby-card-dist { font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.95); }

.nearby-card-btns {
    position: relative; z-index: 1;
    display: flex; align-items: center;
    padding: 0 var(--spacing-3) var(--spacing-3);
    gap: var(--spacing-2);
    justify-content: flex-end;
}
.nearby-card-photo-badge {
    width: 44px; height: 44px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 2.5px solid rgba(255,255,255,0.9);
    box-shadow: 0 2px 6px rgba(0,0,0,0.45);
    margin-right: auto;
}
.nearby-card-btn {
    width: 42px; height: 42px; border-radius: var(--radius);
    background: rgba(255,255,255,0.18); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; text-decoration: none; border: 1px solid rgba(255,255,255,0.25);
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
