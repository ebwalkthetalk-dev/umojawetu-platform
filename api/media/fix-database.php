<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Fix database - add missing columns
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

echo "<h1>Fixing Database Schema</h1>";

// Check what columns exist
$columns = $db->query("PRAGMA table_info(EventMedia)");
$existingColumns = [];
while ($col = $columns->fetchArray(SQLITE3_ASSOC)) {
    $existingColumns[] = $col['name'];
    echo "<p>Column exists: " . $col['name'] . "</p>";
}

// Add StoragePath column if missing
if (!in_array('StoragePath', $existingColumns)) {
    $db->exec("ALTER TABLE EventMedia ADD COLUMN StoragePath TEXT");
    echo "<p style='color:green'>âœ… Added StoragePath column</p>";
}

// Add ThumbnailPath column if missing
if (!in_array('ThumbnailPath', $existingColumns)) {
    $db->exec("ALTER TABLE EventMedia ADD COLUMN ThumbnailPath TEXT");
    echo "<p style='color:green'>âœ… Added ThumbnailPath column</p>";
}

// Update existing records with StoragePath
$update = $db->exec("UPDATE EventMedia SET StoragePath = FileName WHERE StoragePath IS NULL");
echo "<p style='color:green'>âœ… Updated StoragePath for existing records</p>";

echo "<p><a href='/umojawetu-platform/api/media/add-videos.php'>Go back to add videos</a></p>";

$db->close();
?>