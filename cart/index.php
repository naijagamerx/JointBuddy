<?php
/**
 * Cart Page - CannaBuddy
 * Displays cart contents with quantity controls and checkout button
 */

// Load session helper FIRST for consistent session handling
require_once __DIR__ . '/../includes/session_helper.php';
ensureSessionStarted();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/enhanced_error_handler.php';
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/cart_sync_service.php';

// Include database
require_once __DIR__ . '/../includes/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    $db = null;
    error_log("Database connection failed: " . $e->getMessage());
}

// Check login status - must verify user_logged_in is true, not just set
// Do this BEFORE POST handlers so they can use these variables
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$currentUser = null;
if ($isLoggedIn) {
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $quantities = $_POST['quantities'] ?? [];
        foreach ($quantities as $index => $qty) {
            if (isset($_SESSION['cart'][$index])) {
                $qty = max(1, intval($qty));
                $maxStock = $_SESSION['cart'][$index]['max_stock'] ?? 100;
                $_SESSION['cart'][$index]['qty'] = min($qty, $maxStock);
            }
        }
        // Sync to database for logged-in users
        if ($isLoggedIn && isset($currentUser['id']) && $db) {
            syncCartToDatabase($db, $currentUser['id']);
        }
        $_SESSION['cart_message'] = ['type' => 'success', 'text' => 'Cart updated successfully!'];
    }

    if ($action === 'remove' && isset($_POST['index'])) {
        $index = intval($_POST['index']);
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
        }
        // Sync to database for logged-in users
        if ($isLoggedIn && isset($currentUser['id']) && $db) {
            syncCartToDatabase($db, $currentUser['id']);
        }
        $_SESSION['cart_message'] = ['type' => 'success', 'text' => 'Item removed from cart.'];
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        // Clear from database for logged-in users
        if ($isLoggedIn && isset($currentUser['id']) && $db) {
            clearCartFromDatabase($db, $currentUser['id']);
        }
        $_SESSION['cart_message'] = ['type' => 'success', 'text' => 'Cart cleared.'];
    }

    // Redirect to prevent form resubmission
    header('Location: ' . url('/cart/'));
    exit;
}

// Get cart items
$cart = $_SESSION['cart'] ?? [];
$cartMessage = $_SESSION['cart_message'] ?? null;
unset($_SESSION['cart_message']);

// Calculate totals
$subtotal = 0;
$totalSavings = 0;
$itemCount = 0;

foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $itemCount += $item['qty'];
    if ($item['price'] < $item['original_price']) {
        $totalSavings += ($item['original_price'] - $item['price']) * $item['qty'];
    }
}

// Get delivery methods for estimation
$deliveryMethods = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM delivery_methods WHERE active = 1 ORDER BY cost ASC");
        $deliveryMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback delivery options
        $deliveryMethods = [
            ['id' => 1, 'name' => 'Standard Shipping', 'cost' => 50.00],
            ['id' => 2, 'name' => 'Express Shipping', 'cost' => 99.00]
        ];
    }
}

$minShipping = !empty($deliveryMethods) ? $deliveryMethods[0]['cost'] : 50.00;
$freeShippingThreshold = 500;
$qualifiesForFreeShipping = $subtotal >= $freeShippingThreshold;

// Note: Database sync is handled by header.php and POST actions - no sync here to avoid duplication loop

$pageTitle = "Shopping Cart";
include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 border-b">
    <div class="container mx-auto px-4 py-3 max-w-7xl">
        <nav class="flex items-center text-sm">
            <a href="<?php echo  url('/') ?>" class="text-gray-500 hover:text-green-600">Home</a>
            <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
            <span class="text-gray-900 font-medium">Shopping Cart</span>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Page Title -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
            <p class="text-gray-600 mt-1"><?php echo  $itemCount ?> item<?php echo  $itemCount !== 1 ? 's' : '' ?> in your cart</p>
        </div>
        <?php if (!empty($cart)): ?>
            <form method="POST" onsubmit="return confirm('Are you sure you want to clear your cart?');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="text-red-600 hover:text-red-700 font-medium">
                    <i class="fas fa-trash mr-2"></i>Clear Cart
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Cart Message -->
    <?php if ($cartMessage): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo  $cartMessage['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <div class="flex items-center">
                <i class="fas <?php echo  $cartMessage['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-3"></i>
                <?php echo  htmlspecialchars($cartMessage['text']) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($cart)): ?>
        <!-- Empty Cart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-shopping-cart text-5xl text-gray-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                Looks like you haven't added any items to your cart yet. Start shopping and discover our amazing products!
            </p>
            <a href="<?php echo  shopUrl('/') ?>" class="inline-block bg-green-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-shopping-bag mr-3"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="lg:w-2/3">
                <form method="POST" id="cartForm">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Cart Header -->
                        <div class="hidden md:grid md:grid-cols-12 gap-4 p-4 border-b border-gray-100 bg-gray-50 text-sm font-semibold text-gray-600">
                            <div class="col-span-6">Product</div>
                            <div class="col-span-2 text-center">Price</div>
                            <div class="col-span-2 text-center">Quantity</div>
                            <div class="col-span-2 text-right">Total</div>
                        </div>
                        
                        <!-- Cart Items -->
                        <?php foreach ($cart as $index => $item): ?>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4 border-b border-gray-100 items-center">
                                <!-- Product -->
                                <div class="col-span-6 flex items-center gap-4">
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden"
                                         style="width:100px;height:100px;">
                                        <a href="<?php echo  url('/product/' . htmlspecialchars($item['slug'])) ?>" class="block w-full h-full">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo  htmlspecialchars($item['image']) ?>"
                                                     alt="Product image for <?php echo  htmlspecialchars($item['name']) ?>"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy"
                                                     onerror="this.onerror=null;this.src='<?php echo  assetUrl('images/products/placeholder.png') ?>';">
                                            <?php else: ?>
                                                <img src="<?php echo  assetUrl('images/products/placeholder.png') ?>"
                                                     alt="Product image for <?php echo  htmlspecialchars($item['name']) ?>"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy">
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 mb-1">
                                            <a href="<?php echo  url('/product/' . htmlspecialchars($item['slug'])) ?>" class="hover:text-green-600">
                                                <?php echo  htmlspecialchars($item['name']) ?>
                                            </a>
                                        </h3>
                                        <?php if ($item['price'] < $item['original_price']): ?>
                                            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-medium">
                                                On Sale
                                            </span>
                                        <?php endif; ?>
                                        <!-- Mobile Remove Button -->
                                        <button type="button" onclick="removeItem(<?php echo  $index ?>)" class="md:hidden text-red-500 text-sm mt-2">
                                            <i class="fas fa-trash mr-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Price -->
                                <div class="col-span-2 text-center">
                                    <?php if ($item['price'] < $item['original_price']): ?>
                                        <div class="text-red-600 font-bold">R <?php echo  number_format($item['price'], 2) ?></div>
                                        <div class="text-gray-400 text-sm line-through">R <?php echo  number_format($item['original_price'], 2) ?></div>
                                    <?php else: ?>
                                        <div class="text-gray-900 font-bold">R <?php echo  number_format($item['price'], 2) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Quantity -->
                                <div class="col-span-2 flex justify-center">
                                    <div class="flex items-center border border-gray-300 rounded-lg">
                                        <button type="button" onclick="decreaseQty(<?php echo  $index ?>)" class="px-3 py-2 hover:bg-gray-100 transition-colors">
                                            <i class="fas fa-minus text-gray-600 text-xs"></i>
                                        </button>
                                        <input type="number" 
                                               name="quantities[<?php echo  $index ?>]" 
                                               value="<?php echo  $item['qty'] ?>" 
                                               min="1" 
                                               max="<?php echo  $item['max_stock'] ?? 100 ?>"
                                               class="w-12 text-center border-x border-gray-300 py-2 focus:outline-none text-sm"
                                               onchange="updateCart()">
                                        <button type="button" onclick="increaseQty(<?php echo  $index ?>)" class="px-3 py-2 hover:bg-gray-100 transition-colors">
                                            <i class="fas fa-plus text-gray-600 text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Total -->
                                <div class="col-span-2 text-right">
                                    <div class="font-bold text-gray-900">R <?php echo  number_format($item['price'] * $item['qty'], 2) ?></div>
                                    <!-- Desktop Remove Button -->
                                    <button type="button" onclick="removeItem(<?php echo  $index ?>)" class="hidden md:inline text-red-500 text-sm mt-1 hover:text-red-600">
                                        <i class="fas fa-trash mr-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Update Cart Button -->
                    <div class="mt-4 flex justify-between items-center">
                        <a href="<?php echo  shopUrl('/') ?>" class="text-green-600 hover:text-green-700 font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                        </a>
                        <button type="submit" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Update Cart
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>
                    
                    <!-- Free Shipping Progress -->
                    <?php if (!$qualifiesForFreeShipping): ?>
                        <?php $remaining = $freeShippingThreshold - $subtotal; ?>
                        <div class="bg-green-50 rounded-lg p-4 mb-6">
                            <div class="flex items-center text-green-700 mb-2">
                                <i class="fas fa-truck mr-2"></i>
                                <span class="font-medium">Free shipping on orders over R<?php echo  number_format($freeShippingThreshold, 0) ?></span>
                            </div>
                            <div class="w-full bg-green-200 rounded-full h-2 mb-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo  min(100, ($subtotal / $freeShippingThreshold) * 100) ?>%"></div>
                            </div>
                            <p class="text-sm text-green-600">Add R<?php echo  number_format($remaining, 2) ?> more to qualify</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-50 rounded-lg p-4 mb-6">
                            <div class="flex items-center text-green-700">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span class="font-medium">You qualify for FREE shipping!</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Summary Lines -->
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal (<?php echo  $itemCount ?> items)</span>
                            <span class="font-medium text-gray-900">R <?php echo  number_format($subtotal, 2) ?></span>
                        </div>
                        
                        <?php if ($totalSavings > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>You Save</span>
                                <span class="font-medium">- R <?php echo  number_format($totalSavings, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <?php if ($qualifiesForFreeShipping): ?>
                                <span class="font-medium text-green-600">FREE</span>
                            <?php else: ?>
                                <span class="font-medium text-gray-900">From R <?php echo  number_format($minShipping, 2) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4 flex justify-between">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-xl font-bold text-green-600">
                                R <?php echo  number_format($subtotal + ($qualifiesForFreeShipping ? 0 : $minShipping), 2) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Checkout Button -->
                    <a href="<?php echo  url('/checkout/') ?>" class="block w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg text-center hover:bg-green-700 transition-colors mb-4">
                        <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                    </a>
                    
                    <!-- Payment Methods -->
                    <div class="text-center text-sm text-gray-500 mb-4">
                        <i class="fas fa-shield-alt mr-1"></i>Secure checkout
                    </div>
                    
                    <div class="flex justify-center gap-2">
                        <i class="fab fa-cc-visa text-2xl text-gray-400"></i>
                        <i class="fab fa-cc-mastercard text-2xl text-gray-400"></i>
                        <i class="fab fa-cc-amex text-2xl text-gray-400"></i>
                        <i class="fab fa-cc-paypal text-2xl text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Remove Item Form (Hidden) -->
<form id="removeForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="remove">
    <input type="hidden" name="index" id="removeIndex">
</form>

<script>
function decreaseQty(index) {
    const input = document.querySelector(`input[name="quantities[${index}]"]`);
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateCart();
    }
}

function increaseQty(index) {
    const input = document.querySelector(`input[name="quantities[${index}]"]`);
    const max = parseInt(input.max);
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
        updateCart();
    }
}

function updateCart() {
    document.getElementById('cartForm').submit();
}

function removeItem(index) {
    if (confirm('Remove this item from your cart?')) {
        document.getElementById('removeIndex').value = index;
        document.getElementById('removeForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
