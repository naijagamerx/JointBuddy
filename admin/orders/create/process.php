<?php
// Order Creation Handler
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/orders/create/');
}

// Validate CSRF token
CsrfMiddleware::validate();

// Get database connection from services
$db = Services::db();

$orderService = new OrderService($db);

// Validate and sanitize input
$data = [
    'customer_first_name' => trim($_POST['first_name'] ?? ''),
    'customer_last_name' => trim($_POST['last_name'] ?? ''),
    'customer_email' => trim($_POST['email'] ?? ''),
    'customer_phone' => trim($_POST['phone'] ?? ''),
    'shipping_name' => trim($_POST['first_name'] ?? '' . ' ' . trim($_POST['last_name'] ?? '')),
    'shipping_street' => trim($_POST['shipping_street'] ?? ''),
    'shipping_city' => trim($_POST['shipping_city'] ?? ''),
    'shipping_state' => trim($_POST['shipping_state'] ?? ''),
    'shipping_postal_code' => trim($_POST['shipping_postal_code'] ?? ''),
    'same_as_billing' => isset($_POST['same_as_billing']),
    'billing_name' => '',
    'billing_street' => '',
    'billing_city' => '',
    'billing_state' => '',
    'billing_postal_code' => '',
    'delivery_method_id' => (int)($_POST['delivery_method_id'] ?? 0) ?: null,
    'payment_method' => $_POST['payment_method'] ?? 'cash',
    'payment_status' => $_POST['payment_status'] ?? 'paid',
    'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
    'notes' => trim($_POST['notes'] ?? ''),
    'items' => []
];

// Handle billing address if different
if (!$data['same_as_billing']) {
    $data['billing_name'] = trim($_POST['first_name'] ?? '' . ' ' . trim($_POST['last_name'] ?? ''));
    $data['billing_street'] = trim($_POST['billing_street'] ?? '');
    $data['billing_city'] = trim($_POST['billing_city'] ?? '');
    $data['billing_state'] = trim($_POST['billing_state'] ?? '');
    $data['billing_postal_code'] = trim($_POST['billing_postal_code'] ?? '');
}

// Clean up extra spaces in names
$data['shipping_name'] = preg_replace('/\s+/', ' ', $data['shipping_name']);
$data['billing_name'] = preg_replace('/\s+/', ' ', $data['billing_name']);

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
if (empty($data['customer_first_name'])) {
    $_SESSION['error'] = 'First name is required.';
    redirect('/admin/orders/create/');
}

// Last name is now optional (removed validation)

// Email is optional - only validate if provided
if (!empty($data['customer_email']) && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address or leave blank.';
    redirect('/admin/orders/create/');
}

// Phone is optional - no validation needed

// Use placeholder email if neither email nor phone is provided (walk-in customer)
if (empty($data['customer_email']) && empty($data['customer_phone'])) {
    $data['customer_email'] = 'walk-in@pos.local'; // Placeholder for database compatibility
}

if (empty($data['shipping_street']) || empty($data['shipping_city']) ||
    empty($data['shipping_state']) || empty($data['shipping_postal_code'])) {
    $_SESSION['error'] = 'Please fill in all shipping address fields.';
    redirect('/admin/orders/create/');
}

// Validate billing address if different
if (!$data['same_as_billing']) {
    if (empty($data['billing_street']) || empty($data['billing_city']) ||
        empty($data['billing_state']) || empty($data['billing_postal_code'])) {
        $_SESSION['error'] = 'Please fill in all billing address fields.';
        redirect('/admin/orders/create/');
    }
}

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
