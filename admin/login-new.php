<?php
// Secure Admin Login with Password Hashing
session_start();
require_once __DIR__ . '/../config/config.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard-unified.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        // Connect to database
        $dbPath = __DIR__ . '/../storage/umoja-events-database.db';
        if (!file_exists($dbPath)) {
            $dbPath = __DIR__ . '/../api/umoja-events-database.db';
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            // Check if admin_users table exists, if not, create it
            $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_users'");
            if (!$tableCheck->fetchArray()) {
                // Create table
                $db->exec("
                    CREATE TABLE admin_users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        email TEXT UNIQUE NOT NULL,
                        username TEXT UNIQUE NOT NULL,
                        password_hash TEXT NOT NULL,
                        full_name TEXT,
                        role TEXT DEFAULT 'admin',
                        is_active INTEGER DEFAULT 1,
                        last_login DATETIME,
                        last_ip TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Create default super admin
                $defaultPassword = password_hash('UmojaWetu2024!', PASSWORD_DEFAULT);
                $db->exec("
                    INSERT INTO admin_users (email, username, password_hash, full_name, role)
                    VALUES ('info@umojawetu.com', 'superadmin', '$defaultPassword', 'Super Administrator', 'super_admin')
                ");
            }
            
            // Find user by email
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = :email AND is_active = 1");
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_name'] = $user['full_name'];
                
                // Update last login
                $update = $db->prepare("UPDATE admin_users SET last_login = datetime('now'), last_ip = :ip WHERE id = :id");
                $update->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '', SQLITE3_TEXT);
                $update->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $update->execute();
                
                header('Location: dashboard-unified.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
            
            $db->close();
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .input-group input:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .security-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="login-card bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white text-center">
            <div class="text-5xl mb-3">🔐</div>
            <h1 class="text-2xl font-bold">Admin Login</h1>
            <p class="text-red-100 text-sm mt-1">Secure Access Only</p>
        </div>
        
        <div class="p-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-4 text-sm">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required 
                           placeholder="admin@example.com" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500">
                </div>
                
                <div class="input-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required 
                           placeholder="••••••••" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500">
                </div>
                
                <button type="submit" class="btn-login w-full text-white py-3 rounded-lg font-semibold text-lg">
                    🔓 Login to Dashboard
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Secure password hashing enabled</p>
                <p class="text-xs mt-1">🔒 bcrypt encryption</p>
            </div>
        </div>
    </div>
    
    <div class="security-badge">
        🔒 Secure Login | Password Hash: bcrypt
    </div>
</body>
</html>