# Admin Attendee Management Dashboard - Comprehensive Improvement Guide

## Executive Summary
This document outlines current state analysis, identified improvements, and implementation strategies for the attendee management dashboard. The improvements focus on clarity, efficiency, ticket pricing management, flexible layout, mobile responsiveness, and role-based access control.

---

## Part 1: Current State Analysis

### 1.1 Current Organization Structure

#### **Dashboard Layout:**
- **Sidebar Navigation** - Dark gray (w-64) with menu items
- **Top Section** - Header with title and action buttons
- **Stats Section** - 4 grid cards (1-4 columns on desktop)
  - Total Attendees
  - Checked In (✅)
  - Pending (⏳)
  - Deleted (🗑️)
- **Filter Section** - Horizontal form with dropdowns
- **Bulk Actions Bar** - Alternative UI for deleted vs. active states
- **Data Table** - Responsive with checkboxes

#### **Table Columns (Active View):**
| Column | Purpose | Issue |
|--------|---------|-------|
| Checkbox | Bulk selection | ✓ Good |
| ID | Identification | ✓ Good |
| Name | Primary info | ✓ Good |
| Email | Contact | ✓ Good |
| Event | Association | ✓ Good |
| Ticket Type | Ticket category | ✓ Limited - no price info |
| Status | Check-in state | ✓ Good |
| QR Code | Mobile check-in | ✓ Good |
| Email Status | Email tracking | ⚠️ Visual feedback only |
| Actions | Quick operations | ⚠️ Crowded (4 buttons) |

---

## Part 2: Identified Issues & Improvement Areas

### 2.1 **Missing Ticket Pricing Information**
**Current Issue:**
- No ticket price information displayed per attendee
- Impossible to see total revenue/pricing at a glance
- Cannot adjust individual ticket prices
- No visual breakdown of ticket costs

**Impact:**
- Admins cannot quickly verify pricing accuracy
- Manual lookup required for financial validation
- Difficult to track pricing discrepancies

### 2.2 **Limited Quick Edit Capabilities**
**Current Issue:**
- No inline edit for key fields
- Must use modal or separate page for modifications
- Takes multiple clicks to edit a single field

**Impact:**
- Low efficiency for quick updates
- More development overhead

### 2.3 **Table Crowding on Mobile**
**Current Issue:**
- 9+ columns don't fit on mobile screens
- Horizontal scrolling required
- Actions button overflow
- Poor UX on tablets

**Impact:**
- Mobile admins struggle with management
- Accessibility concerns

### 2.4 **Rigid Layout - No Collapsible Sections**
**Current Issue:**
- Stats cards always visible
- Filter bar always expanded
- Cannot hide sections for focus
- One-size-fits-all approach

**Impact:**
- Screen clutter for admins focusing on specific tasks
- Information overload
- Not customizable to admin workflow

### 2.5 **Role-Based Access Control Gaps**
**Current Issue:**
- Basic role check (super_admin only access)
- No granular permissions per action
- All actions available to anyone with access
- No permission levels for different admin types

**Impact:**
- Security risk (no permission granularity)
- Cannot delegate specific tasks safely
- Audit trail missing

### 2.6 **Action Button Organization**
**Current Issue:**
- 4-5 action buttons per row cramped together
- Mobile devices show wrapped buttons
- Icons only, occasionally confusing
- No clear visual hierarchy

**Impact:**
- Cognitive overload
- Accidental clicks
- Poor accessibility

---

## Part 3: Proposed Improvements

### 3.1 **Add Ticket Pricing Management**

#### **Enhancement 1: Display Price Per Ticket**
```
Layout Addition:
- Add "Price" column after "Ticket Type"
- Format: Currency with 2 decimals (e.g., $49.99)
- Align right for easy scanning
```

#### **Enhancement 2: Inline Price Adjustment**

**Option A: Dropdown with Preset Prices**
```
[Dropdown: $0 | $24.99 | $49.99 | $99.99 | Custom...]
- Predefined price tiers from event
- "Custom" opens input field
- Quick apply with checkmark
```

**Option B: Slider for Price Range**
```
[Slider: $0 ----●----- $200]
- Visual representation of price range
- Real-time value display
- Good for discount applications
```

**Option C: Input Field with Validation**
```
[$] [__49.99__] [✓]
- Direct typing for precision
- Validation (non-negative, max decimal places)
- Apply button confirms change
```

**Recommended Approach:** 
Combine **Dropdown (presets) + Input field (custom)**
- Fast for common prices
- Flexible for special cases
- Clear validation feedback

#### **Enhancement 3: Price Adjustment Modal/Panel**
```
Quick Price Editor for Selected Attendees:
┌─────────────────────────────────────┐
│ 🎫 Adjust Price for 5 Attendees     │
├─────────────────────────────────────┤
│ Apply to:                           │
│ ○ All selected (5 tickets)          │
│ ○ Individually (show list)          │
│                                     │
│ New Price: [$] [49.99]              │
│                                     │
│ Adjustment Type:                    │
│ ○ Set new price                     │
│ ○ Increase by amount: $[5.00]       │
│ ○ Increase by %: [10%]              │
│ ○ Apply discount: [EARLYBIRD]       │
│                                     │
│ [Cancel] [Apply to 5]               │
└─────────────────────────────────────┘
```

#### **Enhancement 4: Revenue Display**
```
Added Stats Section:
┌──────────────────┬──────────────────┐
│ Total Revenue    │ Avg. per Ticket  │
│ $4,950.00        │ $45.50           │
└──────────────────┴──────────────────┘
```

### 3.2 **Add Quick Edit Functionality**

#### **Implementation Strategy: Inline Edit + Modal**

**Inline Edit for Key Fields:**
```html
<!-- Editable fields with click-to-edit -->
<td class="editable-cell" data-field="member_status">
  Guest [Edit]  → Click to edit
</td>
```

**Edit Modal for Complex Fields:**
```
Fields Editable via Modal:
- Name (required)
- Email (with validation)
- Phone (formatted)
- Ticket Type (dropdown)
- Member Status (dropdown)
- Custom Fields
```

**Edit Row Implementation:**
```
Add new column: "Edit" (rightmost)
- [✎️ Edit] button
- Opens modal with all attendee fields
- Pre-filled with current data
- Validation on save
- Success notification
```

---

### 3.3 **Flexible, Collapsible Layout**

#### **Section Collapsibility:**
```
Dashboard Sections (with toggle):
┌─────────────────────────────────────┐
│ [▼] 📊 Statistics & Overview         │
│    ├─ Total Attendees: 245          │
│    ├─ Checked In: 189               │
│    ├─ Pending: 56                   │
│    ├─ Deleted: 12                   │
│    └─ Revenue: $5,432.50 (avg $22)  │
├─────────────────────────────────────┤
│ [▼] 🔍 Filters & Search             │
│    ├─ Search: [__________]          │
│    ├─ Event: [Dropdown]             │
│    └─ Status: [Dropdown]            │
├─────────────────────────────────────┤
│ [▼] ⚙️ Bulk Actions                 │
│    ├─ [Action] [Apply]              │
│    └─ [Select All] [Clear]          │
├─────────────────────────────────────┤
│ [▼] 👥 Attendees Table              │
│    └─ [10 rows]                     │
└─────────────────────────────────────┘
```

#### **Layout Persistence:**
- Store collapsed state in localStorage
- Per-user preferences via session
- Default state for new admins
- Remember user preferences

#### **Benefits:**
- Clean, focus-driven interface
- Customizable to workflow
- Reduced cognitive load
- More screen real estate for table

---

### 3.4 **Mobile-Responsive Design**

#### **Mobile Breakpoints:**

**Mobile (< 640px):**
```
- Single column layout
- Sidebar → Hamburger menu
- Stats cards → 2x2 grid
- Table → Card view (one attendee per card)
- Actions → More menu (•••)
```

**Card View for Mobile Table:**
```
┌─────────────────────────────────────┐
│ 👤 John Doe                         │
├─────────────────────────────────────┤
│ Email: john@example.com             │
│ Event: Summer Festival              │
│ Ticket: Standard ($49.99)           │
│ Status: ✅ Checked In               │
│ Phone: +254 xxx xxx xxx             │
├─────────────────────────────────────┤
│ [✓] [Edit] [✉️] [⋯]                │
└─────────────────────────────────────┘
```

**Tablet (640px - 1024px):**
```
- Sidebar → Sidebar (condensed)
- Table → Show 4-5 key columns
- Hide secondary columns (overflow hidden)
- Actions → Icon-only buttons
```

**Desktop (> 1024px):**
```
- Full layout as designed
- All columns visible
- Icon + text buttons
- Hover states
```

#### **Mobile-Friendly Table:**
```
Key columns to display on mobile:
1. Checkbox (bulk select)
2. Name (primary identifier)
3. Status (check-in visual)
4. Price (NEW - key financial info)
5. Actions (Edit, More menu)

Secondary columns (swipe to reveal or hidden):
- Email, Phone, Event, Ticket Type, etc.
```

---

### 3.5 **Role-Based Access Control**

#### **Role Hierarchy:**
```
SUPER_ADMIN (Full Access)
├─ All features enabled
├─ Manage admin users
├─ Access all reports
└─ Manage all settings

MANAGER (High Access)
├─ View all attendees ✓
├─ Check-in attendees ✓
├─ Edit attendee details ✓
├─ Adjust ticket prices ✓
├─ Send emails ✓
├─ Export data ✓
├─ Delete/Restore attendees ✓
└─ Cannot: Manage admins, Settings

OPERATOR (Limited Access)
├─ View attendees ✓
├─ Check-in attendees ✓
├─ View attendee details ✓
├─ Print materials ✓
├─ Send pre-templates ✓
└─ Cannot: Edit prices, Delete, Access reports

VIEWER (Read-Only)
├─ View attendee list ✓
├─ View details ✓
├─ Export reports ✓
└─ Cannot: Any modifications

FINANCE (Special Role)
├─ View all attendees ✓
├─ View/Edit prices ✓
├─ Access payment info ✓
├─ Export financials ✓
├─ View revenue reports ✓
└─ Cannot: Check-in, Delete attendees, Settings
```

#### **UI Adjustments per Role:**
```
SUPER_ADMIN:
- All buttons visible
- Admin management menu
- System settings
- Audit logs

MANAGER:
- All attendee management
- Email & communications
- Export/Import
- Price adjustment

OPERATOR:
- Check-in only
- QR scanning
- Basic printing
- Template emails

VIEWER:
- Reports only
- No action buttons
- Export read-only

FINANCE:
- Financial columns highlighted
- Price adjustment panel
- Revenue analytics
- Payment tracking
```

#### **Permission Matrix:**
| Action | Super Admin | Manager | Operator | Viewer | Finance |
|--------|---|---|---|---|---|
| View Attendees | ✓ | ✓ | ✓ | ✓ | ✓ |
| Edit Name/Email | ✓ | ✓ | ✗ | ✗ | ✗ |
| Check-in | ✓ | ✓ | ✓ | ✗ | ✗ |
| Adjust Price | ✓ | ✓ | ✗ | ✗ | ✓ |
| Delete/Restore | ✓ | ✓ | ✗ | ✗ | ✗ |
| Send Emails | ✓ | ✓ | ✓ | ✗ | ✗ |
| Export Data | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manage Admins | ✓ | ✗ | ✗ | ✗ | ✗ |
| View Revenue | ✓ | ✓ | ✗ | ✓ | ✓ |
| Edit Settings | ✓ | ✗ | ✗ | ✗ | ✗ |

---

## Part 4: Implementation Roadmap

### Phase 1: Data Layer (1-2 days)
- [ ] Add `price_per_ticket` column to Attendees table
- [ ] Add `total_revenue` calculated field
- [ ] Add `last_edited_by` and `last_edited_at` for audit trail
- [ ] Add role-based permissions table

### Phase 2: Backend Logic (2-3 days)
- [ ] Update Attendees fetch query to include pricing
- [ ] Create price update API endpoint
- [ ] Create edit attendee API endpoint
- [ ] Add permission checking middleware
- [ ] Add audit logging for price changes

### Phase 3: Frontend - Core UI (2-3 days)
- [ ] Add price column to table
- [ ] Implement inline edit with modals
- [ ] Add collapsible sections with localStorage
- [ ] Responsive design implementation
- [ ] Mobile card view

### Phase 4: Advanced Features (2 days)
- [ ] Role-based UI adjustments
- [ ] Bulk price adjustment
- [ ] Permission-based action visibility
- [ ] Offline indicators per role

### Phase 5: Testing & Refinement (1-2 days)
- [ ] Cross-browser testing
- [ ] Mobile device testing
- [ ] Permission testing
- [ ] Performance optimization

---

## Part 5: Code Examples & Snippets

### 5.1 Database Schema Additions

```sql
-- Add pricing column
ALTER TABLE Attendees ADD COLUMN price_per_ticket DECIMAL(10, 2) DEFAULT 0.00;
ALTER TABLE Attendees ADD COLUMN last_edited_by INTEGER;
ALTER TABLE Attendees ADD COLUMN last_edited_at DATETIME;

-- Create permissions table
CREATE TABLE admin_permissions (
    id INTEGER PRIMARY KEY,
    role TEXT NOT NULL UNIQUE,
    permissions TEXT NOT NULL, -- JSON array of permission strings
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create audit log table
CREATE TABLE audit_logs (
    id INTEGER PRIMARY KEY,
    admin_id INTEGER,
    action TEXT,
    table_name TEXT,
    record_id INTEGER,
    old_value TEXT,
    new_value TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 5.2 Frontend Component: Price Adjustment Modal

```html
<!-- Price Adjustment Modal -->
<div id="priceModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-md w-full mx-4 shadow-2xl">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="font-bold text-lg">🎫 Adjust Ticket Prices</h3>
            <button onclick="closePriceModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div class="p-4 space-y-4">
            <!-- Apply To Section -->
            <div>
                <label class="block font-medium mb-2">Apply to:</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="priceApplyTo" value="all" checked class="mr-2">
                        All selected attendees (<span id="selectedCount">0</span>)
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="priceApplyTo" value="individual" class="mr-2">
                        Edit individually
                    </label>
                </div>
            </div>

            <!-- Price Input Section -->
            <div>
                <label class="block font-medium mb-2">New Price:</label>
                <div class="flex gap-2">
                    <span class="text-gray-600 self-center">$</span>
                    <input type="number" id="newPrice" min="0" step="0.01" placeholder="0.00" 
                           class="flex-1 border rounded-lg p-2" value="0.00">
                </div>
            </div>

            <!-- Adjustment Type -->
            <div>
                <label class="block font-medium mb-2">Adjustment Type:</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="adjustType" value="set" checked class="mr-2">
                        Set exact price
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="adjustType" value="increase" class="mr-2">
                        Increase by amount:
                        <span class="text-gray-600 ml-2">$</span>
                        <input type="number" step="0.01" class="w-20 border rounded px-1 py-0 ml-1" placeholder="0.00">
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="adjustType" value="percent" class="mr-2">
                        Apply discount: 
                        <input type="number" step="0.1" min="-100" max="100" class="w-20 border rounded px-1 py-0 ml-1" placeholder="0">
                        <span class="ml-1">%</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="adjustType" value="discount" class="mr-2">
                        Apply discount code:
                        <select class="flex-1 border rounded px-2 py-1 ml-2 text-sm">
                            <option>Select code...</option>
                            <option>EARLYBIRD (20%)</option>
                            <option>MEMBER (15%)</option>
                            <option>BULK (10%)</option>
                        </select>
                    </label>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-gray-50 p-3 rounded">
                <p class="text-sm text-gray-600">Updated price will be: <span class="font-bold text-lg">$<span id="previewPrice">0.00</span></span></p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-2 pt-2">
                <button onclick="closePriceModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Cancel</button>
                <button onclick="applyPriceAdjustment()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">Apply Changes</button>
            </div>
        </div>
    </div>
</div>
```

### 5.3 Collapsible Section Component

```html
<!-- Collapsible Section Template -->
<div class="section-container border rounded-lg shadow-sm mb-4">
    <div class="section-header bg-gradient-to-r from-gray-50 to-gray-100 p-4 cursor-pointer flex items-center justify-between" 
         onclick="toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-toggle text-xl">▼</span>
            <span class="section-icon text-lg">📊</span>
            <h2 class="font-bold text-lg">Statistics & Overview</h2>
        </div>
        <span class="text-gray-400 text-sm section-label">Show / Hide</span>
    </div>
    <div class="section-content p-4">
        <!-- Content goes here -->
    </div>
</div>

<script>
// Toggle section with localStorage persistence
function toggleSection(header) {
    const section = header.parentElement;
    const content = header.nextElementSibling;
    const toggle = header.querySelector('.section-toggle');
    const sectionId = header.getAttribute('data-section-id');
    
    const isVisible = content.style.display !== 'none';
    content.style.display = isVisible ? 'none' : 'block';
    toggle.textContent = isVisible ? '▶' : '▼';
    
    // Save preference
    if (sectionId) {
        localStorage.setItem(`section_${sectionId}`, isVisible ? 'hidden' : 'visible');
    }
}

// Load saved preferences on page load
function loadSectionPreferences() {
    document.querySelectorAll('[data-section-id]').forEach(section => {
        const id = section.getAttribute('data-section-id');
        const saved = localStorage.getItem(`section_${id}`);
        if (saved === 'hidden') {
            const content = section.querySelector('.section-content');
            const toggle = section.querySelector('.section-toggle');
            content.style.display = 'none';
            toggle.textContent = '▶';
        }
    });
}

// Call on page load
document.addEventListener('DOMContentLoaded', loadSectionPreferences);
</script>
```

### 5.4 Mobile Responsive Card View

```html
<!-- Mobile Card View for Attendees -->
<div class="block md:hidden space-y-4">
    <?php while($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-4">
            <!-- Header with checkbox and name -->
            <div class="flex items-start gap-3 mb-3">
                <input type="checkbox" name="selected_ids[]" value="<?php echo $row['Id']; ?>" class="row-checkbox mt-1">
                <div class="flex-1">
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($row['Name']); ?></h3>
                    <p class="text-sm text-gray-600">#<?php echo $row['Id']; ?></p>
                </div>
                <span class="badge bg-<?php echo $row['CheckedIn'] ? 'green' : 'yellow'; ?>-100 text-<?php echo $row['CheckedIn'] ? 'green' : 'yellow'; ?>-700 px-2 py-1 rounded text-xs">
                    <?php echo $row['CheckedIn'] ? '✅ In' : '⏳ Pending'; ?>
                </span>
            </div>

            <!-- Key Information Grid -->
            <div class="grid grid-cols-2 gap-3 mb-3 text-sm">
                <div>
                    <p class="text-gray-600">Email</p>
                    <p class="font-medium truncate"><?php echo htmlspecialchars($row['Email']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Event</p>
                    <p class="font-medium truncate"><?php echo htmlspecialchars($row['EventTitle'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Ticket</p>
                    <p class="font-medium"><?php echo $row['ticket_type'] ?? 'Standard'; ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Price</p>
                    <p class="font-bold text-blue-600">$<?php echo number_format($row['price_per_ticket'] ?? 0, 2); ?></p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium">Edit</button>
                <button class="flex-1 bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium">More</button>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Desktop Table View (shown on md and up) -->
<div class="hidden md:block overflow-x-auto">
    <!-- Existing table code -->
</div>
```

### 5.5 Role-Based Permission Checking

```php
<?php
// Helper function to check permissions
function hasPermission($db, $adminId, $action) {
    // Get admin role
    $admin = $db->querySingle(
        "SELECT role FROM admin_users WHERE id = $adminId",
        true
    );
    
    if (!$admin) return false;
    
    $role = $admin['role'];
    
    // Define permission matrix
    $permissions = [
        'super_admin' => [
            'view_attendees', 'edit_attendee', 'delete_attendee', 'restore_attendee',
            'check_in', 'adjust_price', 'send_email', 'export_data',
            'manage_admins', 'view_revenue', 'access_settings', 'view_audit_logs'
        ],
        'manager' => [
            'view_attendees', 'edit_attendee', 'delete_attendee', 'restore_attendee',
            'check_in', 'adjust_price', 'send_email', 'export_data', 'view_revenue'
        ],
        'operator' => [
            'view_attendees', 'check_in', 'send_email', 'export_data'
        ],
        'viewer' => [
            'view_attendees', 'export_data', 'view_revenue'
        ],
        'finance' => [
            'view_attendees', 'adjust_price', 'export_data', 'view_revenue'
        ]
    ];
    
    return in_array($action, $permissions[$role] ?? []);
}

// Usage in page
if (!hasPermission($db, $_SESSION['admin_id'], 'adjust_price')) {
    header('Location: attendees.php');
    exit;
}
?>
```

### 5.6 Price Adjustment API Endpoint

```php
<?php
// /api/attendees/adjust-price.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$db = getDbConnection();

// Check permission
if (($_SESSION['admin_role'] ?? '') !== 'super_admin' && 
    ($_SESSION['admin_role'] ?? '') !== 'manager' &&
    ($_SESSION['admin_role'] ?? '') !== 'finance') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ids']) || !isset($data['price'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$ids = array_map('intval', $data['ids']);
$newPrice = floatval($data['price']);
$adjustmentType = $data['type'] ?? 'set';
$adminId = $_SESSION['admin_id'];

if (empty($ids) || $newPrice < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $idsList = implode(',', $ids);
    
    if ($adjustmentType === 'set') {
        $stmt = $db->prepare("
            UPDATE Attendees 
            SET price_per_ticket = :price, 
                last_edited_by = :admin_id,
                last_edited_at = datetime('now')
            WHERE Id IN ($idsList)
        ");
    } else if ($adjustmentType === 'increase') {
        $stmt = $db->prepare("
            UPDATE Attendees 
            SET price_per_ticket = price_per_ticket + :price,
                last_edited_by = :admin_id,
                last_edited_at = datetime('now')
            WHERE Id IN ($idsList)
        ");
    } else if ($adjustmentType === 'percent') {
        $percent = ($newPrice / 100) + 1; // Convert percentage to multiplier
        $stmt = $db->prepare("
            UPDATE Attendees 
            SET price_per_ticket = price_per_ticket * :price,
                last_edited_by = :admin_id,
                last_edited_at = datetime('now')
            WHERE Id IN ($idsList)
        ");
    }
    
    $stmt->bindValue(':price', $newPrice, SQLITE3_FLOAT);
    $stmt->bindValue(':admin_id', $adminId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Log audit trail
    foreach ($ids as $id) {
        $db->prepare("
            INSERT INTO audit_logs (admin_id, action, table_name, record_id, new_value, timestamp)
            VALUES (:admin_id, 'price_adjusted', 'Attendees', :record_id, :new_value, datetime('now'))
        ")->execute([
            ':admin_id' => $adminId,
            ':record_id' => $id,
            ':new_value' => json_encode(['type' => $adjustmentType, 'value' => $newPrice])
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => count($ids) . ' prices updated successfully',
        'count' => count($ids)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$db->close();
?>
```

---

## Part 6: Implementation Priority

### Quick Wins (1-2 days):
1. ✅ Add price column to table
2. ✅ Add Edit button with modal
3. ✅ Implement collapsible sections
4. ✅ Add basic mobile card view

### Medium Priority (2-3 days):
1. Price adjustment dropdown/input
2. Revenue statistics display
3. Complete mobile responsiveness
4. Role-based UI visibility

### Strategic (3+ days):
1. Comprehensive RBAC system
2. Audit logging
3. Advanced bulk operations
4. Analytics dashboard

---

## Part 7: Accessibility & Best Practices

### Mobile-First Design:
- ✅ Touch-friendly button sizes (44x44px minimum)
- ✅ Large text on mobile (16px minimum)
- ✅ Vertical layout reduces scrolling
- ✅ One action per row on mobile

### Accessibility (WCAG 2.1 AA):
- ✅ Color not sole indicator (use icons + text)
- ✅ Keyboard navigation support
- ✅ ARIA labels for screen readers
- ✅ Focus indicators on buttons
- ✅ Form field labels and placeholders

### Performance:
- ✅ Lazy load price calculation
- ✅ Debounce filter searches
- ✅ Cache role permissions
- ✅ Optimize table rendering

---

## Part 8: Success Metrics

After implementation, measure:
1. **Time to Edit**: Reduce from 15s (modal) to 5s (inline)
2. **Mobile Usage**: Track increase in admin access from mobile
3. **Pricing Accuracy**: Monitor price discrepancy reports
4. **User Satisfaction**: Survey admins on usability
5. **Task Completion**: Measure bulk operation speed

---

## Conclusion

This comprehensive improvement plan transforms the attendee management dashboard into a powerful, flexible, and role-aware administrative tool while maintaining the existing functionality and improving overall user experience across all devices and user types.

