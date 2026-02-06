<?php
/**
 * Edit Address Page
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();
$message = '';
$messageType = '';
$address = null;

// Load address service
require_once INCLUDES_PATH . '/user_addresses_service.php';

// Get database
$db = Services::db();

// Get address ID from URL
$addressId = intval($_GET['id'] ?? 0);

if ($addressId <= 0) {
    header('Location: ' . userUrl('address-book/'));
    exit;
}

// Fetch address
$addresses = getUserAddresses($db, $currentUser['id']);
foreach ($addresses as $addr) {
    if ($addr['id'] == $addressId) {
        $address = $addr;
        break;
    }
}

// If address not found or doesn't belong to user, redirect
if (!$address) {
    header('Location: ' . userUrl('address-book/'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();

    try {
        $addressData = [
            'label' => $_POST['label'] ?? 'Home',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address_line1' => $_POST['address_line1'] ?? '',
            'address_line2' => $_POST['address_line2'] ?? '',
            'city' => $_POST['city'] ?? '',
            'province' => $_POST['province'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'country' => $_POST['country'] ?? 'South Africa',
            'address_type' => $_POST['address_type'] ?? 'residential',
            'delivery_instructions' => $_POST['delivery_instructions'] ?? '',
            'default_for_shipping' => isset($_POST['default_for_shipping'])
        ];

        $result = updateUserAddress($db, $currentUser['id'], $addressId, $addressData);
        if ($result) {
            $message = 'Address updated successfully!';
            $messageType = 'success';
            // Redirect after short delay
            header('Location: ' . userUrl('address-book/'));
            exit;
        } else {
            $message = 'Failed to update address. Please check your information.';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$pageTitle = "Edit Address";
$currentPage = "address-book";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Address</h1>
                <p class="text-gray-600 mt-1">Update your delivery address information</p>
            </div>
            <a href="<?= userUrl('address-book/') ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to Address Book
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> px-4 py-3 rounded-lg mb-6 border">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                <form method="POST" action="" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" name="first_name" required
                                   value="<?= htmlspecialchars($address['first_name'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                   placeholder="John">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" name="last_name" required
                                   value="<?= htmlspecialchars($address['last_name'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Doe">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" name="phone" required
                               value="<?= htmlspecialchars($address['phone'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                               placeholder="+27 XX XXX XXXX">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                        <input type="text" name="address_line1" required
                               value="<?= htmlspecialchars($address['address_line1'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                               placeholder="123 Main Street">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apartment, suite, etc.</label>
                        <input type="text" name="address_line2"
                               value="<?= htmlspecialchars($address['address_line2'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                               placeholder="Apt 4B, Unit 12, etc.">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <input type="text" name="city" required
                                   value="<?= htmlspecialchars($address['city'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Cape Town">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <input type="text" name="province"
                                   value="<?= htmlspecialchars($address['province'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Western Cape">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                            <input type="text" name="postal_code" required
                                   value="<?= htmlspecialchars($address['postal_code'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                   placeholder="8001">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <select name="country" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500">
                                <option value="South Africa" <?= ($address['country'] ?? 'South Africa') === 'South Africa' ? 'selected' : '' ?>>South Africa</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address Label</label>
                        <input type="text" name="label"
                               value="<?= htmlspecialchars($address['label'] ?? 'Home') ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                               placeholder="Home, Office, etc.">
                        <p class="text-sm text-gray-500 mt-1">Give this address a memorable name</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address Type</label>
                        <select name="address_type" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500">
                            <option value="residential" <?= ($address['address_type'] ?? 'residential') === 'residential' ? 'selected' : '' ?>>Residential</option>
                            <option value="business" <?= ($address['address_type'] ?? 'residential') === 'business' ? 'selected' : '' ?>>Business</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special delivery instructions</label>
                        <textarea name="delivery_instructions" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500"
                                  placeholder="e.g., Ring doorbell twice, leave at front door, security code is 1234..."><?= htmlspecialchars($address['delivery_instructions'] ?? '') ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">Optional: Provide specific delivery instructions</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="default_for_shipping" id="default_for_shipping" <?= $address['default_for_shipping'] ? 'checked' : '' ?> class="text-green-600 focus:ring-green-500 rounded">
                        <span class="ml-2 text-sm text-gray-700">Set as default shipping address</span>
                    </div>

                    <div class="flex space-x-4 pt-4 border-t">
                        <a href="<?= userUrl('address-book/') ?>" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50 font-medium text-center">
                            Cancel
                        </a>
                        <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-medium">
                            Update Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
