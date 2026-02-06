<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle cache clearing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CsrfMiddleware::validate();
    $results = [];
    $errors = [];

    switch ($_POST['action']) {
        case 'clear_opcache':
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $results[] = 'OPcache cleared successfully';
            } else {
                $errors[] = 'OPcache is not available on this server';
            }
            break;

        case 'clear_session':
            session_destroy();
            session_start();
            $results[] = 'Session cache cleared successfully';
            break;

        case 'clear_all':
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $results[] = 'OPcache cleared';
            }
            session_destroy();
            session_start();
            $results[] = 'Session cache cleared';
            $results[] = 'Browser cache should be refreshed manually (Ctrl+F5 or Cmd+Shift+R)';
            break;

        case 'restart_session':
            session_regenerate_id(true);
            $results[] = 'Session restarted with new ID';
            break;
    }

    // Set messages
    if (!empty($results)) {
        $_SESSION['success'] = implode('<br>', $results);
    }
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    // Redirect to prevent form resubmission
    redirect('/admin/tools/');
}

// Get cache status
$cacheStatus = [
    'opcache_enabled' => function_exists('opcache_reset'),
    'session_id' => session_id(),
    'session_status' => session_status(),
];

// Generate tools content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">System Tools</h1>
                <p class="text-gray-600 mt-2">Cache management and system utilities</p>
            </div>
        </div>
    </div>';

if (isset($_SESSION['success'])) {
    $content .= '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . $_SESSION['success'] . '</p>
            </div>
        </div>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $content .= '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . $_SESSION['error'] . '</p>
            </div>
        </div>
    </div>';
    unset($_SESSION['error']);
}

$content .= '
    <!-- Cache Status Card -->
    <div class="bg-white shadow rounded-xl overflow-hidden mb-8">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Cache Status</h2>
            <p class="text-gray-600 mt-1">Current cache information</p>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-bolt text-blue-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">OPcache</h3>
                    </div>
                    <p class="text-3xl font-bold ' . ($cacheStatus['opcache_enabled'] ? 'text-green-600' : 'text-gray-400') . ' mb-2">
                        ' . ($cacheStatus['opcache_enabled'] ? 'Enabled' : 'Disabled') . '
                    </p>
                    <p class="text-sm text-gray-600">PHP bytecode cache</p>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-cookie-bite text-purple-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Session</h3>
                    </div>
                    <p class="text-lg font-bold text-gray-900 mb-2">
                        Active
                    </p>
                    <p class="text-sm text-gray-600">ID: ' . substr($cacheStatus['session_id'], 0, 12) . '...</p>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-server text-green-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Server</h3>
                    </div>
                    <p class="text-lg font-bold text-gray-900 mb-2">
                        Running
                    </p>
                    <p class="text-sm text-gray-600">All systems operational</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Product Management Menu -->
    <div class="bg-white shadow rounded-xl overflow-hidden mb-8">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Quick Product Management</h2>
            <p class="text-gray-600 mt-1">Fast access to product operations</p>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="' . adminUrl('/products/add.php') . '" class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-green-300 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all group">
                    <i class="fas fa-plus-circle text-green-600 text-5xl mb-4 group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Add New Product</h3>
                    <p class="text-sm text-gray-600 text-center">Create a new product from scratch</p>
                </a>

                <a href="' . adminUrl('/products/') . '" class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 rounded-xl hover:border-gray-500 hover:bg-gray-50 transition-all group">
                    <i class="fas fa-eye text-gray-600 text-5xl mb-4 group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Product List</h3>
                    <p class="text-sm text-gray-600 text-center">Browse all products</p>
                </a>

                <a href="' . adminUrl('/products/inventory.php') . '" class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-yellow-300 rounded-xl hover:border-yellow-500 hover:bg-yellow-50 transition-all group">
                    <i class="fas fa-warehouse text-yellow-500 text-5xl mb-4 group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Inventory</h3>
                    <p class="text-sm text-gray-600 text-center">Quick access to stock management</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Cache Clearing Tools -->
    <div class="bg-white shadow rounded-xl overflow-hidden mb-8">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Cache Management</h2>
            <p class="text-gray-600 mt-1">Clear different types of cache to refresh the system</p>
        </div>
        <div class="p-8">
            <div class="space-y-6">
                <!-- Clear OPcache -->
                <div class="border border-gray-200 rounded-lg p-6 ' . (!$cacheStatus['opcache_enabled'] ? 'opacity-60' : 'hover:shadow-md') . ' transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <i class="fas fa-bolt text-blue-600 mr-2"></i>Clear OPcache
                                ' . (!$cacheStatus['opcache_enabled'] ? '<span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Not Available</span>' : '') . '
                            </h3>
                            <p class="text-gray-600 mb-4">' . ($cacheStatus['opcache_enabled'] ? 'Clears the PHP OPcache, forcing PHP to recompile all scripts. Use this if you have updated PHP files but the changes are not reflecting.' : 'OPcache is not available on this server. This is normal for some hosting configurations.') . '</p>
                            <ul class="text-sm text-gray-500 space-y-1">
                                <li>• Affects: Server-side PHP cache</li>
                                <li>• Effect: Immediate</li>
                                <li>• Use case: After updating PHP files</li>
                                ' . (!$cacheStatus['opcache_enabled'] ? '<li class="text-yellow-600">• <strong>Note:</strong> Your server does not have OPcache enabled</li>' : '') . '
                            </ul>
                        </div>
                        <div class="ml-6">
                            ' . ($cacheStatus['opcache_enabled'] ? '
                            <form method="POST">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="clear_opcache">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Clear OPcache
                                </button>
                            </form>
                            ' : '
                            <button disabled class="bg-gray-300 text-gray-500 px-6 py-3 rounded-lg font-medium cursor-not-allowed">
                                <i class="fas fa-times mr-2"></i>Not Available
                            </button>
                            ') . '
                        </div>
                    </div>
                </div>

                <!-- Clear Session Cache -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <i class="fas fa-cookie-bite text-purple-600 mr-2"></i>Clear Session Cache
                            </h3>
                            <p class="text-gray-600 mb-4">Destroys the current session and starts a new one. This will log out all current users and clear session data.</p>
                            <ul class="text-sm text-gray-500 space-y-1">
                                <li>• Affects: Current session only</li>
                                <li>• Effect: You will be logged out</li>
                                <li>• Use case: Session-related issues</li>
                            </ul>
                        </div>
                        <div class="ml-6">
                            <form method="POST">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="clear_session">
                                <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition-colors" onclick="return confirm(\'This will clear your session and log you out. Continue?\')">
                                    <i class="fas fa-trash mr-2"></i>Clear Session
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Restart Session -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <i class="fas fa-sync-alt text-green-600 mr-2"></i>Restart Session
                            </h3>
                            <p class="text-gray-600 mb-4">Generates a new session ID while keeping you logged in. This is a security best practice.</p>
                            <ul class="text-sm text-gray-500 space-y-1">
                                <li>• Affects: Session security</li>
                                <li>• Effect: You stay logged in</li>
                                <li>• Use case: Security refresh</li>
                            </ul>
                        </div>
                        <div class="ml-6">
                            <form method="POST">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="restart_session">
                                <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                                    <i class="fas fa-sync mr-2"></i>Restart Session
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Clear All Cache -->
                <div class="border-2 border-red-200 rounded-lg p-6 bg-red-50 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-red-900 mb-2">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Clear All Cache
                            </h3>
                            <p class="text-red-800 mb-4">Clears all server-side caches (' . ($cacheStatus['opcache_enabled'] ? 'OPcache and session' : 'session only') . '). This is the most comprehensive cache clear.</p>
                            <ul class="text-sm text-red-700 space-y-1">
                                <li>• Affects: All server-side cache</li>
                                <li>• Effect: You will be logged out</li>
                                <li>• Use case: Complete system refresh</li>
                                <li>• <strong>Note:</strong> Browser cache must be cleared manually (Ctrl+F5)</li>
                                ' . (!$cacheStatus['opcache_enabled'] ? '<li class="text-yellow-700">• <strong>Note:</strong> OPcache not available, will clear session only</li>' : '') . '
                            </ul>
                        </div>
                        <div class="ml-6">
                            <form method="POST">
                                ' . csrf_field() . '
                                <input type="hidden" name="action" value="clear_all">
                                <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors" onclick="return confirm(\'This will clear ALL cache and log you out. This action cannot be undone. Continue?\')">
                                    <i class="fas fa-bomb mr-2"></i>Clear All Cache
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Browser Cache Instructions -->
    <div class="bg-white shadow rounded-xl overflow-hidden mb-8">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Browser Cache</h2>
            <p class="text-gray-600 mt-1">How to clear your browser cache</p>
        </div>
        <div class="p-8">
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-bold">1</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Chrome / Edge / Firefox</h3>
                        <p class="text-gray-600">Press <kbd class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">Ctrl+Shift+Delete</kbd> (Windows) or <kbd class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">Cmd+Shift+Delete</kbd> (Mac)</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-bold">2</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Hard Refresh</h3>
                        <p class="text-gray-600">Press <kbd class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">Ctrl+F5</kbd> or <kbd class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">Cmd+Shift+R</kbd> to force reload without cache</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-bold">3</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Incognito / Private Mode</h3>
                        <p class="text-gray-600">Open an incognito/private browsing window to view the page without cache</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">System Information</h2>
            <p class="text-gray-600 mt-1">Server and PHP configuration</p>
        </div>
        <div class="p-8">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-semibold text-gray-500 uppercase tracking-wider">PHP Version</dt>
                    <dd class="mt-2 text-lg text-gray-900">' . phpversion() . '</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Server Software</dt>
                    <dd class="mt-2 text-lg text-gray-900">' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-gray-500 uppercase tracking-wider">OPCache Status</dt>
                    <dd class="mt-2 text-lg text-gray-900">
                        <span class="px-3 py-1 text-sm font-medium ' . ($cacheStatus['opcache_enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . ' rounded-full">
                            ' . ($cacheStatus['opcache_enabled'] ? 'Enabled' : 'Disabled') . '
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Memory Limit</dt>
                    <dd class="mt-2 text-lg text-gray-900">' . ini_get('memory_limit') . '</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Max Execution Time</dt>
                    <dd class="mt-2 text-lg text-gray-900">' . ini_get('max_execution_time') . ' seconds</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Upload Max Size</dt>
                    <dd class="mt-2 text-lg text-gray-900">' . ini_get('upload_max_filesize') . '</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<style>
kbd {
    display: inline-block;
    padding: 2px 6px;
    font-size: 11px;
    line-height: 1;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 3px;
    font-family: monospace;
}
</style>';

// Render the page with sidebar
echo adminSidebarWrapper('System Tools', $content, 'tools');
?>
