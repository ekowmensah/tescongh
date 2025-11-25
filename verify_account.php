<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$tokenData = null;

// Validate token if provided
if ($token) {
    $query = "SELECT vt.*, m.fullname, m.phone, m.user_id 
              FROM verification_tokens vt
              JOIN members m ON vt.member_id = m.id
              WHERE vt.token = :token 
              AND vt.is_used = 0 
              AND vt.expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'Invalid or expired verification link. Please contact support.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenData) {
    $student_id = sanitize($_POST['student_id']);
    $action = sanitize($_POST['action']);
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Verify student ID matches
    if ($student_id !== $tokenData['student_id']) {
        $error = 'Student ID does not match our records.';
    } else {
        try {
            $db->beginTransaction();
            
            if ($action === 'reset_password') {
                // Validate password
                if (empty($new_password) || empty($confirm_password)) {
                    $error = 'Please enter and confirm your new password.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':password', $hashed_password);
                    $update_stmt->bindParam(':user_id', $tokenData['user_id']);
                    $update_stmt->execute();
                    
                    // Mark token as used
                    $token_query = "UPDATE verification_tokens SET is_used = 1 WHERE id = :token_id";
                    $token_stmt = $db->prepare($token_query);
                    $token_stmt->bindParam(':token_id', $tokenData['id']);
                    $token_stmt->execute();
                    
                    $db->commit();
                    $success = 'Password reset successfully! You can now login with your new password.';
                    $tokenData = null; // Clear token data to show success message
                }
            } elseif ($action === 'keep_password') {
                // Just mark token as used (verification complete)
                $token_query = "UPDATE verification_tokens SET is_used = 1 WHERE id = :token_id";
                $token_stmt = $db->prepare($token_query);
                $token_stmt->bindParam(':token_id', $tokenData['id']);
                $token_stmt->execute();
                
                $db->commit();
                $success = 'Account verified successfully! You can now login with your existing password.';
                $tokenData = null; // Clear token data to show success message
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Verification failed. Please try again.';
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
    <title>Verify Account - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    
    <link rel="stylesheet" href="https://unpkg.com/@coreui/coreui@4.2.0/dist/css/coreui.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --primary-red: #dc2626;
            --secondary-red: #ef4444;
            --white: #ffffff;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1e3a8a 50%, var(--primary-red) 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            position: relative;
            padding: 2rem 0;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
            z-index: 0;
        }
        
        .verify-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.98);
            position: relative;
            z-index: 1;
        }
        
        .brand-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--secondary-red) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
            color: white;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
            border: 4px solid white;
        }
        
        .verify-card h1 {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 1.8rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            border: none;
            font-weight: 700;
            padding: 0.75rem;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, var(--primary-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
            border: none;
            font-weight: 700;
            padding: 0.75rem;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(107, 114, 128, 0.4);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        .info-box {
            background: var(--light-blue);
            border-left: 4px solid var(--primary-blue);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .success-icon {
            width: 60px;
            height: 60px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card verify-card">
                    <div class="card-body p-4 p-md-5">
                        <?php if (!$tokenData && !$success): ?>
                            <div class="brand-logo">TG</div>
                            <h1 class="text-center mb-4">Account Verification</h1>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="cil-x-circle"></i> <?php echo $error; ?>
                                </div>
                                <div class="text-center mt-4">
                                    <a href="signup.php" class="btn btn-primary">Register New Account</a>
                                    <a href="login.php" class="btn btn-secondary ms-2">Login</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="cil-warning"></i> No verification token provided. Please check your SMS for the verification link.
                                </div>
                                <div class="text-center mt-4">
                                    <a href="home.php" class="btn btn-primary">Back to Home</a>
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($success): ?>
                            <div class="success-icon">
                                <i class="cil-check"></i>
                            </div>
                            <h1 class="text-center mb-4 text-success">Success!</h1>
                            
                            <div class="alert alert-success">
                                <i class="cil-check-circle"></i> <?php echo $success; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="login.php" class="btn btn-primary btn-lg">Proceed to Login</a>
                            </div>
                            
                        <?php else: ?>
                            <div class="brand-logo">TG</div>
                            <h1 class="text-center mb-2">Verify Your Account</h1>
                            <p class="text-medium-emphasis text-center mb-4">Welcome, <?php echo htmlspecialchars($tokenData['fullname']); ?>!</p>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="info-box">
                                <p class="mb-2"><strong>Verification Instructions:</strong></p>
                                <ol class="mb-0">
                                    <li>Enter your Student ID to confirm your identity</li>
                                    <li>Choose to keep your current password or reset it</li>
                                    <li>Complete verification to activate your account</li>
                                </ol>
                            </div>
                            
                            <form method="POST" action="" id="verifyForm">
                                <div class="mb-4">
                                    <label class="form-label">Student ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="student_id" placeholder="Enter your student ID" required>
                                    <small class="text-muted">This must match the Student ID you registered with</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Password Action <span class="text-danger">*</span></label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="action" id="keepPassword" value="keep_password" checked>
                                        <label class="form-check-label" for="keepPassword">
                                            <strong>Keep my current password</strong>
                                            <br><small class="text-muted">Use the password you set during registration</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" id="resetPassword" value="reset_password">
                                        <label class="form-check-label" for="resetPassword">
                                            <strong>Reset my password</strong>
                                            <br><small class="text-muted">Create a new password for your account</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="passwordFields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Minimum 6 characters">
                                        <small class="text-muted">At least 6 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Re-enter new password">
                                    </div>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">Verify Account</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">
                                <a href="home.php" class="text-decoration-none"><i class="cil-arrow-left"></i> Back to Home</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-white">
                        <small>&copy; <?php echo date('Y'); ?> UEW-TESCON. All rights reserved.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/@coreui/coreui@4.2.0/dist/js/coreui.bundle.min.js"></script>
    <script>
    // Show/hide password fields based on selection
    document.querySelectorAll('input[name="action"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const passwordFields = document.getElementById('passwordFields');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (this.value === 'reset_password') {
                passwordFields.style.display = 'block';
                newPassword.required = true;
                confirmPassword.required = true;
            } else {
                passwordFields.style.display = 'none';
                newPassword.required = false;
                confirmPassword.required = false;
                newPassword.value = '';
                confirmPassword.value = '';
            }
        });
    });
    </script>
</body>
</html>
