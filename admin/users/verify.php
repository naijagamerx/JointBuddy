<?php
/**
 * Verify User Email
 * Admin can manually verify a user's email
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection
$db = Services::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    CsrfMiddleware::validate();

    $userId = (int)$_POST['id'];
    $action = $_POST['action'] ?? 'verify';

    try {
        if ($action === 'verify') {
            $stmt = $db->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['success'] = 'User email verified successfully!';
        } elseif ($action === 'unverify') {
            $stmt = $db->prepare("UPDATE users SET email_verified = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['success'] = 'User email unverified.';
        }

        redirect('/admin/users/');
    } catch (Exception $e) {
        $_SESSION['admin_error'] = 'Error updating user: ' . $e->getMessage();
        redirect('/admin/users/');
    }
}

// GET request - show verify page
$userId = (int)($_GET['id'] ?? 0);

if ($userId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['admin_error'] = 'User not found.';
            redirect('/admin/users/');
        }
    } catch (Exception $e) {
        $_SESSION['admin_error'] = 'Error fetching user: ' . $e->getMessage();
        redirect('/admin/users/');
    }
} else {
    $_SESSION['admin_error'] = 'Invalid user ID.';
    redirect('/admin/users/');
}

// Check for error messages
$errorMessage = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);

// Check for success messages
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Generate content
$content = '
<div class="w-full max-w-4xl mx-auto">
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
        <h1 class="text-3xl font-bold text-gray-900">Verify User Email</h1>
        <p class="text-gray-600 mt-1">Manage email verification status for users</p>
    </div>

    <!-- User Info Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-user text-gray-500 text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">' . safe_html($user['first_name'] . ' ' . $user['last_name']) . '</h2>
                <p class="text-gray-600">' . safe_html($user['email']) . '</p>
                <p class="text-sm text-gray-500">ID: ' . $user['id'] . '</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Email Verified:</span>
                <span class="ml-2 font-medium ' . ($user['email_verified'] ? 'text-green-600' : 'text-red-600') . '">
                    ' . ($user['email_verified'] ? 'Yes' : 'No') . '
                </span>
            </div>
            <div>
                <span class="text-gray-500">Phone Verified:</span>
                <span class="ml-2 font-medium ' . ($user['phone_verified'] ? 'text-green-600' : 'text-red-600') . '">
                    ' . ($user['phone_verified'] ? 'Yes' : 'No') . '
                </span>
            </div>
            <div>
                <span class="text-gray-500">Status:</span>
                <span class="ml-2 font-medium ' . ($user['is_active'] ? 'text-green-600' : 'text-red-600') . '">
                    ' . ($user['is_active'] ? 'Active' : 'Inactive') . '
                </span>
            </div>
            <div>
                <span class="text-gray-500">Joined:</span>
                <span class="ml-2 font-medium text-gray-900">
                    ' . date('M j, Y', strtotime($user['created_at'] ?? 'now')) . '
                </span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Actions</h3>

        <div class="flex flex-wrap gap-3">
            <form method="POST" onsubmit="return confirm(\'Are you sure you want to verify this user email?\');" class="inline">
                ' . csrf_field() . '
                <input type="hidden" name="id" value="' . $user['id'] . '">
                <input type="hidden" name="action" value="verify">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check-circle mr-2"></i>Verify Email
                </button>
            </form>

            <form method="POST" onsubmit="return confirm(\'Are you sure you want to unverify this user email?\');" class="inline">
                ' . csrf_field() . '
                <input type="hidden" name="id" value="' . $user['id'] . '">
                <input type="hidden" name="action" value="unverify">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times-circle mr-2"></i>Unverify Email
                </button>
            </form>

            <a href="' . adminUrl('/users/') . '" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
        </div>
    </div>
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('Verify User Email', $content, 'users');
