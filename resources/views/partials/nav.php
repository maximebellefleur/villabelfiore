<nav class="nav">
    <div class="nav-brand">
        <a href="<?= url('/dashboard') ?>" class="nav-logo">🌿 Rooted</a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
    </div>
    <ul class="nav-links" id="navLinks">
        <li><a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a></li>
        <li><a href="<?= url('/items') ?>" class="nav-link">Items</a></li>
        <li><a href="<?= url('/reminders') ?>" class="nav-link">Reminders</a></li>
        <li><a href="<?= url('/finance') ?>" class="nav-link">Finance</a></li>
        <li><a href="<?= url('/activity-log') ?>" class="nav-link">Activity</a></li>
        <li><a href="<?= url('/settings') ?>" class="nav-link">Settings</a></li>
        <li>
            <form method="POST" action="<?= url('/logout') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="nav-link btn-link">Sign Out</button>
            </form>
        </li>
    </ul>
</nav>
