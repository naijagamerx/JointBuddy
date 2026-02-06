<?php
/**
 * Whoops Error Handler Setup
 * Provides beautiful error pages for development
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;

function setupWhoops($returnJson = false) {
    $whoops = new Run();

    if ($returnJson) {
        // Return errors as JSON for AJAX requests
        $whoops->pushHandler(new JsonResponseHandler());
    } else {
        // Pretty HTML error page
        $handler = new PrettyPageHandler();

        // Set page title
        $handler->setPageTitle("CannaBuddy - Error Occurred");

        // Add custom variables to the error page
        $handler->addDataTable('CannaBuddy Application', [
            'Environment' => 'Development',
            'Debug Mode' => 'Enabled',
            'Application' => 'CannaBuddy E-commerce',
            'Timestamp' => date('Y-m-d H:i:s')
        ]);

        // Add database info if available
        global $db;
        if (isset($db) && $db instanceof PDO) {
            try {
                $handler->addDataTable('Database', [
                    'Status' => 'Connected',
                    'Driver' => $db->getAttribute(PDO::ATTR_DRIVER_NAME),
                ]);
            } catch (Exception $e) {
                // Ignore
            }
        }

        $whoops->pushHandler($handler);
    }

    // Register the handler
    $whoops->register();

    return $whoops;
}

// Setup Whoops for the current request if in development
if (isset($_GET['debug']) || isset($_GET['whoops'])) {
    setupWhoops();
}

/**
 * Log errors to file with more detail
 */
function logDetailedError($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED',
    ];

    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $errorType: $errstr in $errfile on line $errline\n";
    $message .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'CLI') . "\n";
    $message .= "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'CLI') . "\n";
    $message .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
    $message .= "Memory Usage: " . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    $message .= "Peak Memory: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    $message .= str_repeat('-', 80) . "\n\n";

    // Log to custom error file
    $logFile = __DIR__ . '/../logs/detailed_errors.log';
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    error_log($message, 3, $logFile);

    // Also log to PHP error log
    error_log($message, 0);
}

/**
 * Setup custom error handler for admin pages
 */
function setupAdminErrorHandler() {
    set_error_handler('logDetailedError');
    set_exception_handler(function($exception) {
        logDetailedError(
            E_ERROR,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        echo "<div style='background:#fee; border:2px solid #c00; padding:20px; margin:20px; border-radius:5px;'>";
        echo "<h2 style='color:#c00;'>Exception Caught</h2>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>";
        echo htmlspecialchars($exception->getTraceAsString());
        echo "</pre></details>";
        echo "</div>";
    });
}

// Auto-setup for admin pages if debug mode is enabled
if (isset($_GET['debug_admin']) || (isset($_GET['debug']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false)) {
    setupWhoops();
    setupAdminErrorHandler();
}
