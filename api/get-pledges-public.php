<?php
// Public API for Pledge Wall - Only shows approved pledges
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$db = getDbConnection();

// Check if pledge wall is enabled
$result = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'pledge_wall_enabled'");
$row = $result->fetchArray(SQLITE3_ASSOC);
$isEnabled = $row && $row['setting_value'] == '1';

if (!$isEnabled) {
    echo json_encode(['enabled' => false, 'message' => 'Pledge wall is currently offline', 'pledges' => []]);
    $db->close();
    exit;
}

// Get all pledges with status 'paid' or 'partial' (show only completed/significant pledges)
$result = $db->query("
    SELECT 
        pledger_name,
        amount_pledged,
        amount_paid,
        payment_status,
        pledge_date,
        CASE 
            WHEN is_member = 1 THEN 'Member' 
            ELSE 'Supporter' 
        END as pledger_type,
        CASE 
            WHEN amount_paid >= amount_pledged THEN '✅ Fully Paid'
            WHEN amount_paid > 0 THEN '💰 Partially Paid'
            ELSE '🤝 Pledged'
        END as status_text
    FROM pledges 
    WHERE payment_status IN ('paid', 'partial')
    ORDER BY amount_pledged DESC
    LIMIT 50
");

$pledges = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $pledges[] = [
        'name' => htmlspecialchars($row['pledger_name']),
        'amount' => $row['amount_pledged'],
        'paid' => $row['amount_paid'],
        'type' => $row['pledger_type'],
        'status' => $row['status_text'],
        'date' => date('F Y', strtotime($row['pledge_date']))
    ];
}

// Get total stats
$totalRaised = $db->querySingle("SELECT SUM(amount_paid) as total FROM pledges WHERE payment_status IN ('paid', 'partial')");
$totalPledges = $db->querySingle("SELECT COUNT(*) as count FROM pledges WHERE payment_status IN ('paid', 'partial')");
$totalGoal = $db->querySingle("SELECT target_amount FROM pledge_campaigns WHERE is_active = 1 LIMIT 1");

$db->close();

echo json_encode([
    'enabled' => true,
    'stats' => [
        'total_raised' => floatval($totalRaised ?? 0),
        'total_pledges' => intval($totalPledges ?? 0),
        'total_goal' => floatval($totalGoal ?? 10000),
        'progress_percentage' => $totalGoal > 0 ? round(($totalRaised / $totalGoal) * 100, 1) : 0
    ],
    'pledges' => $pledges
], JSON_PRETTY_PRINT);
?>