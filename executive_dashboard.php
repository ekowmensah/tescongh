<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!hasAnyRole(['Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Executive Dashboard';

$database = new Database();
$db = $database->getConnection();

// Get current user's member ID
$userId = $_SESSION['user_id'];
$memberQuery = "SELECT id, fullname FROM members WHERE user_id = :user_id LIMIT 1";
$memberStmt = $db->prepare($memberQuery);
$memberStmt->bindParam(':user_id', $userId);
$memberStmt->execute();
$currentMember = $memberStmt->fetch();

if (!$currentMember) {
    setFlashMessage('danger', 'Member profile not found');
    redirect('dashboard.php');
}

$memberId = $currentMember['id'];

// Get executive's position and campus
$execQuery = "SELECT 
                ce.id,
                ce.appointed_at,
                ce.term_start,
                ce.term_end,
                p.name as position_name,
                p.level as position_level,
                c.id as campus_id,
                c.name as campus_name,
                c.location as campus_location,
                i.name as institution_name
              FROM campus_executives ce
              INNER JOIN positions p ON ce.position_id = p.id
              INNER JOIN campuses c ON ce.campus_id = c.id
              INNER JOIN institutions i ON c.institution_id = i.id
              WHERE ce.member_id = :member_id 
                AND ce.is_current = 1
              LIMIT 1";
$execStmt = $db->prepare($execQuery);
$execStmt->bindParam(':member_id', $memberId);
$execStmt->execute();
$executive = $execStmt->fetch();

if (!$executive) {
    setFlashMessage('warning', 'You are not currently assigned to an executive position');
    redirect('dashboard.php');
}

$campusId = $executive['campus_id'];

// Get team members (other executives in same campus)
$teamQuery = "SELECT 
                m.id,
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
              WHERE ce.campus_id = :campus_id 
                AND ce.is_current = 1
                AND ce.member_id != :member_id
              ORDER BY p.level ASC";
$teamStmt = $db->prepare($teamQuery);
$teamStmt->bindParam(':campus_id', $campusId);
$teamStmt->bindParam(':member_id', $memberId);
$teamStmt->execute();
$teamMembers = $teamStmt->fetchAll();

// Get campus statistics
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id1 AND position = 'Member') as total_members,
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id2 AND position = 'Executive') as total_executives,
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id3 AND position = 'Patron') as total_patrons,
                (SELECT COUNT(*) FROM members WHERE campus_id = :campus_id4 AND membership_status = 'Active') as active_members";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':campus_id1', $campusId);
$statsStmt->bindParam(':campus_id2', $campusId);
$statsStmt->bindParam(':campus_id3', $campusId);
$statsStmt->bindParam(':campus_id4', $campusId);
$statsStmt->execute();
$stats = $statsStmt->fetch();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Executive Dashboard</h2>
        <p class="text-muted">
            <?php echo htmlspecialchars($executive['position_name']); ?> - 
            <?php echo htmlspecialchars($executive['institution_name']); ?> 
            (<?php echo htmlspecialchars($executive['campus_name']); ?>)
        </p>
    </div>
</div>

<!-- Executive Info Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2"><?php echo htmlspecialchars($currentMember['fullname']); ?></h4>
                        <h5><?php echo htmlspecialchars($executive['position_name']); ?></h5>
                        <p class="mb-0">
                            <i class="cil-building"></i> <?php echo htmlspecialchars($executive['institution_name']); ?><br>
                            <i class="cil-location-pin"></i> <?php echo htmlspecialchars($executive['campus_name']); ?>, <?php echo htmlspecialchars($executive['campus_location']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-1"><strong>Appointed:</strong> <?php echo date('M d, Y', strtotime($executive['appointed_at'])); ?></p>
                        <?php if ($executive['term_start']): ?>
                        <p class="mb-1"><strong>Term:</strong> <?php echo date('M Y', strtotime($executive['term_start'])); ?> - 
                            <?php echo $executive['term_end'] ? date('M Y', strtotime($executive['term_end'])) : 'Present'; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Total Members</h6>
                <h2 class="mb-0"><?php echo $stats['total_members']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Active Members</h6>
                <h2 class="mb-0 text-success"><?php echo $stats['active_members']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Executives</h6>
                <h2 class="mb-0 text-primary"><?php echo $stats['total_executives']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Patrons</h6>
                <h2 class="mb-0 text-info"><?php echo $stats['total_patrons']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Executive Team -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Your Executive Team</strong>
                <span class="badge bg-primary ms-2"><?php echo count($teamMembers) + 1; ?> Members</span>
            </div>
            <div class="card-body">
                <?php if (empty($teamMembers)): ?>
                    <div class="alert alert-info">
                        <i class="cil-info"></i> You are the only executive assigned to this campus. 
                        <a href="executive_add.php" class="alert-link">Add more executives</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Appointed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamMembers as $member): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $member['position_level'] <= 3 ? 'primary' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($member['position_name']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($member['fullname']); ?></strong></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($member['phone']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($member['appointed_at'])); ?></td>
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

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Quick Actions</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="member_add.php" class="btn btn-primary w-100">
                            <i class="cil-user-plus"></i> Add Member
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="executive_add.php" class="btn btn-success w-100">
                            <i class="cil-star"></i> Add Executive
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="members.php" class="btn btn-info w-100">
                            <i class="cil-people"></i> View All Members
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="sms.php" class="btn btn-warning w-100">
                            <i class="cil-comment-bubble"></i> Send SMS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
