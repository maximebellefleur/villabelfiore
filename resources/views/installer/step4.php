<?php $layout = 'installer'; ?>
<div class="installer-card">
    <h2>Step 3: Storage Setup</h2>
    <form method="POST" action="<?= url('/install/step/4') ?>" class="form">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">

        <div class="form-group">
            <label class="form-label">Storage Driver</label>
            <label class="radio-label"><input type="radio" name="storage_driver" value="local" checked> Local Filesystem</label>
            <label class="radio-label"><input type="radio" name="storage_driver" value="ftp"> FTP</label>
            <label class="radio-label"><input type="radio" name="storage_driver" value="sftp"> SFTP</label>
        </div>

        <button type="submit" class="btn btn-primary">Continue &rarr;</button>
    </form>
</div>
