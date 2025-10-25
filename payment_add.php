<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';
require_once 'classes/Member.php';
require_once 'classes/Dues.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Record Payment';

$database = new Database();
$db = $database->getConnection();

$payment = new Payment($db);
$member = new Member($db);
$dues = new Dues($db);

$members = $member->getAll(1000, 0); // Get all members
$allDues = $dues->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'member_id' => (int)$_POST['member_id'],
        'dues_id' => (int)$_POST['dues_id'],
        'amount' => (float)$_POST['amount'],
        'payment_method' => sanitize($_POST['payment_method']),
        'transaction_id' => sanitize($_POST['transaction_id']),
        'hubtel_reference' => sanitize($_POST['hubtel_reference']),
        'status' => sanitize($_POST['status']),
        'payment_date' => !empty($_POST['payment_date']) ? sanitize($_POST['payment_date']) : date('Y-m-d H:i:s'),
        'notes' => sanitize($_POST['notes'])
    ];
    
    $result = $payment->create($data);
    
    if ($result['success']) {
        setFlashMessage('success', 'Payment recorded successfully');
        redirect('payments.php');
    } else {
        setFlashMessage('danger', 'Failed to record payment');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Record Payment</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="payments.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Payment Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Member <span class="text-danger">*</span></label>
                        <select class="form-select" name="member_id" id="member_id" required>
                            <option value="">Select Member</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['fullname']) . ' - ' . htmlspecialchars($m['student_id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dues Year <span class="text-danger">*</span></label>
                            <select class="form-select" name="dues_id" id="dues_id" required>
                                <option value="">Select Year</option>
                                <?php foreach ($allDues as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" data-amount="<?php echo $d['amount']; ?>">
                                        <?php echo $d['year'] . ' - ' . formatCurrency($d['amount']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" id="amount" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" id="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="hubtel_mobile">Hubtel Mobile Money</option>
                                <option value="hubtel_card">Hubtel Card Payment</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" name="transaction_id" placeholder="Optional">
                        </div>
                        
                        <div class="col-md-6 mb-3" id="hubtel_ref_field" style="display: none;">
                            <label class="form-label">Hubtel Reference</label>
                            <input type="text" class="form-control" name="hubtel_reference" placeholder="Hubtel reference number">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="datetime-local" class="form-control" name="payment_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes or comments"></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Record Payment
                    </button>
                    <a href="payments.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-fill amount when dues year is selected
document.getElementById('dues_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    if (amount) {
        document.getElementById('amount').value = amount;
    }
});

// Show/hide Hubtel reference field based on payment method
document.getElementById('payment_method').addEventListener('change', function() {
    const hubtelRefField = document.getElementById('hubtel_ref_field');
    if (this.value === 'hubtel_mobile' || this.value === 'hubtel_card') {
        hubtelRefField.style.display = 'block';
    } else {
        hubtelRefField.style.display = 'none';
    }
});

// Add search functionality to member select
$(document).ready(function() {
    $('#member_id').select2({
        placeholder: 'Search member by name or student ID',
        allowClear: true
    });
});
</script>

<?php include 'includes/footer.php'; ?>
