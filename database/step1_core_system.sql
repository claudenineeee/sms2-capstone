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


SET FOREIGN_KEY_CHECKS = 1;
