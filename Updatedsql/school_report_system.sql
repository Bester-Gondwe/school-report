-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 05:03 AM
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
-- Database: `school_report_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `created_at`) VALUES
(1, 'Form 1', '2025-02-12 01:55:36'),
(2, 'Form 2', '2025-02-12 01:55:36'),
(3, 'Form 3', '2025-02-12 01:55:36'),
(4, 'Form 4', '2025-02-12 01:55:36');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `total_marks` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subject` varchar(100) DEFAULT NULL,
  `marks_obtained` int(11) NOT NULL,
  `term` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `grade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `student_id`, `subject_id`, `total_marks`, `created_at`, `subject`, `marks_obtained`, `term`, `year`, `grade`) VALUES
(73, 21, 2, 100, '2025-02-14 16:40:38', NULL, 12, 'Term One', 2025, 0),
(81, 21, 2, 100, '2025-02-14 16:56:10', NULL, 12, 'Term One', 2025, 0),
(83, 21, 4, NULL, '2025-02-14 17:38:07', NULL, 23, '', 0, 0),
(84, 21, 4, NULL, '2025-02-14 17:38:11', NULL, 23, '', 0, 0),
(85, 22, 11, NULL, '2025-02-14 18:19:45', NULL, 23, '', 0, 0),
(86, 32, 1, NULL, '2025-10-04 02:51:12', NULL, 23, '1', 0, 1),
(87, 32, 2, NULL, '2025-10-04 02:51:12', NULL, 35, '1', 0, 1),
(88, 32, 3, NULL, '2025-10-04 02:51:12', NULL, 55, '1', 0, 1),
(89, 32, 4, NULL, '2025-10-04 02:51:12', NULL, 55, '1', 0, 1),
(90, 32, 5, NULL, '2025-10-04 02:51:12', NULL, 34, '1', 0, 1),
(91, 32, 6, NULL, '2025-10-04 02:51:12', NULL, 66, '1', 0, 1),
(92, 32, 7, NULL, '2025-10-04 02:51:12', NULL, 78, '1', 0, 1),
(93, 32, 8, NULL, '2025-10-04 02:51:12', NULL, 89, '1', 0, 1),
(94, 32, 9, NULL, '2025-10-04 02:51:12', NULL, 45, '1', 0, 1),
(95, 32, 10, NULL, '2025-10-04 02:51:12', NULL, 99, '1', 0, 1),
(96, 32, 11, NULL, '2025-10-04 02:51:12', NULL, 45, '1', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `rankings`
--

CREATE TABLE `rankings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_marks` int(11) NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `term` enum('Term 1','Term 2','Term 3') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_cards`
--

CREATE TABLE `report_cards` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `term` enum('Term 1','Term 2','Term 3') NOT NULL,
  `year` year(4) NOT NULL,
  `total_marks` int(11) NOT NULL,
  `average_marks` float NOT NULL,
  `rank` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_cards`
--

INSERT INTO `report_cards` (`id`, `student_id`, `term`, `year`, `total_marks`, `average_marks`, `rank`, `created_at`, `grade`) VALUES
(51, 21, 'Term 1', '0000', 35, 3.18182, 1, '2025-02-14 18:19:48', 0),
(52, 22, 'Term 1', '0000', 23, 2.09091, 2, '2025-02-14 18:19:48', 0),
(53, 23, 'Term 1', '0000', 0, 0, 3, '2025-02-14 18:19:48', 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `permissions`, `created_at`) VALUES
(1, 'admin', 'System Administrator', '{\"manage_users\": true, \"manage_teachers\": true, \"manage_students\": true, \"view_all_data\": true, \"assign_roles\": true, \"system_settings\": true}', '2025-10-04 02:34:24'),
(2, 'teacher', 'Teacher', '{\"manage_students\": true, \"input_marks\": true, \"view_student_data\": true, \"generate_reports\": true}', '2025-10-04 02:34:24'),
(3, 'student', 'Student', '{\"view_own_data\": true, \"view_report_card\": true}', '2025-10-04 02:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `examination_number` varchar(50) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `term` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_classes`
--

CREATE TABLE `student_classes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `term` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `created_at`) VALUES
(1, 'Mathematics', '2025-02-12 02:04:31'),
(2, 'English', '2025-02-12 02:04:31'),
(3, 'Science', '2025-02-12 02:04:31'),
(4, 'English Language', '2025-02-14 02:41:54'),
(5, 'History', '2025-02-14 02:41:54'),
(6, 'Geography', '2025-02-14 02:41:54'),
(7, 'Computer Studies', '2025-02-14 02:41:54'),
(8, 'Physics', '2025-02-14 02:41:54'),
(9, 'Chemistry', '2025-02-14 02:41:54'),
(10, 'Biology', '2025-02-14 02:41:54'),
(11, 'Business Studies', '2025-02-14 02:41:54');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rank` int(11) DEFAULT NULL,
  `grade` varchar(50) NOT NULL,
  `term` varchar(50) NOT NULL,
  `unique_id` varchar(255) NOT NULL,
  `examination_number` varchar(255) DEFAULT NULL,
  `student_id` int(11) UNSIGNED DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `rank`, `grade`, `term`, `unique_id`, `examination_number`, `student_id`, `gender`, `status`) VALUES
(21, 'ceaser kalikunde', '', '', 'student', '2025-02-14 16:03:37', 1, 'Form One', 'Term One', '', 'bict0722', NULL, NULL, 'active'),
(22, 'regina nyamablo', '', '', 'student', '2025-02-14 17:50:48', 2, '1', '1', '', NULL, NULL, NULL, 'active'),
(23, 'tate', '', '', 'student', '2025-02-14 17:56:26', 3, '2', '2', '', 'bict0922', NULL, NULL, 'active'),
(24, 'System Administrator', 'admin@school.com', '$2y$10$IGlJsXRDCksSZOK2fW8LHuo7nNaqhqwLEz9oPtB/4ALBrIntAWCLq', 'admin', '2025-10-04 02:34:25', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(25, 'John Teacher', 'teacher1@school.com', '$2y$10$jbnVU33GsORyujiCL8QpzeWiUw9Rl0fSb5Rk1yWMHngBzUjYXNZ1O', 'teacher', '2025-10-04 02:34:25', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(26, 'Jane Smith', 'teacher2@school.com', '$2y$10$jbnVU33GsORyujiCL8QpzeWiUw9Rl0fSb5Rk1yWMHngBzUjYXNZ1O', 'teacher', '2025-10-04 02:34:25', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(27, 'Dr. Williams', 'teacher3@school.com', '$2y$10$jbnVU33GsORyujiCL8QpzeWiUw9Rl0fSb5Rk1yWMHngBzUjYXNZ1O', 'teacher', '2025-10-04 02:34:25', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(28, 'System Administrator', 'admin@school.com', '$2y$10$03LFcLqaVL87exAObri3He08Ydhfq2Sh8E1cGInFq9iXrFxy2SQCi', 'admin', '2025-10-04 02:34:31', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(29, 'John Teacher', 'teacher1@school.com', '$2y$10$cUYBWS7VNmAwSIpgnem2TO99QfRjBOAoUT7xg9HdLrZUaRm5KeHPa', 'teacher', '2025-10-04 02:34:32', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(30, 'Jane Smith', 'teacher2@school.com', '$2y$10$cUYBWS7VNmAwSIpgnem2TO99QfRjBOAoUT7xg9HdLrZUaRm5KeHPa', 'teacher', '2025-10-04 02:34:32', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(31, 'Dr. Williams', 'teacher3@school.com', '$2y$10$cUYBWS7VNmAwSIpgnem2TO99QfRjBOAoUT7xg9HdLrZUaRm5KeHPa', 'teacher', '2025-10-04 02:34:32', NULL, '', '', '', NULL, NULL, NULL, 'active'),
(32, 'chrispin mark', '', '$2y$10$OszpkvkNvrQy0sm2OiSvbuY3uyMAoNRFvc89nqWlONuRqpo4/jsFO', 'student', '2025-10-04 02:40:59', NULL, '1', '1', '', 'b1', NULL, 'Male', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `fk_student_id` (`student_id`);

--
-- Indexes for table `rankings`
--
ALTER TABLE `rankings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `examination_number` (`examination_number`);

--
-- Indexes for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_examination_number` (`examination_number`),
  ADD KEY `idx_grade_term` (`grade`,`term`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `rankings`
--
ALTER TABLE `rankings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_cards`
--
ALTER TABLE `report_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_classes`
--
ALTER TABLE `student_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `fk_student_id` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rankings`
--
ALTER TABLE `rankings`
  ADD CONSTRAINT `rankings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD CONSTRAINT `report_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD CONSTRAINT `student_classes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD CONSTRAINT `student_grades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
