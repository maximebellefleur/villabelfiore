<?php
/**
 * Partial: Garden Bed Schematic — Topographic terracotta style
 * Expects: $schematicBeds (array), $schematicGardens (id→name map)
 * Optional: $schematicTitle, $schematicLink, $schematicLinkLabel
 */
if (empty($schematicBeds)) return;

if (!function_exists('_gs_renderBed')) {
    function _gs_renderBed(array $bed, float $scale = 45): string {
        $lM = max(0.4, (float)($bed['length_m'] ?? 2));
        $wM = max(0.3, (float)($bed['width_m']  ?? 1));
        $nL = max(1, (int)($bed['bed_rows'] ?? 1));
        $ld = $bed['line_dir'] ?? 'EW';
        $pl = $bed['plantings'] ?? [];

        // width_m = E-W → card width; length_m = N-S → card height
        $cardW = (int) max(70,  min(220, round($wM * $scale)));
        $cardH = (int) max(48,  min(140, round($lM * $scale)));

        $sc = ['growing' => '#6b9952', 'planned' => '#c8924a', 'harvested' => '#c04838'];
        $dc = ['growing' => '#4d7a34', 'planned'  => '#a07028', 'harvested' => '#962e20', 'empty' => '#b8a898'];

        // Dominant status for the corner dot
        $counts = ['growing' => 0, 'planned' => 0, 'harvested' => 0, 'empty' => 0];
        for ($i = 1; $i <= $nL; $i++) {
            $st = $pl[$i]['status'] ?? 'empty';
            $counts[$st] = ($counts[$st] ?? 0) + 1;
        }
        $dotSt = 'empty';
        foreach (['growing', 'harvested', 'planned'] as $s) {
            if (!empty($counts[$s])) { $dotSt = $s; break; }
        }

        $hid  = 'gh' . $bed['id'];
        $cid  = 'gc' . $bed['id'];
        $href = url('/items/' . (int)$bed['id'] . '/planting');
        $name = htmlspecialchars($bed['name'], ENT_QUOTES);
        $dim  = ($wM > 0.3 && $lM > 0.4)
            ? rtrim(rtrim(number_format($wM, 1), '0'), '.') . '×' . rtrim(rtrim(number_format($lM, 1), '0'), '.') . 'm'
            : '';

        // Build SVG lines
        $lines = '';
        if ($ld === 'NS') {
            $sw = ($cardW - 3) / $nL;
            for ($li = 0; $li < $nL; $li++) {
                $st  = $pl[$li+1]['status'] ?? 'empty';
                $col = $sc[$st] ?? null;
                $x   = round(1.5 + $li * $sw);
                $w   = max(1, round($sw));
                $fill   = $col ? $col : "url(#$hid)";
                $fopac  = $col ? '.82' : '1';
                $lines .= "<rect x=\"$x\" y=\"1.5\" width=\"$w\" height=\"" . ($cardH-3) . "\" clip-path=\"url(#$cid)\" fill=\"$fill\" fill-opacity=\"$fopac\"/>";
                if ($li > 0) $lines .= "<line x1=\"$x\" y1=\"1.5\" x2=\"$x\" y2=\"" . ($cardH-1.5) . "\" stroke=\"#fff8f0\" stroke-width=\"1\" opacity=\".5\"/>";
            }
        } else {
            $sh = ($cardH - 3) / $nL;
            for ($li = 0; $li < $nL; $li++) {
                $st  = $pl[$li+1]['status'] ?? 'empty';
                $col = $sc[$st] ?? null;
                $y   = round(1.5 + $li * $sh);
                $h   = max(1, round($sh));
                $fill  = $col ? $col : "url(#$hid)";
                $fopac = $col ? '.82' : '1';
                $lines .= "<rect x=\"1.5\" y=\"$y\" width=\"" . ($cardW-3) . "\" height=\"$h\" clip-path=\"url(#$cid)\" fill=\"$fill\" fill-opacity=\"$fopac\"/>";
                if ($li > 0) $lines .= "<line x1=\"1.5\" y1=\"$y\" x2=\"" . ($cardW-1.5) . "\" y2=\"$y\" stroke=\"#fff8f0\" stroke-width=\"1\" opacity=\".5\"/>";
            }
        }

        $dotX = $cardW - 10;
        $dotC = $dc[$dotSt];
        $dimHtml = $dim ? ' <span class="gs-bed-dim">· ' . $dim . '</span>' : '';

        return '<a href="' . $href . '" class="gs-bed" title="' . $name . '">'
            . '<div class="gs-north">↓ N</div>'
            . '<svg class="gs-bed-svg" width="' . $cardW . '" height="' . $cardH . '"'
            .     ' viewBox="0 0 ' . $cardW . ' ' . $cardH . '" xmlns="http://www.w3.org/2000/svg">'
            .   '<defs>'
            .     '<pattern id="' . $hid . '" patternUnits="userSpaceOnUse" width="6" height="6" patternTransform="rotate(45)">'
            .       '<line x1="0" y1="0" x2="0" y2="6" stroke="#c4b49a" stroke-width="1.4"/>'
            .     '</pattern>'
            .     '<clipPath id="' . $cid . '">'
            .       '<rect x="1.5" y="1.5" width="' . ($cardW-3) . '" height="' . ($cardH-3) . '" rx="4.5"/>'
            .     '</clipPath>'
            .   '</defs>'
            .   '<rect x="0" y="0" width="' . $cardW . '" height="' . $cardH . '" fill="#fef9f2" rx="6"/>'
            .   $lines
            .   '<rect x=".75" y=".75" width="' . ($cardW-1.5) . '" height="' . ($cardH-1.5) . '"'
            .       ' fill="none" stroke="#c4855a" stroke-width="1.5" rx="5.5"/>'
            .   '<circle cx="' . $dotX . '" cy="10" r="4" fill="' . $dotC . '" stroke="#fef9f2" stroke-width="1.5"/>'
            . '</svg>'
            . '<div class="gs-bed-label">' . $name . $dimHtml . '</div>'
            . '</a>';
    }
}

$_schTitle = $schematicTitle ?? 'Bed Overview';

$_bedsByGarden = [];
foreach ($schematicBeds as $_bed) {
    $_bedsByGarden[$_bed['parent_id'] ?? 0][] = $_bed;
}

$_totalBeds    = count($schematicBeds);
$_totalGardens = count(array_filter(array_keys($_bedsByGarden), fn($k) => $k > 0));
?>
<style>
.gs-wrap {
    position: relative;
    background: #f5f0e8;
    border: 1px solid #d4c4b0;
    border-radius: 14px;
    padding: 18px 20px 20px;
    margin-bottom: var(--spacing-4);
    overflow: hidden;
}
.gs-topo {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    opacity: .45;
}
.gs-inner { position: relative; }
.gs-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    gap: 10px;
}
.gs-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 1.05rem;
    font-weight: 800;
    color: #3a2a1e;
    text-decoration: none;
}
.gs-stats {
    font-size: .72rem;
    color: #8c7060;
    font-weight: 500;
    margin-left: 4px;
}
.gs-link { font-size: .75rem; color: #b8714a; font-weight: 700; text-decoration: none; }
.gs-link:hover { text-decoration: underline; }
.gs-compass {
    font-size: .63rem;
    font-weight: 800;
    color: #b8714a;
    letter-spacing: .06em;
    flex-shrink: 0;
}
.gs-group { margin-bottom: 20px; }
.gs-group:last-of-type { margin-bottom: 8px; }
.gs-group-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}
.gs-diamond {
    width: 9px;
    height: 9px;
    background: #b8714a;
    transform: rotate(45deg);
    flex-shrink: 0;
    border-radius: 1px;
}
.gs-group-name {
    font-size: .66rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #5a3a28;
    white-space: nowrap;
}
.gs-group-rule {
    flex: 1;
    border: none;
    border-top: 1.5px dashed #c4a888;
    margin: 0;
}
.gs-group-count {
    font-size: .63rem;
    color: #8c7060;
    font-weight: 600;
    white-space: nowrap;
}
.gs-shelf {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: flex-end;
    margin-bottom: 6px;
}
.gs-bed {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    text-decoration: none;
    color: inherit;
    flex-shrink: 0;
    transition: transform .14s;
}
.gs-bed:hover { transform: translateY(-2px); }
.gs-bed:hover .gs-bed-svg { box-shadow: 0 5px 16px rgba(100,50,20,.22); }
.gs-north {
    font-size: .5rem;
    font-weight: 800;
    color: #b8714a;
    letter-spacing: .06em;
    line-height: 1;
}
.gs-bed-svg {
    display: block;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(80,40,10,.15);
    transition: box-shadow .14s;
}
.gs-bed-label {
    font-size: .62rem;
    font-weight: 700;
    color: #4a3728;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}
.gs-bed-dim {
    font-weight: 400;
    color: #8c7060;
}
.gs-legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    padding-top: 14px;
    border-top: 1px solid #d4c4b0;
    margin-top: 4px;
}
.gs-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: .62rem;
    color: #7a6050;
    font-weight: 500;
}
.gs-legend-swatch {
    width: 22px;
    height: 11px;
    border-radius: 2px;
    flex-shrink: 0;
    border: 1px solid rgba(0,0,0,.1);
}
.gs-legend-hatch {
    background: repeating-linear-gradient(
        45deg,
        #c4b49a,
        #c4b49a 1px,
        #e8ddd0 1px,
        #e8ddd0 5px
    );
}
</style>

<div class="gs-wrap">
    <!-- Topographic background -->
    <svg class="gs-topo" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
        <g fill="none" stroke="#c4a882" stroke-width="1">
            <path d="M-100,55  C100,28  250,75  450,48  S700,18  900,52  S1100,78 1300,50  S1450,28 1600,58"/>
            <path d="M-100,110 C100,83  250,130 450,103 S700,73  900,107 S1100,133 1300,105 S1450,83 1600,113"/>
            <path d="M-100,168 C120,142 270,188 470,160 S720,130 920,165 S1120,192 1320,162 S1460,140 1600,170"/>
            <path d="M-100,228 C110,202 280,248 480,220 S730,190 930,225 S1130,252 1330,222 S1465,200 1600,230"/>
            <path d="M-100,290 C130,264 285,308 488,280 S738,250 940,285 S1140,312 1340,282 S1470,260 1600,292"/>
            <path d="M-100,354 C115,328 290,370 494,342 S746,312 948,347 S1148,374 1348,344 S1472,322 1600,356"/>
            <path d="M-100,420 C125,394 295,435 498,406 S750,376 952,412 S1152,438 1354,408 S1476,386 1600,422"/>
        </g>
    </svg>

    <div class="gs-inner">
        <!-- Header -->
        <div class="gs-header">
            <?php if (isset($schematicLink)): ?>
                <a href="<?= $schematicLink ?>" class="gs-title">
                    🌱 <?= htmlspecialchars($_schTitle, ENT_QUOTES) ?>
                    <span class="gs-stats"><?= $_totalBeds ?> bed<?= $_totalBeds !== 1 ? 's' : '' ?> · <?= $_totalGardens ?> garden<?= $_totalGardens !== 1 ? 's' : '' ?></span>
                </a>
                <a href="<?= $schematicLink ?>" class="gs-link"><?= $schematicLinkLabel ?? 'Full view →' ?></a>
            <?php else: ?>
                <div class="gs-title">
                    🌱 <?= htmlspecialchars($_schTitle, ENT_QUOTES) ?>
                    <span class="gs-stats"><?= $_totalBeds ?> bed<?= $_totalBeds !== 1 ? 's' : '' ?> · <?= $_totalGardens ?> garden<?= $_totalGardens !== 1 ? 's' : '' ?></span>
                </div>
                <div class="gs-compass">↑ N</div>
            <?php endif; ?>
        </div>

        <?php foreach ($_bedsByGarden as $_gardenId => $_beds): ?>
        <div class="gs-group">
            <div class="gs-group-head">
                <div class="gs-diamond"></div>
                <div class="gs-group-name">
                    <?= htmlspecialchars(($_gardenId && isset($schematicGardens[$_gardenId]))
                        ? $schematicGardens[$_gardenId]
                        : 'Prep Beds (unassigned)', ENT_QUOTES) ?>
                </div>
                <hr class="gs-group-rule">
                <div class="gs-group-count"><?= count($_beds) ?> bed<?= count($_beds) !== 1 ? 's' : '' ?></div>
            </div>

            <?php
            // Compute per-group scale from physical dimensions
            $_allDims = [];
            foreach ($_beds as $_b) {
                $_allDims[] = (float)($_b['width_m']  ?? 0);
                $_allDims[] = (float)($_b['length_m'] ?? 0);
            }
            $_maxDim   = max(array_merge($_allDims, [1]));
            $_scale    = min(55, 220 / max(1, $_maxDim));

            // GPS shelf-row grouping
            $_gpsCount = 0;
            foreach ($_beds as $_b) {
                if (!empty($_b['gps_lat']) && !empty($_b['gps_lng'])) $_gpsCount++;
            }

            if ($_gpsCount >= 2):
                $_bedsGps   = array_values(array_filter($_beds, fn($b) => !empty($b['gps_lat']) && !empty($b['gps_lng'])));
                $_bedsNoGps = array_values(array_filter($_beds, fn($b) => empty($b['gps_lat'])  || empty($b['gps_lng'])));
                usort($_bedsGps, function($a, $b) {
                    $d = (float)$b['gps_lat'] - (float)$a['gps_lat'];
                    if (abs($d) > 1e-9) return $d > 0 ? 1 : -1;
                    return ((float)$a['gps_lng'] - (float)$b['gps_lng']) > 0 ? 1 : -1;
                });
                $_allLats   = array_map(fn($b) => (float)$b['gps_lat'], $_bedsGps);
                $_latSpan   = count($_allLats) > 1 ? (max($_allLats) - min($_allLats)) : 0;
                $_latTol    = max($_latSpan > 0 ? $_latSpan / (ceil(count($_bedsGps) / 3) + 1) : 0.00005, 0.00003);
                $_shelfRows = []; $_shelf = []; $_prevLat = null;
                foreach ($_bedsGps as $_b) {
                    $_lat = (float)$_b['gps_lat'];
                    if ($_prevLat !== null && ($_prevLat - $_lat) > $_latTol) {
                        $_shelfRows[] = $_shelf; $_shelf = [];
                    }
                    $_shelf[] = $_b; $_prevLat = $_lat;
                }
                if ($_shelf) $_shelfRows[] = $_shelf;
                if ($_bedsNoGps) $_shelfRows[] = $_bedsNoGps;

                foreach ($_shelfRows as $_shelf):
                    usort($_shelf, fn($a, $b) => ((float)($a['gps_lng'] ?? 0) - (float)($b['gps_lng'] ?? 0)) > 0 ? 1 : -1);
            ?>
            <div class="gs-shelf">
                <?php foreach ($_shelf as $_bed): ?>
                    <?= _gs_renderBed($_bed, $_scale) ?>
                <?php endforeach; ?>
            </div>
            <?php       endforeach;
            else: ?>
            <div class="gs-shelf">
                <?php foreach ($_beds as $_bed): ?>
                    <?= _gs_renderBed($_bed, $_scale) ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Legend -->
        <div class="gs-legend">
            <div class="gs-legend-item">
                <div class="gs-legend-swatch" style="background:#6b9952"></div> Growing
            </div>
            <div class="gs-legend-item">
                <div class="gs-legend-swatch" style="background:#c8924a"></div> Planned
            </div>
            <div class="gs-legend-item">
                <div class="gs-legend-swatch" style="background:#c04838"></div> Harvested
            </div>
            <div class="gs-legend-item">
                <div class="gs-legend-swatch gs-legend-hatch"></div> Empty
            </div>
        </div>
    </div>
</div>
