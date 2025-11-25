<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/HubtelCheckout.php';

$pageTitle = 'Payment Status';

$database = new Database();
$db = $database->getConnection();

$transactionRef = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';
$payment = null;
$statusChecked = false;

if ($transactionRef) {
    // Get payment from database
    $query = "SELECT p.*, d.year, d.amount as due_amount, m.fullname 
              FROM payments p
              LEFT JOIN dues d ON p.dues_id = d.id
              LEFT JOIN members m ON p.member_id = m.id
              WHERE p.transaction_id = :transaction_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':transaction_id', $transactionRef);
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If payment is still pending, check status with Hubtel
    if ($payment && $payment['status'] === 'pending') {
        try {
            $hubtel = new HubtelCheckout(
                HUBTEL_CLIENT_ID,
                HUBTEL_CLIENT_SECRET,
                HUBTEL_MERCHANT_ACCOUNT
            );
            
            $statusResult = $hubtel->checkTransactionStatus($transactionRef);
            $statusChecked = true;
            
            if ($statusResult['success'] && isset($statusResult['data']['data'])) {
                $statusData = $statusResult['data']['data'];
                
                // Update payment based on status check
                if ($statusData['status'] === 'Paid') {
                    $updateQuery = "UPDATE payments 
                                   SET status = 'completed', 
                                       payment_date = NOW(),
                                       notes = CONCAT(notes, ' | Status checked: Paid')
                                   WHERE id = :payment_id";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':payment_id', $payment['id']);
                    $updateStmt->execute();
                    
                    $payment['status'] = 'completed';
                } elseif ($statusData['status'] === 'Unpaid') {
                    $payment['status'] = 'pending';
                }
            }
        } catch (Exception $e) {
            // Log error but don't show to user
            error_log("Status check failed: " . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="text-center mb-4">
            <?php if ($payment): ?>
                <?php if ($payment['status'] === 'completed'): ?>
                    <div class="mb-4">
                        <div class="success-icon mx-auto" style="width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="cil-check" style="font-size: 3rem; color: white;"></i>
                        </div>
                    </div>
                    <h2 class="text-success mb-3">Payment Successful!</h2>
                    <p class="text-muted">Your payment has been processed successfully.</p>
                <?php elseif ($payment['status'] === 'pending'): ?>
                    <div class="mb-4">
                        <div class="pending-icon mx-auto" style="width: 80px; height: 80px; background: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="cil-clock" style="font-size: 3rem; color: white;"></i>
                        </div>
                    </div>
                    <h2 class="text-warning mb-3">Payment Pending</h2>
                    <p class="text-muted">Your payment is being processed. This may take a few minutes.</p>
                    <?php if ($statusChecked): ?>
                        <div class="alert alert-info mt-3">
                            <i class="cil-info"></i> We've checked with Hubtel and your payment is still being processed. 
                            Please check back in a few minutes or contact support if this persists.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="mb-4">
                        <div class="failed-icon mx-auto" style="width: 80px; height: 80px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="cil-x" style="font-size: 3rem; color: white;"></i>
                        </div>
                    </div>
                    <h2 class="text-danger mb-3">Payment Failed</h2>
                    <p class="text-muted">Unfortunately, your payment could not be completed.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="mb-4">
                    <div class="error-icon mx-auto" style="width: 80px; height: 80px; background: #6b7280; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="cil-warning" style="font-size: 3rem; color: white;"></i>
                    </div>
                </div>
                <h2 class="text-muted mb-3">Payment Not Found</h2>
                <p class="text-muted">We couldn't find a payment with this reference.</p>
            <?php endif; ?>
        </div>

        <?php if ($payment): ?>
        <div class="card">
            <div class="card-header">
                <strong>Payment Details</strong>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Transaction Reference</label>
                        <div><strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <div>
                            <?php if ($payment['status'] === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php elseif ($payment['status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Member Name</label>
                        <div><strong><?php echo htmlspecialchars($payment['fullname']); ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Amount Paid</label>
                        <div class="fs-4 text-success"><strong>GH₵<?php echo number_format($payment['amount'], 2); ?></strong></div>
                    </div>
                </div>

                <?php if ($payment['year']): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Academic Year</label>
                        <div><strong><?php echo htmlspecialchars($payment['year']); ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Payment Method</label>
                        <div><strong><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></strong></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($payment['payment_date']): ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="text-muted small">Payment Date</label>
                        <div><strong><?php echo date('F j, Y g:i A', strtotime($payment['payment_date'])); ?></strong></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($payment['notes']): ?>
                <div class="row">
                    <div class="col-md-12">
                        <label class="text-muted small">Notes</label>
                        <div class="small"><?php echo htmlspecialchars($payment['notes']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <?php if ($payment['status'] === 'pending'): ?>
                <a href="payment_success.php?ref=<?php echo urlencode($transactionRef); ?>" class="btn btn-primary me-2">
                    <i class="cil-reload"></i> Refresh Status
                </a>
            <?php endif; ?>
            
            <a href="payments.php" class="btn btn-secondary me-2">
                <i class="cil-list"></i> View All Payments
            </a>
            
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="cil-home"></i> Go to Dashboard
            </a>
        </div>
        <?php else: ?>
        <div class="text-center mt-4">
            <a href="pay_dues.php" class="btn btn-primary me-2">
                <i class="cil-dollar"></i> Make a Payment
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="cil-home"></i> Go to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
