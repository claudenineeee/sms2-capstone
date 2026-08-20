<?php
/**
 * Secretary Dashboard
 * Purpose: Premium SaaS-style overview of secretary tasks and department status
 * Design: Linear / Vercel / Stripe inspired
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Secretary Dashboard';
$activeModule = 'faculty';
$activePage   = 'dashboard';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary', 'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Dashboard', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
require_once __DIR__ . '/../../../../includes/nav-icons.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/faculty/assets/css/faculty.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard-glass.css">

<!-- ============================================================
     PREMIUM SAAS DASHBOARD — DESIGN TOKENS & COMPONENT STYLES
     Linear / Vercel / Stripe inspired
     ============================================================ -->
<style>
    /* ---------- Design Tokens ---------- */
    :root,
    [data-theme="light"] {
        --sec-bg:              #F8FAFC;
        --sec-bg-elevated:     #FFFFFF;
        --sec-surface:         #FFFFFF;
        --sec-surface-muted:   #F1F5F9;
        --sec-surface-hover:   #F8FAFC;
        --sec-border:          rgba(15, 23, 42, 0.08);
        --sec-border-strong:   rgba(15, 23, 42, 0.12);
        --sec-text:            #334155;
        --sec-text-strong:     #0F172A;
        --sec-text-muted:      #64748B;
        --sec-text-faint:      #94A3B8;
        --sec-accent:          #2563EB;
        --sec-accent-2:        #4F46E5;
        --sec-success:         #10B981;
        --sec-warning:         #F59E0B;
        --sec-danger:          #EF4444;
        --sec-info:            #3B82F6;
        --sec-shadow-sm:       0 1px 2px rgba(15,23,42,0.04), 0 1px 3px rgba(15,23,42,0.03);
        --sec-shadow-md:       0 4px 6px -1px rgba(15,23,42,0.04), 0 2px 4px -2px rgba(15,23,42,0.03);
        --sec-shadow-lg:       0 10px 15px -3px rgba(15,23,42,0.05), 0 4px 6px -4px rgba(15,23,42,0.03);
        --sec-radius-xs:       6px;
        --sec-radius-sm:       8px;
        --sec-radius-md:       12px;
        --sec-radius-lg:       16px;
        --sec-radius-xl:       20px;
        --sec-ease:            cubic-bezier(0.4, 0, 0.2, 1);
    }

    [data-theme="dark"] {
        --sec-bg:              #080E1E;
        --sec-bg-elevated:     #0B132B;
        --sec-surface:         #0F172A;
        --sec-surface-muted:   #131C31;
        --sec-surface-hover:   #111C33;
        --sec-border:          rgba(255, 255, 255, 0.06);
        --sec-border-strong:   rgba(255, 255, 255, 0.10);
        --sec-text:            #CBD5E1;
        --sec-text-strong:     #F1F5F9;
        --sec-text-muted:      #94A3B8;
        --sec-text-faint:      #64748B;
        --sec-accent:          #3B82F6;
        --sec-accent-2:        #6366F1;
        --sec-success:         #34D399;
        --sec-warning:         #FBBF24;
        --sec-danger:          #F87171;
        --sec-info:            #60A5FA;
        --sec-shadow-sm:       0 1px 2px rgba(0,0,0,0.3), 0 1px 3px rgba(0,0,0,0.2);
        --sec-shadow-md:       0 4px 6px -1px rgba(0,0,0,0.35), 0 2px 4px -2px rgba(0,0,0,0.2);
        --sec-shadow-lg:       0 10px 15px -3px rgba(0,0,0,0.4), 0 4px 6px -4px rgba(0,0,0,0.25);
    }

    /* ---------- Dashboard Shell ---------- */
    .sec-dashboard {
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        color: var(--sec-text);
        line-height: 1.5;
        padding-bottom: 2rem;
    }

    /* ---------- Page Header (Vercel-style) ---------- */
    .sec-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        padding: 1.75rem 0 0.25rem;
    }

    .sec-header-left { min-width: 0; flex: 1; }

    .sec-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--sec-accent);
        margin-bottom: 0.6rem;
    }
    .sec-kicker::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--sec-accent);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }

    .sec-title {
        margin: 0;
        font-size: 1.65rem;
        font-weight: 700;
        letter-spacing: -0.025em;
        color: var(--sec-text-strong);
        line-height: 1.2;
    }

    .sec-subtitle {
        margin: 0.45rem 0 0;
        font-size: 0.9rem;
        color: var(--sec-text-muted);
        font-weight: 450;
    }

    .sec-header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .sec-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.95rem;
        border-radius: var(--sec-radius-sm);
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.18s var(--sec-ease);
        text-decoration: none !important;
        white-space: nowrap;
        line-height: 1.3;
    }
    .sec-btn-primary {
        background: var(--sec-text-strong);
        color: var(--sec-bg-elevated);
        border-color: var(--sec-text-strong);
        box-shadow: var(--sec-shadow-sm);
    }
    .sec-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: var(--sec-shadow-md);
        opacity: 0.96;
    }
    .sec-btn-secondary {
        background: var(--sec-surface);
        color: var(--sec-text-strong);
        border-color: var(--sec-border-strong);
    }
    .sec-btn-secondary:hover {
        background: var(--sec-surface-hover);
        border-color: var(--sec-text-muted);
        transform: translateY(-1px);
        box-shadow: var(--sec-shadow-sm);
    }
    .sec-btn-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.25rem;
        height: 1.25rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: var(--sec-danger);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 700;
        line-height: 1;
    }

    /* ---------- Card Container Base ---------- */
    .sec-card {
        background: var(--sec-surface);
        border: 1px solid var(--sec-border);
        border-radius: var(--sec-radius-lg);
        box-shadow: var(--sec-shadow-sm);
        transition: box-shadow 0.2s var(--sec-ease), border-color 0.2s var(--sec-ease), transform 0.2s var(--sec-ease);
        overflow: hidden;
    }
    .sec-card:hover {
        box-shadow: var(--sec-shadow-md);
    }

    .sec-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.35rem 0.9rem;
        border-bottom: 1px solid var(--sec-border);
    }

    .sec-card-title-wrap { min-width: 0; }

    .sec-card-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 650;
        letter-spacing: -0.01em;
        color: var(--sec-text-strong);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .sec-card-sub {
        margin: 0.25rem 0 0;
        font-size: 0.78rem;
        color: var(--sec-text-muted);
        font-weight: 450;
    }

    .sec-card-head-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .sec-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.32rem 0.7rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: -0.005em;
        border: 1px solid var(--sec-border-strong);
        background: var(--sec-surface-muted);
        color: var(--sec-text-muted);
        white-space: nowrap;
    }

    .sec-chip-date {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.32rem 0.75rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        background: rgba(59,130,246,0.10);
        color: var(--sec-accent);
        border: 1px solid rgba(59,130,246,0.18);
    }

    .sec-card-body { padding: 1.35rem; }

    /* ---------- Metric Grid ---------- */
    .sec-metric-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }
    @media (max-width: 1199.98px) {
        .sec-metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .sec-metric-grid { grid-template-columns: 1fr; }
    }

    .sec-metric {
        position: relative;
        padding: 1.1rem 1.05rem 1rem;
        background: var(--sec-surface-muted);
        border: 1px solid var(--sec-border);
        border-radius: var(--sec-radius-md);
        transition: all 0.22s var(--sec-ease);
        min-width: 0;
        overflow: hidden;
    }
    .sec-metric::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        pointer-events: none;
        background: linear-gradient(135deg, transparent 0%, transparent 70%, var(--metric-tint, transparent) 100%);
        opacity: 0.5;
    }
    .sec-metric:hover {
        transform: translateY(-2px);
        border-color: var(--sec-border-strong);
        box-shadow: var(--sec-shadow-md);
    }

    .sec-metric-icon {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        color: #fff;
        margin-bottom: 0.85rem;
        z-index: 1;
    }
    .sec-metric-icon svg { width: 20px; height: 20px; stroke-width: 2.1; }

    .mi-blue   { background: linear-gradient(145deg, #2563EB, #3B82F6); box-shadow: 0 8px 20px -6px rgba(37,99,235,0.5); --metric-tint: rgba(59,130,246,0.08); }
    .mi-purple { background: linear-gradient(145deg, #7C3AED, #A855F7); box-shadow: 0 8px 20px -6px rgba(124,58,237,0.5); --metric-tint: rgba(168,85,247,0.08); }
    .mi-green  { background: linear-gradient(145deg, #059669, #10B981); box-shadow: 0 8px 20px -6px rgba(5,150,105,0.5); --metric-tint: rgba(16,185,129,0.08); }
    .mi-orange { background: linear-gradient(145deg, #EA580C, #F97316); box-shadow: 0 8px 20px -6px rgba(234,88,12,0.5); --metric-tint: rgba(249,115,22,0.08); }

    .sec-metric-label {
        position: relative;
        z-index: 1;
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--sec-text-faint);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0;
    }

    .sec-metric-value {
        position: relative;
        z-index: 1;
        font-size: 1.7rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--sec-text-strong);
        line-height: 1.1;
        margin: 0.3rem 0 0.6rem;
        font-variant-numeric: tabular-nums;
    }

    .sec-metric-foot {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .sec-trend {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.72rem;
        font-weight: 650;
        letter-spacing: -0.01em;
    }
    .sec-trend svg { width: 12px; height: 12px; stroke-width: 3; }
    .sec-trend.up   { color: var(--sec-success); }
    .sec-trend.down { color: var(--sec-danger); }

    .sec-metric-compare {
        font-size: 0.68rem;
        color: var(--sec-text-faint);
        font-weight: 500;
    }

    /* ---------- Two-Column Content Grid ---------- */
    .sec-content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: 1.25rem;
        margin-top: 1.25rem;
    }
    @media (max-width: 991.98px) {
        .sec-content-grid { grid-template-columns: 1fr; }
    }

    .sec-stack { display: grid; gap: 1.25rem; }

    /* ---------- Schedule Section ---------- */
    .sec-schedule-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.5rem;
    }
    @media (max-width: 650px) {
        .sec-schedule-grid { grid-template-columns: 1fr; }
    }

    .sec-sched-block-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }

    .sec-sched-block-title {
        margin: 0;
        font-size: 0.78rem;
        font-weight: 650;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: var(--sec-text-muted);
    }

    .sec-sched-block-title::before {
        content: '';
        width: 2px; height: 0.9rem;
        border-radius: 2px;
        display: inline-block;
        margin-right: 0.5rem;
        vertical-align: -2px;
    }
    .sched-am .sec-sched-block-title::before { background: var(--sec-info); }
    .sched-pm .sec-sched-block-title::before { background: var(--sec-warning); }

    .sec-sched-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 0.4rem;
    }

    .sec-sched-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        border-radius: var(--sec-radius-sm);
        border: 1px solid var(--sec-border);
        background: var(--sec-bg-elevated);
        transition: all 0.15s var(--sec-ease);
    }
    .sec-sched-item:hover {
        border-color: var(--sec-border-strong);
        background: var(--sec-surface-hover);
    }

    .sec-sched-time {
        flex-shrink: 0;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.22rem 0.55rem;
        border-radius: var(--sec-radius-xs);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.01em;
    }
    .sched-am .sec-sched-time {
        background: rgba(59,130,246,0.10);
        color: var(--sec-info);
    }
    .sched-pm .sec-sched-time {
        background: rgba(245,158,11,0.12);
        color: var(--sec-warning);
    }

    .sec-sched-subj {
        flex: 1;
        min-width: 0;
        font-size: 0.82rem;
        font-weight: 550;
        color: var(--sec-text-strong);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sec-sched-room {
        flex-shrink: 0;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--sec-text-muted);
        padding: 0.18rem 0.5rem;
        border-radius: var(--sec-radius-xs);
        background: var(--sec-surface-muted);
        border: 1px solid var(--sec-border);
    }

    /* ---------- Activity Feed ---------- */
    .sec-activity {
        list-style: none;
        margin: 0;
        padding: 0;
        position: relative;
    }

    .sec-activity-item {
        display: flex;
        gap: 0.9rem;
        padding: 0.85rem 0.2rem;
        position: relative;
    }
    .sec-activity-item + .sec-activity-item {
        border-top: 1px solid var(--sec-border);
    }

    .sec-activity-icon {
        position: relative;
        flex-shrink: 0;
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: grid;
        place-items: center;
        font-size: 0.8rem;
        color: #fff;
        margin-top: 1px;
    }
    .sec-activity-icon svg { width: 16px; height: 16px; stroke-width: 2.2; }

    .ai-success { background: linear-gradient(145deg, #059669, #10B981); box-shadow: 0 6px 14px -4px rgba(5,150,105,0.5); }
    .ai-info    { background: linear-gradient(145deg, #2563EB, #3B82F6); box-shadow: 0 6px 14px -4px rgba(37,99,235,0.5); }
    .ai-primary { background: linear-gradient(145deg, #4F46E5, #6366F1); box-shadow: 0 6px 14px -4px rgba(79,70,229,0.5); }
    .ai-warning { background: linear-gradient(145deg, #D97706, #F59E0B); box-shadow: 0 6px 14px -4px rgba(217,119,6,0.5); }
    .ai-secondary { background: linear-gradient(145deg, #475569, #64748B); box-shadow: 0 6px 14px -4px rgba(71,85,105,0.5); }

    .sec-activity-body { flex: 1; min-width: 0; }

    .sec-activity-top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.2rem;
    }

    .sec-activity-title {
        font-size: 0.82rem;
        font-weight: 650;
        color: var(--sec-text-strong);
        letter-spacing: -0.005em;
    }

    .sec-activity-time {
        font-size: 0.7rem;
        color: var(--sec-text-faint);
        font-weight: 500;
        white-space: nowrap;
    }

    .sec-activity-msg {
        margin: 0;
        font-size: 0.78rem;
        color: var(--sec-text-muted);
        line-height: 1.45;
    }

    /* ---------- Deadlines ---------- */
    .sec-deadlines {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 0.5rem;
    }

    .sec-deadline-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 0.85rem;
        border-radius: var(--sec-radius-sm);
        border: 1px solid var(--sec-border);
        background: var(--sec-bg-elevated);
        transition: all 0.15s var(--sec-ease);
    }
    .sec-deadline-item:hover {
        border-color: var(--sec-border-strong);
        background: var(--sec-surface-hover);
    }

    .sec-deadline-date {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 52px;
        padding: 0.35rem 0.4rem;
        border-radius: var(--sec-radius-xs);
        background: var(--sec-surface-muted);
        border: 1px solid var(--sec-border);
    }

    .sec-deadline-date span {
        display: block;
        font-size: 0.58rem;
        font-weight: 650;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--sec-text-faint);
    }
    .sec-deadline-date strong {
        display: block;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--sec-text-strong);
        line-height: 1;
        margin-top: 1px;
        font-variant-numeric: tabular-nums;
    }

    .sec-deadline-text {
        flex: 1;
        min-width: 0;
        font-size: 0.82rem;
        font-weight: 550;
        color: var(--sec-text-strong);
        line-height: 1.35;
    }

    .sec-priority {
        flex-shrink: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .p-danger  { background: var(--sec-danger);  box-shadow: 0 0 0 3px color-mix(in srgb, var(--sec-danger) 20%, transparent); }
    .p-warning { background: var(--sec-warning); box-shadow: 0 0 0 3px color-mix(in srgb, var(--sec-warning) 20%, transparent); }
    .p-info    { background: var(--sec-info);    box-shadow: 0 0 0 3px color-mix(in srgb, var(--sec-info) 20%, transparent); }
    .p-muted   { background: var(--sec-text-faint); box-shadow: 0 0 0 3px color-mix(in srgb, var(--sec-text-faint) 18%, transparent); }

    /* ---------- Section spacing ---------- */
    .sec-mb { margin-bottom: 1.25rem; }

    /* ---------- Subtle fade-in on load ---------- */
    .sec-card,
    .sec-metric,
    .sec-header {
        animation: secFadeIn 0.5s var(--sec-ease) both;
    }
    .sec-header { animation-delay: 0.00s; }
    .sec-card:nth-of-type(1) { animation-delay: 0.05s; }
    .sec-card:nth-of-type(2) { animation-delay: 0.08s; }
    .sec-card:nth-of-type(3) { animation-delay: 0.11s; }
    .sec-card:nth-of-type(4) { animation-delay: 0.14s; }
    .sec-metric:nth-child(1) { animation-delay: 0.12s; }
    .sec-metric:nth-child(2) { animation-delay: 0.15s; }
    .sec-metric:nth-child(3) { animation-delay: 0.18s; }
    .sec-metric:nth-child(4) { animation-delay: 0.21s; }

    @keyframes secFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ---------- color-mix fallback for Safari ---------- */
    @supports not (color: color-mix(in srgb, red, blue)) {
        .p-danger  { background: var(--sec-danger);  box-shadow: 0 0 0 3px rgba(239,68,68,0.2); }
        .p-warning { background: var(--sec-warning); box-shadow: 0 0 0 3px rgba(245,158,11,0.2); }
        .p-info    { background: var(--sec-info);    box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        .p-muted   { background: var(--sec-text-faint); box-shadow: 0 0 0 3px rgba(148,163,184,0.18); }
    }
</style>

<div class="sec-dashboard">

    <!-- ============================================================
         PAGE HEADER — Vercel style
         ============================================================ -->
    <header class="sec-header">
        <div class="sec-header-left">
            <span class="sec-kicker">Faculty · Secretary Workspace</span>
            <h1 class="sec-title">Secretary Dashboard</h1>
            <p class="sec-subtitle">College of Computer Studies (CCS) — Task overview, department status, and quick actions.</p>
        </div>
        <div class="sec-header-actions">
            <button type="button" class="sec-btn sec-btn-secondary" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/secretary/pages/daily-attendance-log.php'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <polyline points="9 16 11 18 15 14"/>
                </svg>
                Record Attendance
            </button>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-3 shadow-sm" onclick="window.location.href='<?= BASE_URL ?>/modules/faculty/users/secretary/pages/leave-request-screening.php'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
                <span>Pending Screening</span>
                <span class="badge bg-white text-primary rounded-pill fw-bold">3</span>
            </button>
        </div>
    </header>

    <!-- ============================================================
         PERFORMANCE OVERVIEW — 4 metric cards in a panel
         ============================================================ -->
    <section class="sec-card sec-mb">
        <div class="sec-card-head">
            <div class="sec-card-title-wrap">
                <h2 class="sec-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Performance overview
                </h2>
                <p class="sec-card-sub">Key metrics for your workspace · Faculty operations snapshot</p>
            </div>
            <div class="sec-card-head-right">
                <span class="sec-chip">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    This month
                </span>
            </div>
        </div>
        <div class="sec-card-body">
            <div class="sec-metric-grid">

                <!-- Metric 1: Blue — Total Faculty (Presentation/Screen icon) -->
                <article class="sec-metric">
                    <div class="sec-metric-icon mi-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <p class="sec-metric-label">Total Faculty</p>
                    <p class="sec-metric-value">102</p>
                    <div class="sec-metric-foot">
                        <span class="sec-trend up">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="19" x2="12" y2="5"/>
                                <polyline points="5 12 12 5 19 12"/>
                            </svg>
                            +1.0%
                        </span>
                        <span class="sec-metric-compare">vs last month</span>
                    </div>
                </article>

                <!-- Metric 2: Purple — On Leave Today (Calendar + Cross icon) -->
                <article class="sec-metric">
                    <div class="sec-metric-icon mi-purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <line x1="17" y1="14" x2="10" y2="21"/>
                        </svg>
                    </div>
                    <p class="sec-metric-label">On Leave Today</p>
                    <p class="sec-metric-value">5</p>
                    <div class="sec-metric-foot">
                        <span class="sec-trend up">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="19" x2="12" y2="5"/>
                                <polyline points="5 12 12 5 19 12"/>
                            </svg>
                            +2
                        </span>
                        <span class="sec-metric-compare">vs yesterday</span>
                    </div>
                </article>

                <!-- Metric 3: Emerald — Avg Eval Rating (Star icon) -->
                <article class="sec-metric">
                    <div class="sec-metric-icon mi-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <p class="sec-metric-label">Avg Eval Rating</p>
                    <p class="sec-metric-value">4.2</p>
                    <div class="sec-metric-foot">
                        <span class="sec-trend up">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="19" x2="12" y2="5"/>
                                <polyline points="5 12 12 5 19 12"/>
                            </svg>
                            +0.1
                        </span>
                        <span class="sec-metric-compare">vs last term</span>
                    </div>
                </article>

                <!-- Metric 4: Orange — Pending Requests (Clock icon) -->
                <article class="sec-metric">
                    <div class="sec-metric-icon mi-orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <p class="sec-metric-label">Pending Requests</p>
                    <p class="sec-metric-value">8</p>
                    <div class="sec-metric-foot">
                        <span class="sec-trend down">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                            -3
                        </span>
                        <span class="sec-metric-compare">vs last week</span>
                    </div>
                </article>

            </div>
        </div>
    </section>

    <!-- ============================================================
         TWO-COLUMN CONTENT GRID
         ============================================================ -->
    <div class="sec-content-grid">

        <!-- LEFT STACK: Schedule + Activity -->
        <div class="sec-stack">

            <!-- Today's Schedule Overview -->
            <section class="sec-card">
                <div class="sec-card-head">
                    <div class="sec-card-title-wrap">
                        <h2 class="sec-card-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Today's Schedule Overview
                        </h2>
                        <p class="sec-card-sub">CCS class schedule · 6 classes across 4 rooms</p>
                    </div>
                    <div class="sec-card-head-right">
                        <span class="sec-chip-date">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            August 1, 2025
                        </span>
                    </div>
                </div>
                <div class="sec-card-body">
                    <div class="sec-schedule-grid">

                        <!-- Morning -->
                        <div class="sched-am">
                            <div class="sec-sched-block-head">
                                <h3 class="sec-sched-block-title">Morning Classes · 8 AM – 12 PM</h3>
                            </div>
                            <ul class="sec-sched-list">
                                <li class="sec-sched-item">
                                    <span class="sec-sched-time">8:00–9:30</span>
                                    <span class="sec-sched-subj">CS101 · Intro to CS</span>
                                    <span class="sec-sched-room">Room 201</span>
                                </li>
                                <li class="sec-sched-item">
                                    <span class="sec-sched-time">10:00–11:30</span>
                                    <span class="sec-sched-subj">CS201 · Data Structures</span>
                                    <span class="sec-sched-room">Room 202</span>
                                </li>
                                <li class="sec-sched-item">
                                    <span class="sec-sched-time">9:30–11:00</span>
                                    <span class="sec-sched-subj">CS401 · Software Engineering</span>
                                    <span class="sec-sched-room">Room 203</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Afternoon -->
                        <div class="sched-pm">
                            <div class="sec-sched-block-head">
                                <h3 class="sec-sched-block-title">Afternoon Classes · 1 PM – 5 PM</h3>
                            </div>
                            <ul class="sec-sched-list">
                                <li class="sec-sched-item">
                                    <span class="sec-sched-time">1:00–3:00</span>
                                    <span class="sec-sched-subj">CS301 · Algorithms</span>
                                    <span class="sec-sched-room">Room 301</span>
                                </li>
                                <li class="sec-sched-item">
                                    <span class="sec-sched-time">2:00–4:00</span>
                                    <span class="sec-sched-subj">IT401 · Network Security</span>
                                    <span class="sec-sched-room">Room 302</span>
                                </li>
                                <li class="sec-sched-item">
                                    <span class="sec-sched-time">1:00–2:30</span>
                                    <span class="sec-sched-subj">CS501 · Research Methods</span>
                                    <span class="sec-sched-room">Room 204</span>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="sec-card">
                <div class="sec-card-head">
                    <div class="sec-card-title-wrap">
                        <h2 class="sec-card-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Recent Activity
                        </h2>
                        <p class="sec-card-sub">Latest actions and system events from the CCS department</p>
                    </div>
                </div>
                <div class="sec-card-body" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                    <ul class="sec-activity">
                        <?php
                        $activities = [
                            ['type' => 'success',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>', 'title' => 'Leave screened',       'msg' => 'Prof. J. Aquino sick leave request forwarded to Dept. Head',           'time' => '1h ago'],
                            ['type' => 'info',      'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', 'title' => 'Document uploaded',  'msg' => 'Contract renewal for Prof. S. Alvarez uploaded to personnel files',    'time' => '2h ago'],
                            ['type' => 'primary',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>', 'title' => 'Attendance recorded','msg' => '16 faculty attendance logged for today via biometric terminal',        'time' => '3h ago'],
                            ['type' => 'warning',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>', 'title' => 'Leave returned',     'msg' => 'Prof. R. Villanueva request returned for additional supporting docs',  'time' => '5h ago'],
                            ['type' => 'secondary', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>', 'title' => 'Record updated',     'msg' => 'Contact information updated for Dr. M. Santos (Faculty Profile)',      'time' => '1d ago'],
                        ];
                        foreach ($activities as $act) {
                            echo <<<HTML
                            <li class="sec-activity-item">
                                <div class="sec-activity-icon ai-{$act['type']}">{$act['icon']}</div>
                                <div class="sec-activity-body">
                                    <div class="sec-activity-top">
                                        <span class="sec-activity-title">{$act['title']}</span>
                                        <span class="sec-activity-time">{$act['time']}</span>
                                    </div>
                                    <p class="sec-activity-msg">{$act['msg']}</p>
                                </div>
                            </li>
                            HTML;
                        }
                        ?>
                    </ul>
                </div>
            </section>

        </div>

        <!-- RIGHT STACK: Deadlines (keeps visual balance) -->
        <div class="sec-stack">

            <!-- Upcoming Deadlines -->
            <section class="sec-card">
                <div class="sec-card-head">
                    <div class="sec-card-title-wrap">
                        <h2 class="sec-card-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Upcoming Deadlines
                        </h2>
                        <p class="sec-card-sub">Action items with priority · CCS Secretary queue</p>
                    </div>
                    <div class="sec-card-head-right">
                        <span class="sec-chip">5 tasks</span>
                    </div>
                </div>
                <div class="sec-card-body">
                    <ul class="sec-deadlines">
                        <?php
                        $deadlines = [
                            ['m' => 'Aug', 'd' => '02', 't' => 'Complete leave screening for pending requests', 'p' => 'danger'],
                            ['m' => 'Aug', 'd' => '03', 't' => 'Submit daily attendance report to HR office',     'p' => 'warning'],
                            ['m' => 'Aug', 'd' => '05', 't' => 'Document verification & endorsement deadline',    'p' => 'warning'],
                            ['m' => 'Aug', 'd' => '07', 't' => 'Monthly faculty performance report due',          'p' => 'info'],
                            ['m' => 'Aug', 'd' => '10', 't' => 'Send contract renewal reminders to faculty',      'p' => 'muted'],
                        ];
                        foreach ($deadlines as $item) {
                            echo <<<HTML
                            <li class="sec-deadline-item">
                                <div class="sec-deadline-date">
                                    <span>{$item['m']}</span>
                                    <strong>{$item['d']}</strong>
                                </div>
                                <span class="sec-deadline-text">{$item['t']}</span>
                                <span class="sec-priority p-{$item['p']}" title="Priority: {$item['p']}"></span>
                            </li>
                            HTML;
                        }
                        ?>
                    </ul>
                </div>
            </section>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
