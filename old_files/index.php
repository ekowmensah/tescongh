<?php
/**
 * TESCON Ghana - Home Page
 */
require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

$pageTitle = "Dashboard - UEWTESCON";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '#']
];

include 'includes/coreui_head.php';
?>

<!-- Dashboard Statistics -->
<div class="row">
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                <div>
                    <div class="fs-4 fw-semibold">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM members WHERE membership_status = 'Active'");
                            echo number_format($stmt->fetch()['count']);
                        } catch (PDOException $e) {
                            echo "N/A";
                        }
                        ?>
                    </div>
                    <div>Active Members</div>
                </div>
                <div class="dropdown">
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-info">
            <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                <div>
                    <div class="fs-4 fw-semibold">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(DISTINCT institution) as count FROM members");
                            echo number_format($stmt->fetch()['count']);
                        } catch (PDOException $e) {
                            echo "N/A";
                        }
                        ?>
                    </div>
                    <div>Institutions</div>
                </div>
                <div class="dropdown">
                    <i class="fas fa-university fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-warning">
            <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                <div>
                    <div class="fs-4 fw-semibold">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM regions");
                            echo number_format($stmt->fetch()['count']);
                        } catch (PDOException $e) {
                            echo "N/A";
                        }
                        ?>
                    </div>
                    <div>Regions</div>
                </div>
                <div class="dropdown">
                    <i class="fas fa-map-marker-alt fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-danger">
            <div class="card-body pb-0 d-flex justify-content-between align-items-start">
                <div>
                    <div class="fs-4 fw-semibold">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM campuses");
                            echo number_format($stmt->fetch()['count']);
                        } catch (PDOException $e) {
                            echo "N/A";
                        }
                        ?>
                    </div>
                    <div>Campuses</div>
                </div>
                <div class="dropdown">
                    <i class="fas fa-building fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Section -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-home me-2"></i>Welcome to UEW TESCON
            </div>
            <div class="card-body">
                <h5 class="card-title">Tertiary Students Confederacy of the New Patriotic Party</h5>
                <p class="card-text">Empowering students, building leaders, shaping Ghana's future.</p>
                
                <?php if (!isLoggedIn()): ?>
                <div class="mt-3">
                    <a href="register.php" class="btn btn-primary me-2">
                        <i class="fas fa-user-plus me-2"></i>Register Now
                    </a>
                    <a href="login.php" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Member Login
                    </a>
                </div>
                <?php else: ?>
                <div class="mt-3">
                    <a href="members.php" class="btn btn-primary me-2">
                        <i class="fas fa-users me-2"></i>View Members
                    </a>
                    <a href="pay_dues.php" class="btn btn-success">
                        <i class="fas fa-credit-card me-2"></i>Pay Dues
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Networking</h5>
                <p class="card-text">Connect with like-minded students across Ghana's tertiary institutions.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                <h5 class="card-title">Leadership Development</h5>
                <p class="card-text">Develop your leadership skills through training and mentorship programs.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-flag fa-3x text-success mb-3"></i>
                <h5 class="card-title">Political Engagement</h5>
                <p class="card-text">Participate in shaping Ghana's political landscape and future.</p>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/coreui_layout_end.php';
?>
