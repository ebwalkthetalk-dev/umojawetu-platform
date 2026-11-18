<?php
/**
 * Soft Delete Utility
 * Handles soft delete, restore, and permanent delete operations
 * 
 * Location: /config/soft-delete.php
 */

/**
 * Soft delete records
 * Marks records as deleted without removing them
 */
function softDeleteRecords($db, $table, $ids) {
    if (empty($ids)) {
        return ['success' => false, 'error' => 'No records selected'];
    }
    
    // Validate table name (prevent SQL injection)
    $allowedTables = ['Attendees', 'Events', 'pledges', 'Requests', 'admin_users', 'Bookings'];
    if (!in_array($table, $allowedTables)) {
        return ['success' => false, 'error' => 'Invalid table name'];
    }
    
    // Ensure IDs are integers
    $ids = array_map('intval', $ids);
    $idsList = implode(',', $ids);
    
    try {
        // Check if table has IsDeleted column
        $columns = getTableColumns($db, $table);
        
        if (in_array('IsDeleted', $columns) || in_array('is_deleted', $columns)) {
            $isDeletedCol = in_array('IsDeleted', $columns) ? 'IsDeleted' : 'is_deleted';
            $deletedAtCol = in_array('DeletedAt', $columns) ? 'DeletedAt' : 'deleted_at';
            
            // Add columns if missing
            try {
                if (!in_array($deletedAtCol, $columns)) {
                    $db->exec("ALTER TABLE $table ADD COLUMN $deletedAtCol DATETIME");
                }
            } catch (Exception $e) {
                // Column might already exist
            }
            
            $query = "UPDATE $table SET $isDeletedCol = 1, $deletedAtCol = datetime('now') WHERE Id IN ($idsList)";
            $db->exec($query);
            
            return ['success' => true, 'count' => count($ids), 'message' => count($ids) . ' records moved to trash'];
        } else {
            return ['success' => false, 'error' => 'Table does not support soft delete'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Restore deleted records
 */
function restoreRecords($db, $table, $ids) {
    if (empty($ids)) {
        return ['success' => false, 'error' => 'No records selected'];
    }
    
    // Validate table name
    $allowedTables = ['Attendees', 'Events', 'pledges', 'Requests', 'admin_users', 'Bookings'];
    if (!in_array($table, $allowedTables)) {
        return ['success' => false, 'error' => 'Invalid table name'];
    }
    
    // Ensure IDs are integers
    $ids = array_map('intval', $ids);
    $idsList = implode(',', $ids);
    
    try {
        $columns = getTableColumns($db, $table);
        
        if (in_array('IsDeleted', $columns) || in_array('is_deleted', $columns)) {
            $isDeletedCol = in_array('IsDeleted', $columns) ? 'IsDeleted' : 'is_deleted';
            $deletedAtCol = in_array('DeletedAt', $columns) ? 'DeletedAt' : 'deleted_at';
            
            $query = "UPDATE $table SET $isDeletedCol = 0, $deletedAtCol = NULL WHERE Id IN ($idsList)";
            $db->exec($query);
            
            return ['success' => true, 'count' => count($ids), 'message' => count($ids) . ' records restored'];
        } else {
            return ['success' => false, 'error' => 'Table does not support restore'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Permanently delete records (hard delete)
 * WARNING: This is permanent and cannot be undone
 */
function permanentlyDeleteRecords($db, $table, $ids) {
    if (empty($ids)) {
        return ['success' => false, 'error' => 'No records selected'];
    }
    
    // Validate table name
    $allowedTables = ['Attendees', 'Events', 'pledges', 'Requests', 'admin_users', 'Bookings'];
    if (!in_array($table, $allowedTables)) {
        return ['success' => false, 'error' => 'Invalid table name'];
    }
    
    // Ensure IDs are integers
    $ids = array_map('intval', $ids);
    $idsList = implode(',', $ids);
    
    try {
        $query = "DELETE FROM $table WHERE Id IN ($idsList)";
        $db->exec($query);
        
        return ['success' => true, 'count' => count($ids), 'message' => count($ids) . ' records permanently deleted'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get deleted records from a table
 */
function getDeletedRecords($db, $table, $limit = 50, $offset = 0) {
    // Validate table name
    $allowedTables = ['Attendees', 'Events', 'pledges', 'Requests', 'admin_users', 'Bookings'];
    if (!in_array($table, $allowedTables)) {
        return null;
    }
    
    try {
        $columns = getTableColumns($db, $table);
        $isDeletedCol = in_array('IsDeleted', $columns) ? 'IsDeleted' : 'is_deleted';
        
        $query = "SELECT * FROM $table WHERE $isDeletedCol = 1 ORDER BY Id DESC LIMIT $limit OFFSET $offset";
        $result = $db->query($query);
        
        $records = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $records[] = $row;
        }
        
        return $records;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get deleted records count
 */
function getDeletedRecordsCount($db, $table) {
    // Validate table name
    $allowedTables = ['Attendees', 'Events', 'pledges', 'Requests', 'admin_users', 'Bookings'];
    if (!in_array($table, $allowedTables)) {
        return 0;
    }
    
    try {
        $columns = getTableColumns($db, $table);
        $isDeletedCol = in_array('IsDeleted', $columns) ? 'IsDeleted' : 'is_deleted';
        
        $count = $db->querySingle("SELECT COUNT(*) FROM $table WHERE $isDeletedCol = 1");
        return $count ?: 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get table columns (for soft delete support detection)
 */
function getTableColumns($db, $table) {
    $columns = [];
    try {
        $result = $db->query("PRAGMA table_info($table)");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
    } catch (Exception $e) {
        $columns = [];
    }
    return $columns;
}

/**
 * Ensure table has soft delete columns
 */
function ensureSoftDeleteColumns($db, $table) {
    $allowedTables = ['Attendees', 'Events', 'pledges', 'Requests', 'admin_users', 'Bookings'];
    if (!in_array($table, $allowedTables)) {
        return false;
    }
    
    try {
        $columns = getTableColumns($db, $table);
        
        // Add IsDeleted column if missing
        if (!in_array('IsDeleted', $columns) && !in_array('is_deleted', $columns)) {
            @$db->exec("ALTER TABLE $table ADD COLUMN IsDeleted INTEGER DEFAULT 0");
        }
        
        // Add DeletedAt column if missing
        if (!in_array('DeletedAt', $columns) && !in_array('deleted_at', $columns)) {
            @$db->exec("ALTER TABLE $table ADD COLUMN DeletedAt DATETIME");
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

?>
