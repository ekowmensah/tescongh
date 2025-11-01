<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Position.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Positions Management';

$database = new Database();
$db = $database->getConnection();

$position = new Position($db);

// Handle delete
if (isset($_GET['delete']) && hasRole('Admin')) {
    $id = (int)$_GET['delete'];
    if ($position->delete($id)) {
        setFlashMessage('success', 'Position deleted successfully');
    } else {
        setFlashMessage('danger', 'Failed to delete position. It may be in use.');
    }
    redirect('positions.php');
}

// Handle toggle active
if (isset($_GET['toggle']) && hasRole('Admin')) {
    $id = (int)$_GET['toggle'];
    if ($position->toggleActive($id)) {
        setFlashMessage('success', 'Position status updated');
    } else {
        setFlashMessage('danger', 'Failed to update position status');
    }
    redirect('positions.php');
}

// Get filter
$filterCategory = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Get positions
if ($filterCategory) {
    $positions = $position->getByCategory($filterCategory);
} else {
    $positions = $position->getAll(null, false); // Show all including inactive
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Positions Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="position_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Position
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-3">
    <div class="card-header">
        <strong>Filter Positions</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <option value="Executive" <?php echo ($filterCategory == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                    <option value="Patron" <?php echo ($filterCategory == 'Patron') ? 'selected' : ''; ?>>Patron</option>
                    <option value="Member" <?php echo ($filterCategory == 'Member') ? 'selected' : ''; ?>>Member</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Positions List -->
<div class="card">
    <div class="card-header">
        <strong>Positions List</strong>
        <span class="badge bg-primary ms-2"><?php echo count($positions); ?> Total</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Position Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($positions)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No positions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($positions as $pos): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $pos['level']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($pos['name']); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $pos['category'] == 'Executive' ? 'primary' : 
                                            ($pos['category'] == 'Patron' ? 'info' : 'secondary'); 
                                    ?>">
                                        <?php echo $pos['category']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($pos['description'] ?? ''); ?></td>
                                <td>
                                    <?php if ($pos['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="position_edit.php?id=<?php echo $pos['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <a href="positions.php?toggle=<?php echo $pos['id']; ?>" 
                                       class="btn btn-sm btn-<?php echo $pos['is_active'] ? 'secondary' : 'success'; ?>" 
                                       title="<?php echo $pos['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="cil-<?php echo $pos['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </a>
                                    <a href="positions.php?delete=<?php echo $pos['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirmDelete('Are you sure you want to delete this position?')">
                                        <i class="cil-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Position Statistics by Category</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $categories = ['Executive', 'Patron', 'Member'];
                    foreach ($categories as $cat):
                        $catPositions = $position->getByCategory($cat);
                        $count = count($catPositions);
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title text-muted"><?php echo $cat; ?> Positions</h6>
                                    <h3 class="mb-0"><?php echo $count; ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
