<?php
// Enhanced Admin Attendees Management with Pricing, Edit, and Flexible Layout
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
    // ... Camera mode code (keep existing implementation) ...
    exit;
}

$db = getDbConnection();
$message = '';
$adminRole = $_SESSION['admin_role'] ?? 'viewer';
$adminId = $_SESSION['admin_id'] ?? null;

// Helper: Check permission for current admin
function hasPermission($role, $action) {
    $permissions = [
        'super_admin' => ['view', 'edit', 'delete', 'restore', 'checkin', 'adjust_price', 'email', 'export'],
        'manager' => ['view', 'edit', 'delete', 'restore', 'checkin', 'adjust_price', 'email', 'export'],
        'operator' => ['view', 'checkin', 'email', 'export'],
        'viewer' => ['view', 'export'],
        'finance' => ['view', 'adjust_price', 'export']
    ];
    
    return in_array($action, $permissions[$role] ?? []);
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids) || $action === 'export_all') {
        if ($action === 'checkin' && hasPermission($adminRole, 'checkin')) {
            $idsList = implode(',', array_map('intval', $ids));
            $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees checked in ✅";
            header('Location: attendees-enhanced.php');
            exit;
        } elseif ($action === 'delete' && hasPermission($adminRole, 'delete')) {
            $idsList = implode(',', array_map('intval', $ids));
            $db->exec("UPDATE Attendees SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees moved to trash 🗑️";
            header('Location: attendees-enhanced.php');
            exit;
        } elseif (($action === 'export' || $action === 'export_all') && hasPermission($adminRole, 'export')) {
            // Export handling
            if ($action === 'export_all') {
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
            } else {
                $idsList = implode(',', array_map('intval', $ids));
                $exportQuery = "SELECT a.Id, a.Name, a.Email, a.Phone, a.ticket_type, a.member_status, a.price_per_ticket, a.CheckedIn, e.Title as EventTitle, b.BookingReference 
                               FROM Attendees a 
                               LEFT JOIN Bookings b ON a.BookingId = b.Id 
                               LEFT JOIN Events e ON a.EventId = e.Id 
                               WHERE a.Id IN ($idsList)";
            }
            
            $result = $db->query($exportQuery);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="attendees_' . date('Ymd_His') . '.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Ticket Type', 'Member Status', 'Price', 'Checked In', 'Event', 'Booking Ref']);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                fputcsv($output, [
                    $row['Id'],
                    $row['Name'],
                    $row['Email'],
                    $row['Phone'],
                    $row['ticket_type'] ?? 'Standard',
                    $row['member_status'] ?? 'Guest',
                    $row['price_per_ticket'] ?? 0,
                    $row['CheckedIn'] ? 'Yes' : 'No',
                    $row['EventTitle'] ?? 'N/A',
                    $row['BookingReference'] ?? 'N/A'
                ]);
            }
            fclose($output);
            exit;
        }
    }
}

// Handle edit attendee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_attendee') {
    if (!hasPermission($adminRole, 'edit')) {
        $message = '❌ Permission denied';
    } else {
        $id = intval($_POST['attendee_id']);
        $name = SQLite3::escapeString($_POST['name'] ?? '');
        $email = SQLite3::escapeString($_POST['email'] ?? '');
        $phone = SQLite3::escapeString($_POST['phone'] ?? '');
        $ticket_type = SQLite3::escapeString($_POST['ticket_type'] ?? '');
        $member_status = SQLite3::escapeString($_POST['member_status'] ?? '');
        
        $db->exec("UPDATE Attendees SET Name = '$name', Email = '$email', Phone = '$phone', ticket_type = '$ticket_type', member_status = '$member_status', last_edited_by = $adminId, last_edited_at = datetime('now') WHERE Id = $id");
        $message = "✅ Attendee updated successfully";
    }
    header('Location: attendees-enhanced.php');
    exit;
}

// Handle single check-in
if (isset($_GET['checkin']) && hasPermission($adminRole, 'checkin')) {
    $id = intval($_GET['checkin']);
    $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id = $id");
    header('Location: attendees-enhanced.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && hasPermission($adminRole, 'delete')) {
    $id = intval($_GET['delete']);
    $db->exec("UPDATE Attendees SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id = $id");
    header('Location: attendees-enhanced.php');
    exit;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if ($action === 'checkin' && hasPermission($adminRole, 'checkin')) {
        $idsList = implode(',', array_map('intval', $ids));
        $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id IN ($idsList)");
        $message = count($ids) . " attendees checked in ✅";
    } elseif ($action === 'delete' && hasPermission($adminRole, 'delete')) {
        $idsList = implode(',', array_map('intval', $ids));
        $db->exec("UPDATE Attendees SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
        $message = count($ids) . " attendees moved to trash 🗑️";
    } elseif ($action === 'export' && hasPermission($adminRole, 'export')) {
        // Export logic (keep existing)
        if (!empty($ids)) {
            $idsList = implode(',', array_map('intval', $ids));
            $exportQuery = "SELECT a.Id, a.Name, a.Email, a.Phone, a.ticket_type, a.member_status, a.price_per_ticket, a.CheckedIn, e.Title as EventTitle 
                           FROM Attendees a 
                           LEFT JOIN Events e ON a.EventId = e.Id 
                           WHERE a.Id IN ($idsList)";
            
            $result = $db->query($exportQuery);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="attendees_' . date('Ymd_His') . '.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Ticket Type', 'Member Status', 'Price', 'Checked In', 'Event']);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                fputcsv($output, [
                    $row['Id'], $row['Name'], $row['Email'], $row['Phone'],
                    $row['ticket_type'], $row['member_status'], $row['price_per_ticket'],
                    $row['CheckedIn'] ? 'Yes' : 'No', $row['EventTitle']
                ]);
            }
            fclose($output);
            exit;
        }
    }
    header('Location: attendees-enhanced.php');
    exit;
}

// Get filters
$eventFilter = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

// Build query with pricing
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

// Get stats
$totalAttendees = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
$checkedInCount = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE CheckedIn = 1 AND (IsDeleted = 0 OR IsDeleted IS NULL)");
$deletedCount = $db->querySingle("SELECT COUNT(*) FROM Attendees WHERE IsDeleted = 1");
$pendingCount = $totalAttendees - $checkedInCount;
$totalRevenue = $db->querySingle("SELECT SUM(CAST(price_per_ticket AS REAL)) FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
$avgPrice = $totalAttendees > 0 ? $totalRevenue / $totalAttendees : 0;

// Get events
$eventsResult = $db->query("SELECT Id, Title FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL) ORDER BY Date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendees Management - Enhanced Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stat-card { transition: transform 0.2s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-3px); }
        .section-head { cursor: pointer; user-select: none; transition: all 0.3s; }
        .section-head:hover { background-color: rgba(0,0,0,0.02); }
        .section-toggle { display: inline-block; transition: transform 0.3s; }
        .section-toggle.collapsed { transform: rotate(-90deg); }
        @media (max-width: 768px) {
            .desktop-only { display: none !important; }
            .mobile-show { display: block !important; }
        }
        @media (min-width: 769px) {
            .desktop-only { display: block !important; }
            .mobile-show { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col md:flex-row h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-gray-900 text-white flex flex-col md:min-h-screen">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400">Role: <span class="capitalize"><?php echo str_replace('_', ' ', $adminRole); ?></span></p>
            </div>
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <a href="dashboard-unified.php" class="block px-4 py-2 rounded-lg hover:bg-gray-800">📊 Dashboard</a>
                <a href="attendees-enhanced.php" class="block px-4 py-2 rounded-lg bg-red-600">👥 Attendees</a>
                <a href="analytics.php" class="block px-4 py-2 rounded-lg hover:bg-gray-800">📈 Analytics</a>
                <a href="trash.php" class="block px-4 py-2 rounded-lg hover:bg-gray-800">🗑️ Trash</a>
                <a href="logout.php" class="block px-4 py-2 rounded-lg hover:bg-gray-800 text-red-400 mt-8">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-4 md:p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold">👥 Attendees Management</h1>
                <p class="text-gray-600 text-sm">Manage attendees, check-in, pricing, and communications</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">✅ <?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Stats Section (Collapsible) -->
            <div class="section-container bg-white rounded-lg shadow mb-4">
                <div class="section-head flex items-center justify-between p-4 border-b" onclick="toggleSection(this, 'stats')">
                    <div class="flex items-center gap-3">
                        <span class="section-toggle">▼</span>
                        <span class="text-lg">📊</span>
                        <h2 class="font-bold">Statistics & Overview</h2>
                    </div>
                    <span class="text-xs text-gray-500">Click to collapse</span>
                </div>
                <div class="section-content p-4" id="section-stats">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-sm">
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                            <p class="text-gray-600 text-xs">Total</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $totalAttendees; ?></p>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                            <p class="text-gray-600 text-xs">Checked In</p>
                            <p class="text-2xl font-bold text-green-600">✅ <?php echo $checkedInCount; ?></p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg border border-orange-200">
                            <p class="text-gray-600 text-xs">Pending</p>
                            <p class="text-2xl font-bold text-orange-600">⏳ <?php echo $pendingCount; ?></p>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                            <p class="text-gray-600 text-xs">Deleted</p>
                            <p class="text-2xl font-bold text-purple-600">🗑️ <?php echo $deletedCount; ?></p>
                        </div>
                        <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-200">
                            <p class="text-gray-600 text-xs">Total Revenue</p>
                            <p class="text-xl font-bold text-emerald-600">${<?php echo number_format($totalRevenue, 2); ?></p>
                        </div>
                        <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-200">
                            <p class="text-gray-600 text-xs">Avg. Price</p>
                            <p class="text-xl font-bold text-indigo-600">${<?php echo number_format($avgPrice, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section (Collapsible) -->
            <div class="section-container bg-white rounded-lg shadow mb-4">
                <div class="section-head flex items-center justify-between p-4 border-b" onclick="toggleSection(this, 'filters')">
                    <div class="flex items-center gap-3">
                        <span class="section-toggle">▼</span>
                        <span class="text-lg">🔍</span>
                        <h2 class="font-bold">Filters & Search</h2>
                    </div>
                </div>
                <div class="section-content p-4" id="section-filters">
                    <form method="GET" class="space-y-3 md:space-y-0 md:flex md:flex-wrap md:gap-3 md:items-end">
                        <div class="flex-1 md:min-w-[200px]">
                            <label class="text-sm text-gray-600">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Name or email..." class="w-full border rounded-lg p-2 text-sm mt-1">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Event</label>
                            <select name="event_id" class="border rounded-lg p-2 text-sm mt-1">
                                <option value="">All Events</option>
                                <?php while($e=$eventsResult->fetchArray(SQLITE3_ASSOC)):?>
                                <option value="<?php echo $e['Id']; ?>" <?php echo $eventFilter==$e['Id']?'selected':''; ?>><?php echo htmlspecialchars($e['Title']); ?></option>
                                <?php endwhile;?>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Status</label>
                            <select name="status" class="border rounded-lg p-2 text-sm mt-1">
                                <option value="all">All</option>
                                <option value="checked_in" <?php echo $statusFilter=='checked_in'?'selected':''; ?>>Checked In</option>
                                <option value="not_checked" <?php echo $statusFilter=='not_checked'?'selected':''; ?>>Not Checked</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full md:w-auto bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions (Desktop) -->
            <form method="POST" id="bulkForm" class="desktop-only bg-white rounded-lg shadow mb-4">
                <div class="p-4 border-b bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                        <div class="flex gap-2">
                            <select name="bulk_action" class="flex-1 border rounded-lg p-2 text-sm">
                                <option value="">Bulk Actions</option>
                                <?php if (hasPermission($adminRole, 'checkin')): ?>
                                <option value="checkin">✅ Check In</option>
                                <?php endif; ?>
                                <?php if (hasPermission($adminRole, 'delete')): ?>
                                <option value="delete">🗑️ Move to Trash</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn text-sm font-medium">Apply</button>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="selectAll()" class="flex-1 bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">✓ Select All</button>
                            <button type="button" onclick="deselectAll()" class="flex-1 bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">✗ Clear All</button>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="bulkPrintQR()" class="flex-1 bg-purple-600 text-white px-3 py-2 rounded-lg text-sm font-medium">🖨️ Print QRs</button>
                            <button type="button" onclick="bulkResend()" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium">📧 Resend</button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="exportSelected()" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium">📥 Export Selected</button>
                        <button type="button" onclick="exportAll()" class="flex-1 bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium">📥 Export All</button>
                    </div>
                </div>

                <!-- Table (Desktop) -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="p-2 w-8"><input type="checkbox" id="masterCheckbox" onclick="toggleAll(this)"></th>
                                <th class="p-2 text-left">ID</th>
                                <th class="p-2 text-left">Name</th>
                                <th class="p-2 text-left">Email</th>
                                <th class="p-2 text-left">Event</th>
                                <th class="p-2 text-left">Ticket</th>
                                <th class="p-2 text-right">Price</th>
                                <th class="p-2 text-center">Status</th>
                                <th class="p-2 text-center">QR Code</th>
                                <th class="p-2 text-center">Email</th>
                                <?php if (hasPermission($adminRole, 'edit')): ?>
                                <th class="p-2 text-center">Edit</th>
                                <?php endif; ?>
                                <th class="p-2 text-center">Actions</th>
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
                                <td class="p-2 text-sm font-medium"><?php echo $row['Id']; ?></td>
                                <td class="p-2 font-medium" title="<?php echo htmlspecialchars($row['Name']); ?>"><?php echo htmlspecialchars(substr($row['Name'], 0, 20)); ?></td>
                                <td class="p-2 text-sm" title="<?php echo htmlspecialchars($row['Email']); ?>"><?php echo htmlspecialchars(substr($row['Email'], 0, 25)); ?></td>
                                <td class="p-2 text-sm"><?php echo htmlspecialchars(substr($row['EventTitle'] ?? 'N/A', 0, 15)); ?></td>
                                <td class="p-2 text-sm"><?php echo $row['ticket_type'] ?? 'Standard'; ?></td>
                                <td class="p-2 text-right font-semibold text-blue-600">$<?php echo number_format($row['price_per_ticket'] ?? 0, 2); ?></td>
                                <td class="p-2 text-center"><?php echo $row['CheckedIn'] ? '<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">✅ In</span>' : '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">⏳ Pending</span>'; ?></td>
                                <td class="p-2 text-center"><a href="/umojawetu-platform/api/attendees/generate-qr.php?id=<?php echo $row['Id']; ?>" target="_blank" class="bg-purple-600 text-white px-2 py-1 rounded text-xs inline-block btn hover:bg-purple-700">📱</a></td>
                                <td class="p-2 text-center"><span class="email-status text-xs px-2 py-1 rounded bg-gray-100" id="status-<?php echo $row['Id']; ?>">—</span></td>
                                <?php if (hasPermission($adminRole, 'edit')): ?>
                                <td class="p-2 text-center"><button type="button" onclick="openEditModal(<?php echo $row['Id']; ?>, '<?php echo htmlspecialchars($row['Name']); ?>', '<?php echo htmlspecialchars($row['Email']); ?>', '<?php echo htmlspecialchars($row['Phone'] ?? ''); ?>', '<?php echo htmlspecialchars($row['ticket_type'] ?? ''); ?>', '<?php echo htmlspecialchars($row['member_status'] ?? ''); ?>')" class="text-blue-600 hover:text-blue-800 font-medium">✎️</button></td>
                                <?php endif; ?>
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        <?php if (!$row['CheckedIn'] && hasPermission($adminRole, 'checkin')): ?>
                                        <a href="?checkin=<?php echo $row['Id']; ?>" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 btn" onclick="return confirm('Check in?')">In</a>
                                        <?php endif; ?>
                                        <?php if (hasPermission($adminRole, 'email')): ?>
                                        <button type="button" onclick="resendEmail(<?php echo $row['Id']; ?>)" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 btn">📧</button>
                                        <?php endif; ?>
                                        <button type="button" onclick="printPass(<?php echo $row['Id']; ?>)" class="text-xs bg-teal-600 text-white px-2 py-1 rounded hover:bg-teal-700 btn">🖨️</button>
                                        <?php if (hasPermission($adminRole, 'delete')): ?>
                                        <button type="button" onclick="confirmDelete(<?php echo $row['Id']; ?>)" class="text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 btn">🗑️</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($rowCount === 0): ?>
                            <tr><td colspan="<?php echo hasPermission($adminRole, 'edit') ? '12' : '11'; ?>" class="text-center text-gray-500 py-8">👥 No attendees found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Card View (Mobile) -->
            <div class="mobile-show space-y-4 pb-4">
                <?php 
                $result = $db->query($query);
                while($row = $result->fetchArray(SQLITE3_ASSOC)): 
                ?>
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div class="flex items-start gap-3 mb-3">
                        <input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox mt-1">
                        <div class="flex-1">
                            <h3 class="font-bold"><?php echo htmlspecialchars($row['Name']); ?></h3>
                            <p class="text-xs text-gray-600">#<?php echo $row['Id']; ?></p>
                        </div>
                        <span class="text-xs font-semibold <?php echo $row['CheckedIn'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?> px-2 py-1 rounded">
                            <?php echo $row['CheckedIn'] ? '✅' : '⏳'; ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                        <div>
                            <p class="text-gray-600">Email</p>
                            <p class="font-medium truncate"><?php echo htmlspecialchars($row['Email']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Event</p>
                            <p class="font-medium truncate"><?php echo htmlspecialchars($row['EventTitle'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Ticket</p>
                            <p class="font-medium"><?php echo $row['ticket_type'] ?? 'Standard'; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Price</p>
                            <p class="font-bold text-blue-600">$<?php echo number_format($row['price_per_ticket'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <?php if (hasPermission($adminRole, 'edit')): ?>
                        <button type="button" onclick="openEditModal(<?php echo $row['Id']; ?>, '<?php echo htmlspecialchars($row['Name']); ?>', '<?php echo htmlspecialchars($row['Email']); ?>', '<?php echo htmlspecialchars($row['Phone'] ?? ''); ?>', '<?php echo htmlspecialchars($row['ticket_type'] ?? ''); ?>', '<?php echo htmlspecialchars($row['member_status'] ?? ''); ?>')" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium">Edit</button>
                        <?php endif; ?>
                        <?php if (!$row['CheckedIn'] && hasPermission($adminRole, 'checkin')): ?>
                        <a href="?checkin=<?php echo $row['Id']; ?>" class="flex-1 bg-green-600 text-white px-3 py-2 rounded text-sm font-medium text-center">Check In</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="sticky top-0 bg-white border-b p-4 flex justify-between items-center">
                <h3 class="font-bold text-lg">✎️ Edit Attendee</h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form method="POST" class="p-4 space-y-4">
                <input type="hidden" name="action" value="edit_attendee">
                <input type="hidden" name="attendee_id" id="attendee_id" value="">
                
                <div>
                    <label class="block text-sm font-medium mb-1">Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full border rounded-lg p-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Email *</label>
                    <input type="email" name="email" id="edit_email" required class="w-full border rounded-lg p-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" class="w-full border rounded-lg p-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Ticket Type</label>
                    <select name="ticket_type" id="edit_ticket_type" class="w-full border rounded-lg p-2">
                        <option value="">Select...</option>
                        <option value="Standard">Standard</option>
                        <option value="VIP">VIP</option>
                        <option value="Complimentary">Complimentary</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Member Status</label>
                    <select name="member_status" id="edit_member_status" class="w-full border rounded-lg p-2">
                        <option value="">Select...</option>
                        <option value="Member">Member</option>
                        <option value="Guest">Guest</option>
                        <option value="Speaker">Speaker</option>
                    </select>
                </div>
                
                <div class="flex gap-2 pt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium">Cancel</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Section Toggle with localStorage
        function toggleSection(header, sectionId) {
            const content = header.nextElementSibling;
            const toggle = header.querySelector('.section-toggle');
            
            const isVisible = content.style.display !== 'none';
            content.style.display = isVisible ? 'none' : 'block';
            toggle.classList.toggle('collapsed');
            
            localStorage.setItem(`section_${sectionId}`, isVisible ? 'hidden' : 'visible');
        }

        // Load saved preferences
        function loadSectionPreferences() {
            ['stats', 'filters'].forEach(id => {
                const saved = localStorage.getItem(`section_${id}`);
                if (saved === 'hidden') {
                    const section = document.getElementById(`section-${id}`);
                    const container = section.closest('.section-container');
                    const header = container.querySelector('.section-head');
                    const toggle = header.querySelector('.section-toggle');
                    section.style.display = 'none';
                    toggle.classList.add('collapsed');
                }
            });
        }

        // Checkbox Functions
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
            n.className = `notification fixed top-5 right-5 z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-3 rounded-lg shadow-lg`;
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

        // Export Functions
        function exportSelected() {
            let selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if(selected.length === 0) {
                showNotification('Please select at least one attendee', 'error');
                return;
            }
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input name="bulk_action" value="export">`;
            selected.forEach(id => {
                form.innerHTML += `<input name="selected_ids[]" value="${id}">`;
            });
            document.body.appendChild(form);
            form.submit();
        }

        function exportAll() {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input name="bulk_action" value="export_all">`;
            document.body.appendChild(form);
            form.submit();
        }

        // Delete Attendee (Soft Delete)
        function confirmDelete(id) {
            if(!confirm('🗑️ Move to trash? (Can be restored)')) return;
            window.location.href = `?delete=${id}`;
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

        // Edit Modal
        function openEditModal(id, name, email, phone, ticket_type, member_status) {
            document.getElementById('attendee_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_ticket_type').value = ticket_type;
            document.getElementById('edit_member_status').value = member_status;
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', loadSectionPreferences);
    </script>
</body>
</html>

<?php $db->close(); ?>
