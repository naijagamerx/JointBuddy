<?php
// Route.php - File-based routing system
// This file handles all routing without .htaccess

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Clean up the path
$path = trim($path, '/');

// Get the base directory path dynamically
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = dirname($scriptName);
if ($baseDir === '.' || $baseDir === '\\') {
    $baseDir = '';
} else {
    $baseDir = trim($baseDir, '/');
}

// Handle different path scenarios
if ($path === '' || $path === $baseDir || $path === $baseDir . '/') {
    $route = 'home';
} else {
    // Remove base directory prefix if present
    if (!empty($baseDir) && strpos($path, $baseDir . '/') === 0) {
        $route = substr($path, strlen($baseDir . '/'));
    } else {
        $route = $path;
    }
    
    // Clean up any trailing slashes
    $route = trim($route, '/');
}

// Debug output if requested
if (isset($_GET['debug_routing'])) {
    header('Content-Type: text/html');
    echo "<h2>Routing Debug</h2>";
    echo "<p><strong>REQUEST_URI:</strong> " . htmlspecialchars($request_uri) . "</p>";
    echo "<p><strong>Path:</strong> " . htmlspecialchars($path) . "</p>";
    echo "<p><strong>Final Route:</strong> '" . htmlspecialchars($route) . "'</p>";
    exit;
}

return $route;
?>