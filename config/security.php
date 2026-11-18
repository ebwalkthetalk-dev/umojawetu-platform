<?php
/**
 * Security Helper Functions
 * Centralized security functions for CORS, validation, and credentials
 *
 * All functions are wrapped in function_exists() guards so this file can be
 * safely included multiple times (from config.php, auth.php, individual pages,
 * and API endpoints) without triggering "Cannot redeclare function" fatals.
 */

// ============================================
// CORS Configuration
// ============================================

/**
 * Set secure CORS headers based on environment
 * Production: Restrict to specific origin
 * Local: Allow localhost
 */
if (!function_exists('setCorsHeaders')) {
    function setCorsHeaders() {
        $isLocal = Environment::isLocal();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Define allowed origins
        $allowedOrigins = [];

        if ($isLocal) {
            $allowedOrigins = [
                'http://localhost',
                'http://localhost/umojawetu-platform',
                'http://127.0.0.1',
                'http://127.0.0.1/umojawetu-platform'
            ];
        } else {
            // Production - restrict to specific domain
            $allowedOrigins = [
                'https://event.umojawetu.com',
                'https://www.event.umojawetu.com'
            ];
        }

        if (in_array($origin, $allowedOrigins) || $isLocal) {
            header('Access-Control-Allow-Origin: ' . ($isLocal ? '*' : $origin));
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// ============================================
// Input Validation & Sanitization
// ============================================

/**
 * Validate and sanitize string input
 */
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input, $type = 'string') {
        if (is_null($input)) {
            return null;
        }

        $input = trim($input);

        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : null;

            case 'int':
            case 'integer':
                return filter_var($input, FILTER_VALIDATE_INT) !== false ? intval($input) : null;

            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? floatval($input) : null;

            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) ? $input : null;

            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
}

/**
 * Validate required fields in POST/GET data
 * Returns array of errors if validation fails
 */
if (!function_exists('validateRequired')) {
    function validateRequired($data, $requiredFields) {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        return $errors;
    }
}

// ============================================
// Admin Credentials
// ============================================

/**
 * Get default admin credentials from environment
 * Falls back to safe defaults for development
 */
if (!function_exists('getDefaultAdminCredentials')) {
    function getDefaultAdminCredentials() {
        $isLocal = Environment::isLocal();

        return [
            'username' => getenv('ADMIN_USERNAME') ?: ($isLocal ? 'admin' : ''),
            'password' => getenv('ADMIN_PASSWORD') ?: ($isLocal ? 'admin123' : ''),
            'email' => getenv('ADMIN_EMAIL') ?: 'admin@umojawetu.com'
        ];
    }
}

/**
 * Verify admin login (check both database and environment fallback)
 */
if (!function_exists('verifyAdminLogin')) {
    function verifyAdminLogin($username, $password) {
        global $db;

        try {
            // First, try to fetch from database
            $stmt = $db->prepare('SELECT id, username, email, password_hash, full_name FROM admin_users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->bindValue(1, $username, SQLITE3_TEXT);
            $stmt->bindValue(2, $username, SQLITE3_TEXT);
            $result = $stmt->execute();

            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Password found in database, verify hash
                if (password_verify($password, $row['password_hash'])) {
                    return [
                        'success' => true,
                        'user' => $row
                    ];
                }
            }
        } catch (Exception $e) {
            // Database might not be ready, fall through to environment check
        }

        // Fallback: Check environment variables
        $envAdmin = getDefaultAdminCredentials();
        if ($username === $envAdmin['username'] && $password === $envAdmin['password']) {
            return [
                'success' => true,
                'user' => [
                    'id' => 0,
                    'username' => $envAdmin['username'],
                    'email' => $envAdmin['email'],
                    'full_name' => 'Administrator'
                ]
            ];
        }

        return [
            'success' => false,
            'user' => null
        ];
    }
}

// ============================================
// Session Security
// ============================================

/**
 * Check if session has timed out (30 minutes default)
 */
if (!function_exists('isSessionExpired')) {
    function isSessionExpired($timeout = 1800) {
        if (!isset($_SESSION['admin_login_time'])) {
            return true;
        }

        if (time() - $_SESSION['admin_login_time'] > $timeout) {
            return true;
        }

        return false;
    }
}

/**
 * Verify admin is logged in and session is valid
 */
if (!function_exists('requireAdminLogin')) {
    function requireAdminLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header('Location: login-new.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }

        if (isSessionExpired()) {
            session_destroy();
            header('Location: login-new.php?expired=1');
            exit;
        }

        // Refresh session timestamp
        $_SESSION['admin_login_time'] = time();
    }
}

/**
 * Generate CSRF token for forms
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verify CSRF token
 */
if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

?>