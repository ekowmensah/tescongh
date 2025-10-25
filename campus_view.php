<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';

$pageTitle = 'Campus Details';

$database = new Database();
$db = $database->getConnection();

$campusObj = new Campus($db);

// Get campus ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Campus ID not provided');
    redirect('campuses.php');
}

$campusId = (int)$_GET['id'];
$campus = $campusObj->getById($campusId);

if (!$campus) {
    setFlashMessage('danger', 'Campus not found');
    redirect('campuses.php');
}

// Get statistics
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id) as total_members,
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id2 AND position = 'Member') as regular_members,
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id3 AND position = 'Executive') as executives,
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id4 AND position = 'Patron') as patrons,
                (SELECT COUNT(*) FROM campus_executives WHERE campus_id = :campus_id5 AND is_current = 1) as filled_positions";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':campus_id', $campusId);
$statsStmt->bindParam(':campus_id2', $campusId);
$statsStmt->bindParam(':campus_id3', $campusId);
$statsStmt->bindParam(':campus_id4', $campusId);
$statsStmt->bindParam(':campus_id5', $campusId);
$statsStmt->execute();
$stats = $statsStmt->fetch();

// Get executives
$execQuery = "SELECT m.id, m.fullname, m.phone, u.email, p.name as position_name, p.level
              FROM campus_executives ce
              INNER JOIN members m ON ce.member_id = m.id
              INNER JOIN users u ON m.user_id = u.id
              INNER JOIN positions p ON ce.position_id = p.id
              WHERE ce.campus_id = :campus_id AND ce.is_current = 1
              ORDER BY p.level ASC
              LIMIT 5";
$execStmt = $db->prepare($execQuery);
$execStmt->bindParam(':campus_id', $campusId);
$execStmt->execute();
$executives = $execStmt->fetchAll();

// Get recent members
$membersQuery = "SELECT m.id, m.fullname, m.phone, m.position, m.created_at
                 FROM members m
                 WHERE m.campus_id = :campus_id
                 ORDER BY m.created_at DESC
                 LIMIT 10";
$membersStmt = $db->prepare($membersQuery);
$membersStmt->bindParam(':campus_id', $campusId);
$membersStmt->execute();
$recentMembers = $membersStmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Campus Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasRole('Admin')): ?>
        <a href="campus_edit.php?id=<?php echo $campusId; ?>" class="btn btn-warning">
            <i class="cil-pencil"></i> Edit Campus
        </a>
        <?php endif; ?>
        <a href="campuses.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Campuses
        </a>
    </div>
</div>

<!-- Campus Header Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <?php if (!empty($campus['logo'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($campus['logo']); ?>" 
                         alt="Campus Logo" 
                         class="img-fluid rounded" 
                         style="max-height: 100px;">
                <?php else: ?>
                    <div class="bg-primary text-white rounded d-inline-flex align-items-center justify-content-center" 
                         style="width: 100px; height: 100px; font-size: 36px;">
                        <?php echo strtoupper(substr($campus['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-10">
                <h3 class="mb-2"><?php echo htmlspecialchars($campus['name']); ?></h3>
                <h5 class="text-muted mb-3"><?php echo htmlspecialchars($campus['institution_name']); ?></h5>
                <div class="row">
                    <div class="col-md-4">
                        <i class="cil-location-pin text-primary"></i> 
                        <strong>Location:</strong> <?php echo htmlspecialchars($campus['location']); ?>
                    </div>
                    <div class="col-md-4">
                        <i class="cil-map text-success"></i> 
                        <strong>Region:</strong> <?php echo htmlspecialchars($campus['region_name']); ?>
                    </div>
                    <div class="col-md-4">
                        <i class="cil-list text-info"></i> 
                        <strong>Constituency:</strong> <?php echo htmlspecialchars($campus['constituency_name']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-primary mb-2" style="font-size: 36px;">
                    <i class="cil-people"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['total_members']; ?></h3>
                <p class="text-muted mb-0">Total Members</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-success mb-2" style="font-size: 36px;">
                    <i class="cil-star"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['filled_positions']; ?>/11</h3>
                <p class="text-muted mb-0">Executives</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-info mb-2" style="font-size: 36px;">
                    <i class="cil-user-follow"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['patrons']; ?></h3>
                <p class="text-muted mb-0">Patrons</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-secondary mb-2" style="font-size: 36px;">
                    <i class="cil-user"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['regular_members']; ?></h3>
                <p class="text-muted mb-0">Regular Members</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Executive Team -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <strong><i class="cil-star"></i> Executive Team</strong>
                <a href="campus_assign_executive.php?campus_id=<?php echo $campusId; ?>" 
                   class="btn btn-sm btn-light float-end">
                    <i class="cil-plus"></i> Assign
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($executives)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> No executives assigned yet.
                        <a href="campus_assign_executive.php?campus_id=<?php echo $campusId; ?>" class="alert-link">Assign executives</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($executives as $exec): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary mb-1"><?php echo htmlspecialchars($exec['position_name']); ?></span>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($exec['fullname']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($exec['phone']); ?> | 
                                            <?php echo htmlspecialchars($exec['email']); ?>
                                        </small>
                                    </div>
                                    <a href="member_view.php?id=<?php echo $exec['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="cil-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($executives) >= 5): ?>
                        <div class="text-center mt-3">
                            <a href="campus_executives.php?campus_id=<?php echo $campusId; ?>" class="btn btn-sm btn-primary">
                                View All Executives
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Members -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <strong><i class="cil-people"></i> Recent Members</strong>
                <a href="member_add.php" class="btn btn-sm btn-light float-end">
                    <i class="cil-plus"></i> Add Member
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentMembers)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> No members yet.
                        <a href="member_add.php" class="alert-link">Add first member</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentMembers as $member): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($member['fullname']); ?>
                                            <span class="badge bg-<?php 
                                                echo $member['position'] == 'Executive' ? 'primary' : 
                                                    ($member['position'] == 'Patron' ? 'info' : 'secondary'); 
                                            ?> ms-2">
                                                <?php echo htmlspecialchars($member['position']); ?>
                                            </span>
                                        </h6>
                                        <small class="text-muted">
                                            Joined <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                                        </small>
                                    </div>
                                    <a href="member_view.php?id=<?php echo $member['id']; ?>" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="cil-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="members.php?campus_id=<?php echo $campusId; ?>" class="btn btn-sm btn-success">
                            View All Members
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-secondary text-white">
        <strong><i class="cil-settings"></i> Quick Actions</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-2">
                <a href="campus_assign_executive.php?campus_id=<?php echo $campusId; ?>" class="btn btn-primary w-100">
                    <i class="cil-star"></i> Assign Executive
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="campus_assign_patron.php?campus_id=<?php echo $campusId; ?>" class="btn btn-info w-100">
                    <i class="cil-user-follow"></i> Assign Patron
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="member_add.php" class="btn btn-success w-100">
                    <i class="cil-user-plus"></i> Add Member
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="members.php?campus_id=<?php echo $campusId; ?>" class="btn btn-secondary w-100">
                    <i class="cil-list"></i> View All Members
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
