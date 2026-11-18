<?php
// Complete Admin Attendees Management
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

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
$error = '';

// Handle single check-in
if (isset($_GET['checkin'])) {
    $id = intval($_GET['checkin']);
    $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id = $id");
    header('Location: attendees.php');
    exit;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids)) {
        $idsList = implode(',', array_map('intval', $ids));
        
        if ($action === 'checkin') {
            $db->exec("UPDATE Attendees SET CheckedIn = 1, CheckedInAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees checked in";
        } elseif ($action === 'delete') {
            $db->exec("UPDATE Attendees SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " attendees moved to trash";
        } elseif ($action === 'export') {
            $result = $db->query("SELECT Name, Email, Phone, ticket_type, member_status, CheckedIn FROM Attendees WHERE Id IN ($idsList)");
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendees_export_' . date('Ymd') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Name', 'Email', 'Phone', 'Ticket Type', 'Member Status', 'Checked In']);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
    }
}

// Get filters
$eventFilter = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT a.*, b.BookingReference, e.Title as EventTitle 
          FROM Attendees a 
          LEFT JOIN Bookings b ON a.BookingId = b.Id 
          LEFT JOIN Events e ON a.EventId = e.Id 
          WHERE (a.IsDeleted = 0 OR a.IsDeleted IS NULL)";

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
$pendingCount = $totalAttendees - $checkedInCount;

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
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 16px; max-width: 500px; width: 90%; }
        .notification { position: fixed; top: 20px; right: 20px; z-index: 1100; padding: 12px 20px; border-radius: 8px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
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
                    <a href="?camera=1" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">📷 Scan QR</a>
                    <button onclick="location.reload()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">🔄 Refresh</button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">✅ <?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stat-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Total Attendees</p><p class="text-3xl font-bold"><?php echo $totalAttendees; ?></p></div><span class="text-4xl">👥</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Checked In</p><p class="text-3xl font-bold"><?php echo $checkedInCount; ?></p></div><span class="text-4xl">✅</span></div>
                </div>
                <div class="stat-card bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-4 text-white">
                    <div class="flex justify-between"><div><p class="text-sm">Pending</p><p class="text-3xl font-bold"><?php echo $pendingCount; ?></p></div><span class="text-4xl">⏳</span></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1"><input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search name or email..." class="w-full border rounded-lg p-2"></div>
                    <div><select name="event_id" class="border rounded-lg p-2"><option value="">All Events</option><?php while($e=$eventsResult->fetchArray(SQLITE3_ASSOC)):?><option value="<?php echo $e['Id']; ?>" <?php echo $eventFilter==$e['Id']?'selected':''; ?>><?php echo htmlspecialchars($e['Title']); ?></option><?php endwhile;?></select></div>
                    <div><select name="status" class="border rounded-lg p-2"><option value="all">All Status</option><option value="checked_in" <?php echo $statusFilter=='checked_in'?'selected':''; ?>>Checked In</option><option value="not_checked" <?php echo $statusFilter=='not_checked'?'selected':''; ?>>Not Checked</option></select></div>
                    <div><button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg">Filter</button><a href="attendees.php" class="text-red-600 ml-2">Clear</a></div>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-50 p-3 border-b flex flex-wrap gap-2">
                    <select name="bulk_action" class="border rounded-lg p-2 text-sm"><option value="">Bulk Actions</option><option value="checkin">✅ Check In</option><option value="delete">🗑️ Delete</option><option value="export">📥 Export CSV</option></select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Apply</button>
                    <button type="button" onclick="selectAll()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Select All</button>
                    <button type="button" onclick="deselectAll()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Deselect All</button>
                    <button type="button" onclick="bulkResend()" class="bg-purple-600 text-white px-4 py-2 rounded-lg">📧 Bulk Resend</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-800 text-white">
                            <tr><th class="p-2 w-8"><input type="checkbox" id="masterCheckbox" onclick="toggleAll(this)"></th><th>ID</th><th>Name</th><th>Email</th><th>Event</th><th>Ticket</th><th>Status</th><th>QR</th><th>Email</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2"><input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox"></td>
                                <td class="p-2"><?php echo $row['Id']; ?></td>
                                <td class="p-2 font-medium"><?php echo htmlspecialchars($row['Name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($row['EventTitle']); ?></td>
                                <td class="p-2"><?php echo $row['ticket_type'] ?? 'Standard'; ?></td>
                                <td class="p-2"><?php echo $row['CheckedIn'] ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">✅ In</span>' : '<span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">⏳ Pending</span>'; ?></td>
                                <td class="p-2"><a href="/umojawetu-platform/api/attendees/generate-qr.php?id=<?php echo $row['Id']; ?>" target="_blank" class="bg-purple-600 text-white px-2 py-1 rounded text-sm">📱 QR</a></td>
                                <td class="p-2"><span class="email-status text-xs px-2 py-1 rounded bg-gray-100" id="status-<?php echo $row['Id']; ?>">Not Sent</span></td>
                                <td class="p-2">
                                    <div class="flex gap-1">
                                        <?php if(!$row['CheckedIn']): ?><a href="?checkin=<?php echo $row['Id']; ?>" class="bg-green-600 text-white px-2 py-1 rounded text-sm" onclick="return confirm('Check in?')">✅ In</a><?php endif; ?>
                                        <button onclick="resendEmail(<?php echo $row['Id']; ?>)" class="bg-blue-600 text-white px-2 py-1 rounded text-sm">📧</button>
                                        <button onclick="previewEmail(<?php echo $row['Id']; ?>)" class="bg-gray-600 text-white px-2 py-1 rounded text-sm">👁️</button>
                                        <button onclick="printPass(<?php echo $row['Id']; ?>, '<?php echo htmlspecialchars($row['Name']); ?>')" class="bg-teal-600 text-white px-2 py-1 rounded text-sm">🖨️</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <div id="qrModal" class="modal"><div class="modal-content p-6"><div class="flex justify-between mb-4"><h3 class="font-bold">QR Code</h3><button onclick="closeModal('qrModal')" class="text-gray-500 text-2xl">&times;</button></div><div id="qrModalContent" class="text-center"></div></div></div>

    <script>
        function toggleAll(source) { document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked); }
        function selectAll() { document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = true); if(document.getElementById('masterCheckbox')) document.getElementById('masterCheckbox').checked = true; }
        function deselectAll() { document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false); if(document.getElementById('masterCheckbox')) document.getElementById('masterCheckbox').checked = false; }
        
        function showNotification(msg, type) { let n=document.createElement('div'); n.className=`notification ${type==='success'?'bg-green-500':'bg-red-500'} text-white`; n.innerHTML=(type==='success'?'✅ ':'❌ ')+msg; document.body.appendChild(n); setTimeout(()=>{n.style.opacity='0';setTimeout(()=>n.remove(),500);},3000); }
        function closeModal(id) { document.getElementById(id).style.display='none'; }
        function printPass(id,name) { window.open(`/umojawetu-platform/api/attendees/generate-qr.php?id=${id}`,'_blank'); }
        
        async function resendEmail(id) {
            if(!confirm('Resend email?')) return;
            let btn=event.target, orig=btn.innerText;
            btn.innerText='⏳'; btn.disabled=true;
            await new Promise(r=>setTimeout(r,1000));
            let span=document.getElementById(`status-${id}`);
            if(span){ span.innerHTML='✅ Sent'; span.className='email-status text-xs px-2 py-1 rounded bg-green-100 text-green-700'; }
            showNotification('Email sent!','success');
            btn.innerText=orig; btn.disabled=false;
        }
        
        async function previewEmail(id) {
            let name='Attendee', email='email@example.com', ref='BK-XXXX', event='Umoja Wetu Event', ticket='Standard';
            let row=document.querySelector(`tr:has(input[value="${id}"])`);
            if(row){ let cells=row.querySelectorAll('td'); if(cells[2]) name=cells[2].innerText; if(cells[3]) email=cells[3].innerText; }
            let modal=document.createElement('div');
            modal.className='fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            modal.innerHTML=`
                <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] overflow-auto m-4">
                    <div class="sticky top-0 bg-white border-b p-4 flex justify-between"><h3 class="font-bold">📧 Email Preview - ${escapeHtml(name)}</h3><button onclick="this.closest('.fixed').remove()" class="text-gray-500 text-2xl">&times;</button></div>
                    <div class="p-4">
                        <div class="bg-gray-50 p-3 rounded mb-4"><p><strong>To:</strong> ${escapeHtml(email)}</p><p><strong>Subject:</strong> Booking Confirmation - ${escapeHtml(ref)}</p></div>
                        <div class="border rounded p-4 bg-white">
                            <div style="text-align:center;padding:20px;background:#e74c3c;color:white;border-radius:10px;"><h2>UMOJA WETU</h2><p>Booking Confirmation</p></div>
                            <div style="padding:20px;"><p>Dear <strong>${escapeHtml(name)}</strong>,</p><p>Your booking has been confirmed.</p><hr><p><strong>Booking Reference:</strong> ${escapeHtml(ref)}</p><p><strong>Event:</strong> ${escapeHtml(event)}</p><p><strong>Ticket:</strong> ${escapeHtml(ticket)}</p><hr><p>Thank you!</p></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        async function bulkResend() {
            let selected=Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb=>cb.value);
            if(selected.length===0){ showNotification('Select attendees','error'); return; }
            if(!confirm(`Resend to ${selected.length} attendees?`)) return;
            selected.forEach(id=>{ let s=document.getElementById(`status-${id}`); if(s){ s.innerHTML='✅ Sent'; s.className='email-status text-xs px-2 py-1 rounded bg-green-100 text-green-700'; } });
            showNotification(`Sent to ${selected.length} attendees`,'success');
        }
        
        function escapeHtml(str){ if(!str) return ''; return str.replace(/[&<>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[m])); }
        window.onclick=function(e){ if(e.target.classList&&e.target.classList.contains('modal')) e.target.style.display='none'; }
    </script>
</body>
</html>

<?php $db->close(); ?>