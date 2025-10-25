<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'classes/Member.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Custom Lists';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);
    
    if ($action === 'create') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        $query = "INSERT INTO custom_lists (name, description, created_by) VALUES (:name, :description, :created_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindValue(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Custom list created successfully');
        } else {
            setFlashMessage('danger', 'Failed to create custom list');
        }
        redirect('custom_lists.php');
        
    } elseif ($action === 'delete') {
        $list_id = (int)$_POST['list_id'];
        
        $query = "DELETE FROM custom_lists WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $list_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Custom list deleted successfully');
        } else {
            setFlashMessage('danger', 'Failed to delete custom list');
        }
        redirect('custom_lists.php');
        
    } elseif ($action === 'add_members') {
        $list_id = (int)$_POST['list_id'];
        $member_ids = isset($_POST['member_ids']) ? $_POST['member_ids'] : [];
        
        if (!empty($member_ids)) {
            $query = "INSERT IGNORE INTO custom_list_members (list_id, member_id) VALUES (:list_id, :member_id)";
            $stmt = $db->prepare($query);
            
            $added = 0;
            foreach ($member_ids as $member_id) {
                $stmt->bindParam(':list_id', $list_id);
                $stmt->bindParam(':member_id', $member_id);
                if ($stmt->execute()) {
                    $added++;
                }
            }
            
            setFlashMessage('success', "$added member(s) added to the list");
        } else {
            setFlashMessage('warning', 'No members selected');
        }
        redirect('custom_lists.php?view=' . $list_id);
        
    } elseif ($action === 'remove_member') {
        $list_id = (int)$_POST['list_id'];
        $member_id = (int)$_POST['member_id'];
        
        $query = "DELETE FROM custom_list_members WHERE list_id = :list_id AND member_id = :member_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->bindParam(':member_id', $member_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Member removed from list');
        } else {
            setFlashMessage('danger', 'Failed to remove member');
        }
        redirect('custom_lists.php?view=' . $list_id);
    }
}

// Get all custom lists
$query = "SELECT cl.*, COUNT(clm.member_id) as member_count, u.email as creator_email
          FROM custom_lists cl 
          LEFT JOIN custom_list_members clm ON cl.id = clm.list_id 
          LEFT JOIN users u ON cl.created_by = u.id
          GROUP BY cl.id 
          ORDER BY cl.created_at DESC";
$stmt = $db->query($query);
$customLists = $stmt->fetchAll();

// If viewing a specific list
$viewingList = null;
$listMembers = [];
if (isset($_GET['view'])) {
    $list_id = (int)$_GET['view'];
    
    // Get list details
    $query = "SELECT * FROM custom_lists WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $list_id);
    $stmt->execute();
    $viewingList = $stmt->fetch();
    
    if ($viewingList) {
        // Get members in this list
        $query = "SELECT m.* FROM members m 
                  INNER JOIN custom_list_members clm ON m.id = clm.member_id 
                  WHERE clm.list_id = :list_id
                  ORDER BY m.fullname ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->execute();
        $listMembers = $stmt->fetchAll();
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Custom Lists</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($viewingList): ?>
            <a href="custom_lists.php" class="btn btn-secondary">
                <i class="cil-arrow-left"></i> Back to Lists
            </a>
        <?php else: ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createListModal">
                <i class="cil-plus"></i> Create New List
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($viewingList): ?>
    <!-- Viewing specific list -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <strong><?php echo htmlspecialchars($viewingList['name']); ?></strong>
                    <span class="badge bg-info ms-2"><?php echo count($listMembers); ?> members</span>
                </div>
                <div class="card-body">
                    <?php if ($viewingList['description']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($viewingList['description']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (empty($listMembers)): ?>
                        <div class="alert alert-info">
                            <i class="cil-info"></i> No members in this list yet. Use the form on the right to add members.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Student ID</th>
                                        <th>Phone</th>
                                        <th>Institution</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listMembers as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($member['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($member['institution']); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this member from the list?');">
                                                    <input type="hidden" name="action" value="remove_member">
                                                    <input type="hidden" name="list_id" value="<?php echo $viewingList['id']; ?>">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="cil-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <strong>Add Members</strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_members">
                        <input type="hidden" name="list_id" value="<?php echo $viewingList['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Search Members</label>
                            <input type="text" class="form-control" id="member_search" placeholder="Type to search...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Members</label>
                            <div id="member_checkboxes" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                <p class="text-muted">Loading members...</p>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="cil-plus"></i> Add Selected Members
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- List all custom lists -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($customLists)): ?>
                <div class="alert alert-info">
                    <i class="cil-info"></i> No custom lists created yet. Click "Create New List" to get started.
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Members</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customLists as $list): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($list['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($list['description'] ?? '-'); ?></td>
                                            <td><span class="badge bg-info"><?php echo $list['member_count']; ?></span></td>
                                            <td><?php echo htmlspecialchars($list['creator_email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($list['created_at'])); ?></td>
                                            <td>
                                                <a href="custom_lists.php?view=<?php echo $list['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="cil-pencil"></i> Manage
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this list? This cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="cil-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Create List Modal -->
<div class="modal fade" id="createListModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Custom List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">List Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create List</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($viewingList): ?>
<script>
// Load all members for selection
let allMembers = [];
let currentListMemberIds = <?php echo json_encode(array_column($listMembers, 'id')); ?>;

fetch('ajax/get_members.php')
    .then(response => response.json())
    .then(data => {
        allMembers = data;
        displayMembers(allMembers);
    });

function displayMembers(members) {
    const container = document.getElementById('member_checkboxes');
    container.innerHTML = '';
    
    // Filter out members already in the list
    const availableMembers = members.filter(m => !currentListMemberIds.includes(parseInt(m.id)));
    
    if (availableMembers.length === 0) {
        container.innerHTML = '<p class="text-muted">All members are already in this list</p>';
        return;
    }
    
    availableMembers.forEach(member => {
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `
            <input class="form-check-input" type="checkbox" name="member_ids[]" value="${member.id}" id="member_${member.id}">
            <label class="form-check-label" for="member_${member.id}">
                ${member.fullname} - ${member.student_id}
            </label>
        `;
        container.appendChild(div);
    });
}

// Search functionality
document.getElementById('member_search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const filtered = allMembers.filter(m => 
        m.fullname.toLowerCase().includes(searchTerm) || 
        m.student_id.toLowerCase().includes(searchTerm)
    );
    displayMembers(filtered);
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
