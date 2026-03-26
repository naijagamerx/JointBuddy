<?php
/**
 * Authentication Middleware
 *
 * Provides consistent authentication checks across all routes
 * Replaces inline authentication checks with centralized middleware
 *
 * @package CannaBuddy
 */

class AuthMiddleware {

    /**
     * Require admin authentication
     * Redirects to login if not authenticated
     *
     * @return void
     */
    public static function requireAdmin(): void {
        ensureSessionStarted();

        if (!self::isAdminLoggedIn()) {
            sessionSet('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
            redirect('/admin/login/');
        }

        // Security: Verify session fingerprint
        if (!Services::adminAuth()->verifySessionFingerprint()) {
            sessionFlash('error', 'Session invalidated for security reasons. Please login again.');
            redirect('/admin/login/');
        }
    }

    /**
     * Require user authentication
     * Redirects to login if not authenticated
     *
     * @return void
     */
    public static function requireUser(): void {
        ensureSessionStarted();

        if (!self::isUserLoggedIn()) {
            sessionSet('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
            redirect('/user/login/');
        }
    }

    /**
     * Check if admin is logged in
     *
     * @return bool True if admin is authenticated
     */
    public static function isAdminLoggedIn(): bool {
        try {
            $adminAuth = Services::adminAuth();
            return $adminAuth && $adminAuth->isLoggedIn();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user is logged in
     *
     * @return bool True if user is authenticated
     */
    public static function isUserLoggedIn(): bool {
        try {
            $userAuth = Services::userAuth();
            return $userAuth && $userAuth->isLoggedIn();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get current admin user
     *
     * @return array|null Admin user data or null if not logged in
     */
    public static function getCurrentAdmin(): ?array {
        if (!self::isAdminLoggedIn()) {
            return null;
        }
        return Services::adminAuth()->getCurrentAdmin();
    }

    /**
     * Get current user
     *
     * @return array|null User data or null if not logged in
     */
    public static function getCurrentUser(): ?array {
        if (!self::isUserLoggedIn()) {
            return null;
        }
        // Build user data from session since UserAuth doesn't have getCurrentUser()
        $firstName = sessionGet('user_first_name', '');
        $lastName = sessionGet('user_last_name', '');
        return [
            'id' => sessionGet('user_id'),
            'email' => sessionGet('user_email'),
            'name' => trim($firstName . ' ' . $lastName) ?: 'User',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => sessionGet('user_role', 'customer'),
        ];
    }

    /**
     * Get current admin ID
     *
     * @return int|null Admin ID or null if not logged in
     */
    public static function getAdminId(): ?int {
        try {
            return Services::adminAuth()->getAdminId();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get current user ID
     *
     * @return int|null User ID or null if not logged in
     */
    public static function getUserId(): ?int {
        if (!self::isUserLoggedIn()) {
            return null;
        }
        return sessionGet('user_id');
    }

    /**
     * Require specific admin role
     *
     * @param string $role Required role (e.g., 'super_admin', 'admin', 'editor')
     * @return void
     */
    public static function requireAdminRole(string $role): void {
        self::requireAdmin();
        $admin = self::getCurrentAdmin();

        if (!$admin || $admin['role'] !== $role) {
            sessionFlash('error', 'Access denied. Insufficient permissions.');
            redirect('/admin/');
        }
    }

    /**
     * Check if current admin has a specific role
     *
     * @param string $role Role to check
     * @return bool True if admin has the role
     */
    public static function adminHasRole(string $role): bool {
        $admin = self::getCurrentAdmin();
        return $admin && $admin['role'] === $role;
    }

    /**
     * Check if current admin has any of the given roles
     *
     * @param array $roles Array of roles to check
     * @return bool True if admin has any of the roles
     */
    public static function adminHasAnyRole(array $roles): bool {
        $admin = self::getCurrentAdmin();
        return $admin && in_array($admin['role'], $roles, true);
    }
}
