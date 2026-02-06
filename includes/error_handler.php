<?php
// Comprehensive Error Handler for JointBuddy Admin
// Professional error handling to replace raw 500 errors

if (defined('ERROR_HANDLER_LOADED')) {
    return;
}
define('ERROR_HANDLER_LOADED', true);

/**
 * Custom error handler that displays user-friendly messages
 * instead of technical errors to end users
 */
class JointBuddyErrorHandler {
    
    private static $isDevelopment = false;

    /**
     * Initialize error handling
     */
    public static function init() {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        self::$isDevelopment = self::isDevelopmentEnvironment();
        if (self::$isDevelopment) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
            ini_set('display_errors', 0);
        }
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/../logs/error.log');
    }
    
    /**
     * Check if we're in development environment
     */
    private static function isDevelopmentEnvironment() {
        // Check for local development indicators
        $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost', 
            '127.0.0.1',
            '::1'
        ]);
        
        // Check for MAMP/WAMP indicators
        $isLocalServer = strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'MAMP') !== false ||
                        strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'WAMP') !== false;
        
        return $isLocal || $isLocalServer;
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
        
        // Only show detailed errors in development
        if (self::$isDevelopment) {
            self::showDevelopmentError($errorType, $message, $file, $line);
        } else {
            self::showUserFriendlyError($errorType);
        }
        
        return true; // Prevent default error handler
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
        
        if (self::$isDevelopment) {
            self::showDevelopmentException($exception);
        } else {
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
            
            if (self::$isDevelopment) {
                self::showDevelopmentError('Fatal Error', $error['message'], $error['file'], $error['line']);
            } else {
                self::showUserFriendlyError('Fatal Error');
            }
        }
    }
    
    /**
     * Show development-friendly error details
     */
    private static function showDevelopmentError($type, $message, $file, $line) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>Development Error - JointBuddy</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                .error-container { max-width: 800px; margin: 2rem auto; }
                .code-block { background: #1a1a1a; color: #00ff00; padding: 1rem; border-radius: 0.5rem; font-family: monospace; }
            </style>
        </head>
        <body class="bg-gray-900 text-white p-8">
            <div class="error-container">
                <div class="bg-red-900 border border-red-700 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-bug text-red-400 text-2xl mr-3"></i>
                        <h1 class="text-2xl font-bold text-red-400">Development Error</h1>
                    </div>
                    <div class="bg-red-800 p-4 rounded mb-4">
                        <h2 class="font-bold mb-2">' . htmlspecialchars($type) . '</h2>
                        <p class="mb-2">' . htmlspecialchars($message) . '</p>
                        <p class="text-sm opacity-75">
                            <strong>File:</strong> ' . htmlspecialchars($file) . ' 
                            <strong>Line:</strong> ' . $line . '
                        </p>
                    </div>
                    <div class="text-sm text-gray-300">
                        <p><i class="fas fa-info-circle mr-2"></i>This error is shown because you are in development mode.</p>
                        <p><i class="fas fa-code mr-2"></i>Check the error log for more details.</p>
                    </div>
                </div>
                <div class="text-center">
                    <a href="javascript:history.back()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Go Back
                    </a>
                    <a href="' . adminUrl('/') . '" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </body>
        </html>';
        
        echo $html;
        exit(1);
    }
    
    /**
     * Show user-friendly error message
     */
    private static function showUserFriendlyError($type = 'Technical Issue') {
        self::showGenericErrorPage('We encountered a technical issue while processing your request.', $type);
    }
    
    /**
     * Show development-friendly exception details
     */
    private static function showDevelopmentException($exception) {
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>Development Exception - JointBuddy</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                .error-container { max-width: 900px; margin: 2rem auto; }
                .code-block { background: #1a1a1a; color: #00ff00; padding: 1rem; border-radius: 0.5rem; font-family: monospace; overflow-x: auto; }
                .trace-block { background: #2d2d2d; color: #f8f8f2; padding: 1rem; border-radius: 0.5rem; font-family: monospace; font-size: 0.85rem; }
            </style>
        </head>
        <body class="bg-gray-900 text-white p-8">
            <div class="error-container">
                <div class="bg-red-900 border border-red-700 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-bug text-red-400 text-2xl mr-3"></i>
                        <h1 class="text-2xl font-bold text-red-400">Development Exception</h1>
                    </div>
                    <div class="bg-red-800 p-4 rounded mb-4">
                        <h2 class="font-bold mb-2">Exception Details</h2>
                        <p class="mb-2"><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>
                        <p class="mb-2"><strong>File:</strong> ' . htmlspecialchars($file) . '</p>
                        <p class="mb-2"><strong>Line:</strong> ' . $line . '</p>
                    </div>
                    <div class="mb-4">
                        <h3 class="font-bold mb-2">Stack Trace:</h3>
                        <div class="trace-block">' . nl2br(htmlspecialchars($trace)) . '</div>
                    </div>
                    <div class="text-sm text-gray-300">
                        <p><i class="fas fa-info-circle mr-2"></i>This detailed error is shown because you are in development mode.</p>
                        <p><i class="fas fa-code mr-2"></i>Check the error log for more details.</p>
                    </div>
                </div>
                <div class="text-center">
                    <a href="javascript:history.back()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Go Back
                    </a>
                    <a href="' . url('/') . '" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                </div>
            </div>
        </body>
        </html>';

        echo $html;
        exit(1);
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
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>' . htmlspecialchars($title) . ' - JointBuddy Admin</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">' . htmlspecialchars($title) . '</h1>
                    <p class="text-gray-600 mb-6">' . htmlspecialchars($message) . '</p>
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
                        <p class="text-xs text-gray-400">
                            <i class="fas fa-shield-alt mr-1"></i>
                            JointBuddy Admin Panel
                        </p>
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

// Initialize the error handler
// Initialize Error Handling
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        if (class_exists('Whoops\Run')) {
            $whoops = new \Whoops\Run;
            
            // Register PrettyPageHandler for standard requests
            $prettyPageHandler = new \Whoops\Handler\PrettyPageHandler;
            $prettyPageHandler->setPageTitle("CannaBuddy Error");
            
            // Add custom data table to the error page
            $prettyPageHandler->addDataTable('CannaBuddy Application', [
                'User Session' => $_SESSION ?? [],
                'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'Method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
            ]);
            
            // Check if AJAX/JSON request
            $isJson = 
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            
            if ($isJson) {
                $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler);
            } else {
                $whoops->pushHandler($prettyPageHandler);
            }
            
            $whoops->register();
            define('WHOOPS_LOADED', true);
        }
    } catch (Exception $e) {
        // Fallback to custom handler
        error_log("Failed to load Whoops: " . $e->getMessage());
    }
}

if (!defined('WHOOPS_LOADED')) {
    JointBuddyErrorHandler::init();
}

/**
 * Helpers (kept for compatibility)
 */
function safeExecute($callback, $context = 'database operation') {
    return $callback(); // Whoops will catch exceptions
}

function showUserError($message = 'A technical issue occurred. Please try again.') {
    throw new Exception($message); // Whoops will display it
}

/**
 * AppError Class - Standardized Error Handling for Security
 * Provides safe error messages without exposing sensitive information
 */
class AppError {
    /**
     * Check if running in development environment
     */
    public static function isDevelopment() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * Handle database errors safely
     * @param Exception $e The exception
     * @param string $userMessage User-facing message
     * @return string Safe error message
     */
    public static function handleDatabaseError($e, $userMessage = 'Database error occurred') {
        // Always log the actual error for debugging
        error_log("DB Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

        // In development, show the actual error
        if (self::isDevelopment()) {
            return $e->getMessage();
        }

        // In production, show generic message
        return $userMessage;
    }

    /**
     * Handle general errors safely
     * @param Exception $e The exception
     * @param string $userMessage User-facing message
     * @return string Safe error message
     */
    public static function handleError($e, $userMessage = 'An error occurred') {
        // Always log the actual error for debugging
        error_log("Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

        // In development, show the actual error
        if (self::isDevelopment()) {
            return $e->getMessage();
        }

        // In production, show generic message
        return $userMessage;
    }

    /**
     * Get safe error message for database operations
     * @param Exception $e The exception
     * @return string Safe error message
     */
    public static function getDatabaseErrorMessage($e) {
        $message = strtolower($e->getMessage());

        // Map common database errors to user-friendly messages
        if (strpos($message, 'duplicate entry') !== false) {
            return 'This record already exists. Please check your input.';
        }

        if (strpos($message, 'foreign key constraint') !== false) {
            return 'Cannot complete this action due to related data.';
        }

        if (strpos($message, 'connection') !== false) {
            return 'Database connection issue. Please try again.';
        }

        if (strpos($message, 'syntax') !== false) {
            return 'Invalid data format. Please check your input.';
        }

        // Default generic message
        return 'A database error occurred. Please try again.';
    }

    /**
     * Validate and sanitize input
     * @param mixed $value The value to validate
     * @param string $type Expected type (string, int, float, email)
     * @param array $options Additional options (max_length, min_value, etc.)
     * @return mixed Sanitized value or throws exception
     */
    public static function validateInput($value, $type = 'string', $options = []) {
        switch ($type) {
            case 'string':
                $maxLength = $options['max_length'] ?? 255;
                $value = trim((string)$value);
                if (strlen($value) > $maxLength) {
                    throw new InvalidArgumentException("Value too long (max $maxLength characters)");
                }
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            case 'int':
                $value = filter_var($value, FILTER_VALIDATE_INT);
                if ($value === false) {
                    throw new InvalidArgumentException('Invalid integer value');
                }
                if (isset($options['min']) && $value < $options['min']) {
                    throw new InvalidArgumentException("Value must be at least {$options['min']}");
                }
                if (isset($options['max']) && $value > $options['max']) {
                    throw new InvalidArgumentException("Value must be at most {$options['max']}");
                }
                return $value;

            case 'float':
                $value = filter_var($value, FILTER_VALIDATE_FLOAT);
                if ($value === false) {
                    throw new InvalidArgumentException('Invalid number value');
                }
                if (isset($options['min']) && $value < $options['min']) {
                    throw new InvalidArgumentException("Value must be at least {$options['min']}");
                }
                if (isset($options['max']) && $value > $options['max']) {
                    throw new InvalidArgumentException("Value must be at most {$options['max']}");
                }
                return $value;

            case 'email':
                $value = filter_var($value, FILTER_VALIDATE_EMAIL);
                if ($value === false) {
                    throw new InvalidArgumentException('Invalid email address');
                }
                return $value;

            default:
                throw new InvalidArgumentException("Unknown validation type: $type");
        }
    }
}
?>
