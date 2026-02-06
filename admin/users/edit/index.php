<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get user ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    sessionFlash('error', 'Invalid user ID');
    redirect('/admin/users/');
}

// Get user details
$user = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = 'User not found';
            redirect('/admin/users/');
        }
    } catch (Exception $e) {
        error_log("Error getting user: " . $e->getMessage());
        $_SESSION['error'] = 'Error loading user';
        redirect('/admin/users/');
    }
}

$message = '';
$error = '';

// Check for session messages from redirect
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle form submission
if ($_POST && $adminAuth && $db) {
    try {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? null);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($firstName) || empty($lastName) || empty($email)) {
            throw new Exception('First name, last name, and email are required');
        }

        // Check if email exists for another user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists for another user');
        }

        // Update user
        $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$firstName, $lastName, $email, $phone, $dateOfBirth ?: null, $isActive, $userId]);

        if ($result) {
            $_SESSION['success'] = 'User updated successfully!';
            redirect('/admin/users/edit/?id=' . $userId);
        } else {
            throw new Exception('Failed to update user');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error updating user: " . $e->getMessage());
    }
}

// Handle Admin Password Actions
if ($_POST && isset($_POST['action']) && $adminAuth && $db) {
    try {
        require_once __DIR__ . '/../../../includes/email_service.php';
        $emailService = new EmailService($db);
        $userAuth = new UserAuth($db);
        $action = $_POST['action'];
        
        if ($action === 'send_reset_email') {
            $token = $userAuth->createPasswordResetToken($user['email']);
            if ($token) {
                $resetUrl = url('/user/reset-password/?token=' . $token);
                $emailService->send($user['email'], $user['first_name'], 'Reset Your Password', '', '', 'password_reset', [
                    'first_name' => $user['first_name'],
                    'reset_url' => $resetUrl
                ]);
                $_SESSION['success'] = 'Password reset email sent to ' . $user['email'];
            } else {
                throw new Exception('Failed to create reset token');
            }
        } 
        elseif ($action === 'generate_temp_pass') {
            $tempPass = $userAuth->generateTemporaryPassword($userId);
            if ($tempPass) {
                $emailService->send($user['email'], $user['first_name'], 'Your Temporary Password', '', '', 'temporary_password', [
                    'first_name' => $user['first_name'],
                    'temp_password' => $tempPass,
                    'login_url' => url('/login/')
                ]);
                $_SESSION['success'] = 'Temporary password generated and emailed to ' . $user['email'];
            } else {
                throw new Exception('Failed to generate temporary password');
            }
        }
        
        redirect('/admin/users/edit/?id=' . $userId);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error in admin user action: " . $e->getMessage());
    }
}

// Display success/error messages
$messageHtml = '';
if ($message) {
    $messageHtml = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . safe_html($message) . '</p>
            </div>
        </div>
    </div>';
} elseif ($error) {
    $messageHtml = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . safe_html($error) . '</p>
            </div>
        </div>
    </div>';
}

// Generate edit user content
$content = '
<div class="w-full max-w-4xl mx-auto">
    ' . $messageHtml . '

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <a href="' . adminUrl('/users/') . '" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Edit User</h1>
            <p class="text-gray-600 mt-1">Update user information</p>
        </div>
    </div>

    <form method="POST">
        ' . csrf_field() . '
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">User Information</h2>
            </div>
            <div class="px-6 py-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="first_name" required value="' . safe_html($user['first_name'] ?? '') . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter first name">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="last_name" required value="' . safe_html($user['last_name'] ?? '') . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter last name">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" name="email" required value="' . safe_html($user['email'] ?? '') . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="user@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" name="phone" value="' . safe_html($user['phone'] ?? '') . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="+27 12 345 6789">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="' . safe_html($user['date_of_birth'] ?? '') . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Account Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">User ID:</span>
                            <span class="ml-2 text-gray-900">' . (int)$user['id'] . '</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Registered:</span>
                            <span class="ml-2 text-gray-900">' . date('M j, Y g:i A', strtotime($user['created_at'])) . '</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" ' . (($user['is_active'] ?? 0) == 1 ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Active Account</strong><br>
                        <span class="text-gray-500">Inactive users cannot log in</span>
                    </label>
                </div>

                <div class="pt-6 border-t border-gray-100 flex flex-wrap gap-4">
                    <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to send a password reset email?\')">
                        <input type="hidden" name="action" value="send_reset_email">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>Send Reset Email
                        </button>
                    </form>

                    <form method="POST" class="inline" onsubmit="return confirm(\'Generate a new temporary password and email it to the user?\')">
                        <input type="hidden" name="action" value="generate_temp_pass">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-blue-100 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                            <i class="fas fa-key mr-2"></i>Generate Temp Password
                        </button>
                    </form>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="' . adminUrl('/users/') . '" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update User
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// Password reset confirmation
function resetPassword() {
    if (confirm("Are you sure you want to send a password reset email to this user?")) {
        // In a real implementation, this would trigger a password reset email
        alert("Password reset email would be sent to: ' . safe_html($user['email']) . '");
    }
}
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Edit User', $content, 'users');
?>
