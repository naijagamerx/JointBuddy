<?php
/**
 * Admin Return Details View - CannaBuddy
 * Manage and update return requests
 */
require_once __DIR__ . '/../../includes/admin_error_catcher.php';

// Initialize error handling
setupAdminErrorHandling();

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$returnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$returnId) {
    redirect('/admin/returns/');
}

$return = null;
$returnItems = [];
$statusHistory = [];
$error = '';
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    $adminNote = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    $refundAmount = isset($_POST['refund_amount']) ? (float)$_POST['refund_amount'] : 0;
    $refundMethod = isset($_POST['refund_method']) ? $_POST['refund_method'] : '';

    if (empty($newStatus)) {
        $error = 'Please select a status.';
    } else {
        try {
            // Get current return status
            $stmt = $db->prepare("SELECT status, total_amount FROM returns WHERE id = ?");
            $stmt->execute([$returnId]);
            $currentReturn = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentReturn) {
                $error = 'Return not found.';
            } else {
                $oldStatus = $currentReturn['status'];

                // Validate status transition
                $allowedTransitions = [
                    'pending' => ['approved', 'rejected', 'cancelled'],
                    'approved' => ['received', 'cancelled'],
                    'received' => ['refunded']
                ];

                if (!isset($allowedTransitions[$oldStatus]) || !in_array($newStatus, $allowedTransitions[$oldStatus])) {
                    $error = "Cannot transition from '$oldStatus' to '$newStatus'.";
                } else {
                    // Update status
                    if ($newStatus === 'refunded') {
                        $stmt = $db->prepare("
                            UPDATE returns SET status = ?, admin_notes = ?, refund_amount = ?, refund_method = ?, refunded_at = NOW(), updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$newStatus, $adminNote, $refundAmount, $refundMethod, $returnId]);
                    } else {
                        $stmt = $db->prepare("
                            UPDATE returns SET status = ?, admin_notes = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$newStatus, $adminNote, $returnId]);
                    }

                    // Log status change
                    $stmt = $db->prepare("
                        INSERT INTO return_status_history (return_id, old_status, new_status, admin_note, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$returnId, $oldStatus, $newStatus, $adminNote, $adminAuth->getAdminId()]);

                    $success = 'Return status updated successfully.';
                    $_GET['id'] = $returnId; // Refresh
                }
            }

        } catch (Exception $e) {
            $error = AppError::handleDatabaseError($e, 'Error updating return');
        }
    }
}

// Fetch return details
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT r.*, o.order_number, o.shipping_address,
                   u.name as customer_name, u.email as customer_email, u.phone as customer_phone
            FROM returns r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$returnId]);
        $return = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$return) {
            redirect('/admin/returns/');
        }

        // Decode shipping address JSON
        $shippingAddress = json_decode($return['shipping_address'] ?? '{}', true);
        $return['shipping_city'] = $shippingAddress['city'] ?? '';
        $return['shipping_postal_code'] = $shippingAddress['zip'] ?? '';
        $return['shipping_phone'] = $shippingAddress['phone'] ?? '';

        // Get return items
        $stmt = $db->prepare("
            SELECT ri.*, oi.product_name, oi.unit_price, p.images as product_images, p.id as product_id
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
}

// Helper functions
function getAdminReturnStatusBadge($status) {
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
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $label . '</span>';
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

// Get available status transitions
$currentStatus = $return['status'] ?? 'pending';
$allowedTransitions = [
    'pending' => [
        ['value' => 'approved', 'label' => 'Approve Return', 'class' => 'bg-blue-600 text-white hover:bg-blue-700'],
        ['value' => 'rejected', 'label' => 'Reject Return', 'class' => 'bg-red-600 text-white hover:bg-red-700'],
        ['value' => 'cancelled', 'label' => 'Cancel Return', 'class' => 'bg-gray-600 text-white hover:bg-gray-700']
    ],
    'approved' => [
        ['value' => 'received', 'label' => 'Mark as Received', 'class' => 'bg-purple-600 text-white hover:bg-purple-700'],
        ['value' => 'cancelled', 'label' => 'Cancel Return', 'class' => 'bg-gray-600 text-white hover:bg-gray-700']
    ],
    'received' => [
        ['value' => 'refunded', 'label' => 'Process Refund', 'class' => 'bg-green-600 text-white hover:bg-green-700']
    ]
];

// Generate content for admin layout
ob_start();
?>

<!-- Content Container -->
<div class="w-full max-w-7xl mx-auto">
                <?php if (function_exists('renderAdminErrors')) renderAdminErrors(); ?>
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700"><?= safe_html($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700"><?= safe_html($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Return Items -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Items Being Returned</h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <?php foreach ($returnItems as $item): ?>
                                        <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                            <div class="w-16 h-16 bg-white rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                                <img src="<?= getProductImageUrl($item['product_images'] ?? '') ?>"
                                                     class="w-full h-full object-cover"
                                                     alt="<?= safe_html($item['product_name'] ?? 'Product') ?>">
                                            </div>
                                            <div class="ml-4 flex-1">
                                                <h4 class="text-base font-medium text-gray-900"><?= safe_html($item['product_name'] ?? 'Product') ?></h4>
                                                <p class="text-sm text-gray-600">Qty: <?= $item['quantity'] ?> × R <?= number_format($item['unit_price'], 2) ?></p>
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
                        </div>

                        <!-- Status Update Form -->
                        <?php if (isset($allowedTransitions[$currentStatus])): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Update Status</h3>
                            </div>
                            <form method="POST" class="p-6">
                                ' . csrf_field() . '
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Action</label>
                                        <div class="flex flex-wrap gap-3">
                                            <?php foreach ($allowedTransitions[$currentStatus] as $action): ?>
                                                <label class="inline-flex items-center px-4 py-2 border rounded-lg cursor-pointer transition-colors <?= str_replace('hover:', 'hover:', $action['class']) ?>">
                                                    <input type="radio" name="status" value="<?= $action['value'] ?>" class="sr-only">
                                                    <span class="text-sm font-medium"><?= $action['label'] ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Refund Section (shown when processing refund) -->
                                    <div id="refundSection" class="hidden">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="refund_amount" class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">R</span>
                                                    <input type="number" name="refund_amount" id="refund_amount"
                                                           value="<?= number_format($return['total_amount'] ?? 0, 2) ?>"
                                                           step="0.01" min="0"
                                                           class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400">
                                                </div>
                                            </div>
                                            <div>
                                                <label for="refund_method" class="block text-sm font-medium text-gray-700 mb-1">Refund Method</label>
                                                <select name="refund_method" id="refund_method"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400">
                                                    <option value="original_payment">Original Payment Method</option>
                                                    <option value="store_credit">Store Credit</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                                        <textarea name="admin_notes" id="admin_notes" rows="3"
                                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400"
                                                  placeholder="Add notes about this status update..."><?= safe_html($return['admin_notes'] ?? '') ?></textarea>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                                class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                            <i class="fas fa-save mr-2"></i>Update Status
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                                <i class="fas fa-lock text-gray-400 text-3xl mb-3"></i>
                                <p class="text-gray-600">This return is in a terminal state and cannot be updated.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Status History -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Status History</h3>
                            </div>
                            <div class="p-6">
                                <?php if (empty($statusHistory)): ?>
                                    <p class="text-gray-500 text-center">No status changes yet.</p>
                                <?php else: ?>
                                    <div class="relative">
                                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                                        <div class="space-y-6">
                                            <?php foreach ($statusHistory as $history): ?>
                                                <div class="relative flex items-start pl-10">
                                                    <div class="absolute left-2.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            <?= ucfirst($history['old_status']) ?> → <?= ucfirst($history['new_status']) ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            <?= date('M j, Y g:i A', strtotime($history['created_at'] ?? 'now')) ?>
                                                        </p>
                                                        <?php if (!empty($history['admin_note'])): ?>
                                                            <p class="mt-1 text-sm text-gray-600 bg-gray-50 rounded p-2">
                                                                <?= safe_html($history['admin_note']) ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Return Info -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Return Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm text-gray-500">Return Number</span>
                                    <p class="text-gray-900 font-medium"><?= safe_html($return['return_number'] ?? '') ?></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Reason</span>
                                    <p class="text-gray-900"><?= safe_html(getReasonLabel($return['reason_type'] ?? '')) ?></p>
                                </div>
                                <?php if (!empty($return['reason_details'])): ?>
                                <div>
                                    <span class="text-sm text-gray-500">Details</span>
                                    <p class="text-gray-900"><?= safe_html($return['reason_details'] ?? '') ?></p>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <span class="text-sm text-gray-500">Condition</span>
                                    <p class="text-gray-900"><?= ($return['product_not_used'] ?? 0) ? 'Unused/Unopened' : 'Used' ?></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Return Method</span>
                                    <p class="text-gray-900"><?= safe_html(ucfirst(str_replace('_', ' ', $return['courier_method'] ?? ''))) ?></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Submitted</span>
                                    <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($return['created_at'] ?? 'now')) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Info -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm text-gray-500">Name</span>
                                    <p class="text-gray-900"><?= safe_html($return['customer_name'] ?? '') ?></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Email</span>
                                    <p class="text-gray-900"><?= safe_html($return['customer_email'] ?? '') ?></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Phone</span>
                                    <p class="text-gray-900"><?= safe_html($return['customer_phone'] ?? 'Not provided') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Shipping Address</h3>
                            <p class="text-gray-900">
                                <?= safe_html($shippingAddress['street'] ?? '') ?><br>
                                <?php if (!empty($shippingAddress['city'])): ?>
                                    <?= safe_html($shippingAddress['city']) ?><?php if (!empty($shippingAddress['state'])): ?>, <?= safe_html($shippingAddress['state']) ?><?php endif; ?>
                                <?php endif; ?>
                                <?php if (!empty($shippingAddress['zip'])): ?>
                                    <?= safe_html($shippingAddress['zip']) ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($shippingAddress['country'])): ?>
                                <p class="text-gray-600"><?= safe_html($shippingAddress['country']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($shippingAddress['phone'])): ?>
                                <p class="mt-2 text-gray-600"><?= safe_html($shippingAddress['phone']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
</div>

<script>
// Show/hide refund section based on selected action
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const refundSection = document.getElementById('refundSection');
        if (this.value === 'refunded') {
            refundSection.classList.remove('hidden');
        } else {
            refundSection.classList.add('hidden');
        }
    });
});

// Highlight selected action button
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const parent = this.closest('label');
        document.querySelectorAll('input[name="status"]').forEach(r => {
            r.closest('label').classList.remove('ring-2', 'ring-offset-2', 'ring-green-500');
        });
        parent.classList.add('ring-2', 'ring-offset-2', 'ring-green-500');
    });
});
</script>
<?php
$content = ob_get_clean();

// Page header with back button and actions
$pageHeader = '
<div class="flex justify-between items-center mb-6">
    <div class="flex items-center">
        <a href="' . adminUrl('/returns/') . '" class="text-gray-500 hover:text-gray-700 mr-4">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Return #' . safe_html($return['return_number'] ?? '') . '</h1>
            <span class="ml-4">' . getAdminReturnStatusBadge($currentStatus) . '</span>
        </div>
    </div>
    <div class="flex items-center space-x-4">
        <a href="mailto:' . safe_html($return['customer_email'] ?? '') . '"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <i class="fas fa-envelope mr-2"></i>Email Customer
        </a>
        <a href="' . adminUrl('/orders/view/?id=' . ($return['order_id'] ?? '')) . '"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <i class="fas fa-shopping-cart mr-2"></i>View Order
        </a>
    </div>
</div>';

// Combine page header with content
$fullContent = $pageHeader . $content;

// Render with admin sidebar wrapper
echo adminSidebarWrapper('Return #' . safe_html($return['return_number'] ?? ''), $fullContent, 'returns');
?>
