# Implementation Plan - CannaBuddy.shop Refactoring

**Based on:** REFACTORING_PLAN.md
**Date:** 2026-01-17
**Status:** READY FOR APPROVAL
**Estimated Duration:** 6-8 weeks

---

## Table of Contents

1. [Phase 1: Critical Security Fixes](#phase-1-critical-security-fixes)
2. [Phase 2: Infrastructure & Architecture](#phase-2-infrastructure--architecture)
3. [Phase 3: Code Organization](#phase-3-code-organization)
4. [Phase 4: Quality & Testing](#phase-4-quality--testing)
5. [Testing Protocol](#testing-protocol)
6. [Deployment Checklist](#deployment-checklist)

---

## Phase 1: Critical Security Fixes

### Task 1.1: Create Session Helper
**File:** `includes/session_helper.php`
**Priority:** CRITICAL
**Estimated Time:** 30 minutes

**Implementation:**

```php
<?php
/**
 * Session Helper - Unified session management
 */

if (!function_exists('ensureSessionStarted')) {
    /**
     * Ensure session is started with secure configuration
     * Only configures once, subsequent calls are safe no-ops
     */
    function ensureSessionStarted(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();
    }
}

if (!function_exists('regenerateSession')) {
    /**
     * Regenerate session ID to prevent session fixation
     * Call after authentication changes
     */
    function regenerateSession(): void {
        ensureSessionStarted();
        session_regenerate_id(true);
    }
}

if (!function_exists('destroySession')) {
    /**
     * Completely destroy the current session
     */
    function destroySession(): void {
        ensureSessionStarted();
        $_SESSION = [];
        session_destroy();
    }
}
```

**Testing:**
- Create test file: `test_delete/test_session_helper.php`
- Verify session starts only once
- Verify session regeneration works
- Verify session destruction works

---

### Task 1.2: Create Service Container
**File:** `includes/services/Services.php`
**Priority:** CRITICAL
**Estimated Time:** 1 hour

**Implementation:**

```php
<?php
/**
 * Service Container - Single source for application services
 * Implements singleton pattern for database and auth instances
 */

class Services {
    private static ?PDO $db = null;
    private static ?AdminAuth $adminAuth = null;
    private static ?UserAuth $userAuth = null;
    private static ?Database $database = null;
    private static bool $initialized = false;

    /**
     * Initialize all services
     * Should be called once in bootstrap
     */
    public static function initialize(): void {
        if (self::$initialized) {
            return;
        }

        try {
            self::$database = new Database();
            self::$db = self::$database->getConnection();
            self::$adminAuth = new AdminAuth(self::$db);
            self::$userAuth = new UserAuth(self::$db);
            self::$initialized = true;
        } catch (Exception $e) {
            error_log("Services initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get database connection
     */
    public static function db(): PDO {
        self::initialize();
        return self::$db;
    }

    /**
     * Get admin authentication instance
     */
    public static function adminAuth(): AdminAuth {
        self::initialize();
        return self::$adminAuth;
    }

    /**
     * Get user authentication instance
     */
    public static function userAuth(): UserAuth {
        self::initialize();
        return self::$userAuth;
    }

    /**
     * Get database instance
     */
    public static function database(): Database {
        self::initialize();
        return self::$database;
    }

    /**
     * Reset all services (for testing only)
     */
    public static function reset(): void {
        self::$db = null;
        self::$adminAuth = null;
        self::$userAuth = null;
        self::$database = null;
        self::$initialized = false;
    }

    /**
     * Check if services are initialized
     */
    public static function isInitialized(): bool {
        return self::$initialized;
    }
}
```

**Testing:**
- Create test file: `test_delete/test_services.php`
- Verify singleton pattern works
- Verify database connection reused
- Verify auth instances are singletons

---

### Task 1.3: Create Authentication Middleware
**File:** `includes/middleware/AuthMiddleware.php`
**Priority:** CRITICAL
**Estimated Time:** 1 hour

**Implementation:**

```php
<?php
/**
 * Authentication Middleware
 * Provides consistent authentication checks across all routes
 */

class AuthMiddleware {

    /**
     * Require admin authentication
     * Redirects to login if not authenticated
     */
    public static function requireAdmin(): void {
        ensureSessionStarted();

        if (!self::isAdminLoggedIn()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/admin/login/');
        }
    }

    /**
     * Require user authentication
     * Redirects to login if not authenticated
     */
    public static function requireUser(): void {
        ensureSessionStarted();

        if (!self::isUserLoggedIn()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/user/login/');
        }
    }

    /**
     * Check if admin is logged in
     */
    public static function isAdminLoggedIn(): bool {
        $adminAuth = Services::adminAuth();
        return $adminAuth && $adminAuth->isLoggedIn();
    }

    /**
     * Check if user is logged in
     */
    public static function isUserLoggedIn(): bool {
        $userAuth = Services::userAuth();
        return $userAuth && $userAuth->isLoggedIn();
    }

    /**
     * Get current admin user
     */
    public static function getCurrentAdmin(): ?array {
        if (!self::isAdminLoggedIn()) {
            return null;
        }
        return Services::adminAuth()->getCurrentAdmin();
    }

    /**
     * Get current user
     */
    public static function getCurrentUser(): ?array {
        if (!self::isUserLoggedIn()) {
            return null;
        }
        return Services::userAuth()->getCurrentUser();
    }

    /**
     * Require specific admin role
     */
    public static function requireAdminRole(string $role): void {
        self::requireAdmin();
        $admin = self::getCurrentAdmin();

        if (!$admin || $admin['role'] !== $role) {
            $_SESSION['error'] = 'Access denied. Insufficient permissions.';
            redirect('/admin/');
        }
    }
}
```

**Testing:**
- Create test file: `test_delete/test_auth_middleware.php`
- Verify admin auth required works
- Verify user auth required works
- Verify role checking works

---

### Task 1.4: Create CSRF Middleware
**File:** `includes/middleware/CsrfMiddleware.php`
**Priority:** CRITICAL
**Estimated Time:** 45 minutes

**Implementation:**

```php
<?php
/**
 * CSRF Middleware
 * Provides CSRF protection for all forms
 */

class CsrfMiddleware {

    /**
     * Validate CSRF token for current request
     * Throws exception if validation fails
     */
    public static function validate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!self::isValid($token)) {
            http_response_code(403);
            $_SESSION['csrf_error'] = 'Security check failed. Please try again.';
            error_log('CSRF validation failed for: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }

        // Regenerate after successful validation
        csrf_regenerate();
    }

    /**
     * Check if CSRF token is valid
     */
    public static function isValid(string $token): bool {
        return verifyCsrfToken($token);
    }

    /**
     * Get CSRF token for forms
     */
    public static function getToken(): string {
        return csrf_token();
    }

    /**
     * Get CSRF HTML field
     */
    public static function getField(): string {
        return csrf_field();
    }

    /**
     * Exempt route from CSRF validation
     * For API endpoints or special cases
     */
    public static function exempt(): void {
        // Token to exempt current request
        // Used sparingly for legitimate API endpoints
    }
}
```

**Testing:**
- Create test file: `test_delete/test_csrf_middleware.php`
- Verify token generation
- Verify token validation
- Verify exemption works

---

### Task 1.5: Fix Hardcoded URLs (40+ files)
**Priority:** CRITICAL
**Estimated Time:** 3-4 hours

**Files to update:**
1. `admin/login/index.php`
2. `admin/products/add.php`
3. `admin/products/edit/index.php`
4. `admin/products/delete/index.php`
5. `admin/products/inventory.php`
6. `admin/products/variations/index.php`
7. `admin/orders/index.php`
8. `admin/orders/view/index.php`
9. `admin/orders/create/index.php`
10. `admin/orders/create/process.php`
11. `admin/users/index.php`
12. `admin/users/edit/index.php`
13. `admin/users/view.php`
14. `admin/categories.php`
15. `admin/coupons.php`
16. `admin/vouchers.php`
17. `admin/settings/index.php`
18. `admin/settings/appearance.php`
19. `admin/settings/currency.php`
20. `admin/settings/email.php`
21. `admin/settings/notifications.php`
22. `admin/slider/index.php`
23. `admin/hero-images.php`
24. `admin/returns/index.php`
25. `admin/returns/settings.php`
26. `admin/returns/view.php`
27. `admin/newsletter/index.php`
28. `admin/messages/index.php`
29. `admin/seo/index.php`
30. `admin/tools/index.php`
31. `admin/payment-methods/index.php`
32. `admin/payment-methods/add/index.php`
33. `admin/payment-methods/edit/index.php`
34. `admin/delivery-methods/index.php`
35. `admin/qr-codes/index.php`
36. `admin/qr-codes/generate.php`
37. `admin/qr-codes/scans.php`
38. `user/login/index.php`
39. `user/dashboard/index.php`
40. And more...

**Pattern to find and replace:**

Find:
```php
redirect('/admin/');
redirect('/admin/login/');
redirect('/user/');
header("Location: /admin/");
header('Location: /user/login/');
```

Replace with:
```php
redirect('/admin/');        // Uses url() helper internally
adminUrl('login/');         // For admin URLs
userUrl('dashboard/');      // For user URLs
redirect('/admin/login/');  // Uses redirect() function
```

**Special cases to handle:**

`admin/orders/index.php` line 89:
```php
// BEFORE:
$cleanPath = ltrim(str_replace('/CannaBuddy.shop/', '', $imagePath), '/');

// AFTER:
$cleanPath = ltrim(str_replace(rurl('/'), '', $imagePath), '/');
```

`admin/orders/view/index.php` line 307:
```php
// BEFORE:
$imagePath = ltrim(str_replace('/CannaBuddy.shop/', '', $firstImage), '/');

// AFTER:
$imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
```

`admin/returns/view.php` line 186:
```php
// BEFORE:
$imagePath = ltrim(str_replace('/CannaBuddy.shop/', '', $firstImage), '/');

// AFTER:
$imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
```

`admin/users/view.php` line 376:
```php
// BEFORE:
$imagePath = ltrim(str_replace('/CannaBuddy.shop/', '', $firstImage), '/');

// AFTER:
$imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
```

**Automation script:**
Create `scripts/fix_urls.php` to automate the find-replace:

```php
<?php
/**
 * Fix Hardcoded URLs Script
 * Run: php scripts/fix_urls.php
 */

$files = [
    'admin/login/index.php',
    'admin/products/add.php',
    // ... full list of files
];

$replacements = [
    '/CannaBuddy.shop/' => 'rurl("/")',
    "redirect('/admin/')" => "redirect('/admin/')", // Already uses helper
    "header('Location: /admin/" => "header('Location: " . adminUrl(",
    // ... more patterns
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "SKIP: $file (not found)\n";
        continue;
    }

    $content = file_get_contents($file);
    $original = $content;

    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "FIXED: $file\n";
    } else {
        echo "OK: $file (no changes needed)\n";
    }
}
```

**Testing:**
- After fixing, run: `php test_delete/test_url_helpers.php`
- Manually test admin navigation
- Test redirects on forms
- Verify image paths work

---

### Task 1.6: Standardize Session Handling (35+ files)
**Priority:** CRITICAL
**Estimated Time:** 2-3 hours

**Files to update:**

**Pattern A - Replace manual session_start():**
Files: `admin/analytics.php`, `admin/categories.php`, `admin/coupons.php`, etc.

```php
// BEFORE:
session_start();

// AFTER:
require_once __DIR__ . '/../../includes/session_helper.php';
ensureSessionStarted();
```

**Pattern B - Replace session_status check:**
Files: `admin/newsletter/index.php`, `admin/products/reviews.php`, `admin/seo/index.php`

```php
// BEFORE:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// AFTER:
require_once __DIR__ . '/../../includes/session_helper.php';
ensureSessionStarted();
```

**Pattern C - Remove duplicate session starts:**
Files: `admin/tools/index.php`

```php
// BEFORE (line 4):
session_start();

// BEFORE (line 43, 53):
session_start();  // DUPLICATE!

// AFTER:
require_once __DIR__ . '/../../includes/session_helper.php';
ensureSessionStarted();  // Only once at top
```

**User files to update:**
`user/login/index.php`, `user/dashboard/index.php`, `user/profile/index.php`, etc.

**Index.php update:**

```php
// BEFORE (lines 8-16):
session_set_cookie_params([...]);
session_start();

// AFTER:
require_once __DIR__ . '/includes/session_helper.php';
ensureSessionStarted();
```

**Automation:**

Create `scripts/fix_sessions.php`:

```php
<?php
$files = glob_recursive('{admin,user}/**/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Skip if already using helper
    if (strpos($content, 'ensureSessionStarted') !== false) {
        continue;
    }

    // Remove old patterns
    $content = preg_replace('/if\s*\(\s*session_status\(\)\s*===\s*PHP_SESSION_NONE\s*\)\s*\{\s*session_start\(\)\s*\;\s*\}/s', '', $content);
    $content = str_replace('session_start();', '', $content);

    // Add helper at top after <?php
    $helper = "require_once __DIR__ . '/../../includes/session_helper.php';\nensureSessionStarted();\n\n";
    $content = preg_replace('/(<\?php\s*\n)/', '$1' . $helper, $content, 1);

    file_put_contents($file, $content);
    echo "FIXED: $file\n";
}
```

---

### Task 1.7: Add CSRF Protection to All Forms (15+ files)
**Priority:** CRITICAL
**Estimated Time:** 3-4 hours

**Files needing CSRF protection:**

1. `admin/categories.php` - POST handler lines 43-116
2. `admin/products/add.php` - POST handler lines 76-289
3. `admin/products/edit/index.php` - POST handler
4. `admin/products/delete/index.php` - POST handler
5. `admin/products/inventory.php` - POST handler
6. `admin/coupons.php` - POST handler
7. `admin/vouchers.php` - POST handler
8. `admin/settings/appearance.php` - POST handler lines 66-93
9. `admin/settings/currency.php` - POST handler
10. `admin/settings/email.php` - POST handler
11. `admin/slider/index.php` - POST handler
12. `admin/hero-images.php` - POST handler
13. `admin/returns/settings.php` - POST handler
14. `admin/newsletter/index.php` - POST handler
15. `admin/payment-methods/add/index.php` - POST handler
16. `admin/payment-methods/edit/index.php` - POST handler

**Pattern to implement:**

```php
// Add at top of file after includes:
require_once __DIR__ . '/../../includes/middleware/CsrfMiddleware.php';

// Add before POST processing:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    // ... rest of POST handler
}
```

**Example for admin/categories.php:**

```php
<?php
// Existing includes
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/admin_layout.php';

// NEW: Add CSRF middleware
require_once __DIR__ . '/../includes/middleware/CsrfMiddleware.php';

// ... existing code ...

// POST handler - ADD CSRF VALIDATION
if ($_POST && isset($_POST['action'])) {
    // NEW: Validate CSRF token first
    CsrfMiddleware::validate();

    $action = $_POST['action'];
    // ... rest of POST handler
}
```

**Also ensure forms have CSRF field:**

```php
// In form HTML, add:
<form method="POST">
    <?php echo csrf_field(); ?>
    <!-- rest of form -->
</form>
```

**Testing:**
- Submit form without token → should fail
- Submit form with valid token → should succeed
- Submit form with expired token → should fail

---

### Task 1.8: Replace Auth Checks with Middleware (25+ files)
**Priority:** CRITICAL
**Estimated Time:** 2-3 hours

**Files to update:**

Pattern 1 - Inline auth check:
```php
// BEFORE:
if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

// AFTER:
require_once __DIR__ . '/../../includes/middleware/AuthMiddleware.php';
AuthMiddleware::requireAdmin();
```

Pattern 2 - Separate auth check file:
```php
// BEFORE:
require_once __DIR__ . '/../../includes/admin_auth_check.php';

// AFTER:
require_once __DIR__ . '/../../includes/middleware/AuthMiddleware.php';
AuthMiddleware::requireAdmin();
```

**Files to update:**
1. `admin/analytics.php`
2. `admin/categories.php`
3. `admin/coupons.php`
4. `admin/delivery-methods/index.php`
5. `admin/hero-images.php`
6. `admin/index.php`
7. `admin/messages/index.php`
8. `admin/newsletter/index.php`
9. `admin/orders/index.php`
10. `admin/orders/view/index.php`
11. `admin/payment-methods/index.php`
12. `admin/payment-methods/add/index.php`
13. `admin/payment-methods/edit/index.php`
14. `admin/products/index.php`
15. `admin/products/inquiries.php`
16. `admin/products/reviews.php`
17. `admin/products/upload_image.php`
16. `admin/qr-codes/index.php`
17. `admin/qr-codes/generate.php`
18. `admin/qr-codes/scans.php`
19. `admin/returns/index.php`
20. `admin/returns/settings.php`
21. `admin/returns/view.php`
22. `admin/seo/index.php`
23. `admin/settings/appearance.php`
24. `admin/settings/currency.php`
25. `admin/settings/email.php`
26. `admin/settings/index.php`
27. `admin/settings/notifications.php`
28. `admin/slider/index.php`
29. `admin/tools/index.php`
30. `admin/users/edit/index.php`
31. `admin/users/index.php`
32. `admin/users/view.php`
33. `admin/vouchers.php`

---

## Phase 2: Infrastructure & Architecture

### Task 2.1: Create Bootstrap File
**File:** `includes/bootstrap.php`
**Priority:** HIGH
**Estimated Time:** 1 hour

**Implementation:**

```php
<?php
/**
 * Bootstrap File - Application initialization
 * Include this at the top of every file instead of individual includes
 */

// Define paths
define('APP_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', APP_ROOT . '/includes');
define('ADMIN_PATH', APP_ROOT . '/admin');
define('USER_PATH', APP_ROOT . '/user');
define('ASSETS_PATH', APP_ROOT . '/assets');

// Load core files
require_once INCLUDES_PATH . '/session_helper.php';
require_once INCLUDES_PATH . '/url_helper.php';
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/services/Services.php';
require_once INCLUDES_PATH . '/middleware/AuthMiddleware.php';
require_once INCLUDES_PATH . '/middleware/CsrfMiddleware.php';

// Initialize session
ensureSessionStarted();

// Initialize services
try {
    Services::initialize();
} catch (Exception $e) {
    error_log("Bootstrap failed: " . $e->getMessage());
    // Continue in degraded mode if possible
}

// Set error reporting based on environment
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Load additional helpers
require_once INCLUDES_PATH . '/error_handler.php';
require_once INCLUDES_PATH . '/admin_layout.php';

// Register autoload for custom classes
spl_autoload_register(function ($class) {
    $paths = [
        INCLUDES_PATH . '/commerce/',
        INCLUDES_PATH . '/seo/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
```

**Update all files to use bootstrap:**

```php
// BEFORE in admin files:
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/url_helper.php';
// ... more includes

// AFTER:
require_once __DIR__ . '/../includes/bootstrap.php';
```

---

### Task 2.2: Create Input Validation Layer
**File:** `includes/validation/Validator.php`
**Priority:** HIGH
**Estimated Time:** 2 hours

**Implementation:**

```php
<?php
/**
 * Input Validator
 * Provides consistent input validation and sanitization
 */

class ValidationException extends Exception {
    public function __construct(string $message, private array $errors = []) {
        parent::__construct($message);
    }

    public function getErrors(): array {
        return $this->errors;
    }
}

class Validator {

    /**
     * Validate and sanitize a string
     */
    public static function string($value, int $maxLen = 255, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Value is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim($value);

        if (strlen($value) > $maxLen) {
            throw new ValidationException("Value must not exceed {$maxLen} characters");
        }

        // Sanitize for HTML output
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate an email address
     */
    public static function email(string $value, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Email is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim($value);

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address');
        }

        return $value;
    }

    /**
     * Validate an integer
     */
    public static function integer($value, int $min = 0, ?int $max = null, bool $required = true): int {
        if ($required && $value === '' && $value !== '0') {
            throw new ValidationException('Value is required');
        }

        if (!$required && $value === '') {
            return 0;
        }

        if (!is_numeric($value)) {
            throw new ValidationException('Value must be a number');
        }

        $value = (int) $value;

        if ($value < $min) {
            throw new ValidationException("Value must be at least {$min}");
        }

        if ($max !== null && $value > $max) {
            throw new ValidationException("Value must not exceed {$max}");
        }

        return $value;
    }

    /**
     * Validate a price/decimal
     */
    public static function price($value, bool $required = true): float {
        if ($required && $value === '' && $value !== '0') {
            throw new ValidationException('Price is required');
        }

        if (!$required && $value === '') {
            return 0.0;
        }

        if (!is_numeric($value)) {
            throw new ValidationException('Price must be a number');
        }

        $value = (float) $value;

        if ($value < 0) {
            throw new ValidationException('Price cannot be negative');
        }

        return round($value, 2);
    }

    /**
     * Validate a URL
     */
    public static function url(string $value, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('URL is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim($value);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid URL');
        }

        return $value;
    }

    /**
     * Validate a slug (URL-friendly string)
     */
    public static function slug(string $value, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Slug is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim($value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value);
        $value = trim($value, '-');

        if (empty($value)) {
            throw new ValidationException('Slug cannot be empty');
        }

        return $value;
    }

    /**
     * Validate a phone number
     */
    public static function phone(string $value, bool $required = false): ?string {
        if (!$required && empty($value)) {
            return null;
        }

        $value = trim($value);
        // Remove all non-numeric characters
        $value = preg_replace('/[^0-9]/', '', $value);

        // Check length (South African numbers: 10 digits)
        if (strlen($value) < 10 || strlen($value) > 15) {
            throw new ValidationException('Invalid phone number');
        }

        return $value;
    }

    /**
     * Validate boolean
     */
    public static function boolean($value, bool $default = false): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) (int) $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', '1', 'yes', 'on'], true);
        }

        return $default;
    }

    /**
     * Validate an array of values
     */
    public static function array($value, bool $required = false): array {
        if (!$required && empty($value)) {
            return [];
        }

        if (!is_array($value)) {
            throw new ValidationException('Value must be an array');
        }

        return $value;
    }

    /**
     * Validate file upload
     */
    public static function file(array $file, array $allowedTypes = [], int $maxSize = 2097152): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('No file uploaded');
        }

        // Check size
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1048576, 2);
            throw new ValidationException("File size exceeds {$maxMB}MB limit");
        }

        // Check type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedTypes, true)) {
                throw new ValidationException('Invalid file type');
            }
        }

        return $file;
    }

    /**
     * Sanitize HTML content (allow certain tags)
     */
    public static function html(string $value, array $allowedTags = []): string {
        if (empty($value)) {
            return '';
        }

        // If no allowed tags, escape everything
        if (empty($allowedTags)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        // Allow specific tags (basic implementation)
        $allowed = implode('', $allowedTags);
        return strip_tags($value, $allowed);
    }

    /**
     * Validate date
     */
    public static function date(string $value, string $format = 'Y-m-d', bool $required = true): ?string {
        if (!$required && empty($value)) {
            return null;
        }

        $date = DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            throw new ValidationException("Invalid date format, expected {$format}");
        }

        return $value;
    }

    /**
     * Validate enum value
     */
    public static function enum($value, array $allowedValues, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Value is required');
        }

        if (!$required && empty($value)) {
            return $allowedValues[0] ?? '';
        }

        if (!in_array($value, $allowedValues, true)) {
            throw new ValidationException('Invalid value');
        }

        return $value;
    }
}
```

**Usage examples:**

```php
// In POST handlers:
try {
    $name = Validator::string($_POST['name'], 100);
    $email = Validator::email($_POST['email']);
    $price = Validator::price($_POST['price']);
    $stock = Validator::integer($_POST['stock'], 0);
    $slug = Validator::slug($_POST['slug']);
    $description = Validator::html($_POST['description'], ['<p>', '<br>', '<strong>', '<em>']);

    // Use validated values
    $stmt = $db->prepare("INSERT INTO products (name, email, price, stock, slug, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $price, $stock, $slug, $description]);

} catch (ValidationException $e) {
    $_SESSION['error'] = $e->getMessage();
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}
```

---

### Task 2.3: Create Error Handler
**File:** `includes/error_handler.php` (already exists, update)
**Priority:** HIGH
**Estimated Time:** 1 hour

**Update with consistent patterns:**

```php
<?php
/**
 * Application Error Handler
 * Centralized error and exception handling
 */

class AppError {

    /**
     * Handle database errors
     */
    public static function handleDatabaseError(Exception $e, string $context = ''): string {
        error_log("Database error in {$context}: " . $e->getMessage());

        if (getenv('APP_ENV') === 'production') {
            return 'A database error occurred. Please try again.';
        }

        return "Database error: {$e->getMessage()}";
    }

    /**
     * Handle validation errors
     */
    public static function handleValidationError(ValidationException $e): string {
        return $e->getMessage();
    }

    /**
     * Handle authentication errors
     */
    public static function handleAuthError(string $message = 'Authentication failed'): string {
        return $message;
    }

    /**
     * Handle file upload errors
     */
    public static function handleUploadError(int $errorCode): string {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds max file size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Log error with context
     */
    public static function log(string $message, array $context = []): void {
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        error_log($message . $contextStr);
    }

    /**
     * Set flash error message
     */
    public static function flashError(string $message): void {
        $_SESSION['error'] = $message;
    }

    /**
     * Set flash success message
     */
    public static function flashSuccess(string $message): void {
        $_SESSION['success'] = $message;
    }

    /**
     * Set flash info message
     */
    public static function flashInfo(string $message): void {
        $_SESSION['info'] = $message;
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash(string $type): ?string {
        $key = "{$type}_message";
        $message = $_SESSION[$key] ?? $_SESSION[$type] ?? null;
        unset($_SESSION[$key], $_SESSION[$type]);
        return $message;
    }
}

// Set error and exception handlers
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];

    $type = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "[{$type}] {$errstr} in {$errfile}:{$errline}";

    error_log($message);

    if (ini_get('display_errors')) {
        echo "<pre>{$message}</pre>";
    }

    return true;
});

set_exception_handler(function ($exception) {
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());

    if (ini_get('display_errors')) {
        echo "<h1>Internal Server Error</h1>";
        echo "<pre>{$exception->getMessage()}</pre>";
    } else {
        http_response_code(500);
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === 0) {
            echo json_encode(['error' => 'Internal server error']);
        } else {
            echo "<h1>Internal Server Error</h1>";
            echo "<p>An error occurred. Please try again later.</p>";
        }
    }
});
```

---

## Phase 3: Code Organization

### Task 3.1: Split index.php into Controllers
**Priority:** HIGH
**Estimated Time:** 4-5 hours

**Current index.php structure:**
- Line 1-50: Includes and initialization
- Line 51-100: Session management
- Line 101-200: POST handlers
- Line 201-500: Homepage HTML (300+ lines!)
- Line 501-1757: Various route handlers

**New structure:**

```
includes/
├── bootstrap.php (from Task 2.1)
├── controllers/
│   ├── HomeController.php
│   ├── AdminController.php
│   ├── UserController.php
│   ├── ShopController.php
│   ├── ProductController.php
│   ├── CartController.php
│   └── CheckoutController.php
```

**HomeController.php:**

```php
<?php
/**
 * Home Controller
 * Handles homepage rendering and related functionality
 */

class HomeController {

    private $db;
    private $currencyService;

    public function __construct() {
        $this->db = Services::db();
        $this->currencyService = Services::currencyService();
    }

    /**
     * Render homepage
     */
    public function index(): string {
        // Get featured products
        $featuredProducts = $this->getFeaturedProducts();

        // Get sale products
        $saleProducts = $this->getSaleProducts();

        // Get hero slides
        $heroSlides = $this->getHeroSlides();

        // Render view
        return $this->renderView([
            'featuredProducts' => $featuredProducts,
            'saleProducts' => $saleProducts,
            'heroSlides' => $heroSlides,
        ]);
    }

    /**
     * Get featured products
     */
    private function getFeaturedProducts(): array {
        $stmt = $this->db->query("
            SELECT * FROM products
            WHERE featured = 1 AND status = 'active'
            ORDER BY sort_order ASC, created_at DESC
            LIMIT 6
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get sale products
     */
    private function getSaleProducts(): array {
        $stmt = $this->db->query("
            SELECT * FROM products
            WHERE on_sale = 1 AND status = 'active'
            ORDER BY sale_price ASC
            LIMIT 6
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hero slides
     */
    private function getHeroSlides(): array {
        $stmt = $this->db->query("
            SELECT * FROM homepage_slider
            WHERE is_active = 1
            ORDER BY sort_order ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Render homepage view
     */
    private function renderView(array $data): string {
        extract($data);

        ob_start();
        require APP_ROOT . '/views/home/index.php';
        return ob_get_clean();
    }
}
```

**AdminController.php:**

```php
<?php
/**
 * Admin Controller
 * Handles admin panel functionality
 */

class AdminController {

    private $db;
    private $adminAuth;

    public function __construct() {
        AuthMiddleware::requireAdmin();
        $this->db = Services::db();
        $this->adminAuth = Services::adminAuth();
    }

    /**
     * Admin dashboard
     */
    public function dashboard(): string {
        // Get stats
        $stats = $this->getDashboardStats();

        // Get recent orders
        $recentOrders = $this->getRecentOrders();

        return $this->renderView([
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ], 'dashboard');
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array {
        $stats = [];

        // Total orders
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders");
        $stats['total_orders'] = $stmt->fetch()['count'];

        // Total revenue
        $stmt = $this->db->query("SELECT SUM(total_amount) as sum FROM orders WHERE payment_status = 'paid'");
        $stats['total_revenue'] = $stmt->fetch()['sum'] ?? 0;

        // Total products
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM products");
        $stats['total_products'] = $stmt->fetch()['count'];

        // Total users
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];

        return $stats;
    }

    /**
     * Get recent orders
     */
    private function getRecentOrders(): array {
        $stmt = $this->db->query("
            SELECT * FROM orders
            ORDER BY created_at DESC
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Render admin view
     */
    private function renderView(array $data, string $activePage): string {
        global $adminAuth;
        $adminAuth = $this->adminAuth;
        extract($data);

        ob_start();
        require APP_ROOT . '/includes/admin_layout.php';
        return ob_get_clean();
    }
}
```

**Updated index.php (after split):**

```php
<?php
/**
 * Main Entry Point
 * Routes requests to appropriate controllers
 */

// Include bootstrap
require_once __DIR__ . '/includes/bootstrap.php';

// Get current route
$route = $_GET['route'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$route = trim(parse_url($route, PHP_URL_PATH), '/');

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    // POST handlers delegated to controllers
}

// Route to controller
try {
    $response = match (true) {
        // Home routes
        $route === '' || $route === 'home' => (new HomeController())->index(),

        // Admin routes
        str_starts_with($route, 'admin') => routeAdmin($route),

        // User routes
        str_starts_with($route, 'user') => routeUser($route),

        // Shop routes
        str_starts_with($route, 'shop') => (new ShopController())->index(),

        // Product routes
        str_starts_with($route, 'product') => routeProduct($route),

        // Cart routes
        str_starts_with($route, 'cart') => (new CartController())->index(),

        // Checkout routes
        str_starts_with($route, 'checkout') => (new CheckoutController())->index(),

        // Default: 404
        default => render404(),
    };

    echo $response;

} catch (Exception $e) {
    error_log("Route error: " . $e->getMessage());
    render500();
}

/**
 * Route admin requests
 */
function routeAdmin(string $route): string {
    AuthMiddleware::requireAdmin();

    // Extract sub-route
    $parts = explode('/', $route);
    $action = $parts[1] ?? 'dashboard';
    $controller = new AdminController();

    return match ($action) {
        'dashboard' => $controller->dashboard(),
        'products' => $controller->products(),
        'orders' => $controller->orders(),
        'users' => $controller->users(),
        default => render404(),
    };
}

/**
 * Route user requests
 */
function routeUser(string $route): string {
    $parts = explode('/', $route);
    $action = $parts[1] ?? 'dashboard';
    $controller = new UserController();

    return match ($action) {
        'dashboard' => $controller->dashboard(),
        'orders' => $controller->orders(),
        'profile' => $controller->profile(),
        'login', 'register' => handleAuth($action),
        default => render404(),
    };
}

/**
 * Render 404 page
 */
function render404(): string {
    http_response_code(404);
    ob_start();
    require APP_ROOT . '/views/errors/404.php';
    return ob_get_clean();
}

/**
 * Render 500 page
 */
function render500(): void {
    http_response_code(500);
    require APP_ROOT . '/views/errors/500.php';
}
```

---

### Task 3.2: Create Configuration System
**File:** `config/app.php`
**Priority:** MEDIUM
**Estimated Time:** 1 hour

**Implementation:**

```php
<?php
/**
 * Application Configuration
 * Environment-based configuration loading
 */

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'CannaBuddy',
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'url' => getenv('APP_URL') ?: null,
        'timezone' => 'Africa/Johannesburg',
    ],

    'database' => [
        'host' => getenv('CB_DB_HOST') ?: 'localhost',
        'name' => getenv('CB_DB_NAME') ?: 'cannabuddy',
        'user' => getenv('CB_DB_USER') ?: 'root',
        'pass' => getenv('CB_DB_PASS') ?: 'root',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],

    'session' => [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Auto-detected
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    'security' => [
        'csrf_token_length' => 32,
        'csrf_token_expiry' => 3600, // 1 hour
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 1800, // 30 minutes
    ],

    'upload' => [
        'max_size' => 2 * 1024 * 1024, // 2MB
        'allowed_image_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ],
        'path' => APP_ROOT . '/assets/uploads',
    ],

    'pagination' => [
        'admin_per_page' => 20,
        'user_per_page' => 12,
    ],

    'email' => [
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@cannabuddy.co.za',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'CannaBuddy',
        'smtp_host' => getenv('MAIL_HOST') ?: 'localhost',
        'smtp_port' => (int) (getenv('MAIL_PORT') ?: 587),
        'smtp_username' => getenv('MAIL_USERNAME') ?: null,
        'smtp_password' => getenv('MAIL_PASSWORD') ?: null,
        'smtp_encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    ],

    'currency' => [
        'default' => 'ZAR',
        'position' => 'before',
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ' ',
    ],

    'features' => [
        'enable_registration' => true,
        'enable_guest_checkout' => true,
        'enable_reviews' => true,
        'enable_wishlist' => true,
        'enable_newsletter' => true,
        'enable_returns' => true,
        'enable_vouchers' => true,
    ],
];
```

**Create config helper:**

`includes/config.php`:

```php
<?php
/**
 * Configuration Helper
 */

class Config {
    private static array $config = [];

    public static function load(): void {
        self::$config = require APP_ROOT . '/config/app.php';
    }

    public static function get(string $key, $default = null) {
        if (empty(self::$config)) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function has(string $key): bool {
        return self::get($key) !== null;
    }

    public static function is(string $key, bool $default = false): bool {
        return filter_var(self::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
```

---

## Phase 4: Quality & Testing

### Task 4.1: Add Type Hints to All Functions
**Priority:** MEDIUM
**Estimated Time:** 3-4 hours

**Pattern to apply:**

```php
// BEFORE:
public function login($username, $password, $ip_address = null) {
    // ...
}

// AFTER:
public function login(string $username, string $password, ?string $ip_address = null): array {
    // ...
}
```

**Files to update:**
1. `includes/database.php` - All class methods
2. `includes/services/Services.php` - All methods
3. `includes/middleware/AuthMiddleware.php` - All methods
4. `includes/middleware/CsrfMiddleware.php` - All methods
5. `includes/validation/Validator.php` - All methods
6. All controller methods

---

### Task 4.2: Write PHPUnit Tests
**Priority:** HIGH
**Estimated Time:** 6-8 hours

**Test files to create:**

1. `tests/SessionHelperTest.php`
2. `tests/ServicesTest.php`
3. `tests/AuthMiddlewareTest.php`
4. `tests/CsrfMiddlewareTest.php`
5. `tests/ValidatorTest.php`
6. `tests/DatabaseTest.php`
7. `tests/AdminAuthTest.php`
8. `tests/UserAuthTest.php`
9. `tests/UrlHelperTest.php`
10. `tests/ConfigTest.php`

**Example test file:**

`tests/ValidatorTest.php`:

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase {

    public function testStringValidation(): void {
        // Valid string
        $result = Validator::string('Test Product', 100);
        $this->assertEquals('Test Product', $result);

        // Empty string when not required
        $result = Validator::string('', 100, required: false);
        $this->assertEquals('', $result);

        // String too long
        $this->expectException(ValidationException::class);
        Validator::string(str_repeat('a', 300), 100);
    }

    public function testEmailValidation(): void {
        // Valid email
        $result = Validator::email('test@example.com');
        $this->assertEquals('test@example.com', $result);

        // Invalid email
        $this->expectException(ValidationException::class);
        Validator::email('not-an-email');
    }

    public function testIntegerValidation(): void {
        // Valid integer
        $result = Validator::integer('42');
        $this->assertEquals(42, $result);

        // Below minimum
        $this->expectException(ValidationException::class);
        Validator::integer('-5', 0);

        // Non-numeric
        $this->expectException(ValidationException::class);
        Validator::integer('abc');
    }

    public function testPriceValidation(): void {
        // Valid price
        $result = Validator::price('99.99');
        $this->assertEquals(99.99, $result);

        // Negative price
        $this->expectException(ValidationException::class);
        Validator::price('-10.00');

        // Non-numeric
        $this->expectException(ValidationException::class);
        Validator::price('abc');
    }

    public function testSlugValidation(): void {
        // Valid slug
        $result = Validator::slug('Test Product Name');
        $this->assertEquals('test-product-name', $result);

        // Empty after sanitizing
        $this->expectException(ValidationException::class);
        Validator::slug('!!!');
    }

    public function testBooleanValidation(): void {
        // True values
        $this->assertTrue(Validator::boolean('1'));
        $this->assertTrue(Validator::boolean('true'));
        $this->assertTrue(Validator::boolean('yes'));
        $this->assertTrue(Validator::boolean(1));

        // False values
        $this->assertFalse(Validator::boolean('0'));
        $this->assertFalse(Validator::boolean('false'));
        $this->assertFalse(Validator::boolean(0));

        // Default
        $this->assertFalse(Validator::boolean('invalid', default: false));
    }
}
```

---

### Task 4.3: PSR-12 Code Style Compliance
**Priority:** MEDIUM
**Estimated Time:** 2-3 hours

**Install PHP CS Fixer:**

```bash
composer require --dev friendsofphp/php-cs-fixer
```

**Create `.php-cs-fixer.php`:**

```php
<?php
declare(strict_types=1);

$config = (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'try', 'throw', 'if', 'switch', 'for', 'foreach', 'while', 'do'],
        ],
        'cast_spaces' => true,
        'class_definition' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => true,
        'declare_strict_types' => true,
        'function_typehint_space' => true,
        'include' => true,
        'lowercase_cast' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'method_argument_space' => true,
        'native_function_casing' => true,
        'native_function_type_declaration_casing' => true,
        'new_with_braces' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_align' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'return_type_declaration' => true,
        'short_scalar_cast' => true,
        'single_blank_line_before_statement' => true,
        'single_class_element_per_statement' => true,
        'single_line_comment_style' => true,
        'single_quote' => true,
        'space_after_semicolon' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude('vendor')
            ->exclude('test_delete')
            ->name('*.php')
    )
;

return $config;
```

**Run fixes:**

```bash
# Dry run to see changes
vendor/bin/php-cs-fixer fix --dry-run --diff

# Apply fixes
vendor/bin/php-cs-fixer fix
```

---

## Testing Protocol

### Before Each Phase
1. Create feature branch: `phase{N}-{description}`
2. Run existing tests to establish baseline
3. Create backup of current state

### During Implementation
1. Run tests after each major change
2. Commit frequently with descriptive messages
3. Update documentation as changes are made

### After Each Phase
1. Run full test suite
2. Manual testing checklist
3. Code review
4. Merge to main branch

### Manual Testing Checklist

**Phase 1 Testing:**
- [ ] Admin login works
- [ ] User login works
- [ ] All forms submit with CSRF token
- [ ] All redirects work correctly
- [ ] Session persists across pages
- [ ] Logout works correctly

**Phase 2 Testing:**
- [ ] Service container provides instances
- [ ] Middleware blocks unauthorized access
- [ ] Error messages display correctly
- [ ] Validation rejects invalid input

**Phase 3 Testing:**
- [ ] Homepage loads correctly
- [ ] Admin dashboard loads
- [ ] All admin pages accessible
- [ ] User dashboard loads
- [ ] All user pages accessible

**Phase 4 Testing:**
- [ ] All PHPUnit tests pass
- [ ] Code style checks pass
- [ ] No PHP warnings/errors
- [ ] Performance acceptable

---

## Deployment Checklist

### Pre-Deployment
- [ ] All phases completed
- [ ] All tests passing
- [ ] Code review complete
- [ ] Documentation updated
- [ ] Backup created

### Deployment Steps
1. Create database backup
2. Deploy to staging environment
3. Run smoke tests on staging
4. Monitor for 24 hours
5. Deploy to production
6. Verify production functionality
7. Monitor for 48 hours

### Post-Deployment
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Verify all integrations working
- [ ] Update deployment documentation

---

## Summary

This implementation plan provides a detailed roadmap for refactoring the CannaBuddy.shop codebase over 6-8 weeks. The plan addresses all 87 identified issues with specific tasks, code examples, and testing protocols.

**Key Deliverables:**
- 4 major phases with 20+ specific tasks
- New architecture with service container and middleware
- Comprehensive testing suite
- Production-ready codebase

**Estimated Effort:**
- Development: 6-8 weeks
- Testing: 2 weeks (ongoing)
- Code review: 1 week
- Documentation: 1 week

**Ready for Implementation:** Awaiting approval

---

**Document Version:** 1.0
**Last Updated:** 2026-01-17
**Author:** Claude Code Agent
**Status:** AWAITING APPROVAL
