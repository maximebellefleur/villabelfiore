<?php
$monthNames  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$typeEmoji   = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'🌾'];
$plantMonths = !empty($seed['planting_months']) ? json_decode($seed['planting_months'], true) : [];
$harvMonths  = !empty($seed['harvest_months'])  ? json_decode($seed['harvest_months'], true)  : [];
$companions  = !empty($seed['companions'])  ? json_decode($seed['companions'],  true) : [];
$antagonists = !empty($seed['antagonists']) ? json_decode($seed['antagonists'], true) : [];
$low         = $seed['stock_enabled'] && $seed['stock_low_threshold'] !== null && (float)$seed['stock_qty'] <= (float)$seed['stock_low_threshold'];
$needsRestock = !empty($seed['needs_restock']);
?>
<style>
.seed-grid { display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-4); }
@media (max-width:640px) {
    .seed-grid { grid-template-columns:1fr; }
    .seed-col-right { order:-1; }
}
.btn-restock-on  { background:#dc3545;color:#fff;border-color:#dc3545 }
.btn-restock-on:hover { background:#b02a37 }
</style>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= ($typeEmoji[$seed['type']] ?? '🌾') . ' ' . e($seed['name']) ?></h1>
        <?php if ($seed['variety']): ?><p class="text-muted" style="margin:0"><?= e($seed['variety']) ?><?php if ($seed['botanical_family']): ?> · <em><?= e($seed['botanical_family']) ?></em><?php endif; ?></p><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button type="button" id="rgAddToBedBtn" class="btn btn-primary" style="background:#111;border-color:#111">🛏 Add to Bed</button>
        <!-- Out-of-seed toggle -->
        <form method="POST" action="<?= url('/seeds/' . (int)$seed['id'] . '/toggle-restock') ?>">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <button type="submit" class="btn <?= $needsRestock ? 'btn-restock-on' : 'btn-secondary' ?>" title="<?= $needsRestock ? 'Remove from buy list' : 'Mark as out of seed — adds to buy list' ?>">
                <?= $needsRestock ? '🛒 Out of seed' : '🌱 In stock' ?>
            </button>
        </form>
        <a href="<?= url('/seeds/' . (int)$seed['id'] . '/edit') ?>" class="btn btn-secondary">Edit</a>
        <a href="<?= url('/seeds') ?>" class="btn btn-ghost">&larr; Back</a>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if ($needsRestock): ?>
<div style="background:#fff5f5;border:1px solid #dc3545;border-radius:var(--radius);padding:10px 16px;margin-bottom:var(--spacing-3);font-size:0.875rem;color:#dc3545;display:flex;align-items:center;gap:8px">
    🛒 <strong><?= e($seed['name']) ?></strong> is on your <a href="<?= url('/seeds/buy-list') ?>" style="color:#dc3545;text-decoration:underline">Buy List</a>.
</div>
<?php endif; ?>

<div class="seed-grid">

    <!-- Left column -->
    <div>
        <!-- Growing info card -->
        <div class="card" style="margin-bottom:var(--spacing-3)">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-3)">Growing Info</div>
                <table style="width:100%;font-size:0.875rem;border-collapse:collapse">
                    <?php $rows = [
                        'Type'            => ucfirst($seed['type']),
                        'Sowing'          => ucfirst(str_replace('_',' ',$seed['sowing_type'] ?? '')),
                        'Germination'     => $seed['days_to_germinate'] ? $seed['days_to_germinate'].' days' : '—',
                        'Maturity'        => $seed['days_to_maturity']  ? $seed['days_to_maturity'].' days'  : '—',
                        'Spacing'         => $seed['spacing_cm']        ? $seed['spacing_cm'].'cm'          : '—',
                        'Row spacing'     => $seed['row_spacing_cm']    ? $seed['row_spacing_cm'].'cm'       : '—',
                        'Sowing depth'    => $seed['sowing_depth_mm']   ? $seed['sowing_depth_mm'].'mm'      : '—',
                        'Sun'             => $seed['sun_exposure'] ?: '—',
                        'Soil'            => $seed['soil_notes'] ?: '—',
                        'Frost hardy'     => $seed['frost_hardy'] ? '✅ Yes' : 'No',
                        'Yield/plant'     => $seed['yield_per_plant_kg'] ? number_format((float)$seed['yield_per_plant_kg'],2).' kg' : '—',
                    ]; foreach ($rows as $label => $val): ?>
                    <tr style="border-bottom:1px solid var(--color-border)">
                        <td style="padding:5px 8px 5px 0;color:var(--color-text-muted);font-size:0.8rem;width:110px"><?= $label ?></td>
                        <td style="padding:5px 0"><?= e($val) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Companions -->
        <?php if ($companions || $antagonists): ?>
        <div class="card" style="margin-bottom:var(--spacing-3)">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-2)">Companion Planting</div>
                <?php if ($companions): ?>
                <p class="text-sm" style="margin-bottom:6px"><strong style="color:var(--color-primary)">Good with:</strong> <?= e(implode(', ', $companions)) ?></p>
                <?php endif; ?>
                <?php if ($antagonists): ?>
                <p class="text-sm" style="margin:0"><strong style="color:#dc3545">Avoid:</strong> <?= e(implode(', ', $antagonists)) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($seed['notes']): ?>
        <div class="card" style="margin-bottom:var(--spacing-3)">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-2)">Notes</div>
                <p class="text-sm" style="white-space:pre-line;margin:0"><?= e($seed['notes']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gardener's Note — always visible, inline editable -->
        <div class="card" id="rgGardenerCard">
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <div class="settings-group-title" style="margin:0">✏️ Gardener's Note</div>
                    <button type="button" id="rgNoteEditBtn" onclick="rgNoteStartEdit()" style="background:none;border:none;font-size:.78rem;color:var(--color-primary);cursor:pointer;font-weight:600;padding:2px 6px">Edit</button>
                </div>
                <!-- Read view -->
                <div id="rgNoteView">
                    <?php if (!empty($seed['gardener_note'])): ?>
                    <p id="rgNoteText" class="text-sm" style="white-space:pre-line;margin:0;color:var(--color-text)"><?= e($seed['gardener_note']) ?></p>
                    <?php else: ?>
                    <p id="rgNoteText" class="text-sm" style="margin:0;color:var(--color-text-muted);font-style:italic">Tap <strong>Edit</strong> to add your personal note — variety source, growing tips, observations…</p>
                    <?php endif; ?>
                </div>
                <!-- Edit view -->
                <div id="rgNoteEdit" style="display:none">
                    <textarea id="rgNoteInput" rows="4" class="form-input" style="width:100%;font-size:.88rem;resize:vertical;margin-bottom:8px;box-sizing:border-box" placeholder="Your personal observations, seed source, growing tips…"><?= e($seed['gardener_note'] ?? '') ?></textarea>
                    <div style="display:flex;gap:8px;justify-content:flex-end">
                        <button type="button" onclick="rgNoteCancel()" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="button" id="rgNoteSaveBtn" onclick="rgNoteSave()" class="btn btn-primary btn-sm">Save note</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column (moves to top on mobile) -->
    <div class="seed-col-right">
        <!-- Calendar -->
        <?php if ($plantMonths || $harvMonths): ?>
        <div class="card" style="margin-bottom:var(--spacing-3)">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-3)">Calendar</div>
                <div style="display:grid;grid-template-columns:repeat(12,1fr);gap:3px;margin-bottom:8px">
                    <?php foreach (range(1,12) as $m):
                        $isPlant = in_array($m,$plantMonths);
                        $isHarv  = in_array($m,$harvMonths);
                        $bg = $isPlant && $isHarv ? '#7c3aed' : ($isPlant ? 'var(--color-primary)' : ($isHarv ? '#b45309' : 'var(--color-bg)'));
                    ?>
                    <div style="text-align:center;padding:5px 0;background:<?= $bg ?>;color:<?= ($isPlant||$isHarv)?'#fff':'var(--color-text-muted)' ?>;border-radius:4px;font-size:0.65rem;font-weight:600;border:1px solid var(--color-border)"><?= $monthNames[$m-1] ?></div>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex;gap:12px;font-size:0.75rem;flex-wrap:wrap">
                    <span><span style="display:inline-block;width:10px;height:10px;background:var(--color-primary);border-radius:2px;margin-right:4px"></span>Sow</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#b45309;border-radius:2px;margin-right:4px"></span>Harvest</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#7c3aed;border-radius:2px;margin-right:4px"></span>Both</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stock -->
        <?php if ($seed['stock_enabled']): ?>
        <div class="card" style="margin-bottom:var(--spacing-3);<?= $low ? 'border-color:#dc3545' : '' ?>">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-2)">Stock</div>
                <div style="font-size:2rem;font-weight:700;color:<?= $low ? '#dc3545' : 'var(--color-primary)' ?>"><?= number_format((float)$seed['stock_qty'], 2) ?> <span style="font-size:1rem;font-weight:400;color:var(--color-text-muted)"><?= e($seed['stock_unit']) ?></span></div>
                <?php if ($seed['stock_low_threshold'] !== null): ?>
                <p class="text-muted text-sm" style="margin:4px 0 var(--spacing-3)">Low alert below: <?= number_format((float)$seed['stock_low_threshold'], 2) ?> <?= e($seed['stock_unit']) ?></p>
                <?php endif; ?>
                <form method="POST" action="<?= url('/seeds/' . (int)$seed['id'] . '/stock') ?>" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:var(--spacing-2)">
                    <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                    <select name="stock_action" class="form-input form-input--sm" style="min-width:90px">
                        <option value="add">Add</option>
                        <option value="subtract">Use</option>
                        <option value="set">Set to</option>
                    </select>
                    <input type="number" name="stock_amount" class="form-input form-input--sm" step="0.001" min="0" value="0" style="width:90px">
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Family needs summary -->
        <?php if (!empty($familyNeeds)): ?>
        <div class="card">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-2)">Family Needs</div>
                <?php foreach ($familyNeeds as $fn): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;border-bottom:1px solid var(--color-border);font-size:0.85rem">
                    <span><?= e($fn['vegetable_name']) ?></span>
                    <span class="text-muted"><?= isset($fn['yearly_qty']) && $fn['yearly_qty'] !== null ? number_format((float)$fn['yearly_qty'],1).' '.e($fn['yearly_unit'] ?? 'kg').'/yr' : '—' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ===== Gardener's Note inline edit ===== -->
<script>
(function () {
    var _origNote = <?= json_encode($seed['gardener_note'] ?? '') ?>;
    function rgNoteStartEdit() {
        document.getElementById('rgNoteView').style.display = 'none';
        document.getElementById('rgNoteEditBtn').style.display = 'none';
        document.getElementById('rgNoteEdit').style.display = 'block';
        document.getElementById('rgNoteInput').focus();
    }
    function rgNoteCancel() {
        document.getElementById('rgNoteInput').value = _origNote;
        document.getElementById('rgNoteEdit').style.display = 'none';
        document.getElementById('rgNoteView').style.display = 'block';
        document.getElementById('rgNoteEditBtn').style.display = '';
    }
    function rgNoteSave() {
        var note = document.getElementById('rgNoteInput').value;
        var btn  = document.getElementById('rgNoteSaveBtn');
        btn.disabled = true; btn.textContent = 'Saving…';
        $.post('<?= url('/seeds/' . (int)$seed['id'] . '/gardener-note') ?>', {
            _token: '<?= e(\App\Support\CSRF::getToken()) ?>',
            note: note
        }).done(function (d) {
            if (d && d.success) {
                _origNote = note;
                var el = document.getElementById('rgNoteText');
                if (note) {
                    el.style.fontStyle = 'normal';
                    el.style.color = 'var(--color-text)';
                    el.textContent = note;
                } else {
                    el.style.fontStyle = 'italic';
                    el.style.color = 'var(--color-text-muted)';
                    el.innerHTML = 'Tap <strong>Edit</strong> to add your personal note — variety source, growing tips, observations…';
                }
                rgNoteCancel();
            } else {
                btn.disabled = false; btn.textContent = 'Save note';
                alert((d && d.error) ? d.error : 'Could not save — please try again.');
            }
        }).fail(function () {
            btn.disabled = false; btn.textContent = 'Save note';
            alert('Network error — please try again.');
        });
    }
    // expose so onclick attrs work
    window.rgNoteStartEdit = rgNoteStartEdit;
    window.rgNoteCancel    = rgNoteCancel;
    window.rgNoteSave      = rgNoteSave;

    // Double-tap on note view to start edit (mobile-friendly)
    var _tapTimer = null;
    document.getElementById('rgNoteView').addEventListener('click', function () {
        if (_tapTimer) { clearTimeout(_tapTimer); _tapTimer = null; rgNoteStartEdit(); }
        else { _tapTimer = setTimeout(function () { _tapTimer = null; }, 300); }
    });
}());
</script>

<!-- ===== Add to Garden Bed modal ===== -->
<div id="rgAddBedModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.82);align-items:flex-end;justify-content:center">
  <div style="background:#1a1a1a;color:#f0f0f0;width:100%;max-width:480px;border-radius:16px 16px 0 0;padding:20px 16px 32px;max-height:80vh;display:flex;flex-direction:column">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <span id="rgAddBedTitle" style="font-weight:700;font-size:1rem">🛏 Select a Garden Bed</span>
      <button id="rgAddBedClose" style="background:none;border:none;color:#aaa;font-size:1.4rem;cursor:pointer;line-height:1">×</button>
    </div>
    <!-- Step 1: bed list -->
    <div id="rgAddBedStep1" style="overflow-y:auto;flex:1">
      <div id="rgAddBedList" style="display:flex;flex-direction:column;gap:8px">
        <div style="color:#888;font-size:.85rem;text-align:center;padding:20px">Loading beds…</div>
      </div>
    </div>
    <!-- Step 2: line list -->
    <div id="rgAddBedStep2" style="display:none;overflow-y:auto;flex:1">
      <button id="rgAddBedBack" style="background:none;border:none;color:#8ab4f8;font-size:.85rem;cursor:pointer;padding:0 0 10px;display:flex;align-items:center;gap:4px">← back to beds</button>
      <div id="rgAddLineList" style="display:flex;flex-direction:column;gap:7px"></div>
    </div>
    <!-- Step 3: confirm -->
    <div id="rgAddBedStep3" style="display:none;text-align:center">
      <p id="rgAddConfirmText" style="color:#ccc;font-size:.9rem;margin-bottom:14px"></p>
      <button id="rgAddBedBack2" style="background:none;border:none;color:#8ab4f8;font-size:.85rem;cursor:pointer;margin-right:12px">← pick another line</button>
      <button id="rgAddBedSave" style="background:#22c55e;border:none;border-radius:999px;color:#fff;font-weight:700;padding:10px 26px;font-size:.95rem;cursor:pointer">Plant here →</button>
    </div>
  </div>
</div>

<script>
(function () {
    var SEED_ID  = <?= (int)$seed['id'] ?>;
    var CSRF_TOK = '<?= e(\App\Support\CSRF::getToken()) ?>';
    var selectedBedId   = null;
    var selectedLineNum = null;
    var selectedBedName = '';

    function openModal() {
        $('#rgAddBedModal').css('display','flex');
        showStep(1);
        loadBeds();
    }
    function closeModal() {
        $('#rgAddBedModal').hide();
        selectedBedId = selectedLineNum = null;
    }
    function showStep(n) {
        $('#rgAddBedStep1,#rgAddBedStep2,#rgAddBedStep3').hide();
        $('#rgAddBedStep'+n).show();
    }

    function loadBeds() {
        $('#rgAddBedList').html('<div style="color:#888;font-size:.85rem;text-align:center;padding:20px">Loading…</div>');
        $.getJSON('<?= url('/api/garden/beds') ?>').done(function(data) {
            if (!data.success || !data.beds || !data.beds.length) {
                $('#rgAddBedList').html('<div style="color:#888;font-size:.85rem;text-align:center;padding:20px">No garden beds found.</div>');
                return;
            }
            var html = '';
            $.each(data.beds, function(_, b) {
                html += '<button class="rg-bed-row" data-id="'+b.id+'" data-name="'+encodeURIComponent(b.name)+'" style="background:#2a2a2a;border:1px solid #333;border-radius:10px;padding:12px 14px;text-align:left;cursor:pointer;color:#f0f0f0;width:100%">'
                    + '<div style="font-weight:700;font-size:.9rem">'+escHtml(b.name)+'</div>'
                    + (b.garden_name ? '<div style="font-size:.75rem;color:#888;margin-top:2px">'+escHtml(b.garden_name)+'</div>' : '')
                    + '<div style="font-size:.72rem;color:#666;margin-top:2px">'+b.num_lines+' line'+(b.num_lines!==1?'s':'')+'</div>'
                    + '</button>';
            });
            $('#rgAddBedList').html(html);
            $('.rg-bed-row').on('click', function() {
                selectedBedId   = $(this).data('id');
                selectedBedName = decodeURIComponent($(this).data('name'));
                loadLines(selectedBedId, selectedBedName);
            });
        }).fail(function() {
            $('#rgAddBedList').html('<div style="color:#f87171;text-align:center;padding:20px">Failed to load beds.</div>');
        });
    }

    function loadLines(bedId, bedName) {
        $('#rgAddBedTitle').text('Lines in '+bedName);
        showStep(2);
        $('#rgAddLineList').html('<div style="color:#888;font-size:.85rem;text-align:center;padding:20px">Loading lines…</div>');
        $.getJSON('<?= url('/api/garden/beds') ?>/'+bedId+'/lines').done(function(data) {
            if (!data.success || !data.lines || !data.lines.length) {
                $('#rgAddLineList').html('<div style="color:#888;text-align:center;padding:20px">No lines found.</div>');
                return;
            }
            var html = '';
            $.each(data.lines, function(_, l) {
                var statusCol = l.status === 'growing' ? '#f59e0b' : (l.status === 'resting' ? '#94a3b8' : '#22c55e');
                var statusLabel = l.status === 'growing' ? 'Growing' : (l.status === 'resting' ? 'Resting' : 'Empty');
                var fillBar = '<div style="height:4px;border-radius:2px;background:#333;margin-top:5px"><div style="height:4px;border-radius:2px;background:'+statusCol+';width:'+l.fill_pct+'%"></div></div>';
                html += '<button class="rg-line-row" data-line="'+l.line_number+'" style="background:#2a2a2a;border:1px solid #333;border-radius:10px;padding:10px 14px;text-align:left;cursor:pointer;color:#f0f0f0;width:100%">'
                    + '<div style="display:flex;justify-content:space-between;align-items:center">'
                    + '<span style="font-weight:700;font-size:.88rem">Line '+l.line_number+'</span>'
                    + '<span style="font-size:.72rem;background:'+statusCol+'22;color:'+statusCol+';padding:2px 8px;border-radius:999px;font-weight:600">'+statusLabel+' · '+l.fill_pct+'%</span>'
                    + '</div>'
                    + (l.plants_summary ? '<div style="font-size:.75rem;color:#aaa;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+escHtml(l.plants_summary)+'</div>' : '')
                    + fillBar
                    + '</button>';
            });
            $('#rgAddLineList').html(html);
            $('.rg-line-row').on('click', function() {
                selectedLineNum = $(this).data('line');
                $('#rgAddConfirmText').text('Plant <?= e(addslashes($seed['name'])) ?> in Line '+selectedLineNum+' of '+selectedBedName);
                $('#rgAddBedTitle').text('Confirm planting');
                showStep(3);
            });
        }).fail(function() {
            $('#rgAddLineList').html('<div style="color:#f87171;text-align:center;padding:20px">Failed to load lines.</div>');
        });
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    $('#rgAddToBedBtn').on('click', openModal);
    $('#rgAddBedClose').on('click', closeModal);
    $('#rgAddBedModal').on('click', function(e) { if ($(e.target).is('#rgAddBedModal')) closeModal(); });
    $('#rgAddBedBack').on('click', function() {
        $('#rgAddBedTitle').text('🛏 Select a Garden Bed');
        showStep(1);
    });
    $('#rgAddBedBack2').on('click', function() {
        $('#rgAddBedTitle').text('Lines in '+selectedBedName);
        showStep(2);
    });

    $('#rgAddBedSave').on('click', function() {
        if (!selectedBedId || !selectedLineNum) return;
        var btn = $(this);
        btn.prop('disabled', true).text('Planting…');
        $.post('<?= url('/items') ?>/'+selectedBedId+'/plant-tap', {
            _token: CSRF_TOK, crop_id: SEED_ID, line_number: selectedLineNum, count: 1
        }).done(function(data) {
            if (data && data.success) {
                window.location.href = '<?= url('/items') ?>/'+selectedBedId+'/planting';
            } else {
                btn.prop('disabled', false).text('Plant here →');
                alert((data && data.error) ? data.error : 'Failed to plant. Please try again.');
            }
        }).fail(function() {
            btn.prop('disabled', false).text('Plant here →');
            alert('Network error. Please try again.');
        });
    });
}());
</script>
