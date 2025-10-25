<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Member.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);
$members = $member->getAll(10000, 0); // Get all members

// Return simplified data
$result = array_map(function($m) {
    return [
        'id' => $m['id'],
        'fullname' => $m['fullname'],
        'student_id' => $m['student_id'],
        'phone' => $m['phone']
    ];
}, $members);

echo json_encode($result);
