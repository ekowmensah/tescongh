<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an executive or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle region creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_region'])) {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);

    if (empty($name)) {
        $error = 'Region name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO regions (name, code, created_by) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $code, $_SESSION['user_id']])) {
                $success = 'Region created successfully.';
            } else {
                $error = 'Failed to create region.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error = 'Region with this name already exists.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle constituency creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_constituency'])) {
    $name = trim($_POST['constituency_name']);
    $region_id = $_POST['region_id'];

    if (empty($name) || empty($region_id)) {
        $error = 'Constituency name and region are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO constituencies (name, region_id, created_by) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $region_id, $_SESSION['user_id']])) {
                $success = 'Constituency created successfully.';
            } else {
                $error = 'Failed to create constituency.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle region deletion
if (isset($_GET['delete_region'])) {
    $region_id = $_GET['delete_region'];
    try {
        $stmt = $pdo->prepare("DELETE FROM regions WHERE id = ?");
        if ($stmt->execute([$region_id])) {
            $success = 'Region deleted successfully.';
        } else {
            $error = 'Failed to delete region.';
        }
    } catch (PDOException $e) {
        $error = 'Cannot delete region with existing constituencies.';
    }
    // Redirect to avoid re-processing on refresh
    header('Location: location_management.php');
    exit();
}

// Handle constituency deletion
if (isset($_GET['delete_constituency'])) {
    $constituency_id = $_GET['delete_constituency'];
    try {
        $stmt = $pdo->prepare("DELETE FROM constituencies WHERE id = ?");
        if ($stmt->execute([$constituency_id])) {
            $success = 'Constituency deleted successfully.';
        } else {
            $error = 'Failed to delete constituency.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
    // Redirect to avoid re-processing on refresh
    header('Location: location_management.php');
    exit();
}

// Get all regions with constituency count
$regions = [];
try {
    $stmt = $pdo->query("
        SELECT r.*,
               COUNT(c.id) as constituency_count,
               u.fullname as created_by_name
        FROM regions r
        LEFT JOIN constituencies c ON r.id = c.region_id
        LEFT JOIN members u ON r.created_by = u.user_id
        GROUP BY r.id
        ORDER BY r.name
    ");
    $regions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get all constituencies with region info
$constituencies = [];
try {
    $stmt = $pdo->query("
        SELECT c.*,
               r.name as region_name,
               m.fullname as created_by_name
        FROM constituencies c
        JOIN regions r ON c.region_id = r.id
        LEFT JOIN members m ON c.created_by = m.user_id
        ORDER BY r.name, c.name
    ");
    $constituencies = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Management - UEW-TESCON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Location Management</h2>
            <div>
                <a href="members.php" class="btn btn-secondary me-2">Back to Members</a>
                <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Region -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Add New Region</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="create_region" value="1">
                            <div class="mb-3">
                                <label for="name" class="form-label">Region Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       placeholder="e.g., Greater Accra">
                            </div>
                            <div class="mb-3">
                                <label for="code" class="form-label">Region Code</label>
                                <input type="text" class="form-control" id="code" name="code" maxlength="10"
                                       placeholder="e.g., GAR">
                                <div class="form-text">Optional short code for the region</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Region</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Create Constituency -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add New Constituency</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="create_constituency" value="1">
                            <div class="mb-3">
                                <label for="region_id" class="form-label">Region *</label>
                                <select class="form-select" id="region_id" name="region_id" required>
                                    <option value="">Select Region</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['id']; ?>">
                                            <?php echo htmlspecialchars($region['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="constituency_name" class="form-label">Constituency Name *</label>
                                <input type="text" class="form-control" id="constituency_name" name="constituency_name" required
                                       placeholder="e.g., Tema Central">
                            </div>
                            <button type="submit" class="btn btn-success">Create Constituency</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regions List -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Regions (<?php echo count($regions); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Region Name</th>
                                <th>Code</th>
                                <th>Constituencies</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($regions as $region): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($region['name']); ?></td>
                                    <td><?php echo htmlspecialchars($region['code'] ?: '-'); ?></td>
                                    <td><?php echo $region['constituency_count']; ?> constituencies</td>
                                    <td><?php echo htmlspecialchars($region['created_by_name'] ?: 'System'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($region['created_at'])); ?></td>
                                    <td>
                                        <a href="?delete_region=<?php echo $region['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this region? This will also delete all constituencies in this region.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($regions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No regions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Constituencies List -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Constituencies (<?php echo count($constituencies); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Constituency Name</th>
                                <th>Region</th>
                                <th>Members Count</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($constituencies as $constituency): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($constituency['name']); ?></td>
                                    <td><?php echo htmlspecialchars($constituency['region_name']); ?></td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members WHERE constituency = ?");
                                        $stmt->execute([$constituency['name']]);
                                        echo $stmt->fetch()['count'];
                                        ?> members
                                    </td>
                                    <td><?php echo htmlspecialchars($constituency['created_by_name'] ?: 'System'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($constituency['created_at'])); ?></td>
                                    <td>
                                        <a href="?delete_constituency=<?php echo $constituency['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this constituency?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($constituencies)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No constituencies found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
