# Update User Files to Use New Middleware and Session Patterns

## Overview
Update all user-facing files to use the new bootstrap loading system, middleware patterns, and centralized authentication while preserving ALL existing functionality.

## Analysis

### Current State
- Each file has duplicate includes (database.php, url_helper.php, session_start())
- Authentication checks are inline and inconsistent
- CSRF validation is manual and repetitive
- Error handling varies between files
- No unified initialization pattern

### Target State
- Single bootstrap.php include at top of each file
- Consistent AuthMiddleware::requireUser() for protected pages
- CsrfMiddleware::validate() for all POST handlers
- Unified error handling via bootstrap
- Services layer for database and auth access

## Files to Update (30 files)

### Authentication Files (3)
1. user/login/index.php - Public, handles login POST
2. user/forgot-password/index.php - Public, password reset request
3. user/reset-password/index.php - Public, password reset form

### Protected Dashboard Files (20)
4. user/dashboard/index.php - Main dashboard
5. user/profile/index.php - Profile overview
6. user/personal-details/index.php - Personal details management
7. user/security-settings/index.php - Security settings
8. user/address-book/index.php - Address list
9. user/address-book/add.php - Add address (POST)
10. user/address-book/edit.php - Edit address (POST)
11. user/orders/index.php - Order list
12. user/orders/view.php - View order details
13. user/orders/track.php - Track order
14. user/invoices/index.php - Invoice list
15. user/invoices/view.php - View invoice
16. user/payment-history/index.php - Payment history
17. user/returns/index.php - Returns list
18. user/returns/request.php - Request return (POST)
19. user/returns/view.php - View return details
20. user/returns/cancel.php - Cancel return (POST)
21. user/reviews/index.php - Product reviews
22. user/support/index.php - Support tickets
23. user/help-centre/index.php - Help center

### Additional Features (6)
24. user/redeem-voucher/index.php - Voucher redemption (POST)
25. user/coupons-offers/index.php - Coupons list
26. user/credit-refunds/index.php - Credit/refund history
27. user/my-lists/index.php - User lists
28. user/create-list/index.php - Create list (POST)
29. user/newsletter-subscriptions/index.php - Newsletter preferences (POST)
30. user/subscription-plan/index.php - Subscription management

### Navigation & Utilities (4)
31. user/logout/index.php - Logout handler
32. user/index.php - User area entry/redirect
33. user/navigation-helper.php - Navigation helpers
34. user/components/header.php - Dashboard header
35. user/components/footer.php - Dashboard footer
36. user/components/sidebar.php - Dashboard sidebar

## Implementation Pattern

### For Authentication Files (login, forgot-password, reset-password)
```php
<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// These are public pages, no auth required
// Get services
$userAuth = Services::userAuth();
$db = Services::db();

// Handle POST with CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    // ... existing POST logic
}

// ... rest of existing logic
```

### For Protected Dashboard Files
```php
<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();

// Get services
$db = Services::db();
$userAuth = Services::userAuth();

// Handle POST with CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    // ... existing POST logic
}

// ... rest of existing logic
```

### For Component Files (header, footer, sidebar)
```php
<?php
// Include bootstrap if not already loaded
if (!function_exists('Services')) {
    require_once __DIR__ . '/../../includes/bootstrap.php';
}

// Get current user (may be null if not logged in)
$currentUser = AuthMiddleware::getCurrentUser();
$isLoggedIn = AuthMiddleware::isUserLoggedIn();

// ... rest of component logic
```

## Step-by-Step Implementation

### Phase 1: Authentication Files (Priority: High)
- [ ] Update user/login/index.php
- [ ] Update user/forgot-password/index.php
- [ ] Update user/reset-password/index.php

### Phase 2: Core Dashboard Files (Priority: High)
- [ ] Update user/dashboard/index.php
- [ ] Update user/profile/index.php
- [ ] Update user/logout/index.php

### Phase 3: Component Files (Priority: High)
- [ ] Update user/components/header.php
- [ ] Update user/components/footer.php
- [ ] Update user/components/sidebar.php

### Phase 4: Address & Profile Management (Priority: Medium)
- [ ] Update user/personal-details/index.php
- [ ] Update user/security-settings/index.php
- [ ] Update user/address-book/index.php
- [ ] Update user/address-book/add.php
- [ ] Update user/address-book/edit.php

### Phase 5: Orders & Invoices (Priority: Medium)
- [ ] Update user/orders/index.php
- [ ] Update user/orders/view.php
- [ ] Update user/orders/track.php
- [ ] Update user/invoices/index.php
- [ ] Update user/invoices/view.php

### Phase 6: Payments & Returns (Priority: Medium)
- [ ] Update user/payment-history/index.php
- [ ] Update user/returns/index.php
- [ ] Update user/returns/request.php
- [ ] Update user/returns/view.php
- [ ] Update user/returns/cancel.php

### Phase 7: Additional Features (Priority: Low)
- [ ] Update user/redeem-voucher/index.php
- [ ] Update user/coupons-offers/index.php
- [ ] Update user/credit-refunds/index.php
- [ ] Update user/my-lists/index.php
- [ ] Update user/create-list/index.php
- [ ] Update user/newsletter-subscriptions/index.php
- [ ] Update user/subscription-plan/index.php
- [ ] Update user/reviews/index.php
- [ ] Update user/support/index.php
- [ ] Update user/help-centre/index.php

### Phase 8: Utility Files (Priority: Low)
- [ ] Update user/index.php
- [ ] Update user/navigation-helper.php

## Testing Checklist

After each file update, verify:
- [ ] Page loads without errors
- [ ] Authentication redirect works (for protected pages)
- [ ] POST forms submit successfully
- [ ] CSRF validation works
- [ ] All existing functionality preserved
- [ ] Session data accessible
- [ ] Database queries work
- [ ] URL helpers generate correct links

## Important Notes

1. **Preserve ALL functionality** - Only update initialization, don't change business logic
2. **Maintain backward compatibility** - Keep existing variable names where possible
3. **Error handling** - Bootstrap provides error handling, keep existing try/catch where needed
4. **Session variables** - Existing $_SESSION['user_id'] etc. still work
5. **CSRF tokens** - csrf_field() function still works, now wrapped by CsrfMiddleware
6. **URL helpers** - All url(), userUrl() functions still work
7. **Test thoroughly** - Each file should be tested after update

## Progress

- [x] Analysis complete
- [x] Plan created
- [ ] Implementation in progress
