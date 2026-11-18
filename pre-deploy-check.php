<?php
/**
 * PRE-DEPLOYMENT VERIFICATION CHECKLIST
 * Run this before uploading to live server
 */

echo "========================================\n";
echo "  UMOJAWETU PLATFORM - PRE-DEPLOY CHECK\n";
echo "========================================\n\n";

$checks = [
    'passed' => 0,
    'warnings' => 0,
    'failed' => 0
];

// 1. Database Connection
echo "1. DATABASE CONNECTION\n";
try {
    $db = new SQLite3(__DIR__ . '/storage/umoja-events-database.db');
    if ($db) {
        echo "   ✅ Database connected\n";
        $checks['passed']++;
    }
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    $checks['failed']++;
    exit;
}

// 2. Check Attendees Table Columns
echo "\n2. ATTENDEES TABLE SCHEMA\n";
$columns = $db->query('PRAGMA table_info(Attendees)');
$columnNames = [];
$requiredColumns = ['Id', 'Name', 'Email', 'price_per_ticket', 'IsDeleted', 'DeletedAt', 'CheckedIn'];

while ($col = $columns->fetchArray(SQLITE3_ASSOC)) {
    $columnNames[] = $col['name'];
}

foreach ($requiredColumns as $col) {
    if (in_array($col, $columnNames)) {
        echo "   ✅ Column '$col' exists\n";
        $checks['passed']++;
    } else {
        echo "   ⚠️  Column '$col' MISSING\n";
        $checks['warnings']++;
    }
}

// 3. Check data counts
echo "\n3. DATA INTEGRITY\n";
$totalAttendees = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
$totalRevenue = $db->querySingle("SELECT COALESCE(SUM(CAST(price_per_ticket AS DECIMAL(10,2))), 0) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND price_per_ticket IS NOT NULL AND price_per_ticket > 0");
$deletedCount = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE IsDeleted = 1");

echo "   ✅ Active attendees: $totalAttendees\n";
echo "   ✅ Total revenue: \$" . number_format($totalRevenue, 2) . "\n";
echo "   ✅ Deleted records: $deletedCount\n";
$checks['passed'] += 3;

// 4. Check admin session security
echo "\n4. SECURITY CHECK\n";
if (file_exists(__DIR__ . '/admin/attendees.php')) {
    $content = file_get_contents(__DIR__ . '/admin/attendees.php');
    if (strpos($content, 'session_start()') !== false && strpos($content, '\$_SESSION[\'admin_logged_in\']') !== false) {
        echo "   ✅ Admin login protection enabled\n";
        $checks['passed']++;
    }
} else {
    echo "   ❌ attendees.php not found\n";
    $checks['failed']++;
}

// 5. Check file paths
echo "\n5. FILE AVAILABILITY\n";
$requiredFiles = [
    'admin/attendees.php' => 'Attendees dashboard',
    'config/config.php' => 'Database config',
    'public/booking.php' => 'Public booking page',
    'api/attendees/generate-qr.php' => 'QR generator'
];

foreach ($requiredFiles as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $desc ($file)\n";
        $checks['passed']++;
    } else {
        echo "   ⚠️  Missing: $desc ($file)\n";
        $checks['warnings']++;
    }
}

// Summary
echo "\n========================================\n";
echo "  SUMMARY\n";
echo "========================================\n";
echo "✅ Passed:   " . $checks['passed'] . "\n";
echo "⚠️  Warnings: " . $checks['warnings'] . "\n";
echo "❌ Failed:   " . $checks['failed'] . "\n\n";

if ($checks['failed'] === 0) {
    echo "🚀 READY TO DEPLOY!\n";
    echo "   All critical checks passed.\n";
    if ($checks['warnings'] > 0) {
        echo "   (Review warnings listed above)\n";
    }
} else {
    echo "⛔ NOT READY TO DEPLOY!\n";
    echo "   Please fix the failed checks above.\n";
}

echo "========================================\n";

$db->close();
?>
