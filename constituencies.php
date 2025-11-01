<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Constituency.php';
require_once 'classes/Region.php';

if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Constituencies';

$database = new Database();
$db = $database->getConnection();

$constituency = new Constituency($db);
$regionObj = new Region($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $name = sanitize($_POST['name']);
            $region_id = (int)$_POST['region_id'];
            
            $result = $constituency->create($name, $region_id, $_SESSION['user_id']);
            if ($result['success']) {
                setFlashMessage('success', 'Constituency created successfully');
            } else {
                setFlashMessage('danger', 'Failed to create constituency');
            }
            redirect('constituencies.php');
        } elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $name = sanitize($_POST['name']);
            $region_id = (int)$_POST['region_id'];
            
            if ($constituency->update($id, $name, $region_id)) {
                setFlashMessage('success', 'Constituency updated successfully');
            } else {
                setFlashMessage('danger', 'Failed to update constituency');
            }
            redirect('constituencies.php');
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && hasRole('Admin')) {
    $id = (int)$_GET['delete'];
    
    // Check if constituency is being used by institutions
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM institutions WHERE constituency_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $institutionCount = $stmt->fetch()['count'];
    
    // Check if constituency is being used by campuses
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM campuses WHERE constituency_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $campusCount = $stmt->fetch()['count'];
    
    if ($institutionCount > 0 || $campusCount > 0) {
        $message = "Cannot delete constituency. It is being used by ";
        $parts = [];
        if ($institutionCount > 0) $parts[] = "{$institutionCount} institution(s)";
        if ($campusCount > 0) $parts[] = "{$campusCount} campus(es)";
        $message .= implode(' and ', $parts) . ". Please reassign or remove them first.";
        setFlashMessage('danger', $message);
    } else {
        if ($constituency->delete($id)) {
            setFlashMessage('success', 'Constituency deleted successfully');
        } else {
            setFlashMessage('danger', 'Failed to delete constituency.');
        }
    }
    redirect('constituencies.php');
}

// Get filter
$filterRegion = isset($_GET['region']) ? (int)$_GET['region'] : null;

// Get constituencies
if ($filterRegion) {
    $constituencies = $constituency->getByRegion($filterRegion);
} else {
    $constituencies = $constituency->getAll();
}

$regions = $regionObj->getAll();

// Get constituency for editing
$editConstituency = null;
if (isset($_GET['edit'])) {
    $editConstituency = $constituency->getById((int)$_GET['edit']);
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Constituencies</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#addConstituencyModal">
            <i class="cil-plus"></i> Add Constituency
        </button>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-3">
    <div class="card-header">
        <strong>Filter Constituencies</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <select class="form-select" name="region">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $reg): ?>
                        <option value="<?php echo $reg['id']; ?>" <?php echo ($filterRegion == $reg['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($reg['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Constituencies List -->
<div class="card">
    <div class="card-header">
        <strong>Constituencies List</strong>
        <span class="badge bg-primary ms-2"><?php echo count($constituencies); ?> <?php echo $filterRegion ? 'in selected region' : 'Total'; ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Constituency Name</th>
                        <th>Region</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($constituencies)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No constituencies found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($constituencies as $c): ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($c['region_name'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($c['created_at'], 'd M Y'); ?></td>
                                <td class="table-actions">
                                    <a href="?edit=<?php echo $c['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       data-coreui-toggle="modal" 
                                       data-coreui-target="#editConstituencyModal<?php echo $c['id']; ?>"
                                       title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                    <?php if (hasRole('Admin')): ?>
                                        <a href="constituencies.php?delete=<?php echo $c['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this constituency?')">
                                            <i class="cil-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal for each constituency -->
                            <div class="modal fade" id="editConstituencyModal<?php echo $c['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Constituency</h5>
                                                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Constituency Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($c['name']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Region <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="region_id" required>
                                                        <option value="">Select Region</option>
                                                        <?php foreach ($regions as $reg): ?>
                                                            <option value="<?php echo $reg['id']; ?>" <?php echo ($c['region_id'] == $reg['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($reg['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Constituency</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Constituency Modal -->
<div class="modal fade" id="addConstituencyModal" tabindex="-1" aria-labelledby="addConstituencyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addConstituencyModalLabel">Add New Constituency</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Region <span class="text-danger">*</span></label>
                        <select class="form-select" name="region_id" required>
                            <option value="">Select Region</option>
                            <?php foreach ($regions as $reg): ?>
                                <option value="<?php echo $reg['id']; ?>">
                                    <?php echo htmlspecialchars($reg['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Constituency Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Tema Central" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Constituency</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Statistics Card -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Constituency Statistics by Region</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    // Get constituency count by region
                    $query = "SELECT r.name as region_name, COUNT(c.id) as count 
                              FROM regions r 
                              LEFT JOIN constituencies c ON r.id = c.region_id 
                              GROUP BY r.id, r.name 
                              ORDER BY count DESC, r.name ASC";
                    $stmt = $db->query($query);
                    $stats = $stmt->fetchAll();
                    
                    foreach ($stats as $stat):
                    ?>
                        <div class="col-md-3 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title text-muted"><?php echo htmlspecialchars($stat['region_name']); ?></h6>
                                    <h3 class="mb-0"><?php echo $stat['count']; ?></h3>
                                    <small class="text-muted">Constituencies</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
