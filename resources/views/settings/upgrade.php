<?php
$result = $_SESSION['upgrade_result'] ?? null;
unset($_SESSION['upgrade_result']);
?>

<div class="page-header">
    <h1 class="page-title">Update</h1>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary">&larr; Settings</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php if ($result): ?>
<!-- ── Post-upgrade summary ── -->
<div class="upg-result">
    <div class="upg-result-icon">✅</div>
    <div class="upg-result-title">Updated to v<?= e($result['to']) ?></div>
    <?php if ($result['to_name']): ?>
    <div class="upg-result-subtitle"><?= e($result['to_name']) ?></div>
    <?php endif; ?>
    <div class="upg-result-meta"><?= (int)$result['extracted'] ?> files updated &middot; <?= (int)$result['skipped'] ?> skipped</div>
    <?php if (!empty($result['new_entries'])): ?>
    <div class="upg-result-changelog">
        <?php foreach ($result['new_entries'] as $ver => $entry): ?>
        <?php foreach (['new' => '✨', 'improved' => '⚡', 'fixed' => '🐛'] as $type => $icon): ?>
        <?php if (!empty($entry[$type])): ?>
        <?php foreach ($entry[$type] as $item): ?>
        <div class="upg-result-line"><span><?= $icon ?></span> <?= e($item) ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Current version ── -->
<div class="upg-version-card">
    <div class="upg-version-label">Current version</div>
    <div class="upg-version-number">v<?= e($currentVersion) ?></div>
    <div class="upg-version-name"><?= e($currentName) ?></div>
</div>

<?php if (!$zipSupported): ?>
<div class="alert alert-warning" style="margin-bottom:var(--spacing-4)">
    ⚠️ <strong>ZipArchive not available</strong> on this server. See <code>docs/UPDATE.md</code> for manual steps.
</div>
<?php else: ?>

<!-- ── ONE button ── -->
<div class="upg-action-card" id="upgActionCard">
    <div class="upg-action-icon">🔄</div>
    <div class="upg-action-body">
        <div class="upg-action-title">Apply Latest Update</div>
        <div class="upg-action-desc">Downloads and installs the latest version directly from GitHub. Your database, .env, and photos are never touched.</div>
    </div>
    <button type="button" class="btn btn-primary upg-action-btn" id="updateNowBtn">Update Now</button>
</div>

<!-- Progress (hidden until started) -->
<div class="upg-progress-wrap" id="upgProgress" style="display:none">
    <div class="upg-progress-bar-track">
        <div class="upg-progress-bar-fill" id="upgProgressFill"></div>
    </div>
    <div class="upg-progress-text" id="upgProgressText">Connecting to GitHub…</div>
</div>
<div class="upg-error" id="upgError" style="display:none"></div>

<!-- ── Advanced: manual ZIP upload ── -->
<details class="upg-advanced">
    <summary class="upg-advanced-toggle">Advanced — upload a ZIP manually</summary>
    <div class="upg-advanced-body">
        <form method="POST" action="<?= url('/settings/upgrade/upload') ?>"
              enctype="multipart/form-data" id="upgradeForm">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <div id="dropZone" class="upg-drop-zone">
                <div class="upg-drop-icon">📦</div>
                <div class="upg-drop-label">Drop <code>rooted-cpanel-update.zip</code> here or tap to browse</div>
                <input type="file" name="upgrade_zip" id="upgradeFile" accept=".zip" required class="upg-drop-input">
                <div id="dropZoneFilename" class="upg-drop-filename" style="display:none"></div>
            </div>

            <div id="upgradeSubmit" style="display:none;margin-top:var(--spacing-4)">
                <button type="submit" class="btn btn-primary btn-full" id="upgradeBtn">🚀 Apply ZIP</button>
                <p class="text-muted text-sm" style="margin-top:var(--spacing-2);text-align:center">Your .env, database and storage/ are never touched.</p>
            </div>

            <div class="upg-progress-wrap" id="uploadProgress" style="display:none;margin-top:var(--spacing-3)">
                <div class="upg-progress-bar-track">
                    <div class="upg-progress-bar-fill" id="uploadProgressBar"></div>
                </div>
                <div class="upg-progress-text" id="uploadProgressText">Uploading…</div>
            </div>
        </form>
    </div>
</details>

<?php endif; ?>

<!-- ── Upgrade history ── -->
<?php if (!empty($upgradeLog)): ?>
<div class="upg-history">
    <div class="upg-section-title">Upgrade history</div>
    <?php foreach ($upgradeLog as $entry): ?>
    <div class="upg-history-row">
        <span class="upg-history-date text-muted"><?= e($entry['date']) ?></span>
        <span>v<?= e($entry['from']) ?> → v<?= e($entry['to']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Full changelog ── -->
<div class="upg-section-title" style="margin-top:var(--spacing-6)">Changelog</div>
<?php foreach ($changelog as $ver => $entry): ?>
<div class="upg-changelog-entry <?= $ver === $currentVersion ? 'upg-changelog-entry--current' : '' ?>">
    <div class="upg-changelog-header">
        <span class="upg-changelog-ver">v<?= e($ver) ?></span>
        <span class="upg-changelog-title"><?= e($entry['title'] ?? '') ?></span>
        <span class="upg-changelog-date text-muted"><?= e($entry['date'] ?? '') ?></span>
        <?php if ($ver === $currentVersion): ?><span class="upg-changelog-badge">current</span><?php endif; ?>
    </div>
    <?php foreach (['new' => '✨ New', 'improved' => '⚡ Improved', 'fixed' => '🐛 Fixed'] as $type => $label): ?>
    <?php if (!empty($entry[$type])): ?>
    <ul class="upg-changelog-list">
        <?php foreach ($entry[$type] as $item): ?>
        <li><span class="upg-changelog-tag"><?= $label ?></span> <?= e($item) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<style>
/* ── Version card ── */
.upg-version-card {
    background: var(--color-surface-raised);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    padding: var(--spacing-5) var(--spacing-5);
    margin-bottom: var(--spacing-4);
    box-shadow: var(--shadow-sm);
}
.upg-version-label {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: var(--color-text-muted); margin-bottom: var(--spacing-1);
}
.upg-version-number {
    font-size: 2.8rem; font-weight: 900; color: var(--color-primary); line-height: 1;
}
.upg-version-name {
    font-size: .95rem; color: var(--color-text-muted); margin-top: var(--spacing-1);
}

/* ── Update Now card ── */
.upg-action-card {
    display: flex; align-items: center; gap: var(--spacing-4);
    background: linear-gradient(135deg, #e8f5e1, #f0f9ec);
    border: 1.5px solid rgba(45,106,79,.25);
    border-radius: var(--radius-xl);
    padding: var(--spacing-5);
    margin-bottom: var(--spacing-3);
    box-shadow: var(--shadow);
    flex-wrap: wrap;
}
.upg-action-icon { font-size: 2rem; flex-shrink: 0; }
.upg-action-body { flex: 1; min-width: 0; }
.upg-action-title { font-size: 1rem; font-weight: 700; margin-bottom: 2px; }
.upg-action-desc  { font-size: .82rem; color: var(--color-text-muted); }
.upg-action-btn   { flex-shrink: 0; padding: var(--spacing-3) var(--spacing-5); font-size: 1rem; }

/* ── Progress ── */
.upg-progress-wrap { margin-bottom: var(--spacing-4); }
.upg-progress-bar-track {
    height: 10px; background: var(--color-border); border-radius: 999px; overflow: hidden;
    margin-bottom: var(--spacing-2);
}
.upg-progress-bar-fill {
    height: 100%; background: var(--color-primary); border-radius: 999px; width: 0;
    transition: width .4s ease;
}
.upg-progress-text { font-size: .85rem; color: var(--color-text-muted); }

/* ── Error ── */
.upg-error {
    background: #fde8e8; border: 1px solid #f5a0a0; border-radius: var(--radius-lg);
    padding: var(--spacing-3) var(--spacing-4); font-size: .875rem; color: #c00;
    margin-bottom: var(--spacing-4);
}

/* ── Advanced ── */
.upg-advanced {
    border: 1px solid var(--color-border); border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-5);
    overflow: hidden;
}
.upg-advanced-toggle {
    cursor: pointer; padding: var(--spacing-3) var(--spacing-4);
    font-size: .85rem; font-weight: 600; color: var(--color-text-muted);
    list-style: none; user-select: none;
}
.upg-advanced-toggle::-webkit-details-marker { display: none; }
.upg-advanced-toggle::before { content: '▶ '; font-size: .7rem; }
details[open] .upg-advanced-toggle::before { content: '▼ '; }
.upg-advanced-body { padding: var(--spacing-4); border-top: 1px solid var(--color-border); }

/* Drop zone */
.upg-drop-zone {
    position: relative; border: 2px dashed var(--color-border); border-radius: var(--radius-lg);
    min-height: 120px; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: var(--spacing-2);
    padding: var(--spacing-5); text-align: center; cursor: pointer;
    transition: border-color .2s, background .2s; background: var(--color-surface);
}
.upg-drop-zone:hover { border-color: var(--color-primary); background: #f0f9ec; }
.upg-drop-icon { font-size: 2rem; pointer-events: none; }
.upg-drop-label { font-size: .875rem; color: var(--color-text-muted); pointer-events: none; }
.upg-drop-input {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    opacity: 0; cursor: pointer; font-size: 0;
}
.upg-drop-filename { font-weight: 600; color: var(--color-primary); font-size: .9rem; pointer-events: none; }

/* ── History ── */
.upg-history { margin-bottom: var(--spacing-5); }
.upg-section-title {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: var(--color-text-muted); margin-bottom: var(--spacing-3);
}
.upg-history-row {
    display: flex; gap: var(--spacing-4); align-items: center;
    padding: var(--spacing-2) 0; border-bottom: 1px solid var(--color-border);
    font-size: .875rem;
}
.upg-history-date { min-width: 130px; }

/* ── Result ── */
.upg-result {
    background: linear-gradient(135deg, #e8f5e1, #f0f9ec);
    border: 1.5px solid rgba(45,106,79,.25);
    border-radius: var(--radius-xl); padding: var(--spacing-5);
    margin-bottom: var(--spacing-5); text-align: center;
    box-shadow: var(--shadow);
}
.upg-result-icon   { font-size: 2.5rem; margin-bottom: var(--spacing-2); }
.upg-result-title  { font-size: 1.4rem; font-weight: 800; color: var(--color-primary); margin-bottom: 2px; }
.upg-result-subtitle { font-size: .95rem; color: var(--color-text-muted); margin-bottom: var(--spacing-2); }
.upg-result-meta   { font-size: .8rem; color: var(--color-text-muted); margin-bottom: var(--spacing-3); }
.upg-result-changelog { text-align: left; margin-top: var(--spacing-3); border-top: 1px solid rgba(45,106,79,.15); padding-top: var(--spacing-3); }
.upg-result-line   { display: flex; gap: var(--spacing-2); font-size: .875rem; padding: 2px 0; }

/* ── Changelog ── */
.upg-changelog-entry {
    border: 1px solid var(--color-border); border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-3); overflow: hidden;
}
.upg-changelog-entry--current { border-color: var(--color-primary); }
.upg-changelog-header {
    display: flex; align-items: center; gap: var(--spacing-3); flex-wrap: wrap;
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--color-surface); border-bottom: 1px solid var(--color-border);
}
.upg-changelog-ver   { font-size: .85rem; font-weight: 800; color: var(--color-primary); }
.upg-changelog-title { font-size: .875rem; font-weight: 600; flex: 1; }
.upg-changelog-date  { font-size: .75rem; }
.upg-changelog-badge {
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    background: var(--color-primary); color: #fff;
    padding: 2px 8px; border-radius: 999px;
}
.upg-changelog-list { list-style: none; padding: var(--spacing-3) var(--spacing-4); margin: 0; }
.upg-changelog-list li { display: flex; gap: var(--spacing-2); font-size: .85rem; padding: 3px 0; align-items: flex-start; }
.upg-changelog-tag {
    font-size: .68rem; font-weight: 700; color: var(--color-text-muted);
    white-space: nowrap; padding-top: 2px;
}
</style>

<script>
(function () {
    // ── GitHub "Update Now" button ──
    var updateBtn   = document.getElementById('updateNowBtn');
    var progressWrap = document.getElementById('upgProgress');
    var progressFill = document.getElementById('upgProgressFill');
    var progressText = document.getElementById('upgProgressText');
    var errorBox     = document.getElementById('upgError');

    if (updateBtn) {
        updateBtn.addEventListener('click', function () {
            updateBtn.disabled = true;
            updateBtn.textContent = '⏳ Updating…';
            progressWrap.style.display = 'block';
            errorBox.style.display = 'none';

            // Animate progress during wait (server-side takes ~5-30s)
            var fakeProgress = 10;
            progressFill.style.width = fakeProgress + '%';
            progressText.textContent = 'Downloading from GitHub…';
            var ticker = setInterval(function () {
                if (fakeProgress < 80) {
                    fakeProgress += Math.random() * 6;
                    progressFill.style.width = Math.min(fakeProgress, 80) + '%';
                }
            }, 800);

            var fd = new FormData();
            fd.append('_token', '<?= e(\App\Support\CSRF::getToken()) ?>');

            var xhr = new XMLHttpRequest();
            xhr.addEventListener('load', function () {
                clearInterval(ticker);
                updateBtn.disabled = false;
                updateBtn.textContent = 'Update Now';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        progressFill.style.width = '100%';
                        progressText.textContent = '✅ Done! Reloading…';
                        setTimeout(function () {
                            window.location.href = (window.APP_BASE || '') + (res.redirect || '/settings/upgrade');
                        }, 900);
                    } else {
                        progressWrap.style.display = 'none';
                        errorBox.textContent = res.message || 'Update failed. Try again.';
                        errorBox.style.display = 'block';
                    }
                } catch (ex) {
                    progressWrap.style.display = 'none';
                    errorBox.textContent = 'Unexpected server response. Check error logs.';
                    errorBox.style.display = 'block';
                }
            });
            xhr.addEventListener('error', function () {
                clearInterval(ticker);
                updateBtn.disabled = false;
                updateBtn.textContent = 'Update Now';
                progressWrap.style.display = 'none';
                errorBox.textContent = 'Network error. Check your connection and try again.';
                errorBox.style.display = 'block';
            });
            xhr.open('POST', '<?= url('/settings/upgrade/github') ?>');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(fd);
        });
    }

    // ── Manual ZIP upload ──
    var input        = document.getElementById('upgradeFile');
    var dropZone     = document.getElementById('dropZone');
    var fileLabel    = document.getElementById('dropZoneFilename');
    var submitWrap   = document.getElementById('upgradeSubmit');
    var uploadBtn    = document.getElementById('upgradeBtn');
    var uploadProg   = document.getElementById('uploadProgress');
    var uploadBar    = document.getElementById('uploadProgressBar');
    var uploadText   = document.getElementById('uploadProgressText');

    if (input) {
        input.addEventListener('change', function () {
            if (this.files[0]) showFile(this.files[0].name);
        });
        dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.style.borderColor = 'var(--color-primary)'; });
        dropZone.addEventListener('dragleave', function ()  { dropZone.style.borderColor = ''; });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault(); dropZone.style.borderColor = '';
            var f = e.dataTransfer.files[0];
            if (f) { try { var dt = new DataTransfer(); dt.items.add(f); input.files = dt.files; } catch(ex){} showFile(f.name); }
        });

        document.getElementById('upgradeForm').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!input.files[0]) return;
            var fd = new FormData(this);
            uploadBtn.disabled = true; uploadBtn.textContent = '⏳ Uploading…';
            uploadProg.style.display = 'block';
            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (ev) {
                if (!ev.lengthComputable) return;
                var pct = Math.round(ev.loaded / ev.total * 100);
                uploadBar.style.width = pct + '%';
                uploadText.textContent = pct < 100 ? 'Uploading… ' + pct + '%' : 'Installing…';
            });
            xhr.addEventListener('load', function () {
                uploadBtn.disabled = false; uploadBtn.textContent = '🚀 Apply ZIP';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        uploadText.textContent = '✅ Done! Reloading…';
                        setTimeout(function () { window.location.href = (window.APP_BASE || '') + (res.redirect || '/settings/upgrade'); }, 900);
                    } else {
                        uploadProg.style.display = 'none';
                        errorBox.textContent = res.message || 'Upload failed.';
                        errorBox.style.display = 'block';
                    }
                } catch (ex) {
                    uploadProg.style.display = 'none';
                    errorBox.textContent = 'Unexpected server response.';
                    errorBox.style.display = 'block';
                }
            });
            xhr.addEventListener('error', function () {
                uploadBtn.disabled = false; uploadBtn.textContent = '🚀 Apply ZIP';
                uploadProg.style.display = 'none';
                errorBox.textContent = 'Network error.';
                errorBox.style.display = 'block';
            });
            xhr.open('POST', this.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(fd);
        });
    }

    function showFile(name) {
        fileLabel.textContent = '✅ ' + name;
        fileLabel.style.display = 'block';
        submitWrap.style.display = 'block';
        dropZone.style.borderColor = 'var(--color-primary)';
        dropZone.style.background  = '#f0f9ec';
    }
}());
</script>
