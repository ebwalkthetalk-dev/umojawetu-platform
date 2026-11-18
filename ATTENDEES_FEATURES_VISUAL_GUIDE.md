# 👥 Attendees Management - Feature Guide

## 🎯 Main Dashboard View

```
╔═══════════════════════════════════════════════════════════════════╗
║  👥 Attendees Management                                          ║
║  📷 Scan QR  🔄 Refresh                                           ║
╠═══════════════════════════════════════════════════════════════════╣

┌─────────────────────────────────────────────────────────────────┐
│  STATS CARDS:                                                    │
│  
│  [Total Attendees: 198 👥]  [Checked In: 18 ✅]
│  [Pending: 180 ⏳]           [Deleted: 3 🗑️] ← CLICK TO VIEW TRASH
│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  FILTERS:                                                        │
│  
│  [Search.....................] [All Events] [All Status] 🔍
│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  BULK ACTIONS:                                                   │
│  
│  [Bulk Actions ▼] [Apply]  [✓ Select All] [✗ Clear]
│  [🖨️ Print QRs]  [📧 Resend]
│  [📥 Export Selected] [📥 Export All]
│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  TABLE OF ATTENDEES:                                             │
│  
│  ☐ ID │ Name              │ Email            │ Event │ Actions  │
│  ─────┼──────────────────┼──────────────────┼───────┼──────────│
│  ☐ 185│ Test User        │ test@example.com │ Umoja │ ✅ 📧 🖨️ 🗑️ │
│  ☐   2│ Attendee Test    │ atten@test.com   │ N/A   │ ✅ 📧 🖨️ 🗑️ │
│  ☐   1│ Attendee Test 2  │ atten2@test.com  │ N/A   │ ✅ 📧 🖨️ 🗑️ │
│
└─────────────────────────────────────────────────────────────────┘
```

---

## 🗑️ Trash / Deleted Records View

### How to Access:
1. Click the red **Deleted (🗑️ 3)** stat card, OR
2. Use "Show Deleted" button in filters

```
╔═══════════════════════════════════════════════════════════════════╗
║  👥 Attendees Management - TRASH VIEW                            ║
║  [Show Deleted] [← Back]                                         ║
╠═══════════════════════════════════════════════════════════════════╣

┌─────────────────────────────────────────────────────────────────┐
│  BULK ACTIONS (For Deleted Records):                             │
│  
│  [Bulk Actions ▼]      [Apply]
│  ├─ ↩️ Restore Selected
│  └─ 💀 Permanently Delete (CAREFUL!)
│  
│  [✓ Select All] [✗ Clear]
│  
│  [↩️ Restore All]  ← ONE-CLICK Restore Everything!
│  
│  [📥 Export Selected] [📥 Export All]
│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  DELETED RECORDS TABLE:                                          │
│  
│  ☐ ID │ Name          │ Email            │ Deleted │ Actions     │
│  ─────┼───────────────┼──────────────────┼─────────┼─────────────│
│  ☐ 100│ Old Attendee  │ old@example.com  │Apr 13   │ ↩️ Restore  │
│  ☐  99│ Previous User │ prev@test.com    │Apr 12   │ ↩️ 💀 Purge │
│  ☐  98│ Test Record   │ test@test.com    │Apr 13   │ ↩️ 💀 Purge │
│
└─────────────────────────────────────────────────────────────────┘
```

---

## ✨ Key Features Breakdown

### 1️⃣ Individual Restore (Per Row)

```
In TRASH VIEW, each record has:

ROW ACTIONS:
┌──────────────────┐
│ ↩️ Restore       │  ← Click to restore single record
│ 💀 Permanently   │  ← Click to permanently delete
│    Delete        │  
└──────────────────┘

What happens:
✅ Restore → Record moves back to main list (IsDeleted = 0)
💀 Delete → Record erased from database (CANNOT UNDO!)
```

### 2️⃣ Bulk Restore (Multiple)

```
STEP 1: Select records
┌──────────┐
│ ☐ ☐ ☐   │  ← Check multiple boxes
└──────────┘

STEP 2: Choose action
┌──────────────────────────────┐
│ [Bulk Actions ▼]             │
│ ├─ ↩️ Restore Selected        │  ← Select this
│ └─ 💀 Permanently Delete     │
└──────────────────────────────┘

STEP 3: Apply
┌──────────────────────────────┐
│ [Apply]                      │  ← Click
└──────────────────────────────┘

RESULT:
✅ All selected records restored!
```

### 3️⃣ One-Click Restore ALL

```
TRASH VIEW - Top Buttons:

┌───────────────────────────────────────┐
│         ↩️ Restore All                 │  ← ONE BUTTON!
└───────────────────────────────────────┘

Click ONE button → Restore ALL deleted records at once!

Confirmation dialog:
"↩️ Restore ALL deleted attendees?"
[Cancel] [OK]
```

### 4️⃣ Permanent Delete (Irreversible)

```
METHOD 1: Individual
Click 💀 button on any row
→ Confirmation: "Permanently delete? Cannot be undone!"
→ Record erased forever!

METHOD 2: Bulk
Select records → Choose "💀 Permanently Delete" → Apply
→ Confirmation dialog
→ All selected records erased forever!

⚠️ WARNING: This CANNOT be undone!
Database has NO backup of permanently deleted records.
```

---

## 📋 Button Reference

### Active Records View (Main List)

| Button | Icon | Action |
|--------|------|--------|
| Check In | ✅ | Mark as checked in |
| Email | 📧 | Send confirmation email |
| Print | 🖨️ | Print QR code |
| Delete | 🗑️ | Move to trash (soft delete) |

**Bulk Actions:**
| Action | Effect |
|--------|--------|
| Check In (bulk) | Mark multiple as checked in |
| Delete (bulk) | Move multiple to trash |
| Print QRs | Print QR codes for multiple |
| Resend | Send emails to multiple |
| Export Selected | Download CSV of selected |
| Export All | Download CSV of all visible |

---

### Trash / Deleted View

| Button | Icon | Action |
|--------|------|--------|
| Restore | ↩️ | Restore single record |
| Delete | 💀 | Permanently delete record |
| Restore All | ↩️ | Restore ALL at once |

**Bulk Actions:**
| Action | Effect |
|--------|--------|
| Restore Selected | Restore multiple from trash |
| Permanently Delete | Delete multiple from trash forever |
| Export Selected | Download CSV of deleted (selected) |
| Export All | Download CSV of all deleted |

---

## 🔐 Safety Features

### Soft Delete (Trash)
✅ Records are NOT immediately deleted  
✅ Records can be restored anytime  
✅ Records are hidden from main view (but stored in DB)  
✅ Safe, reversible action

### Hard Delete (Permanent)
⚠️ Records are COMPLETELY removed from database  
⚠️ Cannot be recovered or restored  
⚠️ Requires explicit confirmation  
⚠️ Use only when absolutely sure!

---

## 💾 CSV Export Features

### What's Included in Export

**Active Records:**
- ID, Name, Email, Phone
- Ticket Type, Member Status
- Checked In (Yes/No)
- Event Title
- Booking Reference

**Deleted Records (Extra Column!):**
- Same as above, PLUS:
- **Deleted At** (when was it deleted)

### Export Options:
- **Export Selected**: Only checked records
- **Export All**: All visible records (respects filters)

### File Format:
- Format: CSV (Excel compatible)
- Encoding: UTF-8 with BOM
- Filename: `attendees_YYYYMMDD_HHMMSS.csv`

---

## 🎯 Quick Workflows

### Workflow 1: Accidentally Deleted Someone?
```
1. Click red "Deleted" stat card
2. Search for attendee name
3. Click ↩️ next to their name
4. Done! ✅
```

### Workflow 2: Oops, Many Deletions!
```
1. View Deleted records
2. Click "✓ Select All"
3. Select "↩️ Restore Selected"
4. Click "Apply"
5. All restored! ✅
```

### Workflow 3: Clean Up Old Records
```
1. View Deleted records
2. Find old record
3. Click 💀 (Purge)
4. Confirm "Cannot be undone!"
5. Record deleted forever! 💀
```

### Workflow 4: Remove Everyone From Trash
```
1. View Deleted records
2. Click "↩️ Restore All" button
3. All gone from trash! ✅
```

---

## 🎨 Color Coding

| Color | Meaning |
|-------|---------|
| 🟢 Green | Positive action (Restore, Check In) |
| 🔵 Blue | Apply/Submit action |
| 🔴 Red | Destructive action (Delete) |
| ⚫ Black | Default button |
| 🟡 Yellow | Pending status |
| ⚪ Gray | Secondary action |

---

## 📞 Troubleshooting

### Problem: Attendee not in trash
**Solution:** Attendee may not have been deleted yet, or was already restored

### Problem: Can't find deleted attendee
**Solution:** 
- Use search bar in trash view
- Check exact date deleted
- Verify attendee wasn't permanently deleted

### Problem: Restored attendee missing
**Solution:**
- Go back to main view
- Refresh page
- Check event filters

### Problem: Accidental permanent delete
**Solution:**
- ⚠️ **Cannot be recovered!**
- Must restore from database backup
- Prevents: Always use "Move to Trash" first

---

## ✅ This Dashboard Now Supports:

- ✅ Soft delete (move to trash)
- ✅ Restore individual records
- ✅ Restore multiple records at once
- ✅ Restore ALL records with one click
- ✅ Permanent delete (irreversible)
- ✅ Bulk permanent delete
- ✅ Export deleted records
- ✅ Track when records were deleted
- ✅ Search/filter in trash
- ✅ Full audit trail via timestamps

---

**🚀 Ready to use!** Start managing your attendees with confidence!

