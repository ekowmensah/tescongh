<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Only admins and executives can view reports
if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Voting Regions & Constituencies Report';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$selected_region = isset($_GET['region_id']) ? (int)$_GET['region_id'] : null;
$selected_constituency = isset($_GET['constituency_id']) ? (int)$_GET['constituency_id'] : null;

// Get all voting regions for filter
$regionsQuery = "SELECT * FROM voting_regions ORDER BY name ASC";
$regionsStmt = $db->query($regionsQuery);
$votingRegions = $regionsStmt->fetchAll();

// Build main query with filters
$query = "SELECT 
            m.id,
            m.fullname,
            m.student_id,
            m.phone,
            m.gender,
            m.institution,
            m.program,
            m.year,
            m.position,
            m.membership_status,
            vr.id as voting_region_id,
            vr.name as voting_region_name,
            vc.id as voting_constituency_id,
            vc.name as voting_constituency_name,
            c.name as campus_name
          FROM members m
          LEFT JOIN voting_regions vr ON m.voting_region_id = vr.id
          LEFT JOIN voting_constituencies vc ON m.voting_constituency_id = vc.id
          LEFT JOIN campuses c ON m.campus_id = c.id
          WHERE 1=1";

$params = [];

if ($selected_region) {
    $query .= " AND m.voting_region_id = :region_id";
    $params[':region_id'] = $selected_region;
}

if ($selected_constituency) {
    $query .= " AND m.voting_constituency_id = :constituency_id";
    $params[':constituency_id'] = $selected_constituency;
}

$query .= " ORDER BY vr.name ASC, vc.name ASC, m.fullname ASC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$members = $stmt->fetchAll();

// Group members by voting region and constituency
$groupedData = [];
$totalMembers = 0;

foreach ($members as $member) {
    $regionName = $member['voting_region_name'] ?? 'Not Specified';
    $constituencyName = $member['voting_constituency_name'] ?? 'Not Specified';
    
    if (!isset($groupedData[$regionName])) {
        $groupedData[$regionName] = [
            'region_id' => $member['voting_region_id'],
            'constituencies' => [],
            'total' => 0
        ];
    }
    
    if (!isset($groupedData[$regionName]['constituencies'][$constituencyName])) {
        $groupedData[$regionName]['constituencies'][$constituencyName] = [
            'constituency_id' => $member['voting_constituency_id'],
            'members' => []
        ];
    }
    
    $groupedData[$regionName]['constituencies'][$constituencyName]['members'][] = $member;
    $groupedData[$regionName]['total']++;
    $totalMembers++;
}

// Get summary statistics
$statsQuery = "SELECT 
                vr.name as region_name,
                COUNT(DISTINCT m.id) as member_count,
                COUNT(DISTINCT m.voting_constituency_id) as constituency_count
               FROM voting_regions vr
               LEFT JOIN members m ON vr.id = m.voting_region_id
               GROUP BY vr.id, vr.name
               ORDER BY member_count DESC";
$statsStmt = $db->query($statsQuery);
$regionStats = $statsStmt->fetchAll();

include 'includes/header.php';
?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.report-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.region-card {
    border-left: 4px solid #667eea;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.region-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.constituency-section {
    border-left: 3px solid #38ef7d;
    margin: 1rem 0;
    padding-left: 1rem;
}

.constituency-header {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    font-weight: 600;
    color: #495057;
    border-radius: 5px;
    margin-bottom: 0.5rem;
}

.member-row {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.member-row:last-child {
    border-bottom: none;
}

.stat-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.export-buttons {
    display: flex;
    gap: 0.5rem;
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .region-card {
        page-break-inside: avoid;
    }
}

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 2rem;
}

.search-box {
    position: relative;
}

.search-box input {
    padding-left: 2.5rem;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.stats-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stats-icon {
    font-size: 2.5rem;
    opacity: 0.2;
}

.filter-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #e7f3ff;
    border-radius: 20px;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.filter-badge i {
    cursor: pointer;
    margin-left: 0.5rem;
    color: #dc3545;
}

.loading-spinner {
    display: none;
    text-align: center;
    padding: 2rem;
}

.loading-spinner.active {
    display: block;
}

.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.highlight {
    background-color: #fff3cd;
    transition: background-color 0.3s;
}
</style>

<div class="report-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-2"><i class="cil-location-pin"></i> Voting Regions & Constituencies Report</h2>
            <p class="mb-0">Members organized by their voting registration locations</p>
        </div>
        <div class="text-end">
            <div class="display-4"><?php echo number_format($totalMembers); ?></div>
            <small>Total Members</small>
        </div>
    </div>
</div>

<!-- Real-time Search and Filters -->
<div class="card mb-4 no-print">
    <div class="card-header">
        <strong><i class="cil-search"></i> Search & Filters</strong>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Search Members</label>
                <div class="search-box">
                    <i class="cil-search"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name, student ID, phone, institution...">
                </div>
                <small class="text-muted">Real-time search across all fields</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Voting Region</label>
                <select class="form-select" id="regionFilter">
                    <option value="">All Regions</option>
                    <?php foreach ($votingRegions as $region): ?>
                        <option value="<?php echo $region['id']; ?>">
                            <?php echo htmlspecialchars($region['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Voting Constituency</label>
                <select class="form-select" id="constituencyFilter">
                    <option value="">All Constituencies</option>
                </select>
            </div>
        </div>
        
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select class="form-select" id="genderFilter">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Position</label>
                <select class="form-select" id="positionFilter">
                    <option value="">All Positions</option>
                    <option value="Member">Member</option>
                    <option value="Executive">Executive</option>
                    <option value="Patron">Patron</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Suspended">Suspended</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-danger w-100" id="clearFilters">
                    <i class="cil-x"></i> Clear All Filters
                </button>
            </div>
        </div>
        
        <!-- Active Filters Display -->
        <div id="activeFilters" class="mt-3" style="display: none;">
            <strong>Active Filters:</strong>
            <div id="filterBadges" class="mt-2"></div>
        </div>
        
        <!-- Results Counter -->
        <div class="mt-3">
            <span class="badge bg-primary" id="resultsCounter">Showing <?php echo $totalMembers; ?> members</span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card text-center">
            <div class="card-body">
                <div class="stats-icon text-primary">
                    <i class="cil-location-pin"></i>
                </div>
                <h3 class="text-primary mb-0"><?php echo count($votingRegions); ?></h3>
                <p class="text-muted mb-0">Voting Regions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-center">
            <div class="card-body">
                <div class="stats-icon text-success">
                    <i class="cil-map"></i>
                </div>
                <h3 class="text-success mb-0" id="totalConstituencies">0</h3>
                <p class="text-muted mb-0">Constituencies</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-center">
            <div class="card-body">
                <div class="stats-icon text-info">
                    <i class="cil-people"></i>
                </div>
                <h3 class="text-info mb-0" id="visibleMembers"><?php echo $totalMembers; ?></h3>
                <p class="text-muted mb-0">Visible Members</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card text-center">
            <div class="card-body">
                <div class="stats-icon text-warning">
                    <i class="cil-chart"></i>
                </div>
                <h3 class="text-warning mb-0" id="avgPerRegion">0</h3>
                <p class="text-muted mb-0">Avg per Region</p>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Charts -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-chart-pie"></i> Regional Distribution (Pie Chart)</strong>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-bar-chart"></i> Members by Region (Bar Chart)</strong>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gender Distribution Chart -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-chart"></i> Gender Distribution</strong>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 200px;">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-chart-line"></i> Position Distribution</strong>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 200px;">
                    <canvas id="positionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2">Filtering data...</p>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-chart-pie"></i> Regional Distribution</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Voting Region</th>
                                <th class="text-center">Constituencies</th>
                                <th class="text-center">Members</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($regionStats as $stat): ?>
                                <?php 
                                $percentage = $totalMembers > 0 ? ($stat['member_count'] / $totalMembers) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($stat['region_name']); ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $stat['constituency_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $stat['member_count']; ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%"
                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-file"></i> Export Options</strong>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="cil-print"></i> Print Report
                    </button>
                    <a href="export_voting_regions.php<?php echo $selected_region ? '?region_id='.$selected_region : ''; ?><?php echo $selected_constituency ? '&constituency_id='.$selected_constituency : ''; ?>" class="btn btn-success">
                        <i class="cil-spreadsheet"></i> Export to Excel
                    </a>
                    <a href="members.php" class="btn btn-secondary">
                        <i class="cil-arrow-left"></i> Back to Members
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Report -->
<?php if (empty($groupedData)): ?>
    <div class="alert alert-info">
        <i class="cil-info"></i> No members found with the selected filters.
    </div>
<?php else: ?>
    <?php foreach ($groupedData as $regionName => $regionData): ?>
        <div class="card region-card">
            <div class="region-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="cil-location-pin"></i> <?php echo htmlspecialchars($regionName); ?></span>
                    <span class="stat-badge bg-white text-primary">
                        <?php echo $regionData['total']; ?> <?php echo $regionData['total'] == 1 ? 'Member' : 'Members'; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php foreach ($regionData['constituencies'] as $constituencyName => $constituencyData): ?>
                    <div class="constituency-section">
                        <div class="constituency-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="cil-map"></i> <?php echo htmlspecialchars($constituencyName); ?></span>
                                <span class="badge bg-success">
                                    <?php echo count($constituencyData['members']); ?> <?php echo count($constituencyData['members']) == 1 ? 'Member' : 'Members'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Student ID</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                        <th>Institution</th>
                                        <th>Program</th>
                                        <th>Year</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($constituencyData['members'] as $member): ?>
                                        <tr class="member-row"
                                            data-name="<?php echo htmlspecialchars($member['fullname']); ?>"
                                            data-student-id="<?php echo htmlspecialchars($member['student_id']); ?>"
                                            data-phone="<?php echo htmlspecialchars($member['phone']); ?>"
                                            data-institution="<?php echo htmlspecialchars($member['institution']); ?>"
                                            data-region="<?php echo htmlspecialchars($member['voting_region_name'] ?? ''); ?>"
                                            data-region-id="<?php echo $member['voting_region_id'] ?? ''; ?>"
                                            data-constituency="<?php echo htmlspecialchars($member['voting_constituency_name'] ?? ''); ?>"
                                            data-constituency-id="<?php echo $member['voting_constituency_id'] ?? ''; ?>"
                                            data-gender="<?php echo htmlspecialchars($member['gender'] ?? ''); ?>"
                                            data-position="<?php echo htmlspecialchars($member['position']); ?>"
                                            data-status="<?php echo htmlspecialchars($member['membership_status']); ?>">
                                            <td>
                                                <a href="member_view.php?id=<?php echo $member['id']; ?>" class="text-decoration-none">
                                                    <strong><?php echo htmlspecialchars($member['fullname']); ?></strong>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['student_id']); ?></td>
                                            <td><small><?php echo htmlspecialchars($member['phone']); ?></small></td>
                                            <td><?php echo htmlspecialchars($member['gender'] ?? 'N/A'); ?></td>
                                            <td><small><?php echo htmlspecialchars($member['institution']); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($member['program']); ?></small></td>
                                            <td><span class="badge bg-secondary">Year <?php echo $member['year']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($member['position']); ?></span></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadgeClass($member['membership_status']); ?>">
                                                    <?php echo $member['membership_status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Store all members data for client-side filtering
const allMembers = <?php echo json_encode($members); ?>;
let pieChartInstance, barChartInstance, genderChartInstance, positionChartInstance;

// Initialize charts
function initializeCharts() {
    const regionData = {};
    const genderData = { Male: 0, Female: 0, 'Not Specified': 0 };
    const positionData = {};
    
    allMembers.forEach(member => {
        // Region data
        const region = member.voting_region_name || 'Not Specified';
        regionData[region] = (regionData[region] || 0) + 1;
        
        // Gender data
        const gender = member.gender || 'Not Specified';
        genderData[gender] = (genderData[gender] || 0) + 1;
        
        // Position data
        const position = member.position || 'Member';
        positionData[position] = (positionData[position] || 0) + 1;
    });
    
    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    pieChartInstance = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(regionData),
            datasets: [{
                data: Object.values(regionData),
                backgroundColor: [
                    '#667eea', '#764ba2', '#f093fb', '#4facfe',
                    '#43e97b', '#fa709a', '#fee140', '#30cfd0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                title: { display: false }
            }
        }
    });
    
    // Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    barChartInstance = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(regionData),
            datasets: [{
                label: 'Members',
                data: Object.values(regionData),
                backgroundColor: '#667eea',
                borderColor: '#764ba2',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    
    // Gender Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    genderChartInstance = new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(genderData),
            datasets: [{
                data: Object.values(genderData),
                backgroundColor: ['#4facfe', '#fa709a', '#cccccc']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    
    // Position Chart
    const positionCtx = document.getElementById('positionChart').getContext('2d');
    positionChartInstance = new Chart(positionCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(positionData),
            datasets: [{
                label: 'Count',
                data: Object.values(positionData),
                backgroundColor: ['#43e97b', '#667eea', '#fa709a']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            }
        }
    });
    
    updateStats();
}

// Update statistics
function updateStats() {
    const visibleRows = document.querySelectorAll('.member-row:not([style*="display: none"])');
    const visibleCount = visibleRows.length;
    
    document.getElementById('visibleMembers').textContent = visibleCount;
    document.getElementById('resultsCounter').textContent = `Showing ${visibleCount} members`;
    
    // Count unique constituencies
    const constituencies = new Set();
    visibleRows.forEach(row => {
        const constituency = row.getAttribute('data-constituency');
        if (constituency) constituencies.add(constituency);
    });
    document.getElementById('totalConstituencies').textContent = constituencies.size;
    
    // Calculate average per region
    const regions = new Set();
    visibleRows.forEach(row => {
        const region = row.getAttribute('data-region');
        if (region) regions.add(region);
    });
    const avg = regions.size > 0 ? Math.round(visibleCount / regions.size) : 0;
    document.getElementById('avgPerRegion').textContent = avg;
}

// Real-time search
document.getElementById('searchInput').addEventListener('input', function() {
    applyFilters();
});

// Filter change handlers
document.getElementById('regionFilter').addEventListener('change', function() {
    const regionId = this.value;
    const constituencySelect = document.getElementById('constituencyFilter');
    
    if (regionId) {
        fetch('api/get_voting_constituencies.php?region_id=' + regionId)
            .then(response => response.text())
            .then(html => {
                constituencySelect.innerHTML = '<option value="">All Constituencies</option>' + html;
            });
    } else {
        constituencySelect.innerHTML = '<option value="">All Constituencies</option>';
    }
    
    applyFilters();
});

document.getElementById('constituencyFilter').addEventListener('change', applyFilters);
document.getElementById('genderFilter').addEventListener('change', applyFilters);
document.getElementById('positionFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);

// Apply all filters
function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const regionFilter = document.getElementById('regionFilter').value;
    const constituencyFilter = document.getElementById('constituencyFilter').value;
    const genderFilter = document.getElementById('genderFilter').value;
    const positionFilter = document.getElementById('positionFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    // Show loading
    document.getElementById('loadingSpinner').classList.add('active');
    
    setTimeout(() => {
        const memberRows = document.querySelectorAll('.member-row');
        let visibleCount = 0;
        
        memberRows.forEach(row => {
            const name = row.getAttribute('data-name').toLowerCase();
            const studentId = row.getAttribute('data-student-id').toLowerCase();
            const phone = row.getAttribute('data-phone').toLowerCase();
            const institution = row.getAttribute('data-institution').toLowerCase();
            const region = row.getAttribute('data-region-id');
            const constituency = row.getAttribute('data-constituency-id');
            const gender = row.getAttribute('data-gender');
            const position = row.getAttribute('data-position');
            const status = row.getAttribute('data-status');
            
            let show = true;
            
            // Search filter
            if (searchTerm && !name.includes(searchTerm) && !studentId.includes(searchTerm) && 
                !phone.includes(searchTerm) && !institution.includes(searchTerm)) {
                show = false;
            }
            
            // Region filter
            if (regionFilter && region !== regionFilter) show = false;
            
            // Constituency filter
            if (constituencyFilter && constituency !== constituencyFilter) show = false;
            
            // Gender filter
            if (genderFilter && gender !== genderFilter) show = false;
            
            // Position filter
            if (positionFilter && position !== positionFilter) show = false;
            
            // Status filter
            if (statusFilter && status !== statusFilter) show = false;
            
            row.style.display = show ? '' : 'none';
            if (show) {
                visibleCount++;
                row.classList.add('fade-in');
            }
        });
        
        // Hide empty regions/constituencies
        document.querySelectorAll('.region-card').forEach(card => {
            const visibleInCard = card.querySelectorAll('.member-row:not([style*="display: none"])').length;
            card.style.display = visibleInCard > 0 ? '' : 'none';
        });
        
        document.querySelectorAll('.constituency-section').forEach(section => {
            const visibleInSection = section.querySelectorAll('.member-row:not([style*="display: none"])').length;
            section.style.display = visibleInSection > 0 ? '' : 'none';
        });
        
        updateStats();
        updateActiveFilters();
        document.getElementById('loadingSpinner').classList.remove('active');
    }, 100);
}

// Update active filters display
function updateActiveFilters() {
    const filters = [];
    const regionSelect = document.getElementById('regionFilter');
    const constituencySelect = document.getElementById('constituencyFilter');
    const genderSelect = document.getElementById('genderFilter');
    const positionSelect = document.getElementById('positionFilter');
    const statusSelect = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    
    if (regionSelect.value) filters.push({ name: 'Region', value: regionSelect.options[regionSelect.selectedIndex].text, id: 'regionFilter' });
    if (constituencySelect.value) filters.push({ name: 'Constituency', value: constituencySelect.options[constituencySelect.selectedIndex].text, id: 'constituencyFilter' });
    if (genderSelect.value) filters.push({ name: 'Gender', value: genderSelect.value, id: 'genderFilter' });
    if (positionSelect.value) filters.push({ name: 'Position', value: positionSelect.value, id: 'positionFilter' });
    if (statusSelect.value) filters.push({ name: 'Status', value: statusSelect.value, id: 'statusFilter' });
    if (searchInput.value) filters.push({ name: 'Search', value: searchInput.value, id: 'searchInput' });
    
    const activeFiltersDiv = document.getElementById('activeFilters');
    const filterBadges = document.getElementById('filterBadges');
    
    if (filters.length > 0) {
        activeFiltersDiv.style.display = 'block';
        filterBadges.innerHTML = filters.map(f => 
            `<span class="filter-badge">
                <strong>${f.name}:</strong> ${f.value}
                <i class="cil-x" onclick="clearFilter('${f.id}')"></i>
            </span>`
        ).join('');
    } else {
        activeFiltersDiv.style.display = 'none';
    }
}

// Clear specific filter
function clearFilter(filterId) {
    document.getElementById(filterId).value = '';
    applyFilters();
}

// Clear all filters
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('searchInput').value = '';
    document.getElementById('regionFilter').value = '';
    document.getElementById('constituencyFilter').innerHTML = '<option value="">All Constituencies</option>';
    document.getElementById('genderFilter').value = '';
    document.getElementById('positionFilter').value = '';
    document.getElementById('statusFilter').value = '';
    applyFilters();
});

// Initialize on page load
window.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    updateStats();
});
</script>

<?php include 'includes/footer.php'; ?>
