<?php
$categoryIcons = [
    'Building'    => '🏗',
    'Planting'    => '🌱',
    'Pruning'     => '✂️',
    'Harvest'     => '🌾',
    'Maintenance' => '🔧',
    'Watering'    => '💧',
    'Cleaning'    => '🧹',
    'Shopping'    => '🛒',
    'Other'       => '📌',
];
$suggestedCategories = array_keys($categoryIcons);
$csrfToken = \App\Support\CSRF::getToken();
?>
<style>
.tasks-page { max-width: 680px; margin: 0 auto; }

/* Tab bar */
.tasks-tabs {
    display: flex; gap: 0; margin-bottom: var(--spacing-4);
    background: var(--color-surface-raised); border: 1px solid var(--color-border);
    border-radius: var(--radius-lg); padding: 4px; overflow-x: auto;
}
.tasks-tab {
    flex: 1; text-align: center; padding: 8px 12px;
    border-radius: var(--radius); font-size: .82rem; font-weight: 600;
    cursor: pointer; color: var(--color-text-muted); text-decoration: none;
    white-space: nowrap; transition: background .15s, color .15s;
    border: none; background: none;
}
.tasks-tab.active {
    background: var(--color-primary); color: #fff;
}

/* Add task form */
.task-add-form {
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}
.task-add-toggle {
    display: flex; align-items: center; gap: 8px;
    font-weight: 700; font-size: .9rem; color: var(--color-primary);
    cursor: pointer; background: none; border: none; padding: 0;
}

/* Task list */
.task-list { display: flex; flex-direction: column; gap: 2px; }

.task-row {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 12px;
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    transition: opacity .2s, background .15s;
}
.task-row:hover { background: var(--color-surface); }
.task-row.done { opacity: .55; }

.task-checkbox {
    width: 22px; height: 22px; border-radius: 6px;
    border: 2px solid var(--color-border);
    background: #fff; flex-shrink: 0; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; transition: border-color .15s, background .15s;
    margin-top: 1px;
}
.task-checkbox.checked {
    background: var(--color-primary); border-color: var(--color-primary); color: #fff;
}

.task-body { flex: 1; min-width: 0; }
.task-title {
    font-size: .9rem; font-weight: 500; color: var(--color-text);
    word-break: break-word; line-height: 1.4;
}
.task-title.done-text {
    text-decoration: line-through; color: var(--color-text-muted);
}
.task-meta { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 4px; align-items: center; }
.task-category {
    font-size: .68rem; font-weight: 700; padding: 2px 8px;
    border-radius: 999px; background: var(--color-border);
    color: var(--color-text-muted); white-space: nowrap;
}
.task-due {
    font-size: .68rem; color: var(--color-text-muted);
}
.task-due.overdue { color: #dc2626; font-weight: 600; }
.task-notes { font-size: .75rem; color: var(--color-text-muted); margin-top: 3px; }

.task-actions {
    display: flex; gap: 4px; flex-shrink: 0; align-items: flex-start;
}
.task-act-btn {
    background: none; border: none; cursor: pointer;
    padding: 4px 6px; border-radius: var(--radius);
    color: var(--color-text-muted); font-size: .8rem; line-height: 1;
    transition: background .12s, color .12s;
}
.task-act-btn:hover { background: var(--color-border); color: var(--color-text); }

/* Section header */
.task-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: var(--spacing-3);
}
.task-section-title { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-muted); }

/* Empty state */
.task-empty {
    text-align: center; padding: var(--spacing-6);
    color: var(--color-text-muted); font-size: .88rem;
    background: var(--color-surface-raised);
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-lg);
}

/* Reminder row (tab 2) */
.reminder-row {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 14px;
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    margin-bottom: 2px;
}
.reminder-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary); flex-shrink: 0; }
.reminder-dot.overdue { background: #dc2626; }
</style>

<div class="tasks-page">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-4);flex-wrap:wrap;gap:8px">
    <h1 style="font-size:1.5rem;font-weight:800;margin:0">✅ Tasks</h1>
    <?php if ($archiveCount > 0): ?>
    <a href="<?= url('/tasks/archive') ?>" style="font-size:.78rem;color:var(--color-text-muted);text-decoration:none">📦 Archive (<?= $archiveCount ?>)</a>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- Tab bar -->
<div class="tasks-tabs">
    <a href="<?= url('/tasks?tab=todos') . ($showDone ? '&done=1' : '') ?>" class="tasks-tab <?= $tab === 'todos' ? 'active' : '' ?>">✅ To-Do</a>
    <a href="<?= url('/tasks?tab=reminders') ?>" class="tasks-tab <?= $tab === 'reminders' ? 'active' : '' ?>">🔔 Reminders</a>
    <a href="<?= url('/tasks?tab=irrigation') ?>" class="tasks-tab <?= $tab === 'irrigation' ? 'active' : '' ?>">💧 Irrigation</a>
</div>

<!-- ============================================================
     TAB 1: TO-DO LIST
     ============================================================ -->
<?php if ($tab === 'todos'): ?>

<!-- Add task form -->
<div class="task-add-form">
    <button type="button" class="task-add-toggle" onclick="document.getElementById('taskFormBody').style.display=document.getElementById('taskFormBody').style.display==='none'?'block':'none'">
        <span style="font-size:1.2rem">＋</span> Add a task
    </button>
    <div id="taskFormBody" style="display:none;margin-top:var(--spacing-3);border-top:1px solid var(--color-border);padding-top:var(--spacing-3)">
        <form method="POST" action="<?= url('/tasks') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="tab" value="todos">
            <div class="form-group" style="margin-bottom:var(--spacing-3)">
                <input type="text" name="title" class="form-input" placeholder="What needs to be done?" required autofocus style="font-size:.95rem">
            </div>
            <div class="form-row" style="margin-bottom:var(--spacing-3)">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" list="taskCategoryList" placeholder="e.g. Building, Planting…">
                    <datalist id="taskCategoryList">
                        <?php foreach ($suggestedCategories as $cat): ?>
                        <option value="<?= $cat ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Due date <small class="text-muted">(optional)</small></label>
                    <input type="date" name="due_date" class="form-input">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:var(--spacing-3)">
                <textarea name="notes" class="form-input" rows="2" placeholder="Notes (optional)"></textarea>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary btn-sm">Add Task</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('taskFormBody').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Toolbar: show/hide done -->
<div class="task-section-head">
    <span class="task-section-title"><?= count($tasks) ?> task<?= count($tasks) !== 1 ? 's' : '' ?></span>
    <a href="<?= url('/tasks?tab=todos' . ($showDone ? '' : '&done=1')) ?>"
       style="font-size:.75rem;color:var(--color-text-muted);text-decoration:none;display:flex;align-items:center;gap:4px">
        <span style="font-size:.85rem"><?= $showDone ? '👁' : '👁' ?></span>
        <?= $showDone ? 'Hide completed' : 'Show completed' ?>
    </a>
</div>

<!-- Task list -->
<?php if (empty($tasks)): ?>
<div class="task-empty">
    <div style="font-size:2rem;margin-bottom:8px">✅</div>
    <?= $showDone ? 'No tasks yet. Add one above.' : 'All done! No pending tasks.' ?>
</div>
<?php else: ?>
<div class="task-list" id="taskList">
    <?php foreach ($tasks as $t):
        $isDone   = (bool)$t['is_done'];
        $catIcon  = $categoryIcons[$t['category']] ?? '📌';
        $isOverdue = !$isDone && !empty($t['due_date']) && strtotime($t['due_date']) < mktime(0,0,0,(int)date('n'),(int)date('j'),(int)date('Y'));
    ?>
    <div class="task-row <?= $isDone ? 'done' : '' ?>" id="taskRow<?= $t['id'] ?>">
        <!-- Checkbox -->
        <button class="task-checkbox <?= $isDone ? 'checked' : '' ?>"
                onclick="toggleTask(<?= $t['id'] ?>, this)"
                title="<?= $isDone ? 'Mark undone' : 'Mark done' ?>">
            <?= $isDone ? '✓' : '' ?>
        </button>

        <!-- Body -->
        <div class="task-body">
            <div class="task-title <?= $isDone ? 'done-text' : '' ?>"><?= e($t['title']) ?></div>
            <div class="task-meta">
                <?php if (!empty($t['category'])): ?>
                <span class="task-category"><?= e($catIcon . ' ' . $t['category']) ?></span>
                <?php endif; ?>
                <?php if (!empty($t['due_date'])): ?>
                <span class="task-due <?= $isOverdue ? 'overdue' : '' ?>">
                    <?= $isOverdue ? '⚠ ' : '📅 ' ?><?= e(date('d M', strtotime($t['due_date']))) ?>
                </span>
                <?php endif; ?>
                <?php if ($isDone && !empty($t['done_at'])): ?>
                <span style="font-size:.65rem;color:var(--color-text-muted)">Done <?= e(date('d M', strtotime($t['done_at']))) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($t['notes'])): ?>
            <div class="task-notes"><?= e($t['notes']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="task-actions">
            <button class="task-act-btn" title="Archive" onclick="archiveTask(<?= $t['id'] ?>, this)">📦</button>
            <button class="task-act-btn" title="Delete" style="color:#dc3545" onclick="deleteTask(<?= $t['id'] ?>, this)">✕</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ============================================================
     TAB 2: REMINDERS
     ============================================================ -->
<?php if ($tab === 'reminders'): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3)">
    <span class="task-section-title"><?= count($reminders) ?> pending</span>
    <a href="<?= url('/reminders') ?>" class="btn btn-primary btn-sm">+ Add Reminder</a>
</div>

<?php if (empty($reminders)): ?>
<div class="task-empty">
    <div style="font-size:2rem;margin-bottom:8px">🔔</div>
    No pending reminders.
    <div style="margin-top:10px"><a href="<?= url('/reminders') ?>" class="btn btn-primary btn-sm">Go to Reminders</a></div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:2px">
    <?php foreach ($reminders as $r):
        $isOverdueR = strtotime($r['due_at']) < time();
    ?>
    <div class="reminder-row">
        <div class="reminder-dot <?= $isOverdueR ? 'overdue' : '' ?>"></div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:.88rem"><?= e($r['title']) ?></div>
            <?php if (!empty($r['item_name'])): ?>
            <div style="font-size:.72rem;font-style:italic;color:var(--color-text-muted);margin-top:1px"><?= e($r['item_name']) ?></div>
            <?php endif; ?>
            <div style="font-size:.72rem;color:<?= $isOverdueR ? '#dc2626' : 'var(--color-text-muted)' ?>">
                <?= $isOverdueR ? '⚠ Overdue · ' : '' ?><?= e(date('d M Y, H:i', strtotime($r['due_at']))) ?>
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
<?php endif; ?>

<!-- ============================================================
     TAB 3: IRRIGATION
     ============================================================ -->
<?php if ($tab === 'irrigation'): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3)">
    <span class="task-section-title"><?= count($irrigationPlans) ?> plan<?= count($irrigationPlans) !== 1 ? 's' : '' ?></span>
    <a href="<?= url('/irrigation') ?>" style="font-size:.78rem;color:var(--color-primary);font-weight:600;text-decoration:none">All plans →</a>
</div>

<?php if (empty($irrigationPlans)): ?>
<div class="task-empty">
    <div style="font-size:2rem;margin-bottom:8px">💧</div>
    No irrigation plans yet. Set them up on individual item pages.
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:2px">
    <?php foreach ($irrigationPlans as $ip):
        $isActive = $ip['start_date'] <= date('Y-m-d') && (empty($ip['end_date']) || $ip['end_date'] >= date('Y-m-d'));
        $doneTodayIrr = !empty($ip['last_done_date']) && $ip['last_done_date'] === date('Y-m-d');
    ?>
    <div class="reminder-row" style="<?= $doneTodayIrr ? 'opacity:.5' : '' ?>">
        <span style="font-size:1.1rem">💧</span>
        <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:.88rem">
                <a href="<?= url('/items/' . (int)$ip['item_id']) ?>" style="color:inherit;text-decoration:none"><?= e($ip['item_name']) ?></a>
            </div>
            <div style="font-size:.72rem;color:var(--color-text-muted)">
                <?= e(\App\Controllers\IrrigationController::intervalLabel($ip['interval_type'])) ?>
                <?php if (!empty($ip['quantity_liters'])): ?> · <?= (float)$ip['quantity_liters'] ?>L<?php endif; ?>
                <?php if (!empty($ip['end_date'])): ?> · until <?= e(date('d M Y', strtotime($ip['end_date']))) ?><?php endif; ?>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
            <?php if ($isActive && !$doneTodayIrr): ?>
            <span style="font-size:.65rem;font-weight:700;color:#15803d;background:#f0fdf4;padding:2px 8px;border-radius:999px">Active</span>
            <?php elseif ($doneTodayIrr): ?>
            <span style="font-size:.65rem;font-weight:700;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:999px">Done today</span>
            <?php endif; ?>
        </div>
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

function toggleTask(id, btn) {
    fetch(BASE + 'tasks/' + id + '/toggle', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(CSRF)
    }).then(r => r.json()).then(d => {
        if (!d.success) return;
        var row = document.getElementById('taskRow' + id);
        var title = row.querySelector('.task-title');
        if (d.is_done) {
            btn.classList.add('checked');
            btn.textContent = '✓';
            row.classList.add('done');
            title.classList.add('done-text');
            btn.title = 'Mark undone';
        } else {
            btn.classList.remove('checked');
            btn.textContent = '';
            row.classList.remove('done');
            title.classList.remove('done-text');
            btn.title = 'Mark done';
        }
    });
}

function archiveTask(id, btn) {
    if (!confirm('Archive this task?')) return;
    fetch(BASE + 'tasks/' + id + '/archive', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(CSRF) + '&ajax=1'
    }).then(r => r.json()).then(d => {
        if (d.success) {
            var row = document.getElementById('taskRow' + id);
            row.style.opacity = '0';
            row.style.transform = 'translateX(30px)';
            row.style.transition = 'opacity .25s, transform .25s';
            setTimeout(function(){ row.remove(); }, 260);
        }
    });
}

function deleteTask(id, btn) {
    if (!confirm('Delete this task permanently?')) return;
    fetch(BASE + 'tasks/' + id + '/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(CSRF) + '&ajax=1'
    }).then(r => r.json()).then(d => {
        if (d.success) {
            var row = document.getElementById('taskRow' + id);
            row.style.opacity = '0';
            row.style.transition = 'opacity .2s';
            setTimeout(function(){ row.remove(); }, 220);
        }
    });
}
</script>
