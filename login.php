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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier']); // Can be email or student ID
    $password = $_POST['password'];
    
    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $user = new User($db);
            
            // Check if identifier is email or student ID
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                // Login with email (for Admin/Executive)
                $result = $user->login($identifier, $password);
            } else {
                // Login with student ID (for Members)
                $result = $user->loginWithStudentId($identifier, $password);
            }
            
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['email'] = $result['user']['email'];
                $_SESSION['role'] = $result['user']['role'];
                $_SESSION['last_activity'] = time();
                
                redirect('dashboard.php');
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Database connection failed';
        }
    }
}

// Check for timeout
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Login - <?php echo APP_NAME; ?></title>
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
            overflow: hidden;
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
        
        .login-container {
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.98);
        }
        
        .brand-logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--secondary-red) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.75rem;
            color: white;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
            border: 3px solid white;
        }
        
        .brand-logo img {
            width: 90%;
            height: 90%;
            object-fit: contain;
            border-radius: 50%;
        }
        
        .login-card h1 {
            color: var(--primary-blue);
            font-weight: 800;
            font-size: 1.75rem;
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
        
        .input-group-text {
            background: var(--light-blue);
            border: 1px solid var(--secondary-blue);
            color: var(--primary-blue);
        }
        
        .form-control:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        .register-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .register-link:hover {
            color: var(--primary-red);
        }
        
        .login-image-section {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%);
            border-radius: 20px 0 0 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1.25rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-image-section::before {
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
        
        .login-image-section .content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .login-image-section h2 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .login-image-section p {
            font-size: 0.9rem;
            opacity: 0.95;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            text-align: left;
            margin-top: 0.75rem;
        }
        
        .feature-list li {
            padding: 0.35rem 0;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        .feature-list li i {
            margin-right: 0.6rem;
            font-size: 1.1rem;
            color: rgba(255,255,255,0.9);
        }
        
        .illustration {
            width: 100%;
            max-width: 160px;
            margin: 0.75rem auto;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .login-form-section {
            padding: 1.5rem 1.75rem 1rem 1.75rem;
        }
        
        @media (max-width: 991px) {
            .login-image-section {
                border-radius: 20px 20px 0 0;
                padding: 2rem 1.5rem;
            }
            
            .login-image-section h2 {
                font-size: 1.8rem;
            }
            
            .illustration {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex flex-row align-items-center">
        <div class="container login-container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="card login-card">
                        <div class="row g-0">
                            <!-- Image Section -->
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="login-image-section">
                                    <div class="content">
                                        <h2>Welcome Back!</h2>
                                        <p>Access your TESCON member portal</p>
                                        
                                        <div class="illustration">
                                            <svg viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                                                <!-- Graduation Cap -->
                                                <circle cx="250" cy="200" r="120" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="4"/>
                                                <rect x="190" y="160" width="120" height="80" fill="rgba(255,255,255,0.3)" stroke="white" stroke-width="3" rx="10"/>
                                                <polygon points="250,140 150,180 250,220 350,180" fill="white" opacity="0.9"/>
                                                <rect x="240" y="220" width="20" height="60" fill="white" opacity="0.8"/>
                                                <circle cx="250" cy="280" r="15" fill="#dc2626"/>
                                                
                                                <!-- Books -->
                                                <rect x="180" y="320" width="60" height="80" fill="rgba(255,255,255,0.3)" stroke="white" stroke-width="2" rx="5"/>
                                                <rect x="260" y="330" width="60" height="70" fill="rgba(255,255,255,0.4)" stroke="white" stroke-width="2" rx="5"/>
                                                
                                                <!-- Stars -->
                                                <circle cx="100" cy="100" r="3" fill="white" opacity="0.8"/>
                                                <circle cx="400" cy="120" r="4" fill="white" opacity="0.7"/>
                                                <circle cx="380" cy="300" r="3" fill="white" opacity="0.9"/>
                                                <circle cx="120" cy="350" r="4" fill="white" opacity="0.6"/>
                                            </svg>
                                        </div>
                                        
                                        <ul class="feature-list">
                                            <li>
                                                <i class="cil-check-circle"></i>
                                                <span>Manage your membership profile</span>
                                            </li>
                                            <li>
                                                <i class="cil-check-circle"></i>
                                                <span>Stay updated with events</span>
                                            </li>
                                            <li>
                                                <i class="cil-check-circle"></i>
                                                <span>Connect with fellow members</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Section -->
                            <div class="col-lg-6">
                                <div class="login-form-section">
                                    <div class="brand-logo">
                                        <?php 
                                        // Check if logo exists, otherwise show text
                                        $logo_path = 'assets/images/logo.png';
                                        if (file_exists($logo_path)): 
                                        ?>
                                            <img src="<?php echo $logo_path; ?>" alt="TESCON Logo">
                                        <?php else: ?>
                                            TG
                                        <?php endif; ?>
                                    </div>
                                    <h1 class="text-center mb-2">TESCON Ghana</h1>
                                    <p class="text-medium-emphasis text-center mb-4">Member Login Portal</p>
                            
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
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Email or Student ID</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="cil-user"></i>
                                        </span>
                                        <input type="text" class="form-control" name="identifier" placeholder="Enter email or student ID" required autofocus>
                                    </div>
                                  <!--  <small class="text-muted">Admin: use email | Members: use student ID</small>-->
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="cil-lock-locked"></i>
                                        </span>
                                        <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                                </div>
                            </form>
                            
                                    <div class="text-center mt-3 mb-0">
                                  <!--      <p class="text-medium-emphasis mb-2">
                                            <small>Default login: ekowme@gmail.com / password</small>
                                        </p> -->
                                        <p class="mb-0">
                                            <a href="signup.php" class="register-link">Don't have an account? Register here</a>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="text-center mt-4">
                        <p class="text-white mb-2">
                            <a href="home.php" class="text-white text-decoration-none fw-semibold">
                                <i class="cil-arrow-left"></i> Back to Home
                            </a>
                        </p>
                        <p class="text-white mb-0">
                            <small>&copy; <?php echo date('Y'); ?> TESCON Ghana. All rights reserved.</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/@coreui/coreui@4.2.0/dist/js/coreui.bundle.min.js"></script>
</body>
</html>
