<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';
require_once 'classes/HubtelSMS.php';
require_once 'classes/Email.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier']);
    
    if (empty($identifier)) {
        $error = 'Please enter your email address or student ID';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $user = new User($db);
            $result = $user->createPasswordResetToken($identifier);
            
            if ($result['success']) {
                // Generate reset link for email
                $resetLink = APP_URL . '/reset_password.php?token=' . $result['token'];
                
                // Get OTP code for SMS
                $otpCode = $result['otp_code'];
                
                $emailSent = false;
                $smsSent = false;
                $deliveryMethods = [];
                
                // Send reset LINK via EMAIL
                try {
                    $emailService = new Email();
                    $userName = isset($result['fullname']) ? $result['fullname'] : null;
                    $emailResult = $emailService->sendPasswordReset($result['email'], $resetLink, $userName);
                    $emailSent = $emailResult['success'];
                    
                    if ($emailSent) {
                        $deliveryMethods[] = 'email (reset link)';
                    }
                } catch (Exception $e) {
                    error_log('Email Error: ' . $e->getMessage());
                }
                
                // Send OTP CODE via SMS if user has phone number
                if (!empty($result['phone'])) {
                    try {
                        $sms = new HubtelSMS(
                            HUBTEL_SMS_CLIENT_ID,
                            HUBTEL_SMS_CLIENT_SECRET,
                            SMS_SENDER_ID
                        );
                        
                        // Send OTP code via SMS (much shorter message)
                        $message = "Password Reset\n"
                                 . "Your Reset code: " . $otpCode . "\n"
                                 . "Valid for 15 min. Don't share.";
                        
                        $smsResult = $sms->sendSimplePOST($result['phone'], $message);
                        $smsSent = $smsResult['success'];
                        
                        if ($smsSent) {
                            $deliveryMethods[] = 'SMS (OTP code)';
                        }
                    } catch (Exception $e) {
                        error_log('SMS Error: ' . $e->getMessage());
                    }
                }
                
                // Show success message based on delivery methods
                if ($emailSent || $smsSent) {
                    if (count($deliveryMethods) > 0) {
                        $methodsText = implode(' and ', $deliveryMethods);
                        $success = "Password reset instructions sent via {$methodsText}.<br><br>";
                        
                        if ($smsSent) {
                            $success .= '<div class="alert alert-info mt-2">'
                                     . '<i class="cil-mobile me-2"></i>'
                                     . '<strong>SMS sent!</strong> Check your phone for a 6-digit OTP code. '
                                     . '<a href="verify_otp.php" class="alert-link">Click Here to Enter your Email/Student ID and OTP Code</a><span>OTP Code expires in 15 minutes)</span>'
                                     . '</div>';
                        }
                        
                        if ($emailSent) {
                            $success .= '<div class="alert alert-info mt-2">'
                                     . '<i class="cil-envelope-closed me-2"></i>'
                                     . '<strong>Email sent!</strong> Check your inbox for the reset link.'
                                     . '</div>';
                        }
                    }
                } else {
                    // Fallback: show link on screen
                    $success = 'Password reset link has been generated. Please copy the link below:<br><br>'
                             . '<div class="alert alert-info mt-3">'
                             . '<strong>Reset Link:</strong><br>'
                             . '<a href="' . htmlspecialchars($resetLink) . '" class="text-break">' 
                             . htmlspecialchars($resetLink) . '</a>'
                             . '</div>';
                }
                
                // Clear identifier field on success
                $identifier = '';
            } else {
                // For security, show generic message even if account doesn't exist
                $success = 'If an account exists with this email or student ID, password reset instructions will be sent.';
            }
        } else {
            $error = 'Database connection failed. Please try again later.';
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
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png">
    
    <!-- CoreUI CSS -->
    <link rel="stylesheet" href="https://unpkg.com/@coreui/coreui@4.2.0/dist/css/coreui.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --primary-red: #dc2626;
            --secondary-red: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1e3a8a 50%, var(--primary-red) 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
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
        }
        
        .forgot-password-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.98);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-red) 100%);
            color: white;
            padding: 35px 30px;
            text-align: center;
            border: none;
            position: relative;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .card-header-content {
            position: relative;
            z-index: 1;
        }
        
        .card-header h4 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .card-header p {
            margin: 10px 0 0 0;
            opacity: 0.95;
            font-size: 0.95rem;
        }
        
        .card-body {
            padding: 35px 30px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo-container img {
            max-width: 90px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .input-group-text {
            background: var(--light-blue);
            border: 1px solid var(--secondary-blue);
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .form-control {
            border: 1px solid #d1d5db;
            padding: 12px 15px;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            border: none;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, var(--primary-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.4);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .back-to-login a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .back-to-login a:hover {
            color: var(--primary-red);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            margin-top: 15px;
        }
        
        .info-box {
            background: #f3f4f6;
            border-left: 4px solid var(--secondary-blue);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .info-box small {
            color: #6b7280;
            line-height: 1.6;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .footer-text small {
            opacity: 0.9;
        }
        
        @media (max-width: 576px) {
            .forgot-password-container {
                padding: 15px;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .card-header {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="<?php echo APP_NAME; ?>">
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-header-content">
                    <h4>
                        <i class="cil-lock-unlocked"></i> Forgot Password?
                    </h4>
                    <p>Don't worry, we'll help you reset it</p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="cil-warning me-2" style="font-size: 1.25rem;"></i>
                            <div><?php echo $error; ?></div>
                        </div>
                        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="cil-check-circle me-2" style="font-size: 1.25rem; margin-top: 2px;"></i>
                            <div><?php echo $success; ?></div>
                        </div>
                        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="cil-user me-1"></i> Email Address or Student ID
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">
                                <i class="cil-envelope-closed"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   name="identifier" 
                                   placeholder="e.g., john@example.com or 12345678"
                                   value="<?php echo htmlspecialchars($identifier); ?>"
                                   required 
                                   autofocus>
                        </div>
                        <div class="info-box">
                            <small>
                                <i class="cil-info me-1"></i>
                                <strong>How it works:</strong> Enter your email or student ID. We'll send password reset instructions to your registered email and phone number (via SMS).
                            </small>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="cil-paper-plane me-2"></i> Send Reset Instructions
                        </button>
                    </div>
                </form>
                
                <div class="back-to-login">
                    <a href="login.php">
                        <i class="cil-arrow-left me-1"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
        
        <div class="footer-text">
            <small>
                <i class="cil-shield-alt me-1"></i>
                Secure Password Reset • <?php echo date('Y'); ?> UEW TESCON
            </small>
        </div>
    </div>
    
    <!-- CoreUI JS -->
    <script src="https://unpkg.com/@coreui/coreui@4.2.0/dist/js/coreui.bundle.min.js"></script>
</body>
</html>
