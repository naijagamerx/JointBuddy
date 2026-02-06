<?php
// Help Centre Page - Customer Support and FAQs
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
$storeName = $settings['store_name'] ?? $settings['site_name'] ?? 'Our Store';

$successMessage = '';
$errorMessage = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    try {
        $database = new Database();
        $db = $database->getConnection();

        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $message = trim($_POST['message'] ?? '');

        // Validate
        if (empty($subject) || empty($message)) {
            throw new Exception('Subject and message are required.');
        }

        if (strlen($subject) < 3) {
            throw new Exception('Subject must be at least 3 characters long.');
        }

        if (strlen($message) < 10) {
            throw new Exception('Message must be at least 10 characters long.');
        }

        // Get user data
        $stmt = $db->prepare("SELECT email, first_name, last_name, phone FROM users WHERE id = ?");
        $stmt->execute([$currentUser['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Determine priority
        $priority = 'normal';
        if (in_array($category, ['complaint', 'technical'])) {
            $priority = 'high';
        }

        // Insert message
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, phone, user_id, subject, category, message, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
        $stmt->execute([
            $currentUser['name'],
            $userData['email'],
            $userData['phone'] ?: null,
            $currentUser['id'],
            $subject,
            $category,
            $message,
            $priority
        ]);

        $successMessage = 'Your message has been sent! We will get back to you within 24 hours.';

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

$pageTitle = "Help Centre";
$currentPage = "help-centre";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <?php if ($successMessage): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <p><?= htmlspecialchars($successMessage) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Title Card -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Help Centre</h2>
                    <p class="text-green-100 text-sm">Find answers and get the help you need</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-question-circle text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row gap-6">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Help Centre -->
        <div class="lg:w-3/4">
            <!-- Search Bar -->
    <div class="mb-8">
        <div class="max-w-2xl mx-auto">
            <div class="relative">
                <input 
                    type="text" 
                    placeholder="Search for help articles..." 
                    class="w-full pl-12 pr-4 py-4 text-lg border rounded-lg focus:ring-green-500 focus:border-green-500"
                >
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Topics -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Popular Topics</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="p-4 border rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Getting Started</h3>
                            <p class="text-gray-600 text-sm">New to <?= htmlspecialchars($storeName ?? 'Our Store') ?>? Start here</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Orders & Shipping</h3>
                            <p class="text-gray-600 text-sm">Track, modify, and manage orders</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Payments & Refunds</h3>
                            <p class="text-gray-600 text-sm">Billing, payment methods, and refunds</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Returns & Exchanges</h3>
                            <p class="text-gray-600 text-sm">Return policy and procedures</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Account Security</h3>
                            <p class="text-gray-600 text-sm">Password, 2FA, and account safety</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Cannabis Education</h3>
                            <p class="text-gray-600 text-sm">Learn about cannabis and products</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Articles -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Recent Articles</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <article class="border-b pb-4 last:border-b-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        <a href="#" class="hover:text-green-600 transition">How to track your order status</a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-2">Learn how to monitor your order from confirmation to delivery</p>
                    <div class="flex items-center text-sm text-gray-500">
                        <span>Orders & Shipping</span>
                        <span class="mx-2">•</span>
                        <span>Updated 2 days ago</span>
                        <span class="mx-2">•</span>
                        <span>5 min read</span>
                    </div>
                </article>

                <article class="border-b pb-4 last:border-b-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        <a href="#" class="hover:text-green-600 transition">Understanding our refund policy</a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-2">Everything you need to know about returns and refunds</p>
                    <div class="flex items-center text-sm text-gray-500">
                        <span>Returns & Refunds</span>
                        <span class="mx-2">•</span>
                        <span>Updated 1 week ago</span>
                        <span class="mx-2">•</span>
                        <span>8 min read</span>
                    </div>
                </article>

                <article class="border-b pb-4 last:border-b-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        <a href="#" class="hover:text-green-600 transition">Setting up two-factor authentication</a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-2">Secure your account with an extra layer of protection</p>
                    <div class="flex items-center text-sm text-gray-500">
                        <span>Account Security</span>
                        <span class="mx-2">•</span>
                        <span>Updated 3 days ago</span>
                        <span class="mx-2">•</span>
                        <span>6 min read</span>
                    </div>
                </article>

                <article class="border-b pb-4 last:border-b-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        <a href="#" class="hover:text-green-600 transition">Cannabis dosage guide for beginners</a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-2">A comprehensive guide to understanding cannabis dosing</p>
                    <div class="flex items-center text-sm text-gray-500">
                        <span>Cannabis Education</span>
                        <span class="mx-2">•</span>
                        <span>Updated 5 days ago</span>
                        <span class="mx-2">•</span>
                        <span>12 min read</span>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <!-- Help Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Getting Started -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Getting Started</h2>
            </div>
            <div class="p-6">
                <ul class="space-y-3">
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Creating your account</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Verifying your identity</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Your first order</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Understanding delivery zones</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Account & Profile -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Account & Profile</h2>
            </div>
            <div class="p-6">
                <ul class="space-y-3">
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Managing personal information</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Changing your password</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Setting up email preferences</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-green-600 hover:text-green-700 flex items-center justify-between">
                            <span>Closing your account</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Still Need Help - Contact Form -->
    <div class="mt-8 bg-white border border-gray-200 rounded-lg p-6">
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Still need help?</h3>
            <p class="text-gray-600">Can't find what you're looking for? Send us a message and our support team will get back to you.</p>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="send_message">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select
                        id="category"
                        name="category"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="general">General Inquiry</option>
                        <option value="order">Order Related</option>
                        <option value="payment">Payment & Billing</option>
                        <option value="return">Returns & Refunds</option>
                        <option value="technical">Technical Support</option>
                        <option value="complaint">Complaint</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        required
                        minlength="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="Brief description of your inquiry"
                    >
                </div>
            </div>
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                <textarea
                    id="message"
                    name="message"
                    required
                    minlength="10"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Please provide details about your inquiry..."
                ></textarea>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    We typically respond within 24 hours
                </p>
                <button
                    type="submit"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition font-semibold"
                >
                    <i class="fas fa-paper-plane mr-2"></i>Send Message
                </button>
            </div>
        </form>
    </div>
            </div>
        </div>
                </div>

<?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
