<?php
// Media Update API - Updates caption and year
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

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit();
}

$id = intval($input['id']);
$caption = isset($input['caption']) ? $input['caption'] : '';
$year = isset($input['year']) ? intval($input['year']) : null;

// Database path
$dbPath = __DIR__ . '/../../storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}

try {
    $db = new SQLite3($dbPath);
    
    $updates = [];
    $params = [];
    
    if ($caption !== '') {
        $updates[] = "Caption = :caption";
        $params[':caption'] = $caption;
    }
    
    if ($year !== null) {
        $updates[] = "Year = :year";
        $params[':year'] = $year;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'No updates provided']);
        $db->close();
        exit();
    }
    
    $query = "UPDATE EventMedia SET " . implode(', ', $updates) . " WHERE Id = :id";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    
    $stmt->execute();
    
    $db->close();
    
    echo json_encode(['success' => true, 'message' => 'Media updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>