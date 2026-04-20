<?php
use App\Support\BiodynamicCalendar;

$monthNames  = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$dayNames    = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$monthName   = $monthNames[$month];

// First day of week for month header
$firstDow = (int) date('w', mktime(0,0,0,$month,1,$year));
$daysInMonth = count($monthData);

$organColor = BiodynamicCalendar::ORGAN_COLOR;
$organBg    = BiodynamicCalendar::ORGAN_BG;
$organEmoji = BiodynamicCalendar::ORGAN_EMOJI;

// Determine the filter from query string
$filterOrgan = trim($_GET['organ'] ?? '');
$filterCrop  = trim($_GET['crop'] ?? '');

// Crop → organ mapping
$cropOrganMap = [
    'tomato'=>'Fruit','tomatoes'=>'Fruit','pepper'=>'Fruit','peppers'=>'Fruit',
    'cucumber'=>'Fruit','zucchini'=>'Fruit','squash'=>'Fruit','pumpkin'=>'Fruit',
    'bean'=>'Fruit','beans'=>'Fruit','pea'=>'Fruit','peas'=>'Fruit',
    'corn'=>'Fruit','eggplant'=>'Fruit','aubergine'=>'Fruit',
    'carrot'=>'Root','carrots'=>'Root','potato'=>'Root','potatoes'=>'Root',
    'beet'=>'Root','beetroot'=>'Root','radish'=>'Root','turnip'=>'Root',
    'onion'=>'Root','onions'=>'Root','garlic'=>'Root','parsnip'=>'Root',
    'celeriac'=>'Root','fennel bulb'=>'Root',
    'lettuce'=>'Leaf','spinach'=>'Leaf','cabbage'=>'Leaf','kale'=>'Leaf',
    'chard'=>'Leaf','parsley'=>'Leaf','basil'=>'Leaf','mint'=>'Leaf',
    'leek'=>'Leaf','celery'=>'Leaf','endive'=>'Leaf','chicory'=>'Leaf',
    'broccoli'=>'Flower','cauliflower'=>'Flower','artichoke'=>'Flower',
    'lavender'=>'Flower','rose'=>'Flower','chamomile'=>'Flower',
    'calendula'=>'Flower','sunflower'=>'Flower',
];

// Resolve effective filter organ
$effectiveOrgan = $filterOrgan;
if (!$effectiveOrgan && $filterCrop !== '') {
    $key = strtolower(trim($filterCrop));
    $effectiveOrgan = $cropOrganMap[$key] ?? '';
}

$currentHour = (int) date('G');
?>

<style>
.bio-page { max-width: 100%; }
.bio-nav {
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    margin-bottom:var(--spacing-4);flex-wrap:wrap;
}
.bio-nav-month { font-size:1.3rem;font-weight:800; }
.bio-nav-btns  { display:flex;gap:8px;align-items:center; }

.bio-legend {
    display:flex;gap:10px;flex-wrap:wrap;align-items:center;
    margin-bottom:var(--spacing-4);
    background:var(--color-surface-raised);border:1px solid var(--color-border);
    border-radius:var(--radius-lg);padding:10px 14px;
}
.bio-legend-item {
    display:flex;align-items:center;gap:5px;font-size:.78rem;font-weight:600;
    padding:4px 10px;border-radius:999px;cursor:pointer;border:1.5px solid transparent;
    transition:border-color .15s;text-decoration:none;color:inherit;
}
.bio-legend-item:hover, .bio-legend-item.active { border-color: currentColor; }
.bio-legend-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
.bio-legend-sep { width:1px;height:16px;background:var(--color-border);flex-shrink:0; }

.bio-filter-bar {
    display:flex;gap:10px;flex-wrap:wrap;align-items:center;
    margin-bottom:var(--spacing-4);
}
.bio-filter-bar input { flex:1;min-width:160px;max-width:280px; }

/* ── Calendar grid ── */
.bio-grid-wrap { overflow-x:auto;-webkit-overflow-scrolling:touch; }
.bio-grid {
    display:grid;
    grid-template-columns: 60px repeat(24, minmax(28px, 1fr));
    min-width: 750px;
    border-radius:var(--radius-lg);
    overflow:hidden;
    border:1px solid var(--color-border);
}
.bio-grid-header {
    background:var(--color-surface-raised);
    font-size:.65rem;font-weight:700;color:var(--color-text-muted);
    padding:4px 2px;text-align:center;border-bottom:1px solid var(--color-border);
}
.bio-grid-day-label {
    background:var(--color-surface-raised);
    padding:4px 6px;display:flex;flex-direction:column;justify-content:center;
    border-bottom:1px solid var(--color-border-subtle,rgba(0,0,0,.06));
    font-size:.75rem;font-weight:600;border-right:1px solid var(--color-border);
    min-height:30px;
}
.bio-grid-day-label.today { background:var(--color-primary);color:#fff; }
.bio-grid-day-num  { font-size:.85rem;line-height:1; }
.bio-grid-day-name { font-size:.62rem;opacity:.65; }

.bio-cell {
    height:36px;
    border-bottom:1px solid var(--color-border-subtle,rgba(0,0,0,.04));
    position:relative;transition:opacity .1s;overflow:hidden;
}
.bio-cell:hover { opacity:.8; }
.bio-cell--anomaly { background: repeating-linear-gradient(45deg,#e5e7eb,#e5e7eb 2px,#f3f4f6 2px,#f3f4f6 8px) !important; }
.bio-cell--filtered-out { opacity:.12; }
.bio-cell--organ-start { border-left:2.5px solid rgba(0,0,0,0.25) !important; }
.bio-cell-emoji {
    position:absolute;top:50%;left:2px;transform:translateY(-50%);
    font-size:13px;line-height:1;pointer-events:none;
}
.bio-cell-initial {
    position:absolute;top:50%;right:2px;transform:translateY(-50%);
    font-size:.5rem;font-weight:800;opacity:.4;pointer-events:none;letter-spacing:0;
}
.bio-cell-desc-bar {
    position:absolute;bottom:0;left:0;right:0;height:3px;
    background:#2563eb;opacity:.65;
}
.bio-cell-now {
    position:absolute;top:0;bottom:0;left:0;right:0;
    outline:2px solid #ef4444;outline-offset:-2px;z-index:1;
}

/* Day-organ summary row (below grid) */
.bio-day-summary {
    display:grid;
    grid-template-columns: 60px repeat(<?= $daysInMonth ?>, 1fr);
    min-width:700px;
    border:1px solid var(--color-border);border-top:none;
    border-radius:0 0 var(--radius-lg) var(--radius-lg);overflow:hidden;
    margin-bottom:var(--spacing-5);
}
.bio-day-sum-label {
    background:var(--color-surface-raised);font-size:.65rem;font-weight:600;
    padding:4px 6px;color:var(--color-text-muted);display:flex;align-items:center;
    border-right:1px solid var(--color-border);
}
.bio-day-sum-cell {
    padding:3px 2px;text-align:center;font-size:.8rem;
    border-right:1px solid var(--color-border-subtle,rgba(0,0,0,.04));
    cursor:default;
}
.bio-day-sum-cell:last-child { border-right:none; }

/* Quick-look cards */
.bio-best-days {
    display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
    gap:var(--spacing-3);margin-bottom:var(--spacing-5);
}
.bio-best-card {
    border-radius:var(--radius-lg);padding:14px 16px;
    border:1px solid var(--color-border);
}
.bio-best-card-title { font-weight:700;font-size:.82rem;margin-bottom:6px; }
.bio-best-card-days { font-size:.75rem;line-height:1.7;color:var(--color-text-muted); }
.bio-best-day-badge {
    display:inline-block;padding:1px 7px;border-radius:999px;
    font-size:.7rem;font-weight:700;margin:1px;
}

/* Tooltip on hover */
.bio-cell:hover .bio-cell-tip {
    display:block;
}
.bio-cell-tip {
    display:none;
    position:absolute;bottom:calc(100% + 4px);left:50%;transform:translateX(-50%);
    background:#1f2937;color:#fff;border-radius:6px;
    font-size:.68rem;white-space:nowrap;padding:3px 7px;z-index:100;
    pointer-events:none;
}
</style>

<div class="bio-page">

<!-- Navigation -->
<div class="bio-nav">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="<?= url('/garden') ?>" class="btn btn-ghost btn-sm">← Garden</a>
        <div class="bio-nav-month">🌙 <?= $monthName ?> <?= $year ?></div>
        <button onclick="document.getElementById('bioInfoModalCal').style.display='flex'"
                style="background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:50%;width:28px;height:28px;color:var(--color-text-muted);font-size:.8rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                title="How does this work?">ⓘ</button>
    </div>
    <div class="bio-nav-btns">
        <a href="<?= url('/garden/biodynamic?year='.$prevYear.'&month='.$prevMonth) ?>" class="btn btn-secondary btn-sm">← <?= $monthNames[$prevMonth] ?></a>
        <?php if (!$isCurrentMonth): ?>
        <a href="<?= url('/garden/biodynamic') ?>" class="btn btn-ghost btn-sm">Today</a>
        <?php endif; ?>
        <a href="<?= url('/garden/biodynamic?year='.$nextYear.'&month='.$nextMonth) ?>" class="btn btn-secondary btn-sm"><?= $monthNames[$nextMonth] ?> →</a>
    </div>
</div>

<!-- Biodynamic method info modal -->
<div id="bioInfoModalCal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:16px;max-width:560px;width:100%;max-height:88vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,0.3)">
        <div style="background:linear-gradient(135deg,#0f2d18,#1a3a1c);border-radius:16px 16px 0 0;padding:20px 24px;display:flex;align-items:center;justify-content:space-between">
            <div style="font-weight:800;font-size:1.05rem;color:#fff">🌙 How Rooted computes this</div>
            <button onclick="document.getElementById('bioInfoModalCal').style.display='none'" style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:30px;height:30px;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <div style="padding:22px 24px;font-size:.875rem;line-height:1.7;color:#374151">
            <p style="margin:0 0 16px;color:#6b7280;font-size:.82rem">These recommendations combine <strong>precise astronomical calculations</strong> with <strong>Maria Thun's biodynamic method</strong>.</p>
            <div style="background:#f0fdf4;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                <div style="font-weight:700;font-size:.88rem;color:#15803d;margin-bottom:8px">🔭 Astronomy — Jean Meeus algorithms</div>
                <p style="margin:0 0 8px">The moon's position is computed using <strong>Jean Meeus' "Astronomical Algorithms"</strong> (2nd ed., 1998) — the professional standard used by observatories worldwide.</p>
                <ul style="margin:0;padding-left:18px">
                    <li><strong>Lunar longitude</strong>: 40+ periodic terms from Meeus Tables 47.A, accurate to ~0.1°</li>
                    <li><strong>Sidereal conversion</strong>: Fagan-Bradley ayanamsa (~25.1° in 2026) — places the moon in its <em>actual constellation</em></li>
                    <li><strong>Declination</strong>: determines ascending ↑ vs. descending ↓ moon</li>
                    <li><strong>Distance + nodes</strong>: detect apogee/perigee and ecliptic crossings for avoid periods (⚠)</li>
                </ul>
            </div>
            <div style="background:#fef3c7;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                <div style="font-weight:700;font-size:.88rem;color:#92400e;margin-bottom:8px">🌱 Biodynamic interpretation — Maria Thun's method</div>
                <ul style="margin:0;padding-left:18px">
                    <li>🥕 <strong>Root</strong>: Moon in Earth signs (Taurus, Virgo, Capricorn)</li>
                    <li>🥬 <strong>Leaf</strong>: Moon in Water signs (Cancer, Scorpio, Pisces)</li>
                    <li>🌸 <strong>Flower</strong>: Moon in Air signs (Gemini, Libra, Aquarius)</li>
                    <li>🍎 <strong>Fruit</strong>: Moon in Fire signs (Aries, Leo, Sagittarius)</li>
                </ul>
                <p style="margin:8px 0 0;font-size:.82rem;color:#78350f">
                    <strong>↓ Descending</strong>: sow &amp; plant · <strong>↑ Ascending</strong>: harvest &amp; pick · <strong>⚠ Avoid</strong>: ±6h around nodes &amp; apses
                </p>
            </div>
            <div style="background:#f1f5f9;border-radius:10px;padding:14px 16px;font-size:.8rem;color:#475569">
                <strong>Scientific status:</strong> The astronomical calculations are precise and verifiable. The biodynamic interpretation is an empirical agricultural tradition used on thousands of farms worldwide — not replicated by conventional double-blind peer review. Use as an additional timing layer alongside your own experience.
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Legend + filter -->
<div class="bio-legend">
    <?php foreach (['Root','Leaf','Flower','Fruit'] as $org): ?>
    <a href="<?= url('/garden/biodynamic?year='.$year.'&month='.$month.'&organ='.($effectiveOrgan===$org?'':$org)) ?>"
       class="bio-legend-item <?= $effectiveOrgan===$org ? 'active' : '' ?>"
       style="color:<?= $organColor[$org] ?>;background:<?= $organBg[$org] ?>">
        <span class="bio-legend-dot" style="background:<?= $organColor[$org] ?>"></span>
        <?= $organEmoji[$org] ?> <?= $org ?>
    </a>
    <?php endforeach; ?>
    <div class="bio-legend-sep"></div>
    <div class="bio-legend-item" style="color:#2563eb;background:#eff6ff">
        <div style="width:22px;height:3px;background:#2563eb;border-radius:2px"></div> Planting ↓
    </div>
    <div class="bio-legend-item" style="color:#6b7280;background:#f3f4f6">
        <div style="width:10px;height:10px;background:repeating-linear-gradient(45deg,#d1d5db,#d1d5db 2px,#f3f4f6 2px,#f3f4f6 6px);border-radius:2px"></div> Avoid
    </div>
</div>

<!-- Crop search filter -->
<form method="GET" action="<?= url('/garden/biodynamic') ?>" class="bio-filter-bar">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="month" value="<?= $month ?>">
    <input type="text" name="crop" class="form-input" placeholder="Search by crop… e.g. tomatoes, carrots"
           value="<?= e($filterCrop) ?>" style="max-width:280px">
    <button type="submit" class="btn btn-primary btn-sm">Show Best Days</button>
    <?php if ($filterCrop || $effectiveOrgan): ?>
    <a href="<?= url('/garden/biodynamic?year='.$year.'&month='.$month) ?>" class="btn btn-ghost btn-sm">✕ Clear</a>
    <?php endif; ?>
    <?php if ($filterCrop && $effectiveOrgan): ?>
    <span style="font-size:.8rem;color:var(--color-text-muted)">→ Showing <strong><?= $organEmoji[$effectiveOrgan] ?> <?= $effectiveOrgan ?></strong> days for <em><?= e($filterCrop) ?></em></span>
    <?php elseif ($filterCrop && !$effectiveOrgan): ?>
    <span style="font-size:.8rem;color:#dc2626">Crop not recognized. Try: tomatoes, carrots, lettuce, broccoli…</span>
    <?php endif; ?>
</form>

<!-- ── Main hourly grid ─────────────────────────────────────────────── -->
<div class="bio-grid-wrap">
<div class="bio-grid">

    <!-- Header row: hour labels (bold at 0, 6, 12, 18) -->
    <div class="bio-grid-header">Day</div>
    <?php for ($h = 0; $h < 24; $h++):
        $isKey = in_array($h, [0,6,12,18]);
    ?>
    <div class="bio-grid-header" style="<?= $isKey ? 'font-size:.72rem;color:var(--color-text);font-weight:800;background:var(--color-surface)' : '' ?>"><?= $h ?></div>
    <?php endfor; ?>

    <!-- Day rows -->
    <?php foreach ($monthData as $day => $hours):
        $dow       = (int) date('w', mktime(0,0,0,$month,$day,$year));
        $isToday   = $isCurrentMonth && $day === $today;
        $isWeekend = $dow === 0 || $dow === 6;
    ?>
    <div class="bio-grid-day-label <?= $isToday ? 'today' : '' ?>">
        <div class="bio-grid-day-num"><?= $day ?></div>
        <div class="bio-grid-day-name"><?= $dayNames[$dow] ?></div>
    </div>
    <?php $prevOrgan = null; ?>
    <?php foreach ($hours as $h => $pt):
        $organ   = $pt['organ'];
        $color   = $organColor[$organ];
        $bg      = $organBg[$organ];
        $emoji   = $organEmoji[$organ] ?? '';
        $isAnom  = $pt['is_anomaly'];
        $isDesc  = $pt['is_descending'];
        $isNow   = $isToday && $h === $currentHour;
        $dimmed  = $effectiveOrgan && $organ !== $effectiveOrgan;
        $isStart = !$isAnom && $organ !== $prevOrgan;
        $classes = 'bio-cell'
            . ($isAnom  ? ' bio-cell--anomaly' : '')
            . ($dimmed  ? ' bio-cell--filtered-out' : '')
            . ($isStart ? ' bio-cell--organ-start' : '');
    ?>
    <div class="<?= $classes ?>"
         style="<?= !$isAnom ? 'background:'.$bg.';border-top:2px solid '.$color : '' ?>"
         title="<?= $day ?> <?= $monthName ?> <?= sprintf('%02d',$h) ?>h — <?= $pt['name'] ?> (<?= $organ ?>) <?= $isDesc ? '↓Plant' : '↑Harvest' ?><?= $isAnom ? ' ⚠Avoid' : '' ?>">
        <?php if ($isStart): ?>
        <span class="bio-cell-emoji"><?= $emoji ?></span>
        <?php endif; ?>
        <?php if (!$isAnom && !$isStart): ?>
        <span class="bio-cell-initial" style="color:<?= $color ?>"><?= $organ[0] ?></span>
        <?php endif; ?>
        <?php if ($isDesc && !$isAnom): ?>
        <div class="bio-cell-desc-bar"></div>
        <?php endif; ?>
        <?php if ($isNow): ?>
        <div class="bio-cell-now"></div>
        <?php endif; ?>
    </div>
    <?php $prevOrgan = $isAnom ? null : $organ; ?>
    <?php endforeach; ?>
    <?php endforeach; ?>

</div><!-- .bio-grid -->
</div><!-- .bio-grid-wrap -->

<!-- Day summary icons row -->
<div style="overflow-x:auto;-webkit-overflow-scrolling:touch;margin-top:2px">
<div style="display:flex;min-width:700px;border:1px solid var(--color-border);border-radius:var(--radius);overflow:hidden">
    <div style="background:var(--color-surface-raised);font-size:.6rem;font-weight:600;padding:4px 6px;color:var(--color-text-muted);display:flex;align-items:center;min-width:60px;border-right:1px solid var(--color-border);flex-shrink:0">Day</div>
    <?php foreach ($daySummary as $day => $ds):
        $org   = $ds['dominant_organ'];
        $emoji = $organEmoji[$org] ?? '•';
        $bg2   = $organBg[$org] ?? 'transparent';
        $col2  = $organColor[$org] ?? '#666';
        $isToday2 = $isCurrentMonth && $day === $today;
    ?>
    <div style="flex:1;text-align:center;padding:4px 2px;font-size:.75rem;background:<?= $bg2 ?>;border-right:1px solid rgba(0,0,0,.05);<?= $isToday2 ? 'outline:2px solid #ef4444;outline-offset:-2px;z-index:1;position:relative' : '' ?>"
         title="<?= $day ?> <?= $monthName ?> — <?= $org ?> day<?= $ds['anomaly_hours']>0 ? ' ('.$ds['anomaly_hours'].'h ⚠)' : '' ?>">
        <div style="font-size:.7rem"><?= $emoji ?></div>
        <div style="font-size:.55rem;color:<?= $col2 ?>;font-weight:700"><?= $day ?></div>
    </div>
    <?php endforeach; ?>
</div>
</div>

<!-- ── Best days by organ ─────────────────────────────────────────────── -->
<h3 style="font-size:.95rem;font-weight:700;margin:var(--spacing-5) 0 var(--spacing-3)">
    ✨ Best Planting Days — <?= $monthName ?>
    <span style="font-weight:400;font-size:.78rem;color:var(--color-text-muted)">(descending moon + no anomalies)</span>
</h3>
<div class="bio-best-days">
<?php foreach (['Root','Leaf','Flower','Fruit'] as $org):
    $bestDays = [];
    foreach ($daySummary as $day => $ds) {
        if ($ds['dominant_organ'] === $org && $ds['ascending_hours'] < 12 && $ds['anomaly_hours'] < 8) {
            $bestDays[] = $day;
        }
    }
?>
<div class="bio-best-card" style="border-left:3px solid <?= $organColor[$org] ?>;background:<?= $organBg[$org] ?>20">
    <div class="bio-best-card-title" style="color:<?= $organColor[$org] ?>"><?= $organEmoji[$org] ?> <?= $org ?> Days</div>
    <div class="bio-best-card-days">
        <?php if (empty($bestDays)): ?>
        <em style="color:var(--color-text-muted)">None this month</em>
        <?php else: ?>
        <?php foreach ($bestDays as $bd):
            $dow2 = date('D', mktime(0,0,0,$month,$bd,$year));
        ?>
        <span class="bio-best-day-badge" style="background:<?= $organColor[$org] ?>22;color:<?= $organColor[$org] ?>"><?= $dow2 ?> <?= $bd ?></span>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Info box -->
<div style="background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:var(--spacing-4);font-size:.82rem;color:var(--color-text-muted);line-height:1.7">
    <strong style="color:var(--color-text)">How to read this calendar:</strong><br>
    Each cell is one hour of the day. Colors show which plant organ the Moon is influencing — <?= $organEmoji['Root'] ?> <strong>Root</strong> (Taurus/Virgo/Capricorn),
    <?= $organEmoji['Leaf'] ?> <strong>Leaf</strong> (Cancer/Scorpio/Pisces),
    <?= $organEmoji['Flower'] ?> <strong>Flower</strong> (Gemini/Libra/Aquarius),
    <?= $organEmoji['Fruit'] ?> <strong>Fruit</strong> (Aries/Leo/Sagittarius).
    The <span style="display:inline-block;width:16px;height:3px;background:#2563eb;vertical-align:middle;border-radius:2px"></span> blue bar means the Moon is <strong>descending</strong> — best time to plant and sow.
    Hatched grey cells = Moon near a node or apogee/perigee — avoid important garden work.
    Sidereal zodiac · Fagan-Bradley ayanamsa · Timezone: <?= e($tz) ?>.
</div>

</div><!-- .bio-page -->
