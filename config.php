<?php
/**
 * Global Configuration File
 * Include this file at the top of every page
 *
 * ERROR DISPLAY SETTINGS
 * Set to false in production!
 */
define('DEBUG_MODE', false); // Set to false in production!

if (DEBUG_MODE) {
    // Show all errors on screen (development)
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Hide all errors (production)
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0755, true);
}

/**
 * Global Error Handler
 * Catches all errors and displays them nicely in debug mode
 */
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (DEBUG_MODE) {
        echo "<div style='background:#ffebee;color:#c62828;padding:10px;margin:5px;border-radius:5px;font-family:monospace;'>";
        echo "<strong>ERROR:</strong> $message in <strong>$file</strong> on line <strong>$line</strong>";
        echo "</div>";
    }
    error_log("ERROR: $message in $file on line $line");
    return true;
});

/**
 * Global Exception Handler
 * Catches unhandled exceptions
 */
set_exception_handler(function($exception) {
    $msg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($msg);
    if (DEBUG_MODE) {
        echo "<div style='background:#ffebee;color:#c62828;padding:15px;margin:10px;border-radius:5px;font-family:monospace;'>";
        echo "<h3 style='margin-top:0;'>Uncaught Exception</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre></details>";
        echo "</div>";
    } else {
        echo "An error occurred. Please try again later.";
    }
    exit(1);
});
