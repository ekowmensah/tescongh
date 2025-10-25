-- Create positions table
CREATE TABLE IF NOT EXISTS `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` enum('Executive','Patron','Member') NOT NULL DEFAULT 'Executive',
  `level` int(11) NOT NULL DEFAULT 0 COMMENT 'Hierarchy level (1=highest)',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_category` (`name`, `category`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `positions_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modify campus_executives table to use position_id
ALTER TABLE `campus_executives` 
ADD COLUMN `position_id` int(11) DEFAULT NULL AFTER `member_id`,
ADD KEY `position_id` (`position_id`),
ADD CONSTRAINT `campus_executives_ibfk_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL;

-- Keep the old position column for backward compatibility (can be removed later)
-- ALTER TABLE `campus_executives` MODIFY COLUMN `position` varchar(50) NULL;

-- Insert default executive positions
INSERT INTO `positions` (`name`, `category`, `level`, `description`, `created_by`) VALUES
('President', 'Executive', 1, 'Overall leader of the campus chapter', 1),
('Vice President', 'Executive', 2, 'Assists the President and acts in their absence', 1),
('General Secretary', 'Executive', 3, 'Handles correspondence and record keeping', 1),
('Treasurer', 'Executive', 4, 'Manages finances and financial records', 1),
('Organizer', 'Executive', 5, 'Coordinates activities and mobilization', 1),
('Women\'s Organizer', 'Executive', 6, 'Leads women\'s wing activities', 1),
('Communications Director', 'Executive', 7, 'Manages communications and publicity', 1),
('Welfare Officer', 'Executive', 8, 'Handles member welfare and support', 1),
('NASARA Coordinator', 'Executive', 9, 'Coordinates NASARA wing activities', 1),
('Deputy Organizer', 'Executive', 10, 'Assists the Organizer', 1),
('Deputy Secretary', 'Executive', 11, 'Assists the General Secretary', 1);

-- Insert default patron positions
INSERT INTO `positions` (`name`, `category`, `level`, `description`, `created_by`) VALUES
('Patron', 'Patron', 1, 'Faculty advisor or supporter', 1),
('Senior Patron', 'Patron', 2, 'Senior faculty advisor', 1),
('Honorary Patron', 'Patron', 3, 'Distinguished supporter or alumnus', 1);

-- Insert default member position
INSERT INTO `positions` (`name`, `category`, `level`, `description`, `created_by`) VALUES
('Member', 'Member', 1, 'Regular member', 1);
