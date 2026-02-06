<?php
/**
 * Product Helper Functions - CannaBuddy
 * Functions for fetching product data, custom fields, and recommendations
 */

/**
 * Get product custom fields from JSON column
 */
function getProductCustomFields($db, $productId) {
    try {
        $stmt = $db->prepare("SELECT custom_fields FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || empty($result['custom_fields'])) {
            return [];
        }

        $customFields = json_decode($result['custom_fields'], true);
        if (!is_array($customFields)) {
            return [];
        }

        // Convert to expected format
        $formatted = [];
        foreach ($customFields as $field) {
            if (isset($field['label']) && isset($field['value'])) {
                $formatted[] = [
                    'field_name' => $field['label'],
                    'field_value' => $field['value'],
                    'field_label' => $field['label']
                ];
            }
        }

        return $formatted;
    } catch (Exception $e) {
        error_log("Error fetching custom fields: " . $e->getMessage());
        return [];
    }
}

/**
 * Get available field templates
 */
function getFieldTemplates($db) {
    try {
        $stmt = $db->query("SELECT * FROM product_field_templates WHERE is_active = 1 ORDER BY display_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching field templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Save product custom field
 */
function saveProductCustomField($db, $productId, $fieldName, $fieldValue, $fieldOrder = 0) {
    try {
        $stmt = $db->prepare("
            INSERT INTO product_custom_fields (product_id, field_name, field_value, field_order)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE field_value = VALUES(field_value), field_order = VALUES(field_order)
        ");
        return $stmt->execute([$productId, $fieldName, $fieldValue, $fieldOrder]);
    } catch (Exception $e) {
        error_log("Error saving custom field: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete product custom field
 */
function deleteProductCustomField($db, $productId, $fieldName) {
    try {
        $stmt = $db->prepare("DELETE FROM product_custom_fields WHERE product_id = ? AND field_name = ?");
        return $stmt->execute([$productId, $fieldName]);
    } catch (Exception $e) {
        error_log("Error deleting custom field: " . $e->getMessage());
        return false;
    }
}

/**
 * Get popular picks in category
 */
function getPopularPicksInCategory($db, $productId, $categoryId = null, $limit = 4) {
    try {
        if ($categoryId) {
            $stmt = $db->prepare("
                SELECT * FROM products
                WHERE id != ? AND category_id = ? AND status = 'active'
                ORDER BY featured DESC, RAND()
                LIMIT ?
            ");
            $stmt->execute([$productId, $categoryId, $limit]);
        } else {
            $stmt = $db->prepare("
                SELECT * FROM products
                WHERE id != ? AND status = 'active'
                ORDER BY featured DESC, RAND()
                LIMIT ?
            ");
            $stmt->execute([$productId, $limit]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching popular picks: " . $e->getMessage());
        return [];
    }
}

/**
 * Get "You Might Also Like" products
 */
function getYouMightAlsoLike($db, $productId, $limit = 4) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM products
            WHERE id != ? AND status = 'active'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching recommendations: " . $e->getMessage());
        return [];
    }
}

/**
 * Get related products
 */
function getRelatedProducts($db, $productId, $categoryId = null, $limit = 4) {
    try {
        if ($categoryId) {
            $stmt = $db->prepare("
                SELECT * FROM products
                WHERE id != ? AND category_id = ? AND status = 'active'
                ORDER BY RAND()
                LIMIT ?
            ");
            $stmt->execute([$productId, $categoryId, $limit]);
        } else {
            $stmt = $db->prepare("
                SELECT * FROM products
                WHERE id != ? AND status = 'active'
                ORDER BY RAND()
                LIMIT ?
            ");
            $stmt->execute([$productId, $limit]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching related products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get similar items from popular brands
 */
function getSimilarFromBrands($db, $productId, $brand = null, $limit = 4) {
    try {
        if ($brand) {
            $stmt = $db->prepare("
                SELECT * FROM products
                WHERE id != ? AND brand = ? AND status = 'active'
                ORDER BY featured DESC, RAND()
                LIMIT ?
            ");
            $stmt->execute([$productId, $brand, $limit]);
        } else {
            $stmt = $db->prepare("
                SELECT * FROM products
                WHERE id != ? AND brand IS NOT NULL AND brand != '' AND status = 'active'
                ORDER BY featured DESC, RAND()
                LIMIT ?
            ");
            $stmt->execute([$productId, $limit]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching brand products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get often bought together products
 */
function getOftenBoughtTogether($db, $productId, $limit = 3) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, obt.frequency
            FROM often_bought_together obt
            JOIN products p ON obt.related_product_id = p.id
            WHERE obt.product_id = ? AND p.status = 'active'
            ORDER BY obt.frequency DESC
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching often bought together: " . $e->getMessage());
        return [];
    }
}

/**
 * Get product reviews with user info
 */
function getProductReviews($db, $productId, $limit = 10) {
    try {
        $stmt = $db->prepare("
            SELECT pr.*, u.first_name, u.last_name, u.email
            FROM product_reviews pr
            LEFT JOIN users u ON pr.user_id = u.id
            WHERE pr.product_id = ? AND pr.status = 'approved'
            ORDER BY pr.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table might not exist yet
        return [];
    }
}

/**
 * Get product rating summary
 */
function getProductRatingSummary($db, $productId) {
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM product_reviews
            WHERE product_id = ? AND status = 'approved'
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'total_reviews' => 0,
            'avg_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0
        ];
    }
}

/**
 * Render star rating HTML
 */
function renderStarRating($rating, $showNumber = true) {
    $rating = floatval($rating);
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

    $html = '<div class="flex items-center">';

    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star text-yellow-400"></i>';
    }

    // Half star
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
    }

    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star text-yellow-400"></i>';
    }

    if ($showNumber && $rating > 0) {
        $html .= '<span class="ml-1 text-sm text-gray-600">(' . number_format($rating, 1) . ')</span>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Get product main image
 */
function getProductMainImage($product) {
    // Check new format first: comma-separated URLs in 'images' field
    if (!empty($product['images'])) {
        $imageUrls = explode(',', $product['images']);
        $imagePath = trim($imageUrls[0]);
        // Convert ANY localhost or hardcoded domain URLs to relative paths
        $imagePath = preg_replace('#^https?://localhost/[^/]+/#', '/', $imagePath);
        $imagePath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $imagePath);
        // Strip hardcoded CannaBuddy.shop prefix if present (but NOT /assets/)
        $imagePath = preg_replace('#^/CannaBuddy\.shop/#', '/', $imagePath);
        // Convert to full URL using url() helper for production compatibility
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath; // Already a full URL
        }
        return url($imagePath); // Convert relative path to full URL
    }

    // Fallback to legacy format: image_1 field
    if (!empty($product['image_1'])) {
        $imagePath = $product['image_1'];
        // Convert ANY localhost or hardcoded domain URLs to relative paths
        $imagePath = preg_replace('#^https?://localhost/[^/]+/#', '/', $imagePath);
        $imagePath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $imagePath);
        // Strip hardcoded CannaBuddy.shop prefix if present (but NOT /assets/)
        $imagePath = preg_replace('#^/CannaBuddy\.shop/#', '/', $imagePath);
        // Convert to full URL using url() helper for production compatibility
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath; // Already a full URL
        }
        return url($imagePath); // Convert relative path to full URL
    }

    return assetUrl('images/products/placeholder.png');
}

/**
 * Get all product images
 */
function getProductImages($product) {
    $images = [];

    // Check new format first: comma-separated URLs in 'images' field
    if (!empty($product['images'])) {
        $imageUrls = explode(',', $product['images']);
        foreach ($imageUrls as $imagePath) {
            $imagePath = trim($imagePath);
            if (!empty($imagePath)) {
                // Convert ANY localhost or hardcoded domain URLs to relative paths
                $imagePath = preg_replace('#^https?://localhost/[^/]+/#', '/', $imagePath);
                $imagePath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $imagePath);
                // Strip hardcoded CannaBuddy.shop prefix if present (but NOT /assets/)
                $imagePath = preg_replace('#^/CannaBuddy\.shop/#', '/', $imagePath);
                // Convert to full URL using url() helper for production compatibility
                if (strpos($imagePath, 'http') === 0) {
                    $images[] = $imagePath; // Already a full URL
                } else {
                    $images[] = url($imagePath); // Convert relative path to full URL
                }
            }
        }
    }

    // Fallback to legacy format: image_1, image_2, etc. fields
    if (empty($images)) {
        for ($i = 1; $i <= 5; $i++) {
            $key = 'image_' . $i;
            if (!empty($product[$key])) {
                $imagePath = $product[$key];
                // Convert ANY localhost or hardcoded domain URLs to relative paths
                $imagePath = preg_replace('#^https?://localhost/[^/]+/#', '/', $imagePath);
                $imagePath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $imagePath);
                // Strip hardcoded CannaBuddy.shop prefix if present (but NOT /assets/)
                $imagePath = preg_replace('#^/CannaBuddy\.shop/#', '/', $imagePath);
                // Convert to full URL using url() helper for production compatibility
                if (strpos($imagePath, 'http') === 0) {
                    $images[] = $imagePath; // Already a full URL
                } else {
                    $images[] = url($imagePath); // Convert relative path to full URL
                }
            }
        }
    }

    // If still no images, return placeholder
    if (empty($images)) {
        $images[] = assetUrl('images/products/placeholder.png');
    }
    return $images;
}

/**
 * Get category breadcrumb
 */
function getCategoryBreadcrumb($db, $categoryId) {
    try {
        $breadcrumb = [];
        $stmt = $db->prepare("SELECT id, name, slug, parent_id FROM categories WHERE id = ?");

        while ($categoryId) {
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($category) {
                array_unshift($breadcrumb, $category);
                $categoryId = $category['parent_id'];
            } else {
                break;
            }
        }
        return $breadcrumb;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get product policies as array of lines
 */
function getProductPolicies($product) {
    if (empty($product['product_policies'])) {
        return [];
    }

    // Split by newlines and filter out empty lines
    $policies = explode("\n", $product['product_policies']);
    $policies = array_filter(array_map('trim', $policies));

    return array_values($policies);
}
