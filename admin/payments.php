<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();

// Get all stats
$totalBookings = $db->querySingle("SELECT COUNT(*) FROM Bookings");
$totalRevenue = $db->querySingle("SELECT SUM(CAST(TotalAmount AS REAL)) FROM Bookings WHERE PaymentStatus = 'paid'");
$totalEvents = $db->querySingle("SELECT COUNT(*) FROM Events WHERE isActive = 1");
$totalAttendees = $db->querySingle("SELECT COUNT(*) FROM Attendees");
$totalRequests = $db->querySingle("SELECT COUNT(*) FROM Requests");
$pendingRequests = $db->querySingle("SELECT COUNT(*) FROM Requests WHERE Status = 'pending'");

// Monthly revenue
$monthlyRevenue = $db->query("SELECT strftime('%Y-%m', CreatedAt) as month, SUM(CAST(TotalAmount AS REAL)) as total FROM Bookings WHERE PaymentStatus = 'paid' GROUP BY month ORDER BY month DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Admin</title>
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
                <a href="admins.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👑 Admin Management</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">📊 Analytics</a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <div class="text-sm text-gray-400 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 text-red-400">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-6">📊 Analytics Dashboard</h1>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <p class="text-gray-500">Total Revenue</p>
                        <p class="text-3xl font-bold text-green-600">£<?php echo number_format($totalRevenue ?? 0, 2); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <p class="text-gray-500">Total Bookings</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo number_format($totalBookings); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <p class="text-gray-500">Total Attendees</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo number_format($totalAttendees); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <p class="text-gray-500">Pending Requests</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo number_format($pendingRequests); ?></p>
                    </div>
                </div>

                <!-- More Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-3">Monthly Revenue</h3>
                        <table class="w-full">
                            <?php while ($row = $monthlyRevenue->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo $row['month']; ?></td>
                                <td class="py-2 text-right font-bold">£<?php echo number_format($row['total'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-3">Quick Stats</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between"><span>Active Events:</span><span class="font-bold"><?php echo $totalEvents; ?></span></div>
                            <div class="flex justify-between"><span>Total Requests:</span><span class="font-bold"><?php echo $totalRequests; ?></span></div>
                            <div class="flex justify-between"><span>Approval Rate:</span><span class="font-bold"><?php echo $totalRequests > 0 ? round(($totalRequests - $pendingRequests) / $totalRequests * 100) : 0; ?>%</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php $db->close(); ?>