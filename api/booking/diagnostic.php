<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
header('Content-Type: application/json');

$results = [];

// Test 1: Database file exists
$dbPaths = [
    'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db',
    __DIR__ . '/../umoja-events-database.db',
    __DIR__ . '/../../storage/umoja-events-database.db'
];

$foundPath = null;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        $foundPath = $path;
        break;
    }
}

$results['database_file_exists'] = $foundPath ? true : false;
$results['database_path'] = $foundPath;

// Test 2: Can connect to database
if ($foundPath) {
    try {
        $db = new SQLite3($foundPath);
        $results['database_connection'] = 'success';
        
        // Test 3: Check Bookings table
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tableNames = [];
        while ($row = $tables->fetchArray(SQLITE3_ASSOC)) {
            $tableNames[] = $row['name'];
        }
        $results['tables'] = $tableNames;
        
        $db->close();
    } catch (Exception $e) {
        $results['database_connection'] = 'failed: ' . $e->getMessage();
    }
}

// Test 4: Check if API is reachable
$results['api_self_url'] = 'http://localhost/umojawetu-platform/api/booking/bookings-wizard.php';

echo json_encode($results, JSON_PRETTY_PRINT);
?>