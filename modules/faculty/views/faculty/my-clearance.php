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
// Steps: 1: Faculty Submit | 2: Dept Head Review | 3: Offices/Units Verification | 4: Cleared
$activeLifecycleStep = 1;
if ($status === 'Cleared') {
    $activeLifecycleStep = 4;
} elseif ($cfFormApproved || $status === 'Under Verification' || $cfApprovedCount > 0 || $status === 'For Final Approval') {
    $activeLifecycleStep = 3;
} elseif ($cfFormSubmitted || $status === 'For Department Head Approval') {
    $activeLifecycleStep = 2;
}

// Digital Office Approvals & Signatures collection
$clearanceId = (int) ($clearance['clearance_id'] ?? 0);
$officeApprovalsList = [];
$officeApprovalsKeyed = [];
if ($db && $clearanceId > 0) {
    facultyClearanceEnsureDigitalApprovalSchema($db);
    try {
        $oaStmt = $db->prepare("SELECT * FROM clearance_office_approvals WHERE clearance_id = ? ORDER BY approved_at ASC, id ASC");
        $oaStmt->execute([$clearanceId]);
        $officeApprovalsList = $oaStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($officeApprovalsList as $oa) {
            $officeApprovalsKeyed[$oa['office']] = $oa;
            $oaKey = facultyClearanceOfficeKey($oa['office']);
            $officeApprovalsKeyed[$oaKey] = $oa;
        }
    } catch (Throwable $e) {
        $officeApprovalsList = [];
    }
}

$signatoryOfficesDef = [
    'department' => [
        'name' => 'Department Clearance',
        'office' => 'Department Head / Dean',
        'icon' => 'fa-building-columns',
        'color' => 'primary',
    ],
    'academic' => [
        'name' => 'Academic Clearance',
        'office' => 'Registrar\'s Office',
        'icon' => 'fa-graduation-cap',
        'color' => 'info',
    ],
    'library' => [
        'name' => 'Library Clearance',
        'office' => 'Library & Learning Resource',
        'icon' => 'fa-book-open',
        'color' => 'secondary',
    ],
    'property' => [
        'name' => 'Property Clearance',
        'office' => 'Property & Custodian Office',
        'icon' => 'fa-boxes-stacked',
        'color' => 'warning',
    ],
    'financial' => [
        'name' => 'Financial Clearance',
        'office' => 'Finance & Accounting Office',
        'icon' => 'fa-receipt',
        'color' => 'success',
    ],
    'hr' => [
        'name' => 'HR Clearance',
        'office' => 'Human Resources (HR)',
        'icon' => 'fa-user-check',
        'color' => 'dark',
    ],
];

$signedOfficesCount = 0;
foreach ($signatoryOfficesDef as $sKey => $sDef) {
    $approval = $officeApprovalsKeyed[$sKey] ?? ($officeApprovalsKeyed[$sDef['name']] ?? null);
    if (!empty($approval['signature_data'])) {
        $signedOfficesCount++;
    }
}
?>
<style>
    /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       FACULTY CLEARANCE PORTAL â€” Modern Clean Styles
       â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
    .clr-page {
        padding: 1.5rem 1.75rem;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    /* â”€â”€ Workflow Lifecycle Stepper â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Sections & Cards (Dean Faculty Directory style) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .clr-card {
        background: var(--bs-card-bg, var(--bs-body-bg));
        border: 0 !important;
        border-radius: 1rem;
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .clr-card-header {
        padding: 1rem 1.4rem;
        background-color: var(--bs-primary, #0d6efd);
        color: #ffffff;
        border-bottom: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .clr-card-header .fw-bold,
    .clr-card-header h5,
    .clr-card-header h6,
    .clr-card-header .card-title {
        color: #ffffff !important;
    }

    .clr-card-header .text-body-secondary,
    .clr-card-header .small {
        color: rgba(255, 255, 255, 0.78) !important;
    }

    .card-header .rounded-circle.bg-white,
    .office-card-header .rounded-circle.bg-white,
    .clr-card-header .rounded-circle.bg-white,
    .clr-card-icon-wrap,
    .office-icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background-color: #ffffff !important;
        color: var(--bs-primary, #0d6efd) !important;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, .12);
    }

    .card-header .rounded-circle.bg-white i,
    .office-card-header .rounded-circle.bg-white i,
    .clr-card-header .rounded-circle.bg-white i,
    .clr-card-icon-wrap i,
    .office-icon-wrap i {
        color: var(--bs-primary, #0d6efd) !important;
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

    /* â”€â”€ Progress Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Office Section Grid & Cards (Dean Faculty Directory style) â”€â”€ */
    .office-card {
        background: var(--bs-card-bg, var(--bs-body-bg));
        border: 0 !important;
        border-radius: 1rem;
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }

    .office-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1) !important;
    }

    .office-card-header {
        padding: 1rem 1.25rem;
        background-color: var(--bs-primary, #0d6efd);
        color: #ffffff;
        border-bottom: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .office-card-header .fw-bold {
        color: #ffffff !important;
    }

    .office-card-header .text-body-secondary {
        color: rgba(255, 255, 255, 0.78) !important;
    }

    /* Status chip on primary office header â€” white pill badges */
    .office-card-header .clr-chip,
    .card-header .clr-chip {
        background: #ffffff !important;
        border: none !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .15);
    }

    .office-card-header .clr-chip-cleared {
        color: #198754 !important;
    }

    .office-card-header .clr-chip-ready {
        color: #0d6efd !important;
    }

    .office-card-header .clr-chip-review {
        color: #0d6efd !important;
    }

    .office-card-header .clr-chip-deficiency {
        color: #dc3545 !important;
    }

    .office-card-header .clr-chip-onhold {
        color: #997404 !important;
    }

    .office-card-header .clr-chip-pending {
        color: #6c757d !important;
    }

    .office-card-header .clr-chip-locked {
        color: #6c757d !important;
        background: #f1f3f5 !important;
    }

    /* â”€â”€ Dark Mode Consistency (Matches the User's Dark Theme Banner) â”€â”€ */
    [data-theme="dark"] .clr-card,
    [data-theme="dark"] .office-card {
        background: var(--sms-surface, rgba(18, 28, 52, 0.72)) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    [data-theme="dark"] .clr-card-header,
    [data-theme="dark"] .office-card-header,
    [data-theme="dark"] .card-header.bg-primary {
        background-color: rgba(96, 165, 250, 0.22) !important;
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    [data-theme="dark"] .card-header .rounded-circle.bg-white,
    [data-theme="dark"] .office-card-header .rounded-circle.bg-white,
    [data-theme="dark"] .clr-card-header .rounded-circle.bg-white,
    [data-theme="dark"] .clr-card-icon-wrap,
    [data-theme="dark"] .office-icon-wrap,
    [data-theme="dark"] .rounded-circle.bg-white {
        background-color: var(--sms-surface-muted, #131c30) !important;
        color: var(--sms-primary, #60a5fa) !important;
        border: 1px solid rgba(96, 165, 250, 0.2) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .3) !important;
    }

    [data-theme="dark"] .card-header .rounded-circle.bg-white i,
    [data-theme="dark"] .office-card-header .rounded-circle.bg-white i,
    [data-theme="dark"] .clr-card-header .rounded-circle.bg-white i,
    [data-theme="dark"] .clr-card-icon-wrap i,
    [data-theme="dark"] .office-icon-wrap i {
        color: var(--sms-primary, #60a5fa) !important;
    }

    [data-theme="dark"] .card-header .btn-light {
        background-color: #ffffff !important;
        color: #2563eb !important;
        border: none !important;
    }

    [data-theme="dark"] .card-header .badge.bg-white,
    [data-theme="dark"] .office-card-header .clr-chip,
    [data-theme="dark"] .card-header .clr-chip {
        background-color: var(--sms-surface-muted, #131c30) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .2) !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-cleared {
        color: #34d399 !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-ready {
        color: #60a5fa !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-review {
        color: #60a5fa !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-deficiency {
        color: #f87171 !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-onhold {
        color: #fbbf24 !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-pending {
        color: #94a3b8 !important;
    }

    [data-theme="dark"] .office-card-header .clr-chip-locked {
        color: #94a3b8 !important;
        background-color: rgba(30, 41, 59, 0.9) !important;
    }

    [data-theme="dark"] .clr-flow-stepper {
        background: var(--sms-surface, rgba(18, 28, 52, 0.72)) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-theme="dark"] .clr-flow-circle {
        background: var(--sms-surface-muted, #131c30) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: var(--sms-text-muted, #94a3b8) !important;
    }

    [data-theme="dark"] .clr-flow-step.active .clr-flow-circle {
        border-color: #60a5fa !important;
        background: #3b82f6 !important;
        color: #fff !important;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, .25) !important;
    }

    [data-theme="dark"] .clr-flow-step.completed .clr-flow-circle {
        border-color: #34d399 !important;
        background: #10b981 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .clr-flow-divider {
        background: rgba(255, 255, 255, 0.12) !important;
    }

    [data-theme="dark"] .clr-flow-divider.completed {
        background: #10b981 !important;
    }

    [data-theme="dark"] .clr-info-label {
        color: var(--sms-text-muted, #94a3b8) !important;
    }

    [data-theme="dark"] .clr-info-value {
        color: var(--sms-text-strong, #f8fafc) !important;
    }

    [data-theme="dark"] .office-checklist li {
        color: var(--sms-text, #cbd5e1) !important;
    }

    [data-theme="dark"] .office-upload-zone {
        background: rgba(15, 23, 42, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    [data-theme="dark"] .office-upload-zone:hover {
        border-color: rgba(96, 165, 250, 0.4) !important;
        background: rgba(15, 23, 42, 0.6) !important;
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

    .office-checklist li i.text-danger {
        color: #dc3545 !important;
        opacity: 1 !important;
    }

    .office-checklist li i.text-success {
        color: #198754 !important;
        opacity: 1 !important;
    }

    /* â”€â”€ Status Badges & Chips â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    .clr-chip-ready {
        background: rgba(13, 110, 253, .12);
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, .3);
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

    .clr-chip-locked {
        background: rgba(108, 117, 125, .15);
        color: #6c757d;
        border: 1px solid rgba(108, 117, 125, .3);
    }

    /* Sequential Unlocking & Locked Card Styles */
    .office-card.office-card-locked {
        opacity: 0.72;
        border-color: #dee2e6 !important;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

    .office-card.office-card-locked .office-card-header {
        background: #6c757d !important;
        background-color: #6c757d !important;
    }

    .office-card.office-card-locked .office-card-header .rounded-circle {
        background-color: #e9ecef !important;
        color: #6c757d !important;
    }

    .office-card.office-card-locked .office-card-header .rounded-circle i {
        color: #6c757d !important;
    }

    .office-card.office-card-locked .office-upload-zone {
        background-color: #f1f3f5 !important;
        border-color: #ced4da !important;
        cursor: not-allowed !important;
        pointer-events: none;
    }

    .office-card.office-card-locked .office-checklist {
        opacity: 0.65;
    }

    [data-theme="dark"] .office-card.office-card-locked {
        opacity: 0.65;
        background: rgba(18, 28, 52, 0.45) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-theme="dark"] .office-card.office-card-locked .office-card-header {
        background: rgba(71, 85, 105, 0.4) !important;
        background-color: rgba(71, 85, 105, 0.4) !important;
    }

    [data-theme="dark"] .office-card.office-card-locked .office-card-header .rounded-circle {
        background-color: rgba(30, 41, 59, 0.8) !important;
        color: #94a3b8 !important;
    }

    [data-theme="dark"] .office-card.office-card-locked .office-card-header .rounded-circle i {
        color: #94a3b8 !important;
    }

    [data-theme="dark"] .office-card.office-card-locked .office-upload-zone {
        background-color: rgba(15, 23, 42, 0.35) !important;
        border-color: rgba(255, 255, 255, 0.06) !important;
    }

    /* â”€â”€ Remarks & Deficiency Alerts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Declaration & Action Footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Upload Zone â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Digital Signature & Declaration Box â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .signature-pad-container {
        border: 2px dashed var(--bs-border-color);
        border-radius: .75rem;
        background: var(--bs-tertiary-bg);
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
        border-bottom: 1px dashed var(--bs-border-color);
        pointer-events: none;
    }

    .signature-hint {
        position: absolute;
        bottom: 6px;
        left: 20px;
        font-size: 0.7rem;
        color: var(--bs-secondary-color);
        pointer-events: none;
    }

    .signature-preview-img {
        max-height: 75px;
        max-width: 260px;
        object-fit: contain;
        display: block;
    }

    /* â”€â”€ Clearance Form â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
        content: "";
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

    <!-- â”€â”€ Page Title Row â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-check text-primary me-2"></i>Faculty Clearance Portal
            </h4>
            <p class="text-body-secondary small mb-0">Track verification status across all 6 administrative for
                <?= facultyClearanceEsc($cfTerm) ?>, S.Y. <?= facultyClearanceEsc($cfSY) ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php
            $statusBadgeClass = match ($status) {
                'Cleared' => 'bg-success text-white',
                'With Deficiency' => 'bg-danger text-white',
                'For Final Approval' => 'bg-warning text-dark',
                'Under Verification' => 'bg-info text-dark',
                default => 'bg-secondary text-white',
            };
            $statusIcon = match ($status) {
                'Cleared' => 'fa-check-double',
                'With Deficiency' => 'fa-exclamation-triangle',
                'For Final Approval' => 'fa-user-check',
                'Under Verification' => 'fa-clock',
                default => 'fa-circle-dot',
            };
            ?>
            <?php if ($status !== 'For Department Head Approval' && !empty($status) && $status !== 'Not Submitted'): ?>
                <span class="badge <?= $statusBadgeClass ?> fs-6 px-3 py-2 shadow-sm" id="pageStatusBadge">
                    <i class="fas <?= $statusIcon ?> me-1"></i>
                    <?= facultyClearanceEsc($status) ?>
                </span>
            <?php else: ?>
                <span class="badge fs-6 px-3 py-2 shadow-sm d-none" id="pageStatusBadge"></span>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                onclick="refreshClearanceStatus()" title="Refresh Live Status">

                <span class="d-none d-md-inline small"></span>
                <i class="fas fa-sync-alt"></i>
                <span class="d-none d-md-inline small">Refresh</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                onclick="confirmResetClearance()" title="Reset Status Tracker, Signatures & Files">
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

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• VIEW 1: CLEARANCE OF CONDUCT AGREEMENT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div id="viewAgreementSection" class="d-none mb-4">
            <div class="card border-0 shadow-sm overflow-hidden mb-3" id="facultyClearanceFormCard">
                <div
                    class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-sm"
                            style="width:42px;height:42px;">
                            <i class="fas fa-file-contract fs-5 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-0 fs-6">Clearance Form</h5>
                            <div class="small text-white-75">Official Faculty Clearance Form</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                            class="btn btn-sm btn-light fw-semibold d-flex align-items-center gap-1 shadow-sm px-3"
                            onclick="printClearanceAgreementForm()" title="Print Clearance Form">
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
                            <div class="clr-conduct-title">Clearance Form</div>
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
                                        <div class="fw-bold text-warning-emphasis">Clearance Form Submitted Awaiting
                                            Department Head Approval</div>
                                        <div class="small text-body-secondary">
                                            Submitted on <strong><?= facultyClearanceEsc($cfFormSubmittedAt) ?></strong> Â·
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
                                            Â·
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

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• VIEW 2: FACULTY CLEARANCE PORTAL â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div id="viewPortalSection">

            <!-- â”€â”€ Workflow Lifecycle Stepper â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
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

                <div class="clr-flow-divider <?= $activeLifecycleStep >= 4 ? 'completed' : '' ?>"></div>

                <!-- 4. Cleared -->
                <div class="clr-flow-step <?= $activeLifecycleStep === 4 ? 'completed' : '' ?>">
                    <div class="clr-flow-circle">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="clr-flow-title">Clearance<br>Completed</div>
                </div>
            </div>

            <!-- â”€â”€ Clearance Form Gate Banner â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
            <?php if (!$cfFormApproved): ?>
                <?php if ($cfFormSubmitted && $cfFormStatus === 'Pending Review'): ?>
                    <div class="card border-0 shadow-sm overflow-hidden mb-4">
                        <div
                            class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-sm"
                                    style="width:42px;height:42px;">
                                    <i class="fas fa-hourglass-half fs-5 text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-white mb-1 fs-6">Clearance Form Under Department Head Review
                                    </h6>
                                    <p class="small text-white-75 mb-0">Your Clearance Form has been submitted and is awaiting
                                        Department Head review and endorsement. Requirement uploads and portal submission will
                                        unlock
                                        once approved.</p>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light text-primary fw-semibold shadow-sm px-3"
                                onclick="switchClearanceView('agreement')">
                                <i class="fas fa-file-contract me-1"></i>View Agreement Form
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm overflow-hidden mb-4">
                        <div
                            class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-sm"
                                    style="width:42px;height:42px;">
                                    <i class="fas fa-file-signature fs-5 text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-white mb-1 fs-6">Step 1 Required: Submit Clearance Form</h6>
                                    <p class="small text-white-75 mb-0">You must submit and obtain Department Head endorsement on
                                        your Clearance Form before unit requirement uploads can begin.</p>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light text-primary fw-semibold shadow-sm px-3"
                                onclick="switchClearanceView('agreement')">
                                <i class="fas fa-file-contract me-1"></i>Open Clearance Form
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- â”€â”€ Faculty Information Card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
            <div class="card border-0 shadow-sm overflow-hidden mb-4">
                <div
                    class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-sm"
                            style="width:42px;height:42px;">
                            <i class="fas fa-id-card fs-5 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-0 fs-6">Faculty Information</h5>
                            <div class="small text-white-75">Official Personnel &amp; Department Record</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge bg-white fs-7 px-2 py-1 shadow-sm <?= $cfSubmitted ? 'text-success' : 'text-secondary' ?> fw-semibold"
                            id="formSubmittedBadge">
                            <i class="fas <?= $cfSubmitted ? 'fa-check-circle' : 'fa-circle-dot' ?> me-1"></i>
                            <?= $cfSubmitted ? 'Clearance Submitted' : 'Pending Submission' ?>
                        </span>
                        <span class="badge bg-white text-dark fs-7 px-2 py-1 shadow-sm d-none d-md-inline">Form No:
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

            <!-- â”€â”€ The 6 Main Clearance Sections â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-building-circle-check text-primary me-2"></i>Clearance
                        Verification Sections</h5>
                    <p class="text-body-secondary small mb-0">Each responsible office reviews and confirms your clearance
                        accountabilities directly.</p>
                </div>
                <span class="badge bg-primary text-white shadow-sm px-3 py-2">6 Clearance Units</span>
            </div>

            <div class="row g-3 mb-4">
                <?php
                $sequentialProgression = facultyClearanceStageProgression($clearance);
                foreach ($offices as $index => $foff):
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

                    $fProg = $sequentialProgression[$fName] ?? null;
                    $fStageState = $fProg['state'] ?? 'locked';
                    $isStageUnlocked = !empty($fProg['is_unlocked']);
                    $fLockReason = $fProg['lock_reason'] ?? 'Complete the previous clearance to unlock this step.';

                    if ($fStageState === 'cleared') {
                        $cardClass = 'office-card office-card-cleared';
                        $fChip = 'cleared';
                        $fChipIcon = 'fa-check-circle';
                        $fChipLabel = 'Cleared';
                        $uploadBlocked = true;
                        $uploadHint = 'This section has been cleared by the office.';
                    } elseif ($fStageState === 'with_issue') {
                        $cardClass = 'office-card office-card-deficiency';
                        $fChip = 'deficiency';
                        $fChipIcon = 'fa-exclamation-triangle';
                        $fChipLabel = 'With Issue';
                        $uploadBlocked = false;
                        $uploadHint = 'Upload a new document to resolve flagged issue';
                    } elseif ($fStageState === 'in_progress') {
                        $cardClass = 'office-card office-card-progress';
                        $fChip = 'review';
                        $fChipIcon = 'fa-hourglass-half';
                        $fChipLabel = 'Under Verification';
                        $uploadBlocked = true;
                        $uploadHint = 'Document is under review by ' . facultyClearanceEsc($meta['office']);
                    } elseif ($fStageState === 'ready') {
                        $cardClass = 'office-card office-card-ready';
                        $fChip = 'ready';
                        $fChipIcon = 'fa-circle-dot';
                        $fChipLabel = 'Ready for Verification';
                        $uploadBlocked = false;
                        $uploadHint = 'Upload supporting documents (PDF, max 10 MB)';
                    } else { // locked
                        $cardClass = 'office-card office-card-locked';
                        $fChip = 'locked';
                        $fChipIcon = 'fa-lock';
                        $fChipLabel = 'Locked';
                        $uploadBlocked = true;
                        $uploadHint = $fLockReason ?: 'Complete the previous clearance to unlock this step.';
                    }

                    $fDate = $fItem ? (string) ($fItem['cleared_at'] ?? ($fItem['updated_at'] ?? '')) : '';
                    $fRemarks = $fItem ? (string) ($fItem['remarks'] ?? '') : '';
                    $fCleanRmk = preg_replace('/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i', '', $fRemarks);
                    $fCleanRmk = trim(preg_replace('/<!--SCOPE_STATE:.*?-->/s', '', $fCleanRmk));
                    $fCleanRmk = trim(preg_replace('/Deficiencies Flagged:[\s\S]*?(?=Instructions:|$)/i', '', $fCleanRmk));
                    $fCleanRmk = trim(preg_replace('/Complied:[\s\S]*?(?=Instructions:|$)/i', '', $fCleanRmk));
                    $fCleanRmk = trim(preg_replace('/^Instructions:\s*/i', '', $fCleanRmk));

                    $fScopeState = null;
                    if (preg_match('/<!--SCOPE_STATE:(.*?)-->/s', $fRemarks, $mScope)) {
                        $fScopeState = json_decode($mScope[1], true);
                    }
                    $fScopeFailed = (is_array($fScopeState) && isset($fScopeState['failed']) && is_array($fScopeState['failed'])) ? $fScopeFailed = $fScopeState['failed'] : [];
                    $fScopePassed = (is_array($fScopeState) && isset($fScopeState['passed']) && is_array($fScopeState['passed'])) ? $fScopePassed = $fScopeState['passed'] : [];

                    $fFileName = $fItem ? (string) ($fItem['original_name'] ?? basename((string) ($fItem['file_path'] ?? ''))) : '';
                    $hasScopeFailed = !empty($fScopeFailed);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="<?= $cardClass ?>" data-office-row-id="<?= $fOid ?>" data-stage-state="<?= $fStageState ?>"
                            data-office-name="<?= facultyClearanceEsc($fName) ?>">
                            <div
                                class="office-card-header card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-sm"
                                        style="width:42px;height:42px;">
                                        <i class="fas <?= $meta['icon'] ?? 'fa-circle-check' ?> text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-white" style="font-size:.9rem;">
                                            <?= facultyClearanceEsc($meta['name']) ?>
                                        </div>
                                        <div class="text-white-75 small" style="font-size:.72rem;">
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
                                        <?php foreach ($meta['items'] as $chk):
                                            $chkNorm = trim(mb_strtolower($chk));
                                            $isFailed = false;
                                            $isPassed = false;
                                            if (!empty($fScopeFailed)) {
                                                foreach ($fScopeFailed as $ff) {
                                                    if (trim(mb_strtolower($ff)) === $chkNorm) {
                                                        $isFailed = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!empty($fScopePassed)) {
                                                foreach ($fScopePassed as $fp) {
                                                    if (trim(mb_strtolower($fp)) === $chkNorm) {
                                                        $isPassed = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($fChip === 'cleared') {
                                                $isPassed = true;
                                            }
                                            ?>
                                            <li
                                                class="<?= $isFailed ? 'text-danger fw-semibold d-flex align-items-center justify-content-between gap-2' : ($isPassed ? 'text-success d-flex align-items-center gap-2' : 'd-flex align-items-center gap-2') ?>">
                                                <?php if ($isFailed): ?>
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        <i class="fas fa-times-circle text-danger flex-shrink-0"
                                                            title="Flagged as Deficient"></i>
                                                        <span class="flex-grow-1"><?= facultyClearanceEsc($chk) ?></span>
                                                    </div>
                                                    <span
                                                        class="badge bg-danger-subtle text-danger border border-danger-subtle flex-shrink-0"
                                                        style="font-size:0.68rem;">
                                                        <i class="fas fa-file-arrow-up me-1"></i>Upload a new File
                                                    </span>
                                                <?php elseif ($isPassed): ?>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-check-circle text-success flex-shrink-0" title="Complied"></i>
                                                        <span class="flex-grow-1"><?= facultyClearanceEsc($chk) ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-check-circle flex-shrink-0"></i>
                                                        <span class="flex-grow-1"><?= facultyClearanceEsc($chk) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Remarks / Office Note box -->
                                <div class="office-remarks-container">
                                    <?php if ($fChip === 'deficiency' && $fCleanRmk !== ''): ?>
                                        <div class="office-deficiency-box mb-2">
                                            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Issue
                                                Reported:</div>
                                            <div><?= nl2br(facultyClearanceEsc($fCleanRmk)) ?></div>
                                        </div>
                                    <?php elseif ($fChip === 'onhold' && $fCleanRmk !== ''): ?>
                                        <div class="office-onhold-box mb-2">
                                            <div class="fw-bold mb-1"><i class="fas fa-pause-circle me-1"></i>Office Note:</div>
                                            <div class="office-remarks-text"><?= nl2br(facultyClearanceEsc($fCleanRmk)) ?></div>
                                        </div>
                                    <?php elseif ($fChip === 'cleared' && $fDate !== ''): ?>
                                        <div class="small text-success d-flex align-items-center gap-1 mb-2">
                                            <i class="fas fa-badge-check"></i>
                                            <span>Cleared on <?= date('M j, Y', strtotime($fDate)) ?></span>
                                        </div>
                                    <?php elseif ($fChip === 'locked'): ?>
                                        <div class="small text-body-secondary d-flex align-items-center gap-1 mb-2">
                                            <i class="fas fa-lock text-secondary opacity-75"></i>
                                            <span>Sequential Step Locked</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-body-secondary d-flex align-items-center gap-1 mb-2">
                                            <i class="fas fa-shield-halved text-secondary opacity-50"></i>
                                            <span>Verified directly by <?= facultyClearanceEsc($meta['office']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- ── Upload Zone ────────────────────────────────────── -->
                                <div class="office-upload-zone <?= $uploadBlocked ? 'upload-blocked' : '' ?>"
                                    id="uploadZone<?= $fOid ?>" data-office-id="<?= $fOid ?>"
                                    data-office-name="<?= facultyClearanceEsc($fName) ?>"
                                    data-blocked="<?= $uploadBlocked ? '1' : '0' ?>" <?= !$uploadBlocked ? 'ondragover="handleDragOver(event,this)" ondragleave="handleDragLeave(event,this)" ondrop="handleDrop(event,this)"' : '' ?>>

                                    <?php if ($fFileName !== '' && !in_array($fChip, ['cleared', 'locked'], true)): ?>
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

                                    <?php if ($fStageState === 'locked'): ?>
                                        <div class="upload-zone-inner py-3 text-center" id="zoneInner<?= $fOid ?>">
                                            <i class="fas fa-lock text-secondary mb-2" style="font-size:1.75rem;"></i>
                                            <div class="fw-semibold text-secondary small mb-1">Clearance Step Locked</div>
                                            <div class="upload-hint-text text-body-secondary small">
                                                <?= facultyClearanceEsc($uploadHint) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="upload-zone-inner <?= $uploadBlocked ? 'opacity-50' : '' ?>"
                                            id="zoneInner<?= $fOid ?>">
                                            <i class="fas <?= ($fChip === 'cleared' ? 'fa-check-circle text-success' : ($fChip === 'review' ? 'fa-clock text-info' : ($fChip === 'deficiency' ? 'fa-triangle-exclamation text-danger' : 'fa-cloud-arrow-up text-primary'))) ?> mb-1"
                                                style="font-size:1.5rem;"></i>
                                            <div class="upload-hint-text"><?= htmlspecialchars($uploadHint) ?></div>
                                            <?php if (!$uploadBlocked): ?>
                                                <label class="btn-upload-choose" for="fileInput<?= $fOid ?>"
                                                    id="chooseLabel<?= $fOid ?>">
                                                    <i class="fas fa-folder-open me-1"></i>
                                                    <?= $hasScopeFailed ? 'Upload a new File' : ($fFileName !== '' ? 'Select New PDF' : 'Choose PDF') ?>
                                                </label>
                                                <input type="file" id="fileInput<?= $fOid ?>" class="office-file-input d-none"
                                                    accept=".pdf,application/pdf" data-office-id="<?= $fOid ?>"
                                                    onchange="handleFileSelected(this)">
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Submit Attached Files Bar (Bottom of Clearance Verification Sections) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 d-none overflow-hidden" id="resubmitBar">
                <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3">
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
            </div>

            <!-- ── Office Clearance Signatures Panel ───────────────────────────── -->
            <div class="card border-0 shadow-sm overflow-hidden mb-4" id="officeSignaturesPanel">
                <div
                    class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                            style="width:40px;height:40px;">
                            <i class="fas fa-stamp fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-body-emphasis mb-0 fs-6">Office Clearance Signatures</h5>
                            <div class="small text-body-secondary">Official digital sign-offs &amp; authorized
                                representative signatures</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-semibold"
                            id="officeSignaturesCounter">
                            <i class="fas fa-signature me-1"></i><?= $signedOfficesCount ?> of
                            <?= count($signatoryOfficesDef) ?> Signed
                        </span>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2.5 rounded-pill"
                            onclick="refreshOfficeSignatures()" title="Refresh Signatures">
                            <i class="fas fa-rotate me-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2.5 rounded-pill"
                            onclick="confirmResetOfficeSignatures()" title="Reset Official Clearance Signatures">
                            <i class="fas fa-rotate-left me-1"></i>Reset Signatures
                        </button>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <div class="row g-3" id="officeSignaturesGrid">
                        <?php foreach ($signatoryOfficesDef as $sKey => $sDef):
                            $approval = $officeApprovalsKeyed[$sKey] ?? ($officeApprovalsKeyed[$sDef['name']] ?? null);
                            $hasSig = !empty($approval['signature_data']);
                            $isApproved = !empty($approval['status']) && in_array($approval['status'], ['Approved', 'Cleared', 'Final Verified'], true);
                            $appDate = !empty($approval['approved_at']) ? date('M d, Y · h:i A', strtotime($approval['approved_at'])) : null;
                            ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div
                                    class="card h-100 border rounded-3 overflow-hidden <?= $hasSig ? 'border-success-subtle shadow-sm bg-body' : 'border-dashed bg-body-tertiary bg-opacity-50' ?>">
                                    <div
                                        class="card-header py-2.5 px-3 d-flex align-items-center justify-content-between <?= $hasSig ? 'bg-success-subtle border-bottom border-success-subtle' : 'bg-body-secondary border-bottom' ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas <?= $sDef['icon'] ?> text-<?= $sDef['color'] ?>"></i>
                                            <span
                                                class="fw-semibold text-body-emphasis small"><?= facultyClearanceEsc($sDef['name']) ?></span>
                                        </div>
                                        <?php if ($hasSig): ?>
                                            <span class="badge bg-success text-white px-2 py-0.5" style="font-size:0.68rem;">
                                                <i class="fas fa-check-circle me-1"></i>Signed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-body-secondary border px-2 py-0.5"
                                                style="font-size:0.68rem;">
                                                <i class="fas fa-hourglass-half me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                                        <?php if ($hasSig): ?>
                                            <div>
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <small class="text-body-secondary d-block"
                                                            style="font-size:0.7rem; letter-spacing:0.03em;">APPROVED BY</small>
                                                        <strong
                                                            class="text-body-emphasis small d-block"><?= facultyClearanceEsc($approval['approver_name'] ?? 'Authorized Officer') ?></strong>
                                                        <small class="text-body-secondary"
                                                            style="font-size:0.72rem;"><?= facultyClearanceEsc($sDef['office']) ?></small>
                                                    </div>
                                                    <?php if (!empty($approval['approval_ref'])): ?>
                                                        <span
                                                            class="badge bg-body-tertiary text-body-secondary border font-monospace px-1.5 py-0.5"
                                                            style="font-size:0.68rem;" title="Official Approval Reference">
                                                            <?= facultyClearanceEsc($approval['approval_ref']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Signature Display Pad -->
                                                <div class="p-2 bg-white rounded-2 border text-center my-2 shadow-xs"
                                                    style="min-height:85px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                                                    <img src="<?= facultyClearanceEsc($approval['signature_data']) ?>"
                                                        alt="Signature of <?= facultyClearanceEsc($approval['approver_name'] ?? 'Officer') ?>"
                                                        style="max-height:64px; max-width:100%; object-fit:contain;">
                                                    <div class="w-100 border-top mt-1 pt-1 d-flex justify-content-between align-items-center text-muted"
                                                        style="font-size:0.62rem;">
                                                        <span>DIGITAL SIGNATURE</span>
                                                        <span><?= $appDate ? facultyClearanceEsc($appDate) : '' ?></span>
                                                    </div>
                                                </div>

                                                <?php if (!empty($approval['remarks'])): ?>
                                                    <div class="small text-body-secondary bg-body-tertiary p-1.5 rounded border border-light-subtle fst-italic mt-1"
                                                        style="font-size:0.72rem;">
                                                        <i class="fas fa-quote-left text-muted me-1"
                                                            style="font-size:0.65rem;"></i><?= facultyClearanceEsc($approval['remarks']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4 my-auto">
                                                <div class="rounded-circle bg-body-secondary text-body-tertiary d-flex align-items-center justify-content-center mx-auto mb-2"
                                                    style="width:44px;height:44px;">
                                                    <i class="fas fa-file-signature fs-5 opacity-50"></i>
                                                </div>
                                                <h6 class="text-body-secondary fw-semibold small mb-1">Awaiting Signature</h6>
                                                <small class="text-body-tertiary d-block" style="font-size:0.72rem;">
                                                    <?= facultyClearanceEsc($sDef['office']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ── Faculty Declaration & Digital Signature (Placed at the bottom) ──────────────────────── -->
            <div class="card border-0 shadow-sm overflow-hidden mb-4" id="facultyDeclarationCard">
                <div
                    class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-sm"
                            style="width:42px;height:42px;">
                            <i class="fas fa-file-signature fs-5 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-0 fs-6">Faculty Declaration</h5>
                            <div class="small text-white-75">Official Clearance Certification &amp; Digital Signature</div>
                        </div>
                    </div>
                    <span
                        class="badge bg-white fs-7 px-3 py-2 shadow-sm fw-semibold <?= !empty($cfSignatureData) ? (($status === 'Cleared') ? 'text-success' : 'text-warning') : (($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0) ? 'text-primary' : 'text-secondary') ?>"
                        id="declarationBadge">
                        <i
                            class="fas <?= !empty($cfSignatureData) ? (($status === 'Cleared') ? 'fa-check-circle' : 'fa-hourglass-half') : (($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0) ? 'fa-pen-clip' : 'fa-lock') ?> me-1"></i>
                        <?= !empty($cfSignatureData) ? (($status === 'Cleared') ? 'Signed' : 'Pending Department Head Review') : (($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0) ? 'Ready to Sign' : 'Locked Pending Document Approvals') ?>
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
                                <?php if ($status !== 'Cleared'): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                                            onclick="confirmResetFacultySignature()" title="Reset Declaration Signature">
                                            <i class="fas fa-rotate-left"></i> Reset Signature
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($cfApprovedCount >= $cfTotalCount && $cfTotalCount > 0): ?>
                        <!-- All documents approved â€” signature pad unlocked -->
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
                                    <div class="p-3 bg-body-tertiary rounded-3 border mb-3">
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
                        <!-- Locked â€” not all documents approved yet -->
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
                <!-- â”€â”€ Clearance Requirements Unified Submission Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                <div class="clr-card">
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

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• MODALS â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

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
                    This will clear all uploaded documents, official clearance signatures, remove office review flags, and reset your status tracker
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

<!-- Confirm Reset Office Signatures Modal -->
<div class="modal fade" id="confirmResetOfficeSignaturesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;">
                        <i class="fas fa-signature text-danger fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Reset Signatures?</h6>
                        <small class="text-body-secondary">Official office sign-offs</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-body-secondary small mb-0">
                    This will clear all <strong>official clearance signatures</strong> and reset office approvals back to pending state.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger flex-fill fw-semibold" id="confirmResetOfficeSignaturesBtn"
                    onclick="executeResetOfficeSignatures()">
                    <i class="fas fa-trash-alt me-1"></i> Yes, Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Reset Faculty Signature Modal -->
<div class="modal fade" id="confirmResetFacultySignatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px;height:44px;">
                        <i class="fas fa-pen-fancy text-danger fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Reset Signature?</h6>
                        <small class="text-body-secondary">Faculty Declaration</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-body-secondary small mb-0">
                    This will remove your submitted digital signature so you can re-sign your Faculty Declaration.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger flex-fill fw-semibold" id="confirmResetFacultySignatureBtn"
                    onclick="executeResetFacultySignature()">
                    <i class="fas fa-trash-alt me-1"></i> Yes, Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• SCRIPTS â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<script>
    const clearanceApi = '<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php';

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        }[character]));
    }

    // ── Digital Office Signatures Dynamic Refresh ────────────────────────────
    const CLEARANCE_RECORD_ID = <?= (int) $clearanceId ?>;
    const SIGNATORY_OFFICES = <?= json_encode($signatoryOfficesDef) ?>;

    async function refreshOfficeSignatures() {
        if (!CLEARANCE_RECORD_ID) return;
        const grid = document.getElementById('officeSignaturesGrid');
        const counter = document.getElementById('officeSignaturesCounter');
        try {
            const resp = await fetch(`${clearanceApi}?action=get-office-signatures&clearance_id=${CLEARANCE_RECORD_ID}`);
            const data = await resp.json();
            if (!data.ok || !Array.isArray(data.signatures)) return;

            const sigByOffice = {};
            data.signatures.forEach(s => {
                if (s.office) {
                    sigByOffice[s.office] = s;
                    const norm = s.office.toLowerCase();
                    if (norm.includes('acad') || norm.includes('regist')) sigByOffice['academic'] = s;
                    if (norm.includes('library')) sigByOffice['library'] = s;
                    if (norm.includes('property') || norm.includes('custod')) sigByOffice['property'] = s;
                    if (norm.includes('finan') || norm.includes('account')) sigByOffice['financial'] = s;
                    if (norm.includes('hr') || norm.includes('human')) sigByOffice['hr'] = s;
                    if (norm.includes('dept') || norm.includes('head') || norm.includes('dean')) sigByOffice['department'] = s;
                }
            });

            let signedCount = 0;
            const totalCount = Object.keys(SIGNATORY_OFFICES).length;

            const html = Object.entries(SIGNATORY_OFFICES).map(([sKey, sDef]) => {
                const sig = sigByOffice[sKey] || sigByOffice[sDef.name];
                const hasSig = Boolean(sig && sig.signature_data);
                if (hasSig) signedCount++;
                const appDate = (sig && sig.approved_at)
                    ? new Date(sig.approved_at.replace(' ', 'T')).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : '';

                return `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border rounded-3 overflow-hidden ${hasSig ? 'border-success-subtle shadow-sm bg-body' : 'border-dashed bg-body-tertiary bg-opacity-50'}">
                        <div class="card-header py-2.5 px-3 d-flex align-items-center justify-content-between ${hasSig ? 'bg-success-subtle border-bottom border-success-subtle' : 'bg-body-secondary border-bottom'}">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas ${sDef.icon} text-${sDef.color}"></i>
                                <span class="fw-semibold text-body-emphasis small">${escapeHtml(sDef.name)}</span>
                            </div>
                            ${hasSig ? `
                                <span class="badge bg-success text-white px-2 py-0.5" style="font-size:0.68rem;">
                                    <i class="fas fa-check-circle me-1"></i>Signed
                                </span>
                            ` : `
                                <span class="badge bg-secondary-subtle text-body-secondary border px-2 py-0.5" style="font-size:0.68rem;">
                                    <i class="fas fa-hourglass-half me-1"></i>Pending
                                </span>
                            `}
                        </div>
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            ${hasSig ? `
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <small class="text-body-secondary d-block" style="font-size:0.7rem; letter-spacing:0.03em;">APPROVED BY</small>
                                            <strong class="text-body-emphasis small d-block">${escapeHtml(sig.approver_name || 'Authorized Officer')}</strong>
                                            <small class="text-body-secondary" style="font-size:0.72rem;">${escapeHtml(sDef.office)}</small>
                                        </div>
                                        ${sig.approval_ref ? `
                                            <span class="badge bg-body-tertiary text-body-secondary border font-monospace px-1.5 py-0.5" style="font-size:0.68rem;" title="Official Approval Reference">
                                                ${escapeHtml(sig.approval_ref)}
                                            </span>
                                        ` : ''}
                                    </div>

                                    <div class="p-2 bg-white rounded-2 border text-center my-2 shadow-xs" style="min-height:85px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                                        <img src="${escapeHtml(sig.signature_data)}" alt="Signature" style="max-height:64px; max-width:100%; object-fit:contain;">
                                        <div class="w-100 border-top mt-1 pt-1 d-flex justify-content-between align-items-center text-muted" style="font-size:0.62rem;">
                                            <span>DIGITAL SIGNATURE</span>
                                            <span>${escapeHtml(appDate)}</span>
                                        </div>
                                    </div>

                                    ${sig.remarks ? `
                                        <div class="small text-body-secondary bg-body-tertiary p-1.5 rounded border border-light-subtle fst-italic mt-1" style="font-size:0.72rem;">
                                            <i class="fas fa-quote-left text-muted me-1" style="font-size:0.65rem;"></i>${escapeHtml(sig.remarks)}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : `
                                <div class="text-center py-4 my-auto">
                                    <div class="rounded-circle bg-body-secondary text-body-tertiary d-flex align-items-center justify-content-center mx-auto mb-2" style="width:44px;height:44px;">
                                        <i class="fas fa-file-signature fs-5 opacity-50"></i>
                                    </div>
                                    <h6 class="text-body-secondary fw-semibold small mb-1">Awaiting Signature</h6>
                                    <small class="text-body-tertiary d-block" style="font-size:0.72rem;">${escapeHtml(sDef.office)}</small>
                                </div>
                            `}
                        </div>
                    </div>
                </div>`;
            }).join('');

            if (grid) grid.innerHTML = html;
            if (counter) counter.innerHTML = `<i class="fas fa-signature me-1"></i>${signedCount} of ${totalCount} Signed`;
        } catch (e) {
            console.warn('Could not refresh office signatures:', e);
        }
    }

    // â”€â”€ Dropdown Section View Switcher â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ Live Refresh & Reset Controls â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
            if (typeof CLEARANCE_RECORD_ID !== 'undefined' && CLEARANCE_RECORD_ID) {
                formData.append('clearance_id', String(CLEARANCE_RECORD_ID));
            }
            const res = await fetch(clearanceApi, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to reset clearance.');
            clearSignatureCanvas();
            showFacultyAlert(data.message || 'Clearance status tracker, official signatures, and files reset successfully!', 'success');
            setTimeout(() => location.reload(), 700);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Yes, Reset All'; }
        }
    }

    function confirmResetOfficeSignatures() {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmResetOfficeSignaturesModal'));
        modal.show();
    }

    async function executeResetOfficeSignatures() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmResetOfficeSignaturesModal'));
        modal?.hide();
        const btn = document.getElementById('confirmResetOfficeSignaturesBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resetting…'; }

        try {
            const formData = new FormData();
            formData.append('action', 'reset-office-signatures');
            if (typeof CLEARANCE_RECORD_ID !== 'undefined' && CLEARANCE_RECORD_ID) {
                formData.append('clearance_id', String(CLEARANCE_RECORD_ID));
            }
            const res = await fetch(clearanceApi, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to reset official clearance signatures.');
            showFacultyAlert(data.message || 'Official clearance signatures have been reset successfully!', 'success');
            await refreshOfficeSignatures();
            setTimeout(() => location.reload(), 700);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Yes, Reset'; }
        }
    }

    function confirmResetFacultySignature() {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmResetFacultySignatureModal'));
        modal.show();
    }

    async function executeResetFacultySignature() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmResetFacultySignatureModal'));
        modal?.hide();
        const btn = document.getElementById('confirmResetFacultySignatureBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resetting…'; }

        try {
            const formData = new FormData();
            formData.append('action', 'reset-faculty-signature');
            if (typeof CLEARANCE_RECORD_ID !== 'undefined' && CLEARANCE_RECORD_ID) {
                formData.append('clearance_id', String(CLEARANCE_RECORD_ID));
            }
            const res = await fetch(clearanceApi, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to reset faculty signature.');
            showFacultyAlert(data.message || 'Faculty declaration signature reset successfully!', 'success');
            setTimeout(() => location.reload(), 700);
        } catch (err) {
            showFacultyAlert(err.message, 'danger');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Yes, Reset'; }
        }
    }

    // â”€â”€ Alerts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ Helper Utilities â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function escapeHtml(value) {
        return String(value || '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        }[character]));
    }

    // â”€â”€ Digital Signature Canvas Handler â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark'
                    || document.documentElement.classList.contains('dark-mode')
                    || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && !document.documentElement.hasAttribute('data-bs-theme'));
                ctx.strokeStyle = isDark ? '#ffffff' : '#000000';
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
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark'
                || document.documentElement.classList.contains('dark-mode')
                || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && !document.documentElement.hasAttribute('data-bs-theme'));
            ctx.strokeStyle = isDark ? '#ffffff' : '#000000';
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
            if (!data.ok) throw new Error(data.error || 'Failed to submit clearance form.');

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
        const btn = document.getElementById('btnResubmitClearance');
        const isDeclared = document.getElementById('cfDeclareCheck') ? document.getElementById('cfDeclareCheck').checked : true;

        if (btn) {
            btn.disabled = count === 0 || !isDeclared;
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
        const entries = Object.entries(pendingFiles);
        if (entries.length === 0) {
            showFacultyAlert('Please attach at least one PDF file before submitting.', 'warning');
            return;
        }

        const listEl = document.getElementById('modalAttachedList');
        const badgeEl = document.getElementById('modalAttachedBadge');
        const allOfficeCards = document.querySelectorAll('.office-card');

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



    // â”€â”€ Workflow Stepper Dynamic Updater (4 steps â€” HR Final Approval removed) â”€â”€â”€â”€
    function updateLifecycleStepper(status, approvedItems = 0, totalItems = 6) {
        let step = 1;
        if (status === 'Cleared') {
            step = 4;
        } else if (status === 'Under Verification' || approvedItems > 0 || status === 'With Deficiency' || status === 'For Final Approval') {
            step = 3;
        } else if (status === 'For Department Head Approval') {
            step = 2;
        }

        const stepper = document.querySelector('.clr-flow-stepper');
        if (!stepper) return;
        const steps = stepper.querySelectorAll('.clr-flow-step');
        const dividers = stepper.querySelectorAll('.clr-flow-divider');

        if (steps.length >= 4) {
            // Step 1: Faculty Submit
            steps[0].className = `clr-flow-step ${step > 1 ? 'completed' : (step === 1 ? 'active' : '')}`;
            const c1 = steps[0].querySelector('.clr-flow-circle');
            if (c1) c1.innerHTML = step > 1 ? '<i class="fas fa-check"></i>' : '1';

            // Divider 1
            if (dividers[0]) dividers[0].className = `clr-flow-divider ${step > 1 ? 'completed' : ''}`;

            // Step 2: Dept Head Review
            steps[1].className = `clr-flow-step ${step > 2 ? 'completed' : (step === 2 ? 'active' : '')}`;
            const c2 = steps[1].querySelector('.clr-flow-circle');
            if (c2) c2.innerHTML = step > 2 ? '<i class="fas fa-check"></i>' : '2';

            // Divider 2
            if (dividers[1]) dividers[1].className = `clr-flow-divider ${step > 2 ? 'completed' : ''}`;

            // Step 3: Offices / Units Verification
            if (status === 'With Deficiency') {
                steps[2].className = 'clr-flow-step deficiency';
                const c3 = steps[2].querySelector('.clr-flow-circle');
                if (c3) c3.innerHTML = '<i class="fas fa-exclamation"></i>';
            } else {
                steps[2].className = `clr-flow-step ${step > 3 ? 'completed' : (step === 3 ? 'active' : '')}`;
                const c3 = steps[2].querySelector('.clr-flow-circle');
                if (c3) c3.innerHTML = step > 3 ? '<i class="fas fa-check"></i>' : '3';
            }

            // Divider 3
            if (dividers[2]) dividers[2].className = `clr-flow-divider ${step >= 4 ? 'completed' : ''}`;

            // Step 4: Cleared / Completed
            steps[3].className = `clr-flow-step ${step === 4 ? 'completed' : ''}`;
            const c4 = steps[3].querySelector('.clr-flow-circle');
            if (c4) c4.innerHTML = '<i class="fas fa-check-double"></i>';
        }
    }

    // ── Live Status Poller ───────────────────────────────────────────────────
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

                        // Get stage progression state
                        const stage = (data.clearance.stages && data.clearance.stages[item.name]) || null;
                        const stageState = stage ? stage.state : 'locked';

                        card.classList.remove('office-card-locked', 'office-card-ready', 'office-card-progress', 'office-card-cleared', 'office-card-deficiency');

                        let chipClass = 'pending';
                        let chipIcon = 'fa-circle-dot';
                        let labelText = 'Pending Verification';

                        const zone = card.querySelector('.office-upload-zone');
                        const zoneInner = card.querySelector('.upload-zone-inner');

                        if (stageState === 'cleared') {
                            card.classList.add('office-card-cleared');
                            chipClass = 'cleared';
                            chipIcon = 'fa-check-circle';
                            labelText = 'Cleared';
                            if (zone) {
                                zone.classList.add('upload-blocked');
                                zone.dataset.blocked = '1';
                            }
                        } else if (stageState === 'with_issue') {
                            card.classList.add('office-card-deficiency');
                            chipClass = 'deficiency';
                            chipIcon = 'fa-exclamation-triangle';
                            labelText = 'With Issue';
                            if (zone) {
                                zone.classList.remove('upload-blocked');
                                zone.dataset.blocked = '0';
                            }
                        } else if (stageState === 'in_progress') {
                            card.classList.add('office-card-progress');
                            chipClass = 'review';
                            chipIcon = 'fa-hourglass-half';
                            labelText = 'Under Verification';
                            if (zone) {
                                zone.classList.add('upload-blocked');
                                zone.dataset.blocked = '1';
                            }
                        } else if (stageState === 'ready') {
                            card.classList.add('office-card-ready');
                            chipClass = 'ready';
                            chipIcon = 'fa-circle-dot';
                            labelText = 'Ready for Verification';
                            if (zone) {
                                zone.classList.remove('upload-blocked');
                                zone.dataset.blocked = '0';
                            }
                            if (zoneInner && (!zoneInner.querySelector('.btn-upload-choose') || zoneInner.querySelector('.fa-lock'))) {
                                zoneInner.innerHTML = `
                                    <i class="fas fa-cloud-arrow-up text-primary mb-1" style="font-size:1.5rem;"></i>
                                    <div class="upload-hint-text">Upload supporting documents (PDF, max 10 MB)</div>
                                    <label class="btn-upload-choose" for="fileInput${item.office_id}" id="chooseLabel${item.office_id}">
                                        <i class="fas fa-folder-open me-1"></i> Choose PDF
                                    </label>
                                    <input type="file" id="fileInput${item.office_id}" class="office-file-input d-none"
                                        accept=".pdf,application/pdf" data-office-id="${item.office_id}"
                                        onchange="handleFileSelected(this)">
                                `;
                            }
                        } else { // locked
                            card.classList.add('office-card-locked');
                            chipClass = 'locked';
                            chipIcon = 'fa-lock';
                            labelText = 'Locked';
                            if (zone) {
                                zone.classList.add('upload-blocked');
                                zone.dataset.blocked = '1';
                            }
                            if (zoneInner) {
                                const lockMsg = (stage && stage.lock_reason) ? stage.lock_reason : 'Complete the previous clearance to unlock this step.';
                                zoneInner.innerHTML = `
                                    <i class="fas fa-lock text-secondary mb-2" style="font-size:1.75rem;"></i>
                                    <div class="fw-semibold text-secondary small mb-1">Clearance Step Locked</div>
                                    <div class="upload-hint-text text-body-secondary small">${escapeHtml(lockMsg)}</div>
                                `;
                            }
                        }

                        if (chip) {
                            chip.className = `clr-chip clr-chip-${chipClass} office-chip`;
                            const ico = chip.querySelector('i');
                            if (ico) ico.className = `fas ${chipIcon}`;
                        }
                        if (chipLabel) chipLabel.textContent = labelText;

                        if (remarksBox) {
                            if (chipClass === 'deficiency' && item.remarks) {
                                const cleanRmk = item.remarks.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '').replace(/<!--SCOPE_STATE:.*?-->/g, '').trim();
                                    remarksBox.innerHTML = `
                                    <div class="office-deficiency-box mb-2">
                                        <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Issue Reported:</div>
                                        <div>${escapeHtml(cleanRmk).replace(/\n/g, '<br>')}</div>
                                    </div>`;
                            } else if (chipClass === 'onhold' && item.remarks) {
                                const cleanRmk = item.remarks.replace(/^\[(Denied|On Hold|Hold|Approved|With Deficiency)\]\s*/i, '').replace(/<!--SCOPE_STATE:.*?-->/g, '').trim();
                                remarksBox.innerHTML = `
                                    <div class="office-onhold-box mb-2">
                                        <div class="fw-bold mb-1"><i class="fas fa-pause-circle me-1"></i>Office Note:</div>
                                        <div class="office-remarks-text">${escapeHtml(cleanRmk).replace(/\n/g, '<br>')}</div>
                                    </div>`;
                            } else if (chipClass === 'cleared' && item.cleared_at) {
                                const d = new Date(item.cleared_at.replace(' ', 'T'));
                                const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                remarksBox.innerHTML = `
                                    <div class="small text-success d-flex align-items-center gap-1 mb-2">
                                        <i class="fas fa-badge-check"></i>
                                        <span>Cleared on ${dateStr}</span>
                                    </div>`;
                            } else if (chipClass === 'locked') {
                                remarksBox.innerHTML = `
                                    <div class="small text-body-secondary d-flex align-items-center gap-1 mb-2">
                                        <i class="fas fa-lock text-secondary opacity-75"></i>
                                        <span>Sequential Step Locked</span>
                                    </div>`;
                            } else {
                                remarksBox.innerHTML = `
                                    <div class="small text-body-secondary d-flex align-items-center gap-1 mb-2">
                                        <i class="fas fa-shield-halved text-secondary opacity-50"></i>
                                        <span>Verified directly by responsible office</span>
                                    </div>`;
                            }
                        }

                        // Update checklist items in the card (green check and red x circle, NO badges)
                        const listItems = card.querySelectorAll('.office-checklist li');
                        let scopeState = null;
                        if (item.remarks) {
                            const scopeMatch = item.remarks.match(/<!--SCOPE_STATE:(.*?)-->/);
                            if (scopeMatch) {
                                try {
                                    scopeState = JSON.parse(scopeMatch[1]);
                                } catch (e) { }
                            }
                        }
                        const failedSet = new Set(((scopeState && scopeState.failed) || []).map(s => String(s).trim().toLowerCase()));
                        const passedSet = new Set(((scopeState && scopeState.passed) || []).map(s => String(s).trim().toLowerCase()));

                        listItems.forEach(li => {
                            const oldBadge = li.querySelector('.badge');
                            if (oldBadge) oldBadge.remove();

                            const textSpan = li.querySelector('span');
                            const text = textSpan ? textSpan.textContent.trim().toLowerCase() : '';
                            let icon = li.querySelector('i');

                            if (failedSet.has(text)) {
                                li.className = 'text-danger fw-semibold d-flex align-items-center justify-content-between gap-2';
                                if (icon) icon.className = 'fas fa-times-circle text-danger flex-shrink-0';
                                const badge = document.createElement('span');
                                badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle flex-shrink-0';
                                badge.style.fontSize = '0.68rem';

                                li.appendChild(badge);
                            } else if (passedSet.has(text) || chipClass === 'cleared') {
                                li.className = 'text-success d-flex align-items-center gap-2';
                                if (icon) icon.className = 'fas fa-check-circle text-success flex-shrink-0';
                            } else {
                                li.className = 'd-flex align-items-center gap-2';
                                if (icon) icon.className = 'fas fa-check-circle flex-shrink-0';
                            }
                        });

                        const hintEl = card.querySelector('.upload-hint-text');
                        const chooseLabel = card.querySelector('.btn-upload-choose');
                        if (failedSet.size > 0) {
                            if (hintEl) hintEl.innerHTML = '<span class="text-danger fw-semibold"></span>';
                            if (chooseLabel) chooseLabel.innerHTML = '<i class="fas fa-folder-open me-1"></i>Upload a new File';
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
                    if (!st || st === 'For Department Head Approval' || st === 'Not Submitted') {
                        pageBadge.classList.add('d-none');
                    } else {
                        pageBadge.classList.remove('d-none');
                        let badgeClass = 'bg-secondary text-white';
                        let iconClass = 'fa-circle-dot';
                        if (st === 'Cleared') {
                            badgeClass = 'bg-success text-white';
                            iconClass = 'fa-check-double';
                        } else if (st === 'With Deficiency') {
                            badgeClass = 'bg-danger text-white';
                            iconClass = 'fa-exclamation-triangle';
                        } else if (st === 'For Final Approval') {
                            badgeClass = 'bg-warning text-dark';
                            iconClass = 'fa-user-check';
                        } else if (st === 'Under Verification') {
                            badgeClass = 'bg-info text-dark';
                            iconClass = 'fa-clock';
                        }
                        pageBadge.innerHTML = `<i class="fas ${iconClass} me-1"></i>${escapeHtml(st)}`;
                        pageBadge.className = `badge ${badgeClass} fs-6 px-3 py-2 shadow-sm`;
                    }
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

    /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       Per-Office File Selection & Staging
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
        const card = zone.closest('.office-card');
        if (!officeId || zone.dataset.blocked === '1' || (card && card.classList.contains('office-card-locked'))) {
            showFacultyAlert('This clearance stage is locked. Complete the previous clearance first.', 'warning');
            return;
        }
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
        const card = input.closest('.office-card');
        if (!officeId || (card && card.classList.contains('office-card-locked'))) {
            showFacultyAlert('This clearance stage is locked. Complete the previous clearance first.', 'warning');
            input.value = '';
            return;
        }
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            showFacultyAlert('Only PDF files are allowed.', 'danger');
            input.value = '';
            return;
        }
        stageFile(officeId, file);
    }

    function stageFile(officeId, file) {
        const card = document.querySelector(`[data-office-row-id="${officeId}"]`);
        if (card && card.classList.contains('office-card-locked')) {
            showFacultyAlert('This clearance stage is locked. Complete the previous clearance first.', 'warning');
            return;
        }
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
    <title>Clearance Form - <?= facultyClearanceEsc($cfFullName) ?></title>
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
        .clr-conduct-list li::before { content: ""; position: absolute; left: 0.5rem; top: -0.1rem; color: #0d6efd; font-size: 1.25rem; font-weight: bold; }
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