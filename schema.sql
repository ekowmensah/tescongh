-- Create database
CREATE DATABASE IF NOT EXISTS `tescon_ghana` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tescon_ghana`;

-- Create users table (for authentication)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Member','Executive','Patron','Admin') NOT NULL DEFAULT 'Member',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create regions table
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `regions_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create constituencies table
CREATE TABLE IF NOT EXISTS `constituencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `region_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `constituencies_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `constituencies_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create institutions table
CREATE TABLE IF NOT EXISTS `institutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('University','Polytechnic','College','Other') NOT NULL DEFAULT 'Other',
  `location` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `region_id` int(11) NOT NULL,
  `constituency_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `region_id` (`region_id`),
  KEY `constituency_id` (`constituency_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `institutions_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `institutions_ibfk_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `institutions_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create campuses table
CREATE TABLE IF NOT EXISTS `campuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `region_id` int(11) NOT NULL,
  `constituency_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`),
  KEY `constituency_id` (`constituency_id`),
  KEY `institution_id` (`institution_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `campuses_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campuses_ibfk_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campuses_ibfk_institution` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campuses_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `institution` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `program` varchar(100) NOT NULL,
  `year` varchar(20) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `campus_id` (`campus_id`),
  KEY `membership_status` (`membership_status`),
  CONSTRAINT `members_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create campus_executives table
CREATE TABLE IF NOT EXISTS `campus_executives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `position` varchar(50) NOT NULL,
  `appointed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `campus_member` (`campus_id`,`member_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `campus_executives_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campus_executives_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sms_templates table
CREATE TABLE IF NOT EXISTS `sms_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `content` varchar(160) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sms_templates_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sms_logs table
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `message` varchar(160) NOT NULL,
  `message_id` varchar(100) DEFAULT NULL,
  `status` enum('sent','delivered','failed') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `cost` decimal(5,2) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_phone` (`recipient_phone`),
  KEY `message_id` (`message_id`),
  KEY `sent_at` (`sent_at`),
  CONSTRAINT `sms_logs_ibfk_user` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create dues table (annual membership dues)
CREATE TABLE IF NOT EXISTS `dues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payments table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `dues_id` (`dues_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `hubtel_reference` (`hubtel_reference`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`dues_id`) REFERENCES `dues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create events table (for future use)
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `events_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create event_attendance table (for future use)
CREATE TABLE IF NOT EXISTS `event_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `attended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_member` (`event_id`,`member_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `event_attendance_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_attendance_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Create admin user
INSERT INTO `users` (`email`, `password`, `role`, `status`, `email_verified`, `phone_verified`) VALUES
('ekowme@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Active', 1, 1);
SET @admin_user_id = LAST_INSERT_ID();

-- Create sample member user
INSERT INTO `users` (`email`, `password`, `role`, `status`, `email_verified`, `phone_verified`) VALUES
('john.doe@ug.edu.gh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Member', 'Active', 1, 1);
SET @sample_user_id = LAST_INSERT_ID();

-- Insert sample institutions
INSERT INTO `institutions` (`name`, `type`, `location`, `region_id`, `constituency_id`, `created_by`) VALUES
('University of Ghana', 'University', 'Legon, Accra', (SELECT id FROM regions WHERE name = 'Greater Accra'), (SELECT id FROM constituencies WHERE name = 'Tema Central'), @admin_user_id),
('Kwame Nkrumah University of Science and Technology', 'University', 'Kumasi', (SELECT id FROM regions WHERE name = 'Ashanti'), (SELECT id FROM constituencies WHERE name = 'Kumasi Central'), @admin_user_id),
('University of Cape Coast', 'University', 'Cape Coast', (SELECT id FROM regions WHERE name = 'Central'), (SELECT id FROM constituencies WHERE name = 'Cape Coast North'), @admin_user_id);

-- Insert Ghana's 16 administrative regions
INSERT INTO `regions` (`name`, `code`, `created_by`) VALUES
('Greater Accra', 'GAR', @admin_user_id),
('Ashanti', 'ASR', @admin_user_id),
('Central', 'CR', @admin_user_id),
('Western', 'WR', @admin_user_id),
('Eastern', 'ER', @admin_user_id),
('Volta', 'VR', @admin_user_id),
('Northern', 'NR', @admin_user_id),
('Upper East', 'UER', @admin_user_id),
('Upper West', 'UWR', @admin_user_id),
('Oti', 'OR', @admin_user_id),
('Bono', 'BR', @admin_user_id),
('Bono East', 'BER', @admin_user_id),
('Ahafo', 'AR', @admin_user_id),
('North East', 'NER', @admin_user_id),
('Savannah', 'SR', @admin_user_id),
('Western North', 'WNR', @admin_user_id);

-- Insert sample constituencies for each region (limited examples)
-- Greater Accra Region
INSERT INTO `constituencies` (`name`, `region_id`, `created_by`) VALUES
('Tema Central', (SELECT id FROM regions WHERE name = 'Greater Accra'), @admin_user_id),
('Accra Central', (SELECT id FROM regions WHERE name = 'Greater Accra'), @admin_user_id),
('Ablekuma North', (SELECT id FROM regions WHERE name = 'Greater Accra'), @admin_user_id);

-- Ashanti Region
INSERT INTO `constituencies` (`name`, `region_id`, `created_by`) VALUES
('Kumasi Central', (SELECT id FROM regions WHERE name = 'Ashanti'), @admin_user_id),
('Ejisu', (SELECT id FROM regions WHERE name = 'Ashanti'), @admin_user_id),
('Asokwa', (SELECT id FROM regions WHERE name = 'Ashanti'), @admin_user_id);

-- Central Region
INSERT INTO `constituencies` (`name`, `region_id`, `created_by`) VALUES
('Cape Coast North', (SELECT id FROM regions WHERE name = 'Central'), @admin_user_id),
('Cape Coast South', (SELECT id FROM regions WHERE name = 'Central'), @admin_user_id),
('Agona East', (SELECT id FROM regions WHERE name = 'Central'), @admin_user_id);

-- Western Region
INSERT INTO `constituencies` (`name`, `region_id`, `created_by`) VALUES
('Takoradi', (SELECT id FROM regions WHERE name = 'Western'), @admin_user_id),
('Sekondi', (SELECT id FROM regions WHERE name = 'Western'), @admin_user_id),
('Ahanta West', (SELECT id FROM regions WHERE name = 'Western'), @admin_user_id);



-- Insert sample campuses
INSERT INTO `campuses` (`name`, `institution_id`, `location`, `region_id`, `constituency_id`, `created_by`) VALUES
('Main Campus', (SELECT id FROM institutions WHERE name = 'University of Ghana'), 'Legon', (SELECT id FROM regions WHERE name = 'Greater Accra'), (SELECT id FROM constituencies WHERE name = 'Tema Central'), @admin_user_id),
('City Campus', (SELECT id FROM institutions WHERE name = 'University of Ghana'), 'Accra', (SELECT id FROM regions WHERE name = 'Greater Accra'), (SELECT id FROM constituencies WHERE name = 'Accra Central'), @admin_user_id),
('Kumasi Campus', (SELECT id FROM institutions WHERE name = 'Kwame Nkrumah University of Science and Technology'), 'Kumasi', (SELECT id FROM regions WHERE name = 'Ashanti'), (SELECT id FROM constituencies WHERE name = 'Kumasi Central'), @admin_user_id),
('Cape Coast Campus', (SELECT id FROM institutions WHERE name = 'University of Cape Coast'), 'Cape Coast', (SELECT id FROM regions WHERE name = 'Central'), (SELECT id FROM constituencies WHERE name = 'Cape Coast North'), @admin_user_id);

-- Insert sample member
INSERT INTO `members` (`user_id`, `fullname`, `phone`, `date_of_birth`, `institution`, `department`, `program`, `year`, `student_id`, `position`, `region`, `constituency`, `hails_from_region`, `hails_from_constituency`, `campus_id`, `membership_status`) VALUES
(@sample_user_id, 'John Doe', '+233501234567', '2000-05-15', 'University of Ghana', 'Computer Science', 'Bachelor of Science in Computer Science', '3', 'UGCS12345', 'Member', 'Greater Accra', 'Tema Central', 'Ashanti', 'Kumasi Central', (SELECT id FROM campuses WHERE name = 'Main Campus'), 'Active');

-- Insert sample dues for current year
INSERT INTO `dues` (`year`, `amount`, `description`, `due_date`) VALUES
(2024, 50.00, 'Annual TESCON Membership Dues 2024', '2024-12-31'),
(2025, 60.00, 'Annual TESCON Membership Dues 2025', '2025-12-31');

-- Insert sample SMS templates
INSERT INTO `sms_templates` (`name`, `content`, `created_by`) VALUES
('Dues Reminder', 'Dear TESCON member, your annual membership dues for {year} (GH₵{amount}) are due. Pay now to avoid penalties. Visit our portal to pay.', @admin_user_id),
('Payment Confirmation', 'Thank you for paying your TESCON membership dues for {year}. Your payment of GH₵{amount} has been received successfully.', @admin_user_id),
('Event Notification', 'TESCON Event: {event_name} on {event_date} at {event_location}. All members are encouraged to attend. For more info, contact your executives.', @admin_user_id),
('Welcome Message', 'Welcome to TESCON Ghana! Complete your registration and pay your dues to access all member benefits. Visit our portal for more information.', @admin_user_id),
('Executive Appointment', 'Congratulations! You have been appointed as {position} for {campus_name}. Contact the national secretariat for your responsibilities.', @admin_user_id);
