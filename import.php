<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_DATABASE');

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$files = [
    __DIR__ . '/sms2_db.sql', 
    __DIR__ . '/faculty_db.sql'
];

// Paste your entire sms2_db and faculty_db SQL statements inside this string:
$sql =  <<<'SQL'
SET SQL_MODE = NO_AUTO_VALUE_ON_ZERO;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sms2_db`
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
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(3, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 08:59:38'),
(4, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated Super Admin profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 09:02:04'),
(5, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 09:02:07'),
(6, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:04:48'),
(7, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:02'),
(8, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:18'),
(9, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated Super Admin profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:29'),
(10, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:33'),
(11, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:47:36'),
(12, 1, 'Super Admin', 'admin', 'view', 'enrollment', 'Opened Enrollment Management Module Security', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:51:40'),
(13, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:53:42'),
(14, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:05:23'),
(15, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:05:45'),
(16, 1, 'Super Admin', 'admin', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:05:59'),
(17, 1, NULL, NULL, 'password_reset', 'System', 'Password reset via token', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:06:28'),
(18, 9, 'Student User', 'student', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:06:33'),
(19, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:06:34'),
(20, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:08:29'),
(21, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:08:46'),
(22, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:09:03'),
(23, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:09:41'),
(24, 1, 'Super Admin', 'admin', 'password_reset_request', 'user-management', 'OTP emailed for Super Admin password change', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:10:21'),
(25, 1, 'Super Admin', 'admin', 'password_change', 'user-management', 'Super Admin password reset via account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:10:29'),
(26, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:02'),
(27, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:14'),
(28, 9, 'Student User', 'student', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:19'),
(29, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:19'),
(30, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:12:08'),
(31, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:12:17'),
(32, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:13:35'),
(33, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:13:48'),
(34, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:15:37'),
(35, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:16:14'),
(36, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:16'),
(37, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:20'),
(38, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:32'),
(39, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:41'),
(40, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:44'),
(41, 9, 'Student User', 'student', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:18:27'),
(42, 9, 'Student User', 'student', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:18:36'),
(43, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:21:02'),
(44, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:22:47'),
(45, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:25:47'),
(46, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:28:10'),
(47, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Archived user #10 (status=inactive)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:29:05'),
(48, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:29:42'),
(49, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:29:51'),
(50, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:30:40'),
(51, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:30:43'),
(52, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:30:55'),
(53, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:31:36'),
(54, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:31:50'),
(55, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:31:52'),
(56, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:32:15'),
(57, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:07'),
(58, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:17'),
(59, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:26'),
(60, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:52'),
(61, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:46:20'),
(62, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:55:08'),
(63, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:55:34'),
(64, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:02:03'),
(65, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:02:28'),
(66, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:06:11'),
(67, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:06:38'),
(68, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:17:40'),
(69, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:18:23'),
(70, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:18:37'),
(71, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:32:52'),
(72, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:32:59'),
(73, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user finance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:35:47'),
(74, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:36:52'),
(75, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:37:11'),
(76, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:43:31'),
(77, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:44:03'),
(78, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:07:06'),
(79, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:13:15'),
(80, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:52:18'),
(81, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:52:21'),
(82, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:53:03'),
(83, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:54:55'),
(84, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 16:36:05'),
(85, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 16:51:54'),
(86, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 17:34:40'),
(87, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 17:36:17'),
(88, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00002 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 18:22:42'),
(89, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00003 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 18:36:18'),
(90, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 19:10:53'),
(91, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00002 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 19:11:52'),
(92, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:03:13'),
(93, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:03:50'),
(94, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission registrar:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:12:08'),
(95, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission registrar:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:12:08'),
(96, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user finance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:20:39'),
(97, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:20:47'),
(98, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:20:55'),
(99, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:21:04'),
(100, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:21:27'),
(101, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:22:34'),
(102, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:25:41'),
(103, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:26:12'),
(104, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:26:19'),
(105, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:43:55'),
(106, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:44:40'),
(107, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:44:46'),
(108, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:45:15'),
(109, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 12:33:08'),
(110, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:08:00'),
(111, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00003 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:15:45'),
(112, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:39:34'),
(113, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:46:38'),
(114, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:46:45'),
(115, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:47:17'),
(116, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:47:43'),
(117, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:51:23'),
(118, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:51:31'),
(119, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:55:57'),
(120, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:56:06'),
(121, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:02:05'),
(122, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:02:19'),
(123, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:06:50'),
(124, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:06:57'),
(125, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:07:01'),
(126, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:07:10'),
(127, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:07:40'),
(128, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:29'),
(129, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:36'),
(130, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user hr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:53'),
(131, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:55'),
(132, NULL, 'HR', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:03'),
(133, NULL, 'HR', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:15'),
(134, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:24'),
(135, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:33'),
(136, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:41'),
(137, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:12:33'),
(138, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:22:41'),
(139, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:22:49'),
(140, 1, 'Super Admin', 'admin', 'update', 'System', 'Added passkey: This device', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:23:01'),
(141, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:23:06'),
(142, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:23:21'),
(143, 1, 'Super Admin', 'admin', 'update', 'System', 'Enabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:24:39'),
(144, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:24:41'),
(145, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:24:56'),
(146, 1, 'Super Admin', 'admin', 'update', 'System', 'Disabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:25:46'),
(147, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:27:34'),
(148, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:27:42'),
(149, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:27:50'),
(150, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:28:00'),
(151, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:28:02'),
(152, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:28:10'),
(153, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:32:37'),
(154, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:33:33'),
(155, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:33:55'),
(156, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:37:05'),
(157, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:37:12'),
(158, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:04:56'),
(159, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:18:44'),
(160, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:19:12'),
(161, 3, 'CRAD Officer', 'crad_officer', 'update', 'System', 'Enabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:21:14'),
(162, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:21:16'),
(163, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:22:03'),
(164, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:22:09'),
(165, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:22:17'),
(166, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:23:34'),
(167, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:23:46'),
(168, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:23:57'),
(169, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00004 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:27:28'),
(170, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:27:30'),
(171, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:27:51'),
(172, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:29:05'),
(173, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:29:25'),
(174, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 21:37:37'),
(175, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 21:37:40'),
(176, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 21:59:44'),
(177, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 22:01:44'),
(178, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 22:01:51'),
(179, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:07:11'),
(180, 1, 'Super Admin', 'admin', 'view', 'faculty', 'Opened Faculty Management Module Security', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:10:57'),
(181, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user hr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:11:42'),
(182, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:11:57'),
(183, NULL, 'HR', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:12:05'),
(184, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 02:06:41'),
(185, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:26:55'),
(186, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user dean', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:53'),
(187, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:58'),
(188, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:05'),
(189, NULL, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:22'),
(190, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:04'),
(191, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:19'),
(192, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:53:52'),
(193, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:54:01'),
(194, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:54:55'),
(195, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:55:09'),
(196, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:58:50'),
(197, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:59:01'),
(198, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:37:46'),
(199, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:38:01'),
(200, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:20:38'),
(201, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:20:48'),
(202, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:21:58'),
(203, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:22:07'),
(204, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:31:56'),
(205, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:38:32'),
(206, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:38:53'),
(207, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:39:46'),
(208, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:46:11'),
(209, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:46:25'),
(210, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:50:27'),
(211, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:50:38'),
(212, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:50:49'),
(213, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:51:37'),
(214, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:52:46'),
(215, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:53:02'),
(216, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:53:07'),
(217, NULL, 'Dr. Jose B. Tan', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:53:19'),
(218, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:17'),
(219, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:37'),
(220, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:49');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(221, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:59'),
(222, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:31:48'),
(223, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:31:58'),
(224, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:32:19'),
(225, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:32:32'),
(226, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:48:21'),
(227, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:48:29'),
(228, NULL, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:48:41'),
(229, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:59:38'),
(230, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:59:52'),
(231, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:17'),
(232, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:21'),
(233, NULL, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:31'),
(234, NULL, 'Dr. Roberto M. Santos', 'adviser', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:48'),
(235, NULL, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:01:15'),
(236, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:01:29'),
(237, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:33:22'),
(238, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:33:35'),
(239, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user departmenthead', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:49:08'),
(240, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user departmenthead', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:52:53'),
(241, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user secretary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:53:27'),
(242, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user faculty', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:54:02'),
(243, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:58:39'),
(244, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:58:48'),
(245, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:15'),
(246, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:25'),
(247, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:30'),
(248, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:40'),
(249, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:54:31'),
(250, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:57:16'),
(251, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:57:31'),
(252, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:58:16'),
(253, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:58:30'),
(254, 44, 'Department Head', 'department_head', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 22:03:12'),
(255, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 23:00:42'),
(256, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 23:06:40'),
(257, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 23:12:58'),
(258, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 23:13:10'),
(259, 45, 'Secretary', 'secretary', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 23:46:55'),
(260, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:15:03'),
(261, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:15:16'),
(262, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:16:57'),
(263, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:17:10'),
(264, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:17:46'),
(265, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:18:04'),
(266, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:27:08'),
(267, 46, 'Faculty', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 00:27:19'),
(268, 46, 'Faculty', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:10:52'),
(269, 46, 'Faculty', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:11:02'),
(270, 46, 'Faculty', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:23:21'),
(271, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:25:35'),
(272, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:47:44'),
(273, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:47:53'),
(274, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:06'),
(275, 46, 'Faculty', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:21'),
(276, 46, 'Faculty', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:27'),
(277, NULL, 'Dr. Jose B. Tan', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:35'),
(278, NULL, 'Dr. Jose B. Tan', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:49:03'),
(279, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:49:11'),
(280, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:49:16'),
(281, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:49:24'),
(282, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:50:56'),
(283, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:51:12'),
(284, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:51:38'),
(285, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jtan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:51:57'),
(286, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission panel:faculty = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:52:17'),
(287, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission panel:faculty = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:52:18'),
(288, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user faculty', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:53:09'),
(289, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:53:13'),
(290, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:53:28'),
(291, 46, 'Jean Claude', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:53:37'),
(292, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:01:13'),
(293, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:04:27'),
(294, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:04:38'),
(295, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:06:24'),
(296, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:06:32'),
(297, NULL, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:07:55'),
(298, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:07:59'),
(299, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:08:13'),
(300, 44, 'Department Head', 'department_head', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:30:44'),
(301, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:50:59'),
(302, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:10'),
(303, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:20'),
(304, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:26'),
(305, 46, 'Jean Claude', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:53:56'),
(306, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:58:48'),
(307, NULL, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:20:18'),
(308, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:20:42'),
(309, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:03'),
(310, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:14'),
(311, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:33'),
(312, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:45'),
(313, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:07:41'),
(314, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:07:52'),
(315, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:11:55'),
(316, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:12:05'),
(317, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:12:54'),
(318, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:13:03'),
(319, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:58:12'),
(320, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:58:22'),
(321, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:20:38'),
(322, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:37:26'),
(323, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:37:41'),
(324, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:59:07'),
(325, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:59:40'),
(326, NULL, 'Dean', 'hr', 'create', 'faculty', 'Registered faculty account: Jean Claude Espejo (department_head)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:18:13'),
(327, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:29:21'),
(328, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:29:34'),
(329, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:34:40'),
(330, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:34:52'),
(331, NULL, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:58:28'),
(332, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:40:37'),
(333, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:40:56'),
(334, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:57:18'),
(335, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:57:40'),
(336, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:23:49'),
(337, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:24:02'),
(338, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:29:18'),
(339, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:29:38'),
(340, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:23:04'),
(341, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:36:08'),
(342, NULL, 'qwerty qwerts', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:37:26'),
(343, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:37:50'),
(344, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:38:18'),
(345, NULL, 'Jean Espejo', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:39:08'),
(346, NULL, 'qwerty qwerts', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:39:26'),
(347, NULL, 'jr Espejo', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:39:55'),
(348, NULL, 'qwerty qwerts', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:40:22'),
(349, NULL, 'qwerty qwerts', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:40:22'),
(350, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:42:23'),
(351, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:47:00'),
(352, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:32:58'),
(353, NULL, 'te test', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:33:08'),
(354, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:33:20'),
(355, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:42:37'),
(356, NULL, 'Brix brix', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:42:48'),
(357, NULL, 'Brix brix', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:44:29'),
(358, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:44:38'),
(359, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:47:02'),
(360, NULL, 'Jean Espejo', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:47:10'),
(361, NULL, 'Jean Espejo', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:50:26'),
(362, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:50:47'),
(363, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 19:44:30'),
(364, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 20:18:05'),
(365, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:15:06'),
(366, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:17:53'),
(367, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:03'),
(368, NULL, 'Brix brix', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:30'),
(369, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:43'),
(370, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:58'),
(371, NULL, 'Brix brix', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:19:07'),
(372, NULL, 'Brix brix', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:19:57'),
(373, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:33:23'),
(374, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:00:32'),
(375, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:00:45'),
(376, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:01:08'),
(377, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:01:18'),
(378, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:04:01'),
(379, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:04:17'),
(380, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:08:04'),
(381, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:08:22'),
(382, 46, 'Jean Claude', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:44:16'),
(383, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:44:30'),
(384, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:46:15'),
(385, NULL, 'diosdado aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:46:31'),
(386, NULL, 'diosdado aban', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:59:08'),
(387, NULL, 'diosdado aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:59:15'),
(388, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:59:23'),
(389, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:31:06'),
(390, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:51:58'),
(391, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:52:10'),
(392, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:52:51'),
(393, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:53:00'),
(394, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:54:02'),
(395, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:54:11'),
(396, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:58:48'),
(397, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:59:02'),
(398, 149, 'Jean Espejo', 'department_head', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:59:29'),
(399, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:00:17'),
(400, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:00:38'),
(401, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:23:23'),
(402, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:23:41'),
(403, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:53:28'),
(404, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:54:23'),
(405, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:54:59'),
(406, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:55:15'),
(407, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:00:25'),
(408, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:00:36'),
(409, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:00:55'),
(410, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:06:20'),
(411, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:09:59'),
(412, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:10:11'),
(413, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:10:37'),
(414, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:10:45'),
(415, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:43:56'),
(416, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:44:08'),
(417, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:47:58'),
(418, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:09'),
(419, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:46'),
(420, NULL, 'Dean', 'hr', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:54'),
(421, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:59'),
(422, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:51:01'),
(423, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:51:11'),
(424, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:53:18'),
(425, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:53:31'),
(426, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:54:17'),
(427, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:54:38'),
(428, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:00:57'),
(429, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:01:05'),
(430, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:28'),
(431, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:40'),
(432, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:12:43'),
(433, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:35:37'),
(434, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:36:31'),
(435, 151, 'Faculty Admin', 'faculty_admin', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:36:50'),
(436, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:41:22'),
(437, 152, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:41:37'),
(438, 152, 'Monitoring Officer', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:41:50'),
(439, 152, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:48:16'),
(440, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:49:08'),
(441, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:49:40'),
(442, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:49:52'),
(443, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:17:33'),
(444, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:17:45');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(445, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:18:02'),
(446, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:18:08'),
(447, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:20:55'),
(448, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:21:06'),
(449, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:35:46'),
(450, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:35:57'),
(451, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:20:33'),
(452, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:22:21'),
(453, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:22:52'),
(454, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:27:49'),
(455, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:27:59'),
(456, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:16'),
(457, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:30'),
(458, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:57:36'),
(459, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:57:46'),
(460, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:58:00'),
(461, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:58:15'),
(462, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:59:02'),
(463, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:59:16'),
(464, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:49:29'),
(465, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:51:07'),
(466, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:51:47'),
(467, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:51:57'),
(468, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:52:06'),
(469, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:53:05'),
(470, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:53:41'),
(471, NULL, 'Jean Espejo', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:54:14'),
(472, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:12:43'),
(473, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:13:51'),
(474, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:28:23'),
(475, NULL, 'Dean', 'hr', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:28:30'),
(476, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:28:38'),
(477, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:07'),
(478, NULL, 'Name Testing', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:23'),
(479, NULL, 'Name Testing', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:50'),
(480, NULL, 'Name Testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:59:00'),
(481, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:59:12'),
(482, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:00:11'),
(483, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:00:23'),
(484, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:02:26'),
(485, NULL, 'mingdsada Ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:02:39'),
(486, NULL, 'mingdsada Ming', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:02:53'),
(487, NULL, 'mingdsada Ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:10:43'),
(488, NULL, 'Name Testing', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:16:25'),
(489, NULL, 'Name Testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:21:18'),
(490, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:21:38'),
(491, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:22:03'),
(492, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:22:09'),
(493, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:17'),
(494, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:29'),
(495, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:35'),
(496, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:43'),
(497, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:34:45'),
(498, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:14'),
(499, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:34'),
(500, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:41'),
(501, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:54'),
(502, NULL, 'Name Testing', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:59'),
(503, NULL, 'Name Testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:36:05'),
(504, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:01:56'),
(505, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:03:27'),
(506, NULL, 'ming ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:03:39'),
(507, NULL, 'ming ming', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:03:56'),
(508, NULL, 'ming ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:16:56'),
(509, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:17:06'),
(510, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:22:10'),
(511, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:22:20'),
(512, 158, 'dwadsadwa Qwerty', 'secretary', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:22:48'),
(513, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:24:17'),
(514, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:24:45'),
(515, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:30:04'),
(516, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:30:14'),
(517, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:33:57'),
(518, NULL, 'ming ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:34:11'),
(519, NULL, 'ming ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:37:44'),
(520, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:38:00'),
(521, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:44:38'),
(522, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:44:55'),
(523, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:45:05'),
(524, 152, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:45:17'),
(525, 152, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:32:54'),
(526, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:33:50'),
(527, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:41:31'),
(528, 159, 'Claude Claude', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:41:54'),
(529, 159, 'Claude Claude', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:42:24'),
(530, 159, 'Claude Claude', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:43:19'),
(531, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 01:43:31'),
(532, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 02:58:25'),
(533, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 02:58:38'),
(534, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 03:04:48'),
(535, 159, 'Claude Claude', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 03:05:08'),
(536, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 21:22:42'),
(537, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:08:06'),
(538, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:08:39'),
(539, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:41:57'),
(540, NULL, 'clara Mendoza', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:42:13'),
(541, NULL, 'clara Mendoza', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 22:42:28'),
(542, NULL, 'clara Mendoza', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 23:55:54'),
(543, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 23:56:21'),
(544, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 23:56:33'),
(545, NULL, 'clara Mendoza', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 23:56:49'),
(546, NULL, 'clara Mendoza', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 23:57:10'),
(547, NULL, 'clara Mendoza', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 23:57:30'),
(548, NULL, 'clara Mendoza', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:00:29'),
(549, NULL, 'clara Mendoza', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:00:44'),
(550, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:01:15'),
(551, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:01:41'),
(552, NULL, 'clara Mendoza', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:01:54'),
(553, NULL, 'clara Mendoza', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:02:41'),
(554, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:16:00'),
(555, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:25:26'),
(556, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:28:08'),
(557, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:28:36'),
(558, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:28:41'),
(559, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 01:36:05'),
(560, 159, 'Claude Claude', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 01:36:24'),
(561, 159, 'Claude Claude', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:21:21'),
(562, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:21:33'),
(563, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:28:57'),
(564, NULL, 'Mark Reyes', 'monitoring_officer', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:08'),
(565, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:25'),
(566, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:37'),
(567, NULL, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:53'),
(568, NULL, 'Mark Reyes', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:30:17'),
(569, NULL, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:05:40'),
(570, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:06:20'),
(571, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:10:42'),
(572, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:10:59'),
(573, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:11:11'),
(574, NULL, 'Maria Santos', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:11:29'),
(575, NULL, 'Maria Santos', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:11:41'),
(576, NULL, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 17:31:10'),
(577, NULL, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:16:30'),
(578, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:16:42'),
(579, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:17:26'),
(580, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:17:35'),
(581, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:17:54'),
(582, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:18:09'),
(583, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:20:04'),
(584, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:20:19'),
(585, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:20:34'),
(586, NULL, 'Bert Bert', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:20:49'),
(587, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:21:05'),
(588, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:21:15'),
(589, NULL, 'Bert Bert', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:21:37'),
(590, NULL, 'Bert Bert', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:21:48'),
(591, NULL, 'Bert Bert', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:26:05'),
(592, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:26:31'),
(593, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:32:32'),
(594, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:32:43'),
(595, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:36:22'),
(596, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:36:37'),
(597, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:51:23'),
(598, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:52:12'),
(599, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:10:26'),
(600, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:10:41'),
(601, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:14:09'),
(602, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:14:20'),
(603, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:14:51'),
(604, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:15:04'),
(605, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:17:21'),
(606, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:17:30'),
(607, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:19:53'),
(608, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:20:02'),
(609, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:20:22'),
(610, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:20:32'),
(611, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:21:12'),
(612, NULL, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:21:42'),
(613, NULL, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:25:45'),
(614, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:29:04'),
(615, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:34:02'),
(616, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:34:20'),
(617, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:59:26'),
(618, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:59:38'),
(619, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:06:05'),
(620, 159, 'Claude Claude', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:06:18'),
(621, 159, 'Claude Claude', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:07:50'),
(622, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:08:00'),
(623, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:14:58'),
(624, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:15:09'),
(625, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:21:23'),
(626, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:21:39'),
(627, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:22:22'),
(628, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:22:34'),
(629, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:59:03'),
(630, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:59:14'),
(631, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:59:31'),
(632, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:59:46'),
(633, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:00:02'),
(634, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:00:15'),
(635, NULL, '', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:00:29'),
(636, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:00:29'),
(637, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:01:05'),
(638, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:15:55'),
(639, NULL, '', 'faculty', 'password_reset_request', 'System', 'Password reset email failed: SMTP authentication failed. Check username/app password.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:30:20'),
(640, NULL, '', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:48:41'),
(641, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:48:41'),
(642, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 00:49:13'),
(643, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:26:10'),
(644, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:39:25'),
(645, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:39:37'),
(646, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:39:54'),
(647, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:40:09'),
(648, NULL, '', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:40:21'),
(649, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:40:21'),
(650, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:40:57'),
(651, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:09'),
(652, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:15'),
(653, NULL, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:31'),
(654, NULL, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:36'),
(655, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:50'),
(656, NULL, 'Maria Santos', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:02'),
(657, NULL, 'Maria Santos', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:20'),
(658, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:32'),
(659, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:49'),
(660, NULL, 'ming ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:43:00'),
(661, NULL, 'ming ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:45:19'),
(662, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:45:32'),
(663, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:46:24');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(664, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:46:40'),
(665, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:48:23'),
(666, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:48:33'),
(667, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:59:44'),
(668, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:00:01'),
(669, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:00:31'),
(670, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:01:34'),
(671, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:01:50'),
(672, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:02:07'),
(673, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:02:24'),
(674, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:02:46'),
(675, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:07:46'),
(676, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:07:56'),
(677, NULL, '', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-19 03:09:04'),
(678, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-19 03:09:05'),
(679, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 03:19:40'),
(680, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:05:53'),
(681, NULL, '', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:12:01'),
(682, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:14:38'),
(683, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:16:08'),
(684, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:16:44'),
(685, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:16:59'),
(686, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:19:15'),
(687, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:19:32'),
(688, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:19:43'),
(689, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:20:39'),
(690, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:31:00'),
(691, NULL, '', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:31:33'),
(692, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:31:33'),
(693, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:32:06'),
(694, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:33:44'),
(695, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:33:54'),
(696, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:34:11'),
(697, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:34:34'),
(698, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:35:08'),
(699, NULL, '', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:35:21'),
(700, NULL, '', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:39:56'),
(701, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:40:56'),
(702, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:08'),
(703, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:17'),
(704, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:35'),
(705, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:44'),
(706, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:57'),
(707, NULL, '', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:43:09'),
(708, NULL, '', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:44:45'),
(709, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:44:56'),
(710, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:55:05'),
(711, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:55:24'),
(712, NULL, 'Alexander.mercer@gmail.com', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:01:41'),
(713, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:01:59'),
(714, NULL, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:06'),
(715, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:11'),
(716, NULL, 'test testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:17'),
(717, NULL, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:28'),
(718, NULL, 'Alexander Mercer', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:06:38'),
(719, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:06:50'),
(720, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:06:54'),
(721, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:05'),
(722, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:17'),
(723, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:24'),
(724, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:30'),
(725, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:41'),
(726, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:31:47'),
(727, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:31:57'),
(728, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:14'),
(729, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:24'),
(730, NULL, 'Erwin Smith', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:39'),
(731, NULL, 'Erwin Smith', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:56'),
(732, NULL, 'Erwin Smith', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:33:29'),
(733, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:33:40'),
(734, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:34:16'),
(735, NULL, 'Erwin Smith', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:34:28'),
(736, NULL, 'Erwin Smith', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:39:26'),
(737, NULL, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:39:36'),
(738, NULL, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:52:36'),
(739, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:52:49'),
(740, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:58:18'),
(741, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:58:40'),
(742, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:09'),
(743, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:21'),
(744, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:42'),
(745, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:49'),
(746, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:01:11'),
(747, NULL, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:01:22'),
(748, NULL, 'Monitoring Officer', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:01:39'),
(749, NULL, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:10:09'),
(750, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:10:22'),
(751, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:11:27'),
(752, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:11:37'),
(753, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:12:31'),
(754, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:12:44'),
(755, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:13:27'),
(756, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:13:40'),
(757, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:14:26'),
(758, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:14:41'),
(759, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:19:15'),
(760, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:19:24'),
(761, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:32:49'),
(762, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:33:06'),
(763, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:41:31'),
(764, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:42:10'),
(765, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 15:26:40'),
(766, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 15:27:07'),
(767, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:53:01'),
(768, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:53:45'),
(769, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:53:55'),
(770, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:13'),
(771, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:23'),
(772, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:42'),
(773, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:52'),
(774, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:55:08'),
(775, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:55:15'),
(776, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 00:13:23'),
(777, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 00:13:37'),
(778, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 01:59:10'),
(779, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 18:04:32'),
(780, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:07:36'),
(781, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:07:50'),
(782, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:11:16'),
(783, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:11:19'),
(784, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:12:04'),
(785, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:12:16'),
(786, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:13:03'),
(787, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:13:15'),
(788, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:25:23'),
(789, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:25:40'),
(790, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:30:30'),
(791, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:35:43'),
(792, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:22:54'),
(793, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:30:33'),
(794, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:30:44'),
(795, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:30:58'),
(796, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:31:13'),
(797, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:50:27'),
(798, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:50:36'),
(799, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:32:56'),
(800, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:33:31'),
(801, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:33:40'),
(802, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:36:51'),
(803, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:37:04'),
(804, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:22:26'),
(805, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:59:19'),
(806, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:59:29'),
(807, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:11:36'),
(808, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:11:44'),
(809, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:14:15'),
(810, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:14:26'),
(811, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:57:01'),
(812, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:21:38'),
(813, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:21:47'),
(814, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:35:31'),
(815, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:35:41'),
(816, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:19:45'),
(817, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:19:53'),
(818, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:20:20'),
(819, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:20:43'),
(820, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:21:45'),
(821, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:21:53'),
(822, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 22:57:07'),
(823, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 23:51:59'),
(824, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 23:52:06'),
(825, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:44:00'),
(826, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:10:34'),
(827, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:10:49'),
(828, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:11:10'),
(829, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:11:23'),
(830, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:23:52'),
(831, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:25:27'),
(832, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:31:34'),
(833, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:31:47'),
(834, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 13:27:30'),
(835, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 13:57:08'),
(836, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 13:57:24'),
(837, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:25:53'),
(838, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:26:02'),
(839, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:26:24'),
(840, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:26:38'),
(841, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:51:34'),
(842, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:51:49'),
(843, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 18:59:53'),
(844, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:08:16'),
(845, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:08:27'),
(846, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:08:41'),
(847, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:16:02'),
(848, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:17:18'),
(849, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:08'),
(850, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:17'),
(851, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:25'),
(852, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:35'),
(853, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:22:33'),
(854, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:22:42'),
(855, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:26:17'),
(856, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:30:23'),
(857, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:33:17'),
(858, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:33:30'),
(859, NULL, '', 'department_head', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:33:44'),
(860, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:39:06'),
(861, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:39:54'),
(862, NULL, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:40:03'),
(863, NULL, 'Department Head', 'department_head', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:40:15'),
(864, NULL, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:50:13'),
(865, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:50:25'),
(866, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:01:05'),
(867, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:12:23'),
(868, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:12:39'),
(869, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:12:56'),
(870, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:14:14'),
(871, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:14:30'),
(872, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:26:27'),
(873, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:26:34'),
(874, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:27:56'),
(875, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 01:52:33'),
(876, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 01:58:05'),
(877, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 01:58:15'),
(878, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:20:03'),
(879, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:20:14'),
(880, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:32:09'),
(881, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:32:24'),
(882, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:41:48');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(883, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:01'),
(884, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:27'),
(885, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:38'),
(886, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:53'),
(887, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:43:04'),
(888, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 13:43:34'),
(889, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:10:55'),
(890, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:11:09'),
(891, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:13:33'),
(892, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:13:48'),
(893, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:27:10'),
(894, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:27:25'),
(895, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:27:49'),
(896, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:28:01'),
(897, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:29:43'),
(898, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:29:58'),
(899, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:37:07'),
(900, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:37:23'),
(901, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:38:01'),
(902, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:38:12'),
(903, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:44:55'),
(904, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:45:04'),
(905, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:52:48'),
(906, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:52:59'),
(907, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:04:26'),
(908, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:04:37'),
(909, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission it_office:lms = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:06:01'),
(910, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission it_office:lms = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:06:02'),
(911, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission it_office:lms = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:06:07'),
(912, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission it_office:lms = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:06:08'),
(913, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission it_office:payment = grant', '::1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-23 15:06:23'),
(914, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission it_office:payment = deny', '::1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', '2026-08-23 15:06:23'),
(915, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 01:49:37'),
(916, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 01:53:39'),
(917, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 01:53:53'),
(918, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:10:49'),
(919, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:11:00'),
(920, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:11:39'),
(921, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:11:50'),
(922, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:12:02'),
(923, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:12:12'),
(924, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:12:56'),
(925, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:13:06'),
(926, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:16:26'),
(927, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:16:37'),
(928, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:00'),
(929, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:11'),
(930, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:31'),
(931, NULL, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:41'),
(932, NULL, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:29:35'),
(933, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:29:47'),
(934, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:31:29'),
(935, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:31:40'),
(936, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:32:09'),
(937, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:32:59'),
(938, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:39:17'),
(939, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:39:30'),
(940, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:05'),
(941, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:18'),
(942, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:33'),
(943, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:44'),
(944, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:41:15'),
(945, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:41:24'),
(946, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:46:26'),
(947, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:46:39'),
(948, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:04:34'),
(949, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:04:51'),
(950, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:04:57'),
(951, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:05:06'),
(952, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:13:54'),
(953, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:14:08'),
(954, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:16:07'),
(955, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:16:19'),
(956, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 00:51:57'),
(957, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:10:26'),
(958, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:10:36'),
(959, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:25:10'),
(960, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:25:22'),
(961, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:40:50'),
(962, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:41:04'),
(963, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:42:57'),
(964, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:43:06'),
(965, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:52:51'),
(966, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:53:08'),
(967, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:02:40'),
(968, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:02:51'),
(969, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:00'),
(970, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:13'),
(971, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:25'),
(972, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:35'),
(973, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:04'),
(974, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:18'),
(975, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:31'),
(976, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:41'),
(977, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 14:59:57'),
(978, NULL, 'Jean Espejo', 'faculty', 'create', 'faculty', 'Submitted Letter of Intent for clearance #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:00:55'),
(979, NULL, 'Jean Espejo', 'faculty', 'create', 'faculty', 'Submitted faculty clearance requirements #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:01:01'),
(980, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:01:21'),
(981, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:02:26'),
(982, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #1: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:02:47'),
(983, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #3: denied', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:02:52'),
(984, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #4: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:02:57'),
(985, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #5: placed on hold', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:03:03'),
(986, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Renewed contract for faculty #35 (Jean Claude Espejo) from Jun 10, 2031 to Jun 09, 2032 (Status: Probationary)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:03:25'),
(987, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:03:35'),
(988, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:03:40'),
(989, NULL, 'Jean Espejo', 'faculty', 'create', 'faculty', 'Submitted faculty clearance requirements #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:04:12'),
(990, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:04:16'),
(991, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:04:22'),
(992, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #3: denied', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:05:22'),
(993, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #5: denied', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:05:27'),
(994, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:05:33'),
(995, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:05:41'),
(996, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:09:57'),
(997, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:10:06'),
(998, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:14:25'),
(999, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:14:35'),
(1000, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:15:26'),
(1001, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:15:37'),
(1002, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:17:08'),
(1003, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:17:25'),
(1004, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:17:41'),
(1005, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:17:50'),
(1006, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:20:30'),
(1007, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:20:47'),
(1008, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:48:05'),
(1009, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:48:17'),
(1010, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:13:52'),
(1011, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:15:38'),
(1012, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:16:53'),
(1013, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:17:04'),
(1014, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:17:44'),
(1015, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:17:55'),
(1016, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:26:15'),
(1017, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:26:29'),
(1018, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:26:55'),
(1019, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:27:09'),
(1020, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:27:33'),
(1021, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:27:39'),
(1022, NULL, 'Jean Espejo', 'faculty', 'update', 'faculty', 'Faculty reset clearance requirements #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:28:13'),
(1023, NULL, 'Jean Espejo', 'faculty', 'create', 'faculty', 'Submitted Letter of Intent for clearance #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:33:34'),
(1024, NULL, 'Jean Espejo', 'faculty', 'create', 'faculty', 'Submitted faculty clearance requirements #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:37:51'),
(1025, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:38:08'),
(1026, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:38:17'),
(1027, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:43:35'),
(1028, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:43:53'),
(1029, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:51:15'),
(1030, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:51:30'),
(1031, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:59:43'),
(1032, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:00:29'),
(1033, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:01:06'),
(1034, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:01:17'),
(1035, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:02:22'),
(1036, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:02:32'),
(1037, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:03:09'),
(1038, NULL, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:03:28'),
(1039, NULL, 'Monitoring Officer', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 17:03:37'),
(1040, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:01:28'),
(1041, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:37:50'),
(1042, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:38:04'),
(1043, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:39:07'),
(1044, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:39:21'),
(1045, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:43:44'),
(1046, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:44:05'),
(1047, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:45:11'),
(1048, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:45:23'),
(1049, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:48:03'),
(1050, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:48:20'),
(1051, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:48:44'),
(1052, NULL, 'qwe qwe qwe', 'faculty_schedule_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 01:48:59'),
(1053, NULL, 'qwe qwe qwe', 'faculty_schedule_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 02:15:54'),
(1054, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:39:03'),
(1055, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:42:17'),
(1056, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:42:33'),
(1057, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:49:20'),
(1058, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:49:32'),
(1059, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:56:19'),
(1060, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 16:57:11'),
(1061, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 17:01:49'),
(1062, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 17:01:58'),
(1063, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 17:06:35'),
(1064, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 17:06:42'),
(1065, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 17:43:56'),
(1066, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 17:44:12'),
(1067, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 18:07:06'),
(1068, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 18:07:19'),
(1069, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 18:29:40'),
(1070, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 18:30:18'),
(1071, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:03:23'),
(1072, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:03:35'),
(1073, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:48:41'),
(1074, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:48:54'),
(1075, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:49:02'),
(1076, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:49:12'),
(1077, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:49:19'),
(1078, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:49:29'),
(1079, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:49:38'),
(1080, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:49:48'),
(1081, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 19:51:14'),
(1082, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:06:39'),
(1083, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:06:52'),
(1084, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:07:07'),
(1085, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:39:39'),
(1086, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:39:57'),
(1087, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:41:07'),
(1088, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 21:41:20'),
(1089, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:11:49'),
(1090, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:12:06'),
(1091, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:18:34'),
(1092, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:18:43'),
(1093, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:24:55'),
(1094, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:25:06'),
(1095, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:40:52'),
(1096, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 01:41:03'),
(1097, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 13:42:26'),
(1098, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 14:04:03'),
(1099, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 14:04:13');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1100, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 14:05:06'),
(1101, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 14:05:20'),
(1102, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 14:05:27'),
(1103, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 14:05:36'),
(1104, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:14:43'),
(1105, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:25:01'),
(1106, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:25:13'),
(1107, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:30:37'),
(1108, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:30:48'),
(1109, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:39:07'),
(1110, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:39:14'),
(1111, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:43:51'),
(1112, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:43:58'),
(1113, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:45:46'),
(1114, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:45:54'),
(1115, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:51:42'),
(1116, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 20:51:59'),
(1117, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 21:04:24'),
(1118, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 21:04:37'),
(1119, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:00:01'),
(1120, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:29:05'),
(1121, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:29:16'),
(1122, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:52:02'),
(1123, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:52:11'),
(1124, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:52:37'),
(1125, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 10:52:49'),
(1126, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 18:05:51'),
(1127, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 18:09:16'),
(1128, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 18:09:27'),
(1129, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 20:37:33'),
(1130, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:26:39'),
(1131, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:42:39'),
(1132, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:42:48'),
(1133, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:43:10'),
(1134, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:43:21'),
(1135, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:47:41'),
(1136, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:47:51'),
(1137, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:51:26'),
(1138, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 22:51:35'),
(1139, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 23:37:28'),
(1140, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-30 23:37:40'),
(1141, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 00:20:29'),
(1142, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 00:20:40'),
(1143, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:11:01'),
(1144, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:11:19'),
(1145, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:13:17'),
(1146, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:13:29'),
(1147, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:13:52'),
(1148, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:14:04'),
(1149, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:19:10'),
(1150, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 01:19:21'),
(1151, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:11:36'),
(1152, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:11:52'),
(1153, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:12:24'),
(1154, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:12:31'),
(1155, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:20:07'),
(1156, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:20:17'),
(1157, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:20:59'),
(1158, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 02:21:08'),
(1159, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:06:45'),
(1160, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:21:32'),
(1161, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:21:41'),
(1162, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:26:44'),
(1163, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:26:55'),
(1164, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:29:07'),
(1165, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:29:17'),
(1166, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:35:49'),
(1167, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 00:36:05'),
(1168, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:21:30'),
(1169, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:22:18'),
(1170, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:22:57'),
(1171, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:23:05'),
(1172, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:23:25'),
(1173, NULL, 'Christopher Navarro', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:23:35'),
(1174, NULL, 'Christopher Navarro', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:24:35'),
(1175, NULL, 'Christopher Navarro', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:33:06'),
(1176, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:33:13'),
(1177, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 00:50:10'),
(1178, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 00:52:43'),
(1179, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 00:53:00'),
(1180, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 00:53:11'),
(1181, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 00:56:41'),
(1182, NULL, 'Adrian Santos', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 00:56:50'),
(1183, NULL, 'Adrian Santos', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-02 01:00:03'),
(1184, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 18:54:24'),
(1185, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:06:31'),
(1186, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:07:03'),
(1187, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:19:22'),
(1188, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:20:26'),
(1189, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:24:21'),
(1190, NULL, 'Adrian Santos', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:24:33'),
(1191, NULL, 'Adrian Santos', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:28:28'),
(1192, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:28:39'),
(1193, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:29:26'),
(1194, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:29:37'),
(1195, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:55:50'),
(1196, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:55:55'),
(1197, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:56:26'),
(1198, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:56:31'),
(1199, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:56:57'),
(1200, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 19:57:06'),
(1201, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:17:03'),
(1202, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:17:10'),
(1203, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:29:24'),
(1204, 158, 'dwadsadwa Qwerty', 'secretary', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:29:34'),
(1205, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:29:46'),
(1206, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:47:55'),
(1207, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:48:06'),
(1208, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:11:33'),
(1209, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:11:46'),
(1210, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:17:40'),
(1211, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:17:56'),
(1212, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:18:05'),
(1213, NULL, 'Adrian Santos', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:18:11'),
(1214, NULL, 'Adrian Santos', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:19:03'),
(1215, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:19:12'),
(1216, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:49:23'),
(1217, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:49:32'),
(1218, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:49:46'),
(1219, NULL, 'Adrian Santos', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:49:53'),
(1220, NULL, 'Adrian Santos', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:50:09'),
(1221, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 00:50:17'),
(1222, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:04:38'),
(1223, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:04:53'),
(1224, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:05:21'),
(1225, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:05:31'),
(1226, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:09:17'),
(1227, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:09:36'),
(1228, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:17:41'),
(1229, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:17:55'),
(1230, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:19:06'),
(1231, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:19:18'),
(1232, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:21:44'),
(1233, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:21:54'),
(1234, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:24:43'),
(1235, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:24:53'),
(1236, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:54:42'),
(1237, NULL, 'Daniel Cruz', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:55:13'),
(1238, NULL, 'Daniel Cruz', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 01:55:29'),
(1239, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 15:35:16'),
(1240, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 15:53:12'),
(1241, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 15:53:23'),
(1242, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 15:55:26'),
(1243, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 15:55:35'),
(1244, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 18:27:24'),
(1245, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:23:39'),
(1246, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:23:58'),
(1247, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:32:49'),
(1248, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:33:00'),
(1249, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:33:19'),
(1250, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:33:27'),
(1251, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:39:21'),
(1252, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 19:39:34'),
(1253, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 20:24:48'),
(1254, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 20:24:54'),
(1255, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 23:12:42'),
(1256, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 23:58:55'),
(1257, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-03 23:59:05'),
(1258, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 00:03:16'),
(1259, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 00:03:28'),
(1260, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 01:03:31'),
(1261, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 01:05:54'),
(1262, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:04:24'),
(1263, NULL, 'Gray Fullbuster', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:05:01'),
(1264, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:14:34'),
(1265, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:14:45'),
(1266, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:16:24'),
(1267, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:16:39'),
(1268, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:17:00'),
(1269, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 13:17:09'),
(1270, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:06:31'),
(1271, 151, 'Faculty Admin', 'faculty_admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:06:39'),
(1272, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:06:48'),
(1273, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:10:02'),
(1274, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:10:11'),
(1275, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:11:46'),
(1276, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 14:12:05'),
(1277, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:05:44'),
(1278, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:17:54'),
(1279, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:19:43'),
(1280, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:19:51'),
(1281, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:20:04'),
(1282, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:20:15'),
(1283, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:21:31'),
(1284, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:21:39'),
(1285, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:21:57'),
(1286, NULL, 'John Michae Santos', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:22:16'),
(1287, NULL, 'John Michae Santos', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:22:36'),
(1288, NULL, 'John Michae Santos', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:22:43'),
(1289, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:22:51'),
(1290, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:25:12'),
(1291, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:25:27'),
(1292, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:25:46'),
(1293, NULL, 'John Michae Santos', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:25:54'),
(1294, NULL, 'John Michae Santos', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:28:01'),
(1295, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:28:07'),
(1296, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:31:13'),
(1297, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:31:19'),
(1298, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:32:07'),
(1299, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:32:13'),
(1300, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:38:59'),
(1301, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:39:02'),
(1302, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:54:23'),
(1303, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:54:30'),
(1304, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:54:59'),
(1305, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:55:05'),
(1306, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:55:31'),
(1307, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:55:39'),
(1308, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:57:26'),
(1309, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:57:32'),
(1310, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:57:53'),
(1311, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 15:58:01'),
(1312, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 16:33:30'),
(1313, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-04 16:33:47'),
(1314, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:09:16'),
(1315, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:09:30'),
(1316, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:26:16');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1317, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:28:27'),
(1318, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:38:05'),
(1319, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:49:55'),
(1320, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:50:22'),
(1321, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:50:35'),
(1322, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:53:40'),
(1323, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:53:47'),
(1324, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:54:00'),
(1325, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:54:07'),
(1326, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:54:58'),
(1327, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:55:08'),
(1328, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:55:18'),
(1329, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:55:28'),
(1330, 227, 'HR User', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:55:43'),
(1331, 227, 'HR User', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:55:53'),
(1332, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-06 23:56:05'),
(1333, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:02:59'),
(1334, 2, 'Registrar', 'registrar', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:04:20'),
(1335, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:04:20'),
(1336, 227, 'HR User', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:08:03'),
(1337, 227, 'HR User', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:23:27'),
(1338, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:23:34'),
(1339, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:23:35'),
(1340, 151, 'Faculty Admin', 'faculty_admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:30:00'),
(1341, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:30:07'),
(1342, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:30:18'),
(1343, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:30:26'),
(1344, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:30:39'),
(1345, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:30:48'),
(1346, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:31:16'),
(1347, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:31:23'),
(1348, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:31:37'),
(1349, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:31:43'),
(1350, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:31:50'),
(1351, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:31:56'),
(1352, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:36:46'),
(1353, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:36:53'),
(1354, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:36:57'),
(1355, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:06'),
(1356, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:14'),
(1357, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:23'),
(1358, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:30'),
(1359, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:38'),
(1360, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:48'),
(1361, 227, 'HR User', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:37:59'),
(1362, 227, 'HR User', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:43:00'),
(1363, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:43:07'),
(1364, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:43:43'),
(1365, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:43:43'),
(1366, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:44:15'),
(1367, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:54:08'),
(1368, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:54:18'),
(1369, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:54:18'),
(1370, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:56:06'),
(1371, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 00:58:27'),
(1372, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:00:35'),
(1373, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:00:35'),
(1374, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:01:37'),
(1375, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:03:34'),
(1376, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:06:17'),
(1377, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:06:17'),
(1378, 227, 'HR User', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:06:46'),
(1379, 227, 'HR User', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:09:06'),
(1380, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:09:21'),
(1381, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:10:10'),
(1382, 227, 'HR User', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:10:18'),
(1383, 227, 'HR User', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:14:23'),
(1384, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:14:29'),
(1385, 2, 'Registrar', 'registrar', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:29:20'),
(1386, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:29:20'),
(1387, 2, 'Registrar', 'registrar', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:34:50'),
(1388, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:35:01'),
(1389, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:37:32'),
(1390, 2, 'Registrar', 'registrar_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:57:32'),
(1391, 2, 'Registrar', 'registrar_clearance', 'login', 'registrar', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:58:38'),
(1392, 2, 'Registrar', 'registrar_clearance', 'logout', 'registrar', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:58:58'),
(1393, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 01:59:07'),
(1394, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:00:33'),
(1395, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:00:33'),
(1396, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:01:06'),
(1397, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:10:55'),
(1398, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:16:26'),
(1399, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:16:26'),
(1400, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:16:53'),
(1401, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:16:58'),
(1402, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:17:06'),
(1403, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:17:12'),
(1404, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:17:21'),
(1405, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:17:26'),
(1406, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:17:32'),
(1407, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 02:43:17'),
(1408, 227, 'HR User', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 19:46:15'),
(1409, 227, 'HR User', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 19:46:41'),
(1410, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 19:47:02'),
(1411, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:03:24'),
(1412, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:04:04'),
(1413, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:04:05'),
(1414, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:05:16'),
(1415, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:05:48'),
(1416, 4, 'Finance', 'finance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:07:31'),
(1417, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:07:31'),
(1418, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:10:08'),
(1419, 4, 'Finance', 'finance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:10:40'),
(1420, 227, 'HR User', 'hrnce', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:10:58'),
(1421, 227, 'HR User', 'hrnce', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:24:08'),
(1422, 227, 'HR User', 'hrnce', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:24:20'),
(1423, 227, 'HR User', 'hrnce', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:29:02'),
(1424, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:32:03'),
(1425, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:34:53'),
(1426, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:35:00'),
(1427, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:38:10'),
(1428, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:39:37'),
(1429, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:47:14'),
(1430, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:47:24'),
(1431, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:50:53'),
(1432, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:51:02'),
(1433, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:51:40'),
(1434, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:51:49'),
(1435, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:55:45'),
(1436, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:55:55'),
(1437, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:58:30'),
(1438, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:58:35'),
(1439, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:59:30'),
(1440, 227, 'HR User', 'hr_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 20:59:46'),
(1441, 227, 'HR User', 'hr_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 21:43:04'),
(1442, 230, 'Library User', 'library_clearance', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 21:43:16'),
(1443, 230, 'Library User', 'library_clearance', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 21:43:37'),
(1444, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 21:44:09'),
(1445, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:11:17'),
(1446, NULL, 'Gray Fullbuster', 'faculty', 'login_failed', 'System', 'Login blocked — account status: inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:11:29'),
(1447, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:11:42'),
(1448, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:12:23'),
(1449, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:12:31'),
(1450, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:12:35'),
(1451, 151, 'Faculty Admin', 'faculty_admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:12:42'),
(1452, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 22:13:12'),
(1453, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:08:55'),
(1454, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:09:12'),
(1455, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:09:42'),
(1456, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:09:52'),
(1457, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:10:21'),
(1458, NULL, 'Dean', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:10:28'),
(1459, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:10:37'),
(1460, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:35:11'),
(1461, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:35:21'),
(1462, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:42:18'),
(1463, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:42:28'),
(1464, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:50:37'),
(1465, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:50:48'),
(1466, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:50:56'),
(1467, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:51:06'),
(1468, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:55:54'),
(1469, NULL, 'Dean', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:56:01'),
(1470, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-07 23:56:08'),
(1471, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:22:43'),
(1472, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:22:52'),
(1473, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:22:58'),
(1474, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:33:27'),
(1475, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:33:39'),
(1476, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:34:28'),
(1477, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:34:38'),
(1478, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:39:51'),
(1479, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:39:58'),
(1480, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:41:22'),
(1481, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:41:37'),
(1482, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:44:07'),
(1483, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:44:14'),
(1484, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:46:01'),
(1485, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:46:09'),
(1486, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:49:27'),
(1487, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:49:35'),
(1488, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:50:04'),
(1489, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:54:38'),
(1490, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:55:16'),
(1491, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 18:55:26'),
(1492, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:02:14'),
(1493, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:02:27'),
(1494, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:48:34'),
(1495, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:48:51'),
(1496, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:49:07'),
(1497, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:49:14'),
(1498, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:49:48'),
(1499, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 19:51:43'),
(1500, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 01:02:14'),
(1501, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 03:00:28'),
(1502, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 22:28:44'),
(1503, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 22:28:57'),
(1504, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 22:35:16'),
(1505, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 23:48:28'),
(1506, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 23:48:41'),
(1507, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:35:38'),
(1508, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:35:54'),
(1509, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:39:05'),
(1510, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:39:20'),
(1511, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:45:13'),
(1512, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:45:39'),
(1513, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:49:10'),
(1514, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:49:23'),
(1515, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:51:19'),
(1516, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 01:51:31'),
(1517, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:10:08'),
(1518, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:10:16'),
(1519, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:12:46'),
(1520, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:12:55'),
(1521, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:24:31'),
(1522, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 02:33:11'),
(1523, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 02:15:54'),
(1524, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:14:41'),
(1525, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:14:56'),
(1526, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:15:06'),
(1527, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:26:05'),
(1528, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:26:13'),
(1529, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:27:56'),
(1530, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:28:07'),
(1531, NULL, 'Diosdado Aban', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:28:19'),
(1532, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:28:54'),
(1533, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:29:02'),
(1534, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:32:18'),
(1535, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 15:32:35');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1536, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 16:45:53'),
(1537, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 16:46:00'),
(1538, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 17:08:53'),
(1539, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 17:08:59'),
(1540, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 17:35:50'),
(1541, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:11:03'),
(1542, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:46:06'),
(1543, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:46:18'),
(1544, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:55:21'),
(1545, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:55:31'),
(1546, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:58:33'),
(1547, NULL, 'Gray Fullbuster', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 02:58:40'),
(1548, NULL, 'Gray Fullbuster', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:00:29'),
(1549, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:00:37'),
(1550, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:01:49'),
(1551, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:01:59'),
(1552, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:02:42'),
(1553, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:02:50'),
(1554, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:05:34'),
(1555, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 03:05:43'),
(1556, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 18:28:37'),
(1557, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 18:35:49'),
(1558, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 18:36:05'),
(1559, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 19:08:24'),
(1560, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 19:08:30'),
(1561, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 19:54:34'),
(1562, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 19:54:43'),
(1563, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 20:49:05'),
(1564, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 20:49:15'),
(1565, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:48:24'),
(1566, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:48:38'),
(1567, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:49:23'),
(1568, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:49:36'),
(1569, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 02:19:40'),
(1570, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 02:19:52'),
(1571, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 02:20:33'),
(1572, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 02:20:54'),
(1573, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 02:21:13'),
(1574, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 02:21:23'),
(1575, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:05:15'),
(1576, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:18:29'),
(1577, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:18:39'),
(1578, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:46:52'),
(1579, 250, 'Light Yagami', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:47:22'),
(1580, 250, 'Light Yagami', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:47:40'),
(1581, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:48:01'),
(1582, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:48:46'),
(1583, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:49:02'),
(1584, 250, 'Light Yagami', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-17 23:49:28'),
(1585, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:03:25'),
(1586, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:03:37'),
(1587, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:04:35'),
(1588, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:04:46'),
(1589, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:32:36'),
(1590, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:32:46'),
(1591, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:33:36'),
(1592, NULL, '', 'secretary', 'login_failed', 'System', 'Login blocked — account status: inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:33:48'),
(1593, NULL, 'ming ming', 'faculty', 'login_failed', 'System', 'Login blocked — account status: inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:34:04'),
(1594, NULL, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:34:13'),
(1595, NULL, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:34:24'),
(1596, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:34:37'),
(1597, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:34:51'),
(1598, NULL, 'Adrian Santos', 'faculty', 'login_failed', 'System', 'Login blocked — account status: inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:35:02'),
(1599, NULL, 'Levi Ackerman', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:36:34'),
(1600, NULL, 'Levi Ackerman', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:37:11'),
(1601, NULL, 'Levi Ackerman', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:37:37'),
(1602, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:37:53'),
(1603, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:42:03'),
(1604, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-18 00:42:13'),
(1605, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:10:25'),
(1606, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:16:09'),
(1607, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:16:19'),
(1608, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:17:04'),
(1609, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:17:16'),
(1610, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:27:41'),
(1611, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:27:52'),
(1612, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:28:58'),
(1613, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 16:29:11'),
(1614, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 18:44:31'),
(1615, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 19:12:33'),
(1616, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 19:12:44'),
(1617, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 20:09:41'),
(1618, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 20:09:59'),
(1619, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 20:36:40'),
(1620, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 20:36:55'),
(1621, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 21:05:22'),
(1622, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-19 21:05:38'),
(1623, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:45:09'),
(1624, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:50:34'),
(1625, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:50:47'),
(1626, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:51:06'),
(1627, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:51:15'),
(1628, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:55:04'),
(1629, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:56:22'),
(1630, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:57:06'),
(1631, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:57:15'),
(1632, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:57:47'),
(1633, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 00:57:56'),
(1634, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:06:45'),
(1635, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:06:57'),
(1636, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:19:46'),
(1637, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:19:57'),
(1638, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:20:10'),
(1639, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:20:18'),
(1640, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:34:58'),
(1641, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:35:07'),
(1642, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:40:39'),
(1643, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:40:59'),
(1644, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:41:21'),
(1645, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:41:28'),
(1646, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 01:50:55'),
(1647, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 02:04:32'),
(1648, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 02:05:30'),
(1649, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 02:05:44'),
(1650, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-20 18:32:10'),
(1651, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 00:33:23'),
(1652, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 00:33:40'),
(1653, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 00:33:47'),
(1654, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 00:48:35'),
(1655, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 00:48:44'),
(1656, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 02:04:28'),
(1657, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 02:04:41'),
(1658, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 02:12:21'),
(1659, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 02:13:43'),
(1660, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-21 21:12:19'),
(1661, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:11:33'),
(1662, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:11:46'),
(1663, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:11:56'),
(1664, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:14:33'),
(1665, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:14:43'),
(1666, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:18:10'),
(1667, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:18:19'),
(1668, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:23:52'),
(1669, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:24:04'),
(1670, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:24:28'),
(1671, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:25:25'),
(1672, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:26:21'),
(1673, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:26:31'),
(1674, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:30:31'),
(1675, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:30:43'),
(1676, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:30:56'),
(1677, NULL, 'Diosdado Aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:31:07'),
(1678, NULL, 'Diosdado Aban', 'faculty', 'create', 'faculty', 'Submitted faculty clearance form #2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:32:29'),
(1679, NULL, 'Diosdado Aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 13:52:11'),
(1680, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 14:04:10'),
(1681, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 14:07:51'),
(1682, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 14:08:00'),
(1683, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:51:26'),
(1684, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:51:55'),
(1685, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:55:49'),
(1686, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:56:14'),
(1687, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:57:11'),
(1688, NULL, 'John Bert Lonzaga', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:58:06'),
(1689, NULL, 'John Bert Lonzaga', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 15:58:39'),
(1690, NULL, 'John Bert Lonzaga', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:01:40'),
(1691, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:01:53'),
(1692, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:02:49'),
(1693, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:03:05'),
(1694, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:04:36'),
(1695, NULL, 'Dean', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:04:41'),
(1696, NULL, 'Dean', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:04:50'),
(1697, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-22 16:05:08'),
(1698, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 14:21:03'),
(1699, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-23 14:50:58'),
(1700, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:18:32'),
(1701, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:18:42'),
(1702, 250, 'Light Yagami', 'faculty', 'create', 'faculty', 'Submitted faculty clearance form #3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:19:15'),
(1703, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:19:23'),
(1704, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:19:29'),
(1705, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Endorsed clearance form #3 for faculty #101', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:20:45'),
(1706, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:20:49'),
(1707, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:21:07'),
(1708, 250, 'Light Yagami', 'faculty', 'create', 'faculty', 'Submitted faculty clearance requirements #3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:23:49'),
(1709, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:24:06'),
(1710, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:24:15'),
(1711, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #5566: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:24:27'),
(1712, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #5566: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:24:31'),
(1713, 149, 'Department Head', 'department_head', 'update', 'faculty', 'Reviewed clearance item #5566: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:25:22'),
(1714, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:25:52'),
(1715, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:26:00'),
(1716, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:27:01'),
(1717, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:27:07'),
(1718, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:28:27'),
(1719, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:28:36'),
(1720, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:30:35'),
(1721, 2, 'Registrar', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:30:45'),
(1722, 2, 'Registrar', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:31:38'),
(1723, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:31:46'),
(1724, 250, 'Light Yagami', 'faculty', 'create', 'faculty', 'Submitted faculty clearance requirements #3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:32:53'),
(1725, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:06'),
(1726, 2, 'Registrar', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:18'),
(1727, 2, 'Registrar', 'registrar_clearance', 'update', 'faculty', 'Reviewed clearance item #5565: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:25'),
(1728, 2, 'Registrar', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:34'),
(1729, 227, 'HR User', 'hr_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:39'),
(1730, 227, 'HR User', 'hr_clearance', 'update', 'faculty', 'Reviewed clearance item #5570: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:46'),
(1731, 227, 'HR User', 'hr_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:34:52'),
(1732, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:35:02'),
(1733, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:35:28'),
(1734, 229, 'Property Custodian User', 'registrar_clearance', 'update', 'faculty', 'Reviewed clearance item #5565: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:08'),
(1735, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:13'),
(1736, 230, 'Library User', 'library_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:18'),
(1737, 230, 'Library User', 'library_clearance', 'update', 'faculty', 'Reviewed clearance item #5567: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:26'),
(1738, 230, 'Library User', 'library_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:30'),
(1739, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:37'),
(1740, 234, 'Finance Office User', 'finance_office', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:54'),
(1741, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:36:54'),
(1742, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:38:06'),
(1743, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:38:19'),
(1744, 230, 'Library User', 'library_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:38:42'),
(1745, 230, 'Library User', 'library_clearance', 'update', 'faculty', 'Reviewed clearance item #5567: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:38:48'),
(1746, 230, 'Library User', 'library_clearance', 'update', 'faculty', 'Reviewed clearance item #5567: cleared', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:38:50'),
(1747, 230, 'Library User', 'library_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:38:54'),
(1748, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 16:39:04'),
(1749, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 18:25:20'),
(1750, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:08:42');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1751, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:08:56'),
(1752, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:09:05'),
(1753, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:09:23'),
(1754, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:33:10'),
(1755, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:33:29'),
(1756, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:33:45'),
(1757, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:33:54'),
(1758, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:47:17'),
(1759, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 19:47:28'),
(1760, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:08:58'),
(1761, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:09:09'),
(1762, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:31:14'),
(1763, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:31:25'),
(1764, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:32:38'),
(1765, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:32:49'),
(1766, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:34:12'),
(1767, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:34:21'),
(1768, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:45:07'),
(1769, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 00:52:48'),
(1770, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:10:55'),
(1771, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:11:06'),
(1772, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:11:21'),
(1773, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:11:27'),
(1774, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:12:09'),
(1775, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:12:17'),
(1776, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:45:47'),
(1777, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 01:47:44'),
(1778, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 06:43:53'),
(1779, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 06:50:58'),
(1780, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 06:51:04'),
(1781, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:52:30'),
(1782, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:52:43'),
(1783, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:56:30'),
(1784, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:56:54'),
(1785, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:57:03'),
(1786, NULL, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:57:15'),
(1787, NULL, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:57:21'),
(1788, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 07:57:34'),
(1789, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:22:17'),
(1790, 230, 'Library User', 'library_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:23:59'),
(1791, 230, 'Library User', 'library_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:05'),
(1792, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:10'),
(1793, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:22'),
(1794, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:32'),
(1795, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:39'),
(1796, 227, 'HR User', 'hr_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:52'),
(1797, 227, 'HR User', 'hr_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:24:57'),
(1798, 2, 'Registrar', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:25:03'),
(1799, 2, 'Registrar', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:25:09'),
(1800, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:25:26'),
(1801, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:48:14'),
(1802, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:48:29'),
(1803, 255, '', 'secretary', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:01:27'),
(1804, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:01:36'),
(1805, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:02:03'),
(1806, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:03:21'),
(1807, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:05:33'),
(1808, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:05:49'),
(1809, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 15:17:23'),
(1810, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:07:44'),
(1811, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:18:15'),
(1812, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:29:58'),
(1813, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:30:25'),
(1814, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:33:13'),
(1815, NULL, 'Dean', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:33:40'),
(1816, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 16:34:11'),
(1817, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:01:10'),
(1818, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:01:31'),
(1819, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:01:43'),
(1820, 151, 'Faculty Admin', 'faculty_admin', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:02:18'),
(1821, 151, NULL, NULL, 'password_reset', 'System', 'Password reset via token', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:05:07'),
(1822, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:05:25'),
(1823, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:06:01'),
(1824, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:07:37'),
(1825, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:07:57'),
(1826, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:08:21'),
(1827, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:56:10'),
(1828, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:57:07'),
(1829, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 17:58:18'),
(1830, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:02:13'),
(1831, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:02:34'),
(1832, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:03:01'),
(1833, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:03:17'),
(1834, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:04:38'),
(1835, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:07:25'),
(1836, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:07:47'),
(1837, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:22:33'),
(1838, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:22:54'),
(1839, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:23:18'),
(1840, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:33:27'),
(1841, 260, 'Jorge Lucero', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:34:00'),
(1842, 260, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:34:43'),
(1843, 260, 'Department Head', 'department_head', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:34:59'),
(1844, 260, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:35:47'),
(1845, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:36:15'),
(1846, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:38:21'),
(1847, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:45:07'),
(1848, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:45:37'),
(1849, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:46:20'),
(1850, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:47:03'),
(1851, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:47:18'),
(1852, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 18:48:28'),
(1853, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:26:34'),
(1854, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:26:43'),
(1855, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:26:49'),
(1856, 149, 'Jean Espejo', 'department_head', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:31:13'),
(1857, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:31:13'),
(1858, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:31:53'),
(1859, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 19:32:26'),
(1860, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 21:48:22'),
(1861, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 21:48:54'),
(1862, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:44:56'),
(1863, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:45:34'),
(1864, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:46:06'),
(1865, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:49:53'),
(1866, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:51:52'),
(1867, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:52:39'),
(1868, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:53:04'),
(1869, 261, 'Sofia Reyes', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:53:44'),
(1870, 261, 'Sofia Reyes', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:54:59'),
(1871, 261, 'Sofia Reyes', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:55:22'),
(1872, 261, 'Sofia Reyes', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:55:36'),
(1873, 261, 'Sofia Reyes', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:57:28'),
(1874, NULL, 'Dean', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 22:59:43'),
(1875, NULL, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:00:00'),
(1876, NULL, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:06:45'),
(1877, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:07:16'),
(1878, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:07:30'),
(1879, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:21:41'),
(1880, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:22:05'),
(1881, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:22:42'),
(1882, 262, 'Dean', 'dean', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:22:59'),
(1883, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:23:44'),
(1884, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:24:07'),
(1885, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:24:40'),
(1886, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:50:47'),
(1887, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:51:21'),
(1888, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-26 23:52:14'),
(1889, 250, 'Unknown', NULL, 'password_change', 'faculty', 'Password changed by user', '::1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36', '2026-09-27 00:39:37'),
(1890, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:41:04'),
(1891, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:41:39'),
(1892, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:43:29'),
(1893, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:49:17'),
(1894, NULL, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:49:49'),
(1895, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:50:16'),
(1896, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:50:59'),
(1897, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:52:33'),
(1898, 255, 'Earl Salvame', 'secretary', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:53:57'),
(1899, 255, 'Earl Salvame', 'secretary', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:54:13'),
(1900, 255, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:55:07'),
(1901, 255, 'Secretary', 'secretary', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:55:32'),
(1902, 255, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:57:53'),
(1903, 256, 'Alfred Joseph Alcantara', 'monitoring_officer', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:58:24'),
(1904, 256, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:59:13'),
(1905, 256, 'Monitoring Officer', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 00:59:30'),
(1906, 256, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 01:36:26'),
(1907, 255, 'Earl Salvame', 'secretary', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 01:37:11'),
(1908, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 01:38:56'),
(1909, 255, 'Earl Salvame', 'secretary', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 01:51:39'),
(1910, 255, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 01:51:58'),
(1911, 255, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:04:54'),
(1912, 229, 'Property Custodian User', 'registrar_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:05:11'),
(1913, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:06:48'),
(1914, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:07:32'),
(1915, 220, 'Registrar Officer Name', 'registrar', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:07:55'),
(1916, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:11:45'),
(1917, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:11:59'),
(1918, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:42:55'),
(1919, 220, 'Registrar Officer Name', 'registrar', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:43:19'),
(1920, 220, 'Registrar Officer Name', 'registrar', 'login', 'enrollment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:43:48'),
(1921, 220, 'Registrar Officer Name', 'registrar', 'logout', 'enrollment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:48:22'),
(1922, 230, 'Library User', 'library_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:48:35'),
(1923, 230, 'Library User', 'library_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:48:51'),
(1924, 230, 'Library User', 'library_clearance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:52:30'),
(1925, 230, 'Library User', 'library_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:52:30'),
(1926, 234, 'Finance Office User', 'finance_office', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:52:59'),
(1927, 234, 'Finance Office User', 'finance_office', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:53:54'),
(1928, 234, 'Finance Office User', 'finance_office', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:54:06'),
(1929, 227, 'HR User', 'hr_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:54:48'),
(1930, 227, 'HR User', 'hr_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:55:04'),
(1931, 227, 'HR User', 'hr_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:55:10'),
(1932, 229, 'Property Custodian User', 'registrar_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:55:27'),
(1933, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:56:09'),
(1934, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:56:15'),
(1935, 220, 'Registrar Officer Name', 'registrar', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:56:46'),
(1936, 220, 'Registrar Officer Name', 'registrar', 'login', 'enrollment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:57:17'),
(1937, 220, 'Registrar Officer Name', 'registrar', 'logout', 'enrollment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:57:44'),
(1938, 230, 'Library User', 'library_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:57:59'),
(1939, 230, 'Library User', 'library_clearance', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:58:24'),
(1940, 230, NULL, NULL, 'password_reset', 'System', 'Password reset via token', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 02:59:50'),
(1941, 230, 'Library User', 'library_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:00:13'),
(1942, 230, 'Library User', 'library_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:00:52'),
(1943, 230, 'Library User', 'library_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:04:50'),
(1944, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:21:46'),
(1945, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:23:03'),
(1946, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:25:48'),
(1947, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:26:07'),
(1948, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:26:32'),
(1949, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:32:32'),
(1950, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:35:04'),
(1951, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 03:36:58'),
(1952, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:17:55'),
(1953, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:18:15'),
(1954, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:18:32'),
(1955, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:24:34'),
(1956, 262, 'Jorge Lucero', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:24:52'),
(1957, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:25:30'),
(1958, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:26:01');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1959, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:28:41'),
(1960, 255, 'Earl Salvame', 'secretary', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:29:05'),
(1961, 255, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:29:45'),
(1962, 255, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:38:04'),
(1963, 256, 'Alfred Joseph Alcantara', 'monitoring_officer', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:38:58'),
(1964, 256, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:39:24'),
(1965, 256, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:48:18'),
(1966, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:48:43'),
(1967, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:49:13'),
(1968, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:54:16'),
(1969, 229, 'Property Custodian User', 'registrar_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:54:30'),
(1970, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:56:18'),
(1971, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:56:28'),
(1972, 220, 'Registrar Officer Name', 'registrar', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:56:46'),
(1973, 220, 'Registrar Officer Name', 'registrar', 'login', 'enrollment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:57:42'),
(1974, 220, 'Registrar Officer Name', 'registrar', 'logout', 'enrollment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:58:09'),
(1975, 230, 'Library User', 'library_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:58:29'),
(1976, 230, 'Library User', 'library_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:58:54'),
(1977, 230, 'Library User', 'library_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:59:29'),
(1978, 234, 'Finance Office User', 'finance_office', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 04:59:50'),
(1979, 234, 'Finance Office User', 'finance_office', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 05:00:14'),
(1980, 234, 'Finance Office User', 'finance_office', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 05:00:20'),
(1981, 227, 'HR User', 'hr_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-27 05:00:38'),
(1982, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 09:41:46'),
(1983, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 09:42:06'),
(1984, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:16:37'),
(1985, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:16:59'),
(1986, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:17:29'),
(1987, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:18:04'),
(1988, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:18:15'),
(1989, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:18:30'),
(1990, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:18:52'),
(1991, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:19:20'),
(1992, 255, 'Earl Salvame', 'secretary', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:19:39'),
(1993, 255, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:27:53'),
(1994, 255, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:28:08'),
(1995, 256, 'Alfred Joseph Alcantara', 'monitoring_officer', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:28:27'),
(1996, 256, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:30:23'),
(1997, 256, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:30:34'),
(1998, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:30:52'),
(1999, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:31:04'),
(2000, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:43:41'),
(2001, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:44:01'),
(2002, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:44:56'),
(2003, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:45:50'),
(2004, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:46:10'),
(2005, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 10:46:27'),
(2006, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 15:02:14'),
(2007, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-29 15:02:45'),
(2008, 151, 'Faculty Admin', 'faculty_admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 17:06:13'),
(2009, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 17:06:40'),
(2010, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 17:06:57'),
(2011, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:28:14'),
(2012, 220, 'Registrar Officer Name', 'registrar', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:28:54'),
(2013, 220, 'Registrar Officer Name', 'registrar', 'login', 'enrollment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:29:10'),
(2014, 220, 'Registrar Officer Name', 'registrar', 'logout', 'enrollment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:33:23'),
(2015, 229, 'Property Custodian User', 'registrar_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:33:40'),
(2016, 229, 'Property Custodian User', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:33:51'),
(2017, 229, 'Property Custodian User', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:48:05'),
(2018, 220, 'Registrar Officer Name', 'registrar_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:48:25'),
(2019, 220, 'Registrar Officer Name', 'registrar_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:48:35'),
(2020, 220, 'Registrar Officer Name', 'registrar_clearance', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:48:49'),
(2021, 220, 'Registrar Officer Name', 'registrar_clearance', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:48:49'),
(2022, 220, 'Registrar Officer Name', 'registrar_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:51:00'),
(2023, 220, 'Registrar Officer Name', 'registrar_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:51:11'),
(2024, 220, 'Registrar Officer Name', 'registrar_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:55:40'),
(2025, 229, 'Property Custodian User', 'property_custodian_office', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:55:58'),
(2026, 229, 'Property Custodian User', 'property_custodian_office', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:56:19'),
(2027, 229, 'Property Custodian User', 'property_custodian_office', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:56:24'),
(2028, 230, 'Library User', 'library_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:57:42'),
(2029, 230, 'Library User', 'library_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:57:57'),
(2030, 230, 'Library User', 'library_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:58:01'),
(2031, 227, 'HR User', 'hr_clearance', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:58:17'),
(2032, 227, 'HR User', 'hr_clearance', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:58:36'),
(2033, 227, 'HR User', 'hr_clearance', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 18:58:41'),
(2034, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:03:30'),
(2035, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:04:12'),
(2036, 234, 'Finance Office User', 'finance_office', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:04:33'),
(2037, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:04:34'),
(2038, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:04:54'),
(2039, 234, 'Finance Office User', 'finance_office', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:05:37'),
(2040, 234, 'Finance Office User', 'finance_office', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:06:20'),
(2041, 234, 'Finance Office User', 'finance_office', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:06:51'),
(2042, 234, 'Finance Office User', 'finance_office', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:11:39'),
(2043, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:11:55'),
(2044, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 19:12:21'),
(2045, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 21:13:21'),
(2046, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 21:13:57'),
(2047, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:01:06'),
(2048, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:02:10'),
(2049, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:02:23'),
(2050, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 08:41:44'),
(2051, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 08:42:05'),
(2052, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:07:33'),
(2053, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-02 10:07:58'),
(2054, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:29:48'),
(2055, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 15:30:01'),
(2056, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 17:16:25'),
(2057, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 17:16:51'),
(2058, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 17:17:22'),
(2059, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 17:55:01'),
(2060, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-06 17:55:16'),
(2061, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 20:50:01'),
(2062, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-07 20:50:19'),
(2063, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:20:01'),
(2064, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:20:21'),
(2065, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:20:31'),
(2066, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:20:46'),
(2067, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:35:50'),
(2068, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:36:08'),
(2069, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 19:36:42'),
(2070, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:11:09'),
(2071, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:11:20'),
(2072, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:11:36'),
(2073, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:31:55'),
(2074, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:33:00'),
(2075, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-08 20:33:21'),
(2076, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 08:05:33'),
(2077, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 08:06:05'),
(2078, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 08:06:25'),
(2079, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 08:06:45'),
(2080, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 08:06:58'),
(2081, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 10:18:42'),
(2082, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 10:19:44'),
(2083, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 10:59:36'),
(2084, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 11:00:11'),
(2085, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 11:07:36'),
(2086, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 11:07:59'),
(2087, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-09 11:08:02'),
(2088, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-13 16:14:57'),
(2089, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-13 16:15:55'),
(2090, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-13 17:00:54'),
(2091, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-13 17:01:19'),
(2092, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-13 18:14:18'),
(2093, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-13 18:14:59'),
(2094, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 23:21:49'),
(2095, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 23:22:03'),
(2096, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 23:34:37'),
(2097, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 23:34:48'),
(2098, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-14 23:35:14'),
(2099, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 23:12:59'),
(2100, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 23:13:40'),
(2101, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 00:27:34'),
(2102, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 00:27:53'),
(2103, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 00:28:48'),
(2104, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 12:46:16'),
(2105, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 12:47:02'),
(2106, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 13:50:10'),
(2107, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 13:56:39'),
(2108, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 13:57:20'),
(2109, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-24 00:30:47'),
(2110, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-24 00:30:58'),
(2111, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-24 00:41:59'),
(2112, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-24 00:42:19'),
(2113, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-24 00:45:38'),
(2114, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-24 00:46:07'),
(2115, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:29:27'),
(2116, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:29:55'),
(2117, 262, 'Unknown', NULL, 'password_change', 'faculty', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:36:13'),
(2118, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:36:20'),
(2119, 262, 'Jorge Lucero', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:36:33'),
(2120, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:36:46'),
(2121, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:37:06'),
(2122, 262, 'Unknown', NULL, 'password_change', 'faculty', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:37:26'),
(2123, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:43:43'),
(2124, 262, 'Jorge Lucero', 'dean', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:44:14'),
(2125, 262, NULL, NULL, 'password_reset', 'System', 'Password reset via token', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:45:12'),
(2126, 262, 'Jorge Lucero', 'dean', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:45:31'),
(2127, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:45:46'),
(2128, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:46:00'),
(2129, 262, 'Unknown', NULL, 'password_change', 'faculty', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:46:43'),
(2130, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:47:54'),
(2131, 262, 'Jorge Lucero', 'dean', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:48:11'),
(2132, 262, 'Jorge Lucero', 'dean', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 00:56:06'),
(2133, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 01:30:03'),
(2134, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 01:30:43'),
(2135, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 20:00:46'),
(2136, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 20:01:05'),
(2137, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 21:25:03'),
(2138, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 21:25:56'),
(2139, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 21:57:14'),
(2140, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 21:57:33'),
(2141, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:07:53'),
(2142, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:08:10'),
(2143, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:09:12'),
(2144, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:10:52'),
(2145, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:11:02'),
(2146, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:11:21'),
(2147, 149, 'Jean Espejo', 'department_head', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:12:42'),
(2148, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:12:42'),
(2149, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:13:06'),
(2150, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:13:40'),
(2151, 250, 'Light Yagami', 'faculty', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:14:02'),
(2152, 250, 'Light Yagami', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:14:24'),
(2153, 250, 'Light Yagami', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:14:51'),
(2154, 256, 'Alfred Joseph Alcantara', 'monitoring_officer', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:15:07'),
(2155, 256, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:15:28'),
(2156, 256, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:15:47'),
(2157, 255, 'Earl Salvame', 'secretary', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:16:04'),
(2158, 255, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:16:14'),
(2159, 255, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:16:54'),
(2160, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:17:10'),
(2161, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:18:02'),
(2162, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:19:20'),
(2163, 149, 'Jean Espejo', 'department_head', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:19:34'),
(2164, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:19:35');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(2165, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:19:58'),
(2166, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:20:40'),
(2167, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:21:32'),
(2168, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:22:42'),
(2169, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:29:53'),
(2170, 262, 'Jorge Lucero', 'dean', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:30:09'),
(2171, 262, 'Jorge Lucero', 'dean', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:30:19'),
(2172, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:31:06'),
(2173, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 22:31:55'),
(2174, 149, 'Unknown', NULL, 'password_change', 'faculty', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 23:33:42'),
(2175, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 23:33:47'),
(2176, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 23:34:09'),
(2177, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 23:34:25'),
(2178, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-30 23:34:40'),
(2179, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-31 00:11:07'),
(2180, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-31 00:11:19'),
(2181, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-31 00:11:36'),
(2182, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:31:14'),
(2183, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:31:42'),
(2184, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:36:28'),
(2185, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:36:45'),
(2186, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:37:18'),
(2187, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:40:48'),
(2188, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:45:24'),
(2189, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 01:45:46'),
(2190, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 17:55:55'),
(2191, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 17:56:49'),
(2192, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 17:58:45'),
(2193, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:02:31'),
(2194, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:02:50'),
(2195, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:03:07'),
(2196, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:03:45'),
(2197, 250, 'Light Yagami', 'faculty', 'login_failed', 'System', 'Login blocked — account status: inactive', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:03:57'),
(2198, 151, 'Faculty Admin', 'faculty_admin', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:04:21'),
(2199, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:04:38'),
(2200, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:13:42'),
(2201, 262, 'Jorge Lucero', 'dean', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:14:02'),
(2202, 262, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:14:42'),
(2203, 262, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:19:15'),
(2204, 149, 'Jean Espejo', 'department_head', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:19:30'),
(2205, 149, 'Jean Espejo', 'department_head', 'login_2fa_initiated', 'System', 'User initiated login 2FA (email OTP)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:19:51'),
(2206, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-11-03 18:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `login_throttles`
--

CREATE TABLE `login_throttles` (
  `id` int(10) UNSIGNED NOT NULL,
  `throttle_key` char(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_ip`, `created_at`) VALUES
(2, 1, '550d259303762ee9ce8b5378b3b6b1e212a4b5cf796b005404689bb4c5596866', '2026-08-06 14:05:55', '2026-08-06 13:06:28', '::1', '2026-08-06 13:05:55'),
(3, 9, '691edab739335bc353c7ecaa7d183393ea51e47def723d4f3e68adccdd10fcb9', '2026-08-06 14:18:23', '2026-08-06 13:18:33', '::1', '2026-08-06 13:18:23'),
(4, 9, '55eabae148518a30c44e17552b678572afdda8d79fc2c59f95152780545da52b', '2026-08-06 14:18:33', NULL, '::1', '2026-08-06 13:18:33'),
(6, 151, 'e2e84bcf60bb38ed031ac3c6c6fc2669beb771b818ded66493021a693680a0ef', '2026-09-26 18:02:10', '2026-09-26 17:05:07', '::1', '2026-09-26 17:02:10'),
(7, 230, 'd21e52fce9f56e9dbeacfedc14337964f8e39aa1419f5ede49e69ce7e369f4f4', '2026-09-27 03:58:18', '2026-09-27 02:59:50', '::1', '2026-09-27 02:58:18'),
(8, 262, '7b4283a91bdeb5b19b1a42b1a34f0be28e22015cadcfe9efb9ee572c249a577e', '2026-10-29 01:44:09', '2026-10-29 00:45:12', '::1', '2026-10-29 00:44:09'),
(9, 262, '2b335dc2c63fd70b94c581fe6b99b7e6c0c51e07d64f885acfd61c694c0c0237', '2026-10-29 01:48:06', '2026-10-29 00:56:00', '::1', '2026-10-29 00:48:06'),
(10, 262, '99245149ed25417686ce5cfb298c910d0b186d17e701e40bdf117462f2f9152f', '2026-10-29 01:56:00', '2026-10-30 22:30:04', '::1', '2026-10-29 00:56:00'),
(11, 262, 'e8dc494565b5b86b4ebc9756801975c1bc6a5f518b395edf526452c08d03efd2', '2026-10-30 23:30:04', '2026-10-30 22:30:13', '::1', '2026-10-30 22:30:04'),
(12, 262, '2f15dcc1017e2936f5bb8cf2907fdb62a40929da7ca5dfb869e3976067699212', '2026-10-30 23:30:13', NULL, '::1', '2026-10-30 22:30:13');

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
-- Dumping data for table `security_otps`
--

INSERT INTO `security_otps` (`id`, `user_id`, `purpose`, `code_hash`, `module_key`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 3, 'auth_setup', '00aa177502733dd1e947e9addf22e1e33ff0a061d3af840955c53e19f129d130', NULL, '2026-07-23 12:32:33', NULL, '2026-07-23 12:22:33'),
(2, 10, 'auth_setup', '434b2a7ce1742c5901ad141e3fd48d88a160776362d41a87fe120c61735dca25', NULL, '2026-07-23 12:41:18', '2026-07-23 12:31:35', '2026-07-23 12:31:18'),
(3, 1, 'login_2fa', 'fe8a2e43fdc5dfd231e4a5365fb4b97accb2d492d0618b8cb1239f2003384197', 'System', '2026-08-06 12:18:17', '2026-08-07 13:47:43', '2026-08-06 12:08:17'),
(4, 1, 'login_2fa', 'e5533c6584ffce50ff4b8a86ab22a27b4aec09511ca75cc45ae10f872e3b395f', 'System', '2026-08-06 12:35:41', '2026-08-07 13:47:43', '2026-08-06 12:25:41'),
(5, 1, 'login_2fa', '42b95c3f3a5bc304110b877053c1d5165d141cb2dd8aa4640078b64f9cd3f55a', 'System', '2026-08-06 12:37:17', '2026-08-07 13:47:43', '2026-08-06 12:27:17'),
(6, 1, 'login_2fa', 'b121a650c927ab1c64dbe5dcd7b1ee6cad3e31559cc17a9498e845353b39543c', 'System', '2026-08-06 12:49:29', '2026-08-07 13:47:43', '2026-08-06 12:39:29'),
(7, 1, 'login_2fa', 'd8a6f5265b58db60841f94a1910e75b34d95e7d484a4d684ba76c8a376ae8b74', 'System', '2026-08-06 12:51:04', '2026-08-07 13:47:43', '2026-08-06 12:41:04'),
(8, 1, 'login_2fa', '464da7d02deaa950b60fba29e5dd949ddb3d4d2108cc4d07dfd084fa472e4c9f', 'System', '2026-08-06 12:54:49', '2026-08-07 13:47:43', '2026-08-06 12:44:49'),
(9, 1, 'login_2fa', '52f02070f7f89d4a26b2de369b5d3f25b1d5ad68edca4505583d7dfae7629fe4', 'System', '2026-08-06 12:57:18', '2026-08-07 13:47:43', '2026-08-06 12:47:18'),
(10, 1, 'login_2fa', 'b901c750520b0d84eccbd2e6c98d5090d087acf2e44adff08fd38976d0db9412', 'System', '2026-08-06 13:19:23', '2026-08-07 13:47:43', '2026-08-06 13:09:23'),
(11, 1, 'password_change', 'a2b739a763e75c0377332f0cf70bc4d4b1fa1e6c777d960a592a3a9a072a3d40', 'admin-account', '2026-08-06 13:20:17', '2026-08-07 13:47:43', '2026-08-06 13:10:17'),
(12, 1, 'login_2fa', '96fb4145442779cd5534a2c540a7e9c6af8ec7d5d65d44f3a362bd466f75ea1f', 'System', '2026-08-06 13:23:25', '2026-08-07 13:47:43', '2026-08-06 13:13:25'),
(13, 1, 'login_2fa', '1a06a98bfb9fd1053e961bd6756f21edf14accb3eec42542a172d8ad2ae0aa66', 'System', '2026-08-06 13:25:26', '2026-08-07 13:47:43', '2026-08-06 13:15:26'),
(14, 1, 'login_2fa', '98e0b51ec04c9d63f34871bfe2e7a6b7284c174c2cdb537b35a4532978a1cff0', 'System', '2026-08-06 13:30:15', '2026-08-07 13:47:43', '2026-08-06 13:20:15'),
(15, 1, 'login_2fa', '6c828c6267e0b07f37a35b2000fc08831c60cece48c87fce4a60aabd2bbf634e', 'System', '2026-08-06 13:35:35', '2026-08-07 13:47:43', '2026-08-06 13:25:35'),
(16, 1, 'login_2fa', '78b7b05beda2d061eafc5c3dc98d62ab33427d54ccad3efbcc93c5686f68379c', 'System', '2026-08-06 13:41:19', '2026-08-07 13:47:43', '2026-08-06 13:31:19'),
(17, 3, 'login_2fa', 'f0bda89589cd1f9af75f462a57bbdf5d5baf94be8e6553c731c314ca8eea028d', 'System', '2026-08-06 13:42:02', '2026-08-07 13:47:43', '2026-08-06 13:32:02'),
(18, 3, 'login_2fa', '38acad02af807fed2131bc29cba07e19e1df2ab2cb5d0dd6324bb02f13a55556', 'System', '2026-08-06 13:51:38', '2026-08-07 13:47:43', '2026-08-06 13:41:38'),
(19, 1, 'login_2fa', 'cdc036ae8ca397a128794e366b26d21e3e8ec44b03aee9f90735aca07f1ad5a6', 'System', '2026-08-06 14:05:22', '2026-08-07 13:47:43', '2026-08-06 13:55:22'),
(20, 1, 'login_2fa', 'edbde5b085641bb56b53105fa228487e2f7d0c2e1a230f920ddbebe2f2c72e14', 'System', '2026-08-06 14:12:17', '2026-08-07 13:47:43', '2026-08-06 14:02:17'),
(21, 1, 'login_2fa', '59e1d8a04bbdb577a5127fa8e4b51d51f57aa2daad911e88e97b6c843272f28d', 'System', '2026-08-06 14:16:28', '2026-08-07 13:47:43', '2026-08-06 14:06:28'),
(22, 3, 'login_2fa', 'e6c1d8b78e999e9bc4f1b633fa0a584669ec5984759d4adabf59745f49bc503e', 'System', '2026-08-06 14:53:44', '2026-08-07 13:47:43', '2026-08-06 14:43:44'),
(23, 1, 'login_2fa', '264797c49afc4ad46350f996be7c1894b7e19828bdb52083f16a24301e3edb82', 'System', '2026-08-06 16:02:00', '2026-08-07 13:47:43', '2026-08-06 15:52:00'),
(24, 3, 'login_2fa', '5c7435d07f6f8c48816e5d6c8522b3add82f6cce0e3284bb92589998761e4583', 'System', '2026-08-06 16:02:53', '2026-08-07 13:47:43', '2026-08-06 15:52:53'),
(25, 3, 'login_2fa', 'a880ff676aac443c05712a3771640b6275a86282c34ba45a2d1075c757b86991', 'System', '2026-08-06 16:45:53', '2026-08-07 13:47:43', '2026-08-06 16:35:53'),
(26, 1, 'login_2fa', 'cd26d8606d44d05c0e9116c5d9b445655a9132310ad7bda5983a969e9ea9f26d', 'System', '2026-08-06 20:13:26', '2026-08-07 13:47:43', '2026-08-06 20:03:26'),
(27, 1, 'login_2fa', '967cb927a3559c93bbbe1d503405f92049cfeaeedcb2fd11c3ec9eee0c4d2f6e', 'System', '2026-08-06 20:31:15', '2026-08-07 13:47:43', '2026-08-06 20:21:15'),
(28, 3, 'login_2fa', '365e076e8bb97bae37a97870e02200186d2776cdced25150c0d37cc086eb8a5b', 'System', '2026-08-06 20:35:28', '2026-08-07 13:47:43', '2026-08-06 20:25:28'),
(29, 1, 'login_2fa', 'ad13979ae9e79127941523c4bbb960dcee573c5765e05db86dbd1bdf67527e62', 'System', '2026-08-07 11:54:29', '2026-08-07 13:47:43', '2026-08-07 11:44:29'),
(30, 3, 'login_2fa', '8c4b0774bc5f82a69bba1cde2b2965d76c7e7db49b4c2a7a4c7415b60bf784cf', 'System', '2026-08-07 11:55:03', '2026-08-07 13:47:43', '2026-08-07 11:45:03'),
(31, 1, 'login_2fa', '90858f8d89d06736dd2f40b75ff8eb3a998e0c93fcbd8e9933f9e73630d7127f', 'System', '2026-08-07 13:57:27', '2026-08-07 13:47:43', '2026-08-07 13:47:27'),
(32, 1, 'passkey_remove', 'df8c90265057ad814bde1ac13e69cd5d1aa480375cd8a00f9e9b4a29e917b7fe', NULL, '2026-08-07 14:33:29', NULL, '2026-08-07 14:23:29'),
(35, 250, 'login_2fa', '8cc43e0c5a0b28b1711f4c78ef384e8d474717bb36899ae8bec95d4a81360779', 'System', '2026-09-26 16:17:40', '2026-09-26 16:11:06', '2026-09-26 16:07:40'),
(36, 250, 'login_2fa', 'dbcf32008e8b72bf1eb19fb865666c78f21970679b925d9d38d60b905406a6ea', 'System', '2026-09-26 16:21:06', '2026-09-26 16:18:15', '2026-09-26 16:11:06'),
(37, 250, 'login_2fa', '4c1c3bc561980e775502252c9fa833dd6ecfa17e2254108dee5c5cc953d042c6', 'System', '2026-09-26 16:28:15', '2026-09-26 16:29:52', '2026-09-26 16:18:15'),
(38, 250, 'login_2fa', '6d1a0d8fc2126ef4ddff5523e63282b436ed5d6a366a99083fea2d603f198977', 'System', '2026-09-26 16:39:52', '2026-09-26 16:30:25', '2026-09-26 16:29:52'),
(39, 8, 'login_2fa', 'd76ca0973dec50cfa0e1853ff7eeb7f03d93bd413d770ac33d7a8b1e35fda660', 'System', '2026-09-26 16:43:33', '2026-09-26 22:59:39', '2026-09-26 16:33:33'),
(40, 151, 'login_2fa', 'c1d311b937cfd437d1e171501db703e2ec2b5209487f9a5d42fdf6faf1c690d2', 'System', '2026-09-26 16:44:06', '2026-09-26 17:01:05', '2026-09-26 16:34:06'),
(41, 151, 'login_2fa', 'c6c5229da3785f0613ea67f04386f9a38bef522b5b8d69f673e5dac3e910ca9a', 'System', '2026-09-26 17:11:05', '2026-09-26 17:01:31', '2026-09-26 17:01:05'),
(42, 151, 'login_2fa', '0db36c75e53ee78b2e8c5e0f8fbc8c894086edd01ec16113a0f3cbde942cc8c9', 'System', '2026-09-26 17:15:20', '2026-09-26 17:06:01', '2026-09-26 17:05:20'),
(43, 149, 'login_2fa', 'f7d30bbb27f517d42029422f1650551e39949ec9cefed69182bbbf11580befab', 'System', '2026-09-26 17:17:53', '2026-09-26 17:08:21', '2026-09-26 17:07:53'),
(44, 151, 'login_2fa', '939cf52547d009b0a2a4a419773a22b512e64282578f17d9fceaea334706b7db', 'System', '2026-09-26 18:06:06', '2026-09-26 18:07:19', '2026-09-26 17:56:06'),
(45, 250, 'login_2fa', '4cab3f4d7dc746259efc26b41f5a90c40d3596611d0a45d80858994a0b015c32', 'System', '2026-09-26 18:07:02', '2026-09-26 17:58:18', '2026-09-26 17:57:02'),
(46, 250, 'login_2fa', '94043be3fc92e7df79c394666cdd3b585e3e6be0da1b1a729d97965adca32bed', 'System', '2026-09-26 18:12:29', '2026-09-26 18:45:31', '2026-09-26 18:02:29'),
(47, 149, 'login_2fa', 'bb528935728c830b996cd70ac27646c971ef6ca4b61db1ba08d6662f534002ae', 'System', '2026-09-26 18:13:13', '2026-09-26 18:04:18', '2026-09-26 18:03:13'),
(48, 149, 'login_2fa', 'c71f9389e08fc923511dd81cc35da82e77728b6fec252f48510032e81018b9d5', 'System', '2026-09-26 18:14:18', '2026-09-26 18:04:38', '2026-09-26 18:04:18'),
(49, 151, 'login_2fa', '4740957740a0211dd0340b5474f7119f1931d3c888c7057b73538e74980718d4', 'System', '2026-09-26 18:17:19', '2026-09-26 18:07:47', '2026-09-26 18:07:19'),
(50, 151, 'login_2fa', 'b62a5b5860037d20d63f6275a92eb6fe0be17bc87f1cafb9ef06ba27f190e32b', 'System', '2026-09-26 18:32:50', '2026-09-26 18:23:18', '2026-09-26 18:22:50'),
(51, 260, 'login_2fa', 'c31123d283226c669da954a62d932c03edf14c194aa7328b1396fbf380fef779', 'System', '2026-09-26 18:43:56', '2026-09-26 18:34:43', '2026-09-26 18:33:56'),
(52, 149, 'login_2fa', 'b8ee37fd8c30fb2b4548ae195286f4ba79654002c7dee1daa13819d97758eb93', 'System', '2026-09-26 18:46:10', '2026-09-26 18:38:21', '2026-09-26 18:36:10'),
(53, 250, 'login_2fa', 'd0fc9dcdf35e2eeeeecd6d22ea3dd3a6ae85a0a36f8b32ace1745aa1e48ac96e', 'System', '2026-09-26 18:55:31', '2026-09-26 18:46:20', '2026-09-26 18:45:31'),
(54, 149, 'login_2fa', 'adbdf811159d0903c32613d762f9853892ae0e46de506a0eeaf3f8e6a54baef7', 'System', '2026-09-26 18:57:13', '2026-09-26 18:48:28', '2026-09-26 18:47:13'),
(55, 149, 'login_2fa', '1e50ff3eef759af97c42ab2f7705e8f665efeff267fdffd639ab2edfa6d8c551', 'System', '2026-09-26 19:41:48', '2026-09-26 19:32:26', '2026-09-26 19:31:48'),
(56, 250, 'login_2fa', '5295ffa484631ff322b105fad06b12ffc696caed3461bd3e9def020d9cdac126', 'System', '2026-09-26 21:58:17', '2026-09-26 21:48:54', '2026-09-26 21:48:17'),
(57, 149, 'login_2fa', '4689469efbdf028bafc124970e6c34716d90cabfe96a90e78a650ff25d7782cc', 'System', '2026-09-26 22:55:29', '2026-09-26 22:46:06', '2026-09-26 22:45:29'),
(58, 151, 'login_2fa', 'b14e9805007cc853e2c9b2b7ae412e39f27073132a19de42f3a50770c091900b', 'System', '2026-09-26 23:01:47', '2026-09-26 22:52:38', '2026-09-26 22:51:47'),
(59, 261, 'login_2fa', '256e54853765118b2496855cf6b4e77a6557bf20cee9ce2a7317d79e5620c67b', 'System', '2026-09-26 23:04:48', '2026-09-26 22:55:22', '2026-09-26 22:54:48'),
(60, 8, 'login_2fa', '5827f1d577fb940fb5353c6f2186f6bd20ef51e38d54fb16ab92db4d49af3141', 'System', '2026-09-26 23:09:39', '2026-09-26 23:00:00', '2026-09-26 22:59:39'),
(61, 151, 'login_2fa', '295077adf63862b9cade71d27ac31ddc461c07e24c8d5b40e58adefe3ec91af1', 'System', '2026-09-26 23:17:09', '2026-09-26 23:07:30', '2026-09-26 23:07:09'),
(62, 262, 'login_2fa', '8e13a346efafd50d978126130b22ae5c262eb2eb5982677b1dfa1a22bf775a11', 'System', '2026-09-26 23:31:59', '2026-09-26 23:22:41', '2026-09-26 23:21:59'),
(63, 151, 'login_2fa', '3f4b9bef67350072d8e5e3c035ac6d1d07c59bc72b4eba0fc9e1c036655d14ff', 'System', '2026-09-26 23:34:01', '2026-09-26 23:24:40', '2026-09-26 23:24:01'),
(64, 250, 'login_2fa', '3670f0c85c82790612ddd5ce2c7584f983079154f89a7996c0eccbf6de0bdca5', 'System', '2026-09-27 00:01:17', '2026-09-26 23:52:14', '2026-09-26 23:51:17'),
(65, 149, 'login_2fa', 'b2eb0a47338ee6ba5c5e6ad32dee2063b11132e78c8b12fbd2f52e4ddc3fea22', 'System', '2026-09-27 00:51:33', '2026-09-27 00:43:29', '2026-09-27 00:41:33'),
(66, 149, 'login_2fa', '93edc8396575fdde7cb9e4cb1b622c6d1ae5a1e3213bdf6a34cf79b3e5817a78', 'System', '2026-09-27 01:00:11', '2026-09-27 00:50:59', '2026-09-27 00:50:11'),
(67, 255, 'login_2fa', '61b63fe68540e65b6dbe44211da53e439634f09504f58c07b72ecfecb25e507a', 'System', '2026-09-27 01:04:08', '2026-09-27 00:55:06', '2026-09-27 00:54:08'),
(68, 256, 'login_2fa', '824856bb44b76a40d481fbe98c5d3588d7859c32082a237f9aed0d0dbb8f40ac', 'System', '2026-09-27 01:08:19', '2026-09-27 00:59:13', '2026-09-27 00:58:19'),
(69, 255, 'login_2fa', '9f0221aff2f0b9c691deb8dd99a5256f28d3b54f6fb3a0eaf2648cf6ae16121a', 'System', '2026-09-27 01:47:11', '2026-09-27 01:38:20', '2026-09-27 01:37:11'),
(70, 255, 'login_2fa', '8e432989ac57bec61152d9399d5b4e1c429f9f198dffaec6c025cba20f1106a6', 'System', '2026-09-27 01:48:20', '2026-09-27 01:51:33', '2026-09-27 01:38:20'),
(71, 149, 'login_2fa', '5e75d11be1a51c0bb0c954d3774906071b8d307a24b34607cbb697dff67de0a4', 'System', '2026-09-27 01:48:56', '2026-09-27 01:40:27', '2026-09-27 01:38:56'),
(72, 149, 'login_2fa', '1379444f5a501223020c68b065fe5f161afcc608b3fcc92dcff3a6d419119570', 'System', '2026-09-27 01:50:27', '2026-09-27 03:21:35', '2026-09-27 01:40:27'),
(73, 255, 'login_2fa', '9707654ec13a773caf478915097ad99023b1252606a7bde6cc05309c9c16e315', 'System', '2026-09-27 02:01:33', '2026-09-27 01:51:58', '2026-09-27 01:51:33'),
(74, 229, 'login_2fa', '2c46a9eb0d717d570088dec6cc0d1906dfb65c5b82c0d518dc971c162f90bd23', 'System', '2026-09-27 02:15:06', '2026-09-27 02:06:47', '2026-09-27 02:05:06'),
(75, 220, 'login_2fa', '5a43e6ba3c3d055455fbe96f41cd83eab458b97710c57ddc2059f8b0856cee7f', 'System', '2026-09-27 02:17:47', '2026-09-27 02:43:14', '2026-09-27 02:07:47'),
(76, 250, 'login_2fa', '2e632aba7298adaf0e379cb25ac9cc91fb42790b360ee60814665292fdc0b182', 'System', '2026-09-27 02:21:41', '2026-09-27 02:11:59', '2026-09-27 02:11:41'),
(77, 220, 'login_2fa', '3e7d34809d593acd2947d8339a0ed9161db631d0a1c91bb0be8c2c913f676520', 'System', '2026-09-27 02:53:14', '2026-09-27 02:43:48', '2026-09-27 02:43:14'),
(78, 234, 'login_2fa', '5927cd2af47841e53190454ab36a391ef2f41cc0d93ac37eb72822f379947be7', 'System', '2026-09-27 03:02:54', '2026-09-27 02:53:54', '2026-09-27 02:52:54'),
(79, 227, 'login_2fa', '0ee51a0f63a3bb8e8d3160cf9d175b666f3a6fb939d9957fed06848e9f8b4eaa', 'System', '2026-09-27 03:04:43', '2026-09-27 02:55:03', '2026-09-27 02:54:43'),
(80, 229, 'login_2fa', 'd19035ccdf52a4af2efcbd78003219e6b21c1166a020c7bacad011cda07b6463', 'System', '2026-09-27 03:05:21', '2026-09-27 02:56:09', '2026-09-27 02:55:21'),
(81, 220, 'login_2fa', '48e439a271865d75b591a9d7dc9abf88fde94fba339d2afb14ebb7bdaee10345', 'System', '2026-09-27 03:06:42', '2026-09-27 02:57:16', '2026-09-27 02:56:42'),
(82, 230, 'login_2fa', '26ae347d187b26ebaf71fe68b3eeba0e1f7c4cc22c3ed4455b79aa5088dda42c', 'System', '2026-09-27 03:10:06', '2026-09-27 03:00:52', '2026-09-27 03:00:06'),
(83, 149, 'login_2fa', '2c764b422194e615e36902be805d7ee0cf4fc25305552ddb703e7fdf1dcfb304', 'System', '2026-09-27 03:31:35', '2026-09-27 03:23:03', '2026-09-27 03:21:35'),
(84, 149, 'login_2fa', 'c3d81c9ff77d90a56df290aee2ce1f8e69024aa0d8e03ead9011bb054e2e0a12', 'System', '2026-09-27 03:36:02', '2026-09-27 03:26:32', '2026-09-27 03:26:02'),
(85, 149, 'login_2fa', 'da726dd0a0b7fa7d9197fcf5554d8414a2b460a4c2531553d41067cf2d5d8ebf', 'System', '2026-09-27 03:44:58', '2026-09-27 03:36:58', '2026-09-27 03:34:58'),
(86, 151, 'login_2fa', '90fe209b535b074a94e3eab6ea8862b71ebc6d6f8026c78865ecc0f0d45b22e4', 'System', '2026-09-27 04:28:09', '2026-09-27 04:18:32', '2026-09-27 04:18:09'),
(87, 262, 'login_2fa', '76712ab60b4a55c95277ed77612d81d8f4b4c3134d998a41c47877b18a8f3e4f', 'System', '2026-09-27 04:35:25', '2026-09-27 04:26:00', '2026-09-27 04:25:25'),
(88, 255, 'login_2fa', 'e3b3fde3f8dfd96259f0c333c7141531c630a632f0fe1eb1dd3bfe5a629e3d35', 'System', '2026-09-27 04:38:59', '2026-09-27 04:29:45', '2026-09-27 04:28:59'),
(89, 256, 'login_2fa', '6d736f72c4a4c041c61c337828b1f58aa00213e6f50ebb7b9c040014c8159ebe', 'System', '2026-09-27 04:48:52', '2026-09-27 04:39:24', '2026-09-27 04:38:52'),
(90, 250, 'login_2fa', 'd148cf76e9cb1368f5e08e3ccd5aca02e2b732ac12097cb044cc225a484840af', 'System', '2026-09-27 04:58:37', '2026-09-27 04:49:13', '2026-09-27 04:48:37'),
(91, 229, 'login_2fa', '862826e2c7526610958b48553d53adffe918bcb7afc7e322d028bdb28f9045f2', 'System', '2026-09-27 05:04:25', '2026-09-27 04:56:18', '2026-09-27 04:54:25'),
(92, 220, 'login_2fa', '4fb838cc00001d6b2cea1dbe9c563c68a161599e027ea410faef63eb88feeb67', 'System', '2026-09-27 05:06:41', '2026-09-27 04:57:42', '2026-09-27 04:56:41'),
(93, 230, 'login_2fa', 'fe8442e960c740b22adda10dbc930fa268e2d0486feccdec0069d599f0c645a9', 'System', '2026-09-27 05:08:25', '2026-09-27 04:58:53', '2026-09-27 04:58:25'),
(94, 234, 'login_2fa', '1ddf1839be68b7d1e1ea8769cd70e52cc6cc7922eafa06abda2af0f1f1efb704', 'System', '2026-09-27 05:09:44', '2026-09-27 05:00:14', '2026-09-27 04:59:44'),
(95, 227, 'login_2fa', '6e2573e7011223cfa658ae1c7d1c4c56b062b09c65062dfb36a456a415e59162', 'System', '2026-09-27 05:10:33', '2026-09-30 18:58:12', '2026-09-27 05:00:33'),
(96, 151, 'login_2fa', 'b8a190b6bea96012b0f0c6c5b51a5026b570bbee066433674c2e55aa5721edd7', 'System', '2026-09-29 09:51:41', '2026-09-29 09:42:06', '2026-09-29 09:41:41'),
(97, 262, 'login_2fa', 'e4537dbc662b5361215c6dbf48ca1b75e30ca4bb9e406f784d84765f745e31a6', 'System', '2026-09-29 10:26:54', '2026-09-29 10:17:28', '2026-09-29 10:16:54'),
(98, 149, 'login_2fa', '0f3daa0a32b549543629c1431811ac7eac84f9a7e36230abf6a0ddb58eafd537', 'System', '2026-09-29 10:28:25', '2026-09-29 10:18:52', '2026-09-29 10:18:25'),
(99, 255, 'login_2fa', 'be80baac46166b5a5267b449123bf94da5dc9dabc478343379130334cc3bb282', 'System', '2026-09-29 10:29:34', '2026-09-29 10:27:53', '2026-09-29 10:19:34'),
(100, 256, 'login_2fa', 'a2e1e6630c9bee9756fc45d5448904b1bf60f9b60eabbe06c3a35f00e676c9b1', 'System', '2026-09-29 10:38:21', '2026-09-29 10:30:23', '2026-09-29 10:28:21'),
(101, 250, 'login_2fa', 'a7fe08438cb95484ada35338313287843a9d26f04a3ae1af73d7ffdaeda80467', 'System', '2026-09-29 10:40:46', '2026-09-29 10:31:04', '2026-09-29 10:30:46'),
(102, 149, 'login_2fa', '179df886ae9d8a8904d6a5d40768a112028101117ef90e698d36f78e6b79689a', 'System', '2026-09-29 10:53:56', '2026-09-29 10:44:56', '2026-09-29 10:43:56'),
(103, 151, 'login_2fa', '5a6f48fe0804a7befa63d5d659cc867ec12ff84ea9c25e11fa44bd94acbebe54', 'System', '2026-09-29 10:56:06', '2026-09-29 10:46:27', '2026-09-29 10:46:06'),
(104, 151, 'login_2fa', '625391ccf7d023c7f797749ad4b16732908830ce01234c2b44d36d0ca31b02b5', 'System', '2026-09-29 15:12:09', '2026-09-29 15:02:45', '2026-09-29 15:02:09'),
(105, 151, 'login_2fa', 'ab518999659d947b1c41e7d67466cb46e645129e6385315a3b641782f4d4f765', 'System', '2026-09-30 17:16:36', '2026-09-30 17:06:57', '2026-09-30 17:06:36'),
(106, 220, 'login_2fa', '03ced2ac974bb43fcccf1f40fbe43614a5a5895114f8a4312180602866f76a91', 'System', '2026-09-30 18:38:49', '2026-09-30 18:29:10', '2026-09-30 18:28:49'),
(107, 229, 'login_2fa', '560020735b5f245756872529379a559d75c954f88c3bb74e4d35393acc4fc395', 'System', '2026-09-30 18:43:35', '2026-09-30 18:33:50', '2026-09-30 18:33:35'),
(108, 220, 'login_2fa', '2d11475f7c32ed51f1a6ce9851586d391cd426bdb748046d5170cd9e9b8841ce', 'System', '2026-09-30 19:00:54', '2026-09-30 18:51:11', '2026-09-30 18:50:54'),
(109, 229, 'login_2fa', '115edfcc8cdfe03d1271824ce5798805bc198256d515f0b020a881efa5e769d2', 'System', '2026-09-30 19:05:54', '2026-09-30 18:56:19', '2026-09-30 18:55:54'),
(110, 230, 'login_2fa', 'da58eade37cb5091ab3a578e19ea8db5343e262bc2982dd346aecf5605677993', 'System', '2026-09-30 19:07:37', '2026-09-30 18:57:57', '2026-09-30 18:57:37'),
(111, 227, 'login_2fa', 'c57c6f85ec0c7e082109ac02c4829846c1a2755e28e2fd134756c046211dd71c', 'System', '2026-09-30 19:08:12', '2026-09-30 18:58:36', '2026-09-30 18:58:12'),
(112, 234, 'login_2fa', '1bf0e7c7fc365d0d149c107202eb745bd2968a7e281d4c5d3e01515cdff1dae1', 'System', '2026-09-30 19:16:16', '2026-09-30 19:06:51', '2026-09-30 19:06:16'),
(113, 151, 'login_2fa', 'e5fdbe4b24f4021e1c7f7bba9b1f6a906fddfcb4ab80cc627f320b1886cd864b', 'System', '2026-09-30 19:21:51', '2026-09-30 19:12:21', '2026-09-30 19:11:51'),
(114, 151, 'login_2fa', 'ea2b98eb025c615965f1152580eb986efa35028fc61c0cf9155fd520b823866f', 'System', '2026-09-30 21:23:16', '2026-09-30 21:13:57', '2026-09-30 21:13:16'),
(115, 151, 'login_2fa', 'bfbe3d3bf6dbe182527cc77bdc259a945339c89b265e3a9206366f7414527542', 'System', '2026-09-30 22:11:54', '2026-09-30 22:02:23', '2026-09-30 22:01:54'),
(116, 151, 'login_2fa', '61ce182781f19acd95a5161a2e87da042a29ae47fef47a3bf94d085cc0866bb3', 'System', '2026-10-02 08:51:40', '2026-10-02 08:42:05', '2026-10-02 08:41:40'),
(117, 151, 'login_2fa', '4d0f52a3c9164b4e1d957727128442da730c0bd94a664fcb670790324769eac7', 'System', '2026-10-02 10:17:28', '2026-10-02 10:07:57', '2026-10-02 10:07:28'),
(118, 151, 'login_2fa', '91b304f11bd650dbf6aa68892490859773b5899004ed58f06101d4d38663293f', 'System', '2026-10-06 15:39:37', '2026-10-06 15:30:01', '2026-10-06 15:29:37'),
(119, 151, 'login_2fa', '1706d0aa267e85fceabf76ee96aa34d2fe81b711f72b0f81406f4a7b516c3439', 'System', '2026-10-06 17:26:46', '2026-10-06 17:17:22', '2026-10-06 17:16:46'),
(120, 151, 'login_2fa', '953870f44aa47a98370b7ce9d03e8f7496c91666a7a32c7f90ed03ea25e571da', 'System', '2026-10-06 18:04:56', '2026-10-06 17:55:15', '2026-10-06 17:54:56'),
(121, 151, 'login_2fa', '1358f4f1974e1e95c682339af13a349c119e5153939bb1ddda03c4e1b709c3f3', 'System', '2026-10-07 20:59:57', '2026-10-07 20:50:19', '2026-10-07 20:49:57'),
(122, 151, 'login_2fa', '42540e7cd0aae8cda63f7cfdf94c928835e490265298a760c0f09151ce3496e4', 'System', '2026-10-08 19:29:56', '2026-10-08 19:20:21', '2026-10-08 19:19:56'),
(123, 151, 'login_2fa', '991872cd2455f2af7d2b26a073f40df4e8e21406d4f78243e0ef70a87d027df1', 'System', '2026-10-08 19:30:26', '2026-10-08 19:20:45', '2026-10-08 19:20:26'),
(124, 262, 'login_2fa', '0e3c52f9a3a10bd5b3be1ba3a02cbb72b43f2ee52511dd493180e136c638007a', 'System', '2026-10-08 19:46:04', '2026-10-08 19:36:42', '2026-10-08 19:36:04'),
(125, 151, 'login_2fa', 'b727121a96e8cff2dc7ceb78ecbd33370da6285e31d0dfb051fbc8da0abae506', 'System', '2026-10-08 20:21:15', '2026-10-08 20:11:36', '2026-10-08 20:11:15'),
(126, 262, 'login_2fa', '977664d5899a5108b5353de95a55be83bc1f6170371c278d00fa79439aaf9402', 'System', '2026-10-08 20:42:56', '2026-10-08 20:33:21', '2026-10-08 20:32:56'),
(127, 151, 'login_2fa', '0ecc996ca1d884a2eadca8db93484064af3133d5a402c73d8dfa2d5c15dc23ca', 'System', '2026-10-09 08:15:29', '2026-10-09 08:06:05', '2026-10-09 08:05:29'),
(128, 262, 'login_2fa', '93c567f7392c87cbc7ae14116b0885b25fd0ac511f2346e29c687211e933a226', 'System', '2026-10-09 08:16:41', '2026-10-09 08:06:58', '2026-10-09 08:06:41'),
(129, 262, 'login_2fa', 'd2b7b1e6d74b3ef37452f17e77c9987bae8bf520f7aa7bd30dcaf2b94af1fdf6', 'System', '2026-10-09 10:28:37', '2026-10-09 10:19:44', '2026-10-09 10:18:37'),
(130, 151, 'login_2fa', 'bdf35151d6f620948ade1e205224b787d898e5dbd3c87668fd9625e05c4782d6', 'System', '2026-10-09 11:10:06', '2026-10-13 16:14:52', '2026-10-09 11:00:06'),
(131, 262, 'login_2fa', '0f5bf20baf7fea907e6049fd58e473648936b72bda236be2b13ad5322f0a1508', 'System', '2026-10-09 11:17:31', '2026-10-09 11:07:59', '2026-10-09 11:07:31'),
(132, 151, 'login_2fa', 'f2487338015332582bc8767e5f0c1b55fb1215866b1e995d025d6313339945a3', 'System', '2026-10-13 16:24:52', '2026-10-13 16:15:54', '2026-10-13 16:14:52'),
(133, 151, 'login_2fa', 'bfdc22161246bbba414abcd19d185eb0f77975c47e6785a9c5fb60ad8205e659', 'System', '2026-10-13 17:10:49', '2026-10-13 17:01:19', '2026-10-13 17:00:49'),
(134, 262, 'login_2fa', '01767207f54fe2a76396bca82070b4fd0105d01c7988308a09751b9c6bd3bbb7', 'System', '2026-10-13 18:24:11', '2026-10-13 18:14:59', '2026-10-13 18:14:11'),
(135, 262, 'login_2fa', '8cd9ea8e7bd5ff510c6070c70026046acf07d5f0049659f825add70a5771d74c', 'System', '2026-10-14 23:31:43', '2026-10-14 23:22:03', '2026-10-14 23:21:43'),
(136, 151, 'login_2fa', 'c51fd32c5721f89cd5e8b92c5e2fddec43176a5fabfdfda2955cbbf073e01366', 'System', '2026-10-14 23:44:44', '2026-10-14 23:35:13', '2026-10-14 23:34:44'),
(137, 262, 'login_2fa', '32a3267b79c3339ce9f2ceca51924310a10d2e35d771f5e3386037e365c526fb', 'System', '2026-10-15 23:22:53', '2026-10-15 23:13:40', '2026-10-15 23:12:53'),
(138, 262, 'login_2fa', '205083d0de3ee6660cbc179926393b59dacfda9c7f172b70ea2df58197027b1b', 'System', '2026-10-16 00:37:49', '2026-10-16 00:28:48', '2026-10-16 00:27:49'),
(139, 262, 'login_2fa', '7cdae562ed2fd2634fc374a4c21813026c15983b1b1048b1005da36874862167', 'System', '2026-10-16 12:56:10', '2026-10-16 12:47:01', '2026-10-16 12:46:10'),
(140, 262, 'login_2fa', 'adb0b0e559d19a9dd72bf40a67dc8d16f19908e69a0d442b4d77bde3ad8fab8e', 'System', '2026-10-16 14:06:33', '2026-10-16 13:57:20', '2026-10-16 13:56:33'),
(141, 262, 'login_2fa', 'bb358732d5e173184fb109c040b867f0ee0a2c77c317a37b274ce91bf397b356', 'System', '2026-10-24 00:40:41', '2026-10-24 00:30:58', '2026-10-24 00:30:41'),
(142, 262, 'login_2fa', '77bf6b1f899a2107a5dfd3f1202d6982bca12d330b6de588b2e1b21308aea14b', 'System', '2026-10-24 00:52:14', '2026-10-24 00:45:32', '2026-10-24 00:42:14'),
(143, 262, 'login_2fa', 'a27646bfd7c0a38f5bd57b1ef47b5b3b57f0a721f8f8ccfd834d564d75e11363', 'System', '2026-10-24 00:55:32', '2026-10-24 00:46:07', '2026-10-24 00:45:32'),
(144, 262, 'login_2fa', '35df23522bf509d42e3bf7d42329bfd982cccbad73fd36672c4abdd3f36b5402', 'System', '2026-10-29 00:39:21', '2026-10-29 00:29:55', '2026-10-29 00:29:21'),
(145, 262, 'login_2fa', '637c6d6e79630092c0ec60937d09a9f8549f1455300e3295077d6f23bd1fa5d5', 'System', '2026-10-29 00:46:41', '2026-10-29 00:37:06', '2026-10-29 00:36:41'),
(146, 262, 'login_2fa', 'b16908ed38ee93f8957557ea8e9daae47e8b2e32c9e6dfad3893e4c8b74ee21c', 'System', '2026-10-29 00:55:41', '2026-10-29 00:45:59', '2026-10-29 00:45:41'),
(147, 149, 'login_2fa', 'a6d6b015dd6c5ccf37ea908fd03c7e7af60e0abd2fdf01198c7142f89b37b6cd', 'System', '2026-10-29 01:39:58', '2026-10-29 01:30:42', '2026-10-29 01:29:58'),
(148, 151, 'login_2fa', '5c9ce6cc79572724923e49689a4a39a5079074b7395002bb4ae038a2273b61bc', 'System', '2026-10-29 20:10:38', '2026-10-29 20:01:05', '2026-10-29 20:00:38'),
(149, 151, 'login_2fa', '006ce043199534f77f1058becbb6602455494c3eaefc4a6c88dc2e0fa5e72aca', 'System', '2026-10-29 21:34:57', '2026-10-29 21:25:56', '2026-10-29 21:24:57'),
(150, 151, 'login_2fa', '38e322b9262815a87714b1e843f848d57d1d78265c5080b08c91e7e9a825be3f', 'System', '2026-10-30 22:07:08', '2026-10-30 21:57:32', '2026-10-30 21:57:08'),
(151, 262, 'login_2fa', '52b42804acc2ee1198cc37fb1ce86084ba271163a675d52cb488da34ea745f9c', 'System', '2026-10-30 22:18:03', '2026-10-30 22:09:11', '2026-10-30 22:08:03'),
(152, 250, 'login_2fa', '7ed483892d4390bc1625f1bca7f86c6cfa16251bf32d8acdec76c7e7d56d3fea', 'System', '2026-10-30 22:23:57', '2026-10-30 22:14:24', '2026-10-30 22:13:57'),
(153, 256, 'login_2fa', 'e0d99aff3edf516b4ea14e0dda42f66209e446e2135898878d5b86946eaf033f', 'System', '2026-10-30 22:25:02', '2026-10-30 22:15:28', '2026-10-30 22:15:02'),
(154, 255, 'login_2fa', 'ce9d8ab4144338d6b3e203a37576d4517a6f7b6340ad2f608f7765a70dea488b', 'System', '2026-10-30 22:25:59', '2026-10-30 22:16:14', '2026-10-30 22:15:59'),
(155, 262, 'login_2fa', 'e71a83b5f928aaa90818b6e93ad97e3e874fc558f0a237be7da651af97e2bd65', 'System', '2026-10-30 22:27:05', '2026-10-30 22:18:02', '2026-10-30 22:17:05'),
(156, 149, 'login_2fa', '56045f5c1cb95ac8b3e0a2912daa5590d5b1240ff8876d01fea114ff833aeadb', 'System', '2026-10-30 22:30:36', '2026-10-30 22:22:10', '2026-10-30 22:20:36'),
(157, 149, 'login_2fa', 'b840e152bcc1ec5727b8c4842b7503c33fa68a43135ed94e0b54b7daea0f6893', 'System', '2026-10-30 22:27:10', '2026-10-30 22:22:42', '2026-10-30 22:22:10'),
(158, 149, 'login_2fa', '1289186fee7542176eab0083cc3ae859bac3c08d236700f554345db942801769', 'System', '2026-10-30 22:41:01', '2026-10-30 22:31:55', '2026-10-30 22:31:01'),
(159, 149, 'login_2fa', '8b3db697dde40dc1aaab80c7dc86d1c2fbf1dd4a23919b0a122731132d6fea9a', 'System', '2026-10-30 23:44:20', '2026-10-30 23:34:40', '2026-10-30 23:34:20'),
(160, 149, 'login_2fa', '12f77e2249ab5a708c1d6dea8dff0542bd661639d4f0c7fe709330a92554904c', 'System', '2026-10-31 00:21:15', '2026-10-31 00:11:35', '2026-10-31 00:11:15'),
(161, 262, 'login_2fa', 'cbe877e775e660283d6ee02e358d6092298e6f8cb223d7c5e27d53e66529d300', 'System', '2026-11-03 01:41:10', '2026-11-03 01:31:42', '2026-11-03 01:31:10'),
(162, 151, 'login_2fa', '3cf8cdd66f58f3ba5212f6066b0bd2ce91e3d4a452ceea758370a4282c64657f', 'System', '2026-11-03 01:46:41', '2026-11-03 01:37:18', '2026-11-03 01:36:41'),
(163, 262, 'login_2fa', '15666610972f8da21ac502133482f639f17b0fdacac292f218e3524f53db693c', 'System', '2026-11-03 01:55:19', '2026-11-03 01:45:46', '2026-11-03 01:45:19'),
(164, 149, 'login_2fa', '3b1d7bd7224610aef5aae8b73eb51dc69481e9fba334dc79ca0ab95df9a08815', 'System', '2026-11-03 18:06:42', '2026-11-03 17:58:45', '2026-11-03 17:56:42'),
(165, 151, 'login_2fa', '52c26e0c15a816e5605678a85e987e2319aa95cf16b70047913e387e57fdc53e', 'System', '2026-11-03 18:12:43', '2026-11-03 18:03:07', '2026-11-03 18:02:43'),
(166, 151, 'login_2fa', '640bb64a01e59944ece19571ddc5a859e20fc0f4d2a65abbeecaec8b8abd234f', 'System', '2026-11-03 18:14:15', '2026-11-03 18:04:38', '2026-11-03 18:04:15'),
(167, 262, 'login_2fa', '328e600853159319a4013c40942a3a8ae7d068674027ee08dda1fb7e55795183', 'System', '2026-11-03 18:23:56', '2026-11-03 18:14:41', '2026-11-03 18:13:56'),
(168, 149, 'login_2fa', 'f2e1d2bc86180706e61ba1221b03ff914b74178e23e36f6e9154f4556e4d3f41', 'System', '2026-11-03 18:29:45', '2026-11-03 18:20:05', '2026-11-03 18:19:45');

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
(149, 'claudeespejo@gmail.com', 'claudeespejo@gmail.com', '$2y$10$44W90NnYOZQx3h.8uDXjNeiqo9fjiiFHLyhGJjB5GAsFqLlbNgSci', 'Jean Espejo', 'department_head', NULL, 'active', 0, 0, NULL, '2026-10-30 23:33:42', '2026-11-03 18:20:05', '2026-11-03 18:57:50', '::1', NULL, '2026-08-14 11:58:31', '2026-11-03 18:57:50'),
(151, 'facultyadmin', 'gitirene6@gmail.com', '$2y$10$OB99jFDNiuB2z5ag2LTfj.npSx2gzctwJ3hNtrzFXc59JDk7TsBgW', 'Faculty Admin', 'faculty_admin', NULL, 'active', 0, 0, NULL, '2026-09-26 17:05:07', '2026-11-03 18:04:38', NULL, '::1', NULL, '2026-08-15 01:14:06', '2026-11-03 18:13:43'),
(152, 'monitoring', 'monitoring@bestlink.edu.ph', '$2y$10$VA/SJ7ZDJfaphs4elb5O7O1FxgCV2gakN7tMqG49T0UUf83OJmdO.', 'Monitoring Officer', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-15 01:41:50', '2026-08-17 00:45:17', NULL, '::1', NULL, '2026-08-15 01:14:06', '2026-08-17 01:32:54'),
(158, 'asdfghjkl@gmail.com', 'asdfghjkl@gmail.com', '$2y$10$krRJZwchT6S4kL9sEoiOy.mp4XhL1THTugqcDFJk5wOtOCNWFOxB2', 'dwadsadwa Qwerty', 'secretary', NULL, 'active', 0, 0, NULL, '2026-08-17 00:22:48', '2026-09-24 01:11:06', NULL, '::1', NULL, '2026-08-17 00:21:59', '2026-09-24 01:11:21'),
(159, 'jeanie@gmail.com', 'jeanie@gmail.com', '$2y$10$XT67xyI5B6ebSjLVKaXwoOHmmGsD79vo6GJX11HYYlBiPqgJr9MM6', 'Claude Claude', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-17 01:42:24', '2026-09-26 15:03:21', NULL, '::1', NULL, '2026-08-17 01:40:22', '2026-09-26 15:05:33'),
(219, 'registrar_head', 'registrar.head@bestlink.edu.ph', '$2y$10$PLACEHOLDER', 'Registrar Head Name', 'registrar', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-06 21:51:08'),
(220, 'registrar_officer_01', 'jr.aban23@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Registrar Officer Name', 'registrar_clearance', NULL, 'active', 0, 0, NULL, NULL, '2026-09-30 18:51:11', NULL, '::1', NULL, '2026-09-06 21:51:08', '2026-09-30 18:55:40'),
(221, 'hr_manager', 'hr.manager@bestlink.edu.ph', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'HR Manager Name', 'hr', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-27 01:25:45'),
(222, 'hr_officer_01', 'hr.officer01@bestlink.edu.ph', '$2y$10$PLACEHOLDER', 'HR Officer Name', 'hr', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-06 21:51:08', '2026-09-06 21:51:08'),
(227, 'hr', 'brianbrix002@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'HR User', 'hr_clearance', NULL, 'active', 0, 0, NULL, NULL, '2026-09-30 18:58:36', NULL, '::1', NULL, '2026-09-06 22:41:32', '2026-09-30 18:58:41'),
(229, 'property_custodian', 'espejo.jeanclaude.obsania002@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Property Custodian User', 'property_custodian_office', NULL, 'active', 0, 0, NULL, NULL, '2026-09-30 18:56:19', NULL, '::1', NULL, '2026-09-06 22:41:32', '2026-09-30 18:56:25'),
(230, 'library', 'diosdado.rocero.aban@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Library User', 'library_clearance', NULL, 'active', 0, 0, NULL, '2026-09-27 02:59:50', '2026-09-30 18:57:57', NULL, '::1', NULL, '2026-09-06 22:41:32', '2026-09-30 19:06:05'),
(234, 'finance_office', 'lonzbert12@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Finance Office User', 'finance_office', NULL, 'active', 0, 0, NULL, NULL, '2026-09-30 19:06:51', NULL, '::1', NULL, '2026-09-07 20:10:23', '2026-09-30 19:11:39'),
(250, 'whitybrix@gmail.com', 'whitybrix@gmail.com', '$2y$10$e2ljqEJnXNpnkmz12ScPlOkoWj0KwF6xem4.rUXIB1kcrf15GMJtC', 'Light Yagami', 'faculty', NULL, 'inactive', 0, 0, NULL, '2026-09-27 00:39:37', '2026-10-30 22:14:24', NULL, '::1', NULL, '2026-09-11 02:12:42', '2026-11-03 18:03:38'),
(255, 'bardpanget2025@gmail.com', 'bardpanget2025@gmail.com', '$2y$10$KpZ4LECpPi8Bh8xe5yvBwuqTPbQw7Ik6eJUr.ye4hljbWsUmTVhwG', 'Earl Salvame', 'secretary', NULL, 'active', 0, 0, NULL, '2026-09-27 00:55:32', '2026-10-30 22:16:14', NULL, '::1', NULL, '2026-09-24 08:34:28', '2026-10-30 22:16:54'),
(256, 'mingthecat002@gmail.com', 'mingthecat002@gmail.com', '$2y$10$qMtpnrJLl7c3oyO/CPaTduob/8vIW9m3czkVyA46Ru8Mx/VWp7Uc6', 'Alfred Joseph Alcantara', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-09-27 00:59:30', '2026-10-30 22:15:28', NULL, '::1', NULL, '2026-09-24 08:45:01', '2026-10-30 22:15:47'),
(260, 'midnightgaming099@gmail.com', 'midnightgaming099@gmail.com', '$2y$10$H6ZLXcmoILKClSelab7PkO4mNqAZreyQ6s2aMFzIPYqkOq8rYpkNy', 'Jorge Lucero', 'department_head', NULL, 'active', 0, 0, NULL, '2026-09-26 18:34:59', '2026-09-26 18:34:43', NULL, '::1', NULL, '2026-09-26 18:30:51', '2026-09-26 18:35:47'),
(261, 'idkwhoistonio@gmail.com', 'idkwhoistonio@gmail.com', '$2y$10$3fdBTxfIX3Jd6Pz15mScCeGjQnM/Y8PCDj.JSB/1s7RkSIim89dVO', 'Sofia Reyes', 'faculty', NULL, 'active', 0, 0, NULL, '2026-09-26 22:55:36', '2026-09-26 22:55:22', NULL, '::1', NULL, '2026-09-26 22:49:46', '2026-10-08 22:34:48'),
(262, 'jcespejo002@gmail.com', 'jcespejo002@gmail.com', '$2y$10$hP/Fy/lidjvyu2I4JIdAwurgbwQmceeUWunbBviCZeqUxWYViOoOG', 'Jorge Lucero', 'dean', NULL, 'active', 0, 0, NULL, '2026-10-29 00:46:43', '2026-11-03 18:14:41', NULL, '::1', NULL, '2026-09-26 23:21:19', '2026-11-03 18:19:16'),
(263, 'leriqwe27@gmail.com', 'leriqwe27@gmail.com', '$2y$10$0upP25pfXjnHwaqRuXTT7.6m5hvG4VjxZzpRFS9PJ4njDZ3FLKOrm', '', 'department_head', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-27 04:24:13', '2026-09-29 16:21:11');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2207;

--
-- AUTO_INCREMENT for table `login_throttles`
--
ALTER TABLE `login_throttles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
--

-- ------------FACULTY SYSTEM DATABASE


-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2026 at 12:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = NO_AUTO_VALUE_ON_ZERO;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `faculty_db`
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
(12, 101, 1, '2026-09-22', NULL, NULL, 'Present', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAW4AAAECCAYAAADelD2uAAAQAElEQVR4AeydzY4dSVbHI8Ld0D1Cotkgwcq2YIM0PAB2o2bJjv1Q5gV4h+l5AyR4Ahezni0rpkV3zYoVCFYtlzdISAgx4kPT02NXEv9bfXzjpvPeyu+MiPyV7qmIzIyMOOd3Iv43Kst1HRxfEIAABCBQFAGEu6h04SwEIAAB5xBuZgEEIJAHAbzoTQDh7o2KhhCAAATyIIBw55EHvIAABCDQmwDC3RsVDSEwhgD3QGB+Agj3/EzpEQIQgMCiBBDuRfHSOQQgAIH5CSDc8zPdQ4/ECAEIbEgA4d4QPkNDAAIQGEMA4R5DjXsgAAEIbEgA4U7gU4UABCBQAgGEu4Qs4SMEIACBhADCncCgCgEIQCAPApe9QLgv8+EqBCAAgewIINzZpQSHIAABCFwmgHBf5sNVCEBgPgL0NBMBhHsmkHQDAQhAYC0CCPdapBkHAhCAwEwEEO6ZQNLNfgkQOQTWJoBwr02c8SAAAQhMJIBwTwTI7RCAAATWJoBwr028lPHwEwIQyJYAwp1tanAMAhCAQDcBhLubC2chAAEIZEtgZ8KdbR5wDAIQgEBvAgh3b1Q0hAAEIJAHAYQ7jzzgBQQgsDMCU8JFuKfQ414IQAACGxBAuDeAzpAQgAAEphBAuKfQ414IQOCUAEerEEC4V8HMIBCAAATmI4Bwz8eSniAAAQisQgDhXgUzg5RNAO8hkBcBhDuvfOANBCAAgQcJINwPIqIBBCAAgbwIINx55WNNbxgLAhAolADCXWjicBsCENgvAYR7v7kncghAoFAC1Ql3oXnAbQhAAAK9CSDcvVHREAIQgEAeBBDuPPKAFxCAQHUElgsI4V6OLT1DAAIQWIQAwr0IVjqFAAQgsBwBhHs5tvQMgRoJEFMGBBDuDJKACxCAAASGEEC4h9CiLQQgAIEMCCDcGSQBF7YngAcQKIkAwl1StvAVAhCAQCSAcEcIvCAAAQiURADhLilbQ32lPQQgUCWBXQv345urt09vXjRT7cnN1Z2syhlCUBCAQHYEdiXcEmqZCXVwPsyREe+8l1m/aSlBl80xDn1AAAIQEIFZhEsdrWfDRzIhDc4H2fAext8hQZfJBwR8PEfuhAAEjgTCsVpfzXbX5yK7c83dq2cv/RymvhrXNOfG0nkJuHxSHYMABCAwlkC1wq3dbYg77BSMxFVmQv362fWj9PqUuvq6fXYdrO+0fEjQp4zLvRCAwHYEtho5bDXwkuNKtLW7tTEknBJSiavMzq9VStBtrNB6M7HzlBCAAAT6Egh9G5bQTo8h9Cw5FW3tsFPh3CoOvXnY2PLT6pQQgAAEhhKoQrglhBLs0NrNSrS32GF3JSE+/I6vriucg0BmBHAnewIhew8vOHhOsLW7tUcjF27f8hIiviV9xoZA4QSKFW49xw6tHbZyoV12Do9G5EvLEOsWEA4hAIFxBMK427a9S6KdPseWNxLszHfZchPbjAADQ6AeAkUKdyraCHY9k5FIIACBfgSKE27tti00PcvO5ZeP5lOf0jvHY5M+oGgDAQh0EihOuL3z3iLJ9Fm2uXdSRqeXEOuTMTiAAAT2QaAo4W7vtktKUXyT+dD89c5/YHVKCEAAAkMJFCXcUfC8BRiFsCjfzW9KCEAAAlMJZCl+XUGVvNu2eOIz+TdWf3Lz4lurU0IAAhAYQqAI4ZZo+++ebUfxa8rdbXuecw+ZnTts+/jm6q3mu/4SOGeTj2byWbbDdG0WchHCbaItSuWKtrzHIHAkILGT+KUCHZwP6Xw/ts6rJh/N5LMsjUNxmSlOWV4R9PUmz3YhT7eOXin5dqTdttUpIVASAQmX5nIqbqEQkR7D2URdpeKUpbGLhUxcxvS/93tCzgCUWCVePkq02W2LBJYzAQmRTHM3FarQQ6Q1x+0PyvRXwKWYfJbvZn3yo3UtE5eUk7jJxLBPP3ttE3IOXIk1/+oT7cv/W47FTZkvAYmLLBWeEAVals7drggkchK8VJw1x1f6g7Iul0afk8/y3SyNSXXFqXjNLg0kbjIxTLki5qfUwulhPkdKlHmjhFu97BKxLjl/50T6oZg0fyVeEjEziZwE76F7a7iuOBWvmTFQKS7iI7sUayrmqTZcuqfma1kKtxaIEiXwSqgSrjoGgbUJaC7azi/E3fRD40uIZBIlM81fiddD9+7xuriIj8x4qRRDrX1Zm4u0QTmRgMva1/dwHHIM0jsXX+7w1fC5HgcOfHuYwFwt+oi1BEXiIpPQmEmIZHP5std+xFBiLjO2Yp7y8M57mYl4eq32enbCrXdQJUPglSglUHUMAksS6CPWqUhLUDQ3ZUv6Rd9HAmIuEZcuyI5XnJNmmIBLQ1zlX9kJtxJgzJUoq1dY3lUYUzEhSahlWuyy4Hzocj4Va0S6i9D656QLMhPx1APph0w5lYDL0uu11Dsn61bBpZDb76hb+TTzuMPFemYH9txdW6gDYl38dDAB1xtsWzMk4LIaRTzkkjmJtiDLHyVACVEd2w8BLTCZ5sKcUatPWTgj1BpLC187OBk7axEpy5QzaYbyJ/2QpRFIW2SaB5pfMr2Rp21KqodcnBVU80UJsDrlPghoQVmkmgu/9w8/+AM7HltqYab9pv1IqGVa6DIt/PQ69XIJSD9kyqsEXJZGo/klC/GNXPNDJiFP2+ReD9s4eDpqCq0N+bRlRUfeva0omkmhaOGkHWgOfP3HP/7X9NzQuuZUiAszva8t1Ih1SqfOugRcdk7ELWoJueaMHedehtwcFOTcfJrNH8T6PZSpaEuwtcCmzgEtQC1EG8z6RaiNyD5LzSuZ5phM80JmNDRn9FOaHedcZifcOcPCt3kJSGCtRy0gLSo7HlvqjUAL0O7XLnuOfq0/ynoIaF7INEcsqtD6Kc3O51aGHBxKF1oO/uDDOgQs73OItnZKEu3Ucy1IdtkpEepdBDRHNAftWnse2fmcyiyEOycg+LIOgdPdtmumjKq+QrJT0iLUj8JakFP65d79ENDOO41Wcyo9zq0ecnJICy4nf/BlGQJaFOlue4rAandkfclbzaH2ItT53RkBDyagN3u7SXNKP8XZcW5lVsKdGxz8WYaAFoX1PEVk2wtLj0am9Gc+Ue6XgOaQRR+Sn+LsXC5l2NqRdPHFn5fja2uPGH9JAtptW//aHVt9aqkFN2XnPnV87q+DgOZQOi/T+ZpThJsLd04wFvelcd+zMbxz7/7HdztXe6lF4J33ilOLY/juWHdiEFiWQDovfZyvmrfLjji8d4R7OLNZ7nj1R9efzNJRQZ1oEZi76eKwc5QQyIVA+3l3buKNcK84U0y4tNtccdgshkon/h7jzyIJODGIQM7ivblwB3f8OE3V0wU+iHLmjdO4vHf/nbhbfVWxp29a7LarT3k1AbbFO/2d3JZBhi0H74KgBT7HBwxtGddDY+/pMYlyrJwaE0TbSFCWQkC/+DZfgztuNO3cFmXYYlAb0zsXX24XX955v4tAW0EGd5zoSz4iiXDjqzU4hxCYgUD7X5poMzJDt5O6CJPuvnDz2Eta3FM/GW7s2Gvcp/jWGCeHMfSIxPxQ3Evutn18Y8xhQVm8lHURaNzxr3tDshnZKsqw1cDpX7xpUetZkmzJxb1GrE+/fPF5jO32yVdXP7Xxnt5c/YfV91oukdf2TmivbIl7eQKaa8uP0n+E1YVbu6IobPEN7OhkPIiv43Gptcdf/vlnLrgfRv8fe+8/e/LVi/+K9ZOXd+4/T05UfODjLljh6Y1Z5RIWJ0583ff8wdu779/X+A6BlMA89fRZd/rT5Dy9D+tlVeFWsMEdn3nKVS3q3N7N5NcYe/3p337RNM3f2b3eu0/ibvsnjfPf2rm0bucoxxP44Lc/+Z7mkKzmR2zjCXHnXARSndKmRJvQufoe2k8YesPY9tplK1i7XwuthkcjFo+Vt8+v/7Rp3L/bsXP+z7xzv+t29qU3aQu5cS6+7Gje8uvf/+tf6jGMbN6e6Q0C7xNId93vX13vzOLCrXcliXYakoKveaF513ydxntab37n9Li+I4m2Tx6TpDuV+qLdLCIG3jGBRYVbgh3c6aMR7bJrX8ivnl9/Gnfd33TNKwna059d/bzrWi3nFKPFUvMbtMVICYG1CYQlBjy3y5ZoLzFejn3ePn/58dl4G/+bOfo8h0/abVs/ehxmdUoIQGA+ArMLtxZu2OEuuysl+onDzseHvHdWr7XUG7bttiXaY3bbtbIhLgjMSSDM2ZmEyhau+tXiPbvrVIOK7enN1S/fhdc0b26fvXzU+Ob/7NyTn139r9VrKb1z8eUOX/GNKr4OVb5BAAIzE5hFuLXTkminvtX+C8g01q560/h3O+z4zPvDrjY1ndNPWt55r5j0hl377zEUJ7Y/AnGCx9f2cU8Wbi3Y4Pb3C8iHUue9+0htmsZ941Sp3Px3oq0wb59dB5UYBGomsOXmZNICk2inC1Y7rb0+Gkkn6NOvrv7Hjr1r/tHqrnGPOuvvTpZZ0TwwzzUHrE4JgZoI6MmC6d3W83y0cOvRiAWh5Oz90YgYvDPvf8Pq8THJp1avsZRo2zzQZGa3XWOW9x2TBFt6F9zxyUL8BU58bccljBlaQTh3vFOiveWPDUdPtq/FX0r+xLyIj0mq/vfaitNEW3VEWxSwWghI52QhEWzFpg3K1noX5MgQUyDWXgHo0cjWQZg/OZRRrP/t6Efz42P9UPv1w/dKvmm3baFoLlg9p1K7pdTPnHzDlzwJaM6kOpd6qU1qDhuUQcKdBqOFmkMAKdSt6/pIV+/dX5gf8Weps3/6Htl9bO1KLW23netc0AIMzgf5qXqpnPH7YQJTW2h+SN9kmjNpfxJrbVBluWxSQ+rgpXq6a8l1oV7yf8o1JTWNv6uv40e63j/fjqL9+vXz679K20pAdCx+Kku2lEeMNb5Kjgbf90hA61pCLQvxDb7NQOs0J7FO/QvpwaW6iY7axN1i7/vUvmRTckNMquJX/Vws+khXd+d+FK+/bprmi9tnL5/EepUvibZ4KDhN7lx2IfInNe9cfDm+IHBCQOv4nFir4Z1r7iTYtxn/s9YgRx8yBWpttFCtTnlK4NWnLz+PCX9y+/z6T06vOCexS879KqkXV/XOe3M658ltPhZb4visBCTWsuB8aHdsYh3Xr891I5L6/F4A6cWuevyZOL66rtR5LipUfM0bWxS7Yn9Jmb4B5f4m7pM3mHkzSG8lEdDGU4Ld9rk0sU79Hyzc6c3U+xMwEcld7B6KyOJQu/gGlO38Sd9g5Cu2TwIS7NDaYZtgl7CzPpe1cO4C5+8JpEJ1f2a/31MxHP8GtF9+RL4ega5ddg2CbQQRbiPRUbaFauw7dNqPc/6NK/QrfRPLebctvKmvOsbqJyCxlnXtskt5dt03Swj3BVLp4o8P9uPrQuMLl9J+bp+9/LULTbO9lL755L7bTn3NFiiOTSYgkZZJqGUhPhKRpR3bLjs9V0M91BDEEjEMXPxnXYgT690OO3fBOxtEvHD6r14CJgAADLxJREFU5sOn/0UkvBYmENfOW61DiXKXhQ6hTl2qbZedxhbSA+r3BDRZUqG6Pzv9e9yy303vZf0exMNGLeHNZ4ncWfyUyxDoEukQhXlILrW7lkmwZct4mkevIQ838vKia7KMfb6dV2TjvEl53Gb8Rwnjoqv7ri5B7Nq9bn0uDBRpZU0iLZNIy7RGZbpWu4VLAe7tmia5JnA77om7TJ/0l9aT0/lWS9ttl+bv3Jm3Oax5LAsjBHFun8b0pzWXirKEuW0SadmY/ku/J5QewFz+2yTv6o9d5j2V3DlItP13f3SjhR8fTcXXve97+K74QxTq0mJVrtoirbm2V1Huk7/Qp1EKMBQ4MR6KURM+baNJZMeaVFanzJuAiba81ML3zsWXq/7LdtneeZ8Gq7mrudzeqeZ2rFylGpPGcKxTSwmE9OBSXZPArmuiWL30UqLtkwmvSc0kOmY1ZXM8m19NeTSvbK6mvpeWU60xxaSfBB+y4E4/e8PEGkG0GVFfGfqGpElgbUNrotj5Est0cUu0FYMWjUpZ/Fk7vlQbZ7WwMjEcR2HZuyRwlkf5qbmqczaqzlk9x1LzTZYKtOaNxdTXZ8WpOVzam1Tf+Gh3JBCO1YdrmhjWShPN6qWWWijmexqbnZtatvtnQU0l2n1/KnAS7XarrnPtNmsea+3Int68aDRHQtwIyab4IMHOLc4p8XDvZQLh8uXTq+nECHGynV4t60gLxjyWaKex2fkpZXvHN3f/U3zre6/EpW/brdq1OZsfJubKrZ3boowM38jkp+acTGtHds4f+WyPOyTIfexcX5yvk0AYGpYmld2jSagJace5l3EBvZXJb/NV8cwtqmKSCsfc/Zvva5bxeVF8rTni8LGMs/jb3dHp+LKj+csnNy++1Xw6Z8H5RzKbD10eSKRlJtCKg5/OukhxzggEq/QtNakkdtZeE1ILRWbnciof31y9tUUVnA8y809xKB47nqPUeGJifc3dv/Wbc7mmbynrmOdfRGvSczHfXueWMu/ch0PjlUjLTKgl0rKh/dB+vwTCmNAlRhI9u9c772W2OCTiEjC7vmapcTW++RLc6W/czRf5rzjseGppY6bjaYyp/XL/eQKH/+fz9PJHp4eHI3/4vsE35T8K9FuZibRKibRsA5cYshICYWwcEj1NQk3Odh8+CnmIginxlKC1r085ljCbqW+ZxjHTuBq/a4y4gO5k8lv+d7Vpn/POxZe7+CUfvPM+bSQufcdI76Pen4AP/u8vt270mER2udmEq7HzX2k+dZnyHwX6A9mEIbgVAu8RCO+dGXhCk1OTVoIosWrf7qOgmajOUQZ3/7hDpfqWtcdMj+WX/JPFBfRIll5/qK7+D37fvDj8C4CuutpYPzaeuNg5ynUJaB4q36+eXYdXz17KfCwXsdtCP6Z33Yww2twEwlwdShBv7xfKYYFo8czVd99+NKYJpy1U+dX3fmune9SXHfctdY/u7duedtMIpG+Y1pPyr3lox5QQqJHAbMLdhqPFI/GUmMna14ceqw+ZFqaZ+k9NY84lnOpL4/X1U211T9/2tJufgObCXPmf3zt6hMBsBNxiwm0uSsxkWlRTTH3ItDDNbIylSo3X12e1XcoP+j0loN9x6JGVndWbpvJkx5QQqJ3A4sJdO0DiW4+ACXaIv+dIR+VNM6VBfQ8Ewh6CJMZyCUis9a92tMMOLcFWVNptq8TKJ0AE/QmE/k1pCYHlCUioJdJmIYp11y8hzRN220aCck8Ewp6CJdbxBLxz8eUW+zLBDlGoLw2iX0zbdXbbRoJybwTC3gIm3nEEvPOHPx3XYwvZuF7u75JIy9SPTLvr0CHYEmYJtX7xaOadiy+X9dfTL198Ljs4yTcILEAgLNAnXVZCQP96R+KZhuOd9zKJ7VgLzh8+M0b9yNL+VTex1mMQ+aBzZml7XbfzuZSRya0L7ocHy8Up/KiOQKguIgKalYDEUbtdCbhs1s5bnZlgt8XammmXbvWlfbFxhpRPvrr6aWz/OJpzd+5Hh5JvEFiAAMK9ANQau5SAyyTid665Gyucuu/ONe8+M0b9mZ0TbOPpnYsvd/iSL4dKpt9effry80xdw60KCCDcFSRx7RAksBJOE9whpe7T/bKhfnvnve5p3OHDo1TNyrz3n8mhpmm+UIlBYCkCCPdSZOl3VgLpY5JZO6YzCBRIAOE+Jo1aIQQa5+IrL2fj8+2/STz6l6ROFQKzE0C4Z0dKhxCAAASWJYBwL8uX3iEAAQgMJ/DAHQj3A4C4DIF+BPwP+rWjFQSmE0C4pzOkBwjo37t8Yhhun1//pdUpIbAEAYR7Car0uSsCT7568QsLuGnczx1fZwhwei4CCPdcJOlntwS8dx9Z8LfPX/6W1SkhsBQBhHspsvRbPYGnN1f/9PTmxbt/mhh3299UHzQBZkEA4c4iDTixNoHHN1dvJLpTzDn/feecO3w1zZu42/74UOcbBBYmgHAvDJju8yOgj5INzj+az7Pmn189v/5wvv7oCQKXCYTLl7kKgXoI2C7b69+AzBTW/ee0XP/hTN3RDQR6EUC4e2HaX6PaItYjkdDaZd+55u298L70Y8vaOBFPGQRCGW7iJQTGE5Bop3fr0wUl1K+fXX+QnqcOgVIIINylZAo/RxFoi7YEWx8tO6ozboJAJgT2JdyZQMeNdQikom277HVGZhQILEsA4V6WL71vRKAt2uyyN0oEwy5CAOFeBCudbkkA0d6SPmP3JDCpGcI9CR8350YA0c4tI/izBAGEewmq9Lkqgcc3V29liPaq2BlsQwII94bwGXoaAQm1LDgfZNabfhHJM22jsW7JaOsQCOsMwygQmI9AiEItwe7qEdHuosK52giE2gIinv0RuHPNnYx/o72/3O81YoR7r5kvLO7Xz64faTeduq1jibWuydJrs9bpDAKZEUC4M0sI7pwnoOfWEmozHZ9vzRUI1EsA4a43t0QGAQhUSgDhrjSxD4dFCwhAoFQCCHepmcNvCEBgtwQQ7t2mnsAhAIFSCdQm3KXmAb8hAAEI9CaAcPdGRUMIQAACeRBAuPPIA15AAAK1EVgwHoR7Qbh0DQEIQGAJAgj3ElTpEwIQgMCCBBDuBeHSNQTqI0BEORBAuHPIAj5AAAIQGEAA4R4Ai6YQgAAEciCAcOeQBXzYmgDjQ6AoAgh3UenCWQhAAALOIdzMAghAAAKFEUC4C0vYEHdpCwEI1EkA4a4zr0QFAQhUTADhrji5hAYBCNRJoDzhrjMPRAUBCECgNwGEuzcqGkIAAhDIgwDCnUce8AICECiPwGYeI9yboWdgCEAAAuMIINzjuHEXBCAAgc0IINyboWdgCORJAK/yJ4Bw558jPIQABCBwQgDhPsHBAQQgAIH8CSDc+ecID+cgQB8QqIgAwl1RMgkFAhDYBwGEex95JkoIQKAiAgh30cnEeQhAYI8EEO49Zp2YIQCBogkg3EWnD+chAIE9EshRuPeYB2KGAAQg0JsAwt0bFQ0hAAEI5EEA4c4jD3gBAQjkSCBTnxDuTBODWxCAAATOEUC4z5HhPAQgAIFMCSDcmSYGtyCwHAF6Lp0Awl16BvEfAhDYHQGEe3cpJ2AIQKB0Agh36RnEfyNACYHdEEC4d5NqAoUABGohgHDXkknigAAEdkMA4c481bgHAQhAoE0A4W4T4RgCEIBA5gQQ7swThHsQgAAE2gS2Ee62FxxDAAIQgEBvAgh3b1Q0hAAEIJAHAYQ7jzzgBQQgsA2BIkdFuItMG05DAAJ7JoBw7zn7xA4BCBRJAOEuMm04DYHLBLhaNwGEu+78Eh0EIFAhAYS7wqQSEgQgUDcBhLvu/NYVHdFAAAIHAgj3AQPfIAABCJRDAOEuJ1d4CgEIQOBAAOE+YNjyG2NDAAIQGEYA4R7Gi9YQgAAENieAcG+eAhyAAAQgMIzAUsI9zAtaQwACEIBAbwIId29UNIQABCCQBwGEO4884AUEILAUgQr7RbgrTCohQQACdRNAuOvOL9FBAAIVEkC4K0wqIe2BADHumQDCvefsEzsEIFAkAYS7yLThNAQgsGcCCPees59f7HgEAQj0IIBw94BEEwhAAAI5EUC4c8oGvkAAAhDoQQDh7gFpahPuhwAEIDAnAYR7Tpr0BQEIQGAFAgj3CpAZAgIQgMCcBMYL95xe0BcEIAABCPQmgHD3RkVDCEAAAnkQQLjzyANeQAAC4wns7k6Ee3cpJ2AIQKB0Agh36RnEfwhAYHcEEO7dpZyASyGAnxA4RwDhPkeG8xCAAAQyJYBwZ5oY3IIABCBwjgDCfY4M55chQK8QgMBkAgj3ZIR0AAEIQGBdAgj3urwZDQIQgMBkAgj3ZITqAIMABCCwHoH/BwAA//8GQf+vAAAABklEQVQDAEkiNsh3FDWgAAAAAElFTkSuQmCC', NULL, NULL, '2026-09-22 06:06:35'),
(13, 105, 1, '2026-09-27', NULL, NULL, 'Present', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAgwAAADaCAYAAADKfhoUAAAQAElEQVR4AezdS2802V3H8XNqchslEhMpbLiIhFnbQbS3SLBmAXkD7JCNxCK8AAS8AWCBhL3jHQQWrJkFy8fA2OuEAeWySKRMlESZJDOunF91/+1yu9pdVV2Xc/la/rv6UnXqnM9pV/2e6n6ep3J8IYAAAggggAACRwQIDEeAeBoBBBBAAIH4BebvIYFhfmP2gAACCCCAQPICBIbkp5ABIIAAAgjELpBD/wgMOcwiY0AAAQQQQGBmAQLDzMA0jwACCCAQuwD96yNAYOijxDoIIIAAAggULkBgKPwFwPARQACB2AXoXxwCBIY45oFeIIAAAgggELUAgSHq6aFzCCCAQOwC9K8UAQJDKTPNOBFAAAEEEDhBgMBwAh6bIoAAArEL0D8EphIgMEwlSTsIIIAAAghkLEBgyHhyGRoCCMQuQP8QSEeAwJDOXNFTBBBAAAEEVhMgMKxGz44RQCB2AfqHAAJPAgSGJwtuIYAAAggggMABAQLDARgeRgCB2AXoHwIILClAYFhSm30hgAACCCCQqACBIdGJo9sIxC5A/xBAIC8BAkNe88loEEAAAQQQmEWAwDALK40iELsA/UMAAQSGCRAYhnmxNgIIIIAAAkUKEBiKnHYGHbsA/UMAAQRiEyAwxDYj9AcBBBBAAIEIBQgMEU4KXYpdgP4hgAAC5QkQGMqbc0aMAAIIIIDAYAECw2AyNohdgP4hgAACCEwvQGCY3pQWEUAAAQQQyE6AwJDdlMY+IPqHAAIIIJCiAIEhxVmjzwgggAACCCwsQGBYGDz23dE/BBBAAAEEugQIDF0qPIYAAggggAACzwQIDM84Yr9D/xBAAAEEEFhHgMCwjjt7RQABBBBAICkBAsOE00VTCCCAAAII5CpAYMh1ZhkXAggggAACEwoUFBgmVKMpBBBAAAEEChMgMBQ24QwXAQQQQACBMQLRBIYxnWcbBBBAAAEEEFhGgMCwjDN7QQABBBBAIGmBnoEh6THSeQQQQAABBBA4UYDAcCIgmyOAAAIIIJCMwAkdJTCcgMemCCCAAAIIlCJAYChlphknAgUKXNzd/MyqwOEz5PQEou4xgSHq6aFzCCAwVGBzd/OTi/ubWuW8+5yV7ofnfj60PdZHAIGtAIFh68BPBBBIWCAEgZ9s7m8eFAq8d58/NJTw3Ge03qHneRyBVwUKf5LAUPgLgOEjkLqAAkAIAp/3LlxLcHtftfvIhapd+Ll7Sutpm91dFggg0FOAwNATitUQQCA+AZ34FQDaPatr99M3Z5e+qfPLt9+Euj27rMLjv7D1tI2uRth9llkIMIiZBQgMMwPTPAIIzCOgE75O/NZ6CARNULg9v/yCPdZehsc/qxDRfozbCCDQX4DA0N+KNRFAIAKBzd3NhwoL7a4oCIRA0BkU2uvpdvvtCd2nFhJgN8kLEBj2pjAciD64uLv+f6u9p7mLAAIrCjRvQXj3a9YFnfwVFuw+SwQQmE+AwNCyDWEhHH/c7zjvf9tKB6jWKtxEAIGVBPS7+OwtCOdqfTZhpe7ktlvGg8BRAQLDjmhzd/3N3c1ni/YB6tkT3EEAgcUEFObbv4t17X5EWFiMnx0h0AgQGBoG527Pr97dXdr8ePdQswiXHPiHXhoJfiCwvMCm4/MKTVg4v3xn+d6suEd2jUAEAgSGvUkIoeHTtav/0x4Of6r5rP50s7m7/oY9xhIBBOYX2ISw4FufV9Aew++nvyUsiIJCYHEBAkMH+e3Z1R/owBSe+ihU8+29/9NwAPthc4cfCCAwq0DzeYVWWAhX+urd7+Ss+x3ZOJshUIQAgeGVaQ4HqLfD5c8PbRXv3TuEBtNgicA8Ak1YcC5c3HPNl8ICn1doKPiBwKoCBIYj/OHy5xfruv5XW02hgbcoTIMlAtMKhLDw/ZAUwve23dq5H5wcFrZN8RMBBE4UIDD0ALw9v/pauNrgn19t4C2KHnSsgkBvgV1Y+JJtEMKC/trkr9t9lgggsK4AgWGA//Zqg+MtCscXApMJNA0dCAscnxodfiAQhwC/kAPnYRsa6q63KP53YFOsjgACO4HwHsT+lQWOTTsbFgjEIsAv5YiZOPAWxZd3n20gOIwwZZNIBRboln5vbDc1/3qjUbBEIDoBAsMJU7K72vBBuwnv/WNwuHj/5uuq9vPcRgCBJwHCwpMFtxCIXYDAcOIMhasNX9l+ILJ+ERxc5f5BpYPixfv//LeqE3fH5gi0BZK+rd8LG0DNlQWjYIlAtAIEhomm5lBweGy+qv7GhSI0PIpwo2CBzf3Ngw2fsGASLBGIW4DAMHB+fv/++hOrrk0tOLgH91eq2tf/4h4e/u5x3RAaHm9zI28BRtcpoLDgnQvfrvni31loGPiBQPQCBIYBU6RLqJXzlZXud5UOiHXl/r6p2v3ZQ+X/+sHV4U9UD3+kty8G7JJVEchKQL8bISmE7+2w+H3YOvATgRQEqhQ6GUMfdVWhbz/C0dBbVe4pYDhX/YcOmKoh7fXdL+sNEmDlhQX0uvfu6coCYcHxhUBSAlVSvV2xs/91dvWWDnC6UlA7V6vGdEcHTFUVgoSuTuggqhrTFtsgkIqAXuN63Vt/9btkt5dctvsx9nd4yf6yLwRiEqhi6kwKfVFw0HuuKh30LED06XvXAUoHUZXCg0oHNK4+OOf6gLJOEgJ6Tes1bp0Nvwc/sNtrLvU7vOb+2TcCqQkQGE6cMQsQfcKDHTTDATN8d+9Y61R7Vx8IEN1WPBq/QFdYCCfqVf5/iHZfwi9g+I7fjx4iEJNAFVNnUu9LOzwoQIQjUudbFwoFfcaq9VTVXoDos+3M69A8AkcFdMVMr19bMfwyfHetsGB9sGXoR2W3WSKAQD8Bfmn6OY1aSwclVTs8KESMaixspIOvSgdilf7ExNWHAMN3VAKbu5vv6PXZ7pR+B27PL3+z/diSt/W7ot8d7fOU30FtTyFQqgCBYaGZV3Cw0sFTZZ9/GHsA0wGw2l190AFaB8UmQCw0JnaDwL6AwoL37jfscb229Vq3+zEs9XsYQz/oAwKpCVSpdTin/tpbGDqA6aBqpYOs1ZDxdgUIhYghbbAuAmMF9FrbDwt6bY9tb6rtmn657V/n1O+V4wsBBEYJEBhGsZ200dGNdZC1shAx5mqEAoRKVx9UOnCqjnaAFRAYKKDXlV5rtplOzHoN2/1YljH2KRYb+oHAMQECwzGhSJ4/djWiTzd1QFcpPKh0kFf12ZZ1EDgkoNeSXlf2/O7DjVEcW/T6tr4pxFgfWSKAwHCBKH6ph3d7xi0Sa1p/YlLZlQgdFFV9hqEDqUoHfJUOrio+B9FHj3X0eQW9btoSeh2u+eHGdl/0Otbr2x7T74ndZokAAsMFCAzDzaLeQgdFlQ7c9jZG3w7r4KqqnK90IlB4UOnA27cN1itDQGHBR/zhRr1m9Tq22egbom19lggg8FKgevlQ1I/QuQEC9jaGwoNKB01V3yYUHlSVI0D0NSthPYVIvxcWFFJjGrtes9YfveZj65/1jSUCKQlUKXWWvp4moIOmSuFBpQOpqm+rCg+qym0DhF2F0J/m+rbBemkLNGHBbf/GgQtfev3oNRVuRvOt16V1Jsb+Wd9YIpCaQDVph2ksKQEd6FUKD0PfvrCBdgUInVTseZb5COhErPm2EcX04Ubrk/potwkLJsESgWkECAzTOCbfyqlvXxiATigqHbhVCg8qrkKYUHpLfV5Bc9nuuUJmLB9utH7pdWa3CQsmwRKBcQJdWxEYulR4zOnKg0onBpUOwKqhNAoPqmr3NoYO6ioCxFDJddZXWPB7n1fQ62Gd3hzeq15Tep3ZGnrt2m2WCCAwjUA1TTO0kruADsAqnSzGvn0hIx3UVZV7/jkIHfD1PBWPwOb++hO/Fxb0Goinh9uebO5vHvSa2t5zTq9Ru80SgTwF1hlVtc5u2WvKAlO9fWEGOtirdNlbpROAiqsQJrT8cnN3/U3v/OPxQVeXYgsLen3o9eLd04cwCQuOLwRmE3g8IMy2BxrOXkAnEpUO1iqdXFRjB64TgKoKJyydEBQeVDpBjG2T7YYJeO9/17aoXf2g+bX7ay/1OtDrQq+Pdl905at9n9sIrCWQ636rXAfGuNYT0MlFpfCgUnhQje2RwoOqcryNMdZwyHab8FaErb8NC1dv2f01l4eCgvqksKArX7pNIYDAPALVPM3SKgJPAgoPKoUHlcKD6mmN4bcUIFT6k6ZqE97HVumkMrw1tjCBTQgLPgQz3Y8lLGhONcfVrl/qm5WCgl5ThAUTYXlcgDXGClRjN2Q7BMYKKDyodKBXKTyoxran7bxzXlW57VWIDQHCDf3atMKCtr09W/fKwibMYVdQ0GtFrxsVQUEzRSGwjEC1zG7YCwKHBRQeVDoBqHRCUB3e4vgzCg+qahcgdOJpl05GVvoTrOp4q/musf8hR83DGqPVPNg8af7afbCrCXqttB/ndl4CjCZegSrertGzUgV0QlDppKVSeFBN6aGTkVUVQoXKTlTtpYUKLXUyU03Zj1ja8u0POdb1t6bul9ysZKlqO9ttzcP+vi0ocDVhX4b7CCwrUC27O/aGwHABhQeVwoNK4UE1pCWtrxqyjda1UKFl5XylspNbe6kTYLvs5Kg2Yi+Nw/rYfG7h/Opdu39saePU0sav9vZLblayVB1rm6BwTGiN59lnyQJVyYNn7GkKKDyoFB5UCgKq10ajE5RK69mJSNuqdF+l56xea6vrObXdrqpnuNCJds3SSb49njB+t98fraPaDwG6b+PU0sbfbm/I7bDvWvOg0rxwRWGIHusiML9ANf8u2AMC8wooPKh0klHpxKPq2qtOalU4metkp9KJ0DvvdXJSG1ZqZ790IlOpbauufRx7zLuww12pL2uW+uJaX1190Tqq1mqDbspKblb7rnZf9poH1aAdsPKjADcQmFOgmrNx2kZgDQGdeFR2ItIJS9XVF50IVQoPKgUIVde6OpGp1LaV7aO9tBOj9tmurjZTfkxjs7Fq2TZo35aV3KxSHjN9R6BkAQJDybNfyNh1wlLZSUwnOlXX8BUeVAoPKoUHVde6hx6zE6P22S7bvy11kl2j9sd+rA/W3/2lxmZj1fKQB4+bAEsE0hYgMKQ9f/R+hIBOdCo7AeqEuX8StWYVHlQKDyqFB5U9f8pSJ9k1SuOxfutDjsf6YOuyRACBsgUIDGXPP6MPAjphnhog9EHB0FT035v760+skwoLa//jTNaXGJb0AQEEXhcgMLzuw7MFChwKEF1XIfSndVW1+yDl5v7mQRVjgNiEsOBDPzWlhAUpUAggMESgGrIy6yJQooAFCLsKYW9hpBQg2mFBc5jelQX1mkIAgTUFCAxr6rPvJAVOCRD2OYglr0DE8s8+JznZdBoBBB4FCAyPFNxAYJzAfoDQhyl19UHV1aJ3el9g+59kKUBYbXZvZ9hyqlDh2//ss6sf3AxfNIkAAvkLEBjyn2NGuIKA3r5QKTyoFB5Ur3VFTyLWywAACmpJREFUQaJdlXsZKhQuLFBoqVChOtSu1rfn+NyCSbBEAIExAtWYjdgGAQSGCSg8qBQeVAoP7RrS2n6oqDqChcLEU1jYts7nFrYO/EQAgXEC1bjN2AoBBE4RUHhol0JEu/TBStUpoWK/fwoQ+6VgYaUrFSnW/ji5jwAC8wgQGOZxpVUEegt0rajPRaheCxUKGPuhQgGjq71Dj3VdrajCFYuUSoHn0Ph4HAEEphOopmuKlhBAYGmB/VChgNHuQ13X31KoUClMWLXXSf22Qk/qY6D/CKQgQGBIYZbo44oCae1abzlYj5sPOZ5fvatQoVKYsNLVif1SqEizqq/amFkigMB8AgSG+WxpGYFFBTb3p/2zzwoVadaf3y0Kzc4QKFSAwFDoxOcybMaxFVBY8M43v8/NlYWzq7e2z/ATAQQQmEagOcBM0xStIIDAGgIXd9e/tLCg/fPXJ6VAIYDA1AIEhqlFaa8lwM1FBLz/lO1HH3K02ywRQACBKQUIDFNq0hYCCwvo6sLjLuv649vzq3cf73MDAQQQmFCAwDAhZmpN0d+0BZqwYFcXQlh4c3716bRHRO8RQCBmAQJDzLND3xA4ILC5u75zFhbCOoSFgMA3AgjMKkBgmJX3lMbZFoHDAt77M3u2rut7u80SAQQQmEuAwDCXLO0iMJNA81aEtR3eirg9vzq3uywRQACBuQQIDCNl2QyBNQSasGBvRYSwwFsRa8wC+0SgTAECQ5nzzqhTFbCwEPpPWAgIfCOAwGICmQaGxfzYEQKLCTRXF2xv4eqC3WSJAAIILCFAYFhCmX0gcKJAExbs6kIIC1xdOBGUzRFAYLDAKoFhcC/ZAIGCBfgrlAVPPkNHICIBAkNEk0FXEOgS4K9QdqnwGAIILC3QERiW7gL7QwCBQwLNWxH2ZHgrgr9CaRgsEUBgaQECw9Li7A+BngJNWOBzCz21WA0BBF4ITPwAgWFiUJpDYDIBCwuhQT7kGBD4RgCBVQUIDKvys3MEugWaqwv2VHgrwm6yRACBbASSGwiBIbkpo8O5CzRhwa4uhLDA1YXcZ5zxIZCGAIEhjXmil4UI8FcoC5lohhm/AD18IUBgeEHCAwisJ8BfoVzPnj0jgMDrAgSG1314FoHFBJq3Imxv4a0I/gqlYbBE4IUAD6wgQGBYAZ1dIrAvsLm/+bnjcwv7LNxHAIGIBAgMEU0GXSlXwDv3GRs9H3I0CZbJCtDxLAUIDFlOK4NKSeDi/uZnrf5+1LrNTQQQQCAaAQJDNFNBRwoW+JyN/c3Z5dt2myUCMwnQLAKjBAgMo9jYCIFpBLi6MI0jrSCAwPwCBIb5jdkDAq8JcHXhNZ0Sn2PMCEQqQGCIdGLoVv4CXF3If44ZIQI5CRAYcppNxpKaAFcXUpsx5+gxAsUKEBiKnXoGvqYAVxfW1GffCCAwRoDAMEaNbRA4XYCrC6cbvmyBRxBAYDYBAsNstDSMQLcAVxe6XXgUAQTiFiAwxD0/9C5PAbu68FFh/+5CnrPJqBAoRIDAUMhEM8w4BPauLsTRKXqBAAII9BAgMPRAYhUEJhSI9+rChIOkKQQQyE+AwJDfnDKiSAW4uhDpxNAtBBDoJUBg6MXESghMInDK1YVJOkAjCCCAwFgBAsNYObZDYIBA++oCH3QcAMeqCCAQjQCBIZqpoCNJC7zS+V1YeLy68MqqPIUAAghEK0BgiHZq6FiOAlxdyHFWGRMCZQgQGMqY59JHudr4ubqwGj07RgCBiQUIDBOD0hwChwS4unBIhscRQCAFAQJDCrOUex8zHd/m/vrHYWh8diEg8I0AAukLEBjSn0NGkIAAVxcSmCS6iAACrwoQGF7l4UnnHAgjBbzzX9Cmde0+dHwhgAACiQsQGBKfQLofp8Dm7uaH1rPb88sv2m2WCCCAQKoCBIZUZ876zTJKAe/dOy58cXUhIPCNAAJZCBAYsphGBhGTQPvqQkz9oi8IIIDAKQIEhlP0jm/LGgUK2NUFDZ23I6RAIYBADgIEhhxmkTFEI9C+usDbEdFMCx1BAIEJBMoODBMA0gQChwS4unBIhscRQCBFAQJDirNGn6MVsLcjuLoQ7RTRMQQQGCkQc2AYOSQ2Q2AdgfbbEc7V763TC/aKAAIIzCNAYJjHlVYLFGhfXbg9v/pagQQMGQEEMhYYHxgyRmFoCAwVeH51YejWrI8AAgjEL0BgiH+O6GFiAnzYMbEJo7sIFC7Qd/gEhr5SrIfAAYHN3fU32m9HHFiNhxFAAIGkBQgMSU8fnY9DwP+h9YOrCybBEgEEphGIpxUCQzxzQU8SFeDqQqITR7cRQGCQAIFhEBcrI/BcYNP6XymfP8M9BBAoQaCkMRIYSpptxjq5gF1dUMO8HSEFCgEEchUgMOQ6s4xrdoGL++vv2U74lx1NgiUCMQnQlykFCAxTatJWsQJcXSh26hk4AsUIEBiKmWoGOoPAl7Zt1h9vl/xEAIEhAqyblgCBIa35ordRCfhPqTvh7Yhva0khgAACOQsQGHKeXcY2m8DF3fU/zdY4DSMQhQCdQOC5AIHhuQf3EOglUDv3x7bi7fnVV+w2SwQQQCBXAQJDrjPLuGYV8N791nYHfH5h68DPpQXYHwJLCxAYlhZnf5kI8PmFTCaSYSCAQE8BAkNPKFZDwAT4/IJJsDwswDMI5CdAYMhvThnRzAJ8fmFmYJpHAIEoBQgMUU4LnUIAgTkFaBsBBIYLEBiGm7FF4QLe+y+LoK7rD7SkEEAAgRIECAwlzDJjRCApATqLAAIxChAYYpwV+hStwMX9zX9b57xz/263WSKAAAK5CxAYcp9hxjebwJvzq7+crfGIG6ZrCCBQpgCBocx5Z9SjBeoPR2/KhggggEDCAgSGhCePrq8h4H9vt9dIg8OudywQQACBiQUIDBOD0hwCCCCAAAI5ChAYcpxVxjSnwDvbxuv/2S6H/WRtBBBAIFUBAkOqM0e/EUAAAQQQWFCAwLAgNruKXYD+IYAAAggcEiAwHJLhcQT2BC7ev/n640MP/t8eb3MDAQQQKECAwFDAJOcyRMaBAAIIILCeAIFhPXv2nJpAVf+JdfnNVy//0W6zRAABBEoQIDCUMMuLjDH/ndTe8Z9NOb4QQKBUAQJDqTPPuAcL+E/q/xu8ERsggAACmQgQGDKZyGPD4HkEEEAAAQROESAwnKLHtggggAACCBQiQGCIYqLpRBIClXvP8YUAAggUKkBgKHTiGfZwgTdnf/Hem7NLrxq+NVsggAACaQsQGHrMH6sggAACCCBQugCBofRXAONHAAEEEECgh0AGgaHHKFkFAQQQQAABBE4SIDCcxMfGCCCAAAIIlCEwe2Aog5FRIoAAAgggkLcAgSHv+WV0CCCAAAIITCHgCAyTMNIIAggggAACeQsQGPKeX0aHAAIIIFCCwAJjJDAsgMwuEEAAAQQQSF2AwJD6DNJ/BBBAAIHYBbLoH4Ehi2lkEAgggAACCMwrQGCY15fWEUAAAQRiF6B/vQQIDL2YWAkBBBBAAIGyBQgMZc8/o0cAAQRiF6B/kQgQGCKZCLqBAAIIIIBAzAK/AgAA//9a46+TAAAABklEQVQDAAZ9CVqVXMwSAAAAAElFTkSuQmCC', NULL, NULL, '2026-09-26 20:45:29'),
(14, 101, 1, '2026-09-27', NULL, NULL, 'Late', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAgwAAADaCAYAAADKfhoUAAAQAElEQVR4AezczW4cW9UG4GrznfOBQAIJMUBMkBgnSORMkRhwH4xQDlfE8ZAr4BYYMEwYJGMkJogBQgIJxH+aWsnZdtnHbfdP/ay992Nlp9rtrtprP8t2vV2dztXggwABAgQIECDwhIDA8ASQLxMgQIAAgfwCy1coMCxvbAYCBAgQIFC9gMBQfQstgAABAgSyC7RQn8DQQhetgQABAgQILCwgMCwM7PAECBAgkF1AfccICAzHKHkMAQIECBDoXEBg6PwbwPIJECCQXUB9OQQEhhx9UAUBAgQIEEgtIDCkbo/iCBAgkF1Afb0ICAy9dNo6CRAgQIDABQICwwV4diVAgEB2AfURmEtAYJhL0nEIECBAgEDDAgJDw821NAIEsguoj0A9AgJDPb1SKQECBAgQ2ExAYNiM3sQECGQXUB8BArcCAsOthVsECBAgQIDAAQGB4QCMuwkQyC6gPgIE1hQQGNbUNhcBAgQIEKhUQGCotHHKJpBdQH0ECLQlIDC01U+rIUCAAAECiwgIDIuwOiiB7ALqI0CAwGkCAsNpXh5NgAABAgS6FBAYumy7RWcXUB8BAgSyCQgM2TqiHgIECBAgkFBAYEjYFCVlF1AfAQIE+hMQGPrruRUTIECAAIGTBQSGk8nskF1AfQQIECAwv4DAML+pIxIgQIAAgeYEBIbmWpp9QeojQIAAgRoFBIYau6ZmAgQIECCwsoDAsDJ49unUR4AAAQIEHhIQGB5ScR8BAgQIECBwR0BguMOR/RP1ESBAgACBbQQEhm3czUqAAAECBKoSEBhmbJdDESBAgACBVgUEhlY7a10ECBAgQGBGgY4Cw4xqDkWAAAECBDoTEBg6a7jlEiBAgACBcwTSBIZzircPAQIECBAgsI6AwLCOs1kIECBAgEDVAkcGhqrXqHgCBAgQIEDgQgGB4UJAuxMgQIAAgWoELihUYLgAz64ECBAgQKAXAYGhl05bJwECBAhkF0hdn8CQuj2KI0CAAAECOQQEhhx9UAUBAgQIZBfovD6BofNvAMsnQIAAAQLHCAgMxyh5DAECBAhkF1DfwgICw8LADk+AAAECBFoQEBha6KI1ECBAILuA+qoXEBiqb6EFECBAgACB5QUEhuWNzUCAAIHsAuoj8KSAwPAkkQcQIECAAAECAoPvAQIEFhX45M3131+8uf7n0mPRRWx9cPMTSCAgMCRoghII1CQQAWA6Xry9fhfjk7fX+4fGsBu+vNsNHy89ytxRy/vx5vpfNbmqlUB2gavsBaqPAIHlBU4NABECytgNw5gFhnEzpPgYC3lfz/jXRyuFiBTrVgSBpQUEhqWFHZ9AYoF4Jh4n1fF0/+Uyygn30rL3w7CPMeyHf4w3/rXoKHMdKLqsaRoiYt2x/hgHdnM3AQITAYFhguEmgV4EXry5/mucMONEeuya4+QfIwLAdLx69nL30Hj97OVVjFfPX37l9fOX/7/oKHM9u61lDCj/jnr3Y5g4tMZYf4ywKCMCxPvhJY1DbO7vVEBg6LTxlt2vQJwMd7vhq3cExqsAJQQ8dPKP++LkHyMCwHTcOU6iT8aA8nHUGyPqj3FKiBiNvKSRqJ9K2V5AYNi+ByogsJpAPIuOZ9RlwngGHifSDQNAKWWV7VwhIkJXjFWKNgmBJAICQ5JGKIPAkgLlJYjpHOOz7b/Fs+/pfT3ePidEROiKEQEsRoSHGD36WXM/AgJDP7220k4F4kQ2Xl6/8xJEXFUYT5Rfe5Kk0weMNl94OSPM4opMjPssER5iRHiIEeZjSPv3/cf5nEDNAgJDzd1TO4FHBMYT1hf+YWNcVYgT3yO7+dIjAnFFJkYYxojwEOP+LhEexpD2fzfh4e31u/uP8TmB2gQEhto6pl4CtwIHb8Uz3PGEdeeqQoSF8ZmzqwoH1U7/QoSHGO/Dw374z8HwMAy7CA8xojdjmHP1YfBRm4DAUFvH1EvgCIF4hlseFiexOKEJC0Vkme3o+1EJD+Ed7jHuzxa9GcPc+6sP97/mcwKZBQSGzN1RW90CG1Ufz2DL1HHCipNY+dx2PYFwj/E+PBy4+hBXHNaryEwELhMQGC7zszeBVALjpe4/xzPYKEpYCIUc46GrD6UyoaFI2GYXEBiyd0h9Swk0edzxUvfXy8Li2W25bZtLIHoTga5UJTQUCdvMAgJD5u6ojcAJAvdfijhhVw/dQEBo2ADdlBcJCAwX8dl5MQEHPlmgvBQRO8bJKLZGboHokysNuXukulsBgeHWwi0C1Qq4ulBt64b7oWHay3pXpfIWBQSGFru6/JrMkEggTjDl6kI8W40TUKLylHKEwLRn0csfvP3sv0fs5iEEVhUQGFblNhmB+QXiBFOOOj3xlPts6xB4N+xv/jfIq2F3VUfVquxJwDdli922pm4E4upCWWxcXSi3besT+M2zT780rdpVhqmG2xkEBIYMXVADgTMEIiyUqwsRFlxdOAMx2S7xnzyVkq5cZSgUtkkErpLU0VMZ1kpgFoESFuJgwkIotDEi/JWVuMpQJGwzCAgMGbqgBgInCsTVhbLL9ART7rOtV2Aa/q5cZai3kQ1WftXgmi5bkr0JVCDg6kIFTZqpRFcZZoJ0mIsFBIaLCR2AwLoCd64u7Ie/rDu72dYQmL5jYjfsdmvMaQ4CTwnUFhieWo+vE2haIMLCePYY/wxDvBTx+vnLbzS94E4XN33HxNjs8U+nEJadSkBgSNUOxRB4XGA8c4x/Pjxm+lr3h3v83ZJABMKW1mMt9QvMGxjq97ACAmkF4upCKc7JpEjYEiCwloDAsJb0SvPEP5C6ZKxUpmlOFIiwMF5aGP98/lLEs5d+dk809HACBI4XeOiRfuk8pFLpfZ+8vd5fDburS0Yc47ERJ67745KAYt/P/nuMwfRbcj/s98fs4zHH2WZ1mvZ8WuP0frcJrClwteZk5lpOIH6hLHf02yOPT3F398fVcFlIsf/TfmFeusDraa8WjA71PAJ7+V6w7VVgm3VfbTOtWecWiH9VHf+tbLwd66kRr3+XMXcdjkeAwLIC0yCx7EyOTuCugMBw16P6zyI4PDXiX9eXESGjjBI0soeJUl8v2+k3ZS9r7nGd0z5Pb5efy9vt1fenX3c7n0CrFQkMrXb2jHWVoPFQmIhQEb+wpr/Iz5hill3iGVZPY4rW07p7W+u0z3E7ft7i5678XN5uf/omvm4QWFtAYFhbvOL54hdWCROxjV9mZcQvtxjTQHHodsUESiewuED8HMXPVfy8LT5ZlxNY9LkCAsO5cva7IxC/3GJEkHhqxC/DS0b8Qu1lTJF7WXPP64yfi/g5mvbdbQJZBASGLJ1Qx9EC8Qu1l1FQ4mpNL2vueZ2l3z1vrT2vgMCQtzcq61zg3tvn/tQ5h+UTILCxgMCwcQNMT+CQQPyjv/K18WWeb5XbtgS2EzBzzwICQ8/dt/YqBOLliCoKVSQBAk0LCAxNt9fiahWYvhwxXl3wc1prI1eu23QElhTwi2hJXccmcKbA9OWIMw9hNwIECMwqIDDMyulgBC4XGK8u/LEcxcsRRaKFrTUQqFtAYKi7f6pvU+CbZVlejigStgQIbC0gMGzdAfMTuCdQXo5wdeEezMKfOjwBAo8LCAyP+/gqgVUFxpcj3q06ockIECBwpIDAcCSUhxFYW8DLEVNxtwkQ2FpAYNi6A+YnMBHwcsQEw00CBFIJCAyp2qGYngVqfjmi575ZO4FeBASGXjptnVUJeDmiqnYplkAXAgJDF222yBoEyssR89fqiAQIELhcQGC43NARCMwq4O2Us3I6GAECMwkIDDNBOgyBcwXsR4AAgRoEBIYauqTG5gVevLn+ffOLtEACBKoWEBiqbp/ilxdYaYbd8O2bmfbDH25uu0GAAIEkAgJDkkYog0AReP385XfKbVsCBAhkERAYsnRCHWcJtLKTd0i00knrINCugMDQbm+trEIB75CosGlKJtCJgMDQSaO3WaZZCRAgQKAVAYGhlU5aR7UC3iFRbesUTqArAYGhq3bfXazPkgh4h0SSRiiDAIHHBASGx3R8jcDKAt4hsTK46QgQOFpAYDiaau0Hmq8XAe+Q6KXT1kmgbgGBoe7+qb4hAe+QaKiZlkKgQQGB4cym2o0AAQIECPQkIDD01G1rTSfgHRLpWqIgAgQOCDQaGA6s1t0Esgl4h0S2jqiHAIEDAgLDARh3E1hJYF/m8Q6JImFLgEBGgU0CQ0YINRHYRuAmL2wzvVkJECBwpIDAcCSUhxFYQmA37PwMLgHrmAQIzC7wwC+r2edwQAIECBAgQKByAYGh8gYqvw2B/bB/18ZKrIIAgTQCMxciMMwM6nAECBAgQKBFAYGhxa5aEwECBAhkF6iuPoGhupYpuBWBF28+++3NWvbD7wYfBAgQSCwgMCRujtIIECBAYCMB035BQGD4Aok7CKwksBu+O3z+8fr5p9/7/KYNAQIEUgoIDCnboqgeBHb7nf+1qYdGW+MSAo65gYDAsAG6KQm8eHv9z2E3fGmIj/3+P7ExCBAgkFlAYMjcHbU1KfDizWe/3A3Dx2Vxr55/+lG5bUugCQGLaFJAYGiyrRaVWmA3/LjUtx/2vy63bQkQIJBZQGDI3B21NSfwyduf/2g37L5WFvb62ac/LLdtCawkYBoCZwkIDGex2YnAeQL73e4n5+1pLwIECGwrIDBs62/2zgR2+/0vypJfPXu5K7dtCdwIuEEgqYDAkLQxympT4NWzn/0qgkKMNldoVQQItCogMLTaWesiQGAJAcck0K2AwNBt6y2cAAECBAgcLyAwHG/lkQQIZBdQHwECiwkIDIvROjABAgQIEGhHQGBop5dWQiC7gPoIEKhYQGCouHlKJ0CAAAECawkIDGtJm4dAdgH1ESBA4BEBgeERHF8iQIAAAQIEPggIDB8c/E0gu4D6CBAgsKmAwLApv8kJECBAgEAdAgJDHX1SZXYB9REgQKBxAYGh8QZbHgECBAgQmENAYJhD0TGyC6iPAAECBC4UEBguBLQ7AQIECBDoQUBg6KHL2deoPgIECBBILyAwpG+RAgkQIECAwPYCAsP2PchegfoIECBAgMAgMPgmIECAAAECBJ4UEBieJEr+AOURIECAAIEVBASGFZBNQYAAAQIEahcQGJbtoKMTIECAAIEmBASGJtpoEQQIECBAYFmBvgPDsraOToAAAQIEmhEQGJpppYUQIECAAIHlBDIHhuVW7cgECBAgQIDASQICw0lcHkyAAAECBPoUOD8w9Oll1QQIECBAoEsBgaHLtls0AQIECBD4IHDs3wLDsVIeR4AAAQIEOhYQGDpuvqUTIECAQHaBPPUJDHl6oRICBAgQIJBWQGBI2xqFESBAgEB2gZ7qExh66ra1EiBAgACBMwUEhjPh7EaAAAEC2QXUN6eAwDCnpmMRIECAAIFGBQSGRhtrWQQIEMguoL66BASGuvqlWgIECBAgsImAwLAJu0kJECCQXUB9BO4KCAx3PXxGrJ7veAAAAjxJREFUgAABAgQIPCAgMDyA4i4CBAhkF1AfgbUFBIa1xc1HgAABAgQqFBAYKmyakgkQyC6gPgLtCQgM7fXUiggQIECAwOwCAsPspA5IgEB2AfURIHC6gMBwupk9CBAgQIBAdwICQ3ctt2AC2QXUR4BARgGBIWNX1ESAAAECBJIJCAzJGqIcAtkF1EeAQJ8CAkOffbdqAgQIECBwkoDAcBKXBxPILqA+AgQILCMgMCzj6qgECBAgQKApAYGhqXZaTHYB9REgQKBWAYGh1s6pmwABAgQIrCggMKyIbarsAuojQIAAgUMCAsMhGfcTIECAAAECNwICww2FG9kF1EeAAAEC2wkIDNvZm5kAAQIECFQjIDBU06rshaqPAAECBFoWEBha7q61ESBAgACBmQQEhpkgsx9GfQQIECBA4BIBgeESPfsSIECAAIFOBASGFI1WBAECBAgQyC0gMOTuj+oIECBAgEAKAYHhiDZ4CAECBAgQ6F1AYOj9O8D6CRAgQIDAEQINBIYjVukhBAgQIECAwEUCAsNFfHYmQIAAAQJ9CCweGPpgtEoCBAgQINC2gMDQdn+tjgABAgQIzCEwCAyzMDoIAQIECBBoW0BgaLu/VkeAAAECPQissEaBYQVkUxAgQIAAgdoFBIbaO6h+AgQIEMgu0ER9AkMTbbQIAgQIECCwrIDAsKyvoxMgQIBAdgH1HSUgMBzF5EEECBAgQKBvAYGh7/5bPQECBLILqC+JgMCQpBHKIECAAAECmQX+BwAA//9KtPWpAAAABklEQVQDAN2G5UtF4/KpAAAAAElFTkSuQmCC', NULL, NULL, '2026-09-26 20:46:05');

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

--
-- Dumping data for table `class_attendance_sessions`
--

INSERT INTO `class_attendance_sessions` (`session_id`, `class_schedule_id`, `department_id`, `campus_id`, `faculty_id`, `room_id`, `subject_id`, `session_date`, `time_slot`, `attending_students`, `secretary_verifier_external_id`, `secretary_verifier_name`, `status`, `created_at`) VALUES
(13, NULL, 1, 1, 105, 4, 1, '2026-09-27', '04:45 AM', 40, NULL, 'System', 'Present', '2026-09-26 20:45:29'),
(14, NULL, 1, 1, 101, 4, 1, '2026-09-27', '04:45 AM', 45, NULL, 'System', '', '2026-09-26 20:46:05');

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
-- Table structure for table `committees`
--

CREATE TABLE `committees` (
  `committee_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Completed','Archived') NOT NULL DEFAULT 'Active',
  `created_by` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(53, 'FAC-2026-0004', '149', 'Jean', 'Claude', 'Espejo', '', NULL, NULL, NULL, 'claudeespejo@gmail.com', 1, NULL, 'Faculty Professor', NULL, 0, NULL, 'instructor', NULL, 'Probationary', 'Active', 0.00, '2023-01-30', NULL, '2026-08-25 07:00:55', '2026-08-25 07:00:55'),
(101, 'FAC-2026-0069', '248', 'Light', 'A', 'Yagami', '', '2002-01-29', 'male', '09318298352', 'whitybrix@gmail.com', 1, NULL, 'Faculty Professor', NULL, 0, NULL, 'instructor', NULL, 'Probationary', 'Active', 0.00, '2026-02-18', '2027-10-13', '2026-09-10 17:45:44', '2026-09-10 17:45:44'),
(105, 'FAC-2026-0107', '256', 'Alfred Joseph', 'A', 'Alcantara', '', '2002-05-22', 'male', '09318298352', 'mingthecat002@gmail.com', 1, NULL, 'Attendance Monitoring Officer', NULL, 0, NULL, 'instructor', NULL, 'Regular', 'Active', 0.00, '2026-03-12', NULL, '2026-09-24 00:51:19', '2026-09-24 00:51:19'),
(106, 'FAC-2026-0106-P106', NULL, 'Earl', 'M', 'Salvame', '', '2004-10-08', 'male', '09318298352', 'bardpanget2025@gmail.com', 1, NULL, 'Faculty Secretary', NULL, 0, NULL, 'instructor', NULL, 'Regular', 'Active', 0.00, '2026-02-26', NULL, '2026-09-26 20:52:25', '2026-09-26 20:52:25'),
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
-- Table structure for table `faculty_committee_assignments`
--

CREATE TABLE `faculty_committee_assignments` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `committee_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `role` enum('Chair','Co-Chair','Secretary','Member') NOT NULL DEFAULT 'Member',
  `status` enum('Active','Completed','Replaced') NOT NULL DEFAULT 'Active',
  `appointed_at` date NOT NULL,
  `ended_at` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
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
(34, 'FAC-2026-0004', 'Jean', 'Claude', 'Espejo', '', '2000-05-17', 26, 'MALE', '09318298353', 'claudeespejo@gmail.com', 'uploads/signatures/sig_149_1790064541.png', 'BSIT', 'Department Head', NULL, 0, 0, NULL, NULL, '2023-01-30', '0000-00-00', NULL, 'regular', 'Active', 'approved', '2026-08-14 03:58:31', '2026-10-30 15:31:10', NULL, NULL, '', '', '', '', 149),
(101, 'FAC-2026-0069', 'Light', 'A', 'Yagami', '', '2000-02-01', 26, 'MALE', '09318298352', 'whitybrix@gmail.com', NULL, 'BSIT', 'Faculty Professor', NULL, 0, 0, NULL, NULL, '2026-02-12', '2027-06-11', NULL, 'probationary', 'Active', 'approved', '2026-09-10 18:12:42', '2026-09-19 11:11:07', NULL, NULL, NULL, NULL, NULL, NULL, 250),
(106, 'FAC-2026-0106', 'Earl', 'M', 'Salvame', '', '2004-10-08', 21, 'MALE', '09318298352', 'bardpanget2025@gmail.com', NULL, 'BSIT', 'Faculty Secretary', NULL, 0, 0, NULL, NULL, '2026-02-26', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-24 00:34:28', '2026-09-26 07:01:44', NULL, NULL, NULL, NULL, NULL, NULL, 255),
(107, 'FAC-2026-0107', 'Alfred Joseph', 'A', 'Alcantara', '', '2002-05-22', 24, 'MALE', '09318298352', 'mingthecat002@gmail.com', NULL, 'BSIT', 'Attendance Monitoring Officer', NULL, 0, 0, NULL, NULL, '2026-03-12', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-24 00:45:01', '2026-09-24 00:51:19', NULL, NULL, NULL, NULL, NULL, NULL, 256),
(111, 'FAC-2026-0111', 'Sofia', 'A', 'Reyes', '', '2002-03-23', 24, 'FEMALE', '09318278392', 'idkwhoistonio@gmail.com', NULL, 'BSIT', 'Faculty Professor', NULL, 0, 0, NULL, 'Professor III', '2025-07-09', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-26 14:49:46', '2026-09-26 14:52:45', 'Professor', NULL, NULL, NULL, NULL, NULL, 261),
(112, 'FAC-2026-0112', 'Jorge', 'V', 'Lucero', '', '1990-03-23', 36, 'MALE', '09318278392', 'jcespejo002@gmail.com', NULL, 'BSIT', 'Dean', NULL, 0, 0, NULL, 'Professor VI', '2022-09-23', NULL, NULL, 'regular', 'Active', 'approved', '2026-09-26 15:21:19', '2026-10-28 16:42:41', 'Professor', NULL, 'quezon city', '', '', '', 262),
(113, 'FAC-2026-0113', 'Ivan', 'F', 'Camachi', '', '2004-02-23', 22, 'MALE', '09318278392', 'leriqwe27@gmail.com', NULL, 'BSBA', 'Department Head', NULL, 0, 0, NULL, NULL, '2022-06-23', NULL, NULL, 'regular', 'Pending Approval', 'pending', '2026-09-26 20:24:13', '2026-11-03 10:07:15', NULL, NULL, NULL, NULL, NULL, NULL, 263);

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
(12, 112, 1, '2026-11-03 10:07:25');

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
(1, 101, '2026-2027', 15, 0, 7, 0, 0, 0, 7, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-09-26 18:40:35');

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

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `request_ref`, `faculty_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `reason`, `documents_status`, `screening_status`, `screened_by_external_id`, `screened_at`, `screening_signature`, `approval_status`, `status`, `approver_id`, `approver_at`, `approver_comment`, `documents`, `notification`, `updated_at`, `approved_by_external_id`, `approved_at`, `comments`, `created_at`, `faculty_id_backup`) VALUES
(36, 'LR-20260927024035-11', 101, 'Paternity Leave', '2026-09-28', '2026-09-28', 1, 'test', 'Incomplete', 'Pending', NULL, NULL, NULL, 'Pending', 'Pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2026-09-27 02:40:35', NULL);

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
-- Table structure for table `teaching_load_final_records`
--

CREATE TABLE `teaching_load_final_records` (
  `final_record_id` int(10) UNSIGNED NOT NULL,
  `load_request_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `total_units` decimal(5,1) NOT NULL DEFAULT 0.0,
  `subject_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `assignments_json` longtext NOT NULL,
  `acknowledged_by_external_id` varchar(64) NOT NULL,
  `finalized_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `comments` text DEFAULT NULL,
  `workflow_stage` varchar(40) NOT NULL DEFAULT 'dean_review',
  `faculty_user_id` int(10) UNSIGNED DEFAULT NULL,
  `submitted_by_external_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_load_request_items`
--

CREATE TABLE `teaching_load_request_items` (
  `load_request_item_id` int(10) UNSIGNED NOT NULL,
  `load_request_id` int(10) UNSIGNED NOT NULL,
  `class_schedule_id` int(10) UNSIGNED NOT NULL,
  `created_for_request` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_load_workflow_history`
--

CREATE TABLE `teaching_load_workflow_history` (
  `history_id` int(10) UNSIGNED NOT NULL,
  `load_request_id` int(10) UNSIGNED NOT NULL,
  `stage` varchar(40) NOT NULL,
  `action` varchar(40) NOT NULL,
  `performed_by_external_id` varchar(64) DEFAULT NULL,
  `performed_by_role` varchar(80) NOT NULL,
  `performed_by_name` varchar(150) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_pins`
--

CREATE TABLE `user_pins` (
  `pin_id` int(10) UNSIGNED NOT NULL,
  `external_user_id` varchar(64) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Indexes for table `committees`
--
ALTER TABLE `committees`
  ADD PRIMARY KEY (`committee_id`),
  ADD KEY `idx_committees_dept_term` (`department_id`,`term_id`);

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
-- Indexes for table `faculty_committee_assignments`
--
ALTER TABLE `faculty_committee_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_committee_faculty` (`committee_id`,`faculty_id`,`appointed_at`),
  ADD KEY `idx_assignment_faculty` (`faculty_id`);

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
-- Indexes for table `teaching_load_final_records`
--
ALTER TABLE `teaching_load_final_records`
  ADD PRIMARY KEY (`final_record_id`),
  ADD UNIQUE KEY `uq_final_record_request` (`load_request_id`),
  ADD KEY `idx_final_record_faculty_term` (`faculty_id`,`term_id`);

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
-- Indexes for table `teaching_load_workflow_history`
--
ALTER TABLE `teaching_load_workflow_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_load_workflow_request` (`load_request_id`),
  ADD KEY `idx_load_workflow_stage` (`stage`);

--
-- Indexes for table `user_pins`
--
ALTER TABLE `user_pins`
  ADD PRIMARY KEY (`pin_id`),
  ADD UNIQUE KEY `uq_user_pins_external` (`external_user_id`);

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
  MODIFY `attendance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `campus_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_attendance_sessions`
--
ALTER TABLE `class_attendance_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  MODIFY `clearance_office_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22027;

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
-- AUTO_INCREMENT for table `committees`
--
ALTER TABLE `committees`
  MODIFY `committee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `faculty_committee_assignments`
--
ALTER TABLE `faculty_committee_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `faculty_profile_department_assignments`
--
ALTER TABLE `faculty_profile_department_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  MODIFY `entitlement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

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
-- AUTO_INCREMENT for table `teaching_load_final_records`
--
ALTER TABLE `teaching_load_final_records`
  MODIFY `final_record_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `teaching_load_workflow_history`
--
ALTER TABLE `teaching_load_workflow_history`
  MODIFY `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_pins`
--
ALTER TABLE `user_pins`
  MODIFY `pin_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `faculty_committee_assignments`
--
ALTER TABLE `faculty_committee_assignments`
  ADD CONSTRAINT `fk_assignment_committee` FOREIGN KEY (`committee_id`) REFERENCES `committees` (`committee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignment_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SQL;

mysqli_report(MYSQLI_REPORT_OFF);
$ok = $conn->multi_query($sql);
while (true) {
    if ($result = $conn->store_result()) { $result->free(); }
    if (!$conn->more_results()) { break; }
    if (!$conn->next_result()) { break; }
}
if (!$ok || $conn->errno) {
    echo "Import failed: " . $conn->error . "\n";
} else {
    echo "Database imported successfully!\n";
}
$conn->close();