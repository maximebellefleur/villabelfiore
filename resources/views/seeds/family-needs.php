<div class="page-header">
    <h1 class="page-title">👨‍👩‍👧 Family Needs</h1>
    <a href="<?= url('/seeds') ?>" class="btn btn-secondary">&larr; Seed Catalog</a>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Add form -->
<div class="card" style="margin-bottom:var(--spacing-4)">
    <div class="card-body">
        <h3 style="margin:0 0 var(--spacing-3);font-size:1rem">Add Yearly Need</h3>
        <form method="POST" action="<?= url('/seeds/family-needs') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Vegetable / Food <span class="required">*</span></label>
                    <input type="text" name="vegetable_name" class="form-input" required placeholder="e.g. Tomatoes">
                </div>
                <div class="form-group">
                    <label class="form-label">Linked Seed</label>
                    <select name="seed_id" class="form-input">
                        <option value="">— none —</option>
                        <?php foreach ($seeds as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?><?= $s['variety'] ? ' ('.$s['variety'].')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Yearly Need (kg)</label>
                    <input type="number" step="0.1" name="yearly_qty_kg" class="form-input" min="0" placeholder="e.g. 50">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority (1=top, 10=low)</label>
                    <input type="number" name="priority" class="form-input" min="1" max="10" value="5">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-input" placeholder="Optional notes…">
            </div>
            <button type="submit" class="btn btn-primary">Add Need</button>
        </form>
    </div>
</div>

<!-- List -->
<?php if (empty($needs)): ?>
<p class="text-muted">No family needs defined yet.</p>
<?php else: ?>
<div class="card">
    <div class="card-body" style="padding:0">
        <table style="width:100%;font-size:0.875rem;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:2px solid var(--color-border)">
                    <th style="text-align:left;padding:10px 12px;font-weight:600">Priority</th>
                    <th style="text-align:left;padding:10px 12px;font-weight:600">Vegetable</th>
                    <th style="text-align:left;padding:10px 12px;font-weight:600">Linked Seed</th>
                    <th style="text-align:left;padding:10px 12px;font-weight:600">Yearly kg</th>
                    <th style="text-align:left;padding:10px 12px;font-weight:600">Notes</th>
                    <th style="padding:10px 12px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($needs as $need): ?>
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:8px 12px;text-align:center">
                    <span style="display:inline-block;width:26px;height:26px;border-radius:50%;background:var(--color-primary);color:#fff;font-size:0.8rem;font-weight:700;line-height:26px;text-align:center"><?= (int)$need['priority'] ?></span>
                </td>
                <td style="padding:8px 12px;font-weight:600"><?= e($need['vegetable_name']) ?></td>
                <td style="padding:8px 12px;color:var(--color-text-muted)"><?= $need['seed_name'] ? e($need['seed_name']) : '—' ?></td>
                <td style="padding:8px 12px"><?= $need['yearly_qty_kg'] !== null ? number_format((float)$need['yearly_qty_kg'],1).' kg' : '—' ?></td>
                <td style="padding:8px 12px;color:var(--color-text-muted);font-size:0.8rem"><?= e($need['notes'] ?? '') ?></td>
                <td style="padding:8px 12px;text-align:right">
                    <form method="POST" action="<?= url('/family-needs/' . (int)$need['id'] . '/trash') ?>" style="display:inline" onsubmit="return confirm('Remove this need?')">
                        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:#dc3545">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
