<?php
/**
 * User Detail View - CannaBuddy Admin
 * Displays comprehensive user information including profile, orders, wishlist, cart, and statistics
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get user ID from URL path
// URL format: /admin/users/view/[id]
$userId = null;

// First check if ID was passed via query string (for backwards compatibility)
if (isset($_GET['id'])) {
    $userId = $_GET['id'];
} else {
    // Extract from URL path
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = trim($path, '/');

    // Extract the ID from the path: admin/users/view/[id]
    if (preg_match('/admin\/users\/view\/(\d+)/', $path, $matches)) {
        $userId = $matches[1];
    }
}

if (!$userId) {
    // Store error message in session for display
    $_SESSION['admin_error'] = 'User ID not specified';
    header('Location: ' . adminUrl('/users/'));
    exit;
}

// Validate user ID is numeric
if (!is_numeric($userId)) {
    $_SESSION['admin_error'] = 'Invalid user ID';
    header('Location: ' . adminUrl('/users/'));
    exit;
}

// Convert to integer for security
$userId = (int)$userId;

// Initialize data variables
$user = null;
$orders = [];
$wishlistItems = [];
$userAddresses = [];
$userCartItems = [];
$stats = [
    'total_orders' => 0,
    'total_spent' => 0,
    'average_order' => 0,
    'last_order_date' => null
];

// Fetch user data
if ($db) {
    try {
        // Get user profile
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User not found, redirect to list with error
            $_SESSION['admin_error'] = 'User not found';
            header('Location: ' . adminUrl('/users/'));
            exit;
        }

        // Get user addresses from user_addresses table
        $stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY default_for_shipping DESC, created_at DESC");
        $stmt->execute([$userId]);
        $userAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent orders (last 10)
        $stmt = $db->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$user['email']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get wishlist items
        $stmt = $db->prepare("SELECT w.*, p.name as product_name, p.image_1, p.price, p.slug
                            FROM wishlists w
                            JOIN products p ON w.product_id = p.id
                            WHERE w.user_id = ?
                            ORDER BY w.created_at DESC");
        $stmt->execute([$userId]);
        $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get user cart items from user_carts table
        $stmt = $db->prepare("
            SELECT uc.*, p.name as product_name, p.slug as product_slug, p.images as product_images, p.price
            FROM user_carts uc
            JOIN products p ON uc.product_id = p.id
            WHERE uc.user_id = ?
            ORDER BY uc.updated_at DESC
        ");
        $stmt->execute([$userId]);
        $userCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get account statistics
        if (!empty($orders)) {
            $stats['total_orders'] = count($orders);

            $totalSpent = 0;
            foreach ($orders as $order) {
                if ($order['status'] !== 'cancelled') {
                    $totalSpent += (float)($order['total_amount'] ?? 0);
                }
            }
            $stats['total_spent'] = $totalSpent;
            $stats['average_order'] = $stats['total_orders'] > 0 ? ($totalSpent / $stats['total_orders']) : 0;
            $stats['last_order_date'] = $orders[0]['created_at'] ?? null;
        }

    } catch (Exception $e) {
        $error = AppError::handleDatabaseError($e, 'Error loading user data');
    }
}

// Generate content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="' . adminUrl('/users/') . '" class="inline-flex items-center text-green-600 hover:text-green-500 text-sm font-medium">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Users
        </a>
    </div>

    <!-- User Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-green-600 text-3xl"></i>
                </div>
                <div class="ml-6">
                    <h1 class="text-3xl font-bold text-gray-900">'
                        . safe_html(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) .
                    '</h1>
                    <p class="text-gray-600 mt-1">' . safe_html($user['email'] ?? 'N/A') . '</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' .
                            ($user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . '">
                            ' . ($user['is_active'] ? 'Active' : 'Inactive') . '
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">';

$content .= adminStatCard('Total Orders', $stats['total_orders'], 'fas fa-shopping-bag', 'blue');
$content .= adminStatCard('Total Spent', 'R' . number_format($stats['total_spent'], 2), 'fas fa-dollar-sign', 'green');
$content .= adminStatCard('Average Order', 'R' . number_format($stats['average_order'], 2), 'fas fa-chart-line', 'purple');
$content .= adminStatCard('Last Order',
    $stats['last_order_date'] ? date('M j, Y', strtotime($stats['last_order_date'])) : 'Never',
    'fas fa-calendar-alt',
    'yellow'
);

$content .= '
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Profile & Addresses -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-user-circle mr-2 text-green-600"></i>
                    Profile Information
                </h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">First Name</label>
                            <p class="mt-1 text-sm text-gray-900">' . safe_html($user['first_name'] ?? 'N/A') . '</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Last Name</label>
                            <p class="mt-1 text-sm text-gray-900">' . safe_html($user['last_name'] ?? 'N/A') . '</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="mt-1 text-sm text-gray-900">' . safe_html($user['email'] ?? 'N/A') . '</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="mt-1 text-sm text-gray-900">' . safe_html($user['phone'] ?? 'N/A') . '</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Address</label>
                        <div class="mt-1 text-sm text-gray-900">';
                        if (!empty($userAddresses)) {
                            $primaryAddress = $userAddresses[0]; // Already sorted by default_for_shipping DESC
                            $addressParts = [];
                            if (!empty($primaryAddress['address_line1'])) $addressParts[] = safe_html($primaryAddress['address_line1']);
                            if (!empty($primaryAddress['address_line2'])) $addressParts[] = safe_html($primaryAddress['address_line2']);
                            if (!empty($primaryAddress['city'])) $addressParts[] = safe_html($primaryAddress['city']);
                            if (!empty($primaryAddress['province'])) $addressParts[] = safe_html($primaryAddress['province']);
                            if (!empty($primaryAddress['postal_code'])) $addressParts[] = safe_html($primaryAddress['postal_code']);
                            $content .= implode(', ', $addressParts);
                            if (!empty($primaryAddress['label'])) {
                                $content .= ' <span class="text-xs text-gray-500">(' . safe_html($primaryAddress['label']) . ')</span>';
                            }
                        } else {
                            $content .= 'N/A';
                        }
                        $content .= '</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Phone</label>
                            <p class="mt-1 text-sm text-gray-900">' . safe_html($userAddresses[0]['phone'] ?? $user['phone'] ?? 'N/A') . '</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Country</label>
                            <p class="mt-1 text-sm text-gray-900">' . safe_html($userAddresses[0]['country'] ?? 'South Africa') . '</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Member Since</label>
                            <p class="mt-1 text-sm text-gray-900">' . date('M j, Y', strtotime($user['created_at'] ?? 'now')) . '</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Last Login</label>
                            <p class="mt-1 text-sm text-gray-900">'
                                . ($user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never') .
                            '</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saved Addresses -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                    Saved Addresses (' . count($userAddresses) . ')
                </h2>';

if (empty($userAddresses)) {
    $content .= '
                <div class="text-center py-6">
                    <i class="fas fa-map-marker-alt text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-600 text-sm">No saved addresses</p>
                </div>';
} else {
    $content .= '
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    foreach ($userAddresses as $addr) {
        $addrLabel = !empty($addr['label']) ? safe_html($addr['label']) : 'Address';
        $isDefault = $addr['default_for_shipping'] ? ' <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Default</span>' : '';
        $addrStr = '';
        if (!empty($addr['address_line1'])) $addrStr .= safe_html($addr['address_line1']) . '<br>';
        if (!empty($addr['address_line2'])) $addrStr .= safe_html($addr['address_line2']) . '<br>';
        $cityParts = array_filter([$addr['city'] ?? '', $addr['province'] ?? '', $addr['postal_code'] ?? '']);
        if (!empty($cityParts)) $addrStr .= implode(', ', $cityParts);
        $content .= '
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-medium text-gray-900">' . $addrLabel . '</span>
                            ' . $isDefault . '
                        </div>
                        <div class="text-sm text-gray-600">
                            ' . $addrStr . '
                        </div>
                        <div class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-phone mr-1"></i>' . safe_html($addr['phone'] ?? 'No phone') . '
                        </div>
                    </div>';
    }
    $content .= '
                </div>';
}

$content .= '
            </div>
        </div>

        <!-- Right Column: Wishlist & Cart -->
        <div class="space-y-6">
            <!-- Wishlist -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-heart mr-2 text-red-500"></i>
                    Wishlist
                </h2>';

if (empty($wishlistItems)) {
    $content .= '
                <div class="text-center py-6">
                    <i class="fas fa-heart text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-600 text-sm">No items in wishlist</p>
                </div>';
} else {
    $content .= '
                <div class="space-y-3">';

    foreach ($wishlistItems as $item) {
        $content .= '
                    <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-green-300 transition-colors">
                        <img src="' . safe_html($item['image_1'] ?? url('/assets/images/placeholder.png')) . '"
                             alt="' . safe_html($item['product_name']) . '"
                             class="w-12 h-12 rounded-lg object-cover">
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">' . safe_html($item['product_name']) . '</p>
                            <p class="text-sm text-gray-500">R' . number_format($item['price'], 2) . '</p>
                        </div>
                    </div>';
    }

    $content .= '
                </div>';
}

$content .= '
            </div>

            <!-- Current Cart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-shopping-basket mr-2 text-blue-600"></i>
                    Current Cart
                </h2>';

if (empty($userCartItems)) {
    $content .= '
                <div class="text-center py-6">
                    <i class="fas fa-shopping-basket text-3xl text-gray-300 mb-2"></i>
                    <p class="text-gray-600 text-sm">Cart is empty</p>
                </div>';
} else {
    $content .= '
                <div class="divide-y divide-gray-100">';

    foreach ($userCartItems as $cartItem) {
        $productImages = $cartItem['product_images'] ?? '';
        $imageUrl = '';
        if ($productImages) {
            $imageParts = explode(',', $productImages);
            $firstImage = trim($imageParts[0]);
            if (!empty($firstImage)) {
                $imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
                $imageUrl = url($imagePath);
            }
        }

        $content .= '
                    <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden border border-gray-200">';
        if (!empty($imageUrl)) {
            $content .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($cartItem['product_name']) . '" class="w-full h-full object-cover">';
        } else {
            $content .= '<i class="fas fa-box text-gray-400 text-xl"></i>';
        }
        $content .= '
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">' . safe_html($cartItem['product_name']) . '</p>
                            <p class="text-sm text-gray-500">Qty: ' . (int)$cartItem['quantity'] . ' × R' . number_format($cartItem['price'], 2) . '</p>
                            ' . (!empty($cartItem['variation']) ? '<p class="text-xs text-gray-400">Variation: ' . safe_html($cartItem['variation']) . '</p>' : '') . '
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-gray-900">R' . number_format(($cartItem['price'] ?? 0) * ($cartItem['quantity'] ?? 1), 2) . '</p>
                        </div>
                    </div>';
    }

    $cartTotal = 0;
    foreach ($userCartItems as $cartItem) {
        $cartTotal += ($cartItem['price'] ?? 0) * ($cartItem['quantity'] ?? 1);
    }

    $content .= '
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-end">
                            <div class="text-sm">
                                <span class="text-gray-600 mr-4">Cart Total:</span>
                                <span class="text-lg font-bold text-gray-900">R' . number_format($cartTotal, 2) . '</span>
                            </div>
                        </div>
                    </div>
                </div>';
}

$content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900">
                <i class="fas fa-shopping-cart mr-2 text-green-600"></i>
                Recent Orders
            </h2>
        </div>';

if (empty($orders)) {
    $content .= '
                <div class="text-center py-8">
                    <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-600">No orders yet</p>
                </div>';
} else {
    $content .= '
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">';

    foreach ($orders as $order) {
        $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800'
        ];
        $statusClass = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';

        $content .= '
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #' . safe_html($order['order_number'] ?? $order['id']) . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ' . date('M j, Y', strtotime($order['created_at'])) . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">
                                        ' . ucfirst(safe_html($order['status'])) . '
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    R' . number_format($order['total_amount'] ?? 0, 2) . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="' . adminUrl('/orders/view/?id=' . $order['id']) . '" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>';
    }

    $content .= '
                        </tbody>
                    </table>
                </div>';
}

$content .= '
            </div>
        </div>
    </div>
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('User: ' . safe_html(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')), $content, 'users');
?>
