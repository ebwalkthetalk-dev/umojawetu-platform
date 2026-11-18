<?php
// Main configuration file - SQLite Only
require_once __DIR__ . '/environments.php';

// ============================================
// Load .env into process environment
// ============================================
// Reads config/.env and loads into putenv(), $_ENV, $_SERVER.
// This makes getenv('KEY') work everywhere in the codebase.
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key === '') continue;
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

$env = Environment::detect();
$isLocal = Environment::isLocal();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site settings
$site_name = "Umojawetu Platform";
$site_url = $isLocal ? 'http://localhost/umojawetu-platform' : 'https://event.umojawetu.com';
$admin_email = "admin@umojawetu.com";

// Timezone
date_default_timezone_set('Europe/London');

// ============================================
// SQLITE DATABASE CONNECTION (No MySQL needed)
// ============================================

function getDbConnection() {
    $isLocal = Environment::isLocal();

    $dbPaths = [
        __DIR__ . '/../storage/umoja-events-database.db',
        __DIR__ . '/../api/umoja-events-database.db',
        'C:/xampp/htdocs/umojawetu-platform/storage/umoja-events-database.db'
    ];

    $dbPath = null;
    foreach ($dbPaths as $path) {
        if (file_exists($path)) {
            $dbPath = $path;
            break;
        }
    }

    if (!$dbPath) {
        die('Database not found. Please copy umoja-events-database.db to /storage/ folder.');
    }

    try {
        $db = new SQLite3($dbPath);
        $db->busyTimeout(5000);
        return $db;
    } catch (Exception $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Create global database connection variable
$db = getDbConnection();

// For backward compatibility with code that expects MySQL connection
$conn = null;

// Load security helper functions
require_once __DIR__ . '/security.php';

// Load soft delete utilities
require_once __DIR__ . '/soft-delete.php';

// Load live chat utilities
require_once __DIR__ . '/chat.php';

// ============================================
// Stripe configuration
// ============================================
// Reads from .env. Fails loudly if keys are missing.
// Never returns placeholder values — those are bugs waiting to happen.
function getStripeConfig() {
    $pk = getenv('STRIPE_PUBLISHABLE_KEY');
    $sk = getenv('STRIPE_SECRET_KEY');

    if (empty($pk) || empty($sk)) {
        error_log('SECURITY: Stripe keys not configured in .env');
        return ['error' => 'Payment configuration unavailable'];
    }

    return [
        'publishable_key' => $pk,
        'secret_key' => $sk,
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
        'test_mode' => (strpos($pk, 'pk_test_') === 0)
    ];
}

// ============================================
// Email configuration
// ============================================
function sendEmail($to, $subject, $body) {
    $isLocal = Environment::isLocal();

    if ($isLocal) {
        $logDir = __DIR__ . '/../storage/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/emails.log';
        $logEntry = date('Y-m-d H:i:s') . " | TO: $to | SUBJECT: $subject\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        return true;
    } else {
        // Phase 4: replace with Postmark/SendGrid adapter
        error_log('sendEmail() production stub called - email not sent');
        return true;
    }
}

// Database variables for backward compatibility (unused by SQLite path)
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'umojawetu_db';