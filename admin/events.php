<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';
$error = '';

// SAFELY add columns only if they don't exist - NO WARNINGS
$existingColumns = [];
$cols = $db->query("PRAGMA table_info(Events)");
while ($col = $cols->fetchArray(SQLITE3_ASSOC)) {
    $existingColumns[] = $col['name'];
}

// Only add if missing
if (!in_array('IsDeleted', $existingColumns)) {
    @$db->exec("ALTER TABLE Events ADD COLUMN IsDeleted INTEGER DEFAULT 0");
}
if (!in_array('DeletedAt', $existingColumns)) {
    @$db->exec("ALTER TABLE Events ADD COLUMN DeletedAt DATETIME");
}
if (!in_array('status', $existingColumns)) {
    @$db->exec("ALTER TABLE Events ADD COLUMN status TEXT DEFAULT 'draft'");
}
if (!in_array('featured', $existingColumns)) {
    @$db->exec("ALTER TABLE Events ADD COLUMN featured INTEGER DEFAULT 0");
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids)) {
        $idsList = implode(',', array_map('intval', $ids));
        
        if ($action === 'soft_delete') {
            $db->exec("UPDATE Events SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " events moved to trash";
        } elseif ($action === 'restore') {
            $db->exec("UPDATE Events SET IsDeleted = 0, DeletedAt = NULL WHERE Id IN ($idsList)");
            $message = count($ids) . " events restored";
        } elseif ($action === 'permanent_delete') {
            $db->exec("DELETE FROM Events WHERE Id IN ($idsList)");
            $message = count($ids) . " events permanently deleted";
        } elseif ($action === 'publish') {
            $db->exec("UPDATE Events SET status = 'published' WHERE Id IN ($idsList)");
            $message = count($ids) . " events published";
        } elseif ($action === 'draft') {
            $db->exec("UPDATE Events SET status = 'draft' WHERE Id IN ($idsList)");
            $message = count($ids) . " events moved to draft";
        } elseif ($action === 'feature') {
            $db->exec("UPDATE Events SET featured = 1 WHERE Id IN ($idsList)");
            $message = count($ids) . " events featured";
        } elseif ($action === 'unfeature') {
            $db->exec("UPDATE Events SET featured = 0 WHERE Id IN ($idsList)");
            $message = count($ids) . " events unfeatured";
        } elseif ($action === 'export') {
            $result = $db->query("SELECT Id, Title, Date, Location, PriceAdult, PriceChild, status, isActive FROM Events WHERE Id IN ($idsList)");
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="events_export.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Title', 'Date', 'Location', 'Adult Price', 'Child Price', 'Status', 'Active']);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
    }
}

// Handle single actions
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $db->exec("UPDATE Events SET IsDeleted = 0, DeletedAt = NULL WHERE Id = $id");
    header('Location: events.php?show=trash');
    exit;
}
if (isset($_GET['soft_delete'])) {
    $id = intval($_GET['soft_delete']);
    $db->exec("UPDATE Events SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id = $id");
    header('Location: events.php');
    exit;
}
if (isset($_GET['permanent_delete'])) {
    $id = intval($_GET['permanent_delete']);
    $db->exec("DELETE FROM Events WHERE Id = $id");
    header('Location: events.php?show=trash');
    exit;
}
if (isset($_GET['toggle_featured'])) {
    $id = intval($_GET['toggle_featured']);
    $db->exec("UPDATE Events SET featured = NOT featured WHERE Id = $id");
    header('Location: events.php');
    exit;
}
if (isset($_GET['duplicate'])) {
    $id = intval($_GET['duplicate']);
    $event = $db->querySingle("SELECT * FROM Events WHERE Id = $id", true);
    if ($event) {
        $newTitle = $event['Title'] . ' (Copy)';
        $db->exec("
            INSERT INTO Events (Title, Description, Date, Location, PriceAdult, PriceChild, PriceVIP, Capacity, FirstChildFree, isActive, visibility, status, CreatedAt)
            VALUES ('$newTitle', '{$event['Description']}', '{$event['Date']}', '{$event['Location']}', {$event['PriceAdult']}, {$event['PriceChild']}, {$event['PriceVIP']}, {$event['Capacity']}, {$event['FirstChildFree']}, 0, 'draft', 'draft', datetime('now'))
        ");
        $message = "Event duplicated successfully";
    }
}

// Get filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$featuredFilter = isset($_GET['featured']) ? $_GET['featured'] : 'all';
$showTrash = isset($_GET['show']) && $_GET['show'] === 'trash';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM Bookings WHERE EventId = e.Id) as booking_count,
          (SELECT SUM(CAST(TotalAmount AS REAL)) FROM Bookings WHERE EventId = e.Id AND PaymentStatus = 'paid') as revenue
          FROM Events e WHERE 1=1";

if (!$showTrash) {
    $query .= " AND (e.IsDeleted = 0 OR e.IsDeleted IS NULL)";
} else {
    $query .= " AND e.IsDeleted = 1";
}
if ($statusFilter !== 'all') {
    $query .= " AND e.status = '" . SQLite3::escapeString($statusFilter) . "'";
}
if ($featuredFilter === 'featured') {
    $query .= " AND e.featured = 1";
} elseif ($featuredFilter === 'not_featured') {
    $query .= " AND (e.featured = 0 OR e.featured IS NULL)";
}
if (!empty($searchQuery)) {
    $query .= " AND (e.Title LIKE '%" . SQLite3::escapeString($searchQuery) . "%' OR e.Location LIKE '%" . SQLite3::escapeString($searchQuery) . "%')";
}
$query .= " ORDER BY e.Date DESC";

$result = $db->query($query);

// Get counts for stats
$totalEvents = $db->querySingle("SELECT COUNT(*) FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL)");
$publishedEvents = $db->querySingle("SELECT COUNT(*) FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND status = 'published'");
$draftEvents = $db->querySingle("SELECT COUNT(*) FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND status = 'draft'");
$featuredEvents = $db->querySingle("SELECT COUNT(*) FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND featured = 1");
$totalAttendees = $db->querySingle("SELECT COUNT(*) FROM Attendees a JOIN Events e ON a.EventId = e.Id WHERE (e.IsDeleted = 0 OR e.IsDeleted IS NULL)");
$totalRevenue = $db->querySingle("SELECT SUM(CAST(TotalAmount AS REAL)) FROM Bookings b JOIN Events e ON b.EventId = e.Id WHERE (e.IsDeleted = 0 OR e.IsDeleted IS NULL) AND PaymentStatus = 'paid'");
$totalTrash = $db->querySingle("SELECT COUNT(*) FROM Events WHERE IsDeleted = 1");
$soldOutEvents = $db->querySingle("SELECT COUNT(*) FROM Events e WHERE (e.IsDeleted = 0 OR e.IsDeleted IS NULL) AND e.Capacity > 0 AND (SELECT COUNT(*) FROM Bookings WHERE EventId = e.Id) >= e.Capacity");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .event-row { transition: background 0.2s; }
        .event-row:hover { background: #fef2f2; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-sold_out { background: #f8d7da; color: #721c24; }
        .featured-badge { background: #fef3c7; color: #d97706; }
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
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">📅 Events</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👥 Attendees</a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💰 Requests</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto fade-in">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">📅 Events</h1>
                        <p class="text-gray-600 text-sm">Manage your events, track performance, and organize your calendar</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="?show=<?php echo $showTrash ? 'active' : 'trash'; ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                            <?php echo $showTrash ? '← Active Events' : "🗑️ Trash ($totalTrash)"; ?>
                        </a>
                        <a href="event-builder.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition shadow-md">
                            + Create New Event
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 animate-pulse">✨ <?php echo $message; ?></div>
                <?php endif; ?>

                <!-- Stats Cards Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm opacity-90">Total Events</p>
                                <p class="text-3xl font-bold"><?php echo $totalEvents; ?></p>
                            </div>
                            <span class="text-4xl opacity-80">📅</span>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm opacity-90">Published</p>
                                <p class="text-3xl font-bold"><?php echo $publishedEvents; ?></p>
                            </div>
                            <span class="text-4xl opacity-80">✅</span>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm opacity-90">Total Attendees</p>
                                <p class="text-3xl font-bold"><?php echo number_format($totalAttendees); ?></p>
                            </div>
                            <span class="text-4xl opacity-80">👥</span>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm opacity-90">Total Revenue</p>
                                <p class="text-3xl font-bold">£<?php echo number_format($totalRevenue ?? 0, 2); ?></p>
                            </div>
                            <span class="text-4xl opacity-80">💰</span>
                        </div>
                    </div>
                </div>

                <!-- Second Row Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-3 text-center">
                        <p class="text-xs text-gray-500">Draft Events</p>
                        <p class="text-xl font-bold text-gray-700"><?php echo $draftEvents; ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-3 text-center">
                        <p class="text-xs text-gray-500">Featured Events</p>
                        <p class="text-xl font-bold text-amber-600">⭐ <?php echo $featuredEvents; ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-3 text-center">
                        <p class="text-xs text-gray-500">Sold Out</p>
                        <p class="text-xl font-bold text-red-600">🔥 <?php echo $soldOutEvents; ?></p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <?php if ($showTrash): ?>
                            <input type="hidden" name="show" value="trash">
                        <?php endif; ?>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">🔍 Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Event title or location..." class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">📌 Status</label>
                            <select name="status" class="border rounded-lg p-2">
                                <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="published" <?php echo $statusFilter == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo $statusFilter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">⭐ Featured</label>
                            <select name="featured" class="border rounded-lg p-2">
                                <option value="all" <?php echo $featuredFilter == 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="featured" <?php echo $featuredFilter == 'featured' ? 'selected' : ''; ?>>Featured Only</option>
                                <option value="not_featured" <?php echo $featuredFilter == 'not_featured' ? 'selected' : ''; ?>>Not Featured</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">Apply Filters</button>
                            <a href="events.php<?php echo $showTrash ? '?show=trash' : ''; ?>" class="text-red-600 ml-2 hover:underline">Clear</a>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions Form -->
                <form method="POST" id="bulkForm" class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-gray-50 p-3 border-b flex flex-wrap gap-2 items-center">
                        <select name="bulk_action" class="border rounded-lg p-2 text-sm">
                            <option value="">Bulk Actions</option>
                            <?php if (!$showTrash): ?>
                                <option value="publish">✅ Publish</option>
                                <option value="draft">📝 Move to Draft</option>
                                <option value="feature">⭐ Feature</option>
                                <option value="unfeature">☆ Unfeature</option>
                                <option value="soft_delete">🗑️ Move to Trash</option>
                            <?php else: ?>
                                <option value="restore">↩️ Restore</option>
                                <option value="permanent_delete">💀 Permanent Delete</option>
                            <?php endif; ?>
                            <option value="export">📥 Export CSV</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">Apply</button>
                        <button type="button" onclick="selectAll()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-400 transition">Select All</button>
                        <button type="button" onclick="deselectAll()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-400 transition">Deselect All</button>
                        <?php if (!$showTrash): ?>
                            <span class="text-xs text-gray-500 ml-auto">⭐ Featured events appear on homepage</span>
                        <?php endif; ?>
                    </div>

                    <!-- Events Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-800 text-white">
                                <tr>
                                    <th class="p-3 w-10"><input type="checkbox" id="masterCheckbox" onclick="toggleAll(this)"></th>
                                    <th class="p-3">ID</th>
                                    <th class="p-3">Event</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Location</th>
                                    <th class="p-3">Tickets Sold</th>
                                    <th class="p-3">Revenue</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasRows = false;
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                                    $hasRows = true;
                                    $bookingCount = $row['booking_count'] ?? 0;
                                    $capacity = $row['Capacity'] ?? 0;
                                    $soldOut = ($capacity > 0 && $bookingCount >= $capacity);
                                    // SAFE: Use null coalescing operator to handle missing status
                                    $eventStatus = $row['status'] ?? 'draft';
                                    $statusClass = $soldOut ? 'status-sold_out' : ($eventStatus == 'published' ? 'status-published' : 'status-draft');
                                    $statusText = $soldOut ? '🔥 Sold Out' : ucfirst($eventStatus);
                                ?>
                                <tr class="event-row border-b">
                                    <td class="p-3"><input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox"></td>
                                    <td class="p-3"><?php echo $row['Id']; ?></td>
                                    <td class="p-3">
                                        <div class="font-medium"><?php echo htmlspecialchars($row['Title']); ?></div>
                                        <?php if (!empty($row['featured'])): ?>
                                            <span class="featured-badge text-xs px-2 py-0.5 rounded-full">⭐ Featured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3"><?php echo date('d/m/Y H:i', strtotime($row['Date'])); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['Location']); ?></td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold"><?php echo $bookingCount; ?></span>
                                            <?php if ($capacity > 0): ?>
                                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo min(100, ($bookingCount / $capacity) * 100); ?>%"></div>
                                                </div>
                                                <span class="text-xs text-gray-500">/<?php echo $capacity; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-3 font-semibold text-green-600">£<?php echo number_format($row['revenue'] ?? 0, 2); ?></td>
                                    <td class="p-3"><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td class="p-3">
                                        <div class="flex gap-1">
                                            <a href="event-builder.php?id=<?php echo $row['Id']; ?>" class="text-blue-600 hover:text-blue-800" title="Edit">✏️</a>
                                            <a href="?duplicate=<?php echo $row['Id']; ?>" class="text-green-600 hover:text-green-800" title="Duplicate" onclick="return confirm('Duplicate this event?')">📋</a>
                                            <a href="?toggle_featured=<?php echo $row['Id']; ?>" class="text-amber-600 hover:text-amber-800" title="<?php echo !empty($row['featured']) ? 'Unfeature' : 'Feature'; ?>">⭐</a>
                                            <?php if (!$showTrash): ?>
                                                <a href="?soft_delete=<?php echo $row['Id']; ?>" class="text-red-600 hover:text-red-800" title="Move to Trash" onclick="return confirm('Move to trash?')">🗑️</a>
                                            <?php else: ?>
                                                <a href="?restore=<?php echo $row['Id']; ?>" class="text-green-600 hover:text-green-800" title="Restore">↩️</a>
                                                <a href="?permanent_delete=<?php echo $row['Id']; ?>" class="text-red-600 hover:text-red-800" title="Permanent Delete" onclick="return confirm('Permanently delete? This cannot be undone.')">💀</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if (!$hasRows): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-gray-500 py-12">
                                        <?php if ($showTrash): ?>
                                            🗑️ No events in trash
                                        <?php else: ?>
                                            📅 No events found. <a href="event-builder.php" class="text-red-600 hover:underline">Create your first event</a>
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
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
        }
        function selectAll() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = true);
            const master = document.getElementById('masterCheckbox');
            if (master) master.checked = true;
        }
        function deselectAll() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            const master = document.getElementById('masterCheckbox');
            if (master) master.checked = false;
        }
        
        // Animate stats cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php $db->close(); ?>