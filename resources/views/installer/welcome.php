<?php $layout = 'installer'; ?>
<div class="installer-card">
    <h2>Welcome to Rooted Installation</h2>
    <p>Before we begin, let's verify your server meets the requirements.</p>

    <table class="table">
        <thead>
            <tr><th>Check</th><th>Status</th><th>Detail</th></tr>
        </thead>
        <tbody>
            <?php foreach ($checks as $check): ?>
            <tr>
                <td><?= e($check['check']) ?></td>
                <td>
                    <?php if ($check['passed']): ?>
                        <span class="badge badge-success">Pass</span>
                    <?php else: ?>
                        <span class="badge badge-error">Fail</span>
                    <?php endif; ?>
                </td>
                <td><?= e($check['detail']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($allPassed): ?>
    <form method="POST" action="/install/step/1">
        <input type="hidden" name="_token" value="<?= e(\App\Support\CSRF::getToken()) ?>">
        <button type="submit" class="btn btn-primary">Continue to Database Setup &rarr;</button>
    </form>
    <?php else: ?>
    <div class="alert alert-error">
        Please fix the failing checks before continuing.
    </div>
    <?php endif; ?>
</div>
