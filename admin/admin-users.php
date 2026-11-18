<?php
// Complete Admin Users Management
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    header('Location: dashboard-unified.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$dbPath = __DIR__ . '/../storage/umoja-events-database.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../api/umoja-events-database.db';
}
$db = new SQLite3($dbPath);
$message = '';
$error = '';

// Handle Add Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_admin') {
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $fullName = trim($_POST['full_name']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (empty($email) || empty($username) || empty($password)) {
            $error = "Email, username, and password are required";
        } else {
            // Check if email exists
            $check = $db->prepare("SELECT id FROM admin_users WHERE email = :email");
            $check->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $check->execute();
            if ($result->fetchArray()) {
                $error = "Email already exists";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO admin_users (email, username, password_hash, full_name, role, is_active, created_at)
                    VALUES (:email, :user, :hash, :name, :role, 1, datetime('now'))
                ");
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $stmt->bindValue(':user', $username, SQLITE3_TEXT);
                $stmt->bindValue(':hash', $passwordHash, SQLITE3_TEXT);
                $stmt->bindValue(':name', $fullName, SQLITE3_TEXT);
                $stmt->bindValue(':role', $role, SQLITE3_TEXT);
                $stmt->execute();
                $message = "Admin user added successfully";
            }
        }
    }
    
    if ($action === 'toggle_active') {
        $id = intval($_POST['user_id']);
        if ($id != $_SESSION['admin_id']) {
            $db->exec("UPDATE admin_users SET is_active = NOT is_active WHERE id = $id");
            $message = "Admin status updated";
        } else {
            $error = "You cannot deactivate your own account";
        }
    }
    
    if ($action === 'reset_password') {
        $id = intval($_POST['user_id']);
        $newPassword = 'TempPass123!';
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->exec("UPDATE admin_users SET password_hash = '$passwordHash' WHERE id = $id");
        $message = "Password reset to: $newPassword";
    }
    
    if ($action === 'delete_admin') {
        $id = intval($_POST['user_id']);
        if ($id != $_SESSION['admin_id']) {
            $db->exec("DELETE FROM admin_users WHERE id = $id");
            $message = "Admin user deleted";
        } else {
            $error = "You cannot delete your own account";
        }
    }
}

// Get all admin users
$result = $db->query("SELECT * FROM admin_users ORDER BY id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <a href="pledges.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🤝 Pledges</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Analytics</a>
                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">👑 Admin Users</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <h1 class="text-2xl font-bold mb-6">👑 Admin Users Management</h1>
            
            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Admin Form -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">➕ Add New Admin</h2>
                <form method="POST" class="grid md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <input type="hidden" name="action" value="add_admin">
                    <div><input type="email" name="email" placeholder="Email *" required class="w-full border rounded p-2"></div>
                    <div><input type="text" name="username" placeholder="Username *" required class="w-full border rounded p-2"></div>
                    <div><input type="text" name="full_name" placeholder="Full Name" class="w-full border rounded p-2"></div>
                    <div><input type="text" name="password" value="TempPass123!" class="w-full border rounded p-2"></div>
                    <div>
                        <select name="role" class="w-full border rounded p-2">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="md:col-span-5">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Add Admin User</button>
                    </div>
                </form>
            </div>

            <!-- Admin Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Username</th>
                            <th class="p-3">Full Name</th>
                            <th class="p-3">Role</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Last Login</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3"><?php echo $row['id']; ?> <?php echo $row['id'] == $_SESSION['admin_id'] ? '👑' : ''; ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($row['full_name'] ?? '-'); ?></td>
                            <td class="p-3"><span class="px-2 py-1 rounded text-xs <?php echo $row['role'] == 'super_admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100'; ?>"><?php echo $row['role']; ?></span></td>
                            <td class="p-3"><?php echo $row['is_active'] ? '✅ Active' : '❌ Inactive'; ?></td>
                            <td class="p-3"><?php echo $row['last_login'] ? date('d/m/Y H:i', strtotime($row['last_login'])) : 'Never'; ?></td>
                            <td class="p-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="toggle_active" class="text-blue-600 hover:underline text-sm">Toggle</button>
                                </form>
                                <form method="POST" class="inline ml-2">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="reset_password" class="text-yellow-600 hover:underline text-sm" onclick="return confirm('Reset password to TempPass123!?')">Reset</button>
                                </form>
                                <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                                <form method="POST" class="inline ml-2">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="delete_admin" class="text-red-600 hover:underline text-sm" onclick="return confirm('Delete this admin?')">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php $db->close(); ?>