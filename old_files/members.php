<?php
/**
 * TESCON Ghana - Members Directory
 */
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();
requireLogin();

$pageTitle = "Members Directory";
$useDataTables = true;
$breadcrumbs = [
    ['title' => 'Members', 'url' => '#'],
    ['title' => 'Directory', 'url' => '#']
];

// Get institutions, regions and constituencies for dropdowns
$institutions = [];
$regions = [];
$constituencies = [];
try {
    $stmt = $pdo->query("SELECT * FROM institutions ORDER BY name");
    $institutions = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM regions ORDER BY name");
    $regions = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT c.*, r.name as region_name
        FROM constituencies c
        JOIN regions r ON c.region_id = r.id
        ORDER BY r.name, c.name
    ");
    $constituencies = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get all members with campus information and payment status
$currentYear = date('Y');
$members = [];
try {
    // Base query
    $query = "
        SELECT m.*, c.name as campus_name, i.name as campus_institution,
               CASE WHEN ce.member_id IS NOT NULL THEN ce.position ELSE NULL END as campus_executive_position,
               CASE WHEN p.id IS NOT NULL THEN 'Paid' ELSE 'Unpaid' END as payment_status,
               u.email, u.role, u.status as user_status, u.last_login
        FROM members m
        JOIN users u ON m.user_id = u.id
        LEFT JOIN campuses c ON m.campus_id = c.id
        LEFT JOIN institutions i ON c.institution_id = i.id
        LEFT JOIN campus_executives ce ON m.id = ce.member_id
        LEFT JOIN payments p ON m.id = p.member_id
            AND p.status = 'completed'
            AND p.dues_id = (SELECT id FROM dues WHERE year = ? LIMIT 1)
    ";

    // Filter by campus for regular members
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Member') {
        // Get current member's campus
        $stmt = $pdo->prepare("SELECT campus_id FROM members WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentMember = $stmt->fetch();
        
        if ($currentMember && $currentMember['campus_id']) {
            $query .= " WHERE m.campus_id = ?";
            $params = [$currentYear, $currentMember['campus_id']];
        } else {
            $params = [$currentYear];
        }
    } else {
        $params = [$currentYear];
    }

    $query .= " ORDER BY m.fullname ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle member creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_member'])) {
    if (!hasRole(['Executive', 'Patron', 'Admin'])) {
        $error = 'Unauthorized access';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
        logSecurityEvent('csrf_failure', ['page' => 'members', 'action' => 'create']);
    } else {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $student_id = trim($_POST['student_id']);
    $date_of_birth = $_POST['date_of_birth'];
    $institution = trim($_POST['institution']);
    $department = trim($_POST['department']);
    $program = trim($_POST['program']);
    $year = trim($_POST['year']);
    $role = trim($_POST['role']);
    $region = trim($_POST['region']);
    $constituency = trim($_POST['constituency']);
    $npp_position = trim($_POST['npp_position']);

    if (empty($fullname) || empty($email) || empty($phone) || empty($student_id)) {
        $error = 'Required fields must be filled';
    } else {
        try {
            // Check if email or student_id already exists
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? OR student_id = ?");
            $stmt->execute([$email, $student_id]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email or Student ID already exists';
            } else {
                // Handle photo upload
                $photo_filename = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploader = new FileUpload('uploads/photos/');
                    $upload_result = $uploader->upload($_FILES['photo'], 'member_');
                    if ($upload_result['success']) {
                        $photo_filename = $upload_result['filename'];
                    }
                }

                // Start transaction
                $pdo->beginTransaction();

                try {
                    // Hash password (default password)
                    $hashed_password = password_hash('password', PASSWORD_DEFAULT);

                    // Create user account
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status, email_verified, phone_verified) VALUES (?, ?, ?, 'Active', 1, 0)");
                    $stmt->execute([$email, $hashed_password, $role]);
                    $userId = $pdo->lastInsertId();

                    // Create member record
                    $stmt = $pdo->prepare("INSERT INTO members (user_id, fullname, phone, date_of_birth, photo, institution, department, program, year, student_id, position, region, constituency, npp_position, membership_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                    $stmt->execute([$userId, $fullname, $phone, $date_of_birth, $photo_filename, $institution, $department, $program, $year, $student_id, $role, $region, $constituency, $npp_position]);

                    $pdo->commit();
                    $success = 'Member created successfully';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
    }
}

// Handle member editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_member']) && in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])) {
    $memberId = $_POST['member_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $student_id = trim($_POST['student_id']);
    $date_of_birth = $_POST['date_of_birth'];
    $institution = trim($_POST['institution']);
    $department = trim($_POST['department']);
    $program = trim($_POST['program']);
    $year = trim($_POST['year']);
    $role = trim($_POST['role']);
    $region = trim($_POST['region']);
    $constituency = trim($_POST['constituency']);
    $npp_position = trim($_POST['npp_position']);

    if (empty($fullname) || empty($email) || empty($phone) || empty($student_id)) {
        $error = 'Required fields must be filled';
    } else {
        try {
            // Check if email or student_id already exists (excluding current member)
            $stmt = $pdo->prepare("SELECT id FROM members WHERE (email = ? OR student_id = ?) AND id != ?");
            $stmt->execute([$email, $student_id, $memberId]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email or Student ID already exists';
            } else {
                // Handle photo upload
                $photo_filename = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploader = new FileUpload('uploads/photos/');
                    $upload_result = $uploader->upload($_FILES['photo'], 'member_');
                    if ($upload_result['success']) {
                        $photo_filename = $upload_result['filename'];
                    }
                }

                // Start transaction
                $pdo->beginTransaction();

                try {
                    // Update user account
                    $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = (SELECT user_id FROM members WHERE id = ?)");
                    $stmt->execute([$email, $role, $memberId]);

                    // Update member record
                    $updateFields = "fullname = ?, phone = ?, date_of_birth = ?, institution = ?, department = ?, program = ?, year = ?, student_id = ?, position = ?, region = ?, constituency = ?, npp_position = ?";
                    $params = [$fullname, $phone, $date_of_birth, $institution, $department, $program, $year, $student_id, $role, $region, $constituency, $npp_position];

                    if ($photo_filename) {
                        $updateFields .= ", photo = ?";
                        $params[] = $photo_filename;
                    }

                    $params[] = $memberId;

                    $stmt = $pdo->prepare("UPDATE members SET {$updateFields} WHERE id = ?");
                    $stmt->execute($params);

                    $pdo->commit();
                    $success = 'Member updated successfully';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['delete_member']) && isset($_GET['csrf_token'])) {
    if (!hasRole(['Executive', 'Patron', 'Admin'])) {
        $error = 'Unauthorized access';
    } elseif (!verifyCSRFToken($_GET['csrf_token'])) {
        $error = 'Invalid request';
        logSecurityEvent('csrf_failure', ['page' => 'members', 'action' => 'delete']);
    } else {
    $memberId = $_GET['delete_member'];
    try {
        // Get user_id first
        $stmt = $pdo->prepare("SELECT user_id FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();

        if ($member) {
            // Delete member record (this will cascade to user due to foreign key)
            $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
            if ($stmt->execute([$memberId])) {
                $success = 'Member deleted successfully';
            } else {
                $error = 'Failed to delete member';
            }
        } else {
            $error = 'Member not found';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
    }
}

?>

<?php
include 'includes/coreui_layout_start.php';
?>


<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-users me-2"></i>Members Directory</h2>
            <?php if ($_SESSION['role'] === 'Member'): ?>
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Showing members from your campus only</small>
            <?php endif; ?>
        </div>
        <div>
            <?php if (hasRole(['Executive', 'Patron', 'Admin'])): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMemberModal">
                    <i class="fas fa-user-plus me-2"></i>Add Member
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>All Members</h5>
        </div>
        <div class="card-body">
                <div class="table-responsive">
                    <table id="membersTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Photo</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Date of Birth</th>
                                <th>Institution</th>
                                <th>Department</th>
                                <th>Student ID</th>
                                <th>Campus</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Role</th>
                                <th>Campus Role</th>
                                <th>Payment Status</th>
                                <th>NPP Position</th>
                                <th>Region</th>
                                <th>Constituency</th>
                                <th>Account Status</th>
                                <th>Last Login</th>
                                <?php if (in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $index => $member): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php if ($member['photo']): ?>
                                            <img src="uploads/photos/<?php echo htmlspecialchars($member['photo']); ?>"
                                                 alt="Profile Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center"
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                    <td><?php echo $member['date_of_birth'] ? date('d/m/Y', strtotime($member['date_of_birth'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($member['institution']); ?></td>
                                    <td><?php echo htmlspecialchars($member['department'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($member['student_id'] ?: '-'); ?></td>
                                    <td><?php echo $member['campus_name'] ? htmlspecialchars($member['campus_institution'] . ' - ' . $member['campus_name']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($member['program']); ?></td>
                                    <td>
                                        <?php
                                        $year = $member['year'];
                                        echo is_numeric($year) ? $year . (($year == 1) ? 'st' : (($year == 2) ? 'nd' : (($year == 3) ? 'rd' : 'th'))) . ' Year' : $year;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $member['role'] == 'Executive' ? 'primary' : ($member['role'] == 'Patron' ? 'success' : ($member['role'] == 'Admin' ? 'danger' : 'secondary')); ?>">
                                            <?php echo htmlspecialchars($member['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($member['campus_executive_position']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($member['campus_executive_position']); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $member['payment_status'] == 'Paid' ? 'success' : 'warning'; ?>">
                                            <i class="fas fa-<?php echo $member['payment_status'] == 'Paid' ? 'check-circle' : 'clock'; ?>"></i>
                                            <?php echo $member['payment_status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['npp_position'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($member['region'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($member['constituency'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $member['user_status'] == 'Active' ? 'success' : ($member['user_status'] == 'Inactive' ? 'warning' : 'danger'); ?>">
                                            <?php echo htmlspecialchars($member['user_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $member['last_login'] ? date('d/m/Y H:i', strtotime($member['last_login'])) : 'Never'; ?></td>
                                    <?php if (in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])): ?>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editMemberModal"
                                                onclick="editMember(<?php echo $member['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?delete_member=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this member?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Member Modal -->
    <?php if (in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])): ?>
    <div class="modal fade" id="createMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="create_member" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fullname" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label">Student ID *</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="region" class="form-label">Region</label>
                                <select class="form-select" id="region" name="region">
                                    <option value="">Select Region</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['name']; ?>"><?php echo htmlspecialchars($region['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="constituency" class="form-label">Constituency</label>
                                <select class="form-select" id="constituency" name="constituency">
                                    <option value="">Select Constituency</option>
                                    <?php foreach ($constituencies as $constituency): ?>
                                        <option value="<?php echo $constituency['name']; ?>" data-region="<?php echo $constituency['region_name']; ?>"><?php echo htmlspecialchars($constituency['name'] . ' (' . $constituency['region_name'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="institution" class="form-label">Institution *</label>
                            <select class="form-select" id="institution" name="institution" required>
                                <option value="">Select Institution</option>
                                <?php foreach ($institutions as $institution): ?>
                                    <option value="<?php echo $institution['name']; ?>" data-region="<?php echo $institution['region_name']; ?>" data-constituency="<?php echo $institution['constituency_name']; ?>"><?php echo htmlspecialchars($institution['name'] . ' (' . $institution['location'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="program" class="form-label">Program of Study</label>
                                <input type="text" class="form-control" id="program" name="program">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year">
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                    <option value="5">5th Year</option>
                                    <option value="6">6th Year</option>
                                    <option value="Graduate">Graduate</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="Member">Member</option>
                                    <option value="Executive">Executive</option>
                                    <option value="Patron">Patron</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="region" class="form-label">Region</label>
                                <input type="text" class="form-control" id="region" name="region">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="constituency" class="form-label">Constituency</label>
                                <input type="text" class="form-control" id="constituency" name="constituency">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="npp_position" class="form-label">NPP Position</label>
                            <input type="text" class="form-control" id="npp_position" name="npp_position">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Member Modal -->
    <?php if (in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])): ?>
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="edit_member" value="1">
                        <input type="hidden" name="member_id" id="edit_member_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_fullname" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="edit_fullname" name="fullname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_student_id" class="form-label">Student ID *</label>
                                <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="edit_photo" name="photo" accept="image/*">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_region" class="form-label">Region</label>
                                <select class="form-select" id="edit_region" name="region">
                                    <option value="">Select Region</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['name']; ?>"><?php echo htmlspecialchars($region['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_constituency" class="form-label">Constituency</label>
                                <select class="form-select" id="edit_constituency" name="constituency">
                                    <option value="">Select Constituency</option>
                                    <?php foreach ($constituencies as $constituency): ?>
                                        <option value="<?php echo $constituency['name']; ?>" data-region="<?php echo $constituency['region_name']; ?>"><?php echo htmlspecialchars($constituency['name'] . ' (' . $constituency['region_name'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_institution" class="form-label">Institution</label>
                            <select class="form-select" id="edit_institution" name="institution">
                                <option value="">Select Institution</option>
                                <?php foreach ($institutions as $institution): ?>
                                    <option value="<?php echo $institution['name']; ?>" data-region="<?php echo $institution['region_name']; ?>" data-constituency="<?php echo $institution['constituency_name']; ?>"><?php echo htmlspecialchars($institution['name'] . ' (' . $institution['location'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="edit_department" name="department">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_program" class="form-label">Program of Study</label>
                                <input type="text" class="form-control" id="edit_program" name="program">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_year" class="form-label">Year</label>
                                <select class="form-select" id="edit_year" name="year">
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                    <option value="5">5th Year</option>
                                    <option value="6">6th Year</option>
                                    <option value="Graduate">Graduate</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_role" class="form-label">Role</label>
                                <select class="form-select" id="edit_role" name="role">
                                    <option value="Member">Member</option>
                                    <option value="Executive">Executive</option>
                                    <option value="Patron">Patron</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_region" class="form-label">Region</label>
                                <input type="text" class="form-control" id="edit_region" name="region">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_constituency" class="form-label">Constituency</label>
                                <input type="text" class="form-control" id="edit_constituency" name="constituency">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_npp_position" class="form-label">NPP Position</label>
                            <input type="text" class="form-control" id="edit_npp_position" name="npp_position">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#membersTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[1, 'asc']]
            });
        });

        function editMember(memberId) {
            // Fetch member data via AJAX
            fetch(`get_member.php?id=${memberId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const member = data.member;
                        document.getElementById('edit_member_id').value = member.id;
                        document.getElementById('edit_fullname').value = member.fullname || '';
                        document.getElementById('edit_email').value = member.email || '';
                        document.getElementById('edit_phone').value = member.phone || '';
                        document.getElementById('edit_student_id').value = member.student_id || '';
                        document.getElementById('edit_date_of_birth').value = member.date_of_birth || '';
                        document.getElementById('edit_institution').value = member.institution || '';
                        document.getElementById('edit_department').value = member.department || '';
                        document.getElementById('edit_program').value = member.program || '';
                        document.getElementById('edit_year').value = member.year || '';
                        document.getElementById('edit_role').value = member.role || 'Member';
                        document.getElementById('edit_region').value = member.region || '';
                        document.getElementById('edit_constituency').value = member.constituency || '';
                        document.getElementById('edit_npp_position').value = member.npp_position || '';
                    } else {
                        alert('Error loading member data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading member data');
                });
        }

        // Dynamic filtering for create modal
        document.getElementById('region').addEventListener('change', function() {
            filterConstituenciesAndInstitutions('region', 'constituency', 'institution');
        });

        document.getElementById('constituency').addEventListener('change', function() {
            filterInstitutions('region', 'constituency', 'institution');
        });

        // Dynamic filtering for edit modal
        document.getElementById('edit_region').addEventListener('change', function() {
            filterConstituenciesAndInstitutions('edit_region', 'edit_constituency', 'edit_institution');
        });

        document.getElementById('edit_constituency').addEventListener('change', function() {
            filterInstitutions('edit_region', 'edit_constituency', 'edit_institution');
        });

        function filterConstituenciesAndInstitutions(regionId, constituencyId, institutionId) {
            const selectedRegion = document.getElementById(regionId).value;
            const constituencySelect = document.getElementById(constituencyId);
            const institutionSelect = document.getElementById(institutionId);
            const constituencies = <?php echo json_encode($constituencies); ?>;
            const institutions = <?php echo json_encode($institutions); ?>;

            // Clear and filter constituencies
            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
            constituencies.forEach(function(constituency) {
                if (selectedRegion === '' || constituency.region_name === selectedRegion) {
                    const option = document.createElement('option');
                    option.value = constituency.name;
                    option.textContent = constituency.name + ' (' + constituency.region_name + ')';
                    constituencySelect.appendChild(option);
                }
            });

            // Clear and filter institutions
            institutionSelect.innerHTML = '<option value="">Select Institution</option>';
            institutions.forEach(function(institution) {
                if (selectedRegion === '' || institution.region_name === selectedRegion) {
                    const option = document.createElement('option');
                    option.value = institution.name;
                    option.textContent = institution.name + ' (' + institution.location + ')';
                    institutionSelect.appendChild(option);
                }
            });
        }

        function filterInstitutions(regionId, constituencyId, institutionId) {
            const selectedRegion = document.getElementById(regionId).value;
            const selectedConstituency = document.getElementById(constituencyId).value;
            const institutionSelect = document.getElementById(institutionId);
            const institutions = <?php echo json_encode($institutions); ?>;

            institutionSelect.innerHTML = '<option value="">Select Institution</option>';
            institutions.forEach(function(institution) {
                if ((selectedRegion === '' || institution.region_name === selectedRegion) &&
                    (selectedConstituency === '' || institution.constituency_name === selectedConstituency)) {
                    const option = document.createElement('option');
                    option.value = institution.name;
                    option.textContent = institution.name + ' (' + institution.location + ')';
                    institutionSelect.appendChild(option);
                }
            });
        }
    </script>
</div>

<?php
include 'includes/coreui_layout_end.php';
?>

