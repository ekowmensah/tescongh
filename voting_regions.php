<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/VotingRegion.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Voting Regions';

$database = new Database();
$db = $database->getConnection();

$votingRegion = new VotingRegion($db);
$votingRegions = $votingRegion->getAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Voting Regions</h2>
        <p class="text-muted">Electoral regions of Ghana (for voter registration)</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="voting_region_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Voting Region
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>All Voting Regions (<?php echo count($votingRegions); ?>)</strong>
    </div>
    <div class="card-body">
        <table class="table table-hover datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Region Name</th>
                    <th>Code</th>
                    <th>Constituencies</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($votingRegions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="cil-info" style="font-size: 48px;"></i>
                            <p class="mt-3">No voting regions found</p>
                            <a href="voting_region_add.php" class="btn btn-primary">Add First Voting Region</a>
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($votingRegions as $vr): ?>
                    <?php
                    // Get constituency count
                    $countQuery = "SELECT COUNT(*) as total FROM voting_constituencies WHERE voting_region_id = :id";
                    $countStmt = $db->prepare($countQuery);
                    $countStmt->bindParam(':id', $vr['id']);
                    $countStmt->execute();
                    $count = $countStmt->fetch();
                    ?>
                    <tr>
                        <td><?php echo $vr['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($vr['name']); ?></strong></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($vr['code']); ?></span></td>
                        <td>
                            <a href="voting_constituencies.php?region_id=<?php echo $vr['id']; ?>" class="badge bg-primary">
                                <?php echo $count['total']; ?> constituencies
                            </a>
                        </td>
                        <td><?php echo formatDate($vr['created_at'], 'd M Y'); ?></td>
                        <td class="table-actions">
                            <a href="voting_region_edit.php?id=<?php echo $vr['id']; ?>" 
                               class="btn btn-sm btn-warning" 
                               title="Edit">
                                <i class="cil-pencil"></i>
                            </a>
                            <a href="voting_region_delete.php?id=<?php echo $vr['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Delete"
                               onclick="return confirmDelete('Are you sure you want to delete this voting region?')">
                                <i class="cil-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-3">
    <strong><i class="cil-info"></i> Note:</strong> 
    Voting regions represent Ghana's 16 electoral regions. These are separate from campus location regions and are used for tracking where members are registered to vote.
</div>

<?php include 'includes/footer.php'; ?>
