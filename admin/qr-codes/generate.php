<?php
// QR Code Generation Handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/url_helper.php';
require_once __DIR__ . '/../../includes/qr_code_service.php';

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

$adminId = $adminAuth->getAdminId();
$qrService = new QRCodeService($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $qrType = $_POST['qr_type'] ?? '';
    $label = trim($_POST['label'] ?? '');

    if ($qrType === 'product') {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            $_SESSION['error'] = 'Please select a product.';
            redirect('/admin/qr-codes/');
        }

        $result = $qrService->generateProductQRCode($productId, $adminId, $label ?: null);

        if ($result['success']) {
            $_SESSION['success'] = 'Product QR code generated successfully!';
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Failed to generate QR code.';
        }

    } elseif ($qrType === 'invoice') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            $_SESSION['error'] = 'Please select an order.';
            redirect('/admin/qr-codes/');
        }

        $result = $qrService->generateInvoiceQRCode($orderId, $adminId, $label ?: null);

        if ($result['success']) {
            $_SESSION['success'] = 'Invoice QR code generated successfully!';
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Failed to generate QR code.';
        }

    } elseif ($qrType === 'custom_link') {
        $customUrl = trim($_POST['custom_url'] ?? '');
        $selectedPage = $_POST['page_url'] ?? '';
        $selectedProduct = (int)($_POST['product_id_custom'] ?? 0);

        // Determine the final URL
        $finalUrl = '';
        if (!empty($customUrl)) {
            // Manual URL takes priority
            $finalUrl = $customUrl;
        } elseif (!empty($selectedPage)) {
            // Selected page from dropdown
            $finalUrl = url($selectedPage);
        } elseif ($selectedProduct > 0) {
            // Selected product
            $stmt = $db->prepare("SELECT slug FROM products WHERE id = ?");
            $stmt->execute([$selectedProduct]);
            $product = $stmt->fetch();
            if ($product) {
                $finalUrl = productUrl($product['slug']);
            }
        }

        if (empty($finalUrl)) {
            $_SESSION['error'] = 'Please enter a URL or select a page/product.';
            redirect('/admin/qr-codes/');
        }

        $result = $qrService->generateCustomLinkQRCode($finalUrl, $adminId, $label ?: null);

        if ($result['success']) {
            $_SESSION['success'] = 'Custom link QR code generated successfully!';
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Failed to generate QR code.';
        }

    } else {
        $_SESSION['error'] = 'Please select a QR code type.';
    }

    redirect('/admin/qr-codes/');
}

redirect('/admin/qr-codes/');
