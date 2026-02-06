# PHPUnit Test Suite Implementation Plan

## Overview
Create comprehensive PHPUnit tests for the refactored CannaBuddy codebase to ensure code quality, prevent regressions, and document expected behavior.

## Requirements Analysis

### Core Files to Test
1. **session_helper.php** - Session management functions
2. **services/Services.php** - Service container (singleton pattern)
3. **middleware/AuthMiddleware.php** - Authentication checks
4. **middleware/CsrfMiddleware.php** - CSRF protection
5. **validation/Validator.php** - Input validation and sanitization
6. **url_helper.php** - URL generation and helpers

### Testing Constraints
- PHP 8.3.1 environment
- PHPUnit 10+ syntax
- Must mock PDO for database operations
- Must mock session for session tests
- Must handle $_SERVER, $_POST, $_GET superglobals
- Type hints required (strict_types=1)

## Implementation Plan

### Phase 1: Test Infrastructure Setup
1. Create tests directory structure
2. Create phpunit.xml configuration file
3. Create base test classes with common setup/teardown
4. Create test doubles for:
   - PDO mock
   - Session mock
   - Auth classes mock
   - Server environment mock

### Phase 2: SessionHelperTest.php
Test functions from session_helper.php:
- testEnsureSessionStarted - Verify session starts once
- testSessionFlash - Set and retrieve flash messages
- testSessionGetFlash - Get and clear flash
- testRegenerateSession - Session ID regeneration
- testDestroySession - Complete session cleanup
- testSessionSet - Set session variables
- testSessionGet - Get session variables with defaults
- testSessionRemove - Remove session variables
- testSessionHasFlash - Check flash message existence

### Phase 3: ServicesTest.php
Test Services singleton container:
- testInitialize - Services initialize once
- testDbReturnsSingleton - Same PDO instance
- testAdminAuthReturnsSingleton - Same AdminAuth instance
- testUserAuthReturnsSingleton - Same UserAuth instance
- testDatabaseReturnsSingleton - Same Database instance
- testCurrencyServiceReturnsSingleton - Same CurrencyService instance
- testReset - Clear all services
- testIsInitialized - Check initialization state

### Phase 4: AuthMiddlewareTest.php
Test authentication middleware:
- testRequireAdminRedirectsWhenNotLoggedIn
- testRequireAdminAllowsWhenLoggedIn
- testRequireUserRedirectsWhenNotLoggedIn
- testRequireUserAllowsWhenLoggedIn
- testIsAdminLoggedIn - Returns true/false
- testIsUserLoggedIn - Returns true/false
- testGetCurrentAdmin - Returns admin data or null
- testGetCurrentUser - Returns user data or null
- testGetAdminId - Returns ID or null
- testGetUserId - Returns ID or null
- testRequireAdminRole - Role-based access
- testAdminHasRole - Role checking
- testAdminHasAnyRole - Multiple role checking

### Phase 5: CsrfMiddlewareTest.php
Test CSRF protection:
- testGetTokenGeneratesToken - Token generation
- testGetFieldReturnsHtml - HTML field generation
- testIsValidAcceptsValidToken - Valid token passes
- testIsValidRejectsInvalidToken - Invalid token fails
- testValidateOnPostRequest - POST validation
- testValidateSkipsGetRequest - GET bypassed
- testRegenerate - Token regeneration
- testGetAjaxHeader - AJAX header format
- testExempt - Route exemption
- testIsExempt - Exemption checking

### Phase 6: ValidatorTest.php
Test input validation:
- testStringValidation - Basic string validation
- testStringRequired - Required string
- testStringMaxLength - Max length enforcement
- testStringSanitization - HTML escaping
- testEmailValidation - Email format
- testEmailRequired - Required email
- testIntegerValidation - Integer conversion
- testIntegerMin - Minimum value
- testIntegerMax - Maximum value
- testPriceValidation - Price/decimal validation
- testPriceNegative - Reject negative prices
- testSlugValidation - Slug format
- testBooleanValidation - Boolean conversion
- testArrayValidation - Array type checking
- testDateValidation - Date format
- testEnumValidation - Enum values
- testTextValidation - Long text validation
- testPhoneValidation - Phone number format
- testFileValidation - File upload checks
- testHtmlValidation - HTML sanitization
- testValidateMethod - Bulk validation
- testValidationException - Exception handling

### Phase 7: UrlHelperTest.php
Test URL generation:
- testUrlGeneratesCorrectUrl - Full URL with protocol
- testUrlWithHttps - HTTPS detection
- testUrlWithPath - Path concatenation
- testRurlGeneratesRelativeUrl - Relative paths
- testRurlWithBasePath - Base path inclusion
- testAdminUrlGeneratesAdminUrl - Admin prefix
- testUserUrlGeneratesUserUrl - User prefix
- testShopUrlGeneratesShopUrl - Shop prefix
- testProductUrlGeneratesProductUrl - Product slug
- testAssetUrlGeneratesAssetUrl - Asset paths
- testAssetPathGeneratesPath - Database path format
- testSafeHtmlEscaping - HTML escaping
- testValidateRedirectAllowed - Valid redirects
- testValidateRedirectBlocked - Blocked redirects
- testCsrfTokenGeneration - CSRF token creation
- testCsrfFieldGeneration - HTML field
- testVerifyCsrfTokenValid - Valid token
- testVerifyCsrfTokenInvalid - Invalid token
- testCsrfRegenerate - Token regeneration

### Phase 8: Documentation
1. Create test documentation in tests/README.md
2. Document how to run tests
3. Document test coverage
4. Create testing guidelines

## Testing Patterns

### Mock Objects
```php
// PDO Mock
$pdo = $this->createMock(PDO::class);

// Auth Mock
$auth = $this->createMock(AdminAuth::class);
$auth->method('isLoggedIn')->willReturn(true);
```

### Session Simulation
```php
// Start session before tests
protected function setUp(): void {
    @session_start();
}

// Clean up after tests
protected function tearDown(): void {
    $_SESSION = [];
    session_destroy();
}
```

### Server Environment Mock
```php
$_SERVER['REQUEST_URI'] = '/admin/dashboard/';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'localhost';
```

### POST Data Mock
```php
$_POST['csrf_token'] = 'valid_token';
$_POST['email'] = 'test@example.com';
```

## Success Criteria

### Coverage Targets
- SessionHelper: 100% coverage
- Services: 95%+ coverage
- AuthMiddleware: 90%+ coverage
- CsrfMiddleware: 95%+ coverage
- Validator: 95%+ coverage
- UrlHelper: 90%+ coverage

### Quality Standards
- All tests pass independently
- Tests are isolated (no shared state)
- Tests are readable and maintainable
- Tests document expected behavior
- Tests run quickly (< 5 seconds total)

## Implementation Progress

- [x] **Setup** - Created directory structure and config
- [x] **Base Classes** - Created test base classes
- [x] **Session Tests** - Implemented SessionHelperTest (19 tests, 16 passing, 3 skipped)
- [x] **Services Tests** - Implemented ServicesTest (pending run)
- [x] **Auth Tests** - Implemented AuthMiddlewareTest (pending run)
- [x] **CSRF Tests** - Implemented CsrfMiddlewareTest (pending run)
- [x] **Validator Tests** - Implemented ValidatorTest (pending run)
- [x] **URL Tests** - Implemented UrlHelperTest (pending run)
- [x] **Documentation** - Created README
- [ ] **Verification** - Run all tests, ensure 100% pass rate

### Completed Work

#### Files Created
1. `tests/phpunit.xml` - PHPUnit configuration
2. `tests/bootstrap.php` - Test bootstrap with session handling
3. `tests/base/TestCase.php` - Base test class
4. `tests/base/MockPDO.php` - PDO mock factory
5. `tests/SessionHelperTest.php` - 19 tests for session functions
6. `tests/ServicesTest.php` - Tests for service container
7. `tests/AuthMiddlewareTest.php` - Tests for authentication middleware
8. `tests/CsrfMiddlewareTest.php` - Tests for CSRF protection
9. `tests/ValidatorTest.php` - Tests for input validation
10. `tests/UrlHelperTest.php` - Tests for URL helpers
11. `tests/README.md` - Test documentation

#### Code Changes Made
- Updated `includes/session_helper.php` to handle headers already sent scenario for testing
- Added checks for `headers_sent()` before `session_set_cookie_params()` and `session_start()`
- This allows tests to run in CLI environment where headers are already sent

#### Test Results
- **SessionHelperTest**: 19 tests, 16 passing, 3 skipped (due to headers sent in CLI)
- **Other tests**: Pending execution

### Known Issues and Solutions

1. **Headers Already Sent**: PHPUnit outputs headers before tests run
   - Solution: Added `headers_sent()` checks in session helper
   - Tests that require session modification are skipped in CLI

2. **Missing Dependencies**: CurrencyService not loaded
   - Solution: Added to bootstrap.php includes

3. **Property Name Conflicts**: PHPUnit's $backupGlobals conflicted with test properties
   - Solution: Renamed to $stateBackup, $sessionStateBackup, $globalStateBackup

## Files to Create

```
tests/
├── phpunit.xml
├── README.md
├── base/
│   ├── TestCase.php (base class with common setup)
│   ├── MockPDO.php (PDO mock factory)
│   └── MockSession.php (Session simulation)
├── SessionHelperTest.php
├── ServicesTest.php
├── AuthMiddlewareTest.php
├── CsrfMiddlewareTest.php
├── ValidatorTest.php
└── UrlHelperTest.php
```

## Testing Commands

```bash
# Run all tests
vendor/bin/phpunit tests/

# Run specific test file
vendor/bin/phpunit tests/SessionHelperTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage tests/

# Run specific test method
vendor/bin/phpunit --filter testEnsureSessionStarted tests/SessionHelperTest.php
```

## Notes

- All test files must use `declare(strict_types=1);`
- Use PHPUnit 10+ syntax (no extends PHPUnit_Framework_TestCase)
- Use type hints on all methods
- Use setUp()/tearDown() (not setUpBeforeClass/tearDownAfterClass unless needed)
- Mock external dependencies (PDO, auth classes)
- Test both success and failure cases
- Test edge cases (empty values, null, invalid types)
- Use data providers for multiple similar test cases
- Add descriptive test names that explain what is being tested
