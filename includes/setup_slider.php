<?php
/**
 * Setup/Update homepage slider data
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/url_helper.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if slider table exists, if not create
    $db->exec("CREATE TABLE IF NOT EXISTS homepage_slider (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255),
        image_path VARCHAR(255),
        link_url VARCHAR(255),
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Clear existing (optional, but ensures we match the new images)
    // Actually, better to UPDATE or INSERT if empty to preserve user edits if any.
    // Let's check count.
    $stmt = $db->query("SELECT COUNT(*) FROM homepage_slider");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert 4 slides with dynamic URLs
        $sql = "INSERT INTO homepage_slider (title, subtitle, image_path, link_url, sort_order, is_active) VALUES 
        ('Premium Quality Cannabis', 'Sourced from the finest growers for your satisfaction', '" . assetUrl('/images/slider/slide1.png') . "', '" . shopUrl('/') . "', 1, 1),
        ('Natural Wellness', 'Discover the healing power of nature', '" . assetUrl('/images/slider/slide2.png') . "', '" . shopUrl('?category=wellness') . "', 2, 1),
        ('New Arrivals', 'Check out the latest strains and accessories', '" . assetUrl('/images/slider/slide1.png') . "', '" . shopUrl('?sort=newest') . "', 3, 1),
        ('Join the Community', 'Sign up for exclusive deals and updates', '" . assetUrl('/images/slider/slide2.png') . "', '" . url('/register/') . "', 4, 1)";
        
        $db->exec($sql);
        echo "Inserted 4 default slides.\n";
    } else {
        // Update images for existing slides 1 and 2 to point to new files
        $db->exec("UPDATE homepage_slider SET image_path = '" . assetUrl('/images/slider/slide1.png') . "' WHERE sort_order = 1");
        $db->exec("UPDATE homepage_slider SET image_path = '" . assetUrl('/images/slider/slide2.png') . "' WHERE sort_order = 2");
        // For 3 and 4 re-use 1 and 2
        $db->exec("UPDATE homepage_slider SET image_path = '" . assetUrl('/images/slider/slide1.png') . "' WHERE sort_order = 3");
        $db->exec("UPDATE homepage_slider SET image_path = '" . assetUrl('/images/slider/slide2.png') . "' WHERE sort_order = 4");
        echo "Updated existing slides with new image paths.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
