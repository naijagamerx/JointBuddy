<?php
/**
 * Profile Page
 *
 * User profile overview and management
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();

$pageTitle = "Profile";
$currentPage = "profile";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-violet-500 to-purple-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-violet-100 text-sm">Manage your account profile and preferences</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-user-circle text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Profile -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="bg-white rounded shadow-sm border border-gray-200">
            <!-- Profile Overview -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6">
            <div class="flex items-center space-x-6">
                <div class="relative">
                    <img src="https://via.placeholder.com/120x120" alt="Profile Picture" class="w-24 h-24 rounded-full border-4 border-white shadow-lg">
                    <button class="absolute bottom-0 right-0 bg-green-600 text-white rounded-full p-2 hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900">John Smith</h2>
                    <p class="text-gray-600">john.smith@email.com</p>
                    <p class="text-gray-500">Member since December 2022</p>
                    <div class="flex items-center mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Verified Account
                        </span>
                        <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            CannaMore Plus Member
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <button class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Edit Profile</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Personal Details</h3>
                    <p class="text-gray-600">Update your information</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-green-100">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Security Settings</h3>
                    <p class="text-gray-600">Password & 2FA</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-purple-100">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Address Book</h3>
                    <p class="text-gray-600">Manage delivery addresses</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-yellow-100">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Newsletter</h3>
                    <p class="text-gray-600">Email preferences</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Orders Placed</h3>
                    <p class="text-3xl font-bold text-blue-600">25</p>
                </div>
                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-600 mt-2">Last order: 2 days ago</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Total Spent</h3>
                    <p class="text-3xl font-bold text-green-600">$847</p>
                </div>
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
            </div>
            <p class="text-sm text-gray-600 mt-2">Average: $33.88 per order</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Loyalty Points</h3>
                    <p class="text-3xl font-bold text-purple-600">2,450</p>
                </div>
                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-600 mt-2">Next reward at 2,500 points</p>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Recent Activity</h2>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">Added new shipping address</p>
                        <p class="text-gray-600 text-sm">123 Main St, San Francisco, CA 94102</p>
                        <p class="text-gray-500 text-xs">December 20, 2023 at 2:30 PM</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">Placed new order</p>
                        <p class="text-gray-600 text-sm">Order #12345 - Premium Cannabis Flower</p>
                        <p class="text-gray-500 text-xs">December 19, 2023 at 11:20 AM</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">Updated profile information</p>
                        <p class="text-gray-600 text-sm">Changed phone number and updated preferences</p>
                        <p class="text-gray-500 text-xs">December 18, 2023 at 4:15 PM</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">Subscribed to CannaMore Plus</p>
                        <p class="text-gray-600 text-sm">Monthly subscription started</p>
                        <p class="text-gray-500 text-xs">December 15, 2023 at 10:30 AM</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <button class="text-green-600 hover:text-green-700 font-medium">View All Activity</button>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
            </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
