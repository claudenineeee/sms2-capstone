<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();

header('Location: ' . BASE_URL . '/modules/faculty/views/finance/faculty-clearance.php');
exit;
