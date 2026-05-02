<?php $layout = 'auth';
// Login page has a light background — prefer dark logo variants.
// Priority: horizontal-dark → icon-dark → horizontal-light → icon-light → logo-nav → icon-192 fallback
$_loginIconUrl  = null; // square icon
$_loginHorizUrl = null; // wide text logo
foreach (['svg','png','webp','jpg'] as $_e) {
    if (!$_loginHorizUrl) {
        foreach (['logo-horizontal-dark','logo-horizontal-light','logo-nav'] as $_n) {
            $_f = PUBLIC_PATH . '/assets/images/' . $_n . '.' . $_e;
            if (file_exists($_f)) { $_loginHorizUrl = url('/assets/images/'.$_n.'.'.$_e).'?v='.filemtime($_f); break; }
        }
    }
    if (!$_loginIconUrl) {
        foreach (['logo-icon-dark','logo-icon-light'] as $_n) {
            $_f = PUBLIC_PATH . '/assets/images/' . $_n . '.' . $_e;
            if (file_exists($_f)) { $_loginIconUrl = url('/assets/images/'.$_n.'.'.$_e).'?v='.filemtime($_f); break; }
        }
    }
}
?>
<div class="auth-card">
    <div style="text-align:center;margin-bottom:24px">
        <?php if ($_loginIconUrl && $_loginHorizUrl): ?>
            <img src="<?= $_loginIconUrl ?>" alt="" style="width:64px;height:64px;object-fit:contain;border-radius:14px;display:block;margin:0 auto 10px">
            <img src="<?= $_loginHorizUrl ?>" alt="Rooted" style="max-height:28px;max-width:180px;object-fit:contain">
        <?php elseif ($_loginHorizUrl): ?>
            <img src="<?= $_loginHorizUrl ?>" alt="Rooted" style="max-height:44px;max-width:200px;object-fit:contain">
        <?php elseif ($_loginIconUrl): ?>
            <img src="<?= $_loginIconUrl ?>" alt="Rooted" style="width:64px;height:64px;object-fit:contain;border-radius:14px">
        <?php else: ?>
            <img src="<?= url('/assets/images/icon-192.png') ?>" alt="Rooted" style="width:64px;height:64px;object-fit:contain;border-radius:14px">
        <?php endif; ?>
    </div>
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
