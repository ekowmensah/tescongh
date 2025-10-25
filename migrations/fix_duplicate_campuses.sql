-- Check for duplicate campuses
SELECT name, institution_id, COUNT(*) as count
FROM campuses
GROUP BY name, institution_id
HAVING COUNT(*) > 1;

-- If duplicates exist, this will help identify them
-- To remove duplicates, keep the one with the lowest ID:

-- First, create a temporary table with the IDs to keep
CREATE TEMPORARY TABLE campuses_to_keep AS
SELECT MIN(id) as id
FROM campuses
GROUP BY name, institution_id;

-- Show campuses that will be deleted (for review)
SELECT * FROM campuses
WHERE id NOT IN (SELECT id FROM campuses_to_keep);

-- IMPORTANT: Review the above output before running the delete!
-- Uncomment the line below ONLY after reviewing:
-- DELETE FROM campuses WHERE id NOT IN (SELECT id FROM campuses_to_keep);

-- Drop temporary table
DROP TEMPORARY TABLE IF EXISTS campuses_to_keep;

-- Add unique constraint to prevent future duplicates
-- ALTER TABLE campuses ADD UNIQUE KEY unique_campus (name, institution_id);
