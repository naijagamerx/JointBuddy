<?php
require_once __DIR__ . '/../includes/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $checkTable = $db->query("SHOW TABLES LIKE 'user_carts'");
    if ($checkTable && $checkTable->rowCount() > 0) {
        echo "user_carts table already exists." . PHP_EOL;
        exit;
    }

    $sql = "CREATE TABLE user_carts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        variation VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_cart_item (user_id, product_id, variation)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);
    echo "user_carts table created successfully." . PHP_EOL;

} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
