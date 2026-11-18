<?php
// Events API - Standalone Version (No config.php required)
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');
setCorsHeaders();

// Find the SQLite database
$dbPath = __DIR__ . '/../storage/umoja-events-database.db';

if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}

if (!file_exists($dbPath)) {
    echo json_encode(['error' => 'Database not found at: ' . $dbPath, 'events' => []]);
    exit();
}

try {
    $db = new SQLite3($dbPath);
    
    // Check if Events table exists
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='Events'");
    if (!$tableCheck->fetchArray()) {
        echo json_encode(['error' => 'Events table not found', 'events' => []]);
        $db->close();
        exit();
    }
    
    // Get all events
    $result = $db->query("SELECT * FROM Events ORDER BY Date ASC");
    $events = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $events[] = [
            'id' => $row['Id'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'date' => $row['Date'],
            'priceAdult' => floatval($row['PriceAdult'] ?? 0),
            'priceChild' => floatval($row['PriceChild'] ?? 0),
            'priceVIP' => floatval($row['PriceVIP'] ?? 0),
            'location' => $row['Location'] ?? '',
            'address' => $row['Location'] ?? '',
            'imageUrl' => $row['ImageUrl'] ?? '',
            'video_url' => $row['video_url'] ?? '',
            'video_type' => $row['video_type'] ?? 'none',
            'isActive' => ($row['isActive'] == 1 || $row['isActive'] === true),
            'visibility' => $row['visibility'] ?? 'public',
            'firstChildFree' => ($row['FirstChildFree'] == 1 || $row['FirstChildFree'] === true)
        ];
    }
    
    $db->close();
    echo json_encode($events, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'events' => []]);
}
?>