<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Rooted') ?> — Rooted</title>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
    <style>
        .pub-nav {
            background: var(--color-primary);
            padding: 0 var(--spacing-5);
            height: var(--nav-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pub-nav-logo {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
        }
        .pub-nav-login {
            color: rgba(255,255,255,.85);
            font-size: .875rem;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.4);
            padding: 6px 14px;
            border-radius: var(--radius);
        }
        .pub-nav-login:hover { background: rgba(255,255,255,.1); text-decoration: none; }
        .pub-body {
            max-width: 780px;
            margin: 0 auto;
            padding: var(--spacing-8) var(--spacing-5);
        }
        .pub-footer {
            text-align: center;
            padding: var(--spacing-6) var(--spacing-5);
            font-size: .8rem;
            color: var(--color-text-muted);
            border-top: 1px solid var(--color-border);
        }
        .pub-footer a { color: var(--color-text-muted); }
    </style>
</head>
<body>
<nav class="pub-nav">
    <a href="<?= url('/') ?>" class="pub-nav-logo">🌿 Rooted</a>
    <a href="<?= url('/login') ?>" class="pub-nav-login">Sign in</a>
</nav>
<div class="pub-body">
    <?= $content ?>
</div>
<footer class="pub-footer">
    &copy; <?= date('Y') ?> Rooted &mdash;
    <a href="<?= url('/privacy') ?>">Privacy Policy</a>
</footer>
<script>window.APP_BASE = <?= json_encode(defined('APP_BASE') ? APP_BASE : '') ?>;</script>
</body>
</html>
