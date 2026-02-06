<?php
/**
 * User Dashboard Footer Component
 * Consistent footer for all user dashboard pages
 */

// Include bootstrap if not already loaded
if (!function_exists('Services')) {
    require_once __DIR__ . '/../../includes/bootstrap.php';
}

// Get dynamic store name from database
$storeName = 'Your Store';
try {
    $db = Services::db();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['setting_value'])) {
        $storeName = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
}
?>

<!-- Footer -->
<footer class="bg-gray-900 text-white mt-16">
    <div class="container mx-auto px-4 py-12 max-w-7xl">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- Column 1 - Company -->
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <?= htmlspecialchars($storeName) ?>
                </h3>
                <p class="text-gray-300 mb-4 text-sm">
                    Your trusted cannabis marketplace connecting consumers with quality products and verified vendors.
                </p>
                <!-- Social Media Links (Coming Soon) -->
                <!--
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
                -->
            </div>
            
            <!-- Column 2 - Shop -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Shop</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= shopUrl('/') ?>" class="text-gray-300 hover:text-white transition-colors">All Products</a></li>
                    <li><a href="<?= shopUrl('/?category=flowers') ?>" class="text-gray-300 hover:text-white transition-colors">Cannabis Flowers</a></li>
                    <li><a href="<?= shopUrl('/?category=edibles') ?>" class="text-gray-300 hover:text-white transition-colors">Edibles</a></li>
                    <li><a href="<?= shopUrl('/?category=vapes') ?>" class="text-gray-300 hover:text-white transition-colors">Vapes & Cartridges</a></li>
                    <li><a href="<?= shopUrl('/?category=concentrates') ?>" class="text-gray-300 hover:text-white transition-colors">Concentrates</a></li>
                    <li><a href="<?= shopUrl('/?sort=price_low') ?>" class="text-gray-300 hover:text-white transition-colors">Deals & Offers</a></li>
                    <li><a href="<?= shopUrl('/?sort=newest') ?>" class="text-gray-300 hover:text-white transition-colors">New Arrivals</a></li>
                </ul>
            </div>
            
            <!-- Column 3 - Account & Support -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Account & Support</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= userUrl('/login/') ?>" class="text-gray-300 hover:text-white transition-colors">Login</a></li>
                    <li><a href="<?= url('/register/') ?>" class="text-gray-300 hover:text-white transition-colors">Register</a></li>
                    <li><a href="<?= userUrl('/orders/') ?>" class="text-gray-300 hover:text-white transition-colors">Track Your Order</a></li>
                    <li><a href="<?= userUrl('/dashboard/') ?>" class="text-gray-300 hover:text-white transition-colors">My Account</a></li>
                    <li><a href="<?= url('/help/') ?>" class="text-gray-300 hover:text-white transition-colors">Help Centre</a></li>
                    <li><a href="<?= url('/contact/') ?>" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="<?= url('/returns/') ?>" class="text-gray-300 hover:text-white transition-colors">Returns & Refunds</a></li>
                    <li><a href="<?= url('/shipping/') ?>" class="text-gray-300 hover:text-white transition-colors">Shipping Info</a></li>
                </ul>
            </div>
            
            <!-- Column 4 - Legal & Info -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Legal & Information</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= url('/about/') ?>" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                    <li><a href="<?= url('/privacy/') ?>" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="<?= url('/terms/') ?>" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="<?= url('/age-verification/') ?>" class="text-gray-300 hover:text-white transition-colors">Age Verification</a></li>
                    <li><a href="<?= url('/compliance/') ?>" class="text-gray-300 hover:text-white transition-colors">Compliance</a></li>
                    <li><a href="<?= url('/vendor-application/') ?>" class="text-gray-300 hover:text-white transition-colors">Become a Vendor</a></li>
                    <li><a href="<?= url('/careers/') ?>" class="text-gray-300 hover:text-white transition-colors">Careers</a></li>
                    <li><a href="<?= url('/news/') ?>" class="text-gray-300 hover:text-white transition-colors">News & Updates</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div class="border-t border-gray-700 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-300 text-sm">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?>. All rights reserved. Please consume responsibly.
                </p>
                <div class="flex items-center mt-4 md:mt-0 space-x-4">
                    <span class="text-gray-300 text-sm">We verify age for all purchases</span>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-shield-alt text-green-400"></i>
                        <span class="text-green-400 text-sm font-semibold">Secure & Verified</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

