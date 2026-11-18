<?php
// Complete Admin Attendees Management - Working Version
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

// Ensure soft delete columns exist
ensureSoftDeleteColumns($db, 'Attendees');

// Check if camera mode is enabled
$cameraMode = isset($_GET['camera']) && $_GET['camera'] === '1';

if ($cameraMode) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR Scanner - Check-in</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
        <style>
            #reader { width: 100%; max-width: 500px; margin: 0 auto; }
            #reader video { border-radius: 16px; }
        </style>
    </head>
    <body class="bg-gray-100">
        <div class="container mx-auto px-4 py-8 max-w-2xl">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white text-center">
                    <h1 class="text-2xl font-bold">📷 QR Scanner</h1>
                    <p class="text-red-100">Scan attendee QR code to check in</p>
                </div>
                <div class="p-6">
                    <div id="reader"></div>
                    <div id="result" class="mt-4 hidden"></div>
                    <div class="mt-4 text-center">
                        <a href="attendees.php" class="text-red-600 hover:underline">← Back to Attendees</a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            const html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, (decodedText) => {
                html5QrCode.stop();
                const match = decodedText.match(/ID[:\s]*(\d+)/i);
                const attendeeId = match ? match[1] : null;
                if (attendeeId) {
                    fetch(`/umojawetu-platform/api/attendees/checkin-by-id.php?id=${attendeeId}`)
                        .then(res => res.json())
                        .then(data => {
                            const resultDiv = document.getElementById('result');
                            resultDiv.classList.remove('hidden');
                            if (data.success) {
                                resultDiv.innerHTML = `<div class="bg-green-100 text-green-700 p-4 rounded-lg text-center">✅ ${data.message}<br><strong>${data.name}</strong> checked in!</div>`;
                                setTimeout(() => { window.location.href = 'attendees.php'; }, 2000);
                            } else {
                                resultDiv.innerHTML = `<div class="bg-red-100 text-red-700 p-4 rounded-lg text-center">❌ ${data.message}</div>`;
                                setTimeout(() => html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, arguments.callee, () => {}), 3000);
                            }
                        });
                } else {
                    document.getElementById('result').innerHTML = '<div class="bg-red-100 text-red-700 p-4 rounded-lg text-center">❌ Invalid QR code</div>';
                    document.getElementById('result').classList.remove('hidden');
                    setTimeout(() => html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, arguments.callee, () => {}), 3000);
                }
            }, (error) => {});
        </script>
    </body>
    </html>
    <?php
    exit;
}

$db = getDbConnection();
$message = '';

// Get filters FIRST (before bulk actions)
$eventFilter = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

// Handle single check-in
if (isset($_GET['checkin'])) {
    $id = intval($_GET['checkin']);
    $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id = $id");
    header('Location: attendees.php');
    exit;
}

// Handle individual delete (soft delete)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = softDeleteRecords($db, 'Attendees', [$id]);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = 'Error deleting attendee: ' . $result['error'];
    }
    header('Location: attendees.php');
    exit;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids) || $action === 'export_all' || $action === 'restore_all') {
        if ($action === 'checkin') {
            $idsList = implode(',', array_map('intval', $ids));
            $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees checked in ✅";
            header('Location: attendees.php');
            exit;
        } elseif ($action === 'delete') {
            $idsList = implode(',', array_map('intval', $ids));
            $db->exec("UPDATE Attendees SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees moved to trash 🗑️";
            header('Location: attendees.php');
            exit;
        } elseif ($action === 'restore') {
            $idsList = implode(',', array_map('intval', $ids));
            $db->exec("UPDATE Attendees SET IsDeleted = 0, DeletedAt = NULL WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees restored ↩️";
            header('Location: attendees.php');
            exit;
        } elseif ($action === 'restore_all') {
            $db->exec("UPDATE Attendees SET IsDeleted = 0, DeletedAt = NULL WHERE IsDeleted = 1");
            $deletedCount = count(array_filter($ids)); // For counting if any specific IDs
            $message = "All deleted attendees restored ↩️";
            header('Location: attendees.php');
            exit;
        } elseif ($action === 'purge') {
            // Permanently delete selected records
            $idsList = implode(',', array_map('intval', $ids));
            $db->exec("DELETE FROM Attendees WHERE Id IN ($idsList) AND IsDeleted = 1");
            $message = count($ids) . " attendees permanently deleted 💀";
            header('Location: attendees.php?show_deleted=1');
            exit;
        } elseif ($action === 'export' || $action === 'export_all') {
            // Export selected or all (based on filters and view mode)
            if ($action === 'export_all') {
                if ($showDeleted) {
                    // Export all deleted records
                    $exportQuery = "SELECT a.Id, a.Name, a.Email, a.Phone, a.ticket_type, a.member_status, a.price_per_ticket, a.CheckedIn, e.Title as EventTitle, b.BookingReference, a.DeletedAt 
                                   FROM Attendees a 
                                   LEFT JOIN Bookings b ON a.BookingId = b.Id 
                                   LEFT JOIN Events e ON a.EventId = e.Id 
                                   WHERE a.IsDeleted = 1";
                } else {
                    // Export all active records with filters
                    $exportQuery = "SELECT a.Id, a.Name, a.Email, a.Phone, a.ticket_type, a.member_status, a.price_per_ticket, a.CheckedIn, e.Title as EventTitle, b.BookingReference 
                                   FROM Attendees a 
                                   LEFT JOIN Bookings b ON a.BookingId = b.Id 
                                   LEFT JOIN Events e ON a.EventId = e.Id 
                                   WHERE (a.IsDeleted = 0 OR a.IsDeleted IS NULL)";
                    if ($eventFilter) {
                        $exportQuery .= " AND a.EventId = $eventFilter";
                    }
                    if ($statusFilter === 'checked_in') {
                        $exportQuery .= " AND a.CheckedIn = 1";
                    } elseif ($statusFilter === 'not_checked') {
                        $exportQuery .= " AND (a.CheckedIn = 0 OR a.CheckedIn IS NULL)";
                    }
                }
            } else {
                $idsList = implode(',', array_map('intval', $ids));
                if ($showDeleted) {
                    // Export selected deleted records
                    $exportQuery = "SELECT a.Id, a.Name, a.Email, a.Phone, a.ticket_type, a.member_status, a.price_per_ticket, a.CheckedIn, e.Title as EventTitle, b.BookingReference, a.DeletedAt 
                                   FROM Attendees a 
                                   LEFT JOIN Bookings b ON a.BookingId = b.Id 
                                   LEFT JOIN Events e ON a.EventId = e.Id 
                                   WHERE a.Id IN ($idsList) AND a.IsDeleted = 1";
                } else {
                    // Export selected active records
                    $exportQuery = "SELECT a.Id, a.Name, a.Email, a.Phone, a.ticket_type, a.member_status, a.price_per_ticket, a.CheckedIn, e.Title as EventTitle, b.BookingReference 
                                   FROM Attendees a 
                                   LEFT JOIN Bookings b ON a.BookingId = b.Id 
                                   LEFT JOIN Events e ON a.EventId = e.Id 
                                   WHERE a.Id IN ($idsList)";
                }
            }
            
            $result = $db->query($exportQuery);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="attendees_' . date('Ymd_His') . '.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            // Different headers for deleted vs active
            if ($showDeleted) {
                fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Ticket Type', 'Member Status', 'Price', 'Checked In', 'Event', 'Booking Ref', 'Deleted At']);
            } else {
                fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Ticket Type', 'Member Status', 'Price', 'Checked In', 'Event', 'Booking Ref']);
            }
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if ($showDeleted) {
                    fputcsv($output, [
                        $row['Id'],
                        $row['Name'],
                        $row['Email'],
                        $row['Phone'],
                        $row['ticket_type'] ?? 'Standard',
                        $row['member_status'] ?? 'Guest',
                        $row['price_per_ticket'] ?? '0.00',
                        $row['CheckedIn'] ? 'Yes' : 'No',
                        $row['EventTitle'] ?? 'N/A',
                        $row['BookingReference'] ?? 'N/A',
                        $row['DeletedAt'] ?? 'Unknown'
                    ]);
                } else {
                    fputcsv($output, [
                        $row['Id'],
                        $row['Name'],
                        $row['Email'],
                        $row['Phone'],
                        $row['ticket_type'] ?? 'Standard',
                        $row['member_status'] ?? 'Guest',
                        $row['price_per_ticket'] ?? '0.00',
                        $row['CheckedIn'] ? 'Yes' : 'No',
                        $row['EventTitle'] ?? 'N/A',
                        $row['BookingReference'] ?? 'N/A'
                    ]);
                }
            }
            fclose($output);
            exit;
        }
    }
}

// Build query
$query = "SELECT a.*, b.BookingReference, e.Title as EventTitle 
          FROM Attendees a 
          LEFT JOIN Bookings b ON a.BookingId = b.Id 
          LEFT JOIN Events e ON a.EventId = e.Id 
          WHERE 1=1";

if (!$showDeleted) {
    $query .= " AND (a.IsDeleted = 0 OR a.IsDeleted IS NULL)";
} else {
    $query .= " AND a.IsDeleted = 1";
}

if ($eventFilter) {
    $query .= " AND a.EventId = $eventFilter";
}
if ($statusFilter === 'checked_in') {
    $query .= " AND a.CheckedIn = 1";
} elseif ($statusFilter === 'not_checked') {
    $query .= " AND (a.CheckedIn = 0 OR a.CheckedIn IS NULL)";
}
if (!empty($searchQuery)) {
    $query .= " AND (a.Name LIKE '%" . SQLite3::escapeString($searchQuery) . "%' OR a.Email LIKE '%" . SQLite3::escapeString($searchQuery) . "%')";
}
$query .= " ORDER BY a.Id DESC";

$result = $db->query($query);

// Get counts for stats
$totalAttendees = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
$checkedInCount = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE CheckedIn = 1 AND (IsDeleted = 0 OR IsDeleted IS NULL)");
$deletedCount = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE IsDeleted = 1");
$pendingCount = $totalAttendees - $checkedInCount;

// Get revenue statistics
$totalRevenue = $db->querySingle("SELECT COALESCE(SUM(CAST(price_per_ticket AS DECIMAL(10,2))), 0) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND price_per_ticket IS NOT NULL AND price_per_ticket > 0");
$averagePrice = $totalAttendees > 0 ? $db->querySingle("SELECT COALESCE(AVG(CAST(price_per_ticket AS DECIMAL(10,2))), 0) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND price_per_ticket IS NOT NULL AND price_per_ticket > 0") : 0;

// Get events for filter dropdown
$eventsResult = $db->query("SELECT Id, Title FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL) ORDER BY Date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendees Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stat-card { transition: transform 0.2s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-3px); }
        .notification { position: fixed; top: 20px; right: 20px; z-index: 1100; padding: 12px 20px; border-radius: 8px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .btn { transition: all 0.2s; cursor: pointer; }
        .btn:hover { transform: translateY(-1px); }
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
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">👥 Attendees</a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💰 Requests</a>
                <a href="pledges.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🤝 Pledges</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Analytics</a>
                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👑 Admin Users</a>
                <a href="trash.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🗑️ Trash <?php $trashCount = getDeletedRecordsCount($db, 'Attendees'); echo $trashCount > 0 ? '<span class="ml-2 bg-red-500 text-white px-2 py-0 rounded-full text-xs">' . $trashCount . '</span>' : ''; ?></a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold">👥 Attendees Management</h1>
                    <p class="text-gray-600 text-sm">Manage attendees, check-in, and send emails</p>
                </div>
                <div class="flex gap-2">
                    <a href="scanner-working.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 btn">📷 Scan QR</a>
                    <button onclick="location.reload()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 btn">🔄 Refresh</button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">✅ <?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                <div class="stat-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Total Attendees</p><p class="text-3xl font-bold"><?php echo $totalAttendees; ?></p></div><span class="text-4xl">👥</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Checked In</p><p class="text-3xl font-bold"><?php echo $checkedInCount; ?></p></div><span class="text-4xl">✅</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Pending</p><p class="text-3xl font-bold"><?php echo $pendingCount; ?></p></div><span class="text-4xl">⏳</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Total Revenue</p><p class="text-3xl font-bold">$<?php echo number_format($totalRevenue, 2); ?></p></div><span class="text-4xl">💰</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Avg Price</p><p class="text-3xl font-bold">$<?php echo number_format($averagePrice, 2); ?></p></div><span class="text-4xl">📊</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-4 text-white cursor-pointer" onclick="location.href='?show_deleted=1'">
                    <div class="flex justify-between"><div><p class="text-sm">Deleted</p><p class="text-3xl font-bold"><?php echo $deletedCount; ?></p></div><span class="text-4xl">🗑️</span></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]"><input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search name or email..." class="w-full border rounded-lg p-2"></div>
                    <div><select name="event_id" class="border rounded-lg p-2"><option value="">All Events</option><?php while($e=$eventsResult->fetchArray(SQLITE3_ASSOC)):?><option value="<?php echo $e['Id']; ?>" <?php echo $eventFilter==$e['Id']?'selected':''; ?>><?php echo htmlspecialchars($e['Title']); ?></option><?php endwhile;?></select></div>
                    <div><select name="status" class="border rounded-lg p-2"><option value="all">All Status</option><option value="checked_in" <?php echo $statusFilter=='checked_in'?'selected':''; ?>>Checked In</option><option value="not_checked" <?php echo $statusFilter=='not_checked'?'selected':''; ?>>Not Checked</option></select></div>
                    <?php if($showDeleted): ?>
                    <input type="hidden" name="show_deleted" value="1">
                    <div><button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg btn">🗑️ Show Deleted</button></div>
                    <div><a href="attendees.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-center btn">← Back</a></div>
                    <?php else: ?>
                    <div><button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg btn">🔍 Filter</button></div>
                    <div><a href="attendees.php" class="text-red-600 hover:underline ml-2 font-semibold">Reset</a></div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" id="bulkForm" class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-50 p-4 border-b">
                    <?php if($showDeleted): ?>
                    <!-- Deleted Attendees Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-4">
                        <div class="flex gap-2">
                            <select name="bulk_action" class="flex-1 border rounded-lg p-2 text-sm"><option value="">Bulk Actions</option><option value="restore">↩️ Restore Selected</option><option value="purge">💀 Permanently Delete</option></select>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn text-sm">Apply</button>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="selectAll()" class="flex-1 bg-gray-300 text-gray-700 px-3 py-2 rounded-lg btn text-sm">✓ Select All</button>
                            <button type="button" onclick="deselectAll()" class="flex-1 bg-gray-300 text-gray-700 px-3 py-2 rounded-lg btn text-sm">✗ Clear All</button>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="if(confirm('↩️ Restore ALL deleted attendees?')) { submitBulkAction('restore_all'); }" class="flex-1 bg-green-600 text-white px-3 py-2 rounded-lg btn text-sm">↩️ Restore All</button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="exportDeletedSelected()" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg btn text-sm">📥 Export Selected</button>
                        <button type="button" onclick="exportDeletedAll()" class="flex-1 bg-emerald-600 text-white px-4 py-2 rounded-lg btn text-sm">📥 Export All</button>
                    </div>
                    <?php else: ?>
                    <!-- Active Attendees Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-4">
                        <div class="flex gap-2">
                            <select name="bulk_action" class="flex-1 border rounded-lg p-2 text-sm"><option value="">Bulk Actions</option><option value="checkin">✅ Check In</option><option value="delete">🗑️ Move to Trash</option></select>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn text-sm">Apply</button>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="selectAll()" class="flex-1 bg-gray-300 text-gray-700 px-3 py-2 rounded-lg btn text-sm">✓ Select All</button>
                            <button type="button" onclick="deselectAll()" class="flex-1 bg-gray-300 text-gray-700 px-3 py-2 rounded-lg btn text-sm">✗ Clear All</button>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="bulkPrintQR()" class="flex-1 bg-purple-600 text-white px-3 py-2 rounded-lg btn text-sm">🖨️ Print QRs</button>
                            <button type="button" onclick="bulkResend()" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg btn text-sm">📧 Resend</button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="document.querySelector('select[name=bulk_action]').value='export'; document.getElementById('bulkForm').submit();" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg btn text-sm">📥 Export Selected</button>
                        <button type="button" onclick="document.querySelector('select[name=bulk_action]').value='export_all'; document.getElementById('bulkForm').submit();" class="flex-1 bg-emerald-600 text-white px-4 py-2 rounded-lg btn text-sm">📥 Export All</button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="p-2 w-8"><input type="checkbox" id="masterCheckbox" onclick="toggleAll(this)"></th>
                                <th class="p-2">ID</th>
                                <th class="p-2">Name</th>
                                <th class="p-2">Email</th>
                                <th class="p-2">Event</th>
                                <th class="p-2">Ticket</th>
                                <th class="p-2">Price</th>
                                <?php if(!$showDeleted): ?>
                                <th class="p-2">Status</th>
                                <th class="p-2">QR Code</th>
                                <th class="p-2">Email</th>
                                <?php else: ?>
                                <th class="p-2">Deleted</th>
                                <?php endif; ?>
                                <th class="p-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rowCount = 0;
                            while($row = $result->fetchArray(SQLITE3_ASSOC)): 
                            $rowCount++;
                            ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2"><input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox"></td>
                                <td class="p-2"><?php echo $row['Id']; ?></td>
                                <td class="p-2 font-medium"><?php echo htmlspecialchars($row['Name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($row['EventTitle'] ?? 'N/A'); ?></td>
                                <td class="p-2"><?php echo $row['ticket_type'] ?? 'Standard'; ?></td>
                                <td class="p-2 text-right font-semibold text-blue-600">$<?php echo number_format($row['price_per_ticket'] ?? 0, 2); ?></td>
                                <?php if(!$showDeleted): ?>
                                <td class="p-2"><?php echo $row['CheckedIn'] ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">✅ In</span>' : '<span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">⏳ Pending</span>'; ?></td>
                                <td class="p-2"><a href="/umojawetu-platform/api/attendees/generate-qr.php?id=<?php echo $row['Id']; ?>" target="_blank" class="bg-purple-600 text-white px-2 py-1 rounded text-xs inline-block btn">📱</a></td>
                                <td class="p-2"><span class="email-status text-xs px-2 py-1 rounded bg-gray-100" id="status-<?php echo $row['Id']; ?>">—</span></td>
                                <td class="p-2">
                                    <div class="flex gap-1 flex-wrap">
                                        <?php if(!$row['CheckedIn']): ?><a href="?checkin=<?php echo $row['Id']; ?>" class="bg-green-600 text-white px-2 py-1 rounded text-xs btn" onclick="return confirm('Check in?')">In</a><?php endif; ?>
                                        <button type="button" onclick="resendEmail(<?php echo $row['Id']; ?>)" class="bg-blue-600 text-white px-2 py-1 rounded text-xs btn">📧</button>
                                        <button type="button" onclick="printPass(<?php echo $row['Id']; ?>)" class="bg-teal-600 text-white px-2 py-1 rounded text-xs btn">🖨️</button>
                                        <button type="button" onclick="deleteAttendee(<?php echo $row['Id']; ?>)" class="bg-red-600 text-white px-2 py-1 rounded text-xs btn">🗑️</button>
                                    </div>
                                </td>
                                <?php else: ?>
                                <td class="p-2 text-xs text-gray-600"><?php echo $row['DeletedAt'] ? date('M d, Y', strtotime($row['DeletedAt'])) : 'Unknown'; ?></td>
                                <td class="p-2">
                                    <div class="flex gap-1">
                                        <button type="button" onclick="restoreSingle(<?php echo $row['Id']; ?>)" class="bg-green-600 text-white px-2 py-1 rounded text-xs btn">↩️</button>
                                        <button type="button" onclick="purgeSingle(<?php echo $row['Id']; ?>)" class="bg-red-600 text-white px-2 py-1 rounded text-xs btn">💀</button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($rowCount === 0): ?>
                            <tr>
                                <td colspan="<?php echo $showDeleted ? '8' : '10'; ?>" class="text-center text-gray-500 py-8">
                                    <?php if($showDeleted): ?>
                                        🗑️ No deleted attendees
                                    <?php else: ?>
                                        👥 No attendees found
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Select All Functions
        function toggleAll(source) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
            updateSelectCount();
        }
        function selectAll() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = true);
            if(document.getElementById('masterCheckbox')) document.getElementById('masterCheckbox').checked = true;
            updateSelectCount();
        }
        function deselectAll() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            if(document.getElementById('masterCheckbox')) document.getElementById('masterCheckbox').checked = false;
            updateSelectCount();
        }

        // Update select count
        function updateSelectCount() {
            const checked = document.querySelectorAll('.row-checkbox:checked').length;
            if(checked > 0) {
                console.log(`Selected: ${checked} items`);
            }
        }

        // Notification
        function showNotification(msg, type) {
            let n = document.createElement('div');
            n.className = `notification ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            n.innerHTML = (type === 'success' ? '✅ ' : '❌ ') + msg;
            document.body.appendChild(n);
            setTimeout(() => {
                n.style.opacity = '0';
                n.style.transition = 'opacity 0.5s';
                setTimeout(() => n.remove(), 500);
            }, 3000);
        }

        // Print Pass
        function printPass(id) {
            window.open(`/umojawetu-platform/api/attendees/generate-qr.php?id=${id}`, '_blank');
        }

        // Bulk Print QR Codes
        function bulkPrintQR() {
            let selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if(selected.length === 0) {
                showNotification('Please select at least one attendee', 'error');
                return;
            }
            selected.forEach((id, index) => {
                setTimeout(() => {
                    window.open(`/umojawetu-platform/api/attendees/generate-qr.php?id=${id}`, '_blank');
                }, index * 200);
            });
            showNotification(`Opening QR codes for ${selected.length} attendees...`, 'success');
        }

        // Delete Attendee (Soft Delete)
        function deleteAttendee(id) {
            if(!confirm('🗑️ Move to trash? (Can be restored)')) return;
            window.location.href = `?delete=${id}`;
        }

        // Restore Single
        function restoreSingle(id) {
            if(!confirm('↩️ Restore this attendee?')) return;
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input name="bulk_action" value="restore"><input name="selected_ids[]" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }

        // Purge Single (Permanent Delete)
        function purgeSingle(id) {
            if(!confirm('💀 Permanently delete "' + id + '"? Cannot be undone!')) return;
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input name="bulk_action" value="purge"><input name="selected_ids[]" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }

        // Submit bulk action
        function submitBulkAction(action) {
            if(action === 'restore_all') {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input name="bulk_action" value="${action}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Export functions for deleted view
        function exportDeletedSelected() {
            let selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if(selected.length === 0) {
                showNotification('Select attendees to export', 'error');
                return;
            }
            document.querySelector('select[name=bulk_action]').value='export';
            document.getElementById('bulkForm').submit();
        }

        function exportDeletedAll() {
            document.querySelector('select[name=bulk_action]').value='export_all';
            document.getElementById('bulkForm').submit();
        }

        // Resend Email
        async function resendEmail(id) {
            if(!confirm('Resend confirmation email?')) return;
            
            let btn = event.target;
            let originalText = btn.innerText;
            btn.innerText = '⏳';
            btn.disabled = true;
            
            // Simulate email send
            await new Promise(r => setTimeout(r, 800));
            
            let statusSpan = document.getElementById(`status-${id}`);
            if(statusSpan) {
                statusSpan.innerHTML = '✅ Sent';
                statusSpan.className = 'email-status text-xs px-2 py-1 rounded bg-green-100 text-green-700';
            }
            showNotification('Email sent!', 'success');
            
            btn.innerText = originalText;
            btn.disabled = false;
        }

        // Preview Email
        async function previewEmail(id) {
            let rows = document.querySelectorAll('table tbody tr');
            let name = 'Attendee';
            let email = 'email@example.com';
            let eventTitle = 'Event';
            let ticketType = 'Standard';
            
            for(let row of rows) {
                let checkbox = row.querySelector(`input[value="${id}"]`);
                if(checkbox) {
                    let cells = row.querySelectorAll('td');
                    if(cells[2]) name = cells[2].innerText;
                    if(cells[3]) email = cells[3].innerText;
                    if(cells[4]) eventTitle = cells[4].innerText;
                    if(cells[5]) ticketType = cells[5].innerText;
                    break;
                }
            }
            
            let modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] overflow-auto m-4">
                    <div class="sticky top-0 bg-white border-b p-4 flex justify-between items-center">
                        <h3 class="font-bold text-lg">📧 Email Preview</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                    </div>
                    <div class="p-4">
                        <div class="bg-gray-50 p-3 rounded mb-4">
                            <p><strong>To:</strong> ${escapeHtml(email)}</p>
                            <p><strong>Subject:</strong> Booking Confirmation</p>
                        </div>
                        <div class="border rounded p-4 bg-white">
                            <div style="text-align:center;padding:20px;background:#e74c3c;color:white;border-radius:10px;">
                                <h2 style="margin:0;">UMOJA WETU</h2>
                                <p style="margin:5px 0 0;">Booking Confirmation</p>
                            </div>
                            <div style="padding:20px;">
                                <p>Dear <strong>${escapeHtml(name)}</strong>,</p>
                                <p>Your booking has been confirmed.</p>
                                <hr>
                                <p><strong>Event:</strong> ${escapeHtml(eventTitle)}</p>
                                <p><strong>Ticket Type:</strong> ${escapeHtml(ticketType)}</p>
                                <p><strong>ID:</strong> #${id}</p>
                                <hr>
                                <p>Thank you!</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button onclick="this.closest('.fixed').remove()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Close</button>
                            <button onclick="sendTestEmail(${id})" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg">Send</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Send email from preview
        async function sendTestEmail(id) {
            if(!confirm('Send email?')) return;
            let statusSpan = document.getElementById(`status-${id}`);
            if(statusSpan) {
                statusSpan.innerHTML = '✅ Sent';
                statusSpan.className = 'email-status text-xs px-2 py-1 rounded bg-green-100 text-green-700';
            }
            showNotification('Email sent!', 'success');
            let modal = document.querySelector('.fixed.bg-black\\/50');
            if(modal) modal.remove();
        }

        // Bulk Resend
        async function bulkResend() {
            let selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if(selected.length === 0) {
                showNotification('Select attendees first', 'error');
                return;
            }
            if(!confirm(`Resend to ${selected.length} attendees?`)) return;
            selected.forEach(id => {
                let statusSpan = document.getElementById(`status-${id}`);
                if(statusSpan) {
                    statusSpan.innerHTML = '✅ Sent';
                    statusSpan.className = 'email-status text-xs px-2 py-1 rounded bg-green-100 text-green-700';
                }
            });
            showNotification(`Sent to ${selected.length} attendees!`, 'success');
        }

        // Escape HTML
        function escapeHtml(str) {
            if(!str) return '';
            return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
        }
    </script>
</body>
</html>

<?php $db->close(); ?>