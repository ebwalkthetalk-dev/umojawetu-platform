<?php
// Admin Pledge Wall Settings
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';

// Ensure system_settings table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS system_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME
    )
");

// Get current status
$result = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'pledge_wall_enabled'");
$row = $result->fetchArray(SQLITE3_ASSOC);
$isEnabled = $row && $row['setting_value'] == '1';

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = isset($_POST['enabled']) ? '1' : '0';
    
    if ($row) {
        $db->exec("UPDATE system_settings SET setting_value = '$newStatus', updated_at = datetime('now') WHERE setting_key = 'pledge_wall_enabled'");
    } else {
        $db->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES ('pledge_wall_enabled', '$newStatus')");
    }
    
    $message = "Pledge wall " . ($newStatus == '1' ? 'enabled' : 'disabled');
    $isEnabled = $newStatus == '1';
    
    // Refresh page to show updated status
    header('Location: pledge-wall-settings.php?message=' . urlencode($message));
    exit;
}

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pledge Wall Settings - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400">Umojawetu Platform</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard-unified.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Dashboard</a>
                <a href="event-builder.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">✨ Event Builder</a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📅 Events</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👥 Attendees</a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💰 Requests</a>
                <a href="pledges.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🤝 Pledges</a>
                <a href="pledge-wall-settings.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">🏆 Pledge Wall</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Analytics</a>
                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👑 Admin Users</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-2xl font-bold mb-6">🏆 Pledge Wall Settings</h1>
                
                <?php if ($message): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">✅ <?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold">Pledge Wall Visibility</h2>
                            <p class="text-gray-600 text-sm">Control whether the pledge wall is visible to the public</p>
                        </div>
                        <div class="text-4xl">🏆</div>
                    </div>
                    
                    <form method="POST" class="space-y-6">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <span class="font-medium">Public Visibility</span>
                                <p class="text-sm text-gray-500">When enabled, anyone can view the pledge wall</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?php echo $isEnabled ? 'checked' : ''; ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                <span class="ml-3 text-sm font-medium" id="statusText">
                                    <?php echo $isEnabled ? '✅ Live' : '🔒 Offline'; ?>
                                </span>
                            </label>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="font-bold text-blue-800 mb-2">📋 Preview Links</h3>
                            <p class="text-sm text-blue-700 mb-2">View the pledge wall in action:</p>
                            <a href="/umojawetu-platform/public/pledge-wall.php" target="_blank" class="text-blue-600 hover:underline break-all">
                                <?php echo $siteUrl; ?>/public/pledge-wall.php
                            </a>
                        </div>
                        
                        <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700 transition">
                            Update Settings
                        </button>
                    </form>
                </div>
                
                <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-bold text-yellow-800 mb-2">⚠️ Important Notes</h3>
                    <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                        <li>Only pledges with status "Paid" or "Partial" are displayed</li>
                        <li>Pledger names are shown publicly</li>
                        <li>Amounts are displayed for transparency</li>
                        <li>You can disable the wall at any time</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const toggle = document.querySelector('input[name="enabled"]');
        const statusText = document.getElementById('statusText');
        
        if (toggle) {
            toggle.addEventListener('change', function() {
                statusText.textContent = this.checked ? '✅ Live' : '🔒 Offline';
            });
        }
    </script>
</body>
</html>

<?php $db->close(); ?>