<?php
// Gift Voucher Management Page
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../includes/bootstrap.php';

// Require voucher service
require_once __DIR__ . '/../includes/voucher_service.php';

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
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $createdFor = !empty($_POST['created_for']) ? (int)$_POST['created_for'] : null;
        $expiresAt = $_POST['expires_at'] ?? null;
        $maxUses = (int)($_POST['max_uses'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');

        if (empty($code) || $amount <= 0) {
            $error = 'Voucher code and amount are required';
        } else {
            $data = [
                'code' => $code,
                'amount' => $amount,
                'description' => $description,
                'created_by' => $_SESSION['admin_id'],
                'created_for' => $createdFor,
                'expires_at' => $expiresAt,
                'max_uses' => $maxUses,
                'notes' => $notes
            ];

            if (createVoucher($db, $data)) {
                $message = 'Voucher created successfully!';
            } else {
                $error = 'Error creating voucher. Code may already exist.';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $voucher = getVoucherById($db, $id);

        if ($voucher) {
            $data = [
                'code' => strtoupper(trim($_POST['code'] ?? '')),
                'amount' => (float)($_POST['amount'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
                'expires_at' => $_POST['expires_at'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'max_uses' => (int)($_POST['max_uses'] ?? 1),
                'notes' => trim($_POST['notes'] ?? '')
            ];

            if (updateVoucher($db, $id, $data)) {
                $message = 'Voucher updated successfully!';
            } else {
                $error = 'Error updating voucher';
            }
        } else {
            $error = 'Voucher not found';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if (deleteVoucher($db, $id)) {
            $message = 'Voucher deleted successfully!';
        } else {
            $error = 'Error deleting voucher';
        }
    }
}

// Handle delete via GET
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (deleteVoucher($db, $id)) {
        $_SESSION['success'] = 'Voucher deleted successfully!';
    } else {
        $_SESSION['error'] = 'Error deleting voucher';
    }
    redirect('/admin/vouchers.php');
}

// Get all vouchers
$vouchers = getAllVouchers($db, 100);

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

$totalVouchers = count($vouchers);
$activeVouchers = count(array_filter($vouchers, fn($v) => $v['is_active']));
$totalRedeemed = array_sum(array_column($vouchers, 'total_redeemed'));
$pendingValue = array_sum(array_map(fn($v) => $v['amount'] * max(0, $v['max_uses'] - $v['current_uses']), $vouchers));

$content .= '
<div class="w-full">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gift Vouchers</h1>
            <p class="text-gray-600 mt-1">Manage gift vouchers and promotional codes</p>
        </div>
        <button onclick="showCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center">
            <i class="fas fa-plus mr-2"></i>Create Voucher
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Vouchers</p>
                    <p class="text-2xl font-bold text-gray-900">' . $totalVouchers . '</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-gift text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Vouchers</p>
                    <p class="text-2xl font-bold text-green-600">' . $activeVouchers . '</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Redeemed</p>
                    <p class="text-2xl font-bold text-purple-600">R' . number_format($totalRedeemed, 2) . '</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-purple-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Value</p>
                    <p class="text-2xl font-bold text-yellow-600">R' . number_format($pendingValue, 2) . '</p>
                </div>
                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uses</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Redeemed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

if (empty($vouchers)) {
    $content .= '
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-gift text-4xl mb-3 text-gray-300"></i>
                            <p>No vouchers found. Create your first voucher!</p>
                        </td>
                    </tr>';
} else {
    foreach ($vouchers as $voucher) {
        $rowClass = !$voucher['is_active'] ? 'bg-gray-50' : '';
        $expired = $voucher['expires_at'] && strtotime($voucher['expires_at']) < time();
        $fullyUsed = $voucher['current_uses'] >= $voucher['max_uses'];

        if ($voucher['is_active']) {
            if ($expired) {
                $statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>';
            } elseif ($fullyUsed) {
                $statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Fully Used</span>';
            } else {
                $statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>';
            }
        } else {
            $statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>';
        }

        $expiresDisplay = $voucher['expires_at']
            ? date('M j, Y', strtotime($voucher['expires_at']))
            : '<span class="text-gray-400">Never</span>';

        $content .= '
                    <tr class="' . $rowClass . '">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-blue-600">' . htmlspecialchars($voucher['code']) . '</span>
                            ' . ($voucher['created_for'] ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">User-specific</span>' : '') . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-green-600">R' . number_format($voucher['amount'], 2) . '</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($voucher['description'] ?: '-') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ' . $statusBadge . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            ' . $voucher['current_uses'] . ' / ' . $voucher['max_uses'] . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            R' . number_format($voucher['total_redeemed'] ?? 0, 2) . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            ' . $expiresDisplay . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editVoucher(' . htmlspecialchars(json_encode($voucher)) . ')" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this voucher?\');">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="' . $voucher['id'] . '">
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

<!-- Create Voucher Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-medium text-gray-900">Create New Gift Voucher</h3>
                <button onclick="hideCreateModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="mt-4 space-y-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="create">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Voucher Code *</label>
                    <input type="text" id="code" name="code" placeholder="GIFT-100-SUMMER" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 uppercase font-bold">
                    <p class="text-xs text-gray-500 mt-1">Enter a unique code for this voucher</p>
                </div>
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (R) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" id="description" name="description" placeholder="Birthday gift voucher"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="created_for" class="block text-sm font-medium text-gray-700 mb-1">Created For (User ID)</label>
                    <input type="number" id="created_for" name="created_for" placeholder="Leave empty for anyone to use"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-500 mt-1">Enter a user ID to create voucher for specific user only</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                        <input type="datetime-local" id="expires_at" name="expires_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-1">Max Uses</label>
                        <input type="number" id="max_uses" name="max_uses" value="1" min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex justify-end pt-4 border-t">
                    <button type="button" onclick="hideCreateModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mr-3">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Create Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Voucher Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-medium text-gray-900">Edit Gift Voucher</h3>
                <button onclick="hideEditModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="mt-4 space-y-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_code" class="block text-sm font-medium text-gray-700 mb-1">Voucher Code *</label>
                    <input type="text" id="edit_code" name="code" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 uppercase font-bold">
                </div>
                <div>
                    <label for="edit_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (R) *</label>
                    <input type="number" id="edit_amount" name="amount" step="0.01" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" id="edit_description" name="description"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                        <input type="datetime-local" id="edit_expires_at" name="expires_at"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <div class="flex items-center h-full pt-6">
                            <input type="checkbox" id="edit_is_active" name="is_active" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="edit_is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="edit_notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="edit_notes" name="notes" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex justify-end pt-4 border-t">
                    <button type="button" onclick="hideEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mr-3">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Voucher</button>
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

function editVoucher(voucher) {
    document.getElementById("edit_id").value = voucher.id;
    document.getElementById("edit_code").value = voucher.code;
    document.getElementById("edit_amount").value = voucher.amount;
    document.getElementById("edit_description").value = voucher.description || "";
    document.getElementById("edit_expires_at").value = voucher.expires_at ? voucher.expires_at.slice(0, 16) : "";
    document.getElementById("edit_is_active").checked = voucher.is_active == 1;
    document.getElementById("edit_notes").value = voucher.notes || "";
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

echo adminSidebarWrapper('Gift Vouchers', $content, 'vouchers');
