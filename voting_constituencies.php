<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/VotingConstituency.php';
require_once 'classes/VotingRegion.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Voting Constituencies';

$database = new Database();
$db = $database->getConnection();

$votingConstituency = new VotingConstituency($db);
$votingRegion = new VotingRegion($db);

// Get filter
$filterRegionId = isset($_GET['region_id']) ? (int)$_GET['region_id'] : null;

// Get constituencies
$constituencies = $votingConstituency->getAll($filterRegionId);

// Get all regions for filter
$regions = $votingRegion->getAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Voting Constituencies</h2>
        <p class="text-muted">Electoral constituencies of Ghana (for voter registration)</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="voting_constituency_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Voting Constituency
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-header">
        <strong>Filter by Voting Region</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <select class="form-select" name="region_id" onchange="this.form.submit()">
                    <option value="">All Voting Regions</option>
                    <?php foreach ($regions as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo ($filterRegionId == $r['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <?php if ($filterRegionId): ?>
                    <a href="voting_constituencies.php" class="btn btn-secondary w-100">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Voting Constituencies (<?php echo count($constituencies); ?>)</strong>
    </div>
    <div class="card-body">
        <table class="table table-hover datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Constituency Name</th>
                    <th>Voting Region</th>
                    <th>Region Code</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($constituencies)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="cil-info" style="font-size: 48px;"></i>
                            <p class="mt-3">No voting constituencies found</p>
                            <a href="voting_constituency_add.php" class="btn btn-primary">Add First Voting Constituency</a>
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($constituencies as $vc): ?>
                    <tr>
                        <td><?php echo $vc['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($vc['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($vc['voting_region_name']); ?></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($vc['voting_region_code']); ?></span></td>
                        <td><?php echo formatDate($vc['created_at'], 'd M Y'); ?></td>
                        <td class="table-actions">
                            <a href="voting_constituency_edit.php?id=<?php echo $vc['id']; ?>" 
                               class="btn btn-sm btn-warning" 
                               title="Edit">
                                <i class="cil-pencil"></i>
                            </a>
                            <a href="voting_constituency_delete.php?id=<?php echo $vc['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Delete"
                               onclick="return confirmDelete('Are you sure you want to delete this voting constituency?')">
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
    Voting constituencies represent Ghana's 275+ parliamentary constituencies. These are separate from campus location constituencies and are used for tracking where members are registered to vote.
</div>

<?php include 'includes/footer.php'; ?>
