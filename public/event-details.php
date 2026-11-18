<?php
// Public Event Details Page with Announcements
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/meta-tags.php';

$eventId = intval($_GET['id'] ?? 0);

if (!$eventId) {
    http_response_code(404);
    header('Location: events.php');
    exit;
}

try {
    $db = getDbConnection();
    
    // Get event details
    $event = $db->querySingle("SELECT * FROM Events WHERE Id = $eventId AND (IsDeleted = 0 OR IsDeleted IS NULL)", true);
    
    if (!$event) {
        http_response_code(404);
        header('Location: events.php?error=Event not found');
        exit;
    }
    
    // Get published announcements
    $announcementsResult = $db->query("
        SELECT Id, Title, Content, AnnouncementType, Priority, PublishedAt
        FROM EventAnnouncements
        WHERE EventId = $eventId AND Status = 'published'
        ORDER BY Priority DESC, PublishedAt DESC
        LIMIT 5
    ");
    
    $announcements = [];
    while ($row = $announcementsResult->fetchArray(SQLITE3_ASSOC)) {
        $announcements[] = $row;
    }
    
    $db->close();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit;
}

// Helper function for icon based on announcement type
function getAnnouncementIcon($type) {
    switch($type) {
        case 'alert': return '🚨';
        case 'reminder': return '⏰';
        case 'update': return '📝';
        default: return '📢';
    }
}

// Helper function for priority color
function getPriorityColor($priority) {
    if ($priority >= 5) return 'border-l-4 border-red-600 bg-red-50';
    if ($priority >= 4) return 'border-l-4 border-orange-600 bg-orange-50';
    if ($priority >= 3) return 'border-l-4 border-yellow-600 bg-yellow-50';
    if ($priority >= 2) return 'border-l-4 border-blue-600 bg-blue-50';
    return 'border-l-4 border-gray-400 bg-gray-50';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['Title']); ?> | <?php echo $site_name; ?></title>
    
    <?php echo generateMetaTags([
        'title' => htmlspecialchars($event['Title']) . ' - Event Details',
        'description' => htmlspecialchars(substr(strip_tags($event['Description']), 0, 160)),
        'image' => $event['ImageUrl'] ?? '/assets/og-image.png',
        'url' => $site_url . '/public/event-details.php?id=' . $eventId
    ]); ?>
    
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d12828">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/shared-styles.css">
    
    <style>
        .hero-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.4);
        }
        .hero-content {
            position: relative;
            z-index: 10;
        }
        .announcement-card {
            transition: all 0.3s;
        }
        .announcement-card:hover {
            transform: translateX(4px);
        }
        .announcement-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .type-alert { background: #fee2e2; color: #991b1b; }
        .type-update { background: #e0e7ff; color: #3730a3; }
        .type-reminder { background: #fce7f3; color: #831843; }
        .event-detail-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: #f3f4f6;
            border-radius: 8px;
        }
        .price-card {
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
        }
        .price-card:hover {
            border-color: #dc2626;
            background: #fef2f2;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-red-600">🎭 Umoja Wetu</a>
            <div class="flex gap-6">
                <a href="events.php" class="text-gray-600 hover:text-red-600">Events</a>
                <a href="bookings-wizard.php" class="text-gray-600 hover:text-red-600">Book Now</a>
                <a href="gallery.php" class="text-gray-600 hover:text-red-600">Gallery</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-image" style="background-image: url('<?php echo htmlspecialchars($event['ImageUrl'] ?? ''); ?>')">
        <div class="hero-overlay"></div>
        <div class="hero-content max-w-6xl mx-auto px-4 py-24 text-white">
            <span class="inline-block bg-red-600 text-white px-4 py-2 rounded-full text-sm font-bold mb-4">
                <?php echo $event['isActive'] ? '🔴 Now Booking' : '⏳ Coming Soon'; ?>
            </span>
            <h1 class="text-5xl font-bold mb-4"><?php echo htmlspecialchars($event['Title']); ?></h1>
            <div class="flex gap-8 text-lg">
                <div class="flex items-center gap-2">
                    <span>📅</span>
                    <span><?php echo date('F j, Y g:i A', strtotime($event['Date'])); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span>📍</span>
                    <span><?php echo htmlspecialchars($event['Location'] ?? 'Venue TBC'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="md:col-span-2 space-y-8">
                <!-- Event Details Cards -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold mb-6">Event Details</h2>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="event-detail-badge">
                            <span class="text-2xl">📅</span>
                            <div class="text-left">
                                <p class="text-xs text-gray-600">Date & Time</p>
                                <p class="font-bold"><?php echo date('M d, Y', strtotime($event['Date'])); ?></p>
                                <p class="text-sm"><?php echo date('g:i A', strtotime($event['Date'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="event-detail-badge">
                            <span class="text-2xl">📍</span>
                            <div class="text-left">
                                <p class="text-xs text-gray-600">Location</p>
                                <p class="font-bold"><?php echo htmlspecialchars($event['Location'] ?? 'TBC'); ?></p>
                                <a href="https://maps.google.com/?q=<?php echo urlencode($event['Location'] ?? ''); ?>" target="_blank" class="text-xs text-red-600 hover:underline">View on Map →</a>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-bold mb-3">About This Event</h3>
                        <div class="prose max-w-none text-gray-700 leading-relaxed">
                            <?php echo html_entity_decode($event['Description']); ?>
                        </div>
                    </div>
                </div>

                <!-- Announcements Section -->
                <?php if (count($announcements) > 0): ?>
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold mb-6">📢 Latest Updates</h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-card <?php echo getPriorityColor($ann['Priority']); ?> p-6 rounded-lg">
                            <div class="flex gap-4">
                                <div class="text-3xl flex-shrink-0">
                                    <?php echo getAnnouncementIcon($ann['AnnouncementType']); ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($ann['Title']); ?></h3>
                                        <span class="announcement-type-badge type-<?php echo strtolower($ann['AnnouncementType']); ?>">
                                            <?php echo ucfirst($ann['AnnouncementType']); ?>
                                        </span>
                                    </div>
                                    <div class="text-gray-700 mb-2 leading-relaxed">
                                        <?php echo html_entity_decode($ann['Content']); ?>
                                    </div>
                                    <p class="text-xs text-gray-600">
                                        📅 Posted <?php echo date('M d, Y g:i A', strtotime($ann['PublishedAt'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <p class="text-gray-700">✨ No announcements yet. Check back soon for updates!</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pricing Card -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-xl font-bold mb-6">Pricing</h3>
                    
                    <div class="space-y-3">
                        <div class="price-card">
                            <p class="text-gray-600 text-sm">Adult</p>
                            <p class="text-3xl font-bold text-red-600">£<?php echo floatval($event['PriceAdult'] ?? 0); ?></p>
                        </div>
                        
                        <div class="price-card">
                            <p class="text-gray-600 text-sm">Child</p>
                            <p class="text-3xl font-bold text-red-600">£<?php echo floatval($event['PriceChild'] ?? 0); ?></p>
                            <?php if ($event['FirstChildFree']): ?>
                            <p class="text-xs text-green-600 font-bold mt-2">✅ First child FREE</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (floatval($event['PriceVIP'] ?? 0) > 0): ?>
                        <div class="price-card">
                            <p class="text-gray-600 text-sm">VIP Experience</p>
                            <p class="text-3xl font-bold text-red-600">£<?php echo floatval($event['PriceVIP']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Book Now Button -->
                <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-lg shadow-lg p-8 text-white text-center">
                    <p class="text-sm mb-3 opacity-90">Ready to join us?</p>
                    <a href="/umojawetu-platform/public/bookings-wizard.php?event=<?php echo $eventId; ?>" class="block bg-white text-red-600 font-bold py-3 rounded-lg hover:bg-gray-100 transition mb-4">
                        🎫 Book Your Tickets
                    </a>
                    <p class="text-xs opacity-75">Easy checkout • Secure payment • Instant confirmation</p>
                </div>

                <!-- Share Card -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-lg font-bold mb-4">Share Event</h3>
                    <div class="space-y-2">
                        <button onclick="shareOnFacebook()" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 text-sm font-bold">📘 Share on Facebook</button>
                        <button onclick="shareOnTwitter()" class="w-full bg-blue-400 text-white py-2 rounded-lg hover:bg-blue-500 text-sm font-bold">𝕏 Share on Twitter</button>
                        <button onclick="copyLink()" class="w-full bg-gray-600 text-white py-2 rounded-lg hover:bg-gray-700 text-sm font-bold">🔗 Copy Link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <p class="text-gray-400">© 2026 Umoja Wetu. Building Community Together.</p>
        </div>
    </footer>

    <script>
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }

        function shareOnTwitter() {
            const text = encodeURIComponent('Check out this event: <?php echo htmlspecialchars($event['Title']); ?>');
            const url = encodeURIComponent(window.location.href);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('✅ Event link copied to clipboard!');
            });
        }

        // Scroll to section
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const id = link.getAttribute('href').slice(1);
                document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>
