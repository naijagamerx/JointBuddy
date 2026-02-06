<?php
/**
 * Main Entry Point - CannaBuddy E-Commerce
 * Routes requests to appropriate handlers
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/includes/bootstrap.php';

// Include routing logic
require_once __DIR__ . '/route.php';

// Get the route from route.php
$route = $route ?? '';

// Initialize global variables from Services for backward compatibility
$db = Services::db();
$adminAuth = Services::adminAuth();
$userAuth = Services::userAuth();
$currencyService = Services::currencyService();

// Handle POST requests with CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation using middleware
    CsrfMiddleware::validate();

    // Handle user registration
    if ($route === 'register' && isset($_POST['email']) && isset($_POST['password'])) {
        if ($userAuth) {
            $userData = [
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? null
            ];

            $result = $userAuth->register($userData);

            if ($result['success']) {
                $_SESSION['registration_success'] = $result['message'];
            } else {
                $_SESSION['registration_error'] = $result['message'];
            }
        }
    }

    // Handle user login
    if ($route === 'user/login' && isset($_POST['email']) && isset($_POST['password'])) {
        if ($userAuth) {
            $result = $userAuth->login($_POST['email'], $_POST['password']);

            if ($result['success']) {
                header('Location: ' . url('user/'));
                exit;
            } else {
                $_SESSION['user_login_error'] = $result['message'];
            }
        }
    }

    // Handle Wishlist Add
    if ($route === 'wishlist/add' && isset($_POST['product_id'])) {
        header('Content-Type: application/json');

        if (!$isLoggedIn) {
            echo json_encode(['success' => false, 'message' => 'Please login to add to wishlist']);
            exit;
        }

        try {
            if ($db) {
                // Check if already in wishlist
                $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$currentUser['id'], $_POST['product_id']]);

                if ($stmt->fetch()) {
                    echo json_encode(['success' => true, 'message' => 'Already in wishlist']);
                } else {
                    $stmt = $db->prepare("INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$currentUser['id'], $_POST['product_id']]);
                    echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Handle admin logout
if ($route === 'admin/logout' || (isset($_GET['logout']) && $_GET['logout'] === 'admin')) {
    if ($adminAuth) {
        $adminAuth->logout();
    }
    header('Location: ' . adminUrl('login/'));
    exit;
}

// Check if user is admin for admin routes (excluding login page)
$isAdminRoute = in_array($route, [
    'admin',
    'admin/logout',
    'admin/products',
    'admin/products/add',
    'admin/products/edit',
    'admin/products/delete',
    'admin/products/inquiries',
    'admin/products/inventory',
    'admin/products/reviews',
    'admin/orders',
    'admin/orders/view',
    'admin/orders/update',
    'admin/users',
    'admin/users/view',
    'admin/categories',
    'admin/analytics',
    'admin/hero-images',
    'admin/settings',
    'admin/settings/appearance',
    'admin/settings/email',
    'admin/settings/notifications',
    'admin/profile',
    'admin/activity'
]);
$isAdminLoggedIn = AuthMiddleware::isAdminLoggedIn();

if ($isAdminRoute && !$isAdminLoggedIn) {
    // Redirect to admin login if not authenticated (exclude login page itself)
    header('Location: ' . adminUrl('login/'));
    exit;
}

// Check user authentication status for header
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

// HTML template function
function renderPage($title, $content) {
    global $pageTitle, $currentPage, $currentUser, $isLoggedIn, $db;
    $pageTitle = $title;
    if (!isset($currentPage)) {
        $currentPage = "";
    }

    ob_start();
    include __DIR__ . "/includes/header.php";
    $header = ob_get_clean();

    ob_start();
    include __DIR__ . "/includes/footer.php";
    $footer = ob_get_clean();
    $footer = preg_replace("/<\!DOCTYPE.*?<body[^>]*>/s", "", $footer);
    $footer = preg_replace("/<\/body>\s*<\/html>\s*$/s", "", $footer);

    return $header . $content . $footer;
}

// ============================================================================
// ROUTING - Generate content based on route
// ============================================================================

// Home page content
if ($route === 'home' || $route === '') {
    $currentPage = 'main';
    // Fetch homepage data
    $featuredProducts = [];
    $saleProducts = [];
    $sliderImages = [];
    $categories = [];
    $heroImages = [];
    $preHeroProducts = [];

    if ($db) {
        try {
            // Get categories
            $stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get hero images (16:9 slider with customization)
            $stmt = $db->prepare("SELECT * FROM hero_images WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 10");
            $stmt->execute();
            $heroImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get featured products (6)
            $stmt = $db->prepare("SELECT * FROM products WHERE featured = 1 AND status = 'active' ORDER BY id DESC LIMIT 6");
            $stmt->execute();
            $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get sale products (6)
            $stmt = $db->prepare("SELECT * FROM products WHERE on_sale = 1 AND status = 'active' ORDER BY id DESC LIMIT 6");
            $stmt->execute();
            $saleProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get slider images
            $stmt = $db->prepare("SELECT * FROM homepage_slider WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");
            $stmt->execute();
            $sliderImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get hero sections
            $stmt = $db->prepare("SELECT * FROM homepage_hero_sections WHERE is_active = 1 ORDER BY hero_number ASC");
            $stmt->execute();
            $heroSections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY id DESC LIMIT 8");
            $stmt->execute();
            $preHeroProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching homepage data: " . $e->getMessage());
        }
    }

    // Build slider HTML
    // Get settings from the first slide to apply to the whole slider
    $sliderHeight = '500px';
    $sliderPaddingTop = '0px';
    $sliderPaddingBottom = '0px';
    if (!empty($sliderImages) && isset($sliderImages[0])) {
        $firstSlide = $sliderImages[0];
        $sliderHeight = $firstSlide['slider_height'] ?? '500px';
        $sliderPaddingTop = $firstSlide['padding_top'] ?? '0';
        $sliderPaddingBottom = $firstSlide['padding_bottom'] ?? '0';
    }

    $sliderHTML = '
    <div class="relative w-full overflow-hidden group" style="height: ' . htmlspecialchars($sliderHeight) . '; padding-top: ' . htmlspecialchars($sliderPaddingTop) . '; padding-bottom: ' . htmlspecialchars($sliderPaddingBottom) . ';">
        <!-- Slides -->
        <div id="slider-track" class="flex transition-transform duration-700 ease-in-out h-full">';

    if (!empty($sliderImages)) {
        foreach ($sliderImages as $index => $slide) {
            $imagePath = !empty($slide['image_path']) ? htmlspecialchars(url($slide['image_path'])) : assetUrl('images/slider/slide1.png');
            $sliderHTML .= '
            <div class="min-w-full h-full relative">
                <img src="' . $imagePath . '" alt="' . htmlspecialchars($slide['title']) . '" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                    <div class="text-center text-white px-4">
                        <h2 class="text-4xl md:text-6xl font-bold mb-4 transform translate-y-4 opacity-0 transition-all duration-700 delay-300 slide-animate">' . htmlspecialchars($slide['title']) . '</h2>
                        <p class="text-xl md:text-2xl mb-8 transform translate-y-4 opacity-0 transition-all duration-700 delay-500 slide-animate">' . htmlspecialchars($slide['subtitle']) . '</p>
                        <a href="' . htmlspecialchars($slide['link_url']) . '" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full transition-all duration-300 transform translate-y-4 opacity-0 transition-all duration-700 delay-700 slide-animate inline-block">
                            Explore Now
                        </a>
                    </div>
                </div>
            </div>';
        }
    } else {
         // Fallback
         $sliderHTML .= '<div class="min-w-full h-full flex items-center justify-center bg-gray-200"><p>No slides available</p></div>';
    }

    $sliderHTML .= '
        </div>

        <!-- Indicators -->
        <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex space-x-3 z-10">
            ' . str_repeat('<button class="w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-100 transition-all duration-300 slider-indicator"></button>', count($sliderImages)) . '
        </div>

        <!-- Controls -->
        <button id="prev-slide" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white p-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <i class="fas fa-chevron-left text-2xl"></i>
        </button>
        <button id="next-slide" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white p-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <i class="fas fa-chevron-right text-2xl"></i>
        </button>
    </div>';

    // Build Categories HTML
    $categoriesHTML = '';
    if (!empty($categories)) {
        $categoriesHTML .= '
        <div class="py-12 bg-white border-b border-gray-100">
            <div class="container mx-auto px-4 max-w-7xl">
                <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Shop by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">';

        foreach ($categories as $cat) {
            $catImage = !empty($cat['image']) ? url($cat['image']) : assetUrl('images/categories/default.png');
            $categoriesHTML .= '
                <a href="' . shopUrl('/?category=' . htmlspecialchars($cat['slug'])) . '" class="group text-center block">
                    <div class="w-32 h-32 mx-auto rounded-full bg-gray-50 flex items-center justify-center mb-4 border border-gray-200 group-hover:border-green-500 group-hover:shadow-lg transition-all duration-300 overflow-hidden relative">
                         <!-- Fallback icon if no image -->
                         <div class="absolute inset-0 flex items-center justify-center text-gray-400 group-hover:text-green-500 transition-colors">
                              ' . (empty($cat['image']) ? '<i class="fas fa-cubes text-3xl"></i>' : '<img src="' . htmlspecialchars($catImage) . '" alt="' . htmlspecialchars($cat['name']) . '" class="w-full h-full object-cover">') . '
                         </div>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-green-600 transition-colors">' . htmlspecialchars($cat['name']) . '</h3>
                </a>';
        }

        $categoriesHTML .= '
                </div>
            </div>
        </div>';
    }

    // Build featured products HTML
    $featuredHTML = '
    <div class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Recommended For You</h2>
                    <p class="text-gray-600">Curated specifically for your taste</p>
                </div>
                <a href="' . shopUrl('') . '" class="text-green-600 font-semibold hover:text-green-700 transition-colors flex items-center">
                    View All <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">';

    if (!empty($featuredProducts)) {
        foreach ($featuredProducts as $product) {
            // Get product image - check 'images' field first, then fallback to 'image_1'
            $imagePath = assetUrl('images/products/placeholder.png');
            if (!empty($product['images'])) {
                // New format: comma-separated URLs
                $imageUrls = explode(',', $product['images']);
                $dbPath = trim($imageUrls[0]);
                // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
                $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                // Trim leading slash for url() helper if it's there
                $dbPath = ltrim($dbPath, '/');
                // Use path directly or convert to full URL using url() helper
                $imagePath = (strpos($dbPath, 'http') === 0) ? $dbPath : url($dbPath);
            } elseif (!empty($product['image_1'])) {
                // Legacy format: image_1 field
                $dbPath = $product['image_1'];
                // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
                $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                // Trim leading slash for url() helper if it's there
                $dbPath = ltrim($dbPath, '/');
                // Use path directly or convert to full URL using url() helper
                $imagePath = (strpos($dbPath, 'http') === 0) ? $dbPath : url($dbPath);
            }
            $featuredHTML .= '
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group transform hover:-translate-y-1">
                <div class="relative h-64 bg-gray-100 overflow-hidden cursor-pointer" onclick="window.location.href=\'' . productUrl($product['slug']) . '\'">
                    <img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['name']) . '"
                         class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                         onerror="this.onerror=null;this.src=\'https://via.placeholder.com/400x400/16a34a/ffffff?text=No+Image\';">

                    <!-- Quick Actions -->
                    <div class="absolute top-4 right-4 flex flex-col space-y-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform translate-x-4 group-hover:translate-x-0">
                        <button onclick="event.stopPropagation(); addToWishlist(' . $product['id'] . ')" class="bg-white text-gray-800 p-2 rounded-full shadow hover:bg-green-600 hover:text-white transition-colors" title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <button onclick="event.stopPropagation(); window.location.href=\'' . url('/product/' . htmlspecialchars($product['slug'])) . '\'" class="bg-white text-gray-800 p-2 rounded-full shadow hover:bg-green-600 hover:text-white transition-colors flex items-center justify-center" title="View Product">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>

                    ' . ((!empty($product['on_sale'])) ? '<div class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Sale</div>' : '') . '
                </div>

                <div class="p-6">
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400 text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="text-xs text-gray-400 ml-2">(24)</span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-1">
                        <a href="' . url('/product/' . htmlspecialchars($product['slug'])) . '" class="hover:text-green-600 transition-colors">
                            ' . htmlspecialchars($product['name']) . '
                        </a>
                    </h3>

                    <div class="flex justify-between items-center mt-4">
                        <div class="flex flex-col">
                            <span class="text-gray-400 text-xs uppercase tracking-wider font-medium">Price</span>
                            <span class="text-xl font-bold text-green-600">' . (isset($currencyService) ? $currencyService->formatPrice($product['price']) : 'R ' . number_format($product['price'], 2)) . '</span>
                        </div>
                        <button onclick="event.stopPropagation(); addToCart(' . $product['id'] . ', this)" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-600 transition-colors duration-300 flex items-center transform active:scale-95">
                            <i class="fas fa-shopping-bag mr-2"></i> Add
                        </button>
                    </div>
                </div>
            </div>';
        }
    } else {
        // Fallback or Placeholder Products
        for ($i = 1; $i <= 4; $i++) {
            $featuredHTML .= '
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                <div class="h-64 bg-gray-50 flex items-center justify-center relative overflow-hidden">
                    <span class="text-6xl group-hover:scale-110 transition-transform duration-500">📦</span>
                </div>
                <div class="p-6">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-6"></div>
                    <div class="flex justify-between items-center">
                        <div class="h-6 bg-gray-200 rounded w-1/3"></div>
                        <div class="h-8 bg-gray-200 rounded w-1/4"></div>
                    </div>
                </div>
            </div>';
        }
    }


    $featuredHTML .= '</div>
        </div>
    </div>';

    // Build Hero Section 1 HTML (Main banner after slider)
    $mainBannerHTML = '';
    // Get Hero Section 1 specifically
    $hero1 = null;
    foreach ($heroSections as $h) {
        if ($h['hero_number'] == 1) {
            $hero1 = $h;
            break;
        }
    }

    if ($hero1 && $hero1['is_active']) {
        $bgImage = !empty($hero1['background_image']) ? (strpos($hero1['background_image'], 'http') === 0 ? $hero1['background_image'] : url($hero1['background_image'])) : '';
        $bgImageStyle = !empty($bgImage) ? 'background-image: url(' . htmlspecialchars($bgImage) . ');' : '';
        $mainBannerHTML = '
    <div class="container mx-auto px-4 my-8 max-w-7xl">
        <div class="py-20 relative overflow-hidden rounded-3xl shadow-2xl" style="' . $bgImageStyle . ' background-size: cover; background-position: center;">
            <div class="absolute inset-0 bg-gray-900 ' . (empty($bgImage) ? '' : 'bg-opacity-40') . '"></div>
            <div class="relative z-10 text-center px-6">
                <h2 class="text-4xl font-bold text-white mb-4">' . htmlspecialchars($hero1['title']) . '</h2>
                <p class="text-xl text-gray-200 mb-8 max-w-2xl mx-auto">' . htmlspecialchars($hero1['subtitle']) . '</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="' . htmlspecialchars($hero1['button_link']) . '" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition-colors">
                        ' . htmlspecialchars($hero1['button_text']) . '
                    </a>
                </div>
            </div>
        </div>
    </div>';
    } else {
        // Fallback if no hero sections in database
        $mainBannerHTML = '
    <div class="container mx-auto px-4 my-8 max-w-7xl">
        <div class="py-20 bg-gray-900 relative overflow-hidden rounded-3xl shadow-2xl">
            <img src="' . assetUrl('images/main_banner.png') . '" alt="Join our community" class="absolute inset-0 w-full h-full object-cover opacity-60">
            <div class="relative z-10 text-center px-6">
                <h2 class="text-4xl font-bold text-white mb-4">Join our community</h2>
                <p class="text-xl text-gray-200 mb-8 max-w-2xl mx-auto">Get exclusive access to new products, limited editions, and member-only discounts.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="' . url('/register/') . '" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition-colors">
                        Sign Up Free
                    </a>
                    <a href="' . shopUrl('') . '" class="bg-white hover:bg-gray-100 text-gray-900 font-bold py-3 px-8 rounded-lg transition-colors">
                        Start Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>';
    }

    $preHeroHTML = '
    <div class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Latest Products</h2>
                    <p class="text-gray-600">Fresh picks added recently</p>
                </div>
                <a href="' . shopUrl('') . '" class="text-green-600 font-semibold hover:text-green-700 transition-colors flex items-center">
                    View All <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">';

    if (!empty($preHeroProducts)) {
        foreach ($preHeroProducts as $product) {
            // Get product image - check 'images' field first, then fallback to 'image_1'
            $imagePath = assetUrl('images/products/placeholder.png');
            if (!empty($product['images'])) {
                // New format: comma-separated URLs
                $imageUrls = explode(',', $product['images']);
                $dbPath = trim($imageUrls[0]);
                // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
                $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                // Trim leading slash for url() helper if it's there
                $dbPath = ltrim($dbPath, '/');
                // Use path directly or convert to full URL using url() helper
                $imagePath = (strpos($dbPath, 'http') === 0) ? $dbPath : url($dbPath);
            } elseif (!empty($product['image_1'])) {
                // Legacy format: image_1 field
                $dbPath = $product['image_1'];
                // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
                $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                // Trim leading slash for url() helper if it's there
                $dbPath = ltrim($dbPath, '/');
                // Use path directly or convert to full URL using url() helper
                $imagePath = (strpos($dbPath, 'http') === 0) ? $dbPath : url($dbPath);
            }

            $preHeroHTML .= '
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group transform hover:-translate-y-1">
                <div class="relative h-64 bg-gray-100 overflow-hidden cursor-pointer" onclick="window.location.href=\'' . productUrl($product['slug']) . '\'">
                    <img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['name']) . '"
                         class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                         onerror="this.onerror=null;this.src=\'https://via.placeholder.com/400x400/16a34a/ffffff?text=No+Image\';">
                    ' . ((!empty($product['on_sale'])) ? '<div class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Sale</div>' : '') . '
                </div>

                <div class="p-6">
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400 text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="text-xs text-gray-400 ml-2">(24)</span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-1">
                        <a href="' . url('/product/' . htmlspecialchars($product['slug'])) . '" class="hover:text-green-600 transition-colors">
                            ' . htmlspecialchars($product['name']) . '
                        </a>
                    </h3>

                    <div class="flex justify-between items-center mt-4">
                        <div class="flex flex-col">
                            <span class="text-gray-400 text-xs uppercase tracking-wider font-medium">Price</span>
                            <span class="text-xl font-bold text-green-600">' . (isset($currencyService) ? $currencyService->formatPrice($product['price']) : 'R ' . number_format($product['price'], 2)) . '</span>
                        </div>
                        <button onclick="addToCart(' . $product['id'] . ', this)" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-600 transition-colors duration-300 flex items-center transform active:scale-95">
                            <i class="fas fa-shopping-bag mr-2"></i> Add
                        </button>
                    </div>
                </div>
            </div>';
        }
    } else {
        for ($i = 1; $i <= 8; $i++) {
            $preHeroHTML .= '
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                <div class="h-64 bg-gray-50 flex items-center justify-center relative overflow-hidden">
                    <span class="text-6xl group-hover:scale-110 transition-transform duration-500">📦</span>
                </div>
                <div class="p-6">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-6"></div>
                    <div class="flex justify-between items-center">
                        <div class="h-6 bg-gray-200 rounded w-1/3"></div>
                        <div class="h-8 bg-gray-200 rounded w-1/4"></div>
                    </div>
                </div>
            </div>';
        }
    }

    $preHeroHTML .= '</div>
        </div>
    </div>';

    // Build Secondary Hero HTML (Hero Section 2)
    $heroHTML = '';
    // Get Hero Section 2 specifically
    $hero2 = null;
    foreach ($heroSections as $h) {
        if ($h['hero_number'] == 2) {
            $hero2 = $h;
            break;
        }
    }

    if ($hero2 && $hero2['is_active']) {
        $bgImage = !empty($hero2['background_image']) ? (strpos($hero2['background_image'], 'http') === 0 ? $hero2['background_image'] : url($hero2['background_image'])) : '';
        $bgImageStyle = !empty($bgImage) ? 'background-image: url(' . htmlspecialchars($bgImage) . ');' : '';
        $heroHTML = '
    <div class="py-12 bg-white border-b border-gray-100">
        <div class="container mx-auto px-4 my-8 max-w-7xl">
            <div class="relative w-full overflow-hidden rounded-3xl shadow-2xl mx-4" style="height: 500px;">
                <div class="w-full h-full relative" style="' . $bgImageStyle . ' background-size: cover; background-position: center;">
                    <div class="absolute inset-0 bg-gray-900 ' . (empty($bgImage) ? '' : 'bg-opacity-40') . '"></div>
                    <div class="relative z-10 h-full flex items-center justify-center">
                        <div class="text-center text-white px-6">
                            <h2 class="text-4xl md:text-6xl font-bold mb-4 hero-slide-animate opacity-0 translate-y-4 transition-all duration-700">' . htmlspecialchars($hero2['title'] ?? '') . '</h2>
                            <p class="text-xl md:text-2xl mb-8 hero-slide-animate opacity-0 translate-y-4 transition-all duration-700 delay-200 max-w-3xl">' . htmlspecialchars($hero2['subtitle'] ?? '') . '</p>
                            <a href="' . htmlspecialchars($hero2['button_link'] ?? '') . '" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full transition-all duration-300 hero-slide-animate opacity-0 translate-y-4 transition-all duration-700 delay-400 inline-block">
                                ' . htmlspecialchars($hero2['button_text'] ?? '') . '
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    }

    // Build sale products HTML
    $saleHTML = '
    <div class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Special Offers</h2>
                    <p class="text-gray-600">Don\'t Miss Out</p>
                </div>
                <a href="' . shopUrl('') . '" class="text-green-600 font-semibold hover:text-green-700 transition-colors flex items-center">
                    View All <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">';

    if (!empty($saleProducts)) {
        foreach ($saleProducts as $product) {
            // Get product image - check 'images' field first, then fallback to 'image_1'
            $imagePath = assetUrl('images/products/placeholder.png');
            if (!empty($product['images'])) {
                // New format: comma-separated URLs
                $imageUrls = explode(',', $product['images']);
                $dbPath = trim($imageUrls[0]);
                // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
                $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                // Trim leading slash for url() helper if it's there
                $dbPath = ltrim($dbPath, '/');
                // Use path directly or convert to full URL using url() helper
                $imagePath = (strpos($dbPath, 'http') === 0) ? $dbPath : url($dbPath);
            } elseif (!empty($product['image_1'])) {
                // Legacy format: image_1 field
                $dbPath = $product['image_1'];
                // Match full URLs like http://localhost/CannaBuddy.shop/... OR just absolute/relative paths
                $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
                $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
                // Trim leading slash for url() helper if it's there
                $dbPath = ltrim($dbPath, '/');
                // Use path directly or convert to full URL using url() helper
                $imagePath = (strpos($dbPath, 'http') === 0) ? $dbPath : url($dbPath);
            }
            $originalPrice = $product['price'];
            $salePrice = $product['sale_price'] ?: $product['price'];
            $discount = $originalPrice > $salePrice ? round((($originalPrice - $salePrice) / $originalPrice) * 100) : 0;

            $saleHTML .= '
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 group transform hover:-translate-y-1 relative">
                ' . ($discount > 0 ? '<div class="absolute top-4 right-4 bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide z-10 animate-bounce">-' . $discount . '% OFF</div>' : '') . '

                <div class="h-64 bg-gray-100 overflow-hidden relative cursor-pointer" onclick="window.location.href=\'' . url('/product/' . htmlspecialchars($product['slug'])) . '\'">
                     <img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['name']) . '"
                          class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                          onerror="this.onerror=null;this.src=\'https://via.placeholder.com/400x400/16a34a/ffffff?text=No+Image\';">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-1">
                        <a href="' . url('/product/' . htmlspecialchars($product['slug'])) . '" class="hover:text-green-600 transition-colors">
                            ' . htmlspecialchars($product['name']) . '
                        </a>
                    </h3>

                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400 text-sm">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <span class="text-sm text-gray-400 ml-2">(18)</span>
                    </div>

                    <div class="flex justify-between items-end mb-4">
                        <div class="flex flex-col">
                            <span class="text-gray-400 text-xs uppercase font-medium">Was ' . (isset($currencyService) ? $currencyService->formatPrice($originalPrice) : 'R ' . number_format($originalPrice, 2)) . '</span>
                            <span class="text-2xl font-bold text-red-600">' . (isset($currencyService) ? $currencyService->formatPrice($salePrice) : 'R ' . number_format($salePrice, 2)) . '</span>
                        </div>
                    </div>

                    <button onclick="event.stopPropagation(); addToCart(' . $product['id'] . ', this)" class="block w-full text-center bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-green-600 transition-colors duration-300 shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-shopping-bag mr-2"></i> Add to Cart
                    </button>
                </div>
            </div>';
        }
    } else {
        // Fallback for Sale
        for ($i = 1; $i <= 4; $i++) {
            $saleHTML .= '
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                <div class="h-64 bg-gray-50 flex items-center justify-center relative overflow-hidden">
                    <span class="text-6xl group-hover:scale-110 transition-transform duration-500">🎁</span>
                </div>
                <div class="p-6">
                    <div class="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-6"></div>
                    <div class="flex justify-between items-center">
                         <div class="h-8 bg-gray-200 rounded w-1/3"></div>
                    </div>
                </div>
            </div>';
        }
    }

    $saleHTML .= '</div>
        </div>
    </div>';

    // Build Features / Why Choose Us HTML (Reduced to just Newsletter wrapper basically)
    $bannersHTML = '
    <div class="py-12 bg-gray-50">
        <div class="container mx-auto px-4 max-w-7xl">




            <!-- Newsletter Signup -->
            <div class="bg-green-600 rounded-3xl p-8 md:p-12 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10"></div>
                <div class="relative z-10 max-w-2xl mx-auto">
                    <h2 class="text-3xl font-bold mb-4">Join the Inner Circle</h2>
                    <p class="text-green-100 mb-8 text-lg">Get notified about new drops, limited editions, and exclusive member-only deals.</p>
                    <form action="' . url('/newsletter/subscribe/') . '" method="POST" class="flex flex-col md:flex-row gap-4">
                        <input type="email" name="email" placeholder="Enter your email address" required
                               class="flex-1 px-6 py-4 rounded-xl text-gray-900 focus:outline-none focus:ring-4 focus:ring-green-400">
                        <button type="submit" class="bg-gray-900 text-white px-8 py-4 rounded-xl font-bold hover:bg-gray-800 transition-colors shadow-lg">
                            Subscribe
                        </button>
                    </form>
                    <p class="text-green-200 text-sm mt-4">We respect your privacy. No spam, ever.</p>
                </div>
            </div>
        </div>
    </div>';

    // Combine all sections (Including Categories)
    $content = $sliderHTML . $categoriesHTML . $featuredHTML . $mainBannerHTML . $preHeroHTML . $heroHTML . $saleHTML . $bannersHTML;

    // Add Slider Logic Script and Wishlist Script
    $content .= '
    <script>
    function addToWishlist(productId) {
        fetch(\'' . url('/wishlist/add') . '\', {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "product_id=" + productId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast("Added to wishlist!", "success");
            } else {
                showToast(data.message || "Could not add to wishlist", "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("An error occurred. Please try again.", "error");
        });
    }

    function addToCart(productId, btn) {
        // Disable button and show loading
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = \'<i class="fas fa-spinner fa-spin mr-2"></i> Adding...\';

        fetch(\'' . url('/cart/add.php') . '\', {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "product_id=" + productId + "&quantity=1"
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header
                updateCartCount(data.cartCount);
                showToast(data.message || "Added to cart!", "success");

                // Brief success state
                btn.innerHTML = \'<i class="fas fa-check mr-2"></i> Added!\';
                btn.classList.remove("bg-gray-900", "hover:bg-green-600");
                btn.classList.add("bg-green-600");

                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove("bg-green-600");
                    btn.classList.add("bg-gray-900", "hover:bg-green-600");
                    btn.disabled = false;
                }, 1500);
            } else {
                showToast(data.message || "Could not add to cart", "error");
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("An error occurred. Please try again.", "error");
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    function updateCartCount(count) {
        // Update all cart count badges on the page
        const cartBadges = document.querySelectorAll(".cart-count, #cart-count, [data-cart-count]");
        cartBadges.forEach(badge => {
            badge.textContent = count;
            if (count > 0) {
                badge.classList.remove("hidden");
            }
        });
    }

    function showToast(message, type) {
        // Remove existing toast
        const existingToast = document.getElementById("toast-notification");
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast element
        const toast = document.createElement("div");
        toast.id = "toast-notification";
        toast.className = "fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-y-full opacity-0";
        toast.className += type === "success" ? " bg-green-600 text-white" : " bg-red-600 text-white";
        toast.innerHTML = \'<div class="flex items-center"><i class="fas \' + (type === "success" ? "fa-check-circle" : "fa-exclamation-circle") + \' mr-2"></i>\' + message + \'</div>\';

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove("translate-y-full", "opacity-0");
            toast.classList.add("translate-y-0", "opacity-100");
        }, 10);

        // Auto hide after 3 seconds
        setTimeout(() => {
            toast.classList.add("translate-y-full", "opacity-0");
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const track = document.getElementById("slider-track");
        const slides = track.children;
        const indicators = document.querySelectorAll(".slider-indicator");
        const prevBtn = document.getElementById("prev-slide");
        const nextBtn = document.getElementById("next-slide");
        let currentIndex = 0;
        const totalSlides = slides.length;
        let autoSlideInterval;

        function updateSlider() {
            track.style.transform = `translateX(-${currentIndex * 100}%)`;

            // Update indicators
            indicators.forEach((ind, i) => {
                if (i === currentIndex) {
                    ind.classList.remove("bg-opacity-50");
                    ind.classList.add("bg-opacity-100", "scale-125");
                } else {
                    ind.classList.add("bg-opacity-50");
                    ind.classList.remove("bg-opacity-100", "scale-125");
                }
            });

            // Trigger animations
            const activeSlide = slides[currentIndex];
            const animatedElements = activeSlide.querySelectorAll(".slide-animate");
            animatedElements.forEach(el => {
                el.classList.remove("opacity-0", "translate-y-4");
                el.classList.add("opacity-100", "translate-y-0");
            });

            // Reset other slides
            Array.from(slides).forEach((slide, i) => {
                if (i !== currentIndex) {
                    const elements = slide.querySelectorAll(".slide-animate");
                    elements.forEach(el => {
                        el.classList.add("opacity-0", "translate-y-4");
                        el.classList.remove("opacity-100", "translate-y-0");
                    });
                }
            });
        }

        function nextSlide() {
            currentIndex = (currentIndex + 1) % totalSlides;
            updateSlider();
        }

        function prevSlide() {
            currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
            updateSlider();
        }

        // Event Listeners
        if(nextBtn) nextBtn.addEventListener("click", () => {
             nextSlide();
             resetAutoSlide();
        });

        if(prevBtn) prevBtn.addEventListener("click", () => {
             prevSlide();
             resetAutoSlide();
        });

        indicators.forEach((ind, i) => {
            ind.addEventListener("click", () => {
                currentIndex = i;
                updateSlider();
                resetAutoSlide();
            });
        });

        function startAutoSlide() {
            autoSlideInterval = setInterval(nextSlide, 5000);
        }

        function resetAutoSlide() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }

        // Initialize
        updateSlider();
        startAutoSlide();

        // Initialize hero image animations
        const heroSlides = document.querySelectorAll(".hero-slide-animate");
        if (heroSlides.length > 0) {
            // Animate hero slides on page load
            setTimeout(() => {
                heroSlides.forEach(el => {
                    el.classList.remove("opacity-0", "translate-y-4");
                    el.classList.add("opacity-100", "translate-y-0");
                });
            }, 300);
        }
    });
    </script>';

    echo renderPage('Home', $content);
    exit;
}

// Legal Pages
if ($route === 'terms') {
    echo renderPage('Terms and Conditions', getTermsContent());
    exit;
}
if ($route === 'privacy') {
    echo renderPage('Privacy Policy', getPrivacyContent());
    exit;
}
if ($route === 'refund-policy') {
    echo renderPage('Refund Policy', getRefundPolicyContent());
    exit;
}

// Shop page
if ($route === 'shop') {
    $content = '
    <div style="padding: 60px 20px; background: #f9fafb;">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: bold; color: #1f2937; margin-bottom: 20px;">
                Shop <span style="color: #16a34a;">Our Store</span>
            </h1>
            <p style="font-size: 1.25rem; color: #6b7280; margin-bottom: 40px;">
                Browse our curated selection of premium cannabis products.
            </p>
            <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <p style="color: #4b5563; font-size: 1.1rem;">Shop feature coming soon!</p>
            </div>
        </div>
    </div>';

    echo renderPage('Shop', $content);
    exit;
}

// Include extended Admin Routes
// Pass necessary services to admin routes scope
$adminRoutesCurrencyService = $currencyService;
require_once __DIR__ . '/includes/admin_routes.php';



// Admin Dashboard
if ($route === 'admin') {
    if (!AuthMiddleware::isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login/'));
        exit;
    }

    // Get basic stats
    $totalProducts = 0;
    $totalOrders = 0;
    $recentOrders = [];

    if ($db) {
        try {
            // Get total products
            $stmt = $db->query("SELECT COUNT(*) as count FROM products");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalProducts = $result['count'] ?? 0;

            // Get total orders
            $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalOrders = $result['count'] ?? 0;

            // Get recent orders
            $stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting admin stats: " . $e->getMessage());
        }
    }

    $adminName = $_SESSION['admin_username'] ?? 'Administrator';

    $content = '
    <div class="w-full max-w-7xl mx-auto">
        <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600">Welcome back, ' . htmlspecialchars($adminName) . '</p>
            </div>

            <!-- Stats Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 50px; height: 50px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                            <span style="font-size: 1.5rem;">📦</span>
                        </div>
                        <div>
                            <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 4px;">Total Products</p>
                            <p style="font-size: 2rem; font-weight: bold; color: #1f2937;">' . $totalProducts . '</p>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 50px; height: 50px; background: #dbeafe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                            <span style="font-size: 1.5rem;">🛒</span>
                        </div>
                        <div>
                            <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 4px;">Total Orders</p>
                            <p style="font-size: 2rem; font-weight: bold; color: #1f2937;">' . $totalOrders . '</p>
                        </div>
                    </div>
                </div>

                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 50px; height: 50px; background: #fef3c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                            <span style="font-size: 1.5rem;">⚡</span>
                        </div>
                        <div>
                            <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 4px;">Quick Actions</p>
                            <a href="' . adminUrl('products/add') . '" style="color: #16a34a; font-weight: 600; text-decoration: none;">Add Product</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">Management</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <a href="' . adminUrl('products') . '" style="display: block; padding: 16px; background: #f9fafb; border-radius: 8px; text-decoration: none; color: #374151; border: 2px solid transparent; transition: all 0.3s;">
                        <div style="display: flex; align-items: center;">
                            <span style="margin-right: 12px; font-size: 1.25rem;">📦</span>
                            <div>
                                <div style="font-weight: 600;">Products</div>
                                <div style="font-size: 0.85rem; color: #6b7280;">Manage your products</div>
                            </div>
                        </div>
                    </a>

                    <a href="' . adminUrl('orders') . '" style="display: block; padding: 16px; background: #f9fafb; border-radius: 8px; text-decoration: none; color: #374151; border: 2px solid transparent; transition: all 0.3s;">
                        <div style="display: flex; align-items: center;">
                            <span style="margin-right: 12px; font-size: 1.25rem;">🛒</span>
                            <div>
                                <div style="font-weight: 600;">Orders</div>
                                <div style="font-size: 0.85rem; color: #6b7280;">View and manage orders</div>
                            </div>
                        </div>
                    </a>

                    <a href="' . adminUrl('users') . '" style="display: block; padding: 16px; background: #f9fafb; border-radius: 8px; text-decoration: none; color: #374151; border: 2px solid transparent; transition: all 0.3s;">
                        <div style="display: flex; align-items: center;">
                            <span style="margin-right: 12px; font-size: 1.25rem;">👥</span>
                            <div>
                                <div style="font-weight: 600;">Users</div>
                                <div style="font-size: 0.85rem; color: #6b7280;">Manage customers</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Orders -->
            <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">Recent Orders</h2>';

    if (empty($recentOrders)) {
        $content .= '<p style="color: #6b7280; text-align: center; padding: 20px;">No orders yet.</p>';
    } else {
        $content .= '<div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Order ID</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Customer</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Total</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Status</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Date</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Action</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($recentOrders as $order) {
            $statusColors = [
                'pending' => '#fbbf24',
                'processing' => '#3b82f6',
                'shipped' => '#8b5cf6',
                'delivered' => '#10b981',
                'cancelled' => '#ef4444'
            ];
            $statusColor = $statusColors[$order['status']] ?? '#6b7280';

            $content .= '<tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px; font-weight: 600;">#' . htmlspecialchars($order['id']) . '</td>
                <td style="padding: 12px;">' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</td>
                <td style="padding: 12px;">$' . number_format($order['total_amount'] ?? 0, 2) . '</td>
                <td style="padding: 12px;">
                    <span style="background: ' . $statusColor . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; text-transform: capitalize;">' . htmlspecialchars($order['status']) . '</span>
                </td>
                <td style="padding: 12px; color: #6b7280;">' . date('M j, Y', strtotime($order['created_at'] ?? 'now')) . '</td>
                <td style="padding: 12px;">
                    <a href="' . adminUrl('orders/view/' . $order['id']) . '" style="color: #16a34a; text-decoration: none; font-weight: 600;">View</a>
                </td>
            </tr>';
        }

        $content .= '</tbody>
            </table>
        </div>';
    }

    $content .= '</div>';

    echo renderAdminPage('Dashboard', $content, 'dashboard');
    exit;
}

// Admin Products Page
if ($route === 'admin/products') {
    if (!AuthMiddleware::isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login/'));
        exit;
    }

    $products = [];
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting products: " . $e->getMessage());
        }
    }

    $content = '
    <div class="w-full max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Products</h1>
                    <p class="text-gray-600">Manage your product catalog</p>
                </div>
                <div>
                    <a href="' . adminUrl('products/add') . '" style="background: #16a34a; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">+ Add Product</a>
                </div>
            </div>

            <!-- Products Table -->
            <form method="POST" action="' . url('admin/products/bulk-update') . '">
                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 12px;">
                    <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937;">All Products (' . count($products) . ')</h2>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <select name="action" aria-label="Bulk action" style="border:1px solid #e5e7eb; border-radius:8px; padding:6px;">
                            <option value="">Bulk actions</option>
                            <option value="set_active">Set Active</option>
                            <option value="set_inactive">Set Inactive</option>
                            <option value="increase_price_percent">Increase Price %</option>
                            <option value="decrease_price_percent">Decrease Price %</option>
                            <option value="set_category">Set Category</option>
                        </select>
                        <input type="number" name="percent" placeholder="%" step="0.1" style="border:1px solid #e5e7eb; border-radius:8px; padding:6px; width:90px;">
                        <input type="text" name="category" placeholder="Category" style="border:1px solid #e5e7eb; border-radius:8px; padding:6px;">
                        <button type="submit" style="background:#16a34a;color:white;padding:8px 12px;border-radius:8px;font-weight:600;">Apply</button>
                    </div>
                </div>';

    if (empty($products)) {
        $content .= '<div style="text-align: center; padding: 40px; color: #6b7280;">
                    <p>No products found.</p>
                    <a href="' . adminUrl('products/add') . '" style="color: #16a34a; text-decoration: none; font-weight: 600;">Add your first product</a>
                </div>';
    } else {
        $content .= '<div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Product</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Price</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Stock</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Status</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Date</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Actions</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($products as $product) {
            $stockColor = ($product['stock'] ?? 0) < 10 ? '#fbbf24' : '#10b981';
            $statusColor = ($product['active'] ?? 0) ? '#10b981' : '#ef4444';

            $content .= '<tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px;">
                    <input type="checkbox" name="selected[]" value="' . htmlspecialchars($product['slug'] ?? '') . '" aria-label="Select product">
                </td>
                <td style="padding: 12px;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; margin-right: 12px; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 1.25rem;">📦</span>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #1f2937;">' . htmlspecialchars($product['name'] ?? 'N/A') . '</div>
                            <div style="font-size: 0.85rem; color: #6b7280;">' . htmlspecialchars($product['slug'] ?? '') . '</div>
                        </div>
                    </div>
                </td>
                <td style="padding: 12px; font-weight: 600;">$' . number_format($product['price'] ?? 0, 2) . '</td>
                <td style="padding: 12px;">
                    <span style="color: ' . $stockColor . '; font-weight: 600;">' . ($product['stock'] ?? 0) . ' units</span>
                </td>
                <td style="padding: 12px;">
                    <span style="background: ' . $statusColor . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase;">' . ((($product['active'] ?? 0) ? 'Active' : 'Inactive')) . '</span>
                </td>
                <td style="padding: 12px; color: #6b7280;">' . date('M j, Y', strtotime($product['created_at'] ?? 'now')) . '</td>
                <td style="padding: 12px;">
                    <a href="' . adminUrl('products/view/' . ($product['slug'] ?? '')) . '" style="color: #374151; text-decoration: none; font-weight: 600; margin-right: 10px;">View</a>
                    <a href="' . adminUrl('products/edit/' . ($product['slug'] ?? '')) . '" style="color: #16a34a; text-decoration: none; font-weight: 600; margin-right: 10px;">Edit</a>
                    <a href="' . adminUrl('products/delete/' . ($product['slug'] ?? '')) . '" style="color: #ef4444; text-decoration: none; font-weight: 600;" onclick="return confirm(\"Are you sure?\")">Delete</a>
                </td>
            </tr>';
        }

        $content .= '</tbody>
            </table>
        </div></div></form>
        <script>var sa=document.getElementById("selectAll");if(sa){sa.addEventListener("change",function(){document.querySelectorAll("input[name=\"selected[]\"]").forEach(function(cb){cb.checked=sa.checked;});});}</script>';
    }

    $content .= '</div>';

    echo renderAdminPage('Products', $content, 'products');
    exit;
}

// Admin Orders Page
if ($route === 'admin/orders') {
    if (!AuthMiddleware::isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login/'));
        exit;
    }

    $orders = [];
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting orders: " . $e->getMessage());
        }
    }

    $content = '
    <div class="w-full max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Orders</h1>
                    <p class="text-gray-600">Manage customer orders</p>
                </div>
            </div>

            <!-- Orders Table -->
            <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">All Orders (' . count($orders) . ')</h2>';

    if (empty($orders)) {
        $content .= '<div style="text-align: center; padding: 40px; color: #6b7280;">
                    <p>No orders found.</p>
                </div>';
    } else {
        $content .= '<div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Order ID</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Customer</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Total</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Status</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Date</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Actions</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($orders as $order) {
            $statusColors = [
                'pending' => '#fbbf24',
                'processing' => '#3b82f6',
                'shipped' => '#8b5cf6',
                'delivered' => '#10b981',
                'cancelled' => '#ef4444'
            ];
            $statusColor = $statusColors[$order['status']] ?? '#6b7280';

            $content .= '<tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px; font-weight: 600;">#' . htmlspecialchars($order['id']) . '</td>
                <td style="padding: 12px;">' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</td>
                <td style="padding: 12px; font-weight: 600;">$' . number_format($order['total_amount'] ?? 0, 2) . '</td>
                <td style="padding: 12px;">
                    <span style="background: ' . $statusColor . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; text-transform: capitalize;">' . htmlspecialchars($order['status']) . '</span>
                </td>
                <td style="padding: 12px; color: #6b7280;">' . date('M j, Y g:i A', strtotime($order['created_at'] ?? 'now')) . '</td>
                <td style="padding: 12px;">
                    <a href="' . adminUrl('orders/view/' . $order['id']) . '" style="color: #16a34a; text-decoration: none; font-weight: 600;">View Details</a>
                </td>
            </tr>';
        }

        $content .= '</tbody>
            </table>
        </div>';
    }

    $content .= '</div>';

    echo renderAdminPage('Orders', $content, 'orders');
    exit;
}

// Order Detail View
if (preg_match('/^admin\/orders\/view\/(\d+)$/', $route, $matches)) {
    if (!AuthMiddleware::isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login/'));
        exit;
    }

    $orderId = $matches[1];
    $order = null;
    $orderItems = [];

    if ($db) {
        try {
            // Get order
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Get order items
                $stmt = $db->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmt->execute([$orderId]);
                $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error getting order details: " . $e->getMessage());
        }
    }

    if (!$order) {
        $content = '
        <div style="padding: 20px; background: #f9fafb; min-height: 100vh;">
            <div style="max-width: 1200px; margin: 0 auto;">
                <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <h1 style="font-size: 2rem; font-weight: bold; color: #1f2937; margin-bottom: 20px;">Order Not Found</h1>
                    <p style="color: #6b7280; margin-bottom: 30px;">The order you\'re looking for doesn\'t exist.</p>
                    <a href="' . adminUrl('orders') . '" style="background: #16a34a; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600;">← Back to Orders</a>
                </div>
            </div>
        </div>';
        echo renderAdminPage('Order Not Found', $content, 'orders');
        exit;
    }

    $content = '
    <div style="padding: 20px; background: #f9fafb; min-height: 100vh;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <!-- Header -->
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: bold; color: #1f2937; margin-bottom: 5px;">Order #' . htmlspecialchars($order['id']) . '</h1>
                    <p style="color: #6b7280;">Order Details</p>
                </div>
                <div>
                    <a href="' . adminUrl('orders') . '" style="background: #6b7280; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">← Back to Orders</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- Order Info -->
                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">Order Information</h2>
                    <div style="display: grid; gap: 12px;">
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Order ID</label>
                            <p style="color: #1f2937; font-weight: 600;">#' . htmlspecialchars($order['id']) . '</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Order Date</label>
                            <p style="color: #1f2937;">' . date('F j, Y g:i A', strtotime($order['created_at'])) . '</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Status</label>
                            <span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; text-transform: capitalize;">' . htmlspecialchars($order['status']) . '</span>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Total Amount</label>
                            <p style="color: #1f2937; font-weight: 600; font-size: 1.25rem;">$' . number_format($order['total_amount'], 2) . '</p>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">Customer Information</h2>
                    <div style="display: grid; gap: 12px;">
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Name</label>
                            <p style="color: #1f2937;">' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Email</label>
                            <p style="color: #1f2937;">' . htmlspecialchars($order['customer_email'] ?? 'N/A') . '</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Phone</label>
                            <p style="color: #1f2937;">' . htmlspecialchars($order['customer_phone'] ?? 'N/A') . '</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #6b7280; font-size: 0.9rem;">Shipping Address</label>
                            <p style="color: #1f2937;">' . nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')) . '</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">Order Items (' . count($orderItems) . ')</h2>';

    if (empty($orderItems)) {
        $content .= '<p style="color: #6b7280; text-align: center; padding: 20px;">No items found for this order.</p>';
    } else {
        $content .= '<div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Product</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Price</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Quantity</th>
                        <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($orderItems as $item) {
            $content .= '<tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 12px;">' . htmlspecialchars($item['product_name'] ?? 'N/A') . '</td>
                <td style="padding: 12px;">$' . number_format($item['price'], 2) . '</td>
                <td style="padding: 12px;">' . htmlspecialchars($item['quantity']) . '</td>
                <td style="padding: 12px; font-weight: 600;">$' . number_format($item['price'] * $item['quantity'], 2) . '</td>
            </tr>';
        }

        $content .= '</tbody>
            </table>
        </div>';
    }

    $content .= '</div>
        </div>
    </div>';

    echo renderAdminPage('Order #' . htmlspecialchars($order['id'] ?? ''), $content, 'orders');
    exit;
}

// User Detail View
if (preg_match('/^admin\/users\/view\/(\d+)$/', $route, $matches)) {
    if (!AuthMiddleware::isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login/'));
        exit;
    }

    $userId = $matches[1];

    // Include the user detail view page
    require __DIR__ . '/admin/users/view.php';
    exit;
}

// About page
if ($route === 'about') {
    $content = '
    <div style="padding: 60px 20px; background: white;">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1 style="font-size: 2.5rem; font-weight: bold; color: #1f2937; margin-bottom: 30px; text-align: center;">
                About <span style="color: #16a34a;">Our Marketplace</span>
            </h1>
            <div style="background: #f9fafb; padding: 30px; border-radius: 12px;">
                <p style="color: #4b5563; font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px;">
                    Our marketplace connects consumers with quality products from verified vendors.
                </p>
                <p style="color: #4b5563; font-size: 1.1rem; line-height: 1.8;">
                    We prioritize safety, quality, and discretion in every transaction.
                </p>
            </div>
        </div>
    </div>';

    echo renderPage('About Us', $content);
    exit;
}

// Contact page
if ($route === 'contact') {
    $content = '
    <div style="padding: 60px 20px; background: #f9fafb;">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1 style="font-size: 2.5rem; font-weight: bold; color: #1f2937; margin-bottom: 30px; text-align: center;">
                Contact <span style="color: #16a34a;">Us</span>
            </h1>
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <p style="color: #4b5563; font-size: 1.1rem;">
                    Get in touch with our team for any questions or support.
                </p>
                <p style="color: #6b7280; margin-top: 20px;">
                    Email: info@cannabuddy.com
                </p>
            </div>
        </div>
    </div>';

    echo renderPage('Contact Us', $content);
    exit;
}

// ============================================================================
// Registration page
// ============================================================================
if ($route === 'register') {
    // Include registration template
    require_once __DIR__ . '/templates/registration_template.php';

    // If there's a success message, redirect to login
    if (isset($_SESSION['registration_success'])) {
        $success = $_SESSION['registration_success'];
        unset($_SESSION['registration_success']);
        header('Location: ' . url('user/login/?message=registered'));
        exit;
    }

    // Render registration page
    $content = renderRegistrationPage();
    echo $content;
    exit;
}

// ============================================================================
// FILE-BASED ROUTING - Check for files in user directory
// ============================================================================

// Check if route starts with 'user/' and try to include the corresponding file
if (strpos($route, 'user/') === 0) {
    // Remove 'user/' prefix to get the path within user directory
    $userRoute = substr($route, 5);

    // Handle trailing slashes
    $userRoute = trim($userRoute, '/');

    // Try to find and include the file
    // First check if it's a directory with index.php
    if (empty($userRoute)) {
        // Root user path - try user/index.php
        if (file_exists(__DIR__ . '/user/index.php')) {
            include __DIR__ . '/user/index.php';
            exit;
        }
    } else {
        // Check for directory/index.php
        $filePath = __DIR__ . '/user/' . $userRoute . '/index.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }

        // Check for direct PHP file
        $filePath = __DIR__ . '/user/' . $userRoute . '.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }
    }
}

// Check if route starts with 'product/' and try to include the corresponding file
if (strpos($route, 'product/') === 0) {
    // Remove 'product/' prefix to get the path within product directory
    $productRoute = substr($route, 8);

    // Handle trailing slashes
    $productRoute = trim($productRoute, '/');

    // Try to find and include the file
    // First check if it's a directory with index.php
    if (empty($productRoute)) {
        // Root product path - try product/index.php
        if (file_exists(__DIR__ . '/product/index.php')) {
            include __DIR__ . '/product/index.php';
            exit;
        }
    } else {
        // Check for directory/index.php
        $filePath = __DIR__ . '/product/' . $productRoute . '/index.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }

        // Check for direct PHP file
        $filePath = __DIR__ . '/product/' . $productRoute . '.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }
    }
}

// ============================================================================
// SPECIAL ROUTING - Handle dynamic admin routes
// ============================================================================

// Handle admin/products/view/{slug}
if (preg_match('#^admin/products/view/(.+)$#', $route, $matches)) {
    if (file_exists(__DIR__ . '/admin/products/view/index.php')) {
        $_GET['slug'] = $matches[1];
        include __DIR__ . '/admin/products/view/index.php';
        exit;
    }
}

// Handle admin/products/edit/{slug}
if (preg_match('#^admin/products/edit/(.+)$#', $route, $matches)) {
    if (file_exists(__DIR__ . '/admin/products/edit/index.php')) {
        include __DIR__ . '/admin/products/edit/index.php';
        exit;
    }
}

// Handle admin/products/delete/{slug}
if (preg_match('#^admin/products/delete/(.+)$#', $route, $matches)) {
    if (file_exists(__DIR__ . '/admin/products/delete/index.php')) {
        include __DIR__ . '/admin/products/delete/index.php';
        exit;
    }
}

// ============================================================================
// FILE-BASED ROUTING - Check for files in admin directory
// ============================================================================

// Check if route starts with 'admin/' and try to include the corresponding file
if (strpos($route, 'admin/') === 0) {
    // Remove 'admin/' prefix to get the path within admin directory
    $adminRoute = substr($route, 6);

    // Handle trailing slashes
    $adminRoute = trim($adminRoute, '/');

    // Check if admin is logged in for admin routes (except login)
    if (!AuthMiddleware::isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login/'));
        exit;
    }

    // Try to find and include the file
    // First check if it's a directory with index.php
    if (empty($adminRoute)) {
        // Root admin path - try admin/index.php
        if (file_exists(__DIR__ . '/admin/index.php')) {
            include __DIR__ . '/admin/index.php';
            exit;
        }
    } else {
        // Check for directory/index.php
        $filePath = __DIR__ . '/admin/' . $adminRoute . '/index.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }

        // Check for direct PHP file
        $filePath = __DIR__ . '/admin/' . $adminRoute . '.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }
    }
}

// Default 404
$content = '
<div style="padding: 60px 20px; background: #f9fafb;">
    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
        <h1 style="font-size: 3rem; font-weight: bold; color: #1f2937; margin-bottom: 20px;">
            404 - Page Not Found
        </h1>
        <p style="font-size: 1.25rem; color: #6b7280; margin-bottom: 30px;">
            The page you\'re looking for doesn\'t exist.
        </p>
        <a href="' . url('/') . '" style="background: #16a34a; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600;">
            Go Home
        </a>
    </div>
</div>';

echo renderPage('Page Not Found', $content);
