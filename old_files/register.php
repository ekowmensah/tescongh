<?php
/**
 * TESCON Ghana - Member Registration
 */
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/FileUpload.php';
require_once 'includes/SMSNotifications.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$pageTitle = "Member Registration";
$breadcrumbs = [
    ['title' => 'Account', 'url' => '#'],
    ['title' => 'Register', 'url' => '#']
];
$error = '';
$success = '';

// Get regions and constituencies for dropdowns
$regions = [];
$constituencies = [];
$institutions = [];
$campuses = [];
try {
    $stmt = $pdo->query("SELECT * FROM regions ORDER BY name");
    $regions = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT c.*, r.name as region_name
        FROM constituencies c
        JOIN regions r ON c.region_id = r.id
        ORDER BY r.name, c.name
    ");
    $constituencies = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT i.*, r.name as region_name, c.name as constituency_name
        FROM institutions i
        JOIN regions r ON i.region_id = r.id
        JOIN constituencies c ON i.constituency_id = c.id
        ORDER BY r.name, c.name, i.name
    ");
    $institutions = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT ca.*, r.name as region_name, co.name as constituency_name, i.name as institution_name
        FROM campuses ca
        JOIN regions r ON ca.region_id = r.id
        JOIN constituencies co ON ca.constituency_id = co.id
        JOIN institutions i ON ca.institution_id = i.id
        ORDER BY r.name, co.name, ca.name
    ");
    $campuses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in register.php: " . $e->getMessage());
    // Handle error silently for now
}

// Debug: check if regions are loaded
error_log("Regions count: " . count($regions));
if (count($regions) > 0) {
    error_log("First region: " . print_r($regions[0], true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        logSecurityEvent('csrf_failure', ['page' => 'register']);
    } else {
        // Get and sanitize form data
        $fullname = sanitizeInput($_POST['fullname']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $date_of_birth = $_POST['date_of_birth'];
        $institution = sanitizeInput($_POST['institution']);
        $department = sanitizeInput($_POST['department']);
        $program = sanitizeInput($_POST['program']);
        $year = sanitizeInput($_POST['year']);
        $student_id = sanitizeInput($_POST['student_id']);
        $position = 'Member';
        $region = sanitizeInput($_POST['region']);
        $constituency = sanitizeInput($_POST['constituency']);
        $npp_position = sanitizeInput($_POST['npp_position']);
        $hails_from_region = sanitizeInput($_POST['hails_from_region']);
        $hails_from_constituency = sanitizeInput($_POST['hails_from_constituency']);
        $campus_id = !empty($_POST['campus_id']) ? $_POST['campus_id'] : null;
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

    // Validate form data
    if (empty($fullname) || empty($email) || empty($phone) || empty($student_id) || empty($password) || empty($confirm_password)) {
        $error = 'All required fields must be filled';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!empty($date_of_birth) && !strtotime($date_of_birth)) {
        $error = 'Invalid date of birth';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = 'Email already exists';
            } else {
                // Check if student ID already exists
                $stmt = $pdo->prepare("SELECT id FROM members WHERE student_id = ?");
                $stmt->execute([$student_id]);

                if ($stmt->rowCount() > 0) {
                    $error = 'Student ID already exists';
                } else {
                    // Handle photo upload
                    $photo_filename = null;
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploader = new FileUpload('uploads/photos/');
                        $upload_result = $uploader->upload($_FILES['photo'], 'member_');
                        if ($upload_result['success']) {
                            $photo_filename = $upload_result['filename'];
                        } else {
                            $error = $upload_result['error'];
                        }
                    }

                    if (empty($error)) {
                        // Start transaction
                        $pdo->beginTransaction();

                        try {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                            // Create user account first
                            $stmt = $pdo->prepare("
                                INSERT INTO users (email, password, role, status, email_verified, phone_verified)
                                VALUES (?, ?, ?, 'Active', 0, 0)
                            ");
                            $stmt->execute([$email, $hashed_password, $position]);

                            $userId = $pdo->lastInsertId();

                            // Handle photo upload
                            $photo_filename = null;
                            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                                $uploader = new FileUpload('uploads/photos/');
                                $upload_result = $uploader->upload($_FILES['photo'], 'member_');
                                if ($upload_result['success']) {
                                    $photo_filename = $upload_result['filename'];
                                } else {
                                    $error = $upload_result['error'];
                                }
                            }

                            if (empty($error)) {
                                // Create member record
                                $stmt = $pdo->prepare("
                                    INSERT INTO members (user_id, fullname, phone, date_of_birth, photo, institution, department, program, year, student_id, position, region, constituency, hails_from_region, hails_from_constituency, npp_position, campus_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([$userId, $fullname, $phone, $date_of_birth, $photo_filename, $institution, $department, $program, $year, $student_id, $position, $region, $constituency, $hails_from_region, $hails_from_constituency, $npp_position, $campus_id]);

                                $memberId = $pdo->lastInsertId();

                                // Commit transaction
                                $pdo->commit();

                                $success = 'Registration successful! You can now login.';

                                // Send welcome SMS to new member
                                $smsResult = sendWelcomeSMS($memberId);
                                if (!$smsResult['success']) {
                                    // Don't fail registration, just log the SMS failure
                                    error_log("Failed to send welcome SMS to new member ID {$memberId}: " . ($smsResult['error'] ?? 'Unknown error'));
                                }

                                // Clear form
                                $_POST = array();
                            } else {
                                // Rollback if photo upload failed
                                $pdo->rollBack();
                            }

                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            $error = 'Database error: ' . $e->getMessage();
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}}
?>

<?php
include 'includes/coreui_layout_start.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Member Registration</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <!-- Personal Information -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fullname" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" required
                                           value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                           value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</div>
                            </div>

                            <!-- Location Information -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="region" class="form-label">Region</label>
                                    <select class="form-select" id="region" name="region">
                                        <option value="">Select Region</option>
                                        <?php foreach ($regions as $region): ?>
                                            <option value="<?php echo $region['name']; ?>" <?php echo (isset($_POST['region']) && $_POST['region'] == $region['name']) ? 'selected' : ''; ?>><?php echo $region['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="constituency" class="form-label">Constituency</label>
                                    <select class="form-select" id="constituency" name="constituency">
                                        <option value="">Select Constituency</option>
                                        <?php foreach ($constituencies as $constituency): ?>
                                            <option value="<?php echo $constituency['name']; ?>" data-region="<?php echo $constituency['region_name']; ?>" <?php echo (isset($_POST['constituency']) && $_POST['constituency'] == $constituency['name']) ? 'selected' : ''; ?>><?php echo $constituency['name']; ?> (<?php echo $constituency['region_name']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Institution & Campus -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="institution" class="form-label">Institution *</label>
                                    <select class="form-select" id="institution" name="institution" required>
                                        <option value="">Select Institution</option>
                                        <?php foreach ($institutions as $institution): ?>
                                            <option value="<?php echo $institution['name']; ?>" data-region="<?php echo $institution['region_name']; ?>" data-constituency="<?php echo $institution['constituency_name']; ?>" <?php echo (isset($_POST['institution']) && $_POST['institution'] == $institution['name']) ? 'selected' : ''; ?>><?php echo $institution['name']; ?> (<?php echo $institution['location']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="campus_id" class="form-label">Campus</label>
                                    <select class="form-select" id="campus_id" name="campus_id">
                                        <option value="">Select Campus</option>
                                        <?php foreach ($campuses as $campus): ?>
                                            <option value="<?php echo $campus['id']; ?>" data-region="<?php echo $campus['region_name']; ?>" data-constituency="<?php echo $campus['constituency_name']; ?>" data-institution="<?php echo $campus['institution_name']; ?>" <?php echo (isset($_POST['campus_id']) && $_POST['campus_id'] == $campus['id']) ? 'selected' : ''; ?>><?php echo $campus['name']; ?> (<?php echo $campus['institution_name']; ?>, <?php echo $campus['location']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Academic Information -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department"
                                           value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="program" class="form-label">Program of Study *</label>
                                    <input type="text" class="form-control" id="program" name="program" required
                                           value="<?php echo isset($_POST['program']) ? htmlspecialchars($_POST['program']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="year" class="form-label">Year *</label>
                                    <select class="form-select" id="year" name="year" required>
                                        <option value="">Select Year</option>
                                        <option value="1" <?php echo (isset($_POST['year']) && $_POST['year'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2" <?php echo (isset($_POST['year']) && $_POST['year'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3" <?php echo (isset($_POST['year']) && $_POST['year'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4" <?php echo (isset($_POST['year']) && $_POST['year'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="5" <?php echo (isset($_POST['year']) && $_POST['year'] == '5') ? 'selected' : ''; ?>>5th Year</option>
                                        <option value="6" <?php echo (isset($_POST['year']) && $_POST['year'] == '6') ? 'selected' : ''; ?>>6th Year</option>
                                        <option value="Graduate" <?php echo (isset($_POST['year']) && $_POST['year'] == 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="student_id" class="form-label">Student ID *</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" required
                                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                                </div>
                            </div>

                            <!-- Political Information -->
                            <div class="mb-3">
                                <label for="npp_position" class="form-label">NPP Position (if any)</label>
                                <input type="text" class="form-control" id="npp_position" name="npp_position"
                                       value="<?php echo isset($_POST['npp_position']) ? htmlspecialchars($_POST['npp_position']) : ''; ?>">
                            </div>

                            <!-- Hails From Information -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="hails_from_region" class="form-label">Region of Origin</label>
                                    <select class="form-select" id="hails_from_region" name="hails_from_region">
                                        <option value="">Select Region</option>
                                        <?php foreach ($regions as $region): ?>
                                            <option value="<?php echo $region['name']; ?>" <?php echo (isset($_POST['hails_from_region']) && $_POST['hails_from_region'] == $region['name']) ? 'selected' : ''; ?>><?php echo $region['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hails_from_constituency" class="form-label">Constituency of Origin</label>
                                    <select class="form-select" id="hails_from_constituency" name="hails_from_constituency">
                                        <option value="">Select Constituency</option>
                                        <?php foreach ($constituencies as $constituency): ?>
                                            <option value="<?php echo $constituency['name']; ?>" data-region="<?php echo $constituency['region_name']; ?>" <?php echo (isset($_POST['hails_from_constituency']) && $_POST['hails_from_constituency'] == $constituency['name']) ? 'selected' : ''; ?>><?php echo $constituency['name']; ?> (<?php echo $constituency['region_name']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p>Already have an account? <a href="login.php">Login here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dynamic constituency and institution loading based on region selection
        document.getElementById('region').addEventListener('change', function() {
            const selectedRegion = this.value;
            const constituencySelect = document.getElementById('constituency');
            const institutionSelect = document.getElementById('institution');
            const campusSelect = document.getElementById('campus_id');
            const constituencies = <?php echo json_encode($constituencies); ?>;
            const institutions = <?php echo json_encode($institutions); ?>;

            // Clear constituency options
            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';

            // Clear institution options
            institutionSelect.innerHTML = '<option value="">Select Institution</option>';

            // Clear campus options (since institution hasn't been selected yet)
            campusSelect.innerHTML = '<option value="">Select Campus</option>';

            // Filter and add constituencies for selected region
            constituencies.forEach(function(constituency) {
                if (selectedRegion === '' || constituency.region_name === selectedRegion) {
                    const option = document.createElement('option');
                    option.value = constituency.name;
                    option.textContent = constituency.name + ' (' + constituency.region_name + ')';
                    option.setAttribute('data-region', constituency.region_name);

                    // Preserve selected value if it exists
                    if ('<?php echo isset($_POST['constituency']) ? $_POST['constituency'] : ''; ?>' === constituency.name) {
                        option.selected = true;
                    }

                    constituencySelect.appendChild(option);
                }
            });

            // Filter and add institutions for selected region
            institutions.forEach(function(institution) {
                if (selectedRegion === '' || institution.region_name === selectedRegion) {
                    const option = document.createElement('option');
                    option.value = institution.name;
                    option.textContent = institution.name + ' (' + institution.location + ')';
                    option.setAttribute('data-region', institution.region_name);
                    option.setAttribute('data-constituency', institution.constituency_name);

                    // Preserve selected value if it exists
                    if ('<?php echo isset($_POST['institution']) ? $_POST['institution'] : ''; ?>' === institution.name) {
                        option.selected = true;
                    }

                    institutionSelect.appendChild(option);
                }
            });
        });

        // Dynamic institution loading based on constituency selection
        document.getElementById('constituency').addEventListener('change', function() {
            const selectedConstituency = this.value;
            const selectedRegion = document.getElementById('region').value;
            const institutionSelect = document.getElementById('institution');
            const campusSelect = document.getElementById('campus_id');
            const institutions = <?php echo json_encode($institutions); ?>;

            // Clear institution options
            institutionSelect.innerHTML = '<option value="">Select Institution</option>';

            // Clear campus options (since institution hasn't been selected yet)
            campusSelect.innerHTML = '<option value="">Select Campus</option>';

            // Filter and add institutions for selected region and constituency
            institutions.forEach(function(institution) {
                if ((selectedRegion === '' || institution.region_name === selectedRegion) &&
                    (selectedConstituency === '' || institution.constituency_name === selectedConstituency)) {
                    const option = document.createElement('option');
                    option.value = institution.name;
                    option.textContent = institution.name + ' (' + institution.location + ')';
                    option.setAttribute('data-region', institution.region_name);
                    option.setAttribute('data-constituency', institution.constituency_name);

                    // Preserve selected value if it exists
                    if ('<?php echo isset($_POST['institution']) ? $_POST['institution'] : ''; ?>' === institution.name) {
                        option.selected = true;
                    }

                    institutionSelect.appendChild(option);
                }
            });
        });

        // Dynamic campus loading based on institution selection
        document.getElementById('institution').addEventListener('change', function() {
            const selectedInstitution = this.value;
            const selectedRegion = document.getElementById('region').value;
            const selectedConstituency = document.getElementById('constituency').value;
            const campusSelect = document.getElementById('campus_id');
            const campuses = <?php echo json_encode($campuses); ?>;

            // Clear campus options
            campusSelect.innerHTML = '<option value="">Select Campus</option>';

            // Filter and add campuses for selected region, constituency, and institution
            campuses.forEach(function(campus) {
                if ((selectedRegion === '' || campus.region_name === selectedRegion) &&
                    (selectedConstituency === '' || campus.constituency_name === selectedConstituency) &&
                    (selectedInstitution === '' || campus.institution_name === selectedInstitution)) {
                    const option = document.createElement('option');
                    option.value = campus.id;
                    option.textContent = campus.name + ' (' + campus.institution_name + ', ' + campus.location + ')';
                    option.setAttribute('data-region', campus.region_name);
                    option.setAttribute('data-constituency', campus.constituency_name);
                    option.setAttribute('data-institution', campus.institution_name);

                    // Preserve selected value if it exists
                    if ('<?php echo isset($_POST['campus_id']) ? $_POST['campus_id'] : ''; ?>' === campus.id.toString()) {
                        option.selected = true;
                    }

                    campusSelect.appendChild(option);
                }
            });
        });

        // Dynamic hails from constituency loading based on hails from region selection
        document.getElementById('hails_from_region').addEventListener('change', function() {
            const selectedRegion = this.value;
            const constituencySelect = document.getElementById('hails_from_constituency');
            const constituencies = <?php echo json_encode($constituencies); ?>;

            // Clear constituency options
            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';

            // Filter and add constituencies for selected region
            constituencies.forEach(function(constituency) {
                if (selectedRegion === '' || constituency.region_name === selectedRegion) {
                    const option = document.createElement('option');
                    option.value = constituency.name;
                    option.textContent = constituency.name + ' (' + constituency.region_name + ')';
                    option.setAttribute('data-region', constituency.region_name);

                    // Preserve selected value if it exists
                    if ('<?php echo isset($_POST['hails_from_constituency']) ? $_POST['hails_from_constituency'] : ''; ?>' === constituency.name) {
                        option.selected = true;
                    }

                    constituencySelect.appendChild(option);
                }
            });
        });

        // Trigger change event on page load to populate hails from constituencies if hails from region is pre-selected
        document.addEventListener('DOMContentLoaded', function() {
            const regionSelect = document.getElementById('region');
            if (regionSelect.value) {
                regionSelect.dispatchEvent(new Event('change'));
            }

            const hailsFromRegionSelect = document.getElementById('hails_from_region');
            if (hailsFromRegionSelect.value) {
                hailsFromRegionSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/coreui_layout_end.php';
?>