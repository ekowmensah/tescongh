<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pageTitle = 'Events';

$database = new Database();
$db = $database->getConnection();

// Handle delete
if (isset($_GET['delete']) && hasAnyRole(['Admin', 'Executive'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM events WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Event deleted successfully');
        } else {
            setFlashMessage('danger', 'Failed to delete event');
        }
    } catch (Exception $e) {
        error_log("Event delete error: " . $e->getMessage());
        setFlashMessage('danger', 'Failed to delete event');
    }
    redirect('events.php');
}

// Get all events
$query = "SELECT e.*, u.email as created_by_email 
          FROM events e
          LEFT JOIN users u ON e.created_by = u.id
          ORDER BY e.event_date DESC, e.event_time DESC";
$stmt = $db->query($query);
$events = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Events</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
            <a href="event_add.php" class="btn btn-primary">
                <i class="cil-plus"></i> Create Event
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <?php if (empty($events)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="cil-calendar" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h4 class="mt-3 text-muted">No Events Found</h4>
                    <p class="text-muted">There are no events scheduled at the moment.</p>
                    <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                        <a href="event_add.php" class="btn btn-primary mt-2">Create First Event</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <?php
            $eventDateTime = strtotime($event['event_date'] . ' ' . $event['event_time']);
            $isPast = $eventDateTime < time();
            $isToday = date('Y-m-d') == $event['event_date'];
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card <?php echo $isPast ? 'opacity-75' : ''; ?>">
                    <div class="card-header <?php echo $isToday ? 'bg-warning text-dark' : ($isPast ? 'bg-secondary' : 'bg-primary text-white'); ?>">
                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                        <?php if ($isToday): ?>
                            <span class="badge bg-dark float-end">Today</span>
                        <?php elseif ($isPast): ?>
                            <span class="badge bg-dark float-end">Past</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        
                        <div class="mb-2">
                            <i class="cil-calendar me-2"></i>
                            <strong><?php echo formatDate($event['event_date'], 'd M Y'); ?></strong>
                        </div>
                        
                        <div class="mb-2">
                            <i class="cil-clock me-2"></i>
                            <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                        </div>
                        
                        <div class="mb-3">
                            <i class="cil-location-pin me-2"></i>
                            <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="event_view.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                            <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                                <a href="event_attendance.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="cil-check"></i> Attendance
                                </a>
                                <div class="btn-group">
                                    <a href="event_edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="cil-pencil"></i> Edit
                                    </a>
                                    <a href="events.php?delete=<?php echo $event['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirmDelete('Are you sure you want to delete this event?')">
                                        <i class="cil-trash"></i> Delete
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        Created <?php echo formatDate($event['created_at'], 'd M Y'); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
