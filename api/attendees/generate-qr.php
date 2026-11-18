<?php
// Simple QR Code Generator - No external libraries needed
require_once __DIR__ . '/../../config/config.php';

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---

$attendeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$attendeeId) {
    die('Invalid attendee ID');
}

// Get attendee data
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

$stmt = $db->prepare("
    SELECT a.Id, a.Name, a.Email, a.phone, a.ticket_type, 
           b.BookingReference, e.Title as EventTitle
    FROM Attendees a 
    LEFT JOIN Bookings b ON a.BookingId = b.Id 
    LEFT JOIN Events e ON a.EventId = e.Id 
    WHERE a.Id = :id
");
$stmt->bindValue(':id', $attendeeId, SQLITE3_INTEGER);
$result = $stmt->execute();
$attendee = $result->fetchArray(SQLITE3_ASSOC);
$db->close();

if (!$attendee) {
    die('Attendee not found');
}

// Create QR data
$qrData = base64_encode(json_encode([
    'id' => $attendee['Id'],
    'name' => $attendee['Name'],
    'booking' => $attendee['BookingReference'],
    'event' => $attendee['EventTitle'],
    'valid' => true
]));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($attendee['Name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .qr-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .qr-code {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin: 20px 0;
            border: 2px solid #e74c3c;
        }
        .qr-code canvas {
            margin: 0 auto;
            display: block;
        }
        .info {
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
        }
        .info p { margin: 8px 0; font-size: 14px; }
        .info strong { color: #e74c3c; }
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: #e74c3c; color: white; }
        .btn-secondary { background: #3498db; color: white; }
        @media print {
            body { background: white; padding: 0; }
            .buttons { display: none; }
            .qr-card { box-shadow: none; padding: 0; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body>
    <div class="qr-card">
        <div class="header">
            <h1>ðŸŽŸï¸ UMOJA WETU</h1>
            <p>Event Admission Ticket</p>
        </div>
        
        <div class="qr-code">
            <div id="qrcode"></div>
        </div>
        
        <div class="info">
            <p><strong>Attendee:</strong> <?php echo htmlspecialchars($attendee['Name']); ?></p>
            <p><strong>Ticket ID:</strong> #<?php echo $attendee['Id']; ?></p>
            <p><strong>Booking Ref:</strong> <?php echo htmlspecialchars($attendee['BookingReference']); ?></p>
            <p><strong>Event:</strong> <?php echo htmlspecialchars($attendee['EventTitle']); ?></p>
            <p><strong>Ticket Type:</strong> <?php echo ucfirst($attendee['ticket_type'] ?? 'Standard'); ?></p>
        </div>
        
        <div class="buttons">
            <button class="btn btn-primary" onclick="window.print()">ðŸ–¨ï¸ Print Ticket</button>
            <button class="btn btn-secondary" onclick="window.close()">âœ–ï¸ Close</button>
        </div>
    </div>

    <script>
        // Create QR code with attendee data
        const qrData = <?php echo json_encode($qrData); ?>;
        const qrText = `ID: ${<?php echo $attendee['Id']; ?>}\nName: ${<?php echo json_encode($attendee['Name']); ?>}\nBooking: ${<?php echo json_encode($attendee['BookingReference']); ?>}`;
        
        new QRCode(document.getElementById("qrcode"), {
            text: qrText,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>