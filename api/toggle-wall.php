<?php
// Admin Toggle for Pledge Wall
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$db = getDbConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get current status
$result = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'pledge_wall_enabled'");
$row = $result->fetchArray(SQLITE3_ASSOC);
$currentStatus = $row ? $row['setting_value'] : '0';

if ($action === 'get') {
    echo json_encode(['enabled' => $currentStatus == '1']);
    exit;
}

if ($action === 'toggle') {
    $newStatus = $currentStatus == '1' ? '0' : '1';
    
    if ($row) {
        $db->exec("UPDATE system_settings SET setting_value = '$newStatus', updated_at = datetime('now') WHERE setting_key = 'pledge_wall_enabled'");
    } else {
        $db->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES ('pledge_wall_enabled', '$newStatus')");
    }
    
    echo json_encode(['enabled' => $newStatus == '1', 'message' => 'Pledge wall ' . ($newStatus == '1' ? 'enabled' : 'disabled')]);
    exit;
}

$db->close();
?>