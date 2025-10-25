<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';
require_once 'classes/Region.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Send SMS';

$database = new Database();
$db = $database->getConnection();

$member = new Member($db);
$regionObj = new Region($db);

$regions = $regionObj->getAll();

// Get SMS templates
$query = "SELECT * FROM sms_templates ORDER BY name ASC";
$stmt = $db->query($query);
$templates = $stmt->fetchAll();

// Get custom lists
$query = "SELECT cl.*, COUNT(clm.member_id) as member_count 
          FROM custom_lists cl 
          LEFT JOIN custom_list_members clm ON cl.id = clm.list_id 
          GROUP BY cl.id 
          ORDER BY cl.name ASC";
$stmt = $db->query($query);
$customLists = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_type = sanitize($_POST['recipient_type']);
    $message = sanitize($_POST['message']);
    $recipients = [];
    
    // Get recipients based on type
    if ($recipient_type === 'all') {
        $recipients = $member->getAll(10000, 0);
    } elseif ($recipient_type === 'region') {
        $region = sanitize($_POST['region']);
        $recipients = $member->getAll(10000, 0, ['region' => $region]);
    } elseif ($recipient_type === 'status') {
        $status = sanitize($_POST['status']);
        $recipients = $member->getAll(10000, 0, ['membership_status' => $status]);
    } elseif ($recipient_type === 'position') {
        $position = sanitize($_POST['position']);
        $recipients = $member->getAll(10000, 0, ['position' => $position]);
    } elseif ($recipient_type === 'individual') {
        $member_id = (int)$_POST['member_id'];
        $memberData = $member->getById($member_id);
        if ($memberData) {
            $recipients = [$memberData];
        }
    } elseif ($recipient_type === 'custom_list') {
        $list_id = (int)$_POST['custom_list_id'];
        // Get members from custom list
        $query = "SELECT m.* FROM members m 
                  INNER JOIN custom_list_members clm ON m.id = clm.member_id 
                  WHERE clm.list_id = :list_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->execute();
        $recipients = $stmt->fetchAll();
    } elseif ($recipient_type === 'custom_numbers') {
        $custom_numbers = sanitize($_POST['custom_numbers']);
        // Parse phone numbers (comma, semicolon, or newline separated)
        $numbers = preg_split('/[,;\n\r]+/', $custom_numbers);
        $numbers = array_map('trim', $numbers);
        $numbers = array_filter($numbers); // Remove empty values
        
        // Create recipient array with phone numbers
        foreach ($numbers as $number) {
            $recipients[] = [
                'phone' => $number,
                'fullname' => 'Custom Recipient',
                'student_id' => 'N/A'
            ];
        }
    }
    
    // Send SMS to each recipient
    $sentCount = 0;
    $failedCount = 0;
    
    foreach ($recipients as $recipient) {
        $phone = formatPhoneNumber($recipient['phone']);
        
        // Replace placeholders in message
        $personalizedMessage = str_replace(
            ['{name}', '{fullname}', '{student_id}'],
            [$recipient['fullname'], $recipient['fullname'], $recipient['student_id']],
            $message
        );
        
        // Log SMS (in production, this would call Hubtel API)
        $logQuery = "INSERT INTO sms_logs (sender_id, recipient_phone, message, status, sent_at) 
                     VALUES (:sender_id, :phone, :message, 'sent', NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bindValue(':sender_id', $_SESSION['user_id']);
        $logStmt->bindParam(':phone', $phone);
        $logStmt->bindParam(':message', $personalizedMessage);
        
        if ($logStmt->execute()) {
            $sentCount++;
        } else {
            $failedCount++;
        }
    }
    
    setFlashMessage('success', "SMS sent to $sentCount recipient(s). Failed: $failedCount");
    redirect('sms.php');
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Send SMS</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="sms_logs.php" class="btn btn-info">
            <i class="cil-list"></i> View SMS Logs
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="cil-info"></i> <strong>Note:</strong> SMS sending requires Hubtel API credentials to be configured in config.php
</div>

<div class="row">
    <div class="col-md-8">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Compose SMS</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Select Recipients <span class="text-danger">*</span></label>
                        <select class="form-select" name="recipient_type" id="recipient_type" required>
                            <option value="">Choose recipient type</option>
                            <option value="all">All Members</option>
                            <option value="region">By Region</option>
                            <option value="status">By Status</option>
                            <option value="position">By Position</option>
                            <option value="custom_numbers">Custom Phone Numbers</option>
                            <option value="custom_list">Custom List</option>
                            <option value="individual">Individual Member</option>
                        </select>
                    </div>
                    
                    <div id="region_filter" class="mb-3" style="display: none;">
                        <label class="form-label">Select Region</label>
                        <select class="form-select" name="region">
                            <option value="">Select Region</option>
                            <?php foreach ($regions as $reg): ?>
                                <option value="<?php echo htmlspecialchars($reg['name']); ?>">
                                    <?php echo htmlspecialchars($reg['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="status_filter" class="mb-3" style="display: none;">
                        <label class="form-label">Select Status</label>
                        <select class="form-select" name="status">
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Suspended">Suspended</option>
                            <option value="Graduated">Graduated</option>
                        </select>
                    </div>
                    
                    <div id="position_filter" class="mb-3" style="display: none;">
                        <label class="form-label">Select Position</label>
                        <select class="form-select" name="position">
                            <option value="">Select Position</option>
                            <option value="Member">Member</option>
                            <option value="Executive">Executive</option>
                            <option value="Patron">Patron</option>
                        </select>
                    </div>
                    
                    <div id="custom_numbers_filter" class="mb-3" style="display: none;">
                        <label class="form-label">Enter Phone Numbers</label>
                        <textarea class="form-control" name="custom_numbers" id="custom_numbers" rows="6" placeholder="Enter phone numbers (one per line or comma-separated)&#10;Example:&#10;+233501234567&#10;+233241234567, +233551234567&#10;0501234567"></textarea>
                        <small class="text-muted">
                            Enter phone numbers separated by commas, semicolons, or new lines. <span id="number_count">0</span> number(s) entered.
                        </small>
                    </div>
                    
                    <div id="custom_list_filter" class="mb-3" style="display: none;">
                        <label class="form-label">Select Custom List</label>
                        <select class="form-select" name="custom_list_id">
                            <option value="">Select Custom List</option>
                            <?php foreach ($customLists as $list): ?>
                                <option value="<?php echo $list['id']; ?>">
                                    <?php echo htmlspecialchars($list['name']); ?> (<?php echo $list['member_count']; ?> members)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <a href="custom_lists.php" target="_blank">Manage Custom Lists</a>
                        </small>
                    </div>
                    
                    <div id="individual_filter" class="mb-3" style="display: none;">
                        <label class="form-label">Select Member</label>
                        <select class="form-select" name="member_id" id="member_select">
                            <option value="">Select Member</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Use Template (Optional)</label>
                        <select class="form-select" id="template_select">
                            <option value="">Select a template</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?php echo htmlspecialchars($template['content']); ?>">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Available placeholders: {name}, {fullname}, {student_id}</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="message" id="message" rows="5" maxlength="160" required></textarea>
                        <div class="form-text">
                            <span id="char_count">0</span>/160 characters
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-send"></i> Send SMS
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('message').value = '';">
                        <i class="cil-x"></i> Clear
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong>SMS Templates</strong>
            </div>
            <div class="card-body">
                <?php if (empty($templates)): ?>
                    <p class="text-muted">No templates available</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($templates as $template): ?>
                            <div class="list-group-item">
                                <strong><?php echo htmlspecialchars($template['name']); ?></strong>
                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($template['content']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <strong>Tips</strong>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Keep messages under 160 characters</li>
                    <li>Use templates for consistency</li>
                    <li>Personalize with placeholders</li>
                    <li>Test with individual first</li>
                    <li>Check recipient count before sending</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide filters based on recipient type
document.getElementById('recipient_type').addEventListener('change', function() {
    document.getElementById('region_filter').style.display = 'none';
    document.getElementById('status_filter').style.display = 'none';
    document.getElementById('position_filter').style.display = 'none';
    document.getElementById('custom_numbers_filter').style.display = 'none';
    document.getElementById('custom_list_filter').style.display = 'none';
    document.getElementById('individual_filter').style.display = 'none';
    
    if (this.value === 'region') {
        document.getElementById('region_filter').style.display = 'block';
    } else if (this.value === 'status') {
        document.getElementById('status_filter').style.display = 'block';
    } else if (this.value === 'position') {
        document.getElementById('position_filter').style.display = 'block';
    } else if (this.value === 'custom_numbers') {
        document.getElementById('custom_numbers_filter').style.display = 'block';
    } else if (this.value === 'custom_list') {
        document.getElementById('custom_list_filter').style.display = 'block';
    } else if (this.value === 'individual') {
        document.getElementById('individual_filter').style.display = 'block';
        loadMembers();
    }
});

// Load template into message
document.getElementById('template_select').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('message').value = this.value;
        updateCharCount();
    }
});

// Character counter
const messageField = document.getElementById('message');
const charCount = document.getElementById('char_count');

function updateCharCount() {
    charCount.textContent = messageField.value.length;
}

messageField.addEventListener('input', updateCharCount);

// Count phone numbers in custom numbers field
const customNumbersField = document.getElementById('custom_numbers');
if (customNumbersField) {
    customNumbersField.addEventListener('input', function() {
        const numbers = this.value.split(/[,;\n\r]+/).map(n => n.trim()).filter(n => n.length > 0);
        document.getElementById('number_count').textContent = numbers.length;
    });
}

// Load members for individual selection
function loadMembers() {
    fetch('ajax/get_members.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('member_select');
            select.innerHTML = '<option value="">Select Member</option>';
            data.forEach(member => {
                const option = document.createElement('option');
                option.value = member.id;
                option.textContent = member.fullname + ' - ' + member.student_id;
                select.appendChild(option);
            });
        });
}
</script>

<?php include 'includes/footer.php'; ?>
