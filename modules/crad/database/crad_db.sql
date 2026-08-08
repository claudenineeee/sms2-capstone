-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2026 at 12:05 PM
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
-- Database: `crad_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `proposal_documents`
--

CREATE TABLE `proposal_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED NOT NULL,
  `doc_key` varchar(60) NOT NULL COMMENT 'Slot key: manuscript, approval, abstract, etc.',
  `doc_title` varchar(200) NOT NULL,
  `original_name` varchar(300) NOT NULL,
  `stored_name` varchar(300) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bytes',
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proposal_documents`
--

INSERT INTO `proposal_documents` (`id`, `proposal_id`, `doc_key`, `doc_title`, `original_name`, `stored_name`, `file_size`, `uploaded_at`) VALUES
(95, 13, 'manuscript', 'Research Manuscript', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'c1718417bdd0fad24eee05e4336023a7.docx', 351388, '2026-08-08 17:02:31'),
(96, 13, 'approval', 'Approval Sheet', 'Untitled.png', '36cc670609a68bf7e18e1c4a9c11485d.png', 1893327, '2026-08-08 17:02:31'),
(97, 13, 'abstract', 'Abstract', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'da8f7681a78a86b10536f9c43f4921f4.docx', 351388, '2026-08-08 17:02:31'),
(98, 13, 'certificate_adviser', 'Certificate of Technical Adviser and Grammarian', 'Untitled.png', 'e84c1f1e077473d9ba787d5c0dccaa46.png', 1893327, '2026-08-08 17:02:31'),
(99, 13, 'certificate_originality', 'Certificate of Originality', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'f454d02bee0e3e006cb406d58598961a.docx', 351388, '2026-08-08 17:02:31'),
(100, 13, 'receipt_screenshot', 'Screenshot of the Receipt', 'Untitled.png', 'fe63e9fc12266721eecc187603c52049.png', 1893327, '2026-08-08 17:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_drafts`
--

CREATE TABLE `proposal_drafts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users (optional)',
  `revision_ref` varchar(30) NOT NULL DEFAULT '' COMMENT 'Returned proposal ref when draft is for revision',
  `draft_data` longtext NOT NULL COMMENT 'JSON encoded draft form fields except upload files',
  `signature_data` mediumtext DEFAULT NULL COMMENT 'Base64 PNG of representative signature draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal_members`
--

CREATE TABLE `proposal_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = lead member',
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proposal_members`
--

INSERT INTO `proposal_members` (`id`, `proposal_id`, `sort_order`, `student_id`, `student_name`, `email`, `contact`) VALUES
(15, 13, 1, 'S230000001', 'User, Student A.', 's230000001@bcp.edu.ph', '09171234567');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_status_logs`
--

CREATE TABLE `proposal_status_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users',
  `remarks` text DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proposal_status_logs`
--

INSERT INTO `proposal_status_logs` (`id`, `proposal_id`, `old_status`, `new_status`, `changed_by`, `remarks`, `changed_at`) VALUES
(43, 13, NULL, 'Submitted', 9, 'Initial submission via Student Portal', '2026-08-08 17:02:31'),
(44, 13, 'Submitted', 'Approved', NULL, 'CRAD Officer approved proposal', '2026-08-08 17:03:26'),
(45, 13, 'Approved', 'Approved', 3, 'Registered approved proposal as CRD-2026-00013', '2026-08-08 17:03:28');

-- --------------------------------------------------------

--
-- Table structure for table `research_adviser_assignments`
--

CREATE TABLE `research_adviser_assignments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) DEFAULT NULL,
  `adviser_name` varchar(150) NOT NULL DEFAULT '',
  `adviser_email` varchar(190) NOT NULL DEFAULT '',
  `expertise` varchar(255) NOT NULL DEFAULT '',
  `availability_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `assignment_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_raa_group` (`research_group_id`),
  KEY `idx_raa_proposal` (`proposal_id`),
  KEY `idx_raa_group_number` (`group_number`),
  KEY `idx_raa_status` (`assignment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_adviser_assignments`
--

INSERT INTO `research_adviser_assignments` (`id`, `research_group_id`, `proposal_id`, `proposal_number`, `group_number`, `adviser_name`, `adviser_email`, `expertise`, `availability_status`, `assignment_status`, `notes`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`) VALUES
(1, 7, 13, 'CRD-2026-00013', 'RG-2026-001', 'Dr. Roberto M. Santos', 'rsantos@bestlink.edu.ph', 'Artificial Intelligence / Machine Learning', 'Available', 'Pending', 'Matched based on AI research topic; ready for coordinator contact.', 40, '2026-08-08 18:30:00', '2026-08-08 18:30:00', '2026-08-08 18:30:00'),
(2, 7, 13, 'CRD-2026-00013', 'RG-2026-001', 'Prof. Clara T. Reyes', 'creyes@bestlink.edu.ph', 'Data Analytics / Educational Technology', 'Available', 'Pending', 'Secondary adviser match for AI-assisted title review.', 40, '2026-08-08 18:31:00', '2026-08-08 18:31:00', '2026-08-08 18:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `research_panel_assignments`
--

CREATE TABLE `research_panel_assignments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) DEFAULT NULL,
  `panel_name` varchar(150) NOT NULL DEFAULT '',
  `panel_email` varchar(190) NOT NULL DEFAULT '',
  `panel_role` varchar(80) NOT NULL DEFAULT '',
  `expertise` varchar(255) NOT NULL DEFAULT '',
  `availability_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `assignment_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rpa_group` (`research_group_id`),
  KEY `idx_rpa_proposal` (`proposal_id`),
  KEY `idx_rpa_group_number` (`group_number`),
  KEY `idx_rpa_status` (`assignment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_panel_assignments`
--

INSERT INTO `research_panel_assignments` (`id`, `research_group_id`, `proposal_id`, `proposal_number`, `group_number`, `panel_name`, `panel_email`, `panel_role`, `expertise`, `availability_status`, `assignment_status`, `notes`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`) VALUES
(1, 7, 13, 'CRD-2026-00013', 'RG-2026-001', 'Dr. Jose B. Tan', 'jtan@bestlink.edu.ph', 'Panel Chair', 'Systems Development / AI Evaluation', 'Available', 'Pending', 'Recommended panel chair for technical AI assessment.', 40, '2026-08-08 18:32:00', '2026-08-08 18:32:00', '2026-08-08 18:32:00'),
(2, 7, 13, 'CRD-2026-00013', 'RG-2026-001', 'Prof. Nina G. Cruz', 'ncruz@bestlink.edu.ph', 'Panel Member', 'Research Methods / Data Analysis', 'Available', 'Pending', 'Recommended panel member for methodology and validation review.', 40, '2026-08-08 18:33:00', '2026-08-08 18:33:00', '2026-08-08 18:33:00');

-- --------------------------------------------------------

--
-- Table structure for table `research_groups`
--

CREATE TABLE `research_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) NOT NULL,
  `group_name` varchar(40) NOT NULL DEFAULT '',
  `research_title` varchar(255) NOT NULL DEFAULT '',
  `college_dept` varchar(120) NOT NULL DEFAULT '',
  `adviser` varchar(120) NOT NULL DEFAULT '',
  `academic_year` varchar(20) NOT NULL DEFAULT '',
  `leader_name` varchar(120) NOT NULL DEFAULT '',
  `leader_id` varchar(40) NOT NULL DEFAULT '',
  `leader_email` varchar(120) NOT NULL DEFAULT '',
  `leader_contact` varchar(40) NOT NULL DEFAULT '',
  `status` varchar(40) NOT NULL DEFAULT 'Approved',
  `date_assigned` date NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_groups`
--

INSERT INTO `research_groups` (`id`, `proposal_id`, `proposal_number`, `group_number`, `group_name`, `research_title`, `college_dept`, `adviser`, `academic_year`, `leader_name`, `leader_id`, `leader_email`, `leader_contact`, `status`, `date_assigned`, `created_by`, `created_at`) VALUES
(7, 13, 'CRD-2026-00013', 'RG-2026-001', 'Group 01', 'AI ASSISTEND TITLE OPENGPT', 'College of Computer Studies', 'Dr. Roberto M. Santos', 'A.Y. 2026-2027', 'User, Student A.', 'S230000001', 's230000001@bcp.edu.ph', '09171234567', 'Approved', '2026-08-08', 3, '2026-08-08 09:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `research_proposals`
--

CREATE TABLE `research_proposals` (
  `id` int(10) UNSIGNED NOT NULL,
  `ref_code` varchar(30) NOT NULL COMMENT 'Auto-generated reference e.g. CRD-2026-00001',
  `proposal_number` varchar(30) DEFAULT NULL COMMENT 'Official number generated after approved proposal registration',
  `research_title` varchar(500) NOT NULL,
  `program_course` varchar(200) NOT NULL,
  `year_section` varchar(100) NOT NULL,
  `college_department` varchar(200) NOT NULL,
  `research_adviser` varchar(200) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `rep_name` varchar(200) NOT NULL,
  `rep_id` varchar(50) NOT NULL,
  `rep_email` varchar(200) NOT NULL,
  `rep_contact` varchar(20) NOT NULL,
  `status` enum('Submitted','In Progress','Panel Assigned','Approved','Returned') NOT NULL DEFAULT 'Submitted',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Progress % shown in tracking',
  `date_submitted` date NOT NULL,
  `approved_at` datetime DEFAULT NULL COMMENT 'Date/time when tracking proposal was approved',
  `registered_at` datetime DEFAULT NULL COMMENT 'Date/time when approved proposal received official proposal number',
  `registration_status` enum('Pending','Registered') NOT NULL DEFAULT 'Pending',
  `signature_data` mediumtext DEFAULT NULL COMMENT 'Base64 PNG of representative signature',
  `submitted_by_user` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users (optional)',
  `notes` text DEFAULT NULL COMMENT 'CRAD officer notes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_proposals`
--

INSERT INTO `research_proposals` (`id`, `ref_code`, `proposal_number`, `research_title`, `program_course`, `year_section`, `college_department`, `research_adviser`, `academic_year`, `rep_name`, `rep_id`, `rep_email`, `rep_contact`, `status`, `progress`, `date_submitted`, `approved_at`, `registered_at`, `registration_status`, `signature_data`, `submitted_by_user`, `notes`, `created_at`, `updated_at`) VALUES
(13, 'CRD-2026-00001', 'CRD-2026-00013', 'AI ASSISTEND TITLE OPENGPT', 'BS in Information Technology', 'BSIT 4101', 'College of Computer Studies', 'Dr. Roberto M. Santos', 'A.Y. 2026-2027', 'User, Student A.', 'S230000001', 's230000001@bcp.edu.ph', '09171234567', 'Approved', 100, '2026-08-08', '2026-08-08 17:03:26', '2026-08-08 17:03:28', 'Registered', 'data:image/png;base64,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', 9, NULL, '2026-08-08 17:02:31', '2026-08-08 17:03:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pd_proposal` (`proposal_id`);

--
-- Indexes for table `proposal_drafts`
--
ALTER TABLE `proposal_drafts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proposal_members`
--
ALTER TABLE `proposal_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proposal` (`proposal_id`);

--
-- Indexes for table `proposal_status_logs`
--
ALTER TABLE `proposal_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_psl_proposal` (`proposal_id`);

--
-- Indexes for table `research_groups`
--
ALTER TABLE `research_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_number` (`group_number`),
  ADD UNIQUE KEY `proposal_id` (`proposal_id`),
  ADD KEY `idx_rg_proposal_number` (`proposal_number`);

--
-- Indexes for table `research_proposals`
--
ALTER TABLE `research_proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD UNIQUE KEY `proposal_number` (`proposal_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dept` (`college_department`(50)),
  ADD KEY `idx_submitted` (`date_submitted`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `proposal_drafts`
--
ALTER TABLE `proposal_drafts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `proposal_members`
--
ALTER TABLE `proposal_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `proposal_status_logs`
--
ALTER TABLE `proposal_status_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `research_groups`
--
ALTER TABLE `research_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `research_proposals`
--
ALTER TABLE `research_proposals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  ADD CONSTRAINT `fk_pd_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `proposal_members`
--
ALTER TABLE `proposal_members`
  ADD CONSTRAINT `fk_pm_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `proposal_status_logs`
--
ALTER TABLE `proposal_status_logs`
  ADD CONSTRAINT `fk_psl_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
