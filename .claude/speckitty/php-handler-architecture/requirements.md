# PHP Handler Architecture - Requirements

**Project**: CannaBuddy.shop E-Commerce Platform
**PHP Version**: 8.3.1
**Type**: Standalone PHP (No Framework)
**Date**: 2026-02-06

---

## Overview

Refactor the monolithic `index.php` (~1750 lines) into a modular handler architecture. Extract POST request handlers into separate, testable classes following established codebase patterns.

---

## Functional Requirements

### RQ-1: Handler Interface
**Priority**: HIGH
**Description**: All handlers must implement a common interface for consistency.

**Requirements**:
- Create `HandlerInterface` with methods:
  - `canHandle(string $route, array $request): bool` - Check if handler can process the route
  - `handle(string $route, array $request): void` - Process the request
- Interface must be in `includes/handlers/HandlerInterface.php`

**Acceptance Criteria**:
- [ ] Interface defined with type hints
- [ ] All handler classes implement the interface
- [ ] PHP 8.3+ return types declared

### RQ-2: Handler Exception
**Priority**: HIGH
**Description**: Dedicated exception class for handler-specific errors.

**Requirements**:
- Create `HandlerException` extending `Exception`
- Include optional `$context` array for debugging
- File: `includes/handlers/HandlerException.php`

**Acceptance Criteria**:
- [ ] Exception class created
- [ ] `getContext()` method returns context array
- [ ] Used in all handlers for error reporting

### RQ-3: RequestHandler (Dispatcher)
**Priority**: HIGH
**Description**: Central dispatcher that routes POST requests to appropriate handlers.

**Requirements**:
- Create `RequestHandler` class in `includes/handlers/RequestHandler.php`
- Constructor registers all available handlers
- `handlePost(string $route): bool` method:
  - Validates CSRF using `CsrfMiddleware::validate()`
  - Aggregates POST, GET, FILES into `$request` array
  - Iterates through handlers to find matching handler
  - Calls `handle()` on matching handler
  - Returns `true` if handled, `false` if no handler found
- Logs errors via `error_log()`

**Acceptance Criteria**:
- [ ] Class created with handler registration
- [ ] CSRF validation integrated
- [ ] Request aggregation works (POST + GET + FILES)
- [ ] Handlers are called in registration order
- [ ] Returns boolean indicating if request was handled
- [ ] Errors logged with context

### RQ-4: AuthHandler
**Priority**: HIGH
**Description**: Handle authentication operations (register, login, logout).

**Requirements**:
- Create `AuthHandler` in `includes/handlers/AuthHandler.php`
- Implements `HandlerInterface`
- Routes handled: `register`, `user/login`, `admin/logout`
- Methods:
  - `canHandle()` - Match auth routes
  - `handle()` - Route to specific handler method
  - `handleRegister()` - Process user registration
  - `handleUserLogin()` - Process user login
  - `handleAdminLogout()` - Process admin logout
- Uses `Services::userAuth()` and `Services::adminAuth()`
- Session messages stored in `$_SESSION`
- Redirects using `url()` and `adminUrl()` helpers

**Acceptance Criteria**:
- [ ] All three auth routes handled correctly
- [ ] Registration stores success/error in session
- [ ] Login redirects to `/user/` on success
- [ ] Admin logout redirects to `/admin/login/`
- [ ] Uses Services container for auth classes
- [ ] No hardcoded URLs

### RQ-5: WishlistHandler
**Priority**: MEDIUM
**Description**: Handle wishlist operations (add/remove products).

**Requirements**:
- Create `WishlistHandler` in `includes/handlers/WishlistHandler.php`
- Implements `HandlerInterface`
- Routes handled: `wishlist/add`, `wishlist/remove`
- Returns JSON responses (Content-Type: application/json)
- Checks authentication via `AuthMiddleware::getCurrentUser()`
- Database operations via `Services::db()`
- Duplicate check before insert
- Response format: `{'success': bool, 'message': string}`

**Acceptance Criteria**:
- [ ] Add to wishlist checks for duplicates
- [ ] Remove from wishlist works correctly
- [ ] Returns JSON with proper Content-Type header
- [ ] Requires user authentication
- [ ] Uses prepared statements for all queries
- [ ] Error handling returns JSON error messages

### RQ-6: index.php Integration
**Priority**: HIGH
**Description**: Refactor index.php to use the new handler system.

**Requirements**:
- Add handler includes after bootstrap (lines 9-14 approx)
- Replace lines 22-101 (POST handling) with RequestHandler call
- Keep all existing GET routing and file includes
- No breaking changes to existing URLs
- Reduce index.php to <500 lines

**Acceptance Criteria**:
- [ ] index.php compiles without syntax errors
- [ ] All existing POST operations work (register, login, wishlist)
- [ ] All existing GET routes work (admin, user, product, shop)
- [ ] Line count reduced to <500
- [ ] No hardcoded URLs

### RQ-7: Bootstrap Integration
**Priority**: HIGH
**Description**: Handlers must integrate with existing bootstrap system.

**Requirements**:
- Handlers loaded via `require_once` in index.php
- Services initialized via `Services::initialize()`
- Middleware accessible (`AuthMiddleware`, `CsrfMiddleware`)
- No duplicate includes (handlers check for existing classes)

**Acceptance Criteria**:
- [ ] Bootstrap loads before handlers
- [ ] Services available in handlers
- [ ] No class redeclaration errors
- [ ] Handlers work with existing middleware

### RQ-8: Error Handling
**Priority**: MEDIUM
**Description**: Consistent error handling across all handlers.

**Requirements**:
- All database operations wrapped in try-catch
- Errors logged via `error_log()`
- User-friendly messages in responses
- Sensitive details never exposed to users
- Session error messages for failed operations

**Acceptance Criteria**:
- [ ] Database errors caught and logged
- [ ] User sees generic error messages
- [ ] Error messages set in session for display
- [ ] No stack traces exposed to users

### RQ-9: Security
**Priority**: HIGH
**Description**: Maintain security standards during refactoring.

**Requirements**:
- CSRF validation on all POST requests (via RequestHandler)
- Prepared statements for all database queries
- Input validation before database operations
- Output sanitization (htmlspecialchars) where needed
- Authentication checks for protected operations

**Acceptance Criteria**:
- [ ] All POST requests validate CSRF token
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Wishlist operations require authentication
- [ ] Admin operations require authentication

### RQ-10: Backward Compatibility
**Priority**: HIGH
**Description**: No breaking changes to existing functionality.

**Requirements**:
- All existing URLs continue to work
- Session structure unchanged
- Response formats unchanged
- Redirects go to same destinations
- Form submissions work as before

**Acceptance Criteria**:
- [ ] User registration works
- [ ] User login works
- [ ] Admin logout works
- [ ] Wishlist add/remove works (AJAX)
- [ ] No 404 errors on existing routes

---

## Non-Functional Requirements

### NFR-1: Performance
- Handler dispatch overhead < 5ms
- No additional database queries introduced
- Handler registration happens once at startup

### NFR-2: Maintainability
- Each handler class < 300 lines
- Methods < 50 lines
- Clear separation of concerns
- PHPDoc comments on all public methods

### NFR-3: Testability
- Handlers use dependency injection (Services)
- No global state in handler logic
- Static methods only where stateless
- Return values consistent and predictable

### NFR-4: Code Quality
- PSR-12 coding standards followed
- PHP 8.3+ type hints on all methods
- Nullable types explicitly declared
- No mixed return types

### NFR-5: Documentation
- PHPDoc on all classes and public methods
- Comments explain complex logic
- Examples in class PHPDoc
- Integration guide in codebase documentation

---

## Constraints

### C-1: No Framework
- Must use standalone PHP patterns
- No Laravel, Symfony, or other frameworks
- Follow existing codebase conventions

### C-2: No Breaking Changes
- All existing URLs must work
- Session structure unchanged
- Database schema unchanged

### C-3: File Locations
- Handlers in `includes/handlers/`
- Must work with existing bootstrap
- No changes to routing system

### C-4: PHP Version
- Target PHP 8.3.1
- Use PHP 8.0+ features (union types, null safe operator, etc.)

---

## Dependencies

### External
- PHP 8.3.1+
- MySQL 5.7+ (via PDO)
- No external packages

### Internal
- `includes/bootstrap.php` - Core initialization
- `includes/middleware/AuthMiddleware.php` - Authentication
- `includes/middleware/CsrfMiddleware.php` - CSRF protection
- `includes/services/Services.php` - Service container
- `includes/session_helper.php` - Session management
- `includes/url_helper.php` - URL generation
- `includes/validation/Validator.php` - Input validation

---

## Out of Scope

The following are explicitly out of scope for this specification:

- Cart operations (already in separate files)
- Checkout operations (already in separate files)
- Product operations (admin CRUD)
- Order management
- User profile management
- Email notifications
- File uploads
- API versioning
- Handler caching
- Middleware chains beyond auth/CSRF

---

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Code Reduction | index.php < 500 lines | wc -l index.php |
| POST Operations | All working | Manual testing |
| Test Coverage | Handlers testable | Code review |
| Performance | < 5ms overhead | Benchmark |
| Security | No vulnerabilities | Security scan |
| Compatibility | 100% URLs work | Smoke test |

---

*Version: 1.0*
*Status: Ready for Implementation*
