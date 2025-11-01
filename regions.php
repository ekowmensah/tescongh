<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Region.php';

// Check if user has permission
if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Regions';

$database = new Database();
$db = $database->getConnection();

$region = new Region($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $name = sanitize($_POST['name']);
            $code = sanitize($_POST['code']);
            
            $result = $region->create($name, $code, $_SESSION['user_id']);
            if ($result['success']) {
                setFlashMessage('success', 'Region created successfully');
            } else {
                setFlashMessage('danger', 'Failed to create region');
            }
            redirect('regions.php');
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && hasRole('Admin')) {
    $id = (int)$_GET['delete'];
    if ($region->delete($id)) {
        setFlashMessage('success', 'Region deleted successfully');
    } else {
        setFlashMessage('danger', 'Failed to delete region');
    }
    redirect('regions.php');
}

$regions = $region->getAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Regions</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#addRegionModal">
            <i class="cil-plus"></i> Add Region
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Regions List</strong>
        <span class="badge bg-primary ms-2"><?php echo count($regions); ?> Total</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions as $r): ?>
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($r['code']); ?></span></td>
                            <td><?php echo formatDate($r['created_at'], 'd M Y'); ?></td>
                            <td class="table-actions">
                                <?php if (hasRole('Admin')): ?>
                                    <a href="regions.php?delete=<?php echo $r['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirmDelete('Are you sure you want to delete this region?')">
                                        <i class="cil-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Region Modal -->
<div class="modal fade" id="addRegionModal" tabindex="-1" aria-labelledby="addRegionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRegionModalLabel">Add New Region</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Region Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Region Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" placeholder="e.g., GAR" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Region</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
