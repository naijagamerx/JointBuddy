<?php
// Coupon Management Page
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CsrfMiddleware::validate();
    $action = $_POST['action'];

    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
        $maxDiscountAmount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
        $startsAt = $_POST['starts_at'] ?? null;
        $expiresAt = $_POST['expires_at'] ?? null;
        $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
        $usagePerUser = !empty($_POST['usage_per_user']) ? (int)$_POST['usage_per_user'] : null;
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($code) || $discountValue <= 0) {
            $error = 'Coupon code and discount value are required';
        } else {
            try {
                $sql = "INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount,
                        max_discount_amount, starts_at, expires_at, usage_limit, usage_per_user, active)
                        VALUES (:code, :description, :discount_type, :discount_value, :min_order_amount,
                                :max_discount_amount, :starts_at, :expires_at, :usage_limit, :usage_per_user, :active)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':code' => $code,
                    ':description' => $description,
                    ':discount_type' => $discountType,
                    ':discount_value' => $discountValue,
                    ':min_order_amount' => $minOrderAmount,
                    ':max_discount_amount' => $maxDiscountAmount,
                    ':starts_at' => $startsAt,
                    ':expires_at' => $expiresAt,
                    ':usage_limit' => $usageLimit,
                    ':usage_per_user' => $usagePerUser,
                    ':active' => $active
                ]);
                $message = 'Coupon created successfully!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Coupon code already exists';
                } else {
                    $error = AppError::handleDatabaseError($e, 'Error creating coupon');
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
        $maxDiscountAmount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
        $startsAt = $_POST['starts_at'] ?? null;
        $expiresAt = $_POST['expires_at'] ?? null;
        $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
        $usagePerUser = !empty($_POST['usage_per_user']) ? (int)$_POST['usage_per_user'] : null;
        $active = isset($_POST['active']) ? 1 : 0;

        try {
            $sql = "UPDATE coupons SET code = :code, description = :description, discount_type = :discount_type,
                    discount_value = :discount_value, min_order_amount = :min_order_amount,
                    max_discount_amount = :max_discount_amount, starts_at = :starts_at, expires_at = :expires_at,
                    usage_limit = :usage_limit, usage_per_user = :usage_per_user, active = :active
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':code' => $code,
                ':description' => $description,
                ':discount_type' => $discountType,
                ':discount_value' => $discountValue,
                ':min_order_amount' => $minOrderAmount,
                ':max_discount_amount' => $maxDiscountAmount,
                ':starts_at' => $startsAt,
                ':expires_at' => $expiresAt,
                ':usage_limit' => $usageLimit,
                ':usage_per_user' => $usagePerUser,
                ':active' => $active,
                ':id' => $id
            ]);
            $message = 'Coupon updated successfully!';
        } catch (PDOException $e) {
            $error = AppError::handleDatabaseError($e, 'Error updating coupon');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Coupon deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Error deleting coupon';
        }
    }
}

// Handle delete via GET
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Coupon deleted successfully!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting coupon';
    }
    redirect('/admin/coupons.php');
}

// Get all coupons with usage stats
$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = c.id) as usage_count
        FROM coupons c
        ORDER BY c.created_at DESC";
$stmt = $db->query($sql);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build content
$content = '';

// Add alert if any
if ($message) {
    $content .= adminAlert($message, 'success');
}
if ($error) {
    $content .= adminAlert($error, 'error');
}
if (isset($_SESSION['success'])) {
    $content .= adminAlert($_SESSION['success'], 'success');
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $content .= adminAlert($_SESSION['error'], 'error');
    unset($_SESSION['error']);
}

$totalCoupons = count($coupons);
$activeCoupons = count(array_filter($coupons, fn($c) => $c['active']));
$totalUses = array_sum(array_column($coupons, 'usage_count'));
$expiredCount = count(array_filter($coupons, fn($c) => $c['expires_at'] && strtotime($c['expires_at']) < time()));

$content .= '
<div class="w-full">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Coupons</h1>
            <p class="text-gray-600 mt-1">Manage discount codes and promotions</p>
        </div>
        <button onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center">
            <i class="fas fa-plus mr-2"></i>Create Coupon
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Coupons</p>
                    <p class="text-2xl font-bold text-gray-900">' . $totalCoupons . '</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-ticket text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Coupons</p>
                    <p class="text-2xl font-bold text-green-600">' . $activeCoupons . '</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Uses</p>
                    <p class="text-2xl font-bold text-purple-600">' . $totalUses . '</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-purple-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-red-600">' . $expiredCount . '</p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

if (empty($coupons)) {
    $content .= '
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-ticket text-4xl mb-3 text-gray-300"></i>
                            <p>No coupons found. Create your first coupon!</p>
                        </td>
                    </tr>';
} else {
    foreach ($coupons as $coupon) {
        $now = time();
        $notStarted = $coupon['starts_at'] && strtotime($coupon['starts_at']) > $now;
        $expired = $coupon['expires_at'] && strtotime($coupon['expires_at']) < $now;
        $rowClass = !$coupon['active'] ? 'bg-gray-50' : '';

        if ($coupon['discount_type'] == 'percent') {
            $discountBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">' . $coupon['discount_value'] . '%</span>';
        } elseif ($coupon['discount_type'] == 'fixed') {
            $discountBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">R' . number_format($coupon['discount_value'], 2) . '</span>';
        } else {
            $discountBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Free Shipping</span>';
        }

        if ($notStarted) {
            $validBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Starts ' . date('M j', strtotime($coupon['starts_at'])) . '</span>';
        } elseif ($expired) {
            $validBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>';
        } elseif ($coupon['expires_at']) {
            $validBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Until ' . date('M j', strtotime($coupon['expires_at'])) . '</span>';
        } else {
            $validBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">No limit</span>';
        }

        $statusBadge = $coupon['active']
            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>'
            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>';

        $content .= '
                    <tr class="' . $rowClass . '">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-blue-600">' . htmlspecialchars($coupon['code']) . '</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($coupon['description']) . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $discountBadge . '
                            ' . ($coupon['max_discount_amount'] ? '<div class="text-xs text-gray-500 mt-1">(max R' . number_format($coupon['max_discount_amount'], 2) . ')</div>' : '') . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            ' . ($coupon['min_order_amount'] > 0 ? 'R' . number_format($coupon['min_order_amount'], 2) : '-') . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $validBadge . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            ' . $coupon['usage_count'] . ($coupon['usage_limit'] ? ' / ' . $coupon['usage_limit'] : '') . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $statusBadge . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editCoupon(' . htmlspecialchars(json_encode($coupon)) . ')" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this coupon?\');">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="' . $coupon['id'] . '">
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>';
    }
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Coupon Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-medium text-gray-900">Create New Coupon</h3>
                <button onclick="hideCreateModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="mt-4 space-y-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="create">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Coupon Code *</label>
                        <input type="text" id="code" name="code" placeholder="SUMMER2025" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 uppercase font-bold">
                    </div>
                    <div>
                        <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-1">Discount Type *</label>
                        <select id="discount_type" name="discount_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (R)</option>
                            <option value="free_shipping">Free Shipping</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-1">Discount Value *</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-1">Minimum Order</label>
                        <input type="number" id="min_order_amount" name="min_order_amount" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <input type="text" id="description" name="description" placeholder="e.g., 20% off all products" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="max_discount_amount" class="block text-sm font-medium text-gray-700 mb-1">Maximum Discount</label>
                    <input type="number" id="max_discount_amount" name="max_discount_amount" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="datetime-local" id="starts_at" name="starts_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                        <input type="datetime-local" id="expires_at" name="expires_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">Usage Limit</label>
                        <input type="number" id="usage_limit" name="usage_limit" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="usage_per_user" class="block text-sm font-medium text-gray-700 mb-1">Usage Per User</label>
                        <input type="number" id="usage_per_user" name="usage_per_user" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="active" name="active" checked class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="active" class="ml-2 block text-sm text-gray-700">Active (coupon can be used)</label>
                </div>
                <div class="flex justify-end pt-4 border-t">
                    <button type="button" onclick="hideCreateModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mr-3">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-medium text-gray-900">Edit Coupon</h3>
                <button onclick="hideEditModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="mt-4 space-y-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_code" class="block text-sm font-medium text-gray-700 mb-1">Coupon Code *</label>
                        <input type="text" id="edit_code" name="code" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 uppercase font-bold">
                    </div>
                    <div>
                        <label for="edit_discount_type" class="block text-sm font-medium text-gray-700 mb-1">Discount Type *</label>
                        <select id="edit_discount_type" name="discount_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (R)</option>
                            <option value="free_shipping">Free Shipping</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_discount_value" class="block text-sm font-medium text-gray-700 mb-1">Discount Value *</label>
                        <input type="number" id="edit_discount_value" name="discount_value" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="edit_min_order_amount" class="block text-sm font-medium text-gray-700 mb-1">Minimum Order</label>
                        <input type="number" id="edit_min_order_amount" name="min_order_amount" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <input type="text" id="edit_description" name="description" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="edit_max_discount_amount" class="block text-sm font-medium text-gray-700 mb-1">Maximum Discount</label>
                    <input type="number" id="edit_max_discount_amount" name="max_discount_amount" step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_starts_at" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="datetime-local" id="edit_starts_at" name="starts_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="edit_expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                        <input type="datetime-local" id="edit_expires_at" name="expires_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_usage_limit" class="block text-sm font-medium text-gray-700 mb-1">Usage Limit</label>
                        <input type="number" id="edit_usage_limit" name="usage_limit" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="edit_usage_per_user" class="block text-sm font-medium text-gray-700 mb-1">Usage Per User</label>
                        <input type="number" id="edit_usage_per_user" name="usage_per_user" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="edit_active" name="active" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="edit_active" class="ml-2 block text-sm text-gray-700">Active (coupon can be used)</label>
                </div>
                <div class="flex justify-end pt-4 border-t">
                    <button type="button" onclick="hideEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mr-3">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById("createModal").classList.remove("hidden");
}

function hideCreateModal() {
    document.getElementById("createModal").classList.add("hidden");
}

function editCoupon(coupon) {
    document.getElementById("edit_id").value = coupon.id;
    document.getElementById("edit_code").value = coupon.code;
    document.getElementById("edit_discount_type").value = coupon.discount_type;
    document.getElementById("edit_discount_value").value = coupon.discount_value;
    document.getElementById("edit_min_order_amount").value = coupon.min_order_amount;
    document.getElementById("edit_description").value = coupon.description;
    document.getElementById("edit_max_discount_amount").value = coupon.max_discount_amount || "";
    document.getElementById("edit_starts_at").value = coupon.starts_at ? coupon.starts_at.slice(0, 16) : "";
    document.getElementById("edit_expires_at").value = coupon.expires_at ? coupon.expires_at.slice(0, 16) : "";
    document.getElementById("edit_usage_limit").value = coupon.usage_limit || "";
    document.getElementById("edit_usage_per_user").value = coupon.usage_per_user || "";
    document.getElementById("edit_active").checked = coupon.active == 1;
    document.getElementById("editModal").classList.remove("hidden");
}

function hideEditModal() {
    document.getElementById("editModal").classList.add("hidden");
}

// Close modals on outside click
document.getElementById("createModal").addEventListener("click", function(e) {
    if (e.target === this) hideCreateModal();
});

document.getElementById("editModal").addEventListener("click", function(e) {
    if (e.target === this) hideEditModal();
});
</script>';

echo adminSidebarWrapper('Coupons', $content, 'coupons');
