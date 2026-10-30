<?php
/**
 * Teaching Load partial: request queue shown to every role
 */
$isDeanView = in_array($roleKey, $deanRoles, true);
$isRegistrarView = in_array($roleKey, $registrarRoles, true);

// Render Toast Notifications (Success & Error)
if (!empty($successMessage)) {
    echo '<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"><div class="toast show align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><i class="fas fa-check-circle me-2"></i> ' . e($successMessage) . '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>';
}
if (!empty($errorMessage)) {
    echo '<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"><div class="toast show align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body"><i class="fas fa-exclamation-triangle me-2"></i> ' . e($errorMessage) . '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div></div>';
}

$pendingRequests = [];
$deanApprovedRequests = [];
$registrarValidatedRequests = [];

if ($isDeanView || $isRegistrarView) {
    foreach ($requests as $request) {
        $currentStage = $request['workflow_stage'];
        if ($isDeanView && $currentStage === 'dean_review') $pendingRequests[] = $request;
        elseif ($isRegistrarView && $currentStage === 'registrar_validation') $pendingRequests[] = $request;
    }
    if ($pdo instanceof PDO) {
        try {
            if ($isDeanView) {
                $historySql = "SELECT r.load_request_id, r.faculty_id, r.term_id, r.total_units, r.status, r.workflow_stage, r.submitted_at, f.first_name, f.last_name, f.faculty_no, t.academic_year, t.semester FROM teaching_load_requests r JOIN faculty f ON f.faculty_id = r.faculty_id JOIN academic_terms t ON t.term_id = r.term_id JOIN teaching_load_workflow_history h ON h.load_request_id = r.load_request_id WHERE h.action = 'dean_approved' ORDER BY h.created_at DESC";
                $deanApprovedRequests = $pdo->query($historySql)->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($isRegistrarView) {
                $historySql = "SELECT r.load_request_id, r.faculty_id, r.term_id, r.total_units, r.status, r.workflow_stage, r.submitted_at, f.first_name, f.last_name, f.faculty_no, t.academic_year, t.semester FROM teaching_load_requests r JOIN faculty f ON f.faculty_id = r.faculty_id JOIN academic_terms t ON t.term_id = r.term_id JOIN teaching_load_workflow_history h ON h.load_request_id = r.load_request_id WHERE h.action = 'registrar_validated' ORDER BY h.created_at DESC";
                $registrarValidatedRequests = $pdo->query($historySql)->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) { error_log("Role history query failed: " . $e->getMessage()); }
    }
} else {
    $pendingRequests = $requests;
}

function renderHistoryCard($pdo, $request, $stageLabels, $modalPrefix) {
    $requestId = (int) $request['load_request_id'];
    $requestStage = (string) $request['workflow_stage'];
    $submittedTimestamp = strtotime((string) $request['submitted_at']);
    $submittedLabel = $submittedTimestamp ? date('M j, Y', $submittedTimestamp) : (string) $request['submitted_at'];
    $facultyInitials = strtoupper(substr(trim((string) $request['first_name']), 0, 1) . substr(trim((string) $request['last_name']), 0, 1));
    $itemStmt = $pdo->prepare('SELECT s.code, s.title, cs.section, cs.units, cs.day_pattern, cs.time_start, cs.time_end FROM teaching_load_request_items i JOIN class_schedules cs ON cs.class_schedule_id = i.class_schedule_id JOIN subjects s ON s.subject_id = cs.subject_id WHERE i.load_request_id = ?');
    $itemStmt->execute([$requestId]);
    $historyItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <article class="dean-clean-history" style="background: var(--sms-surface); border: 1px solid var(--sms-border);">
        <div class="dean-clean-history__identity">
            <div class="teaching-load-item__avatar" style="width: 42px; height: 42px; font-size: .85rem;"><?= e($facultyInitials) ?></div>
            <div class="dean-clean-card__details">
                <h3 class="dean-clean-card__title" style="color: var(--sms-heading);"><?= e(trim($request['first_name'] . ' ' . $request['last_name'])) ?></h3>
                <div class="dean-clean-card__subtitle" style="color: var(--sms-text-muted);"><?= e($request['faculty_no']) ?> · <?= e($request['academic_year'] . ' · ' . $request['semester']) ?></div>
            </div>
        </div>
        <div class="dean-clean-history__stats">
            <span class="badge text-bg-light border text-dark"><?= count($historyItems) ?> Subjects</span>
            <span class="badge text-bg-light border text-dark"><?= e(number_format((float) $request['total_units'], 1)) ?> Units</span>
            <span class="badge text-bg-<?= $requestStage === 'completed' ? 'success' : 'primary' ?>"><?= e($stageLabels[$requestStage] ?? $requestStage) ?></span>
        </div>
        <div class="dean-clean-history__actions">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= $modalPrefix ?>Modal<?= $requestId ?>"><i class="fas fa-eye me-1"></i> View</button>
        </div>
    </article>
    <div class="modal fade" id="<?= $modalPrefix ?>Modal<?= $requestId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content dean-modal" style="background: var(--sms-surface); border: 1px solid var(--sms-border);">
                <div class="modal-header" style="border-bottom: 1px solid var(--sms-border);">
                    <h5 class="modal-title" style="color: var(--sms-heading);"><i class="fas fa-file-invoice text-sms-primary me-2"></i>Teaching Load Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="dean-modal-banner" style="background: var(--sms-surface-muted); border: 1px solid var(--sms-border);">
                        <div class="teaching-load-item__avatar" style="width: 50px; height: 50px;"><?= e($facultyInitials) ?></div>
                        <div>
                            <h4 class="dean-clean-card__title" style="color: var(--sms-heading);"><?= e(trim($request['first_name'] . ' ' . $request['last_name'])) ?></h4>
                            <div class="dean-clean-card__subtitle" style="color: var(--sms-text-muted);"><?= e($request['faculty_no']) ?> · <?= e($request['academic_year'] . ' · ' . $request['semester']) ?></div>
                        </div>
                        <div class="ms-auto text-end">
                            <div style="color: var(--sms-text-muted); font-size: .75rem;">Processed on</div>
                            <strong style="font-size: .9rem; color: var(--sms-heading);"><?= e($submittedLabel) ?></strong>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-3 mt-4" style="color: var(--sms-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Subjects</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="border-color: var(--sms-border);">
                            <thead style="background: var(--sms-surface-muted); color: var(--sms-text-muted);">
                                <tr><th style="border-color: var(--sms-border);">Code</th><th style="border-color: var(--sms-border);">Subject Title</th><th style="border-color: var(--sms-border);">Section</th><th style="border-color: var(--sms-border);">Units</th><th style="border-color: var(--sms-border);">Schedule</th></tr>
                            </thead>
                            <tbody style="color: var(--sms-text);">
                                <?php foreach ($historyItems as $item): ?>
                                    <tr>
                                        <td class="fw-bold" style="border-color: var(--sms-border);"><?= e($item['code']) ?></td>
                                        <td style="border-color: var(--sms-border);"><?= e($item['title']) ?></td>
                                        <td style="border-color: var(--sms-border);"><?= e($item['section']) ?></td>
                                        <td style="border-color: var(--sms-border);"><?= e(number_format((float)$item['units'], 1)) ?></td>
                                        <td style="border-color: var(--sms-border);"><?php if ($item['day_pattern']): ?><span class="badge text-bg-light border text-dark"><?= e($item['day_pattern']) ?></span> <?= $item['time_start'] ? date('g:i A', strtotime($item['time_start'])) . ' - ' . date('g:i A', strtotime($item['time_end'])) : '' ?><?php else: ?><span class="text-muted">TBA</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--sms-border);"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>
    <?php
}
?>

<section class="teaching-load-section" aria-labelledby="teachingLoadQueueTitle">
    <div class="teaching-load-section__head">
        <div><h2 class="h6 mb-0" id="teachingLoadQueueTitle"><i class="fas fa-list-check text-sms-primary me-2" aria-hidden="true"></i><?php if (in_array($roleKey, $chairRoles, true)): ?>My submitted loads<?php elseif ($isDeanView): ?>Awaiting Dean review<?php elseif ($isRegistrarView): ?>Awaiting Registrar validation<?php elseif ($roleKey === 'vpaa'): ?>Awaiting VPAA approval<?php else: ?>My teaching loads<?php endif; ?></h2></div>
        <span class="badge rounded-pill text-bg-secondary"><?= count($pendingRequests) ?> request<?= count($pendingRequests) === 1 ? '' : 's' ?></span>
    </div>

    <?php if ($pendingRequests === []): ?>
        <div class="teaching-load-empty" style="background: var(--sms-surface); border: 1px dashed var(--sms-border); color: var(--sms-text-muted);"><i class="fas fa-inbox d-block fs-4 mb-2" aria-hidden="true"></i><p class="mb-0">No teaching-load requests are available in this queue.</p></div>
    <?php else: ?>
        <div class="teaching-load-list">
            <?php foreach ($pendingRequests as $request): ?>
                <?php
                $requestId = (int) $request['load_request_id'];
                $requestStage = (string) $request['workflow_stage'];
                $canAct = ($roleStage !== null && $requestStage === $roleStage) || ($roleKey === 'faculty' && $requestStage === 'faculty_acknowledgement' && (int) $request['faculty_user_id'] === $userId);
                $submittedTimestamp = strtotime((string) $request['submitted_at']);
                $submittedLabel = $submittedTimestamp ? date('M j, Y · g:i A', $submittedTimestamp) : (string) $request['submitted_at'];
                $requestBadge = $requestStage === 'completed' ? 'success' : ($requestStage === 'rejected' ? 'danger' : 'warning');
                $facultyInitials = strtoupper(substr(trim((string) $request['first_name']), 0, 1) . substr(trim((string) $request['last_name']), 0, 1));
                ?>

                <?php if ($isDeanView || $isRegistrarView): ?>
                    <!-- DEAN / REGISTRAR CARD -->
                    <article class="dean-clean-card mb-4" style="background: var(--sms-surface); border: 1px solid var(--sms-border);">
                        <div class="dean-clean-card__grid" style="grid-template-columns: 250px 1fr 320px;">
                            <div class="dean-clean-card__col dean-clean-card__col--faculty">
                                <div class="teaching-load-item__avatar"><?= e($facultyInitials) ?></div>
                                <div class="dean-clean-card__details">
                                    <h3 class="dean-clean-card__title" style="color: var(--sms-heading);"><?= e(trim($request['first_name'] . ' ' . $request['last_name'])) ?></h3>
                                    <div class="dean-clean-card__subtitle" style="color: var(--sms-text-muted);"><?= e($request['faculty_no']) ?></div>
                                    <div class="dean-clean-card__subtitle text-sms-primary"><?= e($request['academic_year'] . ' · ' . $request['semester']) ?></div>
                                    <div class="mt-1"><span class="badge text-bg-secondary" style="font-size:0.65rem;">Regular</span></div>
                                </div>
                            </div>
                            <div class="dean-clean-card__col dean-clean-card__col--subjects" style="display:flex; flex-direction:column; justify-content:center; padding: 0 2rem; border-left: 1px solid var(--sms-border); border-right: 1px solid var(--sms-border);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="dean-clean-card__label" style="margin-bottom: 0;">Requested Teaching Units</div>
                                    <?php if ($request['total_units'] > 18): ?><span class="badge text-bg-danger" style="font-size:0.65rem;">Overload</span><?php endif; ?>
                                </div>
                                <div class="d-flex align-items-baseline gap-2 mb-2">
                                    <h2 class="mb-0 text-dark fw-bold" style="font-size: 1.75rem; color: var(--sms-heading) !important;"><?= e(number_format((float) $request['total_units'], 1)) ?></h2>
                                    <span class="text-muted mt-1" style="font-size: 0.85rem;">units</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 text-muted" style="font-size: 0.7rem;">
                                    <span><i class="fas fa-check-circle text-success me-1"></i> Load Assignment</span>
                                    <span><i class="fas fa-check-circle text-success me-1"></i> Unit Calculation</span>
                                </div>
                            </div>
                            <div class="dean-clean-card__col dean-clean-card__col--actions">
                                <div class="dean-clean-card__meta mb-1">
                                    <span class="text-muted" style="font-size:0.75rem;">Status</span>
                                    <span class="badge text-bg-<?= $requestBadge ?>" style="font-size:0.65rem;"><?= e($stageLabels[$requestStage] ?? $requestStage) ?></span>
                                </div>
                                <?php if ($canAct): ?>
                                    <form method="post" class="dean-clean-card__form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="load_request_id" value="<?= $requestId ?>">
                                        <textarea class="form-control form-control-sm" name="remarks" rows="1" style="font-size:0.75rem; padding:0.25rem; background: var(--sms-input-bg); color: var(--sms-text); border-color: var(--sms-border);" placeholder="Remarks..."></textarea>
                                        <div class="d-flex gap-2">
                                            <?php if ($isDeanView): ?><button class="btn btn-sm btn-success w-50" style="font-size:0.75rem; padding:0.25rem;" name="action" value="dean_approve" type="submit">Approve</button>
                                            <?php else: ?><button class="btn btn-sm btn-primary w-50" style="font-size:0.75rem; padding:0.25rem;" name="action" value="registrar_validate" type="submit">Validate</button><?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger w-50" style="font-size:0.75rem; padding:0.25rem;" name="action" value="reject" type="submit" onclick="return confirm('Reject this load?');">Reject</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>

                <?php else: ?>
                    <!-- OTHER ROLES (Chair, Faculty, VPAA) -->
                    <article class="teaching-load-item teaching-load-item--<?= e($requestStage) ?>" style="background: var(--sms-surface); border: 1px solid var(--sms-border); border-left: 4px solid var(--sms-primary); border-radius: .5rem; padding: 1.25rem; margin-bottom: 1rem;">
                        <div class="row g-3 align-items-center">
                            
                            <!-- Col 1: Identity -->
                            <div class="col-12 col-lg-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="teaching-load-item__avatar" style="width: 48px; height: 48px; font-size: 1rem; background: var(--sms-primary-xlight); color: var(--sms-primary);" aria-hidden="true"><?= e($facultyInitials) ?></div>
                                    <div>
                                        <h3 class="h6 mb-1 fw-bold" style="color: var(--sms-heading);"><?= e(trim($request['first_name'] . ' ' . $request['last_name'])) ?></h3>
                                        <div class="text-muted" style="font-size: .8rem;"><?= e($request['faculty_no']) ?></div>
                                        <div style="color: var(--sms-text); font-size: .8rem;"><?= e($request['academic_year'] . ' · ' . $request['semester']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Col 2: Total Units -->
                            <div class="col-12 col-lg-3">
                                <div class="p-2 px-3 rounded" style="background: var(--sms-surface-muted); border: 1px solid var(--sms-border);">
                                    <div class="text-muted mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Units</div>
                                    <div class="fw-bold" style="color: var(--sms-heading); font-size: 1.1rem;"><?= e(number_format((float) $request['total_units'], 1)) ?> <span style="font-size: 0.8rem; font-weight: normal; color: var(--sms-text-muted);">units</span></div>
                                </div>
                            </div>

                            <!-- Col 3: Details & Actions -->
                            <div class="col-12 col-lg-5">
                                <!-- ADDED BORDER-LEFT FOR VISUAL SPACING -->
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 ps-lg-4" style="border-left: 1px solid var(--sms-border);">
                                    <div>
                                        <div class="text-muted mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: 0.5px;">Request Details</div>
                                        <div class="mb-1"><span class="badge text-bg-<?= $requestBadge ?> rounded-pill px-2" style="font-size: 0.65rem;"><?= e($stageLabels[$requestStage] ?? $requestStage) ?></span></div>
                                        <div class="text-muted" style="font-size: .75rem;">Submitted <?= e($submittedLabel) ?></div>
                                    </div>
                                    
                                    <?php if ($canAct): ?>
                                        <form method="post" class="d-flex flex-column gap-2" style="min-width: 140px;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="load_request_id" value="<?= $requestId ?>">
                                            <input type="text" class="form-control form-control-sm" name="remarks" placeholder="Remarks..." style="font-size: .75rem; background: var(--sms-input-bg); color: var(--sms-text); border-color: var(--sms-border);">
                                            
                                            <?php if ($roleKey === 'faculty'): ?>
                                                <button class="btn btn-sm btn-success w-100" name="action" value="faculty_acknowledge" type="submit" style="font-size: .75rem;">Acknowledge</button>
                                            <?php elseif ($roleKey === 'vpaa'): ?>
                                                <button class="btn btn-sm btn-success w-100" name="action" value="vpaa_approve" type="submit" style="font-size: .75rem;">Final Approval</button>
                                            <?php endif; ?>
                                            
                                            <?php if ($roleStage !== null && !$isDeanView && !$isRegistrarView): ?>
                                                <button class="btn btn-sm btn-outline-danger w-100" name="action" value="reject" type="submit" style="font-size: .75rem;">Reject</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        </div>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($isDeanView): ?>
    <section class="teaching-load-section" aria-labelledby="historyTitle" style="margin-top: 3rem;">
        <div class="teaching-load-section__head">
            <div><h2 class="h6 mb-0"><i class="fas fa-check-double text-success me-2"></i>History of Approved by Dean</h2></div>
            <span class="badge rounded-pill text-bg-success"><?= count($deanApprovedRequests) ?> approved</span>
        </div>
        <?php if ($deanApprovedRequests === []): ?>
            <div class="teaching-load-empty" style="background: var(--sms-surface); border: 1px dashed var(--sms-border);"><p class="mb-0">No approved loads found.</p></div>
        <?php else: ?>
            <div class="teaching-load-list">
                <?php foreach ($deanApprovedRequests as $request) renderHistoryCard($pdo, $request, $stageLabels, 'dean'); ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($isRegistrarView): ?>
    <section class="teaching-load-section" aria-labelledby="historyTitle" style="margin-top: 3rem;">
        <div class="teaching-load-section__head">
            <div><h2 class="h6 mb-0"><i class="fas fa-check-double text-success me-2"></i>History of Validated by Registrar</h2></div>
            <span class="badge rounded-pill text-bg-success"><?= count($registrarValidatedRequests) ?> validated</span>
        </div>
        <?php if ($registrarValidatedRequests === []): ?>
            <div class="teaching-load-empty" style="background: var(--sms-surface); border: 1px dashed var(--sms-border);"><p class="mb-0">No validated loads found.</p></div>
        <?php else: ?>
            <div class="teaching-load-list">
                <?php foreach ($registrarValidatedRequests as $request) renderHistoryCard($pdo, $request, $stageLabels, 'registrar'); ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>