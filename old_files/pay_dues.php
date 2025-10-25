<?php
/**
 * TESCON Ghana - Pay Membership Dues
 */
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/HubtelPayment.php';

startSecureSession();
requireLogin();

$pageTitle = "Pay Membership Dues";
$breadcrumbs = [
    ['title' => 'Payments', 'url' => '#'],
    ['title' => 'Pay Dues', 'url' => '#']
];
$error = '';
$success = '';
$checkoutUrl = '';

$userId = $_SESSION['user_id'];

// Get current dues for the year
$currentYear = date('Y');
try {
    $stmt = $pdo->prepare("SELECT * FROM dues WHERE year = ?");
    $stmt->execute([$currentYear]);
    $currentDues = $stmt->fetch();

    if (!$currentDues) {
        $error = 'No dues configured for the current year.';
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Check if user has already paid for current year
$paymentStatus = 'unpaid';
try {
    $stmt = $pdo->prepare("
        SELECT p.* FROM payments p
        JOIN dues d ON p.dues_id = d.id
        WHERE p.member_id = ? AND d.year = ? AND p.status = 'completed'
        ORDER BY p.payment_date DESC LIMIT 1
    ");
    $stmt->execute([$userId, $currentYear]);
    $existingPayment = $stmt->fetch();

    if ($existingPayment) {
        $paymentStatus = 'paid';
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_payment'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('csrf_failure', ['page' => 'pay_dues']);
    } else {
    if ($paymentStatus === 'paid') {
        $error = 'You have already paid dues for this year.';
    } elseif (!$currentDues) {
        $error = 'No dues configured for the current year.';
    } else {
        $phone = trim($_POST['phone']);
        $paymentMethod = $_POST['payment_method'];

        if (empty($phone)) {
            $error = 'Phone number is required.';
        } else {
            // Create payment record
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO payments (member_id, dues_id, amount, payment_method, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$userId, $currentDues['id'], $currentDues['amount'], $paymentMethod]);

                $paymentId = $pdo->lastInsertId();
                $reference = 'TESCON_' . $paymentId . '_' . time();

                // Update payment with reference
                $stmt = $pdo->prepare("UPDATE payments SET hubtel_reference = ? WHERE id = ?");
                $stmt->execute([$reference, $paymentId]);

                // Initialize Hubtel payment
                $hubtel = new HubtelPayment();

                $paymentData = [
                    'amount' => $currentDues['amount'],
                    'phone' => $phone,
                    'description' => $currentDues['description'],
                    'customer_name' => $_SESSION['fullname'],
                    'reference' => $reference
                ];

                $result = $hubtel->initiateMobileMoneyPayment($paymentData);

                if ($result['success']) {
                    $checkoutUrl = $result['checkout_url'];

                    // Update payment with invoice token
                    $stmt = $pdo->prepare("UPDATE payments SET transaction_id = ? WHERE id = ?");
                    $stmt->execute([$result['invoice_token'], $paymentId]);

                    $success = 'Payment initiated successfully. You will be redirected to complete the payment.';
                } else {
                    // Update payment status to failed
                    $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', notes = ? WHERE id = ?");
                    $stmt->execute([$result['error'], $paymentId]);

                    $error = 'Payment initiation failed: ' . ($result['error'] ?? 'Unknown error');
                }

            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    }
}

// Get user's phone number for form pre-fill
$userPhone = '';
try {
    $stmt = $pdo->prepare("SELECT phone FROM members WHERE user_id = ?");
    $stmt->execute([$userId]);
    $member = $stmt->fetch();
    $userPhone = $member['phone'];
} catch (PDOException $e) {
    // Handle silently
}
?>

<?php
include 'includes/coreui_layout_start.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Pay Membership Dues</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                        <?php if ($currentDues): ?>
                            <!-- Dues Information -->
                            <div class="card mb-4 border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Current Year Dues</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Year:</strong> <?php echo $currentDues['year']; ?></p>
                                            <p><strong>Description:</strong> <?php echo htmlspecialchars($currentDues['description']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Amount:</strong> GH₵ <?php echo number_format($currentDues['amount'], 2); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('d/m/Y', strtotime($currentDues['due_date'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <?php if ($paymentStatus === 'paid'): ?>
                                            <span class="badge bg-success fs-6"><i class="fas fa-check-circle"></i> PAID</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning fs-6"><i class="fas fa-clock"></i> UNPAID</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($paymentStatus === 'unpaid'): ?>
                                <!-- Payment Form -->
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="initiate_payment" value="1">

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required
                                               value="<?php echo htmlspecialchars($userPhone); ?>"
                                               placeholder="e.g., 0244123456">
                                        <div class="form-text">Enter the phone number for mobile money payment</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="hubtel_mobile">Mobile Money (MTN, Vodafone, AirtelTigo)</option>
                                            <option value="hubtel_card">Credit/Debit Card</option>
                                        </select>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Note:</strong> You will be redirected to Hubtel's secure payment page to complete your transaction.
                                        Supported payment methods include MTN Mobile Money, Vodafone Cash, AirtelTigo Money, and Visa/MasterCard.
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-mobile-alt"></i> Pay GH₵ <?php echo number_format($currentDues['amount'], 2); ?>
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                No dues have been configured for the current year. Please contact the TESCON administration.
                            </div>
                        <?php endif; ?>

                        <!-- Payment History -->
                        <div class="mt-4">
                            <h5>Payment History</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Year</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("
                                                SELECT p.*, d.year, d.description
                                                FROM payments p
                                                JOIN dues d ON p.dues_id = d.id
                                                WHERE p.member_id = (SELECT id FROM members WHERE user_id = ?)
                                                ORDER BY p.created_at DESC
                                            ");
                                            $stmt->execute([$userId]);
                                            $payments = $stmt->fetchAll();

                                            if (empty($payments)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No payment history found</td>
                                                </tr>
                                            <?php else:
                                                foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?php echo $payment['year']; ?></td>
                                                        <td>GH₵ <?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = 'secondary';
                                                            switch ($payment['status']) {
                                                                case 'completed': $statusClass = 'success'; break;
                                                                case 'pending': $statusClass = 'warning'; break;
                                                                case 'failed': $statusClass = 'danger'; break;
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                                <?php echo ucfirst($payment['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $payment['payment_date'] ? date('d/m/Y', strtotime($payment['payment_date'])) : date('d/m/Y', strtotime($payment['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach;
                                            endif;
                                        } catch (PDOException $e) {
                                            echo '<tr><td colspan="5" class="text-danger">Error loading payment history</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Redirect Script -->
<?php if ($checkoutUrl): ?>
    <script>
        setTimeout(function() {
            window.location.href = '<?php echo $checkoutUrl; ?>';
        }, 2000);
    </script>
<?php endif; ?>

<?php
include 'includes/coreui_layout_end.php';
?>
