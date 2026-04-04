<?php
$harvestIcon = [
    'olive_tree'  => '🧺',
    'almond_tree' => '🪣',
    'vine'        => '🍇',
    'tree'        => '🌳',
];
$harvestColor = [
    'olive_tree'  => '#2d6a4f',
    'almond_tree' => '#92400e',
    'vine'        => '#6d28d9',
    'tree'        => '#166534',
];
$harvestUnit = [
    'olive_tree'  => ['label' => 'baskets', 'icon' => '🧺'],
    'almond_tree' => ['label' => 'wheelbarrows', 'icon' => '🪣'],
    'vine'        => ['label' => 'kg', 'icon' => '⚖️'],
    'tree'        => ['label' => 'kg', 'icon' => '⚖️'],
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
    $unitInfo  = $harvestUnit[$item['type']] ?? ['label' => 'units', 'icon' => '📦'];
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
    <?php else: ?>
    <!-- Slider -->
    <div class="qh-slider-wrap">
        <div class="qh-slider-labels">
            <span class="qh-unit-icon"><?= $unitInfo['icon'] ?></span>
            <span class="qh-slider-val" id="val_<?= (int)$item['id'] ?>">0</span>
            <span class="qh-unit-label"><?= $unitInfo['label'] ?></span>
        </div>
        <input type="range" class="qh-slider" id="slider_<?= (int)$item['id'] ?>"
               min="0" max="20" step="1" value="0"
               data-item="<?= (int)$item['id'] ?>">
        <div class="qh-slider-ticks">
            <span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
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
            <form method="POST" action="<?= url('/harvests/' . (int)$entry['id'] . '/trash') ?>" class="qh-entry-del"
                  onsubmit="return confirm('Delete this harvest entry?')">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <input type="hidden" name="_redirect" value="<?= url('/harvest/quick') ?>">
                <button type="submit" class="qh-del-btn" title="Delete">✕</button>
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
    // Slider: value 0-20 maps to 0.00-5.00 in 0.25 steps
    document.querySelectorAll('.qh-slider').forEach(function(slider) {
        var itemId = slider.dataset.item;
        var valEl  = document.getElementById('val_' + itemId);
        var qtyEl  = document.getElementById('qty_' + itemId);
        var btnEl  = document.getElementById('btn_' + itemId);

        slider.addEventListener('input', function() {
            var v = parseFloat(slider.value) * 0.25;
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

    function hav(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
    function fmt(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}
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
</style>
