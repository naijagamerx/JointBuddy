<?php
// Setup admin error handling
require_once __DIR__ . '/../../../includes/admin_error_catcher.php';

// Enable Whoops for debugging
require_once __DIR__ . '/../../../includes/whoops_handler.php';
setupWhoops();

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get payment method ID from URL path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');
$pathParts = explode('/', $path);
$editIndex = array_search('edit', $pathParts);
$methodId = 0;

if ($editIndex !== false && isset($pathParts[$editIndex + 1])) {
    $methodId = (int)$pathParts[$editIndex + 1];
} else {
    $methodId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if ($methodId <= 0) {
    $_SESSION['error'] = 'Invalid payment method ID';
    redirect('/admin/payment-methods/');
}

// Get payment method details
$method = null;
$customFields = [];
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM payment_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        $method = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$method) {
            $_SESSION['error'] = 'Payment method not found';
            redirect('/admin/payment-methods/');
        }

        // Get custom fields if this is a manual payment method
        if ($method['is_manual'] == 1) {
            $fieldStmt = $db->prepare("
                SELECT field_name, field_value, sort_order
                FROM payment_method_fields
                WHERE payment_method_id = ?
                ORDER BY sort_order ASC, id ASC
            ");
            $fieldStmt->execute([$methodId]);
            $customFields = $fieldStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error getting payment method: " . $e->getMessage());
        $_SESSION['error'] = 'Error loading payment method';
        redirect('/admin/payment-methods/');
    }
}

$message = '';
$error = '';

// Handle form submission
if ($_POST && $adminAuth && $db) {
    try {
        $name = trim($_POST['name']);
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($name)) {
            throw new Exception('Name is required');
        }

        // Handle manual payment method
        if ($method['is_manual'] == 1) {
            $manualType = trim($_POST['manual_type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color'] ?? '#6B7280');
            $bankName = trim($_POST['bank_name'] ?? '');
            $bankAccountName = trim($_POST['bank_account_name'] ?? '');
            $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
            $bankBranchCode = trim($_POST['bank_branch_code'] ?? '');

            // Handle QR code image upload for cryptocurrency
            $qrCodePath = $method['qr_code_path'] ?? '';
            if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../assets/images/payment-methods/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($_FILES['qr_code_image']['type'], $allowedTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, WebP, and GIF allowed.');
                }

                if ($_FILES['qr_code_image']['size'] > 2 * 1024 * 1024) {
                    throw new Exception('File size exceeds 2MB limit.');
                }

                $ext = pathinfo($_FILES['qr_code_image']['name'], PATHINFO_EXTENSION);
                $filename = 'qr_' . $methodId . '_' . time() . '_' . uniqid() . '.' . $ext;

                if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $uploadDir . $filename)) {
                    // Delete old QR code if exists
                    if (!empty($method['qr_code_path'])) {
                        $oldPath = __DIR__ . '/../../../assets/' . $method['qr_code_path'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $qrCodePath = 'assets/images/payment-methods/' . $filename;
                }
            }

            $type = 'manual_custom';
            if ($manualType === 'bank') {
                $type = 'bank_transfer';
            } elseif ($manualType === 'crypto') {
                $type = 'crypto';
            }

            $stmt = $db->prepare("
                UPDATE payment_methods SET
                    name = ?, type = ?, description = ?, manual_type = ?,
                    bank_name = ?, bank_account_name = ?, bank_account_number = ?, bank_branch_code = ?,
                    qr_code_path = ?, color = ?, active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $description, $manualType, $bankName, $bankAccountName, $bankAccountNumber, $bankBranchCode, $qrCodePath, $color, $active, $methodId]);

            // Delete existing custom fields
            $db->prepare("DELETE FROM payment_method_fields WHERE payment_method_id = ?")->execute([$methodId]);

            // Insert new custom fields
            $fieldNames = $_POST['field_name'] ?? [];
            $fieldValues = $_POST['field_value'] ?? [];

            if (is_array($fieldNames) && is_array($fieldValues)) {
                $fieldStmt = $db->prepare("
                    INSERT INTO payment_method_fields (payment_method_id, field_name, field_value, sort_order)
                    VALUES (?, ?, ?, ?)
                ");

                $sort = 0;
                $count = min(count($fieldNames), count($fieldValues));
                for ($i = 0; $i < $count; $i++) {
                    $fieldName = trim($fieldNames[$i]);
                    $fieldValue = trim($fieldValues[$i]);
                    if ($fieldName !== '' || $fieldValue !== '') {
                        $fieldStmt->execute([$methodId, $fieldName, $fieldValue, $sort]);
                        $sort++;
                    }
                }
            }
        } else {
            // Handle automatic payment method
            $description = trim($_POST['description'] ?? '');
            $type = trim($_POST['type']);
            $config = trim($_POST['config'] ?? '');

            if (empty($type)) {
                throw new Exception('Type is required');
            }

            $stmt = $db->prepare("
                UPDATE payment_methods SET
                    name = ?, description = ?, type = ?, config = ?, active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $type, $config, $active, $methodId]);
        }

        $_SESSION['success'] = 'Payment method updated successfully!';
        redirect('/admin/payment-methods/');
    } catch (Exception $e) {
        $error = AppError::handleDatabaseError($e, 'Error updating payment method');
    }
}

// Display success/error messages
$messageHtml = '';
if ($message) {
    $messageHtml = adminAlert('success', $message);
} elseif ($error) {
    $messageHtml = adminAlert('error', $error);
}

// Generate edit form content
$isManual = ($method['is_manual'] ?? 0) == 1;

$content = '
<div class="max-w-7xl mx-auto">
    ' . $messageHtml . '

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Payment Method</h1>
            <p class="text-gray-600 mt-1">Update payment method details</p>
        </div>
        <a href="' . adminUrl('/payment-methods/') . '" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Payment Methods
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        ' . csrf_field() . '
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">' . ($isManual ? 'Manual Payment Method' : 'Payment Method Information') . '</h2>
                ' . ($isManual ? '<p class="text-sm text-gray-500 mt-1">Bank transfer, cryptocurrency, or other manual payments.</p>' : '') . '
            </div>
            <div class="px-6 py-6 space-y-6">';

if ($isManual) {
    // Manual payment method form
    $content .= '
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required value="' . htmlspecialchars($method['name'] ?? '') . '" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., Bank Transfer (FNB)">
                    <p class="text-xs text-gray-500 mt-1">Display name shown to customers at checkout.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                    <select name="manual_type" required class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm">
                        <option value="">Select Type</option>
                        <option value="bank"' . (($method['manual_type'] ?? '') === 'bank' ? ' selected' : '') . '>Bank</option>
                        <option value="crypto"' . (($method['manual_type'] ?? '') === 'crypto' ? ' selected' : '') . '>Cryptocurrency</option>
                        <option value="custom"' . (($method['manual_type'] ?? '') === 'custom' ? ' selected' : '') . '>Custom</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Bank and cryptocurrency will show dedicated instructions on checkout.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                    <div class="flex flex-wrap gap-3 mt-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#3B82F6" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#3B82F6') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-blue-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-blue-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#10B981" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#10B981') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-green-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-green-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#8B5CF6" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#8B5CF6') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-purple-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-purple-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#F97316" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#F97316') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-orange-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-orange-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#EC4899" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#EC4899') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-pink-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-pink-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#14B8A6" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#14B8A6') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-teal-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-teal-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#F59E0B" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#F59E0B') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-amber-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-amber-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#6B7280" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#6B7280') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-gray-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-gray-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#06B6D4" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#06B6D4') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-cyan-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-cyan-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#6366F1" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#6366F1') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-indigo-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-indigo-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="#84CC16" class="sr-only peer"' . ((($method['color'] ?? '#6B7280') === '#84CC16') ? ' checked' : '') . '>
                            <div class="w-8 h-8 rounded-full bg-lime-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-lime-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Choose a color to identify this payment method in the list.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 placeholder-gray-300 placeholder-opacity-60 sm:text-sm" placeholder="Short description of how this manual payment works">' . htmlspecialchars($method['description'] ?? '') . '</textarea>
                </div>

                <!-- Banking Details for Invoice -->
                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fas fa-university mr-1 text-gray-500"></i>
                        Banking Details (for Invoice)
                    </h4>
                    <p class="text-xs text-gray-500 mb-4">
                        These details will be displayed on the invoice when this payment method is selected.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="' . htmlspecialchars($method['bank_name'] ?? '') . '" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., First National Bank">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Account Name</label>
                            <input type="text" name="bank_account_name" value="' . htmlspecialchars($method['bank_account_name'] ?? '') . '" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., Your Company Name">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Account Number</label>
                            <input type="text" name="bank_account_number" value="' . htmlspecialchars($method['bank_account_number'] ?? '') . '" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., 1234567890">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Branch Code</label>
                            <input type="text" name="bank_branch_code" value="' . htmlspecialchars($method['bank_branch_code'] ?? '') . '" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., 250655">
                        </div>
                    </div>
                </div>

                <!-- QR Code Upload for Cryptocurrency -->
                <div id="qr-code-upload-section" class="border-t border-gray-200 pt-4" style="display: ' . (($method['manual_type'] ?? '') === 'crypto' ? 'block' : 'none') . ';">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fas fa-qrcode mr-1 text-gray-500"></i>
                        Cryptocurrency QR Code (Optional)
                    </h4>
                    <p class="text-xs text-gray-500 mb-4">
                        Upload a QR code image that customers can scan to pay. This will be displayed on the invoice.
                    </p>
                    <div class="flex items-start space-x-4">
                        <div class="flex-1">
                            <input type="file" name="qr_code_image" accept="image/*"
                                   class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm"
                                   onchange="previewQRCode(this)">
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, WebP, GIF (Max 2MB)</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div id="qr-preview" class="w-32 h-32 bg-gray-100 border border-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                                ' . (!empty($method['qr_code_path']) ? '<img src="' . assetUrl($method['qr_code_path']) . '" alt="QR Code" class="w-full h-full object-cover">' : '<span class="text-gray-400 text-xs text-center px-2">No QR code</span>') . '
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Custom fields</label>
                        <button type="button" id="add-custom-field" class="inline-flex items-center px-3 py-1.5 border border-green-600 text-sm leading-4 font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none">
                            <i class="fas fa-plus mr-2"></i>Add field
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">
                        Add additional key-value pairs for checkout display (e.g., Wallet Address for crypto).
                    </p>
                    <div id="custom-fields-container" class="space-y-3"></div>
                </div>';
} else {
    // Automatic payment method form (original)
    $content .= '
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required value="' . htmlspecialchars($method['name'] ?? '') . '" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Credit Card via PayFast">
                    <p class="text-xs text-gray-500 mt-1">Display name for this payment method</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                    <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Type</option>
                        <option value="payfast"' . (($method['type'] ?? '') === 'payfast' ? ' selected' : '') . '>PayFast</option>
                        <option value="bank_transfer"' . (($method['type'] ?? '') === 'bank_transfer' ? ' selected' : '') . '>Bank Transfer</option>
                        <option value="cash_on_delivery"' . (($method['type'] ?? '') === 'cash_on_delivery' ? ' selected' : '') . '>Cash on Delivery</option>
                        <option value="credit_card"' . (($method['type'] ?? '') === 'credit_card' ? ' selected' : '') . '>Credit Card</option>
                        <option value="eft"' . (($method['type'] ?? '') === 'eft' ? ' selected' : '') . '>EFT</option>
                        <option value="crypto"' . (($method['type'] ?? '') === 'crypto' ? ' selected' : '') . '>Cryptocurrency</option>
                        <option value="mobile_money"' . (($method['type'] ?? '') === 'mobile_money' ? ' selected' : '') . '>Mobile Money</option>
                        <option value="other"' . (($method['type'] ?? '') === 'other' ? ' selected' : '') . '>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Brief description of this payment method">' . htmlspecialchars($method['description'] ?? '') . '</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Configuration (JSON)</label>
                    <textarea name="config" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder=\'{"merchant_id": "12345", "key": "xyz", "endpoint": "sandbox"}\'>' . htmlspecialchars($method['config'] ?? '') . '</textarea>
                    <p class="text-xs text-gray-500 mt-1">Enter configuration settings as JSON. This will vary by payment provider.</p>
                </div>';
}

$content .= '
                <div class="flex items-center">
                    <input type="checkbox" name="active" value="1"' . (($method['active'] ?? 0) == 1 ? ' checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-400">
                    <label class="ml-2 text-sm text-gray-700">
                        <strong>Active</strong><br>
                        <span class="text-gray-500">Payment method is available to customers</span>
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="' . adminUrl('/payment-methods/') . '" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="fas fa-save mr-2"></i>Update Payment Method
                </button>
            </div>
        </div>
    </form>
</div>';

// Add JavaScript for custom fields (only for manual payment methods)
if ($isManual) {
    $content .= '
<script>
(function() {
    var container = document.getElementById("custom-fields-container");
    var addButton = document.getElementById("add-custom-field");

    function addFieldRow(name, value) {
        var row = document.createElement("div");
        row.className = "flex flex-col md:flex-row md:items-end md:space-x-4 space-y-2 md:space-y-0";

        row.innerHTML =
            \'<div class="flex-1">\'+
                \'<label class="block text-xs font-medium text-gray-600 mb-1">Field name</label>\'+
                \'<input type="text" name="field_name[]" value="\' + (name || "") + \'" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., Bank Name">\'+
            \'</div>\'+
            \'<div class="flex-1">\'+
                \'<label class="block text-xs font-medium text-gray-600 mb-1">Field value</label>\'+
                \'<input type="text" name="field_value[]" value="\' + (value || "") + \'" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., FNB">\'+
            \'</div>\'+
            \'<div class="md:w-auto">\'+
                \'<button type="button" class="remove-custom-field mt-2 md:mt-0 inline-flex items-center px-3 py-2 border border-red-500 text-sm leading-4 font-medium rounded-md text-red-600 bg-white hover:bg-red-50 focus:outline-none">\'+
                    \'<i class="fas fa-trash mr-1"></i>Remove\'+
                \'</button>\'+
            \'</div>\';

        container.appendChild(row);
    }

    if (addButton) {
        addButton.addEventListener("click", function(e) {
            e.preventDefault();
            addFieldRow("", "");
        });
    }

    if (container) {
        container.addEventListener("click", function(e) {
            var target = e.target;
            if (target.classList.contains("remove-custom-field") || target.closest(".remove-custom-field")) {
                var button = target.closest(".remove-custom-field");
                var row = button.closest("div.flex");
                if (row && row.parentNode === container) {
                    container.removeChild(row);
                }
            }
        });
    }

    // Load existing fields
    var existingFields = ' . json_encode($customFields) . ';
    if (existingFields && existingFields.length > 0) {
        existingFields.forEach(function(field) {
            addFieldRow(field.field_name, field.field_value);
        });
    } else {
        // Add default fields if none exist
        addFieldRow("Bank Name", "");
        addFieldRow("Account Number", "");
    }

    // Toggle QR Code section based on payment type
    var manualTypeSelect = document.querySelector("select[name=\"manual_type\"]");
    var qrCodeSection = document.getElementById("qr-code-upload-section");

    function showHideQRCode() {
        if (manualTypeSelect && qrCodeSection) {
            if (manualTypeSelect.value === "crypto") {
                qrCodeSection.style.display = "block";
            } else {
                qrCodeSection.style.display = "none";
            }
        }
    }

    if (manualTypeSelect) {
        manualTypeSelect.addEventListener("change", showHideQRCode);
        // Trigger on page load in case crypto is already selected
        showHideQRCode();
    }

    function previewQRCode(input) {
        var preview = document.getElementById("qr-preview");
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = \'<img src="\' + e.target.result + \'" alt="QR Code" class="w-full h-full object-cover">\';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
})();
</script>';
}

// Render the page with sidebar
echo adminSidebarWrapper('Edit Payment Method', $content, 'payment-methods');
?>
