<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/VotingConstituency.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

$votingConstituency = new VotingConstituency($db);

if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Voting constituency ID not provided');
    redirect('voting_constituencies.php');
}

$id = (int)$_GET['id'];
$constituency = $votingConstituency->getById($id);

if (!$constituency) {
    setFlashMessage('danger', 'Voting constituency not found');
    redirect('voting_constituencies.php');
}

// Check if any members are using this voting constituency
$checkQuery = "SELECT COUNT(*) as total FROM members WHERE voting_constituency_id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $id);
$checkStmt->execute();
$memberCount = $checkStmt->fetch()['total'];

if ($memberCount > 0) {
    setFlashMessage('danger', "Cannot delete voting constituency. It is assigned to $memberCount member(s).");
    redirect('voting_constituencies.php');
}

if ($votingConstituency->delete($id)) {
    setFlashMessage('success', 'Voting constituency deleted successfully');
} else {
    setFlashMessage('danger', 'Failed to delete voting constituency');
}

redirect('voting_constituencies.php');
?>
