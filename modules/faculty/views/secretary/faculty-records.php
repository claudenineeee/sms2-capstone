<?php
/**
 * Faculty Records
 * Purpose: View and update faculty information (non-sensitive)
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Faculty Records';
$activeModule = 'faculty';
$activePage   = 'faculty-records';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary', 'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Faculty Records', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../../../includes/nav-icons.php';
?>
<!-- Cache-busting query added to bypass browser caching -->
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css?v=<?= time(); ?>">

<!-- ============================================================
     Faculty Records — Premium Light & Dark Mode Theme
     Hooks into SMS2 data-theme attribute system (theme.js)
     ============================================================ -->
<style>
    /* ============================================================
       DESIGN TOKENS — Light (default & [data-theme="light"])
       Modern SaaS aesthetic (Linear / Vercel style)
       ============================================================ */
    :root,
    [data-theme="light"] {
        /* Brand Accents */
        --fr-primary:        #4338CA;  /* Deep Indigo 700 */
        --fr-primary-hover:  #3730A3;  /* Indigo 800 */
        --fr-primary-soft:   #EEF2FF;  /* Indigo 50 tint */
        --fr-primary-ring:   rgba(67, 56, 202, 0.15);

        /* Surfaces — Clean, airy, low contrast hierarchy */
        --fr-surface:        #FFFFFF;  /* Cards, modals */
        --fr-surface-muted:  #F8FAFC;  /* Subtle panels, hover rows */
        --fr-surface-subtle: #F1F5F9;  /* Headers, table thead */
        --fr-page-bg:        inherit;  /* Inherit project gradient (don't override) */

        /* Typography — 4-level slate system */
        --fr-text-strong:    #0F172A;  /* Slate 900 — headings */
        --fr-text-body:      #334155;  /* Slate 700 — body text */
        --fr-text-muted:     #64748B;  /* Slate 500 — captions, meta */
        --fr-text-faint:     #94A3B8;  /* Slate 400 — placeholders, faint */

        /* Borders — Hairline, low-saturation */
        --fr-border:         rgba(15, 23, 42, 0.08);
        --fr-border-strong:  rgba(15, 23, 42, 0.12);

        /* Status — Low-saturation tint backgrounds (Stripe-style) */
        --fr-success-bg:     #ECFDF5;
        --fr-success-text:   #047857;
        --fr-success-ring:   rgba(16, 185, 129, 0.18);
        --fr-warning-bg:     #FFFBEB;
        --fr-warning-text:   #B45309;
        --fr-info-bg:        #EFF6FF;
        --fr-info-text:      #1D4ED8;
        --fr-danger-bg:      #FEF2F2;
        --fr-danger-text:    #B91C1C;

        /* Elevation */
        --fr-shadow-sm:      0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.03);
        --fr-shadow-md:      0 4px 8px -2px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.04);

        /* Radius scale */
        --fr-radius-sm:      8px;
        --fr-radius-md:      10px;
        --fr-radius-lg:      14px;
        --fr-radius-xl:      16px;
        --fr-radius-pill:    999px;

        /* Easing */
        --fr-ease:           cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ============================================================
       DESIGN TOKENS — Dark ([data-theme="dark"])
       ============================================================ */
    [data-theme="dark"] {
        --fr-primary:        #818CF8;
        --fr-primary-hover:  #A5B4FC;
        --fr-primary-soft:   rgba(99, 102, 241, 0.20);
        --fr-primary-ring:   rgba(129, 140, 248, 0.25);

        --fr-surface:        rgba(18, 28, 52, 0.88);
        --fr-surface-muted:  rgba(15, 23, 42, 0.55);
        --fr-surface-subtle: rgba(15, 23, 42, 0.75);
        --fr-page-bg:        inherit;

        --fr-text-strong:    #F1F5F9;
        --fr-text-body:      #CBD5E1;
        --fr-text-muted:     #94A3B8;
        --fr-text-faint:     #64748B;

        --fr-border:         rgba(255, 255, 255, 0.07);
        --fr-border-strong:  rgba(255, 255, 255, 0.11);

        --fr-success-bg:     rgba(6, 78, 59, 0.55);
        --fr-success-text:   #6EE7B7;
        --fr-success-ring:   rgba(16, 185, 129, 0.28);
        --fr-warning-bg:     rgba(120, 53, 15, 0.55);
        --fr-warning-text:   #FDE047;
        --fr-info-bg:        rgba(30, 58, 138, 0.55);
        --fr-info-text:      #93C5FD;
        --fr-danger-bg:      rgba(127, 29, 29, 0.55);
        --fr-danger-text:    #FCA5A5;

        --fr-shadow-sm:      0 1px 2px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0, 0, 0, 0.2);
        --fr-shadow-md:      0 4px 12px -2px rgba(0, 0, 0, 0.4), 0 2px 6px -2px rgba(0, 0, 0, 0.3);
    }

    /* ============================================================
       PAGE SHELL & TYPOGRAPHY
       ============================================================ */
    .fr-wrapper {
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--fr-text-body);
        line-height: 1.55;
        -webkit-font-smoothing: antialiased;
    }

    /* Breadcrumb tuning (keep project defaults but boost contrast) */
    .fr-wrapper + .breadcrumb,
    .breadcrumb {
        font-size: 0.78rem !important;
    }
    .breadcrumb a {
        color: var(--fr-text-muted) !important;
        text-decoration: none !important;
        transition: color 0.15s var(--fr-ease);
    }
    .breadcrumb a:hover {
        color: var(--fr-primary) !important;
    }
    .breadcrumb-item.active {
        color: var(--fr-text-strong) !important;
        font-weight: 600 !important;
    }

    /* ============================================================
       PAGE HEADER
       ============================================================ */
    .fr-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.25rem;
        flex-wrap: wrap;
        padding: 1.25rem 0 1.5rem;
    }
    .fr-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--fr-primary);
        margin-bottom: 0.45rem;
    }
    .fr-kicker::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--fr-primary);
        box-shadow: 0 0 0 3px var(--fr-primary-ring);
    }
    .fr-title {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 750;
        letter-spacing: -0.025em;
        color: var(--fr-text-strong);
        line-height: 1.2;
    }
    .fr-title i { color: var(--fr-primary); }
    .fr-subtitle {
        margin: 0.4rem 0 0;
        font-size: 0.88rem;
        color: var(--fr-text-muted);
        font-weight: 450;
    }

    /* ============================================================
       BUTTONS
       ============================================================ */
    .fr-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        border-radius: var(--fr-radius-sm);
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.16s var(--fr-ease);
        text-decoration: none !important;
        white-space: nowrap;
        line-height: 1.3;
    }
    .fr-btn-primary {
        background: var(--fr-primary);
        color: #FFFFFF !important;
        border-color: var(--fr-primary);
        box-shadow: var(--fr-shadow-sm);
    }
    .fr-btn-primary:hover {
        background: var(--fr-primary-hover);
        border-color: var(--fr-primary-hover);
        transform: translateY(-1px);
        box-shadow: var(--fr-shadow-md);
    }
    .fr-btn-ghost {
        background: var(--fr-surface);
        color: var(--fr-text-body) !important;
        border-color: var(--fr-border-strong);
    }
    .fr-btn-ghost:hover {
        background: var(--fr-surface-muted);
        color: var(--fr-text-strong) !important;
        border-color: var(--fr-text-muted);
        transform: translateY(-1px);
        box-shadow: var(--fr-shadow-sm);
    }
    .fr-btn-success {
        background: var(--fr-success-bg);
        color: var(--fr-success-text) !important;
        border-color: var(--fr-success-ring);
    }
    .fr-btn-success:hover {
        filter: brightness(0.97);
        transform: translateY(-1px);
        box-shadow: var(--fr-shadow-sm);
    }
    .fr-btn-sm { padding: 0.38rem 0.72rem; font-size: 0.76rem; }
    .fr-btn-icon-only {
        width: 32px; height: 32px;
        padding: 0;
        display: inline-grid;
        place-items: center;
    }
    .fr-btn-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.3rem;
        height: 1.3rem;
        padding: 0 0.4rem;
        border-radius: var(--fr-radius-pill);
        background: var(--fr-danger-bg);
        color: var(--fr-danger-text);
        font-size: 0.68rem;
        font-weight: 750;
        line-height: 1;
    }

    /* ============================================================
       CARDS
       ============================================================ */
    .fr-card {
        background: var(--fr-surface);
        border: 1px solid var(--fr-border);
        border-radius: var(--fr-radius-lg);
        box-shadow: var(--fr-shadow-sm);
        transition: box-shadow 0.2s var(--fr-ease), border-color 0.2s var(--fr-ease);
        overflow: hidden;
    }
    .fr-card:hover {
        box-shadow: var(--fr-shadow-md);
    }
    .fr-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem 1.25rem;
        border-bottom: 1px solid var(--fr-border);
        background: var(--fr-surface);
    }
    .fr-card-head i:first-child { color: var(--fr-primary); }
    .fr-card-title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        color: var(--fr-text-strong);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .fr-card-foot {
        padding: 0.85rem 1.25rem;
        border-top: 1px solid var(--fr-border);
        background: var(--fr-surface-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* ============================================================
       CHIPS / BADGES / TAGS
       ============================================================ */
    .fr-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.3rem 0.68rem;
        border-radius: var(--fr-radius-pill);
        font-size: 0.72rem;
        font-weight: 650;
        letter-spacing: -0.005em;
        border: 1px solid var(--fr-border-strong);
        background: var(--fr-surface-muted);
        color: var(--fr-text-muted);
        white-space: nowrap;
    }
    .fr-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.32rem;
        padding: 0.34em 0.78em;
        border-radius: var(--fr-radius-pill);
        font-size: 0.7rem;
        font-weight: 750;
        letter-spacing: 0.01em;
        border: 1px solid transparent;
        line-height: 1.2;
    }
    .fr-badge-success {
        background: var(--fr-success-bg);
        color: var(--fr-success-text);
        border-color: var(--fr-success-ring);
    }
    .fr-badge-warning {
        background: var(--fr-warning-bg);
        color: var(--fr-warning-text);
        border-color: rgba(245, 158, 11, 0.20);
    }
    .fr-badge-info {
        background: var(--fr-info-bg);
        color: var(--fr-info-text);
        border-color: rgba(59, 130, 246, 0.20);
    }

    /* ============================================================
       FORMS
       ============================================================ */
    .fr-form-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--fr-primary);
        margin-bottom: 0.35rem;
    }
    .fr-input,
    .fr-select {
        width: 100%;
        background: var(--fr-surface);
        color: var(--fr-text-body);
        border: 1px solid var(--fr-border-strong);
        border-radius: var(--fr-radius-sm);
        font-size: 0.85rem;
        padding: 0.58rem 0.8rem;
        line-height: 1.4;
        transition: all 0.15s var(--fr-ease);
        font-family: inherit;
    }
    .fr-input::placeholder { color: var(--fr-text-faint); }
    .fr-input:focus,
    .fr-select:focus {
        outline: none;
        border-color: var(--fr-primary);
        box-shadow: 0 0 0 3px var(--fr-primary-ring);
        background: var(--fr-surface);
        color: var(--fr-text-strong);
    }
    .fr-select-sm {
        width: auto;
        padding: 0.35rem 1.75rem 0.35rem 0.7rem;
        font-size: 0.76rem;
    }
    .fr-input-group {
        display: flex;
        align-items: stretch;
    }
    .fr-input-group-prepend {
        display: inline-flex;
        align-items: center;
        padding: 0 0.75rem;
        border: 1px solid var(--fr-border-strong);
        border-right: none;
        border-radius: var(--fr-radius-sm) 0 0 var(--fr-radius-sm);
        background: var(--fr-primary-soft);
        color: var(--fr-primary);
        font-size: 0.82rem;
    }
    .fr-input-group > .fr-input {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* ============================================================
       TABLES
       ============================================================ */
    .fr-table-wrap { overflow-x: auto; }
    .fr-table {
        width: 100%;
        color: var(--fr-text-body);
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }
    .fr-table thead th {
        font-size: 0.7rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--fr-text-muted);
        background: var(--fr-surface-subtle);
        border-bottom: 1px solid var(--fr-border);
        padding: 0.8rem 1rem;
        text-align: left;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .fr-table tbody td {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--fr-border);
        font-size: 0.84rem;
        color: var(--fr-text-body);
        background: var(--fr-surface);
        vertical-align: middle;
    }
    .fr-table tbody tr:last-child td { border-bottom: none; }
    .fr-table tbody tr:hover td {
        background: var(--fr-surface-muted);
    }
    .fr-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--fr-primary-soft);
        color: var(--fr-primary);
        display: inline-grid;
        place-items: center;
        flex-shrink: 0;
        font-size: 1.05rem;
        font-weight: 700;
    }
    .fr-avatar-lg {
        width: 72px;
        height: 72px;
        font-size: 1.8rem;
    }
    .fr-cell-strong {
        font-weight: 700;
        color: var(--fr-text-strong);
    }
    .fr-cell-meta {
        font-size: 0.74rem;
        color: var(--fr-text-muted);
        margin-top: 2px;
    }
    .fr-cell-dept {
        font-weight: 750;
        color: var(--fr-primary);
    }
    .fr-table-actions {
        display: flex;
        gap: 0.35rem;
        justify-content: flex-end;
    }

    /* ============================================================
       PAGINATION
       ============================================================ */
    .fr-pagination {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .fr-pagination .page-btn {
        min-width: 30px;
        height: 30px;
        padding: 0 0.6rem;
        display: inline-grid;
        place-items: center;
        border-radius: var(--fr-radius-pill);
        border: 1px solid var(--fr-border);
        background: var(--fr-surface);
        color: var(--fr-text-body);
        font-size: 0.76rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.14s var(--fr-ease);
        text-decoration: none !important;
    }
    .fr-pagination .page-btn:hover:not(:disabled) {
        background: var(--fr-surface-muted);
        border-color: var(--fr-border-strong);
        color: var(--fr-text-strong);
    }
    .fr-pagination .page-btn.active {
        background: var(--fr-primary);
        color: #FFFFFF !important;
        border-color: var(--fr-primary);
        box-shadow: 0 2px 8px -2px var(--fr-primary-ring);
    }
    .fr-pagination .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        color: var(--fr-text-faint);
    }

    /* ============================================================
       MODALS
       ============================================================ */
    .fr-modal-content {
        background: var(--fr-surface);
        color: var(--fr-text-body);
        border: 1px solid var(--fr-border);
        border-radius: var(--fr-radius-xl) !important;
        box-shadow: 0 20px 50px -10px rgba(15, 23, 42, 0.25);
        overflow: hidden;
    }
    [data-theme="dark"] .fr-modal-content {
        box-shadow: 0 20px 60px -10px rgba(0, 0, 0, 0.6);
    }
    .fr-modal-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--fr-border);
        background: var(--fr-surface);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .fr-modal-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 750;
        letter-spacing: -0.01em;
        color: var(--fr-text-strong);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .fr-modal-title i { color: var(--fr-primary); }
    .fr-modal-head .btn-close {
        filter: none;
        opacity: 0.5;
        transition: opacity 0.15s var(--fr-ease);
    }
    .fr-modal-head .btn-close:hover { opacity: 1; }
    [data-theme="dark"] .fr-modal-head .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    .fr-modal-body { padding: 1.25rem; }
    .fr-modal-foot {
        padding: 0.9rem 1.25rem 1.25rem;
        border-top: 1px solid var(--fr-border);
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        background: var(--fr-surface);
    }

    /* Info panel (inside modals) */
    .fr-info-panel {
        padding: 0.9rem 1rem;
        border-radius: var(--fr-radius-md);
        background: var(--fr-surface-muted);
        border: 1px solid var(--fr-border);
        font-size: 0.82rem;
    }
    .fr-info-panel.alert-info {
        background: var(--fr-info-bg) !important;
        color: var(--fr-info-text) !important;
        border-color: rgba(59, 130, 246, 0.22) !important;
    }
    .fr-info-row {
        display: flex;
        gap: 0.75rem;
        padding: 0.25rem 0;
    }
    .fr-info-row .k {
        flex: 0 0 120px;
        color: var(--fr-text-muted);
        font-weight: 600;
        font-size: 0.78rem;
    }
    .fr-info-row .v {
        flex: 1;
        min-width: 0;
        color: var(--fr-text-strong);
        font-weight: 550;
        font-size: 0.82rem;
        word-break: break-word;
    }

    /* ============================================================
       RESPONSIVE TWEAKS
       ============================================================ */
    @media (max-width: 767.98px) {
        .fr-title { font-size: 1.3rem; }
        .fr-table thead th,
        .fr-table tbody td { padding: 0.65rem 0.7rem; }
        .fr-info-row { flex-direction: column; gap: 0.1rem; }
        .fr-info-row .k { flex: none; }
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="fr-wrapper">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <header class="fr-page-header">
        <div>
            <span class="fr-kicker">Secretary · Faculty Management</span>
            <h2 class="fr-title">
                <i class="fas fa-id-badge me-2"></i>Faculty Records
            </h2>
            <p class="fr-subtitle">Manage profile and contact information in the CCS department directory</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="fr-btn fr-btn-success">
                <i class="fas fa-file-excel"></i>
                Export Directory
            </button>
        </div>
    </header>

    <!-- ============================================================
         MAIN GRID LAYOUT
         ============================================================ -->
    <div class="row g-4">

        <!-- Left Column: Search & Filters -->
        <div class="col-lg-5 col-xl-4">
            <section class="fr-card h-100">
                <div class="fr-card-head">
                    <h6 class="fr-card-title">
                        <i class="fas fa-sliders-h"></i>Filter Records
                    </h6>
                </div>
                <div class="p-4">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="fr-form-label">Search Faculty</label>
                                <div class="fr-input-group">
                                    <span class="fr-input-group-prepend"><i class="fas fa-search small"></i></span>
                                    <input type="text" name="search" class="fr-input" placeholder="Search by name or ID...">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="fr-form-label">Department</label>
                                <select name="department" class="fr-select">
                                    <option value="">All Departments</option>
                                    <option selected>College of Computer Studies</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="fr-form-label">Employment Status</label>
                                <select name="status" class="fr-select">
                                    <option value="">All Statuses</option>
                                    <option selected>Active</option>
                                    <option>On Leave</option>
                                    <option>Probationary</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="fr-form-label">Academic Rank</label>
                                <select name="rank" class="fr-select">
                                    <option value="">All Academic Ranks</option>
                                    <option>Instructor</option>
                                    <option>Assistant Professor</option>
                                    <option>Associate Professor</option>
                                    <option>Professor</option>
                                </select>
                            </div>
                            <div class="col-12 pt-2 d-flex gap-2">
                                <button type="submit" class="fr-btn fr-btn-primary w-100 py-2">
                                    Apply Filter
                                </button>
                                <button type="reset" class="fr-btn fr-btn-ghost w-100 py-2">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <!-- Right Column: Faculty Directory Table -->
        <div class="col-lg-7 col-xl-8">
            <section class="fr-card">
                <div class="fr-card-head">
                    <h6 class="fr-card-title">
                        <i class="fas fa-users-cog" style="color:#7C3AED;"></i>Faculty Directory
                        <span class="fr-badge fr-badge-success ms-2">18 Registered</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small" style="color:var(--fr-text-muted);" class="d-none d-sm-inline">Show:</span>
                        <select class="fr-select fr-select-sm">
                            <option>10 per page</option>
                            <option>25 per page</option>
                            <option>50 per page</option>
                        </select>
                    </div>
                </div>
                <div class="fr-table-wrap">
                    <table class="fr-table align-middle">
                        <thead>
                            <tr>
                                <th>Faculty Member</th>
                                <th>Dept</th>
                                <th>Contact Information</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $faculty = [
                                ['id'=>'F-001','name'=>'Dr. Maria Santos','dept'=>'CCS','contact'=>'+63 912 345 6789','email'=>'msantos@bestlink.edu.ph','status'=>'Active'],
                                ['id'=>'F-002','name'=>'Prof. Luis Tan','dept'=>'CCS','contact'=>'+63 917 234 5678','email'=>'ltan@bestlink.edu.ph','status'=>'Active'],
                                ['id'=>'F-003','name'=>'Prof. Katherine Lim','dept'=>'CCS','contact'=>'+63 918 345 6789','email'=>'klim@bestlink.edu.ph','status'=>'Active'],
                                ['id'=>'F-004','name'=>'Prof. John Aquino','dept'=>'CCS','contact'=>'+63 919 456 7890','email'=>'jaquino@bestlink.edu.ph','status'=>'Active'],
                                ['id'=>'F-005','name'=>'Dr. Ana Reyes','dept'=>'CCS','contact'=>'+63 920 567 8901','email'=>'areyes@bestlink.edu.ph','status'=>'On Leave'],
                                ['id'=>'F-006','name'=>'Prof. Sarah Martinez','dept'=>'CCS','contact'=>'+63 921 678 9012','email'=>'smartinez@bestlink.edu.ph','status'=>'Active'],
                                ['id'=>'F-007','name'=>'Prof. Roberto Villanueva','dept'=>'CCS','contact'=>'+63 922 789 0123','email'=>'rvillanueva@bestlink.edu.ph','status'=>'Active'],
                            ];
                            foreach ($faculty as $f) {
                                $statusClass = $f['status'] === 'Active' ? 'fr-badge fr-badge-success' : 'fr-badge fr-badge-warning';
                                echo <<<HTML
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="fr-avatar flex-shrink-0">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div>
                                                <div class="fr-cell-strong">{$f['name']}</div>
                                                <div class="fr-cell-meta">ID: {$f['id']}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="fr-cell-dept">{$f['dept']}</span></td>
                                    <td>
                                        <div class="fr-cell-strong">{$f['contact']}</div>
                                        <div class="fr-cell-meta">{$f['email']}</div>
                                    </td>
                                    <td><span class="{$statusClass}">{$f['status']}</span></td>
                                    <td class="text-end">
                                        <div class="fr-table-actions">
                                            <button class="fr-btn fr-btn-ghost fr-btn-sm fr-btn-icon-only" title="View Profile" onclick="viewProfile('{$f['id']}')" style="color:var(--fr-primary) !important;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="fr-btn fr-btn-ghost fr-btn-sm fr-btn-icon-only" title="Update Info" onclick="updateInfo('{$f['id']}')" style="color:#D97706 !important;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                HTML;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="fr-card-foot">
                    <small style="color:var(--fr-text-muted); font-weight:600;">Showing 1 to 7 of 18 records</small>
                    <ul class="fr-pagination">
                        <li><button class="page-btn" disabled>Previous</button></li>
                        <li><button class="page-btn active">1</button></li>
                        <li><button class="page-btn">2</button></li>
                        <li><button class="page-btn">3</button></li>
                        <li><button class="page-btn" style="color:var(--fr-primary);">Next</button></li>
                    </ul>
                </div>
            </section>
        </div>

    </div>

    <!-- ============================================================
         VIEW PROFILE MODAL
         ============================================================ -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content fr-modal-content">
                <div class="fr-modal-head">
                    <h5 class="fr-modal-title"><i class="fas fa-id-card"></i>Faculty Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="fr-modal-body">
                    <div class="row g-4">
                        <div class="col-md-4 text-center" style="border-right:1px solid var(--fr-border);">
                            <div class="fr-avatar fr-avatar-lg mx-auto mb-3">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h5 style="font-weight:750; color:var(--fr-text-strong); margin:0 0 0.25rem; letter-spacing:-0.02em;" id="modalName">Dr. Maria Santos</h5>
                            <p class="fr-cell-meta mb-3">Professor • CCS</p>
                            <span class="fr-badge fr-badge-success">Active</span>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-4">
                                <h6 class="fr-form-label mb-3"><i class="fas fa-info-circle me-2"></i>Contact Details</h6>
                                <div class="fr-info-panel">
                                    <div class="fr-info-row"><span class="k">Faculty ID:</span><span class="v" id="modalId">F-001</span></div>
                                    <div class="fr-info-row"><span class="k">Email:</span><span class="v">msantos@bestlink.edu.ph</span></div>
                                    <div class="fr-info-row"><span class="k">Contact:</span><span class="v">+63 912 345 6789</span></div>
                                    <div class="fr-info-row"><span class="k">Department:</span><span class="v">College of Computer Studies</span></div>
                                </div>
                            </div>
                            <div>
                                <h6 class="fr-form-label mb-3"><i class="fas fa-map-marker-alt me-2"></i>Residential Address</h6>
                                <div class="fr-info-panel">
                                    <span style="color:var(--fr-text-strong); font-weight:550;">123 Main Street, Quezon City, Metro Manila</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fr-modal-foot">
                    <button type="button" class="fr-btn fr-btn-ghost" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="fr-btn fr-btn-primary" onclick="updateInfo()">Edit Details</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         UPDATE INFORMATION MODAL
         ============================================================ -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content fr-modal-content">
                <div class="fr-modal-head">
                    <h5 class="fr-modal-title"><i class="fas fa-edit"></i>Update Directory Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="fr-modal-body">
                    <div class="fr-info-panel alert-info mb-4" style="font-size:0.78rem;">
                        <i class="fas fa-info-circle me-2"></i>Updates are restricted to non-sensitive contact details only.
                    </div>
                    <div class="mb-3">
                        <label class="fr-form-label">Contact Number</label>
                        <input type="text" class="fr-input" value="+63 912 345 6789">
                    </div>
                    <div class="mb-3">
                        <label class="fr-form-label">Email Address</label>
                        <input type="email" class="fr-input" value="msantos@bestlink.edu.ph">
                    </div>
                    <div class="mb-3">
                        <label class="fr-form-label">Address</label>
                        <textarea class="fr-input" rows="2">123 Main Street, Quezon City, Metro Manila</textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="fr-form-label">Emergency Contact Name</label>
                            <input type="text" class="fr-input" value="Juan Santos">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fr-form-label">Emergency Contact Number</label>
                            <input type="text" class="fr-input" value="+63 923 456 7890">
                        </div>
                    </div>
                </div>
                <div class="fr-modal-foot">
                    <button type="button" class="fr-btn fr-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="fr-btn fr-btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function viewProfile(id) {
    const modal = new bootstrap.Modal(document.getElementById('profileModal'));
    modal.show();
}
function updateInfo(id) {
    const modal = new bootstrap.Modal(document.getElementById('updateModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>