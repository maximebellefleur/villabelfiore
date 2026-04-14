<?php
$monthNames  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$typeEmoji   = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'🌾'];
$plantMonths = !empty($seed['planting_months']) ? json_decode($seed['planting_months'], true) : [];
$harvMonths  = !empty($seed['harvest_months'])  ? json_decode($seed['harvest_months'], true)  : [];
$companions  = !empty($seed['companions'])  ? json_decode($seed['companions'],  true) : [];
$antagonists = !empty($seed['antagonists']) ? json_decode($seed['antagonists'], true) : [];
$low = $seed['stock_enabled'] && $seed['stock_low_threshold'] !== null && (float)$seed['stock_qty'] <= (float)$seed['stock_low_threshold'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= ($typeEmoji[$seed['type']] ?? '🌾') . ' ' . e($seed['name']) ?></h1>
        <?php if ($seed['variety']): ?><p class="text-muted" style="margin:0"><?= e($seed['variety']) ?><?php if ($seed['botanical_family']): ?> · <em><?= e($seed['botanical_family']) ?></em><?php endif; ?></p><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= url('/seeds/' . (int)$seed['id'] . '/edit') ?>" class="btn btn-secondary">Edit</a>
        <a href="<?= url('/seeds') ?>" class="btn btn-ghost">&larr; Back</a>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--spacing-4)">

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
    </div>

    <!-- Right column -->
    <div>
        <!-- Calendar -->
        <?php if ($plantMonths || $harvMonths): ?>
        <div class="card" style="margin-bottom:var(--spacing-3)">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-3)">Calendar</div>
                <div style="display:grid;grid-template-columns:repeat(12,1fr);gap:2px;margin-bottom:6px">
                    <?php foreach (range(1,12) as $m):
                        $isPlant = in_array($m,$plantMonths);
                        $isHarv  = in_array($m,$harvMonths);
                        $bg = $isPlant && $isHarv ? '#7c3aed' : ($isPlant ? 'var(--color-primary)' : ($isHarv ? '#b45309' : 'var(--color-bg)'));
                    ?>
                    <div style="text-align:center;padding:4px 0;background:<?= $bg ?>;color:<?= ($isPlant||$isHarv)?'#fff':'var(--color-text-muted)' ?>;border-radius:4px;font-size:0.6rem;font-weight:600;border:1px solid var(--color-border)"><?= $monthNames[$m-1] ?></div>
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

        <!-- Bed rows summary -->
        <?php if (!empty($bedRows)): ?>
        <div class="card" style="margin-bottom:var(--spacing-3)">
            <div class="card-body">
                <div class="settings-group-title" style="margin-bottom:var(--spacing-2)">Bed Rows (<?= count($bedRows) ?>)</div>
                <?php foreach (array_slice($bedRows, 0, 5) as $br): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;border-bottom:1px solid var(--color-border);font-size:0.85rem">
                    <span><?= $br['season_year'] ?> · Row <?= $br['row_number'] ?></span>
                    <span class="text-muted"><?= ucfirst($br['status']) ?></span>
                </div>
                <?php endforeach; ?>
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
                    <span class="text-muted"><?= $fn['yearly_qty'] ?? $fn['yearly_qty_kg'] ?? null ? number_format((float)($fn['yearly_qty'] ?? $fn['yearly_qty_kg']),1).' '.e($fn['yearly_unit'] ?? 'kg').'/yr' : '—' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
