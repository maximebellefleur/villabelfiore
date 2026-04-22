<?php
function taskTagColor(string $tag): string {
    $tag = strtoupper(trim($tag));
    $hash = 0;
    for ($i = 0; $i < strlen($tag); $i++) $hash = (($hash * 31) + ord($tag[$i])) & 0x7fffffff;
    $p = ['#2d6a4f','#4338ca','#c05621','#0369a1','#7e22ce','#0f766e','#be123c','#6b21a8','#1e40af','#854d0e','#065f46','#9d174d'];
    return $p[$hash % count($p)];
}
$csrfToken = \App\Support\CSRF::getToken();
?>
<style>
.tasks-page { max-width: 680px; margin: 0 auto; }
.tasks-tabs { display:flex;gap:0;margin-bottom:var(--spacing-4);background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius-lg);padding:4px;overflow-x:auto; }
.tasks-tab { flex:1;text-align:center;padding:8px 10px;border-radius:var(--radius);font-size:.8rem;font-weight:600;cursor:pointer;color:var(--color-text-muted);text-decoration:none;white-space:nowrap;transition:background .15s,color .15s;border:none;background:none; }
.tasks-tab.active { background:var(--color-primary);color:#fff; }
.task-quick-bar { display:flex;align-items:center;gap:10px;background:var(--color-surface-raised);border:1.5px solid var(--color-border);border-radius:var(--radius-lg);padding:10px 14px;margin-bottom:var(--spacing-3);transition:border-color .15s,box-shadow .15s; }
.task-quick-bar:focus-within { border-color:var(--color-primary);box-shadow:0 0 0 3px var(--color-primary-soft); }
.task-quick-plus { width:22px;height:22px;border-radius:6px;border:2px dashed var(--color-border);background:none;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:1rem;pointer-events:none; }
.task-quick-input { flex:1;border:none;background:none;outline:none;font-size:.95rem;font-family:inherit;color:var(--color-text);min-width:0; }
.task-quick-input::placeholder { color:var(--color-text-muted); }
.task-quick-hint { font-size:.7rem;color:var(--color-text-muted);flex-shrink:0;white-space:nowrap; }
.task-tag { display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#fff;white-space:nowrap;flex-shrink:0;line-height:1.6; }
.task-title-row { display:flex;align-items:center;gap:6px;flex-wrap:wrap; }
.task-list { display:flex;flex-direction:column;gap:2px; }
.task-row { display:flex;align-items:flex-start;gap:8px;padding:10px 12px;background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius);transition:opacity .2s,background .15s;cursor:default; }
.task-row:hover { background:var(--color-surface); }
.task-row.done { opacity:.5; }
.task-row--important { border-left:3px solid #f59e0b;background:#fffbeb; }
.task-row.dragging { opacity:.3; }
.task-row.drag-over { box-shadow:0 -2px 0 var(--color-primary); }
.task-drag-handle { cursor:grab;color:var(--color-text-muted);padding:2px 3px;font-size:.9rem;user-select:none;flex-shrink:0;opacity:.4;line-height:1;margin-top:3px; }
.task-drag-handle:hover { opacity:1; }
.task-checkbox { width:22px;height:22px;border-radius:6px;border:2px solid var(--color-border);background:#fff;flex-shrink:0;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;transition:border-color .15s,background .15s;margin-top:1px; }
.task-checkbox.checked { background:var(--color-primary);border-color:var(--color-primary);color:#fff; }
.task-body { flex:1;min-width:0; }
.task-title { font-size:.9rem;font-weight:500;color:var(--color-text);word-break:break-word;line-height:1.4; }
.task-title.done-text { text-decoration:line-through;color:var(--color-text-muted); }
.task-meta { display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;align-items:center; }
.task-due { font-size:.68rem;color:var(--color-text-muted); }
.task-due.overdue { color:#dc2626;font-weight:600; }
.task-notes { font-size:.75rem;color:var(--color-text-muted);margin-top:3px; }
.task-actions { display:flex;gap:2px;flex-shrink:0;align-items:flex-start;margin-top:1px; }
.task-act-btn { background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:var(--radius);color:var(--color-text-muted);font-size:.8rem;line-height:1;transition:background .12s,color .12s; }
.task-act-btn:hover { background:var(--color-border);color:var(--color-text); }
.task-imp-btn { background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:var(--radius);font-size:.85rem;line-height:1;color:var(--color-text-muted);transition:background .12s; }
.task-imp-btn.active { color:#f59e0b; }
.task-imp-btn:hover { background:var(--color-border); }
.task-section-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3); }
.task-section-title { font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted); }
.task-empty { text-align:center;padding:var(--spacing-6);color:var(--color-text-muted);font-size:.88rem;background:var(--color-surface-raised);border:1px dashed var(--color-border);border-radius:var(--radius-lg); }
.achat-group-head { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);padding:10px 12px 4px;display:flex;align-items:center;gap:6px; }
.reminder-row { display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--color-surface-raised);border:1px solid var(--color-border);border-radius:var(--radius);margin-bottom:2px; }
.reminder-dot { width:8px;height:8px;border-radius:50%;background:var(--color-primary);flex-shrink:0; }
.reminder-dot.overdue { background:#dc2626; }
</style>

<div class="tasks-page">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-4);flex-wrap:wrap;gap:8px">
    <h1 style="font-size:1.5rem;font-weight:800;margin:0">Tasks</h1>
    <?php if ($archiveCount > 0): ?>
    <a href="<?= url('/tasks/archive') ?>" style="font-size:.78rem;color:var(--color-text-muted);text-decoration:none">Archive (<?= $archiveCount ?>)</a>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<div class="tasks-tabs">
    <a href="<?= url('/tasks?tab=todos') . ($showDone ? '&done=1' : '') ?>" class="tasks-tab <?= $tab === 'todos'      ? 'active' : '' ?>">✅ To-Do</a>
    <a href="<?= url('/tasks?tab=achats') ?>"                                 class="tasks-tab <?= $tab === 'achats'     ? 'active' : '' ?>">🛒 Achats</a>
    <a href="<?= url('/tasks?tab=reminders') ?>"                              class="tasks-tab <?= $tab === 'reminders'  ? 'active' : '' ?>">🔔 Reminders</a>
    <a href="<?= url('/tasks?tab=irrigation') ?>"                             class="tasks-tab <?= $tab === 'irrigation' ? 'active' : '' ?>">💧 Irrigation</a>
</div>

<?php /* ===================== TO-DO TAB ===================== */ if ($tab === 'todos'): ?>

<div class="task-quick-bar" id="taskQuickBar">
    <div class="task-quick-plus">+</div>
    <span id="taskTagPreview" style="display:none"></span>
    <input type="text" class="task-quick-input" id="taskQuickInput"
           placeholder="(TAG) task title… then Enter"
           autocomplete="off" spellcheck="true">
    <span class="task-quick-hint" id="taskQuickHint" style="display:none">↵ save</span>
</div>

<div class="task-section-head">
    <span class="task-section-title" id="taskCounter"><?= count($tasks) ?> task<?= count($tasks) !== 1 ? 's' : '' ?></span>
    <a href="<?= url('/tasks?tab=todos' . ($showDone ? '' : '&done=1')) ?>"
       style="font-size:.75rem;color:var(--color-text-muted);text-decoration:none">
        <?= $showDone ? 'Hide completed' : 'Show completed' ?>
    </a>
</div>

<?php if (empty($tasks)): ?>
<div class="task-empty"><div style="font-size:2rem;margin-bottom:8px">✅</div>
<?= $showDone ? 'No tasks yet.' : 'All done! No pending tasks.' ?></div>
<?php else: ?>
<div class="task-list" id="taskList">
<?php foreach ($tasks as $t):
    $isDone    = (bool)$t['is_done'];
    $isImp     = (bool)$t['is_important'];
    $isOverdue = !$isDone && !empty($t['due_date']) && strtotime($t['due_date']) < mktime(0,0,0,(int)date('n'),(int)date('j'),(int)date('Y'));
?>
<div class="task-row <?= $isDone ? 'done' : '' ?> <?= $isImp ? 'task-row--important' : '' ?>"
     id="taskRow<?= $t['id'] ?>" data-id="<?= $t['id'] ?>" draggable="true">
    <span class="task-drag-handle" title="Drag to reorder">⠿</span>
    <button class="task-checkbox <?= $isDone ? 'checked' : '' ?>"
            onclick="toggleTask(<?= $t['id'] ?>, this)"><?= $isDone ? '✓' : '' ?></button>
    <div class="task-body">
        <div class="task-title-row">
            <?php if (!empty($t['category'])): ?>
            <span class="task-tag" style="background:<?= taskTagColor($t['category']) ?>"><?= e(strtoupper($t['category'])) ?></span>
            <?php endif; ?>
            <span class="task-title <?= $isDone ? 'done-text' : '' ?>"><?= e($t['title']) ?></span>
        </div>
        <?php if (!empty($t['due_date'])): ?>
        <div class="task-meta">
            <span class="task-due <?= $isOverdue ? 'overdue' : '' ?>"><?= $isOverdue ? '⚠ ' : '' ?><?= e(date('d M', strtotime($t['due_date']))) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($t['notes'])): ?><div class="task-notes"><?= e($t['notes']) ?></div><?php endif; ?>
    </div>
    <div class="task-actions">
        <button class="task-imp-btn <?= $isImp ? 'active' : '' ?>" title="Important" onclick="toggleImportant(<?= $t['id'] ?>, this)">⭐</button>
        <button class="task-act-btn" title="Archive" onclick="archiveTask(<?= $t['id'] ?>, this)">📦</button>
        <button class="task-act-btn" title="Delete" style="color:#dc3545" onclick="deleteTask(<?= $t['id'] ?>, this)">✕</button>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php /* ===================== ACHATS TAB ===================== */ elseif ($tab === 'achats'): ?>

<div class="task-quick-bar" id="achatQuickBar">
    <div class="task-quick-plus">+</div>
    <span id="achatTagPreview" style="display:none"></span>
    <input type="text" class="task-quick-input" id="achatQuickInput"
           placeholder="(STORE) item to buy… then Enter"
           autocomplete="off" spellcheck="true">
    <span class="task-quick-hint" id="achatQuickHint" style="display:none">↵ save</span>
</div>

<div class="task-section-head">
    <span class="task-section-title"><?= $achatsTotal ?> item<?= $achatsTotal !== 1 ? 's' : '' ?></span>
</div>

<?php if (empty($achats)): ?>
<div class="task-empty"><div style="font-size:2rem;margin-bottom:8px">🛒</div>Nothing to buy yet. Type above to add.</div>
<?php else: ?>
<div id="achatList">
<?php foreach ($achats as $catKey => $catItems):
    $label = $catKey !== '__none__' ? strtoupper($catKey) : null;
    $color = $label ? taskTagColor($catKey) : null;
?>
<div class="achat-group" data-cat="<?= e($catKey) ?>">
    <div class="achat-group-head">
        <?php if ($label): ?>
        <span class="task-tag" style="background:<?= $color ?>"><?= e($label) ?></span>
        <?php else: ?>
        <span style="color:var(--color-text-muted)">No category</span>
        <?php endif; ?>
    </div>
    <div class="task-list achat-group-items">
    <?php foreach ($catItems as $a): ?>
    <div class="task-row <?= $a['is_done'] ? 'done' : '' ?>" id="taskRow<?= $a['id'] ?>" data-id="<?= $a['id'] ?>">
        <button class="task-checkbox <?= $a['is_done'] ? 'checked' : '' ?>"
                onclick="toggleTask(<?= $a['id'] ?>, this)"><?= $a['is_done'] ? '✓' : '' ?></button>
        <div class="task-body">
            <div class="task-title-row">
                <span class="task-title <?= $a['is_done'] ? 'done-text' : '' ?>"><?= e($a['title']) ?></span>
            </div>
        </div>
        <div class="task-actions">
            <button class="task-act-btn" title="Delete" style="color:#dc3545" onclick="deleteTask(<?= $a['id'] ?>, this)">✕</button>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php /* ===================== REMINDERS TAB ===================== */ elseif ($tab === 'reminders'): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3)">
    <span class="task-section-title"><?= count($reminders) ?> pending</span>
    <a href="<?= url('/reminders') ?>" class="btn btn-primary btn-sm">+ Add Reminder</a>
</div>
<?php if (empty($reminders)): ?>
<div class="task-empty"><div style="font-size:2rem;margin-bottom:8px">🔔</div>No pending reminders.
<div style="margin-top:10px"><a href="<?= url('/reminders') ?>" class="btn btn-primary btn-sm">Go to Reminders</a></div></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:2px">
<?php foreach ($reminders as $r):
    $isOD = strtotime($r['due_at']) < time();
?>
<div class="reminder-row">
    <div class="reminder-dot <?= $isOD ? 'overdue' : '' ?>"></div>
    <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:.88rem"><?= e($r['title']) ?></div>
        <?php if (!empty($r['item_name'])): ?>
        <div style="font-size:.72rem;font-style:italic;color:var(--color-text-muted)"><?= e($r['item_name']) ?></div>
        <?php endif; ?>
        <div style="font-size:.72rem;color:<?= $isOD ? '#dc2626' : 'var(--color-text-muted)' ?>">
            <?= $isOD ? '⚠ Overdue · ' : '' ?><?= e(date('d M Y, H:i', strtotime($r['due_at']))) ?>
        </div>
    </div>
    <div style="display:flex;gap:4px;flex-shrink:0">
        <form method="POST" action="<?= url('/reminders/' . (int)$r['id'] . '/complete') ?>" style="display:inline">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <button type="submit" class="task-act-btn" title="Complete">✓</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<div style="margin-top:var(--spacing-3);text-align:center">
    <a href="<?= url('/reminders') ?>" style="font-size:.8rem;color:var(--color-primary);font-weight:600;text-decoration:none">Full Reminders page →</a>
</div>
<?php endif; ?>

<?php /* ===================== IRRIGATION TAB ===================== */ elseif ($tab === 'irrigation'): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3)">
    <span class="task-section-title"><?= count($irrigationPlans) ?> plan<?= count($irrigationPlans) !== 1 ? 's' : '' ?></span>
    <a href="<?= url('/irrigation') ?>" style="font-size:.78rem;color:var(--color-primary);font-weight:600;text-decoration:none">All plans →</a>
</div>
<?php if (empty($irrigationPlans)): ?>
<div class="task-empty"><div style="font-size:2rem;margin-bottom:8px">💧</div>No irrigation plans yet.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:2px">
<?php foreach ($irrigationPlans as $ip):
    $isActive   = $ip['start_date'] <= date('Y-m-d') && (empty($ip['end_date']) || $ip['end_date'] >= date('Y-m-d'));
    $doneToday  = !empty($ip['last_done_date']) && $ip['last_done_date'] === date('Y-m-d');
?>
<div class="reminder-row" style="<?= $doneToday ? 'opacity:.5' : '' ?>">
    <span style="font-size:1.1rem">💧</span>
    <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:.88rem">
            <a href="<?= url('/items/' . (int)$ip['item_id']) ?>" style="color:inherit;text-decoration:none"><?= e($ip['item_name']) ?></a>
        </div>
        <div style="font-size:.72rem;color:var(--color-text-muted)">
            <?= e(\App\Controllers\IrrigationController::intervalLabel($ip['interval_type'])) ?>
            <?php if (!empty($ip['quantity_liters'])): ?>· <?= (float)$ip['quantity_liters'] ?>L<?php endif; ?>
        </div>
    </div>
    <?php if ($doneToday): ?><span style="font-size:.65rem;font-weight:700;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:999px">Done today</span>
    <?php elseif ($isActive): ?><span style="font-size:.65rem;font-weight:700;color:#15803d;background:#f0fdf4;padding:2px 8px;border-radius:999px">Active</span>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<div style="margin-top:var(--spacing-3);text-align:center">
    <a href="<?= url('/irrigation') ?>" style="font-size:.8rem;color:var(--color-primary);font-weight:600;text-decoration:none">Full Irrigation page →</a>
</div>
<?php endif; ?>

<?php endif; ?>
</div><!-- .tasks-page -->

<script>
var CSRF = '<?= e($csrfToken) ?>';
var BASE = '<?= url('/') ?>';

function tagColor(tag) {
    tag = tag.toUpperCase().trim();
    var h = 0;
    for (var i = 0; i < tag.length; i++) h = ((h * 31) + tag.charCodeAt(i)) & 0x7fffffff;
    var p = ['#2d6a4f','#4338ca','#c05621','#0369a1','#7e22ce','#0f766e','#be123c','#6b21a8','#1e40af','#854d0e','#065f46','#9d174d'];
    return p[h % p.length];
}
function parseTag(val) {
    var m = val.match(/^\(([^)]+)\)\s*/);
    return m ? { tag: m[1].trim().toUpperCase(), title: val.slice(m[0].length).trim() } : { tag: '', title: val.trim() };
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ---- To-Do quick-add ---- */
(function () {
    var input = document.getElementById('taskQuickInput');
    var hint  = document.getElementById('taskQuickHint');
    var prev  = document.getElementById('taskTagPreview');
    if (!input) return;
    input.addEventListener('input', function () {
        hint.style.display = input.value.trim() ? 'inline' : 'none';
        var p = parseTag(input.value);
        if (p.tag) { prev.style.display='inline-flex'; prev.innerHTML='<span class="task-tag" style="background:'+tagColor(p.tag)+'">'+escHtml(p.tag)+'</span>'; }
        else { prev.style.display='none'; prev.innerHTML=''; }
    });
    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var raw = input.value.trim(); if (!raw) return;
        var p = parseTag(raw); if (!p.title) return;
        input.disabled = true;
        fetch(BASE + 'tasks', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body:'_token='+encodeURIComponent(CSRF)+'&title='+encodeURIComponent(p.title)+'&category='+encodeURIComponent(p.tag)+'&list_type=todo'
        }).then(function(r){return r.json();}).then(function(d){
            input.disabled = false;
            if (!d.success) { input.focus(); return; }
            input.value=''; hint.style.display='none'; prev.style.display='none'; prev.innerHTML=''; input.focus();
            var list = document.getElementById('taskList');
            var empty = document.querySelector('.task-empty');
            if (empty) empty.remove();
            if (!list) { list=document.createElement('div'); list.className='task-list'; list.id='taskList'; document.getElementById('taskQuickBar').after(document.querySelector('.task-section-head'), list); }
            var row = buildTodoRow(d.task);
            row.style.opacity='0'; list.insertBefore(row, list.firstChild);
            requestAnimationFrame(function(){ row.style.transition='opacity .2s'; row.style.opacity='1'; });
            initDrag(row);
            var ctr = document.getElementById('taskCounter');
            if (ctr) { var n=list.querySelectorAll('.task-row').length; ctr.textContent=n+' task'+(n!==1?'s':''); }
        }).catch(function(){ input.disabled=false; input.focus(); });
    });
    function buildTodoRow(t) {
        var tagHtml = t.category ? '<span class="task-tag" style="background:'+tagColor(t.category)+'">'+escHtml(t.category.toUpperCase())+'</span>' : '';
        var el = document.createElement('div');
        el.className = 'task-row'; el.id = 'taskRow'+t.id; el.dataset.id = t.id; el.draggable = true;
        el.innerHTML = '<span class="task-drag-handle" title="Drag to reorder">⠿</span>'
            +'<button class="task-checkbox" onclick="toggleTask('+t.id+', this)"></button>'
            +'<div class="task-body"><div class="task-title-row">'+tagHtml+'<span class="task-title">'+escHtml(t.title)+'</span></div></div>'
            +'<div class="task-actions">'
            +'<button class="task-imp-btn" title="Important" onclick="toggleImportant('+t.id+', this)">⭐</button>'
            +'<button class="task-act-btn" title="Archive" onclick="archiveTask('+t.id+', this)">📦</button>'
            +'<button class="task-act-btn" title="Delete" style="color:#dc3545" onclick="deleteTask('+t.id+', this)">✕</button>'
            +'</div>';
        return el;
    }
}());

/* ---- Achats quick-add ---- */
(function () {
    var input = document.getElementById('achatQuickInput');
    var hint  = document.getElementById('achatQuickHint');
    var prev  = document.getElementById('achatTagPreview');
    if (!input) return;
    input.addEventListener('input', function () {
        hint.style.display = input.value.trim() ? 'inline' : 'none';
        var p = parseTag(input.value);
        if (p.tag) { prev.style.display='inline-flex'; prev.innerHTML='<span class="task-tag" style="background:'+tagColor(p.tag)+'">'+escHtml(p.tag)+'</span>'; }
        else { prev.style.display='none'; prev.innerHTML=''; }
    });
    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var raw = input.value.trim(); if (!raw) return;
        var p = parseTag(raw); if (!p.title) return;
        input.disabled = true;
        fetch(BASE + 'tasks', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body:'_token='+encodeURIComponent(CSRF)+'&title='+encodeURIComponent(p.title)+'&category='+encodeURIComponent(p.tag)+'&list_type=achat'
        }).then(function(r){return r.json();}).then(function(d){
            input.disabled=false;
            if (!d.success) { input.focus(); return; }
            input.value=''; hint.style.display='none'; prev.style.display='none'; prev.innerHTML=''; input.focus();
            // Find or create group
            var catKey = p.tag || '__none__';
            var list   = document.getElementById('achatList');
            var empty  = document.querySelector('.task-empty');
            if (empty) { empty.remove(); list=document.createElement('div'); list.id='achatList'; empty.parentNode.appendChild(list); }
            var group = list ? list.querySelector('[data-cat="'+CSS.escape(catKey)+'"]') : null;
            if (!group) {
                group = document.createElement('div');
                group.className='achat-group'; group.dataset.cat=catKey;
                var lbl = p.tag ? '<span class="task-tag" style="background:'+tagColor(p.tag)+'">'+escHtml(p.tag)+'</span>' : '<span style="color:var(--color-text-muted)">No category</span>';
                group.innerHTML='<div class="achat-group-head">'+lbl+'</div><div class="task-list achat-group-items"></div>';
                if (list) list.appendChild(group);
            }
            var items = group.querySelector('.achat-group-items');
            var row   = document.createElement('div');
            row.className='task-row'; row.id='taskRow'+d.task.id; row.dataset.id=d.task.id;
            row.innerHTML='<button class="task-checkbox" onclick="toggleTask('+d.task.id+', this)"></button>'
                +'<div class="task-body"><div class="task-title-row"><span class="task-title">'+escHtml(d.task.title)+'</span></div></div>'
                +'<div class="task-actions"><button class="task-act-btn" title="Delete" style="color:#dc3545" onclick="deleteTask('+d.task.id+', this)">✕</button></div>';
            row.style.opacity='0'; items.appendChild(row);
            requestAnimationFrame(function(){ row.style.transition='opacity .2s'; row.style.opacity='1'; });
        }).catch(function(){ input.disabled=false; input.focus(); });
    });
}());

/* ---- Toggle done ---- */
function toggleTask(id, btn) {
    fetch(BASE+'tasks/'+id+'/toggle', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'_token='+encodeURIComponent(CSRF) })
    .then(function(r){return r.json();}).then(function(d){
        if (!d.success) return;
        var row = document.getElementById('taskRow'+id);
        var title = row.querySelector('.task-title');
        if (d.is_done) { btn.classList.add('checked'); btn.textContent='✓'; row.classList.add('done'); if(title)title.classList.add('done-text'); }
        else           { btn.classList.remove('checked'); btn.textContent=''; row.classList.remove('done'); if(title)title.classList.remove('done-text'); }
    });
}

/* ---- Toggle important ---- */
function toggleImportant(id, btn) {
    fetch(BASE+'tasks/'+id+'/important', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'_token='+encodeURIComponent(CSRF) })
    .then(function(r){return r.json();}).then(function(d){
        if (!d.success) return;
        var row = document.getElementById('taskRow'+id);
        if (d.is_important) { btn.classList.add('active'); row.classList.add('task-row--important'); }
        else                { btn.classList.remove('active'); row.classList.remove('task-row--important'); }
    });
}

/* ---- Archive ---- */
function archiveTask(id, btn) {
    fetch(BASE+'tasks/'+id+'/archive', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'_token='+encodeURIComponent(CSRF)+'&ajax=1' })
    .then(function(r){return r.json();}).then(function(d){
        if (d.success) { var row=document.getElementById('taskRow'+id); row.style.opacity='0'; row.style.transition='opacity .25s'; setTimeout(function(){row.remove();},260); }
    });
}

/* ---- Delete ---- */
function deleteTask(id, btn) {
    if (!confirm('Delete permanently?')) return;
    fetch(BASE+'tasks/'+id+'/delete', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'_token='+encodeURIComponent(CSRF)+'&ajax=1' })
    .then(function(r){return r.json();}).then(function(d){
        if (d.success) { var row=document.getElementById('taskRow'+id); row.style.opacity='0'; row.style.transition='opacity .2s'; setTimeout(function(){row.remove();},220); }
    });
}

/* ---- Drag-to-reorder ---- */
var _dragSrc = null;
function initDrag(el) {
    el.addEventListener('dragstart', function(e) {
        _dragSrc = el;
        e.dataTransfer.effectAllowed = 'move';
        el.classList.add('dragging');
    });
    el.addEventListener('dragend', function() {
        el.classList.remove('dragging');
        document.querySelectorAll('.task-row').forEach(function(r){ r.classList.remove('drag-over'); });
    });
    el.addEventListener('dragover', function(e) {
        e.preventDefault(); e.dataTransfer.dropEffect='move';
        document.querySelectorAll('.task-row').forEach(function(r){ r.classList.remove('drag-over'); });
        el.classList.add('drag-over');
    });
    el.addEventListener('drop', function(e) {
        e.stopPropagation();
        if (_dragSrc && _dragSrc !== el) {
            var list = el.parentNode;
            var rows = Array.from(list.querySelectorAll('.task-row[data-id]'));
            var si   = rows.indexOf(_dragSrc);
            var di   = rows.indexOf(el);
            if (si < di) list.insertBefore(_dragSrc, el.nextSibling);
            else         list.insertBefore(_dragSrc, el);
            saveOrder(list);
        }
        el.classList.remove('drag-over');
    });
}
function saveOrder(list) {
    var ids = Array.from(list.querySelectorAll('.task-row[data-id]')).map(function(r){ return r.dataset.id; });
    fetch(BASE+'tasks/reorder', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'_token='+encodeURIComponent(CSRF)+'&ids='+encodeURIComponent(JSON.stringify(ids)) });
}
document.querySelectorAll('#taskList .task-row[data-id]').forEach(initDrag);
</script>
