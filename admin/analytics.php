<?php
// Admin Analytics Dashboard - Secure Version with Charts
require_once __DIR__ . '/../config/config.php';

// Secure session check - redirect to NEW secure login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}

$db = getDbConnection();

// Safe queries with error handling
$totalBookings = 0;
$totalRevenue = 0;
$totalEvents = 0;
$totalAttendees = 0;
$totalRequests = 0;
$pendingRequests = 0;

// Get total bookings
$result = $db->query("SELECT COUNT(*) as count FROM Bookings");
if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalBookings = $row['count'];
}

// Get total revenue
$result = $db->query("SELECT SUM(CAST(TotalAmount AS REAL)) as total FROM Bookings WHERE PaymentStatus = 'paid'");
if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalRevenue = $row['total'] ?? 0;
}

// Get total events (active only)
$result = $db->query("SELECT COUNT(*) as count FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalEvents = $row['count'];
}

// Get total attendees
$result = $db->query("SELECT COUNT(*) as count FROM Attendees");
if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
    $totalAttendees = $row['count'];
}

// Get checked-in attendees
$checkedInAttendees = 0;
$result = $db->query("SELECT COUNT(*) as count FROM Attendees WHERE CheckedIn = 1");
if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
    $checkedInAttendees = $row['count'];
}

// Get member vs non-member stats
$memberAttendees = 0;
$nonMemberAttendees = 0;
$result = $db->query("SELECT COUNT(*) as count FROM Attendees WHERE member_status = 'member'");
if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
    $memberAttendees = $row['count'];
}
$nonMemberAttendees = $totalAttendees - $memberAttendees;

// Get request stats (if table exists)
$tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='Requests'");
if ($tableCheck && $tableCheck->fetchArray()) {
    $result = $db->query("SELECT COUNT(*) as count FROM Requests WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
    if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
        $totalRequests = $row['count'];
    }
    $result = $db->query("SELECT COUNT(*) as count FROM Requests WHERE Status = 'pending' AND (IsDeleted = 0 OR IsDeleted IS NULL)");
    if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
        $pendingRequests = $row['count'];
    }
}

// Monthly revenue (last 6 months)
$monthlyRevenue = [];
$result = $db->query("SELECT strftime('%Y-%m', CreatedAt) as month, SUM(CAST(TotalAmount AS REAL)) as total FROM Bookings WHERE PaymentStatus = 'paid' GROUP BY month ORDER BY month DESC LIMIT 6");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $monthlyRevenue[] = $row;
}
$monthlyRevenue = array_reverse($monthlyRevenue);

// Top events by bookings
$topEvents = [];
$result = $db->query("SELECT e.Title, COUNT(b.id) as booking_count, SUM(CAST(b.TotalAmount AS REAL)) as revenue 
                      FROM Events e 
                      LEFT JOIN Bookings b ON e.Id = b.EventId 
                      WHERE (e.IsDeleted = 0 OR e.IsDeleted IS NULL)
                      GROUP BY e.Id 
                      ORDER BY booking_count DESC 
                      LIMIT 5");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $topEvents[] = $row;
}

// Recent bookings
$recentBookings = [];
$result = $db->query("SELECT BookingReference, FullName, Email, TotalAmount, PaymentStatus, CreatedAt FROM Bookings ORDER BY CreatedAt DESC LIMIT 10");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recentBookings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
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
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">📊 Analytics</a>
                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👑 Admin Users</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <div class="text-sm text-gray-400 mb-2">Logged in as: <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin'); ?></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto fade-in">
            <div class="p-6">
                <h1 class="text-3xl font-bold mb-2">📊 Analytics Dashboard</h1>
                <p class="text-gray-600 mb-6">Real-time insights and performance metrics</p>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div><p class="text-sm opacity-90">Total Revenue</p><p class="text-3xl font-bold">£<?php echo number_format($totalRevenue, 2); ?></p></div>
                            <span class="text-4xl opacity-80">💰</span>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div><p class="text-sm opacity-90">Total Bookings</p><p class="text-3xl font-bold"><?php echo number_format($totalBookings); ?></p></div>
                            <span class="text-4xl opacity-80">🎟️</span>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div><p class="text-sm opacity-90">Total Attendees</p><p class="text-3xl font-bold"><?php echo number_format($totalAttendees); ?></p></div>
                            <span class="text-4xl opacity-80">👥</span>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div><p class="text-sm opacity-90">Pending Requests</p><p class="text-3xl font-bold"><?php echo number_format($pendingRequests); ?></p></div>
                            <span class="text-4xl opacity-80">⏳</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <!-- Revenue Chart -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">📈 Monthly Revenue Trend</h3>
                        <canvas id="revenueChart" height="200"></canvas>
                        <?php if (empty($monthlyRevenue)): ?>
                            <p class="text-gray-500 text-center py-4">No revenue data available yet</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Member vs Non-Member Chart -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">👥 Member vs Non-Member</h3>
                        <canvas id="memberChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Second Row Charts -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <!-- Top Events Chart -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">🏆 Top Performing Events</h3>
                        <canvas id="eventsChart" height="200"></canvas>
                        <?php if (empty($topEvents)): ?>
                            <p class="text-gray-500 text-center py-4">No event data available</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Check-in Rate Chart -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">✅ Check-in Rate</h3>
                        <canvas id="checkinChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Quick Stats Summary -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">📋 Quick Stats</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between py-1 border-b"><span>Active Events:</span><span class="font-bold"><?php echo $totalEvents; ?></span></div>
                            <div class="flex justify-between py-1 border-b"><span>Total Requests:</span><span class="font-bold"><?php echo $totalRequests; ?></span></div>
                            <div class="flex justify-between py-1 border-b"><span>Checked-in Attendees:</span><span class="font-bold text-green-600"><?php echo $checkedInAttendees; ?> (<?php echo $totalAttendees > 0 ? round(($checkedInAttendees / $totalAttendees) * 100) : 0; ?>%)</span></div>
                            <div class="flex justify-between py-1 border-b"><span>Member Attendees:</span><span class="font-bold text-blue-600"><?php echo $memberAttendees; ?> (<?php echo $totalAttendees > 0 ? round(($memberAttendees / $totalAttendees) * 100) : 0; ?>%)</span></div>
                            <div class="flex justify-between py-1"><span>Pending Approval Rate:</span><span class="font-bold text-orange-600"><?php echo $totalRequests > 0 ? round(($pendingRequests / $totalRequests) * 100) : 0; ?>%</span></div>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings -->
                    <div class="bg-white rounded-xl shadow-md p-4">
                        <h3 class="font-bold text-lg mb-3">🕐 Recent Bookings</h3>
                        <div class="space-y-2 max-h-64 overflow-auto">
                            <?php foreach ($recentBookings as $booking): ?>
                            <div class="flex justify-between items-center border-b pb-2">
                                <div>
                                    <p class="font-medium text-sm"><?php echo htmlspecialchars($booking['FullName']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $booking['BookingReference']; ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">£<?php echo number_format($booking['TotalAmount'], 2); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date('d/m/Y', strtotime($booking['CreatedAt'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($recentBookings)): ?>
                                <p class="text-gray-500 text-center py-4">No recent bookings</p>
                            <?php endif; ?>
                        </div>
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
                        tension: 0.4,
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }

        // Member vs Non-Member Chart
        const memberCtx = document.getElementById('memberChart')?.getContext('2d');
        if (memberCtx) {
            new Chart(memberCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Members', 'Non-Members'],
                    datasets: [{
                        data: [<?php echo $memberAttendees; ?>, <?php echo $nonMemberAttendees; ?>],
                        backgroundColor: ['#3498db', '#95a5a6'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
            });
        }

        // Top Events Chart
        const eventsCtx = document.getElementById('eventsChart')?.getContext('2d');
        if (eventsCtx && <?php echo !empty($topEvents) ? 'true' : 'false'; ?>) {
            new Chart(eventsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_map(function($e) { return substr($e['Title'], 0, 20); }, $topEvents)); ?>,
                    datasets: [{
                        label: 'Bookings',
                        data: <?php echo json_encode(array_column($topEvents, 'booking_count')); ?>,
                        backgroundColor: '#3498db',
                        borderRadius: 8,
                        barPercentage: 0.7
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } } }
            });
        }

        // Check-in Rate Chart
        const checkinCtx = document.getElementById('checkinChart')?.getContext('2d');
        if (checkinCtx) {
            const checkedIn = <?php echo $checkedInAttendees; ?>;
            const notCheckedIn = <?php echo $totalAttendees - $checkedInAttendees; ?>;
            new Chart(checkinCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Checked In', 'Not Checked In'],
                    datasets: [{
                        data: [checkedIn, notCheckedIn],
                        backgroundColor: ['#27ae60', '#e74c3c'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
            });
        }
    </script>
</body>
</html>

<?php $db->close(); ?>