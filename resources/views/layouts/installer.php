<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Install Rooted') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
<div class="installer-container">
    <div class="installer-header">
        <h1 class="installer-logo">🌿 Rooted</h1>
        <p class="installer-tagline">Land Management System</p>
    </div>
    <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <?= $content ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
