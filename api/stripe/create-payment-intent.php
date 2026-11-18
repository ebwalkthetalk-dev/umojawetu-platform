<?php
// Create Payment Intent API
// Location: /api/stripe/create-payment-intent.php

// Load configuration (this loads your .env file)
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get Stripe secret key from .env (NOT hardcoded)
$secret_key = getenv('STRIPE_SECRET_KEY');

if (empty($secret_key)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Stripe secret key not configured in .env file'
    ]);
    exit();
}

// Get amount from request
$input = json_decode(file_get_contents('php://input'), true);

// Validate and sanitize amount
$errors = validateRequired($input, ['amount']);
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

$amount = sanitizeInput($input['amount'], 'int');

if (!$amount || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount (must be > 0)', 'success' => false]);
    exit();
}

// For local testing without Stripe PHP library
$isLocal = Environment::isLocal();

if ($isLocal) {
    // LOCAL TESTING - Mock response (no actual Stripe call)
    echo json_encode([
        'success' => true,
        'clientSecret' => 'mock_secret_' . uniqid(),
        'amount' => $amount,
        'test_mode' => true,
        'message' => 'Local test mode - no actual charge'
    ], JSON_PRETTY_PRINT);
} else {
    // PRODUCTION - You'll need Stripe PHP library
    // The secret key is already loaded from .env above
    echo json_encode([
        'success' => false,
        'error' => 'Stripe integration needs to be completed on live server',
        'message' => 'Your secret key is loaded from .env, but Stripe PHP library is not installed'
    ], JSON_PRETTY_PRINT);
}
?>