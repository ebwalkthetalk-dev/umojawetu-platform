<?php
/**
 * Enhanced Attendee Management - Helper Functions
 * Provides permissions, audit logging, and utility functions
 */

// ========================================
// PERMISSION MANAGEMENT
// ========================================

/**
 * Check if admin has specific permission
 * @param SQLite3 $db - Database connection
 * @param int $adminId - Admin user ID
 * @param string $action - Action to check (view, edit, delete, etc)
 * @return bool
 */
function hasPermission($db, $adminId, $action) {
    static $permissionCache = [];
    
    if (isset($permissionCache[$adminId][$action])) {
        return $permissionCache[$adminId][$action];
    }
    
    // Get admin role
    $result = $db->querySingle(
        "SELECT role FROM admin_users WHERE id = " . intval($adminId),
        true
    );
    
    if (!$result) {
        return false;
    }
    
    $role = $result['role'];
    
    // Define permissions by role
    $rolePermissions = [
        'super_admin' => ['view', 'edit', 'delete', 'restore', 'checkin', 'adjust_price', 'email', 'export', 'manage_admins', 'view_audit', 'settings'],
        'manager' => ['view', 'edit', 'delete', 'restore', 'checkin', 'adjust_price', 'email', 'export'],
        'operator' => ['view', 'checkin', 'email', 'export'],
        'viewer' => ['view', 'export'],
        'finance' => ['view', 'adjust_price', 'email', 'export', 'view_audit']
    ];
    
    $allowed = in_array($action, $rolePermissions[$role] ?? []);
    
    // Cache result
    if (!isset($permissionCache[$adminId])) {
        $permissionCache[$adminId] = [];
    }
    $permissionCache[$adminId][$action] = $allowed;
    
    return $allowed;
}

/**
 * Get list of actions available for a role
 * @param string $role - Admin role
 * @return array
 */
function getRoleActions($role) {
    $rolePermissions = [
        'super_admin' => ['view', 'edit', 'delete', 'restore', 'checkin', 'adjust_price', 'email', 'export', 'manage_admins', 'view_audit', 'settings'],
        'manager' => ['view', 'edit', 'delete', 'restore', 'checkin', 'adjust_price', 'email', 'export'],
        'operator' => ['view', 'checkin', 'email', 'export'],
        'viewer' => ['view', 'export'],
        'finance' => ['view', 'adjust_price', 'email', 'export', 'view_audit']
    ];
    
    return $rolePermissions[$role] ?? [];
}

/**
 * Get role label for display
 * @param string $role - Role name
 * @return string
 */
function getRoleLabel($role) {
    $labels = [
        'super_admin' => '👑 Super Admin',
        'manager' => '👔 Manager',
        'operator' => '👤 Operator',
        'viewer' => '👁️ Viewer',
        'finance' => '💰 Finance'
    ];
    
    return $labels[$role] ?? ucwords(str_replace('_', ' ', $role));
}

/**
 * Get role description
 * @param string $role - Role name
 * @return string
 */
function getRoleDescription($role) {
    $descriptions = [
        'super_admin' => 'Full system access including admin management',
        'manager' => 'Complete attendee and pricing management',
        'operator' => 'Check-in and basic communications',
        'viewer' => 'Read-only access with export capability',
        'finance' => 'Pricing and financial data management'
    ];
    
    return $descriptions[$role] ?? 'Custom role';
}

// ========================================
// AUDIT LOGGING
// ========================================

/**
 * Log admin action for audit trail
 * @param SQLite3 $db - Database connection
 * @param int $adminId - Admin user ID
 * @param string $action - Action performed
 * @param string $tableName - Table affected
 * @param int $recordId - Record ID affected
 * @param mixed $oldValue - Previous value
 * @param mixed $newValue - New value
 * @return bool
 */
function logAuditAction($db, $adminId, $action, $tableName, $recordId, $oldValue = null, $newValue = null) {
    try {
        $adminName = '';
        $result = $db->querySingle(
            "SELECT full_name FROM admin_users WHERE id = " . intval($adminId),
            true
        );
        if ($result) {
            $adminName = $result['full_name'];
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (admin_id, admin_name, action, table_name, record_id, old_value, new_value, ip_address, timestamp)
            VALUES (:admin_id, :admin_name, :action, :table_name, :record_id, :old_value, :new_value, :ip_address, datetime('now'))
        ");
        
        $stmt->bindValue(':admin_id', $adminId, SQLITE3_INTEGER);
        $stmt->bindValue(':admin_name', $adminName, SQLITE3_TEXT);
        $stmt->bindValue(':action', $action, SQLITE3_TEXT);
        $stmt->bindValue(':table_name', $tableName, SQLITE3_TEXT);
        $stmt->bindValue(':record_id', $recordId, SQLITE3_INTEGER);
        $stmt->bindValue(':old_value', is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue, SQLITE3_TEXT);
        $stmt->bindValue(':new_value', is_array($newValue) ? json_encode($newValue) : (string)$newValue, SQLITE3_TEXT);
        $stmt->bindValue(':ip_address', $ip, SQLITE3_TEXT);
        
        return $stmt->execute() ? true : false;
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log price adjustment with detailed information
 * @param SQLite3 $db - Database connection
 * @param int $attendeeId - Attendee ID
 * @param float $oldPrice - Previous price
 * @param float $newPrice - New price
 * @param string $adjustType - Type of adjustment (set, increase, percent)
 * @param int $adjustedBy - Admin ID who made adjustment
 * @param string $reason - Reason for adjustment
 * @return bool
 */
function logPriceAdjustment($db, $attendeeId, $oldPrice, $newPrice, $adjustType, $adjustedBy, $reason = '') {
    try {
        $stmt = $db->prepare("
            INSERT INTO price_history (attendee_id, old_price, new_price, adjustment_type, adjusted_by, reason, adjusted_at)
            VALUES (:attendee_id, :old_price, :new_price, :adjustment_type, :adjusted_by, :reason, datetime('now'))
        ");
        
        $stmt->bindValue(':attendee_id', $attendeeId, SQLITE3_INTEGER);
        $stmt->bindValue(':old_price', $oldPrice, SQLITE3_FLOAT);
        $stmt->bindValue(':new_price', $newPrice, SQLITE3_FLOAT);
        $stmt->bindValue(':adjustment_type', $adjustType, SQLITE3_TEXT);
        $stmt->bindValue(':adjusted_by', $adjustedBy, SQLITE3_INTEGER);
        $stmt->bindValue(':reason', $reason, SQLITE3_TEXT);
        
        return $stmt->execute() ? true : false;
    } catch (Exception $e) {
        error_log("Price history logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit logs with filtering
 * @param SQLite3 $db - Database connection
 * @param array $filters - Filter options (action, table_name, admin_id, start_date, end_date)
 * @param int $limit - Number of records to return
 * @return array
 */
function getAuditLogs($db, $filters = [], $limit = 100) {
    $query = "SELECT * FROM audit_logs WHERE 1=1";
    
    if (!empty($filters['action'])) {
        $query .= " AND action = '" . SQLite3::escapeString($filters['action']) . "'";
    }
    if (!empty($filters['table_name'])) {
        $query .= " AND table_name = '" . SQLite3::escapeString($filters['table_name']) . "'";
    }
    if (!empty($filters['admin_id'])) {
        $query .= " AND admin_id = " . intval($filters['admin_id']);
    }
    if (!empty($filters['start_date'])) {
        $query .= " AND DATE(timestamp) >= '" . SQLite3::escapeString($filters['start_date']) . "'";
    }
    if (!empty($filters['end_date'])) {
        $query .= " AND DATE(timestamp) <= '" . SQLite3::escapeString($filters['end_date']) . "'";
    }
    
    $query .= " ORDER BY timestamp DESC LIMIT " . intval($limit);
    
    $result = $db->query($query);
    $logs = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $logs[] = $row;
    }
    
    return $logs;
}

// ========================================
// PRICE MANAGEMENT
// ========================================

/**
 * Adjust ticket price for attendees
 * @param SQLite3 $db - Database connection
 * @param array $attendeeIds - Array of attendee IDs
 * @param float $value - Price value or adjustment amount
 * @param string $type - Adjustment type (set, increase, percent)
 * @param int $adminId - Admin making the adjustment
 * @return array - Result with success status and message
 */
function adjustAttendeePrice($db, $attendeeIds, $value, $type = 'set', $adminId = null) {
    if (empty($attendeeIds) || $value < 0) {
        return ['success' => false, 'error' => 'Invalid input'];
    }
    
    try {
        $updatedCount = 0;
        $idsList = implode(',', array_map('intval', $attendeeIds));
        
        // Get existing prices for audit
        $existing = $db->query("SELECT Id, price_per_ticket FROM Attendees WHERE Id IN ($idsList)");
        $priceMap = [];
        while ($row = $existing->fetchArray(SQLITE3_ASSOC)) {
            $priceMap[$row['Id']] = $row['price_per_ticket'];
        }
        
        // Perform adjustment
        switch ($type) {
            case 'set':
                $db->exec("UPDATE Attendees SET price_per_ticket = $value, last_edited_by = " . intval($adminId) . ", last_edited_at = datetime('now') WHERE Id IN ($idsList)");
                $newPrice = $value;
                break;
            
            case 'increase':
                $db->exec("UPDATE Attendees SET price_per_ticket = price_per_ticket + $value, last_edited_by = " . intval($adminId) . ", last_edited_at = datetime('now') WHERE Id IN ($idsList)");
                break;
            
            case 'percent':
                $multiplier = 1 + ($value / 100);
                $db->exec("UPDATE Attendees SET price_per_ticket = ROUND(price_per_ticket * $multiplier, 2), last_edited_by = " . intval($adminId) . ", last_edited_at = datetime('now') WHERE Id IN ($idsList)");
                break;
            
            default:
                return ['success' => false, 'error' => 'Invalid adjustment type'];
        }
        
        // Log price changes
        if ($adminId) {
            foreach ($priceMap as $attendeeId => $oldPrice) {
                logPriceAdjustment($db, $attendeeId, $oldPrice, 
                    ($type === 'set') ? $value : ($oldPrice + $value), 
                    $type, $adminId);
                logAuditAction($db, $adminId, 'price_adjusted', 'Attendees', $attendeeId, 
                    ['price' => $oldPrice], ['price' => ($type === 'set') ? $value : ($oldPrice + $value)]);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Price adjusted for ' . count($attendeeIds) . ' attendees',
            'count' => count($attendeeIds)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get total revenue for attendees
 * @param SQLite3 $db - Database connection
 * @param array $filters - Filter options (event_id, checkin_status, date_range)
 * @return array - Total and average price
 */
function calculateRevenue($db, $filters = []) {
    $query = "SELECT COUNT(*) as count, SUM(price_per_ticket) as total FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL)";
    
    if (isset($filters['event_id'])) {
        $query .= " AND EventId = " . intval($filters['event_id']);
    }
    if (isset($filters['checkin_status']) && $filters['checkin_status'] === 'checked_in') {
        $query .= " AND CheckedIn = 1";
    }
    if (isset($filters['checkin_status']) && $filters['checkin_status'] === 'pending') {
        $query .= " AND (CheckedIn = 0 OR CheckedIn IS NULL)";
    }
    
    $result = $db->querySingle($query, true);
    
    return [
        'total_attendees' => $result['count'] ?? 0,
        'total_revenue' => $result['total'] ?? 0,
        'average_price' => ($result['count'] > 0) ? ($result['total'] / $result['count']) : 0
    ];
}

/**
 * Get price statistics
 * @param SQLite3 $db - Database connection
 * @return array - Price statistics
 */
function getPriceStatistics($db) {
    $result = $db->querySingle(
        "SELECT 
            COUNT(*) as total,
            MIN(price_per_ticket) as min_price,
            MAX(price_per_ticket) as max_price,
            AVG(price_per_ticket) as avg_price,
            SUM(price_per_ticket) as total_price
        FROM Attendees 
        WHERE (IsDeleted = 0 OR IsDeleted IS NULL)",
        true
    );
    
    return [
        'total_attendees' => $result['total'],
        'min_price' => $result['min_price'],
        'max_price' => $result['max_price'],
        'avg_price' => $result['avg_price'],
        'total_revenue' => $result['total_price']
    ];
}

// ========================================
// EDIT OPERATIONS
// ========================================

/**
 * Edit attendee information
 * @param SQLite3 $db - Database connection
 * @param int $attendeeId - Attendee ID
 * @param array $updates - Fields to update (name, email, phone, etc)
 * @param int $adminId - Admin making the edit
 * @return array - Result with success status
 */
function editAttendee($db, $attendeeId, $updates, $adminId = null) {
    try {
        $allowedFields = ['Name', 'Email', 'Phone', 'ticket_type', 'member_status'];
        $setClause = [];
        
        // Get original data for audit
        $original = $db->querySingle(
            "SELECT * FROM Attendees WHERE Id = " . intval($attendeeId),
            true
        );
        
        // Validate and build update clause
        foreach ($updates as $field => $value) {
            if (!in_array($field, $allowedFields)) {
                continue;
            }
            $setClause[] = "$field = '" . SQLite3::escapeString($value) . "'";
        }
        
        if (empty($setClause)) {
            return ['success' => false, 'error' => 'No valid fields to update'];
        }
        
        // Update database
        $setClause[] = "last_edited_by = " . intval($adminId);
        $setClause[] = "last_edited_at = datetime('now')";
        
        $db->exec("UPDATE Attendees SET " . implode(', ', $setClause) . " WHERE Id = " . intval($attendeeId));
        
        // Log changes
        if ($adminId) {
            logAuditAction($db, $adminId, 'attendee_edited', 'Attendees', $attendeeId, $original, $updates);
        }
        
        return ['success' => true, 'message' => 'Attendee updated successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ========================================
// UI HELPERS
// ========================================

/**
 * Format price for display
 * @param float $price - Price value
 * @param string $currency - Currency symbol
 * @return string
 */
function formatPrice($price, $currency = '$') {
    return $currency . number_format(floatval($price), 2);
}

/**
 * Get badge HTML for status
 * @param string $status - Status name
 * @return string - HTML badge
 */
function getStatusBadge($status) {
    $badges = [
        'checked_in' => '<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">✅ Checked In</span>',
        'pending' => '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">⏳ Pending</span>',
        'deleted' => '<span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">🗑️ Deleted</span>',
    ];
    
    return $badges[$status] ?? '<span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">Unknown</span>';
}

/**
 * Get role badge HTML
 * @param string $role - Role name
 * @return string - HTML badge
 */
function getRoleBadge($role) {
    $badges = [
        'super_admin' => '<span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">👑 Super Admin</span>',
        'manager' => '<span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">👔 Manager</span>',
        'operator' => '<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">👤 Operator</span>',
        'viewer' => '<span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">👁️ Viewer</span>',
        'finance' => '<span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">💰 Finance</span>',
    ];
    
    return $badges[$role] ?? '<span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">' . ucwords(str_replace('_', ' ', $role)) . '</span>';
}

?>
