# CannaBuddy.shop - Codebase Architecture Improvement Plan
**Date**: 2026-02-06
**Analysis Method**: Comprehensive codebase scan using auggie-mcp
**Status**: Ready for Review

---

## Executive Summary

Your codebase has undergone **significant recent refactoring** (January 2026) with modern patterns (Services, Middleware, Validator, Bootstrap). However, several structural issues remain that impact maintainability and deployment.

**Overall Assessment**: 7/10 - Good foundation, needs cleanup.

---

## Current Architecture Overview

### What's Working Well ✅

| Component | Status | Details |
|-----------|--------|---------|
| **Services Container** | ✅ Excellent | Single DB connection, singleton pattern |
| **AuthMiddleware** | ✅ Excellent | Centralized authentication checks |
| **CsrfMiddleware** | ✅ Excellent | All POST handlers protected |
| **Validator** | ✅ Excellent | 13+ validation methods |
| **Bootstrap** | ✅ Excellent | Single include for all core services |
| **Database Indexes** | ✅ Just Added | 16 new indexes for performance |
| **Security** | ✅ Good | Bcrypt, PDO, prepared statements, attempt limiting |
| **URL Helper** | ✅ Excellent | Dynamic URL generation, no hardcoding |

### What Needs Improvement ⚠️

| Issue | Severity | Impact |
|-------|----------|--------|
| **Code Duplication** | 🔴 High | Maintenance nightmare, bugs in old versions |
| **production/ Directory** | 🟡 Medium | Deployment confusion, sync issues |
| **_archive/ Directory** | 🟢 Low | Dead code taking up space |
| **index.php Size** | 🟡 Medium | 1755 lines, too many responsibilities |
| **Documentation Spread** | 🟢 Low | Confusing for new developers |
| **User Files Pending** | 🟡 Medium | 30+ files not using new middleware |

---

## Detailed Findings

### 1. Code Duplication (HIGH PRIORITY)

#### user/dashboard/ Directory - 5 Versions Found:
```
user/dashboard/
├── enhanced_dashboard.php    ❌ DELETE
├── fixed_dashboard.php       ❌ DELETE
├── index.php                 ✅ KEEP (main)
├── main_dashboard.php        ❌ DELETE
├── my-account-test.php       ❌ DELETE
├── my-account.php            ❌ DELETE
└── router.php                ❌ DELETE
```

#### user/login/ Directory - 4 Versions Found:
```
user/login/
├── index.php                 ✅ KEEP (has CSRF, uses bootstrap)
├── index_new.php             ❌ DELETE
├── redirect_fix.php          ❌ DELETE
└── simple_login.php          ❌ DELETE
```

**Impact**: 6 unnecessary files creating confusion about which version is "live".

---

### 2. production/ Directory Issue (MEDIUM PRIORITY)

**Finding**: You have a `production/` directory that appears to be a duplicate deployment.

**Evidence**:
- `sync_production.php` script exists to sync dev → production
- `compare_environments.php` script to compare directories
- production/ contains duplicate versions of:
  - includes/bootstrap.php
  - includes/services/Services.php
  - includes/middleware/CsrfMiddleware.php
  - user/navigation-helper.php (old pattern)
  - admin/index.php (different content)

**Problem**: This is a **deployment anti-pattern**. You're manually syncing files instead of using git/deployment tools.

**Recommendation**: Delete production/ directory. Use git branching for deployment.

---

### 3. _archive/ Directory (LOW PRIORITY)

**Finding**: Contains old CodeIgniter 4 implementation.

**Contents**:
- app/Controllers/Admin.php
- app/Models/ (ProductModel, OrderModel, etc.)
- Complete CI4 structure

**Status**: This is dead code. Current system is standalone PHP.

**Recommendation**: Archive to external storage or delete if you're sure it's not needed.

---

### 4. index.php is Too Large (MEDIUM PRIORITY)

**Finding**: `index.php` is ~1755 lines.

**Current Responsibilities**:
1. Bootstrap loading
2. Route handling
3. POST request handling (registration, login, cart, checkout, etc.)
4. Admin authentication redirects
5. User authentication redirects
6. File inclusion logic
7. Home page rendering
8. Multiple form handlers

**Recommendation**: Split into:
```
index.php (~50 lines)          - Entry point only
route.php (~50 lines)          - Routing logic (exists)
includes/
├── handlers/
│   ├── AuthHandler.php        - Login/register/logout
│   ├── CartHandler.php        - Cart operations
│   ├── CheckoutHandler.php    - Checkout flow
│   └── ContactHandler.php     - Contact forms
```

---

### 5. Documentation Scattered (LOW PRIORITY)

**Finding**: Documentation files in multiple locations:

| Location | Files | Purpose |
|----------|-------|---------|
| Root/ | README.md, CLAUDE.md, agent.md | General docs |
| PRD/ | PRD.md, PRD_COMPREHENSIVE.md, current_tasks.md, etc. | Requirements |
| .claude/tasks/ | Multiple .md task files | Implementation plans |
| .claude/memory/ | MAJOR_REFACTORING.md, etc. | History |
| test_delete/ | PRODUCTION_DEPLOYMENT_CHECKLIST.md | Deployment docs |

**Recommendation**: Consolidate to:
```
docs/
├── README.md                    - Project overview
├── ARCHITECTURE.md              - System design
├── DEPLOYMENT.md                - Deployment guide
├── CONTRIBUTING.md              - Dev guidelines
└── CHANGELOG.md                 - Version history
```

---

### 6. Pending Middleware Updates (MEDIUM PRIORITY)

**Finding**: 30+ user files still using old patterns.

**Status**: According to USER_FILES_UPDATE_SUMMARY.md:
- ✅ 14 files updated (login, dashboard, profile, address-book, security-settings)
- ❌ 20+ files pending:
  - redeem-voucher, coupons-offers, credit-refunds
  - my-lists, create-list, newsletter-subscriptions
  - subscription-plan, reviews, support, help-centre
  - invoices, payment-history, payments-credit
  - returns, orders

**Impact**: Inconsistent authentication, missing CSRF protection in some areas.

---

### 7. Root Directory Cleanup (LOW PRIORITY)

**Files that shouldn't be in root**:
```
installer.php           ❌ Move to tools/ or delete
migrate.php             ❌ Move to tools/
seed_templates.php      ❌ Move to tools/
sync_production.php     ❌ Delete (use git)
compare_environments.php❌ Delete (use git)
debug_images.php        ❌ Delete
debug_id.php            ❌ Delete
fix_images.php          ❌ Delete
cookies.txt             ❌ Delete
composer.*              ❌ Delete if not used
phpunit.*               ❌ Keep but move to tests/
```

---

## Recommended Action Plan

### Phase 1: Critical Cleanup (Week 1)

#### 1.1 Delete Duplicate User Files
```bash
# From user/dashboard/
rm -f enhanced_dashboard.php fixed_dashboard.php main_dashboard.php
rm -f my-account-test.php my-account.php router.php

# From user/login/
rm -f index_new.php redirect_fix.php simple_login.php
```

#### 1.2 Delete Debug/Temp Files
```bash
# From root
rm -f debug_images.php debug_id.php fix_images.php
rm -f cookies.txt nul

# From test_delete/ (keep the SQL files for reference, move old tests)
# Move actual test files to tests/ directory
```

#### 1.3 Delete production/ Directory (After Confirming Git Setup)
```bash
# Make sure your git is tracking the main codebase first!
rm -rf production/
rm -f sync_production.php compare_environments.php
```

---

### Phase 2: Structure Improvements (Week 2)

#### 2.1 Split index.php

**New structure**:
```php
// index.php (entry point)
<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/route.php';

// Route to handlers
$handler = new RequestHandler($route);
$handler->handle();
```

```php
// includes/RequestHandler.php
class RequestHandler {
    public function handle(string $route): void {
        // Handle POST requests first
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($route);
            return;
        }

        // Handle GET routes
        $this->handleGet($route);
    }

    private function handlePost(string $route): void {
        CsrfMiddleware::validate();

        match($route) {
            'register' => (new AuthHandler())->register(),
            'login' => (new AuthHandler())->login(),
            'cart/add' => (new CartHandler())->add(),
            'cart/update' => (new CartHandler())->update(),
            'cart/remove' => (new CartHandler())->remove(),
            'checkout/process' => (new CheckoutHandler())->process(),
            default => null
        };
    }

    private function handleGet(string $route): void {
        // Existing file include logic
    }
}
```

#### 2.2 Create includes/handlers/ Directory
```
includes/handlers/
├── AuthHandler.php        - Registration, login, logout
├── CartHandler.php        - Add, update, remove from cart
├── CheckoutHandler.php    - Checkout process
├── ContactHandler.php     - Contact form submissions
└── RequestHandler.php     - Main router/dispatcher
```

---

### Phase 3: Complete User Middleware Updates (Week 3)

**Files to update** (use same pattern as completed files):

```php
// Pattern to apply:
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require auth for protected pages
AuthMiddleware::requireUser();

$db = Services::db();
$userAuth = Services::userAuth();
$currentUser = AuthMiddleware::getCurrentUser();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    // ... form handling
}

// ... rest of page
```

**Files to update**:
1. user/redeem-voucher/index.php
2. user/coupons-offers/index.php
3. user/credit-refunds/index.php
4. user/my-lists/index.php
5. user/create-list/index.php
6. user/newsletter-subscriptions/index.php
7. user/subscription-plan/index.php
8. user/reviews/index.php
9. user/support/index.php
10. user/help-centre/index.php
11. user/invoices/index.php
12. user/invoices/view.php (if exists)
13. user/payment-history/index.php
14. user/payments-credit/index.php
15. user/returns/index.php
16. user/orders/index.php
17. user/orders/view.php
18. user/orders/track.php (if exists)

---

### Phase 4: Documentation Consolidation (Week 4)

**Create docs/ directory structure**:

```
docs/
├── README.md                    - Project overview
├── ARCHITECTURE.md              - System design & patterns
├── DEPLOYMENT.md                - Production deployment
├── API.md                       - Internal API reference
├── CHANGELOG.md                 - Version history
├── CONTRIBUTING.md              - Dev guidelines
└── SECURITY.md                  - Security practices
```

**Move existing content**:
- CLAUDE.md → docs/CONTRIBUTING.md (extract dev guidelines)
- agent.md → docs/ARCHITECTURE.md
- PRD/ content → docs/ (relevant parts)
- test_delete/PRODUCTION_DEPLOYMENT_CHECKLIST.md → docs/DEPLOYMENT.md

---

### Phase 5: Archive Cleanup (Anytime)

**Option A: Delete _archive/** (if confident)
```bash
rm -rf _archive/
```

**Option B: Move to external backup**
```bash
mv _archive/ ../CannaBuddy_archive_backup/
```

---

## Recommended Directory Structure (Final State)

```
CannaBuddy.shop/
├── index.php                    (~50 lines - entry point only)
├── route.php                    (routing logic)
├── config.php                   (configuration)
│
├── includes/
│   ├── bootstrap.php            (core initialization)
│   ├── database.php             (Database class)
│   ├── url_helper.php           (URL helpers)
│   ├── session_helper.php       (Session helpers)
│   ├── header.php               (public header)
│   ├── footer.php               (public footer)
│   ├── admin_layout.php         (admin layout)
│   │
│   ├── services/
│   │   └── Services.php         (service container)
│   │
│   ├── middleware/
│   │   ├── AuthMiddleware.php   (authentication)
│   │   └── CsrfMiddleware.php   (CSRF protection)
│   │
│   ├── validation/
│   │   └── Validator.php        (input validation)
│   │
│   ├── handlers/
│   │   ├── RequestHandler.php   (main dispatcher)
│   │   ├── AuthHandler.php      (auth operations)
│   │   ├── CartHandler.php      (cart operations)
│   │   ├── CheckoutHandler.php  (checkout)
│   │   └── ContactHandler.php   (forms)
│   │
│   └── commerce/
│       └── CurrencyService.php  (currency handling)
│
├── admin/                       (admin panel - 20+ files)
│   ├── analytics.php
│   ├── categories.php
│   ├── coupons.php
│   ├── delivery-methods/
│   ├── login/
│   ├── orders/
│   ├── payment-methods/
│   ├── products/
│   ├── qr-codes/
│   ├── settings/
│   ├── slider/
│   ├── users/
│   └── vouchers.php
│
├── user/                        (customer dashboard)
│   ├── index.php                (redirect to dashboard/login)
│   ├── dashboard/index.php
│   ├── profile/index.php
│   ├── login/index.php
│   ├── logout/index.php
│   ├── forgot-password/index.php
│   ├── reset-password/index.php
│   ├── address-book/
│   ├── orders/
│   ├── security-settings/
│   └── [other features...]
│
├── shop/                        (product catalog)
├── cart/                        (shopping cart)
├── checkout/                    (checkout flow)
├── product/                     (product pages)
├── contact/                     (contact page)
├── register/                    (registration)
├── newsletter/                  (newsletter signup)
│
├── assets/
│   ├── images/
│   ├── css/
│   └── js/
│
├── migrations/                  (database migrations)
│
├── tests/                       (PHPUnit tests)
│
├── docs/                        (consolidated documentation)
│   ├── README.md
│   ├── ARCHITECTURE.md
│   ├── DEPLOYMENT.md
│   └── ...
│
└── tools/                       (dev tools - optional)
    ├── installer.php
    └── migrate.php
```

---

## Deployment Strategy (Replacing production/ Directory)

### Use Git Branches

```bash
# Main branches
main        (or "master")  - Production code
develop                     - Development work
feature/*                   - Feature branches

# Deployment flow
1. Develop on develop or feature/* branch
2. Test thoroughly
3. Merge to main
4. Deploy from main to production server
```

### Deployment Script (Replace sync_production.php)

```bash
#!/bin/bash
# deploy.sh - Deploy to production

# Variables
PROD_HOST="user@hostinger.com"
PROD_PATH="/public_html"
EXCLUDE="--exclude='.git' --exclude='tests/' --exclude='docs/'"

# Sync files
rsync -avz $EXCLUDE ./ $PROD_HOST:$PROD_PATH

echo "Deployment complete!"
```

---

## Security Checklist

### Already Implemented ✅
- [x] CSRF protection (CsrfMiddleware)
- [x] Bcrypt password hashing
- [x] PDO prepared statements
- [x] Login attempt limiting
- [x] Session fingerprinting
- [x] Input validation (Validator class)

### Consider Adding 🤔
- [ ] Content Security Policy headers
- [ ] X-Frame-Options headers
- [ ] Rate limiting for API endpoints
- [ ] File upload validation (if present)
- [ ] HTTPS enforcement

---

## Estimated Time & Effort

| Phase | Tasks | Estimated Time |
|-------|-------|----------------|
| Phase 1 | Delete duplicates, cleanup | 2-3 hours |
| Phase 2 | Split index.php, create handlers | 4-6 hours |
| Phase 3 | Update 18 user files | 6-8 hours |
| Phase 4 | Consolidate documentation | 3-4 hours |
| Phase 5 | Archive cleanup | 1 hour |
| **Total** | **All phases** | **16-22 hours** |

---

## Priority Matrix

| Priority | Phase | Impact | Effort | ROI |
|----------|-------|--------|--------|-----|
| 🔴 HIGH | Phase 1 | Eliminate confusion | Low | High |
| 🟡 MEDIUM | Phase 3 | Complete security/middleware coverage | Medium | High |
| 🟡 MEDIUM | Phase 2 | Improve maintainability | Medium | Medium |
| 🟢 LOW | Phase 4 | Better developer experience | Low | Medium |
| 🟢 LOW | Phase 5 | Disk space | Low | Low |

---

## Next Steps

### Immediate (This Week)
1. **Review this plan** - Approve or modify recommendations
2. **Backup everything** - Before deleting anything
3. **Start Phase 1** - Delete duplicate files

### This Month
1. Complete Phases 1-3
2. Run php-guardian smoke tests after each phase
3. Update CLAUDE.md with new patterns

### Ongoing
1. Keep docs/ updated
2. Follow CONTRIBUTING.md guidelines
3. Use git for deployment (no more production/ directory)

---

## Questions for You

1. **Do you still need the CodeIgniter code in _archive/?** If not, we can delete it.

2. **Are you using git for version control?** If yes, we can delete production/ and use git branches instead.

3. **What's your deployment process?** This will help me recommend the right strategy.

4. **Are any of the duplicate user files (dashboard versions, login versions) actually being used?** Or can we safely delete them?

5. **Do you want me to proceed with Phase 1 cleanup?** I can delete the duplicate files after you confirm.

---

**Ready to proceed?** Let me know which phase you'd like to start with, or if you have questions about any recommendation!
