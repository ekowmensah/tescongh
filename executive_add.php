<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';
require_once 'classes/Position.php';
require_once 'classes/Region.php';
require_once 'classes/VotingRegion.php';
require_once 'classes/Campus.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Add Executive';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);
$position = new Position($db);
$region = new Region($db);
$votingRegion = new VotingRegion($db);
$campus = new Campus($db);

// Get data for dropdowns
$regions = $region->getAll();
$votingRegions = $votingRegion->getAll();
$executivePositions = $position->getExecutivePositions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $institution = sanitize($_POST['institution']);
    $department = sanitize($_POST['department']);
    $program = sanitize($_POST['program']);
    $year = sanitize($_POST['year']);
    $student_id = sanitize($_POST['student_id']);
    $region = sanitize($_POST['region']);
    $constituency = sanitize($_POST['constituency']);
    $hails_from_region = sanitize($_POST['hails_from_region']);
    $hails_from_constituency = sanitize($_POST['hails_from_constituency']);
    $npp_position = sanitize($_POST['npp_position']);
    $voting_region_id = !empty($_POST['voting_region_id']) ? (int)$_POST['voting_region_id'] : null;
    $voting_constituency_id = !empty($_POST['voting_constituency_id']) ? (int)$_POST['voting_constituency_id'] : null;
    $campus_id = !empty($_POST['campus_id']) ? (int)$_POST['campus_id'] : null;
    $position_id = !empty($_POST['position_id']) ? (int)$_POST['position_id'] : null;
    
    // Handle photo upload
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['photo'], 'uploads/');
        if ($uploadResult['success']) {
            $photo = $uploadResult['filename'];
        }
    }
    
    // Create user account with Executive role
    $userResult = $user->register($email, $password, 'Executive');
    
    if ($userResult['success']) {
        $userId = $userResult['user_id'];
        
        // Create member profile
        $memberData = [
            'user_id' => $userId,
            'fullname' => $fullname,
            'phone' => $phone,
            'date_of_birth' => $date_of_birth,
            'photo' => $photo,
            'institution' => $institution,
            'department' => $department,
            'program' => $program,
            'year' => $year,
            'student_id' => $student_id,
            'position' => 'Executive',
            'region' => $region,
            'constituency' => $constituency,
            'hails_from_region' => $hails_from_region,
            'hails_from_constituency' => $hails_from_constituency,
            'npp_position' => $npp_position,
            'voting_region_id' => $voting_region_id,
            'voting_constituency_id' => $voting_constituency_id,
            'campus_id' => $campus_id,
            'membership_status' => 'Active'
        ];
        
        $memberResult = $member->create($memberData);
        
        if ($memberResult['success']) {
            $memberId = $memberResult['member_id'];
            
            // Assign executive position to campus
            if ($campus_id && $position_id) {
                $query = "INSERT INTO campus_executives (campus_id, member_id, position_id) 
                          VALUES (:campus_id, :member_id, :position_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':campus_id', $campus_id);
                $stmt->bindParam(':member_id', $memberId);
                $stmt->bindParam(':position_id', $position_id);
                $stmt->execute();
            }
            
            setFlashMessage('success', 'Executive added successfully');
            redirect('members.php');
        } else {
            $errorMsg = isset($memberResult['message']) ? $memberResult['message'] : 'Failed to create executive profile';
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
        <h2>Add New Executive</h2>
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
                        <i class="cil-info"></i> <strong>Executive Access:</strong> Executives can login using their <strong>Email</strong> or <strong>Student ID</strong>.
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
            
            <!-- Current Location & Campus Assignment -->
            <div class="card">
                <div class="card-header">
                    <strong>Campus Assignment</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="cil-warning"></i> <strong>Important:</strong> Select the campus where this executive will serve.
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Region <span class="text-danger">*</span></label>
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
                            <label class="form-label">Current Constituency <span class="text-danger">*</span></label>
                            <select class="form-select" name="constituency" id="current_constituency" required>
                                <option value="">Select Region First</option>
                            </select>
                            <small class="text-muted">Required to load institutions</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Academic Information -->
            <div class="card">
                <div class="card-header">
                    <strong>Academic Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Institution <span class="text-danger">*</span></label>
                            <select class="form-select" name="institution" id="institution_select" required>
                                <option value="">Select Region & Constituency First</option>
                            </select>
                            <small class="text-muted"><strong>Note:</strong> You must select both region and constituency to load institutions</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select" name="campus_id" id="campus_select" required>
                                <option value="">Select Institution First</option>
                            </select>
                            <small class="text-muted"><strong>This is where the executive will serve</strong></small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" required>
                            <small class="text-muted"><strong>Important:</strong> Can be used for login</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year/Level <span class="text-danger">*</span></label>
                            <select class="form-select" name="year" required>
                                <option value="">Select Year</option>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                                <option value="5">Year 5</option>
                                <option value="6">Year 6</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" placeholder="e.g., Computer Science">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Program <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="program" placeholder="e.g., BSc Computer Science" required>
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
                <!-- Executive Position -->
                <div class="card">
                    <div class="card-header">
                        <strong>Executive Position</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Position <span class="text-danger">*</span></label>
                            <select class="form-select" name="position_id" required>
                                <option value="">Select Position</option>
                                <?php foreach ($executivePositions as $pos): ?>
                                    <option value="<?php echo $pos['id']; ?>">
                                        <?php echo htmlspecialchars($pos['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Their role in the campus executive team</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">NPP Position (if any)</label>
                            <input type="text" class="form-control" name="npp_position" placeholder="e.g., Constituency Secretary">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Voting Region</label>
                            <select class="form-select" name="voting_region_id" id="voting_region">
                                <option value="">Select Voting Region</option>
                                <?php foreach ($votingRegions as $vr): ?>
                                    <option value="<?php echo $vr['id']; ?>">
                                        <?php echo htmlspecialchars($vr['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Where they are registered to vote</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Voting Constituency</label>
                            <select class="form-select" name="voting_constituency_id" id="voting_constituency">
                                <option value="">Select Voting Region First</option>
                            </select>
                            <small class="text-muted">Their constituency for voting</small>
                        </div>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="cil-check"></i> Create Executive
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

<script>
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
</script>

<?php include 'includes/footer.php'; ?>
