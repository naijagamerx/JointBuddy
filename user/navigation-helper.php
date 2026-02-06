<?php
// User Dashboard Navigation Helper
require_once __DIR__ . '/../includes/url_helper.php';
session_start();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard Navigation - CannaBuddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 p-8">
    <div class="container mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">CannaBuddy User Dashboard Navigation</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- My Account Section -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-green-800 mb-4 flex items-center">
                        <i class="fas fa-box mr-2"></i>
                        Orders & Management
                    </h2>
                    <div class="space-y-2">
                        <a href="<?= userUrl('/dashboard/') ?>" class="block text-sm text-green-700 hover:text-green-900 hover:underline">Dashboard (6 Cards)</a>
                        <a href="<?= userUrl('/orders/') ?>" class="block text-sm text-green-700 hover:text-green-900 hover:underline">Orders Management</a>
                        <a href="<?= userUrl('/invoices/') ?>" class="block text-sm text-green-700 hover:text-green-900 hover:underline">Invoices & Downloads</a>
                        <a href="<?= userUrl('/returns/') ?>" class="block text-sm text-green-700 hover:text-green-900 hover:underline">Returns Management</a>
                        <a href="<?= userUrl('/reviews/') ?>" class="block text-sm text-green-700 hover:text-green-900 hover:underline">Product Reviews</a>
                    </div>
                </div>

                <!-- Payments Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-blue-800 mb-4 flex items-center">
                        <i class="fas fa-credit-card mr-2"></i>
                        Payments & Credit
                    </h2>
                    <div class="space-y-2">
                        <a href="#" class="block text-sm text-blue-700 hover:text-blue-900 hover:underline">Coupons & Offers</a>
                        <a href="#" class="block text-sm text-blue-700 hover:text-blue-900 hover:underline">Credit & Refunds</a>
                        <a href="#" class="block text-sm text-blue-700 hover:text-blue-900 hover:underline">Redeem Gift Voucher</a>
                    </div>
                </div>

                <!-- CannaMore Section -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-purple-800 mb-4 flex items-center">
                        <i class="fas fa-crown mr-2"></i>
                        CannaMore
                    </h2>
                    <div class="space-y-2">
                        <a href="#" class="block text-sm text-purple-700 hover:text-purple-900 hover:underline">Subscription Plan</a>
                        <a href="#" class="block text-sm text-purple-700 hover:text-purple-900 hover:underline">Payment History</a>
                    </div>
                </div>

                <!-- Profile Section -->
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-indigo-800 mb-4 flex items-center">
                        <i class="fas fa-user-cog mr-2"></i>
                        Profile & Settings
                    </h2>
                    <div class="space-y-2">
                        <a href="<?= userUrl('/profile/personal-details.php') ?>" class="block text-sm text-indigo-700 hover:text-indigo-900 hover:underline">Personal Details</a>
                        <a href="#" class="block text-sm text-indigo-700 hover:text-indigo-900 hover:underline">Security Settings</a>
                        <a href="#" class="block text-sm text-indigo-700 hover:text-indigo-900 hover:underline">Address Book</a>
                        <a href="#" class="block text-sm text-indigo-700 hover:text-indigo-900 hover:underline">Newsletter Subscriptions</a>
                    </div>
                </div>

                <!-- Lists Section -->
                <div class="bg-teal-50 border border-teal-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-teal-800 mb-4 flex items-center">
                        <i class="fas fa-list mr-2"></i>
                        My Lists
                    </h2>
                    <div class="space-y-2">
                        <a href="#" class="block text-sm text-teal-700 hover:text-teal-900 hover:underline">Create a List</a>
                    </div>
                </div>

                <!-- Support Section -->
                <div class="bg-pink-50 border border-pink-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-pink-800 mb-4 flex items-center">
                        <i class="fas fa-life-ring mr-2"></i>
                        Support
                    </h2>
                    <div class="space-y-2">
                        <a href="#" class="block text-sm text-pink-700 hover:text-pink-900 hover:underline">Help Centre</a>
                        <a href="<?= userUrl('/logout/') ?>" class="block text-sm text-red-600 hover:text-red-800 hover:underline">Logout</a>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">📋 What's Been Created</h3>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>✅ <strong>Dashboard:</strong> Updated with Orders menu item in Orders card</li>
                    <li>✅ <strong>Orders Page:</strong> Complete order management with status tabs and filtering</li>
                    <li>✅ <strong>Invoices Page:</strong> Invoice management with individual and group download options</li>
                    <li>✅ <strong>Returns Page:</strong> Return request management with status tracking</li>
                    <li>✅ <strong>Reviews Page:</strong> Product review management with pending reviews section</li>
                    <li>✅ <strong>Personal Details:</strong> Comprehensive profile form with cannabis preferences</li>
                    <li>✅ <strong>Universal Headers:</strong> Consistent navigation across all user pages</li>
                    <li>✅ <strong>Responsive Design:</strong> Mobile-friendly layouts using Tailwind CSS</li>
                </ul>
            </div>

            <div class="mt-6 text-center">
                <a href="<?= userUrl('/dashboard/') ?>" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
