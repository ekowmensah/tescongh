<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $institution = sanitize($_POST['institution']);
    $program = sanitize($_POST['program']);
    $year = sanitize($_POST['year']);
    $student_id = sanitize($_POST['student_id']);
    
    // Validation
    if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($institution) || empty($program) || empty($year)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = 'Email already registered. Please login instead.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Create user account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $user_query = "INSERT INTO users (email, password, role, status) VALUES (:email, :password, 'Member', 'Active')";
                    $user_stmt = $db->prepare($user_query);
                    $user_stmt->bindParam(':email', $email);
                    $user_stmt->bindParam(':password', $hashed_password);
                    $user_stmt->execute();
                    
                    $user_id = $db->lastInsertId();
                    
                    // Create member profile
                    $member_query = "INSERT INTO members (user_id, fullname, phone, institution, program, year, student_id, position, membership_status) 
                                    VALUES (:user_id, :fullname, :phone, :institution, :program, :year, :student_id, 'Member', 'Active')";
                    $member_stmt = $db->prepare($member_query);
                    $member_stmt->bindParam(':user_id', $user_id);
                    $member_stmt->bindParam(':fullname', $fullname);
                    $member_stmt->bindParam(':phone', $phone);
                    $member_stmt->bindParam(':institution', $institution);
                    $member_stmt->bindParam(':program', $program);
                    $member_stmt->bindParam(':year', $year);
                    $member_stmt->bindParam(':student_id', $student_id);
                    $member_stmt->execute();
                    
                    $db->commit();
                    
                    $success = 'Registration successful! You can now login with your email and password.';
                    
                    // Clear form
                    $_POST = array();
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Registration failed. Please try again.';
                }
            }
        } else {
            $error = 'Database connection failed';
        }
    }
}

// Get institutions for dropdown
$database = new Database();
$db = $database->getConnection();
$institutions_query = "SELECT DISTINCT institution FROM members ORDER BY institution ASC";
$institutions_stmt = $db->query($institutions_query);
$institutions = $institutions_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Register - <?php echo APP_NAME; ?></title>
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
        
        .register-card {
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
        
        .register-card h1 {
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
        
        .input-group-text {
            background: var(--light-blue);
            border: 1px solid var(--secondary-blue);
            color: var(--primary-blue);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        .login-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link:hover {
            color: var(--primary-red);
        }
        
        .section-title {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-blue);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card register-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="brand-logo">TG</div>
                        <h1 class="text-center mb-2">Join TESCON Ghana</h1>
                        <p class="text-medium-emphasis text-center mb-4">Register as a new member</p>
                        
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
                            <div class="section-title">Personal Information</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="fullname" placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" placeholder="your.email@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="phone" placeholder="+233XXXXXXXXX" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" name="student_id" placeholder="Your student ID" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="section-title">Academic Information</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Institution <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="institution" list="institutions" placeholder="Select or type institution" required value="<?php echo isset($_POST['institution']) ? htmlspecialchars($_POST['institution']) : ''; ?>">
                                    <datalist id="institutions">
                                        <?php foreach ($institutions as $inst): ?>
                                            <option value="<?php echo htmlspecialchars($inst); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program/Course <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="program" placeholder="e.g., BSc Computer Science" required value="<?php echo isset($_POST['program']) ? htmlspecialchars($_POST['program']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                                <select class="form-select" name="year" required>
                                    <option value="">Select Year</option>
                                    <option value="1" <?php echo (isset($_POST['year']) && $_POST['year'] == '1') ? 'selected' : ''; ?>>Year 1</option>
                                    <option value="2" <?php echo (isset($_POST['year']) && $_POST['year'] == '2') ? 'selected' : ''; ?>>Year 2</option>
                                    <option value="3" <?php echo (isset($_POST['year']) && $_POST['year'] == '3') ? 'selected' : ''; ?>>Year 3</option>
                                    <option value="4" <?php echo (isset($_POST['year']) && $_POST['year'] == '4') ? 'selected' : ''; ?>>Year 4</option>
                                    <option value="5" <?php echo (isset($_POST['year']) && $_POST['year'] == '5') ? 'selected' : ''; ?>>Year 5</option>
                                    <option value="6" <?php echo (isset($_POST['year']) && $_POST['year'] == '6') ? 'selected' : ''; ?>>Year 6</option>
                                </select>
                            </div>
                            
                            <div class="section-title">Account Security</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password" placeholder="Minimum 6 characters" required>
                                    <small class="text-muted">At least 6 characters</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">
                                <a href="login.php" class="login-link">Already have an account? Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-white mb-2">
                        <a href="home.php" class="text-white text-decoration-none"><i class="cil-arrow-left"></i> Back to Home</a>
                    </p>
                    <p class="text-white">
                        <small>&copy; <?php echo date('Y'); ?> TESCON Ghana. All rights reserved.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/@coreui/coreui@4.2.0/dist/js/coreui.bundle.min.js"></script>
</body>
</html>
