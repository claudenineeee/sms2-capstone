<?php
/**
 * SMS 2 - Sidebar Navigation
 * Expects: optional $activeModule (string), optional $activePage (string)
 */
if (!isset($MODULES)) {
    require_once __DIR__ . '/../config/config.php';
}
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/module-controls.php';
require_once __DIR__ . '/nav-icons.php';

$activeModule = $activeModule ?? '';
$activePage   = $activePage ?? '';
$roleKey = getCurrentUserRoleKey();
$isStudentPortal = $activeModule === 'student_portal';
$visibleModules = getVisibleModules($MODULES);
$securitySettingsModule = '';
if (!in_array($roleKey, ['superadmin', 'admin'], true)) {
    foreach ($visibleModules as $securityModuleKey => $_securityModule) {
        if ($securityModuleKey !== 'user-management') {
            $securitySettingsModule = (string) $securityModuleKey;
            break;
        }
    }
}

// ── For students: check if Research Forum is paid ───────────────────────────
$researchForumPaid = false;
if ($isStudentPortal) {
    // If student-portal-page.php already computed this, use it.
    // Otherwise check independently from the payment data source.
    if (isset($researchForumPaid) && $researchForumPaid === true) {
        // already set by student-portal-page.php context
    } else {
        // Standalone check: mirror the same transaction list.
        // In production, replace with a real DB query against payment table.
        $sidebarPayments = [
            ['description' => 'Tuition Down Payment',  'status' => 'Paid'],
            ['description' => 'Registration Fee',       'status' => 'Paid'],
            ['description' => 'Laboratory Fee',         'status' => 'Paid'],
            ['description' => 'Research Forum',         'status' => 'Paid'],
        ];
        foreach ($sidebarPayments as $txn) {
            if (
                stripos($txn['description'], 'Research Forum') !== false &&
                strtolower($txn['status']) === 'paid'
            ) {
                $researchForumPaid = true;
                break;
            }
        }
    }
}

$studentNavGroups = [
    'Overview' => [
        ['slug' => 'dashboard', 'href' => BASE_URL . '/modules/student-portal/pages/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'locked' => false],
    ],
    'Student Information' => [
        ['slug' => 'my-profile',  'href' => BASE_URL . '/modules/student-portal/pages/my-profile.php',  'icon' => 'fa-user',    'label' => 'My Profile',  'locked' => false],
        ['slug' => 'student-id',  'href' => BASE_URL . '/modules/student-portal/pages/student-id.php',  'icon' => 'fa-id-card', 'label' => 'Student ID',  'locked' => false],
    ],
    'Financial' => [
        ['slug' => 'account-balance',  'href' => BASE_URL . '/modules/student-portal/pages/account-balance.php',  'icon' => 'fa-wallet',  'label' => 'Account Balance',  'locked' => false],
        ['slug' => 'payment-history',  'href' => BASE_URL . '/modules/student-portal/pages/payment-history.php',  'icon' => 'fa-receipt', 'label' => 'Payment History',  'locked' => false],
    ],
    'Academics' => [
        ['slug' => 'class-schedule',      'href' => BASE_URL . '/modules/student-portal/pages/class-schedule.php',      'icon' => 'fa-calendar-alt',        'label' => 'Class Schedule',       'locked' => false],
        ['slug' => 'academic-records',    'href' => BASE_URL . '/modules/student-portal/pages/academic-records.php',    'icon' => 'fa-file-alt',            'label' => 'Academic Records',     'locked' => false],
        ['slug' => 'subjects-professors', 'href' => BASE_URL . '/modules/student-portal/pages/subjects-professors.php', 'icon' => 'fa-chalkboard-teacher',  'label' => 'Subject & Professors', 'locked' => false],
        ['slug' => 'grades-portal',       'href' => BASE_URL . '/modules/student-portal/pages/grades-portal.php',       'icon' => 'fa-star-half-alt',       'label' => 'Grades Portal',        'locked' => false],
    ],
    'Research' => [
        ['slug' => 'research-proposal-submission', 'href' => BASE_URL . '/modules/student-portal/pages/research-proposal-submission.php', 'icon' => 'fa-flask',            'label' => 'Research Proposal', 'locked' => false],
        ['slug' => 'submit-documents',             'href' => BASE_URL . '/modules/student-portal/pages/submit-documents.php',             'icon' => 'fa-cloud-upload-alt', 'label' => 'Submit Documents',  'locked' => !$researchForumPaid],
    ],
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=student_portal', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings', 'locked' => false],
    ],
];

$facultyAccountNavGroups = [
    'Dashboard' => [
        ['slug' => '', 'href' => BASE_URL . '/modules/faculty/index.php', 'icon' => 'fa-th-large', 'label' => 'Overview'],
    ],
    'My Research' => [
        ['slug' => 'assigned-research', 'href' => BASE_URL . '/modules/faculty/pages/assigned-research.php', 'icon' => 'fa-flask', 'label' => 'Assigned Research'],
        ['slug' => 'research-details', 'href' => BASE_URL . '/modules/faculty/pages/research-details.php', 'icon' => 'fa-file-alt', 'label' => 'Research Details'],
        ['slug' => 'research-progress', 'href' => BASE_URL . '/modules/faculty/pages/research-progress.php', 'icon' => 'fa-tasks', 'label' => 'Research Progress'],
        ['slug' => 'research-documents', 'href' => BASE_URL . '/modules/faculty/pages/research-documents.php', 'icon' => 'fa-folder-open', 'label' => 'Research Documents'],
    ],
    'Grades Portal' => [
        ['slug' => 'grade-entry', 'href' => BASE_URL . '/modules/faculty/pages/grade-entry.php', 'icon' => 'fa-pen', 'label' => 'Grade Entry'],
        ['slug' => 'grade-records', 'href' => BASE_URL . '/modules/faculty/pages/grade-records.php', 'icon' => 'fa-list-alt', 'label' => 'Grade Records'],
        ['slug' => 'grade-summary', 'href' => BASE_URL . '/modules/faculty/pages/grade-summary.php', 'icon' => 'fa-chart-pie', 'label' => 'Grade Summary'],
    ],
    'Schedule' => [
        ['slug' => 'my-schedule', 'href' => BASE_URL . '/modules/faculty/pages/my-schedule.php', 'icon' => 'fa-calendar', 'label' => 'My Schedule'],
        ['slug' => 'defense-schedule', 'href' => BASE_URL . '/modules/faculty/pages/defense-schedule.php', 'icon' => 'fa-calendar-check', 'label' => 'Defense Schedule'],
    ],
    'Profile' => [
        ['slug' => 'my-profile', 'href' => BASE_URL . '/modules/faculty/pages/my-profile.php', 'icon' => 'fa-user', 'label' => 'My Profile'],
        ['slug' => 'expertise', 'href' => BASE_URL . '/modules/faculty/pages/expertise.php', 'icon' => 'fa-brain', 'label' => 'Expertise'],
        ['slug' => 'availability', 'href' => BASE_URL . '/modules/faculty/pages/availability.php', 'icon' => 'fa-user-check', 'label' => 'Availability'],
    ],
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=faculty', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings'],
    ],
];
//FACULTY MANAGEMENT USER TYPES
$facultyAdministrator = [
    'Dashboard' => [
        ['slug' => 'dashboard',   'href' => BASE_URL . '/modules/faculty/views/administrator/dashboard.php',   'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
    ],
    'Profiles' => [
        ['slug' => 'faculty-profile',   'href' => BASE_URL . '/modules/faculty/views/administrator/faculty-profile.php',   'icon' => 'fa-id-badge',     'label' => 'Faculty Profile'],
        ['slug' => 'faculty-directory', 'href' => BASE_URL . '/modules/faculty/views/administrator/faculty-directory.php', 'icon' => 'fa-address-book', 'label' => 'Faculty Directory'],
    ],    
    'Pending & Approvals' => [    
        ['slug' => 'pending-approvals', 'href' => BASE_URL . '/modules/faculty/views/administrator/pending-approvals.php', 'icon' => 'fa-user-clock',   'label' => 'Pending Approvals'],
    ],
    'Departments' => [
        ['slug' => 'departments', 'href' => BASE_URL . '/modules/faculty/views/administrator/departments.php', 'icon' => 'fa-building',     'label' => 'Departments'],
    ],
];
$facultyDeanNavGroups = [
    'Faculty Profiles' => [
        ['slug' => 'faculty-profile',   'href' => BASE_URL . '/modules/faculty/views/dean/faculty-profile.php',   'icon' => 'fa-id-badge',           'label' => 'Faculty Profile'],
        ['slug' => 'faculty-directory', 'href' => BASE_URL . '/modules/faculty/views/dean/faculty-directory.php', 'icon' => 'fa-address-book',     'label' => 'Faculty Directory'],
        ['slug' => 'teaching-history',  'href' => BASE_URL . '/modules/faculty/views/dean/teaching-history.php',  'icon' => 'fa-chalkboard-teacher', 'label' => 'Teaching History'],
    ],
    'Subject & Attendance' => [
        ['slug' => 'subject-load-tracker',  'href' => BASE_URL . '/modules/faculty/views/dean/subject-load-tracker.php',  'icon' => 'fa-book-open',  'label' => 'Subject Load Tracker'],
        ['slug' => 'attendance-monitoring', 'href' => BASE_URL . '/modules/faculty/views/dean/attendance-monitoring.php', 'icon' => 'fa-user-check', 'label' => 'Attendance Monitoring'],
    ],
    'Leave' => [
        ['slug' => 'leave-application-approval', 'href' => BASE_URL . '/modules/faculty/views/dean/leave-application-approval.php', 'icon' => 'fa-file-signature', 'label' => 'Leave Application & Approval'],
    ],
    'Evaluation & Clearance' => [
        ['slug' => 'evaluation-summary', 'href' => BASE_URL . '/modules/faculty/views/dean/evaluation-summary.php', 'icon' => 'fa-chart-pie',      'label' => 'Evaluation Summary'],
        ['slug' => 'clearance-system',   'href' => BASE_URL . '/modules/faculty/views/dean/clearance-system.php',   'icon' => 'fa-clipboard-check', 'label' => 'Clearance System'],
    ],
];

$facultyDepartmentHeadNavGroups = [
    'Faculty Profiles' => [
        ['slug' => 'faculty-profile',     'href' => BASE_URL . '/modules/faculty/views/department-head/faculty-profile.php',     'icon' => 'fa-id-badge',       'label' => 'Faculty Profile'],
        ['slug' => 'faculty-directory',   'href' => BASE_URL . '/modules/faculty/views/department-head/faculty-directory.php',   'icon' => 'fa-address-book',   'label' => 'Faculty Directory'],
        ['slug' => 'faculty-performance', 'href' => BASE_URL . '/modules/faculty/views/department-head/faculty-performance.php', 'icon' => 'fa-chart-line',     'label' => 'Faculty Performance'],
    ],
    'Schedule & Load' => [
        ['slug' => 'schedule-approval',      'href' => BASE_URL . '/modules/faculty/views/department-head/schedule-approval.php',      'icon' => 'fa-calendar-check', 'label' => 'Schedule Approval'],
        ['slug' => 'teaching-load-approval', 'href' => BASE_URL . '/modules/faculty/views/department-head/teaching-load-approval.php', 'icon' => 'fa-layer-group',    'label' => 'Teaching Load Approval'],
    ],
    'Evaluation' => [
         ['slug' => 'evaluation-summary', 'href' => BASE_URL . '/modules/faculty/views/department-head/evaluation-summary.php', 'icon' => 'fa-chart-pie',      'label' => 'Evaluation Summary'],
    ],
    'Clearance & Reports' => [
        ['slug' => 'faculty-clearance', 'href' => BASE_URL . '/modules/faculty/views/department-head/faculty-clearance.php', 'icon' => 'fa-clipboard-check', 'label' => 'Faculty Clearance'],
        ['slug' => 'reports',           'href' => BASE_URL . '/modules/faculty/views/department-head/reports.php',           'icon' => 'fa-file-alt',        'label' => 'Reports'],
    ],
];

$facultySecretaryNavGroups = [
    'Faculty' => [
        ['slug' => 'dashboard',       'href' => BASE_URL . '/modules/faculty/views/secretary/dashboard.php',       'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['slug' => 'faculty-records', 'href' => BASE_URL . '/modules/faculty/views/secretary/faculty-records.php', 'icon' => 'fa-folder-open',     'label' => 'Faculty Records'],
    ],
    'Documents & Leave' => [
        ['slug' => 'documents',               'href' => BASE_URL . '/modules/faculty/views/secretary/documents.php',               'icon' => 'fa-file-alt',       'label' => 'Documents'],
        ['slug' => 'leave-request-screening', 'href' => BASE_URL . '/modules/faculty/views/secretary/leave-request-screening.php', 'icon' => 'fa-file-signature', 'label' => 'Leave Request'],
    ],
    'Reports' => [
        ['slug' => 'reports', 'href' => BASE_URL . '/modules/faculty/views/secretary/reports.php', 'icon' => 'fa-chart-pie', 'label' => 'Reports'],
    ],
];
$facultyAttendanceMonitoringNavGroups = [
    'Dashboard' => [
        ['slug' => 'monitoring-dashboard', 'href' => BASE_URL . '/modules/faculty/views/monitoring-officer/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
    ],
    'Attendance' => [   
        ['slug' => 'daily-attendance-log', 'href' => BASE_URL . '/modules/faculty/views/monitoring-officer/daily-attendance-log.php', 'icon' => 'fa-calendar-check', 'label' => 'Daily Attendance'],
    ],
    'Reports' => [ 
        ['slug' => 'reports', 'href' => BASE_URL . '/modules/faculty/views/monitoring-officer/reports.php', 'icon' => 'fa-file-alt', 'label' => 'Reports'],
    ],
];
$facultyTeacherNavGroups = [
    'Faculty' => [
        ['slug' => 'dashboard',   'href' => BASE_URL . '/modules/faculty/views/faculty/dashboard.php',   'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['slug' => 'profile',     'href' => BASE_URL . '/modules/faculty/views/faculty/profile.php',     'icon' => 'fa-id-badge',       'label' => 'My Profile'],
        ['slug' => 'attendance',  'href' => BASE_URL . '/modules/faculty/views/faculty/attendance.php',  'icon' => 'fa-user-clock',     'label' => 'My Attendance'],
        ['slug' => 'my-schedule', 'href' => BASE_URL . '/modules/faculty/views/faculty/my-schedule.php', 'icon' => 'fa-calendar-alt',   'label' => 'My Schedule'],
    ],
    'Documents & Leave' => [
        ['slug' => 'leave-request',  'href' => BASE_URL . '/modules/faculty/views/faculty/leave-request.php',  'icon' => 'fa-file-signature', 'label' => 'Leave Request'],
        ['slug' => 'teaching-load',   'href' => BASE_URL . '/modules/faculty/views/faculty/teaching-load.php',   'icon' => 'fa-book-open',      'label' => 'Teaching Load'],
        ['slug' => 'my-clearance',    'href' => BASE_URL . '/modules/faculty/views/faculty/my-clearance.php',    'icon' => 'fa-clipboard-check','label' => 'My Clearance'],
        ['slug' => 'peer-evaluation', 'href' => BASE_URL . '/modules/faculty/views/faculty/peer-evaluation.php', 'icon' => 'fa-star',           'label' => 'Evaluation'],
    ],
    'Notifications' => [
        ['slug' => 'notification', 'href' => BASE_URL . '/modules/faculty/views/faculty/notifications.php', 'icon' => 'fa-bell', 'label' => 'Notifications'],
    ],
];

?>
<aside class="sms-sidebar" id="smsSidebar" aria-label="Main navigation">
    <nav class="sidebar-nav" id="smsSidebarAccordion">
        <ul class="nav flex-column">
            <?php if ($isStudentPortal): ?>
                <?php foreach ($studentNavGroups as $groupLabel => $groupItems): ?>
                    <li class="nav-item sidebar-group-label">
                        <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                    </li>
                    <?php foreach ($groupItems as $item): ?>
                        <?php
                        $isLocked  = !empty($item['locked']);
                        $linkClass = ($activeModule === 'student_portal' && $activePage === $item['slug']) ? 'active' : '';
                        if ($isLocked) { $linkClass .= ' nav-link-locked'; }
                        ?>
                        <li class="nav-item">
                            <?php if ($isLocked): ?>
                                <span class="nav-link sidebar-sub <?= $linkClass ?>"
                                      data-title="<?= htmlspecialchars($item['label']) ?> (Locked)"
                                      title="<?= htmlspecialchars($item['label']) ?> — Pay Research Forum to unlock"
                                      style="cursor:not-allowed;opacity:0.5;">
                                    <i class="fas fa-lock me-1" aria-hidden="true" style="font-size:0.75rem;"></i>
                                    <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </span>
                            <?php else: ?>
                                <a class="nav-link sidebar-sub <?= $linkClass ?>"
                                   href="<?= htmlspecialchars($item['href']) ?>"
                                   data-title="<?= htmlspecialchars($item['label']) ?>"
                                   title="<?= htmlspecialchars($item['label']) ?>">
                                    <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>


                <!-- ADMINISTRATOR -->
            <?php elseif (in_array($roleKey, ['faculty_admin'], true)): ?>
                <?php foreach ($facultyAdministrator as $groupLabel => $groupItems): ?>
                    <li class="nav-item sidebar-group-label">
                        <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                    </li>
                    <?php foreach ($groupItems as $item): ?>
                        <?php $linkClass = ($activeModule === 'faculty' && $activePage === $item['slug']) ? 'active' : ''; ?>
                        <li class="nav-item">
                            <a class="nav-link sidebar-sub <?= $linkClass ?>"
                            href="<?= htmlspecialchars($item['href']) ?>"
                            data-title="<?= htmlspecialchars($item['label']) ?>"
                            title="<?= htmlspecialchars($item['label']) ?>">
                                <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
            <?php endforeach; ?>

                <!-- DEAN -->
            <?php elseif (in_array($roleKey, ['dean', 'hr'], true)): ?>
                <?php foreach ($facultyDeanNavGroups as $groupLabel => $groupItems): ?>
                    <li class="nav-item sidebar-group-label">
                        <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                    </li>
                    <?php foreach ($groupItems as $item): ?>
                        <?php $linkClass = ($activeModule === 'faculty' && $activePage === $item['slug']) ? 'active' : ''; ?>
                        <li class="nav-item">
                            <a class="nav-link sidebar-sub <?= $linkClass ?>"
                            href="<?= htmlspecialchars($item['href']) ?>"
                            data-title="<?= htmlspecialchars($item['label']) ?>"
                            title="<?= htmlspecialchars($item['label']) ?>">
                                <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- DEPARTMENT HEAD -->
            <?php elseif (in_array($roleKey, ['department_head', 'department-head', 'dept_head', 'depthead'], true)): ?>
                <?php foreach ($facultyDepartmentHeadNavGroups as $groupLabel => $groupItems): ?>
                    <li class="nav-item sidebar-group-label">
                        <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                    </li>
                    <?php foreach ($groupItems as $item): ?>
                        <?php $linkClass = ($activeModule === 'faculty' && $activePage === $item['slug']) ? 'active' : ''; ?>
                        <li class="nav-item">
                            <a class="nav-link sidebar-sub <?= $linkClass ?>"
                            href="<?= htmlspecialchars($item['href']) ?>"
                            data-title="<?= htmlspecialchars($item['label']) ?>"
                            title="<?= htmlspecialchars($item['label']) ?>">
                                <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                    <!-- SECRETARY -->
                <?php elseif (in_array($roleKey, ['secretary'], true)): ?>
                    <?php foreach ($facultySecretaryNavGroups as $groupLabel => $groupItems): ?>
                        <li class="nav-item sidebar-group-label">
                            <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                        </li>
                        <?php foreach ($groupItems as $item): ?>
                            <?php $linkClass = ($activeModule === 'faculty' && $activePage === $item['slug']) ? 'active' : ''; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-sub <?= $linkClass ?>"
                                href="<?= htmlspecialchars($item['href']) ?>"
                                data-title="<?= htmlspecialchars($item['label']) ?>"
                                title="<?= htmlspecialchars($item['label']) ?>">
                                    <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                <?php endforeach; ?>
                            
                <!-- ATTENDANCE MONITORING -->
                <?php elseif (in_array($roleKey, ['monitoring_officer'], true)): ?>
                    <?php foreach ($facultyAttendanceMonitoringNavGroups as $groupLabel => $groupItems): ?>
                        <li class="nav-item sidebar-group-label">
                            <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                        </li>
                        <?php foreach ($groupItems as $item): ?>
                            <?php $linkClass = ($activeModule === 'faculty' && $activePage === $item['slug']) ? 'active' : ''; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-sub <?= $linkClass ?>"
                                href="<?= htmlspecialchars($item['href']) ?>"
                                data-title="<?= htmlspecialchars($item['label']) ?>"
                                title="<?= htmlspecialchars($item['label']) ?>">
                                    <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- FACULTY -->
                 <?php elseif (in_array($roleKey, ['faculty'], true)): ?>
                    <?php foreach ($facultyTeacherNavGroups as $groupLabel => $groupItems): ?>
                        <li class="nav-item sidebar-group-label">
                            <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                        </li>
                        <?php foreach ($groupItems as $item): ?>
                            <?php $linkClass = ($activeModule === 'faculty' && $activePage === $item['slug']) ? 'active' : ''; ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-sub <?= $linkClass ?>"
                                href="<?= htmlspecialchars($item['href']) ?>"
                                data-title="<?= htmlspecialchars($item['label']) ?>"
                                title="<?= htmlspecialchars($item['label']) ?>">
                                    <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                <?php endforeach; ?>

                <?php else: ?>
                    
                 <?php foreach ($visibleModules as $navModuleKey => $module): ?>
                    <?php
                    $isModuleActive = ($activeModule === $navModuleKey);
                    $moduleFolder = $navModuleKey === 'student_portal' ? 'student-portal' : $navModuleKey;
                    $overviewUrl = BASE_URL . '/modules/' . $moduleFolder . '/index.php';
                    $moduleInMaint = smsIsModuleInMaintenance((string) $navModuleKey);
                    ?>
                    <li class="nav-item sidebar-group-label">
                        <span class="nav-link sidebar-group-heading">
                            <?= htmlspecialchars($module['label']) ?>
                            <?php if ($moduleInMaint): ?>
                                <span class="badge text-bg-warning ms-1" style="font-size:0.62rem;">Maint</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sidebar-sub overview-link <?= ($isModuleActive && $activePage === '') ? 'active' : '' ?>"
                           href="<?= htmlspecialchars($overviewUrl) ?>">
                            <i class="fas fa-th-large" aria-hidden="true"></i>
                            <span>Overview</span>
                        </a>
                    </li>
                    <?php
                    // Check if module has grouped sidebar sections
                    $hasGroups = !empty($module['groups']) && is_array($module['groups']);
                    if ($hasGroups):
                        // Build a lookup map from slug to page title
                        $pageTitles = [];
                        foreach ($module['pages'] as $p) {
                            $pageTitles[$p['slug']] = $p['title'];
                        }
                        foreach ($module['groups'] as $groupLabel => $groupSlugs):
                    ?>
                        <li class="nav-item sidebar-group-label">
                            <span class="nav-link sidebar-group-heading"><?= htmlspecialchars($groupLabel) ?></span>
                        </li>
                        <?php foreach ($groupSlugs as $slug): ?>
                            <?php
                            if (!isset($pageTitles[$slug])) { continue; }
                            $isPageActive = ($isModuleActive && $activePage === $slug);
                            $pageHref = BASE_URL . '/modules/' . $moduleFolder . '/pages/' . $slug . '.php';
                            ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-sub <?= $isPageActive ? 'active' : '' ?>"
                                   href="<?= htmlspecialchars($pageHref) ?>">
                                    <i class="fas <?= htmlspecialchars(smsNavPageIcon($slug)) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($pageTitles[$slug]) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($module['pages'] as $page): ?>
                            <?php
                            $isPageActive = ($isModuleActive && $activePage === $page['slug']);
                            $pageHref = BASE_URL . '/modules/' . $moduleFolder . '/pages/' . $page['slug'] . '.php';
                            // Module Security: keep CRAD/etc. focus when already inside a module.
                            if ($navModuleKey === 'user-management' && $page['slug'] === 'module-security') {
                                $secFocus = (string) ($_SESSION['um_sec_focus'] ?? '');
                                if ($secFocus !== '' && ($activePage ?? '') === 'module-security' && empty($_GET['picker'])) {
                                    $pageHref .= '?focus=' . rawurlencode($secFocus);
                                } else {
                                    $pageHref .= '?picker=1';
                                }
                            }
                            ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-sub <?= $isPageActive ? 'active' : '' ?>"
                                   href="<?= htmlspecialchars($pageHref) ?>">
                                    <i class="fas <?= htmlspecialchars(smsNavPageIcon($page['slug'])) ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($page['title']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($securitySettingsModule !== ''): ?>
                    <li class="nav-item sidebar-group-label">
                        <span class="nav-link sidebar-group-heading">System</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sidebar-sub <?= ($activePage === 'security-settings') ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/account/module-security.php?module=<?= urlencode($securitySettingsModule) ?>">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                            <span>Security Settings</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php unset($navModuleKey, $module, $page, $isModuleActive, $overviewUrl, $pageHref, $isPageActive, $secFocus); ?>            <?php endif; ?>
        </ul>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
