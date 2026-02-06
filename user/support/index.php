<?php
// Support Page - Customer Support and Help
session_start();
require_once __DIR__ . '/../../includes/url_helper.php';
require_once __DIR__ . '/../../includes/database.php';

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
    header('Location: ' . userUrl('/login/'));
    exit;
}

// Fetch settings from database
$settings = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE category = 'general'");
    $settingsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settingsData as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
}

// Use settings or fallback defaults
$storeEmail = $settings['store_email'] ?? $settings['support_email'] ?? 'support@example.com';
$storePhone = $settings['store_phone'] ?? $settings['phone'] ?? '+27 11 123 4567';
$businessHours = $settings['business_hours'] ?? 'Mon-Fri: 9AM - 6PM SAST';

$pageTitle = "Support";
$currentPage = "support";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                    <p class="text-indigo-100 text-sm">How can we help you today?</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-headset text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <a href="<?= userUrl('/logout/" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-1 rounded text-sm font-medium transition">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Universal Sidebar Navigation -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <!-- Main Content - Support -->
            <div class="lg:w-3/4">
                <!-- Support Options -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-6 hover:shadow-md transition cursor-pointer">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comments text-blue-600 text-lg"></i>
                            </div>
                            <h3 class="text-base font-semibold mb-2">Live Chat</h3>
                            <p class="text-gray-600 text-sm mb-4">Chat with our support team in real-time</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Available Now</span>
                        </div>
                    </div>
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-6 hover:shadow-md transition cursor-pointer">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-phone text-green-600 text-lg"></i>
                            </div>
                            <h3 class="text-base font-semibold mb-2">Phone Support</h3>
                            <p class="text-gray-600 text-sm mb-4">Call us for immediate assistance</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">9AM - 6PM SAST</span>
                        </div>
                    </div>
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-6 hover:shadow-md transition cursor-pointer">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-envelope text-purple-600 text-lg"></i>
                            </div>
                            <h3 class="text-base font-semibold mb-2">Email Support</h3>
                            <p class="text-gray-600 text-sm mb-4">Send us a detailed message</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">24/7 Response</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="<?= userUrl('/orders/" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-box text-gray-600 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">Track Your Order</h4>
                                <p class="text-sm text-gray-600">Check order status and delivery updates</p>
                            </div>
                        </a>
                        <a href="<?= userUrl('/returns/" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-undo text-gray-600 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">Return an Item</h4>
                                <p class="text-sm text-gray-600">Start a return or exchange process</p>
                            </div>
                        </a>
                        <a href="<?= userUrl('/payment-history/" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-credit-card text-gray-600 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">Billing Questions</h4>
                                <p class="text-sm text-gray-600">Review your payment history and invoices</p>
                            </div>
                        </a>
                        <a href="<?= userUrl('/personal-details/" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-user-edit text-gray-600 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">Account Settings</h4>
                                <p class="text-sm text-gray-600">Update your profile and preferences</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-phone text-gray-600"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Phone</p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($storePhone) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-envelope text-gray-600"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Email</p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($storeEmail) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-clock text-gray-600"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Business Hours</p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($businessHours) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Emergency Support</h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Order Issues</p>
                                    <p class="text-sm text-gray-600">For urgent order problems</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-shield-alt text-blue-600"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Account Security</p>
                                    <p class="text-sm text-gray-600">For security concerns</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-medkit text-green-600"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Medical Inquiries</p>
                                    <p class="text-sm text-gray-600">Product and medical questions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
