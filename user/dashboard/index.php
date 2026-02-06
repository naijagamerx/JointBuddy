<?php
/**
 * User Dashboard Page
 *
 * Main dashboard for logged-in users
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();
$errors = [];

// Try to connect to database for additional user data
$db = null;
$userDetails = null;

try {
    $db = Services::db();

    if ($db) {
        // Fetch user details
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$currentUser['id']]);
        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userDetails) {
            $errors[] = "User account not found in database.";
        }
    }
} catch (Exception $e) {
    error_log("Database error in user dashboard: " . $e->getMessage());
    $errors[] = "Unable to load account details. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - CannaBuddy</title>
<?php
$pageTitle = "My Account";
$currentPage = "dashboard";

try {
    include __DIR__ . "/../../includes/header.php";
} catch (Exception $e) {
    error_log("Header include error: " . $e->getMessage());
    echo '<script src="https://cdn.tailwindcss.com"></script>';
}
?>

<!-- Main Dashboard Content -->
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error Loading Dashboard</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!
        </h1>
        <p class="text-gray-600">Manage your account and view your orders</p>
    </div>

    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Orders Card -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900" style="font-size: 16px;">Orders</h3>
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-2 pb-3">
                <ul class="space-y-0">
                    <li><a href="<?= userUrl('/orders/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Orders</a></li>
                    <li><a href="<?= userUrl('/invoices/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Invoices</a></li>
                    <li><a href="<?= userUrl('/returns/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Returns</a></li>
                    <li><a href="<?= userUrl('/reviews/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Product Reviews</a></li>
                </ul>
            </div>
        </div>

        <!-- Payments & Credit Card -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900" style="font-size: 16px;">Payments & Credit</h3>
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-2 pb-3">
                <ul class="space-y-0">
                    <li><a href="<?= userUrl('/coupons-offers/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Coupons & Offers</a></li>
                    <li><a href="<?= userUrl('/credit-refunds/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Credit & Refunds</a></li>
                    <li><a href="<?= userUrl('/redeem-voucher/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Redeem Gift Voucher</a></li>
                </ul>
            </div>
        </div>

        <!-- CannaMORE Card -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900" style="font-size: 16px;">CannaMORE</h3>
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-2 pb-3">
                <ul class="space-y-0">
                    <li><a href="<?= userUrl('/subscription-plan/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Subscription Plan</a></li>
                    <li><a href="<?= userUrl('/payment-history/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Payment History</a></li>
                </ul>
            </div>
        </div>

        <!-- Profile Card -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900" style="font-size: 16px;">Profile</h3>
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-2 pb-3">
                <ul class="space-y-0">
                    <li><a href="<?= userUrl('/personal-details/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Personal Details</a></li>
                    <li><a href="<?= userUrl('/security-settings/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Security Settings</a></li>
                    <li><a href="<?= userUrl('/address-book/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Address Book</a></li>
                    <li><a href="<?= userUrl('/newsletter-subscriptions/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Newsletter Subscriptions</a></li>
                </ul>
            </div>
        </div>

        <!-- My Lists Card -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900" style="font-size: 16px;">My Lists</h3>
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-2 pb-3">
                <ul class="space-y-0">
                    <li><a href="<?= userUrl('/my-lists/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">My Lists</a></li>
                    <li><a href="<?= userUrl('/create-list/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Create a List</a></li>
                </ul>
            </div>
        </div>

        <!-- Support Card -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900" style="font-size: 16px;">Support</h3>
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-2 pb-3">
                <ul class="space-y-0">
                    <li><a href="<?= userUrl('/help-centre/') ?>" class="block px-3 text-green-600 hover:bg-gray-50 rounded transition-colors" style="font-size: 16px;">Help Centre</a></li>
                    <li><a href="<?= userUrl('/logout/') ?>" class="block px-3 text-red-600 hover:bg-red-50 rounded transition-colors font-medium" style="font-size: 16px;">Log Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
try {
    include __DIR__ . "/../../includes/footer.php";
} catch (Exception $e) {
    error_log("Footer include error: " . $e->getMessage());
    echo '</body></html>';
}
?>
