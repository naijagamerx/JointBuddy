<?php
/**
 * Bootstrap File - Application initialization
 *
 * This file should be included at the top of every file
 * It sets up all core services and helpers
 *
 * Usage:
 * require_once __DIR__ . '/path/to/includes/bootstrap.php';
 *
 * @package CannaBuddy
 */

// Define application paths
define('APP_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', APP_ROOT . '/includes');
define('ADMIN_PATH', APP_ROOT . '/admin');
define('USER_PATH', APP_ROOT . '/user');
define('ASSETS_PATH', APP_ROOT . '/assets');
define('VIEWS_PATH', APP_ROOT . '/views');
define('TEST_DELETE_PATH', APP_ROOT . '/test_delete');

// Load core helpers
require_once INCLUDES_PATH . '/session_helper.php';
require_once INCLUDES_PATH . '/url_helper.php';
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/legal_pages.php';

// Load services
require_once INCLUDES_PATH . '/services/Services.php';

// Load middleware
require_once INCLUDES_PATH . '/middleware/AuthMiddleware.php';
require_once INCLUDES_PATH . '/middleware/CsrfMiddleware.php';

// Load validation
require_once INCLUDES_PATH . '/validation/Validator.php';

// Load commerce services
require_once INCLUDES_PATH . '/commerce/CurrencyService.php';

// Load layout components
require_once INCLUDES_PATH . '/admin_layout.php';

// Initialize session
ensureSessionStarted();

// Initialize services
try {
    Services::initialize();
} catch (Exception $e) {
    error_log("Bootstrap initialization failed: " . $e->getMessage());
    // Continue in degraded mode if possible
}

// Set error reporting based on environment
$appEnv = getenv('APP_ENV') ?: 'production';

if ($appEnv === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Load additional helpers if they exist
$helpers = [
    INCLUDES_PATH . '/error_handler.php',
    INCLUDES_PATH . '/security_headers.php',
    // NOTE: admin_auth_check.php is NOT loaded here - it should only be used in admin-specific files
    INCLUDES_PATH . '/admin_sidebar_components.php',
    INCLUDES_PATH . '/user_dashboard_components.php',
    INCLUDES_PATH . '/payment_methods_service.php',
];

foreach ($helpers as $helper) {
    if (file_exists($helper)) {
        require_once $helper;
    }
}

// Register autoload for custom classes
spl_autoload_register(function ($class) {
    $paths = [
        INCLUDES_PATH . '/commerce/',
        INCLUDES_PATH . '/seo/',
        INCLUDES_PATH . '/services/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Set default exception handler for production
if ($appEnv === 'production') {
    set_exception_handler(function ($exception) {
        error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal server error']);
        } else {
            $errorView = VIEWS_PATH . '/errors/500.php';
            if (file_exists($errorView)) {
                require_once $errorView;
            } else {
                // Fallback error page if view file doesn't exist
                echo '<!DOCTYPE html><html><head><title>500 Error</title>';
                echo '<style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;}h1{color:#e53e3e;}</style>';
                echo '</head><body>';
                echo '<h1>500 - Internal Server Error</h1>';
                echo '<p>Something went wrong. Please try again later.</p>';
                echo '<p><a href="/">Go to Homepage</a></p>';
                echo '</body></html>';
            }
        }
    });
}

/**
 * Helper function to get database connection
 * Shortcut for Services::db()
 *
 * @return PDO
 */
function db(): PDO {
    return Services::db();
}

/**
 * Helper function to get admin auth
 * Shortcut for Services::adminAuth()
 *
 * @return AdminAuth
 */
function adminAuth(): AdminAuth {
    return Services::adminAuth();
}

/**
 * Helper function to get user auth
 * Shortcut for Services::userAuth()
 *
 * @return UserAuth
 */
function userAuth(): UserAuth {
    return Services::userAuth();
}

/**
 * Helper function to get currency service
 * Shortcut for Services::currencyService()
 *
 * @return CurrencyService
 */
function currencyService(): CurrencyService {
    return Services::currencyService();
}
