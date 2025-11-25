<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Only Admin and Executive can view attendance
if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Event Attendance';

$database = new Database();
$db = $database->getConnection();

// Get event ID
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$eventId) {
    setFlashMessage('danger', 'Invalid event ID');
    redirect('events.php');
}

// Get event details
$eventQuery = "SELECT e.*, u.email as created_by_email 
               FROM events e
               LEFT JOIN users u ON e.created_by = u.id
               WHERE e.id = :id";
$eventStmt = $db->prepare($eventQuery);
$eventStmt->bindParam(':id', $eventId);
$eventStmt->execute();
$event = $eventStmt->fetch();

if (!$event) {
    setFlashMessage('danger', 'Event not found');
    redirect('events.php');
}

// Get attendees
$attendeesQuery = "SELECT ea.*, m.fullname, m.student_id, m.phone, u.email as member_email,
                          c.name as campus_name, i.name as institution_name
                   FROM event_attendees ea
                   INNER JOIN members m ON ea.member_id = m.id
                   LEFT JOIN users u ON m.user_id = u.id
                   LEFT JOIN campuses c ON m.campus_id = c.id
                   LEFT JOIN institutions i ON c.institution_id = i.id
                   WHERE ea.event_id = :event_id
                   ORDER BY ea.registered_at DESC";
$attendeesStmt = $db->prepare($attendeesQuery);
$attendeesStmt->bindParam(':event_id', $eventId);
$attendeesStmt->execute();
$attendees = $attendeesStmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Event Attendance</h2>
        <p class="text-muted"><?php echo htmlspecialchars($event['title']); ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="event_view.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Event
        </a>
        <a href="events.php" class="btn btn-secondary">
            <i class="cil-list"></i> All Events
        </a>
    </div>
</div>

<!-- Event Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary mb-0"><?php echo count($attendees); ?></h3>
                <p class="text-muted small mb-0">Total Attendees</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="mb-0"><?php echo formatDate($event['event_date'], 'd M Y'); ?></h5>
                <p class="text-muted small mb-0">Event Date</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="mb-0"><?php echo date('g:i A', strtotime($event['event_time'])); ?></h5>
                <p class="text-muted small mb-0">Event Time</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="mb-0"><?php echo htmlspecialchars($event['location']); ?></h5>
                <p class="text-muted small mb-0">Location</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendees List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="cil-people"></i> Registered Attendees</strong>
        <?php if (!empty($attendees)): ?>
            <button class="btn btn-sm btn-success" onclick="exportToCSV()">
                <i class="cil-data-transfer-down"></i> Export CSV
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($attendees)): ?>
            <div class="text-center py-5">
                <i class="cil-people" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">No attendees registered yet</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="attendeesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Campus</th>
                            <th>Registered At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($attendees as $attendee): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($attendee['fullname']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($attendee['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['phone']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['member_email']); ?></td>
                                <td>
                                    <?php if ($attendee['campus_name']): ?>
                                        <small>
                                            <?php echo htmlspecialchars($attendee['campus_name']); ?><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($attendee['institution_name']); ?></span>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatDate($attendee['registered_at'], 'd M Y, g:i A'); ?></small>
                                </td>
                                <td>
                                    <a href="member_view.php?id=<?php echo $attendee['member_id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Member">
                                        <i class="cil-user"></i>
                                    </a>
                                    <?php if (hasRole('Admin')): ?>
                                        <a href="event_attendance_remove.php?event_id=<?php echo $eventId; ?>&member_id=<?php echo $attendee['member_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Remove Attendee"
                                           onclick="return confirm('Remove this attendee from the event?')">
                                            <i class="cil-x"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Export attendees to CSV
function exportToCSV() {
    const table = document.getElementById('attendeesTable');
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach((th, index) => {
        if (index < 7) { // Exclude Actions column
            headers.push(th.textContent.trim());
        }
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach((td, index) => {
            if (index < 7) { // Exclude Actions column
                // Clean up text and handle commas
                let text = td.textContent.trim().replace(/\n/g, ' ').replace(/\s+/g, ' ');
                // Wrap in quotes if contains comma
                if (text.includes(',')) {
                    text = '"' + text + '"';
                }
                rowData.push(text);
            }
        });
        csv.push(rowData.join(','));
    });
    
    // Create download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'event_attendance_<?php echo $eventId; ?>_<?php echo date('Y-m-d'); ?>.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include 'includes/footer.php'; ?>
