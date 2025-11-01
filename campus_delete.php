<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';

// Only admins can delete campuses
requireRole('Admin');

$database = new Database();
$db = $database->getConnection();

$campus = new Campus($db);

// Get campus ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Campus ID not provided');
    redirect('campuses.php');
}

$campusId = (int)$_GET['id'];

// Get campus data to verify it exists
$campusData = $campus->getById($campusId);

if (!$campusData) {
    setFlashMessage('danger', 'Campus not found');
    redirect('campuses.php');
}

// Check if campus has members
$stmt = $db->prepare("SELECT COUNT(*) as count FROM members WHERE campus_id = :campus_id");
$stmt->bindParam(':campus_id', $campusId);
$stmt->execute();
$memberCount = $stmt->fetch()['count'];

if ($memberCount > 0) {
    setFlashMessage('danger', "Cannot delete campus. It has {$memberCount} member(s) assigned. Please reassign or remove members first.");
    redirect('campuses.php');
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Delete related records first to maintain referential integrity
    
    // Delete campus executive assignments
    $stmt = $db->prepare("DELETE FROM campus_executives WHERE campus_id = :campus_id");
    $stmt->bindParam(':campus_id', $campusId);
    $stmt->execute();
    
    // Delete campus patron assignments
    $stmt = $db->prepare("DELETE FROM campus_patrons WHERE campus_id = :campus_id");
    $stmt->bindParam(':campus_id', $campusId);
    $stmt->execute();
    
    // Delete the campus
    if ($campus->delete($campusId)) {
        // Commit transaction
        $db->commit();
        
        setFlashMessage('success', 'Campus deleted successfully');
        redirect('campuses.php');
    } else {
        // Rollback on failure
        $db->rollBack();
        setFlashMessage('danger', 'Failed to delete campus');
        redirect('campuses.php');
    }
} catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    error_log("Campus delete error: " . $e->getMessage());
    setFlashMessage('danger', 'An error occurred while deleting the campus: ' . $e->getMessage());
    redirect('campuses.php');
}
