-- ========================================================
-- SMS 2 & Faculty Portal - Clean Unified Cloud Database Setup
-- HostForge / MariaDB / MySQL Compatible
-- ========================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- version 5.2.1
-- https://www.phpmyadmin.net/
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



--
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `role_key` varchar(40) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `detail` varchar(500) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------

--
-- Table structure for table `login_throttles`
--

CREATE TABLE `login_throttles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `throttle_key` char(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_throttle_key` (`throttle_key`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `module_key` varchar(60) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `requested_password_hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_note` varchar(500) DEFAULT NULL,
  `temp_password_set` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `label` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_key`, `label`, `description`, `is_system`, `created_at`) VALUES
(1, 'admin', 'Super Admin', 'Legacy super admin access', 1, '2026-07-22 22:24:44'),
(2, 'registrar', 'Registrar', 'Enrollment, records, scheduling', 1, '2026-07-22 22:24:44'),
(3, 'finance', 'Finance', 'Payments and receivables', 1, '2026-07-22 22:24:44'),
(4, 'hr', 'HR', 'Faculty and HR processes', 1, '2026-07-22 22:24:44'),
(5, 'it_office', 'IT Office', 'LMS and IT modules', 1, '2026-07-22 22:24:44'),
(6, 'osa', 'OSA', 'Student affairs / co-curricular', 1, '2026-07-22 22:24:44'),
(7, 'qa', 'QA Office', 'Accreditation and quality', 1, '2026-07-22 22:24:44'),
(8, 'crad_officer', 'CRAD Officer', 'Research and development', 1, '2026-07-22 22:24:44'),
(9, 'student', 'Student', 'Student portal only', 1, '2026-07-22 22:24:44'),
(10, 'superadmin', 'Super Admin', 'Full system access', 1, '2026-08-09 18:27:15'),
(11, 'admission', 'Admission', 'Admission office access', 1, '2026-08-09 18:27:15'),
(12, 'research_coordinator', 'Research Coordinator', 'Research coordination access', 1, '2026-08-09 18:27:15'),
(13, 'adviser', 'Adviser', 'Research adviser faculty account', 1, '2026-08-09 18:27:15'),
(14, 'panel', 'Panel', 'Research panel faculty account', 1, '2026-08-09 18:27:15'),
(76, 'department_head', 'Department Head', 'Department head faculty processes', 1, '2026-08-10 01:31:00'),
(77, 'secretary', 'Secretary', 'Faculty secretary processes', 1, '2026-08-10 01:31:00'),
(78, 'faculty', 'Faculty', 'Faculty/teacher access', 1, '2026-08-10 01:31:00'),
(128, 'faculty_schedule_officer', 'Faculty Schedule Officer', 'Assign faculty schedule', 1, '2026-08-12 03:26:12'),
(185, 'faculty_admin', 'Faculty Admin', 'Administrative oversight of the faculty module', 1, '2026-08-15 01:14:06'),
(186, 'monitoring_officer', 'Monitoring Officer', 'Monitors faculty compliance and activity', 1, '2026-08-15 01:14:06'),
(218, 'dean', 'Dean', 'Oversees one or more academic departments', 1, '2026-08-24 02:26:16'),
(232, 'hr_clearance', 'HR Clearance', 'Manages HR clearance processes', 1, '2026-09-06 22:33:45'),
(233, 'registrar_clearance', 'Registrar Clearance', 'Manages registrar clearance processes', 1, '2026-09-06 22:33:45'),
(234, 'library_clearance', 'Library Clearance', 'Manages library clearance processes', 1, '2026-09-06 22:33:45'),
(237, 'finance_office', 'Finance Office', 'Finance Office Staff', 1, '2026-09-06 23:23:02'),
(238, 'property_custodian_office', 'Property Custodian Office', 'Manages property accountability and clearances', 1, '2026-09-06 23:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `module_key` varchar(60) NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_key`, `module_key`, `granted`, `updated_at`) VALUES
(19, 'registrar', 'enrollment', 1, '2026-08-06 20:12:08'),
(20, 'registrar', 'registrar', 1, '2026-07-22 22:53:59'),
(21, 'registrar', 'curriculum', 1, '2026-07-22 22:53:59'),
(22, 'registrar', 'scheduling', 1, '2026-07-22 22:53:59'),
(23, 'crad_officer', 'crad', 1, '2026-07-22 22:53:59'),
(24, 'finance', 'payment', 1, '2026-07-22 22:53:59'),
(25, 'osa', 'cocurricular', 1, '2026-07-22 22:53:59'),
(26, 'it_office', 'lms', 1, '2026-08-23 15:06:08'),
(27, 'qa', 'accreditation', 1, '2026-07-22 22:53:59'),
(28, 'hr', 'faculty', 1, '2026-07-22 22:53:59'),
(29, 'student', 'student_portal', 1, '2026-07-22 22:53:59'),
(32, 'admission', 'enrollment', 1, '2026-08-09 18:27:15'),
(33, 'adviser', 'faculty', 1, '2026-08-09 18:27:15'),
(34, 'panel', 'faculty', 1, '2026-08-11 01:52:18'),
(65, 'department_head', 'faculty', 1, '2026-08-10 01:31:00'),
(66, 'secretary', 'faculty', 1, '2026-08-10 01:31:00'),
(67, 'faculty', 'faculty', 1, '2026-08-10 01:31:00'),
(102, 'faculty_schedule_officer', 'faculty', 1, '2026-08-12 03:26:12'),
(133, 'faculty_admin', 'faculty', 1, '2026-08-15 01:14:06'),
(134, 'monitoring_officer', 'faculty', 1, '2026-08-15 01:14:06'),
(148, 'it_office', 'payment', 0, '2026-08-23 15:06:23'),
(168, 'finance_office', 'faculty', 1, '2026-09-07 19:57:24');

-- --------------------------------------------------------

--
-- Table structure for table `security_otps`
--

CREATE TABLE `security_otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `purpose` varchar(40) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('csrf_enabled', '1', '2026-07-22 22:24:44'),
('lockout_minutes', '1', '2026-07-23 08:05:06'),
('lockout_seconds', '15', '2026-07-23 08:05:06'),
('lockout_unit', 'seconds', '2026-07-23 08:05:06'),
('lockout_value', '15', '2026-07-23 08:05:06'),
('mail_admin_email', 'j14677365@gmail.com', '2026-07-23 10:34:27'),
('mail_from_email', 'noreply@bestlink.edu.ph', '2026-07-23 10:33:25'),
('mail_from_name', 'SMS 2', '2026-07-23 10:33:25'),
('mail_show_link_on_failure', '0', '2026-07-23 10:34:27'),
('max_failed_logins', '3', '2026-07-23 07:33:05'),
('min_password_length', '8', '2026-07-22 22:24:44'),
('module_kick_epoch_crad', '1784849304', '2026-07-23 15:28:24'),
('module_maintenance_crad', '0', '2026-07-23 15:29:22'),
('module_maintenance_msg_crad', 'The system is currently under maintenance. Some services may be temporarily unavailable.\r\n\r\nThank you for your patience and understanding.', '2026-07-23 15:05:14'),
('password_expiry_days', '0', '2026-07-22 22:24:44'),
('require_password_change_first_login', '0', '2026-07-22 22:24:44'),
('session_timeout_minutes', '30', '2026-07-22 22:24:44'),
('smtp_encryption', 'tls', '2026-07-23 10:33:25'),
('smtp_host', 'smtp.gmail.com', '2026-07-23 10:51:48'),
('smtp_password', 'sms2enc1.BnNN43RIftF9bLKe7buHTa6/qaxuGXWg5XruC7mKh67ZYei7aPH7AeOKNpdXJ7A=', '2026-08-06 12:08:17'),
('smtp_port', '587', '2026-07-23 10:33:25'),
('smtp_username', 'j14677365@gmail.com', '2026-07-23 10:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `status` enum('active','inactive','locked','suspended') NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `last_login_at`, `last_seen_at`, `last_login_ip`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'kennethabejuela0308@gmail.com', '$2y$10$o48cjRxVOhYsuBWzhYqHHuHr0l2HLqSRERtOoe6viDpP0xRSgtQuu', 'Super Admin', 'superadmin', NULL, 'active', 0, 0, NULL, '2026-08-06 13:10:29', '2026-08-27 01:44:05', NULL, '::1', NULL, '2026-07-22 22:53:59', '2026-08-27 01:45:11'),
(2, 'registrar', 'registrar@example.com', '$2y$10$q0d8MsCulEkFYl0N1zSDNe0ToXMWTtXFCHftbvYdePuP8Orjs2lYO', 'Registrar', 'registrar_clearance', NULL, 'active', 0, 0, NULL, '2026-08-06 13:16:14', '2026-09-24 08:25:03', NULL, '::1', NULL, '2026-07-22 22:53:59', '2026-09-24 08:25:09'),
(3, 'cradofficer', 'angelicadublin340@gmail.com', '$2y$10$MpwtxHnKofWTxV5/axRiPuudxLEFIdLJChvSoykU9poFiH/W9wRPK', 'CRAD Officer', 'crad_officer', NULL, 'active', 0, 0, NULL, '2026-08-06 13:31:50', '2026-08-07 22:01:51', '2026-08-07 22:07:41', '::1', NULL, '2026-07-22 22:53:59', '2026-08-07 22:07:41'),
(4, 'finance', 'monvictortesiorna@gmail.com', '$2y$10$mOPKz95hA/OlTNHGzzgLEuvYqMBNAE1RdQFThECQjfv94o.RbvIZq', 'Finance', 'finance', NULL, 'active', 0, 2, NULL, '2026-08-06 20:20:39', '2026-08-07 14:28:00', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-09-07 20:10:40'),
(5, 'studentaffairs', 'studentaffairs@bestlink.edu.ph', '$2y$10$lViM6fo1qu33TQ8G45UW6OF6op7etas9WBZ12cvQdEPSKuU7TGmXW', 'Student Affairs', 'osa', NULL, 'active', 0, 0, NULL, '2026-07-22 22:54:00', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-07-22 22:54:00'),
(6, 'itofficer', 'itofficer@bestlink.edu.ph', '$2y$10$fIFFgaSnSssf4ZdaYupnZ.fzX6dYDfE7escqc/GMedxVZUHCaqCPe', 'IT Officer', 'it_office', NULL, 'active', 0, 0, NULL, '2026-07-22 22:54:00', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-07-22 22:54:00'),
(7, 'qualityassurance', 'qualityassurance@bestlink.edu.ph', '$2y$10$Bm/Te5m0uFyTRDhDDV.lf.9HuUEe7qIUOfZtHXF2eufIIXL1N3IVC', 'Quality Assurance', 'qa', NULL, 'active', 0, 0, NULL, '2026-07-22 22:54:00', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-07-22 22:54:00'),
(9, 's230000001', 'kenlangmalakas0308@gmail.com', '$2y$10$E0IiZOWMscnUfdX8H7gxt.5YzIkUqLK.qn07WF9MCA0StJhToFn2q', 'Student User', 'student', 'S230000001', 'active', 0, 0, NULL, '2026-08-06 13:17:16', '2026-08-07 15:27:51', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-07 15:29:05'),
(11, 'admission', 'admission@bestlink.edu.ph', '$2y$10$8wRzkkIHcsmsGHDwVhTXmeMlWGbHtylBJcQUV.8JuSspmUVuioP1G', 'Admission', 'admission', NULL, 'active', 0, 0, NULL, '2026-08-09 18:27:15', NULL, NULL, NULL, NULL, '2026-08-09 18:27:15', '2026-08-09 18:27:15'),
(44, 'departmenthead', 'departmenthead@bestlink.edu.ph', '$2y$10$zetuMf6PPbFBs9KybGEap.RwJc59VdWoUIseM7fvfQg2pKgaN463e', 'Department Head', 'department_head', NULL, 'active', 0, 0, NULL, '2026-08-10 01:52:53', '2026-08-14 13:51:11', NULL, '::1', NULL, '2026-08-10 01:31:00', '2026-08-14 13:53:18'),
(45, 'secretary', 'secretary@bestlink.edu.ph', '$2y$10$fKm8UsVAwGoQoo.1h8smw.7pnuxX0937LzFMPqRxyKT9wt2T7m5ye', 'Secretary', 'secretary', NULL, 'active', 0, 0, NULL, '2026-08-10 01:53:27', '2026-08-16 02:58:15', NULL, '::1', NULL, '2026-08-10 01:31:00', '2026-08-16 02:59:02'),
(46, 'faculty', 'faculty@bestlink.edu.ph', '$2y$10$R1uQbxKRilhWkaEeCMnNne6LsgMhRTt.i5F9Izk5N6ufS8WJUdDr.', 'Jean Claude', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-10 01:54:02', '2026-08-16 02:59:16', '2026-08-16 03:19:40', '::1', NULL, '2026-08-10 01:31:00', '2026-08-16 03:19:40'),
(149, 'claudeespejo@gmail.com', 'claudeespejo@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Jean Espejo', 'department_head', NULL, 'active', 0, 0, NULL, '2026-08-14 11:59:29', '2026-09-27 00:50:59', NULL, '::1', NULL, '2026-08-14 11:58:31', '2026-09-27 00:52:33'),
(151, 'facultyadmin', 'gitirene6@gmail.com', '$2y$10$OB99jFDNiuB2z5ag2LTfj.npSx2gzctwJ3hNtrzFXc59JDk7TsBgW', 'Faculty Admin', 'faculty_admin', NULL, 'active', 0, 0, NULL, '2026-09-26 17:05:07', '2026-09-26 23:24:40', NULL, '::1', NULL, '2026-08-15 01:14:06', '2026-09-26 23:50:47'),
(152, 'monitoring', 'monitoring@bestlink.edu.ph', '$2y$10$VA/SJ7ZDJfaphs4elb5O7O1FxgCV2gakN7tMqG49T0UUf83OJmdO.', 'Monitoring Officer', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-15 01:41:50', '2026-08-17 00:45:17', NULL, '::1', NULL, '2026-08-15 01:14:06', '2026-08-17 01:32:54'),
(158, 'asdfghjkl@gmail.com', 'asdfghjkl@gmail.com', '$2y$10$krRJZwchT6S4kL9sEoiOy.mp4XhL1THTugqcDFJk5wOtOCNWFOxB2', 'dwadsadwa Qwerty', 'secretary', NULL, 'active', 0, 0, NULL, '2026-08-17 00:22:48', '2026-09-24 01:11:06', NULL, '::1', NULL, '2026-08-17 00:21:59', '2026-09-24 01:11:21'),
(159, 'jeanie@gmail.com', 'jeanie@gmail.com', '$2y$10$XT67xyI5B6ebSjLVKaXwoOHmmGsD79vo6GJX11HYYlBiPqgJr9MM6', 'Claude Claude', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-17 01:42:24', '2026-09-26 15:03:21', NULL, '::1', NULL, '2026-08-17 01:40:22', '2026-09-26 15:05:33'),
(219, 'registrar_head', 'registrar.head@bestlink.edu.ph', '$2y$10$PLACEHOLDER', 'Registrar Head Name', 'registrar', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-06 21:51:08'),
(220, 'registrar_officer_01', 'jr.aban23@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Registrar Officer Name', 'registrar', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-27 01:25:35'),
(221, 'hr_manager', 'hr.manager@bestlink.edu.ph', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'HR Manager Name', 'hr', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-27 01:25:45'),
(222, 'hr_officer_01', 'hr.officer01@bestlink.edu.ph', '$2y$10$PLACEHOLDER', 'HR Officer Name', 'hr', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-06 21:51:08'),
(227, 'hr', 'brianbrix002@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'HR User', 'hr_clearance', NULL, 'active', 0, 0, NULL, NULL, '2026-09-24 08:24:52', NULL, '::1', NULL, '2026-09-06 22:41:32', '2026-09-27 01:20:46'),
(229, 'property_custodian', 'xcross0105@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Property Custodian User', 'registrar_clearance', NULL, 'active', 0, 0, NULL, NULL, '2026-09-24 08:24:10', NULL, '::1', NULL, '2026-09-06 22:41:32', '2026-09-27 01:20:18'),
(230, 'library', 'diosdado.rocero.aban@gmail.com', '$2y$10$q0d8MsCulEkFYl0N1zSDNe0ToXMWTtXFCHftbvYdePuP8Orjs2lYO$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Library User', 'library_clearance', NULL, 'active', 0, 0, NULL, NULL, '2026-09-24 08:23:59', NULL, '::1', NULL, '2026-09-06 22:41:32', '2026-09-27 01:20:23'),
(234, 'finance_office', 'robletone23@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Finance Office User', 'finance_office', NULL, 'active', 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-07 20:10:23', '2026-09-27 01:20:26'),
(250, 'whitybrix@gmail.com', 'whitybrix@gmail.com', '$2y$10$e2ljqEJnXNpnkmz12ScPlOkoWj0KwF6xem4.rUXIB1kcrf15GMJtC', 'Light Yagami', 'faculty', NULL, 'active', 0, 0, NULL, '2026-09-27 00:39:37', '2026-09-26 23:52:14', NULL, '::1', NULL, '2026-09-11 02:12:42', '2026-09-27 00:41:04'),
(255, 'bardpanget2025@gmail.com', 'bardpanget2025@gmail.com', '$2y$10$KpZ4LECpPi8Bh8xe5yvBwuqTPbQw7Ik6eJUr.ye4hljbWsUmTVhwG', 'Earl Salvame', 'secretary', NULL, 'active', 0, 0, NULL, '2026-09-27 00:55:32', '2026-09-27 00:55:06', NULL, '::1', NULL, '2026-09-24 08:34:28', '2026-09-27 00:57:53'),
(256, 'mingthecat002@gmail.com', 'mingthecat002@gmail.com', '$2y$10$qMtpnrJLl7c3oyO/CPaTduob/8vIW9m3czkVyA46Ru8Mx/VWp7Uc6', 'Alfred Joseph Alcantara', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-09-27 00:59:30', '2026-09-27 00:59:13', '2026-09-27 01:22:19', '::1', NULL, '2026-09-24 08:45:01', '2026-09-27 01:22:19'),
(260, 'midnightgaming099@gmail.com', 'midnightgaming099@gmail.com', '$2y$10$H6ZLXcmoILKClSelab7PkO4mNqAZreyQ6s2aMFzIPYqkOq8rYpkNy', 'Jorge Lucero', 'department_head', NULL, 'active', 0, 0, NULL, '2026-09-26 18:34:59', '2026-09-26 18:34:43', NULL, '::1', NULL, '2026-09-26 18:30:51', '2026-09-26 18:35:47'),
(261, 'idkwhoistonio@gmail.com', 'idkwhoistonio@gmail.com', '$2y$10$3fdBTxfIX3Jd6Pz15mScCeGjQnM/Y8PCDj.JSB/1s7RkSIim89dVO', 'Sofia Reyes', 'faculty', NULL, 'active', 0, 0, NULL, '2026-09-26 22:55:36', '2026-09-26 22:55:22', NULL, '::1', NULL, '2026-09-26 22:49:46', '2026-09-26 22:57:28'),
(262, 'jcespejo002@gmail.com', 'jcespejo002@gmail.com', '$2y$10$XTAELrWVNuDaMAt5aMFGuu4VuN1I/NP9b8oH/3Yrz3UEYX4waM9He', 'Jorge Lucero', 'dean', NULL, 'active', 0, 0, NULL, '2026-09-26 23:22:59', '2026-09-26 23:22:41', NULL, '::1', NULL, '2026-09-26 23:21:19', '2026-09-26 23:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_authenticators`
--

CREATE TABLE `user_authenticators` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `secret` varchar(512) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `pending_secret` varchar(512) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_passkeys`
--

CREATE TABLE `user_passkeys` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` text NOT NULL,
  `sign_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `device_name` varchar(120) NOT NULL DEFAULT 'Passkey',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user` (`user_id`),
  ADD KEY `idx_logs_action` (`action`),
  ADD KEY `idx_logs_created` (`created_at`);

--
-- Indexes for table `login_throttles`
--
ALTER TABLE `login_throttles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_login_throttle_key` (`throttle_key`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_user` (`user_id`),
  ADD KEY `idx_reset_token` (`token_hash`),
  ADD KEY `idx_reset_expires` (`expires_at`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prr_user` (`user_id`),
  ADD KEY `idx_prr_status` (`status`),
  ADD KEY `idx_prr_module` (`module_key`),
  ADD KEY `fk_prr_admin` (`admin_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_key` (`role_key`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_module` (`role_key`,`module_key`),
  ADD KEY `idx_perm_module` (`module_key`);

--
-- Indexes for table `security_otps`
--
ALTER TABLE `security_otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_key`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_student_id` (`student_id`),
  ADD KEY `idx_users_last_seen` (`last_seen_at`);

--
-- Indexes for table `user_authenticators`
--
ALTER TABLE `user_authenticators`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_passkeys`
--
ALTER TABLE `user_passkeys`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1906;

--
-- AUTO_INCREMENT for table `login_throttles`
--
ALTER TABLE `login_throttles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `security_otps`
--
ALTER TABLE `security_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- AUTO_INCREMENT for table `user_authenticators`
--
ALTER TABLE `user_authenticators`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_passkeys`
--
ALTER TABLE `user_passkeys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `fk_prr_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_perm_role` FOREIGN KEY (`role_key`) REFERENCES `roles` (`role_key`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_key`) REFERENCES `roles` (`role_key`) ON UPDATE CASCADE;

--
-- Constraints for table `user_authenticators`
--
ALTER TABLE `user_authenticators`
  ADD CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;



-- version 5.2.1
-- https://www.phpmyadmin.net/
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



--
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_terms`
--

CREATE TABLE `academic_terms` (
  `term_id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` enum('1st Semester','2nd Semester','Summer') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_terms`
--

INSERT INTO `academic_terms` (`term_id`, `academic_year`, `semester`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, '2026-2027', '1st Semester', NULL, NULL, 1, '2026-08-21 17:22:11');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `attendance_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `campus_id` int(10) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent','On Leave') NOT NULL,
  `hours_rendered` decimal(4,1) DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_by_external_id` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`attendance_id`, `faculty_id`, `campus_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_rendered`, `signature_data`, `notes`, `recorded_by_external_id`, `created_at`) VALUES
(12, 101, 1, '2026-09-22', NULL, NULL, 'Present', NULL, 'data:image/png;base64,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', NULL, NULL, '2026-09-22 06:06:35');

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `campus_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`campus_id`, `code`, `name`, `address`, `created_at`, `updated_at`) VALUES
(1, 'MAIN', 'Main Campus', NULL, '2026-08-22 05:36:05', '2026-08-22 05:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `students` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `students`, `status`) VALUES
(1, 50, 0),
(2, 50, 1),
(3, 50, 0),
(4, 50, 0),
(5, 50, 0);

-- --------------------------------------------------------

--
-- Table structure for table `class_attendance_sessions`
--

CREATE TABLE `class_attendance_sessions` (
  `session_id` int(10) UNSIGNED NOT NULL,
  `class_schedule_id` int(10) UNSIGNED DEFAULT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `campus_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED DEFAULT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `time_slot` varchar(30) DEFAULT NULL,
  `attending_students` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `secretary_verifier_external_id` varchar(64) DEFAULT NULL,
  `secretary_verifier_name` varchar(150) DEFAULT NULL,
  `status` enum('Pending','Present','Absent','Completed','Finalized') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `class_schedule_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED DEFAULT NULL,
  `section` varchar(10) NOT NULL,
  `units` decimal(4,1) NOT NULL DEFAULT 3.0,
  `day_pattern` varchar(20) DEFAULT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `enrolled_students` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('Proposed','Approved','Rejected') NOT NULL DEFAULT 'Proposed',
  `has_conflict` tinyint(1) NOT NULL DEFAULT 0,
  `conflict_notes` varchar(255) DEFAULT NULL,
  `approved_by_external_id` varchar(64) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_approval_history`
--

CREATE TABLE `clearance_approval_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `requirement_id` int(10) UNSIGNED DEFAULT NULL,
  `office` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `performed_by_id` int(10) UNSIGNED DEFAULT NULL,
  `performed_by_name` varchar(150) NOT NULL,
  `performed_by_role` varchar(100) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_items`
--

CREATE TABLE `clearance_items` (
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_office_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Missing',
  `remarks` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(500) DEFAULT NULL,
  `cleared_by_external_id` varchar(64) DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_offices`
--

CREATE TABLE `clearance_offices` (
  `clearance_office_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `sequence_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearance_offices`
--

INSERT INTO `clearance_offices` (`clearance_office_id`, `name`, `description`, `sequence_order`) VALUES
(1, 'Letter of Intent', NULL, 1),
(2, 'Updated Resume', NULL, 2),
(3, 'Personal Evaluation', NULL, 3),
(4, 'Summary Evaluation', NULL, 4),
(5005, 'Academic Clearance', 'Grade sheets, class records, syllabus, attendance/DTR, pending student academic concerns.', 1),
(5006, 'Department Clearance', 'Department reports, assigned duties, committee responsibilities, Department Head verification.', 6),
(5007, 'Library Clearance', 'No unreturned books/materials, library accountabilities cleared.', 2),
(5008, 'Property Clearance', 'School equipment returned, ID/keys/other issued institutional property returned.', 4),
(5009, 'Financial Clearance', 'No outstanding financial obligations, cash advances/accountabilities settled.', 3),
(5010, 'HR Clearance', 'Required HR documents submitted, contract/employment records, final HR verification.', 5);

-- --------------------------------------------------------

--
-- Table structure for table `clearance_office_approvals`
--

CREATE TABLE `clearance_office_approvals` (
  `id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `office` varchar(50) NOT NULL,
  `approval_ref` varchar(50) NOT NULL,
  `approver_user_id` int(10) UNSIGNED NOT NULL,
  `approver_name` varchar(150) NOT NULL,
  `approver_role` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Approved',
  `remarks` text DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL,
  `approved_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_requests`
--

CREATE TABLE `clearance_requests` (
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_no` varchar(50) DEFAULT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `intent_type` enum('renewal','resignation','regularization') NOT NULL,
  `overall_status` varchar(50) NOT NULL DEFAULT 'In Progress',
  `submitted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `form_submitted` tinyint(1) NOT NULL DEFAULT 0,
  `form_submitted_at` datetime DEFAULT NULL,
  `form_status` varchar(50) NOT NULL DEFAULT 'Not Submitted',
  `form_approved_at` datetime DEFAULT NULL,
  `form_approved_by` int(10) UNSIGNED DEFAULT NULL,
  `form_remarks` text DEFAULT NULL,
  `faculty_declaration` text DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL,
  `dh_verified` tinyint(1) NOT NULL DEFAULT 0,
  `dh_verified_at` datetime DEFAULT NULL,
  `dh_verified_by` int(10) UNSIGNED DEFAULT NULL,
  `dh_remarks` text DEFAULT NULL,
  `faculty_declared` tinyint(1) NOT NULL DEFAULT 0,
  `faculty_declared_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_signatures`
--

CREATE TABLE `clearance_signatures` (
  `signature_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `office` varchar(100) NOT NULL,
  `office_key` varchar(50) NOT NULL,
  `signatory_type` enum('faculty','department_head','office_signatory','dean','admin') NOT NULL DEFAULT 'office_signatory',
  `approval_ref` varchar(50) DEFAULT NULL,
  `signer_user_id` int(10) UNSIGNED DEFAULT NULL,
  `signer_name` varchar(150) NOT NULL,
  `signer_role` varchar(100) NOT NULL,
  `signature_data` longtext NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Signed',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `signed_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(10) UNSIGNED NOT NULL,
  `campus_id` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `campus_id`, `code`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'BSIT', 'Bachelor of Science in Information Technology', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-24 16:57:31'),
(2, NULL, 'BSED', 'Bachelor of Secondary Education', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-16 18:57:56'),
(3, NULL, 'BS CRIM', 'Bachelor of Science in Criminology', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-23 19:04:24'),
(4, NULL, 'BSBA', 'Bachelor of Science in Business Administration', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-16 18:18:47');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(10) UNSIGNED NOT NULL,
  `document_no` varchar(20) NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `document_type` enum('Contract','Certificate','ID','Training','Others') NOT NULL,
  `document_name` varchar(200) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `upload_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Valid','Expiring Soon','Expired') NOT NULL DEFAULT 'Valid',
  `uploaded_by_external_id` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `source_type` enum('Student','Peer','DeptHead') NOT NULL,
  `evaluator_id` int(10) UNSIGNED DEFAULT NULL,
  `evaluator_external_id` varchar(64) DEFAULT NULL,
  `composite_score` decimal(3,2) DEFAULT NULL,
  `rating_label` varchar(50) DEFAULT NULL,
  `eval_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `response_rate` decimal(5,2) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_categories`
--

CREATE TABLE `evaluation_categories` (
  `evaluation_category_id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `letter` char(1) NOT NULL,
  `title` varchar(150) NOT NULL,
  `score` decimal(3,2) NOT NULL,
  `percentage` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_feedback`
--

CREATE TABLE `evaluation_feedback` (
  `evaluation_feedback_id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `strength_comment` text DEFAULT NULL,
  `improvement_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `faculty_no` varchar(20) NOT NULL,
  `external_user_id` varchar(64) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `sex` enum('male','female') DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `campus_id` int(10) UNSIGNED DEFAULT NULL,
  `position` enum('Faculty Secretary','Faculty Professor','Department Head','Department Secretary','Attendance Monitoring Officer','Dean') DEFAULT NULL,
  `assignment_label` varchar(100) DEFAULT NULL,
  `is_coordinator` tinyint(1) NOT NULL DEFAULT 0,
  `coordinator_type` enum('NSTP','OJT','RESEARCH') DEFAULT NULL,
  `academic_rank` enum('instructor','assistant_professor','associate_professor','professor') NOT NULL DEFAULT 'instructor',
  `tier` varchar(30) DEFAULT NULL,
  `employment_status` enum('Regular','Probationary','Part-Time') NOT NULL DEFAULT 'Probationary',
  `profile_status` enum('Active','On Leave','Inactive') NOT NULL DEFAULT 'Active',
  `overall_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `hired_date` date DEFAULT NULL,
  `contractual_end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `faculty_no`, `external_user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthdate`, `sex`, `phone`, `email`, `department_id`, `campus_id`, `position`, `assignment_label`, `is_coordinator`, `coordinator_type`, `academic_rank`, `tier`, `employment_status`, `profile_status`, `overall_rating`, `hired_date`, `contractual_end_date`, `created_at`, `updated_at`) VALUES
(51, 'FAC-2026-0100', '8', 'Jean', NULL, 'Claude', NULL, NULL, NULL, NULL, 'dean@bestlink.edu.ph', 1, NULL, '', NULL, 0, NULL, 'instructor', NULL, 'Regular', 'Active', 0.00, '2026-08-22', NULL, '2026-08-22 06:25:47', '2026-08-23 18:10:32'),
(53, 'FAC-2026-0004', '149', 'Jean', 'Claude', 'Espejo', '', NULL, NULL, NULL, 'claudeespejo@gmail.com', 1, NULL, 'Faculty Professor', NULL, 0, NULL, 'instructor', NULL, 'Probationary', 'Active', 0.00, '2023-01-30', NULL, '2026-08-25 07:00:55', '2026-08-25 07:00:55'),
(101, 'FAC-2026-0069', '248', 'Light', 'A', 'Yagami', '', '2002-01-29', 'male', '09318298352', 'whitybrix@gmail.com', 1, NULL, 'Faculty Professor', NULL, 0, NULL, 'instructor', NULL, 'Probationary', 'Active', 0.00, '2026-02-18', '2027-10-13', '2026-09-10 17:45:44', '2026-09-10 17:45:44'),
(105, 'FAC-2026-0107', '256', 'Alfred Joseph', 'A', 'Alcantara', '', '2002-05-22', 'male', '09318298352', 'mingthecat002@gmail.com', 1, NULL, 'Attendance Monitoring Officer', NULL, 0, NULL, 'instructor', NULL, 'Regular', 'Active', 0.00, '2026-03-12', NULL, '2026-09-24 00:51:19', '2026-09-24 00:51:19'),
(109, 'FAC-2026-0111', '261', 'Sofia', 'A', 'Reyes', '', '2002-03-23', 'female', '09318278392', 'idkwhoistonio@gmail.com', 1, NULL, 'Faculty Professor', NULL, 0, NULL, 'instructor', NULL, 'Regular', 'Active', 0.00, '2025-07-09', NULL, '2026-09-26 14:52:45', '2026-09-26 14:52:45'),
(110, 'FAC-2026-0112', '262', 'Jorge', 'V', 'Lucero', '', '1990-03-23', 'male', '09318278392', 'jcespejo002@gmail.com', 1, NULL, 'Dean', NULL, 0, NULL, 'instructor', NULL, 'Regular', 'Active', 0.00, '2022-09-23', NULL, '2026-09-26 15:21:31', '2026-09-26 15:21:31');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_class_assignments`
--

CREATE TABLE `faculty_class_assignments` (
  `id` int(11) NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(11) NOT NULL,
  `units` enum('1','2','3','4','5') NOT NULL,
  `room` varchar(100) NOT NULL,
  `time` varchar(100) NOT NULL,
  `days` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','waiting for approval') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_class_assignments`
--

INSERT INTO `faculty_class_assignments` (`id`, `faculty_id`, `class_id`, `units`, `room`, `time`, `days`, `status`) VALUES
(1, 35, 2, '3', '301', '10:00 AM - 12:00 PM', 'Mon, Wed, Sun', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives`
--

CREATE TABLE `faculty_clearance_archives` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_no` varchar(50) DEFAULT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `overall_status` varchar(50) DEFAULT 'Cleared',
  `items_json` longtext DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_approvals_json` longtext DEFAULT NULL,
  `approval_history_json` longtext DEFAULT NULL,
  `dh_verification_json` longtext DEFAULT NULL,
  `declaration_json` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_clearance_archives`
--

INSERT INTO `faculty_clearance_archives` (`archive_id`, `clearance_id`, `clearance_no`, `faculty_id`, `term_id`, `profile_id`, `faculty_no`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `phone`, `designated_department`, `position`, `academic_rank`, `tier`, `employment_status`, `contractual_end`, `academic_year`, `semester`, `intent_type`, `overall_status`, `items_json`, `submitted_at`, `completed_at`, `created_at`, `office_approvals_json`, `approval_history_json`, `dh_verification_json`, `declaration_json`) VALUES
(1, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2029-08-06', '2026-2027', '1st Semester', 'renewal', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-ff0c3002f79536928efad93d.pdf\",\"file_path\":\"faculty-clearance/35/1-ff0c3002f79536928efad93d.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:14:27\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:14:32\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:14:32\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:14:32\"}]', '2026-08-20 11:10:18', '2026-08-20 11:14:32', '2026-08-20 03:10:40', NULL, NULL, NULL, NULL),
(2, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2030-08-05', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-ff0c3002f79536928efad93d.pdf\",\"file_path\":\"faculty-clearance/35/1-ff0c3002f79536928efad93d.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:14:27\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:19:22\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:19:22\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:19:22\"}]', '2026-08-20 11:10:18', '2026-08-20 11:19:22', '2026-08-20 03:19:22', NULL, NULL, NULL, NULL),
(3, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2031-08-04', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-82190735fe747cd3c0879659.pdf\",\"file_path\":\"faculty-clearance/35/1-82190735fe747cd3c0879659.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:26:43\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Hold\",\"file_name\":\"194-625672e16edc18238dae7f22.pdf\",\"file_path\":\"faculty-clearance/35/194-625672e16edc18238dae7f22.pdf\",\"remarks\":\"[Denied] we\",\"cleared_at\":\"2026-08-20 11:26:48\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Hold\",\"file_name\":\"195-8054e332b2ef3b83aea41553.pdf\",\"file_path\":\"faculty-clearance/35/195-8054e332b2ef3b83aea41553.pdf\",\"remarks\":\"[Denied] w\",\"cleared_at\":\"2026-08-20 11:26:51\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Hold\",\"file_name\":\"196-ed334fa77d71f8a93ae02082.pdf\",\"file_path\":\"faculty-clearance/35/196-ed334fa77d71f8a93ae02082.pdf\",\"remarks\":\"[On Hold] qwes\",\"cleared_at\":\"2026-08-20 11:26:55\"}]', '2026-08-20 11:26:08', '2026-08-20 11:27:02', '2026-08-20 03:27:02', NULL, NULL, NULL, NULL),
(4, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2031-08-04', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-82190735fe747cd3c0879659.pdf\",\"file_path\":\"faculty-clearance/35/1-82190735fe747cd3c0879659.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:26:43\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Hold\",\"file_name\":\"194-625672e16edc18238dae7f22.pdf\",\"file_path\":\"faculty-clearance/35/194-625672e16edc18238dae7f22.pdf\",\"remarks\":\"[Denied] we\",\"cleared_at\":\"2026-08-20 11:26:48\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Hold\",\"file_name\":\"195-8054e332b2ef3b83aea41553.pdf\",\"file_path\":\"faculty-clearance/35/195-8054e332b2ef3b83aea41553.pdf\",\"remarks\":\"[Denied] w\",\"cleared_at\":\"2026-08-20 11:26:51\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Hold\",\"file_name\":\"196-ed334fa77d71f8a93ae02082.pdf\",\"file_path\":\"faculty-clearance/35/196-ed334fa77d71f8a93ae02082.pdf\",\"remarks\":\"[On Hold] qwes\",\"cleared_at\":\"2026-08-20 11:26:55\"}]', '2026-08-20 11:26:08', '2026-08-20 11:31:15', '2026-08-20 03:31:15', NULL, NULL, NULL, NULL),
(5, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2032-08-03', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:32:05\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Cleared\",\"file_name\":\"194-a5e817f33c879cc74cca9cb9.pdf\",\"file_path\":\"faculty-clearance/35/194-a5e817f33c879cc74cca9cb9.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:31:40\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Hold\",\"file_name\":\"195-a5a2b9422cd1a81aef6246ca.pdf\",\"file_path\":\"faculty-clearance/35/195-a5a2b9422cd1a81aef6246ca.pdf\",\"remarks\":\"[On Hold] s\",\"cleared_at\":\"2026-08-20 11:31:47\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Hold\",\"file_name\":\"196-6291fe9bccdc65bb88ad5ca7.pdf\",\"file_path\":\"faculty-clearance/35/196-6291fe9bccdc65bb88ad5ca7.pdf\",\"remarks\":\"[Denied] w\",\"cleared_at\":\"2026-08-20 11:31:50\"}]', '2026-08-20 11:31:24', '2026-08-20 11:32:05', '2026-08-20 03:32:05', NULL, NULL, NULL, NULL),
(6, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2032-08-03', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:34:16\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Cleared\",\"file_name\":\"194-a5e817f33c879cc74cca9cb9.pdf\",\"file_path\":\"faculty-clearance/35/194-a5e817f33c879cc74cca9cb9.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:31:40\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Hold\",\"file_name\":\"195-a5a2b9422cd1a81aef6246ca.pdf\",\"file_path\":\"faculty-clearance/35/195-a5a2b9422cd1a81aef6246ca.pdf\",\"remarks\":\"[On Hold] s\",\"cleared_at\":\"2026-08-20 11:31:47\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Hold\",\"file_name\":\"196-6291fe9bccdc65bb88ad5ca7.pdf\",\"file_path\":\"faculty-clearance/35/196-6291fe9bccdc65bb88ad5ca7.pdf\",\"remarks\":\"[Denied] w\",\"cleared_at\":\"2026-08-20 11:31:50\"}]', '2026-08-20 11:31:24', '2026-08-20 11:34:16', '2026-08-20 03:34:16', NULL, NULL, NULL, NULL),
(7, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2033-08-02', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:34:54\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Cleared\",\"file_name\":\"194-3cb85145df8b977cd5a80e10.pdf\",\"file_path\":\"faculty-clearance/35/194-3cb85145df8b977cd5a80e10.pdf\",\"remarks\":\"1\",\"cleared_at\":\"2026-08-20 11:34:44\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"\",\"file_name\":\"195-1d97bc682865eef852631bc3.pdf\",\"file_path\":\"faculty-clearance/35/195-1d97bc682865eef852631bc3.pdf\",\"remarks\":\"[On Hold] 2\",\"cleared_at\":\"2026-08-20 11:34:40\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Hold\",\"file_name\":\"196-e80811a93c67990b035ac837.pdf\",\"file_path\":\"faculty-clearance/35/196-e80811a93c67990b035ac837.pdf\",\"remarks\":\"[Denied] 1\",\"cleared_at\":\"2026-08-20 11:34:51\"}]', '2026-08-20 11:34:24', '2026-08-20 11:34:54', '2026-08-20 03:34:54', NULL, NULL, NULL, NULL),
(8, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2033-08-02', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:40:55\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Cleared\",\"file_name\":\"194-3cb85145df8b977cd5a80e10.pdf\",\"file_path\":\"faculty-clearance/35/194-3cb85145df8b977cd5a80e10.pdf\",\"remarks\":\"1\",\"cleared_at\":\"2026-08-20 11:34:44\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"\",\"file_name\":\"195-1d97bc682865eef852631bc3.pdf\",\"file_path\":\"faculty-clearance/35/195-1d97bc682865eef852631bc3.pdf\",\"remarks\":\"[On Hold] 2\",\"cleared_at\":\"2026-08-20 11:34:40\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Hold\",\"file_name\":\"196-e80811a93c67990b035ac837.pdf\",\"file_path\":\"faculty-clearance/35/196-e80811a93c67990b035ac837.pdf\",\"remarks\":\"[Denied] 1\",\"cleared_at\":\"2026-08-20 11:34:51\"}]', '2026-08-20 11:34:24', '2026-08-20 11:40:55', '2026-08-20 03:40:55', NULL, NULL, NULL, NULL),
(9, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2034-08-01', '2026-2027', '1st Semester', 'regularization', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:41:27\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"On Hold\",\"file_name\":\"194-8a9f0f8c01ac839fc9b57426.pdf\",\"file_path\":\"faculty-clearance/35/194-8a9f0f8c01ac839fc9b57426.pdf\",\"remarks\":\"[On Hold] \",\"cleared_at\":\"2026-08-20 11:41:21\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Cleared\",\"file_name\":\"195-c81a8802d4a9864148d79bed.pdf\",\"file_path\":\"faculty-clearance/35/195-c81a8802d4a9864148d79bed.pdf\",\"remarks\":null,\"cleared_at\":\"2026-08-20 11:41:23\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Denied\",\"file_name\":\"196-e3f09a19224a649aade12f67.pdf\",\"file_path\":\"faculty-clearance/35/196-e3f09a19224a649aade12f67.pdf\",\"remarks\":\"[Denied] \",\"cleared_at\":\"2026-08-20 11:41:25\"}]', '2026-08-20 11:41:03', '2026-08-20 11:41:27', '2026-08-20 03:41:27', NULL, NULL, NULL, NULL),
(10, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2035-07-31', '2026-2027', '1st Semester', 'renewal', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-1d6aa69af77e6eae3bff2d08.pdf\",\"file_path\":\"faculty-clearance/35/1-1d6aa69af77e6eae3bff2d08.pdf\",\"remarks\":null,\"cleared_at\":\"2026-08-20 11:45:48\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"On Hold\",\"file_name\":\"194-8a9f0f8c01ac839fc9b57426.pdf\",\"file_path\":\"faculty-clearance/35/194-8a9f0f8c01ac839fc9b57426.pdf\",\"remarks\":\"[On Hold] \",\"cleared_at\":\"2026-08-20 11:41:21\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Cleared\",\"file_name\":\"195-c81a8802d4a9864148d79bed.pdf\",\"file_path\":\"faculty-clearance/35/195-c81a8802d4a9864148d79bed.pdf\",\"remarks\":null,\"cleared_at\":\"2026-08-20 11:41:23\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Denied\",\"file_name\":\"196-e3f09a19224a649aade12f67.pdf\",\"file_path\":\"faculty-clearance/35/196-e3f09a19224a649aade12f67.pdf\",\"remarks\":\"[Denied] \",\"cleared_at\":\"2026-08-20 11:41:25\"}]', '2026-08-20 11:41:03', '2026-08-20 11:45:51', '2026-08-20 03:45:51', NULL, NULL, NULL, NULL),
(11, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2036-07-30', '2026-2027', '1st Semester', 'renewal', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-1d6aa69af77e6eae3bff2d08.pdf\",\"file_path\":\"faculty-clearance/35/1-1d6aa69af77e6eae3bff2d08.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:47:25\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"On Hold\",\"file_name\":\"194-8a9f0f8c01ac839fc9b57426.pdf\",\"file_path\":\"faculty-clearance/35/194-8a9f0f8c01ac839fc9b57426.pdf\",\"remarks\":\"[On Hold] \",\"cleared_at\":\"2026-08-20 11:41:21\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Cleared\",\"file_name\":\"195-c81a8802d4a9864148d79bed.pdf\",\"file_path\":\"faculty-clearance/35/195-c81a8802d4a9864148d79bed.pdf\",\"remarks\":null,\"cleared_at\":\"2026-08-20 11:41:23\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Denied\",\"file_name\":\"196-e3f09a19224a649aade12f67.pdf\",\"file_path\":\"faculty-clearance/35/196-e3f09a19224a649aade12f67.pdf\",\"remarks\":\"[Denied] \",\"cleared_at\":\"2026-08-20 11:41:25\"}]', '2026-08-20 11:41:03', '2026-08-20 11:47:31', '2026-08-20 03:47:31', NULL, NULL, NULL, NULL),
(12, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Regular', '2036-07-30', '2026-2027', '1st Semester', 'renewal', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-1d6aa69af77e6eae3bff2d08.pdf\",\"file_path\":\"faculty-clearance/35/1-1d6aa69af77e6eae3bff2d08.pdf\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-20 11:47:25\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"On Hold\",\"file_name\":\"194-8a9f0f8c01ac839fc9b57426.pdf\",\"file_path\":\"faculty-clearance/35/194-8a9f0f8c01ac839fc9b57426.pdf\",\"remarks\":\"[On Hold] \",\"cleared_at\":\"2026-08-20 11:41:21\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Cleared\",\"file_name\":\"195-c81a8802d4a9864148d79bed.pdf\",\"file_path\":\"faculty-clearance/35/195-c81a8802d4a9864148d79bed.pdf\",\"remarks\":null,\"cleared_at\":\"2026-08-20 11:41:23\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Denied\",\"file_name\":\"196-e3f09a19224a649aade12f67.pdf\",\"file_path\":\"faculty-clearance/35/196-e3f09a19224a649aade12f67.pdf\",\"remarks\":\"[Denied] \",\"cleared_at\":\"2026-08-20 11:41:25\"}]', '2026-08-20 11:41:03', '2026-08-20 11:47:52', '2026-08-20 03:47:52', NULL, NULL, NULL, NULL),
(13, 21, NULL, 3, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Probationary', '2037-07-29', '2026-2027', '1st Semester', 'renewal', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:49:46\"},{\"id\":76,\"name\":\"Updated Resume\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:49:46\"},{\"id\":77,\"name\":\"Personal Evaluation\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:49:46\"},{\"id\":78,\"name\":\"Summary Evaluation\",\"status\":\"Missing\",\"file_name\":null,\"file_path\":null,\"remarks\":null,\"cleared_at\":\"2026-08-20 11:49:46\"}]', '2026-08-20 11:41:03', '2026-08-20 11:49:46', '2026-08-20 03:49:46', NULL, NULL, NULL, NULL),
(14, 1, NULL, 52, 1, 35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', 'qwerty@gmail.com', '09318298352', 'BSIT', 'Faculty Professor', NULL, NULL, 'Probationary', '2032-06-09', '2026-2027', '1st Semester', 'renewal', 'Cleared', '[{\"id\":1,\"name\":\"Letter of Intent\",\"status\":\"Cleared\",\"file_name\":\"1-1b609d0a8206083863697011.docx\",\"file_path\":\"faculty-clearance/35/1-1b609d0a8206083863697011.docx\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-25 15:02:47\"},{\"id\":3,\"name\":\"Updated Resume\",\"status\":\"\",\"file_name\":\"2-4ebc88e39794d5ee75b3f6c6.docx\",\"file_path\":\"faculty-clearance/35/2-4ebc88e39794d5ee75b3f6c6.docx\",\"remarks\":\"[Denied] 3\",\"cleared_at\":\"2026-08-25 15:02:52\"},{\"id\":4,\"name\":\"Personal Evaluation\",\"status\":\"Cleared\",\"file_name\":\"3-8ee67aca09c6afaefd7bd184.docx\",\"file_path\":\"faculty-clearance/35/3-8ee67aca09c6afaefd7bd184.docx\",\"remarks\":\"Requirement approved.\",\"cleared_at\":\"2026-08-25 15:02:57\"},{\"id\":5,\"name\":\"Summary Evaluation\",\"status\":\"\",\"file_name\":\"4-2a4c009b0482ff473746579b.docx\",\"file_path\":\"faculty-clearance/35/4-2a4c009b0482ff473746579b.docx\",\"remarks\":\"[On Hold] 3\",\"cleared_at\":\"2026-08-25 15:03:03\"}]', '2026-08-25 15:01:01', '2026-08-25 15:03:25', '2026-08-25 07:03:25', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives_academic`
--

CREATE TABLE `faculty_clearance_archives_academic` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `requirement_name` varchar(100) NOT NULL,
  `requirement_status` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives_department`
--

CREATE TABLE `faculty_clearance_archives_department` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `requirement_name` varchar(100) NOT NULL,
  `requirement_status` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives_financial`
--

CREATE TABLE `faculty_clearance_archives_financial` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `requirement_name` varchar(100) NOT NULL,
  `requirement_status` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives_hr`
--

CREATE TABLE `faculty_clearance_archives_hr` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `requirement_name` varchar(100) NOT NULL,
  `requirement_status` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives_library`
--

CREATE TABLE `faculty_clearance_archives_library` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `requirement_name` varchar(100) NOT NULL,
  `requirement_status` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_clearance_archives_property`
--

CREATE TABLE `faculty_clearance_archives_property` (
  `archive_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `intent_type` varchar(50) DEFAULT 'renewal',
  `requirement_name` varchar(100) NOT NULL,
  `requirement_status` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_deadlines`
--

CREATE TABLE `faculty_deadlines` (
  `deadline_id` int(10) UNSIGNED NOT NULL,
  `campus_id` int(10) UNSIGNED DEFAULT NULL,
  `faculty_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_department_assignments`
--

CREATE TABLE `faculty_department_assignments` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_department_assignments`
--

INSERT INTO `faculty_department_assignments` (`assignment_id`, `faculty_id`, `department_id`, `created_at`) VALUES
(1, 51, 1, '2026-08-22 06:25:47');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_profiles`
--

CREATE TABLE `faculty_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `faculty_id` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('MALE','FEMALE') DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `specialization_assignment` varchar(255) DEFAULT NULL,
  `is_coordinator` tinyint(1) NOT NULL DEFAULT 0,
  `is_solo_parent` tinyint(1) NOT NULL DEFAULT 0,
  `coordinator_type` varchar(100) DEFAULT NULL,
  `tier` varchar(100) DEFAULT NULL,
  `hired_date` date DEFAULT NULL,
  `contractual_end` date DEFAULT NULL,
  `contractual_end_date` date DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `profile_status` varchar(50) DEFAULT NULL,
  `request_status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `academic_rank` varchar(100) DEFAULT NULL,
  `education_attainment` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `emergency_relationship` varchar(100) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_profiles`
--

INSERT INTO `faculty_profiles` (`id`, `faculty_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthdate`, `age`, `sex`, `phone`, `email`, `signature`, `designated_department`, `position`, `specialization_assignment`, `is_coordinator`, `is_solo_parent`, `coordinator_type`, `tier`, `hired_date`, `contractual_end`, `contractual_end_date`, `employment_status`, `profile_status`, `request_status`, `created_at`, `updated_at`, `academic_rank`, `education_attainment`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_relationship`, `user_id`) VALUES
(34, 'FAC-2026-0004', 'Jean', 'Claude', 'Espejo', '', '2000-05-17', 26, 'MALE', '09318298352', 'claudeespejo@gmail.com', 'uploads/signatures/sig_149_1790064541.png', 'BSIT', 'Department Head', NULL, 0, 0, NULL, NULL, '2023-01-30', '0000-00-00', NULL, 'regular', 'Active', 'approved', '2026-08-14 03:58:31', '2026-09-26 15:43:30', NULL, NULL, NULL, NULL, NULL, NULL, 149),
(101, 'FAC-2026-0069', 'Light', 'A', 'Yagami', '', '2000-02-01', 26, 'MALE', '09318298352', 'whitybrix@gmail.com', NULL, 'BSIT', 'Faculty Professor', NULL, 0, 0, NULL, NULL, '2026-02-12', '2027-06-11', NULL, 'probationary', 'Active', 'approved', '2026-09-10 18:12:42', '2026-09-19 11:11:07', NULL, NULL, NULL, NULL, NULL, NULL, 250),
(105, 'FAC-2026-0102', 'John Bert', 'M', 'Lonzaga', '', '2004-06-20', 22, 'MALE', '09318298352', 'brianbrix002@gmail.com', NULL, 'BSIT', 'Faculty Professor', NULL, 0, 0, NULL, NULL, '2026-06-25', NULL, NULL, 'regular', 'Pending Approval', 'pending', '2026-09-24 00:30:37', '2026-09-24 00:30:37', NULL, NULL, NULL, NULL, NULL, NULL, 254),
(106, 'FAC-2026-0106', 'Earl', 'M', 'Salvame', '', '2004-10-08', 21, 'MALE', '09318298352', 'bardpanget2025@gmail.com', NULL, 'BSIT', 'Faculty Secretary', NULL, 0, 0, NULL, NULL, '2026-02-26', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-24 00:34:28', '2026-09-26 07:01:44', NULL, NULL, NULL, NULL, NULL, NULL, 255),
(107, 'FAC-2026-0107', 'Alfred Joseph', 'A', 'Alcantara', '', '2002-05-22', 24, 'MALE', '09318298352', 'mingthecat002@gmail.com', NULL, 'BSIT', 'Attendance Monitoring Officer', NULL, 0, 0, NULL, NULL, '2026-03-12', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-24 00:45:01', '2026-09-24 00:51:19', NULL, NULL, NULL, NULL, NULL, NULL, 256),
(111, 'FAC-2026-0111', 'Sofia', 'A', 'Reyes', '', '2002-03-23', 24, 'FEMALE', '09318278392', 'idkwhoistonio@gmail.com', NULL, 'BSIT', 'Faculty Professor', NULL, 0, 0, NULL, 'Professor III', '2025-07-09', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-26 14:49:46', '2026-09-26 14:52:45', 'Professor', NULL, NULL, NULL, NULL, NULL, 261),
(112, 'FAC-2026-0112', 'Jorge', 'V', 'Lucero', '', '1990-03-23', 36, 'MALE', '09318278392', 'jcespejo002@gmail.com', NULL, 'BSIT', 'Dean', NULL, 0, 0, NULL, 'Professor VI', '2022-09-23', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-26 15:21:19', '2026-09-26 15:21:31', 'Professor', NULL, NULL, NULL, NULL, NULL, 262);

-- --------------------------------------------------------

--
-- Table structure for table `faculty_profile_department_assignments`
--

CREATE TABLE `faculty_profile_department_assignments` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `faculty_profile_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_profile_department_assignments`
--

INSERT INTO `faculty_profile_department_assignments` (`assignment_id`, `faculty_profile_id`, `department_id`, `created_at`) VALUES
(8, 112, 1, '2026-09-26 15:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_profile_details`
--

CREATE TABLE `faculty_profile_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `faculty_profile_id` int(10) UNSIGNED NOT NULL,
  `address` varchar(555) NOT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `highest_education` varchar(255) DEFAULT NULL,
  `specialization_assignment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_reports`
--

CREATE TABLE `generated_reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `report_name` varchar(200) NOT NULL,
  `report_type` enum('Daily','Attendance','Document','Clearance') NOT NULL,
  `file_format` enum('PDF','Excel','CSV') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `filters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_json`)),
  `generated_by_external_id` varchar(64) DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `generated_reports`
--

INSERT INTO `generated_reports` (`report_id`, `report_name`, `report_type`, `file_format`, `file_path`, `filters_json`, `generated_by_external_id`, `generated_at`, `created_at`) VALUES
(1, 'Daily Activity Log', 'Daily', 'PDF', NULL, NULL, NULL, '2026-09-02 23:52:46', '2026-09-02 15:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `leave_approval_history`
--

CREATE TABLE `leave_approval_history` (
  `history_id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `approved_by` int(10) UNSIGNED NOT NULL,
  `action` enum('Submitted','Approved','Rejected','Modified','Returned') NOT NULL,
  `comments` text DEFAULT NULL,
  `signature` longblob DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `balance_id` int(11) NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `sick_leave_total` int(11) NOT NULL DEFAULT 15,
  `sick_leave_used` int(11) NOT NULL DEFAULT 0,
  `vacation_leave_total` int(11) NOT NULL DEFAULT 0,
  `vacation_leave_used` int(11) NOT NULL DEFAULT 0,
  `maternity_total` int(11) NOT NULL DEFAULT 105,
  `maternity_used` int(11) NOT NULL DEFAULT 0,
  `paternity_total` int(11) NOT NULL DEFAULT 7,
  `solo_parent_total` int(11) NOT NULL DEFAULT 0,
  `solo_parent_used` int(11) NOT NULL DEFAULT 0,
  `paternity_used` int(11) NOT NULL DEFAULT 0,
  `emergency_total` int(11) NOT NULL DEFAULT 0,
  `emergency_used` int(11) NOT NULL DEFAULT 0,
  `magna_carta_total` int(11) NOT NULL DEFAULT 60,
  `magna_carta_used` int(11) NOT NULL DEFAULT 0,
  `vawc_total` int(11) NOT NULL DEFAULT 10,
  `vawc_used` int(11) NOT NULL DEFAULT 0,
  `sabbatical_total` int(11) NOT NULL DEFAULT 0,
  `sabbatical_used` int(11) NOT NULL DEFAULT 0,
  `admin_vacation_total` int(11) NOT NULL DEFAULT 0,
  `admin_vacation_used` int(11) NOT NULL DEFAULT 0,
  `admin_special_total` int(11) NOT NULL DEFAULT 3,
  `admin_special_used` int(11) NOT NULL DEFAULT 0,
  `study_leave_total` int(11) NOT NULL DEFAULT 0,
  `study_leave_used` int(11) NOT NULL DEFAULT 0,
  `pvp_used` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_balances`
--

INSERT INTO `leave_balances` (`balance_id`, `faculty_id`, `academic_year`, `sick_leave_total`, `sick_leave_used`, `vacation_leave_total`, `vacation_leave_used`, `maternity_total`, `maternity_used`, `paternity_total`, `solo_parent_total`, `solo_parent_used`, `paternity_used`, `emergency_total`, `emergency_used`, `magna_carta_total`, `magna_carta_used`, `vawc_total`, `vawc_used`, `sabbatical_total`, `sabbatical_used`, `admin_vacation_total`, `admin_vacation_used`, `admin_special_total`, `admin_special_used`, `study_leave_total`, `study_leave_used`, `pvp_used`, `updated_at`) VALUES
(1, 101, '2026-2027', 15, 0, 7, 0, 0, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-09-26 14:44:17'),
(2, 109, '2026-2027', 15, 0, 7, 0, 105, 0, 0, 0, 0, 0, 0, 0, 60, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-09-26 14:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `leave_entitlements`
--

CREATE TABLE `leave_entitlements` (
  `entitlement_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `vacation_leave_total` int(10) UNSIGNED NOT NULL DEFAULT 10,
  `vacation_leave_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sick_leave_total` int(10) UNSIGNED NOT NULL DEFAULT 5,
  `sick_leave_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `emergency_leave_total` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `emergency_leave_used` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `study_leave_total` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `study_leave_used` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_ref` varchar(20) NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `leave_type` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(10) UNSIGNED NOT NULL,
  `reason` text DEFAULT NULL,
  `documents_status` enum('Complete','Incomplete') NOT NULL DEFAULT 'Incomplete',
  `screening_status` enum('Pending','Screened','Returned') NOT NULL DEFAULT 'Pending',
  `screened_by_external_id` varchar(64) DEFAULT NULL,
  `screened_at` datetime DEFAULT NULL,
  `screening_signature` longtext DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `approver_id` int(11) DEFAULT NULL,
  `approver_at` datetime DEFAULT NULL,
  `approver_comment` text DEFAULT NULL,
  `documents` varchar(255) DEFAULT NULL,
  `notification` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `approved_by_external_id` varchar(64) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `faculty_id_backup` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(500) NOT NULL,
  `priority` enum('Low','Medium','High Priority') NOT NULL DEFAULT 'Low',
  `notification_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(10) UNSIGNED NOT NULL,
  `campus_id` int(10) UNSIGNED NOT NULL,
  `room_code` varchar(30) NOT NULL,
  `building` varchar(100) DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `campus_id`, `room_code`, `building`, `capacity`, `created_at`) VALUES
(4, 1, '321', NULL, NULL, '2026-08-22 05:36:29'),
(5, 1, '405', NULL, NULL, '2026-08-22 05:47:57'),
(6, 1, '319', NULL, NULL, '2026-08-22 05:56:36'),
(7, 1, '302', NULL, NULL, '2026-09-04 05:16:14');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `default_units` decimal(4,1) NOT NULL DEFAULT 3.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `department_id`, `code`, `title`, `default_units`, `created_at`) VALUES
(1, 1, 'DSA', 'DSA', 3.0, '2026-08-22 05:28:06'),
(2, 1, 'ITE 4', 'ITE 4', 3.0, '2026-08-22 05:47:57'),
(3, 1, 'dasdaw', 'dasdaw', 3.0, '2026-09-22 06:06:35');

-- --------------------------------------------------------

--
-- Table structure for table `teaching_load_history`
--

CREATE TABLE `teaching_load_history` (
  `history_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `subject_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_units` decimal(5,1) NOT NULL DEFAULT 0.0,
  `total_students` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('Current','Completed') NOT NULL DEFAULT 'Completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_load_requests`
--

CREATE TABLE `teaching_load_requests` (
  `load_request_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `total_units` decimal(5,1) NOT NULL DEFAULT 0.0,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by_external_id` varchar(64) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_load_request_items`
--

CREATE TABLE `teaching_load_request_items` (
  `load_request_item_id` int(10) UNSIGNED NOT NULL,
  `load_request_id` int(10) UNSIGNED NOT NULL,
  `class_schedule_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD PRIMARY KEY (`term_id`),
  ADD UNIQUE KEY `uq_terms_year_sem` (`academic_year`,`semester`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `uq_attendance_faculty_date` (`faculty_id`,`attendance_date`),
  ADD KEY `fk_attendance_campus` (`campus_id`);

--
-- Indexes for table `campuses`
--
ALTER TABLE `campuses`
  ADD PRIMARY KEY (`campus_id`),
  ADD UNIQUE KEY `uq_campuses_code` (`code`),
  ADD UNIQUE KEY `uq_campuses_name` (`name`);

--
-- Indexes for table `class_attendance_sessions`
--
ALTER TABLE `class_attendance_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `fk_sessions_class_schedule` (`class_schedule_id`),
  ADD KEY `fk_sessions_department` (`department_id`),
  ADD KEY `fk_sessions_campus` (`campus_id`),
  ADD KEY `fk_sessions_faculty` (`faculty_id`),
  ADD KEY `fk_sessions_room` (`room_id`),
  ADD KEY `fk_sessions_subject` (`subject_id`),
  ADD KEY `idx_sessions_date_dept` (`session_date`,`department_id`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`class_schedule_id`),
  ADD KEY `fk_class_schedules_term` (`term_id`),
  ADD KEY `fk_class_schedules_subject` (`subject_id`),
  ADD KEY `fk_class_schedules_room` (`room_id`),
  ADD KEY `idx_class_schedules_faculty_term` (`faculty_id`,`term_id`);

--
-- Indexes for table `clearance_approval_history`
--
ALTER TABLE `clearance_approval_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_clearance` (`clearance_id`),
  ADD KEY `idx_hist_office` (`office`),
  ADD KEY `idx_hist_created` (`created_at`);

--
-- Indexes for table `clearance_items`
--
ALTER TABLE `clearance_items`
  ADD PRIMARY KEY (`clearance_item_id`),
  ADD UNIQUE KEY `uq_clearance_items` (`clearance_id`,`clearance_office_id`),
  ADD KEY `fk_clearance_items_office` (`clearance_office_id`);

--
-- Indexes for table `clearance_offices`
--
ALTER TABLE `clearance_offices`
  ADD PRIMARY KEY (`clearance_office_id`),
  ADD UNIQUE KEY `uq_clearance_offices_name` (`name`);

--
-- Indexes for table `clearance_office_approvals`
--
ALTER TABLE `clearance_office_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_clearance_office` (`clearance_id`,`office`),
  ADD KEY `idx_approval_faculty` (`faculty_id`),
  ADD KEY `idx_approval_ref` (`approval_ref`);

--
-- Indexes for table `clearance_requests`
--
ALTER TABLE `clearance_requests`
  ADD PRIMARY KEY (`clearance_id`),
  ADD UNIQUE KEY `uq_clearance_faculty_term` (`faculty_id`,`term_id`),
  ADD UNIQUE KEY `uk_clearance_no` (`clearance_no`),
  ADD KEY `fk_clearance_term` (`term_id`);

--
-- Indexes for table `clearance_signatures`
--
ALTER TABLE `clearance_signatures`
  ADD PRIMARY KEY (`signature_id`),
  ADD UNIQUE KEY `uk_clearance_office_sig` (`clearance_id`,`office_key`),
  ADD KEY `idx_sig_faculty` (`faculty_id`),
  ADD KEY `idx_sig_clearance` (`clearance_id`),
  ADD KEY `idx_sig_signer` (`signer_user_id`),
  ADD KEY `idx_sig_ref` (`approval_ref`),
  ADD KEY `idx_sig_date` (`signed_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `uq_departments_code` (`code`),
  ADD KEY `fk_departments_campus` (`campus_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `uq_document_no` (`document_no`),
  ADD KEY `fk_documents_faculty` (`faculty_id`),
  ADD KEY `idx_documents_expiry` (`expiry_date`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `fk_evaluations_peer` (`evaluator_id`),
  ADD KEY `fk_evaluations_term` (`term_id`),
  ADD KEY `idx_evaluations_faculty_term_source` (`faculty_id`,`term_id`,`source_type`);

--
-- Indexes for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  ADD PRIMARY KEY (`evaluation_category_id`),
  ADD KEY `fk_eval_categories_evaluation` (`evaluation_id`);

--
-- Indexes for table `evaluation_feedback`
--
ALTER TABLE `evaluation_feedback`
  ADD PRIMARY KEY (`evaluation_feedback_id`),
  ADD KEY `fk_eval_feedback_evaluation` (`evaluation_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `uq_faculty_no` (`faculty_no`),
  ADD UNIQUE KEY `uq_faculty_external_user` (`external_user_id`),
  ADD KEY `fk_faculty_department` (`department_id`),
  ADD KEY `fk_faculty_campus` (`campus_id`);

--
-- Indexes for table `faculty_class_assignments`
--
ALTER TABLE `faculty_class_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_assignment` (`class_id`);

--
-- Indexes for table `faculty_clearance_archives`
--
ALTER TABLE `faculty_clearance_archives`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_fca_faculty` (`faculty_id`),
  ADD KEY `idx_fca_term` (`term_id`),
  ADD KEY `idx_fca_clearance` (`clearance_id`);

--
-- Indexes for table `faculty_clearance_archives_academic`
--
ALTER TABLE `faculty_clearance_archives_academic`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_archive_clearance` (`clearance_id`),
  ADD KEY `idx_archive_faculty` (`faculty_id`),
  ADD KEY `idx_archive_term` (`term_id`),
  ADD KEY `idx_archive_item` (`clearance_item_id`),
  ADD KEY `idx_archive_status` (`requirement_status`);

--
-- Indexes for table `faculty_clearance_archives_department`
--
ALTER TABLE `faculty_clearance_archives_department`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_archive_clearance` (`clearance_id`),
  ADD KEY `idx_archive_faculty` (`faculty_id`),
  ADD KEY `idx_archive_term` (`term_id`),
  ADD KEY `idx_archive_item` (`clearance_item_id`),
  ADD KEY `idx_archive_status` (`requirement_status`);

--
-- Indexes for table `faculty_clearance_archives_financial`
--
ALTER TABLE `faculty_clearance_archives_financial`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_archive_clearance` (`clearance_id`),
  ADD KEY `idx_archive_faculty` (`faculty_id`),
  ADD KEY `idx_archive_term` (`term_id`),
  ADD KEY `idx_archive_item` (`clearance_item_id`),
  ADD KEY `idx_archive_status` (`requirement_status`);

--
-- Indexes for table `faculty_clearance_archives_hr`
--
ALTER TABLE `faculty_clearance_archives_hr`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_archive_clearance` (`clearance_id`),
  ADD KEY `idx_archive_faculty` (`faculty_id`),
  ADD KEY `idx_archive_term` (`term_id`),
  ADD KEY `idx_archive_item` (`clearance_item_id`),
  ADD KEY `idx_archive_status` (`requirement_status`);

--
-- Indexes for table `faculty_clearance_archives_library`
--
ALTER TABLE `faculty_clearance_archives_library`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_archive_clearance` (`clearance_id`),
  ADD KEY `idx_archive_faculty` (`faculty_id`),
  ADD KEY `idx_archive_term` (`term_id`),
  ADD KEY `idx_archive_item` (`clearance_item_id`),
  ADD KEY `idx_archive_status` (`requirement_status`);

--
-- Indexes for table `faculty_clearance_archives_property`
--
ALTER TABLE `faculty_clearance_archives_property`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `idx_archive_clearance` (`clearance_id`),
  ADD KEY `idx_archive_faculty` (`faculty_id`),
  ADD KEY `idx_archive_term` (`term_id`),
  ADD KEY `idx_archive_item` (`clearance_item_id`),
  ADD KEY `idx_archive_status` (`requirement_status`);

--
-- Indexes for table `faculty_deadlines`
--
ALTER TABLE `faculty_deadlines`
  ADD PRIMARY KEY (`deadline_id`),
  ADD KEY `fk_deadlines_campus` (`campus_id`),
  ADD KEY `fk_deadlines_faculty` (`faculty_id`);

--
-- Indexes for table `faculty_department_assignments`
--
ALTER TABLE `faculty_department_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_faculty_department` (`faculty_id`,`department_id`),
  ADD KEY `fk_fda_department` (`department_id`);

--
-- Indexes for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faculty_profile_department_assignments`
--
ALTER TABLE `faculty_profile_department_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_profile_department` (`faculty_profile_id`,`department_id`),
  ADD KEY `fk_fpda_department` (`department_id`);

--
-- Indexes for table `faculty_profile_details`
--
ALTER TABLE `faculty_profile_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_profile_details_profile` (`faculty_profile_id`);

--
-- Indexes for table `generated_reports`
--
ALTER TABLE `generated_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_reports_type_date` (`report_type`,`generated_at`);

--
-- Indexes for table `leave_approval_history`
--
ALTER TABLE `leave_approval_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD UNIQUE KEY `uq_faculty_year` (`faculty_id`,`academic_year`);

--
-- Indexes for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD PRIMARY KEY (`entitlement_id`),
  ADD UNIQUE KEY `uq_faculty_year_entitlement` (`faculty_id`,`academic_year`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_leave_no` (`request_ref`),
  ADD KEY `fk_leave_faculty` (`faculty_id`),
  ADD KEY `idx_leave_status` (`screening_status`,`approval_status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_faculty_read` (`faculty_id`,`is_read`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `uq_rooms_campus_code` (`campus_id`,`room_code`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `uq_subjects_code` (`code`),
  ADD KEY `fk_subjects_department` (`department_id`);

--
-- Indexes for table `teaching_load_history`
--
ALTER TABLE `teaching_load_history`
  ADD PRIMARY KEY (`history_id`),
  ADD UNIQUE KEY `uq_load_history` (`faculty_id`,`term_id`),
  ADD KEY `fk_load_history_term` (`term_id`);

--
-- Indexes for table `teaching_load_requests`
--
ALTER TABLE `teaching_load_requests`
  ADD PRIMARY KEY (`load_request_id`),
  ADD KEY `fk_load_requests_faculty` (`faculty_id`),
  ADD KEY `fk_load_requests_term` (`term_id`),
  ADD KEY `idx_load_requests_status` (`status`);

--
-- Indexes for table `teaching_load_request_items`
--
ALTER TABLE `teaching_load_request_items`
  ADD PRIMARY KEY (`load_request_item_id`),
  ADD UNIQUE KEY `uq_load_items` (`load_request_id`,`class_schedule_id`),
  ADD KEY `fk_load_items_schedule` (`class_schedule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_terms`
--
ALTER TABLE `academic_terms`
  MODIFY `term_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `attendance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `campus_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_attendance_sessions`
--
ALTER TABLE `class_attendance_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `class_schedule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_approval_history`
--
ALTER TABLE `clearance_approval_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_items`
--
ALTER TABLE `clearance_items`
  MODIFY `clearance_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7683;

--
-- AUTO_INCREMENT for table `clearance_offices`
--
ALTER TABLE `clearance_offices`
  MODIFY `clearance_office_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21091;

--
-- AUTO_INCREMENT for table `clearance_office_approvals`
--
ALTER TABLE `clearance_office_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_requests`
--
ALTER TABLE `clearance_requests`
  MODIFY `clearance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clearance_signatures`
--
ALTER TABLE `clearance_signatures`
  MODIFY `signature_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `evaluation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  MODIFY `evaluation_category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_feedback`
--
ALTER TABLE `evaluation_feedback`
  MODIFY `evaluation_feedback_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `faculty_class_assignments`
--
ALTER TABLE `faculty_class_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives`
--
ALTER TABLE `faculty_clearance_archives`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives_academic`
--
ALTER TABLE `faculty_clearance_archives_academic`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives_department`
--
ALTER TABLE `faculty_clearance_archives_department`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives_financial`
--
ALTER TABLE `faculty_clearance_archives_financial`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives_hr`
--
ALTER TABLE `faculty_clearance_archives_hr`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives_library`
--
ALTER TABLE `faculty_clearance_archives_library`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_clearance_archives_property`
--
ALTER TABLE `faculty_clearance_archives_property`
  MODIFY `archive_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_deadlines`
--
ALTER TABLE `faculty_deadlines`
  MODIFY `deadline_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_department_assignments`
--
ALTER TABLE `faculty_department_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `faculty_profile_department_assignments`
--
ALTER TABLE `faculty_profile_department_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `faculty_profile_details`
--
ALTER TABLE `faculty_profile_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `generated_reports`
--
ALTER TABLE `generated_reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `leave_approval_history`
--
ALTER TABLE `leave_approval_history`
  MODIFY `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  MODIFY `entitlement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teaching_load_history`
--
ALTER TABLE `teaching_load_history`
  MODIFY `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teaching_load_requests`
--
ALTER TABLE `teaching_load_requests`
  MODIFY `load_request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teaching_load_request_items`
--
ALTER TABLE `teaching_load_request_items`
  MODIFY `load_request_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `fk_attendance_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_attendance_sessions`
--
ALTER TABLE `class_attendance_sessions`
  ADD CONSTRAINT `fk_sessions_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_class_schedule` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`class_schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sessions_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sessions_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `fk_class_schedules_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_schedules_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_class_schedules_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_schedules_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `clearance_items`
--
ALTER TABLE `clearance_items`
  ADD CONSTRAINT `fk_clearance_items_office` FOREIGN KEY (`clearance_office_id`) REFERENCES `clearance_offices` (`clearance_office_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clearance_items_request` FOREIGN KEY (`clearance_id`) REFERENCES `clearance_requests` (`clearance_id`) ON DELETE CASCADE;

--
-- Constraints for table `clearance_requests`
--
ALTER TABLE `clearance_requests`
  ADD CONSTRAINT `fk_clearance_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clearance_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `fk_evaluations_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evaluations_peer` FOREIGN KEY (`evaluator_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evaluations_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  ADD CONSTRAINT `fk_eval_categories_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`evaluation_id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_feedback`
--
ALTER TABLE `evaluation_feedback`
  ADD CONSTRAINT `fk_eval_feedback_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`evaluation_id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `fk_faculty_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_faculty_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `faculty_deadlines`
--
ALTER TABLE `faculty_deadlines`
  ADD CONSTRAINT `fk_deadlines_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_deadlines_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_department_assignments`
--
ALTER TABLE `faculty_department_assignments`
  ADD CONSTRAINT `fk_fda_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fda_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_profile_department_assignments`
--
ALTER TABLE `faculty_profile_department_assignments`
  ADD CONSTRAINT `fk_fpda_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fpda_profile` FOREIGN KEY (`faculty_profile_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_profile_details`
--
ALTER TABLE `faculty_profile_details`
  ADD CONSTRAINT `fk_profile_details_profile` FOREIGN KEY (`faculty_profile_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_approval_history`
--
ALTER TABLE `leave_approval_history`
  ADD CONSTRAINT `fk_leave_approval_request` FOREIGN KEY (`request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD CONSTRAINT `fk_entitlements_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`campus_id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `teaching_load_history`
--
ALTER TABLE `teaching_load_history`
  ADD CONSTRAINT `fk_load_history_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_load_history_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `teaching_load_requests`
--
ALTER TABLE `teaching_load_requests`
  ADD CONSTRAINT `fk_load_requests_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_load_requests_term` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`term_id`) ON DELETE CASCADE;

--
-- Constraints for table `teaching_load_request_items`
--
ALTER TABLE `teaching_load_request_items`
  ADD CONSTRAINT `fk_load_items_request` FOREIGN KEY (`load_request_id`) REFERENCES `teaching_load_requests` (`load_request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_load_items_schedule` FOREIGN KEY (`class_schedule_id`) REFERENCES `class_schedules` (`class_schedule_id`) ON DELETE CASCADE;



-- Seed official user accounts
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('superadmin', 'superadmin@bestlink.edu.ph', '$2y$10$1VVwRW85XyIuMatHIr9aUuaX1ngHlN1u05aZSecUCqtX2YDF8HBcG', 'Super Administrator', 'superadmin', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('admin', 'admin@bestlink.edu.ph', '$2y$10$VF3jn4Iv0woHIoxc/I39be/yqfPcmuqnTgTumvCjhvoN85PScytoi', 'Administrator', 'admin', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('faculty', 'faculty@bestlink.edu.ph', '$2y$10$Q93pxXUTdQeaTg7Yw0ywMeXFx4buyosPGNbq7.y8mP7XDynXaxiKK', 'Demo Faculty', 'faculty', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('dean', 'dean@bestlink.edu.ph', '$2y$10$iT4OgDmsCCjbodx4wt.wnO1CTJ8NQFz1znNkPL/UUnhnkHt8PERMq', 'Academic Dean', 'dean', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('registrar', 'registrar@bestlink.edu.ph', '$2y$10$E2KYIm0nOJHIH1cBmFiFWunVEKOUDqWrdNNlzvYxcQknF4PZFUjaS', 'Registrar Officer', 'registrar', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('hr', 'hr@gmail.com', '$2y$10$zip09pn5vV6BSsS9I.po0uuQNWJYAYk2Js6fcMOfVfJca0SaF3O62', 'HR Clearance Officer', 'hr_clearance', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('registrarclearance', 'registrarclearance121@gmail.com', '$2y$10$TDN5qCUfv1cDWkeUzJTGm.kaG.HJScomcgk06hERuaWGWh.lld5m.', 'Registrar Clearance Officer', 'registrar_clearance', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('finance', 'finance@gmail.com', '$2y$10$XvxrWye.05W6i1oxh3OxXudME3Uqe3uiHdwwOqCApo6KGpN7drSUe', 'Finance Officer', 'finance_office', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('library', 'library@bestlink.edu.ph', '$2y$10$9TBLQTgo9r9b2Lnl7FjNYeQ.Fa0A/pCixwRFvj0plW4HNocQhIXGu', 'Library Clearance Officer', 'library_clearance', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role_key`, `status`) VALUES ('property', 'property@bestlink.edu.ph', '$2y$10$CZv63GZCqGCLdo6ZGtRR2up0Q/7X29umXt9xRJK7wVAvMmg5XxMjG', 'Property Custodian Officer', 'property_custodian_office', 'active') ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `status` = 'active';

SET FOREIGN_KEY_CHECKS = 1;