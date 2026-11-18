<?php
// Get Chat Messages API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$conversationId = intval($_GET['id'] ?? 0);
$limit = intval($_GET['limit'] ?? 50);
$offset = intval($_GET['offset'] ?? 0);

if (!$conversationId) {
    echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
    exit;
}

// Get messages
$messages = getConversationMessages($conversationId, $limit, $offset);

echo json_encode([
    'success' => true,
    'count' => count($messages),
    'messages' => $messages
]);

?>
