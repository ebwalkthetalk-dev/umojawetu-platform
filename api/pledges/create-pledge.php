<?php
// Create Pledge API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$isLocal = Environment::isLocal();
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['pledger_name', 'email', 'amount_pledged'];
$errors = validateRequired($input, $required);

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Validate and sanitize inputs
$pledgerName = sanitizeInput($input['pledger_name']);
$email = sanitizeInput($input['email'], 'email');
$phone = sanitizeInput($input['phone'] ?? '');
$amountPledged = sanitizeInput($input['amount_pledged'], 'float');

// Validate email format
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit();
}

// Validate amount
if (!$amountPledged || $amountPledged <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    exit();
}

$db = getDbConnection();

// Insert pledge
$stmt = $db->prepare("
    INSERT INTO pledges (
        pledge_reference, pledger_name, email, phone, is_member, membership_id,
        amount_pledged, payment_method, notes, created_at
    ) VALUES (
        :ref, :name, :email, :phone, :member, :member_id,
        :amount, :method, :notes, datetime('now')
    )
");

$stmt->bindValue(':ref', $pledgeReference, SQLITE3_TEXT);
$stmt->bindValue(':name', $pledgerName, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
$stmt->bindValue(':member', $isMember, SQLITE3_INTEGER);
$stmt->bindValue(':member_id', $membershipId, SQLITE3_TEXT);
$stmt->bindValue(':amount', $amountPledged, SQLITE3_FLOAT);
$stmt->bindValue(':method', $paymentMethod, SQLITE3_TEXT);
$stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
$stmt->execute();

$pledgeId = $db->lastInsertRowID();

// If payment method is stripe, return client secret
$clientSecret = null;
if ($paymentMethod === 'stripe' && $amountPledged > 0) {
    // Stripe integration will be added in Phase 3
    // For now, just return success
}

$db->close();

// Log for local development
if ($isLocal) {
    $logDir = __DIR__ . '/../../storage/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/pledges.log';
    $logEntry = date('Y-m-d H:i:s') . " | $pledgeReference | $pledgerName | £$amountPledged\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

echo json_encode([
    'success' => true,
    'pledge_reference' => $pledgeReference,
    'pledge_id' => $pledgeId,
    'message' => 'Pledge created successfully'
], JSON_PRETTY_PRINT);
?>