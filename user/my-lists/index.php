<?php
// My Lists Page - List Management
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

$pageTitle = "My Lists";
$currentPage = "my-lists";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-teal-100 text-sm">View and manage all your shopping lists</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-list-alt text-2xl"></i>
        </div>
    </div>
            </div>

    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">My Lists</h1>
        <p class="text-gray-600 text-sm">Create, manage, and share your shopping lists</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - My Lists -->
        <div class="lg:w-3/4">
            <!-- Lists Management -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <!-- Lists Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-900">Your Shopping Lists</h3>
            <a href="<?= userUrl('/create-list/" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="fas fa-plus mr-2"></i>Create New List
            </a>
        </div>

        <!-- Lists Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- List Card 1 -->
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Weekly Groceries</h4>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">12 items</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Updated 2 days ago</span>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-700 text-sm">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button class="text-red-600 hover:text-red-700 text-sm">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- List Card 2 -->
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Party Supplies</h4>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">8 items</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Updated 1 week ago</span>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-700 text-sm">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button class="text-red-600 hover:text-red-700 text-sm">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- List Card 3 -->
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Cannabis Products</h4>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-teal-100 text-teal-800 px-2 py-1 rounded-full">15 items</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Updated 3 days ago</span>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-700 text-sm">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button class="text-red-600 hover:text-red-700 text-sm">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create New List Card -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 flex flex-col items-center justify-center hover:border-gray-400 transition-colors cursor-pointer" onclick="window.location.href='<?= userUrl('/create-list/') ?>'">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-plus text-gray-500"></i>
                </div>
                <h4 class="font-medium text-gray-900 text-center">Create New List</h4>
                <p class="text-sm text-gray-500 text-center mt-1">Add items to your new shopping list</p>
            </div>
        </div>
    </div>
</div>
            </div>
            </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
