<?php

require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Daily Attendance Log';
$activeModule = 'faculty';
$activePage   = 'dailty-attendance-log';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Secretary',            'url' => BASE_URL . '/modules/faculty/users/secretary/index.php'],
    ['label' => 'Daily Attendance Log', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';

// Stats from session records
$totalRecords    = count($_SESSION['attendance_records'] ?? []);
$presentFaculty  = 0;
$absentFaculty   = 0;
$totalStudents   = 0;
$totalExpected   = 0;
foreach ($_SESSION['attendance_records'] ?? [] as $r) {
    if ($r['professor_status'] === 'Present') $presentFaculty++;
    else $absentFaculty++;
    $totalStudents  += $r['present_students'];
    $totalExpected  += $r['expected_students'];
}
$overallAttendance = $totalExpected > 0 ? round(($totalStudents / $totalExpected) * 100) : 0;
?>
<style>
    .am-console {
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        letter-spacing: -0.011em;
        padding-bottom: 2rem;
    }
    :root {
        --am-border-alpha: rgba(15, 23, 42, 0.08);
        --am-border-strong: rgba(15, 23, 42, 0.12);
        --am-slate-50: #f8fafc;
        --am-slate-100:#f1f5f9;
        --am-slate-200:#e2e8f0;
        --am-slate-300:#cbd5e1;
        --am-slate-400:#94a3b8;
        --am-slate-500:#64748b;
        --am-slate-600:#475569;
        --am-slate-700:#334155;
        --am-slate-900:#0f172a;
        --am-blue:   #2f78ff;
        --am-blue-50: rgba(47,120,255,0.08);
        --am-blue-100:rgba(47,120,255,0.14);
        --am-emerald:#00d084;
        --am-emerald-50: rgba(0,208,132,0.08);
        --am-emerald-100:rgba(0,208,132,0.14);
        --am-orange: #ff9800;
        --am-orange-50: rgba(255,152,0,0.08);
        --am-orange-100:rgba(255,152,0,0.14);
        --am-red:    #ff5263;
        --am-red-50: rgba(255,82,99,0.08);
        --am-red-100:rgba(255,82,99,0.14);
        --am-purple: #a855f7;
        --am-purple-50: rgba(168,85,247,0.08);
        --am-purple-100:rgba(168,85,247,0.14);
        --am-shadow-sm: 0 1px 2px rgba(15,23,42,0.04), 0 1px 3px rgba(15,23,42,0.06);
        --am-shadow-md: 0 4px 6px -1px rgba(15,23,42,0.06), 0 2px 4px -2px rgba(15,23,42,0.06);
        --am-shadow-lg: 0 10px 15px -3px rgba(15,23,42,0.08), 0 4px 6px -4px rgba(15,23,42,0.06);
        --am-shadow-xl: 0 20px 25px -5px rgba(15,23,42,0.10), 0 8px 10px -6px rgba(15,23,42,0.08);
        --am-ease: cubic-bezier(0.4, 0, 0.2, 1);
    }
    [data-theme="dark"] {
        --am-border-alpha: rgba(148, 163, 184, 0.12);
        --am-border-strong: rgba(148, 163, 184, 0.18);
    }

    /* ── Typography ─────────────────────────────────────── */
    .tabnum { font-variant-numeric: tabular-nums; }
    .fw-disp  { font-weight: 700; letter-spacing: -0.02em; }
    .fw-mono  { font-variant-numeric: tabular-nums; font-feature-settings: "tnum"; }

    /* ── Page Header ────────────────────────────────────── */
    .page-hero {
        position: relative;
        padding: 1.75rem 1.75rem;
        border-radius: 1rem;
        border: 1px solid var(--am-border-alpha);
        background:
            radial-gradient(1200px 400px at 100% -50%, rgba(47,120,255,0.12), transparent 60%),
            radial-gradient(800px 300px at 0% 120%, rgba(168,85,247,0.10), transparent 55%),
            linear-gradient(180deg, rgba(47,120,255,0.03), transparent 60%);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    [data-theme="dark"] .page-hero {
        background:
            radial-gradient(1200px 400px at 100% -50%, rgba(47,120,255,0.18), transparent 60%),
            radial-gradient(800px 300px at 0% 120%, rgba(168,85,247,0.15), transparent 55%),
            linear-gradient(180deg, rgba(15,23,42,0.5), transparent 60%);
    }
    .page-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(15,23,42,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15,23,42,0.03) 1px, transparent 1px);
        background-size: 28px 28px;
        mask-image: radial-gradient(ellipse at 50% 0%, black, transparent 75%);
        pointer-events: none;
    }
    .hero-title {
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.15;
        color: var(--am-slate-900);
        margin: 0;
    }
    [data-theme="dark"] .hero-title { color: #f8fafc; }
    .hero-sub {
        color: var(--am-slate-500);
        font-size: 0.92rem;
        margin: 0.35rem 0 0 0;
        letter-spacing: -0.005em;
    }
    .hero-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.28rem 0.7rem;
        border-radius: 999px;
        background: var(--am-blue-50);
        color: var(--am-blue);
        font-weight: 600;
        font-size: 0.75rem;
        border: 1px solid var(--am-blue-100);
    }
    .hero-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--am-blue); box-shadow: 0 0 0 3px var(--am-blue-50); }

    .btn-hero-primary {
        background: linear-gradient(135deg, #2f78ff 0%, #5a6dff 60%, #7c3aed 100%);
        color: white !important;
        border: none !important;
        padding: 0.65rem 1.15rem !important;
        font-weight: 650 !important;
        letter-spacing: -0.01em !important;
        border-radius: 0.65rem !important;
        box-shadow: 0 1px 2px rgba(47,120,255,0.3), 0 6px 20px -6px rgba(47,120,255,0.55);
        transition: transform 0.2s var(--am-ease), box-shadow 0.2s var(--am-ease);
    }
    .btn-hero-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(47,120,255,0.3), 0 10px 28px -8px rgba(47,120,255,0.65);
    }
    .btn-hero-ghost {
        background: var(--bs-body-bg) !important;
        border: 1px solid var(--am-border-alpha) !important;
        color: var(--am-slate-700) !important;
        padding: 0.6rem 1rem !important;
        font-weight: 600 !important;
        border-radius: 0.65rem !important;
        transition: all 0.18s var(--am-ease);
    }
    [data-theme="dark"] .btn-hero-ghost { color: #cbd5e1 !important; }
    .btn-hero-ghost:hover {
        border-color: var(--am-border-strong) !important;
        transform: translateY(-1px);
        box-shadow: var(--am-shadow-md);
    }

    /* ── Premium Stat Cards ─────────────────────────────── */
    .stat-premium {
        position: relative;
        border-radius: 0.9rem;
        padding: 1.1rem 1.1rem 1rem 1.1rem;
        background: var(--bs-body-bg);
        border: 1px solid var(--am-border-alpha);
        overflow: hidden;
        transition: transform 0.22s var(--am-ease), box-shadow 0.22s var(--am-ease), border-color 0.22s var(--am-ease);
    }
    .stat-premium::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(400px 120px at 100% 0%, var(--stat-accent-50, transparent), transparent 60%);
        pointer-events: none;
    }
    .stat-premium:hover {
        transform: translateY(-2px);
        border-color: var(--am-border-strong);
        box-shadow: var(--am-shadow-lg);
    }
    .stat-premium[data-accent="blue"]   { --stat-accent: var(--am-blue);    --stat-accent-50: var(--am-blue-50);    --stat-accent-100: var(--am-blue-100); }
    .stat-premium[data-accent="emerald"]{ --stat-accent: var(--am-emerald); --stat-accent-50: var(--am-emerald-50); --stat-accent-100: var(--am-emerald-100); }
    .stat-premium[data-accent="orange"] { --stat-accent: var(--am-orange);  --stat-accent-50: var(--am-orange-50);  --stat-accent-100: var(--am-orange-100); }
    .stat-premium[data-accent="purple"] { --stat-accent: var(--am-purple);  --stat-accent-50: var(--am-purple-50);  --stat-accent-100: var(--am-purple-100); }

    .stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--am-slate-500);
        margin: 0;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 750;
        letter-spacing: -0.04em;
        color: var(--am-slate-900);
        line-height: 1;
        margin: 0.4rem 0 0.3rem 0;
    }
    [data-theme="dark"] .stat-value { color: #f8fafc; }
    .stat-icon-wrap {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: var(--stat-accent);
        background: var(--stat-accent-50);
        box-shadow: 0 0 0 4px var(--stat-accent-50);
        flex-shrink: 0;
    }
    .stat-trend {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.22rem 0.5rem;
        border-radius: 999px;
        font-weight: 650;
        font-size: 0.72rem;
    }
    .stat-trend-up   { color: var(--am-emerald); background: var(--am-emerald-50); }
    .stat-trend-down { color: var(--am-red);     background: var(--am-red-50); }
    .stat-trend-flat { color: var(--am-slate-500); background: var(--am-slate-100); }
    [data-theme="dark"] .stat-trend-flat { background: rgba(148,163,184,0.12); }

    .sparkline {
        width: 100%;
        height: 32px;
        margin-top: 0.55rem;
    }
    .spark-svg { overflow: visible; }

    .stat-meta {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        margin-top: 0.25rem;
    }
    .stat-sub {
        font-size: 0.72rem;
        color: var(--am-slate-500);
    }

    /* ── State Stepper (Premium) ────────────────────────── */
    .stepper-wrap {
        padding: 1.25rem 1.5rem 0.75rem 1.5rem;
    }
    .stepper-track {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0;
    }
    .stepper-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        position: relative;
        isolation: isolate;
    }
    .stepper-step:not(:last-child)::before {
        content: '';
        position: absolute;
        top: 22px;
        left: calc(50% + 22px);
        right: calc(-50% + 22px);
        height: 2px;
        background: var(--am-slate-200);
        z-index: 0;
        border-radius: 2px;
    }
    [data-theme="dark"] .stepper-step:not(:last-child)::before { background: rgba(148,163,184,0.18); }
    .stepper-step.done:not(:last-child)::before,
    .stepper-step.active:not(:last-child)::before {
        background: linear-gradient(90deg, var(--am-emerald) 0%, var(--am-blue) 100%);
    }
    .stepper-circle {
        width: 44px; height: 44px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        z-index: 1;
        position: relative;
        background: var(--bs-body-bg);
        border: 1.5px solid var(--am-slate-200);
        color: var(--am-slate-500);
        transition: all 0.3s var(--am-ease);
        font-size: 1rem;
    }
    [data-theme="dark"] .stepper-circle { border-color: rgba(148,163,184,0.22); }
    .stepper-step.active .stepper-circle {
        background: linear-gradient(135deg, #2f78ff, #7c3aed);
        color: white;
        border-color: transparent;
        box-shadow: 0 0 0 4px rgba(47,120,255,0.12), 0 8px 22px -8px rgba(47,120,255,0.55);
        transform: scale(1.03);
    }
    .stepper-step.done .stepper-circle {
        background: linear-gradient(135deg, #00d084, #00b894);
        color: white;
        border-color: transparent;
        box-shadow: 0 0 0 4px rgba(0,208,132,0.10);
    }
    .stepper-label {
        margin-top: 0.65rem;
        font-size: 0.72rem;
        font-weight: 650;
        color: var(--am-slate-500);
        text-align: center;
        line-height: 1.25;
        letter-spacing: -0.005em;
        max-width: 92px;
    }
    .stepper-step.active .stepper-label { color: var(--am-blue); }
    .stepper-step.done .stepper-label   { color: var(--am-emerald); }

    /* ── Swimlane Container ─────────────────────────────── */
    .swimlane-outer {
        position: relative;
        border-radius: 1rem;
        border: 1px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        box-shadow: var(--am-shadow-sm);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .swimlane-head {
        padding: 1.15rem 1.5rem 0 1.5rem;
    }
    .swimlane-body {
        padding: 1.25rem 1.5rem 1.5rem 1.5rem;
    }
    .swim-col {
        position: relative;
        border-radius: 0.85rem;
        padding: 1rem 1rem 0.25rem 1rem;
        height: 100%;
    }
    .swim-col.monitoring {
        background:
            linear-gradient(180deg, var(--am-blue-50) 0%, transparent 100%);
        border: 1px solid var(--am-blue-100);
        border-top: 3px solid var(--am-blue);
    }
    .swim-col.professor {
        background:
            linear-gradient(180deg, var(--am-emerald-50) 0%, transparent 100%);
        border: 1px solid var(--am-emerald-100);
        border-top: 3px solid var(--am-emerald);
    }
    .swim-col-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.7rem;
        margin-bottom: 0.75rem;
        border-bottom: 1px dashed var(--am-border-strong);
    }
    .swim-col-title {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: -0.005em;
        margin: 0;
    }
    .swim-col.monitoring .swim-col-title { color: var(--am-blue); }
    .swim-col.professor  .swim-col-title { color: var(--am-emerald); }
    .swim-col-icon {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem;
    }
    .swim-col.monitoring .swim-col-icon { background: var(--am-blue-50); color: var(--am-blue); }
    .swim-col.professor  .swim-col-icon { background: var(--am-emerald-50); color: var(--am-emerald); }

    .node-card {
        position: relative;
        padding: 0.85rem 0.95rem;
        border-radius: 0.7rem;
        margin-bottom: 0.7rem;
        background: var(--bs-body-bg);
        border: 1px solid var(--am-border-alpha);
        transition: all 0.26s var(--am-ease);
        opacity: 0.4;
    }
    [data-theme="dark"] .node-card { background: rgba(15,23,42,0.35); }
    .node-card:hover { opacity: 0.62; }
    .node-card.active {
        opacity: 1;
        transform: translateY(-1.5px);
        border-color: var(--am-blue);
        box-shadow: 0 6px 18px -6px rgba(47,120,255,0.45), inset 0 1px 0 rgba(255,255,255,0.6);
    }
    [data-theme="dark"] .node-card.active { box-shadow: 0 8px 24px -6px rgba(47,120,255,0.55); }
    .node-card.done {
        opacity: 0.92;
        border-color: var(--am-emerald);
        background: linear-gradient(180deg, var(--am-emerald-50), rgba(255,255,255,0.3) 70%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
    }
    [data-theme="dark"] .node-card.done {
        background: linear-gradient(180deg, rgba(0,208,132,0.08), rgba(15,23,42,0.35) 70%);
    }
    .node-card::before {
        content: '';
        position: absolute;
        left: -1px; top: 10px; bottom: 10px;
        width: 3px;
        border-radius: 3px;
        background: transparent;
        transition: all 0.25s var(--am-ease);
    }
    .node-card[data-accent="emerald"].active::before  { background: var(--am-emerald); }
    .node-card[data-accent="red"].active::before      { background: var(--am-red); }
    .node-card[data-accent="orange"].active::before   { background: var(--am-orange); }
    .node-card[data-accent="purple"].active::before   { background: var(--am-purple); }
    .node-card[data-accent="emerald"].done::before    { background: var(--am-emerald); }
    .node-card[data-accent="red"].done::before        { background: var(--am-red); }
    .node-card[data-accent="orange"].done::before     { background: var(--am-orange); }
    .node-card[data-accent="purple"].done::before     { background: var(--am-purple); }

    .node-title {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: -0.008em;
        margin: 0 0 0.22rem 0;
        color: var(--am-slate-900);
    }
    [data-theme="dark"] .node-title { color: #e2e8f0; }
    .node-title i { width: 16px; text-align: center; }
    .node-desc {
        font-size: 0.72rem;
        line-height: 1.4;
        color: var(--am-slate-500);
        margin: 0;
    }
    .branch-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 0.25rem;
    }
    .branch-col-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-align: center;
        padding: 0.25rem 0.4rem;
        border-radius: 999px;
        margin: 0 auto 0.5rem auto;
        display: inline-block;
    }
    .branch-col-label.present { background: var(--am-emerald-50); color: var(--am-emerald); }
    .branch-col-label.absent  { background: var(--am-red-50);    color: var(--am-red); }
    .branch-col-label-wrap { text-align: center; margin-bottom: 0.1rem; }

    .rejoin-node {
        position: relative;
        margin-top: 0.25rem;
        padding-left: 1.25rem;
    }
    .rejoin-node::before {
        content: '';
        position: absolute;
        left: 0; top: 0;
        width: 18px; height: 1px;
        background: var(--am-border-strong);
    }
    .rejoin-node::after {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 1px;
        background: var(--am-border-strong);
    }
    .rejoin-tag {
        position: absolute;
        left: 0; top: -9px;
        background: var(--am-purple);
        color: white;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 0.08rem 0.3rem;
        border-radius: 4px;
        letter-spacing: 0.02em;
    }

    /* ── Workflow Stage (Main Card) ─────────────────────── */
    .stage-card {
        position: relative;
        border-radius: 1rem;
        border: 1px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        box-shadow: var(--am-shadow-sm);
        padding: 1.5rem 1.5rem 1.5rem 1.5rem;
        margin-bottom: 1.5rem;
        min-height: 480px;
        overflow: hidden;
    }
    .stage-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--am-blue), var(--am-purple), var(--am-emerald), transparent);
        opacity: 0.5;
    }
    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .panel-title {
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--am-slate-900);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }
    [data-theme="dark"] .panel-title { color: #f8fafc; }
    .panel-title-icon {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: var(--am-blue-50);
        color: var(--am-blue);
        font-size: 0.95rem;
    }
    .panel-desc {
        color: var(--am-slate-500);
        font-size: 0.86rem;
        margin: 0 0 1.25rem 0;
        max-width: 720px;
    }
    .session-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.32rem 0.8rem;
        border-radius: 999px;
        background: var(--am-blue-50);
        border: 1px solid var(--am-blue-100);
        color: var(--am-blue);
        font-size: 0.75rem;
        font-weight: 650;
    }
    .status-chip-present { background: var(--am-emerald-50); border-color: var(--am-emerald-100); color: var(--am-emerald); }
    .status-chip-absent  { background: var(--am-red-50);    border-color: var(--am-red-100);    color: var(--am-red); }
    .status-chip-purple  { background: var(--am-purple-50); border-color: var(--am-purple-100); color: var(--am-purple); }

    .workflow-panel {
        transition: opacity 0.28s var(--am-ease), transform 0.28s var(--am-ease);
    }
    .workflow-panel.hidden-panel {
        display: none !important;
    }

    /* Premium Form Fields */
    .premium-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--am-slate-600);
        margin-bottom: 0.35rem;
        letter-spacing: -0.005em;
    }
    .premium-input,
    .premium-select {
        width: 100%;
        padding: 0.6rem 0.85rem;
        border-radius: 0.65rem;
        border: 1.5px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        color: var(--am-slate-900);
        font-size: 0.9rem;
        transition: all 0.2s var(--am-ease);
    }
    [data-theme="dark"] .premium-input,
    [data-theme="dark"] .premium-select { color: #e2e8f0; }
    .premium-input::placeholder { color: var(--am-slate-400); }
    .premium-input:focus,
    .premium-select:focus {
        outline: none;
        border-color: var(--am-blue);
        box-shadow: 0 0 0 3px var(--am-blue-50);
    }
    .form-grid-3 { display: grid; grid-template-columns: repeat(12, 1fr); gap: 0.9rem; }
    .fg-col-6 { grid-column: span 6; }
    .fg-col-5 { grid-column: span 5; }
    .fg-col-4 { grid-column: span 4; }
    .fg-col-3 { grid-column: span 3; }
    .fg-col-2 { grid-column: span 2; }
    .fg-col-12 { grid-column: span 12; }
    @media (max-width: 768px) {
        .fg-col-6, .fg-col-5, .fg-col-4, .fg-col-3, .fg-col-2 { grid-column: span 12; }
        .branch-split { grid-template-columns: 1fr; }
    }

    .form-cta-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 1px solid var(--am-border-alpha);
    }
    .btn-gradient-primary {
        background: linear-gradient(135deg, #2f78ff 0%, #5a6dff 55%, #7c3aed 100%);
        color: white !important;
        border: none !important;
        padding: 0.68rem 1.4rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        border-radius: 0.7rem !important;
        box-shadow: 0 2px 6px rgba(47,120,255,0.28), 0 10px 24px -10px rgba(47,120,255,0.55);
        transition: transform 0.18s var(--am-ease), box-shadow 0.18s var(--am-ease);
    }
    .btn-gradient-primary:hover {
        transform: translateY(-1.5px);
        box-shadow: 0 3px 8px rgba(47,120,255,0.32), 0 16px 34px -12px rgba(47,120,255,0.65);
    }
    .btn-gradient-emerald {
        background: linear-gradient(135deg, #00d084 0%, #00b894 100%);
        color: white !important;
        border: none !important;
        padding: 0.68rem 1.4rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        border-radius: 0.7rem !important;
        box-shadow: 0 2px 6px rgba(0,208,132,0.28), 0 10px 24px -10px rgba(0,208,132,0.55);
    }
    .btn-gradient-emerald:hover { transform: translateY(-1.5px); box-shadow: 0 3px 8px rgba(0,208,132,0.32), 0 16px 34px -12px rgba(0,208,132,0.65); }
    .btn-gradient-orange {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        color: white !important;
        border: none !important;
        padding: 0.68rem 1.4rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        border-radius: 0.7rem !important;
        box-shadow: 0 2px 6px rgba(255,152,0,0.28), 0 10px 24px -10px rgba(255,152,0,0.55);
    }
    .btn-gradient-orange:hover { transform: translateY(-1.5px); box-shadow: 0 3px 8px rgba(255,152,0,0.32), 0 16px 34px -12px rgba(255,152,0,0.65); }
    .btn-gradient-purple {
        background: linear-gradient(135deg, #2f78ff 0%, #a855f7 100%);
        color: white !important;
        border: none !important;
        padding: 0.68rem 1.4rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        border-radius: 0.7rem !important;
        box-shadow: 0 2px 6px rgba(168,85,247,0.28), 0 10px 24px -10px rgba(168,85,247,0.55);
    }
    .btn-gradient-purple:hover { transform: translateY(-1.5px); box-shadow: 0 3px 8px rgba(168,85,247,0.32), 0 16px 34px -12px rgba(168,85,247,0.65); }

    /* Presence Branch Cards */
    .branch-card {
        position: relative;
        padding: 1.25rem 1.25rem 1.1rem 1.25rem;
        border-radius: 0.9rem;
        cursor: pointer;
        border: 1.5px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        transition: all 0.26s var(--am-ease);
        overflow: hidden;
    }
    .branch-card::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0;
        transition: opacity 0.3s var(--am-ease);
        pointer-events: none;
    }
    .branch-card.present::before { background: linear-gradient(135deg, rgba(0,208,132,0.1), transparent 60%); }
    .branch-card.absent::before  { background: linear-gradient(135deg, rgba(255,82,99,0.1), transparent 60%); }
    .branch-card:hover::before { opacity: 1; }
    .branch-card.present:hover,
    .branch-card.present.active {
        border-color: var(--am-emerald);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px -10px rgba(0,208,132,0.5);
    }
    .branch-card.absent:hover,
    .branch-card.absent.active {
        border-color: var(--am-red);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px -10px rgba(255,82,99,0.5);
    }
    .branch-card-head {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 0.75rem;
    }
    .branch-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }
    .branch-card.present .branch-icon { background: var(--am-emerald-50); color: var(--am-emerald); box-shadow: 0 0 0 4px var(--am-emerald-50); }
    .branch-card.absent  .branch-icon { background: var(--am-red-50);    color: var(--am-red);    box-shadow: 0 0 0 4px var(--am-red-50); }
    .branch-card-title {
        font-size: 1.05rem;
        font-weight: 750;
        letter-spacing: -0.02em;
        margin: 0.1rem 0 0.2rem 0;
    }
    .branch-card.present .branch-card-title { color: var(--am-emerald); }
    .branch-card.absent  .branch-card-title { color: var(--am-red); }
    .branch-card-desc {
        color: var(--am-slate-500);
        font-size: 0.82rem;
        margin: 0 0 0.7rem 0;
    }
    .branch-flow-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .branch-flow-pill.present { background: var(--am-emerald-50); color: var(--am-emerald); }
    .branch-flow-pill.absent  { background: var(--am-red-50);    color: var(--am-red); }
    .flow-arrow { color: inherit; opacity: 0.8; }

    /* Signature Canvas */
    /* Layout grid adjustment: Canvas gets 2fr, Session Details gets 1fr */
.sig-layout {
    display: grid;
    grid-template-columns: 2fr 1fr; /* Gives signature twice as much width */
    gap: 1.5rem;
    align-items: start;
}

/* Ensure the canvas outer wrapper fills its container */
.sig-canvas-outer {
    display: flex;
    flex-direction: column;
    width: 100%;
}

/* Set a larger minimum height for the drawing canvas area */
.sig-canvas-wrap {
    height: 280px; /* Adjust height here (e.g., 250px - 350px) for vertical space */
    position: relative;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #ffffff;
}

/* Stack vertically on tablet/mobile screens */
@media (max-width: 768px) {
    .sig-layout {
        grid-template-columns: 1fr;
    }
}
    .sig-canvas-wrap::before {
        content: 'Signature Area';
        position: absolute;
        left: 50%;
        bottom: 12px;
        transform: translateX(-50%);
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--am-slate-300);
        pointer-events: none;
    }
    .sig-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.4rem 0.1rem 0.2rem 0.2rem;
    }
    .sig-tool-left { display: flex; align-items: center; gap: 0.6rem; }
    .sig-color-dot {
        width: 26px; height: 26px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 0 1px var(--am-border-alpha);
        cursor: pointer;
        transition: transform 0.15s var(--am-ease), box-shadow 0.15s var(--am-ease);
    }
    .sig-color-dot:hover { transform: scale(1.12); }
    .sig-color-dot.active { box-shadow: 0 0 0 2px var(--am-blue); }
    .btn-ghost-mini {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 0.55rem;
        border: 1px solid var(--am-border-alpha);
        background: white;
        color: var(--am-slate-600);
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.18s var(--am-ease);
    }
    [data-theme="dark"] .btn-ghost-mini { background: var(--bs-body-bg); }
    .btn-ghost-mini:hover { border-color: var(--am-border-strong); color: var(--am-slate-900); }

    .info-card {
        border-radius: 0.85rem;
        padding: 1rem 1rem;
        background: var(--am-slate-50);
        border: 1px solid var(--am-border-alpha);
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    [data-theme="dark"] .info-card {
        background: rgba(15,23,42,0.4);
    }
    .info-card h6 {
        font-size: 0.8rem;
        font-weight: 750;
        letter-spacing: -0.01em;
        margin: 0;
        color: var(--am-slate-900);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    [data-theme="dark"] .info-card h6 { color: #f8fafc; }
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .info-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.76rem;
        color: var(--am-slate-600);
        line-height: 1.45;
    }
    [data-theme="dark"] .info-list li { color: #cbd5e1; }
    .info-list i {
        color: var(--am-blue);
        margin-top: 0.1rem;
        font-size: 0.68rem;
        flex-shrink: 0;
    }
    .kv-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.4rem 0.6rem;
        border-radius: 0.45rem;
        background: white;
        border: 1px solid var(--am-border-alpha);
        font-size: 0.78rem;
    }
    [data-theme="dark"] .kv-row { background: rgba(15,23,42,0.35); }
    .kv-row span:first-child { color: var(--am-slate-500); font-weight: 600; }
    .kv-row span:last-child  { color: var(--am-slate-900); font-weight: 700; }
    [data-theme="dark"] .kv-row span:last-child { color: #f8fafc; }

    .alert-soft {
        padding: 0.75rem 0.9rem;
        border-radius: 0.7rem;
        border: 1px solid var(--am-red-100);
        background: var(--am-red-50);
        color: #b91c1c;
        font-size: 0.8rem;
        line-height: 1.5;
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        margin-bottom: 1rem;
    }
    [data-theme="dark"] .alert-soft { background: rgba(255,82,99,0.1); color: #fca5a5; }
    .alert-soft i { margin-top: 0.12rem; flex-shrink: 0; }

    /* Student Count Panel */
    .count-stage {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 1.1rem;
    }
    @media (max-width: 900px) { .count-stage { grid-template-columns: 1fr; } }
    .counter-card {
        padding: 1.3rem 1.3rem 1.1rem 1.3rem;
        border-radius: 0.9rem;
        background:
            radial-gradient(600px 220px at 100% -20%, var(--am-purple-50), transparent 60%),
            var(--bs-body-bg);
        border: 1px solid var(--am-border-alpha);
    }
    .counter-widget {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        margin: 1.1rem 0 0.9rem 0;
    }
    .counter-btn {
        width: 52px; height: 52px;
        border-radius: 14px;
        border: 1.5px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        color: var(--am-slate-700);
        font-size: 1.3rem;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.18s var(--am-ease);
    }
    [data-theme="dark"] .counter-btn { color: #e2e8f0; }
    .counter-btn:hover {
        border-color: var(--am-blue);
        color: var(--am-blue);
        transform: translateY(-1px);
        box-shadow: var(--am-shadow-md);
    }
    .counter-btn:active { transform: translateY(0); }
    .counter-display {
        flex: 1;
        max-width: 240px;
        position: relative;
    }
    .counter-input {
        width: 100%;
        text-align: center;
        padding: 1rem 0.5rem;
        border-radius: 1rem;
        border: 2px solid var(--am-border-alpha);
        background: white;
        font-size: 2.6rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        color: var(--am-slate-900);
        font-variant-numeric: tabular-nums;
        transition: all 0.2s var(--am-ease);
    }
    [data-theme="dark"] .counter-input { background: rgba(15,23,42,0.35); color: #f8fafc; }
    .counter-input:focus {
        outline: none;
        border-color: var(--am-purple);
        box-shadow: 0 0 0 4px var(--am-purple-50);
    }
    .progress-card {
        border-radius: 0.9rem;
        padding: 1rem 1.05rem;
        background: white;
        border: 1px solid var(--am-border-alpha);
    }
    [data-theme="dark"] .progress-card { background: rgba(15,23,42,0.35); }
    .progress-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.35rem;
        font-size: 0.8rem;
    }
    .progress-row:last-of-type { margin-bottom: 0.65rem; }
    .progress-row span:first-child { color: var(--am-slate-500); font-weight: 600; }
    .progress-row span:last-child  { font-weight: 750; color: var(--am-slate-900); }
    [data-theme="dark"] .progress-row span:last-child { color: #f8fafc; }
    .progress-bar-am {
        height: 10px;
        border-radius: 999px;
        background: var(--am-slate-100);
        overflow: hidden;
    }
    [data-theme="dark"] .progress-bar-am { background: rgba(148,163,184,0.15); }
    .progress-fill-am {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #2f78ff 0%, #a855f7 70%, #ec4899 100%);
        transition: width 0.35s var(--am-ease);
        box-shadow: 0 0 0 1px rgba(255,255,255,0.2) inset;
    }
    .rate-chip {
        display: inline-flex;
        align-items: baseline;
        gap: 0.15rem;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-weight: 800;
        font-size: 1.15rem;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.03em;
        color: var(--am-blue);
        background: var(--am-blue-50);
    }

    /* Summary session card */
    .summary-card {
        padding: 1.2rem 1.15rem;
        border-radius: 0.9rem;
        background:
            radial-gradient(500px 180px at 0% -30%, var(--am-blue-50), transparent 55%),
            var(--bs-body-bg);
        border: 1px solid var(--am-border-alpha);
        height: 100%;
    }
    .summary-title {
        font-size: 0.82rem;
        font-weight: 750;
        letter-spacing: -0.01em;
        margin: 0 0 0.8rem 0;
        color: var(--am-slate-900);
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }
    [data-theme="dark"] .summary-title { color: #f8fafc; }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.55rem 0.2rem;
        border-bottom: 1px dashed var(--am-border-alpha);
        font-size: 0.82rem;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-row span:first-child { color: var(--am-slate-500); font-weight: 600; }
    .summary-row span:last-child  { color: var(--am-slate-900); font-weight: 700; text-align: right; max-width: 58%; word-break: break-word; }
    [data-theme="dark"] .summary-row span:last-child { color: #f8fafc; }

    /* Completion Panel */
    .complete-stage {
        text-align: center;
        padding: 1.5rem 1rem 0.5rem 1rem;
    }
    .success-orbit {
        position: relative;
        width: 84px; height: 84px;
        margin: 0 auto 1.2rem auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .success-orbit::before,
    .success-orbit::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        inset: 0;
    }
    .success-orbit::before {
        background: rgba(0,208,132,0.15);
        animation: pulseRing 2.5s ease-out infinite;
    }
    .success-orbit::after {
        background: linear-gradient(135deg, rgba(0,208,132,0.3), rgba(0,184,148,0.1));
        inset: 6px;
    }
    @keyframes pulseRing {
        0%   { transform: scale(0.9); opacity: 0.9; }
        70%  { transform: scale(1.35); opacity: 0; }
        100% { transform: scale(1.35); opacity: 0; }
    }
    .success-core {
        width: 58px; height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00d084, #00b894);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        position: relative;
        z-index: 1;
        box-shadow: 0 10px 24px -8px rgba(0,208,132,0.6), 0 0 0 4px rgba(0,208,132,0.08);
    }
    .complete-title {
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin: 0 0 0.25rem 0;
        color: var(--am-slate-900);
    }
    [data-theme="dark"] .complete-title { color: #f8fafc; }
    .complete-sub {
        color: var(--am-slate-500);
        font-size: 0.9rem;
        margin: 0 0 1.5rem 0;
    }

    .dual-ledgers {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        max-width: 720px;
        margin: 0 auto 1.5rem auto;
    }
    @media (max-width: 680px) { .dual-ledgers { grid-template-columns: 1fr; } }
    .ledger-card {
        padding: 1rem 1.05rem;
        border-radius: 0.85rem;
        border: 1px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        text-align: left;
        transition: all 0.22s var(--am-ease);
    }
    .ledger-card:hover { transform: translateY(-1.5px); box-shadow: var(--am-shadow-md); border-color: var(--am-border-strong); }
    .ledger-head {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 0.4rem;
    }
    .ledger-icon {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem;
    }
    .ledger-card:nth-child(1) .ledger-icon { background: var(--am-blue-50); color: var(--am-blue); box-shadow: 0 0 0 4px var(--am-blue-50); }
    .ledger-card:nth-child(2) .ledger-icon { background: var(--am-emerald-50); color: var(--am-emerald); box-shadow: 0 0 0 4px var(--am-emerald-50); }
    .ledger-title {
        font-size: 0.85rem;
        font-weight: 750;
        margin: 0 0 0.05rem 0;
        color: var(--am-slate-900);
    }
    [data-theme="dark"] .ledger-title { color: #f8fafc; }
    .ledger-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.14rem 0.5rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 750;
        background: var(--am-emerald-50);
        color: var(--am-emerald);
    }
    .ledger-desc {
        color: var(--am-slate-500);
        font-size: 0.75rem;
        margin: 0;
        line-height: 1.45;
    }

    .audit-card {
        max-width: 720px;
        margin: 0 auto 1.5rem auto;
        padding: 1.15rem 1.2rem;
        border-radius: 0.9rem;
        background: var(--am-slate-50);
        border: 1px solid var(--am-border-alpha);
        text-align: left;
    }
    [data-theme="dark"] .audit-card {
        background: rgba(15,23,42,0.4);
    }
    .audit-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.6rem 1rem;
    }
    @media (max-width: 520px) { .audit-grid { grid-template-columns: 1fr; } }
    .audit-cell {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }
    .audit-cell .k { font-size: 0.7rem; font-weight: 650; color: var(--am-slate-500); }
    .audit-cell .v { font-size: 0.82rem; font-weight: 750; color: var(--am-slate-900); }
    [data-theme="dark"] .audit-cell .v { color: #f8fafc; }

    .cta-bar-bottom {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }
    .btn-outline-premium {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.62rem 1rem;
        border-radius: 0.7rem;
        border: 1.5px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        color: var(--am-slate-700);
        font-weight: 650;
        font-size: 0.85rem;
        transition: all 0.18s var(--am-ease);
    }
    [data-theme="dark"] .btn-outline-premium { color: #e2e8f0; }
    .btn-outline-premium:hover {
        border-color: var(--am-border-strong);
        transform: translateY(-1px);
        box-shadow: var(--am-shadow-sm);
        text-decoration: none;
        color: var(--am-slate-900);
    }
    .btn-outline-premium.emerald:hover { color: var(--am-emerald); border-color: var(--am-emerald); }
    .btn-outline-premium.info:hover { color: var(--am-blue); border-color: var(--am-blue); }

    /* Recent Logs Table */
    .logs-card {
        border-radius: 1rem;
        border: 1px solid var(--am-border-alpha);
        background: var(--bs-body-bg);
        box-shadow: var(--am-shadow-sm);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .logs-head {
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--am-border-alpha);
    }
    .logs-title {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        font-size: 0.92rem;
        font-weight: 750;
        letter-spacing: -0.01em;
        color: var(--am-slate-900);
        margin: 0;
    }
    [data-theme="dark"] .logs-title { color: #f8fafc; }
    .logs-title i { color: var(--am-blue); }
    .count-chip {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: var(--am-blue-50);
        color: var(--am-blue);
    }
    .logs-table-wrap { padding: 0 0.5rem 0.5rem 0.5rem; }
    .premium-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.8rem;
    }
    .premium-table thead th {
        text-align: left;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--am-slate-500);
        padding: 0.8rem 0.9rem 0.55rem 0.9rem;
        border-bottom: 1px solid var(--am-border-alpha);
        background: transparent;
    }
    .premium-table thead th.text-end { text-align: right; }
    .premium-table tbody td {
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid var(--am-border-alpha);
        color: var(--am-slate-700);
        vertical-align: middle;
    }
    [data-theme="dark"] .premium-table tbody td { color: #cbd5e1; }
    .premium-table tbody tr:last-child td { border-bottom: none; }
    .premium-table tbody tr {
        transition: background 0.15s var(--am-ease);
    }
    .premium-table tbody tr:hover {
        background: linear-gradient(90deg, var(--am-blue-50), transparent 70%);
    }
    .cell-record {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-weight: 700;
        color: var(--am-slate-600);
        font-size: 0.72rem;
    }
    [data-theme="dark"] .cell-record { color: #94a3b8; }
    .cell-faculty {
        font-weight: 750;
        color: var(--am-slate-900);
    }
    [data-theme="dark"] .cell-faculty { color: #f8fafc; }
    .cell-subject { color: var(--am-slate-500); font-size: 0.76rem; }
    .badge-am {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.22rem 0.55rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .badge-am.present { background: var(--am-emerald-50); color: var(--am-emerald); border: 1px solid var(--am-emerald-100); }
    .badge-am.absent  { background: var(--am-red-50);    color: var(--am-red);    border: 1px solid var(--am-red-100); }

    .rate-cell {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.6rem;
    }
    .rate-pct {
        font-weight: 800;
        color: var(--am-blue);
        font-size: 0.85rem;
    }
    .sig-indicator {
        display: inline-flex;
        gap: 0.2rem;
    }
    .sig-dot {
        width: 9px; height: 9px;
        border-radius: 50%;
        background: var(--am-slate-200);
        display: inline-block;
    }
    [data-theme="dark"] .sig-dot { background: rgba(148,163,184,0.18); }
    .sig-dot.done-prof  { background: var(--am-emerald); box-shadow: 0 0 0 2px var(--am-emerald-50); }
    .sig-dot.done-mayor { background: var(--am-orange);  box-shadow: 0 0 0 2px var(--am-orange-50); }

    .empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--am-slate-500);
        font-size: 0.85rem;
    }
    .empty-state i { opacity: 0.7; margin-right: 0.3rem; }

    /* Badge Status Tokens (kept for backward compat) */
    .badge-status-present { background-color: var(--am-emerald-50) !important; color: var(--am-emerald) !important; border: 1px solid var(--am-emerald-100) !important; }
    .badge-status-absent  { background-color: var(--am-red-50) !important;     color: var(--am-red) !important;     border: 1px solid var(--am-red-100) !important; }

    /* Backward compat: original .swimlane classes still referenced in JS */
    .swimlane { border-radius: 0.85rem; padding: 1rem; border: 1px solid var(--am-border-alpha); }
    .swimlane-header { display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px dashed var(--am-border-alpha); }
    .swimlane-lane { background: var(--am-blue-50); border-left: 3px solid var(--am-blue); }
    .swimlane-lane-alt { background: var(--am-emerald-50); border-left: 3px solid var(--am-emerald); }
    .lane-step { padding: 0.75rem 1rem; border-radius: 0.6rem; margin-bottom: 0.5rem; background: var(--bs-body-bg); border: 1px solid var(--am-border-alpha); transition: all 0.25s var(--am-ease); opacity: 0.4; }
    .lane-step.active { opacity:1; transform: translateY(-1px); box-shadow: var(--am-shadow-md); border-color: var(--am-blue); }
    .lane-step.completed { opacity: 0.85; border-color: var(--am-emerald); background: var(--am-emerald-50); }

    /* ── Enhanced accents & motion ──────────────────────── */
    .node-card[data-accent="blue"].active::before,
    .node-card[data-accent="blue"].done::before { background: var(--am-blue); }
    .hero-dot { animation: amPulse 2s ease-in-out infinite; }
    @keyframes amPulse {
        0%, 100% { box-shadow: 0 0 0 3px var(--am-blue-50); }
        50%      { box-shadow: 0 0 0 6px rgba(47,120,255,0.18); }
    }
    @keyframes amFadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .workflow-panel:not(.hidden-panel) { animation: amFadeUp 0.38s var(--am-ease) both; }
    .stat-premium { backdrop-filter: blur(8px); }
    .stat-ring {
        position: absolute;
        right: 0.85rem;
        bottom: 0.85rem;
        width: 44px;
        height: 44px;
        opacity: 0.35;
    }
    .glow-blue, .glow-emerald {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .glow-blue    { color: var(--am-blue);    background: var(--am-blue-50);    box-shadow: 0 0 0 4px var(--am-blue-50); }
    .glow-emerald { color: var(--am-emerald); background: var(--am-emerald-50); box-shadow: 0 0 0 4px var(--am-emerald-50); }
    .btn-branch {
        border: none !important;
        padding: 0 !important;
        background: transparent !important;
    }
    .btn-branch .branch-card { width: 100%; }
    .swimlane-outer { backdrop-filter: blur(12px); }
    .stage-card { backdrop-filter: blur(10px); }
    .logs-card { backdrop-filter: blur(10px); height: 100%; display: flex; flex-direction: column; }
    .logs-table-wrap { flex: 1; overflow: auto; }
    .stage-card .workflow-panel.hidden-panel { display: none !important; }
    .am-modal .modal-content {
        border-radius: 1rem;
        border: 1px solid var(--am-border-alpha);
        box-shadow: var(--am-shadow-xl);
        overflow: hidden;
    }
    .am-modal .modal-header {
        padding: 1.15rem 1.35rem;
        border-bottom: 1px solid var(--am-border-alpha);
        background: linear-gradient(180deg, var(--am-blue-50), transparent);
    }
    .am-modal .modal-title {
        font-weight: 750;
        letter-spacing: -0.02em;
        font-size: 1rem;
    }
    .am-modal .nav-tabs {
        border-bottom: 1px solid var(--am-border-alpha);
        padding: 0 1.25rem;
        gap: 0.25rem;
    }
    .am-modal .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: var(--am-slate-500);
        font-weight: 650;
        font-size: 0.82rem;
        padding: 0.75rem 1rem;
        border-radius: 0;
    }
    .am-modal .nav-tabs .nav-link.active {
        color: var(--am-blue);
        border-bottom-color: var(--am-blue);
        background: transparent;
    }
    .am-modal .modal-body { padding: 0; }
    .am-modal .tab-pane { padding: 0.75rem 1rem 1rem; }
    .hero-actions-stack { display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem; }
    .hero-live-clock {
        font-size: 0.72rem;
        font-weight: 650;
        color: var(--am-slate-500);
        font-variant-numeric: tabular-nums;
    }
    @media (max-width: 768px) {
        .hero-actions-stack { align-items: stretch; width: 100%; }
        .page-hero { padding: 1.25rem; }
        .hero-title { font-size: 1.45rem; }
    }
</style>

<div class="am-console">
<div class="page-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index:1;">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="hero-pill">
                    <span class="hero-dot"></span>
                    <span>Live Monitoring Console</span>
                </span>
                <span class="hero-pill" style="background:var(--am-purple-50);color:var(--am-purple);border-color:var(--am-purple-100);">
                    <i class="fas fa-calendar-day" style="font-size:0.65rem;"></i>
                    <?= date('l, F j, Y') ?>
                </span>
            </div>
            <h1 class="hero-title">
                <i class="fas fa-chalkboard-teacher me-2" style="background: linear-gradient(135deg, var(--am-blue), var(--am-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                Daily Attendance Log
            </h1>
            <p class="hero-sub">End-to-end room check workflow with dual-role validation — Monitoring Officer, Professor, and Mayor of the Class.</p>
        </div>
        <div class="hero-actions-stack">
            <span class="hero-live-clock" id="liveClock"><?= date('h:i:s A') ?></span>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="button" id="btnViewRecords" class="btn btn-hero-ghost" data-bs-toggle="modal" data-bs-target="#recordsModal">
                    <i class="fas fa-table me-1"></i>All Records
                </button>
                <button type="button" id="btnResetWorkflow" class="btn btn-hero-ghost d-none">
                    <i class="fas fa-rotate-left me-1"></i>Reset Session
                </button>
                <button type="button" id="btnNewRoomCheck" class="btn btn-hero-primary">
                    <i class="fas fa-bolt me-2"></i>Start Room Check
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     PREMIUM STAT CARDS (with sparkline accents)
══════════════════════════════════════════════════════════════ -->
<?php
// Heuristic trends + sparkline data for premium visuals
$records = $_SESSION['attendance_records'] ?? [];
$rateHistory = [];
$last7 = array_slice($records, -7);
foreach ($last7 as $r) $rateHistory[] = $r['attendance_rate'];
while (count($rateHistory) < 7) array_unshift($rateHistory, null);

function toSparkline($arr, $color, $accent50) {
    $w = 120; $h = 32; $pad = 2;
    $pts = array_filter($arr, fn($v)=>$v!==null);
    if (empty($pts)) return '';
    $min = min(0, min($pts));
    $max = max(100, max($pts));
    $range = $max - $min ?: 1;
    $step = ($w - 2*$pad) / (count($arr)-1);
    $path = ''; $area = '';
    $idx = 0;
    foreach ($arr as $v) {
        $x = $pad + $idx * $step;
        $y = $h - $pad - (($v ?? $min) - $min) / $range * ($h - 2*$pad);
        $sep = $idx === 0 ? 'M' : 'L';
        if ($v === null) { $idx++; continue; }
        $path .= "$sep $x $y ";
        if ($idx === 0) $area .= "M $x " . ($h - $pad) . " L $x $y ";
        else $area .= "L $x $y ";
        $idx++;
    }
    $areaLastX = $pad + ($idx-1) * $step;
    $area .= "L $areaLastX " . ($h - $pad) . " Z";
    $lastVal = end($pts);
    return "<svg class=\"sparkline spark-svg\" viewBox=\"0 0 $w $h\" preserveAspectRatio=\"none\">
        <defs><linearGradient id=\"spg_{$color}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\">
            <stop offset=\"0%\" stop-color=\"$color\" stop-opacity=\"0.28\"/>
            <stop offset=\"100%\" stop-color=\"$color\" stop-opacity=\"0\"/>
        </linearGradient></defs>
        <path d=\"$area\" fill=\"url(#spg_{$color})\"/>
        <path d=\"$path\" fill=\"none\" stroke=\"$color\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>
        <circle cx=\"$areaLastX\" cy=\"".($h - $pad - ($lastVal - $min)/$range*($h-2*$pad))."\" r=\"2.2\" fill=\"$color\"/>
    </svg>";
}

// Compute heuristic deltas (present vs absent ratio)
$presentRate = $totalRecords > 0 ? round($presentFaculty / $totalRecords * 100) : 0;
$delta_total   = $totalRecords >= 1 ? '+' . $totalRecords : '0';
$delta_present = $presentRate;
$delta_absent  = $absentFaculty;
$delta_attend  = $overallAttendance;
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-premium" data-accent="blue">
            <div class="stat-top">
                <div style="min-width:0; flex:1;">
                    <p class="stat-label">Room Checks</p>
                    <p class="stat-value tabnum"><span id="statTotal"><?= $totalRecords ?></span></p>
                    <div class="stat-meta">
                        <span class="stat-sub">Today's sessions</span>
                        <span class="stat-trend stat-trend-up"><i class="fas fa-arrow-up" style="font-size:0.65rem;"></i> <?= $delta_total ?></span>
                    </div>
                </div>
                <div class="stat-icon-wrap"><i class="fas fa-clipboard-check"></i></div>
            </div>
            <?= toSparkline(array_map(fn($_)=>rand(20,100), range(1,7)), '#2f78ff', 'var(--am-blue-50)') ?>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-premium" data-accent="emerald">
            <div class="stat-top">
                <div style="min-width:0; flex:1;">
                    <p class="stat-label">Present Faculty</p>
                    <p class="stat-value tabnum"><span id="statPresent"><?= $presentFaculty ?></span></p>
                    <div class="stat-meta">
                        <span class="stat-sub">Presence rate</span>
                        <span class="stat-trend stat-trend-up"><i class="fas fa-arrow-up" style="font-size:0.65rem;"></i> <?= $presentRate ?>%</span>
                    </div>
                </div>
                <div class="stat-icon-wrap"><i class="fas fa-user-check"></i></div>
            </div>
            <?= toSparkline(array_map(fn($_)=>rand(60,100), range(1,7)), '#00d084', 'var(--am-emerald-50)') ?>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-premium" data-accent="orange">
            <div class="stat-top">
                <div style="min-width:0; flex:1;">
                    <p class="stat-label">Absent Faculty</p>
                    <p class="stat-value tabnum"><span id="statAbsent"><?= $absentFaculty ?></span></p>
                    <div class="stat-meta">
                        <span class="stat-sub">Unattended slots</span>
                        <span class="stat-trend stat-trend-flat"><i class="fas fa-minus" style="font-size:0.65rem;"></i> <?= $totalRecords > 0 ? round(100-$presentRate).'%' : '0%' ?></span>
                    </div>
                </div>
                <div class="stat-icon-wrap"><i class="fas fa-user-times"></i></div>
            </div>
            <?= toSparkline(array_map(fn($_)=>rand(0,35), range(1,7)), '#ff9800', 'var(--am-orange-50)') ?>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-premium" data-accent="purple">
            <div class="stat-top">
                <div style="min-width:0; flex:1;">
                    <p class="stat-label">Student Attendance</p>
                    <p class="stat-value tabnum"><span id="statRate"><?= $overallAttendance ?>%</span></p>
                    <div class="stat-meta">
                        <span class="stat-sub">Across <?= $totalExpected ?> enrollees</span>
                        <span class="stat-trend <?= $overallAttendance >= 85 ? 'stat-trend-up' : ($overallAttendance >= 70 ? 'stat-trend-flat' : 'stat-trend-down') ?>">
                            <i class="fas fa-arrow-<?= $overallAttendance >= 85 ? 'up' : ($overallAttendance >= 70 ? 'right' : 'down') ?>" style="font-size:0.65rem;"></i> <?= $totalStudents ?> present
                        </span>
                    </div>
                </div>
                <div class="stat-icon-wrap"><i class="fas fa-users-line"></i></div>
            </div>
            <?= toSparkline($rateHistory, '#a855f7', 'var(--am-purple-50)') ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     WORKFLOW STATE STEPPER + 2-LANE SWIMLANE
══════════════════════════════════════════════════════════════ -->
<div class="swimlane-outer">
    <div class="swimlane-head d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <p class="stat-label mb-1" style="margin:0;">Workflow Map</p>
            <p class="swim-col-title" style="font-size:0.95rem;color:var(--am-slate-700);margin:0;">Dual-lane attendance monitoring cycle</p>
        </div>
        <span class="session-chip"><i class="fas fa-diagram-project"></i> State-driven · Session persisted</span>
    </div>
    <div class="stepper-wrap">
        <div class="stepper-track" id="stepperTrack"></div>
    </div>

    <div class="swimlane-body">
        <div class="row g-3">
            <!-- LANE 1: Monitoring Officer -->
            <div class="col-md-6 d-flex">
                <div class="swim-col monitoring w-100">
                    <div class="swim-col-head">
                        <div class="swim-col-icon"><i class="fas fa-user-shield"></i></div>
                        <p class="swim-col-title">Lane 1 &middot; Monitoring Officer</p>
                    </div>
                    <div class="node-card" data-step-lane="START_ROOM_CHECK" data-accent="blue">
                        <p class="node-title"><i class="fas fa-door-open" style="color:var(--am-blue);"></i>1. Initiate Room Check</p>
                        <p class="node-desc">Select faculty, room, schedule &mdash; begin monitoring session</p>
                    </div>
                    <div class="node-card" data-step-lane="PRESENCE_CHECK" data-accent="blue">
                        <p class="node-title"><i class="fas fa-question-circle" style="color:var(--am-blue);"></i>2. Presence Check (Decision)</p>
                        <p class="node-desc">Is the Professor physically present during this slot?</p>
                    </div>
                </div>
            </div>

            <!-- LANE 2: Professor / Mayor of the Class -->
            <div class="col-md-6 d-flex">
                <div class="swim-col professor w-100">
                    <div class="swim-col-head">
                        <div class="swim-col-icon"><i class="fas fa-user-graduate"></i></div>
                        <p class="swim-col-title">Lane 2 &middot; Professor / Mayor</p>
                    </div>

                    <div class="branch-split mb-2">
                        <div>
                            <div class="branch-col-label-wrap">
                                <span class="branch-col-label present"><i class="fas fa-check me-1"></i>BRANCH A · PRESENT</span>
                            </div>
                            <div class="node-card" data-step-lane="PROF_SIGNATURE" data-accent="emerald">
                                <p class="node-title" style="font-size:0.78rem;"><i class="fas fa-file-signature" style="color:var(--am-emerald);"></i>A1. Professor Signs</p>
                                <p class="node-desc">Digital canvas signature validation</p>
                            </div>
                        </div>
                        <div>
                            <div class="branch-col-label-wrap">
                                <span class="branch-col-label absent"><i class="fas fa-times me-1"></i>BRANCH B · ABSENT</span>
                            </div>
                            <div class="node-card" data-step-lane="MARK_ABSENT" data-accent="red">
                                <p class="node-title" style="font-size:0.78rem;"><i class="fas fa-user-slash" style="color:var(--am-red);"></i>B1. Mark Absent</p>
                                <p class="node-desc">Flag professor absence in performance log</p>
                            </div>
                            <div class="node-card" data-step-lane="MAYOR_SIGNATURE" data-accent="orange">
                                <p class="node-title" style="font-size:0.78rem;"><i class="fas fa-file-signature" style="color:var(--am-orange);"></i>B2. Mayor Signs</p>
                                <p class="node-desc">Class Mayor validates via signature</p>
                            </div>
                        </div>
                    </div>

                    <div class="rejoin-node">
                        <span class="rejoin-tag">⇵ REJOIN</span>
                    </div>
                    <div class="node-card mt-2" data-step-lane="STUDENT_COUNT" data-accent="purple">
                        <p class="node-title"><i class="fas fa-users" style="color:var(--am-purple);"></i>3. Record Student Attendance</p>
                        <p class="node-desc">Input headcount of present students for this session</p>
                    </div>
                    <div class="node-card" data-step-lane="COMPLETE" data-accent="emerald">
                        <p class="node-title"><i class="fas fa-check-double" style="color:var(--am-emerald);"></i>4. Persist &amp; Complete</p>
                        <p class="node-desc">Logs to Prof Performance &middot; Saves to Attendance Records</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     WORKFLOW + RECENT LOGS — SPLIT PANEL LAYOUT
══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4 align-items-stretch">
    <!-- LEFT: Interactive Workflow Stage -->
    <div class="col-lg-7 d-flex flex-column">
        <div class="stage-card flex-grow-1 position-relative" id="workflowStage">

        <!-- PANEL: IDLE → Start Room Check -->
        <div class="workflow-panel" data-panel="IDLE">
            <div class="panel-head">
                <h2 class="panel-title">
                    <span class="panel-title-icon"><i class="fas fa-door-open"></i></span>
                    Initiate Room Check
                </h2>
                <span class="session-chip"><i class="fas fa-shield-halved"></i> Step 1 of 5</span>
            </div>
            <p class="panel-desc">Enter schedule details to open a new monitoring session. Officer identity is captured for audit compliance.</p>
            <form id="startRoomCheckForm">
                <div class="form-grid-3">
                    <div class="fg-col-6">
                        <label class="premium-label" for="form_faculty">Faculty / Professor</label>
                        <select id="form_faculty" class="premium-select" required>
                            <option value="" disabled selected>Select instructor...</option>
                            <option value="Dr. Earl Salvame">Dr. Earl Salvame (Information Technology)</option>
                            <option value="Prof. Juan Dela Cruz">Prof. Juan Dela Cruz (Information Technology)</option>
                            <option value="Dr. Aj">Dr. Aj (Teacher Education)</option>
                            <option value="Dr. Maria Santos">Dr. Maria Santos (College of Education)</option>
                            <option value="Prof. Luis Tan">Prof. Luis Tan (Business Administration)</option>
                            <option value="Prof. Katherine Lim">Prof. Katherine Lim (Computer Studies)</option>
                        </select>
                    </div>
                    <div class="fg-col-3">
                        <label class="premium-label" for="form_room">Room</label>
                        <input type="text" id="form_room" class="premium-input" placeholder="e.g. Room 403-B" required>
                    </div>
                    <div class="fg-col-3">
                        <label class="premium-label" for="form_time">Time Slot</label>
                        <input type="text" id="form_time" class="premium-input" placeholder="e.g. 09:30 AM" value="<?= date('h:i A') ?>" required>
                    </div>
                    <div class="fg-col-4">
                        <label class="premium-label" for="form_subject">Subject / Course</label>
                        <input type="text" id="form_subject" class="premium-input" placeholder="e.g. SIA-201" required>
                    </div>
                    <div class="fg-col-2">
                        <label class="premium-label" for="form_expected">Expected</label>
                        <input type="number" id="form_expected" class="premium-input text-end tabnum" min="1" value="45" required>
                    </div>
                    <div class="fg-col-6">
                        <label class="premium-label" for="form_officer">Monitoring Officer</label>
                        <input type="text" id="form_officer" class="premium-input" placeholder="Your name (audit trail)" value="Secretary / Monitoring Staff" required>
                    </div>
                </div>
                <div class="form-cta-bar">
                    <span class="stat-sub"><i class="fas fa-arrow-right me-1"></i>Proceeds to Presence Check</span>
                    <button type="submit" class="btn btn-gradient-primary">
                        <i class="fas fa-play me-2"></i>Begin Monitoring Session
                    </button>
                </div>
            </form>
        </div>

        <!-- PANEL: PRESENCE_CHECK -->
        <div class="workflow-panel hidden-panel" data-panel="PRESENCE_CHECK">
            <div class="panel-head">
                <h2 class="panel-title">
                    <span class="panel-title-icon" style="background:var(--am-blue-50);color:var(--am-blue);"><i class="fas fa-question-circle"></i></span>
                    Presence Check
                </h2>
                <span class="session-chip"><i class="fas fa-fingerprint"></i> <span id="pc_session_id"></span></span>
            </div>
            <p class="panel-desc">Is <strong id="pc_faculty_name">Professor</strong> physically present for <strong id="pc_subject"></strong> at <strong id="pc_room"></strong> (<span id="pc_time"></span>)?</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <button type="button" class="btn btn-branch w-100" data-branch="PRESENT">
                        <div class="branch-card present">
                            <div class="branch-card-head">
                                <div class="branch-icon"><i class="fas fa-user-check"></i></div>
                                <div>
                                    <p class="branch-card-title">Professor is Present</p>
                                    <p class="branch-card-desc mb-0">Digital signature → student headcount → persist</p>
                                </div>
                            </div>
                            <span class="branch-flow-pill present"><i class="fas fa-route"></i> Flow A1 → A2 → Complete</span>
                        </div>
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-branch w-100" data-branch="ABSENT">
                        <div class="branch-card absent">
                            <div class="branch-card-head">
                                <div class="branch-icon"><i class="fas fa-user-times"></i></div>
                                <div>
                                    <p class="branch-card-title">Professor is Absent</p>
                                    <p class="branch-card-desc mb-0">Mark absent → Mayor signature → headcount</p>
                                </div>
                            </div>
                            <span class="branch-flow-pill absent"><i class="fas fa-route"></i> Flow B1 → B2 → B3 → Complete</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- PANEL: PROF_SIGNATURE -->
<div class="workflow-panel hidden-panel" data-panel="PROF_SIGNATURE">
    <div class="panel-head">
        <h2 class="panel-title">
            <span class="panel-title-icon" style="background:var(--am-emerald-50);color:var(--am-emerald);"><i class="fas fa-file-signature"></i></span>
            Professor Digital Signature
        </h2>
        <span class="session-chip status-chip-present"><i class="fas fa-user-check"></i> Branch A · Present</span>
    </div>
    <p class="panel-desc">Request the professor to sign on the canvas below. Signature is stored as PNG and attached to the permanent record.</p>
    <span class="d-none" id="ps_meta"></span>

    <div class="sig-layout">
        <!-- Main Signature Section (Takes 2/3 of space) -->
        <div class="sig-main-area">
            <div class="sig-canvas-outer">
                <div class="sig-toolbar">
                    <div class="sig-tool-left">
                        <input type="color" id="prof_sig_color" class="sig-color-dot active" value="#0d9488" title="Ink color">
                        <button type="button" id="prof_sig_clear" class="btn-ghost-mini"><i class="fas fa-eraser"></i> Clear</button>
                    </div>
                    <span class="stat-sub">Use mouse, stylus, or touch</span>
                </div>
                <div class="sig-canvas-wrap">
                    <canvas id="profSignatureCanvas" style="width:100%;height:100%;"></canvas>
                </div>
            </div>
            <div class="form-cta-bar mt-3">
                <span></span>
                <button type="button" id="prof_sig_save" class="btn btn-gradient-emerald">
                    <i class="fas fa-check me-2"></i>Attach Signature &amp; Continue
                </button>
            </div>
        </div>

        <!-- Session Details Sidebar (Takes 1/3 of space) -->
        <div class="info-card">
            <h6><i class="fas fa-circle-info" style="color:var(--am-blue);"></i> Session Details</h6>
            <div class="kv-row"><span>Professor</span><span id="ps_prof">—</span></div>
            <div class="kv-row"><span>Subject</span><span id="ps_subj">—</span></div>
            <ul class="info-list mt-1">
                <li><i class="fas fa-check"></i> Signature validates physical presence</li>
                <li><i class="fas fa-check"></i> Stored as base64 PNG in audit log</li>
                <li><i class="fas fa-check"></i> Required before student headcount</li>
            </ul>
        </div>
    </div>
</div>

        <!-- PANEL: MAYOR_SIGNATURE -->
        <div class="workflow-panel hidden-panel" data-panel="MAYOR_SIGNATURE">
            <div class="panel-head">
                <h2 class="panel-title">
                    <span class="panel-title-icon" style="background:var(--am-orange-50);color:var(--am-orange);"><i class="fas fa-file-signature"></i></span>
                    Mayor of the Class Signature
                </h2>
                <span class="session-chip status-chip-absent"><i class="fas fa-user-times"></i> Branch B · Absent</span>
            </div>
            <div class="alert-soft">
                <i class="fas fa-triangle-exclamation"></i>
                <div><strong>Absence logged.</strong> This will be recorded in Professor Performance. Obtain the Mayor's signature to validate the unattended session.</div>
            </div>
            <span class="d-none" id="ms_meta"></span>
            <div class="sig-layout">
                <div>
                    <div class="sig-canvas-outer" style="background:linear-gradient(135deg,var(--am-orange-50),var(--am-red-50));border-color:var(--am-orange-100);">
                        <div class="sig-toolbar">
                            <div class="sig-tool-left">
                                <input type="color" id="mayor_sig_color" class="sig-color-dot active" value="#ff9800" title="Ink color">
                                <button type="button" id="mayor_sig_clear" class="btn-ghost-mini"><i class="fas fa-eraser"></i> Clear</button>
                            </div>
                            <span class="stat-sub">Mayor confirms absence &amp; headcount</span>
                        </div>
                        <div class="sig-canvas-wrap">
                            <canvas id="mayorSignatureCanvas" style="width:100%;height:100%;"></canvas>
                        </div>
                    </div>
                    <div class="form-cta-bar mt-3">
                        <span></span>
                        <button type="button" id="mayor_sig_save" class="btn btn-gradient-orange">
                            <i class="fas fa-check me-2"></i>Attach Mayor Signature &amp; Continue
                        </button>
                    </div>
                </div>
                <div class="info-card">
                    <h6><i class="fas fa-user-graduate" style="color:var(--am-orange);"></i> Mayor Validation</h6>
                    <div class="kv-row"><span>Absent Prof.</span><span id="ms_prof">—</span></div>
                    <div class="kv-row"><span>Room / Time</span><span id="ms_rt">—</span></div>
                    <ul class="info-list mt-1">
                        <li><i class="fas fa-check"></i> Mayor confirms professor absence</li>
                        <li><i class="fas fa-check"></i> Takes responsibility for student count</li>
                        <li><i class="fas fa-check"></i> Provides audit trail validation</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- PANEL: STUDENT_COUNT -->
        <div class="workflow-panel hidden-panel" data-panel="STUDENT_COUNT">
            <div class="panel-head">
                <h2 class="panel-title">
                    <span class="panel-title-icon" style="background:var(--am-purple-50);color:var(--am-purple);"><i class="fas fa-users"></i></span>
                    Record Student Attendance
                </h2>
                <span class="session-chip status-chip-purple"><i class="fas fa-code-merge"></i> Rejoin · <span id="sc_via"></span></span>
            </div>
            <p class="panel-desc">Input the headcount of students physically present. This feeds attendance rate analytics and department dashboards.</p>
            <div class="count-stage">
                <div class="counter-card">
                    <label class="premium-label text-center d-block">Present Students</label>
                    <div class="counter-widget">
                        <button type="button" class="counter-btn" id="countMinus" aria-label="Decrease"><i class="fas fa-minus"></i></button>
                        <div class="counter-display">
                            <input type="number" id="studentCount" class="counter-input tabnum" min="0" value="0">
                        </div>
                        <button type="button" class="counter-btn" id="countPlus" aria-label="Increase"><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="progress-card mt-2">
                        <div class="progress-row"><span>Expected Enrollees</span><span class="tabnum" id="sc_expected">0</span></div>
                        <div class="progress-row"><span>Attendance Rate</span><span class="rate-chip tabnum" id="sc_rate">0%</span></div>
                        <div class="progress-bar-am"><div class="progress-fill-am" id="sc_progress" style="width:0%;"></div></div>
                    </div>
                    <div class="form-cta-bar">
                        <span></span>
                        <button type="button" id="saveStudentCountBtn" class="btn btn-gradient-purple">
                            <i class="fas fa-database me-2"></i>Finalize &amp; Persist Records
                        </button>
                    </div>
                </div>
                <div class="summary-card">
                    <p class="summary-title"><i class="fas fa-clipboard-list" style="color:var(--am-blue);"></i> Session Summary</p>
                    <div class="summary-row"><span>Professor</span><span id="sc_faculty">—</span></div>
                    <div class="summary-row"><span>Status</span><span id="sc_status">—</span></div>
                    <div class="summary-row"><span>Subject</span><span id="sc_subject">—</span></div>
                    <div class="summary-row"><span>Room</span><span id="sc_room">—</span></div>
                    <div class="summary-row"><span>Time</span><span id="sc_time">—</span></div>
                </div>
            </div>
        </div>

        <!-- PANEL: COMPLETE -->
        <div class="workflow-panel hidden-panel" data-panel="COMPLETE">
            <div class="complete-stage">
                <div class="success-orbit"><div class="success-core"><i class="fas fa-check-double"></i></div></div>
                <h2 class="complete-title">Session Complete</h2>
                <p class="complete-sub">All records persisted to attendance ledger and professor performance metrics.</p>
                <div class="dual-ledgers">
                    <div class="ledger-card">
                        <div class="ledger-head">
                            <div class="ledger-icon"><i class="fas fa-database"></i></div>
                            <div>
                                <p class="ledger-title">Attendance Records</p>
                                <span class="ledger-tag"><i class="fas fa-check"></i> SAVED</span>
                            </div>
                        </div>
                        <p class="ledger-desc">Full session log with digital signatures archived permanently.</p>
                    </div>
                    <div class="ledger-card">
                        <div class="ledger-head">
                            <div class="ledger-icon"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <p class="ledger-title">Professor Performance</p>
                                <span class="ledger-tag"><i class="fas fa-check"></i> UPDATED</span>
                            </div>
                        </div>
                        <p class="ledger-desc">Presence/absence integrated into monthly faculty metrics.</p>
                    </div>
                </div>
                <div class="audit-card">
                    <p class="summary-title mb-3"><i class="fas fa-shield-halved" style="color:var(--am-blue);"></i> Audit Snapshot</p>
                    <div class="audit-grid">
                        <div class="audit-cell"><span class="k">Record ID</span><span class="v tabnum" id="cp_record_id">—</span></div>
                        <div class="audit-cell"><span class="k">Date</span><span class="v" id="cp_date">—</span></div>
                        <div class="audit-cell"><span class="k">Faculty</span><span class="v" id="cp_faculty">—</span></div>
                        <div class="audit-cell"><span class="k">Status</span><span class="v" id="cp_status">—</span></div>
                        <div class="audit-cell"><span class="k">Present Students</span><span class="v tabnum" id="cp_present">—</span></div>
                        <div class="audit-cell"><span class="k">Attendance Rate</span><span class="v tabnum" id="cp_rate">—</span></div>
                        <div class="audit-cell" style="grid-column:1/-1;"><span class="k">Monitoring Officer</span><span class="v" id="cp_officer">—</span></div>
                    </div>
                </div>
                <div class="cta-bar-bottom">
                    <a href="<?= BASE_URL ?>/modules/faculty/users/secretary/index.php" class="btn btn-gradient-primary">
                        <i class="fas fa-house me-2"></i>Return to Dashboard
                    </a>
                    <button type="button" id="btnNextSession" class="btn btn-outline-premium emerald">
                        <i class="fas fa-plus-circle"></i> New Room Check
                    </button>
                    <button type="button" class="btn btn-outline-premium info" data-bs-toggle="modal" data-bs-target="#recordsModal">
                        <i class="fas fa-table"></i> View All Records
                    </button>
                </div>
            </div>
        </div>

        </div><!-- /stage-card -->
    </div>

    <!-- RIGHT: Recent Logs -->
    <div class="col-lg-5 d-flex flex-column">
        <div class="logs-card flex-grow-1">
            <div class="logs-head">
                <h3 class="logs-title"><i class="fas fa-clock-rotate-left"></i> Recent Sessions</h3>
                <span class="count-chip"><span id="logCount">0</span> records</span>
            </div>
            <div class="logs-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Faculty</th>
                            <th>Status</th>
                            <th>Room / Subject</th>
                            <th class="text-end">Rate</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i> No sessions yet — start a room check to begin.</div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div><!-- /am-console -->

<!-- Records Modal -->
<div class="modal fade am-modal" id="recordsModal" tabindex="-1" aria-labelledby="recordsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordsModalLabel"><i class="fas fa-table me-2" style="color:var(--am-blue);"></i>Attendance &amp; Performance Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAttendance" type="button">Attendance Logs</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPerformance" type="button">Professor Performance</button></li>
            </ul>
            <div class="modal-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabAttendance">
                        <div class="table-responsive">
                            <table class="premium-table">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Date</th><th>Faculty</th><th>Status</th>
                                        <th>Room</th><th>Subject</th><th>Students</th><th>Rate</th><th>Sigs</th>
                                    </tr>
                                </thead>
                                <tbody id="modalAttendanceBody">
                                    <tr><td colspan="9"><div class="empty-state">No records yet</div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tabPerformance">
                        <div class="table-responsive">
                            <table class="premium-table">
                                <thead>
                                    <tr>
                                        <th>Faculty</th><th>Period</th><th>Classes</th><th>Present</th>
                                        <th>Absent</th><th>Presence %</th><th>Students</th><th>Avg Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="modalPerfBody">
                                    <tr><td colspan="8"><div class="empty-state">No performance data yet</div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../../includes/layout-end.php'; ?>

<script>
/* ═══════════════════════════════════════════════════════════════════
   ATTENDANCE MONITORING — STATE MACHINE FRONTEND CONTROLLER
   ═══════════════════════════════════════════════════════════════════ */

(function() {
    'use strict';

    // ── State Definitions ─────────────────────────────────────────
    const STEPPER_CONFIG = [
        { key: 'START_ROOM_CHECK',  label: 'Start\nRoom Check', icon: 'fa-door-open' },
        { key: 'PRESENCE_CHECK',    label: 'Presence\nDecision', icon: 'fa-question-circle' },
        { key: 'SIGNATURE',         label: 'Digital\nSignature', icon: 'fa-file-signature' },
        { key: 'STUDENT_COUNT',     label: 'Student\nHeadcount', icon: 'fa-users' },
        { key: 'COMPLETE',          label: 'Persist\nRecords',   icon: 'fa-check-double' },
    ];

    // Map workflow states → stepper indices
    const STATE_TO_STEPPER = {
        'IDLE':            0,
        'START_ROOM_CHECK':0,
        'PRESENCE_CHECK':  1,
        'PROF_SIGNATURE':  2,
        'MAYOR_SIGNATURE': 2,
        'STUDENT_COUNT':   3,
        'COMPLETE':        4,
    };

    // ── DOM References ────────────────────────────────────────────
    const apiBase  = window.location.href; // POST to self (PHP state machine)
    const panels   = document.querySelectorAll('.workflow-panel');
    const stepper  = document.getElementById('stepperTrack');
    const btnNew   = document.getElementById('btnNewRoomCheck');
    const btnReset = document.getElementById('btnResetWorkflow');
    const stage    = document.getElementById('workflowStage');

    // Canvas refs
    let profCanvas, profCtx, mayorCanvas, mayorCtx;
    let drawing    = { isDrawing: false, prof: false, mayor: false };
    let sessionData = null;

    // ── Init ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        renderStepper();
        setupCanvases();
        bindEvents();
        refreshRecordsUI();
        transitionTo('IDLE', true);
        startLiveClock();
    }

    function startLiveClock() {
        const el = document.getElementById('liveClock');
        if (!el) return;
        setInterval(() => {
            el.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }, 1000);
    }

    // ── AJAX Helper ───────────────────────────────────────────────
    async function api(action, payload = {}) {
        const fd = new FormData();
        fd.append('action', action);
        Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
        const res = await fetch(apiBase, { method: 'POST', body: fd });
        return res.json();
    }

    // ── Stepper Rendering ─────────────────────────────────────────
    function renderStepper() {
        stepper.innerHTML = STEPPER_CONFIG.map((s, i) => `
            <div class="stepper-step" data-stepper="${s.key}" data-idx="${i}">
                <div class="stepper-circle"><i class="fas ${s.icon}"></i></div>
                <div class="stepper-label">${s.label.replace('\n', '<br>')}</div>
            </div>
        `).join('');
    }

    function updateStepper(state) {
        const idx = STATE_TO_STEPPER[state] ?? 0;
        document.querySelectorAll('.stepper-step').forEach((el, i) => {
            el.classList.remove('active', 'completed');
            if (i < idx)  el.classList.add('completed');
            if (i === idx) el.classList.add('active');
        });

        // Update swimlane node cards
        document.querySelectorAll('.node-card').forEach(el => el.classList.remove('active', 'done'));
        const activeKey = getLaneKeyForState(state);
        if (activeKey) {
            const el = document.querySelector(`.node-card[data-step-lane="${activeKey}"]`);
            if (el) el.classList.add('active');
        }
        const order = ['START_ROOM_CHECK', 'PRESENCE_CHECK', 'PROF_SIGNATURE', 'MARK_ABSENT', 'MAYOR_SIGNATURE', 'STUDENT_COUNT', 'COMPLETE'];
        const pos = order.indexOf(activeKey);
        order.forEach((k, i) => {
            if (i < pos) {
                const el = document.querySelector(`.node-card[data-step-lane="${k}"]`);
                if (el) el.classList.add('done');
            }
        });
    }

    function getLaneKeyForState(state) {
        // Signature states map to specific lanes
        if (state === 'PROF_SIGNATURE') return 'PROF_SIGNATURE';
        if (state === 'MAYOR_SIGNATURE') return 'MAYOR_SIGNATURE';
        return state;
    }

    // ── Panel Transitions ─────────────────────────────────────────
    function transitionTo(state, immediate = false) {
        updateStepper(state);
        const targetKey = (state === 'START_ROOM_CHECK' || state === 'IDLE') ? 'IDLE' : state;
        panels.forEach(panel => {
            const match = panel.getAttribute('data-panel') === targetKey;
            if (match) {
                panel.classList.remove('hidden-panel');
            } else {
                panel.classList.add('hidden-panel');
            }
        });

        // Toggle reset button visibility
        btnReset.classList.toggle('d-none', state === 'IDLE' || state === 'COMPLETE');

        // Scroll workflow into view on step change
        if (!immediate && stage) {
            stage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // ── Canvas Setup ──────────────────────────────────────────────
    function setupCanvases() {
        profCanvas = document.getElementById('profSignatureCanvas');
        mayorCanvas = document.getElementById('mayorSignatureCanvas');
        if (profCanvas)  profCtx  = profCanvas.getContext('2d');
        if (mayorCanvas) mayorCtx = mayorCanvas.getContext('2d');
        [profCanvas, mayorCanvas].forEach(c => c && sizeCanvas(c));
        window.addEventListener('resize', () => {
            [profCanvas, mayorCanvas].forEach(c => c && sizeCanvas(c));
        });
        bindCanvas(profCanvas,  'prof');
        bindCanvas(mayorCanvas, 'mayor');
    }

    function sizeCanvas(c) {
        const r = c.getBoundingClientRect();
        c.width  = r.width;
        c.height = r.height;
        const ctx = c.getContext('2d');
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        if (c === profCanvas) {
            ctx.strokeStyle = document.getElementById('prof_sig_color')?.value || '#0d9488';
        } else {
            ctx.strokeStyle = document.getElementById('mayor_sig_color')?.value || '#ff9800';
        }
    }

    function bindCanvas(c, which) {
        if (!c) return;
        const start = (e) => {
            e.preventDefault();
            drawing.isDrawing = true;
            drawing[which] = true;
            const ctx = c.getContext('2d');
            ctx.beginPath();
            const p = getPointer(e, c);
            ctx.moveTo(p.x, p.y);
        };
        const move = (e) => {
            if (!drawing.isDrawing || !drawing[which]) return;
            e.preventDefault();
            const ctx = c.getContext('2d');
            const p = getPointer(e, c);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        };
        const stop = () => { drawing.isDrawing = false; drawing[which] = false; };
        c.addEventListener('mousedown', start);
        c.addEventListener('mousemove', move);
        c.addEventListener('mouseup',   stop);
        c.addEventListener('mouseleave',stop);
        c.addEventListener('touchstart', start, { passive: false });
        c.addEventListener('touchmove',  move,  { passive: false });
        c.addEventListener('touchend',   stop);
    }

    function getPointer(e, c) {
        const r = c.getBoundingClientRect();
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: cx - r.left, y: cy - r.top };
    }

    function isCanvasBlank(canvas) {
        const ctx = canvas.getContext('2d');
        const d = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        for (let i = 0; i < d.length; i += 4) if (d[i+3] !== 0) return false;
        return true;
    }

    // ── Event Bindings ────────────────────────────────────────────
    function bindEvents() {
        // Start form
        document.getElementById('startRoomCheckForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                faculty_name:       document.getElementById('form_faculty').value,
                room:               document.getElementById('form_room').value,
                subject:            document.getElementById('form_subject').value,
                time_slot:          document.getElementById('form_time').value,
                expected_students:  document.getElementById('form_expected').value,
                monitoring_officer: document.getElementById('form_officer').value,
            };
            const res = await api('START_ROOM_CHECK', payload);
            if (res.ok) {
                sessionData = res.data;
                populatePresenceCheckUI(res.data);
                transitionTo('PRESENCE_CHECK');
            } else {
                alert(res.error || 'Failed to start session');
            }
        });

        // Top-level buttons
        btnNew.addEventListener('click', () => {
            api('RESET_WORKFLOW').then(() => {
                sessionData = null;
                document.getElementById('startRoomCheckForm').reset();
                transitionTo('IDLE');
                stage?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.getElementById('form_faculty')?.focus();
            });
        });
        btnReset.addEventListener('click', () => {
            if (confirm('Reset current workflow and discard unsaved data?')) {
                api('RESET_WORKFLOW').then(() => {
                    sessionData = null;
                    transitionTo('IDLE');
                });
            }
        });
        document.getElementById('btnNextSession').addEventListener('click', () => {
            api('RESET_WORKFLOW').then(() => {
                sessionData = null;
                document.getElementById('startRoomCheckForm').reset();
                transitionTo('IDLE');
            });
        });

        // Presence branch buttons
        document.querySelectorAll('[data-branch]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const branch = btn.getAttribute('data-branch');
                if (branch === 'PRESENT') {
                    const res = await api('MARK_PRESENT');
                    if (res.ok) {
                        sessionData = res.data;
                        populateProfSigUI(res.data);
                        sizeCanvas(profCanvas);
                        transitionTo('PROF_SIGNATURE');
                    }
                } else {
                    const res = await api('MARK_ABSENT');
                    if (res.ok) {
                        sessionData = res.data;
                        populateMayorSigUI(res.data);
                        sizeCanvas(mayorCanvas);
                        transitionTo('MAYOR_SIGNATURE');
                    }
                }
            });
        });

        // Prof signature color
        document.getElementById('prof_sig_color')?.addEventListener('input', (e) => {
            if (profCtx) profCtx.strokeStyle = e.target.value;
        });
        document.getElementById('prof_sig_clear')?.addEventListener('click', () => {
            if (profCtx) profCtx.clearRect(0, 0, profCanvas.width, profCanvas.height);
        });
        document.getElementById('prof_sig_save')?.addEventListener('click', async () => {
            if (isCanvasBlank(profCanvas)) {
                alert('Please draw the professor signature first.');
                return;
            }
            const dataUrl = profCanvas.toDataURL('image/png');
            const res = await api('SAVE_PROF_SIGNATURE', { signature_data: dataUrl });
            if (res.ok) {
                sessionData = res.data;
                populateStudentCountUI(res.data, 'Present Professor Signature');
                transitionTo('STUDENT_COUNT');
            } else alert(res.error);
        });

        // Mayor signature color
        document.getElementById('mayor_sig_color')?.addEventListener('input', (e) => {
            if (mayorCtx) mayorCtx.strokeStyle = e.target.value;
        });
        document.getElementById('mayor_sig_clear')?.addEventListener('click', () => {
            if (mayorCtx) mayorCtx.clearRect(0, 0, mayorCanvas.width, mayorCanvas.height);
        });
        document.getElementById('mayor_sig_save')?.addEventListener('click', async () => {
            if (isCanvasBlank(mayorCanvas)) {
                alert('Please draw the Mayor of the Class signature first.');
                return;
            }
            const dataUrl = mayorCanvas.toDataURL('image/png');
            const res = await api('SAVE_MAYOR_SIGNATURE', { signature_data: dataUrl });
            if (res.ok) {
                sessionData = res.data;
                populateStudentCountUI(res.data, 'Mayor Signature (Prof Absent)');
                transitionTo('STUDENT_COUNT');
            } else alert(res.error);
        });

        // Student count +/-
        const scInput = document.getElementById('studentCount');
        document.getElementById('countPlus')?.addEventListener('click', () => {
            scInput.value = Math.max(0, parseInt(scInput.value || '0') + 1);
            updateRateUI();
        });
        document.getElementById('countMinus')?.addEventListener('click', () => {
            scInput.value = Math.max(0, parseInt(scInput.value || '0') - 1);
            updateRateUI();
        });
        scInput?.addEventListener('input', updateRateUI);

        document.getElementById('saveStudentCountBtn')?.addEventListener('click', async () => {
            const n = parseInt(scInput.value);
            if (isNaN(n) || n < 0) { alert('Invalid student count'); return; }
            const res = await api('SAVE_STUDENT_COUNT', { present_students: n });
            if (res.ok) {
                populateCompletionUI(res.attendance_record, res.performance_snapshot);
                refreshRecordsUI();
                transitionTo('COMPLETE');
            } else alert(res.error);
        });

        // View records modal
        document.getElementById('recordsModal')?.addEventListener('show.bs.modal', populateRecordsModal);
    }

    // ── UI Population Helpers ─────────────────────────────────────
    function populatePresenceCheckUI(d) {
        document.getElementById('pc_session_id').textContent   = d.session_id;
        document.getElementById('pc_faculty_name').textContent = d.faculty_name;
        document.getElementById('pc_subject').textContent      = d.subject;
        document.getElementById('pc_room').textContent         = d.room;
        document.getElementById('pc_time').textContent         = d.time_slot;
    }
    function populateProfSigUI(d) {
        document.getElementById('ps_meta').textContent   = `${d.session_id} · ${d.date}`;
        document.getElementById('ps_prof').textContent   = d.faculty_name;
        document.getElementById('ps_subj').textContent   = `${d.subject} @ ${d.room} · ${d.time_slot}`;
        if (profCtx) profCtx.clearRect(0, 0, profCanvas.width, profCanvas.height);
    }
    function populateMayorSigUI(d) {
        document.getElementById('ms_meta').textContent   = `${d.session_id} · ${d.date}`;
        document.getElementById('ms_prof').textContent   = d.faculty_name;
        document.getElementById('ms_rt').textContent     = `${d.room} · ${d.time_slot}`;
        if (mayorCtx) mayorCtx.clearRect(0, 0, mayorCanvas.width, mayorCanvas.height);
    }
    function populateStudentCountUI(d, via) {
        document.getElementById('sc_via').textContent        = via;
        document.getElementById('sc_expected').textContent   = d.expected_students;
        document.getElementById('sc_faculty').textContent    = d.faculty_name;
        document.getElementById('sc_subject').textContent    = d.subject;
        document.getElementById('sc_room').textContent       = d.room;
        document.getElementById('sc_time').textContent       = d.time_slot;
        const statusEl = document.getElementById('sc_status');
        if (d.professor_status === 'Present') {
            statusEl.innerHTML = '<span class="badge-am present"><i class="fas fa-user-check"></i> Present</span>';
        } else {
            statusEl.innerHTML = '<span class="badge-am absent"><i class="fas fa-user-times"></i> Absent</span>';
        }
        const scInput = document.getElementById('studentCount');
        scInput.value = Math.max(0, Math.min(40, Math.floor(d.expected_students * 0.9)));
        updateRateUI();
    }
    function updateRateUI() {
        const exp = parseInt(document.getElementById('sc_expected')?.textContent || '0');
        const pres = parseInt(document.getElementById('studentCount')?.value || '0');
        const rate = exp > 0 ? Math.round((pres / exp) * 100) : 0;
        document.getElementById('sc_rate').textContent = rate + '%';
        const bar = document.getElementById('sc_progress');
        if (bar) bar.style.width = Math.min(100, rate) + '%';
    }
    function populateCompletionUI(rec, perf) {
        document.getElementById('cp_record_id').textContent = rec.record_id;
        document.getElementById('cp_date').textContent      = rec.date;
        document.getElementById('cp_faculty').textContent   = rec.faculty_name;
        const cpStatus = document.getElementById('cp_status');
        if (rec.professor_status === 'Present') {
            cpStatus.innerHTML = '<span class="badge-am present"><i class="fas fa-user-check"></i> Present</span>';
        } else {
            cpStatus.innerHTML = '<span class="badge-am absent"><i class="fas fa-user-times"></i> Absent</span>';
        }
        document.getElementById('cp_present').textContent = `${rec.present_students} / ${rec.expected_students}`;
        document.getElementById('cp_rate').textContent    = `${rec.attendance_rate}%`;
        document.getElementById('cp_officer').textContent = rec.monitoring_officer;
    }

    // ── Records UI ────────────────────────────────────────────────
    async function refreshRecordsUI() {
        const res = await api('GET_RECORDS');
        if (!res.ok) return;

        const recs = res.attendance_records || [];
        const perfs = res.professor_performance || [];

        // Stat cards
        let p = 0, a = 0, totalPres = 0, totalExp = 0;
        recs.forEach(r => {
            r.professor_status === 'Present' ? p++ : a++;
            totalPres += r.present_students;
            totalExp  += r.expected_students;
        });
        const rate = totalExp > 0 ? Math.round((totalPres / totalExp) * 100) : 0;
        document.getElementById('statTotal').textContent   = recs.length;
        document.getElementById('statPresent').textContent = p;
        document.getElementById('statAbsent').textContent  = a;
        document.getElementById('statRate').textContent    = rate + '%';

        // Logs table body
        const tbody = document.getElementById('logsTableBody');
        document.getElementById('logCount').textContent = recs.length;
        if (!recs.length) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i> No sessions yet — start a room check to begin.</div></td></tr>`;
        } else {
            tbody.innerHTML = recs.slice(-10).reverse().map(r => {
                const statusBadge = r.professor_status === 'Present'
                    ? `<span class="badge-am present"><i class="fas fa-user-check"></i> Present</span>`
                    : `<span class="badge-am absent"><i class="fas fa-user-times"></i> Absent</span>`;
                const sigDots = `<span class="sig-indicator" title="Signatures">
                    <span class="sig-dot ${r.professor_signature ? 'done-prof' : ''}"></span>
                    <span class="sig-dot ${r.mayor_signature ? 'done-mayor' : ''}"></span>
                </span>`;
                const shortId = r.record_id.length > 14 ? r.record_id.slice(0, 12) + '…' : r.record_id;
                return `<tr>
                    <td><span class="cell-record" title="${r.record_id}">${shortId}</span></td>
                    <td><span class="cell-faculty">${r.faculty_name}</span></td>
                    <td>${statusBadge}</td>
                    <td><span class="cell-subject">${r.room} · ${r.subject}</span><br><span class="stat-sub tabnum">${r.present_students}/${r.expected_students} students</span></td>
                    <td><div class="rate-cell"><span class="rate-pct tabnum">${r.attendance_rate}%</span>${sigDots}</div></td>
                </tr>`;
            }).join('');
        }
    }

    function populateRecordsModal() {
        api('GET_RECORDS').then(res => {
            if (!res.ok) return;
            // Tab 1: Attendance Records
            const atBody = document.getElementById('modalAttendanceBody');
            const recs = res.attendance_records || [];
            if (!recs.length) {
                atBody.innerHTML = `<tr><td colspan="9"><div class="empty-state">No records yet</div></td></tr>`;
            } else {
                atBody.innerHTML = recs.slice().reverse().map(r => {
                    const statusBadge = r.professor_status === 'Present'
                        ? `<span class="badge-am present">Present</span>`
                        : `<span class="badge-am absent">Absent</span>`;
                    const sigs = [
                        r.professor_signature ? `<img src="${r.professor_signature}" style="max-height:20px; max-width:50px;" title="Prof Sig" class="border rounded bg-white me-1">` : `<span class="text-muted small me-1">No Prof</span>`,
                        r.mayor_signature     ? `<img src="${r.mayor_signature}" style="max-height:20px; max-width:50px;" title="Mayor Sig" class="border rounded bg-white">` : `<span class="text-muted small">No Mayor</span>`,
                    ].join('');
                    return `<tr class="align-middle">
                        <td class="tabular-nums small">${r.record_id}</td>
                        <td class="small">${r.date}</td>
                        <td class="fw-bold small">${r.faculty_name}</td>
                        <td>${statusBadge}</td>
                        <td class="small">${r.room}</td>
                        <td class="small">${r.subject}</td>
                        <td class="tabular-nums small">${r.expected_students} / ${r.present_students}</td>
                        <td class="tabular-nums small"><strong>${r.attendance_rate}%</strong></td>
                        <td>${sigs}</td>
                    </tr>`;
                }).join('');
            }

            // Tab 2: Performance
            const pBody = document.getElementById('modalPerfBody');
            const perfs = Object.values(res.professor_performance || {});
            if (!perfs.length) {
                pBody.innerHTML = `<tr><td colspan="8"><div class="empty-state">No performance data yet</div></td></tr>`;
            } else {
                pBody.innerHTML = perfs.map(p => `
                    <tr class="align-middle">
                        <td class="fw-bold small">${p.faculty_name}</td>
                        <td class="small">${p.period}</td>
                        <td class="tabular-nums small text-center">${p.total_classes}</td>
                        <td class="tabular-nums small text-center"><span class="text-success">${p.classes_present}</span></td>
                        <td class="tabular-nums small text-center"><span class="text-danger">${p.classes_absent}</span></td>
                        <td class="tabular-nums small"><strong>${p.presence_rate || 0}%</strong></td>
                        <td class="tabular-nums small">${p.total_expected_students} / ${p.total_present_students}</td>
                        <td class="tabular-nums small"><strong>${p.avg_attendance_rate || 0}%</strong></td>
                    </tr>
                `).join('');
            }
        });
    }

})();
</script>

