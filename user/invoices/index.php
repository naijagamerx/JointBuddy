<?php
/**
 * User Invoices Page - Invoice Management
 * Display and manage user invoices
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

$invoices = [];
$invoiceStats = [
    'all' => 0,
    'this_year' => 0,
    'last_year' => 0,
    'total_amount' => 0
];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all invoices (orders) for the user
    $stmt = $db->prepare("
        SELECT o.*,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $currentYear = date('Y');
    $lastYear = date('Y') - 1;

    foreach ($allOrders as $order) {
        $invoiceStats['all']++;
        $orderYear = date('Y', strtotime($order['created_at']));
        if ($orderYear == $currentYear) {
            $invoiceStats['this_year']++;
        } elseif ($orderYear == $lastYear) {
            $invoiceStats['last_year']++;
        }
        $invoiceStats['total_amount'] += $order['total_amount'];
    }

    $invoices = $allOrders;

} catch (Exception $e) {
    error_log("Error fetching invoices: " . $e->getMessage());
}

$pageTitle = "My Invoices";
$currentPage = "invoices";

// Status styles
$statusStyles = [
    'new' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
    'approved' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800'],
    'preparing' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800'],
    'ready' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-800'],
    'on_the_way' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800'],
    'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800']
];

$paymentStyles = [
    'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
    'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800'],
    'refunded' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800']
];

$statusLabels = [
    'new' => 'New',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'on_the_way' => 'On The Way',
    'delivered' => 'Delivered',
    'rejected' => 'Rejected'
];

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Invoice Management</h2>
                <p class="text-green-100 text-sm">Download and manage your order invoices</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-file-invoice text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Invoices -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Invoices Header -->
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">My Invoices</h1>
                        <?php if (!empty($invoices)): ?>
                            <a href="<?= userUrl('/orders/') ?>" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                View Orders <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoices List -->
                <div class="p-6">
                    <!-- Invoice Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <a href="<?= userUrl('/invoices/') ?>"
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= !isset($_GET['filter']) ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            All Invoices (<?= $invoiceStats['all'] ?>)
                        </a>
                        <a href="<?= userUrl('/invoices/?filter=this_year') ?>"
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= ($_GET['filter'] ?? '') === 'this_year' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            This Year (<?= $invoiceStats['this_year'] ?>)
                        </a>
                        <a href="<?= userUrl('/invoices/?filter=last_year') ?>"
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= ($_GET['filter'] ?? '') === 'last_year' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            Last Year (<?= $invoiceStats['last_year'] ?>)
                        </a>
                    </div>

                    <?php
                    // Filter invoices based on selection
                    $filteredInvoices = $invoices;
                    $filter = $_GET['filter'] ?? '';
                    $currentYear = date('Y');
                    $lastYear = date('Y') - 1;

                    if ($filter === 'this_year') {
                        $filteredInvoices = array_filter($invoices, function($order) use ($currentYear) {
                            return date('Y', strtotime($order['created_at'])) == $currentYear;
                        });
                    } elseif ($filter === 'last_year') {
                        $filteredInvoices = array_filter($invoices, function($order) use ($lastYear) {
                            return date('Y', strtotime($order['created_at'])) == $lastYear;
                        });
                    }
                    ?>

                    <?php if (empty($filteredInvoices)): ?>
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-file-invoice-dollar text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">
                                <?= empty($filter) ? 'No invoices yet' : 'No invoices for this period' ?>
                            </h3>
                            <p class="text-gray-500 mb-6">
                                <?= empty($filter) ? 'When you place your first order, an invoice will be created.' : 'Invoices from this time period will appear here.' ?>
                            </p>
                            <a href="<?= shopUrl('/') ?>" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Invoices List -->
                        <div class="space-y-4">
                            <?php foreach ($filteredInvoices as $order): ?>
                                <?php
                                $orderStatus = $order['status'] ?? 'pending';
                                $paymentStatus = $order['payment_status'] ?? 'pending';
                                $statusStyle = $statusStyles[$orderStatus] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                                $paymentStyle = $paymentStyles[$paymentStatus] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                                $statusLabel = $statusLabels[$orderStatus] ?? ucfirst($orderStatus);
                                $paymentLabel = ucfirst($paymentStatus);
                                ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-4 mb-2">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                                                </h3>
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusStyle['bg'] . ' ' . $statusStyle['text'] ?>">
                                                    <?= $statusLabel ?>
                                                </span>
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $paymentStyle['bg'] . ' ' . $paymentStyle['text'] ?>">
                                                    <?= $paymentLabel ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-1">
                                                <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>
                                                Order Date: <?= date('F j, Y', strtotime($order['created_at'])) ?>
                                            </p>
                                            <p class="text-sm text-gray-600 mb-1">
                                                <i class="fas fa-box mr-1 text-gray-400"></i>
                                                Items: <?= (int)($order['item_count'] ?? 0) ?> item(s)
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-credit-card mr-1 text-gray-400"></i>
                                                Payment: <?= ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?>
                                            </p>
                                        </div>
                                        <div class="mt-4 md:mt-0 flex flex-row md:flex-col items-center md:items-end gap-3">
                                            <div class="text-right">
                                                <p class="text-sm text-gray-500">Total Amount</p>
                                                <p class="text-xl font-bold text-green-600">R <?= number_format($order['total_amount'], 2) ?></p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="<?= userUrl('/invoices/view.php?id=' . $order['id']) ?>"
                                                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                                    <i class="fas fa-eye mr-2"></i>View
                                                </a>
                                                <button onclick="window.open('<?= userUrl('/invoices/view.php?id=' . $order['id'] . '&print=1') ?>', '_blank')"
                                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                                    <i class="fas fa-download mr-2"></i>Download
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Invoice Summary -->
                        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex flex-col sm:flex-row justify-between items-center">
                                <div class="mb-3 sm:mb-0">
                                    <p class="text-sm text-gray-600">Total Invoices: <span class="font-medium text-gray-900"><?= count($filteredInvoices) ?></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Spent: <span class="font-bold text-green-600 text-lg">R <?= number_format($invoiceStats['total_amount'], 2) ?></span></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
