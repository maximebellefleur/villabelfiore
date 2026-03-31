<?php $layout = 'auth'; ?>
<div class="auth-card">
    <h1 class="auth-title">Sign In to Rooted</h1>
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <form method="POST" action="/login" class="form" novalidate>
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

        <button type="submit" class="btn btn-primary btn-full">Sign In</button>
    </form>
</div>
