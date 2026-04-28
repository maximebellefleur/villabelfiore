<?php
$typeColor = [
    'vegetable' => '#2d6a4f',
    'herb'      => '#6b9b5e',
    'fruit'     => '#c0413a',
    'flower'    => '#c84b9f',
    'other'     => '#A66141',
];
$typeEmoji = ['vegetable'=>'🥦','herb'=>'🌿','fruit'=>'🍓','flower'=>'🌸','other'=>'🌾'];
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$sort  = $sort ?? 'name';
$type  = $type  ?? '';
$search= $search ?? '';

function seedColor(array $seed, array $typeColor): string {
    $c = $seed['color'] ?? null;
    if ($c && preg_match('/^#[0-9a-f]{6}$/i', $c)) return $c;
    return $typeColor[$seed['type'] ?? 'other'] ?? '#A66141';
}
?>
<div class="page-header">
    <h1 class="page-title">🌱 Seed Catalog</h1>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="<?= url('/seeds/family-needs') ?>" class="btn btn-secondary btn-sm">👨‍👩‍👧 Family Needs</a>
        <a href="<?= url('/seeds/create') ?>" class="btn btn-primary">+ Add Seed</a>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning" style="margin-bottom:var(--spacing-4);padding:var(--spacing-3);background:rgba(234,179,8,.12);border:1px solid rgba(234,179,8,.4);border-radius:var(--radius);color:#92400e">
    ⚠️ <strong><?= count($lowStock) ?> seed<?= count($lowStock) > 1 ? 's' : '' ?> running low:</strong>
    <?= implode(', ', array_map(fn($s) => e($s['name']), $lowStock)) ?>
</div>
<?php endif; ?>

<!-- Filter bar: search + type 50/50, then sort row -->
<form method="GET" action="<?= url('/seeds') ?>" id="seedFilterForm">
  <input type="hidden" name="sort" value="<?= e($sort) ?>">
  <div style="display:flex;gap:8px;margin-bottom:8px">
    <input type="text" name="q" class="form-input" placeholder="Search seeds…" value="<?= e($search) ?>"
           style="flex:1;min-width:0" oninput="this.form.submit()">
    <select name="type" class="form-input" style="flex:1;min-width:0" onchange="this.form.submit()">
        <option value="">All types</option>
        <?php foreach (['vegetable','herb','fruit','flower','other'] as $t): ?>
        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ($typeEmoji[$t] ?? '') . ' ' . ucfirst($t) ?></option>
        <?php endforeach; ?>
    </select>
  </div>
  <div style="display:flex;gap:6px;align-items:center;margin-bottom:var(--spacing-4)">
    <span style="font-size:.7rem;font-weight:700;color:var(--color-text-muted);letter-spacing:.06em;text-transform:uppercase;margin-right:4px">Sort</span>
    <?php foreach (['name'=>'Name','type'=>'Type','days'=>'Days to harvest'] as $sk => $sl): ?>
    <button type="submit" name="sort" value="<?= $sk ?>"
            class="btn btn-<?= $sort === $sk ? 'primary' : 'ghost' ?> btn-sm"
            style="font-size:.72rem;padding:3px 10px"><?= $sl ?></button>
    <?php endforeach; ?>
    <?php if ($search || $type): ?>
    <a href="<?= url('/seeds') ?>" class="btn btn-ghost btn-sm" style="margin-left:auto;font-size:.72rem">✕ Clear</a>
    <?php endif; ?>
  </div>
</form>

<?php if (empty($seeds)): ?>
<div class="empty-state" style="text-align:center;padding:var(--spacing-8) var(--spacing-4)">
    <div style="font-size:3rem;margin-bottom:var(--spacing-3)">🌱</div>
    <h3>No seeds yet</h3>
    <p class="text-muted">Add your first seed variety to start tracking your stock.</p>
    <a href="<?= url('/seeds/create') ?>" class="btn btn-primary" style="margin-top:var(--spacing-3)">+ Add Seed</a>
</div>
<?php else: ?>

<div class="seed-catalog-grid">
<?php foreach ($seeds as $seed):
    $low   = $seed['stock_enabled'] && $seed['stock_low_threshold'] !== null && (float)$seed['stock_qty'] <= (float)$seed['stock_low_threshold'];
    $plantMonths = $seed['planting_months'] ? json_decode($seed['planting_months'], true) : [];
    $color = seedColor($seed, $typeColor);
    $emoji = $seed['emoji'] ?: ($typeEmoji[$seed['type'] ?? 'other'] ?? '🌱');
    $dth   = (int)($seed['days_to_maturity'] ?? 0);
    $sp    = (int)($seed['spacing_cm'] ?? 0);
?>
<article class="seed-packet">
  <!-- Packet header -->
  <div class="seed-packet-header" style="background:<?= e($color) ?>">
    <div class="seed-packet-emoji"><?= e($emoji) ?></div>
    <?php if ($seed['stock_enabled']): ?>
    <div class="seed-packet-stock <?= $low ? 'is-low' : '' ?>">
      <?= number_format((float)$seed['stock_qty'], 1) ?> <?= e($seed['stock_unit'] ?? '') ?>
    </div>
    <?php endif; ?>
    <div class="seed-packet-type-badge"><?= e(ucfirst($seed['type'] ?? 'other')) ?></div>
  </div>

  <!-- Packet body -->
  <div class="seed-packet-body">
    <div class="seed-packet-name"><?= e($seed['name']) ?></div>
    <?php if ($seed['variety']): ?>
    <div class="seed-packet-variety"><?= e($seed['variety']) ?></div>
    <?php endif; ?>

    <!-- Key specs -->
    <?php if ($dth || $sp): ?>
    <div class="seed-packet-specs">
      <?php if ($dth): ?>
      <span class="seed-packet-spec">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <?= $dth ?>d
      </span>
      <?php endif; ?>
      <?php if ($sp): ?>
      <span class="seed-packet-spec">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 3H3"/><path d="M21 21H3"/><path d="M12 3v18"/><path d="M6 8l-3 4 3 4"/><path d="M18 8l3 4-3 4"/></svg>
        <?= $sp ?>cm
      </span>
      <?php endif; ?>
      <?php if (!empty($seed['family'])): ?>
      <span class="seed-packet-spec" style="text-transform:capitalize"><?= e($seed['family']) ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Planting calendar -->
    <?php if (!empty($plantMonths)): ?>
    <div class="seed-packet-calendar">
      <?php foreach (range(1,12) as $m): ?>
      <span class="seed-cal-month <?= in_array($m, $plantMonths) ? 'is-active' : '' ?>"
            style="<?= in_array($m, $plantMonths) ? 'background:' . e($color) . ';color:#fff;border-color:' . e($color) : '' ?>">
        <?= $monthNames[$m-1] ?>
      </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="seed-packet-actions">
      <a href="<?= url('/seeds/' . (int)$seed['id']) ?>" class="btn btn-ghost btn-sm seed-packet-view">View</a>
      <a href="<?= url('/seeds/' . (int)$seed['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">Edit</a>
    </div>
  </div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.seed-catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 14px;
}
.seed-packet {
  background: var(--color-surface-raised);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  overflow: hidden;
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  transition: box-shadow .16s, transform .16s;
}
.seed-packet:hover { box-shadow: var(--shadow); transform: translateY(-2px); }

/* Header — coloured strip with big emoji */
.seed-packet-header {
  position: relative;
  padding: 18px 12px 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.seed-packet-emoji { font-size: 2.6rem; line-height: 1; filter: drop-shadow(0 2px 4px rgba(0,0,0,.18)); }
.seed-packet-stock {
  position: absolute;
  top: 8px; right: 8px;
  background: rgba(255,255,255,.88);
  color: var(--color-text);
  font-size: .62rem; font-weight: 700;
  border-radius: 999px;
  padding: 2px 7px;
  box-shadow: 0 1px 3px rgba(0,0,0,.12);
}
.seed-packet-stock.is-low {
  background: #fef2f2;
  color: #dc2626;
}
.seed-packet-type-badge {
  position: absolute;
  bottom: 8px; left: 10px;
  background: rgba(255,255,255,.22);
  color: rgba(255,255,255,.9);
  font-size: .58rem; font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  border-radius: 999px;
  padding: 2px 7px;
  backdrop-filter: blur(4px);
}

/* Body */
.seed-packet-body {
  padding: 12px 13px 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}
.seed-packet-name { font-size: .95rem; font-weight: 800; color: var(--color-text); line-height: 1.25; }
.seed-packet-variety { font-size: .76rem; color: var(--color-text-muted); font-style: italic; margin-top: -2px; }

/* Specs row */
.seed-packet-specs {
  display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
  margin-top: 2px;
}
.seed-packet-spec {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: .68rem; color: var(--color-text-muted); font-family: var(--font-mono);
  font-weight: 600;
}
.seed-packet-spec svg { opacity: .6; flex-shrink: 0; }

/* Calendar */
.seed-packet-calendar {
  display: flex; gap: 2px; flex-wrap: wrap;
  margin-top: 4px;
}
.seed-cal-month {
  font-size: .6rem; font-weight: 600;
  padding: 2px 4px;
  border-radius: 3px;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  background: var(--color-bg);
  line-height: 1.3;
}
.seed-cal-month.is-active { font-weight: 700; }

/* Actions */
.seed-packet-actions {
  display: flex; gap: 6px; margin-top: 6px;
  padding-top: 8px;
  border-top: 1px solid var(--color-border);
}
.seed-packet-view { flex: 1; text-align: center; }
</style>
