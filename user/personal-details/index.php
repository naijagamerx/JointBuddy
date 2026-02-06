<?php
/**
 * Personal Details Page
 *
 * Manage user profile information
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();
$userData = [];

// Fetch user data from database
try {
    $db = Services::db();
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, phone, date_of_birth, password FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $userData = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');

    if (!empty($firstName)) {
        try {
            // Check if email is being changed
            $emailChanged = ($email !== $userData['email']);

            // If email is being changed, verify current password
            if ($emailChanged) {
                if (empty($currentPassword)) {
                    throw new Exception('Please enter your current password to change your email address.');
                }

                // Verify current password
                if (!password_verify($currentPassword, $userData['password'])) {
                    throw new Exception('Current password is incorrect.');
                }

                // Check if new email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $currentUser['id']]);
                if ($stmt->fetch()) {
                    throw new Exception('This email address is already in use.');
                }
            }

            // Update user
            $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $email, $phone, $dateOfBirth ?: null, $currentUser['id']]);

            // Update session if name or email changed
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            if ($emailChanged) {
                $_SESSION['user_email'] = $email;
                $currentUser['email'] = $email;
                $userData['email'] = $email;
            }

            $successMessage = $emailChanged
                ? "Personal details updated successfully! Your email has been changed."
                : "Personal details updated successfully!";

            // Refresh data
            $stmt = $db->prepare("SELECT id, email, first_name, last_name, phone, date_of_birth, password FROM users WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $errorMessage = "Error updating details: " . $e->getMessage();
        }
    }
}

$pageTitle = "Personal Details";
$currentPage = "personal-details";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                    <p class="text-teal-100 text-sm">Manage your personal information</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user-edit text-2xl"></i>
                </div>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Universal Sidebar Navigation -->
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <!-- Main Content - Personal Details -->
            <div class="lg:w-3/4">
                <!-- Personal Information Form -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Personal Information</h3>
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name
                                </label>
                                <input
                                    type="text"
                                    id="firstName"
                                    name="firstName"
                                    value="<?= htmlspecialchars($userData['first_name'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    required
                                >
                            </div>
                            <div>
                                <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name
                                </label>
                                <input
                                    type="text"
                                    id="lastName"
                                    name="lastName"
                                    value="<?= htmlspecialchars($userData['last_name'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                required
                            >
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                        </div>

                        <div>
                            <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-2">
                                Date of Birth
                            </label>
                            <input
                                type="date"
                                id="dateOfBirth"
                                name="dateOfBirth"
                                value="<?= htmlspecialchars($userData['date_of_birth'] ?? '') ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                        </div>

                        <div id="passwordField" style="display: none;">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Password <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Enter your current password to change email"
                            >
                            <p class="text-xs text-gray-500 mt-1">Required when changing email address</p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Preferences -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Account Preferences</h3>
                    <form class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">Email Notifications</h4>
                                <p class="text-sm text-gray-600">Receive order updates and promotional emails</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">SMS Notifications</h4>
                                <p class="text-sm text-gray-600">Receive SMS updates for order status</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">Marketing Communications</h4>
                                <p class="text-sm text-gray-600">Receive special offers and new product announcements</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                        
                        <div class="flex justify-end">
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                            >
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Privacy Settings -->
                <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Privacy Settings</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Data Processing Consent</h4>
                                <p class="text-sm text-gray-600">Allow us to process your personal data for order fulfillment</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Third-party Analytics</h4>
                                <p class="text-sm text-gray-600">Share anonymous usage data to improve our services</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Show password field when email changes
const emailInput = document.getElementById('email');
const passwordField = document.getElementById('passwordField');
const passwordInput = document.getElementById('current_password');
const originalEmail = emailInput.value;

emailInput.addEventListener('input', function() {
    if (this.value !== originalEmail && this.value.trim() !== '') {
        passwordField.style.display = 'block';
        passwordInput.setAttribute('required', 'required');
    } else {
        passwordField.style.display = 'none';
        passwordInput.removeAttribute('required');
        passwordInput.value = '';
    }
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
