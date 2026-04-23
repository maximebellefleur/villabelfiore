<?php $layout = 'auth'; ?>
<div class="auth-card">
    <h1 class="auth-title">Sign In to Rooted</h1>
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <form method="POST" action="<?= url('/login') ?>" class="form" novalidate>
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input"
                   value="<?= e(getFlash('old')['email'] ?? '') ?>"
                   autocomplete="email" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input"
                   autocomplete="current-password" required>
        </div>

        <label for="remember_me" style="display:flex;align-items:center;gap:8px;margin-bottom:var(--spacing-4);cursor:pointer;width:fit-content">
            <input type="checkbox" id="remember_me" name="remember_me" value="1" checked
                   style="width:17px;height:17px;accent-color:var(--color-primary);cursor:pointer;flex-shrink:0;margin:0">
            <span style="font-size:.88rem;color:var(--color-text)">Remember me for 30 days</span>
        </label>

        <button type="submit" class="btn btn-primary btn-full">Sign In</button>
    </form>
    <p style="text-align:center;margin-top:var(--spacing-4);font-size:.8rem;color:var(--color-text-muted)">
        <a href="<?= url('/privacy') ?>" style="color:var(--color-text-muted)">Privacy Policy</a>
    </p>
</div>
