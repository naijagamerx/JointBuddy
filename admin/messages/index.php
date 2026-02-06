<?php
// Admin Messages Inbox - View and manage contact messages
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "cm.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter !== 'all') {
    $where[] = "cm.category = ?";
    $params[] = $categoryFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) FROM contact_messages cm WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalMessages = $stmt->fetchColumn();
$totalPages = ceil($totalMessages / $perPage);

// Get messages
$sql = "SELECT cm.*, u.first_name, u.last_name, au.username as admin_name
        FROM contact_messages cm
        LEFT JOIN users u ON cm.user_id = u.id
        LEFT JOIN admin_users au ON cm.admin_id = au.id
        WHERE $whereClause
        ORDER BY cm.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsSql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
    FROM contact_messages";
$stats = $db->query($statsSql)->fetch(PDO::FETCH_ASSOC);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];
        $messageId = (int)($_POST['message_id'] ?? 0);

        if ($action === 'mark_read') {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$messageId]);
            $_SESSION['success'] = 'Message marked as read';
        } elseif ($action === 'mark_replied') {
            $reply = trim($_POST['reply'] ?? '');
            if (empty($reply)) {
                throw new Exception('Reply message is required.');
            }
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', admin_reply = ?, admin_id = ?, replied_at = NOW() WHERE id = ?");
            $stmt->execute([$reply, $_SESSION['admin_id'], $messageId]);
            $_SESSION['success'] = 'Reply sent successfully';
        } elseif ($action === 'mark_resolved') {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'resolved', admin_id = ? WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $messageId]);
            $_SESSION['success'] = 'Message marked as resolved';
        } elseif ($action === 'mark_closed') {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'closed', admin_id = ? WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $messageId]);
            $_SESSION['success'] = 'Message closed';
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$messageId]);
            $_SESSION['success'] = 'Message deleted';
        }

        redirect('/admin/messages/?status=' . $statusFilter . '&category=' . $categoryFilter . '&page=' . $page);

    } catch (Exception $e) {
        $error = AppError::handleDatabaseError($e, 'Error processing request');
    }
}

// Check for messages
$message = '';
$error = '';
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$content = '';

// Add alert if any
if ($message) {
    $content .= adminAlert($message, 'success');
}
if ($error) {
    $content .= adminAlert($error, 'error');
}

$content .= '
<div class="w-full">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Contact Messages</h1>
            <p class="text-gray-600 mt-1">Manage customer inquiries and support requests</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Messages</p>
                    <p class="text-2xl font-bold text-gray-900">' . (int)$stats['total'] . '</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">New</p>
                    <p class="text-2xl font-bold text-green-600">' . (int)$stats['new_count'] . '</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-star text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Read</p>
                    <p class="text-2xl font-bold text-blue-600">' . (int)$stats['read_count'] . '</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope-open text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Replied</p>
                    <p class="text-2xl font-bold text-purple-600">' . (int)$stats['replied_count'] . '</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-reply text-purple-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Urgent</p>
                    <p class="text-2xl font-bold text-red-600">' . (int)$stats['urgent_count'] . '</p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="all" ' . ($statusFilter === 'all' ? 'selected' : '') . '>All Status</option>
                    <option value="new" ' . ($statusFilter === 'new' ? 'selected' : '') . '>New</option>
                    <option value="read" ' . ($statusFilter === 'read' ? 'selected' : '') . '>Read</option>
                    <option value="replied" ' . ($statusFilter === 'replied' ? 'selected' : '') . '>Replied</option>
                    <option value="resolved" ' . ($statusFilter === 'resolved' ? 'selected' : '') . '>Resolved</option>
                    <option value="closed" ' . ($statusFilter === 'closed' ? 'selected' : '') . '>Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="all" ' . ($categoryFilter === 'all' ? 'selected' : '') . '>All Categories</option>
                    <option value="general" ' . ($categoryFilter === 'general' ? 'selected' : '') . '>General</option>
                    <option value="order" ' . ($categoryFilter === 'order' ? 'selected' : '') . '>Order</option>
                    <option value="payment" ' . ($categoryFilter === 'payment' ? 'selected' : '') . '>Payment</option>
                    <option value="return" ' . ($categoryFilter === 'return' ? 'selected' : '') . '>Return</option>
                    <option value="technical" ' . ($categoryFilter === 'technical' ? 'selected' : '') . '>Technical</option>
                    <option value="complaint" ' . ($categoryFilter === 'complaint' ? 'selected' : '') . '>Complaint</option>
                    <option value="other" ' . ($categoryFilter === 'other' ? 'selected' : '') . '>Other</option>
                </select>
            </div>
            <div class="self-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="<?php echo adminUrl('messages/'); ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 ml-2">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Messages List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

if (empty($messages)) {
    $content .= '
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                            <p>No messages found</p>
                        </td>
                    </tr>';
} else {
    foreach ($messages as $msg) {
        $statusColors = [
            'new' => 'bg-green-100 text-green-800',
            'read' => 'bg-blue-100 text-blue-800',
            'replied' => 'bg-purple-100 text-purple-800',
            'resolved' => 'bg-gray-100 text-gray-800',
            'closed' => 'bg-red-100 text-red-800'
        ];
        $priorityColors = [
            'low' => 'bg-gray-100 text-gray-800',
            'normal' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-orange-100 text-orange-800',
            'urgent' => 'bg-red-100 text-red-800'
        ];

        $content .= '
                    <tr class="' . ($msg['status'] === 'new' ? 'bg-green-50' : '') . '">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusColors[$msg['status']] . '">
                                ' . ucfirst($msg['status']) . '
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($msg['name']) . '</div>
                            <div class="text-sm text-gray-500">' . htmlspecialchars($msg['email']) . '</div>
                            ' . (!empty($msg['phone']) ? '<div class="text-xs text-gray-400">' . htmlspecialchars($msg['phone']) . '</div>' : '') . '
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">' . htmlspecialchars($msg['subject']) . '</div>
                            <div class="text-sm text-gray-500 truncate max-w-xs">' . htmlspecialchars(substr($msg['message'], 0, 50)) . '...</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                ' . ucfirst($msg['category']) . '
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' . $priorityColors[$msg['priority']] . '">
                                ' . ucfirst($msg['priority']) . '
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ' . date('M j, Y g:i A', strtotime($msg['created_at'])) . '
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewMessage(' . $msg['id'] . ')" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>';
    }
}

$content .= '
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        ' . ($totalPages > 1 ? '
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="?status=' . $statusFilter . '&category=' . $categoryFilter . '&page=' . max(1, $page - 1) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="?status=' . $statusFilter . '&category=' . $categoryFilter . '&page=' . min($totalPages, $page + 1) . '" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">' . (($page - 1) * $perPage + 1) . '</span>
                            to <span class="font-medium">' . min($page * $perPage, $totalMessages) . '</span>
                            of <span class="font-medium">' . $totalMessages . '</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            ' . ($page > 1 ? '<a href="?status=' . $statusFilter . '&category=' . $categoryFilter . '&page=' . ($page - 1) . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>' : '') . '
                            ' . ($page < $totalPages ? '<a href="?status=' . $statusFilter . '&category=' . $categoryFilter . '&page=' . ($page + 1) . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>' : '') . '
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        ' : '') . '
    </div>
</div>

<!-- Message View Modal -->
<div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-medium text-gray-900">Message Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="messageContent" class="mt-4">
                <!-- Message content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewMessage(id) {
    const modal = document.getElementById("messageModal");
    const content = document.getElementById("messageContent");
    content.innerHTML = \'<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i></div>\';
    modal.classList.remove("hidden");

    fetch("/admin/messages/ajax.php?id=" + id)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = \'<div class="text-red-600">\' + data.error + \'</div>\';
            } else {
                content.innerHTML = data.html;
            }
        })
        .catch(error => {
            content.innerHTML = \'<div class="text-red-600">Error loading message</div>\';
        });
}

function closeModal() {
    document.getElementById("messageModal").classList.add("hidden");
}

// Close modal on outside click
document.getElementById("messageModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>';

echo adminSidebarWrapper('Messages', $content, 'messages');
