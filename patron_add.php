<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';
require_once 'classes/Position.php';
require_once 'classes/Region.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Add Patron';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);
$position = new Position($db);
$region = new Region($db);

// Get data for dropdowns
$regions = $region->getAll();
$patronPositions = $position->getPatronPositions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $institution = sanitize($_POST['institution']);
    $region = sanitize($_POST['region']);
    $constituency = sanitize($_POST['constituency']);
    $hails_from_region = sanitize($_POST['hails_from_region']);
    $hails_from_constituency = sanitize($_POST['hails_from_constituency']);
    $npp_position = sanitize($_POST['npp_position']);
    $campus_id = !empty($_POST['campus_id']) ? (int)$_POST['campus_id'] : null;
    
    // Handle photo upload
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['photo'], 'uploads/');
        if ($uploadResult['success']) {
            $photo = $uploadResult['filename'];
        }
    }
    
    // Create user account with Patron role
    $userResult = $user->register($email, $password, 'Patron');
    
    if ($userResult['success']) {
        $userId = $userResult['user_id'];
        
        // Create member profile (no student ID, year, program for patrons)
        $memberData = [
            'user_id' => $userId,
            'fullname' => $fullname,
            'phone' => $phone,
            'date_of_birth' => $date_of_birth,
            'photo' => $photo,
            'institution' => $institution,
            'department' => null,
            'program' => 'N/A',
            'year' => null,
            'student_id' => null,
            'position' => 'Patron',
            'region' => $region,
            'constituency' => $constituency,
            'hails_from_region' => $hails_from_region,
            'hails_from_constituency' => $hails_from_constituency,
            'npp_position' => $npp_position,
            'campus_id' => $campus_id,
            'membership_status' => 'Active'
        ];
        
        $memberResult = $member->create($memberData);
        
        if ($memberResult['success']) {
            setFlashMessage('success', 'Patron added successfully');
            redirect('members.php');
        } else {
            $errorMsg = isset($memberResult['message']) ? $memberResult['message'] : 'Failed to create patron profile';
            setFlashMessage('danger', $errorMsg);
        }
    } else {
        setFlashMessage('danger', $userResult['message']);
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Patron</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="members.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

<form method="POST" action="" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <strong>Account Information</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="cil-info"></i> <strong>Patron Access:</strong> Patrons login using their <strong>Email</strong> only (no student ID required).
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="email" required>
                            <div id="email-feedback" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" id="password" minlength="6" required>
                            <small class="text-muted">Minimum 6 characters</small>
                            <div id="password-feedback" class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personal Information -->
            <div class="card">
                <div class="card-header">
                    <strong>Personal Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fullname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="0XXXXXXXXX" maxlength="10" required>
                            <small class="text-muted">Enter 10 digits (e.g., 0241234567)</small>
                            <div id="phone-feedback" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campus Affiliation -->
            <div class="card">
                <div class="card-header">
                    <strong>Campus Affiliation</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="cil-info"></i> <strong>Note:</strong> Select the campus this patron will support or advise.
                    </div>
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
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Institution</label>
                            <select class="form-select" name="institution" id="institution_select">
                                <option value="">Select Region & Constituency First</option>
                            </select>
                            <small class="text-muted">Optional: Institution they're affiliated with (as faculty, alumni, etc.)</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Campus</label>
                            <select class="form-select" name="campus_id" id="campus_select">
                                <option value="">Select Institution First</option>
                            </select>
                            <small class="text-muted">Optional: Specific campus they support</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Origin Information -->
            <div class="card">
                <div class="card-header">
                    <strong>Origin (Where They Hail From)</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hails From Region</label>
                            <select class="form-select" name="hails_from_region" id="hails_region">
                                <option value="">Select Region</option>
                                <?php foreach ($regions as $reg): ?>
                                    <option value="<?php echo htmlspecialchars($reg['name']); ?>" data-id="<?php echo $reg['id']; ?>">
                                        <?php echo htmlspecialchars($reg['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hails From Constituency</label>
                            <select class="form-select" name="hails_from_constituency" id="hails_constituency">
                                <option value="">Select Region First</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="sticky-sidebar">
                <!-- Patron Type -->
                <div class="card">
                    <div class="card-header">
                        <strong>Patron Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <small><strong>Note:</strong> Patrons are faculty advisors, alumni, or supporters who are not students.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">NPP Position (if any)</label>
                            <input type="text" class="form-control" name="npp_position" placeholder="e.g., Constituency Chairman">
                        </div>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="cil-check"></i> Create Patron
                        </button>
                        <a href="members.php" class="btn btn-secondary w-100">
                            <i class="cil-x"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Include the same JavaScript from member_add.php -->
<script src="js/member_form.js"></script>

<?php include 'includes/footer.php'; ?>
