<?php
// Enable comprehensive error reporting based on environment
$host = $_SERVER['HTTP_HOST'] ?? '';
$server = $_SERVER['SERVER_SOFTWARE'] ?? '';
$isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']);
$isLocalServer = strpos($server, 'MAMP') !== false || strpos($server, 'WAMP') !== false || strpos($server, 'XAMPP') !== false;
$isDevelopment = $isLocal || $isLocalServer;
if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors_admin.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

register_shutdown_function(function() use ($isDevelopment) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if ($isDevelopment) {
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
            $msg = htmlspecialchars($e['message'], ENT_QUOTES, 'UTF-8');
            $file = htmlspecialchars($e['file'] ?? '', ENT_QUOTES, 'UTF-8');
            $line = (int)($e['line'] ?? 0);
            echo '<div style="max-width:900px;margin:40px auto;padding:20px;border:2px solid #c00;background:#fee;border-radius:8px;font-family:system-ui,Segoe UI,Arial;">'
                . '<h2 style="margin:0 0 12px;color:#c00;font-weight:700;">Admin fatal error</h2>'
                . '<p style="margin:0 0 8px;color:#222;">' . $msg . '</p>'
                . '<p style="margin:0;color:#666;font-size:13px;">' . $file . ' on line ' . $line . '</p>'
                . '</div>';
        } else {
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
            echo '<div style="max-width:900px;margin:40px auto;padding:20px;border:2px solid #c00;background:#fee;border-radius:8px;font-family:system-ui,Segoe UI,Arial;">'
                . '<h2 style="margin:0 0 12px;color:#c00;font-weight:700;">Admin error</h2>'
                . '<p style="margin:0 0 8px;color:#222;">A technical issue occurred while processing your request.</p>'
                . '</div>';
        }
    }
});

$required = [
    __DIR__ . '/../includes/database.php',
    __DIR__ . '/../includes/url_helper.php',
    __DIR__ . '/../admin_sidebar_components.php',
];
$missing = array_values(array_filter($required, function($p){ return !file_exists($p); }));
if (!empty($missing)) {
    if (!isset($_SESSION['admin_errors'])) {
        $_SESSION['admin_errors'] = [];
    }
    foreach ($missing as $p) {
        $_SESSION['admin_errors'][] = [
            'type' => 'Missing File',
            'message' => 'File not found: ' . $p,
            'file' => basename($p),
            'line' => 0,
            'time' => date('H:i:s')
        ];
    }
    $list = '';
    foreach ($missing as $p) {
        $list .= '<li style="margin-bottom:6px">' . htmlspecialchars(str_replace('\\','/',$p), ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '<div style="max-width:900px;margin:40px auto;padding:20px;border:2px solid #c00;background:#fee;border-radius:8px;font-family:system-ui,Segoe UI,Arial;">'
        . '<h2 style="margin:0 0 12px;color:#c00;font-weight:700;">Required files missing</h2>'
        . '<p style="margin:0 0 8px;color:#222;">Restore the files from backup and reload.</p>'
        . '<ul style="margin:12px 0 0 18px;color:#a00;">' . $list . '</ul>'
        . '</div>';
    exit;
}

// Custom error handler for admin panel
function adminErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];

    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $errorType: $errstr in $errfile on line $errline\n";

    // Log to file
    error_log($message, 3, __DIR__ . '/../logs/php_errors_admin.log');

    // Store in session for display
    $_SESSION['admin_errors'][] = [
        'type' => $errorType,
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline,
        'time' => date('H:i:s')
    ];

    return true;
}

// Set custom error handler
set_error_handler('adminErrorHandler');

// Exception handler for uncaught exceptions
function adminExceptionHandler($exception) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    $message .= "Stack trace:\n" . $exception->getTraceAsString() . "\n";

    // Log to file
    error_log($message, 3, __DIR__ . '/../logs/php_errors_admin.log');

    // Store in session for display
    $_SESSION['admin_errors'][] = [
        'type' => 'Uncaught Exception',
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
        'time' => date('H:i:s')
    ];
}

// Set exception handler
set_exception_handler('adminExceptionHandler');

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get statistics
$stats = [
    'total_products' => 0,
    'total_orders' => 0,
    'total_users' => 0,
    'pending_orders' => 0
];

if ($db) {
    try {
        // Get total products
        $stmt = $db->query('SELECT COUNT(*) FROM products');
        $stats['total_products'] = $stmt->fetchColumn();

        // Get total orders
        $stmt = $db->query('SELECT COUNT(*) FROM orders');
        $stats['total_orders'] = $stmt->fetchColumn();

        // Get total users
        $stmt = $db->query('SELECT COUNT(*) FROM users');
        $stats['total_users'] = $stmt->fetchColumn();

        // Get pending orders
        $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $stats['pending_orders'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting stats: " . $e->getMessage());
    }
}

// Generate dashboard content
$content = '<div class="max-w-7xl mx-auto">';

// Error Display Section
if (isset($_SESSION['admin_errors']) && !empty($_SESSION['admin_errors'])) {
    $content .= '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800">Errors Detected (' . count($_SESSION['admin_errors']) . ')</h3>
                <div class="mt-2 text-sm text-red-700 max-h-60 overflow-y-auto">
                    <ul class="list-disc list-inside space-y-1">';
    foreach ($_SESSION['admin_errors'] as $error) {
        $content .= '<li class="font-mono text-xs">
            <span class="font-bold">[' . htmlspecialchars($error['type'] ?? '') . ']</span>
            ' . htmlspecialchars($error['message'] ?? '');
        if (!empty($error['file'])) {
            $content .= ' in <span class="text-red-900">' . htmlspecialchars($error['file']) . '</span>';
        }
        if (!empty($error['line'])) {
            $content .= ' on line <span class="text-red-900">' . htmlspecialchars($error['line']) . '</span>';
        }
        if (!empty($error['time'])) {
            $content .= ' at <span class="text-red-600">' . htmlspecialchars($error['time']) . '</span>';
        }
        $content .= '</li>';
    }
    $content .= '</ul>
                </div>
            </div>
            <div class="ml-auto pl-3">
                <a href="?clear_errors=1" class="text-red-600 hover:text-red-800 text-sm font-medium">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </div>
    </div>';

    if (isset($_GET['clear_errors'])) {
        unset($_SESSION['admin_errors']);
    }
}

$content .= '
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 mb-6 text-white">
        <h1 class="text-2xl font-bold mb-2">Welcome to JointBuddy Admin!</h1>
        <p class="text-green-100">Manage your products, orders, and customers all in one place.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Products</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">' . $stats['total_products'] . '</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">' . $stats['total_orders'] . '</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">' . $stats['total_users'] . '</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Orders</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">' . $stats['pending_orders'] . '</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Recent Activity</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-box text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">New product added</p>
                        <p class="text-xs text-gray-500">2 hours ago</p>
                    </div>
                </div>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-shopping-cart text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">New order received</p>
                        <p class="text-xs text-gray-500">3 hours ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Quick Actions</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="' . adminUrl('/products/') . '" class="flex items-center p-4 rounded-lg border-2 border-gray-200 hover:border-green-500 hover:bg-green-50 transition-all">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-box text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Products</h3>
                        <p class="text-sm text-gray-600">Manage inventory</p>
                    </div>
                </a>

                <a href="' . adminUrl('/orders/') . '" class="flex items-center p-4 rounded-lg border-2 border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition-all">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Orders</h3>
                        <p class="text-sm text-gray-600">Process orders</p>
                    </div>
                </a>

                <a href="' . adminUrl('/users/') . '" class="flex items-center p-4 rounded-lg border-2 border-gray-200 hover:border-purple-500 hover:bg-purple-50 transition-all">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Users</h3>
                        <p class="text-sm text-gray-600">View customers</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>';

// Debug Information Panel
if (isset($_GET['debug'])) {
    $content .= '<div class="bg-gray-800 text-green-400 p-6 rounded-lg shadow-lg mt-6 font-mono text-sm">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center">
            <i class="fas fa-bug mr-2"></i> Debug Information
            <a href="?clear_debug=1" class="ml-auto text-xs text-gray-400 hover:text-white">Clear</a>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="bg-gray-900 p-3 rounded">
                <h3 class="text-white font-bold mb-2">System Info</h3>
                <div class="space-y-1">
                    <div><span class="text-gray-400">PHP Version:</span> ' . PHP_VERSION . '</div>
                    <div><span class="text-gray-400">Server:</span> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</div>
                    <div><span class="text-gray-400">Document Root:</span> ' . $_SERVER['DOCUMENT_ROOT'] . '</div>
                </div>
            </div>

            <div class="bg-gray-900 p-3 rounded">
                <h3 class="text-white font-bold mb-2">Performance</h3>
                <div class="space-y-1">
                    <div><span class="text-gray-400">Memory Usage:</span> ' . number_format(memory_get_usage() / 1024 / 1024, 2) . ' MB</div>
                    <div><span class="text-gray-400">Peak Memory:</span> ' . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB</div>
                    <div><span class="text-gray-400">Execution Time:</span> ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's</div>
                </div>
            </div>
        </div>
    </div>';
}

// Render the page with sidebar
echo adminSidebarWrapper('Dashboard', $content, 'dashboard');
