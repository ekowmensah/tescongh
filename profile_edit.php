<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';
require_once 'classes/Region.php';
require_once 'classes/VotingRegion.php';

$pageTitle = 'Edit Profile';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);
$regionObj = new Region($db);
$votingRegionObj = new VotingRegion($db);

$userData = $user->getUserById($_SESSION['user_id']);
$memberData = $member->getByUserId($_SESSION['user_id']);

$regions = $regionObj->getAll();
$votingRegions = $votingRegionObj->getAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $institution = sanitize($_POST['institution']);
    $department = sanitize($_POST['department']);
    $program = sanitize($_POST['program']);
    $year = sanitize($_POST['year']);
    $student_id = sanitize($_POST['student_id']);
    $region = sanitize($_POST['region']);
    $constituency = sanitize($_POST['constituency']);
    $npp_position = sanitize($_POST['npp_position']);
    $voting_region_id = !empty($_POST['voting_region_id']) ? (int)$_POST['voting_region_id'] : null;
    $voting_constituency_id = !empty($_POST['voting_constituency_id']) ? (int)$_POST['voting_constituency_id'] : null;
    $campus_id = !empty($_POST['campus_id']) ? (int)$_POST['campus_id'] : null;
    
    // Handle photo upload with automatic passport size cropping
    $photo = $memberData['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadPassportPhoto($_FILES['photo'], 'uploads/');
        if ($uploadResult['success']) {
            $photo = $uploadResult['filename'];
            // Delete old photo if exists
            if (!empty($memberData['photo']) && file_exists('uploads/' . $memberData['photo'])) {
                unlink('uploads/' . $memberData['photo']);
            }
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // Validation
    if (empty($fullname) || empty($phone) || empty($institution) || empty($program) || empty($year) || empty($student_id) || empty($region) || empty($constituency)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if student ID is being changed and if it's already taken
        if ($student_id !== $memberData['student_id']) {
            $check_query = "SELECT id FROM members WHERE student_id = :student_id AND id != :member_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':student_id', $student_id);
            $check_stmt->bindParam(':member_id', $memberData['id']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = 'Student ID already exists. Please use a different one.';
            }
        }
        
        if (empty($error)) {
            $updateData = [
                'fullname' => $fullname,
                'phone' => $phone,
                'photo' => $photo,
                'institution' => $institution,
                'department' => $department,
                'program' => $program,
                'year' => $year,
                'student_id' => $student_id,
                'region' => $region,
                'constituency' => $constituency,
                'npp_position' => $npp_position,
                'voting_region_id' => $voting_region_id,
                'voting_constituency_id' => $voting_constituency_id,
                'campus_id' => $campus_id
            ];
            
            if ($member->update($memberData['id'], $updateData)) {
                $success = 'Profile updated successfully!';
                // Refresh member data
                $memberData = $member->getByUserId($_SESSION['user_id']);
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Edit Profile</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="profile.php">Profile</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

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

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Personal Information</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fullname" required value="<?php echo htmlspecialchars($memberData['fullname']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" required value="<?php echo htmlspecialchars($memberData['phone']); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" required value="<?php echo htmlspecialchars($memberData['student_id']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="photo" id="photoInput" accept="image/*" data-preview="photoPreview" onchange="initImageCropper(this)">
                            <small class="text-muted d-block mt-1">
                                <i class="cil-info"></i> Click to select an image, then crop it to your preferred size
                            </small>
                            <div class="mt-2" id="photoPreviewContainer">
                                <img id="photoPreview" 
                                     src="<?php echo !empty($memberData['photo']) ? 'uploads/' . htmlspecialchars($memberData['photo']) : ''; ?>" 
                                     alt="Photo Preview" 
                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; <?php echo empty($memberData['photo']) ? 'display: none;' : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Academic Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Region <span class="text-danger">*</span></label>
                            <select class="form-select" name="region" id="current_region" required>
                                <option value="">Select Region</option>
                                <?php foreach ($regions as $reg): ?>
                                    <option value="<?php echo htmlspecialchars($reg['name']); ?>" 
                                            data-id="<?php echo $reg['id']; ?>"
                                            <?php echo ($memberData['region'] == $reg['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($reg['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Constituency <span class="text-danger">*</span></label>
                            <select class="form-select" name="constituency" id="current_constituency" required>
                                <option value="<?php echo htmlspecialchars($memberData['constituency']); ?>">
                                    <?php echo htmlspecialchars($memberData['constituency']); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Institution <span class="text-danger">*</span></label>
                            <select class="form-select" name="institution" id="institution_select" required>
                                <option value="<?php echo htmlspecialchars($memberData['institution']); ?>">
                                    <?php echo htmlspecialchars($memberData['institution']); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Campus</label>
                            <select class="form-select" name="campus_id" id="campus_select">
                                <option value="">Select Campus (Optional)</option>
                                <?php if (!empty($memberData['campus_id'])): ?>
                                    <option value="<?php echo $memberData['campus_id']; ?>" selected>
                                        <?php echo htmlspecialchars($memberData['campus_name']); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($memberData['department']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Program/Course <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="program" required value="<?php echo htmlspecialchars($memberData['program']); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                            <select class="form-select" name="year" required>
                                <option value="">Select Year</option>
                                <option value="1" <?php echo ($memberData['year'] == '1') ? 'selected' : ''; ?>>Year 1</option>
                                <option value="2" <?php echo ($memberData['year'] == '2') ? 'selected' : ''; ?>>Year 2</option>
                                <option value="3" <?php echo ($memberData['year'] == '3') ? 'selected' : ''; ?>>Year 3</option>
                                <option value="4" <?php echo ($memberData['year'] == '4') ? 'selected' : ''; ?>>Year 4</option>
                                <option value="5" <?php echo ($memberData['year'] == '5') ? 'selected' : ''; ?>>Year 5</option>
                                <option value="6" <?php echo ($memberData['year'] == '6') ? 'selected' : ''; ?>>Year 6</option>
                            </select>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Political Information (Optional)</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">NPP Position (if any)</label>
                        <input type="text" class="form-control" name="npp_position" value="<?php echo htmlspecialchars($memberData['npp_position']); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Voting Region</label>
                            <select class="form-select" name="voting_region_id" id="voting_region">
                                <option value="">Select Voting Region</option>
                                <?php foreach ($votingRegions as $vr): ?>
                                    <option value="<?php echo $vr['id']; ?>" 
                                            <?php echo ($memberData['voting_region_id'] == $vr['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vr['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Where you are registered to vote</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Voting Constituency</label>
                            <select class="form-select" name="voting_constituency_id" id="voting_constituency">
                                <option value="">Select Voting Region First</option>
                                <?php if (!empty($memberData['voting_constituency_id'])): ?>
                                    <option value="<?php echo $memberData['voting_constituency_id']; ?>" selected>
                                        Current Selection
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-end mt-4">
                        <a href="profile.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="cil-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
    
    if (regionId && constituencyId) {
        loadInstitutions(regionId, constituencyId);
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

// Trigger region change on page load to populate constituencies if region is already selected
window.addEventListener('DOMContentLoaded', function() {
    const regionSelect = document.getElementById('current_region');
    if (regionSelect.value) {
        const event = new Event('change');
        regionSelect.dispatchEvent(event);
    }
    
    const votingRegionSelect = document.getElementById('voting_region');
    if (votingRegionSelect.value) {
        const event = new Event('change');
        votingRegionSelect.dispatchEvent(event);
    }
});
</script>

<?php include 'includes/image_cropper.php'; ?>
<?php include 'includes/footer.php'; ?>
