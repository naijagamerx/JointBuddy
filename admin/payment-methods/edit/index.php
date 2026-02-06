<?php
// Setup admin error handling
require_once __DIR__ . '/../../../includes/admin_error_catcher.php';

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
            $bankName = trim($_POST['bank_name'] ?? '');
            $bankAccountName = trim($_POST['bank_account_name'] ?? '');
            $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
            $bankBranchCode = trim($_POST['bank_branch_code'] ?? '');

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
                    active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $description, $manualType, $bankName, $bankAccountName, $bankAccountNumber, $bankBranchCode, $active, $methodId]);

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

    <form method="POST">
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
})();
</script>';
}

// Render the page with sidebar
echo adminSidebarWrapper('Edit Payment Method', $content, 'payment-methods');
?>
