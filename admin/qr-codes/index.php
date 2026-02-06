<?php
// QR Codes Management - Admin Page
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require QR code service
require_once __DIR__ . '/../../includes/services/QRCodeService.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$adminId = AuthMiddleware::getAdminId();
$qrService = new QRCodeService($db);

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $qrCodeId = (int)($_POST['qr_code_id'] ?? 0);
        $result = $qrService->deleteQRCode($qrCodeId);
        if ($result['success']) {
            $message = 'QR code deleted successfully.';
            $messageType = 'success';
        } else {
            $message = $result['message'] ?? 'Failed to delete QR code.';
            $messageType = 'error';
        }
    } elseif ($action === 'toggle_status') {
        $qrCodeId = (int)($_POST['qr_code_id'] ?? 0);
        $result = $qrService->toggleQRCodeStatus($qrCodeId);
        $message = 'QR code status updated.';
        $messageType = 'success';
    }
}

// Get filter
$filterType = $_GET['type'] ?? '';
$qrCodes = $qrService->getAllQRCodes($filterType ?: null, 100);
$stats = $qrService->getQRStatistics();

// Get products and orders for generate modal
$products = $qrService->getProductsForDropdown();
$orders = $qrService->getOrdersForDropdown();

// Generate content
$content = '<div class="w-full max-w-7xl mx-auto">';

// Message display
if ($message) {
    $bgClass = $messageType === 'success' ? 'bg-green-50 border-green-400 text-green-800' : 'bg-red-50 border-red-400 text-red-800';
    $iconClass = $messageType === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400';
    $content .= '
    <div class="border-l-4 p-4 mb-6 rounded-lg ' . $bgClass . '">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas ' . $iconClass . '"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm">' . htmlspecialchars($message) . '</p>
            </div>
        </div>
    </div>';
}

// Statistics cards
$content .= '
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-blue-100 text-blue-600 mr-3">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total QR Codes</p>
                    <p class="text-xl font-bold text-gray-900">' . $stats['total_qr_codes'] . '</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-green-100 text-green-600 mr-3">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Product QRs</p>
                    <p class="text-xl font-bold text-gray-900">' . $stats['product_qr_codes'] . '</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-purple-100 text-purple-600 mr-3">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Invoice QRs</p>
                    <p class="text-xl font-bold text-gray-900">' . $stats['invoice_qr_codes'] . '</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-indigo-100 text-indigo-600 mr-3">
                    <i class="fas fa-link"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Custom Links</p>
                    <p class="text-xl font-bold text-gray-900">' . ($stats['custom_link_qr_codes'] ?? 0) . '</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-yellow-100 text-yellow-600 mr-3">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Scans Today</p>
                    <p class="text-xl font-bold text-gray-900">' . $stats['scans_today'] . '</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">QR Codes</h1>
            <p class="text-gray-600 mt-1">Generate and manage QR codes for products and invoices</p>
        </div>
        <div class="flex space-x-3">
            <select id="filterType" onchange="window.location.href=\'?type=\'+this.value" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All QR Codes</option>
                <option value="product" ' . ($filterType === 'product' ? 'selected' : '') . '>Products</option>
                <option value="invoice" ' . ($filterType === 'invoice' ? 'selected' : '') . '>Invoices</option>
                <option value="custom_link" ' . ($filterType === 'custom_link' ? 'selected' : '') . '>Custom Links</option>
            </select>
            <button onclick="openGenerateModal()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Generate QR Code
            </button>
        </div>
    </div>';

if (empty($qrCodes)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-qrcode text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No QR codes yet</h3>
        <p class="text-gray-600 mb-6">Generate your first QR code for a product or invoice</p>
        <button onclick="openGenerateModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i>Generate QR Code
        </button>
    </div>';
} else {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scans</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    foreach ($qrCodes as $qr) {
        // Resolve image URL (handle both legacy absolute URLs and new relative paths)
        $qrImageUrl = '';
        if (!empty($qr['qr_code_image_path'])) {
            if (strpos($qr['qr_code_image_path'], 'http') === 0) {
                $qrImageUrl = $qr['qr_code_image_path'];
            } else {
                $qrImageUrl = assetUrl($qr['qr_code_image_path']);
            }
        }

        if ($qr['qr_code_type'] === 'product') {
            $typeIcon = 'fa-box text-green-600';
            $typeLabel = 'Product';
        } elseif ($qr['qr_code_type'] === 'invoice') {
            $typeIcon = 'fa-file-invoice text-purple-600';
            $typeLabel = 'Invoice';
        } elseif ($qr['qr_code_type'] === 'custom_link') {
            $typeIcon = 'fa-link text-blue-600';
            $typeLabel = 'Custom Link';
        } else {
            $typeIcon = 'fa-qrcode text-gray-600';
            $typeLabel = 'QR Code';
        }

        $statusBadge = $qr['is_active']
            ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>'
            : '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Inactive</span>';

        // Display different reference info based on type
        if ($qr['qr_code_type'] === 'custom_link') {
            $referenceDisplay = '<div class="text-sm text-gray-900" title="' . htmlspecialchars($qr['reference_id'] ?? 'N/A') . '">' . (strlen($qr['reference_id'] ?? '') > 40 ? htmlspecialchars(substr($qr['reference_id'], 0, 40)) . '...' : htmlspecialchars($qr['reference_id'] ?? 'N/A')) . '</div>';
        } else {
            $referenceDisplay = '<div class="text-sm text-gray-900" title="' . htmlspecialchars($qr['reference_name'] ?? 'N/A') . '">' . (strlen($qr['reference_name'] ?? '') > 30 ? htmlspecialchars(substr($qr['reference_name'], 0, 30)) . '...' : htmlspecialchars($qr['reference_name'] ?? 'N/A')) . '</div>
                            <div class="text-xs text-gray-500">ID: ' . $qr['reference_id'] . '</div>';
        }

        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas ' . $typeIcon . ' mr-2"></i>
                                <span class="text-sm font-medium text-gray-900">' . $typeLabel . '</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $referenceDisplay . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">' . htmlspecialchars($qr['qr_code_label'] ?? '-') . '</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . ($qrImageUrl ? '<img src="' . htmlspecialchars($qrImageUrl) . '" alt="QR Code" class="w-12 h-12 border border-gray-200 rounded">' : '<span class="text-gray-400">No image</span>') . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">' . ($qr['scan_count'] ?? 0) . '</span>
                            <a href="' . adminUrl('/qr-codes/scans?id=' . $qr['id']) . '" class="ml-2 text-xs text-blue-600 hover:text-blue-800">View</a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">' . $statusBadge . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ' . date('M j, Y', strtotime($qr['created_at'])) . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="' . adminUrl('/qr-codes/scans?id=' . $qr['id']) . '" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 mr-2" title="View Scans">
                                <i class="fas fa-chart-line"></i>
                            </a>
                            ' . ($qrImageUrl ? '<a href="' . htmlspecialchars($qrImageUrl) . '" download class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600 hover:bg-green-200 mr-2" title="Download">
                                <i class="fas fa-download"></i>
                            </a>' : '') . '
                            <button onclick="toggleStatus(' . $qr['id'] . ')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 hover:bg-yellow-200 mr-2" title="Toggle Status">
                                <i class="fas fa-power-off"></i>
                            </button>
                            <button onclick="deleteQRCode(' . $qr['id'] . ', \'' . htmlspecialchars($qr['reference_name'] ?? 'this QR code') . '\')" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200" title="Delete">
                                <i class="fas fa-trash"></i>
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

<!-- Generate QR Code Modal -->
<div id="generateModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeGenerateModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <button onclick="closeGenerateModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="text-xl font-bold text-gray-900 mb-4">Generate QR Code</h2>

            <form id="generateForm" method="POST" action="' . adminUrl('/qr-codes/generate.php') . '" class="space-y-4">
                <input type="hidden" name="action" value="generate">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">QR Code Type</label>
                    <select id="qrType" name="qr_type" required onchange="updateReferenceOptions()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select type...</option>
                        <option value="product">Product QR Code</option>
                        <option value="invoice">Invoice QR Code</option>
                        <option value="custom_link">Custom Link QR Code</option>
                    </select>
                </div>

                <div id="productSelect" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Product</label>
                    <select id="productId" name="product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select a product...</option>';
                        foreach ($products as $p) {
                            $content .= '<option value="' . $p['id'] . '">' . htmlspecialchars($p['name']) . '</option>';
                        }
$content .= '
                    </select>
                </div>

                <div id="orderSelect" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Order</label>
                    <select id="orderId" name="order_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select an order...</option>';
                        foreach ($orders as $o) {
                            $content .= '<option value="' . $o['id'] . '">' . htmlspecialchars($o['order_number']) . ' - ' . htmlspecialchars($o['customer_name']) . '</option>';
                        }
$content .= '
                    </select>
                </div>

                <div id="customLinkOptions" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or Enter Custom URL</label>
                        <input type="url" id="customUrl" name="custom_url" placeholder="https://example.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <p class="text-xs text-gray-500 mt-1">Manually enter any URL</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or Select a Page</label>
                        <select id="pageUrl" name="page_url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Select a page...</option>
                            <option value="contact/">Contact Page</option>
                            <option value="shop/">Shop</option>
                            <option value="user/cart/">Cart</option>
                            <option value="">Homepage</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or Select a Product</label>
                        <select id="productIdCustom" name="product_id_custom" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Select a product...</option>';
                        foreach ($products as $p) {
                            $content .= '<option value="' . $p['id'] . '">' . htmlspecialchars($p['name']) . '</option>';
                        }
$content .= '
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label (Optional)</label>
                    <input type="text" name="label" placeholder="e.g., Store display, Batch #123" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeGenerateModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-qrcode mr-2"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openGenerateModal() {
    document.getElementById("generateModal").classList.remove("hidden");
}

function closeGenerateModal() {
    document.getElementById("generateModal").classList.add("hidden");
    document.getElementById("generateForm").reset();
    updateReferenceOptions();
}

function updateReferenceOptions() {
    const type = document.getElementById("qrType").value;
    document.getElementById("productSelect").classList.add("hidden");
    document.getElementById("orderSelect").classList.add("hidden");
    document.getElementById("customLinkOptions").classList.add("hidden");

    if (type === "product") {
        document.getElementById("productSelect").classList.remove("hidden");
    } else if (type === "invoice") {
        document.getElementById("orderSelect").classList.remove("hidden");
    } else if (type === "custom_link") {
        document.getElementById("customLinkOptions").classList.remove("hidden");
    }
}

function toggleStatus(qrCodeId) {
    if (confirm("Are you sure you want to toggle the status of this QR code?")) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = \'<input type="hidden" name="action" value="toggle_status"><input type="hidden" name="qr_code_id" value="\' + qrCodeId + \'">\';
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteQRCode(qrCodeId, name) {
    if (confirm("Are you sure you want to delete the QR code for " + name + "?")) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = \'<input type="hidden" name="action" value="delete"><input type="hidden" name="qr_code_id" value="\' + qrCodeId + \'">\';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('QR Codes', $content, 'qr-codes');
