-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2026 at 08:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
(132, 8, 'HR', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:03'),
(133, 8, 'HR', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:15'),
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
(183, 8, 'HR', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:12:05'),
(184, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 02:06:41'),
(185, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:26:55'),
(186, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user dean', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:53'),
(187, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:58'),
(188, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:05'),
(189, 8, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:22'),
(190, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:04'),
(191, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:19'),
(192, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:53:52'),
(193, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:54:01'),
(194, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:54:55'),
(195, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:55:09'),
(196, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:58:50'),
(197, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:59:01'),
(198, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:37:46'),
(199, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:38:01'),
(200, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:20:38'),
(201, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:20:48'),
(202, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:21:58'),
(203, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:22:07'),
(204, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:31:56'),
(205, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:38:32'),
(206, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:38:53'),
(207, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:39:46'),
(208, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:46:11'),
(209, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:46:25'),
(210, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:50:27'),
(211, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:50:38'),
(212, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:50:49'),
(213, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:51:37'),
(214, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:52:46'),
(215, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:53:02'),
(216, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:53:07'),
(217, 13, 'Dr. Jose B. Tan', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 20:53:19'),
(218, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:17'),
(219, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:37'),
(220, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:49'),
(221, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:00:59');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(222, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:31:48'),
(223, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:31:58'),
(224, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:32:19'),
(225, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:32:32'),
(226, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:48:21'),
(227, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:48:29'),
(228, 8, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:48:41'),
(229, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:59:38'),
(230, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 00:59:52'),
(231, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:17'),
(232, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:21'),
(233, 12, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:31'),
(234, 12, 'Dr. Roberto M. Santos', 'adviser', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:00:48'),
(235, 12, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:01:15'),
(236, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:01:29'),
(237, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:33:22'),
(238, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:33:35'),
(239, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user departmenthead', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:49:08'),
(240, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user departmenthead', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:52:53'),
(241, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user secretary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:53:27'),
(242, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user faculty', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:54:02'),
(243, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:58:39'),
(244, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:58:48'),
(245, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:15'),
(246, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:25'),
(247, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:30'),
(248, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 01:59:40'),
(249, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:54:31'),
(250, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:57:16'),
(251, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:57:31'),
(252, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:58:16'),
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
(271, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:25:35'),
(272, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:47:44'),
(273, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:47:53'),
(274, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:06'),
(275, 46, 'Faculty', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:21'),
(276, 46, 'Faculty', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:27'),
(277, 13, 'Dr. Jose B. Tan', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:48:35'),
(278, 13, 'Dr. Jose B. Tan', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:49:03'),
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
(296, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:06:32'),
(297, 8, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:07:55'),
(298, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:07:59'),
(299, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:08:13'),
(300, 44, 'Department Head', 'department_head', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:30:44'),
(301, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:50:59'),
(302, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:10'),
(303, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:20'),
(304, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:26'),
(305, 46, 'Jean Claude', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:53:56'),
(306, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:58:48'),
(307, 8, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:20:18'),
(308, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:20:42'),
(309, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:03'),
(310, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:14'),
(311, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:33'),
(312, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:56:45'),
(313, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:07:41'),
(314, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:07:52'),
(315, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:11:55'),
(316, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:12:05'),
(317, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:12:54'),
(318, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:13:03'),
(319, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:58:12'),
(320, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 22:58:22'),
(321, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:20:38'),
(322, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:37:26'),
(323, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:37:41'),
(324, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:59:07'),
(325, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 19:59:40'),
(326, 8, 'Dean', 'hr', 'create', 'faculty', 'Registered faculty account: Jean Claude Espejo (department_head)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:18:13'),
(327, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:29:21'),
(328, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:29:34'),
(329, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:34:40'),
(330, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:34:52'),
(331, 8, 'Dean', 'hr', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 21:58:28'),
(332, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:40:37'),
(333, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:40:56'),
(334, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:57:18'),
(335, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 22:57:40'),
(336, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:23:49'),
(337, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:24:02'),
(338, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:29:18'),
(339, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:29:38'),
(340, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:23:04'),
(341, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:36:08'),
(342, NULL, 'qwerty qwerts', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:37:26'),
(343, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:37:50'),
(344, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:38:18'),
(345, NULL, 'Jean Espejo', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:39:08'),
(346, NULL, 'qwerty qwerts', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:39:26'),
(347, NULL, 'jr Espejo', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:39:55'),
(348, NULL, 'qwerty qwerts', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:40:22'),
(349, NULL, 'qwerty qwerts', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:40:22'),
(350, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:42:23'),
(351, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 15:47:00'),
(352, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:32:58'),
(353, NULL, 'te test', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:33:08'),
(354, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:33:20'),
(355, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:42:37'),
(356, NULL, 'Brix brix', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:42:48'),
(357, NULL, 'Brix brix', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:44:29'),
(358, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:44:38'),
(359, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:47:02'),
(360, NULL, 'Jean Espejo', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:47:10'),
(361, NULL, 'Jean Espejo', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:50:26'),
(362, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 16:50:47'),
(363, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 19:44:30'),
(364, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 20:18:05'),
(365, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:15:06'),
(366, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:17:53'),
(367, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:03'),
(368, NULL, 'Brix brix', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:30'),
(369, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:43'),
(370, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:18:58'),
(371, NULL, 'Brix brix', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:19:07'),
(372, NULL, 'Brix brix', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:19:57'),
(373, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 22:33:23'),
(374, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:00:32'),
(375, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:00:45'),
(376, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:01:08'),
(377, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 00:01:18'),
(378, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:04:01'),
(379, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:04:17'),
(380, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:08:04'),
(381, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:08:22'),
(382, 46, 'Jean Claude', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:44:16'),
(383, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:44:30'),
(384, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:46:15'),
(385, NULL, 'diosdado aban', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:46:31'),
(386, NULL, 'diosdado aban', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:59:08'),
(387, NULL, 'diosdado aban', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:59:15'),
(388, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 01:59:23'),
(389, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:31:06'),
(390, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:51:58'),
(391, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:52:10'),
(392, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:52:51'),
(393, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:53:00'),
(394, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:54:02'),
(395, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:54:11'),
(396, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:58:48'),
(397, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:59:02'),
(398, 149, 'Jean Espejo', 'department_head', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:59:29'),
(399, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:00:17'),
(400, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:00:38'),
(401, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:23:23'),
(402, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:23:41'),
(403, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:53:28'),
(404, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:54:23'),
(405, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:54:59'),
(406, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:55:15'),
(407, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:00:25'),
(408, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:00:36'),
(409, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:00:55'),
(410, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:06:20'),
(411, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:09:59'),
(412, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:10:11'),
(413, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:10:37'),
(414, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:10:45'),
(415, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:43:56'),
(416, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:44:08'),
(417, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:47:58'),
(418, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:09'),
(419, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:46'),
(420, 8, 'Dean', 'hr', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:54'),
(421, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:48:59'),
(422, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:51:01'),
(423, 44, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:51:11'),
(424, 44, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:53:18'),
(425, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:53:31'),
(426, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:54:17'),
(427, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:54:38'),
(428, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:00:57'),
(429, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:01:05'),
(430, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:28'),
(431, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:40'),
(432, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:12:43'),
(433, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 01:35:37'),
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
(444, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:17:45'),
(445, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:18:02'),
(446, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:18:08');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(447, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:20:55'),
(448, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:21:06'),
(449, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:35:46'),
(450, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 02:35:57'),
(451, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:20:33'),
(452, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:22:21'),
(453, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:22:52'),
(454, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:27:49'),
(455, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:27:59'),
(456, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:16'),
(457, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:34:30'),
(458, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:57:36'),
(459, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:57:46'),
(460, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:58:00'),
(461, 45, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:58:15'),
(462, 45, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:59:02'),
(463, 46, 'Jean Claude', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 02:59:16'),
(464, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:49:29'),
(465, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:51:07'),
(466, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:51:47'),
(467, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:51:57'),
(468, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:52:06'),
(469, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:53:05'),
(470, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:53:41'),
(471, 153, 'Jean Espejo', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:54:14'),
(472, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:12:43'),
(473, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:13:51'),
(474, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:28:23'),
(475, 8, 'Dean', 'hr', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:28:30'),
(476, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:28:38'),
(477, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:07'),
(478, NULL, 'Name Testing', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:23'),
(479, NULL, 'Name Testing', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:50'),
(480, NULL, 'Name Testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:59:00'),
(481, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:59:12'),
(482, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:00:11'),
(483, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:00:23'),
(484, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:02:26'),
(485, NULL, 'mingdsada Ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:02:39'),
(486, NULL, 'mingdsada Ming', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:02:53'),
(487, NULL, 'mingdsada Ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:10:43'),
(488, NULL, 'Name Testing', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:16:25'),
(489, NULL, 'Name Testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:21:18'),
(490, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:21:38'),
(491, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:22:03'),
(492, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:22:09'),
(493, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:17'),
(494, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:29'),
(495, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:35'),
(496, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:23:43'),
(497, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:34:45'),
(498, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:14'),
(499, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:34'),
(500, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:41'),
(501, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:54'),
(502, NULL, 'Name Testing', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:35:59'),
(503, NULL, 'Name Testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 23:36:05'),
(504, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:01:56'),
(505, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:03:27'),
(506, 156, 'ming ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:03:39'),
(507, 156, 'ming ming', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:03:56'),
(508, 156, 'ming ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:16:56'),
(509, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:17:06'),
(510, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:22:10'),
(511, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:22:20'),
(512, 158, 'dwadsadwa Qwerty', 'secretary', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:22:48'),
(513, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:24:17'),
(514, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:24:45'),
(515, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:30:04'),
(516, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:30:14'),
(517, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:33:57'),
(518, 156, 'ming ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:34:11'),
(519, 156, 'ming ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 00:37:44'),
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
(533, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 02:58:38'),
(534, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 03:04:48'),
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
(556, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:28:08'),
(557, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:28:36'),
(558, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 00:28:41'),
(559, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 01:36:05'),
(560, 159, 'Claude Claude', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 01:36:24'),
(561, 159, 'Claude Claude', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:21:21'),
(562, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:21:33'),
(563, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:28:57'),
(564, 161, 'Mark Reyes', 'monitoring_officer', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:08'),
(565, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:25'),
(566, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:37'),
(567, 161, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:29:53'),
(568, 161, 'Mark Reyes', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 02:30:17'),
(569, 161, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:05:40'),
(570, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:06:20'),
(571, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:10:42'),
(572, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:10:59'),
(573, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:11:11'),
(574, NULL, 'Maria Santos', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:11:29'),
(575, NULL, 'Maria Santos', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 03:11:41'),
(576, 161, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 17:31:10'),
(577, 161, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:16:30'),
(578, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:16:42'),
(579, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:17:26'),
(580, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:17:35'),
(581, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:17:54'),
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
(594, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:32:43'),
(595, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:36:22'),
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
(606, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:17:30'),
(607, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:19:53'),
(608, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:20:02'),
(609, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:20:22'),
(610, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:20:32'),
(611, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:21:12'),
(612, 161, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:21:42'),
(613, 161, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:25:45'),
(614, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:29:04'),
(615, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:34:02'),
(616, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:34:20'),
(617, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:59:26'),
(618, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 22:59:38'),
(619, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:06:05'),
(620, 159, 'Claude Claude', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:06:18'),
(621, 159, 'Claude Claude', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:07:50'),
(622, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:08:00'),
(623, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 23:14:58'),
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
(653, 161, 'Mark Reyes', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:31'),
(654, 161, 'Mark Reyes', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:36'),
(655, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:41:50'),
(656, NULL, 'Maria Santos', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:02'),
(657, NULL, 'Maria Santos', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:20'),
(658, 158, 'dwadsadwa Qwerty', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:32'),
(659, 158, 'dwadsadwa Qwerty', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:42:49'),
(660, 156, 'ming ming', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:43:00'),
(661, 156, 'ming ming', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:45:19'),
(662, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:45:32'),
(663, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:46:24'),
(664, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:46:40'),
(665, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 02:48:23');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
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
(684, 172, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:16:44'),
(685, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:16:59'),
(686, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:19:15'),
(687, 172, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:19:32'),
(688, 172, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:19:43'),
(689, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:20:39'),
(690, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:31:00'),
(691, 172, '', 'faculty', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:31:33'),
(692, 172, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:31:33'),
(693, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:32:06'),
(694, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:33:44'),
(695, 173, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:33:54'),
(696, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:34:11'),
(697, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:34:34'),
(698, 173, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:35:08'),
(699, 173, '', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:35:21'),
(700, 173, '', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:39:56'),
(701, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:40:56'),
(702, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:08'),
(703, 174, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:17'),
(704, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:35'),
(705, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:44'),
(706, 174, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:42:57'),
(707, 174, '', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:43:09'),
(708, 174, '', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:44:45'),
(709, 149, 'Jean Espejo', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:44:56'),
(710, 149, 'Jean Espejo', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:55:05'),
(711, 174, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:55:24'),
(712, 174, 'Alexander.mercer@gmail.com', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:01:41'),
(713, 173, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:01:59'),
(714, 173, '', 'faculty', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:06'),
(715, 173, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:11'),
(716, 173, 'test testing', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:17'),
(717, 174, '', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:02:28'),
(718, 174, 'Alexander Mercer', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:06:38'),
(719, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:06:50'),
(720, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:06:54'),
(721, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:05'),
(722, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:17'),
(723, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:24'),
(724, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:30'),
(725, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:07:41'),
(726, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:31:47'),
(727, 175, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:31:57'),
(728, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:14'),
(729, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:24'),
(730, 175, 'Erwin Smith', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:39'),
(731, 175, 'Erwin Smith', 'faculty', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:32:56'),
(732, 175, 'Erwin Smith', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:33:29'),
(733, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:33:40'),
(734, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:34:16'),
(735, 175, 'Erwin Smith', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:34:28'),
(736, 175, 'Erwin Smith', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:39:26'),
(737, 175, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:39:36'),
(738, 175, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:52:36'),
(739, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:52:49'),
(740, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:58:18'),
(741, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 13:58:40'),
(742, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:09'),
(743, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:21'),
(744, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:42'),
(745, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:00:49'),
(746, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:01:11'),
(747, 177, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:01:22'),
(748, 177, 'Monitoring Officer', 'monitoring_officer', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:01:39'),
(749, 177, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:10:09'),
(750, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:10:22'),
(751, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:11:27'),
(752, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:11:37'),
(753, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:12:31'),
(754, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:12:44'),
(755, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:13:27'),
(756, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:13:40'),
(757, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:14:26'),
(758, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:14:41'),
(759, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:19:15'),
(760, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:19:24'),
(761, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:32:49'),
(762, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:33:06'),
(763, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:41:31'),
(764, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 14:42:10'),
(765, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 15:26:40'),
(766, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 15:27:07'),
(767, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:53:01'),
(768, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:53:45'),
(769, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:53:55'),
(770, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:13'),
(771, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:23'),
(772, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:42'),
(773, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:54:52'),
(774, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:55:08'),
(775, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 23:55:15'),
(776, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 00:13:23'),
(777, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 00:13:37'),
(778, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 01:59:10'),
(779, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 18:04:32'),
(780, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:07:36'),
(781, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:07:50'),
(782, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:11:16'),
(783, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:11:19'),
(784, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:12:04'),
(785, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:12:16'),
(786, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:13:03'),
(787, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:13:15'),
(788, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:25:23'),
(789, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:25:40'),
(790, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:30:30'),
(791, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:35:43'),
(792, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:22:54'),
(793, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:30:33'),
(794, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:30:44'),
(795, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:30:58'),
(796, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:31:13'),
(797, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:50:27'),
(798, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:50:36'),
(799, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:32:56'),
(800, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:33:31'),
(801, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:33:40'),
(802, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:36:51'),
(803, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:37:04'),
(804, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:22:26'),
(805, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:59:19'),
(806, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:59:29'),
(807, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:11:36'),
(808, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:11:44'),
(809, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:14:15'),
(810, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:14:26'),
(811, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:57:01'),
(812, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:21:38'),
(813, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:21:47'),
(814, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:35:31'),
(815, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 20:35:41'),
(816, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:19:45'),
(817, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:19:53'),
(818, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:20:20'),
(819, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:20:43'),
(820, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:21:45'),
(821, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 21:21:53'),
(822, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 22:57:07'),
(823, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 23:51:59'),
(824, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 23:52:06'),
(825, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:44:00'),
(826, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:10:34'),
(827, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:10:49'),
(828, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:11:10'),
(829, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:11:23'),
(830, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:23:52'),
(831, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:25:27'),
(832, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:31:34'),
(833, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:31:47'),
(834, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 13:27:30'),
(835, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 13:57:08'),
(836, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 13:57:24'),
(837, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:25:53'),
(838, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:26:02'),
(839, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:26:24'),
(840, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:26:38'),
(841, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:51:34'),
(842, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 14:51:49'),
(843, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 18:59:53'),
(844, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:08:16'),
(845, 178, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:08:27'),
(846, 178, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:08:41'),
(847, 178, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:16:02'),
(848, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:17:18'),
(849, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:08'),
(850, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:17'),
(851, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:25'),
(852, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:19:35'),
(853, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:22:33'),
(854, 178, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:22:42'),
(855, 178, '', 'faculty', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:26:17'),
(856, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:30:23'),
(857, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:33:17'),
(858, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:33:30'),
(859, 179, '', 'department_head', 'login_failed', 'System', 'Login blocked — account status: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:33:44'),
(860, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:39:06'),
(861, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:39:54'),
(862, 179, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:40:03'),
(863, 179, 'Department Head', 'department_head', 'password_change', 'System', 'Password changed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:40:15'),
(864, 179, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:50:13'),
(865, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 19:50:25'),
(866, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:01:05'),
(867, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:12:23'),
(868, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:12:39'),
(869, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:12:56'),
(870, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:14:14'),
(871, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:14:30'),
(872, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:26:27'),
(873, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:26:34'),
(874, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 20:27:56'),
(875, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 01:52:33'),
(876, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 01:58:05'),
(877, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 01:58:15'),
(878, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:20:03'),
(879, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:20:14'),
(880, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:32:09'),
(881, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:32:24'),
(882, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:41:48'),
(883, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:01'),
(884, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:27'),
(885, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:38');
INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(886, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:42:53'),
(887, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:43:04'),
(888, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 13:43:34'),
(889, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:10:55'),
(890, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:11:09'),
(891, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:13:33'),
(892, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:13:48'),
(893, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:27:10'),
(894, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:27:25'),
(895, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:27:49'),
(896, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:28:01'),
(897, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:29:43'),
(898, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:29:58'),
(899, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:37:07'),
(900, 159, 'Monitoring Officer', 'monitoring_officer', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:37:23'),
(901, 159, 'Monitoring Officer', 'monitoring_officer', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:38:01'),
(902, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:38:12'),
(903, 158, 'Secretary', 'secretary', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:44:55'),
(904, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:45:04'),
(905, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:52:48'),
(906, 153, 'Jean Espejo', 'faculty', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 14:52:59'),
(907, 153, 'Jean Espejo', 'faculty', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:04:26'),
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
(919, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:11:00'),
(920, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:11:39'),
(921, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:11:50'),
(922, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:12:02'),
(923, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:12:12'),
(924, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:12:56'),
(925, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:13:06'),
(926, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:16:26'),
(927, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:16:37'),
(928, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:00'),
(929, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:11'),
(930, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:31'),
(931, 8, 'Dean', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:18:41'),
(932, 8, 'Dean', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:29:35'),
(933, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:29:47'),
(934, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:31:29'),
(935, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:31:40'),
(936, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:32:09'),
(937, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:32:59'),
(938, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:39:17'),
(939, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:39:30'),
(940, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:05'),
(941, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:18'),
(942, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:33'),
(943, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:40:44'),
(944, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:41:15'),
(945, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:41:24'),
(946, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:46:26'),
(947, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 02:46:39'),
(948, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:04:34'),
(949, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:04:51'),
(950, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:04:57'),
(951, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:05:06'),
(952, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:13:54'),
(953, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:14:08'),
(954, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:16:07'),
(955, 158, 'Secretary', 'secretary', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 03:16:19'),
(956, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 00:51:57'),
(957, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:10:26'),
(958, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:10:36'),
(959, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:25:10'),
(960, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:25:22'),
(961, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:40:50'),
(962, 151, 'Faculty Admin', 'faculty_admin', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:41:04'),
(963, 151, 'Faculty Admin', 'faculty_admin', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:42:57'),
(964, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:43:06'),
(965, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:52:51'),
(966, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 01:53:08'),
(967, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:02:40'),
(968, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:02:51'),
(969, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:00'),
(970, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:13'),
(971, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:25'),
(972, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:03:35'),
(973, 149, 'Department Head', 'department_head', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:04'),
(974, 8, 'Dean', 'dean', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:18'),
(975, 8, 'Dean', 'dean', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:31'),
(976, 149, 'Department Head', 'department_head', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 02:04:41');

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
(4, 9, '55eabae148518a30c44e17552b678572afdda8d79fc2c59f95152780545da52b', '2026-08-06 14:18:33', NULL, '::1', '2026-08-06 13:18:33');

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
(185, 'faculty_admin', 'Faculty Admin', 'Administrative oversight of the faculty module', 1, '2026-08-15 01:14:06'),
(186, 'monitoring_officer', 'Monitoring Officer', 'Monitors faculty compliance and activity', 1, '2026-08-15 01:14:06'),
(218, 'dean', 'Dean', 'Oversees one or more academic departments', 1, '2026-08-24 02:26:16');

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
(133, 'faculty_admin', 'faculty', 1, '2026-08-15 01:14:06'),
(134, 'monitoring_officer', 'faculty', 1, '2026-08-15 01:14:06'),
(148, 'it_office', 'payment', 0, '2026-08-23 15:06:23');

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
(32, 1, 'passkey_remove', 'df8c90265057ad814bde1ac13e69cd5d1aa480375cd8a00f9e9b4a29e917b7fe', NULL, '2026-08-07 14:33:29', NULL, '2026-08-07 14:23:29');

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
(1, 'superadmin', 'kennethabejuela0308@gmail.com', '$2y$10$o48cjRxVOhYsuBWzhYqHHuHr0l2HLqSRERtOoe6viDpP0xRSgtQuu', 'Super Admin', 'superadmin', NULL, 'active', 0, 0, NULL, '2026-08-06 13:10:29', '2026-08-23 15:04:37', '2026-08-23 15:31:49', '::1', NULL, '2026-07-22 22:53:59', '2026-08-23 15:31:49'),
(2, 'registrar', 'registrar@bestlink.edu.ph', '$2y$10$IWZoE2QLtbfeoOh8n0VdkeSvNx9aNeiKGC6B5oWs1KzO5C08997eS', 'Registrar', 'registrar', NULL, 'active', 0, 0, NULL, '2026-08-06 13:16:14', NULL, NULL, NULL, NULL, '2026-07-22 22:53:59', '2026-08-06 13:16:14'),
(3, 'cradofficer', 'angelicadublin340@gmail.com', '$2y$10$MpwtxHnKofWTxV5/axRiPuudxLEFIdLJChvSoykU9poFiH/W9wRPK', 'CRAD Officer', 'crad_officer', NULL, 'active', 0, 0, NULL, '2026-08-06 13:31:50', '2026-08-07 22:01:51', '2026-08-07 22:07:41', '::1', NULL, '2026-07-22 22:53:59', '2026-08-07 22:07:41'),
(4, 'finance', 'monvictortesiorna@gmail.com', '$2y$10$mOPKz95hA/OlTNHGzzgLEuvYqMBNAE1RdQFThECQjfv94o.RbvIZq', 'Finance', 'finance', NULL, 'active', 0, 0, NULL, '2026-08-06 20:20:39', '2026-08-07 14:28:00', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-07 14:28:02'),
(5, 'studentaffairs', 'studentaffairs@bestlink.edu.ph', '$2y$10$lViM6fo1qu33TQ8G45UW6OF6op7etas9WBZ12cvQdEPSKuU7TGmXW', 'Student Affairs', 'osa', NULL, 'active', 0, 0, NULL, '2026-07-22 22:54:00', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-07-22 22:54:00'),
(6, 'itofficer', 'itofficer@bestlink.edu.ph', '$2y$10$fIFFgaSnSssf4ZdaYupnZ.fzX6dYDfE7escqc/GMedxVZUHCaqCPe', 'IT Officer', 'it_office', NULL, 'active', 0, 0, NULL, '2026-07-22 22:54:00', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-07-22 22:54:00'),
(7, 'qualityassurance', 'qualityassurance@bestlink.edu.ph', '$2y$10$Bm/Te5m0uFyTRDhDDV.lf.9HuUEe7qIUOfZtHXF2eufIIXL1N3IVC', 'Quality Assurance', 'qa', NULL, 'active', 0, 0, NULL, '2026-07-22 22:54:00', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-07-22 22:54:00'),
(8, 'dean', 'dean@bestlink.edu.ph', '$2y$10$gKCSAHM8.aeonS0qhutDeOuyVqtsuQneESNB4gP0tboWn4lyuCvP2', 'Dean', 'dean', NULL, 'active', 0, 0, NULL, '2026-08-09 18:27:53', '2026-08-25 02:04:18', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-25 02:04:32'),
(9, 's230000001', 'kenlangmalakas0308@gmail.com', '$2y$10$E0IiZOWMscnUfdX8H7gxt.5YzIkUqLK.qn07WF9MCA0StJhToFn2q', 'Student User', 'student', 'S230000001', 'active', 0, 0, NULL, '2026-08-06 13:17:16', '2026-08-07 15:27:51', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-07 15:29:05'),
(11, 'admission', 'admission@bestlink.edu.ph', '$2y$10$8wRzkkIHcsmsGHDwVhTXmeMlWGbHtylBJcQUV.8JuSspmUVuioP1G', 'Admission', 'admission', NULL, 'active', 0, 0, NULL, '2026-08-09 18:27:15', NULL, NULL, NULL, NULL, '2026-08-09 18:27:15', '2026-08-09 18:27:15'),
(12, 'rsantos', 'rsantos@bestlink.edu.ph', '$2y$10$V/hBEBlGrsqnT0fHRHkIqOEBnhTVCO15n8qa2YKrMd6DVzKJ0th6m', 'Dr. Roberto M. Santos', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-10 01:00:17', '2026-08-10 01:00:31', NULL, '::1', NULL, '2026-08-09 18:27:15', '2026-08-11 01:51:38'),
(13, 'jtan', 'jtan@bestlink.edu.ph', '$2y$10$WLfa8fhcrbqfRhlbVGjgHuoEGlHa40qNomWGWLYhxgnc2RtT6JrE2', 'Dr. Jose B. Tan', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-09 18:27:15', '2026-08-11 01:48:35', NULL, '::1', NULL, '2026-08-09 18:27:15', '2026-08-11 01:51:57'),
(44, 'departmenthead', 'departmenthead@bestlink.edu.ph', '$2y$10$zetuMf6PPbFBs9KybGEap.RwJc59VdWoUIseM7fvfQg2pKgaN463e', 'Department Head', 'department_head', NULL, 'active', 0, 0, NULL, '2026-08-10 01:52:53', '2026-08-14 13:51:11', NULL, '::1', NULL, '2026-08-10 01:31:00', '2026-08-14 13:53:18'),
(45, 'secretary', 'secretary@bestlink.edu.ph', '$2y$10$fKm8UsVAwGoQoo.1h8smw.7pnuxX0937LzFMPqRxyKT9wt2T7m5ye', 'Secretary', 'secretary', NULL, 'active', 0, 0, NULL, '2026-08-10 01:53:27', '2026-08-16 02:58:15', NULL, '::1', NULL, '2026-08-10 01:31:00', '2026-08-16 02:59:02'),
(46, 'faculty', 'faculty@bestlink.edu.ph', '$2y$10$R1uQbxKRilhWkaEeCMnNne6LsgMhRTt.i5F9Izk5N6ufS8WJUdDr.', 'Jean Claude', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-10 01:54:02', '2026-08-16 02:59:16', '2026-08-16 03:19:40', '::1', NULL, '2026-08-10 01:31:00', '2026-08-16 03:19:40'),
(149, 'claudeespejo@gmail.com', 'claudeespejo@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', 'Jean Espejo', 'department_head', NULL, 'active', 0, 0, NULL, '2026-08-14 11:59:29', '2026-08-25 02:04:41', '2026-08-25 02:49:24', '::1', NULL, '2026-08-14 11:58:31', '2026-08-25 02:49:24'),
(151, 'facultyadmin', 'facultyadmin@bestlink.edu.ph', '$2y$10$M6bAMgmDgxJvRvpNHhlkWOl2RaReNDeWjlKUVOrWgVOHu7wOwOLGC', 'Faculty Admin', 'faculty_admin', NULL, 'active', 0, 0, NULL, '2026-08-15 01:36:50', '2026-08-25 01:41:04', NULL, '::1', NULL, '2026-08-15 01:14:06', '2026-08-25 01:42:57'),
(152, 'monitoring', 'monitoring@bestlink.edu.ph', '$2y$10$VA/SJ7ZDJfaphs4elb5O7O1FxgCV2gakN7tMqG49T0UUf83OJmdO.', 'Monitoring Officer', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-15 01:41:50', '2026-08-17 00:45:17', NULL, '::1', NULL, '2026-08-15 01:14:06', '2026-08-17 01:32:54'),
(153, 'qwerty@gmail.com', 'qwerty@gmail.com', '$2y$10$71BcLxdqM1/UFcuZZPXx3.5Cn00miPmSXHP7IELpaDbzGO6i1xl12', 'Jean Espejo', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-16 18:54:14', '2026-08-23 14:52:59', NULL, '::1', NULL, '2026-08-16 18:52:52', '2026-08-23 15:04:26'),
(156, 'ming2026@gmail.com', 'ming2026@gmail.com', '$2y$10$rls6tsobGRkbHZRSOooYQe/02abDHDHkvpyL3gpzIQ4480A47w5Mu', 'ming ming', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-17 00:03:56', '2026-08-19 02:42:59', NULL, '::1', NULL, '2026-08-17 00:03:14', '2026-08-19 02:45:19'),
(158, 'asdfghjkl@gmail.com', 'asdfghjkl@gmail.com', '$2y$10$krRJZwchT6S4kL9sEoiOy.mp4XhL1THTugqcDFJk5wOtOCNWFOxB2', 'dwadsadwa Qwerty', 'secretary', NULL, 'active', 0, 0, NULL, '2026-08-17 00:22:48', '2026-08-24 03:16:19', '2026-08-24 03:16:19', '::1', NULL, '2026-08-17 00:21:59', '2026-08-24 03:16:19'),
(159, 'jeanie@gmail.com', 'jeanie@gmail.com', '$2y$10$XT67xyI5B6ebSjLVKaXwoOHmmGsD79vo6GJX11HYYlBiPqgJr9MM6', 'Claude Claude', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-17 01:42:24', '2026-08-23 14:37:23', NULL, '::1', NULL, '2026-08-17 01:40:22', '2026-08-23 14:38:01'),
(161, 'mark.reyes.test@gmail.com', 'mark.reyes.test@gmail.com', '$2y$10$SYwGtQIZKZ6s1ep0Q6TUX.v6O6NwTyWB2GSY1lLgfFfLPQVHeRise', 'Mark Reyes', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-18 02:30:17', '2026-08-19 02:41:31', NULL, '::1', NULL, '2026-08-18 02:28:50', '2026-08-19 02:41:36'),
(165, 'qwertyuiop@gmail.com', 'qwertyuiop@gmail.com', '$2y$10$gOu6spIaATuzzPDo0dJbBeyWWe.wV3yL/CH65TdJYIJmMGD8zmXCa', '', 'faculty', NULL, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-18 23:19:42', '2026-08-18 23:19:42'),
(167, 'jdiojfioejdpoaskd@gmail.com', 'jdiojfioejdpoaskd@gmail.com', '$2y$10$PLm6aS1o2u1mXOnS6KSEnuWjYxH.4A8o2FhAEql3IR1IzOWBGebdG', '', 'faculty', NULL, 'active', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-18 23:37:32', '2026-08-19 02:39:47'),
(172, 'monkeydluffy@gmail.com', 'monkeydluffy@gmail.com', '$2y$10$XV88FnRB8VChr1LJH851A./Yn.R2QwurNnZJOG1K4XgApRysjpJXi', '', 'faculty', NULL, 'locked', 0, 3, '2026-08-19 12:31:48', NULL, NULL, NULL, NULL, NULL, '2026-08-19 12:15:54', '2026-08-19 12:31:33'),
(173, 'testing123@gmail.com', 'testing123@gmail.com', '$2y$10$rQejomkPPYLX9Olm83WFFODC1CPEUPSOzrJXnft8o8WTo9Y3kjIom', '', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-19 12:35:21', '2026-08-19 13:02:11', NULL, '::1', NULL, '2026-08-19 12:33:40', '2026-08-19 13:02:17'),
(174, 'alexander.mercer@gmail.com', 'alexander.mercer@gmail.com', '$2y$10$6TQErBTAwm.wRRq/dnR.teGPudGg2e9I.Qs0Eva8mgTklh4/i8en6', '', 'faculty', NULL, 'active', 0, 0, NULL, '2026-08-19 12:43:09', '2026-08-19 13:02:27', NULL, '::1', NULL, '2026-08-19 12:42:04', '2026-08-19 13:06:38'),
(175, 'erwin.smith@gmail.com', 'erwin.smith@gmail.com', '$2y$10$qBahTLwAFEvX60JI.SMQXe23YgbDYLMcvNgcIVa0c5d5B0MXLicdW', 'Erwin Smith', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-19 13:32:56', '2026-08-19 13:39:36', NULL, '::1', NULL, '2026-08-19 13:31:32', '2026-08-19 13:52:36'),
(176, 'juan.delacruz@example.com', 'juan.delacruz@example.com', '$2y$10$kN4pgCXaMF5LVXHcNkH9XeP7CvfOgCG.o9ElRPtnCNokzjPnluTL2', '', 'faculty', NULL, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-19 13:58:14', '2026-08-19 13:58:14'),
(177, 'juan.delacruztesting@example.com', 'juan.delacruztesting@example.com', '$2y$10$jF7SPxC.ynsECm9c/9rneevXcz0GIUjIGQtcdA1odiR8I2jXQtBbK', 'juan cruz', 'monitoring_officer', NULL, 'active', 0, 0, NULL, '2026-08-19 14:01:39', '2026-08-19 14:01:22', NULL, '::1', NULL, '2026-08-19 14:00:01', '2026-08-19 14:10:09'),
(178, 'rivera@gmail.com', 'rivera@gmail.com', '$2y$10$AH1cgkTlRtsxB0fQVU7UB.sINBL0iSTehtLnY3.KwA27plquRX2SS', '', 'faculty', NULL, '', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-22 19:07:58', '2026-08-22 19:25:36'),
(179, 'michaeljordan@gmail.com', 'michaeljordan@gmail.com', '$2y$10$2W2VmX5KPLJsYiQDRiNnRe/HBSv9oFFxouRWmkAOv6HakLZXbGb1W', 'michael jordan', 'department_head', NULL, 'active', 0, 0, NULL, '2026-08-22 19:40:15', '2026-08-22 19:40:03', NULL, '::1', NULL, '2026-08-22 19:32:46', '2026-08-22 19:50:13');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=977;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `security_otps`
--
ALTER TABLE `security_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
