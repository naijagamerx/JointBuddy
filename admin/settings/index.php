<?php
require_once __DIR__ . '/../orders/view/invoice_registry.php';

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get current settings
$settings = [];
$settingsError = '';
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM settings ORDER BY setting_key ASC");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert to key-value array for easier access
        $settings_map = [];
        foreach ($settings as $setting) {
            $settings_map[$setting['setting_key']] = $setting['setting_value'];
        }
        $settings = $settings_map;

        // Get available currencies from currencies table
        $stmt = $db->query("SELECT code, name, symbol FROM currencies WHERE is_active = 1 ORDER BY is_default DESC, code ASC");
        $availableCurrencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get default currency code
        $stmt = $db->query("SELECT code FROM currencies WHERE is_default = 1 LIMIT 1");
        $defaultCurrency = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($defaultCurrency) {
            $settings['default_currency_code'] = $defaultCurrency['code'];
        }
    } catch (Exception $e) {
        $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error loading settings');
        $settingsError = $_SESSION['error'];
    }
} else {
    $errorMsg = "Database connection failed";
    error_log($errorMsg);
    $settingsError = $errorMsg;
    $_SESSION['error'] = $errorMsg;
}

// Handle form submission
if ($_POST && $adminAuth && $db) {
    try {
        // Handle regular settings updates
        $updates = $_POST;
        unset($updates['action']);

        // Handle logo upload
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $_FILES['store_logo'];

            // Validate file type (allow common image formats, including WEBP)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif', 'image/webp'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'];

            $extension = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new Exception('Invalid file type. Only JPG, PNG, SVG, GIF, and WEBP are allowed.');
            }

            $fileType = null;
            if (function_exists('mime_content_type')) {
                $fileType = mime_content_type($logoFile['tmp_name']);
            }
            if ($fileType && !in_array($fileType, $allowedTypes, true)) {
                throw new Exception('Invalid file type. Only JPG, PNG, SVG, GIF, and WEBP are allowed.');
            }

            // Validate file size (2MB max)
            if ($logoFile['size'] > 2 * 1024 * 1024) {
                throw new Exception('File size too large. Maximum size is 2MB.');
            }

            // Upload logo
            $uploadDir = __DIR__ . '/../../assets/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $logoFileName = 'logo_' . time() . '.' . pathinfo($logoFile['name'], PATHINFO_EXTENSION);
            $logoPath = $uploadDir . $logoFileName;

            if (move_uploaded_file($logoFile['tmp_name'], $logoPath)) {
                $logoUrl = assetUrl('images/' . $logoFileName);
                $updates['store_logo'] = $logoUrl;
            } else {
                throw new Exception('Failed to upload logo.');
            }
        }

        foreach ($updates as $key => $value) {
            // Check if setting exists
            $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);

            if ($stmt->rowCount() > 0) {
                // Update existing setting
                $stmt = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->execute([$key, $value]);
            }
        }

        // Update default currency in currencies table if provided
        if (!empty($updates['default_currency_code'])) {
            // First, unset all defaults
            $db->exec("UPDATE currencies SET is_default = 0");
            // Set new default
            $stmt = $db->prepare("UPDATE currencies SET is_default = 1 WHERE code = ?");
            $stmt->execute([$updates['default_currency_code']]);
        }

        $_SESSION['success'] = 'Settings updated successfully!';
        redirect('/admin/settings/');
    } catch (Exception $e) {
        $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error updating settings');
    }
}

// Default settings values (no hardcoded brand; admin should set store_name)
$defaults = [
    'store_name' => '',
    'store_email' => '',
    'store_phone' => '',
    'store_address' => '',
    'store_logo' => '',
    'logo_display_mode' => 'text',
    'logo_filter' => 'original',
    'currency_symbol' => 'R',
    'currency_code' => 'ZAR',
    'timezone' => 'Africa/Johannesburg',
    'maintenance_mode' => '0',
    'enable_registration' => '1',
    'default_order_status' => 'pending',
    'default_invoice_design' => 'default'
];

// Merge with actual settings
$settings = array_merge($defaults, $settings);

// Prepare invoice design options
$invoiceDesignOptions = [];
if (class_exists('InvoiceDesignRegistry')) {
    foreach (InvoiceDesignRegistry::getAll() as $k => $v) {
        $invoiceDesignOptions[$k] = $v['name'];
    }
}

// Generate general settings content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">General Settings</h1>
            <p class="text-gray-600 mt-1">Configure basic store information and preferences</p>
        </div>
    </div>

    <!-- Quick Stats / Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <a href="' . adminUrl('/settings') . '" class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors bg-white">
            <div>
                <p class="text-sm font-medium text-gray-900">General Settings</p>
                <p class="text-xs text-gray-500">Store name, email, and localization</p>
            </div>
            <span class="text-gray-400">
                <i class="fas fa-chevron-right"></i>
            </span>
        </a>
        <a href="' . adminUrl('/settings/email') . '" class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors bg-white">
            <div>
                <p class="text-sm font-medium text-gray-900">Email Settings</p>
                <p class="text-xs text-gray-500">Configure SMTP and outgoing options</p>
            </div>
            <span class="text-gray-400">
                <i class="fas fa-chevron-right"></i>
            </span>
        </a>
        <a href="' . adminUrl('/settings/notifications') . '" class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors bg-white">
            <div>
                <p class="text-sm font-medium text-gray-900">Notifications</p>
                <p class="text-xs text-gray-500">Control order and system emails</p>
            </div>
            <span class="text-gray-400">
                <i class="fas fa-chevron-right"></i>
            </span>
        </a>
        <a href="' . adminUrl('/settings/appearance') . '" class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors bg-white">
            <div>
                <p class="text-sm font-medium text-gray-900">Appearance</p>
                <p class="text-xs text-gray-500">Logo, favicon, colors & theme</p>
            </div>
            <span class="text-gray-400">
                <i class="fas fa-chevron-right"></i>
            </span>
        </a>
    </div>

    <!-- Alert Messages -->';

if (isset($_SESSION['success'])) {
    $content .= adminAlert('success', $_SESSION['success']);
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $content .= adminAlert('error', $_SESSION['error']);
    unset($_SESSION['error']);
}

$content .= '
<!-- Settings Form -->
<form method="POST" class="space-y-8" enctype="multipart/form-data">
    ' . csrf_field() . '
    <!-- Store Information -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Store Information</h2>
            <p class="text-sm text-gray-600">Basic information about your store</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('Store Name', 'store_name', $settings['store_name'], 'text', true, 'Enter your store name') . '
                ' . adminFormInput('Store Email', 'store_email', $settings['store_email'], 'email', true, 'contact@yourstore.com') . '
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('Store Phone', 'store_phone', $settings['store_phone'], 'tel', false, '+27 12 345 6789') . '
                ' . adminFormSelect('Timezone', 'timezone', $settings['timezone'], [
                    'Africa/Johannesburg' => 'South Africa (Johannesburg)',
                    'Africa/Cape_Town' => 'South Africa (Cape Town)',
                    'UTC' => 'UTC',
                    'Europe/London' => 'Europe (London)',
                    'America/New_York' => 'America (New York)'
                ], true) . '
            </div>
            ' . adminFormTextarea('Store Address', 'store_address', $settings['store_address'], 3, false, 'Enter your complete store address') . '

            <!-- Logo Upload Section -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Store Logo</h3>

                <!-- Logo Display Mode -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Mode</label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="logo_display_mode" value="text" ' . (($settings['logo_display_mode'] ?? 'text') === 'text' ? 'checked' : '') . ' class="mr-2">
                            Text Only
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="logo_display_mode" value="logo" ' . (($settings['logo_display_mode'] ?? 'text') === 'logo' ? 'checked' : '') . ' class="mr-2">
                            Logo Only
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="logo_display_mode" value="both" ' . (($settings['logo_display_mode'] ?? 'text') === 'both' ? 'checked' : '') . ' class="mr-2">
                            Both
                        </label>
                    </div>
                </div>

                <!-- Current Logo Preview -->
                ' . (!empty($settings['store_logo']) ? '
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Logo</label>
                    <div class="flex items-center space-x-4">
                        <img src="' . htmlspecialchars($settings['store_logo']) . '" alt="Current Logo" class="h-16 w-auto">
                        <div class="flex space-x-2">
                            <label class="flex items-center">
                                <input type="radio" name="logo_filter" value="original" ' . (($settings['logo_filter'] ?? 'original') === 'original' ? 'checked' : '') . ' class="mr-2">
                                Original
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="logo_filter" value="white" ' . (($settings['logo_filter'] ?? 'original') === 'white' ? 'checked' : '') . ' class="mr-2">
                                White (filter)
                            </label>
                        </div>
                    </div>
                </div>
                ' : '') . '

                <!-- Upload New Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Logo</label>
                    <input type="file" name="store_logo" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Recommended: PNG, SVG, or WEBP with transparent background, max 2MB</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Currency & Localization -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Currency & Localization</h2>
            <p class="text-sm text-gray-600">Configure currency and regional settings</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            ' . adminFormSelect('Default Currency', 'default_currency_code', $settings['default_currency_code'] ?? 'ZAR', array_column($availableCurrencies, 'name', 'code'), false, '', '', true) . '
        </div>
    </div>

    <!-- Store Preferences -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Store Preferences</h2>
            <p class="text-sm text-gray-600">General store behavior and options</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            ' . adminFormSelect('Default Order Status', 'default_order_status', $settings['default_order_status'], [
                'pending' => 'Pending',
                'processing' => 'Processing', 
                'confirmed' => 'Confirmed',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered'
            ], true) . '
            
            ' . adminFormSelect('Default Invoice Design', 'default_invoice_design', $settings['default_invoice_design'], $invoiceDesignOptions, true) . '
            
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="maintenance_mode" value="1" ' . ($settings['maintenance_mode'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Maintenance Mode</strong><br>
                        <span class="text-gray-500">Enable to temporarily disable the store for visitors</span>
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="enable_registration" value="1" ' . ($settings['enable_registration'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Customer Registration</strong><br>
                        <span class="text-gray-500">Allow new customers to register accounts</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition-colors">
            <i class="fas fa-save mr-2"></i>Save Settings
        </button>
    </div>
</form>
</div>';

echo adminSidebarWrapper('General Settings', $content, 'settings');
