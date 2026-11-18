<?php
/**
 * Admin Trash Manager
 * View and manage deleted records (soft-deleted items)
 * 
 * Location: /admin/trash.php
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';
$error = '';

// Ensure all tables support soft delete
ensureSoftDeleteColumns($db, 'Attendees');
ensureSoftDeleteColumns($db, 'Events');
ensureSoftDeleteColumns($db, 'pledges');
ensureSoftDeleteColumns($db, 'Requests');

// Get table filter
$table = isset($_GET['table']) ? sanitizeInput($_GET['table']) : 'Attendees';
$allowedTables = ['Attendees', 'Events', 'pledges', 'Requests'];
if (!in_array($table, $allowedTables)) {
    $table = 'Attendees';
}

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = isset($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : [];
    $action = $_POST['bulk_action'] ?? '';
    
    if (!empty($ids)) {
        if ($action === 'restore') {
            $result = restoreRecords($db, $table, $ids);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['error'];
            }
        } elseif ($action === 'permanent_delete') {
            $result = permanentlyDeleteRecords($db, $table, $ids);
            if ($result['success']) {
                $message = $result['message'] . ' (This cannot be undone)';
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get deleted records
$deletedRecords = getDeletedRecords($db, $table);
$deletedCount = getDeletedRecordsCount($db, $table);

// Get trash counts for all tables
$attendeesTrash = getDeletedRecordsCount($db, 'Attendees');
$eventsTrash = getDeletedRecordsCount($db, 'Events');
$pledgesTrash = getDeletedRecordsCount($db, 'pledges');
$requestsTrash = getDeletedRecordsCount($db, 'Requests');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Trash - <?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tab-active {
            border-bottom: 3px solid #667eea;
            color: #667eea;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🗑️ Trash</h1>
            <p class="text-gray-600">Manage deleted items. Deleted items can be restored or permanently deleted.</p>
        </div>
        
        <!-- Message Alerts -->
        <?php if ($message): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                ✗ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Trash Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <a href="?table=Attendees" class="p-4 bg-white rounded-lg shadow hover:shadow-lg transition cursor-pointer <?php echo $table === 'Attendees' ? 'ring-2 ring-blue-500' : ''; ?>">
                <div class="text-2xl font-bold text-blue-600"><?php echo $attendeesTrash; ?></div>
                <div class="text-gray-600">Attendees in Trash</div>
            </a>
            <a href="?table=Events" class="p-4 bg-white rounded-lg shadow hover:shadow-lg transition cursor-pointer <?php echo $table === 'Events' ? 'ring-2 ring-blue-500' : ''; ?>">
                <div class="text-2xl font-bold text-green-600"><?php echo $eventsTrash; ?></div>
                <div class="text-gray-600">Events in Trash</div>
            </a>
            <a href="?table=pledges" class="p-4 bg-white rounded-lg shadow hover:shadow-lg transition cursor-pointer <?php echo $table === 'pledges' ? 'ring-2 ring-blue-500' : ''; ?>">
                <div class="text-2xl font-bold text-purple-600"><?php echo $pledgesTrash; ?></div>
                <div class="text-gray-600">Pledges in Trash</div>
            </a>
            <a href="?table=Requests" class="p-4 bg-white rounded-lg shadow hover:shadow-lg transition cursor-pointer <?php echo $table === 'Requests' ? 'ring-2 ring-blue-500' : ''; ?>">
                <div class="text-2xl font-bold text-orange-600"><?php echo $requestsTrash; ?></div>
                <div class="text-gray-600">Requests in Trash</div>
            </a>
        </div>
        
        <!-- Deleted Records Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white">
                <h2 class="text-2xl font-bold">
                    <?php 
                        $labels = [
                            'Attendees' => '👥 Deleted Attendees',
                            'Events' => '📅 Deleted Events',
                            'pledges' => '💰 Deleted Pledges',
                            'Requests' => '🎫 Deleted Requests'
                        ];
                        echo $labels[$table] ?? 'Deleted Items';
                    ?>
                    (<?php echo $deletedCount; ?>)
                </h2>
            </div>
            
            <?php if ($deletedCount > 0): ?>
                <form method="POST" class="p-6">
                    <div class="mb-4 flex gap-2">
                        <button type="submit" name="bulk_action" value="restore" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition" onclick="return confirm('Restore selected items?')">
                            ↩️ Restore Selected
                        </button>
                        <button type="submit" name="bulk_action" value="permanent_delete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition" onclick="return confirm('⚠️ Permanently delete selected items? This CANNOT be undone!')">
                            🗑️ Permanently Delete
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100 border-b">
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" id="selectAll" onchange="document.querySelectorAll('input[name=\"selected_ids[]\"]').forEach(cb => cb.checked = this.checked)">
                                    </th>
                                    <?php if ($table === 'Attendees'): ?>
                                        <th class="px-4 py-3 text-left">Name</th>
                                        <th class="px-4 py-3 text-left">Email</th>
                                        <th class="px-4 py-3 text-left">Ticket Type</th>
                                    <?php elseif ($table === 'Events'): ?>
                                        <th class="px-4 py-3 text-left">Event Title</th>
                                        <th class="px-4 py-3 text-left">Date</th>
                                        <th class="px-4 py-3 text-left">Location</th>
                                    <?php elseif ($table === 'pledges'): ?>
                                        <th class="px-4 py-3 text-left">Pledger Name</th>
                                        <th class="px-4 py-3 text-left">Email</th>
                                        <th class="px-4 py-3 text-left">Amount</th>
                                    <?php elseif ($table === 'Requests'): ?>
                                        <th class="px-4 py-3 text-left">Full Name</th>
                                        <th class="px-4 py-3 text-left">Email</th>
                                        <th class="px-4 py-3 text-left">Subject</th>
                                    <?php endif; ?>
                                    <th class="px-4 py-3 text-left">Deleted</th>
                                    <th class="px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deletedRecords as $record): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="selected_ids[]" value="<?php echo $record['Id']; ?>">
                                        </td>
                                        <?php if ($table === 'Attendees'): ?>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Name']); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Email']); ?></td>
                                            <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded"><?php echo htmlspecialchars($record['ticket_type'] ?? 'N/A'); ?></span></td>
                                        <?php elseif ($table === 'Events'): ?>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Title']); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Date'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Location'] ?? 'N/A'); ?></td>
                                        <?php elseif ($table === 'pledges'): ?>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['pledger_name']); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['email']); ?></td>
                                            <td class="px-4 py-3">KES <?php echo number_format($record['amount_pledged']); ?></td>
                                        <?php elseif ($table === 'Requests'): ?>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['FullName']); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Email']); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($record['Subject']); ?></td>
                                        <?php endif; ?>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <?php if (isset($record['DeletedAt']) && $record['DeletedAt']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($record['DeletedAt'])); ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="selected_ids[]" value="<?php echo $record['Id']; ?>">
                                                <button type="submit" name="bulk_action" value="restore" class="text-green-600 hover:text-green-800 text-sm font-bold" onclick="return confirm('Restore this item?')">↩️ Restore</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div class="p-8 text-center text-gray-600">
                    <p class="text-lg">No deleted items in <?php echo htmlspecialchars($table); ?></p>
                    <p class="text-sm">All items are safe and sound! 🎉</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Help Info -->
        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-bold text-blue-900 mb-2">
                ℹ️ How Soft Delete Works
            </h3>
            <ul class="text-blue-800 text-sm space-y-1">
                <li>• <strong>Soft Delete (Trash):</strong> Items are marked as deleted and hidden from normal views. They can be restored anytime.</li>
                <li>• <strong>Restore:</strong> Brings soft-deleted items back to active status. They will appear in normal lists again.</li>
                <li>• <strong>Permanent Delete:</strong> Completely removes items from database. This action CANNOT be undone!</li>
                <li>• <strong>Safety:</strong> Always review items before permanent deletion. Soft delete is always reversible.</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-check "select all" if all items are checked
        document.querySelectorAll('input[name="selected_ids[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(document.querySelectorAll('input[name="selected_ids[]"]'))
                    .every(cb => cb.checked);
                const selectAllCheckbox = document.getElementById('selectAll');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });
    </script>
</body>
</html>
