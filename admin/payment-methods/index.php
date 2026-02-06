<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get payment methods
$payment_methods = [];
if ($db) {
    try {
        ensurePaymentMethodsSchema($db);
        $stmt = $db->query("SELECT * FROM payment_methods ORDER BY name ASC");
        $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting payment methods: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("INSERT INTO payment_methods (name, description, type, config, active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['type'],
                    $_POST['config'] ?? '',
                    $_POST['active'] ?? 0
                ]);
                $_SESSION['success'] = 'Payment method added successfully!';
                redirect(adminUrl('/payment-methods/'));
            } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
                $stmt = $db->prepare("UPDATE payment_methods SET name = ?, description = ?, type = ?, config = ?, active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['type'],
                    $_POST['config'] ?? '',
                    $_POST['active'] ?? 0,
                    $_POST['id']
                ]);
                $_SESSION['success'] = 'Payment method updated successfully!';
                redirect(adminUrl('/payment-methods/'));
            } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
                $stmt = $db->prepare("DELETE FROM payment_methods WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = 'Payment method deleted successfully!';
                redirect(adminUrl('/payment-methods/'));
            }
        }
    } catch (Exception $e) {
        error_log("Error processing payment method: " . $e->getMessage());
        $_SESSION['error'] = 'Error processing request. Please try again.';
    }
}

// Generate payment methods content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Payment Methods</h1>
            <p class="text-gray-600 mt-1">Configure payment gateways and options (' . count($payment_methods) . ' methods)</p>
        </div>
        <a href="' . adminUrl('/payment-methods/add/') . '" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Payment Method
        </a>
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

$content .= '<!-- Payment Methods Table -->';

if (empty($payment_methods)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-credit-card text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No payment methods yet</h3>
        <p class="text-gray-600 mb-6">Add your first payment method to get started</p>
        <button onclick="toggleAddModal()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
            <i class="fas fa-plus mr-2"></i>Add Payment Method
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($payment_methods as $method) {
        $activeStatus = ($method['active'] ?? 0) == 1 
            ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>'
            : '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Inactive</span>';
        
        $typeClass = $method['type'] === 'payfast' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
        
        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-credit-card text-gray-500"></i>
                                </div>
                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($method['name'] ?? 'N/A') . '</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium ' . $typeClass . ' rounded-full">' . strtoupper($method['type'] ?? 'N/A') . '</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($method['description'] ?? 'N/A') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $activeStatus . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="' . adminUrl('/payment-methods/edit/?id=' . $method['id']) . '" class="text-purple-600 hover:text-purple-900 mr-4">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this payment method?\')">
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
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('Payment Methods', $content, 'payment-methods');
