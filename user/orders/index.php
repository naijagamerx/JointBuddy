<?php
/**
 * User Orders Page
 *
 * Display user's orders with tracking and filtering
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();

// Get database
try {
    $db = Services::db();
} catch (Exception $e) {
    $db = null;
    error_log("Database connection failed: " . $e->getMessage());
}

// Get filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch orders
$orders = [];
$orderCounts = [
    'all' => 0,
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0
];

if ($db) {
    try {
        // Get order counts by status
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count 
            FROM orders 
            WHERE user_id = ? 
            GROUP BY status
        ");
        $stmt->execute([$currentUser['id']]);
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($counts as $count) {
            $orderCounts[$count['status']] = $count['count'];
            $orderCounts['all'] += $count['count'];
        }
        
        // Get orders
        $sql = "SELECT * FROM orders WHERE user_id = ?";
        $params = [$currentUser['id']];
        
        if (!empty($statusFilter)) {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order items for each order
        foreach ($orders as $key => $order) {
            $stmt = $db->prepare("
                SELECT oi.*, p.name as product_name, p.slug as product_slug, p.images as product_images
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order['id']]);
            $orders[$key]['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    // Helper function to get product image URL
    function getProductImageUrl($productImages) {
        if (empty($productImages)) {
            return '';
        }
        $imageParts = explode(',', $productImages);
        $firstImage = trim($imageParts[0]);
        if (empty($firstImage)) {
            return '';
        }
        // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
        $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $firstImage);
        $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
        // Trim leading slash for url() helper if it's there
        $dbPath = ltrim($dbPath, '/');
        return url($dbPath);
    }
        
    } catch (Exception $e) {
        error_log("Error fetching orders: " . $e->getMessage());
    }
}

$pageTitle = "My Orders";
$currentPage = "orders";

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-green-100 text-sm">Track and manage all your orders</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-shopping-bag text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Orders -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Orders Header -->
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">My Orders</h1>
                        <a href="<?= shopUrl('/') ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                        </a>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="p-6">
                    <!-- Order Status Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <a href="<?= userUrl('/orders/') ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= empty($statusFilter) ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            All Orders (<?= $orderCounts['all'] ?>)
                        </a>
                        <a href="<?= userUrl('/orders/?status=pending') ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            Pending (<?= $orderCounts['pending'] ?>)
                        </a>
                        <a href="<?= userUrl('/orders/?status=processing') ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'processing' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            Processing (<?= $orderCounts['processing'] ?>)
                        </a>
                        <a href="<?= userUrl('/orders/?status=shipped') ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'shipped' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            Shipped (<?= $orderCounts['shipped'] ?>)
                        </a>
                        <a href="<?= userUrl('/orders/?status=delivered') ?>" 
                           class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'delivered' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                            Delivered (<?= $orderCounts['delivered'] ?>)
                        </a>
                    </div>

                    <?php if (empty($orders)): ?>
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-box-open text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">
                                <?= empty($statusFilter) ? 'No orders yet' : 'No ' . $statusFilter . ' orders' ?>
                            </h3>
                            <p class="text-gray-500 mb-6">
                                <?= empty($statusFilter) ? 'When you place your first order, it will appear here.' : 'Orders with this status will appear here.' ?>
                            </p>
                            <?php if (empty($statusFilter)): ?>
                                <a href="<?= shopUrl('/') ?>" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                                    <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                                </a>
                            <?php else: ?>
                                <a href="<?= userUrl('/orders/') ?>" class="text-green-600 hover:text-green-700 font-medium">
                                    View all orders <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Orders List -->
                        <div class="space-y-4">
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $statusStyles = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'shipped' => 'bg-purple-100 text-purple-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $statusStyle = $statusStyles[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                
                                $statusIcons = [
                                    'pending' => 'fa-clock',
                                    'processing' => 'fa-cog fa-spin',
                                    'shipped' => 'fa-truck',
                                    'delivered' => 'fa-check-double',
                                    'cancelled' => 'fa-times'
                                ];
                                $statusIcon = $statusIcons[$order['status']] ?? 'fa-question';
                                
                                $itemCount = 0;
                                foreach ($order['items'] as $item) {
                                    $itemCount += $item['quantity'];
                                }
                                ?>
                                <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                                    <!-- Order Header -->
                                    <div class="bg-gray-50 px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center space-x-4 mb-2 md:mb-0">
                                            <div>
                                                <span class="text-sm text-gray-500">Order #</span>
                                                <span class="font-bold text-gray-900"><?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                            </div>
                                            <span class="text-gray-300">|</span>
                                            <div class="text-sm text-gray-500">
                                                <?= date('F j, Y', strtotime($order['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusStyle ?>">
                                                <i class="fas <?= $statusIcon ?> mr-1"></i>
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                            <?php if ($order['payment_status'] === 'awaiting_payment'): ?>
                                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Payment Pending
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Items Preview -->
                                    <div class="px-6 py-4">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                                <!-- Product Images -->
                                                <div class="flex -space-x-2">
                                                    <?php
                                                    $displayItems = array_slice($order['items'], 0, 3);
                                                    foreach ($displayItems as $item):
                                                        $imageUrl = getProductImageUrl($item['product_images'] ?? '');
                                                    ?>
                                                        <div class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border-2 border-white flex items-center justify-center overflow-hidden">
                                                            <?php if (!empty($imageUrl)): ?>
                                                                <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" class="w-full h-full object-cover">
                                                            <?php else: ?>
                                                                <span class="text-lg">📦</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (count($order['items']) > 3): ?>
                                                        <div class="w-12 h-12 bg-gray-100 rounded-lg border-2 border-white flex items-center justify-center">
                                                            <span class="text-xs font-bold text-gray-500">+<?= count($order['items']) - 3 ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div>
                                                    <p class="font-medium text-gray-900"><?= $itemCount ?> item<?= $itemCount > 1 ? 's' : '' ?></p>
                                                    <p class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($order['items'][0]['product_name'] ?? 'Product') ?>
                                                        <?php if (count($order['items']) > 1): ?>
                                                            and <?= count($order['items']) - 1 ?> more
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-between md:justify-end md:space-x-6">
                                                <div class="text-right">
                                                    <p class="text-sm text-gray-500">Total</p>
                                                    <p class="text-xl font-bold text-green-600">R <?= number_format($order['total_amount'], 2) ?></p>
                                                </div>
                                                
                                                <div class="flex items-center space-x-2">
                                                    <a href="<?= userUrl('/orders/view.php?id=' . $order['id']) ?>" 
                                                       class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                                        View Details
                                                    </a>
                                                    <?php if ($order['status'] === 'shipped'): ?>
                                                        <button class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                                                            <i class="fas fa-map-marker-alt mr-1"></i>Track
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Progress (for active orders) -->
                                    <?php if (in_array($order['status'], ['pending', 'processing', 'shipped'])): ?>
                                        <div class="px-6 pb-4">
                                            <div class="relative">
                                                <div class="flex items-center justify-between">
                                                    <?php
                                                    $steps = ['pending', 'processing', 'shipped', 'delivered'];
                                                    $currentStep = array_search($order['status'], $steps);
                                                    foreach ($steps as $index => $step):
                                                        $isCompleted = $index <= $currentStep;
                                                        $isCurrent = $index === $currentStep;
                                                    ?>
                                                        <div class="flex flex-col items-center">
                                                            <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $isCompleted ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-400' ?> <?= $isCurrent ? 'ring-4 ring-green-100' : '' ?>">
                                                                <?php if ($isCompleted && !$isCurrent): ?>
                                                                    <i class="fas fa-check text-xs"></i>
                                                                <?php else: ?>
                                                                    <?= $index + 1 ?>
                                                                <?php endif; ?>
                                                            </div>
                                                            <span class="text-xs mt-1 <?= $isCompleted ? 'text-green-600 font-medium' : 'text-gray-400' ?>">
                                                                <?= ucfirst($step) ?>
                                                            </span>
                                                        </div>
                                                        <?php if ($index < count($steps) - 1): ?>
                                                            <div class="flex-1 h-1 mx-2 <?= $index < $currentStep ? 'bg-green-600' : 'bg-gray-200' ?>"></div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
