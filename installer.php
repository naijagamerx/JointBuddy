<?php
/**
 * CannaBuddy Installer/Config Tool
 *
 * This script helps you:
 * 1. Update database credentials in includes/database.php
 * 2. Toggle DEBUG_MODE in config.php
 * 3. Delete unwanted files/folders before production
 *
 * USAGE:
 * 1. Upload this file to your server
 * 2. Visit: http://yoursite.com/installer.php
 * 3. Make changes and delete unwanted files
 * 4. DELETE installer.php when done!
 *
 * @version 1.0
 */

// Prevent access if already deleted/protected
if (basename(__FILE__) !== 'installer.php') {
    die('Access denied');
}

$messages = [];
$errors = [];

// Get current database config
$dbConfig = getCurrentDbConfig();
$debugMode = getCurrentDebugMode();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_db') {
        if (updateDatabaseConfig($_POST)) {
            $messages[] = '✅ Database credentials updated successfully!';
            $dbConfig = getCurrentDbConfig(); // Refresh
        } else {
            $errors[] = '❌ Failed to update database credentials';
        }
    }

    if ($action === 'toggle_debug') {
        if (updateDebugMode($_POST['debug_mode'])) {
            $messages[] = '✅ DEBUG_MODE updated!';
            $debugMode = getCurrentDebugMode(); // Refresh
        } else {
            $errors[] = '❌ Failed to update DEBUG_MODE';
        }
    }

    if ($action === 'cleanup') {
        $cleanupResults = cleanupFiles($_POST['cleanup_items'] ?? []);
        foreach ($cleanupResults as $result) {
            if ($result['success']) {
                $messages[] = '✅ ' . $result['message'];
            } else {
                $errors[] = '❌ ' . $result['message'];
            }
        }
    }
}

/**
 * Get current database configuration from includes/database.php
 */
function getCurrentDbConfig() {
    $file = __DIR__ . '/includes/database.php';
    if (!file_exists($file)) {
        return [
            'host' => 'localhost',
            'db_name' => '',
            'username' => '',
            'password' => ''
        ];
    }

    $content = file_get_contents($file);

    // Parse the private property values using regex
    preg_match("/private\s+\$host\s*=\s*['\"]([^'\"]+)['\"]/", $content, $hostMatch);
    preg_match("/private\s+\$db_name\s*=\s*['\"]([^'\"]+)['\"]/", $content, $dbMatch);
    preg_match("/private\s+\$username\s*=\s*['\"]([^'\"]+)['\"]/", $content, $userMatch);
    preg_match("/private\s+\$password\s*=\s*['\"]([^'\"]*)['\"]/", $content, $passMatch);

    return [
        'host' => $hostMatch[1] ?? 'localhost',
        'db_name' => $dbMatch[1] ?? '',
        'username' => $userMatch[1] ?? '',
        'password' => $passMatch[1] ?? ''
    ];
}

/**
 * Get current DEBUG_MODE setting
 */
function getCurrentDebugMode() {
    $file = __DIR__ . '/config.php';
    if (!file_exists($file)) {
        return true; // Default to true if file doesn't exist
    }

    $content = file_get_contents($file);
    if (preg_match("/define\s*\(\s*['\"]DEBUG_MODE['\"]\s*,\s*(true|false)\s*\)/", $content, $matches)) {
        return $matches[1] === 'true';
    }
    return true;
}

/**
 * Update database configuration in includes/database.php
 */
function updateDatabaseConfig($data) {
    $file = __DIR__ . '/includes/database.php';
    if (!file_exists($file)) {
        return false;
    }

    $content = file_get_contents($file);

    // Update each property
    $replacements = [
        'host' => $data['db_host'] ?? 'localhost',
        'db_name' => $data['db_name'] ?? '',
        'username' => $data['db_user'] ?? '',
        'password' => $data['db_pass'] ?? ''
    ];

    foreach ($replacements as $prop => $value) {
        $pattern = "/private\s+\$" . $prop . "\s*=\s*['\"][^'\"]*['\"]/";
        $replacement = "private \$" . $prop . " = '" . addslashes($value) . "'";
        $content = preg_replace($pattern, $replacement, $content, 1, $count);

        if ($count === 0) {
            error_log("Failed to update $prop in database.php");
        }
    }

    // Backup original
    copy($file, $file . '.backup.' . date('YmdHis'));

    // Write updated content
    return file_put_contents($file, $content) !== false;
}

/**
 * Update DEBUG_MODE in config.php
 */
function updateDebugMode($enabled) {
    $file = __DIR__ . '/config.php';
    if (!file_exists($file)) {
        return false;
    }

    $content = file_get_contents($file);
    $value = $enabled ? 'true' : 'false';

    // Replace the DEBUG_MODE definition
    $content = preg_replace(
        "/define\s*\(\s*['\"]DEBUG_MODE['\"]\s*,\s*(true|false)\s*\)/",
        "define('DEBUG_MODE', $value)",
        $content
    );

    // Backup original
    copy($file, $file . '.backup.' . date('YmdHis'));

    return file_put_contents($file, $content) !== false;
}

/**
 * Delete files and folders
 */
function cleanupFiles($items) {
    $results = [];
    $basePath = __DIR__;

    // Files and folders to clean up
    $cleanupMap = [
        'mcp_json' => '.mcp.json',
        'cookies' => 'cookies.txt',
        'nul' => 'nul',
        'test_delete' => 'test_delete',
        'agent_md' => 'agent.md',
        'claude_md' => 'CLAUDE.md',
        'changelog' => 'CHANGELOG.md',
        'readme' => 'README.md',
        'deployment' => 'DEPLOYMENT.md',
        'project_status' => 'PROJECT_STATUS.md',
        'discovery' => 'discovery.md',
        'return_prompt' => 'return_prompt.md',
        'checkout_plan' => 'checkout_plan.md',
        'codebase_map' => 'codebase_map_simple.md',
        'composer_json' => 'composer.json',
        'composer_lock' => 'composer.lock',
        'composer_phar' => 'composer.phar',
        'phpunit_xml' => 'phpunit.xml',
        'phpunit_phar' => 'phpunit.phar',
        'phpunit_cache' => '.phpunit.result.cache',
        'trae_folder' => '.trae',
        'claude_folder' => '.claude',
        'playwright_folder' => '.playwright-mcp',
        'serena_folder' => '.serena',
        'admin_orders_view' => 'C:MAMPhtdocsCannaBuddy.shopadminordersview',
        'admin_users_edit' => 'C:MAMPhtdocsCannaBuddy.shopadminusersedit',
        'includes_seo' => 'C:MAMPhtdocsCannaBuddy.shopincludesseo',
        'newsletter_subscribe' => 'C:MAMPhtdocsCannaBuddy.shopnewslettersubscribe',
    ];

    foreach ($items as $item) {
        if (!isset($cleanupMap[$item])) {
            continue;
        }

        $target = $basePath . '/' . $cleanupMap[$item];

        if (!file_exists($target)) {
            $results[] = [
                'success' => true,
                'message' => "Skipped: {$cleanupMap[$item]} (not found)"
            ];
            continue;
        }

        if (is_dir($target)) {
            // Delete directory recursively
            $deleted = deleteDirectory($target);
            if ($deleted) {
                $results[] = [
                    'success' => true,
                    'message' => "Deleted folder: {$cleanupMap[$item]}"
                ];
            } else {
                $results[] = [
                    'success' => false,
                    'message' => "Failed to delete folder: {$cleanupMap[$item]}"
                ];
            }
        } else {
            // Delete file
            if (unlink($target)) {
                $results[] = [
                    'success' => true,
                    'message' => "Deleted file: {$cleanupMap[$item]}"
                ];
            } else {
                $results[] = [
                    'success' => false,
                    'message' => "Failed to delete file: {$cleanupMap[$item]}"
                ];
            }
        }
    }

    return $results;
}

/**
 * Recursively delete directory
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . '/' . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

/**
 * Get all cleanup items with their status
 */
function getCleanupItems() {
    $basePath = __DIR__;

    $items = [
        [
            'id' => 'mcp_json',
            'name' => '.mcp.json',
            'description' => 'Contains exposed API keys - MUST DELETE',
            'priority' => 'critical',
            'exists' => file_exists($basePath . '/.mcp.json')
        ],
        [
            'id' => 'cookies',
            'name' => 'cookies.txt',
            'description' => 'Contains session data - should delete',
            'priority' => 'high',
            'exists' => file_exists($basePath . '/cookies.txt')
        ],
        [
            'id' => 'test_delete',
            'name' => 'test_delete/',
            'description' => 'Test files folder - should delete',
            'priority' => 'medium',
            'exists' => is_dir($basePath . '/test_delete')
        ],
        [
            'id' => 'agent_md',
            'name' => 'agent.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/agent.md')
        ],
        [
            'id' => 'claude_md',
            'name' => 'CLAUDE.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/CLAUDE.md')
        ],
        [
            'id' => 'changelog',
            'name' => 'CHANGELOG.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/CHANGELOG.md')
        ],
        [
            'id' => 'readme',
            'name' => 'README.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/README.md')
        ],
        [
            'id' => 'deployment',
            'name' => 'DEPLOYMENT.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/DEPLOYMENT.md')
        ],
        [
            'id' => 'project_status',
            'name' => 'PROJECT_STATUS.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/PROJECT_STATUS.md')
        ],
        [
            'id' => 'discovery',
            'name' => 'discovery.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/discovery.md')
        ],
        [
            'id' => 'return_prompt',
            'name' => 'return_prompt.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/return_prompt.md')
        ],
        [
            'id' => 'checkout_plan',
            'name' => 'checkout_plan.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/checkout_plan.md')
        ],
        [
            'id' => 'codebase_map',
            'name' => 'codebase_map_simple.md',
            'description' => 'Documentation - optional to delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/codebase_map_simple.md')
        ],
        [
            'id' => 'composer_json',
            'name' => 'composer.json',
            'description' => 'Dev dependency file - should delete',
            'priority' => 'medium',
            'exists' => file_exists($basePath . '/composer.json')
        ],
        [
            'id' => 'composer_lock',
            'name' => 'composer.lock',
            'description' => 'Dev dependency file - should delete',
            'priority' => 'medium',
            'exists' => file_exists($basePath . '/composer.lock')
        ],
        [
            'id' => 'composer_phar',
            'name' => 'composer.phar',
            'description' => 'Dev tool - should delete',
            'priority' => 'medium',
            'exists' => file_exists($basePath . '/composer.phar')
        ],
        [
            'id' => 'phpunit_xml',
            'name' => 'phpunit.xml',
            'description' => 'Test config - should delete',
            'priority' => 'medium',
            'exists' => file_exists($basePath . '/phpunit.xml')
        ],
        [
            'id' => 'phpunit_phar',
            'name' => 'phpunit.phar',
            'description' => 'Test tool - should delete',
            'priority' => 'medium',
            'exists' => file_exists($basePath . '/phpunit.phar')
        ],
        [
            'id' => 'phpunit_cache',
            'name' => '.phpunit.result.cache',
            'description' => 'Test cache - should delete',
            'priority' => 'medium',
            'exists' => file_exists($basePath . '/.phpunit.result.cache')
        ],
        [
            'id' => 'trae_folder',
            'name' => '.trae/',
            'description' => 'Dev tool folder - should delete',
            'priority' => 'medium',
            'exists' => is_dir($basePath . '/.trae')
        ],
        [
            'id' => 'claude_folder',
            'name' => '.claude/',
            'description' => 'Dev tool folder - should delete',
            'priority' => 'medium',
            'exists' => is_dir($basePath . '/.claude')
        ],
        [
            'id' => 'playwright_folder',
            'name' => '.playwright-mcp/',
            'description' => 'Dev tool folder - should delete',
            'priority' => 'medium',
            'exists' => is_dir($basePath . '/.playwright-mcp')
        ],
        [
            'id' => 'serena_folder',
            'name' => '.serena/',
            'description' => 'Dev tool folder - should delete',
            'priority' => 'medium',
            'exists' => is_dir($basePath . '/.serena')
        ],
        [
            'id' => 'nul',
            'name' => 'nul',
            'description' => 'Empty file - should delete',
            'priority' => 'low',
            'exists' => file_exists($basePath . '/nul')
        ],
        [
            'id' => 'admin_orders_view',
            'name' => 'C:MAMPhtdocs... (accidental folder)',
            'description' => 'Accidental folder - should delete',
            'priority' => 'low',
            'exists' => is_dir($basePath . '/C:MAMPhtdocsCannaBuddy.shopadminordersview')
        ],
        [
            'id' => 'admin_users_edit',
            'name' => 'C:MAMPhtdocs... (accidental folder)',
            'description' => 'Accidental folder - should delete',
            'priority' => 'low',
            'exists' => is_dir($basePath . '/C:MAMPhtdocsCannaBuddy.shopadminusersedit')
        ],
        [
            'id' => 'includes_seo',
            'name' => 'C:MAMPhtdocs... (accidental folder)',
            'description' => 'Accidental folder - should delete',
            'priority' => 'low',
            'exists' => is_dir($basePath . '/C:MAMPhtdocsCannaBuddy.shopincludesseo')
        ],
        [
            'id' => 'newsletter_subscribe',
            'name' => 'C:MAMPhtdocs... (accidental folder)',
            'description' => 'Accidental folder - should delete',
            'priority' => 'low',
            'exists' => is_dir($basePath . '/C:MAMPhtdocsCannaBuddy.shopnewslettersubscribe')
        ],
    ];

    return $items;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CannaBuddy Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .priority-critical { border-left: 4px solid #ef4444; }
        .priority-high { border-left: 4px solid #f97316; }
        .priority-medium { border-left: 4px solid #eab308; }
        .priority-low { border-left: 4px solid #22c55e; }
        .exists-yes { background-color: #fef2f2; }
        .exists-no { opacity: 0.6; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-tools text-green-600 mr-3"></i>CannaBuddy Installer
                    </h1>
                    <p class="text-gray-600 mt-2">Configure your database & clean up unwanted files</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Current URL</div>
                    <div class="font-mono text-sm bg-gray-100 px-3 py-1 rounded"><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?></div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($messages)): ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                <?php foreach ($messages as $msg): ?>
                    <div class="text-green-800"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <?php foreach ($errors as $err): ?>
                    <div class="text-red-800"><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Warning Banner -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3 mt-1"></i>
                <div>
                    <h3 class="font-bold text-yellow-800">Remember to DELETE this file when done!</h3>
                    <p class="text-yellow-700 text-sm mt-1">After configuration, delete <code>installer.php</code> from your server for security.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Database Configuration -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-database text-blue-600 mr-2"></i>Database Configuration
                </h2>
                <p class="text-gray-600 text-sm mb-4">Current credentials from <code>includes/database.php</code></p>

                <form method="POST">
                    <input type="hidden" name="action" value="update_db">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                            <input type="text" name="db_host" value="<?php echo htmlspecialchars($dbConfig['host']); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                            <input type="text" name="db_name" value="<?php echo htmlspecialchars($dbConfig['db_name']); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Database Username</label>
                            <input type="text" name="db_user" value="<?php echo htmlspecialchars($dbConfig['username']); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Database Password</label>
                            <input type="password" name="db_pass" value="<?php echo htmlspecialchars($dbConfig['password']); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Save Database Configuration
                        </button>
                    </div>
                </form>
            </div>

            <!-- Debug Mode -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-bug text-orange-600 mr-2"></i>DEBUG_MODE
                </h2>
                <p class="text-gray-600 text-sm mb-4">Current setting from <code>config.php</code></p>

                <div class="mb-4">
                    <?php if ($debugMode): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-600 text-2xl mr-3"></i>
                                <div>
                                    <div class="font-bold text-red-800">DEBUG_MODE is ON</div>
                                    <div class="text-red-700 text-sm">This shows PHP errors to users - NOT for production!</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                                <div>
                                    <div class="font-bold text-green-800">DEBUG_MODE is OFF</div>
                                    <div class="text-green-700 text-sm">Safe for production - errors are logged only</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="toggle_debug">
                    <input type="hidden" name="debug_mode" value="<?php echo $debugMode ? '0' : '1'; ?>">

                    <button type="submit" class="w-full <?php echo $debugMode ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-600 hover:bg-orange-700'; ?> text-white py-3 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-toggle-<?php echo $debugMode ? 'on' : 'off'; ?> mr-2"></i>
                        <?php echo $debugMode ? 'Turn OFF Debug Mode (Recommended)' : 'Turn ON Debug Mode'; ?>
                    </button>
                </form>

                <div class="mt-4 bg-gray-50 rounded-lg p-3 text-xs text-gray-600">
                    <strong>Tip:</strong> Always keep DEBUG_MODE OFF in production. Errors will be logged to <code>/logs/php_errors.log</code> instead.
                </div>
            </div>
        </div>

        <!-- Cleanup Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-broom text-purple-600 mr-2"></i>Cleanup Unwanted Files
            </h2>
            <p class="text-gray-600 text-sm mb-6">Select files/folders to delete. Backups are created automatically.</p>

            <form method="POST">
                <input type="hidden" name="action" value="cleanup">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
                    <?php foreach (getCleanupItems() as $item): ?>
                        <label class="cursor-pointer block border rounded-lg p-3 hover:border-green-300 transition-colors priority-<?php echo $item['priority']; ?> <?php echo $item['exists'] ? 'exists-yes' : 'exists-no'; ?>">
                            <div class="flex items-start">
                                <input type="checkbox" name="cleanup_items[]" value="<?php echo $item['id']; ?>"
                                       class="mt-1 mr-3" <?php echo $item['priority'] === 'critical' ? 'checked' : ''; ?>>
                                <div class="flex-1">
                                    <div class="font-mono text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($item['description']); ?></div>
                                    <div class="mt-1">
                                        <?php if ($item['exists']): ?>
                                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">Exists</span>
                                        <?php else: ?>
                                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Not found</span>
                                        <?php endif; ?>
                                        <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded ml-1"><?php echo $item['priority']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="flex gap-3">
                    <button type="submit" onclick="return confirm('Are you sure you want to delete the selected files? Backups will be created.')"
                            class="flex-1 bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete Selected Files
                    </button>

                    <button type="button" onclick="selectAllCleanup()" class="px-6 bg-gray-600 text-white py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                        <i class="fas fa-check-double mr-2"></i>Select All
                    </button>

                    <button type="button" onclick="deselectAllCleanup()" class="px-6 bg-gray-400 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-500 transition-colors">
                        <i class="fas fa-times mr-2"></i>Deselect All
                    </button>
                </div>
            </form>
        </div>

        <!-- Test Database Connection -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-plug text-teal-600 mr-2"></i>Test Database Connection
            </h2>

            <?php
            try {
                require_once __DIR__ . '/includes/database.php';
                $testDb = new Database();
                $result = $testDb->testConnection();

                if (isset($result['error'])) {
                    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
                    echo '<div class="flex items-center text-red-800">';
                    echo '<i class="fas fa-times-circle text-2xl mr-3"></i>';
                    echo '<div><strong>Connection Failed</strong><br><small>' . htmlspecialchars($result['error']) . '</small></div>';
                    echo '</div></div>';
                } else {
                    echo '<div class="bg-green-50 border border-green-200 rounded-lg p-4">';
                    echo '<div class="flex items-center text-green-800">';
                    echo '<i class="fas fa-check-circle text-2xl mr-3"></i>';
                    echo '<div><strong>Connection Successful!</strong><br><small>Database is working correctly</small></div>';
                    echo '</div></div>';
                }
            } catch (Exception $e) {
                echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
                echo '<div class="flex items-center text-red-800">';
                echo '<i class="fas fa-times-circle text-2xl mr-3"></i>';
                echo '<div><strong>Connection Failed</strong><br><small>' . htmlspecialchars($e->getMessage()) . '</small></div>';
                echo '</div></div>';
            }
            ?>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-bolt text-yellow-600 mr-2"></i>Quick Actions
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <a href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>/" target="_blank"
                   class="block text-center bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-4 transition-colors">
                    <i class="fas fa-home text-blue-600 text-2xl mb-2"></i>
                    <div class="font-medium text-blue-900">Visit Home Page</div>
                </a>

                <a href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>/admin/" target="_blank"
                   class="block text-center bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 transition-colors">
                    <i class="fas fa-cog text-green-600 text-2xl mb-2"></i>
                    <div class="font-medium text-green-900">Admin Panel</div>
                </a>

                <a href="<?php echo $_SERVER['PHP_SELF']; ?>"
                   class="block text-center bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 transition-colors">
                    <i class="fas fa-sync text-purple-600 text-2xl mb-2"></i>
                    <div class="font-medium text-purple-900">Refresh Page</div>
                </a>

                <button onclick="if(confirm('Delete installer.php? This cannot be undone!')){window.location.href='?delete_installer=1'}"
                        class="block w-full text-center bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg p-4 transition-colors">
                    <i class="fas fa-trash text-red-600 text-2xl mb-2"></i>
                    <div class="font-medium text-red-900">Delete Installer</div>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8">
            <p>CannaBuddy Installer v1.0 | Remember to delete this file after configuration!</p>
        </div>
    </div>

    <script>
    function selectAllCleanup() {
        document.querySelectorAll('input[name="cleanup_items[]"]').forEach(cb => cb.checked = true);
    }

    function deselectAllCleanup() {
        document.querySelectorAll('input[name="cleanup_items[]"]').forEach(cb => cb.checked = false);
    }

    // Auto-delete installer if requested
    <?php if (isset($_GET['delete_installer'])): ?>
        if (confirm('Delete installer.php? This cannot be undone!')) {
            fetch(window.location.href, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=delete_installer'})
                .then(() => alert('Installer deleted! Redirecting to home page...'))
                .then(() => window.location.href = '<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>/');
        }
    <?php endif; ?>

    // Handle delete_installer action
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_installer'): ?>
        <?php
        if (unlink(__FILE__)) {
            echo 'alert("Installer deleted successfully!");';
            echo 'window.location.href = "' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/";';
        } else {
            echo 'alert("Failed to delete installer. Please delete manually via FTP/File Manager.");';
        }
        exit;
        ?>
    <?php endif; ?>
    </script>
</body>
</html>
