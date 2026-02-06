<?php
// Newsletter Subscriptions Page - Email Preferences Management
session_start();
require_once __DIR__ . '/../../includes/url_helper.php';

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
    header('Location: ' . userUrl('/login/'));
    exit;
}

$pageTitle = "Newsletter Subscriptions";
$currentPage = "newsletter-subscriptions";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-purple-100 text-sm">Manage your email preferences and communication settings</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-envelope text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Newsletter Subscriptions -->
        <div class="lg:w-3/4">

    <!-- Subscription Overview -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Current Subscriptions</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Product Updates</h3>
                            <p class="text-gray-600 text-sm">New products and restocks</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" checked class="text-green-600 focus:ring-green-500 h-5 w-5">
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Special Offers</h3>
                            <p class="text-gray-600 text-sm">Exclusive deals and promotions</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" checked class="text-green-600 focus:ring-green-500 h-5 w-5">
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Community News</h3>
                            <p class="text-gray-600 text-sm">Blog posts and cannabis education</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" checked class="text-green-600 focus:ring-green-500 h-5 w-5">
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Safety Alerts</h3>
                            <p class="text-gray-600 text-sm">Product recalls and safety notices</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" checked class="text-green-600 focus:ring-green-500 h-5 w-5">
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Order Updates</h3>
                            <p class="text-gray-600 text-sm">Order confirmations and tracking</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" checked class="text-green-600 focus:ring-green-500 h-5 w-5">
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Loyalty Program</h3>
                            <p class="text-gray-600 text-sm">Points updates and rewards</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" checked class="text-green-600 focus:ring-green-500 h-5 w-5">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Communication Preferences -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Communication Preferences</h2>
        </div>
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Frequency</label>
                    <select class="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500">
                        <option>Weekly digest</option>
                        <option>Bi-weekly</option>
                        <option>Monthly</option>
                        <option>Immediate</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Language</label>
                    <select class="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500">
                        <option>English</option>
                        <option>Spanish</option>
                        <option>French</option>
                        <option>German</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Time Zone</label>
                <select class="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500">
                    <option>Pacific Time (PT)</option>
                    <option>Eastern Time (ET)</option>
                    <option>Central Time (CT)</option>
                    <option>Mountain Time (MT)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SMS Notifications</label>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5" checked>
                        <span class="ml-2 text-gray-700">Order status updates</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                        <span class="ml-2 text-gray-700">Delivery notifications</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                        <span class="ml-2 text-gray-700">Special promotions</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Settings -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Privacy & Data Usage</h2>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">Data Sharing</h3>
                    <p class="text-gray-600 text-sm mb-4">Control how we use your data to improve our services</p>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5" checked>
                            <span class="ml-2 text-gray-700">Allow personalized product recommendations</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                            <span class="ml-2 text-gray-700">Share anonymized usage data for research</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                            <span class="ml-2 text-gray-700">Allow third-party analytics</span>
                        </label>
                    </div>
                </div>

                <div>
                    <h3 class="font-medium text-gray-900 mb-2">Marketing Communications</h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5" checked>
                            <span class="ml-2 text-gray-700">Send promotional emails</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                            <span class="ml-2 text-gray-700">Participate in surveys and feedback requests</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                            <span class="ml-2 text-gray-700">Receive SMS marketing messages</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unsubscribed Topics -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Recently Unsubscribed</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Social Media Updates</h3>
                            <p class="text-gray-600 text-sm">Unsubscribed on December 10, 2023</p>
                        </div>
                    </div>
                    <button class="text-green-600 hover:text-green-700 font-medium">Resubscribe</button>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Affiliate Partner Offers</h3>
                            <p class="text-gray-600 text-sm">Unsubscribed on November 28, 2023</p>
                        </div>
                    </div>
                    <button class="text-green-600 hover:text-green-700 font-medium">Resubscribe</button>
                </div>
            </div>
        </div>
    </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>