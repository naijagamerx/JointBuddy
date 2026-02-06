# PHP Handler Architecture - Implementation Tasks

**Project**: CannaBuddy.shop E-Commerce Platform
**PHP Version**: 8.3.1
**Type**: Standalone PHP (No Framework)

---

## Task Breakdown

### Phase 1: Infrastructure (15 minutes)

#### Task 1.1: Create Handlers Directory
**File**: `includes/handlers/` (new directory)
**Priority**: HIGH
**Effort**: 2 minutes

```bash
mkdir -p includes/handlers
```

**Acceptance**:
- [ ] Directory created
- [ ] Writeable by PHP process

#### Task 1.2: Create HandlerInterface
**File**: `includes/handlers/HandlerInterface.php` (new)
**Priority**: HIGH
**Effort**: 5 minutes

```php
<?php
/**
 * Handler Interface
 * All request handlers must implement this interface
 *
 * @package CannaBuddy
 */
interface HandlerInterface {
    /**
     * Check if this handler can process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data
     * @return bool True if handler can process this route
     */
    public function canHandle(string $route, array $request): bool;

    /**
     * Process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data
     * @return void
     * @throws HandlerException If processing fails
     */
    public function handle(string $route, array $request): void;
}
```

**Acceptance**:
- [ ] File created
- [ ] Interface defines both methods
- [ ] PHPDoc complete
- [ ] No syntax errors (`php -l`)

#### Task 1.3: Create HandlerException
**File**: `includes/handlers/HandlerException.php` (new)
**Priority**: HIGH
**Effort**: 5 minutes

```php
<?php
/**
 * Handler Exception
 * Thrown when handler processing fails
 *
 * @package CannaBuddy
 */
class HandlerException extends Exception {
    protected ?array $context = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get debug context
     *
     * @return array|null Context data or null
     */
    public function getContext(): ?array {
        return $this->context;
    }
}
```

**Acceptance**:
- [ ] File created
- [ ] Extends Exception
- [ ] Context property and getter
- [ ] No syntax errors

---

### Phase 2: AuthHandler (45 minutes)

#### Task 2.1: Create AuthHandler Class
**File**: `includes/handlers/AuthHandler.php` (new)
**Priority**: HIGH
**Effort**: 30 minutes

**Key Methods**:
- `canHandle(string $route, array $request): bool`
- `handle(string $route, array $request): void`
- `handleRegister(?UserAuth, array $request): void`
- `handleUserLogin(?UserAuth, array $request): void`
- `handleAdminLogout(?AdminAuth): void`

**Acceptance**:
- [ ] Implements HandlerInterface
- [ ] Routes: register, user/login, admin/logout
- [ ] Uses Services::userAuth() and Services::adminAuth()
- [ ] Session messages set correctly
- [ ] Redirects use url() helpers
- [ ] No hardcoded URLs

#### Task 2.2: Test AuthHandler
**Effort**: 15 minutes

**Test Cases**:
- [ ] Registration with valid data works
- [ ] Registration with invalid email shows error
- [ ] Login with valid credentials redirects
- [ ] Login with invalid credentials shows error
- [ ] Admin logout redirects to login

---

### Phase 3: WishlistHandler (30 minutes)

#### Task 3.1: Create WishlistHandler Class
**File**: `includes/handlers/WishlistHandler.php` (new)
**Priority**: MEDIUM
**Effort**: 20 minutes

**Key Methods**:
- `canHandle(string $route, array $request): bool`
- `handle(string $route, array $request): void`
- `handleAdd(array $currentUser, int $productId): void`
- `handleRemove(array $currentUser, int $productId): void`
- `sendJsonResponse(array $data): void`

**Acceptance**:
- [ ] Implements HandlerInterface
- [ ] Routes: wishlist/add, wishlist/remove
- [ ] Returns JSON with Content-Type header
- [ ] Checks authentication
- [ ] Duplicate check on add
- [ ] Prepared statements used

#### Task 3.2: Test WishlistHandler
**Effort**: 10 minutes

**Test Cases**:
- [ ] Add to wishlist when logged in
- [ ] Add to wishlist when logged out (should fail)
- [ ] Duplicate add returns "Already in wishlist"
- [ ] Remove from wishlist works
- [ ] JSON response format correct

---

### Phase 4: RequestHandler (30 minutes)

#### Task 4.1: Create RequestHandler Class
**File**: `includes/handlers/RequestHandler.php` (new)
**Priority**: HIGH
**Effort**: 20 minutes

**Key Methods**:
- `__construct()` - Register all handlers
- `registerHandler(HandlerInterface $handler): void`
- `handlePost(string $route): bool`

**Acceptance**:
- [ ] Registers AuthHandler and WishlistHandler
- [ ] Validates CSRF before dispatching
- [ ] Aggregates POST + GET + FILES
- [ ] Iterates through handlers
- [ ] Returns true if handled, false otherwise
- [ ] Logs errors

#### Task 4.2: Test RequestHandler
**Effort**: 10 minutes

**Test Cases**:
- [ ] CSRF validation happens first
- [ ] Routes to correct handler
- [ ] Request aggregation works
- [ ] Returns false for unknown routes
- [ ] Errors are logged

---

### Phase 5: Integration (30 minutes)

#### Task 5.1: Update index.php
**File**: `index.php` (modify)
**Priority**: HIGH
**Effort**: 15 minutes

**Changes**:
1. Add handler includes after line 8
2. Replace lines 22-101 with RequestHandler call
3. Keep all GET routing unchanged

**Acceptance**:
- [ ] Handlers loaded
- [ ] POST handling via RequestHandler
- [ ] GET routing unchanged
- [ ] No syntax errors
- [ ] Line count < 500

#### Task 5.2: Full Integration Test
**Effort**: 15 minutes

**Test Cases**:
- [ ] User registration works
- [ ] User login works
- [ ] Admin logout works
- [ ] Wishlist add works (AJAX)
- [ ] Wishlist remove works (AJAX)
- [ ] All GET routes work
- [ ] No 404 errors on existing URLs

---

### Phase 6: Verification (15 minutes)

#### Task 6.1: Syntax Checks
**Effort**: 5 minutes

```bash
php -l includes/handlers/HandlerInterface.php
php -l includes/handlers/HandlerException.php
php -l includes/handlers/AuthHandler.php
php -l includes/handlers/WishlistHandler.php
php -l includes/handlers/RequestHandler.php
php -l index.php
```

**Acceptance**:
- [ ] All files pass syntax check
- [ ] No errors reported

#### Task 6.2: Smoke Test
**Effort**: 10 minutes

```bash
# Test homepage
curl -s http://localhost/CannaBuddy.shop/ | grep -i error || echo "✓ Home OK"

# Test registration endpoint
curl -s -X POST http://localhost/CannaBuddy.shop/ \
  -d "email=test@example.com&password=test123&first_name=Test&last_name=User" \
  | grep -i error || echo "✓ Register OK"

# Test user login endpoint
curl -s -X POST http://localhost/CannaBuddy.shop/user/login/ \
  -d "email=test@example.com&password=test123" \
  | grep -i error || echo "✓ Login OK"
```

**Acceptance**:
- [ ] Homepage loads
- [ ] Registration endpoint responds
- [ ] Login endpoint responds
- [ ] No PHP errors in logs

---

## Task Priority Matrix

| Task | Phase | Priority | Dependencies | Est. Time |
|------|-------|----------|--------------|-----------|
| 1.1 Create directory | 1 | HIGH | None | 2 min |
| 1.2 HandlerInterface | 1 | HIGH | 1.1 | 5 min |
| 1.3 HandlerException | 1 | HIGH | 1.1 | 5 min |
| 2.1 AuthHandler | 2 | HIGH | 1.2, 1.3 | 30 min |
| 2.2 Test AuthHandler | 2 | HIGH | 2.1 | 15 min |
| 3.1 WishlistHandler | 3 | MEDIUM | 1.2, 1.3 | 20 min |
| 3.2 Test WishlistHandler | 3 | MEDIUM | 3.1 | 10 min |
| 4.1 RequestHandler | 4 | HIGH | 2.1, 3.1 | 20 min |
| 4.2 Test RequestHandler | 4 | HIGH | 4.1 | 10 min |
| 5.1 Update index.php | 5 | HIGH | 4.1 | 15 min |
| 5.2 Integration test | 5 | HIGH | 5.1 | 15 min |
| 6.1 Syntax checks | 6 | HIGH | 5.1 | 5 min |
| 6.2 Smoke test | 6 | HIGH | All | 10 min |

---

## Total Time Estimate

| Phase | Tasks | Time |
|-------|-------|------|
| Phase 1: Infrastructure | 3 tasks | 12 min |
| Phase 2: AuthHandler | 2 tasks | 45 min |
| Phase 3: WishlistHandler | 2 tasks | 30 min |
| Phase 4: RequestHandler | 2 tasks | 30 min |
| Phase 5: Integration | 2 tasks | 30 min |
| Phase 6: Verification | 2 tasks | 15 min |
| **TOTAL** | **13 tasks** | **2h 42min** |

---

## Rollback Plan

If issues occur:

### Quick Rollback (Per Phase)
```bash
# Undo git changes
git checkout HEAD -- index.php
rm -rf includes/handlers/
```

### Full Rollback
```bash
# Reset to before Phase 1
git reset --hard HEAD~1
```

---

## Completion Checklist

- [ ] All 13 tasks completed
- [ ] All acceptance criteria met
- [ ] Smoke test passes
- [ ] No PHP errors in logs
- [ ] index.php < 500 lines
- [ ] All POST operations working
- [ ] All GET routes working
- [ ] Git commit with descriptive message

---

*Version: 1.0*
*Status: Ready for Implementation*
