<div class="page-header">
    <h1 class="page-title">Settings — Upgrade</h1>
    <a href="<?= url('/settings') ?>" class="btn btn-secondary">&larr; Back to Settings</a>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<?php
$result = $_SESSION['upgrade_result'] ?? null;
unset($_SESSION['upgrade_result']);
?>

<?php if ($result): ?>
<!-- Post-upgrade summary -->
<div class="upgrade-result card">
    <div class="upgrade-result-header">
        <span class="upgrade-badge upgrade-badge--success">✅ Upgraded</span>
        <h2>v<?= e($result['from']) ?> → v<?= e($result['to']) ?></h2>
        <?php if ($result['to_name']): ?>
        <p class="upgrade-release-name"><?= e($result['to_name']) ?></p>
        <?php endif; ?>
        <p class="text-muted text-sm"><?= (int)$result['extracted'] ?> files updated &middot; <?= (int)$result['skipped'] ?> protected files skipped</p>
    </div>

    <?php if (!empty($result['new_entries'])): ?>
    <div class="upgrade-changelog">
        <h3>What changed in this update</h3>
        <?php foreach ($result['new_entries'] as $ver => $entry): ?>
        <div class="changelog-entry">
            <div class="changelog-version">v<?= e($ver) ?> — <?= e($entry['title'] ?? '') ?> <span class="changelog-date"><?= e($entry['date'] ?? '') ?></span></div>
            <?php foreach (['new' => '✨ New', 'improved' => '⚡ Improved', 'fixed' => '🐛 Fixed'] as $type => $label): ?>
                <?php if (!empty($entry[$type])): ?>
                <div class="changelog-section">
                    <strong><?= $label ?></strong>
                    <ul>
                        <?php foreach ($entry[$type] as $item): ?>
                        <li><?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-muted" style="padding:var(--spacing-4)">Already up to date — no new changelog entries for this version.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Current version -->
<div class="card" style="margin-bottom:var(--spacing-4)">
    <div class="card-body">
        <div class="upgrade-current">
            <div>
                <div class="upgrade-version-label">Current version</div>
                <div class="upgrade-version-number">v<?= e($currentVersion) ?></div>
                <div class="upgrade-version-name"><?= e($currentName) ?></div>
            </div>
            <div class="upgrade-actions">
                <a href="https://github.com/maximebellefleur/villabelfiore/raw/claude/create-rooted-project-RVbog/rooted-cpanel-update.zip"
                   class="btn btn-secondary" target="_blank" rel="noopener">
                    ⬇️ Download latest update ZIP
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Upload form -->
<div class="card" style="margin-bottom:var(--spacing-4)">
    <div class="card-body">
        <h2 class="card-title">Apply Update</h2>

        <?php if (!$zipSupported): ?>
        <div class="alert alert-warning">
            ⚠️ <strong>ZipArchive not available</strong> on this server.
            You must update manually — see <code>docs/UPDATE.md</code> for instructions.
        </div>
        <?php else: ?>

        <ol class="upgrade-steps">
            <li>Download the latest <code>rooted-cpanel-update.zip</code> using the button above</li>
            <li>Upload it here using the form below</li>
            <li>Your <code>.env</code>, database, and <code>storage/</code> folder are <strong>never touched</strong></li>
        </ol>

        <form method="POST" action="<?= url('/settings/upgrade/upload') ?>"
              enctype="multipart/form-data" class="form" id="upgradeForm">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <div class="upgrade-drop-zone" id="dropZone">
                <div class="upgrade-drop-icon">📦</div>
                <div class="upgrade-drop-text">Drop <code>rooted-cpanel-update.zip</code> here or click to browse</div>
                <input type="file" name="upgrade_zip" id="upgradeFile" accept=".zip" required
                       style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                <div id="dropZoneFilename" class="upgrade-drop-filename" style="display:none"></div>
            </div>

            <div class="upgrade-submit" id="upgradeSubmit" style="display:none">
                <button type="submit" class="btn btn-primary btn-lg" id="upgradeBtn">
                    🚀 Apply Update
                </button>
                <p class="text-muted text-sm">This will replace all code files. Your data is safe.</p>
            </div>

            <!-- Upload progress -->
            <div id="uploadProgress" style="display:none; margin-top:var(--spacing-4)">
                <div class="upgrade-progress-wrap">
                    <div class="upgrade-progress-bar" id="uploadProgressBar"></div>
                </div>
                <div id="uploadProgressText" class="text-muted text-sm" style="margin-top:6px">Uploading…</div>
            </div>
            <!-- Error box -->
            <div id="upgradeError" class="alert alert-error" style="display:none; margin-top:var(--spacing-3)"></div>
        </form>

        <script>
        (function() {
            var input    = document.getElementById('upgradeFile');
            var dropZone = document.getElementById('dropZone');
            var label    = document.getElementById('dropZoneFilename');
            var submit   = document.getElementById('upgradeSubmit');
            var btn      = document.getElementById('upgradeBtn');
            var progressWrap = document.getElementById('uploadProgress');
            var progressBar  = document.getElementById('uploadProgressBar');
            var progressText = document.getElementById('uploadProgressText');
            var errorBox     = document.getElementById('upgradeError');

            function showFile(name) {
                label.textContent = '📦 ' + name + ' ready';
                label.style.display = 'block';
                submit.style.display = 'block';
                dropZone.classList.add('upgrade-drop-zone--ready');
            }

            input.addEventListener('change', function() {
                if (this.files[0]) showFile(this.files[0].name);
            });

            // Drag and drop
            ['dragover', 'dragenter'].forEach(function(evt) {
                dropZone.addEventListener(evt, function(e) {
                    e.preventDefault();
                    dropZone.classList.add('upgrade-drop-zone--hover');
                });
            });
            dropZone.addEventListener('dragleave', function() {
                dropZone.classList.remove('upgrade-drop-zone--hover');
            });
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.classList.remove('upgrade-drop-zone--hover');
                var files = e.dataTransfer.files;
                if (files[0]) {
                    // Transfer dropped file to the hidden input
                    var dt = new DataTransfer();
                    dt.items.add(files[0]);
                    input.files = dt.files;
                    showFile(files[0].name);
                }
            });

            // XHR submit with progress
            document.getElementById('upgradeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (!input.files[0]) return;

                var form = this;
                var fd   = new FormData(form);

                btn.disabled = true;
                btn.textContent = '⏳ Uploading…';
                progressWrap.style.display = 'block';
                errorBox.style.display = 'none';

                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(ev) {
                    if (!ev.lengthComputable) return;
                    var pct = Math.round(ev.loaded / ev.total * 100);
                    progressBar.style.width = pct + '%';
                    progressText.textContent = pct < 100 ? 'Uploading… ' + pct + '%' : 'Extracting files…';
                });
                xhr.addEventListener('load', function() {
                    btn.disabled = false;
                    btn.textContent = '🚀 Apply Update';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            progressText.textContent = '✅ Done! Reloading…';
                            setTimeout(function() { window.location.href = res.redirect || window.location.href; }, 800);
                        } else {
                            progressWrap.style.display = 'none';
                            errorBox.textContent = res.message || 'Upgrade failed. Please try again.';
                            errorBox.style.display = 'block';
                        }
                    } catch(err) {
                        progressWrap.style.display = 'none';
                        errorBox.textContent = 'Unexpected server response. Check server logs.';
                        errorBox.style.display = 'block';
                    }
                });
                xhr.addEventListener('error', function() {
                    btn.disabled = false;
                    btn.textContent = '🚀 Apply Update';
                    progressWrap.style.display = 'none';
                    errorBox.textContent = 'Network error. Please check your connection and try again.';
                    errorBox.style.display = 'block';
                });
                xhr.open('POST', form.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(fd);
            });
        }());
        </script>

        <?php endif; ?>
    </div>
</div>

<!-- Upgrade history -->
<?php if (!empty($upgradeLog)): ?>
<div class="card" style="margin-bottom:var(--spacing-4)">
    <div class="card-body">
        <h2 class="card-title">Upgrade History</h2>
        <table class="table">
            <thead><tr><th>Date</th><th>From</th><th>To</th></tr></thead>
            <tbody>
            <?php foreach ($upgradeLog as $entry): ?>
            <tr>
                <td><?= e($entry['date']) ?></td>
                <td>v<?= e($entry['from']) ?></td>
                <td>v<?= e($entry['to']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Full changelog -->
<div class="card">
    <div class="card-body">
        <h2 class="card-title">Full Changelog</h2>
        <?php foreach ($changelog as $ver => $entry): ?>
        <div class="changelog-entry <?= $ver === $currentVersion ? 'changelog-entry--current' : '' ?>">
            <div class="changelog-version">
                v<?= e($ver) ?> — <?= e($entry['title'] ?? '') ?>
                <span class="changelog-date"><?= e($entry['date'] ?? '') ?></span>
                <?php if ($ver === $currentVersion): ?>
                <span class="changelog-badge">current</span>
                <?php endif; ?>
            </div>
            <?php foreach (['new' => '✨ New', 'improved' => '⚡ Improved', 'fixed' => '🐛 Fixed'] as $type => $label): ?>
                <?php if (!empty($entry[$type])): ?>
                <div class="changelog-section">
                    <strong><?= $label ?></strong>
                    <ul>
                        <?php foreach ($entry[$type] as $item): ?>
                        <li><?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
