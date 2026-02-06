<?php
// Payments & Credit Page - Payment Management
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

$pageTitle = "Payments & Credit";
$currentPage = "payments-credit";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-yellow-500 to-amber-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-yellow-100 text-sm">Manage your payment methods and credit balance</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-credit-card text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Payments & Credit -->
        <div class="lg:w-3/4">
            <!-- Credit Balance -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold mb-2">Available Credit Balance</h2>
                <p class="text-3xl font-bold">$125.50</p>
                <p class="text-green-100 mt-1">Last updated: December 20, 2023</p>
            </div>
            <div class="text-right">
                <svg class="w-16 h-16 text-green-200" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex space-x-4">
            <button class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-medium">Add Money</button>
            <button class="border border-white text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">Transfer Credit</button>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Saved Cards</h3>
                    <p class="text-gray-600">Manage payment methods</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">2</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-yellow-100">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Pending Payments</h3>
                    <p class="text-gray-600">Awaiting processing</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">$0.00</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-purple-100">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold">Payment History</h3>
                    <p class="text-gray-600">Transaction records</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">25</p>
        </div>
    </div>

    <!-- Saved Payment Methods -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Saved Payment Methods</h2>
                <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Add New Card</button>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <!-- Credit Card 1 -->
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded flex items-center justify-center">
                            <span class="text-white text-xs font-bold">VISA</span>
                        </div>
                        <div>
                            <p class="font-semibold">•••• •••• •••• 4242</p>
                            <p class="text-gray-600 text-sm">Expires 12/26</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Default
                        </span>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Credit Card 2 -->
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-8 bg-gradient-to-r from-red-500 to-red-600 rounded flex items-center justify-center">
                            <span class="text-white text-xs font-bold">MC</span>
                        </div>
                        <div>
                            <p class="font-semibold">•••• •••• •••• 8888</p>
                            <p class="text-gray-600 text-sm">Expires 08/27</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Recent Transactions</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <!-- Transaction -->
                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Credit Added</p>
                            <p class="text-gray-600 text-sm">Payment method: Visa ending in 4242</p>
                            <p class="text-gray-500 text-xs">December 20, 2023</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-green-600">+$50.00</p>
                        <p class="text-gray-500 text-sm">Completed</p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Purchase</p>
                            <p class="text-gray-600 text-sm">Order #12345 - Premium Cannabis Flower</p>
                            <p class="text-gray-500 text-xs">December 19, 2023</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-red-600">-$29.99</p>
                        <p class="text-gray-500 text-sm">Completed</p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Refund</p>
                            <p class="text-gray-600 text-sm">Order #12340 - CBD Edible Gummies</p>
                            <p class="text-gray-500 text-xs">December 18, 2023</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-green-600">+$15.00</p>
                        <p class="text-gray-500 text-sm">Completed</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <button class="text-green-600 hover:text-green-700 font-medium">View All Transactions</button>
            </div>
        </div>
    </div>
        </div> <!-- Close main content wrapper -->
    </div> <!-- Close flex row -->
</div> <!-- Close container -->

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>