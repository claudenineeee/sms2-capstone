<?php
/**
 * Shared Adviser / Panel faculty account page.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';

function facultyAccountMap(?string $role = null): array
{
    $role = $role ?? getCurrentUserRoleKey();
    $isPanel = $role === 'panel';

    return [
        'role_label' => $isPanel ? 'Panel' : 'Adviser',
        'table' => $isPanel ? 'research_panel_assignments' : 'research_adviser_assignments',
        'name_col' => $isPanel ? 'panel_name' : 'adviser_name',
        'email_col' => $isPanel ? 'panel_email' : 'adviser_email',
        'role_col' => $isPanel ? 'panel_role' : "'Research Adviser'",
    ];
}

function facultyAccountAssignments(): array
{
    $email = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $name = (string) ($_SESSION['user_name'] ?? '');
    $rows = [];

    $crad = cradDb();
    if (!$crad || $email === '') {
        return $rows;
    }

    $map = facultyAccountMap();
    $table = $map['table'];
    $nameCol = $map['name_col'];
    $emailCol = $map['email_col'];
    $roleCol = $map['role_col'];

    try {
        $stmt = $crad->prepare(
            "SELECT
                a.id,
                a.group_number,
                a.proposal_number,
                a.expertise,
                a.availability_status,
                a.assignment_status,
                a.updated_at,
                a.$nameCol AS faculty_name,
                a.$emailCol AS faculty_email,
                $roleCol AS faculty_role,
                COALESCE(rg.group_name, CONCAT('Group ', LPAD(a.research_group_id, 2, '0'))) AS group_name,
                COALESCE(rg.research_title, rp.research_title, 'Research title pending') AS research_title,
                COALESCE(rg.status, rp.status, 'Approved') AS research_status
             FROM $table a
             LEFT JOIN research_groups rg ON rg.id = a.research_group_id
             LEFT JOIN research_proposals rp ON rp.id = a.proposal_id
             WHERE LOWER(a.$emailCol) = ?
                OR LOWER(a.$nameCol) = LOWER(?)
             ORDER BY FIELD(a.assignment_status, 'Assigned', 'Pending'), a.updated_at DESC, a.id DESC"
        );
        $stmt->execute([$email, $name]);
        $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Faculty account assignment load failed: ' . $e->getMessage());
    }

    return $rows;
}

function facultyAccountPostNotice(): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['faculty_action'])) {
        return null;
    }

    try {
        requireCsrf((string) ($_POST['csrf_token'] ?? ''));
    } catch (Throwable $e) {
        return ['type' => 'danger', 'message' => 'Security token expired. Refresh the page and try again.'];
    }

    $role = getCurrentUserRoleKey();
    if (!in_array($role, ['adviser', 'panel'], true)) {
        return ['type' => 'danger', 'message' => 'This action is only available for Adviser and Panel accounts.'];
    }

    $oldName = (string) ($_SESSION['user_name'] ?? '');
    $oldEmail = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $map = facultyAccountMap($role);
    $table = $map['table'];
    $nameCol = $map['name_col'];
    $emailCol = $map['email_col'];
    $action = (string) $_POST['faculty_action'];

    try {
        if ($action === 'update_profile') {
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['type' => 'danger', 'message' => 'Enter a valid name and email.'];
            }

            $pdo = db();
            if (!$pdo) {
                return ['type' => 'danger', 'message' => 'Main database is unavailable.'];
            }

            $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ? LIMIT 1')
                ->execute([$fullName, $email, (int) getCurrentUserId()]);

            $crad = cradDb();
            if ($crad) {
                $stmt = $crad->prepare(
                    "UPDATE $table
                     SET $nameCol = ?, $emailCol = ?
                     WHERE LOWER($emailCol) = ? OR LOWER($nameCol) = LOWER(?)"
                );
                $stmt->execute([$fullName, $email, $oldEmail, $oldName]);
            }

            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            return ['type' => 'success', 'message' => 'Profile updated successfully.'];
        }

        if ($action === 'update_expertise') {
            $expertise = trim((string) ($_POST['expertise'] ?? ''));
            if ($expertise === '') {
                return ['type' => 'danger', 'message' => 'Expertise cannot be empty.'];
            }

            $crad = cradDb();
            if (!$crad) {
                return ['type' => 'danger', 'message' => 'CRAD database is unavailable.'];
            }

            $stmt = $crad->prepare(
                "UPDATE $table
                 SET expertise = ?
                 WHERE LOWER($emailCol) = ? OR LOWER($nameCol) = LOWER(?)"
            );
            $stmt->execute([$expertise, $oldEmail, $oldName]);
            return ['type' => 'success', 'message' => 'Expertise updated in the assignment database.'];
        }

    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            return ['type' => 'danger', 'message' => 'That email is already used by another account.'];
        }
        error_log('Faculty account save failed: ' . $e->getMessage());
        return ['type' => 'danger', 'message' => 'Could not save changes. Please check the database.'];
    } catch (Throwable $e) {
        error_log('Faculty account save failed: ' . $e->getMessage());
        return ['type' => 'danger', 'message' => 'Could not save changes.'];
    }

    return null;
}

function facultyAccountPrimaryExpertise(array $assignments): string
{
    foreach ($assignments as $row) {
        $expertise = trim((string) ($row['expertise'] ?? ''));
        if ($expertise !== '') {
            return $expertise;
        }
    }
    return '';
}

function facultyAccountPrimaryAvailability(array $assignments): string
{
    foreach ($assignments as $row) {
        $availability = trim((string) ($row['availability_status'] ?? ''));
        if ($availability !== '') {
            return $availability;
        }
    }
    return 'Pending';
}

function renderFacultyResearchList(array $assignments, string $emptyMessage = 'Approved adviser or panel assignments will appear here automatically.'): void
{
    if (!$assignments) {
        ?>
        <div class="faculty-empty">
            <strong>No assigned research yet.</strong>
            <div class="mt-1"><?= htmlspecialchars($emptyMessage) ?></div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="faculty-research-list">
        <?php foreach ($assignments as $row): ?>
            <?php
            $assignmentStatus = (string) ($row['assignment_status'] ?? 'Pending');
            $statusClass = strtolower($assignmentStatus) === 'assigned' ? 'success' : 'warning';
            $availability = (string) ($row['availability_status'] ?? 'Pending');
            $availabilityClass = strtolower($availability) === 'available'
                ? 'success'
                : (strtolower($availability) === 'unavailable' ? 'danger' : 'warning');
            $updatedAt = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
            ?>
            <article class="faculty-research-card">
                <div>
                    <h3><?= htmlspecialchars((string) $row['research_title']) ?></h3>
                    <div class="faculty-meta">
                        <?= htmlspecialchars((string) $row['group_name']) ?> &middot;
                        <?= htmlspecialchars((string) $row['group_number']) ?> &middot;
                        <?= htmlspecialchars((string) $row['proposal_number']) ?>
                    </div>
                    <div class="faculty-tags">
                        <span class="faculty-pill"><?= htmlspecialchars((string) $row['faculty_role']) ?></span>
                        <span class="faculty-pill"><?= htmlspecialchars((string) $row['expertise']) ?></span>
                        <span class="faculty-pill <?= $statusClass ?>"><?= htmlspecialchars($assignmentStatus) ?></span>
                        <span class="faculty-pill <?= $availabilityClass ?>"><?= htmlspecialchars($availability) ?></span>
                    </div>
                </div>
                <small class="text-muted"><?= htmlspecialchars(date('M j, Y g:i A', $updatedAt)) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function renderFacultyAccountPage(string $title, string $activePage, string $mode = 'overview'): void
{
    $role = getCurrentUserRoleKey();
    $roleLabel = facultyAccountMap($role)['role_label'];
    $notice = facultyAccountPostNotice();
    $assignments = facultyAccountAssignments();
    $assigned = array_values(array_filter($assignments, static fn($row) => strtolower((string) $row['assignment_status']) === 'assigned'));
    $pending = max(0, count($assignments) - count($assigned));
    $csrf = csrfToken();
    $primaryExpertise = facultyAccountPrimaryExpertise($assignments);
    $primaryAvailability = facultyAccountPrimaryAvailability($assignments);
    $availabilityCardClass = strtolower($primaryAvailability) === 'available'
        ? 'success'
        : (strtolower($primaryAvailability) === 'unavailable' ? 'danger' : 'warning');

    ?>
    <style>
        .faculty-account-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1rem; }
        .faculty-stat { border:1px solid var(--sms-border); border-radius:8px; background:var(--sms-card-bg); padding:1rem; }
        .faculty-stat span { display:block; color:var(--sms-text-muted); font-size:.72rem; font-weight:800; text-transform:uppercase; }
        .faculty-stat strong { display:block; color:var(--sms-text); font-size:1.45rem; line-height:1.2; }
        .faculty-research-list { display:grid; gap:.85rem; }
        .faculty-research-card { border:1px solid var(--sms-border); border-radius:8px; background:var(--sms-card-bg); padding:1rem; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:start; }
        .faculty-research-card h3 { margin:0 0 .25rem; font-size:1rem; font-weight:800; color:var(--sms-text); }
        .faculty-meta { color:var(--sms-text-muted); font-size:.82rem; font-weight:600; }
        .faculty-tags { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.7rem; }
        .faculty-pill { display:inline-flex; align-items:center; border-radius:999px; padding:.28rem .65rem; font-size:.72rem; font-weight:800; background:rgba(37,99,235,.10); color:var(--sms-primary); }
        .faculty-pill.success { background:rgba(16,185,129,.16); color:#059669; }
        .faculty-pill.warning { background:rgba(245,158,11,.16); color:#b45309; }
        .faculty-pill.danger { background:rgba(239,68,68,.14); color:#dc2626; }
        .faculty-empty { border:1px dashed var(--sms-border); border-radius:8px; padding:2.5rem 1rem; text-align:center; color:var(--sms-text-muted); background:var(--sms-card-bg); }
        .faculty-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .faculty-profile-card { border:1px solid var(--sms-border); border-radius:8px; background:var(--sms-card-bg); padding:1rem; }
        .faculty-profile-label { display:block; color:var(--sms-text-muted); font-size:.72rem; font-weight:800; text-transform:uppercase; margin-bottom:.25rem; }
        .faculty-profile-value { color:var(--sms-text); font-weight:800; overflow-wrap:anywhere; }
        .faculty-choice-row { display:flex; flex-wrap:wrap; gap:.6rem; }
        .faculty-choice-row .btn { border-radius:999px; font-weight:800; }
        .faculty-stat strong.success { color:#059669; }
        .faculty-stat strong.warning { color:#b45309; }
        .faculty-stat strong.danger { color:#dc2626; }
        @media (max-width: 991px) { .faculty-account-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .faculty-research-card { grid-template-columns:1fr; } }
        @media (max-width: 575px) { .faculty-account-grid, .faculty-form-grid { grid-template-columns:1fr; } }
    </style>

    <?php renderBreadcrumbs([
        ['label' => $roleLabel . ' Account', 'url' => BASE_URL . '/modules/faculty/index.php'],
        ['label' => $title, 'url' => null],
    ]); ?>

    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1><i class="fas fa-user-tie text-sms-primary me-2"></i><?= htmlspecialchars($title) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(getCurrentUserName()) ?> &middot; <?= htmlspecialchars((string) ($_SESSION['user_email'] ?? '')) ?></p>
        </div>
        <span class="badge rounded-pill text-bg-primary"><?= htmlspecialchars($roleLabel) ?></span>
    </div>

    <?php if ($notice): ?>
        <div class="alert alert-<?= htmlspecialchars($notice['type']) ?> fw-semibold">
            <?= htmlspecialchars($notice['message']) ?>
        </div>
    <?php endif; ?>

    <div class="faculty-account-grid">
        <section class="faculty-stat"><span>Total Records</span><strong><?= count($assignments) ?></strong></section>
        <section class="faculty-stat"><span>Assigned</span><strong><?= count($assigned) ?></strong></section>
        <section class="faculty-stat"><span>Pending</span><strong><?= $pending ?></strong></section>
        <section class="faculty-stat"><span>Availability</span><strong class="<?= $availabilityCardClass ?>"><?= htmlspecialchars($primaryAvailability) ?></strong></section>
    </div>

    <?php if ($mode === 'profile'): ?>
        <section class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0 fw-bold">My Profile</h2>
            </div>
            <div class="card-body">
                <form method="post" class="faculty-form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="faculty_action" value="update_profile">
                    <div>
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars(getCurrentUserName()) ?>" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars((string) ($_SESSION['user_email'] ?? '')) ?>" required>
                    </div>
                    <div class="faculty-profile-card">
                        <span class="faculty-profile-label">Role</span>
                        <span class="faculty-profile-value"><?= htmlspecialchars($roleLabel) ?></span>
                    </div>
                    <div class="faculty-profile-card">
                        <span class="faculty-profile-label">Expertise From DB</span>
                        <span class="faculty-profile-value"><?= htmlspecialchars($primaryExpertise !== '' ? $primaryExpertise : 'No expertise yet') ?></span>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </section>
    <?php elseif ($mode === 'profile-expertise'): ?>
        <section class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0 fw-bold">Expertise</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="faculty_action" value="update_expertise">
                    <label class="form-label fw-semibold">Expertise Based on Assignment Database</label>
                    <textarea class="form-control mb-3" name="expertise" rows="3" required><?= htmlspecialchars($primaryExpertise) ?></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Expertise
                    </button>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold"><?= htmlspecialchars($mode === 'overview' ? 'Research Overview' : $title) ?></h2>
            <small class="text-muted">Synced <?= date('M j, Y g:i:s A') ?></small>
        </div>
        <div class="card-body">
            <?php renderFacultyResearchList($assignments); ?>
        </div>
    </section>
    <?php
}
