<?php
/**
 * Database Migration for Enhanced Attendee Management
 * Sets up pricing, audit logging, and permission system
 * 
 * Usage: Run this file once via browser or command line
 * Access: /umojawetu-platform/api/attendees/setup-enhanced.php
 */

require_once __DIR__ . '/../../config/config.php';

// --- AUTH ---
require_once __DIR__ . '/../../config/auth.php';
requireAdminAuth();
// --- /AUTH ---

$db = getDbConnection();
$isSetup = false;
$messages = [];
$errors = [];

try {
    // Check if price_per_ticket column exists
    $tableInfo = $db->querySingle("PRAGMA table_info(Attendees)", true);
    $columns = $db->query("PRAGMA table_info(Attendees)");
    $hasPrice = false;
    
    while ($col = $columns->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'price_per_ticket') {
            $hasPrice = true;
            break;
        }
    }
    
    // Add price column if missing
    if (!$hasPrice) {
        $db->exec("ALTER TABLE Attendees ADD COLUMN price_per_ticket DECIMAL(10, 2) DEFAULT 0.00");
        $messages[] = "✅ Added price_per_ticket column to Attendees table";
    }
    
    // Add audit tracking columns if missing
    $columns = $db->query("PRAGMA table_info(Attendees)");
    $hasAudit = false;
    while ($col = $columns->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'last_edited_by') {
            $hasAudit = true;
            break;
        }
    }
    
    if (!$hasAudit) {
        $db->exec("ALTER TABLE Attendees ADD COLUMN last_edited_by INTEGER");
        $db->exec("ALTER TABLE Attendees ADD COLUMN last_edited_at DATETIME");
        $messages[] = "✅ Added audit tracking columns to Attendees table";
    }
    
    // Create admin_permissions table
    $tableCheck = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_permissions'");
    
    if (!$tableCheck) {
        $db->exec("
            CREATE TABLE admin_permissions (
                id INTEGER PRIMARY KEY,
                role TEXT NOT NULL UNIQUE,
                permissions TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default role permissions
        $defaultRoles = [
            ['super_admin', 'view,edit,delete,restore,checkin,adjust_price,email,export,manage,settings,logs', 'Full system access'],
            ['manager', 'view,edit,delete,restore,checkin,adjust_price,email,export', 'Manage all attendees and pricing'],
            ['operator', 'view,checkin,email,export', 'Check-in and basic operations'],
            ['viewer', 'view,export', 'Read-only access'],
            ['finance', 'view,adjust_price,export,revenue', 'Financial and pricing management']
        ];
        
        $stmt = $db->prepare("
            INSERT INTO admin_permissions (role, permissions, description) 
            VALUES (:role, :perms, :desc)
        ");
        
        foreach ($defaultRoles as $role) {
            $stmt->bindValue(':role', $role[0], SQLITE3_TEXT);
            $stmt->bindValue(':perms', $role[1], SQLITE3_TEXT);
            $stmt->bindValue(':desc', $role[2], SQLITE3_TEXT);
            $stmt->execute();
        }
        
        $messages[] = "✅ Created admin_permissions table with default roles";
    }
    
    // Create audit_logs table
    $auditCheck = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_logs'");
    
    if (!$auditCheck) {
        $db->exec("
            CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY,
                admin_id INTEGER,
                admin_name TEXT,
                action TEXT NOT NULL,
                table_name TEXT,
                record_id INTEGER,
                old_value TEXT,
                new_value TEXT,
                ip_address TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(admin_id) REFERENCES admin_users(id)
            )
        ");
        
        $messages[] = "✅ Created audit_logs table";
    }
    
    // Create price_history table for tracking changes
    $historyCheck = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='price_history'");
    
    if (!$historyCheck) {
        $db->exec("
            CREATE TABLE price_history (
                id INTEGER PRIMARY KEY,
                attendee_id INTEGER NOT NULL,
                old_price DECIMAL(10, 2),
                new_price DECIMAL(10, 2),
                adjustment_type TEXT,
                adjusted_by INTEGER,
                adjusted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                reason TEXT,
                FOREIGN KEY(attendee_id) REFERENCES Attendees(Id),
                FOREIGN KEY(adjusted_by) REFERENCES admin_users(id)
            )
        ");
        
        $messages[] = "✅ Created price_history table for price change tracking";
    }
    
    $isSetup = true;

} catch (Exception $e) {
    $errors[] = "❌ Error: " . $e->getMessage();
    $isSetup = false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Attendees Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
                <h1 class="text-3xl font-bold mb-2">🚀 Enhanced Attendee Management Setup</h1>
                <p class="text-indigo-100">Database configuration and feature initialization</p>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <?php if ($isSetup): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-4 rounded">
                        <p class="text-green-700 font-bold mb-3">✅ Setup Completed Successfully!</p>
                        <ul class="space-y-2">
                            <?php foreach ($messages as $msg): ?>
                                <li class="text-green-600 text-sm flex items-start gap-2">
                                    <span>✓</span>
                                    <span><?php echo htmlspecialchars($msg); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-600 p-4 rounded">
                        <p class="font-bold text-blue-700 mb-2">📋 What's New:</p>
                        <ul class="space-y-1 text-sm text-blue-600">
                            <li>✅ <strong>Ticket Pricing:</strong> Track and manage individual ticket prices</li>
                            <li>✅ <strong>Role-Based Access:</strong> 5 permission levels (Super Admin, Manager, Operator, Viewer, Finance)</li>
                            <li>✅ <strong>Audit Logging:</strong> Track all admin actions with timestamps</li>
                            <li>✅ <strong>Price History:</strong> Monitor all price changes and adjustments</li>
                            <li>✅ <strong>Edit Functionality:</strong> Inline editing with quick modals</li>
                            <li>✅ <strong>Collapsible Sections:</strong> Save preferences with localStorage</li>
                            <li>✅ <strong>Mobile Responsive:</strong> Desktop table & mobile card views</li>
                        </ul>
                    </div>

                    <div class="bg-amber-50 border-l-4 border-amber-600 p-4 rounded">
                        <p class="font-bold text-amber-700 mb-2">🔐 Default Role Permissions:</p>
                        <div class="text-sm space-y-2">
                            <p><strong class="text-amber-700">Super Admin:</strong> All permissions + manage system</p>
                            <p><strong class="text-amber-700">Manager:</strong> Full attendee management including pricing</p>
                            <p><strong class="text-amber-700">Operator:</strong> Check-in, emails, and basic operations</p>
                            <p><strong class="text-amber-700">Viewer:</strong> Read-only access and exports</p>
                            <p><strong class="text-amber-700">Finance:</strong> Pricing and revenue management</p>
                        </div>
                    </div>

                    <div class="bg-purple-50 border-l-4 border-purple-600 p-4 rounded">
                        <p class="font-bold text-purple-700 mb-2">🔧 Next Steps:</p>
                        <ol class="space-y-2 text-sm text-purple-600 list-decimal list-inside">
                            <li>Access the enhanced dashboard: <code class="bg-white px-2 py-1 rounded text-xs">/admin/attendees-enhanced.php</code></li>
                            <li>Update admin user roles if needed in admin-users.php</li>
                            <li>Test different role permissions with test accounts</li>
                            <li>Configure default ticket prices in event settings</li>
                            <li>Monitor audit logs for admin activity</li>
                        </ol>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <a href="/umojawetu-platform/admin/attendees-enhanced.php" class="flex-1 bg-blue-600 text-white px-4 py-3 rounded-lg font-bold text-center hover:bg-blue-700">
                            → Go to Enhanced Dashboard
                        </a>
                        <a href="/umojawetu-platform/admin/dashboard-unified.php" class="flex-1 bg-gray-600 text-white px-4 py-3 rounded-lg font-bold text-center hover:bg-gray-700">
                            ← Back to Dashboard
                        </a>
                    </div>

                <?php else: ?>
                    <div class="bg-red-50 border-l-4 border-red-600 p-4 rounded">
                        <p class="text-red-700 font-bold mb-2">❌ Setup Failed</p>
                        <ul class="space-y-1 text-sm text-red-600">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Feature Overview -->
                <div class="bg-gray-50 rounded-lg p-4 border">
                    <p class="font-bold text-gray-700 mb-3">📊 Database Schema Additions:</p>
                    <div class="space-y-3 text-xs">
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <p class="font-mono text-gray-600"><strong>Attendees Table:</strong></p>
                            <ul class="mt-2 space-y-1 text-gray-600 ml-2">
                                <li>• price_per_ticket (DECIMAL)</li>
                                <li>• last_edited_by (INTEGER)</li>
                                <li>• last_edited_at (DATETIME)</li>
                            </ul>
                        </div>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <p class="font-mono text-gray-600"><strong>admin_permissions Table:</strong></p>
                            <ul class="mt-2 space-y-1 text-gray-600 ml-2">
                                <li>• role (TEXT, UNIQUE)</li>
                                <li>• permissions (TEXT - comma-separated)</li>
                                <li>• description (TEXT)</li>
                            </ul>
                        </div>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <p class="font-mono text-gray-600"><strong>audit_logs Table:</strong></p>
                            <ul class="mt-2 space-y-1 text-gray-600 ml-2">
                                <li>• admin_id, admin_name, action, table_name</li>
                                <li>• record_id, old_value, new_value</li>
                                <li>• ip_address, timestamp</li>
                            </ul>
                        </div>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <p class="font-mono text-gray-600"><strong>price_history Table:</strong></p>
                            <ul class="mt-2 space-y-1 text-gray-600 ml-2">
                                <li>• attendee_id, old_price, new_price</li>
                                <li>• adjustment_type, adjusted_by, reason</li>
                                <li>• adjusted_at (DATETIME)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 border-t p-4 text-center text-xs text-gray-600">
                <p>Enhanced Attendee Management System v2.0 | Last Updated: April 2026</p>
            </div>
        </div>
    </div>
</body>
</html>

<?php $db->close(); ?>
