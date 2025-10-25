<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';
require_once 'classes/Position.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Assign Executive to Campus';

$database = new Database();
$db = $database->getConnection();

$campusObj = new Campus($db);
$positionObj = new Position($db);

// Get campus ID
if (!isset($_GET['campus_id'])) {
    setFlashMessage('danger', 'Campus ID not provided');
    redirect('campuses.php');
}

$campusId = (int)$_GET['campus_id'];
$campusData = $campusObj->getById($campusId);

if (!$campusData) {
    setFlashMessage('danger', 'Campus not found');
    redirect('campuses.php');
}

// Get executive positions
$executivePositions = $positionObj->getExecutivePositions();

// Get members from this campus (excluding Patrons)
$membersQuery = "SELECT m.id, m.fullname, m.student_id, m.phone, u.email, m.position
                 FROM members m
                 INNER JOIN users u ON m.user_id = u.id
                 WHERE m.campus_id = :campus_id 
                   AND m.position != 'Patron'
                 ORDER BY m.fullname ASC";
$membersStmt = $db->prepare($membersQuery);
$membersStmt->bindParam(':campus_id', $campusId);
$membersStmt->execute();
$allCampusMembers = $membersStmt->fetchAll();

// Filter out members who already have an executive position at THIS campus
$availableMembers = [];
foreach ($allCampusMembers as $member) {
    $checkQuery = "SELECT id FROM campus_executives 
                   WHERE campus_id = :campus_id 
                     AND member_id = :member_id 
                     AND is_current = 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':campus_id', $campusId);
    $checkStmt->bindParam(':member_id', $member['id']);
    $checkStmt->execute();
    
    // Only add if they don't have a position at this campus yet
    if ($checkStmt->rowCount() == 0) {
        $availableMembers[] = $member;
    }
}

// Get currently assigned executives
$currentExecsQuery = "SELECT 
                        ce.id as assignment_id,
                        m.id as member_id,
                        m.fullname,
                        m.phone,
                        u.email,
                        p.name as position_name,
                        p.level as position_level,
                        ce.appointed_at
                      FROM campus_executives ce
                      INNER JOIN members m ON ce.member_id = m.id
                      INNER JOIN users u ON m.user_id = u.id
                      INNER JOIN positions p ON ce.position_id = p.id
                      WHERE ce.campus_id = :campus_id AND ce.is_current = 1
                      ORDER BY p.level ASC";
$currentExecsStmt = $db->prepare($currentExecsQuery);
$currentExecsStmt->bindParam(':campus_id', $campusId);
$currentExecsStmt->execute();
$currentExecutives = $currentExecsStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = (int)$_POST['member_id'];
    $positionId = (int)$_POST['position_id'];
    
    // Check if position is already filled
    $checkQuery = "SELECT id FROM campus_executives 
                   WHERE campus_id = :campus_id 
                     AND position_id = :position_id 
                     AND is_current = 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':campus_id', $campusId);
    $checkStmt->bindParam(':position_id', $positionId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        setFlashMessage('danger', 'This position is already filled. Please remove the current executive first.');
    } else {
        // Assign executive
        $insertQuery = "INSERT INTO campus_executives (campus_id, member_id, position_id, is_current) 
                        VALUES (:campus_id, :member_id, :position_id, 1)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':campus_id', $campusId);
        $insertStmt->bindParam(':member_id', $memberId);
        $insertStmt->bindParam(':position_id', $positionId);
        
        if ($insertStmt->execute()) {
            // Update member position to Executive
            $updateMemberQuery = "UPDATE members SET position = 'Executive' WHERE id = :member_id";
            $updateMemberStmt = $db->prepare($updateMemberQuery);
            $updateMemberStmt->bindParam(':member_id', $memberId);
            $updateMemberStmt->execute();
            
            // Update user role to Executive
            $updateUserQuery = "UPDATE users SET role = 'Executive' 
                                WHERE id = (SELECT user_id FROM members WHERE id = :member_id)";
            $updateUserStmt = $db->prepare($updateUserQuery);
            $updateUserStmt->bindParam(':member_id', $memberId);
            $updateUserStmt->execute();
            
            setFlashMessage('success', 'Executive assigned successfully');
            redirect('campus_assign_executive.php?campus_id=' . $campusId);
        } else {
            setFlashMessage('danger', 'Failed to assign executive');
        }
    }
}

// Handle removal
if (isset($_GET['remove'])) {
    $assignmentId = (int)$_GET['remove'];
    
    // Get member_id before deleting
    $getMemberQuery = "SELECT member_id FROM campus_executives WHERE id = :id";
    $getMemberStmt = $db->prepare($getMemberQuery);
    $getMemberStmt->bindParam(':id', $assignmentId);
    $getMemberStmt->execute();
    $assignment = $getMemberStmt->fetch();
    
    if ($assignment) {
        // Delete assignment
        $deleteQuery = "DELETE FROM campus_executives WHERE id = :id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':id', $assignmentId);
        
        if ($deleteStmt->execute()) {
            // Check if member has other executive positions
            $checkOtherQuery = "SELECT COUNT(*) as count FROM campus_executives 
                                WHERE member_id = :member_id AND is_current = 1";
            $checkOtherStmt = $db->prepare($checkOtherQuery);
            $checkOtherStmt->bindParam(':member_id', $assignment['member_id']);
            $checkOtherStmt->execute();
            $otherPositions = $checkOtherStmt->fetch();
            
            // If no other executive positions, revert to Member
            if ($otherPositions['count'] == 0) {
                $updateMemberQuery = "UPDATE members SET position = 'Member' WHERE id = :member_id";
                $updateMemberStmt = $db->prepare($updateMemberQuery);
                $updateMemberStmt->bindParam(':member_id', $assignment['member_id']);
                $updateMemberStmt->execute();
                
                $updateUserQuery = "UPDATE users SET role = 'Member' 
                                    WHERE id = (SELECT user_id FROM members WHERE id = :member_id)";
                $updateUserStmt = $db->prepare($updateUserQuery);
                $updateUserStmt->bindParam(':member_id', $assignment['member_id']);
                $updateUserStmt->execute();
            }
            
            setFlashMessage('success', 'Executive removed from position');
            redirect('campus_assign_executive.php?campus_id=' . $campusId);
        } else {
            setFlashMessage('danger', 'Failed to remove executive');
        }
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Assign Executive</h2>
        <p class="text-muted">
            <?php echo htmlspecialchars($campusData['institution_name']); ?> - 
            <?php echo htmlspecialchars($campusData['name']); ?>
        </p>
    </div>
    <div class="col-md-6 text-end">
        <a href="campuses.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Campuses
        </a>
    </div>
</div>

<div class="row">
    <!-- Assign New Executive -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <strong>Assign New Executive</strong>
            </div>
            <div class="card-body">
                <?php if (empty($availableMembers)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> No available members to assign. All members from this campus are already executives.
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Select Member <span class="text-danger">*</span></label>
                            <select class="form-select" name="member_id" required>
                                <option value="">Choose a member...</option>
                                <?php foreach ($availableMembers as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['fullname']); ?> 
                                        (<?php echo htmlspecialchars($member['student_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Position <span class="text-danger">*</span></label>
                            <select class="form-select" name="position_id" required>
                                <option value="">Choose a position...</option>
                                <?php foreach ($executivePositions as $position): ?>
                                    <?php
                                    // Check if position is already filled
                                    $filled = false;
                                    foreach ($currentExecutives as $exec) {
                                        if ($exec['position_name'] == $position['name']) {
                                            $filled = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?php echo $position['id']; ?>" <?php echo $filled ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($position['name']); ?>
                                        <?php echo $filled ? '(Filled)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="cil-check"></i> Assign Executive
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Current Executives -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Current Executives</strong>
                <span class="badge bg-light text-dark ms-2"><?php echo count($currentExecutives); ?>/11</span>
            </div>
            <div class="card-body">
                <?php if (empty($currentExecutives)): ?>
                    <div class="alert alert-warning">
                        <i class="cil-warning"></i> No executives assigned yet.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($currentExecutives as $exec): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($exec['position_name']); ?></span>
                                        </h6>
                                        <p class="mb-1"><strong><?php echo htmlspecialchars($exec['fullname']); ?></strong></p>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($exec['phone']); ?> | 
                                            <?php echo htmlspecialchars($exec['email']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            Appointed: <?php echo date('M d, Y', strtotime($exec['appointed_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="?campus_id=<?php echo $campusId; ?>&remove=<?php echo $exec['assignment_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Remove this executive from their position?')">
                                            <i class="cil-x"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Campus Members -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>All Members from This Campus</strong>
                <span class="badge bg-primary ms-2"><?php echo count($availableMembers) + count($currentExecutives); ?> Total</span>
            </div>
            <div class="card-body">
                <?php
                // Get all members from this campus
                $allMembersQuery = "SELECT m.id, m.fullname, m.student_id, m.phone, m.position, m.membership_status, u.email
                                    FROM members m
                                    INNER JOIN users u ON m.user_id = u.id
                                    WHERE m.campus_id = :campus_id
                                    ORDER BY m.position DESC, m.fullname ASC";
                $allMembersStmt = $db->prepare($allMembersQuery);
                $allMembersStmt->bindParam(':campus_id', $campusId);
                $allMembersStmt->execute();
                $allMembers = $allMembersStmt->fetchAll();
                ?>
                
                <?php if (empty($allMembers)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> No members found for this campus.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Contact</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allMembers as $member): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($member['student_id']); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($member['phone']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['position'] == 'Executive' ? 'primary' : ($member['position'] == 'Patron' ? 'info' : 'secondary'); ?>">
                                                <?php echo htmlspecialchars($member['position']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['membership_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($member['membership_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="member_view.php?id=<?php echo $member['id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View Profile">
                                                <i class="cil-user"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
