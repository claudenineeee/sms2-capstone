<?php
/**
 * SMS 2 – Create official role accounts (no demo fluff).
 * Run via browser: http://localhost/sms2-capstone/database/seed_accounts.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';

try {
    $pdo = getDatabaseConnection();
} catch (Throwable $e) {
    echo "ERROR: Cannot connect to database." . PHP_EOL;
    echo "Host: " . (defined('DB_HOST') ? DB_HOST : 'not defined') . PHP_EOL;
    echo "Database: " . (defined('DB_NAME') ? DB_NAME : 'not defined') . PHP_EOL;
    echo "User: " . (defined('DB_USER') ? DB_USER : 'not defined') . PHP_EOL;
    echo "Details: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// ── Auto-import schemas if tables do not exist yet ────────────────────────
try {
    $hasRoles = $pdo->query("SHOW TABLES LIKE 'roles'")->rowCount() > 0;
    if (!$hasRoles) {
        echo "Database tables not found. Auto-importing sms2_db.sql..." . PHP_EOL;
        $schemaFile = __DIR__ . '/sms2_db.sql';
        if (is_readable($schemaFile)) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            echo "  ✓ sms2_db.sql schema imported successfully." . PHP_EOL;
        } else {
            echo "  ✗ sms2_db.sql not found!" . PHP_EOL;
        }
    }

    $hasFaculty = $pdo->query("SHOW TABLES LIKE 'faculty_profiles'")->rowCount() > 0;
    if (!$hasFaculty) {
        $facultySqlFile = ROOT_PATH . '/modules/faculty/faculty_db.sql';
        if (is_readable($facultySqlFile)) {
            echo "Auto-importing faculty_db.sql..." . PHP_EOL;
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $fsql = file_get_contents($facultySqlFile);
            $pdo->exec($fsql);
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            echo "  ✓ faculty_db.sql schema imported successfully." . PHP_EOL;
        }
    }

    // Seed basic system settings if table is empty
    $hasSettings = (int) ($pdo->query("SELECT COUNT(*) AS c FROM system_settings")->fetch()['c'] ?? 0);
    if ($hasSettings === 0) {
        $settings = [
            'session_timeout_minutes' => '30',
            'max_failed_logins' => '3',
            'lockout_value' => '5',
            'lockout_unit' => 'minutes',
            'lockout_seconds' => '300',
            'lockout_minutes' => '5',
            'min_password_length' => '8',
            'password_expiry_days' => '0',
            'require_password_change_first_login' => '0',
            'csrf_enabled' => '1',
            'mail_from_email' => 'noreply@bestlink.edu.ph',
            'mail_from_name' => 'SMS 2',
        ];
        $insSet = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($settings as $k => $v) {
            $insSet->execute([$k, $v]);
        }
        echo "  ✓ System settings initialized." . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "Note during schema check: " . $e->getMessage() . PHP_EOL;
}

echo "Ensuring roles..." . PHP_EOL;

$roles = [
    ['superadmin', 'Super Admin', 'Full system access'],
    ['admin', 'Super Admin', 'Legacy super admin access'],
    ['admission', 'Admission', 'Admission office access'],
    ['registrar', 'Registrar', 'Enrollment, records, scheduling'],
    ['registrar_clearance', 'Registrar Clearance', 'Faculty clearance – registrar office'],
    ['finance', 'Finance', 'Payments and receivables'],
    ['finance_office', 'Finance Office', 'Faculty clearance – finance office'],
    ['hr', 'Dean', 'Dean and faculty processes'],
    ['hr_clearance', 'HR Clearance', 'Faculty clearance – HR office'],
    ['library_clearance', 'Library Clearance', 'Faculty clearance – library'],
    ['property_custodian_office', 'Property Custodian', 'Faculty clearance – property custodian'],
    ['it_office', 'IT Office', 'LMS and IT modules'],
    ['osa', 'OSA', 'Student affairs / co-curricular'],
    ['qa', 'QA Office', 'Accreditation and quality'],
    ['crad_officer', 'CRAD Officer', 'Research and development'],
    ['research_coordinator', 'Research Coordinator', 'Research coordination access'],
    ['student', 'Student', 'Student portal only'],
    ['dean', 'Dean', 'Dean faculty administration'],
    ['department_head', 'Department Head', 'Department head oversight'],
    ['secretary', 'Secretary', 'Department secretary records'],
    ['faculty', 'Faculty', 'Faculty teacher portal'],
];

$insRole = $pdo->prepare(
    'INSERT INTO roles (role_key, label, description) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description)'
);
foreach ($roles as $role) {
    $insRole->execute($role);
}

echo "Updating role permissions…" . PHP_EOL;

$pdo->exec('DELETE FROM role_permissions');

$perms = [
    'superadmin' => ['user-management', 'student_portal'],
    'admission' => ['enrollment'],
    'registrar' => ['registrar', 'curriculum', 'scheduling'],
    'registrar_clearance' => ['faculty'],
    'crad_officer' => ['crad'],
    'research_coordinator' => ['crad'],
    'finance' => ['payment'],
    'finance_office' => ['faculty'],
    'hr_clearance' => ['faculty'],
    'library_clearance' => ['faculty'],
    'property_custodian_office' => ['faculty'],
    'osa' => ['cocurricular'],
    'it_office' => ['lms'],
    'qa' => ['accreditation'],
    'hr' => ['faculty'],
    'student' => ['student_portal'],
    'dean' => ['faculty'],
    'department_head' => ['faculty'],
    'secretary' => ['faculty'],
    'faculty' => ['faculty'],
];

$insPerm = $pdo->prepare(
    'INSERT INTO role_permissions (role_key, module_key, granted) VALUES (?, ?, 1)'
);
foreach ($perms as $role => $modules) {
    foreach ($modules as $mod) {
        $insPerm->execute([$role, $mod]);
        echo "  + {$role} → {$mod}" . PHP_EOL;
    }
}

echo "Creating / updating accounts…" . PHP_EOL;

$accounts = [
    [
        'username' => 'superadmin',
        'email' => 'superadmin@bestlink.edu.ph',
        'password' => '@superadmin123',
        'full_name' => 'Super Admin',
        'role_key' => 'superadmin',
        'student_id' => null,
    ],
    [
        'username' => 'admission',
        'email' => 'admission@bestlink.edu.ph',
        'password' => '@admission123',
        'full_name' => 'Admission',
        'role_key' => 'admission',
        'student_id' => null,
    ],
    [
        'username' => 'registrar',
        'email' => 'registrar@bestlink.edu.ph',
        'password' => '@registrar123',
        'full_name' => 'Registrar',
        'role_key' => 'registrar',
        'student_id' => null,
    ],
    [
        'username' => 'cradofficer',
        'email' => 'cradofficer@bestlink.edu.ph',
        'password' => '@cradofficer123',
        'full_name' => 'CRAD Officer',
        'role_key' => 'crad_officer',
        'student_id' => null,
    ],
    [
        'username' => 'researchcoordinator',
        'email' => 'researchcoordinator@bestlink.edu.ph',
        'password' => '@research123',
        'full_name' => 'Research Coordinator',
        'role_key' => 'research_coordinator',
        'student_id' => null,
    ],
    [
        'username' => 'finance',
        'email' => 'finance@bestlink.edu.ph',
        'password' => '@finance123',
        'full_name' => 'Finance',
        'role_key' => 'finance',
        'student_id' => null,
    ],
    [
        'username' => 'studentaffairs',
        'email' => 'studentaffairs@bestlink.edu.ph',
        'password' => '@studentaffairs123',
        'full_name' => 'Student Affairs',
        'role_key' => 'osa',
        'student_id' => null,
    ],
    [
        'username' => 'itofficer',
        'email' => 'itofficer@bestlink.edu.ph',
        'password' => '@itofficer123',
        'full_name' => 'IT Officer',
        'role_key' => 'it_office',
        'student_id' => null,
    ],
    [
        'username' => 'qualityassurance',
        'email' => 'qualityassurance@bestlink.edu.ph',
        'password' => '@qualityassurance123',
        'full_name' => 'Quality Assurance',
        'role_key' => 'qa',
        'student_id' => null,
    ],
    [
        'username' => 'dean',
        'email' => 'dean@bestlink.edu.ph',
        'password' => '@dean123',
        'full_name' => 'Dean of College',
        'role_key' => 'dean',
        'student_id' => null,
    ],
    [
        'username' => 'depthead',
        'email' => 'depthead@bestlink.edu.ph',
        'password' => '@depthead123',
        'full_name' => 'Department Head',
        'role_key' => 'department_head',
        'student_id' => null,
    ],
    [
        'username' => 'secretary',
        'email' => 'secretary@bestlink.edu.ph',
        'password' => '@secretary123',
        'full_name' => 'Department Secretary',
        'role_key' => 'secretary',
        'student_id' => null,
    ],
    [
        'username' => 'faculty',
        'email' => 'faculty@bestlink.edu.ph',
        'password' => '@faculty123',
        'full_name' => 'Faculty Teacher',
        'role_key' => 'faculty',
        'student_id' => null,
    ],
    [
        'username' => 's230000001',
        'email' => 's230000001@bestlink.edu.ph',
        'password' => '@student123',
        'full_name' => 'Student User',
        'role_key' => 'student',
        'student_id' => 'S230000001',
    ],
    // --- Clearance Office Accounts ---
    [
        'username' => 'hr',
        'email' => 'hr@gmail.com',
        'password' => '12345678',
        'full_name' => 'HR Clearance Officer',
        'role_key' => 'hr_clearance',
        'student_id' => null,
    ],
    [
        'username' => 'registrarclearance',
        'email' => 'registrar@gmail.com',
        'password' => '12345678',
        'full_name' => 'Registrar Clearance Officer',
        'role_key' => 'registrar_clearance',
        'student_id' => null,
    ],
    [
        'username' => 'financeoffice',
        'email' => 'finance@gmail.com',
        'password' => '12345678',
        'full_name' => 'Finance Office Officer',
        'role_key' => 'finance_office',
        'student_id' => null,
    ],
    [
        'username' => 'library',
        'email' => 'library@gmail.com',
        'password' => '12345678',
        'full_name' => 'Library Clearance Officer',
        'role_key' => 'library_clearance',
        'student_id' => null,
    ],
    [
        'username' => 'property',
        'email' => 'property@gmail.com',
        'password' => '12345678',
        'full_name' => 'Property Custodian Officer',
        'role_key' => 'property_custodian_office',
        'student_id' => null,
    ],
];

// Clean up legacy username aliases if present
$pdo->exec("DELETE FROM users WHERE username IN ('hrclearance', 'libraryclearance', 'propertycustodian')");

$upsert = $pdo->prepare(
    'INSERT INTO users
        (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
     VALUES (?, ?, ?, ?, ?, ?, \'active\', NOW(), 0, 0, NULL)
     ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        password_hash = VALUES(password_hash),
        full_name = VALUES(full_name),
        role_key = VALUES(role_key),
        student_id = VALUES(student_id),
        status = \'active\',
        password_changed_at = NOW(),
        must_change_password = 0,
        failed_login_attempts = 0,
        locked_until = NULL'
);

foreach ($accounts as $a) {
    $upsert->execute([
        $a['username'],
        $a['email'],
        password_hash($a['password'], PASSWORD_DEFAULT),
        $a['full_name'],
        $a['role_key'],
        $a['student_id'],
    ]);
    echo "  ✓ {$a['username']} ({$a['role_key']})" . PHP_EOL;
}

// Clear legacy JSON overrides so DB is source of truth
$permFile = ROOT_PATH . '/config/perm_overrides.json';
if (is_file($permFile)) {
    @unlink($permFile);
    echo "Removed perm_overrides.json" . PHP_EOL;
}

$pdo->prepare(
    'INSERT INTO activity_logs (user_id, user_name, role_key, action, module_key, detail, ip_address)
     VALUES (NULL, ?, ?, ?, ?, ?, ?)'
)->execute(['System', 'admin', 'seed', 'System', 'Official role accounts seeded', 'cli']);

echo PHP_EOL . 'DONE. Accounts ready.' . PHP_EOL;
