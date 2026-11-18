<?php
// Admin Management (Placeholder)
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();
$result = $db->query("SELECT Id, Username, Email, Role, CreatedAt FROM Users ORDER BY Id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management - Admin</title>
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
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🏠 Dashboard</a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📅 Events</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👥 Attendees</a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💰 Financial Requests</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Gallery</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="email-templates.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📧 Email Templates</a>
                <a href="admins.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">👑 Admin Management</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Analytics</a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <div class="text-sm text-gray-400 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 text-red-400">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">👑 Admin Users</h1>
                    <button onclick="alert('Add admin feature coming soon')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Add Admin</button>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-800 text-white">
                            <tr><th class="p-3">ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3"><?php echo $row['Id']; ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['Username']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td class="p-3"><?php echo $row['Role']; ?></td>
                                <td class="p-3"><?php echo substr($row['CreatedAt'], 0, 10); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php $db->close(); ?>