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

$pageTitle = 'Edit Position';

$database = new Database();
$db = $database->getConnection();

$positionObj = new Position($db);

// Get position ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Position ID not provided');
    redirect('positions.php');
}

$positionId = (int)$_GET['id'];
$positionData = $positionObj->getById($positionId);

if (!$positionData) {
    setFlashMessage('danger', 'Position not found');
    redirect('positions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $level = (int)$_POST['level'];
    $description = sanitize($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if position name already exists in this category (excluding current)
    if ($positionObj->nameExists($name, $category, $positionId)) {
        setFlashMessage('danger', 'A position with this name already exists in the ' . $category . ' category');
    } else {
        $data = [
            'name' => $name,
            'category' => $category,
            'level' => $level,
            'description' => $description,
            'is_active' => $is_active
        ];
        
        if ($positionObj->update($positionId, $data)) {
            setFlashMessage('success', 'Position updated successfully');
            redirect('positions.php');
        } else {
            setFlashMessage('danger', 'Failed to update position');
        }
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Position</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="positions.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Positions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Position Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Position Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($positionData['name']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Executive" <?php echo ($positionData['category'] == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                                <option value="Patron" <?php echo ($positionData['category'] == 'Patron') ? 'selected' : ''; ?>>Patron</option>
                                <option value="Member" <?php echo ($positionData['category'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hierarchy Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="level" min="1" value="<?php echo $positionData['level']; ?>" required>
                            <small class="text-muted">1 = Highest, 2 = Second, etc.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($positionData['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo $positionData['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                        <small class="text-muted">Inactive positions won't appear in dropdowns</small>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Update Position
                    </button>
                    <a href="positions.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
