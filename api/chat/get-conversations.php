<?php
// Get Active Conversations API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();
requireAdminLogin();

$status = sanitizeInput($_GET['status'] ?? 'all', 'string');

// Validate status
$validStatuses = ['all', 'active', 'waiting', 'closed'];
if (!in_array($status, $validStatuses)) {
    $status = 'all';
}

$conversations = getActiveConversations($status);
$unreadCount = getUnreadChatCount();

echo json_encode([
    'success' => true,
    'count' => count($conversations),
    'unread' => $unreadCount,
    'conversations' => $conversations
]);

?>
