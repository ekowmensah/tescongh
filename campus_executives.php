<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Campus Executives';

$database = new Database();
$db = $database->getConnection();

// Get filter
$filterCampusId = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : null;

// Get all campuses for filter
$campusQuery = "SELECT c.id, c.name, c.location, i.name as institution_name 
                FROM campuses c
                INNER JOIN institutions i ON c.institution_id = i.id
                ORDER BY i.name, c.name";
$campusStmt = $db->prepare($campusQuery);
$campusStmt->execute();
$campuses = $campusStmt->fetchAll();

// Get executives
$query = "SELECT 
            ce.id as assignment_id,
            ce.appointed_at,
            m.id as member_id,
            m.fullname,
            m.phone,
            m.student_id,
            u.email,
            p.name as position_name,
            p.level as position_level,
            c.name as campus_name,
            c.location as campus_location,
            i.name as institution_name
          FROM campus_executives ce
          INNER JOIN members m ON ce.member_id = m.id
          INNER JOIN users u ON m.user_id = u.id
          INNER JOIN positions p ON ce.position_id = p.id
          INNER JOIN campuses c ON ce.campus_id = c.id
          INNER JOIN institutions i ON c.institution_id = i.id
          WHERE 1=1";

if ($filterCampusId) {
    $query .= " AND ce.campus_id = :campus_id";
}

$query .= " ORDER BY c.name, p.level ASC";

$stmt = $db->prepare($query);

if ($filterCampusId) {
    $stmt->bindParam(':campus_id', $filterCampusId);
}

$stmt->execute();
$executives = $stmt->fetchAll();

// Group by campus
$executivesByCampus = [];
foreach ($executives as $exec) {
    $campusKey = $exec['campus_name'] . ' - ' . $exec['institution_name'];
    if (!isset($executivesByCampus[$campusKey])) {
        $executivesByCampus[$campusKey] = [];
    }
    $executivesByCampus[$campusKey][] = $exec;
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Campus Executives</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="executive_add.php" class="btn btn-primary">
            <i class="cil-plus"></i> Add Executive
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-3">
    <div class="card-header">
        <strong>Filter by Campus</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <select class="form-select" name="campus_id">
                    <option value="">All Campuses</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?php echo $campus['id']; ?>" <?php echo ($filterCampusId == $campus['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($campus['institution_name'] . ' - ' . $campus['name']); ?>
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

<!-- Executives by Campus -->
<?php if (empty($executivesByCampus)): ?>
    <div class="card">
        <div class="card-body text-center text-muted">
            <i class="cil-info" style="font-size: 48px;"></i>
            <p class="mt-3">No executives found</p>
            <a href="executive_add.php" class="btn btn-primary">Add First Executive</a>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($executivesByCampus as $campusName => $campusExecs): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><?php echo htmlspecialchars($campusName); ?></strong>
                <span class="badge bg-light text-dark ms-2"><?php echo count($campusExecs); ?> Executives</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Student ID</th>
                                <th>Appointed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campusExecs as $exec): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($exec['position_name']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($exec['fullname']); ?></strong></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($exec['phone']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($exec['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($exec['student_id']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($exec['appointed_at'])); ?></td>
                                    <td>
                                        <a href="member_view.php?id=<?php echo $exec['member_id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="View Profile">
                                            <i class="cil-user"></i>
                                        </a>
                                        <?php if (hasRole('Admin')): ?>
                                        <a href="?remove=<?php echo $exec['assignment_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Remove from Position"
                                           onclick="return confirm('Remove this executive from their position?')">
                                            <i class="cil-x"></i>
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
    <?php endforeach; ?>
<?php endif; ?>

<!-- Statistics -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>Executive Statistics</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Campuses</h6>
                                <h3 class="mb-0"><?php echo count($executivesByCampus); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Executives</h6>
                                <h3 class="mb-0"><?php echo count($executives); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Avg per Campus</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    echo count($executivesByCampus) > 0 
                                        ? round(count($executives) / count($executivesByCampus), 1) 
                                        : 0; 
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Vacant Positions</h6>
                                <h3 class="mb-0 text-warning">
                                    <?php 
                                    // Calculate vacant positions (11 positions per campus - assigned)
                                    $totalCampuses = count($campuses);
                                    $expectedPositions = $totalCampuses * 11; // 11 executive positions
                                    $vacant = $expectedPositions - count($executives);
                                    echo max(0, $vacant);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
