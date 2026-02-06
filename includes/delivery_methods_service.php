<?php
/**
 * Delivery Methods Service
 * Provides functions for managing delivery methods
 */

/**
 * Get all active delivery methods
 *
 * @param PDO $db Database connection
 * @return array Array of delivery methods sorted by cost
 */
function ensureDeliveryMethodsFreeShippingSchema(PDO $db): void
{
    try {
        $stmt = $db->query("SHOW COLUMNS FROM delivery_methods LIKE 'free_shipping_min_amount'");
        $hasColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasColumn) {
            $db->exec("ALTER TABLE delivery_methods ADD COLUMN free_shipping_min_amount DECIMAL(10,2) DEFAULT NULL AFTER cost");
        }
    } catch (Exception $e) {
        error_log("Error ensuring delivery methods schema: " . $e->getMessage());
    }
}

function getActiveDeliveryMethods(PDO $db): array
{
    try {
        ensureDeliveryMethodsFreeShippingSchema($db);

        $stmt = $db->prepare("
            SELECT * FROM delivery_methods
            WHERE is_active = 1
            ORDER BY display_order ASC, cost ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching delivery methods: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a delivery method by ID
 *
 * @param PDO $db Database connection
 * @param int $id Delivery method ID
 * @return array|null Delivery method data or null if not found
 */
function getDeliveryMethodById(PDO $db, int $id): ?array
{
    try {
        $stmt = $db->prepare("
            SELECT * FROM delivery_methods
            WHERE id = ? AND active = 1
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $method = $stmt->fetch(PDO::FETCH_ASSOC);
        return $method ?: null;
    } catch (Exception $e) {
        error_log("Error fetching delivery method by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get delivery method cost
 *
 * @param array $method Delivery method data
 * @param bool $qualifiesForFreeShipping Whether order qualifies for free shipping
 * @return float Delivery cost
 */
function getEffectiveDeliveryCost(array $method, float $orderSubtotal): float
{
    $baseCost = floatval($method['cost'] ?? 0);

    if (!empty($method['free_shipping_min_amount'])) {
        $threshold = floatval($method['free_shipping_min_amount']);
        if ($threshold > 0 && $orderSubtotal >= $threshold) {
            return 0.0;
        }
    }

    return $baseCost;
}

/**
 * Format delivery method for display
 *
 * @param array $method Delivery method data
 * @param bool $showCost Whether to show cost
 * @param bool $qualifiesForFreeShipping Whether order qualifies for free shipping
 * @return string Formatted delivery method string
 */
function formatDeliveryMethod(array $method, bool $showCost = true, bool $qualifiesForFreeShipping = false): string
{
    $name = $method['name'] ?? 'Unknown';
    $description = $method['description'] ?? '';
    $estimatedTime = $method['estimated_delivery_time'] ?? '';
    $cost = $qualifiesForFreeShipping ? 0.0 : floatval($method['cost'] ?? 0);

    $formatted = $name;

    if ($description) {
        $formatted .= ' - ' . $description;
    }

    if ($estimatedTime) {
        $formatted .= ' (' . $estimatedTime . ')';
    }

    if ($showCost) {
        if ($qualifiesForFreeShipping && $cost == 0) {
            $formatted .= ' - FREE';
        } else {
            $formatted .= ' - R' . number_format($cost, 2);
        }
    }

    return $formatted;
}

/**
 * Get delivery method display text for checkout
 *
 * @param array $method Delivery method data
 * @param bool $qualifiesForFreeShipping Whether order qualifies for free shipping
 * @return array Display data with title, subtitle, and cost
 */
function getDeliveryDisplayData(array $method, bool $qualifiesForFreeShipping = false): array
{
    $name = $method['name'] ?? 'Unknown';
    $description = $method['description'] ?? '';
    $estimatedTime = $method['estimated_delivery_time'] ?? '';
    $cost = $qualifiesForFreeShipping ? 0.0 : floatval($method['cost'] ?? 0);

    $title = $name;
    $subtitle = [];

    if ($description) {
        $subtitle[] = $description;
    }

    if ($estimatedTime) {
        $subtitle[] = $estimatedTime;
    }

    $costText = '';
    if ($qualifiesForFreeShipping && $cost == 0) {
        $costText = 'FREE';
    } else {
        $costText = 'R' . number_format($cost, 2);
    }

    return [
        'title' => $title,
        'subtitle' => implode(' • ', $subtitle),
        'cost' => $costText,
        'cost_numeric' => $cost,
        'is_free' => $qualifiesForFreeShipping && $cost == 0
    ];
}
