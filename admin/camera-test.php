<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Camera Test</title>
</head>
<body>
    <h1>Camera Test</h1>
    <video id="video" width="400" height="300" autoplay playsinline></video>
    <p id="status">Checking camera...</p>
    
    <script>
        const video = document.getElementById('video');
        const status = document.getElementById('status');
        
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                status.innerHTML = '✅ Camera is working!';
                status.style.color = 'green';
            })
            .catch(err => {
                status.innerHTML = '❌ Camera error: ' + err.message;
                status.style.color = 'red';
                console.error(err);
            });
    </script>
</body>
</html>