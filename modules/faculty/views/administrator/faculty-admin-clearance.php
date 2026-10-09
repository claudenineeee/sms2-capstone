<?php
declare(strict_types=1);
/**
 * SMS 2 - Faculty Admin: Faculty Clearance Management
 * Role  : Faculty Admin
 */
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();

$userRole = getCurrentUserRoleKey();
if (!in_array($userRole, ['department_head', 'dept_head', 'hr', 'faculty_admin', 'dean', 'admin', 'super_admin'], true)) {
  http_response_code(403);
  exit('Access denied.');
}

require_once __DIR__ . '/../../controllers/clearance.php';
$db = facultyDb();
$profile = $db ? facultyClearanceProfile($db, (int) getCurrentUserId()) : null;
$term = $db ? facultyClearanceTerm($db) : null;
$offices = $db ? facultyClearanceOffices($db) : [];

// 1. Fetch departments from database
$departmentsList = [];
if ($db) {
  try {
    $departmentsList = $db->query("SELECT department_id, code, name FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
  }
}

// 2. Fetch all faculty members and clearance statuses from database
$rawFacultyList = [];
$totalClearances = 0;
$pendingCount = 0;
$forVerificationCount = 0;
$returnedCount = 0;
$readyCount = 0;
$clearedCount = 0;
$attentionItems = [];
$recentActivity = [];

if ($db && $term) {
  try {
    $termId = (int) $term['term_id'];
    $stmt = $db->prepare("SELECT fp.*, 
                                 cr.clearance_id, 
                                 cr.clearance_no, 
                                 cr.overall_status, 
                                 cr.submitted_at, 
                                 cr.updated_at, 
                                 cr.form_status, 
                                 cr.form_submitted, 
                                 cr.form_submitted_at
                          FROM faculty_profiles fp
                          LEFT JOIN faculty f ON f.faculty_no = fp.faculty_id
                          LEFT JOIN clearance_requests cr ON cr.faculty_id = f.faculty_id AND cr.term_id = ?
                          WHERE (fp.position = 'Faculty Professor' OR fp.position LIKE '%Faculty%' OR cr.clearance_id IS NOT NULL)
                            AND (fp.profile_status = 'Active' OR fp.profile_status IS NULL OR fp.profile_status = 'Pending Approval')
                          ORDER BY cr.updated_at DESC, fp.last_name, fp.first_name");
    $stmt->execute([$termId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deptNameMap = [];
    foreach ($departmentsList as $d) {
      $deptNameMap[strtoupper(trim((string) $d['code']))] = $d['name'];
    }

    $colorPalette = [
      ['bg' => '#eff6ff', 'color' => '#2563eb'],
      ['bg' => '#fffbeb', 'color' => '#d97706'],
      ['bg' => '#faf5ff', 'color' => '#7c3aed'],
      ['bg' => '#fff7ed', 'color' => '#ea580c'],
      ['bg' => '#f0fdf4', 'color' => '#16a34a'],
      ['bg' => '#ecfeff', 'color' => '#0891b2'],
    ];

    foreach ($rows as $r) {
      $cJson = $r['clearance_id'] ? facultyClearanceJson(facultyClearanceRequest($db, (int) $r['id'], $termId)) : null;
      $rawStatus = $cJson['status'] ?? ($r['overall_status'] ?: 'Not Submitted');
      $progress = (int) ($cJson['progress'] ?? 0);

      $statusLower = strtolower(trim((string) $rawStatus));
      if (in_array($statusLower, ['completed', 'cleared'], true)) {
        $statusKey = 'cleared';
        $statusLabel = 'Cleared';
        $badgeClass = 'fcm-badge-cleared';
        $clearedCount++;
      } elseif (in_array($statusLower, ['for final approval', 'ready for final approval', 'ready'], true) || $progress >= 83) {
        $statusKey = 'ready';
        $statusLabel = 'Ready for Final Approval';
        $badgeClass = 'fcm-badge-ready';
        $readyCount++;
      } elseif (in_array($statusLower, ['with deficiency', 'action required', 'denied', 'on hold', 'returned', 'rejected'], true)) {
        $statusKey = 'returned';
        $statusLabel = 'Returned';
        $badgeClass = 'fcm-badge-returned';
        $returnedCount++;
      } elseif (in_array($statusLower, ['under verification', 'pending review', 'for department head approval', 'under review', 'pending verification'], true) || (!empty($cJson['submitted_items']) && $cJson['submitted_items'] > 0)) {
        $statusKey = 'for-verification';
        $statusLabel = 'For Verification';
        $badgeClass = 'fcm-badge-verify';
        $forVerificationCount++;
      } else {
        $statusKey = 'pending';
        $statusLabel = 'Pending';
        $badgeClass = 'fcm-badge-pending';
        $pendingCount++;
      }

      $totalClearances++;

      $dCode = strtoupper(trim((string) ($r['designated_department'] ?? '')));
      $dName = $deptNameMap[$dCode] ?? ($dCode ? "Department of $dCode" : 'General Faculty');

      $subDate = !empty($r['submitted_at'])
        ? date('M d, Y', strtotime($r['submitted_at']))
        : (!empty($r['form_submitted_at']) ? date('M d, Y', strtotime($r['form_submitted_at'])) : 'Awaiting submission');

      $first = trim((string) ($r['first_name'] ?? ''));
      $last = trim((string) ($r['last_name'] ?? ''));
      $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
      if (!$initials)
        $initials = 'FP';

      $colorIdx = (int) ($r['id'] ?? 0) % count($colorPalette);

      $facultyItem = [
        'profile_id' => (int) $r['id'],
        'clearance_id' => !empty($r['clearance_id']) ? (int) $r['clearance_id'] : null,
        'faculty_no' => (string) ($r['faculty_id'] ?? 'FAC-' . $r['id']),
        'name' => facultyClearanceDisplayName($r),
        'first_name' => $first,
        'last_name' => $last,
        'initials' => $initials,
        'avatarBg' => $colorPalette[$colorIdx]['bg'],
        'avatarColor' => $colorPalette[$colorIdx]['color'],
        'dept' => $dName,
        'deptCode' => strtolower($dCode),
        'period' => ($term['semester'] ?? '1st Sem') . ', AY ' . ($term['academic_year'] ?? '2026–2027'),
        'subDate' => $subDate,
        'submitted_at_raw' => $r['submitted_at'] ?? $r['form_submitted_at'] ?? null,
        'completion' => $progress,
        'status' => $statusKey,
        'statusLabel' => $statusLabel,
        'badgeClass' => $badgeClass,
        'empType' => ucfirst(strtolower((string) ($r['employment_status'] ?? 'Regular'))),
        'position' => $r['position'] ?? 'Faculty Professor',
        'academic_rank' => $r['academic_rank'] ?? $r['tier'] ?? 'Faculty Member',
        'clearance' => $cJson,
      ];

      $rawFacultyList[] = $facultyItem;

      if (in_array($statusKey, ['for-verification', 'returned'], true)) {
        $attentionItems[] = $facultyItem;
      }
    }

    $actStmt = $db->query("SELECT h.*, fp.first_name AS fac_first, fp.last_name AS fac_last 
                           FROM clearance_approval_history h
                           LEFT JOIN clearance_requests cr ON cr.clearance_id = h.clearance_id
                           LEFT JOIN faculty f ON f.faculty_id = cr.faculty_id
                           LEFT JOIN faculty_profiles fp ON fp.faculty_id = f.faculty_no
                           ORDER BY h.created_at DESC LIMIT 6");
    $recentActivity = $actStmt->fetchAll(PDO::FETCH_ASSOC);

  } catch (Throwable $e) {
    error_log("Faculty clearance query error: " . $e->getMessage());
  }
}

$completionRate = $totalClearances > 0 ? round(($clearedCount / $totalClearances) * 100, 1) : 0;

$pageTitle = 'Faculty Clearance Management';
$activeModule = 'faculty';
$activePage = 'faculty-clearance';
$breadcrumbs = [
  ['label' => 'FACULTY MANAGEMENT SYSTEM', 'url' => BASE_URL . '/modules/faculty/index.php'],
  ['label' => 'CLEARANCE', 'url' => null],
];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');

  :root {
    --fcm-bg: #f8fafc;
    --fcm-card-bg: #ffffff;
    --fcm-border: #e2e8f0;
    --fcm-text-main: #0f172a;
    --fcm-text-muted: #64748b;
    --fcm-primary: #1d4ed8;
    --fcm-primary-hover: #1e40af;
    --fcm-primary-light: #eff6ff;
    --fcm-navy: #0f2d5e;
  }

  #fcm-page {
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif;
    color: var(--fcm-text-main);
    background-color: var(--fcm-bg);
    min-height: 100vh;
  }

  /* Header */
  .fcm-header-meta {
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 6px;
  }

  .fcm-header-title {
    font-size: 1.85rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
  }

  .fcm-header-sub {
    font-size: 0.92rem;
    color: var(--fcm-text-muted);
    margin-bottom: 0;
  }

  /* Admin Workflow Bar */
  .fcm-workflow-bar {
    background: #f1f5f9;
    border-radius: 50px;
    padding: 6px 14px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .fcm-wf-label {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #64748b;
    text-transform: uppercase;
    padding-right: 10px;
    border-right: 1.5px solid #cbd5e1;
  }

  .fcm-wf-badge-done {
    background: #1d4ed8;
    color: #ffffff;
    font-size: 0.73rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }

  .fcm-wf-badge-pending {
    color: #64748b;
    font-size: 0.73rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .fcm-wf-num {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 1.5px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 700;
    color: #64748b;
  }

  .fcm-wf-arrow {
    color: #94a3b8;
    font-size: 0.75rem;
  }

  /* KPI Cards */
  .fcm-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 22px;
    position: relative;
    transition: all 0.2s ease;
    cursor: pointer;
    height: 100%;
  }

  .fcm-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
  }

  .fcm-kpi-card.active-kpi {
    border: 2px solid #2563eb !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15);
  }

  .fcm-kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    margin-bottom: 14px;
  }

  .fcm-kpi-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--fcm-text-muted);
    margin-bottom: 4px;
  }

  .fcm-kpi-val {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.03em;
    line-height: 1.1;
    margin-bottom: 8px;
  }

  .fcm-kpi-footer {
    font-size: 0.73rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .fcm-kpi-arrow {
    font-size: 0.85rem;
    color: #94a3b8;
    transition: transform 0.2s;
  }

  .fcm-kpi-card:hover .fcm-kpi-arrow {
    color: #1d4ed8;
    transform: translateX(3px);
  }

  /* Sections */
  .fcm-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
  }

  .fcm-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
  }

  .fcm-panel-title {
    font-size: 1.12rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
  }

  .fcm-panel-sub {
    font-size: 0.83rem;
    color: var(--fcm-text-muted);
    margin-bottom: 0;
  }

  /* Live Badge */
  .fcm-live-pill {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .fcm-live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
  }

  /* Filter Bar */
  .fcm-filter-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .fcm-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
  }

  .fcm-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.88rem;
  }

  .fcm-search-input {
    width: 100%;
    padding: 8px 14px 8px 38px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.84rem;
    background: #ffffff;
    color: #0f172a;
    transition: all 0.2s;
  }

  .fcm-search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
  }

  .fcm-filter-select {
    padding: 8px 32px 8px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.84rem;
    background-color: #ffffff;
    color: #334155;
    font-weight: 500;
    cursor: pointer;
  }

  /* Monitoring Table */
  .fcm-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }

  .fcm-table thead th {
    font-size: 0.69rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
    padding: 12px 14px;
    border-bottom: 1.5px solid #e2e8f0;
    background: transparent;
  }

  .fcm-table tbody tr {
    transition: background 0.15s ease;
  }

  .fcm-table tbody tr:hover {
    background: #f8fafc;
  }

  .fcm-table tbody td {
    padding: 14px 14px;
    vertical-align: middle;
    font-size: 0.84rem;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Badges */
  .fcm-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 600;
    white-space: nowrap;
  }

  .fcm-badge-verify {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
  }

  .fcm-badge-pending {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
  }

  .fcm-badge-returned {
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #fed7aa;
  }

  .fcm-badge-ready {
    background: #faf5ff;
    color: #7c3aed;
    border: 1px solid #e9d5ff;
  }

  .fcm-badge-cleared {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
  }

  .fcm-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  /* Action button in table */
  .fcm-btn-view {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 6px 14px;
    border-radius: 9px;
    font-size: 0.77rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.18s;
    cursor: pointer;
  }

  .fcm-btn-view:hover {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
  }

  /* Attention Cards & Activity */
  .fcm-attn-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    margin-bottom: 10px;
    transition: background 0.15s;
  }

  .fcm-attn-card:hover {
    background: #f8fafc;
  }

  .fcm-attn-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
  }

  .fcm-attn-btn {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    border-radius: 8px;
    padding: 5px 14px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
  }

  .fcm-attn-btn:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
  }

  /* Activity item */
  .fcm-act-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .fcm-act-item:last-child {
    border-bottom: none;
  }

  .fcm-act-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
    margin-right: 12px;
  }

  /* Report Selectable Cards */
  .fcm-report-box {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s;
    cursor: pointer;
    height: 100%;
  }

  .fcm-report-box:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
  }

  .fcm-report-box.active-report {
    border-color: #2563eb;
    background: #f8fafc;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
  }

  /* Slide-over Drawer (Offcanvas) */
  .fcm-drawer {
    width: 740px !important;
    max-width: 95vw;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
    border-left: 1px solid #e2e8f0;
  }

  .fcm-navy-banner {
    background: linear-gradient(135deg, #0f2d5e 0%, #17386d 100%);
    border-radius: 14px;
    padding: 22px 24px;
    color: #ffffff;
    margin-bottom: 24px;
  }

  .fcm-req-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 12px;
    background: #ffffff;
    transition: all 0.18s;
  }

  .fcm-req-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  }

  .fcm-req-action-btn {
    font-size: 0.74rem;
    font-weight: 600;
    color: #2563eb;
    background: transparent;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
  }

  .fcm-req-action-btn:hover {
    background: #eff6ff;
  }

  /* Admin Action Cards in Drawer */
  .fcm-grid-action-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.83rem;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    transition: all 0.18s;
    width: 100%;
  }

  .fcm-grid-action-btn:hover {
    border-color: #2563eb;
    background: #eff6ff;
    color: #1d4ed8;
  }

  /* Yellow warning box */
  .fcm-warn-box {
    background: #fefce8;
    border: 1px solid #fef08a;
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    margin-bottom: 24px;
  }

  /* History Card in Drawer */
  .fcm-hist-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
  }

  .fcm-hist-row:last-child {
    border-bottom: none;
  }
</style>

<div id="fcm-page" class="p-3 p-md-4">
  <!-- TOP HEADER AREA -->
  <div
    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-3">
    <div>
      <div class="fcm-header-meta">FACULTY MANAGEMENT SYSTEM &nbsp;/&nbsp; CLEARANCE</div>
      <h1 class="fcm-header-title">Faculty Clearance Management</h1>
      <p class="fcm-header-sub">Monitor submissions, verify requirements, and prepare clearances for final approval.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3"
        style="font-size:0.84rem;background:#fff" onclick="openReportsModal()">
        <i class="fas fa-file-export"></i>Export Overview
      </button>
      <button class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3"
        style="font-size:0.84rem;background:#1d4ed8;border-color:#1d4ed8" onclick="openReportsModal()">
        <i class="fas fa-chart-line"></i>Generate Report
      </button>
    </div>
  </div>


  <!-- SUMMARY CARDS (6 CARDS IN EXACT FIGMA ROW) -->
  <div class="row g-3 mb-4">
    <!-- 1. Total -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="fcm-kpi-card" onclick="filterByStatus('all')">
        <div class="fcm-kpi-icon" style="background:#eff6ff;color:#2563eb">
          <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="fcm-kpi-title">Total Faculty Clearances</div>
        <div class="fcm-kpi-val" id="kpi-total-val"><?= (int) $totalClearances ?></div>
        <div class="fcm-kpi-footer">
          <span id="kpi-total-sub"><?= (int) $totalClearances ?> total faculty</span>
          <span class="fcm-kpi-arrow">&rarr;</span>
        </div>
      </div>
    </div>
    <!-- 2. Pending -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="fcm-kpi-card" onclick="filterByStatus('pending')">
        <div class="fcm-kpi-icon" style="background:#fffbeb;color:#d97706">
          <i class="fas fa-clock"></i>
        </div>
        <div class="fcm-kpi-title">Pending</div>
        <div class="fcm-kpi-val" id="kpi-pending-val"><?= (int) $pendingCount ?></div>
        <div class="fcm-kpi-footer">
          <span id="kpi-pending-sub"><?= (int) $pendingCount ?> awaiting action</span>
          <span class="fcm-kpi-arrow">&rarr;</span>
        </div>
      </div>
    </div>
    <!-- 3. For Verification (Active highlighted) -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="fcm-kpi-card active-kpi" onclick="filterByStatus('for-verification')">
        <div class="fcm-kpi-icon" style="background:#ecfeff;color:#0891b2">
          <i class="fas fa-file-alt"></i>
        </div>
        <div class="fcm-kpi-title">For Verification</div>
        <div class="fcm-kpi-val" id="kpi-verify-val"><?= (int) $forVerificationCount ?></div>
        <div class="fcm-kpi-footer">
          <span id="kpi-verify-sub"><?= (int) $forVerificationCount ?> to verify</span>
          <span class="fcm-kpi-arrow">&rarr;</span>
        </div>
      </div>
    </div>
    <!-- 4. Returned -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="fcm-kpi-card" onclick="filterByStatus('returned')">
        <div class="fcm-kpi-icon" style="background:#fff7ed;color:#ea580c">
          <i class="fas fa-undo"></i>
        </div>
        <div class="fcm-kpi-title">Returned</div>
        <div class="fcm-kpi-val" id="kpi-returned-val"><?= (int) $returnedCount ?></div>
        <div class="fcm-kpi-footer">
          <span id="kpi-returned-sub"><?= (int) $returnedCount ?> with deficiency</span>
          <span class="fcm-kpi-arrow">&rarr;</span>
        </div>
      </div>
    </div>
    <!-- 5. Ready for Final Approval -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="fcm-kpi-card" onclick="filterByStatus('ready')">
        <div class="fcm-kpi-icon" style="background:#faf5ff;color:#7c3aed">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="fcm-kpi-title">Ready for Final Approval</div>
        <div class="fcm-kpi-val" id="kpi-ready-val"><?= (int) $readyCount ?></div>
        <div class="fcm-kpi-footer">
          <span id="kpi-ready-sub"><?= (int) $readyCount ?> endorsed</span>
          <span class="fcm-kpi-arrow">&rarr;</span>
        </div>
      </div>
    </div>
    <!-- 6. Cleared -->
    <div class="col-6 col-md-4 col-xl-2">
      <div class="fcm-kpi-card" onclick="filterByStatus('cleared')">
        <div class="fcm-kpi-icon" style="background:#f0fdf4;color:#16a34a">
          <i class="fas fa-check"></i>
        </div>
        <div class="fcm-kpi-title">Cleared</div>
        <div class="fcm-kpi-val" id="kpi-cleared-val"><?= (int) $clearedCount ?></div>
        <div class="fcm-kpi-footer">
          <span id="kpi-cleared-sub"><?= $completionRate ?>% completion rate</span>
          <span class="fcm-kpi-arrow">&rarr;</span>
        </div>
      </div>
    </div>
  </div>

  <!-- CLEARANCE MONITORING CARD (MAIN SECTION) -->
  <div class="fcm-panel">
    <div class="fcm-panel-header">
      <div>
        <h2 class="fcm-panel-title">Clearance Monitoring</h2>
        <p class="fcm-panel-sub">Review and manage faculty clearance submissions across all departments.</p>
      </div>
      <div>
        <span class="fcm-live-pill"><span class="fcm-live-dot"></span>Live SQL records</span>
      </div>
    </div>

    <!-- FILTER ROW -->
    <div class="fcm-filter-row">
      <div class="fcm-search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="fcm-search" class="fcm-search-input" placeholder="Search name, ID, or department"
          oninput="applyFilters()">
      </div>
      <select id="fcm-status-filter" class="fcm-filter-select" onchange="applyFilters()">
        <option value="all">All statuses</option>
        <option value="for-verification" selected>For Verification</option>
        <option value="pending">Pending</option>
        <option value="returned">Returned</option>
        <option value="ready">Ready for Final Approval</option>
        <option value="cleared">Cleared</option>
      </select>
      <select id="fcm-dept-filter" class="fcm-filter-select" onchange="applyFilters()">
        <option value="all">All departments</option>
        <?php foreach ($departmentsList as $d): ?>
          <option value="<?= strtolower(htmlspecialchars((string) $d['code'])) ?>">
            <?= htmlspecialchars((string) $d['name']) ?> (<?= htmlspecialchars((string) $d['code']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3"
        style="font-size:0.83rem;background:#fff" onclick="toggleMoreFilters()">
        <i class="fas fa-filter"></i>More Filters
      </button>
    </div>

    <!-- EXTRA FILTERS (TOGGLEABLE) -->
    <div id="fcm-more-filters" class="row g-2 mb-3 d-none p-3 rounded-3"
      style="background:#f8fafc;border:1px dashed #cbd5e1">
      <div class="col-md-4">
        <label class="form-label text-muted small fw-bold">Academic Year &amp; Semester</label>
        <select id="fcm-ay-filter" class="form-select form-select-sm" onchange="applyFilters()">
          <option value="all">All Periods</option>
          <option value="2026-2027 1st" selected>AY 2026–2027 &middot; 1st Semester</option>
          <option value="2025-2026 2nd">AY 2025–2026 &middot; 2nd Semester</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label text-muted small fw-bold">Employment Type</label>
        <select id="fcm-emp-filter" class="form-select form-select-sm" onchange="applyFilters()">
          <option value="all">All Employment Types</option>
          <option value="Regular">Regular Full-time</option>
          <option value="Part-time">Part-time</option>
          <option value="Contractual">Contractual</option>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetFilters()"><i
            class="fas fa-undo me-1"></i>Reset All Filters</button>
      </div>
    </div>

    <!-- DATA TABLE -->
    <div class="table-responsive">
      <table class="fcm-table">
        <thead>
          <tr>
            <th>FACULTY</th>
            <th>DEPARTMENT</th>
            <th>CLEARANCE PERIOD</th>
            <th>SUBMISSION DATE</th>
            <th>COMPLETION</th>
            <th>STATUS</th>
            <th class="text-end">ACTION</th>
          </tr>
        </thead>
        <tbody id="fcm-tbody">
          <!-- Rendered via JS -->
        </tbody>
      </table>
    </div>

    <!-- TABLE FOOTER / PAGINATION -->
    <div class="d-flex align-items-center justify-content-between pt-3 border-top flex-wrap gap-2 mt-2">
      <div id="fcm-table-info" class="text-muted small">Showing 1 of 248 clearances</div>
      <div class="d-flex align-items-center gap-1" id="fcm-pagination">
        <!-- Rendered via JS -->
      </div>
    </div>
  </div>

  <!-- MIDDLE ROW: NEEDS ATTENTION & RECENT ACTIVITY -->
  <div class="row g-4 mb-4">
    <!-- Left: Needs Your Attention -->
    <div class="col-lg-6">
      <div class="fcm-panel h-100">
        <div class="fcm-panel-header">
          <div>
            <h2 class="fcm-panel-title">Needs Your Attention</h2>
            <p class="fcm-panel-sub">Prioritized verification tasks</p>
          </div>
          <a href="javascript:void(0)" onclick="filterByStatus('for-verification')"
            class="text-primary text-decoration-none small fw-bold">View all &rarr;</a>
        </div>
        <div class="d-flex flex-column">
          <?php if (!empty($attentionItems)): ?>
            <?php foreach (array_slice($attentionItems, 0, 4) as $item): ?>
              <div class="fcm-attn-card">
                <div class="d-flex align-items-center gap-3">
                  <div class="fcm-attn-icon" style="background:<?= $item['avatarBg'] ?>;color:<?= $item['avatarColor'] ?>">
                    <i class="fas <?= $item['status'] === 'returned' ? 'fa-undo' : 'fa-file-alt' ?>"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark" style="font-size:0.86rem"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($item['dept']) ?> &middot;
                      <?= htmlspecialchars($item['statusLabel']) ?></div>
                  </div>
                </div>
                <button class="fcm-attn-btn" onclick="openDrawer(<?= (int) $item['profile_id'] ?>)">Review</button>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="p-4 text-center text-muted">
              <i class="fas fa-check-circle text-success fs-3 mb-2 opacity-50"></i>
              <div class="fw-semibold small">All Clear</div>
              <div class="small text-secondary">No clearance submissions currently requiring urgent verification.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right: Recent Admin Activity -->
    <div class="col-lg-6">
      <div class="fcm-panel h-100">
        <div class="fcm-panel-header">
          <div>
            <h2 class="fcm-panel-title">Recent Admin Activity</h2>
            <p class="fcm-panel-sub">Your clearance audit trail</p>
          </div>
          <a href="javascript:void(0)" onclick="openAuditModal()"
            class="text-primary text-decoration-none small fw-bold">View history &rarr;</a>
        </div>
        <div class="d-flex flex-column">
          <?php if (!empty($recentActivity)): ?>
            <?php foreach ($recentActivity as $act): ?>
              <?php
              $timeStr = !empty($act['created_at']) ? date('M d, g:i A', strtotime($act['created_at'])) : 'Recent';
              $facName = trim(($act['fac_first'] ?? '') . ' ' . ($act['fac_last'] ?? '')) ?: 'Faculty Member';
              $officeName = $act['office'] ?? 'Clearance';
              $actName = $act['action'] ?? 'Updated';
              ?>
              <div class="fcm-act-item">
                <div class="d-flex align-items-center">
                  <div class="fcm-act-icon"><i class="fas fa-check"></i></div>
                  <div>
                    <div class="fw-bold text-dark" style="font-size:0.84rem"><?= htmlspecialchars($actName) ?></div>
                    <div class="text-muted" style="font-size:0.74rem"><?= htmlspecialchars($officeName) ?> &middot;
                      <?= htmlspecialchars($facName) ?></div>
                  </div>
                </div>
                <span class="text-muted" style="font-size:0.73rem"><?= htmlspecialchars($timeStr) ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="p-4 text-center text-muted">
              <i class="fas fa-history text-secondary fs-3 mb-2 opacity-50"></i>
              <div class="fw-semibold small">No Recent Activity</div>
              <div class="small text-secondary">No clearance actions or audit records logged yet.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- BOTTOM SECTION: CLEARANCE REPORTS -->
  <div class="fcm-panel">
    <div class="fcm-panel-header">
      <div>
        <h2 class="fcm-panel-title">Clearance Reports</h2>
        <p class="fcm-panel-sub">Generate oversight reports for administrative review and decision-making.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3"
          style="font-size:0.83rem;background:#fff" onclick="downloadReport('pdf')">
          <i class="fas fa-file-pdf text-danger"></i>Export PDF
        </button>
        <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3"
          style="font-size:0.83rem;background:#fff" onclick="downloadReport('xlsx')">
          <i class="fas fa-file-excel text-success"></i>Export Excel
        </button>
        <button class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3"
          style="font-size:0.83rem;background:#1d4ed8;border-color:#1d4ed8" onclick="openReportsModal()">
          <i class="fas fa-chart-bar"></i>Generate Report
        </button>
      </div>
    </div>

    <!-- Reports Filters -->
    <div class="row g-2 mb-3">
      <div class="col-6 col-md">
        <select class="form-select form-select-sm" style="font-size:0.8rem">
          <option>Department: All</option>
          <option>College of Engineering</option>
          <option>College of Computer Studies</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select class="form-select form-select-sm" style="font-size:0.8rem">
          <option>Academic Year: 2026–2027</option>
          <option>Academic Year: 2025–2026</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select class="form-select form-select-sm" style="font-size:0.8rem">
          <option>Semester: 1st Semester</option>
          <option>Semester: 2nd Semester</option>
        </select>
      </div>
      <div class="col-6 col-md">
        <select class="form-select form-select-sm" style="font-size:0.8rem">
          <option>Status: All</option>
          <option>For Verification</option>
          <option>Cleared</option>
        </select>
      </div>
      <div class="col-12 col-md">
        <select class="form-select form-select-sm" style="font-size:0.8rem">
          <option>Date Range: Sep 01 - Oct 31</option>
          <option>Last 30 Days</option>
        </select>
      </div>
    </div>

    <!-- 5 Clickable Report Types in Row -->
    <div class="row g-3">
      <div class="col-12 col-sm-6 col-lg">
        <div class="fcm-report-box" onclick="selectReportType(this, 'pending')">
          <div>
            <div class="fw-bold text-dark" style="font-size:0.84rem"><i
                class="fas fa-file-alt text-muted me-2"></i>Pending Faculty Clearance</div>
            <div class="text-muted" style="font-size:0.73rem">View and export report</div>
          </div>
          <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg">
        <div class="fcm-report-box" onclick="selectReportType(this, 'returned')">
          <div>
            <div class="fw-bold text-dark" style="font-size:0.84rem"><i class="fas fa-undo text-muted me-2"></i>Returned
              Clearance</div>
            <div class="text-muted" style="font-size:0.73rem">View and export report</div>
          </div>
          <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg">
        <div class="fcm-report-box active-report" onclick="selectReportType(this, 'verification')">
          <div>
            <div class="fw-bold text-primary" style="font-size:0.84rem"><i
                class="fas fa-tasks text-primary me-2"></i>Clearance Verification Report</div>
            <div class="text-muted" style="font-size:0.73rem">Verification activity and status</div>
          </div>
          <i class="fas fa-chevron-right text-primary" style="font-size:0.75rem"></i>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg">
        <div class="fcm-report-box" onclick="selectReportType(this, 'completed')">
          <div>
            <div class="fw-bold text-dark" style="font-size:0.84rem"><i
                class="fas fa-check-circle text-muted me-2"></i>Completed Clearance</div>
            <div class="text-muted" style="font-size:0.73rem">View and export report</div>
          </div>
          <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg">
        <div class="fcm-report-box" onclick="selectReportType(this, 'dept')">
          <div>
            <div class="fw-bold text-dark" style="font-size:0.84rem"><i
                class="fas fa-chart-bar text-muted me-2"></i>Department Clearance Summary</div>
            <div class="text-muted" style="font-size:0.73rem">View and export report</div>
          </div>
          <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================== -->
<!-- EXACT FIGMA RIGHT-SIDE SLIDE-OVER DRAWER: CLEARANCE REVIEW -->
<!-- ============================================================== -->
<div class="offcanvas offcanvas-end fcm-drawer" tabindex="-1" id="clearanceDrawer"
  aria-labelledby="clearanceDrawerLabel">
  <div class="offcanvas-header border-bottom px-4 py-3 align-items-start">
    <div>
      <div class="fcm-header-meta text-primary mb-1">CLEARANCE REVIEW</div>
      <h3 class="fw-bold mb-0 text-dark" id="dr-faculty-name" style="font-size:1.45rem">Dr. Amelia Santos</h3>
      <div class="text-muted small" id="dr-faculty-sub">FAC-2026-0148 &middot; College of Engineering</div>
    </div>
    <button type="button" class="btn-close text-reset mt-1" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-4">
    <!-- 4-Box Faculty Info Grid -->
    <div class="row g-2 mb-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
      <div class="col-6 col-md-3">
        <div class="text-muted text-uppercase fw-bold" style="font-size:0.68rem">POSITION</div>
        <div class="fw-bold text-dark small" id="dr-position">Associate Professor IV</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-muted text-uppercase fw-bold" style="font-size:0.68rem">ACADEMIC YEAR</div>
        <div class="fw-bold text-dark small" id="dr-ay">2026–2027</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-muted text-uppercase fw-bold" style="font-size:0.68rem">SEMESTER</div>
        <div class="fw-bold text-dark small" id="dr-sem">1st Semester</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-muted text-uppercase fw-bold" style="font-size:0.68rem">SUBMITTED</div>
        <div class="fw-bold text-dark small" id="dr-subdate">Oct 02, 2026</div>
      </div>
    </div>

    <!-- Dark Navy Banner: CLEARANCE PROGRESS -->
    <div class="fcm-navy-banner">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
          <div class="text-uppercase text-white-50 fw-bold" style="font-size:0.68rem">CLEARANCE PROGRESS</div>
          <div class="fs-5 fw-bold text-white" id="dr-prog-label">4 of 6 Requirements Verified</div>
        </div>
        <div class="display-6 fw-bold text-white" id="dr-prog-pct">67%</div>
      </div>
      <div class="progress mb-4" style="height:8px;background:rgba(255,255,255,0.2);border-radius:10px">
        <div id="dr-prog-bar" class="progress-bar bg-white" style="width:67%;border-radius:10px"></div>
      </div>
      <!-- Stepper inside banner -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-center"
        style="font-size:0.72rem">
        <div class="text-white"><i class="fas fa-check-circle me-1 text-info"></i>Submitted</div>
        <div class="text-white"><i class="fas fa-check-circle me-1 text-info"></i>Verification</div>
        <div class="text-white-50"><span class="badge bg-primary rounded-pill me-1">3</span>Correction</div>
        <div class="text-white-50"><span class="badge bg-secondary rounded-pill me-1">4</span>Verified</div>
        <div class="text-white-50"><span class="badge bg-secondary rounded-pill me-1">5</span>Final approval</div>
        <div class="text-white-50"><span class="badge bg-secondary rounded-pill me-1">6</span>Cleared</div>
      </div>
    </div>

    <!-- Requirements Checklist Section -->
    <div class="mb-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <h4 class="fw-bold mb-1 text-dark" style="font-size:1.05rem">Requirements Status</h4>
          <p class="text-muted small mb-0">Track requirement approval and deficiency status across all clearance offices.</p>
        </div>
        <span class="badge bg-light text-primary border px-3 py-2 rounded-pill fw-bold" id="dr-complete-badge"
          style="font-size:0.73rem">0 of 6 approved</span>
      </div>

      <!-- Checklist Items -->
      <div id="dr-reqs-list">
        <div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Select a faculty clearance
          to view requirements.</div>
      </div>
    </div>

    <!-- Administrative Actions & Reports Section -->
    <div class="mb-4">
      <h4 class="fw-bold mb-1 text-dark" style="font-size:1.05rem">Administrative Actions &amp; Reports</h4>
      <p class="text-muted small mb-3">Document access, audit logs, and institutional report generation.</p>

      <div class="row g-2 mb-3">
        <div class="col-md-6">
          <button class="fcm-grid-action-btn" onclick="viewAllAttachedDocs()">
            <span><i class="fas fa-file-archive text-primary me-2"></i>View All Attached Docs</span>
            <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
          </button>
        </div>
        <div class="col-md-6">
          <button class="fcm-grid-action-btn" onclick="openAuditModal()">
            <span><i class="fas fa-clock text-info me-2"></i>View Clearance History</span>
            <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
          </button>
        </div>
        <div class="col-md-6">
          <button class="fcm-grid-action-btn" onclick="downloadReport('pdf')">
            <span><i class="fas fa-file-pdf text-danger me-2"></i>Generate Clearance Report (PDF)</span>
            <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
          </button>
        </div>
        <div class="col-md-6">
          <button class="fcm-grid-action-btn" onclick="downloadReport('excel')">
            <span><i class="fas fa-file-excel text-success me-2"></i>Export Summary (Excel)</span>
            <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem"></i>
          </button>
        </div>
      </div>

      <!-- Verification Callout Banner -->
      <div class="fcm-warn-box" id="dr-warn-box">
        <div
          style="width:36px;height:36px;border-radius:50%;background:#fef08a;color:#a16207;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
          <i class="fas fa-clock"></i>
        </div>
        <div>
          <div class="fw-bold" id="dr-warn-title" style="color:#854d0e;font-size:0.9rem">Clearance In Progress</div>
          <div class="fw-bold mb-1" id="dr-warn-sub" style="color:#713f12;font-size:0.83rem">0 of 6 requirements approved</div>
          <div class="small" id="dr-warn-desc" style="color:#854d0e;line-height:1.4">Tracking individual office verification and requirement approval statuses.</div>
        </div>
      </div>
    </div>

    <!-- Clearance History Section (Image 4 bottom) -->
    <div class="mb-3">
      <h4 class="fw-bold mb-1 text-dark" style="font-size:1.05rem">Clearance History</h4>
      <p class="text-muted small mb-3">Recorded Faculty Admin activity for this submission.</p>

      <div class="border rounded-3" style="background:#ffffff" id="dr-hist-list">
        <div class="p-3 text-center text-muted small">No audit history for this clearance yet.</div>
      </div>
    </div>
  </div>
  <div class="offcanvas-footer border-top p-3 px-4 bg-light d-flex justify-content-between">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="offcanvas">Close Drawer</button>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadReport('pdf')"><i class="fas fa-file-pdf me-1"></i>Export PDF</button>
      <button type="button" class="btn btn-sm btn-primary" onclick="openForwardModal('Dr. Amelia Santos')"><i
          class="fas fa-share me-1"></i>Forward to Dean / VPAA</button>
    </div>
  </div>
</div>

<!-- MODAL: DOCUMENT PREVIEW -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90vw;">
    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="height: 88vh;">
      <div class="modal-header py-2.5 px-3 bg-light border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2.5 text-truncate me-3">
          <div style="width:34px;height:34px;border-radius:8px;background:#fef2f2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0">
            <i class="fas fa-file-pdf"></i>
          </div>
          <div class="text-truncate">
            <div id="docPreviewTitle" class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">Document Preview</div>
            <div id="docPreviewSub" class="text-muted small" style="font-size: 0.72rem;">Clearance Requirement Attachment</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          <a id="docPreviewOpenTab" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary py-1 px-2.5 d-inline-flex align-items-center gap-1.5" style="font-size: 0.75rem;">
            <i class="fas fa-external-link-alt"></i><span>Open in New Tab</span>
          </a>
          <a id="docPreviewDownload" href="#" class="btn btn-sm btn-primary py-1 px-2.5 d-inline-flex align-items-center gap-1.5" style="font-size: 0.75rem;">
            <i class="fas fa-download"></i><span>Download</span>
          </a>
          <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-0 position-relative" style="height: calc(88vh - 58px); background: #525659;">
        <div id="docPreviewLoading" class="position-absolute top-50 start-50 translate-middle text-white text-center">
          <i class="fas fa-circle-notch fa-spin fa-2x mb-2 text-light opacity-75"></i>
          <div class="small opacity-75">Loading uploaded document...</div>
        </div>
        <iframe id="docPreviewFrame" src="" style="width: 100%; height: 100%; border: none;" onload="const l=document.getElementById('docPreviewLoading');if(l)l.classList.add('d-none');"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: RETURN REQUIREMENT -->
<div class="modal fade" id="returnReqModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-undo-alt me-2"></i>Return Clearance Requirement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <label class="form-label text-muted small fw-bold">Requirement</label>
          <input type="text" id="ret-modal-req" class="form-control form-control-sm fw-bold" readonly
            value="Property Clearance">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Return Reason <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" id="ret-modal-reason">
            <option value="">-- Select Reason --</option>
            <option>Missing property serial number / tag</option>
            <option>Incomplete departmental clearance checklist</option>
            <option>Illegible / blurry scan uploaded</option>
            <option>Unsettled institutional accountability</option>
            <option>Expired certification / clearance slip</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Specific Deficiencies &amp; Instructions</label>
          <textarea class="form-control form-control-sm" rows="3"
            placeholder="Explain what the faculty member needs to correct and re-upload..."></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="ret-modal-notify" checked>
          <label class="form-check-label small" for="ret-modal-notify">Notify faculty member immediately via email
            notification</label>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" onclick="confirmReturnReq()"><i
            class="fas fa-paper-plane me-1"></i>Confirm &amp; Return Requirement</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: ADD REMARKS -->
<div class="modal fade" id="addRemarkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-comment-dots me-2"></i>Add Administrative Remark</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <label class="form-label text-muted small fw-bold">Target Area</label>
          <input type="text" id="rem-modal-target" class="form-control form-control-sm fw-bold" readonly
            value="General Clearance">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Remark Notes <span class="text-danger">*</span></label>
          <textarea class="form-control form-control-sm" id="rem-modal-text" rows="3"
            placeholder="Enter observations, tracking notes, or requirements clarification..."></textarea>
        </div>
        <div class="p-3 rounded-3 bg-light border">
          <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="rem-visibility" id="rem-vis-faculty" checked>
            <label class="form-check-label small" for="rem-vis-faculty"><strong>Visible to Faculty:</strong> Faculty
              member can read this observation in their portal.</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="rem-visibility" id="rem-vis-admin">
            <label class="form-check-label small" for="rem-vis-admin"><strong>Internal Admin Only:</strong> Restricted
              to Faculty Admin &amp; Approving Authorities.</label>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="confirmSaveRemark()"><i
            class="fas fa-save me-1"></i>Save Remark</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: FORWARD TO APPROVING AUTHORITY -->
<div class="modal fade" id="forwardAuthModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header" style="background:#0f2d5e;color:#fff">
        <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-paper-plane me-2"></i>Forward to Approving Authority</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning border-0 small mb-3" style="background:#fffbeb;color:#92400e">
          <i class="fas fa-info-circle me-1"></i><strong>Scope Reminder:</strong> Faculty Admin reviews requirements and
          prepares completed clearances. Final clearance sign-off is granted by the selected university official.
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Select Approving Authority <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" id="fwd-auth-select">
            <option>Office of the College Dean</option>
            <option>Vice President for Academic Affairs (VPAA)</option>
            <option>Human Resources Director</option>
            <option>Office of the University President</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Faculty Admin Endorsement Note</label>
          <textarea class="form-control form-control-sm"
            rows="3">All 5 clearance requirements have been audited and verified in full compliance with university academic clearance policies. Recommended for final official approval.</textarea>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="confirmForwardAuth()"><i
            class="fas fa-check-double me-1"></i>Confirm &amp; Forward Clearance</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="fcmToast" class="toast align-items-center text-white bg-primary border-0 shadow-lg" role="alert"
    aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="fcm-toast-msg">Action completed successfully.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- JAVASCRIPT LOGIC -->
<script>
  const clearanceApi = '<?= BASE_URL ?>/modules/faculty/controllers/ClearanceController.php';
  let facultyClearanceRecords = <?= json_encode($rawFacultyList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  let activeStatusFilter = 'for-verification';
  let currentPage = 1;
  const rowsPerPage = 5;

  let currentSelectedProfileId = null;
  let currentSelectedClearanceId = null;
  let currentReviewData = null;
  let currentReturnItemId = 0;
  let currentRemarkItemId = 0;

  function renderTable() {
    const tbody = document.getElementById('fcm-tbody');
    if (!tbody) return;

    const searchVal = (document.getElementById('fcm-search')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('fcm-status-filter')?.value || 'all';
    const deptVal = document.getElementById('fcm-dept-filter')?.value || 'all';
    const empVal = document.getElementById('fcm-emp-filter')?.value || 'all';

    const filtered = facultyClearanceRecords.filter(item => {
      if (activeStatusFilter !== 'all' && item.status !== activeStatusFilter) return false;
      if (statusVal !== 'all' && item.status !== statusVal) return false;
      if (deptVal !== 'all' && item.deptCode !== deptVal) return false;
      if (empVal !== 'all' && item.empType !== empVal) return false;
      if (searchVal) {
        const matchName = (item.name || '').toLowerCase().includes(searchVal);
        const matchId = (item.faculty_no || '').toLowerCase().includes(searchVal);
        const matchDept = (item.dept || '').toLowerCase().includes(searchVal);
        if (!matchName && !matchId && !matchDept) return false;
      }
      return true;
    });

    const total = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
    if (currentPage > totalPages) currentPage = 1;

    const startIdx = (currentPage - 1) * rowsPerPage;
    const pageData = filtered.slice(startIdx, startIdx + rowsPerPage);

    const infoEl = document.getElementById('fcm-table-info');
    if (infoEl) {
      infoEl.innerHTML = `Showing ${total === 0 ? 0 : startIdx + 1} of ${total} clearances`;
    }

    // Render pagination
    const pagEl = document.getElementById('fcm-pagination');
    if (pagEl) {
      let pagHtml = `<button class="btn btn-sm btn-light border py-1 px-2 text-muted" style="font-size:0.75rem" onclick="goToPage(${Math.max(1, currentPage - 1)})">Previous</button>`;
      for (let i = 1; i <= totalPages; i++) {
        pagHtml += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-light border'} py-1 px-2 fw-semibold" style="font-size:0.75rem;min-width:28px" onclick="goToPage(${i})">${i}</button>`;
      }
      if (totalPages > 3) pagHtml += `<span class="px-1 text-muted">...</span><button class="btn btn-sm btn-light border py-1 px-2 fw-semibold" style="font-size:0.75rem" onclick="goToPage(${totalPages})">${totalPages}</button>`;
      pagHtml += `<button class="btn btn-sm btn-light border py-1 px-2 text-muted" style="font-size:0.75rem" onclick="goToPage(${Math.min(totalPages, currentPage + 1)})">Next</button>`;
      pagEl.innerHTML = pagHtml;
    }

    if (pageData.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-folder-open fa-2x mb-2 text-secondary opacity-50"></i><br>No faculty clearance records match current filters.</td></tr>`;
      return;
    }

    tbody.innerHTML = pageData.map(fac => `
    <tr>
      <td>
        <div class="d-flex align-items-center gap-3">
          <div style="width:36px;height:36px;border-radius:10px;background:${fac.avatarBg};color:${fac.avatarColor};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.78rem;flex-shrink:0">
            ${escapeHtml(fac.initials)}
          </div>
          <div>
            <div class="fw-bold text-dark" style="font-size:0.86rem">${escapeHtml(fac.name)}</div>
            <div class="text-muted" style="font-size:0.73rem">${escapeHtml(fac.faculty_no)}</div>
          </div>
        </div>
      </td>
      <td style="font-size:0.83rem;color:#334155">${escapeHtml(fac.dept)}</td>
      <td style="font-size:0.83rem;color:#334155">${escapeHtml(fac.period)}</td>
      <td style="font-size:0.83rem;color:#334155">${escapeHtml(fac.subDate)}</td>
      <td style="min-width:140px">
        <div class="d-flex align-items-center gap-2">
          <div class="progress flex-grow-1" style="height:6px;border-radius:6px;background:#e2e8f0">
            <div class="progress-bar" style="width:${fac.completion}%;background:${fac.completion === 100 ? '#16a34a' : '#2563eb'};border-radius:6px"></div>
          </div>
          <span class="fw-bold text-dark" style="font-size:0.75rem">${fac.completion}%</span>
        </div>
      </td>
      <td>
        <span class="fcm-badge ${fac.badgeClass}"><span class="dot"></span>${escapeHtml(fac.statusLabel)}</span>
      </td>
      <td class="text-end">
        <div class="d-flex align-items-center justify-content-end gap-1">
          <button class="fcm-btn-view" onclick="openDrawer(${fac.profile_id})">View Clearance &rarr;</button>
          <div class="dropdown d-inline-block">
            <button class="btn btn-sm btn-light border p-1 px-2" data-bs-toggle="dropdown" style="font-size:0.78rem"><i class="fas fa-ellipsis-h"></i></button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:0.8rem">
              <li><a class="dropdown-item" href="javascript:void(0)" onclick="openDrawer(${fac.profile_id})"><i class="fas fa-search me-2 text-primary"></i>View Requirements Status</a></li>
              <li><a class="dropdown-item" href="javascript:void(0)" onclick="openAuditModal()"><i class="fas fa-clock me-2 text-info"></i>View Audit History</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-primary fw-bold" href="javascript:void(0)" onclick="openForwardModal('${escapeJs(fac.name)}')"><i class="fas fa-share me-2"></i>Forward to Dean / VPAA</a></li>
            </ul>
          </div>
        </div>
      </td>
    </tr>
  `).join('');
  }

  function goToPage(page) {
    currentPage = page;
    renderTable();
  }

  function filterByStatus(status) {
    activeStatusFilter = status;
    currentPage = 1;
    const statusSel = document.getElementById('fcm-status-filter');
    if (statusSel) statusSel.value = status;

    document.querySelectorAll('.fcm-kpi-card').forEach(c => c.classList.remove('active-kpi'));
    if (window.event && window.event.currentTarget) {
      window.event.currentTarget.classList.add('active-kpi');
    }

    renderTable();
  }

  function applyFilters() {
    const statusSel = document.getElementById('fcm-status-filter');
    if (statusSel) activeStatusFilter = statusSel.value;
    currentPage = 1;
    renderTable();
  }

  function resetFilters() {
    const s = document.getElementById('fcm-search'); if (s) s.value = '';
    const st = document.getElementById('fcm-status-filter'); if (st) st.value = 'all';
    const d = document.getElementById('fcm-dept-filter'); if (d) d.value = 'all';
    const emp = document.getElementById('fcm-emp-filter'); if (emp) emp.value = 'all';
    activeStatusFilter = 'all';
    currentPage = 1;
    renderTable();
    showToast('Filters reset to default view.');
  }

  function toggleMoreFilters() {
    const box = document.getElementById('fcm-more-filters');
    if (box) box.classList.toggle('d-none');
  }

  // Load Drawer with live data from SQL
  async function openDrawer(profileId) {
    currentSelectedProfileId = profileId;
    const fac = facultyClearanceRecords.find(f => f.profile_id === profileId) || {};

    // Populate header info immediately
    document.getElementById('dr-faculty-name').innerText = fac.name || 'Faculty Member';
    document.getElementById('dr-faculty-sub').innerText = `${fac.faculty_no || 'FAC'} · ${fac.dept || 'Department'}`;
    document.getElementById('dr-position').innerText = fac.position || fac.academic_rank || 'Faculty Professor';
    document.getElementById('dr-subdate').innerText = fac.subDate || 'Awaiting submission';

    const reqsContainer = document.getElementById('dr-reqs-list');
    reqsContainer.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary opacity-50"></i><br>Loading live clearance records from database...</div>';

    const drawerEl = document.getElementById('clearanceDrawer');
    if (drawerEl) {
      bootstrap.Offcanvas.getOrCreateInstance(drawerEl).show();
    }

    try {
      const resp = await fetch(`${clearanceApi}?action=review&faculty_id=${profileId}`);
      if (!resp.ok) throw new Error(`HTTP error ${resp.status}`);
      const data = await resp.json();
      if (!data.ok) throw new Error(data.error || 'Failed to load clearance record.');

      currentReviewData = data;
      const c = data.clearance || {};
      currentSelectedClearanceId = c.clearance_id || null;

      // Update progress
      const progress = c.progress || 0;
      const approvedItems = c.approved_items || 0;
      const totalItems = c.total_items || 6;
      document.getElementById('dr-prog-label').innerText = `${approvedItems} of ${totalItems} Requirements Approved`;
      document.getElementById('dr-prog-pct').innerText = `${progress}%`;
      document.getElementById('dr-prog-bar').style.width = `${progress}%`;
      document.getElementById('dr-complete-badge').innerText = `${approvedItems} of ${totalItems} approved`;

      // Update callout banner
      const warnTitle = document.getElementById('dr-warn-title');
      const warnSub = document.getElementById('dr-warn-sub');
      const warnDesc = document.getElementById('dr-warn-desc');
      if (progress >= 100) {
        warnTitle.innerText = 'Clearance Completed';
        warnSub.innerText = '100% of requirements cleared';
        warnDesc.innerText = 'All institutional clearance requirements have been verified and approved by the respective offices.';
      } else {
        warnTitle.innerText = 'Clearance In Progress';
        warnSub.innerText = `${approvedItems} of ${totalItems} requirements approved (${progress}%)`;
        warnDesc.innerText = 'Tracking individual office verification and requirement approval statuses.';
      }

      // Render 6 requirements checklist
      renderDrawerRequirements(c.items || []);

      // Render History
      renderDrawerHistory(c.approval_history || []);

    } catch (err) {
      reqsContainer.innerHTML = `<div class="alert alert-danger py-3 small"><i class="fas fa-exclamation-triangle me-2"></i>Error loading clearance records: ${escapeHtml(err.message)}</div>`;
    }
  }

  function parseItemRemarks(rawRemarks, scopeData) {
    if (!rawRemarks && !scopeData) {
      return { text: '', passed: [], failed: [], isScopeLocked: false };
    }

    let text = typeof rawRemarks === 'string' ? rawRemarks : '';
    let passed = (scopeData && Array.isArray(scopeData.passed)) ? scopeData.passed : [];
    let failed = (scopeData && Array.isArray(scopeData.failed)) ? scopeData.failed : [];
    let isScopeLocked = !!(scopeData && (scopeData.locked || scopeData.confirmed));

    const scopeMatch = text.match(/<!--SCOPE_STATE:(.*?)-->/s);
    if (scopeMatch) {
      try {
        const parsed = JSON.parse(scopeMatch[1]);
        if (Array.isArray(parsed.passed) && passed.length === 0) passed = parsed.passed;
        if (Array.isArray(parsed.failed) && failed.length === 0) failed = parsed.failed;
        if (parsed.locked || parsed.confirmed) isScopeLocked = true;
      } catch (e) {}
      text = text.replace(/<!--SCOPE_STATE:.*?-->/gs, '');
    }

    text = text.replace(/<!--.*?-->/gs, '').trim();

    return { text, passed, failed, isScopeLocked };
  }

  function cleanRemarkText(rem) {
    if (!rem || typeof rem !== 'string') return '';
    return rem.replace(/<!--SCOPE_STATE:.*?-->/gs, '').replace(/<!--.*?-->/gs, '').trim();
  }

  function renderDrawerRequirementsOLD_REPLACED(items) {
    const container = document.getElementById('dr-reqs-list');
    if (!container) return;

    if (!items || items.length === 0) {
      container.innerHTML = '<div class="text-center py-4 text-muted small">No clearance requirement items found for this record.</div>';
      return;
    }

    const officeMeta = {
      'Academic Clearance': { office: 'Academic Affairs & Registrar', icon: 'fa-graduation-cap' },
      'Library Clearance': { office: 'University Library', icon: 'fa-book-bookmark' },
      'Financial Clearance': { office: 'Accounting & Finance Office', icon: 'fa-receipt' },
      'Property Clearance': { office: 'Property & Custodian Office', icon: 'fa-boxes-stacked' },
      'HR Clearance': { office: 'Human Resources Office', icon: 'fa-user-check' },
      'Department Clearance': { office: 'Department Head & Dean', icon: 'fa-building-columns' }
    };

    const approvals = (currentReviewData && currentReviewData.clearance && currentReviewData.clearance.office_approvals) || {};

    container.innerHTML = items.map(it => {
      const isApproved = it.status === 'Cleared' || it.status === 'Approved' || it.status === 'Verified' || it.status === 'Signed';
      const isDenied = ['Denied', 'Hold', 'With Deficiency', 'On Hold', 'Rejected', 'Returned'].includes(it.status);
      const isUnderReview = ['Pending Review', 'Under Verification', 'Pending Verification', 'Submitted'].includes(it.status);
      const hasFile = Boolean(it.file_name || it.original_name || it.file_path);
      const rawFileName = it.file_name || it.original_name || (it.file_path ? it.file_path.split('/').pop() : 'clearance-document.pdf');
      const displayCleanName = rawFileName.replace(/\.[^/.]+$/, "");

      const meta = officeMeta[it.name] || { office: 'Clearance Office', icon: 'fa-file-alt' };
      const officeApp = approvals[it.name] || {};
      const approverText = officeApp.approver_name ? `${officeApp.approver_name}${officeApp.approver_role ? ' (' + officeApp.approver_role + ')' : ''}` : '';

      // Clean remarks & criteria parsing
      const remInfo = parseItemRemarks(it.remarks, it.scope_data);
      const cleanRemarks = remInfo.text;
      const passedItems = remInfo.passed || [];
      const failedItems = remInfo.failed || [];
      const hasAnyRemarksOrCriteria = cleanRemarks || passedItems.length > 0 || failedItems.length > 0;

      let statusBadge = '';
      let icon = meta.icon;
      let iconBg = '#f8fafc';
      let iconColor = '#64748b';
      let cardBorder = '#e2e8f0';
      let cardBg = '#ffffff';

      if (isApproved) {
        statusBadge = `
          <span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:0.75rem;padding:5px 11px;border-radius:20px;font-weight:600">
            <i class="fas fa-check-circle me-1"></i>Approved
          </span>`;
        icon = 'fa-check';
        iconBg = '#f0fdf4';
        iconColor = '#16a34a';
        cardBorder = '#bbf7d0';
        cardBg = '#fafdfb';
      } else if (isDenied) {
        statusBadge = `
          <span class="badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:0.75rem;padding:5px 11px;border-radius:20px;font-weight:600">
            <i class="fas fa-times-circle me-1"></i>Denied
          </span>`;
        icon = 'fa-times';
        iconBg = '#fef2f2';
        iconColor = '#dc2626';
        cardBorder = '#fecaca';
        cardBg = '#fffcfc';
      } else if (hasFile || isUnderReview) {
        statusBadge = `
          <span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:0.75rem;padding:5px 11px;border-radius:20px;font-weight:600">
            <i class="fas fa-hourglass-half me-1"></i>Under Review
          </span>`;
        icon = 'fa-clock';
        iconBg = '#eff6ff';
        iconColor = '#2563eb';
        cardBorder = '#bfdbfe';
        cardBg = '#fafcff';
      } else {
        statusBadge = `
          <span class="badge" style="background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;font-size:0.75rem;padding:5px 11px;border-radius:20px;font-weight:600">
            <i class="fas fa-minus-circle me-1"></i>Pending Submission
          </span>`;
        icon = meta.icon;
        iconBg = '#fffbeb';
        iconColor = '#d97706';
      }

      const fileViewUrl = it.file_path
        ? `${clearanceApi}?action=file&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}&filename=${encodeURIComponent(rawFileName)}`
        : (it.id ? `${clearanceApi}?action=file&item_id=${it.id}&filename=${encodeURIComponent(rawFileName)}` : '#');
      const fileDownloadUrl = it.file_path
        ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}&filename=${encodeURIComponent(rawFileName)}`
        : (it.id ? `${clearanceApi}?action=file&download=1&item_id=${it.id}&filename=${encodeURIComponent(rawFileName)}` : '#');

      return `
      <div class="fcm-req-card mb-3" style="border:1px solid ${cardBorder};background:${cardBg}">
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="fcm-attn-icon" style="background:${iconBg};color:${iconColor}">
              <i class="fas ${icon}"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-bold text-dark" style="font-size:0.91rem">${escapeHtml(it.name)}</span>
                <span class="text-muted" style="font-size:0.75rem">&bull; ${escapeHtml(meta.office)}</span>
              </div>
              <div class="text-muted" style="font-size:0.74rem;margin-top:2px">
                ${isApproved ? `
                  <span class="text-success fw-medium"><i class="fas fa-check me-1"></i>Signed off &amp; verified</span>
                  ${approverText ? ` &middot; <span class="text-muted">${escapeHtml(approverText)}</span>` : ''}
                  ${it.cleared_at ? ` &middot; <span class="text-muted">${escapeHtml(it.cleared_at)}</span>` : ''}
                ` : isDenied ? `
                  <span class="text-danger fw-semibold"><i class="fas fa-exclamation-circle me-1"></i>Flagged with deficiency / Denied</span>
                ` : hasFile ? `
                  <span class="text-primary"><i class="fas fa-file-check me-1"></i>Submitted &middot; Awaiting office approval</span>
                ` : `
                  <span class="text-muted"><i class="fas fa-circle-notch me-1"></i>Awaiting faculty document submission</span>
                `}
              </div>
            </div>
          </div>
          <div>
            ${statusBadge}
          </div>
        </div>

        ${isDenied && hasAnyRemarksOrCriteria ? `
          <div class="mt-2.5 p-2.5 px-3 rounded-2" style="background:#fef2f2;border:1px solid #fee2e2;color:#991b1b;font-size:0.77rem;line-height:1.45">
            <div class="fw-bold mb-1"><i class="fas fa-ban me-1 text-danger"></i>Reason for Denial / Deficiency:</div>
            <div>${cleanRemarks ? escapeHtml(cleanRemarks) : 'Flagged with deficiency during office verification.'}</div>
            ${failedItems.length > 0 ? `
              <div class="mt-2 pt-2 border-top border-danger-subtle">
                <div class="fw-bold text-danger mb-1" style="font-size:0.72rem">Unfulfilled Criteria:</div>
                <div class="d-flex flex-wrap gap-1">
                  ${failedItems.map(f => `
                    <span class="badge bg-white text-danger border border-danger-subtle fw-normal py-1 px-2" style="font-size:0.71rem">
                      <i class="fas fa-times text-danger me-1"></i>${escapeHtml(f)}
                    </span>
                  `).join('')}
                </div>
              </div>
            ` : ''}
          </div>
        ` : ''}

        ${isApproved && hasAnyRemarksOrCriteria ? `
          <div class="mt-2 p-2.5 px-3 rounded-2" style="background:#f0fdf4;border:1px solid #dcfce7;color:#166534;font-size:0.76rem">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
              <span><i class="fas fa-check-circle me-1 text-success"></i><strong>Office Verification:</strong> ${cleanRemarks ? escapeHtml(cleanRemarks) : 'All required checklist items verified and cleared.'}</span>
              ${passedItems.length > 0 ? `<span class="badge bg-success text-white rounded-pill px-2 py-0.5" style="font-size:0.68rem">${passedItems.length} verified criteria</span>` : ''}
            </div>
            ${passedItems.length > 0 ? `
              <div class="mt-2 pt-2 border-top border-success-subtle">
                <div class="text-muted small mb-1 fw-semibold" style="font-size:0.7rem">Verified Scope Items:</div>
                <div class="d-flex flex-wrap gap-1">
                  ${passedItems.map(p => `
                    <span class="badge bg-white text-success border border-success-subtle fw-normal py-1 px-2" style="font-size:0.71rem">
                      <i class="fas fa-check text-success me-1"></i>${escapeHtml(p)}
                    </span>
                  `).join('')}
                </div>
              </div>
            ` : ''}
          </div>
        ` : ''}

        ${!isApproved && !isDenied && cleanRemarks ? `
          <div class="mt-2 p-2 px-3 rounded-2" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:0.76rem">
            <i class="fas fa-comment-alt me-1 text-muted"></i><strong>Notes:</strong> ${escapeHtml(cleanRemarks)}
          </div>
        ` : ''}

        <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top flex-wrap gap-2">
          <div class="text-muted text-truncate" style="font-size:0.74rem;max-width:55%">
            ${hasFile ? `
              <span class="text-dark fw-medium text-truncate d-inline-block" style="max-width:100%" title="${escapeHtml(rawFileName)}">
                <i class="fas fa-file-pdf text-danger me-1.5"></i>${escapeHtml(rawFileName)}
              </span>
            ` : `
              <span class="text-muted"><i class="fas fa-paperclip me-1 opacity-50"></i>No file attached</span>
            `}
          </div>
          <div class="d-flex align-items-center gap-2">
            ${hasFile ? `
              <button type="button" class="fcm-btn-view" style="padding:4px 11px;font-size:0.74rem;cursor:pointer" onclick="previewClearanceDoc('${escapeHtml(fileViewUrl)}', '${escapeHtml(fileDownloadUrl)}', '${escapeHtml(rawFileName)}', '${escapeHtml(it.name)}')">
                <i class="fas fa-eye me-1"></i>View Document
              </button>
              <a href="${fileViewUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light border py-1 px-2 text-muted" title="Open in new tab" style="font-size:0.72rem;line-height:1">
                <i class="fas fa-external-link-alt"></i>
              </a>
              <a href="${fileDownloadUrl}" class="fcm-req-action-btn text-secondary text-decoration-none" style="font-size:0.74rem">
                <i class="fas fa-download me-1"></i>Download
              </a>
            ` : `
              <span class="badge bg-light text-muted border" style="font-size:0.7rem;font-weight:normal">No Document Attached</span>
            `}
          </div>
        </div>
      </div>`;
    }).join('');
  }

  function renderDrawerRequirements(items) {
    const container = document.getElementById('dr-reqs-list');
    if (!container) return;

    if (!items || items.length === 0) {
      container.innerHTML = '<div class="text-center py-4 text-muted small">No clearance requirement items found for this record.</div>';
      return;
    }

    const officeMeta = {
      'Academic Clearance':   { office: 'Academic Affairs & Registrar',      icon: 'fa-graduation-cap' },
      'Library Clearance':    { office: 'University Library',                 icon: 'fa-book-bookmark' },
      'Financial Clearance':  { office: 'Accounting & Finance Office',        icon: 'fa-receipt' },
      'Property Clearance':   { office: 'Property & Custodian Office',        icon: 'fa-boxes-stacked' },
      'HR Clearance':         { office: 'Human Resources Office',             icon: 'fa-user-check' },
      'Department Clearance': { office: 'Department Head & Dean',             icon: 'fa-building-columns' }
    };

    const approvals = (currentReviewData && currentReviewData.clearance && currentReviewData.clearance.office_approvals) || {};

    container.innerHTML = items.map(it => {
      const isApproved    = ['Cleared','Approved','Verified','Signed'].includes(it.status);
      const isDenied      = ['Denied','Hold','With Deficiency','On Hold','Rejected','Returned'].includes(it.status);
      const isUnderReview = ['Pending Review','Under Verification','Pending Verification','Submitted'].includes(it.status);
      const hasFile       = Boolean(it.file_name || it.original_name || it.file_path);
      const rawFileName   = it.file_name || it.original_name || (it.file_path ? it.file_path.split('/').pop() : 'clearance-document.pdf');

      const meta        = officeMeta[it.name] || { office: 'Clearance Office', icon: 'fa-file-alt' };
      const officeApp   = approvals[it.name] || {};
      const approverName = officeApp.approver_name || '';
      const clearedAt   = it.cleared_at || officeApp.cleared_at || '';
      const remInfo     = parseItemRemarks(it.remarks, it.scope_data);
      const cleanRemarks = remInfo.text;

      // --- status badge & card theming ---
      let statusBadge, iconBg, iconColor, icon, cardBorder;
      if (isApproved) {
        statusBadge  = `<span style="display:inline-flex;align-items:center;gap:5px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:0.73rem;padding:3px 10px;border-radius:20px;font-weight:600"><span style="width:6px;height:6px;border-radius:50%;background:#10b981;flex-shrink:0"></span>Completed</span>`;
        icon = 'fa-check'; iconBg = '#f0fdf4'; iconColor = '#16a34a'; cardBorder = '#bbf7d0';
      } else if (isDenied) {
        statusBadge  = `<span style="display:inline-flex;align-items:center;gap:5px;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:0.73rem;padding:3px 10px;border-radius:20px;font-weight:600"><span style="width:6px;height:6px;border-radius:50%;background:#ef4444;flex-shrink:0"></span>Returned</span>`;
        icon = 'fa-times'; iconBg = '#fef2f2'; iconColor = '#dc2626'; cardBorder = '#fecaca';
      } else if (hasFile || isUnderReview) {
        statusBadge  = `<span style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:0.73rem;padding:3px 10px;border-radius:20px;font-weight:600"><span style="width:6px;height:6px;border-radius:50%;background:#3b82f6;flex-shrink:0"></span>For Verification</span>`;
        icon = 'fa-clock'; iconBg = '#eff6ff'; iconColor = '#2563eb'; cardBorder = '#bfdbfe';
      } else {
        statusBadge  = `<span style="display:inline-flex;align-items:center;gap:5px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;font-size:0.73rem;padding:3px 10px;border-radius:20px;font-weight:600"><span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;flex-shrink:0"></span>Pending</span>`;
        icon = meta.icon; iconBg = '#fffbeb'; iconColor = '#d97706'; cardBorder = '#e2e8f0';
      }

      // file URLs
      const fileViewUrl     = it.file_path
        ? `${clearanceApi}?action=file&path=${encodeURIComponent(it.file_path)}&item_id=${it.id||0}&filename=${encodeURIComponent(rawFileName)}`
        : (it.id ? `${clearanceApi}?action=file&item_id=${it.id}&filename=${encodeURIComponent(rawFileName)}` : '#');
      const fileDownloadUrl = it.file_path
        ? `${clearanceApi}?action=file&download=1&path=${encodeURIComponent(it.file_path)}&item_id=${it.id||0}&filename=${encodeURIComponent(rawFileName)}`
        : (it.id ? `${clearanceApi}?action=file&download=1&item_id=${it.id}&filename=${encodeURIComponent(rawFileName)}` : '#');

      // sub-info line
      let subInfo = '';
      if (isApproved) {
        subInfo = `<span class="text-muted" style="font-size:0.73rem">Verified by ${escapeHtml(approverName || 'Faculty Admin')}${clearedAt ? ' &middot; ' + escapeHtml(clearedAt) : ''}</span>`;
      } else if (isDenied) {
        subInfo = `<span class="text-danger" style="font-size:0.73rem"><i class="fas fa-exclamation-circle me-1"></i>Flagged with deficiency${cleanRemarks ? ' &middot; ' + escapeHtml(cleanRemarks) : ''}</span>`;
      } else if (hasFile) {
        subInfo = `<span class="text-primary" style="font-size:0.73rem"><i class="fas fa-file-check me-1"></i>Document submitted &middot; Awaiting office approval</span>`;
      } else {
        subInfo = `<span class="text-muted" style="font-size:0.73rem">Awaiting faculty document submission &middot; No document submitted</span>`;
      }

      // escaped strings for inline onclick attrs
      const eViewUrl  = escapeHtml(fileViewUrl);
      const eDlUrl    = escapeHtml(fileDownloadUrl);
      const eName     = escapeHtml(rawFileName);
      const eReqName  = escapeHtml(it.name);
      const itemId    = parseInt(it.id) || 0;

      return `
      <div style="border:1px solid ${cardBorder};background:#ffffff;border-radius:12px;padding:14px 16px;margin-bottom:10px;transition:box-shadow 0.18s"
           onmouseenter="this.style.boxShadow='0 4px 14px rgba(0,0,0,0.06)'" onmouseleave="this.style.boxShadow=''">
        <!-- Header row -->
        <div class="d-flex align-items-center justify-content-between gap-2">
          <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
            <div style="width:36px;height:36px;border-radius:10px;background:${iconBg};color:${iconColor};display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0">
              <i class="fas ${icon}"></i>
            </div>
            <div class="overflow-hidden">
              <div class="fw-bold text-dark" style="font-size:0.9rem">${eReqName}</div>
              <div style="margin-top:2px">${subInfo}</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-shrink-0">
            ${statusBadge}
            <div class="dropdown">
              <button class="btn btn-sm btn-light border px-2" data-bs-toggle="dropdown" style="font-size:0.75rem;line-height:1.4">
                <i class="fas fa-ellipsis-h"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:0.8rem">
                ${hasFile ? `<li><a class="dropdown-item" href="javascript:void(0)" onclick="previewClearanceDoc('${eViewUrl}','${eDlUrl}','${eName}','${eReqName}')"><i class="fas fa-eye me-2 text-primary"></i>View Document</a></li>` : ''}
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="verifyRequirement(${itemId},'${eReqName}')"><i class="fas fa-check me-2 text-success"></i>Verify</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="openReturnModal(${itemId},'${eReqName}')"><i class="fas fa-undo me-2 text-danger"></i>Return</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="openRemarkModal(${itemId},'${eReqName}')"><i class="fas fa-comment me-2 text-muted"></i>Add Remarks</a></li>
              </ul>
            </div>
          </div>
        </div>
        <!-- Action bar -->
        <div class="d-flex align-items-center gap-0 pt-2 mt-2" style="border-top:1px solid #f1f5f9">
          ${hasFile ? `
            <button type="button" onclick="previewClearanceDoc('${eViewUrl}','${eDlUrl}','${eName}','${eReqName}')"
              class="fcm-req-action-btn" style="color:#2563eb">
              <i class="fas fa-eye"></i> View Document
            </button>
            <span class="text-muted mx-1" style="font-size:0.7rem">&bull;</span>
          ` : `
            <span class="fcm-req-action-btn" style="color:#94a3b8;cursor:default">
              <i class="fas fa-paperclip opacity-50"></i> No document
            </span>
            <span class="text-muted mx-1" style="font-size:0.7rem">&bull;</span>
          `}
          <button type="button" onclick="verifyRequirement(${itemId},'${eReqName}')"
            class="fcm-req-action-btn" style="color:#16a34a">
            <i class="fas fa-check"></i> Verify
          </button>
          <span class="text-muted mx-1" style="font-size:0.7rem">&bull;</span>
          <button type="button" onclick="openReturnModal(${itemId},'${eReqName}')"
            class="fcm-req-action-btn" style="color:#ea580c">
            <i class="fas fa-undo"></i> Return
          </button>
          <span class="text-muted mx-1" style="font-size:0.7rem">&bull;</span>
          <button type="button" onclick="openRemarkModal(${itemId},'${eReqName}')"
            class="fcm-req-action-btn" style="color:#64748b">
            <i class="fas fa-comment"></i> Add Remarks
          </button>
        </div>
      </div>`;
    }).join('');
  }

  function renderDrawerHistory(history) {
    const histEl = document.getElementById('dr-hist-list');
    if (!histEl) return;

    if (!history || history.length === 0) {
      histEl.innerHTML = '<div class="p-3 text-center text-muted small">No audit activity logged for this clearance yet.</div>';
      return;
    }

    histEl.innerHTML = history.map(h => {
      const cleanHRemark = cleanRemarkText(h.remarks);
      return `
      <div class="fcm-hist-row">
        <div class="d-flex align-items-center gap-3">
          <div class="fcm-act-icon"><i class="fas fa-check"></i></div>
          <div>
            <div class="fw-bold text-dark" style="font-size:0.84rem">${escapeHtml(h.action || 'Updated')}</div>
            <div class="text-muted" style="font-size:0.73rem">${escapeHtml(h.office || 'Clearance')} ${cleanHRemark ? '&middot; ' + escapeHtml(cleanHRemark) : ''}</div>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-bold text-dark" style="font-size:0.8rem">${escapeHtml(h.performed_by_name || 'System')}</div>
          <div class="text-muted" style="font-size:0.72rem">${escapeHtml(h.created_at || 'Recent')}</div>
        </div>
      </div>`;
    }).join('');
  }

  // Action: Verify Requirement
  async function verifyRequirement(itemId, reqName) {
    if (!confirm(`Confirm verification for "${reqName}"?\nThis will stamp your official approval on this requirement in the database.`)) {
      return;
    }

    try {
      const fd = new FormData();
      fd.append('action', 'review-item');
      fd.append('item_id', itemId);
      fd.append('decision', 'approve');
      fd.append('remark', 'Verified and cleared by Faculty Admin.');

      const resp = await fetch(clearanceApi, { method: 'POST', body: fd });
      const res = await resp.json();
      if (!res.ok) throw new Error(res.error || 'Failed to verify requirement.');

      showToast(`${reqName} verified and cleared successfully!`);
      if (currentSelectedProfileId) {
        await openDrawer(currentSelectedProfileId);
      }
      refreshLiveSummary();
    } catch (err) {
      alert(`Error verifying requirement: ${err.message}`);
    }
  }

  function verifyFirstPendingReq() {
    if (!currentReviewData || !currentReviewData.clearance) {
      alert('Please select a faculty member clearance first.');
      return;
    }
    const items = currentReviewData.clearance.items || [];
    const pending = items.find(it => it.status !== 'Cleared' && it.status !== 'Approved' && it.file_name);
    if (!pending) {
      alert('All uploaded requirements are already verified, or no files have been uploaded yet.');
      return;
    }
    verifyRequirement(pending.id, pending.name);
  }

  function previewClearanceDoc(viewUrl, downloadUrl, fileName, reqName) {
    if (!viewUrl || viewUrl === '#') {
      alert('Document URL is not available.');
      return;
    }
    const modalEl = document.getElementById('docPreviewModal');
    if (!modalEl) {
      window.open(viewUrl, '_blank');
      return;
    }
    const titleEl = document.getElementById('docPreviewTitle');
    const subEl = document.getElementById('docPreviewSub');
    const tabEl = document.getElementById('docPreviewOpenTab');
    const dlEl = document.getElementById('docPreviewDownload');
    const loadingEl = document.getElementById('docPreviewLoading');
    const frame = document.getElementById('docPreviewFrame');

    if (titleEl) titleEl.innerText = fileName || 'Uploaded Document';
    if (subEl) subEl.innerText = reqName ? `${reqName} · Clearance Attachment` : 'Clearance Requirement Attachment';
    if (tabEl) tabEl.href = viewUrl;
    if (dlEl) {
      dlEl.href = downloadUrl;
      dlEl.setAttribute('download', fileName || 'clearance-document.pdf');
    }
    if (loadingEl) loadingEl.classList.remove('d-none');
    if (frame) {
      const cacheBustUrl = viewUrl + (viewUrl.includes('?') ? '&' : '?') + '_t=' + Date.now();
      frame.src = cacheBustUrl;
    }

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  function viewAllAttachedDocs() {
    if (!currentReviewData || !currentReviewData.clearance) {
      alert('Please select a faculty member clearance first.');
      return;
    }
    const items = (currentReviewData.clearance.items || []).filter(it => it.file_name || it.original_name || it.file_path);
    if (items.length === 0) {
      alert('No documents attached to this clearance yet.');
      return;
    }
    items.forEach(it => {
      const rawFileName = it.file_name || it.original_name || (it.file_path ? it.file_path.split('/').pop() : 'clearance-document.pdf');
      const url = it.file_path
        ? `${clearanceApi}?action=file&path=${encodeURIComponent(it.file_path)}&item_id=${it.id || 0}&filename=${encodeURIComponent(rawFileName)}`
        : `${clearanceApi}?action=file&item_id=${it.id}&filename=${encodeURIComponent(rawFileName)}`;
      window.open(url, '_blank');
    });
  }

  // Action: Return Modal
  function openReturnReqModal(itemId, reqName) {
    currentReturnItemId = itemId;
    const input = document.getElementById('ret-modal-req');
    if (input) input.value = reqName || 'Requirement';
    const modal = document.getElementById('returnReqModal');
    if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
  }

  // Alias for card action buttons
  function openReturnModal(itemId, reqName) { openReturnReqModal(itemId, reqName); }

  async function confirmReturnReq() {
    const reason = document.getElementById('ret-modal-reason')?.value || 'Requirement flagged with deficiency';
    const notes = document.querySelector('#returnReqModal textarea')?.value || '';
    const fullRemark = `[With Deficiency] ${reason}${notes ? ' - Instructions: ' + notes : ''}`;

    if (!currentReturnItemId && currentReviewData && currentReviewData.clearance) {
      const items = currentReviewData.clearance.items || [];
      const itemToReturn = items.find(it => it.file_name) || items[0];
      if (itemToReturn) currentReturnItemId = itemToReturn.id;
    }

    if (!currentReturnItemId) {
      alert('Please select a specific requirement to return.');
      return;
    }

    try {
      const fd = new FormData();
      fd.append('action', 'review-item');
      fd.append('item_id', currentReturnItemId);
      fd.append('decision', 'deny');
      fd.append('remark', fullRemark);

      const resp = await fetch(clearanceApi, { method: 'POST', body: fd });
      const res = await resp.json();
      if (!res.ok) throw new Error(res.error || 'Failed to return requirement.');

      const modal = document.getElementById('returnReqModal');
      if (modal) bootstrap.Modal.getInstance(modal)?.hide();

      showToast('Clearance requirement returned to faculty for correction.');
      if (currentSelectedProfileId) {
        await openDrawer(currentSelectedProfileId);
      }
      refreshLiveSummary();
    } catch (err) {
      alert(`Error returning requirement: ${err.message}`);
    }
  }

  // Action: Remark Modal
  function openRemarkModal(itemId, targetName) {
    currentRemarkItemId = itemId;
    const input = document.getElementById('rem-modal-target');
    if (input) input.value = targetName || 'Clearance';
    const modal = document.getElementById('addRemarkModal');
    if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
  }

  async function confirmSaveRemark() {
    const text = document.getElementById('rem-modal-text')?.value || '';
    if (!text.trim()) {
      alert('Please enter remark text.');
      return;
    }

    if (currentRemarkItemId > 0) {
      try {
        const fd = new FormData();
        fd.append('action', 'review-item');
        fd.append('item_id', currentRemarkItemId);
        fd.append('decision', 'hold');
        fd.append('remark', text);

        const resp = await fetch(clearanceApi, { method: 'POST', body: fd });
        const res = await resp.json();
        if (!res.ok) throw new Error(res.error || 'Failed to save remark.');
      } catch (e) { }
    }

    const modal = document.getElementById('addRemarkModal');
    if (modal) bootstrap.Modal.getInstance(modal)?.hide();
    showToast('Administrative remark saved into database audit history.');
    if (currentSelectedProfileId) {
      await openDrawer(currentSelectedProfileId);
    }
    refreshLiveSummary();
  }

  // Action: Forward Modal
  function openForwardModal(name) {
    const modal = document.getElementById('forwardAuthModal');
    if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
  }

  function confirmForwardAuth() {
    const auth = document.getElementById('fwd-auth-select')?.value || 'Approving Authority';
    const modal = document.getElementById('forwardAuthModal');
    if (modal) bootstrap.Modal.getInstance(modal)?.hide();
    showToast(`Clearance endorsed and forwarded to ${auth} for final signature.`);
  }

  function openReportsModal() {
    downloadReport('pdf');
  }

  function downloadReport(format) {
    window.print();
  }

  function selectReportType(el, type) {
    document.querySelectorAll('.fcm-report-box').forEach(b => b.classList.remove('active-report'));
    el.classList.add('active-report');
    showToast(`Selected report preset: ${type.toUpperCase()}`);
  }

  function openAuditModal() {
    if (currentSelectedProfileId) {
      const drawerEl = document.getElementById('clearanceDrawer');
      if (drawerEl) {
        bootstrap.Offcanvas.getOrCreateInstance(drawerEl).show();
        document.getElementById('dr-hist-list')?.scrollIntoView({ behavior: 'smooth' });
      }
    } else {
      showToast('Select a faculty member from the table to view their complete audit history.');
    }
  }

  // Refresh table and KPI counts from database
  async function refreshLiveSummary() {
    try {
      const resp = await fetch(`${clearanceApi}?action=summary`);
      if (!resp.ok) return;
      const data = await resp.json();
      if (!data.ok || !data.rows) return;

      facultyClearanceRecords = data.rows.map(r => {
        const c = r.clearance || {};
        const stLower = (c.status || r.overall_status || 'not submitted').toLowerCase();
        let statusKey = 'pending';
        let statusLabel = 'Pending';
        let badgeClass = 'fcm-badge-pending';

        if (stLower === 'completed' || stLower === 'cleared') {
          statusKey = 'cleared';
          statusLabel = 'Cleared';
          badgeClass = 'fcm-badge-cleared';
        } else if (stLower.includes('final') || (c.progress || 0) >= 83) {
          statusKey = 'ready';
          statusLabel = 'Ready for Final Approval';
          badgeClass = 'fcm-badge-ready';
        } else if (stLower.includes('deficiency') || stLower.includes('action') || stLower.includes('denied') || stLower.includes('returned')) {
          statusKey = 'returned';
          statusLabel = 'Returned';
          badgeClass = 'fcm-badge-returned';
        } else if (stLower.includes('verification') || stLower.includes('review') || (c.submitted_items && c.submitted_items > 0)) {
          statusKey = 'for-verification';
          statusLabel = 'For Verification';
          badgeClass = 'fcm-badge-verify';
        }

        const first = r.first_name || '';
        const last = r.last_name || '';
        const initials = ((first[0] || '') + (last[0] || '')).toUpperCase() || 'FP';

        return {
          profile_id: r.id,
          clearance_id: r.clearance_id || null,
          faculty_no: r.faculty_id || `FAC-${r.id}`,
          name: r.name || `${first} ${last}`,
          first_name: first,
          last_name: last,
          initials: initials,
          avatarBg: '#eff6ff',
          avatarColor: '#2563eb',
          dept: r.designated_department || 'General Faculty',
          deptCode: (r.designated_department || '').toLowerCase(),
          period: (data.term?.semester || '1st Sem') + ', AY ' + (data.term?.academic_year || '2026–2027'),
          subDate: r.submitted_at ? new Date(r.submitted_at.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Awaiting submission',
          completion: c.progress || 0,
          status: statusKey,
          statusLabel: statusLabel,
          badgeClass: badgeClass,
          empType: r.employment_status || 'Regular',
          position: r.position || 'Faculty Professor',
          clearance: c,
        };
      });

      const total = facultyClearanceRecords.length;
      const v = facultyClearanceRecords.filter(f => f.status === 'for-verification').length;
      const p = facultyClearanceRecords.filter(f => f.status === 'pending').length;
      const ret = facultyClearanceRecords.filter(f => f.status === 'returned').length;
      const rdy = facultyClearanceRecords.filter(f => f.status === 'ready').length;
      const clr = facultyClearanceRecords.filter(f => f.status === 'cleared').length;

      const totalEl = document.getElementById('kpi-total-val'); if (totalEl) totalEl.innerText = total;
      const verifyEl = document.getElementById('kpi-verify-val'); if (verifyEl) verifyEl.innerText = v;
      const pendEl = document.getElementById('kpi-pending-val'); if (pendEl) pendEl.innerText = p;
      const retEl = document.getElementById('kpi-returned-val'); if (retEl) retEl.innerText = ret;
      const rdyEl = document.getElementById('kpi-ready-val'); if (rdyEl) rdyEl.innerText = rdy;
      const clrEl = document.getElementById('kpi-cleared-val'); if (clrEl) clrEl.innerText = clr;

      renderTable();
    } catch (e) {
      console.error('Error refreshing summary:', e);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c]));
  }

  function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
  }

  function showToast(msg) {
    const toastEl = document.getElementById('fcmToast');
    const msgEl = document.getElementById('fcm-toast-msg');
    if (msgEl) msgEl.innerText = msg;
    if (toastEl) {
      bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 }).show();
    }
  }

  // Initial Run
  document.addEventListener('DOMContentLoaded', () => {
    renderTable();
    document.getElementById('docPreviewModal')?.addEventListener('hidden.bs.modal', () => {
      const frame = document.getElementById('docPreviewFrame');
      if (frame) frame.src = 'about:blank';
    });
  });
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>