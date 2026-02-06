## Fix Admin Login Redirect Issue

**Problem**: The admin login page doesn't redirect to the dashboard after successful login because the `/admin/` path is not included in the `validateRedirect()` function's allowed paths whitelist.

### Root Cause
In `includes/url_helper.php`, the `validateRedirect()` function blocks redirects to paths not in its whitelist. The whitelist only contains user/shop paths (`/user/dashboard`, `/shop`, `/product`, etc.) but does NOT include `/admin/` paths. When `redirect('/admin/')` generates a full URL (starting with `http`), it gets blocked by validation.

### Solution
Add admin paths to the allowed paths whitelist in the `validateRedirect()` function.

### Changes Required
**File**: `includes/url_helper.php`
- **Line 221-229**: Add `/admin` and `/admin/login` to the `$allowedPaths` array in the `validateRedirect()` function

### Implementation
```php
// Update the allowedPaths array to include admin routes:
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
```

### Testing
1. Clear browser cache/cookies
2. Navigate to admin login page
3. Enter valid credentials
4. Verify redirect to `/admin/` dashboard succeeds