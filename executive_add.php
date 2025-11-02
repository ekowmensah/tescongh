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

$pageTitle = 'Promote Member to Executive';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);
$position = new Position($db);

// Get data for dropdowns
$executivePositions = $position->getExecutivePositions();

// Get all institutions for executive assignment
$institutionsQuery = "SELECT DISTINCT i.id, i.name 
                      FROM institutions i 
                      INNER JOIN campuses c ON i.id = c.institution_id 
                      ORDER BY i.name ASC";
$institutionsStmt = $db->query($institutionsQuery);
$institutions = $institutionsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
    $position_id = !empty($_POST['position_id']) ? (int)$_POST['position_id'] : null;
    $campus_id = !empty($_POST['campus_id']) ? (int)$_POST['campus_id'] : null;
    
    if (!$member_id) {
        setFlashMessage('danger', 'Please select a member to promote');
        redirect('executive_add.php');
    }
    
    if (!$position_id) {
        setFlashMessage('danger', 'Please select an executive position');
        redirect('executive_add.php');
    }
    
    try {
        $db->beginTransaction();
        
        // Get member details
        $memberData = $member->getById($member_id);
        if (!$memberData) {
            throw new Exception('Member not found');
        }
        
        // Update member position to Executive
        $updateQuery = "UPDATE members SET position = 'Executive' WHERE id = :member_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':member_id', $member_id);
        $updateStmt->execute();
        
        // Update user role to Executive
        $updateUserQuery = "UPDATE users SET role = 'Executive' WHERE id = :user_id";
        $updateUserStmt = $db->prepare($updateUserQuery);
        $updateUserStmt->bindParam(':user_id', $memberData['user_id']);
        $updateUserStmt->execute();
        
        // Assign executive position to campus
        if ($campus_id) {
            // Check if already assigned to this campus
            $checkQuery = "SELECT id FROM campus_executives WHERE campus_id = :campus_id AND member_id = :member_id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':campus_id', $campus_id);
            $checkStmt->bindParam(':member_id', $member_id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Update existing assignment
                $assignQuery = "UPDATE campus_executives SET position_id = :position_id WHERE campus_id = :campus_id AND member_id = :member_id";
            } else {
                // Create new assignment
                $assignQuery = "INSERT INTO campus_executives (campus_id, member_id, position_id) VALUES (:campus_id, :member_id, :position_id)";
            }
            
            $assignStmt = $db->prepare($assignQuery);
            $assignStmt->bindParam(':campus_id', $campus_id);
            $assignStmt->bindParam(':member_id', $member_id);
            $assignStmt->bindParam(':position_id', $position_id);
            $assignStmt->execute();
        }
        
        $db->commit();
        setFlashMessage('success', 'Member successfully promoted to Executive');
        redirect('members.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Executive promotion error: ' . $e->getMessage());
        setFlashMessage('danger', 'Failed to promote member: ' . $e->getMessage());
        redirect('executive_add.php');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Promote Member to Executive</h2>
        <p class="text-muted">Select an existing member and assign them an executive position</p>
        <a href="members.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

<form method="POST" action="" id="promoteForm">
    <div class="row">
        <div class="col-md-8">
            <!-- Select Institution and Campus -->
            <div class="card">
                <div class="card-header">
                    <strong>Step 1: Select Institution & Campus</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="cil-info"></i> <strong>Note:</strong> Select the institution and campus first, then choose a member from that campus.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Institution <span class="text-danger">*</span></label>
                        <select class="form-select" id="institutionSelect" required>
                            <option value="">Select Institution</option>
                            <?php foreach ($institutions as $inst): ?>
                                <option value="<?php echo $inst['id']; ?>">
                                    <?php echo htmlspecialchars($inst['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select the institution where executive will serve</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Campus <span class="text-danger">*</span></label>
                        <select class="form-select" name="campus_id" id="campusSelect" required>
                            <option value="">Select Institution First</option>
                        </select>
                        <small class="text-muted">Select the specific campus</small>
                    </div>
                </div>
            </div>
            
            <!-- Select Member -->
            <div class="card" id="memberSelectionCard" style="display: none;">
                <div class="card-header">
                    <strong>Step 2: Select Member to Promote</strong>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="cil-check-circle"></i> <strong>Campus Selected:</strong> <span id="selectedCampusName"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Search Member</label>
                        <input type="text" class="form-control" id="memberSearch" placeholder="Type to search by name, student ID, or phone...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Member <span class="text-danger">*</span></label>
                        <select class="form-select" name="member_id" id="memberSelect" required size="10">
                            <option value="">-- Loading members... --</option>
                        </select>
                        <small class="text-muted" id="memberCount">Select campus to load members</small>
                    </div>
                </div>
            </div>
            
            <!-- Member Details Preview -->
            <div class="card" id="memberDetailsCard" style="display: none;">
                <div class="card-header">
                    <strong>Selected Member Details</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Full Name:</strong>
                            <div id="previewFullname" class="text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Student ID:</strong>
                            <div id="previewStudentId" class="text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong>
                            <div id="previewEmail" class="text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Phone:</strong>
                            <div id="previewPhone" class="text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Institution:</strong>
                            <div id="previewInstitution" class="text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Campus:</strong>
                            <div id="previewCampus" class="text-muted">-</div>
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
                        <strong>Step 3: Executive Position</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Executive Position <span class="text-danger">*</span></label>
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
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="cil-check"></i> Promote to Executive
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
// Member search functionality
const memberSearch = document.getElementById('memberSearch');
const memberSelect = document.getElementById('memberSelect');
const memberDetailsCard = document.getElementById('memberDetailsCard');
const campusIdInput = document.getElementById('campusIdInput');
const campusDisplay = document.getElementById('campusDisplay');

memberSearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const options = memberSelect.options;
    
    for (let i = 1; i < options.length; i++) {
        const option = options[i];
        const fullname = option.getAttribute('data-fullname').toLowerCase();
        const studentId = option.getAttribute('data-student-id').toLowerCase();
        const phone = option.getAttribute('data-phone').toLowerCase();
        
        if (fullname.includes(searchTerm) || studentId.includes(searchTerm) || phone.includes(searchTerm)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
});

// Show member details when selected
memberSelect.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    
    if (this.value) {
        // Show details card
        memberDetailsCard.style.display = 'block';
        
        // Populate preview fields
        document.getElementById('previewFullname').textContent = selectedOption.getAttribute('data-fullname');
        document.getElementById('previewStudentId').textContent = selectedOption.getAttribute('data-student-id');
        document.getElementById('previewEmail').textContent = selectedOption.getAttribute('data-email');
        document.getElementById('previewPhone').textContent = selectedOption.getAttribute('data-phone');
        document.getElementById('previewInstitution').textContent = selectedOption.getAttribute('data-institution');
        document.getElementById('previewCampus').textContent = selectedOption.getAttribute('data-campus');
    } else {
        // Hide details card
        memberDetailsCard.style.display = 'none';
    }
});

// Load campuses when institution is selected
document.getElementById('institutionSelect').addEventListener('change', function() {
    const institutionId = this.value;
    const campusSelect = document.getElementById('campusSelect');
    const memberSelectionCard = document.getElementById('memberSelectionCard');
    const memberDetailsCard = document.getElementById('memberDetailsCard');
    
    // Hide member selection when institution changes
    memberSelectionCard.style.display = 'none';
    memberDetailsCard.style.display = 'none';
    
    if (institutionId) {
        // Show loading message
        campusSelect.innerHTML = '<option value="">Loading campuses...</option>';
        
        // Fetch campuses for selected institution
        fetch('ajax/get_campuses_by_institution.php?institution_id=' + institutionId)
            .then(response => response.json())
            .then(data => {
                campusSelect.innerHTML = '<option value="">Select Campus</option>';
                
                if (data.length > 0) {
                    data.forEach(campus => {
                        const option = document.createElement('option');
                        option.value = campus.id;
                        option.setAttribute('data-name', campus.name + ' - ' + campus.location);
                        option.textContent = campus.name + ' - ' + campus.location;
                        campusSelect.appendChild(option);
                    });
                } else {
                    campusSelect.innerHTML = '<option value="">No campuses found for this institution</option>';
                }
            })
            .catch(error => {
                console.error('Error loading campuses:', error);
                campusSelect.innerHTML = '<option value="">Error loading campuses</option>';
            });
    } else {
        campusSelect.innerHTML = '<option value="">Select Institution First</option>';
    }
});

// Load members when campus is selected
document.getElementById('campusSelect').addEventListener('change', function() {
    const campusId = this.value;
    const selectedOption = this.options[this.selectedIndex];
    const campusName = selectedOption.getAttribute('data-name');
    const memberSelect = document.getElementById('memberSelect');
    const memberSelectionCard = document.getElementById('memberSelectionCard');
    const memberDetailsCard = document.getElementById('memberDetailsCard');
    const selectedCampusName = document.getElementById('selectedCampusName');
    const memberCount = document.getElementById('memberCount');
    
    // Hide member details when campus changes
    memberDetailsCard.style.display = 'none';
    
    if (campusId) {
        // Show member selection card
        memberSelectionCard.style.display = 'block';
        selectedCampusName.textContent = campusName;
        
        // Show loading message
        memberSelect.innerHTML = '<option value="">Loading members...</option>';
        memberCount.textContent = 'Loading...';
        
        // Fetch members for selected campus
        fetch('ajax/get_members_by_campus.php?campus_id=' + campusId)
            .then(response => response.json())
            .then(data => {
                memberSelect.innerHTML = '<option value="">-- Select a member --</option>';
                
                if (data.length > 0) {
                    data.forEach(member => {
                        const option = document.createElement('option');
                        option.value = member.id;
                        option.setAttribute('data-fullname', member.fullname);
                        option.setAttribute('data-student-id', member.student_id);
                        option.setAttribute('data-phone', member.phone);
                        option.setAttribute('data-email', member.email);
                        option.setAttribute('data-institution', member.institution_name);
                        option.setAttribute('data-campus', member.campus_name);
                        option.textContent = member.fullname + ' - ' + member.student_id;
                        memberSelect.appendChild(option);
                    });
                    memberCount.textContent = 'Showing ' + data.length + ' available members from this campus';
                } else {
                    memberSelect.innerHTML = '<option value="">No eligible members found in this campus</option>';
                    memberCount.textContent = 'No members available';
                }
            })
            .catch(error => {
                console.error('Error loading members:', error);
                memberSelect.innerHTML = '<option value="">Error loading members</option>';
                memberCount.textContent = 'Error loading members';
            });
    } else {
        memberSelectionCard.style.display = 'none';
    }
});

// Form validation
document.getElementById('promoteForm').addEventListener('submit', function(e) {
    const memberId = memberSelect.value;
    const positionId = document.querySelector('select[name="position_id"]').value;
    const institutionId = document.getElementById('institutionSelect').value;
    const campusId = document.getElementById('campusSelect').value;
    
    let errors = [];
    
    if (!memberId) {
        errors.push('Please select a member to promote');
    }
    
    if (!positionId) {
        errors.push('Please select an executive position');
    }
    
    if (!institutionId) {
        errors.push('Please select an institution');
    }
    
    if (!campusId) {
        errors.push('Please select a campus');
    }
    
    if (errors.length > 0) {
        e.preventDefault();
        alert(errors.join('\n'));
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
