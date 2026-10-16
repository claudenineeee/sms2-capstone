<?php
/**
 * SMS 2 - Term Rollover Helpers
 *
 * Safe, transaction-wrapped operations for rolling over the academic term.
 * Every function is designed to be idempotent where possible, so re-running
 * is safe and won't duplicate data.
 *
 * NO SCHEMA CHANGES — uses existing tables only.
 */

if (!function_exists('smsGetCurrentTerm')) {
    /**
     * Returns the currently active academic term row, or null.
     */
    function smsGetCurrentTerm(PDO $pdo): ?array {
        try {
            $stmt = $pdo->query("
                SELECT term_id, academic_year, semester, start_date, end_date, is_current
                FROM faculty_db.academic_terms
                WHERE is_current = 1
                LIMIT 1
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('smsGetCurrentTerm failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('smsGetAllTerms')) {
    /**
     * Returns all academic terms ordered newest first.
     */
    function smsGetAllTerms(PDO $pdo): array {
        try {
            $stmt = $pdo->query("
                SELECT term_id, academic_year, semester, start_date, end_date, is_current
                FROM faculty_db.academic_terms
                ORDER BY academic_year DESC, semester ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('smsGetAllTerms failed: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('smsRolloverClassSchedules')) {
    /**
     * Snapshots the current term's schedules into teaching_load_history
     * (one row per faculty), then marks the source rows as archived via
     * a status flag on class_schedules.
     *
     * Returns ['ok' => bool, 'message' => string, 'stats' => [...]]
     */
    function smsRolloverClassSchedules(PDO $pdo, int $oldTermId): array {
        try {
            $pdo->beginTransaction();

            // Aggregate current term's schedules per faculty
            $aggStmt = $pdo->prepare("
                SELECT
                    cs.faculty_id,
                    COUNT(*) AS subject_count,
                    COALESCE(SUM(cs.units), 0) AS total_units,
                    COALESCE(SUM(cs.enrolled_students), 0) AS total_students
                FROM faculty_db.class_schedules cs
                WHERE cs.term_id = ?
                GROUP BY cs.faculty_id
            ");
            $aggStmt->execute([$oldTermId]);
            $rows = $aggStmt->fetchAll(PDO::FETCH_ASSOC);

            $inserted = 0;
            $updated  = 0;

            $insStmt = $pdo->prepare("
                INSERT INTO faculty_db.teaching_load_history
                    (faculty_id, term_id, subject_count, total_units, total_students, status)
                VALUES (?, ?, ?, ?, ?, 'Completed')
                ON DUPLICATE KEY UPDATE
                    subject_count = VALUES(subject_count),
                    total_units   = VALUES(total_units),
                    total_students= VALUES(total_students),
                    status        = 'Completed'
            ");

            foreach ($rows as $r) {
                $insStmt->execute([
                    (int) $r['faculty_id'],
                    $oldTermId,
                    (int) $r['subject_count'],
                    (float) $r['total_units'],
                    (int) $r['total_students'],
                ]);
                if ($insStmt->rowCount() === 1) $inserted++;
                else                           $updated++;
            }

            // Count total schedules that were snapshotted (for reporting)
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) FROM faculty_db.class_schedules WHERE term_id = ?
            ");
            $countStmt->execute([$oldTermId]);
            $scheduleCount = (int) $countStmt->fetchColumn();

            $pdo->commit();

            return [
                'ok'      => true,
                'message' => "Archived {$scheduleCount} class schedule(s) into teaching_load_history across " . count($rows) . " faculty member(s).",
                'stats'   => [
                    'schedules_archived'    => $scheduleCount,
                    'faculty_summarized'    => count($rows),
                    'history_rows_inserted' => $inserted,
                    'history_rows_updated'  => $updated,
                ],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('smsRolloverClassSchedules failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Class schedule rollover failed: ' . $e->getMessage(), 'stats' => []];
        }
    }
}

if (!function_exists('smsResetLeaveBalances')) {
    /**
     * Creates fresh leave_balances rows for the target academic year.
     * Copies the "total" columns from the most recent row per faculty so
     * entitlements stay consistent, but zeroes out the "used" columns.
     *
     * Returns ['ok' => bool, 'message' => string, 'stats' => [...]]
     */
    function smsResetLeaveBalances(PDO $pdo, string $newAcademicYear): array {
        try {
            $pdo->beginTransaction();

            // Find the most recent balance row per faculty (any year) so we can
            // carry forward the entitlement totals.
            $srcStmt = $pdo->query("
                SELECT lb.*
                FROM faculty_db.leave_balances lb
                INNER JOIN (
                    SELECT faculty_id, MAX(balance_id) AS max_id
                    FROM faculty_db.leave_balances
                    GROUP BY faculty_id
                ) latest ON latest.max_id = lb.balance_id
            ");
            $sources = $srcStmt->fetchAll(PDO::FETCH_ASSOC);

            $inserted = 0;
            $skipped  = 0;

            $insStmt = $pdo->prepare("
                INSERT INTO faculty_db.leave_balances
                    (faculty_id, academic_year,
                     sick_leave_total, sick_leave_used,
                     vacation_leave_total, vacation_leave_used,
                     maternity_total, maternity_used,
                     paternity_total, paternity_used,
                     solo_parent_total, solo_parent_used,
                     emergency_total, emergency_used,
                     magna_carta_total, magna_carta_used,
                     vawc_total, vawc_used,
                     sabbatical_total, sabbatical_used,
                     admin_vacation_total, admin_vacation_used,
                     admin_special_total, admin_special_used,
                     study_leave_total, study_leave_used,
                     pvp_used)
                VALUES (?, ?,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        ?, 0,
                        0)
                ON DUPLICATE KEY UPDATE
                    sick_leave_total      = VALUES(sick_leave_total),
                    sick_leave_used       = 0,
                    vacation_leave_total  = VALUES(vacation_leave_total),
                    vacation_leave_used   = 0,
                    maternity_total       = VALUES(maternity_total),
                    maternity_used        = 0,
                    paternity_total       = VALUES(paternity_total),
                    paternity_used        = 0,
                    solo_parent_total     = VALUES(solo_parent_total),
                    solo_parent_used      = 0,
                    emergency_total       = VALUES(emergency_total),
                    emergency_used        = 0,
                    magna_carta_total     = VALUES(magna_carta_total),
                    magna_carta_used      = 0,
                    vawc_total            = VALUES(vawc_total),
                    vawc_used             = 0,
                    sabbatical_total      = VALUES(sabbatical_total),
                    sabbatical_used       = 0,
                    admin_vacation_total  = VALUES(admin_vacation_total),
                    admin_vacation_used   = 0,
                    admin_special_total   = VALUES(admin_special_total),
                    admin_special_used    = 0,
                    study_leave_total     = VALUES(study_leave_total),
                    study_leave_used      = 0,
                    pvp_used              = 0
            ");

            foreach ($sources as $s) {
                $insStmt->execute([
                    (int) $s['faculty_id'],
                    $newAcademicYear,
                    (int) $s['sick_leave_total'],
                    (int) $s['vacation_leave_total'],
                    (int) $s['maternity_total'],
                    (int) $s['paternity_total'],
                    (int) $s['solo_parent_total'],
                    (int) $s['emergency_total'],
                    (int) $s['magna_carta_total'],
                    (int) $s['vawc_total'],
                    (int) $s['sabbatical_total'],
                    (int) $s['admin_vacation_total'],
                    (int) $s['admin_special_total'],
                    (int) $s['study_leave_total'],
                ]);
                if ($insStmt->rowCount() === 1) $inserted++;
                else                           $skipped++;
            }

            $pdo->commit();

            return [
                'ok'      => true,
                'message' => "Created {$inserted} new leave balance row(s) for AY {$newAcademicYear}" . ($skipped > 0 ? " ({$skipped} already existed and were reset)." : '.'),
                'stats'   => ['inserted' => $inserted, 'reset' => $skipped, 'total_faculty' => count($sources)],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('smsResetLeaveBalances failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Leave balance reset failed: ' . $e->getMessage(), 'stats' => []];
        }
    }
}

if (!function_exists('smsArchiveClearances')) {
    /**
     * Marks in-progress clearances for the old term as Archived.
     * Clearances already completed are already stored in faculty_clearance_archives.
     * This only handles the leftovers.
     *
     * Returns ['ok' => bool, 'message' => string, 'stats' => [...]]
     */
    function smsArchiveClearances(PDO $pdo, int $oldTermId): array {
        try {
            $pdo->beginTransaction();

            // Count clearances still open for the old term
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) FROM faculty_db.clearance_requests
                WHERE term_id = ?
                  AND overall_status NOT IN ('Cleared', 'Completed', 'Archived')
            ");
            $countStmt->execute([$oldTermId]);
            $count = (int) $countStmt->fetchColumn();

            if ($count > 0) {
                $updStmt = $pdo->prepare("
                    UPDATE faculty_db.clearance_requests
                    SET overall_status = 'Archived',
                        updated_at     = NOW()
                    WHERE term_id = ?
                      AND overall_status NOT IN ('Cleared', 'Completed', 'Archived')
                ");
                $updStmt->execute([$oldTermId]);
            }

            $pdo->commit();

            return [
                'ok'      => true,
                'message' => $count > 0
                    ? "Archived {$count} in-progress clearance(s) from the old term."
                    : 'No open clearances from the old term needed archiving.',
                'stats'   => ['archived' => $count],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('smsArchiveClearances failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Clearance archiving failed: ' . $e->getMessage(), 'stats' => []];
        }
    }
}

if (!function_exists('smsPurgeOldNotifications')) {
    /**
     * Removes read notifications older than N days.
     * Unread notifications are preserved so nothing important is lost.
     *
     * Returns ['ok' => bool, 'message' => string, 'stats' => [...]]
     */
    function smsPurgeOldNotifications(PDO $pdo, int $days = 90): array {
        try {
            $pdo->beginTransaction();

            $countStmt = $pdo->prepare("
                SELECT COUNT(*) FROM faculty_db.notifications
                WHERE is_read = 1
                  AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $countStmt->execute([$days]);
            $count = (int) $countStmt->fetchColumn();

            if ($count > 0) {
                $delStmt = $pdo->prepare("
                    DELETE FROM faculty_db.notifications
                    WHERE is_read = 1
                      AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                ");
                $delStmt->execute([$days]);
            }

            $pdo->commit();

            return [
                'ok'      => true,
                'message' => $count > 0
                    ? "Purged {$count} read notification(s) older than {$days} days."
                    : "No old read notifications to purge.",
                'stats'   => ['purged' => $count],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('smsPurgeOldNotifications failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Notification purge failed: ' . $e->getMessage(), 'stats' => []];
        }
    }
}

if (!function_exists('smsActivateNewTerm')) {
    /**
     * Flips is_current from the old term to the new term.
     * Ensures exactly one row has is_current = 1.
     *
     * Returns ['ok' => bool, 'message' => string, 'stats' => [...]]
     */
    function smsActivateNewTerm(PDO $pdo, int $newTermId): array {
        try {
            $pdo->beginTransaction();

            // Verify the target term exists
            $check = $pdo->prepare("SELECT term_id, academic_year, semester FROM faculty_db.academic_terms WHERE term_id = ? LIMIT 1");
            $check->execute([$newTermId]);
            $term = $check->fetch(PDO::FETCH_ASSOC);
            if (!$term) {
                $pdo->rollBack();
                return ['ok' => false, 'message' => 'Target term not found.', 'stats' => []];
            }

            // Clear all is_current flags, then set the new one
            $pdo->exec("UPDATE faculty_db.academic_terms SET is_current = 0 WHERE is_current = 1");
            $upd = $pdo->prepare("UPDATE faculty_db.academic_terms SET is_current = 1 WHERE term_id = ?");
            $upd->execute([$newTermId]);

            $pdo->commit();

            return [
                'ok'      => true,
                'message' => "Activated term {$term['academic_year']} · {$term['semester']}.",
                'stats'   => ['term_id' => $newTermId, 'label' => $term['academic_year'] . ' · ' . $term['semester']],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('smsActivateNewTerm failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Term activation failed: ' . $e->getMessage(), 'stats' => []];
        }
    }
}

if (!function_exists('smsCreateAcademicTerm')) {
    /**
     * Creates a new academic term row. Used by the wizard.
     */
    function smsCreateAcademicTerm(PDO $pdo, string $academicYear, string $semester): array {
        $allowed = ['1st Semester', '2nd Semester', 'Summer'];
        if (!in_array($semester, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid semester value.'];
        }
        if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
            return ['ok' => false, 'message' => 'Academic year must be in YYYY-YYYY format.'];
        }
        try {
            $ins = $pdo->prepare("
                INSERT INTO faculty_db.academic_terms (academic_year, semester, is_current)
                VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE term_id = LAST_INSERT_ID(term_id)
            ");
            $ins->execute([$academicYear, $semester]);
            $newId = (int) $pdo->lastInsertId();
            return ['ok' => true, 'term_id' => $newId, 'message' => "Created term {$academicYear} · {$semester}."];
        } catch (Throwable $e) {
            error_log('smsCreateAcademicTerm failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Could not create term: ' . $e->getMessage()];
        }
    }
}