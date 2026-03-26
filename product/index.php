<?php
/**
 * Product Details Page - CannaBuddy
 * Redesigned single product page with all sections
 */
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/product_helpers.php';

// Initialize global variables from Services for backward compatibility
$db = Services::db();
$userAuth = Services::userAuth();
$currencyService = Services::currencyService();

// Get product slug from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract slug - it's the last segment after /product/
if (preg_match('#/product/([^/\?]+)#', $path, $matches)) {
    $productSlug = $matches[1];
} else {
    $productSlug = '';
}

// Check user authentication status for header (bootstrap handles session)
$currentUser = null;
$isLoggedIn = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

// Fetch product
$product = null;
if ($db && !empty($productSlug)) {
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE slug = ? AND status = 'active'");
        $stmt->execute([$productSlug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching product: " . $e->getMessage());
    }
}

// Fetch custom fields for the product
$customFields = [];
if ($db && $product) {
    $customFields = getProductCustomFields($db, $product['id']);

    // Add product category to custom fields
    if (!empty($product['category'])) {
        array_unshift($customFields, [
            'field_name' => 'Category',
            'field_value' => ucfirst(htmlspecialchars($product['category'])),
            'field_label' => 'Category'
        ]);
    }
}

// Set page title
$pageTitle = $product ? htmlspecialchars($product['name']) : 'Product Not Found';

// Set SEO Meta Tags for Product Page
if ($product) {
    $metaDescription = !empty($product['short_description']) 
        ? htmlspecialchars(mb_strimwidth($product['short_description'], 0, 160, "...")) 
        : htmlspecialchars($product['name']) . " - Premium 3D printed cannabis accessory by JointBuddy.";
    
    $metaKeywords = htmlspecialchars($product['name']) . ", 3d printed " . htmlspecialchars($product['category'] ?? 'cannabis accessories') . ", JointBuddy, jointbuddy.co.za, buy " . htmlspecialchars($product['name']);
    
    // OG Image
    $ogImage = getProductMainImage($product);
    
    // Canonical URL
    $canonicalUrl = productUrl($product['slug']);
}

// Include header
include __DIR__ . '/../includes/header.php';

// If product not found, show error
if (!$product):
?>
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center p-8">
        <div class="mb-6">
            <i class="fas fa-exclamation-triangle text-6xl text-red-500"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Product Not Found</h1>
        <p class="text-gray-600 mb-8">The product you're looking for doesn't exist or has been removed.</p>
        <a href="<?php echo  shopUrl('/') ?>" class="inline-flex items-center bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Shop
        </a>
    </div>
</div>
<?php
include __DIR__ . '/../includes/footer.php';
exit;
endif;

// Get all product data
$productImages = getProductImages($product);
$mainImage = getProductMainImage($product);
$customFields = getProductCustomFields($db, $product['id']);
$productPolicies = getProductPolicies($product);
$popularPicks = getPopularPicksInCategory($db, $product['id'], $product['category_id'] ?? null, 4);
$youMightLike = getYouMightAlsoLike($db, $product['id'], 4);
$relatedProducts = getRelatedProducts($db, $product['id'], $product['category_id'] ?? null, 4);
$brandProducts = getSimilarFromBrands($db, $product['id'], $product['brand'] ?? null, 4);
$oftenBought = getOftenBoughtTogether($db, $product['id'], 3);
$reviews = getProductReviews($db, $product['id']);
$ratingSummary = getProductRatingSummary($db, $product['id']);

// Parse colors if available
$colors = [];
if (!empty($product['colors'])) {
    $colors = json_decode($product['colors'], true) ?: [];
}

// Calculate stock status
$stock = intval($product['stock'] ?? 0);
$inStock = $stock > 0;

// Price formatting
$price = floatval($product['price']);
$salePrice = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
$displayPrice = $salePrice ?: $price;

// Format prices using currency service if available
if (isset($currencyService)) {
    $formattedPrice = $currencyService->formatPrice($price);
    $formattedSalePrice = $salePrice ? $currencyService->formatPrice($salePrice) : null;
    $formattedDisplayPrice = $currencyService->formatPrice($displayPrice);
} else {
    $formattedPrice = 'R ' . number_format($price, 2);
    $formattedSalePrice = $salePrice ? 'R ' . number_format($salePrice, 2) : null;
    $formattedDisplayPrice = 'R ' . number_format($displayPrice, 2);
}

// User logged in check
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']);
$currentUserId = $isLoggedIn ? $_SESSION['user_id'] : null;
?>

<!-- Breadcrumb -->
<div class="bg-gray-50 border-b">
    <div class="container mx-auto px-4 py-3 max-w-7xl">
        <nav class="flex items-center text-sm text-gray-600">
            <a href="<?php echo  url('/') ?>" class="hover:text-green-600 transition-colors">Home</a>
            <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
            <a href="<?php echo  shopUrl('/') ?>" class="hover:text-green-600 transition-colors">Shop</a>
            <?php if (!empty($product['category'])): ?>
            <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
            <a href="<?php echo  shopUrl('/?category=' . urlencode($product['category'])) ?>" class="hover:text-green-600 transition-colors">
                <?php echo  htmlspecialchars(ucfirst($product['category'])) ?>
            </a>
            <?php endif; ?>
            <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
            <span class="text-gray-900 font-medium truncate max-w-[200px]"><?php echo  htmlspecialchars($product['name']) ?></span>
        </nav>
    </div>
</div>

<!-- Product Hero Section -->
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

        <!-- Left: Image Gallery (Thumbnails on left of main image) -->
        <div class="flex gap-4">
            <!-- Thumbnail Column - only show if there are multiple images -->
            <?php if (count($productImages) > 1): ?>
            <div class="flex flex-col gap-2 w-20 flex-shrink-0">
                <?php foreach ($productImages as $index => $img): ?>
                <button onclick="changeMainImage('<?php echo  htmlspecialchars($img) ?>', this)"
                        class="thumbnail-btn aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 <?php echo  $index === 0 ? 'border-green-500' : 'border-transparent' ?> hover:border-green-400 transition-all">
                    <img src="<?php echo  htmlspecialchars($img) ?>" alt="Thumbnail <?php echo  $index + 1 ?>"
                         class="w-full h-full object-cover"
                         onerror="this.src='<?php echo assetUrl('images/products/placeholder.png'); ?>'">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Main Image -->
            <div class="flex-1">
                <div class="relative bg-gray-100 rounded-xl overflow-hidden aspect-square">
                    <img id="mainProductImage"
                         src="<?php echo  htmlspecialchars($mainImage) ?>"
                         alt="<?php echo  htmlspecialchars($product['name']) ?>"
                         class="w-full h-full object-cover cursor-zoom-in"
                         onclick="openLightbox(this.src)"
                         onerror="this.src='<?php echo assetUrl('images/products/placeholder.png'); ?>'">

                    <!-- Sale Badge -->
                    <?php if ($salePrice): ?>
                    <div class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                        SALE
                    </div>
                    <?php endif; ?>

                    <!-- Zoom hint -->
                    <div class="absolute bottom-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-xs">
                        <i class="fas fa-search-plus mr-1"></i>Click to zoom
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Product Info -->
        <div class="lg:pl-4">
            <!-- Brand -->
            <?php if (!empty($product['brand'])): ?>
            <p class="text-sm text-green-600 font-medium mb-2"><?php echo  htmlspecialchars($product['brand']) ?></p>
            <?php endif; ?>

            <!-- Title -->
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-4"><?php echo  htmlspecialchars($product['name']) ?></h1>

            <!-- Rating -->
            <div class="flex items-center gap-3 mb-4">
                <?php echo  renderStarRating($ratingSummary['avg_rating'] ?? 0) ?>
                <span class="text-sm text-gray-500">
                    (<?php echo  intval($ratingSummary['total_reviews'] ?? 0) ?> reviews)
                </span>
                <a href="#reviews" class="text-sm text-green-600 hover:underline">Write a review</a>
            </div>

            <!-- Price -->
            <div class="mb-6">
                <?php if ($salePrice): ?>
                <div class="flex items-center gap-3">
                    <span class="text-3xl font-bold text-green-600"><?php echo  $formattedSalePrice ?></span>
                    <span class="text-xl text-gray-400 line-through"><?php echo  $formattedPrice ?></span>
                    <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-medium">
                        Save <?php echo  round((($price - $salePrice) / $price) * 100) ?>%
                    </span>
                </div>
                <?php else: ?>
                <span class="text-3xl font-bold text-green-600"><?php echo  $formattedPrice ?></span>
                <?php endif; ?>
            </div>

            <!-- Stock Status -->
            <div class="mb-6">
                <?php if ($inStock): ?>
                <div class="flex items-center text-green-600">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="font-medium">In Stock</span>
                    <?php if ($stock <= 10): ?>
                    <span class="ml-2 text-orange-500 text-sm">(Only <?php echo  $stock ?> left!)</span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="flex items-center text-red-500">
                    <i class="fas fa-times-circle mr-2"></i>
                    <span class="font-medium">Out of Stock</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Policies & Warranty -->
            <?php if (!empty($productPolicies)): ?>
            <div class="mb-6 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Product Policies</h3>
                <ul class="space-y-2">
                    <?php foreach ($productPolicies as $policy): ?>
                    <li class="flex items-start text-sm text-gray-700">
                        <i class="fas fa-check-circle text-green-500 mr-3 flex-shrink-0 mt-0.5"></i>
                        <span><?php echo  htmlspecialchars($policy) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>


            <!-- Short Description -->
            <?php if (!empty($product['short_description'])): ?>
            <p class="text-gray-600 mb-6"><?php echo  nl2br(htmlspecialchars($product['short_description'])) ?></p>
            <?php endif; ?>

            <!-- Color Selection -->
            <?php if (!empty($colors)): ?>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Color: <span id="selectedColorName" class="font-normal text-gray-600"><?php echo  htmlspecialchars($colors[0]) ?></span></label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($colors as $index => $color): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="color" value="<?php echo  htmlspecialchars($color) ?>"
                               class="sr-only peer" <?php echo  $index === 0 ? 'checked' : '' ?>
                               onchange="document.getElementById('selectedColorName').textContent = this.value">
                        <span class="block px-4 py-2 border-2 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-gray-400 transition-all">
                            <?php echo  htmlspecialchars($color) ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add to Cart Form -->
            <form id="addToCartForm" class="mb-6" onsubmit="return handleAddToCart(event)">
                <input type="hidden" name="product_id" value="<?php echo  $product['id'] ?>">
                <input type="hidden" name="redirect" value="<?php echo  htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                <!-- Quantity -->
                <div class="flex items-center gap-4 mb-4">
                    <label class="text-sm font-semibold text-gray-700">Quantity:</label>
                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                        <button type="button" onclick="updateQuantity(-1)"
                                class="w-10 h-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo  $stock ?>"
                               class="w-16 h-10 text-center border-x border-gray-300 focus:outline-none">
                        <button type="button" onclick="updateQuantity(1)"
                                class="w-10 h-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button type="submit" <?php echo  !$inStock ? 'disabled' : '' ?>
                            class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        <?php echo  $inStock ? 'Add to Cart' : 'Out of Stock' ?>
                    </button>
                    <button type="button" onclick="addToWishlist(<?php echo  $product['id'] ?>)"
                            class="w-12 h-12 border-2 border-gray-300 rounded-lg flex items-center justify-center hover:border-red-400 hover:text-red-500 transition-colors">
                        <i class="far fa-heart text-xl"></i>
                    </button>
                    <button type="button" onclick="shareProduct()"
                            class="w-12 h-12 border-2 border-gray-300 rounded-lg flex items-center justify-center hover:border-green-400 hover:text-green-500 transition-colors">
                        <i class="fas fa-share-alt text-xl"></i>
                    </button>
                </div>
            </form>

            <!-- Trust Badges -->
            <div class="flex flex-wrap gap-4 pt-4 border-t">
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-truck text-green-500 mr-2"></i>
                    Free Delivery
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-undo text-green-500 mr-2"></i>
                    30-Day Returns
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                    Secure Payment
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Popular Picks in this Category -->
<?php if (!empty($popularPicks)): ?>
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Popular Picks in this Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($popularPicks as $pick): ?>
                <?php
                    $pickImage = getProductMainImage($pick);
                    $pickPrice = isset($currencyService) ? $currencyService->formatPrice($pick['price']) : 'R ' . number_format($pick['price'], 2);
                ?>
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group border border-gray-100">
                    <a href="<?php echo  url('/product/' . htmlspecialchars($pick['slug'])) ?>" class="block">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <img src="<?php echo  htmlspecialchars($pickImage) ?>" alt="<?php echo  htmlspecialchars($pick['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='<?php echo  assetUrl('images/products/placeholder.png') ?>'">
                        </div>
                    </a>
                    <div class="p-4">
                        <a href="<?php echo  url('/product/' . htmlspecialchars($pick['slug'])) ?>" class="block">
                            <h3 class="text-sm text-gray-900 mb-2 line-clamp-2 hover:text-green-600"><?php echo  htmlspecialchars($pick['name']) ?></h3>
                        </a>
                        <?php echo  renderStarRating(rand(35, 50) / 10, false) ?>
                        <p class="text-lg font-semibold text-green-600 mt-2"><?php echo  $pickPrice ?></p>
                        <button onclick="quickAddToCart(<?php echo  $pick['id'] ?>)"
                                class="w-full mt-3 border-2 border-green-600 text-green-600 bg-transparent py-2 rounded-lg text-sm font-medium hover:bg-green-600 hover:text-white transition-colors">
                            Add to Cart
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Product Description -->
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Product Description</h2>
            <div class="prose max-w-none text-gray-700">
                <?php echo  nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
            </div>
        </div>
    </div>
</section>

<!-- You Might Also Like -->
<?php if (!empty($youMightLike)): ?>
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">You Might Also Like</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($youMightLike as $item): ?>
                <?php
                    $itemImage = getProductMainImage($item);
                    $itemPrice = isset($currencyService) ? $currencyService->formatPrice($item['price']) : 'R ' . number_format($item['price'], 2);
                ?>
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group border border-gray-100">
                    <a href="<?php echo  url('/product/' . htmlspecialchars($item['slug'])) ?>" class="block">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <img src="<?php echo  htmlspecialchars($itemImage) ?>" alt="<?php echo  htmlspecialchars($item['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='<?php echo  assetUrl('images/products/placeholder.png') ?>'">
                        </div>
                    </a>
                    <div class="p-4">
                        <a href="<?php echo  url('/product/' . htmlspecialchars($item['slug'])) ?>">
                            <h3 class="text-sm text-gray-900 mb-2 line-clamp-2 hover:text-green-600"><?php echo  htmlspecialchars($item['name']) ?></h3>
                        </a>
                        <?php echo  renderStarRating(rand(35, 50) / 10, false) ?>
                        <p class="text-lg font-semibold text-green-600 mt-2"><?php echo  $itemPrice ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Product Information -->
<?php if (!empty($customFields)): ?>
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Product Information</h2>
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($customFields as $field): ?>
                        <tr>
                            <td class="bg-gray-50 text-gray-700 font-medium px-4 py-3 w-1/3 md:w-1/4 whitespace-nowrap border-r border-gray-200"><?php echo htmlspecialchars($field['field_name']) ?></td>
                            <td class="bg-white text-gray-900 px-4 py-3"><?php echo htmlspecialchars($field['field_value']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Products</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($relatedProducts as $related): ?>
                <?php
                    $relatedImage = getProductMainImage($related);
                    $relatedPrice = isset($currencyService) ? $currencyService->formatPrice($related['price']) : 'R ' . number_format($related['price'], 2);
                ?>
                <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition-shadow overflow-hidden group border border-gray-100">
                    <a href="<?php echo  url('/product/' . htmlspecialchars($related['slug'])) ?>" class="block">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <img src="<?php echo  htmlspecialchars($relatedImage) ?>" alt="<?php echo  htmlspecialchars($related['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='<?php echo  assetUrl('images/products/placeholder.png') ?>'">
                        </div>
                    </a>
                    <div class="p-4">
                        <a href="<?php echo  url('/product/' . htmlspecialchars($related['slug'])) ?>">
                            <h3 class="text-sm text-gray-900 mb-2 line-clamp-2 hover:text-green-600"><?php echo  htmlspecialchars($related['name']) ?></h3>
                        </a>
                        <?php echo  renderStarRating(rand(35, 50) / 10, false) ?>
                        <p class="text-lg font-semibold text-green-600 mt-2"><?php echo  $relatedPrice ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Similar Items from Popular Brands -->
<?php if (!empty($brandProducts)): ?>
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Similar Items from Popular Brands</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($brandProducts as $brandItem): ?>
                <?php
                    $brandImage = getProductMainImage($brandItem);
                    $brandPrice = isset($currencyService) ? $currencyService->formatPrice($brandItem['price']) : 'R ' . number_format($brandItem['price'], 2);
                ?>
                <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition-shadow overflow-hidden group border border-gray-100">
                    <a href="<?php echo  url('/product/' . htmlspecialchars($brandItem['slug'])) ?>" class="block">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <img src="<?php echo  htmlspecialchars($brandImage) ?>" alt="<?php echo  htmlspecialchars($brandItem['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='<?php echo  assetUrl('images/products/placeholder.png') ?>'">
                            <?php if (!empty($brandItem['brand'])): ?>
                            <span class="absolute top-2 left-2 bg-white/90 text-xs font-medium px-2 py-1 rounded">
                                <?php echo  htmlspecialchars($brandItem['brand']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="p-4">
                        <a href="<?php echo  url('/product/' . htmlspecialchars($brandItem['slug'])) ?>">
                            <h3 class="text-sm text-gray-900 mb-2 line-clamp-2 hover:text-green-600"><?php echo  htmlspecialchars($brandItem['name']) ?></h3>
                        </a>
                        <p class="text-lg font-semibold text-green-600"><?php echo  $brandPrice ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Write a Review Section -->
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Write a Review</h2>
            <p class="text-gray-600 mb-6">Happy with your product? Share your thoughts with other customers.</p>

            <?php if ($isLoggedIn): ?>
            <div class="bg-gray-50 rounded-xl p-6">
                <form id="reviewForm" onsubmit="return submitReview(event)">
                    <input type="hidden" name="product_id" value="<?php echo  $product['id'] ?>">
                    <?php echo csrf_field(); ?>

                    <!-- Star Rating -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
                        <div class="flex gap-1" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" onclick="setRating(<?php echo  $i ?>)"
                                    class="star-btn text-3xl text-gray-300 hover:text-yellow-400 transition-colors">
                                <i class="far fa-star"></i>
                            </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0">
                    </div>

                    <!-- Review Title -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Review Title</label>
                        <input type="text" name="title" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="Summarize your review">
                    </div>

                    <!-- Review Content -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                        <textarea name="content" rows="4" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                  placeholder="Tell us about your experience with this product"></textarea>
                    </div>

                    <button type="submit"
                            class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Review
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="bg-gray-50 rounded-xl p-8 text-center">
                <i class="fas fa-user-circle text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 mb-4">Please log in to write a review.</p>
                <a href="<?php echo  userUrl('/login/?redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>"
                   class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                    Login to Review
                </a>
            </div>
            <?php endif; ?>

            <!-- Existing Reviews -->
            <?php if (!empty($reviews)): ?>
            <div class="mt-10">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Customer Reviews (<?php echo  count($reviews) ?>)</h3>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    <?php echo  htmlspecialchars(($review['first_name'] ?? 'Anonymous') . ' ' . substr($review['last_name'] ?? '', 0, 1) . '.') ?>
                                </p>
                                <?php echo  renderStarRating($review['rating'] ?? 5, false) ?>
                            </div>
                            <span class="text-sm text-gray-500">
                                <?php echo  date('M d, Y', strtotime($review['created_at'])) ?>
                            </span>
                        </div>
                        <?php if (!empty($review['title'])): ?>
                        <h4 class="font-semibold text-gray-900 mb-2"><?php echo  htmlspecialchars($review['title']) ?></h4>
                        <?php endif; ?>
                        <p class="text-gray-600"><?php echo  nl2br(htmlspecialchars($review['content'] ?? $review['review'] ?? '')) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="mt-10 bg-gray-50 rounded-xl p-8 text-center">
                <i class="fas fa-comments text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Customer Reviews</h3>
                <p class="text-gray-600 mb-4">No reviews yet. Be the first to review this product!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Often Bought Together -->
<?php if (!empty($oftenBought)): ?>
<section class="py-10">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Often Bought Together</h2>
            <div class="bg-gray-50 rounded-xl p-6">
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <!-- Current Product -->
                    <div class="text-center">
                        <div class="w-32 h-32 bg-gray-100 rounded-lg overflow-hidden mx-auto mb-2">
                            <img src="<?php echo  htmlspecialchars($mainImage) ?>" alt="<?php echo  htmlspecialchars($product['name']) ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <p class="text-sm text-gray-900 line-clamp-2"><?php echo  htmlspecialchars($product['name']) ?></p>
                        <p class="text-sm font-semibold text-green-600"><?php echo  $formattedDisplayPrice ?></p>
                    </div>

                    <?php
                    $bundleTotal = $displayPrice;
                    foreach ($oftenBought as $index => $bundleItem):
                        $bundleImage = getProductMainImage($bundleItem);
                        $bundlePrice = floatval($bundleItem['price']);
                        $bundleTotal += $bundlePrice;
                        $formattedBundlePrice = isset($currencyService) ? $currencyService->formatPrice($bundlePrice) : 'R ' . number_format($bundlePrice, 2);
                    ?>
                    <div class="text-2xl text-gray-400">+</div>
                    <div class="text-center">
                        <div class="w-32 h-32 bg-gray-100 rounded-lg overflow-hidden mx-auto mb-2">
                            <img src="<?php echo  htmlspecialchars($bundleImage) ?>" alt="<?php echo  htmlspecialchars($bundleItem['name']) ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='<?php echo  assetUrl('images/products/placeholder.png') ?>'">
                        </div>
                        <p class="text-sm text-gray-900 line-clamp-2"><?php echo  htmlspecialchars($bundleItem['name']) ?></p>
                        <p class="text-sm font-semibold text-green-600"><?php echo  $formattedBundlePrice ?></p>
                    </div>
                    <?php endforeach; ?>

                    <!-- Total & Add All -->
                    <div class="text-2xl text-gray-400">=</div>
                    <div class="text-center min-w-[150px]">
                        <?php
                        $formattedBundleTotal = isset($currencyService) ? $currencyService->formatPrice($bundleTotal) : 'R ' . number_format($bundleTotal, 2);
                        ?>
                        <p class="text-sm text-gray-500 mb-1">Bundle Price</p>
                        <p class="text-2xl font-bold text-green-600 mb-3"><?php echo  $formattedBundleTotal ?></p>
                        <button onclick="addBundleToCart([<?php echo  $product['id'] ?>, <?php echo  implode(',', array_column($oftenBought, 'id')) ?>])"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                            <i class="fas fa-cart-plus mr-2"></i>Add All to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Image Lightbox Modal -->
<div id="lightboxModal" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center p-4">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300">
        <i class="fas fa-times"></i>
    </button>
    <img id="lightboxImage" src="" alt="Product Image" class="max-w-full max-h-full object-contain">
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50">
    <span id="toastMessage"></span>
</div>

<script>
// Change main image
function changeMainImage(src, btn) {
    document.getElementById('mainProductImage').src = src;
    document.querySelectorAll('.thumbnail-btn').forEach(b => b.classList.remove('border-green-500'));
    btn.classList.add('border-green-500');
}

// Update quantity
function updateQuantity(delta) {
    const input = document.getElementById('quantity');
    const newVal = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    if (newVal >= 1 && newVal <= max) {
        input.value = newVal;
    }
}

// Handle add to cart
function handleAddToCart(e) {
    e.preventDefault();
    const form = document.getElementById('addToCartForm');
    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
    btn.disabled = true;

    fetch('<?php echo  url('/cart/add.php') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Added to cart!');
            updateCartCount(data.cartCount);
        } else {
            showToast(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        showToast('Error adding to cart', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });

    return false;
}

// Quick add to cart
function quickAddToCart(productId) {
    fetch('<?php echo  url('/cart/add.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId + '&quantity=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Added to cart!');
            updateCartCount(data.cartCount);
        } else {
            showToast(data.message || 'Error', 'error');
        }
    });
}

// Add bundle to cart
function addBundleToCart(productIds) {
    Promise.all(productIds.map(id =>
        fetch('<?php echo  url('/cart/add.php') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'product_id=' + id + '&quantity=1'
        }).then(r => r.json())
    ))
    .then(results => {
        const lastResult = results[results.length - 1];
        showToast('Bundle added to cart!');
        if (lastResult.cartCount) updateCartCount(lastResult.cartCount);
    });
}

// Add to wishlist
function addToWishlist(productId) {
    fetch('<?php echo  url('/') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add_to_wishlist&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.message || 'Added to wishlist!');
    });
}

// Share product
function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo  addslashes($product['name']) ?>',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        showToast('Link copied to clipboard!');
    }
}

// Star rating
function setRating(rating) {
    document.getElementById('ratingInput').value = rating;
    document.querySelectorAll('#starRating .star-btn').forEach((btn, index) => {
        const icon = btn.querySelector('i');
        if (index < rating) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            btn.classList.add('text-yellow-400');
            btn.classList.remove('text-gray-300');
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            btn.classList.remove('text-yellow-400');
            btn.classList.add('text-gray-300');
        }
    });
}

// Submit review
function submitReview(e) {
    e.preventDefault();
    const form = document.getElementById('reviewForm');
    const formData = new FormData(form);

    if (formData.get('rating') == '0') {
        showToast('Please select a rating', 'error');
        return false;
    }

    fetch('<?php echo  url('/product/submit_review.php') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Review submitted!');
            form.reset();
            setRating(0);
        } else {
            showToast(data.message || 'Error submitting review', 'error');
        }
    });

    return false;
}

// Lightbox
function openLightbox(src) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('lightboxModal').classList.remove('hidden');
    document.getElementById('lightboxModal').classList.add('flex');
}

function closeLightbox() {
    document.getElementById('lightboxModal').classList.add('hidden');
    document.getElementById('lightboxModal').classList.remove('flex');
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMessage');
    toastMsg.textContent = message;
    toast.className = toast.className.replace(/bg-\w+-600/g, type === 'error' ? 'bg-red-600' : 'bg-green-600');
    toast.classList.remove('translate-y-20', 'opacity-0');
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 3000);
}

// Update cart count
function updateCartCount(count) {
    document.querySelectorAll('.cart-count').forEach(el => el.textContent = count);
}

// Close lightbox on escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
