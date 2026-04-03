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
        <?php foreach ($block['features'] as $fi => $f): ?>
        <?php $ckId = 'rm_' . $version . '_' . $fi; ?>
        <div class="roadmap-feature" id="<?= e($ckId) ?>">
            <button type="button" class="roadmap-feature-toggle btn-link" data-key="<?= e($ckId) ?>" title="Tap to cycle: blank → ✅ done → ❌ problem → blank">
                <span class="roadmap-toggle-icon">○</span>
            </button>
            <div class="roadmap-feature-body">
                <div class="roadmap-feature-title"><?= e($f['title']) ?></div>
                <div class="roadmap-feature-detail"><?= e($f['detail']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endforeach; ?>

<script>
(function() {
    var KEY_PREFIX = 'rooted_roadmap_';
    // States: 0=none, 1=done ✅, 2=problem ❌
    var STATES = [
        { icon: '○', color: '',                         opacity: '1'  },
        { icon: '✅', color: 'var(--color-success,#27ae60)', opacity: '.5' },
        { icon: '❌', color: 'var(--color-danger,#e74c3c)',  opacity: '1'  },
    ];

    function applyState(btn, state) {
        var cfg     = STATES[state];
        var icon    = btn.querySelector('.roadmap-toggle-icon');
        var feature = btn.closest('.roadmap-feature');
        icon.textContent    = cfg.icon;
        btn.style.color     = cfg.color;
        feature.style.opacity = cfg.opacity;
        btn.title = state === 0 ? 'Click: mark done' : state === 1 ? 'Click: mark problem' : 'Click: clear';
    }

    document.querySelectorAll('.roadmap-feature-toggle').forEach(function(btn) {
        var k     = btn.dataset.key;
        var saved = parseInt(localStorage.getItem(KEY_PREFIX + k) || '0', 10);
        if (isNaN(saved) || saved < 0 || saved > 2) saved = 0;
        applyState(btn, saved);

        btn.addEventListener('click', function() {
            var cur  = parseInt(localStorage.getItem(KEY_PREFIX + k) || '0', 10);
            var next = (cur + 1) % 3;
            localStorage.setItem(KEY_PREFIX + k, String(next));
            applyState(btn, next);
        });
    });
}());
</script>
