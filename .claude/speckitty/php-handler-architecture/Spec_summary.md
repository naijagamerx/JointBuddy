# PHP Handler Architecture - Specification Summary

**Project**: CannaBuddy.shop E-Commerce Platform
**PHP Version**: 8.3.1
**Type**: Standalone PHP (No Framework)
**Date**: 2026-02-06

---

## Executive Summary

Refactor the monolithic `index.php` (~1750 lines) into a modular handler architecture. Extract POST request handlers into separate, testable classes following established codebase patterns.

**Estimated Effort**: 2h 42min (13 tasks across 6 phases)
**Risk Level**: LOW (Preserves all existing functionality)
**Impact**: HIGH (Improves maintainability, testability, code organization)

---

## What We're Building

### The Problem
- `index.php` is ~1750 lines with mixed concerns
- POST handlers are inline and difficult to test
- No separation between routing, business logic, and presentation

### The Solution
A modular handler architecture with:
- **HandlerInterface** - Common interface for all handlers
- **HandlerException** - Dedicated exception class
- **RequestHandler** - Central dispatcher for POST requests
- **AuthHandler** - Authentication operations (register, login, logout)
- **WishlistHandler** - Wishlist operations (add, remove)

### The Result
- `index.php` reduced to <500 lines
- POST handlers in separate, testable classes
- Consistent error handling across all handlers
- Easy to add new handlers in the future

---

## Architecture Overview

```
index.php → RequestHandler → AuthHandler / WishlistHandler
           (Dispatcher)       (Implement HandlerInterface)
```

**Key Components**:

| Component | Purpose | File |
|-----------|---------|------|
| HandlerInterface | Common contract for all handlers | `includes/handlers/HandlerInterface.php` |
| HandlerException | Handler-specific exceptions | `includes/handlers/HandlerException.php` |
| RequestHandler | Central dispatcher | `includes/handlers/RequestHandler.php` |
| AuthHandler | Register, login, logout | `includes/handlers/AuthHandler.php` |
| WishlistHandler | Wishlist add/remove | `includes/handlers/WishlistHandler.php` |

---

## Requirements At A Glance

### Functional Requirements (10)
1. **Handler Interface** - Common contract for all handlers
2. **Handler Exception** - Dedicated exception with context
3. **RequestHandler** - Central dispatcher with CSRF validation
4. **AuthHandler** - Handle auth operations (register, login, logout)
5. **WishlistHandler** - Handle wishlist operations (add, remove)
6. **index.php Integration** - Refactor to use handlers
7. **Bootstrap Integration** - Work with existing bootstrap system
8. **Error Handling** - Consistent try-catch with logging
9. **Security** - CSRF, prepared statements, auth checks
10. **Backward Compatibility** - No breaking changes

### Non-Functional Requirements (5)
1. **Performance** - Handler dispatch < 5ms overhead
2. **Maintainability** - Classes < 300 lines, methods < 50 lines
3. **Testability** - Dependency injection via Services
4. **Code Quality** - PSR-12, PHP 8.3+ type hints
5. **Documentation** - PHPDoc on all public methods

---

## Implementation Phases

| Phase | Tasks | Time | Priority |
|-------|-------|------|----------|
| **1. Infrastructure** | Create directory, interface, exception | 12 min | 🔴 HIGH |
| **2. AuthHandler** | Create and test auth handler | 45 min | 🔴 HIGH |
| **3. WishlistHandler** | Create and test wishlist handler | 30 min | 🟡 MEDIUM |
| **4. RequestHandler** | Create and test dispatcher | 30 min | 🔴 HIGH |
| **5. Integration** | Update index.php, test integration | 30 min | 🔴 HIGH |
| **6. Verification** | Syntax checks, smoke test | 15 min | 🔴 HIGH |
| **TOTAL** | **13 tasks** | **2h 42min** | |

---

## Key Design Decisions

### 1. Static vs Instance Methods
**Decision**: Instance methods for handlers with state (DB access), static could work but instance is more testable.

### 2. Handler Registration
**Decision**: Manual registration in `RequestHandler::__construct()` - simple, predictable order.

### 3. Error Handling
**Decision**: Try-catch in handlers, log errors, set session messages for display.

### 4. CSRF Validation
**Decision**: Centralized in `RequestHandler::handlePost()` - all POST requests validated.

### 5. Authentication
**Decision**: Use existing `AuthMiddleware` and `Services` container - no new patterns.

---

## Integration Points

### With Existing Code

| Existing Component | How Handlers Use It |
|--------------------|---------------------|
| `bootstrap.php` | Loaded before handlers in index.php |
| `Services::db()` | Database access in handlers |
| `Services::userAuth()` | User authentication operations |
| `Services::adminAuth()` | Admin authentication operations |
| `AuthMiddleware` | Authentication checks in handlers |
| `CsrfMiddleware` | CSRF validation in dispatcher |
| `url()` helpers | Redirects and URL generation |
| `session_helper.php` | Flash messages |

### No Changes Required To
- Routing system (route.php)
- GET request handling
- File-based routing
- Session structure
- Database schema

---

## File Changes Summary

### New Files (5)
```
includes/handlers/HandlerInterface.php    (~30 lines)
includes/handlers/HandlerException.php    (~40 lines)
includes/handlers/AuthHandler.php         (~150 lines)
includes/handlers/WishlistHandler.php     (~120 lines)
includes/handlers/RequestHandler.php      (~80 lines)
```

### Modified Files (1)
```
index.php                                  (~420 lines, down from ~1750)
```

### Files Unchanged
- All admin files
- All user files
- All routing (route.php)
- All middleware
- All services

---

## Success Criteria

✅ **All acceptance criteria met** (see tasks.md for details)

✅ **index.php < 500 lines**

✅ **All POST operations working**:
- User registration
- User login
- Admin logout
- Wishlist add (AJAX)
- Wishlist remove (AJAX)

✅ **All GET routes working** (unchanged)

✅ **No PHP errors** in logs

✅ **No breaking changes** to existing URLs

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Breaking existing URLs | LOW | HIGH | Comprehensive testing, git rollback |
| Performance regression | LOW | MEDIUM | Benchmark before/after, optimize if needed |
| CSRF validation issues | LOW | HIGH | Test all POST operations |
| Session issues | LOW | MEDIUM | Test auth flow thoroughly |
| Database errors | LOW | MEDIUM | Prepared statements, try-catch |

---

## Next Steps

1. **Review this specification** - Ensure all requirements are understood
2. **Approve implementation plan** - Confirm tasks.md approach
3. **Begin Phase 1** - Create infrastructure (interface, exception)
4. **Proceed through phases** - Complete tasks in order
5. **Verify and test** - Ensure all acceptance criteria met

---

## Questions?

Before implementation begins, clarify:

1. **Handler Extensibility** - Should we add other handlers now (CartHandler, etc.) or defer?
   - **Recommendation**: Defer - get auth and wishlist working first

2. **Testing Approach** - Should we create unit tests now or later?
   - **Recommendation**: Later - focus on implementation first

3. **Documentation** - Should handlers have inline usage examples?
   - **Recommendation**: Yes - in PHPDoc comments

---

**Specification Version**: 1.0
**Status**: ✅ Ready for Implementation
**Created**: 2026-02-06

---

## Files Generated

1. `requirements.md` - Detailed functional and non-functional requirements
2. `design.md` - Architecture, class hierarchy, method signatures
3. `tasks.md` - 13 implementation tasks with acceptance criteria
4. `Spec_summary.md` - This file (executive overview)

---

*End of Specification*
