<?php
/** @var array $bed */
/** @var array $catalog */
/** @var array $cropsById */
/** @var array $item */
/** @var array|null $parentGarden */
/** @var string $today */

use App\Support\GardenHelpers;
use App\Support\CSRF;

$csrf = CSRF::getToken();
$bedId = (int)$item['id'];
?>
<div class="rg-bed-page" id="rgBedPage" data-item-id="<?= $bedId ?>">

  <div class="rg-bed-header">
    <a href="<?= url('/items/' . $bedId) ?>" class="rg-bed-back" aria-label="Back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div style="flex:1;min-width:0">
      <?php if ($parentGarden): ?>
      <div class="rg-label-tiny"><?= e($parentGarden['name']) ?></div>
      <?php endif; ?>
      <div class="rg-bed-title"><?= e($item['name']) ?></div>
    </div>
    <span class="rg-bed-dim"><?= e(rtrim(rtrim(number_format($lengthM,1,'.',''),'0'),'.')) ?>×<?= e(rtrim(rtrim(number_format($widthM,1,'.',''),'0'),'.')) ?>m</span>
  </div>

  <div class="rg-mode-tabs" role="tablist">
    <a href="<?= url('/items/' . $bedId . '/planting') ?>" class="rg-mode-tab">🌱 Plant</a>
    <a href="<?= url('/items/' . $bedId . '/planting/inline') ?>" class="rg-mode-tab is-active">⏭ Plan</a>
    <a href="<?= url('/items/' . $bedId . '/planting/timeline') ?>" class="rg-mode-tab">📅 Timeline</a>
  </div>

  <div class="rg-label-tiny" style="margin:0 0 10px;display:flex;align-items:center;gap:6px">
    📅 Planning · <?= e(GardenHelpers::fmtDate($today)) ?>
    <span class="rg-mono" style="color:var(--color-text-muted);text-transform:none;letter-spacing:0;font-size:.66rem">· tap "Pick what comes next" to schedule a follow-on</span>
  </div>

  <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

  <?php foreach ($bed['lines'] as $idx => $line):
    $segments = GardenHelpers::computeSegments($line, $cropsById);
    $fill     = GardenHelpers::computeFill($line, $cropsById);
    $empty    = empty($line['plantings']);
    $harvestDate = GardenHelpers::lineHarvestDate($line, $cropsById);
    $daysLeft = GardenHelpers::daysToLineHarvest($line, $cropsById, $today);
    $sownDate = GardenHelpers::lineSownDate($line);
    $succession = $line['succession'];
    $succCrop = $succession ? ($cropsById[$succession['cropId']] ?? null) : null;
    $succWarn = ($succession && $succCrop) ? GardenHelpers::rotationWarning($line, $succCrop, $cropsById, $today) : null;
  ?>
    <div class="rg-line" data-line="<?= (int)$line['lineNumber'] ?>" style="position:relative">
      <div class="rg-line-head">
        <div class="rg-line-num"><?= (int)$line['lineNumber'] ?></div>
        <div class="rg-line-name">
          Line <?= (int)$line['lineNumber'] ?>
          <span class="rg-line-fill">·
            <?php if ($empty): ?>
              empty<?= $line['empty_since'] ? ' · since ' . e(GardenHelpers::fmtDate($line['empty_since'])) : '' ?>
            <?php else: ?>
              <?= (int)$fill['used'] ?>/<?= (int)$line['lengthCm'] ?>cm
            <?php endif; ?>
          </span>
        </div>
        <?php if (!$empty): ?>
        <?php
        // Build the per-crop payload the harvest modal needs. Sent inline as
        // JSON so the modal can render rows for every crop on the line without
        // a second HTTP round-trip.
        $_harvestData = [];
        foreach ($line['plantings'] as $_p) {
            $_c = $cropsById[$_p['cropId']] ?? null;
            if (!$_c) continue;
            $_harvestData[] = [
                'planting_id' => (int)$_p['id'],
                'name'        => $_c['name'],
                'emoji'       => $_c['emoji'],
                'color'       => $_c['color'],
                'count'       => (int)$_p['plants'],
            ];
        }
        ?>
        <div class="rg-line-actions">
          <button class="btn btn-secondary btn-sm rg-harvest-btn"
                  data-line="<?= (int)$line['lineNumber'] ?>"
                  data-crops="<?= e(json_encode($_harvestData)) ?>"
                  style="border-color:var(--color-accent);color:var(--color-accent)">🌾 Harvest</button>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!$empty): ?>
      <div class="rg-stripe rg-stripe--maturity">
        <?php foreach ($segments as $i => $s):
          $w = max(0.5, $s['pct'] * 100);
          $c = $s['crop'];
          $color = $c['color'];
          $planting = ['cropId' => $s['cropId'], 'sown_at' => $s['sown_at'] ?? $sownDate];
          $m = GardenHelpers::maturity($planting, $line, $c, $today);
        ?>
          <div class="rg-stripe-seg" style="position:relative;width: <?= $w ?>%; background: linear-gradient(180deg, <?= e($color) ?>, <?= e($color) ?>dd);">
            <?php if ($s['pct'] > 0.10): ?>
            <span><?= e($c['emoji']) ?> <?= round($m * 100) ?>%</span>
            <?php endif; ?>
            <div class="rg-stripe-tick" style="left: <?= $m * 100 ?>%"></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="rg-sown-row" data-line="<?= (int)$line['lineNumber'] ?>" style="display:flex;align-items:baseline;gap:10px;margin-top:6px;font-size:.74rem;color:var(--color-text-muted);flex-wrap:wrap">
        <span><strong style="color:var(--color-text)">Sown</strong>
          <span class="rg-sown-display" style="cursor:pointer;border-bottom:1px dashed var(--color-text-muted)" title="Click to edit sown date"><?= e(GardenHelpers::fmtDate($sownDate)) ?></span>
          <span class="rg-sown-edit" style="display:none;align-items:center;gap:6px">
            <input type="date" class="rg-sown-input" value="<?= e($sownDate) ?>" style="font-size:.74rem;padding:2px 6px;border:1px solid var(--color-border);border-radius:4px;font-family:inherit">
            <button type="button" class="btn btn-primary btn-xs rg-sown-save" style="padding:2px 8px;font-size:.7rem">Save</button>
            <button type="button" class="btn btn-ghost btn-xs rg-sown-cancel" style="padding:2px 8px;font-size:.7rem">Cancel</button>
          </span>
        </span>
        <span>→</span>
        <span><strong style="color:var(--color-text)">Harvest</strong> ~<span class="rg-harvest-display"><?= e(GardenHelpers::fmtDate($harvestDate)) ?></span>
          <?php if ($daysLeft !== null): ?>
            <span class="rg-mono rg-harvest-days">(<?= $daysLeft >= 0 ? 'in ' . $daysLeft . 'd' : abs($daysLeft) . 'd ago' ?>)</span>
          <?php else: ?>
            <span class="rg-mono rg-harvest-days"></span>
          <?php endif; ?>
        </span>
      </div>
      <?php else: ?>
      <div style="padding:8px 0;font-size:.82rem;color:var(--color-text-muted)">
        🪴 This line is bare.
        <?php if ($line['empty_since']): ?>
          Empty for <?= max(0, GardenHelpers::daysBetween($line['empty_since'], $today)) ?> days.
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Succession card -->
      <div class="rg-succ <?= $succession ? 'rg-succ--filled' : '' ?>">
        <div class="rg-succ-header">
          <?= $empty ? '⏭ Plan first crop' : '⏭ After harvest' ?>
          <?php if ($succession): ?>
            <span class="rg-succ-header-meta">· queued for <?= e(GardenHelpers::fmtDate($succession['startsOn'])) ?></span>
          <?php endif; ?>
        </div>

        <?php if ($succession && $succCrop): ?>
          <div class="rg-succ-body">
            <div class="rg-succ-tile" style="background:<?= e($succCrop['color']) ?>22;border:1.5px solid <?= e($succCrop['color']) ?>66"><?= e($succCrop['emoji']) ?></div>
            <div style="flex:1;min-width:0">
              <div class="rg-succ-name"><?= e($succCrop['name']) ?></div>
              <div class="rg-succ-dates">sow <?= e(GardenHelpers::fmtDate($succession['startsOn'])) ?> → harvest ~<?= e(GardenHelpers::fmtDate(GardenHelpers::addDays($succession['startsOn'], (int)$succCrop['days_to_maturity']))) ?></div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm rg-succ-clear" data-line="<?= (int)$line['lineNumber'] ?>" style="color:var(--color-text-muted)">Remove</button>
            <button type="button" class="btn btn-ghost btn-sm rg-succ-pick" data-line="<?= (int)$line['lineNumber'] ?>">Change</button>
          </div>
        <?php else: ?>
          <button type="button" class="rg-succ-empty-cta rg-succ-pick" data-line="<?= (int)$line['lineNumber'] ?>">＋ Pick what comes next</button>
        <?php endif; ?>

        <?php if ($succWarn): ?>
        <div class="rg-succ-warn"><span>⚠</span><span><?= e($succWarn) ?></span></div>
        <?php endif; ?>

        <!-- Picker -->
        <div class="rg-picker" data-picker-for="<?= (int)$line['lineNumber'] ?>" style="display:none">
          <div class="rg-label-tiny" style="padding:4px 6px 6px">Pick a follow-on crop</div>
          <div class="rg-picker-grid">
            <?php foreach ($catalog as $c):
              $warn = GardenHelpers::rotationWarning($line, $c, $cropsById, $today);
            ?>
              <button type="button" class="rg-picker-tile <?= $warn ? 'is-warn' : '' ?> rg-pick-tile" data-line="<?= (int)$line['lineNumber'] ?>" data-crop-id="<?= (int)$c['id'] ?>" data-starts-on="<?= e(GardenHelpers::nextSuccessionStart($line, $cropsById, $today)) ?>" title="<?= e($warn ?? '') ?>">
                <span class="rg-picker-tile-emoji"><?= e($c['emoji']) ?></span>
                <div style="flex:1;min-width:0">
                  <div class="rg-picker-tile-name"><?= e($c['name']) ?></div>
                  <div class="rg-picker-tile-meta"><?= (int)$c['days_to_maturity'] ?>d · <?= (int)$c['spacing_cm'] ?>cm</div>
                </div>
                <?php if ($warn): ?><span style="color:#b45309">⚠</span><?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-ghost btn-sm rg-picker-cancel" style="width:100%;margin-top:6px">Cancel</button>
        </div>
      </div>

      <!-- Rotation history -->
      <?php if (!empty($line['rotation_history'])): ?>
      <div class="rg-rot-row">
        <span class="rg-label-tiny rg-mono">Last grown here</span>
        <?php foreach (array_slice(array_reverse($line['rotation_history']), 0, 3) as $r):
          $rc = $cropsById[$r['cropId'] ?? 0] ?? null;
          if (!$rc) continue;
          $color = $rc['color'];
        ?>
          <span class="rg-rot-pill" style="background:<?= e($color) ?>14;border-color:<?= e($color) ?>33">
            <span><?= e($rc['emoji']) ?></span>
            <span class="rg-mono"><?= e($rc['name']) ?> · <?= (int)($r['year'] ?? 0) ?> <?= e($r['season'] ?? '') ?></span>
          </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <!-- Harvest blackout (per-crop) -->
  <div class="rg-blackout" id="rgHarvestModal" style="display:none">
    <div class="rg-blackout-card" style="max-width:560px">
      <div class="rg-blackout-head">
        <div style="flex:1">
          <div class="rg-label-tiny">Harvest</div>
          <div style="font-weight:800;font-size:1rem;margin-top:2px"><?= e($bed['name']) ?> · Line <span id="rgHarvestLine">—</span></div>
        </div>
        <button type="button" class="rg-blackout-close" id="rgHarvestClose">×</button>
      </div>
      <div class="rg-blackout-body">
        <!-- Quick presets — fills plant counts on each crop row -->
        <div style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap">
          <button type="button" class="btn btn-ghost btn-sm rg-harvest-preset" data-preset="one"  style="flex:1;min-width:90px">1 each</button>
          <button type="button" class="btn btn-ghost btn-sm rg-harvest-preset" data-preset="half" style="flex:1;min-width:90px">Half line</button>
          <button type="button" class="btn btn-ghost btn-sm rg-harvest-preset" data-preset="all"  style="flex:1;min-width:90px">Full line</button>
        </div>

        <!-- Per-crop rows are injected here by JS -->
        <div id="rgHarvestRows" style="display:flex;flex-direction:column;gap:10px"></div>

        <div class="form-group" style="margin-top:14px">
          <label class="form-label" style="font-size:.78rem">Notes <small class="text-muted" style="font-weight:400">(optional, applied to all)</small></label>
          <input type="text" class="form-input" id="rgHarvestNotes" placeholder="e.g. afternoon picking, slightly under-ripe">
        </div>

        <div style="display:flex;gap:8px;margin-top:18px">
          <button type="button" class="btn btn-ghost" id="rgHarvestCancel" style="flex:1">Cancel</button>
          <button type="button" class="btn btn-primary" id="rgHarvestConfirm" style="flex:2">Save Harvest 🌾</button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
(function () {
  var $page = $('#rgBedPage');
  var bedId = parseInt($page.data('item-id'), 10);
  var csrf = <?= json_encode($csrf) ?>;

  // ---- inline sown date edit ----
  $page.on('click', '.rg-sown-display', function () {
    var $row = $(this).closest('.rg-sown-row');
    $row.find('.rg-sown-display').hide();
    $row.find('.rg-sown-edit').css('display', 'inline-flex');
    $row.find('.rg-sown-input').focus();
  });
  $page.on('click', '.rg-sown-cancel', function () {
    var $row = $(this).closest('.rg-sown-row');
    $row.find('.rg-sown-edit').hide();
    $row.find('.rg-sown-display').show();
  });
  $page.on('keydown', '.rg-sown-input', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $(this).closest('.rg-sown-row').find('.rg-sown-save').click(); }
    else if (e.key === 'Escape') { $(this).closest('.rg-sown-row').find('.rg-sown-cancel').click(); }
  });
  $page.on('click', '.rg-sown-save', function () {
    var $row   = $(this).closest('.rg-sown-row');
    var line   = parseInt($row.data('line'), 10);
    var sownAt = $row.find('.rg-sown-input').val();
    var $btn   = $(this); $btn.prop('disabled', true).text('…');
    $.post('<?= url('/items/' . $bedId . '/lines/set-sown') ?>', { _token: csrf, line_number: line, sown_at: sownAt })
      .done(function (data) {
        $btn.prop('disabled', false).text('Save');
        if (!data || data.success === false) {
          alert((data && data.error) || 'Could not save');
          return;
        }
        $row.find('.rg-sown-display').text(data.sown_at_label).show();
        $row.find('.rg-sown-edit').hide();
        $row.find('.rg-harvest-display').text(data.harvest_at_label);
        var d = parseInt(data.days_to_harvest, 10);
        var label = isNaN(d) ? '' : '(' + (d >= 0 ? 'in ' + d + 'd' : Math.abs(d) + 'd ago') + ')';
        $row.find('.rg-harvest-days').text(label);
      })
      .fail(function () { $btn.prop('disabled', false).text('Save'); alert('Network error'); });
  });

  // ---- picker open/close ----
  $page.on('click', '.rg-succ-pick', function () {
    var line = parseInt($(this).data('line'), 10);
    $('.rg-picker').hide();
    $('.rg-picker[data-picker-for="' + line + '"]').show();
  });
  $page.on('click', '.rg-picker-cancel', function () {
    $(this).closest('.rg-picker').hide();
  });

  // ---- pick a tile ----
  $page.on('click', '.rg-pick-tile', function () {
    var line = parseInt($(this).data('line'), 10);
    var cropId = parseInt($(this).data('crop-id'), 10);
    var startsOn = $(this).data('starts-on') || '';
    $.post('<?= url('/items/' . $bedId . '/lines/succession/set') ?>', {
      _token: csrf, line_number: line, crop_id: cropId, starts_on: startsOn
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not save succession.'); });
  });

  // ---- clear succession ----
  $page.on('click', '.rg-succ-clear', function () {
    var line = parseInt($(this).data('line'), 10);
    $.post('<?= url('/items/' . $bedId . '/lines/succession/clear') ?>', {
      _token: csrf, line_number: line
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not clear.'); });
  });

  // ---- harvest blackout ----
  var harvestLine = null;
  $page.on('click', '.rg-harvest-btn', function () {
    harvestLine = parseInt($(this).data('line'), 10);
    var crops = [];
    try { crops = $(this).data('crops') || []; } catch (e) { crops = []; }
    if (typeof crops === 'string') { try { crops = JSON.parse(crops); } catch (e) { crops = []; } }
    $('#rgHarvestLine').text(harvestLine);
    $('#rgHarvestNotes').val('');
    var $rows = $('#rgHarvestRows').empty();
    if (!crops.length) {
      $rows.append('<div class="text-muted text-sm">Nothing planted on this line.</div>');
    } else {
      crops.forEach(function (c) {
        var rowHtml = '<div class="rg-harvest-row" data-planting-id="' + c.planting_id + '" data-max="' + c.count + '"' +
          ' style="background:' + c.color + '14;border:1px solid ' + c.color + '44;border-radius:10px;padding:10px 12px">' +
          '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">' +
            '<span style="font-size:1.1rem">' + (c.emoji || '🌱') + '</span>' +
            '<strong style="color:' + c.color + ';flex:1">' + c.name + '</strong>' +
            '<span class="text-muted text-sm rg-mono">' + c.count + ' plants</span>' +
          '</div>' +
          '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">' +
            '<div style="flex:1;min-width:110px">' +
              '<div class="form-label" style="font-size:.7rem">Plants harvested</div>' +
              '<div style="display:inline-flex;align-items:center;background:#fff;border:1px solid var(--color-border);border-radius:999px;overflow:hidden">' +
                '<button type="button" class="rg-harvest-step rg-harvest-minus" style="width:28px;height:28px;border:0;background:transparent;font-weight:800;cursor:pointer">−</button>' +
                '<input type="number" class="rg-harvest-plants" min="0" max="' + c.count + '" step="1" value="0" style="width:46px;text-align:center;border:0;background:transparent;font-weight:700;font-family:var(--font-mono);outline:none">' +
                '<button type="button" class="rg-harvest-step rg-harvest-plus" style="width:28px;height:28px;border:0;background:transparent;font-weight:800;cursor:pointer">+</button>' +
              '</div>' +
            '</div>' +
            '<div style="flex:1.4;min-width:120px">' +
              '<div class="form-label" style="font-size:.7rem">Yield (optional)</div>' +
              '<div style="display:flex;gap:4px">' +
                '<input type="number" class="rg-harvest-qty form-input" step="0.01" min="0" placeholder="0.0" style="flex:1">' +
                '<select class="rg-harvest-unit form-input" style="max-width:78px"><option>kg</option><option>g</option><option>L</option><option>bunch</option><option>items</option></select>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>';
        $rows.append(rowHtml);
      });
    }
    $('#rgHarvestModal').css('display', 'flex');
  });

  // Stepper +/- on each crop row, clamped to [0, max]
  $page.on('click', '.rg-harvest-step', function () {
    var $row = $(this).closest('.rg-harvest-row');
    var max = parseInt($row.data('max'), 10);
    var $i  = $row.find('.rg-harvest-plants');
    var v   = parseInt($i.val(), 10) || 0;
    v += $(this).hasClass('rg-harvest-plus') ? 1 : -1;
    if (v < 0) v = 0; if (v > max) v = max;
    $i.val(v);
  });
  $page.on('input', '.rg-harvest-plants', function () {
    var max = parseInt($(this).closest('.rg-harvest-row').data('max'), 10);
    var v = parseInt(this.value, 10);
    if (isNaN(v) || v < 0) v = 0;
    if (v > max) v = max;
    this.value = v;
  });

  // Quick-fill presets — apply the same rule across every crop row
  $page.on('click', '.rg-harvest-preset', function () {
    var preset = $(this).data('preset');
    $('#rgHarvestRows .rg-harvest-row').each(function () {
      var max = parseInt($(this).data('max'), 10);
      var v = 0;
      if (preset === 'one')  v = max > 0 ? 1 : 0;
      if (preset === 'half') v = Math.ceil(max / 2);
      if (preset === 'all')  v = max;
      $(this).find('.rg-harvest-plants').val(v);
    });
  });

  function closeHarvest() { $('#rgHarvestModal').hide(); harvestLine = null; }
  $('#rgHarvestClose, #rgHarvestCancel').on('click', closeHarvest);

  $('#rgHarvestConfirm').on('click', function () {
    if (harvestLine === null) return;
    var payload = { _token: csrf, line_number: harvestLine, notes: $('#rgHarvestNotes').val() || '', harvest: {} };
    var anything = false;
    $('#rgHarvestRows .rg-harvest-row').each(function () {
      var pid = $(this).data('planting-id');
      var plants = parseInt($(this).find('.rg-harvest-plants').val(), 10) || 0;
      var qty    = parseFloat($(this).find('.rg-harvest-qty').val()) || 0;
      var unit   = $(this).find('.rg-harvest-unit').val() || 'kg';
      if (plants > 0 || qty > 0) {
        payload.harvest[pid] = { plants: plants, qty: qty, unit: unit };
        anything = true;
      }
    });
    if (!anything) { alert('Set at least one quantity or plant count.'); return; }
    var $btn = $(this); $btn.prop('disabled', true).text('Saving…');
    $.post('<?= url('/items/' . $bedId . '/harvest-partial') ?>', payload)
      .done(function (data) {
        if (!data || data.success === false) {
          $btn.prop('disabled', false).text('Save Harvest 🌾');
          alert((data && data.error) || 'Could not save.');
          return;
        }
        window.location.reload();
      })
      .fail(function () { $btn.prop('disabled', false).text('Save Harvest 🌾'); alert('Could not save.'); });
  });
})();
</script>
