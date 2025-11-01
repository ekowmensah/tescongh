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

$pageTitle = 'Add Voting Constituency';

$database = new Database();
$db = $database->getConnection();

$votingConstituency = new VotingConstituency($db);
$votingRegion = new VotingRegion($db);

// Get all voting regions
$votingRegions = $votingRegion->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'voting_region_id' => (int)$_POST['voting_region_id']
    ];
    
    if ($votingConstituency->create($data)) {
        setFlashMessage('success', 'Voting constituency added successfully');
        redirect('voting_constituencies.php');
    } else {
        setFlashMessage('danger', 'Failed to add voting constituency');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add Voting Constituency</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="voting_constituencies.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Voting Constituencies
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Voting Constituency Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Voting Region <span class="text-danger">*</span></label>
                        <select class="form-select" name="voting_region_id" required>
                            <option value="">Select Voting Region</option>
                            <?php foreach ($votingRegions as $vr): ?>
                                <option value="<?php echo $vr['id']; ?>">
                                    <?php echo htmlspecialchars($vr['name']); ?> (<?php echo htmlspecialchars($vr['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Constituency Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required 
                               placeholder="e.g., Accra Central">
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create Voting Constituency
                    </button>
                    <a href="voting_constituencies.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info mt-3">
    <strong><i class="cil-info"></i> Note:</strong> 
    Voting constituencies represent Ghana's 275+ parliamentary constituencies used for electoral purposes.
</div>

<?php include 'includes/footer.php'; ?>
