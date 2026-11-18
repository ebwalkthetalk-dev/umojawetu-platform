<?php
// BOOKING WIZARD - FINAL WORKING VERSION
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/meta-tags.php';

$isLocal = Environment::isLocal();
?>

<!DOCTYPE html>
<html lang="en" data-is-admin="false">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets | <?php echo $site_name; ?></title>
    
    <!-- Meta Tags for SEO & Social Sharing -->
    <?php echo generateMetaTags([
        'title' => 'Book Tickets - Amazing Events',
        'description' => 'Book tickets for upcoming community events. Easy checkout with multiple ticket types.',
        'image' => '/assets/og-image.png',
        'url' => $site_url . '/public/booking.php'
    ]); ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#d12828">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Shared Styles -->
    <link rel="stylesheet" href="/assets/shared-styles.css">
    <link rel="stylesheet" href="/assets/event-card-hover.css">
    <style>
        .step { display: none; }
        .step.active { display: block; }
        .step-indicator {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin: 0 auto 8px;
        }
        .step-indicator.active { background: #dc2626; color: white; }
        .step-indicator.completed { background: #10b981; color: white; }
        .step-indicator.pending { background: #d1d5db; color: #4b5563; }
        .event-card { cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
        .event-card:hover { transform: translateY(-2px); border-color: #dc2626; }
        .event-card.selected { border-color: #dc2626 !important; background: #fef2f2 !important; }
        .ticket-btn { width: 36px; height: 36px; border-radius: 50%; background: #f3f4f6; border: 1px solid #e5e7eb; cursor: pointer; font-size: 18px; font-weight: bold; }
        .ticket-btn:hover { background: #dc2626; color: white; }
        .ticket-count { font-size: 20px; font-weight: bold; min-width: 40px; text-align: center; display: inline-block; }
        .env-badge { position: fixed; bottom: 10px; right: 10px; background: <?php echo $isLocal ? '#10b981' : '#dc2626'; ?>; color: white; padding: 5px 10px; border-radius: 5px; font-size: 11px; z-index: 999; }
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen" data-is-admin="false">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Progress Bar -->
            <div class="bg-gray-50 px-6 py-4 border-b">
                <div class="flex justify-between max-w-2xl mx-auto">
                    <div class="text-center flex-1"><div id="step1Dot" class="step-indicator pending">1</div><p class="text-xs">Event</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mt-5"></div>
                    <div class="text-center flex-1"><div id="step2Dot" class="step-indicator pending">2</div><p class="text-xs">Tickets</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mt-5"></div>
                    <div class="text-center flex-1"><div id="step3Dot" class="step-indicator pending">3</div><p class="text-xs">Details</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mt-5"></div>
                    <div class="text-center flex-1"><div id="step4Dot" class="step-indicator pending">4</div><p class="text-xs">Review</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mt-5"></div>
                    <div class="text-center flex-1"><div id="step5Dot" class="step-indicator pending">5</div><p class="text-xs">Payment</p></div>
                </div>
            </div>

            <!-- Step 1 -->
            <div id="step1" class="step active p-8">
                <h2 class="text-2xl font-bold mb-4">Select an Event</h2>
                <div id="eventsGrid" class="grid md:grid-cols-2 gap-4">
                    <div class="text-center py-10 col-span-full"><div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-red-600 border-t-transparent"></div><p>Loading events...</p></div>
                </div>
            </div>

            <!-- Step 2 -->
            <div id="step2" class="step p-8">
                <h2 class="text-2xl font-bold mb-4">Choose Your Tickets</h2>
                <div id="ticketSelector"></div>
                <div id="priceBreakdown" class="mt-6 bg-gray-50 rounded-xl p-4"></div>
            </div>

            <!-- Step 3 -->
            <div id="step3" class="step p-8">
                <h2 class="text-2xl font-bold mb-4">Your Details</h2>
                <div class="space-y-4">
                    <input type="text" id="fullName" placeholder="Full Name *" class="w-full border rounded-lg p-3">
                    <input type="email" id="email" placeholder="Email *" class="w-full border rounded-lg p-3">
                    <input type="tel" id="phone" placeholder="Phone" class="w-full border rounded-lg p-3">
                    <select id="memberStatus" class="w-full border rounded-lg p-3">
                        <option value="non-member">Non-member</option>
                        <option value="member">Member</option>
                    </select>
                </div>
            </div>

            <!-- Step 4 -->
            <div id="step4" class="step p-8">
                <h2 class="text-2xl font-bold mb-4">Review Your Booking</h2>
                <div id="reviewContent" class="bg-gray-50 rounded-xl p-6"></div>
                <button id="proceedToPayment" class="mt-6 w-full bg-red-600 text-white py-3 rounded-lg font-bold">Proceed to Payment →</button>
            </div>

            <!-- Step 5 -->
            <div id="step5" class="step p-8">
                <h2 class="text-2xl font-bold mb-4">Payment</h2>
                <div class="bg-red-50 p-4 rounded-xl text-center mb-4">
                    <span class="text-3xl font-bold text-red-600" id="paymentAmount">£0.00</span>
                </div>
                <button id="confirmBookingBtn" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold text-lg">✅ Confirm Booking</button>
                <button id="backToReviewBtn" class="mt-4 w-full text-gray-500 text-sm">← Back to Review</button>
            </div>

            <!-- Navigation -->
            <div class="px-8 py-6 bg-gray-50 border-t flex justify-between">
                <button id="prevBtn" class="px-6 py-2 bg-gray-300 rounded-lg hidden">← Back</button>
                <button id="nextBtn" class="px-6 py-2 bg-red-600 text-white rounded-lg ml-auto">Continue →</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 text-center max-w-md">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-2xl font-bold text-green-600">Booking Confirmed!</h3>
            <p id="bookingRef" class="mt-2 text-gray-600"></p>
            <button onclick="location.href='events.php'" class="mt-4 bg-red-600 text-white px-6 py-2 rounded-lg">Back to Events</button>
        </div>
    </div>

    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?></div>

    <script>
        // STATE
        let currentStep = 1;
        let events = [];
        let selectedEvent = null;
        let tickets = { adult: 0, child: 0, vip: 0 };
        let userDetails = { name: '', email: '', phone: '', memberStatus: 'non-member' };

        const API_BOOKING = '/umojawetu-platform/api/booking/bookings-wizard.php';
        const API_EVENTS = '/umojawetu-platform/api/events/events.php';

        // Load events
        async function loadEvents() {
            try {
                const res = await fetch(API_EVENTS);
                const data = await res.json();
                events = Array.isArray(data) ? data : (data.events || []);
                const active = events.filter(e => e.isActive === true);
                const grid = document.getElementById('eventsGrid');
                
                if (active.length === 0) {
                    grid.innerHTML = '<div class="text-center py-10">No events available</div>';
                    return;
                }
                
                grid.innerHTML = active.map(e => `
                    <div class="event-card event-card-enhanced border rounded-xl p-4 cursor-pointer" data-id="${e.id}" data-hover-effect="lift">
                        <div class="event-image-container" style="background-image: url('${e.imageUrl || '/assets/og-image.png'}'); margin:-1rem -1rem 1rem -1rem;">
                            <div class="image-overlay">
                                <button class="overlay-button" onclick="return false;">Learn More →</button>
                            </div>
                        </div>
                        <h3 class="event-title font-bold text-lg">${escapeHtml(e.title)}</h3>
                        
                        <div class="event-meta">
                            <span class="meta-item"><span class="icon" data-animate="bounce">📅</span> ${new Date(e.date).toLocaleDateString()}</span>
                            <span class="meta-item"><span class="icon" data-animate="slide">📍</span> ${escapeHtml(e.location || 'Venue TBC')}</span>
                        </div>
                        
                        <div class="event-countdown mt-2" data-countdown="${e.date}"></div>
                        
                        <div class="event-price mt-auto pt-3 border-t">
                            <span class="price-label">From</span>
                            <span class="price-value">£${e.priceAdult}</span>
                        </div>
                        
                        <button class="event-cta mt-4 bg-red-600 text-white px-4 py-2 rounded-lg font-bold" onclick="return false;">Book Now</button>
                    </div>
                `).join('');
                
                document.querySelectorAll('.event-card').forEach(card => {
                    card.onclick = () => {
                        document.querySelectorAll('.event-card').forEach(c => c.classList.remove('selected', 'border-red-500', 'bg-red-50'));
                        card.classList.add('selected', 'border-red-500', 'bg-red-50');
                        selectedEvent = events.find(e => e.id == card.dataset.id);
                        renderTickets();
                        goToStep(2);
                    };
                });
            } catch(e) {
                console.error(e);
                document.getElementById('eventsGrid').innerHTML = '<div class="text-center py-10 text-red-500">Failed to load events</div>';
            }
        }

        function escapeHtml(str) { 
            if (!str) return ''; 
            return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m])); 
        }

        // Tickets
        function renderTickets() {
            if (!selectedEvent) return;
            
            document.getElementById('ticketSelector').innerHTML = `
                <div class="border rounded-xl p-4 mb-4">
                    <div class="flex justify-between items-center">
                        <div><h3 class="font-bold text-lg">👤 Adult Tickets</h3><p class="text-gray-500">£${selectedEvent.priceAdult} each</p></div>
                        <div class="flex items-center gap-3">
                            <button class="ticket-btn" onclick="updateTicket('adult', -1)">−</button>
                            <span class="ticket-count" id="count-adult">0</span>
                            <button class="ticket-btn" onclick="updateTicket('adult', 1)">+</button>
                        </div>
                    </div>
                </div>
                <div class="border rounded-xl p-4">
                    <div class="flex justify-between items-center">
                        <div><h3 class="font-bold text-lg">👶 Child Tickets</h3><p class="text-gray-500">£${selectedEvent.priceChild} each</p></div>
                        <div class="flex items-center gap-3">
                            <button class="ticket-btn" onclick="updateTicket('child', -1)">−</button>
                            <span class="ticket-count" id="count-child">0</span>
                            <button class="ticket-btn" onclick="updateTicket('child', 1)">+</button>
                        </div>
                    </div>
                </div>
            `;
            updatePrice();
        }

        window.updateTicket = function(type, delta) {
            let newVal = tickets[type] + delta;
            if (newVal >= 0 && newVal <= 100) {
                tickets[type] = newVal;
                document.getElementById(`count-${type}`).innerText = newVal;
                updatePrice();
            }
        };

        // Price
        function calculateTotal() {
            if (!selectedEvent) return 0;
            let total = (tickets.adult * selectedEvent.priceAdult) + (tickets.child * selectedEvent.priceChild);
            if (selectedEvent.member_discount > 0 && userDetails.memberStatus === 'member') {
                total = total * (1 - selectedEvent.member_discount / 100);
            }
            return Math.max(0, total);
        }

        function updatePrice() {
            let total = calculateTotal();
            document.getElementById('priceBreakdown').innerHTML = `<div class="flex justify-between items-center"><span class="font-bold text-lg">💰 TOTAL</span><span class="text-2xl font-bold text-red-600">£${total.toFixed(2)}</span></div>`;
            document.getElementById('paymentAmount').innerText = `£${total.toFixed(2)}`;
        }

        // Navigation
        function goToStep(step) {
            document.querySelectorAll('.step').forEach((s, i) => s.classList.toggle('active', i+1 === step));
            currentStep = step;
            
            for(let i = 1; i <= 5; i++) {
                let dot = document.getElementById(`step${i}Dot`);
                if (i < step) {
                    dot.classList.add('completed');
                    dot.classList.remove('active', 'pending');
                    dot.innerText = '✓';
                } else if (i === step) {
                    dot.classList.add('active');
                    dot.classList.remove('completed', 'pending');
                    dot.innerText = i;
                } else {
                    dot.classList.add('pending');
                    dot.classList.remove('active', 'completed');
                    dot.innerText = i;
                }
            }
            
            document.getElementById('prevBtn').classList.toggle('hidden', step === 1);
            document.getElementById('nextBtn').classList.toggle('hidden', step === 5);
            if (step === 4) displayReview();
        }

        function nextStep() {
            if (currentStep === 1 && !selectedEvent) {
                alert('Please select an event');
                return;
            }
            if (currentStep === 2 && tickets.adult === 0 && tickets.child === 0 && tickets.vip === 0) {
                alert('Please select at least one ticket');
                return;
            }
            if (currentStep === 3) {
                let name = document.getElementById('fullName').value.trim();
                let email = document.getElementById('email').value.trim();
                if (!name || !email) {
                    alert('Please enter your name and email');
                    return;
                }
                userDetails.name = name;
                userDetails.email = email;
                userDetails.phone = document.getElementById('phone').value.trim();
                userDetails.memberStatus = document.getElementById('memberStatus').value;
                updatePrice();
            }
            if (currentStep < 5) goToStep(currentStep + 1);
        }

        function prevStep() {
            if (currentStep > 1) goToStep(currentStep - 1);
        }

        function displayReview() {
            let total = calculateTotal();
            document.getElementById('reviewContent').innerHTML = `
                <div><strong>Event:</strong> ${escapeHtml(selectedEvent.title)}</div>
                <div class="mt-2"><strong>Tickets:</strong> 👤 ${tickets.adult} 👶 ${tickets.child}</div>
                <div class="mt-2"><strong>Member Status:</strong> ${userDetails.memberStatus === 'member' ? 'Member' : 'Non-member'}</div>
                <div class="mt-2"><strong>Main Contact:</strong> ${escapeHtml(userDetails.name)} (${escapeHtml(userDetails.email)})</div>
                <div class="border-t pt-2 mt-2 font-bold">Total to Pay: £${total.toFixed(2)}</div>
            `;
        }

        // Submit booking - FIXED with proper button handler
        async function submitBooking() {
            console.log("submitBooking called!");
            
            const payload = {
                eventId: selectedEvent.id,
                fullName: userDetails.name,
                email: userDetails.email,
                phone: userDetails.phone,
                memberStatus: userDetails.memberStatus,
                adults: tickets.adult,
                children: tickets.child,
                vip: tickets.vip,
                totalAmount: calculateTotal()
            };
            
            console.log("Sending payload:", payload);
            
            try {
                const response = await fetch(API_BOOKING, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                console.log("Response:", result);
                
                if (result.success) {
                    document.getElementById('bookingRef').innerHTML = `Reference: ${result.bookingReference}`;
                    document.getElementById('successModal').classList.add('flex');
                } else {
                    alert('Booking failed: ' + (result.error || 'Unknown error'));
                }
            } catch(error) {
                console.error("Error:", error);
                alert('Network error: ' + error.message);
            }
        }

        // Set up event listeners - IMPORTANT: Wait for DOM to load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nextBtn').onclick = nextStep;
            document.getElementById('prevBtn').onclick = prevStep;
            document.getElementById('proceedToPayment').onclick = () => goToStep(5);
            document.getElementById('backToReviewBtn').onclick = () => goToStep(4);
            
            // FIXED: Make sure the confirm button has the click handler
            const confirmBtn = document.getElementById('confirmBookingBtn');
            if (confirmBtn) {
                confirmBtn.onclick = submitBooking;
                console.log("Confirm button handler attached");
            } else {
                console.error("Confirm button not found!");
            }
        });
        
        // Initialize
        loadEvents();
        goToStep(1);
        
        // Show confetti on successful booking completion
        function showConfetti() {
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#d12828', '#fbbf24', '#ffffff'],
                    duration: 3000,
                    scalar: 1.2
                });
            }
        }
        
        // Listen for successful booking
        window.addEventListener('bookingSuccess', showConfetti);
    </script>
    
    <!-- Confetti Library for celebration -->
    <script src="https://cdn.jsdelivr.net/npm/confetti@2.0.0/dist/confetti.min.js"></script>
    
    <!-- Countdown Timer Component -->
    <script src="/assets/countdown-timer.js"></script>
    
    <!-- Social Sharing Component -->
    <script src="/assets/social-sharing.js"></script>
    
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