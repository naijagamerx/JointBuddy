<?php
/**
 * Migration: Add Product Custom Fields Tables
 * Run this once to add new tables for product custom fields
 */

require_once __DIR__ . '/../includes/database.php';

echo "<h2>🚀 Product Custom Fields Migration</h2>\n";
echo "<pre>\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "✅ Connected to database\n\n";

    // 1. Create product_field_templates table (defines available field types)
    echo "Creating product_field_templates table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `product_field_templates` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `field_name` VARCHAR(100) NOT NULL UNIQUE,
        `field_label` VARCHAR(100) NOT NULL,
        `field_type` ENUM('text', 'textarea', 'select', 'number') DEFAULT 'text',
        `field_options` TEXT NULL COMMENT 'JSON array for select options',
        `is_active` TINYINT(1) DEFAULT 1,
        `display_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✅ product_field_templates created\n";

    // 2. Create product_custom_fields table (stores actual field values per product)
    echo "Creating product_custom_fields table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `product_custom_fields` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `product_id` INT NOT NULL,
        `field_name` VARCHAR(100) NOT NULL,
        `field_value` TEXT,
        `field_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_product_id` (`product_id`),
        UNIQUE KEY `unique_product_field` (`product_id`, `field_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✅ product_custom_fields created\n";

    // 3. Create often_bought_together table
    echo "Creating often_bought_together table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `often_bought_together` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `product_id` INT NOT NULL,
        `related_product_id` INT NOT NULL,
        `frequency` INT DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_product_id` (`product_id`),
        UNIQUE KEY `unique_pair` (`product_id`, `related_product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✅ often_bought_together created\n";

    // 4. Add category_id to products if not exists
    echo "Checking products table for category_id column...\n";
    try {
        $db->exec("ALTER TABLE `products` ADD COLUMN `category_id` INT NULL AFTER `slug`");
        echo "✅ Added category_id column to products\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ category_id column already exists\n";
        } else {
            throw $e;
        }
    }

    // 5. Add brand column to products if not exists
    echo "Checking products table for brand column...\n";
    try {
        $db->exec("ALTER TABLE `products` ADD COLUMN `brand` VARCHAR(100) NULL AFTER `category_id`");
        echo "✅ Added brand column to products\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ brand column already exists\n";
        } else {
            throw $e;
        }
    }

    // 6. Insert default field templates
    echo "\nInserting default field templates...\n";
    $defaultFields = [
        ['warranty', 'Warranty', 'text', 1],
        ['part_number', 'Part Number', 'text', 2],
        ['model', 'Model', 'text', 3],
        ['whats_in_box', "What's in the Box", 'textarea', 4],
        ['colour_name', 'Colour Name', 'text', 5],
        ['basic_colours', 'Basic Colours', 'text', 6],
        ['barcode', 'Barcode', 'text', 7],
        ['dimensions', 'Dimensions', 'text', 8],
        ['weight', 'Weight', 'text', 9],
        ['material', 'Material', 'text', 10],
        ['manufacturer', 'Manufacturer', 'text', 11],
        ['country_of_origin', 'Country of Origin', 'text', 12],
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO product_field_templates (field_name, field_label, field_type, display_order) VALUES (?, ?, ?, ?)");
    foreach ($defaultFields as $field) {
        $stmt->execute($field);
    }
    echo "✅ Default field templates inserted\n";

    // 7. Populate often_bought_together with some sample data based on random products
    echo "\nPopulating often_bought_together with sample data...\n";
    $products = $db->query("SELECT id FROM products LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
    if (count($products) >= 3) {
        $stmt = $db->prepare("INSERT IGNORE INTO often_bought_together (product_id, related_product_id, frequency) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($products); $i++) {
            // Add 2 random related products for each
            $related1 = $products[($i + 1) % count($products)];
            $related2 = $products[($i + 2) % count($products)];
            $stmt->execute([$products[$i], $related1, rand(5, 50)]);
            $stmt->execute([$products[$i], $related2, rand(5, 50)]);
        }
        echo "✅ Sample often_bought_together data inserted\n";
    }

    echo "\n========================================\n";
    echo "✅ Migration completed successfully!\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
