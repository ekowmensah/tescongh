-- Create custom_lists table for SMS custom recipient lists
CREATE TABLE IF NOT EXISTS `custom_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `custom_lists_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create custom_list_members table for many-to-many relationship
CREATE TABLE IF NOT EXISTS `custom_list_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `list_member` (`list_id`,`member_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `custom_list_members_ibfk_list` FOREIGN KEY (`list_id`) REFERENCES `custom_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `custom_list_members_ibfk_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
