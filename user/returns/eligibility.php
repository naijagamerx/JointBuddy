<?php
/**
 * Return Eligibility Check - CannaBuddy
 * Check if an order is eligible for return
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
    redirect('/user/login/?redirect=' . urlencode('/user/returns/'));
}

// Include database
require_once __DIR__ . '/../../includes/database.php';

$db = null;
$order = null;
$orderItems = [];
$isEligible = false;
$ineligibilityReason = '';
$settings = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE category = 'returns'");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

$eligibilityDays = isset($settings['return_eligibility_days']) ? (int)$settings['return_eligibility_days'] : 14;
$policyText = $settings['return_policy_text'] ?? '';

// Get order_id from URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$orderId) {
    redirect('/user/returns/');
}

if ($db) {
    try {
        // Get order details
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $currentUser['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            redirect('/user/returns/');
        }

        // Check eligibility
        $daysSinceDelivery = (int)(new DateTime($order['updated_at']))->diff(new DateTime())->days;

        if ($order['status'] !== 'delivered') {
            $isEligible = false;
            $ineligibilityReason = 'This order has not been delivered yet.';
        } elseif ($daysSinceDelivery > $eligibilityDays) {
            $isEligible = false;
            $ineligibilityReason = "The {$eligibilityDays}-day return window has expired. This order was delivered {$daysSinceDelivery} days ago.";
        } else {
            // Check for existing active return
            $stmt = $db->prepare("SELECT COUNT(*) FROM returns WHERE order_id = ? AND status NOT IN ('cancelled', 'rejected')");
            $stmt->execute([$orderId]);
            if ($stmt->fetchColumn() > 0) {
                $isEligible = false;
                $ineligibilityReason = 'This order already has an active return request.';
            } else {
                $isEligible = true;
            }
        }

        // Get order items if eligible
        if ($order) {
            $stmt = $db->prepare("
                SELECT oi.*, p.images as product_images, p.id as product_id, p.sku as product_sku
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (Exception $e) {
        error_log("Error checking eligibility: " . $e->getMessage());
    }
}

// Helper function for product image
function getProductImageUrl($images, $productId) {
    if (!empty($images)) {
        $imageParts = explode(',', $images);
        $firstImage = trim($imageParts[0]);
        if (!empty($firstImage)) {
            $imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
            return url($imagePath);
        }
    }
    return url('assets/images/placeholder.png');
}

$pageTitle = "Return Eligibility";
$currentPage = "returns";

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Back Button -->
    <a href="<?= userUrl('/returns/') ?>" class="inline-flex items-center text-gray-600 hover:text-green-600 mb-6 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Back to Returns
    </a>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Header -->
                <div class="border-b border-gray-200 px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-900">Return Eligibility Check</h1>
                    <p class="text-gray-600 mt-1">Order #<?= htmlspecialchars($order['order_number'] ?? '') ?></p>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <?php if (!$isEligible): ?>
                        <!-- Not Eligible -->
                        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-times-circle text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-red-800">Not Eligible for Return</h3>
                                    <p class="text-red-700 mt-1"><?= htmlspecialchars($ineligibilityReason) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center py-8">
                            <a href="<?= userUrl('/returns/') ?>"
                               class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Returns
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Eligible - Show Order Details -->
                        <?php
                        $daysRemaining = $eligibilityDays - (int)(new DateTime($order['updated_at']))->diff(new DateTime())->days;
                        ?>

                        <!-- Eligibility Banner -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                                    <div>
                                        <h3 class="text-lg font-medium text-green-800">Eligible for Return</h3>
                                        <p class="text-green-700 text-sm">You have <?= $daysRemaining ?> day<?= $daysRemaining !== 1 ? 's' : '' ?> remaining to submit your return.</p>
                                    </div>
                                </div>
                                <a href="<?= userUrl('/returns/request.php?order_id=' . $orderId) ?>"
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                    <i class="fas fa-undo mr-2"></i>Continue to Return
                                </a>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Items in This Order</h3>
                        <div class="space-y-4 mb-8">
                            <?php foreach ($orderItems as $item): ?>
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="w-16 h-16 bg-white rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        <img src="<?= getProductImageUrl($item['product_images'] ?? '', $item['product_id'] ?? '') ?>"
                                             class="w-full h-full object-cover"
                                             alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-base font-medium text-gray-900"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></h4>
                                        <?php if (!empty($item['product_sku'])): ?>
                                            <p class="text-sm text-gray-500">SKU: <?= htmlspecialchars($item['product_sku']) ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-600">Qty: <?= $item['quantity'] ?> × R <?= number_format($item['unit_price'], 2) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-gray-900">R <?= number_format($item['total_price'], 2) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Order Total -->
                        <div class="border-t border-gray-200 pt-4 mb-6">
                            <div class="flex justify-end">
                                <div class="w-64">
                                    <div class="flex justify-between py-2">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="font-medium">R <?= number_format($order['subtotal'], 2) ?></span>
                                    </div>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <div class="flex justify-between py-2">
                                            <span class="text-gray-600">Discount</span>
                                            <span class="font-medium text-green-600">-R <?= number_format($order['discount_amount'], 2) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between py-2">
                                        <span class="text-gray-600">Shipping</span>
                                        <span class="font-medium">R <?= number_format($order['shipping_amount'], 2) ?></span>
                                    </div>
                                    <div class="flex justify-between py-2 border-t border-gray-200 mt-2">
                                        <span class="text-lg font-bold text-gray-900">Total</span>
                                        <span class="text-lg font-bold text-green-600">R <?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Return Policy Summary -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-800 mb-3">
                                <i class="fas fa-info-circle mr-2"></i>Return Policy
                            </h3>
                            <ul class="text-sm text-blue-700 space-y-2">
                                <li><i class="fas fa-check text-blue-500 mr-2"></i>Returns must be initiated within <?= $eligibilityDays ?> days of delivery</li>
                                <li><i class="fas fa-check text-blue-500 mr-2"></i>Products must be unused and in original packaging</li>
                                <li><i class="fas fa-check text-blue-500 mr-2"></i>You can return via courier collection or drop-off</li>
                                <li><i class="fas fa-check text-blue-500 mr-2"></i>Refunds processed within 5-7 business days</li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-4 mt-6">
                            <a href="<?= userUrl('/returns/') ?>"
                               class="px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                Cancel
                            </a>
                            <a href="<?= userUrl('/returns/request.php?order_id=' . $orderId) ?>"
                               class="px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                <i class="fas fa-undo mr-2"></i>Continue to Return
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>
