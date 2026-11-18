<?php
// Simple QR Code using HTML/CSS (no image generation)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/security.php';
requireAdminLogin();
$attendeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Code - Attendee <?php echo $attendeeId; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f0f0f0; }
        .qr-container { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        #qrcode { margin: 20px auto; }
        .info { margin-top: 20px; text-align: left; border-top: 1px solid #eee; padding-top: 15px; }
        button { margin-top: 20px; padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="qr-container">
        <h2>UMOJA WETU</h2>
        <p>Event Admission QR Code</p>
        <div id="qrcode"></div>
        <div class="info">
            <p><strong>Attendee ID:</strong> <span id="attendeeId"><?php echo $attendeeId; ?></span></p>
            <p><strong>Name:</strong> <span id="attendeeName">Loading...</span></p>
            <p><strong>Event:</strong> <span id="eventName">Loading...</span></p>
        </div>
        <button onclick="window.print()">🖨️ Print QR Code</button>
    </div>

    <script>
        // Fetch attendee data
        fetch(`/umojawetu-platform/api/attendees/get-attendee.php?id=<?php echo $attendeeId; ?>`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('attendeeName').innerText = data.name;
                    document.getElementById('eventName').innerText = data.event_title;
                    
                    // Generate QR code
                    const qrData = JSON.stringify({
                        id: data.id,
                        name: data.name,
                        booking_ref: data.booking_ref,
                        event: data.event_title
                    });
                    new QRCode(document.getElementById("qrcode"), {
                        text: qrData,
                        width: 200,
                        height: 200,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            })
            .catch(err => console.error('Error:', err));
    </script>
</body>
</html>