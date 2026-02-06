<?php
// Product Search API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/order_service.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Input validation - limit search term length and require minimum length
$searchTerm = $_GET['q'] ?? '';
$searchTerm = trim($searchTerm);
$searchTerm = substr($searchTerm, 0, 100); // Limit to 100 characters

if (strlen($searchTerm) < 2) {
    echo json_encode([
        'success' => true,
        'products' => []
    ]);
    exit;
}

$orderService = new OrderService($db);
$products = $orderService->searchProducts($searchTerm, 20, 0);

echo json_encode([
    'success' => true,
    'products' => $products
]);
