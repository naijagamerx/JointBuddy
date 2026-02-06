<?php
/**
 * Invoice View/Download Page - User Dashboard
 * View and download invoice for an order
 */
require_once __DIR__ . '/../../includes/url_helper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = null;
$isLoggedIn = false;

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

// Redirect to login if not logged in
if (!$isLoggedIn) {
    redirect('/user/login/');
}

// Get order ID
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    die('Invalid order ID');
}

// Include database
require_once __DIR__ . '/../../includes/database.php';

$order = null;
$orderItems = [];
$settings = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get order (verify it belongs to the user)
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $currentUser['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Order not found or access denied');
    }

    // Get order items with product details
    $stmt = $db->prepare("
        SELECT oi.*, p.name as product_name, p.slug as product_slug,
               p.sku as product_sku, p.weight as product_weight, p.brand as product_brand
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get store settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get customer account ID
    $customerAccountId = null;
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$order['customer_email']]);
    $userRow = $stmt->fetch();
    if ($userRow) {
        $customerAccountId = 'CUST-' . str_pad($userRow['id'], 6, '0', STR_PAD_LEFT);
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    die('Error loading invoice');
}

// Parse shipping address
$shippingAddress = json_decode($order['shipping_address'] ?? '{}', true);
if (empty($shippingAddress) || !is_array($shippingAddress)) {
    $shippingAddress = [
        'name' => $order['customer_name'],
        'street' => $order['shipping_address'] ?? '',
        'city' => $order['shipping_city'] ?? '',
        'postal_code' => $order['shipping_postal_code'] ?? '',
        'phone' => $order['customer_phone'] ?? ''
    ];
}

// Store settings
$storeName = $settings['store_name'] ?? 'Store';
$storeAddress = $settings['store_address'] ?? '';
$storeEmail = $settings['store_email'] ?? $settings['support_email'] ?? '';
$storePhone = $settings['store_phone'] ?? '';
$storeWebsite = $settings['site_url'] ?? '';

// Generate QR Code URL for invoice link
$invoiceUrl = url('/user/invoices/view.php?id=' . $orderId);
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=' . urlencode($invoiceUrl);

// Status options
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

// Helper function for address formatting
function formatAddressCompact($address) {
    if (empty($address) || !is_array($address)) {
        return 'No address provided';
    }
    $parts = [];
    if (!empty($address['name'])) $parts[] = htmlspecialchars($address['name']);
    $street = $address['street'] ?? $address['address_line_1'] ?? '';
    if (!empty($street)) $parts[] = htmlspecialchars($street);
    $cityParts = [];
    if (!empty($address['city'])) $cityParts[] = htmlspecialchars($address['city']);
    if (!empty($address['state'])) $cityParts[] = htmlspecialchars($address['state']);
    if (!empty($address['postal_code'])) $cityParts[] = htmlspecialchars($address['postal_code']);
    if (!empty($cityParts)) $parts[] = implode(', ', $cityParts);
    if (!empty($address['phone'])) $parts[] = 'Tel: ' . htmlspecialchars($address['phone']);
    return implode("\n", $parts);
}

// Check if print mode
$printMode = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 0; }
        @media print {
            body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .document { box-shadow: none !important; margin: 0 !important; }
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .document {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 40px;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .brand h1 { font-size: 24px; font-weight: 800; color: #111827; margin: 0 0 5px 0; letter-spacing: -0.5px; }
        .brand p { margin: 0; color: #6b7280; font-size: 13px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 36px; font-weight: 800; color: #e5e7eb; margin: 0; line-height: 1; letter-spacing: -1px; text-transform: uppercase; }
        .invoice-title .meta { margin-top: 10px; font-size: 13px; color: #4b5563; }
        .status-bar {
            display: flex;
            justify-content: space-between;
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        .status-item { flex: 1; }
        .status-item label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 2px; font-weight: 600; }
        .status-item span { font-size: 13px; font-weight: 600; color: #111827; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin: 0 0 10px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        .box-content { font-size: 13px; }
        .box-content p { margin: 2px 0; white-space: pre-line; }
        .box-content strong { display: block; margin-bottom: 4px; color: #111827; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { text-align: left; padding: 12px 10px; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; font-weight: 600; letter-spacing: 0.5px; }
        .items-table td { padding: 15px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .items-table tr:last-child td { border-bottom: none; }
        .item-desc { font-weight: 500; color: #111827; }
        .item-meta { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .totals-container { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .totals { width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .total-row span:first-child { color: #6b7280; }
        .total-row span:last-child { font-weight: 600; color: #111827; }
        .total-row.grand-total { border-bottom: none; border-top: 2px solid #111827; padding-top: 12px; margin-top: 5px; }
        .total-row.grand-total span { font-size: 16px; font-weight: 800; color: #111827; }
        .footer { margin-top: auto; border-top: 1px solid #e5e7eb; padding-top: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-info { font-size: 11px; color: #9ca3af; }
        .footer-info p { margin: 2px 0; }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 12px 24px;
            background: #111827;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .print-btn:hover { background: #374151; }
        .back-btn {
            position: fixed;
            bottom: 30px;
            right: 200px;
            padding: 12px 24px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .back-btn:hover { background: #4b5563; }
        .hidden-print { display: none; }
    </style>
</head>
<body>
    <?php if (!$printMode): ?>
        <a href="<?= userUrl('/invoices/') ?>" class="back-btn no-print">
            <i class="fas fa-arrow-left"></i> Back to Invoices
        </a>
        <button class="print-btn no-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Invoice
        </button>
    <?php endif; ?>

    <div class="document">
        <div class="header">
            <div class="brand">
                <h1><?= htmlspecialchars($storeName) ?></h1>
                <?php
                $addressLines = array_filter(array_map('trim', explode("\n", $storeAddress)));
                foreach ($addressLines as $line) {
                    echo '<p>' . htmlspecialchars($line) . '</p>';
                }
                if (!empty($storeEmail)) echo '<p>' . htmlspecialchars($storeEmail) . '</p>';
                if (!empty($storePhone)) echo '<p>' . htmlspecialchars($storePhone) . '</p>';
                ?>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <div class="meta">
                    #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?><br>
                    <?= date('F j, Y', strtotime($order['created_at'])) ?>
                </div>
            </div>
        </div>

        <div class="status-bar">
            <div class="status-item">
                <label>Status</label>
                <span><?= htmlspecialchars($statusOptions[$order['status']] ?? ucfirst($order['status'])) ?></span>
            </div>
            <div class="status-item">
                <label>Payment</label>
                <span><?= htmlspecialchars(ucfirst($order['payment_status'] ?? 'Pending')) ?></span>
            </div>
            <div class="status-item">
                <label>Method</label>
                <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))) ?></span>
            </div>
            <div class="status-item" style="text-align: right;">
                <label>Amount Due</label>
                <span>R<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
            </div>
        </div>

        <div class="grid-2">
            <div class="box">
                <h3>Billed To</h3>
                <div class="box-content">
                    <strong><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></strong>
                    <?= htmlspecialchars($order['customer_email'] ?? '') ?><br>
                    <?php if (!empty($order['customer_phone'])) echo htmlspecialchars($order['customer_phone']) . '<br>'; ?>
                    <?php if (!empty($customerAccountId)) echo '<span style="color:#6b7280; font-size:11px">ID: ' . htmlspecialchars($customerAccountId) . '</span>'; ?>
                </div>
            </div>
            <div class="box">
                <h3>Shipped To</h3>
                <div class="box-content">
                    <?= formatAddressCompact($shippingAddress); ?>
                </div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item):
                    $sku = $item['product_sku'] ?? '';
                    $brand = $item['product_brand'] ?? '';
                ?>
                <tr>
                    <td>
                        <div class="item-desc"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="item-meta">
                            <?php if ($sku) echo 'SKU: ' . htmlspecialchars($sku); ?>
                            <?php if ($brand) echo ' | ' . htmlspecialchars($brand); ?>
                            <?php if (!empty($item['product_weight'])) echo ' | ' . htmlspecialchars($item['product_weight']) . 'kg'; ?>
                        </div>
                    </td>
                    <td style="text-align: center;"><?= (int)$item['quantity']; ?></td>
                    <td style="text-align: right;">R<?= number_format($item['unit_price'] ?? 0, 2); ?></td>
                    <td style="text-align: right; font-weight: 600;">R<?= number_format($item['total_price'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-container">
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>R<?= number_format($order['subtotal'] ?? 0, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery</span>
                    <span>R<?= number_format($order['shipping_amount'] ?? 0, 2); ?></span>
                </div>
                <?php if (($order['tax_amount'] ?? 0) > 0): ?>
                <div class="total-row">
                    <span>Tax</span>
                    <span>R<?= number_format($order['tax_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                <div class="total-row" style="color: #16a34a;">
                    <span>Discount</span>
                    <span>-R<?= number_format($order['discount_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row grand-total">
                    <span>Total</span>
                    <span>R<?= number_format($order['total_amount'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-info">
                <p>Thank you for your business!</p>
                <p>For questions, contact <?= htmlspecialchars($storeEmail) ?></p>
            </div>
            <div class="qr-code">
                <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code" style="width:60px;height:60px;">
            </div>
        </div>
    </div>
</body>
</html>
