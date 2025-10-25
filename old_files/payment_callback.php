<?php
// Payment callback handler for Hubtel webhook
require_once 'config/database.php';
require_once 'includes/SMSNotifications.php';

// Get callback data
$callbackData = json_decode(file_get_contents('php://input'), true);

// If no JSON data, check GET parameters (for redirect callback)
if (!$callbackData) {
    $callbackData = $_GET;
}

// Log callback for debugging
$logFile = 'logs/payment_callbacks.log';
$logData = date('Y-m-d H:i:s') . " - Callback received: " . json_encode($callbackData) . "\n";
file_put_contents($logFile, $logData, FILE_APPEND);

if (!$callbackData) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No callback data received']);
    exit();
}

// Process the callback
try {
    // Check if this is a Hubtel webhook
    if (isset($callbackData['reference'])) {
        $reference = $callbackData['reference'];

        // Find payment by reference
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE hubtel_reference = ?");
        $stmt->execute([$reference]);
        $payment = $stmt->fetch();

        if ($payment) {
            $newStatus = 'pending';
            $paymentDate = null;

            // Determine status based on callback data
            if (isset($callbackData['status'])) {
                $callbackStatus = strtolower($callbackData['status']);

                switch ($callbackStatus) {
                    case 'success':
                    case 'completed':
                        $newStatus = 'completed';
                        $paymentDate = date('Y-m-d H:i:s');

                        // Send payment confirmation SMS
                        $smsResult = sendPaymentConfirmationSMS($payment['member_id'], $payment['id']);
                        if ($smsResult['success']) {
                            // Log SMS sent
                            error_log("Payment confirmation SMS sent to member ID: " . $payment['member_id']);
                        } else {
                            error_log("Failed to send payment confirmation SMS: " . ($smsResult['error'] ?? 'Unknown error'));
                        }
                        break;
                    case 'failed':
                        $newStatus = 'failed';
                        break;
                    case 'cancelled':
                        $newStatus = 'cancelled';
                        break;
                    default:
                        $newStatus = 'pending';
                }
            }

            // Update payment record
            $stmt = $pdo->prepare("
                UPDATE payments SET
                    status = ?,
                    payment_date = ?,
                    transaction_id = COALESCE(?, transaction_id),
                    notes = CONCAT(COALESCE(notes, ''), ?),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $notes = "\nCallback: " . json_encode($callbackData) . " at " . date('Y-m-d H:i:s');
            $transactionId = $callbackData['transaction_id'] ?? null;

            $stmt->execute([$newStatus, $paymentDate, $transactionId, $notes, $payment['id']]);

            // Log successful update
            $logData = date('Y-m-d H:i:s') . " - Payment updated: ID {$payment['id']}, Status: {$newStatus}\n";
            file_put_contents($logFile, $logData, FILE_APPEND);

            // Send success response to Hubtel
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Payment updated successfully']);
        } else {
            // Payment not found
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
        }
    } else {
        // Invalid callback format
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid callback format']);
    }

} catch (Exception $e) {
    // Log error
    $logData = date('Y-m-d H:i:s') . " - Error processing callback: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $logData, FILE_APPEND);

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}

// For redirect callbacks, show a user-friendly page
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $reference = $_GET['reference'] ?? '';

    $message = '';
    $alertClass = 'info';

    switch ($status) {
        case 'success':
            $message = 'Payment completed successfully! Your dues have been paid.';
            $alertClass = 'success';
            break;
        case 'cancelled':
            $message = 'Payment was cancelled. You can try again anytime.';
            $alertClass = 'warning';
            break;
        default:
            $message = 'Payment status unknown. Please contact support if you have any questions.';
            $alertClass = 'info';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Result - TESCON Ghana</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="alert alert-<?php echo $alertClass; ?> mb-4">
                                <h4><?php echo $status === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-info-circle"></i>'; ?></h4>
                                <p class="mb-0"><?php echo $message; ?></p>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="pay_dues.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Back to Payments
                                </a>
                                <a href="members.php" class="btn btn-secondary">
                                    <i class="fas fa-users"></i> View Members
                                </a>
                            </div>

                            <?php if ($reference): ?>
                                <div class="mt-3 text-muted small">
                                    Reference: <?php echo htmlspecialchars($reference); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Auto redirect after 5 seconds for success
            <?php if ($status === 'success'): ?>
                setTimeout(function() {
                    window.location.href = 'pay_dues.php';
                }, 5000);
            <?php endif; ?>
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>
