<?php
// Enhanced Error Handler with Whoops Integration
// Professional error handling with beautiful development interface

if (defined('ENHANCED_ERROR_HANDLER_LOADED')) {
    return;
}
define('ENHANCED_ERROR_HANDLER_LOADED', true);

// Load Whoops if available
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $whoops = new \Whoops\Run;
    
    // Determine environment
    $isDevelopment = enhancedErrorHandler::isDevelopmentEnvironment();
    
    if ($isDevelopment) {
        // In development, show detailed error information with Whoops
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
    } else {
        // In production, use custom handlers
        $whoops->pushHandler(new \Whoops\Handler\CallbackHandler(function($exception, $inspector, $run) {
            enhancedErrorHandler::handleException($exception);
        }));
        $whoops->register();
    }
} catch (Exception $e) {
    // Fallback to basic error handling if Whoops fails to load
    error_log("Whoops failed to load: " . $e->getMessage());
}

/**
 * Enhanced Error Handler Class
 */
class enhancedErrorHandler {
    
    private static $isDevelopment = false;
    
    /**
     * Initialize enhanced error handling
     */
    public static function init() {
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set exception handler (will be overridden by Whoops in development)
        set_exception_handler([self::class, 'handleException']);
        
        // Handle fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Set error reporting level
        self::$isDevelopment = self::isDevelopmentEnvironment();
        error_reporting(self::$isDevelopment ? E_ALL : E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING);
        ini_set('display_errors', self::$isDevelopment ? 1 : 0);
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/../logs/error.log');
        
        // Create logs directory if it doesn't exist
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Check if we're in development environment
     */
    public static function isDevelopmentEnvironment() {
        // Check for local development indicators
        $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost', 
            '127.0.0.1',
            '::1'
        ]);
        
        // Check for MAMP/WAMP/XAMPP indicators
        $isLocalServer = strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'MAMP') !== false ||
                        strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'WAMP') !== false ||
                        strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'XAMPP') !== false;
        
        // Check for development flag in URL
        $isDevUrl = strpos($_SERVER['REQUEST_URI'] ?? '', 'dev') !== false;
        
        return $isLocal || $isLocalServer || $isDevUrl;
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($severity, $message, $file, $line) {
        // Don't handle errors if they're suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
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
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $errorType = $errorTypes[$severity] ?? 'Unknown Error';
        $errorMsg = "[$errorType] $message in $file on line $line";
        
        // Log the error
        error_log($errorMsg);
        
        // Only show detailed errors in development mode
        // In production, errors are handled by Whoops callback or this handler
        if (!self::$isDevelopment && !headers_sent()) {
            self::showUserFriendlyError($errorType);
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        $errorMsg = "Uncaught Exception: $message in $file:$line\n$trace";
        error_log($errorMsg);
        
        // In development, let Whoops handle it
        // In production, show user-friendly error
        if (!self::$isDevelopment && !headers_sent()) {
            self::showUserFriendlyException($message);
        }
    }
    
    /**
     * Handle fatal errors and shutdown
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorMsg = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            error_log($errorMsg);
            
            if (!self::$isDevelopment && !headers_sent()) {
                self::showUserFriendlyError('Fatal Error');
            }
        }
    }
    
    /**
     * Show user-friendly error message
     */
    private static function showUserFriendlyError($type = 'Technical Issue') {
        self::showGenericErrorPage('We encountered a technical issue while processing your request.', $type);
    }
    
    /**
     * Show user-friendly exception message
     */
    private static function showUserFriendlyException($message = 'An unexpected error occurred') {
        // Don't expose database details to users
        $safeMessage = 'We encountered a technical issue while processing your request.';
        
        // Log the actual message for debugging
        error_log("User saw safe message. Actual error: $message");
        
        self::showGenericErrorPage($safeMessage, 'Technical Issue');
    }
    
    /**
     * Show generic error page
     */
    private static function showGenericErrorPage($message, $title = 'Technical Issue') {
        // Prevent header injection
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $safeTitle . ' - JointBuddy Admin</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                .error-animation {
                    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.8; }
                }
            </style>
        </head>
        <body class="bg-gray-100 min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
                <div class="text-center">
                    <div class="error-animation">
                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">' . $safeTitle . '</h1>
                    <p class="text-gray-600 mb-6">' . $safeMessage . '</p>
                    <p class="text-sm text-gray-500 mb-8">Please try again or contact support if the problem persists.</p>
                    
                    <div class="space-y-3">
                        <button onclick="history.back()" class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Go Back
                        </button>
                        <a href="' . adminUrl('/') . '" class="block w-full bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded transition-colors text-center">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    </div>
                    
                    <div class="mt-8 pt-4 border-t border-gray-200">
                        <div class="text-xs text-gray-400">
                            <i class="fas fa-shield-alt mr-1"></i>
                            JointBuddy Admin Panel v2.0
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            Error ID: ' . uniqid() . '
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        
        echo $html;
        exit(1);
    }
    
    /**
     * SQL Error Handler - Specific handling for database errors
     */
    public static function handleSQLError($exception, $context = 'database operation') {
        $message = $exception->getMessage();
        
        // Log the full error for debugging
        error_log("SQL Error in $context: $message");
        
        // Create user-friendly message based on error type
        $userMessage = self::getSQLUserMessage($message);
        
        // Show appropriate error page
        self::showGenericErrorPage($userMessage, 'Database Issue');
    }
    
    /**
     * Convert SQL error messages to user-friendly text
     */
    private static function getSQLUserMessage($sqlMessage) {
        $message = strtolower($sqlMessage);
        
        if (strpos($message, 'connection') !== false) {
            return 'Unable to connect to the database. Please check your connection and try again.';
        }
        
        if (strpos($message, 'table') !== false) {
            return 'Database structure issue. Please contact support.';
        }
        
        if (strpos($message, 'column') !== false || strpos($message, 'field') !== false) {
            return 'Database field issue. Please contact support.';
        }
        
        if (strpos($message, 'duplicate') !== false) {
            return 'This information already exists. Please check your input and try again.';
        }
        
        if (strpos($message, 'foreign key') !== false) {
            return 'Cannot complete this action due to related data constraints.';
        }
        
        if (strpos($message, 'syntax') !== false) {
            return 'Database query issue. Please contact support.';
        }
        
        // Default fallback
        return 'A database error occurred while processing your request.';
    }
    
    /**
     * Wrap database operations in try-catch with error handling
     */
    public static function withErrorHandling($callback, $context = 'operation') {
        try {
            return $callback();
        } catch (PDOException $e) {
            self::handleSQLError($e, $context);
        } catch (Exception $e) {
            self::handleException($e);
        }
    }
}

// Initialize the enhanced error handler
enhancedErrorHandler::init();

/**
 * Helper function to safely execute database operations
 */
function safeExecute($callback, $context = 'database operation') {
    return enhancedErrorHandler::withErrorHandling($callback, $context);
}

/**
 * Helper function to display user-friendly error messages
 */
function showUserError($message = 'A technical issue occurred. Please try again.') {
    enhancedErrorHandler::showGenericErrorPage($message, 'Technical Issue');
}

/**
 * Database error handler wrapper
 */
function handleDatabaseError($exception, $context = 'database operation') {
    enhancedErrorHandler::handleSQLError($exception, $context);
}
?>
