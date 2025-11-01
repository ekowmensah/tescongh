<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!hasAnyRole(['Admin', 'Executive', 'Patron'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Patrons';

$database = new Database();
$db = $database->getConnection();

// Get all patrons
$query = "SELECT 
            m.id,
            m.fullname,
            m.phone,
            m.npp_position,
            m.region,
            m.constituency,
            m.membership_status,
            u.email,
            c.name as campus_name,
            i.name as institution_name
          FROM members m
          INNER JOIN users u ON m.user_id = u.id
          LEFT JOIN campuses c ON m.campus_id = c.id
          LEFT JOIN institutions i ON c.institution_id = i.id
          WHERE m.position = 'Patron'
          ORDER BY m.fullname ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$patrons = $stmt->fetchAll();

// Get statistics
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM members WHERE position = 'Patron' AND membership_status = 'Active') as active_patrons,
                (SELECT COUNT(*) FROM members WHERE position = 'Patron' AND membership_status = 'Inactive') as inactive_patrons,
                (SELECT COUNT(DISTINCT campus_id) FROM members WHERE position = 'Patron' AND campus_id IS NOT NULL) as campuses_with_patrons";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Patrons</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
        <a href="patron_add.php" class="btn btn-primary">
            <i class="cil-user-plus"></i> Add Patron
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Total Patrons</h6>
                <h2 class="mb-0"><?php echo count($patrons); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Active Patrons</h6>
                <h2 class="mb-0 text-success"><?php echo $stats['active_patrons']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Campuses with Patrons</h6>
                <h2 class="mb-0 text-info"><?php echo $stats['campuses_with_patrons']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Patrons List -->
<div class="card">
    <div class="card-header">
        <strong>Patrons List</strong>
        <span class="badge bg-primary ms-2"><?php echo count($patrons); ?> Total</span>
    </div>
    <div class="card-body">
        <?php if (empty($patrons)): ?>
            <div class="alert alert-info text-center">
                <i class="cil-info" style="font-size: 48px;"></i>
                <p class="mt-3">No patrons found</p>
                <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                <a href="patron_add.php" class="btn btn-primary">Add First Patron</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Campus/Institution</th>
                            <th>Region</th>
                            <th>NPP Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patrons as $patron): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($patron['fullname']); ?></strong></td>
                                <td>
                                    <div><?php echo htmlspecialchars($patron['phone']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($patron['email']); ?></small>
                                </td>
                                <td>
                                    <?php if ($patron['campus_name']): ?>
                                        <div><strong><?php echo htmlspecialchars($patron['institution_name']); ?></strong></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($patron['campus_name']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patron['region']): ?>
                                        <div><?php echo htmlspecialchars($patron['region']); ?></div>
                                        <?php if ($patron['constituency']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($patron['constituency']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patron['npp_position']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo htmlspecialchars($patron['npp_position']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $patron['membership_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($patron['membership_status']); ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a href="member_view.php?id=<?php echo $patron['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Profile">
                                        <i class="cil-user"></i>
                                    </a>
                                    <?php if (hasRole('Admin')): ?>
                                    <a href="member_edit.php?id=<?php echo $patron['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <a href="member_delete.php?id=<?php echo $patron['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirmDelete('Are you sure you want to delete this patron?')">
                                        <i class="cil-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
