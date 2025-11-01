<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Institution.php';

// Only admins can delete institutions
if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to perform this action');
    redirect('institutions.php');
}

$database = new Database();
$db = $database->getConnection();

$institution = new Institution($db);

// Get institution ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Institution ID not provided');
    redirect('institutions.php');
}

$institutionId = (int)$_GET['id'];

// Get institution data to verify it exists
$institutionData = $institution->getById($institutionId);

if (!$institutionData) {
    setFlashMessage('danger', 'Institution not found');
    redirect('institutions.php');
}

// Check if institution has campuses
$stmt = $db->prepare("SELECT COUNT(*) as count FROM campuses WHERE institution_id = :institution_id");
$stmt->bindParam(':institution_id', $institutionId);
$stmt->execute();
$campusCount = $stmt->fetch()['count'];

if ($campusCount > 0) {
    setFlashMessage('danger', "Cannot delete institution. It has {$campusCount} campus(es) assigned. Please delete campuses first.");
    redirect('institutions.php');
}

try {
    // Delete the institution
    if ($institution->delete($institutionId)) {
        setFlashMessage('success', 'Institution deleted successfully');
        redirect('institutions.php');
    } else {
        setFlashMessage('danger', 'Failed to delete institution');
        redirect('institutions.php');
    }
} catch (Exception $e) {
    error_log("Institution delete error: " . $e->getMessage());
    setFlashMessage('danger', 'An error occurred while deleting the institution: ' . $e->getMessage());
    redirect('institutions.php');
}
