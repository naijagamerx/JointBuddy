<?php
/**
 * Cancel Return Request - CannaBuddy
 */
require_once __DIR__ . '/../../includes/url_helper.php';
session_start();

$currentUser = null;
$isLoggedIn = false;

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

// Redirect to login if not logged in
if (!$isLoggedIn) {
    redirect('/user/login/?redirect=' . urlencode('/user/returns/'));
}

// Include database
require_once __DIR__ . '/../../includes/database.php';

$returnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$returnId) {
    redirect('/user/returns/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['confirm'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();

        // Verify return belongs to user and is pending
        $stmt = $db->prepare("SELECT * FROM returns WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$returnId, $currentUser['id']]);
        $return = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($return) {
            // Cancel the return
            $stmt = $db->prepare("UPDATE returns SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$returnId]);

            // Log status change
            $stmt = $db->prepare("
                INSERT INTO return_status_history (return_id, old_status, new_status, created_at)
                VALUES (?, 'pending', 'cancelled', NOW())
            ");
            $stmt->execute([$returnId]);

            $_SESSION['return_cancelled'] = true;
        }

        redirect('/user/returns/');

    } catch (Exception $e) {
        error_log("Cancel return error: " . $e->getMessage());
        redirect('/user/returns/');
    }
}

// If not POST, show confirmation
$pageTitle = "Cancel Return";
$currentPage = "returns";

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex flex-col lg:flex-row gap-6">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <div class="lg:w-3/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-4xl text-yellow-600"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Cancel Return Request?</h1>
                    <p class="text-gray-600 mb-6">Are you sure you want to cancel this return? This action cannot be undone.</p>

                    <div class="flex justify-center gap-4">
                        <a href="<?= userUrl('/returns/view.php?id=' . $returnId) ?>"
                           class="px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            No, Keep Return
                        </a>
                        <a href="<?= userUrl('/returns/cancel.php?id=' . $returnId . '&confirm=1') ?>"
                           class="px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            Yes, Cancel Return
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>
