<?php
// Booking Wizard API Endpoint
// Accepts booking data from new booking wizard frontend
// Creates Bookings and Attendees records with individual names

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!$input || !isset($input['eventId']) || !isset($input['fullName']) || !isset($input['email'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: eventId, fullName, email'
    ]);
    exit;
}

try {
    $db = getDbConnection();
    
    // Validate event exists
    $eventId = intval($input['eventId']);
    $event = $db->querySingle("SELECT Id, Title FROM Events WHERE Id = $eventId", true);
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Event not found']);
        exit;
    }
    
    // Extract and validate booking data
    $fullName = trim($input['fullName'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = trim($input['phone'] ?? '');
    $memberStatus = $input['memberStatus'] ?? 'non-member';
    
    $adults = intval($input['adults'] ?? 0);
    $children = intval($input['children'] ?? 0);
    $vip = intval($input['vip'] ?? 0);
    
    $volunteerAmount = floatval($input['volunteerAmount'] ?? 0);
    $paymentIntentId = $input['paymentIntentId'] ?? null;
    $totalAmount = floatval($input['totalAmount'] ?? 0);
    
    // Get attendee name arrays
    $adultNames = $input['adult_names'] ?? [];
    $childNames = $input['child_names'] ?? [];
    $vipNames = $input['vip_names'] ?? [];
    
    // Generate booking reference
    $bookingRef = 'UMJ-' . strtoupper(substr(uniqid(), -8));
    
    // Determine payment status
    $paymentStatus = empty($paymentIntentId) ? 'pending' : 'completed';
    $status = empty($paymentIntentId) ? 'pending' : 'confirmed';
    
    // Insert booking record
    $insertBooking = "
        INSERT INTO Bookings (
            BookingReference, EventId, FullName, Email, Phone,
            Adults, Children, VIP, VolunteerAmount, MemberStatus,
            PaymentMethod, PaymentStatus, Status, TotalAmount, PaymentIntentId,
            CreatedAt, UpdatedAt
        ) VALUES (
            :reference, :eventId, :fullName, :email, :phone,
            :adults, :children, :vip, :volunteerAmount, :memberStatus,
            :paymentMethod, :paymentStatus, :status, :totalAmount, :paymentIntentId,
            datetime('now'), datetime('now')
        )
    ";
    
    $stmt = $db->prepare($insertBooking);
    $stmt->bindValue(':reference', $bookingRef, SQLITE3_TEXT);
    $stmt->bindValue(':eventId', $eventId, SQLITE3_INTEGER);
    $stmt->bindValue(':fullName', $fullName, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':adults', $adults, SQLITE3_INTEGER);
    $stmt->bindValue(':children', $children, SQLITE3_INTEGER);
    $stmt->bindValue(':vip', $vip, SQLITE3_INTEGER);
    $stmt->bindValue(':volunteerAmount', $volunteerAmount > 0 ? $volunteerAmount : 0, SQLITE3_FLOAT);
    $stmt->bindValue(':memberStatus', $memberStatus, SQLITE3_TEXT);
    $stmt->bindValue(':paymentMethod', 'stripe', SQLITE3_TEXT);
    $stmt->bindValue(':paymentStatus', $paymentStatus, SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':totalAmount', max($totalAmount, 0), SQLITE3_FLOAT);
    $stmt->bindValue(':paymentIntentId', $paymentIntentId, SQLITE3_TEXT);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert booking record');
    }
    
    $bookingId = $db->lastInsertRowid();
    
    // Insert attendee records with individual names
    
    // Adults
    for ($i = 0; $i < $adults; $i++) {
        $attendeeName = trim($adultNames[$i] ?? $fullName);
        
        $insertAttendee = "
            INSERT INTO Attendees (
                BookingId, EventId, Name, Email, phone, member_status, 
                ticket_type, CheckedIn, CreatedAt
            ) VALUES (
                :booking_id, :event_id, :name, :email, :phone, :member_status,
                :ticket_type, 0, datetime('now')
            )
        ";
        
        $stmt = $db->prepare($insertAttendee);
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $attendeeName, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $memberStatus, SQLITE3_TEXT);
        $stmt->bindValue(':ticket_type', 'adult', SQLITE3_TEXT);
        
        $stmt->execute();
    }
    
    // Children
    for ($i = 0; $i < $children; $i++) {
        $attendeeName = trim($childNames[$i] ?? $fullName);
        
        $insertAttendee = "
            INSERT INTO Attendees (
                BookingId, EventId, Name, Email, phone, member_status, 
                ticket_type, CheckedIn, CreatedAt
            ) VALUES (
                :booking_id, :event_id, :name, :email, :phone, :member_status,
                :ticket_type, 0, datetime('now')
            )
        ";
        
        $stmt = $db->prepare($insertAttendee);
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $attendeeName, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $memberStatus, SQLITE3_TEXT);
        $stmt->bindValue(':ticket_type', 'child', SQLITE3_TEXT);
        
        $stmt->execute();
    }
    
    // VIP
    for ($i = 0; $i < $vip; $i++) {
        $attendeeName = trim($vipNames[$i] ?? $fullName);
        
        $insertAttendee = "
            INSERT INTO Attendees (
                BookingId, EventId, Name, Email, phone, member_status, 
                ticket_type, CheckedIn, CreatedAt
            ) VALUES (
                :booking_id, :event_id, :name, :email, :phone, :member_status,
                :ticket_type, 0, datetime('now')
            )
        ";
        
        $stmt = $db->prepare($insertAttendee);
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $attendeeName, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $memberStatus, SQLITE3_TEXT);
        $stmt->bindValue(':ticket_type', 'vip', SQLITE3_TEXT);
        
        $stmt->execute();
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'bookingReference' => $bookingRef,
        'bookingId' => $bookingId,
        'eventId' => $eventId,
        'eventTitle' => $event['Title'],
        'totalAmount' => $totalAmount,
        'volunteerAmount' => $volunteerAmount,
        'attendeeCount' => ($adults + $children + $vip),
        'message' => "Booking $bookingRef created successfully with " . ($adults + $children + $vip) . " attendee(s)"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>