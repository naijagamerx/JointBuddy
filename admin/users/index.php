<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    CsrfMiddleware::validate();
    $deleteId = (int)$_POST['id'];
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);
            $_SESSION['success'] = 'User deleted successfully!';
            redirect('/admin/users/');
        } catch (Exception $e) {
            $_SESSION['admin_error'] = AppError::handleDatabaseError($e, 'Error deleting user');
        }
    }
}

// Get all users
$users = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        AppError::handleDatabaseError($e, 'Error fetching users');
    }
}

// Check for error messages
$errorMessage = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);

// Check for success messages
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Generate users content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Success Message -->
    ' . ($successMessage ? '
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>' . safe_html($successMessage) . '
    </div>
    ' : '') . '

    <!-- Error Message -->
    ' . ($errorMessage ? '
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>' . safe_html($errorMessage) . '
    </div>
    ' : '') . '

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Users</h1>
        <p class="text-gray-600 mt-1">Manage customer accounts (' . count($users) . ' users)</p>
    </div>

    <!-- Users Table -->';

if (empty($users)) {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No users yet</h3>
        <p class="text-gray-600">Users will appear here when they register</p>
    </div>';
} else {
    $content .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    $rowNum = 1;
    foreach ($users as $user) {
        $content .= '
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            ' . $rowNum . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    ' . safe_html(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) . '
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">' . safe_html($user['email'] ?? 'N/A') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">' . safe_html($user['phone'] ?? 'N/A') . '</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ' . date('M j, Y', strtotime($user['created_at'] ?? 'now')) . '
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="' . adminUrl('/users/view/' . $user['id']) . '" class="w-8 h-8 flex items-center justify-center rounded-full bg-green-100 text-green-600 hover:bg-green-200 transition-colors" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="' . adminUrl('/users/edit/?id=' . $user['id']) . '" class="w-8 h-8 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this user? This action cannot be undone.\')">
                                    ' . csrf_field() . '
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="' . $user['id'] . '">
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition-colors" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>';
        $rowNum++;
    }
    
    $content .= '
                </tbody>
            </table>
        </div>
    </div>';
}

$content .= '
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('Users', $content, 'users');
