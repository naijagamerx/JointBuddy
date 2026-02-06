<?php
// Subscription Plan Page - CannaMore Subscription Management
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

$pageTitle = "Subscription Plan";
$currentPage = "subscription-plan";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                    <p class="text-green-100 text-sm">Manage your CannaMore subscription and exclusive benefits</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-crown text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Universal Sidebar Navigation -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <!-- Main Content - Subscription Plan -->
            <div class="lg:w-3/4">
                <!-- Current Subscription Status -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Plan</h3>
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold mb-2">CannaMore Plus</h2>
                                <p class="text-green-100 mb-4">Expires: January 15, 2026</p>
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="bg-white bg-opacity-20 rounded-lg p-3 text-center">
                                        <p class="text-sm text-green-100">Monthly Savings</p>
                                        <p class="text-lg font-bold">R675.00</p>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded-lg p-3 text-center">
                                        <p class="text-sm text-green-100">Free Deliveries</p>
                                        <p class="text-lg font-bold">Unlimited</p>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded-lg p-3 text-center">
                                        <p class="text-sm text-green-100">Priority Support</p>
                                        <p class="text-lg font-bold">24/7</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold">R449.99</p>
                                <p class="text-green-100">per month</p>
                                <button class="mt-4 bg-white text-green-600 px-6 py-2 rounded text-sm font-medium hover:bg-gray-100 transition">
                                    Manage Plan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Plans -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Choose Your Plan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Basic Plan -->
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="text-center mb-6">
                                <h4 class="text-xl font-semibold mb-2">CannaMore Basic</h4>
                                <div class="text-3xl font-bold text-gray-900">R149.99</div>
                                <p class="text-gray-600">per month</p>
                            </div>
                            <ul class="space-y-3 mb-6">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">5% discount on products</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Free shipping on orders R750+</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Standard support</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Monthly product recommendations</span>
                                </li>
                            </ul>
                            <button class="w-full border border-gray-300 text-gray-700 py-2 rounded text-sm font-medium hover:bg-gray-50 transition">
                                Select Plan
                            </button>
                        </div>

                        <!-- Plus Plan (Current) -->
                        <div class="border-2 border-green-500 rounded-lg p-6 relative">
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                <span class="bg-green-500 text-white px-4 py-1 rounded-full text-sm font-medium">Current Plan</span>
                            </div>
                            <div class="text-center mb-6">
                                <h4 class="text-xl font-semibold mb-2">CannaMore Plus</h4>
                                <div class="text-3xl font-bold text-gray-900">R449.99</div>
                                <p class="text-gray-600">per month</p>
                            </div>
                            <ul class="space-y-3 mb-6">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">15% discount on products</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Free shipping on all orders</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Priority support 24/7</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Early access to new products</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Personalized recommendations</span>
                                </li>
                            </ul>
                            <button class="w-full bg-green-600 text-white py-2 rounded text-sm font-medium hover:bg-green-700 transition">
                                Manage Plan
                            </button>
                        </div>

                        <!-- Premium Plan -->
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="text-center mb-6">
                                <h4 class="text-xl font-semibold mb-2">CannaMore Premium</h4>
                                <div class="text-3xl font-bold text-gray-900">R749.99</div>
                                <p class="text-gray-600">per month</p>
                            </div>
                            <ul class="space-y-3 mb-6">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">25% discount on products</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Free same-day delivery</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Dedicated account manager</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Exclusive member events</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700">Concierge service</span>
                                </li>
                            </ul>
                            <button class="w-full bg-gray-900 text-white py-2 rounded text-sm font-medium hover:bg-gray-800 transition">
                                Upgrade Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Subscription History -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription History</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">CannaMore Plus</p>
                                    <p class="text-sm text-gray-600">Monthly subscription</p>
                                    <p class="text-xs text-gray-500">Started: December 15, 2023</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-900">R449.99</p>
                                <p class="text-sm text-gray-600">Auto-renewal: Jan 15, 2026</p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
