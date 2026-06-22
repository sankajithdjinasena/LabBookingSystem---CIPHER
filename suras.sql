-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 22, 2026 at 05:59 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `suras`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `resource_id` int UNSIGNED NOT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `urgency` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `team_size` int UNSIGNED NOT NULL DEFAULT '1',
  `priority_score` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','waitlist','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_bookings_user` (`user_id`),
  KEY `idx_bookings_resource_time` (`resource_id`,`start_time`,`end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `resource_id`, `purpose`, `start_time`, `end_time`, `urgency`, `team_size`, `priority_score`, `status`, `created_at`) VALUES
(1, 4, 4, 'Meet', '2026-06-21 18:00:00', '2026-06-21 19:00:00', 5, 1, 7.30, 'approved', '2026-06-21 11:50:48'),
(11, 1, 9, 'Internal work', '2026-06-30 10:00:00', '2026-06-30 12:00:00', 4, 1, 5.50, 'approved', '2026-06-21 13:55:33'),
(12, 4, 4, 'Scenario 1 study session', '2026-06-25 10:00:00', '2026-06-25 12:00:00', 3, 2, 4.20, 'approved', '2026-06-21 13:58:49'),
(13, 5, 4, 'Scenario 2 low priority', '2026-06-25 14:00:00', '2026-06-25 16:00:00', 1, 1, 3.10, 'rejected', '2026-06-21 13:58:49'),
(14, 3, 4, 'Scenario 2 high priority', '2026-06-25 14:00:00', '2026-06-25 16:00:00', 5, 20, 9.00, 'approved', '2026-06-21 13:58:49'),
(15, 4, 4, 'Scenario 3 low priority request', '2026-06-25 14:30:00', '2026-06-25 15:30:00', 1, 1, 1.90, 'rejected', '2026-06-21 13:58:49'),
(26, 6, 9, 'Exam', '2026-06-24 10:00:00', '2026-06-24 12:00:00', 4, 1, 5.10, 'cancelled', '2026-06-22 09:56:37'),
(27, 6, 4, 'AI Conversational Booking', '2026-12-07 00:00:00', '2026-12-07 02:00:00', 5, 1, 5.50, 'approved', '2026-06-22 10:11:19'),
(34, 4, 2, 'meeting', '2026-12-12 10:00:00', '2026-12-12 12:00:00', 5, 11, 7.00, 'rejected', '2026-06-22 10:34:59'),
(35, 6, 3, 'Meeting', '2026-12-12 16:00:00', '2026-12-12 18:00:00', 5, 18, 7.80, 'approved', '2026-06-22 10:37:11'),
(36, 4, 2, 'meeting', '2026-12-12 10:00:00', '2026-12-12 12:00:00', 5, 11, 7.00, 'approved', '2026-06-22 10:40:50'),
(37, 6, 8, 'Christmas', '2026-12-28 00:00:00', '2026-12-28 08:00:00', 5, 1, 5.10, 'approved', '2026-06-22 10:53:28'),
(38, 6, 8, 'nb (Admin Override)', '2027-10-26 10:00:00', '2027-10-27 10:00:00', 3, 1, 2.70, 'approved', '2026-06-22 12:34:22'),
(41, 6, 6, 'Movie (Admin Override)', '2026-08-20 10:00:00', '2026-08-21 12:00:00', 3, 1, 2.70, 'approved', '2026-06-22 13:38:07'),
(42, 6, 4, 'Work', '2026-06-25 02:00:00', '2026-06-25 04:00:00', 5, 12, 7.00, 'approved', '2026-06-22 13:41:20'),
(43, 6, 4, 'Internal', '2026-06-25 14:00:00', '2026-06-25 16:00:00', 5, 12, 7.00, 'rejected', '2026-06-22 13:42:54');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(3, 'Business School'),
(1, 'Computer Science'),
(2, 'Engineering'),
(4, 'Resource Office');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `booking_id` int UNSIGNED DEFAULT NULL,
  `type` enum('approval','rejection','cancellation','reminder','waitlist','alternative') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_user` (`user_id`),
  KEY `fk_notifications_booking` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `booking_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(22, 4, 12, 'approval', 'Your booking request has been approved automatically.', 0, '2026-06-21 13:58:49'),
(23, 5, 13, 'approval', 'Your booking request has been approved automatically.', 0, '2026-06-21 13:58:49'),
(24, 5, 13, 'alternative', 'Your booking for Conference Room A on Jun 25 was demoted to the waitlist because a higher priority request was approved. Alternative slot available: 8:00 AM–10:00 AM.', 0, '2026-06-21 13:58:49'),
(25, 3, 14, 'approval', 'Your booking request overrode an existing lower-priority booking.', 1, '2026-06-21 13:58:49'),
(26, 4, 15, 'alternative', 'Your booking request conflicted with a higher-priority request and has been waitlisted. Alternative slot: Jun 25, 8:00 AM–9:00 AM.', 0, '2026-06-21 13:58:49'),
(27, 4, NULL, 'approval', 'Your booking request has been approved automatically.', 0, '2026-06-21 13:58:49'),
(28, 4, NULL, 'alternative', 'Your booking for Computer Lab 204 conflicted with another request. It was split fairly using Round Robin. Approved slots: 12:00 PM–2:00 PM.', 0, '2026-06-21 13:58:49'),
(29, 5, NULL, 'alternative', 'Your booking for Computer Lab 204 was split fairly using Round Robin. Approved slots: 10:00 AM–12:00 PM.', 0, '2026-06-21 13:58:49'),
(30, 5, 13, 'rejection', 'Your booking request was rejected.', 0, '2026-06-21 14:02:37'),
(31, 4, 15, 'rejection', 'Your booking request was rejected.', 0, '2026-06-21 14:02:46'),
(32, 4, NULL, 'alternative', 'Your booking for Computer Lab 204 conflicted with another request. It was split fairly using Round Robin. Approved slots: 9:00 AM–11:00 AM.', 0, '2026-06-21 17:37:11'),
(33, 3, NULL, 'alternative', 'Your booking for Computer Lab 204 was split fairly using Round Robin. Approved slots: 7:00 AM–9:00 AM, 11:00 AM–12:00 PM, 1:04 AM–7:00 AM, 12:00 PM–4:03 PM.', 1, '2026-06-21 17:37:11'),
(34, 6, NULL, 'approval', 'Your booking request has been approved automatically.', 1, '2026-06-22 09:33:17'),
(35, 6, 26, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 1, '2026-06-22 09:56:37'),
(36, 6, 26, 'approval', 'Your booking request has been approved.', 1, '2026-06-22 09:56:57'),
(37, 6, 26, 'reminder', 'Reminder: Your booking starts in 1 hour.', 1, '2026-06-24 03:30:00'),
(38, 6, 26, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 1, '2026-06-24 06:30:00'),
(39, 6, 27, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 1, '2026-06-22 10:11:19'),
(40, 6, 27, 'approval', 'Your booking request has been approved.', 1, '2026-06-22 10:11:38'),
(41, 6, 27, 'reminder', 'Reminder: Your booking starts in 1 hour.', 1, '2026-12-06 17:30:00'),
(42, 6, 27, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 1, '2026-12-06 20:30:00'),
(49, 4, 34, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 0, '2026-06-22 10:34:59'),
(50, 6, 35, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 1, '2026-06-22 10:37:11'),
(51, 4, 36, 'alternative', 'Your booking request conflicted with a higher-priority request and has been waitlisted. Alternative slot: Dec 12, 8:00 AM–10:00 AM.', 0, '2026-06-22 10:40:50'),
(52, 6, 37, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 1, '2026-06-22 10:53:28'),
(53, 6, 37, 'approval', 'Your booking request has been approved.', 1, '2026-06-22 11:00:53'),
(54, 6, 37, 'reminder', 'Reminder: Your booking starts in 1 hour.', 1, '2026-12-27 17:30:00'),
(55, 6, 37, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 1, '2026-12-28 02:30:00'),
(56, 6, 35, 'approval', 'Your booking request has been approved.', 1, '2026-06-22 11:58:15'),
(57, 6, 35, 'reminder', 'Reminder: Your booking starts in 1 hour.', 1, '2026-12-12 09:30:00'),
(58, 6, 35, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 1, '2026-12-12 12:30:00'),
(59, 4, 34, 'rejection', 'Your booking request was rejected.', 0, '2026-06-22 12:07:42'),
(60, 6, 38, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 1, '2026-06-22 12:34:22'),
(61, 6, 38, 'approval', 'Your booking request has been approved.', 1, '2026-06-22 12:34:38'),
(62, 6, 38, 'reminder', 'Reminder: Your booking starts in 1 hour.', 1, '2027-10-26 03:30:00'),
(63, 6, 38, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 1, '2027-10-27 04:30:00'),
(64, 4, NULL, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 0, '2026-06-22 13:10:55'),
(65, 4, NULL, 'approval', 'Your resource booking request has been approved by administrator override.', 0, '2026-06-22 13:12:06'),
(66, 4, 36, 'approval', 'Your booking request has been approved.', 0, '2026-06-22 13:36:51'),
(67, 4, 36, 'reminder', 'Reminder: Your booking starts in 1 hour.', 0, '2026-12-12 03:30:00'),
(68, 4, 36, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 0, '2026-12-12 06:30:00'),
(69, 6, 41, 'approval', 'Your resource booking request has been approved by administrator override.', 1, '2026-06-22 13:38:07'),
(70, 6, 42, '', 'Your booking request has been submitted and is pending faculty/admin approval.', 1, '2026-06-22 13:41:20'),
(71, 6, 43, 'alternative', 'Your booking request conflicted with a higher-priority request and has been waitlisted. Alternative slot: Jun 25, 8:00 AM–10:00 AM.', 1, '2026-06-22 13:42:54'),
(72, 6, 42, 'approval', 'Your booking request has been approved.', 1, '2026-06-22 13:43:34'),
(73, 6, 42, 'reminder', 'Reminder: Your booking starts in 1 hour.', 1, '2026-06-24 19:30:00'),
(74, 6, 42, 'reminder', 'Your booking has ended. Thank you for using SURAS!', 1, '2026-06-24 22:30:00'),
(75, 6, 43, 'rejection', 'Your booking request was rejected.', 1, '2026-06-22 13:44:31'),
(76, 6, 26, 'cancellation', 'Your booking has been cancelled.', 1, '2026-06-22 13:45:08');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
CREATE TABLE IF NOT EXISTS `resources` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('lab','room','multimedia','device') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int UNSIGNED DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','maintenance','retired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `name`, `category`, `location`, `capacity`, `description`, `status`, `created_at`) VALUES
(1, 'Computer Lab 204', 'lab', 'Tech Building, Floor 2', 40, 'Windows lab with 40 workstations and dual monitors.', 'available', '2026-06-20 04:51:18'),
(2, 'Computer Lab 118', 'lab', 'Tech Building, Floor 1', 30, 'Linux lab used mainly for systems and networking courses.', 'retired', '2026-06-20 04:51:18'),
(3, 'Seminar Room B', 'room', 'Main Hall, Floor 1', 18, 'Round-table seminar room with a whiteboard wall.', 'available', '2026-06-20 04:51:18'),
(4, 'Conference Room A', 'room', 'Admin Block, Floor 3', 12, 'Glass-walled meeting room with video conferencing.', 'available', '2026-06-20 04:51:18'),
(5, 'Lecture Hall LH-3', 'room', 'Academic Block, Ground', 120, 'Tiered lecture hall with PA system.', 'available', '2026-06-20 04:51:18'),
(6, 'Projector Kit 02', 'multimedia', 'AV Store', NULL, 'Portable HD projector with tripod screen.', 'available', '2026-06-20 04:51:18'),
(7, 'Mobile PA System', 'multimedia', 'AV Store', NULL, 'Speaker, mixer and two wireless mics.', 'available', '2026-06-20 04:51:18'),
(8, 'DSLR Camera Kit', 'multimedia', 'AV Store', 1, 'Camera, tripod and lavalier mic for recordings.', 'available', '2026-06-20 04:51:18'),
(9, 'VR Headset Set (x4)', 'device', 'Innovation Lab', 4, 'Standalone VR headsets for prototyping sessions.', 'available', '2026-06-20 04:51:18'),
(10, 'Oscilloscope Bench Kit', 'device', 'Engineering Lab 2', NULL, 'Bench oscilloscope and probes for lab assignments.', 'available', '2026-06-20 04:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `label` varchar(120) NOT NULL,
  `description` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
<<<<<<< HEAD
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `label`, `description`) VALUES
('notify_email_enabled', '1', 'Email notifications', 'Set to 1 to send email notifications via PHPMailer, 0 for in-app only.'),
('notify_from_email', 'noreply@university.edu', 'Notification sender email', 'The From address used for all outgoing SURAS emails.'),
('notify_from_name', 'SURAS Resource System', 'Notification sender name', 'The From name used for all outgoing SURAS emails.'),
('rr_min_duration', '14400', 'Round-robin threshold (seconds)', 'Bookings at or above this duration (in seconds) trigger fair-share splitting. Default 14400 = 4 hours.'),
('rr_slot_duration', '7200', 'Round-robin slot size (seconds)', 'Size of each fair-share slot in seconds. Default 7200 = 2 hours.'),
('weight_fairness', '0.20', 'Fairness weight', 'Proportion driven by how few recent bookings the requester has had.'),
('weight_request_time', '0.10', 'Request time weight', 'Proportion given to how long ago the request was submitted (first-come tiebreaker).'),
('weight_team_size', '0.30', 'Team size weight', 'Proportion driven by the number of people in the booking.'),
('weight_urgency', '0.40', 'Urgency weight', 'Proportion of the priority score driven by how urgent the request is (1-5 scale).');

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_support_sender` (`sender_id`),
  KEY `fk_support_receiver` (`receiver_id`)
<<<<<<< HEAD
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(17, 4, 1, 'Hi Harol, I would like to request approval for my 12-hour booking override.', 0, '2026-06-22 18:00:00'),
(9, 1, 1, 'hi', 0, '2026-06-22 17:30:55'),
(18, 1, 4, 'Hello Mathurya! Sure, I see your request in the system. Could you tell me more about the project?', 0, '2026-06-22 18:02:00'),
(19, 4, 1, 'It is for the CS-401 advanced research paper. We need overnight access to the compute nodes.', 0, '2026-06-22 18:05:00'),
(20, 5, 1, 'Good evening sir, is Lab 202 open tomorrow morning?', 0, '2026-06-22 18:10:00'),
(21, 1, 5, 'Yes Sanodya, it will be open from 8:00 AM onwards.', 0, '2026-06-22 18:12:00'),
(22, 6, 1, 'Hello, I have a query about the projector equipment.', 0, '2026-06-22 18:15:00'),
(23, 1, 6, 'Hi TestUser, please contact the inventory department directly or write to them here.', 0, '2026-06-22 18:16:00'),
(24, 1, 5, 'hi', 0, '2026-06-22 19:03:25'),
(25, 1, 5, 'hi', 0, '2026-06-22 19:05:35'),
(26, 1, 4, 'ok', 0, '2026-06-22 19:05:47'),
(27, 6, 1, 'ok sir', 0, '2026-06-22 19:06:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','faculty','project_lead','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `university_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `university_id` (`university_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `university_id`, `department`, `status`, `created_at`) VALUES
(1, 'Harol Maxilan', 'harol.admin@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'admin', NULL, 'Resource Office', 'active', '2026-06-20 04:51:18'),
(2, 'Dr. A. Perera', 'a.perera@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'project_lead', NULL, 'Computer Science', 'active', '2026-06-20 04:51:18'),
(3, 'Sankajith Jinasena', 'sankajith@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'project_lead', NULL, 'Computer Science', 'active', '2026-06-20 04:51:18'),
(4, 'Mathurya Muralimohan', 'mathurya@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'student', NULL, 'Computer Science', 'active', '2026-06-20 04:51:18'),
(5, 'Sanodya Jinadasa', 'sanodya@university.edu', '$2y$10$s442GQ/YWPiwFfV8vrpQCOJ0.2m9034OiP60OGP5lVQjMJ1MxrYk.', 'student', NULL, 'Data Science', 'active', '2026-06-21 12:16:56'),
(6, 'TestUser01', 'user01@university.edu', '$2y$10$iLPtNHXJTQ0NGHbu2vG4Y.nhO5H5.aQGd.9y1RybAGIbW0h3phx/y', 'student', '22CDS0459', 'Computer Science', 'active', '2026-06-22 09:24:27'),
(7, 'Prof. S. Bandara', 's.bandara@university.edu', '$2y$10$gU7byn8ooJ88tbtMMp7aaO6i31uq5IiEjmqUBaAumB1Dk/mfjREya', 'faculty', NULL, NULL, 'active', '2026-06-22 11:25:28'),
(8, 'Dr. K. Silva', 'k.silva@university.edu', '$2y$10$5RhNHH8N0A/q/QBeaCuIpOYm.sjQQ6Soqznmv6cwxWPWkkd2cuu8q', 'faculty', NULL, NULL, 'active', '2026-06-22 11:25:28'),
(9, 'Mr. R. Fernando (Lead)', 'r.fernando@university.edu', '$2y$10$9ps6PlhoWII81tSZt1TaoeZFhx2A3BC0iAYYDp7Nh/694f3PHxrFO', 'project_lead', NULL, NULL, 'active', '2026-06-22 11:25:28');

-- --------------------------------------------------------

--
-- Table structure for table `waitlist`
--

DROP TABLE IF EXISTS `waitlist`;
CREATE TABLE IF NOT EXISTS `waitlist` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` int UNSIGNED NOT NULL,
  `resource_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_waitlist_booking` (`booking_id`),
  KEY `fk_waitlist_resource` (`resource_id`),
  KEY `fk_waitlist_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD CONSTRAINT `fk_waitlist_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_waitlist_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_waitlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
