<?php
// Print-friendly Invoice Controller
require_once __DIR__ . '/../../../includes/session_helper.php';
require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/url_helper.php';
require_once __DIR__ . '/invoice_registry.php';

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

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    die('Invalid order ID');
}

$order = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            die('Order not found');
        }

        // Get order items with product details (SKU, weight, etc.)
        $stmt = $db->prepare("
            SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images,
                   p.sku as product_sku, p.weight as product_weight, p.brand as product_brand
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $shippingAddress = json_decode($order['shipping_address'] ?? '{}', true);

        // Get store settings
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $storeName = $settings['store_name'] ?? 'Store';
        $storeAddress = $settings['store_address'] ?? '';
        $storeEmail = $settings['store_email'] ?? $settings['support_email'] ?? '';
        $storePhone = $settings['store_phone'] ?? '';
        $storeWebsite = $settings['site_url'] ?? '';

        // Get payment method details with custom fields
        $paymentMethodDetails = null;
        $paymentCustomFields = [];
        $paymentMethodName = $order['payment_method'] ?? 'N/A';

        if (!empty($order['payment_method'])) {
            // Try to find payment method by name first
            $stmt = $db->prepare("SELECT * FROM payment_methods WHERE name = ? LIMIT 1");
            $stmt->execute([$order['payment_method']]);
            $paymentMethodDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found by name, try by type (for backward compatibility)
            if (!$paymentMethodDetails) {
                $stmt = $db->prepare("SELECT * FROM payment_methods WHERE type = ? OR manual_type = ? LIMIT 1");
                $stmt->execute([$order['payment_method'], $order['payment_method']]);
                $paymentMethodDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Get custom fields for this payment method
            if ($paymentMethodDetails) {
                $paymentMethodName = $paymentMethodDetails['name'];
                $stmt = $db->prepare("SELECT field_name, field_value FROM payment_method_fields WHERE payment_method_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$paymentMethodDetails['id']]);
                $paymentCustomFields = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        }

        // Banking details - for backward compatibility with templates
        $bankName = '';
        $bankAccountName = '';
        $bankAccountNumber = '';
        $bankBranchCode = '';

        // Map custom fields to old variable names for template compatibility
        foreach ($paymentCustomFields as $fieldName => $fieldValue) {
            $normalizedName = strtolower(str_replace([' ', '-', '_'], '', $fieldName));
            if (strpos($normalizedName, 'bankname') !== false || (strpos($normalizedName, 'bank') !== false && strpos($normalizedName, 'name') !== false)) {
                $bankName = $fieldValue;
            } elseif (strpos($normalizedName, 'accountname') !== false) {
                $bankAccountName = $fieldValue;
            } elseif (strpos($normalizedName, 'accountnumber') !== false || strpos($normalizedName, 'accountno') !== false) {
                $bankAccountNumber = $fieldValue;
            } elseif (strpos($normalizedName, 'branchcode') !== false) {
                $bankBranchCode = $fieldValue;
            }
        }

        // NO FALLBACK to settings - banking details only show if payment method has them

        // Get QR code for cryptocurrency payments
        $qrCodeUrl = '';
        if (!empty($order['payment_method'])) {
            $stmt = $db->prepare("SELECT qr_code_path FROM payment_methods WHERE name = ? OR manual_type = ? LIMIT 1");
            $stmt->execute([$order['payment_method'], $order['payment_method']]);
            $pm = $stmt->fetch();

            if ($pm && !empty($pm['qr_code_path'])) {
                $qrCodeUrl = assetUrl($pm['qr_code_path']);
            }
        }

        // Get delivery method
        $deliveryMethod = null;
        if (!empty($order['delivery_method_id'])) {
            $stmt = $db->prepare("SELECT name, cost FROM delivery_methods WHERE id = ?");
            $stmt->execute([$order['delivery_method_id']]);
            $deliveryMethod = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Get customer account info
        $customerUserId = null;
        $customerAccountId = null;
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$order['customer_email']]);
        $userRow = $stmt->fetch();
        if ($userRow) {
            $customerUserId = $userRow['id'];
            $customerAccountId = 'CUST-' . str_pad($customerUserId, 6, '0', STR_PAD_LEFT);
        }

    } catch (Exception $e) {
        error_log("Error getting order: " . $e->getMessage());
        die('Error loading order');
    }
}

$statusOptions = [
    'new' => 'New',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'on_the_way' => 'On The Way',
    'delivered' => 'Delivered',
    'rejected' => 'Rejected'
];

// Helper function used by templates
function formatAddressCompact($address) {
    if (empty($address) || !is_array($address)) {
        return 'No address provided';
    }
    $parts = [];
    if (!empty($address['name'])) $parts[] = $address['name'];
    $street = $address['street'] ?? $address['address_line_1'] ?? '';
    if (!empty($street)) $parts[] = $street;
    $cityParts = [];
    if (!empty($address['city'])) $cityParts[] = $address['city'];
    if (!empty($address['state'])) $cityParts[] = $address['state'];
    if (!empty($address['postal_code'])) $cityParts[] = $address['postal_code'];
    if (!empty($cityParts)) $parts[] = implode(', ', $cityParts);
    if (!empty($address['phone'])) $parts[] = 'Tel: ' . $address['phone'];
    return implode("\n", array_map('htmlspecialchars', $parts));
}

// Determine Invoice Design
// Priority: 1. GET param (preview/override), 2. Saved Order Preference, 3. Global Default, 4. 'default'
$designKey = $_GET['design'] ?? $order['invoice_design'] ?? $settings['default_invoice_design'] ?? 'default';

// Validate Design
if (!InvoiceDesignRegistry::exists($designKey)) {
    $designKey = 'default';
}
$design = InvoiceDesignRegistry::get($designKey);

// If design was passed via GET and differs from saved preference, we *could* save it, 
// but usually GET is for temporary switching/preview. 
// The saving logic should be handled by the order view page via AJAX.

// Load Template
$templatePath = __DIR__ . '/templates/' . $design['file'];

if (file_exists($templatePath)) {
    require $templatePath;
} else {
    // Fallback
    die("Invoice template file not found: " . htmlspecialchars($design['file']));
}
?>
