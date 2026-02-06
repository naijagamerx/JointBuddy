<?php
// Admin Dashboard Template
require_once __DIR__ . '/../includes/url_helper.php';

function renderAdminDashboard($admin = null, $stats = []) {
    $adminName = $admin ? $admin['full_name'] : 'Administrator';
    
    return '<div class="min-h-screen bg-gray-50">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-bold text-green-600">JointBuddy Admin</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="<?php echo  adminUrl('') ?>" class="border-green-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="<?php echo  adminUrl('users') ?>" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Users
                            </a>
                            <a href="<?php echo  adminUrl('products') ?>" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Products
                            </a>
                            <a href="<?php echo  adminUrl('orders') ?>" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Orders
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700">Welcome, ' . htmlspecialchars($adminName) . '</span>
                        <a href="<?php echo  adminUrl('logout') ?>" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="px-4 py-6 sm:px-0">
                <div class="border-4 border-dashed border-gray-200 rounded-lg p-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Total Users -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                            <dd class="text-lg font-medium text-gray-900">' . ($stats['total_users'] ?? '0') . '</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Users -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Active Users</dt>
                                            <dd class="text-lg font-medium text-gray-900">' . ($stats['active_users'] ?? '0') . '</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New Registrations -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">New This Month</dt>
                                            <dd class="text-lg font-medium text-gray-900">' . ($stats['new_users'] ?? '0') . '</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">System Status</dt>
                                            <dd class="text-lg font-medium text-green-600">Online</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
                            <div class="space-y-4">
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">New user registration</p>
                                        <p class="text-sm text-gray-500">john.doe@example.com registered</p>
                                    </div>
                                    <div class="ml-auto">
                                        <p class="text-sm text-gray-500">2 minutes ago</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">Admin login</p>
                                        <p class="text-sm text-gray-500">Administrator logged in</p>
                                    </div>
                                    <div class="ml-auto">
                                        <p class="text-sm text-gray-500">15 minutes ago</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">User Management</h3>
                            <div class="space-y-3">
                                <a href="<?php echo  adminUrl('users') ?>" class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                    View All Users
                                </a>
                                <a href="<?php echo  adminUrl('users/add') ?>" class="block w-full text-center bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                                    Add New User
                                </a>
                            </div>
                        </div>

                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Product Management</h3>
                            <div class="space-y-3">
                                <a href="<?php echo  adminUrl('products') ?>" class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                    View Products
                                </a>
                                <a href="<?php echo  adminUrl('products/add') ?>" class="block w-full text-center bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                                    Add Product
                                </a>
                            </div>
                        </div>

                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Management</h3>
                            <div class="space-y-3">
                                <a href="<?php echo  adminUrl('orders') ?>" class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                    View Orders
                                </a>
                                <a href="<?php echo  adminUrl('orders/pending') ?>" class="block w-full text-center bg-yellow-600 text-white py-2 px-4 rounded-md hover:bg-yellow-700">
                                    Pending Orders
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

// User Management Page
function renderUserManagement($users = []) {
    return '<div class="min-h-screen bg-gray-50">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-bold text-green-600">JointBuddy Admin</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="<?php echo  adminUrl('') ?>" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="<?php echo  adminUrl('users') ?>" class="border-green-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Users
                            </a>
                            <a href="<?php echo  adminUrl('products') ?>" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Products
                            </a>
                            <a href="<?php echo  adminUrl('orders') ?>" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Orders
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="<?php echo  adminUrl('logout') ?>" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                    <a href="<?php echo  adminUrl('users/add') ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Add New User
                    </a>
                </div>

                <!-- Users Table -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">City</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">';

    if (empty($users)) {
        $html .= '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No users found</td></tr>';
    } else {
        foreach ($users as $user) {
            $status = $user['is_active'] ? 'Active' : 'Inactive';
            $statusClass = $user['is_active'] ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100';
            $lastLogin = $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never';
            
            $html .= '<tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($user['email']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($user['phone'] ?? 'N/A') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($user['city'] ?? 'N/A') . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $statusClass . '">' . $status . '</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $lastLogin . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="<?php echo  adminUrl('users/edit/' . $user['id']) ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                    <a href="<?php echo  adminUrl('users/delete/' . $user['id']) ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>';
        }
    }

    $html .= '</tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}
?>
