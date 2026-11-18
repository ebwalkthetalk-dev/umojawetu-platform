<?php
// Media List API - Fast & Simple Permanent Version
require_once __DIR__ . '/../../config/config.php';

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
header('Content-Type: application/json');
setCorsHeaders();

// Database path
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';

if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database not found', 'media' => []]);
    exit();
}

$db = new SQLite3($dbPath);
$yearFilter = isset($_GET['year']) ? $_GET['year'] : 'all';

// Simple, fast query
$sql = "SELECT Id, FileName, Caption, Year FROM EventMedia WHERE MediaType = 'image'";
if ($yearFilter !== 'all') {
    $sql .= " AND Year = " . intval($yearFilter);
}
$sql .= " ORDER BY Id DESC";

$result = $db->query($sql);

$media = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $media[] = [
        'id' => $row['Id'],
        'file_name' => $row['FileName'],
        'caption' => $row['Caption'] ?: 'Event Moment',
        'year' => $row['Year'],
        'url' => 'http://localhost/umojawetu-platform/storage/uploads/media/images/' . $row['FileName']
    ];
}

$db->close();

echo json_encode(['success' => true, 'media' => $media]);
?>