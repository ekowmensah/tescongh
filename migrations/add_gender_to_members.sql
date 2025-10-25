-- Add gender column to members table
ALTER TABLE `members` 
ADD COLUMN `gender` enum('Male','Female') DEFAULT NULL AFTER `date_of_birth`;
