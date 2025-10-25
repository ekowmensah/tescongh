<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Institution.php';

$pageTitle = 'Institution Details';

$database = new Database();
$db = $database->getConnection();

$institutionObj = new Institution($db);

// Get institution ID
if (!isset($_GET['id'])) {
    setFlashMessage('danger', 'Institution ID not provided');
    redirect('institutions.php');
}

$institutionId = (int)$_GET['id'];
$institution = $institutionObj->getById($institutionId);

if (!$institution) {
    setFlashMessage('danger', 'Institution not found');
    redirect('institutions.php');
}

// Get campuses
$campusesQuery = "SELECT c.*, r.name as region_name, con.name as constituency_name
                  FROM campuses c
                  LEFT JOIN regions r ON c.region_id = r.id
                  LEFT JOIN constituencies con ON c.constituency_id = con.id
                  WHERE c.institution_id = :institution_id
                  ORDER BY c.name ASC";
$campusesStmt = $db->prepare($campusesQuery);
$campusesStmt->bindParam(':institution_id', $institutionId);
$campusesStmt->execute();
$campuses = $campusesStmt->fetchAll();

// Get statistics
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM campuses WHERE institution_id = :inst_id) as total_campuses,
                (SELECT COUNT(*) FROM members m 
                 INNER JOIN campuses c ON m.campus_id = c.id 
                 WHERE c.institution_id = :inst_id2) as total_members,
                (SELECT COUNT(*) FROM campus_executives ce
                 INNER JOIN campuses c ON ce.campus_id = c.id
                 WHERE c.institution_id = :inst_id3 AND ce.is_current = 1) as total_executives";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':inst_id', $institutionId);
$statsStmt->bindParam(':inst_id2', $institutionId);
$statsStmt->bindParam(':inst_id3', $institutionId);
$statsStmt->execute();
$stats = $statsStmt->fetch();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Institution Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasRole('Admin')): ?>
        <a href="institution_edit.php?id=<?php echo $institutionId; ?>" class="btn btn-warning">
            <i class="cil-pencil"></i> Edit Institution
        </a>
        <?php endif; ?>
        <a href="institutions.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Institutions
        </a>
    </div>
</div>

<!-- Institution Header -->
<div class="card mb-4 border-0 shadow-lg">
    <div class="card-body p-5">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <?php if (!empty($institution['logo'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($institution['logo']); ?>" 
                         alt="Institution Logo" 
                         class="img-fluid rounded" 
                         style="max-height: 120px;">
                <?php else: ?>
                    <div class="bg-gradient-primary text-white rounded d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px; font-size: 48px;">
                        <?php echo strtoupper(substr($institution['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-10">
                <h2 class="mb-3"><?php echo htmlspecialchars($institution['name']); ?></h2>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <i class="cil-education text-primary"></i> 
                        <strong>Type:</strong> 
                        <span class="badge bg-primary"><?php echo htmlspecialchars($institution['type']); ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <i class="cil-location-pin text-success"></i> 
                        <strong>Location:</strong> <?php echo htmlspecialchars($institution['location'] ?? 'N/A'); ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <i class="cil-map text-info"></i> 
                        <strong>Region:</strong> <?php echo htmlspecialchars($institution['region_name']); ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <i class="cil-list text-warning"></i> 
                        <strong>Constituency:</strong> <?php echo htmlspecialchars($institution['constituency_name']); ?>
                    </div>
                </div>
                <?php if ($institution['website']): ?>
                <div class="mt-3">
                    <i class="cil-globe-alt"></i> 
                    <a href="<?php echo htmlspecialchars($institution['website']); ?>" target="_blank" class="text-decoration-none">
                        <?php echo htmlspecialchars($institution['website']); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="text-primary mb-2" style="font-size: 48px;">
                    <i class="cil-location-pin"></i>
                </div>
                <h2 class="mb-0"><?php echo $stats['total_campuses']; ?></h2>
                <p class="text-muted mb-0">Campuses</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="text-success mb-2" style="font-size: 48px;">
                    <i class="cil-people"></i>
                </div>
                <h2 class="mb-0"><?php echo $stats['total_members']; ?></h2>
                <p class="text-muted mb-0">Total Members</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="text-info mb-2" style="font-size: 48px;">
                    <i class="cil-star"></i>
                </div>
                <h2 class="mb-0"><?php echo $stats['total_executives']; ?></h2>
                <p class="text-muted mb-0">Executives</p>
            </div>
        </div>
    </div>
</div>

<!-- Campuses -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white">
        <strong><i class="cil-building"></i> Campuses</strong>
        <?php if (hasRole('Admin')): ?>
        <a href="campus_add.php?institution_id=<?php echo $institutionId; ?>" class="btn btn-sm btn-light float-end">
            <i class="cil-plus"></i> Add Campus
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($campuses)): ?>
            <div class="alert alert-info">
                <i class="cil-info"></i> No campuses added yet.
                <?php if (hasRole('Admin')): ?>
                <a href="campus_add.php?institution_id=<?php echo $institutionId; ?>" class="alert-link">Add first campus</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($campuses as $campus): ?>
                    <?php
                    // Get campus stats
                    $campusStatsQuery = "SELECT COUNT(*) as member_count FROM members WHERE campus_id = :campus_id";
                    $campusStatsStmt = $db->prepare($campusStatsQuery);
                    $campusStatsStmt->bindParam(':campus_id', $campus['id']);
                    $campusStatsStmt->execute();
                    $campusStats = $campusStatsStmt->fetch();
                    ?>
                    <div class="col-md-6 mb-3">
                        <div class="card border-start border-primary border-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title mb-2"><?php echo htmlspecialchars($campus['name']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="cil-location-pin"></i> <?php echo htmlspecialchars($campus['location']); ?>
                                        </p>
                                        <p class="text-muted mb-2">
                                            <i class="cil-map"></i> <?php echo htmlspecialchars($campus['region_name']); ?>, 
                                            <?php echo htmlspecialchars($campus['constituency_name']); ?>
                                        </p>
                                        <span class="badge bg-primary">
                                            <i class="cil-people"></i> <?php echo $campusStats['member_count']; ?> Members
                                        </span>
                                    </div>
                                    <div>
                                        <a href="campus_view.php?id=<?php echo $campus['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="cil-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
