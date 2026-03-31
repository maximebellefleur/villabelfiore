<div class="page-header">
    <h1 class="page-title">Items</h1>
    <a href="/items/create" class="btn btn-primary">+ Add Item</a>
</div>

<form method="GET" action="/items" class="filter-bar">
    <input type="text" name="search" class="form-input form-input--sm" placeholder="Search…" value="<?= e($filters['search']) ?>">
    <select name="type" class="form-input form-input--sm">
        <option value="">All Types</option>
        <?php foreach ($itemTypes as $typeKey => $typeCfg): ?>
        <option value="<?= e($typeKey) ?>" <?= ($filters['type'] === $typeKey) ? 'selected' : '' ?>>
            <?= e($typeCfg['label']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="form-input form-input--sm">
        <option value="active"   <?= ($filters['status'] === 'active')   ? 'selected' : '' ?>>Active</option>
        <option value="archived" <?= ($filters['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
        <option value="trashed"  <?= ($filters['status'] === 'trashed')  ? 'selected' : '' ?>>Trashed</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="/items" class="btn btn-link">Clear</a>
</form>

<?php if (empty($items)): ?>
<div class="empty-state">
    <p>No items found. <a href="/items/create">Add your first item</a>.</p>
</div>
<?php else: ?>
<div class="card-list">
    <?php foreach ($items as $item): ?>
    <div class="card">
        <div class="card-body">
            <div class="card-title">
                <a href="/items/<?= (int)$item['id'] ?>"><?= e($item['name']) ?></a>
                <span class="badge badge-type"><?= e(str_replace('_', ' ', $item['type'])) ?></span>
                <?php if ($item['status'] !== 'active'): ?>
                <span class="badge badge-status badge-status--<?= e($item['status']) ?>"><?= e($item['status']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($item['gps_lat'] && $item['gps_lng']): ?>
            <div class="card-meta text-muted text-sm">
                <?= number_format((float)$item['gps_lat'], 5) ?>, <?= number_format((float)$item['gps_lng'], 5) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-actions">
            <a href="/items/<?= (int)$item['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
            <?php if ($item['status'] !== 'trashed'): ?>
            <form method="POST" action="/items/<?= (int)$item['id'] ?>/trash" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button class="btn btn-sm btn-danger" onclick="return confirm('Move to trash?')">Trash</button>
            </form>
            <?php else: ?>
            <form method="POST" action="/items/<?= (int)$item['id'] ?>/restore" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button class="btn btn-sm btn-success">Restore</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($lastPage > 1): ?>
<div class="pagination">
    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
    <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"
       class="pagination-link <?= ($p === $page) ? 'pagination-link--active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>
