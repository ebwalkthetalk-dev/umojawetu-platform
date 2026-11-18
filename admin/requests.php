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
$cols = $db->query("PRAGMA table_info(Requests)");
while ($col = $cols->fetchArray(SQLITE3_ASSOC)) {
    $existingColumns[] = $col['name'];
}

// Only add if missing
if (!in_array('IsDeleted', $existingColumns)) {
    @$db->exec("ALTER TABLE Requests ADD COLUMN IsDeleted INTEGER DEFAULT 0");
}
if (!in_array('DeletedAt', $existingColumns)) {
    @$db->exec("ALTER TABLE Requests ADD COLUMN DeletedAt DATETIME");
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids)) {
        $idsList = implode(',', array_map('intval', $ids));
        
        if ($action === 'soft_delete') {
            $db->exec("UPDATE Requests SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " requests moved to trash";
        } elseif ($action === 'restore') {
            $db->exec("UPDATE Requests SET IsDeleted = 0, DeletedAt = NULL WHERE Id IN ($idsList)");
            $message = count($ids) . " requests restored";
        } elseif ($action === 'permanent_delete') {
            $db->exec("DELETE FROM Requests WHERE Id IN ($idsList)");
            $message = count($ids) . " requests permanently deleted";
        } elseif ($action === 'approve') {
            $db->exec("UPDATE Requests SET Status = 'approved', ResolvedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " requests approved";
        } elseif ($action === 'reject') {
            $db->exec("UPDATE Requests SET Status = 'rejected', ResolvedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " requests rejected";
        } elseif ($action === 'export') {
            $result = $db->query("SELECT RequestReference, RequestType, FullName, Email, AmountRequested, Status, SubmittedAt FROM Requests WHERE Id IN ($idsList)");
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="requests_export.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Reference', 'Type', 'Name', 'Email', 'Amount', 'Status', 'Date']);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
    }
}

// Handle single approve/reject
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $db->exec("UPDATE Requests SET Status = 'approved', ResolvedAt = datetime('now') WHERE Id = $id");
    header('Location: requests.php');
    exit;
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $db->exec("UPDATE Requests SET Status = 'rejected', ResolvedAt = datetime('now') WHERE Id = $id");
    header('Location: requests.php');
    exit;
}
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $db->exec("UPDATE Requests SET IsDeleted = 0, DeletedAt = NULL WHERE Id = $id");
    header('Location: requests.php?show=trash');
    exit;
}
if (isset($_GET['permanent_delete'])) {
    $id = intval($_GET['permanent_delete']);
    $db->exec("DELETE FROM Requests WHERE Id = $id");
    header('Location: requests.php?show=trash');
    exit;
}

// Get filters
$typeFilter = isset($_GET['type']) ? $_GET['type'] : 'all';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$showTrash = isset($_GET['show']) && $_GET['show'] === 'trash';

// Build query
$query = "SELECT * FROM Requests WHERE 1=1";
if (!$showTrash) {
    $query .= " AND (IsDeleted = 0 OR IsDeleted IS NULL)";
} else {
    $query .= " AND IsDeleted = 1";
}
if ($typeFilter !== 'all') {
    $query .= " AND RequestType = '" . SQLite3::escapeString($typeFilter) . "'";
}
if ($statusFilter !== 'all') {
    $query .= " AND Status = '" . SQLite3::escapeString($statusFilter) . "'";
}
$query .= " ORDER BY SubmittedAt DESC";

$result = $db->query($query);

// Get counts for stats
$totalPending = $db->querySingle("SELECT COUNT(*) FROM Requests WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND Status = 'pending'");
$totalApproved = $db->querySingle("SELECT COUNT(*) FROM Requests WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND Status = 'approved'");
$totalRejected = $db->querySingle("SELECT COUNT(*) FROM Requests WHERE (IsDeleted = 0 OR IsDeleted IS NULL) AND Status = 'rejected'");
$totalTrash = $db->querySingle("SELECT COUNT(*) FROM Requests WHERE IsDeleted = 1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Requests - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
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
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">💰 Requests</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">💰 Financial Assistance Requests</h1>
                    <div class="flex gap-2">
                        <a href="?show=<?php echo $showTrash ? 'active' : 'trash'; ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg">
                            <?php echo $showTrash ? '← View Active' : "🗑️ Trash ($totalTrash)"; ?>
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $totalPending; ?></p>
                        <p class="text-sm text-gray-600">Pending</p>
                    </div>
                    <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-2xl font-bold text-green-600"><?php echo $totalApproved; ?></p>
                        <p class="text-sm text-gray-600">Approved</p>
                    </div>
                    <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-2xl font-bold text-red-600"><?php echo $totalRejected; ?></p>
                        <p class="text-sm text-gray-600">Rejected</p>
                    </div>
                    <div class="stat-card bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-2xl font-bold text-gray-600"><?php echo $totalTrash; ?></p>
                        <p class="text-sm text-gray-600">In Trash</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium">Request Type</label>
                            <select name="type" class="border rounded-lg p-2">
                                <option value="all" <?php echo $typeFilter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="refund" <?php echo $typeFilter == 'refund' ? 'selected' : ''; ?>>Refund</option>
                                <option value="payment" <?php echo $typeFilter == 'payment' ? 'selected' : ''; ?>>Payment</option>
                                <option value="bereavement" <?php echo $typeFilter == 'bereavement' ? 'selected' : ''; ?>>Bereavement</option>
                                <option value="special" <?php echo $typeFilter == 'special' ? 'selected' : ''; ?>>Special</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="status" class="border rounded-lg p-2">
                                <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $statusFilter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <?php if ($showTrash): ?>
                            <input type="hidden" name="show" value="trash">
                        <?php endif; ?>
                        <div>
                            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg">Apply Filters</button>
                            <a href="requests.php<?php echo $showTrash ? '?show=trash' : ''; ?>" class="text-red-600 ml-2">Clear</a>
                        </div>
                    </div>
                </form>

                <!-- Bulk Actions Form -->
                <form method="POST" id="bulkForm">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="bg-gray-50 p-3 border-b flex flex-wrap gap-2">
                            <select name="bulk_action" class="border rounded-lg p-2">
                                <option value="">Bulk Actions</option>
                                <?php if (!$showTrash): ?>
                                    <option value="approve">✅ Approve</option>
                                    <option value="reject">❌ Reject</option>
                                    <option value="soft_delete">🗑️ Move to Trash</option>
                                <?php else: ?>
                                    <option value="restore">↩️ Restore</option>
                                    <option value="permanent_delete">💀 Permanent Delete</option>
                                <?php endif; ?>
                                <option value="export">📥 Export CSV</option>
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Apply</button>
                            <button type="button" onclick="selectAll()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Select All</button>
                            <button type="button" onclick="deselectAll()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Deselect All</button>
                        </div>

                        <table class="w-full">
                            <thead class="bg-gray-800 text-white">
                                <tr>
                                    <th class="p-3 w-10"><input type="checkbox" id="masterCheckbox" onclick="toggleAll(this)"></th>
                                    <th>Reference</th><th>Name</th><th>Email</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3"><input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox"></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['RequestReference']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['FullName']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td class="p-3"><?php echo ucfirst($row['RequestType']); ?></td>
                                    <td class="p-3">£<?php echo number_format($row['AmountRequested'] ?? 0, 2); ?></td>
                                    <td class="p-3">
                                        <?php
                                        $statusClass = match($row['Status']) {
                                            'approved' => 'text-green-600',
                                            'rejected' => 'text-red-600',
                                            default => 'text-yellow-600'
                                        };
                                        ?>
                                        <span class="<?php echo $statusClass; ?> font-medium"><?php echo ucfirst($row['Status']); ?></span>
                                    </td>
                                    <td class="p-3"><?php echo substr($row['SubmittedAt'], 0, 10); ?></td>
                                    <td class="p-3">
                                        <?php if (!$showTrash && $row['Status'] === 'pending'): ?>
                                            <a href="?approve=<?php echo $row['Id']; ?>" class="bg-green-600 text-white px-2 py-1 rounded text-sm">Approve</a>
                                            <a href="?reject=<?php echo $row['Id']; ?>" class="bg-red-600 text-white px-2 py-1 rounded text-sm ml-1">Reject</a>
                                        <?php elseif ($showTrash): ?>
                                            <a href="?restore=<?php echo $row['Id']; ?>" class="bg-blue-600 text-white px-2 py-1 rounded text-sm">Restore</a>
                                            <a href="?permanent_delete=<?php echo $row['Id']; ?>" class="bg-red-600 text-white px-2 py-1 rounded text-sm ml-1" onclick="return confirm('Permanently delete?')">Delete</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
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
            document.getElementById('masterCheckbox').checked = true;
        }
        function deselectAll() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('masterCheckbox').checked = false;
        }
    </script>
</body>
</html>

<?php $db->close(); ?>