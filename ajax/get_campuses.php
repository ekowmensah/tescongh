<?php
require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$institutionName = isset($_GET['institution']) ? $_GET['institution'] : '';

if (empty($institutionName)) {
    echo json_encode([]);
    exit;
}

// Get campuses for the institution by name
$query = "SELECT c.id, c.name, c.location 
          FROM campuses c
          INNER JOIN institutions i ON c.institution_id = i.id
          WHERE i.name = :institution_name
          ORDER BY c.name ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':institution_name', $institutionName);
$stmt->execute();
$campuses = $stmt->fetchAll();

echo json_encode($campuses);
