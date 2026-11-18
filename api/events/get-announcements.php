<?php
// Get announcements for a specific event (public API)
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$eventId = intval($_GET['event_id'] ?? $_GET['eventId'] ?? 0);
$limit = intval($_GET['limit'] ?? 5);

if (!$eventId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing event ID'
    ]);
    exit;
}

try {
    $db = getDbConnection();
    
    // Verify event exists
    $event = $db->querySingle("SELECT Id FROM Events WHERE Id = $eventId", true);
    if (!$event) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Event not found'
        ]);
        exit;
    }
    
    // Get published announcements, ordered by priority and date
    $stmt = $db->prepare("
        SELECT Id, EventId, Title, Content, AnnouncementType, Priority, Status, PublishedAt, CreatedAt
        FROM EventAnnouncements
        WHERE EventId = :event_id AND Status = 'published'
        ORDER BY Priority DESC, PublishedAt DESC
        LIMIT :limit
    ");
    
    $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $announcements = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $announcements[] = [
            'id' => $row['Id'],
            'eventId' => $row['EventId'],
            'title' => $row['Title'],
            'content' => $row['Content'],
            'type' => $row['AnnouncementType'],
            'priority' => intval($row['Priority']),
            'status' => $row['Status'],
            'publishedAt' => $row['PublishedAt'],
            'publishedDate' => $row['PublishedAt'] ? date('M d, Y g:i A', strtotime($row['PublishedAt'])) : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'eventId' => $eventId,
        'count' => count($announcements),
        'announcements' => $announcements
    ]);
    
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
