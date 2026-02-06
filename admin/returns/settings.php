<?php
/**
 * Admin Returns Settings - CannaBuddy
 * Configure return policy and settings
 */
require_once __DIR__ . '/../../includes/admin_error_catcher.php';

// Initialize error handling
setupAdminErrorHandling();

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    $settingsToUpdate = [
        'return_eligibility_days' => [
            'value' => isset($_POST['return_eligibility_days']) ? (int)$_POST['return_eligibility_days'] : 14,
            'description' => 'Days allowed for standard returns'
        ],
        'damaged_delivery_window' => [
            'value' => isset($_POST['damaged_delivery_window']) ? (int)$_POST['damaged_delivery_window'] : 30,
            'description' => 'Days allowed for damaged delivery returns'
        ],
        'allow_drop_off' => [
            'value' => isset($_POST['allow_drop_off']) ? '1' : '0',
            'description' => 'Allow in-store drop-off returns'
        ],
        'allow_courier_service' => [
            'value' => isset($_POST['allow_courier_service']) ? '1' : '0',
            'description' => 'Allow courier collection returns'
        ],
        'return_policy_text' => [
            'value' => isset($_POST['return_policy_text']) ? $_POST['return_policy_text'] : '',
            'description' => 'Return policy text displayed to customers'
        ]
    ];

    try {
        foreach ($settingsToUpdate as $key => $setting) {
            // Check if setting exists
            $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = ? AND category = 'returns'");
            $stmt->execute([$key]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$setting['value'], $existing['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, description, category, created_at, updated_at) VALUES (?, ?, ?, 'returns', NOW(), NOW())");
                $stmt->execute([$key, $setting['value'], $setting['description']]);
            }
        }
        $success = 'Settings saved successfully.';
    } catch (Exception $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
        error_log("Settings update error: " . $e->getMessage());
    }
}

// Fetch current settings
$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE category = 'returns'");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Error fetching settings: " . $e->getMessage());
    }
}

// Default values
$returnEligibilityDays = $settings['return_eligibility_days'] ?? 14;
$damagedDeliveryWindow = $settings['damaged_delivery_window'] ?? 30;
$allowDropOff = isset($settings['allow_drop_off']) && $settings['allow_drop_off'] == '1';
$allowCourierService = isset($settings['allow_courier_service']) && $settings['allow_courier_service'] == '1';
$returnPolicyText = $settings['return_policy_text'] ?? '';

$pageTitle = 'Returns Settings';
$currentPage = 'returns';

// Build page content
$content = '';

if (function_exists('renderAdminErrors')) {
    $content .= renderAdminErrors();
}

if (!empty($error)) {
    $content .= adminAlert($error, 'error');
}

if (!empty($success)) {
    $content .= adminAlert($success, 'success');
}

$content .= '<div class="mb-4">
    <a href="' . adminUrl('/returns/') . '" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6">
        <i class="fas fa-arrow-left mr-2"></i> Back to Returns
    </a>
</div>';

$content .= '<form method="POST">
    ' . csrf_field() . '
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Eligibility Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Eligibility Windows</h3>
                <p class="text-sm text-gray-500 mt-1">Configure how customers can request returns</p>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="return_eligibility_days" class="block text-sm font-medium text-gray-700 mb-1">
                        Standard Return Window (days)
                    </label>
                    <input type="number" name="return_eligibility_days" id="return_eligibility_days"
                           value="' . htmlspecialchars($returnEligibilityDays) . '" min="1" max="365"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400">
                    <p class="text-sm text-gray-500 mt-1">Number of days after delivery to request a return</p>
                </div>

                <div>
                    <label for="damaged_delivery_window" class="block text-sm font-medium text-gray-700 mb-1">
                        Damaged Delivery Window (days)
                    </label>
                    <input type="number" name="damaged_delivery_window" id="damaged_delivery_window"
                           value="' . htmlspecialchars($damagedDeliveryWindow) . '" min="1" max="365"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400">
                    <p class="text-sm text-gray-500 mt-1">Extended window for reporting damaged deliveries</p>
                </div>
            </div>
        </div>

        <!-- Return Methods -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Return Methods</h3>
                <p class="text-sm text-gray-500 mt-1">Enable or disable return method options</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Drop-off at Store</h4>
                        <p class="text-sm text-gray-500">Allow customers to return items in-person</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="allow_drop_off" value="1"
                               ' . ($allowDropOff ? 'checked' : '') . '
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Courier Collection</h4>
                        <p class="text-sm text-gray-500">Arrange for pickup at customer\'s address</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="allow_courier_service" value="1"
                               ' . ($allowCourierService ? 'checked' : '') . '
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>';

if (!$allowDropOff && !$allowCourierService) {
    $content .= '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
            <p class="text-sm text-yellow-700">Warning: No return methods are enabled. Customers will not be able to submit returns.</p>
        </div>
    </div>';
}

$content .= '    </div>
        </div>

        <!-- Return Policy -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Return Policy</h3>
                <p class="text-sm text-gray-500 mt-1">This text will be displayed to customers when requesting returns</p>
            </div>
            <div class="p-6">
                <textarea name="return_policy_text" id="return_policy_text" rows="8"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400"
                          placeholder="Enter your return policy here...">' . htmlspecialchars($returnPolicyText) . '</textarea>

                <!-- Live Preview -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Customer Preview</h4>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">';
if (empty($returnPolicyText)) {
    $content .= '<p class="text-gray-400 italic">No policy text configured. Add text above to show customers.</p>';
} else {
    $content .= '<div class="prose prose-sm max-w-none text-gray-700">
                        ' . nl2br(htmlspecialchars($returnPolicyText)) . '
                    </div>';
}
$content .= '    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="mt-6 flex justify-end">
        <button type="submit"
                class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
            <i class="fas fa-save mr-2"></i>Save Settings
        </button>
    </div>
</form>';

// Render the page using adminSidebarWrapper
echo adminSidebarWrapper($pageTitle, $content, $currentPage);
