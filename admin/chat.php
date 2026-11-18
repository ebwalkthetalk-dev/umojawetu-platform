<?php
// Admin Live Chat Interface
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
initializeChatTables();

$conversationId = intval($_GET['id'] ?? 0);
$filterStatus = $_GET['status'] ?? 'all';
$action = $_GET['action'] ?? '';

// Handle close conversation
if ($action === 'close' && $conversationId) {
    $notes = $_POST['notes'] ?? '';
    updateConversationStatus($conversationId, 'closed', $notes);
    header('Location: chat.php');
    exit;
}

// Get current conversation if selected
$currentConversation = $conversationId ? getConversationDetails($conversationId) : null;
$messages = $conversationId ? getConversationMessages($conversationId, 100) : [];

// Mark messages as read
if ($conversationId) {
    markMessagesAsRead($conversationId, 'visitor');
}

// Get all conversations
$conversations = getActiveConversations($filterStatus);
$unreadCount = getUnreadChatCount();
$onlineVisitors = getOnlineVisitorsCount();

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat Support - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-message { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message-box { max-height: 400px; overflow-y: auto; }
        .visitor-badge { background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
        .admin-badge { background: #3b82f6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400">Umojawetu Platform</p>
            </div>
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <a href="dashboard-unified.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Dashboard</a>
                <a href="event-builder.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">✨ Event Builder</a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📅 Events</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👥 Attendees</a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💰 Requests</a>
                <a href="pledges.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🤝 Pledges</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="chat.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600 relative">💬 Live Chat <?php if ($unreadCount > 0): ?><span class="absolute top-2 right-2 bg-yellow-400 text-black text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $unreadCount; ?></span><?php endif; ?></a>
                <a href="trash.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🗑️ Trash</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Conversations List -->
            <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-bold mb-2">💬 Conversations</h2>
                    <div class="flex gap-2 mb-3 text-sm">
                        <span class="text-green-600">🟢 <?php echo $onlineVisitors; ?> Online</span>
                        <span class="text-blue-600">📬 <?php echo count($conversations); ?> Total</span>
                    </div>
                    <div class="flex gap-1 flex-wrap">
                        <a href="chat.php" class="px-3 py-1 rounded-full text-sm <?php echo $filterStatus === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">All</a>
                        <a href="?status=waiting" class="px-3 py-1 rounded-full text-sm <?php echo $filterStatus === 'waiting' ? 'bg-yellow-500 text-white' : 'bg-gray-200'; ?>">Waiting</a>
                        <a href="?status=active" class="px-3 py-1 rounded-full text-sm <?php echo $filterStatus === 'active' ? 'bg-green-600 text-white' : 'bg-gray-200'; ?>">Active</a>
                        <a href="?status=closed" class="px-3 py-1 rounded-full text-sm <?php echo $filterStatus === 'closed' ? 'bg-gray-600 text-white' : 'bg-gray-200'; ?>">Closed</a>
                    </div>
                </div>

                <!-- Conversations -->
                <div class="flex-1 overflow-y-auto">
                    <?php if (empty($conversations)): ?>
                        <div class="p-6 text-center text-gray-500">
                            <p>No conversations yet</p>
                            <p class="text-xs mt-2">Chat widget is inactive</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?id=<?php echo $conv['Id']; ?>" class="block p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer <?php echo $conversationId === $conv['Id'] ? 'bg-blue-50 border-l-4 border-l-blue-600' : ''; ?>">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-semibold text-sm"><?php echo htmlspecialchars($conv['VisitorName'] ?: 'Anonymous'); ?></span>
                                    <span class="<?php echo $conv['Status'] === 'active' ? 'bg-green-100 text-green-700' : ($conv['Status'] === 'waiting' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'); ?> text-xs px-2 py-1 rounded"><?php echo ucfirst($conv['Status']); ?></span>
                                </div>
                                <p class="text-xs text-gray-600 mb-1"><?php echo htmlspecialchars($conv['VisitorEmail'] ?: 'No email'); ?></p>
                                <p class="text-xs text-gray-500">Last activity: <?php echo date('H:i', strtotime($conv['UpdatedAt'])); ?></p>
                                <?php if ($conv['UnreadCount'] > 0): ?>
                                    <div class="mt-2">
                                        <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $conv['UnreadCount']; ?> unread</span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col bg-white">
                <?php if ($conversationId && $currentConversation): ?>
                    <!-- Chat Header -->
                    <div class="p-4 border-b border-gray-200 bg-blue-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-lg font-bold"><?php echo htmlspecialchars($currentConversation['VisitorName'] ?: 'Anonymous'); ?></h1>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($currentConversation['VisitorEmail'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Started: <?php echo date('M d, Y H:i', strtotime($currentConversation['CreatedAt'])); ?>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <span class="<?php echo $currentConversation['Status'] === 'active' ? 'bg-green-100 text-green-700' : ($currentConversation['Status'] === 'waiting' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'); ?> px-3 py-1 rounded text-sm font-semibold"><?php echo ucfirst($currentConversation['Status']); ?></span>
                                
                                <?php if ($currentConversation['Status'] !== 'closed'): ?>
                                    <button onclick="document.getElementById('closeForm').classList.remove('hidden')" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Close Chat</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-4 message-box bg-gray-50 space-y-3" id="messagesContainer">
                        <?php foreach ($messages as $msg): ?>
                            <div class="chat-message flex <?php echo $msg['SenderType'] === 'admin' ? 'justify-end' : 'justify-start'; ?>">
                                <div class="max-w-xs">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="<?php echo $msg['SenderType'] === 'admin' ? 'admin-badge' : 'visitor-badge'; ?>">
                                            <?php echo $msg['SenderType'] === 'admin' ? '👨‍💼 ' : '👤 '; echo htmlspecialchars($msg['SenderName'] ?: ucfirst($msg['SenderType'])); ?>
                                        </span>
                                        <span class="text-xs text-gray-500"><?php echo date('H:i', strtotime($msg['CreatedAt'])); ?></span>
                                    </div>
                                    <div class="<?php echo $msg['SenderType'] === 'admin' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300'; ?> p-3 rounded-lg">
                                        <?php echo nl2br(htmlspecialchars($msg['Message'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 border-t border-gray-200 bg-white">
                        <form id="messageForm">
                            <input type="hidden" id="conversationId" value="<?php echo $conversationId; ?>">
                            <div class="flex gap-2">
                                <input type="text" id="messageInput" placeholder="Type your message..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Send</button>
                            </div>
                        </form>
                    </div>

                    <!-- Close Chat Form (Hidden) -->
                    <div id="closeForm" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white p-6 rounded-lg max-w-md">
                            <h3 class="text-lg font-bold mb-4">Close Conversation</h3>
                            <form method="POST" action="?action=close&id=<?php echo $conversationId; ?>">
                                <textarea name="notes" placeholder="Add closing notes (optional)..." class="w-full border border-gray-300 rounded-lg p-3 mb-4" rows="4"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded hover:bg-red-700">Close Chat</button>
                                    <button type="button" onclick="document.getElementById('closeForm').classList.add('hidden')" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded hover:bg-gray-400">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- No Conversation Selected -->
                    <div class="flex-1 flex items-center justify-center text-gray-500">
                        <div class="text-center">
                            <div class="text-5xl mb-4">💬</div>
                            <p class="text-lg font-semibold">No conversation selected</p>
                            <p class="text-sm">Select a conversation from the list to start chatting</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to latest message
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        scrollToBottom();

        // Send message
        document.getElementById('messageForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const conversationId = document.getElementById('conversationId').value;
            const message = document.getElementById('messageInput').value.trim();
            
            if (!message) return;
            
            try {
                const response = await fetch('/api/chat/send-message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        message: message,
                        sender_type: 'admin',
                        sender_name: '<?php echo htmlspecialchars($adminUsername); ?>'
                    })
                });
                
                if (response.ok) {
                    document.getElementById('messageInput').value = '';
                    // Reload conversation
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        });

        // Auto-refresh messages every 3 seconds
        setInterval(() => {
            const conversationId = document.getElementById('conversationId')?.value;
            if (conversationId) {
                fetch(`/api/chat/get-messages.php?id=${conversationId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.messages.length > document.querySelectorAll('.chat-message').length) {
                            window.location.reload();
                        }
                    });
            }
        }, 3000);
    </script>
</body>
</html>

<?php $db->close(); ?>
