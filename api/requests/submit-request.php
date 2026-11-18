<?php
// Submit Request API
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();

$isLocal = Environment::isLocal();

// Get form data
$postData = [
    'requestType' => $_POST['requestType'] ?? '',
    'requesterType' => $_POST['requesterType'] ?? '',
    'fullName' => $_POST['fullName'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'subject' => $_POST['subject'] ?? '',
    'description' => $_POST['description'] ?? ''
];

// Validate required fields
$required = ['requestType', 'fullName', 'email', 'subject', 'description'];
$errors = validateRequired($postData, $required);

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Validate and sanitize inputs
$requestType = sanitizeInput($postData['requestType']);
$requesterType = sanitizeInput($_POST['requesterType'] ?? 'individual');
$fullName = sanitizeInput($postData['fullName']);
$email = sanitizeInput($postData['email'], 'email');
$phone = sanitizeInput($_POST['phone'] ?? '');
$membershipId = sanitizeInput($_POST['membershipId'] ?? '');
$subject = sanitizeInput($postData['subject']);
$description = sanitizeInput($postData['description']);
$bookingRef = sanitizeInput($_POST['bookingRef'] ?? '');
$amount = sanitizeInput($_POST['amount'] ?? 0, 'float');
$requestFor = sanitizeInput($_POST['requestFor'] ?? 'myself');
$priority = sanitizeInput($_POST['priority'] ?? 'normal');

// Validate email format
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit();
}

// Generate request ID
$requestId = 'REQ-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');

// Prepare data
$requestData = json_encode([
    'subject' => $subject,
    'description' => $description,
    'bookingRef' => $bookingRef,
    'amount' => $amount,
    'requestFor' => $requestFor
]);

$db = getDbConnection();

// Create table if needed
$db->exec("
    CREATE TABLE IF NOT EXISTS Requests (
        Id INTEGER PRIMARY KEY AUTOINCREMENT,
        RequestReference TEXT UNIQUE,
        RequestType TEXT,
        RequesterType TEXT,
        Status TEXT DEFAULT 'pending',
        Priority TEXT DEFAULT 'normal',
        FullName TEXT,
        Email TEXT,
        Phone TEXT,
        MembershipId TEXT,
        RequestData TEXT,
        AmountRequested REAL,
        SubmittedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        ResolvedAt DATETIME,
        IsRead INTEGER DEFAULT 0
    )
");

// Insert
$stmt = $db->prepare("
    INSERT INTO Requests (RequestReference, RequestType, RequesterType, FullName, Email, Phone, MembershipId, RequestData, AmountRequested, Priority)
    VALUES (:ref, :type, :requester, :name, :email, :phone, :member, :data, :amount, :priority)
");

$stmt->bindValue(':ref', $requestId, SQLITE3_TEXT);
$stmt->bindValue(':type', $requestType, SQLITE3_TEXT);
$stmt->bindValue(':requester', $requesterType, SQLITE3_TEXT);
$stmt->bindValue(':name', $fullName, SQLITE3_TEXT);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
$stmt->bindValue(':member', $membershipId, SQLITE3_TEXT);
$stmt->bindValue(':data', $requestData, SQLITE3_TEXT);
$stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
$stmt->bindValue(':priority', $priority, SQLITE3_TEXT);
$stmt->execute();

$db->close();

// Log for local dev
if ($isLocal) {
    $logDir = __DIR__ . '/../../storage/logs';
    if (!file_exists($logDir)) mkdir($logDir, 0777, true);
    file_put_contents($logDir . '/requests.log', date('Y-m-d H:i:s') . " | $requestId | $fullName | $email | $requestType\n", FILE_APPEND);
}

echo json_encode(['success' => true, 'requestId' => $requestId]);
?>