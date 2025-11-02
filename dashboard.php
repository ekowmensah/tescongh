<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';

$pageTitle = 'Dashboard';

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);

// Check user role and customize dashboard
$isRegularMember = hasRole('Member') && !hasAnyRole(['Admin', 'Executive', 'Patron']);
$canViewStats = hasAnyRole(['Admin', 'Executive', 'Patron']);

// Get statistics only for authorized users
if ($canViewStats) {
    $stats = $member->getStatistics();
    // Get recent members
    $recentMembers = $member->getAll(5, 0);
} else {
    // Regular members see their own profile data
    $currentUserId = $_SESSION['user_id'];
    $memberQuery = "SELECT m.*, u.email FROM members m 
                    LEFT JOIN users u ON m.user_id = u.id 
                    WHERE m.user_id = :user_id";
    $stmt = $db->prepare($memberQuery);
    $stmt->bindParam(':user_id', $currentUserId);
    $stmt->execute();
    $currentMemberData = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Dashboard</h2>
        <?php if ($isRegularMember): ?>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($currentMemberData['fullname'] ?? 'Member'); ?>!</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($isRegularMember): ?>
<!-- Regular Member Dashboard -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <strong>My Profile</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Full Name</label>
                        <div><strong><?php echo htmlspecialchars($currentMemberData['fullname']); ?></strong></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Student ID</label>
                        <div><strong><?php echo htmlspecialchars($currentMemberData['student_id']); ?></strong></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">Phone Number</label>
                        <div><strong><?php echo htmlspecialchars($currentMemberData['phone']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Institution</label>
                        <div><strong><?php echo htmlspecialchars($currentMemberData['institution']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Program</label>
                        <div><strong><?php echo htmlspecialchars($currentMemberData['program']); ?></strong></div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">Year/Level</label>
                        <div><strong>Year <?php echo htmlspecialchars($currentMemberData['year']); ?></strong></div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">Position</label>
                        <div><span class="badge bg-info"><?php echo htmlspecialchars($currentMemberData['position']); ?></span></div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">Status</label>
                        <div>
                            <span class="badge bg-<?php echo getStatusBadgeClass($currentMemberData['membership_status']); ?>">
                                <?php echo $currentMemberData['membership_status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="text-muted small">Member Since</label>
                        <div><strong><?php echo formatDate($currentMemberData['created_at'], 'd M Y'); ?></strong></div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="member_view.php?id=<?php echo $currentMemberData['id']; ?>" class="btn btn-primary">
                        <i class="cil-user"></i> View Full Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong>Quick Actions</strong>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pay_dues.php" class="btn btn-success btn-lg">
                        <i class="cil-dollar"></i> Pay Dues
                    </a>
                    <a href="payments.php" class="btn btn-outline-primary">
                        <i class="cil-wallet"></i> Payment History
                    </a>
                    <a href="events.php" class="btn btn-outline-secondary">
                        <i class="cil-calendar"></i> View Events
                    </a>
                    <a href="gallery.php" class="btn btn-outline-info">
                        <i class="cil-image"></i> Gallery
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <strong>Announcements</strong>
            </div>
            <div class="card-body">
                <p class="text-muted text-center">No new announcements</p>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Admin/Executive/Patron Dashboard -->
<div class="row">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-medium-emphasis small text-uppercase fw-semibold">Total Members</div>
                        <div class="fs-3 fw-semibold text-primary"><?php echo number_format($stats['total_members']); ?></div>
                    </div>
                    <div class="text-primary" style="font-size: 3rem; opacity: 0.2;">
                        <i class="cil-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-medium-emphasis small text-uppercase fw-semibold">Active Members</div>
                        <div class="fs-3 fw-semibold text-success"><?php echo number_format($stats['active_members']); ?></div>
                    </div>
                    <div class="text-success" style="font-size: 3rem; opacity: 0.2;">
                        <i class="cil-user-follow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-medium-emphasis small text-uppercase fw-semibold">Executives</div>
                        <div class="fs-3 fw-semibold text-warning"><?php echo number_format($stats['executives']); ?></div>
                    </div>
                    <div class="text-warning" style="font-size: 3rem; opacity: 0.2;">
                        <i class="cil-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-medium-emphasis small text-uppercase fw-semibold">Patrons</div>
                        <div class="fs-3 fw-semibold text-info"><?php echo number_format($stats['patrons']); ?></div>
                    </div>
                    <div class="text-info" style="font-size: 3rem; opacity: 0.2;">
                        <i class="cil-shield-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <strong>Recent Members</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Student ID</th>
                                <th>Institution</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentMembers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No members found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentMembers as $m): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initials me-2" style="width: 32px; height: 32px; font-size: 0.875rem;">
                                                    <?php echo getInitials($m['fullname']); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($m['fullname']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($m['phone']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($m['student_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($m['institution']); ?></td>
                                        <td><small><?php echo htmlspecialchars($m['program']); ?></small></td>
                                        <td><span class="badge bg-secondary">Year <?php echo $m['year']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($m['membership_status']); ?>">
                                                <?php echo $m['membership_status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="members.php" class="btn btn-primary">View All Members</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong>Members by Status</strong>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['members_by_status'])): ?>
                    <?php foreach ($stats['members_by_status'] as $statusData): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $statusData['membership_status']; ?></span>
                                <strong><?php echo $statusData['count']; ?></strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <?php 
                                $percentage = ($statusData['count'] / $stats['total_members']) * 100;
                                $progressClass = getStatusBadgeClass($statusData['membership_status']);
                                ?>
                                <div class="progress-bar bg-<?php echo $progressClass; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $percentage; ?>%" 
                                     aria-valuenow="<?php echo $percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No data available</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <strong>Top Regions</strong>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['members_by_region'])): ?>
                    <ul class="list-unstyled mb-0">
                        <?php 
                        $topRegions = array_slice($stats['members_by_region'], 0, 5);
                        foreach ($topRegions as $regionData): 
                        ?>
                            <li class="d-flex justify-content-between align-items-center mb-2">
                                <span><?php echo htmlspecialchars($regionData['region']); ?></span>
                                <span class="badge bg-primary rounded-pill"><?php echo $regionData['count']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted text-center">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
