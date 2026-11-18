<?php

// --- AUTH ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
// Setup Admin Security - Creates secure admin table
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

// Create secure admin table
$db->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        full_name TEXT,
        role TEXT DEFAULT 'admin',
        is_active INTEGER DEFAULT 1,
        last_login DATETIME,
        last_ip TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME
    )
");

echo "<h1>Admin Security Setup</h1>";

// Check if super admin exists
$check = $db->query("SELECT COUNT(*) as count FROM admin_users WHERE email = 'info@umojawetu.com'");
$row = $check->fetchArray(SQLITE3_ASSOC);

if ($row['count'] == 0) {
    // Create super admin with hashed password
    $email = 'info@umojawetu.com';
    $username = 'superadmin';
    $fullName = 'Super Administrator';
    $password = 'UmojaWetu2024!'; // Change this after first login
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO admin_users (email, username, password_hash, full_name, role, is_active)
        VALUES (:email, :username, :hash, :name, 'super_admin', 1)
    ");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':hash', $passwordHash, SQLITE3_TEXT);
    $stmt->bindValue(':name', $fullName, SQLITE3_TEXT);
    $stmt->execute();
    
    echo "<p style='color:green'>âœ… Super Admin created successfully!</p>";
    echo "<p><strong>Email:</strong> info@umojawetu.com</p>";
    echo "<p><strong>Password:</strong> UmojaWetu2024!</p>";
    echo "<p class='text-red-500'>âš ï¸ Please change this password after first login!</p>";
} else {
    echo "<p style='color:blue'>âœ… Super Admin already exists</p>";
}

echo "<h2>Admin Users:</h2>";
$result = $db->query("SELECT id, email, username, full_name, role, is_active, last_login FROM admin_users");
echo "<ul>";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<li>" . htmlspecialchars($row['email']) . " - Role: " . $row['role'] . " - Active: " . ($row['is_active'] ? 'Yes' : 'No') . "</li>";
}
echo "</ul>";

echo "<p><a href='/admin/login-new.php'>Go to New Secure Admin Login â†’</a></p>";

$db->close();
?>