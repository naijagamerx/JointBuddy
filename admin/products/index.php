<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get all products
$products = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting products: " . $e->getMessage());
    }
}

// Display success/error messages
$messageHtml = '';
if (isset($_SESSION['success'])) {
    $messageHtml = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . htmlspecialchars($_SESSION['success']) . '</p>
            </div>
        </div>
    </div>';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $messageHtml = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . htmlspecialchars($_SESSION['error']) . '</p>
            </div>
        </div>
    </div>';
    unset($_SESSION['error']);
}

// Generate products content
$addProductUrl = adminUrl('/products/add.php');
$content = '
<div class="w-full max-w-7xl mx-auto">
    ' . $messageHtml . '
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Products</h1>
            <p class="text-gray-600 mt-1">Manage your product catalog (' . count($products) . ' products)</p>
        </div>
        <a href="' . $addProductUrl . '" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add New Product
        </a>
    </div>

    <!-- Products Table -->';

if (empty($products)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No products yet</h3>
        <p class="text-gray-600 mb-6">Get started by adding your first product</p>
        <a href="' . $addProductUrl . '" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i>Add Product
        </a>
    </div>';
} else {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
    
    $counter = 1;
    foreach ($products as $product) {
        $activeStatus = ($product['active'] ?? 0) == 1
            ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>'
            : '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Inactive</span>';

        $stockClass = ($product['stock'] ?? 0) < 10 ? 'text-yellow-600' : 'text-gray-900';

        $slugText = $product['slug'] ?? '';
        if (strlen($slugText) > 35) {
            $slugText = substr($slugText, 0, 35) . '...';
        }

        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">
                            ' . $counter . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-4 overflow-hidden">';

                                // Get product image - check 'images' field first, then fallback to 'image_1'
                                $productImage = '';
                                if (!empty($product['images'])) {
                                    // New format: comma-separated URLs
                                    $imageUrls = explode(',', $product['images']);
                                    $dbPath = trim($imageUrls[0]);
                                    // Remove hardcoded paths and convert to proper URL
                                    $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                                    $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                                    $dbPath = ltrim($dbPath, '/');
                                    $productImage = url($dbPath);
                                } elseif (!empty($product['image_1'])) {
                                    // Legacy format: image_1 field
                                    $dbPath = $product['image_1'];
                                    // Remove hardcoded paths and convert to proper URL
                                    $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                                    $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                                    $dbPath = ltrim($dbPath, '/');
                                    $productImage = url($dbPath);
                                }

                                if (!empty($productImage)) {
                                    $content .= '<img src="' . htmlspecialchars($productImage) . '" alt="' . htmlspecialchars($product['name'] ?? '') . '" class="w-full h-full object-cover">';
                                } else {
                                    $content .= '<i class="fas fa-box text-gray-500 text-xl"></i>';
                                }

                                $content .= '
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' . htmlspecialchars($product['name'] ?? 'N/A') . '">
                                        ' . htmlspecialchars($product['name'] ?? 'N/A') . '
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ' . htmlspecialchars($slugText) . '
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                R' . number_format($product['price'] ?? 0, 2) . '
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm ' . $stockClass . ' font-medium">
                                ' . ($product['stock'] ?? 0) . ' units
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $activeStatus . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ' . date('M j, Y', strtotime($product['created_at'] ?? 'now')) . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="' . adminUrl('/products/view/' . urlencode($product['slug'] ?? '')) . '" 
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 mr-2" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="' . adminUrl('/products/edit/' . urlencode($product['slug'] ?? '')) . '" 
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600 hover:bg-green-200 mr-2" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="' . adminUrl('/products/delete/' . urlencode($product['slug'] ?? '')) . '" 
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200"
                               onclick="return confirm(\'Are you sure?\')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>';
        $counter++;
    }
    
    $content .= '
                </tbody>
            </table>
        </div>
    </div>';
}

$content .= '
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('All Products', $content, 'products');
