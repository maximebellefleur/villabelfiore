<div class="page-header">
    <h1 class="page-title">Settings — Upcoming Features</h1>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary">&larr; Back to Settings</a>
</div>

<p class="text-muted" style="margin-bottom:var(--spacing-5)">
    This page tracks every feature — released, in progress, and planned — so you always know what's coming and when it arrived.
</p>

<?php
$statusConfig = [
    'released'    => ['label' => 'Released',     'class' => 'roadmap-badge--released',    'icon' => '✅'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'roadmap-badge--in-progress', 'icon' => '🔨'],
    'planned'     => ['label' => 'Planned',      'class' => 'roadmap-badge--planned',     'icon' => '📋'],
    'future'      => ['label' => 'Future',       'class' => 'roadmap-badge--future',      'icon' => '🔭'],
];

foreach ($roadmap as $version => $block):
    $sc = $statusConfig[$block['status']] ?? $statusConfig['future'];
?>

<div class="roadmap-block roadmap-block--<?= e($block['status']) ?>">
    <div class="roadmap-block-header">
        <div class="roadmap-block-title">
            <span class="roadmap-version">v<?= e($version) ?></span>
            <span class="roadmap-name"><?= e($block['title']) ?></span>
        </div>
        <div class="roadmap-block-meta">
            <span class="roadmap-badge <?= $sc['class'] ?>"><?= $sc['icon'] ?> <?= $sc['label'] ?></span>
            <?php if ($block['released']): ?>
            <span class="roadmap-date">Released <?= e($block['released']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="roadmap-feature-list">
        <?php foreach ($block['features'] as $f): ?>
        <div class="roadmap-feature">
            <div class="roadmap-feature-check"><?= $block['status'] === 'released' ? '✓' : '○' ?></div>
            <div class="roadmap-feature-body">
                <div class="roadmap-feature-title"><?= e($f['title']) ?></div>
                <div class="roadmap-feature-detail"><?= e($f['detail']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endforeach; ?>
