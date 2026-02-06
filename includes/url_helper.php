<?php
/**
 * URL Helper for CannaBuddy
 * Automatically detects base URL for any deployment scenario
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Cache the base URL to avoid recalculation
$_GLOBALS['cannabuddy_base_url'] = null;
$_GLOBALS['cannabuddy_base_path'] = null;

/**
 * Get the application's base path (URL path portion only)
 * Uses the location of this file to determine the app root
 */
function getAppBasePath() {
    global $_GLOBALS;

    // Return cached value if available
    if (isset($_GLOBALS['cannabuddy_base_path']) && $_GLOBALS['cannabuddy_base_path'] !== null) {
        return $_GLOBALS['cannabuddy_base_path'];
    }

    $basePath = '';

    // Method 1: Use document root to calculate relative path
    // This is the most reliable method when configured correctly
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = str_replace('\\', '/', BASE_PATH);

    // Calculate the path from document root to app root
    if (!empty($docRoot) && stripos($appRoot, $docRoot) === 0) {
        $basePath = substr($appRoot, strlen($docRoot));
    } else {
        // Method 2: Structure-based detection (Fallback)
        // If DocumentRoot doesn't match (common in symlinked setups or complex hosting),
        // we deduce base path by known file structure.
        
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        
        // List of known entry points relative to App Root
        $knownEntries = [
            '/product/index.php',
            '/shop/index.php',
            '/user/index.php',
            '/admin/index.php',
            '/index.php'
        ];

        foreach ($knownEntries as $entry) {
            // Check if script name ends with a known entry point
            if (substr($scriptName, -strlen($entry)) === $entry) {
                // Extract the part BEFORE the entry point
                $basePath = substr($scriptName, 0, -strlen($entry));
                break;
            }
        }
    }

    // Normalize: ensure proper format (add leading slash, remove trailing)
    $basePath = '/' . ltrim($basePath, '/');
    $basePath = rtrim($basePath, '/');

    // Handle root deployment (no subdirectory)
    if ($basePath === '/' || $basePath === '/.') {
        $basePath = '';
    }

    $_GLOBALS['cannabuddy_base_path'] = $basePath;
    return $basePath;
}

/**
 * Auto-detect and get the base URL
 * Works for any deployment: localhost/CannaBuddy.shop/, localhost/, cannakingdom.ky/, etc.
 */
function getBaseUrl() {
    global $_GLOBALS;

    // Return cached value if available
    if (isset($_GLOBALS['cannabuddy_base_url']) && $_GLOBALS['cannabuddy_base_url'] !== null) {
        return $_GLOBALS['cannabuddy_base_url'];
    }

    // Get protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        ? 'https'
        : 'http';

    // Get host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Get base path using reliable method
    $basePath = getAppBasePath();

    // Build full base URL with trailing slash
    $baseUrl = $protocol . '://' . $host . $basePath . '/';

    // Cache it
    $_GLOBALS['cannabuddy_base_url'] = $baseUrl;

    return $baseUrl;
}

/**
 * Generate a full URL from a path
 * Example: url('/admin/login/') -> 'http://localhost/CannaBuddy.shop/admin/login/'
 */
function url($path = '') {
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');
    return $baseUrl . $path;
}

/**
 * Generate a relative URL from a path
 * Example: rurl('/admin/login/') -> '/CannaBuddy.shop/admin/login/' or '/admin/login/'
 */
function rurl($path = '') {
    // Use the same base path calculation as getBaseUrl
    $basePath = getAppBasePath();

    // Combine base path with requested path
    $requestedPath = ltrim($path, '/');
    $fullPath = $basePath . '/' . $requestedPath;

    return $fullPath;
}

/**
 * Redirect to a URL
 * Automatically prepends base URL if not absolute
 */
function redirect($path, $code = 302) {
    $fullUrl = $path;
    
    // Generate full URL for relative paths
    if (strpos($path, 'http') !== 0) {
        $fullUrl = url($path);
    }
    
    // Only validate absolute URLs (not relative paths starting with /)
    if (strpos($path, 'http') === 0 && !validateRedirect($fullUrl)) {
        // Log potential open redirect attempt
        error_log("Blocked potential open redirect to: " . $fullUrl);
        // Fallback to home
        $fullUrl = url('/');
    }
    
    header("Location: $fullUrl", true, $code);
    exit;
}

/**
 * Generate a URL for admin section
 */
function adminUrl($path = '') {
    return url('admin/' . ltrim($path, '/'));
}

/**
 * Generate a URL for user section
 */
function userUrl($path = '') {
    return url('user/' . ltrim($path, '/'));
}

/**
 * Generate a URL for shop section
 */
function shopUrl($path = '') {
    return url('shop/' . ltrim($path, '/'));
}

/**
 * Generate a URL for product
 */
function productUrl($slug) {
    return url('product/' . ltrim($slug, '/'));
}

/**
 * Get asset URL (full URL with protocol and host)
 */
function assetUrl($path) {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Get asset path for database storage (relative path only, no host/protocol)
 * Use this when saving image paths to database
 * Example: assetPath('images/slider/file.jpg') -> '/assets/images/slider/file.jpg'
 */
function assetPath($path) {
    return '/assets/' . ltrim($path, '/');
}

/**
 * Safely escape HTML content, handling null values (PHP 8.1+ compatibility)
 *
 * @param mixed $string The value to escape
 * @param int $flags ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 by default
 * @param string|null $encoding UTF-8 by default
 * @param bool $double_encode True by default
 * @return string
 */
function safe_html($string, $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $encoding = 'UTF-8', $double_encode = true) {
    return htmlspecialchars((string)($string ?? ''), $flags, $encoding, $double_encode);
}

/**
 * Validate a redirect URL against a whitelist of allowed paths
 * Prevents open redirect vulnerabilities
 *
 * @param string $redirect The redirect URL to validate
 * @param array $allowedPaths Optional array of allowed path patterns
 * @return bool True if redirect is allowed, false otherwise
 */
function validateRedirect($redirect, $allowedPaths = null) {
    // Default allowed paths (relative paths only)
    if ($allowedPaths === null) {
        $allowedPaths = [
            '/admin',
            '/admin/login',
            '/user/dashboard',
            '/user/orders',
            '/user/profile',
            '/user/checkout',
            '/user/cart',
            '/shop',
            '/product'
        ];
    }

    // Empty redirect is valid (will use default)
    if (empty($redirect)) {
        return true;
    }

    // Check for protocol-relative URLs (security risk)
    if (strpos($redirect, '//') === 0) {
        error_log("Blocked protocol-relative redirect: {$redirect}");
        return false;
    }

    // Check for absolute URLs with different protocol
    if (preg_match('#^(https?:|//)#i', $redirect)) {
        error_log("Blocked external URL redirect: {$redirect}");
        return false;
    }

    // Must start with / (relative path)
    if (strpos($redirect, '/') !== 0) {
        error_log("Blocked redirect not starting with /: {$redirect}");
        return false;
    }

    // Check against whitelist (must start with one of the allowed paths)
    foreach ($allowedPaths as $allowed) {
        if (strpos($redirect, $allowed) === 0) {
            return true;
        }
    }

    // Block if not in whitelist
    error_log("Blocked redirect not in whitelist: {$redirect}");
    return false;
}

/**
 * Generate CSRF token for forms
 * Creates and/or returns the current CSRF token from session
 *
 * @return string The CSRF token
 */
function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 32) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Generate HTML hidden input field for CSRF token
 * Usage: echo csrf_field();
 *
 * @return string HTML input element
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request
 * Returns true if token is valid, false otherwise
 *
 * @param string|null $token The token to verify (uses $_POST['csrf_token'] if null)
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    // Check token expiration (1 hour)
    if (isset($_SESSION['csrf_token_created'])) {
        $age = time() - $_SESSION['csrf_token_created'];
        if ($age > 3600) {
            // Token expired
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_created']);
            return false;
        }
    }

    // Use timing-safe comparison
    return hash_equals($sessionToken, $token);
}

/**
 * Regenerate CSRF token (call after successful form submission)
 */
function csrf_regenerate() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_created'] = time();
}

// Auto-load the helper when included
// Usage in any file:
// require_once __DIR__ . '/url_helper.php';
// Then use: url('/admin/') to generate URLs
