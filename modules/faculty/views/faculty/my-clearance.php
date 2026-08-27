<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
require_once __DIR__ . '/../../controllers/clearance.php';

$db = facultyDb();
$profile = $db ? facultyClearanceProfile($db, (int) getCurrentUserId()) : null;
$offices = $db ? facultyClearanceOffices($db) : [];
$term = $db ? facultyClearanceTerm($db) : null;
$clearance = ($db && $profile && $term) ? facultyClearanceRequest($db, (int) $profile['id'], (int) $term['term_id']) : null;
$itemByOffice = [];
foreach ($clearance['items'] ?? [] as $item) {
    $itemByOffice[(int) $item['clearance_office_id']] = $item;
}
$intentItem = null;
foreach ($itemByOffice as $item) {
    if (($item['name'] ?? '') === 'Letter of Intent') {
        $intentItem = $item;
        break;
    }
}
$contractEnd = $profile['contractual_end'] ?? null;
$daysRemaining = $contractEnd && $contractEnd !== '0000-00-00'
    ? (int) floor((strtotime($contractEnd) - strtotime(date('Y-m-d'))) / 86400)
    : null;
$status = facultyClearanceStatus($clearance);
$intentStatusLabel = $intentItem ? facultyClearanceItemLabel($intentItem) : 'Not Submitted';
$intentCanUploadAgain = $intentItem && (($intentItem['status'] ?? '') === 'Cleared' || facultyClearanceCanResubmit($intentItem));
$intentStatusClass = $intentStatusLabel === 'Approved'
    ? 'bg-success-subtle text-success border border-success-subtle'
    : ($intentStatusLabel === 'Denied'
        ? 'bg-danger-subtle text-danger border border-danger-subtle'
        : ($intentStatusLabel === 'On Hold'
            ? 'bg-warning-subtle text-warning border border-warning-subtle'
            : ($intentStatusLabel === 'Pending Review' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-body-secondary')));
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
<style>
    /* ═══════════════════════════════════════════════════════════════
   LETTER OF INTENT — Enhanced Premium UI  (scoped to my-clearance)
   ═══════════════════════════════════════════════════════════════ */

    /* ── LOI Hero Card ──────────────────────────────────────────── */
    .loi-hero-card {
        background: var(--bs-body-bg);
        border: 1px solid rgba(var(--bs-primary-rgb), .18) !important;
        border-radius: 18px !important;
        overflow: hidden;
        box-shadow: 0 4px 28px rgba(var(--bs-primary-rgb), .08), 0 1px 4px rgba(0, 0, 0, .06) !important;
        transition: box-shadow .25s ease, transform .25s ease;
    }

    .loi-hero-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 36px rgba(var(--bs-primary-rgb), .14), 0 2px 6px rgba(0, 0, 0, .08) !important;
    }

    /* ── LOI Gradient Header ───────────────────────────────────── */
    .loi-card-header {
        background: linear-gradient(135deg,
                rgba(var(--bs-primary-rgb), .92) 0%,
                rgba(var(--bs-primary-rgb), .72) 60%,
                rgba(79, 140, 255, .6) 100%);
        padding: 1.1rem 1.4rem;
        position: relative;
        overflow: hidden;
    }

    .loi-card-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='28'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        pointer-events: none;
    }

    .loi-header-title {
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: .04em;
        margin: 0;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .18);
    }

    .loi-header-subtitle {
        color: rgba(255, 255, 255, .78);
        font-size: .78rem;
        margin: .18rem 0 0;
    }

    .loi-header-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, .18);
        border: 1.5px solid rgba(255, 255, 255, .35);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
        flex-shrink: 0;
        backdrop-filter: blur(4px);
    }

    /* ── LOI Status Pill ──────────────────────────────────────── */
    .loi-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .32rem .85rem;
        border-radius: 50px;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        white-space: nowrap;
        backdrop-filter: blur(4px);
        border: 1.5px solid;
    }

    .loi-status-pill.not-submitted {
        background: rgba(108, 117, 125, .12);
        color: var(--bs-secondary);
        border-color: rgba(108, 117, 125, .3);
    }

    .loi-status-pill.approved {
        background: rgba(25, 135, 84, .15);
        color: #198754;
        border-color: rgba(25, 135, 84, .35);
        animation: loi-pulse-success 2s ease-in-out infinite;
    }

    .loi-status-pill.pending {
        background: rgba(13, 202, 240, .12);
        color: #0dcaf0;
        border-color: rgba(13, 202, 240, .35);
    }

    .loi-status-pill.denied {
        background: rgba(220, 53, 69, .13);
        color: #dc3545;
        border-color: rgba(220, 53, 69, .35);
    }

    .loi-status-pill.onhold {
        background: rgba(255, 193, 7, .14);
        color: #997404;
        border-color: rgba(255, 193, 7, .4);
    }

    @keyframes loi-pulse-success {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, .25);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(25, 135, 84, 0);
        }
    }

    /* ── LOI Steps Guide ──────────────────────────────────────── */
    .loi-steps {
        display: flex;
        gap: .5rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .loi-step {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex: 1 1 auto;
        min-width: 120px;
        padding: .55rem .75rem;
        background: rgba(var(--bs-primary-rgb), .04);
        border: 1px solid rgba(var(--bs-primary-rgb), .1);
        border-radius: 10px;
        font-size: .73rem;
        color: var(--bs-body-color);
        transition: background .2s;
    }

    .loi-step:hover {
        background: rgba(var(--bs-primary-rgb), .08);
    }

    .loi-step-num {
        width: 22px;
        height: 22px;
        background: rgba(var(--bs-primary-rgb), .15);
        color: var(--bs-primary);
        border-radius: 50%;
        font-weight: 800;
        font-size: .7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .loi-step-text {
        font-weight: 500;
    }

    /* ── LOI Form Layout ──────────────────────────────────────── */
    .loi-form-body {
        padding: 1.4rem 1.5rem;
    }

    .loi-select-wrap {
        position: relative;
    }

    .loi-select-wrap .loi-select-icon {
        position: absolute;
        left: .9rem;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(var(--bs-primary-rgb), .7);
        pointer-events: none;
        z-index: 1;
        font-size: .9rem;
    }

    .loi-select-wrap select {
        padding-left: 2.4rem !important;
        border-radius: 10px !important;
        border-color: rgba(var(--bs-primary-rgb), .2) !important;
        background: var(--bs-body-bg) !important;
        transition: border-color .2s, box-shadow .2s;
        font-size: .875rem;
    }

    .loi-select-wrap select:focus {
        border-color: rgba(var(--bs-primary-rgb), .55) !important;
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .1) !important;
    }

    /* ── LOI Drag-Drop Upload Zone ─────────────────────────────── */
    .loi-upload-zone {
        border: 2.5px dashed rgba(var(--bs-primary-rgb), .3);
        border-radius: 14px;
        padding: 1.4rem 1rem;
        text-align: center;
        cursor: pointer;
        background: rgba(var(--bs-primary-rgb), .025);
        transition: all .25s ease;
        position: relative;
        overflow: hidden;
    }

    .loi-upload-zone::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at center, rgba(var(--bs-primary-rgb), .06) 0%, transparent 70%);
        opacity: 0;
        transition: opacity .3s;
    }

    .loi-upload-zone:hover,
    .loi-upload-zone.is-over {
        border-color: rgba(var(--bs-primary-rgb), .7);
        background: rgba(var(--bs-primary-rgb), .06);
        transform: scale(1.01);
    }

    .loi-upload-zone:hover::before,
    .loi-upload-zone.is-over::before {
        opacity: 1;
    }

    .loi-upload-zone input[type=file] {
        display: none;
    }

    .loi-upload-icon {
        font-size: 2rem;
        color: rgba(var(--bs-primary-rgb), .55);
        transition: transform .25s ease, color .25s;
        display: block;
        margin-bottom: .45rem;
    }

    .loi-upload-zone:hover .loi-upload-icon,
    .loi-upload-zone.is-over .loi-upload-icon {
        color: var(--bs-primary);
        transform: translateY(-4px) scale(1.08);
    }

    .loi-upload-label {
        font-size: .85rem;
        font-weight: 600;
        color: var(--bs-body-color);
        margin: 0;
    }

    .loi-upload-hint {
        font-size: .73rem;
        color: var(--bs-secondary-color);
        margin-top: .2rem;
    }

    /* File selected state */
    .loi-upload-zone.has-file {
        border-style: solid;
        border-color: rgba(var(--bs-primary-rgb), .55);
        background: rgba(var(--bs-primary-rgb), .05);
    }

    .loi-upload-zone.has-file .loi-upload-icon {
        color: var(--bs-primary);
    }

    /* Locked state */
    .loi-upload-zone.is-locked {
        border-color: rgba(108, 117, 125, .25);
        background: rgba(0, 0, 0, .025);
        cursor: not-allowed;
        opacity: .7;
    }

    .loi-upload-zone.is-locked:hover {
        transform: none;
    }

    /* ── LOI Textarea ─────────────────────────────────────────── */
    .loi-textarea {
        border-radius: 10px !important;
        border-color: rgba(var(--bs-primary-rgb), .18) !important;
        font-size: .875rem;
        resize: none;
        background: var(--bs-body-bg) !important;
        transition: border-color .2s, box-shadow .2s;
    }

    .loi-textarea:focus {
        border-color: rgba(var(--bs-primary-rgb), .5) !important;
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .08) !important;
    }

    /* ── LOI Submit Button ────────────────────────────────────── */
    .loi-submit-btn {
        background: linear-gradient(135deg, var(--bs-primary) 0%, rgba(var(--bs-primary-rgb), .8) 100%);
        border: none;
        border-radius: 10px;
        padding: .65rem 2rem;
        font-size: .9rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: #fff;
        box-shadow: 0 3px 14px rgba(var(--bs-primary-rgb), .35);
        transition: all .22s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }

    .loi-submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 22px rgba(var(--bs-primary-rgb), .45);
        filter: brightness(1.06);
        color: #fff;
    }

    .loi-submit-btn:active:not(:disabled) {
        transform: translateY(0);
    }

    .loi-submit-btn:disabled {
        opacity: .6;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    /* ── LOI Existing File Banner ─────────────────────────────── */
    .loi-file-banner {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .65rem 1rem;
        background: rgba(var(--bs-primary-rgb), .06);
        border: 1px solid rgba(var(--bs-primary-rgb), .18);
        border-radius: 10px;
        font-size: .82rem;
        margin-bottom: .75rem;
    }

    .loi-file-banner .loi-file-icon {
        width: 34px;
        height: 34px;
        background: rgba(var(--bs-primary-rgb), .1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-primary);
        font-size: 1rem;
        flex-shrink: 0;
    }

    .loi-file-name {
        font-weight: 600;
        color: var(--bs-body-color);
    }

    .loi-file-sub {
        color: var(--bs-secondary-color);
        font-size: .73rem;
    }

    /* ── LOI Info Divider ─────────────────────────────────────── */
    .loi-section-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--bs-secondary-color);
        margin-bottom: .45rem;
        margin-top: 1rem;
    }

    .loi-form-divider {
        border: none;
        border-top: 1px dashed rgba(var(--bs-primary-rgb), .12);
        margin: 1rem 0;
    }

    /* ── Success Modal ────────────────────────────────────────── */
    .loi-success-modal .modal-content {
        border-radius: 20px !important;
        overflow: hidden;
        border: none !important;
    }

    .loi-success-modal-header {
        background: linear-gradient(135deg,
                rgba(25, 135, 84, 1) 0%,
                rgba(32, 178, 106, .85) 100%);
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .loi-success-modal-header::after {
        content: '';
        position: absolute;
        right: -30px;
        top: -30px;
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
    }

    .loi-success-anim {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, rgba(25, 135, 84, .15) 0%, rgba(25, 135, 84, .05) 100%);
        border: 2px solid rgba(25, 135, 84, .3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: loi-success-pop .5s cubic-bezier(.34, 1.56, .64, 1) both;
    }

    @keyframes loi-success-pop {
        from {
            transform: scale(0);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .loi-success-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(13, 202, 240, .1);
        color: #0dcaf0;
        border: 1px solid rgba(13, 202, 240, .3);
        border-radius: 50px;
        padding: .3rem .9rem;
        font-size: .78rem;
        font-weight: 600;
        margin-top: .5rem;
    }

    /* ── LOI Status Flow Panel ─────────────────────────────── */
    .loi-status-panel {
        padding: 1.5rem;
        animation: loi-panel-in .4s cubic-bezier(.22, .68, 0, 1.2) both;
    }

    @keyframes loi-panel-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Submitted summary card */
    .loi-submitted-summary {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-radius: 14px;
        background: rgba(var(--bs-primary-rgb), .05);
        border: 1px solid rgba(var(--bs-primary-rgb), .15);
        margin-bottom: 1.5rem;
    }

    .loi-submitted-icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        background: rgba(var(--bs-primary-rgb), .12);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: var(--bs-primary);
    }

    .loi-submitted-title {
        font-weight: 700;
        font-size: .95rem;
        margin-bottom: .1rem;
    }

    .loi-submitted-meta {
        font-size: .78rem;
        color: var(--bs-secondary-color);
    }

    .loi-submitted-type {
        margin-left: auto;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .8rem;
        border-radius: 50px;
        font-size: .73rem;
        font-weight: 700;
        background: rgba(var(--bs-primary-rgb), .1);
        color: var(--bs-primary);
        border: 1px solid rgba(var(--bs-primary-rgb), .2);
        text-transform: capitalize;
    }

    /* Vertical timeline */
    .loi-timeline {
        position: relative;
        padding: 0 0 .5rem 0;
        margin-bottom: 1.4rem;
    }

    .loi-timeline::before {
        content: '';
        position: absolute;
        left: 19px;
        top: 6px;
        bottom: 6px;
        width: 2px;
        background: linear-gradient(to bottom,
                rgba(var(--bs-primary-rgb), .25) 0%,
                rgba(var(--bs-primary-rgb), .08) 100%);
        border-radius: 2px;
    }

    .loi-tl-item {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .55rem 0;
        position: relative;
    }

    .loi-tl-dot {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        border: 2.5px solid;
        background: var(--bs-body-bg);
        transition: transform .2s;
        position: relative;
        z-index: 1;
    }

    .loi-tl-item.is-done .loi-tl-dot {
        color: #198754;
        border-color: #198754;
        background: rgba(25, 135, 84, .08);
    }

    .loi-tl-item.is-active .loi-tl-dot {
        color: var(--bs-primary);
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), .08);
        animation: loi-tl-pulse 1.8s ease-in-out infinite;
    }

    .loi-tl-item.is-denied .loi-tl-dot {
        color: #dc3545;
        border-color: #dc3545;
        background: rgba(220, 53, 69, .08);
    }

    .loi-tl-item.is-onhold .loi-tl-dot {
        color: #997404;
        border-color: #ffc107;
        background: rgba(255, 193, 7, .08);
    }

    .loi-tl-item.is-wait .loi-tl-dot {
        color: var(--bs-secondary-color);
        border-color: var(--bs-border-color);
    }

    @keyframes loi-tl-pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), .3);
        }

        50% {
            box-shadow: 0 0 0 7px rgba(var(--bs-primary-rgb), 0);
        }
    }

    .loi-tl-content {
        padding-top: .4rem;
    }

    .loi-tl-label {
        font-weight: 700;
        font-size: .88rem;
        margin-bottom: .12rem;
    }

    .loi-tl-sub {
        font-size: .75rem;
        color: var(--bs-secondary-color);
    }

    .loi-tl-time {
        font-size: .72rem;
        color: var(--bs-secondary-color);
        margin-top: .18rem;
    }

    /* Remarks block */
    .loi-remarks-block {
        border-radius: 10px;
        padding: .85rem 1rem;
        font-size: .82rem;
        border-left: 3.5px solid;
        margin-bottom: 1.2rem;
    }

    .loi-remarks-block.is-denied {
        background: rgba(220, 53, 69, .06);
        border-color: #dc3545;
    }

    .loi-remarks-block.is-onhold {
        background: rgba(255, 193, 7, .08);
        border-color: #ffc107;
    }

    .loi-remarks-block.is-info {
        background: rgba(13, 202, 240, .06);
        border-color: #0dcaf0;
    }

    .loi-remarks-title {
        font-weight: 700;
        margin-bottom: .2rem;
    }

    /* Upload Again button */
    .loi-upload-again-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .55rem 1.4rem;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 700;
        border: 2px solid rgba(var(--bs-primary-rgb), .4);
        background: transparent;
        color: var(--bs-primary);
        cursor: pointer;
        transition: all .22s ease;
    }

    .loi-upload-again-btn:hover:not(:disabled) {
        background: rgba(var(--bs-primary-rgb), .06);
        border-color: var(--bs-primary);
        transform: translateY(-1px);
    }

    .loi-upload-again-btn:disabled,
    .loi-upload-again-btn.is-disabled {
        opacity: 0.55;
        cursor: not-allowed !important;
        pointer-events: auto;
        border-color: rgba(108, 117, 125, 0.35) !important;
        color: var(--bs-secondary-color) !important;
        background: rgba(0, 0, 0, 0.04) !important;
        transform: none !important;
        box-shadow: none !important;
    }

    .loi-upload-again-btn.btn-denied {
        border-color: #dc3545;
        color: #dc3545;
        background: rgba(220, 53, 69, 0.05);
    }

    .loi-upload-again-btn.btn-denied:hover:not(:disabled) {
        background: rgba(220, 53, 69, 0.12);
        border-color: #dc3545;
        color: #dc3545;
    }

    .loi-upload-again-btn.btn-onhold {
        border-color: #ffc107;
        color: #997404;
        background: rgba(255, 193, 7, 0.08);
    }

    .loi-upload-again-btn.btn-onhold:hover:not(:disabled) {
        background: rgba(255, 193, 7, 0.18);
        border-color: #ffc107;
        color: #997404;
    }
</style>
<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="container-fluid p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-file-upload text-primary me-2"></i>Faculty Clearance Portal</h3>
            <p class="text-body-secondary small mb-0">AS-IS Process: Receive contract notifications and complete
                clearance requirements.</p>
        </div>
        <span
            class="badge <?= $status === 'Action Required' ? 'bg-danger' : ($status === 'Completed' ? 'bg-success' : 'bg-warning text-dark') ?> fs-6 px-3 py-2 shadow-sm"
            id="facultyStatusBadge">
            <i class="fas fa-clock me-1"></i><?= facultyClearanceEsc($status) ?>
        </span>
    </div>

    <div id="facultyAlert" class="alert alert-success alert-dismissible fade show d-none mb-4 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i><span id="facultyAlertMessage"></span>
        <button type="button" class="btn-close" onclick="dismissFacultyAlert()" aria-label="Close"></button>
    </div>

    <?php if (!$profile): ?>
        <div class="alert alert-warning">No faculty profile is linked to this account. Please contact the Faculty
            Administrator.</div>
    <?php else: ?>
        <div class="card bg-body-tertiary border shadow-sm mb-4">
            <div class="card-header bg-body-tertiary border-bottom py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-uppercase small text-primary"><i
                        class="fas fa-envelope-open-text me-2"></i>RECEIVE NOTICE / NOTIFICATION</span>
                <span
                    class="badge <?= ($profile['employment_status'] ?? 'Probationary') === 'Regular' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?> px-2 py-1">
                    <i
                        class="fas <?= ($profile['employment_status'] ?? 'Probationary') === 'Regular' ? 'fa-user-check' : 'fa-user-clock' ?> me-1"></i>Status:
                    <?= facultyClearanceEsc((string) ($profile['employment_status'] ?? 'Probationary')) ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold text-body-secondary text-uppercase mb-1 small">Contract Expiration Clearance
                            Progress</h6>
                        <h4 class="fw-bold mb-2" id="progressTitle"><?= (int) ($clearance['approved_items'] ?? 0) ?> of
                            <?= (int) ($clearance['total_items'] ?? count($offices)) ?> Requirements Completed
                        </h4>
                        <div class="progress bg-secondary-subtle" style="height: 10px">
                            <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                                id="facultyProgressBar" style="width: <?= (int) ($clearance['progress'] ?? 0) ?>%"></div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0 border-start-md">
                        <small class="text-body-secondary d-block">Contract Expiry Date</small>
                        <span
                            class="fw-bold <?= $daysRemaining !== null && $daysRemaining <= 30 ? 'text-danger' : 'text-primary' ?> fs-5"><i
                                class="fas fa-calendar-alt me-1"></i><?= $contractEnd && $contractEnd !== '0000-00-00' ? date('M d, Y', strtotime($contractEnd)) : 'Not set' ?></span>
                        <small
                            class="text-body-secondary d-block mt-1"><?= $daysRemaining === null ? 'No expiration date recorded' : ($daysRemaining < 0 ? 'Contract Expired' : 'Contract expires in ' . $daysRemaining . ' days') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ LETTER OF INTENT SUBMISSION — Enhanced UI ═══ -->
        <div class="card loi-hero-card mb-4">

            <!-- ── Gradient Header ─────────────────────────── -->
            <div class="loi-card-header d-flex justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="loi-header-icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <div class="loi-header-title">Letter of Intent Submission</div>
                        <div class="loi-header-subtitle">Faculty Contract Clearance Requirement</div>
                    </div>
                </div>
            </div>

            <?php
            // If a submission exists (Pending Review, Approved, Denied, On Hold), show Status Flow by default
            $loiHasSubmission = !empty($intentItem['file_path']) || in_array($intentItem['status'] ?? '', ['Pending Review', 'Cleared', 'Denied', 'Hold', 'On Hold'], true);
            $loiShowStatus = (bool) $loiHasSubmission;
            ?>

            <!-- ── PANEL A: Upload Form ──────────────────────── -->
            <div class="loi-form-body" id="loiFormPanel" style="<?= $loiShowStatus ? 'display:none;' : '' ?>">

                <!-- 3-step guide -->
                <div class="loi-steps">
                    <div class="loi-step">
                        <div class="loi-step-num">1</div>
                        <div class="loi-step-text">Choose your statement of intent</div>
                    </div>
                    <div class="loi-step">
                        <div class="loi-step-num">2</div>
                        <div class="loi-step-text">Upload your signed PDF letter</div>
                    </div>
                    <div class="loi-step">
                        <div class="loi-step-num">3</div>
                        <div class="loi-step-text">Submit &amp; await dept. head review</div>
                    </div>
                </div>

                <form id="intentLetterForm">

                    <div class="row g-3">

                        <!-- ── Statement of Intent ───────────── -->
                        <div class="col-md-5">
                            <div class="loi-section-label">Statement of Intent <span class="text-danger">*</span></div>
                            <div class="loi-select-wrap">
                                <i class="fas fa-list-check loi-select-icon"></i>
                                <select class="form-select" id="intentChoice" required>
                                    <option value="" selected disabled>— Select Your Intent —</option>
                                    <option value="renewal">📄 Contract Renewal / Extension</option>
                                    <option value="regularization">⭐ Regularization</option>
                                </select>
                            </div>
                        </div>

                        <!-- ── Upload Zone ───────────────────── -->
                        <div class="col-md-7">
                            <div class="loi-section-label">Upload Signed Letter <span class="text-danger">*</span></div>

                            <?php if ($intentItem && !empty($intentItem['file_path'])): ?>
                                <div class="loi-file-banner">
                                    <div class="loi-file-icon"><i class="fas fa-file-pdf"></i></div>
                                    <div class="overflow-hidden">
                                        <div class="loi-file-name text-truncate"
                                            title="<?= facultyClearanceEsc(basename((string) $intentItem['file_path'])) ?>">
                                            <?= facultyClearanceEsc(basename((string) $intentItem['file_path'])) ?>
                                        </div>
                                        <div class="loi-file-sub">Upload a new version to replace this file.</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <label for="intentFile" class="loi-upload-zone d-block w-100" id="loiUploadZone">
                                <input type="file" id="intentFile" accept=".pdf" required>
                                <i class="fas fa-cloud-arrow-up loi-upload-icon"></i>
                                <div class="loi-upload-label">Click to browse or drag &amp; drop</div>
                                <div class="loi-upload-hint" id="intentFileName">PDF files only &middot; Max 10 MB</div>
                            </label>
                        </div>

                        <!-- ── Additional Message ────────────── -->
                        <div class="col-12">
                            <hr class="loi-form-divider">
                            <div class="loi-section-label">Message to Department Head <small
                                    class="text-body-secondary fw-normal">(Optional)</small></div>
                            <textarea class="form-control loi-textarea" id="intentRemarks" rows="2"
                                placeholder="Add any notes or context for the Department Head…"></textarea>
                        </div>
                    </div>

                    <!-- ── Submit Footer ──────────────────── -->
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-1 flex-wrap gap-2">
                        <div>
                            <?php if ($loiHasSubmission): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                    onclick="showLoiStatusPanel()">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Status Tracker
                                </button>
                            <?php else: ?>
                                <small class="text-body-secondary">
                                    <i class="fas fa-shield-halved me-1 text-primary"></i>
                                    Sent securely to your Department Head
                                </small>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="loi-submit-btn" id="btnSubmitIntent">
                            <i class="fas fa-paper-plane"></i>
                            Submit Intent Letter
                        </button>
                    </div>

                </form>
            </div>

            <!-- ── PANEL B: Status Flow ─────────────────────── -->
            <?php
            $loiIntentType = $intentItem['intent_type'] ?? ($intentItem['notes'] ?? '');
            $loiFileName = $intentItem ? basename((string) ($intentItem['file_path'] ?? '')) : '';
            $loiSubmittedAt = $intentItem ? (string) ($intentItem['cleared_at'] ?? ($intentItem['updated_at'] ?? '')) : '';
            $loiStatusForPanel = $intentItem ? (string) ($intentItem['status'] ?? 'Missing') : 'Missing';
            $loiStatusNorm = strtolower(trim($loiStatusForPanel));
            $loiRemarks = $intentItem ? (string) ($intentItem['remarks'] ?? '') : '';

            $isApproved = in_array($loiStatusNorm, ['cleared', 'approved'], true);
            $isDenied = in_array($loiStatusNorm, ['denied', 'hold', 'returned', 'rejected'], true);
            $isOnHold = in_array($loiStatusNorm, ['on hold', 'on_hold', 'resubmit'], true);
            $isUnderReview = in_array($loiStatusNorm, ['pending review', 'pending', 'submitted'], true) || (!$isApproved && !$isDenied && !$isOnHold && $loiFileName !== '');

            // Map internal status → timeline stage
            //  Stage 1: Submitted (always done if file exists)
            //  Stage 2: Under Review (active when under review, done when decision is made)
            //  Stage 3: Decision (Approved / Denied / On Hold)
            $loiStage = ($isApproved || $isDenied || $isOnHold) ? 3 : ($isUnderReview ? 2 : ($loiFileName !== '' ? 1 : 0));
            $loiDecision = ($isApproved || $isDenied || $isOnHold);
            ?>
            <div class="loi-status-panel" id="loiStatusPanel" style="<?= $loiShowStatus ? '' : 'display:none;' ?>">

                <!-- Submitted summary -->
                <div class="loi-submitted-summary" id="loiSummaryCard">
                    <div class="loi-submitted-icon"><i class="fas fa-file-pdf"></i></div>
                    <div class="overflow-hidden flex-grow-1">
                        <div class="loi-submitted-title text-truncate" id="loiSummaryFile">
                            <?= $loiFileName !== '' ? facultyClearanceEsc($loiFileName) : 'Submitted file' ?>
                        </div>
                        <div class="loi-submitted-meta" id="loiSummaryDate">
                            <?php if ($loiSubmittedAt): ?>
                                Submitted <?= htmlspecialchars(date('M j, Y \a\t h:i A', strtotime($loiSubmittedAt))) ?>
                            <?php else: ?>
                                Submitted
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="loi-submitted-type" id="loiSummaryType">
                        <i class="fas fa-tag"></i>
                        <span
                            id="loiSummaryTypeText"><?= $loiIntentType !== '' ? facultyClearanceEsc(ucwords(str_replace('_', ' ', $loiIntentType))) : 'Intent' ?></span>
                    </div>
                </div>

                <!-- Vertical Timeline -->
                <div class="loi-timeline">

                    <!-- Stage 1 — Submitted -->
                    <div class="loi-tl-item <?= $loiStage >= 1 ? 'is-done' : 'is-wait' ?>" id="loiTlStage1">
                        <div class="loi-tl-dot">
                            <i class="fas <?= $loiStage >= 1 ? 'fa-check' : 'fa-circle-dot' ?>"></i>
                        </div>
                        <div class="loi-tl-content">
                            <div class="loi-tl-label">Letter Submitted</div>
                            <div class="loi-tl-sub">Your signed Letter of Intent was sent to the Department Head.</div>
                            <?php if ($loiSubmittedAt && $loiStage >= 1): ?>
                                <div class="loi-tl-time" id="loiTlDate1">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= htmlspecialchars(date('M j, Y h:i A', strtotime($loiSubmittedAt))) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stage 2 — Under Review -->
                    <?php
                    $stage2Class = ($loiStage === 2) ? 'is-active' : (($loiStage >= 3) ? 'is-done' : 'is-wait');
                    $stage2Icon = ($loiStage === 2) ? 'fa-hourglass-half' : (($loiStage >= 3) ? 'fa-check' : 'fa-circle');
                    ?>
                    <div class="loi-tl-item <?= $stage2Class ?>" id="loiTlStage2">
                        <div class="loi-tl-dot">
                            <i class="fas <?= $stage2Icon ?>"></i>
                        </div>
                        <div class="loi-tl-content">
                            <div class="loi-tl-label">Under Department Head Review</div>
                            <div class="loi-tl-sub">The Department Head is currently reviewing your letter.</div>
                        </div>
                    </div>

                    <!-- Stage 3 — Decision -->
                    <?php
                    $stage3Class = 'is-wait';
                    $stage3Icon = 'fa-circle-dot';
                    if ($isApproved) {
                        $stage3Class = 'is-done';
                        $stage3Icon = 'fa-check-double';
                        $stage3Label = 'Decision: Approved';
                        $stage3Sub = 'Your Letter of Intent has been approved by the Department Head.';
                    } elseif ($isDenied) {
                        $stage3Class = 'is-denied';
                        $stage3Icon = 'fa-times-circle';
                        $stage3Label = 'Decision: Denied / Returned';
                        $stage3Sub = 'Your Letter of Intent was denied. Please review the remarks below and upload a replacement.';
                    } elseif ($isOnHold) {
                        $stage3Class = 'is-onhold';
                        $stage3Icon = 'fa-pause-circle';
                        $stage3Label = 'Decision: Placed On Hold';
                        $stage3Sub = 'Your Letter of Intent is on hold pending further review or documents.';
                    } else {
                        $stage3Label = 'Decision: Awaiting Review (Approved / Denied)';
                        $stage3Sub = 'Your submission is pending review. The Department Head will mark it as Approved or Denied.';
                    }
                    ?>
                    <div class="loi-tl-item <?= $stage3Class ?>" id="loiTlStage3">
                        <div class="loi-tl-dot">
                            <i class="fas <?= $stage3Icon ?>"></i>
                        </div>
                        <div class="loi-tl-content">
                            <div class="loi-tl-label" id="loiTlLabel3"><?= htmlspecialchars($stage3Label) ?></div>
                            <div class="loi-tl-sub" id="loiTlSub3"><?= htmlspecialchars($stage3Sub) ?></div>
                        </div>
                    </div>

                </div><!-- /loi-timeline -->

                <!-- Remarks from Dept Head (shown if denied or on hold) -->
                <?php if ($loiRemarks !== '' && ($isDenied || $isOnHold)): ?>
                    <?php $remarksClass = $isDenied ? 'is-denied' : 'is-onhold'; ?>
                    <div class="loi-remarks-block <?= $remarksClass ?>" id="loiRemarksBlock">
                        <div class="loi-remarks-title">
                            <i class="fas fa-message-lines me-1"></i> Department Head Remarks
                        </div>
                        <?php $cleanRemarks = preg_replace('/^\[(Denied|On Hold|Hold|Approved)\]\s*/i', '', $loiRemarks); ?>
                        <div id="loiRemarksText"><?= nl2br(facultyClearanceEsc($cleanRemarks)) ?></div>
                    </div>
                <?php else: ?>
                    <div class="loi-remarks-block is-info d-none" id="loiRemarksBlock">
                        <div class="loi-remarks-title">
                            <i class="fas fa-message-lines me-1"></i> Department Head Remarks
                        </div>
                        <div id="loiRemarksText"></div>
                    </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-body-secondary">
                        <i class="fas fa-shield-halved me-1 text-primary"></i>
                        Submission is securely on record
                    </small>
                    <button type="button"
                        class="loi-upload-again-btn <?= $isDenied ? 'btn-denied' : ($isOnHold ? 'btn-onhold' : '') ?>"
                        id="loiUploadAgainBtn" onclick="showLoiFormPanel()" <?= $isUnderReview ? 'disabled title="Upload is locked while your Letter of Intent is under Department Head review."' : ($isDenied ? 'title="Click to upload a replacement letter"' : ($isOnHold ? 'title="Click to upload a replacement file"' : '')) ?>>
                        <?php if ($isUnderReview): ?>
                            <i class="fas fa-lock me-1"></i> Upload Locked (Under Review)
                        <?php elseif ($isDenied): ?>
                            <i class="fas fa-rotate-left me-1"></i> Upload Replacement Letter
                        <?php elseif ($isOnHold): ?>
                            <i class="fas fa-arrow-up-from-bracket me-1"></i> Upload Replacement File
                        <?php else: ?>
                            <i class="fas fa-arrow-up-from-bracket me-1"></i> Upload Again
                        <?php endif; ?>
                    </button>
                </div>

            </div><!-- /loiStatusPanel -->

        </div>
        <!-- ═══ END Letter of Intent ═══ -->

        <?php
        $nonIntentOffices = [];
        $reqItems = [];
        $reqSubmittedCount = 0;
        $reqApprovedCount = 0;
        $reqHasPending = false;
        foreach ($offices as $office) {
            if (($office['name'] ?? '') === 'Letter of Intent') {
                continue;
            }
            $nonIntentOffices[] = $office;
        }
        $reqHasDenied = false;
        $reqHasOnHold = false;
        $reqHasPending = false;
        $reqApprovedCount = 0;
        $reqSubmittedCount = 0;
        foreach ($nonIntentOffices as $off) {
            $item = $itemByOffice[(int) $off['clearance_office_id']] ?? null;
            if ($item) {
                if (!empty($item['file_path'])) {
                    $reqSubmittedCount++;
                }
                if (($item['status'] ?? '') === 'Cleared') {
                    $reqApprovedCount++;
                }
                if (($item['status'] ?? '') === 'Pending Review') {
                    $reqHasPending = true;
                }
                if (($item['status'] ?? '') === 'Hold' || ($item['status'] ?? '') === 'Denied') {
                    $reqHasDenied = true;
                }
                if (($item['status'] ?? '') === 'On Hold') {
                    $reqHasOnHold = true;
                }
            }
        }
        $reqTotal = count($nonIntentOffices);
        $reqStatusLabel = ($reqApprovedCount === $reqTotal && $reqTotal > 0)
            ? 'Approved'
            : ($reqHasDenied
                ? 'Denied'
                : ($reqHasOnHold
                    ? 'On Hold'
                    : ($reqHasPending || ($reqSubmittedCount === $reqTotal && $reqTotal > 0)
                        ? 'Pending Review'
                        : ($reqSubmittedCount > 0 ? 'In Progress' : 'Not Submitted'))));
        $reqStatusClass = $reqStatusLabel === 'Approved'
            ? 'bg-success-subtle text-success border border-success-subtle'
            : ($reqStatusLabel === 'Denied'
                ? 'bg-danger text-white border border-danger'
                : ($reqStatusLabel === 'On Hold'
                    ? 'bg-warning text-dark border border-warning'
                    : ($reqStatusLabel === 'Pending Review'
                        ? 'bg-info-subtle text-info border border-info-subtle'
                        : ($reqStatusLabel === 'In Progress'
                            ? 'bg-warning-subtle text-warning border border-warning-subtle'
                            : 'bg-secondary-subtle text-body-secondary border'))));

        $hasUploadableItems = count(array_filter($nonIntentOffices, function ($off) use ($itemByOffice) {
            $item = $itemByOffice[(int) $off['clearance_office_id']] ?? null;
            // Allow upload for anything that is not Pending Review (locked)
            $status = $item['status'] ?? 'Missing';
            return !$item || empty($item['file_path']) || $status === 'Hold' || $status === 'Denied' || $status === 'On Hold' || $status === 'Missing' || $status === 'Cleared';
        })) > 0;
        ?>

        <style>
            .req-card {
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .req-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, .09) !important;
            }

            .intent-form-shell {
                padding: 0.25rem 0;
            }

            .intent-upload-box {
                border: 1px solid var(--bs-border-color);
                border-radius: 10px;
                background: rgba(0, 0, 0, 0.015);
                padding: 0.75rem 0.9rem;
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            }

            .intent-upload-box:focus-within {
                border-color: rgba(var(--bs-primary-rgb), .55);
                box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), .08);
                background: rgba(var(--bs-primary-rgb), .02);
            }

            .intent-upload-meta {
                margin-top: 0.7rem;
                display: flex;
                align-items: center;
                gap: 0.4rem;
                font-size: 0.8rem;
                color: var(--bs-secondary-color);
                word-break: break-word;
            }

            .intent-upload-box .form-control {
                border: none;
                background: transparent;
                box-shadow: none;
                padding: 0;
            }

            .upload-zone {
                border: 2px dashed var(--bs-border-color);
                border-radius: 10px;
                padding: 14px 16px;
                cursor: pointer;
                transition: border-color 0.2s, background 0.2s;
                background: var(--bs-body-bg);
            }

            .upload-zone:hover {
                border-color: var(--bs-primary);
                background: rgba(var(--bs-primary-rgb), .04);
            }

            .upload-zone.denied-zone {
                border-color: var(--bs-danger);
            }

            .upload-zone.denied-zone:hover {
                background: rgba(var(--bs-danger-rgb), .04);
            }

            .upload-zone.onhold-zone {
                border-color: var(--bs-warning);
            }

            .upload-zone.onhold-zone:hover {
                background: rgba(var(--bs-warning-rgb), .06);
            }

            .upload-zone input[type=file] {
                display: none;
            }
        </style>

        <form id="clearanceForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit">
            <div class="card border shadow-sm">
                <div
                    class="card-header bg-body-tertiary border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-uppercase"><i class="fas fa-tasks me-2 text-primary"></i>Submit
                            Clearance Requirements</h6>
                        <small class="text-body-secondary">Upload each required document below. Approved items are securely
                            locked.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= $reqStatusClass ?> px-3 py-2 fs-7" id="clearanceReqsStatusBadge">
                            <?php if ($reqStatusLabel === 'Approved'): ?><i class="fas fa-check-double me-1"></i>
                            <?php elseif ($reqStatusLabel === 'Denied'): ?><i class="fas fa-times-circle me-1"></i>
                            <?php elseif ($reqStatusLabel === 'On Hold'): ?><i class="fas fa-pause-circle me-1"></i>
                            <?php elseif ($reqStatusLabel === 'Pending Review'): ?><i class="fas fa-clock me-1"></i>
                            <?php else: ?><i class="fas fa-circle-dot me-1"></i><?php endif; ?>
                            <?= facultyClearanceEsc($reqStatusLabel) ?>
                        </span>
                        <button type="button" id="refreshClearanceBtn" title="Reset &amp; Refresh"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 px-2"
                            onclick="clearanceResetAndRefresh(this)">
                            <i class="fas fa-rotate-right"></i>
                            <span class="d-none d-md-inline small">Reset &amp; Refresh</span>
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($nonIntentOffices as $index => $office):
                            $officeId = (int) $office['clearance_office_id'];
                            $item = $itemByOffice[$officeId] ?? null;
                            $status = $item['status'] ?? 'Missing';
                            $isApproved = $status === 'Cleared';
                            $isPending = $status === 'Pending Review';
                            $isDenied = $status === 'Denied' || $status === 'Hold';
                            $isOnHold = $status === 'On Hold';
                            $isMissing = !$item || empty($item['file_path']) || $status === 'Missing';
                            $canUpload = $isDenied || $isOnHold || $isMissing;
                            $itemLabel = facultyClearanceItemLabel($item ?? ['status' => 'Missing']);
                            $fileName = $item && $item['file_path'] ? basename((string) $item['file_path']) : null;

                            if ($isApproved) {
                                $cardBorder = 'border-success';
                                $iconClass = 'fas fa-check-circle text-success';
                                $badgeClass = 'bg-success text-white';
                                $cardBg = '';
                            } elseif ($isDenied) {
                                $cardBorder = 'border-danger';
                                $iconClass = 'fas fa-times-circle text-danger';
                                $badgeClass = 'bg-danger text-white';
                                $cardBg = '';
                            } elseif ($isOnHold) {
                                $cardBorder = 'border-warning';
                                $iconClass = 'fas fa-exclamation-triangle text-warning';
                                $badgeClass = 'bg-warning text-dark';
                                $cardBg = '';
                            } elseif ($isPending) {
                                $cardBorder = 'border-info';
                                $iconClass = 'fas fa-clock text-info';
                                $badgeClass = 'bg-info text-dark';
                                $cardBg = '';
                            } else {
                                $cardBorder = '';
                                $iconClass = 'fas fa-file-upload text-secondary';
                                $badgeClass = 'bg-secondary text-white';
                                $cardBg = '';
                            }
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="card h-100 req-card <?= $cardBorder ?> shadow-sm <?= $cardBg ?> requirement-row"
                                    data-office-id="<?= $officeId ?>">
                                    <div class="card-body p-4 d-flex flex-column gap-3">

                                        <!-- Header row -->
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width:36px;height:36px;background:rgba(0,0,0,.06);flex-shrink:0;">
                                                    <i class="<?= $iconClass ?> fs-6"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small text-uppercase text-body-secondary"
                                                        style="letter-spacing:.05em;">Requirement <?= $index + 1 ?></div>
                                                    <div class="fw-semibold fs-6">
                                                        <?= facultyClearanceEsc((string) $office['name']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge <?= $badgeClass ?> item-status px-3 py-2 rounded-pill"
                                                style="font-size:.75rem;white-space:nowrap;"><?= facultyClearanceEsc($itemLabel) ?></span>
                                        </div>

                                        <?php if ($isApproved): ?>
                                            <!-- APPROVED — re-upload allowed -->
                                            <div
                                                class="d-flex align-items-center gap-2 p-3 rounded-3 bg-success bg-opacity-10 border border-success-subtle">
                                                <i class="fas fa-file-circle-check text-success fs-5"></i>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="small fw-semibold text-success">Currently Approved</div>
                                                    <div class="small text-body-secondary text-truncate"
                                                        title="<?= facultyClearanceEsc($fileName) ?>">
                                                        <?= facultyClearanceEsc($fileName) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="file" name="requirements[<?= $officeId ?>]" id="file_<?= $officeId ?>"
                                                class="requirement-file" accept=".pdf" style="display:none">
                                            <label for="file_<?= $officeId ?>" class="upload-zone text-center d-block mb-0"
                                                style="border-color: var(--bs-success); cursor:pointer;">
                                                <div class="d-flex flex-column align-items-center gap-1 py-1">
                                                    <i class="fas fa-arrow-up-from-bracket text-success fs-5"></i>
                                                    <div class="small fw-semibold text-success">Replace with a New File <span
                                                            class="text-body-secondary fw-normal">(optional)</span></div>
                                                    <div class="small text-body-secondary file-name">Click to browse — PDF only
                                                    </div>
                                                </div>
                                            </label>

                                        <?php elseif ($isPending): ?>
                                            <!-- PENDING REVIEW -->
                                            <div
                                                class="d-flex align-items-center gap-2 p-3 rounded-3 bg-info bg-opacity-10 border border-info-subtle">
                                                <i class="fas fa-file-clock text-info fs-5"></i>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="small fw-semibold text-info">Under Review</div>
                                                    <div class="small text-body-secondary text-truncate"
                                                        title="<?= facultyClearanceEsc($fileName) ?>">
                                                        <?= facultyClearanceEsc($fileName) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="small text-body-secondary d-flex align-items-center gap-1">
                                                <i class="fas fa-lock"></i>
                                                <span>Submitted and awaiting Department Head review. Upload is locked.</span>
                                            </div>
                                            <!-- Hidden locked input -->
                                            <input type="file" name="requirements[<?= $officeId ?>]" class="requirement-file d-none"
                                                disabled>

                                        <?php elseif ($isDenied): ?>
                                            <!-- DENIED — allow re-upload -->
                                            <?php if (!empty($item['remarks'])): ?>
                                                <div class="alert alert-danger py-2 px-3 mb-0 small rounded-3 d-flex gap-2">
                                                    <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                                                    <div><strong>Dept Head Note:</strong>
                                                        <?= facultyClearanceEsc((string) $item['remarks']) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="requirements[<?= $officeId ?>]" id="file_<?= $officeId ?>"
                                                class="requirement-file" accept=".pdf" style="display:none">
                                            <label for="file_<?= $officeId ?>"
                                                class="upload-zone denied-zone text-center d-block mb-0" style="cursor:pointer;">
                                                <div class="d-flex flex-column align-items-center gap-2 py-2">
                                                    <i class="fas fa-cloud-arrow-up text-danger fs-3"></i>
                                                    <div class="small fw-semibold text-danger">Upload Replacement File</div>
                                                    <div class="small text-body-secondary file-name">Click to browse — PDF only
                                                    </div>
                                                </div>
                                            </label>

                                        <?php elseif ($isOnHold): ?>
                                            <!-- ON HOLD — allow re-upload (yellow) -->
                                            <?php if (!empty($item['remarks'])): ?>
                                                <div class="alert alert-warning py-2 px-3 mb-0 small rounded-3 d-flex gap-2">
                                                    <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                                                    <div><strong>Dept Head Note:</strong>
                                                        <?= facultyClearanceEsc((string) $item['remarks']) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="requirements[<?= $officeId ?>]" id="file_<?= $officeId ?>"
                                                class="requirement-file" accept=".pdf" style="display:none">
                                            <label for="file_<?= $officeId ?>"
                                                class="upload-zone onhold-zone text-center d-block mb-0" style="cursor:pointer;">
                                                <div class="d-flex flex-column align-items-center gap-2 py-2">
                                                    <i class="fas fa-cloud-arrow-up text-warning fs-3"></i>
                                                    <div class="small fw-semibold text-warning">Upload Replacement File</div>
                                                    <div class="small text-body-secondary file-name">Click to browse — PDF only
                                                    </div>
                                                </div>
                                            </label>

                                        <?php else: ?>
                                            <!-- NOT SUBMITTED / MISSING -->
                                            <input type="file" name="requirements[<?= $officeId ?>]" id="file_<?= $officeId ?>"
                                                class="requirement-file" accept=".pdf" style="display:none">
                                            <label for="file_<?= $officeId ?>" class="upload-zone text-center d-block mb-0"
                                                style="cursor:pointer;">
                                                <div class="d-flex flex-column align-items-center gap-2 py-2">
                                                    <i class="fas fa-cloud-arrow-up text-primary fs-3"></i>
                                                    <div class="small fw-semibold">Click to Upload Document</div>
                                                    <div class="small text-body-secondary file-name">Invalid file format</div>
                                                </div>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div
                    class="card-footer bg-body-tertiary border-top p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill"><?= $reqApprovedCount ?>/<?= $reqTotal ?></span>
                        <small class="text-body-secondary">Requirements approved ·
                            <strong><?= $reqTotal - $reqApprovedCount ?></strong> remaining</small>
                    </div>
                    <button
                        class="btn <?= $reqApprovedCount === $reqTotal && $reqTotal > 0 ? 'btn-success' : ($reqHasDenied ? 'btn-danger' : ($reqHasOnHold ? 'btn-warning text-dark' : 'btn-primary')) ?> px-5 py-2 fw-semibold"
                        type="submit" id="submitClearance" <?= !$hasUploadableItems ? 'disabled' : '' ?>>
                        <?php if ($reqApprovedCount === $reqTotal && $reqTotal > 0): ?>
                            <i class="fas fa-check-double me-2"></i>All Requirements Approved
                        <?php elseif ($reqHasDenied): ?>
                            <i class="fas fa-paper-plane me-2"></i>Resubmit Denied Requirements
                        <?php elseif ($reqHasOnHold): ?>
                            <i class="fas fa-paper-plane me-2"></i>Resubmit On Hold Requirements
                        <?php elseif ($reqHasPending): ?>
                            <i class="fas fa-clock me-2"></i>Clearance Submitted — Pending Review
                        <?php else: ?>
                            <i class="fas fa-paper-plane me-2"></i>Submit Clearance
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="modal fade loi-success-modal" id="intentSubmissionModal" tabindex="-1"
    aria-labelledby="intentSubmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            <!-- Gradient Header -->
            <div class="loi-success-modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-white mb-0" id="intentSubmissionModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Letter Submitted!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body text-center p-4 pb-2">
                <div class="loi-success-anim">
                    <i class="fas fa-file-signature fa-2x text-success"></i>
                </div>
                <h5 class="fw-bold text-body-emphasis mb-1">Submission Received!</h5>
                <p class="text-body-secondary mb-1" style="font-size:.92rem;">
                    Your <strong>Letter of Intent</strong> has been forwarded to<br>
                    the <strong>Department Head</strong> for review.
                </p>
                <div class="loi-success-badge">
                    <i class="fas fa-hourglass-half"></i>
                    Pending Review
                </div>

                <!-- What's next -->
                <div class="text-start mt-3 p-3 bg-body-tertiary rounded-3 border" style="font-size:.82rem;">
                    <div class="fw-bold text-body-secondary mb-2 text-uppercase"
                        style="letter-spacing:.05em;font-size:.7rem;">What happens next</div>
                    <div class="d-flex gap-2 mb-1"><i class="fas fa-check text-success mt-1"></i><span>Your letter is
                            now visible to the Department Head.</span></div>
                    <div class="d-flex gap-2 mb-1"><i class="fas fa-check text-success mt-1"></i><span>You will receive
                            a notification once it is reviewed.</span></div>
                    <div class="d-flex gap-2"><i class="fas fa-check text-success mt-1"></i><span>Upload is locked until
                            a review decision is made.</span></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 justify-content-center pb-4 pt-3">
                <button type="button" class="loi-submit-btn" data-bs-dismiss="modal"
                    style="background:linear-gradient(135deg,#198754,#20b264);box-shadow:0 3px 14px rgba(25,135,84,.35);">
                    <i class="fas fa-check"></i> Got it, Thanks!
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="clearanceSubmissionModal" tabindex="-1" aria-labelledby="clearanceSubmissionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="clearanceSubmissionModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Completed Submission
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center shadow-sm"
                        style="width: 72px; height: 72px;">
                        <i class="fas fa-clipboard-check fa-2x"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-body-emphasis mb-2">Completed Submission</h5>
                <p class="text-body-secondary mb-3 fs-6">
                    All your clearance requirement files have been submitted to the Department Head for review.<br>
                    <strong>Please wait for the review.</strong>
                </p>
                <div
                    class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-info-subtle text-info border border-info-subtle rounded-pill small fw-medium">
                    <i class="fas fa-hourglass-half"></i> Status: Pending Verification
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary border-top-0 justify-content-center p-3">
                <button type="button" class="btn btn-success px-4 fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i> Okay, Got it
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset & Refresh Confirmation Modal -->
<div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:48px;height:48px;">
                        <i class="fas fa-rotate-right text-warning fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-body-emphasis mb-0" id="resetConfirmModalLabel">Reset
                            Requirements?</h6>
                        <small class="text-body-secondary">This cannot be undone</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-body-secondary small mb-2">
                    Are you sure you want to <strong>reset your clearance requirements</strong>? All uploaded files will
                    be cleared so you can upload new ones.
                </p>
                <div
                    class="d-flex align-items-center gap-2 p-2 rounded-3 bg-warning-subtle border border-warning-subtle">
                    <i class="fas fa-exclamation-triangle text-warning flex-shrink-0"></i>
                    <small class="text-warning-emphasis fw-medium">Your previously uploaded files will be
                        removed.</small>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning flex-fill fw-semibold" id="confirmResetBtn">
                    <i class="fas fa-rotate-right me-1"></i> Yes, Reset
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const clearanceApi = '<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php';

    function showFacultyAlert(message, tone = 'success') {
        const box = document.getElementById('facultyAlert');
        if (!box) return;
        box.className = `alert alert-${tone} alert-dismissible fade show mb-4 shadow-sm`;
        document.getElementById('facultyAlertMessage').textContent = message;
    }

    function dismissFacultyAlert() {
        document.getElementById('facultyAlert')?.classList.add('d-none');
    }

    let _resetBtn = null;

    function clearanceResetAndRefresh(btn) {
        _resetBtn = btn;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('resetConfirmModal'));
        modal.show();
    }

    async function _executeReset() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
        modal?.hide();
        const btn = _resetBtn;
        const icon = btn?.querySelector('i');
        if (icon) icon.classList.add('fa-spin');
        if (btn) btn.disabled = true;
        try {
            const data = new FormData();
            data.append('action', 'reset-requirements');
            const response = await fetch(clearanceApi, { method: 'POST', body: data });
            const result = await response.json();
            if (!result.ok) throw new Error(result.error || 'Failed to reset clearance requirements.');
            showFacultyAlert(result.message || 'Requirements reset. Refreshing...', 'success');
            setTimeout(() => location.reload(), 400);
        } catch (error) {
            showFacultyAlert(error.message, 'danger');
            if (icon) icon.classList.remove('fa-spin');
            if (btn) btn.disabled = false;
        }
    }

    document.getElementById('confirmResetBtn')?.addEventListener('click', _executeReset);

    function showIntentSubmissionPopup() {
        const modalElement = document.getElementById('intentSubmissionModal');
        if (!modalElement) {
            window.alert('Completed Submission! Please wait for the review.');
            return;
        }
        try {
            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                return;
            }
        } catch (error) {
            console.warn('Bootstrap modal unavailable; using fallback popup.', error);
        }
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.style.opacity = '1';
        modalElement.style.zIndex = '1065';
        modalElement.removeAttribute('aria-hidden');
        let backdrop = document.getElementById('intentSubmissionBackdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'intentSubmissionBackdrop';
            document.body.appendChild(backdrop);
        }
        modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => button.addEventListener('click', () => {
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            backdrop?.remove();
        }, { once: true }));
    }

    function showClearanceSubmissionPopup() {
        const modalElement = document.getElementById('clearanceSubmissionModal');
        if (!modalElement) {
            window.alert('Completed Submission! Please wait for the review.');
            return;
        }
        try {
            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                return;
            }
        } catch (error) {
            console.warn('Bootstrap modal unavailable; using fallback popup.', error);
        }
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.style.opacity = '1';
        modalElement.style.zIndex = '1065';
        modalElement.removeAttribute('aria-hidden');
        let backdrop = document.getElementById('clearanceSubmissionBackdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'clearanceSubmissionBackdrop';
            document.body.appendChild(backdrop);
        }
        modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => button.addEventListener('click', () => {
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            backdrop?.remove();
        }, { once: true }));
    }

    document.querySelectorAll('.requirement-row').forEach(row => {
        const status = row.querySelector('.item-status')?.textContent.trim() || '';
        const input = row.querySelector('.requirement-file');
        if (input) {
            if (status.includes('Approved') || status.includes('Pending Review')) {
                input.disabled = true;
            } else {
                input.disabled = false;
            }
        }
    });

    document.querySelectorAll('.requirement-file').forEach(input => input.addEventListener('change', () => {
        const row = input.closest('.requirement-row');
        const statusBadge = row?.querySelector('.item-status');
        const fileNameEl = row?.querySelector('.file-name');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const isPdf = file.name.toLowerCase().endsWith('.pdf') || file.type === 'application/pdf';
            if (!isPdf) {
                showFacultyAlert('Invalid file type. Only PDF files (.pdf) are accepted for clearance requirements.', 'danger');
                input.value = '';
                if (fileNameEl) {
                    fileNameEl.innerHTML = '<span class="text-danger fw-semibold"><i class="fas fa-circle-exclamation me-1"></i>Invalid file type — Only PDF files are allowed</span>';
                }
                if (statusBadge && statusBadge.textContent.trim() === 'Ready to Submit') {
                    statusBadge.textContent = 'Not Submitted';
                    statusBadge.className = 'badge item-status bg-secondary text-white py-2 px-3';
                }
                return;
            }
            if (fileNameEl) fileNameEl.textContent = file.name;
            if (statusBadge && (statusBadge.textContent.trim() === 'Not Submitted' || statusBadge.textContent.trim() === 'Missing' || statusBadge.textContent.trim() === 'Denied')) {
                statusBadge.textContent = 'Ready to Submit';
                statusBadge.className = 'badge item-status bg-warning text-dark border border-warning py-2 px-3';
            }
        } else {
            if (fileNameEl && !fileNameEl.textContent.includes('retained') && !fileNameEl.textContent.includes('Submitted:')) {
                fileNameEl.textContent = 'No file selected';
            }
        }
    }));

    document.getElementById('intentFile')?.addEventListener('change', event => {
        const file = event.target.files[0];
        const fileNameEl = document.getElementById('intentFileName');
        const uploadZone = document.getElementById('loiUploadZone');
        const uploadIcon = uploadZone?.querySelector('.loi-upload-icon');
        const uploadLabel = uploadZone?.querySelector('.loi-upload-label');
        if (!file) {
            if (fileNameEl) fileNameEl.textContent = 'PDF files only · Max 10 MB';
            uploadZone?.classList.remove('has-file');
            if (uploadIcon) { uploadIcon.className = 'fas fa-cloud-arrow-up loi-upload-icon'; }
            if (uploadLabel) uploadLabel.innerHTML = 'Click to browse or drag & drop';
            return;
        }

        const isPdf = file.name.toLowerCase().endsWith('.pdf') || file.type === 'application/pdf';
        if (!isPdf) {
            showFacultyAlert('Only PDF files (.pdf) are allowed for the Letter of Intent submission.', 'danger');
            event.target.value = '';
            if (fileNameEl) {
                fileNameEl.innerHTML = '<span class="text-danger fw-semibold"><i class="fas fa-circle-exclamation me-1"></i>Invalid file type — Only PDF files (.pdf) are allowed (Max 10 MB)</span>';
            }
            uploadZone?.classList.remove('has-file');
            if (uploadIcon) { uploadIcon.className = 'fas fa-cloud-arrow-up loi-upload-icon'; }
            if (uploadLabel) uploadLabel.innerHTML = 'Click to browse or drag & drop';
            return;
        }

        if (fileNameEl) fileNameEl.textContent = file.name + ' · ' + (file.size / 1024 < 1024 ? (file.size / 1024).toFixed(1) + ' KB' : (file.size / 1048576).toFixed(2) + ' MB');
        uploadZone?.classList.add('has-file');
        if (uploadIcon) { uploadIcon.className = 'fas fa-file-pdf loi-upload-icon'; }
        if (uploadLabel) uploadLabel.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> File ready to upload';
    });

    // Drag-and-drop support for LOI upload zone
    (() => {
        const zone = document.getElementById('loiUploadZone');
        const input = document.getElementById('intentFile');
        if (!zone || !input || input.disabled) return;
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('is-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('is-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('is-over');
            const dt = e.dataTransfer;
            if (!dt || !dt.files.length) return;
            // Transfer files to the hidden input
            const transfer = new DataTransfer();
            transfer.items.add(dt.files[0]);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change'));
        });
    })();

    function resetLoiStatusBadge() {
        const statusBadge = document.getElementById('intentStatusBadge');
        const statusIcon = document.getElementById('intentStatusIcon');
        const statusText = document.getElementById('intentStatusText');

        if (statusBadge) statusBadge.className = 'loi-status-pill not-submitted';
        if (statusIcon) statusIcon.className = 'fas fa-circle-dot';
        if (statusText) statusText.textContent = 'Not Submitted';
    }

    function showLoiFormPanel() {
        const uploadAgainBtn = document.getElementById('loiUploadAgainBtn');
        if (uploadAgainBtn && uploadAgainBtn.disabled) {
            showFacultyAlert('Upload is locked while your Letter of Intent is under Department Head review.', 'warning');
            return;
        }
        resetLoiStatusBadge();
        const formPanel = document.getElementById('loiFormPanel');
        const statusPanel = document.getElementById('loiStatusPanel');
        if (formPanel && statusPanel) {
            statusPanel.style.display = 'none';
            formPanel.style.display = 'block';
            formPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function updateLoiStatusView(rawStatus, rawRemarks = '') {
        const status = String(rawStatus || '').trim().toLowerCase();
        const stage1 = document.getElementById('loiTlStage1');
        const stage2 = document.getElementById('loiTlStage2');
        const stage3 = document.getElementById('loiTlStage3');
        const label3 = document.getElementById('loiTlLabel3');
        const sub3 = document.getElementById('loiTlSub3');
        const uploadAgainBtn = document.getElementById('loiUploadAgainBtn');
        const remarksBlock = document.getElementById('loiRemarksBlock');
        const remarksText = document.getElementById('loiRemarksText');
        const statusBadge = document.getElementById('intentStatusBadge');
        const statusIcon = document.getElementById('intentStatusIcon');
        const statusText = document.getElementById('intentStatusText');

        const cleanRemarks = String(rawRemarks || '').replace(/^\[(Denied|On Hold|Hold|Approved)\]\s*/i, '');

        if (status === 'cleared' || status === 'approved') {
            if (stage1) {
                stage1.className = 'loi-tl-item is-done';
                const dot1 = stage1.querySelector('.loi-tl-dot i');
                if (dot1) dot1.className = 'fas fa-check';
            }
            if (stage2) {
                stage2.className = 'loi-tl-item is-done';
                const dot2 = stage2.querySelector('.loi-tl-dot i');
                if (dot2) dot2.className = 'fas fa-check';
            }
            if (stage3) {
                stage3.className = 'loi-tl-item is-done';
                const dot3 = stage3.querySelector('.loi-tl-dot i');
                if (dot3) dot3.className = 'fas fa-check-double';
                if (label3) label3.textContent = 'Decision: Approved';
                if (sub3) sub3.textContent = 'Your Letter of Intent has been approved by the Department Head.';
            }
            if (statusBadge) statusBadge.className = 'loi-status-pill approved';
            if (statusIcon) statusIcon.className = 'fas fa-check-circle';
            if (statusText) statusText.textContent = 'Approved';
            if (uploadAgainBtn) {
                uploadAgainBtn.disabled = false;
                uploadAgainBtn.className = 'loi-upload-again-btn';
                uploadAgainBtn.title = 'Upload a revised letter if needed';
                uploadAgainBtn.innerHTML = '<i class="fas fa-arrow-up-from-bracket me-1"></i> Upload Again';
                uploadAgainBtn.style.display = 'inline-flex';
            }
            if (remarksBlock) remarksBlock.classList.add('d-none');
        } else if (status === 'denied' || status === 'hold' || status === 'returned' || status === 'rejected') {
            if (stage1) {
                stage1.className = 'loi-tl-item is-done';
                const dot1 = stage1.querySelector('.loi-tl-dot i');
                if (dot1) dot1.className = 'fas fa-check';
            }
            if (stage2) {
                stage2.className = 'loi-tl-item is-done';
                const dot2 = stage2.querySelector('.loi-tl-dot i');
                if (dot2) dot2.className = 'fas fa-check';
            }
            if (stage3) {
                stage3.className = 'loi-tl-item is-denied';
                const dot3 = stage3.querySelector('.loi-tl-dot i');
                if (dot3) dot3.className = 'fas fa-times-circle';
                if (label3) label3.textContent = 'Decision: Denied / Returned';
                if (sub3) sub3.textContent = 'Your Letter of Intent was denied. Please review remarks below and upload a replacement.';
            }
            if (statusBadge) statusBadge.className = 'loi-status-pill denied';
            if (statusIcon) statusIcon.className = 'fas fa-times-circle';
            if (statusText) statusText.textContent = 'Denied';
            if (uploadAgainBtn) {
                uploadAgainBtn.disabled = false;
                uploadAgainBtn.className = 'loi-upload-again-btn btn-denied';
                uploadAgainBtn.title = 'Click to upload a replacement letter';
                uploadAgainBtn.innerHTML = '<i class="fas fa-rotate-left me-1"></i> Upload Replacement Letter';
                uploadAgainBtn.style.display = 'inline-flex';
            }
            if (remarksBlock) {
                remarksBlock.className = 'loi-remarks-block is-denied';
                remarksBlock.classList.remove('d-none');
                if (remarksText) remarksText.textContent = cleanRemarks || 'Your letter was denied. Please submit an updated version.';
            }
        } else if (status === 'on hold' || status === 'on_hold' || status === 'resubmit') {
            if (stage1) {
                stage1.className = 'loi-tl-item is-done';
                const dot1 = stage1.querySelector('.loi-tl-dot i');
                if (dot1) dot1.className = 'fas fa-check';
            }
            if (stage2) {
                stage2.className = 'loi-tl-item is-done';
                const dot2 = stage2.querySelector('.loi-tl-dot i');
                if (dot2) dot2.className = 'fas fa-check';
            }
            if (stage3) {
                stage3.className = 'loi-tl-item is-onhold';
                const dot3 = stage3.querySelector('.loi-tl-dot i');
                if (dot3) dot3.className = 'fas fa-pause-circle';
                if (label3) label3.textContent = 'Decision: Placed On Hold';
                if (sub3) sub3.textContent = 'Your Letter of Intent is on hold pending further review or documents.';
            }
            if (statusBadge) statusBadge.className = 'loi-status-pill onhold';
            if (statusIcon) statusIcon.className = 'fas fa-pause-circle';
            if (statusText) statusText.textContent = 'On Hold';
            if (uploadAgainBtn) {
                uploadAgainBtn.disabled = false;
                uploadAgainBtn.className = 'loi-upload-again-btn btn-onhold';
                uploadAgainBtn.title = 'Click to upload a replacement file';
                uploadAgainBtn.innerHTML = '<i class="fas fa-arrow-up-from-bracket me-1"></i> Upload Replacement File';
                uploadAgainBtn.style.display = 'inline-flex';
            }
            if (remarksBlock) {
                remarksBlock.className = 'loi-remarks-block is-onhold';
                remarksBlock.classList.remove('d-none');
                if (remarksText) remarksText.textContent = cleanRemarks || 'Your letter was placed on hold pending review.';
            }
        } else {
            // Pending Review / Under review (not clickable)
            if (stage1) {
                stage1.className = 'loi-tl-item is-done';
                const dot1 = stage1.querySelector('.loi-tl-dot i');
                if (dot1) dot1.className = 'fas fa-check';
            }
            if (stage2) {
                stage2.className = 'loi-tl-item is-active';
                const dot2 = stage2.querySelector('.loi-tl-dot i');
                if (dot2) dot2.className = 'fas fa-hourglass-half';
            }
            if (stage3) {
                stage3.className = 'loi-tl-item is-wait';
                const dot3 = stage3.querySelector('.loi-tl-dot i');
                if (dot3) dot3.className = 'fas fa-circle-dot';
                if (label3) label3.textContent = 'Decision: Awaiting Review (Approved / Denied)';
                if (sub3) sub3.textContent = 'Your submission is pending review. The Department Head will mark it as Approved or Denied.';
            }
            if (statusBadge) statusBadge.className = 'loi-status-pill pending';
            if (statusIcon) statusIcon.className = 'fas fa-hourglass-half';
            if (statusText) statusText.textContent = 'Pending Review';
            if (uploadAgainBtn) {
                uploadAgainBtn.disabled = true;
                uploadAgainBtn.className = 'loi-upload-again-btn';
                uploadAgainBtn.title = 'Upload is locked while your Letter of Intent is under Department Head review.';
                uploadAgainBtn.innerHTML = '<i class="fas fa-lock me-1"></i> Upload Locked (Under Review)';
                uploadAgainBtn.style.display = 'inline-flex';
            }
            if (remarksBlock) remarksBlock.classList.add('d-none');
        }
    }

    function showLoiStatusPanel(details = null) {
        const formPanel = document.getElementById('loiFormPanel');
        const statusPanel = document.getElementById('loiStatusPanel');
        if (!formPanel || !statusPanel) return;

        if (details && details.fileName) {
            const fileEl = document.getElementById('loiSummaryFile');
            if (fileEl) fileEl.textContent = details.fileName;

            if (details.intentType) {
                const typeEl = document.getElementById('loiSummaryTypeText');
                if (typeEl) {
                    const formatted = details.intentType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                    typeEl.textContent = formatted;
                }
            }
            const dateEl = document.getElementById('loiSummaryDate');
            if (dateEl) {
                const now = new Date();
                const formattedDate = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
                    ' at ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                dateEl.textContent = 'Submitted ' + formattedDate;
                const tlDate = document.getElementById('loiTlDate1');
                if (tlDate) {
                    tlDate.innerHTML = `<i class="fas fa-clock me-1"></i> ${formattedDate}`;
                }
            }

            // Newly submitted -> set state to Pending Review (Upload Again NOT clickable)
            updateLoiStatusView('Pending Review');
        }

        formPanel.style.display = 'none';
        statusPanel.style.display = 'block';
        statusPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    document.getElementById('intentLetterForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        const file = document.getElementById('intentFile');
        if (!file.files.length) return showFacultyAlert('Please select an intent letter file first.', 'danger');
        if (!file.files[0].name.toLowerCase().endsWith('.pdf') && file.files[0].type !== 'application/pdf') {
            showFacultyAlert('Only PDF files are allowed for the Letter of Intent submission.', 'danger');
            file.value = '';
            document.getElementById('intentFileName').textContent = 'No file selected';
            return;
        }
        const data = new FormData();
        data.append('action', 'intent');
        const chosenIntent = document.getElementById('intentChoice').value;
        const chosenFileName = file.files[0].name;
        data.append('intent_type', chosenIntent);
        data.append('intent_remarks', document.getElementById('intentRemarks').value);
        data.append('intent_file', file.files[0]);
        const button = document.getElementById('btnSubmitIntent');
        const originalBtnHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';
        try {
            const response = await fetch(clearanceApi, { method: 'POST', body: data });
            const result = await response.json();
            if (!result.ok) throw new Error(result.error);

            showFacultyAlert(result.message || 'Completed Submission. Please wait for the review.');
            showIntentSubmissionPopup();
            showLoiStatusPanel({
                fileName: chosenFileName,
                intentType: chosenIntent
            });
        } catch (error) {
            showFacultyAlert(error.message, 'danger');
        } finally {
            button.innerHTML = originalBtnHtml;
            button.disabled = false;
        }
    });

    document.getElementById('clearanceForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        const button = document.getElementById('submitClearance');
        const originalBtnHtml = button.innerHTML;

        const rows = document.querySelectorAll('.requirement-row');
        let missingInput = null;
        rows.forEach(row => {
            const input = row.querySelector('.requirement-file');
            if (!input) return;

            // Skip disabled inputs (Pending Review/Approved rows are disabled in HTML)
            if (input.disabled) return;

            const statusBadge = row.querySelector('.item-status');
            const statusText = statusBadge ? statusBadge.textContent.trim() : '';

            // Rows that are Approved or Pending Review have existing files → skip validation
            if (statusText === 'Approved' || statusText === 'Pending Review') return;

            const fileNameEl = row.querySelector('.file-name');
            const hasNewFile = input.files && input.files.length > 0;
            // "Submitted:", "Existing file retained", "File accepted" text indicate an existing server file
            const hasExistingIndicator = fileNameEl && (
                fileNameEl.textContent.includes('Existing file retained') ||
                fileNameEl.textContent.includes('Submitted:') ||
                fileNameEl.textContent.includes('File accepted')
            );

            if (!hasNewFile && !hasExistingIndicator && !missingInput) {
                missingInput = input;
            }
        });

        if (missingInput) {
            showFacultyAlert('Please attach all required clearance documents before submitting.', 'danger');
            missingInput.closest('.requirement-row')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting Clearance...';

        try {
            const response = await fetch(clearanceApi, {
                method: 'POST',
                body: new FormData(event.target)
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.error);

            if (result.clearance) {
                const facultyBadge = document.getElementById('facultyStatusBadge');
                if (facultyBadge) {
                    facultyBadge.textContent = result.clearance.status;
                    facultyBadge.className = `badge ${result.clearance.status === 'Action Required' ? 'bg-danger' : (result.clearance.status === 'Completed' ? 'bg-success' : 'bg-warning text-dark')} fs-6 px-3 py-2 shadow-sm`;
                }
                const progressTitle = document.getElementById('progressTitle');
                if (progressTitle) {
                    progressTitle.textContent = `${result.clearance.approved_items} of ${result.clearance.total_items} Requirements Completed`;
                }
                const progressBar = document.getElementById('facultyProgressBar');
                if (progressBar) {
                    progressBar.style.width = `${result.clearance.progress}%`;
                }
            }

            const sectionBadge = document.getElementById('clearanceReqsStatusBadge');
            if (sectionBadge) {
                sectionBadge.textContent = 'Status: Pending Review';
                sectionBadge.className = 'badge bg-info-subtle text-info border border-info-subtle';
            }

            if (result.clearance && Array.isArray(result.clearance.items)) {
                result.clearance.items.forEach(item => {
                    if (item.name === 'Letter of Intent') {
                        updateLoiStatusView(item.status, item.remarks || '');
                    }
                    const row = document.querySelector(`[data-office-id="${item.office_id}"]`);
                    if (row) {
                        const statusBadge = row.querySelector('.item-status');
                        if (statusBadge) {
                            const label = item.display_status || item.status;
                            statusBadge.textContent = label;
                            statusBadge.className = `badge item-status ${label === 'Approved' ? 'bg-success-subtle text-success border border-success-subtle' : (label === 'Denied' ? 'bg-danger text-white border border-danger' : (label === 'On Hold' ? 'bg-warning text-dark border border-warning' : (label === 'Pending Review' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-body-secondary border')))} py-2 px-3`;
                        }
                        const input = row.querySelector('.requirement-file');
                        if (input) {
                            input.disabled = (item.status === 'Pending Review' || item.status === 'Cleared');
                        }
                        const fileNameEl = row.querySelector('.file-name');
                        if (fileNameEl && item.file_name) {
                            fileNameEl.textContent = (item.status === 'Hold') ? 'Upload replacement file' : `Submitted: ${item.file_name}`;
                        }
                    }
                });
            }

            showFacultyAlert(result.message || 'Clearance submitted. Please wait for the review.', 'success');
            showClearanceSubmissionPopup();
        } catch (error) {
            showFacultyAlert(error.message, 'danger');
        } finally {
            button.innerHTML = originalBtnHtml;
            const allDisabled = [...document.querySelectorAll('.requirement-file')].every(inp => inp.disabled);
            button.disabled = allDisabled;
            if (allDisabled) {
                button.innerHTML = '<i class="fas fa-check me-1"></i> CLEARANCE SUBMITTED';
            }
        }
    });

    // ── Live background poller to sync Dept Head decisions (Denied, Approved, On Hold) in real-time ──
    async function pollFacultyClearanceStatus() {
        try {
            const response = await fetch(`${clearanceApi}?action=summary`);
            const data = await response.json();
            if (data && data.ok && data.clearance && Array.isArray(data.clearance.items)) {
                data.clearance.items.forEach(item => {
                    if (item.name === 'Letter of Intent') {
                        // Update the timeline labels, icons, remarks and button state in the background
                        updateLoiStatusView(item.status, item.remarks || '');
                    }
                    const row = document.querySelector(`[data-office-id="${item.office_id}"]`);
                    if (row) {
                        // Skip updating status if the user already has a file selected but not yet submitted
                        const fileInput = row.querySelector('.requirement-file');
                        const userHasPendingFile = fileInput && fileInput.files && fileInput.files.length > 0;
                        if (userHasPendingFile) return;

                        const statusBadge = row.querySelector('.item-status');
                        if (statusBadge) {
                            const label = item.display_status || item.status;
                            statusBadge.textContent = label;
                            statusBadge.className = `badge item-status ${label === 'Approved' ? 'bg-success-subtle text-success border border-success-subtle' : (label === 'Denied' ? 'bg-danger text-white border border-danger' : (label === 'On Hold' ? 'bg-warning text-dark border border-warning' : (label === 'Pending Review' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-body-secondary border')))} py-2 px-3`;
                        }
                    }
                });
            }
        } catch (e) {
            // silent catch for background polling
        }
    }
    setInterval(pollFacultyClearanceStatus, 5000);
    // Run once immediately on page load to apply any existing decision
    pollFacultyClearanceStatus();
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>