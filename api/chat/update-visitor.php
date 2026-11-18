<?php
// Update Visitor Status API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$visitorId = sanitizeInput($input['visitor_id'] ?? '', 'string');
$isOnline = intval($input['is_online'] ?? 1);
$currentPage = sanitizeInput($input['current_page'] ?? '', 'string');

if (!$visitorId) {
    echo json_encode(['success' => false, 'error' => 'Visitor ID required']);
    exit;
}

$result = updateVisitorStatus($visitorId, $isOnline === 1, $currentPage);

echo json_encode([
    'success' => true,
    'message' => 'Visitor status updated'
]);

?>
