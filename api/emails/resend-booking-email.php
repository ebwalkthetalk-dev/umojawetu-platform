<?php
// Resend Booking Email API - FIXED VERSION
require_once __DIR__ . '/../../config/config.php';

// Simple email function for testing
function sendSimpleEmail($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Umojawetu Platform <noreply@localhost>\r\n";
    
    // Log instead of send for local testing
    $logDir = __DIR__ . '/../../storage/logs';
    if (!file_exists($logDir)) mkdir($logDir, 0777, true);
    $logFile = $logDir . '/email_resend.log';
    $logEntry = date('Y-m-d H:i:s') . " | TO: $to | SUBJECT: $subject\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return true;
}

header('Content-Type: application/json');
setCorsHeaders();

$input = json_decode(file_get_contents('php://input'), true);
$bookingIds = $input['booking_ids'] ?? [];
$action = $input['action'] ?? 'send';
$previewOnly = $action === 'preview';

if (empty($bookingIds)) {
    echo json_encode(['success' => false, 'error' => 'No booking IDs provided']);
    exit;
}

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}
$db = new SQLite3($dbPath);

$results = [];

foreach ($bookingIds as $bookingId) {
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, e.Title as event_title, e.Date as event_date 
        FROM Bookings b 
        LEFT JOIN Events e ON b.EventId = e.Id 
        WHERE b.Id = :id
    ");
    $stmt->bindValue(':id', $bookingId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $booking = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$booking) {
        $results[] = ['id' => $bookingId, 'success' => false, 'error' => 'Booking not found'];
        continue;
    }
    
    $tickets = [
        'adult' => $booking['Adults'] ?? 0,
        'child' => $booking['Children'] ?? 0,
        'vip' => $booking['VIP'] ?? 0
    ];
    
    // Build email body
    $emailBody = "<h2>Booking Confirmation</h2>
                  <p>Dear {$booking['FullName']},</p>
                  <p>Your booking has been confirmed.</p>
                  <hr>
                  <p><strong>Booking Reference:</strong> {$booking['BookingReference']}</p>
                  <p><strong>Event:</strong> {$booking['event_title']}</p>
                  <p><strong>Date:</strong> {$booking['event_date']}</p>
                  <p><strong>Adults:</strong> {$tickets['adult']}</p>
                  <p><strong>Children:</strong> {$tickets['child']}</p>
                  <p><strong>Total:</strong> £{$booking['TotalAmount']}</p>
                  <hr>
                  <p>Thank you for your booking!</p>";
    
    if ($previewOnly) {
        $results[] = [
            'id' => $bookingId,
            'success' => true,
            'preview' => $emailBody,
            'to' => $booking['Email'],
            'subject' => "Booking Confirmation - {$booking['BookingReference']}"
        ];
    } else {
        $sent = sendSimpleEmail($booking['Email'], "Booking Confirmation - {$booking['BookingReference']}", $emailBody);
        $results[] = [
            'id' => $bookingId,
            'success' => $sent,
            'to' => $booking['Email'],
            'booking_ref' => $booking['BookingReference']
        ];
    }
}

$db->close();

echo json_encode(['success' => true, 'results' => $results, 'preview_mode' => $previewOnly]);
?>