<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Handle Test Email AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_smtp') {
    CsrfMiddleware::validate();
    header('Content-Type: application/json');
    $to = $_POST['test_email'] ?? '';
    
    if (empty($to)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    
    require_once __DIR__ . '/../../includes/email_service.php';
    $emailService = new EmailService($db);
    
    if ($emailService->sendTestEmail($to)) {
        echo json_encode(['success' => true, 'message' => 'Test email sent successfully! Check your inbox.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send test email: ' . $emailService->getError()]);
    }
    exit;
}

// Get current email settings
$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM settings WHERE setting_key LIKE 'email_%' ORDER BY setting_key ASC");
        $email_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to key-value array
        $settings_map = [];
        foreach ($email_settings as $setting) {
            $settings_map[$setting['setting_key']] = $setting['setting_value'];
        }
        $settings = $settings_map;
    } catch (Exception $e) {
        error_log("Error getting email settings: " . $e->getMessage());
    }
}

// Default settings values
$defaults = [
    'email_method' => 'smtp',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'from_email' => '',
    'from_name' => '', // Will be loaded from settings
    'new_order_notifications' => '1',
    'customer_registration_notifications' => '1',
    'order_status_notifications' => '1'
];

// Merge with actual settings
$settings = array_merge($defaults, $settings);

// Handle form submission
if ($_POST && $adminAuth && $db) {
    try {
        $updates = $_POST;
        unset($updates['action']);
        
        // Define all possible settings for this page to handle unchecked checkboxes
        $all_keys = [
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
            'from_email', 'from_name', 'new_order_notifications', 
            'customer_registration_notifications', 'order_status_notifications'
        ];
        
        foreach ($all_keys as $key) {
            $value = isset($updates[$key]) ? $updates[$key] : '0';
            
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

        $_SESSION['success'] = 'Email settings updated successfully!';
        redirect('/admin/settings/email');
    } catch (Exception $e) {
        error_log("Error updating email settings: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating settings. Please try again.';
    }
}

// Generate email settings content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Email Settings</h1>
            <p class="text-gray-600 mt-1">Configure email notifications and SMTP settings</p>
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
        <a href="' . adminUrl('/settings/email') . '" class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg bg-blue-50 border-blue-200 transition-colors">
            <div>
                <p class="text-sm font-medium text-blue-900">Email Settings</p>
                <p class="text-xs text-blue-700">Configure SMTP and outgoing options</p>
            </div>
            <span class="text-blue-400">
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
<!-- Email Settings Form -->
<form method="POST" class="space-y-8">
    ' . csrf_field() . '
    <!-- SMTP Configuration -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">SMTP Configuration</h2>
            <p class="text-sm text-gray-600">Configure SMTP server settings for outgoing emails</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('SMTP Host', 'smtp_host', $settings['smtp_host'], 'text', true, 'e.g., smtp.gmail.com, smtp.mailtrap.io') . '
                ' . adminFormInput('SMTP Port', 'smtp_port', $settings['smtp_port'], 'number', true, '587', ['min' => '1', 'max' => '65535']) . '
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('SMTP Username', 'smtp_username', $settings['smtp_username'], 'text', true, 'your-email@gmail.com') . '
                ' . adminFormInput('SMTP Password', 'smtp_password', $settings['smtp_password'], 'password', true, 'Your email password or app password') . '
            </div>
            ' . adminFormSelect('Encryption', 'smtp_encryption', $settings['smtp_encryption'], [
                'tls' => 'TLS (Recommended)',
                'ssl' => 'SSL',
                'none' => 'None'
            ], true) . '
        </div>
    </div>

    <!-- Email From Settings -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">From Information</h2>
            <p class="text-sm text-gray-600">Configure sender information for outgoing emails</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('From Email', 'from_email', $settings['from_email'], 'email', true, 'noreply@yourstore.com') . '
                ' . adminFormInput('From Name', 'from_name', $settings['from_name'], 'text', true, 'Your Store Name') . '
            </div>
        </div>
    </div>

    <!-- Notification Preferences -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Notification Preferences</h2>
            <p class="text-sm text-gray-600">Choose which events trigger email notifications</p>
        </div>
        <div class="px-6 py-6 space-y-6">
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="new_order_notifications" value="1" ' . ($settings['new_order_notifications'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>New Order Notifications</strong><br>
                        <span class="text-gray-500">Send email when a new order is placed</span>
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="customer_registration_notifications" value="1" ' . ($settings['customer_registration_notifications'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Customer Registration Notifications</strong><br>
                        <span class="text-gray-500">Send email when a new customer registers</span>
                    </label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="order_status_notifications" value="1" ' . ($settings['order_status_notifications'] == '1' ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Order Status Notifications</strong><br>
                        <span class="text-gray-500">Send email when order status changes</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
            <i class="fas fa-save mr-2"></i>Save Email Settings
        </button>
    </div>
</form>

<!-- Test Email Section -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Test Email Configuration</h2>
        <p class="text-sm text-gray-600">Send a test email to verify your SMTP settings</p>
    </div>
    <div class="px-6 py-6">
        <form onsubmit="testEmail(event)" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                ' . adminFormInput('Test Email Address', 'test_email', '', 'email', true, 'recipient@example.com') . '
            </div>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>Send Test Email
            </button>
        </form>
        <div id="testEmailResult" class="mt-4 hidden"></div>
    </div>
</div>
</div>

<script>
function testEmail(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append("action", "test_smtp");
    const testEmail = formData.get("test_email");
    const resultDiv = document.getElementById("testEmailResult");
    
    // Show loading
    resultDiv.innerHTML = \'<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Sending test email...</div>\';
    resultDiv.classList.remove("hidden");
    
    // Call API
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = \'<div class="text-green-600 bg-green-50 p-3 rounded-lg border border-green-200"><i class="fas fa-check-circle mr-2"></i>\' + data.message + \'</div>\';
        } else {
            resultDiv.innerHTML = \'<div class="text-red-600 bg-red-50 p-3 rounded-lg border border-red-200"><i class="fas fa-exclamation-circle mr-2"></i>\' + data.message + \'</div>\';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = \'<div class="text-red-600 bg-red-50 p-3 rounded-lg border border-red-200"><i class="fas fa-exclamation-circle mr-2"></i>Error: \' + error.message + \'</div>\';
    });
}
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Email Settings', $content, 'settings');