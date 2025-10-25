<?php
require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

if (empty($phone)) {
    echo json_encode(['exists' => false, 'message' => 'Phone number is required']);
    exit;
}

// Validate phone format (10 digits starting with 0)
if (strlen($phone) !== 10 || !preg_match('/^0\d{9}$/', $phone)) {
    echo json_encode(['exists' => false, 'message' => 'Invalid phone format']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check if phone exists in members table
$query = "SELECT id FROM members WHERE phone = :phone LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':phone', $phone);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['exists' => true, 'message' => 'Phone number already registered']);
} else {
    echo json_encode(['exists' => false, 'message' => 'Phone number available']);
}
