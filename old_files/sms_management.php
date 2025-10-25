<?php
session_start();
require_once 'config/database.php';
require_once 'includes/SMSService.php';

// Check if user is logged in and is an executive or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Executive', 'Patron', 'Admin'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$smsService = new SMSService();

// Handle SMS sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_sms'])) {
    $recipients = $_POST['recipients'] ?? '';
    $message = trim($_POST['message']);
    $recipient_type = $_POST['recipient_type'];
    $template_id = $_POST['template_id'] ?? '';

    if (empty($message)) {
        $error = 'Message content is required.';
    } else {
        // Get recipient phone numbers based on type
        $phoneNumbers = [];

        try {
            switch ($recipient_type) {
                case 'all':
                    $stmt = $pdo->query("SELECT phone FROM members WHERE phone IS NOT NULL AND phone != ''");
                    $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $phoneNumbers = $phones;
                    break;

                case 'unpaid':
                    $currentYear = date('Y');
                    $stmt = $pdo->prepare("
                        SELECT m.phone FROM members m
                        LEFT JOIN payments p ON m.id = p.member_id
                            AND p.status = 'completed'
                            AND p.dues_id = (SELECT id FROM dues WHERE year = ? LIMIT 1)
                        WHERE m.phone IS NOT NULL AND m.phone != ''
                        AND p.id IS NULL
                    ");
                    $stmt->execute([$currentYear]);
                    $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $phoneNumbers = $phones;
                    break;

                case 'paid':
                    $currentYear = date('Y');
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT m.phone FROM members m
                        JOIN payments p ON m.id = p.member_id
                        WHERE p.status = 'completed'
                        AND p.dues_id = (SELECT id FROM dues WHERE year = ? LIMIT 1)
                        AND m.phone IS NOT NULL AND m.phone != ''
                    ");
                    $stmt->execute([$currentYear]);
                    $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $phoneNumbers = $phones;
                    break;

                case 'executives':
                    $stmt = $pdo->query("
                        SELECT DISTINCT m.phone FROM members m
                        JOIN campus_executives ce ON m.id = ce.member_id
                        WHERE m.phone IS NOT NULL AND m.phone != ''
                    ");
                    $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $phoneNumbers = $phones;
                    break;

                case 'custom':
                    if (!empty($recipients)) {
                        $phoneNumbers = array_map('trim', explode(',', $recipients));
                        $phoneNumbers = array_filter($phoneNumbers);
                    }
                    break;
            }

            if (empty($phoneNumbers)) {
                $error = 'No valid recipients found for the selected criteria.';
            } else {
                // Send SMS to all recipients
                $results = $smsService->sendBulkSMS($phoneNumbers, $message);

                $successful = 0;
                $failed = 0;

                // Log SMS sending results
                foreach ($results as $result) {
                    if ($result['success']) {
                        $successful++;
                        // Log successful SMS
                        $stmt = $pdo->prepare("
                            INSERT INTO sms_logs (sender_id, recipient_phone, message, message_id, status, sent_at)
                            VALUES (?, ?, ?, ?, 'sent', NOW())
                        ");
                        $stmt->execute([$_SESSION['user_id'], $result['phone'], $message, $result['message_id']]);
                    } else {
                        $failed++;
                        // Log failed SMS
                        $stmt = $pdo->prepare("
                            INSERT INTO sms_logs (sender_id, recipient_phone, message, status, error_message, sent_at)
                            VALUES (?, ?, ?, 'failed', ?, NOW())
                        ");
                        $stmt->execute([$_SESSION['user_id'], $result['phone'], $message, $result['error']]);
                    }
                }

                $totalCost = $smsService->calculateCost(count($phoneNumbers));
                $success = "SMS sent to {$successful} recipients successfully. {$failed} failed. Estimated cost: GH₵" . number_format($totalCost, 2);
            }

        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle template saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $template_name = trim($_POST['template_name']);
    $template_content = trim($_POST['template_content']);

    if (empty($template_name) || empty($template_content)) {
        $error = 'Template name and content are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sms_templates (name, content, created_by, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()
            ");
            $stmt->execute([$template_name, $template_content, $_SESSION['user_id']]);
            $success = 'SMS template saved successfully.';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get SMS templates
$templates = [];
try {
    $stmt = $pdo->query("SELECT * FROM sms_templates ORDER BY name");
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle silently
}

// Get SMS statistics
$smsStats = [];
try {
    // Total SMS sent today
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_count FROM sms_logs WHERE DATE(sent_at) = CURDATE() AND status = 'sent'");
    $stmt->execute();
    $smsStats['today'] = $stmt->fetch()['today_count'];

    // Total SMS sent this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as month_count FROM sms_logs WHERE MONTH(sent_at) = MONTH(CURDATE()) AND YEAR(sent_at) = YEAR(CURDATE()) AND status = 'sent'");
    $stmt->execute();
    $smsStats['month'] = $stmt->fetch()['month_count'];

    // Total SMS sent all time
    $stmt = $pdo->query("SELECT COUNT(*) as total_count FROM sms_logs WHERE status = 'sent'");
    $smsStats['total'] = $stmt->fetch()['total_count'];

} catch (PDOException $e) {
    // Handle silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Management - TESCON Ghana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>SMS Management</h2>
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

        <!-- SMS Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-paper-plane"></i></h5>
                        <h3><?php echo $smsStats['today'] ?? 0; ?></h3>
                        <p class="mb-0">SMS Sent Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-calendar-month"></i></h5>
                        <h3><?php echo $smsStats['month'] ?? 0; ?></h3>
                        <p class="mb-0">SMS This Month</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-chart-line"></i></h5>
                        <h3><?php echo $smsStats['total'] ?? 0; ?></h3>
                        <p class="mb-0">Total SMS Sent</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Send SMS -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Send SMS</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="send_sms" value="1">

                            <div class="mb-3">
                                <label for="recipient_type" class="form-label">Recipients *</label>
                                <select class="form-select" id="recipient_type" name="recipient_type" required>
                                    <option value="">Select Recipient Group</option>
                                    <option value="all">All Members</option>
                                    <option value="unpaid">Unpaid Members (Current Year)</option>
                                    <option value="paid">Paid Members (Current Year)</option>
                                    <option value="executives">Campus Executives</option>
                                    <option value="custom">Custom Recipients</option>
                                </select>
                            </div>

                            <div class="mb-3" id="custom_recipients" style="display: none;">
                                <label for="recipients" class="form-label">Phone Numbers</label>
                                <textarea class="form-control" id="recipients" name="recipients" rows="3"
                                          placeholder="Enter phone numbers separated by commas (e.g., 0244123456, 0201234567)"></textarea>
                                <div class="form-text">Format: 0244123456, 0201234567 (Ghana phone numbers)</div>
                            </div>

                            <div class="mb-3">
                                <label for="template_id" class="form-label">Use Template (Optional)</label>
                                <select class="form-select" id="template_id" name="template_id">
                                    <option value="">Select Template</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>" data-content="<?php echo htmlspecialchars($template['content']); ?>">
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="4" required
                                          maxlength="160" placeholder="Enter your SMS message..."></textarea>
                                <div class="form-text">
                                    <span id="char-count">0</span>/160 characters
                                    <span class="float-end">Cost: GH₵<span id="cost-estimate">0.00</span></span>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>SMS Information:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Sender ID: <strong><?php echo HUBTEL_SMS_SENDER_ID; ?></strong></li>
                                    <li>Maximum length: 160 characters</li>
                                    <li>Cost per SMS: ~GH₵<?php echo number_format(SMS_COST_PER_MESSAGE, 2); ?></li>
                                    <li>Delivery: Usually within 30 seconds</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send SMS
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- SMS Templates -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">SMS Templates</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="mb-3">
                            <input type="hidden" name="save_template" value="1">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="template_name"
                                       placeholder="Template name" required>
                            </div>
                            <div class="mb-2">
                                <textarea class="form-control form-control-sm" name="template_content" rows="3"
                                          placeholder="Template content..." required maxlength="160"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save Template
                            </button>
                        </form>

                        <hr>
                        <h6>Saved Templates:</h6>
                        <div class="list-group">
                            <?php foreach ($templates as $template): ?>
                                <a href="#" class="list-group-item list-group-item-action p-2 small"
                                   onclick="useTemplate('<?php echo htmlspecialchars($template['content']); ?>')">
                                    <strong><?php echo htmlspecialchars($template['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($template['content'], 0, 50)); ?>...</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS History -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Recent SMS History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sender</th>
                                <th>Recipients</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT sl.*, m.fullname as sender_name,
                                           COUNT(*) as recipient_count
                                    FROM sms_logs sl
                                    JOIN members m ON sl.sender_id = m.id
                                    GROUP BY sl.sent_at, sl.sender_id, sl.message
                                    ORDER BY sl.sent_at DESC LIMIT 20
                                ");
                                $smsHistory = $stmt->fetchAll();

                                foreach ($smsHistory as $sms): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($sms['sent_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($sms['sender_name']); ?></td>
                                        <td><?php echo $sms['recipient_count']; ?> recipients</td>
                                        <td><?php echo htmlspecialchars(substr($sms['message'], 0, 50)); ?>...</td>
                                        <td>
                                            <span class="badge bg-<?php echo $sms['status'] == 'sent' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($sms['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach;

                                if (empty($smsHistory)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No SMS history found.</td>
                                    </tr>
                                <?php endif;
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="5" class="text-danger">Error loading SMS history</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Handle recipient type change
        document.getElementById('recipient_type').addEventListener('change', function() {
            const customRecipients = document.getElementById('custom_recipients');
            customRecipients.style.display = this.value === 'custom' ? 'block' : 'none';
        });

        // Handle template selection
        document.getElementById('template_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value && selectedOption.dataset.content) {
                document.getElementById('message').value = selectedOption.dataset.content;
                updateCharCount();
            }
        });

        // Character count and cost calculation
        document.getElementById('message').addEventListener('input', updateCharCount);

        function updateCharCount() {
            const message = document.getElementById('message').value;
            const charCount = message.length;
            document.getElementById('char-count').textContent = charCount;

            // Estimate recipients for cost calculation
            const recipientType = document.getElementById('recipient_type').value;
            let estimatedRecipients = 1; // Default

            // This is a rough estimate - in production you might want to get actual counts via AJAX
            switch(recipientType) {
                case 'all': estimatedRecipients = 100; break;
                case 'unpaid': estimatedRecipients = 50; break;
                case 'paid': estimatedRecipients = 50; break;
                case 'executives': estimatedRecipients = 20; break;
                default: estimatedRecipients = 1;
            }

            const cost = estimatedRecipients * <?php echo SMS_COST_PER_MESSAGE; ?>;
            document.getElementById('cost-estimate').textContent = cost.toFixed(2);
        }

        // Use template function
        function useTemplate(content) {
            document.getElementById('message').value = content;
            updateCharCount();
        }

        // Initialize character count
        updateCharCount();
    </script>
</body>
</html>
