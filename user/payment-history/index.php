<?php
/**
 * Payment History Page - User Dashboard
 * Display user's transaction history
 */
require_once __DIR__ . '/../../includes/url_helper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = null;
$isLoggedIn = false;

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

// Redirect to login if not logged in
if (!$isLoggedIn) {
    redirect('/user/login/');
}

// Include database
require_once __DIR__ . '/../../includes/database.php';

$transactions = [];
$stats = [
    'total_paid' => 0,
    'successful' => 0,
    'pending' => 0,
    'average' => 0
];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user's orders as transactions
    $stmt = $db->prepare("
        SELECT o.*,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build transactions from orders
    foreach ($orders as $order) {
        $transactions[] = [
            'id' => $order['id'],
            'type' => 'order',
            'date' => $order['created_at'],
            'description' => 'Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
            'method' => $order['payment_method'] ?? 'N/A',
            'amount' => $order['total_amount'],
            'status' => $order['payment_status'],
            'order_status' => $order['status'],
            'item_count' => $order['item_count']
        ];

        // Calculate stats
        if ($order['payment_status'] === 'paid') {
            $stats['total_paid'] += $order['total_amount'];
            $stats['successful']++;
        } elseif ($order['payment_status'] === 'pending') {
            $stats['pending']++;
        }
    }

    // Calculate average
    if ($stats['successful'] > 0) {
        $stats['average'] = $stats['total_paid'] / $stats['successful'];
    }

} catch (Exception $e) {
    error_log("Error fetching transactions: " . $e->getMessage());
}

$pageTitle = "Payment History";
$currentPage = "payment-history";

// Status styles
$statusStyles = [
    'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
    'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800'],
    'refunded' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800']
];

$statusLabels = [
    'paid' => 'Completed',
    'pending' => 'Pending',
    'failed' => 'Failed',
    'refunded' => 'Refunded'
];

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-orange-100 text-sm">View and manage your payment transactions</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-credit-card text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <!-- Payment Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100">
                            <i class="fas fa-wallet text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900">R <?= number_format($stats['total_paid'], 2) ?></p>
                            <p class="text-xs text-gray-500">Total Paid</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-blue-100">
                            <i class="fas fa-check-circle text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900"><?= $stats['successful'] ?></p>
                            <p class="text-xs text-gray-500">Successful</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-yellow-100">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900"><?= $stats['pending'] ?></p>
                            <p class="text-xs text-gray-500">Pending</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-purple-100">
                            <i class="fas fa-chart-line text-purple-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900">R <?= number_format($stats['average'], 2) ?></p>
                            <p class="text-xs text-gray-500">Average Order</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900">Transaction History</h2>
                </div>

                <?php if (empty($transactions)): ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-receipt text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions yet</h3>
                        <p class="text-gray-500 mb-4">Your payment transactions will appear here.</p>
                        <a href="<?= shopUrl('/') ?>" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                            $txStatusStyle = $statusStyles[$tx['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                            $txStatusLabel = $statusLabels[$tx['status']] ?? ucfirst($tx['status'] ?? 'Unknown');
                            $isCredit = $tx['amount'] > 0 && $tx['status'] === 'refunded';
                            ?>
                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas <?= $tx['type'] === 'order' ? 'fa-shopping-bag' : 'fa-plus-circle' ?> text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($tx['description']) ?></p>
                                            <div class="flex items-center space-x-2 text-sm text-gray-500">
                                                <span><?= date('M j, Y', strtotime($tx['date'])) ?></span>
                                                <span class="text-gray-300">|</span>
                                                <span><?= ucwords(str_replace('_', ' ', $tx['method'] ?? 'N/A')) ?></span>
                                                <?php if ($tx['item_count'] > 0): ?>
                                                    <span class="text-gray-300">|</span>
                                                    <span><?= $tx['item_count'] ?> item(s)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $txStatusStyle['bg'] . ' ' . $txStatusStyle['text'] ?>">
                                            <?= $txStatusLabel ?>
                                        </span>
                                        <p class="font-bold <?= $isCredit || $tx['status'] === 'paid' ? 'text-green-600' : 'text-gray-900' ?>">
                                            <?= $isCredit ? '+' : '' ?>R <?= number_format($tx['amount'], 2) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 sm:ml-16 flex items-center space-x-3">
                                    <a href="<?= userUrl('/orders/view.php?id=' . $tx['id']) ?>" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                        View Order
                                    </a>
                                    <a href="<?= userUrl('/invoices/view.php?id=' . $tx['id']) ?>" class="text-sm text-gray-600 hover:text-gray-700">
                                        Invoice
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if (count($transactions) >= 10): ?>
                        <div class="px-6 py-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-600">Showing <?= count($transactions) ?> transactions</p>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-50" disabled>Previous</button>
                                    <button class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Next</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
