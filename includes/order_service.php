<?php
/**
 * Order Service
 * Handles order creation and management operations
 */
class OrderService {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Generate unique order number
     * Format: ORD-YYYY-XXXXXXXX
     */
    public function generateOrderNumber() {
        return 'ORD-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Search for existing customer by email or phone
     */
    public function searchCustomer($search) {
        $stmt = $this->db->prepare("
            SELECT id, email, first_name, last_name, phone
            FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");
        $stmt->execute([$search, $search]);
        return $stmt->fetch();
    }

    /**
     * Get products for search (with pagination)
     */
    public function searchProducts($searchTerm = '', $limit = 20, $offset = 0) {
        if (!empty($searchTerm)) {
            $stmt = $this->db->prepare("
                SELECT id, name, slug, sku, price, compare_price, sale_price, on_sale, stock, images
                FROM products
                WHERE active = 1 AND (name LIKE ? OR sku LIKE ?)
                ORDER BY name ASC
                LIMIT ? OFFSET ?
            ");
            $param = "%$searchTerm%";
            $stmt->execute([$param, $param, $limit, $offset]);
        } else {
            $stmt = $this->db->prepare("
                SELECT id, name, slug, sku, price, compare_price, sale_price, on_sale, stock, images
                FROM products
                WHERE active = 1
                ORDER BY name ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get product by ID with full details
     */
    public function getProduct($productId) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category = c.name
            WHERE p.id = ? AND p.active = 1
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }

    /**
     * Get delivery methods
     */
    public function getDeliveryMethods() {
        $stmt = $this->db->query("SELECT * FROM delivery_methods WHERE is_active = 1 ORDER BY display_order ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods() {
        $stmt = $this->db->query("SELECT id, name, manual_type FROM payment_methods WHERE is_active = 1 AND is_manual = 1 ORDER BY display_order ASC");
        $methods = $stmt->fetchAll();

        // Map manual_type to default payment status
        $result = [];
        foreach ($methods as $method) {
            $defaultStatus = 'pending';
            if ($method['manual_type'] === 'cash' || $method['manual_type'] === 'card') {
                $defaultStatus = 'paid';
            }
            $result[] = [
                'id' => $method['id'],
                'name' => $method['name'],
                'default_status' => $defaultStatus
            ];
        }
        return $result;
    }

    /**
     * Create manual order
     */
    public function createManualOrder($data) {
        try {
            $this->db->beginTransaction();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Check if customer exists
            $customer = $this->searchCustomer($data['customer_email']);
            $userId = $customer ? $customer['id'] : null;

            // Prepare addresses as JSON
            $shippingAddress = json_encode([
                'name' => $data['shipping_name'],
                'street' => $data['shipping_street'],
                'city' => $data['shipping_city'],
                'state' => $data['shipping_state'],
                'postal_code' => $data['shipping_postal_code'],
                'phone' => $data['customer_phone'] ?? ''
            ]);

            $billingAddress = $data['same_as_billing']
                ? $shippingAddress
                : json_encode([
                    'name' => $data['billing_name'],
                    'street' => $data['billing_street'],
                    'city' => $data['billing_city'],
                    'state' => $data['billing_state'],
                    'postal_code' => $data['billing_postal_code']
                ]);

            // Validate items and get product details BEFORE inserting order (fix N+1 query problem)
            $productIds = array_column($data['items'], 'product_id');
            if (empty($productIds)) {
                throw new Exception('No products in order');
            }

            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $productStmt = $this->db->prepare("
                SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category = c.name
                WHERE p.id IN ($placeholders) AND p.active = 1
            ");
            $productStmt->execute($productIds);
            $products = [];
            while ($row = $productStmt->fetch(PDO::FETCH_ASSOC)) {
                $products[$row['id']] = $row;
            }

            // Validate items, calculate validated subtotal, and prepare for stock update
            $subtotal = 0;
            $validatedItems = [];
            $stockUpdates = [];

            foreach ($data['items'] as $item) {
                $productId = $item['product_id'];
                if (!isset($products[$productId])) {
                    throw new Exception("Product not found or inactive: ID $productId");
                }

                $product = $products[$productId];

                // Stock validation
                if ($item['quantity'] > $product['stock']) {
                    throw new Exception("Insufficient stock for product: {$product['name']}. Available: {$product['stock']}, Requested: {$item['quantity']}");
                }

                // Get current price from database
                $currentPrice = $product['sale_price'] ?? $product['price'];

                // Price validation - log if override detected
                if (abs($item['unit_price'] - $currentPrice) > 0.01) {
                    error_log("Price override detected for product {$product['name']}: DB price {$currentPrice}, submitted price {$item['unit_price']} by admin");
                    // Use submitted price but log it
                    $unitPrice = $item['unit_price'];
                } else {
                    $unitPrice = $currentPrice;
                }

                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal
                ];

                $stockUpdates[] = [
                    'product_id' => $productId,
                    'quantity' => $item['quantity']
                ];
            }

            // Get delivery cost
            $shippingCost = 0;
            if (!empty($data['delivery_method_id'])) {
                $stmt = $this->db->prepare("SELECT cost FROM delivery_methods WHERE id = ?");
                $stmt->execute([$data['delivery_method_id']]);
                $delivery = $stmt->fetch();
                $shippingCost = $delivery ? $delivery['cost'] : 0;
            }

            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $shippingCost - $discountAmount;

            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_email, customer_phone,
                    shipping_address, billing_address,
                    delivery_method_id, payment_method, payment_status,
                    subtotal, shipping_amount, discount_amount, total_amount,
                    status, notes, order_source
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $orderNumber,
                $userId,
                $data['customer_first_name'] . ' ' . $data['customer_last_name'],
                $data['customer_email'],
                $data['customer_phone'] ?? null,
                $shippingAddress,
                $billingAddress,
                $data['delivery_method_id'] ?? null,
                $data['payment_method'],
                $data['payment_status'],
                $subtotal,
                $shippingCost,
                $discountAmount,
                $totalAmount,
                'pending',
                $data['notes'] ?? null,
                'admin'
            ]);

            $orderId = $this->db->lastInsertId();

            // Insert order items
            $stmt = $this->db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, product_sku,
                    variation_id, variation_name, quantity, unit_price, total_price
                ) VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, ?)
            ");

            foreach ($validatedItems as $validatedItem) {
                $product = $validatedItem['product'];

                $stmt->execute([
                    $orderId,
                    $product['id'],
                    $product['name'],
                    $product['sku'] ?? '',
                    $validatedItem['quantity'],
                    $validatedItem['unit_price'],
                    $validatedItem['line_total']
                ]);

                // Update stock
                $updateStockStmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $updateStockStmt->execute([$validatedItem['quantity'], $product['id'], $validatedItem['quantity']]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get order by ID
     */
    public function getOrder($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }

    /**
     * Get order items for an order
     */
    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images,
                   p.sku as product_sku, p.weight as product_weight, p.brand as product_brand
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}
