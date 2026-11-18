<?php
// Load configuration file (NOT database.php anymore)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/meta-tags.php';

// Site variables are now set in config.php
// $site_name, $site_url, $admin_email are available
?>

<!DOCTYPE html>
<html lang="en" data-is-admin="false">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_name; ?> - Home</title>
    
    <!-- Meta Tags for SEO & Social Sharing -->
    <?php echo generateMetaTags([
        'title' => 'Umojawetu - Community Events & Fundraising',
        'description' => 'Discover amazing community events, book tickets, and support fundraising campaigns. Join us today!',
        'image' => '/assets/og-image.png',
        'url' => $site_url . '/public/index.php'
    ]); ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d12828">
    
    <!-- Shared Styles -->
    <link rel="stylesheet" href="/assets/shared-styles.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
        }
        .status {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .nav {
            background: #2c3e50;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 5px 10px;
        }
        .nav a:hover {
            background: #34495e;
            border-radius: 3px;
        }
        .env-badge {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: <?php echo Environment::isLocal() ? '#28a745' : (Environment::isStaging() ? '#ffc107' : '#dc3545'); ?>;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to <?php echo $site_name; ?></h1>
        
        <div class="nav">
            <a href="/umojawetu-platform/public/">Home</a>
            <a href="/umojawetu-platform/public/events.php">Events</a>
            <a href="/umojawetu-platform/public/booking.php">Book Tickets</a>
            <a href="/umojawetu-platform/public/gallery.php">Gallery</a>
            <a href="/umojawetu-platform/public/status.php">Check Status</a>
            <a href="/umojawetu-platform/admin/login.php">Admin Login</a>
        </div>
        
        <div class="status">
            <strong>✅ System Status:</strong> Running correctly<br>
            <strong>📅 Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>🗄️ Database:</strong> Connected to <?php echo $db_name; ?>
        </div>
        
        <h2>Welcome to Our Community Platform</h2>
        <p>This is your main public homepage. We will add content here soon.</p>
        
        <h3>Quick Links:</h3>
        <ul>
            <li><a href="#">Upcoming Events</a></li>
            <li><a href="#">Book Tickets</a></li>
            <li><a href="#">Financial Assistance</a></li>
            <li><a href="#">Photo Gallery</a></li>
        </ul>
    </div>
    
    <div class="env-badge">
        <?php echo ucfirst(Environment::detect()); ?> Environment
    </div>
    
    <!-- Shared Scripts & Utilities -->
    <script src="/assets/shared-scripts.js"></script>
    
    <!-- Keyboard Hints -->
    <div class="keyboard-hints">
        <p>💡 Press <kbd>?</kbd> for keyboard shortcuts</p>
        <p>💡 Press <kbd>Ctrl+K</kbd> to search</p>
    </div>
    
    <!-- Loading Spinner (managed by shared-scripts.js) -->
    <div id="loading-spinner" class="loading-spinner hidden">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>