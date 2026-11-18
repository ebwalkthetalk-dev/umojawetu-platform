<?php

// --- AUTH ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Diagnostic Tool - Check all files and folders
echo "<h1>Umojawetu Platform Diagnostic</h1>";

$basePath = 'C:/xampp/htdocs/umojawetu-platform/';

// Function to check if file exists
function checkFile($path, $name) {
    $fullPath = $GLOBALS['basePath'] . $path;
    if (file_exists($fullPath)) {
        echo "<span style='color:green'>âœ…</span> $name<br>";
        return true;
    } else {
        echo "<span style='color:red'>âŒ</span> $name (missing at: $path)<br>";
        return false;
    }
}

// Function to check folder
function checkFolder($path, $name) {
    $fullPath = $GLOBALS['basePath'] . $path;
    if (is_dir($fullPath)) {
        echo "<span style='color:green'>âœ…</span> $name<br>";
        return true;
    } else {
        echo "<span style='color:orange'>âš ï¸</span> $name (folder missing)<br>";
        return false;
    }
}

echo "<h2>ðŸ“ Folders:</h2>";
checkFolder('public', 'Public folder');
checkFolder('admin', 'Admin folder');
checkFolder('api', 'API folder');
checkFolder('api/events', 'API/Events folder');
checkFolder('api/media', 'API/Media folder');
checkFolder('api/booking', 'API/Booking folder');
checkFolder('api/requests', 'API/Requests folder');
checkFolder('config', 'Config folder');
checkFolder('storage', 'Storage folder');
checkFolder('storage/uploads', 'Storage/Uploads folder');
checkFolder('storage/uploads/media/images', 'Images folder');
checkFolder('storage/uploads/media/videos', 'Videos folder');
checkFolder('assets', 'Assets folder');

echo "<h2>ðŸ“„ Public Files:</h2>";
checkFile('public/index.php', 'index.php');
checkFile('public/events.php', 'events.php');
checkFile('public/booking.php', 'booking.php');
checkFile('public/gallery.php', 'gallery.php');
checkFile('public/video-gallery.php', 'video-gallery.php');
checkFile('public/request.php', 'request.php');
checkFile('public/status.php', 'status.php');

echo "<h2>ðŸ” Admin Files:</h2>";
checkFile('admin/login.php', 'login.php');
checkFile('admin/dashboard.php', 'dashboard.php');
checkFile('admin/events.php', 'events.php');
checkFile('admin/attendees.php', 'attendees.php');
checkFile('admin/requests.php', 'requests.php');
checkFile('admin/media-manager.php', 'media-manager.php');
checkFile('admin/payments.php', 'payments.php');
checkFile('admin/analytics.php', 'analytics.php');
checkFile('admin/email-templates.php', 'email-templates.php');
checkFile('admin/admins.php', 'admins.php');
checkFile('admin/logout.php', 'logout.php');

echo "<h2>âš™ï¸ API Files:</h2>";
checkFile('api/events/events.php', 'events.php');
checkFile('api/media/media-list.php', 'media-list.php');
checkFile('api/media/media-videos.php', 'media-videos.php');
checkFile('api/media/media-upload.php', 'media-upload.php');
checkFile('api/media/serve-media.php', 'serve-media.php');
checkFile('api/booking/bookings-wizard.php', 'bookings-wizard.php');
checkFile('api/requests/submit-request.php', 'submit-request.php');
checkFile('api/requests/get-status.php', 'get-status.php');

echo "<h2>âš™ï¸ Config Files:</h2>";
checkFile('config/config.php', 'config.php');
checkFile('config/environments.php', 'environments.php');

echo "<h2>ðŸ“¦ Database:</h2>";
checkFile('storage/umoja-events-database.db', 'SQLite Database');

echo "<h2>ðŸ–¼ï¸ Images in Gallery:</h2>";
$imagesPath = $basePath . 'storage/uploads/media/images/';
if (is_dir($imagesPath)) {
    $images = scandir($imagesPath);
    $imageCount = count(array_filter($images, function($f) { return !in_array($f, ['.', '..']); }));
    echo "<p>ðŸ“¸ $imageCount image(s) found in images folder</p>";
} else {
    echo "<p>âŒ Images folder not found</p>";
}

echo "<h2>ðŸŽ¥ Videos in Gallery:</h2>";
$videosPath = $basePath . 'storage/uploads/media/videos/';
if (is_dir($videosPath)) {
    $videos = scandir($videosPath);
    $videoCount = count(array_filter($videos, function($f) { return !in_array($f, ['.', '..']); }));
    echo "<p>ðŸŽ¬ $videoCount video(s) found in videos folder</p>";
} else {
    echo "<p>âŒ Videos folder not found</p>";
}
?>