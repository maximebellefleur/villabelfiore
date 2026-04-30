<?php
$CSRF  = \App\Support\CSRF::getToken();
$BASE  = defined('APP_BASE') ? APP_BASE : '';
$steps = $steps ?? [];

$statusCfg = [
    'empty'      => ['icon' => '○',  'label' => 'Pending',    'color' => 'var(--color-text-muted)', 'bg' => 'transparent'],
    'done'       => ['icon' => '✅', 'label' => 'Done',       'color' => 'var(--color-success,#27ae60)', 'bg' => 'transparent'],
    'error'      => ['icon' => '❌', 'label' => 'Error',      'color' => 'var(--color-danger,#e74c3c)',  'bg' => 'rgba(231,76,60,.05)'],
    'trigger_ai' => ['icon' => '🤖', 'label' => 'Trigger AI', 'color' => '#d97706',                     'bg' => 'rgba(217,119,6,.07)'],
];

$activeTasks   = array_values(array_filter($tasks ?? [], fn($t) => ($t['status'] ?? 'empty') !== 'done'));
$archivedTasks = array_values(array_filter($tasks ?? [], fn($t) => ($t['status'] ?? 'empty') === 'done'));
?>
<style>
.ptask-header { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:var(--spacing-5); }
.ptask-header h1 { font-size:1.3rem;font-weight:800;margin:0; }
.ptask-subtitle { font-size:.82rem;color:var(--color-text-muted);margin-top:2px; }

.ptask-batch-bar { display:none;align-items:center;gap:8px;flex-wrap:wrap;background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius);padding:8px 12px;margin-bottom:12px; }
.ptask-batch-bar.is-visible { display:flex; }
.ptask-batch-label { font-size:.82rem;font-weight:600;flex:1; }

.ptask-list { display:flex;flex-direction:column;gap:8px; }

.ptask-row { display:flex;align-items:flex-start;gap:10px;background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:12px 14px;transition:opacity .2s; }
.ptask-row--trigger_ai { border-color:#d97706;background:rgba(217,119,6,.06); }

.ptask-sel { width:16px;height:16px;margin-top:3px;flex-shrink:0;accent-color:var(--color-primary);cursor:pointer; }

.ptask-toggle { background:none;border:none;padding:0 2px;cursor:pointer;font-size:1.1rem;line-height:1;flex-shrink:0;margin-top:1px;user-select:none;align-self:center; }
.ptask-toggle:focus { outline:none; }

.ptask-body { flex:1;min-width:0; }
.ptask-title { font-weight:700;font-size:.93rem;line-height:1.3;margin-bottom:3px; }
.ptask-desc { font-size:.8rem;color:var(--color-text-muted);line-height:1.45; }
.ptask-meta { display:flex;align-items:center;gap:8px;margin-top:5px;flex-wrap:wrap; }
.ptask-date { font-size:.72rem;color:var(--color-text-muted); }
.ptask-note { font-size:.72rem;color:#d97706;font-style:italic; }
.ptask-badge { font-size:.68rem;font-weight:600;padding:1px 7px;border-radius:999px;background:rgba(217,119,6,.12);color:#d97706; }
.ptask-badge--error { background:rgba(231,76,60,.12);color:#e74c3c; }
.ptask-badge--empty { display:none; }

/* Archive section */
.ptask-archive { margin-top:20px;border:1px solid var(--color-border);border-radius:var(--radius-lg);overflow:hidden; }
.ptask-archive-header { display:flex;align-items:center;gap:8px;padding:11px 14px;cursor:pointer;background:var(--color-surface-raised);user-select:none; }
.ptask-archive-header:hover { background:var(--color-surface); }
.ptask-archive-chevron { font-size:.75rem;color:var(--color-text-muted);transition:transform .18s;flex-shrink:0; }
.ptask-archive-chevron.open { transform:rotate(90deg); }
.ptask-archive-label { font-size:.82rem;font-weight:700;color:var(--color-text-muted);flex:1; }
.ptask-archive-count { font-size:.75rem;color:var(--color-text-muted);background:var(--color-surface);border:1px solid var(--color-border);border-radius:999px;padding:1px 9px; }
.ptask-archive-body { display:none;padding:16px;border-top:1px solid var(--color-border); }
.ptask-archive-body.open { display:block; }

/* Future steps */
.fstep-section { background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);margin-bottom:var(--spacing-5);overflow:hidden; }
.fstep-section-header { display:flex;align-items:center;gap:8px;padding:10px 14px; }
.fstep-section-title { font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);flex:1; }
.fstep-section-count { font-size:.75rem;color:var(--color-text-muted);background:var(--color-surface);border:1px solid var(--color-border);border-radius:999px;padding:1px 9px; }
.fstep-section-toggle { background:none;border:none;cursor:pointer;font-size:.75rem;color:var(--color-text-muted);padding:3px 7px;border-radius:var(--radius);line-height:1;user-select:none;flex-shrink:0;transition:background .15s; }
.fstep-section-toggle:hover { background:var(--color-surface);color:var(--color-text); }
.fstep-section-body { display:none;padding:0 16px 16px; }
.fstep-section-body.open { display:block; }
.fstep-add { display:flex;flex-direction:column;gap:8px;margin-bottom:14px;padding-top:4px; }
.fstep-textarea { width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--color-border);border-radius:var(--radius);font-size:.85rem;line-height:1.5;resize:vertical;min-height:72px;font-family:inherit;background:var(--color-surface);color:var(--color-text) }
.fstep-textarea:focus { outline:2px solid var(--color-primary);border-color:transparent }
.fstep-hint { font-size:.72rem;color:var(--color-text-muted) }
.fstep-list { display:flex;flex-direction:column;gap:6px; }
.fstep-row { display:flex;align-items:flex-start;gap:8px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius);padding:10px 12px;cursor:default; }
.fstep-row--dragging { opacity:.3 }
.fstep-row--drag-over { box-shadow:0 -2px 0 var(--color-primary) }
.fstep-drag { cursor:grab;color:var(--color-text-muted);font-size:1rem;flex-shrink:0;opacity:.35;user-select:none;margin-top:1px }
.fstep-drag:hover { opacity:1 }
.fstep-body { flex:1;min-width:0 }
.fstep-zone { display:inline-block;font-size:.68rem;font-weight:700;padding:1px 7px;border-radius:999px;background:rgba(45,106,79,.12);color:var(--color-primary);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em }
.fstep-content { font-size:.85rem;line-height:1.55;white-space:pre-wrap;word-break:break-word }
.fstep-actions { display:flex;gap:4px;flex-shrink:0;margin-top:1px }
.fstep-btn { background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:4px;color:var(--color-text-muted);font-size:.85rem;line-height:1 }
.fstep-btn:hover { background:var(--color-surface-raised);color:var(--color-text) }
.fstep-empty { font-size:.82rem;color:var(--color-text-muted);text-align:center;padding:10px 0 4px }
</style>

<!-- ── Future Steps ─────────────────────────────────────────────── -->
<div class="fstep-section">
  <div class="fstep-section-header">
    <span class="fstep-section-title">📌 Future Steps</span>
    <span class="fstep-section-count" id="fstepSectionCount" style="<?= empty($steps) ? 'display:none' : '' ?>"><?= count($steps) ?></span>
    <button type="button" class="fstep-section-toggle" id="fstepToggleBtn" onclick="fstepToggleSection()" title="Expand / collapse">▶</button>
  </div>
  <div class="fstep-section-body" id="fstepSectionBody">
    <div class="fstep-add">
      <textarea class="fstep-textarea" id="fstepInput" placeholder="Write a future step… Start with (ZONE) to tag it, e.g. (SEEDS) Add companion planting logic" rows="3"></textarea>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
        <span class="fstep-hint">Tip: start with <strong>(ZONE)</strong> to tag, e.g. <em>(GARDEN) Fix map display</em></span>
        <div style="display:flex;gap:6px">
          <button type="button" class="btn btn-ghost btn-sm" id="fstepCancelBtn" style="display:none" onclick="fstepCancelEdit()">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm" id="fstepSaveBtn" onclick="fstepSave()">Log future step</button>
        </div>
      </div>
    </div>
    <div class="fstep-list" id="fstepList">
      <?php if (empty($steps)): ?>
      <div class="fstep-empty" id="fstepEmpty">No future steps yet.</div>
      <?php else: ?>
      <?php foreach ($steps as $st): ?>
      <?= fstepRowHtml($st) ?>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
function fstepRowHtml(array $st): string {
    $id   = (int)$st['id'];
    $zone = !empty($st['zone']) ? '<span class="fstep-zone">'.htmlspecialchars($st['zone'], ENT_QUOTES).'</span><br>' : '';
    $body = nl2br(htmlspecialchars($st['content'] ?? '', ENT_QUOTES));
    return '<div class="fstep-row" data-id="'.$id.'" id="fstep'.$id.'" draggable="true">'
         .   '<span class="fstep-drag" draggable="false">⠿</span>'
         .   '<div class="fstep-body">'.$zone.'<div class="fstep-content">'.$body.'</div></div>'
         .   '<div class="fstep-actions">'
         .     '<button type="button" class="fstep-btn" title="Edit" onclick="fstepEdit('.$id.')">✏️</button>'
         .     '<button type="button" class="fstep-btn" title="Copy" onclick="fstepCopy('.$id.')">📋</button>'
         .     '<button type="button" class="fstep-btn" title="Delete" onclick="fstepDelete('.$id.')">✕</button>'
         .   '</div>'
         . '</div>';
}
?>

<?php
$triggerAiCount = count(array_filter($activeTasks, fn($t) => ($t['status'] ?? '') === 'trigger_ai'));
?>
<div class="ptask-header">
  <div>
    <h1>Task Log</h1>
    <div class="ptask-subtitle">Every platform request, newest first. Click ○ to cycle status.</div>
  </div>
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <button type="button" class="btn btn-secondary btn-sm" id="ptaskCopyAiBtn" onclick="ptaskCopyAi()"
            style="display:<?= $triggerAiCount > 0 ? 'inline-flex' : 'none' ?>"
            title="Copy all 🤖 Trigger AI tasks formatted for pasting into an AI conversation">
      📋 Copy AI tasks (<span id="ptaskAiCount"><?= $triggerAiCount ?></span>)
    </button>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary btn-sm">&larr; Settings</a>
  </div>
</div>

<!-- Batch bar -->
<div class="ptask-batch-bar" id="ptaskBatchBar">
  <span class="ptask-batch-label"><span id="ptaskSelCount">0</span> selected</span>
  <button type="button" class="btn btn-ghost btn-sm" onclick="ptaskBatch('empty')">○ Pending</button>
  <button type="button" class="btn btn-ghost btn-sm" onclick="ptaskBatch('done')">✅ Done</button>
  <button type="button" class="btn btn-ghost btn-sm" onclick="ptaskBatch('error')">❌ Error</button>
  <button type="button" class="btn btn-ghost btn-sm" onclick="ptaskBatch('trigger_ai')">🤖 Trigger AI</button>
  <button type="button" class="btn btn-ghost btn-sm" onclick="ptaskDeselectAll()">✕ Clear</button>
</div>

<!-- Select-all row -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;padding:0 2px">
  <input type="checkbox" id="ptaskSelAll" class="ptask-sel" onchange="ptaskToggleAll(this.checked)" style="margin-top:0">
  <label for="ptaskSelAll" style="font-size:.8rem;color:var(--color-text-muted);cursor:pointer;user-select:none">Select all</label>
</div>

<!-- Active tasks -->
<div class="ptask-list" id="ptaskList">
<?php foreach ($activeTasks as $t):
    $sc     = $statusCfg[$t['status']] ?? $statusCfg['empty'];
    $rowCls = 'ptask-row ptask-row--' . e($t['status']);
    $created = substr($t['created_at'] ?? '', 0, 16);
    $badge   = $t['status'] !== 'empty' ? '<span class="ptask-badge ptask-badge--' . e($t['status']) . '">' . e($sc['label']) . '</span>' : '';
?>
  <div class="<?= $rowCls ?>" data-id="<?= (int)$t['id'] ?>" data-status="<?= e($t['status']) ?>" id="ptask<?= (int)$t['id'] ?>">
    <input type="checkbox" class="ptask-sel ptask-selbox" onchange="ptaskSelChanged()">
    <div class="ptask-body">
      <div class="ptask-title"><?= e($t['title']) ?></div>
      <div class="ptask-desc"><?= nl2br(e($t['description'])) ?></div>
      <div class="ptask-meta">
        <?= $badge ?>
        <?php if ($created): ?><span class="ptask-date"><?= e($created) ?></span><?php endif; ?>
        <?php if (!empty($t['note'])): ?><span class="ptask-note"><?= e($t['note']) ?></span><?php endif; ?>
      </div>
    </div>
    <button type="button" class="ptask-toggle" onclick="ptaskCycle(<?= (int)$t['id'] ?>)"
            title="Click to cycle: pending → done → error → trigger AI → pending"
            style="color:<?= $sc['color'] ?>"><?= $sc['icon'] ?></button>
  </div>
<?php endforeach; ?>
<?php if (empty($activeTasks)): ?>
  <div style="text-align:center;padding:40px;color:var(--color-text-muted);font-size:.88rem">No active tasks.</div>
<?php endif; ?>
</div>

<!-- Archive section -->
<div class="ptask-archive">
  <div class="ptask-archive-header" onclick="ptaskToggleArchive()">
    <span class="ptask-archive-chevron" id="ptaskArchiveChevron">▶</span>
    <span class="ptask-archive-label">Archived Tasks</span>
    <span class="ptask-archive-count" id="ptaskArchiveCount"><?= count($archivedTasks) ?></span>
  </div>
  <div class="ptask-archive-body" id="ptaskArchiveBody">
    <?php if (empty($archivedTasks)): ?>
    <p style="font-size:.82rem;color:var(--color-text-muted);margin:0">No archived tasks yet.</p>
    <?php else: ?>
    <p style="font-size:.82rem;color:var(--color-text-muted);margin:0 0 12px">
      <?= count($archivedTasks) ?> completed task<?= count($archivedTasks) !== 1 ? 's' : '' ?>.
    </p>
    <a href="<?= url('/settings/tasks/archive/download') ?>" class="btn btn-secondary btn-sm">⬇ Download archive.txt</a>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var CSRF  = <?= json_encode($CSRF) ?>;
  var BASE  = window.APP_BASE || '';

  var CYCLE  = ['empty','done','error','trigger_ai'];
  var ICONS  = { empty:'○', done:'✅', error:'❌', trigger_ai:'🤖' };
  var COLORS = { empty:'var(--color-text-muted)', done:'var(--color-success,#27ae60)', error:'var(--color-danger,#e74c3c)', trigger_ai:'#d97706' };
  var LABELS = { empty:'Pending', done:'Done', error:'Error', trigger_ai:'Trigger AI' };

  function rowEl(id)  { return document.getElementById('ptask' + id); }

  function updateAiCount() {
    var n   = document.querySelectorAll('.ptask-row[data-status="trigger_ai"]').length;
    var btn = document.getElementById('ptaskCopyAiBtn');
    var lbl = document.getElementById('ptaskAiCount');
    if (lbl) lbl.textContent = n;
    if (btn) btn.style.display = n > 0 ? 'inline-flex' : 'none';
  }

  function applyTask(t) {
    var row = rowEl(t.id);
    if (!row) return;

    if (t.status === 'done') {
      row.remove();
      var countEl = document.getElementById('ptaskArchiveCount');
      if (countEl) countEl.textContent = parseInt(countEl.textContent || '0', 10) + 1;
      ptaskSelChanged();
      updateAiCount();
      return;
    }

    row.dataset.status = t.status;
    row.className      = 'ptask-row ptask-row--' + t.status;

    var btn  = row.querySelector('.ptask-toggle');
    btn.textContent  = ICONS[t.status];
    btn.style.color  = COLORS[t.status];

    var badge = row.querySelector('.ptask-badge');
    if (t.status !== 'empty') {
      if (!badge) {
        badge = document.createElement('span');
        row.querySelector('.ptask-meta').insertAdjacentElement('afterbegin', badge);
      }
      badge.className   = 'ptask-badge ptask-badge--' + t.status;
      badge.textContent = LABELS[t.status];
    } else if (badge) {
      badge.remove();
    }

    if (t.note) {
      var noteEl = row.querySelector('.ptask-note');
      if (!noteEl) {
        noteEl = document.createElement('span');
        noteEl.className = 'ptask-note';
        row.querySelector('.ptask-meta').appendChild(noteEl);
      }
      noteEl.textContent = t.note;
    }
    updateAiCount();
  }

  window.ptaskCopyAi = function() {
    var rows = document.querySelectorAll('.ptask-row[data-status="trigger_ai"]');
    if (!rows.length) return;
    var lines = [];
    rows.forEach(function(row) {
      var id    = row.dataset.id;
      var title = (row.querySelector('.ptask-title') || {}).textContent || '';
      var desc  = (row.querySelector('.ptask-desc')  || {}).textContent || '';
      lines.push('TRIGGER AI — Task #' + id + ': ' + title.trim());
      if (desc.trim()) lines.push(desc.trim());
      lines.push('');
    });
    var text = lines.join('\n').trimEnd();
    var done = function() {
      var btn = document.getElementById('ptaskCopyAiBtn');
      if (!btn) return;
      var orig = btn.innerHTML;
      btn.innerHTML = '✓ Copied';
      setTimeout(function(){ btn.innerHTML = orig; }, 1500);
    };
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var t = document.createElement('textarea');
      t.value = text; document.body.appendChild(t); t.select();
      document.execCommand('copy'); document.body.removeChild(t);
      done();
    }
  };

  window.ptaskCycle = function(id) {
    fetch(BASE + '/settings/tasks/' + id + '/status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_token=' + encodeURIComponent(CSRF)
    })
    .then(function(r){ return r.json(); })
    .then(function(res) {
      if (res.success) res.tasks.forEach(applyTask);
    });
  };

  window.ptaskBatch = function(status) {
    var ids = Array.from(document.querySelectorAll('.ptask-selbox:checked'))
                   .map(function(cb){ return cb.closest('.ptask-row').dataset.id; });
    if (!ids.length) return;
    fetch(BASE + '/settings/tasks/batch', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_token=' + encodeURIComponent(CSRF) + '&status=' + encodeURIComponent(status) + '&ids=' + encodeURIComponent(JSON.stringify(ids))
    })
    .then(function(r){ return r.json(); })
    .then(function(res) {
      if (res.success) {
        res.tasks.forEach(applyTask);
        ptaskDeselectAll();
      }
    });
  };

  window.ptaskSelChanged = function() {
    var n = document.querySelectorAll('.ptask-selbox:checked').length;
    document.getElementById('ptaskSelCount').textContent = n;
    document.getElementById('ptaskBatchBar').classList.toggle('is-visible', n > 0);
    document.getElementById('ptaskSelAll').indeterminate =
      n > 0 && n < document.querySelectorAll('.ptask-selbox').length;
    document.getElementById('ptaskSelAll').checked = n === document.querySelectorAll('.ptask-selbox').length;
  };

  window.ptaskToggleAll = function(checked) {
    document.querySelectorAll('.ptask-selbox').forEach(function(cb){ cb.checked = checked; });
    ptaskSelChanged();
  };

  window.ptaskDeselectAll = function() {
    document.querySelectorAll('.ptask-selbox').forEach(function(cb){ cb.checked = false; });
    document.getElementById('ptaskSelAll').checked = false;
    ptaskSelChanged();
  };

  window.ptaskToggleArchive = function() {
    var body    = document.getElementById('ptaskArchiveBody');
    var chevron = document.getElementById('ptaskArchiveChevron');
    var open    = body.classList.toggle('open');
    chevron.classList.toggle('open', open);
  };
}());

// ── Future Steps ──────────────────────────────────────────────────────────
(function () {
  var CSRF = <?= json_encode($CSRF) ?>;
  var BASE = window.APP_BASE || '';

  // Raw step data keyed by id (used for copy)
  var _stepData = {};
  <?php foreach ($steps as $st): ?>
  _stepData[<?= (int)$st['id'] ?>] = <?= json_encode(['zone' => $st['zone'] ?? null, 'content' => $st['content'] ?? '']) ?>;
  <?php endforeach; ?>

  window.fstepToggleSection = function() {
    var body = document.getElementById('fstepSectionBody');
    var btn  = document.getElementById('fstepToggleBtn');
    var open = body.classList.toggle('open');
    if (btn) btn.textContent = open ? '▼' : '▶';
  };

  function post(url, body) {
    return fetch(BASE + url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_token=' + encodeURIComponent(CSRF) + (body ? '&' + body : '')
    }).then(function(r){ return r.json(); });
  }

  function renderRow(st) {
    var zone  = st.zone ? '<span class="fstep-zone">'+escHtml(st.zone)+'</span><br>' : '';
    var body  = nl2brEsc(st.content || '');
    var div = document.createElement('div');
    div.className = 'fstep-row';
    div.dataset.id = st.id;
    div.id = 'fstep' + st.id;
    div.draggable = true;
    div.innerHTML = '<span class="fstep-drag" draggable="false">⠿</span>'
      + '<div class="fstep-body">' + zone + '<div class="fstep-content">' + body + '</div></div>'
      + '<div class="fstep-actions">'
      +   '<button type="button" class="fstep-btn" title="Edit" onclick="fstepEdit(' + st.id + ')">✏️</button>'
      +   '<button type="button" class="fstep-btn" title="Copy" onclick="fstepCopy(' + st.id + ')">📋</button>'
      +   '<button type="button" class="fstep-btn" title="Delete" onclick="fstepDelete(' + st.id + ')">✕</button>'
      + '</div>';
    _stepData[st.id] = { zone: st.zone || null, content: st.content || '' };
    initDrag(div);
    return div;
  }

  function escHtml(s) { var d=document.createElement('div');d.textContent=s;return d.innerHTML; }
  function nl2brEsc(s) { return escHtml(s).replace(/\n/g,'<br>'); }

  var _editingId = null;

  window.fstepSave = function() {
    var ta  = document.getElementById('fstepInput');
    var val = ta.value.trim();
    if (!val) return;

    if (_editingId) {
      post('/settings/future-steps/' + _editingId + '/update', 'content=' + encodeURIComponent(val))
      .then(function(res) {
        if (!res.success) return;
        var old = document.getElementById('fstep' + _editingId);
        if (old) { var fresh = renderRow(res.step); old.replaceWith(fresh); }
        fstepCancelEdit();
      });
    } else {
      post('/settings/future-steps', 'content=' + encodeURIComponent(val))
      .then(function(res) {
        if (!res.success) return;
        var list = document.getElementById('fstepList');
        var empty = document.getElementById('fstepEmpty');
        if (empty) empty.remove();
        list.appendChild(renderRow(res.step));
        ta.value = '';
        // update count badge
        var countEl = document.getElementById('fstepSectionCount');
        if (countEl) countEl.textContent = parseInt(countEl.textContent || '0', 10) + 1;
      });
    }
  };

  window.fstepEdit = function(id) {
    var st = _stepData[id];
    if (!st) return;
    var ta = document.getElementById('fstepInput');
    ta.value = st.zone ? '(' + st.zone + ') ' + st.content : st.content;
    _editingId = id;
    document.getElementById('fstepSaveBtn').textContent = 'Save changes';
    document.getElementById('fstepCancelBtn').style.display = '';
    ta.focus();
    ta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  };

  window.fstepCancelEdit = function() {
    _editingId = null;
    document.getElementById('fstepInput').value = '';
    document.getElementById('fstepSaveBtn').textContent = 'Log future step';
    document.getElementById('fstepCancelBtn').style.display = 'none';
  };

  window.fstepDelete = function(id) {
    post('/settings/future-steps/' + id + '/delete')
    .then(function(res) {
      if (!res.success) return;
      var el = document.getElementById('fstep' + id);
      if (el) el.remove();
      delete _stepData[id];
      if (!document.querySelector('.fstep-row')) {
        var empty = document.createElement('div');
        empty.className = 'fstep-empty'; empty.id = 'fstepEmpty'; empty.textContent = 'No future steps yet.';
        document.getElementById('fstepList').appendChild(empty);
      }
      var countEl = document.getElementById('fstepSectionCount');
      if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent || '0', 10) - 1);
    });
  };

  window.fstepCopy = function(id) {
    var st = _stepData[id];
    if (!st) return;
    var text = st.zone ? '(' + st.zone + ') ' + st.content : st.content;
    navigator.clipboard ? navigator.clipboard.writeText(text) : (function(){ var t=document.createElement('textarea');t.value=text;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t); })();
  };

  // Drag-to-reorder
  var _dragSrc = null;
  function initDrag(el) {
    el.addEventListener('dragstart', function(e) {
      _dragSrc = el; e.dataTransfer.effectAllowed = 'move';
      setTimeout(function(){ el.classList.add('fstep-row--dragging'); }, 0);
    });
    el.addEventListener('dragend', function() {
      el.classList.remove('fstep-row--dragging');
      document.querySelectorAll('.fstep-row').forEach(function(r){ r.classList.remove('fstep-row--drag-over'); });
    });
    el.addEventListener('dragover', function(e) {
      if (!_dragSrc || _dragSrc === el) return;
      e.preventDefault();
      document.querySelectorAll('.fstep-row').forEach(function(r){ r.classList.remove('fstep-row--drag-over'); });
      el.classList.add('fstep-row--drag-over');
    });
    el.addEventListener('dragleave', function() { el.classList.remove('fstep-row--drag-over'); });
    el.addEventListener('drop', function(e) {
      e.preventDefault(); e.stopPropagation();
      el.classList.remove('fstep-row--drag-over');
      if (!_dragSrc || _dragSrc === el) return;
      var list = document.getElementById('fstepList');
      var rows = Array.from(list.querySelectorAll('.fstep-row[data-id]'));
      var si = rows.indexOf(_dragSrc), di = rows.indexOf(el);
      if (si === -1 || di === -1) return;
      if (si < di) list.insertBefore(_dragSrc, el.nextSibling);
      else         list.insertBefore(_dragSrc, el);
      var ids = Array.from(list.querySelectorAll('.fstep-row[data-id]')).map(function(r){ return r.dataset.id; });
      post('/settings/future-steps/reorder', 'ids=' + encodeURIComponent(JSON.stringify(ids)));
    });
  }
  document.querySelectorAll('#fstepList .fstep-row[data-id]').forEach(initDrag);
}());
</script>
