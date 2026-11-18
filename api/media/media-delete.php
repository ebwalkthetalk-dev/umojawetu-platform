<?php
// Media Delete API - Deletes media file and database record
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Media ID required']);
    exit();
}

$id = intval($input['id']);

// Database path
$dbPath = __DIR__ . '/../../storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}

try {
    $db = new SQLite3($dbPath);
    
    // Get file name before deleting
    $stmt = $db->prepare("SELECT FileName FROM EventMedia WHERE Id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Media not found']);
        $db->close();
        exit();
    }
    
    $fileName = $row['FileName'];
    
    // Delete from database (soft delete or hard delete)
    $delete = $db->prepare("DELETE FROM EventMedia WHERE Id = :id");
    $delete->bindValue(':id', $id, SQLITE3_INTEGER);
    $delete->execute();
    
    $db->close();
    
    // Delete physical file
    $filePaths = [
        __DIR__ . '/../../storage/uploads/' . $fileName,
        __DIR__ . '/../uploads/' . $fileName
    ];
    
    foreach ($filePaths as $filePath) {
        if (file_exists($filePath)) {
            unlink($filePath);
            break;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Media deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>