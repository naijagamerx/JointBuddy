<?php
// Contact Page - Public Contact Form
session_start();
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/database.php';

// Check for QR code scan
$qrScanData = null;
if (isset($_GET['qr'])) {
    require_once __DIR__ . '/../includes/qr_code_service.php';
    try {
        $database = new Database();
        $db = $database->getConnection();
        $qrService = new QRCodeService($db);
        $qrResult = $qrService->trackQRScan($_GET['qr']);
        if ($qrResult['success']) {
            $qrScanData = $qrResult;
            // Store scan ID in session for contact form submission
            $_SESSION['qr_scan_id'] = $qrResult['scan_id'];
            $_SESSION['qr_code_data'] = $qrResult['qr_code'];
            $_SESSION['qr_details'] = $qrResult['details'];
        }
    } catch (Exception $e) {
        // QR tracking failed, continue normally
        error_log("QR tracking error: " . $e->getMessage());
    }
}

$isLoggedIn = false;
$currentUser = null;

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
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
    // Use defaults if settings can't be loaded
    $settings = [];
}

// Use settings or fallback defaults
$storeName = $settings['store_name'] ?? $settings['site_name'] ?? 'Our Store';
$storeEmail = $settings['store_email'] ?? $settings['support_email'] ?? 'support@example.com';
$storePhone = $settings['store_phone'] ?? $settings['phone'] ?? '+27 11 123 4567';
$storeAddress = $settings['store_address'] ?? $settings['address'] ?? '';
$businessHours = $settings['business_hours'] ?? 'Mon-Fri: 9AM - 6PM SAST';

$successMessage = '';
$errorMessage = '';
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'category' => 'general',
    'message' => ''
];

// Pre-fill subject if QR scan
if ($qrScanData && !empty($_SESSION['qr_details'])) {
    if ($qrScanData['qr_code']['qr_code_type'] === 'product') {
        $formData['subject'] = 'Inquiry about: ' . $_SESSION['qr_details']['name'];
        $formData['category'] = 'order';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();

        // Get QR scan ID if available
        $qrScanId = $_SESSION['qr_scan_id'] ?? null;

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $message = trim($_POST['message'] ?? '');

        // Validate
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            throw new Exception('Please fill in all required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (strlen($subject) < 3) {
            throw new Exception('Subject must be at least 3 characters long.');
        }

        if (strlen($message) < 10) {
            throw new Exception('Message must be at least 10 characters long.');
        }

        // Determine priority
        $priority = 'normal';
        $categoryLower = strtolower($category);
        if (strpos($categoryLower, 'urgent') !== false || strpos($subject, 'urgent') !== false) {
            $priority = 'urgent';
        } elseif (in_array($category, ['complaint', 'technical'])) {
            $priority = 'high';
        }

        // Insert message
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, phone, user_id, subject, category, message, priority, status, qr_scan_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', ?)");
        $stmt->execute([
            $name,
            $email,
            $phone ?: null,
            $isLoggedIn ? $currentUser['id'] : null,
            $subject,
            $category,
            $message,
            $priority,
            $qrScanId
        ]);

        $contactMessageId = $db->lastInsertId();

        // Update QR scan record if this was from a QR code
        if ($qrScanId) {
            $stmt = $db->prepare("UPDATE qr_scans SET contact_form_submitted = 1, contact_message_id = ? WHERE id = ?");
            $stmt->execute([$contactMessageId, $qrScanId]);
            // Clear QR session data
            unset($_SESSION['qr_scan_id'], $_SESSION['qr_code_data'], $_SESSION['qr_details']);
        }

        $successMessage = 'Thank you for contacting us! We will get back to you within 24 hours.';
        $formData = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'subject' => '',
            'category' => 'general',
            'message' => ''
        ];

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        $formData = $_POST;
    }
}

// Pre-fill data if logged in
if ($isLoggedIn && empty($_POST)) {
    $formData['name'] = $currentUser['name'];
    $formData['email'] = $currentUser['email'];
}

$pageTitle = "Contact Us";
$currentPage = "contact";

// Include header
include __DIR__ . '/../includes/header.php';
?>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-7xl">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Contact Us</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Have a question or need assistance? We're here to help. Fill out the form below and we'll get back to you as soon as possible.</p>
        </div>

        <?php if ($qrScanData): ?>
            <!-- QR Code Scan Info Banner -->
            <div class="max-w-2xl mx-auto mb-8 bg-blue-50 border-l-4 border-blue-400 px-6 py-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-qrcode text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <?php if ($qrScanData['qr_code']['qr_code_type'] === 'product' && !empty($_SESSION['qr_details'])): ?>
                            <p class="text-sm text-blue-800">
                                <strong>Scanned Product:</strong> <?= htmlspecialchars($_SESSION['qr_details']['name']) ?>
                                <span class="ml-2 text-blue-600">R<?= number_format($_SESSION['qr_details']['price'], 2) ?></span>
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-blue-800">
                                <strong>We're ready to help!</strong>
                            </p>
                        <?php endif; ?>
                        <p class="text-xs text-blue-600 mt-1">Please fill out the form below and we'll get back to you right away.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="max-w-2xl mx-auto mb-8 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <p><?= htmlspecialchars($successMessage) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="max-w-2xl mx-auto mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">Send us a Message</h2>
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="<?= htmlspecialchars($formData['name']) ?>"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="John Doe"
                                >
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($formData['email']) ?>"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="john@example.com"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($formData['phone']) ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="+27 12 345 6789"
                            >
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select
                                    id="category"
                                    name="category"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="general" <?= $formData['category'] === 'general' ? 'selected' : '' ?>>General Inquiry</option>
                                    <option value="order" <?= $formData['category'] === 'order' ? 'selected' : '' ?>>Order Related</option>
                                    <option value="payment" <?= $formData['category'] === 'payment' ? 'selected' : '' ?>>Payment & Billing</option>
                                    <option value="return" <?= $formData['category'] === 'return' ? 'selected' : '' ?>>Returns & Refunds</option>
                                    <option value="technical" <?= $formData['category'] === 'technical' ? 'selected' : '' ?>>Technical Support</option>
                                    <option value="complaint" <?= $formData['category'] === 'complaint' ? 'selected' : '' ?>>Complaint</option>
                                    <option value="other" <?= $formData['category'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                                <input
                                    type="text"
                                    id="subject"
                                    name="subject"
                                    value="<?= htmlspecialchars($formData['subject']) ?>"
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
                                rows="6"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Please provide details about your inquiry..."
                            ><?= htmlspecialchars($formData['message']) ?></textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 font-semibold transition"
                        >
                            <i class="fas fa-paper-plane mr-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-phone text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Phone</p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($storePhone) ?></p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-envelope text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Email</p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($storeEmail) ?></p>
                            </div>
                        </div>
                        <?php if (!empty($storeAddress)): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Address</p>
                                <p class="text-sm text-gray-600 whitespace-pre-line"><?= htmlspecialchars($storeAddress) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Business Hours</p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($businessHours) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Links</h3>
                    <div class="space-y-3">
                        <?php if ($isLoggedIn): ?>
                            <a href="<?= userUrl('/orders/') ?>" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-box text-gray-600 mr-3"></i>
                                <span class="text-gray-900">Track Your Order</span>
                            </a>
                            <a href="<?= userUrl('/returns/') ?>" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-undo text-gray-600 mr-3"></i>
                                <span class="text-gray-900">Returns & Exchanges</span>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('/shop/') ?>" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-shopping-bag text-gray-600 mr-3"></i>
                                <span class="text-gray-900">Browse Products</span>
                            </a>
                            <a href="<?= url('/faq/') ?>" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-question-circle text-gray-600 mr-3"></i>
                                <span class="text-gray-900">FAQs</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">Need Immediate Help?</h3>
                    <p class="text-green-100 text-sm mb-4">Our support team is ready to assist you with any urgent inquiries.</p>
                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $storePhone) ?>" class="inline-block bg-white text-green-600 px-6 py-2 rounded-lg hover:bg-gray-100 font-semibold transition">
                        <i class="fas fa-phone mr-2"></i>Call Now
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
