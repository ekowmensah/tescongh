<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';
require_once 'classes/Region.php';
require_once 'classes/VotingRegion.php';

if (!hasAnyRole(['Admin', 'Executive', 'Patron'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Edit Member';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);
$regionObj = new Region($db);
$votingRegionObj = new VotingRegion($db);

$regions = $regionObj->getAll();
$votingRegions = $votingRegionObj->getAll();

// Get member ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Member ID not provided');
    redirect('dashboard.php');
}

$memberId = (int)$_GET['id'];

// Check permissions - only Admin and Executive can edit any member
// Regular members and Patrons cannot edit profiles
if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to edit member profiles');
    redirect('member_view.php?id=' . $memberId);
}

$memberData = $member->getById($memberId);

if (!$memberData) {
    setFlashMessage('danger', 'Member not found');
    redirect('members.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $gender = sanitize($_POST['gender']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $institution = sanitize($_POST['institution']);
    $department = sanitize($_POST['department']);
    $program = sanitize($_POST['program']);
    $year = sanitize($_POST['year']);
    $student_id = sanitize($_POST['student_id']);
    $position = sanitize($_POST['position']);
    $region = sanitize($_POST['region']);
    $constituency = sanitize($_POST['constituency']);
    $hails_from_region = sanitize($_POST['hails_from_region']);
    $hails_from_constituency = sanitize($_POST['hails_from_constituency']);
    $npp_position = sanitize($_POST['npp_position']);
    $voting_region_id = !empty($_POST['voting_region_id']) ? (int)$_POST['voting_region_id'] : null;
    $voting_constituency_id = !empty($_POST['voting_constituency_id']) ? (int)$_POST['voting_constituency_id'] : null;
    $membership_status = sanitize($_POST['membership_status']);
    
    // Handle photo upload
    $photo = $memberData['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['photo'], 'uploads/');
        if ($uploadResult['success']) {
            $photo = $uploadResult['filename'];
            // Delete old photo
            if (!empty($memberData['photo']) && file_exists('uploads/' . $memberData['photo'])) {
                unlink('uploads/' . $memberData['photo']);
            }
        }
    }
    
    $updateData = [
        'fullname' => $fullname,
        'phone' => $phone,
        'gender' => $gender,
        'date_of_birth' => $date_of_birth,
        'photo' => $photo,
        'institution' => $institution,
        'department' => $department,
        'program' => $program,
        'year' => $year,
        'student_id' => $student_id,
        'position' => $position,
        'region' => $region,
        'constituency' => $constituency,
        'hails_from_region' => $hails_from_region,
        'hails_from_constituency' => $hails_from_constituency,
        'npp_position' => $npp_position,
        'voting_region_id' => $voting_region_id,
        'voting_constituency_id' => $voting_constituency_id,
        'membership_status' => $membership_status
    ];
    
    if ($member->update($memberId, $updateData)) {
        setFlashMessage('success', 'Member updated successfully');
        redirect('members.php');
    } else {
        setFlashMessage('danger', 'Failed to update member');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Member</h2>
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
            <div class="card">
                <div class="card-header">
                    <strong>Personal Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($memberData['fullname']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($memberData['phone']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($memberData['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($memberData['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo $memberData['date_of_birth']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="photo" id="memberPhotoInput" accept="image/*" data-preview="memberPhotoPreview" onchange="initImageCropper(this)">
                            <small class="text-muted d-block mt-1">
                                <i class="cil-info"></i> Click to select an image, then crop it to your preferred size
                            </small>
                            <div class="mt-2" id="memberPhotoPreviewContainer">
                                <img id="memberPhotoPreview" 
                                     src="<?php echo !empty($memberData['photo']) ? 'uploads/' . htmlspecialchars($memberData['photo']) : ''; ?>" 
                                     alt="Photo Preview" 
                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; <?php echo empty($memberData['photo']) ? 'display: none;' : ''; ?>">
                            </div>
                            <small class="text-muted d-block mt-1">Leave blank to keep current photo</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <strong>Current Location (School)</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Region <span class="text-danger">*</span></label>
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
                            <label class="form-label">Current Constituency</label>
                            <select class="form-select" name="constituency" id="current_constituency">
                                <option value="<?php echo htmlspecialchars($memberData['constituency'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($memberData['constituency'] ?? 'Select Region First'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <strong>Academic Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Institution <span class="text-danger">*</span></label>
                            <select class="form-select" name="institution" id="institution_select" required>
                                <option value="<?php echo htmlspecialchars($memberData['institution']); ?>">
                                    <?php echo htmlspecialchars($memberData['institution']); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Campus</label>
                            <select class="form-select" name="campus_id" id="campus_select">
                                <option value="<?php echo $memberData['campus_id'] ?? ''; ?>">
                                    <?php echo !empty($memberData['campus_name']) ? htmlspecialchars($memberData['campus_name']) : 'Select Campus (Optional)'; ?>
                                </option>
                            </select>
                            <small class="text-muted">Campus will populate based on selected institution</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" value="<?php echo htmlspecialchars($memberData['student_id']); ?>" required>
                            <small class="text-muted"><strong>Important:</strong> Used for login</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year/Level <span class="text-danger">*</span></label>
                            <select class="form-select" name="year" required>
                                <option value="">Select Year</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($memberData['year'] == $i) ? 'selected' : ''; ?>>
                                        Year <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($memberData['department'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Program <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="program" value="<?php echo htmlspecialchars($memberData['program']); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <strong>Origin (Hails From)</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hails From Region</label>
                            <select class="form-select" name="hails_from_region" id="hails_region">
                                <option value="">Select Region</option>
                                <?php foreach ($regions as $reg): ?>
                                    <option value="<?php echo htmlspecialchars($reg['name']); ?>" 
                                            data-id="<?php echo $reg['id']; ?>"
                                            <?php echo ($memberData['hails_from_region'] == $reg['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($reg['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hails From Constituency</label>
                            <select class="form-select" name="hails_from_constituency" id="hails_constituency">
                                <option value="<?php echo htmlspecialchars($memberData['hails_from_constituency'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($memberData['hails_from_constituency'] ?? 'Select Region First'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <strong>Position & Status</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Position <span class="text-danger">*</span></label>
                        <select class="form-select" name="position" required>
                            <option value="Member" <?php echo ($memberData['position'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                            <option value="Executive" <?php echo ($memberData['position'] == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                            <option value="Patron" <?php echo ($memberData['position'] == 'Patron') ? 'selected' : ''; ?>>Patron</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Membership Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="membership_status" required>
                            <option value="Active" <?php echo ($memberData['membership_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($memberData['membership_status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Suspended" <?php echo ($memberData['membership_status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                            <option value="Graduated" <?php echo ($memberData['membership_status'] == 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">NPP Position (if any)</label>
                        <input type="text" class="form-control" name="npp_position" value="<?php echo htmlspecialchars($memberData['npp_position'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Voting Region</label>
                        <select class="form-select" name="voting_region_id" id="voting_region">
                            <option value="">Select Voting Region</option>
                            <?php foreach ($votingRegions as $vr): ?>
                                <option value="<?php echo $vr['id']; ?>" <?php echo (isset($memberData['voting_region_id']) && $memberData['voting_region_id'] == $vr['id']) ? 'selected' : ''; ?>>
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
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="cil-check"></i> Update Member
                    </button>
                    <a href="members.php" class="btn btn-secondary w-100">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Load constituencies for current region
document.getElementById('current_region').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const regionId = selectedOption.getAttribute('data-id');
    const constituencySelect = document.getElementById('current_constituency');
    const institutionSelect = document.getElementById('institution_select');
    
    if (regionId) {
        fetch('ajax/get_constituencies.php?region_id=' + regionId)
            .then(response => response.json())
            .then(data => {
                constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
                data.forEach(constituency => {
                    const option = document.createElement('option');
                    option.value = constituency.name;
                    option.textContent = constituency.name;
                    constituencySelect.appendChild(option);
                });
            });
        
        loadInstitutions(regionId, null);
    }
});

// Load institutions when constituency changes
document.getElementById('current_constituency').addEventListener('change', function() {
    const regionSelect = document.getElementById('current_region');
    const selectedRegionOption = regionSelect.options[regionSelect.selectedIndex];
    const regionId = selectedRegionOption.getAttribute('data-id');
    
    if (regionId) {
        loadInstitutions(regionId, null);
    }
});

function loadInstitutions(regionId, constituencyId) {
    const institutionSelect = document.getElementById('institution_select');
    let url = 'ajax/get_institutions.php?region_id=' + regionId;
    if (constituencyId) {
        url += '&constituency_id=' + constituencyId;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const currentValue = institutionSelect.value;
            institutionSelect.innerHTML = '<option value="">Select Institution</option>';
            data.forEach(institution => {
                const option = document.createElement('option');
                option.value = institution.name;
                option.textContent = institution.name;
                if (institution.name === currentValue) {
                    option.selected = true;
                }
                institutionSelect.appendChild(option);
            });
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
                const currentCampusId = campusSelect.value;
                campusSelect.innerHTML = '<option value="">Select Campus (Optional)</option>';
                data.forEach(campus => {
                    const option = document.createElement('option');
                    option.value = campus.id;
                    option.textContent = campus.name + ' - ' + campus.location;
                    if (campus.id == currentCampusId) {
                        option.selected = true;
                    }
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

// Load campuses on page load if institution is set
window.addEventListener('DOMContentLoaded', function() {
    const institutionName = document.getElementById('institution_select').value;
    if (institutionName) {
        const event = new Event('change');
        document.getElementById('institution_select').dispatchEvent(event);
    }
});

// Load constituencies for hails from region
document.getElementById('hails_region').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const regionId = selectedOption.getAttribute('data-id');
    const constituencySelect = document.getElementById('hails_constituency');
    
    if (regionId) {
        fetch('ajax/get_constituencies.php?region_id=' + regionId)
            .then(response => response.json())
            .then(data => {
                constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
                data.forEach(constituency => {
                    const option = document.createElement('option');
                    option.value = constituency.name;
                    option.textContent = constituency.name;
                    constituencySelect.appendChild(option);
                });
            });
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
                // Re-select the current value if editing
                <?php if (isset($memberData['voting_constituency_id'])): ?>
                votingConstituencySelect.value = '<?php echo $memberData['voting_constituency_id']; ?>';
                <?php endif; ?>
            })
            .catch(error => {
                console.error('Error loading voting constituencies:', error);
                votingConstituencySelect.innerHTML = '<option value="">Error loading constituencies</option>';
            });
    } else {
        votingConstituencySelect.innerHTML = '<option value="">Select Voting Region First</option>';
    }
});

// Load voting constituencies on page load if voting region is already selected
window.addEventListener('DOMContentLoaded', function() {
    const votingRegionSelect = document.getElementById('voting_region');
    if (votingRegionSelect.value) {
        votingRegionSelect.dispatchEvent(new Event('change'));
    }
});

</script>

<?php include 'includes/image_cropper.php'; ?>
<?php include 'includes/footer.php'; ?>
