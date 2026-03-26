<?php
/**
 * Shop Listing Page - CannaBuddy
 * Displays all products with filtering and sorting capabilities
 */

// Load session helper FIRST for consistent session handling
require_once __DIR__ . '/../includes/session_helper.php';
ensureSessionStarted();

// Include database and URL helper
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/url_helper.php';
require_once __DIR__ . '/../includes/product_helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    $db = null;
    error_log("Database connection failed: " . $e->getMessage());
}

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000000; // Increased max limit

// Fetch products
$products = [];
$categories = [];

if ($db) {
    try {
        // Get all categories
        $stmt = $db->query("SELECT DISTINCT category_id FROM products WHERE category_id IS NOT NULL AND category_id != '' AND status = 'active'");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get category names
        $categoryNames = [];
        if (!empty($categories)) {
            $stmt = $db->query("SELECT DISTINCT id, name FROM categories WHERE id IN (" . implode(',', array_map('intval', $categories)) . ")");
            $categoryNames = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Build query based on filters
        $sql = "SELECT * FROM products WHERE status = 'active'";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND category_id = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Only apply price filter if user explicitly sets it or if we want to default safe
        $sql .= " AND price >= ? AND price <= ?";
        $params[] = $minPrice;
        $params[] = $maxPrice;
        
        // Sorting
        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY price DESC";
                break;
            case 'name_az':
                $sql .= " ORDER BY name ASC";
                break;
            case 'name_za':
                $sql .= " ORDER BY name DESC";
                break;
            case 'oldest':
                $sql .= " ORDER BY created_at ASC";
                break;
            default: // newest
                $sql .= " ORDER BY created_at DESC";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
             error_log("Shop: No products found. SQL: $sql Params: " . json_encode($params));
        }
        
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
    }
}

// Check login status
$isLoggedIn = isset($_SESSION['user_id']);
$currentUser = null;
if ($isLoggedIn) {
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

$pageTitle = "Shop";
$currentPage = "shop";
if (isset($_GET['sort']) && $_GET['sort'] === 'newest' && !isset($_GET['search']) && !isset($_GET['category'])) {
    $currentPage = "new-arrivals";
}

// Set SEO Meta Tags for Shop Page
$metaDescription = "Browse the JointBuddy collection of premium 3D printed cannabis accessories. Filter by category, price, and discover innovative grinders, trays, and more.";
$metaKeywords = "3d printed cannabis accessories shop, weed gear online south africa, JointBuddy grinders, custom rolling trays, jointbuddy.co.za";
$canonicalUrl = shopUrl('/');

include __DIR__ . '/../includes/header.php';
?>

<!-- Shop Hero Section -->
<div class="bg-gradient-to-r from-green-600 to-green-700 text-white py-12">
    <div class="container mx-auto px-4 max-w-7xl">
        <h1 class="text-4xl font-bold mb-4">Shop CannaBuddy</h1>
        <p class="text-green-100 text-lg">Discover our premium selection of cannabis accessories</p>
        
        <!-- Search Bar -->
        <form method="GET" class="mt-6 max-w-2xl">
            <div class="flex gap-2">
                <input type="text" 
                       name="search" 
                       value="<?php echo  htmlspecialchars($search) ?>"
                       placeholder="Search products..." 
                       class="flex-1 px-4 py-3 rounded-lg text-gray-900 focus:ring-2 focus:ring-green-400 focus:outline-none">
                <button type="submit" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-green-50 transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </div>
            <?php if (!empty($category)): ?>
                <input type="hidden" name="category" value="<?php echo  htmlspecialchars($category) ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="fas fa-filter mr-2 text-green-600"></i>Filters
                </h3>
                
                <form method="GET" id="filterForm">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo  htmlspecialchars($search) ?>">
                    <?php endif; ?>
                    
                    <!-- Categories -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-800 mb-3">Categories</h4>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="category" value="" 
                                       <?php echo  empty($category) ? 'checked' : '' ?>
                                       class="text-green-600 focus:ring-green-500"
                                       onchange="this.form.submit()">
                                <span class="ml-2 text-gray-700">All Categories</span>
                            </label>
                            <?php foreach ($categories as $catId): ?>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="category" value="<?php echo  htmlspecialchars($catId) ?>"
                                           <?php echo  $category === $catId ? 'checked' : '' ?>
                                           class="text-green-600 focus:ring-green-500"
                                           onchange="this.form.submit()">
                                    <span class="ml-2 text-gray-700"><?php echo  htmlspecialchars($categoryNames[$catId] ?? 'Category ' . $catId) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-800 mb-3">Price Range</h4>
                        <div class="flex gap-2 items-center">
                            <input type="number" 
                                   name="min_price" 
                                   value="<?php echo  $minPrice > 0 ? $minPrice : '' ?>"
                                   placeholder="Min" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-green-500 focus:border-green-500">
                            <span class="text-gray-500">-</span>
                            <input type="number" 
                                   name="max_price" 
                                   value="<?php echo  $maxPrice < 10000 ? $maxPrice : '' ?>"
                                   placeholder="Max" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                        <button type="submit" class="mt-3 w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                            Apply Price Filter
                        </button>
                    </div>
                    
                    <!-- Sort -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-800 mb-3">Sort By</h4>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" onchange="this.form.submit()">
                            <option value="newest" <?php echo  $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?php echo  $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="price_low" <?php echo  $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo  $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="name_az" <?php echo  $sort === 'name_az' ? 'selected' : '' ?>>Name: A to Z</option>
                            <option value="name_za" <?php echo  $sort === 'name_za' ? 'selected' : '' ?>>Name: Z to A</option>
                        </select>
                    </div>
                    
                    <!-- Clear Filters -->
                    <a href="<?php echo  shopUrl('/') ?>" class="block text-center text-green-600 hover:text-green-700 font-medium mt-4">
                        <i class="fas fa-times mr-1"></i>Clear All Filters
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="lg:w-3/4">
            <!-- Results Header -->
            <div class="flex justify-between items-center mb-6">
                <p class="text-gray-600">
                    Showing <span class="font-semibold text-gray-900"><?php echo  count($products) ?></span> products
                    <?php if (!empty($search)): ?>
                        for "<span class="font-semibold text-gray-900"><?php echo  htmlspecialchars($search) ?></span>"
                    <?php endif; ?>
                    <?php if (!empty($category)): ?>
                        in <span class="font-semibold text-gray-900"><?php echo  htmlspecialchars(ucfirst($category)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (empty($products)): ?>
                <!-- No Products Found -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-600 mb-6">Try adjusting your search or filter criteria</p>
                    <a href="<?php echo  shopUrl('/') ?>" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        View All Products
                    </a>
                </div>
            <?php else: ?>
                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $imagePath = getProductMainImage($product);
                        $isOnSale = !empty($product['on_sale']) && $product['on_sale'] == 1;
                        $salePrice = $product['sale_price'] ?? $product['price'];
                        $discount = $isOnSale && $product['price'] > $salePrice ? round((($product['price'] - $salePrice) / $product['price']) * 100) : 0;
                        $stock = $product['stock'] ?? 0;
                        ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group">
                            <!-- Product Image -->
                            <div class="relative h-56 bg-gray-100 overflow-hidden">
                                <?php if ($discount > 0): ?>
                                    <div class="absolute top-3 left-3 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold z-10">
                                        -<?php echo  $discount ?>%
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($product['featured'])): ?>
                                    <div class="absolute top-3 right-3 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold z-10">
                                        <i class="fas fa-star mr-1"></i>Featured
                                    </div>
                                <?php endif; ?>
                                <img src="<?php echo  htmlspecialchars($imagePath) ?>" alt="<?php echo  htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onError="this.onerror=null;this.src='https://via.placeholder.com/400x400/16a34a/ffffff?text=No+Image';">
                                
                                <!-- Quick Actions Overlay -->
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <a href="<?php echo  url('/product/' . htmlspecialchars($product['slug'])) ?>" 
                                       class="bg-white text-gray-900 px-4 py-2 rounded-full font-medium hover:bg-green-600 hover:text-white transition-colors">
                                        <i class="fas fa-eye mr-2"></i>Quick View
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 mt-1 mb-2 line-clamp-2">
                                    <a href="<?php echo  url('/product/' . htmlspecialchars($product['slug'])) ?>" class="hover:text-green-600 transition-colors">
                                        <?php echo  htmlspecialchars($product['name']) ?>
                                    </a>
                                </h3>
                                
                                <!-- Rating -->
                                <div class="flex items-center mb-3">
                                    <div class="flex text-yellow-400 text-sm">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <span class="text-gray-500 text-sm ml-2">(<?php echo  rand(10, 50) ?>)</span>
                                </div>
                                
                                <!-- Price -->
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <?php if (isset($currencyService)): ?>
                                            <?php if ($isOnSale && $discount > 0): ?>
                                                <span class="text-xl font-bold text-red-600"><?php echo  $currencyService->formatPrice($salePrice) ?></span>
                                                <span class="text-sm text-gray-400 line-through ml-2"><?php echo  $currencyService->formatPrice($product['price']) ?></span>
                                            <?php else: ?>
                                                <span class="text-xl font-bold text-green-600"><?php echo  $currencyService->formatPrice($product['price']) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($isOnSale && $discount > 0): ?>
                                                <span class="text-xl font-bold text-red-600">R <?php echo  number_format($salePrice, 2) ?></span>
                                                <span class="text-sm text-gray-400 line-through ml-2">R <?php echo  number_format($product['price'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-xl font-bold text-green-600">R <?php echo  number_format($product['price'], 2) ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($stock < 10 && $stock > 0): ?>
                                        <span class="text-xs text-orange-600 font-medium">Only <?php echo  $stock ?> left</span>
                                    <?php elseif ($stock == 0): ?>
                                        <span class="text-xs text-red-600 font-medium">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Add to Cart Button -->
                                <?php if ($stock > 0): ?>
                                    <form action="<?php echo  url('/cart/add.php') ?>" method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo  $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center justify-center">
                                            <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="w-full bg-gray-300 text-gray-500 py-3 rounded-lg font-semibold cursor-not-allowed">
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg transform translate-y-full opacity-0 transition-all duration-300 z-50">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-3 text-xl"></i>
        <span id="toast-message">Product added to cart!</span>
    </div>
</div>

<script>
// Add to cart AJAX
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        button.disabled = true;
        
        fetch('<?php echo  url('/cart/add.php') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Product added to cart!');
                // Update cart count in header
                const cartBadges = document.querySelectorAll('.cart-count');
                cartBadges.forEach(badge => {
                    badge.textContent = data.cartCount || parseInt(badge.textContent) + 1;
                });
            } else {
                showToast(data.message || 'Failed to add product', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
});

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    toastMessage.textContent = message;
    toast.className = toast.className.replace('translate-y-full opacity-0', 'translate-y-0 opacity-100');
    
    if (type === 'error') {
        toast.classList.remove('bg-green-600');
        toast.classList.add('bg-red-600');
    } else {
        toast.classList.remove('bg-red-600');
        toast.classList.add('bg-green-600');
    }
    
    setTimeout(() => {
        toast.className = toast.className.replace('translate-y-0 opacity-100', 'translate-y-full opacity-0');
    }, 3000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
