<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Add videos from folder to database
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

$videosFolder = 'C:/xampp/htdocs/umojawetu-platform/storage/uploads/media/videos/';
$videoFiles = scandir($videosFolder);

echo "<h1>ðŸ“¹ Adding Videos to Database</h1>";
echo "<ul>";

$added = 0;
$existing = 0;

foreach ($videoFiles as $file) {
    if ($file === '.' || $file === '..') continue;
    
    // Check if already in database
    $check = $db->prepare("SELECT Id FROM EventMedia WHERE FileName = :file");
    $check->bindValue(':file', $file, SQLITE3_TEXT);
    $result = $check->execute();
    $exists = $result->fetchArray();
    
    if (!$exists) {
        // Insert video
        $insert = $db->prepare("
            INSERT INTO EventMedia (FileName, OriginalName, MediaType, Caption, Year, UploadedAt, IsActive)
            VALUES (:file, :file, 'video', 'Event Video', 2025, datetime('now'), 1)
        ");
        $insert->bindValue(':file', $file, SQLITE3_TEXT);
        $insert->execute();
        echo "<li style='color:green'>âœ… Added: $file</li>";
        $added++;
    } else {
        echo "<li style='color:blue'>âµ Already exists: $file</li>";
        $existing++;
    }
}

echo "</ul>";
echo "<p><strong>Summary:</strong> $added added, $existing already existed</p>";
echo "<p><a href='/umojawetu-platform/api/media/media-videos.php'>ðŸ“¡ Check Video API</a></p>";
echo "<p><a href='/umojawetu-platform/public/video-gallery.php'>ðŸŽ¬ Go to Video Gallery</a></p>";

$db->close();
?>