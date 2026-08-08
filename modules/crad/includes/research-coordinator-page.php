<?php
/**
 * SMS 2 - Research Coordinator workflow pages.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['research_coordinator', 'superadmin'], true)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$rcPageSlug = $rcPageSlug ?? 'approved-research';
$rcPages = [
    'approved-research' => [
        'title' => 'View Approved Research',
        'description' => 'View approved research groups and titles ready for adviser and panel coordination.',
        'metrics' => [
            ['label' => 'Approved Titles', 'value' => '18', 'icon' => 'fa-clipboard-check', 'tone' => 'green'],
            ['label' => 'Awaiting Adviser', 'value' => '6', 'icon' => 'fa-user-clock', 'tone' => 'amber'],
            ['label' => 'Awaiting Panel', 'value' => '5', 'icon' => 'fa-users', 'tone' => 'purple'],
            ['label' => 'Completed', 'value' => '22', 'icon' => 'fa-check-circle', 'tone' => 'blue'],
        ],
        'actions' => [
            ['label' => 'View Approved List', 'process' => 'view', 'icon' => 'fa-eye', 'class' => 'primary'],
            ['label' => 'Open Assignment', 'process' => 'edit', 'icon' => 'fa-tasks', 'class' => 'ghost'],
        ],
    ],
    'find-contact-adviser' => [
        'title' => 'Find/Contact Adviser',
        'description' => 'Find qualified advisers based on expertise, college, and research agenda fit.',
        'actions' => [
            ['label' => 'Find Adviser', 'process' => 'validate', 'icon' => 'fa-search', 'class' => 'primary'],
            ['label' => 'Contact Adviser', 'process' => 'submit', 'icon' => 'fa-envelope', 'class' => 'ghost'],
        ],
    ],
    'adviser-availability' => [
        'title' => 'Check Adviser Availability',
        'description' => 'Check if advisers are available or already assigned to active research groups.',
        'actions' => [
            ['label' => 'Check Availability', 'process' => 'validate', 'icon' => 'fa-calendar-check', 'class' => 'primary'],
        ],
    ],
    'assign-research-adviser' => [
        'title' => 'Assign Research Adviser',
        'description' => 'Assign the selected research adviser to an approved research group.',
        'actions' => [
            ['label' => 'Assign Adviser', 'process' => 'approve', 'icon' => 'fa-user-plus', 'class' => 'primary'],
        ],
    ],
    'find-contact-panel' => [
        'title' => 'Find/Contact Panel',
        'description' => 'Find qualified panel members based on expertise and panel load.',
        'actions' => [
            ['label' => 'Find Panel', 'process' => 'validate', 'icon' => 'fa-search', 'class' => 'primary'],
            ['label' => 'Contact Panel', 'process' => 'submit', 'icon' => 'fa-envelope', 'class' => 'ghost'],
        ],
    ],
    'panel-availability' => [
        'title' => 'Check Panel Availability',
        'description' => 'Review availability and existing assignments of panel members.',
        'actions' => [
            ['label' => 'Check Panel Availability', 'process' => 'validate', 'icon' => 'fa-calendar-alt', 'class' => 'primary'],
        ],
    ],
    'assign-panel-members' => [
        'title' => 'Assign Panel Members',
        'description' => 'Assign panel members to an approved research group.',
        'actions' => [
            ['label' => 'Assign Panel', 'process' => 'approve', 'icon' => 'fa-user-friends', 'class' => 'primary'],
        ],
    ],
    'send-notifications' => [
        'title' => 'Send Notifications',
        'description' => 'Notify students, advisers, and panel members about assignment updates.',
        'actions' => [
            ['label' => 'Send Notifications', 'process' => 'submit', 'icon' => 'fa-paper-plane', 'class' => 'primary'],
        ],
    ],
    'manage-assignments' => [
        'title' => 'View/Manage Assignments',
        'description' => 'View completed adviser and panel assignments and manage follow-up updates.',
        'actions' => [
            ['label' => 'Manage Assignments', 'process' => 'edit', 'icon' => 'fa-tasks', 'class' => 'primary'],
            ['label' => 'Assignment Report', 'process' => 'report', 'icon' => 'fa-file-export', 'class' => 'ghost'],
        ],
    ],
];

if (!isset($rcPages[$rcPageSlug])) {
    $rcPageSlug = 'approved-research';
}

$pageConfig = $rcPages[$rcPageSlug];
$pageTitle = $pageConfig['title'];
$activeModule = 'crad';
$activePage = $rcPageSlug;
$breadcrumbs = [
    ['label' => 'Research Coordinator', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => $pageTitle, 'url' => null],
];

$baseRecords = [
    [
        'reference' => 'RC-2026-018',
        'title' => 'AI-Based Enrollment Prediction Model',
        'owner' => 'BSIT 4A - Cruz Group',
        'detail' => 'Approved title',
        'status' => 'Awaiting Adviser',
        'status_class' => 'pending',
        'updated' => 'Aug 8, 2026',
    ],
    [
        'reference' => 'RC-2026-017',
        'title' => 'IoT Flood Monitoring for Campus Safety',
        'owner' => 'BSIT 4B - Santos Group',
        'detail' => 'Adviser matched',
        'status' => 'Awaiting Panel',
        'status_class' => 'review',
        'updated' => 'Aug 7, 2026',
    ],
    [
        'reference' => 'RC-2026-016',
        'title' => 'Micro-Enterprise Marketing Adaptability',
        'owner' => 'BSBA 4A - Reyes Group',
        'detail' => 'Adviser and panel assigned',
        'status' => 'Completed',
        'status_class' => 'assigned',
        'updated' => 'Aug 6, 2026',
    ],
];

$cradProcess = [
    'kicker' => 'Research Coordinator',
    'description' => $pageConfig['description'],
    'metrics' => $pageConfig['metrics'] ?? [
        ['label' => 'Available Advisers', 'value' => '12', 'icon' => 'fa-user-tie', 'tone' => 'blue'],
        ['label' => 'Available Panelists', 'value' => '18', 'icon' => 'fa-users', 'tone' => 'purple'],
        ['label' => 'Pending Assignments', 'value' => '6', 'icon' => 'fa-user-clock', 'tone' => 'amber'],
        ['label' => 'Completed', 'value' => '22', 'icon' => 'fa-check-circle', 'tone' => 'green'],
    ],
    'steps' => [
        ['View Approved Research', 'Open approved research groups or titles ready for coordination.'],
        ['Match Adviser and Panel', 'Find faculty members based on expertise, availability, and load.'],
        ['Assign and Notify', 'Record assignments and notify students, advisers, and panel members.'],
    ],
    'columns' => ['Reference', 'Research Group / Title', 'Coordinator Detail', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => $baseRecords,
    'actions' => $pageConfig['actions'],
    'form' => [
        ['label' => 'Research Reference', 'type' => 'text', 'name' => 'reference', 'placeholder' => 'RC-2026-000'],
        ['label' => 'Research Group / Title', 'type' => 'text', 'name' => 'group', 'placeholder' => 'Group or approved title'],
        ['label' => 'Adviser', 'type' => 'select', 'name' => 'adviser', 'options' => [
            'Dr. Roberto M. Santos',
            'Prof. Clara T. Reyes',
            'Dr. Ana L. Mendoza',
            'Dr. Liza M. Torres',
        ]],
        ['label' => 'Panel Member', 'type' => 'select', 'name' => 'panel', 'options' => [
            'Dr. Jose B. Tan',
            'Prof. Nina G. Cruz',
            'Dr. Art C. Lim',
            'Prof. Rhea D. Santos',
        ]],
        ['label' => 'Remarks', 'type' => 'textarea', 'name' => 'remarks', 'placeholder' => 'Assignment notes, availability, notification details...'],
    ],
    'notice' => 'Research Coordinator access is limited to approved research, adviser matching, panel assignment, notifications, and assignment management.',
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
require ROOT_PATH . '/includes/crad-module-process.php';
require_once ROOT_PATH . '/includes/layout-end.php';
