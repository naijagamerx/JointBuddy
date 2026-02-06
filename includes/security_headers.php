<?php
/**
 * Security Headers for CannaBuddy
 * Implements defense-in-depth security measures
 *
 * Call this function at the top of any page that needs security headers
 */

if (!function_exists('sendLoginSecurityHeaders')) {
    /**
     * Send HTTP security headers specifically for login pages
     * More restrictive than general headers to protect authentication endpoints
     *
     * @return void
     */
    function sendLoginSecurityHeaders() {
        // Prevent clickjacking attacks - only allow same-origin framing
        header('X-Frame-Options: SAMEORIGIN', true);

        // Enable XSS protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block', true);

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff', true);

        // Referrer policy - control referrer information leakage
        header('Referrer-Policy: strict-origin-when-cross-origin', true);

        // Content Security Policy - strict but functional for login pages
        // Allows Tailwind CSS and Font Awesome CDNs (currently used)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; form-action 'self';", true);

        // Permissions policy - disable browser features not needed for login
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()', true);

        // Login pages should never be cached
        header('Cache-Control: no-store, no-cache, must-revalidate, private', true);
        header('Pragma: no-cache', true);
        header('Expires: 0', true);
    }
}

if (!function_exists('sendSecurityHeaders')) {
    /**
     * Send HTTP security headers for general pages
     * Less restrictive than login-specific headers
     *
     * @param array $options Optional configuration overrides
     * @return void
     */
    function sendSecurityHeaders($options = []) {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN', true);

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block', true);

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff', true);

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin', true);

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self';", true);

        // Permissions policy
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=()', true);
    }
}
?>
