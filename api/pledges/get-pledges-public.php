<?php
// Public Pledge API - Includes payment status
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');
setCorsHeaders();

$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

// Check if public display is enabled
$result = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'public_pledges_enabled'");
$row = $result->fetchArray(SQLITE3_ASSOC);
$isEnabled = $row && $row['setting_value'] == '1';

if (!$isEnabled) {
    echo json_encode(['enabled' => false, 'message' => 'Public pledge display is currently offline', 'pledges' => []]);
    $db->close();
    exit;
}

// Get pledges - show paid and partial, include payment_status
$result = $db->query("
    SELECT 
        pledger_name,
        amount_pledged,
        amount_paid,
        payment_status,
        pledge_date,
        CASE WHEN is_member = 1 THEN 'Member' ELSE 'Supporter' END as pledger_type
    FROM pledges 
    WHERE payment_status IN ('paid', 'partial')
    ORDER BY amount_pledged DESC
    LIMIT 50
");

$pledges = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $pledges[] = [
        'name' => htmlspecialchars($row['pledger_name']),
        'amount' => floatval($row['amount_pledged']),
        'paid' => floatval($row['amount_paid']),
        'type' => $row['pledger_type'],
        'payment_status' => $row['payment_status'],
        'date' => date('F Y', strtotime($row['pledge_date']))
    ];
}

// Get totals
$totalRaised = $db->querySingle("SELECT SUM(amount_paid) FROM pledges WHERE payment_status IN ('paid', 'partial')");
$totalGoal = 10000;

$db->close();

echo json_encode([
    'enabled' => true,
    'stats' => [
        'total_raised' => floatval($totalRaised ?? 0),
        'total_pledges' => count($pledges),
        'total_goal' => $totalGoal,
        'progress_percentage' => $totalGoal > 0 ? min(100, round(($totalRaised / $totalGoal) * 100, 1)) : 0
    ],
    'pledges' => $pledges
]);
?>