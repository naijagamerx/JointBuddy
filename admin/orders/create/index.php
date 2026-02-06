<?php
// Manual Order Creation Page
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$orderService = new OrderService($db);
$adminId = AuthMiddleware::getAdminId();

// Generate CSRF token for form protection
$csrfToken = CsrfMiddleware::getToken();

// Get delivery methods and payment methods
$deliveryMethods = $orderService->getDeliveryMethods();
$paymentMethods = $orderService->getPaymentMethods();

// Display messages
$messageHtml = '';
if (isset($_SESSION['success'])) {
    $messageHtml = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . htmlspecialchars($_SESSION['success']) . '</p>
            </div>
        </div>
    </div>';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $messageHtml = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . htmlspecialchars($_SESSION['error']) . '</p>
            </div>
        </div>
    </div>';
    unset($_SESSION['error']);
}

// Generate page content
$content = '
<div class="w-full max-w-7xl mx-auto">
    ' . $messageHtml . '

    <!-- Page Header -->
    <div class="mb-6">
        <a href="' . adminUrl('/orders/') . '" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <i class="fas fa-arrow-left mr-2"></i>Back to Orders
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Create Manual Order</h1>
        <p class="text-gray-600 mt-1">Create an order for customers who didn\'t purchase through the website</p>
    </div>

    <form method="POST" action="' . adminUrl('/orders/create/process.php') . '" id="orderForm" onsubmit="return validateForm();">
        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
        <input type="hidden" name="items_data" id="items_data">

        <!-- Customer Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-user mr-2 text-green-600"></i>Customer Information
            </h2>

            <!-- Customer Search -->
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Existing Customer (Email or Phone)</label>
                <div class="flex gap-2">
                    <input type="text" id="customer_search" placeholder="Enter email or phone number..."
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           oninput="searchCustomer(this.value)">
                    <button type="button" onclick="clearCustomerSearch()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Clear
                    </button>
                </div>
                <div id="customer_search_result" class="mt-2 hidden"></div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <p class="text-sm text-gray-500 mb-4">Or enter new customer details:</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" id="last_name" name="last_name"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="email" id="email" name="email"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="tel" id="phone" name="phone"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-truck mr-2 text-blue-600"></i>Shipping Address
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                    <input type="text" id="shipping_street" name="shipping_street" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                    <input type="text" id="shipping_city" name="shipping_city" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State/Province *</label>
                    <input type="text" id="shipping_state" name="shipping_state" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                    <input type="text" id="shipping_postal_code" name="shipping_postal_code" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
        </div>

        <!-- Billing Address -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center mb-4">
                <input type="checkbox" id="same_as_billing" name="same_as_billing" value="1" checked
                       onchange="toggleBillingAddress(this.checked)" class="mr-2">
                <label for="same_as_billing" class="text-sm font-medium text-gray-700">
                    <strong>Same as Shipping Address</strong>
                </label>
            </div>

            <div id="billing_address_fields" class="hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-file-invoice mr-2 text-purple-600"></i>Billing Address
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                        <input type="text" id="billing_street" name="billing_street"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                        <input type="text" id="billing_city" name="billing_city"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">State/Province *</label>
                        <input type="text" id="billing_state" name="billing_state"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                        <input type="text" id="billing_postal_code" name="billing_postal_code"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-box mr-2 text-yellow-600"></i>Add Products
            </h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Products</label>
                <div class="flex gap-2">
                    <input type="text" id="product_search" placeholder="Search by name or SKU..."
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           oninput="searchProducts(this.value)">
                </div>
                <div id="product_search_results" class="mt-2 hidden max-h-60 overflow-y-auto border border-gray-200 rounded-lg"></div>
            </div>

            <!-- Order Items Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="order_items_table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody id="order_items_body" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-2"></i>
                                <p>No products added yet. Search and add products above.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Order Options -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-cog mr-2 text-gray-600"></i>Order Options
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Method</label>
                    <select id="delivery_method" name="delivery_method_id" onchange="updateTotals()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select delivery method...</option>';
                        foreach ($deliveryMethods as $dm) {
                            $content .= '<option value="' . htmlspecialchars($dm['id']) . '" data-cost="' . ($dm['cost'] ?? 0) . '">' . htmlspecialchars($dm['name']) . ' (R' . number_format($dm['cost'] ?? 0, 2) . ')</option>';
                        }
                        $content .= '
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select id="payment_method" name="payment_method" onchange="updatePaymentStatus()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">';
                        foreach ($paymentMethods as $pm) {
                            $content .= '<option value="' . htmlspecialchars($pm['id']) . '" data-default-status="' . htmlspecialchars($pm['default_status'] ?? '') . '">' . htmlspecialchars($pm['name']) . '</option>';
                        }
                        $content .= '
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                    <select id="payment_status" name="payment_status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial Payment</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes (Internal)</label>
                <textarea id="notes" name="notes" rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                          placeholder="Add any internal notes about this order..."></textarea>
            </div>
        </div>

        <!-- Pricing Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-calculator mr-2 text-green-600"></i>Pricing Summary
            </h2>
            <div class="max-w-md ml-auto">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Subtotal</span>
                    <span id="subtotal" class="font-medium">R0.00</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200" id="product_discount_row" style="display: none;">
                    <span class="text-gray-600">Product Discount</span>
                    <span id="product_discount" class="font-medium text-green-600">-R0.00</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Shipping</span>
                    <span id="shipping" class="font-medium">R0.00</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Extra Discount</span>
                    <div class="flex items-center gap-2">
                        <span class="text-red-600">-</span>
                        <input type="number" id="discount_amount" name="discount_amount" value="0" min="0" step="0.01"
                               onchange="updateTotals()" class="w-24 px-2 py-1 border border-gray-300 rounded text-right">
                        <span id="discount" class="text-red-600 font-medium">R0.00</span>
                    </div>
                </div>
                <div class="flex justify-between py-3 text-lg">
                    <span class="font-bold">Total</span>
                    <span id="total" class="font-bold text-green-600">R0.00</span>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Create Order
            </button>
        </div>
    </form>
</div>

<script>
// Order items storage
let orderItems = [];

// Event delegation for product search results
document.addEventListener("DOMContentLoaded", function() {
    const searchResultsContainer = document.getElementById("product_search_results");
    if (searchResultsContainer) {
        searchResultsContainer.addEventListener("click", function(e) {
            // Find the closest product-result element (clicked element or parent)
            const productResult = e.target.closest(".product-result");
            if (productResult) {
                addProductToOrder(
                    parseInt(productResult.dataset.productId),
                    productResult.dataset.productName,
                    parseFloat(productResult.dataset.productPrice),
                    parseFloat(productResult.dataset.productOriginalPrice),
                    parseFloat(productResult.dataset.productComparePrice),
                    productResult.dataset.productIsOnSale === "1",
                    productResult.dataset.productSku,
                    parseInt(productResult.dataset.productStock),
                    productResult.dataset.productImage
                );
            }
        });
    }
});

// Customer search
function searchCustomer(query) {
    if (query.length < 3) {
        document.getElementById("customer_search_result").classList.add("hidden");
        return;
    }

    fetch("' . adminUrl('/orders/create/search-customer.php') . '?q=" + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.customer) {
                const c = data.customer;
                document.getElementById("first_name").value = c.first_name || "";
                document.getElementById("last_name").value = c.last_name || "";
                document.getElementById("email").value = c.email || "";
                document.getElementById("phone").value = c.phone || "";

                document.getElementById("customer_search_result").innerHTML =
                    "<div class=\"text-sm text-green-600\">Found customer: " + c.first_name + " " + c.last_name + "</div>";
                document.getElementById("customer_search_result").classList.remove("hidden");
            } else {
                document.getElementById("customer_search_result").classList.add("hidden");
            }
        });
}

function clearCustomerSearch() {
    document.getElementById("customer_search").value = "";
    document.getElementById("customer_search_result").classList.add("hidden");
}

// Product search
let searchTimeout;
function searchProducts(query) {
    clearTimeout(searchTimeout);

    if (query.length < 2) {
        document.getElementById("product_search_results").classList.add("hidden");
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch("' . adminUrl('/orders/create/search-products.php') . '?q=" + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.products.length > 0) {
                    let html = "";
                    data.products.forEach(p => {
                        const originalPrice = parseFloat(p.price || 0);
                        const comparePrice = parseFloat(p.compare_price || 0);
                        const salePrice = parseFloat(p.sale_price || 0);
                        const isOnSale = p.on_sale == 1 && salePrice > 0;
                        const finalPrice = isOnSale ? salePrice : originalPrice;
                        const image = p.images ? p.images.split(",")[0] : null;

                        // Calculate discount info
                        let discountInfo = "";
                        let priceDisplay = "";

                        if (isOnSale) {
                            const basePrice = comparePrice > 0 ? comparePrice : originalPrice;
                            const discountAmount = basePrice - finalPrice;
                            const discountPercent = Math.round((discountAmount / basePrice) * 100);

                            priceDisplay = `
                                <div class="text-xs text-gray-400 line-through">R${basePrice.toFixed(2)}</div>
                                <div class="font-bold text-green-600">R${finalPrice.toFixed(2)}</div>
                                <div class="text-xs text-red-500 font-medium">Save ${discountPercent}%</div>
                            `;
                        } else {
                            priceDisplay = `
                                <div class="font-medium text-green-600">R${finalPrice.toFixed(2)}</div>
                            `;
                        }

                        // Build HTML string with data attributes for event delegation (XSS-safe)
                        html += `
                            <div class="product-result flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                 data-product-id="${p.id}"
                                 data-product-name="${escapeHtml(p.name).replace(/"/g, "&quot;")}"
                                 data-product-price="${finalPrice}"
                                 data-product-original-price="${originalPrice}"
                                 data-product-compare-price="${comparePrice}"
                                 data-product-is-on-sale="${isOnSale ? "1" : "0"}"
                                 data-product-sku="${escapeHtml(p.sku || "").replace(/"/g, "&quot;")}"
                                 data-product-stock="${p.stock}"
                                 data-product-image="${image || ""}">
                                ${image ? `<img src="${image}" class="w-12 h-12 object-cover rounded mr-3">` : ""}
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">${escapeHtml(p.name)}</div>
                                    <div class="text-sm text-gray-500">SKU: ${escapeHtml(p.sku || "N/A")} | Stock: ${p.stock}</div>
                                </div>
                                <div class="text-right">
                                    ${priceDisplay}
                                </div>
                            </div>
                        `;
                    });
                    document.getElementById("product_search_results").innerHTML = html;
                    document.getElementById("product_search_results").classList.remove("hidden");
                } else {
                    document.getElementById("product_search_results").classList.add("hidden");
                }
            });
    }, 300);
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function addProductToOrder(productId, name, price, originalPrice, comparePrice, isOnSale, sku, stock, image) {
    // Check if already exists
    const existing = orderItems.find(i => i.product_id === productId);
    if (existing) {
        existing.quantity++;
    } else {
        orderItems.push({
            product_id: productId,
            name: name,
            sku: sku,
            unit_price: price,
            original_price: originalPrice,
            compare_price: comparePrice,
            is_on_sale: isOnSale,
            stock: stock,
            image: image,
            quantity: 1
        });
    }

    document.getElementById("product_search").value = "";
    document.getElementById("product_search_results").classList.add("hidden");

    updateOrderItemsTable();
    updateTotals();
}

function updateOrderItemsTable() {
    const tbody = document.getElementById("order_items_body");

    if (orderItems.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-2 block"></i>
                    <p>No products added yet. Search and add products above.</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = "";
    orderItems.forEach((item, index) => {
        const lineTotal = item.unit_price * item.quantity;
        const stockClass = item.quantity > item.stock ? "text-red-600" : "text-gray-600";
        const stockWarning = item.quantity > item.stock ? " (exceeds stock!)" : "";

        // Build price display with discount info
        let priceDisplay = `R${item.unit_price.toFixed(2)}`;
        if (item.is_on_sale) {
            const basePrice = item.compare_price > 0 ? item.compare_price : item.original_price;
            const discountAmount = basePrice - item.unit_price;
            const discountPercent = Math.round((discountAmount / basePrice) * 100);
            priceDisplay = `
                <div class="text-xs text-gray-400 line-through">R${basePrice.toFixed(2)}</div>
                <div class="text-green-600 font-medium">R${item.unit_price.toFixed(2)}</div>
                <div class="text-xs text-red-500">-${discountPercent}%</div>
            `;
        }

        html += `
            <tr>
                <td class="px-4 py-3">
                    ${item.image ? `<img src="${item.image}" class="w-10 h-10 object-cover rounded mr-2 inline">` : ""}
                    <div>
                        <div class="font-medium text-gray-900">${item.name}</div>
                        <div class="text-xs text-gray-500">${item.sku || "No SKU"}</div>
                        ${item.is_on_sale ? `<span class="inline-block mt-1 px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded-full">SALE</span>` : ""}
                    </div>
                </td>
                <td class="px-4 py-3 text-center ${stockClass}">${item.stock}${stockWarning}</td>
                <td class="px-4 py-3 text-center">
                    <input type="number" value="${item.quantity}" min="1"
                           onchange="updateQuantity(${index}, this.value)"
                           class="w-20 px-2 py-1 border border-gray-300 rounded text-center">
                </td>
                <td class="px-4 py-3 text-right">${priceDisplay}</td>
                <td class="px-4 py-3 text-right font-medium">R${lineTotal.toFixed(2)}</td>
                <td class="px-4 py-3 text-center">
                    <button type="button" onclick="removeItem(${index})"
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function updateQuantity(index, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 1) quantity = 1;
    orderItems[index].quantity = quantity;
    updateOrderItemsTable();
    updateTotals();
}

function removeItem(index) {
    orderItems.splice(index, 1);
    updateOrderItemsTable();
    updateTotals();
}

function updateTotals() {
    // Calculate original subtotal (before product discounts)
    let originalSubtotal = 0;
    let productDiscount = 0;
    let saleSubtotal = 0;

    orderItems.forEach(item => {
        const qty = item.quantity;
        const originalPrice = item.compare_price > 0 ? item.compare_price : item.original_price;
        const salePrice = item.unit_price;

        originalSubtotal += originalPrice * qty;
        saleSubtotal += salePrice * qty;
    });

    productDiscount = originalSubtotal - saleSubtotal;

    // Get shipping cost
    const deliverySelect = document.getElementById("delivery_method");
    const selectedOption = deliverySelect.options[deliverySelect.selectedIndex];
    const shippingCost = selectedOption && selectedOption.dataset.cost ? parseFloat(selectedOption.dataset.cost) : 0;

    // Get manual discount
    const manualDiscount = parseFloat(document.getElementById("discount_amount").value) || 0;

    const total = saleSubtotal + shippingCost - manualDiscount;

    // Update display
    document.getElementById("subtotal").textContent = "R" + originalSubtotal.toFixed(2);
    document.getElementById("shipping").textContent = "R" + shippingCost.toFixed(2);
    document.getElementById("discount").textContent = "-R" + manualDiscount.toFixed(2);
    document.getElementById("total").textContent = "R" + total.toFixed(2);

    // Show/hide product discount row
    const productDiscountRow = document.getElementById("product_discount_row");
    if (productDiscount > 0) {
        productDiscountRow.style.display = "flex";
        document.getElementById("product_discount").textContent = "-R" + productDiscount.toFixed(2);
    } else {
        productDiscountRow.style.display = "none";
    }
}

function updatePaymentStatus() {
    const paymentSelect = document.getElementById("payment_method");
    const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
    const defaultStatus = selectedOption.dataset.defaultStatus;

    document.getElementById("payment_status").value = defaultStatus;
}

function toggleBillingAddress(same) {
    const billingFields = document.getElementById("billing_address_fields");
    if (same) {
        billingFields.classList.add("hidden");
    } else {
        billingFields.classList.remove("hidden");
    }
}

function validateForm() {
    const required = [
        "first_name",
        "shipping_street", "shipping_city", "shipping_state", "shipping_postal_code"
    ];

    for (const field of required) {
        const el = document.getElementById(field);
        if (!el.value.trim()) {
            alert("Please fill in all required fields");
            el.focus();
            return false;
        }
    }

    // Email validation - only if provided
    const email = document.getElementById("email");
    if (email.value.trim() !== "" && !email.value.match(/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/)) {
        alert("Please enter a valid email address or leave blank");
        email.focus();
        return false;
    }

    // Check billing address if not same as shipping
    if (!document.getElementById("same_as_billing").checked) {
        const billingRequired = [
            "billing_street", "billing_city", "billing_state", "billing_postal_code"
        ];
        for (const field of billingRequired) {
            const el = document.getElementById(field);
            if (!el.value.trim()) {
                alert("Please fill in all billing address fields");
                el.focus();
                return false;
            }
        }
    }

    // Check order items
    if (orderItems.length === 0) {
        alert("Please add at least one product");
        return false;
    }

    // Add order items to form data
    const itemsInput = document.getElementById("items_data");
    const itemsData = orderItems.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price
    }));
    itemsInput.value = JSON.stringify(itemsData);

    // Add items as hidden inputs for form submission
    const form = document.getElementById("orderForm");
    // Remove existing items inputs
    form.querySelectorAll("input[name^=\'items[\']").forEach(el => el.remove());

    // Add new items inputs
    itemsData.forEach((item, index) => {
        const productIdInput = document.createElement("input");
        productIdInput.type = "hidden";
        productIdInput.name = "items[" + index + "][product_id]";
        productIdInput.value = item.product_id;
        form.appendChild(productIdInput);

        const quantityInput = document.createElement("input");
        quantityInput.type = "hidden";
        quantityInput.name = "items[" + index + "][quantity]";
        quantityInput.value = item.quantity;
        form.appendChild(quantityInput);

        const unitPriceInput = document.createElement("input");
        unitPriceInput.type = "hidden";
        unitPriceInput.name = "items[" + index + "][unit_price]";
        unitPriceInput.value = item.unit_price;
        form.appendChild(unitPriceInput);
    });

    return true;
}

// Close search results when clicking outside
document.addEventListener("click", function(e) {
    if (!e.target.closest("#product_search") && !e.target.closest("#product_search_results")) {
        document.getElementById("product_search_results").classList.add("hidden");
    }
});
</script>
';

// Render the page with sidebar
echo adminSidebarWrapper('Create Manual Order', $content, 'orders-create');
