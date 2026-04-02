<nav class="nav">
    <div class="nav-brand">
        <a href="<?= url('/dashboard') ?>" class="nav-logo">🌿 Rooted</a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navLinks">
            <span id="navToggleIcon">&#9776;</span>
        </button>
    </div>
    <ul class="nav-links" id="navLinks" role="navigation">
        <li><a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a></li>
        <li><a href="<?= url('/dashboard/map') ?>" class="nav-link">Map</a></li>
        <li><a href="<?= url('/items') ?>" class="nav-link">Items</a></li>
        <li><a href="<?= url('/reminders') ?>" class="nav-link">Reminders</a></li>
        <li><a href="<?= url('/finance') ?>" class="nav-link">Finance</a></li>
        <li><a href="<?= url('/activity-log') ?>" class="nav-link">Activity</a></li>
        <li><a href="<?= url('/settings') ?>" class="nav-link">Settings</a></li>
        <li>
            <form method="POST" action="<?= url('/logout') ?>" style="display:block;width:100%">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="nav-link btn-link">Sign Out</button>
            </form>
        </li>
    </ul>
</nav>
<div class="nav-overlay" id="navOverlay" aria-hidden="true"></div>

<!-- Bottom Navigation (mobile only) -->
<nav class="bottom-nav" id="bottomNav" aria-label="Main navigation">
    <a href="<?= url('/dashboard') ?>" class="bottom-nav-item" data-bnav="dashboard">
        <span class="bottom-nav-icon">🏠</span>
        <span class="bottom-nav-label">Home</span>
    </a>
    <a href="<?= url('/dashboard/map') ?>" class="bottom-nav-item" data-bnav="map">
        <span class="bottom-nav-icon">🗺</span>
        <span class="bottom-nav-label">Map</span>
    </a>
    <a href="<?= url('/items/create') ?>" class="bottom-nav-fab" id="bottomFab" aria-label="Add item">
        <span style="line-height:1;font-size:1.7rem">+</span>
    </a>
    <a href="<?= url('/items') ?>" class="bottom-nav-item" data-bnav="items">
        <span class="bottom-nav-icon">🌿</span>
        <span class="bottom-nav-label">Items</span>
    </a>
    <a href="<?= url('/settings') ?>" class="bottom-nav-item" data-bnav="settings">
        <span class="bottom-nav-icon">⚙️</span>
        <span class="bottom-nav-label">Settings</span>
    </a>
</nav>
<script>
(function() {
    var path = window.location.pathname;
    document.querySelectorAll('.bottom-nav-item[data-bnav]').forEach(function(el) {
        var href = el.getAttribute('href');
        if (href && (path === href || (href !== '/' && path.startsWith(href)))) {
            el.classList.add('active');
        }
    });
})();
</script>

<script>
(function() {
    var toggle  = document.getElementById('navToggle');
    var links   = document.getElementById('navLinks');
    var overlay = document.getElementById('navOverlay');
    var icon    = document.getElementById('navToggleIcon');

    function openMenu() {
        links.classList.add('open');
        overlay.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        icon.innerHTML = '&#10005;'; /* × close icon */
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        links.classList.remove('open');
        overlay.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        icon.innerHTML = '&#9776;'; /* ☰ hamburger */
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', function() {
        if (links.classList.contains('open')) { closeMenu(); } else { openMenu(); }
    });

    overlay.addEventListener('click', closeMenu);

    /* Close on Escape */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && links.classList.contains('open')) { closeMenu(); }
    });

    /* Mark active link */
    var path = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(function(a) {
        if (a.getAttribute('href') && path === a.getAttribute('href')) {
            a.classList.add('active');
        }
    });
})();
</script>
