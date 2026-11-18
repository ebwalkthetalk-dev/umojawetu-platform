<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Book Tickets | Umoja Wetu</title>
    <link rel="icon" type="image/png" href="/Logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .step { transition: all 0.3s ease; }
        .step-indicator {
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
        }
        .step-indicator.active { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .step-indicator.completed { background: linear-gradient(135deg, #27ae60, #219a52); }
        .event-card { transition: all 0.2s ease; cursor: pointer; }
        .event-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .event-card.selected { border-color: #e74c3c !important; background-color: #fff5f5 !important; }
        .ticket-card { transition: all 0.2s ease; cursor: pointer; }
        .ticket-card:hover { border-color: #e74c3c; background: #fef2f2; }
        .payment-option { transition: all 0.2s ease; cursor: pointer; }
        .payment-option.selected { border-color: #e74c3c; background: #fff5f5; }
        .StripeElement { padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: white; }
        .StripeElement--focus { border-color: #e74c3c; }
        .nav-link { transition: all 0.2s ease; }
        .nav-link:hover { transform: translateY(-2px); }
        .egg-logo { width: 120px; height: 160px; object-fit: contain; border-radius: 50% / 60% 60% 40% 40%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s ease; }
        .egg-logo:hover { transform: scale(1.05); }
        @media (max-width: 640px) { .egg-logo { width: 80px; height: 107px; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
        .edit-btn { background: #f3f4f6; border: 1px solid #e5e7eb; padding: 4px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s; }
        .edit-btn:hover { background: #e5e7eb; }
        .attendee-section { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
        .attendee-row { display: flex; align-items: center; gap: 8px; padding: 8px; background-color: #f9fafb; border-radius: 8px; margin-bottom: 8px; }
        .attendee-row:hover { background-color: #fef2f2; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <div class="text-center mb-8">
            <img src="/Logo.png" alt="UMOJA WETU Logo" class="egg-logo mx-auto mb-4">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Book Your Tickets</h1>
            <p class="text-white/80">Secure your spot at our upcoming events</p>
        </div>

        <div class="bg-white/10 backdrop-blur-md rounded-t-2xl px-6 py-3 border-b border-white/20">
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="/gallery.html" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">🖼️ Gallery</a>
                <a href="/events.html" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">📅 Events</a>
                <a href="/booking-wizard.html" class="nav-link px-4 py-2 bg-red-600 text-white rounded-lg font-medium transition shadow-md">🎟️ Book</a>
                <a href="/request-form.html" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">📝 Request</a>
                <a href="/request-status.html" class="nav-link px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition">🔍 Status</a>
            </div>
        </div>

        <div class="bg-white rounded-b-2xl shadow-2xl overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b">
                <div class="flex justify-between items-center max-w-2xl mx-auto">
                    <div class="text-center flex-1"><div id="step1Dot" class="step-indicator w-10 h-10 mx-auto rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mb-2">1</div><p class="text-xs font-medium text-gray-600">Select Event</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2" id="line1"></div>
                    <div class="text-center flex-1"><div id="step2Dot" class="step-indicator w-10 h-10 mx-auto rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mb-2">2</div><p class="text-xs font-medium text-gray-600">Choose Tickets</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2" id="line2"></div>
                    <div class="text-center flex-1"><div id="step3Dot" class="step-indicator w-10 h-10 mx-auto rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mb-2">3</div><p class="text-xs font-medium text-gray-600">Your Details</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2" id="line3"></div>
                    <div class="text-center flex-1"><div id="step4Dot" class="step-indicator w-10 h-10 mx-auto rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mb-2">4</div><p class="text-xs font-medium text-gray-600">Review</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2" id="line4"></div>
                    <div class="text-center flex-1"><div id="step5Dot" class="step-indicator w-10 h-10 mx-auto rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mb-2">5</div><p class="text-xs font-medium text-gray-600">Payment</p></div>
                </div>
            </div>

            <!-- Step 1: Select Event -->
            <div id="step1" class="step p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Select an Event</h2>
                <p class="text-gray-600 mb-6">Choose from our upcoming events below</p>
                <div id="eventsGrid" class="grid md:grid-cols-2 gap-4">
                    <div class="text-center text-gray-500 py-10 col-span-full">Loading events...</div>
                </div>
            </div>

            <!-- Step 2: Choose Tickets -->
            <div id="step2" class="step p-8 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Choose Your Tickets</h2>
                <p class="text-gray-600 mb-6">Select the number of tickets for each category</p>
                <div id="ticketSelector"></div>
                <div id="priceBreakdown" class="mt-6 bg-gray-50 rounded-xl p-4"></div>
            </div>

            <!-- Step 3: Your Details -->
            <div id="step3" class="step p-8 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Your Details</h2>
                <p class="text-gray-600 mb-6">Please provide your contact information (Payer details)</p>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label><input type="text" id="fullName" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label><input type="email" id="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label><input type="tel" id="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Are you a Umoja Wetu member?</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="memberStatus" value="member" class="w-4 h-4 text-red-600"><span class="text-gray-700">✅ Yes, I am a member</span></label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="memberStatus" value="non-member" class="w-4 h-4 text-red-600" checked><span class="text-gray-700">🤝 No, but I want to support</span></label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">This helps us understand our community better.</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Review -->
            <div id="step4" class="step p-8 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Review Your Booking</h2>
                <p class="text-gray-600 mb-6">Please review your details before proceeding to payment</p>
                <div id="reviewContent" class="bg-gray-50 rounded-xl p-6"></div>
                <div class="mt-6 flex justify-center">
                    <button id="proceedToPaymentBtn" class="px-8 py-3 bg-red-600 text-white rounded-lg font-bold shadow-md hover:bg-red-700 transition">💳 Proceed to Payment</button>
                </div>
            </div>

            <!-- Step 5: Payment -->
            <div id="step5" class="step p-8 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Payment</h2>
                <p class="text-gray-600 mb-6">Complete your payment to confirm your booking</p>
                <div id="paymentTotal" class="bg-red-50 rounded-xl p-4 mb-6 text-center">
                    <span class="text-gray-700">Total to pay:</span>
                    <span id="paymentTotalAmount" class="text-3xl font-bold text-red-600 ml-2">£0.00</span>
                </div>
                <div class="space-y-3">
                    <div class="payment-option p-4 border-2 rounded-xl cursor-pointer selected" data-method="stripe">
                        <input type="radio" name="paymentMethod" value="stripe" class="w-5 h-5 text-red-500 mr-4" checked> 💳 Credit/Debit Card (Stripe) - Pay now securely
                    </div>
                </div>
                <div id="stripe-container" class="mt-4">
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Card Details</label>
                            <div id="card-element" class="py-2"></div>
                        </div>
                        <div id="card-errors" class="text-red-500 text-sm mt-2"></div>
                        <button id="stripe-pay-btn" class="mt-4 w-full bg-green-600 text-white py-3 rounded-lg font-bold text-lg hidden">✅ Pay Now</button>
                    </div>
                </div>
                <div class="mt-4 flex justify-between"><button id="backToReviewBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg">← Back to Review</button></div>
            </div>

            <div class="px-8 py-6 bg-gray-50 border-t flex justify-between">
                <button id="prevBtn" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hidden">← Back</button>
                <button id="nextBtn" class="px-6 py-2 bg-red-600 text-white rounded-lg shadow-md ml-auto">Continue →</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md text-center">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-2xl font-bold text-green-600 mb-2">Booking Successful!</h3>
            <div id="successMessage" class="text-gray-600 mb-4 text-left bg-gray-50 p-4 rounded-lg"></div>
            <p id="bookingRef" class="text-sm font-mono bg-gray-100 p-2 rounded mb-4"></p>
            <p class="text-sm text-gray-500 mb-4">📧 A confirmation email with your QR code has been sent to your email address.</p>
            <button onclick="location.href='/events.html'" class="px-6 py-2 bg-red-600 text-white rounded-lg">Back to Events</button>
        </div>
    </div>

    <script>
        // ============================================
        // STATE MANAGEMENT
        // ============================================
        let currentStep = 1;
        let events = [];
        let selectedEvent = null;
        let tickets = { adult: 0, child: 0, vip: 0 };
        let volunteerAmount = 0;
        let userDetails = { name: '', email: '', phone: '', memberStatus: 'non-member' };
        let adultAttendees = [];
        let childAttendees = [];
        let vipAttendees = [];
        
        let stripe = null;
        let elements = null;
        let cardElement = null;
        let currentClientSecret = null;

        // ============================================
        // EVENT LOADING
        // ============================================
        async function loadEvents() {
            const grid = document.getElementById('eventsGrid');
            grid.innerHTML = '<div class="text-center text-gray-500 py-10 col-span-full">Loading events...</div>';
            
            try {
                const res = await fetch('/api/events.php');
                const data = await res.json();
                events = Array.isArray(data) ? data : (data.events || []);
                const active = events.filter(e => e.isActive === true && e.visibility === 'public');
                
                if (active.length === 0) {
                    grid.innerHTML = '<div class="text-center text-gray-500 py-10 col-span-full">No active events available</div>';
                    return;
                }
                
                grid.innerHTML = active.map(event => {
                    let locationText = event.address || event.location || '';
                    if (locationText.includes('||')) locationText = locationText.split('||')[0];
                    return `
                        <div class="event-card border rounded-xl p-4 cursor-pointer hover:border-red-500 transition" data-id="${event.id}">
                            <h3 class="font-bold text-lg">${escapeHtml(event.title)}</h3>
                            <p class="text-sm text-gray-500 mt-1">📅 ${new Date(event.date).toLocaleString()}</p>
                            ${locationText ? `<p class="text-sm text-gray-500">📍 ${escapeHtml(locationText)}</p>` : ''}
                            <a href="/umojawetu-platform/public/event-details.php?id=${event.id}" target="_blank" class="text-xs text-red-600 hover:text-red-700 hover:underline mt-2 inline-block">📖 View Details & Updates →</a>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full">👤 Adult: £${event.priceAdult}</span>
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full">👶 Child: £${event.priceChild}</span>
                                ${event.priceVIP > 0 ? `<span class="bg-purple-100 text-purple-700 px-2 py-1 rounded-full">⭐ VIP: £${event.priceVIP}</span>` : ''}
                            </div>
                            ${event.firstChildFree ? '<div class="text-xs text-green-600 mt-2">🎉 First Child FREE!</div>' : ''}
                        </div>
                    `;
                }).join('');
                
                document.querySelectorAll('.event-card').forEach(card => {
                    card.onclick = () => {
                        document.querySelectorAll('.event-card').forEach(c => c.classList.remove('border-red-500', 'bg-red-50'));
                        card.classList.add('border-red-500', 'bg-red-50');
                        selectedEvent = events.find(e => e.id === parseInt(card.dataset.id));
                    };
                });
            } catch(e) {
                console.error('Error:', e);
                grid.innerHTML = '<div class="text-center text-red-500 py-10 col-span-full">Failed to load events. Please refresh.</div>';
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
        }

        // ============================================
        // ATTENDEE FUNCTIONS
        // ============================================
        function renderAttendeeFields(type) {
            const container = document.getElementById(`${type}Attendees`);
            if (!container) return;
            let attendees = type === 'adult' ? adultAttendees : (type === 'child' ? childAttendees : vipAttendees);
            let label = type === 'adult' ? 'Adult' : (type === 'child' ? 'Child' : 'VIP Guest');
            
            if (attendees.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-400 italic">No attendees added yet. Use + to add.</p>';
                return;
            }
            
            container.innerHTML = attendees.map((name, idx) => `
                <div class="attendee-row">
                    <span class="text-sm font-medium w-24">${label} #${idx + 1}:</span>
                    <input type="text" placeholder="Full name" value="${escapeHtml(name)}" onchange="updateAttendeeName('${type}', ${idx}, this.value)" class="flex-1 px-3 py-1 border rounded-lg text-sm">
                    <button type="button" onclick="removeAttendee('${type}', ${idx})" class="text-red-500 hover:text-red-700 px-2 py-1"><i class="fas fa-trash-alt"></i></button>
                </div>
            `).join('');
        }
        
        function updateAttendeeName(type, index, name) {
            if (type === 'adult') adultAttendees[index] = name;
            else if (type === 'child') childAttendees[index] = name;
            else vipAttendees[index] = name;
        }
        
        function addAttendee(type) {
            let attendees = type === 'adult' ? adultAttendees : (type === 'child' ? childAttendees : vipAttendees);
            let maxCount = tickets[type];
            if (attendees.length >= maxCount) { alert(`Maximum ${maxCount} ${type}(s) already added`); return; }
            if (type === 'adult') adultAttendees.push('');
            else if (type === 'child') childAttendees.push('');
            else vipAttendees.push('');
            renderAttendeeFields(type);
        }
        
        function removeAttendee(type, index) {
            if (type === 'adult') adultAttendees.splice(index, 1);
            else if (type === 'child') childAttendees.splice(index, 1);
            else vipAttendees.splice(index, 1);
            renderAttendeeFields(type);
        }

        // ============================================
        // VOLUNTEER CHECKBOX HANDLER
        // ============================================
        function initVolunteerCheckbox() {
            const checkbox = document.getElementById('volunteerCheckbox');
            const options = document.getElementById('volunteerOptions');
            const infoBox = document.getElementById('volunteerInfoBox');
            const amountSelect = document.getElementById('volunteerAmount');
            
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        options.style.display = 'block';
                        infoBox.style.display = 'block';
                        volunteerAmount = parseInt(amountSelect.value);
                        updateVolunteerInfo();
                    } else {
                        options.style.display = 'none';
                        infoBox.style.display = 'none';
                        volunteerAmount = 0;
                        document.getElementById('volunteerInfo').innerHTML = '💝 Select an amount to support our cause';
                        updatePrice();
                    }
                });
                
                if (amountSelect) {
                    amountSelect.addEventListener('change', updateVolunteerInfo);
                }
            }
        }

        function updateVolunteerInfo() {
            const amountSelect = document.getElementById('volunteerAmount');
            const checkbox = document.getElementById('volunteerCheckbox');
            
            if (!amountSelect || !checkbox || !checkbox.checked) {
                volunteerAmount = 0;
                return;
            }
            
            volunteerAmount = parseInt(amountSelect.value);
            const msg = volunteerAmount >= 100 ? `🎁 Thank you for your <strong>£${volunteerAmount}</strong> contribution!` : `💝 Thank you for your <strong>£${volunteerAmount}</strong> contribution! Every bit helps our community.`;
            document.getElementById('volunteerInfo').innerHTML = msg;
            updatePrice();
        }

        // ============================================
        // TICKET SELECTION
        // ============================================
        function renderTickets() {
            if (!selectedEvent) return;
            let html = '<div class="space-y-4">';
            
            // Volunteer Contribution Section - OPTIONAL (with checkbox)
            html += `
            <div class="ticket-card border-2 border-orange-300 rounded-xl p-4 bg-gradient-to-r from-orange-50 to-yellow-50">
                <div class="flex flex-col md:flex-row md:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="volunteerCheckbox" class="w-5 h-5 text-orange-600 rounded">
                            <i class="fas fa-hand-holding-heart text-2xl text-orange-600"></i>
                            <h3 class="font-bold text-xl text-orange-700">🤝 VOLUNTEER</h3>
                            <span class="text-xs bg-orange-600 text-white px-2 py-0.5 rounded-full font-bold">Optional</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Support our cause with a voluntary contribution (completely optional)</p>
                        
                        <div id="volunteerOptions" style="display: none;" class="mt-3">
                            <label class="text-sm font-bold text-gray-800 block mb-1">💷 Contribution Amount (£)</label>
                            <select id="volunteerAmount" class="w-32 px-3 py-2 border-2 border-orange-200 rounded-lg mt-1 font-semibold bg-white">
                                <option value="5">£5</option>
                                <option value="10">£10</option>
                                <option value="15">£15</option>
                                <option value="20">£20</option>
                                <option value="25">£25</option>
                                <option value="30">£30</option>
                                <option value="40">£40</option>
                                <option value="50">£50</option>
                                <option value="60">£60</option>
                                <option value="70">£70</option>
                                <option value="80">£80</option>
                                <option value="90">£90</option>
                                <option value="100">£100</option>
                                <option value="150">£150</option>
                                <option value="200">£200</option>
                                <option value="250">£250</option>
                                <option value="300">£300</option>
                                <option value="400">£400</option>
                                <option value="500">£500</option>
                                <option value="1000">£1000</option>
                            </select>
                        </div>
                        
                        <div id="volunteerInfoBox" class="mt-3 p-3 bg-orange-100 rounded-lg" style="display: none;">
                            <p class="text-sm font-semibold text-orange-800">🎁 Your Contribution Includes:</p>
                            <p class="text-sm text-orange-700" id="volunteerInfo">Select an amount to support our cause</p>
                        </div>
                    </div>
                </div>
            </div>`;
            
            html += `<div class="ticket-card border rounded-xl p-4"><div class="flex justify-between items-center mb-3"><div><h3 class="font-bold text-lg">👤 Adult (18+)</h3><p class="text-sm">£${selectedEvent.priceAdult} each</p></div>
                    <div class="flex gap-3"><button type="button" onclick="changeTicket('adult',-1)" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300">−</button>
                    <span id="adultCount" class="w-12 text-center text-lg font-semibold">0</span>
                    <button type="button" onclick="changeTicket('adult',1)" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300">+</button></div></div>
                    <div id="adultAttendees" class="attendee-section"></div>
                    <button type="button" onclick="addAttendee('adult')" class="text-blue-600 text-sm mt-2 hover:text-blue-800">+ Add Adult Name</button></div>`;
            
            html += `<div class="ticket-card border rounded-xl p-4"><div class="flex justify-between items-center mb-3"><div><h3 class="font-bold text-lg">👶 Child (Under 18)</h3><p class="text-sm">£${selectedEvent.priceChild} each</p>${selectedEvent.firstChildFree ? '<p class="text-xs text-green-600 mt-1">🎉 First Child FREE!</p>' : ''}</div>
                    <div class="flex gap-3"><button type="button" onclick="changeTicket('child',-1)" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300">−</button>
                    <span id="childCount" class="w-12 text-center text-lg font-semibold">0</span>
                    <button type="button" onclick="changeTicket('child',1)" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300">+</button></div></div>
                    <div id="childAttendees" class="attendee-section"></div>
                    <button type="button" onclick="addAttendee('child')" class="text-blue-600 text-sm mt-2 hover:text-blue-800">+ Add Child Name</button></div>`;
            
            if (selectedEvent.priceVIP > 0) {
                html += `<div class="ticket-card border rounded-xl p-4 bg-purple-50">
                            <div class="flex justify-between items-center mb-3">
                                <div><h3 class="font-bold text-lg text-purple-800">⭐ VIP Experience</h3><p class="text-sm">£${selectedEvent.priceVIP} <strong>PER TABLE</strong> (includes up to 10 guests)</p><p class="text-xs text-red-600 font-bold">⚠️ Maximum 10 VIP guests total</p></div>
                                <div class="flex gap-3"><button type="button" onclick="changeTicket('vip',-1)" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300">−</button>
                                <span id="vipCount" class="w-12 text-center text-lg font-semibold">0</span>
                                <button type="button" onclick="changeTicket('vip',1)" class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300">+</button></div>
                            </div>
                            <div id="vipAttendees" class="attendee-section"></div>
                            <button type="button" onclick="addAttendee('vip')" class="text-blue-600 text-sm mt-2 hover:text-blue-800" id="addVipBtn">+ Add VIP Name</button>
                            <p class="text-xs text-gray-500 mt-2">💡 Price is per table, not per person. Add up to 10 VIP guests.</p>
                        </div>`;
            }
            
            html += '</div>';
            document.getElementById('ticketSelector').innerHTML = html;
            
            // Initialize volunteer checkbox after rendering
            initVolunteerCheckbox();
            
            adultAttendees = [];
            childAttendees = [];
            vipAttendees = [];
            updatePrice();
        }

        function changeTicket(type, delta) {
            let maxAllowed = type === 'vip' ? 10 : 100;
            let newVal = (tickets[type] || 0) + delta;
            if (newVal >= 0 && newVal <= maxAllowed) {
                tickets[type] = newVal;
                document.getElementById(`${type}Count`).innerText = newVal;
                let attendees = type === 'adult' ? adultAttendees : (type === 'child' ? childAttendees : vipAttendees);
                while (attendees.length > newVal) attendees.pop();
                while (attendees.length < newVal) attendees.push('');
                renderAttendeeFields(type);
                updatePrice();
            } else if (newVal > maxAllowed) {
                alert(`⚠️ Maximum ${maxAllowed} ${type}(s) allowed per booking`);
                document.getElementById(`${type}Count`).innerText = tickets[type];
            }
        }

        function calculateTotal() {
            if (!selectedEvent) return 0;
            let payable = tickets.child;
            if (selectedEvent.firstChildFree && tickets.child > 0) { payable = tickets.child - 1; if (payable < 0) payable = 0; }
            let vipTotal = (tickets.vip > 0) ? (selectedEvent.priceVIP || 0) : 0;
            
            // Only include volunteer amount if checkbox is checked
            const volunteerCheckbox = document.getElementById('volunteerCheckbox');
            const includeVolunteer = volunteerCheckbox ? volunteerCheckbox.checked : false;
            const finalVolunteerAmount = includeVolunteer ? volunteerAmount : 0;
            
            return (tickets.adult * selectedEvent.priceAdult) + (payable * selectedEvent.priceChild) + vipTotal + finalVolunteerAmount;
        }

        function updatePrice() {
            if (!selectedEvent) return;
            let adultTotal = tickets.adult * selectedEvent.priceAdult;
            let payable = tickets.child, free = 0;
            if (selectedEvent.firstChildFree && tickets.child > 0) { free = 1; payable = tickets.child - 1; if (payable < 0) payable = 0; }
            let childTotal = payable * selectedEvent.priceChild;
            let vipTotal = (tickets.vip > 0) ? (selectedEvent.priceVIP || 0) : 0;
            
            // Only include volunteer amount if checkbox is checked
            const volunteerCheckbox = document.getElementById('volunteerCheckbox');
            const includeVolunteer = volunteerCheckbox ? volunteerCheckbox.checked : false;
            const finalVolunteerAmount = includeVolunteer ? volunteerAmount : 0;
            
            let grandTotal = adultTotal + childTotal + vipTotal + finalVolunteerAmount;
            
            document.getElementById('priceBreakdown').innerHTML = `<div class="space-y-2">
                <div class="flex justify-between"><span>👤 Adults (${tickets.adult} × £${selectedEvent.priceAdult})</span><span>£${adultTotal.toFixed(2)}</span></div>
                <div class="flex justify-between"><span>👶 Children (${tickets.child} total)</span><span>£${(tickets.child * selectedEvent.priceChild).toFixed(2)}</span></div>
                ${free > 0 ? `<div class="flex justify-between text-green-600"><span>🎉 First Child FREE!</span><span>-£${selectedEvent.priceChild.toFixed(2)}</span></div>` : ''}
                ${tickets.vip > 0 ? `<div class="flex justify-between"><span>⭐ VIP Table (up to 10 guests)</span><span>£${vipTotal.toFixed(2)}</span></div>` : ''}
                ${includeVolunteer && volunteerAmount > 0 ? `<div class="flex justify-between font-semibold text-orange-700"><span>🤝 VOLUNTEER CONTRIBUTION</span><span>£${volunteerAmount.toFixed(2)}</span></div>` : ''}
                <div class="border-t-2 border-red-300 pt-3 mt-2 bg-red-50 -mx-2 px-2 rounded-lg">
                    <div class="flex justify-between items-center"><span class="font-bold text-lg">💰 TOTAL TO PAY</span><span class="text-2xl font-bold text-red-600">£${grandTotal.toFixed(2)}</span></div>
                </div>
            </div>`;
        }

        // ============================================
        // REVIEW DISPLAY
        // ============================================
        function displayReview() {
            if (!selectedEvent) return;
            let total = calculateTotal();
            let attendeesHtml = '';
            if (adultAttendees.length > 0) attendeesHtml += `<p><strong>👤 Adults:</strong> ${adultAttendees.map((n,i) => n || `Adult ${i+1}`).join(', ')}</p>`;
            if (childAttendees.length > 0) attendeesHtml += `<p><strong>👶 Children:</strong> ${childAttendees.map((n,i) => n || `Child ${i+1}`).join(', ')}</p>`;
            if (vipAttendees.length > 0) attendeesHtml += `<p><strong>⭐ VIP Guests:</strong> ${vipAttendees.map((n,i) => n || `VIP ${i+1}`).join(', ')}</p>`;
            
            // Check if volunteer is selected
            const volunteerCheckbox = document.getElementById('volunteerCheckbox');
            const includeVolunteer = volunteerCheckbox ? volunteerCheckbox.checked : false;
            
            document.getElementById('reviewContent').innerHTML = `<div class="space-y-4">
                <div class="bg-white p-4 border rounded-lg shadow-sm"><div class="flex justify-between items-center"><h3 class="font-bold text-lg">📅 EVENT</h3><button class="edit-btn" onclick="goToStep(1)">Edit</button></div><p class="mt-1">${escapeHtml(selectedEvent.title)}<br><span class="text-gray-500">${new Date(selectedEvent.date).toLocaleString()}</span></p></div>
                <div class="bg-white p-4 border rounded-lg shadow-sm"><div class="flex justify-between items-center"><h3 class="font-bold text-lg">🎟️ TICKETS & ATTENDEES</h3><button class="edit-btn" onclick="goToStep(2)">Edit</button></div>${attendeesHtml}<div class="mt-2"><p>👤 Adults: ${tickets.adult} × £${selectedEvent.priceAdult} = £${(tickets.adult * selectedEvent.priceAdult).toFixed(2)}</p></div>
                <div class="mt-1"><p>⭐ VIP Table: £${(tickets.vip > 0 ? selectedEvent.priceVIP : 0).toFixed(2)} (up to 10 guests)</p></div>
                ${includeVolunteer && volunteerAmount > 0 ? `<div class="mt-1"><p>🤝 Volunteer Contribution: £${volunteerAmount.toFixed(2)}</p></div>` : ''}
                </div>
                <div class="bg-white p-4 border rounded-lg shadow-sm"><div class="flex justify-between items-center"><h3 class="font-bold text-lg">👤 PAYER DETAILS</h3><button class="edit-btn" onclick="goToStep(3)">Edit</button></div><p class="mt-1">${escapeHtml(userDetails.name)}<br>${escapeHtml(userDetails.email)}<br>Member: ${userDetails.memberStatus === 'member' ? '✅ Yes' : '🤝 No, supporter'}</p></div>
                <div class="bg-red-50 p-4 border-2 border-red-200 rounded-lg"><div class="flex justify-between items-center"><span class="font-bold text-lg">💰 TOTAL AMOUNT TO PAY</span><span class="text-3xl font-bold text-red-600">£${total.toFixed(2)}</span></div></div>
            </div>`;
        }

        // ============================================
        // STEP NAVIGATION
        // ============================================
        function updateProgress() {
            for (let i = 1; i <= 5; i++) {
                let dot = document.getElementById(`step${i}Dot`);
                if (i < currentStep) { dot.classList.add('completed'); dot.classList.remove('active', 'bg-gray-300'); dot.textContent = '✓'; let line = document.getElementById(`line${i}`); if (line) line.classList.add('bg-green-500'); }
                else if (i === currentStep) { dot.classList.add('active'); dot.classList.remove('completed', 'bg-gray-300'); dot.textContent = i; }
                else { dot.classList.remove('active', 'completed'); dot.classList.add('bg-gray-300'); dot.textContent = i; }
            }
            let prev = document.getElementById('prevBtn'), next = document.getElementById('nextBtn');
            if (currentStep === 1) prev.classList.add('hidden'); else prev.classList.remove('hidden');
            if (currentStep === 5) next.classList.add('hidden'); else next.classList.remove('hidden');
        }

        function goToStep(step) {
            for (let i = 1; i <= 5; i++) document.getElementById(`step${i}`).classList.add('hidden');
            document.getElementById(`step${step}`).classList.remove('hidden');
            currentStep = step;
            if (step === 2 && selectedEvent) renderTickets();
            if (step === 4) displayReview();
            if (step === 5) { document.getElementById('paymentTotalAmount').innerText = '£' + calculateTotal().toFixed(2); initStripe(); }
            updateProgress();
        }

        function nextStep() {
            if (currentStep === 1 && !selectedEvent) { alert('Please select an event'); return; }
            if (currentStep === 2) { if ((tickets.adult + tickets.child + tickets.vip) === 0) { 
                // Check if volunteer is selected
                const volunteerCheckbox = document.getElementById('volunteerCheckbox');
                const hasVolunteer = volunteerCheckbox ? volunteerCheckbox.checked && volunteerAmount > 0 : false;
                if (!hasVolunteer) {
                    alert('Please select at least one ticket');
                    return;
                }
            } }
            if (currentStep === 3) {
                let name = document.getElementById('fullName').value.trim();
                let email = document.getElementById('email').value.trim();
                if (!name || !email) { alert('Please enter your name and email'); return; }
                userDetails.name = name; userDetails.email = email; userDetails.phone = document.getElementById('phone').value.trim();
                userDetails.memberStatus = document.querySelector('input[name="memberStatus"]:checked')?.value || 'non-member';
            }
            if (currentStep < 4) goToStep(currentStep + 1);
        }

        function prevStep() { if (currentStep > 1) goToStep(currentStep - 1); }

        // ============================================
        // STRIPE PAYMENT
        // ============================================
        async function initStripe() {
            if (!stripe) {
                try {
                    const res = await fetch('/api/stripe-config.php');
                    const config = await res.json();
                    stripe = Stripe(config.publishable_key);
                } catch(e) { 
                    console.error('Stripe init error:', e); 
                    return; 
                }
            }
            
            let total = calculateTotal();
            let amount = Math.round(total * 100);
            
            try {
                let res = await fetch('/api/create-payment-intent.php', {
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        amount: amount,
                        booking_reference: 'UMJ-' + Date.now(),
                        event_id: selectedEvent.id,
                        customer_name: userDetails.name,
                        email: userDetails.email,
                        phone: userDetails.phone || ''
                    })
                });
                let data = await res.json();
                
                if (data.success) {
                    currentClientSecret = data.clientSecret;
                    elements = stripe.elements({ clientSecret: data.clientSecret });
                    
                    cardElement = elements.create('card', { 
                        hidePostalCode: true
                    });
                    cardElement.mount('#card-element');
                    cardElement.on('change', e => { 
                        document.getElementById('card-errors').textContent = e.error ? e.error.message : ''; 
                    });
                    document.getElementById('stripe-pay-btn').classList.remove('hidden');
                }
            } catch(e) { 
                console.error('Payment intent error:', e); 
                alert('Payment setup failed. Please try again.'); 
            }
        }

        async function handleStripePayment() {
            if (!currentClientSecret) return;
            
            let btn = document.getElementById('stripe-pay-btn');
            btn.disabled = true; 
            btn.textContent = 'Processing...';
            
            try {
                const { error, paymentIntent } = await stripe.confirmCardPayment(currentClientSecret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: userDetails.name,
                            email: userDetails.email,
                            phone: userDetails.phone || ''
                        }
                    }
                });
                
                if (error) { 
                    alert('Payment failed: ' + error.message); 
                    btn.disabled = false; 
                    btn.textContent = '✅ Pay Now'; 
                } else if (paymentIntent.status === 'succeeded') { 
                    await submitBooking(paymentIntent.id); 
                }
            } catch(e) { 
                alert('Payment error: ' + e.message); 
                btn.disabled = false; 
                btn.textContent = '✅ Pay Now'; 
            }
        }

        // ============================================
        // SUBMIT BOOKING
        // ============================================
        async function submitBooking(paymentIntentId) {
            let total = calculateTotal();
            
            // Check if volunteer is selected
            const volunteerCheckbox = document.getElementById('volunteerCheckbox');
            const includeVolunteer = volunteerCheckbox ? volunteerCheckbox.checked : false;
            const finalVolunteerAmount = includeVolunteer ? volunteerAmount : 0;
            
            let payload = {
                eventId: selectedEvent.id, 
                fullName: userDetails.name, 
                email: userDetails.email, 
                phone: userDetails.phone || '',
                adults: tickets.adult, 
                children: tickets.child, 
                vip: tickets.vip, 
                volunteerAmount: finalVolunteerAmount,
                paymentMethod: 'stripe', 
                totalAmount: total, 
                memberStatus: userDetails.memberStatus || 'non-member',
                adult_names: adultAttendees, 
                child_names: childAttendees, 
                vip_names: vipAttendees,
                paymentIntentId: paymentIntentId
            };
            
            try {
                let res = await fetch('/api/booking/bookings-wizard.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                let result = await res.json();
                if (result.success) {
                    document.getElementById('successMessage').innerHTML = `<p><strong>Event:</strong> ${escapeHtml(selectedEvent.title)}</p><p><strong>Tickets:</strong> 👤 ${tickets.adult} 👶 ${tickets.child} ${tickets.vip > 0 ? '⭐ ' + tickets.vip : ''}</p>${includeVolunteer && finalVolunteerAmount > 0 ? `<p><strong>Volunteer Contribution:</strong> £${finalVolunteerAmount}</p>` : ''}<p><strong>Total Paid:</strong> £${total.toFixed(2)}</p>`;
                    document.getElementById('bookingRef').innerHTML = `<strong>Reference:</strong> ${result.bookingReference}`;
                    document.getElementById('successModal').classList.remove('hidden');
                } else { alert('Booking failed: ' + (result.error || 'Unknown error')); }
            } catch(e) { alert('Booking error: ' + e.message); }
        }

        // ============================================
        // INITIALIZATION
        // ============================================
        document.getElementById('nextBtn').onclick = nextStep;
        document.getElementById('prevBtn').onclick = prevStep;
        document.getElementById('proceedToPaymentBtn').onclick = () => goToStep(5);
        document.getElementById('backToReviewBtn').onclick = () => goToStep(4);
        document.getElementById('stripe-pay-btn').onclick = handleStripePayment;
        
        loadEvents();
        goToStep(1);
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Book Tickets | Umoja Wetu</title>
    <link rel="icon" type="image/png" href="/Logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .step { transition: all 0.3s ease; }
        .step-indicator {
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
        }
        .step-indicator.active { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .step-indicator.completed { background: linear-gradient(135deg, #27ae60, #219a52); }
        .event-card { transition: all 0.2s ease; cursor: pointer; }
        .event-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .event-card.selected { border-color: #e74c3c !important; background-color: #fff5f5 !important; }
        .ticket-card { transition: all 0.2s ease; cursor: pointer; }
        .ticket-card:hover { border-color: #e74c3c; background: #fef2f2; }
        .payment-option { transition: all 0.2s ease; cursor: pointer; }
        .payment-option.selected { border-color: #e74c3c; background: #fff5f5; }
        .StripeElement { padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: white; }
        .StripeElement--focus { border-color: #e74c3c; }
        .nav-link { transition: all 0.2s ease; }
        .nav-link:hover { transform: translateY(-2px); }
        .egg-logo { width: 120px; height: 160px; object-fit: contain; border-radius: 50% / 60% 60% 40% 40%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s ease; }
        .egg-logo:hover { transform: scale(1.05); }
        @media (max-width: 640px) { .egg-logo { width: 80px; height: 107px; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
        .edit-btn { background: #f3f4f6; border: 1px solid #e5e7eb; padding: 4px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s; }
        .edit-btn:hover { background: #e5e7eb; }
        .attendee-section { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
        .attendee-row { display: flex; align-items: center; gap: 8px; padding: 8px; background-color: #f9fafb; border-radius: 8px; margin-bottom: 8px; }
        .attendee-row:hover { background-color: #fef2f2; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// ERROR REPORTING (Remove in production)
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// LOAD PHPMailER
// ============================================
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================
// DATABASE CONNECTION
// ============================================
$db = new SQLite3('umoja-events-database.db');

// ============================================
// HELPER FUNCTIONS FOR LOCATION
// ============================================
function formatLocationAddress($location) {
    if (empty($location)) return 'To be announced';
    if (strpos($location, '||') !== false) {
        $parts = explode('||', $location);
        return htmlspecialchars(trim($parts[0]));
    }
    if (filter_var($location, FILTER_VALIDATE_URL)) {
        return 'Online Event';
    }
    return htmlspecialchars($location);
}

function formatLocationLink($location) {
    if (empty($location)) return '';
    if (strpos($location, '||') !== false) {
        $parts = explode('||', $location);
        $mapUrl = trim($parts[1]);
        if (filter_var($mapUrl, FILTER_VALIDATE_URL)) {
            return "<a href='" . htmlspecialchars($mapUrl) . "' style='color: #e74c3c; text-decoration: none;' target='_blank'>🗺️ View Map →</a>";
        }
    }
    if (filter_var($location, FILTER_VALIDATE_URL)) {
        return "<a href='" . htmlspecialchars($location) . "' style='color: #e74c3c; text-decoration: none;' target='_blank'>🗺️ View Map →</a>";
    }
    return '';
}

// ============================================
// GENERATE QR CODE (BASE64)
// ============================================
function generateQRCodeBase64($data) {
    $apis = [
        "https://quickchart.io/qr?text=" . urlencode($data) . "&size=200&margin=2&format=png",
        "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data),
        "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($data)
    ];
    
    foreach ($apis as $url) {
        $imageData = @file_get_contents($url);
        if ($imageData !== false && strlen($imageData) > 100) {
            return "data:image/png;base64," . base64_encode($imageData);
        }
    }
    return "";
}

// ============================================
// FUNCTION TO SAVE ATTENDEES (WITH FREE MEMBERS)
// ============================================
function saveAttendees($db, $bookingId, $eventId, $bookingData, $userDetails) {
    $pledgeAmount = $bookingData['pledgeAmount'] ?? 0;
    $freeMembersCount = $bookingData['freeMembers'] ?? 0;
    $freeMembersAdded = 0;
    
    // Save adults
    for ($i = 0; $i < $bookingData['adults']; $i++) {
        $isFreeMember = 0;
        if ($freeMembersAdded < $freeMembersCount) {
            $isFreeMember = 1;
            $freeMembersAdded++;
        }
        
        $stmt = $db->prepare("
            INSERT INTO Attendees (BookingId, EventId, Name, Email, phone, member_status, ticket_type, CheckedIn, CreatedAt, is_free_member)
            VALUES (:booking_id, :event_id, :name, :email, :phone, :member_status, 'adult', 0, datetime('now'), :is_free)
        ");
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $userDetails['name'], SQLITE3_TEXT);
        $stmt->bindValue(':email', $userDetails['email'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $userDetails['phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $userDetails['memberStatus'] ?? 'non-member', SQLITE3_TEXT);
        $stmt->bindValue(':is_free', $isFreeMember, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // Save children
    for ($i = 0; $i < $bookingData['children']; $i++) {
        $isFreeMember = 0;
        if ($freeMembersAdded < $freeMembersCount) {
            $isFreeMember = 1;
            $freeMembersAdded++;
        }
        
        $stmt = $db->prepare("
            INSERT INTO Attendees (BookingId, EventId, Name, Email, phone, member_status, ticket_type, CheckedIn, CreatedAt, is_free_member)
            VALUES (:booking_id, :event_id, :name, :email, :phone, :member_status, 'child', 0, datetime('now'), :is_free)
        ");
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $userDetails['name'], SQLITE3_TEXT);
        $stmt->bindValue(':email', $userDetails['email'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $userDetails['phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $userDetails['memberStatus'] ?? 'non-member', SQLITE3_TEXT);
        $stmt->bindValue(':is_free', $isFreeMember, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // Save VIPs
    for ($i = 0; $i < ($bookingData['vip'] ?? 0); $i++) {
        $isFreeMember = 0;
        if ($freeMembersAdded < $freeMembersCount) {
            $isFreeMember = 1;
            $freeMembersAdded++;
        }
        
        $stmt = $db->prepare("
            INSERT INTO Attendees (BookingId, EventId, Name, Email, phone, member_status, ticket_type, CheckedIn, CreatedAt, is_free_member)
            VALUES (:booking_id, :event_id, :name, :email, :phone, :member_status, 'vip', 0, datetime('now'), :is_free)
        ");
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $userDetails['name'], SQLITE3_TEXT);
        $stmt->bindValue(':email', $userDetails['email'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $userDetails['phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $userDetails['memberStatus'] ?? 'non-member', SQLITE3_TEXT);
        $stmt->bindValue(':is_free', $isFreeMember, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // Save volunteers
    for ($i = 0; $i < ($bookingData['volunteer'] ?? 0); $i++) {
        $isFreeMember = 0;
        if ($freeMembersAdded < $freeMembersCount) {
            $isFreeMember = 1;
            $freeMembersAdded++;
        }
        
        $stmt = $db->prepare("
            INSERT INTO Attendees (BookingId, EventId, Name, Email, phone, member_status, ticket_type, CheckedIn, CreatedAt, is_free_member)
            VALUES (:booking_id, :event_id, :name, :email, :phone, :member_status, 'volunteer', 0, datetime('now'), :is_free)
        ");
        $stmt->bindValue(':booking_id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':event_id', $eventId, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $userDetails['name'], SQLITE3_TEXT);
        $stmt->bindValue(':email', $userDetails['email'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $userDetails['phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':member_status', $userDetails['memberStatus'] ?? 'non-member', SQLITE3_TEXT);
        $stmt->bindValue(':is_free', $isFreeMember, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    return true;
}

// ============================================
// EMAIL FUNCTION WITH QR CODE ATTACHMENT - POSTMARK SMTP
// ============================================
function sendBookingConfirmationPHPMailer($to, $name, $bookingRef, $event, $bookingData, $volunteerAmount) {
    $subject = "Your Booking Confirmation - Umoja Wetu";
    
    // Calculate totals
    $adultTotal = $bookingData['adults'] * $event['PriceAdult'];
    $childTotal = $bookingData['children'] * $event['PriceChild'];
    $vipTotal = $bookingData['vip'] * ($event['PriceVIP'] ?? 0);
    $volunteerTickets = $bookingData['volunteerTickets'] ?? 0;
    $grandTotal = $adultTotal + $childTotal + $vipTotal + $volunteerAmount;
    
    // Determine if user gets a ticket
    $hasTicket = ($bookingData['adults'] > 0) || ($bookingData['children'] > 0) || ($bookingData['vip'] > 0) || ($volunteerTickets > 0);
    
    // Generate QR Code only if user gets a ticket
    $qrImageData = null;
    if ($hasTicket) {
        $qrImageData = generateQRCodeBase64($bookingRef . " - " . $event['Title']);
    }
    
    // Format location
    $locationAddress = formatLocationAddress($event['Location']);
    $locationLink = formatLocationLink($event['Location']);
    
    // Email content
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            
            <div style='background-color: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h2 style='margin: 0;'>" . ($hasTicket ? "Booking Confirmed! 🎉" : "Thank You for Your Support! 💝") . "</h2>
            </div>
            
            <div style='background-color: white; padding: 25px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                
                <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                " . ($hasTicket ? 
                    "<p>Your booking for <strong>" . htmlspecialchars($event['Title']) . "</strong> has been confirmed.</p>" :
                    "<p>Thank you for your generous contribution of <strong>£$volunteerAmount</strong> to <strong>" . htmlspecialchars($event['Title']) . "</strong>!</p>"
                ) . "
                
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                    <p style='margin: 0; font-size: 12px; color: #666;'>" . ($hasTicket ? "🎫 BOOKING REFERENCE" : "🙏 CONTRIBUTION REFERENCE") . "</p>
                    <p style='margin: 8px 0; font-size: 26px; font-family: monospace; letter-spacing: 2px; font-weight: bold; color: #e74c3c;'>$bookingRef</p>
                    " . ($hasTicket ? "<p style='margin: 10px 0 0 0; font-size: 12px; color: #666;'>📎 A QR code is attached to this email</p>" : "<p style='margin: 10px 0 0 0; font-size: 12px; color: #666;'>Your support makes our events possible!</p>") . "
                </div>
                
                <h3 style='color: #333; margin: 25px 0 10px 0; border-bottom: 2px solid #e74c3c; padding-bottom: 5px;'>📅 EVENT DETAILS</h3>
                <p style='margin: 0 0 8px 0; line-height: 1.6;'>
                    <strong>Event:</strong> " . htmlspecialchars($event['Title']) . "<br>
                    <strong>Date:</strong> " . date('F j, Y g:i A', strtotime($event['Date'])) . "
                </p>
                
                <div style='margin: 15px 0 0 0; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #e74c3c; border-radius: 4px;'>
                    <p style='margin: 0 0 5px 0;'><strong>📍 Location:</strong></p>
                    <p style='margin: 0 0 5px 0;'>$locationAddress</p>
                    <p style='margin: 5px 0 0 0;'>$locationLink</p>
                </div>
                
                <h3 style='color: #333; margin: 25px 0 10px 0; border-bottom: 2px solid #e74c3c; padding-bottom: 5px;'>🎟️ TICKET SUMMARY</h3>
                <p style='margin: 0 0 8px 0; line-height: 1.6;'>
                    " . ($bookingData['adults'] > 0 ? "👤 Adults: {$bookingData['adults']}<br>" : "") . "
                    " . ($bookingData['children'] > 0 ? "👶 Children: {$bookingData['children']}<br>" : "") . "
                    " . ($bookingData['vip'] > 0 ? "⭐ VIP: {$bookingData['vip']}<br>" : "") . "
                    " . ($volunteerAmount > 0 ? "🤝 Volunteer Contribution: £$volunteerAmount<br>" : "") . "
                    " . ($volunteerTickets > 0 ? "🎁 Free Tickets Earned: $volunteerTickets<br>" : "") . "
                    <strong style='color: #e74c3c;'>Total Paid: £$grandTotal</strong>
                </p>
                
                <h3 style='color: #333; margin: 25px 0 10px 0; border-bottom: 2px solid #e74c3c; padding-bottom: 5px;'>💳 PAYMENT METHOD</h3>
                <p style='margin: 0 0 8px 0;'>" . ucfirst(str_replace('_', ' ', $bookingData['paymentMethod'])) . "</p>
                
                " . (!$hasTicket && $volunteerAmount > 0 ? "
                <div style='margin-top: 30px; padding: 15px; background-color: #e8f4fd; border-radius: 8px; text-align: center;'>
                    <p style='margin: 0; font-size: 14px;'><strong>💝 Thank You for Your Support!</strong></p>
                    <p style='margin: 8px 0 0 0; font-size: 12px; color: #555;'>Your contribution of £$volunteerAmount helps us run this event. We greatly appreciate your support!</p>
                </div>
                " : "") . "
                
                <div style='margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; text-align: center;'>
                    <p style='margin: 0;'><strong>Thank you for supporting Umoja Wetu!</strong></p>
                </div>
            </div>
            
            <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px;'>
                <p style='margin: 0;'>Umoja Wetu Community | Building Together</p>
                <p style='margin: 5px 0 0 0;'>📍 Coventry, UK | 📧 info@umojawetu.com</p>
                <p style='margin: 5px 0 0 0;'>📅 " . date('F j, Y', strtotime($event['Date'])) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // ============================================
    // POSTMARK SMTP CONFIGURATION
    // ============================================
    $mail = new PHPMailer(true);
    
    try {
        // Postmark SMTP Server Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.postmarkapp.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '2fb5f0a4-6f9a-4d73-bed1-0909605ac675';  // Your Server API Token
        $mail->Password   = '2fb5f0a4-6f9a-4d73-bed1-0909605ac675';  // Same token
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        // Force PLAIN authentication
        $mail->AuthType = 'PLAIN';
        
        // Recipients
        $mail->setFrom('info@umojawetu.com', 'Umoja Wetu Events');
        $mail->addAddress($to, $name);
        $mail->addReplyTo('info@umojawetu.com', 'Umoja Wetu');
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        // Attach QR Code ONLY if user gets a ticket
        if ($hasTicket && $qrImageData && strpos($qrImageData, 'base64,') !== false) {
            $qrBase64 = str_replace('data:image/png;base64,', '', $qrImageData);
            $mail->addStringAttachment(base64_decode($qrBase64), 'qrcode.png', 'base64', 'image/png');
            error_log("QR Code attached for $bookingRef (has ticket)");
        } else {
            error_log("No QR Code for $bookingRef (no ticket earned)");
        }
        
        $mail->send();
        error_log("Postmark email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Postmark email failed to: $to - Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ============================================
// GET INPUT DATA
// ============================================
$input = json_decode(file_get_contents('php://input'), true);

// Debug log
error_log("=== New Booking Request ===");
error_log("Raw input: " . file_get_contents('php://input'));
error_log("Parsed input: " . print_r($input, true));

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data - no JSON received']);
    exit;
}

// Validate required fields
if (empty($input['eventId']) || empty($input['fullName']) || empty($input['email'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields: eventId, fullName, or email',
        'received' => array_keys($input)
    ]);
    exit;
}

// ============================================
// GET EVENT DETAILS
// ============================================
$stmt = $db->prepare("SELECT * FROM Events WHERE Id = :eventId");
$stmt->bindValue(':eventId', $input['eventId'], SQLITE3_INTEGER);
$result = $stmt->execute();

if (!$result) {
    echo json_encode(['error' => 'Database query failed', 'success' => false]);
    exit;
}

$event = $result->fetchArray(SQLITE3_ASSOC);

if (!$event) {
    echo json_encode(['error' => 'Event not found', 'success' => false]);
    exit;
}

// ============================================
// CALCULATE TOTALS
// ============================================
$hasFirstChildFree = isset($event['FirstChildFree']) && $event['FirstChildFree'] == 1;
$childrenCount = intval($input['children'] ?? 0);
$freeChildren = 0;
$payableChildren = $childrenCount;

if ($hasFirstChildFree && $childrenCount > 0) {
    $freeChildren = 1;
    $payableChildren = max(0, $childrenCount - 1);
}

$adultTotal = ($input['adults'] ?? 0) * $event['PriceAdult'];
$childTotal = $payableChildren * $event['PriceChild'];
$vipTotal = ($input['vip'] ?? 0) * ($event['PriceVIP'] ?? 0);
$volunteerTotal = floatval($input['volunteerTotal'] ?? 0);
$totalAmount = $adultTotal + $childTotal + $vipTotal + $volunteerTotal;

// Generate unique booking reference
$bookingRef = 'UMJ-' . strtoupper(substr(uniqid(), -8));

// ============================================
// INSERT BOOKING INTO DATABASE (UPDATED with PaymentIntentId)
// ============================================
$stmt = $db->prepare("
    INSERT INTO Bookings (
        BookingReference, EventId, FullName, Email, Phone,
        Adults, Children, FreeChildren, PayableChildren, VIP, Volunteer, 
        VolunteerAmount, VolunteerTotal, MemberStatus,
        PaymentMethod, PaymentStatus, Status, TotalAmount, PaymentIntentId,
        CreatedAt, UpdatedAt
    ) VALUES (
        :reference, :eventId, :fullName, :email, :phone,
        :adults, :children, :freeChildren, :payableChildren, :vip, :volunteer,
        :volunteerAmount, :volunteerTotal, :memberStatus,
        :paymentMethod, :paymentStatus, :status, :totalAmount, :paymentIntentId,
        :createdAt, :updatedAt
    )
");

$stmt->bindValue(':reference', $bookingRef, SQLITE3_TEXT);
$stmt->bindValue(':eventId', intval($input['eventId']), SQLITE3_INTEGER);
$stmt->bindValue(':fullName', trim($input['fullName']), SQLITE3_TEXT);
$stmt->bindValue(':email', strtolower(trim($input['email'])), SQLITE3_TEXT);
$stmt->bindValue(':phone', trim($input['phone'] ?? ''), SQLITE3_TEXT);
$stmt->bindValue(':adults', intval($input['adults'] ?? 0), SQLITE3_INTEGER);
$stmt->bindValue(':children', $childrenCount, SQLITE3_INTEGER);
$stmt->bindValue(':freeChildren', $freeChildren, SQLITE3_INTEGER);
$stmt->bindValue(':payableChildren', $payableChildren, SQLITE3_INTEGER);
$stmt->bindValue(':vip', intval($input['vip'] ?? 0), SQLITE3_INTEGER);
$stmt->bindValue(':volunteer', intval($input['volunteer'] ?? 0), SQLITE3_INTEGER);
$stmt->bindValue(':volunteerAmount', floatval($input['volunteerAmount'] ?? 0), SQLITE3_FLOAT);
$stmt->bindValue(':volunteerTotal', $volunteerTotal, SQLITE3_FLOAT);

$memberStatus = in_array($input['memberStatus'] ?? '', ['member', 'non-member']) 
    ? $input['memberStatus'] 
    : 'non-member';
$stmt->bindValue(':memberStatus', $memberStatus, SQLITE3_TEXT);

$stmt->bindValue(':paymentMethod', $input['paymentMethod'] ?? 'pay_on_arrival', SQLITE3_TEXT);
// If this is a Stripe payment with a PaymentIntentId, set status to 'paid' immediately
$paymentStatus = ($input['paymentMethod'] === 'stripe' && !empty($input['paymentIntentId'])) ? 'paid' : 'pending';
$stmt->bindValue(':paymentStatus', $paymentStatus, SQLITE3_TEXT);
$stmt->bindValue(':status', 'confirmed', SQLITE3_TEXT);
$stmt->bindValue(':totalAmount', $totalAmount, SQLITE3_FLOAT);
$stmt->bindValue(':paymentIntentId', $input['paymentIntentId'] ?? null, SQLITE3_TEXT);

$currentTime = date('Y-m-d H:i:s');
$stmt->bindValue(':createdAt', $currentTime, SQLITE3_TEXT);
$stmt->bindValue(':updatedAt', $currentTime, SQLITE3_TEXT);

// ============================================
// EXECUTE BOOKING & SAVE ATTENDEES & SEND EMAIL
// ============================================
if ($stmt->execute()) {
    // Get the new booking ID
    $bookingId = $db->lastInsertRowID();
    
    // Save attendees to Attendees table
    $userDetails = [
        'name' => $input['fullName'],
        'email' => $input['email'],
        'phone' => $input['phone'] ?? '',
        'memberStatus' => $memberStatus
    ];
    saveAttendees($db, $bookingId, $event['Id'], $input, $userDetails);
    
    // Send confirmation email via Postmark
    $volunteerAmount = floatval($input['volunteerAmount'] ?? 0);
    $emailSent = sendBookingConfirmationPHPMailer(
        $input['email'],
        $input['fullName'],
        $bookingRef,
        $event,
        $input,
        $volunteerAmount
    );
    
    echo json_encode([
        'success' => true,
        'bookingReference' => $bookingRef,
        'message' => 'Booking confirmed! Check your email for QR code.',
        'email_sent' => $emailSent,
        'payment_status' => $paymentStatus
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: Could not save booking'
    ]);
}
?>