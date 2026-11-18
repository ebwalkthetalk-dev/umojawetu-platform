<?php
// Start session and check login
require_once __DIR__ . '/../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get database connection
$db = getDbConnection();

// Get stats
$totalEvents = 0;
$totalAttendees = 0;
$totalRevenue = 0;
$pendingRequests = 0;

// Get total events
$result = $db->query("SELECT COUNT(*) as count FROM Events WHERE isActive = 1");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalEvents = $row['count'];
}

// Get total attendees
$result = $db->query("SELECT COUNT(*) as count FROM Attendees");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalAttendees = $row['count'];
}

// Get total revenue from bookings
$result = $db->query("SELECT SUM(CAST(TotalAmount AS REAL)) as total FROM Bookings WHERE PaymentStatus = 'paid'");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalRevenue = number_format($row['total'] ?? 0, 2);
}

// Get pending requests
$result = $db->query("SELECT COUNT(*) as count FROM Requests WHERE Status = 'pending'");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $pendingRequests = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background: #374151;
            transform: translateX(5px);
        }
        .stat-card {
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400 mt-1">Umojawetu Platform</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">
                    <span>🏠</span> Dashboard
                </a>
                <a href="events.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>📅</span> Events
                </a>
                <a href="attendees.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>👥</span> Attendees
                </a>
                <a href="requests.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>💰</span> Financial Requests
                </a>
                <a href="media-manager.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>🖼️</span> Gallery
                </a>
                <a href="payments.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>💳</span> Payments
                </a>
                <a href="email-templates.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>📧</span> Email Templates
                </a>
                <a href="admins.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>👑</span> Admin Management
                </a>
                <a href="analytics.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>📊</span> Analytics
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <div class="text-sm text-gray-400 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <a href="logout.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 text-red-400">
                    <span>🚪</span> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome to Admin Dashboard</h1>
                <p class="text-gray-600 mb-6">Current Time: <?php echo date('Y-m-d H:i:s'); ?></p>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Events</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $totalEvents; ?></p>
                            </div>
                            <span class="text-4xl">📅</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Attendees</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $totalAttendees; ?></p>
                            </div>
                            <span class="text-4xl">👥</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Revenue</p>
                                <p class="text-3xl font-bold text-green-600">£<?php echo $totalRevenue; ?></p>
                            </div>
                            <span class="text-4xl">💰</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Pending Requests</p>
                                <p class="text-3xl font-bold text-orange-600"><?php echo $pendingRequests; ?></p>
                            </div>
                            <span class="text-4xl">📝</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="events.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">➕ Create Event</a>
                        <a href="media-manager.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">🖼️ Upload Photos</a>
                        <a href="attendees.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">👥 View Attendees</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$db->close();
?>