<?php
/**
 * Hubtel Payment Callback Handler
 * 
 * This endpoint receives payment status notifications from Hubtel
 * and updates the payment record accordingly.
 */

require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'classes/HubtelCheckout.php';
require_once 'classes/HubtelSMS.php';

// Log all incoming requests for debugging
$logFile = __DIR__ . '/logs/payment_callback.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$requestBody = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');
$logEntry = "\n\n=== Payment Callback Received ===\n";
$logEntry .= "Timestamp: {$timestamp}\n";
$logEntry .= "Method: {$_SERVER['REQUEST_METHOD']}\n";
$logEntry .= "IP: {$_SERVER['REMOTE_ADDR']}\n";
$logEntry .= "Body: {$requestBody}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Parse callback data
$callbackData = HubtelCheckout::parseCallback($requestBody);

if (!$callbackData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid callback data']);
    exit;
}

// Log parsed data
file_put_contents($logFile, "Parsed Data: " . print_r($callbackData, true) . "\n", FILE_APPEND);

// Connect to database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Find payment with member and dues info
    $query = "SELECT p.*, m.fullname, m.phone, m.student_id, d.year, d.amount as due_amount
              FROM payments p
              JOIN members m ON p.member_id = m.id
              LEFT JOIN dues d ON p.dues_id = d.id
              WHERE p.transaction_id = :transaction_id 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':transaction_id', $callbackData['clientReference']);
    $stmt->execute();
    
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found']);
        file_put_contents($logFile, "ERROR: Payment not found for reference: {$callbackData['clientReference']}\n", FILE_APPEND);
        exit;
    }
    
    // Update payment status based on callback
    $status = 'pending';
    $notes = $callbackData['description'] ?? '';
    
    if ($callbackData['transactionStatus'] === 'Success' || $callbackData['status'] === 'Success') {
        $status = 'completed';
        $paymentDate = date('Y-m-d H:i:s');
    } elseif ($callbackData['transactionStatus'] === 'Failed') {
        $status = 'failed';
        $paymentDate = null;
    } else {
        $paymentDate = null;
    }
    
    // Update payment record
    $updateQuery = "UPDATE payments 
                   SET status = :status,
                       payment_date = :payment_date,
                       notes = :notes,
                       updated_at = NOW()
                   WHERE id = :payment_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':status', $status);
    $updateStmt->bindParam(':payment_date', $paymentDate);
    $updateStmt->bindParam(':notes', $notes);
    $updateStmt->bindParam(':payment_id', $payment['id']);
    $updateStmt->execute();
    
    // Log success
    file_put_contents($logFile, "SUCCESS: Payment {$payment['id']} updated to status: {$status}\n", FILE_APPEND);
    
    // Send SMS if payment is successful
    if ($status === 'completed' && !empty($payment['phone'])) {
        try {
            $sms = new HubtelSMS(HUBTEL_SMS_CLIENT_ID, HUBTEL_SMS_CLIENT_SECRET, SMS_SENDER_ID);
            
            // Build SMS message
            $year = $payment['year'] ? $payment['year'] : 'N/A';
            $amount = number_format($payment['amount'], 2);
            // Extract first name from fullname
            $firstName = explode(' ', $payment['fullname'])[0];
            $message = "Hi {$firstName}, You have paid GH₵{$amount} as {$year} dues. Thank You!";
            
            // Send SMS
            $smsResult = $sms->sendSimplePOST($payment['phone'], $message);
            
            // Log SMS
            if ($smsResult['success']) {
                $message_id = isset($smsResult['data']['messageId']) ? $smsResult['data']['messageId'] : null;
                $logQuery = "INSERT INTO sms_logs (sender_id, recipient_phone, message, message_id, status) 
                            VALUES (:sender_id, :phone, :message, :message_id, 'sent')";
                $logStmt = $db->prepare($logQuery);
                $logStmt->bindParam(':sender_id', $payment['member_id']);
                $logStmt->bindParam(':phone', $payment['phone']);
                $logStmt->bindParam(':message', $message);
                $logStmt->bindParam(':message_id', $message_id);
                $logStmt->execute();
                
                file_put_contents($logFile, "SMS sent to {$payment['phone']} for payment {$payment['id']}\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "SMS failed for payment {$payment['id']}: " . ($smsResult['error'] ?? 'Unknown error') . "\n", FILE_APPEND);
            }
        } catch (Exception $smsError) {
            file_put_contents($logFile, "SMS exception for payment {$payment['id']}: " . $smsError->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Send success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated',
        'payment_id' => $payment['id'],
        'status' => $status
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update payment: ' . $e->getMessage()]);
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
