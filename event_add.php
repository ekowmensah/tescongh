<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}

$pageTitle = 'Create Event';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $event_date = sanitize($_POST['event_date']);
    $event_time = sanitize($_POST['event_time']);
    $location = sanitize($_POST['location']);
    
    $query = "INSERT INTO events (title, description, event_date, event_time, location, created_by) 
              VALUES (:title, :description, :event_date, :event_time, :location, :created_by)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':event_date', $event_date);
    $stmt->bindParam(':event_time', $event_time);
    $stmt->bindParam(':location', $location);
    $stmt->bindValue(':created_by', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Event created successfully');
        redirect('events.php');
    } else {
        setFlashMessage('danger', 'Failed to create event');
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Create New Event</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="events.php" class="btn btn-secondary">
            <i class="cil-arrow-left"></i> Back to Events
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <form method="POST" action="">
            <div class="card">
                <div class="card-header">
                    <strong>Event Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Event Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Event Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="event_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Event Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="event_time" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="location" required>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="cil-check"></i> Create Event
                    </button>
                    <a href="events.php" class="btn btn-secondary">
                        <i class="cil-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
