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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>QR Scanner - Check-in</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #video-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #000;
            border-radius: 16px;
            overflow: hidden;
        }
        #video {
            width: 100%;
            height: auto;
        }
        #canvas {
            display: none;
        }
        .scan-area {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1000px rgba(0,0,0,0.5);
            border-radius: 10px;
        }
        .scan-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #e74c3c;
            animation: scan 2s linear infinite;
        }
        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
        .result-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; }
        .result-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white text-center">
                <h1 class="text-2xl font-bold">ðŸ“· QR Scanner</h1>
                <p class="text-red-100">Position QR code in front of camera</p>
            </div>
            <div class="p-6">
                <div id="video-container" style="position: relative;">
                    <video id="video" playsinline autoplay></video>
                    <canvas id="canvas"></canvas>
                    <div class="scan-area">
                        <div class="scan-line"></div>
                    </div>
                </div>
                <div id="result" class="mt-4"></div>
                <div class="mt-6 text-center">
                    <button onclick="startCamera()" class="bg-blue-600 text-white px-4 py-2 rounded-lg">ðŸ”„ Restart Camera</button>
                    <a href="attendees.php" class="text-red-600 hover:underline ml-4">â† Back to Attendees</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let ctx = canvas.getContext('2d');
        let scanning = true;
        
        // Start camera
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "environment" } 
                });
                video.srcObject = stream;
                video.setAttribute('playsinline', true);
                video.play();
                startScanning();
            } catch (err) {
                console.error("Camera error:", err);
                document.getElementById('result').innerHTML = `
                    <div class="result-error">
                        âŒ Cannot access camera. Please:<br>
                        1. Allow camera access<br>
                        2. Use HTTPS or localhost<br>
                        3. Check if camera is not in use
                    </div>
                `;
            }
        }
        
        function startScanning() {
            setInterval(() => {
                if (!scanning) return;
                
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    let imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    let code = jsQR(imageData.data, imageData.width, imageData.height);
                    
                    if (code) {
                        scanning = false;
                        processQRCode(code.data);
                    }
                }
            }, 500);
        }
        
        function processQRCode(qrText) {
            const match = qrText.match(/\bID[:\s]*(\d+)\b/i);
            const attendeeId = match ? match[1] : null;
            
            if (attendeeId) {
                fetch(`/api/attendees/checkin-by-id.php?id=${attendeeId}`)
                    .then(res => res.json())
                    .then(data => {
                        const resultDiv = document.getElementById('result');
                        if (data.success) {
                            resultDiv.innerHTML = `<div class="result-success">âœ… ${data.message}<br><strong>${data.name}</strong> checked in!</div>`;
                            setTimeout(() => { window.location.href = 'attendees.php'; }, 2000);
                        } else {
                            resultDiv.innerHTML = `<div class="result-error">âŒ ${data.message}<br><button onclick="restartScanning()" class="mt-2 bg-red-600 text-white px-3 py-1 rounded">Scan Again</button></div>`;
                        }
                    });
            } else {
                document.getElementById('result').innerHTML = `<div class="result-error">âŒ Invalid QR code<br><button onclick="restartScanning()" class="mt-2 bg-red-600 text-white px-3 py-1 rounded">Scan Again</button></div>`;
            }
        }
        
        function restartScanning() {
            scanning = true;
            document.getElementById('result').innerHTML = '';
        }
        
        // Load jsQR library
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
        script.onload = () => {
            startCamera();
        };
        document.head.appendChild(script);
    </script>
</body>
</html>