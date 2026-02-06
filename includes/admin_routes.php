<?php
// includes/admin_routes.php
require_once __DIR__ . '/url_helper.php';

// Setup Whoops for beautiful error pages if debug mode is enabled
if (isset($_GET['debug']) || isset($_GET['whoops'])) {
    require_once __DIR__ . '/whoops_handler.php';
}

// Ensure admin auth for these routes
if (strpos($route, 'admin') === 0 && $route !== 'admin/login') {
    if (!$adminAuth || !$adminAuth->isLoggedIn()) {
        header('Location: ' . adminUrl('/login/'));
        exit;
    }
}

// Use the passed currency service or global fallback
$currencyServiceToUse = $adminRoutesCurrencyService ?? $GLOBALS['currencyService'] ?? null;

// SEO Dashboard
if ($route === 'admin/seo') {
    require __DIR__ . '/../admin/seo/index.php';
    exit;
}

// Categories Management
if ($route === 'admin/categories') {
    require __DIR__ . '/../admin/categories.php';
    exit;
}

// Analytics Dashboard
if ($route === 'admin/analytics') {
    require __DIR__ . '/../admin/analytics.php';
    exit;
}

// Hero Images Management
if ($route === 'admin/hero-images') {
    require __DIR__ . '/../admin/hero-images.php';
    exit;
}

// Product Reviews
if ($route === 'admin/products/reviews') {
    require __DIR__ . '/../admin/products/reviews.php';
    exit;
}

// Product Inquiries
if ($route === 'admin/products/inquiries') {
    require __DIR__ . '/../admin/products/inquiries.php';
    exit;
}

// Product Inventory
if ($route === 'admin/products/inventory') {
    require __DIR__ . '/../admin/products/inventory.php';
    exit;
}

// Currency Settings
if ($route === 'admin/currency' || $route === 'admin/settings/currency') {
    require __DIR__ . '/../admin/settings/currency.php';
    exit;
}

// Settings - Appearance
if ($route === 'admin/settings/appearance') {
    require __DIR__ . '/../admin/settings/appearance.php';
    exit;
}

// Settings - Email
if ($route === 'admin/settings/email') {
    require __DIR__ . '/../admin/settings/email.php';
    exit;
}

// Settings - Notifications
if ($route === 'admin/settings/notifications') {
    require __DIR__ . '/../admin/settings/notifications.php';
    exit;
}

// Currency Actions (Add/Delete)
if ($route === 'admin/currency/add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
    $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
    $rate = isset($_POST['exchange_rate']) ? (float)$_POST['exchange_rate'] : 0.0;
    if ($db && $code !== '' && $symbol !== '' && $rate > 0) {
        try {
            // Insert or update currency
            $stmt = $db->prepare('INSERT INTO currencies (code, symbol, is_active, is_default) VALUES (?, ?, 1, 0)
                                  ON DUPLICATE KEY UPDATE symbol = VALUES(symbol), is_active = 1');
            $stmt->execute([$code, $symbol]);

            // Insert or update exchange rate
            $stmt = $db->prepare('INSERT INTO exchange_rates (currency_code, rate, updated_at) VALUES (?, ?, NOW())
                                  ON DUPLICATE KEY UPDATE rate = VALUES(rate), updated_at = NOW()');
            $stmt->execute([$code, $rate]);

            $_SESSION['success'] = 'Currency ' . $code . ' added/updated successfully';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to save currency: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Invalid currency data provided';
    }
    header('Location: ' . adminUrl('/currency'));
    exit;
}

if (strpos($route, 'admin/currency/delete/') === 0) {
    $code = substr($route, strlen('admin/currency/delete/'));
    if ($currencyServiceToUse && !empty($code) && strtoupper($code) !== 'USD') {
        try {
            $stmt = $db->prepare('DELETE FROM currencies WHERE code = ? AND is_default = 0');
            $stmt->execute([$code]);
            $stmt = $db->prepare('DELETE FROM exchange_rates WHERE currency_code = ?');
            $stmt->execute([$code]);
        } catch (Exception $e) {}
    }
    header('Location: ' . adminUrl('/currency'));
    exit;
}

// Newsletter
if ($route === 'admin/newsletter' || $route === 'admin/users/newsletter') {
    require __DIR__ . '/../admin/newsletter/index.php';
    exit;
}

// Users Management
if ($route === 'admin/users') {
    $users = [];
    if (isset($db)) {
        try {
            $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
    
    $content = '
    <div class="w-full max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold mb-6 text-gray-900">Users</h1>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';
    
    if (empty($users)) {
        $content .= '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No users found.</td></tr>';
    } else {
        foreach ($users as $user) {
            $content .= '<tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($user['name'] ?? 'N/A') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($user['email'] ?? '') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($user['role'] ?? 'customer') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('M j, Y', strtotime($user['created_at'] ?? 'now')) . '</td>
            </tr>';
        }
    }

    $content .= '</tbody></table></div></div>';
    
    echo renderAdminPage('Users - CannaBuddy Admin', $content, 'users');
    exit;
}

// General Settings: redirect to file-based page to avoid double wrapping
// if ($route === 'admin/settings') {
//    header('Location: ' . adminUrl('/settings'));
//    exit;
// }

// Product Variations
if ($route === 'admin/products/variations') {
    $content = '<div class="w-full max-w-7xl mx-auto"><h1 class="text-3xl font-bold mb-6">Product Variations</h1><div class="bg-white shadow rounded-lg p-6"><p>Manage global product variations here (Sizes, Colors, etc).</p></div></div>';
    echo renderAdminPage('Variations - CannaBuddy Admin', $content, 'variations');
    exit;
}

// Product Inquiries
if ($route === 'admin/products/inquiries') {
    $content = '<div class="w-full max-w-7xl mx-auto"><h1 class="text-3xl font-bold mb-6">Product Inquiries</h1><div class="bg-white shadow rounded-lg p-6"><p>View and respond to customer inquiries about products.</p></div></div>';
    echo renderAdminPage('Inquiries - CannaBuddy Admin', $content, 'inquiries');
    exit;
}

// Payment Methods
if ($route === 'admin/payment-methods') {
    $content = '<div class="w-full max-w-7xl mx-auto"><h1 class="text-3xl font-bold mb-6">Payment Methods</h1><div class="bg-white shadow rounded-lg p-6"><p>Configure payment gateways (Stripe, PayPal, COD).</p></div></div>';
    echo renderAdminPage('Payment Methods - CannaBuddy Admin', $content, 'payment');
    exit;
}

// Delivery Methods
if ($route === 'admin/delivery-methods') {
    $content = '<div class="w-full max-w-7xl mx-auto"><h1 class="text-3xl font-bold mb-6">Delivery Methods</h1><div class="bg-white shadow rounded-lg p-6"><p>Configure delivery options (standard, express, pickup).</p></div></div>';
    echo renderAdminPage('Delivery Methods - CannaBuddy Admin', $content, 'delivery');
    exit;
}

// Returns Management
if ($route === 'admin/returns') {
    require __DIR__ . '/../admin/returns/index.php';
    exit;
}

if ($route === 'admin/returns/view') {
    require __DIR__ . '/../admin/returns/view.php';
    exit;
}

if ($route === 'admin/returns/settings') {
    require __DIR__ . '/../admin/returns/settings.php';
    exit;
}
if ($route === 'admin/delivery-methods') {
    $content = '<div class="w-full max-w-7xl mx-auto"><h1 class="text-3xl font-bold mb-6">Delivery Methods</h1><div class="bg-white shadow rounded-lg p-6"><p>Configure shipping zones and rates.</p></div></div>';
    echo renderAdminPage('Delivery Methods - CannaBuddy Admin', $content, 'delivery');
    exit;
}

// Homepage Slider
if ($route === 'admin/slider' || $route === 'admin/homepage-slider') {
    header('Location: ' . adminUrl('/slider/'));
    exit;
}

// Analytics - Real Data
if ($route === 'admin/analytics') {
    $totalSales = 0;
    $totalOrders = 0;
    $totalProducts = 0;
    $totalUsers = 0;
    $recentOrders = [];
    
    if (isset($db)) {
        try {
            // Total Sales & Orders
            $stmt = $db->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalOrders = $res['count'] ?? 0;
            $totalSales = $res['total'] ?? 0;
            
            // Total Products
            $stmt = $db->query("SELECT COUNT(*) as count FROM products");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalProducts = $res['count'] ?? 0;
            
            // Total Users
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalUsers = $res['count'] ?? 0;
            
            // Recent Orders
            $stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Analytics Error: " . $e->getMessage());
        }
    }
    
    $content = '
    <div class="w-full max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold mb-6 text-gray-900">Analytics Dashboard</h1>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Sales -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Sales</h3>
                        <div class="p-2 bg-green-100 rounded-full text-green-600"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900">$' . number_format($totalSales, 2) . '</p>
                    </div>
                </div>
                
                <!-- Orders -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Orders</h3>
                        <div class="p-2 bg-blue-100 rounded-full text-blue-600"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900">' . number_format($totalOrders) . '</p>
                    </div>
                </div>
                
                <!-- Products -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Products</h3>
                        <div class="p-2 bg-purple-100 rounded-full text-purple-600"><i class="fas fa-box"></i></div>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900">' . number_format($totalProducts) . '</p>
                    </div>
                </div>
                
                <!-- Users -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                        <div class="p-2 bg-yellow-100 rounded-full text-yellow-600"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="flex items-baseline">
                        <p class="text-2xl font-bold text-gray-900">' . number_format($totalUsers) . '</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Orders</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">';
    
    if (empty($recentOrders)) {
        $content .= '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent orders found.</td></tr>';
    } else {
        foreach ($recentOrders as $order) {
            $statusColor = match($order['status'] ?? '') {
                'completed', 'delivered' => 'bg-green-100 text-green-800',
                'processing' => 'bg-blue-100 text-blue-800',
                'cancelled' => 'bg-red-100 text-red-800',
                default => 'bg-gray-100 text-gray-800'
            };
            
            $content .= '<tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#' . htmlspecialchars($order['id']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($order['customer_name'] ?? 'Guest') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$' . number_format($order['total_amount'] ?? 0, 2) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusColor . '">
                        ' . ucfirst(htmlspecialchars($order['status'] ?? 'pending')) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('M j, Y', strtotime($order['created_at'] ?? 'now')) . '</td>
            </tr>';
        }
    }
                        
    $content .= '</tbody></table></div></div></div>';
    
    echo renderAdminPage('Analytics - CannaBuddy Admin', $content, 'analytics');
    exit;
}

 

// Product Management (Add/Edit/Delete)
if ($route === 'admin/products/add') {
    require __DIR__ . '/../admin/products/add.php';
    exit;
}

if (false && strpos($route, 'admin/products/view/') === 0) {
    $slug = substr($route, strlen('admin/products/view/'));
    $product = null;
    $error = '';
    if ($db) {
        try {
            $stmt = $db->prepare('SELECT * FROM products WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) { $error = 'Product not found'; }
        } catch (Exception $e) { $error = $e->getMessage(); }
    } else { $error = 'Database not available'; }

    // Get all product images - check 'images' field first, then fallback to 'image_1', 'image_2', etc.
    $images = [];
    if ($product && !empty($product['images'])) {
        // New format: comma-separated URLs
        $parts = preg_split('/\s*,\s*/', $product['images']);
        foreach ($parts as $url) { if ($url) { $images[] = $url; } }
    } elseif ($product) {
        // Legacy format: image_1, image_2, image_3, image_4
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($product['image_' . $i])) {
                $images[] = $product['image_' . $i];
            }
        }
    }

    $content = '<div class="w-full max-w-7xl mx-auto">';
    $content .= '<div class="flex justify-between items-center mb-6"><h1 class="text-3xl font-bold text-gray-900">View Product</h1><div class="flex gap-3"><a href="' . adminUrl('/products/') . '" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"><i class="fas fa-arrow-left mr-2"></i>Back to Products</a><a href="' . adminUrl('/products/edit/' . htmlspecialchars($slug)) . '" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"><i class="fas fa-edit mr-2"></i>Edit Product</a><a href="' . url('/product/' . htmlspecialchars($slug)) . '" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"><i class="fas fa-external-link-alt mr-2"></i>View on Site</a></div></div>';
    if ($error) { $content .= '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 text-red-800 rounded-lg">' . htmlspecialchars($error) . '</div>'; }
    if ($product) {
        $content .= '<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">';

        // Product Images Section
        $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">';
        $content .= '<div class="px-6 py-4 border-b border-gray-200 bg-gray-50"><h2 class="text-lg font-semibold text-gray-900"><i class="fas fa-images mr-2 text-green-600"></i>Product Images (' . count($images) . ')</h2></div>';
        $content .= '<div class="p-6">';

        if (!empty($images)) {
            // Main image display
            $content .= '<div class="mb-4">';
            $content .= '<div class="relative bg-gray-100 rounded-lg overflow-hidden aspect-square flex items-center justify-center" id="mainImageContainer">';
            $content .= '<img src="' . htmlspecialchars($images[0]) . '" id="mainProductImage" alt="' . htmlspecialchars($product['name']) . '" class="w-full h-full object-contain">';

            // Badges
            if ((int)($product['featured'] ?? 0) === 1) {
                $content .= '<div class="absolute top-4 left-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg"><i class="fas fa-star mr-1"></i>Featured</div>';
            }
            if ((int)($product['active'] ?? 0) === 0) {
                $content .= '<div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">Inactive</div>';
            }
            $content .= '</div>';
            $content .= '</div>';

            // Thumbnail gallery
            $content .= '<div class="grid grid-cols-5 gap-3">';
            foreach ($images as $index => $img) {
                $isActive = $index === 0 ? 'ring-2 ring-green-500' : 'ring-1 ring-gray-200 hover:ring-green-300';
                $content .= '<button onclick="changeMainImage(\'' . htmlspecialchars($img, ENT_QUOTES) . '\', this)" class="aspect-square bg-gray-100 rounded-lg overflow-hidden transition-all ' . $isActive . '">';
                $content .= '<img src="' . htmlspecialchars($img) . '" alt="Image ' . ($index + 1) . '" class="w-full h-full object-cover">';
                $content .= '</button>';
            }
            $content .= '</div>';
        } else {
            $content .= '<div class="h-96 bg-gray-100 rounded-lg flex items-center justify-center"><div class="text-center"><i class="fas fa-image text-6xl text-gray-300 mb-4"></i><p class="text-gray-500">No images available</p></div></div>';
        }
        $content .= '</div></div>';

        // Product Summary Card
        $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">';
        $content .= '<div class="px-6 py-4 border-b border-gray-200 bg-gray-50"><h2 class="text-lg font-semibold text-gray-900"><i class="fas fa-info-circle mr-2 text-green-600"></i>Product Summary</h2></div>';
        $content .= '<div class="p-6 space-y-4">';

        $content .= '<div><div class="text-sm text-gray-500 mb-1">Product Name</div><div class="text-xl font-bold text-gray-900">' . htmlspecialchars($product['name'] ?? '') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">SKU</div><div class="text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">' . htmlspecialchars($product['sku'] ?? '') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Category</div><div class="text-gray-900"><span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">' . htmlspecialchars($product['category'] ?? 'Uncategorized') . '</span></div></div>';

        // Pricing
        $content .= '<div class="pt-4 border-t border-gray-200">';
        $content .= '<div class="text-sm text-gray-500 mb-2">Pricing</div>';
        $content .= '<div class="space-y-2">';
        $content .= '<div class="flex justify-between items-center"><span class="text-gray-600">Regular Price:</span><span class="text-2xl font-bold text-green-600">R ' . number_format((float)($product['price'] ?? 0), 2) . '</span></div>';
        if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
            $content .= '<div class="flex justify-between items-center"><span class="text-gray-600">Sale Price:</span><span class="text-xl font-bold text-red-600">R ' . number_format((float)$product['sale_price'], 2) . '</span></div>';
            $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
            $content .= '<div class="flex justify-between items-center"><span class="text-gray-600">Discount:</span><span class="text-sm font-bold text-red-600 bg-red-100 px-2 py-1 rounded">-' . $discount . '%</span></div>';
        }
        if (!empty($product['cost'])) {
            $content .= '<div class="flex justify-between items-center"><span class="text-gray-600">Cost:</span><span class="text-gray-900">R ' . number_format((float)$product['cost'], 2) . '</span></div>';
        }
        $content .= '</div></div>';

        // Inventory Status
        $content .= '<div class="pt-4 border-t border-gray-200">';
        $content .= '<div class="text-sm text-gray-500 mb-2">Inventory</div>';
        $stockLevel = (int)($product['stock'] ?? 0);
        $stockColor = $stockLevel > 10 ? 'text-green-600 bg-green-100' : ($stockLevel > 0 ? 'text-yellow-600 bg-yellow-100' : 'text-red-600 bg-red-100');
        $stockText = $stockLevel > 10 ? 'In Stock' : ($stockLevel > 0 ? 'Low Stock' : 'Out of Stock');
        $content .= '<div class="flex justify-between items-center"><span class="text-gray-600">Stock:</span><span class="font-bold">' . $stockLevel . ' units</span></div>';
        $content .= '<div class="flex justify-between items-center"><span class="text-gray-600">Status:</span><span class="px-3 py-1 rounded-full text-sm font-bold ' . $stockColor . '">' . $stockText . '</span></div>';
        $content .= '</div>';

        $content .= '</div></div>';

        // Product Details Tabs
        $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">';
        $content .= '<div class="border-b border-gray-200">';
        $content .= '<nav class="flex" id="productTabs">';
        $content .= '<button onclick="showTab(\'details\')" data-tab="details" class="px-6 py-4 font-medium text-green-600 border-b-2 border-green-600 whitespace-nowrap">Details</button>';
        $content .= '<button onclick="showTab(\'description\')" data-tab="description" class="px-6 py-4 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">Description</button>';
        $content .= '<button onclick="showTab(\'seo\')" data-tab="seo" class="px-6 py-4 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">SEO</button>';
        $content .= '<button onclick="showTab(\'shipping\')" data-tab="shipping" class="px-6 py-4 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">Shipping</button>';
        $content .= '<button onclick="showTab(\'meta\')" data-tab="meta" class="px-6 py-4 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">Metadata</button>';
        $content .= '</nav>';
        $content .= '</div>';

        // Tab Content
        $content .= '<div class="p-6">';

        // Details Tab
        $content .= '<div id="tab-details" class="tab-content">';
        $content .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
        $content .= '<div class="space-y-4">';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Product Name</div><div class="text-gray-900 font-medium">' . htmlspecialchars($product['name'] ?? '') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">SKU</div><div class="text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">' . htmlspecialchars($product['sku'] ?? '') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Category</div><div class="text-gray-900">' . htmlspecialchars($product['category'] ?? 'Uncategorized') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Status</div><div class="text-gray-900">' . (((int)($product['active'] ?? 0) === 1) ? '<span class="px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800">Active</span>' : '<span class="px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-800">Inactive</span>') . '</div></div>';
        $content .= '</div>';
        $content .= '<div class="space-y-4">';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Featured</div><div class="text-gray-900">' . (((int)($product['featured'] ?? 0) === 1) ? '<span class="px-3 py-1 rounded-full text-sm font-bold bg-yellow-100 text-yellow-800">Yes</span>' : 'No') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Tags</div><div class="text-gray-900">';
        if (!empty($product['tags'])) {
            $tagsArray = json_decode($product['tags'], true);
            if (is_array($tagsArray)) {
                foreach ($tagsArray as $tag) {
                    $content .= '<span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm mr-2 mb-2">' . htmlspecialchars($tag) . '</span>';
                }
            } else {
                $content .= htmlspecialchars($product['tags']);
            }
        } else {
            $content .= '<span class="text-gray-400">No tags</span>';
        }
        $content .= '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Total Images</div><div class="text-gray-900"><span class="font-bold text-green-600">' . count($images) . '</span> image(s)</div></div>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // Description Tab
        $content .= '<div id="tab-description" class="tab-content hidden">';
        $content .= '<div class="space-y-6">';
        if (!empty($product['short_description'])) {
            $content .= '<div><div class="text-sm font-semibold text-gray-900 mb-2">Short Description</div><div class="text-gray-700 bg-gray-50 p-4 rounded-lg">' . nl2br(htmlspecialchars($product['short_description'])) . '</div></div>';
        }
        if (!empty($product['description'])) {
            $content .= '<div><div class="text-sm font-semibold text-gray-900 mb-2">Full Description</div><div class="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap">' . nl2br(htmlspecialchars($product['description'])) . '</div></div>';
        }
        if (empty($product['short_description']) && empty($product['description'])) {
            $content .= '<div class="text-center py-8 text-gray-400"><i class="fas fa-file-alt text-4xl mb-2"></i><p>No description available</p></div>';
        }
        $content .= '</div>';
        $content .= '</div>';

        // SEO Tab
        $content .= '<div id="tab-seo" class="tab-content hidden">';
        $content .= '<div class="space-y-4">';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">URL Slug</div><div class="text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">/' . htmlspecialchars($product['slug'] ?? '') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Meta Title</div><div class="text-gray-900">' . htmlspecialchars($product['meta_title'] ?? '-') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Meta Description</div><div class="text-gray-900 bg-gray-50 p-3 rounded">' . htmlspecialchars($product['meta_description'] ?? '-') . '</div></div>';
        $content .= '</div>';
        $content .= '</div>';

        // Shipping Tab
        $content .= '<div id="tab-shipping" class="tab-content hidden">';
        $content .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
        $content .= '<div class="space-y-4">';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Weight</div><div class="text-gray-900">' . (!empty($product['weight']) ? htmlspecialchars($product['weight']) . ' g' : '-') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Dimensions</div><div class="text-gray-900">' . htmlspecialchars($product['dimensions'] ?? '-') . '</div></div>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // Metadata Tab
        $content .= '<div id="tab-meta" class="tab-content hidden">';
        $content .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
        $content .= '<div class="space-y-4">';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Created At</div><div class="text-gray-900"><i class="fas fa-calendar-alt mr-2 text-gray-400"></i>' . date('M j, Y g:i A', strtotime($product['created_at'] ?? 'now')) . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Updated At</div><div class="text-gray-900"><i class="fas fa-calendar-check mr-2 text-gray-400"></i>' . date('M j, Y g:i A', strtotime($product['updated_at'] ?? 'now')) . '</div></div>';
        $content .= '</div>';
        $content .= '<div class="space-y-4">';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Product ID</div><div class="text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">#' . htmlspecialchars($product['id'] ?? '') . '</div></div>';
        $content .= '<div><div class="text-sm text-gray-500 mb-1">Total Images</div><div class="text-gray-900"><i class="fas fa-images mr-2 text-gray-400"></i>' . count($images) . ' image(s)</div></div>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        $content .= '</div>'; // End tab content
        $content .= '</div>'; // End tabs container

        $content .= '</div>'; // End main grid

        // JavaScript for tabs and image switching
        $content .= '<script>';
        $content .= 'function showTab(tabName) {';
        $content .= '  document.querySelectorAll(".tab-content").forEach(el => el.classList.add("hidden"));';
        $content .= '  document.querySelectorAll("[data-tab]").forEach(el => {';
        $content .= '    el.classList.remove("text-green-600", "border-b-2", "border-green-600");';
        $content .= '    el.classList.add("text-gray-500");';
        $content .= '  });';
        $content .= '  document.getElementById("tab-" + tabName).classList.remove("hidden");';
        $content .= '  document.querySelector(\'[data-tab="\' + tabName + \'"]\').classList.remove("text-gray-500");';
        $content .= '  document.querySelector(\'[data-tab="\' + tabName + \'"]\').classList.add("text-green-600", "border-b-2", "border-green-600");';
        $content .= '}';
        $content .= 'function changeMainImage(src, button) {';
        $content .= '  document.getElementById("mainProductImage").src = src;';
        $content .= '  document.querySelectorAll("#' . 'mainImageContainer + div button").forEach(btn => {';
        $content .= '    btn.classList.remove("ring-2", "ring-green-500");';
        $content .= '    btn.classList.add("ring-1", "ring-gray-200");';
        $content .= '  });';
        $content .= '  button.classList.remove("ring-1", "ring-gray-200");';
        $content .= '  button.classList.add("ring-2", "ring-green-500");';
        $content .= '}';
        $content .= '</script>';
    }
    echo renderAdminPage('View Product - CannaBuddy Admin', $content, 'products');
    exit;
}

if (false && strpos($route, 'admin/products/edit/') === 0) {
    $slug = substr($route, strlen('admin/products/edit/'));
    $message = '';
    $error = '';
    $product = null;
    $categories = [];
    if ($db) {
        // Fetch categories
        try {
            $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
        }
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
                $name = trim($_POST['name'] ?? '');
                $sku = trim($_POST['sku'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $sale_price = isset($_POST['sale_price']) && $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
                $cost = isset($_POST['cost']) && $_POST['cost'] !== '' ? (float)$_POST['cost'] : null;
                $stock = (int)($_POST['stock'] ?? 0);
                $short_description = $_POST['short_description'] ?? '';
                $description = $_POST['description'] ?? '';
                $category = $_POST['category'] ?? '';
                $tags_raw = $_POST['tags'] ?? '';
                // Convert empty tags to empty JSON array, or validate existing JSON
                if (empty($tags_raw)) {
                    $tags = '[]';
                } else {
                    // If it's comma-separated, convert to JSON array
                    if (strpos($tags_raw, ',') !== false) {
                        $tags_array = array_map('trim', explode(',', $tags_raw));
                        $tags = json_encode($tags_array);
                    } else {
                        // Single tag, wrap in array
                        $tags = json_encode([$tags_raw]);
                    }
                }
                $images = $_POST['images'] ?? '';
                $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
                $dimensions = $_POST['dimensions'] ?? null;
                $active = isset($_POST['active']) ? 1 : 0;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $meta_title = $_POST['meta_title'] ?? $name;
                $meta_description = $_POST['meta_description'] ?? $short_description;

                // Handle custom fields - store as JSON
                $custom_fields = $_POST['custom_fields'] ?? '{}';
                // Validate and sanitize JSON
                if (empty($custom_fields) || $custom_fields === '[]' || $custom_fields === '{}') {
                    $custom_fields = '{}';
                }

                // Handle product policies - multi-line text
                $product_policies = $_POST['product_policies'] ?? '';

                if ($name === '' || $sku === '' || $price < 0 || $stock < 0) { throw new Exception('Invalid data'); }
                $stmt = $db->prepare('UPDATE products SET name=?, sku=?, price=?, sale_price=?, cost=?, stock=?, short_description=?, description=?, category=?, tags=?, images=?, weight=?, dimensions=?, active=?, featured=?, meta_title=?, meta_description=?, custom_fields=?, product_policies=?, updated_at=NOW() WHERE slug=?');
                $stmt->execute([$name,$sku,$price,$sale_price,$cost,$stock,$short_description,$description,$category,$tags,$images,$weight,$dimensions,$active,$featured,$meta_title,$meta_description,$custom_fields,$product_policies,$slug]);
                $stmt = $db->prepare('SELECT id FROM products WHERE slug=? LIMIT 1');
                $stmt->execute([$slug]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $pid = $row['id'] ?? null;
                if ($pid) {
                    $db->exec('CREATE TABLE IF NOT EXISTS product_versions (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, slug VARCHAR(255), data_json TEXT, changed_by VARCHAR(255), created_at DATETIME)');
                    $snapshot = json_encode($_POST);
                    $changedBy = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'admin';
                    $stmt = $db->prepare('INSERT INTO product_versions (product_id, slug, data_json, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $stmt->execute([$pid,$slug,$snapshot,$changedBy]);
                }
                $message = 'Product updated successfully';
            }
            $stmt = $db->prepare('SELECT * FROM products WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) { $error = 'Product not found'; }
            elseif ($error && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $product = array_merge($product, $_POST);
                $product['active'] = isset($_POST['active']) ? 1 : 0;
                $product['featured'] = isset($_POST['featured']) ? 1 : 0;
            }
        } catch (Exception $e) { $error = $e->getMessage(); }
    } else { $error = 'Database not available'; }

    $imagesValue = '';
    if ($product) {
        $imagesValue = $product['images'] ?? '';
        if ($imagesValue === '' || $imagesValue === null) {
            $fallbackImages = [];
            for ($i = 1; $i <= 5; $i++) {
                $key = 'image_' . $i;
                if (!empty($product[$key])) {
                    $imagePath = $product[$key];
                    if (strpos($imagePath, 'assets/') === 0) {
                        $fallbackImages[] = assetUrl(substr($imagePath, 7));
                    } else {
                        $fallbackImages[] = $imagePath;
                    }
                }
            }
            if (!empty($fallbackImages)) {
                $imagesValue = implode(', ', $fallbackImages);
            }
        }
    }

    $content = '<div class="w-full max-w-7xl mx-auto">';
    $content .= '<div class="flex justify-between items-center mb-6"><h1 class="text-3xl font-bold text-gray-900">Edit Product</h1><a href="' . adminUrl('/products/view/' . htmlspecialchars($slug)) . '" class="px-4 py-2 bg-gray-100 rounded-md">View</a></div>';
    if ($message) { $content .= '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 text-green-800">' . htmlspecialchars($message) . '</div>'; }
    if ($error) { $content .= '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 text-red-800">' . htmlspecialchars($error) . '</div>'; }
    if ($product) {
        $content .= '<form method="POST" id="editForm" class="space-y-8">';
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Basic Information</h2></div><div class="px-6 py-6 space-y-6">';
        $content .= adminFormInput('Product Name *','name', $product['name'] ?? '', 'text', true, 'Enter product name');
        $content .= adminFormTextarea('Short Description','short_description',$product['short_description'] ?? '',2,false,'Brief product description');
        $content .= adminFormTextarea('Full Description','description',$product['description'] ?? '',6,false,'Detailed product description');
        $content .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">' . adminFormInput('SKU *','sku',$product['sku'] ?? '', 'text', true, 'Product SKU');
        $content .= '<div><label class="block text-sm font-medium text-gray-700 mb-2">Category</label><select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"><option value="">Select a category</option>';
        foreach ($categories as $cat) {
            $selected = ($product['category'] ?? '') === $cat['name'] ? ' selected' : '';
            $content .= '<option value="' . htmlspecialchars($cat['name']) . '"' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
        }
        $content .= '</select><p class="text-xs text-gray-500 mt-1">Choose from existing categories</p></div></div>';
        $content .= adminFormInput('Tags','tags',$product['tags'] ?? '', 'text', false, 'Comma-separated tags');
        $content .= '</div></div>';
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Pricing</h2></div><div class="px-6 py-6 space-y-6"><div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
        $content .= adminFormInput('Selling Price (R) *','price',$product['price'] ?? '', 'number', true, '0.00', ['step'=>'0.01','min'=>'0']);
        $content .= adminFormInput('Sale Price (R)','sale_price',$product['sale_price'] ?? '', 'number', false, '0.00', ['step'=>'0.01','min'=>'0']);
        $content .= adminFormInput('Cost Price (R)','cost',$product['cost'] ?? '', 'number', false, '0.00', ['step'=>'0.01','min'=>'0']);
        $content .= '</div></div></div>';
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Inventory</h2></div><div class="px-6 py-6 space-y-6"><div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
        $content .= adminFormInput('Stock Quantity *','stock',$product['stock'] ?? '0', 'number', true, '0', ['min'=>'0']);
        $content .= adminFormInput('Weight (g)','weight',$product['weight'] ?? '', 'number', false, '0.00', ['step'=>'0.01','min'=>'0']);
        $content .= adminFormInput('Dimensions (L×W×H cm)','dimensions',$product['dimensions'] ?? '', 'text', false, 'e.g., 10×5×2');
        $content .= '</div></div></div>';
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Media</h2></div><div class="px-6 py-6 space-y-6">';
        $content .= '<input type="hidden" name="images" id="field_images" value="' . htmlspecialchars($imagesValue) . '">';
        $content .= '<div id="imagePreviewContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4"></div>';
        $content .= '<div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors" id="dropZone"><i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i><p class="text-sm text-gray-600 mb-2">Upload product images (JPG, PNG, WebP)</p><input type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" id="imageUpload"><button type="button" onclick="document.getElementById(\'imageUpload\').click()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Choose Files</button><div id="uploadProgress" class="hidden mt-4"><div class="w-full bg-gray-200 rounded-full h-2.5"><div class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div></div><p class="text-xs text-gray-500 mt-1">Uploading...</p></div></div>';
        $content .= '</div></div>';
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Product Settings</h2></div><div class="px-6 py-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-4"><label class="flex items-center"><input type="checkbox" name="active" value="1" ' . (((int)($product['active'] ?? 0)===1)?'checked':'') . ' class="rounded border-gray-300 text-green-600"><span class="ml-2 text-sm text-gray-700">Active</span></label><label class="flex items-center"><input type="checkbox" name="featured" value="1" ' . (((int)($product['featured'] ?? 0)===1)?'checked':'') . ' class="rounded border-gray-300 text-green-600"><span class="ml-2 text-sm text-gray-700">Featured Product</span></label></div></div></div></div>';
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">SEO Settings</h2></div><div class="px-6 py-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
        $content .= adminFormInput('Meta Title','meta_title',$product['meta_title'] ?? '', 'text', false, 'SEO title');
        $content .= adminFormInput('Meta Description','meta_description',$product['meta_description'] ?? '', 'text', false, 'SEO description');
        $content .= '</div></div></div>';

        // Product Information (Custom Fields)
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Product Information</h2><p class="text-sm text-gray-600">Add custom fields for detailed product specifications</p></div><div class="px-6 py-6 space-y-6">';
        $content .= '<input type="hidden" name="custom_fields" id="field_custom_fields" value="' . htmlspecialchars($product['custom_fields'] ?? '') . '">';
        $content .= '<div id="customFieldsContainer" class="space-y-4"></div>';
        $content .= '<button type="button" onclick="addCustomField()" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"><i class="fas fa-plus mr-2"></i>Add Custom Field</button>';
        $content .= '</div></div>';

        // Product Policies / Warranty Info
        $content .= '<div class="bg-white shadow rounded-lg overflow-hidden"><div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-medium text-gray-900">Product Policies & Warranty</h2><p class="text-sm text-gray-600">Add delivery, warranty, and return policy information (shown under stock on product page)</p></div><div class="px-6 py-6 space-y-6">';
        $content .= '<div class="space-y-2">';
        $content .= '<label class="block text-sm font-medium text-gray-700">Policies & Warranty Text</label>';
        $content .= '<textarea name="product_policies" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter policy statements, one per line (e.g., Eligible for next-day delivery, 6-Month Limited Warranty, etc.)">' . htmlspecialchars($product['product_policies'] ?? '') . '</textarea>';
        $content .= '<p class="text-xs text-gray-500">Write one policy per line. These will appear as bullet points under stock on the product page.</p>';
        $content .= '</div>';
        $content .= '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><h4 class="text-sm font-medium text-blue-900 mb-2">Example Policy Lines:</h4><ul class="text-sm text-blue-800 space-y-1 list-disc list-inside"><li>Eligible for next-day delivery or collection</li><li>Eligible for Cash on Delivery</li><li>Hassle-Free Exchanges & Returns for 30 Days</li><li>6-Month Limited Warranty</li></ul></div>';
        $content .= '</div></div>';

        $content .= '<div class="flex justify-end space-x-3"><a href="' . adminUrl('/products/') . '" class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg">Cancel</a><button type="submit" name="update" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium">Save Changes</button></div>';
        $content .= '</form>';
        ?>
        <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                var f = document.getElementById('editForm');
                if (f) {
                    var fd = new FormData(f);
                    fetch('<?php echo adminUrl('/products/autosave/' . htmlspecialchars($slug)); ?>', { method: 'POST', body: fd });
                    var btn = document.querySelector('button[name="update"]');
                    if (btn) { btn.click(); }
                }
            }
        });

        var pto = function() {
            var fd = new FormData(document.getElementById('editForm'));
            fetch('<?php echo adminUrl('/products/autosave/' . htmlspecialchars($slug)); ?>', { method: 'POST', body: fd });
        };

        var inputs = document.querySelectorAll('#editForm input, #editForm textarea');
        inputs.forEach(function(i) { i.addEventListener('change', pto); });
        setInterval(pto, 15000);

        // Image Upload and Preview Handler
        const imageUpload = document.getElementById('imageUpload');
        const dropZone = document.getElementById('dropZone');
        const imagesField = document.getElementById('field_images');
        const previewContainer = document.getElementById('imagePreviewContainer');

        function renderPreviews() {
            if (!imagesField || !previewContainer) return;
            const currentUrls = imagesField.value ? imagesField.value.split(',').map(s => s.trim()).filter(s => s) : [];
            previewContainer.innerHTML = '';
            currentUrls.forEach((url, index) => {
                const div = document.createElement('div');
                div.className = 'relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200';
                const img = document.createElement('img');
                img.src = url;
                img.className = 'w-full h-full object-cover';
                // Main Image Label
                if (index === 0) {
                    const badge = document.createElement('div');
                    badge.className = 'absolute top-2 left-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded shadow';
                    badge.innerText = 'Main Image';
                    div.appendChild(badge);
                }
                // Remove Button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full shadow opacity-0 group-hover:opacity-100 transition-opacity';
                removeBtn.innerHTML = '<i class=\'fas fa-times\'></i>';
                removeBtn.onclick = () => removeImage(index);
                div.appendChild(img);
                div.appendChild(removeBtn);
                previewContainer.appendChild(div);
            });
        }

        function removeImage(index) {
            const currentUrls = imagesField.value ? imagesField.value.split(',').map(s => s.trim()).filter(s => s) : [];
            currentUrls.splice(index, 1);
            imagesField.value = currentUrls.join(', ');
            renderPreviews();
        }

        if(imageUpload && dropZone){
            imageUpload.addEventListener('change', handleFiles);
            dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-green-500'); });
            dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('border-green-500'); });
            dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('border-green-500'); if (e.dataTransfer.files.length) { handleFiles({ target: { files: e.dataTransfer.files } }); } });
            function handleFiles(e) {
                const files = e.target.files;
                if (!files.length) return;
                const progressBar = document.getElementById('uploadProgress');
                const progressFill = progressBar.querySelector('div');
                progressBar.classList.remove('hidden');
                
                const formData = new FormData();
                for (let i = 0; i < files.length; i++) { 
                    formData.append('images[]', files[i]); 
                }
                
                fetch('<?php echo adminUrl('/products/upload_image.php'); ?>', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const currentUrls = imagesField.value ? imagesField.value.split(',').map(s => s.trim()).filter(s => s) : [];
                        data.urls.forEach(url => { currentUrls.push(url); });
                        imagesField.value = currentUrls.join(', ');
                        renderPreviews();
                        progressFill.style.width = '100%';
                        setTimeout(() => progressBar.classList.add('hidden'), 1000);
                    } else { alert('Upload failed: ' + (data.message || 'Unknown error')); progressBar.classList.add('hidden'); }
                })
                .catch(error => { console.error('Error:', error); alert('Upload failed.'); progressBar.classList.add('hidden'); });
            }
        }
        // Initial Render
        renderPreviews();

        // Custom Fields Management
        let customFields = [];

        function loadCustomFields() {
            const customFieldsInput = document.getElementById('field_custom_fields');
            const container = document.getElementById('customFieldsContainer');

            if (customFieldsInput && customFieldsInput.value) {
                try {
                    const parsed = JSON.parse(customFieldsInput.value);
                    // Ensure it's an array
                    customFields = Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    customFields = [];
                }
            } else {
                customFields = [];
            }

            if (container) {
                container.innerHTML = '';
                // Ensure customFields is an array before calling forEach
                if (Array.isArray(customFields)) {
                    customFields.forEach((field, index) => {
                        addFieldToUI(field.label, field.value, index);
                    });
                }
            }
        }

        function addCustomField(label = '', value = '') {
            const container = document.getElementById('customFieldsContainer');
            
            // Ensure customFields is always an array
            if (!Array.isArray(customFields)) {
                customFields = [];
            }
            
            const index = customFields.length;

            customFields.push({ label, value });
            addFieldToUI(label, value, index);
            updateCustomFieldsInput();
        }

        function addFieldToUI(label, value, index) {
            const container = document.getElementById('customFieldsContainer');
            if (!container) return;

            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4';
            fieldDiv.dataset.index = index;

            fieldDiv.innerHTML =
                '<div class="flex items-start space-x-3">' +
                    '<div class="flex-1">' +
                        '<label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>' +
                        '<input type="text" value="' + label + '" onchange="updateCustomField(' + index + ', \'label\', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Warranty">' +
                    '</div>' +
                    '<div class="flex-1">' +
                        '<label class="block text-sm font-medium text-gray-700 mb-1">Value</label>' +
                        '<input type="text" value="' + value + '" onchange="updateCustomField(' + index + ', \'value\', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Limited (6 months)">' +
                    '</div>' +
                    '<button type="button" onclick="removeCustomField(' + index + ')" class="mt-6 p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</div>';

            container.appendChild(fieldDiv);
        }

        function updateCustomField(index, key, value) {
            if (customFields[index]) {
                customFields[index][key] = value;
                updateCustomFieldsInput();
            }
        }

        function removeCustomField(index) {
            const container = document.getElementById('customFieldsContainer');
            const fieldDiv = container.querySelector('[data-index="' + index + '"]');

            if (fieldDiv) {
                customFields.splice(index, 1);
                fieldDiv.remove();

                // Re-index remaining fields
                const fieldDivs = container.querySelectorAll('[data-index]');
                fieldDivs.forEach((div, newIndex) => {
                    div.dataset.index = newIndex;
                    const inputs = div.querySelectorAll('input');
                    inputs.forEach(input => {
                        if (input.onchange) {
                            const funcStr = input.onchange.toString();
                            const newFunc = funcStr.replace(/updateCustomField\(\d+/, 'updateCustomField(' + newIndex);
                            input.setAttribute('onchange', newFunc);
                        }
                    });
                    const buttons = div.querySelectorAll('button[onclick*="removeCustomField"]');
                    buttons.forEach(button => {
                        const funcStr = button.getAttribute('onclick');
                        const newFunc = funcStr.replace(/removeCustomField\(\d+/, 'removeCustomField(' + newIndex);
                        button.setAttribute('onclick', newFunc);
                    });
                });

                updateCustomFieldsInput();
            }
        }

        function updateCustomFieldsInput() {
            const hiddenInput = document.getElementById('field_custom_fields');
            if (hiddenInput) {
                hiddenInput.value = JSON.stringify(customFields);
            }
        }

        // Load existing custom fields when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCustomFields();
        });
        </script>
        <?php
    }
    echo renderAdminPage('Edit Product - CannaBuddy Admin', $content, 'products');
    exit;
}

if (strpos($route, 'admin/products/autosave/') === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = substr($route, strlen('admin/products/autosave/'));
    if ($db) {
        try {
            $stmt = $db->prepare('SELECT id FROM products WHERE slug=? LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $pid = $row['id'] ?? null;
            if ($pid) {
                $db->exec('CREATE TABLE IF NOT EXISTS product_drafts (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, slug VARCHAR(255), data_json TEXT, updated_by VARCHAR(255), updated_at DATETIME)');
                $payload = json_encode($_POST);
                $by = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'admin';
                $stmt = $db->prepare('INSERT INTO product_drafts (product_id, slug, data_json, updated_by, updated_at) VALUES (?, ?, ?, ?, NOW())');
                $stmt->execute([$pid,$slug,$payload,$by]);
            }
        } catch (Exception $e) {}
    }
    echo '';
    exit;
}

if ($route === 'admin/products/bulk-update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db) {
        try {
            $action = $_POST['action'] ?? '';
            $slugs = $_POST['selected'] ?? [];
            if (!is_array($slugs)) { $slugs = []; }
            if (!empty($slugs)) {
                if ($action === 'set_active' || $action === 'set_inactive') {
                    $val = $action === 'set_active' ? 1 : 0;
                    $in = implode(',', array_fill(0, count($slugs), '?'));
                    $stmt = $db->prepare("UPDATE products SET active=? WHERE slug IN ($in)");
                    $params = array_merge([$val], $slugs);
                    $stmt->execute($params);
                } elseif ($action === 'increase_price_percent' || $action === 'decrease_price_percent') {
                    $pct = (float)($_POST['percent'] ?? 0);
                    foreach ($slugs as $s) {
                        $stmt = $db->prepare('UPDATE products SET price = CASE WHEN ? >= 0 THEN price * (1 + (?/100)) ELSE price END WHERE slug = ?');
                        $adj = $action === 'increase_price_percent' ? $pct : -$pct;
                        $stmt->execute([$adj,$adj,$s]);
                    }
                } elseif ($action === 'set_category') {
                    $cat = trim($_POST['category'] ?? '');
                    $in = implode(',', array_fill(0, count($slugs), '?'));
                    $stmt = $db->prepare("UPDATE products SET category=? WHERE slug IN ($in)");
                    $params = array_merge([$cat], $slugs);
                    $stmt->execute($params);
                }
            }
            $_SESSION['success'] = 'Bulk update completed';
        } catch (Exception $e) { $_SESSION['error'] = 'Bulk update failed'; }
    }
    header('Location: ' . adminUrl('/products'));
    exit;
}

if (strpos($route, 'admin/products/delete/') === 0) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Extract product slug from URL
    $slug = substr($route, strlen('admin/products/delete/'));
    
    if (!empty($slug) && isset($db)) {
        try {
            // First, check if product exists
            $stmt = $db->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Delete the product
                $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
                $result = $stmt->execute([$product['id']]);
                
                if ($result) {
                    $_SESSION['success'] = 'Product deleted successfully';
                } else {
                    $_SESSION['error'] = 'Failed to delete product';
                }
            } else {
                $_SESSION['error'] = 'Product not found';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error deleting product: ' . $e->getMessage();
            error_log("Product delete error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error'] = 'Invalid product identifier or database unavailable';
    }
    
    header('Location: ' . adminUrl('/products'));
    exit;
}
