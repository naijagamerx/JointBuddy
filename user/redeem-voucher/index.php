<?php
/**
 * Redeem Gift Voucher Page - Database-Driven Voucher System
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

// Include voucher service
require_once __DIR__ . '/../../includes/voucher_service.php';

$pageTitle = "Redeem Gift Voucher";
$currentPage = "redeem-voucher";
$message = '';
$messageType = '';

// Handle voucher redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();

    if (isset($_POST['voucher_code'])) {
        $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));

        if (empty($voucherCode)) {
            $message = 'Please enter a voucher code';
            $messageType = 'error';
        } else {
            // Get voucher from database
            $voucher = getVoucherByCode($db, $voucherCode);

            if (!$voucher) {
                $message = 'Invalid voucher code. Please check and try again.';
                $messageType = 'error';
            } else {
                // Validate voucher
                $validation = validateVoucher($db, $voucher, $currentUser['id']);

                if (!$validation['valid']) {
                    $message = $validation['message'];
                    $messageType = 'error';
                } else {
                    // Redeem voucher
                    $result = redeemVoucher($db, $voucher, $currentUser['id']);

                    if ($result['success']) {
                        $message = $result['message'];
                        $messageType = 'success';
                    } else {
                        $message = $result['message'];
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

// Get user's voucher balance
$userBalance = getUserVoucherBalance($db, $currentUser['id']);

// Get user's recent redemptions
$recentRedemptions = getUserRedemptions($db, $currentUser['id'], 10);

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                    <p class="text-red-100 text-sm">Redeem your gift voucher code</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-gift text-2xl"></i>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> px-4 py-3 rounded-lg mb-6 border">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Universal Sidebar Navigation -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <!-- Main Content - Redeem Gift Voucher -->
            <div class="lg:w-3/4">
                <!-- Voucher Redemption Form -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Redeem Gift Voucher</h3>
                    <form method="POST" action="" class="space-y-4">
                        <div>
                            <label for="voucherCode" class="block text-sm font-medium text-gray-700 mb-2">
                                Gift Voucher Code
                            </label>
                            <div class="flex space-x-3">
                                <input
                                    type="text"
                                    id="voucherCode"
                                    name="voucher_code"
                                    placeholder="Enter your voucher code (e.g., GIFT-1234-ABCD)"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 uppercase"
                                    required
                                >
                                <button
                                    type="submit"
                                    class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                >
                                    Redeem
                                </button>
                            </div>
                        </div>
                        <?= csrf_field() ?>
                    </form>

                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">How to redeem your voucher:</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Enter the voucher code exactly as provided</li>
                                        <li>Codes are case-sensitive</li>
                                        <li>Credit will be added to your account immediately</li>
                                        <li>Vouchers cannot be combined or transferred</li>
                                        <li>Each voucher can only be used once per account</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Balance -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Balance</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold">Gift Voucher Balance</h4>
                                    <p class="text-2xl font-bold">R<?= number_format($userBalance['balance'] ?? 0, 2) ?></p>
                                </div>
                                <i class="fas fa-gift text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg p-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold">Total Earned</h4>
                                    <p class="text-2xl font-bold">R<?= number_format($userBalance['total_earned'] ?? 0, 2) ?></p>
                                </div>
                                <i class="fas fa-wallet text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Voucher Redemptions -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Redemptions</h3>
                    <?php if (empty($recentRedemptions)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-gift text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-600">No vouchers redeemed yet</p>
                            <p class="text-gray-500 text-sm">Enter a voucher code above to add credit to your account</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentRedemptions as $redemption): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 rounded-full bg-green-100">
                                            <i class="fas fa-check text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($redemption['code']) ?></p>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($redemption['description'] ?: 'Gift voucher') ?></p>
                                            <p class="text-xs text-gray-500">Redeemed: <?= date('M j, Y', strtotime($redemption['redeemed_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-green-600">+R<?= number_format($redemption['amount'], 2) ?></p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Added to Balance
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Voucher Terms -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Voucher Terms & Conditions</h3>
                    <div class="prose prose-sm max-w-none">
                        <div class="space-y-3 text-sm text-gray-600">
                            <p>• Gift vouchers are valid until their expiry date</p>
                            <p>• Vouchers can only be used for purchases on this website</p>
                            <p>• Vouchers cannot be exchanged for cash or transferred to another account</p>
                            <p>• Each voucher can only be redeemed once per user account</p>
                            <p>• Voucher balance is automatically applied at checkout</p>
                            <p>• Lost or stolen vouchers cannot be replaced</p>
                            <p>• Voucher codes are case-sensitive and must be entered exactly as shown</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
