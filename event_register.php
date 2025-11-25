<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Must be logged in as member
if (!isset($_SESSION['member_id'])) {
    setFlashMessage('danger', 'You must be logged in as a member to register for events');
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get event ID
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$eventId) {
    setFlashMessage('danger', 'Invalid event ID');
    redirect('events.php');
}

// Verify event exists and is not past
$eventQuery = "SELECT * FROM events WHERE id = :id";
$eventStmt = $db->prepare($eventQuery);
$eventStmt->bindParam(':id', $eventId);
$eventStmt->execute();
$event = $eventStmt->fetch();

if (!$event) {
    setFlashMessage('danger', 'Event not found');
    redirect('events.php');
}

// Check if event is in the past
$eventDateTime = strtotime($event['event_date'] . ' ' . $event['event_time']);
if ($eventDateTime < time()) {
    setFlashMessage('warning', 'Cannot register for past events');
    redirect('event_view.php?id=' . $eventId);
}

$memberId = $_SESSION['member_id'];

// Handle registration/unregistration
if ($action === 'register') {
    try {
        // Check if already registered
        $checkQuery = "SELECT id FROM event_attendees WHERE event_id = :event_id AND member_id = :member_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':event_id', $eventId);
        $checkStmt->bindParam(':member_id', $memberId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            setFlashMessage('info', 'You are already registered for this event');
        } else {
            // Register for event
            $insertQuery = "INSERT INTO event_attendees (event_id, member_id, registered_at) 
                           VALUES (:event_id, :member_id, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':event_id', $eventId);
            $insertStmt->bindParam(':member_id', $memberId);
            
            if ($insertStmt->execute()) {
                setFlashMessage('success', 'Successfully registered for event!');
            } else {
                setFlashMessage('danger', 'Failed to register for event');
            }
        }
    } catch (Exception $e) {
        error_log("Event registration error: " . $e->getMessage());
        setFlashMessage('danger', 'Failed to register for event. Please try again.');
    }
} elseif ($action === 'unregister') {
    try {
        // Unregister from event
        $deleteQuery = "DELETE FROM event_attendees WHERE event_id = :event_id AND member_id = :member_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':event_id', $eventId);
        $deleteStmt->bindParam(':member_id', $memberId);
        
        if ($deleteStmt->execute() && $deleteStmt->rowCount() > 0) {
            setFlashMessage('success', 'Successfully unregistered from event');
        } else {
            setFlashMessage('info', 'You were not registered for this event');
        }
    } catch (Exception $e) {
        error_log("Event unregistration error: " . $e->getMessage());
        setFlashMessage('danger', 'Failed to unregister from event. Please try again.');
    }
} else {
    setFlashMessage('danger', 'Invalid action');
}

redirect('event_view.php?id=' . $eventId);
?>
