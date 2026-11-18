<?php
/**
 * Security Test Suite
 * Tests CORS, Admin Login, Input Validation, and Session Security
 * 
 * Location: /tests/security-tests.php
 * Usage: Visit http://localhost/umojawetu-platform/tests/security-tests.php
 */

require_once __DIR__ . '/../config/config.php';

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$testResults = [];
$passCount = 0;
$failCount = 0;

/**
 * Test helper function
 */
function runTest($testName, $condition, $expectedValue = true) {
    global $passCount, $failCount, $testResults;
    
    $result = [
        'name' => $testName,
        'passed' => $condition === $expectedValue,
        'value' => $condition,
        'expected' => $expectedValue
    ];
    
    if ($result['passed']) {
        $passCount++;
    } else {
        $failCount++;
    }
    
    $testResults[] = $result;
    return $result['passed'];
}

// ============================================
// TEST 1: CORS Headers Function Exists
// ============================================
runTest('setCorsHeaders() function exists', function_exists('setCorsHeaders'));

// ============================================
// TEST 2: Input Validation Functions
// ============================================
runTest('sanitizeInput() function exists', function_exists('sanitizeInput'));
runTest('validateRequired() function exists', function_exists('validateRequired'));

// ============================================
// TEST 3: Sanitization Tests
// ============================================

// Test string sanitization
$dirtyString = '<script>alert("xss")</script>';
$cleanString = sanitizeInput($dirtyString);
runTest('sanitizeInput() escapes HTML', 
    strpos($cleanString, '<script>') === false,
    true
);

// Test email validation
$validEmail = sanitizeInput('test@example.com', 'email');
runTest('sanitizeInput() validates email', 
    $validEmail === 'test@example.com',
    true
);

$invalidEmail = sanitizeInput('not-an-email', 'email');
runTest('sanitizeInput() rejects invalid email', 
    $invalidEmail === null,
    true
);

// Test integer validation
$validInt = sanitizeInput('42', 'int');
runTest('sanitizeInput() validates integer', 
    $validInt === 42,
    true
);

$invalidInt = sanitizeInput('abc', 'int');
runTest('sanitizeInput() rejects non-integer', 
    $invalidInt === null,
    true
);

// Test float validation
$validFloat = sanitizeInput('3.14', 'float');
runTest('sanitizeInput() validates float', 
    $validFloat === 3.14,
    true
);

// ============================================
// TEST 4: Required Field Validation
// ============================================

$testData = [
    'username' => 'testuser',
    'email' => 'test@example.com',
    'password' => ''
];

$errors = validateRequired($testData, ['username', 'email', 'password']);
runTest('validateRequired() catches missing fields', 
    isset($errors['password']),
    true
);

$errors = validateRequired($testData, ['username', 'email']);
runTest('validateRequired() passes when fields present', 
    empty($errors),
    true
);

// ============================================
// TEST 5: Admin Credentials System
// ============================================

$defaultAdmin = getDefaultAdminCredentials();
runTest('getDefaultAdminCredentials() returns array', 
    is_array($defaultAdmin),
    true
);

runTest('Admin username configured from .env or default', 
    !empty($defaultAdmin['username']),
    true
);

runTest('Admin email configured', 
    !empty($defaultAdmin['email']),
    true
);

// ============================================
// TEST 6: Admin Login Verification
// ============================================

// Test with correct credentials
$adminCreds = getDefaultAdminCredentials();
$loginResult = verifyAdminLogin($adminCreds['username'], $adminCreds['password']);
runTest('verifyAdminLogin() accepts correct credentials', 
    $loginResult['success'],
    true
);

// Test with wrong password
$loginResult = verifyAdminLogin($adminCreds['username'], 'wrong123');
runTest('verifyAdminLogin() rejects wrong password', 
    $loginResult['success'],
    false
);

// ============================================
// TEST 7: CSRF Token Generation & Verification
// ============================================

$token = generateCsrfToken();
runTest('generateCsrfToken() returns non-empty string', 
    !empty($token) && is_string($token),
    true
);

$isValid = verifyCsrfToken($token);
runTest('verifyCsrfToken() validates correct token', 
    $isValid,
    true
);

$isInvalid = verifyCsrfToken('wrong_token_123');
runTest('verifyCsrfToken() rejects incorrect token', 
    $isInvalid,
    false
);

// ============================================
// TEST 8: Session Timeout
// ============================================

// Simulate old login time (31 minutes ago)
$_SESSION['admin_login_time'] = time() - 1860;
$isExpired = isSessionExpired();
runTest('isSessionExpired() detects expired session (30+ min)', 
    $isExpired,
    true
);

// Simulate recent login (5 minutes ago)
$_SESSION['admin_login_time'] = time() - 300;
$isExpired = isSessionExpired();
runTest('isSessionExpired() allows recent session (< 30 min)', 
    $isExpired,
    false
);

// ============================================
// TEST 9: CORS Detection (Manual Check)
// ============================================

// Check if we're in local environment
$isLocal = Environment::isLocal();
runTest('Environment::isLocal() works', 
    is_bool($isLocal),
    true
);

// ============================================
// TEST 10: Database Connection
// ============================================

try {
    $db = getDbConnection();
    $dbConnected = ($db instanceof SQLite3);
    runTest('getDbConnection() returns SQLite3 instance', 
        $dbConnected,
        true
    );
} catch (Exception $e) {
    runTest('getDbConnection() throws exception on missing DB', 
        false,
        true
    );
}

// ============================================
// HTML OUTPUT
// ============================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Test Suite - Umojawetu Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .summary {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .passed {
            color: #28a745;
        }
        
        .failed {
            color: #dc3545;
        }
        
        .total {
            color: #667eea;
        }
        
        .tests-container {
            padding: 30px;
        }
        
        .test-result {
            margin-bottom: 15px;
            padding: 15px;
            border-left: 4px solid #ddd;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .test-result.pass {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .test-result.fail {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .test-info {
            flex-grow: 1;
        }
        
        .test-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .test-detail {
            font-size: 12px;
            color: #666;
        }
        
        .test-status {
            font-size: 24px;
            font-weight: bold;
            margin-left: 20px;
        }
        
        .pass .test-status {
            color: #28a745;
        }
        
        .fail .test-status {
            color: #dc3545;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .danger {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Security Test Suite</h1>
            <p>Umojawetu Platform - Testing latest security implementations</p>
        </div>
        
        <div class="summary">
            <div class="summary-item">
                <div class="summary-label">Passed</div>
                <div class="summary-value passed"><?php echo $passCount; ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Failed</div>
                <div class="summary-value failed"><?php echo $failCount; ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Tests</div>
                <div class="summary-value total"><?php echo count($testResults); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Pass Rate</div>
                <div class="summary-value" style="color: <?php echo $passCount === count($testResults) ? '#28a745' : '#dc3545'; ?>">
                    <?php echo count($testResults) > 0 ? round(($passCount / count($testResults)) * 100) : 0; ?>%
                </div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo count($testResults) > 0 ? round(($passCount / count($testResults)) * 100) : 0; ?>%"></div>
        </div>
        
        <div class="tests-container">
            <?php foreach ($testResults as $result): ?>
                <div class="test-result <?php echo $result['passed'] ? 'pass' : 'fail'; ?>">
                    <div class="test-info">
                        <div class="test-name">
                            <?php echo $result['passed'] ? '✓' : '✗'; ?> 
                            <?php echo htmlspecialchars($result['name']); ?>
                        </div>
                        <div class="test-detail">
                            Expected: <strong><?php echo var_export($result['expected'], true); ?></strong> |
                            Got: <strong><?php echo var_export($result['value'], true); ?></strong>
                        </div>
                    </div>
                    <div class="test-status">
                        <?php echo $result['passed'] ? '✓ PASS' : '✗ FAIL'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>Environment: <?php echo Environment::isLocal() ? 'LOCAL' : 'PRODUCTION'; ?></p>
            <p><small>For detailed results, check the browser console or server logs</small></p>
        </div>
    </div>
</body>
</html>
