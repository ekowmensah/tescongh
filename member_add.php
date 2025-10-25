<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';
require_once 'classes/Region.php';

$pageTitle = 'Add Member';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);
$regionObj = new Region($db);

$regions = $regionObj->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $institution = sanitize($_POST['institution']);
    $department = sanitize($_POST['department']);
    $program = sanitize($_POST['program']);
    $year = sanitize($_POST['year']);
    $student_id = sanitize($_POST['student_id']);
    $position = sanitize($_POST['position']);
    $region = sanitize($_POST['region']);
    $constituency = sanitize($_POST['constituency']);
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
    
    // Create user account
    $userResult = $user->register($email, $password, 'Member');
    
    if ($userResult['success']) {
        $userId = $userResult['user_id'];
        
        // Create member profile
        $memberData = [
            'user_id' => $userId,
            'fullname' => $fullname,
            'phone' => $phone,
            'photo' => $photo,
            'institution' => $institution,
            'department' => $department,
            'program' => $program,
            'year' => $year,
            'student_id' => $student_id,
            'position' => $position,
            'region' => $region,
            'constituency' => $constituency,
            'npp_position' => $npp_position,
            'campus_id' => $campus_id,
            'membership_status' => 'Active'
        ];
        
        $memberResult = $member->create($memberData);
        
        if ($memberResult['success']) {
            setFlashMessage('success', 'Member added successfully');
            redirect('members.php');
        } else {
            $errorMsg = isset($memberResult['message']) ? $memberResult['message'] : 'Failed to create member profile';
            setFlashMessage('danger', $errorMsg);
        }
    } else {
        setFlashMessage('danger', $userResult['message']);
        redirect('member_add.php');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Member</h2>
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
                    <strong>Account Information</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="cil-info"></i> <strong>Login Credentials:</strong> Members will login using their <strong>Student ID</strong> and password. Email is for communication only.
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="email" required>
                            <small class="text-muted">For communication and notifications</small>
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
            
            <div class="card">
                <div class="card-header">
                    <strong>Personal Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fullname" id="fullname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="0XXXXXXXXX" maxlength="10" required>
                            <small class="text-muted">Enter 10 digits (e.g., 0241234567)</small>
                            <div id="phone-feedback" class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
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
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Institution <span class="text-danger">*</span></label>
                            <select class="form-select" name="institution" id="institution_select" required>
                                <option value="">Select Region & Constituency First</option>
                            </select>
                            <small class="text-muted"><strong>Note:</strong> You must select both region and constituency to load institutions</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Campus</label>
                            <select class="form-select" name="campus_id" id="campus_select">
                                <option value="">Select Institution First</option>
                            </select>
                            <small class="text-muted">Campus will populate based on selected institution</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" id="student_id" required>
                            <small class="text-muted"><strong>Important:</strong> This will be used for login</small>
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
        </div>
        
        <div class="col-md-4">
            <div class="sticky-sidebar">
                <div class="card">
                    <div class="card-header">
                        <strong>Position & Role</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Position <span class="text-danger">*</span></label>
                            <select class="form-select" name="position" required>
                                <option value="Member">Member</option>
                                <option value="Executive">Executive</option>
                                <option value="Patron">Patron</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">NPP Position (if any)</label>
                            <input type="text" class="form-control" name="npp_position" placeholder="e.g., Polling Station Executive">
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="cil-check"></i> Create Member
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

<script>
// Real-time Email Validation with Database Check
const emailInput = document.getElementById('email');
const emailFeedback = document.getElementById('email-feedback');
let emailCheckTimeout;

emailInput.addEventListener('input', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    // Clear previous timeout
    clearTimeout(emailCheckTimeout);
    
    if (email === '') {
        this.classList.remove('is-valid', 'is-invalid');
        emailFeedback.textContent = '';
        return;
    }
    
    // First check format
    if (!emailRegex.test(email)) {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
        emailFeedback.textContent = 'Please enter a valid email address';
        emailFeedback.style.display = 'block';
        return;
    }
    
    // Show checking message
    this.classList.remove('is-valid', 'is-invalid');
    emailFeedback.textContent = 'Checking availability...';
    emailFeedback.style.display = 'block';
    emailFeedback.style.color = '#6c757d';
    
    // Check database after 500ms delay (debounce)
    emailCheckTimeout = setTimeout(() => {
        fetch('ajax/check_email.php?email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    emailInput.classList.remove('is-valid');
                    emailInput.classList.add('is-invalid');
                    emailFeedback.textContent = '✗ ' + data.message;
                    emailFeedback.style.color = '#dc3545';
                    emailFeedback.style.display = 'block';
                } else {
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    emailFeedback.textContent = '✓ ' + data.message;
                    emailFeedback.style.color = '#28a745';
                    emailFeedback.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error checking email:', error);
                emailFeedback.textContent = 'Could not verify email';
                emailFeedback.style.color = '#dc3545';
            });
    }, 500);
});

// Real-time Phone Number Validation with Database Check
const phoneInput = document.getElementById('phone');
const phoneFeedback = document.getElementById('phone-feedback');
let phoneCheckTimeout;

phoneInput.addEventListener('input', function() {
    // Remove non-numeric characters
    let phone = this.value.replace(/\D/g, '');
    this.value = phone;
    
    // Clear previous timeout
    clearTimeout(phoneCheckTimeout);
    
    if (phone === '') {
        this.classList.remove('is-valid', 'is-invalid');
        phoneFeedback.textContent = '';
        return;
    }
    
    // Check length and starting digit
    if (phone.length !== 10) {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
        phoneFeedback.textContent = 'Phone number must be exactly 10 digits (current: ' + phone.length + ')';
        phoneFeedback.style.display = 'block';
        phoneFeedback.style.color = '#dc3545';
        return;
    }
    
    if (!phone.startsWith('0')) {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
        phoneFeedback.textContent = 'Phone number must start with 0';
        phoneFeedback.style.display = 'block';
        phoneFeedback.style.color = '#dc3545';
        return;
    }
    
    // Show checking message
    this.classList.remove('is-valid', 'is-invalid');
    phoneFeedback.textContent = 'Checking availability...';
    phoneFeedback.style.display = 'block';
    phoneFeedback.style.color = '#6c757d';
    
    // Check database after 500ms delay (debounce)
    phoneCheckTimeout = setTimeout(() => {
        fetch('ajax/check_phone.php?phone=' + encodeURIComponent(phone))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    phoneInput.classList.remove('is-valid');
                    phoneInput.classList.add('is-invalid');
                    phoneFeedback.textContent = '✗ ' + data.message;
                    phoneFeedback.style.color = '#dc3545';
                    phoneFeedback.style.display = 'block';
                } else {
                    phoneInput.classList.remove('is-invalid');
                    phoneInput.classList.add('is-valid');
                    phoneFeedback.textContent = '✓ ' + data.message;
                    phoneFeedback.style.color = '#28a745';
                    phoneFeedback.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error checking phone:', error);
                phoneFeedback.textContent = 'Could not verify phone number';
                phoneFeedback.style.color = '#dc3545';
            });
    }, 500);
});

// Password Strength Validation
const passwordInput = document.getElementById('password');
const passwordFeedback = document.getElementById('password-feedback');

passwordInput.addEventListener('input', function() {
    const password = this.value;
    
    if (password === '') {
        this.classList.remove('is-valid', 'is-invalid');
        passwordFeedback.textContent = '';
        return;
    }
    
    if (password.length < 6) {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
        passwordFeedback.textContent = 'Password must be at least 6 characters (current: ' + password.length + ')';
        passwordFeedback.style.display = 'block';
    } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        passwordFeedback.textContent = '';
    }
});

// Form Submission Validation
document.querySelector('form').addEventListener('submit', function(e) {
    const email = emailInput.value.trim();
    const phone = phoneInput.value.trim();
    const password = passwordInput.value;
    
    let isValid = true;
    let errorMessage = '';
    
    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        isValid = false;
        errorMessage += 'Invalid email address.\n';
        emailInput.classList.add('is-invalid');
    }
    
    // Validate phone
    if (phone.length !== 10 || !phone.startsWith('0')) {
        isValid = false;
        errorMessage += 'Phone number must be exactly 10 digits starting with 0.\n';
        phoneInput.classList.add('is-invalid');
    }
    
    // Validate password
    if (password.length < 6) {
        isValid = false;
        errorMessage += 'Password must be at least 6 characters.\n';
        passwordInput.classList.add('is-invalid');
    }
    
    if (!isValid) {
        e.preventDefault();
        alert(errorMessage);
        return false;
    }
});

// Load constituencies and institutions for current location
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

// Load institutions ONLY when constituency is selected
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

</script>

<?php include 'includes/footer.php'; ?>
