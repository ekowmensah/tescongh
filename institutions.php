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

$pageTitle = 'Institutions';

$database = new Database();
$db = $database->getConnection();

$institution = new Institution($db);
$regionObj = new Region($db);

$institutions = $institution->getAll();
$regions = $regionObj->getAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Institutions</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="institution_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Institution
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Institutions List</strong>
        <span class="badge bg-primary ms-2"><?php echo count($institutions); ?> Total</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Region</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($institutions as $inst): ?>
                        <tr>
                            <td><?php echo $inst['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($inst['name']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php echo $inst['type'] == 'University' ? 'primary' : 'secondary'; ?>">
                                    <?php echo $inst['type']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($inst['location'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($inst['region_name'] ?? 'N/A'); ?></td>
                            <td class="table-actions">
                                <a href="institution_view.php?id=<?php echo $inst['id']; ?>" class="btn btn-sm btn-info" title="View">
                                    <i class="cil-eye"></i>
                                </a>
                                <?php if (hasRole('Admin')): ?>
                                    <a href="institution_edit.php?id=<?php echo $inst['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="cil-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
