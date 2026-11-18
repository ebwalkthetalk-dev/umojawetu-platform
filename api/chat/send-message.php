<?php
// Send Chat Message API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$conversationId = intval($input['conversation_id'] ?? 0);
$message = sanitizeInput($input['message'] ?? '', 'string');
$senderType = sanitizeInput($input['sender_type'] ?? 'visitor', 'string');
$senderName = sanitizeInput($input['sender_name'] ?? '', 'string');
$senderEmail = sanitizeInput($input['sender_email'] ?? '', 'string');

// Validate input
if (!$conversationId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate sender type
if (!in_array($senderType, ['visitor', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid sender type']);
    exit;
}

// Only allow admin to send if logged in
if ($senderType === 'admin' && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$result = sendChatMessage($conversationId, $message, $senderType, $senderName, $senderEmail);

if ($result['success']) {
    // Update conversation status if admin is sending
    if ($senderType === 'admin') {
        updateConversationStatus($conversationId, 'active');
    }
    
    echo json_encode([
        'success' => true,
        'id' => $result['id'],
        'message' => 'Message sent successfully'
    ]);
} else {
    echo json_encode($result);
}

?>
