-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 25, 2025 at 09:39 AM
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
-- Database: `tescon_ghana`
--

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `region_id` int(11) NOT NULL,
  `constituency_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`id`, `name`, `institution_id`, `location`, `logo`, `region_id`, `constituency_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'South Gate', 3, 'Legon', NULL, 3, 8, 1, '2025-10-23 07:20:06', '2025-10-23 10:29:50'),
(2, 'City Campus', 1, 'Accra', NULL, 1, 2, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(3, 'Kumasi Campus', 2, 'Kumasi', NULL, 2, 4, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(4, 'Cape Coast Campus', 3, 'Cape Coast', NULL, 3, 7, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(5, 'North Campus', 4, 'Behind Estate', NULL, 3, 9, 1, '2025-10-23 08:57:02', '2025-10-23 08:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `campus_executives`
--

CREATE TABLE `campus_executives` (
  `id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `position` varchar(50) NOT NULL,
  `appointed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campus_executives`
--

INSERT INTO `campus_executives` (`id`, `campus_id`, `member_id`, `position_id`, `position`, `appointed_at`, `term_start`, `term_end`, `is_current`) VALUES
(1, 1, 1, 1, '', '2025-10-23 10:18:22', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `constituencies`
--

CREATE TABLE `constituencies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `region_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `constituencies`
--

INSERT INTO `constituencies` (`id`, `name`, `region_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Tema Central', 1, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(2, 'Accra Central', 1, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(3, 'Ablekuma North', 1, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(4, 'Kumasi Central', 2, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(5, 'Ejisu', 2, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(6, 'Asokwa', 2, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(7, 'Cape Coast North', 3, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(8, 'Cape Coast South', 3, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(9, 'Agona East', 3, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(10, 'Takoradi', 4, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(11, 'Sekondi', 4, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(12, 'Ahanta West', 4, 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `custom_lists`
--

CREATE TABLE `custom_lists` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_list_members`
--

CREATE TABLE `custom_list_members` (
  `id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dues`
--

CREATE TABLE `dues` (
  `id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dues`
--

INSERT INTO `dues` (`id`, `year`, `amount`, `description`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 2024, 50.00, 'Annual TESCON Membership Dues 2024', '2024-12-31', '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(2, 2025, 80.00, 'Annual TESCON Membership Dues 2025', '2025-12-31', '2025-10-23 07:20:06', '2025-10-23 09:14:47');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance`
--

CREATE TABLE `event_attendance` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `attended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `executive_history`
--

CREATE TABLE `executive_history` (
  `id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `term_start` date NOT NULL,
  `term_end` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL COMMENT 'Reason for leaving (Graduated, Resigned, etc.)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institutions`
--

CREATE TABLE `institutions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('University','Polytechnic','College','Other') NOT NULL DEFAULT 'Other',
  `location` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `region_id` int(11) NOT NULL,
  `constituency_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutions`
--

INSERT INTO `institutions` (`id`, `name`, `type`, `location`, `website`, `logo`, `region_id`, `constituency_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'University of Ghana', 'University', 'Legon, Accra', NULL, NULL, 1, 2, 1, '2025-10-23 07:20:06', '2025-10-23 08:54:51'),
(2, 'Kwame Nkrumah University of Science and Technology', 'University', 'Kumasi', NULL, NULL, 2, 4, 1, '2025-10-23 07:20:06', '2025-10-23 08:54:30'),
(3, 'University of Cape Coast', 'University', 'Cape Coast', NULL, NULL, 3, 8, 1, '2025-10-23 07:20:06', '2025-10-23 08:55:07'),
(4, 'Career Training Institute', 'Other', 'Breman Asikuma', 'https://career.com', NULL, 3, 9, 1, '2025-10-23 08:56:21', '2025-10-23 08:56:21');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `institution` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `position` enum('Member','Executive','Patron') NOT NULL DEFAULT 'Member',
  `region` varchar(100) DEFAULT NULL,
  `constituency` varchar(100) DEFAULT NULL,
  `hails_from_region` varchar(100) DEFAULT NULL,
  `hails_from_constituency` varchar(100) DEFAULT NULL,
  `npp_position` varchar(255) DEFAULT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `membership_status` enum('Active','Inactive','Suspended','Graduated') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `user_id`, `fullname`, `phone`, `date_of_birth`, `gender`, `photo`, `institution`, `department`, `program`, `year`, `student_id`, `position`, `region`, `constituency`, `hails_from_region`, `hails_from_constituency`, `npp_position`, `campus_id`, `membership_status`, `created_at`, `updated_at`) VALUES
(1, 2, 'John Doe', '+233501234567', '2000-05-15', 'Female', NULL, 'University of Ghana', 'Computer Science', 'BSc Computer Science', '3', 'UGCS12345', 'Executive', 'Greater Accra', 'Tema Central', 'Ashanti', 'Kumasi Central', '', 1, 'Active', '2025-10-23 07:20:06', '2025-10-25 07:04:01'),
(2, 4, 'Ekow Mensah', '0545644749', '2025-10-09', 'Male', '68f9f389a3202_1761211273.jpg', 'Career Training Institute', 'Computer Science', 'BTech Cyber Security', '4', '01222520B', 'Member', 'Central', 'Agona East', 'Central', 'Agona East', 'Constituency Secretary', 5, 'Active', '2025-10-23 09:21:13', '2025-10-25 06:52:33'),
(3, 5, 'Norbert Mensah', '0545644749', NULL, NULL, NULL, 'ATU', NULL, 'BSc Computer Science', '2', '012225201', 'Member', NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-10-25 07:13:39', '2025-10-25 07:13:39');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `dues_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('hubtel_mobile','hubtel_card','bank_transfer','cash') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `hubtel_reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('Executive','Patron','Member') NOT NULL DEFAULT 'Executive',
  `level` int(11) NOT NULL DEFAULT 0 COMMENT 'Hierarchy level (1=highest)',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `name`, `category`, `level`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'President', 'Executive', 1, 'Overall leader of the campus chapter', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(2, 'Vice President', 'Executive', 2, 'Assists the President and acts in their absence', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(3, 'General Secretary', 'Executive', 3, 'Handles correspondence and record keeping', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(4, 'Treasurer', 'Executive', 4, 'Manages finances and financial records', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(5, 'Organizer', 'Executive', 5, 'Coordinates activities and mobilization', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(6, 'Women\'s Organizer', 'Executive', 6, 'Leads women\'s wing activities', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(7, 'Communications Director', 'Executive', 7, 'Manages communications and publicity', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(8, 'Welfare Officer', 'Executive', 8, 'Handles member welfare and support', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(9, 'NASARA Coordinator', 'Executive', 9, 'Coordinates NASARA wing activities', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(10, 'Deputy Organizer', 'Executive', 10, 'Assists the Organizer', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(11, 'Deputy Secretary', 'Executive', 11, 'Assists the General Secretary', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(12, 'Patron', 'Patron', 1, 'Faculty advisor or supporter', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(13, 'Senior Patron', 'Patron', 2, 'Senior faculty advisor', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(14, 'Honorary Patron', 'Patron', 3, 'Distinguished supporter or alumnus', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45'),
(15, 'Member', 'Member', 1, 'Regular member', 1, 1, '2025-10-23 09:33:45', '2025-10-23 09:33:45');

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `name`, `code`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Greater Accra', 'GAR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(2, 'Ashanti', 'ASR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(3, 'Central', 'CR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(4, 'Western', 'WR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(5, 'Eastern', 'ER', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(6, 'Volta', 'VR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(7, 'Northern', 'NR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(8, 'Upper East', 'UER', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(9, 'Upper West', 'UWR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(10, 'Oti', 'OR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(11, 'Bono', 'BR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(12, 'Bono East', 'BER', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(13, 'Ahafo', 'AR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(14, 'North East', 'NER', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(15, 'Savannah', 'SR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(16, 'Western North', 'WNR', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `message` varchar(160) NOT NULL,
  `message_id` varchar(100) DEFAULT NULL,
  `status` enum('sent','delivered','failed') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `cost` decimal(5,2) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `sender_id`, `recipient_phone`, `message`, `message_id`, `status`, `error_message`, `cost`, `sent_at`, `delivered_at`) VALUES
(1, 1, '+233501234567', 'dfkalfkjasd fj; dlsakjf aldskjf asljkfsda', NULL, 'sent', NULL, NULL, '2025-10-23 08:36:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `content` varchar(160) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_templates`
--

INSERT INTO `sms_templates` (`id`, `name`, `content`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Dues Reminder', 'Dear TESCON member, your annual membership dues for {year} (GH₵{amount}) are due. Pay now to avoid penalties. Visit our portal to pay.', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(2, 'Payment Confirmation', 'Thank you for paying your TESCON membership dues for {year}. Your payment of GH₵{amount} has been received successfully.', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(3, 'Event Notification', 'TESCON Event: {event_name} on {event_date} at {event_location}. All members are encouraged to attend. For more info, contact your executives.', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(4, 'Welcome Message', 'Welcome to TESCON Ghana! Complete your registration and pay your dues to access all member benefits. Visit our portal for more information.', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06'),
(5, 'Executive Appointment', 'Congratulations! You have been appointed as {position} for {campus_name}. Contact the national secretariat for your responsibilities.', 1, '2025-10-23 07:20:06', '2025-10-23 07:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Member','Executive','Patron','Admin') NOT NULL DEFAULT 'Member',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `status`, `email_verified`, `phone_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'ekowme@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Active', 1, 1, '2025-10-25 06:22:14', '2025-10-23 07:20:06', '2025-10-25 06:22:14'),
(2, 'john.doe@ug.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Executive', 'Active', 1, 1, '2025-10-23 10:42:24', '2025-10-23 07:20:06', '2025-10-23 10:42:24'),
(3, 'ekowme@gmail.comm', '$2y$10$7QUkr8lqj3ZTIwn6puz.8.5NZjU1mtJ1u71aNCvBJ62v.FjBNLPPC', 'Member', 'Active', 0, 0, NULL, '2025-10-23 09:11:56', '2025-10-23 09:11:56'),
(4, 'ekowme@gmail.commm', '$2y$10$M0.vq/IKYhgnx8U7HuejQegO4FDezbplcV.E0AWhlU6DcNnH.qchW', 'Member', 'Active', 0, 0, '2025-10-23 09:22:52', '2025-10-23 09:21:13', '2025-10-23 09:22:52'),
(5, 'pakowmensah@gmail.com', '$2y$10$HDtFWc6YOgtJfhjWoRgKROTj/fSyT3NMZbCndfMxmxn4te9zjt3kG', 'Member', 'Active', 0, 0, '2025-10-25 07:13:54', '2025-10-25 07:13:39', '2025-10-25 07:13:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campuses`
--
ALTER TABLE `campuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `region_id` (`region_id`),
  ADD KEY `constituency_id` (`constituency_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `campus_executives`
--
ALTER TABLE `campus_executives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campus_member` (`campus_id`,`member_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `is_current` (`is_current`);

--
-- Indexes for table `constituencies`
--
ALTER TABLE `constituencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `region_id` (`region_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `custom_lists`
--
ALTER TABLE `custom_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `custom_list_members`
--
ALTER TABLE `custom_list_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `list_member` (`list_id`,`member_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `dues`
--
ALTER TABLE `dues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_member` (`event_id`,`member_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `executive_history`
--
ALTER TABLE `executive_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campus_id` (`campus_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `region_id` (`region_id`),
  ADD KEY `constituency_id` (`constituency_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `campus_id` (`campus_id`),
  ADD KEY `membership_status` (`membership_status`),
  ADD KEY `idx_campus_status` (`campus_id`,`membership_status`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `dues_id` (`dues_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `hubtel_reference` (`hubtel_reference`),
  ADD KEY `idx_member_status` (`member_id`,`status`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_category` (`name`,`category`),
  ADD KEY `category` (`category`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_phone` (`recipient_phone`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `sent_at` (`sent_at`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `status` (`status`),
  ADD KEY `role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `campus_executives`
--
ALTER TABLE `campus_executives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `constituencies`
--
ALTER TABLE `constituencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `custom_lists`
--
ALTER TABLE `custom_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_list_members`
--
ALTER TABLE `custom_list_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dues`
--
ALTER TABLE `dues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_attendance`
--
ALTER TABLE `event_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `executive_history`
--
ALTER TABLE `executive_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institutions`
--
ALTER TABLE `institutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campuses`
--
ALTER TABLE `campuses`
  ADD CONSTRAINT `campuses_ibfk_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campuses_ibfk_institution` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campuses_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campuses_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campus_executives`
--
ALTER TABLE `campus_executives`
  ADD CONSTRAINT `campus_executives_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campus_executives_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campus_executives_ibfk_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `constituencies`
--
ALTER TABLE `constituencies`
  ADD CONSTRAINT `constituencies_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `constituencies_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_lists`
--
ALTER TABLE `custom_lists`
  ADD CONSTRAINT `custom_lists_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_list_members`
--
ALTER TABLE `custom_list_members`
  ADD CONSTRAINT `custom_list_members_ibfk_list` FOREIGN KEY (`list_id`) REFERENCES `custom_lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `custom_list_members_ibfk_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD CONSTRAINT `event_attendance_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendance_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `executive_history`
--
ALTER TABLE `executive_history`
  ADD CONSTRAINT `executive_history_ibfk_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `executive_history_ibfk_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `executive_history_ibfk_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `institutions`
--
ALTER TABLE `institutions`
  ADD CONSTRAINT `institutions_ibfk_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `institutions_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `institutions_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `members_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`dues_id`) REFERENCES `dues` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `regions`
--
ALTER TABLE `regions`
  ADD CONSTRAINT `regions_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `sms_logs_ibfk_user` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD CONSTRAINT `sms_templates_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
