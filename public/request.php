<?php
// Financial Request Form
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
    <title>Financial Request - <?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step { transition: all 0.3s ease; display: none; }
        .step.active { display: block; }
        .step-indicator {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin: 0 auto 8px;
        }
        .step-indicator.active { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
        .step-indicator.completed { background: #27ae60; color: white; }
        .step-indicator.pending { background: #d1d5db; color: #6b7280; }
        .request-card { cursor: pointer; transition: all 0.2s; }
        .request-card:hover { transform: translateY(-2px); }
        .request-card.selected { border-color: #e74c3c !important; background: #fef2f2 !important; }
        .env-badge {
            position: fixed; bottom: 10px; right: 10px;
            background: <?php echo $isLocal ? '#28a745' : '#dc3545'; ?>;
            color: white; padding: 5px 10px; border-radius: 5px;
            font-size: 11px; z-index: 999;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Progress Steps -->
            <div class="bg-gray-50 px-6 py-4 border-b">
                <div class="flex justify-between max-w-md mx-auto">
                    <div class="text-center flex-1"><div id="step1Dot" class="step-indicator pending">1</div><p class="text-xs">Type</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mt-5" id="line1"></div>
                    <div class="text-center flex-1"><div id="step2Dot" class="step-indicator pending">2</div><p class="text-xs">Details</p></div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mt-5" id="line2"></div>
                    <div class="text-center flex-1"><div id="step3Dot" class="step-indicator pending">3</div><p class="text-xs">Submit</p></div>
                </div>
            </div>

            <!-- Step 1: Request Type -->
            <div id="step1" class="step active p-8">
                <h2 class="text-2xl font-bold mb-4">Select Request Type</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="request-card border-2 rounded-xl p-4" data-type="refund">
                        <div class="text-3xl mb-2">💰</div>
                        <h3 class="font-bold">Refund Request</h3>
                        <p class="text-sm text-gray-500">Request a refund for a booking</p>
                    </div>
                    <div class="request-card border-2 rounded-xl p-4" data-type="payment">
                        <div class="text-3xl mb-2">💳</div>
                        <h3 class="font-bold">Payment Request</h3>
                        <p class="text-sm text-gray-500">Make a payment or donation</p>
                    </div>
                    <div class="request-card border-2 rounded-xl p-4" data-type="bereavement">
                        <div class="text-3xl mb-2">🤝</div>
                        <h3 class="font-bold">Bereavement Support</h3>
                        <p class="text-sm text-gray-500">Request assistance</p>
                    </div>
                    <div class="request-card border-2 rounded-xl p-4" data-type="special">
                        <div class="text-3xl mb-2">⭐</div>
                        <h3 class="font-bold">Special Request</h3>
                        <p class="text-sm text-gray-500">Other requests</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Your Details -->
            <div id="step2" class="step p-8 hidden">
                <h2 class="text-2xl font-bold mb-4">Your Details</h2>
                <div class="space-y-4">
                    <div><label class="block font-medium">Full Name *</label><input type="text" id="fullName" class="w-full border rounded-lg p-2"></div>
                    <div><label class="block font-medium">Email *</label><input type="email" id="email" class="w-full border rounded-lg p-2"></div>
                    <div><label class="block font-medium">Phone</label><input type="tel" id="phone" class="w-full border rounded-lg p-2"></div>
                    <div><label class="block font-medium">Membership ID</label><input type="text" id="membershipId" class="w-full border rounded-lg p-2"></div>
                </div>
            </div>

            <!-- Step 3: Request Details -->
            <div id="step3" class="step p-8 hidden">
                <h2 class="text-2xl font-bold mb-4">Request Details</h2>
                <div class="space-y-4">
                    <div><label class="block font-medium">Subject *</label><input type="text" id="subject" class="w-full border rounded-lg p-2"></div>
                    <div id="amountField" class="hidden"><label class="block font-medium">Amount (£)</label><input type="number" step="0.01" id="amount" class="w-full border rounded-lg p-2"></div>
                    <div><label class="block font-medium">Description *</label><textarea id="description" rows="4" class="w-full border rounded-lg p-2"></textarea></div>
                    <div class="flex items-center gap-2"><input type="checkbox" id="confirm"> <label>I confirm the information is accurate *</label></div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="px-8 py-6 bg-gray-50 border-t flex justify-between">
                <button id="prevBtn" class="px-6 py-2 bg-gray-300 rounded-lg hidden">← Back</button>
                <button id="nextBtn" class="px-6 py-2 bg-red-600 text-white rounded-lg ml-auto">Continue →</button>
                <button id="submitBtn" class="px-6 py-2 bg-green-600 text-white rounded-lg hidden">✓ Submit</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 text-center max-w-md">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-2xl font-bold text-green-600 mb-2">Request Submitted!</h3>
            <p id="requestRef" class="text-gray-600 mb-4"></p>
            <button onclick="location.reload()" class="bg-red-600 text-white px-6 py-2 rounded-lg">Submit Another</button>
        </div>
    </div>

    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?></div>

    <script>
        let currentStep = 1;
        let selectedType = null;
        const apiUrl = '<?php echo $apiBaseUrl; ?>/requests/submit-request.php';
        
        document.querySelectorAll('.request-card').forEach(card => {
            card.onclick = () => {
                document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected', 'border-red-500', 'bg-red-50'));
                card.classList.add('selected', 'border-red-500', 'bg-red-50');
                selectedType = card.dataset.type;
                document.getElementById('amountField').classList.toggle('hidden', selectedType !== 'payment' && selectedType !== 'refund');
            };
        });
        
        function updateProgress() {
            for (let i = 1; i <= 3; i++) {
                let dot = document.getElementById(`step${i}Dot`);
                if (i < currentStep) {
                    dot.classList.add('completed'); dot.classList.remove('active', 'pending');
                    dot.textContent = '✓';
                } else if (i === currentStep) {
                    dot.classList.add('active'); dot.classList.remove('completed', 'pending');
                    dot.textContent = i;
                } else {
                    dot.classList.add('pending'); dot.classList.remove('active', 'completed');
                    dot.textContent = i;
                }
            }
            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
            document.getElementById('nextBtn').classList.toggle('hidden', currentStep === 3);
            document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== 3);
        }
        
        function goToStep(step) {
            document.querySelectorAll('.step').forEach((s, i) => s.classList.toggle('active', i + 1 === step));
            currentStep = step;
            updateProgress();
        }
        
        document.getElementById('nextBtn').onclick = () => {
            if (currentStep === 1 && !selectedType) { alert('Select a request type'); return; }
            if (currentStep === 2) {
                let name = document.getElementById('fullName').value.trim();
                let email = document.getElementById('email').value.trim();
                if (!name || !email) { alert('Enter name and email'); return; }
            }
            goToStep(currentStep + 1);
        };
        
        document.getElementById('prevBtn').onclick = () => goToStep(currentStep - 1);
        
        document.getElementById('submitBtn').onclick = async () => {
            if (!document.getElementById('confirm').checked) { alert('Confirm accuracy'); return; }
            let subject = document.getElementById('subject').value.trim();
            let description = document.getElementById('description').value.trim();
            if (!subject || !description) { alert('Enter subject and description'); return; }
            
            let formData = new FormData();
            formData.append('requestType', selectedType);
            formData.append('fullName', document.getElementById('fullName').value.trim());
            formData.append('email', document.getElementById('email').value.trim());
            formData.append('phone', document.getElementById('phone').value.trim());
            formData.append('membershipId', document.getElementById('membershipId').value.trim());
            formData.append('subject', subject);
            formData.append('description', description);
            formData.append('amount', document.getElementById('amount').value || 0);
            
            let btn = document.getElementById('submitBtn');
            btn.disabled = true; btn.textContent = 'Submitting...';
            
            try {
                let res = await fetch(apiUrl, { method: 'POST', body: formData });
                let data = await res.json();
                if (data.success) {
                    document.getElementById('requestRef').innerHTML = `Reference: ${data.requestId}`;
                    document.getElementById('successModal').classList.add('flex');
                } else alert('Error: ' + (data.error || 'Submission failed'));
            } catch(e) { alert('Network error'); }
            finally { btn.disabled = false; btn.textContent = '✓ Submit'; }
        };
        
        goToStep(1);
    </script>
</body>
</html>