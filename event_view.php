<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pageTitle = 'Event Details';

$database = new Database();
$db = $database->getConnection();

// Get event ID
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$eventId) {
    setFlashMessage('danger', 'Invalid event ID');
    redirect('events.php');
}

// Get event details
$query = "SELECT e.*, u.email as created_by_email 
          FROM events e
          LEFT JOIN users u ON e.created_by = u.id
          WHERE e.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('danger', 'Event not found');
    redirect('events.php');
}

// Get attendees count (if event_attendees table exists)
$attendeesCount = 0;
try {
    $attendeesQuery = "SELECT COUNT(*) as count FROM event_attendees WHERE event_id = :event_id";
    $attendeesStmt = $db->prepare($attendeesQuery);
    $attendeesStmt->bindParam(':event_id', $eventId);
    $attendeesStmt->execute();
    $attendeesCount = $attendeesStmt->fetch()['count'];
} catch (Exception $e) {
    // Table doesn't exist yet
}

// Check if current user is attending (for members)
$isAttending = false;
if (isset($_SESSION['member_id'])) {
    try {
        $checkQuery = "SELECT id FROM event_attendees WHERE event_id = :event_id AND member_id = :member_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':event_id', $eventId);
        $checkStmt->bindParam(':member_id', $_SESSION['member_id']);
        $checkStmt->execute();
        $isAttending = $checkStmt->rowCount() > 0;
    } catch (Exception $e) {
        // Table doesn't exist yet
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Event Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="events.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Events
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Event Information -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="cil-calendar"></i> Event Information</strong>
            </div>
            <div class="card-body">
                <h3 class="mb-3"><?php echo htmlspecialchars($event['title']); ?></h3>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong><i class="cil-calendar"></i> Date:</strong><br>
                            <span class="fs-5"><?php echo formatDate($event['event_date'], 'd M Y'); ?></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong><i class="cil-clock"></i> Time:</strong><br>
                            <span class="fs-5"><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                        </p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <p class="mb-2">
                        <strong><i class="cil-location-pin"></i> Location:</strong><br>
                        <span class="fs-5"><?php echo htmlspecialchars($event['location']); ?></span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <strong><i class="cil-notes"></i> Description:</strong>
                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted small mb-0">
                            <i class="cil-user"></i> Created by: <?php echo htmlspecialchars($event['created_by_email']); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="text-muted small mb-0">
                            <i class="cil-clock"></i> Created: <?php echo formatDate($event['created_at'], 'd M Y'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Event Status -->
        <div class="card mb-3">
            <div class="card-header">
                <strong><i class="cil-info"></i> Event Status</strong>
            </div>
            <div class="card-body">
                <?php
                $eventDateTime = strtotime($event['event_date'] . ' ' . $event['event_time']);
                $now = time();
                $isPast = $eventDateTime < $now;
                $isToday = date('Y-m-d') === $event['event_date'];
                ?>
                
                <?php if ($isPast): ?>
                    <div class="alert alert-secondary mb-0">
                        <i class="cil-check-circle"></i> <strong>Past Event</strong><br>
                        <small>This event has ended</small>
                    </div>
                <?php elseif ($isToday): ?>
                    <div class="alert alert-success mb-0">
                        <i class="cil-calendar"></i> <strong>Today!</strong><br>
                        <small>This event is happening today</small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="cil-calendar"></i> <strong>Upcoming</strong><br>
                        <small>
                            <?php 
                            $days = floor(($eventDateTime - $now) / (60 * 60 * 24));
                            echo $days == 1 ? 'Tomorrow' : "In {$days} days";
                            ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Attendees -->
        <div class="card mb-3">
            <div class="card-header">
                <strong><i class="cil-people"></i> Attendees</strong>
            </div>
            <div class="card-body text-center">
                <h2 class="text-primary mb-0"><?php echo $attendeesCount; ?></h2>
                <p class="text-muted small mb-0">Registered</p>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <strong><i class="cil-settings"></i> Actions</strong>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['member_id']) && !$isPast): ?>
                    <?php if ($isAttending): ?>
                        <div class="alert alert-success mb-3">
                            <i class="cil-check-circle"></i> <strong>You're attending!</strong>
                        </div>
                        <a href="event_register.php?id=<?php echo $event['id']; ?>&action=unregister" 
                           class="btn btn-outline-danger btn-sm w-100 mb-2"
                           onclick="return confirm('Are you sure you want to unregister from this event?')">
                            <i class="cil-x"></i> Unregister
                        </a>
                    <?php else: ?>
                        <a href="event_register.php?id=<?php echo $event['id']; ?>&action=register" 
                           class="btn btn-primary btn-sm w-100 mb-2">
                            <i class="cil-check"></i> Register to Attend
                        </a>
                    <?php endif; ?>
                <?php elseif (isset($_SESSION['member_id']) && $isPast): ?>
                    <div class="alert alert-secondary mb-0">
                        <small>Registration closed - Event has ended</small>
                    </div>
                <?php endif; ?>
                
                <?php if (hasAnyRole(['Admin', 'Executive'])): ?>
                    <a href="event_edit.php?id=<?php echo $event['id']; ?>" class="btn btn-warning btn-sm w-100 mb-2">
                        <i class="cil-pencil"></i> Edit Event
                    </a>
                    <a href="events.php?delete=<?php echo $event['id']; ?>" 
                       class="btn btn-danger btn-sm w-100"
                       onclick="return confirm('Are you sure you want to delete this event?')">
                        <i class="cil-trash"></i> Delete Event
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
