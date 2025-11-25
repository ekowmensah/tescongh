<?php
/**
 * Retry Payment Handler
 * 
 * Re-initiates Hubtel checkout for pending or failed payments
 */

require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/HubtelCheckout.php';

$database = new Database();
$db = $database->getConnection();

// Get payment ID
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paymentId) {
    setFlashMessage('danger', 'Invalid payment ID');
    redirect('payments.php');
}

// Get payment details with member and dues info
$query = "SELECT p.*, m.fullname, m.phone, m.user_id, u.email, d.year, d.amount as due_amount
          FROM payments p
          JOIN members m ON p.member_id = m.id
          JOIN users u ON m.user_id = u.id
          LEFT JOIN dues d ON p.dues_id = d.id
          WHERE p.id = :payment_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':payment_id', $paymentId);
$stmt->execute();
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    setFlashMessage('danger', 'Payment not found');
    redirect('payments.php');
}

// Check if user owns this payment (regular members only)
if (hasRole('Member') && !hasAnyRole(['Admin', 'Executive'])) {
    if ($payment['user_id'] != $_SESSION['user_id']) {
        setFlashMessage('danger', 'Access denied');
        redirect('payments.php');
    }
}

// Check if payment can be retried
if (!in_array($payment['status'], ['pending', 'failed'])) {
    setFlashMessage('warning', 'Only pending or failed payments can be retried');
    redirect('payments.php');
}

// Check if payment method is Hubtel
if (!in_array($payment['payment_method'], ['hubtel_mobile', 'hubtel_card'])) {
    setFlashMessage('warning', 'Only Hubtel payments can be retried online. Please contact support for other payment methods.');
    redirect('payments.php');
}

try {
    // Update payment status back to pending if it was failed
    if ($payment['status'] === 'failed') {
        $updateQuery = "UPDATE payments SET status = 'pending', updated_at = NOW() WHERE id = :payment_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':payment_id', $paymentId);
        $updateStmt->execute();
    }
    
    // Initialize Hubtel checkout
    $hubtel = new HubtelCheckout(
        HUBTEL_CLIENT_ID,
        HUBTEL_CLIENT_SECRET,
        HUBTEL_MERCHANT_ACCOUNT
    );
    
    // Generate NEW transaction ID for retry (Hubtel requires unique clientReference)
    $transactionId = 'TXN' . time() . rand(1000, 9999);
    
    // Update transaction ID
    $updateTxnQuery = "UPDATE payments SET transaction_id = :transaction_id WHERE id = :payment_id";
    $updateTxnStmt = $db->prepare($updateTxnQuery);
    $updateTxnStmt->bindParam(':transaction_id', $transactionId);
    $updateTxnStmt->bindParam(':payment_id', $paymentId);
    $updateTxnStmt->execute();
    
    $year = $payment['year'] ? $payment['year'] : 'N/A';
    $description = "UEW-TESCON Membership Dues {$year} - {$payment['fullname']} (Retry)";
    $callbackUrl = APP_URL . '/payment_callback.php';
    $returnUrl = APP_URL . '/payments.php?payment_success=1&ref=' . $transactionId;
    $cancellationUrl = APP_URL . '/payments.php?cancelled=1';
    
    // Initiate checkout
    $result = $hubtel->initiateCheckout(
        $payment['amount'],
        $description,
        $callbackUrl,
        $returnUrl,
        $transactionId,
        [
            'payeeName' => $payment['fullname'],
            'payeeMobileNumber' => $payment['phone'],
            'payeeEmail' => $payment['email'],
            'cancellationUrl' => $cancellationUrl
        ]
    );
    
    if ($result['success']) {
        // Store/update Hubtel checkout ID
        $updateQuery = "UPDATE payments 
                       SET hubtel_reference = :checkout_id,
                           notes = CONCAT(COALESCE(notes, ''), ' | Retried on ', NOW()),
                           updated_at = NOW()
                       WHERE id = :payment_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':checkout_id', $result['data']['data']['checkoutId']);
        $updateStmt->bindParam(':payment_id', $paymentId);
        $updateStmt->execute();
        
        // Redirect to Hubtel checkout page
        header('Location: ' . $result['data']['data']['checkoutUrl']);
        exit;
    } else {
        // Log detailed error for debugging
        $errorDetails = "Retry Payment Error - Payment ID: {$paymentId}, Amount: {$payment['amount']}, Transaction ID: {$transactionId}, Error: {$result['error']}, HTTP Code: {$result['http_code']}";
        error_log($errorDetails);
        
        // User-friendly error message
        $errorMsg = $result['error'];
        if ($result['http_code'] == 401) {
            $errorMsg = 'Invalid Hubtel credentials. Please contact support.';
        } elseif ($result['http_code'] == 400) {
            $errorMsg = 'Invalid payment parameters. Please contact support with Payment ID: ' . $paymentId;
        }
        
        throw new Exception($errorMsg);
    }
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Failed to retry payment: ' . $e->getMessage());
    redirect('payments.php');
}
