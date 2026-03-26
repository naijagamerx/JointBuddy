<?php
/**
 * User Returns Dashboard - CannaBuddy
 * Display user's return requests and eligible orders
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

$returns = [];
$eligibleOrders = [];
$returnCounts = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0,
    'received' => 0,
    'refunded' => 0
];

// Get return settings
$settings = [];
try {

    // Fetch settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE category = 'returns'");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

$eligibilityDays = isset($settings['return_eligibility_days']) ? (int)$settings['return_eligibility_days'] : 14;

try {
    // Get return counts by status
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count
            FROM returns
            WHERE user_id = ?
            GROUP BY status
        ");
        $stmt->execute([$currentUser['id']]);
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($counts as $count) {
            $returnCounts[$count['status']] = $count['count'];
            $returnCounts['all'] += $count['count'];
        }

        // Get user's returns
        $stmt = $db->prepare("
            SELECT r.*, o.order_number
            FROM returns r
            JOIN orders o ON r.order_id = o.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$currentUser['id']]);
        $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get return items for each return
        foreach ($returns as $key => $return) {
            $stmt = $db->prepare("
                SELECT ri.*, oi.product_name, oi.unit_price, p.images as product_images
                FROM return_items ri
                JOIN order_items oi ON ri.order_item_id = oi.id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE ri.return_id = ?
            ");
            $stmt->execute([$return['id']]);
            $returns[$key]['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get eligible orders (delivered, within eligibility window, no active return)
        $stmt = $db->prepare("
            SELECT o.*,
                   DATEDIFF(NOW(), o.updated_at) as days_since_delivery,
                   (SELECT COUNT(*) FROM returns WHERE order_id = o.id AND status NOT IN ('cancelled', 'rejected')) as has_active_return
            FROM orders o
            WHERE o.user_id = ?
              AND o.status = 'delivered'
              AND DATEDIFF(NOW(), o.updated_at) <= ?
        ");
        $stmt->execute([$currentUser['id'], $eligibilityDays]);
        $allDelivered = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter to only orders without active returns
        foreach ($allDelivered as $order) {
            if ($order['has_active_return'] == 0) {
                // Get order items for display
                $itemStmt = $db->prepare("
                    SELECT oi.*, p.images as product_images, p.id as product_id
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                ");
                $itemStmt->execute([$order['id']]);
                $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                $eligibleOrders[] = $order;
            }
        }

    } catch (Exception $e) {
        error_log("Error fetching returns: " . $e->getMessage());
    }

// Helper function for status badge
function getReturnStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'received' => 'bg-purple-100 text-purple-800',
        'refunded' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-800'
    ];
    $labels = [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'received' => 'Item Received',
        'refunded' => 'Refunded',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled'
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . $label . '</span>';
}

// Helper function for product image
function getProductImageUrl($images, $productId) {
    if (!empty($images)) {
        $imageParts = explode(',', $images);
        $firstImage = trim($imageParts[0]);
        if (!empty($firstImage)) {
            // Remove full URL pattern if present (e.g. if DB has http://...)
            $imagePath = parse_url($firstImage, PHP_URL_PATH);
            
            // Fallback if parse_url fails or returns null (simple relative path)
            if (!$imagePath) {
                $imagePath = $firstImage;
            }
            
            // Remove potential base path prefix if it exists
            $basePath = getAppBasePath(); // e.g. /CannaBuddy.shop
            if ($basePath && strpos($imagePath, $basePath) === 0) {
                $imagePath = substr($imagePath, strlen($basePath));
            }
            
            // Clean leading slashes
            $imagePath = ltrim($imagePath, '/');
            
            return url($imagePath);
        }
    }
    return assetUrl('images/products/placeholder.png'); // Corrected path to placeholder
}

$pageTitle = "My Returns";
$currentPage = "returns";

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-green-100 text-sm">Manage your return requests</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-undo text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Returns -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Header -->
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">My Returns</h1>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('returns')"
                                class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-green-600 text-green-600"
                                data-tab="returns">
                            My Returns (<?= $returnCounts['all'] ?>)
                        </button>
                        <button onclick="switchTab('eligible')"
                                class="tab-btn px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                data-tab="eligible">
                            Eligible Orders (<?= count($eligibleOrders) ?>)
                        </button>
                    </nav>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- My Returns Tab -->
                    <div id="tab-returns" class="tab-content">
                        <?php if (empty($returns)): ?>
                            <!-- Empty State -->
                            <div class="text-center py-12">
                                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-undo text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Return Requests</h3>
                                <p class="text-gray-500 mb-6">You haven't submitted any return requests yet.</p>
                            </div>
                        <?php else: ?>
                            <!-- Returns List -->
                            <div class="space-y-4">
                                <?php foreach ($returns as $return): ?>
                                    <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                                        <!-- Header -->
                                        <div class="bg-gray-50 px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div>
                                                    <span class="text-sm text-gray-500">Return #</span>
                                                    <span class="font-bold text-gray-900"><?= htmlspecialchars($return['return_number']) ?></span>
                                                </div>
                                                <span class="text-gray-300">|</span>
                                                <div class="text-sm text-gray-500">
                                                    Order #<?= htmlspecialchars($return['order_number']) ?>
                                                </div>
                                                <span class="text-gray-300">|</span>
                                                <div class="text-sm text-gray-500">
                                                    <?= date('F j, Y', strtotime($return['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-3 mt-3 md:mt-0">
                                                <?= getReturnStatusBadge($return['status']) ?>
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="px-6 py-4">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                                <!-- Products -->
                                                <div class="flex items-center space-x-4">
                                                    <?php
                                                    $itemCount = count($return['items'] ?? []);
                                                    $displayItems = array_slice($return['items'] ?? [], 0, 3);
                                                    ?>
                                                    <div class="flex -space-x-2">
                                                        <?php foreach ($displayItems as $item): ?>
                                                            <div class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border-2 border-white flex items-center justify-center overflow-hidden">
                                                                <img src="<?= getProductImageUrl($item['product_images'] ?? '', $item['product_id'] ?? '') ?>"
                                                                     class="w-full h-full object-cover"
                                                                     alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if ($itemCount > 3): ?>
                                                            <div class="w-12 h-12 bg-gray-100 rounded-lg border-2 border-white flex items-center justify-center">
                                                                <span class="text-xs text-gray-600 font-medium">+<?= $itemCount - 3 ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600">
                                                            <?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?> •
                                                            Reason: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $return['reason_type']))) ?>
                                                        </p>
                                                        <?php if (!empty($return['total_amount'])): ?>
                                                            <p class="text-lg font-bold text-green-600">
                                                                R <?= number_format($return['total_amount'], 2) ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="mt-4 md:mt-0">
                                                    <a href="<?= userUrl('/returns/view.php?id=' . $return['id']) ?>"
                                                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                                        <i class="fas fa-eye mr-2"></i>View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Eligible Orders Tab -->
                    <div id="tab-eligible" class="tab-content hidden">
                        <?php if (empty($eligibleOrders)): ?>
                            <!-- Empty State -->
                            <div class="text-center py-12">
                                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-box-open text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Eligible Orders</h3>
                                <p class="text-gray-500 mb-6">You don't have any orders eligible for return within the <?= $eligibilityDays ?>-day window.</p>
                                <a href="<?= shopUrl('/') ?>"
                                   class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                                    <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Eligible Orders List -->
                            <div class="space-y-4">
                                <?php foreach ($eligibleOrders as $order): ?>
                                    <?php
                                    $daysSince = (int)$order['days_since_delivery'];
                                    $daysRemaining = max(0, $eligibilityDays - $daysSince);
                                    ?>
                                    <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                                        <!-- Header -->
                                        <div class="bg-gray-50 px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div>
                                                    <span class="text-sm text-gray-500">Order #</span>
                                                    <span class="font-bold text-gray-900"><?= htmlspecialchars($order['order_number']) ?></span>
                                                </div>
                                                <span class="text-gray-300">|</span>
                                                <div class="text-sm text-gray-500">
                                                    <?= date('F j, Y', strtotime($order['created_at'])) ?>
                                                </div>
                                                <span class="text-gray-300">|</span>
                                                <div class="text-sm text-gray-500">
                                                    Delivered <?= $daysSince ?> day<?= $daysSince !== 1 ? 's' : '' ?> ago
                                                </div>
                                            </div>
                                            <div class="mt-3 md:mt-0">
                                                <?php if ($daysRemaining <= 3): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i><?= $daysRemaining ?> day<?= $daysRemaining !== 1 ? 's' : '' ?> left
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check mr-1"></i><?= $daysRemaining ?> days to return
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="px-6 py-4">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                                <!-- Products -->
                                                <div class="flex items-center space-x-4">
                                                    <?php
                                                    $itemCount = count($order['items'] ?? []);
                                                    $displayItems = array_slice($order['items'] ?? [], 0, 3);
                                                    ?>
                                                    <div class="flex -space-x-2">
                                                        <?php foreach ($displayItems as $item): ?>
                                                            <div class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border-2 border-white flex items-center justify-center overflow-hidden">
                                                                <img src="<?= getProductImageUrl($item['product_images'] ?? '', $item['product_id'] ?? '') ?>"
                                                                     class="w-full h-full object-cover"
                                                                     alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if ($itemCount > 3): ?>
                                                            <div class="w-12 h-12 bg-gray-100 rounded-lg border-2 border-white flex items-center justify-center">
                                                                <span class="text-xs text-gray-600 font-medium">+<?= $itemCount - 3 ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm text-gray-600"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?></p>
                                                        <p class="text-lg font-bold text-gray-900">R <?= number_format($order['total_amount'], 2) ?></p>
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="mt-4 md:mt-0">
                                                    <a href="<?= userUrl('/returns/eligibility.php?order_id=' . $order['id']) ?>"
                                                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                                        <i class="fas fa-undo mr-2"></i>Start Return
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Update button styles
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.dataset.tab === tab) {
            btn.classList.remove('border-transparent', 'text-gray-500');
            btn.classList.add('border-green-600', 'text-green-600');
        } else {
            btn.classList.remove('border-green-600', 'text-green-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });

    // Show/hide content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
}
</script>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>
