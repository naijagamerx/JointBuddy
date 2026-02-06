<?php
// Load URL Helper
require_once __DIR__ . '/url_helper.php';

// Skip if already included
if (defined('FOOTER_INCLUDED')) return;
define('FOOTER_INCLUDED', true);
?>

<!-- Universal Footer - Standardized -->
<footer class="bg-gray-900 text-white mt-24 pt-16 pb-12">
    <?php
    // Get Store Name
    $footerStoreName = 'CannaBuddy';
    if (isset($db)) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['setting_value'])) {
                $footerStoreName = $row['setting_value'];
            }
        } catch (Exception $e) {}
    }
    ?>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl pt-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

            <!-- Column 1 - Company -->
            <div>
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <?php echo htmlspecialchars($footerStoreName); ?>
                </h3>
                <p class="text-gray-300 mb-4 text-sm">
                    Your trusted cannabis marketplace connecting consumers with quality products and verified vendors.
                </p>
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
            </div>

            <!-- Column 2 - Shop -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Shop</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo  shopUrl('/') ?>" class="text-gray-300 hover:text-white transition-colors">All Products</a></li>
                    <li><a href="<?php echo  shopUrl('/?category=flowers') ?>" class="text-gray-300 hover:text-white transition-colors">Cannabis Flowers</a></li>
                    <li><a href="<?php echo  shopUrl('/?category=edibles') ?>" class="text-gray-300 hover:text-white transition-colors">Edibles</a></li>
                    <li><a href="<?php echo  shopUrl('/?category=vapes') ?>" class="text-gray-300 hover:text-white transition-colors">Vapes & Cartridges</a></li>
                    <li><a href="<?php echo  shopUrl('/?category=concentrates') ?>" class="text-gray-300 hover:text-white transition-colors">Concentrates</a></li>
                    <li><a href="<?php echo  shopUrl('/deals') ?>" class="text-gray-300 hover:text-white transition-colors">Deals & Offers</a></li>
                    <li><a href="<?php echo  shopUrl('/new') ?>" class="text-gray-300 hover:text-white transition-colors">New Arrivals</a></li>
                </ul>
            </div>

            <!-- Column 3 - Account & Support -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Account & Support</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo  userUrl('/login/') ?>" class="text-gray-300 hover:text-white transition-colors">Login</a></li>
                    <li><a href="<?php echo  url('/register/') ?>" class="text-gray-300 hover:text-white transition-colors">Register</a></li>
                    <li><a href="<?php echo  userUrl('/orders/') ?>" class="text-gray-300 hover:text-white transition-colors">Track Your Order</a></li>
                    <li><a href="<?php echo  userUrl('/dashboard/') ?>" class="text-gray-300 hover:text-white transition-colors">My Account</a></li>
                    <li><a href="<?php echo  url('/help/') ?>" class="text-gray-300 hover:text-white transition-colors">Help Centre</a></li>
                    <li><a href="<?php echo  url('/contact/') ?>" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="<?php echo  url('/returns/') ?>" class="text-gray-300 hover:text-white transition-colors">Returns & Refunds</a></li>
                    <li><a href="<?php echo  url('/shipping/') ?>" class="text-gray-300 hover:text-white transition-colors">Shipping Info</a></li>
                </ul>
            </div>

            <!-- Column 4 - Legal & Info -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Legal & Information</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo  url('/about/') ?>" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                    <li><a href="<?php echo  url('/privacy/') ?>" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="<?php echo  url('/terms/') ?>" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="<?php echo  url('/refund-policy/') ?>" class="text-gray-300 hover:text-white transition-colors">Refund Policy</a></li>
                    <li><a href="<?php echo  url('/compliance/') ?>" class="text-gray-300 hover:text-white transition-colors">Compliance</a></li>
                    <li><a href="<?php echo  url('/vendor-application/') ?>" class="text-gray-300 hover:text-white transition-colors">Become a Vendor</a></li>
                    <li><a href="<?php echo  url('/careers/') ?>" class="text-gray-300 hover:text-white transition-colors">Careers</a></li>
                    <li><a href="<?php echo  url('/news/') ?>" class="text-gray-300 hover:text-white transition-colors">News & Updates</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="border-t border-gray-700 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-300 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($footerStoreName); ?>. All rights reserved. Please consume responsibly.
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
</body>
</html>
