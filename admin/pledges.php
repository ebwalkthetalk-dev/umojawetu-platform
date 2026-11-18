<?php
// Pledge Management with Soft Delete & Bulk Upload
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';
$error = '';
$imported = 0;
$failed = 0;

// Ensure soft delete columns exist
ensureSoftDeleteColumns($db, 'pledges');

// Show trash view or active
$showTrash = isset($_GET['show']) && $_GET['show'] === 'trash';

// Handle soft delete individual
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = softDeleteRecords($db, 'pledges', [$id]);
    if ($result['success']) {
        $message = '✅ ' . $result['message'];
    } else {
        $error = '❌ Error deleting pledge';
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids)) {
        $idsList = implode(',', array_map('intval', $ids));
        
        if ($action === 'email_resend') {
            // Count emails sent
            $emailCount = 0;
            foreach ($ids as $id) {
                $stmt = $db->prepare("SELECT email, pledge_reference, pledger_name, amount_pledged, payment_status, created_at FROM pledges WHERE Id = :id");
                $stmt->bindValue(':id', intval($id), SQLITE3_INTEGER);
                $result = $stmt->execute();
                $pledge = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($pledge) {
                    $statusDisplay = match($pledge['payment_status']) {
                        'paid' => '✅ Paid',
                        'pending' => '⏳ Pending',
                        default => '📋 ' . ucfirst($pledge['payment_status'])
                    };
                    
                    $emailBody = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                            .header { background: #d12828; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                            .content { background: white; padding: 20px; }
                            .pledge-details { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 15px 0; }
                            .detail-row { display: flex; justify-content: space-between; margin: 8px 0; }
                            .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
                            .button { background: #d12828; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🤝 Pledge Confirmation</h1>
                            </div>
                            <div class='content'>
                                <p>Dear <strong>" . htmlspecialchars($pledge['pledger_name']) . "</strong>,</p>
                                
                                <p>This is a reminder of your pledge commitment to Umoja Wetu. Thank you for your support!</p>
                                
                                <div class='pledge-details'>
                                    <div class='detail-row'>
                                        <span><strong>Pledge Reference:</strong></span>
                                        <span>" . htmlspecialchars($pledge['pledge_reference']) . "</span>
                                    </div>
                                    <div class='detail-row'>
                                        <span><strong>Amount Pledged:</strong></span>
                                        <span>£" . number_format($pledge['amount_pledged'], 2) . "</span>
                                    </div>
                                    <div class='detail-row'>
                                        <span><strong>Status:</strong></span>
                                        <span>" . $statusDisplay . "</span>
                                    </div>
                                    <div class='detail-row'>
                                        <span><strong>Date:</strong></span>
                                        <span>" . substr($pledge['created_at'], 0, 10) . "</span>
                                    </div>
                                </div>
                                
                                <p>If you have any questions about your pledge or would like to update your payment information, please reply to this email or contact our office.</p>
                                
                                <p style='margin-top: 30px; color: #666; font-size: 12px;'>
                                    Thank you for supporting Umoja Wetu!<br>
                                    <em>This is an automated message, please do not reply directly.</em>
                                </p>
                            </div>
                            <div class='footer'>
                                <p>&copy; 2026 Umoja Wetu. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    if (sendEmail($pledge['email'], 'Pledge Confirmation - Umoja Wetu', $emailBody)) {
                        $emailCount++;
                    }
                }
            }
            $message = '📧 ' . $emailCount . ' pledge confirmation email(s) sent successfully!';
        } elseif ($action === 'soft_delete') {
            $db->exec("UPDATE pledges SET IsDeleted = 1, DeletedAt = datetime('now') WHERE Id IN ($idsList)");
            $message = count($ids) . " pledges moved to trash";
        } elseif ($action === 'restore') {
            $db->exec("UPDATE pledges SET IsDeleted = 0, DeletedAt = NULL WHERE Id IN ($idsList)");
            $message = count($ids) . " pledges restored";
        } elseif ($action === 'permanent_delete') {
            $db->exec("DELETE FROM pledges WHERE Id IN ($idsList)");
            $message = count($ids) . " pledges permanently deleted";
        }
    }
}

// Handle restore/permanent delete from trash
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $db->exec("UPDATE pledges SET IsDeleted = 0, DeletedAt = NULL WHERE Id = $id");
    header('Location: pledges.php');
    exit;
}
if (isset($_GET['permanent_delete'])) {
    $id = intval($_GET['permanent_delete']);
    $db->exec("DELETE FROM pledges WHERE Id = $id");
    header('Location: pledges.php?show=trash');
    exit;
}

// Handle template download
if (isset($_GET['template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pledge_template.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['pledger_name', 'email', 'phone', 'is_member', 'membership_id', 'amount_pledged', 'notes']);
    fputcsv($output, ['John Doe', 'john@example.com', '07123456789', '0', '', '100', 'Test pledge']);
    fputcsv($output, ['Jane Smith', 'jane@example.com', '07123456780', '1', 'MEM001', '250', 'Member pledge']);
    fclose($output);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed. Error code: " . $file['error'];
    } elseif ($file['size'] == 0) {
        $error = "File is empty";
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xls', 'xlsx'])) {
            $error = "Please upload CSV file";
        } else {
            // Parse CSV
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle !== false) {
                $header = fgetcsv($handle);
                // Expected header
                $expectedHeader = ['pledger_name', 'email', 'phone', 'is_member', 'membership_id', 'amount_pledged', 'notes'];
                
                $db = getDbConnection();
                
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) < 6) continue;
                    
                    $pledger_name = trim($data[0]);
                    $email = trim($data[1]);
                    $phone = trim($data[2] ?? '');
                    $is_member = intval($data[3] ?? 0);
                    $membership_id = trim($data[4] ?? '');
                    $amount_pledged = floatval($data[5] ?? 0);
                    $notes = trim($data[6] ?? '');
                    
                    if (empty($pledger_name) || empty($email) || $amount_pledged <= 0) {
                        $failed++;
                        continue;
                    }
                    
                    $pledge_ref = 'PLG-BULK-' . strtoupper(uniqid()) . '-' . date('Ymd');
                    
                    $stmt = $db->prepare("
                        INSERT INTO pledges (pledge_reference, pledger_name, email, phone, is_member, membership_id, amount_pledged, amount_paid, payment_status, notes, created_at)
                        VALUES (:ref, :name, :email, :phone, :member, :mid, :amount, 0, 'pending', :notes, datetime('now'))
                    ");
                    $stmt->bindValue(':ref', $pledge_ref, SQLITE3_TEXT);
                    $stmt->bindValue(':name', $pledger_name, SQLITE3_TEXT);
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    $stmt->bindValue(':member', $is_member, SQLITE3_INTEGER);
                    $stmt->bindValue(':mid', $membership_id, SQLITE3_TEXT);
                    $stmt->bindValue(':amount', $amount_pledged, SQLITE3_FLOAT);
                    $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
                    $stmt->execute();
                    $imported++;
                }
                fclose($handle);
                $db->close();
                $message = "Successfully imported $imported pledges. Failed: $failed";
            } else {
                $error = "Could not read file";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pledges Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dropzone {
            border: 2px dashed #cbd5e1;
            transition: all 0.3s;
        }
        .dropzone.dragover {
            border-color: #e74c3c;
            background: #fef2f2;
        }
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
                <a href="pledges.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">🤝 Pledges</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Analytics</a>
                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👑 Admin Users</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <!-- Pledges Management Section -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">🤝 Pledges Management</h1>
                    <div class="flex gap-2">
                        <a href="pledges.php" class="px-4 py-2 rounded-lg bg-blue-600 text-white <?php echo !$showTrash ? 'ring-2 ring-yellow-400' : ''; ?>">📊 Active Pledges</a>
                        <?php 
                        $trashCount = $db->querySingle("SELECT COUNT(*) FROM pledges WHERE (IsDeleted = 1 OR IsDeleted IS NULL) AND IsDeleted = 1");
                        $trashCount = $trashCount ?? 0;
                        ?>
                        <a href="pledges.php?show=trash" class="px-4 py-2 rounded-lg bg-red-600 text-white <?php echo $showTrash ? 'ring-2 ring-yellow-400' : ''; ?>">🗑️ Trash<?php if ($trashCount > 0) echo " (" . $trashCount . ")"; ?></a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">✅ <?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">❌ <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Pledges Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <form method="POST" id="bulkForm">
                        <div class="bg-gray-50 p-3 border-b flex flex-wrap gap-2">
                            <select name="bulk_action" class="border rounded-lg p-2" id="bulkActionSelect">
                                <option value="">Bulk Actions</option>
                                <?php if (!$showTrash): ?>
                                    <option value="email_resend">📧 Resend Email</option>
                                    <option value="soft_delete">🗑️ Move to Trash</option>
                                <?php else: ?>
                                    <option value="restore">↩️ Restore</option>
                                    <option value="permanent_delete">💀 Permanent Delete</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700" onclick="return validateBulkAction()">Apply</button>
                        </div>

                        <table class="w-full text-sm">
                            <thead class="bg-gray-800 text-white">
                                <tr>
                                    <th class="p-3 w-10"><input type="checkbox" id="masterCheckbox" onclick="document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked)"></th>
                                    <th class="p-3 text-left">Name</th><th class="p-3 text-left">Email</th><th class="p-3 text-left">Amount</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Date</th><th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $whereClause = $showTrash 
                                    ? "WHERE IsDeleted = 1" 
                                    : "WHERE (IsDeleted = 0 OR IsDeleted IS NULL)";
                                $result = $db->query("SELECT Id, pledge_reference, pledger_name, email, amount_pledged, payment_status, created_at FROM pledges $whereClause ORDER BY created_at DESC");
                                $hasRows = false;
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                                    $hasRows = true;
                                    $statusColor = match($row['payment_status']) {
                                        'paid' => 'text-green-600',
                                        'pending' => 'text-yellow-600',
                                        default => 'text-gray-600'
                                    };
                                ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3"><input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox"></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['pledger_name']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="p-3">£<?php echo number_format($row['amount_pledged'] ?? 0, 2); ?></td>
                                    <td class="p-3"><span class="<?php echo $statusColor; ?> font-medium"><?php echo ucfirst($row['payment_status']); ?></span></td>
                                    <td class="p-3 text-gray-600"><?php echo substr($row['created_at'], 0, 10); ?></td>
                                    <td class="p-3">
                                        <div class="flex gap-1 justify-end">
                                            <?php if (!$showTrash): ?>
                                                <button type="button" onclick="resendPledgeEmail(<?php echo $row['Id']; ?>)" class="text-blue-600 hover:text-blue-800" title="Resend Email">📧</button>
                                                <a href="?delete=<?php echo $row['Id']; ?>" class="text-red-600 hover:text-red-800" title="Move to Trash" onclick="return confirm('Move to trash?')">🗑️</a>
                                            <?php else: ?>
                                                <a href="?restore=<?php echo $row['Id']; ?>" class="text-green-600 hover:text-green-800" title="Restore">↩️</a>
                                                <a href="?permanent_delete=<?php echo $row['Id']; ?>" class="text-red-600 hover:text-red-800" title="Permanent Delete" onclick="return confirm('Permanently delete? Cannot undo.')">💀</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if (!$hasRows): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-gray-500 py-12">
                                        <?php if ($showTrash): ?>
                                            🗑️ No pledges in trash
                                        <?php else: ?>
                                            🤝 No pledges yet. <a href="#upload-section" class="text-blue-600 hover:underline">Upload from CSV</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>

            <!-- Bulk Upload Section -->
            <div id="upload-section" class="max-w-2xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">📤 Bulk Upload Pledges</h2>
                </div>

                <?php if ($message && strpos($message, 'imported') !== false): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">✅ <?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error && strpos($error, 'import') !== false): ?>
                    <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4">❌ <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-bold text-blue-800 mb-2">📋 Instructions</h3>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Download the template CSV file below</li>
                        <li>Fill in your pledge data (one pledge per row)</li>
                        <li>Upload the completed CSV file</li>
                        <li>Required columns: pledger_name, email, amount_pledged</li>
                    </ul>
                </div>

                <!-- Download Template -->
                <div class="bg-white rounded-lg shadow p-6 mb-6 text-center">
                    <a href="?template=1" class="bg-green-600 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2 hover:bg-green-700">
                        📥 Download CSV Template
                    </a>
                    <p class="text-sm text-gray-500 mt-2">Contains example data - edit and re-upload</p>
                </div>

                <!-- Upload Area -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Upload CSV File</h2>
                    
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div id="dropzone" class="dropzone border-2 rounded-xl p-12 text-center cursor-pointer hover:bg-gray-50 transition">
                            <div class="text-5xl mb-3">📁</div>
                            <p class="text-gray-600">Drag & drop CSV file here</p>
                            <p class="text-gray-400 text-sm mt-1">or click to select file</p>
                            <input type="file" name="csv_file" id="fileInput" accept=".csv" class="hidden">
                            <p class="text-xs text-gray-400 mt-3">Maximum file size: 5MB</p>
                        </div>
                        
                        <div id="fileInfo" class="mt-4 hidden">
                            <div class="bg-gray-100 p-3 rounded-lg flex justify-between items-center">
                                <span id="fileName" class="font-medium"></span>
                                <span id="fileSize" class="text-sm text-gray-500"></span>
                                <button type="button" onclick="clearFile()" class="text-red-600">✖ Remove</button>
                            </div>
                        </div>
                        
                        <button type="submit" id="submitBtn" class="mt-6 w-full bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700 transition hidden">
                            📤 Upload & Import Pledges
                        </button>
                    </form>
                </div>

                <!-- Preview of existing pledges -->
                <div class="mt-6">
                    <a href="pledges.php" class="text-blue-600 hover:underline">← Back to Pledge Management</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitBtn = document.getElementById('submitBtn');
        
        dropzone.onclick = () => fileInput.click();
        
        dropzone.ondragover = (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        };
        
        dropzone.ondragleave = () => {
            dropzone.classList.remove('dragover');
        };
        
        dropzone.ondrop = (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        };
        
        fileInput.onchange = (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        };
        
        function handleFile(file) {
            if (!file.name.endsWith('.csv')) {
                alert('Please select a CSV file');
                return;
            }
            
            fileName.innerText = file.name;
            fileSize.innerText = (file.size / 1024).toFixed(2) + ' KB';
            fileInfo.classList.remove('hidden');
            submitBtn.classList.remove('hidden');
        }
        
        function clearFile() {
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            submitBtn.classList.add('hidden');
        }

        // Email Resend Functions
        async function resendPledgeEmail(pledgeId) {
            if(!confirm('📧 Resend pledge confirmation email to this pledger?')) return;
            
            try {
                const response = await fetch('/api/emails/resend-pledge-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        pledge_ids: [pledgeId],
                        action: 'resend'
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.count > 0) {
                    showNotification('✅ Email sent successfully!', 'success');
                } else {
                    showNotification('⚠️ Email sent but may not have been delivered', 'warning');
                }
            } catch (error) {
                console.error('Error sending email:', error);
                showNotification('❌ Error sending email', 'error');
            }
        }

        function showNotification(message, type = 'info') {
            const bgColor = type === 'success' ? 'bg-green-100' : type === 'error' ? 'bg-red-100' : 'bg-yellow-100';
            const textColor = type === 'success' ? 'text-green-700' : type === 'error' ? 'text-red-700' : 'text-yellow-700';
            
            const notif = document.createElement('div');
            notif.className = `${bgColor} ${textColor} p-3 rounded-lg mb-4 fixed top-4 right-4 z-50 max-w-md`;
            notif.textContent = message;
            document.body.appendChild(notif);
            
            setTimeout(() => notif.remove(), 3000);
        }

        // Bulk action validation
        function validateBulkAction() {
            const action = document.getElementById('bulkActionSelect').value;
            if (!action) {
                showNotification('❌ Please select an action', 'error');
                return false;
            }
            
            const checked = document.querySelectorAll('.row-checkbox:checked').length;
            if (checked === 0) {
                showNotification('❌ Please select at least one pledge', 'error');
                return false;
            }
            
            // Confirm destructive actions
            if (action === 'soft_delete') {
                return confirm(`🗑️ Move ${checked} pledge(s) to trash?`);
            } else if (action === 'permanent_delete') {
                return confirm(`💀 Permanently delete ${checked} pledge(s)? This cannot be undone!`);
            } else if (action === 'email_resend') {
                return confirm(`📧 Resend confirmation emails to ${checked} pledger(s)?`);
            }
            
            return true;
        }
    </script>
</body>
</html>

<?php $db->close(); ?>