<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Campus.php';
require_once 'classes/Institution.php';
require_once 'classes/Region.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Edit Campus';

$database = new Database();
$db = $database->getConnection();

$campus = new Campus($db);
$institutionObj = new Institution($db);
$regionObj = new Region($db);

$institutions = $institutionObj->getAll();
$regions = $regionObj->getAll();

// Get campus ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Campus ID not provided');
    redirect('campuses.php');
}

$campusId = (int)$_GET['id'];
$campusData = $campus->getById($campusId);

if (!$campusData) {
    setFlashMessage('danger', 'Campus not found');
    redirect('campuses.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'institution_id' => (int)$_POST['institution_id'],
        'location' => sanitize($_POST['location']),
        'region_id' => (int)$_POST['region_id'],
        'constituency_id' => !empty($_POST['constituency_id']) ? (int)$_POST['constituency_id'] : null
    ];
    
    if ($campus->update($campusId, $data)) {
        setFlashMessage('success', 'Campus updated successfully');
        redirect('campuses.php');
    } else {
        setFlashMessage('danger', 'Failed to update campus');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Campus</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="campuses.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Campuses
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
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
                                    <option value="<?php echo $region['id']; ?>" <?php echo ($campusData['region_id'] == $region['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($region['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Constituency</label>
                            <select class="form-select" name="constituency_id" id="constituency_id">
                                <option value="">Loading...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Institution <span class="text-danger">*</span></label>
                        <select class="form-select" name="institution_id" id="institution_id" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Campus Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($campusData['name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($campusData['location']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Update Campus
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
// Load data on page load
window.addEventListener('DOMContentLoaded', function() {
    const regionId = document.getElementById('region_id').value;
    const currentConstituencyId = <?php echo $campusData['constituency_id'] ?? 'null'; ?>;
    const currentInstitutionId = <?php echo $campusData['institution_id']; ?>;
    
    if (regionId) {
        loadConstituencies(regionId, currentConstituencyId);
        loadInstitutions(regionId, currentConstituencyId, currentInstitutionId);
    }
});

// Load constituencies and institutions when region changes
document.getElementById('region_id').addEventListener('change', function() {
    const regionId = this.value;
    const constituencySelect = document.getElementById('constituency_id');
    const institutionSelect = document.getElementById('institution_id');
    
    if (regionId) {
        loadConstituencies(regionId, null);
        loadInstitutions(regionId, null, null);
    } else {
        constituencySelect.innerHTML = '<option value="">Select Region First</option>';
        institutionSelect.innerHTML = '<option value="">Select Region First</option>';
    }
});

// Load institutions when constituency changes
document.getElementById('constituency_id').addEventListener('change', function() {
    const regionId = document.getElementById('region_id').value;
    const constituencyId = this.value;
    
    if (regionId) {
        loadInstitutions(regionId, constituencyId, null);
    }
});

function loadConstituencies(regionId, selectedId) {
    const constituencySelect = document.getElementById('constituency_id');
    
    fetch('ajax/get_constituencies.php?region_id=' + regionId)
        .then(response => response.json())
        .then(data => {
            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
            data.forEach(constituency => {
                const option = document.createElement('option');
                option.value = constituency.id;
                option.textContent = constituency.name;
                if (selectedId && constituency.id == selectedId) {
                    option.selected = true;
                }
                constituencySelect.appendChild(option);
            });
        });
}

function loadInstitutions(regionId, constituencyId, selectedId) {
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
                if (selectedId && institution.id == selectedId) {
                    option.selected = true;
                }
                institutionSelect.appendChild(option);
            });
            
            if (data.length === 0) {
                institutionSelect.innerHTML = '<option value="">No institutions found in this area</option>';
            }
        });
}
</script>

<?php include 'includes/footer.php'; ?>
