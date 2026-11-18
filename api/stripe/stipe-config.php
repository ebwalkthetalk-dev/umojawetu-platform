<?php
// LEGACY PATH â€” file renamed to stripe-config.php (typo fixed).
// Returns 410 Gone so any old caller fails loudly and is updated.
// Delete this file in Phase 3.

require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'error' => 'This endpoint has moved',
    'reason' => 'Filename typo fixed',
    'new_location' => '/api/stripe/stripe-config.php'
], JSON_PRETTY_PRINT);
