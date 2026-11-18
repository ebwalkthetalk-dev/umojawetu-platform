<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Create booking_attendees table
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';

$db = new SQLite3($dbPath);

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

echo "âœ… booking_attendees table created successfully!";

$db->close();
?>