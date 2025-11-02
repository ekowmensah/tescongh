<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';

$pageTitle = 'Members';

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);

// Check user role and redirect regular members to their own profile
if (hasRole('Member') && !hasAnyRole(['Admin', 'Executive', 'Patron'])) {
    // Regular members can only view their own profile
    $currentUserId = $_SESSION['user_id'];
    $currentMemberQuery = "SELECT id FROM members WHERE user_id = :user_id";
    $stmt = $db->prepare($currentMemberQuery);
    $stmt->bindParam(':user_id', $currentUserId);
    $stmt->execute();
    $currentMember = $stmt->fetch();
    
    if ($currentMember) {
        redirect('member_view.php?id=' . $currentMember['id']);
    } else {
        setFlashMessage('danger', 'Your member profile was not found');
        redirect('dashboard.php');
    }
}

// Get filter data
$institutionsQuery = "SELECT DISTINCT institution FROM members WHERE institution IS NOT NULL AND institution != '' ORDER BY institution ASC";
$institutionsStmt = $db->query($institutionsQuery);
$institutions = $institutionsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get voting regions
$votingRegionsQuery = "SELECT id, name FROM voting_regions ORDER BY name ASC";
$votingRegionsStmt = $db->query($votingRegionsQuery);
$votingRegions = $votingRegionsStmt->fetchAll();

// Get voting constituencies
$votingConstituenciesQuery = "SELECT id, name, voting_region_id FROM voting_constituencies ORDER BY name ASC";
$votingConstituenciesStmt = $db->query($votingConstituenciesQuery);
$votingConstituencies = $votingConstituenciesStmt->fetchAll();

// Handle filters
$filters = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize($_GET['search']);
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['membership_status'] = sanitize($_GET['status']);
}
if (isset($_GET['position']) && !empty($_GET['position'])) {
    $filters['position'] = sanitize($_GET['position']);
}
if (isset($_GET['institution']) && !empty($_GET['institution'])) {
    $filters['institution'] = sanitize($_GET['institution']);
}
if (isset($_GET['voting_region_id']) && !empty($_GET['voting_region_id'])) {
    $filters['voting_region_id'] = (int)$_GET['voting_region_id'];
}
if (isset($_GET['voting_constituency_id']) && !empty($_GET['voting_constituency_id'])) {
    $filters['voting_constituency_id'] = (int)$_GET['voting_constituency_id'];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $recordsPerPage;

// Get members
$members = $member->getAll($recordsPerPage, $offset, $filters);
$totalMembers = $member->count($filters);
$pagination = paginate($totalMembers, $page, $recordsPerPage);

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Members Directory</h2>
        <p class="text-muted">Browse and manage TESCON members</p>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
        <a href="member_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Member
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Filter Members</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" placeholder="Search by name, student ID, phone, institution..." value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Suspended" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                    <option value="Graduated" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Position</label>
                <select class="form-select" name="position">
                    <option value="">All Positions</option>
                    <option value="Member" <?php echo (isset($filters['position']) && $filters['position'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                    <option value="Executive" <?php echo (isset($filters['position']) && $filters['position'] == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                    <option value="Patron" <?php echo (isset($filters['position']) && $filters['position'] == 'Patron') ? 'selected' : ''; ?>>Patron</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Institution</label>
                <select class="form-select" name="institution">
                    <option value="">All Institutions</option>
                    <?php foreach ($institutions as $inst): ?>
                        <option value="<?php echo htmlspecialchars($inst); ?>" <?php echo (isset($filters['institution']) && $filters['institution'] == $inst) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($inst); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Voting Region</label>
                <select class="form-select" name="voting_region_id" id="votingRegionFilter">
                    <option value="">All Voting Regions</option>
                    <?php foreach ($votingRegions as $vr): ?>
                        <option value="<?php echo $vr['id']; ?>" <?php echo (isset($filters['voting_region_id']) && $filters['voting_region_id'] == $vr['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vr['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Voting Constituency</label>
                <select class="form-select" name="voting_constituency_id" id="votingConstituencyFilter">
                    <option value="">All Voting Constituencies</option>
                    <?php foreach ($votingConstituencies as $vc): ?>
                        <option value="<?php echo $vc['id']; ?>" 
                                data-region-id="<?php echo $vc['voting_region_id']; ?>"
                                <?php echo (isset($filters['voting_constituency_id']) && $filters['voting_constituency_id'] == $vc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vc['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="cil-filter"></i> Apply Filters
                </button>
            </div>
        </form>
        
        <?php if (!empty($filters)): ?>
        <div class="mt-3">
            <strong>Active Filters:</strong>
            <?php if (isset($filters['search'])): ?>
                <span class="badge bg-info me-1">Search: <?php echo htmlspecialchars($filters['search']); ?></span>
            <?php endif; ?>
            <?php if (isset($filters['membership_status'])): ?>
                <span class="badge bg-success me-1">Status: <?php echo htmlspecialchars($filters['membership_status']); ?></span>
            <?php endif; ?>
            <?php if (isset($filters['position'])): ?>
                <span class="badge bg-warning me-1">Position: <?php echo htmlspecialchars($filters['position']); ?></span>
            <?php endif; ?>
            <?php if (isset($filters['institution'])): ?>
                <span class="badge bg-primary me-1">Institution: <?php echo htmlspecialchars($filters['institution']); ?></span>
            <?php endif; ?>
            <?php if (isset($filters['voting_region_id'])): ?>
                <?php 
                $selectedVotingRegion = array_filter($votingRegions, function($vr) use ($filters) {
                    return $vr['id'] == $filters['voting_region_id'];
                });
                $selectedVotingRegion = reset($selectedVotingRegion);
                ?>
                <span class="badge bg-secondary me-1">Voting Region: <?php echo htmlspecialchars($selectedVotingRegion['name']); ?></span>
            <?php endif; ?>
            <?php if (isset($filters['voting_constituency_id'])): ?>
                <?php 
                $selectedVotingConstituency = array_filter($votingConstituencies, function($vc) use ($filters) {
                    return $vc['id'] == $filters['voting_constituency_id'];
                });
                $selectedVotingConstituency = reset($selectedVotingConstituency);
                ?>
                <span class="badge bg-dark me-1">Voting Constituency: <?php echo htmlspecialchars($selectedVotingConstituency['name']); ?></span>
            <?php endif; ?>
            <a href="members.php" class="btn btn-sm btn-outline-danger ms-2">
                <i class="cil-x"></i> Clear All
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Members List</strong>
        <span class="badge bg-primary ms-2"><?php echo number_format($totalMembers); ?> Total</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Institution</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No members found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($m['photo'])): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($m['photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($m['fullname']); ?>" 
                                                 class="rounded-circle me-2" 
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="avatar-initials me-2" style="width: 32px; height: 32px; font-size: 0.875rem;">
                                                <?php echo getInitials($m['fullname']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($m['fullname']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($m['phone']); ?></td>
                                <td><?php echo htmlspecialchars($m['institution']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $m['position'] == 'Executive' ? 'warning' : ($m['position'] == 'Patron' ? 'info' : 'secondary'); ?>">
                                        <?php echo $m['position']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($m['membership_status']); ?>">
                                        <?php echo $m['membership_status']; ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a href="member_view.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info" title="View Profile">
                                        <i class="cil-eye"></i>
                                    </a>
                                    <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                                    <a href="member_edit.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasRole('Admin')): ?>
                                        <a href="member_delete.php?id=<?php echo $m['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this member?')">
                                            <i class="cil-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="mt-3">
                <?php echo generatePaginationHTML($pagination, 'members.php'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Dynamic filtering of voting constituencies based on voting region selection
document.getElementById('votingRegionFilter').addEventListener('change', function() {
    const selectedRegionId = this.value;
    const constituencySelect = document.getElementById('votingConstituencyFilter');
    const allOptions = constituencySelect.querySelectorAll('option');
    
    // Show all options first
    allOptions.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block';
            return;
        }
        
        const optionRegionId = option.getAttribute('data-region-id');
        
        if (selectedRegionId === '') {
            // Show all if no region selected
            option.style.display = 'block';
        } else if (optionRegionId === selectedRegionId) {
            // Show only constituencies from selected region
            option.style.display = 'block';
        } else {
            // Hide constituencies from other regions
            option.style.display = 'none';
        }
    });
    
    // Reset constituency selection if current selection is now hidden
    const currentSelection = constituencySelect.value;
    if (currentSelection) {
        const currentOption = constituencySelect.querySelector(`option[value="${currentSelection}"]`);
        if (currentOption && currentOption.style.display === 'none') {
            constituencySelect.value = '';
        }
    }
});

// Trigger on page load to filter based on pre-selected region
window.addEventListener('DOMContentLoaded', function() {
    const regionFilter = document.getElementById('votingRegionFilter');
    if (regionFilter.value) {
        regionFilter.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>
