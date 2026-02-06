<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle unsubscribe action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unsubscribe' && isset($_POST['id'])) {
    CsrfMiddleware::validate();
    try {
        $subscriberId = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$subscriberId]);
        $_SESSION['success'] = 'Subscriber removed successfully';
        redirect(adminUrl('/newsletter/'));
    } catch (Exception $e) {
        error_log("Error removing subscriber: " . $e->getMessage());
        $_SESSION['error'] = 'Error removing subscriber';
    }
}

// Fetch Logic
$subscribers = [];
$totalSubscribers = 0;
$newSubscribers = 0; // Last 7 days

if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'newsletter_subscribers'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC");
            $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalSubscribers = count($subscribers);

            // Count new subscribers (last 7 days)
            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
            foreach ($subscribers as $sub) {
                if (($sub['created_at'] ?? '') >= $sevenDaysAgo) {
                    $newSubscribers++;
                }
            }
        }
    } catch (Exception $e) {}
}

// View Logic
$content = '<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Newsletter Subscribers</h1>
            <p class="text-gray-600 mt-1">Manage email subscriptions</p>
        </div>
        <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            <i class="fas fa-download mr-2"></i>Export CSV
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500">Total Subscribers</p>
                    <h3 class="text-2xl font-bold">' . number_format($totalSubscribers) . '</h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                    <i class="fas fa-user-plus text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500">New (Last 7 Days)</p>
                    <h3 class="text-2xl font-bold">' . number_format($newSubscribers) . '</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribed At</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">';

if (empty($subscribers)) {
    $content .= '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No subscribers found.</td></tr>';
} else {
    foreach ($subscribers as $sub) {
        $content .= '<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($sub['email']) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Subscribed</span></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('M j, Y', strtotime($sub['created_at'])) . '</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to remove this subscriber?\')">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="unsubscribe">
                    <input type="hidden" name="id" value="' . $sub['id'] . '">
                    <button type="submit" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-user-slash mr-1"></i>Remove
                    </button>
                </form>
            </td>
        </tr>';
    }
}

$content .= '</tbody></table></div></div>';

echo adminSidebarWrapper('Newsletter', $content, 'newsletter');
