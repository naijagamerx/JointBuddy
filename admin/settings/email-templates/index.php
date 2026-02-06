<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/url_helper.php';
require_once __DIR__ . '/../../../admin_sidebar_components.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
} catch (Exception $e) {
    $db = null;
    $adminAuth = null;
}

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    redirect('/admin/login/');
}

$message = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle form submission (Update template)
if ($_POST && isset($_POST['template_id'])) {
    try {
        $id = (int)$_POST['template_id'];
        $subject = trim($_POST['subject']);
        $content = $_POST['html_content'];
        $active = isset($_POST['active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE email_templates SET subject = ?, html_content = ?, active = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$subject, $content, $active, $id])) {
            $_SESSION['success'] = "Template updated successfully!";
        } else {
            throw new Exception("Failed to update template.");
        }
        redirect('/admin/settings/email-templates/');
    } catch (Exception $e) {
        $error = AppError::handleDatabaseError($e, 'Error updating template');
    }
}

// Fetch all templates
$templates = [];
if ($db) {
    $stmt = $db->query("SELECT * FROM email_templates ORDER BY name ASC");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$navLinks = '
<div class="flex flex-wrap gap-4 mb-8 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
    <a href="' . adminUrl('/settings/email/') . '" class="flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
        <i class="fas fa-envelope mr-2"></i>Email SMTP Settings
    </a>
    <a href="' . adminUrl('/settings/email-templates/') . '" class="flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-green-50 text-green-700 border border-green-100">
        <i class="fas fa-file-alt mr-2"></i>Email Templates
    </a>
    <a href="' . adminUrl('/settings/notifications/') . '" class="flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
        <i class="fas fa-bell mr-2"></i>Notification Config
    </a>
</div>';

$templateCards = '';
foreach ($templates as $template) {
    $statusBadge = $template['active'] ? 
        '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">Active</span>' : 
        '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full font-medium">Inactive</span>';

    $templateCards .= '
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-gray-900 text-lg">' . safe_html($template['name']) . '</h3>
                <p class="text-sm text-gray-500">Key: <code class="bg-gray-200 px-1 rounded">' . safe_html($template['type']) . '</code></p>
            </div>
            ' . $statusBadge . '
        </div>
        <form method="POST" class="p-6">
            ' . csrf_field() . '
            <input type="hidden" name="template_id" value="' . $template['id'] . '">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email Subject</label>
                    <input type="text" name="subject" value="' . safe_html($template['subject']) . '" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">HTML Content</label>
                    <textarea name="html_content" rows="8" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500" required>' . safe_html($template['html_content']) . '</textarea>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" name="active" value="1" ' . ($template['active'] ? 'checked' : '') . ' 
                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded cursor-pointer">
                        <label class="ml-2 text-sm text-gray-700 cursor-pointer font-medium">Active (Template is used for emails)</label>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center">
                        <i class="fas fa-save mr-2"></i> Update Template
                    </button>
                </div>
            </div>
        </form>
    </div>';
}

$content = '
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    ' . $navLinks . '

    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Email Templates</h1>
        <p class="mt-2 text-gray-600">Customize the content and subject of automated emails sent to customers.</p>
    </div>

    ' . ($message ? '
    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg shadow-sm">
        <div class="flex">
            <i class="fas fa-check-circle text-green-400 mt-1"></i>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">' . safe_html($message) . '</p>
            </div>
        </div>
    </div>' : '') . '

    ' . ($error ? '
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg shadow-sm">
        <div class="flex">
            <i class="fas fa-exclamation-circle text-red-400 mt-1"></i>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">' . safe_html($error) . '</p>
            </div>
        </div>
    </div>' : '') . '

    <div class="space-y-2">
        ' . $templateCards . '
    </div>
</div>';

echo adminSidebarWrapper('Email Templates', $content, 'settings');
?>
