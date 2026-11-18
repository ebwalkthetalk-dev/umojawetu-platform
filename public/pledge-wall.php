<?php
// Public Pledge Wall - Shows community pledges
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/meta-tags.php';

$isLocal = Environment::isLocal();
$apiBaseUrl = $isLocal ? '/umojawetu-platform/api' : 'https://event.umojawetu.com/api';
?>

<!DOCTYPE html>
<html lang="en" data-is-admin="false">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pledge Wall - <?php echo $site_name; ?></title>
    
    <!-- Meta Tags for SEO & Social Sharing -->
    <?php echo generateMetaTags([
        'title' => 'Community Pledge Wall - Support Our Campaigns',
        'description' => 'See how our community is pledging support to amazing campaigns. Join us and make an impact!',
        'image' => '/assets/og-image.png',
        'url' => $site_url . '/public/pledge-wall.php'
    ]); ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d12828">
    
    <!-- Shared Styles -->
    <link rel="stylesheet" href="/assets/shared-styles.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pledge-card {
            animation: fadeInUp 0.6s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .pledge-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -10px rgba(0,0,0,0.15);
        }
        .progress-bar {
            transition: width 1s ease-out;
        }
        .nav-link { transition: all 0.2s ease; }
        .nav-link:hover { transform: translateY(-2px); }
        .egg-logo {
            width: 120px; height: 160px; object-fit: contain;
            border-radius: 50% / 60% 60% 40% 40%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .egg-logo:hover { transform: scale(1.05); }
        .env-badge {
            position: fixed; bottom: 10px; right: 10px;
            background: <?php echo $isLocal ? '#28a745' : '#dc3545'; ?>;
            color: white; padding: 5px 10px; border-radius: 5px;
            font-size: 11px; z-index: 999;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">
    
    <!-- Navigation -->
    <nav class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 max-w-6xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="<?php echo $siteUrl; ?>/assets/images/logo.png" alt="Logo" class="w-10 h-auto" onerror="this.src='https://event.umojawetu.com/Logo.png'">
                    <span class="text-white font-bold text-lg hidden sm:inline"><?php echo $site_name; ?></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="gallery.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">🖼️ Gallery</a>
                    <a href="events.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">📅 Events</a>
                    <a href="booking.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">🎟️ Book</a>
                    <a href="pledge.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">🤝 Pledge</a>
                    <a href="pledge-wall.php" class="nav-link px-4 py-2 bg-red-600 text-white rounded-lg font-medium transition shadow-md">🏆 Pledge Wall</a>
                    <a href="request.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">📝 Request</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12 max-w-6xl">
        <div class="text-center mb-12">
            <img src="<?php echo $siteUrl; ?>/assets/images/logo.png" alt="UMOJA WETU Logo" class="egg-logo mx-auto mb-6" onerror="this.src='https://event.umojawetu.com/Logo.png'">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">🏆 Pledge Wall</h1>
            <p class="text-xl text-white/80">Celebrating our generous community supporters</p>
        </div>

        <div id="pledge-wall-container">
            <div class="text-center text-white py-20">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent"></div>
                <p class="mt-4">Loading pledges...</p>
            </div>
        </div>
    </div>

    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?> Environment</div>

    <script>
        const apiBaseUrl = '<?php echo $apiBaseUrl; ?>';
        
        async function loadPledgeWall() {
            const container = document.getElementById('pledge-wall-container');
            
            try {
                const response = await fetch(`${apiBaseUrl}/pledges/get-pledges-public.php`);
                const data = await response.json();
                
                if (!data.enabled) {
                    container.innerHTML = `
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-12 text-center">
                            <div class="text-6xl mb-4">🔒</div>
                            <h2 class="text-2xl font-bold text-white mb-2">Pledge Wall is Offline</h2>
                            <p class="text-white/70">Please check back soon!</p>
                        </div>
                    `;
                    return;
                }
                
                if (!data.pledges || data.pledges.length === 0) {
                    container.innerHTML = `
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-12 text-center">
                            <div class="text-6xl mb-4">🤝</div>
                            <h2 class="text-2xl font-bold text-white mb-2">Be the First to Pledge!</h2>
                            <p class="text-white/70 mb-6">Support our community and see your name here</p>
                            <a href="pledge.php" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition">Make a Pledge →</a>
                        </div>
                    `;
                    return;
                }
                
                // Progress bar
                const progress = data.stats.progress_percentage || 0;
                const totalRaised = data.stats.total_raised || 0;
                const totalGoal = data.stats.total_goal || 10000;
                
                let html = `
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 mb-8">
                        <div class="text-center mb-4">
                            <h2 class="text-2xl font-bold text-white">Community Impact</h2>
                            <p class="text-white/70">£${totalRaised.toLocaleString()} raised of £${totalGoal.toLocaleString()} goal</p>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-4">
                            <div class="progress-bar bg-gradient-to-r from-green-400 to-green-600 h-4 rounded-full" style="width: ${progress}%"></div>
                        </div>
                        <div class="text-center mt-3 text-white/60 text-sm">${progress}% complete</div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                `;
                
                data.pledges.forEach(pledge => {
                    const statusColor = pledge.status.includes('Fully') ? 'text-green-400' : (pledge.status.includes('Partially') ? 'text-yellow-400' : 'text-blue-400');
                    html += `
                        <div class="pledge-card bg-white rounded-xl shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-red-500 to-red-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <span class="text-2xl">🤝</span>
                                    <span class="text-sm opacity-80">${pledge.date}</span>
                                </div>
                                <h3 class="font-bold text-xl mt-2">${escapeHtml(pledge.name)}</h3>
                                <p class="text-sm opacity-90">${pledge.type}</p>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Pledged:</span>
                                    <span class="font-bold text-green-600">£${pledge.amount.toLocaleString()}</span>
                                </div>
                                ${pledge.paid > 0 ? `
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Paid:</span>
                                    <span class="font-bold text-blue-600">£${pledge.paid.toLocaleString()}</span>
                                </div>
                                ` : ''}
                                <div class="mt-3 pt-2 border-t">
                                    <span class="${statusColor} text-sm font-medium">${pledge.status}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
                container.innerHTML = html;
                
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-12 text-center">
                        <div class="text-6xl mb-4">⚠️</div>
                        <h2 class="text-2xl font-bold text-white mb-2">Unable to Load Pledge Wall</h2>
                        <p class="text-white/70">Please try again later.</p>
                    </div>
                `;
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
        }
        
        loadPledgeWall();
        // Refresh every 30 seconds
        setInterval(loadPledgeWall, 30000);
    </script>
    
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
        <p>Loading pledges...</p>
    </div>
</body>
</html>