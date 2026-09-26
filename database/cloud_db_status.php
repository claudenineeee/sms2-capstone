<?php
/**
 * SMS 2 - Cloud Database Diagnostic & Setup Tool
 * Helps verify connection to HostForge / Cloud MariaDB and initialize schemas/accounts.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';

$action = $_GET['action'] ?? '';
$message = '';
$messageType = 'info';

$dbConnected = false;
$dbError = '';
$tableCount = 0;
$userCount = 0;
$tables = [];

try {
    $pdo = getDatabaseConnection();
    $dbConnected = true;

    // Get list of tables
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);

    if (in_array('users', $tables, true)) {
        $uStmt = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $userCount = (int) ($uStmt->fetch()['c'] ?? 0);
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

// Handle Migration / Seed Actions
if ($dbConnected && ($action === 'migrate' || $action === 'seed')) {
    @set_time_limit(300);
    @ini_set('memory_limit', '256M');

    try {
        if ($action === 'migrate') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            // Drop any existing leftover tables to prevent "Table already exists" errors
            $stmt = $pdo->query('SHOW TABLES');
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($existing as $t) {
                $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
            }

            // 1. sms2_db.sql
            $sms2SqlFile = __DIR__ . '/sms2_db.sql';
            if (is_readable($sms2SqlFile)) {
                $pdo->exec(file_get_contents($sms2SqlFile));
            }

            // 2. faculty_db.sql
            $facultySqlFile = ROOT_PATH . '/modules/faculty/faculty_db.sql';
            if (is_readable($facultySqlFile)) {
                $pdo->exec(file_get_contents($facultySqlFile));
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Run seed_accounts logic
        ob_start();
        include __DIR__ . '/seed_accounts.php';
        $seedOutput = ob_get_clean();

        // Refresh counts
        $stmt = $pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);
        if (in_array('users', $tables, true)) {
            $uStmt = $pdo->query('SELECT COUNT(*) AS c FROM users');
            $userCount = (int) ($uStmt->fetch()['c'] ?? 0);
        }

        $message = ($action === 'migrate')
            ? "Database schemas imported and accounts seeded successfully! Total tables: {$tableCount}. Users ready: {$userCount}."
            : "Accounts re-seeded successfully! Total tables: {$tableCount}. Users ready: {$userCount}.";
        $messageType = 'success';
    } catch (Throwable $e) {
        $message = "Migration failed: " . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Database Status | SMS 2</title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #0f172a;
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 2rem 1rem;
        }

        .card-custom {
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
        }

        .badge-status {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
        }

        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            color: #38bdf8;
        }

        pre {
            background: #020617;
            padding: 1rem;
            border-radius: 8px;
            color: #a5f3fc;
            font-size: 0.85rem;
            max-height: 250px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container" style="max-width: 820px;">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-secondary">
            <h2 class="h4 mb-0"><i class="fas fa-database text-primary me-2"></i>SMS 2 — Cloud Database Status</h2>
            <a href="<?= BASE_URL ?>/login/login.php" class="btn btn-sm btn-outline-light"><i
                    class="fas fa-sign-in-alt me-1"></i>Go to Login</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?> mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Connection Card -->
        <div class="card card-custom mb-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">MySQL / MariaDB Connection</h5>
                <?php if ($dbConnected): ?>
                    <span class="badge bg-success badge-status"><i class="fas fa-check-circle me-1"></i>Connected</span>
                <?php else: ?>
                    <span class="badge bg-danger badge-status"><i class="fas fa-times-circle me-1"></i>Disconnected</span>
                <?php endif; ?>
            </div>

            <table class="table table-dark table-sm table-borderless text-light mb-3">
                <tbody>
                    <tr>
                        <th style="width: 140px;" class="text-secondary">Host:</th>
                        <td><code><?= htmlspecialchars(defined('DB_HOST') ? DB_HOST : 'N/A') ?></code></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Port:</th>
                        <td><code><?= htmlspecialchars(defined('DB_PORT') ? (string) DB_PORT : 'N/A') ?></code></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Database:</th>
                        <td><code><?= htmlspecialchars(defined('DB_NAME') ? DB_NAME : 'N/A') ?></code></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Username:</th>
                        <td><code><?= htmlspecialchars(defined('DB_USER') ? DB_USER : 'N/A') ?></code></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Password:</th>
                        <td><code><?= defined('DB_PASS') && DB_PASS !== '' ? '••••••••' : '(empty)' ?></code></td>
                    </tr>
                </tbody>
            </table>

            <?php if (!$dbConnected): ?>
                <div class="alert alert-danger mb-3">
                    <strong>Connection Error:</strong><br>
                    <code><?= htmlspecialchars($dbError) ?></code>
                </div>

                <div class="card bg-dark border-secondary p-3">
                    <h6 class="text-warning mb-2"><i class="fas fa-info-circle me-1"></i>How to connect HostForge Managed
                        Database:</h6>
                    <p class="small text-secondary mb-2">
                        In your <strong>HostForge Project Dashboard</strong>, navigate to your web service settings &rarr;
                        <strong>Environment Variables</strong> and ensure the following keys are set from your database
                        service:
                    </p>
                    <pre class="mb-0">DB_CONNECTION=mysql
    DATABASE_URL=mysql://hf_ayxb2xxquc:yTgMwNim9sdpjBWpG8ol4vtc9UrTXI8p@mariadb-skhid4fv.internal:3306/hf_db_skhid4fv
    DB_HOST=mariadb-skhid4fv.internal
    DB_PORT=3306
    DB_DATABASE=hf_db_skhid4fv
    DB_USERNAME=hf_ayxb2xxquc
    DB_PASSWORD=yTgMwNim9sdpjBWpG8ol4vtc9UrTXI8p</pre>
                </div>
            <?php else: ?>
                <div class="row g-3 mb-3 text-center">
                    <div class="col-6">
                        <div class="p-3 bg-dark rounded">
                            <div class="h3 mb-0 text-info"><?= $tableCount ?></div>
                            <div class="small text-secondary">Tables in Database</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-dark rounded">
                            <div class="h3 mb-0 text-success"><?= $userCount ?></div>
                            <div class="small text-secondary">Seeded Users</div>
                        </div>
                    </div>
                </div>

                <?php if ($tableCount === 0 || $userCount === 0): ?>
                    <div class="alert alert-warning mb-3">
                        <strong>Notice:</strong> Your database is connected, but has <?= $tableCount ?> tables and
                        <?= $userCount ?> users. Click the button below to upload and seed the database schemas now.
                    </div>
                    <a href="?action=migrate" class="btn btn-primary w-100 py-2 fw-bold"
                        onclick="return confirm('Run database setup and seed accounts now?');">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Run Database Upload & Seed Accounts
                    </a>
                <?php else: ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check me-2"></i><strong>Database Ready!</strong> You can log in with any seeded
                        account.
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/login/login.php" class="btn btn-success flex-fill py-2 fw-bold">
                            <i class="fas fa-sign-in-alt me-1"></i>Go to Sign In
                        </a>
                        <a href="?action=seed" class="btn btn-outline-info py-2"
                            onclick="return confirm('Re-seed official accounts and permissions without wiping tables?');">
                            <i class="fas fa-user-check me-1"></i>Re-seed Accounts
                        </a>
                        <a href="?action=migrate" class="btn btn-outline-warning py-2"
                            onclick="return confirm('Wipe tables and re-run fresh migration & seed?');">
                            <i class="fas fa-redo me-1"></i>Fresh Reinstall
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Seeded Credentials Quick Reference -->
        <div class="card card-custom p-4">
            <h5 class="mb-3"><i class="fas fa-users-cog text-info me-2"></i>Default Credentials Reference</h5>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-primary">Super Admin</span></td>
                            <td>superadmin@bestlink.edu.ph</td>
                            <td><code>@superadmin123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary">Faculty</span></td>
                            <td>faculty@bestlink.edu.ph</td>
                            <td><code>@faculty123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary">Dean</span></td>
                            <td>dean@bestlink.edu.ph</td>
                            <td><code>@dean123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary">Registrar</span></td>
                            <td>registrar@bestlink.edu.ph</td>
                            <td><code>@registrar123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary">HR Clearance</span></td>
                            <td>hr@gmail.com</td>
                            <td><code>12345678</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary">Finance Office</span></td>
                            <td>finance@gmail.com</td>
                            <td><code>12345678</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>