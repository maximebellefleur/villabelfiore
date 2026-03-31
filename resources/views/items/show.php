<div class="page-header">
    <h1 class="page-title"><?= e($item['name']) ?>
        <span class="badge badge-type"><?= e(str_replace('_', ' ', $item['type'])) ?></span>
        <?php if ($item['status'] !== 'active'): ?>
        <span class="badge badge-status badge-status--<?= e($item['status']) ?>"><?= e($item['status']) ?></span>
        <?php endif; ?>
    </h1>
    <div class="page-actions">
        <a href="/items/<?= (int)$item['id'] ?>/edit" class="btn btn-secondary">Edit</a>
        <form method="POST" action="/items/<?= (int)$item['id'] ?>/trash" style="display:inline">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button class="btn btn-danger" onclick="return confirm('Move to trash?')">Trash</button>
        </form>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="tabs" id="itemTabs">
    <nav class="tab-nav">
        <button class="tab-btn tab-btn--active" data-tab="overview">Overview</button>
        <button class="tab-btn" data-tab="attachments">Attachments (<?= count($attachments) ?>)</button>
        <button class="tab-btn" data-tab="reminders">Reminders (<?= count($reminders) ?>)</button>
        <?php if (!empty($harvests)): ?>
        <button class="tab-btn" data-tab="harvests">Harvests</button>
        <?php endif; ?>
        <?php if (!empty($finances)): ?>
        <button class="tab-btn" data-tab="finance">Finance</button>
        <?php endif; ?>
        <button class="tab-btn" data-tab="log">Activity Log</button>
    </nav>

    <div class="tab-panel tab-panel--active" id="tab-overview">
        <div class="grid grid-2">
            <div>
                <h3>Details</h3>
                <dl class="detail-list">
                    <dt>Type</dt><dd><?= e(str_replace('_', ' ', ucfirst($item['type']))) ?></dd>
                    <?php if ($item['gps_lat'] && $item['gps_lng']): ?>
                    <dt>GPS</dt><dd><?= number_format((float)$item['gps_lat'], 7) ?>, <?= number_format((float)$item['gps_lng'], 7) ?> (<?= e($item['gps_source']) ?>)</dd>
                    <?php endif; ?>
                    <dt>Status</dt><dd><?= e($item['status']) ?></dd>
                    <dt>Created</dt><dd><?= e(date('d M Y', strtotime($item['created_at']))) ?></dd>
                </dl>
            </div>
            <?php if (!empty($meta)): ?>
            <div>
                <h3>Properties</h3>
                <dl class="detail-list">
                    <?php foreach ($meta as $key => $value): ?>
                    <dt><?= e(str_replace('_', ' ', ucfirst($key))) ?></dt>
                    <dd><?= e($value) ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-actions">
            <h3>Log Action</h3>
            <form method="POST" action="/items/<?= (int)$item['id'] ?>/actions" class="form-inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <select name="action_type" class="form-input form-input--sm">
                    <option value="note">Note</option>
                    <option value="pruning">Pruning</option>
                    <option value="treatment">Treatment</option>
                    <option value="amendment">Amendment</option>
                    <option value="harvest">Harvest</option>
                    <option value="maintenance">Maintenance</option>
                </select>
                <input type="text" name="description" class="form-input" placeholder="Description (required)" required>
                <button type="submit" class="btn btn-primary">Log</button>
            </form>
        </div>

        <div class="section-actions">
            <h3>Add Reminder</h3>
            <form method="POST" action="/reminders" class="form-inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <input type="text" name="title" class="form-input" placeholder="Reminder title" required>
                <input type="datetime-local" name="due_at" class="form-input" required>
                <button type="submit" class="btn btn-secondary">Add Reminder</button>
            </form>
        </div>
    </div>

    <div class="tab-panel" id="tab-attachments">
        <h3>Attachments</h3>
        <form method="POST" action="/items/<?= (int)$item['id'] ?>/attachments" enctype="multipart/form-data" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <input type="file" name="file" class="form-input" required>
                <select name="category" class="form-input form-input--sm">
                    <option value="general_attachment">General</option>
                    <option value="identification_photo">Identification Photo</option>
                    <option value="yearly_refresh_north">Yearly North</option>
                    <option value="yearly_refresh_south">Yearly South</option>
                    <option value="yearly_refresh_east">Yearly East</option>
                    <option value="yearly_refresh_west">Yearly West</option>
                </select>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>

        <?php if (!empty($attachments)): ?>
        <div class="attachment-grid">
            <?php foreach ($attachments as $att): ?>
            <div class="attachment-card">
                <?php if (str_starts_with($att['mime_type'], 'image/')): ?>
                <img src="/attachments/<?= (int)$att['id'] ?>/download" class="attachment-thumb" loading="lazy">
                <?php endif; ?>
                <div class="attachment-info">
                    <a href="/attachments/<?= (int)$att['id'] ?>/download" class="attachment-name"><?= e($att['original_filename']) ?></a>
                    <span class="badge badge-sm"><?= e(str_replace('_', ' ', $att['category'])) ?></span>
                </div>
                <form method="POST" action="/attachments/<?= (int)$att['id'] ?>/trash">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-danger">&times;</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">No attachments yet.</p>
        <?php endif; ?>
    </div>

    <div class="tab-panel" id="tab-reminders">
        <h3>Reminders</h3>
        <?php if (empty($reminders)): ?>
        <p class="text-muted">No pending reminders.</p>
        <?php else: ?>
        <ul class="reminder-list">
            <?php foreach ($reminders as $r): ?>
            <li class="reminder-item">
                <span class="reminder-date <?= (strtotime($r['due_at']) < time()) ? 'text-danger' : '' ?>">
                    <?= e(date('d M Y', strtotime($r['due_at']))) ?>
                </span>
                <span class="reminder-title"><?= e($r['title']) ?></span>
                <form method="POST" action="/reminders/<?= (int)$r['id'] ?>/complete" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <button class="btn btn-sm btn-success">Done</button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <?php if (!empty($harvests)): ?>
    <div class="tab-panel" id="tab-harvests">
        <h3>Harvests</h3>
        <form method="POST" action="/items/<?= (int)$item['id'] ?>/harvests" class="form-inline">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <input type="number" step="0.001" name="quantity" class="form-input form-input--sm" placeholder="Quantity" required>
            <input type="text" name="unit" class="form-input form-input--sm" placeholder="Unit (kg, L…)" required>
            <input type="datetime-local" name="recorded_at" class="form-input form-input--sm" required>
            <button type="submit" class="btn btn-primary">Record</button>
        </form>
        <table class="table">
            <thead><tr><th>Date</th><th>Qty</th><th>Unit</th><th>Grade</th><th>Notes</th></tr></thead>
            <tbody>
                <?php foreach ($harvests as $h): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($h['recorded_at']))) ?></td>
                    <td><?= e($h['quantity']) ?></td>
                    <td><?= e($h['unit']) ?></td>
                    <td><?= e($h['quality_grade'] ?? '—') ?></td>
                    <td><?= e($h['notes'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($finances)): ?>
    <div class="tab-panel" id="tab-finance">
        <h3>Finance</h3>
        <a href="/items/<?= (int)$item['id'] ?>/finance" class="btn btn-secondary btn-sm">Full Finance View</a>
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Label</th><th>Amount</th></tr></thead>
            <tbody>
                <?php foreach ($finances as $f): ?>
                <tr>
                    <td><?= e($f['entry_date']) ?></td>
                    <td><span class="badge badge-<?= $f['entry_type'] === 'revenue' ? 'success' : 'warning' ?>"><?= e($f['entry_type']) ?></span></td>
                    <td><?= e($f['label']) ?></td>
                    <td><?= number_format((float)$f['amount'], 2) ?> <?= e($f['currency']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="tab-panel" id="tab-log">
        <h3>Activity Log</h3>
        <?php if (empty($activityLog)): ?>
        <p class="text-muted">No activity recorded yet.</p>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Action</th><th>Description</th></tr></thead>
            <tbody>
                <?php foreach ($activityLog as $a): ?>
                <tr>
                    <td class="text-sm text-muted"><?= e(date('d M Y H:i', strtotime($a['performed_at']))) ?></td>
                    <td><span class="badge"><?= e($a['action_label']) ?></span></td>
                    <td><?= e($a['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
$('.tab-btn').on('click', function() {
    var tab = $(this).data('tab');
    $('.tab-btn').removeClass('tab-btn--active');
    $('.tab-panel').removeClass('tab-panel--active');
    $(this).addClass('tab-btn--active');
    $('#tab-' + tab).addClass('tab-panel--active');
});
</script>
