-- Migration: Allow NULL member_id in payments table
-- Purpose: Preserve payment records even when members are deleted
-- Date: 2025-11-25

-- Step 1: Drop the existing foreign key constraint
ALTER TABLE `payments` 
DROP FOREIGN KEY `payments_ibfk_1`;

-- Step 2: Modify the payments table to allow NULL for member_id
ALTER TABLE `payments` 
MODIFY COLUMN `member_id` int(11) NULL;

-- Step 3: Add the foreign key constraint back with SET NULL on delete
-- This ensures payment records are preserved when members are deleted
ALTER TABLE `payments`
ADD CONSTRAINT `payments_ibfk_1` 
FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Add comment to document the change
ALTER TABLE `payments` 
COMMENT = 'Payment records are preserved even when members are deleted. member_id can be NULL for orphaned payments.';
