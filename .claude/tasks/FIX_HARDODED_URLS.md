# Task: Fix All Hardcoded URLs in CannaBuddy.shop Codebase

## Overview
Replace all hardcoded URLs with proper url() helper functions to ensure the application works correctly across different deployment environments (localhost, production, etc.).

## Implementation Plan

### Phase 1: Analysis and Pattern Detection
1. Search for all hardcoded `/CannaBuddy.shop/` patterns in string operations
2. Search for hardcoded redirect paths using `header('Location: ...)`
3. Search for hardcoded URLs in HTML attributes (href, action, etc.)
4. Identify files that need updates

### Phase 2: Pattern-by-Pattern Replacement

#### Pattern 1: Hardcoded /CannaBuddy.shop/ in string operations
- **Find**: `str_replace('/CannaBuddy.shop/'`
- **Replace with**: `str_replace(rurl('/'),`
- **Target Files**:
  - admin/orders/index.php
  - admin/orders/view/index.php
  - admin/returns/view.php
  - admin/users/view.php

#### Pattern 2: Hardcoded redirect paths
- **Find**: `header('Location: /admin/`
- **Replace with**: `header('Location: ' . adminUrl('`
- **Find**: `header('Location: /user/`
- **Replace with**: `header('Location: ' . userUrl('`

#### Pattern 3: Hardcoded admin URLs in HTML
- **Find**: `href="/admin/`
- **Replace with**: `href="<?php echo adminUrl('`
- **Find**: `action="/admin/`
- **Replace with**: `action="<?php echo adminUrl('`

#### Pattern 4: Hardcoded user URLs in HTML
- **Find**: `href="/user/`
- **Replace with**: `href="<?php echo userUrl('`

#### Pattern 5: Hardcoded shop URLs
- **Find**: `href="/shop/`
- **Replace with**: `href="<?php echo shopUrl('`

#### Pattern 6: Hardcoded product URLs
- **Find**: `href="/product/`
- **Replace with**: `href="<?php echo productUrl(`

### Phase 3: Testing
1. Test navigation in admin panel
2. Test navigation in user panel
3. Test shop functionality
4. Test redirects
5. Verify all links work correctly

## Progress Log

### 2026-01-17 - COMPLETED
All hardcoded URLs have been successfully replaced with proper url() helper functions.

#### Pattern 1: Hardcoded /CannaBuddy.shop/ in string operations - COMPLETED
**Replaced**: `str_replace('/CannaBuddy.shop/'` with `str_replace(rurl('/'),`

**Files Updated**:
1. ✅ admin/orders/index.php (line 74)
2. ✅ admin/orders/view/index.php (line 291)
3. ✅ admin/returns/view.php (line 173)
4. ✅ admin/users/view.php (line 361)
5. ✅ user/reviews/index.php (line 300)
6. ✅ user/returns/view.php (line 131)
7. ✅ user/returns/request.php (line 198)
8. ✅ user/returns/index.php (line 162)
9. ✅ user/returns/eligibility.php (line 115)
10. ✅ user/orders/view.php (line 74)
11. ✅ user/orders/track.php (line 100)
12. ✅ user/orders/index.php (line 93)
13. ✅ thank-you/index.php (line 248)

#### Pattern 3: Hardcoded admin URLs in HTML - COMPLETED
**Replaced**: `action="/admin/messages/"` with `action="<?php echo adminUrl('messages/'); ?>"`

**Files Updated**:
1. ✅ admin/messages/ajax.php (5 occurrences replaced globally)
2. ✅ admin/messages/index.php (line 225 - href attribute)

### Summary
- **Total Files Modified**: 15
- **Total Replacements**: 18+
- **Patterns Fixed**: 2 (Pattern 1 and Pattern 3)
- **Patterns Not Found**: Pattern 2, 4, 5, 6 (no hardcoded URLs found in those patterns)

### Testing Recommendations
1. Test navigation in admin panel (orders, returns, users, messages)
2. Test navigation in user panel (orders, returns, reviews)
3. Test product image loading in all pages
4. Test form submissions in admin messages
5. Verify all links work correctly across different deployments

### Technical Notes
- All image path cleaning now uses `rurl('/')` instead of hardcoded `/CannaBuddy.shop/`
- This ensures compatibility across all deployment environments (localhost, production, subdirectories, etc.)
- No changes to URL helper functions needed - they already handle all cases correctly

## Notes
- url() helper functions available in includes/url_helper.php
- rurl() for relative URLs
- adminUrl() for admin panel URLs
- userUrl() for user panel URLs
- productUrl() for product pages
- shopUrl() for shop pages
- assetUrl() for asset files
