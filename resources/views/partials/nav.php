<?php
// ── Icon helper — loads from config/menu_icons.php ──────────────────────────
function _navIcon(string $name, ?string $customSvg = null): string {
    static $icons = null;
    if ($icons === null) { $icons = require BASE_PATH . '/config/menu_icons.php'; }
    $path = $customSvg ?: ($icons[$name] ?? $icons['items'] ?? '');
    return '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

// ── Menu loader — resolves route keys to URLs ────────────────────────────────
function _resolveMenuItems(array $items): array {
    static $routes = null;
    if ($routes === null) { $routes = require BASE_PATH . '/config/menu_routes.php'; }
    foreach ($items as &$item) {
        if (($item['type'] ?? 'custom') === 'route' && !empty($item['route_key'])) {
            $item['href'] = $routes[$item['route_key']]['url'] ?? ($item['url'] ?? '/');
        } else {
            $item['href'] = $item['url'] ?? '/';
        }
        if (!empty($item['children'])) {
            $item['children'] = _resolveMenuItems($item['children']);
        }
    }
    return $items;
}

function _loadNavMenu(): array {
    $path = defined('STORAGE_PATH') ? STORAGE_PATH . '/menus.json' : null;
    if ($path && file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        if (is_array($data) && !empty($data['main'])) {
            return _resolveMenuItems($data['main']);
        }
    }
    $defaults = require BASE_PATH . '/config/menu_defaults.php';
    return _resolveMenuItems($defaults['main']);
}

$navLinks   = _loadNavMenu();
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
<nav class="nav" id="mainNav" style="overflow:visible">
    <a href="<?= url('/dashboard') ?>" class="nav-logo" style="display:flex;align-items:center;gap:8px;overflow-x:visible;overflow-y:hidden;align-self:stretch">
        <?php if ($_navIconUrl && $_navLogoUrl): ?>
            <img src="<?= $_navIconUrl ?>" alt="" style="height:34px;width:34px;object-fit:contain;flex-shrink:0;border-radius:6px">
            <img src="<?= $_navLogoUrl ?>" alt="Logo" style="height:26px;max-width:120px;object-fit:contain">
        <?php elseif ($_navEffective): ?>
            <img src="<?= $_navEffective ?>" alt="Logo" style="height:34px;max-width:150px;object-fit:contain;flex-shrink:0;border-radius:6px">
        <?php else: ?>🌿 Rooted<?php endif; ?>
    </a>

    <!-- Desktop inline links -->
    <ul class="nav-desktop">
        <?php foreach ($navLinks as $nl):
            $hasChildren = !empty($nl['children']);
            $isParentActive = $hasChildren && strpos($currentPath, url($nl['href'])) === 0;
            $active = (!$hasChildren && $currentPath === url($nl['href'])) || $isParentActive ? ' nav-link--active' : '';
        ?>
        <?php if ($hasChildren): ?>
        <li class="nav-has-dropdown">
            <a href="<?= url($nl['href']) ?>" class="nav-link<?= $active ?>">
                <?= _navIcon($nl['icon'] ?? '', $nl['icon_svg'] ?? null) ?>
                <span class="nav-link-text"><?= e($nl['label']) ?></span>
                <svg class="nav-chevron" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </a>
            <ul class="nav-dropdown">
                <?php foreach ($nl['children'] as $child):
                    $childActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY) && strpos($_SERVER['REQUEST_URI'], $child['href']) !== false) ? ' nav-link--active' : '';
                ?>
                <li>
                    <a href="<?= url($child['href']) ?>" class="nav-link<?= $childActive ?>">
                        <?= _navIcon($child['icon'] ?? '', $child['icon_svg'] ?? null) ?>
                        <span class="nav-link-text"><?= e($child['label']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php else: ?>
        <li>
            <a href="<?= url($nl['href']) ?>" class="nav-link<?= $active ?>">
                <?= _navIcon($nl['icon'] ?? '', $nl['icon_svg'] ?? null) ?>
                <span class="nav-link-text"><?= e($nl['label']) ?></span>
            </a>
        </li>
        <?php endif; ?>
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
                <span class="nav-drawer-icon"><?= _navIcon($nl['icon'] ?? '', $nl['icon_svg'] ?? null) ?></span>
                <?= e($nl['label']) ?>
            </a>
        </li>
        <?php foreach ($nl['children'] ?? [] as $child):
            $childActive = strpos($_SERVER['REQUEST_URI'] ?? '', $child['href']) !== false ? ' class="active"' : '';
        ?>
        <li style="padding-left:18px">
            <a href="<?= url($child['href']) ?>"<?= $childActive ?>>
                <span class="nav-drawer-icon"><?= _navIcon($child['icon'] ?? '', $child['icon_svg'] ?? null) ?></span>
                <?= e($child['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
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

<!-- ─── Bottom nav (hardcoded — not menu-editor managed) ─────────── -->
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
    <a href="<?= url('/harvest/quick') ?>" class="bottom-nav-item" data-bnav="harvest">
        <span class="bottom-nav-icon"><?= _navIcon('harvest') ?></span>
        <span class="bottom-nav-label">Harvest</span>
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
.nav-chevron { opacity: .6; margin-left: 1px; flex-shrink: 0; }

/* Desktop dropdown */
.nav-has-dropdown { position: relative; }
.nav-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%);
    background: var(--nav-bg, #1a3a1c);
    border-radius: 10px;
    padding: 6px 0;
    min-width: 168px;
    box-shadow: 0 8px 28px rgba(0,0,0,.35);
    z-index: 500;
    list-style: none;
    margin: 0;
}
.nav-has-dropdown:hover .nav-dropdown,
.nav-has-dropdown:focus-within .nav-dropdown { display: block; }
.nav-dropdown .nav-link {
    padding: 9px 16px;
    border-radius: 0;
    width: 100%;
    white-space: nowrap;
    opacity: 1;
}
.nav-dropdown .nav-link:hover { background: rgba(255,255,255,.08); }
.nav-dropdown .nav-link svg { opacity: .75; }

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
