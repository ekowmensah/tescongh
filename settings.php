<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';

$pageTitle = 'Settings';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$userData = $user->getUserById($_SESSION['user_id']);

$error = '';
$success = '';

// Handle email update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $new_email = sanitize($_POST['email']);
    
    if (empty($new_email)) {
        $error = 'Email cannot be empty';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $new_email);
        $check_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = 'Email already exists. Please use a different one.';
        } else {
            $update_query = "UPDATE users SET email = :email, email_verified = 0 WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':email', $new_email);
            $update_stmt->bindParam(':user_id', $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $_SESSION['email'] = $new_email;
                $success = 'Email updated successfully! Please verify your new email.';
                $userData = $user->getUserById($_SESSION['user_id']);
            } else {
                $error = 'Failed to update email. Please try again.';
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long';
    } else {
        // Verify current password
        if (password_verify($current_password, $userData['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':user_id', $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    
    // Note: You may need to add these columns to the users table if they don't exist
    $update_query = "UPDATE users SET email_notifications = :email_notifications, sms_notifications = :sms_notifications WHERE id = :user_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':email_notifications', $email_notifications);
    $update_stmt->bindParam(':sms_notifications', $sms_notifications);
    $update_stmt->bindParam(':user_id', $_SESSION['user_id']);
    
    if ($update_stmt->execute()) {
        $success = 'Notification preferences updated successfully!';
        $userData = $user->getUserById($_SESSION['user_id']);
    } else {
        $error = 'Failed to update notification preferences. Please try again.';
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Settings</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <!-- Email Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Email Settings</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Current Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                        <?php if ($userData['email_verified']): ?>
                            <small class="text-success"><i class="cil-check-circle"></i> Verified</small>
                        <?php else: ?>
                            <small class="text-warning"><i class="cil-warning"></i> Not Verified</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter new email address">
                    </div>
                    
                    <button type="submit" name="update_email" class="btn btn-primary">
                        <i class="cil-save"></i> Update Email
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Notification Preferences -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Notification Preferences</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                               <?php echo (!empty($userData['email_notifications'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">
                            Email Notifications
                        </label>
                        <small class="d-block text-muted">Receive updates and announcements via email</small>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications"
                               <?php echo (!empty($userData['sms_notifications'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sms_notifications">
                            SMS Notifications
                        </label>
                        <small class="d-block text-muted">Receive important alerts via SMS</small>
                    </div>
                    
                    <button type="submit" name="update_notifications" class="btn btn-primary">
                        <i class="cil-save"></i> Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Password Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Change Password</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="cil-lock-locked"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Information -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Account Information</strong>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-5"><strong>Account Status:</strong></div>
                    <div class="col-sm-7">
                        <span class="badge bg-<?php echo getStatusBadgeClass($userData['status']); ?>">
                            <?php echo $userData['status']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-5"><strong>Role:</strong></div>
                    <div class="col-sm-7">
                        <span class="badge bg-primary">
                            <?php echo $userData['role']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-5"><strong>Member Since:</strong></div>
                    <div class="col-sm-7"><?php echo formatDate($userData['created_at'], 'd M Y'); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-5"><strong>Last Login:</strong></div>
                    <div class="col-sm-7">
                        <?php echo $userData['last_login'] ? formatDateTime($userData['last_login'], 'd M Y, g:i A') : 'Never'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="card border-danger mb-4">
            <div class="card-header bg-danger text-white">
                <strong>Danger Zone</strong>
            </div>
            <div class="card-body">
                <h6 class="text-danger">Delete Account</h6>
                <p class="text-muted small">Once you delete your account, there is no going back. Please be certain.</p>
                <button type="button" class="btn btn-danger" onclick="alert('Please contact an administrator to delete your account.')">
                    <i class="cil-trash"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
