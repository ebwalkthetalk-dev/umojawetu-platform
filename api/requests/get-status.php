<?php
// Get Request Status API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$reference = isset($_GET['ref']) ? $_GET['ref'] : '';

if (empty($reference)) {
    echo json_encode(['success' => false, 'error' => 'No reference provided']);
    exit();
}

$db = getDbConnection();

$stmt = $db->prepare("SELECT * FROM Requests WHERE RequestReference = :ref");
$stmt->bindValue(':ref', $reference, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

$db->close();

if ($row) {
    echo json_encode([
        'success' => true,
        'request' => [
            'RequestReference' => $row['RequestReference'],
            'RequestType' => $row['RequestType'],
            'Status' => $row['Status'],
            'SubmittedAt' => $row['SubmittedAt'],
            'ResolvedAt' => $row['ResolvedAt']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
}
?>