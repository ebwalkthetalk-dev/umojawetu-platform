<?php
// Resend Pledge Email API - Individual & Bulk
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
setCorsHeaders();
requireAdminLogin();

$db = getDbConnection();
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$pledgeIds = $input['pledge_ids'] ?? ($_GET['pledge_ids'] ?? []);
$action = $input['action'] ?? 'resend';
$previewOnly = $action === 'preview';

if (empty($pledgeIds)) {
    echo json_encode(['success' => false, 'error' => 'No pledge IDs provided']);
    exit;
}

// Ensure array
if (!is_array($pledgeIds)) {
    $pledgeIds = [$pledgeIds];
}

$results = [];
$emailCount = 0;

foreach ($pledgeIds as $pledgeId) {
    $pledgeId = intval($pledgeId);
    
    // Get pledge details
    $stmt = $db->prepare("SELECT Id, pledge_reference, pledger_name, email, amount_pledged, payment_status, created_at FROM pledges WHERE Id = :id");
    $stmt->bindValue(':id', $pledgeId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $pledge = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$pledge) {
        $results[] = [
            'id' => $pledgeId,
            'success' => false,
            'error' => 'Pledge not found'
        ];
        continue;
    }
    
    // Build email body
    $statusDisplay = match($pledge['payment_status']) {
        'paid' => '✅ Paid',
        'pending' => '⏳ Pending',
        default => '📋 ' . ucfirst($pledge['payment_status'])
    };
    
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
            .header { background: #d12828; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: white; padding: 20px; }
            .pledge-details { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .detail-row { display: flex; justify-content: space-between; margin: 8px 0; }
            .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
            .button { background: #d12828; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🤝 Pledge Confirmation</h1>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($pledge['pledger_name']) . "</strong>,</p>
                
                <p>This is a reminder of your pledge commitment to Umoja Wetu. Thank you for your support!</p>
                
                <div class='pledge-details'>
                    <div class='detail-row'>
                        <span><strong>Pledge Reference:</strong></span>
                        <span>" . htmlspecialchars($pledge['pledge_reference']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Amount Pledged:</strong></span>
                        <span>£" . number_format($pledge['amount_pledged'], 2) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Status:</strong></span>
                        <span>" . $statusDisplay . "</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Date:</strong></span>
                        <span>" . substr($pledge['created_at'], 0, 10) . "</span>
                    </div>
                </div>
                
                <p>If you have any questions about your pledge or would like to update your payment information, please reply to this email or contact our office.</p>
                
                <a href='#' class='button'>💳 Manage Pledge</a>
                
                <p style='margin-top: 30px; color: #666; font-size: 12px;'>
                    Thank you for supporting Umoja Wetu!<br>
                    <em>This is an automated message, please do not reply directly.</em>
                </p>
            </div>
            <div class='footer'>
                <p>&copy; 2026 Umoja Wetu. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    if ($previewOnly) {
        $results[] = [
            'id' => $pledgeId,
            'success' => true,
            'type' => 'preview',
            'email' => $pledge['email'],
            'subject' => 'Pledge Confirmation - Umoja Wetu',
            'body' => $emailBody
        ];
    } else {
        // Send email
        $emailSubject = 'Pledge Confirmation - Umoja Wetu';
        $emailSuccess = sendEmail($pledge['email'], $emailSubject, $emailBody);
        
        $results[] = [
            'id' => $pledgeId,
            'success' => $emailSuccess,
            'email' => $pledge['email'],
            'pledger' => $pledge['pledger_name'],
            'message' => $emailSuccess ? 'Email sent successfully' : 'Email sent (logged)'
        ];
        
        if ($emailSuccess) {
            $emailCount++;
        }
    }
}

echo json_encode([
    'success' => true,
    'count' => $emailCount,
    'total' => count($pledgeIds),
    'results' => $results
]);

$db->close();
?>
