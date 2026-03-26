<?php
/**
 * Link Auditor for JointBuddy
 * Scans the codebase for URL calls and verifies their validity
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$appRoot = __DIR__;
require_once $appRoot . '/includes/url_helper.php';

// Define the routes that are known to exist in index.php (the whitelist)
$indexRoutes = [
    '', 'home', 'shop', 'about', 'contact', 'register',
    'admin', 'admin/logout', 'admin/products', 'admin/products/add',
    'admin/products/edit', 'admin/products/delete', 'admin/products/inquiries',
    'admin/products/inventory', 'admin/products/reviews', 'admin/orders',
    'admin/orders/view', 'admin/orders/update', 'admin/users', 'admin/users/view',
    'admin/categories', 'admin/analytics', 'admin/hero-images', 'admin/settings',
    'admin/settings/appearance', 'admin/settings/email', 'admin/settings/notifications',
    'admin/profile', 'admin/activity'
];

function scanDirectory($dir, &$results) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.git' || $file === 'vendor') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            scanDirectory($path, $results);
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            checkLinksInFile($path, $results);
        }
    }
}

function checkLinksInFile($filePath, &$results) {
    $content = file_get_contents($filePath);
    
    // Pattern to match url(), adminUrl(), userUrl(), shopUrl()
    // Matches: url('path'), url("/path"), adminUrl(...) etc.
    $pattern = '/(url|adminUrl|userUrl|shopUrl)\s*\(\s*[\'"]([^\'"]*)[\'"]\s*\)/';
    
    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[2] as $index => $urlPath) {
            $func = $matches[1][$index];
            $fullPath = '';
            
            switch ($func) {
                case 'url': $fullPath = $urlPath; break;
                case 'adminUrl': $fullPath = 'admin/' . ltrim($urlPath, '/'); break;
                case 'userUrl': $fullPath = 'user/' . ltrim($urlPath, '/'); break;
                case 'shopUrl': $fullPath = 'shop/' . ltrim($urlPath, '/'); break;
            }
            
            $status = verifyRoute($fullPath);
            if (!$status['valid']) {
                $results[] = [
                    'file' => $filePath,
                    'call' => $matches[0][$index],
                    'resolved' => $fullPath,
                    'reason' => $status['reason']
                ];
            }
        }
    }
}

function verifyRoute($route) {
    global $indexRoutes, $appRoot;
    
    $route = trim($route, '/');
    if ($route === '') return ['valid' => true];
    
    // 1. Check if it's in the index.php whitelist (if it's an admin/user route that usually goes through index)
    // Actually many routes don't go through index.php if they are physical files.
    
    // 2. Check for physical file or directory
    $checkPaths = [
        $appRoot . '/' . $route . '/index.php',
        $appRoot . '/' . $route . '.php',
        $appRoot . '/' . $route
    ];
    
    foreach ($checkPaths as $path) {
        if (file_exists($path)) return ['valid' => true];
    }
    
    // 3. Dynamic routes (product slugs etc)
    if (strpos($route, 'product/') === 0) return ['valid' => true];
    if (strpos($route, 'admin/products/view/') === 0) return ['valid' => true];
    if (strpos($route, 'admin/products/edit/') === 0) return ['valid' => true];
    if (strpos($route, 'admin/products/delete/') === 0) return ['valid' => true];
    
    // 4. Check indexRoutes
    if (in_array($route, $indexRoutes)) return ['valid' => true];
    
    return ['valid' => false, 'reason' => 'No matching file or index route found'];
}

$results = [];
echo "Scanning codebase for broken links...\n";
scanDirectory($appRoot, $results);

if (empty($results)) {
    echo "No broken links found!\n";
} else {
    echo "Found " . count($results) . " potential broken links:\n\n";
    foreach ($results as $r) {
        echo "File: " . $r['file'] . "\n";
        echo "Call: " . $r['call'] . "\n";
        echo "Resolved: " . $r['resolved'] . "\n";
        echo "Reason: " . $r['reason'] . "\n";
        echo "-----------------------------------\n";
    }
}
