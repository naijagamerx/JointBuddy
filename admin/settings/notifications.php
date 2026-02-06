<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get current notification settings
$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'notification_%' ORDER BY setting_key ASC");
        $notification_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to key-value array
        $settings_map = [];
        foreach ($notification_settings as $setting) {
            $settings_map[$setting['setting_key']] = $setting['setting_value'];
        }
        $settings = $settings_map;
    } catch (Exception $e) {
        error_log("Error getting notification settings: " . $e->getMessage());
    }
}

// Default settings values
$defaults = [
    'email_notifications_enabled' => '1',
    'sms_notifications_enabled' => '0',
    'new_order_email' => '1',
    'new_order_sms' => '0',
    'low_stock_email' => '1',
    'low_stock_sms' => '0',
    'customer_register_email' => '1',
    'customer_register_sms' => '0',
    'order_status_email' => '1',
    'order_status_sms' => '0',
    'payment_received_email' => '1',
    'payment_received_sms' => '0',
    'admin_notifications_email' => '1',
    'admin_notifications_sms' => '0',
    'notification_email' => '',
    'notification_phone' => '',
    'low_stock_threshold' => '10'
];

// Merge with actual settings
$settings = array_merge($defaults, $settings);

// Handle form submission
if ($_POST && $adminAuth && $db) {
    try {
        $updates = $_POST;
        unset($updates['action']);
        
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

        $_SESSION['success'] = 'Notification settings updated successfully!';
        redirect('/admin/settings/notifications');
    } catch (Exception $e) {
        error_log("Error updating notification settings: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating settings. Please try again.';
    }
}

// Generate notification settings content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Notification Settings</h1>
            <p class="text-gray-600 mt-1">Configure when and how you receive notifications</p>
        </div>
    </div>

    <!-- Quick Stats / Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
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
        <a href="' . adminUrl('/settings/notifications') . '" class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg bg-yellow-50 border-yellow-200 transition-colors">
            <div>
                <p class="text-sm font-medium text-yellow-900">Notifications Settings</p>
                <p class="text-xs text-yellow-700">Control order and system emails</p>
            </div>
            <span class="text-yellow-400">
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
<!-- Notification Settings Form -->
<form method="POST" class="space-y-8">
    ' . csrf_field() . '
    <!-- Notification Channels -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Notification Channels</h2>
            <p class="text-sm text-gray-600">Choose which channels to enable for notifications</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center">
                    <input type="checkbox" name="email_notifications_enabled" value="1" ' . ($settings['email_notifications_enabled'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong><i class="fas fa-envelope mr-1 text-yellow-600"></i>Email Notifications</strong><br>
                        <span class="text-gray-500">Receive notifications via email</span>
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="sms_notifications_enabled" value="1" ' . ($settings['sms_notifications_enabled'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong><i class="fas fa-sms mr-1 text-yellow-600"></i>SMS Notifications</strong><br>
                        <span class="text-gray-500">Receive notifications via SMS</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Contact Information</h2>
            <p class="text-sm text-gray-600">Where should notifications be sent?</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('Notification Email', 'notification_email', $settings['notification_email'], 'email', false, 'admin@yourstore.com') . '
                ' . adminFormInput('Notification Phone', 'notification_phone', $settings['notification_phone'], 'tel', false, '+27 12 345 6789') . '
            </div>
        </div>
    </div>

    <!-- Order Notifications -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Order Notifications</h2>
            <p class="text-sm text-gray-600">Configure notifications related to orders</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Email</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="new_order_email" value="1" ' . ($settings['new_order_email'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">New orders placed</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="order_status_email" value="1" ' . ($settings['order_status_email'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Order status changes</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="payment_received_email" value="1" ' . ($settings['payment_received_email'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Payment received</label>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">SMS</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="new_order_sms" value="1" ' . ($settings['new_order_sms'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">New orders placed</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="order_status_sms" value="1" ' . ($settings['order_status_sms'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Order status changes</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="payment_received_sms" value="1" ' . ($settings['payment_received_sms'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">Payment received</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Notifications -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Inventory Notifications</h2>
            <p class="text-sm text-gray-600">Configure notifications for product inventory</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('Low Stock Threshold', 'low_stock_threshold', $settings['low_stock_threshold'], 'number', true, '10', ['min' => '0']) . '
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Email</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="low_stock_email" value="1" ' . ($settings['low_stock_email'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <label class="ml-2 text-sm text-gray-700">Low stock alerts</label>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">SMS</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="low_stock_sms" value="1" ' . ($settings['low_stock_sms'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <label class="ml-2 text-sm text-gray-700">Low stock alerts</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Notifications -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Customer Notifications</h2>
            <p class="text-sm text-gray-600">Configure notifications for customer activities</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Email</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="customer_register_email" value="1" ' . ($settings['customer_register_email'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-700">New customer registrations</label>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">SMS</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="customer_register_sms" value="1" ' . ($settings['customer_register_sms'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-700">New customer registrations</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Notifications -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Admin Notifications</h2>
            <p class="text-sm text-gray-600">System and security notifications</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Email</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="admin_notifications_email" value="1" ' . ($settings['admin_notifications_email'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <label class="ml-2 text-sm text-gray-700">System alerts and errors</label>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">SMS</h4>
                    <div class="space-y-3 pl-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="admin_notifications_sms" value="1" ' . ($settings['admin_notifications_sms'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <label class="ml-2 text-sm text-gray-700">Critical system alerts only</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="bg-yellow-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-yellow-700 transition-colors">
            <i class="fas fa-save mr-2"></i>Save Notification Settings
        </button>
    </div>
</form>
</div>';

// Render the page with sidebar
echo adminSidebarWrapper('Notifications', $content, 'settings');