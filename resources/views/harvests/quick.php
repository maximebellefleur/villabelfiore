<?php
// Wheelbarrow SVG — side view, large spoked wheel, amber tray, wood handles, support leg
$wheelbarrowSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 48" width="48" height="32" style="vertical-align:middle;display:inline-block" aria-label="wheelbarrow">'
    . '<!-- wheel -->'
    . '<circle cx="14" cy="36" r="11" fill="#374151" stroke="#111827" stroke-width="1.5"/>'
    . '<line x1="14" y1="25.5" x2="14" y2="33" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round"/>'
    . '<line x1="14" y1="39" x2="14" y2="46.5" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round"/>'
    . '<line x1="3.5" y1="36" x2="11" y2="36" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round"/>'
    . '<line x1="17" y1="36" x2="24.5" y2="36" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round"/>'
    . '<line x1="6.2" y1="28.2" x2="11" y2="33" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round"/>'
    . '<line x1="17" y1="39" x2="21.8" y2="43.8" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round"/>'
    . '<circle cx="14" cy="36" r="3" fill="#D1D5DB"/>'
    . '<!-- axle arm -->'
    . '<line x1="14" y1="25.5" x2="22" y2="27" stroke="#4B5563" stroke-width="3" stroke-linecap="round"/>'
    . '<!-- tray -->'
    . '<path d="M20 9 L56 9 L56 27 L24 27 Z" fill="#D97706" stroke="#92400E" stroke-width="1.5" stroke-linejoin="round"/>'
    . '<path d="M22 11 L54 11 L54 25 L26 25 Z" fill="rgba(0,0,0,.1)"/>'
    . '<rect x="19" y="7" width="38" height="4" rx="2" fill="#FBBF24" stroke="#B45309" stroke-width=".8"/>'
    . '<!-- leg -->'
    . '<line x1="38" y1="27" x2="36" y2="44" stroke="#6B7280" stroke-width="2.5" stroke-linecap="round"/>'
    . '<!-- handles -->'
    . '<line x1="56" y1="9" x2="70" y2="2" stroke="#78350F" stroke-width="2.5" stroke-linecap="round"/>'
    . '<line x1="56" y1="27" x2="70" y2="18" stroke="#78350F" stroke-width="2.5" stroke-linecap="round"/>'
    . '<line x1="70" y1="2" x2="70" y2="18" stroke="#92400E" stroke-width="4" stroke-linecap="round"/>'
    . '</svg>';

$harvestIcon = [
    'olive_tree'  => '🧺',
    'almond_tree' => $wheelbarrowSvg,
    'vine'        => '🍇',
    'tree'        => '🌳',
];
$harvestColor = [
    'olive_tree'  => '#2d6a4f',
    'almond_tree' => '#92400e',
    'vine'        => '#6d28d9',
    'tree'        => '#166534',
];
// Unit info: label from harvestConfig, icon hardcoded per type
$harvestUnitIcon = [
    'olive_tree'  => '🧺',
    'almond_tree' => $wheelbarrowSvg,
    'vine'        => '⚖️',
    'tree'        => '⚖️',
];
?>
<div class="qh-page">

<div class="qh-header">
    <a href="<?= url('/dashboard') ?>" class="qh-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <h1 class="qh-title">🌾 Quick Harvest <?= e($year) ?></h1>
    <button class="qh-sort-btn" id="qhSortBtn" title="Sort by distance">📍 Nearest first</button>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (empty($items)): ?>
<div class="qh-empty">
    <div>🌱</div>
    <p>No harvestable items found. Add some trees first.</p>
</div>
<?php else: ?>

<div class="qh-list" id="qhList">
<?php foreach ($items as $item):
    $icon      = $harvestIcon[$item['type']] ?? '🌾';
    $color     = $harvestColor[$item['type']] ?? '#2d6a4f';
    $typeCfg   = $harvestConfig[$item['type']] ?? [];
    $unitInfo  = ['label' => $typeCfg['unit'] ?? 'units', 'icon' => $harvestUnitIcon[$item['type']] ?? '📦'];
    $hasGps    = !empty($item['gps_lat']) && !empty($item['gps_lng']);
    $count     = $yearCounts[(int)$item['id']] ?? 0;
    $maxYear   = $maxPerYearMap[$item['type']] ?? 1;
    $maxed     = $count >= $maxYear;
    $entries   = $yearHarvests[(int)$item['id']] ?? [];
?>
<div class="qh-card<?= $maxed ? ' qh-card--maxed' : '' ?>"
     data-item-id="<?= (int)$item['id'] ?>"
     data-lat="<?= $hasGps ? e($item['gps_lat']) : '' ?>"
     data-lng="<?= $hasGps ? e($item['gps_lng']) : '' ?>">

    <div class="qh-card-head">
        <div class="qh-card-icon" style="background:<?= $color ?>18;color:<?= $color ?>"><?= $icon ?></div>
        <div class="qh-card-info">
            <div class="qh-card-name"><?= e($item['name']) ?></div>
            <div class="qh-card-meta">
                <span style="color:<?= $color ?>;font-weight:700;font-size:.75rem;text-transform:uppercase"><?= ucwords(str_replace('_',' ',$item['type'])) ?></span>
                <span class="qh-card-dist" style="display:none"></span>
            </div>
        </div>
        <div class="qh-year-badge <?= $maxed ? 'qh-year-badge--maxed' : '' ?>">
            <?= $count ?>/<?= $maxYear ?> <span class="qh-year-label">this year</span>
        </div>
    </div>

    <?php if ($maxed): ?>
    <div class="qh-maxed-msg">✅ Harvest complete for <?= e($year) ?></div>
    <?php else:
        $sliderMax    = (float) ($typeCfg['slider_max']  ?? 5.0);
        $sliderStep   = (float) ($typeCfg['slider_step'] ?? 0.25);
        $sliderSteps  = (int) round($sliderMax / $sliderStep);  // input[max]
        // 5 tick labels: 0, 25%, 50%, 75%, 100% of display_max
        $ticks = [];
        for ($t = 0; $t <= 4; $t++) {
            $v = $sliderMax * $t / 4;
            $ticks[] = ($v == (int)$v) ? (int)$v : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        }
    ?>
    <!-- Slider -->
    <div class="qh-slider-wrap">
        <div class="qh-slider-labels">
            <span class="qh-unit-icon"><?= $unitInfo['icon'] ?></span>
            <span class="qh-slider-val" id="val_<?= (int)$item['id'] ?>">0</span>
            <span class="qh-unit-label"><?= $unitInfo['label'] ?></span>
        </div>
        <input type="range" class="qh-slider" id="slider_<?= (int)$item['id'] ?>"
               min="0" max="<?= $sliderSteps ?>" step="1" value="0"
               data-item="<?= (int)$item['id'] ?>"
               data-step="<?= $sliderStep ?>">
        <div class="qh-slider-ticks">
            <?php foreach ($ticks as $tick): ?><span><?= $tick ?></span><?php endforeach; ?>
        </div>
    </div>

    <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/harvests') ?>" class="qh-form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
        <input type="hidden" name="_redirect" value="<?= url('/harvest/quick') ?>">
        <input type="hidden" name="harvest_type" value="general">
        <input type="hidden" name="unit" value="<?= e($unitInfo['label']) ?>">
        <input type="hidden" name="quantity" id="qty_<?= (int)$item['id'] ?>" value="0">
        <button type="submit" class="qh-harvest-btn" id="btn_<?= (int)$item['id'] ?>" disabled
                style="--item-color:<?= $color ?>">
            🌾 Harvest it!
        </button>
    </form>
    <?php endif; ?>

    <?php if (!empty($entries)): ?>
    <div class="qh-entries">
        <div class="qh-entries-title">📋 <?= e($year) ?> harvests</div>
        <?php foreach ($entries as $entry): ?>
        <div class="qh-entry-row">
            <span class="qh-entry-qty"><?= $unitInfo['icon'] ?> <?= number_format((float)$entry['quantity'], 2) ?> <?= e($entry['unit']) ?></span>
            <span class="qh-entry-date"><?= date('M j', strtotime($entry['recorded_at'])) ?></span>
            <form method="POST" action="<?= url('/harvests/' . (int)$entry['id'] . '/trash') ?>" class="qh-entry-del qh-del-form">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <input type="hidden" name="_redirect" value="<?= url('/harvest/quick') ?>">
                <span class="qh-del-confirm" style="display:none">
                    <button type="submit" class="qh-del-yes">✓ Yes</button>
                    <button type="button" class="qh-del-no">✕ No</button>
                </span>
                <button type="button" class="qh-del-btn qh-del-trigger" title="Delete">✕</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div><!-- .qh-page -->

<script>
(function () {
    // Slider: integer steps × data-step = actual value
    document.querySelectorAll('.qh-slider').forEach(function(slider) {
        var itemId = slider.dataset.item;
        var step   = parseFloat(slider.dataset.step) || 0.25;
        var valEl  = document.getElementById('val_' + itemId);
        var qtyEl  = document.getElementById('qty_' + itemId);
        var btnEl  = document.getElementById('btn_' + itemId);

        slider.addEventListener('input', function() {
            var v = parseFloat(slider.value) * step;
            valEl.textContent = v % 1 === 0 ? v : v.toFixed(2);
            qtyEl.value = v;
            btnEl.disabled = (v <= 0);
        });
    });

    // Sort by distance
    document.getElementById('qhSortBtn').addEventListener('click', function () {
        var btn = this;
        var cached = RootedGPS.last();
        if (cached) { doSort(cached); return; }
        btn.textContent = '⏳ Locating…';
        RootedGPS.get(function(pos) {
            if (!pos) { btn.textContent = '📍 Location unavailable'; return; }
            doSort(pos);
        }, 0);
    });

    function doSort(pos) {
        document.getElementById('qhSortBtn').textContent = '📍 Sorted';
        var list  = document.getElementById('qhList');
        var cards = Array.from(list.querySelectorAll('.qh-card'));
        cards.forEach(function(card) {
            var lat = parseFloat(card.dataset.lat);
            var lng = parseFloat(card.dataset.lng);
            var distEl = card.querySelector('.qh-card-dist');
            if (!isNaN(lat) && !isNaN(lng) && lat && lng) {
                var m = hav(pos.lat, pos.lng, lat, lng);
                card._dist = m;
                if (distEl) { distEl.textContent = fmt(m); distEl.style.display = ''; }
            } else {
                card._dist = Infinity;
            }
        });
        cards.sort(function(a,b){return a._dist - b._dist;});
        cards.forEach(function(c){ list.appendChild(c); });
    }

    // Auto-sort if GPS already warm
    var last = RootedGPS.last();
    if (last) doSort(last);

    // Re-sort automatically as GPS accuracy improves
    RootedGPS.onAccuracyImprove(function(pos) { doSort(pos); });

    function hav(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
    function fmt(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}

    // Inline delete confirmation (replaces window.confirm which breaks in PWA mode)
    document.querySelectorAll('.qh-del-trigger').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form    = btn.closest('.qh-del-form');
            var confirm = form.querySelector('.qh-del-confirm');
            btn.style.display = 'none';
            confirm.style.display = 'inline-flex';
        });
    });
    document.querySelectorAll('.qh-del-no').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var form    = btn.closest('.qh-del-form');
            var trigger = form.querySelector('.qh-del-trigger');
            form.querySelector('.qh-del-confirm').style.display = 'none';
            trigger.style.display = '';
        });
    });
}());
</script>

<style>
.qh-page { padding-bottom: calc(var(--bottom-nav-height,80px) + var(--spacing-5)); animation: fadeUp .3s ease-out; }
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

.qh-header {
    display:flex; align-items:center; gap:var(--spacing-3);
    margin-bottom:var(--spacing-4); padding-bottom:var(--spacing-4);
    border-bottom:1px solid var(--color-border);
}
.qh-back {
    display:flex; align-items:center; justify-content:center;
    width:40px; height:40px; border-radius:50%;
    background:var(--color-surface-raised); border:1px solid var(--color-border);
    color:var(--color-text); text-decoration:none; flex-shrink:0;
    transition:background .15s;
}
.qh-back:hover { background:var(--color-primary); color:#fff; border-color:var(--color-primary); }
.qh-title { font-size:1.15rem; font-weight:800; margin:0; flex:1; }
.qh-sort-btn {
    background:var(--color-surface-raised); border:1.5px solid var(--color-border);
    border-radius:var(--radius-pill); padding:6px 12px;
    font-size:.78rem; font-weight:700; cursor:pointer; white-space:nowrap;
    transition:border-color .15s, background .15s;
}
.qh-sort-btn:hover { border-color:var(--color-primary); background:var(--color-primary-soft); }
.qh-empty { text-align:center; padding:var(--spacing-10); color:var(--color-text-muted); font-size:1.5rem; }
.qh-list { display:flex; flex-direction:column; gap:var(--spacing-3); }

.qh-card {
    background:var(--color-surface-raised); border-radius:16px;
    box-shadow:0 2px 12px rgba(0,0,0,.07); overflow:hidden;
    transition:opacity .2s;
}
.qh-card--maxed { opacity:.75; }

/* Card head */
.qh-card-head {
    display:flex; align-items:center; gap:var(--spacing-3);
    padding:var(--spacing-3) var(--spacing-3) 0;
}
.qh-card-icon {
    width:44px; height:44px; border-radius:12px; font-size:1.5rem;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.qh-card-name { font-size:.95rem; font-weight:700; }
.qh-card-meta { display:flex; gap:var(--spacing-2); align-items:center; margin-top:2px; }
.qh-card-dist { font-size:.72rem; color:var(--color-text-muted); }
.qh-card-info { flex:1; }

/* Year badge */
.qh-year-badge {
    background:var(--color-surface); border:1px solid var(--color-border);
    border-radius:var(--radius-pill); padding:4px 10px;
    font-size:.78rem; font-weight:700; color:var(--color-text-muted);
    white-space:nowrap; text-align:center; flex-shrink:0;
}
.qh-year-badge--maxed { background:#e8f5e1; border-color:#a3d9a5; color:#276749; }
.qh-year-label { font-weight:400; font-size:.72rem; }

/* Maxed message */
.qh-maxed-msg {
    margin:var(--spacing-3) var(--spacing-3) var(--spacing-2);
    padding:10px 14px; border-radius:10px;
    background:#e8f5e1; color:#276749;
    font-size:.85rem; font-weight:600; text-align:center;
}

/* Slider */
.qh-slider-wrap { padding:var(--spacing-3) var(--spacing-3) 0; }
.qh-slider-labels { display:flex; align-items:center; gap:var(--spacing-2); margin-bottom:var(--spacing-2); }
.qh-unit-icon { font-size:1.3rem; }
.qh-slider-val { font-size:1.5rem; font-weight:800; min-width:2.5ch; }
.qh-unit-label { font-size:.82rem; color:var(--color-text-muted); }
.qh-slider { width:100%; accent-color:var(--color-primary); cursor:pointer; }
.qh-slider-ticks { display:flex; justify-content:space-between; font-size:.7rem; color:var(--color-text-muted); margin-top:2px; }

/* Harvest button */
.qh-form { padding:var(--spacing-3); }
.qh-harvest-btn {
    width:100%; padding:14px;
    border:none; border-radius:12px; cursor:pointer;
    background:var(--item-color, #2d6a4f); color:#fff;
    font-size:1rem; font-weight:700;
    transition:opacity .15s, transform .1s;
}
.qh-harvest-btn:disabled { opacity:.35; cursor:not-allowed; }
.qh-harvest-btn:not(:disabled):active { transform:scale(.98); }

/* Year entries */
.qh-entries {
    margin:0 var(--spacing-3) var(--spacing-3);
    border:1px solid var(--color-border); border-radius:10px; overflow:hidden;
}
.qh-entries-title {
    font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
    color:var(--color-text-muted); padding:8px 12px;
    background:var(--color-surface); border-bottom:1px solid var(--color-border);
}
.qh-entry-row {
    display:flex; align-items:center; gap:var(--spacing-2);
    padding:8px 12px; border-bottom:1px solid var(--color-border);
}
.qh-entry-row:last-child { border-bottom:none; }
.qh-entry-qty { flex:1; font-size:.85rem; font-weight:600; }
.qh-entry-date { font-size:.78rem; color:var(--color-text-muted); }
.qh-entry-del { margin:0; padding:0; }
.qh-del-btn {
    background:none; border:none; color:var(--color-text-muted);
    font-size:.9rem; cursor:pointer; padding:4px 8px; border-radius:6px;
    transition:background .15s, color .15s;
}
.qh-del-btn:hover { background:#fde8e8; color:#922b21; }
.qh-del-confirm { display:none; align-items:center; gap:4px; }
.qh-del-yes {
    background:#c0392b; color:#fff; border:none; border-radius:6px;
    font-size:.75rem; font-weight:700; padding:4px 8px; cursor:pointer;
}
.qh-del-no {
    background:var(--color-surface); color:var(--color-text-muted);
    border:1px solid var(--color-border); border-radius:6px;
    font-size:.75rem; font-weight:700; padding:4px 8px; cursor:pointer;
}
</style>
