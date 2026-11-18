<?php
// Create dynamic event schema
// --- AUTH ---
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
// --- /AUTH ---
$dbPath = 'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db';
$db = new SQLite3($dbPath);

echo "<h1>Creating Dynamic Event Schema</h1>";

// 1. Events table (core)
$db->exec("
    CREATE TABLE IF NOT EXISTS events_dynamic (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE,
        description TEXT,
        short_description TEXT,
        event_date DATETIME,
        end_date DATETIME,
        venue_name TEXT,
        venue_address TEXT,
        venue_city TEXT,
        venue_postcode TEXT,
        venue_lat REAL,
        venue_lng REAL,
        status TEXT DEFAULT 'draft',
        visibility TEXT DEFAULT 'public',
        featured_image TEXT,
        gallery_images TEXT,
        video_url TEXT,
        video_type TEXT,
        organizer_name TEXT,
        organizer_email TEXT,
        organizer_phone TEXT,
        organizer_website TEXT,
        max_capacity INTEGER DEFAULT 0,
        min_age INTEGER DEFAULT 0,
        is_free INTEGER DEFAULT 0,
        tax_rate REAL DEFAULT 0,
        refund_policy TEXT,
        terms_conditions TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME,
        published_at DATETIME
    )
");
echo "<p>✅ Created events_dynamic table</p>";

// 2. Ticket types table (UNLIMITED - dynamic)
$db->exec("
    CREATE TABLE IF NOT EXISTS ticket_types_dynamic (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        price REAL DEFAULT 0,
        original_price REAL,
        quantity INTEGER DEFAULT 0,
        max_per_order INTEGER DEFAULT 10,
        min_per_order INTEGER DEFAULT 1,
        sales_start DATETIME,
        sales_end DATETIME,
        is_hidden INTEGER DEFAULT 0,
        is_unlimited INTEGER DEFAULT 0,
        requires_attendee_info INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events_dynamic(id) ON DELETE CASCADE
    )
");
echo "<p>✅ Created ticket_types_dynamic table (unlimited ticket types)</p>";

// 3. Pricing rules table (dynamic pricing)
$db->exec("
    CREATE TABLE IF NOT EXISTS pricing_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        ticket_type_id INTEGER NOT NULL,
        rule_type TEXT, -- 'early_bird', 'group_discount', 'member_discount', 'promo_code'
        trigger_value TEXT, -- date, quantity, code
        discount_type TEXT, -- 'percentage', 'fixed'
        discount_value REAL,
        applies_to TEXT, -- 'all', 'specific_ticket'
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events_dynamic(id) ON DELETE CASCADE,
        FOREIGN KEY (ticket_type_id) REFERENCES ticket_types_dynamic(id) ON DELETE CASCADE
    )
");
echo "<p>✅ Created pricing_rules table (dynamic pricing)</p>";

// 4. Promo codes table
$db->exec("
    CREATE TABLE IF NOT EXISTS promo_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        code TEXT UNIQUE NOT NULL,
        discount_type TEXT, -- 'percentage', 'fixed'
        discount_value REAL,
        usage_limit INTEGER DEFAULT 0,
        used_count INTEGER DEFAULT 0,
        valid_from DATETIME,
        valid_to DATETIME,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events_dynamic(id) ON DELETE CASCADE
    )
");
echo "<p>✅ Created promo_codes table</p>";

// 5. Media gallery table
$db->exec("
    CREATE TABLE IF NOT EXISTS event_media_dynamic (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        media_type TEXT, -- 'image', 'video', 'youtube'
        media_url TEXT,
        thumbnail_url TEXT,
        caption TEXT,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events_dynamic(id) ON DELETE CASCADE
    )
");
echo "<p>✅ Created event_media_dynamic table</p>";

echo "<h2>Migration Complete!</h2>";
echo "<p><a href='/admin/event-builder.php'>Go to Dynamic Event Builder →</a></p>";

$db->close();
?>