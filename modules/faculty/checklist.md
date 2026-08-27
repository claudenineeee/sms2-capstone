# Faculty Module Checklist

## 1. Database & Cross-Database Architecture

* [x] Initialize PDO connection using `pdo = db()`.
* [x] Verify permissions for cross-database access between `sms2_db` and `faculty_db`.
* [x] Query departments from `faculty_db.departments`.
* [x] Wrap department lookup in `try-catch (PDOException)`.
* [x] Provide `BSIT` fallback when the department table is empty or unavailable.

## 2. Directory Listing & RBAC

* [x] Extract `role`, `college`, and `department` from session data.
* [x] Support legacy/alternative session key names.
* [x] Allow Admin/Superadmin/Administrator to view the full directory.
* [x] Filter Dean access by college scope.
* [x] Filter Department Head access by assigned department.
* [x] Support department synonyms such as `BSIT` ↔ `Information Technology`.
* [x] Provide a safe default directory listing when scope data is unavailable.

## 3. Account Approvals & Clearance

* [x] `approve_account`: Set `sms2_db.users.status` to `active`.
* [x] `approve_account`: Set `faculty_db.faculty_profiles.profile_status` to `Active`.
* [x] Display success feedback after approval.
* [x] `reject_account`: Set `sms2_db.users.status` to `rejected`.
* [x] `reject_account`: Set `faculty_db.faculty_profiles.profile_status` to `Rejected`.
* [x] Display warning feedback after rejection.
* [x] Add badges for `Pending Approval`, `Active`, `Probationary`, `Regular`, and `Inactive`.

## 4. Search & Pagination

* [x] Implement `onSearchInput()` for name, ID, and department searches.
* [x] Limit results to 10 rows per page.
* [x] Implement `initPagination()` and `renderPage()`.
* [x] Display Previous, page numbers, and Next controls.
* [x] Display `Showing X to Y of Z entries`.
* [x] Show `#noDataRow` when no results match.

## 5. View Faculty Modal

* [x] Bind row `data-*` attributes to modal fields.
* [x] Support name, email, department, academic rank, tier, and related fields.
* [x] Generate avatar initials using `getInitials()`.
* [x] Use `—` for missing or empty values.

## 6. Edit Faculty Modal

* [x] Populate hidden `profile_id` and read-only `facultyId`.
* [x] Pre-fill personal information fields.
* [x] Pre-fill employment dates.
* [x] Populate the department dropdown using `$departments`.
* [x] Set the selected department from `button.dataset.departmentCode` or `button.dataset.department`.
* [x] Implement Academic Rank → Tier cascading dropdowns.
* [x] Submit updates through `FacultyController::handleUpdateFaculty()`.
* [x] Display backend responses using a dismissible Bootstrap alert.

# Faculty Module — Unfinished Tasks & Integration Checklist

> **Status rules:** `[ ] { }` = unfinished. `[x] {x}` = implemented, tested, and verified.
>
> Features marked **Assigned to Other Module** are not part of my implementation responsibility. Only their integration with the Faculty module is my responsibility where required.

---

# 1. Faculty / Teacher Role

## My Attendance — Integration with Attendance Module

* { } Confirm Attendance/Monitoring module's attendance API or database access method.
* { } Confirm faculty ID mapping between Authentication, Faculty, and Attendance modules.
* { } Retrieve personal attendance records using the logged-in faculty ID.
*{ } Implement `getMyAttendanceLogs($facultyId, $startDate, $endDate)`.
*  { } Build monthly/weekly attendance view with Present, Late, Absent, and Excused statuses.
*  { } Test that a faculty member can only see their own attendance.

## My Schedule — Integration with Schedule Management

* { } Confirm schedule database structure and ownership.
* { } Retrieve assigned class schedules, time slots, and rooms.
* { } Apply active semester/academic-year filtering.
* { } Connect Faculty schedule view to approved schedules.
* { } Build printable weekly timetable/calendar UI.
* { } Test that faculty only see their assigned schedules.

## Leave Request — Assigned to Other Module

* [x]  Coordinate with the Leave Application module owner.
* [x]  Confirm leave-request API/database contract.
* [x]  Retrieve the logged-in faculty's leave requests.
* [x]  Display Pending, Approved, and Rejected statuses.
* [x]  Connect Faculty leave UI to the Leave Application module.
* [x]  Test leave status synchronization.

## Teaching Load

* []  Retrieve assigned subjects, sections, total units, and overload details.
* []  Display teaching-load breakdown with unit totals.
* [] Implement load acceptance/acknowledgment action.
* []  Connect teaching load to approved schedules/subject assignments.
* []  Test that accepted teaching loads match the faculty's assigned subjects.


## Evaluation

* [ ] Retrieve evaluation results and student feedback.
* [ ] Calculate overall evaluation averages per term.
* [ ] Display ratings summary and qualitative feedback.
* [ ] Confirm evaluation data ownership and API/database contract.
* [ ] Test that faculty can only view their own evaluation results.

## Notifications

* [ ] Retrieve notifications from `sms2_db.notifications`.
* [ ] Implement `getNotifications()`.
* [ ] Implement `markAsRead()`.
* [ ] Build notification feed with unread counters.
* [ ] Test notifications using the authenticated `user_id`.

---

# 2. Dean Role

## Teaching History

* [ ] { } Retrieve historical faculty subject assignments.
* [ ] { } Add academic-year and semester filtering.
* [ ] { } Verify historical data against approved teaching loads and schedules.

## Subject Load Tracker

* [ ] { } Cross-reference faculty load limits across college departments.
* [ ] { } Implement unit-cap validation.
* [ ] { } Build college load summary cards and detail modals.
* [ ] { } Integrate with approved teaching loads.
* [ ] { } Integrate with faculty schedules where required.

## Attendance Monitoring — Assigned to Other Module

* [ ] { } Coordinate with Attendance/Monitoring module owner.
* [ ] { } Confirm attendance reporting API/database contract.
* [ ] { } Retrieve college-wide attendance metrics for Dean views.
* [ ] { } Add date-range filtering to the Faculty module view if required.
* [ ] { } Build Dean attendance breakdown UI only if assigned to this module.
* [ ] { } Test college-scope restrictions.

## Leave Application & Approval — Assigned to Other Module

* [x] Coordinate with Leave Application module owner.
* [x] Confirm Dean approval API/endpoint.
* [x] Consume leave application status for Dean dashboard if required.
* [x] Ensure Dean only sees requests within their college scope.
* [x] Integrate approval status with Faculty/Secretary views.

## Evaluation Summary

* [x] Aggregate college evaluation performance.
* [x] Implement weighted-average calculations.
* [x] Build performance charts.
* [x] Build faculty performance breakdown.
* [x] Apply Dean college-scope filtering.

## Clearance System — Assigned to Other Module

* [ ] {x} Coordinate with Clearance module owner.
* [ ] {x} Confirm Dean sign-off API/endpoint.
* [ ] {x} Retrieve final clearance status for Dean views if required.
* [ ] {x} Integrate Dean clearance status with Faculty views.
* [ ] {x} Verify college-level access restrictions.

---

# 3. Department Head Role

## Faculty Performance

* [x] {x} Retrieve performance ratings restricted to the Head's department.
* [x] {x} Build department performance dashboard.
* [x] {x} Add KPI summaries and faculty rating lists.

## Schedule Approval

* [ ] { } Confirm who owns schedule creation.
* [ ] { } Retrieve schedules submitted for Department Head review.
* [ ] { } Implement room/time conflict checking.
* [ ] { } Implement `approveSchedule()`.
* [ ] { } Implement schedule rejection/return-for-revision logic.
* [ ] { } Build timetable review matrix.
* [ ] { } Build approval/rejection action modals.
* [ ] { } Ensure only schedules belonging to the Head's department are visible.
* [ ] { } Connect approved schedules to Faculty "My Schedule".
* [ ] { } Connect approved schedules to Teaching Load.
* [ ] { } Test schedule conflicts before approval.

## Teaching Load Approval

* [ ] { } Validate teaching-load unit boundaries.
* [ ] { } Implement batch `approveTeachingLoad()`.
* [ ] { } Build review table with overload/underload indicators.
* [ ] { } Connect approved teaching loads to Faculty "Teaching Load".
* [ ] { } Verify approved loads correspond to schedules/subject assignments.

## Faculty Clearance — Assigned to Other Module

* [ ] { } Coordinate with Clearance module owner.
* [ ] { } Confirm Department Head sign-off API/endpoint.
* [ ] { } Consume departmental clearance status where required.
* [ ] { } Verify department-scope restrictions.

## Reports

* [ ] { } Build department summary queries.
* [ ] { } Implement PDF/Excel report generation.
* [ ] { } Build report configuration with date filters.
* [ ] { } Add export buttons.
* [ ] { } Verify reports use only the Department Head's department data.

---

# 4. Secretary Role

## Dashboard

* [ ] { } Implement `getSecretaryDashboardStats()`.
* [ ] { } Build summary cards.
* [ ] { } Build recent-activity feed.

## Faculty Records

* [ ] { } Implement Secretary-level faculty CRUD.
* [ ] { } Build searchable faculty directory.
* [ ] { } Add document audit view.

## Leave Request — Assigned to Other Module

* [x] Coordinate with Leave Application module owner.
* [x] Confirm physical-request intake API/database contract.
* [x] Integrate Secretary document intake with Leave Application.
* [x] Verify submitted documents are associated with the correct faculty/user.
* [x] Test leave status synchronization.

## Reports

* [ ] { } Build document-tracking queries.
* [ ] { } Implement report generation.
* [ ] { } Build print layout.
* [ ] { } Add export actions.

---

# 5. Monitoring / Attendance Officer — Assigned to Other Module

> **Implementation is assigned to another teammate/module. Do not duplicate the Attendance system here.**

## Dashboard

* [ ] { } Coordinate with Attendance module owner.
* [ ] { } Confirm attendance API/database contract.
* [ ] { } Confirm attendance status definitions.
* [ ] { } Confirm faculty ID mapping.

## Daily Attendance

* [x] Coordinate with Attendance module owner regarding manual attendance overrides.
* [x] Coordinate with Attendance module owner regarding attendance threshold calculations.
* [x] Coordinate with Attendance module owner regarding daily attendance records.
* [x] Coordinate with Attendance module owner regarding time corrections.
* [x] Confirm Present/Late/Absent/Excused status definitions.

## Reports

* [ ] { } Confirm Attendance module's reporting API.
* [ ] { } Consume attendance reports where required by Faculty/Dean/Department Head.
* [ ] { } Verify report data respects role and department/college scope.

---

# 6. Integration Dependencies

## A. Authentication → Faculty Module

* [ ] { } Obtain authenticated `user_id`.
* [ ] { } Map `user_id` to `faculty_profiles.user_id`.
* [ ] { } Retrieve faculty ID from the authenticated account.
* [ ] { } Confirm role, college, and department information.
* [ ] { } Ensure unauthorized users cannot access Faculty pages.

## B. Faculty Profile → Other Modules

* [ ] { } Define the common faculty identifier.
* [ ] { } Ensure Attendance uses the correct faculty ID.
* [ ] { } Ensure Schedule uses the correct faculty ID.
* [ ] { } Ensure Teaching Load uses the correct faculty ID.
* [ ] { } Ensure Leave uses the correct user/faculty ID.
* [ ] { } Ensure Clearance uses the correct user/faculty ID.
* [ ] { } Ensure Evaluation uses the correct faculty ID.

## C. Schedule Creation → Teaching Load → Faculty Schedule

* [ ] { } Identify the module responsible for creating schedules.
* [ ] { } Identify who creates/submits schedules.
* [ ] { } Identify who approves schedules.
* [ ] { } Define schedule status: Draft → Submitted → Approved/Rejected.
* [ ] { } Ensure schedule records contain faculty ID.
* [ ] { } Ensure schedule records contain subject/course ID.
* [ ] { } Ensure schedule records contain section ID.
* [ ] { } Ensure schedule records contain room.
* [ ] { } Ensure schedule records contain day/time.
* [ ] { } Ensure schedule records contain semester/academic year.
* [ ] { } Implement room/time conflict validation.
* [ ] { } Connect approved schedules to Teaching Load.
* [ ] { } Connect approved schedules to Faculty "My Schedule".
* [ ] { } Prevent Faculty from seeing unapproved schedules if required.
* [ ] { } Test approved schedule propagation across modules.

## D. Teaching Load → Schedule Integration

* [ ] { } Confirm which module creates teaching loads.
* [ ] { } Confirm which module approves teaching loads.
* [ ] { } Verify assigned subject exists before creating schedule.
* [ ] { } Verify faculty is assigned to the subject before scheduling.
* [ ] { } Verify unit totals are consistent.
* [ ] { } Verify overload/underload calculations.
* [ ] { } Prevent schedules from exceeding approved teaching loads.

## E. Attendance → Faculty Schedule Integration

* [ ] { } Confirm Attendance module receives approved schedule information.
* [ ] { } Match attendance records to faculty ID.
* [ ] { } Match attendance records to subject/section.
* [ ] { } Match attendance records to scheduled date/time.
* [ ] { } Prevent attendance records from being assigned to the wrong faculty.

## F. Leave → Faculty / Dean / Secretary Integration

* [ ] { } Confirm Leave module owns leave application records.
* [ ] { } Connect Faculty leave requests to the Leave module.
* [ ] { } Connect Secretary document intake to Leave.
* [ ] { } Connect Dean approval to Leave.
* [ ] { } Return approval status to Faculty.
* [ ] { } Trigger notification when leave status changes.

## G. Clearance → Faculty / Secretary / Department Head / Dean

* [ ] { } Confirm Clearance module owns clearance records.
* [ ] { } Connect Faculty clearance progress to Clearance.
* [ ] { } Connect Secretary verification to Clearance.
* [ ] { } Connect Department Head sign-off to Clearance.
* [ ] { } Connect Dean final sign-off to Clearance.
* [ ] { } Return final clearance status to Faculty.
* [ ] { } Trigger notifications when clearance status changes.

## H. Evaluation → Faculty / Dean / Department Head

* [ ] { } Confirm Evaluation module owns evaluation records.
* [ ] { } Connect faculty evaluation results to Faculty profile.
* [ ] { } Provide aggregated results to Dean.
* [ ] { } Provide department-scoped results to Department Head.
* [ ] { } Ensure faculty cannot view another faculty member's private evaluation data.

## I. Notifications → All Modules

* [ ] { } Define common notification format.
* [ ] { } Store `user_id` as notification recipient.
* [ ] { } Include module/source reference.
* [ ] { } Include notification status (`is_read`).
* [ ] { } Trigger notifications for important events.
* [ ] { } Test notifications from Schedule, Leave, Clearance, Evaluation, and other modules.

---

# 7. Schedule Workflow — Recommended Integration Order

* [ ] { } Faculty/Secretary/Department Head creates or prepares schedule data.
* [ ] { } Validate faculty assignment.
* [ ] { } Validate subject/section assignment.
* [ ] { } Validate room availability.
* [ ] { } Validate time conflicts.
* [ ] { } Validate teaching-load limits.
* [ ] { } Submit schedule for approval.
* [ ] { } Department Head reviews schedule.
* [ ] { } Department Head approves or rejects schedule.
* [ ] { } Store final schedule status.
* [ ] { } Make approved schedule available to Faculty "My Schedule".
* [ ] { } Make approved schedule available to Attendance/Monitoring.
* [ ] { } Update teaching-load information if required.
* [ ] { } Notify affected faculty after approval/rejection.

---

# 8. Ownership Summary

| Module / Feature      | Ownership                        | My Responsibility                       |
| --------------------- | -------------------------------- | --------------------------------------- |
| Faculty Profile       | Me                               | Full implementation                     |
| Faculty Directory     | Me                               | Full implementation                     |
| My Attendance         | Shared                           | Faculty-side integration                |
| Attendance Monitoring | Other teammate                   | Integration only                        |
| My Schedule           | Shared                           | Faculty-side schedule view/integration  |
| Schedule Creation     | Depends on team assignment       | Integration with Faculty                |
| Schedule Approval     | Department Head / schedule owner | Integration                             |
| Teaching Load         | Shared                           | Faculty-side implementation/integration |
| Leave Application     | Other teammate                   | Integration only                        |
| Clearance             | Other teammate                   | Integration only                        |
| Evaluation            | Me / assigned scope              | Faculty + role-specific integration     |
| Notifications         | Shared                           | Integration with relevant events        |
| Secretary             | Me / assigned scope              | Implement assigned features             |
| Dean                  | Me / assigned scope              | Implement assigned features             |
| Department Head       | Me / assigned scope              | Implement assigned features             |

---

# 9. Definition of Done

For every task:

* [ ] { } Code is implemented.
* [ ] { } Database/API integration works.
* [ ] { } Correct role permissions are enforced.
* [ ] { } Correct faculty/department/college scope is enforced.
* [ ] { } Feature has been manually tested.
* [ ] { } Existing related features still work.
* [ ] { } No unrelated files/features were changed unnecessarily.
* [ ] { } `[x] {x}` is applied only after successful verification.
