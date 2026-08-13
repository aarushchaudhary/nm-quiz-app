<?php
/**
 * base_url.php
 * Centralized Base URL configuration for NMIMS Quiz App
 * 
 * This file provides a unified way to handle URLs across different environments:
 * - XAMPP/Apache subdirectory: /nmims_quiz_app/
 * - Built-in server root: /
 * - Environment variables or $_ENV can be used for runtime configuration
 */

// Detect environment and set BASE_URL accordingly
$environment = $_ENV['APP_ENV'] ?? 'development';

if ($environment === 'production') {
    // For production, configure based on your actual domain
    define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost:8080/');
} else {
    // For development (built-in server), use root path
    // For XAMPP, change to '/nmims_quiz_app/'
    define('BASE_URL', '/');
}

// Additional utility functions for path handling
function get_base_url() {
    return BASE_URL;
}

function get_asset_url($path) {
    return BASE_URL . ltrim($path, '/');
}

function get_api_url($path) {
    return BASE_URL . 'api/' . ltrim($path, '/');
}

function redirect($path) {
    $full_url = BASE_URL . ltrim($path, '/');
    header('Location: ' . $full_url);
    exit();
}

/**
 * Check if the current request is coming from the NMIMS Secure Browser.
 * Returns true if the secure browser User-Agent identifier is present.
 */
function is_secure_browser() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return strpos($user_agent, 'NMIMS-Secure-Browser') !== false;
}

/**
 * Legacy function — blocks access entirely if not using the secure browser.
 */
function enforce_secure_browser() {
    if (!is_secure_browser()) {
        http_response_code(403);
        die("<h1>Access Denied</h1><p>You must use the NMIMS Secure Browser to access this page.</p>");
    }
}

/**
 * Enforce secure browser for exam-related pages and APIs.
 * - For page requests: redirects to the student dashboard with an error message.
 * - For API requests (JSON): returns a 403 JSON error.
 * 
 * Call this at the top of exam-critical files (lobby, exam, disqualified pages and all exam APIs).
 */
function enforce_secure_browser_for_exam($is_api = false) {
    if (is_secure_browser()) {
        return; // Secure browser detected, allow access
    }

    if ($is_api) {
        // API endpoint — return JSON error
        header('Content-Type: application/json');
        http_response_code(403);
        exit(json_encode([
            'error' => 'Access denied. You must use the NMIMS Secure Browser to take exams.'
        ]));
    } else {
        // Page request — redirect to dashboard with error
        header('Location: ' . get_base_url() . 'views/student/dashboard.php?error=secure_browser_required');
        exit();
    }
}

/**
 * Migration Notes:
 * - For XAMPP (Apache in subdirectory): Change BASE_URL to '/nmims_quiz_app/'
 * - For built-in server (port 8080): Keep BASE_URL as '/'
 * - For Docker/production: Set BASE_URL via environment variable
 * 
 * Usage:
 * - In PHP: echo get_asset_url('assets/css/main.css');
 * - In HTML: <link href="<?= get_asset_url('assets/css/main.css') ?>">
 * - For redirects: redirect('login.php?error=db_error');
 */
