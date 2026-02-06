<?php
/**
 * Order Details View - User Dashboard
 * Detailed view of a single order with tracking
 */
require_once __DIR__ . '/../../includes/url_helper.php';
session_start();

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

// Include database
require_once __DIR__ . '/../../includes/database.php';

// Helper function to get product image URL
// Helper function to get product image URL
// Helper function to get product image URL
function getProductImageUrl($productImages): string {
    if (empty($productImages)) {
        return '';
    }
    $imageParts = explode(',', $productImages);
    $firstImage = trim($imageParts[0]);
    if (empty($firstImage)) {
        return '';
    }
    
    // Check if it's already a full URL
    if (filter_var($firstImage, FILTER_VALIDATE_URL)) {
        return $firstImage;
    }
    
    // Robust handling for local vs production paths
    $path = parse_url($firstImage, PHP_URL_PATH);
    $basePath = getAppBasePath();
    
    // Remove base path if present to get clean relative path
    if ($basePath && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
    
    return url(ltrim($path, '/'));
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    $db = null;
    error_log("Database connection failed: " . $e->getMessage());
}

// Fetch order
$order = null;
$orderItems = [];
$orderHistory = [];

if ($db && $orderId) {
    try {
        // Get order (verify it belongs to the user)
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $currentUser['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Get order items
            $stmt = $db->prepare("
                SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get order status history
            $stmt = $db->prepare("
                SELECT h.*, a.username as admin_name
                FROM order_status_history h
                LEFT JOIN admin_users a ON h.changed_by = a.id
                WHERE h.order_id = ?
                ORDER BY h.created_at ASC
            ");
            $stmt->execute([$orderId]);
            $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching order: " . $e->getMessage());
    }
}

// View Logic Constants
$statusStyles = [
    'new' => 'bg-blue-100 text-blue-800 border-blue-200',
    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'approved' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'preparing' => 'bg-purple-100 text-purple-800 border-purple-200',
    'ready' => 'bg-teal-100 text-teal-800 border-teal-200',
    'on_the_way' => 'bg-orange-100 text-orange-800 border-orange-200',
    'delivered' => 'bg-green-100 text-green-800 border-green-200',
    'rejected' => 'bg-red-100 text-red-800 border-red-200'
];

$statusLabels = [
    'new' => 'New',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'on_the_way' => 'On The Way',
    'delivered' => 'Delivered',
    'rejected' => 'Rejected'
];

$paymentStyles = [
    'paid' => 'bg-green-100 text-green-800',
    'pending' => 'bg-yellow-100 text-yellow-800',
    'failed' => 'bg-red-100 text-red-800',
    'refunded' => 'bg-gray-100 text-gray-800'
];

$paymentLabels = [
    'paid' => 'Paid',
    'pending' => 'Pending',
    'failed' => 'Failed',
    'refunded' => 'Refunded'
];

// Calculate derived specific values
$statusStyle = $order ? ($statusStyles[$order['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200') : '';
$statusLabel = $order ? ($statusLabels[$order['status']] ?? ucfirst($order['status'])) : '';
$paymentStyle = $order ? ($paymentStyles[$order['payment_status']] ?? 'bg-gray-100 text-gray-800') : '';
$paymentLabel = $order ? ($paymentLabels[$order['payment_status']] ?? 'Unknown') : '';

$itemCount = 0;
foreach ($orderItems as $item) {
    $itemCount += $item['quantity'];
}

$trackingSteps = [
    'new' => ['label' => 'Order Placed', 'description' => 'Your order has been received', 'icon' => 'fa-clipboard-check'],
    'pending' => ['label' => 'Pending', 'description' => 'Awaiting confirmation', 'icon' => 'fa-clock'],
    'approved' => ['label' => 'Approved', 'description' => 'Order confirmed', 'icon' => 'fa-check'],
    'preparing' => ['label' => 'Preparing', 'description' => 'Getting your items ready', 'icon' => 'fa-box'],
    'ready' => ['label' => 'Ready', 'description' => 'Ready for pickup/shipment', 'icon' => 'fa-check-double'],
    'on_the_way' => ['label' => 'On The Way', 'description' => 'Your order is on its way', 'icon' => 'fa-truck'],
    'delivered' => ['label' => 'Delivered', 'description' => 'Order has been delivered', 'icon' => 'fa-home']
];
$stepKeys = array_keys($trackingSteps);
$currentStepIndex = -1;
if ($order) {
    $currentStepIndex = array_search($order['status'], $stepKeys);
    if ($order['status'] === 'delivered') {
        $currentStepIndex = array_search('on_the_way', $stepKeys);
    }
}

$pageTitle = $order ? "Order #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) : "Order Not Found";
$currentPage = "orders";

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-green-100 text-sm">View and track your order details</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-box text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="<?= userUrl('/orders/') ?>" class="inline-flex items-center text-gray-600 hover:text-green-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
            </div>

            <?php if (!$order): ?>
                <!-- Order Not Found -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Order Not Found</h1>
                    <p class="text-gray-600 mb-6">The order you're looking for doesn't exist or you don't have permission to view it.</p>
                    <a href="<?= userUrl('/orders/') ?>" class="inline-flex items-center bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>View My Orders
                    </a>
                </div>
            <?php else: ?>


                <!-- Order Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-green-100 text-sm">Order Number</p>
                                <h1 class="text-3xl font-bold">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                            </div>
                            <div class="mt-4 md:mt-0 flex flex-col md:items-end">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border <?= $statusStyle ?>">
                                    <?= $statusLabel ?>
                                </span>
                                <p class="text-green-100 text-sm mt-2">
                                    Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Order Tracking -->
                        <?php if (in_array($order['status'], ['new', 'pending', 'approved', 'preparing', 'ready', 'on_the_way'])): ?>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-bold text-gray-900 mb-6">Order Tracking</h2>

                                <div class="relative">
                                    <!-- Vertical Timeline -->
                                    <div class="relative pl-8">
                                        <!-- Vertical Connecting Line -->
                                        <div class="absolute left-3 top-3 bottom-3 w-0.5 bg-gray-200"></div>

                                        <?php foreach ($trackingSteps as $key => $step): ?>
                                            <?php
                                            $stepIndex = array_search($key, $stepKeys);
                                            $isCompleted = $stepIndex <= $currentStepIndex;
                                            $isCurrent = $stepIndex === $currentStepIndex;
                                            ?>
                                            <div class="relative mb-5 last:mb-0">
                                                <!-- Step Circle -->
                                                <div class="absolute left-[-20px] w-6 h-6 rounded-full flex items-center justify-center <?= $isCompleted ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-400' ?> <?= $isCurrent ? 'ring-4 ring-green-100' : '' ?>">
                                                    <?php if ($isCompleted && !$isCurrent): ?>
                                                        <i class="fas fa-check text-xs"></i>
                                                    <?php elseif ($isCurrent): ?>
                                                        <i class="fas <?= $step['icon'] ?> text-xs"></i>
                                                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-xs"><?= $stepIndex + 1 ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Step Content -->
                                                <div class="bg-<?= $isCompleted ? 'green' : 'gray' ?>-50 rounded-lg p-3 ml-2">
                                                    <h3 class="font-semibold <?= $isCompleted ? 'text-green-700' : 'text-gray-500' ?>">
                                                        <?= $step['label'] ?>
                                                    </h3>
                                                    <p class="text-sm <?= $isCompleted ? 'text-green-600' : 'text-gray-400' ?>">
                                                        <?= $step['description'] ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Order Items -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h2 class="text-lg font-bold text-gray-900">Order Items (<?= $itemCount ?>)</h2>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="p-6 flex items-center">
                                        <div class="w-20 h-20 bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                            <?php
                                                $imageUrl = getProductImageUrl($item['product_images'] ?? '');
                                                if (!empty($imageUrl)):
                                            ?>
                                                <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" class="w-16 h-16 object-cover rounded">
                                            <?php else: ?>
                                                <span class="text-3xl">📦</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-gray-900 mb-1">
                                                <?php if (!empty($item['product_slug'])): ?>
                                                    <a href="<?= productUrl($item['product_slug']) ?>" class="hover:text-green-600 transition-colors">
                                                        <?= htmlspecialchars($item['product_name'] ?? 'Product') ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($item['product_name'] ?? 'Product') ?>
                                                <?php endif; ?>
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                Qty: <?= $item['quantity'] ?> × R <?= number_format($item['unit_price'], 2) ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-gray-900">R <?= number_format($item['total_price'], 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Order Status History -->
                        <?php if (!empty($orderHistory)): ?>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-bold text-gray-900 mb-4">Order History</h2>
                                <div class="space-y-4">
                                    <?php foreach ($orderHistory as $history): ?>
                                        <div class="flex items-start space-x-3">
                                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">
                                                    Status changed to: <?= $statusLabels[$history['new_status']] ?? ucfirst($history['new_status']) ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?= date('F j, Y \a\t g:i A', strtotime($history['created_at'])) ?>
                                                    <?php if (!empty($history['admin_name'])): ?>
                                                        by <?= htmlspecialchars($history['admin_name']) ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if (!empty($history['note'])): ?>
                                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($history['note']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Information -->
                        <?php if ($order['payment_method'] === 'bank_transfer' && $order['payment_status'] === 'pending'): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                <h3 class="text-lg font-bold text-blue-900 mb-4">
                                    <i class="fas fa-university mr-2"></i>Bank Transfer Details
                                </h3>
                                <div class="grid md:grid-cols-2 gap-4 text-blue-800">
                                    <div>
                                        <p class="text-sm text-blue-600">Bank</p>
                                        <p class="font-semibold">First National Bank (FNB)</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-blue-600">Account Name</p>
                                        <p class="font-semibold">Store Account</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-blue-600">Account Number</p>
                                        <p class="font-semibold">62XXXXXXXXX</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-blue-600">Branch Code</p>
                                        <p class="font-semibold">250655</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-sm text-blue-600">Payment Reference</p>
                                        <p class="font-bold text-lg">ORDER-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                                    </div>
                                </div>
                                <div class="mt-4 p-3 bg-blue-100 rounded-lg">
                                    <p class="text-sm text-blue-700">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Please include your order number as the payment reference. Your order will be processed once payment is confirmed.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Order Summary -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h2>

                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span class="font-medium text-gray-900">R <?= number_format($order['subtotal'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span class="font-medium text-gray-900">
                                        <?= $order['shipping_amount'] > 0 ? 'R ' . number_format($order['shipping_amount'], 2) : 'FREE' ?>
                                    </span>
                                </div>
                                <?php if (($order['tax_amount'] ?? 0) > 0): ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Tax</span>
                                    <span class="font-medium text-gray-900">R <?= number_format($order['tax_amount'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                                <div class="flex justify-between text-green-600">
                                    <span>Discount</span>
                                    <span class="font-medium">-R <?= number_format($order['discount_amount'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="border-t border-gray-200 pt-3 flex justify-between">
                                    <span class="text-lg font-bold text-gray-900">Total</span>
                                    <span class="text-xl font-bold text-green-600">R <?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>

                            <!-- Payment Status -->
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Payment</span>
                                    <?php if (!empty($paymentLabel)): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $paymentStyle ?>">
                                            <?= $paymentLabel ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?= ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?>
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <a href="<?= userUrl('/invoices/view.php?id=' . $order['id']) ?>" class="block w-full bg-green-600 text-white py-3 rounded-lg font-medium text-center hover:bg-green-700 transition-colors">
                                    <i class="fas fa-file-invoice mr-2"></i>View Invoice
                                </a>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <?php
                        $shippingAddress = json_decode($order['shipping_address'] ?? '{}', true);
                        if (empty($shippingAddress)) {
                            $shippingAddress = [
                                'name' => $order['customer_name'],
                                'address_line_1' => $order['shipping_address'] ?? '',
                                'city' => $order['shipping_city'] ?? '',
                                'postal_code' => $order['shipping_postal_code'] ?? ''
                            ];
                        }
                        ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">
                                <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>Shipping Address
                            </h2>
                            <div class="text-gray-600 space-y-1">
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($shippingAddress['name'] ?? $order['customer_name']) ?></p>
                                <p><?= htmlspecialchars($shippingAddress['address_line_1'] ?? $shippingAddress['street'] ?? '') ?></p>
                                <?php if (!empty($shippingAddress['address_line_2'])): ?>
                                    <p><?= htmlspecialchars($shippingAddress['address_line_2']) ?></p>
                                <?php endif; ?>
                                <p><?= htmlspecialchars($shippingAddress['city'] ?? '') ?>
                                    <?= !empty($shippingAddress['state']) ? ', ' . htmlspecialchars($shippingAddress['state']) : '' ?>
                                    <?= !empty($shippingAddress['postal_code']) ? ' ' . htmlspecialchars($shippingAddress['postal_code']) : '' ?>
                                </p>
                                <p><?= htmlspecialchars($shippingAddress['country'] ?? 'South Africa') ?></p>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">
                                <i class="fas fa-user text-green-600 mr-2"></i>Contact Information
                            </h2>
                            <div class="text-gray-600 space-y-2">
                                <p>
                                    <i class="fas fa-envelope text-gray-400 mr-2 w-4"></i>
                                    <?= htmlspecialchars($order['customer_email']) ?>
                                </p>
                                <?php if (!empty($order['customer_phone'])): ?>
                                <p>
                                    <i class="fas fa-phone text-gray-400 mr-2 w-4"></i>
                                    <?= htmlspecialchars($order['customer_phone']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Need Help -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">Need Help?</h2>
                            <div class="space-y-3">
                                <a href="<?= url('/contact/') ?>" class="block w-full bg-gray-100 text-gray-700 py-3 rounded-lg font-medium text-center hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-headset mr-2"></i>Contact Support
                                </a>
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <a href="<?= userUrl('/returns/?order=' . $order['id']) ?>" class="block w-full border-2 border-gray-200 text-gray-700 py-3 rounded-lg font-medium text-center hover:border-gray-300 transition-colors">
                                        <i class="fas fa-undo mr-2"></i>Request Return
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
