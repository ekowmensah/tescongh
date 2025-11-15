<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';

$pageTitle = 'Pay Dues';

$database = new Database();
$db = $database->getConnection();

// Get current member info
$currentUserId = $_SESSION['user_id'];
$memberQuery = "SELECT m.*, u.email FROM members m 
                LEFT JOIN users u ON m.user_id = u.id 
                WHERE m.user_id = :user_id";
$stmt = $db->prepare($memberQuery);
$stmt->bindParam(':user_id', $currentUserId);
$stmt->execute();
$memberData = $stmt->fetch();

if (!$memberData) {
    setFlashMessage('danger', 'Member profile not found');
    redirect('dashboard.php');
}

// Get current academic year
$currentYear = date('Y');

// Get all available dues from dues table (not yet paid by this member)
$availableDuesQuery = "SELECT d.* 
                       FROM dues d
                       WHERE d.id NOT IN (
                           SELECT COALESCE(p.dues_id, 0)
                           FROM payments p 
                           WHERE p.member_id = :member_id 
                           AND p.status = 'completed'
                           AND p.dues_id IS NOT NULL
                       )
                       ORDER BY d.year DESC";
$availableStmt = $db->prepare($availableDuesQuery);
$availableStmt->bindParam(':member_id', $memberData['id']);
$availableStmt->execute();
$availableDues = $availableStmt->fetchAll();

// Get all unpaid dues for this member (pending/failed payments)
$unpaidDuesQuery = "SELECT d.*, p.status as payment_status, p.id as payment_id
                    FROM payments p
                    INNER JOIN dues d ON p.dues_id = d.id
                    WHERE p.member_id = :member_id 
                    AND p.status IN ('pending', 'failed')
                    ORDER BY d.year DESC";
$unpaidStmt = $db->prepare($unpaidDuesQuery);
$unpaidStmt->bindParam(':member_id', $memberData['id']);
$unpaidStmt->execute();
$unpaidDues = $unpaidStmt->fetchAll();

// Check if already paid for current year
$checkPaymentQuery = "SELECT p.*, d.year, d.amount 
                      FROM payments p
                      LEFT JOIN dues d ON p.dues_id = d.id
                      WHERE p.member_id = :member_id AND d.year = :year AND p.status = 'completed'";
$checkStmt = $db->prepare($checkPaymentQuery);
$checkStmt->bindParam(':member_id', $memberData['id']);
$checkStmt->bindParam(':year', $currentYear);
$checkStmt->execute();
$existingPayment = $checkStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $payment_method = sanitize($_POST['payment_method']);
    $dues_id = !empty($_POST['dues_id']) ? (int)$_POST['dues_id'] : null;
    
    // Validate
    if (empty($dues_id)) {
        setFlashMessage('danger', 'Please select a due to pay');
    } elseif ($amount < 10) {
        setFlashMessage('danger', 'Minimum payment amount is GH₵10.00');
    } else {
        try {
            $db->beginTransaction();
            
            // Verify the due exists and is available
            $verifyQuery = "SELECT d.* FROM dues d WHERE d.id = :dues_id";
            $verifyStmt = $db->prepare($verifyQuery);
            $verifyStmt->bindParam(':dues_id', $dues_id);
            $verifyStmt->execute();
            $dueToPayFor = $verifyStmt->fetch();
            
            if (!$dueToPayFor) {
                throw new Exception('Invalid due selected');
            }
            
            // Check if member already has a paid payment for this due
            $checkPaidQuery = "SELECT id FROM payments 
                              WHERE member_id = :member_id 
                              AND dues_id = :dues_id 
                              AND status = 'completed'";
            $checkStmt = $db->prepare($checkPaidQuery);
            $checkStmt->bindParam(':member_id', $memberData['id']);
            $checkStmt->bindParam(':dues_id', $dues_id);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                throw new Exception('You have already paid this due');
            }
            
            $finalDuesId = $dues_id;
            
            // Create payment record
            $transactionId = 'TXN' . time() . rand(1000, 9999);
            $paymentQuery = "INSERT INTO payments (member_id, dues_id, amount, payment_method, transaction_id, status, notes, created_at) 
                            VALUES (:member_id, :dues_id, :amount, :payment_method, :transaction_id, 'pending', :notes, NOW())";
            $paymentStmt = $db->prepare($paymentQuery);
            $paymentStmt->bindParam(':member_id', $memberData['id']);
            $paymentStmt->bindParam(':dues_id', $finalDuesId);
            $paymentStmt->bindParam(':amount', $amount);
            $paymentStmt->bindParam(':payment_method', $payment_method);
            $paymentStmt->bindParam(':transaction_id', $transactionId);
            $paymentStmt->bindValue(':notes', 'Self-service payment via member portal');
            $paymentStmt->execute();
            
            $db->commit();
            
            setFlashMessage('success', 'Payment initiated successfully! Your payment is pending confirmation.');
            redirect('payments.php');
            
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('danger', 'Failed to process payment: ' . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Pay Membership Dues</h2>
                <p class="text-muted">Make your annual membership payment</p>
            </div>
            <a href="payments.php" class="btn btn-secondary">
                <i class="cil-arrow-left"></i> Back to Payments
            </a>
        </div>

        <?php if ($existingPayment): ?>
        <div class="alert alert-success">
            <i class="cil-check-circle"></i> <strong>Already Paid!</strong> 
            You have already paid your dues for the year <?php echo $currentYear; ?>. 
            Amount: <strong><?php echo formatCurrency($existingPayment['amount']); ?></strong>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <strong>Payment Information</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($memberData['fullname']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($memberData['email']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($memberData['phone']); ?>" readonly>
                            </div>

                            <hr>

                            <?php if (!empty($unpaidDues)): ?>
                            <div class="alert alert-warning">
                                <i class="cil-warning"></i> <strong>You have <?php echo count($unpaidDues); ?> pending payment(s)!</strong>
                                <small class="d-block mt-1">These are payments awaiting confirmation.</small>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($availableDues)): ?>
                            <div class="mb-3">
                                <label class="form-label">Select Due to Pay <span class="text-danger">*</span></label>
                                <select class="form-select" name="dues_id" id="dues_select" required>
                                    <option value="">-- Select a due --</option>
                                    <?php foreach ($availableDues as $due): ?>
                                        <option value="<?php echo $due['id']; ?>" 
                                                data-amount="<?php echo $due['amount']; ?>" 
                                                data-year="<?php echo $due['year']; ?>">
                                            <?php echo $due['year']; ?> Academic Year - GH₵<?php echo number_format($due['amount'], 2); ?>
                                            <?php if (isset($due['status']) && $due['status'] == 'Pending'): ?>
                                                <span class="text-muted">(Pending)</span>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Select which due you want to pay from available dues</small>
                            </div>
                            <input type="hidden" name="payment_type" value="existing">
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="cil-info"></i> <strong>No dues available!</strong> All dues have been paid or are pending payment.
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Amount (GH₵) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="amount" id="amount_input" min="10" step="0.01" value="50.00" required>
                                <small class="text-muted">Minimum: GH₵10.00 | Recommended: GH₵50.00</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="hubtel_mobile">Mobile Money (Hubtel)</option>
                                    <option value="hubtel_card">Card Payment (Hubtel)</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash Payment</option>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <strong><i class="cil-info"></i> Note:</strong> After submitting, your payment will be marked as "Pending" until confirmed by an administrator.
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="cil-dollar"></i> Submit Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <strong>Payment Summary</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Member</label>
                            <div><strong><?php echo htmlspecialchars($memberData['fullname']); ?></strong></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Student ID</label>
                            <div><strong><?php echo htmlspecialchars($memberData['student_id']); ?></strong></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Institution</label>
                            <div><strong><?php echo htmlspecialchars($memberData['institution']); ?></strong></div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="text-muted small">Recommended Amount</label>
                            <div class="fs-4 text-success"><strong>GH₵50.00</strong></div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <strong>Need Help?</strong>
                    </div>
                    <div class="card-body">
                        <p class="small">If you have any issues with payment, please contact:</p>
                        <p class="small mb-0">
                            <i class="cil-envelope-closed"></i> uewtescon@gmail.com<br>
                            <i class="cil-phone"></i> +233 243 115 135
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill amount when due is selected
document.getElementById('dues_select')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    const year = selectedOption.getAttribute('data-year');
    const amountInput = document.getElementById('amount_input');
    
    if (amount && this.value) {
        // Auto-fill and lock the amount
        amountInput.value = parseFloat(amount).toFixed(2);
        amountInput.readOnly = true;
        amountInput.classList.add('bg-light');
        amountInput.required = true;
    } else {
        // Reset if no due selected
        amountInput.value = '50.00';
        amountInput.readOnly = false;
        amountInput.classList.remove('bg-light');
        amountInput.required = true;
    }
});

// Trigger on page load if there's only one due
window.addEventListener('DOMContentLoaded', function() {
    const duesSelect = document.getElementById('dues_select');
    if (duesSelect && duesSelect.options.length === 2) {
        // Only one due available (plus the default option)
        duesSelect.selectedIndex = 1;
        duesSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>
