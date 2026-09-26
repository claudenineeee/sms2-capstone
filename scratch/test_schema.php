<?php
require_once __DIR__ . '/../modules/faculty/controllers/clearance.php';
$pdo = getFacultyDatabaseConnection();
facultyClearanceEnsureDigitalApprovalSchema($pdo);
$cols = $pdo->query("SHOW COLUMNS FROM clearance_office_approvals")->fetchAll(PDO::FETCH_ASSOC);
echo "=== clearance_office_approvals columns ===\n";
foreach ($cols as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ")\n";
}
