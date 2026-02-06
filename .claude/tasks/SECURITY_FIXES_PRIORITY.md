# Security Fixes Priority Plan
## CannaBuddy.shop PHP E-Commerce System

**Date Created**: 2026-01-14
**Agent**: php-code-reviewer (a2bd496)
**Status**: IN PROGRESS - Phase 1 Partially Complete
**Last Updated**: 2026-01-14

---

## Progress Tracking

### Completed (Phase 1 - Critical)
- ✅ **Task 1.1**: Created `csrf_field()` helper in `includes/url_helper.php`
- ✅ **Task 1.2**: Updated `index.php` - Applied CSRF to ALL POST requests
- ✅ **Task 1.3**: Added CSRF token to admin login form (`admin/login/index.php`)
- ✅ **Task 3.1**: Created `AppError` class in `includes/error_handler.php`
- ✅ **Task 6**: Added `session_regenerate_id()` to both admin and user login methods

### Files Modified
1. `includes/url_helper.php` - Added CSRF helper functions
2. `includes/database.php` - Added session regeneration to login methods
3. `includes/error_handler.php` - Added AppError class
4. `index.php` - Applied CSRF to all POST requests
5. `admin/login/index.php` - Added CSRF protection

### Remaining (Phase 1)
- ⏳ **Task 1.4**: Update all admin forms (products, orders, users, sliders) with CSRF
- ⏳ **Task 2**: Fix SQL injection risk in database.php:64
- ⏳ **Task 3.2**: Update admin files to hide exception messages

---

## Overview

This plan addresses **3 CRITICAL**, **8 HIGH**, and **12 MEDIUM** severity security vulnerabilities identified by the php-code-reviewer agent.

### Summary Table

| Priority | Issues | Est. Files |
|----------|--------|------------|
| P0 - Critical | 3 | 3 files |
| P1 - High | 8 | 6 files |
| P2 - Medium | 12+ | 10+ files |

---

## P0 - CRITICAL (Must Fix Before Deployment)

### Task 1: CSRF Protection for All POST Requests
**Severity**: CRITICAL
**Files**: `index.php`, `includes/database.php`, `includes/url_helper.php`

**Current Issue**:
```php
// index.php lines 40-50 - CSRF only for specific routes
if (in_array($route, ['register', 'user/login'], true)) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
```

**Fix Required**:
1. Update `index.php` - Apply CSRF to ALL POST requests
2. Create `csrf_field()` helper in `includes/url_helper.php`
3. Add CSRF tokens to ALL forms in admin panel

**Implementation Steps**:
- [ ] Step 1.1: Modify `index.php` CSRF validation (lines ~40-50)
- [ ] Step 1.2: Add `csrf_field()` function to `includes/url_helper.php`
- [ ] Step 1.3: Update admin login form (`admin/login/index.php`)
- [ ] Step 1.4: Update all admin forms (products, orders, users, sliders)
- [ ] Step 1.5: Update user forms (register, profile)
- [ ] Step 1.6: Test all POST operations

**Code Reference**:
```php
// Add to includes/url_helper.php
function csrf_field() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}
```

---

### Task 2: Fix SQL Injection Risk via Direct Queries
**Severity**: CRITICAL
**Files**: `includes/database.php`, `admin/products/add.php`, `index.php`

**Current Issue**:
```php
// includes/database.php line 64
public function testConnection() {
    $stmt = $this->pdo->query("SELECT 'Connection successful' as message");
}
```

**Fix Required**:
1. Audit all direct `query()` calls
2. Replace with prepared statements
3. Create `safeQuery()` wrapper method

**Implementation Steps**:
- [ ] Step 2.1: Add `safeQuery()` method to Database class
- [ ] Step 2.2: Audit `index.php` for direct queries (lines 213-248)
- [ ] Step 2.3: Audit `admin/products/add.php` line 113
- [ ] Step 2.4: Replace all direct queries with prepared statements
- [ ] Step 2.5: Test database operations

**Code Reference**:
```php
// Add to Database class
public function safeQuery($sql, $params = []) {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
```

---

### Task 3: Remove Information Disclosure in Error Messages
**Severity**: CRITICAL
**Files**: `admin/products/add.php`, all admin files

**Current Issue**:
```php
// admin/products/add.php line 105
} catch (Exception $e) {
    error_log("Error creating product: " . $e->getMessage());
    $_SESSION['error'] = 'Error creating product: ' . $e->getMessage(); // EXPOSES DETAILS
}
```

**Fix Required**:
1. Replace all exception message exposures with generic messages
2. Log details to error file only
3. Add development mode detection

**Implementation Steps**:
- [ ] Step 3.1: Create `AppError` class in `includes/error_handler.php`
- [ ] Step 3.2: Update `admin/products/add.php` error handling
- [ ] Step 3.3: Update `admin/products/edit.php` error handling
- [ ] Step 3.4: Update all admin files with try/catch blocks
- [ ] Step 3.5: Update user-facing error messages
- [ ] Step 3.6: Test error scenarios

**Files to Update**:
- `admin/products/add.php`
- `admin/products/edit.php`
- `admin/orders/*.php`
- `admin/users/*.php`
- `admin/sliders/*.php`
- `user/register/index.php`
- `user/login/index.php`

**Code Reference**:
```php
// Create includes/error_handler.php
class AppError {
    public static function handleDatabaseError($e, $userMessage = 'Database error occurred') {
        error_log("DB Error: " . $e->getMessage());
        if (self::isDevelopment()) {
            return $e->getMessage();
        }
        return $userMessage;
    }

    public static function isDevelopment() {
        return in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);
    }
}
```

---

## P1 - HIGH (Should Fix for Production Security)

### Task 4: Fix Open Redirect Vulnerability
**Severity**: HIGH
**Files**: `user/login/index.php`

**Current Issue**:
```php
// user/login/index.php lines 41-48
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : null;
if (!$redirect || !str_starts_with($redirect, '/') || strpos($redirect, 'login') !== false) {
    $redirect = userUrl('/dashboard/');
}
header('Location: ' . $redirect); // VULNERABLE to //evil.com
```

**Fix Required**:
- Implement whitelist-based redirect validation

**Implementation Steps**:
- [ ] Step 4.1: Define allowed redirects array
- [ ] Step 4.2: Update redirect validation logic
- [ ] Step 4.3: Test redirect functionality

**Code Reference**:
```php
$allowedRedirects = [
    userUrl('/dashboard/'),
    userUrl('/orders/'),
    userUrl('/profile/')
];

if ($redirect && in_array($redirect, $allowedRedirects, true)) {
    header('Location: ' . $redirect);
} else {
    header('Location: ' . userUrl('/dashboard/'));
}
exit;
```

---

### Task 5: Add CSRF Token to Admin Login
**Severity**: HIGH
**Files**: `admin/login/index.php`

**Current Issue**: Admin login form has no CSRF protection

**Fix Required**:
- Add CSRF token field to admin login form
- Validate token on submission

**Implementation Steps**:
- [ ] Step 5.1: Add `csrf_field()` to admin login form
- [ ] Step 5.2: Test admin login with CSRF

---

### Task 6: Fix Session Fixation Vulnerability
**Severity**: HIGH
**Files**: `includes/database.php`

**Current Issue**:
```php
// index.php line 7 - Session started before authentication
session_start();

// includes/database.php line 117 - No session regeneration after login
$_SESSION['admin_id'] = $admin['id'];
```

**Fix Required**:
- Regenerate session ID after successful authentication

**Implementation Steps**:
- [ ] Step 6.1: Add `session_regenerate_id(true)` to `AdminAuth::login()` (after line ~117)
- [ ] Step 6.2: Add `session_regenerate_id(true)` to `UserAuth::login()` (after line ~224)
- [ ] Step 6.3: Test login flows

**Code Reference**:
```php
// In AdminAuth::login() after successful auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true);
$_SESSION['admin_id'] = $admin['id'];
```

---

### Task 7: Implement Password Strength Validation
**Severity**: HIGH
**Files**: `includes/database.php`

**Current Issue**:
```php
// includes/database.php line 194
public function register($data) {
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    // NO VALIDATION
}
```

**Fix Required**:
- Add password strength validation before hashing

**Implementation Steps**:
- [ ] Step 7.1: Create `validatePasswordStrength()` function
- [ ] Step 7.2: Add validation to `UserAuth::register()`
- [ ] Step 7.3: Add validation to user profile password change
- [ ] Step 7.4: Test password validation

**Validation Rules**:
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number

**Code Reference**:
```php
private function validatePasswordStrength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    return $errors;
}
```

---

### Task 8: Add Input Validation for Product Fields
**Severity**: HIGH
**Files**: `admin/products/add.php`, `admin/products/edit.php`

**Current Issue**:
```php
// admin/products/add.php lines 78-99
$stmt->execute([
    $_POST['name'],           // NO VALIDATION
    $_POST['description'],     // NO VALIDATION
    $_POST['price'],          // NO VALIDATION
    $_POST['stock'],          // NO VALIDATION
]);
```

**Fix Required**:
- Create `InputSanitizer` class
- Validate all product fields before database insert

**Implementation Steps**:
- [ ] Step 8.1: Create `includes/input_sanitizer.php`
- [ ] Step 8.2: Update `admin/products/add.php` with validation
- [ ] Step 8.3: Update `admin/products/edit.php` with validation
- [ ] Step 8.4: Test product creation/editing

**Code Reference**:
```php
// Create includes/input_sanitizer.php
class InputSanitizer {
    public static function string($string, $maxLength = 255) {
        return mb_substr(trim(strip_tags($string)), 0, $maxLength);
    }

    public static function float($float, $min = null, $max = null) {
        $float = filter_var($float, FILTER_VALIDATE_FLOAT);
        if ($float === false) return 0.0;
        if ($min !== null && $float < $min) return $min;
        if ($max !== null && $float > $max) return $max;
        return $float;
    }

    public static function int($int, $min = null, $max = null) {
        $int = filter_var($int, FILTER_VALIDATE_INT);
        if ($int === false) return 0;
        if ($min !== null && $int < $min) return $min;
        if ($max !== null && $int > $max) return $max;
        return $int;
    }
}
```

---

### Task 9: Configure Secure Session Cookies
**Severity**: HIGH
**Files**: `includes/database.php`

**Current Issue**:
```php
// includes/database.php lines 222-223
session_set_cookie_params(0, '/');
```

**Fix Required**:
- Add secure, httponly, samesite parameters

**Implementation Steps**:
- [ ] Step 9.1: Update `session_set_cookie_params()` with secure options
- [ ] Step 9.2: Test session functionality

**Code Reference**:
```php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,      // HTTPS only
    'httponly' => true,    // Not accessible via JavaScript
    'samesite' => 'Strict' // Prevent CSRF
]);
```

---

### Task 10: Remove Debug Parameters from Production
**Severity**: HIGH
**Files**: `admin/products/add.php`, `route.php`, `index.php`

**Current Issue**:
```php
// admin/products/add.php lines 7-10
if (isset($_GET['debug']) || isset($_GET['whoops'])) {
    require_once __DIR__ . '/../../includes/whoops_handler.php';
}

// route.php lines 36-43
if (isset($_GET['debug_routing'])) {
    header('Content-Type: text/html');
    echo "<h2>Routing Debug</h2>";
    // EXPOSES INTERNAL ROUTING
}
```

**Fix Required**:
- Remove URL-based debug parameters
- Use environment-based configuration

**Implementation Steps**:
- [ ] Step 10.1: Create environment-based debug config
- [ ] Step 10.2: Remove `?debug` parameter code from all files
- [ ] Step 10.3: Remove `?debug_routing` from route.php
- [ ] Step 10.4: Remove `?whoops` from admin files

**Code Reference**:
```php
// Add to config or top of index.php
define('DEBUG_MODE', getenv('APP_DEBUG') === 'true' || $_SERVER['HTTP_HOST'] === 'localhost');

// Then use:
if (DEBUG_MODE && isset($_GET['debug'])) {
    // Debug code
}
```

---

### Task 11: Add File Upload Validation
**Severity**: HIGH
**Files**: File upload handlers (inferred location)

**Fix Required**:
- Implement comprehensive file upload validation

**Implementation Steps**:
- [ ] Step 11.1: Locate all file upload handlers
- [ ] Step 11.2: Create `validateImageUpload()` function
- [ ] Step 11.3: Add validation to all upload points
- [ ] Step 11.4: Test file uploads

**Code Reference**:
```php
function validateImageUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxSize) {
        throw new RuntimeException('File too large. Maximum 5MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedTypes)) {
        throw new RuntimeException('Invalid file type.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        throw new RuntimeException('Invalid file extension.');
    }

    if (!getimagesize($file['tmp_name'])) {
        throw new RuntimeException('File is not a valid image.');
    }

    return true;
}
```

---

## P2 - MEDIUM (Quality & Performance)

### Task 12: Add Database Indexes
**Severity**: MEDIUM
**Files**: Database schema

**Implementation Steps**:
- [ ] Step 12.1: Create SQL migration file with indexes
- [ ] Step 12.2: Run migration on database
- [ ] Step 12.3: Verify query performance improvement

**SQL Commands**:
```sql
CREATE INDEX idx_products_featured ON products(featured, status, created_at);
CREATE INDEX idx_products_sale ON products(on_sale, status, created_at);
CREATE UNIQUE INDEX idx_products_slug ON products(slug);
CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_created ON orders(created_at DESC);
CREATE INDEX idx_orders_status ON orders(status, created_at DESC);
```

---

### Task 13: Implement Output Caching
**Severity**: MEDIUM
**Files**: `index.php`

**Implementation Steps**:
- [ ] Step 13.1: Create `cache/` directory
- [ ] Step 13.2: Implement caching function
- [ ] Step 13.3: Apply to homepage data fetching
- [ ] Step 13.4: Test cache invalidation

---

### Task 14: Move Credentials to Environment Variables
**Severity**: MEDIUM
**Files**: `includes/database.php`

**Current Issue**:
```php
private $host = 'localhost';
private $db_name = 'cannabuddy';
private $username = 'root';
private $password = 'root';
```

**Fix Required**:
- Load credentials from environment

**Implementation Steps**:
- [ ] Step 14.1: Create `.env.example` file
- [ ] Step 14.2: Update Database class to use getenv()
- [ ] Step 14.3: Document environment variables
- [ ] Step 14.4: Test with different environments

---

### Task 15: Standardize Error Handling
**Severity**: MEDIUM
**Files**: Multiple files with try/catch

**Implementation Steps**:
- [ ] Step 15.1: Create `includes/error_handler.php` (already in Task 3)
- [ ] Step 15.2: Update all error handling to use AppError class
- [ ] Step 15.3: Test error scenarios

---

### Task 16: Ensure Consistent htmlspecialchars Usage
**Severity**: LOW/MEDIUM
**Files**: Multiple output files

**Implementation Steps**:
- [ ] Step 16.1: Audit all echo statements for missing htmlspecialchars
- [ ] Step 16.2: Use existing `safe_html()` helper throughout
- [ ] Step 16.3: Test XSS prevention

---

## Additional Security Checklist

### HTTP Headers (Task 17)
- [ ] Add Content Security Policy (CSP) header
- [ ] Add X-Frame-Options: DENY
- [ ] Add X-XSS-Protection
- [ ] Add Strict-Transport-Security (HSTS)
- [ ] Add Referrer-Policy
- [ ] Add Permissions-Policy

### Rate Limiting (Task 18)
- [ ] Implement API rate limiting
- [ ] Add request throttling
- [ ] Add IP-based blocking for repeated failures

### Audit Logging (Task 19)
- [ ] Implement audit logging for sensitive operations
- [ ] Create log review process

---

## Testing & Verification

### Test Files to Run:
```bash
php test_delete/test_database.php
php test_delete/test_admin_flow.php
bash test_delete/test_system.sh
```

### Verification Checklist:
- [ ] All POST operations require valid CSRF token
- [ ] No direct SQL queries with user input
- [ ] No exception messages exposed to users
- [ ] Redirects use whitelist validation
- [ ] Session IDs regenerated after login
- [ ] Passwords meet strength requirements
- [ ] All input validated and sanitized
- [ ] Session cookies are secure
- [ ] No debug parameters in production code
- [ ] File uploads validated
- [ ] Database indexes in place
- [ ] Credentials from environment

---

## Implementation Order

### Phase 1: Critical Security (1-2 days)
1. Task 1: CSRF for all POST requests
2. Task 2: Fix SQL injection risks
3. Task 3: Remove error message disclosure

### Phase 2: High Priority (2-3 days)
4. Task 4: Open redirect fix
5. Task 5: Admin login CSRF
6. Task 6: Session fixation
7. Task 7: Password strength
8. Task 8: Input validation
9. Task 9: Secure session cookies
10. Task 10: Remove debug parameters
11. Task 11: File upload validation

### Phase 3: Medium Priority (1-2 days)
12. Task 12: Database indexes
13. Task 13: Output caching
14. Task 14: Environment variables
15. Task 15: Standardize error handling
16. Task 16: htmlspecialchars consistency

### Phase 4: Security Hardening (1 day)
17. Task 17: HTTP headers
18. Task 18: Rate limiting
19. Task 19: Audit logging

---

## Files to Create

| File | Purpose |
|------|---------|
| `includes/error_handler.php` | Standardized error handling |
| `includes/input_sanitizer.php` | Input sanitization class |
| `cache/.gitkeep` | Cache directory |
| `.env.example` | Environment variable template |

---

## Notes

- Delete this file after all tasks are completed
- Update this file as tasks are completed
- Each task should be tested individually
- Create backup before making changes
- Test in development environment first

---

**END OF PLAN**
