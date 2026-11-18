<?php
// Unified Admin Dashboard - Combines all features
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();

// Get all stats
$totalEvents = $db->querySingle("SELECT COUNT(*) FROM events_dynamic WHERE status = 'published'");
$totalBookings = $db->querySingle("SELECT COUNT(*) FROM Bookings");
$totalAttendees = $db->querySingle("SELECT COUNT(*) FROM Attendees");
$totalRevenue = $db->querySingle("SELECT SUM(CAST(TotalAmount AS REAL)) FROM Bookings WHERE PaymentStatus = 'paid'");
$pendingRequests = $db->querySingle("SELECT COUNT(*) FROM Requests WHERE Status = 'pending'");
$totalMedia = $db->querySingle("SELECT COUNT(*) FROM EventMedia");

// Monthly revenue for chart
$monthlyRevenue = [];
$result = $db->query("SELECT strftime('%Y-%m', CreatedAt) as month, SUM(CAST(TotalAmount AS REAL)) as total FROM Bookings WHERE PaymentStatus = 'paid' GROUP BY month ORDER BY month DESC LIMIT 6");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $monthlyRevenue[] = $row;
}
$monthlyRevenue = array_reverse($monthlyRevenue);

// Event stats for chart
$eventStats = [];
$result = $db->query("SELECT e.Title, COUNT(b.id) as count FROM Events e LEFT JOIN Bookings b ON e.Id = b.EventId GROUP BY e.Id LIMIT 5");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $eventStats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { transition: transform 0.2s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-5px); }
        .sidebar-item { transition: all 0.2s; }
        .sidebar-item:hover { background: #374151; transform: translateX(5px); }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Umojawetu Admin</h2>
                <p class="text-xs text-gray-400">Unified Dashboard</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard-unified.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">
                    <span>📊</span> Dashboard
                </a>
                <a href="event-builder.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>✨</span> Event Builder
                </a>
                <a href="events.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>📅</span> Events
                </a>
                <a href="attendees.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>👥</span> Attendees
                </a>
                <a href="requests.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>💰</span> Requests
                </a>
                <a href="media-manager.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>🖼️</span> Media
                </a>
                <a href="payments.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>💳</span> Payments
                </a>
                <a href="logout.php" class="sidebar-item flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">
                    <span>🚪</span> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
               <h1 class="text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin'); ?></h1>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                    <div class="stat-card bg-white rounded-xl shadow-md p-4">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-sm">Events</p><p class="text-2xl font-bold"><?php echo $totalEvents; ?></p></div>
                            <span class="text-3xl">📅</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-4">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-sm">Bookings</p><p class="text-2xl font-bold"><?php echo number_format($totalBookings); ?></p></div>
                            <span class="text-3xl">🎟️</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-4">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-sm">Attendees</p><p class="text-2xl font-bold"><?php echo number_format($totalAttendees); ?></p></div>
                            <span class="text-3xl">👥</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-4">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-sm">Revenue</p><p class="text-2xl font-bold text-green-600">£<?php echo number_format($totalRevenue ?? 0, 2); ?></p></div>
                            <span class="text-3xl">💰</span>
                        </div>
                    </div>
                    <div class="stat-card bg-white rounded-xl shadow-md p-4">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-sm">Pending</p><p class="text-2xl font-bold text-orange-600"><?php echo $pendingRequests; ?></p></div>
                            <span class="text-3xl">⏳</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <!-- Revenue Chart -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">📈 Monthly Revenue</h3>
                        <canvas id="revenueChart" height="200"></canvas>
                        <?php if (empty($monthlyRevenue)): ?>
                            <p class="text-gray-500 text-center py-8">No revenue data yet</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Events Chart -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">📊 Top Events</h3>
                        <canvas id="eventsChart" height="200"></canvas>
                        <?php if (empty($eventStats)): ?>
                            <p class="text-gray-500 text-center py-8">No event data yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-md p-4 mb-8">
                    <h3 class="font-bold text-lg mb-3">⚡ Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="event-builder.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">✨ Create Event</a>
                        <a href="attendees.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">👥 View Attendees</a>
                        <a href="media-manager.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">🖼️ Upload Media</a>
                        <a href="requests.php" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">💰 Review Requests</a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <h3 class="font-bold text-lg mb-3">🕐 Recent Activity</h3>
                    <div class="space-y-2">
                        <?php
                        $recent = $db->query("SELECT BookingReference, FullName, TotalAmount, CreatedAt FROM Bookings ORDER BY CreatedAt DESC LIMIT 5");
                        while ($row = $recent->fetchArray(SQLITE3_ASSOC)):
                        ?>
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($row['FullName']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $row['BookingReference']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">£<?php echo number_format($row['TotalAmount'], 2); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($row['CreatedAt'])); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
        if (revenueCtx && <?php echo !empty($monthlyRevenue) ? 'true' : 'false'; ?>) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthlyRevenue, 'month')); ?>,
                    datasets: [{
                        label: 'Revenue (£)',
                        data: <?php echo json_encode(array_column($monthlyRevenue, 'total')); ?>,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }

        // Events Chart
        const eventsCtx = document.getElementById('eventsChart')?.getContext('2d');
        if (eventsCtx && <?php echo !empty($eventStats) ? 'true' : 'false'; ?>) {
            new Chart(eventsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($eventStats, 'Title')); ?>,
                    datasets: [{
                        label: 'Bookings',
                        data: <?php echo json_encode(array_column($eventStats, 'count')); ?>,
                        backgroundColor: '#3498db',
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }
    </script>
</body>
</html>

<?php $db->close(); ?>