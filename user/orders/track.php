<?php
/**
 * Order Tracking Page - User Dashboard
 * Track order status with detailed timeline
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

// Get order ID from query param or form
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$orderNumber = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

// Fetch all user orders for the list
$allOrders = [];
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT id, order_number, status, payment_status, created_at, total_amount
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$currentUser['id']]);
        $allOrders = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching orders: " . $e->getMessage());
    }
}

// Fetch order data for tracking
$order = null;
$orderItems = [];
$orderHistory = [];

if ($db && ($orderId || $orderNumber)) {
    try {
        if ($orderId) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$orderId, $currentUser['id']]);
        } else {
            $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
            $stmt->execute([$orderNumber, $currentUser['id']]);
        }
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $orderId = $order['id'];

            // Get order items
            $stmt = $db->prepare("
                SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Helper function to get product image URL
            function getProductImageUrl($productImages) {
                if (empty($productImages)) {
                    return '';
                }
                $imageParts = explode(',', $productImages);
                $firstImage = trim($imageParts[0]);
                if (empty($firstImage)) {
                    return '';
                }
                $imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
                return url($imagePath);
            }

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

// Status labels and colors
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

$statusStyles = [
    'new' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-200'],
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-200'],
    'approved' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'border' => 'border-indigo-200'],
    'preparing' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-200'],
    'ready' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-800', 'border' => 'border-teal-200'],
    'on_the_way' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'border' => 'border-orange-200'],
    'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-200'],
    'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'border' => 'border-red-200']
];

$paymentStyles = [
    'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
    'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800'],
    'refunded' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800']
];

$pageTitle = $order ? "Track Order #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) : "Track Your Order";
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
                <p class="text-green-100 text-sm">Track your order in real-time</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-shipping-fast text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4 space-y-6">
            <!-- Order Search Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Track Your Order</h2>
                <form action="<?= userUrl('/orders/track/') ?>" method="GET" class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="order_number" class="sr-only">Order Number</label>
                        <input type="text" id="order_number" name="order_number" value="<?= htmlspecialchars($orderNumber) ?>"
                               placeholder="Enter order number (e.g., ORD-123456)"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Track
                    </button>
                </form>
                <?php if ($orderNumber && !$order): ?>
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Order not found. Please check your order number and try again.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- All User Orders -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Your Orders (<?= count($allOrders) ?>)</h3>
                    <a href="<?= userUrl('/orders/') ?>" class="text-sm text-green-600 hover:text-green-700 font-medium">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <?php if (empty($allOrders)): ?>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-box text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-600">You haven't placed any orders yet.</p>
                        <a href="<?= shopUrl('/') ?>" class="inline-flex items-center mt-3 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($allOrders as $ord): ?>
                            <?php
                            $isActive = $order && $order['id'] == $ord['id'];
                            $ordStatusStyle = $statusStyles[$ord['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                            ?>
                            <div class="p-4 <?= $isActive ? 'bg-green-50 border-l-4 border-green-500' : 'hover:bg-gray-50' ?> transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-box text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                #<?= htmlspecialchars($ord['order_number'] ?? str_pad($ord['id'], 6, '0', STR_PAD_LEFT)) ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?= date('F j, Y', strtotime($ord['created_at'])) ?> •
                                                R <?= number_format($ord['total_amount'], 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $ordStatusStyle['bg'] . ' ' . $ordStatusStyle['text'] ?>">
                                            <?= $statusLabels[$ord['status']] ?? ucfirst($ord['status']) ?>
                                        </span>
                                        <a href="<?= userUrl('/orders/track/?id=' . $ord['id']) ?>"
                                           class="px-4 py-2 <?= $isActive ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 border border-green-200' ?> rounded-lg text-sm font-medium hover:opacity-90 transition-colors">
                                            <?= $isActive ? 'Viewing' : 'Track' ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Tracking Details (when order is selected) -->
            <?php if ($order): ?>
                <div class="space-y-6">
                    <!-- Back Button -->
                    <div>
                        <a href="<?= userUrl('/orders/track/') ?>" class="inline-flex items-center text-gray-600 hover:text-green-600 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                        </a>
                    </div>

                    <!-- Order Status Header -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-green-100 text-sm">Order Number</p>
                                    <h1 class="text-3xl font-bold">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                                    <?php if (!empty($order['order_number'])): ?>
                                        <p class="text-green-200 text-sm mt-1">Ref: <?= htmlspecialchars($order['order_number']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-4 md:mt-0 flex flex-col md:items-end">
                                    <?php 
                                        $currentStatusStyle = $statusStyles[$order['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'border' => 'border-gray-200'];
                                    ?>
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border <?= $currentStatusStyle['bg'] . ' ' . $currentStatusStyle['text'] . ' ' . $currentStatusStyle['border'] ?>">
                                        <?= $statusLabels[$order['status']] ?? ucfirst($order['status']) ?>
                                    </span>
                                    <p class="text-green-100 text-sm mt-2">
                                        Last updated: <?= date('F j, Y \a\t g:i A', strtotime($order['updated_at'] ?? $order['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Tracking Timeline -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-6">Order Progress</h2>

                        <?php
                        $steps = [
                            'new' => ['label' => 'Order Placed', 'description' => 'Your order has been received', 'icon' => 'fa-clipboard-check'],
                            'pending' => ['label' => 'Pending', 'description' => 'Awaiting confirmation', 'icon' => 'fa-clock'],
                            'approved' => ['label' => 'Approved', 'description' => 'Order confirmed by store', 'icon' => 'fa-check-circle'],
                            'preparing' => ['label' => 'Preparing', 'description' => 'Getting your items ready', 'icon' => 'fa-box'],
                            'ready' => ['label' => 'Ready', 'description' => 'Ready for pickup/shipment', 'icon' => 'fa-check-double'],
                            'on_the_way' => ['label' => 'On The Way', 'description' => 'Your order is on its way', 'icon' => 'fa-truck'],
                            'delivered' => ['label' => 'Delivered', 'description' => 'Order has been delivered', 'icon' => 'fa-home']
                        ];

                        $rejectedSteps = [
                            'new' => ['label' => 'Order Placed', 'description' => 'Your order has been received', 'icon' => 'fa-clipboard-check'],
                            'pending' => ['label' => 'Pending', 'description' => 'Awaiting confirmation', 'icon' => 'fa-clock'],
                            'rejected' => ['label' => 'Rejected', 'description' => 'Order was rejected', 'icon' => 'fa-times-circle']
                        ];

                        $isRejected = $order['status'] === 'rejected';
                        $stepsToUse = $isRejected ? $rejectedSteps : $steps;
                        $stepKeys = array_keys($stepsToUse);
                        $currentStepIndex = array_search($order['status'], $stepKeys);
                        ?>

                        <!-- Vertical Timeline -->
                        <div class="relative pl-8">
                            <!-- Vertical Connecting Line -->
                            <div class="absolute left-3 top-3 bottom-3 w-0.5 bg-gray-200"></div>

                            <?php foreach ($stepsToUse as $key => $step): ?>
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

                    <div class="grid lg:grid-cols-3 gap-6">
                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Order Items -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <div class="flex justify-between items-center">
                                        <h2 class="text-lg font-bold text-gray-900">Order Items</h2>
                                        <a href="<?= userUrl('/orders/view.php?id=' . $order['id']) ?>" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                            View Details <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <?php foreach ($orderItems as $item): ?>
                                        <div class="p-4 flex items-center">
                                            <div class="w-16 h-16 bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0 overflow-hidden">
                                                <?php
                                                    $imageUrl = getProductImageUrl($item['product_images'] ?? '');
                                                    if (!empty($imageUrl)):
                                                ?>
                                                    <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" class="w-12 h-12 object-cover rounded">
                                                <?php else: ?>
                                                    <i class="fas fa-cannabis text-green-600 text-xl"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 truncate"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></p>
                                                <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?> × R <?= number_format($item['unit_price'], 2) ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900">R <?= number_format($item['total_price'], 2) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total</span>
                                        <span class="font-bold text-green-600">R <?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status History -->
                            <?php if (!empty($orderHistory)): ?>
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h2 class="text-lg font-bold text-gray-900 mb-4">
                                        <i class="fas fa-history text-green-600 mr-2"></i>Status History
                                    </h2>
                                    <div class="relative">
                                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                                        <div class="space-y-6">
                                            <?php foreach ($orderHistory as $history): ?>
                                                <div class="relative pl-10">
                                                    <div class="absolute left-2 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
                                                    <div>
                                                        <p class="font-medium text-gray-900">
                                                            Status changed to: <span class="<?= $statusStyles[$history['new_status']]['text'] ?>"><?= $statusLabels[$history['new_status']] ?? ucfirst($history['new_status']) ?></span>
                                                        </p>
                                                        <p class="text-sm text-gray-500">
                                                            <?= date('F j, Y \a\t g:i A', strtotime($history['created_at'])) ?>
                                                            <?php if (!empty($history['admin_name'])): ?>
                                                                <span class="text-gray-400">by <?= htmlspecialchars($history['admin_name']) ?></span>
                                                            <?php endif; ?>
                                                        </p>
                                                        <?php if (!empty($history['note'])): ?>
                                                            <p class="text-sm text-gray-600 mt-1 bg-gray-50 p-2 rounded"><?= htmlspecialchars($history['note']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">
                            <!-- Order Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h2>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Order Date</span>
                                        <span class="font-medium text-gray-900"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment</span>
                                        <?php
                                        $paymentStyle = $paymentStyles[$order['payment_status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                                        $paymentLabel = ucfirst($order['payment_status'] ?? 'Unknown');
                                        ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $paymentStyle['bg'] . ' ' . $paymentStyle['text'] ?>">
                                            <?= $paymentLabel ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Items</span>
                                        <span class="font-medium text-gray-900"><?= count($orderItems) ?> items</span>
                                    </div>
                                    <div class="border-t border-gray-200 pt-3 flex justify-between">
                                        <span class="text-gray-900 font-medium">Total</span>
                                        <span class="font-bold text-green-600">R <?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200">
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
                                    <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>Shipping To
                                </h2>
                                <div class="text-gray-600 text-sm space-y-1">
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($shippingAddress['name'] ?? $order['customer_name']) ?></p>
                                    <p><?= htmlspecialchars($shippingAddress['address_line_1'] ?? $shippingAddress['street'] ?? '') ?></p>
                                    <?php if (!empty($shippingAddress['address_line_2'])): ?>
                                        <p><?= htmlspecialchars($shippingAddress['address_line_2']) ?></p>
                                    <?php endif; ?>
                                    <p>
                                        <?= htmlspecialchars($shippingAddress['city'] ?? '') ?>
                                        <?= !empty($shippingAddress['postal_code']) ? ' ' . htmlspecialchars($shippingAddress['postal_code']) : '' ?>
                                    </p>
                                    <p><?= htmlspecialchars($shippingAddress['country'] ?? 'South Africa') ?></p>
                                </div>
                            </div>

                            <!-- Need Help -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-bold text-gray-900 mb-4">Need Help?</h2>
                                <div class="space-y-3">
                                    <a href="<?= url('/contact/') ?>" class="block w-full bg-gray-100 text-gray-700 py-3 rounded-lg font-medium text-center hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-headset mr-2"></i>Contact Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
