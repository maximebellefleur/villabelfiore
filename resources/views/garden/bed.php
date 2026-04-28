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
$mode = $mode ?? 'merged';
$bedId = (int)$item['id'];
?>
<div class="rg-bed-page" id="rgBedPage" data-item-id="<?= $bedId ?>">

  <!-- Header -->
  <div class="rg-bed-header">
    <a href="<?= $parentGarden ? url('/garden') : url('/items/' . $bedId) ?>" class="rg-bed-back" aria-label="<?= $parentGarden ? 'Back to garden hub' : 'Back to bed' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div style="flex:1;min-width:0">
      <?php if ($parentGarden): ?>
      <div class="rg-label-tiny"><?= e($parentGarden['name']) ?></div>
      <?php endif; ?>
      <div class="rg-bed-title"><?= e($item['name']) ?></div>
    </div>
    <a href="<?= url('/items/' . $bedId . '/edit') ?>" aria-label="Edit bed" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(0,0,0,.07);color:#444;flex-shrink:0;text-decoration:none">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
    </a>
  </div>

  <!-- Mode tabs -->
  <div class="rg-mode-tabs" role="tablist">
    <a href="<?= url('/items/' . $bedId . '/planting') ?>" class="rg-mode-tab <?= $mode==='merged' ? 'is-active' : '' ?>">🌱 Plant</a>
    <a href="<?= url('/items/' . $bedId . '/planting/inline') ?>" class="rg-mode-tab <?= $mode==='inline' ? 'is-active' : '' ?>">⏭ Plan</a>
    <a href="<?= url('/items/' . $bedId . '/planting/timeline') ?>" class="rg-mode-tab rg-mode-tab--desktop <?= $mode==='timeline' ? 'is-active' : '' ?>">📅 Timeline</a>
  </div>

  <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

  <?php if (!$parentGarden): ?>
  <div style="margin:12px 0;padding:14px 16px;background:#fff3cd;border:1px solid #ffc107;border-radius:var(--radius);display:flex;gap:12px;align-items:flex-start">
    <span style="font-size:1.4rem;flex-shrink:0">🟫</span>
    <div>
      <div style="font-weight:700;font-size:.95rem;margin-bottom:4px">Prep Bed — not assigned to a garden</div>
      <div style="font-size:.82rem;color:#856404;line-height:1.5">Assign this bed to an Active Garden before adding plantings. Logs, treatments and harvests can still be recorded from the bed's <a href="<?= url('/items/' . $bedId) ?>" style="color:#856404;font-weight:600">item page</a>.</div>
      <a href="<?= url('/garden') ?>" style="display:inline-block;margin-top:8px;font-size:.82rem;font-weight:600;color:#856404;text-decoration:underline">Go to Active Gardens →</a>
    </div>
  </div>
  <?php elseif (empty($bed['lines'])): ?>
    <div class="rg-week-empty">No lines configured. <a href="<?= url('/items/' . $bedId . '/edit') ?>">Set bed dimensions</a> to add lines.</div>
  <?php else: ?>

  <!-- Lines -->
  <?php foreach ($bed['lines'] as $idx => $line):
    $segments = GardenHelpers::computeSegments($line, $cropsById);
    $fill     = GardenHelpers::computeFill($line, $cropsById);
    $totalSlots = max(1, (int)floor((int)$line['lengthCm'] / 5));
    $overcap = $fill['used'] > (int)$line['lengthCm'];

    // Build slot map (1 slot = 5 cm)
    $slots = array_fill(0, $totalSlots, null);
    $cur = 0;
    foreach ($line['plantings'] as $p) {
      $c = $cropsById[$p['cropId']] ?? null;
      if (!$c) continue;
      $slotsPerPlant = max(1, (int)round((int)$c['spacing_cm'] / 5));
      for ($n = 0; $n < (int)$p['plants'] && $cur + $slotsPerPlant <= $totalSlots; $n++) {
        for ($k = 0; $k < $slotsPerPlant; $k++) {
          $slots[$cur + $k] = ['cropId' => (int)$c['id'], 'head' => $k === 0];
        }
        $cur += $slotsPerPlant;
      }
    }
    $suggestions = GardenHelpers::getSuggestions($line, $catalog, $cropsById);
  ?>
    <div class="rg-line <?= $overcap ? 'rg-line--overcap' : '' ?>" data-line="<?= (int)$line['lineNumber'] ?>">
      <div class="rg-line-head">
        <div class="rg-line-num"><?= (int)$line['lineNumber'] ?></div>
        <div class="rg-line-name">
          Line <?= (int)$line['lineNumber'] ?>
          <span class="rg-line-fill">· <?= (int)$fill['used'] ?>/<?= (int)$line['lengthCm'] ?>cm</span>
        </div>
        <?php if (!empty($line['plantings'])): ?>
        <div class="rg-line-actions">
          <button class="btn btn-secondary btn-sm rg-harvest-btn" data-line="<?= (int)$line['lineNumber'] ?>" style="border-color:var(--color-accent);color:var(--color-accent)">🌾 Harvest</button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Stripe -->
      <div class="rg-stripe">
        <?php foreach ($segments as $s):
          $w = max(0.5, $s['pct'] * 100);
          $c = $s['crop'];
          $color = $c['color'];
        ?>
          <div class="rg-stripe-seg" style="width: <?= $w ?>%; background: linear-gradient(180deg, <?= e($color) ?>, <?= e($color) ?>dd);">
            <?php if ($s['pct'] > 0.10): ?>
            <span><?= e($c['emoji']) ?> <?= (int)$s['plants'] ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Dot grid -->
      <div class="rg-dotgrid <?= $totalSlots > 60 ? 'rg-dotgrid--dense' : '' ?>">
        <div class="rg-dots">
          <?php foreach ($slots as $i => $s):
            if ($s):
              $crop = $cropsById[$s['cropId']] ?? null;
              $color = $crop ? $crop['color'] : '#A66141';
              $head = !empty($s['head']) ? ' is-head' : '';
              echo '<div class="rg-dot rg-dot--filled' . $head . '" style="background:' . e($color) . ';border-color:' . e($color) . '" title="' . e($crop['name'] ?? '') . '"></div>';
            else:
              echo '<div class="rg-dot rg-dot--clickable" data-slot="' . (int)$i . '" title="tap to plant"></div>';
            endif;
          endforeach; ?>
        </div>
      </div>

      <!-- Stepper chips -->
      <?php if (!empty($line['plantings'])): ?>
      <div class="rg-steppers">
        <?php foreach ($line['plantings'] as $p):
          $c = $cropsById[$p['cropId']] ?? null;
          if (!$c) continue;
          $color = $c['color'];
        ?>
          <div class="rg-stepchip" style="background:<?= e($color) ?>15;border:1px solid <?= e($color) ?>55;color:<?= e($color) ?>">
            <span class="rg-stepchip-emoji"><?= e($c['emoji']) ?></span>
            <span><?= e($c['name']) ?></span>
            <div class="rg-stepper" data-planting-id="<?= (int)$p['id'] ?>">
              <button type="button" class="rg-step-minus" style="color:<?= e($color) ?>" aria-label="-1">−</button>
              <span class="rg-stepper-val"><?= (int)$p['plants'] ?></span>
              <button type="button" class="rg-step-plus"  style="color:<?= e($color) ?>" aria-label="+1">+</button>
            </div>
            <button type="button" class="rg-stepchip-remove" data-planting-id="<?= (int)$p['id'] ?>" title="Remove <?= e($c['name']) ?> from this line">✕</button>
          </div>
        <?php endforeach; ?>
        <button type="button" class="rg-clear-btn" data-line="<?= (int)$line['lineNumber'] ?>">Clear All</button>
      </div>
      <?php endif; ?>

      <!-- Suggestions -->
      <?php if ($fill['remaining'] > 0 && !empty($suggestions)): ?>
      <div class="rg-suggest-row">
        <span class="rg-suggest-row-label">✨ <?= (int)$fill['remaining'] ?>cm · try</span>
        <?php foreach (array_slice($suggestions, 0, 3) as $sc):
          $color = $sc['color'];
        ?>
          <button type="button" class="rg-suggest-chip rg-plant-action" data-line="<?= (int)$line['lineNumber'] ?>" data-crop-id="<?= (int)$sc['id'] ?>" data-spacing="<?= (int)$sc['spacing_cm'] ?>" style="border-color:<?= e($color) ?>">
            <span class="rg-suggest-chip-bullet" style="background:<?= e($color) ?>22"><?= e($sc['emoji']) ?></span>
            Try <?= e($sc['name']) ?>
            <span class="rg-mono" style="font-size:.6rem;color:var(--color-text-muted)">+<?= (int)$sc['spacing_cm'] ?>cm</span>
          </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php endif; ?>

  <?php if ($parentGarden && !empty($bed['lines'])): ?>
  <!-- Fixed crop palette -->
  <div class="rg-palette" id="rgPalette">
    <div id="rgPaletteLabel" style="font-size:.7rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--color-text-muted);margin-bottom:6px">
      Tap a line above to select it ↑
    </div>
    <div class="rg-palette-row">
      <?php foreach ($catalog as $c): ?>
      <button type="button" class="rg-palette-chip" data-crop-id="<?= (int)$c['id'] ?>" data-color="<?= e($c['color']) ?>" data-spacing="<?= (int)$c['spacing_cm'] ?>">
        <span class="rg-palette-chip-emoji"><?= e($c['emoji']) ?></span>
        <span><?= e($c['name']) ?></span>
        <span class="rg-palette-chip-spacing"><?= (int)$c['spacing_cm'] ?>cm</span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Harvest blackout modal -->
  <div class="rg-blackout" id="rgHarvestModal" style="display:none">
    <div class="rg-blackout-card">
      <div class="rg-blackout-head">
        <div style="flex:1">
          <div class="rg-label-tiny">Harvest</div>
          <div style="font-weight:800;font-size:1rem;margin-top:2px"><?= e($bed['name']) ?> · Line <span id="rgHarvestLine">—</span></div>
        </div>
        <button type="button" class="rg-blackout-close" id="rgHarvestClose">×</button>
      </div>
      <div class="rg-blackout-body" id="rgHarvestBody">
        <div style="font-size:.82rem;color:var(--color-text-muted);margin-bottom:14px">How much did you harvest from this line?</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.1" class="form-input" id="rgHarvestQty" placeholder="0.0" />
          </div>
          <div class="form-group form-group--sm">
            <label class="form-label">Unit</label>
            <select class="form-input" id="rgHarvestUnit">
              <option>kg</option><option>g</option><option>L</option><option>bunch</option><option>items</option>
            </select>
          </div>
        </div>
        <div class="form-group" style="margin-top:8px">
          <label class="form-label">Quality</label>
          <div style="display:flex;gap:6px">
            <button type="button" class="btn btn-secondary btn-sm rg-q" data-q="3" style="flex:1">🤩 Excellent</button>
            <button type="button" class="btn btn-secondary btn-sm rg-q" data-q="2" style="flex:1">🙂 Good</button>
            <button type="button" class="btn btn-secondary btn-sm rg-q" data-q="1" style="flex:1">😕 Poor</button>
          </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:18px">
          <button type="button" class="btn btn-ghost" id="rgHarvestCancel" style="flex:1">Cancel</button>
          <button type="button" class="btn btn-primary" id="rgHarvestConfirm" style="flex:2">Save & clear line ↻</button>
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
  var CROP_KEY = 'rooted.activeCrop.' + bedId;
  var LINE_KEY = 'rooted.activeLine.' + bedId;

  // ---- active crop ----
  var activeCropId = parseInt(localStorage.getItem(CROP_KEY), 10) || 0;
  if (!activeCropId) {
    var $first = $('.rg-palette-chip').first();
    if ($first.length) activeCropId = parseInt($first.data('crop-id'), 10);
  }

  function setActiveCrop(cropId) {
    activeCropId = parseInt(cropId, 10) || 0;
    if (activeCropId) localStorage.setItem(CROP_KEY, String(activeCropId));
    $('.rg-palette-chip').each(function () {
      var cid = parseInt($(this).data('crop-id'), 10);
      if (cid === activeCropId) {
        var color = $(this).data('color');
        $(this).addClass('is-active').css({'background': color, 'border-color': color, 'color': '#fff'});
      } else {
        $(this).removeClass('is-active').removeAttr('style');
      }
    });
  }
  setActiveCrop(activeCropId);

  // ---- active line ----
  var activeLineNum = parseInt(localStorage.getItem(LINE_KEY), 10) || 0;

  function setActiveLine(lineNum) {
    activeLineNum = parseInt(lineNum, 10) || 0;
    if (activeLineNum) localStorage.setItem(LINE_KEY, String(activeLineNum));
    $('.rg-line').each(function () {
      var ln = parseInt($(this).data('line'), 10);
      $(this).removeClass('is-active');
      if (ln === activeLineNum) {
        $(this).addClass('is-active');
        // re-trigger animation by removing and re-adding class
        var el = this;
        el.style.animation = 'none';
        el.offsetHeight; // reflow
        el.style.animation = '';
      }
    });
    var $lbl = $('#rgPaletteLabel');
    if ($lbl.length) {
      if (activeLineNum) {
        $lbl.html('Adding to <strong>Line ' + activeLineNum + '</strong> — tap a crop below');
        $lbl.css('color', 'var(--color-primary)');
      } else {
        $lbl.html('Tap a line above to select it ↑');
        $lbl.css('color', '');
      }
    }
  }

  // Restore last active line on load
  if (activeLineNum) setActiveLine(activeLineNum);

  // Tap anywhere on a line header/stripe to select it (but not on buttons/links inside)
  $page.on('click', '.rg-line', function (e) {
    if ($(e.target).closest('button, a, .rg-stepper, .rg-palette-chip').length) return;
    setActiveLine($(this).data('line'));
  });

  // ---- palette chip: plant in active line ----
  $('#rgPalette').on('click', '.rg-palette-chip', function () {
    var cropId = parseInt($(this).data('crop-id'), 10);
    setActiveCrop(cropId);
    if (activeLineNum) {
      plantOne(activeLineNum, cropId);
    }
  });

  // ---- tap a dot to plant (also selects that line) ----
  $page.on('click', '.rg-dot--clickable', function (e) {
    e.stopPropagation();
    if (!activeCropId) return;
    var $line = $(this).closest('.rg-line');
    var lineNum = parseInt($line.data('line'), 10);
    setActiveLine(lineNum);
    plantOne(lineNum, activeCropId);
  });

  // ---- "Try this" suggestion chip ----
  $page.on('click', '.rg-plant-action', function (e) {
    e.stopPropagation();
    var lineNum = parseInt($(this).data('line'), 10);
    var cropId  = parseInt($(this).data('crop-id'), 10);
    setActiveLine(lineNum);
    plantOne(lineNum, cropId);
  });

  function plantOne(lineNum, cropId) {
    $.post('<?= url('/items/' . $bedId . '/plant-tap') ?>', {
      _token: csrf, line_number: lineNum, crop_id: cropId, count: 1
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not plant. Try again.'); });
  }

  // ---- stepper +/- ----
  $page.on('click', '.rg-step-plus, .rg-step-minus', function (e) {
    e.stopPropagation();
    var $stepper = $(this).closest('.rg-stepper');
    var pid = parseInt($stepper.data('planting-id'), 10);
    var delta = $(this).hasClass('rg-step-plus') ? 1 : -1;
    $.post('<?= url('/garden/plantings/') ?>' + pid + '/adjust-qty', { _token: csrf, delta: delta })
      .done(function () { window.location.reload(); })
      .fail(function () { alert('Could not adjust.'); });
  });

  // ---- per-crop remove ----
  $page.on('click', '.rg-stepchip-remove', function (e) {
    e.stopPropagation();
    var pid = parseInt($(this).data('planting-id'), 10);
    $.post('<?= url('/garden/plantings/') ?>' + pid + '/remove', { _token: csrf })
      .done(function () { window.location.reload(); })
      .fail(function () { alert('Could not remove.'); });
  });

  // ---- clear all on line ----
  $page.on('click', '.rg-clear-btn', function (e) {
    e.stopPropagation();
    if (!confirm('Clear all plantings on this line?')) return;
    var lineNum = parseInt($(this).data('line'), 10);
    $.post('<?= url('/items/' . $bedId . '/clear-line') ?>', { _token: csrf, line_number: lineNum })
      .done(function () { window.location.reload(); })
      .fail(function () { alert('Could not clear line.'); });
  });

  // ---- harvest blackout ----
  var harvestLine = null;
  $page.on('click', '.rg-harvest-btn', function (e) {
    e.stopPropagation();
    harvestLine = parseInt($(this).data('line'), 10);
    $('#rgHarvestLine').text(harvestLine);
    $('#rgHarvestQty').val('');
    $('.rg-q').removeClass('btn-primary').addClass('btn-secondary');
    $('#rgHarvestModal').css('display', 'flex');
  });
  function closeHarvest() { $('#rgHarvestModal').hide(); harvestLine = null; }
  $('#rgHarvestClose, #rgHarvestCancel').on('click', closeHarvest);
  $('.rg-q').on('click', function () {
    $('.rg-q').removeClass('btn-primary').addClass('btn-secondary');
    $(this).removeClass('btn-secondary').addClass('btn-primary');
  });
  $('#rgHarvestConfirm').on('click', function () {
    if (harvestLine === null) return;
    var qty = parseFloat($('#rgHarvestQty').val()) || 0;
    var unit = $('#rgHarvestUnit').val();
    $.post('<?= url('/items/' . $bedId . '/harvest-clear') ?>', {
      _token: csrf, line_number: harvestLine, qty: qty, unit: unit
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not save harvest.'); });
  });
})();
</script>
