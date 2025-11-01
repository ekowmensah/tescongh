<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';

// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Campuses';

$database = new Database();
$db = $database->getConnection();

$campus = new Campus($db);
$campuses = $campus->getAll();

// Debug: Check if campuses are loaded
error_log("Total campuses retrieved: " . count($campuses));
if (!empty($campuses)) {
    error_log("First campus: " . json_encode($campuses[0]));
    error_log("Last campus: " . json_encode($campuses[count($campuses) - 1]));
}
if (empty($campuses)) {
    error_log("No campuses found in database");
}

// Get member and executive counts for each campus
foreach ($campuses as &$c) {
    // Get total members
    $memberQuery = "SELECT COUNT(*) as total FROM members WHERE campus_id = :campus_id";
    $memberStmt = $db->prepare($memberQuery);
    $memberStmt->bindParam(':campus_id', $c['id']);
    $memberStmt->execute();
    $memberCount = $memberStmt->fetch();
    $c['member_count'] = $memberCount['total'];
    
    // Get executives count
    $execQuery = "SELECT COUNT(*) as total FROM campus_executives WHERE campus_id = :campus_id AND is_current = 1";
    $execStmt = $db->prepare($execQuery);
    $execStmt->bindParam(':campus_id', $c['id']);
    $execStmt->execute();
    $execCount = $execStmt->fetch();
    $c['executive_count'] = $execCount['total'];
    
    // Get patrons count
    $patronQuery = "SELECT COUNT(*) as total FROM members WHERE campus_id = :campus_id AND position = 'Patron'";
    $patronStmt = $db->prepare($patronQuery);
    $patronStmt->bindParam(':campus_id', $c['id']);
    $patronStmt->execute();
    $patronCount = $patronStmt->fetch();
    $c['patron_count'] = $patronCount['total'];
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Campuses</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="campus_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Campus
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Campuses List</strong>
        <span class="badge bg-primary ms-2"><?php echo count($campuses); ?> Total</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Campus Name</th>
                        <th>Institution</th>
                        <th>Location</th>
                        <th>Region</th>
                        <th>Members</th>
                        <th>Executives</th>
                        <th>Patrons</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campuses)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="cil-info" style="font-size: 48px;"></i>
                                <p class="mt-3">No campuses found</p>
                                <a href="campus_add.php" class="btn btn-primary">Add First Campus</a>
                            </td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($campuses as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['institution_name']); ?></td>
                            <td><?php echo htmlspecialchars($c['location']); ?></td>
                            <td><?php echo htmlspecialchars($c['region_name']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $c['member_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?php echo $c['executive_count']; ?>/11</span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $c['patron_count']; ?></span>
                            </td>
                            <td class="table-actions">
                                <a href="campus_view.php?id=<?php echo $c['id']; ?>" 
                                   class="btn btn-sm btn-info" 
                                   title="View Details">
                                    <i class="cil-eye"></i>
                                </a>
                                <a href="campus_assign_executive.php?campus_id=<?php echo $c['id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   title="Assign Executive">
                                    <i class="cil-star"></i>
                                </a>
                                <a href="campus_assign_patron.php?campus_id=<?php echo $c['id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Assign Patron">
                                    <i class="cil-user-follow"></i>
                                </a>
                                <?php if (hasRole('Admin')): ?>
                                    <a href="campus_edit.php?id=<?php echo $c['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <a href="campus_delete.php?id=<?php echo $c['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirmDelete('Are you sure you want to delete this campus? This will affect all associated members and executives.')">
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Ensure DataTables initializes correctly - placed after footer to ensure jQuery is loaded
$(document).ready(function() {
    // Destroy any existing DataTable instance first
    if ($.fn.DataTable.isDataTable('.datatable')) {
        $('.datatable').DataTable().destroy();
    }
    
    // Initialize DataTable
    $('.datatable').DataTable({
        pageLength: 20,
        responsive: true,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on Actions column
        ]
    });
});
</script>
