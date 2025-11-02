<?php
/**
 * AJAX endpoint to get campuses by institution ID
 */

require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['institution_id']) || empty($_GET['institution_id'])) {
    echo json_encode([]);
    exit;
}

$institution_id = (int)$_GET['institution_id'];

$database = new Database();
$db = $database->getConnection();

if ($db) {
    try {
        $query = "SELECT id, name, location 
                  FROM campuses 
                  WHERE institution_id = :institution_id 
                  ORDER BY name ASC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':institution_id', $institution_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($campuses);
        
    } catch (PDOException $e) {
        error_log("Get campuses by institution error: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
