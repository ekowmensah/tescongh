-- Verify campus and institution relationships
SELECT 
    c.id,
    c.name as campus_name,
    c.institution_id,
    i.name as institution_name,
    c.location,
    r.name as region_name,
    co.name as constituency_name
FROM campuses c
LEFT JOIN institutions i ON c.institution_id = i.id
LEFT JOIN regions r ON c.region_id = r.id
LEFT JOIN constituencies co ON c.constituency_id = co.id
ORDER BY c.id;

-- Check for any NULL institution names (broken foreign keys)
SELECT c.* 
FROM campuses c
LEFT JOIN institutions i ON c.institution_id = i.id
WHERE i.id IS NULL;

-- List all institutions
SELECT id, name FROM institutions ORDER BY id;

-- Expected mappings based on your data:
-- Campus ID 1 (South Gate) -> Institution ID 3
-- Campus ID 2 (City Campus) -> Institution ID 1
-- Campus ID 3 (Kumasi Campus) -> Institution ID 2
-- Campus ID 4 (Cape Coast Campus) -> Institution ID 3
-- Campus ID 5 (North Campus) -> Institution ID 4
