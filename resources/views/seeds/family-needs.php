<?php
$unitLabels = ['kg'=>'kg','g'=>'g','units'=>'units','heads'=>'heads','bunches'=>'bunches','litres'=>'L','jars'=>'jars','other'=>'other'];
?>
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
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Yearly Quantity</label>
                    <input type="number" step="0.1" name="yearly_qty" class="form-input" min="0" placeholder="e.g. 50">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select name="yearly_unit" class="form-input">
                        <?php foreach ($unitLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority (1=top)</label>
                    <input type="number" name="priority" class="form-input" min="1" max="10" value="5">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Need</button>
        </form>
    </div>
</div>

<!-- List -->
<?php if (empty($needs)): ?>
<p class="text-muted">No family needs defined yet.</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:10px">
<?php foreach ($needs as $need): ?>
<div class="card" id="fn-card-<?= (int)$need['id'] ?>">
    <!-- Read view -->
    <div id="fn-view-<?= (int)$need['id'] ?>" style="padding:14px 16px">
        <div style="display:flex;align-items:flex-start;gap:12px">
            <!-- Priority badge -->
            <span style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:var(--color-primary);color:#fff;font-size:0.82rem;font-weight:700;margin-top:2px"><?= (int)$need['priority'] ?></span>
            <!-- Content -->
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:1rem;margin-bottom:2px"><?= e($need['vegetable_name']) ?></div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:0.85rem;color:var(--color-text-muted);margin-bottom:<?= !empty($need['notes']) ? '6px' : '0' ?>">
                    <?php if ($need['yearly_qty'] !== null): ?>
                    <span><strong style="color:var(--color-text)"><?= number_format((float)$need['yearly_qty'], 1) ?> <?= e($need['yearly_unit'] ?? 'kg') ?></strong> / year</span>
                    <?php endif; ?>
                    <?php if ($need['seed_name']): ?>
                    <span>🌱 <?= e($need['seed_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($need['notes'])): ?>
                <div style="font-size:0.82rem;color:var(--color-text-muted);line-height:1.5"><?= nl2br(e($need['notes'])) ?></div>
                <?php endif; ?>
            </div>
            <!-- Actions -->
            <div style="flex-shrink:0;display:flex;gap:6px;align-items:center">
                <button type="button" class="btn btn-ghost btn-sm" onclick="fnEdit(<?= (int)$need['id'] ?>)" title="Edit">✏️</button>
                <button type="button" class="btn btn-ghost btn-sm fn-del-btn" style="color:#dc3545" data-id="<?= (int)$need['id'] ?>" onclick="fnShowDel(this)" title="Remove">✕</button>
            </div>
        </div>
        <!-- Delete confirm (hidden) -->
        <div id="fn-del-<?= (int)$need['id'] ?>" style="display:none;margin-top:10px;padding:10px;background:#fff5f5;border-radius:8px;border:1px solid #fcc;display:flex;align-items:center;gap:10px">
            <span style="font-size:0.9rem;flex:1">Remove <strong><?= e($need['vegetable_name']) ?></strong>?</span>
            <form method="POST" action="<?= url('/family-needs/' . (int)$need['id'] . '/trash') ?>" style="display:inline">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="btn btn-sm" style="background:#dc3545;color:#fff">Yes, remove</button>
            </form>
            <button type="button" class="btn btn-ghost btn-sm" onclick="fnHideDel(<?= (int)$need['id'] ?>)">Cancel</button>
        </div>
    </div>
    <!-- Edit form (hidden) -->
    <div id="fn-edit-<?= (int)$need['id'] ?>" style="display:none;padding:14px 16px;border-top:2px solid var(--color-primary);background:var(--color-surface-alt,#f8f9f5)">
        <form method="POST" action="<?= url('/family-needs/' . (int)$need['id'] . '/update') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Vegetable / Food</label>
                    <input type="text" name="vegetable_name" class="form-input" required value="<?= e($need['vegetable_name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Linked Seed</label>
                    <select name="seed_id" class="form-input">
                        <option value="">— none —</option>
                        <?php foreach ($seeds as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)($need['seed_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?><?= $s['variety'] ? ' ('.$s['variety'].')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Yearly Quantity</label>
                    <input type="number" step="0.1" name="yearly_qty" class="form-input" min="0" value="<?= e($need['yearly_qty'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select name="yearly_unit" class="form-input">
                        <?php foreach ($unitLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($need['yearly_unit'] ?? 'kg') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority (1=top)</label>
                    <input type="number" name="priority" class="form-input" min="1" max="10" value="<?= (int)$need['priority'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3"><?= e($need['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <button type="button" class="btn btn-ghost" onclick="fnEdit(<?= (int)$need['id'] ?>)">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function fnEdit(id) {
    var view = document.getElementById('fn-view-' + id);
    var edit = document.getElementById('fn-edit-' + id);
    var open = edit.style.display !== 'none';
    edit.style.display = open ? 'none' : 'block';
    // hide delete confirm when toggling edit
    fnHideDel(id);
}

function fnShowDel(btn) {
    var id = btn.dataset.id;
    var confirm = document.getElementById('fn-del-' + id);
    btn.style.display = 'none';
    confirm.style.display = 'flex';
    // close edit if open
    document.getElementById('fn-edit-' + id).style.display = 'none';
}

function fnHideDel(id) {
    var confirm = document.getElementById('fn-del-' + id);
    if (!confirm) return;
    confirm.style.display = 'none';
    var card = document.getElementById('fn-card-' + id);
    var btn = card.querySelector('.fn-del-btn');
    if (btn) btn.style.display = '';
}
</script>
