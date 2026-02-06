<?php
// Create List Page - List Management
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

$pageTitle = "Create New List";
$currentPage = "create-list";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-teal-100 text-sm">Create and manage your personalized shopping lists</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-list text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Create List -->
        <div class="lg:w-3/4">
        <p class="text-gray-600">Create a new product list to organize your shopping</p>
    </div>

    <!-- Create List Form -->
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">List Information</h2>
            </div>
            <form class="p-6 space-y-6">
                <!-- List Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">List Name *</label>
                    <input 
                        type="text" 
                        placeholder="e.g., Birthday Gifts, CBD Essentials, Weekly Shopping"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                        required
                    >
                    <p class="text-sm text-gray-500 mt-1">Choose a descriptive name for your list</p>
                </div>

                <!-- List Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <textarea 
                        rows="3" 
                        placeholder="Add a brief description of what this list is for..."
                        class="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                    ></textarea>
                </div>

                <!-- List Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">List Type</label>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="radio" name="listType" value="private" class="text-green-600 focus:ring-green-500" checked>
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">Private</p>
                                <p class="text-gray-600 text-sm">Only you can see this list</p>
                            </div>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="listType" value="shared" class="text-green-600 focus:ring-green-500">
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">Shared</p>
                                <p class="text-gray-600 text-sm">Others can view this list (read-only)</p>
                            </div>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="listType" value="collaborative" class="text-green-600 focus:ring-green-500">
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">Collaborative</p>
                                <p class="text-gray-600 text-sm">Others can add and remove items</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Privacy Settings -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Privacy Settings</label>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5" checked>
                            <span class="ml-2 text-gray-700">Allow others to copy items from this list</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5">
                            <span class="ml-2 text-gray-700">Show in my public profile</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="text-green-600 focus:ring-green-500 h-5 w-5" checked>
                            <span class="ml-2 text-gray-700">Send notifications when items are added</span>
                        </label>
                    </div>
                </div>

                <!-- Icon Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Choose an Icon</label>
                    <div class="grid grid-cols-6 gap-4">
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="icon" value="heart" class="sr-only" checked>
                            <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                            </svg>
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="icon" value="gift" class="sr-only">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12z"/>
                            </svg>
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="icon" value="shopping" class="sr-only">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/>
                            </svg>
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="icon" value="star" class="sr-only">
                            <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="icon" value="leaf" class="sr-only">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="icon" value="home" class="sr-only">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-6 border-t">
                    <a href="<?= userUrl('/my-lists/') ?>" class="text-gray-600 hover:text-gray-800 font-medium">Cancel</a>
                    <div class="space-x-4">
                        <button type="button" class="border border-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-50 transition">Save as Draft</button>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Create List</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tips -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-800 mb-2">Tips for Creating Great Lists</h3>
                    <ul class="text-blue-700 text-sm space-y-1">
                        <li>• Use descriptive names that clearly indicate the list's purpose</li>
                        <li>• Add a description to help others understand what the list is for</li>
                        <li>• Consider sharing lists for collaborative shopping with family</li>
                        <li>• You can always edit these settings later</li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
    </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>