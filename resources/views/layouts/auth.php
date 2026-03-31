<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Rooted') ?> — Rooted</title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-container">
    <?= $content ?>
</div>
<script>window.APP_BASE = <?= json_encode(defined('APP_BASE') ? APP_BASE : '') ?>;</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="<?= url('/assets/js/app.js') ?>"></script>
</body>
</html>
