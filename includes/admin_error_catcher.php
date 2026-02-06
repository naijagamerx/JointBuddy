<?php
/**
 * Admin Error Catcher
 * Comprehensive error handling for admin pages
 */

if (!defined('INCLUDED_ADMIN_ERROR_CATCHER')) {
    define('INCLUDED_ADMIN_ERROR_CATCHER', true);

    /**
     * Setup comprehensive error handling for admin pages
     */
    function setupAdminErrorHandling() {
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

        // Setup error logging
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        ini_set('log_errors', 1);
        ini_set('error_log', $logDir . '/php_errors_admin.log');

        // Custom error handler
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
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
            $message .= "Request: " . ($_SERVER['REQUEST_URI'] ?? 'CLI') . "\n";
            $message .= "Memory: " . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
            $message .= str_repeat('-', 80) . "\n";

            // Log error
            error_log($message, 3, __DIR__ . '/../logs/php_errors_admin.log');

            // Store in session for display
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['admin_errors'][] = [
                'type' => $errorType,
                'message' => $errstr,
                'file' => basename($errfile),
                'line' => $errline,
                'time' => date('H:i:s'),
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ];

            return true;
        });

        // Exception handler
        set_exception_handler(function($exception) {
            $timestamp = date('Y-m-d H:i:s');
            $message = "[$timestamp] Uncaught Exception: " . $exception->getMessage() . "\n";
            $message .= "File: " . $exception->getFile() . "\n";
            $message .= "Line: " . $exception->getLine() . "\n";
            $message .= "Stack trace:\n" . $exception->getTraceAsString() . "\n";
            $message .= str_repeat('=', 80) . "\n";

            // Log exception
            error_log($message, 3, __DIR__ . '/../logs/php_errors_admin.log');

            // Store in session
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['admin_errors'][] = [
                'type' => 'Uncaught Exception',
                'message' => $exception->getMessage(),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
                'time' => date('H:i:s'),
                'trace' => $exception->getTraceAsString()
            ];
        });
    }

    /**
     * Render error display for admin pages
     */
    function renderAdminErrors() {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (isset($_SESSION['admin_errors']) && !empty($_SESSION['admin_errors'])) {
            $errorCount = count($_SESSION['admin_errors']);
            $errors = $_SESSION['admin_errors'];

            echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6" style="background: #fee; border-left: 4px solid #c00; padding: 16px; margin-bottom: 24px; border-radius: 4px;">';
            echo '<div style="display: flex; align-items: flex-start;">';
            echo '<div style="flex-shrink: 0; margin-right: 12px;">';
            echo '<i class="fas fa-exclamation-triangle" style="color: #c00; font-size: 20px;"></i>';
            echo '</div>';
            echo '<div style="flex: 1;">';
            echo '<h3 style="color: #c00; margin: 0 0 8px 0; font-size: 16px; font-weight: bold;">Errors Detected (' . $errorCount . ')</h3>';
            echo '<div style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">';
            echo '<ul style="list-style: none; padding: 0; margin: 0;">';

            foreach (array_slice($errors, -10) as $index => $error) {
                echo '<li style="margin-bottom: 8px; padding: 8px; background: rgba(255,255,255,0.5); border-radius: 4px;">';
                echo '<strong style="color: #c00;">[' . safe_html($error['type'] ?? '') . ']</strong> ';
                echo safe_html($error['message'] ?? '');
                if (!empty($error['file'])) {
                    echo ' <span style="color: #666;">in ' . safe_html($error['file']) . '</span>';
                }
                if (!empty($error['line'])) {
                    echo ' <span style="color: #666;">on line ' . safe_html((string)$error['line']) . '</span>';
                }
                if (!empty($error['time'])) {
                    echo ' <span style="color: #999; font-size: 11px;">at ' . safe_html($error['time']) . '</span>';
                }
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
            echo '<div style="margin-top: 8px;">';
            echo '<a href="?clear_errors=1" style="color: #c00; text-decoration: none; font-size: 14px; font-weight: bold;">';
            echo '<i class="fas fa-times"></i> Clear Errors';
            echo '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Clear errors if requested
            if (isset($_GET['clear_errors'])) {
                unset($_SESSION['admin_errors']);
                echo '<script>window.location.href = window.location.pathname;</script>';
            }
        }
    }

    /**
     * Get error statistics
     */
    function getErrorStats() {
        if (!isset($_SESSION)) {
            session_start();
        }

        $stats = [
            'count' => isset($_SESSION['admin_errors']) ? count($_SESSION['admin_errors']) : 0,
            'fatal' => 0,
            'warnings' => 0,
            'notices' => 0
        ];

        if (isset($_SESSION['admin_errors'])) {
            foreach ($_SESSION['admin_errors'] as $error) {
                if (strpos($error['type'], 'Fatal') !== false || strpos($error['type'], 'Error') !== false) {
                    $stats['fatal']++;
                } elseif (strpos($error['type'], 'Warning') !== false) {
                    $stats['warnings']++;
                } elseif (strpos($error['type'], 'Notice') !== false) {
                    $stats['notices']++;
                }
            }
        }

        return $stats;
    }

    // Auto-setup if debug mode is enabled
    if (isset($_GET['debug']) || isset($_GET['debug_admin'])) {
        setupAdminErrorHandling();
    }
}
