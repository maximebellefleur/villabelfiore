<?php
$unitLabels = ['kg'=>'kg','g'=>'g','units'=>'units','heads'=>'heads','bunches'=>'bunches','litres'=>'L','jars'=>'jars','other'=>'other'];
// Build JS seed list for multi-select
$seedsJs = json_encode(array_map(fn($s) => [
    'id'    => (int)$s['id'],
    'label' => $s['name'] . ($s['variety'] ? ' ('.$s['variety'].')' : ''),
], $seeds));
?>
<style>
/* Multi-seed select component */
.fn-msel { position:relative; }
.fn-msel-field { min-height:40px;padding:5px 34px 5px 10px;border:1px solid var(--color-border);border-radius:var(--radius);background:var(--color-surface-raised);cursor:pointer;display:flex;flex-wrap:wrap;align-items:center;gap:4px;position:relative; }
.fn-msel-field:focus { outline:2px solid var(--color-primary); }
.fn-msel-placeholder { font-size:.85rem;color:var(--color-text-muted);user-select:none; }
.fn-msel-chip { display:inline-flex;align-items:center;gap:4px;padding:2px 8px 2px 8px;background:var(--color-primary);color:#fff;border-radius:999px;font-size:.75rem;font-weight:600;line-height:1.4 }
.fn-msel-chip-x { cursor:pointer;opacity:.7;font-size:.85rem;line-height:1 }
.fn-msel-chip-x:hover { opacity:1 }
.fn-msel-arrow { position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--color-text-muted);font-size:.85rem }
.fn-msel-panel { display:none;position:absolute;z-index:500;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-lg);box-shadow:0 4px 20px rgba(0,0,0,.12);max-height:260px;overflow:hidden;flex-direction:column }
.fn-msel-panel.is-open { display:flex }
.fn-msel-search { padding:8px 10px;border:none;border-bottom:1px solid var(--color-border);outline:none;font-size:.85rem;width:100%;box-sizing:border-box }
.fn-msel-opts { overflow-y:auto;flex:1 }
.fn-msel-opt { display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.85rem }
.fn-msel-opt:hover { background:var(--color-surface-alt,#f5f5f0) }
.fn-msel-opt input[type=checkbox] { width:15px;height:15px;accent-color:var(--color-primary);flex-shrink:0;cursor:pointer }
.fn-msel-empty { padding:12px;text-align:center;color:var(--color-text-muted);font-size:.82rem }
</style>

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
                    <label class="form-label">Linked Seeds <span style="font-size:.75rem;color:var(--color-text-muted)">(any variety works)</span></label>
                    <?php fnMselHtml('fn-msel-add', [], $seeds); ?>
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
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
<?php foreach ($needs as $need):
    $inGround      = (int)($need['plants_in_ground'] ?? 0);
    $planned       = (int)($need['plants_planned'] ?? 0);
    $linkedIds     = $need['linked_seed_ids'] ?? [];
    $linkedNames   = $need['linked_seed_names'] ?? [];
    $hasSeed       = !empty($linkedIds);
    $harvestByYear = $need['harvest_by_year'] ?? [];
    $harvestedTotal = 0;
    foreach ($harvestByYear as $hy) $harvestedTotal += (int)$hy['total'];
?>
<div class="card" id="fn-card-<?= (int)$need['id'] ?>">
    <!-- Read view -->
    <div id="fn-view-<?= (int)$need['id'] ?>" style="padding:14px 16px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px">
            <div style="display:flex;align-items:center;gap:10px;min-width:0">
                <span style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--color-primary);color:#fff;font-size:0.78rem;font-weight:700"><?= (int)$need['priority'] ?></span>
                <div style="min-width:0">
                    <div style="font-weight:700;font-size:.95rem;line-height:1.2"><?= e($need['vegetable_name']) ?></div>
                    <?php if ($linkedNames): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px">
                        <?php foreach ($linkedNames as $sn): ?>
                        <span style="font-size:.7rem;padding:1px 7px;border-radius:999px;background:rgba(var(--color-primary-rgb,45,106,79),.1);color:var(--color-primary);white-space:nowrap">🌱 <?= e($sn) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="flex-shrink:0;display:flex;gap:4px">
                <button type="button" class="btn btn-ghost btn-sm" onclick="fnEdit(<?= (int)$need['id'] ?>)" title="Edit">✏️</button>
                <button type="button" class="btn btn-ghost btn-sm fn-del-btn" style="color:#dc3545" data-id="<?= (int)$need['id'] ?>" onclick="fnShowDel(this)" title="Remove">✕</button>
            </div>
        </div>

        <?php if ($need['yearly_qty'] !== null): ?>
        <div style="font-size:.82rem;color:var(--color-text-muted);margin-bottom:8px">
            Goal: <strong style="color:var(--color-text)"><?= number_format((float)$need['yearly_qty'], 1) ?> <?= e($need['yearly_unit'] ?? 'kg') ?></strong> / year
        </div>
        <?php endif; ?>

        <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:<?= (!empty($need['notes']) || !empty($harvestByYear)) ? '10px' : '0' ?>">
            <?php if ($inGround > 0): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:7px">
                <span style="font-size:.78rem;font-weight:600;color:#16a34a">🌱 <?= $inGround ?> in ground</span>
            </div>
            <?php endif; ?>
            <?php if ($planned > 0): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#fffbeb;border:1px solid #fde68a;border-radius:7px">
                <span style="font-size:.78rem;font-weight:600;color:#d97706">📋 <?= $planned ?> planned</span>
            </div>
            <?php endif; ?>
            <?php if ($inGround === 0 && $planned === 0): ?>
            <div style="font-size:.78rem;color:var(--color-text-muted);padding:4px 0"><?= $hasSeed ? 'Not yet planted' : 'No seed linked' ?></div>
            <?php endif; ?>
            <?php if (!empty($harvestByYear)): ?>
            <?php foreach ($harvestByYear as $hy): ?>
            <div style="padding:5px 10px;background:#f5f5f0;border:1px solid #e2e2dc;border-radius:7px">
                <span style="font-size:.76rem;color:var(--color-text-muted)">🌾 <?= (int)$hy['total'] ?> harvested in <?= (int)$hy['year'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($need['notes'])): ?>
        <div style="font-size:0.78rem;color:var(--color-text-muted);line-height:1.5;border-top:1px solid var(--color-border);padding-top:8px"><?= nl2br(e($need['notes'])) ?></div>
        <?php endif; ?>

        <div id="fn-del-<?= (int)$need['id'] ?>" style="display:none;margin-top:10px;padding:10px;background:#fff5f5;border-radius:8px;border:1px solid #fcc;align-items:center;gap:10px">
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
                    <label class="form-label">Linked Seeds</label>
                    <?php fnMselHtml('fn-msel-' . (int)$need['id'], $linkedIds, $seeds); ?>
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
                    <label class="form-label">Priority</label>
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

<?php
function fnMselHtml(string $uid, array $selectedIds, array $seeds): void {
    $chips = '';
    foreach ($seeds as $s) {
        if (in_array((int)$s['id'], $selectedIds)) {
            $label = htmlspecialchars($s['name'] . ($s['variety'] ? ' ('.$s['variety'].')' : ''), ENT_QUOTES);
            $chips .= '<span class="fn-msel-chip" data-id="'.(int)$s['id'].'">'.$label.'<span class="fn-msel-chip-x" onclick="fnMselRemove(event,\''.$uid.'\',' . (int)$s['id'] . ')">×</span></span>';
        }
    }
    $placeholder = $chips ? '' : '<span class="fn-msel-placeholder">Select seeds…</span>';
    echo '<div class="fn-msel" id="'.$uid.'">';
    echo   '<div class="fn-msel-field" tabindex="0" onclick="fnMselOpen(event,\''.$uid.'\')">';
    echo     '<span class="fn-msel-inner">'.$chips.$placeholder.'</span>';
    echo     '<span class="fn-msel-arrow">▾</span>';
    echo   '</div>';
    echo   '<div class="fn-msel-panel" id="'.$uid.'-panel">';
    echo     '<input type="text" class="fn-msel-search" placeholder="Search seeds…" oninput="fnMselSearch(this,\''.$uid.'\')">';
    echo     '<div class="fn-msel-opts">';
    foreach ($seeds as $s) {
        $label   = htmlspecialchars($s['name'] . ($s['variety'] ? ' ('.$s['variety'].')' : ''), ENT_QUOTES);
        $checked = in_array((int)$s['id'], $selectedIds) ? ' checked' : '';
        echo '<label class="fn-msel-opt" data-label="'.strtolower($s['name'].' '.$s['variety']).'">';
        echo   '<input type="checkbox" name="seed_ids[]" value="'.(int)$s['id'].'"'.$checked.' onchange="fnMselChange(\''.$uid.'\',' . (int)$s['id'] . ','.json_encode($label).',this.checked)">';
        echo   $label;
        echo '</label>';
    }
    if (empty($seeds)) echo '<div class="fn-msel-empty">No seeds in catalog</div>';
    echo     '</div>';
    echo   '</div>';
    echo '</div>';
}
?>

<script>
var FN_SEEDS = <?= $seedsJs ?>;

function fnMselOpen(e, uid) {
    e.stopPropagation();
    var panel = document.getElementById(uid + '-panel');
    var isOpen = panel.classList.contains('is-open');
    // close all other panels
    document.querySelectorAll('.fn-msel-panel.is-open').forEach(function(p){ p.classList.remove('is-open'); });
    if (!isOpen) {
        panel.classList.add('is-open');
        panel.querySelector('.fn-msel-search').focus();
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.fn-msel')) {
        document.querySelectorAll('.fn-msel-panel.is-open').forEach(function(p){ p.classList.remove('is-open'); });
    }
});

function fnMselSearch(inp, uid) {
    var q = inp.value.toLowerCase();
    document.querySelectorAll('#' + uid + '-panel .fn-msel-opt').forEach(function(opt) {
        opt.style.display = (!q || opt.dataset.label.includes(q)) ? '' : 'none';
    });
}

function fnMselChange(uid, seedId, label, checked) {
    var inner = document.querySelector('#' + uid + ' .fn-msel-inner');
    if (checked) {
        // add chip
        var chip = document.createElement('span');
        chip.className = 'fn-msel-chip';
        chip.dataset.id = seedId;
        chip.innerHTML = label + '<span class="fn-msel-chip-x" onclick="fnMselRemove(event,\'' + uid + '\',' + seedId + ')">×</span>';
        var ph = inner.querySelector('.fn-msel-placeholder');
        if (ph) ph.remove();
        inner.appendChild(chip);
    } else {
        fnMselRemoveChip(uid, seedId);
    }
    fnMselSyncPlaceholder(uid);
}

function fnMselRemove(e, uid, seedId) {
    e.stopPropagation();
    fnMselRemoveChip(uid, seedId);
    // uncheck the checkbox
    var cb = document.querySelector('#' + uid + '-panel input[value="' + seedId + '"]');
    if (cb) cb.checked = false;
    fnMselSyncPlaceholder(uid);
}

function fnMselRemoveChip(uid, seedId) {
    var chip = document.querySelector('#' + uid + ' .fn-msel-chip[data-id="' + seedId + '"]');
    if (chip) chip.remove();
}

function fnMselSyncPlaceholder(uid) {
    var inner = document.querySelector('#' + uid + ' .fn-msel-inner');
    if (!inner.querySelector('.fn-msel-chip')) {
        if (!inner.querySelector('.fn-msel-placeholder')) {
            var ph = document.createElement('span');
            ph.className = 'fn-msel-placeholder';
            ph.textContent = 'Select seeds…';
            inner.appendChild(ph);
        }
    } else {
        var ph = inner.querySelector('.fn-msel-placeholder');
        if (ph) ph.remove();
    }
}

function fnEdit(id) {
    var edit = document.getElementById('fn-edit-' + id);
    var open = edit.style.display !== 'none';
    edit.style.display = open ? 'none' : 'block';
    fnHideDel(id);
}

function fnShowDel(btn) {
    var id = btn.dataset.id;
    document.getElementById('fn-del-' + id).style.display = 'flex';
    btn.style.display = 'none';
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
