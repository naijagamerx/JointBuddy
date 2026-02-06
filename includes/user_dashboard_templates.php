<?php
// User Dashboard Template System
// This file contains templates for all user dashboard sections
require_once __DIR__ . '/url_helper.php';

function renderUserOrdersPage($orders = [], $currentSection = 'orders') {
    ob_start();
    ?>
    <!-- Orders Page Content -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">My Orders</h3>
                <div class="flex space-x-2">
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                        <option>All Orders</option>
                        <option>Pending</option>
                        <option>Processing</option>
                        <option>Shipped</option>
                        <option>Delivered</option>
                        <option>Cancelled</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <?php if (empty($orders)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">No orders found</p>
                    <a href="<?php echo  shopUrl('/') ?>" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($orders as $order): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                    <p class="text-sm text-gray-500">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php 
                                    echo [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'shipped' => 'bg-purple-100 text-purple-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ][$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['items_summary'] ?? 'Multiple items'); ?></p>
                                <div class="flex space-x-2">
                                    <a href="<?php echo  userUrl('/orders/view/' . $order['id'] . '/') ?>" 
                                       class="text-green-600 hover:text-green-800 text-sm font-medium">
                                        <i class="fas fa-eye mr-1"></i>View Details
                                    </a>
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            <i class="fas fa-star mr-1"></i>Leave Review
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function renderUserProfilePage($user = null) {
    ob_start();
    ?>
    <!-- Profile Page Content -->
    <div class="space-y-6">
        <!-- Personal Details -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Personal Details</h3>
            </div>
            <div class="p-6">
                <form class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Security Settings</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900">Password</h4>
                            <p class="text-sm text-gray-500">Last changed 3 months ago</p>
                        </div>
                        <button class="text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-edit mr-1"></i>Change Password
                        </button>
                    </div>
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900">Two-Factor Authentication</h4>
                            <p class="text-sm text-gray-500">Add an extra layer of security to your account</p>
                        </div>
                        <button class="text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-shield-alt mr-1"></i>Enable 2FA
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Book -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Address Book</h3>
                    <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>Add Address
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Default Address -->
                    <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-green-800">Default Address</h4>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Primary</span>
                        </div>
                        <p class="text-sm text-gray-700">
                            <?php echo htmlspecialchars($user['address'] ?? '123 Main St'); ?><br>
                            <?php echo htmlspecialchars($user['city'] ?? 'City'); ?>, <?php echo htmlspecialchars($user['state'] ?? 'State'); ?> <?php echo htmlspecialchars($user['zip_code'] ?? '12345'); ?>
                        </p>
                        <div class="mt-3 flex space-x-2">
                            <button class="text-green-600 hover:text-green-800 text-sm font-medium">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button class="text-red-600 hover:text-red-800 text-sm font-medium">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function renderUserPaymentsPage() {
    ob_start();
    ?>
    <!-- Payments & Credit Page -->
    <div class="space-y-6">
        <!-- Available Credit -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Available Credit</h3>
            </div>
            <div class="p-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600">$<?php echo number_format(0, 2); ?></p>
                    <p class="text-gray-500 mt-1">Available for purchases</p>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Payment Methods</h3>
                    <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>Add Payment Method
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fab fa-cc-visa text-2xl text-blue-600 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900">**** **** **** 4532</p>
                                <p class="text-sm text-gray-500">Expires 12/25</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Default</span>
                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Transaction History</h3>
            </div>
            <div class="p-6">
                <div class="text-center py-8">
                    <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No transactions found</p>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function renderUserListsPage() {
    ob_start();
    ?>
    <!-- My Lists Page -->
    <div class="space-y-6">
        <!-- Create New List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">My Lists</h3>
                    <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>Create New List
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Wishlist -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-heart text-red-500 text-xl mr-3"></i>
                            <h4 class="font-medium text-gray-900">Wishlist</h4>
                        </div>
                        <p class="text-sm text-gray-500 mb-3">Items you want to buy later</p>
                        <p class="text-sm font-medium text-green-600">0 items</p>
                        <button class="mt-3 w-full text-green-600 hover:text-green-800 text-sm font-medium">
                            View List
                        </button>
                    </div>
                    
                    <!-- Compare List -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-balance-scale text-blue-500 text-xl mr-3"></i>
                            <h4 class="font-medium text-gray-900">Compare</h4>
                        </div>
                        <p class="text-sm text-gray-500 mb-3">Compare products side by side</p>
                        <p class="text-sm font-medium text-blue-600">0 items</p>
                        <button class="mt-3 w-full text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View List
                        </button>
                    </div>
                    
                    <!-- Recently Viewed -->
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-eye text-purple-500 text-xl mr-3"></i>
                            <h4 class="font-medium text-gray-900">Recently Viewed</h4>
                        </div>
                        <p class="text-sm text-gray-500 mb-3">Products you recently looked at</p>
                        <p class="text-sm font-medium text-purple-600">0 items</p>
                        <button class="mt-3 w-full text-purple-600 hover:text-purple-800 text-sm font-medium">
                            View List
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Lists -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Custom Lists</h3>
            </div>
            <div class="p-6">
                <div class="text-center py-8">
                    <i class="fas fa-list-ul text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">You haven\'t created any custom lists yet</p>
                    <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>Create Your First List
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Helper function to render any dashboard page with sidebar
function renderUserDashboardPage($title, $content, $currentSection = '') {
    ob_start();
    ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar -->
            <div class="lg:w-64 flex-shrink-0">
                <?php echo renderUserSidebar($currentSection); ?>
            </div>
            
            <!-- Main Content -->
            <div class="flex-1 min-w-0">
                <?php echo renderUserDashboardContent($title, $content); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>