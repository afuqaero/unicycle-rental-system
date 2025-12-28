-- ============================================
-- UniCycle Database for InfinityFree Hosting
-- Import this into your InfinityFree database
-- NOTE: Database is already created by InfinityFree
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================
-- Table: admin
-- Admin users for admin panel
-- ============================================
CREATE TABLE IF NOT EXISTS `admin` (
    `admin_id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`admin_id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample admin data (password: admin123)
INSERT INTO `admin` (`admin_id`, `username`, `email`, `password`) VALUES
(1, 'admin', 'admin@unicycle.uthm.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================
-- Table: students
-- All users (students, staff, admins)
-- ============================================
CREATE TABLE IF NOT EXISTS `students` (
    `student_id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `google_id` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user',
    `phone` VARCHAR(20) DEFAULT NULL,
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample user data (password: password123)
INSERT INTO `students` (`student_id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Ahmad Firdaus', 'ahmad@student.uthm.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(2, 'Sarah Lim', 'sarah@staff.uthm.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================
-- Table: password_resets
-- Password reset tokens
-- ============================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `fk_reset_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: bikes
-- Bicycle inventory
-- ============================================
CREATE TABLE IF NOT EXISTS `bikes` (
    `bike_id` INT(11) NOT NULL AUTO_INCREMENT,
    `bike_code` VARCHAR(30) NOT NULL,
    `bike_name` VARCHAR(80) NOT NULL,
    `bike_type` ENUM('mountain', 'city', 'other') NOT NULL DEFAULT 'other',
    `status` ENUM('available', 'rented', 'maintenance', 'pending') NOT NULL DEFAULT 'available',
    `location` VARCHAR(100) NOT NULL DEFAULT 'Main Bike Area',
    `last_maintained_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`bike_id`),
    UNIQUE KEY `bike_code` (`bike_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample bikes data (15 bikes total: 8 mountain, 7 city)
INSERT INTO `bikes` (`bike_id`, `bike_code`, `bike_name`, `bike_type`, `status`, `location`, `last_maintained_date`) VALUES
(1, 'MTB-001', 'Mountain Bike #1', 'mountain', 'available', 'Main Bike Area', '2025-12-01'),
(2, 'CTY-002', 'City Bike #2', 'city', 'available', 'Main Bike Area', '2025-12-03'),
(3, 'MTB-003', 'Mountain Bike #3', 'mountain', 'available', 'Main Bike Area', '2025-11-28'),
(4, 'CTY-004', 'City Bike #4', 'city', 'maintenance', 'Main Bike Area', '2025-11-15'),
(5, 'MTB-005', 'Mountain Bike #5', 'mountain', 'available', 'Main Bike Area', '2025-12-05'),
(6, 'CTY-006', 'City Bike #6', 'city', 'available', 'Main Bike Area', '2025-11-30'),
(7, 'MTB-007', 'Mountain Bike #7', 'mountain', 'available', 'Main Bike Area', '2025-12-06'),
(8, 'CTY-008', 'City Bike #8', 'city', 'available', 'Main Bike Area', '2025-12-02'),
(9, 'MTB-009', 'Mountain Bike #9', 'mountain', 'available', 'Main Bike Area', '2025-12-10'),
(10, 'CTY-010', 'City Bike #10', 'city', 'available', 'Main Bike Area', '2025-12-08'),
(11, 'MTB-011', 'Mountain Bike #11', 'mountain', 'maintenance', 'Main Bike Area', '2025-12-12'),
(12, 'CTY-012', 'City Bike #12', 'city', 'available', 'Main Bike Area', '2025-12-15'),
(13, 'MTB-013', 'Mountain Bike #13', 'mountain', 'available', 'Main Bike Area', '2025-12-18'),
(14, 'CTY-014', 'City Bike #14', 'city', 'available', 'Main Bike Area', '2025-12-20'),
(15, 'MTB-015', 'Mountain Bike #15', 'mountain', 'available', 'Main Bike Area', '2025-12-22');

-- ============================================
-- Table: rentals
-- Rental records
-- ============================================
CREATE TABLE IF NOT EXISTS `rentals` (
    `rental_id` INT(11) NOT NULL AUTO_INCREMENT,
    `rental_code` VARCHAR(30) NOT NULL,
    `student_id` INT(11) NOT NULL,
    `bike_id` INT(11) NOT NULL,
    `start_time` DATETIME NOT NULL,
    `expected_return_time` DATETIME NOT NULL,
    `return_time` DATETIME DEFAULT NULL,
    `status` ENUM('active', 'completed', 'late') NOT NULL DEFAULT 'active',
    `hourly_rate` DECIMAL(6,2) NOT NULL DEFAULT 3.00,
    `planned_hours` INT(11) NOT NULL DEFAULT 1,
    `return_condition` ENUM('good', 'minor_issue', 'needs_repair') DEFAULT NULL,
    `return_feedback` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`rental_id`),
    UNIQUE KEY `rental_code` (`rental_code`),
    KEY `fk_rental_student` (`student_id`),
    KEY `fk_rental_bike` (`bike_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: payments
-- Payment records
-- ============================================
CREATE TABLE IF NOT EXISTS `payments` (
    `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
    `rental_id` INT(11) NOT NULL,
    `amount` DECIMAL(8,2) NOT NULL,
    `method` ENUM('cashless', 'card', 'ewallet', 'other') NOT NULL DEFAULT 'cashless',
    `status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'paid',
    `paid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`payment_id`),
    KEY `fk_payment_rental` (`rental_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: penalties
-- Late return penalties
-- ============================================
CREATE TABLE IF NOT EXISTS `penalties` (
    `penalty_id` INT(11) NOT NULL AUTO_INCREMENT,
    `rental_id` INT(11) NOT NULL,
    `minutes_late` INT(11) NOT NULL DEFAULT 0,
    `amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`penalty_id`),
    UNIQUE KEY `rental_id` (`rental_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: complaints
-- User complaints
-- ============================================
CREATE TABLE IF NOT EXISTS `complaints` (
    `complaint_id` INT(11) NOT NULL AUTO_INCREMENT,
    `complaint_code` VARCHAR(30) NOT NULL,
    `student_id` INT(11) NOT NULL,
    `rental_id` INT(11) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`complaint_id`),
    UNIQUE KEY `complaint_code` (`complaint_code`),
    KEY `fk_complaint_student` (`student_id`),
    KEY `fk_complaint_rental` (`rental_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: bike_feedback
-- Post-return bike condition feedback
-- ============================================
CREATE TABLE IF NOT EXISTS `bike_feedback` (
    `feedback_id` INT(11) NOT NULL AUTO_INCREMENT,
    `rental_id` INT(11) NOT NULL,
    `bike_id` INT(11) NOT NULL,
    `student_id` INT(11) NOT NULL,
    `condition_status` ENUM('good', 'minor_issue', 'needs_repair') DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`feedback_id`),
    KEY `fk_feedback_rental` (`rental_id`),
    KEY `fk_feedback_bike` (`bike_id`),
    KEY `fk_feedback_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Foreign Key Constraints
-- ============================================

-- Rentals constraints
ALTER TABLE `rentals`
    ADD CONSTRAINT `fk_rental_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_rental_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`bike_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Payments constraints
ALTER TABLE `payments`
    ADD CONSTRAINT `fk_payment_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Penalties constraints
ALTER TABLE `penalties`
    ADD CONSTRAINT `fk_penalty_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Complaints constraints
ALTER TABLE `complaints`
    ADD CONSTRAINT `fk_complaint_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_complaint_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Bike feedback constraints
ALTER TABLE `bike_feedback`
    ADD CONSTRAINT `fk_feedback_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_feedback_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`bike_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_feedback_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
