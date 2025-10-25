<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Payment.php';

$pageTitle = 'Payments';

$database = new Database();
$db = $database->getConnection();

$payment = new Payment($db);

// Handle filters
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = sanitize($_GET['status']);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $recordsPerPage;

// Get payments
$payments = $payment->getAll($recordsPerPage, $offset, $filters);
$totalPayments = $payment->count($filters);
$pagination = paginate($totalPayments, $page, $recordsPerPage);

// Get statistics
$stats = $payment->getStatistics();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payments</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <a href="payment_add.php" class="btn btn-primary">
                <i class="cil-plus"></i> Record Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Total Collected</div>
                <div class="fs-4 fw-semibold text-success"><?php echo formatCurrency($stats['total_amount']); ?></div>
                <small class="text-muted"><?php echo number_format($stats['total_payments']); ?> payments</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Pending</div>
                <div class="fs-4 fw-semibold text-warning"><?php echo number_format($stats['pending_payments']); ?></div>
                <small class="text-muted">awaiting confirmation</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Failed</div>
                <div class="fs-4 fw-semibold text-danger"><?php echo number_format($stats['failed_payments']); ?></div>
                <small class="text-muted">payment failures</small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <strong>Filter Payments</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo (isset($filters['status']) && $filters['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo (isset($filters['status']) && $filters['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo (isset($filters['status']) && $filters['status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                    <option value="cancelled" <?php echo (isset($filters['status']) && $filters['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Payment Records</strong>
        <span class="badge bg-primary ms-2"><?php echo number_format($totalPayments); ?> Total</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Year</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No payments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['fullname']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['phone']); ?></small>
                                </td>
                                <td><span class="badge bg-info"><?php echo $p['year']; ?></span></td>
                                <td><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $methodBadge = [
                                        'hubtel_mobile' => 'primary',
                                        'hubtel_card' => 'success',
                                        'bank_transfer' => 'info',
                                        'cash' => 'secondary'
                                    ];
                                    $badge = $methodBadge[$p['payment_method']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>">
                                        <?php echo str_replace('_', ' ', ucwords($p['payment_method'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($p['transaction_id'])): ?>
                                        <code><?php echo htmlspecialchars($p['transaction_id']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($p['status']); ?>">
                                        <?php echo ucfirst($p['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($p['created_at'], 'd M Y'); ?></td>
                                <td class="table-actions">
                                    <a href="payment_view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="cil-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="mt-3">
                <?php echo generatePaginationHTML($pagination, 'payments.php'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
