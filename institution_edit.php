<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Institution.php';
require_once 'classes/Region.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Edit Institution';

$database = new Database();
$db = $database->getConnection();

$institution = new Institution($db);
$regionObj = new Region($db);

$regions = $regionObj->getAll();

// Get institution ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Institution ID not provided');
    redirect('institutions.php');
}

$instId = (int)$_GET['id'];
$instData = $institution->getById($instId);

if (!$instData) {
    setFlashMessage('danger', 'Institution not found');
    redirect('institutions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name']),
        'type' => sanitize($_POST['type']),
        'location' => sanitize($_POST['location']),
        'website' => sanitize($_POST['website']),
        'region_id' => (int)$_POST['region_id'],
        'constituency_id' => !empty($_POST['constituency_id']) ? (int)$_POST['constituency_id'] : null
    ];
    
    if ($institution->update($instId, $data)) {
        setFlashMessage('success', 'Institution updated successfully');
        redirect('institutions.php');
    } else {
        setFlashMessage('danger', 'Failed to update institution');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Institution</h2>
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
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($instData['name']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" required>
                                <option value="">Select Type</option>
                                <option value="University" <?php echo ($instData['type'] == 'University') ? 'selected' : ''; ?>>University</option>
                                <option value="Polytechnic" <?php echo ($instData['type'] == 'Polytechnic') ? 'selected' : ''; ?>>Polytechnic</option>
                                <option value="College" <?php echo ($instData['type'] == 'College') ? 'selected' : ''; ?>>College</option>
                                <option value="Other" <?php echo ($instData['type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($instData['location'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($instData['website'] ?? ''); ?>" placeholder="https://example.edu.gh">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Region <span class="text-danger">*</span></label>
                            <select class="form-select" name="region_id" id="region_id" required>
                                <option value="">Select Region</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo $region['id']; ?>" <?php echo ($instData['region_id'] == $region['id']) ? 'selected' : ''; ?>>
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
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Update Institution
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
// Load constituencies on page load
window.addEventListener('DOMContentLoaded', function() {
    const regionId = document.getElementById('region_id').value;
    const currentConstituencyId = <?php echo $instData['constituency_id'] ?? 'null'; ?>;
    
    if (regionId) {
        loadConstituencies(regionId, currentConstituencyId);
    }
});

// Load constituencies when region changes
document.getElementById('region_id').addEventListener('change', function() {
    const regionId = this.value;
    if (regionId) {
        loadConstituencies(regionId, null);
    } else {
        document.getElementById('constituency_id').innerHTML = '<option value="">Select Region First</option>';
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
</script>

<?php include 'includes/footer.php'; ?>
