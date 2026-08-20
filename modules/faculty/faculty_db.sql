-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2026 at 09:22 AM
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
-- Table structure for table `clearance_items`
--

CREATE TABLE `clearance_items` (
  `clearance_item_id` int(10) UNSIGNED NOT NULL,
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `clearance_office_id` int(10) UNSIGNED NOT NULL,
  `status` enum('Missing','Pending Review','Cleared','Hold') NOT NULL DEFAULT 'Pending Review',
  `remarks` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
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
  `sequence_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_requests`
--

CREATE TABLE `clearance_requests` (
  `clearance_id` int(10) UNSIGNED NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `term_id` int(10) UNSIGNED NOT NULL,
  `intent_type` enum('renewal','resignation','regularization') NOT NULL,
  `overall_status` enum('Cleared','In Progress','With Hold') NOT NULL DEFAULT 'In Progress',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
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
(1, NULL, 'BSIT', 'Bachelor of Science in Information Technology', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-16 18:23:41'),
(2, NULL, 'BSED', 'Bachelor of Secondary Education', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-16 18:57:56'),
(3, NULL, 'BS CRIM', 'Bachelor of Science in Criminology', NULL, 'Active', '2026-08-16 18:18:47', '2026-08-16 18:18:47'),
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
  `position` enum('Faculty Secretary','Faculty Professor') NOT NULL DEFAULT 'Faculty Professor',
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
  `designated_department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `specialization_assignment` varchar(255) DEFAULT NULL,
  `is_coordinator` tinyint(1) NOT NULL DEFAULT 0,
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

INSERT INTO `faculty_profiles` (`id`, `faculty_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthdate`, `age`, `sex`, `phone`, `email`, `designated_department`, `position`, `specialization_assignment`, `is_coordinator`, `coordinator_type`, `tier`, `hired_date`, `contractual_end`, `contractual_end_date`, `employment_status`, `profile_status`, `request_status`, `created_at`, `updated_at`, `academic_rank`, `education_attainment`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_relationship`, `user_id`) VALUES
(31, 'FAC-2026-0006', 'Brix', 'brax', 'brix', '', '2006-06-06', 20, 'MALE', '09318298352', 'brix2026@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2026-07-03', '2026-08-14', NULL, 'probationary', 'Active', 'approved', '2026-08-13 08:39:57', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'FAC-2026-0007', 'Jean', 'Claude', 'Espejo', '', '2003-06-10', 23, 'MALE', '09318298352', 'jeanespejo@gmail.com', 'BSIT', 'Department Secretary', NULL, 0, NULL, NULL, '2025-06-10', '0000-00-00', NULL, 'regular', 'Active', 'approved', '2026-08-13 08:46:17', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'FAC-2026-0008', 'diosdado', 'diosdado', 'aban', 'jy', '2014-07-23', 12, 'MALE', '09318298352', 'jraban@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2025-06-10', '2027-11-18', NULL, 'probationary', 'Active', 'approved', '2026-08-13 17:45:39', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 145),
(34, 'FAC-2026-0004', 'Jean', 'Claude', 'Espejo', '', '2000-05-17', 26, 'MALE', '09318298352', 'claudeespejo@gmail.com', 'BSIT', 'Department Head', NULL, 0, NULL, NULL, '2023-01-30', '0000-00-00', NULL, 'regular', 'Active', 'approved', '2026-08-14 03:58:31', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 149),
(35, 'FAC-2026-0005', 'Jean', 'Claude', 'Espejo', '', '2001-02-05', 25, 'MALE', '09318298352', 'qwerty@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2025-10-14', '2031-06-10', NULL, 'probationary', 'Active', 'approved', '2026-08-16 10:52:52', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 153),
(38, 'FAC-2026-0006', 'ming', 'ming', 'ming', '', '2005-10-11', 20, 'MALE', '09318298352', 'ming2026@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2026-02-17', '0000-00-00', NULL, 'regular', 'Active', 'approved', '2026-08-16 16:03:14', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 156),
(39, 'FAC-2026-0007', 'dwadsadwa', 'adawda', 'Qwerty', '', '1997-06-09', 29, 'MALE', '09318298352', 'asdfghjkl@gmail.com', 'BSIT', 'Faculty Secretary', NULL, 0, NULL, NULL, '2025-06-10', '2028-10-17', NULL, 'part-time', 'Active', 'approved', '2026-08-16 16:21:59', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 158),
(40, 'FAC-2026-0008', 'Claude', 'O.', 'Claude', '', '2007-10-17', 18, 'MALE', '09318298352', 'jeanie@gmail.com', 'BSIT', 'Attendance Monitoring Officer', NULL, 0, NULL, NULL, '2026-08-08', '2028-07-05', NULL, 'probationary', 'Active', 'approved', '2026-08-16 17:40:22', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 159),
(42, 'FAC-2026-0010', 'Mark', 'Mark', 'Reyes', '', '1999-06-15', 27, 'MALE', '09318298352', 'mark.reyes.test@gmail.com', 'BSIT', 'Attendance Monitoring Officer', NULL, 0, NULL, NULL, '2023-06-06', '2027-10-19', NULL, 'probationary', 'Active', 'approved', '2026-08-17 18:28:50', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 161),
(43, 'FAC-2026-0011', 'Maria', 'Maria', 'Santos', '', '1992-11-18', 33, 'MALE', '09318298352', 'maria.santos.test@gmail.com', 'BSIT', 'Attendance Monitoring Officer', NULL, 0, NULL, NULL, '2017-07-05', '0000-00-00', NULL, 'regular', 'Active', 'approved', '2026-08-17 19:10:33', '2026-08-18 13:44:06', NULL, NULL, NULL, NULL, NULL, NULL, 162),
(47, 'FAC-2026-0047', 'dsadwadsa', 'dsadwadsa', 'asdfg', '', '2019-01-14', 7, 'MALE', '09318298352', 'jdiojfioejdpoaskd@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2025-06-11', NULL, NULL, 'regular', 'Active', 'pending', '2026-08-18 15:37:32', '2026-08-18 18:39:47', NULL, NULL, NULL, NULL, NULL, NULL, 167),
(50, 'FAC-2026-0048', 'monkey', 'd', 'luffy', '', '1998-02-09', 28, 'MALE', '09318298352', 'monkeydluffy@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2023-07-06', NULL, NULL, 'regular', 'Active', 'pending', '2026-08-19 04:15:54', '2026-08-19 04:17:04', NULL, NULL, NULL, NULL, NULL, NULL, 172),
(51, 'FAC-2026-0051', 'test', 'testing', 'testing', '', '2015-02-13', 11, 'MALE', '09318298352', 'testing123@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2026-06-10', NULL, NULL, 'regular', 'Active', 'pending', '2026-08-19 04:33:40', '2026-08-19 04:34:24', NULL, NULL, NULL, NULL, NULL, NULL, 173),
(52, 'FAC-2026-0052', 'Alexander', 'M', 'Mercer', '', '2006-03-01', 20, 'MALE', '09318298352', 'alexander.mercer@gmail.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2020-11-19', '2028-11-15', NULL, 'probationary', 'Active', 'pending', '2026-08-19 04:42:04', '2026-08-19 04:42:39', NULL, NULL, NULL, NULL, NULL, NULL, 174),
(53, 'FAC-2026-0053', 'Erwin', 's', 'Smith', '', '1997-06-10', 29, 'MALE', '09318298352', 'erwin.smith@gmail.com', 'BSIT', 'Attendance Monitoring Officer', NULL, 0, NULL, NULL, '2025-10-14', '2027-11-17', NULL, 'probationary', 'Active', 'pending', '2026-08-19 05:31:32', '2026-08-19 05:32:19', NULL, NULL, NULL, NULL, NULL, NULL, 175),
(54, 'FAC-2026-0054', 'jaun', 'd', 'cruz', '', '2005-03-09', 21, 'MALE', '09318298352', 'juan.delacruz@example.com', 'BSIT', 'Faculty Professor', NULL, 0, NULL, NULL, '2026-06-09', '2026-08-06', NULL, 'probationary', 'Pending Approval', 'pending', '2026-08-19 05:58:14', '2026-08-19 05:58:14', NULL, NULL, NULL, NULL, NULL, NULL, 176),
(55, 'FAC-2026-0055', 'juan', 'h', 'cruz', '', '2003-01-28', 23, 'MALE', '09318298352', 'juan.delacruztesting@example.com', 'BSIT', 'Attendance Monitoring Officer', NULL, 0, NULL, NULL, '2026-06-16', NULL, NULL, 'regular', 'Active', 'pending', '2026-08-19 06:00:01', '2026-08-19 06:01:01', NULL, NULL, NULL, NULL, NULL, NULL, 177);

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
  `generated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `leave_id` int(10) UNSIGNED NOT NULL,
  `leave_no` varchar(20) NOT NULL,
  `faculty_id` int(10) UNSIGNED NOT NULL,
  `leave_type` enum('Vacation Leave','Sick Leave','Emergency Leave','Study Leave') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(10) UNSIGNED NOT NULL,
  `reason` text DEFAULT NULL,
  `documents_status` enum('Complete','Incomplete') NOT NULL DEFAULT 'Incomplete',
  `screening_status` enum('Pending','Screened','Returned') NOT NULL DEFAULT 'Pending',
  `screened_by_external_id` varchar(64) DEFAULT NULL,
  `screened_at` datetime DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `approved_by_external_id` varchar(64) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `filed_at` datetime NOT NULL DEFAULT current_timestamp()
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
-- Indexes for table `clearance_requests`
--
ALTER TABLE `clearance_requests`
  ADD PRIMARY KEY (`clearance_id`),
  ADD UNIQUE KEY `uq_clearance_faculty_term` (`faculty_id`,`term_id`),
  ADD KEY `fk_clearance_term` (`term_id`);

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
-- Indexes for table `faculty_deadlines`
--
ALTER TABLE `faculty_deadlines`
  ADD PRIMARY KEY (`deadline_id`),
  ADD KEY `fk_deadlines_campus` (`campus_id`),
  ADD KEY `fk_deadlines_faculty` (`faculty_id`);

--
-- Indexes for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD PRIMARY KEY (`entitlement_id`),
  ADD UNIQUE KEY `uq_faculty_year_entitlement` (`faculty_id`,`academic_year`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD UNIQUE KEY `uq_leave_no` (`leave_no`),
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
  MODIFY `term_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `attendance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `campus_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_attendance_sessions`
--
ALTER TABLE `class_attendance_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `class_schedule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_items`
--
ALTER TABLE `clearance_items`
  MODIFY `clearance_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_offices`
--
ALTER TABLE `clearance_offices`
  MODIFY `clearance_office_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearance_requests`
--
ALTER TABLE `clearance_requests`
  MODIFY `clearance_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `evaluation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  MODIFY `evaluation_category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_feedback`
--
ALTER TABLE `evaluation_feedback`
  MODIFY `evaluation_feedback_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_deadlines`
--
ALTER TABLE `faculty_deadlines`
  MODIFY `deadline_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `faculty_profile_details`
--
ALTER TABLE `faculty_profile_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `generated_reports`
--
ALTER TABLE `generated_reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  MODIFY `entitlement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `faculty_profile_details`
--
ALTER TABLE `faculty_profile_details`
  ADD CONSTRAINT `fk_profile_details_profile` FOREIGN KEY (`faculty_profile_id`) REFERENCES `faculty_profiles` (`id`) ON DELETE CASCADE;

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
