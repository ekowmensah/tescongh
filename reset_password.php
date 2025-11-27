<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$token = '';
$validToken = false;
$userEmail = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $user = new User($db);
        $verification = $user->verifyPasswordResetToken($token);
        
        if ($verification['success']) {
            $validToken = true;
            $userEmail = $verification['email'];
        } else {
            $error = $verification['message'];
        }
    } else {
        $error = 'Database connection failed';
    }
} else {
    $error = 'No reset token provided';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $token = sanitize($_POST['token']);
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $user = new User($db);
            $result = $user->resetPasswordWithToken($token, $newPassword);
            
            if ($result['success']) {
                $success = $result['message'];
                $validToken = false; // Prevent further submissions
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Database connection failed';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png">
    
    <!-- CoreUI CSS -->
    <link href="assets/vendors/@coreui/coreui/css/coreui.min.css" rel="stylesheet">
    <link href="assets/vendors/@coreui/icons/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-password-container {
            width: 100%;
            max-width: 450px;
            padding: 15px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
            text-align: center;
        }
        .card-body {
            padding: 30px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-width: 80px;
            height: auto;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="<?php echo APP_NAME; ?>">
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="cil-lock-locked"></i> Reset Password
                </h4>
                <?php if ($validToken): ?>
                    <p class="mb-0 mt-2 small">Enter your new password</p>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="cil-warning"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                    </div>
                    
                    <?php if (!$validToken): ?>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-primary">
                                <i class="cil-reload"></i> Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="cil-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="cil-account-logout"></i> Go to Login
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($validToken && empty($success)): ?>
                    <div class="alert alert-info mb-3">
                        <small>
                            <i class="cil-info"></i> 
                            Resetting password for: <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                        </small>
                    </div>
                    
                    <form method="POST" action="" id="resetPasswordForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="cil-lock-locked"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       name="password" 
                                       id="password"
                                       placeholder="Enter new password"
                                       required 
                                       minlength="6"
                                       autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="cil-lock-locked"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       name="confirm_password" 
                                       id="confirm_password"
                                       placeholder="Confirm new password"
                                       required 
                                       minlength="6">
                            </div>
                        </div>
                        
                        <div class="password-requirements">
                            <strong>Password Requirements:</strong>
                            <ul>
                                <li>Minimum 6 characters</li>
                                <li>Both passwords must match</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="cil-check"></i> Reset Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="back-to-login">
                    <a href="login.php" class="text-decoration-none">
                        <i class="cil-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3 text-white">
            <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</small>
        </div>
    </div>
    
    <!-- CoreUI JS -->
    <script src="assets/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    
    <script>
        // Client-side password validation
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
