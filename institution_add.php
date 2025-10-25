<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Institution.php';
require_once 'classes/Region.php';
require_once 'classes/Constituency.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Add Institution';

$database = new Database();
$db = $database->getConnection();

$institution = new Institution($db);
$regionObj = new Region($db);
$constituencyObj = new Constituency($db);

$regions = $regionObj->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'type' => sanitize($_POST['type']),
        'location' => sanitize($_POST['location']),
        'website' => sanitize($_POST['website']),
        'region_id' => (int)$_POST['region_id'],
        'constituency_id' => (int)$_POST['constituency_id'],
        'created_by' => $_SESSION['user_id']
    ];
    
    $result = $institution->create($data);
    
    if ($result['success']) {
        setFlashMessage('success', 'Institution added successfully');
        redirect('institutions.php');
    } else {
        setFlashMessage('danger', 'Failed to add institution');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Institution</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="institutions.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Institutions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Institution Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Institution Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" required>
                                <option value="">Select Type</option>
                                <option value="University">University</option>
                                <option value="Polytechnic">Polytechnic</option>
                                <option value="College">College</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" name="website" placeholder="https://example.edu.gh">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Region <span class="text-danger">*</span></label>
                            <select class="form-select" name="region_id" id="region_id" required>
                                <option value="">Select Region</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo $region['id']; ?>">
                                        <?php echo htmlspecialchars($region['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Constituency <span class="text-danger">*</span></label>
                            <select class="form-select" name="constituency_id" id="constituency_id" required>
                                <option value="">Select Region First</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create Institution
                    </button>
                    <a href="institutions.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Load constituencies when region is selected
document.getElementById('region_id').addEventListener('change', function() {
    const regionId = this.value;
    const constituencySelect = document.getElementById('constituency_id');
    
    if (regionId) {
        fetch('ajax/get_constituencies.php?region_id=' + regionId)
            .then(response => response.json())
            .then(data => {
                constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
                data.forEach(constituency => {
                    const option = document.createElement('option');
                    option.value = constituency.id;
                    option.textContent = constituency.name;
                    constituencySelect.appendChild(option);
                });
            });
    } else {
        constituencySelect.innerHTML = '<option value="">Select Region First</option>';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
