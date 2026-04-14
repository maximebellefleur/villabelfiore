<div class="page-header">
    <h1 class="page-title">🛏 Bed Planner — <?= e($item['name']) ?></h1>
    <a href="<?= url('/items/' . (int)$item['id']) ?>" class="btn btn-secondary">&larr; Back to Item</a>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Add row form -->
<div class="card" style="margin-bottom:var(--spacing-4)">
    <div class="card-body">
        <h3 style="margin:0 0 var(--spacing-3);font-size:1rem">Add Row</h3>
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/rows') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Season Year</label>
                    <input type="number" name="season_year" class="form-input" value="<?= date('Y') ?>" min="2000" max="2100">
                </div>
                <div class="form-group">
                    <label class="form-label">Row #</label>
                    <input type="number" name="row_number" class="form-input" value="<?= count($rows) + 1 ?>" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Seed</label>
                    <select name="seed_id" class="form-input">
                        <option value="">— none —</option>
                        <?php foreach ($seeds as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?><?= $s['variety'] ? ' ('.$s['variety'].')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Plant Count</label>
                    <input type="number" name="plant_count" class="form-input" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Spacing (cm)</label>
                    <input type="number" name="spacing_used_cm" class="form-input" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Sowing Type</label>
                    <select name="sowing_type" class="form-input">
                        <option value="">—</option>
                        <option value="direct">Direct</option>
                        <option value="nursery">Nursery</option>
                        <option value="both">Both</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Sowing Date</label>
                    <input type="date" name="sowing_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Transplant Date</label>
                    <input type="date" name="transplant_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="planned">Planned</option>
                        <option value="sown">Sown</option>
                        <option value="growing">Growing</option>
                        <option value="harvested">Harvested</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-input" placeholder="Optional notes…">
            </div>
            <button type="submit" class="btn btn-primary">Add Row</button>
        </form>
    </div>
</div>

<!-- Existing rows -->
<?php if (empty($rows)): ?>
<p class="text-muted">No rows planned yet.</p>
<?php else: ?>
<?php
$grouped = [];
foreach ($rows as $row) { $grouped[$row['season_year']][] = $row; }
krsort($grouped);
$statusColors = ['planned'=>'#6b7280','sown'=>'#0369a1','growing'=>'#2d6a4f','harvested'=>'#b45309'];
?>
<?php foreach ($grouped as $year => $yearRows): ?>
<div class="card" style="margin-bottom:var(--spacing-3)">
    <div class="card-body">
        <div class="settings-group-title" style="margin-bottom:var(--spacing-3)"><?= $year ?> Season</div>
        <div style="overflow-x:auto">
            <table style="width:100%;font-size:0.85rem;border-collapse:collapse">
                <thead>
                    <tr style="border-bottom:2px solid var(--color-border)">
                        <th style="text-align:left;padding:4px 8px;font-weight:600">Row</th>
                        <th style="text-align:left;padding:4px 8px;font-weight:600">Seed</th>
                        <th style="text-align:left;padding:4px 8px;font-weight:600">Plants</th>
                        <th style="text-align:left;padding:4px 8px;font-weight:600">Sowing</th>
                        <th style="text-align:left;padding:4px 8px;font-weight:600">Status</th>
                        <th style="text-align:right;padding:4px 8px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($yearRows as $row): ?>
                <tr style="border-bottom:1px solid var(--color-border)">
                    <td style="padding:6px 8px;font-weight:600">#<?= $row['row_number'] ?></td>
                    <td style="padding:6px 8px"><?= $row['seed_name'] ? e($row['seed_name']) : '—' ?></td>
                    <td style="padding:6px 8px"><?= $row['plant_count'] ?? '—' ?><?= $row['spacing_used_cm'] ? ' · '.$row['spacing_used_cm'].'cm' : '' ?></td>
                    <td style="padding:6px 8px"><?= $row['sowing_date'] ? date('d M', strtotime($row['sowing_date'])) : '—' ?></td>
                    <td style="padding:6px 8px"><span style="font-size:0.75rem;font-weight:600;color:<?= $statusColors[$row['status']] ?? '#6b7280' ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td style="padding:6px 8px;text-align:right">
                        <form method="POST" action="<?= url('/bed-rows/' . (int)$row['id'] . '/trash') ?>" style="display:inline" onsubmit="return confirm('Remove this row?')">
                            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:#dc3545">✕</button>
                        </form>
                    </td>
                </tr>
                <?php if ($row['notes']): ?>
                <tr style="border-bottom:1px solid var(--color-border)">
                    <td colspan="6" style="padding:2px 8px 8px;color:var(--color-text-muted);font-size:0.8rem;font-style:italic"><?= e($row['notes']) ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
