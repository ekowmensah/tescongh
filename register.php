<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = 'Members Register';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$region_filter = isset($_GET['region']) ? sanitize($_GET['region']) : '';
$constituency_filter = isset($_GET['constituency']) ? sanitize($_GET['constituency']) : '';
$campus_filter = isset($_GET['campus']) ? sanitize($_GET['campus']) : '';
$institution_filter = isset($_GET['institution']) ? sanitize($_GET['institution']) : '';
$gender_filter = isset($_GET['gender']) ? sanitize($_GET['gender']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$position_filter = isset($_GET['position']) ? sanitize($_GET['position']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // 20 members per page (10 rows x 2 columns)
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(m.fullname LIKE :search OR m.student_id LIKE :search OR m.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($region_filter) {
    $where_conditions[] = "m.region = :region";
    $params[':region'] = $region_filter;
}

if ($constituency_filter) {
    $where_conditions[] = "m.constituency = :constituency";
    $params[':constituency'] = $constituency_filter;
}

if ($campus_filter) {
    $where_conditions[] = "c.name = :campus";
    $params[':campus'] = $campus_filter;
}

if ($institution_filter) {
    $where_conditions[] = "m.institution = :institution";
    $params[':institution'] = $institution_filter;
}

if ($gender_filter) {
    $where_conditions[] = "m.gender = :gender";
    $params[':gender'] = $gender_filter;
}

if ($status_filter) {
    $where_conditions[] = "m.membership_status = :status";
    $params[':status'] = $status_filter;
}

if ($position_filter) {
    $where_conditions[] = "m.position = :position";
    $params[':position'] = $position_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total 
                FROM members m 
                LEFT JOIN campuses c ON m.campus_id = c.id 
                $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get members
$query = "SELECT m.*, c.name as campus_name, c.location as campus_location
          FROM members m
          LEFT JOIN campuses c ON m.campus_id = c.id
          $where_clause
          ORDER BY m.fullname ASC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$members = $stmt->fetchAll();

// Get filter options
$regions_query = "SELECT DISTINCT region FROM members WHERE region IS NOT NULL ORDER BY region ASC";
$regions = $db->query($regions_query)->fetchAll(PDO::FETCH_COLUMN);

$constituencies_query = "SELECT DISTINCT constituency FROM members WHERE constituency IS NOT NULL ORDER BY constituency ASC";
$constituencies = $db->query($constituencies_query)->fetchAll(PDO::FETCH_COLUMN);

$campuses_query = "SELECT DISTINCT name FROM campuses ORDER BY name ASC";
$campuses = $db->query($campuses_query)->fetchAll(PDO::FETCH_COLUMN);

$institutions_query = "SELECT DISTINCT institution FROM members ORDER BY institution ASC";
$institutions = $db->query($institutions_query)->fetchAll(PDO::FETCH_COLUMN);

// Helper function to build filter query string
function buildFilterQuery($exclude_page = false) {
    global $search, $region_filter, $constituency_filter, $campus_filter, $institution_filter, $gender_filter, $status_filter, $position_filter;
    
    $params = [];
    if ($search) $params['search'] = $search;
    if ($region_filter) $params['region'] = $region_filter;
    if ($constituency_filter) $params['constituency'] = $constituency_filter;
    if ($campus_filter) $params['campus'] = $campus_filter;
    if ($institution_filter) $params['institution'] = $institution_filter;
    if ($gender_filter) $params['gender'] = $gender_filter;
    if ($status_filter) $params['status'] = $status_filter;
    if ($position_filter) $params['position'] = $position_filter;
    
    return !empty($params) ? '&' . http_build_query($params) : '';
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Members Register</h2>
        <p class="text-muted">Total Members: <strong><?php echo number_format($total_records); ?></strong></p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-secondary" onclick="window.print();">
            <i class="cil-print"></i> Print Register
        </button>
        <button type="button" class="btn btn-info" data-coreui-toggle="collapse" data-coreui-target="#filterPanel">
            <i class="cil-filter"></i> Filters
        </button>
    </div>
</div>

<!-- Filter Panel -->
<div class="collapse mb-4 <?php echo ($search || $region_filter || $constituency_filter || $campus_filter || $institution_filter || $gender_filter || $status_filter || $position_filter) ? 'show' : ''; ?>" id="filterPanel">
    <div class="card">
        <div class="card-header">
            <strong>Filter Members</strong>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" placeholder="Name, ID, Phone..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Region</label>
                            <select class="form-select" name="region">
                                <option value="">All Regions</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo htmlspecialchars($region); ?>" <?php echo $region_filter === $region ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($region); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Constituency</label>
                            <select class="form-select" name="constituency">
                                <option value="">All Constituencies</option>
                                <?php foreach ($constituencies as $constituency): ?>
                                    <option value="<?php echo htmlspecialchars($constituency); ?>" <?php echo $constituency_filter === $constituency ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($constituency); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Campus</label>
                            <select class="form-select" name="campus">
                                <option value="">All Campuses</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?php echo htmlspecialchars($campus); ?>" <?php echo $campus_filter === $campus ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($campus); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Institution</label>
                            <select class="form-select" name="institution">
                                <option value="">All Institutions</option>
                                <?php foreach ($institutions as $institution): ?>
                                    <option value="<?php echo htmlspecialchars($institution); ?>" <?php echo $institution_filter === $institution ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($institution); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">All Genders</option>
                                <option value="Male" <?php echo $gender_filter === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $gender_filter === 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Suspended" <?php echo $status_filter === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="Graduated" <?php echo $status_filter === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <select class="form-select" name="position">
                                <option value="">All Positions</option>
                                <option value="Member" <?php echo $position_filter === 'Member' ? 'selected' : ''; ?>>Member</option>
                                <option value="Executive" <?php echo $position_filter === 'Executive' ? 'selected' : ''; ?>>Executive</option>
                                <option value="Patron" <?php echo $position_filter === 'Patron' ? 'selected' : ''; ?>>Patron</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="cil-filter"></i> Apply Filters
                        </button>
                        <a href="register.php" class="btn btn-secondary">
                            <i class="cil-x"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Members Grid -->
<?php if (empty($members)): ?>
    <div class="alert alert-info">
        <i class="cil-info"></i> No members found matching your criteria.
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 g-2">
        <?php foreach ($members as $member): ?>
            <div class="col">
                <div class="card member-card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column p-4">
                        <div class="row g-2">
                            <div class="col-4 text-center photo-container">
                                <?php 
                                $photo_url = '';
                                if (!empty($member['photo'])) {
                                    if (strpos($member['photo'], 'http') === 0) {
                                        $photo_url = $member['photo'];
                                    } elseif (strpos($member['photo'], 'uploads/') === 0) {
                                        $photo_url = $member['photo'];
                                    } else {
                                        $photo_url = 'uploads/' . $member['photo'];
                                    }
                                }
                                
                                if ($photo_url): 
                                ?>
                                    <div class="member-photo-wrapper">
                                        <img src="<?php echo htmlspecialchars($photo_url); ?>" 
                                             alt="<?php echo htmlspecialchars($member['fullname']); ?>" 
                                             class="member-photo"
                                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'member-photo-placeholder\'><i class=\'cil-user\'></i></div>';">
                                    </div>
                                <?php else: ?>
                                    <div class="member-photo-wrapper">
                                        <div class="member-photo-placeholder">
                                            <i class="cil-user"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-8">
                                <h5 class="card-title mb-3 text-primary"><?php echo htmlspecialchars($member['fullname']); ?></h5>
                                
                                <div class="member-info">
                                    <div class="info-item">
                                        <span class="info-label">Student ID:</span>
                                        <span class="info-value fw-bold"><?php echo htmlspecialchars($member['student_id'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Institution:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($member['institution']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Campus:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($member['campus_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Program:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($member['program']); ?></span>
                                    </div>
                            <!--        <div class="info-item">
                                        <span class="info-label">Year:</span>
                                        <span class="info-value"><span class="badge bg-secondary">Year <?php echo htmlspecialchars($member['year']); ?></span></span>
                                    </div> -->
                                    <!-- <tr>
                                        <td class="text-muted"><strong>Gender:</strong></td>
                                        <td><?php echo htmlspecialchars($member['gender'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><strong>Region:</strong></td>
                                        <td><?php echo htmlspecialchars($member['region'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><strong>Constituency:</strong></td>
                                        <td><?php echo htmlspecialchars($member['constituency'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><strong>Position:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['position'] === 'Executive' ? 'primary' : ($member['position'] === 'Patron' ? 'warning' : 'secondary'); ?>">
                                                <?php echo htmlspecialchars($member['position']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['membership_status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($member['membership_status']); ?>
                                            </span>
                                        </td>
                                    </tr> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="register.php?page=<?php echo ($page - 1) . buildFilterQuery(); ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="register.php?page=<?php echo $i . buildFilterQuery(); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="register.php?page=<?php echo ($page + 1) . buildFilterQuery(); ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<style>
/* Member Card Styling */
.member-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.member-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
}

/* Photo Container */
.member-photo-wrapper {
    width: 100%;
    aspect-ratio: 1;
    position: relative;
}

.member-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
    border: 4px solid #e3f2fd;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.member-card:hover .member-photo {
    border-color: #2196f3;
    transform: scale(1.05);
}

.member-photo-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 4px solid #e3f2fd;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.member-photo-placeholder i {
    font-size: 3.5rem;
    color: white;
    opacity: 0.8;
}

/* Card Title */
.card-title {
    font-weight: 700;
    font-size: 1.1rem;
    line-height: 1.3;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

/* Member Info */
.member-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-height: 100px;
    flex: 1;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    border-bottom: 1px solid #f0f0f0;
    flex-shrink: 0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
    min-width: 90px;
}

.info-value {
    font-size: 0.875rem;
    color: #212529;
    text-align: right;
    flex: 1;
}

/* Ensure consistent card widths and heights */
.row-cols-md-2 {
    display: flex;
    flex-wrap: wrap;
    margin: -0.25rem;
}

.row-cols-md-2 > .col {
    display: flex;
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0.25rem;
}

.member-card {
    width: 100%;
    display: flex;
    flex-direction: column;
}

.member-card .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.member-card .row {
    flex: 1;
}

/* Ensure photo column has consistent width */
.photo-container {
    flex: 0 0 35%;
    max-width: 35%;
}

.member-card .col-8 {
    flex: 0 0 65%;
    max-width: 65%;
}

@media (max-width: 767px) {
    .row-cols-md-2 > .col {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

@media print {
    /* Hide navigation and UI elements */
    .btn, .pagination, nav, #filterPanel, .collapse,
    .sidebar, .header, footer, .header-sticky {
        display: none !important;
    }
    
    /* Reset wrapper margin for print */
    .wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    body {
        margin: 0;
        padding: 8px;
        background: white !important;
    }
    
    /* Page title - Professional header */
    h2 {
        margin: 0 0 10px 0 !important;
        padding: 10px !important;
        text-align: center;
        font-size: 20px !important;
        font-weight: bold;
        border-bottom: 3px solid #000;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #000 !important;
    }
    
    /* Grid layout for print - 2 Columns */
    .row-cols-md-2 {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        column-count: 2 !important;
        column-gap: 6px !important;
    }
    
    .row-cols-md-2 > .col {
        width: 100% !important;
        display: inline-block !important;
        padding: 0 !important;
        margin-bottom: 6px !important;
        box-sizing: border-box;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    
    /* Member cards - Compact layout */
    .member-card {
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 2px solid #000 !important;
        border-radius: 10px !important;
        padding: 8px !important;
        background: white !important;
        height: auto !important;
        transform: none !important;
    }
    
    .member-card .card-body {
        padding: 0 !important;
    }
    
    /* Internal row spacing */
    .member-card .row {
        margin: 0 !important;
    }
    
    /* Photo container */
    .photo-container {
        width: 35% !important;
        text-align: center !important;
        display: flex !important;
        align-items: flex-start !important;
        justify-content: center !important;
        padding: 3px !important;
    }
    
    .member-card .col-8 {
        width: 65% !important;
        padding-left: 8px !important;
    }
    
    /* Photo wrapper and styling - Rounded square */
    .member-photo-wrapper {
        width: 90px !important;
        height: 90px !important;
        margin: 0 auto !important;
    }
    
    .member-photo {
        width: 90px !important;
        height: 90px !important;
        object-fit: cover !important;
        border: 3px solid #2196f3 !important;
        border-radius: 10px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        display: block !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    }
    
    .member-photo-placeholder {
        width: 90px !important;
        height: 90px !important;
        border: 3px solid #2196f3 !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    }
    
    .member-photo-placeholder i {
        font-size: 2.2rem !important;
        color: white !important;
    }
    
    /* Card title - Member name */
    .card-title {
        font-size: 12px !important;
        font-weight: bold !important;
        margin-bottom: 6px !important;
        padding-bottom: 4px !important;
        border-bottom: 2px solid #2196f3 !important;
        color: #2196f3 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Member info styling - Compact */
    .member-info {
        display: block !important;
        gap: 0 !important;
        min-height: auto !important;
    }
    
    .info-item {
        display: flex !important;
        justify-content: space-between !important;
        padding: 2px 0 !important;
        border-bottom: 1px solid #e0e0e0 !important;
        page-break-inside: avoid;
    }
    
    .info-item:last-child {
        border-bottom: none !important;
    }
    
    .info-label {
        font-size: 9px !important;
        font-weight: 700 !important;
        color: #666 !important;
        min-width: 70px !important;
    }
    
    .info-value {
        font-size: 9px !important;
        color: #000 !important;
        text-align: right !important;
        font-weight: 600 !important;
    }
    
    /* Badges */
    .badge {
        border: 1px solid #000 !important;
        padding: 1px 4px !important;
        font-size: 7px !important;
        font-weight: bold !important;
        background: white !important;
        color: #000 !important;
        border-radius: 3px !important;
    }
    
    /* Force images and colors to print */
    img {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Page breaks */
    @page {
        margin: 0.6cm;
        size: A4;
    }
}

@media (max-width: 768px) {
    .member-photo, .member-photo-placeholder {
        height: 150px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
