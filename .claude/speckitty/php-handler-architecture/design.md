# PHP Handler Architecture - Design

**Project**: CannaBuddy.shop E-Commerce Platform
**PHP Version**: 8.3.1
**Type**: Standalone PHP (No Framework)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                              index.php                                │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ 1. Load bootstrap.php                                           │ │
│  │ 2. Load route.php                                               │ │
│  │ 3. Initialize Services                                          │ │
│  │ 4. Load handlers                                                 │ │
│  │ 5. POST → RequestHandler::handlePost($route)                    │ │
│  │ 6. GET → File-based routing (unchanged)                         │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         RequestHandler                                │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ handlePost(string $route): bool                                 │ │
│  │   1. CsrfMiddleware::validate()                                │ │
│  │   2. Aggregate request (POST + GET + FILES)                    │ │
│  │   3. foreach ($handlers as $handler) {                          │ │
│  │        if ($handler->canHandle($route, $request)) {             │ │
│  │            $handler->handle($route, $request);                  │ │
│  │            return true;                                         │ │
│  │        }                                                         │ │
│  │    }                                                             │ │
│  │   4. return false;                                              │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                ▼             ▼             ▼
        ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
        │ AuthHandler │ │WishlistHandler│ │  Future...  │
        │             │ │              │ │  Handlers   │
        │ - register  │ │ - add        │ │             │
        │ - login     │ │ - remove     │ │             │
        │ - logout    │ │              │ │             │
        └─────────────┘ └─────────────┘ └─────────────┘
```

---

## Class Hierarchy

```
HandlerInterface (interface)
    ├── canHandle(string $route, array $request): bool
    └── handle(string $route, array $request): void

HandlerException extends Exception
    ├── protected ?array $context
    └── getContext(): ?array

RequestHandler (dispatcher)
    ├── private array $handlers
    ├── __construct()
    ├── registerHandler(HandlerInterface $handler)
    └── handlePost(string $route): bool

AuthHandler implements HandlerInterface
    ├── canHandle(string $route, array $request): bool
    ├── handle(string $route, array $request): void
    ├── handleRegister(array $request): void
    ├── handleUserLogin(array $request): void
    └── handleAdminLogout(): void

WishlistHandler implements HandlerInterface
    ├── canHandle(string $route, array $request): bool
    ├── handle(string $route, array $request): void
    ├── handleAdd(array $currentUser, int $productId): void
    ├── handleRemove(array $currentUser, int $productId): void
    └── sendJsonResponse(array $data): void
```

---

## File Structure

```
includes/handlers/
├── HandlerInterface.php      (Interface definition)
├── HandlerException.php      (Exception class)
├── AuthHandler.php           (Auth operations)
├── WishlistHandler.php       (Wishlist operations)
└── RequestHandler.php        (Dispatcher)
```

---

## Integration Points

### With Bootstrap

```php
// index.php (after bootstrap)
require_once __DIR__ . '/includes/bootstrap.php';

// Load handlers
require_once __DIR__ . '/includes/handlers/HandlerInterface.php';
require_once __DIR__ . '/includes/handlers/HandlerException.php';
require_once __DIR__ . '/includes/handlers/AuthHandler.php';
require_once __DIR__ . '/includes/handlers/WishlistHandler.php';
require_once __DIR__ . '/includes/handlers/RequestHandler.php';

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestHandler = new RequestHandler();
    $requestHandler->handlePost($route);
}
```

### With Services Container

```php
// Inside handlers
$db = Services::db();              // PDO instance
$userAuth = Services::userAuth();   // UserAuth instance
$adminAuth = Services::adminAuth(); // AdminAuth instance
```

### With Middleware

```php
// In RequestHandler::handlePost()
CsrfMiddleware::validate(); // Throws/redirects on invalid

// In handlers for auth checks
$currentUser = AuthMiddleware::getCurrentUser();
if (!$currentUser) {
    // Handle unauthenticated
}
```

---

## Data Flow

### POST Request Flow

```
1. User submits form → POST /user/login
                    ↓
2. index.php receives request
                    ↓
3. route.php returns 'user/login'
                    ↓
4. RequestHandler::handlePost('user/login')
                    ↓
5. CsrfMiddleware::validate() ✓
                    ↓
6. Aggregate $request = $_POST + $_GET + $_FILES
                    ↓
7. AuthHandler::canHandle('user/login', $request) → true
                    ↓
8. AuthHandler::handle('user/login', $request)
                    ↓
9. handleUserLogin($request)
   ├─ Services::userAuth()->login($email, $password)
   ├─ If success: redirect(url('user/'))
   └─ If fail: $_SESSION['user_login_error'] = $message
```

### Wishlist AJAX Flow

```
1. JavaScript fetch('/wishlist/add', {method: 'POST', body: FormData})
                    ↓
2. RequestHandler::handlePost('wishlist/add')
                    ↓
3. WishlistHandler::canHandle() → true
                    ↓
4. WishlistHandler::handle()
                    ↓
5. handleAdd($currentUser, $productId)
   ├─ AuthMiddleware::getCurrentUser()
   ├─ If not auth: sendJsonResponse(['success' => false])
   ├─ Services::db() - Check for duplicate
   ├─ Services::db() - Insert if new
   └─ sendJsonResponse(['success' => true])
                    ↓
6. JSON response to JavaScript
```

---

## Method Signatures

### HandlerInterface

```php
<?php
interface HandlerInterface {
    /**
     * Check if this handler can process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data (POST + GET + FILES)
     * @return bool True if handler can process this route
     */
    public function canHandle(string $route, array $request): bool;

    /**
     * Process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data (POST + GET + FILES)
     * @return void
     * @throws HandlerException If processing fails
     */
    public function handle(string $route, array $request): void;
}
```

### HandlerException

```php
<?php
class HandlerException extends Exception {
    protected ?array $context = null;

    /**
     * @param string $message Error message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param array|null $context Additional context for debugging
     */
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
     * @return array|null Context data or null
     */
    public function getContext(): ?array {
        return $this->context;
    }
}
```

### RequestHandler

```php
<?php
class RequestHandler {
    private array $handlers = [];

    /**
     * Constructor - registers all handlers
     */
    public function __construct();

    /**
     * Register a handler
     * @param HandlerInterface $handler Handler to register
     */
    private function registerHandler(HandlerInterface $handler): void;

    /**
     * Handle POST request
     * @param string $route Current route
     * @return bool True if handled, false if no handler found
     */
    public function handlePost(string $route): bool;
}
```

### AuthHandler

```php
<?php
class AuthHandler implements HandlerInterface {
    public function canHandle(string $route, array $request): bool;

    public function handle(string $route, array $request): void;

    /**
     * Handle user registration
     * @param UserAuth|null $userAuth User auth service
     * @param array $request Request data
     */
    private function handleRegister(?UserAuth $userAuth, array $request): void;

    /**
     * Handle user login
     * @param UserAuth|null $userAuth User auth service
     * @param array $request Request data
     */
    private function handleUserLogin(?UserAuth $userAuth, array $request): void;

    /**
     * Handle admin logout
     * @param AdminAuth|null $adminAuth Admin auth service
     */
    private function handleAdminLogout(?AdminAuth $adminAuth): void;
}
```

### WishlistHandler

```php
<?php
class WishlistHandler implements HandlerInterface {
    public function canHandle(string $route, array $request): bool;

    public function handle(string $route, array $request): void;

    /**
     * Add product to wishlist
     * @param array $currentUser Current user data
     * @param int $productId Product ID
     */
    private function handleAdd(array $currentUser, int $productId): void;

    /**
     * Remove product from wishlist
     * @param array $currentUser Current user data
     * @param int $productId Product ID
     */
    private function handleRemove(array $currentUser, int $productId): void;

    /**
     * Send JSON response
     * @param array $data Response data
     */
    private function sendJsonResponse(array $data): void;
}
```

---

## Database Operations

### Prepared Statement Pattern

```php
// In WishlistHandler::handleAdd()
try {
    $db = Services::db();
    $userId = $currentUser['id'];
    $productId = (int)$request['product_id'];

    // Check for duplicate
    $stmt = $db->prepare(
        "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?"
    );
    $stmt->execute([$userId, $productId]);

    if ($stmt->fetch()) {
        $this->sendJsonResponse([
            'success' => true,
            'message' => 'Already in wishlist'
        ]);
        return;
    }

    // Insert new
    $stmt = $db->prepare(
        "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())"
    );
    $stmt->execute([$userId, $productId]);

    $this->sendJsonResponse([
        'success' => true,
        'message' => 'Added to wishlist'
    ]);

} catch (Exception $e) {
    error_log("Wishlist add error: " . $e->getMessage());
    $this->sendJsonResponse([
        'success' => false,
        'message' => 'Database error'
    ]);
}
```

---

## Error Handling Strategy

### Try-Catch Pattern

```php
try {
    // Database operation
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new HandlerException("Record not found", 404, null, [
            'route' => $route,
            'params' => $params
        ]);
    }

    return $result;

} catch (PDOException $e) {
    error_log("Database error in " . __METHOD__ . ": " . $e->getMessage());
    throw new HandlerException("Operation failed", 500, $e);
}
```

### Session Error Messages

```php
// Success
$_SESSION['registration_success'] = 'Account created successfully!';
$_SESSION['user_login_success'] = 'Welcome back!';

// Error
$_SESSION['registration_error'] = 'Email already exists';
$_SESSION['user_login_error'] = 'Invalid credentials';
```

---

## Security Considerations

### CSRF Protection

```php
// In RequestHandler::handlePost()
public function handlePost(string $route): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }

    // Always validate CSRF for POST
    CsrfMiddleware::validate();

    // ... continue to handlers
}
```

### SQL Injection Prevention

```php
// ALWAYS use prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// NEVER concatenate
// WRONG: "SELECT * FROM users WHERE email = '$email'"
```

### Output Escaping

```php
// In views
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Using helper
<?= safe_html($userInput) ?>
```

---

## Testing Strategy

### Unit Tests (Future)

```php
// Test AuthHandler::canHandle()
$handler = new AuthHandler();
assert($handler->canHandle('register', ['email' => 'x']) === true);
assert($handler->canHandle('shop', []) === false);

// Test WishlistHandler::sendJsonResponse()
ob_start();
$handler->sendJsonResponse(['success' => true]);
$output = ob_get_clean();
assert(json_decode($output)->success === true);
```

### Integration Tests

```php
// Test full request flow
$_POST['email'] = 'test@example.com';
$_POST['password'] = 'password123';
$_POST['csrf_token'] = csrf_token();

$handler = new RequestHandler();
$result = $handler->handlePost('user/login');
assert($result === true);
```

---

## Migration Strategy

### Phase 1: Create Infrastructure
1. Create `includes/handlers/` directory
2. Create `HandlerInterface.php`
3. Create `HandlerException.php`

### Phase 2: Create Handlers
1. Create `AuthHandler.php`
2. Create `WishlistHandler.php`
3. Create `RequestHandler.php`

### Phase 3: Integrate
1. Add includes to `index.php`
2. Replace POST handling (lines 22-101)
3. Test all POST operations

### Phase 4: Cleanup
1. Verify all functionality works
2. Remove old inline code from `index.php`
3. Commit changes

---

## Performance Considerations

- Handler registration: O(n) where n = number of handlers (small)
- Handler matching: O(n) worst case, typically O(1) with early return
- No additional database queries
- Memory overhead: ~1KB per handler class

---

## Future Extensibility

### Adding New Handlers

```php
// 1. Create handler class
class CartHandler implements HandlerInterface {
    public function canHandle(string $route, array $request): bool {
        return str_starts_with($route, 'cart/');
    }

    public function handle(string $route, array $request): void {
        // Cart logic
    }
}

// 2. Register in RequestHandler::__construct()
public function __construct() {
    $this->registerHandler(new AuthHandler());
    $this->registerHandler(new WishlistHandler());
    $this->registerHandler(new CartHandler()); // NEW
}
```

### Handler Priority

```php
// Handlers are checked in registration order
// Register specific handlers before generic ones

$this->registerHandler(new ProductReviewHandler());  // Specific
$this->registerHandler(new ProductHandler());         // Generic
```

---

*Version: 1.0*
*Status: Ready for Implementation*
