<?php
// Request Status Tracking
require_once __DIR__ . '/../config/config.php';

$isLocal = Environment::isLocal();
$apiBaseUrl = $isLocal ? '/umojawetu-platform/api' : 'https://event.umojawetu.com/api';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Status - <?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .env-badge {
            position: fixed; bottom: 10px; right: 10px;
            background: <?php echo $isLocal ? '#28a745' : '#dc3545'; ?>;
            color: white; padding: 5px 10px; border-radius: 5px;
            font-size: 11px; z-index: 999;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-center mb-4">🔍 Track Your Request</h1>
            <p class="text-gray-600 text-center mb-8">Enter your Request ID</p>
            
            <input type="text" id="requestId" placeholder="e.g., REQ-XXXXXX-20250409" class="w-full p-3 border rounded-lg mb-4">
            <button onclick="checkStatus()" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700">Check Status</button>
            
            <div id="result" class="mt-6 hidden"></div>
        </div>
    </div>
    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?></div>

    <script>
        const apiUrl = '<?php echo $apiBaseUrl; ?>/requests/get-status.php';
        
        async function checkStatus() {
            let ref = document.getElementById('requestId').value.trim();
            if (!ref) { alert('Enter Request ID'); return; }
            
            let resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="text-center py-4">Checking...</div>';
            resultDiv.classList.remove('hidden');
            
            try {
                let res = await fetch(apiUrl + '?ref=' + encodeURIComponent(ref));
                let data = await res.json();
                if (data.success && data.request) {
                    let statusClass = { pending: 'bg-yellow-100 text-yellow-800', approved: 'bg-green-100 text-green-800', rejected: 'bg-red-100 text-red-800' }[data.request.Status] || 'bg-gray-100';
                    resultDiv.innerHTML = `<div class="border rounded-lg p-4 mt-4">
                        <h3 class="font-bold">${data.request.RequestReference}</h3>
                        <div class="flex justify-between mt-2"><span>Status:</span><span class="${statusClass} px-2 py-1 rounded">${data.request.Status}</span></div>
                        <div class="flex justify-between mt-2"><span>Type:</span><span>${data.request.RequestType}</span></div>
                        <div class="flex justify-between mt-2"><span>Submitted:</span><span>${new Date(data.request.SubmittedAt).toLocaleDateString()}</span></div>
                    </div>`;
                } else resultDiv.innerHTML = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mt-4">Request not found</div>';
            } catch(e) { resultDiv.innerHTML = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mt-4">Error checking status</div>'; }
        }
    </script>
</body>
</html>