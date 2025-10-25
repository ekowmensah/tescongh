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
$stats = $member->getStatistics();

// Get recent members
$recentMembers = $member->getAll(5, 0);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Dashboard</h2>
    </div>
</div>

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
                                <th>Email</th>
                                <th>Institution</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentMembers)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No members found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentMembers as $m): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-initials me-2" style="width: 32px; height: 32px; font-size: 0.875rem;">
                                                    <?php echo getInitials($m['fullname']); ?>
                                                </div>
                                                <strong><?php echo htmlspecialchars($m['fullname']); ?></strong>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($m['email']); ?></td>
                                        <td><?php echo htmlspecialchars($m['institution']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($m['membership_status']); ?>">
                                                <?php echo $m['membership_status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($m['created_at'], 'd M Y'); ?></td>
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

<?php include 'includes/footer.php'; ?>
