<?php
// Create Chat Conversation API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$visitorId = sanitizeInput($input['visitor_id'] ?? '', 'string');
$visitorName = sanitizeInput($input['visitor_name'] ?? 'Anonymous', 'string');
$visitorEmail = sanitizeInput($input['visitor_email'] ?? '', 'email');

if (!$visitorId) {
    echo json_encode(['success' => false, 'error' => 'Visitor ID required']);
    exit;
}

$conversationId = getOrCreateConversation($visitorId, $visitorName, $visitorEmail);

echo json_encode([
    'success' => true,
    'conversation_id' => $conversationId,
    'message' => 'Conversation created'
]);

?>
