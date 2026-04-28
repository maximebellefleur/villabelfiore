<?php
// ── SVG icon definitions (Lucide-style, 24×24 viewBox, stroke only) ─────────
function _navIcon(string $name): string {
    $icons = [
        'dashboard' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'map'       => '<polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/>',
        'items'     => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'garden'    => '<path d="m5 11 4-7"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8c.9 0 1.8-.7 2-1.6L20.5 11"/><path d="m9 11 1 9"/><path d="M4.5 15.5h15"/><path d="m15 11-1 9"/>',
        'tasks'     => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'reminders' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'finance'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M6 15h.01M10 15h4"/>',
        'activity'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'harvest'    => '<path d="M2 22 16 8"/><path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94z"/><path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94z"/><path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94z"/>',
        'irrigation' => '<path d="M12 2c0 6-6 8-6 13a6 6 0 0 0 12 0c0-5-6-7-6-13z"/><path d="M12 22v-4"/><path d="M9 17h6"/>',
        'photos'    => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'plus'      => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'cart'      => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
    ];
    $path = $icons[$name] ?? $icons['items'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

$navLinks = [
    ['href' => '/dashboard',      'label' => 'Dashboard', 'icon' => 'dashboard'],
    ['href' => '/dashboard/map',  'label' => 'Map',       'icon' => 'map'],
    ['href' => '/items',          'label' => 'Items',     'icon' => 'items'],
    ['href' => '/garden',         'label' => 'Garden',     'icon' => 'garden'],
    ['href' => '/tasks?tab=achats',     'label' => 'Achats',     'icon' => 'harvest'],
    ['href' => '/tasks?tab=irrigation', 'label' => 'Irrigation', 'icon' => 'irrigation'],
    ['href' => '/tasks',                'label' => 'Tasks',      'icon' => 'tasks'],
    ['href' => '/tasks?tab=reminders',  'label' => 'Reminders',  'icon' => 'reminders'],
    ['href' => '/finance',        'label' => 'Finance',   'icon' => 'finance'],
    ['href' => '/activity-log',   'label' => 'Activity',  'icon' => 'activity'],
    ['href' => '/settings',        'label' => 'Settings',  'icon' => 'settings'],
];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Custom logo check — prefer horizontal-light for top nav, fall back to icon-light, then legacy logo-nav
$_navLogoUrl  = null;
$_navIconUrl  = null;
foreach (['svg','png','webp','jpg'] as $_navExt) {
    if (!$_navLogoUrl) {
        $_navHorizFile = PUBLIC_PATH . '/assets/images/logo-horizontal-light.' . $_navExt;
        if (file_exists($_navHorizFile)) { $_navLogoUrl = url('/assets/images/logo-horizontal-light.'.$_navExt).'?v='.filemtime($_navHorizFile); }
    }
    if (!$_navIconUrl) {
        $_navIconFile = PUBLIC_PATH . '/assets/images/logo-icon-light.' . $_navExt;
        if (file_exists($_navIconFile)) { $_navIconUrl = url('/assets/images/logo-icon-light.'.$_navExt).'?v='.filemtime($_navIconFile); }
    }
}
if (!$_navLogoUrl && !$_navIconUrl) {
    foreach (['png','jpg','webp','svg'] as $_navExt) {
        $_navLegacyFile = PUBLIC_PATH . '/assets/images/logo-nav.' . $_navExt;
        if (file_exists($_navLegacyFile)) { $_navLogoUrl = $_navIconUrl = url('/assets/images/logo-nav.'.$_navExt).'?v='.filemtime($_navLegacyFile); break; }
    }
}
$_navEffective = $_navLogoUrl ?: $_navIconUrl;
?>

<!-- ─── Top nav bar ───────────────────────────────────────────────── -->
<nav class="nav" id="mainNav" style="overflow:hidden">
    <a href="<?= url('/dashboard') ?>" class="nav-logo" style="display:flex;align-items:center;gap:8px">
        <?php if ($_navIconUrl && $_navLogoUrl): ?>
            <img src="<?= $_navIconUrl ?>" alt="" style="height:84px;width:84px;object-fit:cover;flex-shrink:0;padding:4px;border-radius:100px;margin:0 -7px 0 -40px">
            <img src="<?= $_navLogoUrl ?>" alt="Logo" style="height:26px;max-width:120px;object-fit:contain">
        <?php elseif ($_navEffective): ?>
            <img src="<?= $_navEffective ?>" alt="Logo" style="height:44px;width:44px;object-fit:cover;flex-shrink:0;background:#2b552d;padding:4px;border-radius:100px;border:3px solid #fff;margin:0 3px 0 -15px">
        <?php else: ?>🌿 Rooted<?php endif; ?>
    </a>

    <!-- Desktop inline links -->
    <ul class="nav-desktop">
        <?php foreach ($navLinks as $nl):
            $active = ($currentPath === url($nl['href'])) ? ' nav-link--active' : '';
        ?>
        <li>
            <a href="<?= url($nl['href']) ?>" class="nav-link<?= $active ?>">
                <?= _navIcon($nl['icon']) ?>
                <span class="nav-link-text"><?= $nl['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        <li>
            <form method="POST" action="<?= url('/logout') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="nav-link nav-signout">
                    <?= _navIcon('logout') ?>
                    <span class="nav-link-text">Sign Out</span>
                </button>
            </form>
        </li>
    </ul>

    <!-- Hamburger (mobile only) -->
    <button class="nav-hamburger" id="navHamburger" aria-label="Open menu" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <line x1="2" y1="5"  x2="20" y2="5"/>
            <line x1="2" y1="11" x2="20" y2="11"/>
            <line x1="2" y1="17" x2="20" y2="17"/>
        </svg>
    </button>
</nav>

<!-- ─── Mobile drawer — OUTSIDE nav to avoid stacking context ──────── -->
<div class="nav-drawer" id="navDrawer" aria-hidden="true">
    <div class="nav-drawer-head">
        <?php if ($_navIconUrl || $_navEffective): ?>
        <img src="<?= $_navIconUrl ?: $_navEffective ?>" alt="Logo" style="height:28px;max-width:100px;object-fit:contain;display:block;">
        <?php else: ?>
        <span class="nav-drawer-brand">🌿 Rooted</span>
        <?php endif; ?>
        <button class="nav-drawer-close" id="navDrawerClose" aria-label="Close menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <ul class="nav-drawer-list">
        <?php foreach ($navLinks as $nl):
            $active = ($currentPath === url($nl['href'])) ? ' class="active"' : '';
        ?>
        <li>
            <a href="<?= url($nl['href']) ?>"<?= $active ?>>
                <span class="nav-drawer-icon"><?= _navIcon($nl['icon']) ?></span>
                <?= $nl['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li class="nav-drawer-signout-row">
            <form method="POST" action="<?= url('/logout') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit">
                    <span class="nav-drawer-icon"><?= _navIcon('logout') ?></span>
                    Sign Out
                </button>
            </form>
        </li>
    </ul>
</div>

<!-- Dark backdrop -->
<div class="nav-overlay" id="navOverlay"></div>

<!-- ─── Bottom nav ───────────────────────────────────────────────── -->
<nav class="bottom-nav" aria-label="Main navigation">
    <a href="<?= url('/dashboard') ?>" class="bottom-nav-item" data-bnav="home">
        <span class="bottom-nav-icon"><?= _navIcon('dashboard') ?></span>
        <span class="bottom-nav-label">Home</span>
    </a>
    <a href="<?= url('/items') ?>" class="bottom-nav-item" data-bnav="items">
        <span class="bottom-nav-icon"><?= _navIcon('items') ?></span>
        <span class="bottom-nav-label">Items</span>
    </a>
    <a href="<?= url('/dashboard/map') ?>" class="bottom-nav-item" data-bnav="map">
        <span class="bottom-nav-icon"><?= _navIcon('map') ?></span>
        <span class="bottom-nav-label">Map</span>
    </a>
    <a href="<?= url('/items/create') ?>" class="bottom-nav-fab" aria-label="Add item">
        <?= _navIcon('plus') ?>
    </a>
    <a href="<?= url('/garden') ?>" class="bottom-nav-item" data-bnav="garden">
        <span class="bottom-nav-icon"><?= _navIcon('garden') ?></span>
        <span class="bottom-nav-label">Garden</span>
    </a>
    <a href="<?= url('/tasks?tab=achats') ?>" class="bottom-nav-item" data-bnav="harvest">
        <span class="bottom-nav-icon"><?= _navIcon('harvest') ?></span>
        <span class="bottom-nav-label">Achats</span>
    </a>
    <a href="<?= url('/tasks') ?>" class="bottom-nav-item" data-bnav="tasks">
        <span class="bottom-nav-icon"><?= _navIcon('tasks') ?></span>
        <span class="bottom-nav-label">Tasks</span>
    </a>
</nav>

<style>
/* Desktop nav links: icon + text side by side */
.nav-link {
    display: flex;
    align-items: center;
    gap: 5px;
}
.nav-link svg { flex-shrink: 0; opacity: .85; }
.nav-link--active svg { opacity: 1; }
.nav-link-text { font-size: .8rem; }

/* Drawer icon alignment */
.nav-drawer-icon {
    display: inline-flex;
    align-items: center;
    opacity: .7;
    flex-shrink: 0;
}
.nav-drawer-list a,
.nav-drawer-list button {
    display: flex;
    align-items: center;
    gap: 10px;
}
.nav-drawer-list a.active .nav-drawer-icon,
.nav-drawer-list li.active .nav-drawer-icon { opacity: 1; }

/* Bottom nav SVG icons */
.bottom-nav-icon svg { width: 18px; height: 18px; }
.bottom-nav-fab svg  { width: 20px; height: 20px; }
</style>

<script>
(function () {
    /* Bottom nav active state */
    var path = window.location.pathname;
    document.querySelectorAll('.bottom-nav-item[data-bnav]').forEach(function (el) {
        var href = el.getAttribute('href');
        var bnav = el.getAttribute('data-bnav');
        // Home: exact match only (avoid matching /dashboard/map, etc.)
        var isActive = bnav === 'home'
            ? (path === href || path === href + '/')
            : href && (path === href || (href.length > 1 && path.startsWith(href)));
        if (isActive) el.classList.add('active');
    });

    /* Drawer */
    var hamburger = document.getElementById('navHamburger');
    var drawer    = document.getElementById('navDrawer');
    var overlay   = document.getElementById('navOverlay');
    var closeBtn  = document.getElementById('navDrawerClose');

    function openMenu() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        hamburger.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
    }
    function closeMenu() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        hamburger.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
    }

    hamburger.addEventListener('click', function () {
        drawer.classList.contains('open') ? closeMenu() : openMenu();
    });
    closeBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
}());
</script>
