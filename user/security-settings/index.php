<?php
/**
 * Security Settings Page
 *
 * Account security management
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();
$userData = [];
$userSessions = [];
$successMessage = '';
$errorMessage = '';

// Fetch user data from database
try {
    $db = Services::db();

    // Get user details
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, phone, password, password_changed_at, email_verified, phone_verified FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Get active sessions
    $stmt = $db->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
    $stmt->execute([$currentUser['id']]);
    $userSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching security data: " . $e->getMessage());
    $userData = [];
    $userSessions = [];
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    CsrfMiddleware::validate();
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    try {
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('All password fields are required.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('New passwords do not match.');
        }

        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }

        if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword)) {
            throw new Exception('Password must contain both uppercase and lowercase letters.');
        }

        if (!preg_match('/[0-9]/', $newPassword)) {
            throw new Exception('Password must contain at least one number.');
        }

        if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            throw new Exception('Password must contain at least one special character.');
        }

        // Verify current password
        if (!password_verify($currentPassword, $userData['password'])) {
            throw new Exception('Current password is incorrect.');
        }

        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $stmt = $db->prepare("UPDATE users SET password = ?, password_changed_at = NOW(), must_change_password = 0 WHERE id = ?");
        $stmt->execute([$newPasswordHash, $currentUser['id']]);

        $successMessage = 'Password updated successfully!';

        // Refresh user data
        $stmt = $db->prepare("SELECT id, email, first_name, last_name, phone, password, password_changed_at, email_verified, phone_verified FROM users WHERE id = ?");
        $stmt->execute([$currentUser['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle session revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke_session') {
    $sessionId = (int)($_POST['session_id'] ?? 0);

    try {
        // Don't allow revoking current session
        $currentSessionToken = session_id();
        $stmt = $db->prepare("SELECT session_token FROM user_sessions WHERE id = ? AND user_id = ?");
        $stmt->execute([$sessionId, $currentUser['id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session && $session['session_token'] === $currentSessionToken) {
            throw new Exception('You cannot revoke your current session.');
        }

        // Delete the session
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ?");
        $stmt->execute([$sessionId, $currentUser['id']]);

        $successMessage = 'Session revoked successfully!';

        // Refresh sessions list
        $stmt = $db->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
        $stmt->execute([$currentUser['id']]);
        $userSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle revoke all sessions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke_all_sessions') {
    try {
        // Delete all sessions except current
        $currentSessionToken = session_id();
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token != ?");
        $stmt->execute([$currentUser['id'], $currentSessionToken]);

        $deletedCount = $stmt->rowCount();
        $successMessage = "Revoked $deletedCount other session(s).";

        // Refresh sessions list
        $stmt = $db->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
        $stmt->execute([$currentUser['id']]);
        $userSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

$pageTitle = "Security Settings";
$currentPage = "security-settings";

// Helper function to parse user agent
function parseUserAgent($userAgent) {
    $device = 'Unknown Device';
    $browser = 'Unknown Browser';
    $os = 'Unknown OS';

    // Detect browser
    if (preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = 'Edge';
    }

    // Detect OS
    if (preg_match('/Windows/i', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/Mac/i', $userAgent)) {
        $os = 'macOS';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $os = 'Android';
        $device = 'Mobile';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
        $os = 'iOS';
        $device = 'Mobile';
    }

    // Detect mobile
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
        $device = 'Mobile';
    } else {
        $device = 'Desktop';
    }

    return ['browser' => $browser, 'os' => $os, 'device' => $device];
}

// Helper function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins == 1 ? '1 minute ago' : "$mins minutes ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? '1 hour ago' : "$hours hours ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days == 1 ? '1 day ago' : "$days days ago";
    } else {
        return date('M j, Y', $time);
    }
}

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                    <p class="text-red-100 text-sm">Manage your account security and authentication</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Universal Sidebar Navigation -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <!-- Main Content - Security Settings -->
            <div class="lg:w-3/4">
                <!-- Security Status -->
                <?php if (!empty($userData['password_changed_at'])): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-green-600 mr-3"></i>
                            <div>
                                <h3 class="font-medium text-green-800">Your account is secure</h3>
                                <p class="text-green-700 text-sm">Password last changed <?= timeAgo($userData['password_changed_at']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                            <div>
                                <h3 class="font-medium text-yellow-800">Recommendation: Change your password</h3>
                                <p class="text-yellow-700 text-sm">You haven't changed your password recently. Consider updating it for better security.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Password Change -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                        <?php if (!empty($userData['password_changed_at'])): ?>
                            <span class="text-sm text-gray-500">Last changed <?= timeAgo($userData['password_changed_at']) ?></span>
                        <?php else: ?>
                            <span class="text-sm text-gray-500">Never changed</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="max-w-md space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                            <input type="password" name="current_password" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                            <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                            <input type="password" name="confirm_password" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Update Password
                        </button>
                    </form>

                    <!-- Password Requirements -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-3">Password Requirements:</h4>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>At least 8 characters long</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>Contains uppercase and lowercase letters</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>Contains at least one number</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>Contains at least one special character</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Account Verification Status -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Account Verification</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $userData['email_verified'] ? 'bg-green-100' : 'bg-gray-100' ?>">
                                    <i class="fas fa-envelope <?= $userData['email_verified'] ? 'text-green-600' : 'text-gray-400' ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Email Address</h4>
                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($userData['email']) ?></p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $userData['email_verified'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $userData['email_verified'] ? 'Verified' : 'Not Verified' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $userData['phone_verified'] ? 'bg-green-100' : 'bg-gray-100' ?>">
                                    <i class="fas fa-phone <?= $userData['phone_verified'] ? 'text-green-600' : 'text-gray-400' ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Phone Number</h4>
                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($userData['phone'] ?: 'Not provided') ?></p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $userData['phone_verified'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $userData['phone_verified'] ? 'Verified' : 'Not Verified' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Active Sessions -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Active Sessions</h3>
                        <?php if (count($userSessions) > 1): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to sign out of all other devices?');">
                                <input type="hidden" name="action" value="revoke_all_sessions">
                                <button type="submit" class="text-red-600 hover:text-red-700 font-medium text-sm">Sign out of all other devices</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($userSessions)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-desktop text-4xl mb-3 text-gray-300"></i>
                            <p>No active sessions found</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php
                            $currentSessionToken = session_id();
                            foreach ($userSessions as $session):
                                $isCurrent = $session['session_token'] === $currentSessionToken;
                                $ua = parseUserAgent($session['user_agent'] ?? '');
                            ?>
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg <?= $isCurrent ? 'bg-green-50 border-green-200' : '' ?>">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $isCurrent ? 'bg-green-100' : ($ua['device'] === 'Mobile' ? 'bg-blue-100' : 'bg-gray-100') ?>">
                                            <i class="fas <?= $isCurrent ? 'fa-desktop text-green-600' : ($ua['device'] === 'Mobile' ? 'fa-mobile-alt text-blue-600' : 'fa-desktop text-gray-400') ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                <?= $isCurrent ? 'Current Session' : ($ua['device'] === 'Mobile' ? 'Mobile Device' : 'Desktop') ?>
                                                <?php if ($isCurrent): ?>
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Current</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-gray-600 text-sm"><?= htmlspecialchars($ua['browser']) ?> on <?= htmlspecialchars($ua['os']) ?></p>
                                            <p class="text-gray-500 text-xs">
                                                <?= timeAgo($session['created_at']) ?>
                                                <?php if (!empty($session['ip_address'])): ?>
                                                    • IP: <?= htmlspecialchars($session['ip_address']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if (!$isCurrent): ?>
                                        <form method="POST" onsubmit="return confirm('Sign out this device?');">
                                            <input type="hidden" name="action" value="revoke_session">
                                            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Sign Out</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Security Activity -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Recent Security Activity</h3>
                    <div class="space-y-4">
                        <?php if (!empty($userData['password_changed_at'])): ?>
                            <div class="flex items-center space-x-3 p-3 bg-yellow-50 rounded-lg">
                                <i class="fas fa-key text-yellow-600"></i>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">Password changed</p>
                                    <p class="text-gray-600 text-sm">Your password was last updated <?= timeAgo($userData['password_changed_at']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($userSessions)): ?>
                            <div class="flex items-center space-x-3 p-3 bg-green-50 rounded-lg">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">Active sessions</p>
                                    <p class="text-gray-600 text-sm">You currently have <?= count($userSessions) ?> active session(s)</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($userData['email_verified']): ?>
                            <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg">
                                <i class="fas fa-envelope text-blue-600"></i>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">Email verified</p>
                                    <p class="text-gray-600 text-sm">Your email address has been verified</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$userData['email_verified'] || !$userData['phone_verified']): ?>
                            <div class="flex items-center space-x-3 p-3 bg-yellow-50 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">Security recommendations</p>
                                    <p class="text-gray-600 text-sm">
                                        <?php if (!$userData['email_verified']): ?>Verify your email address. <?php endif; ?>
                                        <?php if (!$userData['phone_verified']): ?>Add and verify your phone number. <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Password confirmation match check
const newPassword = document.querySelector('input[name="new_password"]');
const confirmPassword = document.querySelector('input[name="confirm_password"]');
const form = document.querySelector('form');

if (newPassword && confirmPassword && form) {
    form.addEventListener('submit', function(e) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            alert('New passwords do not match.');
            return false;
        }
    });
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
