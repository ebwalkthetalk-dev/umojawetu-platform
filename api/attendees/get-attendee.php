<?php
// Get attendee data for preview
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');
setCorsHeaders();

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---

$attendeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$attendeeId) {
    echo json_encode(['success' => false, 'error' => 'No attendee ID provided']);
    exit;
}

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}

$db = new SQLite3($dbPath);

$stmt = $db->prepare("
    SELECT a.Id, a.Name, a.Email, a.phone, a.ticket_type, a.CheckedIn,
           b.BookingReference, e.Title as EventTitle, e.Date as EventDate
    FROM Attendees a 
    LEFT JOIN Bookings b ON a.BookingId = b.Id 
    LEFT JOIN Events e ON a.EventId = e.Id 
    WHERE a.Id = :id
");
$stmt->bindValue(':id', $attendeeId, SQLITE3_INTEGER);
$result = $stmt->execute();
$attendee = $result->fetchArray(SQLITE3_ASSOC);

$db->close();

if ($attendee) {
    echo json_encode([
        'success' => true,
        'id' => $attendee['Id'],
        'name' => $attendee['Name'],
        'email' => $attendee['Email'],
        'phone' => $attendee['phone'],
        'ticket_type' => $attendee['ticket_type'],
        'checked_in' => $attendee['CheckedIn'],
        'booking_ref' => $attendee['BookingReference'],
        'event_title' => $attendee['EventTitle'],
        'event_date' => $attendee['EventDate']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Attendee not found']);
}
?>