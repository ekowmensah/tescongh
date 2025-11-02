<?php
/**
 * AJAX endpoint to check if student ID already exists
 */

require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo json_encode([
        'exists' => false,
        'message' => 'Student ID is required'
    ]);
    exit;
}

$student_id = sanitize($_GET['student_id']);

// Optional: Check if we're editing a member (exclude current member from check)
$exclude_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;

$database = new Database();
$db = $database->getConnection();

if ($db) {
    try {
        if ($exclude_member_id) {
            // When editing, exclude the current member
            $query = "SELECT id FROM members WHERE student_id = :student_id AND id != :member_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':member_id', $exclude_member_id);
        } else {
            // When adding new member
            $query = "SELECT id FROM members WHERE student_id = :student_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'exists' => true,
                'message' => 'Student ID already exists'
            ]);
        } else {
            echo json_encode([
                'exists' => false,
                'message' => 'Student ID is available'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Student ID check error: " . $e->getMessage());
        echo json_encode([
            'exists' => false,
            'message' => 'Error checking student ID'
        ]);
    }
} else {
    echo json_encode([
        'exists' => false,
        'message' => 'Database connection failed'
    ]);
}
?>
