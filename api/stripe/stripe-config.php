<?php
// Stripe Configuration API
// Location: /api/stripe/stripe-config.php
// Returns the PUBLIC publishable key only. NEVER the secret key.
// Reads from .env only â€” no hardcoded fallbacks.

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$publishableKey = getenv('STRIPE_PUBLISHABLE_KEY');

if (empty($publishableKey)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Payment configuration unavailable',
        'detail' => 'STRIPE_PUBLISHABLE_KEY is not set in .env'
    ], JSON_PRETTY_PRINT);
    exit;
}

if (strpos($publishableKey, 'pk_') !== 0) {
    error_log('SECURITY: STRIPE_PUBLISHABLE_KEY does not start with pk_');
    http_response_code(500);
    echo json_encode([
        'error' => 'Payment configuration invalid'
    ], JSON_PRETTY_PRINT);
    exit;
}

$isLocal = Environment::isLocal();
$testMode = (strpos($publishableKey, 'pk_test_') === 0);

echo json_encode([
    'publishable_key' => $publishableKey,
    'test_mode' => $testMode,
    'environment' => $isLocal ? 'local' : 'production'
], JSON_PRETTY_PRINT);
