<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an executive or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $memberId = $_GET['id'];

    try {
        $stmt = $pdo->prepare("
            SELECT m.*, u.email, u.role
            FROM members m
            JOIN users u ON m.user_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'member' => $member]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Member not found']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Member ID required']);
}
?>
