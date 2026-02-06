<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get product slug from URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Extract slug from /admin/products/delete/{slug}
$pathParts = explode('/', $path);
$productSlug = '';
$deleteIndex = array_search('delete', $pathParts);
if ($deleteIndex !== false && isset($pathParts[$deleteIndex + 1])) {
    $productSlug = $pathParts[$deleteIndex + 1];
} else {
    $productSlug = $_GET['slug'] ?? '';
}

// Handle deletion
if ($db && $productSlug) {
    try {
        // First fetch the product to confirm it exists
        $stmt = $db->prepare("SELECT * FROM products WHERE slug = ?");
        $stmt->execute([$productSlug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['error'] = 'Product not found';
            redirect('/admin/products/');
        }

        // Check if this is a POST request (confirming deletion)
        if ($_POST && isset($_POST['confirm_delete'])) {
            // Delete the product
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product['id']]);

            $_SESSION['success'] = 'Product deleted successfully';
            redirect('/admin/products/');
        } elseif ($_POST && isset($_POST['cancel'])) {
            // Cancel deletion
            redirect('/admin/products/');
        }

        // Generate confirmation page
        $content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-red-600">Delete Product</h1>
        <p class="text-gray-600 mt-1">Are you sure you want to delete this product?</p>
    </div>

    <!-- Confirmation Form -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Product Details</h2>
        </div>
        <div class="px-6 py-6">
            <div class="flex items-start space-x-4">
                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                    <i class="fas fa-box text-gray-500 text-3xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900">' . htmlspecialchars($product['name'] ?? '') . '</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        <strong>SKU:</strong> ' . htmlspecialchars($product['sku'] ?? 'N/A') . '<br>
                        <strong>Price:</strong> R' . number_format($product['price'] ?? 0, 2) . '<br>
                        <strong>Stock:</strong> ' . ($product['stock'] ?? 0) . ' units
                    </p>
                </div>
            </div>

            <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Warning</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>This action cannot be undone. This will permanently delete the product and all associated data.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmation Form -->
            <form method="POST" class="mt-6">
                ' . csrf_field() . '
                <div class="flex justify-end space-x-3">
                    <a href="' . adminUrl('/products/') . '" class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" name="confirm_delete" class="bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>';

        // Render the page with sidebar
        echo adminSidebarWrapper('Delete Product', $content, 'products');

    } catch (Exception $e) {
        $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error deleting product');
        redirect('/admin/products/');
    }
} else {
    $_SESSION['error'] = 'No product specified';
    redirect('/admin/products/');
}
?>
