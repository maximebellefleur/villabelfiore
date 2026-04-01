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
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
                <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#888;margin-bottom:4px">Current version</div>
                <div style="font-size:2.5rem;font-weight:800;color:var(--color-primary,#2d5a27);line-height:1">v<?= e($currentVersion) ?></div>
                <div style="font-size:1rem;color:#555;margin-top:2px"><?= e($currentName) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Apply Update -->
<div class="card" style="margin-bottom:var(--spacing-4)">
    <div class="card-body">
        <h2 class="card-title">Apply Update</h2>

        <?php if (!$zipSupported): ?>
        <div class="alert alert-warning">
            ⚠️ <strong>ZipArchive not available</strong> on this server. Update manually — see <code>docs/UPDATE.md</code>.
        </div>
        <?php else: ?>

        <!-- Option A: Direct from GitHub -->
        <div style="background:#f0f7f0;border:1px solid #b6d9b6;border-radius:8px;padding:20px 20px 16px;margin-bottom:24px">
            <div style="font-weight:700;font-size:1rem;margin-bottom:6px">⚡ Apply directly from GitHub</div>
            <p style="font-size:.875rem;color:#555;margin:0 0 14px">One click — the server downloads and applies the latest update automatically. Requires a published GitHub release with <code>rooted-cpanel-update.zip</code> attached.</p>
            <button type="button" class="btn btn-primary" id="githubApplyBtn" style="margin-right:8px">
                🔄 Apply Latest Update from GitHub
            </button>
            <div id="githubApplyStatus" style="display:none;margin-top:12px">
                <div style="height:8px;background:#d0e8d0;border-radius:999px;overflow:hidden;margin-bottom:6px">
                    <div id="githubApplyBar" style="height:100%;background:var(--color-primary,#2d5a27);border-radius:999px;width:0;transition:width .3s"></div>
                </div>
                <div id="githubApplyText" style="font-size:.875rem;color:#555"></div>
            </div>
        </div>

        <!-- Option B: Manual upload -->
        <div style="font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;color:#888;margin-bottom:12px">— or upload a ZIP manually —</div>

        <form method="POST" action="<?= url('/settings/upgrade/upload') ?>"
              enctype="multipart/form-data" id="upgradeForm">
            <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

            <!-- Drop zone — critical layout styles inline so they work before CSS update is applied -->
            <div id="dropZone"
                 style="position:relative;border:2px dashed #ccc;border-radius:10px;min-height:140px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:28px 20px;text-align:center;cursor:pointer;overflow:hidden;transition:border-color .2s,background .2s;background:#fafafa">
                <div style="font-size:2.5rem;line-height:1;pointer-events:none">📦</div>
                <div style="color:#666;font-size:.9rem;pointer-events:none">
                    Drop <code>rooted-cpanel-update.zip</code> here, or
                </div>
                <div style="background:#fff;border:1.5px solid #ccc;border-radius:6px;padding:7px 18px;font-size:.875rem;font-weight:600;color:#333;pointer-events:none">
                    Browse files…
                </div>
                <!-- The actual file input covers the whole zone but is invisible -->
                <input type="file" name="upgrade_zip" id="upgradeFile" accept=".zip" required
                       style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;font-size:0">
                <div id="dropZoneFilename" style="display:none;font-weight:600;color:var(--color-primary,#2d5a27);font-size:.9rem;pointer-events:none"></div>
            </div>

            <!-- Submit — shown after file selected; rendered OUTSIDE the drop zone so file input can't cover it -->
            <div id="upgradeSubmit" style="display:none;margin-top:20px;display:none">
                <button type="submit" class="btn btn-primary" id="upgradeBtn"
                        style="padding:14px 28px;font-size:1rem">
                    🚀 Apply Update
                </button>
                <p style="font-size:.8rem;color:#888;margin:8px 0 0">Your .env, database and storage/ are never touched.</p>
            </div>

            <!-- Progress -->
            <div id="uploadProgress" style="display:none;margin-top:16px">
                <div style="height:8px;background:#e0e0e0;border-radius:999px;overflow:hidden;margin-bottom:6px">
                    <div id="uploadProgressBar" style="height:100%;background:var(--color-primary,#2d5a27);border-radius:999px;width:0;transition:width .3s"></div>
                </div>
                <div id="uploadProgressText" style="font-size:.875rem;color:#666">Uploading…</div>
            </div>

            <!-- Error -->
            <div id="upgradeError" style="display:none;margin-top:12px;background:#fde;border:1px solid #f99;border-radius:6px;padding:10px 14px;font-size:.875rem;color:#c00"></div>
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
                label.textContent = '✅ ' + name + ' ready to upload';
                label.style.display = 'block';
                submit.style.display = 'block';
                dropZone.style.borderColor = '#4caf50';
                dropZone.style.background  = '#f0faf0';
            }

            input.addEventListener('change', function() {
                if (this.files[0]) showFile(this.files[0].name);
            });

            dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.style.borderColor = '#4a7c59'; });
            dropZone.addEventListener('dragleave', function()  { dropZone.style.borderColor = '#ccc'; });
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.style.borderColor = '#ccc';
                var files = e.dataTransfer.files;
                if (files[0]) {
                    try { var dt = new DataTransfer(); dt.items.add(files[0]); input.files = dt.files; } catch(ex) {}
                    showFile(files[0].name);
                }
            });

            // XHR submit with progress bar
            document.getElementById('upgradeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (!input.files[0]) return;
                var fd = new FormData(this);
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
                    btn.disabled = false; btn.textContent = '🚀 Apply Update';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            progressText.textContent = '✅ Done! Reloading…';
                            setTimeout(function() { window.location.href = (window.APP_BASE || '') + (res.redirect || '/settings/upgrade'); }, 900);
                        } else {
                            progressWrap.style.display = 'none';
                            errorBox.textContent = res.message || 'Upgrade failed.';
                            errorBox.style.display = 'block';
                        }
                    } catch(ex) {
                        progressWrap.style.display = 'none';
                        errorBox.textContent = 'Unexpected server response. Check logs.';
                        errorBox.style.display = 'block';
                    }
                });
                xhr.addEventListener('error', function() {
                    btn.disabled = false; btn.textContent = '🚀 Apply Update';
                    progressWrap.style.display = 'none';
                    errorBox.textContent = 'Network error. Check your connection.';
                    errorBox.style.display = 'block';
                });
                xhr.open('POST', this.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(fd);
            });

            // GitHub direct apply
            document.getElementById('githubApplyBtn').addEventListener('click', function() {
                var btn2    = this;
                var status  = document.getElementById('githubApplyStatus');
                var bar     = document.getElementById('githubApplyBar');
                var text    = document.getElementById('githubApplyText');
                var errBox  = document.getElementById('upgradeError');

                btn2.disabled = true;
                btn2.textContent = '⏳ Connecting to GitHub…';
                status.style.display = 'block';
                bar.style.width = '20%';
                text.textContent = 'Downloading latest update from GitHub…';
                errBox.style.display = 'none';

                var fd = new FormData();
                fd.append('_token', '<?= e(\App\Support\CSRF::getToken()) ?>');

                var xhr = new XMLHttpRequest();
                xhr.addEventListener('load', function() {
                    btn2.disabled = false;
                    btn2.textContent = '🔄 Apply Latest Update from GitHub';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            bar.style.width = '100%';
                            text.textContent = '✅ Done! Reloading…';
                            setTimeout(function() { window.location.href = (window.APP_BASE || '') + (res.redirect || '/settings/upgrade'); }, 900);
                        } else {
                            status.style.display = 'none';
                            errBox.textContent = res.message || 'Update from GitHub failed.';
                            errBox.style.display = 'block';
                        }
                    } catch(ex) {
                        status.style.display = 'none';
                        errBox.textContent = 'Unexpected server response.';
                        errBox.style.display = 'block';
                    }
                });
                xhr.addEventListener('error', function() {
                    btn2.disabled = false;
                    btn2.textContent = '🔄 Apply Latest Update from GitHub';
                    status.style.display = 'none';
                    errBox.textContent = 'Network error contacting server.';
                    errBox.style.display = 'block';
                });
                xhr.open('POST', '<?= url('/settings/upgrade/github') ?>');
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
