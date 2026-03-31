<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Support\CSRF::getToken()) ?>">
    <title><?= e($title ?? 'Rooted') ?> — Rooted</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2d5a27">
</head>
<body>
<?php include BASE_PATH . '/resources/views/partials/nav.php'; ?>
<main class="main-content">
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <?= $content ?>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
