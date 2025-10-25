<?php
require_once '../config/config.php';
require_once '../config/Database.php';

header('Content-Type: application/json');

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    echo json_encode(['exists' => false, 'message' => 'Email is required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['exists' => false, 'message' => 'Invalid email format']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check if email exists in users table
$query = "SELECT id FROM users WHERE email = :email LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['exists' => true, 'message' => 'Email already registered']);
} else {
    echo json_encode(['exists' => false, 'message' => 'Email available']);
}
