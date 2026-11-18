<?php
// Public Pledge Display - Shows community supporters (No contact info)
require_once __DIR__ . '/../config/config.php';

$isLocal = Environment::isLocal();
$siteUrl = Environment::getConfig()['site_url'];
$apiBaseUrl = $isLocal ? '/umojawetu-platform/api' : 'https://event.umojawetu.com/api';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Supporters - <?php echo $site_name; ?></title>
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
        .env-badge {
            position: fixed; bottom: 10px; right: 10px;
            background: <?php echo $isLocal ? '#28a745' : '#dc3545'; ?>;
            color: white; padding: 5px 10px; border-radius: 5px;
            font-size: 11px; z-index: 999;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-pending { background: #e2e3e5; color: #383d41; }
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
                    <a href="gallery.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">🖼️ Gallery</a>
                    <a href="events.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">📅 Events</a>
                    <a href="booking.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">🎟️ Book</a>
                    <a href="pledge.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">🤝 Pledge</a>
                    <a href="pledges-public.php" class="nav-link px-4 py-2 bg-red-600 text-white rounded-lg shadow-md transition">🏆 Supporters</a>
                    <a href="request.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">📝 Request</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12 max-w-6xl">
        <div class="text-center mb-12">
            <img src="<?php echo $siteUrl; ?>/assets/images/logo.png" alt="UMOJA WETU Logo" class="egg-logo mx-auto mb-6" onerror="this.src='https://event.umojawetu.com/Logo.png'">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">🏆 Our Supporters</h1>
            <p class="text-xl text-white/80">Thank you to our generous community</p>
        </div>

        <div id="pledges-container">
            <div class="text-center text-white py-20">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent"></div>
                <p class="mt-4">Loading supporters...</p>
            </div>
        </div>
        
        <div class="text-center mt-12">
            <a href="pledge.php" class="inline-block bg-red-600 text-white px-8 py-3 rounded-full font-bold hover:bg-red-700 transition shadow-lg">
                🤝 Make a Pledge
            </a>
        </div>
    </div>

    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?> Environment</div>

    <script>
        const apiBaseUrl = '<?php echo $apiBaseUrl; ?>';
        
        async function loadPledges() {
            const container = document.getElementById('pledges-container');
            
            try {
                const response = await fetch(`${apiBaseUrl}/pledges/get-pledges-public.php`);
                const data = await response.json();
                
                if (!data.enabled) {
                    container.innerHTML = `
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-12 text-center">
                            <div class="text-6xl mb-4">🔒</div>
                            <h2 class="text-2xl font-bold text-white mb-2">Coming Soon</h2>
                            <p class="text-white/70">Our supporter wall will be revealed soon!</p>
                        </div>
                    `;
                    return;
                }
                
                if (!data.pledges || data.pledges.length === 0) {
                    container.innerHTML = `
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-12 text-center">
                            <div class="text-6xl mb-4">🤝</div>
                            <h2 class="text-2xl font-bold text-white mb-2">Be the First Supporter!</h2>
                            <p class="text-white/70 mb-6">Make a pledge and see your name here</p>
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
                    // Status badge based on payment status
                    let statusBadge = '';
                    let statusIcon = '';
                    
                    if (pledge.payment_status === 'paid') {
                        statusBadge = '<span class="status-paid px-3 py-1 rounded-full text-xs font-semibold">✅ Fully Paid</span>';
                        statusIcon = '🏆';
                    } else if (pledge.payment_status === 'partial') {
                        statusBadge = '<span class="status-partial px-3 py-1 rounded-full text-xs font-semibold">💰 Partially Paid</span>';
                        statusIcon = '⭐';
                    } else {
                        statusBadge = '<span class="status-pending px-3 py-1 rounded-full text-xs font-semibold">🤝 Pledged</span>';
                        statusIcon = '🤝';
                    }
                    
                    // Amount display with icons for larger pledges
                    let amountDisplay = '';
                    if (pledge.amount >= 1000) {
                        amountDisplay = '🏆 £' + pledge.amount.toLocaleString();
                    } else if (pledge.amount >= 500) {
                        amountDisplay = '⭐ £' + pledge.amount.toLocaleString();
                    } else if (pledge.amount >= 100) {
                        amountDisplay = '💝 £' + pledge.amount.toLocaleString();
                    } else {
                        amountDisplay = '£' + pledge.amount.toLocaleString();
                    }
                    
                    html += `
                        <div class="pledge-card bg-white rounded-xl shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-red-500 to-red-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <span class="text-2xl">${statusIcon}</span>
                                    <span class="text-sm opacity-80">${pledge.date || 'Recent'}</span>
                                </div>
                                <h3 class="font-bold text-xl mt-2">${escapeHtml(pledge.name)}</h3>
                                <p class="text-sm opacity-90">${pledge.type}</p>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-gray-600">Pledged:</span>
                                    <span class="font-bold text-green-600">${amountDisplay}</span>
                                </div>
                                ${pledge.paid > 0 && pledge.paid < pledge.amount ? `
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-gray-600">Paid:</span>
                                    <span class="font-bold text-blue-600">£${pledge.paid.toLocaleString()}</span>
                                </div>
                                ` : ''}
                                <div class="mt-3 pt-3 border-t">
                                    ${statusBadge}
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
                        <h2 class="text-2xl font-bold text-white mb-2">Unable to Load Supporters</h2>
                        <p class="text-white/70">Please try again later.</p>
                    </div>
                `;
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
        }
        
        loadPledges();
        // Refresh every 60 seconds
        setInterval(loadPledges, 60000);
    </script>
</body>
</html>