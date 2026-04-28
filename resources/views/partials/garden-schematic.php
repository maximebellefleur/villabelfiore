<?php
/**
 * Partial: Garden Bed Schematic
 * Expects: $schematicBeds (array), $schematicGardens (id→name map)
 * Optional: $schematicTitle (string, default "🌱 Bed Overview")
 */
if (empty($schematicBeds)) return;

$_schTitle = $schematicTitle ?? '🌱 Bed Overview';

$_bedsByGarden = [];
foreach ($schematicBeds as $_bed) {
    $_key = $_bed['parent_id'] ?? 0;
    $_bedsByGarden[$_key][] = $_bed;
}
$_statusColors = ['growing'=>'#22c55e','planned'=>'#f59e0b','harvested'=>'#3b82f6','empty'=>'#e2e8f0'];
?>
<style>
.schematic-section { margin-bottom:var(--spacing-4); }
.schematic-section-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3); }
.schematic-section-title { font-size:1rem;font-weight:700;display:flex;align-items:center;gap:6px; }
.schematic-group { margin-bottom:var(--spacing-3); }
.schematic-group-label { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:8px;display:flex;align-items:center;gap:6px; }
.schematic-beds-row { display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end; }
.schematic-bed-card { display:flex;flex-direction:column;align-items:center;gap:4px;text-decoration:none;color:inherit;cursor:pointer;transition:transform .15s; }
.schematic-bed-card:hover { transform:translateY(-2px); }
.schematic-bed-card svg { display:block;border-radius:4px;box-shadow:0 1px 6px rgba(0,0,0,.12);transition:box-shadow .15s; }
.schematic-bed-card:hover svg { box-shadow:0 3px 12px rgba(0,0,0,.2); }
.schematic-bed-name { font-size:.7rem;font-weight:600;color:var(--color-text-muted);text-align:center;max-width:80px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.schematic-legend { display:flex;gap:10px;flex-wrap:wrap;margin-top:8px; }
.schematic-legend-item { display:flex;align-items:center;gap:4px;font-size:.68rem;color:var(--color-text-muted); }
.schematic-legend-dot { width:10px;height:10px;border-radius:2px;flex-shrink:0; }
</style>

<div class="schematic-section">
    <div class="schematic-section-head">
        <span class="schematic-section-title"><?= $_schTitle ?></span>
        <?php if (isset($schematicLink)): ?><a href="<?= $schematicLink ?>" style="font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600"><?= $schematicLinkLabel ?? 'View all →' ?></a><?php endif; ?>
    </div>

    <?php foreach ($_bedsByGarden as $_gardenId => $_beds): ?>
    <div class="schematic-group">
        <div class="schematic-group-label">
            <?php if ($_gardenId && isset($schematicGardens[$_gardenId])): ?>
                🌿 <?= e($schematicGardens[$_gardenId]) ?>
            <?php else: ?>
                🟫 Prep Beds (unassigned)
            <?php endif; ?>
        </div>
        <?php
        $_gpsCount = 0;
        foreach ($_beds as $_b) { if (!empty($_b['gps_lat']) && !empty($_b['gps_lng'])) $_gpsCount++; }
        $_useGps = ($_gpsCount >= 2);

        if ($_useGps):
            $_bedsGps   = array_values(array_filter($_beds, fn($b) => !empty($b['gps_lat']) && !empty($b['gps_lng'])));
            $_bedsNoGps = array_values(array_filter($_beds, fn($b) => empty($b['gps_lat']) || empty($b['gps_lng'])));
            usort($_bedsGps, function($a, $b) {
                $latD = (float)$b['gps_lat'] - (float)$a['gps_lat'];
                if (abs($latD) > 1e-9) return $latD > 0 ? 1 : -1;
                return ((float)$a['gps_lng'] - (float)$b['gps_lng']) > 0 ? 1 : -1;
            });
            $_allLats = array_map(fn($b) => (float)$b['gps_lat'], $_bedsGps);
            $_latSpan = count($_allLats) > 1 ? (max($_allLats) - min($_allLats)) : 0;
            $_latTol  = max($_latSpan > 0 ? $_latSpan / (ceil(count($_bedsGps) / 3) + 1) : 0.00005, 0.00003);
            $_shelfRows = []; $_shelf = []; $_prevLat = null;
            foreach ($_bedsGps as $_b) {
                $_lat = (float)$_b['gps_lat'];
                if ($_prevLat !== null && ($_prevLat - $_lat) > $_latTol) { $_shelfRows[] = $_shelf; $_shelf = []; }
                $_shelf[] = $_b; $_prevLat = $_lat;
            }
            if ($_shelf) $_shelfRows[] = $_shelf;
            if ($_bedsNoGps) $_shelfRows[] = $_bedsNoGps;
            $_allDims = [];
            foreach ($_beds as $_b) { $_allDims[] = (float)($_b['length_m'] ?? 0); $_allDims[] = (float)($_b['width_m'] ?? 0); }
            $_maxDim = max(array_merge($_allDims, [1]));
            $_scale  = 80 / $_maxDim;
        ?>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:8px;background:#f1f5f9;border:1px solid var(--color-border);border-radius:8px;padding:12px;overflow-x:auto">
        <?php foreach ($_shelfRows as $_shelf):
            usort($_shelf, fn($a, $b) => ((float)($a['gps_lng'] ?? 0) - (float)($b['gps_lng'] ?? 0)) > 0 ? 1 : -1);
        ?>
        <div style="display:flex;flex-direction:row;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <?php foreach ($_shelf as $_bed):
            $_lM = max(1.0, (float)($_bed['length_m'] ?? 2));
            $_wM = max(0.5, (float)($_bed['width_m']  ?? 1));
            $_bW = max(24, (int)round($_wM * $_scale));
            $_bH = max(16, (int)round($_lM * $_scale));
            $_nL = max(1, (int)($_bed['bed_rows'] ?? 1));
            $_ld = $_bed['line_dir'] ?? 'NS';
            $_pl = $_bed['plantings'] ?? [];
        ?>
        <a href="<?= url('/items/' . (int)$_bed['id'] . '/planting') ?>" title="<?= e($_bed['name']) ?>"
           style="display:flex;flex-direction:column;align-items:center;text-decoration:none;color:inherit">
            <svg width="<?= $_bW ?>" height="<?= $_bH ?>" viewBox="0 0 <?= $_bW ?> <?= $_bH ?>" xmlns="http://www.w3.org/2000/svg" style="display:block;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.15)">
                <rect x="0" y="0" width="<?= $_bW ?>" height="<?= $_bH ?>" fill="#e2e8f0" rx="2"/>
                <?php if ($_ld === 'EW'): $_sh = $_bH / $_nL; for ($_li = 0; $_li < $_nL; $_li++): $_col = $_statusColors[($_pl[$_li+1] ?? [])['status'] ?? 'empty']; $_y = round($_li * $_sh); ?>
                <rect x="0" y="<?= $_y ?>" width="<?= $_bW ?>" height="<?= round($_sh) ?>" fill="<?= $_col ?>" fill-opacity="0.85"/>
                <?php if ($_li > 0): ?><line x1="0" y1="<?= $_y ?>" x2="<?= $_bW ?>" y2="<?= $_y ?>" stroke="#fff" stroke-width="1"/><?php endif; ?>
                <?php endfor; else: $_sw = $_bW / $_nL; for ($_li = 0; $_li < $_nL; $_li++): $_col = $_statusColors[($_pl[$_li+1] ?? [])['status'] ?? 'empty']; $_x = round($_li * $_sw); ?>
                <rect x="<?= $_x ?>" y="0" width="<?= round($_sw) ?>" height="<?= $_bH ?>" fill="<?= $_col ?>" fill-opacity="0.85"/>
                <?php if ($_li > 0): ?><line x1="<?= $_x ?>" y1="0" x2="<?= $_x ?>" y2="<?= $_bH ?>" stroke="#fff" stroke-width="1"/><?php endif; ?>
                <?php endfor; endif; ?>
                <rect x="0" y="0" width="<?= $_bW ?>" height="<?= $_bH ?>" fill="none" stroke="#94a3b8" stroke-width="1.5" rx="2"/>
            </svg>
            <span style="font-size:.55rem;font-weight:600;color:#475569;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:<?= $_bW + 20 ?>px;margin-top:2px"><?= e($_bed['name']) ?></span>
        </a>
        <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="schematic-beds-row">
        <?php $_dH = 120; foreach ($_beds as $_bed):
            $_lM = (float)($_bed['length_m'] ?? 0); $_wM = (float)($_bed['width_m'] ?? 0);
            $_dW = ($_lM > 0 && $_wM > 0) ? max(40, min(180, round($_dH * ($_wM / $_lM)))) : 80;
            $_nL = max(1, (int)($_bed['bed_rows'] ?? 1)); $_ld = $_bed['line_dir'] ?? 'NS'; $_pl = $_bed['plantings'] ?? [];
        ?>
        <a href="<?= url('/items/' . (int)$_bed['id'] . '/planting') ?>" class="schematic-bed-card" title="<?= e($_bed['name']) ?>">
            <svg width="<?= $_dW ?>" height="<?= $_dH ?>" viewBox="0 0 <?= $_dW ?> <?= $_dH ?>" xmlns="http://www.w3.org/2000/svg">
                <rect x="0" y="0" width="<?= $_dW ?>" height="<?= $_dH ?>" fill="#f1f5f9" rx="3"/>
                <?php if ($_ld === 'EW'): $_sw = $_dW / $_nL; for ($_li = 0; $_li < $_nL; $_li++): $_col = $_statusColors[($_pl[$_li+1] ?? [])['status'] ?? 'empty']; $_x = round($_li * $_sw); ?>
                <rect x="<?= $_x ?>" y="0" width="<?= round($_sw) ?>" height="<?= $_dH ?>" fill="<?= $_col ?>" fill-opacity="0.8"/>
                <?php if ($_li > 0): ?><line x1="<?= $_x ?>" y1="0" x2="<?= $_x ?>" y2="<?= $_dH ?>" stroke="#fff" stroke-width="1"/><?php endif; ?>
                <?php endfor; else: $_sh = $_dH / $_nL; for ($_li = 0; $_li < $_nL; $_li++): $_col = $_statusColors[($_pl[$_li+1] ?? [])['status'] ?? 'empty']; $_y = round($_li * $_sh); ?>
                <rect x="0" y="<?= $_y ?>" width="<?= $_dW ?>" height="<?= round($_sh) ?>" fill="<?= $_col ?>" fill-opacity="0.8"/>
                <?php if ($_li > 0): ?><line x1="0" y1="<?= $_y ?>" x2="<?= $_dW ?>" y2="<?= $_y ?>" stroke="#fff" stroke-width="1"/><?php endif; ?>
                <?php endfor; endif; ?>
                <rect x="0" y="0" width="<?= $_dW ?>" height="<?= $_dH ?>" fill="none" stroke="#94a3b8" stroke-width="1.5" rx="3"/>
            </svg>
            <span class="schematic-bed-name"><?= e($_bed['name']) ?></span>
            <?php if ($_lM > 0 && $_wM > 0): ?><span style="font-size:.62rem;color:var(--color-text-muted)"><?= $_wM ?>×<?= $_lM ?>m</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="schematic-legend">
        <div class="schematic-legend-item"><div class="schematic-legend-dot" style="background:#22c55e"></div> Growing</div>
        <div class="schematic-legend-item"><div class="schematic-legend-dot" style="background:#f59e0b"></div> Planned</div>
        <div class="schematic-legend-item"><div class="schematic-legend-dot" style="background:#3b82f6"></div> Harvested</div>
        <div class="schematic-legend-item"><div class="schematic-legend-dot" style="background:#e2e8f0"></div> Empty</div>
    </div>
</div>
