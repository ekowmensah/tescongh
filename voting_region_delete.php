<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/VotingRegion.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

$votingRegion = new VotingRegion($db);

if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Voting region ID not provided');
    redirect('voting_regions.php');
}

$id = (int)$_GET['id'];
$region = $votingRegion->getById($id);

if (!$region) {
    setFlashMessage('danger', 'Voting region not found');
    redirect('voting_regions.php');
}

// Check if any constituencies are using this voting region
$checkQuery = "SELECT COUNT(*) as total FROM voting_constituencies WHERE voting_region_id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $id);
$checkStmt->execute();
$constituencyCount = $checkStmt->fetch()['total'];

if ($constituencyCount > 0) {
    setFlashMessage('danger', "Cannot delete voting region. It has $constituencyCount voting constituency(ies). Please delete constituencies first.");
    redirect('voting_regions.php');
}

// Check if any members are using this voting region
$memberCheckQuery = "SELECT COUNT(*) as total FROM members WHERE voting_region_id = :id";
$memberCheckStmt = $db->prepare($memberCheckQuery);
$memberCheckStmt->bindParam(':id', $id);
$memberCheckStmt->execute();
$memberCount = $memberCheckStmt->fetch()['total'];

if ($memberCount > 0) {
    setFlashMessage('danger', "Cannot delete voting region. It is assigned to $memberCount member(s).");
    redirect('voting_regions.php');
}

if ($votingRegion->delete($id)) {
    setFlashMessage('success', 'Voting region deleted successfully');
} else {
    setFlashMessage('danger', 'Failed to delete voting region');
}

redirect('voting_regions.php');
?>
