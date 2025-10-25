<?php
require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$regionId = isset($_GET['region_id']) ? (int)$_GET['region_id'] : 0;
$constituencyId = isset($_GET['constituency_id']) ? (int)$_GET['constituency_id'] : 0;

if (!$regionId) {
    echo json_encode([]);
    exit;
}

// Build query based on filters
if ($constituencyId) {
    $query = "SELECT id, name, type, location FROM institutions 
              WHERE region_id = :region_id AND constituency_id = :constituency_id 
              ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':region_id', $regionId);
    $stmt->bindParam(':constituency_id', $constituencyId);
} else {
    $query = "SELECT id, name, type, location FROM institutions 
              WHERE region_id = :region_id 
              ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':region_id', $regionId);
}

$stmt->execute();
$institutions = $stmt->fetchAll();

echo json_encode($institutions);
