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

// Build 16-week timeline starting 2 weeks before today, rounded to Monday
$todayObj = new DateTime($today);
$start = clone $todayObj;
$start->modify('-14 days');
$dow = (int)$start->format('w'); // 0=Sun
$start->modify('-' . (($dow + 6) % 7) . ' days'); // Monday
$startIso = $start->format('Y-m-d');
$totalDays = 16 * 7;
$todayPct = max(0, min(100, GardenHelpers::daysBetween($startIso, $today) / $totalDays * 100));

$weeks = [];
for ($i = 0; $i < 16; $i++) {
  $w = GardenHelpers::addDays($startIso, $i * 7);
  $d = new DateTime($w);
  $weeks[] = [
    'iso'    => $w,
    'day'    => (int)$d->format('j'),
    'month'  => $d->format('M'),
    'isMonth' => (int)$d->format('j') <= 7,
  ];
}
function pct(string $start, ?string $iso, int $totalDays): float {
  if (!$iso) return 0;
  $d = \App\Support\GardenHelpers::daysBetween($start, $iso);
  return max(0, min(100, $d / $totalDays * 100));
}

// Aggregate rotation history across lines
$allHistory = [];
foreach ($bed['lines'] as $line) {
  foreach ($line['rotation_history'] ?? [] as $r) {
    $allHistory[] = array_merge($r, ['lineNumber' => $line['lineNumber']]);
  }
}
$byYear = [];
foreach ($allHistory as $h) {
  $y = (int)($h['year'] ?? 0);
  if (!isset($byYear[$y])) $byYear[$y] = [];
  $byYear[$y][] = $h;
}
krsort($byYear);
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
    <a href="<?= url('/items/' . $bedId . '/planting/inline') ?>" class="rg-mode-tab">⏭ Plan</a>
    <a href="<?= url('/items/' . $bedId . '/planting/timeline') ?>" class="rg-mode-tab is-active">📅 Timeline</a>
  </div>

  <?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

  <!-- Mobile fallback -->
  <div class="rg-timeline-mobile-fallback">
    📅 Timeline view is desktop-only.
    <br><br>
    <a href="<?= url('/items/' . $bedId . '/planting/inline') ?>" class="btn btn-primary btn-sm">Open inline plan instead</a>
  </div>

  <!-- Timeline -->
  <div class="rg-timeline">
    <div class="rg-label-tiny" style="margin-bottom:8px;display:flex;align-items:center;gap:6px">
      📅 4-month outlook
      <span class="rg-mono" style="color:var(--color-text-muted);text-transform:none;letter-spacing:0;font-size:.66rem">· solid = growing · diagonal = planned succession</span>
    </div>

    <div class="rg-timeline-head">
      <div class="rg-timeline-head-spacer"></div>
      <div class="rg-timeline-grid-head">
        <?php foreach ($weeks as $w): ?>
          <div class="rg-timeline-week <?= $w['isMonth'] ? 'is-month' : '' ?>">
            <?= $w['isMonth'] ? e($w['month']) : '' ?>
            <span class="rg-timeline-week-day"><?= $w['day'] ?></span>
          </div>
        <?php endforeach; ?>
        <div class="rg-timeline-today" style="left: <?= $todayPct ?>%">
          <span class="rg-timeline-today-head" style="left:0">TODAY</span>
        </div>
      </div>
      <div class="rg-timeline-head-spacer-r"></div>
    </div>

    <?php foreach ($bed['lines'] as $idx => $line):
      $empty = empty($line['plantings']);
      $harvestDate = GardenHelpers::lineHarvestDate($line, $cropsById);
      $succession = $line['succession'];
      $succCrop = $succession ? ($cropsById[$succession['cropId']] ?? null) : null;
      $succEnd = ($succession && $succCrop) ? GardenHelpers::addDays($succession['startsOn'], (int)$succCrop['days_to_maturity']) : null;
      $succWarn = ($succession && $succCrop) ? GardenHelpers::rotationWarning($line, $succCrop, $cropsById, $today) : null;
    ?>
      <div class="rg-timeline-row">
        <div class="rg-timeline-row-label">
          <div class="rg-timeline-row-name">
            <span class="rg-line-num" style="width:18px;height:18px;font-size:.6rem"><?= (int)$line['lineNumber'] ?></span>
            Line <?= (int)$line['lineNumber'] ?>
          </div>
          <?php if (!$empty): ?>
          <button type="button" class="btn btn-ghost btn-sm rg-harvest-btn" data-line="<?= (int)$line['lineNumber'] ?>" style="padding:2px 4px;font-size:.66rem;color:var(--color-accent);margin-top:2px">🌾 Harvest</button>
          <?php endif; ?>
        </div>

        <div class="rg-timeline-track">
          <div class="rg-timeline-grid-bg">
            <?php for ($i = 0; $i < 16; $i++) echo '<div></div>'; ?>
          </div>
          <div class="rg-timeline-today" style="left: <?= $todayPct ?>%"></div>

          <?php foreach ($line['plantings'] as $i => $p):
            $c = $cropsById[$p['cropId']] ?? null;
            if (!$c) continue;
            $sown = $p['sown_at'] ?? $line['sown_at'] ?? $today;
            $end  = GardenHelpers::addDays($sown, (int)$c['days_to_maturity']);
            $left = pct($startIso, $sown, $totalDays);
            $right = pct($startIso, $end, $totalDays);
            if ($right - $left < 1) $right = $left + 1;
            $m = GardenHelpers::maturity($p, $line, $c, $today);
            $color = $c['color'];
          ?>
            <div class="rg-timeline-bar"
                 style="left:<?= $left ?>%;width:<?= $right - $left ?>%;top: <?= 4 + $i * 22 ?>px;background:linear-gradient(90deg, <?= e($color) ?> <?= $m * 100 ?>%, <?= e($color) ?>55 <?= $m * 100 ?>%);border-color:<?= e($color) ?>aa"
                 title="<?= e($c['name']) ?> · <?= e(GardenHelpers::fmtDate($sown)) ?> → <?= e(GardenHelpers::fmtDate($end)) ?>">
              <span><?= e($c['emoji']) ?></span>
              <span><?= e($c['name']) ?></span>
              <span class="rg-timeline-bar-pct"><?= round($m * 100) ?>%</span>
            </div>
          <?php endforeach; ?>

          <?php if ($succession && $succCrop):
            $left = pct($startIso, $succession['startsOn'], $totalDays);
            $right = pct($startIso, $succEnd, $totalDays);
            if ($right - $left < 1) $right = $left + 1;
            $color = $succCrop['color'];
            $stackTop = $empty ? 4 : (4 + count($line['plantings']) * 22);
          ?>
            <div class="rg-timeline-bar rg-timeline-succ"
                 style="left:<?= $left ?>%;width:<?= $right - $left ?>%;top:<?= $stackTop ?>px;background: repeating-linear-gradient(45deg, <?= e($color) ?>55 0 6px, <?= e($color) ?>33 6px 12px);border-color:<?= e($color) ?>;color:<?= e($color) ?>;text-shadow:none">
              <span><?= e($succCrop['emoji']) ?></span>
              <span><?= e($succCrop['name']) ?></span>
              <?php if ($succWarn): ?><span title="<?= e($succWarn) ?>" style="margin-left:auto;color:#b45309">⚠</span><?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($empty && !$succession): ?>
            <button type="button" class="rg-pick-open" data-line="<?= (int)$line['lineNumber'] ?>" style="position:absolute;inset:4px 6px;border:1px dashed var(--color-border-strong);border-radius:4px;background:transparent;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:6px;font-size:.74rem;color:var(--color-text-muted);font-weight:600">
              ＋ Plan a crop
            </button>
          <?php endif; ?>
        </div>

        <div class="rg-timeline-row-actions">
          <?php if ($succession): ?>
            <button type="button" class="btn btn-ghost btn-sm rg-pick-open" data-line="<?= (int)$line['lineNumber'] ?>">Edit ⏭</button>
            <button type="button" class="btn btn-ghost btn-sm rg-succ-clear" data-line="<?= (int)$line['lineNumber'] ?>" style="color:var(--color-text-muted)">Remove</button>
          <?php elseif (!$empty): ?>
            <button type="button" class="btn btn-secondary btn-sm rg-pick-open" data-line="<?= (int)$line['lineNumber'] ?>">＋ Next</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Rotation memory -->
  <?php if (!empty($byYear)): ?>
  <div class="rg-rotmem">
    <div class="rg-label-tiny" style="margin-bottom:6px;display:flex;align-items:center;gap:6px">
      🧠 Rotation memory
      <span class="rg-mono" style="color:var(--color-text-muted);text-transform:none;letter-spacing:0;font-size:.66rem">· avoid same family two seasons running</span>
    </div>
    <?php foreach ($byYear as $year => $entries): ?>
      <div class="rg-rotmem-year"><?= (int)$year ?></div>
      <div class="rg-rotmem-row">
        <?php foreach ($entries as $r):
          $rc = $cropsById[$r['cropId'] ?? 0] ?? null;
          if (!$rc) continue;
          $color = $rc['color'];
        ?>
          <span class="rg-rot-pill" style="background:<?= e($color) ?>14;border-color:<?= e($color) ?>40">
            <span><?= e($rc['emoji']) ?></span>
            <span><?= e($rc['name']) ?></span>
            <span class="rg-mono" style="color:var(--color-text-muted);font-size:.62rem">L<?= (int)($r['lineNumber'] ?? 0) ?> · <?= e($r['season'] ?? '') ?></span>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Picker modal (centered) -->
  <div class="rg-blackout" id="rgPickerModal" style="display:none">
    <div class="rg-blackout-card" style="max-width:480px">
      <div class="rg-blackout-head">
        <div style="flex:1">
          <div class="rg-label-tiny">Plan succession</div>
          <div style="font-weight:800;font-size:1rem;margin-top:2px"><?= e($bed['name']) ?> · Line <span id="rgPickerLine">—</span></div>
        </div>
        <button type="button" class="rg-blackout-close" id="rgPickerClose">×</button>
      </div>
      <div class="rg-blackout-body">
        <div class="rg-picker-grid" id="rgPickerGrid"></div>
        <button type="button" class="btn btn-ghost" id="rgPickerCancel" style="width:100%;margin-top:12px">Cancel</button>
      </div>
    </div>
  </div>

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
  var catalog = <?= json_encode(array_values($catalog), JSON_UNESCAPED_UNICODE) ?>;
  var lines = <?= json_encode(array_values(array_map(function ($l) use ($cropsById, $today) {
    return [
      'lineNumber' => $l['lineNumber'],
      'rotation_history' => $l['rotation_history'],
      'startsOn' => GardenHelpers::nextSuccessionStart($l, $cropsById, $today),
    ];
  }, $bed['lines'])), JSON_UNESCAPED_UNICODE) ?>;

  function buildWarn(line, crop) {
    if (!line.rotation_history || !line.rotation_history.length) return null;
    var recent = line.rotation_history.filter(function (r) { return (r.year || 0) >= (new Date().getFullYear() - 2); });
    for (var i = 0; i < recent.length; i++) {
      var hc = catalog.find(function (c) { return c.id === parseInt(recent[i].cropId, 10); });
      if (hc && hc.family === crop.family) {
        return 'Last grown here: ' + hc.name + ' (' + recent[i].year + ') — same family. Rotate to rest soil.';
      }
    }
    return null;
  }

  // ---- picker modal ----
  $page.on('click', '.rg-pick-open', function () {
    var lineNum = parseInt($(this).data('line'), 10);
    var line = lines.find(function (l) { return l.lineNumber === lineNum; });
    if (!line) return;
    $('#rgPickerLine').text(lineNum);
    var $grid = $('#rgPickerGrid').empty();
    catalog.forEach(function (c) {
      var warn = buildWarn(line, c);
      var $tile = $('<button type="button" class="rg-picker-tile' + (warn ? ' is-warn' : '') + '"></button>')
        .attr('data-line', lineNum).attr('data-crop-id', c.id).attr('data-starts-on', line.startsOn)
        .attr('title', warn || '');
      $tile.append('<span class="rg-picker-tile-emoji">' + (c.emoji || '🌱') + '</span>');
      $tile.append('<div style="flex:1;min-width:0"><div class="rg-picker-tile-name">' + c.name + '</div><div class="rg-picker-tile-meta">' + (c.days_to_maturity || 60) + 'd · ' + (c.spacing_cm || 5) + 'cm</div></div>');
      if (warn) $tile.append('<span style="color:#b45309">⚠</span>');
      $grid.append($tile);
    });
    $('#rgPickerModal').css('display', 'flex');
  });
  $('#rgPickerClose, #rgPickerCancel').on('click', function () { $('#rgPickerModal').hide(); });

  $('#rgPickerGrid').on('click', '.rg-picker-tile', function () {
    var lineNum = parseInt($(this).data('line'), 10);
    var cropId = parseInt($(this).data('crop-id'), 10);
    var startsOn = $(this).data('starts-on') || '';
    $.post('<?= url('/items/' . $bedId . '/lines/succession/set') ?>', {
      _token: csrf, line_number: lineNum, crop_id: cropId, starts_on: startsOn
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not save.'); });
  });

  // ---- clear succession ----
  $page.on('click', '.rg-succ-clear', function () {
    var line = parseInt($(this).data('line'), 10);
    $.post('<?= url('/items/' . $bedId . '/lines/succession/clear') ?>', { _token: csrf, line_number: line })
      .done(function () { window.location.reload(); }).fail(function () { alert('Could not clear.'); });
  });

  // ---- harvest ----
  var harvestLine = null;
  $page.on('click', '.rg-harvest-btn', function () {
    harvestLine = parseInt($(this).data('line'), 10);
    $('#rgHarvestLine').text(harvestLine);
    $('#rgHarvestQty').val('');
    $('#rgHarvestModal').css('display', 'flex');
  });
  $('#rgHarvestClose, #rgHarvestCancel').on('click', function () { $('#rgHarvestModal').hide(); harvestLine = null; });
  $('#rgHarvestConfirm').on('click', function () {
    if (harvestLine === null) return;
    $.post('<?= url('/items/' . $bedId . '/harvest-clear') ?>', {
      _token: csrf, line_number: harvestLine, qty: parseFloat($('#rgHarvestQty').val()) || 0, unit: $('#rgHarvestUnit').val()
    }).done(function () { window.location.reload(); }).fail(function () { alert('Could not save.'); });
  });
})();
</script>
