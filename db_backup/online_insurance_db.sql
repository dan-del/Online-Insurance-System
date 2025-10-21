-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2025 at 10:46 AM
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
-- Database: `online_insurance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `policy_type_id` int(11) NOT NULL,
  `desired_duration_months` int(11) NOT NULL,
  `additional_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by_user_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `user_id` (`user_id`),
  KEY `policy_type_id` (`policy_type_id`),
  KEY `status_id` (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `user_id`, `policy_type_id`, `desired_duration_months`, `additional_notes`, `admin_notes`, `status_id`, `application_date`, `processed_by_user_id`, `processed_at`) VALUES
(1, 2, 1, 12, '', NULL, 1, '2025-10-21 08:40:09', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `application_status_lookup`
--

DROP TABLE IF EXISTS `application_status_lookup`;
CREATE TABLE IF NOT EXISTS `application_status_lookup` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_status_lookup`
--

INSERT INTO `application_status_lookup` (`status_id`, `status_name`) VALUES
(2, 'Approved'),
(1, 'Pending'),
(3, 'Rejected'),
(4, 'Under Review');

-- --------------------------------------------------------

--
-- Table structure for table `policies`
--

DROP TABLE IF EXISTS `policies`;
CREATE TABLE IF NOT EXISTS `policies` (
  `policy_id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `policy_type_id` int(11) NOT NULL,
  `policy_number` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `premium_amount` decimal(10,2) NOT NULL,
  `issue_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `policy_status` enum('Active','Expired','Lapsed') DEFAULT 'Active',
  PRIMARY KEY (`policy_id`),
  UNIQUE KEY `policy_number` (`policy_number`),
  UNIQUE KEY `application_id` (`application_id`),
  KEY `user_id` (`user_id`),
  KEY `policy_type_id` (`policy_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `policy_types`
--

DROP TABLE IF EXISTS `policy_types`;
CREATE TABLE IF NOT EXISTS `policy_types` (
  `policy_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_premium` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_duration_months` int(11) NOT NULL DEFAULT 1,
  `max_duration_months` int(11) NOT NULL DEFAULT 12,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`policy_type_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `policy_types`
--

INSERT INTO `policy_types` (`policy_type_id`, `name`, `description`, `base_premium`, `min_duration_months`, `max_duration_months`, `status`) VALUES
(1, 'Auto Insurance', 'Comprehensive cover for vehicles.', 1500.00, 6, 24, 'Active'),
(2, 'Life Insurance', 'Term life insurance policy.', 500.00, 12, 60, 'Active'),
(3, 'Health Insurance', 'Basic individual health coverage.', 1200.00, 1, 12, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','company_official','administrator') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `phone_number`, `address`, `role`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@insurance.com', '$2y$10$808lklLCA6WY5Wp86kvLweJ6p87IK1QCVMr4u9DJ/5RPSBT2D8GPe', NULL, NULL, 'administrator', '2025-10-21 08:34:21'),
(2, 'John', 'Doe', 'johndoe@gmail.com', '$2y$10$EzFoD7G8fqN84WpjwQ/aZ.uuKUlkDN4m.qSlI0LtfHvLFY/fA1.QW', '', '', 'customer', '2025-10-21 08:36:06');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`policy_type_id`) REFERENCES `policy_types` (`policy_type_id`),
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `application_status_lookup` (`status_id`);

--
-- Constraints for table `policies`
--
ALTER TABLE `policies`
  ADD CONSTRAINT `policies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `policies_ibfk_2` FOREIGN KEY (`policy_type_id`) REFERENCES `policy_types` (`policy_type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
