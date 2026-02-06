<?php
// AJAX endpoint for loading message details
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/database.php';
require_once __DIR__ . '/../../../includes/url_helper.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);

    if (!$adminAuth || !$adminAuth->isLoggedIn()) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $messageId = (int)($_GET['id'] ?? 0);

    if ($messageId <= 0) {
        echo json_encode(['error' => 'Invalid message ID']);
        exit;
    }

    // Get message details
    $stmt = $db->prepare("SELECT cm.*, u.first_name, u.last_name, au.username as admin_name
            FROM contact_messages cm
            LEFT JOIN users u ON cm.user_id = u.id
            LEFT JOIN admin_users au ON cm.admin_id = au.id
            WHERE cm.id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        echo json_encode(['error' => 'Message not found']);
        exit;
    }

    // Mark as read if still new
    if ($message['status'] === 'new') {
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$messageId]);
    }

    // Format message HTML
    $html = '
        <div class="space-y-6">
            <!-- From Section -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">From</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-600">Name:</span> <span class="font-medium">' . htmlspecialchars($message['name']) . '</span></div>
                    <div><span class="text-gray-600">Email:</span> <span class="font-medium">' . htmlspecialchars($message['email']) . '</span></div>
                    ' . (!empty($message['phone']) ? '<div><span class="text-gray-600">Phone:</span> <span class="font-medium">' . htmlspecialchars($message['phone']) . '</span></div>' : '') . '
                    ' . (!empty($message['user_id']) ? '<div><span class="text-gray-600">User ID:</span> <span class="font-medium">' . $message['user_id'] . '</span></div>' : '') . '
                    <div><span class="text-gray-600">Date:</span> <span class="font-medium">' . date('M j, Y g:i A', strtotime($message['created_at'])) . '</span></div>
                </div>
            </div>

            <!-- Subject & Category -->
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Subject</h4>
                <p class="text-lg font-semibold text-gray-800">' . htmlspecialchars($message['subject']) . '</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">' . ucfirst($message['category']) . '</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ' . ($message['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : ($message['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800')) . '">' . ucfirst($message['priority']) . '</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ' . ($message['status'] === 'new' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . '">' . ucfirst($message['status']) . '</span>
                </div>
            </div>

            <!-- Message -->
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Message</h4>
                <div class="bg-white border border-gray-200 rounded-lg p-4 whitespace-pre-wrap text-gray-700">' . htmlspecialchars($message['message']) . '</div>
            </div>

            ' . (!empty($message['admin_reply']) ? '
            <!-- Previous Reply -->
            <div class="bg-purple-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">Your Reply</h4>
                <p class="text-gray-700">' . htmlspecialchars($message['admin_reply']) . '</p>
                <p class="text-xs text-gray-500 mt-2">Replied by ' . htmlspecialchars($message['admin_name']) . ' on ' . date('M j, Y g:i A', strtotime($message['replied_at'])) . '</p>
            </div>
            ' : '') . '

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 pt-4 border-t">
                <form method="POST" action="<?php echo adminUrl('messages/'); ?>" class="inline">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="message_id" value="' . $message['id'] . '">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-envelope-open mr-2"></i>Mark Read
                    </button>
                </form>
                <button onclick="showReplyForm()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                    <i class="fas fa-reply mr-2"></i>Reply
                </button>
                <form method="POST" action="<?php echo adminUrl('messages/'); ?>" class="inline">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="mark_resolved">
                    <input type="hidden" name="message_id" value="' . $message['id'] . '">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                        <i class="fas fa-check mr-2"></i>Resolve
                    </button>
                </form>
                <form method="POST" action="<?php echo adminUrl('messages/'); ?>" class="inline" onsubmit="return confirm(\'Close this message?\');">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="mark_closed">
                    <input type="hidden" name="message_id" value="' . $message['id'] . '">
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                </form>
                <form method="POST" action="<?php echo adminUrl('messages/'); ?>" class="inline" onsubmit="return confirm(\'Delete this message?\');">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="' . $message['id'] . '">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </form>
            </div>

            <!-- Reply Form -->
            <div id="replyForm" class="hidden">
                <h4 class="font-medium text-gray-900 mb-3">Send Reply</h4>
                <form method="POST" action="<?php echo adminUrl('messages/'); ?>">
                    ' . csrf_field() . '
                    <input type="hidden" name="action" value="mark_replied">
                    <input type="hidden" name="message_id" value="' . $message['id'] . '">
                    <textarea name="reply" required rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Type your reply..."></textarea>
                    <div class="mt-3 flex justify-end">
                        <button type="button" onclick="hideReplyForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 mr-3">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            <i class="fas fa-paper-plane mr-2"></i>Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function showReplyForm() {
            document.getElementById("replyForm").classList.remove("hidden");
        }
        function hideReplyForm() {
            document.getElementById("replyForm").classList.add("hidden");
        }
        </script>
    ';

    echo json_encode(['html' => $html]);

} catch (Exception $e) {
    echo json_encode(['error' => AppError::handleDatabaseError($e, 'Failed to load message')]);
}
