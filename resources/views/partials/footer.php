<?php
function _loadFooterMenu(): array {
    $path = defined('STORAGE_PATH') ? STORAGE_PATH . '/menus.json' : null;
    if ($path && file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        if (is_array($data) && !empty($data['footer'])) {
            return function_exists('_resolveMenuItems')
                ? _resolveMenuItems($data['footer'])
                : $data['footer'];
        }
    }
    $defaults = require BASE_PATH . '/config/menu_defaults.php';
    $items = $defaults['footer'] ?? [];
    return function_exists('_resolveMenuItems') ? _resolveMenuItems($items) : $items;
}
$_footerLinks = _loadFooterMenu();
if (!empty($_footerLinks)): ?>
<footer class="site-footer">
    <nav class="site-footer__nav">
        <?php foreach ($_footerLinks as $fl): ?>
        <a href="<?= e($fl['href'] ?? $fl['url'] ?? '/') ?>" class="site-footer__link"><?= e($fl['label']) ?></a>
        <?php endforeach; ?>
    </nav>
</footer>
<?php endif; ?>
