<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';
require_once 'classes/Payment.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Reports & Analytics';

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);
$payment = new Payment($db);

// Get statistics
$memberStats = $member->getStatistics();
$paymentStats = $payment->getStatistics();

// Get members by institution
$query = "SELECT institution, COUNT(*) as count FROM members GROUP BY institution ORDER BY count DESC LIMIT 10";
$stmt = $db->query($query);
$membersByInstitution = $stmt->fetchAll();

// Get payments by month (current year)
$query = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, COUNT(*) as count, SUM(amount) as total 
          FROM payments 
          WHERE status = 'completed' AND YEAR(payment_date) = YEAR(CURDATE())
          GROUP BY month 
          ORDER BY month ASC";
$stmt = $db->query($query);
$paymentsByMonth = $stmt->fetchAll();

// Get recent payments
$query = "SELECT p.*, m.fullname, m.student_id, d.year 
          FROM payments p
          LEFT JOIN members m ON p.member_id = m.id
          LEFT JOIN dues d ON p.dues_id = d.id
          WHERE p.status = 'completed'
          ORDER BY p.payment_date DESC 
          LIMIT 10";
$stmt = $db->query($query);
$recentPayments = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Reports & Analytics</h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Total Members</div>
                <div class="fs-3 fw-semibold text-primary"><?php echo number_format($memberStats['total_members']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Active Members</div>
                <div class="fs-3 fw-semibold text-success"><?php echo number_format($memberStats['active_members']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Total Revenue</div>
                <div class="fs-3 fw-semibold text-info"><?php echo formatCurrency($paymentStats['total_amount']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="text-medium-emphasis small text-uppercase fw-semibold">Pending Payments</div>
                <div class="fs-3 fw-semibold text-warning"><?php echo number_format($paymentStats['pending_payments']); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong>Members by Region</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Region</th>
                                <th class="text-end">Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($memberStats['members_by_region'] as $regionData): ?>
                                <?php $percentage = ($regionData['count'] / $memberStats['total_members']) * 100; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($regionData['region']); ?></td>
                                    <td class="text-end"><strong><?php echo $regionData['count']; ?></strong></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-primary" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%" 
                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <strong>Members by Institution</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th class="text-end">Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($membersByInstitution as $inst): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inst['institution']); ?></td>
                                    <td class="text-end"><strong><?php echo $inst['count']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong>Members by Status</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-end">Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($memberStats['members_by_status'] as $statusData): ?>
                                <?php $percentage = ($statusData['count'] / $memberStats['total_members']) * 100; ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($statusData['membership_status']); ?>">
                                            <?php echo $statusData['membership_status']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><strong><?php echo $statusData['count']; ?></strong></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo getStatusBadgeClass($statusData['membership_status']); ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%" 
                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <strong>Payment Summary (<?php echo date('Y'); ?>)</strong>
            </div>
            <div class="card-body">
                <?php if (empty($paymentsByMonth)): ?>
                    <p class="text-muted text-center">No payment data for this year</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Payments</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentsByMonth as $monthData): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($monthData['month'] . '-01')); ?></td>
                                        <td class="text-end"><?php echo $monthData['count']; ?></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($monthData['total']); ?></strong></td>
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

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Recent Payments</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Student ID</th>
                                <th>Year</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No payments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPayments as $p): ?>
                                    <tr>
                                        <td><?php echo formatDate($p['payment_date'], 'd M Y'); ?></td>
                                        <td><?php echo htmlspecialchars($p['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($p['student_id']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $p['year']; ?></span></td>
                                        <td><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
