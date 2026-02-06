<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get current appearance settings
$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'theme_%' OR setting_key LIKE 'appearance_%' ORDER BY setting_key ASC");
        $appearance_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to key-value array
        $settings_map = [];
        foreach ($appearance_settings as $setting) {
            $settings_map[$setting['setting_key']] = $setting['setting_value'];
        }
        $settings = $settings_map;
    } catch (Exception $e) {
        error_log("Error getting appearance settings: " . $e->getMessage());
    }
}

// Default settings values
$defaults = [
    'theme_color' => '#10B981',
    'primary_color' => '#10B981',
    'secondary_color' => '#6B7280',
    'accent_color' => '#F59E0B',
    'header_color' => '#1F2937',
    'footer_color' => '#374151',
    'font_family' => 'system-ui',
    'font_size' => 'base',
    'logo_url' => '',
    'favicon_url' => '',
    'site_description' => '',
    'footer_text' => '',
    'enable_dark_mode' => '0',
    'border_radius' => 'md'
];

// Merge with actual settings
$settings = array_merge($defaults, $settings);

// Handle form submission
if ($_POST && $adminAuth && $db) {
    try {
        $updates = $_POST;
        unset($updates['action']);
        
        foreach ($updates as $key => $value) {
            unset($updates['favicon_file']); // Handled separately
            
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

        // Handle Favicon file upload if provided
        if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
            $favFile = $_FILES['favicon_file'];
            $allowedExtensions = ['ico', 'png', 'svg', 'jpg', 'jpeg', 'webp'];
            $ext = strtolower(pathinfo($favFile['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                throw new Exception('Invalid favicon file type. Allowed: ICO, PNG, SVG, JPG, WEBP');
            }

            if ($favFile['size'] > 1 * 1024 * 1024) {
                throw new Exception('Favicon file size too large. Max 1MB.');
            }

            $uploadDir = __DIR__ . '/../../assets/images/branding/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'favicon_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($favFile['tmp_name'], $targetPath)) {
                $favUrl = assetUrl('images/branding/' . $fileName);
                
                // Update or Insert favicon_url setting
                $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = 'favicon_url'");
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $stmt = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'favicon_url'");
                    $stmt->execute([$favUrl]);
                } else {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('favicon_url', ?, NOW(), NOW())");
                    $stmt->execute([$favUrl]);
                }
            }
        }

        $_SESSION['success'] = 'Appearance settings updated successfully!';
        redirect('/admin/settings/appearance');
    } catch (Exception $e) {
        error_log("Error updating appearance settings: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating settings. Please try again.';
    }
}

// Generate appearance settings content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Appearance Settings</h1>
            <p class="text-gray-600 mt-1">Customize the look and feel of your store</p>
        </div>
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
<!-- Appearance Settings Form -->
<form method="POST" class="space-y-8" enctype="multipart/form-data">
    ' . csrf_field() . '
    <!-- Color Scheme -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Color Scheme</h2>
            <p class="text-sm text-gray-600">Customize the colors used throughout your store</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" name="primary_color" value="' . htmlspecialchars($settings['primary_color']) . '" class="w-12 h-12 rounded-lg border border-gray-300">
                        <input type="text" name="primary_color_text" value="' . htmlspecialchars($settings['primary_color']) . '" class="flex-1 px-3 py-2 border border-gray-300 rounded-md" placeholder="#10B981">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" name="secondary_color" value="' . htmlspecialchars($settings['secondary_color']) . '" class="w-12 h-12 rounded-lg border border-gray-300">
                        <input type="text" name="secondary_color_text" value="' . htmlspecialchars($settings['secondary_color']) . '" class="flex-1 px-3 py-2 border border-gray-300 rounded-md" placeholder="#6B7280">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Accent Color</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" name="accent_color" value="' . htmlspecialchars($settings['accent_color']) . '" class="w-12 h-12 rounded-lg border border-gray-300">
                        <input type="text" name="accent_color_text" value="' . htmlspecialchars($settings['accent_color']) . '" class="flex-1 px-3 py-2 border border-gray-300 rounded-md" placeholder="#F59E0B">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Typography -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Typography</h2>
            <p class="text-sm text-gray-600">Configure fonts and text appearance</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormSelect('Font Family', 'font_family', $settings['font_family'], [
                    'system-ui' => 'System UI (Default)',
                    'Arial' => 'Arial',
                    'Helvetica' => 'Helvetica',
                    'Times New Roman' => 'Times New Roman',
                    'Georgia' => 'Georgia',
                    'Courier New' => 'Courier New',
                    'monospace' => 'Monospace'
                ], true) . '
                
                ' . adminFormSelect('Base Font Size', 'font_size', $settings['font_size'], [
                    'sm' => 'Small',
                    'base' => 'Medium (Default)',
                    'lg' => 'Large',
                    'xl' => 'Extra Large'
                ], true) . '
                
                ' . adminFormSelect('Border Radius', 'border_radius', $settings['border_radius'], [
                    'none' => 'None',
                    'sm' => 'Small',
                    'md' => 'Medium (Default)',
                    'lg' => 'Large',
                    'xl' => 'Extra Large',
                    'full' => 'Full (Rounded)'
                ], true) . '
            </div>
        </div>
    </div>

    <!-- Branding -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Branding</h2>
            <p class="text-sm text-gray-600">Upload logos and configure brand elements</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            ' . adminFormInput('Logo URL', 'logo_url', $settings['logo_url'], 'url', false, 'https://example.com/logo.png') . '
            
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>

                <!-- Live Browser Tab Preview -->
                <div class="mb-4 p-3 bg-gray-100 rounded-lg border">
                    <p class="text-xs text-gray-500 mb-2">Live Browser Tab Preview:</p>
                    <div class="flex items-center space-x-2 bg-white rounded border px-3 py-2">
                        ' . (!empty($settings['favicon_url']) ? '
                        <img src="' . htmlspecialchars($settings['favicon_url']) . '" alt="Favicon" class="w-4 h-4" onerror="this.style.display=\'none\'">
                        ' : '<span class="w-4 h-4 bg-gray-300 rounded"></span>') . '
                        <span class="text-sm text-gray-700">' . htmlspecialchars($settings['site_name'] ?? 'Your Store') . ' - Appearance Settings</span>
                    </div>
                </div>

                ' . (!empty($settings['favicon_url']) ? '
                <div class="mb-4 flex items-center space-x-4">
                    <img src="' . htmlspecialchars($settings['favicon_url']) . '" alt="Current Favicon" class="h-8 w-8 object-contain bg-gray-50 border rounded p-1">
                    <span class="text-xs text-gray-500">Current: ' . htmlspecialchars($settings['favicon_url']) . '</span>
                </div>
                ' : '<div class="mb-4 text-xs text-gray-400">No favicon set</div>') . '

                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <input type="file" name="favicon_file" accept=".ico,.png,.svg,.jpg,.jpeg,.webp" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <p class="text-xs text-gray-400 mt-1">Recommended: 32x32 SVG or PNG. Max 1MB.</p>
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    <span class="block mb-1">Or use a remote URL:</span>
                    ' . adminFormInput('', 'favicon_url', $settings['favicon_url'], 'url', false, 'https://example.com/favicon.ico') . '
                </div>
            </div>

            ' . adminFormTextarea('Site Description', 'site_description', $settings['site_description'], 3, false, 'Brief description of your store') . '
        </div>
    </div>

    <!-- Layout Options -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Layout Options</h2>
            <p class="text-sm text-gray-600">Configure layout and design preferences</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="enable_dark_mode" value="1" ' . ($settings['enable_dark_mode'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Enable Dark Mode</strong><br>
                        <span class="text-gray-500">Allow users to switch to dark mode</span>
                    </label>
                </div>
            </div>
            
            ' . adminFormTextarea('Footer Text', 'footer_text', $settings['footer_text'], 3, false, 'Custom footer text or copyright information') . '
        </div>
    </div>

    <!-- Preview Section -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Theme Preview</h2>
            <p class="text-sm text-gray-600">Preview of your current color scheme</p>
        </div>
        <div class="px-6 py-6">
            <div class="p-6 rounded-lg" style="background-color: ' . htmlspecialchars($settings['primary_color']) . '20; border: 2px solid ' . htmlspecialchars($settings['primary_color']) . ';">
                <h3 class="text-lg font-semibold mb-4" style="color: ' . htmlspecialchars($settings['primary_color']) . ';">Sample Button</h3>
                <div class="space-y-3">
                    <button class="px-6 py-3 text-white rounded-lg font-medium transition-colors" style="background-color: ' . htmlspecialchars($settings['primary_color']) . ';">
                        Primary Button
                    </button>
                    <button class="px-6 py-3 text-white rounded-lg font-medium transition-colors" style="background-color: ' . htmlspecialchars($settings['secondary_color']) . ';">
                        Secondary Button
                    </button>
                    <button class="px-6 py-3 text-white rounded-lg font-medium transition-colors" style="background-color: ' . htmlspecialchars($settings['accent_color']) . ';">
                        Accent Button
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="bg-pink-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-pink-700 transition-colors">
            <i class="fas fa-save mr-2"></i>Save Appearance Settings
        </button>
    </div>
</form>
</div>

<script>
// Sync color picker with text input
document.querySelectorAll("input[type=\'color\']").forEach(colorInput => {
    const textInput = colorInput.parentNode.querySelector("input[type=\'text\']");
    
    colorInput.addEventListener("change", function() {
        textInput.value = this.value;
    });
    
    textInput.addEventListener("input", function() {
        if (/^#[0-9A-F]{6}$/i.test(this.value)) {
            colorInput.value = this.value;
        }
    });
});
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Appearance', $content, 'settings');