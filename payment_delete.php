<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';

// Only admins can delete payments
requireRole('Admin');

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
