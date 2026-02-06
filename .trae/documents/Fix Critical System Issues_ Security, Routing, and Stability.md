# Implementation Plan: Fix Critical System Issues

## Task 1: Security Hardening (Antivirus & Redirects)
**Goal**: Remove triggers for antivirus alerts and secure open redirects.
1.  **Block Sensitive Files**: Update `.htaccess` to deny access to development artifacts (`cookies.txt`, `*.md`, `*.json`, `*.lock`, `*.sql`).
2.  **Secure Redirects**: Update `redirect()` in [url_helper.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/includes/url_helper.php) to use `validateRedirect()` and prevent open redirects to external domains (unless explicitly allowed).
3.  **Clean Artifacts**: Delete `cookies.txt` (contains session ID) and other temporary files from the web root.

## Task 2: Fix Admin Routing & 404 Errors
**Goal**: Ensure all admin pages load correctly.
1.  **Fix `.htaccess` Base**: Update `.htaccess` to handle `RewriteBase` dynamically or correct it for the environment (removing hardcoded `/CannaBuddy.shop/` if causing issues, or ensuring it matches).
2.  **Fix `admin_routes.php`**:
    *   Review and fix `require` paths in [admin_routes.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/includes/admin_routes.php).
    *   Ensure "pretty" URLs (e.g., `admin/products/reviews`) map correctly to their files.
3.  **Fix Admin Settings Loop**: Remove or fix the self-redirect loop for `admin/settings` in [admin_routes.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/includes/admin_routes.php).

## Task 3: Fix QR Code Image References
**Goal**: Remove hardcoded `localhost` URLs from the database and code.
1.  **Update Generator**: Modify [QRCodeService.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/includes/services/QRCodeService.php) to store **relative paths** (e.g., `/assets/qr-codes/img.png`) in the database instead of absolute URLs.
2.  **Fix Display**: Update [admin/qr-codes/index.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/admin/qr-codes/index.php) to wrap the stored path with `url()` or `assetUrl()` when displaying.
3.  **Migration (Optional)**: Provide a script to fix existing absolute URL entries in the `qr_codes` table.

## Task 4: Favicon Upload & Settings
**Goal**: Make favicon upload accessible.
1.  **Expose Upload**: Add a clear "Appearance & Branding" link or section in [admin/settings/index.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/admin/settings/index.php) pointing to `appearance.php`.
2.  **Verify Handling**: Ensure [admin/settings/appearance.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/admin/settings/appearance.php) correctly handles the file upload (already verified in analysis, just needs testing).

## Task 5: Fix 500 Error on Payment Methods
**Goal**: Fix the fatal error in payment method editors.
1.  **Correct Include Path**: Update [admin/payment-methods/edit/index.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/admin/payment-methods/edit/index.php) and [admin/payment-methods/add/index.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/admin/payment-methods/add/index.php).
    *   Change `__DIR__ . '/../../../../includes/bootstrap.php'` (4 levels up) to `__DIR__ . '/../../../includes/bootstrap.php'` (3 levels up).

## Task 6: Fix Admin Login Conflicts
**Goal**: Isolate sessions between projects.
1.  **Scope Session Cookie**: Update [includes/session_helper.php](file:///c:/MAMP/htdocs/CannaBuddy.shop/includes/session_helper.php) to set the cookie `path` to the application's base path (using `getAppBasePath()`) instead of root `/`.
2.  **Unique Session Name**: Set a unique `session_name()` (e.g., `CANNABUDDY_SESSION`) in `bootstrap.php` or `session_helper.php` to avoid colliding with other `PHPSESSID` cookies on localhost.
