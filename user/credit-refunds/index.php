<?php
/**
 * Credit & Refunds Page - Manage Refunds and Credit Transactions
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

require_once __DIR__ . '/../../includes/credit_service.php';

$pageTitle = "Credit & Refunds";
$currentPage = "credit-refunds";

$userId = $currentUser['id'];
$creditSummary = getUserCreditSummary($db, $userId);
$transactions = getUserCreditTransactions($db, $userId, 10);
$pendingRefunds = getPendingRefundAmount($db, $userId);
$completedRefunds = getCompletedRefundAmount($db, $userId);

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                    <p class="text-blue-100 text-sm">Track and manage your refunds and credits</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-credit-card text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Universal Sidebar Navigation -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <!-- Main Content - Credit & Refunds -->
            <div class="lg:w-3/4">
                <!-- Refund Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xl font-semibold text-gray-900">R<?= number_format($completedRefunds, 2) ?></p>
                                <p class="text-sm text-gray-600">Total Refunded</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100">
                                <i class="fas fa-clock text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xl font-semibold text-gray-900">R<?= number_format($pendingRefunds, 2) ?></p>
                                <p class="text-sm text-gray-600">Pending Refunds</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100">
                                <i class="fas fa-coins text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xl font-semibold text-gray-900">R<?= number_format($creditSummary['store_credit'], 2) ?></p>
                                <p class="text-sm text-gray-600">Store Credit</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-purple-100">
                                <i class="fas fa-gift text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xl font-semibold text-gray-900"><?= number_format($creditSummary['reward_points']) ?></p>
                                <p class="text-sm text-gray-600">Reward Points</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Transactions</h3>
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-3 text-gray-300"></i>
                            <p>No transactions yet</p>
                            <p class="text-sm">Your transaction history will appear here</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($transactions as $tx): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 rounded-full bg-<?= $tx['color'] ?>-100">
                                            <i class="fas <?= $tx['icon'] ?> text-<?= $tx['color'] ?>-600"></i>
                                        </div>
                                        <div>
                                            <?php if ($tx['type'] === 'voucher_redemption'): ?>
                                                <p class="font-medium text-gray-900">Gift Voucher Redeemed</p>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($tx['description']) ?></p>
                                                <?php if ($tx['code']): ?>
                                                    <p class="text-xs text-gray-500">Code: <?= htmlspecialchars($tx['code']) ?></p>
                                                <?php endif; ?>
                                            <?php elseif ($tx['type'] === 'reward_points'): ?>
                                                <p class="font-medium text-gray-900"><?= htmlspecialchars($tx['title']) ?></p>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($tx['description']) ?></p>
                                                <?php if ($tx['order_id']): ?>
                                                    <p class="text-xs text-gray-500">Order #ORD-<?= str_pad($tx['order_id'], 6, '0', STR_PAD_LEFT) ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($tx['date'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <?php if ($tx['type'] === 'voucher_redemption'): ?>
                                            <p class="font-semibold text-green-600">+R<?= number_format($tx['amount'], 2) ?></p>
                                        <?php elseif ($tx['type'] === 'reward_points'): ?>
                                            <?php if (strpos($tx['title'], 'Earned') !== false): ?>
                                                <p class="font-semibold text-purple-600">+<?= number_format($tx['amount']) ?> pts</p>
                                            <?php else: ?>
                                                <p class="font-semibold text-orange-600">-<?= number_format($tx['amount']) ?> pts</p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $tx['color'] ?>-100 text-<?= $tx['color'] ?>-800">
                                            <?= ucfirst($tx['status']) ?>
                                        </span>
                                        <?php if (isset($tx['balance_after'])): ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Balance: <?= $tx['type'] === 'reward_points' ? number_format($tx['balance_after']) . ' pts' : 'R' . number_format($tx['balance_after'], 2) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Refund Policy -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Refund Policy</h3>
                    <div class="prose prose-sm max-w-none">
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-shield-alt text-green-500 mt-1"></i>
                                <div>
                                    <h4 class="font-medium text-gray-900">30-Day Return Policy</h4>
                                    <p class="text-sm text-gray-600">We offer a 30-day return policy for all unopened products in their original packaging.</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-truck text-blue-500 mt-1"></i>
                                <div>
                                    <h4 class="font-medium text-gray-900">Return Shipping</h4>
                                    <p class="text-sm text-gray-600">Free return shipping for defective or damaged items. Customer pays return shipping for change of mind.</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-clock text-purple-500 mt-1"></i>
                                <div>
                                    <h4 class="font-medium text-gray-900">Processing Time</h4>
                                    <p class="text-sm text-gray-600">Refunds are processed within 5-7 business days after we receive your returned items.</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-credit-card text-orange-500 mt-1"></i>
                                <div>
                                    <h4 class="font-medium text-gray-900">Refund Method</h4>
                                    <p class="text-sm text-gray-600">Refunds are issued to the original payment method or as store credit upon request.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
