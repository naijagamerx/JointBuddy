<?php
/**
 * CSRF Middleware
 *
 * Provides CSRF protection for all forms
 * Prevents Cross-Site Request Forgery attacks
 *
 * @package CannaBuddy
 */

class CsrfMiddleware {

    /**
     * Validate CSRF token for current request
     * Throws exception or redirects if validation fails
     *
     * @return void
     */
    public static function validate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!self::isValid($token)) {
            http_response_code(403);
            error_log('CSRF validation failed for: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
            
            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh the page and try again.']);
                exit;
            }
            
            sessionFlash('csrf_error', 'Security check failed. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }

        // Only regenerate if it's NOT an AJAX request to prevent token rotation during multiple async calls
        if (!isAjax()) {
            self::regenerate();
        }
    }

    /**
     * Check if CSRF token is valid
     *
     * @param string $token Token to validate
     * @return bool True if token is valid
     */
    public static function isValid(string $token): bool {
        return verifyCsrfToken($token);
    }

    /**
     * Get CSRF token for forms
     *
     * @return string CSRF token
     */
    public static function getToken(): string {
        return csrf_token();
    }

    /**
     * Get CSRF HTML field
     *
     * @return string HTML input element with CSRF token
     */
    public static function getField(): string {
        return csrf_field();
    }

    /**
     * Regenerate CSRF token
     * Call after successful form submission
     *
     * @return void
     */
    public static function regenerate(): void {
        csrf_regenerate();
    }

    /**
     * Get AJAX CSRF header value
     *
     * @return array Header array for use in fetch/XMLHttpRequest
     */
    public static function getAjaxHeader(): array {
        return ['X-CSRF-Token' => self::getToken()];
    }

    /**
     * Exempt route from CSRF validation
     * For API endpoints or special cases
     * Use sparingly and document reasons
     *
     * @param string $route Route to exempt
     * @return void
     */
    public static function exempt(string $route): void {
        // Store exempted routes in session for validation
        $exempt = sessionGet('csrf_exempt_routes', []);
        $exempt[] = $route;
        sessionSet('csrf_exempt_routes', array_unique($exempt));
    }

    /**
     * Check if current route is exempt from CSRF
     *
     * @return bool True if route is exempt
     */
    public static function isExempt(): bool {
        $currentRoute = trim($_SERVER['REQUEST_URI'] ?? '', '/');
        $exempt = sessionGet('csrf_exempt_routes', []);
        return in_array($currentRoute, $exempt, true);
    }
}
