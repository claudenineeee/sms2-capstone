<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../includes/authentication.php';
require_once __DIR__ . '/../../includes/dean_rbac.php';
requireAuth();

$uid = $_SESSION['user_id'] ?? ($_SESSION['external_user_id'] ?? null);

echo '<pre style="background:#111;color:#0f0;padding:20px;font-size:14px;">';
echo 'session user_id      : ' . htmlspecialchars((string) $uid) . PHP_EOL;
echo 'resolved profile id  : ' . var_export(getDeanProfileId($uid), true) . PHP_EOL;
echo 'assigned dept ids    : ' . print_r(getDeanAssignedDepartments($uid), true) . PHP_EOL;
echo 'dept names lookup    : ' . print_r(getDeanDepartmentNames(getDeanAssignedDepartments($uid)), true) . PHP_EOL;
echo '</pre>';