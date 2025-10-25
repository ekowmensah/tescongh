<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Assign Patron to Campus';

$database = new Database();
$db = $database->getConnection();

$campusObj = new Campus($db);

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

// Get all patrons (not limited to this campus)
$allPatronsQuery = "SELECT m.id, m.fullname, m.phone, m.campus_id, u.email,
                           c.name as current_campus_name
                    FROM members m
                    INNER JOIN users u ON m.user_id = u.id
                    LEFT JOIN campuses c ON m.campus_id = c.id
                    WHERE m.position = 'Patron'
                    ORDER BY m.fullname ASC";
$allPatronsStmt = $db->prepare($allPatronsQuery);
$allPatronsStmt->execute();
$allPatrons = $allPatronsStmt->fetchAll();

// Get patrons currently assigned to this campus
$currentPatronsQuery = "SELECT m.id, m.fullname, m.phone, u.email, m.npp_position
                        FROM members m
                        INNER JOIN users u ON m.user_id = u.id
                        WHERE m.campus_id = :campus_id 
                          AND m.position = 'Patron'
                        ORDER BY m.fullname ASC";
$currentPatronsStmt = $db->prepare($currentPatronsQuery);
$currentPatronsStmt->bindParam(':campus_id', $campusId);
$currentPatronsStmt->execute();
$currentPatrons = $currentPatronsStmt->fetchAll();

// Handle form submission (assign patron)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $patronId = (int)$_POST['patron_id'];
    
    // Update patron's campus_id
    $updateQuery = "UPDATE members SET campus_id = :campus_id WHERE id = :patron_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':campus_id', $campusId);
    $updateStmt->bindParam(':patron_id', $patronId);
    
    if ($updateStmt->execute()) {
        setFlashMessage('success', 'Patron assigned to campus successfully');
        redirect('campus_assign_patron.php?campus_id=' . $campusId);
    } else {
        setFlashMessage('danger', 'Failed to assign patron');
    }
}

// Handle removal
if (isset($_GET['remove'])) {
    $patronId = (int)$_GET['remove'];
    
    // Set campus_id to NULL
    $removeQuery = "UPDATE members SET campus_id = NULL WHERE id = :patron_id";
    $removeStmt = $db->prepare($removeQuery);
    $removeStmt->bindParam(':patron_id', $patronId);
    
    if ($removeStmt->execute()) {
        setFlashMessage('success', 'Patron removed from campus');
        redirect('campus_assign_patron.php?campus_id=' . $campusId);
    } else {
        setFlashMessage('danger', 'Failed to remove patron');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Assign Patron</h2>
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
    <!-- Assign Patron -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Assign Patron to Campus</strong>
            </div>
            <div class="card-body">
                <?php if (empty($allPatrons)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> No patrons available. 
                        <a href="patron_add.php" class="alert-link">Add a patron first</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Select Patron <span class="text-danger">*</span></label>
                            <select class="form-select" name="patron_id" required>
                                <option value="">Choose a patron...</option>
                                <?php foreach ($allPatrons as $patron): ?>
                                    <option value="<?php echo $patron['id']; ?>">
                                        <?php echo htmlspecialchars($patron['fullname']); ?>
                                        <?php if ($patron['current_campus_name']): ?>
                                            (Currently at: <?php echo htmlspecialchars($patron['current_campus_name']); ?>)
                                        <?php else: ?>
                                            (Not assigned)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Note: Assigning a patron will update their campus affiliation</small>
                        </div>
                        
                        <button type="submit" name="assign" class="btn btn-primary w-100">
                            <i class="cil-check"></i> Assign Patron
                        </button>
                    </form>
                <?php endif; ?>
                
                <hr>
                
                <div class="d-grid">
                    <a href="patron_add.php" class="btn btn-success">
                        <i class="cil-user-plus"></i> Add New Patron
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Current Patrons -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <strong>Patrons at This Campus</strong>
                <span class="badge bg-light text-dark ms-2"><?php echo count($currentPatrons); ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($currentPatrons)): ?>
                    <div class="alert alert-warning">
                        <i class="cil-warning"></i> No patrons assigned to this campus yet.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($currentPatrons as $patron): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong><?php echo htmlspecialchars($patron['fullname']); ?></strong>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($patron['phone']); ?> | 
                                            <?php echo htmlspecialchars($patron['email']); ?>
                                        </small>
                                        <?php if ($patron['npp_position']): ?>
                                            <br>
                                            <span class="badge bg-warning text-dark mt-1">
                                                <?php echo htmlspecialchars($patron['npp_position']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="member_view.php?id=<?php echo $patron['id']; ?>" 
                                           class="btn btn-sm btn-info me-1" 
                                           title="View Profile">
                                            <i class="cil-user"></i>
                                        </a>
                                        <a href="?campus_id=<?php echo $campusId; ?>&remove=<?php echo $patron['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Remove from Campus"
                                           onclick="return confirm('Remove this patron from this campus?')">
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

<!-- All Campus Members (for reference) -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>All Members & Patrons from This Campus</strong>
            </div>
            <div class="card-body">
                <?php
                // Get all members and patrons from this campus
                $allMembersQuery = "SELECT m.id, m.fullname, m.student_id, m.phone, m.position, m.membership_status, u.email
                                    FROM members m
                                    INNER JOIN users u ON m.user_id = u.id
                                    WHERE m.campus_id = :campus_id
                                    ORDER BY 
                                        CASE m.position 
                                            WHEN 'Executive' THEN 1 
                                            WHEN 'Patron' THEN 2 
                                            ELSE 3 
                                        END,
                                        m.fullname ASC";
                $allMembersStmt = $db->prepare($allMembersQuery);
                $allMembersStmt->bindParam(':campus_id', $campusId);
                $allMembersStmt->execute();
                $allMembers = $allMembersStmt->fetchAll();
                ?>
                
                <?php if (empty($allMembers)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> No members or patrons assigned to this campus yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Contact</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allMembers as $member): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($member['student_id'] ?? '-'); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($member['phone']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $member['position'] == 'Executive' ? 'primary' : 
                                                    ($member['position'] == 'Patron' ? 'info' : 'secondary'); 
                                            ?>">
                                                <?php echo htmlspecialchars($member['position']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['membership_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($member['membership_status']); ?>
                                            </span>
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
