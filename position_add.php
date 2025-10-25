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

$pageTitle = 'Add Position';

$database = new Database();
$db = $database->getConnection();

$position = new Position($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $level = (int)$_POST['level'];
    $description = sanitize($_POST['description']);
    
    // Check if position name already exists in this category
    if ($position->nameExists($name, $category)) {
        setFlashMessage('danger', 'A position with this name already exists in the ' . $category . ' category');
    } else {
        $data = [
            'name' => $name,
            'category' => $category,
            'level' => $level,
            'description' => $description,
            'created_by' => $_SESSION['user_id']
        ];
        
        $result = $position->create($data);
        
        if ($result['success']) {
            setFlashMessage('success', 'Position created successfully');
            redirect('positions.php');
        } else {
            setFlashMessage('danger', 'Failed to create position');
        }
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Position</h2>
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
                        <input type="text" class="form-control" name="name" required>
                        <small class="text-muted">e.g., President, Vice President, Patron</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Executive">Executive</option>
                                <option value="Patron">Patron</option>
                                <option value="Member">Member</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hierarchy Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="level" min="1" value="1" required>
                            <small class="text-muted">1 = Highest, 2 = Second, etc.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the position"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="cil-info"></i> <strong>Note:</strong> The hierarchy level determines the order in which positions are displayed. Lower numbers appear first.
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create Position
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
