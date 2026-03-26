<?php
/**
 * Return Details View - CannaBuddy
 * View details of a return request
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

$return = null;
$returnItems = [];
$order = null;
$statusHistory = [];

// Get return_id from URL
$returnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$returnId) {
    userUrl('/returns/');
    exit;
}

try {
    // Get return details
        $stmt = $db->prepare("
            SELECT r.*, o.order_number, o.total_amount as order_total, o.shipping_address as shipping_address_json
            FROM returns r
            JOIN orders o ON r.order_id = o.id
            WHERE r.id = ? AND r.user_id = ?
        ");
        $stmt->execute([$returnId, $currentUser['id']]);
        $return = $stmt->fetch(PDO::FETCH_ASSOC);

        // Parse shipping address JSON
        if (!empty($return['shipping_address_json'])) {
            $shippingAddress = json_decode($return['shipping_address_json'], true);
            $return['shipping_address'] = $shippingAddress['street'] ?? '';
            $return['shipping_city'] = $shippingAddress['city'] ?? '';
            $return['shipping_postal_code'] = $shippingAddress['postal_code'] ?? '';
        } else {
            $return['shipping_address'] = '';
            $return['shipping_city'] = '';
            $return['shipping_postal_code'] = '';
        }

        if (!$return) {
            userUrl('/returns/');
            exit;
        }

        // Get return items
        $stmt = $db->prepare("
            SELECT ri.*, oi.product_name, oi.product_sku, oi.unit_price, p.images as product_images, p.id as product_id
            FROM return_items ri
            JOIN order_items oi ON ri.order_item_id = oi.id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE ri.return_id = ?
        ");
        $stmt->execute([$returnId]);
        $returnItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get status history
        $stmt = $db->prepare("
            SELECT * FROM return_status_history
            WHERE return_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$returnId]);
        $statusHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Error fetching return: " . $e->getMessage());
    }

// Helper functions
function getReturnStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'received' => 'bg-purple-100 text-purple-800',
        'refunded' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-800'
    ];
    $labels = [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'received' => 'Item Received',
        'refunded' => 'Refunded',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled'
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . $label . '</span>';
}

function getProductImageUrl($images) {
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

function getReasonLabel($reason) {
    $reasons = [
        'damaged' => 'Product delivered in damaged condition',
        'not_working' => 'Product doesn\'t work properly',
        'not_as_described' => 'Product not as described',
        'changed_mind' => 'Changed my mind',
        'other' => 'Other'
    ];
    return $reasons[$reason] ?? ucfirst($reason);
}

$pageTitle = "Return Details";
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
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h1 class="text-2xl font-bold text-gray-900">Return #<?= htmlspecialchars($return['return_number'] ?? '') ?></h1>
                                <?= getReturnStatusBadge($return['status'] ?? 'pending') ?>
                            </div>
                            <p class="text-gray-600 mt-1">Order #<?= htmlspecialchars($return['order_number'] ?? '') ?></p>
                        </div>
                        <div class="mt-4 md:mt-0 text-sm text-gray-500">
                            <i class="far fa-calendar mr-1"></i>Submitted <?= date('F j, Y', strtotime($return['created_at'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Return Items -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Items Being Returned</h3>
                        <div class="space-y-4">
                            <?php foreach ($returnItems as $item): ?>
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="w-16 h-16 bg-white rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        <img src="<?= getProductImageUrl($item['product_images'] ?? '') ?>"
                                             class="w-full h-full object-cover"
                                             alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-base font-medium text-gray-900"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></h4>
                                        <?php if (!empty($item['product_sku'])): ?>
                                            <p class="text-sm text-gray-500">SKU: <?= htmlspecialchars($item['product_sku']) ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-600">Quantity: <?= $item['quantity'] ?></p>
                                        <p class="text-sm text-gray-600">R <?= number_format($item['unit_price'], 2) ?> each</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-gray-900">R <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Total -->
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <div class="flex justify-end">
                                    <div class="w-48">
                                        <div class="flex justify-between py-2">
                                            <span class="text-gray-600">Total Refund</span>
                                            <span class="text-xl font-bold text-green-600">R <?= number_format($return['total_amount'] ?? 0, 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Return Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Return Details -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Return Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-500">Reason</span>
                                    <p class="text-gray-900"><?= htmlspecialchars(getReasonLabel($return['reason_type'] ?? '')) ?></p>
                                </div>
                                <?php if (!empty($return['reason_details'])): ?>
                                <div>
                                    <span class="text-sm text-gray-500">Details</span>
                                    <p class="text-gray-900"><?= htmlspecialchars($return['reason_details']) ?></p>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <span class="text-sm text-gray-500">Condition</span>
                                    <p class="text-gray-900"><?= ($return['product_not_used'] ?? 0) ? 'Unused/Unopened' : 'Used' ?></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Return Method</span>
                                    <p class="text-gray-900"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $return['courier_method'] ?? ''))) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Information -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-500">Order Number</span>
                                    <p class="text-gray-900">#<?= htmlspecialchars($return['order_number'] ?? '') ?></p>
                                </div>
                                <?php if (!empty($return['shipping_address'])): ?>
                                <div>
                                    <span class="text-sm text-gray-500">Shipping Address</span>
                                    <p class="text-gray-900">
                                        <?= htmlspecialchars($return['shipping_address']) ?><br>
                                        <?= htmlspecialchars($return['shipping_city']) ?> <?= htmlspecialchars($return['shipping_postal_code']) ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($return['refund_method'])): ?>
                                <div>
                                    <span class="text-sm text-gray-500">Refund Method</span>
                                    <p class="text-gray-900"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $return['refund_method']))) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($return['refunded_at'])): ?>
                                <div>
                                    <span class="text-sm text-gray-500">Refunded On</span>
                                    <p class="text-gray-900"><?= date('F j, Y', strtotime($return['refunded_at'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Return Timeline</h3>
                        <div class="relative">
                            <?php
                            $steps = [
                                'pending' => 'Return Submitted',
                                'approved' => 'Return Approved',
                                'received' => 'Item Received',
                                'refunded' => 'Refund Processed'
                            ];
                            $currentStatus = $return['status'] ?? 'pending';
                            $stepOrder = ['pending', 'approved', 'received', 'refunded'];
                            $currentIndex = array_search($currentStatus, $stepOrder);
                            if (in_array($currentStatus, ['rejected', 'cancelled'])) {
                                $currentIndex = -1; // Terminal state
                            }
                            ?>

                            <div class="flex items-center justify-between">
                                <?php foreach ($stepOrder as $index => $step): ?>
                                    <?php $isCompleted = $index < $currentIndex; ?>
                                    <?php $isCurrent = $index === $currentIndex; ?>
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                                            <?= $isCompleted ? 'bg-green-600 text-white' : ($isCurrent ? 'bg-green-600 text-white ring-4 ring-green-100' : 'bg-gray-200 text-gray-400') ?>">
                                            <?php if ($isCompleted): ?>
                                                <i class="fas fa-check text-sm"></i>
                                            <?php else: ?>
                                                <span class="text-sm font-medium"><?= $index + 1 ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs mt-2 <?= $isCompleted || $isCurrent ? 'text-green-600 font-medium' : 'text-gray-400' ?>">
                                            <?= $steps[$step] ?>
                                        </span>
                                        <?php if (isset($statusHistory[$index])): ?>
                                            <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($statusHistory[$index]['created_at'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($index < count($stepOrder) - 1): ?>
                                        <div class="flex-1 h-1 mx-2 <?= $isCompleted ? 'bg-green-600' : 'bg-gray-200' ?>"></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- Rejected/Cancelled State -->
                            <?php if (in_array($currentStatus, ['rejected', 'cancelled'])): ?>
                                <div class="flex justify-center mt-4">
                                    <div class="px-4 py-2 bg-red-100 text-red-800 rounded-lg">
                                        <i class="fas fa-times-circle mr-2"></i>
                                        <?= $currentStatus === 'rejected' ? 'Return Rejected' : 'Return Cancelled' ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <?php if (!empty($return['admin_notes'])): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3">
                            <i class="fas fa-comment-dots mr-2"></i>Note from Support
                        </h3>
                        <p class="text-blue-700"><?= nl2br(htmlspecialchars($return['admin_notes'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-4">
                        <?php if ($return['status'] === 'pending'): ?>
                            <a href="<?= userUrl('/returns/cancel.php?id=' . $returnId) ?>"
                               class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                               onclick="return confirm('Are you sure you want to cancel this return request?');">
                                <i class="fas fa-times mr-2"></i>Cancel Return
                            </a>
                        <?php endif; ?>

                        <a href="<?= userUrl('/contact/') ?>"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-headset mr-2"></i>Contact Support
                        </a>

                        <a href="<?= userUrl('/orders/view.php?id=' . $return['order_id']) ?>"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>View Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>
