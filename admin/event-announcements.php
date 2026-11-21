<?php
// Admin Event Announcements Management Dashboard
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';
$error = '';

// Ensure table exists
$tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='EventAnnouncements'");
if (!$tableCheck->fetchArray()) {
    $error = 'EventAnnouncements table not found. Please run: /api/events/create-announcements-table.php';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_announcement') {
        $eventId = intval($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'update';
        $priority = intval($_POST['priority'] ?? 3);
        $status = $_POST['status'] ?? 'draft';
        $scheduledDate = $_POST['scheduled_date'] ?? null;
        
        if (empty($title) || empty($content)) {
            $error = 'Title and content are required';
        } else {
            $publishedAt = ($status === 'published') ? ($scheduledDate ? "'" . date('Y-m-d H:i:s', strtotime($scheduledDate)) . "'" : 'datetime("now")') : 'NULL';
            
            $stmt = $db->prepare("
                INSERT INTO EventAnnouncements (EventId, Title, Content, AnnouncementType, Priority, Status, PublishedAt, CreatedAt, UpdatedAt)
                VALUES (:event_id, :title, :content, :type, :priority, :status, " . ($publishedAt === 'NULL' ? 'NULL' : ($publishedAt === 'datetime("now")' ? 'datetime("now")' : $publishedAt)) . ", datetime('now'), datetime('now'))
            ");
            
            $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':content', $content, SQLITE3_TEXT);
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $message = 'âœ… Announcement created successfully';
            } else {
                $error = 'Failed to create announcement';
            }
        }
    } elseif ($action === 'update_announcement') {
        $id = intval($_POST['announcement_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'update';
        $priority = intval($_POST['priority'] ?? 3);
        $status = $_POST['status'] ?? 'draft';
        $scheduledDate = $_POST['scheduled_date'] ?? null;
        
        if (empty($title) || empty($content)) {
            $error = 'Title and content are required';
        } else {
            $publishedAt = '';
            if ($status === 'published') {
                $publishedAt = $scheduledDate ? "'" . date('Y-m-d H:i:s', strtotime($scheduledDate)) . "'" : 'datetime("now")';
            } else {
                $publishedAt = 'NULL';
            }
            
            $query = "UPDATE EventAnnouncements SET Title = :title, Content = :content, AnnouncementType = :type, Priority = :priority, Status = :status, PublishedAt = " . ($publishedAt === 'NULL' ? 'NULL' : ($publishedAt === 'datetime("now")' ? 'datetime("now")' : $publishedAt)) . ", UpdatedAt = datetime('now') WHERE Id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':content', $content, SQLITE3_TEXT);
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $message = 'âœ… Announcement updated successfully';
            } else {
                $error = 'Failed to update announcement';
            }
        }
    } elseif ($action === 'delete_announcement') {
        $id = intval($_POST['announcement_id'] ?? 0);
        if ($db->exec("DELETE FROM EventAnnouncements WHERE Id = $id")) {
            $message = 'âœ… Announcement deleted';
        } else {
            $error = 'Failed to delete announcement';
        }
    } elseif ($action === 'duplicate_announcement') {
        $id = intval($_POST['announcement_id'] ?? 0);
        $ann = $db->querySingle("SELECT * FROM EventAnnouncements WHERE Id = $id", true);
        
        if ($ann) {
            $newTitle = $ann['Title'] . ' (Copy)';
            $stmt = $db->prepare("
                INSERT INTO EventAnnouncements (EventId, Title, Content, AnnouncementType, Priority, Status, CreatedAt, UpdatedAt)
                VALUES (:event_id, :title, :content, :type, :priority, 'draft', datetime('now'), datetime('now'))
            ");
            
            $stmt->bindValue(':event_id', $ann['EventId'], SQLITE3_INTEGER);
            $stmt->bindValue(':title', $newTitle, SQLITE3_TEXT);
            $stmt->bindValue(':content', $ann['Content'], SQLITE3_TEXT);
            $stmt->bindValue(':type', $ann['AnnouncementType'], SQLITE3_TEXT);
            $stmt->bindValue(':priority', $ann['Priority'], SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $message = 'âœ… Announcement duplicated';
            } else {
                $error = 'Failed to duplicate announcement';
            }
        }
    }
}

// Handle GET actions
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($db->exec("DELETE FROM EventAnnouncements WHERE Id = $id")) {
        header('Location: event-announcements.php');
        exit;
    }
}

// Get filters
$eventFilter = isset($_GET['event']) ? intval($_GET['event']) : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT a.*, e.Title as EventTitle FROM EventAnnouncements a LEFT JOIN Events e ON a.EventId = e.Id WHERE 1=1";

if ($eventFilter) {
    $query .= " AND a.EventId = $eventFilter";
}

if ($statusFilter !== 'all') {
    $query .= " AND a.Status = '" . SQLite3::escapeString($statusFilter) . "'";
}

$query .= " ORDER BY a.Status DESC, a.Priority DESC, a.PublishedAt DESC NULLS LAST";

$result = $db->query($query);

// Get events for dropdown
$eventsResult = $db->query("SELECT Id, Title FROM Events WHERE (IsDeleted = 0 OR IsDeleted IS NULL) ORDER BY Date DESC");

// Get stats
$totalAnnouncements = $db->querySingle("SELECT COUNT(*) FROM EventAnnouncements");
$publishedCount = $db->querySingle("SELECT COUNT(*) FROM EventAnnouncements WHERE Status = 'published'");
$draftCount = $db->querySingle("SELECT COUNT(*) FROM EventAnnouncements WHERE Status = 'draft'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Announcements - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.0/classic/ckeditor.js"></script>
    <style>
        .priority-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .priority-1 { background: #dbeafe; color: #1e40af; }
        .priority-2 { background: #d1fae5; color: #065f46; }
        .priority-3 { background: #fef3c7; color: #92400e; }
        .priority-4 { background: #fed7aa; color: #92400e; }
        .priority-5 { background: #fee2e2; color: #7f1d1d; }
        .type-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .type-update { background: #e0e7ff; color: #3730a3; }
        .type-reminder { background: #fce7f3; color: #831843; }
        .type-alert { background: #fecaca; color: #991b1b; }
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
                <a href="dashboard-unified.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">ðŸ“Š Dashboard</a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">ðŸ“… Events</a>
                <a href="event-announcements.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">ðŸ“¢ Announcements</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">ðŸ‘¥ Attendees</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">ðŸšª Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold">ðŸ“¢ Event Announcements</h1>
                    <p class="text-gray-600">Manage event updates, reminders, and alerts</p>
                </div>
                <button onclick="openCreateModal()" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 font-bold">+ New Announcement</button>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4">âœ… <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">âŒ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-gray-600 text-sm">Total Announcements</p>
                    <p class="text-3xl font-bold"><?php echo $totalAnnouncements; ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-gray-600 text-sm">Published</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $publishedCount; ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-gray-600 text-sm">Draft</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $draftCount; ?></p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-2">Filter by Event</label>
                        <select name="event" class="w-full border rounded-lg p-2">
                            <option value="">All Events</option>
                            <?php 
                            $eventsResult->reset();
                            while($e = $eventsResult->fetchArray(SQLITE3_ASSOC)):
                            ?>
                            <option value="<?php echo $e['Id']; ?>" <?php echo $eventFilter == $e['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['Title']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-2">Filter by Status</label>
                        <select name="status" class="w-full border rounded-lg p-2">
                            <option value="all">All Status</option>
                            <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">ðŸ” Filter</button>
                    <a href="event-announcements.php" class="text-gray-600 hover:underline">Reset</a>
                </form>
            </div>

            <!-- Announcements List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="p-4 text-left">Title</th>
                            <th class="p-4 text-left">Event</th>
                            <th class="p-4 text-left">Type</th>
                            <th class="p-4 text-left">Priority</th>
                            <th class="p-4 text-left">Status</th>
                            <th class="p-4 text-left">Published</th>
                            <th class="p-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="announcementsList">
                        <?php 
                        $rowCount = 0;
                        while($row = $result->fetchArray(SQLITE3_ASSOC)):
                        $rowCount++;
                        $typeBadge = 'type-' . strtolower($row['AnnouncementType']);
                        $priorityBadge = 'priority-' . $row['Priority'];
                        $statusColor = $row['Status'] === 'published' ? 'text-green-600' : 'text-yellow-600';
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-4">
                                <p class="font-medium"><?php echo htmlspecialchars(substr($row['Title'], 0, 40)); ?></p>
                                <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars(substr(strip_tags($row['Content']), 0, 80)); ?>...</p>
                            </td>
                            <td class="p-4 text-sm"><?php echo htmlspecialchars($row['EventTitle'] ?? 'General'); ?></td>
                            <td class="p-4">
                                <span class="type-badge <?php echo $typeBadge; ?>">
                                    <?php echo ucfirst($row['AnnouncementType']); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="priority-badge <?php echo $priorityBadge; ?>">
                                    <?php echo str_repeat('â­', $row['Priority']); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="<?php echo $statusColor; ?> font-medium">
                                    <?php echo ucfirst($row['Status']); ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm">
                                <?php 
                                if ($row['PublishedAt']) {
                                    echo date('M d, Y g:i A', strtotime($row['PublishedAt']));
                                } else {
                                    echo '<span class="text-gray-400">Not published</span>';
                                }
                                ?>
                            </td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <button onclick="openEditModal(<?php echo $row['Id']; ?>)" class="text-blue-600 hover:underline text-sm">âœï¸ Edit</button>
                                    <button onclick="duplicateAnnouncement(<?php echo $row['Id']; ?>)" class="text-purple-600 hover:underline text-sm">ðŸ“‹ Duplicate</button>
                                    <button onclick="if(confirm('Delete this announcement?')) location.href='?delete=<?php echo $row['Id']; ?>'" class="text-red-600 hover:underline text-sm">ðŸ—‘ï¸ Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($rowCount === 0): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">
                                ðŸ“­ No announcements yet. Create one to get started!
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="announcementModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-auto">
            <div class="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
                <h2 id="modalTitle" class="text-2xl font-bold">New Announcement</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>

            <form id="announcementForm" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create_announcement">
                <input type="hidden" name="announcement_id" id="announcementId">

                <div>
                    <label class="block text-sm font-medium mb-2">Event *</label>
                    <select name="event_id" id="eventSelect" class="w-full border rounded-lg p-3" required>
                        <option value="">-- Select Event --</option>
                        <?php 
                        $eventsResult->reset();
                        while($e = $eventsResult->fetchArray(SQLITE3_ASSOC)):
                        ?>
                        <option value="<?php echo $e['Id']; ?>">
                            <?php echo htmlspecialchars($e['Title']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Title *</label>
                    <input type="text" name="title" id="titleInput" required maxlength="200" placeholder="e.g., Venue Changed Announcement" class="w-full border rounded-lg p-3">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Content *</label>
                    <textarea name="content" id="contentEditor" required class="w-full border rounded-lg p-3 h-40"></textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Type</label>
                        <select name="type" id="typeSelect" class="w-full border rounded-lg p-3">
                            <option value="update">ðŸ“ Update</option>
                            <option value="reminder">â° Reminder</option>
                            <option value="alert">ðŸš¨ Alert</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Priority</label>
                        <select name="priority" id="prioritySelect" class="w-full border rounded-lg p-3">
                            <option value="1">â­ Low</option>
                            <option value="2">â­â­ Normal</option>
                            <option value="3" selected>â­â­â­ Medium</option>
                            <option value="4">â­â­â­â­ High</option>
                            <option value="5">â­â­â­â­â­ Critical</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Status</label>
                        <select name="status" id="statusSelect" class="w-full border rounded-lg p-3">
                            <option value="draft">ðŸ“„ Draft</option>
                            <option value="published">âœ… Published</option>
                        </select>
                    </div>
                </div>

                <div id="scheduledDateSection" class="hidden">
                    <label class="block text-sm font-medium mb-2">Schedule Publication</label>
                    <input type="datetime-local" name="scheduled_date" id="scheduledDate" class="w-full border rounded-lg p-3">
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700">Save Announcement</button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg font-bold">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let ckEditor = null;

        // Initialize CKEditor
        ClassicEditor
            .create(document.getElementById('contentEditor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo']
            })
            .then(editor => {
                ckEditor = editor;
            });

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'New Announcement';
            document.getElementById('formAction').value = 'create_announcement';
            document.getElementById('announcementId').value = '';
            document.getElementById('announcementForm').reset();
            if (ckEditor) ckEditor.setData('');
            document.getElementById('announcementModal').classList.remove('hidden');
            document.getElementById('announcementModal').classList.add('flex');
        }

        function openEditModal(id) {
            // Fetch announcement data via AJAX
            fetch('/api/events/get-announcement.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const ann = data.data;
                        document.getElementById('modalTitle').textContent = 'Edit Announcement';
                        document.getElementById('formAction').value = 'update_announcement';
                        document.getElementById('announcementId').value = ann.Id;
                        document.getElementById('eventSelect').value = ann.EventId;
                        document.getElementById('titleInput').value = ann.Title;
                        if (ckEditor) ckEditor.setData(ann.Content);
                        document.getElementById('typeSelect').value = ann.AnnouncementType;
                        document.getElementById('prioritySelect').value = ann.Priority;
                        document.getElementById('statusSelect').value = ann.Status;
                        
                        document.getElementById('announcementModal').classList.remove('hidden');
                        document.getElementById('announcementModal').classList.add('flex');
                    }
                })
                .catch(err => alert('Failed to load announcement'));
        }

        function closeModal() {
            document.getElementById('announcementModal').classList.add('hidden');
            document.getElementById('announcementModal').classList.remove('flex');
        }

        function duplicateAnnouncement(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input name="action" value="duplicate_announcement"><input name="announcement_id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }

        // Show/hide scheduled date based on status
        document.addEventListener('DOMContentLoaded', () => {
            const statusSelect = document.getElementById('statusSelect');
            const scheduledSection = document.getElementById('scheduledDateSection');
            
            statusSelect.addEventListener('change', () => {
                if (statusSelect.value === 'published') {
                    scheduledSection.classList.remove('hidden');
                } else {
                    scheduledSection.classList.add('hidden');
                }
            });
        });

        // Close modal on outside click
        document.getElementById('announcementModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

<?php $db->close(); ?>
