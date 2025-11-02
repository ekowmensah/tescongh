<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';
require_once 'classes/Region.php';
require_once 'classes/VotingRegion.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

$regionObj = new Region($db);
$votingRegionObj = new VotingRegion($db);

$regions = $regionObj->getAll();
$votingRegions = $votingRegionObj->getAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = sanitize($_POST['student_id']);
    // Generate email from student ID for user account
    $email = strtolower(str_replace(' ', '', $student_id)) . '@member.uewtescon.com';
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $gender = sanitize($_POST['gender']);
    $institution = sanitize($_POST['institution']);
    $department = sanitize($_POST['department']);
    $program = sanitize($_POST['program']);
    $year = sanitize($_POST['year']);
    $position = isset($_POST['position']) ? sanitize($_POST['position']) : 'Member';
    $region = sanitize($_POST['region']);
    $constituency = sanitize($_POST['constituency']);
    $npp_position = sanitize($_POST['npp_position']);
    $voting_region_id = !empty($_POST['voting_region_id']) ? (int)$_POST['voting_region_id'] : null;
    $voting_constituency_id = !empty($_POST['voting_constituency_id']) ? (int)$_POST['voting_constituency_id'] : null;
    $campus_id = !empty($_POST['campus_id']) ? (int)$_POST['campus_id'] : null;
    
    // Handle photo upload with automatic passport size cropping
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadPassportPhoto($_FILES['photo'], 'uploads/');
        if ($uploadResult['success']) {
            $photo = $uploadResult['filename'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // Validation
    if (empty($fullname) || empty($phone) || empty($gender) || empty($password) || empty($institution) || empty($program) || empty($year) || empty($student_id) || empty($region) || empty($constituency) || empty($department) || empty($npp_position) || empty($voting_region_id) || empty($voting_constituency_id) || empty($campus_id)) {
        $error = 'Please fill in all required fields';
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Profile photo is required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            // Check if student ID already exists
            $check_query = "SELECT id FROM members WHERE student_id = :student_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':student_id', $student_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = 'Student ID already registered. Please login instead.';
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
                    $member_query = "INSERT INTO members (user_id, fullname, phone, gender, photo, institution, department, program, year, student_id, position, region, constituency, npp_position, voting_region_id, voting_constituency_id, campus_id, membership_status) 
                                    VALUES (:user_id, :fullname, :phone, :gender, :photo, :institution, :department, :program, :year, :student_id, :position, :region, :constituency, :npp_position, :voting_region_id, :voting_constituency_id, :campus_id, 'Active')";
                    $member_stmt = $db->prepare($member_query);
                    $member_stmt->bindParam(':user_id', $user_id);
                    $member_stmt->bindParam(':fullname', $fullname);
                    $member_stmt->bindParam(':phone', $phone);
                    $member_stmt->bindParam(':gender', $gender);
                    $member_stmt->bindParam(':photo', $photo);
                    $member_stmt->bindParam(':institution', $institution);
                    $member_stmt->bindParam(':department', $department);
                    $member_stmt->bindParam(':program', $program);
                    $member_stmt->bindParam(':year', $year);
                    $member_stmt->bindParam(':student_id', $student_id);
                    $member_stmt->bindParam(':position', $position);
                    $member_stmt->bindParam(':region', $region);
                    $member_stmt->bindParam(':constituency', $constituency);
                    $member_stmt->bindParam(':npp_position', $npp_position);
                    $member_stmt->bindParam(':voting_region_id', $voting_region_id);
                    $member_stmt->bindParam(':voting_constituency_id', $voting_constituency_id);
                    $member_stmt->bindParam(':campus_id', $campus_id);
                    $member_stmt->execute();
                    
                    $db->commit();
                    
                    // Auto-login the user after successful registration
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'Member';
                    $_SESSION['last_activity'] = time();
                    
                    // Redirect to dashboard
                    redirect('dashboard.php');
                    
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

// Get regions and institutions for dropdown
$database = new Database();
$db = $database->getConnection();
$regionObj = new Region($db);
$regions = $regionObj->getAll();

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
        
        /* Hide Take Picture button on desktop */
        @media (min-width: 768px) {
            #takePictureBtn {
                display: none !important;
            }
            #uploadPictureBtn {
                width: 100% !important;
            }
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
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="alert alert-info">
                                <i class="cil-info"></i> <strong>Login Credentials:</strong> You will login using your <strong>Student ID</strong> and password.
                            </div>
                            
                            <div class="section-title">Personal Information</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="fullname" placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="phone" placeholder="0XXXXXXXXX" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                    <small class="text-muted">Enter 10 digits (e.g., 0241234567)</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <!-- Empty column for layout balance -->
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Student ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="student_id" placeholder="Your student ID" required value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                                    <small class="text-muted"><strong>Important:</strong> This will be used for login</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profile Photo <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100 mb-2" role="group">
                                        <button type="button" class="btn btn-outline-primary" id="takePictureBtn">
                                            <i class="cil-camera"></i> Take Picture
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="uploadPictureBtn">
                                            <i class="cil-image"></i> Upload Photo
                                        </button>
                                    </div>
                                    <input type="file" class="form-control d-none" name="photo" id="photoInput" accept="image/*" data-preview="previewImage" required>
                                    <input type="file" class="form-control d-none" id="cameraInput" accept="image/*" capture="user" data-preview="previewImage">
                                    <div id="photoPreview" class="mt-2" style="display: none;">
                                        <img id="previewImage" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger ms-2" id="removePhotoBtn">Remove</button>
                                    </div>
                                    <small class="text-muted"><i class="cil-info"></i> Select an image, then crop it to your preferred size (600x600px)</small>
                                </div>
                            </div>
                            
                            <div class="section-title">Academic Information</div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Region <span class="text-danger">*</span></label>
                                    <select class="form-select" name="region" id="current_region" required>
                                        <option value="">Select Region</option>
                                        <?php foreach ($regions as $reg): ?>
                                            <option value="<?php echo htmlspecialchars($reg['name']); ?>" data-id="<?php echo $reg['id']; ?>">
                                                <?php echo htmlspecialchars($reg['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Constituency <span class="text-danger">*</span></label>
                                    <select class="form-select" name="constituency" id="current_constituency" required>
                                        <option value="">Select Region First</option>
                                    </select>
                                    <small class="text-muted">Required to load institutions</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Institution <span class="text-danger">*</span></label>
                                    <select class="form-select" name="institution" id="institution_select" required>
                                        <option value="">Select Region & Constituency First</option>
                                    </select>
                                    <small class="text-muted"><strong>Note:</strong> You must select both region and constituency to load institutions</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Campus <span class="text-danger">*</span></label>
                                    <select class="form-select" name="campus_id" id="campus_select" required>
                                        <option value="">Select Institution First</option>
                                    </select>
                                    <small class="text-muted">Campus will populate based on selected institution</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="department" placeholder="e.g., Computer Science" required value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program/Course <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="program" placeholder="e.g., BSc Computer Science" required value="<?php echo isset($_POST['program']) ? htmlspecialchars($_POST['program']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
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
                            </div>
                            
                            <div class="section-title">Political Information</div>
                            
                            <div class="mb-3">
                                <label class="form-label">NPP Position <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="npp_position" placeholder="e.g., Polling Station Executive" required value="<?php echo isset($_POST['npp_position']) ? htmlspecialchars($_POST['npp_position']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Voting Region <span class="text-danger">*</span></label>
                                <select class="form-select" name="voting_region_id" id="voting_region" required>
                                    <option value="">Select Voting Region</option>
                                    <?php foreach ($votingRegions as $vr): ?>
                                        <option value="<?php echo $vr['id']; ?>">
                                            <?php echo htmlspecialchars($vr['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Where you are registered to vote</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Voting Constituency <span class="text-danger">*</span></label>
                                <select class="form-select" name="voting_constituency_id" id="voting_constituency" required>
                                    <option value="">Select Voting Region First</option>
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
    <script>
    // Load constituencies when region is selected
    document.getElementById('current_region').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const regionId = selectedOption.getAttribute('data-id');
        const constituencySelect = document.getElementById('current_constituency');
        const institutionSelect = document.getElementById('institution_select');
        
        if (regionId) {
            // Load constituencies
            fetch('ajax/get_constituencies.php?region_id=' + regionId)
                .then(response => response.json())
                .then(data => {
                    constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
                    data.forEach(constituency => {
                        const option = document.createElement('option');
                        option.value = constituency.name;
                        option.setAttribute('data-id', constituency.id);
                        option.textContent = constituency.name;
                        constituencySelect.appendChild(option);
                    });
                });
            
            // Clear institutions - wait for constituency selection
            institutionSelect.innerHTML = '<option value="">Select Constituency to load institutions</option>';
        } else {
            constituencySelect.innerHTML = '<option value="">Select Region First</option>';
            institutionSelect.innerHTML = '<option value="">Select Region & Constituency First</option>';
        }
    });

    // Load institutions when constituency is selected
    document.getElementById('current_constituency').addEventListener('change', function() {
        const regionSelect = document.getElementById('current_region');
        const selectedRegionOption = regionSelect.options[regionSelect.selectedIndex];
        const regionId = selectedRegionOption.getAttribute('data-id');
        
        const selectedConstOption = this.options[this.selectedIndex];
        const constituencyId = selectedConstOption.getAttribute('data-id');
        const constituencyName = this.value;
        
        // Only load institutions if BOTH region and constituency are selected
        if (regionId && constituencyName && constituencyId) {
            loadInstitutions(regionId, constituencyId);
        } else {
            const institutionSelect = document.getElementById('institution_select');
            institutionSelect.innerHTML = '<option value="">Select Constituency to load institutions</option>';
        }
    });

    // Function to load institutions
    function loadInstitutions(regionId, constituencyId) {
        const institutionSelect = document.getElementById('institution_select');
        let url = 'ajax/get_institutions.php?region_id=' + regionId;
        if (constituencyId) {
            url += '&constituency_id=' + constituencyId;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                institutionSelect.innerHTML = '<option value="">Select Institution</option>';
                data.forEach(institution => {
                    const option = document.createElement('option');
                    option.value = institution.name;
                    option.textContent = institution.name;
                    institutionSelect.appendChild(option);
                });
                
                if (data.length === 0) {
                    institutionSelect.innerHTML = '<option value="">No institutions found in this area</option>';
                }
            });
    }

    // Load campuses when institution is selected
    document.getElementById('institution_select').addEventListener('change', function() {
        const institutionName = this.value;
        const campusSelect = document.getElementById('campus_select');
        
        if (institutionName) {
            fetch('ajax/get_campuses.php?institution=' + encodeURIComponent(institutionName))
                .then(response => response.json())
                .then(data => {
                    campusSelect.innerHTML = '<option value="">Select Campus (Optional)</option>';
                    data.forEach(campus => {
                        const option = document.createElement('option');
                        option.value = campus.id;
                        option.textContent = campus.name + ' - ' + campus.location;
                        campusSelect.appendChild(option);
                    });
                    
                    if (data.length === 0) {
                        campusSelect.innerHTML = '<option value="">No campuses found for this institution</option>';
                    }
                });
        } else {
            campusSelect.innerHTML = '<option value="">Select Institution First</option>';
        }
    });

    // Load voting constituencies when voting region is selected
    document.getElementById('voting_region').addEventListener('change', function() {
        const votingRegionId = this.value;
        const votingConstituencySelect = document.getElementById('voting_constituency');
        
        if (votingRegionId) {
            fetch('api/get_voting_constituencies.php?region_id=' + votingRegionId)
                .then(response => response.text())
                .then(html => {
                    votingConstituencySelect.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading voting constituencies:', error);
                    votingConstituencySelect.innerHTML = '<option value="">Error loading constituencies</option>';
                });
        } else {
            votingConstituencySelect.innerHTML = '<option value="">Select Voting Region First</option>';
        }
    });

    // Photo capture and upload functionality
    const takePictureBtn = document.getElementById('takePictureBtn');
    const uploadPictureBtn = document.getElementById('uploadPictureBtn');
    const photoInput = document.getElementById('photoInput');
    const cameraInput = document.getElementById('cameraInput');
    const photoPreview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    const removePhotoBtn = document.getElementById('removePhotoBtn');

    // Take Picture button - triggers camera
    takePictureBtn.addEventListener('click', function() {
        cameraInput.click();
    });

    // Upload Photo button - triggers file picker
    uploadPictureBtn.addEventListener('click', function() {
        photoInput.click();
    });

    // Handle camera capture
    cameraInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            // Trigger image cropper
            initImageCropper(this);
        }
    });

    // Handle file upload
    photoInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            // Trigger image cropper
            initImageCropper(this);
        }
    });

    // Show image preview
    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            photoPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Remove photo
    removePhotoBtn.addEventListener('click', function() {
        photoInput.value = '';
        cameraInput.value = '';
        photoPreview.style.display = 'none';
        previewImage.src = '';
    });
    </script>

<?php include 'includes/image_cropper.php'; ?>
</body>
</html>
