<?php

// --- AUTH ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Add promotion columns to Events table
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

echo "<h1>Updating Events Table with Promotions</h1>";

// Check current columns
$columns = $db->query("PRAGMA table_info(Events)");
$existingColumns = [];
while ($col = $columns->fetchArray(SQLITE3_ASSOC)) {
    $existingColumns[] = $col['name'];
    echo "<p>Existing column: {$col['name']}</p>";
}

// Add promotion columns if missing
$promotionColumns = [
    'early_bird_enabled' => 'INTEGER DEFAULT 0',
    'early_bird_deadline' => 'TEXT',
    'early_bird_discount' => 'REAL DEFAULT 0',
    'group_deal_enabled' => 'INTEGER DEFAULT 0',
    'group_deal_buy' => 'INTEGER DEFAULT 2',
    'group_deal_get' => 'INTEGER DEFAULT 1',
    'member_discount' => 'REAL DEFAULT 0',
    'promo_codes' => 'TEXT'  // JSON array of promo codes
];

foreach ($promotionColumns as $colName => $colDef) {
    if (!in_array($colName, $existingColumns)) {
        $db->exec("ALTER TABLE Events ADD COLUMN $colName $colDef");
        echo "<p style='color:green'>âœ… Added column: $colName</p>";
    } else {
        echo "<p style='color:blue'>âµ Column already exists: $colName</p>";
    }
}

echo "<h2>Update Complete!</h2>";
echo "<p><a href='/admin/events.php'>Go to Admin Events</a></p>";

$db->close();
?>