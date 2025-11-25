<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Only Admin can remove attendees
if (!hasRole('Admin')) {
    setFlashMessage('danger', 'You do not have permission to perform this action');
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

// Get parameters
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$memberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if (!$eventId || !$memberId) {
    setFlashMessage('danger', 'Invalid parameters');
    redirect('events.php');
}

try {
    // Remove attendee
    $deleteQuery = "DELETE FROM event_attendees WHERE event_id = :event_id AND member_id = :member_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':event_id', $eventId);
    $deleteStmt->bindParam(':member_id', $memberId);
    
    if ($deleteStmt->execute() && $deleteStmt->rowCount() > 0) {
        setFlashMessage('success', 'Attendee removed successfully');
    } else {
        setFlashMessage('info', 'Attendee was not registered for this event');
    }
} catch (Exception $e) {
    error_log("Remove attendee error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to remove attendee');
}

redirect('event_attendance.php?id=' . $eventId);
?>
