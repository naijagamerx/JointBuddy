<?php
/**
 * Return Request Confirmation - CannaBuddy
 * Success page after submitting a return request
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

$db = null;
$return = null;
$returnId = isset($_GET['return_id']) ? (int)$_GET['return_id'] : 0;

if (!$returnId) {
    redirect('/user/returns/');
}

if ($db) {
    try {
        $database = new Database();
        $db = $database->getConnection();

        // Get return details
        $stmt = $db->prepare("SELECT * FROM returns WHERE id = ? AND user_id = ?");
        $stmt->execute([$returnId, $currentUser['id']]);
        $return = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$return) {
            redirect('/user/returns/');
        }

    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

$pageTitle = "Return Submitted";
$currentPage = "returns";

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Success Header -->
                <div class="bg-green-50 border-b border-green-100 px-6 py-8 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-4xl text-green-600"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Return Request Submitted!</h1>
                    <p class="text-gray-600">Your return request has been successfully submitted.</p>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Return Number -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <span class="text-sm text-gray-500">Return Number</span>
                                <p class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($return['return_number'] ?? '') ?></p>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <?php
                                $statusBadge = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-blue-100 text-blue-800',
                                    'received' => 'bg-purple-100 text-purple-800',
                                    'refunded' => 'bg-green-100 text-green-800',
                                ];
                                $statusLabel = [
                                    'pending' => 'Pending Review',
                                    'approved' => 'Approved',
                                    'received' => 'Item Received',
                                    'refunded' => 'Refunded',
                                ];
                                $status = $return['status'] ?? 'pending';
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusBadge[$status] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= $statusLabel[$status] ?? ucfirst($status) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- What Happens Next -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">What Happens Next?</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-medium text-green-600">1</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">We'll Review Your Request</h4>
                                    <p class="text-sm text-gray-600">Our team will review your return request within 1-2 business days. You'll receive an email update when the status changes.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-medium text-gray-600">2</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Return Method Instructions</h4>
                                    <p class="text-sm text-gray-600">
                                        <?php if ($return['courier_method'] === 'courier'): ?>
                                            We'll arrange for a courier to collect the item from your address. You'll receive an email with the collection details.
                                        <?php else: ?>
                                            You can drop off the item at our store. Please bring this return number with you.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-medium text-gray-600">3</span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Refund Processing</h4>
                                    <p class="text-sm text-gray-600">Once we receive and inspect the item, your refund of R <?= number_format($return['total_amount'] ?? 0, 2) ?> will be processed within 5-7 business days.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 text-xl mt-0.5"></i>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-blue-800">Track Your Return</h4>
                                <p class="text-sm text-blue-700 mt-1">
                                    You can track the status of this return at any time from your
                                    <a href="<?= userUrl('/returns/') ?>" class="underline">Returns Dashboard</a>.
                                    We'll also send email notifications for status updates.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-4">
                        <a href="<?= userUrl('/returns/view.php?id=' . $returnId) ?>"
                           class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-eye mr-2"></i>View Return Details
                        </a>
                        <a href="<?= shopUrl('/') ?>"
                           class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>
