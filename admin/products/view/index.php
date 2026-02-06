<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and admin auth
require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/url_helper.php';
require_once __DIR__ . '/../../../admin_sidebar_components.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}

// Check if admin is logged in
if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

// Get product slug from URL
$productSlug = basename(dirname(__DIR__)) === 'view' ? basename(__DIR__) : (isset($_GET['slug']) ? $_GET['slug'] : '');

if (empty($productSlug)) {
    $_SESSION['error'] = 'Product not found';
    redirect('/admin/products/');
}

// Get product details
$product = null;
$category = null;
$variations = [];
$inquiries = [];
if ($db) {
    try {
        // Get product
        $stmt = $db->prepare("SELECT * FROM products WHERE slug = ?");
        $stmt->execute([$productSlug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['error'] = 'Product not found';
            redirect('/admin/products/');
        }

        // Get category - handle both category (text) and category_id (foreign key)
        if (!empty($product['category'])) {
            // New format: category is text
            $category = ['name' => $product['category']];
        } elseif (!empty($product['category_id'])) {
            // Legacy format: category_id references categories table
            $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$product['category_id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Get variations
        $stmt = $db->prepare("SELECT * FROM product_variations WHERE product_id = ? ORDER BY created_at DESC");
        $stmt->execute([$product['id']]);
        $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent inquiries
        $stmt = $db->prepare("SELECT * FROM product_inquiries WHERE product_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$product['id']]);
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Error getting product: " . $e->getMessage());
        $_SESSION['error'] = 'Error loading product';
        redirect('/admin/products/');
    }
}

// Parse images
$images = [];
if (!empty($product['images'])) {
    $images = explode(',', $product['images']);
    $images = array_map('trim', $images);
    $images = array_filter($images);
} elseif (!empty($product['image_1'])) {
    $images[] = $product['image_1'];
}

// Get stock status
$stock = $product['stock'] ?? 0;
if ($stock == 0) {
    $stockStatus = ['label' => 'Out of Stock', 'class' => 'bg-red-100 text-red-800', 'icon' => 'fa-times-circle'];
} elseif ($stock < 10) {
    $stockStatus = ['label' => 'Low Stock', 'class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'fa-exclamation-triangle'];
} else {
    $stockStatus = ['label' => 'In Stock', 'class' => 'bg-green-100 text-green-800', 'icon' => 'fa-check-circle'];
}

// Parse tags
$tagsDisplay = '';
if (!empty($product['tags'])) {
    try {
        $tagsArray = json_decode($product['tags'], true);
        if (is_array($tagsArray)) {
            $tagsDisplay = implode(', ', $tagsArray);
        }
    } catch (Exception $e) {
        $tagsDisplay = $product['tags'];
    }
}

// Parse custom fields
$customFields = [];
if (!empty($product['custom_fields'])) {
    try {
        $customFields = json_decode($product['custom_fields'], true);
        if (!is_array($customFields)) {
            $customFields = [];
        }
    } catch (Exception $e) {
        $customFields = [];
    }
}

// Generate view content
$content = '
<div class="w-full max-w-full mx-auto" style="width: 100% !important; max-width: 100% !important;">
    <div class="mb-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">' . htmlspecialchars($product['name'] ?? 'Product Details') . '</h1>
                <p class="text-gray-600 text-sm mt-1">Comprehensive product overview and management</p>
            </div>
            <div class="flex space-x-3 flex-shrink-0">
                <a href="' . adminUrl('/products/edit/' . urlencode($product['slug'])) . '" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors whitespace-nowrap">
                    <i class="fas fa-edit mr-2"></i>Edit Product
                </a>
                <a href="' . adminUrl('/products/') . '" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors whitespace-nowrap">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Products
                </a>
            </div>
        </div>
    </div>

    <!-- Image Banner -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Product Images (' . count($images) . ')</h2>
                <p class="text-gray-600 mt-1">Click any image to view full size</p>
            </div>
            <div class="p-4 sm:p-6 lg:p-8">';

if (!empty($images)) {
    $content .= '<div class="w-full" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; width: 100%;">';
    foreach ($images as $index => $image) {
        $content .= '
                <div style="aspect-ratio: 1; width: 100%;">
                    <img src="' . htmlspecialchars($image) . '" alt="Product image ' . ($index + 1) . '" class="w-full h-full object-cover rounded-xl border border-gray-200 hover:opacity-90 transition-opacity cursor-pointer shadow-md hover:shadow-lg" onclick="openImageModal(\'' . htmlspecialchars($image) . '\')">
                </div>';
    }
    $content .= '</div>';
} else {
    $content .= '<div class="text-center py-16 text-gray-500 bg-gray-50 rounded-xl">
                    <i class="fas fa-image text-6xl mb-4"></i>
                    <p class="text-lg">No images uploaded</p>
                </div>';
}

$content .= '
            </div>
        </div>
    </div>

    <!-- Overview Card -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-6 sm:p-8 mb-8 w-full" style="width: 100% !important;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; width: 100%;">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900">R' . number_format($product['price'] ?? 0, 2) . '</div>
                <div class="text-sm text-gray-600 mt-1">Regular Price</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold ' . ($stock < 10 ? 'text-yellow-600' : 'text-gray-900') . '">' . $stock . '</div>
                <div class="text-sm text-gray-600 mt-1">Stock Units</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900">' . htmlspecialchars($product['sku'] ?? 'N/A') . '</div>
                <div class="text-sm text-gray-600 mt-1">SKU</div>
            </div>
            <div class="text-center">
                <div class="flex items-center justify-center">
                    <span class="px-3 py-1 text-sm font-medium ' . $stockStatus['class'] . ' rounded-full">
                        <i class="fas ' . $stockStatus['icon'] . ' mr-1"></i>' . $stockStatus['label'] . '
                    </span>
                </div>
                <div class="text-sm text-gray-600 mt-1">Stock Status</div>
            </div>
        </div>
    </div>

    <!-- Product Details - Organized Cards -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Product Information</h2>
            </div>
            <div class="p-6 sm:p-8">
                <div class="w-full space-y-8">';

if (!empty($product['short_description'])) {
    $content .= '
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-align-left text-blue-500 text-xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800 uppercase tracking-wide">Short Description</h3>
                    </div>
                    <p class="text-base text-gray-700 leading-relaxed">' . nl2br(htmlspecialchars($product['short_description'])) . '</p>
                </div>';
}

$content .= '
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Basic Information Card -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-gray-800 font-semibold flex items-center">
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>Basic Information
                                </h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-start">
                                    <i class="fas fa-hashtag text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Product ID</p>
                                        <p class="text-sm font-mono font-semibold text-gray-900">#' . ($product['id'] ?? 'N/A') . '</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-box text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Product Name</p>
                                        <p class="text-sm font-semibold text-gray-900">' . htmlspecialchars($product['name'] ?? 'N/A') . '</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-barcode text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">SKU</p>
                                        <p class="text-sm font-mono font-semibold text-gray-900">' . htmlspecialchars($product['sku'] ?? 'N/A') . '</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-link text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Slug</p>
                                        <p class="text-sm font-mono font-semibold text-gray-900">' . htmlspecialchars($product['slug'] ?? 'N/A') . '</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Classification Card -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-gray-800 font-semibold flex items-center">
                                    <i class="fas fa-tags mr-2 text-blue-500"></i>Classification
                                </h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-start">
                                    <i class="fas fa-folder text-purple-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Category</p>
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            <i class="fas fa-tag mr-2"></i>' . htmlspecialchars($category['name'] ?? 'Uncategorized') . '
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-bookmark text-purple-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Tags</p>
                                        ' . (!empty($tagsDisplay) ? '<div class="flex flex-wrap gap-2 mt-1">' . implode('', array_map(function($tag) {
                                            return '<span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">' . htmlspecialchars(trim($tag)) . '</span>';
                                        }, explode(', ', $tagsDisplay))) . '</div>' : '<span class="text-gray-400 text-sm">No tags</span>') . '
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Card -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-gray-800 font-semibold flex items-center">
                                    <i class="fas fa-dollar-sign mr-2 text-blue-500"></i>Pricing Information
                                </h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-start">
                                    <i class="fas fa-money-bill text-green-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Regular Price</p>
                                        <p class="text-3xl font-bold text-gray-900">R' . number_format($product['price'] ?? 0, 2) . '</p>
                                    </div>
                                </div>';
if (!empty($product['sale_price']) && $product['sale_price'] > 0) {
    $content .= '
                                <div class="flex items-start">
                                    <i class="fas fa-percentage text-green-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Sale Price</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-xl font-bold text-green-600">R' . number_format($product['sale_price'], 2) . '</p>
                                            <p class="text-sm text-gray-500 line-through">R' . number_format($product['price'], 2) . '</p>
                                        </div>
                                    </div>
                                </div>';
}
if (!empty($product['cost']) && $product['cost'] > 0) {
    $content .= '
                                <div class="flex items-start">
                                    <i class="fas fa-coins text-green-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Cost Price</p>
                                        <p class="text-sm font-semibold text-gray-900">R' . number_format($product['cost'], 2) . '</p>
                                    </div>
                                </div>';
}
$content .= '
                            </div>
                        </div>

                        <!-- Inventory Card -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-gray-800 font-semibold flex items-center">
                                    <i class="fas fa-warehouse mr-2 text-blue-500"></i>Inventory Status
                                </h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-start">
                                    <i class="fas fa-cubes text-yellow-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Stock Quantity</p>
                                        <div class="flex items-center gap-3">
                                            <p class="text-4xl font-bold ' . ($stock < 10 ? 'text-yellow-600' : 'text-gray-900') . '">' . $stock . '</p>
                                            <p class="text-lg text-gray-600">units</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas ' . $stockStatus['icon'] . ' text-yellow-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Stock Status</p>
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold ' . $stockStatus['class'] . '">
                                            <i class="fas ' . $stockStatus['icon'] . ' mr-2"></i>' . $stockStatus['label'] . '
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Physical Specifications Card -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-gray-800 font-semibold flex items-center">
                                    <i class="fas fa-ruler-combined mr-2 text-blue-500"></i>Physical Specifications
                                </h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-start">
                                    <i class="fas fa-weight-hanging text-indigo-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Weight</p>
                                        <p class="text-sm font-semibold text-gray-900">' . ($product['weight'] ? number_format($product['weight'], 2) . 'g' : '<span class="text-gray-400">Not specified</span>') . '</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-expand-arrows-alt text-indigo-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Dimensions</p>
                                        <p class="text-sm font-semibold text-gray-900">' . (!empty($product['dimensions']) ? htmlspecialchars($product['dimensions']) : '<span class="text-gray-400">Not specified</span>') . '</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Card -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                <h3 class="text-gray-800 font-semibold flex items-center">
                                    <i class="fas fa-toggle-on mr-2 text-blue-500"></i>Product Status
                                </h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-start">
                                    <i class="fas ' . (($product['active'] ?? 0) == 1 ? 'fa-eye' : 'fa-eye-slash') . ' text-cyan-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Visibility</p>
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold ' . (($product['active'] ?? 0) == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . '">
                                            <i class="fas ' . (($product['active'] ?? 0) == 1 ? 'fa-eye' : 'fa-eye-slash') . ' mr-2"></i>' . (($product['active'] ?? 0) == 1 ? 'Active' : 'Inactive') . '
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas ' . (($product['featured'] ?? 0) == 1 ? 'fa-star' : 'fa-star-o') . ' text-cyan-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Featured Status</p>
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold ' . (($product['featured'] ?? 0) == 1 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') . '">
                                            <i class="fas ' . (($product['featured'] ?? 0) == 1 ? 'fa-star' : 'fa-star-o') . ' mr-2"></i>' . (($product['featured'] ?? 0) == 1 ? 'Featured' : 'Not Featured') . '
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

if (!empty($product['description'])) {
    $content .= '
    <!-- Full Description - Full Width -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Full Description</h2>
            </div>
            <div class="p-6 sm:p-8">
                <div class="prose max-w-none text-gray-700 leading-relaxed bg-gray-50 p-6 rounded-lg">
                    ' . nl2br(htmlspecialchars($product['description'])) . '
                </div>
            </div>
        </div>
    </div>';
}

if (!empty($customFields)) {
    $content .= '
    <!-- Additional Information - Styled Table -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-6 sm:px-8 py-6">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-list-ul mr-3 text-blue-500"></i>Additional Information
                </h2>
            </div>
            <div class="p-6 sm:p-8">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" style="width: 30%;">Field Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">';
    foreach ($customFields as $field) {
        if (!empty($field['label']) && !empty($field['value'])) {
            $content .= '
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <div class="flex items-center">
                                    <i class="fas fa-tag text-indigo-500 mr-3"></i>
                                    ' . htmlspecialchars($field['label']) . '
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="bg-gray-50 px-4 py-3 rounded-lg">' . htmlspecialchars($field['value']) . '</div>
                            </td>
                        </tr>';
        }
    }
    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';
}

if (!empty($product['product_policies'])) {
    $content .= '
    <!-- Product Policies & Warranty - Styled Table -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-6 sm:px-8 py-6">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-shield-alt mr-3 text-blue-500"></i>Product Policies & Warranty
                </h2>
            </div>
            <div class="p-6 sm:p-8">
                <div class="bg-gradient-to-br from-teal-50 to-emerald-50 border-2 border-teal-200 rounded-xl p-6">
                    <div class="prose max-w-none text-gray-800">
                        <div class="flex items-start mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-teal-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-contract text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-900">Policy Details</h3>
                                <p class="text-sm text-gray-600 mt-1">Review the product policies and warranty information below</p>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                            ' . nl2br(htmlspecialchars($product['product_policies'])) . '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

if (!empty($product['meta_title']) || !empty($product['meta_description'])) {
    $content .= '
    <!-- SEO Information - Compact -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-6 sm:px-8 py-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-search mr-3 text-blue-500"></i>SEO Information
                </h2>
            </div>
            <div class="p-5">
                <div class="space-y-4">';
    if (!empty($product['meta_title'])) {
        $content .= '
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-heading text-violet-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Meta Title</label>
                            <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200">' . htmlspecialchars($product['meta_title']) . '</p>
                        </div>
                    </div>';
    }
    if (!empty($product['meta_description'])) {
        $content .= '
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-paragraph text-violet-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Meta Description</label>
                            <p class="text-sm text-gray-700 bg-gray-50 px-3 py-2 rounded border border-gray-200">' . htmlspecialchars($product['meta_description']) . '</p>
                        </div>
                    </div>';
    }
    $content .= '
                </div>
            </div>
        </div>
    </div>';
}

$content .= '
    <!-- Timestamps - Compact -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-6 sm:px-8 py-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-clock mr-3 text-blue-500"></i>Timestamps
                </h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-calendar-plus text-blue-600"></i>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Created</label>
                            <p class="text-sm text-gray-900 font-medium">' . date('M j, Y \a\t g:i A', strtotime($product['created_at'] ?? 'now')) . '</p>
                        </div>
                    </div>
                    <div class="flex items-center bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-calendar-alt text-green-600"></i>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Last Updated</label>
                            <p class="text-sm text-gray-900 font-medium">' . date('M j, Y \a\t g:i A', strtotime($product['updated_at'] ?? $product['created_at'] ?? 'now')) . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

if (!empty($variations)) {
    $content .= '
    <!-- Product Variations - Full Width -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Product Variations (' . count($variations) . ')</h2>
                        <p class="text-gray-600 mt-1">Manage different variants of this product</p>
                    </div>
                    <a href="' . adminUrl('/products/variations/') . '" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-tags mr-1"></i>Manage Variations →
                    </a>
                </div>
            </div>
            <div class="p-6 sm:p-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attributes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">';
    foreach ($variations as $var) {
        $content .= '
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-900">' . htmlspecialchars($var['sku'] ?? 'N/A') . '</td>
                                <td class="px-6 py-4 text-sm text-gray-900">' . htmlspecialchars($var['attributes'] ?? 'N/A') . '</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">R' . number_format($var['price'] ?? 0, 2) . '</td>
                                <td class="px-6 py-4 text-sm ' . (($var['stock'] ?? 0) < 10 ? 'text-yellow-600 font-semibold' : 'text-gray-900') . '">' . ($var['stock'] ?? 0) . '</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-medium ' . (($var['is_active'] ?? 0) == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . ' rounded-full">
                                        ' . (($var['is_active'] ?? 0) == 1 ? 'Active' : 'Inactive') . '
                                    </span>
                                </td>
                            </tr>';
    }
    $content .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>';
}

if (!empty($inquiries)) {
    $content .= '
    <!-- Recent Inquiries - Full Width -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Recent Inquiries (' . count($inquiries) . ')</h2>
                        <p class="text-gray-600 mt-1">Customer questions about this product</p>
                    </div>
                    <a href="' . adminUrl('/products/inquiries.php') . '" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-comments mr-1"></i>View All Inquiries →
                    </a>
                </div>
            </div>
            <div class="p-6 sm:p-8">
                <div class="space-y-6">';
    foreach ($inquiries as $inq) {
        $content .= '
                    <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="font-semibold text-gray-900 text-lg">' . htmlspecialchars($inq['name']) . '</p>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-envelope mr-1"></i>' . htmlspecialchars($inq['email']) . '
                                </p>
                            </div>
                            <span class="px-3 py-1 text-sm font-medium rounded-full ' . ($inq['status'] === 'new' ? 'bg-blue-100 text-blue-800' : ($inq['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) . '">
                                ' . ucfirst(str_replace('_', ' ', $inq['status'])) . '
                            </span>
                        </div>
                        <p class="text-gray-700 mb-4 leading-relaxed">' . nl2br(htmlspecialchars($inq['message'] ?? '')) . '</p>
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-clock mr-1"></i>' . date('F j, Y \a\t g:i A', strtotime($inq['created_at'])) . '
                        </p>
                    </div>';
    }
    $content .= '
                </div>
            </div>
        </div>
    </div>';
}

$content .= '
    <!-- Action Buttons - Full Width -->
    <div class="w-full mb-8" style="width: 100% !important;">
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Actions</h2>
                <p class="text-gray-600 mt-1">Manage this product</p>
            </div>
            <div class="p-6 sm:p-8">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="' . adminUrl('/products/edit/' . urlencode($product['slug'])) . '" class="flex items-center justify-center px-6 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-semibold">
                        <i class="fas fa-edit mr-3"></i>Edit Product
                    </a>

                    <a href="' . adminUrl('/products/inventory.php') . '" class="flex items-center justify-center px-6 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-semibold">
                        <i class="fas fa-boxes mr-3"></i>Manage Inventory
                    </a>

                    <a href="' . adminUrl('/products/variations/') . '" class="flex items-center justify-center px-6 py-4 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors font-semibold">
                        <i class="fas fa-tags mr-3"></i>Manage Variations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
    <div class="relative max-w-6xl max-h-full">
        <button onclick="closeImageModal()" class="absolute -top-12 right-0 text-white hover:text-gray-300 text-3xl">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="Full size image" class="max-w-full max-h-full object-contain rounded-lg">
    </div>
</div>

<style>
/* Force full width */
.w-full {
    width: 100% !important;
    max-width: 100% !important;
}

/* Cache busting */
img {
    cache-control: no-cache !important;
}
</style>

<script>
function openImageModal(src) {
    document.getElementById("modalImage").src = src;
    document.getElementById("imageModal").classList.remove("hidden");
    document.body.style.overflow = "hidden";
}

function closeImageModal() {
    document.getElementById("imageModal").classList.add("hidden");
    document.body.style.overflow = "auto";
}

// Close modal on ESC key
document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeImageModal();
    }
});

// Close modal when clicking outside image
document.getElementById("imageModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('View Product', $content, 'products');
?>
