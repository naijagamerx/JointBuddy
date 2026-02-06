<?php
/**
 * Coupons Service
 * Provides functions for managing and validating coupons
 */

/**
 * Get all available coupons for a user
 *
 * @param PDO $db Database connection
 * @param int|null $userId User ID (optional)
 * @return array Array of available coupons
 */
function getAvailableCoupons(PDO $db, ?int $userId = null): array
{
    try {
        $now = date('Y-m-d H:i:s');
        $sql = "
            SELECT c.*,
                   CASE
                       WHEN c.usage_limit IS NOT NULL AND cu.usage_count >= c.usage_limit THEN 0
                       WHEN c.usage_per_user IS NOT NULL AND cuu.user_usage_count >= c.usage_per_user THEN 0
                       ELSE 1
                   END as is_available
            FROM coupons c
            LEFT JOIN (
                SELECT coupon_id, COUNT(*) as usage_count
                FROM coupon_usages
                GROUP BY coupon_id
            ) cu ON c.id = cu.coupon_id
            LEFT JOIN (
                SELECT coupon_id, user_id, COUNT(*) as user_usage_count
                FROM coupon_usages
                WHERE user_id IS NOT NULL
                GROUP BY coupon_id, user_id
            ) cuu ON c.id = cuu.coupon_id AND cuu.user_id = ?
            WHERE c.active = 1
            AND (c.starts_at IS NULL OR c.starts_at <= ?)
            AND (c.expires_at IS NULL OR c.expires_at >= ?)
            ORDER BY c.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $now, $now]);

        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter out coupons that have reached usage limits
        $availableCoupons = [];
        foreach ($coupons as $coupon) {
            if ($coupon['is_available']) {
                $availableCoupons[] = $coupon;
            }
        }

        return $availableCoupons;
    } catch (Exception $e) {
        error_log("Error fetching available coupons: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a coupon by its code
 *
 * @param PDO $db Database connection
 * @param string $code Coupon code
 * @return array|null Coupon data or null if not found
 */
function getCouponByCode(PDO $db, string $code): ?array
{
    try {
        $stmt = $db->prepare("
            SELECT * FROM coupons
            WHERE code = ? AND active = 1
            LIMIT 1
        ");
        $stmt->execute([strtoupper(trim($code))]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        return $coupon ?: null;
    } catch (Exception $e) {
        error_log("Error fetching coupon by code: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate a coupon for use
 *
 * @param PDO $db Database connection
 * @param array $coupon Coupon data
 * @param float $subtotal Order subtotal
 * @param int|null $userId User ID (optional)
 * @return array Validation result with 'valid' boolean, 'message' string, and 'discount' float
 */
function validateCoupon(PDO $db, array $coupon, float $subtotal, ?int $userId = null): array
{
    try {
        $code = $coupon['code'] ?? '';
        $now = date('Y-m-d H:i:s');

        // Check if coupon exists and is active
        if (empty($coupon) || $coupon['active'] != 1) {
            return [
                'valid' => false,
                'message' => 'This coupon code is not valid or has been deactivated',
                'discount' => 0
            ];
        }

        // Check start date
        if ($coupon['starts_at'] && $coupon['starts_at'] > $now) {
            return [
                'valid' => false,
                'message' => 'This coupon is not yet active',
                'discount' => 0
            ];
        }

        // Check expiry date
        if ($coupon['expires_at'] && $coupon['expires_at'] < $now) {
            return [
                'valid' => false,
                'message' => 'This coupon has expired',
                'discount' => 0
            ];
        }

        // Check minimum order amount
        if ($coupon['min_order_amount'] && $subtotal < $coupon['min_order_amount']) {
            return [
                'valid' => false,
                'message' => 'This coupon requires a minimum order of R' . number_format($coupon['min_order_amount'], 2),
                'discount' => 0
            ];
        }

        // Check total usage limit
        if ($coupon['usage_limit']) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM coupon_usages WHERE coupon_id = ?");
            $stmt->execute([$coupon['id']]);
            $usageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($usageCount >= $coupon['usage_limit']) {
                return [
                    'valid' => false,
                    'message' => 'This coupon has reached its usage limit',
                    'discount' => 0
                ];
            }
        }

        // Check per-user usage limit
        if ($coupon['usage_per_user'] && $userId) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM coupon_usages
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$coupon['id'], $userId]);
            $userUsageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($userUsageCount >= $coupon['usage_per_user']) {
                return [
                    'valid' => false,
                    'message' => 'You have already used this coupon the maximum number of times',
                    'discount' => 0
                ];
            }
        }

        // Calculate discount
        $discount = calculateDiscount($coupon, $subtotal);

        return [
            'valid' => true,
            'message' => 'Coupon is valid',
            'discount' => $discount
        ];
    } catch (Exception $e) {
        error_log("Error validating coupon: " . $e->getMessage());
        return [
            'valid' => false,
            'message' => 'Error validating coupon',
            'discount' => 0
        ];
    }
}

/**
 * Calculate discount amount for a coupon
 *
 * @param array $coupon Coupon data
 * @param float $subtotal Order subtotal
 * @param float $shipping Shipping cost (optional, default 0)
 * @return float Discount amount
 */
function calculateDiscount(array $coupon, float $subtotal, float $shipping = 0): float
{
    $discountType = $coupon['discount_type'] ?? 'fixed';
    $discountValue = floatval($coupon['discount_value'] ?? 0);
    $maxDiscount = $coupon['max_discount_amount'] ? floatval($coupon['max_discount_amount']) : null;

    $discount = 0;

    switch ($discountType) {
        case 'percent':
            $discount = ($subtotal * $discountValue) / 100;
            break;

        case 'fixed':
            $discount = $discountValue;
            break;

        case 'free_shipping':
            $discount = $shipping;
            break;

        default:
            $discount = 0;
    }

    // Apply max discount cap if set
    if ($maxDiscount !== null && $discount > $maxDiscount) {
        $discount = $maxDiscount;
    }

    // Ensure discount doesn't exceed subtotal
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    return round($discount, 2);
}

/**
 * Record coupon usage
 *
 * @param PDO $db Database connection
 * @param int $couponId Coupon ID
 * @param int|null $userId User ID (optional, can be null for guest orders)
 * @param int|null $orderId Order ID (optional)
 * @return bool True on success, false on failure
 */
function applyCoupon(PDO $db, int $couponId, ?int $userId, ?int $orderId): bool
{
    try {
        $stmt = $db->prepare("
            INSERT INTO coupon_usages (coupon_id, user_id, order_id, used_at)
            VALUES (?, ?, ?, NOW())
        ");

        return $stmt->execute([$couponId, $userId, $orderId]);
    } catch (Exception $e) {
        error_log("Error applying coupon: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's coupon usage history
 *
 * @param PDO $db Database connection
 * @param int $couponId Coupon ID
 * @param int $userId User ID
 * @return int Number of times user has used this coupon
 */
function getUserCouponUsage(PDO $db, int $couponId, int $userId): int
{
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM coupon_usages
            WHERE coupon_id = ? AND user_id = ?
        ");
        $stmt->execute([$couponId, $userId]);
        return intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);
    } catch (Exception $e) {
        error_log("Error getting user coupon usage: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get recently used coupons for a user
 *
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param int $limit Number of coupons to return (default 10)
 * @return array Array of recently used coupons
 */
function getRecentlyUsedCoupons(PDO $db, int $userId, int $limit = 10): array
{
    try {
        $stmt = $db->prepare("
            SELECT c.code, c.description, c.discount_type, c.discount_value,
                   cu.used_at,
                   o.total_amount as order_total,
                   (o.subtotal + o.shipping_amount - o.coupon_discount) as final_total
            FROM coupon_usages cu
            INNER JOIN coupons c ON cu.coupon_id = c.id
            LEFT JOIN orders o ON cu.order_id = o.id
            WHERE cu.user_id = ?
            ORDER BY cu.used_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching recently used coupons: " . $e->getMessage());
        return [];
    }
}

/**
 * Get coupon statistics
 *
 * @param PDO $db Database connection
 * @param int $couponId Coupon ID
 * @return array Statistics including total uses, user uses, etc.
 */
function getCouponStats(PDO $db, int $couponId): array
{
    try {
        // Total uses
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM coupon_usages WHERE coupon_id = ?");
        $stmt->execute([$couponId]);
        $totalUses = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Unique users
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT user_id) as count
            FROM coupon_usages
            WHERE coupon_id = ? AND user_id IS NOT NULL
        ");
        $stmt->execute([$couponId]);
        $uniqueUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Last used date
        $stmt = $db->prepare("
            SELECT MAX(used_at) as last_used
            FROM coupon_usages
            WHERE coupon_id = ?
        ");
        $stmt->execute([$couponId]);
        $lastUsed = $stmt->fetch(PDO::FETCH_ASSOC)['last_used'];

        return [
            'total_uses' => $totalUses,
            'unique_users' => $uniqueUsers,
            'last_used' => $lastUsed
        ];
    } catch (Exception $e) {
        error_log("Error getting coupon stats: " . $e->getMessage());
        return [
            'total_uses' => 0,
            'unique_users' => 0,
            'last_used' => null
        ];
    }
}

/**
 * Format discount value for display
 *
 * @param array $coupon Coupon data
 * @return string Formatted discount string
 */
function formatDiscount(array $coupon): string
{
    $type = $coupon['discount_type'] ?? 'fixed';
    $value = floatval($coupon['discount_value'] ?? 0);

    switch ($type) {
        case 'percent':
            return $value . '% off';
        case 'fixed':
            return 'R' . number_format($value, 2) . ' off';
        case 'free_shipping':
            return 'Free Shipping';
        default:
            return 'R' . number_format($value, 2) . ' off';
    }
}

/**
 * Check if coupon can be applied to current cart
 *
 * @param PDO $db Database connection
 * @param array $coupon Coupon data
 * @param float $subtotal Order subtotal
 * @return bool True if coupon can be applied
 */
function canApplyCoupon(PDO $db, array $coupon, float $subtotal): bool
{
    $validation = validateCoupon($db, $coupon, $subtotal);
    return $validation['valid'];
}
