<?php
// Include bootstrap for all core services
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../admin_sidebar_components.php';

// Get services
$db = Services::db();
$adminAuth = Services::adminAuth();

// Check if admin is logged in
if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                // Validate required fields
                if (empty($_POST['product_id']) || empty($_POST['sku']) || empty($_POST['variation_name']) || empty($_POST['price']) || empty($_POST['stock_quantity'])) {
                    throw new Exception('All fields are required');
                }

                // Check if product exists
                $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception('Product not found');
                }

                // Check if SKU already exists
                $stmt = $db->prepare("SELECT id FROM product_variations WHERE sku = ?");
                $stmt->execute([$_POST['sku']]);
                if ($stmt->fetch()) {
                    throw new Exception('SKU already exists');
                }

                // Create variation
                $stmt = $db->prepare('INSERT INTO product_variations (product_id, sku, variation_name, variation_type, price, stock_quantity, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
                $result = $stmt->execute([
                    $_POST['product_id'],
                    $_POST['sku'],
                    $_POST['variation_name'],
                    $_POST['variation_type'] ?? 'size',
                    (float)$_POST['price'],
                    (int)$_POST['stock_quantity'],
                    isset($_POST['is_active']) ? 1 : 0
                ]);

                if ($result) {
                    $message = 'Variation created successfully';
                } else {
                    throw new Exception('Failed to create variation');
                }
            } elseif ($_POST['action'] === 'update') {
                // Validate required fields
                if (empty($_POST['id']) || empty($_POST['sku']) || empty($_POST['variation_name']) || empty($_POST['price']) || empty($_POST['stock_quantity'])) {
                    throw new Exception('All fields are required');
                }

                // Check if variation exists
                $stmt = $db->prepare("SELECT id FROM product_variations WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                if (!$stmt->fetch()) {
                    throw new Exception('Variation not found');
                }

                // Check if SKU already exists (excluding current variation)
                $stmt = $db->prepare("SELECT id FROM product_variations WHERE sku = ? AND id != ?");
                $stmt->execute([$_POST['sku'], $_POST['id']]);
                if ($stmt->fetch()) {
                    throw new Exception('SKU already exists');
                }

                // Update variation
                $stmt = $db->prepare('UPDATE product_variations SET sku = ?, variation_name = ?, variation_type = ?, price = ?, stock_quantity = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
                $result = $stmt->execute([
                    $_POST['sku'],
                    $_POST['variation_name'],
                    $_POST['variation_type'] ?? 'size',
                    (float)$_POST['price'],
                    (int)$_POST['stock_quantity'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['id']
                ]);

                if ($result) {
                    $message = 'Variation updated successfully';
                } else {
                    throw new Exception('Failed to update variation');
                }
            } elseif ($_POST['action'] === 'delete') {
                if (empty($_POST['id'])) {
                    throw new Exception('Variation ID is required');
                }

                $stmt = $db->prepare('DELETE FROM product_variations WHERE id = ?');
                $result = $stmt->execute([$_POST['id']]);

                if ($result) {
                    $message = 'Variation deleted successfully';
                } else {
                    throw new Exception('Failed to delete variation');
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("Product variation error: " . $e->getMessage());
        }
    }
}

// Get all variations
$variations = [];
if ($db) {
    try {
        $stmt = $db->query('SELECT pv.*, p.name AS product_name, p.slug AS product_slug, p.images AS product_images FROM product_variations pv JOIN products p ON p.id = pv.product_id ORDER BY pv.created_at DESC');
        $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Unable to load variations: ' . $e->getMessage();
        error_log("Error loading variations: " . $e->getMessage());
    }
}

// Get products for dropdown
$products = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT id, name, slug FROM products WHERE active = 1 ORDER BY name ASC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error loading products: " . $e->getMessage());
    }
}

// Generate content
$content = '<div class="max-w-7xl mx-auto">';

if ($message) {
    $content .= '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . htmlspecialchars($message) . '</p>
            </div>
        </div>
    </div>';
}

if ($error) {
    $content .= '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . htmlspecialchars($error) . '</p>
            </div>
        </div>
    </div>';
}

$content .= '<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Product Variations</h2>
    <p class="text-gray-600 mt-1">Manage product variations (' . count($variations) . ' variations)</p>
</div>';

// Variations table
if (!empty($variations)) {
    $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attributes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    foreach ($variations as $var) {
        $productImage = '';
        if (!empty($var['product_images'])) {
            $imageUrls = explode(',', $var['product_images']);
            $dbPath = trim($imageUrls[0]);
            // Remove hardcoded paths and convert to proper URL
            $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
            $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
            $dbPath = ltrim($dbPath, '/');
            $productImage = url($dbPath);
        }
        if (empty($productImage)) $productImage = url('/assets/images/placeholder.png');

        $content .= '<tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        <img class="h-10 w-10 rounded-full object-cover" src="' . htmlspecialchars($productImage) . '" alt="">
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900" title="' . htmlspecialchars($var['product_name']) . '">' . 
                            htmlspecialchars(strlen($var['product_name']) > 30 ? substr($var['product_name'], 0, 30) . '...' : $var['product_name']) . 
                        '</div>
                        <div class="text-sm text-gray-500">#' . $var['product_id'] . '</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">' . htmlspecialchars($var['sku']) . '</div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900">' . htmlspecialchars($var['variation_name']) . '</div>
                <div class="text-xs text-gray-500">' . ucfirst($var['variation_type']) . '</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">R' . number_format($var['price'], 2) . '</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm ' . ($var['stock_quantity'] < 10 ? 'text-yellow-600' : 'text-gray-900') . ' font-medium">
                    ' . (int)$var['stock_quantity'] . ' units
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium ' . ($var['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . ' rounded-full">
                    ' . ($var['is_active'] ? 'Yes' : 'No') . '
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editVariation(' . htmlspecialchars(json_encode($var)) . ')" class="text-blue-600 hover:text-blue-900 mr-4">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this variation?\')">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="' . $var['id'] . '">
                    <button type="submit" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </form>
            </td>
        </tr>';
    }

    $content .= '</tbody>
            </table>
        </div>
    </div>';
} else {
    $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-tags text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No variations yet</h3>
        <p class="text-gray-600">Get started by creating your first product variation</p>
    </div>';
}

// Create/Edit form
$content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Create New Variation</h3>
    <form method="POST" id="variationForm" class="space-y-4">
        ' . csrf_field() . '
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="id" id="variationId" value="">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                <select name="product_id" id="productId" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Product</option>';

foreach ($products as $product) {
    $content .= '<option value="' . $product['id'] . '">' . htmlspecialchars($product['name']) . '</option>';
}

$content .= '</select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                <input type="text" name="sku" id="sku" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., PROD-001-SM">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (R) *</label>
                <input type="number" name="price" id="price" step="0.01" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0.00">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Stock *</label>
                <input type="number" name="stock_quantity" id="stock" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Variation Name *</label>
                <input type="text" name="variation_name" id="variationName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Small, Red, 500mg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Variation Type *</label>
                <select name="variation_type" id="variationType" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="size">Size</option>
                    <option value="weight">Weight</option>
                    <option value="color">Color</option>
                    <option value="flavor">Flavor</option>
                    <option value="strength">Strength</option>
                </select>
            </div>
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="is_active" id="isActive" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label class="ml-2 text-sm text-gray-700">
                <strong>Active</strong><br>
                <span class="text-gray-500">Variation is available for purchase</span>
            </label>
        </div>

        <div class="flex justify-end space-x-3">
            <button type="button" onclick="resetForm()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                Reset
            </button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                <i class="fas fa-save mr-1"></i><span id="submitText">Create Variation</span>
            </button>
        </div>
    </form>
</div>';

$content .= '</div>';

$content .= '<script>
function editVariation(variation) {
    document.getElementById("formAction").value = "update";
    document.getElementById("variationId").value = variation.id;
    document.getElementById("productId").value = variation.product_id;
    document.getElementById("sku").value = variation.sku;
    document.getElementById("price").value = variation.price;
    document.getElementById("stock").value = variation.stock_quantity;
    document.getElementById("variationName").value = variation.variation_name || "";
    document.getElementById("variationType").value = variation.variation_type || "size";

    if (variation.is_active == 1) {
        document.getElementById("isActive").checked = true;
    } else {
        document.getElementById("isActive").checked = false;
    }

    document.getElementById("submitText").textContent = "Update Variation";
}

function resetForm() {
    document.getElementById("formAction").value = "create";
    document.getElementById("variationId").value = "";
    document.getElementById("variationForm").reset();
    document.getElementById("isActive").checked = true;
    document.getElementById("submitText").textContent = "Create Variation";
}
</script>';

echo adminSidebarWrapper('Variations', $content, 'variations');
