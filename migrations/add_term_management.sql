-- Add term management to campus_executives
ALTER TABLE `campus_executives`
ADD COLUMN `term_start` DATE DEFAULT NULL AFTER `appointed_at`,
ADD COLUMN `term_end` DATE DEFAULT NULL AFTER `term_start`,
ADD COLUMN `is_current` TINYINT(1) DEFAULT 1 AFTER `term_end`,
ADD INDEX `is_current` (`is_current`);

-- Create executive history table for tracking past positions
CREATE TABLE IF NOT EXISTS `executive_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `term_start` date NOT NULL,
  `term_end` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL COMMENT 'Reason for leaving (Graduated, Resigned, etc.)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campus_id` (`campus_id`),
  KEY `member_id` (`member_id`),
  KEY `position_id` (`position_id`),
  CONSTRAINT `executive_history_ibfk_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `executive_history_ibfk_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `executive_history_ibfk_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update existing records to set is_current = 1
UPDATE `campus_executives` SET `is_current` = 1 WHERE `is_current` IS NULL;
