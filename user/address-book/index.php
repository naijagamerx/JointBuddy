<?php
/**
 * Address Book Page
 *
 * User address management with database integration
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication
AuthMiddleware::requireUser();

// Get current user
$currentUser = AuthMiddleware::getCurrentUser();
$message = '';
$messageType = '';

// Load address service
require_once INCLUDES_PATH . '/user_addresses_service.php';

// Get database
$db = Services::db();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    $action = $_POST['address_action'] ?? '';

    try {
        switch ($action) {
            case 'add':
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

                $result = saveUserAddress($db, $currentUser['id'], $addressData);
                if ($result) {
                    $message = 'Address added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add address. Please check your information.';
                    $messageType = 'error';
                }
                break;

            case 'edit':
                $addressId = intval($_POST['address_id'] ?? 0);
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
                } else {
                    $message = 'Failed to update address. Please check your information.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $addressId = intval($_POST['address_id'] ?? 0);
                $result = deleteUserAddress($db, $currentUser['id'], $addressId);
                if ($result) {
                    $message = 'Address deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete address.';
                    $messageType = 'error';
                }
                break;

            case 'set_default':
                $addressId = intval($_POST['address_id'] ?? 0);
                $result = setDefaultAddress($db, $currentUser['id'], $addressId);
                if ($result) {
                    $message = 'Default address updated!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to set default address.';
                    $messageType = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch user addresses and stats
$addresses = getUserAddresses($db, $currentUser['id']);
$stats = getAddressStats($db, $currentUser['id']);
$defaultAddress = $stats['default_address'];

// Get edit address if specified
$editAddress = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    foreach ($addresses as $addr) {
        if ($addr['id'] == $editId) {
            $editAddress = $addr;
            break;
        }
    }
}

$pageTitle = "Address Book";
$currentPage = "address-book";

// Include universal components
include __DIR__ . '/../components/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-blue-100 text-sm">Manage your delivery addresses and preferred locations</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-map-marker-alt text-2xl"></i>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> px-4 py-3 rounded-lg mb-6 border">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Universal Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Address Book -->
        <div class="lg:w-3/4">
            <!-- Address Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['total_addresses'] ?></p>
                            <p class="text-gray-600">Total Addresses</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-semibold text-gray-900"><?= $defaultAddress ? '1' : '0' ?></p>
                            <p class="text-gray-600">Default Address</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['delivery_zones'] ?></p>
                            <p class="text-gray-600">Delivery Zones</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add New Address Button -->
            <div class="mb-8">
                <a href="<?= userUrl('address-book/add/') ?>" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition flex items-center inline-block">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add New Address
                </a>
            </div>

            <!-- Addresses List -->
            <div class="space-y-6">
                <?php if (empty($addresses)): ?>
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-map-marker-alt text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No addresses yet</h3>
                        <p class="text-gray-600 mb-4">Add your first address to get started</p>
                        <a href="<?= userUrl('address-book/add/') ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-block">
                            Add Address
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="bg-white rounded-lg shadow <?= $address['default_for_shipping'] ? 'border-2 border-green-200' : 'border border-gray-200' ?>">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-4">
                                            <h3 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($address['label']) ?></h3>
                                            <?php if ($address['default_for_shipping']): ?>
                                                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="space-y-2 text-gray-700">
                                            <p class="font-medium"><?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?></p>
                                            <p><?= htmlspecialchars($address['address_line1']) ?></p>
                                            <?php if ($address['address_line2']): ?>
                                                <p><?= htmlspecialchars($address['address_line2']) ?></p>
                                            <?php endif; ?>
                                            <p>
                                                <?= htmlspecialchars($address['city']) ?>
                                                <?php if ($address['province']): ?>
                                                    , <?= htmlspecialchars($address['province']) ?>
                                                <?php endif; ?>
                                                <?php if ($address['postal_code']): ?>
                                                    , <?= htmlspecialchars($address['postal_code']) ?>
                                                <?php endif; ?>
                                            </p>
                                            <p><?= htmlspecialchars($address['country']) ?></p>
                                            <p class="mt-3">
                                                <span class="font-medium">Phone:</span> <?= htmlspecialchars($address['phone']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="<?= userUrl('address-book/edit/?id=' . $address['id']) ?>" class="text-green-600 hover:text-green-700 font-medium">Edit</a>
                                        <button onclick="confirmDelete(<?= $address['id'] ?>, '<?= htmlspecialchars($address['label'], ENT_QUOTES) ?>')" class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                        <?php if (!$address['default_for_shipping']): ?>
                                            <button onclick="setDefault(<?= $address['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium">Set as Default</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($address['delivery_instructions']): ?>
                                    <div class="mt-6 pt-6 border-t">
                                        <h4 class="font-medium text-gray-900 mb-2">Delivery Instructions</h4>
                                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($address['delivery_instructions']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Delivery Zones Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-blue-800">Delivery Coverage</h3>
                        <p class="text-blue-700 text-sm mt-1">We currently deliver to multiple areas. Enter your postal code at checkout to confirm delivery availability for your address.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(addressId, label) {
    if (confirm('Are you sure you want to delete this address?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="address_action" value="delete">
            <input type="hidden" name="address_id" value="${addressId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function setDefault(addressId) {
    if (confirm('Set this as your default shipping address?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="address_action" value="set_default">
            <input type="hidden" name="address_id" value="${addressId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
