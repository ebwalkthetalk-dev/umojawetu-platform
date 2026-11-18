<?php
// Complete Email Templates Management System
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$db = getDbConnection();
$message = '';
$error = '';

// Email templates configuration
$templates = [
    'booking_confirmation' => [
        'name' => 'Booking Confirmation',
        'description' => 'Sent when a customer books tickets',
        'icon' => '🎫',
        'variables' => ['NAME', 'EMAIL', 'EVENT_NAME', 'BOOKING_REF', 'DATE', 'TICKET_COUNT', 'TOTAL_AMOUNT']
    ],
    'payment_receipt' => [
        'name' => 'Payment Receipt',
        'description' => 'Sent after successful payment',
        'icon' => '💳',
        'variables' => ['NAME', 'EMAIL', 'AMOUNT', 'PAYMENT_DATE', 'TRANSACTION_ID', 'EVENT_NAME', 'INVOICE_NUMBER']
    ],
    'event_reminder' => [
        'name' => 'Event Reminder',
        'description' => 'Sent 24 hours before the event',
        'icon' => '⏰',
        'variables' => ['NAME', 'EMAIL', 'EVENT_NAME', 'EVENT_DATE', 'EVENT_TIME', 'LOCATION', 'QR_CODE_URL']
    ],
    'request_status_update' => [
        'name' => 'Request Status Update',
        'description' => 'Sent when a financial request status changes',
        'icon' => '💰',
        'variables' => ['NAME', 'EMAIL', 'REQUEST_TITLE', 'STATUS', 'AMOUNT', 'MESSAGE', 'ADMIN_NOTES']
    ],
    'newsletter_broadcast' => [
        'name' => 'Newsletter Broadcast',
        'description' => 'General announcements and updates',
        'icon' => '📰',
        'variables' => ['RECIPIENT_NAME', 'SUBJECT', 'MAIN_MESSAGE', 'CTA_TEXT', 'CTA_URL', 'UNSUBSCRIBE_URL']
    ]
];

// Handle template save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $templateId = $_POST['template_id'] ?? '';
    
    if (in_array($templateId, array_keys($templates))) {
        if ($action === 'save_template') {
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? '');
            $footer = trim($_POST['footer'] ?? '');
            
            if (empty($subject) || empty($body)) {
                $error = "Subject and body are required";
            } else {
                // Save/update template (using JSON file storage for now)
                $templateFile = __DIR__ . '/../config/email-templates.json';
                $allTemplates = [];
                
                if (file_exists($templateFile)) {
                    $allTemplates = json_decode(file_get_contents($templateFile), true) ?? [];
                }
                
                $allTemplates[$templateId] = [
                    'subject' => $subject,
                    'body' => $body,
                    'footer' => $footer,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $_SESSION['admin_username'] ?? 'Unknown'
                ];
                
                if (file_put_contents($templateFile, json_encode($allTemplates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $message = "✅ Template saved successfully";
                } else {
                    $error = "Failed to save template";
                }
            }
        } elseif ($action === 'send_test') {
            $testEmail = trim($_POST['test_email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? '');
            
            if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $error = "Valid test email required";
            } else {
                // Send test email (simulated for now)
                $headers = "MIME-Version: 1.0" . "\r\n" . "Content-type: text/html; charset=UTF-8" . "\r\n";
                $headers .= "From: " . ($admin_email ?? "admin@umojawetu.com") . "\r\n";
                
                $testBody = "<html><body style='font-family: Arial, sans-serif;'>";
                $testBody .= "<div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px;'>";
                $testBody .= $body;
                $testBody .= "<hr style='margin: 20px 0;'>";
                $testBody .= "<p style='color: #999; font-size: 12px;'>This is a test email. Variables are shown as placeholders.</p>";
                $testBody .= "</div></body></html>";
                
                if (mail($testEmail, "TEST: " . $subject, $testBody, $headers)) {
                    $message = "✅ Test email sent to " . htmlspecialchars($testEmail);
                } else {
                    $error = "Failed to send test email";
                }
            }
        }
    }
}

// Load template content
function getTemplateContent($templateId) {
    $templateFile = __DIR__ . '/../config/email-templates.json';
    if (file_exists($templateFile)) {
        $allTemplates = json_decode(file_get_contents($templateFile), true) ?? [];
        return $allTemplates[$templateId] ?? null;
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tab-button { transition: all 0.2s ease; }
        .tab-button.active { background-color: #dc2626; color: white; }
        .template-preview { background: #f9fafb; border-left: 4px solid #dc2626; }
        .variable-tag { display: inline-block; background: #fef3c7; color: #92400e; padding: 2px 8px; margin: 2px; border-radius: 4px; font-size: 12px; font-family: monospace; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400">Umojawetu Platform</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard-unified.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📊 Dashboard</a>
                <a href="event-builder.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">✨ Event Builder</a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">📅 Events</a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👥 Attendees</a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💰 Requests</a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">🖼️ Media</a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">💳 Payments</a>
                <a href="email-templates-new.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">📧 Email Templates</a>
                <a href="admin-users.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">👑 Admin Users</a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 mt-20 text-red-400">🚪 Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold">📧 Email Templates</h1>
                <p class="text-gray-600">Manage and customize email templates for your platform</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4 flex items-center gap-2">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4 flex items-center gap-2">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Template Tabs -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Tab Navigation -->
                <div class="flex flex-wrap border-b">
                    <?php foreach ($templates as $id => $template): ?>
                    <button class="tab-button px-6 py-3 font-medium hover:bg-red-50 transition" data-template="<?php echo $id; ?>" onclick="switchTemplate('<?php echo $id; ?>')">
                        <?php echo $template['icon']; ?> <?php echo $template['name']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Tab Contents -->
                <?php foreach ($templates as $id => $template): 
                    $content = getTemplateContent($id);
                    $isActive = isset($_POST['template_id']) ? $_POST['template_id'] === $id : false;
                    if (!$isActive && empty($_POST['template_id'])) {
                        $isActive = $id === 'booking_confirmation';
                    }
                ?>
                <div id="tab-<?php echo $id; ?>" class="template-tab <?php echo $isActive ? '' : 'hidden'; ?> p-6">
                    <form method="POST" class="grid grid-cols-3 gap-6">
                        <input type="hidden" name="action" value="save_template">
                        <input type="hidden" name="template_id" value="<?php echo $id; ?>">
                        
                        <!-- Editor -->
                        <div class="col-span-2">
                            <h3 class="text-xl font-bold mb-2"><?php echo $template['name']; ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?php echo $template['description']; ?></p>

                            <!-- Subject -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">📌 Subject Line</label>
                                <input type="text" name="subject" value="<?php echo htmlspecialchars($content['subject'] ?? 'Default Subject'); ?>" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Email subject...">
                            </div>

                            <!-- Body -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">📝 Email Body (HTML)</label>
                                <textarea name="body" rows="15" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono text-sm" placeholder="Email body in HTML..."><?php echo htmlspecialchars($content['body'] ?? ''); ?></textarea>
                            </div>

                            <!-- Footer -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">🔗 Footer (optional)</label>
                                <textarea name="footer" rows="3" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono text-sm" placeholder="Footer content..."><?php echo htmlspecialchars($content['footer'] ?? ''); ?></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 transition">💾 Save Template</button>
                                <button type="button" onclick="sendTest('<?php echo $id; ?>')" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">📧 Send Test Email</button>
                            </div>
                        </div>

                        <!-- Variables & Preview -->
                        <div>
                            <!-- Available Variables -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="font-bold text-sm mb-3">📋 Available Variables</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($template['variables'] as $var): ?>
                                    <span class="variable-tag cursor-pointer" onclick="insertVariable('<?php echo $var; ?>')" title="Click to insert">{{<?php echo $var; ?>}}</span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Template Info -->
                            <div class="bg-blue-50 rounded-lg p-4">
                                <h4 class="font-bold text-sm mb-2">ℹ️ Template Info</h4>
                                <div class="text-xs space-y-1 text-gray-700">
                                    <p><strong>Type:</strong> <?php echo $template['name']; ?></p>
                                    <p><strong>Status:</strong> <?php echo $content ? '✅ Configured' : '⚠️ Default'; ?></p>
                                    <?php if ($content): ?>
                                    <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($content['updated_at'] ?? 'Unknown'); ?></p>
                                    <p><strong>By:</strong> <?php echo htmlspecialchars($content['updated_by'] ?? 'Unknown'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Template Guide -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="text-xl font-bold mb-4">📚 Template Guide</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-bold mb-2">🎯 Usage Tips</h4>
                        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                            <li>Use {{VARIABLE}} format for dynamic content</li>
                            <li>Write HTML for better email formatting</li>
                            <li>Test emails before deploying</li>
                            <li>Keep subject lines under 50 characters</li>
                            <li>Include unsubscribe links where required</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold mb-2">📝 HTML Tips</h4>
                        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                            <li>Use inline CSS for better compatibility</li>
                            <li>Keep width under 600px for mobile</li>
                            <li>Always include alt text for images</li>
                            <li>Test in different email clients</li>
                            <li>Avoid large files and heavy graphics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Email Modal -->
    <div id="testModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full m-4">
            <h3 class="text-xl font-bold mb-4">📧 Send Test Email</h3>
            <form method="POST" onsubmit="return submitTest(event)">
                <input type="hidden" name="action" value="send_test">
                <input type="hidden" name="template_id" id="testTemplateId" value="">
                <input type="hidden" name="subject" id="testSubject" value="">
                <input type="hidden" name="body" id="testBody" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Recipient Email</label>
                    <input type="email" name="test_email" class="w-full border rounded-lg p-2" placeholder="your@email.com" required>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Send</button>
                    <button type="button" onclick="closeTestModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Switch between templates
        function switchTemplate(templateId) {
            document.querySelectorAll('.template-tab').forEach(tab => tab.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('tab-' + templateId)?.classList.remove('hidden');
            event.target.closest('.tab-button')?.classList.add('active');
        }

        // Insert variable into textarea
        function insertVariable(variable) {
            const templateTab = document.querySelector('.template-tab:not(.hidden)');
            const textarea = templateTab?.querySelector('textarea[name="body"]');
            if (textarea) {
                textarea.value += '{{' + variable + '}}';
                textarea.focus();
            }
        }

        // Send test email
        function sendTest(templateId) {
            const templateTab = document.getElementById('tab-' + templateId);
            const subject = templateTab.querySelector('input[name="subject"]').value;
            const body = templateTab.querySelector('textarea[name="body"]').value;
            
            document.getElementById('testTemplateId').value = templateId;
            document.getElementById('testSubject').value = subject;
            document.getElementById('testBody').value = body;
            document.getElementById('testModal').classList.remove('hidden');
        }

        function closeTestModal() {
            document.getElementById('testModal').classList.add('hidden');
        }

        function submitTest(e) {
            // Form will submit normally
            return true;
        }

        // Initialize first tab as active
        window.addEventListener('DOMContentLoaded', () => {
            const firstTab = document.querySelector('.tab-button');
            if (firstTab) {
                firstTab.classList.add('active');
            }
        });
    </script>
</body>
</html>
