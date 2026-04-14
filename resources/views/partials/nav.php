<?php
$navLinks = [
    ['href' => '/dashboard',      'label' => 'Dashboard'],
    ['href' => '/dashboard/map',  'label' => 'Map'],
    ['href' => '/items',          'label' => 'Items'],
    ['href' => '/reminders',      'label' => 'Reminders'],
    ['href' => '/finance',        'label' => 'Finance'],
    ['href' => '/activity-log',   'label' => 'Activity'],
    ['href' => '/settings',       'label' => 'Settings'],
];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>

<!-- ─── Top nav bar ───────────────────────────────────────────────── -->
<nav class="nav" id="mainNav">
    <a href="<?= url('/dashboard') ?>" class="nav-logo">🌿 Rooted</a>

    <!-- Desktop inline links -->
    <ul class="nav-desktop">
        <?php foreach ($navLinks as $nl):
            $active = ($currentPath === url($nl['href'])) ? ' nav-link--active' : '';
        ?>
        <li><a href="<?= url($nl['href']) ?>" class="nav-link<?= $active ?>"><?= $nl['label'] ?></a></li>
        <?php endforeach; ?>
        <li>
            <form method="POST" action="<?= url('/logout') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="nav-link nav-signout">Sign Out</button>
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
<!-- z-index: 9999 in root stacking context, nothing can hide it -->
<div class="nav-drawer" id="navDrawer" aria-hidden="true">
    <div class="nav-drawer-head">
        <span class="nav-drawer-brand">🌿 Rooted</span>
        <button class="nav-drawer-close" id="navDrawerClose" aria-label="Close menu">✕</button>
    </div>
    <ul class="nav-drawer-list">
        <?php foreach ($navLinks as $nl):
            $active = ($currentPath === url($nl['href'])) ? ' class="active"' : '';
        ?>
        <li><a href="<?= url($nl['href']) ?>"<?= $active ?>><?= $nl['label'] ?></a></li>
        <?php endforeach; ?>
        <li class="nav-drawer-signout-row">
            <form method="POST" action="<?= url('/logout') ?>">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit">Sign Out</button>
            </form>
        </li>
    </ul>
</div>

<!-- Dark backdrop -->
<div class="nav-overlay" id="navOverlay"></div>

<!-- ─── Bottom nav ───────────────────────────────────────────────── -->
<nav class="bottom-nav" aria-label="Main navigation">
    <a href="<?= url('/dashboard') ?>"    class="bottom-nav-item" data-bnav="dashboard">
        <span class="bottom-nav-icon">🏠</span><span class="bottom-nav-label">Home</span>
    </a>
    <a href="<?= url('/dashboard/map') ?>" class="bottom-nav-item" data-bnav="map">
        <span class="bottom-nav-icon">🗺</span><span class="bottom-nav-label">Map</span>
    </a>
    <a href="<?= url('/items/create') ?>" class="bottom-nav-fab" aria-label="Add item">
        <span style="line-height:1;font-size:1.7rem">+</span>
    </a>
    <a href="<?= url('/harvest/quick') ?>" class="bottom-nav-item" data-bnav="harvest">
        <span class="bottom-nav-icon">🌾</span><span class="bottom-nav-label">Harvest</span>
    </a>
    <a href="<?= url('/items') ?>"  class="bottom-nav-item" data-bnav="items">
        <span class="bottom-nav-icon">🌿</span><span class="bottom-nav-label">Items</span>
    </a>
</nav>

<script>
(function () {
    /* Bottom nav active state */
    var path = window.location.pathname;
    document.querySelectorAll('.bottom-nav-item[data-bnav]').forEach(function (el) {
        var href = el.getAttribute('href');
        if (href && (path === href || (href.length > 1 && path.startsWith(href)))) {
            el.classList.add('active');
        }
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
