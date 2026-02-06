<?php
/**
 * Wishlist Handler
 * Handles: Add/remove from wishlist
 *
 * @package CannaBuddy
 */
class WishlistHandler implements HandlerInterface {

    /**
     * Check if this handler can process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data
     * @return bool True if handler can process this route
     */
    public function canHandle(string $route, array $request): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST' &&
               str_starts_with($route, 'wishlist/');
    }

    /**
     * Process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data
     * @return void
     * @throws HandlerException If processing fails
     */
    public function handle(string $route, array $request): void {
        $currentUser = AuthMiddleware::getCurrentUser();

        if (!$currentUser) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Please login to use wishlist'
            ]);
            return;
        }

        if ($route === 'wishlist/add' && isset($request['product_id'])) {
            $this->handleAdd($currentUser, (int)$request['product_id']);
        } elseif ($route === 'wishlist/remove' && isset($request['product_id'])) {
            $this->handleRemove($currentUser, (int)$request['product_id']);
        }
    }

    /**
     * Add product to wishlist
     *
     * @param array $currentUser Current user data
     * @param int $productId Product ID
     * @return void
     */
    private function handleAdd(array $currentUser, int $productId): void {
        try {
            $db = Services::db();
            $userId = $currentUser['id'];

            // Check for duplicate
            $stmt = $db->prepare(
                "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?"
            );
            $stmt->execute([$userId, $productId]);

            if ($stmt->fetch()) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Already in wishlist'
                ]);
                return;
            }

            // Insert new
            $stmt = $db->prepare(
                "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())"
            );
            $stmt->execute([$userId, $productId]);

            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Added to wishlist'
            ]);

        } catch (Exception $e) {
            error_log("Wishlist add error: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Database error'
            ]);
        }
    }

    /**
     * Remove product from wishlist
     *
     * @param array $currentUser Current user data
     * @param int $productId Product ID
     * @return void
     */
    private function handleRemove(array $currentUser, int $productId): void {
        try {
            $db = Services::db();
            $stmt = $db->prepare(
                "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?"
            );
            $stmt->execute([$currentUser['id'], $productId]);

            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Removed from wishlist'
            ]);
        } catch (Exception $e) {
            error_log("Wishlist remove error: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Database error'
            ]);
        }
    }

    /**
     * Send JSON response and exit
     *
     * @param array $data Response data
     * @return void
     */
    private function sendJsonResponse(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
