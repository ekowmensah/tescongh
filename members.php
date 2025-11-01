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
        <h2>Members</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="member_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Member
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Filter Members</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email, phone..." value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Suspended" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                    <option value="Graduated" <?php echo (isset($filters['membership_status']) && $filters['membership_status'] == 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="position">
                    <option value="">All Positions</option>
                    <option value="Member" <?php echo (isset($filters['position']) && $filters['position'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                    <option value="Executive" <?php echo (isset($filters['position']) && $filters['position'] == 'Executive') ? 'selected' : ''; ?>>Executive</option>
                    <option value="Patron" <?php echo (isset($filters['position']) && $filters['position'] == 'Patron') ? 'selected' : ''; ?>>Patron</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
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
                                    <a href="member_view.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="cil-eye"></i>
                                    </a>
                                    <a href="member_edit.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
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

<?php include 'includes/footer.php'; ?>
