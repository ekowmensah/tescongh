<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';

$pageTitle = 'Payment Details';

$database = new Database();
$db = $database->getConnection();

$payment = new Payment($db);

// Get payment ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Payment ID not provided');
    redirect('payments.php');
}

$paymentId = (int)$_GET['id'];
$paymentData = $payment->getById($paymentId);

if (!$paymentData) {
    setFlashMessage('danger', 'Payment not found');
    redirect('payments.php');
}

// Check if regular member is trying to view someone else's payment
$isRegularMember = hasRole('Member') && !hasAnyRole(['Admin', 'Executive', 'Patron']);
if ($isRegularMember) {
    $currentUserId = $_SESSION['user_id'];
    $memberQuery = "SELECT id FROM members WHERE user_id = :user_id";
    $stmt = $db->prepare($memberQuery);
    $stmt->bindParam(':user_id', $currentUserId);
    $stmt->execute();
    $currentMember = $stmt->fetch();
    
    if (!$currentMember || $currentMember['id'] != $paymentData['member_id']) {
        setFlashMessage('danger', 'You can only view your own payments');
        redirect('payments.php');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payment Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="payments.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Payment Information -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="cil-dollar"></i> Payment Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Payment ID</label>
                        <div><strong>#<?php echo $paymentData['id']; ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Transaction ID</label>
                        <div>
                            <?php if (!empty($paymentData['transaction_id'])): ?>
                                <code><?php echo htmlspecialchars($paymentData['transaction_id']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">Not available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Amount</label>
                        <div class="fs-4 text-success"><strong><?php echo formatCurrency($paymentData['amount']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Status</label>
                        <div>
                            <span class="badge bg-<?php echo getStatusBadgeClass($paymentData['status']); ?> fs-6">
                                <?php echo ucfirst($paymentData['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Payment Method</label>
                        <div>
                            <?php
                            $methodBadge = [
                                'hubtel_mobile' => 'primary',
                                'hubtel_card' => 'success',
                                'bank_transfer' => 'info',
                                'cash' => 'secondary'
                            ];
                            $badge = $methodBadge[$paymentData['payment_method']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>">
                                <?php echo str_replace('_', ' ', ucwords($paymentData['payment_method'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Payment Date</label>
                        <div><strong><?php echo formatDate($paymentData['created_at'], 'd M Y h:i A'); ?></strong></div>
                    </div>
                    <?php if (!empty($paymentData['hubtel_reference'])): ?>
                    <div class="col-md-12 mb-3">
                        <label class="text-muted small">Hubtel Reference</label>
                        <div><code><?php echo htmlspecialchars($paymentData['hubtel_reference']); ?></code></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($paymentData['notes'])): ?>
                    <div class="col-md-12">
                        <label class="text-muted small">Notes</label>
                        <div class="alert alert-info mb-0">
                            <?php echo nl2br(htmlspecialchars($paymentData['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Member Information -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <strong><i class="cil-user"></i> Member Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Full Name</label>
                        <div><strong><?php echo htmlspecialchars($paymentData['fullname']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Phone Number</label>
                        <div><strong><?php echo htmlspecialchars($paymentData['phone']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Email</label>
                        <div><strong><?php echo htmlspecialchars($paymentData['email']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Member ID</label>
                        <div><strong>#<?php echo $paymentData['member_id']; ?></strong></div>
                    </div>
                </div>
                <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                <div class="mt-2">
                    <a href="member_view.php?id=<?php echo $paymentData['member_id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="cil-user"></i> View Member Profile
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dues Information -->
        <?php if (!empty($paymentData['year'])): ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <strong><i class="cil-calendar"></i> Dues Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Academic Year</label>
                        <div><strong><?php echo htmlspecialchars($paymentData['year']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Dues Amount</label>
                        <div><strong><?php echo formatCurrency($paymentData['dues_amount']); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong>Actions</strong>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($paymentData['status'] == 'Pending' || $paymentData['status'] == 'pending'): ?>
                    <button class="btn btn-success" onclick="updatePaymentStatus(<?php echo $paymentId; ?>, 'completed')">
                        <i class="cil-check"></i> Mark as Paid
                    </button>
                    <button class="btn btn-danger" onclick="updatePaymentStatus(<?php echo $paymentId; ?>, 'failed')">
                        <i class="cil-x"></i> Mark as Failed
                    </button>
                    <?php endif; ?>
                    <?php if (hasRole('Admin')): ?>
                    <a href="payment_delete.php?id=<?php echo $paymentId; ?>" 
                       class="btn btn-outline-danger"
                       onclick="return confirm('Are you sure you want to delete this payment record?')">
                        <i class="cil-trash"></i> Delete Payment
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Timeline -->
        <div class="card">
            <div class="card-header">
                <strong>Timeline</strong>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Payment Created</small>
                            <div><strong><?php echo formatDate($paymentData['created_at'], 'd M Y h:i A'); ?></strong></div>
                        </div>
                    </div>
                    <?php if (!empty($paymentData['payment_date'])): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Payment Completed</small>
                            <div><strong><?php echo formatDate($paymentData['payment_date'], 'd M Y h:i A'); ?></strong></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 12px;
    bottom: -8px;
    width: 2px;
    background: #e0e0e0;
}
</style>

<script>
function updatePaymentStatus(paymentId, status) {
    if (confirm('Are you sure you want to update this payment status?')) {
        // You can implement AJAX call here or redirect to update page
        window.location.href = 'payment_update_status.php?id=' + paymentId + '&status=' + status;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
