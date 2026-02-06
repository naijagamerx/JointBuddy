<?php
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Setup Whoops for beautiful error pages (if debug mode is enabled)
if (isset($_GET['debug']) || isset($_GET['whoops'])) {
    require_once __DIR__ . '/../../includes/whoops_handler.php';
}

// Setup admin error handling
require_once __DIR__ . '/../../includes/admin_error_catcher.php';

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    try {
        // Generate slug from name
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
        
        // Check if slug exists
        $stmt = $db->prepare("SELECT id FROM products WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->rowCount() > 0) {
            $slug .= '-' . time();
        }

        // Handle tags - convert to JSON
        $tags_raw = $_POST['tags'] ?? '';
        if (empty($tags_raw)) {
            $tags = '[]';
        } else {
            if (strpos($tags_raw, ',') !== false) {
                $tags_array = array_map('trim', explode(',', $tags_raw));
                $tags = json_encode($tags_array);
            } else {
                $tags = json_encode([$tags_raw]);
            }
        }

        // Handle custom fields - store as JSON
        $custom_fields = $_POST['custom_fields'] ?? '{}';
        // Validate and sanitize JSON
        if (empty($custom_fields) || $custom_fields === '[]' || $custom_fields === '{}') {
            $custom_fields = '{}';
        }

        // Handle product policies - multi-line text
        $product_policies = $_POST['product_policies'] ?? '';

        $stmt = $db->prepare("INSERT INTO products (name, slug, description, short_description, price, sale_price, cost, sku, stock, weight, dimensions, category, tags, images, active, featured, meta_title, meta_description, custom_fields, product_policies, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

        $stmt->execute([
            $_POST['name'],
            $slug,
            $_POST['description'],
            $_POST['short_description'],
            $_POST['price'],
            $_POST['sale_price'] ?: null,
            $_POST['cost'] ?: null,
            $_POST['sku'],
            $_POST['stock'],
            $_POST['weight'] ?: null,
            $_POST['dimensions'] ?: null,
            $_POST['category'],
            $tags,
            $_POST['images'],
            $_POST['active'] ?? 0,
            $_POST['featured'] ?? 0,
            $_POST['meta_title'] ?: $_POST['name'],
            $_POST['meta_description'] ?: $_POST['short_description'],
            $custom_fields,
            $product_policies
        ]);
        
        $_SESSION['success'] = 'Product created successfully!';
        redirect(adminUrl('/products/'));
    } catch (Exception $e) {
        $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error creating product');
    }
}

// Fetch categories from database
$categories = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        AppError::handleDatabaseError($e, 'Error fetching categories');
    }
}

// Generate add product content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
        <p class="text-gray-600 mt-1">Create a new product for your store</p>
    </div>

    <!-- Alert Messages -->';

if (isset($_SESSION['success'])) {
    $content .= adminAlert('success', $_SESSION['success']);
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $content .= adminAlert('error', $_SESSION['error']);
    unset($_SESSION['error']);
}

$content .= '
    <!-- Product Form -->
    <form method="POST" class="space-y-8">
        ' . csrf_field() . '
        <!-- Basic Information -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Basic Information</h2>
                <p class="text-sm text-gray-600">Essential product details</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                ' . adminFormInput('Product Name *', 'name', $_POST['name'] ?? '', 'text', true, 'Enter product name') . '
                ' . adminFormTextarea('Short Description', 'short_description', $_POST['short_description'] ?? '', 2, false, 'Brief product description for listings') . '
                ' . adminFormTextarea('Full Description', 'description', $_POST['description'] ?? '', 6, false, 'Detailed product description') . '
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    ' . adminFormInput('SKU *', 'sku', $_POST['sku'] ?? '', 'text', true, 'Product SKU or model number') . '
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Select a category</option>';
foreach ($categories as $cat) {
    $content .= '<option value="' . htmlspecialchars($cat['name']) . '"' . (($_POST['category'] ?? '') === $cat['name'] ? ' selected' : '') . '>' . htmlspecialchars($cat['name']) . '</option>';
}
$content .= '
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Choose from existing categories</p>
                    </div>
                </div>

                ' . adminFormInput('Tags', 'tags', $_POST['tags'] ?? '', 'text', false, 'Comma-separated tags (e.g., cannabis, flower, indica)') . '
            </div>
        </div>

        <!-- Pricing -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Pricing</h2>
                <p class="text-sm text-gray-600">Set product prices and costs</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    ' . adminFormInput('Selling Price (R) *', 'price', $_POST['price'] ?? '', 'number', true, '0.00', ['step' => '0.01', 'min' => '0']) . '
                    ' . adminFormInput('Sale Price (R)', 'sale_price', $_POST['sale_price'] ?? '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '
                    ' . adminFormInput('Cost Price (R)', 'cost', $_POST['cost'] ?? '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '
                </div>
            </div>
        </div>

        <!-- Inventory -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Inventory</h2>
                <p class="text-sm text-gray-600">Manage stock and availability</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    ' . adminFormInput('Stock Quantity *', 'stock', $_POST['stock'] ?? '0', 'number', true, '0', ['min' => '0']) . '
                    ' . adminFormInput('Weight (g)', 'weight', $_POST['weight'] ?? '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '
                    ' . adminFormInput('Dimensions (L×W×H cm)', 'dimensions', $_POST['dimensions'] ?? '', 'text', false, 'e.g., 10×5×2') . '
                </div>
            </div>
        </div>

        <!-- Media -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Media</h2>
                <p class="text-sm text-gray-600">Product images and media</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                <input type="hidden" name="images" id="field_images" value="' . htmlspecialchars($_POST['images'] ?? '') . '">
                
                <!-- Image Preview Grid -->
                <div id="imagePreviewContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4"></div>

                <!-- Dropzone -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors" id="dropZone">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                    <p class="text-sm text-gray-600 mb-2">Upload product images (JPG, PNG, WebP)</p>
                    <p class="text-xs text-gray-500">Drag and drop images here or click to browse</p>
                    <input type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" id="imageUpload">
                    <button type="button" onclick="document.getElementById(\'imageUpload\').click()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Choose Files
                    </button>
                    <div id="uploadProgress" class="hidden mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Uploading...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Product Settings</h2>
                <p class="text-sm text-gray-600">Configure product visibility and options</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="active" value="1" ' . ((!isset($_POST['name']) || isset($_POST['active'])) ? 'checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-700">
                                <strong>Active</strong><br>
                                <span class="text-gray-500">Product is available for purchase</span>
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="featured" value="1" ' . (isset($_POST['featured']) ? 'checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-700">
                                <strong>Featured Product</strong><br>
                                <span class="text-gray-500">Show in featured products section</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Information (Custom Fields) -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Product Information</h2>
                <p class="text-sm text-gray-600">Add custom fields for detailed product specifications</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                <input type="hidden" name="custom_fields" id="field_custom_fields" value="' . htmlspecialchars($_POST['custom_fields'] ?? '') . '">

                <!-- Custom Fields Container -->
                <div id="customFieldsContainer" class="space-y-4">
                    <!-- Dynamic fields will be added here -->
                </div>

                <!-- Add Field Button -->
                <button type="button" onclick="addCustomField()" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Custom Field
                </button>

                <!-- Pre-defined Common Fields -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Quick Add Common Fields</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button type="button" onclick="addPredefinedField(\'Categories\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Categories</button>
                        <button type="button" onclick="addPredefinedField(\'Warranty\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Warranty</button>
                        <button type="button" onclick="addPredefinedField(\'Part Number\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Part Number</button>
                        <button type="button" onclick="addPredefinedField(\'Model\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Model</button>
                        <button type="button" onclick="addPredefinedField(\'Barcode\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Barcode</button>
                        <button type="button" onclick="addPredefinedField(\'Colour Name\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Colour Name</button>
                        <button type="button" onclick="addPredefinedField(\'What&apos;s in the box\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">What&apos;s in the box</button>
                        <button type="button" onclick="addPredefinedField(\'Classification\')" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Classification</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Policies / Warranty Info -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Product Policies & Warranty</h2>
                <p class="text-sm text-gray-600">Add delivery, warranty, and return policy information (shown under stock on product page)</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                ' . adminFormTextarea('Policies & Warranty Text', 'product_policies', $_POST['product_policies'] ?? '', 6, false, 'Enter policy statements, one per line (e.g., Eligible for next-day delivery, 6-Month Limited Warranty, etc.)') . '

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Example Policy Lines:</h4>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                        <li>Eligible for next-day delivery or collection</li>
                        <li>Eligible for Cash on Delivery</li>
                        <li>Hassle-Free Exchanges & Returns for 30 Days</li>
                        <li>6-Month Limited Warranty</li>
                        <li>Free Installation Support</li>
                        <li>24/7 Customer Support</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">SEO Settings</h2>
                <p class="text-sm text-gray-600">Optimize for search engines</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    ' . adminFormInput('Meta Title', 'meta_title', $_POST['meta_title'] ?? '', 'text', false, 'SEO title for search engines') . '
                    ' . adminFormInput('Meta Description', 'meta_description', $_POST['meta_description'] ?? '', 'text', false, 'SEO description for search engines') . '
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <a href="' . adminUrl('/products/') . '" class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Create Product
            </button>
        </div>
    </form>
</div>

<script>
// Auto-generate SKU from product name
document.getElementById("field_name").addEventListener("input", function() {
    const name = this.value;
    const skuField = document.getElementById("field_sku");
    
    if (skuField && !skuField.value) {
        // Generate SKU from name (remove spaces, convert to uppercase, add random number)
        const baseSku = name.toUpperCase().replace(/[^A-Z0-9]/g, "").substring(0, 8);
        const randomNum = Math.floor(Math.random() * 9999).toString().padStart(4, "0");
        skuField.value = baseSku + randomNum;
    }
});

// Generate tags from name and description
document.getElementById("field_name").addEventListener("blur", function() {
    const name = this.value.toLowerCase();
    const description = document.getElementById("field_description").value.toLowerCase();
    const tagsField = document.getElementById("field_tags");
    
    if (tagsField && !tagsField.value) {
        // Extract relevant words as tags
        const allText = name + " " + description;
        const words = allText.split(/[^a-zA-Z0-9]+/).filter(word => 
            word.length > 3 && !["this", "that", "with", "from", "they", "have", "will", "been", "were", "said", "what"].includes(word)
        );
        const uniqueWords = [...new Set(words)].slice(0, 5);
        tagsField.value = uniqueWords.join(", ");
    }
});

// Image Upload Handler
const imageUpload = document.getElementById("imageUpload");
const dropZone = document.getElementById("dropZone");
const imagesField = document.getElementById("field_images");
const previewContainer = document.getElementById("imagePreviewContainer");

function renderPreviews() {
    if (!imagesField || !previewContainer) return;
    const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
    
    previewContainer.innerHTML = "";
    
    currentUrls.forEach((url, index) => {
        const div = document.createElement("div");
        div.className = "relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200";
        
        const img = document.createElement("img");
        img.src = url;
        img.className = "w-full h-full object-cover";
        
        // Main Image Label
        if (index === 0) {
            const badge = document.createElement("div");
            badge.className = "absolute top-2 left-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded shadow";
            badge.innerText = "Main Image";
            div.appendChild(badge);
        }
        
        // Remove Button
        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.className = "absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full shadow opacity-0 group-hover:opacity-100 transition-opacity";
        removeBtn.innerHTML = \'<i class="fas fa-times"></i>\';
        removeBtn.onclick = () => removeImage(index);
        
        div.appendChild(img);
        div.appendChild(removeBtn);
        previewContainer.appendChild(div);
    });
}

function removeImage(index) {
    const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
    currentUrls.splice(index, 1);
    imagesField.value = currentUrls.join(", ");
    renderPreviews();
}

if (imageUpload && dropZone) {
    imageUpload.addEventListener("change", handleFiles);

    // Drag and drop handlers
    dropZone.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropZone.classList.add("border-green-500");
    });

    dropZone.addEventListener("dragleave", (e) => {
        e.preventDefault();
        dropZone.classList.remove("border-green-500");
    });

    dropZone.addEventListener("drop", (e) => {
        e.preventDefault();
        dropZone.classList.remove("border-green-500");
        if (e.dataTransfer.files.length) {
            handleFiles({ target: { files: e.dataTransfer.files } });
        }
    });

    function handleFiles(e) {
        const files = e.target.files;
        if (!files.length) return;

        const progressBar = document.getElementById("uploadProgress");
        const progressFill = progressBar.querySelector("div");
        progressBar.classList.remove("hidden");
        progressFill.style.width = "0%";

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append("images[]", files[i]);
        }

        fetch("' . adminUrl('/products/upload_image.php') . '", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
                data.urls.forEach(url => {
                    currentUrls.push(url);
                });
                imagesField.value = currentUrls.join(", ");
                renderPreviews(); // Update visuals
                
                progressFill.style.width = "100%";
                setTimeout(() => progressBar.classList.add("hidden"), 1000);
            } else {
                alert("Upload failed: " + (data.message || "Unknown error"));
                progressBar.classList.add("hidden");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Upload failed. Please try again.");
            progressBar.classList.add("hidden");
        });
    }
}
// Initial render (if loading with data)
renderPreviews();
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Add Product', $content, 'products');
?>

<!-- Custom Fields JavaScript -->
<script>
// Custom Fields Management
let customFields = [];

function loadCustomFields() {
    const customFieldsInput = document.getElementById('field_custom_fields');
    const container = document.getElementById('customFieldsContainer');

    if (customFieldsInput && customFieldsInput.value) {
        try {
            customFields = JSON.parse(customFieldsInput.value);
        } catch (e) {
            customFields = [];
        }
    } else {
        customFields = [];
    }

    if (container) {
        container.innerHTML = '';
        customFields.forEach((field, index) => {
            addFieldToUI(field.label, field.value, index);
        });
    }
}

function addCustomField(label = '', value = '') {
    const container = document.getElementById('customFieldsContainer');
    const index = customFields.length;

    customFields.push({ label, value });
    addFieldToUI(label, value, index);
    updateCustomFieldsInput();
}

function addPredefinedField(fieldLabel) {
    addCustomField(fieldLabel, '');
}

function addFieldToUI(label, value, index) {
    const container = document.getElementById('customFieldsContainer');
    if (!container) return;

    const fieldDiv = document.createElement('div');
    fieldDiv.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4';
    fieldDiv.dataset.index = index;

    fieldDiv.innerHTML =
        '<div class="flex items-start space-x-3">' +
            '<div class="flex-1">' +
                '<label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>' +
                '<input type="text" value="' + label + '" onchange="updateCustomField(' + index + ', \'label\', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Warranty">' +
            '</div>' +
            '<div class="flex-1">' +
                '<label class="block text-sm font-medium text-gray-700 mb-1">Value</label>' +
                '<input type="text" value="' + value + '" onchange="updateCustomField(' + index + ', \'value\', this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Limited (6 months)">' +
            '</div>' +
            '<button type="button" onclick="removeCustomField(' + index + ')" class="mt-6 p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">' +
                '<i class="fas fa-trash"></i>' +
            '</button>' +
        '</div>';

    container.appendChild(fieldDiv);
}

function updateCustomField(index, key, value) {
    if (customFields[index]) {
        customFields[index][key] = value;
        updateCustomFieldsInput();
    }
}

function removeCustomField(index) {
    customFields.splice(index, 1);
    loadCustomFields();
    updateCustomFieldsInput();
}

function updateCustomFieldsInput() {
    const input = document.getElementById('field_custom_fields');
    if (input) {
        input.value = JSON.stringify(customFields);
    }
}

// Load custom fields when page is ready
document.addEventListener('DOMContentLoaded', function() {
    loadCustomFields();
});
</script>