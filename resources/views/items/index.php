<?php
$typeEmoji = [
    'olive_tree'  => '🫒', 'tree' => '🌳', 'vine' => '🍇',
    'almond_tree' => '🌰', 'garden' => '🌿', 'zone' => '🛖',
    'orchard'     => '🏕', 'bed' => '🌱', 'line' => '〰️',
    'prep_zone'   => '🟫', 'mobile_coop' => '🐓',
    'building'    => '🏠', 'water_point' => '💧',
];
$typeColor = [
    'olive_tree'  => '#2d6a4f', 'almond_tree' => '#92400e', 'vine' => '#6d28d9',
    'tree'        => '#166534', 'garden' => '#0369a1', 'bed' => '#0369a1',
    'orchard'     => '#c2410c', 'zone' => '#4338ca', 'prep_zone' => '#b45309',
    'water_point' => '#0284c7', 'mobile_coop' => '#991b1b',
    'building'    => '#374151', 'line' => '#1d4ed8',
];
?>
<div class="items-page">

<!-- Header -->
<div class="items-header">
    <div>
        <h1 class="items-title">My Items</h1>
        <p class="items-subtitle"><?= $total ?> item<?= $total !== 1 ? 's' : '' ?> on this land</p>
    </div>
    <a href="<?= url('/items/create') ?>" class="btn btn-primary btn-lg items-add-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Item
    </a>
</div>

<!-- Filter bar -->
<form method="GET" action="<?= url('/items') ?>" class="items-filter" id="itemsFilter">
    <div class="items-filter-row">
        <div class="items-filter-search">
            <svg class="items-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" name="search" class="items-search-input" placeholder="Search items…"
                   value="<?= e($filters['search']) ?>">
        </div>
    </div>
    <div class="items-filter-row items-filter-selects">
        <select name="type" class="items-select">
            <option value="">All types</option>
            <?php foreach ($itemTypes as $typeKey => $typeCfg): ?>
            <option value="<?= e($typeKey) ?>" <?= ($filters['type'] === $typeKey) ? 'selected' : '' ?>>
                <?= ($typeEmoji[$typeKey] ?? '') . ' ' . e($typeCfg['label']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="items-select">
            <option value="active"   <?= ($filters['status'] === 'active')   ? 'selected' : '' ?>>Active</option>
            <option value="archived" <?= ($filters['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
            <option value="trashed"  <?= ($filters['status'] === 'trashed')  ? 'selected' : '' ?>>Trashed</option>
        </select>
        <div class="items-filter-btns">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($filters['search'] || $filters['type'] || $filters['status'] !== 'active'): ?>
            <a href="<?= url('/items') ?>" class="btn btn-ghost btn-sm">✕ Clear</a>
            <?php endif; ?>
        </div>
    </div>
    <!-- Sort by distance button (JS-powered) -->
    <div class="items-sort-bar">
        <span class="items-sort-label">Sort by:</span>
        <button type="button" class="items-sort-btn active" data-sort="default">Name</button>
        <button type="button" class="items-sort-btn" data-sort="distance" id="sortByDist">📍 Distance</button>
    </div>
</form>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Items list -->
<?php if (empty($items)): ?>
<div class="items-empty">
    <div class="items-empty-icon">🌱</div>
    <h2 class="items-empty-title">Nothing here yet</h2>
    <p class="items-empty-text">Add your first item to start mapping your land.</p>
    <a href="<?= url('/items/create') ?>" class="btn btn-primary btn-lg" style="margin-top:var(--spacing-4)">
        + Add First Item
    </a>
</div>
<?php else: ?>

<div class="items-list" id="itemsList">
    <?php foreach ($items as $item):
        $emoji   = $typeEmoji[$item['type']] ?? '📦';
        $color   = $typeColor[$item['type']] ?? '#2d6a4f';
        $label   = ucwords(str_replace('_', ' ', $item['type']));
        $hasGps  = !empty($item['gps_lat']) && !empty($item['gps_lng']);
        $photoId = $photoMap[(int)$item['id']] ?? null;
    ?>
    <div class="item-row <?= $item['status'] !== 'active' ? 'item-row--inactive' : '' ?>"
         data-lat="<?= $hasGps ? e($item['gps_lat']) : '' ?>"
         data-lng="<?= $hasGps ? e($item['gps_lng']) : '' ?>">

        <!-- Left accent bar -->
        <div class="item-row-accent" style="background:<?= $color ?>"></div>

        <!-- Main clickable area -->
        <a href="<?= url('/items/' . (int)$item['id']) ?>" class="item-row-main">
            <?php if ($photoId): ?>
            <div class="item-row-icon item-row-icon--photo">
                <img src="<?= att_url((int)$photoId) ?>" alt="">
            </div>
            <?php else: ?>
            <div class="item-row-icon" style="background:<?= $color ?>18;color:<?= $color ?>"><?= $emoji ?></div>
            <?php endif; ?>
            <div class="item-row-body">
                <div class="item-row-name"><?= e($item['name']) ?></div>
                <div class="item-row-meta">
                    <span class="item-row-type" style="color:<?= $color ?>"><?= e($label) ?></span>
                    <?php if ($hasGps): ?>
                    <span class="item-row-gps">📍</span>
                    <?php endif; ?>
                    <?php if ($item['status'] !== 'active'): ?>
                    <span class="item-row-status"><?= $item['status'] ?></span>
                    <?php endif; ?>
                    <span class="item-row-dist" style="display:none"></span>
                </div>
            </div>
            <svg class="item-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>

        <!-- Action buttons -->
        <div class="item-row-actions">
            <a href="<?= url('/items/' . (int)$item['id'] . '/irrigation') ?>" class="item-row-btn" title="Irrigation">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2C6 8 4 13 4 16a8 8 0 0 0 16 0c0-3-2-8-8-14z"/></svg>
            </a>
            <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="item-row-btn" title="Photo">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </a>
            <a href="<?= url('/items/' . (int)$item['id'] . '/survey') ?>" class="item-row-btn" title="Survey">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            </a>
            <a href="<?= url('/items/' . (int)$item['id'] . '/actions') ?>" class="item-row-btn" title="Log">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Infinite scroll sentinel -->
<div id="scrollSentinel" style="height:1px;margin-top:24px"></div>
<div id="scrollLoader" style="display:none;text-align:center;padding:24px 0">
    <span class="items-spinner"></span>
</div>

</div>

<script>
var BASE_URL   = '<?= url('/') ?>';
var _curPage   = <?= $page ?>;
var _lastPage  = <?= $lastPage ?>;
var _loading   = false;
var _distSortActive = false;
var _filters   = <?= json_encode(['type' => $filters['type'], 'status' => $filters['status'], 'search' => $filters['search']]) ?>;

var TYPE_COLOR = {olive_tree:'#2d6a4f',almond_tree:'#92400e',vine:'#6d28d9',tree:'#166534',garden:'#0369a1',bed:'#0369a1',orchard:'#c2410c',zone:'#4338ca',prep_zone:'#b45309',water_point:'#0284c7',mobile_coop:'#991b1b',building:'#374151',line:'#1d4ed8'};
var TYPE_EMOJI = {olive_tree:'🫒',tree:'🌳',vine:'🍇',almond_tree:'🌰',garden:'🌿',zone:'🛖',orchard:'🏕',bed:'🌱','line':'〰️',prep_zone:'🟫',mobile_coop:'🐓',building:'🏠',water_point:'💧'};

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function buildRow(item, idx) {
    var color   = TYPE_COLOR[item.type] || '#2d6a4f';
    var emoji   = TYPE_EMOJI[item.type] || '📦';
    var label   = item.label;
    var inactive= item.status !== 'active' ? ' item-row--inactive' : '';
    var photoHtml = item.photoId
        ? '<div class="item-row-icon item-row-icon--photo"><img src="'+BASE_URL+'attachments/'+item.photoId+'/download" alt="" loading="lazy"></div>'
        : '<div class="item-row-icon" style="background:'+color+'18;color:'+color+'">'+emoji+'</div>';
    var gpsHtml = item.hasGps
        ? ' <span class="item-row-gps">📍</span>'
          + '<span class="item-row-dist" style="display:none"></span>'
        : '<span class="item-row-dist" style="display:none"></span>';
    var statusHtml = item.status !== 'active'
        ? '<span class="item-row-status">'+esc(item.status)+'</span>' : '';
    var el = document.createElement('div');
    el.className = 'item-row item-row--animate' + inactive;
    el.dataset.id  = item.id;
    el.dataset.lat = item.lat  || '';
    el.dataset.lng = item.lng  || '';
    el.dataset.origIndex = idx;
    el.style.animationDelay = (idx * 40) + 'ms';
    el.innerHTML =
        '<div class="item-row-accent" style="background:'+color+'"></div>'
      + '<a href="'+BASE_URL+'items/'+item.id+'" class="item-row-main">'
      +   photoHtml
      +   '<div class="item-row-body">'
      +     '<div class="item-row-name">'+esc(item.name)+'</div>'
      +     '<div class="item-row-meta">'
      +       '<span class="item-row-type" style="color:'+color+'">'+esc(label)+'</span>'
      +       gpsHtml + statusHtml
      +     '</div>'
      +   '</div>'
      +   '<svg class="item-row-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>'
      + '</a>'
      + '<div class="item-row-actions">'
      +   '<a href="'+BASE_URL+'items/'+item.id+'/irrigation" class="item-row-btn" title="Irrigation"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2C6 8 4 13 4 16a8 8 0 0 0 16 0c0-3-2-8-8-14z"/></svg></a>'
      +   '<a href="'+BASE_URL+'items/'+item.id+'/photos"     class="item-row-btn" title="Photo"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg></a>'
      +   '<a href="'+BASE_URL+'items/'+item.id+'/survey"     class="item-row-btn" title="Survey"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg></a>'
      +   '<a href="'+BASE_URL+'items/'+item.id+'/actions"    class="item-row-btn" title="Log"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>'
      + '</div>';
    return el;
}

function loadNextPage() {
    if (_loading || _curPage >= _lastPage) return;
    _loading = true;
    document.getElementById('scrollLoader').style.display = 'block';

    var params = new URLSearchParams(_filters);
    params.set('page', _curPage + 1);
    params.set('_ajax', '1');

    fetch(BASE_URL + 'items?' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(d) {
            _loading  = false;
            _curPage  = d.page;
            _lastPage = d.lastPage;
            document.getElementById('scrollLoader').style.display = 'none';

            var list  = document.getElementById('itemsList');
            var base  = list.querySelectorAll('.item-row').length;
            d.items.forEach(function(item, i) {
                var row = buildRow(item, i);
                list.appendChild(row);
                // Force reflow then add visible class for animation
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() { row.classList.add('item-row--visible'); });
                });
            });

            if (_distSortActive && _pos) doDistanceSort(_pos);
        })
        .catch(function() {
            _loading = false;
            document.getElementById('scrollLoader').style.display = 'none';
        });
}

// Animate initial rows on load
document.querySelectorAll('.item-row').forEach(function(row, i) {
    row.dataset.origIndex = i;
    row.classList.add('item-row--animate');
    row.style.animationDelay = Math.min(i * 35, 400) + 'ms';
    requestAnimationFrame(function() {
        requestAnimationFrame(function() { row.classList.add('item-row--visible'); });
    });
});

// IntersectionObserver for infinite scroll
var sentinel = document.getElementById('scrollSentinel');
if (sentinel && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting) loadNextPage();
    }, { rootMargin: '200px' });
    observer.observe(sentinel);
}

// ── Distance sort ──────────────────────────────────────────────────
var _pos = null;

function doDistanceSort(pos) {
    var list = document.getElementById('itemsList');
    if (!list) return;
    var rows = Array.from(list.querySelectorAll('.item-row'));
    rows.forEach(function(row) {
        var lat = parseFloat(row.dataset.lat), lng = parseFloat(row.dataset.lng);
        row._dist = (!isNaN(lat) && !isNaN(lng)) ? haversineM(pos.lat, pos.lng, lat, lng) : Infinity;
        var distEl = row.querySelector('.item-row-dist');
        if (distEl) { distEl.textContent = row._dist < Infinity ? fmtDist(row._dist) : ''; distEl.style.display = row._dist < Infinity ? '' : 'none'; }
    });
    rows.sort(function(a,b){ return a._dist - b._dist; });
    rows.forEach(function(row){ list.appendChild(row); });
}

document.querySelectorAll('.items-sort-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.items-sort-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        if (btn.dataset.sort === 'default') {
            _distSortActive = false;
            var list = document.getElementById('itemsList');
            Array.from(list.children).sort(function(a,b){ return a.dataset.origIndex - b.dataset.origIndex; }).forEach(function(r){ list.appendChild(r); });
            list.querySelectorAll('.item-row-dist').forEach(function(el){ el.style.display='none'; });
            return;
        }
        _distSortActive = true;
        btn.textContent = '⏳ Locating…';
        RootedGPS.get(function(pos) {
            btn.textContent = '📍 Distance';
            if (!pos) { btn.classList.remove('active'); _distSortActive=false; return; }
            _pos = pos; doDistanceSort(pos);
        }, 5000);
    });
});

(function() {
    var distBtn = document.getElementById('sortByDist');
    var nameBtn = document.querySelector('[data-sort="default"]');
    if (!distBtn || typeof RootedGPS === 'undefined') return;
    distBtn.classList.add('active'); if (nameBtn) nameBtn.classList.remove('active');
    distBtn.textContent = '⏳ Locating…'; _distSortActive = true;
    RootedGPS.get(function(pos) {
        distBtn.textContent = '📍 Distance';
        if (!pos) { distBtn.classList.remove('active'); if (nameBtn) nameBtn.classList.add('active'); _distSortActive=false; return; }
        _pos = pos; doDistanceSort(pos);
    }, 5000);
}());

RootedGPS.onAccuracyImprove(function(pos) { _pos=pos; if (_distSortActive) doDistanceSort(pos); });

function haversineM(lat1,lon1,lat2,lon2){var R=6371000,d1=(lat2-lat1)*Math.PI/180,d2=(lon2-lon1)*Math.PI/180,a=Math.sin(d1/2)*Math.sin(d1/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(d2/2)*Math.sin(d2/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}
function fmtDist(m){return m<1000?Math.round(m)+' m':(m/1000).toFixed(1)+' km';}

// Re-submit filter form without page reload if user changes filters
document.getElementById('itemsFilter').addEventListener('submit', function() {
    _curPage = 0; _lastPage = 1;
});
</script>

<style>
.items-page { animation: fadeUp 0.25s ease-out; }
@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }

/* Header */
.items-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--spacing-3); margin-bottom: var(--spacing-5); flex-wrap: wrap;
}
.items-title   { font-size: 1.8rem; font-weight: 800; margin: 0; letter-spacing: -0.03em; }
.items-subtitle { font-size: 0.85rem; color: var(--color-text-muted); margin: 4px 0 0; }
.items-add-btn { flex-shrink: 0; }

/* Filter */
.items-filter { margin-bottom: var(--spacing-4); }
.items-filter-row { display: flex; gap: var(--spacing-2); margin-bottom: var(--spacing-2); flex-wrap: wrap; align-items: center; }
.items-filter-selects { flex-wrap: wrap; }
.items-filter-search { position: relative; flex: 1; min-width: 200px; }
.items-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted); pointer-events: none; }
.items-search-input {
    width: 100%; padding: 11px 14px 11px 38px;
    border: 1.5px solid var(--color-border); border-radius: var(--radius-pill);
    font-size: 0.9rem; font-family: inherit; background: var(--color-surface-raised);
    transition: border-color 0.15s, box-shadow 0.15s;
}
.items-search-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px var(--color-primary-soft); }
.items-select {
    padding: 10px 14px; border: 1.5px solid var(--color-border); border-radius: var(--radius-pill);
    font-size: 0.85rem; font-family: inherit; background: var(--color-surface-raised);
    color: var(--color-text); cursor: pointer;
}
.items-select:focus { outline: none; border-color: var(--color-primary); }
.items-filter-btns { display: flex; gap: var(--spacing-2); align-items: center; }

/* Sort bar */
.items-sort-bar { display: flex; align-items: center; gap: var(--spacing-2); padding: var(--spacing-1) 0; }
.items-sort-label { font-size: 0.78rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }
.items-sort-btn {
    background: none; border: 1.5px solid var(--color-border); border-radius: var(--radius-pill);
    padding: 5px 14px; font-size: 0.8rem; font-weight: 600; cursor: pointer;
    color: var(--color-text-muted); transition: all 0.15s; font-family: inherit;
}
.items-sort-btn.active, .items-sort-btn:hover { border-color: var(--color-primary); color: var(--color-primary); background: var(--color-primary-soft); }

/* Empty */
.items-empty { text-align: center; padding: var(--spacing-10) var(--spacing-5); }
.items-empty-icon  { font-size: 4rem; margin-bottom: var(--spacing-4); }
.items-empty-title { font-size: 1.4rem; font-weight: 800; margin-bottom: var(--spacing-2); }
.items-empty-text  { color: var(--color-text-muted); }

/* Items list */
.items-list {
    display: flex; flex-direction: column; gap: 0;
    border: 1px solid var(--color-border); border-radius: var(--radius-xl);
    overflow: hidden; background: var(--color-surface-raised);
    box-shadow: var(--shadow-sm);
}

/* Item row */
.item-row {
    display: flex; align-items: stretch; position: relative;
    border-bottom: 1px solid var(--color-border); transition: background 0.12s;
}
.item-row:last-child { border-bottom: none; }
.item-row:hover { background-color: var(--color-surface); }
.item-row--inactive { opacity: 0.6; }
.item-row-icon--photo { overflow: hidden; padding: 0; }
.item-row-icon--photo img { width:100%;height:100%;object-fit:cover;display:block; }
.item-row--has-photo .item-row-accent   { position: relative; z-index: 1; }

.item-row-accent { width: 4px; flex-shrink: 0; }

.item-row-main {
    display: flex; align-items: center; gap: var(--spacing-3);
    flex: 1; min-width: 0; padding: var(--spacing-3) var(--spacing-3);
    text-decoration: none; color: inherit;
}
.item-row-main:hover { text-decoration: none; color: inherit; }

.item-row-icon {
    width: 44px; height: 44px; border-radius: var(--radius);
    font-size: 1.4rem; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.item-row-body { flex: 1; min-width: 0; }
.item-row-name { font-size: 0.95rem; font-weight: 700; color: var(--color-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-row-meta { display: flex; align-items: center; gap: var(--spacing-2); margin-top: 2px; flex-wrap: wrap; }
.item-row-type { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.item-row-gps  { font-size: 0.72rem; opacity: 0.6; }
.item-row-status { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; padding: 1px 6px; border-radius: var(--radius-pill); background: rgba(0,0,0,0.07); color: var(--color-text-muted); }
.item-row-dist { font-size: 0.75rem; font-weight: 600; color: var(--color-primary); }

.item-row-chevron { color: var(--color-text-subtle); flex-shrink: 0; }

/* Action buttons */
.item-row-actions { display: flex; align-items: center; gap: 1px; padding: 0 6px 0 0; flex-shrink: 0; }
.item-row-btn {
    width: 30px; height: 30px; border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none;
    color: var(--color-text-subtle); transition: background 0.12s, color 0.12s;
}
.item-row-btn:hover { background: var(--color-primary-soft); color: var(--color-primary); text-decoration: none; }

/* Infinite scroll animations */
.item-row--animate { opacity: 0; transform: translateY(16px); }
.item-row--visible { animation: itemRowIn 0.32s ease-out forwards; }
@keyframes itemRowIn { to { opacity: 1; transform: none; } }

/* Spinner */
.items-spinner {
    display: inline-block; width: 24px; height: 24px; border-radius: 50%;
    border: 3px solid var(--color-border); border-top-color: var(--color-primary);
    animation: spinnerSpin 0.7s linear infinite;
}
@keyframes spinnerSpin { to { transform: rotate(360deg); } }
</style>
