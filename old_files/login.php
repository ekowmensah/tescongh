<?php
/**
 * TESCON Ghana - Member Login
 */
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$pageTitle = "Member Login";
$breadcrumbs = [
    ['title' => 'Account', 'url' => '#'],
    ['title' => 'Login', 'url' => '#']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!checkRateLimit('login', 5, 300)) {
        $error = 'Too many login attempts. Please try again in 5 minutes.';
        logSecurityEvent('rate_limit_exceeded', ['action' => 'login', 'student_id' => $_POST['student_id'] ?? '']);
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('csrf_failure', ['page' => 'login']);
    } else {
        $student_id = sanitizeInput($_POST['student_id']);
        $password = $_POST['password'];

        if (empty($student_id) || empty($password)) {
            $error = 'Both student ID and password are required';
        } else {
            try {
                // Check user credentials
                $stmt = $pdo->prepare("
                    SELECT u.*, m.fullname, m.photo
                    FROM users u
                    LEFT JOIN members m ON u.id = m.user_id
                    WHERE m.student_id = ? AND u.status = 'Active'
                ");
                $stmt->execute([$student_id]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['fullname'] = $user['fullname'] ?: $student_id;
                    $_SESSION['photo'] = $user['photo'];

                    // Redirect based on role
                    if (in_array($user['role'], ['Executive', 'Patron', 'Admin'])) {
                        header('Location: members.php');
                    } else {
                        header('Location: members.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid student ID or password';
                    logSecurityEvent('login_failed', ['student_id' => $student_id]);
                }
            } catch (PDOException $e) {
                error_log('Login error: ' . $e->getMessage());
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
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Member Login</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="student_id" class="form-label required">Student ID</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="student_id" name="student_id" required autofocus>
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
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-2">Don't have an account? <a href="register.php" class="fw-bold">Register here</a></p>
                        <p class="mb-0"><small class="text-muted">Admin? <a href="admin_login.php">Login here</a></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/coreui_layout_end.php';
?>
