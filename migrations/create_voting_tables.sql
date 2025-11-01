-- Create voting regions table (for electoral/voting purposes)
-- This is separate from geographical regions
CREATE TABLE IF NOT EXISTS `voting_regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create voting constituencies table (for electoral/voting purposes)
-- This is separate from geographical constituencies
CREATE TABLE IF NOT EXISTS `voting_constituencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `voting_region_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `voting_region_id` (`voting_region_id`),
  CONSTRAINT `voting_constituencies_ibfk_region` FOREIGN KEY (`voting_region_id`) REFERENCES `voting_regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Ghana's 16 voting regions
INSERT INTO `voting_regions` (`name`, `code`) VALUES
('Greater Accra', 'GAR'),
('Ashanti', 'ASH'),
('Western', 'WR'),
('Western North', 'WNR'),
('Central', 'CR'),
('Eastern', 'ER'),
('Volta', 'VR'),
('Oti', 'OTI'),
('Bono', 'BR'),
('Bono East', 'BER'),
('Ahafo', 'AHF'),
('Northern', 'NR'),
('Savannah', 'SVR'),
('North East', 'NER'),
('Upper East', 'UER'),
('Upper West', 'UWR');

-- Add new columns to members table for voting information
-- These are separate from campus location (region/constituency)
ALTER TABLE `members` 
ADD COLUMN `voting_region_id` int(11) DEFAULT NULL AFTER `hails_from_region`,
ADD COLUMN `voting_constituency_id` int(11) DEFAULT NULL AFTER `voting_region_id`,
ADD KEY `voting_region_id` (`voting_region_id`),
ADD KEY `voting_constituency_id` (`voting_constituency_id`),
ADD CONSTRAINT `members_ibfk_voting_region` FOREIGN KEY (`voting_region_id`) REFERENCES `voting_regions` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `members_ibfk_voting_constituency` FOREIGN KEY (`voting_constituency_id`) REFERENCES `voting_constituencies` (`id`) ON DELETE SET NULL;

-- Note: You may want to migrate data from old text fields to new foreign keys
-- Example migration (if you have data in 'region' and 'constituency' text fields):
-- UPDATE members m 
-- INNER JOIN voting_regions vr ON m.region = vr.name 
-- SET m.voting_region_id = vr.id 
-- WHERE m.region IS NOT NULL;

-- After migration, you can optionally drop the old text columns:
-- ALTER TABLE members DROP COLUMN region;
-- ALTER TABLE members DROP COLUMN constituency;
