<?php
/**
 * Add to Cart Handler - CannaBuddy
 * Handles adding products to the session-based cart
 */
session_start();
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/product_helpers.php';
require_once __DIR__ . '/../includes/cart_sync_service.php';

header('Content-Type: application/json');

// Include database
require_once __DIR__ . '/../includes/database.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get request data
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $buyNow = isset($_POST['buy_now']) && $_POST['buy_now'] == '1';
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '';
    
    // Validate
    if ($productId <= 0) {
        throw new Exception('Invalid product ID');
    }
    
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch product
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Check stock
    $stock = $product['stock'] ?? 0;
    if ($stock <= 0) {
        throw new Exception('Product is out of stock');
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Determine price (sale or regular)
    $price = $product['price'];
    if (!empty($product['on_sale']) && !empty($product['sale_price'])) {
        $price = $product['sale_price'];
    }
    
    // Check if product already in cart
    $existingKey = null;
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $productId) {
            $existingKey = $key;
            break;
        }
    }
    
    if ($existingKey !== null) {
        // Update quantity
        $newQty = $_SESSION['cart'][$existingKey]['qty'] + $quantity;
        
        // Check stock limit
        if ($newQty > $stock) {
            $newQty = $stock;
            $response['message'] = 'Quantity limited to available stock';
        }
        
        $_SESSION['cart'][$existingKey]['qty'] = $newQty;
    } else {
        // Add new item
        if ($quantity > $stock) {
            $quantity = $stock;
        }
        
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'price' => $price,
            'original_price' => $product['price'],
            'qty' => $quantity,
            'image' => getProductMainImage($product),
            'max_stock' => $stock
        ];
    }
    
    // Sync cart to database for logged-in users
    if (isset($_SESSION['user_id']) && $db) {
        syncCartToDatabase($db, $_SESSION['user_id']);
    }
    
    // Calculate cart count
    $cartCount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
    
    $response['success'] = true;
    $response['message'] = $response['message'] ?: 'Product added to cart!';
    $response['cartCount'] = $cartCount;
    $response['cart'] = $_SESSION['cart'];
    
    if ($buyNow) {
        $response['redirect'] = url('/checkout/');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
