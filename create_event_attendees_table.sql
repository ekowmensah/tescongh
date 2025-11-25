-- Create event_attendees table for event registration
-- Run this SQL in your database if the table doesn't exist

CREATE TABLE IF NOT EXISTS `event_attendees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `registered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_member` (`event_id`, `member_id`),
  KEY `event_id` (`event_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `event_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_attendees_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- This table stores event registrations
-- Features:
-- - Unique constraint prevents duplicate registrations
-- - Foreign keys ensure data integrity
-- - CASCADE delete removes registrations when event or member is deleted
-- - registered_at tracks when member registered
