<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'SMS Logs';

$database = new Database();
$db = $database->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "sl.status = :status";
    $params[':status'] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(sl.sent_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(sl.sent_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($search) {
    $where_conditions[] = "(sl.recipient_phone LIKE :search OR sl.message LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM sms_logs sl $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get SMS logs
$query = "SELECT sl.*, u.email as sender_email 
          FROM sms_logs sl
          LEFT JOIN users u ON sl.sender_id = u.id
          $where_clause
          ORDER BY sl.sent_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(cost) as total_cost
                FROM sms_logs";
$stats_stmt = $db->query($stats_query);
$stats = $stats_stmt->fetch();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>SMS Logs</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="sms.php" class="btn btn-primary">
            <i class="cil-send"></i> Send SMS
        </a>
        <button type="button" class="btn btn-secondary" onclick="window.print();">
            <i class="cil-print"></i> Print
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="text-value-lg"><?php echo number_format($stats['total_sent']); ?></div>
                <div>Total SMS Sent</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="text-value-lg"><?php echo number_format($stats['delivered_count']); ?></div>
                <div>Delivered</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="text-value-lg"><?php echo number_format($stats['failed_count']); ?></div>
                <div>Failed</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="text-value-lg">GH₵ <?php echo number_format($stats['total_cost'] ?? 0, 2); ?></div>
                <div>Total Cost</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <strong>Filter Logs</strong>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" placeholder="Phone or message..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-filter"></i> Apply Filters
                    </button>
                    <a href="sms_logs.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Clear Filters
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- SMS Logs Table -->
<div class="card">
    <div class="card-header">
        <strong>SMS History</strong>
        <span class="badge bg-secondary ms-2"><?php echo number_format($total_records); ?> records</span>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <i class="cil-info"></i> No SMS logs found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipient</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Sender</th>
                            <th>Sent At</th>
                            <th>Delivered At</th>
                            <th>Cost</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['recipient_phone']); ?></strong>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 300px;" title="<?php echo htmlspecialchars($log['message']); ?>">
                                        <?php echo htmlspecialchars($log['message']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'secondary';
                                    if ($log['status'] === 'sent') $badge_class = 'primary';
                                    if ($log['status'] === 'delivered') $badge_class = 'success';
                                    if ($log['status'] === 'failed') $badge_class = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['sender_email']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($log['sent_at'])); ?></td>
                                <td>
                                    <?php echo $log['delivered_at'] ? date('M d, Y H:i', strtotime($log['delivered_at'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $log['cost'] ? 'GH₵ ' . number_format($log['cost'], 2) : '-'; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                        <i class="cil-info"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">SMS Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th>ID</th>
                        <td id="detail_id"></td>
                    </tr>
                    <tr>
                        <th>Recipient Phone</th>
                        <td id="detail_phone"></td>
                    </tr>
                    <tr>
                        <th>Message</th>
                        <td id="detail_message"></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td id="detail_status"></td>
                    </tr>
                    <tr>
                        <th>Message ID</th>
                        <td id="detail_message_id"></td>
                    </tr>
                    <tr>
                        <th>Sender</th>
                        <td id="detail_sender"></td>
                    </tr>
                    <tr>
                        <th>Sent At</th>
                        <td id="detail_sent_at"></td>
                    </tr>
                    <tr>
                        <th>Delivered At</th>
                        <td id="detail_delivered_at"></td>
                    </tr>
                    <tr>
                        <th>Cost</th>
                        <td id="detail_cost"></td>
                    </tr>
                    <tr id="error_row" style="display: none;">
                        <th>Error Message</th>
                        <td id="detail_error" class="text-danger"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(log) {
    document.getElementById('detail_id').textContent = log.id;
    document.getElementById('detail_phone').textContent = log.recipient_phone;
    document.getElementById('detail_message').textContent = log.message;
    document.getElementById('detail_status').innerHTML = '<span class="badge bg-' + getStatusBadge(log.status) + '">' + log.status.toUpperCase() + '</span>';
    document.getElementById('detail_message_id').textContent = log.message_id || '-';
    document.getElementById('detail_sender').textContent = log.sender_email;
    document.getElementById('detail_sent_at').textContent = formatDate(log.sent_at);
    document.getElementById('detail_delivered_at').textContent = log.delivered_at ? formatDate(log.delivered_at) : '-';
    document.getElementById('detail_cost').textContent = log.cost ? 'GH₵ ' + parseFloat(log.cost).toFixed(2) : '-';
    
    if (log.error_message) {
        document.getElementById('error_row').style.display = 'table-row';
        document.getElementById('detail_error').textContent = log.error_message;
    } else {
        document.getElementById('error_row').style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

function getStatusBadge(status) {
    switch(status) {
        case 'sent': return 'primary';
        case 'delivered': return 'success';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}
</script>

<style>
@media print {
    .btn, .pagination, .card-header, nav {
        display: none !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
