<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <a href="<?= url('/items/create') ?>" class="btn btn-primary">+ Add Item</a>
</div>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="stat-label">Total Active Items</div>
        <div class="stat-value"><?php
            $total = 0;
            foreach ($itemCounts as $c) { $total += (int)$c['cnt']; }
            echo $total;
        ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Overdue Reminders</div>
        <div class="stat-value <?= count($overdueReminders) > 0 ? 'stat-value--warning' : '' ?>">
            <?= count($overdueReminders) ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Upcoming Reminders</div>
        <div class="stat-value"><?= count($upcomingReminders) ?></div>
    </div>
</div>

<?php if (!empty($overdueReminders)): ?>
<section class="section">
    <h2 class="section-title">Overdue Reminders</h2>
    <div class="card-list">
        <?php foreach ($overdueReminders as $r): ?>
        <div class="card card--warning">
            <div class="card-body">
                <span class="badge badge-error">Overdue</span>
                <strong><?= e($r['title']) ?></strong>
                <span class="text-muted"><?= e(date('d M Y', strtotime($r['due_at']))) ?></span>
                <?php if ($r['item_id']): ?>
                <a href="<?= url('/items/' . ((int)$r['item_id'])) ?>" class="link-small">View Item</a>
                <?php endif; ?>
            </div>
            <div class="card-actions">
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/complete') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-success">Done</button>
                </form>
                <form method="POST" action="<?= url('/reminders/' . ((int)$r['id']) . '/dismiss') ?>" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-secondary">Dismiss</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<div class="grid grid-2">
    <section class="section">
        <h2 class="section-title">Upcoming Reminders</h2>
        <?php if (empty($upcomingReminders)): ?>
        <p class="text-muted">No upcoming reminders.</p>
        <?php else: ?>
        <ul class="reminder-list">
            <?php foreach ($upcomingReminders as $r): ?>
            <li class="reminder-item">
                <span class="reminder-date"><?= e(date('d M', strtotime($r['due_at']))) ?></span>
                <span class="reminder-title"><?= e($r['title']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <a href="<?= url('/reminders') ?>" class="link-more">All reminders &rarr;</a>
    </section>

    <section class="section">
        <h2 class="section-title">Recent Activity</h2>
        <?php if (empty($recentActivity)): ?>
        <p class="text-muted">No recent activity.</p>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($recentActivity as $a): ?>
            <li class="activity-item">
                <span class="activity-action"><?= e($a['action_label']) ?></span>
                <span class="activity-desc text-muted"><?= e(mb_strimwidth($a['description'], 0, 60, '…')) ?></span>
                <span class="activity-time text-muted text-sm"><?= e(date('d M H:i', strtotime($a['performed_at']))) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <a href="<?= url('/activity-log') ?>" class="link-more">Full log &rarr;</a>
    </section>
</div>

<?php if (!empty($itemCounts)): ?>
<section class="section">
    <h2 class="section-title">Items by Type</h2>
    <div class="grid grid-4">
        <?php foreach ($itemCounts as $c): ?>
        <a href="<?= url('/items?type=' . (e($c['type']))) ?>" class="stat-card stat-card--link">
            <div class="stat-value"><?= (int)$c['cnt'] ?></div>
            <div class="stat-label"><?= e(ucwords(str_replace('_', ' ', $c['type']))) ?></div>
            <?php if (!empty($harvestByTypeMap[$c['type']])): ?>
            <div class="stat-sub text-muted" style="font-size:.75rem;margin-top:4px">
                <?php
                $parts = [];
                foreach ($harvestByTypeMap[$c['type']] as $unit => $qty) {
                    $parts[] = number_format($qty, 1) . ' ' . e($unit);
                }
                echo implode(', ', $parts) . ' this year';
                ?>
            </div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
