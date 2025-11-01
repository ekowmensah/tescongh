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

$pageTitle = 'Edit Voting Region';

$database = new Database();
$db = $database->getConnection();

$votingRegion = new VotingRegion($db);

// Get region ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Voting region ID not provided');
    redirect('voting_regions.php');
}

$id = (int)$_GET['id'];
$region = $votingRegion->getById($id);

if (!$region) {
    setFlashMessage('danger', 'Voting region not found');
    redirect('voting_regions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'code' => strtoupper(sanitize($_POST['code']))
    ];
    
    if ($votingRegion->update($id, $data)) {
        setFlashMessage('success', 'Voting region updated successfully');
        redirect('voting_regions.php');
    } else {
        setFlashMessage('danger', 'Failed to update voting region. Code or name may already exist.');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Voting Region</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="voting_regions.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Voting Regions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Voting Region Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Region Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required 
                               value="<?php echo htmlspecialchars($region['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Region Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" required 
                               value="<?php echo htmlspecialchars($region['code']); ?>" maxlength="20">
                        <small class="text-muted">Short code for the region (will be converted to uppercase)</small>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-save"></i> Update Voting Region
                    </button>
                    <a href="voting_regions.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
