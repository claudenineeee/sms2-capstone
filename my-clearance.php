<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
require_once __DIR__ . '/../../controllers/clearance.php';

$db = facultyDb();
$profile = $db ? facultyClearanceProfile($db, (int) getCurrentUserId()) : null;
$offices = $db ? facultyClearanceOffices($db) : [];
$sectionsMeta = facultyClearanceSections();
$term = $db ? facultyClearanceTerm($db) : null;
$clearance = ($db && $profile && $term) ? facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']) : null;
$itemByOffice = [];
foreach ($clearance['items'] ?? [] as $item) {
    $itemByOffice[(int) $item['clearance_office_id']] = $item;
}
$contractEnd = $profile['contractual_end'] ?? null;
$daysRemaining = $contractEnd && $contractEnd !== '0000-00-00'
    ? (int) floor((strtotime($contractEnd) - strtotime(date('Y-m-d'))) / 86400)
    : null;
$status = facultyClearanceStatus($clearance);

function facultyClearanceEsc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'My Clearance';
$activeModule = 'faculty';
$activePage = 'my-clearance';
$breadcrumbs = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/users/faculty/index.php'],
    ['label' => 'My Clearance', 'url' => null],
];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<?php
$cfFormSubmitted = ($clearance !== null && !empty($clearance['form_submitted']));
$cfSubmitted = ($clearance !== null && !empty($clearance['submitted_at']));
$cfFormStatus = (string) ($clearance['form_status'] ?? ($cfFormSubmitted ? 'Pending Review' : 'Not Submitted'));
$cfFormApproved = ($cfFormStatus === 'Approved');
$cfFormApprovedAt = !empty($clearance['form_approved_at']) ? date('F d, Y', strtotime($clearance['form_approved_at'])) : null;
$cfFormRemarks = (string) ($clearance['form_remarks'] ?? '');

// Clearance form prep
$cfInstitution = defined('INSTITUTION') ? INSTITUTION : 'Bestlink College of the Philippines';
$cfFullName = trim(
    ($profile['first_name'] ?? '') . ' ' .
    ($profile['middle_name'] ?? '' ? ($profile['middle_name'] . ' ') : '') .
    ($profile['last_name'] ?? '') .
    ($profile['suffix'] ?? '' ? ', ' . $profile['suffix'] : '')
);
$cfDept = (string) ($profile['designated_department'] ?? 'N/A');
$cfPosition = (string) ($profile['position'] ?? 'Faculty');
$cfEmpStatus = (string) ($profile['employment_status'] ?? 'Probationary');
$cfFacultyId = (string) ($profile['faculty_id'] ?? ($profile['id'] ?? 'N/A'));
$cfEmail = (string) ($profile['email'] ?? '');
$cfHired = !empty($profile['hired_date']) && $profile['hired_date'] !== '0000-00-00'
    ? date('F d, Y', strtotime($profile['hired_date'])) : 'N/A';
$cfContractEnd = ($contractEnd && $contractEnd !== '0000-00-00')
    ? date('F d, Y', strtotime($contractEnd)) : 'Not set';
$cfTerm = $term ? (string) ($term['term_label'] ?? ($term['name'] ?? ($term['semester'] . ' ' . $term['academic_year']))) : 'Current Term';
$cfSY = $term ? (string) ($term['school_year'] ?? ($term['academic_year'] ?? (date('Y') . '–' . (date('Y') + 1)))) : (date('Y') . '–' . (date('Y') + 1));
$cfFormNo = 'CF-' . date('Y') . '-' . str_pad((string) (int) ($profile['id'] ?? 0), 4, '0', STR_PAD_LEFT);
$cfDateSubmitted = $clearance && !empty($clearance['submitted_at'])
    ? date('F d, Y', strtotime($clearance['submitted_at'])) : '—';
$cfFormSubmittedAt = $cfFormSubmitted && !empty($clearance['form_submitted_at'])
    ? date('F d, Y', strtotime($clearance['form_submitted_at'])) : ($cfDateSubmitted !== '—' ? $cfDateSubmitted : date('F d, Y'));
$cfSignatureData = $clearance['signature_data'] ?? null;
$cfDeclarationText = $clearance['faculty_declaration'] ?? 'I hereby certify that I have completed and submitted the required documents and have returned any school propery, records, or other accountable items assigned to me.';
$cfIntentType = (string) ($clearance['intent_type'] ?? 'renewal');

// Overall clearance progress
$cfApprovedCount = (int) ($clearance['approved_items'] ?? 0);
$cfTotalCount = (int) ($clearance['total_items'] ?? count($offices));
$cfPct = $cfTotalCount > 0 ? (int) round(($cfApprovedCount / $cfTotalCount) * 100) : 0;

// Workflow lifecycle step calculation
// Steps: 1: Faculty Submit | 2: Dept Head Review | 3: Offices/Units Verification | 4: HR Final Approval | 5: Cleared
$activeLifecycleStep = 1;
if ($status === 'Cleared') {
    $activeLifecycleStep = 5;
} elseif ($status === 'For Final Approval') {
    $activeLifecycleStep = 4;
} elseif ($cfFormApproved || $status === 'Under Verification' || $cfApprovedCount > 0) {
    $activeLifecycleStep = 3;
} elseif ($cfFormSubmitted || $status === 'For Department Head Approval') {
    $activeLifecycleStep = 2;
}
?>
<style>
    /* ═══════════════════════════════════════════════════════════════
       FACULTY CLEARANCE PORTAL — Modern Clean Styles
       ═══════════════════════════════════════════════════════════════ */
    .clr-page {
        padding: 1.5rem 1.75rem;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    /* ── Workflow Lifecycle Stepper ───────────────────────────────── */
    .clr-flow-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 1rem;
        padding: 1.25rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
        position: relative;
        overflow-x: auto;
    }

    .clr-flow-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        z-index: 2;
        min-width: 110px;
    }

    .clr-flow-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        font-weight: 700;
        margin-bottom: .45rem;
        border: 2.5px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        color: var(--bs-secondary-color);
        transition: all .25s ease;
    }

    .clr-flow-step.active .clr-flow-circle {
        border-color: #0d6efd;
        background: #0d6efd;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, .18);
    }

    .clr-flow-step.completed .clr-flow-circle {
        border-color: #198754;
        background: #198754;
        color: #fff;
    }

    .clr-flow-step.deficiency .clr-flow-circle {
        border-color: #dc3545;
        background: #dc3545;
        color: #fff;
    }

    .clr-flow-title {
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--bs-secondary-color);
        line-height: 1.2;
    }

    .clr-flow-step.active .clr-flow-title {
        color: #0d6efd;
    }

    .clr-flow-step.completed .clr-flow-title {
        color: #198754;
    }

    .clr-flow-step.deficiency .clr-flow-title {
        color: #dc3545;
    }

    .clr-flow-divider {
        flex: 1;
        height: 2px;
        background: var(--bs-border-color);
        margin: 0 .5rem 1.2rem;
        position: relative;
        z-index: 1;
    }

    .clr-flow-divider.completed {
        background: #198754;
    }

    /* ── Sections & Cards ─────────────────────────────────────────── */
    .clr-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .85rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .clr-card-header {
        padding: 1rem 1.4rem;
        background: var(--bs-tertiary-bg, rgba(0, 0, 0, .02));
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .clr-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 1.1rem 1.4rem;
        padding: 1.25rem 1.4rem;
    }

    .clr-info-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        margin-bottom: .2rem;
    }

    .clr-info-value {
        font-size: .88rem;
        font-weight: 600;
        color: var(--bs-body-color);
    }

    /* ── Progress Bar ─────────────────────────────────────────────── */
    .clr-progress-bar-wrap {
        height: 10px;
        border-radius: 50px;
        background: var(--bs-border-color);
        overflow: hidden;
    }

    .clr-progress-fill {
        height: 100%;
        border-radius: 50px;
        background: linear-gradient(90deg, #1a2e5a, #0d6efd);
        transition: width .5s ease;
    }

    .clr-progress-fill.full {
        background: linear-gradient(90deg, #198754, #20c997);
    }

    /* ── Office Section Grid & Cards ──────────────────────────────── */
    .office-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .85rem;
        transition: all .2s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }

    .office-card:hover {
        box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        border-color: rgba(13, 110, 253, .3);
    }

    .office-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg, rgba(0, 0, 0, .015));
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        border-radius: .85rem .85rem 0 0;
    }

    .office-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(13, 110, 253, .08);
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .office-card-body {
        padding: 1.15rem 1.25rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: .85rem;
    }

    .office-checklist {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
        font-size: .8rem;
    }

    .office-checklist li {
        padding: .25rem 0;
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        color: var(--bs-body-color);
        line-height: 1.35;
    }

    .office-checklist li i {
        font-size: .75rem;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
        opacity: .7;
    }

    /* ── Status Badges & Chips ────────────────────────────────────── */
    .clr-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .75rem;
        border-radius: 50px;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .02em;
        white-space: nowrap;
    }

    .clr-chip-cleared {
        background: rgba(25, 135, 84, .12);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, .3);
    }

    .clr-chip-review {
        background: rgba(13, 202, 240, .12);
        color: #0aa2c0;
        border: 1px solid rgba(13, 202, 240, .3);
    }

    .clr-chip-deficiency {
        background: rgba(220, 53, 69, .12);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, .3);
    }

    .clr-chip-onhold {
        background: rgba(255, 193, 7, .15);
        color: #997404;
        border: 1px solid rgba(255, 193, 7, .35);
    }

    .clr-chip-pending {
        background: rgba(108, 117, 125, .1);
        color: var(--bs-secondary-color);
        border: 1px solid rgba(108, 117, 125, .25);
    }

    /* ── Remarks & Deficiency Alerts ──────────────────────────────── */
    .office-deficiency-box {
        padding: .75rem .9rem;
        border-radius: .5rem;
        background: rgba(220, 53, 69, .08);
        border: 1px solid rgba(220, 53, 69, .25);
        color: #842029;
        font-size: .78rem;
    }

    .office-onhold-box {
        padding: .75rem .9rem;
        border-radius: .5rem;
        background: rgba(255, 193, 7, .1);
        border: 1px solid rgba(255, 193, 7, .3);
        color: #664d03;
        font-size: .78rem;
    }

    /* ── Declaration & Action Footer ──────────────────────────────── */
    .clr-declaration {
        padding: 1.25rem 1.4rem;
        background: rgba(var(--bs-primary-rgb), .02);
        border-top: 1px solid var(--bs-border-color);
    }

    .clr-form-footer {
        padding: 1rem 1.4rem;
        border-top: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        background: var(--bs-tertiary-bg, rgba(0, 0, 0, .02));
    }

    .btn-clr-primary {
        background: linear-gradient(135deg, #1a2e5a, #0d6efd);
        color: #fff;
        border: none;
        border-radius: .5rem;
        padding: .6rem 1.5rem;
        font-size: .88rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        transition: all .2s ease;
        box-shadow: 0 3px 10px rgba(13, 110, 253, .25);
    }

    .btn-clr-primary:hover:not(:disabled) {
        opacity: .92;
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(13, 110, 253, .35);
        color: #fff;
    }

    .btn-clr-primary:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    @media print {

        .btn-clr-primary,
        .clr-flow-stepper,
        .office-upload-zone,
        #facultyAlert,
        .btn-close {
            display: none !important;
        }
    }

    /* ── Upload Zone ────────────────────────────────────────────── */
    .office-upload-zone {
        border: 2px dashed var(--bs-border-color);
        border-radius: .65rem;
        padding: 1rem;
        text-align: center;
        background: rgba(var(--bs-primary-rgb), .015);
        transition: border-color .2s ease, background .2s ease;
        cursor: default;
        position: relative;
    }

    .office-upload-zone:not(.upload-blocked):hover {
        border-color: rgba(13, 110, 253, .5);
        background: rgba(13, 110, 253, .03);
    }

    .office-upload-zone.dragover {
        border-color: #0d6efd;
        background: rgba(13, 110, 253, .07);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
    }

    .office-upload-zone.upload-blocked {
        border-style: solid;
        cursor: not-allowed;
    }

    .upload-zone-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
    }

    .upload-hint-text {
        font-size: .72rem;
        color: var(--bs-secondary-color);
        line-height: 1.35;
    }

    .btn-upload-choose {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .85rem;
        border-radius: .4rem;
        font-size: .76rem;
        font-weight: 600;
        background: rgba(13, 110, 253, .08);
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, .3);
        cursor: pointer;
        transition: all .18s ease;
        margin-top: .3rem;
        white-space: nowrap;
    }

    .btn-upload-choose:hover {
        background: rgba(13, 110, 253, .15);
        border-color: #0d6efd;
    }

    .upload-file-preview {
        display: flex;
        align-items: center;
        gap: .35rem;
        font-size: .74rem;
        color: var(--bs-body-color);
        background: rgba(220, 53, 69, .05);
        border: 1px solid rgba(220, 53, 69, .18);
        border-radius: .4rem;
        padding: .3rem .65rem;
        margin-bottom: .6rem;
        text-align: left;
        overflow: hidden;
    }

    .upload-file-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 160px;
    }

    .upload-actions {
        margin-top: .6rem;
        padding-top: .6rem;
        border-top: 1px solid var(--bs-border-color);
    }

    .upload-selected-name {
        font-size: .74rem;
        font-weight: 600;
        color: #0d6efd;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .upload-progress-wrap {
        height: 5px;
        border-radius: 50px;
        background: var(--bs-border-color);
        overflow: hidden;
        margin-top: .65rem;
    }

    .upload-progress-bar {
        height: 100%;
        border-radius: 50px;
        background: linear-gradient(90deg, #1a2e5a, #0d6efd);
        width: 0%;
        transition: width .4s ease;
        animation: uploadPulse 1.2s ease-in-out infinite;
    }

    @keyframes uploadPulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .7;
        }
    }

    /* ── Digital Signature & Declaration Box ─────────────────────── */
    .signature-pad-container {
        border: 2px dashed var(--bs-border-color);
        border-radius: .75rem;
        background: #ffffff;
        position: relative;
        overflow: hidden;
        cursor: crosshair;
        width: 100%;
        max-width: 100%;
    }

    .signature-pad-canvas {
        display: block;
        width: 100%;
        height: 140px;
        touch-action: none;
    }

    .signature-baseline {
        position: absolute;
        bottom: 30px;
        left: 20px;
        right: 20px;
        border-bottom: 1px dashed rgba(0, 0, 0, 0.2);
        pointer-events: none;
    }

    .signature-hint {
        position: absolute;
        bottom: 6px;
        left: 20px;
        font-size: 0.7rem;
        color: #888;
        pointer-events: none;
    }

    .signature-preview-img {
        max-height: 75px;
        max-width: 260px;
        object-fit: contain;
        display: block;
    }

    /* ── Clearance Agreement Form ───────────────────────── */
    .clr-conduct-doc {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.85rem;
        padding: 2.25rem 2.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    .clr-conduct-header {
        text-align: center;
        border-bottom: 2px solid rgba(var(--bs-primary-rgb), 0.35);
        padding-bottom: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .clr-conduct-title {
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--bs-body-color);
        margin-bottom: 0.35rem;
    }

    .clr-conduct-subheading {
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #0d6efd;
    }

    .clr-conduct-section-label {
        font-size: 0.92rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--bs-body-color);
        padding-bottom: 0.4rem;
        border-bottom: 1px solid var(--bs-border-color);
        margin-top: 1.75rem;
        margin-bottom: 1.2rem;
    }

    .clr-conduct-field-row {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
        font-size: 0.92rem;
    }

    .clr-conduct-label {
        font-weight: 700;
        color: var(--bs-body-color);
        white-space: nowrap;
        min-width: 175px;
    }

    .clr-conduct-underline {
        display: inline-block;
        min-width: 320px;
        max-width: 100%;
        border-bottom: 1.5px solid var(--bs-body-color);
        padding: 0 0.35rem 2px;
        font-weight: 700;
        color: #0d6efd;
    }

    .clr-conduct-inline-underline {
        display: inline;
        border-bottom: 1.5px solid var(--bs-body-color);
        padding: 0 0.15rem 1px;
        font-weight: 700;
        color: #0d6efd;
    }

    .clr-conduct-para {
        font-size: 0.95rem;
        line-height: 1.8;
        color: var(--bs-body-color);
        margin-bottom: 1.15rem;
    }

    .clr-conduct-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 1.5rem;
    }

    .clr-conduct-list li {
        position: relative;
        padding-left: 1.6rem;
        margin-bottom: 0.65rem;
        font-size: 0.92rem;
        line-height: 1.55;
        color: var(--bs-body-color);
    }

    .clr-conduct-list li::before {
        content: "•";
        position: absolute;
        left: 0.5rem;
        top: -0.1rem;
        color: #0d6efd;
        font-size: 1.25rem;
        font-weight: bold;
    }

    .clr-conduct-ack-box {
        background: rgba(13, 110, 253, 0.04);
        border: 1px solid rgba(13, 110, 253, 0.25);
        border-radius: 0.65rem;
        padding: 1rem 1.25rem;
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="clr-page">

    <!-- Global Alert -->
    <div id="facultyAlert" class="alert alert-success alert-dismissible fade show d-none mb-3 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i><span id="facultyAlertMessage"></span>
        <button type="button" class="btn-close" onclick="dismissFacultyAlert()" aria-label="Close"></button>
    </div>

    <!-- ── Page Title Row ──────────────────────────────────────────── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-check text-primary me-2"></i>Faculty Clearance Portal
            </h4>
            <p class="text-body-secondary small mb-0">Track verification status across all 6 administrative and academic
                units for
                <?= facultyClearanceEsc($cfTerm) ?>, S.Y. <?= facultyClearanceEsc($cfSY) ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php
            $statusBadgeClass = match ($status) {
                'Cleared' => 'bg-success text-white',
                'With Deficiency' => 'bg-danger text-white',
                'For Final Approval', 'For Department Head Approval' => 'bg-warning text-dark',
                'Under Verification' => 'bg-info text-dark',
                default => 'bg-secondary text-white',
            };
            $statusIcon = match ($status) {
                'Cleared' => 'fa-check-double',
                'With Deficiency' => 'fa-exclamation-triangle',
                'For Final Approval', 'For Department Head Approval' => 'fa-user-check',
                'Under Verification' => 'fa-clock',
                default => 'fa-circle-dot',
            };
            ?>
            <span class="badge <?= $statusBadgeClass ?> fs-6 px-3 py-2 shadow-sm" id="pageStatusBadge">
                <i class="fas <?= $statusIcon ?> me-1"></i>
                <?= facultyClearanceEsc($status) ?>
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                onclick="refreshClearanceStatus()" title="Refresh Live Status">

                <span class="d-none d-md-inline small"></span>
                <i class="fas fa-sync-alt"></i>
                <span class="d-none d-md-inline small">Refresh</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                onclick="confirmResetClearance()" title="Reset Status Tracker & Files">
                <i class="fas fa-rotate-left"></i>
                <span class="d-none d-md-inline small">Reset</span>
            </button>
        </div>
    </div>

    <?php if (!$profile): ?>
        <div class="alert alert-warning shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i>No faculty profile is linked to this account. Please contact the
            Faculty Administrator.
        </div>
    <?php else: ?>

        <!-- ════════════════ VIEW 1: CLEARANCE OF CONDUCT AGREEMENT ════════════════ -->
        <div id="viewAgreementSection" class="d-none mb-4">
            <div class="clr-card mb-3" id="facultyClearanceFormCard">
                <div class="clr-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-file-contract text-primary fs-5"></i>
                        <div>
                            <div class="fw-bold mb-0">Clearance Agreement Form</div>
                            <div class="small text-body-secondary">Official Faculty Clearance Form
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm"
                            onclick="printClearanceAgreementForm()" title="Print Clearance Agreement Form">
                            <i class="fas fa-print"></i>
                            <span class="d-none d-sm-inline">Print Form</span>
                        </button>
                        <span
                            class="badge fs-7 px-3 py-2 <?= $cfFormApproved ? 'bg-success-subtle text-success border border-success-subtle' : ($cfFormSubmitted ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle') ?>"
                            id="formStatusBadge">
                            <i
                                class="fas <?= $cfFormApproved ? 'fa-check-circle' : ($cfFormSubmitted ? 'fa-hourglass-half' : 'fa-circle-play') ?> me-1"></i>
                            <?= $cfFormApproved ? 'Clearance Form Approved &amp; Endorsed' : ($cfFormSubmitted ? 'Pending Department Head Review' : 'Step 1: Agreement Required') ?>
                        </span>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <div class="clr-conduct-doc">
                        <div class="clr-conduct-header">
                            <div class="clr-conduct-title">Clearance Agreement Form</div>
                            <div class="clr-conduct-subheading"><?= facultyClearanceEsc($cfInstitution) ?></div>
                        </div>

                        <!-- Top Metadata -->
                        <div class="mb-3">
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Institution:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfInstitution) ?></span>
                            </div>
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Department:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfDept) ?></span>
                            </div>
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Academic Year:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfSY) ?></span>
                            </div>
                        </div>

                        <!-- FACULTY MEMBER INFORMATION -->
                        <div class="clr-conduct-section-label">FACULTY MEMBER INFORMATION</div>
                        <div class="mb-3">
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Name:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfFullName) ?: 'N/A' ?></span>
                            </div>
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Employee/Faculty ID:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfFacultyId) ?></span>
                            </div>
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Position:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfPosition) ?></span>
                            </div>
                            <div class="clr-conduct-field-row">
                                <span class="clr-conduct-label">Department:</span>
                                <span class="clr-conduct-underline"><?= facultyClearanceEsc($cfDept) ?></span>
                            </div>
                        </div>

                        <!-- AGREEMENT -->
                        <div class="clr-conduct-section-label">AGREEMENT</div>

                        <p class="clr-conduct-para">
                            I, <span
                                class="clr-conduct-inline-underline"><?= facultyClearanceEsc($cfFullName) ?: '________________________________' ?></span>,
                            hereby acknowledge and agree that I have complied with the rules, regulations, policies, and
                            professional standards of the institution during my period of service.
                        </p>

                        <p class="fw-bold mb-2" style="font-size: 0.92rem; color: var(--bs-body-color);">I certify that:</p>

                        <ul class="clr-conduct-list">
                            <li>I have maintained proper and professional conduct while performing my duties as a faculty
                                member.</li>
                            <li>I have complied with the institution's policies, rules, and regulations.</li>
                            <li>I have no pending disciplinary case, unresolved conduct violation, or administrative matter,
                                unless properly declared and documented.</li>
                            <li>I have fulfilled my responsibilities toward students, colleagues, and the institution.</li>
                            <li>I understand that providing false information may result in appropriate administrative
                                action.</li>
                            <li>I agree that the institution may verify the information provided in this agreement as part
                                of the faculty clearance process.</li>
                        </ul>

                        <!-- Checkbox Acknowledgment -->
                        <div class="clr-conduct-ack-box mb-4">
                            <label class="d-flex align-items-start gap-2 small fw-semibold cursor-pointer mb-0">
                                <input type="checkbox" id="cfFormAgreeCheck" class="form-check-input mt-1"
                                    <?= $cfFormSubmitted ? 'checked disabled' : '' ?>>
                                <span class="text-body-emphasis" style="line-height: 1.55;">
                                    I understand that this Clearance Form is part of the faculty clearance
                                    requirements and will be reviewed by the appropriate department or authorized personnel
                                    before my clearance is approved.
                                </span>
                            </label>
                        </div>

                        <?php if (!$cfFormSubmitted || $cfFormStatus === 'Not Submitted'): ?>
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pt-3 border-top">
                                <div class="small text-body-secondary">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Submitting this agreement forwards it
                                    to the Department Head for initial review and endorsement before requirement uploads unlock.
                                </div>
                                <button type="button" class="btn btn-primary fw-semibold px-4 py-2 shadow-sm text-nowrap"
                                    id="btnSubmitClearanceFormOnly" onclick="submitClearanceFormOnly()" disabled>
                                    <i class="fas fa-paper-plane me-1"></i> Submit Clearance Form
                                </button>
                            </div>
                        <?php elseif ($cfFormStatus === 'Pending Review'): ?>
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 bg-warning-subtle bg-opacity-25 rounded-3 border border-warning-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width:40px;height:40px;">
                                        <i class="fas fa-hourglass-half fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-warning-emphasis">Clearance Form Submitted — Awaiting
                                            Department Head Approval</div>
                                        <div class="small text-body-secondary">
                                            Submitted on <strong><?= facultyClearanceEsc($cfFormSubmittedAt) ?></strong> ·
                                            Form No: <strong><?= facultyClearanceEsc($cfFormNo) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark px-3 py-2 fs-7">
                                    <i class="fas fa-clock me-1"></i>Under Dept Head Review
                                </span>
                            </div>
                        <?php elseif ($cfFormApproved): ?>
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 bg-success-subtle bg-opacity-25 rounded-3 border border-success-subtle">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width:40px;height:40px;">
                                        <i class="fas fa-check fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-success-emphasis">Clearance Form Approved &amp; Endorsed</div>
                                        <div class="small text-body-secondary">
                                            Endorsed on
                                            <strong><?= facultyClearanceEsc($cfFormApprovedAt ?: $cfFormSubmittedAt) ?></strong>
                                            ·
                                            Form No: <strong><?= facultyClearanceEsc($cfFormNo) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold"
                                        onclick="printClearanceAgreementForm()">
                                        <i class="fas fa-print me-1"></i> Print Form
                                    </button>
                                    <span class="badge bg-success text-white px-3 py-2 fs-7">
                                        <i class="fas fa-check-circle me-1"></i>Active &amp; Acknowledged
                                    </span>
                                    <button type="button" class="btn btn-sm btn-primary fw-semibold"
                                        onclick="switchClearanceView('portal')">
                                        <i class="fas fa-arrow-right me-1"></i> Go to Portal
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div><!-- /viewAgreementSection -->

        <!-- ════════════════ VIEW 2: FACULTY CLEARANCE PORTAL ════════════════ -->
        <div id="viewPortalSection">

            <!-- ── Workflow Lifecycle Stepper ───────────────────────────────── -->
            <div class="clr-flow-stepper mb-4">
                <!-- 1. Faculty Submission -->
                <div
                    class="clr-flow-step <?= $activeLifecycleStep > 1 ? 'completed' : ($activeLifecycleStep === 1 ? 'active' : '') ?>">
                    <div class="clr-flow-circle">
                        <?= $activeLifecycleStep > 1 ? '<i class="fas fa-check"></i>' : '1' ?>
                    </div>
                    <div class="clr-flow-title">Faculty<br>Submit</div>
                </div>

                <div class="clr-flow-divider <?= $activeLifecycleStep > 1 ? 'completed' : '' ?>"></div>

                <!-- 2. Dept Head Review -->
                <div
                    class="clr-flow-step <?= $activeLifecycleStep > 2 ? 'completed' : ($activeLifecycleStep === 2 ? 'active' : '') ?>">
                    <div class="clr-flow-circle">
                        <?= $activeLifecycleStep > 2 ? '<i class="fas fa-check"></i>' : '2' ?>
                    </div>
                    <div class="clr-flow-title">Department<br>Head Review</div>
                </div>

                <div class="clr-flow-divider <?= $activeLifecycleStep > 2 ? 'completed' : '' ?>"></div>

                <!-- 3. Offices/Units Verification -->
                <div
                    class="clr-flow-step <?= $status === 'With Deficiency' ? 'deficiency' : ($activeLifecycleStep > 3 ? 'completed' : ($activeLifecycleStep === 3 ? 'active' : '')) ?>">
                    <div class="clr-flow-circle">
                        <?php if ($status === 'With Deficiency'): ?>
                            <i class="fas fa-exclamation"></i>
                        <?php elseif ($activeLifecycleStep > 3): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            3
                        <?php endif; ?>
                    </div>
                    <div class="clr-flow-title">Offices / Units<br>Verification</div>
                </div>

                <div class="clr-flow-divider <?= $activeLifecycleStep > 3 ? 'completed' : '' ?>"></div>

                <!-- 4. HR Final Approval -->
                <div
                    class="clr-flow-step <?= $activeLifecycleStep > 4 ? 'completed' : ($activeLifecycleStep === 4 ? 'active' : '') ?>">
                    <div class="clr-flow-circle">
                        <?= $activeLifecycleStep > 4 ? '<i class="fas fa-check"></i>' : '4' ?>
                    </div>
                    <div class="clr-flow-title">HR Final<br>Approval</div>
                </div>

                <div class="clr-flow-divider <?= $activeLifecycleStep >= 5 ? 'completed' : '' ?>"></div>

                <!-- 5. Cleared -->
                <div class="clr-flow-step <?= $activeLifecycleStep === 5 ? 'completed' : '' ?>">
                    <div class="clr-flow-circle">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="clr-flow-title">Clearance<br>Completed</div>
                </div>
            </div>

            <!-- ── Clearance Agreement Form Gate Banner ─────────────────────── -->
            <?php if (!$cfFormApproved): ?>
                <?php if ($cfFormSubmitted && $cfFormStatus === 'Pending Review'): ?>
                    <div
                        class="alert alert-warning border border-warning-subtle shadow-sm mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:38px;height:38px;">
                                <i class="fas fa-hourglass-half fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-warning-emphasis mb-0">Clearance Agreement Form Under Department Head Review
                                </h6>
                                <p class="small text-body-secondary mb-0">Your Clearance Form has been submitted and is awaiting
                                    Department Head review and endorsement. Requirement uploads and portal submission will unlock
                                    once approved.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold"
                            onclick="switchClearanceView('agreement')">
                            <i class="fas fa-file-contract me-1"></i>View Agreement Form
                        </button>
                    </div>
                <?php else: ?>
                    <div
                        class="alert alert-info border border-info-subtle shadow-sm mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:38px;height:38px;">
                                <i class="fas fa-file-signature fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-info-emphasis mb-0">Step 1 Required: Submit Clearance Agreement Form</h6>
                                <p class="small text-body-secondary mb-0">You must submit and obtain Department Head endorsement on
                                    your Clearance Agreement Form before unit requirement uploads can begin.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="switchClearanceView('agreement')">
                            <i class="fas fa-file-contract me-1"></i>Open Agreement Form
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ── Faculty Information Card ───────────────────────────────── -->
            <div class="clr-card mb-4">
                <div class="clr-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-id-card text-primary fs-5"></i>
                        <div>
                            <div class="fw-bold mb-0">Faculty Information</div>
                            <div class="small text-body-secondary">Official Personnel &amp; Department Record</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge fs-7 px-2 py-1 <?= $cfSubmitted ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-body-secondary' ?>"
                            id="formSubmittedBadge">
                            <i class="fas <?= $cfSubmitted ? 'fa-check-circle' : 'fa-circle-dot' ?> me-1"></i>
                            <?= $cfSubmitted ? 'Clearance Submitted' : 'Pending Submission' ?>
                        </span>
                        <span class="small text-body-secondary d-none d-md-inline">Form No:
                            <strong><?= facultyClearanceEsc($cfFormNo) ?></strong></span>
                    </div>
                </div>

                <div class="clr-info-grid">
                    <div>
                        <div class="clr-info-label">Full Name</div>
                        <div class="clr-info-value"><?= facultyClearanceEsc($cfFullName) ?: 'N/A' ?></div>
                    </div>
                    <div>
                        <div class="clr-info-label">Faculty / Employee ID</div>
                        <div class="clr-info-value"><?= facultyClearanceEsc($cfFacultyId) ?></div>
                    </div>
                    <div>
                        <div class="clr-info-label">Department</div>
                        <div class="clr-info-value"><?= facultyClearanceEsc($cfDept) ?></div>
                    </div>
                    <div>
                        <div class="clr-info-label">Position</div>
                        <div class="clr-info-value"><?= facultyClearanceEsc($cfPosition) ?></div>
                    </div>
                    <div>
                        <div class="clr-info-label">Employment Status</div>
                        <div class="clr-info-value">
                            <span
                                class="badge <?= $cfEmpStatus === 'Regular' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> border">
                                <?= facultyClearanceEsc($cfEmpStatus) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="clr-info-label">Contract Start Date</div>
                        <div class="clr-info-value"><?= facultyClearanceEsc($cfHired) ?></div>
                    </div>
                    <div>
                        <div class="clr-info-label">Contract Expiration</div>
                        <div
                            class="clr-info-value <?= $daysRemaining !== null && $daysRemaining <= 30 ? 'text-danger fw-bold' : '' ?>">
                            <?= facultyClearanceEsc($cfContractEnd) ?>
                            <?php if ($daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining <= 30): ?>
                                <span class="badge bg-danger ms-1"><?= $daysRemaining ?> days</span>
                            <?php elseif ($daysRemaining !== null && $daysRemaining < 0): ?>
                                <span class="badge bg-danger ms-1">Expired</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($cfEmail !== ''): ?>
                        <div>
                            <div class="clr-info-label">Email Address</div>
                            <div class="clr-info-value" style="font-size:.81rem;"><?= facultyClearanceEsc($cfEmail) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Progress Bar -->
                <div class="p-3 border-top bg-body-tertiary bg-opacity-50">
                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="fw-bold"><i class="fas fa-tasks text-primary me-1"></i>Clearance Verification
                            Progress</span>
                        <strong id="progressFraction"><?= $cfApprovedCount ?>/<?= $cfTotalCount ?> offices cleared</strong>
                    </div>
                    <div class="clr-progress-bar-wrap mb-1">
                        <div class="clr-progress-fill <?= $cfPct === 100 ? 'full' : '' ?>" id="mainProgressBar"
                            style="width:<?= $cfPct ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-body-secondary">
                        <span id="progressPctLabel"><?= $cfPct ?>% complete</span>
                        <span>Term: <?= facultyClearanceEsc($cfTerm) ?>, S.Y. <?= facultyClearanceEsc($cfSY) ?></span>
                    </div>
                </div>
            </div>

            <!-- ── The 6 Main Clearance Sections ────────────────────────── -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-building-circle-check text-primary me-2"></i>Clearance
                        Verification Sections</h5>
                    <p class="text-body-secondary small mb-0">Each responsible office reviews and confirms your clearance
                        accountabilities directly.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">6 Clearance
                    Units</span>
            </div>

            <div class="row g-3 mb-4">
                <?php foreach ($offices as $index => $foff):
                    $fOid = (int) $foff['clearance_office_id'];
                    $fName = (string) $foff['name'];
                    $meta = $sectionsMeta[$fName] ?? [
                        'name' => $fName,
                        'office' => 'Administrative Office',
                        'icon' => 'fa-check-circle',
                        'description' => (string) ($foff['description'] ?? 'Clearance verification area'),
                        'items' => ['Accountabilities and obligations verified'],
                    ];
                    $fItem = $itemByOffice[$fOid] ?? null;
                    $fStat = $fItem['status'] ?? 'Missing';
                    $fStatN = strtolower(trim($fStat));

                    $fChip = 'pending';
                    $fChipIcon = 'fa-circle-dot';
                    $fChipLabel = 'Pending Verification';

                    if (in_array($fStatN, ['cleared', 'approved'], true)) {
                        $fChip = 'cleared';
                        $fChipIcon = 'fa-check-circle';
                        $fChipLabel = 'Cleared';
                    } elseif (in_array($fStatN, ['denied', 'hold', 'rejected', 'with deficiency', 'with_deficiency'], true)) {
                        $fChip = 'deficiency';
                        $fChipIcon = 'fa-exclamation-triangle';
                        $fChipLabel = 'With Deficiency';
                    } elseif (in_array($fStatN, ['on hold', 'on_hold'], true)) {
                        $fChip = 'onhold';
                        $fChipIcon = 'fa-pause-circle';
                        $fChipLabel = 'On Hold';
                    } elseif (in_array($fStatN, ['pending review', 'pending verification', 'under verification', 'submitted'], true)) {
                        $fChip = 'review';
                        $fChipIcon = 'fa-hourglass-half';
                        $fChipLabel = 'Under Verification';
                    }

                    $fDate = $fItem ? (string) ($fItem['cleared_at'] ?? ($fItem['updated_at'] ?? '')) : '';
                    $fRemarks = $fItem ? (string) ($fItem['remarks'] ?? '') : '';
                    $fCleanRmk = preg_replace('/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i', '', $fRemarks);
                    $fFileName = $fItem ? (string) ($fItem['original_name'] ?? basename((string) ($fItem['file_path'] ?? ''))) : '';

                    // Upload is blocked when agreement is not approved by Dept Head OR under verification / cleared
                    $uploadBlocked = (!$cfFormApproved) || in_array($fChip, ['review', 'cleared'], true);
                    $uploadHint = match (true) {
                        !$cfFormSubmitted => 'Submit the Clearance Agreement Form to begin the review workflow.',
                        !$cfFormApproved => 'Awaiting Department Head endorsement on your Clearance Agreement Form.',
                        $fChip === 'review' => 'Document is under review and cannot be replaced.',
                        $fChip === 'cleared' => 'This section has been cleared by the office.',
                        default => 'Upload supporting documents (PDF, max 10 MB)',
                    };
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="office-card" data-office-row-id="<?= $fOid ?>">
                            <div class="office-card-header">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="office-icon-wrap">
                                        <i class="fas <?= $meta['icon'] ?? 'fa-circle-check' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size:.9rem;"><?= facultyClearanceEsc($meta['name']) ?>
                                        </div>
                                        <div class="text-body-secondary small" style="font-size:.72rem;">
                                            <i class="fas fa-building me-1"></i><?= facultyClearanceEsc($meta['office']) ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="clr-chip clr-chip-<?= $fChip ?> office-chip">
                                    <i class="fas <?= $fChipIcon ?>"></i> <span class="chip-label"><?= $fChipLabel ?></span>
                                </span>
                            </div>

                            <div class="office-card-body">
                                <div>
                                    <div class="text-uppercase fw-bold text-body-secondary mb-2"
                                        style="font-size:.65rem;letter-spacing:.05em;">
                                        Scope of Verification
                                    </div>
                                    <ul class="office-checklist">
                                        <?php foreach ($meta['items'] as $chk): ?>
                                            <li>
                                                <i
                                                    class="fas fa-check-circle <?= $fChip === 'cleared' ? 'text-success' : '' ?>"></i>
                                                <span><?= facultyClearanceEsc($chk) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Remarks / Deficiency Note box -->
                                <div class="office-remarks-container">
                                    <?php if ($fChip === 'deficiency' && $fCleanRmk !== ''): ?>
                                        <div class="office-deficiency-box">
                                            <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i>Deficiency Note:
                                            </div>
                                            <div class="office-remarks-text"><?= facultyClearanceEsc($fCleanRmk) ?></div>
                                        </div>
                                    <?php elseif ($fChip === 'onhold' && $fCleanRmk !== ''): ?>
                                        <div class="office-onhold-box">
                                            <div class="fw-bold mb-1"><i class="fas fa-pause-circle me-1"></i>Office Note:</div>
                                            <div class="office-remarks-text"><?= facultyClearanceEsc($fCleanRmk) ?></div>
                                        </div>
                                    <?php elseif ($fChip === 'cleared' && $fDate !== ''): ?>
                                        <div class="small text-success d-flex align-items-center gap-1">
                                            <i class="fas fa-badge-check"></i>
                                            <span>Cleared on <?= date('M j, Y', strtotime($fDate)) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-body-secondary d-flex align-items-center gap-1">
                                            <i class="fas fa-shield-halved text-secondary opacity-50"></i>
                                            <span>Verified directly by <?= facultyClearanceEsc($meta['office']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- ── Upload Zone ─────────────────────────────────────── -->
                                <div class="office-upload-zone <?= $uploadBlocked ? 'upload-blocked' : '' ?>"
                                    id="uploadZone<?= $fOid ?>" data-office-id="<?= $fOid ?>"
                                    data-office-name="<?= facultyClearanceEsc($fName) ?>"
                                    data-blocked="<?= $uploadBlocked ? '1' : '0' ?>" <?= !$uploadBlocked ? 'ondragover="handleDragOver(event,this)" ondragleave="handleDragLeave(event,this)" ondrop="handleDrop(event,this)"' : '' ?>>

                                    <?php if ($fFileName !== '' && !in_array($fChip, ['cleared'], true)): ?>
                                        <!-- Existing uploaded file preview -->
                                        <div class="upload-file-preview" id="filePreview<?= $fOid ?>">
                                            <i class="fas fa-file-pdf text-danger me-1"></i>
                                            <a href="<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php?action=file&item_id=<?= (int) ($fItem['clearance_item_id'] ?? 0) ?>"
                                                target="_blank" class="upload-file-name text-decoration-none text-body fw-semibold"
                                                title="Click to preview uploaded PDF"><?= facultyClearanceEsc($fFileName) ?></a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Selected Staged File indicator (shown when chosen before clicking unified submit) -->
                                    <div class="upload-staged-box d-none" id="stagedBox<?= $fOid ?>">
                                        <div
                                            class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-primary-subtle border border-primary-subtle">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                <i class="fas fa-file-pdf text-primary fs-5 flex-shrink-0"></i>
                                                <div class="text-start overflow-hidden">
                                                    <span class="d-block small fw-bold text-primary-emphasis text-truncate"
                                                        id="stagedFileName<?= $fOid ?>">file.pdf</span>
                                                    <small class="text-body-secondary" style="font-size:0.72rem;">Attached ·
                                                        Ready to submit</small>
                                                </div>
                                            </div>
                                            <button type="button"
                                                class="btn btn-sm btn-link text-danger p-0 ms-1 text-decoration-none"
                                                onclick="cancelUpload(<?= $fOid ?>)" title="Remove attached file">
                                                <i class="fas fa-times-circle fs-5"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="upload-zone-inner <?= $uploadBlocked ? 'opacity-50' : '' ?>"
                                        id="zoneInner<?= $fOid ?>">
                                        <i class="fas <?= !$cfFormSubmitted ? 'fa-lock text-secondary' : ($fChip === 'cleared' ? 'fa-lock text-success' : ($fChip === 'review' ? 'fa-clock text-info' : 'fa-cloud-arrow-up text-primary')) ?> mb-1"
                                            style="font-size:1.5rem;"></i>
                                        <div class="upload-hint-text"><?= htmlspecialchars($uploadHint) ?></div>
                                        <?php if (!$uploadBlocked): ?>
                                            <label class="btn-upload-choose" for="fileInput<?= $fOid ?>"
                                                id="chooseLabel<?= $fOid ?>">
                                                <i class="fas fa-folder-open me-1"></i>
                                                <?= $fFileName !== '' ? 'Select New PDF' : 'Choose PDF' ?>
                                            </label>
                                            <input type="file" id="fileInput<?= $fOid ?>" class="office-file-input d-none"
                                                accept=".pdf,application/pdf" data-office-id="<?= $fOid ?>"
                                                onchange="handleFileSelected(this)">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Faculty Declaration & Digital Signature (Placed at the bottom) ──────────────────────── -->
            <div class="clr-card mb-4" id="facultyDeclarationCard">
                <div class="clr-card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-file-signature text-primary fs-5"></i>
                        <div>
                            <div class="fw-bold mb-0">Faculty Declaration</div>
                            <div class="small text-body-secondary">Official Clearance Certification &amp; Digital Signature
                            </div>
                        </div>
                    </div>
                    <span
                        class="badge fs-7 px-3 py-2 <?= !empty($cfSignatureData) ? (($status === 'Cleared') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle') : (($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0) ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-body-secondary border') ?>"
                        id="declarationBadge">
                        <i
                            class="fas <?= !empty($cfSignatureData) ? (($status === 'Cleared') ? 'fa-check-circle' : 'fa-hourglass-half') : (($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0) ? 'fa-pen-clip' : 'fa-lock') ?> me-1"></i>
                        <?= !empty($cfSignatureData) ? (($status === 'Cleared') ? 'Signed' : 'Pending Department Head Review') : (($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0) ? 'Ready to Sign' : 'Locked — Pending Document Approvals') ?>
                    </span>
                </div>

                <div class="p-4">
                    <p class="text-body-secondary small mb-3 fst-italic">
                        "I hereby certify that I have completed and submitted the required documents and have returned any
                        school propery, records, or other accountable items assigned to me."
                    </p>

                    <?php if (!empty($cfSignatureData)): ?>
                        <!-- Signed preview -->
                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 bg-body-tertiary bg-opacity-50 rounded-3 border">
                            <div>
                                <div class="mb-2">
                                    <img src="<?= facultyClearanceEsc($cfSignatureData) ?>" alt="Faculty Signature"
                                        class="signature-preview-img bg-white p-2 rounded border">
                                </div>
                                <div class="fw-bold text-body-emphasis"><?= facultyClearanceEsc($cfFullName) ?></div>
                                <div class="small text-body-secondary"><?= facultyClearanceEsc($cfFormSubmittedAt) ?></div>
                            </div>

                            <div class="text-md-end">
                                <?php if ($status === 'Cleared'): ?>
                                    <span
                                        class="badge bg-success text-white fs-6 px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-check"></i> Signed
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-user-check"></i> With Department Head
                                    </span>
                                <?php endif; ?>
                                <div class="small text-body-secondary mt-1">
                                    Form No: <strong><?= facultyClearanceEsc($cfFormNo) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0): ?>
                        <!-- All documents approved — signature pad unlocked -->
                        <div
                            class="alert alert-success border border-success-subtle mb-3 py-2 px-3 small d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>All <strong><?= $cfTotalCount ?></strong> required documents have been approved. Please draw
                                your digital signature below to complete your declaration.</span>
                        </div>
                        <div class="p-3 bg-body-tertiary rounded-3 border mb-3">
                            <div class="row g-4 align-items-start">
                                <div class="col-12 col-md-7">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold text-uppercase text-body-secondary mb-0">
                                            <i class="fas fa-signature text-primary me-1"></i> Digital Signature <span
                                                class="text-danger">*</span>
                                        </label>
                                        <button type="button"
                                            class="btn btn-sm btn-link text-secondary text-decoration-none p-0"
                                            onclick="clearSignatureCanvas()">
                                            <i class="fas fa-rotate-left me-1"></i> Clear Signature
                                        </button>
                                    </div>
                                    <div class="signature-pad-container position-relative mb-2">
                                        <canvas id="signatureCanvas" class="signature-pad-canvas" width="480"
                                            height="140"></canvas>
                                        <div class="signature-baseline"></div>
                                        <div class="signature-hint" id="signatureHint">Draw your signature here with mouse or
                                            touch</div>
                                    </div>
                                    <small class="text-body-secondary d-block">
                                        Signer: <strong><?= facultyClearanceEsc($cfFullName) ?></strong>
                                    </small>
                                </div>

                                <div class="col-12 col-md-5">
                                    <div class="p-3 bg-white rounded-3 border mb-3">
                                        <div class="small mb-2">
                                            <span class="text-body-secondary d-block">Date of Declaration:</span>
                                            <strong class="text-body-emphasis"><?= date('F d, Y') ?></strong>
                                        </div>
                                        <label class="d-flex align-items-start gap-2 small fw-semibold cursor-pointer mb-0">
                                            <input type="checkbox" id="cfDeclareCheck" class="form-check-input mt-1">
                                            <span>I agree and certify that all uploaded documents and submitted clearances are
                                                true and complete.</span>
                                        </label>
                                    </div>

                                    <small class="text-body-secondary d-block mb-3">
                                        <i class="fas fa-info-circle text-primary me-1"></i> Checking the box enables
                                        submission. Your signed declaration will be forwarded to your Department Head.
                                    </small>

                                    <button type="button" class="btn btn-primary fw-semibold w-100 px-4 py-2 shadow-sm"
                                        id="btnSubmitFacultyDeclaration" onclick="submitFacultyDeclaration()" disabled>
                                        <i class="fas fa-paper-plane me-1"></i> Submit Faculty Declaration
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Locked — not all documents approved yet -->
                        <div class="p-4 bg-body-tertiary rounded-3 border text-center">
                            <div class="mb-3">
                                <i class="fas fa-lock text-secondary" style="font-size:2rem;opacity:.4;"></i>
                            </div>
                            <div class="fw-bold text-body-emphasis mb-1">Digital Signature Locked</div>
                            <div class="small text-body-secondary mb-3">
                                This section will unlock once <strong>all required office documents</strong> have been approved.
                                <?php if ($cfTotalCount > 0): ?>
                                    <span class="d-block mt-1"><?= $cfApprovedCount ?> of <?= $cfTotalCount ?>
                                        document<?= $cfTotalCount > 1 ? 's' : '' ?> approved so far.</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($cfTotalCount > 0 && $cfApprovedCount < $cfTotalCount): ?>
                                <div class="progress mt-2" style="height:8px;max-width:260px;margin:0 auto;">
                                    <div class="progress-bar bg-primary" style="width:<?= $cfPct ?>%" role="progressbar"></div>
                                </div>
                                <div class="small text-body-secondary mt-1"><?= $cfPct ?>% complete</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($cfFormSubmitted): ?>
                <!-- ── Clearance Requirements Unified Submission Bar ──────────── -->
                <div class="clr-card">
                    <!-- Resubmit / Upload Bar when files are attached -->
                    <div class="p-3 bg-primary-subtle border-bottom border-primary-subtle d-none" id="resubmitBar">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-arrow-up text-primary fs-4"></i>
                                <div>
                                    <div class="fw-bold small text-primary-emphasis" id="resubmitText">0 file(s) attached for
                                        submission</div>
                                    <small class="text-body-secondary">Click the button to upload and submit all attached
                                        documents to the responsible offices.</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary fw-semibold px-4 py-2" id="btnResubmitClearance"
                                onclick="submitClearanceForm()">
                                <i class="fas fa-paper-plane me-1"></i> Submit Attached Files
                            </button>
                        </div>
                    </div>

                    <!-- Footer Bar -->
                    <div class="clr-form-footer">
                        <div class="d-flex align-items-center gap-2 text-success">
                            <i class="fas fa-check-circle fs-5"></i>
                            <div>
                                <div class="fw-bold small">Clearance Form is Active &amp; Verified</div>
                                <small class="text-body-secondary">Submitted on <?= facultyClearanceEsc($cfFormSubmittedAt) ?>.
                                    You can attach requirement documents above for office review.</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-2 fw-semibold"
                                onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print Summary
                            </button>
                        </div>
                    </div>
                </div><!-- /clr-card -->
            <?php endif; ?>

        </div><!-- /viewPortalSection -->

    <?php endif; ?>
</div><!-- /clr-page -->

<!-- ══════════════ MODALS ══════════════ -->

<!-- Confirm Submit Clearance Form Modal -->
<div class="modal fade" id="confirmClearanceFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;">
                        <i class="fas fa-paper-plane text-primary fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Confirm Clearance Submission</h5>
                        <small class="text-body-secondary">Academic Term: <?= facultyClearanceEsc($cfTerm) ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-body-secondary small mb-3">
                    Please review the files you have attached before finalizing your submission. Once submitted, all
                    documents will be forwarded to the respective administrative units and department heads for
                    verification.
                </p>

                <!-- Attached requirements summary breakdown -->
                <div class="card border rounded-3 mb-3">
                    <div
                        class="card-header bg-body-tertiary py-2 px-3 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-uppercase text-body-secondary">
                            <i class="fas fa-folder-open me-1 text-primary"></i> Attached Clearance Documents
                        </span>
                        <span class="badge bg-primary px-2 py-1" id="modalAttachedBadge">0 Attached</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive mb-0">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th class="ps-3 py-2" style="width:38%;">Requirement Section</th>
                                        <th class="py-2" style="width:42%;">Attached File</th>
                                        <th class="text-end pe-3 py-2" style="width:20%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="modalAttachedList" class="small">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No files attached.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Verification notice alert -->
                <div
                    class="d-flex align-items-start gap-2 p-3 rounded-3 bg-warning-subtle border border-warning-subtle mb-0">
                    <i class="fas fa-triangle-exclamation text-warning fs-5 mt-1 flex-shrink-0"></i>
                    <div class="small">
                        <strong class="text-warning-emphasis d-block mb-1">Notice on File Review:</strong>
                        <span class="text-body-secondary">Once submitted, attached documents are placed under
                            <strong>Under Verification</strong> and cannot be replaced until the responsible office
                            reviews or issues feedback.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i> Review / Edit Files
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" id="confirmSubmitCFBtn">
                    <i class="fas fa-check-circle me-1"></i> Yes, Confirm &amp; Submit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Reset Clearance Modal -->
<div class="modal fade" id="confirmResetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;">
                        <i class="fas fa-rotate-left text-danger fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Reset Clearance?</h6>
                        <small class="text-body-secondary">Start over from Step 1</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-body-secondary small mb-0">
                    This will clear all uploaded documents, remove office review flags, and reset your status tracker
                    back to <strong>Step 1: Faculty Submit (0%)</strong>.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger flex-fill fw-semibold" id="confirmResetBtn"
                    onclick="executeResetClearance()">
                    <i class="fas fa-trash-alt me-1"></i> Yes, Reset All
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ SCRIPTS ══════════════ -->
<script>
    const clearanceApi = '<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php';

    // ── Dropdown Section View Switcher ──────────────────────────────────────────
    function switchClearanceView(view) {
        const agreementSection = document.getElementById('viewAgreementSection');
        const portalSection = document.getElementById('viewPortalSection');
        const selectEl = document.getElementById('clearanceViewSelect');
        const hintEl = document.getElementById('viewSectionHint');

        if (selectEl && selectEl.value !== view) {
            selectEl.value = view;
        }

        if (view === 'agreement') {
            agreementSection?.classList.remove('d-none');
            portalSection?.classList.add('d-none');
            if (hintEl) {
                hintEl.innerHTML = '<i class="fas fa-file-contract"></i><span>Viewing: <strong>Clearance Form</strong></span>';
            }
        } else {
            portalSection?.classList.remove('d-none');
            agreementSection?.classList.add('d-none');
            if (hintEl) {
                hintEl.innerHTML = '<i class="fas fa-clipboard-check"></i><span>Viewing: <strong>Faculty Clearance Portal</strong></span>';
            }
        }

        // Keep sidebar submenu links active state in sync
        document.querySelectorAll('#sidebarSubmenu_my-clearance .nav-link').forEach(link => {
            const href = link.getAttribute('href') || '';
            const isAgreement = href.includes('agreement');
            if ((view === 'agreement' && isAgreement) || (view !== 'agreement' && !isAgreement)) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        try {
            localStorage.setItem('faculty_clearance_active_view', view);
            if (history.replaceState) {
                history.replaceState(null, '', '#' + view);
            }
        } catch (e) { /* silent */ }
    }

    function getInitialClearanceView() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlView = (urlParams.get('view') || '').toLowerCase();
        if (urlView === 'agreement' || urlView === 'portal') return urlView;

        const hash = (window.location.hash || '').replace('#', '').toLowerCase();
        if (hash === 'agreement' || hash === 'portal') return hash;

        const saved = localStorage.getItem('faculty_clearance_active_view');
        if (saved === 'agreement' || saved === 'portal') return saved;

        return 'portal';
    }

    document.addEventListener('DOMContentLoaded', function () {
        switchClearanceView(getInitialClearanceView());
    });

    window.addEventListener('hashchange', function () {
        const hash = (window.location.hash || '').replace('#', '').toLowerCase();
        if (hash === 'agreement' || hash === 'portal') {
            switchClearanceView(hash);
        }
    });

    // ── Live Refresh & Reset Controls ───────────────────────────────────────────
    async function refreshClearanceStatus() {
        const ico = document.getElementById('refreshIcon');
        if (ico) ico.classList.add('fa-spin');
        try {
            await pollFacultyClearanceStatus();
            showFacultyAlert('Clearance status tracker refreshed.', 'info');
        } catch (e) {
            location.reload();
        } finally {
            setTimeout(() => {
                if (ico) ico.classList.remove('fa-spin');
            }, 600);
        }
    }

    function confirmResetClearance() {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmResetModal'));
        modal.show();
    }

    async function executeResetClearance() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmResetModal'));
        modal?.hide();
        const btn = document.getElementById('confirmResetBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resetting…'; }

        try {
            const formData = new FormData();
            formData.append('action', 'reset-requirements');
            const res = await fetch(clearanceApi, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to reset clearance.');
            showFacultyAlert(data.message || 'Clearance status tracker and files reset successfully!', 'success');
            setTimeout(() => location.reload(), 700);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Yes, Reset All'; }
        }
    }

    // ── Alerts ──────────────────────────────────────────────────────────────────
    function showFacultyAlert(message, tone = 'success') {
        const box = document.getElementById('facultyAlert');
        if (!box) return;
        box.className = `alert alert-${tone} alert-dismissible fade show mb-3 shadow-sm`;
        document.getElementById('facultyAlertMessage').textContent = message;
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function dismissFacultyAlert() {
        document.getElementById('facultyAlert')?.classList.add('d-none');
    }

    // ── Helper Utilities ────────────────────────────────────────────────────────
    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        }[character]));
    }

    // ── Digital Signature Canvas Handler ────────────────────────────────────────
    let isDrawing = false;
    let hasDrawnSignature = false;
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas ? canvas.getContext('2d') : null;

    if (canvas && ctx) {
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            const width = rect.width || canvas.parentElement?.clientWidth || 600;
            const height = rect.height || 140;
            if (canvas.width !== width * dpr) {
                canvas.width = width * dpr;
                canvas.height = height * dpr;
                ctx.scale(dpr, dpr);
                ctx.lineJoin = 'round';
                ctx.lineCap = 'round';
                ctx.lineWidth = 2.5;
                ctx.strokeStyle = '#1a2e5a';
            }
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function getPos(e) {
            const r = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - r.left,
                y: clientY - r.top
            };
        }

        function startDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            const hint = document.getElementById('signatureHint');
            if (hint) hint.style.opacity = '0';
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasDrawnSignature = true;
            updateFormSubmitButtonState();
            updateDeclarationSubmitButtonState();
        }

        function stopDrawing(e) {
            if (isDrawing) {
                isDrawing = false;
                ctx.closePath();
                updateFormSubmitButtonState();
                updateDeclarationSubmitButtonState();
            }
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
    }

    function clearSignatureCanvas() {
        if (!canvas || !ctx) return;
        const dpr = window.devicePixelRatio || 1;
        ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        hasDrawnSignature = false;
        const hint = document.getElementById('signatureHint');
        if (hint) hint.style.opacity = '1';
        updateFormSubmitButtonState();
        updateDeclarationSubmitButtonState();
    }

    document.getElementById('cfFormAgreeCheck')?.addEventListener('change', function () {
        updateFormSubmitButtonState();
    });

    function updateFormSubmitButtonState() {
        const isAgreed = !!document.getElementById('cfFormAgreeCheck')?.checked;
        const btn = document.getElementById('btnSubmitClearanceFormOnly');
        if (btn) {
            btn.disabled = !isAgreed;
        }
    }

    async function submitClearanceFormOnly() {
        const isAgreed = document.getElementById('cfFormAgreeCheck')?.checked;
        if (!isAgreed) {
            showFacultyAlert('Please check the agreement box to confirm your Clearance Form.', 'warning');
            return;
        }

        const btn = document.getElementById('btnSubmitClearanceFormOnly');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting Agreement…';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'submit-clearance-form');
            formData.append('intent_type', 'renewal');

            const res = await fetch(clearanceApi, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to submit clearance agreement.');

            showFacultyAlert(data.message || 'Clearance Form submitted successfully! It has been forwarded to your Department Head for review and endorsement.', 'success');
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Clearance Form';
            }
        }
    }

    // ── Clearance Requirements File Staging & Declaration Check ─────────────────
    const pendingFiles = {}; // { [officeId]: File }

    document.getElementById('cfDeclareCheck')?.addEventListener('change', function () {
        updateSubmitButtonState();
        updateDeclarationSubmitButtonState();
    });

    function updateDeclarationSubmitButtonState() {
        const btn = document.getElementById('btnSubmitFacultyDeclaration');
        if (!btn) return;
        const isDeclared = !!document.getElementById('cfDeclareCheck')?.checked;
        btn.disabled = !isDeclared;
    }

    async function submitFacultyDeclaration() {
        const isDeclared = document.getElementById('cfDeclareCheck')?.checked;
        if (!isDeclared) {
            showFacultyAlert('Please check the Faculty Declaration checkbox before submitting.', 'warning');
            return;
        }
        if (!canvas || !hasDrawnSignature) {
            showFacultyAlert('Please draw your digital signature before submitting your Faculty Declaration.', 'warning');
            return;
        }

        const btn = document.getElementById('btnSubmitFacultyDeclaration');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting Declaration…';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'submit-declaration');
            formData.append('signature_data', canvas.toDataURL('image/png'));
            formData.append('declaration', 'I hereby certify that I have completed and submitted the required documents and have returned any school property, records, or other accountable items assigned to me.');

            const res = await fetch(clearanceApi, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to submit Faculty Declaration.');

            showFacultyAlert(data.message || 'Faculty Declaration submitted successfully! It has been forwarded to your Department Head.', 'success');
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Faculty Declaration';
            }
        }
    }

    function updateSubmitButtonState() {
        const count = Object.keys(pendingFiles).length;
        const btn = document.getElementById('btnSubmitClearanceForm');
        const btnLabel = document.getElementById('submitBtnLabel');
        const isDeclared = document.getElementById('cfDeclareCheck') ? document.getElementById('cfDeclareCheck').checked : true;

        if (btn) {
            btn.disabled = (count === 0 && !hasDrawnSignature) || !isDeclared;
            if (btnLabel) {
                btnLabel.textContent = count > 0
                    ? `Submit Clearance (${count} File${count > 1 ? 's' : ''} Attached)`
                    : 'Submit Attached Documents';
            }
        }

        const resubmitBar = document.getElementById('resubmitBar');
        const resubmitText = document.getElementById('resubmitText');
        if (resubmitBar) {
            if (count > 0) {
                resubmitBar.classList.remove('d-none');
                if (resubmitText) {
                    resubmitText.textContent = `${count} requirement document${count > 1 ? 's' : ''} attached and ready to submit`;
                }
            } else {
                resubmitBar.classList.add('d-none');
            }
        }
    }

    function submitClearanceForm() {
        const listEl = document.getElementById('modalAttachedList');
        const badgeEl = document.getElementById('modalAttachedBadge');
        const allOfficeCards = document.querySelectorAll('.office-card');
        const entries = Object.entries(pendingFiles);

        if (badgeEl) badgeEl.textContent = `${entries.length} Document${entries.length !== 1 ? 's' : ''} Attached`;

        if (listEl) {
            const rowsHtml = [];
            allOfficeCards.forEach(card => {
                const zone = card.querySelector('.office-upload-zone');
                const officeId = zone?.dataset.officeId;
                const officeName = zone?.dataset.officeName || card.querySelector('.office-card-header strong')?.textContent?.trim() || `Requirement #${officeId}`;
                const file = pendingFiles[officeId];

                if (file) {
                    rowsHtml.push(`
                        <tr>
                            <td class="ps-3 py-2 fw-semibold text-body-emphasis">
                                <i class="fas fa-check-circle text-success me-1"></i>${escapeHtml(officeName)}
                            </td>
                            <td class="py-2 text-truncate text-primary fw-medium" style="max-width:220px;">
                                <i class="fas fa-file-pdf text-danger me-1"></i>${escapeHtml(file.name)}
                            </td>
                            <td class="text-end pe-3 py-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Ready to upload</span>
                            </td>
                        </tr>
                    `);
                }
            });

            if (rowsHtml.length === 0) {
                listEl.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-body-secondary py-3">
                            <i class="fas fa-info-circle text-info me-1"></i> No new files attached. Clearance documents will be submitted for verification.
                        </td>
                    </tr>
                `;
            } else {
                listEl.innerHTML = rowsHtml.join('');
            }
        }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmClearanceFormModal'));
        modal.show();
    }

    function saveClearanceDraft() {
        showFacultyAlert('Draft saved. You can return any time to complete and submit your clearance form.', 'info');
    }

    document.getElementById('confirmSubmitCFBtn')?.addEventListener('click', async function () {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmClearanceFormModal'));
        modal?.hide();
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading &amp; Submitting…';

        try {
            const formData = new FormData();
            formData.append('action', 'submit');

            // Attach all staged files into requirements[officeId]
            for (const [officeId, file] of Object.entries(pendingFiles)) {
                formData.append(`requirements[${officeId}]`, file);
            }

            // Attach signature data if drawn on canvas
            if (canvas && hasDrawnSignature) {
                formData.append('signature_data', canvas.toDataURL('image/png'));
            }

            const response = await fetch(clearanceApi, { method: 'POST', body: formData });
            const result = await response.json();
            if (!result.ok) throw new Error(result.error || 'Failed to submit clearance documents.');

            showFacultyAlert(result.message || 'Clearance submitted successfully! The responsible verifying offices have been notified.', 'success');
            setTimeout(() => location.reload(), 900);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check-circle me-1"></i>Yes, Confirm &amp; Submit';
        }
    });

    // ── Workflow Stepper Dynamic Updater ─────────────────────────────────────────
    function updateLifecycleStepper(status, approvedItems = 0, totalItems = 6) {
        let step = 1;
        if (status === 'Cleared') {
            step = 5;
        } else if (status === 'For Final Approval') {
            step = 4;
        } else if (status === 'For Department Head Approval') {
            step = 3;
        } else if (status === 'Under Verification' || approvedItems > 0 || status === 'With Deficiency') {
            step = 2;
        }

        const stepper = document.querySelector('.clr-flow-stepper');
        if (!stepper) return;
        const steps = stepper.querySelectorAll('.clr-flow-step');
        const dividers = stepper.querySelectorAll('.clr-flow-divider');

        if (steps.length >= 5) {
            // Step 1: Faculty Submit
            steps[0].className = `clr-flow-step ${step > 1 ? 'completed' : (step === 1 ? 'active' : '')}`;
            const c1 = steps[0].querySelector('.clr-flow-circle');
            if (c1) c1.innerHTML = step > 1 ? '<i class="fas fa-check"></i>' : '1';

            // Divider 1
            if (dividers[0]) dividers[0].className = `clr-flow-divider ${step > 1 ? 'completed' : ''}`;

            // Step 2: Offices / Units Verification
            if (status === 'With Deficiency') {
                steps[1].className = 'clr-flow-step deficiency';
                const c2 = steps[1].querySelector('.clr-flow-circle');
                if (c2) c2.innerHTML = '<i class="fas fa-exclamation"></i>';
            } else {
                steps[1].className = `clr-flow-step ${step > 2 ? 'completed' : (step === 2 ? 'active' : '')}`;
                const c2 = steps[1].querySelector('.clr-flow-circle');
                if (c2) c2.innerHTML = step > 2 ? '<i class="fas fa-check"></i>' : '2';
            }

            // Divider 2
            if (dividers[1]) dividers[1].className = `clr-flow-divider ${step > 2 ? 'completed' : ''}`;

            // Step 3: Dept Head Review
            steps[2].className = `clr-flow-step ${step > 3 ? 'completed' : (step === 3 ? 'active' : '')}`;
            const c3 = steps[2].querySelector('.clr-flow-circle');
            if (c3) c3.innerHTML = step > 3 ? '<i class="fas fa-check"></i>' : '3';

            // Divider 3
            if (dividers[2]) dividers[2].className = `clr-flow-divider ${step > 3 ? 'completed' : ''}`;

            // Step 4: HR Final Approval
            steps[3].className = `clr-flow-step ${step > 4 ? 'completed' : (step === 4 ? 'active' : '')}`;
            const c4 = steps[3].querySelector('.clr-flow-circle');
            if (c4) c4.innerHTML = step > 4 ? '<i class="fas fa-check"></i>' : '4';

            // Divider 4
            if (dividers[3]) dividers[3].className = `clr-flow-divider ${step >= 5 ? 'completed' : ''}`;

            // Step 5: Cleared / Completed
            steps[4].className = `clr-flow-step ${step === 5 ? 'completed' : ''}`;
            const c5 = steps[4].querySelector('.clr-flow-circle');
            if (c5) c5.innerHTML = '<i class="fas fa-check-double"></i>';
        }
    }

    // ── Live Status Poller ───────────────────────────────────────────────────────
    async function pollFacultyClearanceStatus() {
        try {
            const res = await fetch(`${clearanceApi}?action=summary`);
            const data = await res.json();
            if (data?.ok && data.clearance && Array.isArray(data.clearance.items)) {
                data.clearance.items.forEach(item => {
                    const card = document.querySelector(`[data-office-row-id="${item.office_id}"]`);
                    if (card) {
                        const chip = card.querySelector('.office-chip');
                        const chipLabel = card.querySelector('.chip-label');
                        const remarksBox = card.querySelector('.office-remarks-container');
                        const rawStatus = String(item.status || '').trim().toLowerCase();

                        let chipClass = 'pending';
                        let chipIcon = 'fa-circle-dot';
                        let labelText = 'Pending Verification';

                        if (rawStatus === 'cleared' || rawStatus === 'approved') {
                            chipClass = 'cleared';
                            chipIcon = 'fa-check-circle';
                            labelText = 'Cleared';
                        } else if (rawStatus === 'denied' || rawStatus === 'hold' || rawStatus === 'rejected' || rawStatus === 'with deficiency' || rawStatus === 'with_deficiency') {
                            chipClass = 'deficiency';
                            chipIcon = 'fa-exclamation-triangle';
                            labelText = 'With Deficiency';
                        } else if (rawStatus === 'on hold' || rawStatus === 'on_hold') {
                            chipClass = 'onhold';
                            chipIcon = 'fa-pause-circle';
                            labelText = 'On Hold';
                        } else if (rawStatus === 'pending review' || rawStatus === 'pending verification' || rawStatus === 'under verification' || rawStatus === 'submitted') {
                            chipClass = 'review';
                            chipIcon = 'fa-hourglass-half';
                            labelText = 'Under Verification';
                        }

                        if (chip) {
                            chip.className = `clr-chip clr-chip-${chipClass} office-chip`;
                            const ico = chip.querySelector('i');
                            if (ico) ico.className = `fas ${chipIcon}`;
                        }
                        if (chipLabel) chipLabel.textContent = labelText;

                        if (remarksBox) {
                            if (chipClass === 'deficiency' && item.remarks) {
                                const cleanRmk = item.remarks.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '');
                                remarksBox.innerHTML = `
                                    <div class="office-deficiency-box">
                                        <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i>Deficiency Note:</div>
                                        <div class="office-remarks-text">${cleanRmk}</div>
                                    </div>`;
                            } else if (chipClass === 'onhold' && item.remarks) {
                                const cleanRmk = item.remarks.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '');
                                remarksBox.innerHTML = `
                                    <div class="office-onhold-box">
                                        <div class="fw-bold mb-1"><i class="fas fa-pause-circle me-1"></i>Office Note:</div>
                                        <div class="office-remarks-text">${cleanRmk}</div>
                                    </div>`;
                            } else if (chipClass === 'cleared' && item.cleared_at) {
                                const d = new Date(item.cleared_at.replace(' ', 'T'));
                                const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                remarksBox.innerHTML = `
                                    <div class="small text-success d-flex align-items-center gap-1">
                                        <i class="fas fa-badge-check"></i>
                                        <span>Cleared on ${dateStr}</span>
                                    </div>`;
                            }
                        }
                    }
                });

                const pb = document.getElementById('mainProgressBar');
                if (pb) {
                    pb.style.width = data.clearance.progress + '%';
                    if (data.clearance.progress === 100) pb.classList.add('full');
                    else pb.classList.remove('full');
                }
                const pf = document.getElementById('progressFraction');
                if (pf) pf.textContent = `${data.clearance.approved_items}/${data.clearance.total_items} offices cleared`;
                const pctLbl = document.getElementById('progressPctLabel');
                if (pctLbl) pctLbl.textContent = `${data.clearance.progress}% complete`;

                const pageBadge = document.getElementById('pageStatusBadge');
                if (pageBadge && data.clearance.status) {
                    const st = data.clearance.status;
                    let badgeClass = 'bg-secondary text-white';
                    let iconClass = 'fa-circle-dot';
                    if (st === 'Cleared') {
                        badgeClass = 'bg-success text-white';
                        iconClass = 'fa-check-double';
                    } else if (st === 'With Deficiency') {
                        badgeClass = 'bg-danger text-white';
                        iconClass = 'fa-exclamation-triangle';
                    } else if (st === 'For Final Approval' || st === 'For Department Head Approval') {
                        badgeClass = 'bg-warning text-dark';
                        iconClass = 'fa-user-check';
                    } else if (st === 'Under Verification') {
                        badgeClass = 'bg-info text-dark';
                        iconClass = 'fa-clock';
                    }
                    pageBadge.innerHTML = `<i class="fas ${iconClass} me-1"></i>${st}`;
                    pageBadge.className = `badge ${badgeClass} fs-6 px-3 py-2 shadow-sm`;
                }

                // Update the Workflow Lifecycle Stepper dynamically
                updateLifecycleStepper(data.clearance.status, data.clearance.approved_items, data.clearance.total_items);
            }
        } catch (e) { /* silent */ }
    }

    function updateLifecycleStepper(status, approvedCount, totalCount) {
        let step = 1;
        if (status === 'Cleared') {
            step = 5;
        } else if (status === 'For Final Approval') {
            step = 4;
        } else if (status === 'Under Verification' || approvedCount > 0) {
            step = 3;
        } else if (status === 'For Department Head Approval' || status === 'Pending Review' || status === 'In Progress') {
            step = 2;
        }

        const steps = document.querySelectorAll('.clr-flow-stepper .clr-flow-step');
        const dividers = document.querySelectorAll('.clr-flow-stepper .clr-flow-divider');

        steps.forEach((s, idx) => {
            const stepNum = idx + 1;
            s.classList.remove('active', 'completed', 'deficiency');
            const circle = s.querySelector('.clr-flow-circle');

            if (status === 'With Deficiency' && stepNum === 3) {
                s.classList.add('deficiency');
                if (circle) circle.innerHTML = '<i class="fas fa-exclamation"></i>';
            } else if (step > stepNum) {
                s.classList.add('completed');
                if (circle) circle.innerHTML = '<i class="fas fa-check"></i>';
            } else if (step === stepNum) {
                if (step === 5) {
                    s.classList.add('completed');
                    if (circle) circle.innerHTML = '<i class="fas fa-check-double"></i>';
                } else {
                    s.classList.add('active');
                    if (circle) circle.textContent = stepNum;
                }
            } else {
                if (circle) {
                    if (stepNum === 5) circle.innerHTML = '<i class="fas fa-check-double"></i>';
                    else circle.textContent = stepNum;
                }
            }
        });

        dividers.forEach((d, idx) => {
            const divNum = idx + 1;
            if (step > divNum) {
                d.classList.add('completed');
            } else {
                d.classList.remove('completed');
            }
        });
    }

    setInterval(pollFacultyClearanceStatus, 5000);
    pollFacultyClearanceStatus();

    /* ══════════════════════════════════════════════════════════════
       Per-Office File Selection & Staging
     ══════════════════════════════════════════════════════════════ */
    function handleDragOver(e, zone) {
        e.preventDefault();
        zone.classList.add('dragover');
    }

    function handleDragLeave(e, zone) {
        if (!zone.contains(e.relatedTarget)) {
            zone.classList.remove('dragover');
        }
    }

    function handleDrop(e, zone) {
        e.preventDefault();
        zone.classList.remove('dragover');
        const officeId = parseInt(zone.dataset.officeId, 10);
        if (!officeId || zone.dataset.blocked === '1') return;
        const files = e.dataTransfer?.files;
        if (!files || files.length === 0) return;
        const file = files[0];
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            showFacultyAlert('Only PDF files are allowed.', 'danger');
            return;
        }
        stageFile(officeId, file);
    }

    function handleFileSelected(input) {
        const officeId = parseInt(input.dataset.officeId, 10);
        if (!officeId || !input.files || input.files.length === 0) return;
        const file = input.files[0];
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            showFacultyAlert('Only PDF files are allowed.', 'danger');
            input.value = '';
            return;
        }
        stageFile(officeId, file);
    }

    function stageFile(officeId, file) {
        pendingFiles[officeId] = file;
        const stagedBox = document.getElementById(`stagedBox${officeId}`);
        const stagedName = document.getElementById(`stagedFileName${officeId}`);
        const zoneInner = document.getElementById(`zoneInner${officeId}`);

        if (stagedBox && stagedName) {
            stagedName.textContent = file.name;
            stagedBox.classList.remove('d-none');
        }
        if (zoneInner) {
            zoneInner.classList.add('d-none');
        }
        updateSubmitButtonState();
    }

    function cancelUpload(officeId) {
        delete pendingFiles[officeId];
        const stagedBox = document.getElementById(`stagedBox${officeId}`);
        const zoneInner = document.getElementById(`zoneInner${officeId}`);
        const input = document.getElementById(`fileInput${officeId}`);

        if (stagedBox) stagedBox.classList.add('d-none');
        if (zoneInner) zoneInner.classList.remove('d-none');
        if (input) input.value = '';
        updateSubmitButtonState();
    }

    function printClearanceAgreementForm() {
        const docEl = document.querySelector('.clr-conduct-doc');
        if (!docEl) {
            window.print();
            return;
        }
        const printContents = docEl.outerHTML;
        const printWindow = window.open('', '_blank', 'width=900,height=800');
        printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
    <title>Clearance Agreement Form - <?= facultyClearanceEsc($cfFullName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        @page { size: portrait; margin: 15mm; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #fff; color: #111; padding: 20px; }
        .clr-conduct-doc { background: #fff; border: 1.5px solid #222; border-radius: 6px; padding: 2.25rem 2.5rem; }
        .clr-conduct-header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 1.25rem; margin-bottom: 1.75rem; }
        .clr-conduct-title { font-size: 1.35rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 0.35rem; }
        .clr-conduct-subheading { font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #0d6efd; }
        .clr-conduct-section-label { font-size: 0.92rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; padding-bottom: 0.4rem; border-bottom: 1px solid #ccc; margin-top: 1.75rem; margin-bottom: 1.2rem; }
        .clr-conduct-field-row { display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.65rem; font-size: 0.92rem; }
        .clr-conduct-label { font-weight: 700; min-width: 175px; white-space: nowrap; color: #111; }
        .clr-conduct-underline { display: inline-block; min-width: 320px; border-bottom: 1.5px solid #111; padding: 0 0.35rem 2px; font-weight: 700; color: #0d6efd; }
        .clr-conduct-inline-underline { display: inline; border-bottom: 1.5px solid #111; padding: 0 0.15rem 1px; font-weight: 700; color: #0d6efd; }
        .clr-conduct-para { font-size: 0.95rem; line-height: 1.8; margin-bottom: 1.15rem; color: #111; }
        .clr-conduct-list { list-style: none; padding-left: 0; margin-bottom: 1.5rem; }
        .clr-conduct-list li { position: relative; padding-left: 1.6rem; margin-bottom: 0.65rem; font-size: 0.92rem; line-height: 1.55; color: #111; }
        .clr-conduct-list li::before { content: "•"; position: absolute; left: 0.5rem; top: -0.1rem; color: #0d6efd; font-size: 1.25rem; font-weight: bold; }
        .clr-conduct-ack-box { background: rgba(13, 110, 253, 0.04); border: 1px solid rgba(13, 110, 253, 0.25); border-radius: 0.65rem; padding: 1rem 1.25rem; margin-top: 1.5rem; }
        .btn, .form-check-input, input[type="checkbox"], #cfAgreementSubmitBar, #cfAgreementPendingBanner, #cfAgreementStatusBanner, #btnSubmitFacultyDeclaration { display: none !important; }
    </style>
</head>
<body>
    ${printContents}
</body>
</html>`);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 400);
    }
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>