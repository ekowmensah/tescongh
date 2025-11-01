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

$pageTitle = 'Add Voting Region';

$database = new Database();
$db = $database->getConnection();

$votingRegion = new VotingRegion($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'code' => strtoupper(sanitize($_POST['code']))
    ];
    
    if ($votingRegion->create($data)) {
        setFlashMessage('success', 'Voting region added successfully');
        redirect('voting_regions.php');
    } else {
        setFlashMessage('danger', 'Failed to add voting region. Code or name may already exist.');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add Voting Region</h2>
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
                               placeholder="e.g., Greater Accra">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Region Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" required 
                               placeholder="e.g., GAR" maxlength="20">
                        <small class="text-muted">Short code for the region (will be converted to uppercase)</small>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create Voting Region
                    </button>
                    <a href="voting_regions.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info mt-3">
    <strong><i class="cil-info"></i> Note:</strong> 
    Ghana has 16 voting regions. Only add new regions if the Electoral Commission creates them.
</div>

<?php include 'includes/footer.php'; ?>
