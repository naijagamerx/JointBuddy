<?php
/**
 * Session Helper - Unified session management
 *
 * Provides consistent session handling across the application
 * Prevents session fixation and ensures secure configuration
 *
 * @package CannaBuddy
 */

if (!function_exists('ensureSessionStarted')) {
    /**
     * Ensure session is started with secure configuration
     * Only configures once, subsequent calls are safe no-ops
     *
     * @return void
     */
    function ensureSessionStarted(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Only set cookie params if headers haven't been sent
        if (!headers_sent()) {
            // Use app base path for cookie to prevent conflicts with other apps on localhost
            $cookiePath = function_exists('getAppBasePath') ? (getAppBasePath() ?: '/') : '/';
            
            // Set a unique session name to prevent conflicts
            session_name('CANNABUDDY_SESSION');
            
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie (expires when browser closes)
                'path' => $cookiePath,
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true, // Prevent JavaScript access to session ID
                'samesite' => 'Strict' // Prevent CSRF attacks
            ]);
        }

        // Only start session if headers haven't been sent
        // In testing environment, session may already be started
        if (!headers_sent()) {
            @session_start();
        }
    }
}

if (!function_exists('regenerateSession')) {
    /**
     * Regenerate session ID to prevent session fixation
     * Call after authentication changes (login, logout, privilege change)
     *
     * @return void
     */
    function regenerateSession(): void {
        ensureSessionStarted();
        session_regenerate_id(true);
    }
}

if (!function_exists('destroySession')) {
    /**
     * Completely destroy the current session
     * Use for logout operations
     *
     * @return void
     */
    function destroySession(): void {
        ensureSessionStarted();

        // Unset all session variables
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        // Destroy session data
        session_destroy();
    }
}

if (!function_exists('sessionFlash')) {
    /**
     * Set a flash message that persists until next request
     *
     * @param string $key Message key (success, error, info, warning)
     * @param string $message Message content
     * @return void
     */
    function sessionFlash(string $key, string $message): void {
        ensureSessionStarted();
        $_SESSION['_flash'][$key] = $message;
    }
}

if (!function_exists('sessionGetFlash')) {
    /**
     * Get and clear a flash message
     *
     * @param string $key Message key
     * @return string|null Message content or null if not exists
     */
    function sessionGetFlash(string $key): ?string {
        ensureSessionStarted();

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $message;
    }
}

if (!function_exists('sessionHasFlash')) {
    /**
     * Check if a flash message exists
     *
     * @param string $key Message key
     * @return bool True if message exists
     */
    function sessionHasFlash(string $key): bool {
        ensureSessionStarted();
        return isset($_SESSION['_flash'][$key]);
    }
}

if (!function_exists('sessionSet')) {
    /**
     * Set a session variable
     *
     * @param string $key Variable name
     * @param mixed $value Value to store
     * @return void
     */
    function sessionSet(string $key, $value): void {
        ensureSessionStarted();
        $_SESSION[$key] = $value;
    }
}

if (!function_exists('sessionGet')) {
    /**
     * Get a session variable
     *
     * @param string $key Variable name
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Session value or default
     */
    function sessionGet(string $key, $default = null) {
        ensureSessionStarted();
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('sessionRemove')) {
    /**
     * Remove a session variable
     *
     * @param string $key Variable name
     * @return void
     */
    function sessionRemove(string $key): void {
        ensureSessionStarted();
        unset($_SESSION[$key]);
    }
}
