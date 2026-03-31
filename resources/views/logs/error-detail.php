<div class="page-header">
    <h1 class="page-title">Error Detail</h1>
    <a href="/logs/errors" class="btn btn-secondary">&larr; Back</a>
</div>
<div class="card">
    <div class="card-body">
        <dl class="detail-list">
            <dt>Severity</dt><dd><span class="badge badge-severity-<?= e($log['severity']) ?>"><?= e(strtoupper($log['severity'])) ?></span></dd>
            <dt>Module</dt><dd><?= e($log['module']) ?></dd>
            <dt>Date</dt><dd><?= e(date('d M Y H:i:s', strtotime($log['created_at']))) ?></dd>
            <dt>Message</dt><dd><?= e($log['message']) ?></dd>
            <?php if ($log['details']): ?><dt>Details</dt><dd><pre><?= e($log['details']) ?></pre></dd><?php endif; ?>
            <?php if ($log['trace_text']): ?><dt>Trace</dt><dd><pre><?= e($log['trace_text']) ?></pre></dd><?php endif; ?>
        </dl>
    </div>
</div>
