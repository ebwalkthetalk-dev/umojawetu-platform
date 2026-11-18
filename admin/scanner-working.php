<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - Check-in</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #reader video {
            border-radius: 16px;
            width: 100%;
        }
        .result-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .result-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white text-center">
                <h1 class="text-2xl font-bold">📷 QR Scanner</h1>
                <p class="text-red-100">Scan attendee QR code to check in</p>
            </div>
            <div class="p-6">
                <div id="reader"></div>
                <div id="result" class="mt-4"></div>
                <div class="mt-6 text-center">
                    <a href="attendees.php" class="text-red-600 hover:underline">← Back to Attendees</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const html5QrCode = new Html5Qrcode("reader");
        
        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            html5QrCode.stop();
            
            // Parse QR code for ID
            const match = decodedText.match(/\bID[:\s]*(\d+)\b/i);
            const attendeeId = match ? match[1] : null;
            
            const resultDiv = document.getElementById('result');
            
            if (attendeeId) {
                // Check in the attendee
                fetch(`/umojawetu-platform/api/attendees/checkin-by-id.php?id=${attendeeId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.innerHTML = `
                                <div class="result-success">
                                    ✅ ${data.message}<br>
                                    <strong>${data.name}</strong> has been checked in!
                                    <div class="mt-3">
                                        <a href="attendees.php" class="bg-green-600 text-white px-4 py-2 rounded-lg inline-block">Return to Attendees</a>
                                    </div>
                                </div>
                            `;
                        } else {
                            resultDiv.innerHTML = `
                                <div class="result-error">
                                    ❌ ${data.message}<br>
                                    <button onclick="restartScanner()" class="mt-3 bg-red-600 text-white px-4 py-2 rounded-lg">Scan Again</button>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        resultDiv.innerHTML = `
                            <div class="result-error">
                                ❌ Error checking in<br>
                                <button onclick="restartScanner()" class="mt-3 bg-red-600 text-white px-4 py-2 rounded-lg">Try Again</button>
                            </div>
                        `;
                    });
            } else {
                resultDiv.innerHTML = `
                    <div class="result-error">
                        ❌ Invalid QR code. Please scan a valid attendee QR code.<br>
                        <button onclick="restartScanner()" class="mt-3 bg-red-600 text-white px-4 py-2 rounded-lg">Scan Again</button>
                    </div>
                `;
            }
        }
        
        function onScanFailure(error) {
            // Silently continue scanning - this is normal
            console.log("Scanning...");
        }
        
        function restartScanner() {
            document.getElementById('result').innerHTML = '';
            startScanner();
        }
        
        function startScanner() {
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                console.error("Scanner error:", err);
                document.getElementById('result').innerHTML = `
                    <div class="result-error">
                        ❌ Camera error: ${err}<br>
                        Please ensure camera is accessible.
                    </div>
                `;
            });
        }
        
        // Start scanner on page load
        startScanner();
    </script>
</body>
</html>