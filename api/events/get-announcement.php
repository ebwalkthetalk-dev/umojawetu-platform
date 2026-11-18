<?php
// Get single announcement for editing
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing announcement ID']);
    exit;
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM EventAnnouncements WHERE Id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $announcement = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$announcement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Announcement not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $announcement
    ]);
    
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
