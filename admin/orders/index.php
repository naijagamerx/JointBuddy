<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get all orders with product images
$orders = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT
                o.id,
                o.order_number,
                o.customer_email,
                o.customer_name,
                o.customer_phone,
                o.total_amount,
                o.status,
                o.payment_status,
                o.created_at,
                GROUP_CONCAT(DISTINCT SUBSTRING_INDEX(p.images, ',', 1) ORDER BY oi.id SEPARATOR '|||') as product_images,
                COUNT(DISTINCT oi.id) as product_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add sequential numbering in PHP
        $counter = 1;
        foreach ($orders as &$order) {
            $order['sequential_number'] = $counter++;
        }
        unset($order);
    } catch (Exception $e) {
        error_log("Error getting orders: " . $e->getMessage());
    }
}

// Helper function to render product images
function renderProductImages($imagesString, $productCount) {
    if (empty($imagesString) || $productCount == 0) {
        return '<div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
            <i class="fas fa-box text-xs"></i>
        </div>';
    }
    
    $images = explode('|||', $imagesString);
    $maxShow = 4;
    $showCount = min(count($images), $maxShow);
    
    $html = '<div class="flex -space-x-2">';
    
    for ($i = 0; $i < $showCount; $i++) {
        $imagePath = trim($images[$i]);
        if (!empty($imagePath)) {
            // Remove hardcoded paths and convert to proper URL
            $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $imagePath);
            $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
            $dbPath = ltrim($dbPath, '/');
            $imageUrl = url($dbPath);

            $html .= '<div class="w-8 h-8 rounded-lg border-2 border-white shadow-sm overflow-hidden flex-shrink-0 bg-gray-100">
                <img src="' . htmlspecialchars($imageUrl) . '" alt="Product" class="w-full h-full object-cover">
            </div>';
        }
    }
    
    if ($productCount > $maxShow) {
        $html .= '<div class="w-8 h-8 rounded-full bg-gray-800 text-white flex items-center justify-center text-xs font-medium flex-shrink-0">
            +' . ($productCount - $maxShow) . '
        </div>';
    }
    
    $html .= '</div>';
    return $html;
}

// Generate orders content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
        <p class="text-gray-600 mt-1">Manage customer orders (' . count($orders) . ' orders)</p>
    </div>

    <!-- Orders Table -->';

if (empty($orders)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No orders yet</h3>
        <p class="text-gray-600">Orders will appear here when customers make purchases</p>
    </div>';
} else {
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($orders as $order) {
        $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
        $productImages = renderProductImages($order['product_images'] ?? '', $order['product_count'] ?? 0);
        
        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">#' . str_pad($order['sequential_number'], 4, '0', STR_PAD_LEFT) . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</div>
                            <div class="text-sm text-gray-500">' . htmlspecialchars($order['customer_email'] ?? '') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $productImages . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">R' . number_format($order['total_amount'] ?? 0, 2) . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusColor . '">
                                ' . ucfirst(htmlspecialchars($order['status'] ?? 'unknown')) . '
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ' . date('M j, Y g:i A', strtotime($order['created_at'] ?? 'now')) . '
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
        </div>
    </div>';
}

$content .= '
</div>';

// Render the page using adminSidebarWrapper
echo adminSidebarWrapper('Orders', $content, 'orders');
