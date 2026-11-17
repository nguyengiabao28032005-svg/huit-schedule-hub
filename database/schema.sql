-- Database Schema for HUIT Schedule Hub
-- Compatible with MySQL 8.x / MariaDB / HeidiSQL

CREATE DATABASE IF NOT EXISTS `huit_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `huit_db`;

-- Drop tables in reverse order of foreign keys
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `branches`;

-- 1. Table: branches (Cơ sở)
CREATE TABLE `branches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `branch_name` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: teachers (Giảng viên)
CREATE TABLE `teachers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `expertise` VARCHAR(255) NOT NULL COMMENT 'Chuyên môn chính',
    `password` VARCHAR(255) DEFAULT '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' COMMENT 'Default: password',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: schedules (Lịch dạy)
CREATE TABLE `schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT NOT NULL,
    `branch_id` INT NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL COMMENT 'Tên học phần',
    `room` VARCHAR(50) NOT NULL COMMENT 'Phòng học',
    `day_of_week` VARCHAR(20) NOT NULL COMMENT 'Thứ 2, Thứ 3, ..., Chủ Nhật',
    `shift_or_periods` VARCHAR(50) NOT NULL COMMENT 'Buổi hoặc Tiết học cụ thể',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_schedule_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_schedule_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_day_shift` (`day_of_week`, `shift_or_periods`),
    INDEX `idx_teacher_schedule` (`teacher_id`, `day_of_week`),
    INDEX `idx_branch_schedule` (`branch_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
