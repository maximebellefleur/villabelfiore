<?php
$harvestIcon = [
    'olive_tree'  => '🧺',
    'almond_tree' => '🪣',
    'vine'        => '🍇',
    'tree'        => '🌳',
    'fig_tree'    => '🫐',
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
    <h1 class="qh-title">🌾 Quick Harvest</h1>
    <div class="qh-sort-btn" id="qhSortBtn" title="Sort by distance">📍 Nearest first</div>
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
    $icon  = $harvestIcon[$item['type']] ?? '🌾';
    $color = $harvestColor[$item['type']] ?? '#2d6a4f';
    $unitInfo = $harvestUnit[$item['type']] ?? ['label' => 'units', 'icon' => '📦'];
    $hasGps = !empty($item['gps_lat']) && !empty($item['gps_lng']);
?>
<div class="qh-card" data-item-id="<?= (int)$item['id'] ?>"
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
    </div>

    <!-- Basket slider: 0 to 5 in 0.25 steps -->
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
        <input type="hidden" name="harvest_type" value="general">
        <input type="hidden" name="unit" value="<?= e($unitInfo['label']) ?>">
        <input type="hidden" name="quantity" id="qty_<?= (int)$item['id'] ?>" value="0">
        <!-- No date — server records NOW() -->
        <button type="submit" class="qh-harvest-btn" id="btn_<?= (int)$item['id'] ?>" disabled
                style="--item-color:<?= $color ?>">
            🌾 Harvest it!
        </button>
    </form>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Today's entries -->
<?php if (!empty($recentHarvests)): ?>
<section class="qh-recent">
    <h2 class="qh-recent-title">Today's entries</h2>
    <?php foreach ($recentHarvests as $h): ?>
    <div class="qh-recent-row">
        <strong><?= e($h['item_name']) ?></strong>
        <span class="text-muted"><?= number_format((float)$h['quantity'], 2) ?> <?= e($h['unit']) ?></span>
        <span class="text-muted text-sm"><?= e(date('H:i', strtotime($h['recorded_at']))) ?></span>
    </div>
    <?php endforeach; ?>
</section>
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

    // Sort by distance using RootedGPS
    var sorted = false;
    document.getElementById('qhSortBtn').addEventListener('click', function () {
        var btn = this;
        btn.textContent = '⏳ Locating…';
        RootedGPS.get(function(pos) {
            if (!pos) { btn.textContent = '📍 Location unavailable'; return; }
            btn.textContent = '📍 Sorted by distance';

            var list = document.getElementById('qhList');
            var cards = Array.from(list.querySelectorAll('.qh-card'));

            cards.forEach(function(card) {
                var lat = parseFloat(card.dataset.lat);
                var lng = parseFloat(card.dataset.lng);
                var distEl = card.querySelector('.qh-card-dist');
                if (!isNaN(lat) && !isNaN(lng)) {
                    var m = hav(pos.lat, pos.lng, lat, lng);
                    card._dist = m;
                    if (distEl) { distEl.textContent = fmt(m); distEl.style.display = ''; }
                } else {
                    card._dist = Infinity;
                }
            });

            cards.sort(function(a,b){return a._dist - b._dist;});
            cards.forEach(function(c){ list.appendChild(c); });
        }, 5000);
    });

    function hav(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
    function fmt(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}

    // Auto-sort on load if RootedGPS already has a fix
    var last = RootedGPS.last();
    if (last) document.getElementById('qhSortBtn').click();
}());
</script>

<style>
.qh-page { padding-bottom: calc(var(--bottom-nav-height,80px) + var(--spacing-5)); }

.qh-header {
    display: flex; align-items: center; gap: var(--spacing-3);
    margin-bottom: var(--spacing-5);
}
.qh-back {
    display: flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: var(--radius);
    background: var(--color-surface-raised); border: 1px solid var(--color-border);
    color: var(--color-text); text-decoration: none; flex-shrink: 0;
}
.qh-title { font-size: 1.4rem; font-weight: 800; flex: 1; }
.qh-sort-btn {
    font-size: 0.78rem; font-weight: 600; color: var(--color-primary);
    cursor: pointer; padding: 6px 12px; border-radius: var(--radius-pill);
    border: 1.5px solid var(--color-primary); white-space: nowrap;
    transition: background 0.15s;
}
.qh-sort-btn:hover { background: var(--color-primary-soft); }

.qh-empty { text-align: center; padding: var(--spacing-10) var(--spacing-5); font-size: 1.5rem; }

.qh-list { display: flex; flex-direction: column; gap: var(--spacing-3); }

.qh-card {
    background: var(--color-surface-raised); border: 1px solid var(--color-border);
    border-radius: var(--radius-xl); padding: var(--spacing-4);
    box-shadow: var(--shadow-sm);
}
.qh-card-head { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-4); }
.qh-card-icon {
    width: 50px; height: 50px; border-radius: var(--radius); font-size: 1.6rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.qh-card-info { flex: 1; min-width: 0; }
.qh-card-name { font-size: 1rem; font-weight: 700; }
.qh-card-meta { display: flex; gap: var(--spacing-3); margin-top: 2px; align-items: center; flex-wrap: wrap; }
.qh-card-dist { font-size: 0.78rem; font-weight: 600; color: var(--color-primary); }

/* Slider */
.qh-slider-wrap { margin-bottom: var(--spacing-4); }
.qh-slider-labels { display: flex; align-items: center; gap: var(--spacing-2); margin-bottom: var(--spacing-2); }
.qh-unit-icon { font-size: 1.3rem; }
.qh-slider-val { font-size: 1.8rem; font-weight: 900; color: var(--color-primary); min-width: 40px; text-align: center; }
.qh-unit-label { font-size: 0.78rem; color: var(--color-text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

.qh-slider {
    -webkit-appearance: none; appearance: none;
    width: 100%; height: 8px; border-radius: 999px;
    background: linear-gradient(to right, var(--color-primary) 0%, var(--color-border) 0%);
    outline: none; cursor: pointer;
}
.qh-slider::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--color-primary); border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2); cursor: pointer;
}
.qh-slider::-moz-range-thumb {
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--color-primary); border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2); cursor: pointer;
}
.qh-slider-ticks {
    display: flex; justify-content: space-between;
    font-size: 0.65rem; color: var(--color-text-subtle);
    padding: 2px 4px 0;
}

/* Harvest button */
.qh-harvest-btn {
    width: 100%; padding: var(--spacing-3) var(--spacing-4);
    background: var(--item-color, var(--color-primary));
    color: #fff; border: none; border-radius: var(--radius-pill);
    font-size: 1rem; font-weight: 700; cursor: pointer;
    transition: opacity 0.15s, transform 0.1s;
    font-family: inherit; letter-spacing: .01em;
}
.qh-harvest-btn:disabled { opacity: 0.35; cursor: not-allowed; transform: none; }
.qh-harvest-btn:not(:disabled):hover { opacity: 0.9; transform: translateY(-1px); }
.qh-harvest-btn:not(:disabled):active { transform: scale(0.97); }

/* Recent */
.qh-recent { margin-top: var(--spacing-6); }
.qh-recent-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-muted); margin-bottom: var(--spacing-3); }
.qh-recent-row {
    display: flex; align-items: center; gap: var(--spacing-4);
    padding: var(--spacing-2) 0; border-bottom: 1px solid var(--color-border);
    font-size: 0.875rem;
}
.qh-recent-row:last-child { border-bottom: none; }
</style>

<script>
// Update slider track fill gradient dynamically
document.querySelectorAll('.qh-slider').forEach(function(slider) {
    function updateFill() {
        var pct = (slider.value / slider.max) * 100;
        slider.style.background = 'linear-gradient(to right, var(--color-primary) ' + pct + '%, var(--color-border) ' + pct + '%)';
    }
    slider.addEventListener('input', updateFill);
    updateFill();
});
</script>
