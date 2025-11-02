<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';

$pageTitle = 'Member Profile';

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);

// Get member ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Member ID not provided');
    redirect('dashboard.php');
}

$memberId = (int)$_GET['id'];

// Check if regular member is trying to view someone else's profile
if (hasRole('Member') && !hasAnyRole(['Admin', 'Executive', 'Patron'])) {
    $currentUserId = $_SESSION['user_id'];
    $checkQuery = "SELECT id FROM members WHERE user_id = :user_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $currentUserId);
    $checkStmt->execute();
    $currentMember = $checkStmt->fetch();
    
    if (!$currentMember || $currentMember['id'] != $memberId) {
        setFlashMessage('danger', 'You can only view your own profile');
        redirect('member_view.php?id=' . $currentMember['id']);
    }
}

// Get member data with voting information
$query = "SELECT m.*, u.email, u.role as user_role, u.status as user_status, u.last_login, u.email_verified,
                 c.name as campus_name, c.location as campus_location,
                 i.name as institution_name,
                 vr.name as voting_region_name, vr.code as voting_region_code,
                 vc.name as voting_constituency_name,
                 r.name as region_name,
                 co.name as constituency_name
          FROM members m
          LEFT JOIN users u ON m.user_id = u.id
          LEFT JOIN campuses c ON m.campus_id = c.id
          LEFT JOIN institutions i ON c.institution_id = i.id
          LEFT JOIN voting_regions vr ON m.voting_region_id = vr.id
          LEFT JOIN voting_constituencies vc ON m.voting_constituency_id = vc.id
          LEFT JOIN regions r ON c.region_id = r.id
          LEFT JOIN constituencies co ON c.constituency_id = co.id
          WHERE m.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $memberId);
$stmt->execute();
$memberData = $stmt->fetch();

if (!$memberData) {
    setFlashMessage('danger', 'Member not found');
    redirect('members.php');
}

// Get activity statistics (with error handling for missing tables)
$stats = ['events_attended' => 0, 'dues_paid' => 0, 'total_paid' => 0];

try {
    // Check if event_attendees table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'event_attendees'")->fetch();
    if ($checkTable) {
        $eventsStmt = $db->prepare("SELECT COUNT(*) as count FROM event_attendees WHERE member_id = :id");
        $eventsStmt->bindParam(':id', $memberId);
        $eventsStmt->execute();
        $stats['events_attended'] = $eventsStmt->fetch()['count'];
    }
} catch (PDOException $e) {
    // Table doesn't exist, keep default value
}

try {
    // Get payment statistics from payments table
    $checkTable = $db->query("SHOW TABLES LIKE 'payments'")->fetch();
    if ($checkTable) {
        $duesStmt = $db->prepare("SELECT COUNT(*) as count, SUM(p.amount) as total 
                                   FROM payments p
                                   WHERE p.member_id = :id 
                                   AND p.status = 'completed')");
        $duesStmt->bindParam(':id', $memberId);
        $duesStmt->execute();
        $duesData = $duesStmt->fetch();
        $stats['dues_paid'] = $duesData['count'] ?? 0;
        $stats['total_paid'] = $duesData['total'] ?? 0;
    }
} catch (PDOException $e) {
    // Table doesn't exist, keep default values
}

// Get executive position if applicable
$execPosition = null;
if ($memberData['position'] == 'Executive') {
    $execQuery = "SELECT p.name as position_name, p.level, c.name as campus_name, 
                         i.name as institution_name, ce.appointed_at
                  FROM campus_executives ce
                  INNER JOIN positions p ON ce.position_id = p.id
                  INNER JOIN campuses c ON ce.campus_id = c.id
                  INNER JOIN institutions i ON c.institution_id = i.id
                  WHERE ce.member_id = :member_id AND ce.is_current = 1
                  LIMIT 1";
    $execStmt = $db->prepare($execQuery);
    $execStmt->bindParam(':member_id', $memberId);
    $execStmt->execute();
    $execPosition = $execStmt->fetch();
}

include 'includes/header.php';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    object-fit: cover;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    background: rgba(255,255,255,0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #666;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.info-card-header {
    padding: 1rem 1.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    border-bottom: 2px solid #f0f0f0;
}

.info-card-body {
    padding: 1.5rem;
}

.info-item {
    margin-bottom: 1.25rem;
}

.info-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

.badge-custom {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
}

.action-btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>

<!-- Profile Header -->
<div class="profile-header">
    <div class="row align-items-center">
        <div class="col-md-auto text-center text-md-start mb-3 mb-md-0">
            <?php if ($memberData['photo']): ?>
                <img src="uploads/<?php echo htmlspecialchars($memberData['photo']); ?>" 
                     alt="Profile Photo" 
                     class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder">
                    <?php echo strtoupper(substr($memberData['fullname'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md">
            <h2 class="mb-2"><?php echo htmlspecialchars($memberData['fullname']); ?></h2>
            <div class="mb-2">
                <span class="badge badge-custom bg-light text-dark me-2">
                    <i class="cil-user"></i> <?php echo htmlspecialchars($memberData['position']); ?>
                </span>
                <?php if ($execPosition): ?>
                    <span class="badge badge-custom bg-warning text-dark me-2">
                        <i class="cil-star"></i> <?php echo htmlspecialchars($execPosition['position_name']); ?>
                    </span>
                <?php endif; ?>
                <span class="badge badge-custom bg-<?php echo $memberData['membership_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($memberData['membership_status']); ?>
                </span>
            </div>
            <p class="mb-0 opacity-75">
                <i class="cil-envelope-closed me-2"></i><?php echo htmlspecialchars($memberData['email']); ?>
                <span class="mx-3">|</span>
                <i class="cil-phone me-2"></i><?php echo htmlspecialchars($memberData['phone']); ?>
            </p>
        </div>
        <div class="col-md-auto text-center text-md-end">
            <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <a href="member_edit.php?id=<?php echo $memberId; ?>" class="btn btn-light action-btn me-2 mb-2">
                <i class="cil-pencil"></i> Edit
            </a>
            <?php endif; ?>
            <a href="members.php" class="btn btn-outline-light action-btn mb-2">
                <i class="cil-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon text-primary"><i class="cil-calendar"></i></div>
            <div class="stat-value"><?php echo $stats['events_attended'] ?? 0; ?></div>
            <div class="stat-label">Events Attended</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon text-success"><i class="cil-dollar"></i></div>
            <div class="stat-value"><?php echo $stats['dues_paid'] ?? 0; ?></div>
            <div class="stat-label">Dues Paid</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon text-info"><i class="cil-wallet"></i></div>
            <div class="stat-value">GH₵<?php echo number_format($stats['total_paid'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Contributed</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon text-warning"><i class="cil-clock"></i></div>
            <div class="stat-value"><?php echo floor((time() - strtotime($memberData['created_at'])) / (60*60*24)); ?></div>
            <div class="stat-label">Days as Member</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-md-4">
        <!-- Quick Actions -->
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="cil-settings"></i> Quick Actions
            </div>
            <div class="info-card-body">
                <div class="d-grid gap-2">
                    <a href="member_edit.php?id=<?php echo $memberId; ?>" class="btn btn-warning action-btn">
                        <i class="cil-pencil"></i> Edit Profile
                    </a>
                    <?php if ($memberData['position'] == 'Member' && $memberData['campus_id']): ?>
                    <a href="campus_assign_executive.php?campus_id=<?php echo $memberData['campus_id']; ?>" class="btn btn-success action-btn">
                        <i class="cil-star"></i> Promote to Executive
                    </a>
                    <?php endif; ?>
                    <a href="mailto:<?php echo htmlspecialchars($memberData['email']); ?>" class="btn btn-info action-btn">
                        <i class="cil-envelope-closed"></i> Send Email
                    </a>
                    <?php if (hasRole('Admin')): ?>
                    <button class="btn btn-danger action-btn" onclick="if(confirm('Are you sure you want to delete this member? This action cannot be undone.')) window.location='member_delete.php?id=<?php echo $memberId; ?>'">
                        <i class="cil-trash"></i> Delete Member
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Account Status -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="cil-shield-alt"></i> Account Status
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">User Role</div>
                    <div class="info-value">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($memberData['user_role']); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Account Status</div>
                    <div class="info-value">
                        <span class="badge bg-<?php echo $memberData['user_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                            <?php echo htmlspecialchars($memberData['user_status']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Verified</div>
                    <div class="info-value">
                        <?php if (isset($memberData['email_verified']) && $memberData['email_verified']): ?>
                            <span class="badge bg-success"><i class="cil-check-circle"></i> Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning"><i class="cil-warning"></i> Not Verified</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-item mb-0">
                    <div class="info-label">Last Login</div>
                    <div class="info-value">
                        <?php echo isset($memberData['last_login']) && $memberData['last_login'] ? date('M d, Y h:i A', strtotime($memberData['last_login'])) : '<span class="text-muted">Never</span>'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Membership Info -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="cil-calendar"></i> Membership Info
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($memberData['created_at'])); ?></div>
                </div>
                <?php if ($memberData['date_of_birth']): ?>
                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($memberData['date_of_birth'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Age</div>
                    <div class="info-value"><?php echo date_diff(date_create($memberData['date_of_birth']), date_create('today'))->y; ?> years</div>
                </div>
                <?php endif; ?>
                <div class="info-item mb-0">
                    <div class="info-label">Profile Updated</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($memberData['updated_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-md-8">
        <!-- Contact Information -->
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="cil-contact"></i> Contact Information
            </div>
            <div class="info-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-envelope-closed"></i> Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['email']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-phone"></i> Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['phone']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-user"></i> Gender</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['gender'] ?? 'Not specified'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Academic Information -->
        <?php if ($memberData['position'] != 'Patron'): ?>
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                <i class="cil-education"></i> Academic Information
            </div>
            <div class="info-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-building"></i> Institution</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['institution_name'] ?? $memberData['institution']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-location-pin"></i> Campus</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['campus_name'] ?? 'Not assigned'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-badge"></i> Student ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['student_id'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-book"></i> Program</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['program']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-library"></i> Department</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['department'] ?? 'Not specified'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-chart-line"></i> Year/Level</div>
                            <div class="info-value">Year <?php echo htmlspecialchars($memberData['year']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Location Information -->
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <i class="cil-map"></i> Campus Location
            </div>
            <div class="info-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-location-pin"></i> Campus Region</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['region_name'] ?? $memberData['region'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-map"></i> Campus Constituency</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['constituency_name'] ?? $memberData['constituency'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                    <?php if ($memberData['campus_location']): ?>
                    <div class="col-md-12">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-home"></i> Campus Location</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['campus_location']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Voting Information -->
        <?php if ($memberData['voting_region_name'] || $memberData['voting_constituency_name']): ?>
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="cil-flag-alt"></i> Electoral Information
            </div>
            <div class="info-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-map"></i> Voting Region</div>
                            <div class="info-value">
                                <?php if ($memberData['voting_region_name']): ?>
                                    <?php echo htmlspecialchars($memberData['voting_region_name']); ?>
                                    <span class="badge bg-info ms-2"><?php echo htmlspecialchars($memberData['voting_region_code']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-location-pin"></i> Voting Constituency</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['voting_constituency_name'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Origin Information -->
        <?php if ($memberData['hails_from_region'] || $memberData['hails_from_constituency']): ?>
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="cil-home"></i> Origin (Hometown)
            </div>
            <div class="info-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-map"></i> Hails From Region</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['hails_from_region'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-location-pin"></i> Hails From Constituency</div>
                            <div class="info-value"><?php echo htmlspecialchars($memberData['hails_from_constituency'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Political Information -->
        <?php if ($memberData['npp_position']): ?>
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); color: white;">
                <i class="cil-flag-alt"></i> NPP Information
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label"><i class="cil-badge"></i> NPP Position</div>
                    <div class="info-value"><?php echo htmlspecialchars($memberData['npp_position']); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Executive Information -->
        <?php if ($execPosition): ?>
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: white;">
                <i class="cil-star"></i> Executive Position
            </div>
            <div class="info-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-badge"></i> Position</div>
                            <div class="info-value">
                                <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($execPosition['position_name']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-location-pin"></i> Campus</div>
                            <div class="info-value"><?php echo htmlspecialchars($execPosition['campus_name']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-building"></i> Institution</div>
                            <div class="info-value"><?php echo htmlspecialchars($execPosition['institution_name']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label"><i class="cil-calendar"></i> Appointed On</div>
                            <div class="info-value"><?php echo date('M d, Y', strtotime($execPosition['appointed_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
