<?php
// QR Code Scan History - Admin Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/url_helper.php';
require_once __DIR__ . '/../../includes/qr_code_service.php';
require_once __DIR__ . '/../../admin_sidebar_components.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

$qrService = new QRCodeService($db);

// Get QR code ID from URL
$qrCodeId = (int)($_GET['id'] ?? 0);

if ($qrCodeId <= 0) {
    $_SESSION['error'] = 'Invalid QR code ID.';
    redirect('/admin/qr-codes/');
}

// Get QR code details
$qrCode = $qrService->getQRCodeById($qrCodeId);

if (!$qrCode) {
    $_SESSION['error'] = 'QR code not found.';
    redirect('/admin/qr-codes/');
}

// Get scan history
$scans = $qrService->getQRScanHistory($qrCodeId);

// Get reference details
$referenceDetails = '';
if ($qrCode['qr_code_type'] === 'product') {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$qrCode['reference_id']]);
    $product = $stmt->fetch();
    if ($product) {
        // Get product image
        $productImage = '';
        if (!empty($product['images'])) {
            $imageUrls = explode(',', $product['images']);
            $productImage = trim($imageUrls[0]);
        }

        $referenceDetails = '
            <div class="flex items-start space-x-4">
                ' . ($productImage ? '<img src="' . htmlspecialchars($productImage) . '" alt="' . htmlspecialchars($product['name']) . '" class="w-20 h-20 object-cover rounded-lg border border-gray-200">' : '<div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center"><i class="fas fa-box text-gray-400 text-2xl"></i></div>') . '
                <div class="text-sm text-gray-600">
                    <p><strong>Product:</strong> ' . htmlspecialchars($product['name']) . '</p>
                    <p><strong>SKU:</strong> ' . htmlspecialchars($product['sku'] ?? 'N/A') . '</p>
                    <p><strong>Price:</strong> R' . number_format($product['price'], 2) . '</p>
                </div>
            </div>
        ';
    }
} else {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$qrCode['reference_id']]);
    $order = $stmt->fetch();
    if ($order) {
        $referenceDetails = '
            <div class="text-sm text-gray-600">
                <p><strong>Order:</strong> ' . htmlspecialchars($order['order_number']) . '</p>
                <p><strong>Customer:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($order['customer_email']) . '</p>
            </div>
        ';
    }
}

// Generate content
$typeIcon = $qrCode['qr_code_type'] === 'product' ? 'fa-box text-green-600' : 'fa-file-invoice text-purple-600';
$typeLabel = $qrCode['qr_code_type'] === 'product' ? 'Product' : 'Invoice';
$statusBadge = $qrCode['is_active']
    ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>'
    : '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Inactive</span>';

$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="' . adminUrl('/qr-codes/') . '" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Back to QR Codes
        </a>
    </div>

    <!-- QR Code Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex items-start">
                ' . ($qrCode['qr_code_image_path'] ? '<img src="' . htmlspecialchars($qrCode['qr_code_image_path']) . '" alt="QR Code" class="w-32 h-32 border border-gray-200 rounded-lg mr-6">' : '') . '
                <div>
                    <div class="flex items-center mb-2">
                        <i class="fas ' . $typeIcon . ' mr-2"></i>
                        <span class="text-lg font-bold text-gray-900">' . $typeLabel . ' QR Code</span>
                        ' . $statusBadge . '
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        <strong>Reference:</strong> ' . htmlspecialchars($qrCode['reference_name'] ?? 'N/A') . '<br>
                        <strong>Label:</strong> ' . htmlspecialchars($qrCode['qr_code_label'] ?? '-') . '<br>
                        <strong>Unique ID:</strong> <code class="bg-gray-100 px-2 py-1 rounded text-xs">' . htmlspecialchars($qrCode['qr_code_unique_id']) . '</code>
                    </p>
                    ' . $referenceDetails . '
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Created by</p>
                <p class="font-medium text-gray-900">' . htmlspecialchars($qrCode['created_by_name'] ?? 'Admin') . '</p>
                <p class="text-sm text-gray-500 mt-2">Created</p>
                <p class="font-medium text-gray-900">' . date('M j, Y g:i A', strtotime($qrCode['created_at'])) . '</p>
            </div>
        </div>
    </div>

    <!-- Scan Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-blue-100 text-blue-600 mr-3">
                    <i class="fas fa-eye"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total Scans</p>
                    <p class="text-xl font-bold text-gray-900">' . count($scans) . '</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-green-100 text-green-600 mr-3">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Contact Form Submissions</p>
                    <p class="text-xl font-bold text-gray-900">' . count(array_filter($scans, fn($s) => $s['contact_form_submitted'])) . '</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-yellow-100 text-yellow-600 mr-3">
                    <i class="fas fa-percentage"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Conversion Rate</p>
                    <p class="text-xl font-bold text-gray-900">' . (count($scans) > 0 ? round((count(array_filter($scans, fn($s) => $s['contact_form_submitted'])) / count($scans)) * 100, 1) : 0) . '%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Scan History</h2>
            <p class="text-sm text-gray-600">All scans of this QR code</p>
        </div>';

if (empty($scans)) {
    $content .= '
        <div class="p-12 text-center">
            <i class="fas fa-qrcode text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No scans yet</h3>
            <p class="text-gray-600">This QR code hasn\'t been scanned yet.</p>
        </div>';
} else {
    $content .= '
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scanned At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Form</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    $counter = 1;
    foreach ($scans as $scan) {
        $contactStatus = $scan['contact_form_submitted']
            ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Submitted</span>' .
              ($scan['contact_subject'] ? '<br><span class="text-xs text-gray-500">"' . htmlspecialchars($scan['contact_subject']) . '"</span>' : '')
            : '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">No</span>';

        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">' . $counter++ . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . date('M j, Y g:i A', strtotime($scan['scanned_at'])) . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">' . htmlspecialchars($scan['scanned_from_ip'] ?? '-') . '</td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">' . htmlspecialchars($scan['scanned_from_user_agent'] ?? '-') . '</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">' . $contactStatus . '</td>
                    </tr>';
    }

    $content .= '
                </tbody>
            </table>
        </div>';
}

$content .= '
    </div>
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('QR Code Scan History', $content, 'qr-codes');
