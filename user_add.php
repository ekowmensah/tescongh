<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Add User';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    
    $result = $user->register($email, $password, $role);
    
    if ($result['success']) {
        // Update status if different from default
        if ($status !== 'Active') {
            $updateQuery = "UPDATE users SET status = :status WHERE id = :id";
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':status', $status);
            $stmt->bindValue(':id', $result['user_id']);
            $stmt->execute();
        }
        
        setFlashMessage('success', 'User created successfully');
        redirect('users.php');
    } else {
        setFlashMessage('danger', $result['message']);
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New User</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="users.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Users
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>User Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                        <small class="text-muted">This will be used for login</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Member">Member</option>
                                <option value="Executive">Executive</option>
                                <option value="Patron">Patron</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="cil-info"></i> <strong>Note:</strong> After creating the user account, you can create their member profile from the Members section.
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create User
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
