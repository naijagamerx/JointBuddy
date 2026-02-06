<?php
// Enable comprehensive error reporting for debugging this page
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Setup Whoops for beautiful error pages (if debug mode is enabled)
if (isset($_GET['debug']) || isset($_GET['whoops'])) {
    require_once __DIR__ . '/../../includes/whoops_handler.php';
}

// Setup admin error handling
require_once __DIR__ . '/../../includes/admin_error_catcher.php';

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get products with inventory data
$products = [];
$low_stock_products = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM products ORDER BY name ASC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Identify low stock products
        foreach ($products as $product) {
            if (($product['stock'] ?? 0) < 10) {
                $low_stock_products[] = $product;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting products: " . $e->getMessage());
    }
}

// Handle inventory updates
if ($_POST && $adminAuth && $db) {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $newStockRaw = $_POST['stock'] ?? null;
            $reason = trim($_POST['change_reason'] ?? '');

            if ($productId <= 0) {
                $_SESSION['error'] = 'Invalid product selection.';
                redirect(adminUrl('/products/inventory.php'));
            }

            if ($newStockRaw === null || $newStockRaw === '') {
                $_SESSION['error'] = 'New stock quantity is required.';
                redirect(adminUrl('/products/inventory.php'));
            }

            if (!is_numeric($newStockRaw)) {
                $_SESSION['error'] = 'New stock quantity must be a number.';
                redirect(adminUrl('/products/inventory.php'));
            }

            $newStock = (int)$newStockRaw;
            if ($newStock < 0) {
                $_SESSION['error'] = 'New stock quantity cannot be negative.';
                redirect(adminUrl('/products/inventory.php'));
            }

            if ($reason === '') {
                $_SESSION['error'] = 'Please select a change reason.';
                redirect(adminUrl('/products/inventory.php'));
            }

            $product = null;
            foreach ($products as $p) {
                if ((int)$p['id'] === $productId) {
                    $product = $p;
                    break;
                }
            }

            if (!$product) {
                $_SESSION['error'] = 'Product not found for inventory update.';
                redirect(adminUrl('/products/inventory.php'));
            }

            $previousStock = (int)($product['stock'] ?? 0);

            $stmt = $db->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStock, $productId]);

            $stmt = $db->prepare("INSERT INTO inventory_logs (product_id, change_amount, previous_stock, new_stock, reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $changeAmount = $newStock - $previousStock;
            $stmt->execute([
                $productId,
                $changeAmount,
                $previousStock,
                $newStock,
                $reason
            ]);

            $_SESSION['success'] = 'Inventory updated successfully.';
            redirect(adminUrl('/products/inventory.php'));
        }
    } catch (Exception $e) {
        error_log("Error updating inventory: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating inventory. Please try again.';
        redirect(adminUrl('/products/inventory.php'));
    }
}

// Generate inventory content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
            <p class="text-gray-600 mt-1">Manage product stock levels and track inventory (' . count($products) . ' products)</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="exportInventory()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Export
            </button>
            <button onclick="bulkUpdate()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-edit mr-2"></i>Bulk Update
            </button>
        </div>
    </div>

    <!-- Alert Messages -->';

if (isset($_SESSION['success'])) {
    $content .= adminAlert($_SESSION['success'], 'success');
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $content .= adminAlert($_SESSION['error'], 'error');
    unset($_SESSION['error']);
}

// Low stock alerts
if (!empty($low_stock_products)) {
    $content .= adminAlert('Low Stock Alert: ' . count($low_stock_products) . ' product(s) have low stock levels (below 10 units)', 'warning');
}

$content .= '
    <!-- Inventory Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        ' . adminStatCard('Total Products', count($products), 'fas fa-box', 'blue') . '
        ' . adminStatCard('Low Stock Items', count($low_stock_products), 'fas fa-exclamation-triangle', 'yellow') . '
        ' . adminStatCard('Out of Stock', count(array_filter($products, function($p) { return ($p['stock'] ?? 0) == 0; })), 'fas fa-times-circle', 'red') . '
    </div>';
// Calculate total inventory value
$total_value = 0;
foreach ($products as $product) {
    $total_value += ($product['stock'] ?? 0) * ($product['price'] ?? 0);
}
$content .= adminStatCard('Total Value', 'R' . number_format($total_value, 2), 'fas fa-dollar-sign', 'green') . '
    </div>

    <!-- Inventory Table -->';

if (empty($products)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-warehouse text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
        <p class="text-gray-600 mb-6">Add products first to manage inventory</p>
        <a href="' . adminUrl('/products/add.php') . '" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Add Product
        </a>
    </div>';
} else {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Product Inventory</h3>
                <div class="flex items-center space-x-2">
                    <input type="text" placeholder="Search products..." 
                           class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           onkeyup="filterInventory(this)">
                    <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($products as $product) {
        $stock = $product['stock'] ?? 0;
        $price = $product['price'] ?? 0;
        $value = $stock * $price;
        
        // Stock status
        if ($stock == 0) {
            $status_class = 'bg-red-100 text-red-800';
            $status_text = 'Out of Stock';
        } elseif ($stock < 10) {
            $status_class = 'bg-yellow-100 text-yellow-800';
            $status_text = 'Low Stock';
        } else {
            $status_class = 'bg-green-100 text-green-800';
            $status_text = 'In Stock';
        }
        
        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-box text-gray-500 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        ' . htmlspecialchars($product['name'] ?? 'N/A') . '
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ' . htmlspecialchars($product['slug'] ?? '') . '
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($product['sku'] ?? 'N/A') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">' . $stock . ' units</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium ' . $status_class . ' rounded-full">' . $status_text . '</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">R' . number_format($value, 2) . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="updateStock(' . $product['id'] . ', \'' . addslashes($product['name'] ?? '') . '\', ' . $stock . ')" class="text-blue-600 hover:text-blue-900 mr-4">
                                <i class="fas fa-edit mr-1"></i>Update
                            </button>
                            <button onclick="viewHistory(' . $product['id'] . ')" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-history mr-1"></i>History
                            </button>
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
</div>

<!-- Update Stock Modal -->
<div id="stockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Update Stock</h3>
            </div>
            <form method="POST" class="px-6 py-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="product_id" id="productId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <div id="productName" class="text-sm text-gray-600 bg-gray-50 p-2 rounded"></div>
                </div>
                
                ' . adminFormInput('Current Stock', 'current_stock', '', 'number', true, '', ['readonly' => 'readonly']) . '
                
                ' . adminFormInput('New Stock Quantity', 'stock', '', 'number', true, 'Enter new stock quantity', ['min' => '0']) . '
                
                ' . adminFormSelect('Change Reason', 'change_reason', '', [
                    'restock' => 'Restock',
                    'sale' => 'Sale',
                    'return' => 'Return',
                    'damaged' => 'Damaged/Lost',
                    'correction' => 'Stock Correction',
                    'other' => 'Other'
                ], true) . '
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleStockModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleStockModal() {
    const modal = document.getElementById("stockModal");
    modal.classList.toggle("hidden");
}

function updateStock(productId, productName, currentStock) {
    document.getElementById("modalTitle").textContent = "Update Stock";
    document.getElementById("productId").value = productId;
    document.getElementById("productName").textContent = productName;
    document.getElementById("field_current_stock").value = currentStock;
    document.getElementById("field_stock").value = currentStock;
    document.getElementById("stockModal").classList.remove("hidden");
}

function filterInventory(input) {
    const filter = input.value.toLowerCase();
    const table = input.closest(".bg-white").querySelector("table");
    const rows = table.querySelectorAll("tbody tr");
    
    rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

function exportInventory() {
    // Simple export functionality - in real implementation, generate CSV/PDF
    alert("Export functionality would be implemented here");
}

function bulkUpdate() {
    alert("Bulk update functionality would be implemented here");
}

function viewHistory(productId) {
    // View inventory history for the product
    alert("Inventory history for product " + productId + " would be shown here");
}
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Inventory', $content, 'products');
