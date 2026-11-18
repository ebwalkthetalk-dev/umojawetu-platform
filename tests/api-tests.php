<?php
/**
 * API Endpoint Test Suite
 * Tests API endpoints for proper validation, CORS, and error handling
 * 
 * Location: /tests/api-tests.php
 * Usage: Visit http://localhost/umojawetu-platform/tests/api-tests.php or run via curl
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$testMode = $_GET['test'] ?? 'all';
$results = [];

/**
 * Helper to make internal API calls
 */
function testApiCall($endpoint, $method = 'POST', $data = []) {
    global $testMode;
    
    $url = 'http://localhost/umojawetu-platform' . $endpoint;
    
    // Use curl to test the endpoint
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Origin: http://localhost'
    ]);
    
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'endpoint' => $endpoint,
        'method' => $method,
        'httpCode' => $httpCode,
        'response' => json_decode($response, true),
        'error' => $error
    ];
}

// ============================================
// TEST 1: Pledge Creation API - Missing Fields
// ============================================

if ($testMode === 'all' || $testMode === 'pledge') {
    $test = testApiCall('/api/pledges/create-pledge.php', 'POST', [
        'email' => 'test@example.com'
        // Missing: pledger_name, amount_pledged
    ]);
    
    $results['pledge_missing_fields'] = [
        'name' => 'Pledge API - Missing Required Fields',
        'expected' => 400,
        'actual' => $test['httpCode'],
        'passed' => $test['httpCode'] === 400,
        'details' => $test['response']
    ];
    
    // Test with valid data (local testing)
    $test2 = testApiCall('/api/pledges/create-pledge.php', 'POST', [
        'pledger_name' => 'Test User',
        'email' => 'test@example.com',
        'amount_pledged' => 100
    ]);
    
    $results['pledge_valid'] = [
        'name' => 'Pledge API - Valid Request',
        'expected' => 200,
        'actual' => $test2['httpCode'],
        'passed' => in_array($test2['httpCode'], [200, 201]),
        'details' => $test2['response']
    ];
}

// ============================================
// TEST 2: Pledge API - Invalid Email
// ============================================

if ($testMode === 'all' || $testMode === 'pledge') {
    $test = testApiCall('/api/pledges/create-pledge.php', 'POST', [
        'pledger_name' => 'Test User',
        'email' => 'not-an-email',
        'amount_pledged' => 100
    ]);
    
    $results['pledge_invalid_email'] = [
        'name' => 'Pledge API - Invalid Email',
        'expected' => 400,
        'actual' => $test['httpCode'],
        'passed' => $test['httpCode'] === 400,
        'details' => $test['response']
    ];
}

// ============================================
// TEST 3: Payment Intent API - Invalid Amount
// ============================================

if ($testMode === 'all' || $testMode === 'payment') {
    $test = testApiCall('/api/stripe/create-payment-intent.php', 'POST', [
        'amount' => -100  // Negative amount
    ]);
    
    $results['payment_invalid_amount'] = [
        'name' => 'Payment API - Invalid Amount',
        'expected' => 400,
        'actual' => $test['httpCode'],
        'passed' => $test['httpCode'] === 400,
        'details' => $test['response']
    ];
}

// ============================================
// TEST 4: Payment Intent API - Missing Amount
// ============================================

if ($testMode === 'all' || $testMode === 'payment') {
    $test = testApiCall('/api/stripe/create-payment-intent.php', 'POST', [
        // Missing amount
    ]);
    
    $results['payment_missing_amount'] = [
        'name' => 'Payment API - Missing Amount',
        'expected' => 400,
        'actual' => $test['httpCode'],
        'passed' => $test['httpCode'] === 400,
        'details' => $test['response']
    ];
}

// ============================================
// TEST 5: Request Submission API - Missing Fields
// ============================================

if ($testMode === 'all' || $testMode === 'request') {
    $test = testApiCall('/api/requests/submit-request.php', 'POST', [
        'fullName' => 'Test User'
        // Missing: email, subject, description
    ]);
    
    $results['request_missing_fields'] = [
        'name' => 'Request API - Missing Required Fields',
        'expected' => 400,
        'actual' => $test['httpCode'],
        'passed' => $test['httpCode'] === 400,
        'details' => $test['response']
    ];
}

// ============================================
// TEST 6: Request API - Invalid Email
// ============================================

if ($testMode === 'all' || $testMode === 'request') {
    $test = testApiCall('/api/requests/submit-request.php', 'POST', [
        'requestType' => 'general',
        'fullName' => 'Test User',
        'email' => 'invalid-email',
        'subject' => 'Test Subject',
        'description' => 'Test Description'
    ]);
    
    $results['request_invalid_email'] = [
        'name' => 'Request API - Invalid Email',
        'expected' => 400,
        'actual' => $test['httpCode'],
        'passed' => $test['httpCode'] === 400,
        'details' => $test['response']
    ];
}

// ============================================
// TEST 7: CORS Headers Check
// ============================================

if ($testMode === 'all' || $testMode === 'cors') {
    $ch = curl_init('http://localhost/umojawetu-platform/api/pledges/get-pledges-public.php');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Origin: http://localhost'
    ]);
    
    curl_exec($ch);
    $corsHeader = curl_getinfo($ch, CURLINFO_HTTP_HEADER);
    curl_close($ch);
    
    $results['cors_header'] = [
        'name' => 'CORS Headers Present',
        'expected' => true,
        'actual' => strpos($corsHeader, 'Access-Control-Allow-Origin') !== false,
        'passed' => strpos($corsHeader, 'Access-Control-Allow-Origin') !== false,
        'details' => ['cors_header_found' => strpos($corsHeader, 'Access-Control-Allow-Origin') !== false]
    ];
}

// ============================================
// RENDER RESULTS
// ============================================

$passCount = 0;
$totalCount = count($results);

foreach ($results as &$result) {
    if ($result['passed']) {
        $passCount++;
    }
}

$output = [
    'summary' => [
        'test_mode' => $testMode,
        'total_tests' => $totalCount,
        'passed' => $passCount,
        'failed' => $totalCount - $passCount,
        'pass_rate' => $totalCount > 0 ? round(($passCount / $totalCount) * 100, 2) : 0
    ],
    'results' => $results,
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => Environment::isLocal() ? 'LOCAL' : 'PRODUCTION'
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
