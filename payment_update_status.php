<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';

// Only Admin and Executive can update payment status
if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to update payment status');
    redirect('payments.php');
}

$database = new Database();
$db = $database->getConnection();

$payment = new Payment($db);

// Get payment ID and status
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    setFlashMessage('danger', 'Invalid request');
    redirect('payments.php');
}

$paymentId = (int)$_GET['id'];
$newStatus = sanitize($_GET['status']);

// Validate status
$validStatuses = ['pending', 'completed', 'failed', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    setFlashMessage('danger', 'Invalid payment status');
    redirect('payment_view.php?id=' . $paymentId);
}

// Get payment details
$paymentData = $payment->getById($paymentId);

if (!$paymentData) {
    setFlashMessage('danger', 'Payment not found');
    redirect('payments.php');
}

try {
    $db->beginTransaction();
    
    // Update payment status
    $updateData = [
        'status' => $newStatus
    ];
    
    // If marking as completed, set payment_date
    if ($newStatus === 'completed') {
        $updateData['payment_date'] = date('Y-m-d H:i:s');
    }
    
    $payment->update($paymentId, $updateData);
    
    // Note: dues table doesn't have a status column
    // Payment status is the source of truth for whether a due is paid
    
    $db->commit();
    
    $statusLabel = ucfirst($newStatus);
    setFlashMessage('success', "Payment status updated to {$statusLabel} successfully");
    
} catch (Exception $e) {
    $db->rollBack();
    setFlashMessage('danger', 'Failed to update payment status: ' . $e->getMessage());
}

redirect('payment_view.php?id=' . $paymentId);
