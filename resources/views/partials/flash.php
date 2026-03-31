<?php
$success = getFlash('success');
$error   = getFlash('error');
$errors  = getFlash('errors');
?>
<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?= e($success) ?>
    <button class="alert-close" aria-label="Close">&times;</button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error" role="alert">
    <?= e($error) ?>
    <button class="alert-close" aria-label="Close">&times;</button>
</div>
<?php endif; ?>
<?php if ($errors && is_array($errors)): ?>
<div class="alert alert-error" role="alert">
    <ul class="error-list">
        <?php foreach ($errors as $field => $msg): ?>
            <li><?= e($msg) ?></li>
        <?php endforeach; ?>
    </ul>
    <button class="alert-close" aria-label="Close">&times;</button>
</div>
<?php endif; ?>
