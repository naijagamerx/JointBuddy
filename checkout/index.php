<?php
/**
 * Checkout Page - CannaBuddy
 * Complete checkout flow with shipping, billing, and payment
 */

// Load session helper FIRST for consistent session handling
require_once __DIR__ . '/../includes/session_helper.php';
ensureSessionStarted();

// Load global config for error display (sets DEBUG_MODE)
require_once __DIR__ . '/../config.php';

// Load email service
require_once __DIR__ . '/../includes/email_service.php';

// Enable debug mode if requested (overrides global setting)
$debugMode = isset($_GET['debug']) || isset($_SESSION['checkout_debug']);

if ($debugMode) {
    echo "<div style='background:#1e1e1e;color:#d4d4d4;padding:20px;margin:10px;border-radius:5px;font-family:monospace;'>";
    echo "<h3 style='color:#10b981'>🔧 DEBUG MODE ENABLED</h3>";
    echo "<pre>";
}

// Log all errors to a file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/checkout_errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/payment_methods_service.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/user_addresses_service.php';
require_once __DIR__ . '/../includes/coupons_service.php';
require_once __DIR__ . '/../includes/delivery_methods_service.php';

// Track all errors
$checkoutErrors = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    if ($debugMode) echo "✓ Database connected\n";
} catch (Throwable $e) {
    $db = null;
    $errorMsg = "Database connection failed: " . $e->getMessage();
    error_log($errorMsg);
    $checkoutErrors[] = $errorMsg;
    if ($debugMode) echo "✗ $errorMsg\n";
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    if ($debugMode) echo "✗ Cart is empty, redirecting to cart\n";
    header('Location: ' . url('/cart/'));
    exit;
}

if ($debugMode) {
    echo "✓ Cart has " . count($_SESSION['cart']) . " items\n";
    $cartTotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cartTotal += $item['price'] * $item['qty'];
    }
    echo "✓ Cart subtotal: R" . number_format($cartTotal, 2) . "\n";
}

// Check login status - must verify user_logged_in is true, not just set
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
if ($debugMode) echo ($isLoggedIn ? "✓" : "○") . " User is " . ($isLoggedIn ? "logged in" : "guest") . "\n";
$currentUser = null;
$userAddresses = [];

if ($isLoggedIn && $db) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log("Error fetching user: " . $e->getMessage());
    }
}

// Get delivery methods
$deliveryMethods = [];
if ($db) {
    try {
        $deliveryMethods = getActiveDeliveryMethods($db);
    } catch (Throwable $e) {
        $errorMsg = "Error getting delivery methods: " . $e->getMessage();
        error_log($errorMsg);
        $checkoutErrors[] = $errorMsg;
        if ($debugMode) echo "✗ $errorMsg\n";
    }
}

if (empty($deliveryMethods)) {
    $errorMsg = "No delivery methods found!";
    error_log($errorMsg);
    $checkoutErrors[] = $errorMsg;
    if ($debugMode) echo "✗ $errorMsg\n";
} else {
    if ($debugMode) echo "✓ Found " . count($deliveryMethods) . " delivery methods\n";
}

// Get payment methods
$paymentMethods = [];
$manualPaymentDetails = [];
if ($db) {
    try {
        ensurePaymentMethodsSchema($db);
        $stmt = $db->query("SELECT * FROM payment_methods WHERE active = 1 ORDER BY name ASC");
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($paymentMethods as $method) {
            if (!isset($method['type'])) {
                continue;
            }
            if (in_array($method['type'], ['bank_transfer', 'crypto', 'manual_custom'], true)) {
                try {
                    $details = getManualPaymentDetailsByType($db, $method['type']);
                    if ($details) {
                        $manualPaymentDetails[$method['type']] = $details['fields'];
                    }
                } catch (Throwable $e) {
                    $errorMsg = 'Error fetching manual payment details: ' . $e->getMessage();
                    error_log($errorMsg);
                    $checkoutErrors[] = $errorMsg;
                    if ($debugMode) echo "✗ $errorMsg\n";
                }
            }
        }
    } catch (Throwable $e) {
        $errorMsg = "Error fetching payment methods: " . $e->getMessage();
        error_log($errorMsg);
        $checkoutErrors[] = $errorMsg;
        if ($debugMode) echo "✗ $errorMsg\n";
    }
}

// Check if payment methods exist
if (empty($paymentMethods)) {
    $errorMsg = "No payment methods found!";
    error_log($errorMsg);
    $checkoutErrors[] = $errorMsg;
    if ($debugMode) echo "✗ $errorMsg\n";
} else {
    if ($debugMode) echo "✓ Found " . count($paymentMethods) . " payment methods\n";
}

// Calculate cart totals
$cart = $_SESSION['cart'];
$subtotal = 0;
$itemCount = 0;

foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $itemCount += $item['qty'];
}

$defaultShipping = 0;
if (!empty($deliveryMethods)) {
    $defaultShipping = getEffectiveDeliveryCost($deliveryMethods[0], $subtotal);
}

// Get user's default address if logged in
$defaultAddress = null;
$selectedAddress = null;
if ($isLoggedIn && $db) {
    $defaultAddress = getDefaultAddress($db, $_SESSION['user_id']);
    
    // Handle selected address from modal
    if (isset($_GET['address_id'])) {
        $stmt = $db->prepare("
            SELECT *,
                   CONCAT(first_name, ' ', last_name) as name
            FROM user_addresses
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([intval($_GET['address_id']), $_SESSION['user_id']]);
        $selectedAddress = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Check for applied coupon
$appliedCoupon = null;
$couponDiscount = 0;

if (isset($_SESSION['selected_coupon']) && $db) {
    $couponCode = $_SESSION['selected_coupon'];
    $coupon = getCouponByCode($db, $couponCode);

    if ($coupon && $coupon['active']) {
        $validation = validateCoupon($db, $coupon, $subtotal, $isLoggedIn ? $_SESSION['user_id'] : null);
        if ($validation['valid']) {
            $appliedCoupon = $coupon;
            $couponDiscount = calculateDiscount($coupon, $subtotal, $defaultShipping);
        }
    }
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($debugMode) echo "✓ POST request received\n";

    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'postal_code', 'delivery_method', 'payment_method'];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            if ($debugMode) echo "✗ Missing field: $field\n";
        }
    }

    // Validate email
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
        if ($debugMode) echo "✗ Invalid email format\n";
    }

    if ($debugMode) {
        echo "✓ Validation complete. Errors found: " . count($errors) . "\n";
        if (!empty($errors)) {
            echo "Errors: " . print_r($errors, true) . "\n";
        }
    }

    if (empty($errors) && $db) {
        if ($debugMode) echo "✓ Proceeding to create order...\n";

        try {
            $selectedDeliveryId = intval($_POST['delivery_method']);
            if ($debugMode) echo "✓ Selected delivery method ID: $selectedDeliveryId\n";

            $shippingCost = 0;
            $selectedDelivery = null;
            foreach ($deliveryMethods as $dm) {
                if ($dm['id'] == $selectedDeliveryId) {
                    $selectedDelivery = $dm;
                    $shippingCost = getEffectiveDeliveryCost($dm, $subtotal);
                    if ($debugMode) echo "✓ Found delivery method: {$dm['name']}, Cost: R$shippingCost\n";
                    break;
                }
            }

            if ($selectedDelivery === null) {
                throw new Exception("Invalid delivery method ID: $selectedDeliveryId");
            }

            // Handle coupon
            $couponCode = $_POST['coupon_code'] ?? ($_SESSION['selected_coupon'] ?? null);
            $couponDiscount = 0;
            $appliedCoupon = null;

            if ($couponCode) {
                if ($debugMode) echo "✓ Processing coupon: $couponCode\n";
                $coupon = getCouponByCode($db, $couponCode);
                if ($coupon) {
                    $validation = validateCoupon($db, $coupon, $subtotal, $isLoggedIn ? $_SESSION['user_id'] : null);
                    if ($validation['valid']) {
                        $appliedCoupon = $coupon;
                        $couponDiscount = calculateDiscount($coupon, $subtotal, $shippingCost);
                        if ($debugMode) echo "✓ Coupon applied: R$couponDiscount discount\n";
                    }
                }
            }

            // Handle gift message
            $isGift = isset($_POST['is_gift']);
            $giftMessage = $isGift ? ($_POST['gift_message'] ?? '') : null;

            $totalAmount = $subtotal + $shippingCost - $couponDiscount;
            if ($debugMode) {
                echo "✓ Order totals: subtotal=R$subtotal, shipping=R$shippingCost, discount=R$couponDiscount, total=R$totalAmount\n";
            }

            // Create shipping address JSON
            $shippingAddress = json_encode([
                'street' => $_POST['address'],
                'city' => $_POST['city'],
                'province' => $_POST['province'] ?? '',
                'postal_code' => $_POST['postal_code'],
                'country' => $_POST['country'] ?? 'South Africa'
            ]);

            // Create billing address JSON (fallback to shipping if not provided)
            $billingAddress = json_encode([
                'street' => $_POST['billing_address'] ?? $_POST['address'],
                'city' => $_POST['billing_city'] ?? $_POST['city'],
                'province' => $_POST['billing_province'] ?? $_POST['province'] ?? '',
                'postal_code' => $_POST['billing_postal_code'] ?? $_POST['postal_code'],
                'country' => $_POST['billing_country'] ?? $_POST['country'] ?? 'South Africa'
            ]);

            if ($debugMode) {
                echo "✓ Shipping address: $shippingAddress\n";
                echo "✓ Billing address: $billingAddress\n";
            }

            // Create order
            if ($debugMode) echo "✓ Preparing INSERT statement...\n";

            // Generate order number
            $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
            if ($debugMode) echo "✓ Order number: $orderNumber\n";

            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_email, customer_phone,
                    shipping_address, billing_address,
                    delivery_method_id, payment_method, payment_status,
                    subtotal, shipping_amount, total_amount, status, notes,
                    coupon_code, discount_amount, gift_message, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $userId = $isLoggedIn ? $_SESSION['user_id'] : null;
            $customerName = $_POST['first_name'] . ' ' . $_POST['last_name'];
            $paymentType = $_POST['payment_method'];
            $paymentStatus = 'pending';

            if ($debugMode) echo "✓ Executing order INSERT...\n";

            $stmt->execute([
                $orderNumber,
                $userId,
                $customerName,
                $_POST['email'],
                $_POST['phone'],
                $shippingAddress,
                $billingAddress,
                $selectedDeliveryId,
                $paymentType,
                $paymentStatus,
                $subtotal,
                $shippingCost,
                $totalAmount,
                'pending',
                $_POST['notes'] ?? '',
                $appliedCoupon ? $appliedCoupon['code'] : null,
                $couponDiscount,
                $giftMessage
            ]);

            $orderId = $db->lastInsertId();
            if ($debugMode) echo "✓ Order created! ID: $orderId\n";

            // Record coupon usage if coupon was applied
            if ($appliedCoupon) {
                if ($debugMode) echo "✓ Recording coupon usage...\n";
                applyCoupon($db, $appliedCoupon['id'], $isLoggedIn ? $_SESSION['user_id'] : null, $orderId);
            }

            // Clear selected coupon from session
            unset($_SESSION['selected_coupon']);

            // Insert order items
            if ($debugMode) echo "✓ Inserting " . count($cart) . " order items...\n";

            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart as $index => $item) {
                if ($debugMode) echo "  ✓ Item $index: {$item['name']} (x{$item['qty']})\n";
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['qty'],
                    $item['price'],
                    $item['price'] * $item['qty']
                ]);
            }

            // Save address to address book if user is logged in and checkbox is checked
            if ($isLoggedIn && !empty($_POST['save_address'])) {
                if ($debugMode) echo "✓ Saving address to user address book...\n";
                
                $saveAsDefault = !empty($_POST['save_as_default_address']);
                
                $stmt = $db->prepare("
                    INSERT INTO user_addresses (
                        user_id, name, address, city, postal_code, 
                        country, phone, default_for_shipping, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['first_name'] . ' ' . $_POST['last_name'],
                    $_POST['address'],
                    $_POST['city'],
                    $_POST['postal_code'],
                    $_POST['country'] ?? 'South Africa',
                    $_POST['phone'],
                    $saveAsDefault ? 1 : 0
                ]);
                
                if ($saveAsDefault) {
                    if ($debugMode) echo "  ✓ Address saved as default\n";
                    
                    // Unset other default addresses
                    $stmt = $db->prepare("
                        UPDATE user_addresses 
                        SET default_for_shipping = 0 
                        WHERE user_id = ? AND id != ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $db->lastInsertId()]);
                } else {
                    if ($debugMode) echo "  ✓ Address saved\n";
                }
            }

            // Store order info in session for thank you page
            $_SESSION['completed_order'] = [
                'id' => $orderId,
                'total' => $totalAmount,
                'payment_method' => $paymentType,
                'email' => $_POST['email']
            ];

            // Clear cart
            $_SESSION['cart'] = [];

            // Send order confirmation email
            if ($debugMode) echo "✓ Sending order confirmation email...\n";

            $emailService = new EmailService($db);
            $customerName = $_POST['first_name'] . ' ' . $_POST['last_name'];
            $emailSent = $emailService->sendOrderConfirmation(
                ['id' => $orderId, 'order_number' => $orderNumber, 'created_at' => date('Y-m-d H:i:s'), 'total_amount' => $totalAmount, 'subtotal' => $subtotal, 'shipping_amount' => $shippingCost, 'discount_amount' => $couponDiscount, 'payment_method' => $paymentType],
                $cart,
                $_POST['email'],
                $customerName
            );

            if ($debugMode) {
                if ($emailSent) {
                    echo "✓ Order confirmation email sent to {$_POST['email']}\n";
                } else {
                    echo "⚠ Email sending failed: " . $emailService->getError() . "\n";
                }
            }

            if ($debugMode) {
                echo "✓ Order complete! Redirecting to thank you page...\n";
                echo "</pre></div>";
            }

            // Handle payment method
            if ($paymentType === 'payfast') {
                // Redirect to PayFast (implement PayFast integration)
                header('Location: ' . url('/thank-you/?order=' . $orderId . '&payment=pending'));
            } else {
                // Redirect to thank you page
                header('Location: ' . url('/thank-you/?order=' . $orderId));
            }
            exit;

        } catch (PDOException $e) {
            $errorMsg = "DATABASE ERROR: " . $e->getMessage() . " | Code: " . $e->getCode();
            error_log($errorMsg);
            $checkoutErrors[] = $errorMsg;
            if ($debugMode) {
                echo "✗ $errorMsg\n";
                echo "Trace: " . $e->getTraceAsString() . "\n";
            }
            $errors['general'] = $debugMode ? 'Database Error: ' . htmlspecialchars($e->getMessage()) : 'Unable to process your order due to a system issue. Please try again or contact support.';
        } catch (Throwable $e) {
            $errorMsg = "ORDER ERROR: " . $e->getMessage();
            error_log($errorMsg);
            $checkoutErrors[] = $errorMsg;
            if ($debugMode) {
                echo "✗ $errorMsg\n";
                echo "Trace: " . $e->getTraceAsString() . "\n";
            }
            $errors['general'] = $debugMode ? 'Error: ' . htmlspecialchars($e->getMessage()) : 'An error occurred while processing your order. Please try again.';
        }
    } else {
        if ($debugMode && $db) {
            echo "✗ Validation failed or no database connection\n";
        }
    }
} // End POST handling

$pageTitle = "Checkout";
include __DIR__ . '/../includes/header.php';
?>

<!-- Checkout Progress -->
<div class="bg-white border-b">
    <div class="container mx-auto px-4 py-4 max-w-7xl">
        <div class="flex items-center justify-center space-x-4 text-sm">
            <div class="flex items-center text-gray-400">
                <span class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center mr-2">
                    <i class="fas fa-check text-xs"></i>
                </span>
                Cart
            </div>
            <i class="fas fa-chevron-right text-gray-300"></i>
            <div class="flex items-center text-green-600 font-semibold">
                <span class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center mr-2">2</span>
                Checkout
            </div>
            <i class="fas fa-chevron-right text-gray-300"></i>
            <div class="flex items-center text-gray-400">
                <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center mr-2">3</span>
                Complete
            </div>
        </div>
    </div>
</div>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($errors['general']) ?>
                <?php if ($debugMode && !empty($checkoutErrors)): ?>
                    <div class="mt-3 pt-3 border-t border-red-300">
                        <strong class="block mb-2">Debug Information:</strong>
                        <ul class="list-disc list-inside text-sm font-mono">
                            <?php foreach ($checkoutErrors as $err): ?>
                                <li><?php echo htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($debugMode && !empty($checkoutErrors)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-bug mr-2"></i>
                <strong>Debug Mode Active</strong> - Check errors above
            </div>
        <?php endif; ?>
        
        <form method="POST" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Left Column - Forms -->
                <div class="lg:w-2/3 space-y-6">
                    
                    <!-- Guest Checkout / Login Prompt -->
                    <?php if (!$isLoggedIn): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-user-circle text-blue-500 text-2xl mr-3"></i>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Have an account?</h3>
                                        <p class="text-sm text-gray-600">Sign in for a faster checkout experience</p>
                                    </div>
                                </div>
                                <a href="<?php echo  userUrl('login/?redirect=' . urlencode(url('/checkout/'))) ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                    Login
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Contact Information -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">1</span>
                            Contact Information
                        </h2>

                        <?php if (!$isLoggedIn): ?>
                            <!-- Guest Checkout - Show Forms -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" name="first_name" required
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                           class="w-full px-4 py-3 border <?php echo isset($errors['first_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                           placeholder="John">
                                    <?php if (isset($errors['first_name'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $errors['first_name'] ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" name="last_name" required
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                           class="w-full px-4 py-3 border <?php echo isset($errors['last_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                           placeholder="Doe">
                                    <?php if (isset($errors['last_name'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $errors['last_name'] ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" name="email" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>"
                                           class="w-full px-4 py-3 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                           placeholder="john@example.com">
                                    <?php if (isset($errors['email'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $errors['email'] ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                                    <input type="tel" name="phone" required
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? '') ?>"
                                           class="w-full px-4 py-3 border <?php echo isset($errors['phone']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                           placeholder="+27 XX XXX XXXX">
                                    <?php if (isset($errors['phone'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $errors['phone'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Logged-in User - Show Summary -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($currentUser['name']) ?></p>
                                    <?php if ($defaultAddress): ?>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($defaultAddress['phone']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Hidden fields for logged-in users -->
                            <input type="hidden" name="first_name" value="<?= htmlspecialchars(explode(' ', $currentUser['name'])[0] ?? '') ?>">
                            <input type="hidden" name="last_name" value="<?= htmlspecialchars(explode(' ', $currentUser['name'], 2)[1] ?? '') ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>">
                            <input type="hidden" name="phone" value="<?= htmlspecialchars($defaultAddress['phone'] ?? '') ?>">
                        <?php endif; ?>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">2</span>
                            Shipping Address
                        </h2>

                        <?php 
                        $displayAddress = $selectedAddress ?? $defaultAddress;
                        if ($isLoggedIn && $displayAddress): ?>
                            <!-- Logged-in User with Default/Selected Address - Show Summary -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?= ucfirst($displayAddress['address_type'] ?? 'Shipping') ?>
                                        </span>
                                        <?php if ($displayAddress['label']): ?>
                                            <span class="ml-2 text-sm text-gray-600"><?= htmlspecialchars($displayAddress['label']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" onclick="openAddressModal()" class="text-green-600 text-sm hover:text-green-700 font-medium">
                                        Change
                                    </button>
                                </div>
                                <div class="text-gray-700">
                                    <p><?= htmlspecialchars($displayAddress['address_line1']) ?></p>
                                    <?php if (!empty($displayAddress['address_line2'])): ?>
                                        <p><?= htmlspecialchars($displayAddress['address_line2']) ?></p>
                                    <?php endif; ?>
                                    <p>
                                        <?= htmlspecialchars($displayAddress['city']) ?>
                                        <?php if (!empty($displayAddress['province'])): ?>
                                            , <?= htmlspecialchars($displayAddress['province']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($displayAddress['postal_code'])): ?>
                                            , <?= htmlspecialchars($displayAddress['postal_code']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mt-2">
                                        <span class="font-medium"><?= htmlspecialchars($displayAddress['name']) ?></span>
                                        <span class="ml-2"><?= htmlspecialchars($displayAddress['phone'] ?? '') ?></span>
                                    </p>
                                </div>
                            </div>
                            <!-- Hidden fields with address data -->
                            <input type="hidden" name="address" value="<?= htmlspecialchars($displayAddress['address_line1']) ?>">
                            <input type="hidden" name="address_line2" value="<?= htmlspecialchars($displayAddress['address_line2'] ?? '') ?>">
                            <input type="hidden" name="city" value="<?= htmlspecialchars($displayAddress['city']) ?>">
                            <input type="hidden" name="province" value="<?= htmlspecialchars($displayAddress['province'] ?? '') ?>">
                            <input type="hidden" name="postal_code" value="<?= htmlspecialchars($displayAddress['postal_code'] ?? '') ?>">
                            <input type="hidden" name="country" value="<?= htmlspecialchars($displayAddress['country'] ?? 'South Africa') ?>">
                            <input type="hidden" name="phone" value="<?= htmlspecialchars($displayAddress['phone'] ?? '') ?>">
                            <?php
                            $fullNameParts = explode(' ', $displayAddress['name'] ?? '', 2);
                            $displayFirstName = $fullNameParts[0] ?? '';
                            $displayLastName = $fullNameParts[1] ?? '';
                            ?>
                            <input type="hidden" name="first_name" value="<?= htmlspecialchars($displayFirstName) ?>">
                            <input type="hidden" name="last_name" value="<?= htmlspecialchars($displayLastName) ?>">
                        <?php else: ?>
                            <!-- Guest User or No Default Address - Show Form -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                                    <input type="text" name="address" required
                                           value="<?php echo htmlspecialchars($_POST['address'] ?? '') ?>"
                                           class="w-full px-4 py-3 border <?php echo isset($errors['address']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                           placeholder="123 Main Street, Apartment 4B">
                                    <?php if (isset($errors['address'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo $errors['address'] ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- First Row: City & Province -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                        <input type="text" name="city" required
                                               value="<?php echo htmlspecialchars($_POST['city'] ?? '') ?>"
                                               class="w-full px-4 py-3 border <?php echo isset($errors['city']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                               placeholder="Cape Town">
                                        <?php if (isset($errors['city'])): ?>
                                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['city'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1"><span data-location-province-label>Province/State</span> *</label>
                                        <select name="province" required
                                                data-location-province
                                                class="w-full px-4 py-3 border <?php echo isset($errors['province']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500">
                                            <option value="">Select Province/State</option>
                                            <?php
                                            // Load provinces for South Africa as default
                                            $provinces = [
                                                'EC' => 'Eastern Cape',
                                                'FS' => 'Free State',
                                                'GP' => 'Gauteng',
                                                'KZN' => 'KwaZulu-Natal',
                                                'LP' => 'Limpopo',
                                                'MP' => 'Mpumalanga',
                                                'NC' => 'Northern Cape',
                                                'NW' => 'North West',
                                                'WC' => 'Western Cape',
                                            ];
                                            foreach ($provinces as $code => $name): ?>
                                                <option value="<?= htmlspecialchars($name) ?>"
                                                        <?php echo (isset($_POST['province']) && $_POST['province'] === $name) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['province'])): ?>
                                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['province'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Second Row: Postal Code & Country -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                                        <input type="text" name="postal_code" required
                                               value="<?php echo htmlspecialchars($_POST['postal_code'] ?? '') ?>"
                                               class="w-full px-4 py-3 border <?php echo isset($errors['postal_code']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500"
                                               placeholder="8001">
                                        <?php if (isset($errors['postal_code'])): ?>
                                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['postal_code'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                        <select name="country" required
                                                data-location-country
                                                onchange="updateProvinces(this.value)"
                                                class="w-full px-4 py-3 border <?php echo isset($errors['country']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-green-500 focus:border-green-500">
                                            <option value="">Select Country</option>
                                            <option value="South Africa" <?php echo (isset($_POST['country']) && $_POST['country'] === 'South Africa') ? 'selected' : ''; ?>>South Africa</option>
                                            <option value="Nigeria" <?php echo (isset($_POST['country']) && $_POST['country'] === 'Nigeria') ? 'selected' : ''; ?>>Nigeria</option>
                                            <option value="United States" <?php echo (isset($_POST['country']) && $_POST['country'] === 'United States') ? 'selected' : ''; ?>>United States</option>
                                            <option value="United Kingdom" <?php echo (isset($_POST['country']) && $_POST['country'] === 'United Kingdom') ? 'selected' : ''; ?>>United Kingdom</option>
                                        </select>
                                        <?php if (isset($errors['country'])): ?>
                                            <p class="text-red-500 text-sm mt-1"><?php echo $errors['country'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Hidden data for JavaScript -->
                                <script>
                                // Province/State data for dropdowns
                                const locationData = {
                                    'South Africa': ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape'],
                                    'Nigeria': ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'Federal Capital Territory', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'],
                                    'United States': ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'],
                                    'United Kingdom': ['England', 'Scotland', 'Wales', 'Northern Ireland']
                                };

                                function updateProvinces(country) {
                                    const provinceSelect = document.querySelector('[data-location-province]');
                                    const provinceLabel = document.querySelector('[data-location-province-label]');
                                    if (!provinceSelect) return;

                                    // Clear existing options
                                    provinceSelect.innerHTML = '<option value="">Select Province/State</option>';

                                    // Update label based on country
                                    const labels = {
                                        'South Africa': 'Province',
                                        'Nigeria': 'State',
                                        'United States': 'State',
                                        'United Kingdom': 'Region'
                                    };
                                    if (provinceLabel) {
                                        provinceLabel.textContent = labels[country] || 'Province/State';
                                    }

                                    // Add provinces for selected country
                                    if (locationData[country]) {
                                        locationData[country].forEach(function(province) {
                                            const option = document.createElement('option');
                                            option.value = province;
                                            option.textContent = province;
                                            provinceSelect.appendChild(option);
                                        });
                                    }
                                }
                                </script>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isLoggedIn): ?>
                    <!-- Save Address Option for Logged-in Users -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="save_address" name="save_address" value="1"
                                   class="mt-1 w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <div>
                                <label for="save_address" class="text-sm font-medium text-gray-900 cursor-pointer">
                                    Save this address to my address book
                                </label>
                                <p class="text-sm text-gray-500 mt-1">
                                    This will make it easier to checkout in the future.
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 ml-7">
                            <input type="checkbox" id="save_as_default_address" name="save_as_default_address" value="1"
                                   class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <label for="save_as_default_address" class="text-sm font-medium text-gray-900 cursor-pointer">
                                Set as my default shipping address
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Delivery Method -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">3</span>
                            Delivery Method
                        </h2>

                        <div class="space-y-3">
                            <?php foreach ($deliveryMethods as $index => $method): ?>
                                <?php
                                $effectiveCost = getEffectiveDeliveryCost($method, $subtotal);
                                $isFree = $effectiveCost == 0.0;
                                $hasThreshold = !empty($method['free_shipping_min_amount']) && floatval($method['free_shipping_min_amount']) > 0;
                                $thresholdAmount = $hasThreshold ? floatval($method['free_shipping_min_amount']) : null;
                                ?>
                                <label class="block cursor-pointer">
                                    <input type="radio" name="delivery_method" value="<?php echo $method['id'] ?>"
                                           class="peer sr-only" <?php echo $index === 0 ? 'checked' : '' ?>
                                           onchange="updateShipping(<?php echo number_format($effectiveCost, 2, '.', '') ?>)">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-gray-300 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <i class="fas fa-truck text-gray-400 text-xl mr-4"></i>
                                                <div>
                                                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($method['name']) ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($method['description'] ?? '') ?></p>
                                                    <?php if ($hasThreshold): ?>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            Free over R <?php echo number_format($thresholdAmount, 2) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <?php if ($isFree && $hasThreshold): ?>
                                                    <div class="text-green-600 font-bold">FREE</div>
                                                <?php elseif ($isFree): ?>
                                                    <span class="text-green-600 font-bold">FREE</span>
                                                <?php else: ?>
                                                    <span class="font-bold text-gray-900">
                                                        R <?php echo number_format($method['cost'], 2) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors['delivery_method'])): ?>
                            <p class="text-red-500 text-sm mt-2"><?php echo  $errors['delivery_method'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">4</span>
                            Payment Method
                        </h2>
                        
                        <div class="space-y-3">
                            <?php foreach ($paymentMethods as $index => $method): ?>
                                <?php
                                $icon = 'fa-credit-card';
                                if (($method['type'] ?? '') === 'bank_transfer') $icon = 'fa-university';
                                if (($method['type'] ?? '') === 'cash_on_delivery') $icon = 'fa-money-bill-wave';
                                if (($method['type'] ?? '') === 'crypto') $icon = 'fa-coins';

                                $typeKey = $method['type'] ?? '';
                                $fieldsForMethod = $typeKey && isset($manualPaymentDetails[$typeKey]) ? $manualPaymentDetails[$typeKey] : [];
                                ?>
                                <label class="block cursor-pointer">
                                    <input type="radio" name="payment_method" value="<?php echo  htmlspecialchars($method['type']) ?>" 
                                           class="peer sr-only" <?php echo  $index === 0 ? 'checked' : '' ?>>
                                    <div class="border-2 border-gray-200 rounded-lg p-4 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-gray-300 transition-colors">
                                        <div class="flex flex-col space-y-3">
                                            <div class="flex items-center">
                                                <i class="fas <?php echo  $icon ?> text-gray-400 text-xl mr-4"></i>
                                                <div>
                                                    <h3 class="font-semibold text-gray-900"><?php echo  htmlspecialchars($method['name']) ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo  htmlspecialchars($method['description'] ?? '') ?></p>
                                                </div>
                                            </div>
                                            <?php if (!empty($fieldsForMethod)): ?>
                                                <!-- Details hidden for checkout, will be shown on invoice -->
                                                <!--
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700">
                                                    <?php foreach ($fieldsForMethod as $field): ?>
                                                        <div class="flex justify-between">
                                                            <span class="font-medium mr-2"><?php echo htmlspecialchars($field['field_name']); ?>:</span>
                                                            <span class="text-gray-800 text-right break-all"><?php echo htmlspecialchars($field['field_value']); ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                -->
                                                <div class="text-sm text-gray-500 italic mt-2">
                                                    Payment details will be provided on your invoice after placing the order.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors['payment_method'])): ?>
                            <p class="text-red-500 text-sm mt-2"><?php echo  $errors['payment_method'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order Notes -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Order Notes (Optional)</h2>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                  placeholder="Any special instructions for your order..."><?php echo htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Gift Messaging -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-gift text-green-600 text-xl mr-3"></i>
                            Gift Option
                        </h2>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_gift" id="is_gift" value="1"
                                   onchange="toggleGiftMessage()"
                                   class="text-green-600 focus:ring-green-500 rounded">
                            <span class="ml-2 text-gray-700 font-medium">Is this a gift?</span>
                        </label>

                        <div id="gift-message-container" class="mt-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gift Message</label>
                            <textarea name="gift_message" id="gift_message" rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                      placeholder="Hi [Recipient Name],&#10;&#10;Enjoy your gift!&#10;&#10;From [Your Name]"><?php
                                $senderName = $isLoggedIn ? $currentUser['name'] : '';
                                echo "Hi [Recipient Name],\n\nEnjoy your gift!\n\nFrom " . htmlspecialchars($senderName);
                            ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">You can customize this message</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Order Summary -->
                <div class="lg:w-1/3">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-4">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>

                        <!-- Coupon Input -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Have a coupon?</label>
                            <div class="flex gap-2">
                                <input type="text" name="coupon_code" id="coupon_code"
                                       value="<?= htmlspecialchars($_SESSION['selected_coupon'] ?? '') ?>"
                                       placeholder="Enter coupon code"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 uppercase">
                                <button type="button" onclick="applyCoupon()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                                    Apply
                                </button>
                            </div>
                            <div id="coupon-message" class="mt-2 text-sm"></div>
                        </div>

                        <!-- Cart Items - Image Grid Only -->
                        <style>
                            .order-summary-grid {
                                display: grid;
                                grid-template-columns: repeat(3, 1fr);
                                gap: 8px;
                                max-height: 280px;
                                overflow-y: auto;
                                scrollbar-width: none;
                                -ms-overflow-style: none;
                            }
                            .order-summary-grid::-webkit-scrollbar {
                                display: none;
                            }
                            .grid-item {
                                position: relative;
                                aspect-ratio: 1;
                                border-radius: 8px;
                                overflow: hidden;
                                background: #f3f4f6;
                            }
                            .grid-item img {
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                            }
                            .qty-badge {
                                position: absolute;
                                bottom: 4px;
                                right: 4px;
                                background: rgba(0, 0, 0, 0.7);
                                color: white;
                                padding: 2px 6px;
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 600;
                            }
                        </style>

                        <div class="mb-6">
                            <?php if (count($cart) <= 9): ?>
                                <div class="order-summary-grid">
                                    <?php foreach ($cart as $item): ?>
                                        <div class="grid-item">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>"
                                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                                     loading="lazy"
                                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center\'><i class=\'fas fa-leaf text-green-400 text-2xl\'></i></div>';">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center">
                                                    <i class="fas fa-leaf text-green-400 text-2xl"></i>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($item['qty'] > 1): ?>
                                                <div class="qty-badge">x<?= $item['qty'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <!-- More than 9 items: show 9 + count -->
                                <div class="order-summary-grid">
                                    <?php
                                    $displayCount = 0;
                                    foreach ($cart as $item):
                                        if ($displayCount >= 9) break;
                                    ?>
                                        <div class="grid-item">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="" loading="lazy">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center">
                                                    <i class="fas fa-leaf text-green-400 text-2xl"></i>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($item['qty'] > 1): ?>
                                                <div class="qty-badge">x<?= $item['qty'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php
                                        $displayCount++;
                                        endforeach;
                                    ?>
                                </div>
                                <div class="text-center mt-3 text-sm text-gray-600">
                                    +<?= count($cart) - 9 ?> more items
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4 space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal (<?php echo $itemCount ?> items)</span>
                                <span class="font-medium text-gray-900">R <?php echo number_format($subtotal, 2) ?></span>
                            </div>

                            <?php if ($couponDiscount > 0): ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Coupon (<?= htmlspecialchars($appliedCoupon['code']) ?>)</span>
                                    <span class="font-medium text-green-600">-R <?php echo number_format($couponDiscount, 2) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between text-gray-600">
                                <span>Shipping</span>
                                <span class="font-medium text-gray-900" id="shippingDisplay">
                                    <?php echo $defaultShipping == 0 ? 'FREE' : 'R ' . number_format($defaultShipping, 2) ?>
                                </span>
                            </div>

                            <div class="border-t border-gray-200 pt-3 flex justify-between">
                                <span class="text-lg font-bold text-gray-900">Total</span>
                                <span class="text-xl font-bold text-green-600" id="totalDisplay">
                                    R <?php echo number_format($subtotal + $defaultShipping - $couponDiscount, 2) ?>
                                </span>
                            </div>
                        </div>
                        
                        <input type="hidden" id="subtotalValue" value="<?php echo  $subtotal ?>">
                        <input type="hidden" id="currentShipping" value="<?php echo  $defaultShipping ?>">
                        
                        <button type="submit" id="placeOrderBtn" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg mt-6 hover:bg-green-700 transition-colors flex items-center justify-center">
                            <i class="fas fa-lock mr-3"></i>Place Order
                        </button>
                        
                        <p class="text-center text-sm text-gray-500 mt-4">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Your payment information is secure
                        </p>
                        
                        <!-- Edit Cart Link -->
                        <div class="text-center mt-4">
                            <a href="<?php echo  url('/cart/') ?>" class="text-green-600 hover:text-green-700 text-sm font-medium">
                                <i class="fas fa-edit mr-1"></i>Edit Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Form submission handler
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('placeOrderBtn');
    
    // If client-side validation passes, show loader
    if (this.checkValidity()) {
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-3"></i>Processing...';
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    }
});

const subtotal = parseFloat(document.getElementById('subtotalValue').value);

function updateShipping(cost) {
    const shippingDisplay = document.getElementById('shippingDisplay');
    const totalDisplay = document.getElementById('totalDisplay');
    const currentShippingInput = document.getElementById('currentShipping');

    if (cost == 0) {
        shippingDisplay.textContent = 'FREE';
    } else {
        shippingDisplay.textContent = 'R ' + cost.toFixed(2);
    }

    currentShippingInput.value = cost;
    const total = subtotal + cost;
    totalDisplay.textContent = 'R ' + total.toFixed(2);
}

function toggleGiftMessage() {
    const checkbox = document.getElementById('is_gift');
    const container = document.getElementById('gift-message-container');
    if (checkbox.checked) {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

function applyCoupon() {
    const code = document.getElementById('coupon_code').value.trim();
    const messageDiv = document.getElementById('coupon-message');

    if (!code) {
        messageDiv.innerHTML = '<span class="text-red-500">Please enter a coupon code</span>';
        return;
    }

    // Submit form with coupon code
    const form = document.getElementById('checkoutForm');
    const couponInput = document.createElement('input');
    couponInput.type = 'hidden';
    couponInput.name = 'coupon_code';
    couponInput.value = code;
    form.appendChild(couponInput);

    messageDiv.innerHTML = '<span class="text-blue-500">Applying coupon...</span>';

    // Reload page to apply coupon
    setTimeout(() => {
        location.reload();
    }, 500);
}

function openAddressModal() {
    fetch('<?= url('/checkout/address_modal.php') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.addresses.length > 0) {
                showAddressModal(data.addresses);
            } else {
                window.location.href = '<?= userUrl('address-book/') ?>';
            }
        })
        .catch(error => {
            console.error('Error loading addresses:', error);
            window.location.href = '<?= userUrl('address-book/') ?>';
        });
}

function showAddressModal(addresses) {
    let addressesHtml = addresses.map(addr => `
        <label class="block cursor-pointer border-2 border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
            <input type="radio" name="saved_address" value="${addr.id}" class="peer sr-only" ${addr.default_for_shipping ? 'checked' : ''}>
            <div class="peer-checked:border-green-500 peer-checked:bg-green-50 rounded-md">
                ${addr.default_for_shipping ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mb-2">Default</span>' : ''}
                <p class="font-medium text-gray-900">${addr.name}</p>
                <p class="text-sm text-gray-600">${addr.address}</p>
                <p class="text-sm text-gray-600">${addr.city}, ${addr.postal_code}</p>
                ${addr.phone ? `<p class="text-sm text-gray-600">${addr.phone}</p>` : ''}
            </div>
        </label>
    `).join('');

    const modalHtml = `
        <div id="addressModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-900">Select Shipping Address</h3>
                    <button type="button" onclick="closeAddressModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh] space-y-3">
                    ${addressesHtml}
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-between">
                    <button type="button" onclick="closeAddressModal()" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                        Cancel
                    </button>
                    <div class="space-x-3">
                        <a href="<?= userUrl('address-book/add.php') ?>" class="px-4 py-2 text-green-600 hover:bg-green-50 rounded-lg">
                            <i class="fas fa-plus mr-1"></i>Add New Address
                        </a>
                        <button type="button" onclick="selectAddress()" class="px-6 py-2 bg-green-600 text-white hover:bg-green-700 rounded-lg">
                            Use Selected Address
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);
    document.body.style.overflow = 'hidden';
}

function closeAddressModal() {
    const modal = document.getElementById('addressModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

function selectAddress() {
    const selected = document.querySelector('input[name="saved_address"]:checked');
    if (selected) {
        const addressId = selected.value;
        const addressData = selected.closest('label');
        
        window.location.href = '<?= url('/checkout/') ?>?address_id=' + addressId;
    } else {
        alert('Please select an address');
    }
}

// Debug mode - show session and environment info
<?php if ($debugMode): ?>
console.log('=== CHECKOUT DEBUG INFO ===');
console.log('Cart items: <?php echo count($cart) ?>');
console.log('Subtotal: R<?php echo $subtotal ?>');
console.log('Delivery methods: <?php echo count($deliveryMethods) ?>');
console.log('Payment methods: <?php echo count($paymentMethods) ?>');
console.log('Is logged in: <?php echo $isLoggedIn ? 'true' : 'false' ?>');
<?php endif; ?>
</script>

<?php if ($debugMode): ?>
<!-- Debug Footer Info -->
<div style="background:#1e1e1e;color:#d4d4d4;padding:20px;margin:20px;border-radius:5px;font-family:monospace;">
    <h4 style="color:#10b981;margin-top:0;">🔧 Debug Session Summary</h4>
    <table style="width:100%;text-align:left;">
        <tr><td>Cart Items:</td><td><?php echo count($cart) ?></td></tr>
        <tr><td>Subtotal:</td><td>R<?php echo number_format($subtotal, 2) ?></td></tr>
        <tr><td>Delivery Methods:</td><td><?php echo count($deliveryMethods) ?></td></tr>
        <tr><td>Payment Methods:</td><td><?php echo count($paymentMethods) ?></td></tr>
        <tr><td>User:</td><td><?php echo $isLoggedIn ? 'Logged In' : 'Guest' ?></td></tr>
        <tr><td>Errors Collected:</td><td><?php echo count($checkoutErrors) ?></td></tr>
    </table>
    <?php if (!empty($checkoutErrors)): ?>
        <h5 style="color:#ef4444;margin-top:15px;">Errors:</h5>
        <ul style="color:#ef4444;">
            <?php foreach ($checkoutErrors as $err): ?>
                <li><?php echo htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
