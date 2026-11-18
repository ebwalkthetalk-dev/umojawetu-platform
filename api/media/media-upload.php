<?php
// Media Upload API - Handles file uploads
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['file'];
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$eventId = isset($_POST['eventId']) && $_POST['eventId'] !== '' ? intval($_POST['eventId']) : null;

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, MP4, WebM']);
    exit();
}

// Validate file size (50MB max)
if ($file['size'] > 50 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File too large. Max 50MB']);
    exit();
}

// Generate unique file name
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = uniqid() . '_' . time() . '.' . $ext;

// Determine upload directory
$uploadDir = __DIR__ . '/../../storage/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$destination = $uploadDir . $newFileName;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit();
}

// Determine media type
$mediaType = strpos($file['type'], 'image/') === 0 ? 'image' : 'video';

// Save to database
$dbPath = __DIR__ . '/../../storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../umoja-events-database.db';
}

try {
    $db = new SQLite3($dbPath);
    
    // Check if EventMedia table exists, create if not
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='EventMedia'");
    if (!$tableCheck->fetchArray()) {
        $db->exec("
            CREATE TABLE EventMedia (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                EventId INTEGER,
                MediaType TEXT,
                FileName TEXT,
                OriginalName TEXT,
                FileSize INTEGER,
                MimeType TEXT,
                StoragePath TEXT,
                ThumbnailPath TEXT,
                Caption TEXT,
                Year INTEGER,
                DisplayOrder INTEGER DEFAULT 0,
                IsActive INTEGER DEFAULT 1,
                UploadedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
                UploadedBy TEXT
            )
        ");
    }
    
    // Insert record
    $stmt = $db->prepare("
        INSERT INTO EventMedia (EventId, MediaType, FileName, OriginalName, FileSize, MimeType, Caption, Year, UploadedBy)
        VALUES (:eventId, :mediaType, :fileName, :originalName, :fileSize, :mimeType, :caption, :year, :uploadedBy)
    ");
    
    $stmt->bindValue(':eventId', $eventId, $eventId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':mediaType', $mediaType, SQLITE3_TEXT);
    $stmt->bindValue(':fileName', $newFileName, SQLITE3_TEXT);
    $stmt->bindValue(':originalName', $file['name'], SQLITE3_TEXT);
    $stmt->bindValue(':fileSize', $file['size'], SQLITE3_INTEGER);
    $stmt->bindValue(':mimeType', $file['type'], SQLITE3_TEXT);
    $stmt->bindValue(':caption', pathinfo($file['name'], PATHINFO_FILENAME), SQLITE3_TEXT);
    $stmt->bindValue(':year', $year, SQLITE3_INTEGER);
    $stmt->bindValue(':uploadedBy', session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'admin', SQLITE3_TEXT);
    
    $stmt->execute();
    $newId = $db->lastInsertRowID();
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'file_name' => $newFileName,
        'message' => 'File uploaded successfully'
    ]);
    
} catch (Exception $e) {
    // Delete the uploaded file if database insert fails
    unlink($destination);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>