# 🗑️ Soft Delete Implementation Guide

**Date:** April 13, 2026  
**Platform:** Umojawetu Platform  
**Feature:** Soft Delete with Restore & Permanent Delete

---

## Overview

Soft delete allows you to:
- ✅ Delete records without permanently removing them
- ✅ Recover deleted items anytime from trash
- ✅ Permanently delete when needed (cannot be undone)
- ✅ Manage deletions with bulk or individual delete options

**Key Difference:**
- **Soft Delete:** Marks records as deleted (reversible) ↩️
- **Hard Delete:** Permanently removes from database (irreversible) 🗑️

---

## How It Works

### Database Structure

Each table supporting soft delete has two columns:

```sql
IsDeleted INTEGER DEFAULT 0    -- 0 = Active, 1 = Deleted
DeletedAt DATETIME             -- When deleted (NULL if active)
```

### Query Pattern

```php
-- Show only active items (default)
SELECT * FROM Attendees WHERE (IsDeleted = 0 OR IsDeleted IS NULL)

-- Show only deleted items (trash)
SELECT * FROM Attendees WHERE IsDeleted = 1

-- Show all items (including deleted)
SELECT * FROM Attendees
```

---

## Using the Soft Delete System

### 1. Soft Delete Records

Delete individual or bulk records:

```php
// Soft delete single record
softDeleteRecords($db, 'Attendees', [42]);

// Soft delete multiple records
softDeleteRecords($db, 'Attendees', [42, 43, 44]);
```

### 2. Restore Records

Recover deleted records from trash:

```php
// Restore single record
restoreRecords($db, 'Attendees', [42]);

// Restore multiple records
restoreRecords($db, 'Attendees', [42, 43, 44]);
```

### 3. Permanent Delete

Completely remove records (cannot be undone):

```php
// Permanently delete single record
permanentlyDeleteRecords($db, 'Attendees', [42]);

// Permanently delete multiple records
permanentlyDeleteRecords($db, 'Attendees', [42, 43, 44]);
```

### 4. View Deleted Records

```php
// Get deleted records with pagination
$deleted = getDeletedRecords($db, 'Attendees', 50, 0);

// Get count of deleted records
$count = getDeletedRecordsCount($db, 'Attendees');
```

---

## Admin Interface

### Individual Delete

Click the **🗑️ Delete** button on any record to soft-delete it.

```
Attendee Record
[✅ In] [📧] [👁️] [🖨️] [🗑️ Delete]
         ^    ^    ^     ^
      Email Preview Print  Delete
```

### Bulk Delete

1. Select multiple records with checkboxes
2. Click **"Bulk Delete"** button
3. Confirm the action
4. Records moved to trash

### Restore from Trash

1. Click **🗑️ Trash** in admin sidebar
2. Select table (Attendees, Events, Pledges, Requests)
3. Select items to restore
4. Click **↩️ Restore Selected**

### Permanent Delete

⚠️ **Careful:** This cannot be undone!

1. Go to Trash
2. Select items
3. Click **🗑️ Permanently Delete**
4. Confirm (twice to be sure)
5. Items removed forever

---

## Supported Tables

Currently soft delete is implemented for:

| Table | Individual Delete | Bulk Delete | Restore | Trash View |
|-------|------------------|------------|---------|-----------|
| Attendees | ✅ | ✅ | ✅ | ✅ |
| Events | ✅ | ✅ | ✅ | ✅ |
| pledges | ✅ | ✅ | ✅ | ✅ |
| Requests | ✅ | ✅ | ✅ | ✅ |
| Bookings | 🔄 Partial | 🔄 Partial | 🔄 Partial | 🔄 Partial |
| admin_users | 🔄 Partial | 🔄 Partial | 🔄 Partial | 🔄 Partial |

---

## Key Features

### 1. Safe Deletion
- Records remain in database
- Hidden from normal views
- Can be recovered anytime

### 2. Audit Trail
- DeletedAt timestamp shows when deleted
- Track which records were deleted
- Understand data changes over time

### 3. Bulk Operations
- Delete many records at once
- Restore many records at once
- Permanently delete many at once

### 4. Trash Management
- Dedicated trash page at `/admin/trash.php`
- View deleted items by type
- Restore or permanently delete

### 5. Individual Actions
- Delete single record with confirmation
- One-click restore from trash
- Detailed audit information

---

## File Locations

| File | Purpose |
|------|---------|
| `config/soft-delete.php` | Helper functions |
| `admin/trash.php` | Trash management interface |
| `admin/attendees.php` | Updated with soft delete |
| `admin/events.php` | Updated with soft delete |
| `admin/pledges.php` | Updated with soft delete |

---

## API Functions Reference

### softDeleteRecords()
Marks records as deleted

```php
$result = softDeleteRecords($db, 'Attendees', [1, 2, 3]);

// Returns
[
    'success' => true,
    'count' => 3,
    'message' => '3 records moved to trash'
]
```

### restoreRecords()
Recovers deleted records

```php
$result = restoreRecords($db, 'Attendees', [1, 2, 3]);

// Returns
[
    'success' => true,
    'count' => 3,
    'message' => '3 records restored'
]
```

### permanentlyDeleteRecords()
⚠️ Permanently removes records

```php
$result = permanentlyDeleteRecords($db, 'Attendees', [1, 2, 3]);

// Returns
[
    'success' => true,
    'count' => 3,
    'message' => '3 records permanently deleted'
]
```

### getDeletedRecords()
Fetches deleted records

```php
$records = getDeletedRecords($db, 'Attendees', 50, 0);
// 50 records per page, page 0
```

### getDeletedRecordsCount()
Count of deleted records

```php
$count = getDeletedRecordsCount($db, 'Attendees');
// Returns: integer
```

### ensureSoftDeleteColumns()
Adds IsDeleted and DeletedAt columns if missing

```php
ensureSoftDeleteColumns($db, 'Attendees');
// Safe to call multiple times
```

---

## Best Practices

### ✅ DO

- Use soft delete for regular cleanup
- Review items before permanent deletion
- Check trash periodically for accidental deletes
- Keep audit trail (DeletedAt timestamps)
- Use bulk delete for large cleanups

### ❌ DON'T

- Permanently delete without review
- Delete system records (admin users)
- Force hard delete without soft delete first
- Ignore trash management
- Delete during active operations

---

## Security Considerations

1. **Input Validation:**
   - All user inputs sanitized
   - IDs converted to integers (SQL injection prevention)
   - Table names whitelisted

2. **Permissions:**
   - Only admin can delete
   - Session validation required
   - Delete actions logged (when implemented)

3. **Data Safety:**
   - Soft delete preserves data integrity
   - No data loss until permanent deletion
   - Database backups capture all states

---

## Troubleshooting

### Records still visible after delete
**Solution:** Check if table has IsDeleted column
```php
ensureSoftDeleteColumns($db, 'Attendees');
```

### Can't restore records
**Solution:** Verify DeletedAt timestamps exist
```php
SELECT * FROM Attendees WHERE IsDeleted = 1;
```

### Permanent delete failed
**Solution:** Check foreign key constraints
```php
// Might need to delete related records first
```

---

## Future Enhancements

Planned features:

- [ ] Audit logging (who deleted what, when)
- [ ] Bulk restore from trash (select all)
- [ ] Auto-purge old deleted items (after 90 days)
- [ ] Delete reason field
- [ ] Deletion comments/notes
- [ ] Restore history/undo stack
- [ ] Email notifications on deletion
- [ ] Deletion approval workflow

---

## Testing

### Test Soft Delete

```bash
# Visit attendees page
http://localhost/umojawetu-platform/admin/attendees.php

# Click delete button on an item
# Item should disappear from list

# Go to trash
http://localhost/umojawetu-platform/admin/trash.php

# Item should appear in trash
# Click restore
# Item should reappear in attendees list
```

### Test Permanent Delete

```bash
# In trash, click permanent delete
# Item disappears even from trash
# Cannot be recovered
```

---

## Summary

Soft delete provides a **safe, reversible way** to remove records while maintaining data integrity and allowing recovery. Use soft delete for regular operations and permanent delete only when absolutely certain.

**Remember:** Soft delete = Safe, Permanent delete = Forever! 🗑️

---

**For complete API reference, see:** `config/soft-delete.php`  
**For admin interface, visit:** `admin/trash.php`
