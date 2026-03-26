<?php
/**
 * Coupons & Offers Page - Database-Driven Coupon System
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

require_once __DIR__ . '/../../includes/coupons_service.php';

$message = '';
$messageType = '';

// Handle Apply button actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

    if (empty($couponCode)) {
        $message = 'Please enter a coupon code';
        $messageType = 'error';
    } else {
        // Get coupon from database
        $coupon = getCouponByCode($db, $couponCode);

        if (!$coupon) {
            $message = 'Coupon code not found';
            $messageType = 'error';
        } else {
            // Validate coupon
            $validation = validateCoupon($db, $coupon, 0, $currentUser['id']);

            if ($validation['valid']) {
                // Store coupon in session
                $_SESSION['selected_coupon'] = $couponCode;
                $message = 'Coupon applied successfully! Redirecting to checkout...';
                $messageType = 'success';
            } else {
                $message = $validation['message'];
                $messageType = 'error';
            }
        }
    }
}

// Check if there's a selected coupon in session
$selectedCoupon = $_SESSION['selected_coupon'] ?? null;

$pageTitle = "Coupons & Offers";
$currentPage = "coupons-offers";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-purple-100 text-sm">Discover and redeem exclusive deals</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-ticket-alt text-2xl"></i>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> px-4 py-3 rounded-lg mb-6 border">
            <?= htmlspecialchars($message) ?>
            <?php if ($messageType === 'success'): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = '<?= url('/checkout/') ?>';
                    }, 2000);
                </script>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Coupons & Offers -->
        <div class="lg:w-3/4">
            <!-- Active Coupons -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Coupons</h3>

                <?php if (empty($availableCoupons)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-ticket-alt text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-600">No available coupons at the moment</p>
                        <p class="text-gray-500 text-sm">Check back later for new offers!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($availableCoupons as $coupon): ?>
                            <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-4 text-white relative">
                                <?php if ($selectedCoupon === $coupon['code']): ?>
                                    <div class="absolute top-2 right-2">
                                        <span class="bg-white text-green-600 px-2 py-1 rounded text-xs font-bold">
                                            <i class="fas fa-check"></i> Selected
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-lg"><?= htmlspecialchars($coupon['code']) ?></h4>
                                        <p class="text-sm opacity-90 mt-1"><?= htmlspecialchars($coupon['description']) ?></p>

                                        <div class="mt-3 space-y-1">
                                            <p class="text-xs opacity-75">
                                                <strong>Discount:</strong> <?= formatDiscount($coupon) ?>
                                            </p>

                                            <?php if ($coupon['min_order_amount'] > 0): ?>
                                                <p class="text-xs opacity-75">
                                                    <strong>Min Order:</strong> R<?= number_format($coupon['min_order_amount'], 2) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if ($coupon['expires_at']): ?>
                                                <p class="text-xs opacity-75">
                                                    <strong>Valid until:</strong> <?= date('M j, Y', strtotime($coupon['expires_at'])) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if ($coupon['usage_limit']): ?>
                                                <?php
                                                $stmt = $db->prepare("SELECT COUNT(*) as count FROM coupon_usages WHERE coupon_id = ?");
                                                $stmt->execute([$coupon['id']]);
                                                $usedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                ?>
                                                <p class="text-xs opacity-75">
                                                    <strong>Usage:</strong> <?= $usedCount ?>/<?= $coupon['usage_limit'] ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($selectedCoupon === $coupon['code']): ?>
                                        <a href="<?= url('/checkout/') ?>" class="bg-white text-green-600 px-3 py-1 rounded text-sm font-medium hover:bg-gray-100 flex items-center">
                                            Checkout <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    <?php else: ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="coupon_code" value="<?= htmlspecialchars($coupon['code']) ?>">
                                            <button type="submit" class="bg-white text-green-600 px-3 py-1 rounded text-sm font-medium hover:bg-gray-100">
                                                Apply
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Manual Coupon Code Entry -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Have a coupon code?</h3>
                <form method="POST" action="" class="flex gap-2">
                    <input type="text" name="coupon_code" value="<?= htmlspecialchars($selectedCoupon ?? '') ?>"
                           placeholder="Enter coupon code"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 uppercase">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 font-medium">
                        Apply Coupon
                    </button>
                </form>
                <?php if ($selectedCoupon): ?>
                    <p class="text-green-600 text-sm mt-2">
                        <i class="fas fa-check-circle"></i> Coupon "<?= htmlspecialchars($selectedCoupon) ?>"" is selected
                    </p>
                <?php endif; ?>
            </div>

            <!-- Recently Used Coupons -->
            <?php if (!empty($recentlyUsedCoupons)): ?>
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recently Used</h3>
                    <div class="space-y-3">
                        <?php foreach ($recentlyUsedCoupons as $used): ?>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="font-medium text-gray-700"><?= htmlspecialchars($used['code']) ?></span>
                                    <span class="text-sm text-gray-500 ml-2">
                                        Used on <?= date('M j, Y', strtotime($used['used_at'])) ?>
                                    </span>
                                </div>
                                <?php
                                $discount = calculateDiscount($used, (float)($used['order_total'] ?? 0));
                                ?>
                                <span class="text-sm text-green-600 font-medium">-R<?= number_format($discount, 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Special Offers -->
            <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Special Offers</h3>
                <div class="space-y-4">
                    <div class="border border-orange-200 bg-orange-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-orange-900">Black Friday Early Access</h4>
                                <p class="text-sm text-orange-700">Get 25% off everything + free shipping</p>
                                <p class="text-xs text-orange-600 mt-1">Starts: Nov 25, 2025 12:00 AM</p>
                            </div>
                            <button class="bg-orange-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-orange-700">
                                Get Notified
                            </button>
                        </div>
                    </div>

                    <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-green-900">Refer a Friend</h4>
                                <p class="text-sm text-green-700">Earn R50 credit for each successful referral</p>
                                <p class="text-xs text-green-600 mt-1">Unlimited referrals</p>
                            </div>
                            <button class="bg-green-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-green-700">
                                Share Now
                            </button>
                        </div>
                    </div>

                    <div class="border border-blue-200 bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-blue-900">Membership Program</h4>
                                <p class="text-sm text-blue-700">Exclusive member-only discounts up to 30% off</p>
                                <p class="text-xs text-blue-600 mt-1">Join for only R99/month</p>
                            </div>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700">
                                Learn More
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
