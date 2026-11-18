<?php

// --- AUTH ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Create booking_attendees table for storing individual attendee names
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';

// Check if database exists
if (!file_exists($dbPath)) {
    die("Database not found at: $dbPath");
}

$db = new SQLite3($dbPath);

// Create the booking_attendees table
$db->exec("
    CREATE TABLE IF NOT EXISTS booking_attendees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL,
        attendee_name TEXT NOT NULL,
        attendee_number INTEGER,
        ticket_type TEXT,
        created_at DATETIME,
        FOREIGN KEY (booking_id) REFERENCES Bookings(Id) ON DELETE CASCADE
    )
");

echo "<h1>âœ… booking_attendees table created successfully!</h1>";
echo "<p>Table structure:</p>";
echo "<ul>";
echo "<li><strong>id</strong> - Primary key (auto-increment)</li>";
echo "<li><strong>booking_id</strong> - Links to Bookings table</li>";
echo "<li><strong>attendee_name</strong> - Name of the attendee</li>";
echo "<li><strong>attendee_number</strong> - Order of attendee (1, 2, 3...)</li>";
echo "<li><strong>ticket_type</strong> - adult, child, or vip</li>";
echo "<li><strong>created_at</strong> - Timestamp when record was created</li>";
echo "</ul>";

// Verify table exists
$check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='booking_attendees'");
if ($check->fetchArray()) {
    echo "<p style='color:green'>âœ… Table verified in database</p>";
} else {
    echo "<p style='color:red'>âŒ Table creation failed</p>";
}

$db->close();

echo "<p><a href='/public/booking.php'>Go to Booking Page â†’</a></p>";
?>