<?php
/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment for all test cases
 */

declare(strict_types=1);

// Set error reporting to maximum for testing
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test environment
define('TESTING', true);
define('BASE_PATH', dirname(__DIR__));

// Start session BEFORE any output to avoid headers already sent errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mock server variables for testing
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
if (!isset($_SERVER['HTTPS'])) {
    $_SERVER['HTTPS'] = 'off';
}
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = BASE_PATH;
}
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

// Autoload dependencies
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Load required includes
require_once dirname(__DIR__) . '/includes/session_helper.php';
require_once dirname(__DIR__) . '/includes/url_helper.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/commerce/CurrencyService.php';
require_once dirname(__DIR__) . '/includes/services/Services.php';
require_once dirname(__DIR__) . '/includes/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/includes/middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/includes/validation/Validator.php';

// Mock redirect function to prevent actual redirects during tests
if (!function_exists('redirect')) {
    function redirect($path, $code = 302): void {
        throw new RuntimeException("Redirect to: {$path} (HTTP {$code})");
    }
}

// Helper function to reset global state
function resetGlobalState(): void {
    $_GET = [];
    $_POST = [];
    $_SESSION = [];
    $_COOKIE = [];
    $_FILES = [];
    $_REQUEST = [];

    // Reset server variables to defaults
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // Reset Services singleton
    if (class_exists('Services')) {
        Services::reset();
    }
}
