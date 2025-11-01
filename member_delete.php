<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';

// Only admins can delete members
if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to perform this action');
    redirect('members.php');
}

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);

// Get member ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Member ID not provided');
    redirect('members.php');
}

$memberId = (int)$_GET['id'];

// Get member data to verify it exists
$memberData = $member->getById($memberId);

if (!$memberData) {
    setFlashMessage('danger', 'Member not found');
    redirect('members.php');
}

// Prevent deleting yourself
if ($memberData['user_id'] == $_SESSION['user_id']) {
    setFlashMessage('danger', 'You cannot delete your own account');
    redirect('members.php');
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Delete related records first to maintain referential integrity
    
    // Delete campus executive assignments
    $stmt = $db->prepare("DELETE FROM campus_executives WHERE member_id = :member_id");
    $stmt->bindParam(':member_id', $memberId);
    $stmt->execute();
    
    // Note: Patrons are stored in members table with position='Patron', not in a separate table
    // No need to delete from campus_patrons as that table doesn't exist
    
    // Delete payment records
    $stmt = $db->prepare("DELETE FROM payments WHERE member_id = :member_id");
    $stmt->bindParam(':member_id', $memberId);
    $stmt->execute();
    
    // Delete the member
    if ($member->delete($memberId)) {
        // Delete the associated user account if exists
        if ($memberData['user_id']) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $memberData['user_id']);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        setFlashMessage('success', 'Member deleted successfully');
        redirect('members.php');
    } else {
        // Rollback on failure
        $db->rollBack();
        setFlashMessage('danger', 'Failed to delete member');
        redirect('members.php');
    }
} catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    error_log("Member delete error: " . $e->getMessage());
    setFlashMessage('danger', 'An error occurred while deleting the member: ' . $e->getMessage());
    redirect('members.php');
}
