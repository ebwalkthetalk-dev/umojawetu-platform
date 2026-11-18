<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Serve Media API - Fixed with correct path
header('Cache-Control: public, max-age=86400');

$file = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($file)) {
    http_response_code(400);
    echo 'No file specified';
    exit();
}

// Clean the filename for security
$file = basename($file);
$file = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $file);

// EXACT PATH WHERE YOUR IMAGES ARE STORED
$imagePath = 'C:/xampp/htdocs/umojawetu-platform/storage/uploads/media/images/' . $file;
$videoPath = 'C:/xampp/htdocs/umojawetu-platform/storage/uploads/media/videos/' . $file;

$filePath = null;

if (file_exists($imagePath)) {
    $filePath = $imagePath;
} elseif (file_exists($videoPath)) {
    $filePath = $videoPath;
}

if (!$filePath) {
    // Return a placeholder image
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400"><rect width="400" height="400" fill="#6b21a5"/><text x="200" y="200" font-size="20" fill="white" text-anchor="middle" dy=".3em">Image Not Found</text></svg>';
    exit();
}

// Set correct content type based on file extension
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = 'application/octet-stream';

if (in_array($ext, ['jpg', 'jpeg'])) $contentType = 'image/jpeg';
elseif ($ext == 'png') $contentType = 'image/png';
elseif ($ext == 'gif') $contentType = 'image/gif';
elseif ($ext == 'webp') $contentType = 'image/webp';
elseif ($ext == 'mp4') $contentType = 'video/mp4';
elseif ($ext == 'webm') $contentType = 'video/webm';

header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filePath));

// Output the file
readfile($filePath);
exit();
?>