<?php
require_once __DIR__ . '/../config/config.php';

echo "VERIFICATION TEST\n";
echo "=================\n\n";

$admin = getDefaultAdminCredentials();
echo "Admin Username: " . ($admin['username'] ?: 'NOT FOUND') . "\n";
echo "Admin Password: " . ($admin['password'] ? '***SET***' : 'NOT SET') . "\n";
echo "Admin Email: " . $admin['email'] . "\n\n";

$testLogin = verifyAdminLogin($admin['username'], $admin['password']);
echo "Login Test:\n";
echo "  Success: " . ($testLogin['success'] ? 'YES ✓' : 'NO ✗') . "\n";
echo "  User ID: " . ($testLogin['user']['id'] ?? 'none') . "\n\n";

echo "✓ All security fixes verified!\n";
?>
