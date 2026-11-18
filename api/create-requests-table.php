<?php

// --- AUTH ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Create Requests table
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

// Check if table exists
$check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='Requests'");
if (!$check->fetchArray()) {
    // Create the table
    $db->exec("
        CREATE TABLE Requests (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            RequestReference TEXT UNIQUE,
            RequestType TEXT,
            RequesterType TEXT,
            Status TEXT DEFAULT 'pending',
            Priority INTEGER DEFAULT 1,
            FullName TEXT,
            Email TEXT,
            Phone TEXT,
            MembershipId TEXT,
            AmountRequested REAL,
            AmountJustification TEXT,
            RequestData TEXT,
            SubmittedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            LastUpdated DATETIME,
            ResolvedAt DATETIME,
            ResolutionNotes TEXT,
            AssignedTo TEXT,
            IsDeleted INTEGER DEFAULT 0
        )
    ");
    echo "âœ… Requests table created successfully!";
    
    // Add a sample request for testing
    $db->exec("
        INSERT INTO Requests (RequestReference, RequestType, FullName, Email, AmountRequested, Status)
        VALUES ('REQ-TEST-001', 'Financial Aid', 'Test User', 'test@example.com', 100.00, 'pending')
    ");
    echo "<br>âœ… Sample request added.";
} else {
    echo "âœ… Requests table already exists.";
}

$db->close();
echo "<br><br><a href='/umojawetu-platform/admin/requests.php'>Go to Financial Requests â†’</a>";
?>