<?php
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$currentMonthName = $monthNames[$currentMonth - 1];
$typeEmoji = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'🌾'];
$statusLabel = ['planned'=>'Planned','sown'=>'Sown','growing'=>'Growing','harvested'=>'Harvested'];
$statusColor = ['planned'=>'#94a3b8','sown'=>'#f59e0b','growing'=>'#22c55e','harvested'=>'#3b82f6'];
?>
<style>
.garden-hub { max-width: 900px; margin: 0 auto; }
.garden-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:var(--spacing-4); }
.garden-header h1 { font-size:1.6rem; font-weight:800; margin:0; }
.garden-actions { display:flex; gap:8px; flex-wrap:wrap; }

.garden-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; margin-bottom:var(--spacing-5); }
.garden-stat { background:var(--color-surface-raised); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:14px 16px; text-align:center; }
.garden-stat-num { font-size:1.8rem; font-weight:800; color:var(--color-primary); line-height:1; }
.garden-stat-label { font-size:.75rem; color:var(--color-text-muted); margin-top:4px; }

.garden-section { margin-bottom:var(--spacing-6); }
.garden-section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--spacing-3); }
.garden-section-title { font-size:1rem; font-weight:700; display:flex; align-items:center; gap:6px; }
.garden-section-link { font-size:.8rem; color:var(--color-primary); text-decoration:none; }
.garden-section-link:hover { text-decoration:underline; }

.garden-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; }
.garden-card { background:var(--color-surface-raised); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:14px; }
.garden-card-name { font-weight:700; font-size:.93rem; margin-bottom:2px; }
.garden-card-sub { font-size:.78rem; color:var(--color-text-muted); }
.garden-card-badge { display:inline-block; font-size:.7rem; padding:2px 8px; border-radius:999px; margin-top:6px; font-weight:600; }
.garden-card-badge--green  { background:rgba(34,197,94,.15); color:#15803d; }
.garden-card-badge--orange { background:rgba(234,179,8,.15); color:#92400e; }
.garden-card-badge--red    { background:rgba(239,68,68,.15); color:#b91c1c; }
.garden-card-badge--blue   { background:rgba(59,130,246,.15); color:#1d4ed8; }
.garden-card-badge--gray   { background:rgba(100,116,139,.15); color:#475569; }

.garden-empty { text-align:center; padding:var(--spacing-5); color:var(--color-text-muted); font-size:.88rem; background:var(--color-surface-raised); border:1px dashed var(--color-border); border-radius:var(--radius-lg); }

.garden-needs-list { display:flex; flex-direction:column; gap:10px; }
.garden-need-row { background:var(--color-surface-raised); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:12px 14px; display:flex; align-items:center; gap:12px; }
.garden-need-name { font-weight:700; font-size:.9rem; flex:1; }
.garden-need-qty { font-size:.8rem; color:var(--color-text-muted); white-space:nowrap; }
.garden-need-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.garden-need-stock { font-size:.78rem; margin-top:2px; }
.garden-need-stock--ok  { color:#15803d; }
.garden-need-stock--low { color:#dc2626; }
.garden-need-stock--na  { color:#94a3b8; }

.garden-bedrows-list { display:flex; flex-direction:column; gap:8px; }
.garden-bedrow { background:var(--color-surface-raised); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:11px 14px; display:flex; align-items:center; gap:10px; }
.garden-bedrow-status { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.garden-bedrow-info { flex:1; min-width:0; }
.garden-bedrow-name { font-weight:600; font-size:.88rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.garden-bedrow-sub  { font-size:.75rem; color:var(--color-text-muted); }
.garden-bedrow-date { font-size:.75rem; color:var(--color-text-muted); white-space:nowrap; }

.garden-activity-list { display:flex; flex-direction:column; gap:6px; }
.garden-activity-item { display:flex; align-items:flex-start; gap:10px; padding:8px 12px; background:var(--color-surface-raised); border-radius:var(--radius); border:1px solid var(--color-border); }
.garden-activity-dot { width:7px; height:7px; border-radius:50%; background:var(--color-primary); flex-shrink:0; margin-top:5px; }
.garden-activity-text { flex:1; font-size:.83rem; }
.garden-activity-time { font-size:.75rem; color:var(--color-text-muted); white-space:nowrap; }

.garden-hint { background:linear-gradient(135deg,rgba(41,64,43,.07),rgba(41,64,43,.03)); border:1px solid rgba(41,64,43,.18); border-radius:var(--radius-lg); padding:16px 20px; margin-bottom:var(--spacing-5); }
.garden-hint-title { font-weight:700; font-size:.9rem; margin-bottom:6px; }
.garden-hint-body { font-size:.83rem; color:var(--color-text-muted); line-height:1.6; }
</style>

<div class="garden-hub">

<div class="garden-header">
    <h1>🌿 Garden</h1>
    <div class="garden-actions">
        <a href="<?= url('/seeds/create') ?>" class="btn btn-primary btn-sm">+ Add Seed</a>
        <a href="<?= url('/seeds') ?>" class="btn btn-secondary btn-sm">📖 Catalog</a>
        <a href="<?= url('/seeds/family-needs') ?>" class="btn btn-secondary btn-sm">👨‍👩‍👧 Family Needs</a>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Stats at a glance -->
<div class="garden-stats">
    <div class="garden-stat">
        <div class="garden-stat-num"><?= $totalSeeds ?></div>
        <div class="garden-stat-label">Seeds Cataloged</div>
    </div>
    <div class="garden-stat">
        <div class="garden-stat-num"><?= count($plantNow) ?></div>
        <div class="garden-stat-label">Plant in <?= $currentMonthName ?></div>
    </div>
    <div class="garden-stat">
        <div class="garden-stat-num"><?= count($harvestSoon) ?></div>
        <div class="garden-stat-label">Harvest Soon</div>
    </div>
    <div class="garden-stat">
        <div class="garden-stat-num"><?= count($activeBedRows) ?></div>
        <div class="garden-stat-label">Bed Rows Active</div>
    </div>
    <div class="garden-stat">
        <div class="garden-stat-num"><?= count($familyNeeds) ?></div>
        <div class="garden-stat-label">Family Needs</div>
    </div>
    <?php if (count($lowStock) > 0): ?>
    <div class="garden-stat" style="border-color:rgba(220,38,38,.4);background:rgba(220,38,38,.05)">
        <div class="garden-stat-num" style="color:#dc2626"><?= count($lowStock) ?></div>
        <div class="garden-stat-label">Low Stock ⚠️</div>
    </div>
    <?php endif; ?>
</div>

<!-- Biodynamic overview widget -->
<?php if (!empty($bioNow)): ?>
<div style="background:linear-gradient(135deg,#0f2d18,#1a3a1c);border-radius:var(--radius-lg);padding:var(--spacing-4) var(--spacing-5);margin-bottom:var(--spacing-5);color:#fff">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3)">
        <div style="font-weight:700;font-size:1rem">🌙 Lunar Calendar</div>
        <a href="<?= url('/garden/biodynamic') ?>" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:20px;padding:4px 14px;font-size:.72rem;font-weight:700;color:#fff;text-decoration:none">Full Calendar →</a>
    </div>
    <!-- Today highlight -->
    <?php
    $_gBg    = \App\Support\BiodynamicCalendar::ORGAN_BG[$bioNow['organ']] ?? '#f0fdf4';
    $_gColor = \App\Support\BiodynamicCalendar::ORGAN_COLOR[$bioNow['organ']] ?? '#15803d';
    $_gEmoji = \App\Support\BiodynamicCalendar::ORGAN_EMOJI[$bioNow['organ']] ?? '🌿';
    $_gCrops = ['Root'=>'Carrots · Beets · Garlic · Onions · Potatoes','Leaf'=>'Lettuce · Spinach · Kale · Herbs','Flower'=>'Roses · Lavender · Broccoli · Cauliflower','Fruit'=>'Tomatoes · Peppers · Olives · Grapes'];
    $_gAdvice = $bioNow['is_descending'] ? 'Plant &amp; sow' : 'Harvest';
    ?>
    <div style="background:rgba(255,255,255,0.1);border-radius:var(--radius);padding:10px 14px;margin-bottom:var(--spacing-3);display:flex;align-items:center;gap:12px">
        <span style="font-size:2rem;flex-shrink:0"><?= $_gEmoji ?></span>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:.9rem"><?= $_gAdvice ?> <?= $bioNow['organ'] ?> crops today</div>
            <div style="font-size:.72rem;opacity:.65;margin-top:2px"><?= $_gCrops[$bioNow['organ']] ?? '' ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0;font-size:.7rem;opacity:.6">
            <div><?= $bioNow['name'] ?></div>
            <div><?= $bioNow['is_descending'] ? '↓ Descending' : '↑ Ascending' ?></div>
        </div>
    </div>
    <!-- 7-day mini strip -->
    <div style="display:flex;gap:5px;overflow-x:auto;scrollbar-width:none">
        <?php foreach ($bioWeek as $i => $bw):
            $_wEmoji = \App\Support\BiodynamicCalendar::ORGAN_EMOJI[$bw['organ']] ?? '🌿';
            $_wLabel = $i === 0 ? 'Today' : date('D', mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
            $_wNum   = date('j', mktime(0,0,0,(int)date('n'),(int)date('j')+$i,(int)date('Y')));
        ?>
        <div style="flex-shrink:0;background:rgba(255,255,255,<?= $i===0?'0.18':'0.08' ?>);border-radius:var(--radius);padding:6px 8px;text-align:center;min-width:44px;<?= $bw['is_anomaly']?'opacity:.45':'' ?>">
            <div style="font-size:.55rem;opacity:.6;text-transform:uppercase;font-weight:600"><?= $_wLabel ?></div>
            <div style="font-size:.75rem;font-weight:700;margin:1px 0"><?= $_wNum ?></div>
            <div style="font-size:1rem"><?= $_wEmoji ?></div>
            <div style="font-size:.5rem;opacity:.6;margin-top:1px"><?= $bw['organ'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Assistant hint -->
<?php if (count($plantNow) > 0 || count($harvestSoon) > 0): ?>
<div class="garden-hint">
    <div class="garden-hint-title">🌿 What to do in <?= $currentMonthName ?></div>
    <div class="garden-hint-body">
        <?php if (count($plantNow) > 0): ?>
        <strong>Plant now:</strong> <?= implode(', ', array_map(fn($s) => e($s['name']), array_slice($plantNow, 0, 5))) ?><?= count($plantNow) > 5 ? ' and ' . (count($plantNow)-5) . ' more' : '' ?>.<br>
        <?php endif; ?>
        <?php if (count($harvestSoon) > 0): ?>
        <strong>Harvest window:</strong> <?= implode(', ', array_map(fn($s) => e($s['name']), array_slice($harvestSoon, 0, 5))) ?><?= count($harvestSoon) > 5 ? ' and ' . (count($harvestSoon)-5) . ' more' : '' ?>.
        <?php endif; ?>
        <?php if (count($lowStock) > 0): ?>
        <br><strong style="color:#dc2626">Restock needed:</strong> <?= implode(', ', array_map(fn($s) => e($s['name']), $lowStock)) ?>.
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Climate Suggestions -->
<?php if (!empty($climateSuggestions)): ?>
<section class="garden-section">
    <div class="garden-section-head">
        <div class="garden-section-title">🌍 What to Plant This Month
            <span style="font-weight:400;font-size:.75rem;color:var(--color-text-muted)"> · <?= e(preg_replace('/_/', ' ', ucwords($climateZone, '_'))) ?></span>
        </div>
        <a href="<?= url('/settings') ?>" class="garden-section-link">Change zone →</a>
    </div>
    <div class="garden-cards">
        <?php
        $typeEmoji2 = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'📋'];
        foreach ($climateSuggestions as $sug):
            // Check if user already has this seed
            $hasSeed = false;
            foreach ($allSeeds ?? [] as $s) {
                if (stripos($s['name'], $sug['name']) !== false || stripos($sug['name'], $s['name']) !== false) {
                    $hasSeed = true; break;
                }
            }
        ?>
        <div class="garden-card" style="border-left:3px solid <?= $hasSeed ? 'var(--color-primary)' : 'var(--color-border)' ?>">
            <div class="garden-card-name"><?= ($typeEmoji2[$sug['type']] ?? '🌱') . ' ' . e($sug['name']) ?></div>
            <div class="garden-card-sub"><?= e($sug['tip']) ?></div>
            <?php if ($hasSeed): ?>
            <span class="garden-card-badge garden-card-badge--green">✓ In catalog</span>
            <?php else: ?>
            <a href="<?= url('/seeds/create?name='.urlencode($sug['name'])) ?>" class="garden-card-badge garden-card-badge--blue" style="text-decoration:none;display:inline-block">+ Add seed</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Plant This Month -->
<section class="garden-section">
    <div class="garden-section-head">
        <div class="garden-section-title">🌱 Plant in <?= $currentMonthName ?></div>
        <a href="<?= url('/seeds') ?>" class="garden-section-link">All seeds →</a>
    </div>
    <?php if (empty($plantNow)): ?>
    <div class="garden-empty">No seeds scheduled for planting this month. Check your <a href="<?= url('/seeds') ?>">seed catalog</a> to set planting months.</div>
    <?php else: ?>
    <div class="garden-cards">
        <?php foreach ($plantNow as $s):
            $low = $s['stock_enabled'] && $s['stock_low_threshold'] !== null && (float)$s['stock_qty'] <= (float)$s['stock_low_threshold'];
        ?>
        <a href="<?= url('/seeds/' . $s['id']) ?>" style="text-decoration:none;color:inherit">
        <div class="garden-card" style="border-left:3px solid var(--color-primary)">
            <div class="garden-card-name"><?= ($typeEmoji[$s['type']] ?? '🌾') . ' ' . e($s['name']) ?></div>
            <?php if ($s['variety']): ?><div class="garden-card-sub"><?= e($s['variety']) ?></div><?php endif; ?>
            <?php if ($s['sowing_type']): ?>
            <span class="garden-card-badge garden-card-badge--blue"><?= ucfirst($s['sowing_type']) ?></span>
            <?php endif; ?>
            <?php if ($s['days_to_maturity']): ?>
            <span class="garden-card-badge garden-card-badge--gray"><?= $s['days_to_maturity'] ?>d to harvest</span>
            <?php endif; ?>
            <?php if ($s['stock_enabled']): ?>
            <div style="margin-top:6px;font-size:.78rem;color:<?= $low ? '#dc2626' : '#15803d' ?>">
                Stock: <?= number_format((float)$s['stock_qty'],1) ?> <?= e($s['stock_unit']) ?><?= $low ? ' ⚠️' : '' ?>
            </div>
            <?php endif; ?>
        </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Harvest Window -->
<section class="garden-section">
    <?php
    $harvestWindowLabel = $monthNames[$harvestMonths[0]-1] . '–' . $monthNames[$harvestMonths[2]-1];
    ?>
    <div class="garden-section-head">
        <div class="garden-section-title">🌾 Harvest Window <span style="font-weight:400;color:var(--color-text-muted);font-size:.85rem">(<?= $harvestWindowLabel ?>)</span></div>
        <a href="<?= url('/harvest/quick') ?>" class="garden-section-link">Log harvest →</a>
    </div>
    <?php if (empty($harvestSoon)): ?>
    <div class="garden-empty">No harvests coming up in the next 3 months.</div>
    <?php else: ?>
    <div class="garden-cards">
        <?php foreach ($harvestSoon as $s):
            $seedMonths = $s['harvest_months'] ? json_decode($s['harvest_months'], true) : [];
            $thisMonthHarvest = in_array($currentMonth, $seedMonths ?? []);
        ?>
        <a href="<?= url('/seeds/' . $s['id']) ?>" style="text-decoration:none;color:inherit">
        <div class="garden-card" style="border-left:3px solid <?= $thisMonthHarvest ? '#22c55e' : '#f59e0b' ?>">
            <div class="garden-card-name"><?= ($typeEmoji[$s['type']] ?? '🌾') . ' ' . e($s['name']) ?></div>
            <?php if ($s['variety']): ?><div class="garden-card-sub"><?= e($s['variety']) ?></div><?php endif; ?>
            <span class="garden-card-badge <?= $thisMonthHarvest ? 'garden-card-badge--green' : 'garden-card-badge--orange' ?>">
                <?= $thisMonthHarvest ? 'Harvest now' : 'Coming soon' ?>
            </span>
            <?php if ($s['yield_per_plant_kg']): ?>
            <div style="margin-top:6px;font-size:.78rem;color:var(--color-text-muted)">~<?= $s['yield_per_plant_kg'] ?> kg/plant</div>
            <?php endif; ?>
        </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Active Bed Rows -->
<?php if (!empty($activeBedRows)): ?>
<section class="garden-section">
    <div class="garden-section-head">
        <div class="garden-section-title">🛏 Active Beds (<?= $currentYear ?>)</div>
    </div>
    <div class="garden-bedrows-list">
        <?php foreach ($activeBedRows as $br):
            $sc = $statusColor[$br['status']] ?? '#94a3b8';
            $sl = $statusLabel[$br['status']] ?? $br['status'];
        ?>
        <div class="garden-bedrow">
            <div class="garden-bedrow-status" style="background:<?= $sc ?>"></div>
            <div class="garden-bedrow-info">
                <div class="garden-bedrow-name">
                    <?= e($br['seed_name'] ?? 'Unknown seed') ?>
                    <span style="font-weight:400;color:var(--color-text-muted)"> in <?= e($br['bed_name'] ?? 'Bed') ?>, Row <?= $br['row_number'] ?></span>
                </div>
                <div class="garden-bedrow-sub">
                    <span style="color:<?= $sc ?>;font-weight:600"><?= $sl ?></span>
                    <?php if ($br['plant_count']): ?> · <?= $br['plant_count'] ?> plants<?php endif; ?>
                    <?php if ($br['spacing_used_cm']): ?> · <?= $br['spacing_used_cm'] ?>cm spacing<?php endif; ?>
                </div>
            </div>
            <?php if ($br['sowing_date']): ?>
            <div class="garden-bedrow-date">Sown <?= date('d M', strtotime($br['sowing_date'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Family Needs -->
<section class="garden-section">
    <div class="garden-section-head">
        <div class="garden-section-title">👨‍👩‍👧 Family Needs</div>
        <a href="<?= url('/seeds/family-needs') ?>" class="garden-section-link">Manage →</a>
    </div>
    <?php if (empty($familyNeeds)): ?>
    <div class="garden-empty">No family needs tracked yet. <a href="<?= url('/seeds/family-needs') ?>">Add your yearly vegetable goals</a>.</div>
    <?php else: ?>
    <div class="garden-needs-list">
        <?php foreach (array_slice($familyNeeds, 0, 10) as $fn):
            $hasSeed    = !empty($fn['seed_id']);
            $hasStock   = $hasSeed && $fn['stock_qty'] !== null;
            $stockOk    = false;
            if ($hasStock && $fn['yearly_qty'] > 0) {
                $stockOk = (float)$fn['stock_qty'] > 0;
            }
            $dotColor = $hasSeed ? ($stockOk ? '#22c55e' : '#f59e0b') : '#94a3b8';
        ?>
        <div class="garden-need-row">
            <div class="garden-need-dot" style="background:<?= $dotColor ?>"></div>
            <div style="flex:1;min-width:0">
                <div class="garden-need-name"><?= e($fn['vegetable_name']) ?></div>
                <?php if ($hasSeed): ?>
                <div class="garden-need-stock <?= $stockOk ? 'garden-need-stock--ok' : 'garden-need-stock--low' ?>">
                    Seed: <?= e($fn['seed_name']) ?> · <?= number_format((float)$fn['stock_qty'],1) ?> <?= e($fn['stock_unit']) ?> in stock
                </div>
                <?php else: ?>
                <div class="garden-need-stock garden-need-stock--na">No seed linked</div>
                <?php endif; ?>
            </div>
            <?php if ($fn['yearly_qty']): ?>
            <div class="garden-need-qty">Goal: <?= number_format((float)$fn['yearly_qty'],1) ?> <?= e($fn['yearly_unit']) ?>/yr</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (count($familyNeeds) > 10): ?>
        <div style="text-align:center;padding:var(--spacing-2)">
            <a href="<?= url('/seeds/family-needs') ?>" class="btn btn-ghost btn-sm">View all <?= count($familyNeeds) ?> needs →</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Low Stock -->
<?php if (!empty($lowStock)): ?>
<section class="garden-section">
    <div class="garden-section-head">
        <div class="garden-section-title" style="color:#dc2626">⚠️ Low Stock</div>
        <a href="<?= url('/seeds') ?>" class="garden-section-link">Manage stock →</a>
    </div>
    <div class="garden-cards">
        <?php foreach ($lowStock as $s): ?>
        <a href="<?= url('/seeds/' . $s['id']) ?>" style="text-decoration:none;color:inherit">
        <div class="garden-card" style="border-left:3px solid #dc2626;background:rgba(220,38,38,.04)">
            <div class="garden-card-name"><?= ($typeEmoji[$s['type']] ?? '🌾') . ' ' . e($s['name']) ?></div>
            <div class="garden-card-sub">
                <?= number_format((float)$s['stock_qty'],1) ?> / <?= number_format((float)$s['stock_low_threshold'],1) ?> <?= e($s['stock_unit']) ?>
            </div>
            <span class="garden-card-badge garden-card-badge--red">Restock</span>
        </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Recent Garden Activity -->
<?php if (!empty($recentActivity)): ?>
<section class="garden-section">
    <div class="garden-section-head">
        <div class="garden-section-title">📋 Recent Garden Activity</div>
        <a href="<?= url('/activity-log') ?>" class="garden-section-link">All activity →</a>
    </div>
    <div class="garden-activity-list">
        <?php foreach ($recentActivity as $a):
            $ago = time() - strtotime($a['performed_at']);
            if ($ago < 3600)      $agoStr = round($ago/60) . 'm ago';
            elseif ($ago < 86400) $agoStr = round($ago/3600) . 'h ago';
            else                  $agoStr = date('d M', strtotime($a['performed_at']));
        ?>
        <div class="garden-activity-item">
            <div class="garden-activity-dot"></div>
            <div class="garden-activity-text">
                <strong><?= e($a['item_name']) ?></strong>
                <?php if (!empty($a['action_label'])): ?> — <?= e($a['action_label']) ?><?php elseif (!empty($a['description'])): ?> — <?= e(mb_strimwidth($a['description'],0,60,'…')) ?><?php endif; ?>
            </div>
            <div class="garden-activity-time"><?= $agoStr ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Empty state when no seeds at all -->
<?php if ($totalSeeds === 0): ?>
<div style="text-align:center;padding:var(--spacing-10) var(--spacing-4)">
    <div style="font-size:3.5rem;margin-bottom:var(--spacing-4)">🌿</div>
    <h2 style="margin-bottom:var(--spacing-3)">Your garden starts here</h2>
    <p style="color:var(--color-text-muted);max-width:420px;margin:0 auto var(--spacing-4)">
        Add your seeds to unlock planting calendars, harvest windows, family needs tracking, and more.
    </p>
    <a href="<?= url('/seeds/create') ?>" class="btn btn-primary">+ Add Your First Seed</a>
</div>
<?php endif; ?>

</div>
