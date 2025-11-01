<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';
require_once 'classes/Institution.php';
require_once 'classes/Region.php';
require_once 'classes/Constituency.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Add Campus';

$database = new Database();
$db = $database->getConnection();

$campus = new Campus($db);
$institutionObj = new Institution($db);
$regionObj = new Region($db);

$institutions = $institutionObj->getAll();
$regions = $regionObj->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'institution_id' => (int)$_POST['institution_id'],
        'location' => sanitize($_POST['location']),
        'region_id' => (int)$_POST['region_id'],
        'constituency_id' => !empty($_POST['constituency_id']) ? (int)$_POST['constituency_id'] : null,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Debug log
    error_log("Creating campus with data: " . json_encode($data));
    
    $result = $campus->create($data);
    
    // Debug log
    error_log("Campus create result: " . json_encode($result));
    
    if ($result['success']) {
        $newCampusId = $result['id'];
        error_log("New campus created with ID: " . $newCampusId);
        setFlashMessage('success', 'Campus added successfully (ID: ' . $newCampusId . ')');
        // Add cache busting parameter
        redirect('campuses.php?refresh=' . time());
    } else {
        $errorMsg = isset($result['error']) ? $result['error'] : 'Unknown error';
        error_log("Failed to create campus: " . $errorMsg);
        setFlashMessage('danger', 'Failed to add campus: ' . $errorMsg);
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Campus</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="campuses.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Campuses
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="" autocomplete="off">
            <div class="card">
                <div class="card-header">
                    <strong>Campus Details</strong>
                </div>
                <div class="card-body">
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
                            <label class="form-label">Constituency</label>
                            <select class="form-select" name="constituency_id" id="constituency_id">
                                <option value="">Select Region First</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Institution <span class="text-danger">*</span></label>
                        <select class="form-select" name="institution_id" id="institution_id" required>
                            <option value="">Select Region & Constituency First</option>
                        </select>
                        <small class="text-muted">Institutions will be filtered based on selected region and constituency</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Campus Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Main Campus" required autocomplete="off">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location" required autocomplete="off">
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create Campus
                    </button>
                    <a href="campuses.php" class="btn btn-secondary">
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
    const institutionSelect = document.getElementById('institution_id');
    
    if (regionId) {
        // Load constituencies
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
        
        // Load institutions for this region
        loadInstitutions(regionId, null);
    } else {
        constituencySelect.innerHTML = '<option value="">Select Region First</option>';
        institutionSelect.innerHTML = '<option value="">Select Region First</option>';
    }
});

// Load institutions when constituency is selected
document.getElementById('constituency_id').addEventListener('change', function() {
    const regionId = document.getElementById('region_id').value;
    const constituencyId = this.value;
    
    if (regionId) {
        loadInstitutions(regionId, constituencyId);
    }
});

// Function to load institutions based on region and constituency
function loadInstitutions(regionId, constituencyId) {
    const institutionSelect = document.getElementById('institution_id');
    let url = 'ajax/get_institutions.php?region_id=' + regionId;
    if (constituencyId) {
        url += '&constituency_id=' + constituencyId;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            institutionSelect.innerHTML = '<option value="">Select Institution</option>';
            data.forEach(institution => {
                const option = document.createElement('option');
                option.value = institution.id;
                option.textContent = institution.name;
                institutionSelect.appendChild(option);
            });
            
            if (data.length === 0) {
                institutionSelect.innerHTML = '<option value="">No institutions found in this area</option>';
            }
        });
}
</script>

<?php include 'includes/footer.php'; ?>
