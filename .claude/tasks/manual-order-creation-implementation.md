# Manual Order Creation - Detailed Implementation Plan

## Overview
This document provides step-by-step implementation instructions for the manual order creation feature. Each task includes file paths, code snippets, and validation steps.

---

## Task 1: Database Migration (Optional)

### 1.1 Add `order_source` field to orders table

**File**: Database migration script

**SQL**:
```sql
-- Add order_source field to track where orders originated
ALTER TABLE orders
ADD COLUMN order_source ENUM('website', 'admin', 'pos', 'api') DEFAULT 'website' AFTER payment_method,
ADD INDEX idx_order_source (order_source);

-- Verify the change
DESCRIBE orders;
```

**Validation**:
```bash
C:/MAMP/bin/mysql/bin/mysql -u root -proot cannabuddy -e "DESCRIBE orders;"
```

**Expected Result**: New column `order_source` should appear in the table description

---

## Task 2: Create OrderService Class

### 2.1 Create order management service

**File**: `/includes/order_service.php`

**Purpose**: Centralized order management logic

```php
<?php
/**
 * Order Service
 * Handles order creation and management operations
 */
class OrderService {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Generate unique order number
     * Format: ORD-YYYY-XXXXXXXX
     */
    public function generateOrderNumber() {
        return 'ORD-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Search for existing customer by email or phone
     */
    public function searchCustomer($search) {
        $stmt = $this->db->prepare("
            SELECT id, email, first_name, last_name, phone
            FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");
        $stmt->execute([$search, $search]);
        return $stmt->fetch();
    }

    /**
     * Get products for search (with pagination)
     */
    public function searchProducts($searchTerm = '', $limit = 20, $offset = 0) {
        if (!empty($searchTerm)) {
            $stmt = $this->db->prepare("
                SELECT id, name, slug, sku, price, sale_price, stock, images
                FROM products
                WHERE active = 1 AND (name LIKE ? OR sku LIKE ?)
                ORDER BY name ASC
                LIMIT ? OFFSET ?
            ");
            $param = "%$searchTerm%";
            $stmt->execute([$param, $param, $limit, $offset]);
        } else {
            $stmt = $this->db->prepare("
                SELECT id, name, slug, sku, price, sale_price, stock, images
                FROM products
                WHERE active = 1
                ORDER BY name ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get product by ID with full details
     */
    public function getProduct($productId) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category = c.name
            WHERE p.id = ? AND p.active = 1
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }

    /**
     * Get delivery methods
     */
    public function getDeliveryMethods() {
        $stmt = $this->db->query("SELECT * FROM delivery_methods WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods() {
        return [
            ['id' => 'cash', 'name' => 'Cash', 'default_status' => 'paid'],
            ['id' => 'eft', 'name' => 'EFT (Electronic Funds Transfer)', 'default_status' => 'pending'],
            ['id' => 'card_in_store', 'name' => 'Card Payment (In-Store)', 'default_status' => 'paid'],
            ['id' => 'other', 'name' => 'Other', 'default_status' => 'pending']
        ];
    }

    /**
     * Create manual order
     */
    public function createManualOrder($data) {
        try {
            $this->db->beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Check if customer exists
            $customer = $this->searchCustomer($data['customer_email']);
            $userId = $customer ? $customer['id'] : null;

            // Prepare addresses as JSON
            $shippingAddress = json_encode([
                'name' => $data['shipping_name'],
                'street' => $data['shipping_street'],
                'city' => $data['shipping_city'],
                'state' => $data['shipping_state'],
                'postal_code' => $data['shipping_postal_code'],
                'phone' => $data['customer_phone'] ?? ''
            ]);

            $billingAddress = $data['same_as_billing']
                ? $shippingAddress
                : json_encode([
                    'name' => $data['billing_name'],
                    'street' => $data['billing_street'],
                    'city' => $data['billing_city'],
                    'state' => $data['billing_state'],
                    'postal_code' => $data['billing_postal_code']
                ]);

            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += ($item['unit_price'] * $item['quantity']);
            }

            // Get delivery cost
            $shippingCost = 0;
            if (!empty($data['delivery_method_id'])) {
                $stmt = $this->db->prepare("SELECT cost FROM delivery_methods WHERE id = ?");
                $stmt->execute([$data['delivery_method_id']]);
                $delivery = $stmt->fetch();
                $shippingCost = $delivery ? $delivery['cost'] : 0;
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $shippingCost - $discountAmount;

            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_email, customer_phone,
                    shipping_address, billing_address,
                    delivery_method_id, payment_method, payment_status,
                    subtotal, shipping_amount, discount_amount, total_amount,
                    status, notes, order_source, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'admin', NOW())
            ");

            $stmt->execute([
                $orderNumber,
                $userId,
                $data['customer_first_name'] . ' ' . $data['customer_last_name'],
                $data['customer_email'],
                $data['customer_phone'] ?? null,
                $shippingAddress,
                $billingAddress,
                $data['delivery_method_id'] ?? null,
                $data['payment_method'],
                $data['payment_status'],
                $subtotal,
                $shippingCost,
                $discountAmount,
                $totalAmount,
                $data['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Insert order items
            $stmt = $this->db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, product_sku,
                    variation_id, variation_name, quantity, unit_price, total_price
                ) VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, ?)
            ");

            foreach ($data['items'] as $item) {
                $product = $this->getProduct($item['product_id']);
                $lineTotal = $item['unit_price'] * $item['quantity'];

                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $product['name'],
                    $product['sku'] ?? '',
                    $item['quantity'],
                    $item['unit_price'],
                    $lineTotal
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ];
        }
    }
}
```

**Validation**:
- File exists at `/includes/order_service.php`
- No syntax errors: `php -l includes/order_service.php`

---

## Task 3: Create Manual Order Page

### 3.1 Create order creation page

**File**: `/admin/orders/create/index.php`

**Structure**:
```php
<?php
// Manual Order Creation Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/url_helper.php';
require_once __DIR__ . '/../../../includes/order_service.php';
require_once __DIR__ . '/../../../admin_sidebar_components.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

$orderService = new OrderService($db);
$adminId = $adminAuth->getAdminId();

// Get delivery methods and payment methods
$deliveryMethods = $orderService->getDeliveryMethods();
$paymentMethods = $orderService->getPaymentMethods();

// Display messages
$messageHtml = '';
if (isset($_SESSION['success'])) {
    $messageHtml = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">...</div>';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $messageHtml = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">...</div>';
    unset($_SESSION['error']);
}

// Build page content with:
// 1. Customer search/entry section
// 2. Address forms (shipping/billing)
// 3. Product search with autocomplete
// 4. Order items table
// 5. Order options (delivery, payment)
// 6. Pricing summary
// 7. Submit button

$content = '...'; // Full HTML will be in implementation

echo adminSidebarWrapper('Create Manual Order', $content, 'orders-create');
```

---

## Task 4: Create Order Processing Handler

### 4.1 Create process handler

**File**: `/admin/orders/create/process.php`

```php
<?php
// Order Creation Handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/url_helper.php';
require_once __DIR__ . '/../../../includes/order_service.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/orders/create/');
}

// CSRF validation (implement)

$orderService = new OrderService($db);

// Validate and sanitize input
$data = [
    'customer_first_name' => trim($_POST['first_name'] ?? ''),
    'customer_last_name' => trim($_POST['last_name'] ?? ''),
    'customer_email' => trim($_POST['email'] ?? ''),
    'customer_phone' => trim($_POST['phone'] ?? ''),
    'shipping_name' => trim($_POST['shipping_name'] ?? ''),
    'shipping_street' => trim($_POST['shipping_street'] ?? ''),
    'shipping_city' => trim($_POST['shipping_city'] ?? ''),
    'shipping_state' => trim($_POST['shipping_state'] ?? ''),
    'shipping_postal_code' => trim($_POST['shipping_postal_code'] ?? ''),
    'same_as_billing' => isset($_POST['same_as_billing']),
    'billing_name' => trim($_POST['billing_name'] ?? ''),
    'billing_street' => trim($_POST['billing_street'] ?? ''),
    'billing_city' => trim($_POST['billing_city'] ?? ''),
    'billing_state' => trim($_POST['billing_state'] ?? ''),
    'billing_postal_code' => trim($_POST['billing_postal_code'] ?? ''),
    'delivery_method_id' => (int)($_POST['delivery_method_id'] ?? 0) ?: null,
    'payment_method' => $_POST['payment_method'] ?? 'cash',
    'payment_status' => $_POST['payment_status'] ?? 'paid',
    'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
    'notes' => trim($_POST['notes'] ?? ''),
    'items' => []
];

// Parse items from POST data
if (isset($_POST['items']) && is_array($_POST['items'])) {
    foreach ($_POST['items'] as $item) {
        $data['items'][] = [
            'product_id' => (int)$item['product_id'],
            'quantity' => (int)$item['quantity'],
            'unit_price' => (float)$item['unit_price']
        ];
    }
}

// Validation
if (empty($data['items'])) {
    $_SESSION['error'] = 'Please add at least one product to the order.';
    redirect('/admin/orders/create/');
}

// Create order
$result = $orderService->createManualOrder($data);

if ($result['success']) {
    $_SESSION['success'] = 'Order created successfully! Order number: ' . $result['order_number'];
    redirect('/admin/orders/view/?id=' . $result['order_id']);
} else {
    $_SESSION['error'] = $result['message'];
    redirect('/admin/orders/create/');
}
```

---

## Task 5: Add Navigation Link

### 5.1 Update admin sidebar

**File**: `/admin_sidebar_components.php`

**Find**: Navigation links section in `adminSidebarWrapper()` function

**Add after "Orders" link**:
```php
<a href="' . adminUrl('/orders/create/') . '" class="' . ($currentPage === 'orders-create' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
    <i class="fas fa-plus-circle mr-3"></i>
    Create Order
</a>
```

---

## Task 6: Frontend Implementation Details

### 6.1 Product Search (AJAX)

**Endpoint**: `/admin/orders/create/search-products.php`

```php
<?php
// Product Search API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../includes/database.php';
require_once __DIR__ . '/../../../../includes/order_service.php';

// Admin auth check...
header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';
$orderService = new OrderService($db);
$products = $orderService->searchProducts($searchTerm, 20, 0);

echo json_encode([
    'success' => true,
    'products' => $products
]);
```

### 6.2 Customer Search (AJAX)

**Endpoint**: `/admin/orders/create/search-customer.php`

```php
<?php
// Customer Search API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../includes/database.php';
require_once __DIR__ . '/../../../../includes/order_service.php';

// Admin auth check...
header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';
$orderService = new OrderService($db);
$customer = $orderService->searchCustomer($searchTerm);

echo json_encode([
    'success' => true,
    'customer' => $customer
]);
```

### 6.3 JavaScript for Order Page

Include in create page:
```javascript
// Product search
let orderItems = [];

function searchProducts(query) {
    fetch('<?php echo adminUrl('/orders/create/search-products.php'); ?>?q=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                displayProductResults(data.products);
            }
        });
}

function addProduct(productId, name, price, sku, stock) {
    // Check if already in order
    const existing = orderItems.find(i => i.product_id === productId);
    if (existing) {
        existing.quantity++;
    } else {
        orderItems.push({
            product_id: productId,
            name: name,
            sku: sku,
            unit_price: price,
            quantity: 1,
            stock: stock
        });
    }
    updateOrderItemsTable();
    updateTotals();
}

function updateOrderItemsTable() {
    const tbody = document.getElementById('order-items-body');
    tbody.innerHTML = '';

    orderItems.forEach((item, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}<br><small class="text-gray-500">${item.sku || ''}</small></td>
            <td><input type="number" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, this.value)" class="w-20 border rounded px-2 py-1"></td>
            <td>R${item.unit_price.toFixed(2)}</td>
            <td>R${(item.unit_price * item.quantity).toFixed(2)}</td>
            <td><button onclick="removeItem(${index})" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
    });
}

function updateTotals() {
    let subtotal = 0;
    orderItems.forEach(item => {
        subtotal += item.unit_price * item.quantity;
    });

    // Get shipping cost
    const deliverySelect = document.getElementById('delivery_method');
    const shippingCost = parseFloat(deliverySelect.options[deliverySelect.selectedIndex]?.dataset?.cost || 0);

    // Get discount
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;

    const total = subtotal + shippingCost - discount;

    document.getElementById('subtotal').textContent = 'R' + subtotal.toFixed(2);
    document.getElementById('shipping').textContent = 'R' + shippingCost.toFixed(2);
    document.getElementById('discount').textContent = '-R' + discount.toFixed(2);
    document.getElementById('total').textContent = 'R' + total.toFixed(2);
}
```

---

## Task 7: Form Validation

### 7.1 Client-side validation

```javascript
function validateForm() {
    const required = [
        'first_name', 'last_name', 'email',
        'shipping_street', 'shipping_city', 'shipping_state', 'shipping_postal_code'
    ];

    for (const field of required) {
        const el = document.getElementById(field);
        if (!el.value.trim()) {
            alert('Please fill in all required fields');
            el.focus();
            return false;
        }
    }

    // Email validation
    const email = document.getElementById('email');
    if (!email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert('Please enter a valid email address');
        email.focus();
        return false;
    }

    // Check order items
    if (orderItems.length === 0) {
        alert('Please add at least one product');
        return false;
    }

    return true;
}
```

---

## Task 8: Testing Checklist

### 8.1 Database Tests
```bash
# Test 1: Verify order_source field exists
C:/MAMP/bin/mysql/bin/mysql -u root -proot cannabuddy -e "DESCRIBE orders;"

# Test 2: Verify indexes
C:/MAMP/bin/mysql/bin/mysql -u root -proot cannabuddy -e "SHOW INDEX FROM orders WHERE Key_name = 'idx_order_source';"
```

### 8.2 Service Class Tests
```php
// Test: Create test file test_delete/test_order_service.php
require_once 'includes/database.php';
require_once 'includes/order_service.php';

$db = new Database();
$orderService = new OrderService($db->getConnection());

// Test order number generation
$orderNumber = $orderService->generateOrderNumber();
echo "Order Number: $orderNumber\n";

// Test product search
$products = $orderService->searchProducts('test');
echo "Found " . count($products) . " products\n";
```

### 8.3 Page Load Tests
1. Navigate to `/admin/orders/create/`
2. Verify page loads without errors
3. Check all form sections are visible
4. Verify delivery methods populate
5. Verify payment methods populate

### 8.4 Functionality Tests
1. **Customer Search**: Enter email, verify customer found
2. **Product Search**: Type product name, verify results
3. **Add Product**: Click add, verify item appears in table
4. **Update Quantity**: Change quantity, verify total updates
5. **Remove Product**: Click remove, verify item removed
6. **Totals Calculation**: Verify all calculations correct
7. **Form Validation**: Try submit without required fields
8. **Order Creation**: Submit valid form, verify redirect to order view

### 8.5 Integration Tests
1. Create order and verify it appears in `/admin/orders/`
2. Generate invoice and verify it displays correctly
3. Check order details match what was entered

---

## Task 9: Error Handling

### 9.1 Database Errors
```php
try {
    // Order creation
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred. Please try again.';
    redirect('/admin/orders/create/');
}
```

### 9.2 Validation Errors
```php
// Email validation
if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
    return [
        'success' => false,
        'message' => 'Invalid email address'
    ];
}

// Phone validation (optional)
if (!empty($data['customer_phone'])) {
    // Allow international formats
    if (!preg_match('/^[\d\s\+\-\(\)]+$/', $data['customer_phone'])) {
        return [
            'success' => false,
            'message' => 'Invalid phone number format'
        ];
    }
}
```

---

## Task 10: Responsive Design

### 10.1 Mobile Styles
```css
@media (max-width: 768px) {
    .order-form .grid-2 {
        grid-template-columns: 1fr;
    }

    .order-items-table {
        display: block;
        overflow-x: auto;
    }

    .product-search-results {
        max-height: 200px;
        overflow-y: auto;
    }
}
```

---

## Implementation Order

1. **Day 1 - Foundation**:
   - Run database migration
   - Create `OrderService` class
   - Create basic page structure

2. **Day 1-2 - Core Features**:
   - Implement customer search
   - Implement product search
   - Build order items management
   - Add form validation

3. **Day 2 - Processing**:
   - Create process handler
   - Test order creation
   - Verify database inserts

4. **Day 2-3 - Integration**:
   - Add sidebar link
   - Test invoice generation
   - Test full workflow

5. **Day 3 - Polish**:
   - Responsive design
   - Error handling
   - User feedback improvements
   - Documentation

---

## Progress Tracking

Use this checklist to track implementation progress:

### Database
- [ ] Run migration to add `order_source` field
- [ ] Verify field was added successfully

### Backend
- [ ] Create `OrderService` class
- [ ] Implement `generateOrderNumber()`
- [ ] Implement `searchCustomer()`
- [ ] Implement `searchProducts()`
- [ ] Implement `getProduct()`
- [ ] Implement `getDeliveryMethods()`
- [ ] Implement `getPaymentMethods()`
- [ ] Implement `createManualOrder()`

### Frontend
- [ ] Create `/admin/orders/create/index.php`
- [ ] Create customer search section
- [ ] Create address forms
- [ ] Create product search section
- [ ] Create order items table
- [ ] Create order options section
- [ ] Create pricing summary

### Processing
- [ ] Create `/admin/orders/create/process.php`
- [ ] Implement form validation
- [ ] Implement order creation logic
- [ ] Test redirect to order view

### AJAX Endpoints
- [ ] Create product search endpoint
- [ ] Create customer search endpoint

### Integration
- [ ] Add sidebar navigation link
- [ ] Test invoice generation
- [ ] Test full workflow

### Polish
- [ ] Add responsive CSS
- [ ] Add error handling
- [ ] Add success/error messages
- [ ] Test all validation
