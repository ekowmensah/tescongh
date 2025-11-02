<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'User Management';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== $_SESSION['user_id']) { // Prevent self-deletion
        if ($user->delete($id)) {
            setFlashMessage('success', 'User deleted successfully');
        } else {
            setFlashMessage('danger', 'Failed to delete user');
        }
    } else {
        setFlashMessage('danger', 'You cannot delete your own account');
    }
    redirect('users.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$recordsPerPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $recordsPerPage;

// Get only Admin users with member names
$query = "SELECT u.id, u.email, u.role, u.status, u.email_verified, u.phone_verified, u.last_login, u.created_at,
              m.fullname as member_name
          FROM users u
          LEFT JOIN members m ON u.id = m.user_id
          WHERE u.role = 'Admin'";

if (!empty($search)) {
    $query .= " AND (u.email LIKE :search OR m.fullname LIKE :search)";
}

$query .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

$stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Count Admin users
$countQuery = "SELECT COUNT(*) as total FROM users u WHERE u.role = 'Admin'";

if (!empty($search)) {
    $countQuery .= " AND (u.email LIKE :search OR EXISTS (SELECT 1 FROM members m WHERE m.user_id = u.id AND m.fullname LIKE :search))";
}

$countStmt = $db->prepare($countQuery);

if (!empty($search)) {
    $searchParam = "%$search%";
    $countStmt->bindParam(':search', $searchParam);
}

$countStmt->execute();
$totalUsers = $countStmt->fetch()['total'];
$pagination = paginate($totalUsers, $page, $recordsPerPage);

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Admin Users</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="user_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add User
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <strong>Search Users</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Admin Users List</strong>
        <span class="badge bg-danger ms-2"><?php echo number_format($totalUsers); ?> Admins</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Email Verified</th>
                        <th>Phone Verified</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No admin users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['member_name'] ?? 'N/A'); ?></strong>
                                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-info ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($u['status']); ?>">
                                        <?php echo $u['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['email_verified']): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['phone_verified']): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $u['last_login'] ? formatDateTime($u['last_login'], 'd M Y H:i') : 'Never'; ?>
                                </td>
                                <td><?php echo formatDate($u['created_at'], 'd M Y'); ?></td>
                                <td class="table-actions">
                                    <a href="user_edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $u['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this user?')">
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
                <?php echo generatePaginationHTML($pagination, 'users.php' . ($search ? '?search=' . urlencode($search) : '')); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
