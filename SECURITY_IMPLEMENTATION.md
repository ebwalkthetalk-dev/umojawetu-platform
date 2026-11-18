# Security Implementation Guide

This document explains the security improvements made to the Umojawetu Platform.

## Overview of Changes

### 1. Centralized Security Functions (config/security.php)

All security functions are now centralized in a single file that's automatically loaded via config.php. This includes:

- **setCorsHeaders()** - Secure CORS configuration
- **sanitizeInput()** - Input validation and sanitization
- **validateRequired()** - Required field validation
- **verifyAdminLogin()** - Secure admin authentication
- **Session security functions** - Timeout, CSRF protection

## Security Improvements

### 1. ✅ Hardcoded Credentials - FIXED

**Before:**
```php
// Hardcoded password in login.php
$admin_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
```

**After:**
```php
// Uses environment variables or database via verifyAdminLogin()
$loginResult = verifyAdminLogin($username, $password);
if ($loginResult['success']) { ... }
```

**Setup:**
- Admin credentials are now stored in `.env` file
- Falls back to database admin_users table if available
- Location: `/config/.env`

```env
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123
ADMIN_EMAIL=admin@umojawetu.com
```

### 2. ✅ Open CORS - FIXED

**Before:**
```php
header('Access-Control-Allow-Origin: *');  // Allows ANY domain
```

**After:**
```php
setCorsHeaders();  // Smart, environment-aware CORS
```

**How it works:**
- **Local**: Allows localhost (for development)
- **Production**: Restricted to `https://event.umojawetu.com`

All 19 API files have been updated:
- api/booking/bookings-wizard.php
- api/stripe/*.php
- api/media/*.php
- api/pledges/*.php
- api/requests/*.php
- api/events/events.php
- api/attendees/get-attendee.php
- public/*.php

### 3. ✅ Input Validation - IMPLEMENTED

Added comprehensive input validation to all critical APIs.

**Example - Pledge Creation:**
```php
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
$amountPledged = sanitizeInput($input['amount_pledged'], 'float');

// Validate email format
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email']);
    exit();
}

// Validate amount
if (!$amountPledged || $amountPledged <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid amount']);
    exit();
}
```

## Using the Security Functions

### Input Validation

```php
// Validate required fields
$errors = validateRequired([
    'username' => $_POST['username'] ?? '',
    'email' => $_POST['email'] ?? '',
], ['username', 'email']);

if (!empty($errors)) {
    // Handle errors
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}
```

### Input Sanitization

```php
// Sanitize different data types
$username = sanitizeInput($_POST['username']);           // string
$email = sanitizeInput($_POST['email'], 'email');       // email
$userId = sanitizeInput($_POST['id'], 'int');           // integer
$price = sanitizeInput($_POST['price'], 'float');       // float
$website = sanitizeInput($_POST['url'], 'url');         // URL
```

### Admin Login

```php
// In login form handlers
$result = verifyAdminLogin($username, $password);

if ($result['success']) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $result['user']['username'];
    // ... redirect to dashboard
} else {
    $error = 'Invalid credentials';
}
```

### CSRF Protection

```php
// In form
<input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

// In form handler
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die('CSRF token invalid');
}
```

### Require Admin Login

```php
<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in, has valid session, redirect if needed
requireAdminLogin();

// Code here only runs if admin is logged in
?>
```

## Environment Configuration

Sensitive data is stored in `.env` file:

```env
# Application Environment
APP_ENV=local

# Admin Credentials (Local Development)
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123
ADMIN_EMAIL=admin@umojawetu.com

# Database Configuration
DB_HOST=localhost
DB_NAME=umojawetu_db
DB_USER=root
DB_PASSWORD=

# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```

## Session Security

### Session Timeout (30 minutes)

```php
// Automatically checked when requireAdminLogin() is called
// Or manually check:
if (isSessionExpired()) {
    session_destroy();
    // Redirect to login
}
```

### CSRF Tokens

```php
// Generate token in forms
generateCsrfToken();

// Verify in handlers
verifyCsrfToken($_POST['csrf_token'] ?? '');
```

## Next Steps: Additional Hardening

1. **Database Security**
   - Use prepared statements everywhere (not just in new code)
   - Never concatenate SQL with user input

2. **Production Checklist**
   - Change ADMIN_USERNAME and ADMIN_PASSWORD in production
   - Use strong passwords (20+ characters)
   - Store credentials in secure vault for production
   - Enable HTTPS only
   - Set secure session cookies

3. **Rate Limiting**
   - Add rate limiting to login endpoints
   - Prevent brute force attacks

4. **Logging**
   - Log all admin actions
   - Log failed login attempts

5. **Testing**
   - Test CORS with different origins
   - Test input validation edge cases
   - Security audit of payment flow

## File Locations

- Security functions: `config/security.php`
- Configuration: `config/config.php`
- Environment variables: `config/.env`
- Admin login: `admin/login.php`
- Admin login (database-backed): `admin/login-new.php`

## Backward Compatibility

All changes are backward compatible. Existing code continues to work, but new code should use the security functions.
