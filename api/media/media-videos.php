<?php
// Video List API - Returns videos from EventMedia
require_once __DIR__ . '/../../config/config.php';

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
header('Content-Type: application/json');
setCorsHeaders();

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';

if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database not found', 'videos' => []]);
    exit();
}

$db = new SQLite3($dbPath);
$yearFilter = isset($_GET['year']) ? $_GET['year'] : 'all';

// Query only videos
$sql = "SELECT Id, FileName, Caption, Year FROM EventMedia WHERE MediaType = 'video'";
if ($yearFilter !== 'all') {
    $sql .= " AND Year = " . intval($yearFilter);
}
$sql .= " ORDER BY Id DESC";

$result = $db->query($sql);

$videos = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $videos[] = [
        'id' => $row['Id'],
        'file_name' => $row['FileName'],
        'caption' => $row['Caption'] ?: 'Event Video',
        'year' => $row['Year'],
        'url' => 'http://localhost/umojawetu-platform/storage/uploads/media/videos/' . $row['FileName']
    ];
}

$db->close();

echo json_encode(['success' => true, 'videos' => $videos]);
?>