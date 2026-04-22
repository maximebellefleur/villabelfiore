<?php
// Item type label map for display
$itemTypeLabels = [
    'olive_tree'  => 'Olive Tree',
    'almond_tree' => 'Almond Tree',
    'vine'        => 'Vine/Grape',
    'tree'        => 'Tree',
    'garden'      => 'Garden',
    'orchard'     => 'Orchard',
    'bed'         => 'Bed',
    'zone'        => 'Zone',
];

function finAssocLabel(array $entry, array $itemTypeLabels): string {
    $scope = $entry['scope'] ?? 'general';
    if ($scope === 'item' && !empty($entry['item_name'])) {
        return htmlspecialchars($entry['item_name']);
    }
    if ($scope === 'item_type' && !empty($entry['item_type'])) {
        return htmlspecialchars($itemTypeLabels[$entry['item_type']] ?? $entry['item_type']);
    }
    return '<span class="text-muted">General</span>';
}

$csrfToken = e(\App\Support\CSRF::getToken());
?>

<!-- ── Page header ─────────────────────────────────────────── -->
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
    <h1 class="page-title">Finance</h1>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
        <!-- Year selector -->
        <form method="GET" action="<?= url('/finance') ?>" style="margin:0;">
            <select name="year" class="form-input" style="padding:.3rem .6rem;font-size:.9rem;" onchange="this.form.submit()">
                <?php foreach ($yearList as $yr): ?>
                <option value="<?= (int)$yr ?>" <?= (int)$yr === $year ? 'selected' : '' ?>><?= (int)$yr ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?= url('/finance/export?year=' . $year) ?>" class="btn btn-secondary" style="font-size:.85rem;">Export <?= (int)$year ?> CSV</a>
        <a href="<?= url('/finance/export?year=all') ?>" class="btn btn-secondary" style="font-size:.85rem;">Export All CSV</a>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- ── Year stats ────────────────────────────────────────────── -->
<?php
$rev = (float)($totals['total_revenue'] ?? 0);
$cst = (float)($totals['total_cost'] ?? 0);
$net = $rev - $cst;
?>
<div class="grid grid-3" style="margin-bottom:1.5rem;">
    <div class="stat-card stat-card--success">
        <div class="stat-label">Revenue <?= (int)$year ?></div>
        <div class="stat-value"><?= number_format($rev, 2) ?> EUR</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-label">Costs <?= (int)$year ?></div>
        <div class="stat-value"><?= number_format($cst, 2) ?> EUR</div>
    </div>
    <div class="stat-card <?= $net >= 0 ? 'stat-card--success' : 'stat-card--danger' ?>">
        <div class="stat-label">Net <?= (int)$year ?></div>
        <div class="stat-value"><?= ($net >= 0 ? '+' : '') . number_format($net, 2) ?> EUR</div>
    </div>
</div>

<!-- ── Add Entry ─────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body">
        <h3 style="margin-top:0;margin-bottom:1rem;">Add Entry</h3>
        <form method="POST" action="<?= url('/finance') ?>" class="form" id="addEntryForm">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">

            <!-- Scope -->
            <div class="form-group" style="margin-bottom:.75rem;">
                <label class="form-label">Association</label>
                <div style="display:flex;gap:1.2rem;flex-wrap:wrap;padding-top:.2rem;">
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                        <input type="radio" name="scope" value="general" checked onchange="updateScope(this.value)"> General Land
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                        <input type="radio" name="scope" value="item_type" onchange="updateScope(this.value)"> By Item Type
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                        <input type="radio" name="scope" value="item" onchange="updateScope(this.value)"> Specific Item
                    </label>
                </div>
            </div>

            <!-- Item type picker (hidden by default) -->
            <div class="form-group" id="scopeItemTypeRow" style="display:none;margin-bottom:.75rem;">
                <label class="form-label">Item Type</label>
                <select name="item_type" class="form-input">
                    <?php foreach ($itemTypes as $typeKey => $typeDef): ?>
                    <option value="<?= e($typeKey) ?>"><?= e($typeDef['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Specific item picker (hidden by default) -->
            <div class="form-group" id="scopeItemRow" style="display:none;margin-bottom:.75rem;">
                <label class="form-label">Item ID <small class="text-muted">(numeric ID of the item)</small></label>
                <input type="number" name="item_id" class="form-input" placeholder="e.g. 42" style="max-width:160px;">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="entry_type" class="form-input">
                        <option value="cost">Cost</option>
                        <option value="revenue">Revenue</option>
                        <option value="market_reference">Market Reference</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" placeholder="e.g. pruning, harvest">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-input" required>
                </div>
                <div class="form-group form-group--sm">
                    <label class="form-label">Amount (EUR)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-input" required>
                </div>
                <div class="form-group form-group--sm">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes <small class="text-muted">(optional)</small></label>
                <input type="text" name="notes" class="form-input" placeholder="Optional notes">
            </div>

            <button type="submit" class="btn btn-primary">Add Entry</button>
        </form>
    </div>
</div>

<!-- ── Entries Table ──────────────────────────────────────────── -->
<h2 class="section-title" style="margin-bottom:.75rem;">
    Entries for <?= (int)$year ?>
    <small class="text-muted" style="font-size:.8em;font-weight:normal;">(<?= count($entries) ?> records)</small>
</h2>

<?php if (empty($entries)): ?>
<p class="text-muted">No finance entries for <?= (int)$year ?>.</p>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="table" id="financeTable">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Association</th>
            <th>Category</th>
            <th>Label</th>
            <th>Amount</th>
            <th style="white-space:nowrap;">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($entries as $e): ?>
    <?php
        $eid      = (int)$e['id'];
        $eScope   = $e['scope'] ?? 'general';
        $eType    = $e['entry_type'];
        $badgeCls = $eType === 'revenue' ? 'badge-success' : ($eType === 'cost' ? 'badge-warning' : 'badge-secondary');
    ?>
    <!-- ── Display row ── -->
    <tr id="row-display-<?= $eid ?>">
        <td style="white-space:nowrap;"><?= e($e['entry_date']) ?></td>
        <td><span class="badge <?= $badgeCls ?>"><?= e($eType) ?></span></td>
        <td><?= finAssocLabel($e, $itemTypeLabels) ?></td>
        <td><?= e($e['category']) ?></td>
        <td><?= e($e['label']) ?><?php if (!empty($e['notes'])): ?> <small class="text-muted">— <?= e($e['notes']) ?></small><?php endif; ?></td>
        <td style="white-space:nowrap;"><?= number_format((float)$e['amount'], 2) ?> <?= e($e['currency']) ?></td>
        <td style="white-space:nowrap;">
            <button class="btn btn-sm btn-secondary" onclick="editEntry(<?= $eid ?>)" title="Edit">&#9998;</button>
            <button class="btn btn-sm btn-danger" onclick="deleteEntry(<?= $eid ?>, '<?= $csrfToken ?>')" title="Delete">&#x2715;</button>
        </td>
    </tr>
    <!-- ── Inline edit row (hidden) ── -->
    <tr id="row-edit-<?= $eid ?>" style="display:none;background:#fafaf5;">
        <td colspan="7" style="padding:.75rem;">
            <form onsubmit="saveEntry(event, <?= $eid ?>)" style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end;">
                <input type="hidden" name="_token" value="<?= $csrfToken ?>">

                <!-- scope -->
                <div style="display:flex;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Scope</label>
                    <select name="scope" class="form-input" style="font-size:.85rem;" onchange="editScopeChange(this, <?= $eid ?>)">
                        <option value="general"   <?= $eScope === 'general'   ? 'selected' : '' ?>>General</option>
                        <option value="item_type" <?= $eScope === 'item_type' ? 'selected' : '' ?>>Item Type</option>
                        <option value="item"      <?= $eScope === 'item'      ? 'selected' : '' ?>>Item</option>
                    </select>
                </div>

                <!-- item_type (shown when scope=item_type) -->
                <div id="edit-itype-<?= $eid ?>" style="display:<?= $eScope === 'item_type' ? 'flex' : 'none' ?>;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Item Type</label>
                    <select name="item_type" class="form-input" style="font-size:.85rem;">
                        <?php foreach ($itemTypes as $typeKey => $typeDef): ?>
                        <option value="<?= e($typeKey) ?>" <?= ($e['item_type'] ?? '') === $typeKey ? 'selected' : '' ?>><?= e($typeDef['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- item_id (shown when scope=item) -->
                <div id="edit-iid-<?= $eid ?>" style="display:<?= $eScope === 'item' ? 'flex' : 'none' ?>;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Item ID</label>
                    <input type="number" name="item_id" class="form-input" value="<?= (int)($e['item_id'] ?? 0) ?: '' ?>" style="font-size:.85rem;width:90px;">
                </div>

                <!-- entry_type -->
                <div style="display:flex;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Type</label>
                    <select name="entry_type" class="form-input" style="font-size:.85rem;">
                        <option value="cost"             <?= $eType === 'cost'             ? 'selected' : '' ?>>Cost</option>
                        <option value="revenue"          <?= $eType === 'revenue'          ? 'selected' : '' ?>>Revenue</option>
                        <option value="market_reference" <?= $eType === 'market_reference' ? 'selected' : '' ?>>Market Ref</option>
                    </select>
                </div>

                <!-- category -->
                <div style="display:flex;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Category</label>
                    <input type="text" name="category" class="form-input" value="<?= e($e['category']) ?>" style="font-size:.85rem;width:110px;">
                </div>

                <!-- label -->
                <div style="display:flex;flex-direction:column;gap:.2rem;flex:1;min-width:140px;">
                    <label style="font-size:.75rem;font-weight:600;">Label</label>
                    <input type="text" name="label" class="form-input" value="<?= e($e['label']) ?>" required style="font-size:.85rem;">
                </div>

                <!-- amount -->
                <div style="display:flex;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-input" value="<?= number_format((float)$e['amount'], 2, '.', '') ?>" required style="font-size:.85rem;width:100px;">
                </div>

                <!-- date -->
                <div style="display:flex;flex-direction:column;gap:.2rem;">
                    <label style="font-size:.75rem;font-weight:600;">Date</label>
                    <input type="date" name="entry_date" class="form-input" value="<?= e($e['entry_date']) ?>" required style="font-size:.85rem;">
                </div>

                <!-- notes -->
                <div style="display:flex;flex-direction:column;gap:.2rem;flex:1;min-width:120px;">
                    <label style="font-size:.75rem;font-weight:600;">Notes</label>
                    <input type="text" name="notes" class="form-input" value="<?= e($e['notes'] ?? '') ?>" style="font-size:.85rem;">
                </div>

                <div style="display:flex;gap:.4rem;padding-bottom:1px;">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit(<?= $eid ?>)">Cancel</button>
                </div>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<!-- ── All-time totals ────────────────────────────────────────── -->
<?php
$aRev = (float)($allTotals['total_revenue'] ?? 0);
$aCst = (float)($allTotals['total_cost'] ?? 0);
$aNet = $aRev - $aCst;
?>
<div class="card" style="margin-top:2rem;">
    <div class="card-body">
        <h3 style="margin-top:0;margin-bottom:1rem;">All-time Totals</h3>
        <div class="grid grid-3">
            <div class="stat-card stat-card--success">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value"><?= number_format($aRev, 2) ?> EUR</div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-label">Total Costs</div>
                <div class="stat-value"><?= number_format($aCst, 2) ?> EUR</div>
            </div>
            <div class="stat-card <?= $aNet >= 0 ? 'stat-card--success' : 'stat-card--danger' ?>">
                <div class="stat-label">Net</div>
                <div class="stat-value"><?= ($aNet >= 0 ? '+' : '') . number_format($aNet, 2) ?> EUR</div>
            </div>
        </div>
    </div>
</div>

<!-- ── JavaScript ────────────────────────────────────────────── -->
<script>
// Scope radio → show/hide item_type / item_id pickers (Add form)
function updateScope(val) {
    document.getElementById('scopeItemTypeRow').style.display = val === 'item_type' ? '' : 'none';
    document.getElementById('scopeItemRow').style.display     = val === 'item'      ? '' : 'none';
}

// Inline edit scope change (Edit form)
function editScopeChange(sel, id) {
    document.getElementById('edit-itype-' + id).style.display = sel.value === 'item_type' ? 'flex' : 'none';
    document.getElementById('edit-iid-'   + id).style.display = sel.value === 'item'      ? 'flex' : 'none';
}

// Show inline edit row, hide display row
function editEntry(id) {
    document.getElementById('row-display-' + id).style.display = 'none';
    document.getElementById('row-edit-'    + id).style.display = '';
}

// Restore display row, hide edit row
function cancelEdit(id) {
    document.getElementById('row-edit-'    + id).style.display = 'none';
    document.getElementById('row-display-' + id).style.display = '';
}

// Submit inline edit via AJAX
function saveEntry(event, id) {
    event.preventDefault();
    var form = event.target;
    var data = new FormData(form);

    fetch('/finance/' + id + '/update', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(function(r) { return r.json(); })
    .then(function(json) {
        if (json.success) {
            // Full page reload to refresh display values
            window.location.reload();
        } else {
            alert('Save failed. Please try again.');
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
    });
}

// Delete via AJAX with confirmation
function deleteEntry(id, token) {
    if (!confirm('Remove this entry? This cannot be undone.')) return;

    var data = new FormData();
    data.append('_token', token);

    fetch('/finance/' + id + '/trash', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(function(r) { return r.json(); })
    .then(function(json) {
        if (json.success) {
            var dispRow = document.getElementById('row-display-' + id);
            var editRow = document.getElementById('row-edit-'    + id);
            // Fade out then remove
            [dispRow, editRow].forEach(function(row) {
                if (!row) return;
                row.style.transition = 'opacity .3s';
                row.style.opacity    = '0';
                setTimeout(function() { row.remove(); }, 320);
            });
        } else {
            alert('Delete failed. Please try again.');
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
    });
}
</script>
