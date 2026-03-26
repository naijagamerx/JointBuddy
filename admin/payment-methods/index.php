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
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" style="background-color: ' . htmlspecialchars($method['color'] ?? '#6B7280') . ';">
                                    <i class="fas fa-credit-card text-white"></i>
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
                            <button onclick="viewPaymentMethod(' . $method['id'] . ')" class="text-blue-600 hover:text-blue-900 mr-2 p-1" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="' . adminUrl('/payment-methods/edit/?id=' . $method['id']) . '" class="text-purple-600 hover:text-purple-900 mr-2 p-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this payment method?\')">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="' . $method['id'] . '">
                                <button type="submit" class="text-red-600 hover:text-red-900 p-1" title="Delete">
                                    <i class="fas fa-trash"></i>
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
    <!-- View Payment Method Off-Canvas (Slide from Right) -->
    <div id="viewOffcanvas" class="fixed inset-0 z-50 hidden overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeViewOffcanvas()"></div>
        </div>

        <!-- Off-canvas panel -->
        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div class="w-screen max-w-md">
                <div class="h-full flex flex-col bg-white shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 py-6 border-b border-gray-200 sm:px-6">
                        <h2 class="text-lg font-medium text-gray-900" id="slide-over-title">Payment Method Details</h2>
                        <button type="button" onclick="closeViewOffcanvas()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <!-- Content -->
                    <div class="relative flex-1 px-4 py-6 sm:px-6 overflow-y-auto">
                        <div id="offcanvas-content">
                            <div class="text-center py-12">
                                <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                                <p class="mt-2 text-gray-600">Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewPaymentMethod(id) {
            document.getElementById("viewOffcanvas").classList.remove("hidden");
            document.getElementById("offcanvas-content").innerHTML = "<div class=\'text-center py-12\'><i class=\'fas fa-spinner fa-spin text-2xl text-gray-400\'></i><p class=\'mt-2 text-gray-600\'>Loading...</p></div>";

            fetch("' . adminUrl('/payment-methods/view/?id=') . '" + id, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin"
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error("HTTP " + r.status);
                }
                return r.json();
            })
            .then(d => {
                if (d.success) {
                    let html = "<div class=\'space-y-6\'>";
                    html += "<div class=\'flex items-center pb-4 border-b border-gray-200\'><div class=\'w-12 h-12 rounded-lg flex items-center justify-center mr-4\' style=\'background-color: " + (d.payment_method.color || "#6B7280") + "\'><i class=\'fas fa-credit-card text-white text-xl\'></i></div><div><h3 class=\'text-lg font-semibold text-gray-900\'>" + escapeHtml(d.payment_method.name) + "</h3><p class=\'text-sm text-gray-500\'>" + escapeHtml(d.payment_method.type || "") + (d.payment_method.manual_type ? " (" + escapeHtml(d.payment_method.manual_type) + ")" : "") + "</p></div></div>";
                    if (d.payment_method.description) html += "<div><h4 class=\'text-sm font-medium text-gray-700 mb-1\'>Description</h4><p class=\'text-sm text-gray-600\'>" + escapeHtml(d.payment_method.description) + "</p></div>";
                    if (Object.keys(d.fields).length > 0) {
                        html += "<div><h4 class=\'text-sm font-medium text-gray-700 mb-3\'>Payment Details</h4><div class=\'bg-gray-50 rounded-lg p-4\'>";
                        for (let [k,v] of Object.entries(d.fields)) if (v) html += "<div class=\'flex justify-between py-2 border-b border-gray-100 last:border-0\'><span class=\'text-sm font-medium text-gray-700\'>" + escapeHtml(k) + ":</span><span class=\'text-sm text-gray-900\'>" + escapeHtml(v) + "</span></div>";
                        html += "</div></div>";
                    }
                    if (d.payment_method.qr_code_path && d.payment_method.asset_url) html += "<div><h4 class=\'text-sm font-medium text-gray-700 mb-2\'>QR Code</h4><img src=\'" + escapeHtml(d.payment_method.asset_url) + "\' alt=\'QR Code\' class=\'w-32 h-32 border border-gray-200 rounded-lg\'></div>";
                    html += "<div class=\'flex items-center justify-between pt-4 border-t border-gray-200\'><span class=\'text-sm font-medium text-gray-700\'>Status</span><span class=\'px-2 py-1 text-xs font-medium " + (d.payment_method.active == 1 ? "bg-green-100 text-green-800" : "bg-gray-100 text-gray-800") + " rounded-full\'>" + (d.payment_method.active == 1 ? "Active" : "Inactive") + "</span></div>";
                    html += "</div>";
                    document.getElementById("offcanvas-content").innerHTML = html;
                } else {
                    document.getElementById("offcanvas-content").innerHTML = "<p class=\'text-red-600\'>" + escapeHtml(d.message || "Error loading") + "</p>";
                }
            })
            .catch(e => {
                document.getElementById("offcanvas-content").innerHTML = "<p class=\'text-red-600\'>Error: " + escapeHtml(e.message) + "</p>";
            });
        }

        function closeViewOffcanvas() {
            document.getElementById("viewOffcanvas").classList.add("hidden");
        }

        function escapeHtml(text) {
            if (!text) return "";
            const div = document.createElement("div");
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('Payment Methods', $content, 'payment-methods');
