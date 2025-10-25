<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Dues.php';

$pageTitle = 'Dues';

$database = new Database();
$db = $database->getConnection();

$dues = new Dues($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasAnyRole(['Admin', 'Executive'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $year = (int)$_POST['year'];
            $amount = (float)$_POST['amount'];
            $description = sanitize($_POST['description']);
            $dueDate = sanitize($_POST['due_date']);
            
            $result = $dues->create($year, $amount, $description, $dueDate);
            if ($result['success']) {
                setFlashMessage('success', 'Dues created successfully');
            } else {
                setFlashMessage('danger', 'Failed to create dues');
            }
            redirect('dues.php');
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && hasRole('Admin')) {
    $id = (int)$_GET['delete'];
    if ($dues->delete($id)) {
        setFlashMessage('success', 'Dues deleted successfully');
    } else {
        setFlashMessage('danger', 'Failed to delete dues');
    }
    redirect('dues.php');
}

$allDues = $dues->getAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Membership Dues</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#addDuesModal">
                <i class="cil-plus"></i> Add Dues
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Annual Dues</strong>
        <span class="badge bg-primary ms-2"><?php echo count($allDues); ?> Years</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allDues)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No dues found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allDues as $d): ?>
                            <?php
                            $isPast = strtotime($d['due_date']) < time();
                            $statusClass = $isPast ? 'danger' : 'success';
                            $statusText = $isPast ? 'Overdue' : 'Active';
                            ?>
                            <tr>
                                <td><strong class="fs-5"><?php echo $d['year']; ?></strong></td>
                                <td><strong class="text-primary"><?php echo formatCurrency($d['amount']); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['description']); ?></td>
                                <td><?php echo formatDate($d['due_date'], 'd M Y'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                                        <a href="dues_edit.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="cil-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (hasRole('Admin')): ?>
                                        <a href="?delete=<?php echo $d['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this dues record?')">
                                            <i class="cil-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Payment Information</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>How to Pay</h5>
                <ol>
                    <li>Select the year you want to pay for</li>
                    <li>Choose your payment method (Mobile Money, Card, or Bank Transfer)</li>
                    <li>Complete the payment process</li>
                    <li>Your payment will be verified by administrators</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h5>Payment Methods</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <span class="badge bg-primary">Mobile Money</span> - Pay via Hubtel Mobile Money
                    </li>
                    <li class="mb-2">
                        <span class="badge bg-success">Card Payment</span> - Pay with Visa/Mastercard
                    </li>
                    <li class="mb-2">
                        <span class="badge bg-info">Bank Transfer</span> - Direct bank transfer
                    </li>
                    <li class="mb-2">
                        <span class="badge bg-secondary">Cash</span> - Pay at campus office
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Dues Modal -->
<?php if (hasAnyRole(['Admin', 'Executive'])): ?>
<div class="modal fade" id="addDuesModal" tabindex="-1" aria-labelledby="addDuesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDuesModalLabel">Add Annual Dues</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="year" min="2020" max="2100" value="<?php echo date('Y'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (GH₵) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="e.g., Annual TESCON Membership Dues"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Dues</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
