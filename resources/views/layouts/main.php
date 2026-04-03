<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Support\CSRF::getToken()) ?>">
    <title><?= e($title ?? 'Rooted') ?> — Rooted</title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/admin.css') ?>">
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="theme-color" content="#2d5a27">
    <?php if (!empty($mapEnabled)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= url('/assets/css/map.css') ?>">
    <?php endif; ?>
    <?php if (!empty($miniMapEnabled)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= url('/assets/css/map.css') ?>">
    <?php endif; ?>
</head>
<body>
<?php include BASE_PATH . '/resources/views/partials/nav.php'; ?>
<main class="main-content<?= !empty($mapEnabled) ? ' main-content--map' : '' ?>">
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <?= $content ?>
</main>
<script>window.APP_BASE = <?= json_encode(defined('APP_BASE') ? APP_BASE : '') ?>;</script>
<script src="<?= url('/assets/js/gps.js') ?>"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<?php if (!empty($mapEnabled) || !empty($miniMapEnabled)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<?php endif; ?>
<script src="<?= url('/assets/js/app.js') ?>"></script>
<?php if (!empty($mapEnabled)): ?>
<script src="<?= url('/assets/js/map.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($miniMapEnabled)): ?>
<script src="<?= url('/assets/js/minimap.js') ?>"></script>
<?php endif; ?>
</body>
</html>
