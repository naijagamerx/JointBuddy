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

// Get database connection from services
$db = Services::db();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    try {
        $name = trim($_POST['name'] ?? '');
        $manualType = trim($_POST['manual_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $color = trim($_POST['color'] ?? '#6B7280');

        // Handle QR code image upload for cryptocurrency
        $qrCodePath = '';
        if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === UPLOAD_ERR_OK && $manualType === 'crypto') {
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
            $filename = 'qr_temp_' . time() . '_' . uniqid() . '.' . $ext;

            if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $uploadDir . $filename)) {
                $qrCodePath = 'assets/images/payment-methods/' . $filename;
            }
        }

        $fieldNames = $_POST['field_name'] ?? [];
        $fieldValues = $_POST['field_value'] ?? [];
        $fields = [];

        if (is_array($fieldNames) && is_array($fieldValues)) {
            $count = min(count($fieldNames), count($fieldValues));
            for ($i = 0; $i < $count; $i++) {
                $fields[] = [
                    'name' => $fieldNames[$i],
                    'value' => $fieldValues[$i] ?? '',
                ];
            }
        }

        $methodId = createManualPaymentMethod($db, [
            'name' => $name,
            'manual_type' => $manualType,
            'description' => $description,
            'active' => $active,
            'fields' => $fields,
            'qr_code_path' => $qrCodePath,
            'color' => $color,
        ]);

        if ($methodId > 0) {
            $_SESSION['success'] = 'Manual payment method created successfully';
            redirect('/admin/payment-methods/');
        } else {
            throw new Exception('Failed to create payment method');
        }
    } catch (Exception $e) {
        $error = AppError::handleDatabaseError($e, 'Error creating payment method');
    }
}

// Display success/error messages
$messageHtml = '';
if ($message) {
    $messageHtml = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . htmlspecialchars($message) . '</p>
            </div>
        </div>
    </div>';
} elseif ($error) {
    $messageHtml = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . htmlspecialchars($error) . '</p>
            </div>
        </div>
    </div>';
}

// Generate add form content
$content = '
<div class="max-w-7xl mx-auto">
    ' . $messageHtml . '

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Add Payment Method</h1>
            <p class="text-gray-600 mt-1">Configure manual payment options such as bank transfer or cryptocurrency</p>
        </div>
        <a href="' . adminUrl('/payment-methods/') . '" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Payment Methods
        </a>
    </div>

    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button" class="payment-tab-link border-b-2 border-green-600 text-green-600 whitespace-nowrap py-4 px-1 text-sm font-medium" data-target="manual-tab">
                Manual payment methods
            </button>
            <button type="button" class="payment-tab-link border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 text-sm font-medium" data-target="automatic-tab">
                Automatic payment methods
            </button>
        </nav>
    </div>

    <div id="manual-tab">
        <form method="POST" enctype="multipart/form-data">
            ' . csrf_field() . '
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Manual payment method</h2>
                    <p class="text-sm text-gray-500 mt-1">Use this for bank transfer, cryptocurrency, or other manual payments.</p>
                </div>
                <div class="px-6 py-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm" placeholder="e.g., Bank Transfer (FNB)">
                        <p class="text-xs text-gray-500 mt-1">Display name shown to customers at checkout.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                        <select name="manual_type" required class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm">
                            <option value="">Select Type</option>
                            <option value="bank">Bank</option>
                            <option value="crypto">Cryptocurrency</option>
                            <option value="custom">Custom</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Bank and cryptocurrency will show dedicated instructions on checkout.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <div class="flex flex-wrap gap-3 mt-2">
                            ' . $colorOptionsHtml = '
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#3B82F6" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-blue-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-blue-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#10B981" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-green-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-green-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#8B5CF6" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-purple-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-purple-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#F97316" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-orange-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-orange-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#EC4899" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-pink-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-pink-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#14B8A6" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-teal-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-teal-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#F59E0B" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-amber-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-amber-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#6B7280" class="sr-only peer" checked>
                                <div class="w-8 h-8 rounded-full bg-gray-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-gray-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#06B6D4" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-cyan-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-cyan-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#6366F1" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-indigo-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-indigo-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="#84CC16" class="sr-only peer">
                                <div class="w-8 h-8 rounded-full bg-lime-500 border-2 border-transparent peer-checked:ring-2 peer-checked:ring-lime-300 peer-checked:ring-offset-2 hover:scale-110 transition-transform"></div>
                            </label>
                            ' . '
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Choose a color to identify this payment method in the list.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-md border border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 placeholder-gray-300 placeholder-opacity-60 sm:text-sm" placeholder="Short description of how this manual payment works"></textarea>
                    </div>

                    <!-- QR Code Upload for Cryptocurrency -->
                    <div id="qr-code-upload-section" class="border-t border-gray-200 pt-4" style="display: none;">
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
                                <div id="qr-preview" class="w-32 h-32 bg-gray-100 border border-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-400 text-xs text-center px-2">No QR code</span>
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
                            Add key-value pairs such as Bank Name, Account Number, Branch Code or Wallet Address.
                        </p>
                        <div id="custom-fields-container" class="space-y-3"></div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="active" value="1" checked class="rounded border-gray-300 text-green-600 focus:ring-green-400">
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
                        <i class="fas fa-plus mr-2"></i>Create Manual Payment Method
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="automatic-tab" class="hidden">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Automatic payment methods</h2>
                <p class="text-sm text-gray-500 mt-1">Credit card gateways such as PayFast and Ozow will be configured here later.</p>
            </div>
            <div class="px-6 py-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Automatic payment providers will allow instant card and instant EFT payments. This section will be implemented after manual methods are finalised.
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                    <li>PayFast credit and debit card payments</li>
                    <li>Ozow instant EFT</li>
                    <li>Other third-party gateways</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var tabLinks = document.querySelectorAll(".payment-tab-link");
    var manualTab = document.getElementById("manual-tab");
    var automaticTab = document.getElementById("automatic-tab");

    tabLinks.forEach(function(link) {
        link.addEventListener("click", function() {
            tabLinks.forEach(function(other) {
                other.classList.remove("border-green-600", "text-green-600");
                other.classList.add("border-transparent", "text-gray-500");
            });

            this.classList.remove("border-transparent", "text-gray-500");
            this.classList.add("border-green-600", "text-green-600");

            var target = this.getAttribute("data-target");
            if (target === "manual-tab") {
                manualTab.classList.remove("hidden");
                automaticTab.classList.add("hidden");
            } else {
                automaticTab.classList.remove("hidden");
                manualTab.classList.add("hidden");
            }
        });
    });

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

    if (container && container.children.length === 0) {
        addFieldRow("Bank Name", "");
        addFieldRow("Account Number", "");
    }

    // Toggle QR Code section based on payment type
    var manualTypeSelect = document.querySelector("select[name=\\"manual_type\\"]");
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

// Render the page with sidebar
echo adminSidebarWrapper('Add Payment Method', $content, 'payment-methods');
?>
