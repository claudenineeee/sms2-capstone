<?php
/**
 * SMS 2 - Top Navigation Bar
 */

require_once __DIR__ . '/authentication.php';

$__notifHelper = __DIR__ . '/../modules/faculty/views/notifications.php';
if (is_file($__notifHelper)) {
    require_once $__notifHelper;
}

if (!isset($MODULES)) {
    require_once __DIR__ . '/../config/config.php';
}

// Handle POST actions BEFORE any output
if (function_exists('smsHandleNotificationAction')) {
    smsHandleNotificationAction();
}

$visibleModulesNav = getVisibleModules($MODULES);
$navRoleKey        = getCurrentUserRoleKey();
$navMessages       = [];
$navNotifications  = [];

if (function_exists('smsMarkNotificationFromRequest')) {
    smsMarkNotificationFromRequest();
}

if (function_exists('smsNotificationPayloadForCurrentUser')) {
    try {
        $navNotifications = smsNotificationPayloadForCurrentUser();
    } catch (Throwable $e) {
        error_log('[navbar] notification payload failed: ' . $e->getMessage());
        $navNotifications = [];
    }
}

$navMessageCount            = count($navMessages);
$navNotificationCount       = count($navNotifications);
$navNotificationUnreadCount = count(array_filter(
    $navNotifications,
    static fn(array $item): bool => !empty($item['is_unread'])
));
?>
<nav class="navbar navbar-expand-lg navbar-dark sms-navbar fixed-top">
    <div class="container-fluid navbar-inner">

        <div class="navbar-left d-flex align-items-center gap-2">
            <button class="btn btn-link text-white sidebar-toggle p-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2" href="#" onclick="window.location.reload(); return false;">
                <img src="<?= BASE_URL ?>/images/bcp-logo-source.png" alt="BCP Logo" style="height: 32px; width: auto; object-fit: contain;">
                <span class="d-none d-sm-inline fw-bold"><?= htmlspecialchars(APP_SHORT_NAME) ?></span>
            </a>
        </div>

        <div class="navbar-center">
            <div class="navbar-search position-relative">
                <i class="fas fa-search navbar-search-icon"></i>
                <input type="text" id="globalSearch" class="form-control navbar-search-input"
                       placeholder="Search modules and pages..."
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
                <div class="navbar-search-dropdown" id="searchDropdown" role="listbox" aria-label="Search results">
                    <div class="search-empty" id="searchEmpty">
                        <i class="fas fa-search-minus"></i>
                        <span>No results found</span>
                    </div>
                    <ul class="search-results-list" id="searchResultsList"></ul>
                </div>
            </div>
        </div>

        <div class="navbar-right d-flex align-items-center gap-2 gap-md-3">

            <?php
            $phClockMs   = (int) round(microtime(true) * 1000);
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
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifications: <?= $navNotificationCount ?>"
                        data-sms-notification-button>
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute badge rounded-pill bg-danger notification-badge <?= $navNotificationCount > 0 ? '' : 'd-none' ?>"
                          style="top:2px;right:-2px;transform:none;"
                          data-sms-notification-badge><?= $navNotificationCount ?></span>
                </button>

                <div class="dropdown-menu dropdown-menu-end shadow sms-notification-dropdown" data-sms-notification-menu>
                    <div class="sms-notification-head">
                        <h6>Notifications</h6>
                    </div>

                    <div class="sms-notification-tabs" role="tablist" aria-label="Notification filters">
                        <button type="button" class="active" data-sms-notification-filter="all">All</button>
                        <button type="button" data-sms-notification-filter="unread">
                            Unread<?= $navNotificationUnreadCount > 0 ? ' ' . $navNotificationUnreadCount : '' ?>
                        </button>
                        <span class="ms-auto"></span>
                        <button type="button" class="sms-notif-action" data-sms-mark-all-read title="Mark all as read">
                            <i class="fas fa-check-double"></i>
                        </button>
                        <button type="button" class="sms-notif-action danger" data-sms-delete-all title="Clear all notifications">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    <div class="sms-notification-empty" data-sms-notification-empty <?= $navNotificationCount === 0 ? '' : 'hidden' ?>>
                        <i class="fas fa-bell-slash"></i>
                        <span>No notifications</span>
                    </div>

                    <div class="sms-notification-list" data-sms-notification-items>
                        <?php if (!empty($navNotifications)): ?>
                            <?php foreach ($navNotifications as $notification): ?>
                                <?php
                                    $isUnread = !empty($notification['is_unread']);
                                    $nid      = (string) ($notification['id'] ?? '');
                                    $nLabel   = (string) ($notification['label'] ?? 'Notification');
                                    $nIcon    = (string) ($notification['icon'] ?? 'fa-info-circle');
                                    $nUrl     = (string) ($notification['url'] ?? '#');
                                    $nTime    = (string) ($notification['time'] ?? '');
                                ?>
                                <div class="sms-notification-item <?= $isUnread ? 'unread' : '' ?>" data-notification-row>
                                    <a class="sms-notification-body"
                                       href="<?= htmlspecialchars($nUrl) ?>"
                                       data-sms-notification-link
                                       data-notification-id="<?= htmlspecialchars($nid) ?>"
                                       data-notification-status="<?= $isUnread ? 'unread' : 'read' ?>">
                                        <span class="sms-notification-icon">
                                            <i class="fas <?= htmlspecialchars($nIcon) ?>"></i>
                                        </span>
                                        <span class="sms-notification-copy">
                                            <span class="sms-notification-title"><?= htmlspecialchars($nLabel) ?></span>
                                            <?php if ($nTime !== ''): ?>
                                                <span class="sms-notification-time"><?= htmlspecialchars($nTime) ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($isUnread): ?>
                                            <span class="sms-notification-dot" aria-label="Unread"></span>
                                        <?php endif; ?>
                                    </a>
                                    <button type="button"
                                            class="sms-notification-delete"
                                            data-sms-notification-delete
                                            data-notification-id="<?= htmlspecialchars($nid) ?>"
                                            title="Delete">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="dropdown">
                <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <span class="d-none d-md-inline"><?= htmlspecialchars(getCurrentUserName()) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <?php
                    $navRole = getCurrentUserRoleKey();
                    if ($navRole === 'student') {
                        $profileHref  = BASE_URL . '/modules/student-portal/pages/my-profile.php';
                        $profileLabel = 'My Profile';
                    } elseif (in_array($navRole, ['superadmin', 'admin'], true)) {
                        $profileHref  = BASE_URL . '/account/profile.php';
                        $profileLabel = 'Account Settings';
                    } elseif (in_array($navRole, ['dean', 'hr', 'department_head', 'department-head', 'dept_head', 'depthead', 'secretary', 'faculty', 'faculty_admin', 'monitoring_officer', 'faculty_schedule_officer'], true)) {
                        $profileHref  = BASE_URL . '/modules/faculty/views/my-profile.php';
                        $profileLabel = 'My Profile';
                    } else {
                        $profileHref  = BASE_URL . '/dashboard/index.php';
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
                    <li>
                        <a class="dropdown-item text-danger"
                           href="<?= BASE_URL ?>/login/logout.php"
                           data-logout-confirm>
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<style>
    #logoutConfirmModal .modal-dialog { width: min(420px, calc(100vw - 2rem)); max-width: 420px; margin-left: auto; margin-right: auto; }
    #logoutConfirmModal .modal-content { border-radius: 12px; overflow: hidden; }
    #logoutConfirmModal .modal-header,
    #logoutConfirmModal .modal-body,
    #logoutConfirmModal .modal-footer { padding: 1rem 1.1rem; }
    #logoutConfirmModal .modal-title { font-size: 1rem; }
    #logoutConfirmModal .modal-body { color: var(--sms-text, #334155); font-size: 0.95rem; }

    /* ===== NOTIFICATION DROPDOWN ===== */
    .sms-notification-dropdown {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, .18);
        min-width: 320px;
        max-width: 380px;
        width: 360px;
        overflow: hidden;
        padding: 0;
    }
    @media (max-width: 420px) {
        .sms-notification-dropdown { width: calc(100vw - 2rem); min-width: 0; }
    }

    .sms-notification-head { padding: .9rem 1rem .4rem; }
    .sms-notification-head h6 {
        color: var(--sms-heading, #0f172a);
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
    }

    .sms-notification-tabs {
        display: flex;
        align-items: center;
        gap: .3rem;
        padding: 0 1rem .55rem;
    }
    .sms-notification-tabs button {
        background: transparent;
        border: 0;
        border-radius: 999px;
        color: var(--sms-text, #334155);
        font-size: .76rem;
        font-weight: 700;
        padding: .35rem .7rem;
        cursor: pointer;
    }
    .sms-notification-tabs button.active {
        background: rgba(36, 84, 198, .14);
        color: var(--sms-primary, #2454c6);
    }
    .sms-notif-action {
        background: transparent;
        border: 0;
        border-radius: 6px;
        color: var(--sms-text-muted, #64748b);
        padding: .3rem .5rem;
        font-size: .78rem;
        cursor: pointer;
        transition: all .15s;
    }
    .sms-notif-action:hover {
        background: rgba(36, 84, 198, .1);
        color: var(--sms-primary, #2454c6);
    }
    .sms-notif-action.danger:hover {
        background: rgba(239, 68, 68, .1);
        color: #dc2626;
    }

    .sms-notification-list {
        max-height: min(400px, calc(100vh - 220px));
        overflow-y: auto;
        padding: 0 .4rem .4rem;
    }

    .sms-notification-item {
        display: flex;
        align-items: center;
        gap: .25rem;
        border-radius: 10px;
        transition: background .15s;
    }
    .sms-notification-item:hover {
        background: rgba(36, 84, 198, .06);
    }

    .sms-notification-body {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .5rem .55rem;
        border-radius: 10px;
        color: var(--sms-text, #334155);
        text-decoration: none;
    }
    .sms-notification-body:hover {
        color: var(--sms-text, #334155);
    }

    .sms-notification-icon {
        align-items: center;
        background: rgba(37, 99, 235, .13);
        border-radius: 50%;
        color: #0d6efd;
        display: inline-flex;
        justify-content: center;
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
        font-size: 12px;
    }

    .sms-notification-copy {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 1px;
    }

    .sms-notification-title {
        color: var(--sms-heading, #0f172a);
        font-size: .82rem;
        font-weight: 600;
        line-height: 1.25;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sms-notification-time {
        color: var(--sms-primary, #2454c6);
        font-size: .68rem;
        font-weight: 500;
        display: block;
        white-space: nowrap;
    }

    .sms-notification-dot {
        align-self: center;
        background: #3b82f6;
        border-radius: 50%;
        width: 7px;
        height: 7px;
        flex: 0 0 7px;
        margin-right: .2rem;
    }

    .sms-notification-delete {
        flex: 0 0 auto;
        background: transparent;
        border: 0;
        border-radius: 6px;
        color: var(--sms-text-muted, #94a3b8);
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        opacity: 0;
        transition: opacity .15s, background .15s, color .15s;
        cursor: pointer;
        margin-right: .25rem;
    }
    .sms-notification-item:hover .sms-notification-delete {
        opacity: 1;
    }
    .sms-notification-delete:hover {
        background: rgba(239, 68, 68, .15);
        color: #dc2626;
    }

    .sms-notification-empty {
        align-items: center;
        color: var(--sms-text-muted, #64748b);
        display: flex;
        gap: .5rem;
        padding: 1rem;
        font-size: .82rem;
    }
    .sms-notification-empty[hidden],
    .sms-notification-item[hidden] { display: none !important; }
</style>

<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="logoutConfirmTitle">
                    <i class="fas fa-sign-out-alt text-danger me-2"></i>Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to logout?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="<?= BASE_URL ?>/login/logout.php" id="logoutConfirmBtn">
                    <span class="logout-confirm-idle"><i class="fas fa-check me-1"></i>Yes, logout</span>
                    <span class="logout-confirm-loading d-none">
                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Logging out...
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
window.SMS2_SEARCH_INDEX = (function() {
    var base = '<?= BASE_URL ?>';
    var items = [];
    <?php foreach ($visibleModulesNav as $navModuleKey => $module): ?>
    items.push({type:'module',label:<?= json_encode($module['label']) ?>,icon:<?= json_encode($module['icon']) ?>,url:base+'/modules/<?= $navModuleKey ?>/index.php',keywords:<?= json_encode(strtolower($module['label'])) ?>});
    <?php foreach ($module['pages'] as $page): ?>
    items.push({type:'page',label:<?= json_encode($page['title']) ?>,parent:<?= json_encode($module['label']) ?>,icon:<?= json_encode($module['icon']) ?>,url:base+'/modules/<?= $navModuleKey ?>/pages/<?= $page['slug'] ?>.php',keywords:<?= json_encode(strtolower($page['title'].' '.$module['label'])) ?>});
    <?php endforeach; ?>
    <?php endforeach; ?>
    return items;
})();

document.addEventListener('DOMContentLoaded', function () {

    const notificationEmpty   = document.querySelector('[data-sms-notification-empty]');
    const notificationItems   = document.querySelector('[data-sms-notification-items]');
    const notificationFilters = document.querySelectorAll('[data-sms-notification-filter]');
    const notificationBadge   = document.querySelector('[data-sms-notification-badge]');
    const endpoint            = window.location.href;
    let notificationFilter    = 'all';

    /* -------- Tab filtering -------- */
    const applyFilter = function () {
        if (!notificationItems) return;
        const items = notificationItems.querySelectorAll('[data-notification-row]');
        let visible = 0;
        items.forEach(function (row) {
            const unread = row.classList.contains('unread');
            const show = (notificationFilter === 'all') || unread;
            row.hidden = !show;
            if (show) visible++;
        });
        if (notificationEmpty) {
            notificationEmpty.hidden = visible !== 0;
            const span = notificationEmpty.querySelector('span');
            if (span) {
                span.textContent = notificationFilter === 'unread'
                    ? 'No unread notifications'
                    : 'No notifications';
            }
        }
    };

    notificationFilters.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            notificationFilter = button.dataset.smsNotificationFilter || 'all';
            notificationFilters.forEach(function (b) {
                const active = b.dataset.smsNotificationFilter === notificationFilter;
                b.classList.toggle('active', active);
                b.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyFilter();
        });
    });

    applyFilter();

    /* -------- Send action to server -------- */
    function sendAction(action, notificationId) {
        const body = new FormData();
        body.append('action', action);
        if (notificationId) body.append('notification_id', notificationId);
        return fetch(endpoint, {
            method: 'POST',
            body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).catch(function () {});
    }

    /* -------- Mark single as read on click -------- */
    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-sms-notification-link]');
        if (!link) return;

        const id = link.dataset.notificationId || '';
        if (!id) return;

        const row = link.closest('[data-notification-row]');
        if (row && row.classList.contains('unread')) {
            row.classList.remove('unread');
            const dot = row.querySelector('.sms-notification-dot');
            if (dot) dot.remove();

            const remaining = document.querySelectorAll('[data-notification-row].unread').length;
            if (notificationBadge) {
                notificationBadge.textContent = String(remaining);
                notificationBadge.classList.toggle('d-none', remaining === 0);
            }
            const unreadTab = document.querySelector('[data-sms-notification-filter="unread"]');
            if (unreadTab) {
                unreadTab.textContent = remaining > 0 ? 'Unread ' + remaining : 'Unread';
            }
        }

        sendAction('mark_read', id);
    });

    /* -------- Delete one -------- */
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-sms-notification-delete]');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();

        const id  = btn.dataset.notificationId || '';
        const row = btn.closest('[data-notification-row]');
        if (row) row.remove();

        const remaining = document.querySelectorAll('[data-notification-row]').length;
        if (notificationBadge) {
            const unreadLeft = document.querySelectorAll('[data-notification-row].unread').length;
            notificationBadge.textContent = String(unreadLeft);
            notificationBadge.classList.toggle('d-none', unreadLeft === 0);
        }
        if (notificationEmpty && remaining === 0) {
            notificationEmpty.hidden = false;
        }

        sendAction('delete_one', id);
    });

    /* -------- Mark all read -------- */
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-sms-mark-all-read]');
        if (!btn) return;
        event.preventDefault();

        document.querySelectorAll('[data-notification-row].unread').forEach(function (row) {
            row.classList.remove('unread');
            const dot = row.querySelector('.sms-notification-dot');
            if (dot) dot.remove();
        });
        if (notificationBadge) {
            notificationBadge.textContent = '0';
            notificationBadge.classList.add('d-none');
        }
        const unreadTab = document.querySelector('[data-sms-notification-filter="unread"]');
        if (unreadTab) unreadTab.textContent = 'Unread';

        sendAction('mark_all_read');
    });

    /* -------- Delete all -------- */
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-sms-delete-all]');
        if (!btn) return;
        event.preventDefault();

        document.querySelectorAll('[data-notification-row]').forEach(function (row) {
            row.remove();
        });
        if (notificationBadge) {
            notificationBadge.textContent = '0';
            notificationBadge.classList.add('d-none');
        }
        if (notificationEmpty) {
            notificationEmpty.hidden = false;
        }

        sendAction('delete_all');
    });

    /* -------- Logout confirmation -------- */
    const logoutLink = document.querySelector('[data-logout-confirm]');
    const modalEl    = document.getElementById('logoutConfirmModal');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    if (!logoutLink || !modalEl || typeof bootstrap === 'undefined') return;

    const logoutModal = new bootstrap.Modal(modalEl);
    logoutLink.addEventListener('click', function (event) {
        event.preventDefault();
        logoutModal.show();
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            const idle    = confirmBtn.querySelector('.logout-confirm-idle');
            const loading = confirmBtn.querySelector('.logout-confirm-loading');
            if (idle && loading) {
                idle.classList.add('d-none');
                loading.classList.remove('d-none');
            }
            confirmBtn.classList.add('disabled');
            confirmBtn.setAttribute('aria-disabled', 'true');
        });
    }
});
</script>