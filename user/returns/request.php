<?php
/**
 * Return Request Form - CannaBuddy
 * Submit a new return request
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

$order = null;
$orderItems = [];
$settings = [];
$errors = [];
$success = false;
$returnNumber = '';

try {

    // Fetch settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE category = 'returns'");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

$eligibilityDays = isset($settings['return_eligibility_days']) ? (int)$settings['return_eligibility_days'] : 14;
$allowDropOff = isset($settings['allow_drop_off']) && $settings['allow_drop_off'] == '1';
$allowCourier = isset($settings['allow_courier_service']) && $settings['allow_courier_service'] == '1';

// Get order_id from URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$orderId) {
    redirect('/user/returns/');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    $selectedItems = isset($_POST['items']) ? $_POST['items'] : [];
    $reasonType = isset($_POST['reason_type']) ? $_POST['reason_type'] : '';
    $reasonDetails = isset($_POST['reason_details']) ? trim($_POST['reason_details']) : '';
    $productNotUsed = isset($_POST['product_not_used']) ? 1 : 0;
    $courierMethod = isset($_POST['courier_method']) ? $_POST['courier_method'] : '';

    // Validation
    if (empty($selectedItems)) {
        $errors[] = 'Please select at least one item to return.';
    }
    if (empty($reasonType)) {
        $errors[] = 'Please select a reason for the return.';
    }
    if (empty($courierMethod) && ($allowDropOff || $allowCourier)) {
        $errors[] = 'Please select a return method.';
    }

    if (empty($errors) && $db) {
        try {
            // Verify order eligibility again
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$orderId, $currentUser['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $errors[] = 'Order not found.';
            } else {
                // Check if order is still eligible
                $daysSinceDelivery = (int)(new DateTime($order['updated_at']))->diff(new DateTime())->days;
                if ($order['status'] !== 'delivered') {
                    $errors[] = 'This order has not been delivered yet.';
                } elseif ($daysSinceDelivery > $eligibilityDays) {
                    $errors[] = "The return window has expired.";
                } else {
                    // Check for existing active return
                    $stmt = $db->prepare("SELECT COUNT(*) FROM returns WHERE order_id = ? AND status NOT IN ('cancelled', 'rejected')");
                    $stmt->execute([$orderId]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'This order already has an active return request.';
                    }
                }
            }

            if (empty($errors)) {
                // Generate return number
                $returnNumber = 'RET-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

                // Calculate total amount from selected items
                $totalAmount = 0;
                foreach ($selectedItems as $itemId => $quantity) {
                    $quantity = (int)$quantity;
                    if ($quantity > 0) {
                        $stmt = $db->prepare("SELECT unit_price FROM order_items WHERE id = ? AND order_id = ?");
                        $stmt->execute([$itemId, $orderId]);
                        $item = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($item) {
                            $totalAmount += $item['unit_price'] * $quantity;
                        }
                    }
                }

                // Insert return
                $stmt = $db->prepare("
                    INSERT INTO returns (user_id, order_id, return_number, reason_type, reason_details, product_not_used, courier_method, total_amount, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $currentUser['id'],
                    $orderId,
                    $returnNumber,
                    $reasonType,
                    $reasonDetails,
                    $productNotUsed,
                    $courierMethod,
                    $totalAmount
                ]);

                $returnId = $db->lastInsertId();

                // Insert return items
                foreach ($selectedItems as $itemId => $quantity) {
                    $quantity = (int)$quantity;
                    if ($quantity > 0) {
                        $stmt = $db->prepare("
                            INSERT INTO return_items (return_id, order_item_id, quantity, reason)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$returnId, $itemId, $quantity, $reasonType]);
                    }
                }

                $success = true;
                redirect('/user/returns/confirmation.php?return_id=' . $returnId);
            }

        } catch (Exception $e) {
            $errors[] = 'An error occurred while processing your request. Please try again.';
            error_log("Return request error: " . $e->getMessage());
        }
    }
} else {
    // Load order data for display
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$orderId, $currentUser['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                redirect('/user/returns/');
            }

            // Get order items
            $stmt = $db->prepare("
                SELECT oi.*, p.images as product_images, p.id as product_id, p.sku as product_sku
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error loading order: " . $e->getMessage());
        }
    }
}

// Helper function for product image
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

$pageTitle = "Request Return";
$currentPage = "returns";

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Back Button -->
    <a href="<?= userUrl('/returns/eligibility.php?order_id=' . $orderId) ?>" class="inline-flex items-center text-gray-600 hover:text-green-600 mb-6 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Back to Eligibility
    </a>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <form method="POST" id="returnForm">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <!-- Header -->
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h1 class="text-2xl font-bold text-gray-900">Request Return</h1>
                        <p class="text-gray-600 mt-1">Order #<?= htmlspecialchars($order['order_number'] ?? '') ?></p>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Order Items Selection -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Items to Return</h3>
                            <div class="space-y-4">
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="flex items-center p-4 bg-gray-50 rounded-lg" data-item-id="<?= $item['id'] ?>">
                                        <input type="checkbox" name="items[<?= $item['id'] ?>]" value="1"
                                               class="item-checkbox h-5 w-5 text-green-600 focus:ring-green-500 rounded border-gray-300"
                                               onchange="updateQuantityInput(this)">
                                        <div class="w-16 h-16 bg-white rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0 ml-4">
                                            <img src="<?= getProductImageUrl($item['product_images'] ?? '') ?>"
                                                 class="w-full h-full object-cover"
                                                 alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h4 class="text-base font-medium text-gray-900"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></h4>
                                            <?php if (!empty($item['product_sku'])): ?>
                                                <p class="text-sm text-gray-500">SKU: <?= htmlspecialchars($item['product_sku']) ?></p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-600">R <?= number_format($item['unit_price'], 2) ?> each</p>
                                        </div>
                                        <div class="flex items-center">
                                            <label class="text-sm text-gray-600 mr-2">Qty:</label>
                                            <select name="items[<?= $item['id'] ?>]"
                                                    class="quantity-select w-20 border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                                    disabled>
                                                <?php for ($i = 0; $i <= $item['quantity']; $i++): ?>
                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="ml-4 text-right">
                                            <p class="text-lg font-semibold text-gray-900">R <?= number_format($item['total_price'], 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Return Reason -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reason for Return <span class="text-red-500">*</span></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                    <input type="radio" name="reason_type" value="damaged"
                                           class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300"
                                           <?= (isset($_POST['reason_type']) && $_POST['reason_type'] === 'damaged') ? 'checked' : '' ?>>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Product damaged</span>
                                        <span class="block text-sm text-gray-500">Item arrived in damaged condition</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                    <input type="radio" name="reason_type" value="not_working"
                                           class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300"
                                           <?= (isset($_POST['reason_type']) && $_POST['reason_type'] === 'not_working') ? 'checked' : '' ?>>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Not working</span>
                                        <span class="block text-sm text-gray-500">Product doesn't work properly</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                    <input type="radio" name="reason_type" value="not_as_described"
                                           class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300"
                                           <?= (isset($_POST['reason_type']) && $_POST['reason_type'] === 'not_as_described') ? 'checked' : '' ?>>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Not as described</span>
                                        <span class="block text-sm text-gray-500">Product doesn't match description</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                    <input type="radio" name="reason_type" value="changed_mind"
                                           class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300"
                                           <?= (isset($_POST['reason_type']) && $_POST['reason_type'] === 'changed_mind') ? 'checked' : '' ?>>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Changed my mind</span>
                                        <span class="block text-sm text-gray-500">No longer need this product</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Additional Details -->
                            <div class="mt-4">
                                <label for="reason_details" class="block text-sm font-medium text-gray-700 mb-1">Additional Details (optional)</label>
                                <textarea name="reason_details" id="reason_details" rows="3"
                                          class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                          placeholder="Please provide more details about your return reason..."><?= isset($_POST['reason_details']) ? htmlspecialchars($_POST['reason_details']) : '' ?></textarea>
                            </div>
                        </div>

                        <!-- Product Condition -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Condition</h3>
                            <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                <input type="checkbox" name="product_not_used" value="1"
                                       class="h-5 w-5 text-green-600 focus:ring-green-500 rounded border-gray-300"
                                       <?= (isset($_POST['product_not_used']) && $_POST['product_not_used'] == 1) ? 'checked' : '' ?>>
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900">Product is unused/unopened</span>
                                    <span class="block text-sm text-gray-500">The product is in its original condition with packaging intact</span>
                                </div>
                            </label>
                        </div>

                        <!-- Return Method -->
                        <?php if ($allowDropOff || $allowCourier): ?>
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Return Method <span class="text-red-500">*</span></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php if ($allowDropOff): ?>
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                    <input type="radio" name="courier_method" value="drop_off"
                                           class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300"
                                           <?= (isset($_POST['courier_method']) && $_POST['courier_method'] === 'drop_off') ? 'checked' : '' ?>>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Drop-off at Store</span>
                                        <span class="block text-sm text-gray-500">Bring the item to our store</span>
                                    </div>
                                </label>
                                <?php endif; ?>
                                <?php if ($allowCourier): ?>
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition-colors">
                                    <input type="radio" name="courier_method" value="courier"
                                           class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300"
                                           <?= (isset($_POST['courier_method']) && $_POST['courier_method'] === 'courier') ? 'checked' : '' ?>>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Courier Collection</span>
                                        <span class="block text-sm text-gray-500">We arrange for pickup at your address</span>
                                    </div>
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Footer Actions -->
                    <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end space-x-4 rounded-b-xl">
                        <a href="<?= userUrl('/returns/') ?>"
                           class="px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Return Request
                        </button>
                    </div>
                    <?= csrf_field() ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateQuantityInput(checkbox) {
    const row = checkbox.closest('[data-item-id]');
    const quantitySelect = row.querySelector('.quantity-select');
    if (checkbox.checked) {
        quantitySelect.disabled = false;
        quantitySelect.value = quantitySelect.options[1].value; // Select first available quantity
    } else {
        quantitySelect.disabled = true;
        quantitySelect.value = 0;
    }
}

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const checkedItems = document.querySelectorAll('.item-checkbox:checked');
    if (checkedItems.length === 0) {
        e.preventDefault();
        alert('Please select at least one item to return.');
        return false;
    }

    const reasonSelected = document.querySelector('input[name="reason_type"]:checked');
    if (!reasonSelected) {
        e.preventDefault();
        alert('Please select a reason for the return.');
        return false;
    }

    const courierInputs = document.querySelectorAll('input[name="courier_method"]');
    if (courierInputs.length > 0) {
        const courierSelected = document.querySelector('input[name="courier_method"]:checked');
        if (!courierSelected) {
            e.preventDefault();
            alert('Please select a return method.');
            return false;
        }
    }

    return true;
});
</script>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>
