<?php
// Media Reorder API - Updates display order
require_once __DIR__ . '/../../config/config.php';

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
header('Content-Type: application/json');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order']) || !is_array($input['order'])) {
    echo json_encode(['success' => false, 'error' => 'Order array required']);
    exit();
}

$order = $input['order'];

// Database path
$dbPath = __DIR__ . '/../../storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}

try {
    $db = new SQLite3($dbPath);
    
    // Update display order for each item
    foreach ($order as $index => $id) {
        $stmt = $db->prepare("UPDATE EventMedia SET DisplayOrder = :order WHERE Id = :id");
        $stmt->bindValue(':order', $index, SQLITE3_INTEGER);
        $stmt->bindValue(':id', intval($id), SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    $db->close();
    
    echo json_encode(['success' => true, 'message' => 'Order saved successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>