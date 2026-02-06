<?php
// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Setup Whoops for beautiful error pages (if debug mode is enabled)
if (isset($_GET['debug']) || isset($_GET['whoops'])) {
    require_once __DIR__ . '/../../../includes/whoops_handler.php';
}

// Setup admin error handling
require_once __DIR__ . '/../../../includes/admin_error_catcher.php';

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get admin auth and database connection from services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Get product slug from URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Extract slug from /admin/products/edit/{slug}
$pathParts = explode('/', $path);
$productSlug = '';
$editIndex = array_search('edit', $pathParts);
if ($editIndex !== false && isset($pathParts[$editIndex + 1])) {
    $productSlug = $pathParts[$editIndex + 1];
} else {
    $productSlug = $_GET['slug'] ?? $_POST['slug'] ?? '';
}

// Fetch product data
$product = null;
if ($db && $productSlug) {
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE slug = ?");
        $stmt->execute([$productSlug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['error'] = 'Product not found';
            redirect('/admin/products/');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error loading product');
        redirect('/admin/products/');
    }
} elseif (!$productSlug) {
    $_SESSION['error'] = 'No product specified';
    redirect('/admin/products/');
}

// Handle form submission
if ($_POST && $adminAuth && $db && $product) {
    try {
        // Generate slug from name (only if name changed)
        $newSlug = $product['slug'];
        if ($_POST['name'] !== $product['name']) {
            $newSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));

            // Check if slug exists (excluding current product)
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $stmt->execute([$newSlug, $product['id']]);
            if ($stmt->rowCount() > 0) {
                $newSlug .= '-' . time();
            }
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
        if (empty($custom_fields) || $custom_fields === '[]' || $custom_fields === '{}') {
            $custom_fields = '{}';
        }

        // Handle product policies - multi-line text
        $product_policies = $_POST['product_policies'] ?? '';

        // Update product instead of insert
        $stmt = $db->prepare("UPDATE products SET name = ?, slug = ?, description = ?, short_description = ?, price = ?, sale_price = ?, cost = ?, sku = ?, stock = ?, weight = ?, dimensions = ?, category = ?, tags = ?, images = ?, active = ?, featured = ?, on_sale = ?, meta_title = ?, meta_description = ?, custom_fields = ?, product_policies = ?, updated_at = NOW() WHERE id = ?");

        $stmt->execute([
            $_POST['name'],
            $newSlug,
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
            $_POST['on_sale'] ?? 0,
            $_POST['meta_title'] ?: $_POST['name'],
            $_POST['meta_description'] ?: $_POST['short_description'],
            $custom_fields,
            $product_policies,
            $product['id']
        ]);

        $_SESSION['success'] = 'Product updated successfully!';
        redirect(adminUrl('/products/view/' . urlencode($newSlug)));
    } catch (Exception $e) {
        $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error updating product');
    }
}

// Fetch categories from database
$categories = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
    }
}

// Parse tags from JSON to display
$tagsDisplay = '';
if (!empty($product['tags'])) {
    try {
        $tagsArray = json_decode($product['tags'], true);
        if (is_array($tagsArray)) {
            $tagsDisplay = implode(', ', $tagsArray);
        }
    } catch (Exception $e) {
        $tagsDisplay = $product['tags'];
    }
}

// Parse images from database and convert to proper URLs
$imagesArray = [];
if (!empty($product['images'])) {
    $imagesArray = explode(',', $product['images']);
    $imagesArray = array_map('trim', $imagesArray);
    $imagesArray = array_filter($imagesArray);

    // Convert old paths (/assets/images/...) to proper URLs using assetUrl()
    $imagesArray = array_map(function($img) {
        // If path starts with /assets/, convert to proper URL
        if (strpos($img, '/assets/') === 0 || strpos($img, 'assets/') === 0) {
            $path = ltrim($img, '/');
            return assetUrl($path);
        }
        return $img;
    }, $imagesArray);
}

// Generate edit product content
$content = '
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Edit Product: ' . htmlspecialchars($product['name'] ?? '') . '</h1>
                <p class="text-gray-600 text-sm mt-1">Update product details and settings</p>
            </div>
            <div class="flex space-x-3 flex-shrink-0">
                <a href="' . adminUrl('/products/view/' . urlencode($product['slug'] ?? '')) . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors whitespace-nowrap">
                    <i class="fas fa-eye mr-2"></i>View Product
                </a>
                <a href="' . adminUrl('/qr-codes/') . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-purple-100 rounded-lg hover:bg-purple-200 transition-colors whitespace-nowrap">
                    <i class="fas fa-qrcode mr-2"></i>QR Codes
                </a>
                <a href="' . adminUrl('/products/') . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors whitespace-nowrap">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Products
                </a>
            </div>
        </div>
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
    <form method="POST" id="productEditForm" class="space-y-8">
        ' . csrf_field() . '
        <!-- Basic Information -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Basic Information</h2>
                <p class="text-sm text-gray-600">Essential product details</p>
            </div>
            <div class="px-6 py-6 space-y-6">
                ' . adminFormInput('Product Name *', 'name', $product['name'] ?? '', 'text', true, 'Enter product name') . '
                ' . adminFormTextarea('Short Description', 'short_description', $product['short_description'] ?? '', 2, false, 'Brief product description for listings') . '
                ' . adminFormTextarea('Full Description', 'description', $product['description'] ?? '', 6, false, 'Detailed product description') . '

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    ' . adminFormInput('SKU *', 'sku', $product['sku'] ?? '', 'text', true, 'Product SKU or model number') . '
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Select a category</option>';
foreach ($categories as $cat) {
    $content .= '<option value="' . htmlspecialchars($cat['name']) . '"' . (($product['category'] ?? '') === $cat['name'] ? ' selected' : '') . '>' . htmlspecialchars($cat['name']) . '</option>';
}
$content .= '
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Choose from existing categories</p>
                    </div>
                </div>

                ' . adminFormInput('Tags', 'tags', $tagsDisplay, 'text', false, 'Comma-separated tags (e.g., cannabis, flower, indica)') . '
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
                    ' . adminFormInput('Selling Price (R) *', 'price', $product['price'] ?? '', 'number', true, '0.00', ['step' => '0.01', 'min' => '0']) . '
                    ' . adminFormInput('Sale Price (R)', 'sale_price', $product['sale_price'] ?? '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '
                    ' . adminFormInput('Cost Price (R)', 'cost', $product['cost'] ?? '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '
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
                    ' . adminFormInput('Stock Quantity *', 'stock', $product['stock'] ?? '0', 'number', true, '0', ['min' => '0']) . '
                    ' . adminFormInput('Weight (g)', 'weight', $product['weight'] ?? '', 'number', false, '0.00', ['step' => '0.01', 'min' => '0']) . '
                    ' . adminFormInput('Dimensions (L×W×H cm)', 'dimensions', $product['dimensions'] ?? '', 'text', false, 'e.g., 10×5×2') . '
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
                <input type="hidden" name="images" id="field_images" value="' . htmlspecialchars(implode(', ', $imagesArray)) . '">

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
                            <input type="checkbox" name="active" value="1" ' . (($product['active'] ?? 0) ? 'checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-700">
                                <strong>Active</strong><br>
                                <span class="text-gray-500">Product is available for purchase</span>
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="featured" value="1" ' . (($product['featured'] ?? 0) ? 'checked' : '') . ' class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-700">
                                <strong>Featured Product</strong><br>
                                <span class="text-gray-500">Show in featured products section</span>
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="on_sale" value="1" ' . (($product['on_sale'] ?? 0) ? 'checked' : '') . ' class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <label class="ml-2 text-sm text-gray-700">
                                <strong>On Sale</strong><br>
                                <span class="text-gray-500">Show in Special Offers section on homepage</span>
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
                <input type="hidden" name="custom_fields" id="field_custom_fields" value="' . htmlspecialchars($product['custom_fields'] ?? '{}') . '">

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
                        <button type="button" onclick="addPredefinedField(&quot;Categories&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Categories</button>
                        <button type="button" onclick="addPredefinedField(&quot;Warranty&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Warranty</button>
                        <button type="button" onclick="addPredefinedField(&quot;Part Number&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Part Number</button>
                        <button type="button" onclick="addPredefinedField(&quot;Model&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Model</button>
                        <button type="button" onclick="addPredefinedField(&quot;Barcode&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Barcode</button>
                        <button type="button" onclick="addPredefinedField(&quot;Colour Name&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Colour Name</button>
                        <button type="button" onclick="addPredefinedField(&quot;What&apos;s in the box&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">What&apos;s in the box</button>
                        <button type="button" onclick="addPredefinedField(&quot;Classification&quot;)" class="px-3 py-2 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Classification</button>
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
                ' . adminFormTextarea('Policies & Warranty Text', 'product_policies', $product['product_policies'] ?? '', 6, false, 'Enter policy statements, one per line (e.g., Eligible for next-day delivery, 6-Month Limited Warranty, etc.)') . '

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
                    ' . adminFormInput('Meta Title', 'meta_title', $product['meta_title'] ?? '', 'text', false, 'SEO title for search engines') . '
                    ' . adminFormInput('Meta Description', 'meta_description', $product['meta_description'] ?? '', 'text', false, 'SEO description for search engines') . '
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <a href="' . adminUrl('/products/') . '" class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Update Product
            </button>
        </div>
    </form>
</div>
';

$content .= '
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

/**
 * Normalize image URL for display
 * Removes hardcoded CannaBuddy.shop prefix and converts to proper URL
 */
function normalizeImageUrl(rawUrl) {
    if (!rawUrl) return "";

    // Remove http(s)://localhost/CannaBuddy.shop/ or similar
    var pattern1 = new RegExp("^https?://[^/]+/[^/]+/", "i");
    var url = rawUrl.replace(pattern1, "/");

    // Remove hardcoded /CannaBuddy.shop/ prefix
    var pattern2 = new RegExp("^/CannaBuddy\\\\.shop/", "i");
    url = url.replace(pattern2, "/");

    // Remove leading slash for url() helper
    url = url.replace(new RegExp("^/"), "");

    // Get the base URL from the data attribute or construct it
    var pattern3 = new RegExp("/admin/products/edit/.*");
    var baseUrl = window.location.origin + window.location.pathname.replace(pattern3, "");

    return baseUrl + "/" + url;
}

function renderPreviews() {
    console.log("renderPreviews() called");
    console.log("imagesField:", imagesField);

    console.log("previewContainer:", previewContainer);
    if (!imagesField || !previewContainer) {
        console.log("Missing required elements");
        return;
    }
    const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
    console.log("currentUrls:", currentUrls);

    previewContainer.innerHTML = "";

    currentUrls.forEach((url, index) => {
        const div = document.createElement("div");
        div.className = "relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 border-gray-200 hover:border-green-500 transition-all";
        div.setAttribute("data-image-url", url);
        div.setAttribute("data-index", index);

        const img = document.createElement("img");
        img.src = normalizeImageUrl(url);
        img.className = "w-full h-full object-cover";
        img.alt = "Product image " + (index + 1);

        // Order Badge
        const badge = document.createElement("div");
        badge.className = "absolute top-2 left-2 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full";
        badge.innerText = "#" + (index + 1);
        div.appendChild(badge);

        // Hover Overlay with Controls
        const overlay = document.createElement("div");
        overlay.className = "absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2";

        // Move Up Button
        const moveUpBtn = document.createElement("button");
        moveUpBtn.type = "button";
        moveUpBtn.className = "bg-white text-gray-800 p-2 rounded-lg hover:bg-gray-100 transition-all";
        moveUpBtn.title = "Move Up";
        moveUpBtn.innerHTML = "<i class=\"fas fa-arrow-up\"></i>";
        moveUpBtn.onclick = () => moveImageUp(index);

        // Move Down Button
        const moveDownBtn = document.createElement("button");
        moveDownBtn.type = "button";
        moveDownBtn.className = "bg-white text-gray-800 p-2 rounded-lg hover:bg-gray-100 transition-all";
        moveDownBtn.title = "Move Down";
        moveDownBtn.innerHTML = "<i class=\"fas fa-arrow-down\"></i>";
        moveDownBtn.onclick = () => moveImageDown(index);

        // Delete Button
        const deleteBtn = document.createElement("button");
        deleteBtn.type = "button";
        deleteBtn.className = "bg-red-500 text-white p-2 rounded-lg hover:bg-red-600 transition-all";
        deleteBtn.title = "Delete";
        deleteBtn.innerHTML = "<i class=\"fas fa-trash\"></i>";
        deleteBtn.onclick = () => deleteImage(index);

        overlay.appendChild(moveUpBtn);
        overlay.appendChild(moveDownBtn);
        overlay.appendChild(deleteBtn);

        div.appendChild(img);
        div.appendChild(overlay);
        previewContainer.appendChild(div);
    });
}

function moveImageUp(index) {
    const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
    if (index > 0) {
        [currentUrls[index], currentUrls[index - 1]] = [currentUrls[index - 1], currentUrls[index]];
        imagesField.value = currentUrls.join(", ");
        renderPreviews();
    }
}

function moveImageDown(index) {
    const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
    if (index < currentUrls.length - 1) {
        [currentUrls[index], currentUrls[index + 1]] = [currentUrls[index + 1], currentUrls[index]];
        imagesField.value = currentUrls.join(", ");
        renderPreviews();
    }
}

function deleteImage(index) {
    if (confirm("Are you sure you want to delete this image?")) {
        const currentUrls = imagesField.value ? imagesField.value.split(",").map(s => s.trim()).filter(s => s) : [];
        currentUrls.splice(index, 1);
        imagesField.value = currentUrls.join(", ");
        renderPreviews();
    }
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
console.log("About to call renderPreviews()");
renderPreviews();
console.log("renderPreviews() completed");
</script>
';

$content .= '
<style>
#productEditForm input[type="text"],
#productEditForm input[type="number"],
#productEditForm input[type="email"],
#productEditForm input[type="url"],
#productEditForm textarea,
#productEditForm select {
    border: 1px solid #bbf7d0;
}

#productEditForm input[type="text"]:focus,
#productEditForm input[type="number"]:focus,
#productEditForm input[type="email"]:focus,
#productEditForm input[type="url"]:focus,
#productEditForm textarea:focus,
#productEditForm select:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.35);
}
</style>
';

$content .= '
<script>
// Custom Fields Management
let customFields = [];

function loadCustomFields() {
    const customFieldsInput = document.getElementById("field_custom_fields");
    const container = document.getElementById("customFieldsContainer");

    if (customFieldsInput && customFieldsInput.value) {
        try {
            customFields = JSON.parse(customFieldsInput.value);
            if (!Array.isArray(customFields)) {
                customFields = [];
            }
        } catch (e) {
            customFields = [];
        }
    } else {
        customFields = [];
    }

    if (container) {
        container.innerHTML = "";
        customFields.forEach(function(field, index) {
            addFieldToUI(field.label, field.value, index);
        });
    }
}

function addCustomField(label, value) {
    if (label === undefined) label = "";
    if (value === undefined) value = "";
    const container = document.getElementById("customFieldsContainer");
    const index = customFields.length;

    customFields.push({label: label, value: value});
    addFieldToUI(label, value, index);
    updateCustomFieldsInput();
}

function addPredefinedField(fieldLabel) {
    addCustomField(fieldLabel, "");
}

function htmlEscape(str) {
    if (str === undefined || str === null) return \'\';
    return String(str)
        .replace(/&/g, \'&amp;\')
        .replace(/</g, \'&lt;\')
        .replace(/>/g, \'&gt;\')
        .replace(/"/g, \'&quot;\')
        .replace(/\'/g, \'&#39;\');
}

function addFieldToUI(label, value, index) {
    const container = document.getElementById("customFieldsContainer");
    if (!container) return;

    const fieldDiv = document.createElement("div");
    fieldDiv.className = "bg-gray-50 border border-gray-200 rounded-lg p-4";
    fieldDiv.setAttribute("data-index", index);

    fieldDiv.innerHTML = "<div class=\"flex items-start space-x-3\">" +
        "<div class=\"flex-1\">" +
        "<label class=\"block text-sm font-medium text-gray-700 mb-1\">Field Name</label>" +
        "<input type=\"text\" value=\"" + htmlEscape(label) + "\" onchange=\"updateCustomField(" + index + ", \'label\', this.value)\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500\" placeholder=\"e.g., Warranty\">" +
        "</div>" +
        "<div class=\"flex-1\">" +
        "<label class=\"block text-sm font-medium text-gray-700 mb-1\">Value</label>" +
        "<input type=\"text\" value=\"" + htmlEscape(value) + "\" onchange=\"updateCustomField(" + index + ", \'value\', this.value)\" class=\"w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500\" placeholder=\"e.g., Limited (6 months)\">" +
        "</div>" +
        "<button type=\"button\" onclick=\"removeCustomField(" + index + ")\" class=\"mt-6 p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors\">" +
        "<i class=\"fas fa-trash\"></i>" +
        "</button>" +
        "</div>";

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
    const input = document.getElementById("field_custom_fields");
    if (input) {
        input.value = JSON.stringify(customFields);
    }
}

// Load custom fields when page is ready
document.addEventListener("DOMContentLoaded", function() {
    loadCustomFields();
});
</script>

';

// Render the page with sidebar
echo adminSidebarWrapper('Edit Product', $content, 'products');
