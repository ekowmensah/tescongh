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
    redirect('members.php');
}

$memberId = (int)$_GET['id'];
$memberData = $member->getById($memberId);

if (!$memberData) {
    setFlashMessage('danger', 'Member not found');
    redirect('members.php');
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

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Member Profile</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
        <a href="member_edit.php?id=<?php echo $memberId; ?>" class="btn btn-warning">
            <i class="cil-pencil"></i> Edit Profile
        </a>
        <?php endif; ?>
        <a href="members.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-md-4">
        <!-- Profile Card -->
        <div class="card text-center">
            <div class="card-body">
                <?php if ($memberData['photo']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($memberData['photo']); ?>" 
                         alt="Profile Photo" 
                         class="rounded-circle mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 150px; height: 150px; font-size: 48px;">
                        <?php echo strtoupper(substr($memberData['fullname'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <h4 class="mb-1"><?php echo htmlspecialchars($memberData['fullname']); ?></h4>
                
                <span class="badge bg-<?php 
                    echo $memberData['position'] == 'Executive' ? 'primary' : 
                        ($memberData['position'] == 'Patron' ? 'info' : 'secondary'); 
                ?> mb-2">
                    <?php echo htmlspecialchars($memberData['position']); ?>
                </span>
                
                <?php if ($execPosition): ?>
                    <div class="mb-2">
                        <span class="badge bg-success"><?php echo htmlspecialchars($execPosition['position_name']); ?></span>
                    </div>
                <?php endif; ?>
                
                <span class="badge bg-<?php echo $memberData['membership_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($memberData['membership_status']); ?>
                </span>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
        <div class="card mt-3">
            <div class="card-header">
                <strong>Quick Actions</strong>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="member_edit.php?id=<?php echo $memberId; ?>" class="btn btn-warning">
                        <i class="cil-pencil"></i> Edit Profile
                    </a>
                    <?php if ($memberData['position'] == 'Member'): ?>
                    <a href="campus_assign_executive.php?campus_id=<?php echo $memberData['campus_id']; ?>" class="btn btn-success">
                        <i class="cil-star"></i> Make Executive
                    </a>
                    <?php endif; ?>
                    <?php if (hasRole('Admin')): ?>
                    <button class="btn btn-danger" onclick="if(confirm('Are you sure?')) window.location='member_delete.php?id=<?php echo $memberId; ?>'">
                        <i class="cil-trash"></i> Delete Member
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column -->
    <div class="col-md-8">
        <!-- Contact Information -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="cil-contact"></i> Contact Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Email Address</label>
                        <div><strong><?php echo htmlspecialchars($memberData['email']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Phone Number</label>
                        <div><strong><?php echo htmlspecialchars($memberData['phone']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Date of Birth</label>
                        <div><strong><?php echo $memberData['date_of_birth'] ? date('M d, Y', strtotime($memberData['date_of_birth'])) : 'Not provided'; ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Member Since</label>
                        <div><strong><?php echo date('M d, Y', strtotime($memberData['created_at'])); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Academic Information -->
        <?php if ($memberData['position'] != 'Patron'): ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <strong><i class="cil-education"></i> Academic Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Institution</label>
                        <div><strong><?php echo htmlspecialchars($memberData['institution_name'] ?? $memberData['institution']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Campus</label>
                        <div><strong><?php echo htmlspecialchars($memberData['campus_name'] ?? 'Not assigned'); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Student ID</label>
                        <div><strong><?php echo htmlspecialchars($memberData['student_id'] ?? 'Not provided'); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Program</label>
                        <div><strong><?php echo htmlspecialchars($memberData['program']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Department</label>
                        <div><strong><?php echo htmlspecialchars($memberData['department'] ?? 'Not specified'); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Year/Level</label>
                        <div><strong>Year <?php echo htmlspecialchars($memberData['year']); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Location Information -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <strong><i class="cil-location-pin"></i> Location Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Current Region</label>
                        <div><strong><?php echo htmlspecialchars($memberData['region'] ?? 'Not provided'); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Current Constituency</label>
                        <div><strong><?php echo htmlspecialchars($memberData['constituency'] ?? 'Not provided'); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Hails From Region</label>
                        <div><strong><?php echo htmlspecialchars($memberData['hails_from_region'] ?? 'Not provided'); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Hails From Constituency</label>
                        <div><strong><?php echo htmlspecialchars($memberData['hails_from_constituency'] ?? 'Not provided'); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Political Information -->
        <?php if ($memberData['npp_position']): ?>
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <strong><i class="cil-flag-alt"></i> NPP Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <label class="text-muted small">NPP Position</label>
                        <div><strong><?php echo htmlspecialchars($memberData['npp_position']); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Executive Information -->
        <?php if ($execPosition): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="cil-star"></i> Executive Position</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Position</label>
                        <div><strong><?php echo htmlspecialchars($execPosition['position_name']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Campus</label>
                        <div><strong><?php echo htmlspecialchars($execPosition['campus_name']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Institution</label>
                        <div><strong><?php echo htmlspecialchars($execPosition['institution_name']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Appointed On</label>
                        <div><strong><?php echo date('M d, Y', strtotime($execPosition['appointed_at'])); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Account Information -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <strong><i class="cil-user"></i> Account Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">User Role</label>
                        <div><strong><?php echo htmlspecialchars($memberData['user_role']); ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Account Status</label>
                        <div>
                            <span class="badge bg-<?php echo $memberData['user_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($memberData['user_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Last Login</label>
                        <div><strong><?php echo isset($memberData['last_login']) && $memberData['last_login'] ? date('M d, Y h:i A', strtotime($memberData['last_login'])) : 'Never'; ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Email Verified</label>
                        <div>
                            <?php if (isset($memberData['email_verified']) && $memberData['email_verified']): ?>
                                <span class="badge bg-success"><i class="cil-check"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><i class="cil-x"></i> Not Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
