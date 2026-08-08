<?php
/**
 * SMS 2 - Top Navigation Bar
 */
require_once __DIR__ . '/authentication.php';
if (!isset($MODULES)) {
    require_once __DIR__ . '/../config/config.php';
}
$visibleModulesNav = getVisibleModules($MODULES);
$navRoleKey = getCurrentUserRoleKey();
$navMessages = [];
$navNotifications = [];
$navStudentResearchGroup = (isset($studentResearchGroup) && is_array($studentResearchGroup)) ? $studentResearchGroup : null;
$navStudentReturnedProposals = [];

if ($navRoleKey === 'student') {
    try {
        require_once __DIR__ . '/../modules/crad/config/config.php';
        $navCradPdo = function_exists('cradDb') ? cradDb() : null;

        if ($navCradPdo instanceof PDO) {
            $navStudentId = trim((string) ($_SESSION['student_id'] ?? ''));
            $navStudentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
            $navStudentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
            $navStudentUserId = (int) ($_SESSION['user_id'] ?? 0);

            $navIdentityParams = [
                ':student_id_value' => $navStudentId,
                ':student_id_rep' => $navStudentId,
                ':student_email_value' => $navStudentEmail,
                ':student_email_rep' => $navStudentEmail,
                ':student_name_value' => $navStudentName,
                ':student_name_rep' => $navStudentName,
                ':user_id_value' => $navStudentUserId,
                ':user_id_match' => $navStudentUserId,
            ];

            if (!$navStudentResearchGroup) {
                $navStmt = $navCradPdo->prepare(
                    "SELECT p.proposal_number, p.research_title, p.registration_status,
                            p.rep_name, p.rep_id, p.rep_email, p.submitted_by_user,
                            g.group_number, g.group_name, g.status, g.date_assigned, g.created_at
                     FROM research_groups g
                     INNER JOIN research_proposals p ON p.id = g.proposal_id
                     WHERE g.group_number IS NOT NULL
                       AND (
                            (:student_id_value <> '' AND p.rep_id = :student_id_rep)
                         OR (:student_email_value <> '' AND LOWER(p.rep_email) = :student_email_rep)
                         OR (:student_name_value <> '' AND LOWER(TRIM(p.rep_name)) = :student_name_rep)
                         OR (:user_id_value > 0 AND p.submitted_by_user = :user_id_match)
                       )
                     ORDER BY g.date_assigned DESC, g.id DESC
                     LIMIT 1"
                );
                $navStmt->execute($navIdentityParams);
                $navStudentResearchGroup = $navStmt->fetch() ?: null;
            }

            $navReturnedStmt = $navCradPdo->prepare(
                "SELECT ref_code, research_title, notes, updated_at
                 FROM research_proposals
                 WHERE status = 'Returned'
                   AND (
                        (:student_id_value <> '' AND rep_id = :student_id_rep)
                     OR (:student_email_value <> '' AND LOWER(rep_email) = :student_email_rep)
                     OR (:student_name_value <> '' AND LOWER(TRIM(rep_name)) = :student_name_rep)
                     OR (:user_id_value > 0 AND submitted_by_user = :user_id_match)
                   )
                 ORDER BY updated_at DESC, id DESC
                 LIMIT 5"
            );
            $navReturnedStmt->execute($navIdentityParams);
            $navStudentReturnedProposals = $navReturnedStmt->fetchAll() ?: [];
        }
    } catch (Throwable $e) {
        error_log('Navbar student notification error: ' . $e->getMessage());
    }
}

if ($navRoleKey === 'student' && is_array($navStudentResearchGroup)) {
    $navResearchGroupNumber = (string) ($navStudentResearchGroup['group_number'] ?? '');

    if ($navResearchGroupNumber !== '') {
        $navResearchGroupDateTime = !empty($navStudentResearchGroup['created_at'])
            ? date('M j, Y h:i A', strtotime((string) $navStudentResearchGroup['created_at']))
            : (!empty($navStudentResearchGroup['date_assigned'])
                ? date('M j, Y h:i A', strtotime((string) $navStudentResearchGroup['date_assigned']))
                : date('M j, Y h:i A'));
        $navNotifications[] = [
            'icon' => 'fa-users',
            'class' => 'text-primary',
            'label' => 'Research group number ' . $navResearchGroupNumber . ' is ready',
            'time' => $navResearchGroupDateTime,
            'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php?research_group=1',
        ];
    }
}

foreach ($navStudentReturnedProposals as $returnedProposal) {
    $returnedDate = !empty($returnedProposal['updated_at'])
        ? date('M j, Y h:i A', strtotime((string) $returnedProposal['updated_at']))
        : '';
    $navNotifications[] = [
        'icon' => 'fa-undo',
        'class' => 'text-danger',
        'label' => 'Proposal returned: ' . ($returnedProposal['ref_code'] ?? 'Research Proposal'),
        'time' => $returnedDate,
        'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php?returned_proposal=' . urlencode((string) ($returnedProposal['ref_code'] ?? '')),
    ];
}

$navMessageCount = count($navMessages);
$navNotificationCount = count($navNotifications);
$navNotificationViewAllUrl = BASE_URL . '/modules/student-portal/pages/dashboard.php';
if ($navRoleKey === 'student') {
    if (!empty($navStudentReturnedProposals[0]['ref_code'])) {
        $navNotificationViewAllUrl .= '?returned_proposal=' . urlencode((string) $navStudentReturnedProposals[0]['ref_code']);
    } elseif (is_array($navStudentResearchGroup ?? null) && !empty($navStudentResearchGroup['group_number'])) {
        $navNotificationViewAllUrl .= '?research_group=1';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sms-navbar fixed-top">
    <div class="container-fluid navbar-inner">

        <!-- Left: Toggle + Brand -->
        <div class="navbar-left d-flex align-items-center gap-2">
            <button class="btn btn-link text-white sidebar-toggle p-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/dashboard/index.php">
                <i class="fas fa-graduation-cap"></i>
                <span class="d-none d-sm-inline"><?= htmlspecialchars(APP_SHORT_NAME) ?></span>
            </a>
        </div>

        <!-- Center: Global Search -->
        <div class="navbar-center">
            <div class="navbar-search position-relative">
                <i class="fas fa-search navbar-search-icon"></i>
                <input type="text" id="globalSearch" class="form-control navbar-search-input"
                       placeholder="Search modules and pages…"
                       autocomplete="off"
                       aria-label="Search modules and pages"
                       aria-haspopup="listbox"
                       aria-expanded="false">
                <button class="navbar-search-clear d-none" id="globalSearchClear" type="button" aria-label="Clear search">
                    <i class="fas fa-times"></i>
                </button>
                <div class="search-kbd-hint" aria-hidden="true">
                    <kbd>Ctrl</kbd><kbd>K</kbd>
                </div>
                <!-- Results dropdown -->
                <div class="navbar-search-dropdown" id="searchDropdown" role="listbox" aria-label="Search results">
                    <div class="search-empty" id="searchEmpty">
                        <i class="fas fa-search-minus"></i>
                        <span>No results found</span>
                    </div>
                    <ul class="search-results-list" id="searchResultsList"></ul>
                </div>
            </div>
        </div>

        <!-- Right: PH Clock + Theme + Messages + Notifications + User -->
        <div class="navbar-right d-flex align-items-center gap-2 gap-md-3">

            <!-- Philippine Standard Time (Asia/Manila) -->
            <?php
            $phClockMs = (int) round(microtime(true) * 1000);
            $phClockSeed = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('h:i:s A');
            ?>
            <time id="navbarPhClock"
                  class="navbar-ph-clock text-white"
                  datetime="<?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format(DateTimeInterface::ATOM)) ?>"
                  data-server-ms="<?= $phClockMs ?>"
                  title="Philippine Standard Time (UTC+8)"
                  aria-label="Philippine Standard Time">
                <?= htmlspecialchars($phClockSeed) ?>
            </time>

            <!-- Theme toggle -->
            <button type="button"
                    class="btn theme-toggle"
                    data-theme-toggle
                    aria-label="Switch theme"
                    title="Toggle theme">
                <i class="fas fa-moon theme-icon-moon" aria-hidden="true"></i>
                <i class="fas fa-sun theme-icon-sun" aria-hidden="true"></i>
            </button>

            <!-- Messages -->
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Messages: <?= $navMessageCount ?>">
                    <i class="fas fa-envelope"></i>
                    <?php if ($navMessageCount > 0): ?>
                        <span class="position-absolute badge rounded-pill bg-success notification-badge" style="top:2px;right:-2px;transform:none;"><?= $navMessageCount ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:280px;">
                    <li><h6 class="dropdown-header">Messages</h6></li>
                    <?php if ($navMessageCount === 0): ?>
                        <li><span class="dropdown-item-text text-muted py-2"><i class="fas fa-inbox me-2"></i>No messages</span></li>
                    <?php else: ?>
                        <?php foreach ($navMessages as $message): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-start gap-2 py-2" href="<?= htmlspecialchars($message['url'] ?? '#') ?>">
                                    <div class="navbar-msg-avatar"><?= htmlspecialchars($message['avatar'] ?? 'M') ?></div>
                                    <div class="navbar-msg-body">
                                        <div class="navbar-msg-name"><?= htmlspecialchars($message['from'] ?? 'Message') ?></div>
                                        <div class="navbar-msg-text"><?= htmlspecialchars($message['text'] ?? '') ?></div>
                                        <div class="navbar-msg-time"><?= htmlspecialchars($message['time'] ?? '') ?></div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-center text-primary py-2" href="#"><i class="fas fa-envelope-open me-1"></i>View all messages</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications: <?= $navNotificationCount ?>">
                    <i class="fas fa-bell"></i>
                    <?php if ($navNotificationCount > 0): ?>
                        <span class="position-absolute badge rounded-pill bg-danger notification-badge" style="top:2px;right:-2px;transform:none;"><?= $navNotificationCount ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <?php if ($navNotificationCount === 0): ?>
                        <li><span class="dropdown-item-text text-muted"><i class="fas fa-bell-slash me-2"></i>No notifications</span></li>
                    <?php else: ?>
                        <?php foreach ($navNotifications as $notification): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-start gap-2" href="<?= htmlspecialchars($notification['url'] ?? '#') ?>">
                                    <i class="fas <?= htmlspecialchars($notification['icon'] ?? 'fa-info-circle') ?> <?= htmlspecialchars($notification['class'] ?? 'text-primary') ?> mt-1"></i>
                                    <span>
                                        <span class="d-block"><?= htmlspecialchars($notification['label'] ?? 'Notification') ?></span>
                                        <?php if (!empty($notification['time'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($notification['time']) ?></small>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center text-primary" href="<?= htmlspecialchars($navNotificationViewAllUrl) ?>">View all</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- User Profile -->
            <div class="dropdown">
                <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <span class="d-none d-md-inline"><?= htmlspecialchars(getCurrentUserName()) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header"><?= htmlspecialchars(getCurrentUserRole()) ?></h6></li>
                    <?php
                    $navRole = getCurrentUserRoleKey();
                    if ($navRole === 'student') {
                        $profileHref = BASE_URL . '/modules/student-portal/pages/my-profile.php';
                        $profileLabel = 'My Profile';
                    } elseif (in_array($navRole, ['superadmin', 'admin'], true)) {
                        $profileHref = BASE_URL . '/account/profile.php';
                        $profileLabel = 'Account Settings';
                    } else {
                        $profileHref = BASE_URL . '/dashboard/index.php';
                        $profileLabel = 'My Profile';
                    }
                    ?>
                    <li>
                        <a class="dropdown-item" href="<?= htmlspecialchars($profileHref) ?>">
                            <i class="fas fa-user me-2"></i><?= htmlspecialchars($profileLabel) ?>
                        </a>
                    </li>
                    <?php if (in_array($navRole, ['superadmin', 'admin'], true)): ?>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/account/profile.php?tab=security">
                            <i class="fas fa-key me-2"></i>Login Security
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Appearance</h6></li>
                    <li>
                        <button type="button" class="dropdown-item" data-theme-set="light">
                            <i class="fas fa-sun me-2"></i>Light mode
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-theme-set="dark">
                            <i class="fas fa-moon me-2"></i>Dark mode
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/login/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<script>
/* Global Search Index — built from PHP $MODULES visible to current user */
window.SMS2_SEARCH_INDEX = (function() {
    var base = '<?= BASE_URL ?>';
    var items = [];
    <?php foreach ($visibleModulesNav as $navModuleKey => $module): ?>
    items.push({type:'module',label:<?= json_encode($module['label']) ?>,icon:<?= json_encode($module['icon']) ?>,url:base+'/modules/<?= $navModuleKey ?>/index.php',keywords:<?= json_encode(strtolower($module['label'])) ?>});
    <?php foreach ($module['pages'] as $page): ?>
    items.push({type:'page',label:<?= json_encode($page['title']) ?>,parent:<?= json_encode($module['label']) ?>,icon:<?= json_encode($module['icon']) ?>,url:base+'/modules/<?= $navModuleKey ?>/pages/<?= $page['slug'] ?>.php',keywords:<?= json_encode(strtolower($page['title'].' '.$module['label'])) ?>});
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php unset($navModuleKey, $module, $page); ?>    return items;
})();
</script>
