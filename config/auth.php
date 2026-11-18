<?php
/**
 * config/auth.php — Unified API authentication middleware
 * Thin JSON-returning wrappers around config/security.php helpers.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

function requireAdminAuth(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required', 'code' => 'UNAUTHENTICATED']);
        exit;
    }
    if (function_exists('isSessionExpired') && isSessionExpired()) {
        session_destroy();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired', 'code' => 'SESSION_EXPIRED']);
        exit;
    }
    $_SESSION['admin_login_time'] = time();
}

function requireCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!function_exists('verifyCsrfToken') || !verifyCsrfToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token', 'code' => 'CSRF_FAILED']);
        exit;
    }
}
