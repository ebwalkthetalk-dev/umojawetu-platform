<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Complete Pledge System Setup - Fixed Version
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

echo "<h1>ðŸ” Complete Pledge System Setup</h1>";

// 1. Pledges Table
$db->exec("
    CREATE TABLE IF NOT EXISTS pledges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pledge_reference TEXT UNIQUE NOT NULL,
        pledger_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        is_member INTEGER DEFAULT 0,
        membership_id TEXT,
        amount_pledged REAL NOT NULL,
        amount_paid REAL DEFAULT 0,
        payment_status TEXT DEFAULT 'pending',
        payment_method TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "<p style='color:green'>âœ… Created pledges table</p>";

// 2. Pledge Payments Table
$db->exec("
    CREATE TABLE IF NOT EXISTS pledge_payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pledge_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        payment_method TEXT,
        transaction_id TEXT,
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "<p style='color:green'>âœ… Created pledge_payments table</p>";

// 3. Pledge Campaigns Table
$db->exec("
    CREATE TABLE IF NOT EXISTS pledge_campaigns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        target_amount REAL DEFAULT 10000,
        current_amount REAL DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "<p style='color:green'>âœ… Created pledge_campaigns table</p>";

// 4. System Settings Table (CRITICAL - This was missing!)
$db->exec("
    CREATE TABLE IF NOT EXISTS system_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME
    )
");
echo "<p style='color:green'>âœ… Created system_settings table</p>";

// 5. Insert default settings
$db->exec("INSERT OR IGNORE INTO system_settings (setting_key, setting_value) VALUES ('pledge_wall_enabled', '1')");
echo "<p style='color:green'>âœ… Pledge wall enabled by default</p>";

// 6. Insert default campaign
$db->exec("
    INSERT OR IGNORE INTO pledge_campaigns (id, title, description, target_amount, is_active)
    VALUES (1, 'Community Support Fund', 'Supporting our community initiatives', 10000, 1)
");
echo "<p style='color:green'>âœ… Default campaign created</p>";

// 7. Create a test pledge for demonstration
$check = $db->query("SELECT COUNT(*) as count FROM pledges");
$row = $check->fetchArray(SQLITE3_ASSOC);

if ($row['count'] == 0) {
    $testRef = 'TEST-' . date('Ymd') . '-001';
    $db->exec("
        INSERT INTO pledges (pledge_reference, pledger_name, email, amount_pledged, amount_paid, payment_status)
        VALUES ('$testRef', 'Community Supporter', 'supporter@example.com', 100, 100, 'paid')
    ");
    echo "<p style='color:green'>âœ… Created test pledge: Â£100 from Community Supporter</p>";
}

echo "<h2>âœ… Setup Complete!</h2>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Added missing system_settings table</li>";
echo "<li>Added default campaign</li>";
echo "<li>Created test pledge</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>ðŸ”— Test Links:</h3>";
echo "<ul>";
echo "<li><a href='/public/pledge-wall.php' target='_blank'>ðŸ“Š View Pledge Wall</a></li>";
echo "<li><a href='/admin/pledges.php' target='_blank'>ðŸ‘‘ Admin Pledges</a></li>";
echo "<li><a href='/admin/pledge-wall-settings.php' target='_blank'>âš™ï¸ Pledge Wall Settings</a></li>";
echo "<li><a href='/api/pledges/get-pledges-public.php' target='_blank'>ðŸ“¡ Test API</a></li>";
echo "</ul>";

$db->close();
?>