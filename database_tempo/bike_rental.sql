-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 04:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bike_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE `bikes` (
  `bike_id` int(11) NOT NULL,
  `bike_code` varchar(30) NOT NULL,
  `bike_name` varchar(80) NOT NULL,
  `bike_type` enum('mountain','city','other') NOT NULL DEFAULT 'other',
  `status` enum('available','rented','maintenance') NOT NULL DEFAULT 'available',
  `last_maintained_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location` varchar(100) NOT NULL DEFAULT 'Main Bike Area'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`bike_id`, `bike_code`, `bike_name`, `bike_type`, `status`, `last_maintained_date`, `created_at`, `location`) VALUES
(1, 'MTB-001', 'Mountain Bike #1', 'mountain', 'maintenance', '2025-12-01', '2025-12-15 10:32:07', 'Main Bike Area'),
(2, 'CTY-002', 'City Bike #2', 'city', 'rented', '2025-12-03', '2025-12-15 10:32:07', 'Main Bike Area'),
(3, 'MTB-003', 'Mountain Bike #3', 'mountain', 'available', '2025-11-28', '2025-12-15 10:32:07', 'Main Bike Area'),
(4, 'CTY-004', 'City Bike #4', 'city', 'maintenance', '2025-11-15', '2025-12-15 10:32:07', 'Main Bike Area'),
(5, 'MTB-005', 'Mountain Bike #5', 'mountain', 'rented', '2025-12-05', '2025-12-15 10:32:07', 'Main Bike Area'),
(6, 'CTY-006', 'City Bike #6', 'city', 'available', '2025-11-30', '2025-12-15 10:32:07', 'Main Bike Area'),
(7, 'MTB-007', 'Mountain Bike #7', 'mountain', 'available', '2025-12-06', '2025-12-15 10:32:07', 'Main Bike Area'),
(8, 'CTY-008', 'City Bike #8', 'city', 'maintenance', '2025-12-02', '2025-12-15 10:32:07', 'Main Bike Area');

-- --------------------------------------------------------

--
-- Table structure for table `bike_feedback`
--

CREATE TABLE `bike_feedback` (
  `feedback_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `condition_status` enum('good','minor_issue','needs_repair') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bike_feedback`
--

INSERT INTO `bike_feedback` (`feedback_id`, `rental_id`, `bike_id`, `student_id`, `condition_status`, `note`, `created_at`) VALUES
(1, 5, 1, 1, 'needs_repair', NULL, '2025-12-17 00:24:08'),
(2, 6, 2, 1, 'good', NULL, '2025-12-17 03:11:03');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `complaint_code` varchar(30) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rental_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('open','resolved') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`complaint_id`, `complaint_code`, `student_id`, `rental_id`, `message`, `status`, `created_at`) VALUES
(1, 'COMP214158', 1, 2, 'There is no bell', 'open', '2025-12-17 00:04:45');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `method` enum('cashless','card','ewallet','other') NOT NULL DEFAULT 'cashless',
  `status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'paid',
  `paid_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `rental_id`, `amount`, `method`, `status`, `paid_at`) VALUES
(1, 2, 24.00, 'cashless', 'paid', '2025-12-15 19:47:11'),
(3, 4, 3.00, 'cashless', 'paid', '2025-12-16 23:51:17'),
(4, 5, 3.00, 'cashless', 'paid', '2025-12-17 08:23:59'),
(5, 6, 6.00, 'cashless', 'paid', '2025-12-17 11:10:17'),
(6, 7, 3.00, 'cashless', 'paid', '2025-12-17 11:11:19'),
(7, 8, 24.00, 'cashless', 'paid', '2025-12-17 11:18:11');

-- --------------------------------------------------------

--
-- Table structure for table `penalties`
--

CREATE TABLE `penalties` (
  `penalty_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `minutes_late` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int(11) NOT NULL,
  `rental_code` varchar(30) NOT NULL,
  `student_id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `expected_return_time` datetime NOT NULL,
  `return_time` datetime DEFAULT NULL,
  `status` enum('active','completed','late') NOT NULL DEFAULT 'active',
  `hourly_rate` decimal(6,2) NOT NULL DEFAULT 3.00,
  `planned_hours` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_condition` enum('good','minor_issue','needs_repair') DEFAULT NULL,
  `return_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `rental_code`, `student_id`, `bike_id`, `start_time`, `expected_return_time`, `return_time`, `status`, `hourly_rate`, `planned_hours`, `created_at`, `return_condition`, `return_feedback`) VALUES
(2, 'RENT589993', 1, 5, '2025-12-15 12:47:07', '2025-12-15 20:47:07', '2025-12-15 12:47:22', 'completed', 3.00, 8, '2025-12-15 11:47:07', NULL, NULL),
(4, 'RENT083778', 1, 7, '2025-12-16 16:51:16', '2025-12-16 17:51:16', '2025-12-16 16:51:26', 'completed', 3.00, 1, '2025-12-16 15:51:16', 'good', NULL),
(5, 'RENT371035', 1, 1, '2025-12-17 01:23:58', '2025-12-17 02:23:58', '2025-12-17 01:24:08', 'completed', 3.00, 1, '2025-12-17 00:23:58', NULL, NULL),
(6, 'RENT009937', 1, 2, '2025-12-17 04:10:15', '2025-12-17 06:10:15', '2025-12-17 04:11:03', 'completed', 3.00, 2, '2025-12-17 03:10:15', NULL, NULL),
(7, 'RENT696139', 1, 2, '2025-12-17 04:11:18', '2025-12-17 05:11:18', NULL, 'active', 3.00, 1, '2025-12-17 03:11:18', NULL, NULL),
(8, 'RENT640553', 1, 5, '2025-12-17 04:18:06', '2025-12-17 12:18:06', NULL, 'active', 3.00, 8, '2025-12-17 03:18:06', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `matric_no` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `full_name`, `matric_no`, `created_at`) VALUES
(1, 'Ahmad Firdaus', 'TEMP001', '2025-12-15 10:32:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`bike_id`),
  ADD UNIQUE KEY `bike_code` (`bike_code`);

--
-- Indexes for table `bike_feedback`
--
ALTER TABLE `bike_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `rental_id` (`rental_id`),
  ADD KEY `bike_id` (`bike_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD UNIQUE KEY `complaint_code` (`complaint_code`),
  ADD KEY `fk_complaint_student` (`student_id`),
  ADD KEY `fk_complaint_rental` (`rental_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_rental` (`rental_id`);

--
-- Indexes for table `penalties`
--
ALTER TABLE `penalties`
  ADD PRIMARY KEY (`penalty_id`),
  ADD UNIQUE KEY `rental_id` (`rental_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD UNIQUE KEY `rental_code` (`rental_code`),
  ADD UNIQUE KEY `ux_one_active_rental_per_bike` (`bike_id`,`status`),
  ADD KEY `fk_rental_student` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `bike_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bike_feedback`
--
ALTER TABLE `bike_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `penalties`
--
ALTER TABLE `penalties`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bike_feedback`
--
ALTER TABLE `bike_feedback`
  ADD CONSTRAINT `bike_feedback_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bike_feedback_ibfk_2` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`bike_id`),
  ADD CONSTRAINT `bike_feedback_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaint_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_complaint_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penalties`
--
ALTER TABLE `penalties`
  ADD CONSTRAINT `fk_penalty_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `fk_rental_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`bike_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rental_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
