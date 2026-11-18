<?php
// Live Chat Support - Helper Functions
// Location: /config/chat.php

/**
 * Initialize chat database tables
 */
function initializeChatTables() {
    $db = getDbConnection();
    
    try {
        // Create Conversations table
        $db->exec("CREATE TABLE IF NOT EXISTS ChatConversations (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            VisitorId TEXT UNIQUE NOT NULL,
            VisitorName TEXT,
            VisitorEmail TEXT,
            VisitorPhone TEXT,
            Status TEXT DEFAULT 'active', -- active, closed, waiting
            CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            ClosedAt DATETIME,
            Notes TEXT,
            AssignedTo TEXT -- Admin username
        )");
        
        // Create Messages table
        $db->exec("CREATE TABLE IF NOT EXISTS ChatMessages (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            ConversationId INTEGER NOT NULL,
            SenderType TEXT DEFAULT 'visitor', -- visitor, admin
            SenderName TEXT,
            SenderEmail TEXT,
            Message TEXT,
            IsRead INTEGER DEFAULT 0,
            CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ConversationId) REFERENCES ChatConversations(Id) ON DELETE CASCADE
        )");
        
        // Create Chat Visitors table (for tracking)
        $db->exec("CREATE TABLE IF NOT EXISTS ChatVisitors (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            VisitorId TEXT UNIQUE NOT NULL,
            IP TEXT,
            UserAgent TEXT,
            CurrentPage TEXT,
            IsOnline INTEGER DEFAULT 1,
            LastSeen DATETIME DEFAULT CURRENT_TIMESTAMP,
            Location TEXT,
            Device TEXT -- mobile, tablet, desktop
        )");
        
        return ['success' => true, 'message' => 'Chat tables initialized'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Create or get a conversation
 */
function getOrCreateConversation($visitorId, $visitorName = '', $visitorEmail = '') {
    $db = getDbConnection();
    
    // Check if conversation exists
    $stmt = $db->prepare("SELECT Id FROM ChatConversations WHERE VisitorId = :id");
    $stmt->bindValue(':id', $visitorId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $existing = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($existing) {
        // Update last activity
        $db->exec("UPDATE ChatConversations SET UpdatedAt = datetime('now') WHERE Id = {$existing['Id']}");
        return $existing['Id'];
    }
    
    // Create new conversation
    $stmt = $db->prepare("
        INSERT INTO ChatConversations (VisitorId, VisitorName, VisitorEmail, Status)
        VALUES (:id, :name, :email, 'waiting')
    ");
    $stmt->bindValue(':id', $visitorId, SQLITE3_TEXT);
    $stmt->bindValue(':name', $visitorName, SQLITE3_TEXT);
    $stmt->bindValue(':email', $visitorEmail, SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

/**
 * Send a chat message
 */
function sendChatMessage($conversationId, $message, $senderType = 'visitor', $senderName = '', $senderEmail = '') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO ChatMessages (ConversationId, SenderType, SenderName, SenderEmail, Message)
        VALUES (:cid, :type, :name, :email, :msg)
    ");
    $stmt->bindValue(':cid', $conversationId, SQLITE3_INTEGER);
    $stmt->bindValue(':type', $senderType, SQLITE3_TEXT);
    $stmt->bindValue(':name', $senderName, SQLITE3_TEXT);
    $stmt->bindValue(':email', $senderEmail, SQLITE3_TEXT);
    $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    
    if ($result) {
        // Update conversation last activity
        $db->exec("UPDATE ChatConversations SET UpdatedAt = datetime('now') WHERE Id = $conversationId");
        return ['success' => true, 'id' => $db->lastInsertRowID()];
    }
    
    return ['success' => false, 'error' => 'Failed to save message'];
}

/**
 * Get conversation messages
 */
function getConversationMessages($conversationId, $limit = 50, $offset = 0) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM ChatMessages 
        WHERE ConversationId = :cid
        ORDER BY CreatedAt DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':cid', $conversationId, SQLITE3_INTEGER);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $messages = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = $row;
    }
    
    return array_reverse($messages); // Return in chronological order
}

/**
 * Get all active conversations for admin
 */
function getActiveConversations($status = 'all') {
    $db = getDbConnection();
    
    $whereClause = '';
    if ($status !== 'all') {
        $whereClause = "WHERE Status = '$status'";
    }
    
    $query = "
        SELECT 
            c.*,
            COUNT(m.Id) as UnreadCount
        FROM ChatConversations c
        LEFT JOIN ChatMessages m ON c.Id = m.ConversationId AND m.IsRead = 0 AND m.SenderType = 'visitor'
        $whereClause
        GROUP BY c.Id
        ORDER BY c.UpdatedAt DESC
    ";
    
    $result = $db->query($query);
    $conversations = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $conversations[] = $row;
    }
    
    return $conversations;
}

/**
 * Get conversation details
 */
function getConversationDetails($conversationId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM ChatConversations WHERE Id = :id");
    $stmt->bindValue(':id', $conversationId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    return $result->fetchArray(SQLITE3_ASSOC);
}

/**
 * Update conversation status
 */
function updateConversationStatus($conversationId, $status, $notes = '') {
    $db = getDbConnection();
    
    $closedAt = '';
    if ($status === 'closed') {
        $closedAt = ", ClosedAt = datetime('now')";
    }
    
    $stmt = $db->prepare("
        UPDATE ChatConversations 
        SET Status = :status, Notes = :notes, UpdatedAt = datetime('now') $closedAt
        WHERE Id = :id
    ");
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
    $stmt->bindValue(':id', $conversationId, SQLITE3_INTEGER);
    
    return $stmt->execute() ? ['success' => true] : ['success' => false];
}

/**
 * Mark messages as read
 */
function markMessagesAsRead($conversationId, $senderType = 'visitor') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        UPDATE ChatMessages 
        SET IsRead = 1
        WHERE ConversationId = :cid AND SenderType = :type
    ");
    $stmt->bindValue(':cid', $conversationId, SQLITE3_INTEGER);
    $stmt->bindValue(':type', $senderType, SQLITE3_TEXT);
    
    return $stmt->execute();
}

/**
 * Get unread count for admin
 */
function getUnreadChatCount() {
    $db = getDbConnection();
    
    $result = $db->querySingle("
        SELECT COUNT(*) FROM ChatMessages 
        WHERE IsRead = 0 AND SenderType = 'visitor'
    ");
    
    return $result ?? 0;
}

/**
 * Update visitor status
 */
function updateVisitorStatus($visitorId, $isOnline = true, $currentPage = '') {
    $db = getDbConnection();
    
    // Check if visitor record exists
    $stmt = $db->prepare("SELECT Id FROM ChatVisitors WHERE VisitorId = :id");
    $stmt->bindValue(':id', $visitorId, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    if ($result->fetchArray(SQLITE3_ASSOC)) {
        // Update existing
        $stmt = $db->prepare("
            UPDATE ChatVisitors 
            SET IsOnline = :online, LastSeen = datetime('now'), CurrentPage = :page
            WHERE VisitorId = :id
        ");
    } else {
        // Create new
        $stmt = $db->prepare("
            INSERT INTO ChatVisitors (VisitorId, IsOnline, CurrentPage, UserAgent)
            VALUES (:id, :online, :page, :ua)
        ");
        $stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '', SQLITE3_TEXT);
    }
    
    $stmt->bindValue(':id', $visitorId, SQLITE3_TEXT);
    $stmt->bindValue(':online', $isOnline ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':page', $currentPage, SQLITE3_TEXT);
    
    return $stmt->execute();
}

/**
 * Get online visitors count
 */
function getOnlineVisitorsCount() {
    $db = getDbConnection();
    
    $result = $db->querySingle("
        SELECT COUNT(*) FROM ChatVisitors 
        WHERE IsOnline = 1 AND LastSeen > datetime('now', '-5 minutes')
    ");
    
    return $result ?? 0;
}

?>
