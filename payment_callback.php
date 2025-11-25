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
    // Find payment by client reference (transaction ID)
    $query = "SELECT * FROM payments WHERE transaction_id = :transaction_id LIMIT 1";
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
