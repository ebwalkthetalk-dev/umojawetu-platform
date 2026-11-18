<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
header('Content-Type: text/html');

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

echo "<h1>Database Filenames</h1>";
echo "<ul>";

$result = $db->query("SELECT Id, FileName FROM EventMedia WHERE MediaType = 'image'");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<li>ID: {$row['Id']} - Filename: " . htmlspecialchars($row['FileName']) . "</li>";
}

echo "</ul>";

// Also check the actual files
$imagesPath = 'C:/xampp/htdocs/umojawetu-platform/storage/uploads/media/images/';
$actualFiles = scandir($imagesPath);

echo "<h1>Actual Files in Folder</h1>";
echo "<ul>";
foreach ($actualFiles as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
}
echo "</ul>";

$db->close();
?>