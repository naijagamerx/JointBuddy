# CannaBuddy.shop Codebase Refactoring Plan

**Project:** CannaBuddy E-Commerce Platform
**Date:** 2026-01-17
**Status:** PLANNING
**Estimated Duration:** 6-8 weeks
**Priority:** CRITICAL - Security & Production Readiness

---

## Executive Summary

This plan addresses **87 code quality issues** identified in the comprehensive code analysis. The refactoring will transform the codebase from a functional but inconsistent state into a production-ready, secure, and maintainable e-commerce platform.

### Current State
- 126 PHP files across admin (47), user (37), includes (42)
- 12 Critical security issues
- 23 High priority code quality issues
- 31 Medium priority consistency issues
- Mixed patterns throughout the codebase

### Target State
- Secure, CSRF-protected forms
- Consistent coding patterns (PSR-12 compliant)
- Centralized authentication and error handling
- Modular, maintainable codebase
- Production-ready deployment

---

## Project Goals

### Security Goals
1. **Zero hardcoded URLs** - All URLs use helper functions
2. **100% CSRF protection** - All POST handlers validate tokens
3. **Consistent authentication** - Middleware-based auth checks
4. **Input validation** - All user inputs validated and sanitized

### Code Quality Goals
1. **DRY compliance** - No duplicated database/auth code
2. **Single Responsibility** - index.php split into controllers
3. **Error handling** - Consistent error handling patterns
4. **PSR-12 standards** - Consistent code style throughout

### Maintainability Goals
1. **Service container** - Single source for database/auth instances
2. **Middleware layer** - Centralized request processing
3. **Configuration system** - Environment-based configuration
4. **Developer experience** - Clear patterns, easy to extend

---

## Refactoring Phases

### Phase 1: Critical Security Fixes (Week 1-2)
**Focus:** Immediate security vulnerabilities and deployment blockers

| Task | Files Affected | Risk | Priority |
|------|---------------|------|----------|
| Fix hardcoded URLs | 40+ files | HIGH | CRITICAL |
| Add CSRF protection | 15+ forms | HIGH | CRITICAL |
| Standardize session handling | 35+ files | MEDIUM | CRITICAL |
| Fix authentication bypass | 25+ files | HIGH | CRITICAL |

### Phase 2: Infrastructure & Architecture (Week 3-4)
**Focus:** Core services and architectural improvements

| Task | Impact | Complexity |
|------|--------|------------|
| Create service container | All files | MEDIUM |
| Implement middleware | All routes | HIGH |
| Standardize error handling | All files | MEDIUM |
| Add input validation layer | All forms | HIGH |

### Phase 3: Code Organization (Week 5-6)
**Focus:** Code structure and maintainability

| Task | Impact | Complexity |
|------|--------|------------|
| Split index.php | Core file | HIGH |
| Extract controllers | 10+ controllers | MEDIUM |
| Create configuration system | All files | LOW |
| Standardize include paths | All files | LOW |

### Phase 4: Quality & Testing (Week 7-8)
**Focus:** Code quality, testing, and documentation

| Task | Impact | Complexity |
|------|--------|------------|
| Add type hints | All functions | LOW |
| Implement PSR-12 | All files | MEDIUM |
| Write unit tests | Critical paths | HIGH |
| Update documentation | All changes | MEDIUM |

---

## Key Architectural Changes

### 1. Service Container Pattern
**Current:** Database/Auth code repeated in every file
```php
// Repeated 50+ times
try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}
```

**Target:** Single service container
```php
// One line in any file
$db = Services::db();
$adminAuth = Services::adminAuth();
```

### 2. Middleware Authentication
**Current:** Three different auth patterns
```php
// Pattern A: Inline check
if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

// Pattern B: Separate file
require_once 'admin_auth_check.php';

// Pattern C: Array-based in index.php
$isAdminRoute = in_array($route, [...]);
```

**Target:** Consistent middleware
```php
// All admin files
require_once __DIR__ . '/../../includes/bootstrap.php';
AuthMiddleware::requireAdmin();
```

### 3. Unified Session Handling
**Current:** Inconsistent session initialization
```php
// Pattern A
session_start();

// Pattern B
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pattern C
// Started in index.php, no check needed
```

**Target:** Single session helper
```php
// One function everywhere
ensureSessionStarted();
```

### 4. Controller-Based Routing
**Current:** 1757-line index.php doing everything
```php
// index.php contains:
// - Routing logic
// - HTML generation (700+ lines)
// - Authentication
// - Session management
// - POST handlers
// - Admin dashboard
// - Order management
```

**Target:** Separated concerns
```
includes/
├── bootstrap.php           # Initialization
├── middleware/
│   ├── AuthMiddleware.php  # Authentication
│   └── CsrfMiddleware.php  # CSRF protection
├── controllers/
│   ├── HomeController.php   # Homepage
│   ├── AdminController.php # Admin routes
│   └── ApiController.php   # AJAX handlers
└── services/
    └── Services.php         # Service container
```

---

## Risk Assessment

### High-Risk Changes
| Change | Risk | Mitigation |
|--------|------|------------|
| Splitting index.php | Breaking routes | Comprehensive testing |
| Service container | Breaking existing code | Gradual migration |
| Authentication changes | Locking out users | Staged rollout |

### Medium-Risk Changes
| Change | Risk | Mitigation |
|--------|------|------------|
| CSRF protection | Breaking forms | Backward compatible |
| URL helper changes | Broken links | Search and replace |
| Error handling | Hidden errors | Logging phase |

---

## Testing Strategy

### Unit Tests
- Database class methods
- Authentication methods
- Validation functions
- Service container

### Integration Tests
- Admin login flow
- User registration/login
- Product CRUD operations
- Order creation workflow
- CSRF token validation

### Regression Tests
- All existing functionality
- Homepage rendering
- Admin panel access
- User dashboard
- Checkout process

---

## Success Criteria

### Phase 1 Success (Week 2)
- [ ] All hardcoded URLs replaced with helpers
- [ ] All forms have CSRF protection
- [ ] Session handling consistent
- [ ] Authentication unified

### Phase 2 Success (Week 4)
- [ ] Service container operational
- [ ] Middleware implemented
- [ ] Error handling consistent
- [ ] Input validation added

### Phase 3 Success (Week 6)
- [ ] index.php split (<200 lines)
- [ ] Controllers created
- [ ] Configuration system active
- [ ] Include paths standardized

### Phase 4 Success (Week 8)
- [ ] PHPUnit tests passing
- [ ] PSR-12 compliance verified
- [ ] Documentation updated
- [ ] Production deployment ready

---

## Rollback Strategy

Each phase will be deployed to a branch for testing:
- `phase1-security-fixes`
- `phase2-infrastructure`
- `phase3-refactoring`
- `phase4-quality`

If issues arise:
1. Revert to previous working branch
2. Fix issues in isolation
3. Re-test before merging

---

## Resource Requirements

### Development Time
- 6-8 weeks of focused development
- 1-2 developers recommended
- Code review time: 20% of development

### Testing Time
- Unit tests: Ongoing during development
- Integration tests: End of each phase
- Full regression: Before each phase merge

### Deployment Considerations
- Staging environment required
- Database backup before deployment
- Rollback plan tested
- Monitoring for 48 hours post-deployment

---

## Next Steps

1. **Review this plan** - Ensure all stakeholders agree
2. **Create implementation_plan.md** - Detailed step-by-step guide
3. **Set up staging** - Prepare testing environment
4. **Begin Phase 1** - Start with critical security fixes

---

**Plan Version:** 1.0
**Last Updated:** 2026-01-17
**Status:** AWAITING APPROVAL
