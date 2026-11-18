<?php
// Public Pledge Form
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
    <title>Make a Pledge | <?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .form-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .form-card:hover { transform: translateY(-5px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.15); }
        .amount-btn { transition: all 0.2s ease; cursor: pointer; }
        .amount-btn.selected { background: #e74c3c !important; color: white !important; border-color: #e74c3c !important; }
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
                    <a href="pledge.php" class="nav-link px-4 py-2 bg-red-600 text-white rounded-lg font-medium transition shadow-md">🤝 Pledge</a>
                    <a href="request.php" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">📝 Request</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12 max-w-2xl">
        <div class="form-card bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white text-center">
                <div class="text-5xl mb-3">🤝</div>
                <h1 class="text-3xl font-bold">Make a Pledge</h1>
                <p class="text-red-100 mt-1">Support our community initiatives</p>
            </div>
            
            <div class="p-8">
                <div id="successMessage" class="hidden bg-green-100 text-green-700 p-4 rounded-lg mb-4"></div>
                <div id="errorMessage" class="hidden bg-red-100 text-red-700 p-4 rounded-lg mb-4"></div>
                
                <form id="pledgeForm">
                    <!-- Member Status -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Are you a Umoja Wetu member?</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_member" value="1" class="w-4 h-4 text-red-600"> 
                                <span>✅ Yes, I'm a member</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_member" value="0" checked class="w-4 h-4 text-red-600"> 
                                <span>🤝 No, but I want to support</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Membership ID (conditional) -->
                    <div id="membershipField" class="mb-6 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Membership ID</label>
                        <input type="text" id="membership_id" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500" placeholder="Enter your membership ID">
                    </div>
                    
                    <!-- Personal Details -->
                    <div class="grid md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" id="pledger_name" required class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" id="email" required class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" id="phone" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>
                    
                    <!-- Pledge Amount -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pledge Amount (£) *</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                            <button type="button" data-amount="25" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-red-500 transition">£25</button>
                            <button type="button" data-amount="50" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-red-500 transition">£50</button>
                            <button type="button" data-amount="100" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-red-500 transition">£100</button>
                            <button type="button" data-amount="250" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-red-500 transition">£250</button>
                        </div>
                        <div class="flex gap-2">
                            <span class="bg-gray-100 px-3 py-3 rounded-lg">£</span>
                            <input type="number" id="custom_amount" step="0.01" placeholder="Custom amount" class="flex-1 border rounded-lg p-3 focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                        <div class="grid md:grid-cols-3 gap-3">
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:border-red-500">
                                <input type="radio" name="payment_method" value="stripe" checked> 💳 Credit Card (Stripe)
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:border-red-500">
                                <input type="radio" name="payment_method" value="bank_transfer"> 🏦 Bank Transfer
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:border-red-500">
                                <input type="radio" name="payment_method" value="cash"> 💵 Cash
                            </label>
                        </div>
                    </div>
                    
                    <!-- Stripe Card Element (shown for stripe) -->
                    <div id="stripeElement" class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Details</label>
                        <div id="card-element" class="border rounded-lg p-3 bg-white"></div>
                        <div id="card-errors" class="text-red-500 text-sm mt-2"></div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea id="notes" rows="3" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-red-500" placeholder="Any additional information..."></textarea>
                    </div>
                    
                    <button type="submit" id="submitBtn" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold text-lg hover:bg-red-700 transition">
                        🤝 Submit Pledge
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?> Environment</div>
    
    <script>
        let stripe = null;
        let cardElement = null;
        let selectedAmount = 0;
        
        const isLocal = <?php echo $isLocal ? 'true' : 'false'; ?>;
        const apiBaseUrl = isLocal ? 'http://localhost/umojawetu-platform/api' : 'https://event.umojawetu.com/api';
        
        // Toggle membership field
        document.querySelectorAll('input[name="is_member"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('membershipField').style.display = this.value === '1' ? 'block' : 'none';
            });
        });
        
        // Amount selection
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedAmount = parseInt(this.dataset.amount);
                document.getElementById('custom_amount').value = '';
            });
        });
        
        document.getElementById('custom_amount').addEventListener('input', function() {
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
            selectedAmount = parseFloat(this.value) || 0;
        });
        
        // Initialize Stripe
        async function initStripe() {
            try {
                const response = await fetch(`${apiBaseUrl}/stripe/stripe-config.php`);
                const config = await response.json();
                stripe = Stripe(config.publishable_key);
                const elements = stripe.elements();
                cardElement = elements.create('card');
                cardElement.mount('#card-element');
                cardElement.on('change', function(event) {
                    document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
                });
            } catch (e) {
                console.error('Stripe init error:', e);
            }
        }
        
        // Submit pledge
        document.getElementById('pledgeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('pledger_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const isMember = document.querySelector('input[name="is_member"]:checked').value === '1';
            const membershipId = document.getElementById('membership_id').value.trim();
            const amount = selectedAmount > 0 ? selectedAmount : parseFloat(document.getElementById('custom_amount').value);
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const notes = document.getElementById('notes').value;
            
            if (!name || !email) {
                showError('Please enter your name and email');
                return;
            }
            
            if (!amount || amount <= 0) {
                showError('Please select or enter a pledge amount');
                return;
            }
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            const payload = {
                pledger_name: name,
                email: email,
                phone: phone,
                is_member: isMember,
                membership_id: membershipId,
                amount_pledged: amount,
                payment_method: paymentMethod,
                notes: notes
            };
            
            try {
                const response = await fetch(`${apiBaseUrl}/pledges/create-pledge.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(`Thank you for your pledge of £${amount.toFixed(2)}! Reference: ${result.pledge_reference}`);
                    document.getElementById('pledgeForm').reset();
                    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
                } else {
                    showError(result.error || 'Failed to submit pledge');
                }
            } catch (error) {
                showError('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = '🤝 Submit Pledge';
            }
        });
        
        function showSuccess(message) {
            const el = document.getElementById('successMessage');
            el.innerHTML = '✅ ' + message;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 5000);
        }
        
        function showError(message) {
            const el = document.getElementById('errorMessage');
            el.innerHTML = '❌ ' + message;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 5000);
        }
        
        initStripe();
    </script>
</body>
</html>