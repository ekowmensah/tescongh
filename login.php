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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .brand-logo {
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <div class="brand-logo">TG</div>
                            <h1 class="text-center mb-2">TESCON Ghana</h1>
                            <p class="text-medium-emphasis text-center mb-4">Membership Database System</p>
                            
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
                                    <small class="text-muted">Admin: use email | Members: use student ID</small>
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
                            
                            <div class="text-center mt-4">
                                <p class="text-medium-emphasis mb-0">
                                    <small>Default login: ekowme@gmail.com / password</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p class="text-white">
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
