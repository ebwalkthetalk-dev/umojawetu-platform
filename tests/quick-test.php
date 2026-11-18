<?php
/**
 * Quick Security Test Report
 * Simple command-line format for easy reading
 * 
 * Usage: php tests/quick-test.php
 */

require_once __DIR__ . '/../config/config.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         SECURITY TEST REPORT - QUICK FORMAT                ║\n";
echo "║         Umojawetu Platform Security Verification           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$tests = [];
$passCount = 0;
$failCount = 0;

function test($name, $condition) {
    global $tests, $passCount, $failCount;
    $passed = (bool)$condition;
    if ($passed) $passCount++; else $failCount++;
    $tests[] = [
        'name' => $name,
        'passed' => $passed
    ];
}

// Run all tests
echo "🔍 RUNNING SECURITY TESTS...\n\n";

// Core Functions
test("Security functions included", function_exists('setCorsHeaders'));
test("Input validation functions", function_exists('sanitizeInput'));
test("Required field validation", function_exists('validateRequired'));
test("Admin login verification", function_exists('verifyAdminLogin'));
test("CSRF token generation", function_exists('generateCsrfToken'));
test("Session security checks", function_exists('isSessionExpired'));

// Sanitization
test("HTML XSS protection", strpos(sanitizeInput('<script>xss</script>'), '<script>') === false);
test("Email validation - valid", sanitizeInput('test@test.com', 'email') === 'test@test.com');
test("Email validation - invalid", sanitizeInput('invalid', 'email') === null);
test("Integer validation - valid", sanitizeInput('42', 'int') === 42);
test("Integer validation - invalid", sanitizeInput('abc', 'int') === null);
test("Float validation - valid", sanitizeInput('3.14', 'float') === 3.14);

// Required fields
$errors = validateRequired(['a' => '', 'b' => 'value'], ['a', 'b']);
test("Required fields - detects empty", isset($errors['a']));
test("Required fields - allows present", !isset($errors['b']));

// Admin credentials
$admin = getDefaultAdminCredentials();
test("Admin credentials loaded", !empty($admin['username']));

// Admin login
$result = verifyAdminLogin($admin['username'], $admin['password']);
test("Admin login - correct password", $result['success']);
test("Admin login - wrong password", !verifyAdminLogin($admin['username'], 'wrong123')['success']);

// CSRF
$token = generateCsrfToken();
test("CSRF token generated", !empty($token));
test("CSRF validation - correct", verifyCsrfToken($token));
test("CSRF validation - incorrect", !verifyCsrfToken('wrong'));

// Session timing
$_SESSION['admin_login_time'] = time() - 1860; // 31 minutes ago
test("Session timeout - detects expired", isSessionExpired());
$_SESSION['admin_login_time'] = time() - 300; // 5 minutes ago
test("Session timeout - allows valid", !isSessionExpired());

// Environment
test("Environment detection", is_bool(Environment::isLocal()));

// Database
try {
    $db = getDbConnection();
    test("Database connection", $db instanceof SQLite3);
} catch (Exception $e) {
    test("Database connection", false);
}

// Print results
echo "═══════════════════════════════════════════════════════════\n\n";
echo "RESULTS:\n";
echo "────────────────────────────────────────────────────────────\n";

foreach ($tests as $t) {
    $status = $t['passed'] ? '✓ PASS' : '✗ FAIL';
    $color = $t['passed'] ? "\033[92m" : "\033[91m"; // Green or red
    $reset = "\033[0m";
    printf("%s %-50s %s\n", $color, $t['name'] . ':', $status . $reset);
}

echo "\n═══════════════════════════════════════════════════════════\n";
printf("Total Tests: %d | Passed: %d | Failed: %d\n", 
    count($tests), $passCount, $failCount);
echo "═══════════════════════════════════════════════════════════\n";

// Calculate pass rate
$passRate = count($tests) > 0 ? round(($passCount / count($tests)) * 100, 2) : 0;
printf("Pass Rate: %.2f%%\n", $passRate);

if ($passCount === count($tests)) {
    echo "\n✓ All security tests passed! System is secure.\n";
} else {
    echo "\n✗ Some tests failed. Review security configuration.\n";
}

echo "\nEnvironment: " . (Environment::isLocal() ? 'LOCAL (Development)' : 'PRODUCTION') . "\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "\n";

?>
