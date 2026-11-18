<?php

// --- AUTH ---
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Pledge System Diagnostic - Tests all components
echo "<h1>ðŸ” Pledge System Diagnostic</h1>";
echo "<hr>";

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$baseUrl = 'http://localhost/umojawetu-platform';

// 1. Check Database
echo "<h2>ðŸ“ 1. Database Check</h2>";
if (file_exists($dbPath)) {
    echo "<p style='color:green'>âœ… Database exists at: $dbPath</p>";
} else {
    echo "<p style='color:red'>âŒ Database not found at: $dbPath</p>";
}

$db = new SQLite3($dbPath);

// Check tables
$tables = ['pledges', 'system_settings'];
foreach ($tables as $table) {
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    if ($result->fetchArray()) {
        echo "<p style='color:green'>âœ… Table '$table' exists</p>";
    } else {
        echo "<p style='color:red'>âŒ Table '$table' missing</p>";
    }
}

// 2. Check Pledge Data
echo "<h2>ðŸ“Š 2. Pledge Data</h2>";
$count = $db->querySingle("SELECT COUNT(*) FROM pledges");
echo "<p>Total pledges: <strong>$count</strong></p>";

if ($count > 0) {
    $result = $db->query("SELECT pledge_reference, pledger_name, amount_pledged, payment_status FROM pledges LIMIT 5");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Reference</th><th>Name</th><th>Amount</th><th>Status</th></tr>";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $color = $row['payment_status'] == 'paid' ? 'green' : ($row['payment_status'] == 'partial' ? 'orange' : 'red');
        echo "<tr>";
        echo "<td>" . $row['pledge_reference'] . "</td>";
        echo "<td>" . htmlspecialchars($row['pledger_name']) . "</td>";
        echo "<td>Â£" . number_format($row['amount_pledged'], 2) . "</td>";
        echo "<td style='color:$color'>" . $row['payment_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>âš ï¸ No pledges found. Create a test pledge first.</p>";
}

// 3. Check System Settings
echo "<h2>âš™ï¸ 3. System Settings</h2>";
$result = $db->query("SELECT setting_key, setting_value FROM system_settings");
$found = false;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $found = true;
    $status = $row['setting_value'] == '1' ? 'âœ… Enabled' : 'ðŸ”’ Disabled';
    echo "<p>ðŸ”‘ {$row['setting_key']}: <strong>{$status}</strong></p>";
}
if (!$found) {
    echo "<p style='color:orange'>âš ï¸ No settings found. Run create-pledge-tables.php</p>";
}

$db->close();

// 4. Check API Endpoints
echo "<h2>ðŸŒ 4. API Endpoints</h2>";

$endpoints = [
    'Create Pledge API' => '/api/pledges/create-pledge.php',
    'Public Pledge API' => '/api/pledges/get-pledges-public.php',
];

foreach ($endpoints as $name => $endpoint) {
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<p style='color:green'>âœ… $name (HTTP $httpCode)</p>";
    } else {
        echo "<p style='color:red'>âŒ $name (HTTP $httpCode) - URL: $url</p>";
    }
}

// 5. Check Public Pages
echo "<h2>ðŸ“„ 5. Public Pages</h2>";

$pages = [
    'Pledge Form' => '/public/pledge.php',
    'Pledge Wall' => '/public/pledge-wall.php',
    'Admin Pledges' => '/admin/pledges.php',
    'Admin Settings' => '/admin/pledge-wall-settings.php',
];

foreach ($pages as $name => $page) {
    $url = $baseUrl . $page;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<p style='color:green'>âœ… $name (HTTP $httpCode)</p>";
    } else {
        echo "<p style='color:red'>âŒ $name (HTTP $httpCode) - URL: $url</p>";
    }
}

// 6. Create Test Pledge Button
echo "<h2>ðŸ§ª 6. Create Test Pledge</h2>";
echo "<form method='POST' action='/api/pledges/create-pledge.php' style='display:inline'>";
echo "<input type='hidden' name='pledger_name' value='Test User'>";
echo "<input type='hidden' name='email' value='test@example.com'>";
echo "<input type='hidden' name='amount_pledged' value='50'>";
echo "<input type='hidden' name='payment_method' value='test'>";
echo "<button type='submit' style='background:blue;color:white;padding:10px;border-radius:5px;cursor:pointer'>Create Test Pledge</button>";
echo "</form>";

echo "<br><br>";

// 7. Quick Fix Buttons
echo "<h2>ðŸ”§ 7. Quick Fixes</h2>";
echo "<a href='/api/pledges/create-pledge-tables.php' style='background:green;color:white;padding:10px;border-radius:5px;text-decoration:none;margin-right:10px'>Recreate Tables</a>";
echo "<a href='/admin/pledges.php' style='background:purple;color:white;padding:10px;border-radius:5px;text-decoration:none;margin-right:10px'>Go to Admin Pledges</a>";
echo "<a href='/public/pledge-wall.php' style='background:red;color:white;padding:10px;border-radius:5px;text-decoration:none'>View Pledge Wall</a>";

echo "<hr>";
echo "<h3>ðŸ“‹ Next Steps:</h3>";
echo "<ol>";
echo "<li>If tables are missing, click 'Recreate Tables'</li>";
echo "<li>If no pledges exist, click 'Create Test Pledge'</li>";
echo "<li>Go to Admin Pledges and mark the pledge as 'paid'</li>";
echo "<li>Enable the pledge wall in Admin Settings</li>";
echo "<li>View the Pledge Wall</li>";
echo "</ol>";
?>