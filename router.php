<?php
// Custom router for PHP built-in server
$route = $_SERVER['REQUEST_URI'];

// If it's a static file, serve it
$path = parse_url($route, PHP_URL_PATH);
$filepath = __DIR__ . $path;

if (file_exists($filepath) && is_file($filepath)) {
    return false; // Let PHP serve the file
}

// Otherwise, route through index.php
require __DIR__ . '/index.php';
