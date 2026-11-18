<?php
require_once __DIR__ . '/../config/config.php';

$env = Environment::detect();
$envConfig = Environment::getConfig();

echo "<h1>Environment Status</h1>";
echo "<p><strong>Current Environment:</strong> " . ucfirst($env) . "</p>";
echo "<p><strong>Debug Mode:</strong> " . ($envConfig['debug'] ? 'ON' : 'OFF') . "</p>";
echo "<p><strong>Database:</strong> " . $db_name . "</p>";
echo "<p><strong>Site URL:</strong> " . $site_url . "</p>";
echo "<p><strong>Stripe Mode:</strong> " . ($envConfig['stripe_test_mode'] ? 'Test Mode' : 'Live Mode') . "</p>";
echo "<p><strong>Email Sending:</strong> " . ($envConfig['email_log_only'] ? 'Log Only (No real emails)' : 'Real Emails') . "</p>";
?>