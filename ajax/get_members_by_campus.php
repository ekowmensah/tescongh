<?php
/**
 * AJAX endpoint to get members by campus ID
 * Returns only active members who are not already executives
 */

require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['campus_id']) || empty($_GET['campus_id'])) {
    echo json_encode([]);
    exit;
}

$campus_id = (int)$_GET['campus_id'];

$database = new Database();
$db = $database->getConnection();

if ($db) {
    try {
        $query = "SELECT m.id, m.fullname, m.student_id, m.phone, u.email, 
                         c.name as campus_name, i.name as institution_name
                  FROM members m
                  LEFT JOIN users u ON m.user_id = u.id
                  LEFT JOIN campuses c ON m.campus_id = c.id
                  LEFT JOIN institutions i ON c.institution_id = i.id
                  WHERE m.campus_id = :campus_id 
                  AND m.membership_status = 'Active' 
                  AND m.position = 'Member'
                  ORDER BY m.fullname ASC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':campus_id', $campus_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($members);
        
    } catch (PDOException $e) {
        error_log("Get members by campus error: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
