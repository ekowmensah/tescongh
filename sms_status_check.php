<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/HubtelSMS.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'SMS Status Check';

$database = new Database();
$db = $database->getConnection();

$statusResult = null;
$searchType = '';
$searchId = '';

// Initialize Hubtel SMS
$clientId = defined('HUBTEL_CLIENT_ID') ? HUBTEL_CLIENT_ID : '';
$clientSecret = defined('HUBTEL_CLIENT_SECRET') ? HUBTEL_CLIENT_SECRET : '';
$senderId = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'TESCON-GH';

$hubtelConfigured = !empty($clientId) && !empty($clientSecret);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hubtelConfigured) {
    $searchType = sanitize($_POST['search_type']);
    $searchId = sanitize($_POST['search_id']);
    
    if (!empty($searchId)) {
        try {
            $hubtel = new HubtelSMS($clientId, $clientSecret, $senderId);
            
            if ($searchType === 'message') {
                $statusResult = $hubtel->getMessageStatus($searchId);
            } elseif ($searchType === 'batch') {
                $statusResult = $hubtel->getBatchStatus($searchId);
            }
        } catch (Exception $e) {
            setFlashMessage('danger', 'Error: ' . $e->getMessage());
        }
    } else {
        setFlashMessage('warning', 'Please enter a Message ID or Batch ID');
    }
}

// Get recent SMS logs with message IDs
$query = "SELECT * FROM sms_logs 
          WHERE message_id IS NOT NULL 
          ORDER BY sent_at DESC 
          LIMIT 20";
$stmt = $db->query($query);
$recentLogs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>SMS Status Check</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="sms.php" class="btn btn-primary">
            <i class="cil-send"></i> Send SMS
        </a>
        <a href="sms_logs.php" class="btn btn-info">
            <i class="cil-list"></i> View SMS Logs
        </a>
    </div>
</div>

<?php if (!$hubtelConfigured): ?>
<div class="alert alert-warning">
    <i class="cil-warning"></i> <strong>Configuration Required:</strong> 
    Hubtel SMS credentials are not configured. Please set HUBTEL_CLIENT_ID and HUBTEL_CLIENT_SECRET in config.php
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <strong>Check Message Status</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Search Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="search_type" id="search_type" required <?php echo !$hubtelConfigured ? 'disabled' : ''; ?>>
                            <option value="">Select Type</option>
                            <option value="message" <?php echo $searchType === 'message' ? 'selected' : ''; ?>>Message ID</option>
                            <option value="batch" <?php echo $searchType === 'batch' ? 'selected' : ''; ?>>Batch ID</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message ID / Batch ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="search_id" id="search_id" 
                               value="<?php echo htmlspecialchars($searchId); ?>" 
                               placeholder="e.g., fab43849-6c5b-4334-a88b-d06520b1ace8"
                               required <?php echo !$hubtelConfigured ? 'disabled' : ''; ?>>
                        <small class="text-muted">Enter the Message ID or Batch ID you want to check</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" <?php echo !$hubtelConfigured ? 'disabled' : ''; ?>>
                        <i class="cil-search"></i> Check Status
                    </button>
                </form>
            </div>
        </div>
        
        <?php if ($statusResult): ?>
        <div class="card mt-4">
            <div class="card-header">
                <strong>Status Results</strong>
            </div>
            <div class="card-body">
                <?php if ($statusResult['success']): ?>
                    <?php if ($searchType === 'message'): ?>
                        <!-- Single Message Status -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Message ID</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['messageId'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $status = $statusResult['data']['status'] ?? '';
                                            echo $status === 'Delivered' ? 'success' : 
                                                 ($status === 'Sent' || $status === 'Pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Recipient</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['to'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>From</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['from'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Content</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['content'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Rate (Cost)</th>
                                    <td>GHS <?php echo number_format($statusResult['data']['rate'] ?? 0, 4); ?></td>
                                </tr>
                                <tr>
                                    <th>Network ID</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['networkId'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Sent Time</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['time'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Update Time</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['updateTime'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Batch ID</th>
                                    <td><?php echo htmlspecialchars($statusResult['data']['batchId'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Batch Status -->
                        <div class="mb-3">
                            <strong>Batch ID:</strong> <?php echo htmlspecialchars($statusResult['data']['batchId'] ?? 'N/A'); ?>
                        </div>
                        
                        <?php if (isset($statusResult['data']['data']) && is_array($statusResult['data']['data'])): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Recipient</th>
                                            <th>Status</th>
                                            <th>Message ID</th>
                                            <th>Content</th>
                                            <th>Rate</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($statusResult['data']['data'] as $msg): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($msg['to'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    $status = $msg['status'] ?? '';
                                                    echo $status === 'Delivered' ? 'success' : 
                                                         ($status === 'Sent' || $status === 'Pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($msg['messageId'] ?? 'N/A'); ?></small></td>
                                            <td><?php echo htmlspecialchars(substr($msg['content'] ?? '', 0, 50)); ?>...</td>
                                            <td>GHS <?php echo number_format($msg['rate'] ?? 0, 4); ?></td>
                                            <td><small><?php echo htmlspecialchars($msg['time'] ?? 'N/A'); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="cil-x-circle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($statusResult['error']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Raw Response (for debugging) -->
                <div class="mt-3">
                    <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawResponse">
                        Show Raw Response
                    </button>
                    <div class="collapse mt-2" id="rawResponse">
                        <pre class="bg-light p-3"><?php echo htmlspecialchars(json_encode($statusResult, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong>Recent Messages</strong>
            </div>
            <div class="card-body">
                <?php if (empty($recentLogs)): ?>
                    <p class="text-muted">No recent messages with Message IDs</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($log['recipient_phone']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($log['message'], 0, 40)); ?>...
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($log['sent_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $log['status'] === 'sent' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($log['status']); ?>
                                    </span>
                                </div>
                                <?php if ($log['message_id']): ?>
                                    <button class="btn btn-sm btn-outline-primary mt-2 check-status-btn" 
                                            data-message-id="<?php echo htmlspecialchars($log['message_id']); ?>">
                                        Check Status
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <strong>SMS Status Descriptions</strong>
            </div>
            <div class="card-body">
                <dl>
                    <dt>Delivered</dt>
                    <dd>Message successfully delivered to recipient's phone</dd>
                    
                    <dt>Sent</dt>
                    <dd>Message sent to network operator, pending delivery</dd>
                    
                    <dt>Pending</dt>
                    <dd>Message queued, awaiting dispatch</dd>
                    
                    <dt>Blacklisted</dt>
                    <dd>Recipient has opted out of bulk messages</dd>
                    
                    <dt>Undeliverable/Failed</dt>
                    <dd>Message could not be delivered (phone off, no network, etc.)</dd>
                    
                    <dt>Rejected</dt>
                    <dd>Message was rejected by the network</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
// Quick check status buttons
document.querySelectorAll('.check-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const messageId = this.getAttribute('data-message-id');
        document.getElementById('search_type').value = 'message';
        document.getElementById('search_id').value = messageId;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
