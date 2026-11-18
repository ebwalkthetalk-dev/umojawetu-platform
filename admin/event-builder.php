<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';
$error = '';
$editId = isset($_GET['id']) ? intval($_GET['id']) : null;
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_basic') {
        // Save basic info
        $title = $_POST['title'] ?? '';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $description = $_POST['description'] ?? '';
        $short_description = $_POST['short_description'] ?? '';
        $event_date = $_POST['event_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $venue_name = $_POST['venue_name'] ?? '';
        $venue_address = $_POST['venue_address'] ?? '';
        $venue_city = $_POST['venue_city'] ?? '';
        $venue_postcode = $_POST['venue_postcode'] ?? '';
        $max_capacity = intval($_POST['max_capacity'] ?? 0);
        $is_free = isset($_POST['is_free']) ? 1 : 0;
        
        if ($editId) {
            $stmt = $db->prepare("
                UPDATE events_dynamic SET 
                    title = :title, slug = :slug, description = :desc, short_description = :short,
                    event_date = :event_date, end_date = :end_date, venue_name = :venue_name,
                    venue_address = :venue_address, venue_city = :venue_city, venue_postcode = :venue_postcode,
                    max_capacity = :max_capacity, is_free = :is_free, updated_at = datetime('now')
                WHERE id = :id
            ");
            $stmt->bindValue(':id', $editId, SQLITE3_INTEGER);
            $message = "Event updated";
        } else {
            $stmt = $db->prepare("
                INSERT INTO events_dynamic (
                    title, slug, description, short_description, event_date, end_date,
                    venue_name, venue_address, venue_city, venue_postcode, max_capacity,
                    is_free, status, created_at, updated_at
                ) VALUES (
                    :title, :slug, :desc, :short, :event_date, :end_date,
                    :venue_name, :venue_address, :venue_city, :venue_postcode, :max_capacity,
                    :is_free, 'draft', datetime('now'), datetime('now')
                )
            ");
            $message = "Event created";
        }
        
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
        $stmt->bindValue(':short', $short_description, SQLITE3_TEXT);
        $stmt->bindValue(':event_date', $event_date, SQLITE3_TEXT);
        $stmt->bindValue(':end_date', $end_date, SQLITE3_TEXT);
        $stmt->bindValue(':venue_name', $venue_name, SQLITE3_TEXT);
        $stmt->bindValue(':venue_address', $venue_address, SQLITE3_TEXT);
        $stmt->bindValue(':venue_city', $venue_city, SQLITE3_TEXT);
        $stmt->bindValue(':venue_postcode', $venue_postcode, SQLITE3_TEXT);
        $stmt->bindValue(':max_capacity', $max_capacity, SQLITE3_INTEGER);
        $stmt->bindValue(':is_free', $is_free, SQLITE3_INTEGER);
        $stmt->execute();
        
        if (!$editId) {
            $editId = $db->lastInsertRowID();
        }
        header("Location: event-builder.php?id=$editId&step=2");
        exit;
    }
    
    if ($action === 'save_tickets') {
        // Save ticket types
        $ticketNames = $_POST['ticket_name'] ?? [];
        $ticketPrices = $_POST['ticket_price'] ?? [];
        $ticketQuantities = $_POST['ticket_quantity'] ?? [];
        $ticketMaxPerOrder = $_POST['ticket_max_per_order'] ?? [];
        
        // Delete existing tickets
        $db->exec("DELETE FROM ticket_types_dynamic WHERE event_id = $editId");
        
        foreach ($ticketNames as $index => $name) {
            if (empty($name)) continue;
            $stmt = $db->prepare("
                INSERT INTO ticket_types_dynamic (event_id, name, price, quantity, max_per_order, sort_order)
                VALUES (:event_id, :name, :price, :qty, :max, :order)
            ");
            $stmt->bindValue(':event_id', $editId, SQLITE3_INTEGER);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':price', floatval($ticketPrices[$index] ?? 0), SQLITE3_FLOAT);
            $stmt->bindValue(':qty', intval($ticketQuantities[$index] ?? 0), SQLITE3_INTEGER);
            $stmt->bindValue(':max', intval($ticketMaxPerOrder[$index] ?? 10), SQLITE3_INTEGER);
            $stmt->bindValue(':order', $index, SQLITE3_INTEGER);
            $stmt->execute();
        }
        header("Location: event-builder.php?id=$editId&step=3");
        exit;
    }
    
    if ($action === 'save_pricing') {
        // Save pricing rules
        $db->exec("DELETE FROM pricing_rules WHERE event_id = $editId");
        
        // Early bird
        if (!empty($_POST['early_bird_discount']) && $_POST['early_bird_discount'] > 0) {
            $stmt = $db->prepare("
                INSERT INTO pricing_rules (event_id, ticket_type_id, rule_type, trigger_value, discount_type, discount_value)
                VALUES (:event_id, 0, 'early_bird', :trigger, 'percentage', :discount)
            ");
            $stmt->bindValue(':event_id', $editId, SQLITE3_INTEGER);
            $stmt->bindValue(':trigger', $_POST['early_bird_deadline'], SQLITE3_TEXT);
            $stmt->bindValue(':discount', floatval($_POST['early_bird_discount']), SQLITE3_FLOAT);
            $stmt->execute();
        }
        
        // Group deal
        if (!empty($_POST['group_deal_buy']) && $_POST['group_deal_buy'] > 0) {
            $stmt = $db->prepare("
                INSERT INTO pricing_rules (event_id, ticket_type_id, rule_type, trigger_value, discount_type, discount_value)
                VALUES (:event_id, 0, 'group_deal', :trigger, 'fixed', :discount)
            ");
            $stmt->bindValue(':event_id', $editId, SQLITE3_INTEGER);
            $stmt->bindValue(':trigger', json_encode(['buy' => $_POST['group_deal_buy'], 'get' => $_POST['group_deal_get']]), SQLITE3_TEXT);
            $stmt->bindValue(':discount', 0, SQLITE3_FLOAT);
            $stmt->execute();
        }
        
        // Member discount
        if (!empty($_POST['member_discount']) && $_POST['member_discount'] > 0) {
            $stmt = $db->prepare("
                INSERT INTO pricing_rules (event_id, ticket_type_id, rule_type, discount_type, discount_value)
                VALUES (:event_id, 0, 'member_discount', 'percentage', :discount)
            ");
            $stmt->bindValue(':event_id', $editId, SQLITE3_INTEGER);
            $stmt->bindValue(':discount', floatval($_POST['member_discount']), SQLITE3_FLOAT);
            $stmt->execute();
        }
        
        header("Location: event-builder.php?id=$editId&step=4");
        exit;
    }
    
    if ($action === 'save_media') {
        // Save media
        $mediaUrls = $_POST['media_url'] ?? [];
        $mediaTypes = $_POST['media_type'] ?? [];
        $mediaCaptions = $_POST['media_caption'] ?? [];
        
        $db->exec("DELETE FROM event_media_dynamic WHERE event_id = $editId");
        
        foreach ($mediaUrls as $index => $url) {
            if (empty($url)) continue;
            $stmt = $db->prepare("
                INSERT INTO event_media_dynamic (event_id, media_type, media_url, caption, sort_order)
                VALUES (:event_id, :type, :url, :caption, :order)
            ");
            $stmt->bindValue(':event_id', $editId, SQLITE3_INTEGER);
            $stmt->bindValue(':type', $mediaTypes[$index] ?? 'image', SQLITE3_TEXT);
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
            $stmt->bindValue(':caption', $mediaCaptions[$index] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(':order', $index, SQLITE3_INTEGER);
            $stmt->execute();
        }
        
        // Save featured image
        if (!empty($_POST['featured_image'])) {
            $db->exec("UPDATE events_dynamic SET featured_image = '" . SQLite3::escapeString($_POST['featured_image']) . "' WHERE id = $editId");
        }
        
        header("Location: event-builder.php?id=$editId&step=5");
        exit;
    }
    
    if ($action === 'publish') {
        $db->exec("UPDATE events_dynamic SET status = 'published', published_at = datetime('now') WHERE id = $editId");
        header("Location: events.php");
        exit;
    }
}

// Load event data if editing
$eventData = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM events_dynamic WHERE id = :id");
    $stmt->bindValue(':id', $editId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $eventData = $result->fetchArray(SQLITE3_ASSOC);
}

// Load ticket types
$ticketTypes = [];
if ($editId) {
    $result = $db->query("SELECT * FROM ticket_types_dynamic WHERE event_id = $editId ORDER BY sort_order");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $ticketTypes[] = $row;
    }
}

// Load pricing rules
$pricingRules = [];
if ($editId) {
    $result = $db->query("SELECT * FROM pricing_rules WHERE event_id = $editId");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $pricingRules[$row['rule_type']] = $row;
    }
}

// Load media
$mediaItems = [];
if ($editId) {
    $result = $db->query("SELECT * FROM event_media_dynamic WHERE event_id = $editId ORDER BY sort_order");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $mediaItems[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Builder - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-apikey/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .step-nav { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .step-nav a { padding: 0.75rem 1.5rem; background: #f3f4f6; border-radius: 0.5rem; text-decoration: none; color: #4b5563; transition: all 0.2s; }
        .step-nav a.active { background: #dc2626; color: white; }
        .step-nav a.completed { background: #10b981; color: white; }
        .step-nav a:hover:not(.active) { background: #e5e7eb; }
        .ticket-row { background: #f9fafb; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #e5e7eb; }
        .media-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 0.5rem; }
        .remove-item { cursor: pointer; color: #ef4444; font-size: 1.25rem; }
        .drag-handle { cursor: move; color: #9ca3af; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400">Dynamic Event Builder</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🏠 Dashboard</a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📅 Events</a>
                <a href="event-builder.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">✨ Event Builder</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👥 Attendees</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">✨ <?php echo $editId ? 'Edit Event' : 'Create New Event'; ?></h1>
                    <?php if ($eventData && $eventData['status'] === 'published'): ?>
                        <a href="/public/event.php?id=<?php echo $editId; ?>" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-lg">👁️ View Event</a>
                    <?php endif; ?>
                </div>

                <?php if ($message): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- Step Navigation -->
                <div class="step-nav">
                    <a href="?id=<?php echo $editId; ?>&step=1" class="<?php echo $step == 1 ? 'active' : ($eventData ? 'completed' : ''); ?>">1. Basic Info</a>
                    <a href="?id=<?php echo $editId; ?>&step=2" class="<?php echo $step == 2 ? 'active' : (!empty($ticketTypes) ? 'completed' : ''); ?>">2. Tickets</a>
                    <a href="?id=<?php echo $editId; ?>&step=3" class="<?php echo $step == 3 ? 'active' : (!empty($pricingRules) ? 'completed' : ''); ?>">3. Pricing Rules</a>
                    <a href="?id=<?php echo $editId; ?>&step=4" class="<?php echo $step == 4 ? 'active' : (!empty($mediaItems) ? 'completed' : ''); ?>">4. Media</a>
                    <a href="?id=<?php echo $editId; ?>&step=5" class="<?php echo $step == 5 ? 'active' : ''; ?>">5. Publish</a>
                </div>

                <!-- Step 1: Basic Info -->
                <?php if ($step == 1): ?>
                <form method="POST" class="bg-white rounded-lg shadow p-6">
                    <input type="hidden" name="action" value="save_basic">
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="md:col-span-2"><label class="block font-medium">Event Title *</label><input type="text" name="title" value="<?php echo htmlspecialchars($eventData['title'] ?? ''); ?>" required class="w-full border rounded-lg p-2"></div>
                        <div class="md:col-span-2"><label class="block font-medium">Short Description</label><textarea name="short_description" rows="2" class="w-full border rounded-lg p-2"><?php echo htmlspecialchars($eventData['short_description'] ?? ''); ?></textarea></div>
                        <div class="md:col-span-2"><label class="block font-medium">Full Description</label><textarea name="description" rows="5" class="w-full border rounded-lg p-2"><?php echo htmlspecialchars($eventData['description'] ?? ''); ?></textarea></div>
                        <div><label class="block font-medium">Event Start Date *</label><input type="datetime-local" name="event_date" value="<?php echo htmlspecialchars($eventData['event_date'] ?? ''); ?>" required class="w-full border rounded-lg p-2"></div>
                        <div><label class="block font-medium">Event End Date</label><input type="datetime-local" name="end_date" value="<?php echo htmlspecialchars($eventData['end_date'] ?? ''); ?>" class="w-full border rounded-lg p-2"></div>
                        <div><label class="block font-medium">Venue Name</label><input type="text" name="venue_name" value="<?php echo htmlspecialchars($eventData['venue_name'] ?? ''); ?>" class="w-full border rounded-lg p-2"></div>
                        <div><label class="block font-medium">Venue Address</label><input type="text" name="venue_address" value="<?php echo htmlspecialchars($eventData['venue_address'] ?? ''); ?>" class="w-full border rounded-lg p-2"></div>
                        <div><label class="block font-medium">City</label><input type="text" name="venue_city" value="<?php echo htmlspecialchars($eventData['venue_city'] ?? ''); ?>" class="w-full border rounded-lg p-2"></div>
                        <div><label class="block font-medium">Postcode</label><input type="text" name="venue_postcode" value="<?php echo htmlspecialchars($eventData['venue_postcode'] ?? ''); ?>" class="w-full border rounded-lg p-2"></div>
                        <div><label class="block font-medium">Max Capacity</label><input type="number" name="max_capacity" value="<?php echo $eventData['max_capacity'] ?? 0; ?>" class="w-full border rounded-lg p-2"></div>
                        <div><label class="flex items-center gap-2"><input type="checkbox" name="is_free" <?php echo ($eventData['is_free'] ?? 0) ? 'checked' : ''; ?>> This is a free event</label></div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">Save & Continue →</button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- Step 2: Ticket Types (UNLIMITED) -->
                <?php if ($step == 2): ?>
                <form method="POST" class="bg-white rounded-lg shadow p-6" id="ticketForm">
                    <input type="hidden" name="action" value="save_tickets">
                    
                    <div id="ticketsContainer">
                        <?php if (empty($ticketTypes)): ?>
                        <div class="ticket-row">
                            <div class="grid md:grid-cols-4 gap-3">
                                <input type="text" name="ticket_name[]" placeholder="Ticket Name (e.g., General Admission)" class="border rounded-lg p-2">
                                <input type="number" step="0.01" name="ticket_price[]" placeholder="Price (£)" class="border rounded-lg p-2">
                                <input type="number" name="ticket_quantity[]" placeholder="Quantity Available" class="border rounded-lg p-2">
                                <input type="number" name="ticket_max_per_order[]" placeholder="Max per order" value="10" class="border rounded-lg p-2">
                            </div>
                        </div>
                        <?php else: ?>
                            <?php foreach ($ticketTypes as $ticket): ?>
                            <div class="ticket-row">
                                <div class="grid md:grid-cols-4 gap-3">
                                    <input type="text" name="ticket_name[]" value="<?php echo htmlspecialchars($ticket['name']); ?>" placeholder="Ticket Name" class="border rounded-lg p-2">
                                    <input type="number" step="0.01" name="ticket_price[]" value="<?php echo $ticket['price']; ?>" placeholder="Price (£)" class="border rounded-lg p-2">
                                    <input type="number" name="ticket_quantity[]" value="<?php echo $ticket['quantity']; ?>" placeholder="Quantity" class="border rounded-lg p-2">
                                    <input type="number" name="ticket_max_per_order[]" value="<?php echo $ticket['max_per_order']; ?>" placeholder="Max per order" class="border rounded-lg p-2">
                                </div>
                                <button type="button" class="remove-ticket text-red-600 text-sm mt-2">Remove</button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" onclick="addTicket()" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg">+ Add Another Ticket Type</button>
                    
                    <div class="mt-6 flex justify-between">
                        <a href="?id=<?php echo $editId; ?>&step=1" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg">← Back</a>
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">Save & Continue →</button>
                    </div>
                </form>
                
                <script>
                    function addTicket() {
                        const container = document.getElementById('ticketsContainer');
                        const newRow = document.createElement('div');
                        newRow.className = 'ticket-row';
                        newRow.innerHTML = `
                            <div class="grid md:grid-cols-4 gap-3">
                                <input type="text" name="ticket_name[]" placeholder="Ticket Name" class="border rounded-lg p-2">
                                <input type="number" step="0.01" name="ticket_price[]" placeholder="Price (£)" class="border rounded-lg p-2">
                                <input type="number" name="ticket_quantity[]" placeholder="Quantity" class="border rounded-lg p-2">
                                <input type="number" name="ticket_max_per_order[]" placeholder="Max per order" value="10" class="border rounded-lg p-2">
                            </div>
                            <button type="button" class="remove-ticket text-red-600 text-sm mt-2">Remove</button>
                        `;
                        container.appendChild(newRow);
                        attachRemoveEvents();
                    }
                    
                    function attachRemoveEvents() {
                        document.querySelectorAll('.remove-ticket').forEach(btn => {
                            btn.onclick = function() { this.parentElement.remove(); };
                        });
                    }
                    attachRemoveEvents();
                </script>
                <?php endif; ?>

                <!-- Step 3: Pricing Rules -->
                <?php if ($step == 3): ?>
                <form method="POST" class="bg-white rounded-lg shadow p-6">
                    <input type="hidden" name="action" value="save_pricing">
                    
                    <div class="space-y-6">
                        <div class="border rounded-lg p-4">
                            <h3 class="font-bold text-lg mb-3">⏱️ Early Bird Discount</h3>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div><label class="block text-sm">Discount (%)</label><input type="number" step="0.01" name="early_bird_discount" value="<?php echo $pricingRules['early_bird']['discount_value'] ?? 0; ?>" class="border rounded-lg p-2 w-full"></div>
                                <div><label class="block text-sm">Valid Until</label><input type="datetime-local" name="early_bird_deadline" value="<?php echo $pricingRules['early_bird']['trigger_value'] ?? ''; ?>" class="border rounded-lg p-2 w-full"></div>
                            </div>
                        </div>
                        
                        <div class="border rounded-lg p-4">
                            <h3 class="font-bold text-lg mb-3">👥 Group Deal</h3>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div><label class="block text-sm">Buy X tickets</label><input type="number" name="group_deal_buy" value="<?php 
                                    $val = $pricingRules['group_deal']['trigger_value'] ?? '';
                                    echo $val ? json_decode($val, true)['buy'] ?? 2 : 2;
                                ?>" class="border rounded-lg p-2 w-full"></div>
                                <div><label class="block text-sm">Get Y free</label><input type="number" name="group_deal_get" value="<?php 
                                    $val = $pricingRules['group_deal']['trigger_value'] ?? '';
                                    echo $val ? json_decode($val, true)['get'] ?? 1 : 1;
                                ?>" class="border rounded-lg p-2 w-full"></div>
                            </div>
                        </div>
                        
                        <div class="border rounded-lg p-4">
                            <h3 class="font-bold text-lg mb-3">👑 Member Discount</h3>
                            <div><label class="block text-sm">Discount for members (%)</label><input type="number" step="0.01" name="member_discount" value="<?php echo $pricingRules['member_discount']['discount_value'] ?? 0; ?>" class="border rounded-lg p-2 w-48"></div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-between">
                        <a href="?id=<?php echo $editId; ?>&step=2" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg">← Back</a>
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">Save & Continue →</button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- Step 4: Media -->
                <?php if ($step == 4): ?>
                <form method="POST" class="bg-white rounded-lg shadow p-6">
                    <input type="hidden" name="action" value="save_media">
                    
                    <div class="mb-6">
                        <label class="block font-medium mb-2">Featured Image</label>
                        <input type="text" name="featured_image" id="featuredImage" value="<?php echo htmlspecialchars($eventData['featured_image'] ?? ''); ?>" placeholder="Image URL" class="w-full border rounded-lg p-2">
                        <div id="featuredPreview" class="mt-2"><?php if ($eventData['featured_image']): ?><img src="<?php echo $eventData['featured_image']; ?>" class="w-32 h-32 object-cover rounded-lg"><?php endif; ?></div>
                        <button type="button" onclick="document.getElementById('featuredImage').value = 'https://placehold.co/600x400/6b21a5/white?text=Event'" class="mt-2 text-sm text-blue-600">Use Placeholder</button>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block font-medium mb-2">Gallery Images & Videos</label>
                        <div id="mediaContainer">
                            <?php if (empty($mediaItems)): ?>
                            <div class="media-row border rounded-lg p-3 mb-2">
                                <div class="grid md:grid-cols-3 gap-3">
                                    <input type="text" name="media_url[]" placeholder="Image URL or YouTube URL" class="border rounded-lg p-2">
                                    <select name="media_type[]" class="border rounded-lg p-2">
                                        <option value="image">Image</option>
                                        <option value="youtube">YouTube Video</option>
                                    </select>
                                    <input type="text" name="media_caption[]" placeholder="Caption" class="border rounded-lg p-2">
                                </div>
                                <button type="button" class="remove-media text-red-600 text-sm mt-2">Remove</button>
                            </div>
                            <?php else: ?>
                                <?php foreach ($mediaItems as $media): ?>
                                <div class="media-row border rounded-lg p-3 mb-2">
                                    <div class="grid md:grid-cols-3 gap-3">
                                        <input type="text" name="media_url[]" value="<?php echo htmlspecialchars($media['media_url']); ?>" placeholder="URL" class="border rounded-lg p-2">
                                        <select name="media_type[]" class="border rounded-lg p-2">
                                            <option value="image" <?php echo $media['media_type'] == 'image' ? 'selected' : ''; ?>>Image</option>
                                            <option value="youtube" <?php echo $media['media_type'] == 'youtube' ? 'selected' : ''; ?>>YouTube Video</option>
                                        </select>
                                        <input type="text" name="media_caption[]" value="<?php echo htmlspecialchars($media['caption'] ?? ''); ?>" placeholder="Caption" class="border rounded-lg p-2">
                                    </div>
                                    <button type="button" class="remove-media text-red-600 text-sm mt-2">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="addMedia()" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-lg">+ Add Image/Video</button>
                    </div>
                    
                    <div class="mt-6 flex justify-between">
                        <a href="?id=<?php echo $editId; ?>&step=3" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg">← Back</a>
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">Save & Continue →</button>
                    </div>
                </form>
                
                <script>
                    function addMedia() {
                        const container = document.getElementById('mediaContainer');
                        const newRow = document.createElement('div');
                        newRow.className = 'media-row border rounded-lg p-3 mb-2';
                        newRow.innerHTML = `
                            <div class="grid md:grid-cols-3 gap-3">
                                <input type="text" name="media_url[]" placeholder="Image URL or YouTube URL" class="border rounded-lg p-2">
                                <select name="media_type[]" class="border rounded-lg p-2">
                                    <option value="image">Image</option>
                                    <option value="youtube">YouTube Video</option>
                                </select>
                                <input type="text" name="media_caption[]" placeholder="Caption" class="border rounded-lg p-2">
                            </div>
                            <button type="button" class="remove-media text-red-600 text-sm mt-2">Remove</button>
                        `;
                        container.appendChild(newRow);
                        attachRemoveEvents();
                    }
                    
                    function attachRemoveEvents() {
                        document.querySelectorAll('.remove-media').forEach(btn => {
                            btn.onclick = function() { this.parentElement.remove(); };
                        });
                    }
                    attachRemoveEvents();
                    
                    document.getElementById('featuredImage').addEventListener('change', function() {
                        document.getElementById('featuredPreview').innerHTML = `<img src="${this.value}" class="w-32 h-32 object-cover rounded-lg">`;
                    });
                </script>
                <?php endif; ?>

                <!-- Step 5: Publish -->
                <?php if ($step == 5): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-2xl font-bold mb-4">📋 Ready to Publish?</h2>
                    
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <h3 class="font-bold text-lg mb-3">Event Summary</h3>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($eventData['title'] ?? 'N/A'); ?></p>
                        <p><strong>Date:</strong> <?php echo $eventData['event_date'] ?? 'N/A'; ?></p>
                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($eventData['venue_name'] ?? 'N/A'); ?></p>
                        <p><strong>Ticket Types:</strong> <?php echo count($ticketTypes); ?></p>
                        <p><strong>Media Items:</strong> <?php echo count($mediaItems); ?></p>
                        <p><strong>Status:</strong> <?php echo $eventData['status'] ?? 'draft'; ?></p>
                    </div>
                    
                    <div class="flex gap-4">
                        <a href="?id=<?php echo $editId; ?>&step=4" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg">← Back to Edit</a>
                        <?php if (($eventData['status'] ?? 'draft') !== 'published'): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="publish">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">🚀 Publish Event</button>
                        </form>
                        <?php else: ?>
                        <a href="/public/event.php?id=<?php echo $editId; ?>" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded-lg">👁️ View Live Event</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php $db->close(); ?>