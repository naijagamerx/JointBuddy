<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require delivery methods service
require_once __DIR__ . '/../../includes/delivery_methods_service.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get delivery methods
$delivery_methods = [];
if ($db) {
    try {
        ensureDeliveryMethodsFreeShippingSchema($db);
        $stmt = $db->query("SELECT * FROM delivery_methods ORDER BY name ASC");
        $delivery_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting delivery methods: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    try {
        $freeShippingAmount = null;
        if (isset($_POST['free_shipping_min_amount']) && $_POST['free_shipping_min_amount'] !== '') {
            $value = floatval($_POST['free_shipping_min_amount']);
            if ($value > 0) {
                $freeShippingAmount = $value;
            }
        }

        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO delivery_methods (name, description, cost, free_shipping_min_amount, estimated_delivery_time, is_active, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['cost'],
                    $freeShippingAmount,
                    $_POST['estimated_delivery_time'] ?? '',
                    $_POST['is_active'] ?? 0,
                    $_POST['display_order'] ?? 0
                ]);
                $_SESSION['success'] = 'Delivery method added successfully!';
                redirect(adminUrl('/delivery-methods/'));
            } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
                $stmt = $db->prepare("UPDATE delivery_methods SET name = ?, description = ?, cost = ?, free_shipping_min_amount = ?, estimated_delivery_time = ?, is_active = ?, display_order = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['cost'],
                    $freeShippingAmount,
                    $_POST['estimated_delivery_time'] ?? '',
                    $_POST['is_active'] ?? 0,
                    $_POST['display_order'] ?? 0,
                    $_POST['id']
                ]);
                $_SESSION['success'] = 'Delivery method updated successfully!';
                redirect(adminUrl('/delivery-methods/'));
            } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
                $stmt = $db->prepare("DELETE FROM delivery_methods WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = 'Delivery method deleted successfully!';
                redirect(adminUrl('/delivery-methods/'));
            }
        }
    } catch (Exception $e) {
        error_log("Error processing delivery method: " . $e->getMessage());
        $_SESSION['error'] = 'Error processing request. Please try again.';
    }
}

// Generate delivery methods content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Delivery Methods</h1>
            <p class="text-gray-600 mt-1">Manage shipping and delivery options (' . count($delivery_methods) . ' methods)</p>
        </div>
        <button onclick="toggleAddModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Delivery Method
        </button>
    </div>

    <!-- Alert Messages -->';

if (isset($_SESSION['success'])) {
    $content .= adminAlert('success', $_SESSION['success']);
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $content .= adminAlert('error', $_SESSION['error']);
    unset($_SESSION['error']);
}

$content .= '<!-- Delivery Methods Table -->';

if (empty($delivery_methods)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-truck text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No delivery methods yet</h3>
        <p class="text-gray-600 mb-6">Add your first delivery method to get started</p>
        <button onclick="toggleAddModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Add Delivery Method
        </button>
    </div>';
} else {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost / Free Shipping</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($delivery_methods as $method) {
        $costValue = $method['cost'] ?? 0;
        $costDisplay = 'R' . number_format($costValue, 2);
        if (!empty($method['free_shipping_min_amount'])) {
            $costDisplay .= '<div class="text-xs text-green-600 mt-1">Free over R' . number_format($method['free_shipping_min_amount'], 2) . '</div>';
        }

        $activeStatus = ($method['is_active'] ?? 0) == 1
            ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>'
            : '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Inactive</span>';

        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($method['name'] ?? 'N/A') . '</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($method['description'] ?? 'N/A') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">' . $costDisplay . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $activeStatus . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="editMethod(' . $method['id'] . ', \'' . addslashes($method['name'] ?? '') . '\', \'' . addslashes($method['description'] ?? '') . '\', \'' . ($method['cost'] ?? 0) . '\', \'' . ($method['free_shipping_min_amount'] ?? '') . '\', \'' . ($method['is_active'] ?? 0) . '\', \'' . addslashes($method['estimated_delivery_time'] ?? '') . '\', \'' . ($method['display_order'] ?? 0) . '\')" class="text-blue-600 hover:text-blue-900 mr-4">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this delivery method?\')">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="' . $method['id'] . '">
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </form>
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

<!-- Add/Edit Modal -->
<div id="methodModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add Delivery Method</h3>
            </div>
            <form method="POST" class="px-6 py-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="methodId" value="">

                ' . adminFormInput('Name', 'name', '', 'text', true, 'e.g., Standard Shipping') . '

                ' . adminFormTextarea('Description', 'description', '', 3, false, 'Brief description of this delivery method') . '

                ' . adminFormInput('Cost (R)', 'cost', '', 'number', true, '0.00', ['step' => '0.01', 'min' => '0']) . '

                ' . adminFormInput('Free shipping over amount (R)', 'free_shipping_min_amount', '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '

                ' . adminFormInput('Estimated Delivery Time', 'estimated_delivery_time', '', 'text', false, 'e.g., 2-3 business days') . '

                ' . adminFormInput('Display Order', 'display_order', '0', 'number', false, '0', ['step' => '1', 'min' => '0']) . '

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleAddModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAddModal() {
    const modal = document.getElementById("methodModal");
    modal.classList.toggle("hidden");
    
    // Reset form
    if (modal.classList.contains("hidden")) {
        document.getElementById("modalTitle").textContent = "Add Delivery Method";
        document.getElementById("formAction").value = "add";
        document.getElementById("methodId").value = "";
        document.querySelector("#methodModal form").reset();
    }
}

function editMethod(id, name, description, cost, free_shipping_min_amount, is_active, estimated_delivery_time, display_order) {
    document.getElementById("modalTitle").textContent = "Edit Delivery Method";
    document.getElementById("formAction").value = "update";
    document.getElementById("methodId").value = id;
    document.getElementById("field_name").value = name;
    document.getElementById("field_description").value = description;
    document.getElementById("field_cost").value = cost;
    document.getElementById("field_free_shipping_min_amount").value = free_shipping_min_amount || "";
    document.getElementById("field_estimated_delivery_time").value = estimated_delivery_time || "";
    document.getElementById("field_display_order").value = display_order || "0";

    if (is_active == "1") {
        document.querySelector("#methodModal input[name=\'is_active\']").checked = true;
    } else {
        document.querySelector("#methodModal input[name=\'is_active\']").checked = false;
    }

    document.getElementById("methodModal").classList.remove("hidden");
}
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Delivery Methods', $content, 'delivery-methods');
