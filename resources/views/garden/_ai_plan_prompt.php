<?php
/**
 * AI Planning Prompt — floating "Get AI help" button for Plan & Timeline views.
 *
 * Expected variables (available in both plan_inline and plan_timeline):
 *   $bed, $catalog, $cropsById, $item, $parentGarden, $today
 *
 * Builds a copyable text prompt the user can paste into any AI (Claude, ChatGPT…).
 * No external API call is made — this is purely a client-side copy helper.
 */

// ── Build the prompt text in PHP ───────────────────────────────────────────

$bedName     = $item['name'] ?? 'Garden Bed';
$gardenName  = $parentGarden['name'] ?? '';
$lengthMdisp = isset($lengthM) ? rtrim(rtrim(number_format((float)$lengthM, 1, '.', ''), '0'), '.') : '?';
$widthMdisp  = isset($widthM)  ? rtrim(rtrim(number_format((float)$widthM,  1, '.', ''), '0'), '.') : '?';
$numLines    = count($bed['lines'] ?? []);
$todayFmt    = date('j F Y', strtotime($today));

$monthNames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// Helper: decode JSON array → comma list
$jsonList = function(?string $json): string {
    if (!$json) return '';
    $arr = json_decode($json, true);
    return is_array($arr) ? implode(', ', $arr) : '';
};

$monthList = function(?string $json) use ($monthNames): string {
    if (!$json) return '';
    $arr = json_decode($json, true);
    if (!is_array($arr)) return '';
    return implode(', ', array_filter(array_map(fn($m) => $monthNames[(int)$m] ?? '', $arr)));
};

// Partition catalog: in-stock vs needs buying
$inStock  = [];
$needsBuy = [];
foreach ($catalog as $c) {
    $qty = (float)($c['stock_qty'] ?? 0);
    if ($qty > 0) {
        $inStock[] = $c;
    } else {
        $needsBuy[] = $c;
    }
}

// Rotation history across all lines
$rotLines = [];
foreach ($bed['lines'] as $line) {
    foreach ($line['rotation_history'] ?? [] as $r) {
        $year   = (int)($r['year'] ?? 0);
        $season = $r['season'] ?? '';
        $cid    = (int)($r['cropId'] ?? 0);
        $cname  = $cropsById[$cid]['name'] ?? "seed #$cid";
        $rotLines[] = "  $year " . ucfirst($season) . ": $cname in Line {$line['lineNumber']}";
    }
}

ob_start();
?>
GARDEN PLANNING REQUEST
=======================
Bed: <?= $bedName ?><?= $gardenName ? " | Garden: $gardenName" : '' ?>

Dimensions: <?= $lengthMdisp ?>m × <?= $widthMdisp ?>m | Lines: <?= $numLines ?> | Today: <?= $todayFmt ?>

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SEED STOCK — seeds I already own (prioritise these; flag anything I'd need to buy)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
<?php if (empty($inStock)): ?>
No seeds currently in stock.
<?php else: ?>
<?php foreach ($inStock as $c):
    $nm  = $c['name'] . ($c['variety'] ? ' (' . $c['variety'] . ')' : '');
    $qty = rtrim(rtrim(number_format((float)$c['stock_qty'], 3, '.', ''), '0'), '.');
    $dth = (int)($c['days_to_maturity'] ?? 0);
    $pm  = $monthList($c['planting_months'] ?? null);
    $hm  = $monthList($c['harvest_months'] ?? null);
    $com = $jsonList($c['companions'] ?? null);
    $ant = $jsonList($c['antagonists'] ?? null);
?>• <?= $nm ?> — <?= $qty ?> <?= $c['stock_unit'] ?? 'seeds' ?>

<?php if ($dth): ?>  Days to maturity: <?= $dth ?>
<?php endif; ?>
<?php if ($pm): ?>  Plant in: <?= $pm ?>
<?php endif; ?>
<?php if ($hm): ?>  Harvest in: <?= $hm ?>
<?php endif; ?>
<?php if ($com): ?>  Good companions: <?= $com ?>
<?php endif; ?>
<?php if ($ant): ?>  Avoid near: <?= $ant ?>
<?php endif; ?>

<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($needsBuy)): ?>
--- Seeds I'd need to buy (lower priority) ---
<?php foreach ($needsBuy as $c):
    $nm = $c['name'] . ($c['variety'] ? ' (' . $c['variety'] . ')' : '');
    $dth = (int)($c['days_to_maturity'] ?? 0);
?>• <?= $nm ?> (OUT OF STOCK)<?= $dth ? " — $dth days to maturity" : '' ?>

<?php endforeach; ?>
<?php endif; ?>

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CURRENT BED PLANTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
<?php foreach ($bed['lines'] as $line):
    $fill     = \App\Support\GardenHelpers::computeFill($line, $cropsById);
    $pct      = (int)round($fill['pct'] * 100);
    $usedCm   = $fill['used'];
    $totalCm  = (int)($line['lengthCm'] ?? 0);
    $harvestD = \App\Support\GardenHelpers::lineHarvestDate($line, $cropsById);
    $sownD    = \App\Support\GardenHelpers::lineSownDate($line);
    $plantings = $line['plantings'] ?? [];
?>
Line <?= $line['lineNumber'] ?> (<?= $usedCm ?>/<?= $totalCm ?>cm · <?= $pct ?>% full):
<?php if (empty($plantings)): ?>
  Empty<?= !empty($line['succession']) ? ' — succession planned' : '' ?>

<?php else: ?>
<?php foreach ($plantings as $p):
        $cname    = $p['crop_name'] ?? ($cropsById[$p['cropId'] ?? 0]['name'] ?? 'Unknown');
        $cnt      = (int)($p['plants'] ?? 1);
        $sown     = $p['sown_at'] ?? $sownD;
        $hdate    = $harvestD;
        $sownFmt  = $sown  ? date('j M Y', strtotime($sown))  : null;
        $hFmt     = $hdate ? date('j M Y', strtotime($hdate)) : null;
?>  - <?= $cname ?> · <?= $cnt ?> plant<?= $cnt > 1 ? 's' : '' ?><?= $sownFmt ? " · Sown: $sownFmt" : '' ?><?= $hFmt ? " · Est. harvest: $hFmt" : '' ?>

<?php endforeach; ?>
<?php endif; ?>
<?php endforeach; ?>

<?php if (!empty($rotLines)): ?>
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ROTATION HISTORY (last crops grown in each line)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
<?= implode("\n", $rotLines) ?>


<?php endif; ?>
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
YOUR TASK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Based on all the information above, give me a concrete planting plan:

1. TOP 10 SEEDS TO PLANT NEXT
   - Use my in-stock seeds first. Flag anything I need to buy.
   - For each seed: which line(s) it fits best, WHY (companion synergy, rotation, space), planting window, days to harvest, one practical tip (depth / spacing / direct-sow vs. transplant).
   - If a line is about to harvest, suggest what replaces it immediately (succession planting).

2. COMPANION & ROTATION NOTES
   - Flag any current companion conflicts in the bed.
   - Warn about any rotation issues (same botanical family planted in the same line within 2 years).

3. QUICK SOIL & TIMING TIPS
   - Any bed-prep, fertilising, or timing notes worth knowing for this bed right now.

Format: bullet points, easy to scan. Group by seed/action. Keep it concise.
<?php
$promptText = ob_get_clean();
$promptJs   = json_encode(trim($promptText));
?>

<!-- ── Floating AI help button ──────────────────────────────────────────── -->
<button id="aiPlanBtn"
        aria-label="Get AI planting help"
        style="position:fixed;bottom:72px;right:14px;z-index:900;
               display:flex;align-items:center;gap:6px;
               background:var(--color-primary);color:#fff;border:none;
               border-radius:50px;padding:10px 16px;font-size:.82rem;font-weight:700;
               box-shadow:0 3px 12px rgba(0,0,0,.22);cursor:pointer;
               white-space:nowrap;transition:transform .15s,box-shadow .15s">
    ✨ AI Plan Help
</button>

<!-- ── AI prompt panel (slide-up sheet) ─────────────────────────────────── -->
<div id="aiPlanSheet"
     style="display:none;position:fixed;inset:0;z-index:1000;align-items:flex-end">
  <!-- backdrop -->
  <div id="aiPlanBackdrop"
       style="position:absolute;inset:0;background:rgba(0,0,0,.45)"></div>
  <!-- sheet -->
  <div style="position:relative;width:100%;max-height:85vh;
              background:var(--color-surface,#fff);border-radius:18px 18px 0 0;
              display:flex;flex-direction:column;overflow:hidden;
              box-shadow:0 -4px 24px rgba(0,0,0,.18)">
    <!-- header -->
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:14px 16px 10px;border-bottom:1px solid var(--color-border)">
      <div>
        <div style="font-weight:700;font-size:.95rem">🤖 AI Planting Advisor</div>
        <div style="font-size:.72rem;color:var(--color-text-muted);margin-top:1px">
          Copy prompt → paste into Claude, ChatGPT, or any AI
        </div>
      </div>
      <button id="aiPlanClose"
              style="background:none;border:none;font-size:1.3rem;cursor:pointer;
                     color:var(--color-text-muted);padding:4px 8px;line-height:1">✕</button>
    </div>
    <!-- instruction strip -->
    <div style="padding:10px 16px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;
                font-size:.78rem;color:#15803d;display:flex;align-items:center;gap:8px">
      <span style="font-size:1rem">💡</span>
      <span>This prompt includes your full seed stock + current bed. Paste it into any AI for a personalised planting plan.</span>
    </div>
    <!-- prompt textarea -->
    <div style="flex:1;overflow-y:auto;padding:12px 16px">
      <textarea id="aiPlanPromptText" readonly
                style="width:100%;height:320px;border:1px solid var(--color-border);
                       border-radius:8px;padding:10px;font-size:.75rem;font-family:var(--font-mono,monospace);
                       line-height:1.5;background:var(--color-bg);color:var(--color-text);
                       resize:none;box-sizing:border-box"></textarea>
    </div>
    <!-- footer actions -->
    <div style="padding:12px 16px;border-top:1px solid var(--color-border);
                display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <button id="aiPlanCopyBtn"
              style="flex:1;background:var(--color-primary);color:#fff;border:none;
                     border-radius:var(--radius,8px);padding:11px 20px;font-size:.88rem;
                     font-weight:700;cursor:pointer">
        📋 Copy prompt
      </button>
      <span id="aiPlanCopied"
            style="display:none;font-size:.8rem;font-weight:700;color:#15803d">
        ✅ Copied!
      </span>
    </div>
  </div>
</div>

<script>
(function () {
    var promptText = <?= $promptJs ?>;
    var btn        = document.getElementById('aiPlanBtn');
    var sheet      = document.getElementById('aiPlanSheet');
    var backdrop   = document.getElementById('aiPlanBackdrop');
    var closeBtn   = document.getElementById('aiPlanClose');
    var ta         = document.getElementById('aiPlanPromptText');
    var copyBtn    = document.getElementById('aiPlanCopyBtn');
    var copiedMsg  = document.getElementById('aiPlanCopied');

    function openSheet() {
        ta.value = promptText;
        sheet.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeSheet() {
        sheet.style.display = 'none';
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', openSheet);
    backdrop.addEventListener('click', closeSheet);
    closeBtn.addEventListener('click', closeSheet);

    copyBtn.addEventListener('click', function () {
        ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch(e) {}
        if (!ok && navigator.clipboard) {
            navigator.clipboard.writeText(promptText).then(function () {
                copiedMsg.style.display = 'inline';
                setTimeout(function () { copiedMsg.style.display = 'none'; }, 3000);
            });
            return;
        }
        if (ok) {
            copiedMsg.style.display = 'inline';
            setTimeout(function () { copiedMsg.style.display = 'none'; }, 3000);
        }
    });

    // Hover lift on floating button
    btn.addEventListener('mouseenter', function () {
        btn.style.transform = 'translateY(-2px)';
        btn.style.boxShadow = '0 5px 18px rgba(0,0,0,.28)';
    });
    btn.addEventListener('mouseleave', function () {
        btn.style.transform = '';
        btn.style.boxShadow = '0 3px 12px rgba(0,0,0,.22)';
    });
}());
</script>
