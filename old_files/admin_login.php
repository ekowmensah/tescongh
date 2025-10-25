<?php
/**
 * TESCON Ghana - Admin Login
 */
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn() && hasRole(['Admin', 'Executive', 'Patron'])) {
    header('Location: members.php');
    exit();
}

$error = '';
$pageTitle = "Admin Login";
$breadcrumbs = [
    ['title' => 'Account', 'url' => '#'],
    ['title' => 'Admin Login', 'url' => '#']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!checkRateLimit('admin_login', 5, 300)) {
        $error = 'Too many login attempts. Please try again in 5 minutes.';
        logSecurityEvent('rate_limit_exceeded', ['action' => 'admin_login', 'email' => $_POST['email'] ?? '']);
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('csrf_failure', ['page' => 'admin_login']);
    } else {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = 'Both email and password are required';
        } else {
            try {
                // Check admin credentials
                $stmt = $pdo->prepare("
                    SELECT u.*, 'Admin' as user_type
                    FROM users u
                    WHERE u.email = ? AND u.role IN ('Admin', 'Executive', 'Patron') AND u.status = 'Active'
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['fullname'] = $user['email'];
                    $_SESSION['photo'] = null;

                    // Redirect based on role
                    header('Location: members.php');
                    exit();
                } else {
                    $error = 'Invalid email or password';
                    logSecurityEvent('admin_login_failed', ['email' => $email]);
                }
            } catch (PDOException $e) {
                error_log('Admin login error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>

<?php
include 'includes/coreui_layout_start.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Admin Login</h4>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning border-0 mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small><strong>Admin Access Only</strong> - Authorized personnel only</small>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label required">Admin Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required autofocus
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       placeholder="admin@tesconghana.org">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label required">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-shield-alt me-2"></i>Admin Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0"><small class="text-muted">Member? <a href="login.php">Login here</a></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/coreui_layout_end.php';
?>