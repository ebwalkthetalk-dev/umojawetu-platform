<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Fix filenames in database to match actual files
header('Content-Type: text/html');

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$imagesPath = 'C:/xampp/htdocs/umojawetu-platform/storage/uploads/media/images/';

$db = new SQLite3($dbPath);

// Get all files from the images folder
$actualFiles = scandir($imagesPath);
$actualFiles = array_filter($actualFiles, function($file) {
    return $file !== '.' && $file !== '..';
});

echo "<h1>Fixing Filenames in Database</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Database Filename</th><th>Actual File</th><th>Action</th></tr>";

// Get all media records
$result = $db->query("SELECT Id, FileName FROM EventMedia WHERE MediaType = 'image'");
$updated = 0;

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $dbFilename = $row['FileName'];
    $dbId = $row['Id'];
    
    // Try to find matching actual file (by partial match)
    $matchedFile = null;
    foreach ($actualFiles as $actualFile) {
        // Check if the actual file contains the database filename or vice versa
        if (strpos($actualFile, $dbFilename) !== false || strpos($dbFilename, $actualFile) !== false) {
            $matchedFile = $actualFile;
            break;
        }
    }
    
    if ($matchedFile && $matchedFile !== $dbFilename) {
        // Update database with correct filename
        $update = $db->prepare("UPDATE EventMedia SET FileName = :newName WHERE Id = :id");
        $update->bindValue(':newName', $matchedFile, SQLITE3_TEXT);
        $update->bindValue(':id', $dbId, SQLITE3_INTEGER);
        $update->execute();
        
        echo "<tr style='background:#d4edda'>";
        echo "<td>" . htmlspecialchars($dbFilename) . "</td>";
        echo "<td>" . htmlspecialchars($matchedFile) . "</td>";
        echo "<td>âœ… Updated</td>";
        echo "</tr>";
        $updated++;
    } elseif (!$matchedFile) {
        echo "<tr style='background:#f8d7da'>";
        echo "<td>" . htmlspecialchars($dbFilename) . "</td>";
        echo "<td colspan='2'>âŒ No matching file found</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "<h2>Total updated: $updated</h2>";
echo "<p><a href='/umojawetu-platform/public/gallery.php'>Go to Gallery â†’</a></p>";

$db->close();
?>