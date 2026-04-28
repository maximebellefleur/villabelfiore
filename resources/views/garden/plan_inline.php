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
        <div class="rg-line-actions">
          <button class="btn btn-secondary btn-sm rg-harvest-btn" data-line="<?= (int)$line['lineNumber'] ?>" style="border-color:var(--color-accent);color:var(--color-accent)">🌾 Harvest</button>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!$empty): ?>
      <div class="rg-stripe rg-stripe--maturity">
        <?php foreach ($segments as $i => $s):
          $w = max(0.5, $s['pct'] * 100);
          $c = $s['crop'];
          $color = $c['color'];
          $planting = ['cropId' => $s['cropId'], 'sown_at' => $s['sown_at']];
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
      <div style="display:flex;align-items:baseline;gap:10px;margin-top:6px;font-size:.74rem;color:var(--color-text-muted)">
        <span><strong style="color:var(--color-text)">Sown</strong> <?= e(GardenHelpers::fmtDate($sownDate)) ?></span>
        <span>→</span>
        <span><strong style="color:var(--color-text)">Harvest</strong> ~<?= e(GardenHelpers::fmtDate($harvestDate)) ?>
          <?php if ($daysLeft !== null): ?>
            <span class="rg-mono">(<?= $daysLeft >= 0 ? 'in ' . $daysLeft . 'd' : abs($daysLeft) . 'd ago' ?>)</span>
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

  <!-- Harvest blackout -->
  <div class="rg-blackout" id="rgHarvestModal" style="display:none">
    <div class="rg-blackout-card">
      <div class="rg-blackout-head">
        <div style="flex:1">
          <div class="rg-label-tiny">Harvest</div>
          <div style="font-weight:800;font-size:1rem;margin-top:2px"><?= e($bed['name']) ?> · Line <span id="rgHarvestLine">—</span></div>
        </div>
        <button type="button" class="rg-blackout-close" id="rgHarvestClose">×</button>
      </div>
      <div class="rg-blackout-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Quantity</label><input type="number" step="0.1" class="form-input" id="rgHarvestQty" placeholder="0.0"></div>
          <div class="form-group form-group--sm"><label class="form-label">Unit</label><select class="form-input" id="rgHarvestUnit"><option>kg</option><option>g</option><option>L</option><option>bunch</option></select></div>
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
    $('#rgHarvestLine').text(harvestLine);
    $('#rgHarvestQty').val('');
    $('#rgHarvestModal').css('display', 'flex');
  });
  function closeHarvest() { $('#rgHarvestModal').hide(); harvestLine = null; }
  $('#rgHarvestClose, #rgHarvestCancel').on('click', closeHarvest);
  $('#rgHarvestConfirm').on('click', function () {
    if (harvestLine === null) return;
    var qty = parseFloat($('#rgHarvestQty').val()) || 0;
    var unit = $('#rgHarvestUnit').val();
    $.post('<?= url('/items/' . $bedId . '/harvest-clear') ?>', {
      _token: csrf, line_number: harvestLine, qty: qty, unit: unit
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not save.'); });
  });
})();
</script>
