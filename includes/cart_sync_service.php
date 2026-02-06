<?php
require_once __DIR__ . '/database.php';

function syncCartToDatabase(PDO $db, int $userId) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return ['success' => true, 'message' => 'Cart is empty'];
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO user_carts (user_id, product_id, quantity, variation) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                quantity = VALUES(quantity),
                updated_at = CURRENT_TIMESTAMP");

        foreach ($_SESSION['cart'] as $item) {
            $productId = $item['product_id'] ?? 0;
            $quantity = $item['qty'] ?? $item['quantity'] ?? 1;
            $variation = $item['variation'] ?? null;

            if ($productId > 0) {
                $stmt->execute([$userId, $productId, $quantity, $variation]);
            }
        }

        $db->commit();
        return ['success' => true, 'message' => 'Cart synced successfully'];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Cart sync error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to sync cart: ' . $e->getMessage()];
    }
}

function loadCartFromDatabase(PDO $db, int $userId) {
    try {
        $stmt = $db->prepare("
            SELECT uc.*, p.name as product_name, p.price, p.stock, p.images as product_images,
                   p.slug as product_slug, p.category_id
            FROM user_carts uc
            JOIN products p ON uc.product_id = p.id
            WHERE uc.user_id = ?
            ORDER BY uc.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Load cart error: " . $e->getMessage());
        return [];
    }
}

function clearCartFromDatabase(PDO $db, int $userId) {
    try {
        $stmt = $db->prepare("DELETE FROM user_carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        return ['success' => true, 'message' => 'Cart cleared'];
    } catch (Exception $e) {
        error_log("Clear cart error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to clear cart'];
    }
}

function mergeSessionAndDatabaseCart(PDO $db, int $userId) {
    $dbCart = loadCartFromDatabase($db, $userId);
    $sessionCart = $_SESSION['cart'] ?? [];

    $mergedCart = [];
    $cartIndex = 0;

    $processedProducts = [];

    foreach ($dbCart as $dbItem) {
        $productId = $dbItem['product_id'];
        $variation = $dbItem['variation'] ?? '';
        $productKey = $productId . '|' . $variation;

        $processedProducts[$productKey] = true;

        $mergedCart[$cartIndex] = [
            'product_id' => $productId,
            'qty' => $dbItem['quantity'],
            'quantity' => $dbItem['quantity'],
            'variation' => $dbItem['variation'],
            'price' => $dbItem['price'],
            'max_stock' => $dbItem['stock'],
            'name' => $dbItem['product_name'],
            'images' => $dbItem['product_images'],
            'slug' => $dbItem['product_slug']
        ];
        $cartIndex++;
    }

    foreach ($sessionCart as $sessionItem) {
        $productId = $sessionItem['product_id'] ?? 0;
        $variation = $sessionItem['variation'] ?? '';
        $productKey = $productId . '|' . $variation;

        if (!isset($processedProducts[$productKey]) && $productId > 0) {
            $mergedCart[$cartIndex] = $sessionItem;
            $cartIndex++;
        }
    }

    $_SESSION['cart'] = $mergedCart;
    return $mergedCart;
}

function syncAndLoadCart(PDO $db, int $userId) {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        syncCartToDatabase($db, $userId);
    }
    return loadCartFromDatabase($db, $userId);
}
