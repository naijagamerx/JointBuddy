# Login Page Security Fixes - Detailed Implementation Plan

## Phase 1: Database Schema Enhancement

### 1.1 Add Brute Force Protection Columns

**File**: Database (via MySQL command line)

**Commands**:
```bash
C:\MAMP\bin\mysql\bin\mysql -u root -proot cannabuddy -e "ALTER TABLE users ADD COLUMN login_attempts INT(11) DEFAULT 0 AFTER last_login;"
C:\MAMP\bin\mysql\bin\mysql -u root -proot cannabuddy -e "ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL DEFAULT NULL AFTER login_attempts;"
```

**Verification**:
```bash
C:\MAMP\bin\mysql\bin\mysql -u root -proot cannabuddy -e "DESCRIBE users;"
```

---

## Phase 2: UserAuth Class Enhancement

### 2.1 Add Methods to UserAuth Class

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\includes\database.php`

**Location**: After line 351 (after `logSecurityEvent()` method)

**Add These Methods**:

```php
/**
 * Check if user account is currently locked
 */
public function isAccountLocked($user) {
    if (!$user || empty($user['locked_until'])) {
        return false;
    }

    $lockedUntil = strtotime($user['locked_until']);
    if ($lockedUntil <= time()) {
        $this->resetLoginAttempts($user['id']);
        return false;
    }

    return true;
}

/**
 * Increment login attempts after failed login
 */
private function incrementLoginAttempts($userId) {
    $stmt = $this->db->prepare("
        UPDATE users
        SET login_attempts = login_attempts + 1,
            locked_until = CASE
                WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                ELSE NULL
            END
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
}

/**
 * Reset login attempts after successful login
 */
private function resetLoginAttempts($userId) {
    $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$userId]);
}
```

### 2.2 Modify login() Method

**Location**: Replace lines 215-246

**New login() signature**:
```php
public function login($email, $password, $ipAddress = null)
```

**Add after fetching user**:
```php
// Check if account is locked
if ($this->isAccountLocked($user)) {
    return ['success' => false, 'message' => 'Account temporarily locked. Try again later.'];
}

// Verify password
if (!password_verify($password, $user['password'])) {
    $this->incrementLoginAttempts($user['id']);
    return ['success' => false, 'message' => 'Invalid credentials'];
}
```

**Add after successful login**:
```php
$this->resetLoginAttempts($user['id']);
```

---

## Phase 3: CSRF Protection for User Login

### 3.1 Add CSRF Field to Form

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\user\login/index.php`

**Location**: After line 125 (inside `<form>` tag)

**Add**:
```php
<?php echo csrf_field(); ?>
```

### 3.2 Add CSRF Validation

**Location**: After line 30 (before email processing)

**Add**:
```php
// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $errorMessage = 'Security check failed. Please try again.';
    error_log('User login CSRF validation failed from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}
```

---

## Phase 4: Security Headers

### 4.1 Create Security Headers File

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\includes\security_headers.php` (NEW)

**Content**:
```php
<?php
/**
 * Security Headers for CannaBuddy
 */

if (!function_exists('sendLoginSecurityHeaders')) {
    function sendLoginSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none';");

        // Login pages should never be cached
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }
}
?>
```

### 4.2 Add Headers to User Login

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\user\login\index.php`

**Location**: After line 4 (after `session_start()`)

**Add**:
```php
require_once __DIR__ . '/../../includes/security_headers.php';
sendLoginSecurityHeaders();
```

### 4.3 Add Headers to Admin Login

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\admin\login\index.php`

**Location**: After line 5 (after session check)

**Add**:
```php
require_once __DIR__ . '/../../includes/security_headers.php';
sendLoginSecurityHeaders();
```

---

## Phase 5: Secure Session Cookies

### 5.1 Configure Session Cookies Globally

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\index.php`

**Location**: Replace line 7 (session_start())

**Replace with**:
```php
// Configure secure session cookies
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
```

---

## Phase 6: Redirect Validation

### 6.1 Add Redirect Validation Function

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\includes\url_helper.php`

**Location**: After line 128 (after redirect() function)

**Add**:
```php
/**
 * Validate redirect URL against whitelist
 */
function validateRedirect($redirect) {
    if (empty($redirect)) {
        return true;
    }

    // Block protocol-relative URLs (//evil.com)
    if (strpos($redirect, '//') === 0) {
        return false;
    }

    // Block external URLs
    if (preg_match('#^(https?:|//)#i', $redirect)) {
        return false;
    }

    // Must start with /
    if (strpos($redirect, '/') !== 0) {
        return false;
    }

    // Whitelist of allowed paths
    $allowedPaths = ['/user/dashboard', '/user/orders', '/user/profile', '/user/checkout', '/shop', '/product'];

    foreach ($allowedPaths as $allowed) {
        if (strpos($redirect, $allowed) === 0) {
            return true;
        }
    }

    return false;
}
```

### 6.2 Update User Login Redirects

**File**: `C:\MAMP\htdocs\CannaBuddy.shop\user\login\index.php`

**Location 1**: Lines 40-48 (successful login)

**Replace with**:
```php
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? null;

if (validateRedirect($redirect)) {
    header('Location: ' . url(ltrim($redirect, '/')));
} else {
    header('Location: ' . userUrl('/dashboard/'));
}
exit;
```

**Location 2**: Lines 57-63 (already logged in)

**Replace with**:
```php
$redirect = $_GET['redirect'] ?? null;

if (validateRedirect($redirect)) {
    header('Location: ' . url(ltrim($redirect, '/')));
} else {
    header('Location: ' . userUrl('/dashboard/'));
}
exit;
```

---

## Phase 7: Testing

### 7.1 Test CSRF Protection

```bash
# Navigate to login page
# View page source - should see <input type="hidden" name="csrf_token" value="...">
# Try submitting with modified token - should get "Security check failed"
```

### 7.2 Test Account Locking

```bash
# Attempt login with wrong password 5 times
# On 5th attempt, should see "Account temporarily locked"
# Check database: SELECT email, login_attempts, locked_until FROM users WHERE email='test@example.com';
```

### 7.3 Test Security Headers

```bash
curl -I http://localhost/CannaBuddy.shop/user/login/

# Should see:
# X-Frame-Options: SAMEORIGIN
# X-XSS-Protection: 1; mode=block
# X-Content-Type-Options: nosniff
# Content-Security-Policy: ...
# Cache-Control: no-store
```

### 7.4 Test Redirect Validation

```bash
# Test valid redirect
curl "http://localhost/CannaBuddy.shop/user/login/?redirect=/user/dashboard"

# Test malicious redirect
curl "http://localhost/CannaBuddy.shop/user/login/?redirect=//evil.com"
# Should be blocked and redirect to /user/dashboard/
```

---

## Verification Checklist

- [ ] Database columns added (login_attempts, locked_until)
- [ ] UserAuth methods added (isAccountLocked, incrementLoginAttempts, resetLoginAttempts)
- [ ] UserAuth login() method modified with locking logic
- [ ] CSRF token field added to user login form
- [ ] CSRF validation added to POST handler
- [ ] Security headers file created
- [ ] Security headers added to user login page
- [ ] Security headers added to admin login page
- [ ] Session cookies configured with httponly and samesite
- [ ] Redirect validation function added
- [ ] User login redirects updated with validation
- [ ] CSRF protection tested
- [ ] Account locking tested
- [ ] Security headers verified with curl
- [ ] Redirect validation tested
- [ ] PHP syntax check passed on all modified files

---

## Rollback Plan

If critical issues arise:

1. **Remove security headers** - Delete `require_once` lines from login pages
2. **Remove CSRF validation** - Comment out CSRF check in user login
3. **Drop database columns**:
   ```sql
   ALTER TABLE users DROP COLUMN login_attempts;
   ALTER TABLE users DROP COLUMN locked_until;
   ```
4. **Restore old code** - Use git to revert changes

---

## Expected Outcome

✅ Antivirus alerts resolved
✅ CSRF protection prevents cross-site attacks
✅ Brute force protection prevents automated guessing
✅ Security headers add browser-level protection
✅ Secure session cookies prevent hijacking
✅ Redirect validation prevents open redirects
✅ Consistent security patterns across admin/user login
