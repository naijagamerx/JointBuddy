<?php
/**
 * Thank You Page - CannaBuddy
 * Order confirmation page
 */

// Load global config for error display
require_once __DIR__ . '/../config.php';

session_start();
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/product_helpers.php';

// Include database
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/payment_methods_service.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    $db = null;
    error_log("Database connection failed: " . $e->getMessage());
}

// Get order details
$orderId = isset($_GET['order']) ? intval($_GET['order']) : 0;
$paymentPending = isset($_GET['payment']) && $_GET['payment'] === 'pending';
$order = null;
$orderItems = [];

// Check if we have a completed order in session
$completedOrder = $_SESSION['completed_order'] ?? null;
unset($_SESSION['completed_order']); // Clear after reading

if ($orderId && $db) {
    try {
        // Verify order exists
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Get order items with product image
            $stmt = $db->prepare("
                SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching order: " . $e->getMessage());
    }
}

// Check login status
$isLoggedIn = isset($_SESSION['user_id']);

// Get order number for display
$orderNumber = $order ? ($order['order_number'] ?? 'ORD-' . date('Y') . '-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT)) : 'N/A';

$pageTitle = "Order Confirmed - Thank You!";
include __DIR__ . '/../includes/header.php';
?>

<!-- Success Animation Styles -->
<style>
@keyframes checkmark {
    0% { stroke-dashoffset: 100; }
    100% { stroke-dashoffset: 0; }
}
@keyframes scale {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
.checkmark-circle {
    animation: scale 0.5s ease-in-out;
}
.checkmark-check {
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
    animation: checkmark 0.8s ease-in-out forwards;
    animation-delay: 0.3s;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.fade-in {
    animation: fadeInUp 0.6s ease-out forwards;
}
.delay-1 { animation-delay: 0.2s; opacity: 0; }
.delay-2 { animation-delay: 0.4s; opacity: 0; }
</style>

<?php if (!$order): ?>
    <!-- No Order Found -->
    <div class="bg-gray-100 min-h-screen py-8">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm p-8 text-center max-w-md mx-auto">
                <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-question text-3xl text-yellow-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Order Not Found</h1>
                <p class="text-gray-600 mb-6">We couldn't find the order you're looking for.</p>
                <a href="<?php echo shopUrl('/') ?>" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Order Confirmed -->
    <div class="bg-gray-100 min-h-screen py-8">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <!-- Success Header -->
            <div class="text-center mb-8">
                <!-- Animated Checkmark -->
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-4 checkmark-circle">
                    <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" class="checkmark-check text-green-600"></polyline>
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-2 fade-in">Thank You for Your Order!</h1>
                <p class="text-gray-600 fade-in delay-1">We've received your order and will process it right away.</p>
            </div>

            <!-- Order Confirmation Card -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden fade-in delay-2">
                <!-- Order Number Header -->
                <div class="bg-green-600 text-white p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <p class="text-green-100 text-sm">Order Number</p>
                            <p class="text-2xl font-bold"><?php echo htmlspecialchars($orderNumber) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-green-100 text-sm">Order Date</p>
                            <p class="text-lg font-semibold"><?php echo date('F j, Y', strtotime($order['created_at'])) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="p-6">
                    <!-- Email Confirmation Message -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-envelope text-blue-500 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium text-blue-900">Confirmation email sent!</p>
                                <p class="text-sm text-blue-700">We've sent the order details to <strong><?php echo htmlspecialchars($order['customer_email']) ?></strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status & Payment Method -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-6">
                        <div class="flex items-center">
                            <?php
                            $statusIcon = 'fa-clock';
                            $statusColor = 'text-yellow-500';
                            $statusText = 'Pending';

                            if ($order['status'] === 'processing') {
                                $statusIcon = 'fa-cog fa-spin';
                                $statusColor = 'text-blue-500';
                                $statusText = 'Processing';
                            } elseif ($order['status'] === 'shipped') {
                                $statusIcon = 'fa-truck';
                                $statusColor = 'text-purple-500';
                                $statusText = 'Shipped';
                            } elseif ($order['status'] === 'delivered') {
                                $statusIcon = 'fa-check-double';
                                $statusColor = 'text-green-500';
                                $statusText = 'Delivered';
                            }
                            ?>
                            <i class="fas <?php echo $statusIcon ?> <?php echo $statusColor ?> text-xl mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Status</p>
                                <p class="font-bold text-gray-900"><?php echo $statusText ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <?php
                            $paymentMethod = $order['payment_method'] ?? '';
                            $paymentIcon = 'fa-credit-card';
                            $paymentName = 'Payment Method';

                            if ($paymentMethod === 'bank_transfer') {
                                $paymentIcon = 'fa-university';
                                $paymentName = 'Bank Transfer';
                            } elseif ($paymentMethod === 'crypto') {
                                $paymentIcon = 'fa-coins';
                                $paymentName = 'Cryptocurrency';
                            } elseif ($paymentMethod === 'payfast') {
                                $paymentIcon = 'fa-credit-card';
                                $paymentName = 'PayFast';
                            } elseif ($paymentMethod === 'manual_custom') {
                                $paymentIcon = 'fa-money-bill';
                                $paymentName = 'Manual Payment';
                            } elseif ($paymentMethod === 'cod') {
                                $paymentIcon = 'fa-hand-holding-cash';
                                $paymentName = 'Cash on Delivery';
                            }
                            ?>
                            <i class="fas <?php echo $paymentIcon ?> text-gray-400 text-lg mr-3"></i>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Payment Method</p>
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($paymentName) ?></p>
                            </div>
                        </div>
                        <?php if ($paymentPending || $order['payment_status'] === 'pending'): ?>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                Payment Pending
                            </span>
                        <?php elseif ($order['payment_status'] === 'paid'): ?>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                Payment Confirmed
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-3">Order Items (<?php echo count($orderItems) ?>)</h3>
                    <div class="border rounded-lg divide-y mb-6">
                        <?php foreach ($orderItems as $item):
                            // Build product array for image helper
                            $productForImage = [
                                'images' => $item['product_images'] ?? '',
                                'image_1' => $item['product_images'] ?? '' // fallback
                            ];
                            $imageUrl = getProductMainImage($productForImage);
                            $hasImage = !empty($imageUrl) && strpos($imageUrl, 'placeholder') === false;
                        ?>
                            <div class="flex items-center p-4">
                                <?php if ($hasImage): ?>
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center mr-4 overflow-hidden flex-shrink-0">
                                        <img src="<?php echo htmlspecialchars($imageUrl) ?>"
                                             alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                             class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                        <i class="fas fa-box text-gray-400 text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($item['product_name'] ?? 'Product') ?></p>
                                    <p class="text-sm text-gray-500">Qty: <?php echo $item['quantity'] ?> × R <?php echo number_format($item['unit_price'] ?? 0, 2) ?></p>
                                </div>
                                <div class="font-medium text-gray-900 flex-shrink-0 ml-4">R <?php echo number_format($item['total_price'] ?? 0, 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Totals -->
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>R <?php echo number_format($order['subtotal'], 2) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span><?php echo ($order['shipping_amount'] ?? 0) > 0 ? 'R ' . number_format($order['shipping_amount'], 2) : 'FREE' ?></span>
                        </div>
                        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-R <?php echo number_format($order['discount_amount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
                            <span>Total</span>
                            <span class="text-green-600">R <?php echo number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo userUrl('orders/') ?>" class="inline-flex items-center justify-center bg-green-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-green-700 transition-colors">
                        <i class="fas fa-clipboard-list mr-2"></i>Track Your Order
                    </a>
                <?php endif; ?>
                <a href="<?php echo shopUrl('/') ?>" class="inline-flex items-center justify-center bg-green-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-green-700 transition-colors">
                    <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                </a>
            </div>

            <!-- Need Help -->
            <div class="text-center mt-8 text-gray-600 text-sm">
                <p>Need help with your order?</p>
                <a href="<?php echo url('/contact/') ?>" class="text-green-600 hover:text-green-700 font-medium">
                    Contact our support team
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
