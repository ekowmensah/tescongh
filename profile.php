<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';
require_once 'classes/Member.php';

$pageTitle = 'My Profile';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$member = new Member($db);

$userData = $user->getUserById($_SESSION['user_id']);
$memberData = $member->getByUserId($_SESSION['user_id']);

// Fetch voting region and constituency names if IDs exist
$votingRegionName = '';
$votingConstituencyName = '';

if (!empty($memberData['voting_region_id'])) {
    $vrQuery = "SELECT name FROM voting_regions WHERE id = :id";
    $vrStmt = $db->prepare($vrQuery);
    $vrStmt->bindParam(':id', $memberData['voting_region_id']);
    $vrStmt->execute();
    $vrResult = $vrStmt->fetch();
    if ($vrResult) {
        $votingRegionName = $vrResult['name'];
    }
}

if (!empty($memberData['voting_constituency_id'])) {
    $vcQuery = "SELECT name FROM voting_constituencies WHERE id = :id";
    $vcStmt = $db->prepare($vcQuery);
    $vcStmt->bindParam(':id', $memberData['voting_constituency_id']);
    $vcStmt->execute();
    $vcResult = $vcStmt->fetch();
    if ($vcResult) {
        $votingConstituencyName = $vcResult['name'];
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>My Profile</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($memberData['photo'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($memberData['photo']); ?>" 
                         alt="Profile Photo" 
                         class="rounded-circle mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-initials mx-auto mb-3" style="width: 150px; height: 150px; font-size: 3rem;">
                        <?php echo getInitials($memberData['fullname'] ?? $_SESSION['email']); ?>
                    </div>
                <?php endif; ?>
                
                <h4><?php echo htmlspecialchars($memberData['fullname'] ?? 'User'); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($userData['email']); ?></p>
                
                <div class="mb-3">
                    <span class="badge bg-<?php echo getStatusBadgeClass($userData['status']); ?> me-1">
                        <?php echo $userData['status']; ?>
                    </span>
                    <span class="badge bg-primary">
                        <?php echo $userData['role']; ?>
                    </span>
                </div>
                
                <?php if ($memberData): ?>
                    <span class="badge bg-<?php echo getStatusBadgeClass($memberData['membership_status']); ?>">
                        <?php echo $memberData['membership_status']; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <strong>Account Actions</strong>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="profile_edit.php" class="btn btn-primary">
                        <i class="cil-pencil"></i> Edit Profile
                    </a>
                    <a href="change_password.php" class="btn btn-warning">
                        <i class="cil-lock-locked"></i> Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if ($memberData): ?>
            <div class="card">
                <div class="card-header">
                    <strong>Personal Information</strong>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Full Name:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['fullname']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Phone:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['phone']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Date of Birth:</strong></div>
                        <div class="col-sm-8"><?php echo formatDate($memberData['date_of_birth'], 'd M Y'); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Position:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge bg-<?php echo $memberData['position'] == 'Executive' ? 'warning' : ($memberData['position'] == 'Patron' ? 'info' : 'secondary'); ?>">
                                <?php echo $memberData['position']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <strong>Academic Information</strong>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Institution:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['institution']); ?></div>
                    </div>
                    
                    <?php if (!empty($memberData['campus_name'])): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Campus:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($memberData['campus_name']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Department:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['department']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Program:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['program']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Year:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['year']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Student ID:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['student_id']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <strong>Regional Information</strong>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Current Region:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['region']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Current Constituency:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($memberData['constituency']); ?></div>
                    </div>
                    
                    <?php if (!empty($votingRegionName)): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Voting Region:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($votingRegionName); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($votingConstituencyName)): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Voting Constituency:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($votingConstituencyName); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($memberData['npp_position'])): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>NPP Position:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($memberData['npp_position']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="cil-user" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h4 class="mt-3 text-muted">Member Profile Not Complete</h4>
                    <p class="text-muted">Please complete your member profile to access all features.</p>
                    <a href="profile_edit.php" class="btn btn-primary mt-2">Complete Profile</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <strong>Account Information</strong>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Email Verified:</strong></div>
                    <div class="col-sm-8">
                        <?php if ($userData['email_verified']): ?>
                            <span class="badge bg-success">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Not Verified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Phone Verified:</strong></div>
                    <div class="col-sm-8">
                        <?php if ($userData['phone_verified']): ?>
                            <span class="badge bg-success">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Not Verified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Last Login:</strong></div>
                    <div class="col-sm-8">
                        <?php echo $userData['last_login'] ? formatDateTime($userData['last_login'], 'd M Y, g:i A') : 'Never'; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Member Since:</strong></div>
                    <div class="col-sm-8"><?php echo formatDate($userData['created_at'], 'd M Y'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
