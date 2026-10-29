<?php
/**
 * Teaching Load partial: page header
 */
?>
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1><i class="fas fa-layer-group text-sms-primary me-2"></i><?= e($teachingLoadHeading ?? 'Teaching Load') ?></h1>
        <p class="text-muted mb-0"><?= e($teachingLoadSubtitle ?? 'Create, review, validate, approve, and acknowledge teaching loads.') ?></p>
    </div>
    <?php if ($vpaaRequired): ?><span class="badge text-bg-info">VPAA approval required</span><?php else: ?><span class="badge text-bg-secondary"></span><?php endif; ?>
</div>

<?php if ($databaseError !== ''): ?>
<div class="alert alert-danger" role="alert"><?= e($databaseError) ?></div>
<?php endif; ?>