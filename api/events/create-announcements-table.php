<?php
// Create EventAnnouncements table for event updates & announcements
require_once __DIR__ . '/../../config/config.php';

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---

try {
    $db = getDbConnection();
    
    // Create EventAnnouncements table
    $db->exec("
        CREATE TABLE IF NOT EXISTS EventAnnouncements (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            EventId INTEGER NOT NULL,
            Title TEXT NOT NULL,
            Content TEXT NOT NULL,
            AnnouncementType TEXT DEFAULT 'update',
            Priority INTEGER DEFAULT 3,
            Status TEXT DEFAULT 'draft',
            PublishedAt DATETIME,
            CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (EventId) REFERENCES Events(Id) ON DELETE CASCADE
        )
    ");
    
    // Create index for faster queries
    $db->exec("CREATE INDEX IF NOT EXISTS idx_event_announcements ON EventAnnouncements(EventId, Status, PublishedAt DESC)");
    
    echo json_encode([
        'success' => true,
        'message' => 'âœ… EventAnnouncements table created successfully',
        'table_structure' => [
            'Id' => 'Primary key (auto-increment)',
            'EventId' => 'Foreign key to Events',
            'Title' => 'Announcement title',
            'Content' => 'Announcement content (rich text)',
            'AnnouncementType' => 'update | reminder | alert',
            'Priority' => '1-5 (5 = highest)',
            'Status' => 'draft | published',
            'PublishedAt' => 'When announcement was published',
            'CreatedAt' => 'Creation timestamp',
            'UpdatedAt' => 'Last modified timestamp'
        ]
    ], JSON_PRETTY_PRINT);
    
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
