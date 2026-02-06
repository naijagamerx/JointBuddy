<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$orderManager = new OrderManager($db);

// Get current admin
$adminId = AuthMiddleware::getAdminId();

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    sessionFlash('error', 'Invalid order ID');
    redirect('/admin/orders/');
}

// Handle POST actions
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CsrfMiddleware::validate();
    if ($orderManager) {
        if ($_POST['action'] === 'update_status') {
            $newStatus = $_POST['status'] ?? '';
            $note = $_POST['note'] ?? '';
            $result = $orderManager->updateOrderStatus($orderId, $newStatus, $adminId, $note);
            if ($result['success']) {
                $message = 'Order status updated successfully.';
                if (isset($result['reward_points'])) {
                    $rp = $result['reward_points'];
                    if ($rp['new_balance'] !== null) {
                        $message .= ' Customer now has ' . $rp['new_balance'] . ' points.';
                        if ($rp['eligible_for_gift']) {
                            $message .= ' Customer is eligible for a free gift!';
                        }
                    }
                }
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } elseif ($_POST['action'] === 'update_payment') {
            $paymentStatus = $_POST['payment_status'] ?? '';
            $orderManager->updatePaymentStatus($orderId, $paymentStatus);
            $message = 'Payment status updated.';
        } elseif ($_POST['action'] === 'add_note') {
            $note = $_POST['admin_note'] ?? '';
            if (!empty($note)) {
                $orderManager->addOrderNote($orderId, $adminId, $note);
                $message = 'Note added successfully.';
            } else {
                $message = 'Note cannot be empty.';
                $messageType = 'error';
            }
        } elseif ($_POST['action'] === 'update_invoice_design') {
            $design = $_POST['invoice_design'] ?? 'default';
            if (InvoiceDesignRegistry::exists($design)) {
                $stmt = $db->prepare("UPDATE orders SET invoice_design = ? WHERE id = ?");
                $stmt->execute([$design, $orderId]);
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
        } elseif ($_POST['action'] === 'update_payment_method') {
            $paymentMethodId = $_POST['payment_method_id'] ?? '';
            if (!empty($paymentMethodId)) {
                // Get payment method details
                $stmt = $db->prepare("SELECT name FROM payment_methods WHERE id = ?");
                $stmt->execute([$paymentMethodId]);
                $pm = $stmt->fetch();

                if ($pm) {
                    // Update order with payment method name
                    $stmt = $db->prepare("UPDATE orders SET payment_method = ? WHERE id = ?");
                    $stmt->execute([$pm['name'], $orderId]);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'payment_method' => $pm['name']]);
                    exit;
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
            exit;
        }
    }
}

// Get order details
$order = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            redirect('/admin/orders/');
        }

        // Get order items with product details (SKU, weight, brand, etc.)
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

        // Get addresses
        $billingAddress = json_decode($order['billing_address'] ?? '{}', true);
        $shippingAddress = json_decode($order['shipping_address'] ?? '{}', true);

        // Get status history
        $statusHistory = $orderManager ? $orderManager->getOrderStatusHistory($orderId) : [];

        // Get admin notes
        $orderNotes = $orderManager ? $orderManager->getOrderNotes($orderId) : [];

        // Get email log
        $emailLog = $orderManager ? $orderManager->getEmailLogForOrder($order['customer_email']) : [];

        // Get customer reward points and account info
        $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$order['customer_email']]);
        $customerUser = $stmt->fetch();
        $customerUserId = $customerUser['id'] ?? null;
        $customerAccountId = $customerUserId ? 'CUST-' . str_pad($customerUserId, 6, '0', STR_PAD_LEFT) : null;
        $rewardPoints = null;
        if ($customerUserId && $orderManager) {
            $rewardPoints = $orderManager->getCustomerRewardPoints($customerUserId);
        }

        // Get store settings for company info
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $storeName = $settings['store_name'] ?? 'Store';
        $storeAddress = $settings['store_address'] ?? '';
        $addressLines = array_filter(array_map('trim', explode("\n", $storeAddress)));
        $storeEmail = $settings['store_email'] ?? '';
        $storePhone = $settings['store_phone'] ?? '';

        // Get delivery method
        $deliveryMethod = null;
        if (!empty($order['delivery_method_id'])) {
            $stmt = $db->prepare("SELECT name FROM delivery_methods WHERE id = ?");
            $stmt->execute([$order['delivery_method_id']]);
            $deliveryMethod = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Get all active payment methods for dropdown
        $paymentMethods = [];
        $stmt = $db->query("SELECT id, name, manual_type, type, bank_name FROM payment_methods WHERE active = 1 ORDER BY name ASC");
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Error getting order: " . $e->getMessage());
        $_SESSION['error'] = 'Error loading order';
        redirect('/admin/orders/');
    }
}

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

// Status colors
$statusColors = [
    'new' => 'bg-blue-100 text-blue-800',
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'preparing' => 'bg-indigo-100 text-indigo-800',
    'ready' => 'bg-purple-100 text-purple-800',
    'on_the_way' => 'bg-orange-100 text-orange-800',
    'delivered' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800'
];

$paymentStatusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'paid' => 'bg-green-100 text-green-800',
    'failed' => 'bg-red-100 text-red-800',
    'refunded' => 'bg-gray-100 text-gray-800'
];

// Timeline status order
$timelineStatuses = ['new', 'pending', 'approved', 'preparing', 'ready', 'on_the_way', 'delivered'];

// Helper function to get status icon
function getStatusIcon($status) {
    $icons = [
        'new' => 'fa-star',
        'pending' => 'fa-clock',
        'approved' => 'fa-check',
        'preparing' => 'fa-box',
        'ready' => 'fa-check-double',
        'on_the_way' => 'fa-truck',
        'delivered' => 'fa-check-circle',
        'rejected' => 'fa-times-circle'
    ];
    return $icons[$status] ?? 'fa-circle';
}

// Helper function to format full address
function formatFullAddress($address) {
    if (empty($address) || !is_array($address)) {
        return '<p class="text-sm text-gray-500">No address provided</p>';
    }

    $addressHtml = '<div class="text-sm text-gray-900">';
    
    // Name field
    if (!empty($address['name'])) {
        $addressHtml .= '<p class="font-medium text-gray-900">' . htmlspecialchars($address['name']) . '</p>';
    }
    
    // Address line 1 - handle multiple field names
    $street = $address['street'] ?? $address['address_line_1'] ?? $address['address_line1'] ?? '';
    if (!empty($street)) {
        $addressHtml .= '<p>' . htmlspecialchars($street) . '</p>';
    }
    
    // Address line 2
    $addressLine2 = $address['address_line_2'] ?? $address['address_line2'] ?? '';
    if (!empty($addressLine2)) {
        $addressHtml .= '<p>' . htmlspecialchars($addressLine2) . '</p>';
    }
    
    // City, State/Province, Postal Code
    $addressParts = [];
    if (!empty($address['city'])) $addressParts[] = htmlspecialchars($address['city']);
    $state = $address['state'] ?? $address['province'] ?? '';
    if (!empty($state)) $addressParts[] = htmlspecialchars($state);
    if (!empty($address['postal_code'])) $addressParts[] = htmlspecialchars($address['postal_code']);
    if (!empty($addressParts)) {
        $addressHtml .= '<p>' . implode(', ', $addressParts) . '</p>';
    }
    
    // Country
    if (!empty($address['country'])) {
        $addressHtml .= '<p>' . htmlspecialchars($address['country']) . '</p>';
    }
    
    // Phone
    if (!empty($address['phone'])) {
        $addressHtml .= '<p class="text-gray-600">' . htmlspecialchars($address['phone']) . '</p>';
    }
    
    $addressHtml .= '</div>';
    return $addressHtml;
}

// Generate order items HTML
$orderItemsHtml = '';
foreach ($orderItems as $item) {
    $productImages = $item['product_images'] ?? '';
    $imageUrl = '';
    if ($productImages) {
        $imageParts = explode(',', $productImages);
        $firstImage = trim($imageParts[0]);
        if (!empty($firstImage)) {
            // Check if it's already a full URL
            if (strpos($firstImage, 'http://') === 0 || strpos($firstImage, 'https://') === 0) {
                $imageUrl = $firstImage;
            } 
            // Check if it starts with /assets/ (relative path)
            elseif (strpos($firstImage, '/assets/') === 0 || strpos($firstImage, 'assets/') === 0) {
                $imageUrl = assetUrl(ltrim($firstImage, '/'));
            }
            // Otherwise treat as relative path
            else {
                $imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
                $imageUrl = url($imagePath);
            }
        }
    }
    $hasImage = !empty($imageUrl);
    $sku = $item['product_sku'] ?? 'N/A';
    $weight = !empty($item['product_weight']) ? $item['product_weight'] . 'kg' : 'N/A';
    $brand = $item['product_brand'] ?? '';

    $orderItemsHtml .= '
                    <div class="p-4 flex items-center gap-4">
                        <div class="w-20 h-20 bg-gray-100 rounded flex items-center justify-center flex-shrink-0 overflow-hidden border border-gray-200">';
    if ($hasImage) {
        $orderItemsHtml .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($item['product_name'] ?? '') . '" class="w-full h-full object-cover">';
    } else {
        $orderItemsHtml .= '<i class="fas fa-box text-gray-400 text-2xl"></i>';
    }
    $orderItemsHtml .= '
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-sm font-medium text-gray-900 truncate">' . htmlspecialchars($item['product_name'] ?? $item['product_name']) . '</h4>
                                ' . (!empty($brand) ? '<span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">' . htmlspecialchars($brand) . '</span>' : '') . '
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span class="bg-gray-100 px-2 py-0.5 rounded">SKU: ' . htmlspecialchars($sku) . '</span>
                                <span>Weight: ' . htmlspecialchars($weight) . '</span>
                                <span>Qty: ' . (int)$item['quantity'] . '</span>
                                <span>Unit: R' . number_format($item['unit_price'] ?? 0, 2) . '</span>
                            </div>
                            ' . (!empty($item['variation']) ? '<p class="text-xs text-gray-500">' . htmlspecialchars($item['variation']) . '</p>' : '') . '
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-medium text-gray-900">R' . number_format($item['total_price'] ?? 0, 2) . '</p>
                        </div>
                    </div>';
}

// Generate email log HTML
$emailLogHtml = '';
if (!empty($emailLog)) {
    $emailLogHtml .= '
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Email Communication Log</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">';
    foreach ($emailLog as $email) {
        $emailLogHtml .= '
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('M j, Y g:i A', strtotime($email['created_at'])) . '</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($email['email_type'] ?? '-') . '</td>
                                <td class="px-6 py-4 text-sm text-gray-900">' . htmlspecialchars($email['subject'] ?? '-') . '</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full ' . ($email['status'] === 'sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . '">
                                        ' . htmlspecialchars(ucfirst($email['status'] ?? 'pending')) . '
                                    </span>
                                </td>
                            </tr>';
    }
    $emailLogHtml .= '
                        </tbody>
                    </table>
                </div>
            </div>';
}

// Generate notes HTML
$notesHtml = '';
if (!empty($orderNotes)) {
    foreach ($orderNotes as $note) {
        $notesHtml .= '
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-900">' . htmlspecialchars($note['note']) . '</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-user mr-1"></i>' . htmlspecialchars($note['admin_name']) . '
                                        <span class="mx-1">•</span>
                                        ' . date('M j, Y g:i A', strtotime($note['created_at'])) . '
                                    </p>
                                </div>
                            </div>
                        </div>';
    }
} else {
    $notesHtml .= '
                    <p class="text-sm text-gray-500 italic">No notes yet.</p>';
}

// Generate status history HTML
$statusHistoryHtml = '';
if (!empty($statusHistory)) {
    $recentHistory = array_slice($statusHistory, -5);
    foreach ($recentHistory as $history) {
        $statusHistoryHtml .= '
                            <div class="flex justify-between items-center text-gray-600">
                                <div>
                                    <span class="font-medium">' . htmlspecialchars(ucfirst($history['new_status'])) . '</span>
                                    ' . (!empty($history['note']) ? '<p class="text-xs text-gray-500">' . htmlspecialchars($history['note']) . '</p>' : '') . '
                                </div>
                                <span class="text-xs text-gray-400">' . date('M j', strtotime($history['created_at'])) . '</span>
                            </div>';
    }
    $statusHistoryHtml = '
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Status History</h4>
                        <div class="space-y-2 text-sm">
                            ' . $statusHistoryHtml . '
                        </div>
                    </div>';
}

// Generate status options HTML
$statusOptionsHtml = '';
foreach ($statusOptions as $value => $label) {
    $selected = ($order['status'] === $value) ? 'selected' : '';
    $statusOptionsHtml .= '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
}

// Generate timeline HTML
$timelineHtml = '';
$currentStatusIdx = array_search($order['status'], $timelineStatuses);
foreach ($timelineStatuses as $idx => $status) {
    $isCurrent = ($order['status'] === $status);
    $isPast = ($idx <= $currentStatusIdx);
    $contentStatus = $status === 'on_the_way' ? 'On The Way' : ucfirst($status);
    $timelineHtml .= '
                            <div class="flex items-center">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 rounded-full ' . ($isCurrent ? 'bg-green-500' : ($isPast ? 'bg-green-200' : 'bg-gray-200')) . '">
                                    <i class="fas ' . getStatusIcon($status) . ' text-sm ' . ($isCurrent ? 'text-white' : ($isPast ? 'text-green-700' : 'text-gray-500')) . '"></i>
                                </div>
                                <div class="ml-4">
                                    <span class="text-sm font-medium ' . ($isCurrent ? 'text-green-700' : 'text-gray-900') . '">' . htmlspecialchars($contentStatus) . '</span>
                                </div>
                            </div>';
}
// Add rejected if current status
if ($order['status'] === 'rejected') {
    $timelineHtml .= '
                            <div class="flex items-center">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 rounded-full bg-red-500">
                                    <i class="fas fa-times-circle text-sm text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <span class="text-sm font-medium text-red-700">Rejected</span>
                                </div>
                            </div>';
}

// Generate reward points HTML
$rewardPointsHtml = '';
if ($rewardPoints) {
    $progressWidth = min(100, ((int)$rewardPoints['points'] % 5) / 5 * 100);
    $pointsToGift = 5 - ((int)$rewardPoints['points'] % 5);
    $rewardPointsHtml = '
                    <div class="flex items-center gap-4">
                        <div class="bg-green-50 rounded-lg px-4 py-3">
                            <span class="text-sm text-gray-600">Current Balance</span>
                            <div class="text-2xl font-bold text-green-700">' . (int)$rewardPoints['points'] . '</div>
                            <span class="text-xs text-gray-500">points</span>
                        </div>
                        <div class="flex-1">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-green-500 h-3 rounded-full" style="width: ' . $progressWidth . '%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                ' . $pointsToGift . ' more points until free gift
                            </p>
                        </div>
                    </div>';
} else {
    $rewardPointsHtml .= '
                    <p class="text-sm text-gray-500 italic">No reward points record found for this customer.</p>';
}

// Generate WhatsApp button
$whatsappBtn = '';
if (!empty($order['customer_phone'])) {
    $cleanPhone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
    $waText = urlencode('Hi ' . $order['customer_name'] . ', regarding your order #' . $order['order_number']);
    $whatsappBtn = '
            <a href="https://wa.me/' . $cleanPhone . '?text=' . $waText . '" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600">
                <i class="fab fa-whatsapp mr-2"></i>WhatsApp
            </a>';
}

// Prepare Invoice Design Select
$invoiceDesignSelectHtml = '<div class="relative mr-2">
    <select id="invoice_design_select" onchange="updateInvoiceDesign(this.value)" class="appearance-none bg-white border border-gray-300 text-gray-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500 text-sm h-full" style="padding-right: 2rem;">';
$designs = InvoiceDesignRegistry::getAll();
$currentDesign = $order['invoice_design'] ?? $settings['default_invoice_design'] ?? 'default';
foreach ($designs as $key => $design) {
    $selected = ($key === $currentDesign) ? 'selected' : '';
    $invoiceDesignSelectHtml .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' . htmlspecialchars($design['name']) . '</option>';
}
$invoiceDesignSelectHtml .= '</select>
    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
        <i class="fas fa-chevron-down text-xs"></i>
    </div>
</div>';

$invoiceDesignScript = '<script>
function updateInvoiceDesign(design) {
    const printBtn = document.getElementById("print_btn");
    const currentUrl = new URL(printBtn.href);
    currentUrl.searchParams.set("design", design);
    printBtn.href = currentUrl.toString();

    const formData = new FormData();
    formData.append("action", "update_invoice_design");
    formData.append("invoice_design", design);

    fetch(window.location.href, {
        method: "POST",
        body: formData
    });
}
</script>';

// Prepare Payment Method Select for Invoice
// Only show for POS/walk-in customers (pos.local pattern), not for real customer orders
$paymentMethodSelectHtml = '';
$isPosOrder = false;
$customerEmail = strtolower($order['customer_email'] ?? '');

// Check if this is a POS/walk-in order (pos.local or similar patterns)
if (strpos($customerEmail, 'pos.local') !== false || strpos($customerEmail, 'walk-in') !== false || strpos($customerEmail, 'pos') !== false) {
    $isPosOrder = true;
}

// Only show dropdown for POS orders
if ($isPosOrder && !empty($paymentMethods)) {
    $paymentMethodSelectHtml = '<div class="relative mr-2">
    <select id="payment_method_select" onchange="updatePaymentMethod(this.value)" class="appearance-none bg-white border border-gray-300 text-gray-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500 text-sm h-full" style="padding-right: 2rem;">
        <option value="">Select Payment Method</option>';

    foreach ($paymentMethods as $pm) {
        $selected = (!empty($order['payment_method']) && $order['payment_method'] === $pm['name']) ? 'selected' : '';
        $label = htmlspecialchars($pm['name']);
        // Show bank name if available
        if (!empty($pm['bank_name'])) {
            $label .= ' (' . htmlspecialchars($pm['bank_name']) . ')';
        }
        $paymentMethodSelectHtml .= '<option value="' . $pm['id'] . '" ' . $selected . '>' . $label . '</option>';
    }

    $paymentMethodSelectHtml .= '</select>
    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
        <i class="fas fa-chevron-down text-xs"></i>
    </div>
</div>';
}

// Only include script if it's a POS order
$paymentMethodScript = '';
if ($isPosOrder) {
    $paymentMethodScript = '<script>
function updatePaymentMethod(methodId) {
    if (!methodId) return;

    const formData = new FormData();
    formData.append("action", "update_payment_method");
    formData.append("payment_method_id", methodId);

    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh page to show updated payment method
            location.reload();
        }
    });
}
</script>';
}

// Generate order view content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Alert Messages -->
    ' . (!empty($message) ? adminAlert($message, $messageType) : '') . '

    <!-- Page Header with Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <a href="' . adminUrl('/orders/') . '" class="text-blue-600 hover:text-blue-800 mb-2 inline-block text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Orders
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Order #' . htmlspecialchars($order['order_number'] ?? $order['id']) . '</h1>
            <p class="text-gray-600 text-sm">' . date('F j, Y g:i A', strtotime($order['created_at'])) . '</p>
        </div>
        <div class="flex gap-2 items-center">
            ' . $invoiceDesignSelectHtml . '
            ' . $paymentMethodSelectHtml . '
            <a href="' . adminUrl('/qr-codes/') . '" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 h-full">
                <i class="fas fa-qrcode mr-2"></i>QR Codes
            </a>
            <a id="print_btn" href="' . adminUrl('/orders/view/print.php?id=' . $orderId . '&design=' . $currentDesign) . '" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 h-full">
                <i class="fas fa-print mr-2"></i>Print Invoice
            </a>
            ' . $whatsappBtn . '
        </div>
        ' . $invoiceDesignScript . '
        ' . $paymentMethodScript . '
    </div>

    <!-- Invoice Header -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start">
            <!-- Company Info (Left) -->
            <div class="mb-4 md:mb-0">
                <h2 class="text-xl font-bold text-gray-900">' . htmlspecialchars($storeName) . '</h2>
                <div class="text-sm text-gray-600 mt-1 space-y-1">
                    ' . implode('<br>', array_map('htmlspecialchars', $addressLines)) . '
                    ' . (!empty($storeEmail) ? '<br><i class="fas fa-envelope mr-1 w-4"></i>' . htmlspecialchars($storeEmail) : '') . '
                    ' . (!empty($storePhone) ? '<br><i class="fas fa-phone mr-1 w-4"></i>' . htmlspecialchars($storePhone) : '') . '
                </div>
            </div>
            <!-- Invoice Info (Right) -->
            <div class="text-right">
                <div class="mb-2">
                    <span class="text-sm text-gray-500 uppercase tracking-wide">Invoice #</span>
                    <span class="text-lg font-bold text-gray-900 ml-2">' . htmlspecialchars($order['order_number'] ?? $order['id']) . '</span>
                </div>
                <div class="mb-2">
                    <span class="text-sm text-gray-500 uppercase tracking-wide">Date</span>
                    <span class="text-gray-900 ml-2 font-medium">' . date('M j, Y', strtotime($order['created_at'])) . '</span>
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wider ' . ($statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800') . '">
                        ' . htmlspecialchars($statusOptions[$order['status']] ?? $order['status']) . '
                    </span>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wider ' . ($paymentStatusColors[$order['payment_status']] ?? 'bg-gray-100 text-gray-800') . '">
                        ' . htmlspecialchars($order['payment_status'] ?? 'pending') . '
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Key Metrics Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 pt-6 border-t border-gray-100">
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Customer</span>
                <span class="block text-sm font-semibold text-gray-900 truncate">' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</span>
            </div>
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Payment Method</span>
                <span id="payment-method-display" class="block text-sm font-semibold text-gray-900">' . htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))) . '</span>
            </div>
            <div>
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Delivery Method</span>
                <span class="block text-sm font-semibold text-gray-900">' . htmlspecialchars($deliveryMethod['name'] ?? 'Standard Delivery') . '</span>
            </div>
            <div class="text-right">
                <span class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Amount Due</span>
                <span class="block text-lg font-bold text-gray-900">R' . number_format($order['total_amount'] ?? 0, 2) . '</span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (Items, Details) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Items Card (Invoice Style) -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Items Ordered</h3>
                    <span class="text-sm text-gray-500">' . count($orderItems) . ' item(s)</span>
                </div>
                
                <!-- Table Header -->
                <div class="bg-gray-50 px-6 py-2 border-b border-gray-200 grid grid-cols-12 gap-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <div class="col-span-6">Item</div>
                    <div class="col-span-2 text-center">Qty</div>
                    <div class="col-span-2 text-right">Price</div>
                    <div class="col-span-2 text-right">Total</div>
                </div>

                <div class="divide-y divide-gray-100">
                    ' . $orderItemsHtml . '
                </div>
                
                <!-- Order Summary -->
                <div class="bg-gray-50 px-6 py-6 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="w-full max-w-sm space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="text-gray-900 font-medium">R' . number_format($order['subtotal'] ?? 0, 2) . '</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Delivery Fee</span>
                                <span class="text-gray-900 font-medium">R' . number_format($order['shipping_amount'] ?? 0, 2) . '</span>
                            </div>
                            ' . (($order['tax_amount'] ?? 0) > 0 ? '
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax</span>
                                <span class="text-gray-900 font-medium">R' . number_format($order['tax_amount'], 2) . '</span>
                            </div>
                            ' : '') . '
                            ' . (($order['discount_amount'] ?? 0) > 0 ? '
                            <div class="flex justify-between text-sm text-green-600">
                                <span>Discount</span>
                                <span>-R' . number_format($order['discount_amount'], 2) . '</span>
                            </div>
                            ' : '') . '
                            <div class="border-t border-gray-300 pt-3 mt-3">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-base font-bold text-gray-900">Total Amount</span>
                                    <span class="text-xl font-bold text-gray-900">R' . number_format($order['total_amount'] ?? 0, 2) . '</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Delivery Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Customer Info -->
                <div class="bg-white shadow rounded-lg overflow-hidden h-full">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Customer Details</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</p>
                                ' . (!empty($customerAccountId) ? '<p class="text-xs text-gray-500 font-mono mt-0.5">' . htmlspecialchars($customerAccountId) . '</p>' : '') . '
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <i class="fas fa-envelope text-sm"></i>
                            </div>
                            <div>
                                <a href="mailto:' . htmlspecialchars($order['customer_email'] ?? '') . '" class="text-sm text-blue-600 hover:underline block">' . htmlspecialchars($order['customer_email'] ?? 'N/A') . '</a>
                            </div>
                        </div>
                        ' . (!empty($order['customer_phone']) ? '
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <i class="fas fa-phone text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900">' . htmlspecialchars($order['customer_phone']) . '</p>
                            </div>
                        </div>
                        ' : '') . '
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="bg-white shadow rounded-lg overflow-hidden h-full">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Delivery Address</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0 text-gray-600">
                                <i class="fas fa-map-marker-alt text-sm"></i>
                            </div>
                            <div class="text-sm text-gray-900 leading-relaxed">
                                ' . formatFullAddress($shippingAddress) . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Communication Log -->
            ' . $emailLogHtml . '

            <!-- Reward Points Info -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Reward Points Info</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Setting an order to "Delivered" will add 1 reward point to the customer\'s profile.
                        After every 5 points, the customer is eligible for a free gift.
                    </p>
                    ' . $rewardPointsHtml . '
                </div>
            </div>

            <!-- Admin Notes -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Admin Notes (Internal)</h3>
                </div>
                <div class="p-6">
                    <!-- Add Note Form -->
                    <form method="POST" class="mb-6">
                        ' . csrf_field() . '
                        <input type="hidden" name="action" value="add_note">
                        <div class="flex gap-2">
                            <input type="text" name="admin_note" placeholder="Add an internal note..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-400 text-sm">
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Add Note
                            </button>
                        </div>
                    </form>
                    <!-- Notes List -->
                    <div class="space-y-3">
                        ' . $notesHtml . '
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (Status & Timeline) -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Update Status Card -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Manage Order</h3>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Order Status Form -->
                    <form method="POST">
                        ' . csrf_field() . '
                        <input type="hidden" name="action" value="update_status">
                        <div class="mb-4">
                            <label for="status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Order Status</label>
                            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-shadow">
                                ' . $statusOptionsHtml . '
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="note" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Note (Optional)</label>
                            <textarea id="note" name="note" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-shadow" placeholder="Add context to this update..."></textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium shadow-sm transition-colors flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>Update Status
                        </button>
                    </form>

                    <div class="border-t border-gray-100 pt-6">
                        <form method="POST">
                            ' . csrf_field() . '
                            <input type="hidden" name="action" value="update_payment">
                            <div class="mb-4">
                                <label for="payment_status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Payment Status</label>
                                <select id="payment_status" name="payment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-shadow">
                                    <option value="pending" ' . ($order['payment_status'] === 'pending' ? 'selected' : '') . '>Pending</option>
                                    <option value="paid" ' . ($order['payment_status'] === 'paid' ? 'selected' : '') . '>Paid</option>
                                    <option value="failed" ' . ($order['payment_status'] === 'failed' ? 'selected' : '') . '>Failed</option>
                                    <option value="refunded" ' . ($order['payment_status'] === 'refunded' ? 'selected' : '') . '>Refunded</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full px-4 py-2.5 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm font-medium shadow-sm transition-colors flex items-center justify-center">
                                <i class="fas fa-credit-card mr-2"></i>Update Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Timeline Card -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Order Progress</h3>
                </div>
                <div class="p-6">
                    <div class="relative pl-2">
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-100"></div>
                        <div class="space-y-6 relative">
                            ' . $timelineHtml . '
                        </div>
                    </div>
                    
                    ' . $statusHistoryHtml . '
                </div>
            </div>
        </div>
    </div>
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('View Order #' . ($order['order_number'] ?? $order['id']), $content, 'orders');
?>
