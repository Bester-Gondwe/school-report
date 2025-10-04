-- Update database structure for role-based system
-- Add admin role to users table and create roles table

-- First, update the users table to include admin role
ALTER TABLE `users` MODIFY `role` ENUM('admin','teacher','student') NOT NULL;

-- Create roles table for better role management
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `permissions` json,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `description`, `permissions`) VALUES
('admin', 'System Administrator', '{"manage_users": true, "manage_teachers": true, "manage_students": true, "view_all_data": true, "assign_roles": true, "system_settings": true}'),
('teacher', 'Teacher', '{"manage_students": true, "input_marks": true, "view_student_data": true, "generate_reports": true}'),
('student', 'Student', '{"view_own_data": true, "view_report_card": true}');

-- Add examination_number column to users table if it doesn't exist
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `examination_number` varchar(50) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `grade` varchar(20) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `term` varchar(20) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `gender` varchar(10) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` varchar(20) DEFAULT 'active';

-- Add indexes for better performance
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_role` (`role`);
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_examination_number` (`examination_number`);
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_grade_term` (`grade`, `term`);

-- Preload admin and teacher accounts
-- Admin account
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
('System Administrator', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW())
ON DUPLICATE KEY UPDATE 
`name` = VALUES(`name`),
`password` = VALUES(`password`),
`role` = VALUES(`role`),
`status` = VALUES(`status`);

-- Teacher accounts
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
('John Teacher', 'teacher1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active', NOW()),
('Jane Smith', 'teacher2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active', NOW()),
('Dr. Williams', 'teacher3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active', NOW())
ON DUPLICATE KEY UPDATE 
`name` = VALUES(`name`),
`password` = VALUES(`password`),
`role` = VALUES(`role`),
`status` = VALUES(`status`);

-- Update existing users to have proper roles if they don't have admin role
UPDATE `users` SET `role` = 'teacher' WHERE `role` = 'teacher' AND `email` LIKE '%@school.com';
UPDATE `users` SET `role` = 'student' WHERE `role` = 'student' AND `examination_number` IS NOT NULL;
