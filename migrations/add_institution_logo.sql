-- Add logo field to institutions table
ALTER TABLE `institutions` 
ADD COLUMN `logo` VARCHAR(255) DEFAULT NULL AFTER `website`;

-- Add logo field to campuses table (optional, for campus-specific logos)
ALTER TABLE `campuses` 
ADD COLUMN `logo` VARCHAR(255) DEFAULT NULL AFTER `location`;
