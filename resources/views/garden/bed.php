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
<!-- Desktop crop-info sidebar (hidden on mobile, position:fixed) -->
<aside class="rg-bed-sidebar" id="rgBedSidebar">
  <div class="rg-sidebar-head">Crop Info</div>
  <div id="rgSidebarContent" class="rg-sidebar-empty">
    <div class="rg-sidebar-hint">Select a crop from the palette to see details</div>
  </div>
</aside>

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
    $overcap  = $fill['used'] > (int)$line['lengthCm'];
    $usedPct  = ((int)$line['lengthCm'] > 0) ? ($fill['used'] / (int)$line['lengthCm']) : 0;
    $overpack = $usedPct >= 0.9;

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
    // Pre-compute overpack banner values to keep template clean
    $lineClass      = $overcap ? 'rg-line--overcap' : ($overpack ? 'rg-line--overpack' : '');
    $fillLabel      = $overpack ? '&middot; <strong style="color:#b91c1c">' . round($usedPct * 100) . '%</strong>' : '';
    $bannerBg       = $overcap ? 'rgba(220,38,38,.08)'  : 'rgba(234,179,8,.12)';
    $bannerBorder   = $overcap ? 'rgba(220,38,38,.35)'  : 'rgba(234,179,8,.45)';
    $bannerColor    = $overcap ? '#991b1b' : '#854d0e';
    $bannerIcon     = $overcap ? "\xE2\x9B\x94" : "\xE2\x9A\xA0\xEF\xB8\x8F";
    $bannerMsg      = $overcap ? 'Overpacked — plants are competing for space, expect smaller yields.'
                               : 'Nearly full — careful, you are close to overpacking the line.';
  ?>
    <div class="rg-line <?= e($lineClass) ?>" data-line="<?= (int)$line['lineNumber'] ?>" data-length-cm="<?= (int)$line['lengthCm'] ?>">
      <div class="rg-line-head">
        <div class="rg-line-num"><?= (int)$line['lineNumber'] ?></div>
        <div class="rg-line-name">
          Line <?= (int)$line['lineNumber'] ?>
          <span class="rg-line-fill">&middot; <?= (int)$fill['used'] ?>/<?= (int)$line['lengthCm'] ?>cm <?= $fillLabel ?></span>
        </div>
        <div class="rg-line-actions" style="display:<?= empty($line['plantings']) ? 'none' : 'inline-flex' ?>">
          <button class="btn btn-secondary btn-sm rg-harvest-btn" data-line="<?= (int)$line['lineNumber'] ?>" style="border-color:var(--color-accent);color:var(--color-accent)">&#x1F33E; Harvest</button>
        </div>
      </div>
      <?php if ($overpack): ?>
      <div class="rg-overpack-banner" style="display:flex;align-items:center;gap:8px;padding:7px 11px;margin:4px 0 8px;background:<?= e($bannerBg) ?>;border:1px solid <?= e($bannerBorder) ?>;border-radius:8px;font-size:.78rem;color:<?= e($bannerColor) ?>;font-weight:600">
        <span style="font-size:1rem"><?= e($bannerIcon) ?></span>
        <span><?= e($bannerMsg) ?></span>
      </div>
      <?php endif; ?>

      <!-- Stripe -->
      <div class="rg-stripe">
        <?php foreach ($segments as $s):
          $w = max(0.5, $s['pct'] * 100);
          $c = $s['crop'];
          $color = $c['color'];
        ?>
          <div class="rg-stripe-seg" data-planting-id="<?= (int)$s['plantingId'] ?>" style="width: <?= $w ?>%; background: linear-gradient(180deg, <?= e($color) ?>, <?= e($color) ?>dd);">
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
      <div class="rg-steppers" style="display:<?= empty($line['plantings']) ? 'none' : 'flex' ?>">
        <?php foreach ($line['plantings'] as $p):
          $c = $cropsById[$p['cropId']] ?? null;
          if (!$c) continue;
          $color = $c['color'];
        ?>
          <div class="rg-stepchip" draggable="true" data-planting-id="<?= (int)$p['id'] ?>" data-crop-id="<?= (int)$p['cropId'] ?>" data-spacing-cm="<?= (int)($c['spacing_cm'] ?? 0) ?>" data-color="<?= e($color) ?>" style="background:<?= e($color) ?>15;border:1px solid <?= e($color) ?>55;color:<?= e($color) ?>;cursor:grab">
            <span class="rg-stepchip-emoji"><?= e($c['emoji']) ?></span>
            <span><?= e($c['name']) ?></span>
            <div class="rg-stepper" data-planting-id="<?= (int)$p['id'] ?>">
              <button type="button" class="rg-step-minus" style="color:<?= e($color) ?>" aria-label="-1">−</button>
              <input type="number" class="rg-stepper-val" min="1" step="1" value="<?= (int)$p['plants'] ?>" inputmode="numeric" aria-label="Plant count">
              <button type="button" class="rg-step-plus"  style="color:<?= e($color) ?>" aria-label="+1">+</button>
            </div>
            <button type="button" class="rg-stepchip-remove" data-planting-id="<?= (int)$p['id'] ?>" title="Remove <?= e($c['name']) ?> from this line">✕</button>
          </div>
        <?php endforeach; ?>
        <button type="button" class="rg-clear-btn" data-line="<?= (int)$line['lineNumber'] ?>">Clear All</button>
      </div>

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
      Tap a line to select it, then tap a crop to plant
    </div>
    <div class="rg-palette-row">
      <?php foreach ($catalog as $c): ?>
      <button type="button" class="rg-palette-chip" draggable="true"
        data-crop-id="<?= (int)$c['id'] ?>"
        data-color="<?= e($c['color']) ?>"
        data-spacing="<?= (int)$c['spacing_cm'] ?>"
        data-name="<?= e($c['name']) ?>"
        data-emoji="<?= e($c['emoji']) ?>"
        data-days="<?= (int)$c['days_to_maturity'] ?>"
        data-variety="<?= e($c['variety'] ?? '') ?>"
        data-botanical-family="<?= e($c['botanical_family'] ?? '') ?>"
        data-type="<?= e($c['type'] ?? 'vegetable') ?>"
        data-sowing-type="<?= e($c['sowing_type'] ?? '') ?>"
        data-days-germ="<?= (int)($c['days_to_germinate'] ?? 0) ?>"
        data-row-spacing="<?= (int)($c['row_spacing_cm'] ?? 0) ?>"
        data-sowing-depth="<?= (int)($c['sowing_depth_mm'] ?? 0) ?>"
        data-sun="<?= e($c['sun_exposure'] ?? '') ?>"
        data-soil="<?= e($c['soil_notes'] ?? '') ?>"
        data-frost-hardy="<?= !empty($c['frost_hardy']) ? '1' : '0' ?>"
        data-family="<?= e($c['family'] ?? 'other') ?>"
        data-season="<?= e($c['season'] ?? 'any') ?>"
        data-stock="<?= (float)($c['stock_qty'] ?? 0) ?>"
        data-stock-unit="<?= e($c['stock_unit'] ?? 'seeds') ?>"
        data-planting-months="<?= e($c['planting_months'] ?? '') ?>"
        data-harvest-months="<?= e($c['harvest_months'] ?? '') ?>"
        data-companions="<?= e($c['companions'] ?? '') ?>"
        data-antagonists="<?= e($c['antagonists'] ?? '') ?>"
        data-notes="<?= e($c['notes'] ?? '') ?>">
        <span class="rg-palette-chip-emoji"><?= e($c['emoji']) ?></span>
        <span><?= e($c['name']) ?></span>
        <span class="rg-palette-chip-spacing"><?= (int)$c['spacing_cm'] ?>cm</span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Seed info modal (double-tap palette chip) -->
  <div class="rg-blackout" id="rgSeedInfoModal" style="display:none">
    <div id="rgSeedInfoCard" style="background:#2c4a30;color:#e8f0e9;border-radius:18px 18px 0 0;width:100%;max-height:92vh;display:flex;flex-direction:column;overflow:hidden">
      <div id="rgSeedInfoHead" style="display:flex;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0">
        <div style="flex:1;display:flex;align-items:center;gap:12px">
          <span id="rgSeedInfoEmoji" style="font-size:2rem;line-height:1"></span>
          <div>
            <div id="rgSeedInfoName" style="font-weight:800;font-size:1.05rem;color:#e8f0e9"></div>
            <div id="rgSeedInfoVariety" style="font-size:.78rem;color:rgba(255,255,255,.5)"></div>
          </div>
        </div>
        <button type="button" id="rgSeedInfoClose" style="background:rgba(255,255,255,.1);border:none;color:#e8f0e9;font-size:1.3rem;line-height:1;width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center">×</button>
      </div>
      <div id="rgSeedInfoBody" style="overflow-y:auto;flex:1;-webkit-overflow-scrolling:touch"></div>
    </div>
  </div>

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

<!-- Toast notification (fixed above palette) -->
<div id="rgToast" style="
  display:none;position:fixed;bottom:90px;left:50%;transform:translateX(-50%);
  max-width:360px;width:calc(100% - 32px);
  padding:11px 16px;border-radius:10px;
  font-size:.84rem;font-weight:600;color:#fff;
  z-index:60;box-shadow:0 4px 16px rgba(0,0,0,.22);
  transition:opacity .25s;pointer-events:none;text-align:center
"></div>

<style>
@keyframes rg-toast-in {
  from { opacity:0; transform:translateX(-50%) translateY(8px); }
  to   { opacity:1; transform:translateX(-50%) translateY(0); }
}
#rgToast.is-visible { animation: rg-toast-in .2s ease forwards; }
</style>

<style>
/* Drag-and-drop states */
.rg-palette-chip[draggable] { cursor: grab; }
.rg-palette-chip.is-dragging { opacity: .45; cursor: grabbing; transform: scale(.96); }
.rg-line.is-drag-over {
  border: 2.5px dashed var(--color-primary) !important;
  background: rgba(22,101,52,.07) !important;
  box-shadow: inset 0 0 0 3px rgba(22,101,52,.12) !important;
}
.rg-line.is-planting { opacity: .6; pointer-events: none; }
</style>

<script>
(function () {
  var $page    = $('#rgBedPage');
  var bedId    = parseInt($page.data('item-id'), 10);
  var csrf     = <?= json_encode($csrf) ?>;
  var PLANT_URL = '<?= url('/items/' . $bedId . '/plant-tap') ?>';
  var LINE_KEY  = 'rooted.activeLine.' + bedId;
  var CROP_KEY  = 'rooted.activeCrop.' + bedId;
  var isMobile  = window.innerWidth < 900;

  // ── Crops map (from palette data attributes) ─────────────────────
  var cropsMap = {};
  $('#rgPalette .rg-palette-chip').each(function () {
    var $c = $(this), id = parseInt($c.data('crop-id'), 10);
    cropsMap[id] = {
      id: id, name: $c.data('name') || '', emoji: $c.data('emoji') || '',
      color: $c.data('color') || '#A66141', spacing: parseInt($c.data('spacing'), 10) || 5,
      days: parseInt($c.data('days'), 10) || 0, variety: $c.data('variety') || '',
      botanicalFamily: $c.data('botanical-family') || '',
      type: $c.data('type') || 'vegetable',
      sowingType: $c.data('sowing-type') || '',
      daysGerm: parseInt($c.data('days-germ'), 10) || 0,
      rowSpacing: parseInt($c.data('row-spacing'), 10) || 0,
      sowingDepth: parseInt($c.data('sowing-depth'), 10) || 0,
      sun: $c.data('sun') || '',
      soil: $c.data('soil') || '',
      frostHardy: $c.data('frost-hardy') === '1',
      family: $c.data('family') || 'other',
      season: $c.data('season') || 'any',
      stock: parseFloat($c.data('stock')) || 0, stockUnit: $c.data('stock-unit') || 'seeds',
      plantingMonths: $c.data('planting-months') || '',
      harvestMonths: $c.data('harvest-months') || '',
      companions: $c.data('companions') || '',
      antagonists: $c.data('antagonists') || '',
      notes: $c.data('notes') || '',
    };
  });

  // ── Toast ───────────────────────────────────────────────────────
  var _toastTimer = null;
  function showToast(msg, type) {
    var $t = $('#rgToast');
    if (_toastTimer) clearTimeout(_toastTimer);
    $t.text(msg)
      .css('background', type === 'error' ? '#dc2626' : type === 'warn' ? '#d97706' : '#16a34a')
      .css({display:'block', opacity:''}).addClass('is-visible');
    _toastTimer = setTimeout(function () {
      $t.css('opacity', 0);
      setTimeout(function () { $t.css({display:'none', opacity:''}); }, 260);
    }, type === 'error' ? 4000 : 2500);
  }
  function ajaxErr(xhr, fallback) {
    var msg = fallback;
    try { msg = JSON.parse(xhr.responseText).error || msg; } catch(e){}
    showToast(msg, 'error');
  }

  // ── recalcLine — redraws fill, stripe, dots, banner without reload ─
  function recalcLine($line) {
    var lengthCm = parseInt($line.data('length-cm'), 10) || 100;
    var chips = [];
    $line.find('.rg-stepchip').each(function () {
      var $chip = $(this);
      var plants = parseInt($chip.find('.rg-stepper-val').val(), 10) || 0;
      if (plants > 0) chips.push({
        pid: parseInt($chip.data('planting-id'), 10),
        spacingCm: parseInt($chip.data('spacing-cm'), 10) || 5,
        plants: plants,
        color: $chip.data('color') || '#A66141',
        emoji: $chip.find('.rg-stepchip-emoji').text() || '',
      });
    });

    var usedCm = 0;
    chips.forEach(function (c) { usedCm += c.spacingCm * c.plants; });
    var pct     = lengthCm > 0 ? usedCm / lengthCm : 0;
    var overcap = usedCm > lengthCm;
    var overpack = pct >= 0.9;

    // Fill label
    var fillHtml = '&middot; ' + usedCm + '/' + lengthCm + 'cm';
    if (overpack) fillHtml += ' &middot; <strong style="color:#b91c1c">' + Math.round(pct * 100) + '%</strong>';
    $line.find('.rg-line-fill').html(fillHtml);

    // Line border class
    $line.removeClass('rg-line--overcap rg-line--overpack');
    if (overcap) $line.addClass('rg-line--overcap');
    else if (overpack) $line.addClass('rg-line--overpack');

    // Stripe bar
    var $stripe = $line.find('.rg-stripe');
    $stripe.empty();
    chips.forEach(function (c) {
      var w = Math.max(0.5, (c.spacingCm * c.plants / lengthCm) * 100);
      var $seg = $('<div class="rg-stripe-seg">').attr('data-planting-id', c.pid)
        .css({width: w + '%', background: 'linear-gradient(180deg,' + c.color + ',' + c.color + 'dd)'});
      if (w > 10) $seg.append('<span>' + c.emoji + ' ' + c.plants + '</span>');
      $stripe.append($seg);
    });

    // Dot grid
    var totalSlots = Math.max(1, Math.floor(lengthCm / 5));
    var $dotgrid = $line.find('.rg-dotgrid');
    $dotgrid.toggleClass('rg-dotgrid--dense', totalSlots > 60);
    var $dots = $dotgrid.find('.rg-dots'), cur = 0;
    $dots.empty();
    chips.forEach(function (c) {
      var spp = Math.max(1, Math.round(c.spacingCm / 5));
      for (var n = 0; n < c.plants && cur + spp <= totalSlots; n++) {
        for (var k = 0; k < spp && cur < totalSlots; k++) {
          $dots.append('<div class="rg-dot rg-dot--filled' + (k === 0 ? ' is-head' : '') + '" style="background:' + c.color + ';border-color:' + c.color + '"></div>');
          cur++;
        }
      }
    });
    for (var i = cur; i < totalSlots; i++) {
      $dots.append('<div class="rg-dot rg-dot--clickable" data-slot="' + i + '" title="tap to plant"></div>');
    }

    // Overpack banner
    var $banner = $line.find('.rg-overpack-banner');
    if (overpack) {
      var bg  = overcap ? 'rgba(220,38,38,.08)' : 'rgba(234,179,8,.12)';
      var bdr = overcap ? 'rgba(220,38,38,.35)' : 'rgba(234,179,8,.45)';
      var clr = overcap ? '#991b1b' : '#854d0e';
      var ico = overcap ? '⛔' : '⚠️';
      var msg = overcap ? 'Overpacked — plants are competing for space, expect smaller yields.'
                        : 'Nearly full — careful, you are close to overpacking the line.';
      if (!$banner.length) {
        $banner = $('<div class="rg-overpack-banner" style="display:flex;align-items:center;gap:8px;padding:7px 11px;margin:4px 0 8px;border-radius:8px;font-size:.78rem;font-weight:600">' +
          '<span class="rg-banner-icon" style="font-size:1rem"></span><span class="rg-banner-msg"></span></div>');
        $stripe.before($banner);
      }
      $banner.css({background: bg, border: '1px solid ' + bdr, color: clr}).show();
      $banner.find('.rg-banner-icon').text(ico);
      $banner.find('.rg-banner-msg').text(msg);
    } else if ($banner.length) {
      $banner.hide();
    }

    // Show/hide steppers and harvest button
    var hasChips = chips.length > 0;
    $line.find('.rg-steppers').css('display', hasChips ? 'flex' : 'none');
    $line.find('.rg-line-actions').css('display', hasChips ? 'inline-flex' : 'none');
  }

  // ── Sidebar (desktop) / seed info modal (mobile) ─────────────────
  var MONTH_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  function buildCropInfoHtml(crop, darkMode) {
    var bg   = darkMode ? 'rgba(255,255,255,.06)'  : 'var(--color-surface)';
    var bdr  = darkMode ? 'rgba(255,255,255,.08)'  : 'var(--color-border)';
    var mute = darkMode ? 'rgba(255,255,255,.45)'  : 'var(--color-text-muted)';
    var txt  = darkMode ? '#e8f0e9'                : 'var(--color-text)';
    var green = '#4ade80';

    function row(label, val) {
      return '<div style="display:flex;justify-content:space-between;align-items:baseline;padding:8px 16px;border-bottom:1px solid ' + bdr + '">' +
        '<span style="color:' + mute + ';white-space:nowrap;margin-right:8px;font-size:.75rem">' + label + '</span>' +
        '<span style="text-align:right;font-size:.8rem;font-weight:600;color:' + txt + '">' + val + '</span>' +
      '</div>';
    }

    function monthCalendar(title, monthStr, activeColor) {
      if (!monthStr) return '';
      var active = {};
      String(monthStr).replace(/\d+/g, function(m){ active[parseInt(m,10)] = true; });
      var hasAny = false;
      for (var k in active) { hasAny = true; break; }
      if (!hasAny) return '';
      var dots = '';
      for (var mo = 1; mo <= 12; mo++) {
        var on = active[mo];
        dots += '<div style="display:flex;flex-direction:column;align-items:center;gap:3px">' +
          '<div style="width:8px;height:8px;border-radius:50%;background:' + (on ? activeColor : bdr) + ';opacity:' + (on ? '1' : '.4') + '"></div>' +
          '<span style="font-size:.5rem;color:' + mute + '">' + MONTH_ABBR[mo-1].slice(0,1) + '</span>' +
        '</div>';
      }
      return '<div style="padding:10px 16px;border-bottom:1px solid ' + bdr + '">' +
        '<div style="font-size:.6rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:' + mute + ';margin-bottom:8px">' + title + '</div>' +
        '<div style="display:flex;gap:4px;justify-content:space-between">' + dots + '</div>' +
      '</div>';
    }

    function tagList(jsonStr, color) {
      if (!jsonStr) return '';
      var items = [];
      try { items = JSON.parse(jsonStr); } catch(e) {
        items = String(jsonStr).replace(/[\[\]"]/g,'').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
      }
      if (!items.length) return '';
      return items.map(function(t) {
        return '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:.7rem;font-weight:600;background:' + color + '22;color:' + color + ';margin:2px 2px 0 0">' + String(t).replace(/</g,'&lt;') + '</span>';
      }).join('');
    }

    // Core rows
    var typeLabel = crop.type  ? crop.type.charAt(0).toUpperCase() + crop.type.slice(1) : '';
    var famLabel  = crop.family ? crop.family.charAt(0).toUpperCase() + crop.family.slice(1) : '';
    var seaLabel  = crop.season === 'warm' ? '☀️ Warm' : crop.season === 'cool' ? '🌧 Cool' : '';
    var stockOk   = crop.stock > 0;
    var stockTxt  = stockOk ? crop.stock + ' ' + crop.stockUnit : 'Out of stock';
    var stockColor = stockOk ? green : '#f87171';

    var rows = '';
    if (typeLabel)       rows += row('Type', typeLabel);
    if (famLabel)        rows += row('Family', famLabel);
    if (crop.botanicalFamily) rows += row('Botanical family', crop.botanicalFamily);
    if (seaLabel)        rows += row('Season', seaLabel);
    if (crop.frostHardy) rows += row('Frost hardy', '❄️ Yes');
    if (crop.sowingType) rows += row('Sowing', crop.sowingType.charAt(0).toUpperCase() + crop.sowingType.slice(1));
    if (crop.daysGerm)   rows += row('Days to germinate', crop.daysGerm + 'd');
    if (crop.days)       rows += row('Days to maturity', crop.days + 'd');
    if (crop.spacing)    rows += row('Spacing', crop.spacing + ' cm');
    if (crop.rowSpacing) rows += row('Row spacing', crop.rowSpacing + ' cm');
    if (crop.sowingDepth) rows += row('Sowing depth', crop.sowingDepth + ' mm');
    if (crop.sun)        rows += row('Sun', crop.sun);
    rows += row('Stock', '<span style="color:' + stockColor + '">' + stockTxt + '</span>');

    // Soil notes inline row
    var soilHtml = '';
    if (crop.soil) {
      soilHtml = '<div style="padding:8px 16px;border-bottom:1px solid ' + bdr + '">' +
        '<div style="font-size:.6rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:' + mute + ';margin-bottom:4px">Soil notes</div>' +
        '<div style="font-size:.76rem;line-height:1.5;color:' + txt + '">' + crop.soil.replace(/</g,'&lt;') + '</div>' +
      '</div>';
    }

    // Companions / antagonists
    var compHtml = '';
    var compTags = tagList(crop.companions, green);
    if (compTags) compHtml = '<div style="padding:8px 16px;border-bottom:1px solid ' + bdr + '">' +
      '<div style="font-size:.6rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:' + mute + ';margin-bottom:4px">Companions</div>' +
      '<div>' + compTags + '</div>' +
    '</div>';
    var antHtml = '';
    var antTags = tagList(crop.antagonists, '#f87171');
    if (antTags) antHtml = '<div style="padding:8px 16px;border-bottom:1px solid ' + bdr + '">' +
      '<div style="font-size:.6rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:' + mute + ';margin-bottom:4px">Antagonists</div>' +
      '<div>' + antTags + '</div>' +
    '</div>';

    // Notes
    var notesHtml = '';
    if (crop.notes) notesHtml = '<div style="padding:10px 16px;font-size:.74rem;line-height:1.6;color:' + mute + '">' + crop.notes.replace(/</g,'&lt;') + '</div>';

    // Type emoji per category
    var typeEmojiMap = { vegetable:'🥦', herb:'🌿', fruit:'🍓', flower:'🌸', other:'🌾' };
    var tEmoji = typeEmojiMap[crop.type] || crop.emoji || '🌱';

    // Header — shown in both sidebar (darkMode) and light modal
    var header =
      '<div style="padding:20px 16px 14px;text-align:center;border-bottom:1px solid ' + bdr + '">' +
        '<div style="font-size:2.6rem;line-height:1;margin-bottom:8px">' + tEmoji + '</div>' +
        '<div style="height:3px;border-radius:999px;margin:0 32px 10px;background:' + (crop.color || '#4ade80') + '"></div>' +
        '<div style="font-weight:800;font-size:1.15rem;color:' + txt + ';line-height:1.2">' + (crop.name || '') + '</div>' +
        (crop.variety ? '<div style="font-size:.75rem;color:' + mute + ';margin-top:4px">' + crop.variety + '</div>' : '') +
      '</div>';

    return header + rows + soilHtml +
      monthCalendar('Planting months', crop.plantingMonths, '#34d399') +
      monthCalendar('Harvest months',  crop.harvestMonths,  crop.color || '#A66141') +
      compHtml + antHtml + notesHtml;
  }

  function darkenHex(hex, factor) {
    hex = (hex || '').replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(function(c){return c+c;}).join('');
    if (hex.length !== 6) return '#2c4a30';
    var r = Math.round(parseInt(hex.substr(0,2),16) * factor);
    var g = Math.round(parseInt(hex.substr(2,2),16) * factor);
    var b = Math.round(parseInt(hex.substr(4,2),16) * factor);
    return '#' + [r,g,b].map(function(v){return v.toString(16).padStart(2,'0');}).join('');
  }

  function updateCropSidebar(crop) {
    if (!crop) return;
    var bg = crop.color ? darkenHex(crop.color, 0.38) : '#2c4a30';
    $('#rgBedSidebar').css({background: bg, borderRightColor: 'rgba(255,255,255,.08)'});
    $('#rgSidebarContent').html(buildCropInfoHtml(crop, true)).removeClass('rg-sidebar-empty');
  }

  function showSeedInfoModal(crop) {
    $('#rgSeedInfoEmoji').text(crop.emoji || '🌱');
    $('#rgSeedInfoName').text(crop.name || '');
    $('#rgSeedInfoVariety').text(crop.variety || '');
    // Color accent bar under emoji in head
    var bar = $('#rgSeedInfoHead').find('.rg-seed-colorbar');
    if (!bar.length) bar = $('<div class="rg-seed-colorbar" style="height:3px;border-radius:999px;margin:6px 0 0"></div>').appendTo($('#rgSeedInfoHead'));
    bar.css('background', crop.color || '#4ade80');
    $('#rgSeedInfoBody').html(buildCropInfoHtml(crop, true));
    $('#rgSeedInfoModal').css('display', 'flex').css('align-items', 'flex-end');
  }
  $('#rgSeedInfoClose').on('click', function () { $('#rgSeedInfoModal').hide(); });

  // ── Core plant action ────────────────────────────────────────────
  function plantOne(lineNum, cropId) {
    var $line = $page.find('.rg-line[data-line="' + lineNum + '"]');
    $line.addClass('is-planting');
    var crop = cropsMap[cropId] || {};
    $.post(PLANT_URL, { _token: csrf, line_number: lineNum, crop_id: cropId, count: 1 })
      .done(function (data) {
        $line.removeClass('is-planting');
        if (data && data.success === false) {
          showToast(data.error || 'Could not plant — try again', 'error');
          return;
        }
        var pid    = parseInt(data.planting_id, 10);
        var plants = parseInt(data.plants, 10) || 1;
        // Update existing chip or create new one
        var $chip = $line.find('.rg-stepchip[data-planting-id="' + pid + '"]');
        if ($chip.length) {
          $chip.find('.rg-stepper-val').val(plants).data('orig', plants);
        } else {
          var color  = crop.color || '#A66141';
          var $step  = $line.find('.rg-steppers');
          var $clrBtn = $step.find('.rg-clear-btn');
          var newChip = '<div class="rg-stepchip" draggable="true"' +
            ' data-planting-id="' + pid + '"' +
            ' data-crop-id="' + cropId + '"' +
            ' data-spacing-cm="' + (crop.spacing || 5) + '"' +
            ' data-color="' + color + '"' +
            ' style="background:' + color + '15;border:1px solid ' + color + '55;color:' + color + ';cursor:grab">' +
            '<span class="rg-stepchip-emoji">' + (crop.emoji || '') + '</span>' +
            '<span>' + (crop.name || '') + '</span>' +
            '<div class="rg-stepper" data-planting-id="' + pid + '">' +
            '<button type="button" class="rg-step-minus" style="color:' + color + '" aria-label="-1">−</button>' +
            '<input type="number" class="rg-stepper-val" min="1" step="1" value="' + plants + '" inputmode="numeric" aria-label="Plant count">' +
            '<button type="button" class="rg-step-plus" style="color:' + color + '" aria-label="+1">+</button>' +
            '</div>' +
            '<button type="button" class="rg-stepchip-remove" data-planting-id="' + pid + '" title="Remove">✕</button>' +
            '</div>';
          if ($clrBtn.length) $clrBtn.before(newChip);
          else $step.append(newChip);
        }
        recalcLine($line);
        showToast('Planted!', 'ok');
      })
      .fail(function (xhr) {
        $line.removeClass('is-planting');
        ajaxErr(xhr, 'Could not plant — try again');
      });
  }

  // ── Active line ──────────────────────────────────────────────────
  var activeLineNum = parseInt(localStorage.getItem(LINE_KEY), 10) || 0;
  function setActiveLine(lineNum) {
    var requested = parseInt(lineNum, 10) || 0;
    // toggle off if already active
    activeLineNum = (requested && requested === activeLineNum) ? 0 : requested;
    if (activeLineNum) localStorage.setItem(LINE_KEY, String(activeLineNum));
    else localStorage.removeItem(LINE_KEY);
    $page.find('.rg-line').each(function () {
      var ln = parseInt($(this).data('line'), 10);
      if (ln === activeLineNum) {
        if (!$(this).hasClass('is-active')) {
          $(this).addClass('is-active');
          var el = this; el.style.animation = 'none'; el.offsetHeight; el.style.animation = '';
        }
      } else {
        $(this).removeClass('is-active');
      }
    });
    var $lbl = $('#rgPaletteLabel');
    if (activeLineNum) {
      $lbl.html('Tap a crop to plant in <strong>Line ' + activeLineNum + '</strong>').css('color','var(--color-primary)');
    } else {
      $lbl.html('Select a line, then tap a crop to plant').css('color','');
    }
  }
  if (activeLineNum) setActiveLine(activeLineNum);

  $page.on('click', '.rg-line', function (e) {
    if ($(e.target).closest('button, a, .rg-stepper').length) return;
    setActiveLine(parseInt($(this).data('line'), 10));
  });

  // ── Active crop ──────────────────────────────────────────────────
  var activeCropId = parseInt(localStorage.getItem(CROP_KEY), 10) || 0;
  if (!activeCropId) activeCropId = parseInt($('.rg-palette-chip').first().data('crop-id'), 10) || 0;
  function setActiveCrop(cropId) {
    activeCropId = parseInt(cropId, 10) || 0;
    if (activeCropId) localStorage.setItem(CROP_KEY, String(activeCropId));
    $('#rgPalette .rg-palette-chip').each(function () {
      if (parseInt($(this).data('crop-id'), 10) === activeCropId) {
        var col = $(this).data('color');
        $(this).addClass('is-active').css({background: col, 'border-color': col, color: '#fff'});
      } else {
        $(this).removeClass('is-active').removeAttr('style');
      }
    });
    if (activeCropId && cropsMap[activeCropId]) updateCropSidebar(cropsMap[activeCropId]);
  }
  setActiveCrop(activeCropId);

  // ── Palette chip interaction ─────────────────────────────────────
  // Desktop: tap selects crop + updates sidebar; plants only when a line is active.
  // Mobile:  touchend always intercepts (prevents click). Single tap = select + plant if line active.
  //          Double-tap = show info modal only (no plant even if line active).
  var _lastTap = 0, _lastTapEl = null;

  $('#rgPalette').on('touchend', '.rg-palette-chip', function (e) {
    e.preventDefault(); // always block the synthetic click on mobile
    var $chip = $(this);
    var cropId = parseInt($chip.data('crop-id'), 10);
    var now = Date.now(), el = this;
    if (now - _lastTap < 320 && _lastTapEl === el) {
      // double-tap → info modal only
      if (cropsMap[cropId]) showSeedInfoModal(cropsMap[cropId]);
      _lastTap = 0; _lastTapEl = null;
    } else {
      _lastTap = now; _lastTapEl = el;
      setActiveCrop(cropId);
      if (activeLineNum) plantOne(activeLineNum, cropId);
    }
  });

  $('#rgPalette').on('click', '.rg-palette-chip', function (e) {
    var cropId = parseInt($(this).data('crop-id'), 10);
    setActiveCrop(cropId);
    if (activeLineNum) plantOne(activeLineNum, cropId);
    // no toast — blank space click just selects the crop and shows info
  });

  // ── Suggestion chips + dot clicks ────────────────────────────────
  $page.on('click', '.rg-plant-action', function (e) {
    e.stopPropagation();
    plantOne(parseInt($(this).data('line'), 10), parseInt($(this).data('crop-id'), 10));
  });
  $page.on('click', '.rg-dot--clickable', function (e) {
    e.stopPropagation();
    if (!activeCropId) { showToast('Select a crop from the palette first', 'warn'); return; }
    var lineNum = parseInt($(this).closest('.rg-line').data('line'), 10);
    setActiveLine(lineNum);
    plantOne(lineNum, activeCropId);
  });

  // ── HTML5 Drag-and-Drop ──────────────────────────────────────────
  $page.on('dragstart', '.rg-palette-chip', function (e) {
    var cropId = $(this).data('crop-id');
    e.originalEvent.dataTransfer.setData('text/plain', String(cropId));
    e.originalEvent.dataTransfer.effectAllowed = 'copy';
    $(this).addClass('is-dragging');
    setActiveCrop(cropId);
  });
  $page.on('dragend', '.rg-palette-chip', function () {
    $(this).removeClass('is-dragging');
    $page.find('.rg-line').removeClass('is-drag-over');
  });
  $page.on('dragover', '.rg-line', function (e) {
    e.preventDefault();
    e.originalEvent.dataTransfer.dropEffect = 'copy';
    $(this).addClass('is-drag-over');
  });
  $page.on('dragleave', '.rg-line', function (e) {
    if (!this.contains(e.originalEvent.relatedTarget)) $(this).removeClass('is-drag-over');
  });
  $page.on('drop', '.rg-line', function (e) {
    e.preventDefault();
    $(this).removeClass('is-drag-over');
    var dt = e.originalEvent.dataTransfer;
    if (dt.types && Array.prototype.indexOf.call(dt.types, 'text/x-rooted-reorder') >= 0) return;
    var cropId  = parseInt(dt.getData('text/plain'), 10);
    var lineNum = parseInt($(this).data('line'), 10);
    if (!cropId || !lineNum) return;
    setActiveLine(lineNum);
    plantOne(lineNum, cropId);
  });

  // ── Drag-reorder stepchips ────────────────────────────────────────
  $page.on('dragstart', '.rg-stepchip', function (e) {
    var pid = $(this).data('planting-id');
    if (!pid) return;
    e.originalEvent.dataTransfer.setData('text/x-rooted-reorder', String(pid));
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    $(this).addClass('is-dragging').css('opacity', '.5');
  });
  $page.on('dragend', '.rg-stepchip', function () {
    $(this).removeClass('is-dragging').css('opacity', '');
    $page.find('.rg-stepchip').removeClass('is-drop-target');
  });
  $page.on('dragover', '.rg-stepchip', function (e) {
    var dt = e.originalEvent.dataTransfer;
    if (!dt.types || Array.prototype.indexOf.call(dt.types, 'text/x-rooted-reorder') < 0) return;
    e.preventDefault(); e.stopPropagation();
    dt.dropEffect = 'move';
    $(this).addClass('is-drop-target');
  });
  $page.on('dragleave', '.rg-stepchip', function () { $(this).removeClass('is-drop-target'); });
  $page.on('drop', '.rg-stepchip', function (e) {
    var dt = e.originalEvent.dataTransfer;
    var srcId = parseInt(dt.getData('text/x-rooted-reorder'), 10);
    if (!srcId) return;
    e.preventDefault(); e.stopPropagation();
    var $target = $(this);
    $target.removeClass('is-drop-target');
    var tgtId = parseInt($target.data('planting-id'), 10);
    if (!tgtId || srcId === tgtId) return;
    var ids = [];
    $target.closest('.rg-steppers').find('.rg-stepchip').each(function () { ids.push(parseInt($(this).data('planting-id'), 10)); });
    var srcIdx = ids.indexOf(srcId); if (srcIdx >= 0) ids.splice(srcIdx, 1);
    var tgtIdx = ids.indexOf(tgtId); if (tgtIdx < 0) return;
    ids.splice(tgtIdx, 0, srcId);
    $.post('<?= url('/garden/plantings/reorder') ?>', { _token: csrf, planting_ids: ids })
      .done(function (d) {
        if (!d || d.success === false) { showToast((d && d.error) || 'Could not reorder', 'error'); return; }
        window.location.reload();
      })
      .fail(function () { showToast('Could not reorder', 'error'); });
  });

  // ── Stepper +/− (optimistic, no reload) ──────────────────────────
  $page.on('click', '.rg-step-plus, .rg-step-minus', function (e) {
    e.stopPropagation();
    var $btn    = $(this);
    var $stepper = $btn.closest('.rg-stepper');
    var pid     = parseInt($stepper.data('planting-id'), 10);
    var delta   = $btn.hasClass('rg-step-plus') ? 1 : -1;
    var $input  = $stepper.find('.rg-stepper-val');
    var orig    = parseInt($input.val(), 10) || 1;
    var newVal  = Math.max(1, orig + delta);
    $input.val(newVal).data('orig', newVal);
    var $line = $btn.closest('.rg-line');
    recalcLine($line);
    $.post('<?= url('/garden/plantings/') ?>' + pid + '/adjust-qty', { _token: csrf, count: newVal })
      .fail(function (xhr) {
        $input.val(orig).data('orig', orig);
        recalcLine($line);
        ajaxErr(xhr, 'Could not adjust quantity');
      });
  });

  // ── Stepper numeric entry (optimistic, no reload) ─────────────────
  $page.on('click focus', '.rg-stepper-val', function () { this.select(); });
  $page.on('keydown', '.rg-stepper-val', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
    else if (e.key === 'Escape') { this.value = $(this).data('orig') || this.value; this.blur(); }
  });
  $page.on('focus', '.rg-stepper-val', function () { $(this).data('orig', this.value); });
  $page.on('change blur', '.rg-stepper-val', function () {
    var $input = $(this);
    var orig   = parseInt($input.data('orig'), 10);
    var count  = parseInt($input.val(), 10);
    if (!count || count < 1) count = 1;
    $input.val(count);
    if (count === orig) return;
    var pid   = parseInt($input.closest('.rg-stepper').data('planting-id'), 10);
    var $line = $input.closest('.rg-line');
    $input.data('orig', count);
    recalcLine($line);
    $.post('<?= url('/garden/plantings/') ?>' + pid + '/adjust-qty', { _token: csrf, count: count })
      .done(function (d) {
        if (d && d.success === false) { $input.val(orig).data('orig', orig); recalcLine($line); showToast(d.error || 'Could not set', 'error'); }
      })
      .fail(function (xhr) { $input.val(orig).data('orig', orig); recalcLine($line); ajaxErr(xhr, 'Could not set quantity'); });
  });

  // ── Per-crop remove (no reload) ───────────────────────────────────
  $page.on('click', '.rg-stepchip-remove', function (e) {
    e.stopPropagation();
    var pid   = parseInt($(this).data('planting-id'), 10);
    var $chip = $(this).closest('.rg-stepchip');
    var $line = $chip.closest('.rg-line');
    $.post('<?= url('/garden/plantings/') ?>' + pid + '/remove', { _token: csrf })
      .done(function (d) {
        if (d && d.success === false) { showToast(d.error || 'Could not remove', 'error'); return; }
        $chip.remove();
        recalcLine($line);
      })
      .fail(function (xhr) { ajaxErr(xhr, 'Could not remove crop'); });
  });

  // ── Clear all (confirm + no reload) ──────────────────────────────
  $page.on('click', '.rg-clear-btn', function (e) {
    e.stopPropagation();
    var $btn  = $(this);
    var $line = $btn.closest('.rg-line');
    if ($btn.data('confirm')) {
      $btn.removeData('confirm').text('Clear All');
      $.post('<?= url('/items/' . $bedId . '/clear-line') ?>', { _token: csrf, line_number: parseInt($btn.data('line'), 10) })
        .done(function (d) {
          if (d && d.success === false) { showToast(d.error || 'Could not clear', 'error'); return; }
          $line.find('.rg-stepchip').remove();
          recalcLine($line);
        })
        .fail(function (xhr) { ajaxErr(xhr, 'Could not clear line'); });
    } else {
      $btn.data('confirm', true).text('Tap again to confirm');
      setTimeout(function () { if ($btn.data('confirm')) $btn.removeData('confirm').text('Clear All'); }, 3000);
    }
  });

  // ── Harvest modal (keeps reload — full state reset needed) ────────
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
  $page.on('click', '.rg-q', function () {
    $('.rg-q').removeClass('btn-primary').addClass('btn-secondary');
    $(this).removeClass('btn-secondary').addClass('btn-primary');
  });
  $('#rgHarvestConfirm').on('click', function () {
    if (harvestLine === null) return;
    $.post('<?= url('/items/' . $bedId . '/harvest-clear') ?>', {
      _token: csrf, line_number: harvestLine,
      qty: parseFloat($('#rgHarvestQty').val()) || 0, unit: $('#rgHarvestUnit').val()
    }).done(function (d) {
      if (d && d.success === false) { closeHarvest(); showToast(d.error || 'Could not save harvest', 'error'); }
      else window.location.reload();
    }).fail(function (xhr) { closeHarvest(); ajaxErr(xhr, 'Could not save harvest'); });
  });
})();
</script>
