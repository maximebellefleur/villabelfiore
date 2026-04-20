<?php
$miniMapEnabled = !empty($item['gps_lat']) && !empty($item['gps_lng']);

$typeEmoji = [
    'olive_tree'  => '🫒', 'tree' => '🌳', 'vine' => '🍇',
    'almond_tree' => '🌰', 'garden' => '🌿', 'zone' => '🛖',
    'orchard'     => '🏕', 'bed' => '🌱', 'line' => '〰️',
    'prep_zone'   => '🟫', 'mobile_coop' => '🐓',
    'building'    => '🏠', 'water_point' => '💧',
];
$typeColor = [
    'olive_tree'  => '#2d6a4f', 'almond_tree' => '#92400e', 'vine' => '#6d28d9',
    'tree'        => '#166534', 'garden' => '#0369a1', 'bed' => '#0369a1',
    'orchard'     => '#c2410c', 'zone' => '#4338ca', 'prep_zone' => '#b45309',
    'water_point' => '#0284c7', 'mobile_coop' => '#991b1b',
    'building'    => '#374151', 'line' => '#1d4ed8',
];
$emoji     = $typeEmoji[$item['type']] ?? '📦';
$color     = $typeColor[$item['type']] ?? '#2d6a4f';
$typeLabel = ucwords(str_replace('_', ' ', $item['type']));

// Find identification photo for hero
$idPhoto = null;
foreach ($attachments as $att) {
    if ($att['category'] === 'identification_photo' && str_starts_with($att['mime_type'] ?? '', 'image/')) {
        $idPhoto = $att;
        break;
    }
}

// Photo preview: all image attachments, newest first, max 4
$imageAttachments = array_values(array_filter($attachments, fn($a) => str_starts_with($a['mime_type'] ?? '', 'image/')));
$previewPhotos    = array_slice($imageAttachments, 0, 4);
$totalPhotos      = count($imageAttachments);

// Gallery: find which index in $imageAttachments corresponds to $idPhoto
$idPhotoGalleryIndex = 0;
if ($idPhoto) {
    foreach ($imageAttachments as $_k => $_a) {
        if ((int)$_a['id'] === (int)$idPhoto['id']) { $idPhotoGalleryIndex = $_k; break; }
    }
}

// Recent activity: last 3 log entries
$recentLog = array_slice($activityLog, 0, 3);

// Pending reminders: first 3 (already sorted by due_at)
$recentReminders = array_slice($reminders, 0, 3);
?>

<!-- =========================================================
     HERO
     ========================================================= -->
<div class="show-hero" style="--item-color:<?= $color ?>">
    <?php if ($idPhoto): ?>
    <div class="show-hero-photo show-hero-photo--clickable" onclick="openGallery(<?= $idPhotoGalleryIndex ?>)" title="View photos">
        <img src="<?= url('/attachments/' . (int)$idPhoto['id'] . '/download') ?>"
             alt="<?= e($item['name']) ?>" loading="eager">
        <div class="show-hero-photo-overlay"></div>
    </div>
    <?php else: ?>
    <div class="show-hero-photo show-hero-photo--placeholder">
        <span class="show-hero-placeholder-emoji"><?= $emoji ?></span>
    </div>
    <?php endif; ?>

    <div class="show-hero-content">
        <div class="show-hero-back">
            <a href="<?= url('/items') ?>" class="show-back-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                Items
            </a>
            <a href="<?= url('/items/' . (int)$item['id'] . '/edit') ?>" class="show-edit-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                Edit
            </a>
        </div>
        <?php if ($idPhoto): ?>
        <div class="show-hero-avatar">
            <img src="<?= url('/attachments/' . (int)$idPhoto['id'] . '/download') ?>" alt="">
        </div>
        <?php endif; ?>
        <div class="show-hero-badge"><?= $emoji ?> <?= e($typeLabel) ?></div>
        <h1 class="show-hero-name"><?= e($item['name']) ?></h1>
        <?php if ($item['status'] !== 'active'): ?>
        <span class="show-hero-status"><?= e($item['status']) ?></span>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<!-- =========================================================
     QUICK ACTIONS (add photo / reminder / note / ai)
     ========================================================= -->
<div class="show-quick-actions">
    <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="show-qa-btn">
        <span class="show-qa-icon">📷</span>
        <span class="show-qa-label">Add Photo</span>
    </a>
    <a href="#form-note" class="show-qa-btn" onclick="expandSection('note-section'); preCheckReminder()">
        <span class="show-qa-icon">🔔</span>
        <span class="show-qa-label">Reminder</span>
    </a>
    <a href="#form-note" class="show-qa-btn" onclick="expandSection('note-section')">
        <span class="show-qa-icon">✏️</span>
        <span class="show-qa-label">Log</span>
    </a>
    <button class="show-qa-btn" id="aiPromptBtn"
            data-url="<?= url('/items/' . (int)$item['id'] . '/ai-prompt') ?>">
        <span class="show-qa-icon" id="aiPromptIcon">🤖</span>
        <span class="show-qa-label" id="aiPromptLabel">Copy AI</span>
    </button>
    <button class="show-qa-btn" id="surveyStartBtn">
        <span class="show-qa-icon">🧭</span>
        <span class="show-qa-label">Survey</span>
    </button>
</div>

<!-- =========================================================
     PHOTO PREVIEW STRIP
     ========================================================= -->
<?php if (!empty($previewPhotos)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">📷 Photos</span>
        <a href="<?= url('/items/' . (int)$item['id'] . '/photos') ?>" class="show-section-link">
            <?= $totalPhotos ?> photo<?= $totalPhotos !== 1 ? 's' : '' ?> — See all →
        </a>
    </div>
    <div class="show-photo-grid show-photo-grid--<?= min(count($previewPhotos), 4) ?>">
        <?php foreach ($previewPhotos as $i => $att): ?>
        <div class="show-photo-thumb" onclick="openGallery(<?= $i ?>)" role="button" tabindex="0"
             onkeydown="if(event.key==='Enter'||event.key===' ')openGallery(<?= $i ?>)">
            <img src="<?= url('/attachments/' . (int)$att['id'] . '/download') ?>"
                 alt="" loading="lazy">
            <?php if ($i === 3 && $totalPhotos > 4): ?>
            <div class="show-photo-more">+<?= $totalPhotos - 4 ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     RECENT ACTIVITY + REMINDERS
     ========================================================= -->
<?php if (!empty($recentLog) || !empty($recentReminders)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">📋 Recent Activity</span>
        <a href="#full-log" class="show-section-link" onclick="expandSection('log-section')">See all →</a>
    </div>
    <div class="show-activity-feed">
        <?php foreach ($recentReminders as $r):
            $overdue = strtotime($r['due_at']) < time();
        ?>
        <div class="show-feed-item show-feed-item--reminder <?= $overdue ? 'show-feed-item--overdue' : '' ?>">
            <span class="show-feed-icon">🔔</span>
            <div class="show-feed-body">
                <div class="show-feed-text"><?= e($r['title']) ?></div>
                <div class="show-feed-date"><?= $overdue ? '⚠️ Overdue · ' : '' ?><?= e(date('d M Y', strtotime($r['due_at']))) ?></div>
            </div>
            <form method="POST" action="<?= url('/reminders/' . (int)$r['id'] . '/complete') ?>" class="show-feed-action">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <button type="submit" class="show-feed-done" title="Mark done">✓</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php foreach ($recentLog as $a): ?>
        <div class="show-feed-item">
            <span class="show-feed-icon">
                <?php
                $actionIcons = ['note'=>'📝','pruning'=>'✂️','treatment'=>'💊','amendment'=>'🌿','harvest'=>'🌾','maintenance'=>'🔧'];
                echo $actionIcons[$a['action_label'] ?? ''] ?? '📋';
                ?>
            </span>
            <div class="show-feed-body">
                <div class="show-feed-text"><?= e($a['description']) ?></div>
                <div class="show-feed-date"><?= e($a['action_label']) ?> · <?= e(date('d M Y', strtotime($a['performed_at']))) ?><?= !empty($a['att_id']) ? ' · 📷' : '' ?></div>
            </div>
            <?php if (!empty($a['att_id'])): ?>
            <a href="<?= url('/attachments/' . (int)$a['att_id'] . '/download') ?>" target="_blank" class="show-feed-thumb-link">
                <img src="<?= url('/attachments/' . (int)$a['att_id'] . '/download') ?>" class="show-feed-thumb" alt="">
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     DETAILS
     ========================================================= -->
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">ℹ️ Details</span>
        <?php if (!empty($item['gps_lat'])): ?>
        <a href="<?= url('/dashboard/map') ?>" class="show-section-link">📍 View on map →</a>
        <?php endif; ?>
    </div>
    <div class="item-detail-card">
        <dl class="item-dl">
            <div class="item-dl-row">
                <dt>Type</dt>
                <dd><?= e($typeLabel) ?></dd>
            </div>
            <?php if ($item['gps_lat'] && $item['gps_lng']): ?>
            <div class="item-dl-row">
                <dt>GPS</dt>
                <dd class="text-sm"><?= number_format((float)$item['gps_lat'], 6) ?>, <?= number_format((float)$item['gps_lng'], 6) ?><?= $item['gps_source'] ? ' <span class="text-muted">(' . e($item['gps_source']) . ')</span>' : '' ?></dd>
            </div>
            <?php endif; ?>
            <?php if (!empty($boundaryGeojson)): ?>
            <div class="item-dl-row">
                <dt>Boundary</dt>
                <dd>
                    <a href="<?= url('/dashboard/map') ?>" class="text-sm">View on map →</a>
                    <details style="margin-top:4px">
                        <summary class="text-sm text-muted" style="cursor:pointer">Show GPS polygon JSON</summary>
                        <pre class="item-dl-geojson"><?= e($boundaryGeojson) ?></pre>
                    </details>
                </dd>
            </div>
            <?php endif; ?>
            <?php if ($item['parent_id']): ?>
            <div class="item-dl-row">
                <dt>Part of</dt>
                <dd><a href="<?= url('/items/' . (int)$item['parent_id']) ?>">View parent →</a></dd>
            </div>
            <?php endif; ?>
            <div class="item-dl-row">
                <dt>Status</dt>
                <dd><?= e($item['status']) ?></dd>
            </div>
            <div class="item-dl-row">
                <dt>Created</dt>
                <dd><?= e(date('d M Y', strtotime($item['created_at']))) ?></dd>
            </div>
            <?php foreach ($meta as $key => $value): ?>
            <div class="item-dl-row">
                <dt><?= e(ucwords(str_replace('_', ' ', $key))) ?></dt>
                <dd><?= e($value) ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>
    </div>
</div>

<!-- =========================================================
     LOCATION MAP
     ========================================================= -->
<?php if ($miniMapEnabled): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">📍 Location</span>
    </div>
    <div id="miniMap" style="height:200px;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--color-border);isolation:isolate;position:relative;z-index:0"></div>
</div>
<script>
window.MINI_MAP_LAT = <?= (float)$item['gps_lat'] ?>;
window.MINI_MAP_LNG = <?= (float)$item['gps_lng'] ?>;
window.MINI_MAP_READONLY = true;
</script>
<?php endif; ?>

<!-- =========================================================
     LOG ACTION + OPTIONAL REMINDER (fused form)
     ========================================================= -->
<div class="show-section" id="note-section">
    <div class="show-section-head">
        <span class="show-section-title">✏️ Log Action</span>
    </div>
    <div class="item-detail-card" id="form-note">
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/actions') ?>" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <!-- Action type -->
            <div class="log-field-row">
                <select name="action_type" class="form-input form-input--touch" id="logActionType">
                    <option value="note">Note</option>
                    <option value="pruning">Pruning</option>
                    <option value="treatment">Treatment</option>
                    <option value="amendment">Amendment</option>
                    <option value="harvest">Harvest</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="observation">Observation</option>
                    <option value="other">Other…</option>
                </select>
            </div>
            <div id="logCustomLabelWrap" style="display:none;margin-top:var(--spacing-2)">
                <input type="text" name="custom_action_label" id="logCustomLabel"
                       class="form-input form-input--touch" placeholder="Describe the action type…" maxlength="80">
            </div>

            <!-- Description (bigger textarea) -->
            <div class="log-field-row">
                <textarea name="description" class="form-input form-input--touch log-textarea"
                          placeholder="What happened? Add your notes here…"
                          rows="3" required></textarea>
            </div>

            <!-- Reminder toggle -->
            <div class="log-reminder-row">
                <label class="log-reminder-check">
                    <input type="checkbox" id="setReminderCb" name="set_reminder" value="1">
                    <span class="log-reminder-check-text">Set a reminder for this log</span>
                </label>
            </div>

            <!-- Photo toggle -->
            <div class="log-reminder-row">
                <label class="log-reminder-check">
                    <input type="checkbox" id="setPhotoCb" name="attach_photo" value="1">
                    <span class="log-reminder-check-text">📷 Attach photos to this log</span>
                </label>
            </div>

            <!-- Photo upload panel (hidden until checkbox checked) -->
            <div id="logPhotoPanel" class="log-cal-panel" style="display:none">
                <input type="file" name="log_photos[]" id="logPhotoInput"
                       accept="image/jpeg,image/png,image/webp,image/gif,image/*"
                       multiple
                       style="position:absolute;opacity:0;width:0;height:0;pointer-events:none;overflow:hidden">
                <button type="button" id="logPhotoPickerBtn" class="log-photo-btn">📂 Choose Photos</button>
                <div id="logPhotoPreview" class="log-photo-preview"></div>
            </div>

            <!-- Inline calendar picker (hidden until checkbox checked) -->
            <div id="reminderPickerPanel" class="log-cal-panel" style="display:none">
                <input type="hidden" name="reminder_due_at" id="reminderDueAt">
                <div class="cal-widget">
                    <div class="cal-hdr">
                        <button type="button" id="calPrevBtn" class="cal-nav-btn">&#8249;</button>
                        <span id="calMonthLabel" class="cal-month-label"></span>
                        <button type="button" id="calNextBtn" class="cal-nav-btn">&#8250;</button>
                    </div>
                    <div class="cal-dow">
                        <span>Mo</span><span>Tu</span><span>We</span><span>Th</span>
                        <span>Fr</span><span>Sa</span><span>Su</span>
                    </div>
                    <div class="cal-grid" id="calGrid"></div>
                    <div id="calSelectedLabel" class="cal-selected-label" style="display:none"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="log-submit-row">
                <button type="submit" class="btn btn-primary btn-block-mobile">Log</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     HARVESTS (if any)
     ========================================================= -->
<?php if (!empty($harvests)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">🌾 Harvests</span>
    </div>
    <div class="item-detail-card">
        <?php
        $harvestTotals = [];
        foreach ($harvests as $h) {
            $u = $h['unit'] ?? 'units';
            $harvestTotals[$u] = ($harvestTotals[$u] ?? 0) + (float)$h['quantity'];
        }
        ?>
        <div style="margin-bottom:var(--spacing-3);font-size:.85rem;color:var(--color-text-muted)">
            Total: <strong><?= implode(', ', array_map(fn($u,$q)=>number_format($q,2).' '.$u, array_keys($harvestTotals), $harvestTotals)) ?></strong>
        </div>
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/harvests') ?>" class="show-form-row" style="margin-bottom:var(--spacing-3)">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <input type="number" step="0.001" name="quantity" class="form-input form-input--sm" placeholder="Qty" required style="width:80px">
            <input type="text" name="unit" class="form-input form-input--sm" placeholder="Unit" required style="width:80px">
            <input type="datetime-local" name="recorded_at" class="form-input form-input--sm" required style="flex:1">
            <button type="submit" class="btn btn-primary btn-sm">Record</button>
        </form>
        <table class="table" style="font-size:.82rem">
            <thead><tr><th>Date</th><th>Qty</th><th>Unit</th><th>Grade</th></tr></thead>
            <tbody>
                <?php foreach ($harvests as $h): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($h['recorded_at']))) ?></td>
                    <td><?= number_format((float)$h['quantity'], 2) ?></td>
                    <td><?= e($h['unit']) ?></td>
                    <td><?= e($h['quality_grade'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     IRRIGATION PLAN
     ========================================================= -->
<?php
$irrigationTypes = ['tree','olive_tree','almond_tree','vine','garden','bed'];
$irrigIntervals  = ['twice_daily'=>'Twice daily','daily'=>'Daily','every_2_days'=>'Every 2 days','weekly'=>'Weekly','biweekly'=>'Every 2 weeks','monthly'=>'Monthly'];
?>
<?php if (in_array($item['type'], $irrigationTypes)): ?>
<div class="show-section" id="irrigation-section">
    <div class="show-section-head">
        <span class="show-section-title">💧 Irrigation Plan</span>
    </div>
    <div class="item-detail-card">

    <?php if ($irrigationPlan): ?>
        <!-- ── View ── -->
        <div id="irrView">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
                <div>
                    <div style="font-weight:700;font-size:1rem"><?= e($irrigIntervals[$irrigationPlan['interval_type']] ?? $irrigationPlan['interval_type']) ?></div>
                    <div style="font-size:0.85rem;color:var(--color-text-muted);margin-top:2px">
                        From <?= e(date('d M Y', strtotime($irrigationPlan['start_date']))) ?>
                        · <?= (int)$irrigationPlan['duration_months'] ?> month<?= $irrigationPlan['duration_months'] != 1 ? 's' : '' ?>
                        <?php if (!empty($irrigationPlan['google_event_id'])): ?>
                        · <span style="color:#2d8a27">📅 Google Calendar</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($irrigationPlan['notes'])): ?>
                    <div style="font-size:0.82rem;margin-top:6px;color:var(--color-text-muted)"><?= nl2br(e($irrigationPlan['notes'])) ?></div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="irrToggleEdit(true)">✏️ Edit</button>
                    <button type="button" class="btn btn-ghost btn-sm" style="color:#dc3545" onclick="irrDelToggle(true)">✕</button>
                </div>
            </div>
            <!-- Delete confirm -->
            <div id="irrDelConfirm" style="display:none;margin-top:10px;padding:10px;background:#fff5f5;border-radius:8px;border:1px solid #fcc">
                <div style="font-size:0.9rem;margin-bottom:8px">Remove plan and delete recurring Google Calendar events?</div>
                <div style="display:flex;gap:8px">
                    <form method="POST" action="<?= url('/irrigation/' . (int)$irrigationPlan['id'] . '/delete') ?>">
                        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                        <button type="submit" class="btn btn-sm" style="background:#dc3545;color:#fff">Yes, remove</button>
                    </form>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="irrDelToggle(false)">Cancel</button>
                </div>
            </div>
        </div>

        <!-- ── Edit form ── -->
        <div id="irrEditForm" style="display:none;margin-top:var(--spacing-3);border-top:1px solid var(--color-border);padding-top:var(--spacing-3)">
            <form method="POST" action="<?= url('/irrigation/' . (int)$irrigationPlan['id'] . '/update') ?>" class="form">
                <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Interval</label>
                        <select name="interval_type" class="form-input">
                            <?php foreach ($irrigIntervals as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $irrigationPlan['interval_type'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration</label>
                        <select name="duration_months" class="form-input">
                            <?php foreach ([1=>'1 month',3=>'3 months',6=>'6 months',12=>'1 year'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= (int)$irrigationPlan['duration_months'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" class="form-input" value="<?= e($irrigationPlan['start_date']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2"><?= e($irrigationPlan['notes'] ?? '') ?></textarea>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="irrToggleEdit(false)">Cancel</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- ── Add form ── -->
        <form method="POST" action="<?= url('/items/' . (int)$item['id'] . '/irrigation') ?>" class="form">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Interval</label>
                    <select name="interval_type" class="form-input">
                        <?php foreach ($irrigIntervals as $v => $l): ?>
                        <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <select name="duration_months" class="form-input">
                        <option value="1">1 month</option>
                        <option value="3">3 months</option>
                        <option value="6">6 months</option>
                        <option value="12" selected>1 year</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Start date</label>
                <input type="date" name="start_date" class="form-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Notes <small class="text-muted">(optional)</small></label>
                <textarea name="notes" class="form-input" rows="2" placeholder="e.g. 15 min drip irrigation"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">💧 Set Irrigation Plan</button>
        </form>
    <?php endif; ?>

    </div>
</div>
<script>
function irrToggleEdit(open) {
    document.getElementById('irrView').style.display     = open ? 'none' : '';
    document.getElementById('irrEditForm').style.display = open ? 'block' : 'none';
}
function irrDelToggle(open) {
    document.getElementById('irrDelConfirm').style.display = open ? 'block' : 'none';
}
</script>
<?php endif; ?>

<!-- =========================================================
     FINANCE (if any)
     ========================================================= -->
<?php if (!empty($finances)): ?>
<div class="show-section">
    <div class="show-section-head">
        <span class="show-section-title">💰 Finance</span>
        <a href="<?= url('/items/' . (int)$item['id'] . '/finance') ?>" class="show-section-link">Full view →</a>
    </div>
    <div class="item-detail-card">
        <table class="table" style="font-size:.82rem">
            <thead><tr><th>Date</th><th>Type</th><th>Label</th><th>Amount</th></tr></thead>
            <tbody>
                <?php foreach ($finances as $f): ?>
                <tr>
                    <td><?= e($f['entry_date']) ?></td>
                    <td><span class="badge badge-<?= $f['entry_type'] === 'revenue' ? 'success' : 'warning' ?>"><?= e($f['entry_type']) ?></span></td>
                    <td><?= e($f['label']) ?></td>
                    <td><?= number_format((float)$f['amount'], 2) ?> <?= e($f['currency']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     FULL ACTIVITY LOG
     ========================================================= -->
<div class="show-section" id="log-section">
    <div class="show-section-head">
        <span class="show-section-title" id="full-log">📋 Activity Log</span>
    </div>
    <div class="item-detail-card">
        <?php if (empty($activityLog)): ?>
        <p class="text-muted">No activity recorded yet.</p>
        <?php else: ?>
        <?php $logCsrf = e(\App\Support\CSRF::getToken()); ?>
        <table class="table show-log-table">
            <thead><tr><th>Date</th><th>Action</th><th>Description</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($activityLog as $a):
                    $logId   = (int)$a['id'];
                    $logData = json_encode([
                        'date'        => date('d M Y, H:i', strtotime($a['performed_at'])),
                        'action'      => $a['action_label'] ?? '',
                        'action_type' => $a['action_type'] ?? '',
                        'description' => $a['description'] ?? '',
                        'att_id'      => $a['att_id'] ?? null,
                        'att_mime'    => $a['att_mime'] ?? null,
                        'att_url'     => !empty($a['att_id']) ? url('/attachments/' . (int)$a['att_id'] . '/download') : null,
                        'log_id'      => $logId,
                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                ?>
                <tr id="logrow_<?= $logId ?>" class="show-log-row" data-log='<?= $logData ?>' style="cursor:pointer" title="Click for details">
                    <td class="text-sm text-muted" style="white-space:nowrap"><?= e(date('d M Y', strtotime($a['performed_at']))) ?></td>
                    <td><span class="badge"><?= e($a['action_label']) ?></span></td>
                    <td class="show-log-desc">
                        <?= e(mb_strimwidth($a['description'] ?? '', 0, 60, '…')) ?>
                        <?php if (!empty($a['att_id'])): ?>
                        <span class="text-muted" style="font-size:.75rem"> 📷</span>
                        <?php endif; ?>
                    </td>
                    <td class="show-log-del-cell" onclick="event.stopPropagation()">
                        <button type="button" class="show-log-del-btn" data-log-id="<?= $logId ?>" title="Delete">✕</button>
                        <span class="show-log-del-confirm" id="logdel_<?= $logId ?>" style="display:none">
                            <form method="POST" action="<?= url('/activity-log/' . $logId . '/trash') ?>" style="display:inline">
                                <input type="hidden" name="_token" value="<?= $logCsrf ?>">
                                <button type="submit" class="show-log-del-yes">✓</button>
                            </form>
                            <button type="button" class="show-log-del-no" data-log-id="<?= $logId ?>">✕</button>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
/* ── Fused log form ─────────────────────────── */
.log-field-row { padding: var(--spacing-3) var(--spacing-3) 0; }
.log-textarea {
    width: 100%; min-height: 90px; resize: vertical;
    font-family: inherit; line-height: 1.5;
}
.log-reminder-row {
    padding: var(--spacing-3) var(--spacing-3) 0;
    border-top: 1px solid var(--color-border);
    margin-top: var(--spacing-3);
}
.log-reminder-check {
    display: inline-flex; align-items: center; gap: 10px;
    cursor: pointer; font-size: .88rem; color: var(--color-text-muted);
    font-weight: 500;
}
.log-reminder-check input[type="checkbox"] {
    width: 18px; height: 18px; accent-color: var(--color-primary);
    cursor: pointer; flex-shrink: 0;
}
.log-reminder-check:has(input:checked) .log-reminder-check-text {
    color: var(--color-primary); font-weight: 600;
}
.log-submit-row {
    padding: var(--spacing-3);
}
.btn-block-mobile { width: 100%; justify-content: center; }

/* ── Inline calendar widget ─────────────────── */
.log-cal-panel {
    padding: var(--spacing-2) var(--spacing-3) var(--spacing-3);
    border-top: 1px solid var(--color-border);
}
.cal-widget {
    background: var(--color-surface);
    border: 1.5px solid var(--color-border);
    border-radius: 14px;
    overflow: hidden;
    max-width: 320px;
}
.cal-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px 8px;
    background: var(--color-primary);
}
.cal-month-label {
    font-size: .9rem; font-weight: 700; color: #fff;
    letter-spacing: .01em;
}
.cal-nav-btn {
    background: rgba(255,255,255,.18); border: none; color: #fff;
    width: 30px; height: 30px; border-radius: 8px; font-size: 1.2rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background .15s; line-height: 1;
}
.cal-nav-btn:hover { background: rgba(255,255,255,.32); }
.cal-dow {
    display: grid; grid-template-columns: repeat(7, 1fr);
    padding: 6px 8px 0;
}
.cal-dow span {
    text-align: center; font-size: .65rem; font-weight: 700;
    text-transform: uppercase; color: var(--color-text-muted); letter-spacing: .05em;
}
.cal-grid {
    display: grid; grid-template-columns: repeat(7, 1fr);
    gap: 2px; padding: 4px 8px 8px;
}
.cal-day-blank { /* empty placeholder */ }
.cal-day-btn {
    aspect-ratio: 1; border: none; background: none;
    border-radius: 8px; font-size: .82rem; font-weight: 500;
    cursor: pointer; color: var(--color-text);
    transition: background .12s, color .12s;
    display: flex; align-items: center; justify-content: center;
}
.cal-day-btn:hover { background: var(--color-primary-soft); color: var(--color-primary); }
.cal-day-btn.cal-day--today { font-weight: 800; color: var(--color-primary); }
.cal-day-btn.cal-day--selected {
    background: var(--color-primary); color: #fff; font-weight: 700;
}
.cal-day-btn.cal-day--past { opacity: .35; cursor: default; }
.cal-day-btn.cal-day--past:hover { background: none; color: var(--color-text); }
.cal-selected-label {
    padding: 8px 14px 10px;
    font-size: .82rem; font-weight: 600;
    color: var(--color-primary);
    border-top: 1px solid var(--color-border);
}

/* ── Log photo attach ───────────────────────── */
.log-photo-btn {
    display: inline-block; padding: 8px 16px; border-radius: 8px;
    background: var(--color-surface); border: 1.5px dashed var(--color-border);
    font-size: .82rem; color: var(--color-text-muted); cursor: pointer;
    transition: border-color .15s, color .15s; font-family: inherit;
    width: 100%; text-align: left; box-sizing: border-box;
}
.log-photo-btn:hover, .log-photo-btn:focus { border-color: var(--color-primary); color: var(--color-primary); outline: none; }
.log-photo-preview { display: none; }
.log-photo-thumbs { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.log-photo-thumb-img { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1.5px solid var(--color-border); }
/* ── Log thumbnails in table ────────────────── */
.show-log-thumb-link { display: block; margin-top: 5px; }
.show-log-thumb { width: 60px; height: 44px; object-fit: cover; border-radius: 6px; border: 1.5px solid var(--color-border); }
/* ── Feed item thumb ────────────────────────── */
.show-feed-thumb-link { flex-shrink: 0; }
.show-feed-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1.5px solid var(--color-border); }

.item-dl-geojson {
    font-size:.68rem; line-height:1.4; overflow-x:auto; white-space:pre-wrap; word-break:break-all;
    background:var(--color-surface); border:1px solid var(--color-border);
    border-radius:6px; padding:6px 8px; margin-top:4px; max-height:120px;
    color:var(--color-text-muted);
}
.show-log-table { font-size:.82rem; width:100%; }
.show-log-desc { word-break:break-word; overflow-wrap:anywhere; max-width:180px; }
.show-log-del-cell { white-space:nowrap; text-align:right; padding-right:4px; }
.show-log-del-btn { background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:.8rem;padding:2px 6px;border-radius:4px;opacity:.5;transition:opacity .15s,color .15s; }
.show-log-del-btn:hover { opacity:1;color:var(--color-danger,#c0392b); }
.show-log-del-confirm { display:inline-flex;align-items:center;gap:3px; }
.show-log-del-yes { background:var(--color-danger,#c0392b);color:#fff;border:none;border-radius:4px;font-size:.72rem;font-weight:700;padding:2px 7px;cursor:pointer; }
.show-log-del-no  { background:var(--color-border);color:var(--color-text-muted);border:none;border-radius:4px;font-size:.72rem;font-weight:700;padding:2px 7px;cursor:pointer; }

/* AI prompt modal */
.ai-modal-backdrop {
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
    z-index:2000;align-items:flex-end;justify-content:center;
}
.ai-modal-sheet {
    background:var(--color-surface);border-radius:20px 20px 0 0;
    width:100%;max-width:640px;padding:var(--spacing-4);
    box-shadow:0 -4px 32px rgba(0,0,0,.2);
}
.ai-modal-header {
    display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-2);
}
.ai-modal-close {
    background:none;border:none;font-size:1.3rem;cursor:pointer;
    color:var(--color-text-muted);padding:4px 8px;border-radius:6px;
}
.ai-modal-textarea {
    width:100%;height:200px;font-size:.8rem;line-height:1.5;
    border:1.5px solid var(--color-border);border-radius:var(--radius-lg);
    padding:10px 12px;resize:none;font-family:inherit;box-sizing:border-box;
    background:var(--color-surface);color:var(--color-text);
}
.ai-modal-actions {
    display:flex;gap:var(--spacing-2);margin-top:var(--spacing-3);
}
</style>

<!-- AI Prompt modal sheet — must be before the <script> block so getElementById works -->
<div class="ai-modal-backdrop" id="aiModalBackdrop">
    <div class="ai-modal-sheet">
        <div class="ai-modal-header">
            <strong>🤖 AI Prompt</strong>
            <button type="button" class="ai-modal-close" id="aiModalCloseX">✕</button>
        </div>
        <p style="font-size:.8rem;color:var(--color-text-muted);margin:0 0 var(--spacing-2)">Paste this into Claude, ChatGPT, or any AI assistant.</p>
        <textarea class="ai-modal-textarea" id="aiModalText" readonly></textarea>
        <div class="ai-modal-actions">
            <button type="button" class="btn btn-primary" id="aiModalCopyBtn" style="flex:1">📋 Copy to Clipboard</button>
            <button type="button" class="btn btn-secondary" id="aiModalCloseBtn">Close</button>
        </div>
    </div>
</div>

<script>
// Log delete inline confirm
document.querySelectorAll('.show-log-del-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.logId;
        btn.style.display = 'none';
        document.getElementById('logdel_' + id).style.display = 'inline-flex';
    });
});
document.querySelectorAll('.show-log-del-no').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.logId;
        document.getElementById('logdel_' + id).style.display = 'none';
        document.querySelector('.show-log-del-btn[data-log-id="' + id + '"]').style.display = '';
    });
});

// Log entry detail popup — modal HTML lives below this script, use DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    var backdrop  = document.getElementById('logDetailBackdrop');
    var closeBtn  = document.getElementById('logModalClose');
    var titleEl   = document.getElementById('logModalTitle');
    var badgeEl   = document.getElementById('logModalBadge');
    var dateEl    = document.getElementById('logModalDate');
    var descEl    = document.getElementById('logModalDesc');
    var photoWrap = document.getElementById('logModalPhoto');
    var photoImg  = document.getElementById('logModalPhotoImg');
    var photoLink = document.getElementById('logModalPhotoLink');

    if (!backdrop || !closeBtn) return;

    function openLogModal(data) {
        titleEl.textContent  = data.action || 'Log Entry';
        badgeEl.textContent  = data.action || '';
        dateEl.textContent   = data.date   || '';
        descEl.textContent   = data.description || '—';
        if (data.att_url && data.att_mime && data.att_mime.indexOf('image/') === 0) {
            photoImg.src            = data.att_url;
            photoLink.href          = data.att_url;
            photoWrap.style.display = '';
        } else {
            photoWrap.style.display = 'none';
            photoImg.src = '';
        }
        backdrop.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLogModal() {
        backdrop.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.show-log-row').forEach(function (row) {
        row.addEventListener('click', function () {
            try {
                var data = JSON.parse(row.getAttribute('data-log'));
                openLogModal(data);
            } catch(e) {}
        });
    });

    closeBtn.addEventListener('click', closeLogModal);
    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) closeLogModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && backdrop.style.display !== 'none') closeLogModal();
    });
});

// AI Prompt — shows text in a modal so clipboard copy is a direct user gesture (iOS-safe)
(function() {
    var btn      = document.getElementById('aiPromptBtn');
    if (!btn) return;
    var icon     = document.getElementById('aiPromptIcon');
    var label    = document.getElementById('aiPromptLabel');
    var backdrop = document.getElementById('aiModalBackdrop');
    var textarea = document.getElementById('aiModalText');
    var copyBtn  = document.getElementById('aiModalCopyBtn');
    var closeX   = document.getElementById('aiModalCloseX');
    var closeBtn = document.getElementById('aiModalCloseBtn');

    if (!backdrop) return;

    function openModal(text) {
        textarea.value = text;
        backdrop.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(function() { textarea.focus(); textarea.select(); }, 80);
    }
    function closeModal() {
        backdrop.style.display = 'none';
        document.body.style.overflow = '';
        copyBtn.textContent = '📋 Copy to Clipboard';
    }

    btn.addEventListener('click', function() {
        icon.textContent = '⏳'; label.textContent = 'Building…';
        fetch(btn.dataset.url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.prompt) throw new Error('empty');
                icon.textContent = '🤖'; label.textContent = 'Copy AI';
                openModal(data.prompt);
            })
            .catch(function() {
                icon.textContent = '❌'; label.textContent = 'Error';
                setTimeout(function() { icon.textContent = '🤖'; label.textContent = 'Copy AI'; }, 2000);
            });
    });

    copyBtn.addEventListener('click', function() {
        var text = textarea.value;
        function fallback() {
            textarea.select();
            try { document.execCommand('copy'); copyBtn.textContent = '✅ Copied!'; }
            catch(e) { copyBtn.textContent = '⚠️ Select text above and copy'; }
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(function() { copyBtn.textContent = '✅ Copied!'; })
                .catch(fallback);
        } else { fallback(); }
    });

    closeX.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) closeModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
}());

// Scroll to anchor sections
function expandSection(id) {
    setTimeout(function() {
        var el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
}

// ── Action type "Other" toggle ───────────────────────────────────────────────
(function(){
    var sel  = document.getElementById('logActionType');
    var wrap = document.getElementById('logCustomLabelWrap');
    if (!sel || !wrap) return;
    sel.addEventListener('change', function() {
        wrap.style.display = sel.value === 'other' ? '' : 'none';
        if (sel.value === 'other') {
            document.getElementById('logCustomLabel').focus();
        }
    });
}());

// Pre-check reminder checkbox (called from "Reminder" quick action)
function preCheckReminder() {
    var cb = document.getElementById('setReminderCb');
    if (cb && !cb.checked) { cb.checked = true; cb.dispatchEvent(new Event('change')); }
}

// ── Log photo attach toggle ──────────────────────────────────────────────────
(function() {
    var cb      = document.getElementById('setPhotoCb');
    var panel   = document.getElementById('logPhotoPanel');
    var input   = document.getElementById('logPhotoInput');
    var pickerBtn = document.getElementById('logPhotoPickerBtn');
    var preview = document.getElementById('logPhotoPreview');
    if (!cb || !panel) return;

    cb.addEventListener('change', function() {
        panel.style.display = cb.checked ? '' : 'none';
        if (cb.checked && pickerBtn) {
            // Small delay so the panel is rendered before programmatic click
            setTimeout(function() { input && input.click(); }, 80);
        }
    });

    // Programmatic click — reliable on iOS PWA where label+display:none fails
    if (pickerBtn) {
        pickerBtn.addEventListener('click', function() {
            input && input.click();
        });
    }

    if (input) {
        input.addEventListener('change', function() {
            if (!input.files || !input.files.length) return;
            var files = Array.from(input.files);
            var html = '<div class="log-photo-thumbs">';
            var remaining = files.length;
            files.forEach(function(file, i) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    html += '<img src="' + e.target.result + '" class="log-photo-thumb-img" alt="Photo ' + (i+1) + '">';
                    remaining--;
                    if (remaining === 0) {
                        preview.innerHTML = html + '</div>';
                        preview.style.display = '';
                    }
                };
                reader.readAsDataURL(file);
            });
            pickerBtn.textContent = '📷 ' + files.length + ' photo' + (files.length !== 1 ? 's' : '') + ' selected — tap to change';
        });
    }
}());

// ── Inline calendar picker ───────────────────────────────────────────────────
(function () {
    var cb         = document.getElementById('setReminderCb');
    var panel      = document.getElementById('reminderPickerPanel');
    var hiddenInput= document.getElementById('reminderDueAt');
    var calGrid    = document.getElementById('calGrid');
    var calLabel   = document.getElementById('calMonthLabel');
    var calSelected= document.getElementById('calSelectedLabel');
    var prevBtn    = document.getElementById('calPrevBtn');
    var nextBtn    = document.getElementById('calNextBtn');
    if (!cb || !panel) return;

    // Default date = today + 7 days
    var today    = new Date();
    today.setHours(0,0,0,0);
    var defDate  = new Date(today);
    defDate.setDate(defDate.getDate() + 7);

    var viewYear  = defDate.getFullYear();
    var viewMonth = defDate.getMonth(); // 0-indexed
    var selected  = new Date(defDate);

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
    var DAYS   = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    function isoDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth()+1).padStart(2,'0');
        var day = String(d.getDate()).padStart(2,'0');
        return y+'-'+m+'-'+day+' 09:00:00';
    }
    function formatDisplay(d) {
        return DAYS[(d.getDay()+6)%7] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
    }

    function renderCalendar() {
        calLabel.textContent = MONTHS[viewMonth] + ' ' + viewYear;
        calGrid.innerHTML = '';

        // First day of month (0=Sun…6=Sat) → convert to Mo-based (0=Mo…6=Su)
        var firstDay = new Date(viewYear, viewMonth, 1).getDay();
        var offset   = (firstDay + 6) % 7; // blanks before day 1
        var daysInMonth = new Date(viewYear, viewMonth+1, 0).getDate();

        for (var i = 0; i < offset; i++) {
            var blank = document.createElement('span');
            blank.className = 'cal-day-blank';
            calGrid.appendChild(blank);
        }

        for (var d = 1; d <= daysInMonth; d++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cal-day-btn';
            btn.textContent = d;

            var thisDate = new Date(viewYear, viewMonth, d);
            if (thisDate.toDateString() === today.toDateString()) btn.classList.add('cal-day--today');
            if (selected && thisDate.toDateString() === selected.toDateString()) btn.classList.add('cal-day--selected');
            if (thisDate < today) btn.classList.add('cal-day--past');

            btn.addEventListener('click', (function(date) {
                return function() {
                    selected = date;
                    hiddenInput.value = isoDate(date);
                    calSelected.textContent = '📅 ' + formatDisplay(date);
                    calSelected.style.display = 'block';
                    renderCalendar();
                };
            })(new Date(viewYear, viewMonth, d)));

            calGrid.appendChild(btn);
        }
    }

    function show() {
        panel.style.display = 'block';
        // Set default immediately
        hiddenInput.value = isoDate(defDate);
        calSelected.textContent = '📅 ' + formatDisplay(defDate);
        calSelected.style.display = 'block';
        renderCalendar();
    }
    function hide() {
        panel.style.display = 'none';
        hiddenInput.value = '';
    }

    cb.addEventListener('change', function() {
        if (cb.checked) show(); else hide();
    });

    prevBtn.addEventListener('click', function() {
        viewMonth--; if (viewMonth < 0) { viewMonth = 11; viewYear--; } renderCalendar();
    });
    nextBtn.addEventListener('click', function() {
        viewMonth++; if (viewMonth > 11) { viewMonth = 0; viewYear++; } renderCalendar();
    });
}());
</script>

<style>
/* =========================================================
   Item Show — v1.4.15
   ========================================================= */

/* Hero */
.show-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: var(--spacing-4);
    box-shadow: 0 4px 32px rgba(0,0,0,0.14), 0 1px 4px rgba(0,0,0,0.08);
}
.show-hero-photo {
    width: 100%; height: 300px;
    position: relative; overflow: hidden;
    background: #111;
}
.show-hero-photo img { width:100%;height:100%;display:block;object-fit:cover;object-position:center; }
.show-hero-photo-overlay { position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.05) 0%,rgba(0,0,0,.60) 100%); }
.show-hero-avatar { width:52px;height:52px;border-radius:50%;overflow:hidden;border:2.5px solid rgba(255,255,255,0.75);margin-bottom:8px;box-shadow:0 2px 8px rgba(0,0,0,0.35);flex-shrink:0; }
.show-hero-avatar img { width:100%;height:100%;object-fit:cover;display:block; }
.show-hero-photo--placeholder { height:200px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--item-color,#2d6a4f),color-mix(in srgb,var(--item-color,#2d6a4f) 60%,#000)); }
.show-hero-placeholder-emoji { font-size:5rem;opacity:.45; }

.show-hero-content { position:absolute;bottom:0;left:0;right:0;padding:var(--spacing-3) var(--spacing-4) var(--spacing-4);color:#fff; }
.show-hero-back { display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--spacing-3); }
.show-back-btn {
    display:inline-flex;align-items:center;gap:4px;color:rgba(255,255,255,.85);
    font-size:.78rem;font-weight:600;text-decoration:none;
    background:rgba(0,0,0,.25);padding:4px 10px;border-radius:var(--radius-pill);
    backdrop-filter:blur(8px);
}
.show-back-btn:hover{color:#fff;text-decoration:none;}
.show-edit-btn {
    display:inline-flex;align-items:center;gap:4px;color:rgba(255,255,255,.85);
    font-size:.78rem;font-weight:600;text-decoration:none;
    background:rgba(0,0,0,.25);padding:4px 10px;border-radius:var(--radius-pill);
    backdrop-filter:blur(8px);
}
.show-edit-btn:hover{color:#fff;text-decoration:none;}
.show-hero-badge {
    display:inline-flex;align-items:center;font-size:.7rem;font-weight:800;
    text-transform:uppercase;letter-spacing:.08em;background:rgba(255,255,255,.2);
    backdrop-filter:blur(8px);padding:3px 10px;border-radius:var(--radius-pill);
    margin-bottom:5px;color:#fff;
}
.show-hero-name {
    font-size:1.8rem;font-weight:900;color:#fff;margin:0;
    letter-spacing:-.03em;line-height:1.1;text-shadow:0 1px 8px rgba(0,0,0,.3);
}
@media(max-width:380px){.show-hero-name{font-size:1.4rem;}}
.show-hero-status {
    display:inline-block;background:rgba(220,38,38,.85);color:#fff;
    font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);
    text-transform:uppercase;letter-spacing:.05em;margin-top:4px;
}

/* Quick actions */
.show-quick-actions {
    display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;
    scrollbar-width:none;padding-bottom:4px;margin-bottom:var(--spacing-4);
}
.show-quick-actions::-webkit-scrollbar{display:none;}
.show-qa-btn {
    display:inline-flex;flex-direction:row;align-items:center;gap:7px;
    padding:9px 16px;border-radius:999px;white-space:nowrap;flex-shrink:0;
    background:var(--color-surface-raised);border:1.5px solid var(--color-border);
    color:var(--color-text);text-decoration:none;cursor:pointer;
    font-size:.82rem;font-weight:600;
    transition:border-color .15s,background .15s,color .15s;
    box-shadow:var(--shadow-sm);font-family:inherit;
}
.show-qa-btn:hover{border-color:var(--color-primary);background:var(--color-primary-soft);color:var(--color-primary);text-decoration:none;}
.show-qa-icon{font-size:1.1rem;line-height:1;}

/* Sections */
.show-section { margin-bottom:var(--spacing-4); }
.show-section-head {
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:var(--spacing-2);
}
.show-section-title { font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted); }
.show-section-link { font-size:.78rem;font-weight:700;color:var(--color-primary);text-decoration:none; }
.show-section-link:hover{text-decoration:underline;}

/* Photo grid */
.show-photo-grid {
    display:grid;gap:4px;border-radius:var(--radius-lg);overflow:hidden;
}
.show-photo-grid--1{grid-template-columns:1fr;}
.show-photo-grid--2{grid-template-columns:repeat(2,1fr);}
.show-photo-grid--3{grid-template-columns:repeat(2,1fr);grid-template-rows:auto auto;}
.show-photo-grid--3 .show-photo-thumb:first-child{grid-column:1/-1;}
.show-photo-grid--4{grid-template-columns:repeat(2,1fr);}
.show-photo-thumb {
    position:relative;aspect-ratio:1;overflow:hidden;display:block;
    background:var(--color-surface);
}
.show-photo-thumb img { width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s; }
.show-photo-thumb:hover img { transform:scale(1.04); }
.show-photo-more {
    position:absolute;inset:0;background:rgba(0,0,0,.55);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:1.4rem;font-weight:800;
}

/* Activity feed */
.show-activity-feed { display:flex;flex-direction:column;gap:2px; }
.show-feed-item {
    display:flex;align-items:center;gap:var(--spacing-3);
    padding:var(--spacing-2) var(--spacing-3);
    background:var(--color-surface-raised);border-radius:var(--radius);
    border:1px solid var(--color-border);
}
.show-feed-item--reminder { border-left:3px solid var(--color-primary); }
.show-feed-item--overdue  { border-left:3px solid var(--color-danger,#c0392b);background:#fff8f8; }
.show-feed-icon { font-size:1.2rem;flex-shrink:0; }
.show-feed-body { flex:1;min-width:0; }
.show-feed-text { font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.show-feed-date { font-size:.72rem;color:var(--color-text-muted);margin-top:1px; }
.show-feed-action { flex-shrink:0; }
.show-feed-done {
    width:28px;height:28px;border-radius:50%;background:var(--color-primary-soft);
    border:1.5px solid var(--color-primary);color:var(--color-primary);
    cursor:pointer;font-size:.9rem;font-weight:800;
    display:flex;align-items:center;justify-content:center;
    transition:background .15s;
}
.show-feed-done:hover{background:var(--color-primary);color:#fff;}

/* Detail card */
.item-detail-card {
    background:var(--color-surface-raised);border-radius:var(--radius-lg);
    padding:var(--spacing-3) var(--spacing-4);box-shadow:var(--shadow-card);
}
.item-dl { margin:0; }
.item-dl-row {
    display:flex;gap:var(--spacing-3);padding:8px 0;
    border-bottom:1px solid var(--color-border);
    font-size:.875rem;align-items:baseline;
}
.item-dl-row:last-child{border-bottom:none;}
.item-dl-row dt{width:90px;flex-shrink:0;color:var(--color-text-muted);font-weight:600;font-size:.78rem;}
.item-dl-row dd{flex:1;min-width:0;font-weight:500;}

/* Inline forms */
.show-form-row { display:flex;gap:var(--spacing-2);flex-wrap:wrap;align-items:center; }
.show-reminder-for-badge {
    display:inline-flex;align-items:center;gap:6px;
    padding:5px 12px;border-radius:var(--radius-pill);
    background:var(--color-primary-soft);color:var(--color-primary);
    font-size:.78rem;font-weight:700;margin-bottom:var(--spacing-2);
}

/* ── Hero clickable ─────────────────────────── */
.show-hero-photo--clickable { cursor: zoom-in; }

/* ── Gallery lightbox ───────────────────────── */
.gallery-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.93);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    touch-action: pan-y;
}
.gallery-close {
    position: absolute; top: 14px; right: 14px;
    background: rgba(255,255,255,.18); border: none;
    color: #fff; font-size: 1.2rem;
    width: 42px; height: 42px; border-radius: 50%;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    z-index: 10; transition: background .15s; line-height: 1;
}
.gallery-close:hover { background: rgba(255,255,255,.32); }
.gallery-prev, .gallery-next {
    position: absolute; top: 50%; transform: translateY(-50%);
    background: rgba(255,255,255,.15); border: none;
    color: #fff; font-size: 1.9rem; font-weight: 300;
    width: 50px; height: 50px; border-radius: 50%;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    z-index: 10; transition: background .15s; line-height: 1;
}
.gallery-prev { left: 14px; }
.gallery-next { right: 14px; }
.gallery-prev:hover, .gallery-next:hover { background: rgba(255,255,255,.32); }
@media(max-width:600px) {
    .gallery-prev, .gallery-next { width:38px;height:38px;font-size:1.5rem; }
    .gallery-prev { left:6px; }
    .gallery-next { right:6px; }
}
.gallery-img-wrap {
    max-width: calc(100vw - 120px);
    max-height: calc(100vh - 110px);
    display: flex; align-items: center; justify-content: center;
    flex: 1;
}
@media(max-width:600px) {
    .gallery-img-wrap { max-width: calc(100vw - 80px); }
}
.gallery-img-wrap img {
    max-width: 100%;
    max-height: calc(100vh - 110px);
    object-fit: contain; border-radius: 4px;
    display: block; user-select: none;
    transition: opacity .12s ease;
}
.gallery-footer {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 14px 20px 20px;
    background: linear-gradient(to top, rgba(0,0,0,.7) 0%, transparent 100%);
    text-align: center; pointer-events: none;
}
.gallery-counter {
    color: rgba(255,255,255,.75); font-size: .82rem; font-weight: 600;
    letter-spacing: .03em;
}
.gallery-caption {
    color: rgba(255,255,255,.88); font-size: .88rem; margin-top: 4px;
    padding: 0 60px; line-height: 1.4;
}
.show-photo-thumb { cursor: pointer; }

/* ── Log detail modal ───────────────────────────────────────────── */
.log-modal-backdrop {
    position: fixed; inset: 0; z-index: 4000;
    background: rgba(0,0,0,.72);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    animation: logFadeIn .18s ease;
}
@keyframes logFadeIn { from { opacity:0 } to { opacity:1 } }
.log-modal {
    background: var(--color-surface);
    border-radius: 18px;
    width: 100%; max-width: 480px;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 24px 64px rgba(0,0,0,.45);
    animation: logSlideUp .22s cubic-bezier(.25,.8,.25,1);
    position: relative;
}
@keyframes logSlideUp { from { transform:translateY(24px);opacity:0 } to { transform:translateY(0);opacity:1 } }
.log-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 12px;
    border-bottom: 1px solid var(--color-border);
}
.log-modal-title { font-weight: 700; font-size: 1rem; }
.log-modal-close {
    background: none; border: none; font-size: 1.2rem; cursor: pointer;
    color: var(--color-text-muted); padding: 4px 8px; border-radius: 6px;
    transition: background .12s;
}
.log-modal-close:hover { background: var(--color-border); }
.log-modal-body { padding: 16px 20px 20px; }
.log-modal-badge { display: inline-block; margin-bottom: 12px; }
.log-modal-date { font-size: .82rem; color: var(--color-text-muted); margin-bottom: 10px; }
.log-modal-desc {
    font-size: .93rem; line-height: 1.6;
    word-break: break-word; white-space: pre-wrap;
    color: var(--color-text);
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: 8px; padding: 12px 14px;
}
.log-modal-photo { margin-top: 12px; }
.log-modal-photo img { max-width: 100%; border-radius: 10px; border: 1px solid var(--color-border); }
.log-row-hint { font-size: .72rem; color: var(--color-text-muted); margin-top: 2px; opacity:.7 }
</style>

<!-- ── Log detail modal ─────────────────────────────────────────── -->
<div id="logDetailBackdrop" class="log-modal-backdrop" style="display:none" role="dialog" aria-modal="true" aria-label="Log entry detail">
    <div class="log-modal" id="logDetailModal">
        <div class="log-modal-header">
            <span class="log-modal-title" id="logModalTitle">Log Entry</span>
            <button class="log-modal-close" id="logModalClose" aria-label="Close">&#10005;</button>
        </div>
        <div class="log-modal-body">
            <div class="log-modal-badge"><span class="badge" id="logModalBadge"></span></div>
            <div class="log-modal-date" id="logModalDate"></div>
            <div class="log-modal-desc" id="logModalDesc"></div>
            <div class="log-modal-photo" id="logModalPhoto" style="display:none">
                <a id="logModalPhotoLink" href="#" target="_blank">
                    <img id="logModalPhotoImg" src="" alt="Log photo">
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Yearly Compass Survey modal ────────────────────────────── -->
<div id="surveyBackdrop" class="survey-backdrop" style="display:none" role="dialog" aria-modal="true" aria-label="Compass survey">
    <div class="survey-modal" id="surveyModal">
        <div class="survey-modal-header">
            <span class="survey-modal-title">🧭 Annual Survey</span>
            <button class="survey-modal-close" id="surveyClose" aria-label="Close">&#10005;</button>
        </div>
        <div class="survey-modal-body" id="surveyBody">
            <!-- injected by JS -->
        </div>
    </div>
</div>
<!-- hidden camera input (reused per step) -->
<input type="file" id="surveyFileInput" accept="image/*" capture="environment"
       style="position:absolute;opacity:0;width:0;height:0;pointer-events:none;overflow:hidden">

<style>
/* ── Survey modal ───────────────────────────────────────────── */
.survey-backdrop {
    position:fixed;inset:0;z-index:5000;
    background:rgba(0,0,0,.78);
    display:flex;align-items:center;justify-content:center;
    padding:16px;
    animation:logFadeIn .18s ease;
}
.survey-modal {
    background:var(--color-surface);
    border-radius:20px;
    width:100%;max-width:420px;
    max-height:92vh;overflow-y:auto;
    box-shadow:0 24px 64px rgba(0,0,0,.5);
    animation:logSlideUp .22s cubic-bezier(.25,.8,.25,1);
}
.survey-modal-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:18px 20px 14px;
    border-bottom:1px solid var(--color-border);
}
.survey-modal-title { font-weight:800;font-size:1rem;letter-spacing:.01em; }
.survey-modal-close {
    background:none;border:none;font-size:1.1rem;cursor:pointer;
    color:var(--color-text-muted);padding:4px 8px;border-radius:6px;
    transition:background .12s;
}
.survey-modal-close:hover { background:var(--color-border); }
.survey-modal-body { padding:20px; }

/* Step card */
.survey-step {
    text-align:center;
    padding:8px 0 4px;
}
.survey-step-counter {
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;
    color:var(--color-text-muted);margin-bottom:6px;
}
.survey-direction-arrow {
    font-size:4rem;line-height:1;margin:8px 0;
    display:block;
}
.survey-direction-label {
    font-size:1.3rem;font-weight:900;letter-spacing:-.02em;
    margin-bottom:4px;color:var(--color-text);
}
.survey-direction-hint {
    font-size:.82rem;color:var(--color-text-muted);margin-bottom:20px;
}
.survey-thumb-wrap {
    margin:12px auto 16px;
    width:140px;height:105px;
    border-radius:12px;overflow:hidden;
    border:2px solid var(--color-primary);
    background:var(--color-bg);
    display:none;
}
.survey-thumb-wrap img {
    width:100%;height:100%;object-fit:cover;display:block;
}
.survey-capture-btn {
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    width:100%;padding:14px 20px;
    background:var(--color-primary);color:#fff;
    border:none;border-radius:12px;
    font-size:.95rem;font-weight:700;font-family:inherit;
    cursor:pointer;transition:opacity .15s;
    margin-bottom:10px;
}
.survey-capture-btn:hover { opacity:.88; }
.survey-capture-btn:disabled { opacity:.45;cursor:not-allowed; }
.survey-next-btn {
    display:inline-flex;align-items:center;justify-content:center;
    width:100%;padding:12px 20px;
    background:var(--color-surface-raised);color:var(--color-primary);
    border:1.5px solid var(--color-primary);border-radius:12px;
    font-size:.9rem;font-weight:700;font-family:inherit;
    cursor:pointer;transition:background .15s;
    margin-bottom:8px;
}
.survey-next-btn:hover { background:var(--color-primary-soft); }

/* Upload step */
.survey-upload-grid {
    display:grid;grid-template-columns:repeat(2,1fr);gap:8px;
    margin-bottom:20px;
}
.survey-upload-thumb {
    border-radius:10px;overflow:hidden;border:1.5px solid var(--color-border);
    position:relative;background:var(--color-bg);
}
.survey-upload-thumb img { width:100%;aspect-ratio:4/3;object-fit:cover;display:block; }
.survey-upload-thumb-label {
    position:absolute;bottom:0;left:0;right:0;
    background:rgba(0,0,0,.55);color:#fff;
    font-size:.62rem;font-weight:700;text-align:center;
    padding:3px 4px;letter-spacing:.04em;
}
.survey-upload-btn {
    width:100%;padding:15px;
    background:var(--color-primary);color:#fff;
    border:none;border-radius:12px;
    font-size:1rem;font-weight:700;font-family:inherit;
    cursor:pointer;transition:opacity .15s;
    margin-bottom:10px;
}
.survey-upload-btn:hover { opacity:.88; }
.survey-upload-btn:disabled { opacity:.45;cursor:not-allowed; }
.survey-progress-wrap {
    margin-bottom:10px;
    background:var(--color-border);border-radius:20px;
    height:8px;overflow:hidden;display:none;
}
.survey-progress-fill {
    height:100%;background:var(--color-primary);
    border-radius:20px;transition:width .2s;
}
.survey-progress-label {
    font-size:.78rem;color:var(--color-text-muted);
    text-align:center;display:none;margin-bottom:8px;
}
.survey-done-msg {
    text-align:center;font-size:1rem;font-weight:700;
    color:var(--color-primary);padding:8px 0;display:none;
}
</style>

<script>
(function() {
    var ITEM_ID     = <?= (int)$item['id'] ?>;
    var UPLOAD_URL  = window.APP_BASE + '/items/' + ITEM_ID + '/attachments';
    var CSRF_TOKEN  = <?= json_encode(\App\Support\CSRF::getToken()) ?>;

    var STEPS = [
        { label: 'South', arrow: '⬇️', hint: 'Face south — point camera at item', cat: 'yearly_refresh_south' },
        { label: 'East',  arrow: '➡️', hint: 'Face east — point camera at item',  cat: 'yearly_refresh_east'  },
        { label: 'North', arrow: '⬆️', hint: 'Face north — point camera at item', cat: 'yearly_refresh_north' },
        { label: 'West',  arrow: '⬅️', hint: 'Face west — point camera at item',  cat: 'yearly_refresh_west'  },
    ];

    var backdrop  = document.getElementById('surveyBackdrop');
    var body      = document.getElementById('surveyBody');
    var closeBtn  = document.getElementById('surveyClose');
    var startBtn  = document.getElementById('surveyStartBtn');
    var fileInput = document.getElementById('surveyFileInput');

    var currentStep = 0;
    var photos      = []; // {file, dataUrl} per step

    function openSurvey() {
        currentStep = 0;
        photos      = [];
        backdrop.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        renderStep();
    }

    function closeSurvey() {
        backdrop.style.display = 'none';
        document.body.style.overflow = '';
    }

    function renderStep() {
        if (currentStep >= STEPS.length) {
            renderUpload();
            return;
        }
        var s = STEPS[currentStep];
        var stepNum = currentStep + 1;
        var existingDataUrl = photos[currentStep] ? photos[currentStep].dataUrl : null;

        body.innerHTML = [
            '<div class="survey-step">',
            '  <div class="survey-step-counter">Step ' + stepNum + ' of ' + STEPS.length + '</div>',
            '  <div class="survey-direction-arrow">' + s.arrow + '</div>',
            '  <div class="survey-direction-label">' + s.label + '</div>',
            '  <div class="survey-direction-hint">' + s.hint + '</div>',
            '  <div class="survey-thumb-wrap" id="surveyThumbWrap"' + (existingDataUrl ? ' style="display:block"' : '') + '>',
            '    <img id="surveyThumbImg" src="' + (existingDataUrl || '') + '" alt="">',
            '  </div>',
            '  <button class="survey-capture-btn" id="surveyCaptureBtn">📸 ' + (existingDataUrl ? 'Retake' : 'Take Photo') + '</button>',
            '  <button class="survey-next-btn" id="surveyNextBtn"' + (existingDataUrl ? '' : ' disabled') + '>',
            '    ' + (stepNum < STEPS.length ? 'Next →' : 'Review & Upload'),
            '  </button>',
            '</div>',
        ].join('');

        document.getElementById('surveyCaptureBtn').addEventListener('click', function() {
            fileInput.value = '';
            fileInput.click();
        });

        document.getElementById('surveyNextBtn').addEventListener('click', function() {
            if (!photos[currentStep]) return;
            currentStep++;
            renderStep();
        });
    }

    fileInput.addEventListener('change', function() {
        if (!fileInput.files || !fileInput.files.length) return;
        var file = fileInput.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            photos[currentStep] = { file: file, dataUrl: e.target.result };
            var wrap  = document.getElementById('surveyThumbWrap');
            var img   = document.getElementById('surveyThumbImg');
            var next  = document.getElementById('surveyNextBtn');
            var capBtn= document.getElementById('surveyCaptureBtn');
            if (wrap)  { img.src = e.target.result; wrap.style.display = 'block'; }
            if (capBtn) capBtn.textContent = '📸 Retake';
            if (next)  { next.disabled = false; }
        };
        reader.readAsDataURL(file);
        fileInput.value = '';
    });

    function renderUpload() {
        var grid = photos.map(function(p, i) {
            return '<div class="survey-upload-thumb">'
                + '<img src="' + p.dataUrl + '" alt="' + STEPS[i].label + '">'
                + '<div class="survey-upload-thumb-label">' + STEPS[i].arrow + ' ' + STEPS[i].label + '</div>'
                + '</div>';
        }).join('');

        body.innerHTML = [
            '<div class="survey-upload-grid">' + grid + '</div>',
            '<div class="survey-progress-wrap" id="surveyProgressWrap"><div class="survey-progress-fill" id="surveyProgressFill" style="width:0%"></div></div>',
            '<div class="survey-progress-label" id="surveyProgressLabel"></div>',
            '<div class="survey-done-msg" id="surveyDoneMsg">✅ All 4 photos saved!</div>',
            '<button class="survey-upload-btn" id="surveyUploadBtn">⬆️ Upload All 4 Photos</button>',
        ].join('');

        document.getElementById('surveyUploadBtn').addEventListener('click', startUpload);
    }

    function startUpload() {
        var btn      = document.getElementById('surveyUploadBtn');
        var progWrap = document.getElementById('surveyProgressWrap');
        var progFill = document.getElementById('surveyProgressFill');
        var progLbl  = document.getElementById('surveyProgressLabel');
        var doneMsg  = document.getElementById('surveyDoneMsg');
        btn.disabled = true;
        progWrap.style.display = 'block';
        progLbl.style.display  = 'block';

        var queue  = photos.slice();
        var total  = queue.length;
        var done   = 0;

        function uploadNext() {
            if (!queue.length) {
                progFill.style.width = '100%';
                progLbl.textContent  = '✅ Done!';
                btn.style.display    = 'none';
                doneMsg.style.display = 'block';
                setTimeout(closeSurvey, 1400);
                return;
            }
            var item  = queue.shift();
            var step  = STEPS[total - queue.length - 1];
            done++;
            var label = done + ' / ' + total + ' — ' + step.label;
            progLbl.textContent = label + ' · Compressing…';
            progFill.style.width = Math.round((done - 1) / total * 100) + '%';

            compressImage(item.file, function(compressed) {
                progLbl.textContent = label + ' · Uploading…';
                var fd = new FormData();
                fd.append('file', compressed);
                fd.append('category', step.cat);
                fd.append('_token', CSRF_TOKEN);
                fd.append('_ajax', '1');
                fd.append('_redirect', window.location.pathname);

                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var pct = ((done - 1) / total + e.loaded / e.total / total) * 100;
                        progFill.style.width = Math.min(Math.round(pct), 95) + '%';
                    }
                });
                xhr.addEventListener('load', function() {
                    var res; try { res = JSON.parse(xhr.responseText); } catch(ex) {}
                    if (xhr.status === 200 && res && res.success) {
                        uploadNext();
                    } else {
                        progLbl.textContent = '⚠️ Upload failed for ' + step.label + '. Tap to retry.';
                        btn.disabled = false;
                        btn.textContent = '↩️ Retry';
                        queue.unshift(item); // put back
                        btn.addEventListener('click', function retry() {
                            btn.removeEventListener('click', retry);
                            btn.disabled = true;
                            btn.textContent = '⬆️ Upload All 4 Photos';
                            done--;
                            queue.unshift(item);
                            uploadNext();
                        }, { once: true });
                    }
                });
                xhr.addEventListener('error', function() {
                    progLbl.textContent = '⚠️ Network error. Check connection.';
                    btn.disabled = false;
                    btn.textContent = '↩️ Retry';
                });
                xhr.open('POST', UPLOAD_URL, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(fd);
            });
        }

        uploadNext();
    }

    function compressImage(file, callback) {
        if (file.type.indexOf('image/') !== 0) { callback(file); return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var MAX = 1920, w = img.width, h = img.height;
                var ratio = Math.min(MAX/w, MAX/h, 1);
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(w*ratio); canvas.height = Math.round(h*ratio);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function(blob) {
                    if (!blob) { callback(file); return; }
                    callback(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', 0.82);
            };
            img.onerror = function() { callback(file); };
            img.src = e.target.result;
        };
        reader.onerror = function() { callback(file); };
        reader.readAsDataURL(file);
    }

    startBtn.addEventListener('click', openSurvey);
    closeBtn.addEventListener('click', closeSurvey);
    backdrop.addEventListener('click', function(e) {
        if (e.target === backdrop) closeSurvey();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && backdrop.style.display !== 'none') closeSurvey();
    });
}());
</script>

<!-- ── Gallery overlay ─────────────────────────────────────────── -->
<div id="galleryOverlay" class="gallery-overlay" style="display:none" role="dialog" aria-modal="true" aria-label="Photo gallery">
    <button class="gallery-close" id="galleryClose" aria-label="Close gallery">&#10005;</button>
    <button class="gallery-prev" id="galleryPrev" aria-label="Previous photo">&#8249;</button>
    <div class="gallery-img-wrap">
        <img id="galleryImg" src="" alt="">
    </div>
    <button class="gallery-next" id="galleryNext" aria-label="Next photo">&#8250;</button>
    <div class="gallery-footer">
        <div class="gallery-counter" id="galleryCounter"></div>
        <div class="gallery-caption" id="galleryCaption"></div>
    </div>
</div>

<script>
(function () {
    var items = <?php
        $galleryData = [];
        foreach ($imageAttachments as $att) {
            $galleryData[] = [
                'src'     => url('/attachments/' . (int)$att['id'] . '/download'),
                'caption' => $att['caption'] ?? ($att['original_name'] ?? ''),
            ];
        }
        echo json_encode($galleryData, JSON_HEX_TAG | JSON_HEX_AMP);
    ?>;

    // Declare all DOM refs at the top — before any early return — so the
    // closure assigned to window.openGallery can reference them safely.
    var overlay   = document.getElementById('galleryOverlay');
    var imgEl     = document.getElementById('galleryImg');
    var counter   = document.getElementById('galleryCounter');
    var captionEl = document.getElementById('galleryCaption');
    var closeBtn  = document.getElementById('galleryClose');
    var prevBtn   = document.getElementById('galleryPrev');
    var nextBtn   = document.getElementById('galleryNext');
    var current   = 0;
    var total     = items.length;

    function galleryShow(idx) {
        current = ((idx % total) + total) % total;
        imgEl.style.opacity = '0.4';
        imgEl.src = items[current].src;
        imgEl.onload = function () { imgEl.style.opacity = '1'; };
        counter.textContent = (current + 1) + ' / ' + total;
        captionEl.textContent = items[current].caption || '';
    }

    function galleryOpen(idx) {
        if (!items.length || !overlay) return;
        galleryShow(idx);
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function galleryClose() {
        if (!overlay) return;
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        imgEl.src = '';
    }

    // Direct reference — no wrapper, no name collision with window.open
    window.openGallery = galleryOpen;

    // Skip event listener setup when there are no images or overlay is missing
    if (!items.length || !overlay) return;

    closeBtn.addEventListener('click', galleryClose);
    prevBtn.addEventListener('click', function () { galleryShow(current - 1); });
    nextBtn.addEventListener('click', function () { galleryShow(current + 1); });

    // Click on backdrop (not on img/buttons) closes gallery
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) galleryClose();
    });

    // Keyboard navigation
    document.addEventListener('keydown', function (e) {
        if (overlay.style.display === 'none') return;
        if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   { galleryShow(current - 1); e.preventDefault(); }
        else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { galleryShow(current + 1); e.preventDefault(); }
        else if (e.key === 'Escape') galleryClose();
    });

    // Touch swipe
    var touchStartX = 0;
    var touchStartY = 0;
    overlay.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].clientX;
        touchStartY = e.changedTouches[0].clientY;
    }, { passive: true });
    overlay.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - touchStartX;
        var dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) {
            if (dx < 0) galleryShow(current + 1);
            else        galleryShow(current - 1);
        }
    }, { passive: true });
}());
</script>
