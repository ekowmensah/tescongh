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

$pageTitle = 'Edit User';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

// Get user ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'User ID not provided');
    redirect('users.php');
}

$userId = (int)$_GET['id'];
$userData = $user->getUserById($userId);

if (!$userData) {
    setFlashMessage('danger', 'User not found');
    redirect('users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    $email_verified = isset($_POST['email_verified']) ? 1 : 0;
    $phone_verified = isset($_POST['phone_verified']) ? 1 : 0;
    
    // Update user
    $updateQuery = "UPDATE users SET email = :email, role = :role, status = :status, 
                    email_verified = :email_verified, phone_verified = :phone_verified 
                    WHERE id = :id";
    $stmt = $db->prepare($updateQuery);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':email_verified', $email_verified);
    $stmt->bindParam(':phone_verified', $phone_verified);
    $stmt->bindParam(':id', $userId);
    
    if ($stmt->execute()) {
        // Update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pwdQuery = "UPDATE users SET password = :password WHERE id = :id";
            $pwdStmt = $db->prepare($pwdQuery);
            $pwdStmt->bindParam(':password', $password);
            $pwdStmt->bindParam(':id', $userId);
            $pwdStmt->execute();
        }
        
        setFlashMessage('success', 'User updated successfully');
        redirect('users.php');
    } else {
        setFlashMessage('danger', 'Failed to update user');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit User</h2>
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
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" minlength="6">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Member" <?php echo ($userData['role'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                                <option value="Executive" <?php echo ($userData['role'] == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                                <option value="Patron" <?php echo ($userData['role'] == 'Patron') ? 'selected' : ''; ?>>Patron</option>
                                <option value="Admin" <?php echo ($userData['role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Active" <?php echo ($userData['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($userData['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Suspended" <?php echo ($userData['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="email_verified" id="email_verified" <?php echo $userData['email_verified'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_verified">
                                    Email Verified
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="phone_verified" id="phone_verified" <?php echo $userData['phone_verified'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="phone_verified">
                                    Phone Verified
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Last Login:</strong> <?php echo $userData['last_login'] ? formatDateTime($userData['last_login'], 'd M Y, g:i A') : 'Never'; ?><br>
                        <strong>Account Created:</strong> <?php echo formatDate($userData['created_at'], 'd M Y'); ?>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Update User
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
