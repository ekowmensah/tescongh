<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Dues.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Edit Dues';

$database = new Database();
$db = $database->getConnection();

$dues = new Dues($db);

// Get dues ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Dues ID not provided');
    redirect('dues.php');
}

$duesId = (int)$_GET['id'];
$duesData = $dues->getById($duesId);

if (!$duesData) {
    setFlashMessage('danger', 'Dues not found');
    redirect('dues.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = (int)$_POST['year'];
    $amount = (float)$_POST['amount'];
    $description = sanitize($_POST['description']);
    $dueDate = sanitize($_POST['due_date']);
    
    $result = $dues->update($duesId, $year, $amount, $description, $dueDate);
    if (is_array($result) && isset($result['success'])) {
        if ($result['success']) {
            setFlashMessage('success', 'Dues updated successfully');
            redirect('dues.php');
        } else {
            $message = isset($result['message']) ? $result['message'] : 'Failed to update dues';
            setFlashMessage('danger', $message);
        }
    } else if ($result) {
        // Backward compatibility if update returns boolean true
        setFlashMessage('success', 'Dues updated successfully');
        redirect('dues.php');
    } else {
        setFlashMessage('danger', 'Failed to update dues');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Dues</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="dues.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Dues
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Dues Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="year" min="2020" max="2100" value="<?php echo $duesData['year']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (GH₵) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0" value="<?php echo $duesData['amount']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($duesData['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="due_date" value="<?php echo $duesData['due_date']; ?>" required>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Update Dues
                    </button>
                    <a href="dues.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
