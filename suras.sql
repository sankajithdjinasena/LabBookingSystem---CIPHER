-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2026 at 02:17 PM
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
-- Database: `suras`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `resource_id` int(10) UNSIGNED NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `urgency` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `team_size` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `priority_score` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','waitlist','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `resource_id`, `purpose`, `start_time`, `end_time`, `urgency`, `team_size`, `priority_score`, `status`, `created_at`) VALUES
(1, 4, 4, 'Meet', '2026-06-21 18:00:00', '2026-06-21 19:00:00', 5, 1, 7.30, 'approved', '2026-06-21 11:50:48'),
(2, 4, 1, 'mm', '2026-06-22 07:00:00', '2026-06-22 12:00:00', 4, 1, 6.10, 'approved', '2026-06-21 12:04:01');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('approval','rejection','cancellation','reminder','waitlist','alternative') NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `booking_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 1, 'approval', 'Your booking has been approved and confirmed.', 1, '2026-06-21 11:50:48'),
(2, 4, 2, 'approval', 'Your booking has been approved and confirmed.', 0, '2026-06-21 12:04:01');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('lab','room','multimedia','device') NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `status` enum('available','maintenance','retired') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `name`, `category`, `location`, `capacity`, `description`, `status`, `created_at`) VALUES
(1, 'Computer Lab 204', 'lab', 'Tech Building, Floor 2', 40, 'Windows lab with 40 workstations and dual monitors.', 'available', '2026-06-20 04:51:18'),
(2, 'Computer Lab 118', 'lab', 'Tech Building, Floor 1', 30, 'Linux lab used mainly for systems and networking courses.', 'maintenance', '2026-06-20 04:51:18'),
(3, 'Seminar Room B', 'room', 'Main Hall, Floor 1', 18, 'Round-table seminar room with a whiteboard wall.', 'available', '2026-06-20 04:51:18'),
(4, 'Conference Room A', 'room', 'Admin Block, Floor 3', 12, 'Glass-walled meeting room with video conferencing.', 'available', '2026-06-20 04:51:18'),
(5, 'Lecture Hall LH-3', 'room', 'Academic Block, Ground', 120, 'Tiered lecture hall with PA system.', 'available', '2026-06-20 04:51:18'),
(6, 'Projector Kit 02', 'multimedia', 'AV Store', NULL, 'Portable HD projector with tripod screen.', 'available', '2026-06-20 04:51:18'),
(7, 'Mobile PA System', 'multimedia', 'AV Store', NULL, 'Speaker, mixer and two wireless mics.', 'available', '2026-06-20 04:51:18'),
(8, 'DSLR Camera Kit', 'multimedia', 'AV Store', NULL, 'Camera, tripod and lavalier mic for recordings.', 'maintenance', '2026-06-20 04:51:18'),
(9, 'VR Headset Set (x4)', 'device', 'Innovation Lab', 4, 'Standalone VR headsets for prototyping sessions.', 'available', '2026-06-20 04:51:18'),
(10, 'Oscilloscope Bench Kit', 'device', 'Engineering Lab 2', NULL, 'Bench oscilloscope and probes for lab assignments.', 'available', '2026-06-20 04:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','faculty','project_lead','admin') NOT NULL DEFAULT 'student',
  `department` varchar(120) DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `department`, `status`, `created_at`) VALUES
(1, 'Harol Maxilan', 'harol.admin@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'admin', 'Resource Office', 'active', '2026-06-20 04:51:18'),
(2, 'Dr. A. Perera', 'a.perera@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'faculty', 'Computer Science', 'active', '2026-06-20 04:51:18'),
(3, 'Sankajith Jinasena', 'sankajith@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'project_lead', 'Computer Science', 'active', '2026-06-20 04:51:18'),
(4, 'Mathurya Muralimohan', 'mathurya@university.edu', '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'student', 'Computer Science', 'active', '2026-06-20 04:51:18'),
(5, 'Sanodya Jinadasa', 'sanodya@university.edu', '$2y$10$s442GQ/YWPiwFfV8vrpQCOJ0.2m9034OiP60OGP5lVQjMJ1MxrYk.', 'student', 'Data Science', 'active', '2026-06-21 12:16:56');

-- --------------------------------------------------------

--
-- Table structure for table `waitlist`
--

CREATE TABLE `waitlist` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `resource_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bookings_user` (`user_id`),
  ADD KEY `idx_bookings_resource_time` (`resource_id`,`start_time`,`end_time`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`),
  ADD KEY `fk_notifications_booking` (`booking_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_waitlist_booking` (`booking_id`),
  ADD KEY `fk_waitlist_resource` (`resource_id`),
  ADD KEY `fk_waitlist_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `waitlist`
--
ALTER TABLE `waitlist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
