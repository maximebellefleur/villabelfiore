<?php $layout = 'installer'; ?>
<div class="installer-card">
    <h2>Step 5: Create Admin Account</h2>
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <form method="POST" action="<?= url('/install/finish') ?>" class="form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="admin_name" class="form-input" required>
        </div>

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="admin_email" class="form-input" required>
        </div>

        <div class="form-group">
            <label class="form-label">Password <small>(min 8 characters)</small></label>
            <input type="password" name="admin_password" class="form-input" required minlength="8">
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="admin_password_confirm" class="form-input" required>
        </div>

        <button type="submit" class="btn btn-primary">Complete Installation</button>
    </form>
</div>
