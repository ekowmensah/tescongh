<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';

$database = new Database();
$db = $database->getConnection();

$payment = new Payment($db);

// Get payment ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Payment ID not provided');
    redirect('payments.php');
}

$paymentId = (int)$_GET['id'];

// Get payment data to verify it exists
$paymentData = $payment->getById($paymentId);

if (!$paymentData) {
    setFlashMessage('danger', 'Payment not found');
    redirect('payments.php');
}

// Check permissions
$isAdmin = hasRole('Admin');
$isRegularMember = hasRole('Member') && !hasAnyRole(['Admin', 'Executive', 'Patron']);

if (!$isAdmin && !$isRegularMember) {
    setFlashMessage('danger', 'You do not have permission to perform this action');
    redirect('payments.php');
}

// Check if payment is completed - NO ONE can delete completed payments
if ($paymentData['status'] === 'completed') {
    setFlashMessage('danger', 'Completed payments cannot be deleted. Payment records must be preserved for accounting purposes.');
    redirect('payments.php');
}

// If regular member, check ownership and status
if ($isRegularMember) {
    // Get member ID for current user
    $currentUserId = $_SESSION['user_id'];
    $memberQuery = "SELECT id FROM members WHERE user_id = :user_id";
    $stmt = $db->prepare($memberQuery);
    $stmt->bindParam(':user_id', $currentUserId);
    $stmt->execute();
    $currentMember = $stmt->fetch();
    
    if (!$currentMember) {
        setFlashMessage('danger', 'Member profile not found');
        redirect('payments.php');
    }
    
    // Check if payment belongs to this member
    if ($paymentData['member_id'] != $currentMember['id']) {
        setFlashMessage('danger', 'You can only delete your own payments');
        redirect('payments.php');
    }
    
    // Check if payment is pending or failed
    if (!in_array($paymentData['status'], ['pending', 'failed'])) {
        setFlashMessage('danger', 'You can only delete pending or failed payments');
        redirect('payments.php');
    }
}

try {
    // Delete the payment
    if ($payment->delete($paymentId)) {
        setFlashMessage('success', 'Payment record deleted successfully');
        redirect('payments.php');
    } else {
        setFlashMessage('danger', 'Failed to delete payment record');
        redirect('payments.php');
    }
} catch (Exception $e) {
    error_log("Payment delete error: " . $e->getMessage());
    setFlashMessage('danger', 'An error occurred while deleting the payment: ' . $e->getMessage());
    redirect('payments.php');
}
