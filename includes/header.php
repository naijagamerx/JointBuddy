<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Get Favicon from database directly
    $faviconUrl = '';
    if (isset($db)) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'favicon_url'");
            $stmt->execute();
            $favRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($favRow && !empty($favRow['setting_value'])) {
                $faviconUrl = $favRow['setting_value'];
            }
        } catch (Exception $e) {
            // Fail silently
        }
    }
    if (!empty($faviconUrl)): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <?php endif; ?>
    <?php
    $storeNameForTitle = 'Your Store';
    if (isset($db)) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['setting_value'])) {
                $storeNameForTitle = $row['setting_value'];
            }
        } catch (Exception $e) {
        }
    }
    ?>
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?php echo htmlspecialchars($storeNameForTitle); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription ?? 'JointBuddy - Premium 3D printed cannabis accessories. Your trusted marketplace for innovative grinders, rolling trays, and lifestyle gear.'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords ?? 'cannabis, marketplace, 3d printed, additives, accessories, JointBuddy, jointbuddy.co.za'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl ?? url($_SERVER['REQUEST_URI'])); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl ?? url($_SERVER['REQUEST_URI'])); ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars($storeNameForTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription ?? 'JointBuddy - Premium 3D printed cannabis accessories. Innovative gear for the modern enthusiast.'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage ?? assetUrl('images/branding/og-image.png')); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($canonicalUrl ?? url($_SERVER['REQUEST_URI'])); ?>">
    <meta property="twitter:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars($storeNameForTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($metaDescription ?? 'JointBuddy - Premium 3D printed cannabis accessories. Innovative gear for the modern enthusiast.'); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($ogImage ?? assetUrl('images/branding/og-image.png')); ?>">

<?php
// Universal Header Component for CannaBuddy
// Load URL Helper
require_once __DIR__ . '/url_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isLoggedIn = false;
$currentUser = null;

// Check Login State
if (isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? 'User'
    ];
}

// Currency Service Integration
require_once __DIR__ . '/commerce/CurrencyService.php';

$currencyService = null;
$currentCurrency = 'ZAR';
$currencies = [];

if (isset($db)) {
    $currencyService = new CurrencyService($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_currency'])) {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
        $currencyService->setCurrency($_POST['currency_code']);
        
        // Update user preference if logged in
        if (isset($isLoggedIn) && $isLoggedIn && isset($currentUser['id'])) {
            try {
                // Check if $db is PDO or CI4 Connection
                if ($db instanceof \PDO) {
                    $stmt = $db->prepare("UPDATE users SET preferred_currency = ? WHERE id = ?");
                    $stmt->execute([$_POST['currency_code'], $currentUser['id']]);
                } elseif (method_exists($db, 'query')) {
                    // Assume CI4 Connection
                    $db->query("UPDATE users SET preferred_currency = ? WHERE id = ?", [$_POST['currency_code'], $currentUser['id']]);
                }
            } catch (Exception $e) {
                // Fail silently
            }
        }

        // Refresh to avoid form resubmission on refresh
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $currentCurrency = $currencyService->getCurrentCurrency();
    $currencies = $currencyService->getAllCurrencies();

    // Helper function to get currency icon (from uploaded icon or fallback to hardcoded SVG)
    function getCurrencyIcon($code, $currencyData = null) {
        // If we have uploaded icon data, use it
        if ($currencyData && !empty($currencyData['icon'])) {
            $fileExt = strtolower(pathinfo($currencyData['icon'], PATHINFO_EXTENSION));
            if ($fileExt === 'svg') {
                $iconPath = __DIR__ . '/../' . $currencyData['icon'];
                if (file_exists($iconPath)) {
                    $svgContent = file_get_contents($iconPath);
                    // Add w-4 h-4 inline-block mr-1 classes to SVG
                    $svgContent = str_replace('<svg', '<svg class="w-4 h-4 inline-block mr-1"', $svgContent);
                    return $svgContent;
                }
            } else {
                return '<img src="' . url($currencyData['icon']) . '" alt="' . htmlspecialchars($code) . '" class="w-4 h-4 inline-block mr-1">';
            }
        }

        // Fallback to hardcoded SVGs
        $flags = [
            'ZAR' => '<svg class="w-4 h-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="2" fill="#007A4D"/><rect x="2" y="8" width="20" height="3" fill="#FFB81C"/><rect x="2" y="13" width="20" height="3" fill="#DE3831"/><rect x="2" y="18" width="20" height="4" fill="#005EB8"/></svg>',
            'USD' => '<svg class="w-4 h-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="2" fill="#B22234"/><rect width="24" height="3" y="3" fill="white"/><rect width="24" height="3" y="6" fill="#B22234"/><rect width="24" height="3" y="9" fill="white"/><rect width="24" height="3" y="12" fill="#B22234"/><rect width="24" height="3" y="15" fill="white"/><rect width="24" height="3" y="18" fill="#B22234"/><rect width="12" height="12" y="6" fill="#3C3B6E"/></svg>',
            'EUR' => '<svg class="w-4 h-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="2" fill="#003399"/><g fill="#FFCC00"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></g></svg>',
            'GBP' => '<svg class="w-4 h-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="2" fill="#012169"/><path d="M2 12h20M12 2v20" stroke="white" stroke-width="2"/><path d="M12 6l6-3-6-3-6 3 6 3" fill="white"/><path d="M12 18l6 3-6 3-6-3 6-3" fill="white"/></svg>',
        ];
        return $flags[$code] ?? '<svg class="w-4 h-4 inline-block mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="2" fill="#666"/></svg>';
    }

    // Create a map of currency codes to their data for quick lookup
    $currencyMap = [];
    foreach ($currencies as $curr) {
        $currencyMap[$curr['code']] = $curr;
    }

    // Fetch site settings for logo and branding
    $siteSettings = [];
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($settingsRows as $row) {
            $siteSettings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Fail silently if settings retrieval fails
    }
}

// Calculate Cart Count
$cartCount = 0;
$cart = [];

// For logged-in users, only load from database if session cart is empty (prevents duplication loop)
if (isset($isLoggedIn) && $isLoggedIn && isset($currentUser['id']) && isset($db)) {
    // Use session cart as primary source - only load from DB if session is empty (fresh login)
    if (empty($_SESSION['cart'])) {
        try {
            require_once __DIR__ . '/cart_sync_service.php';
            require_once __DIR__ . '/product_helpers.php';
            $dbCart = loadCartFromDatabase($db, $currentUser['id']);
            if (!empty($dbCart)) {
                // Convert database cart format to session format
                $cart = [];
                foreach ($dbCart as $dbItem) {
                    // Build product array for image helper
                    $productForImage = [
                        'images' => $dbItem['product_images'],
                        'image_1' => null,
                        'image_2' => null,
                        'image_3' => null,
                        'image_4' => null
                    ];
                    // Try to extract first image from comma-separated list
                    if (!empty($dbItem['product_images'])) {
                        $imageList = explode(',', $dbItem['product_images']);
                        if (!empty($imageList[0])) {
                            $productForImage['image_1'] = trim($imageList[0]);
                        }
                    }

                    $cart[] = [
                        'product_id' => $dbItem['product_id'],
                        'name' => $dbItem['product_name'],
                        'slug' => $dbItem['product_slug'],
                        'price' => $dbItem['price'],
                        'original_price' => $dbItem['price'], // Will be updated if on sale
                        'qty' => $dbItem['quantity'],
                        'image' => getProductMainImage($productForImage),
                        'max_stock' => $dbItem['stock']
                    ];
                }
                // Store in session - don't sync back to avoid loop
                $_SESSION['cart'] = $cart;
            } else {
                $cart = [];
            }
        } catch (Exception $e) {
            $cart = [];
        }
    } else {
        // Session cart exists - use it directly (don't reload from DB)
        $cart = $_SESSION['cart'];
    }
} else {
    // Guest users - use session cart only
    $cart = $_SESSION['cart'] ?? [];
}

// Calculate total count
if (is_array($cart)) {
    foreach ($cart as $item) {
        $cartCount += $item['qty'] ?? $item['quantity'] ?? 0;
    }
}

// Skip if already included
if (defined('HEADER_INCLUDED')) return;
define('HEADER_INCLUDED', true);
?>

    <!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: {
                        50: '#f0fdf4',
                        100: '#dcfce7',
                        500: '#22c55e',
                        600: '#16a34a',
                        700: '#15803d'
                    }
                }
            }
        }
    }
</script>

<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- Additional CSS -->
<style>
    .hover-green:hover {
        background-color: #f0fdf4;
    }
    body {
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .font-heading {
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.025em;
    }
</style>

    <!-- Location Dropdowns Script (for checkout and address book) -->
    <?php if (isset($_SERVER['REQUEST_URI'])): ?>
        <?php
        $requestUri = $_SERVER['REQUEST_URI'];
        $includeLocationScript = (strpos($requestUri, '/checkout/') !== false ||
                                  strpos($requestUri, '/user/address-book/') !== false ||
                                  strpos($requestUri, '/user/add-address/') !== false);
        ?>
        <?php if ($includeLocationScript): ?>
        <script src="<?= assetUrl('js/checkout-location.js') ?>" defer></script>
        <?php endif; ?>
    <?php endif; ?>

    </head>
<body class="min-h-screen bg-gray-100">
<?php
$headerClasses = 'bg-white shadow-md';
if (isset($currentPage) && $currentPage === 'main') {
    $headerClasses .= ' sticky top-0 z-50';
}
?>
    <header class="<?php echo  $headerClasses ?>">
        <!-- First Row -->
        <div class="bg-white text-gray-800 border-b py-4">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
                <div class="flex justify-between items-center h-10">
                    <!-- Left side - Logo -->
                    <div class="flex items-center">
                        <a href="<?php echo  url('/') ?>" class="flex items-center space-x-3">
                            <?php
                            $displayMode = $siteSettings['logo_display_mode'] ?? 'text';
                            $storeName = $siteSettings['store_name'] ?? 'Your Store';
                            $logoUrl = $siteSettings['store_logo'] ?? '';
                            $logoFilter = $siteSettings['logo_filter'] ?? 'original';

                            // Display based on mode
                            if ($displayMode === 'logo' && !empty($logoUrl)) {
                                // Logo only
                                echo '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($storeName) . '" class="h-12 w-auto" style="filter: ' . ($logoFilter === 'white' ? 'brightness(0) invert(1)' : 'none') . '">';
                            } elseif ($displayMode === 'both' && !empty($logoUrl)) {
                                // Both logo and text
                                echo '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($storeName) . '" class="h-12 w-auto" style="filter: ' . ($logoFilter === 'white' ? 'brightness(0) invert(1)' : 'none') . '">';
                                echo '<span class="font-bold text-3xl text-green-600 ml-3">' . htmlspecialchars($storeName) . '</span>';
                            } else {
                                // Text only (or fallback): show store name only, no icon
                                echo '<span class="font-bold text-3xl text-green-600">' . htmlspecialchars($storeName) . '</span>';
                            }
                            ?>
                        </a>
                    </div>
                    
                    <!-- Right side - User controls -->
                    <div class="flex items-center space-x-4">
                        <?php if ($currencyService && count($currencies) > 0): ?>
                        <form method="POST" class="inline-block relative">
                            <select name="currency_code" onchange="this.form.submit();" class="text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 py-1 pl-10 pr-6 cursor-pointer appearance-none bg-white min-w-[80px]">
                                <?php foreach ($currencies as $c): ?>
                                    <?php if ($c['is_active']): ?>
                                        <?php
                                        $optionLabel = $c['code'];
                                        if (!empty($c['symbol'])) {
                                            $optionLabel = $c['symbol'] . ' ' . $c['code'];
                                        }
                                        ?>
                                        <option value="<?php echo  htmlspecialchars($c['code']) ?>" <?php echo  $c['code'] === $currentCurrency ? 'selected' : '' ?>>
                                            <?php echo  htmlspecialchars($optionLabel) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <?php echo  getCurrencyIcon($currentCurrency, isset($currencyMap[$currentCurrency]) ? $currencyMap[$currentCurrency] : null) ?>
                            </div>
                            <input type="hidden" name="switch_currency" value="1">
                            <input type="hidden" name="csrf_token" value="<?php echo  htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </form>
                        <span class="text-gray-400">|</span>
                        <?php endif; ?>

                        <?php if ($isLoggedIn): ?>
                            <!-- Logged in user menu -->
                            <span class="text-sm font-medium text-green-600"><?php echo  htmlspecialchars($currentUser['name']) ?></span>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  userUrl('/logout/') ?>" class="text-sm hover:text-green-600 transition-colors">Logout</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  userUrl('/orders/') ?>" class="text-sm hover:text-green-600 transition-colors">Orders</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  userUrl('/dashboard/') ?>" class="text-sm hover:text-green-600 transition-colors">My Account</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  url('/sell/') ?>" class="text-sm hover:text-green-600 transition-colors">Sell on <?php echo  htmlspecialchars($storeName); ?></a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  url('/cart/') ?>" class="relative text-sm hover:text-green-600 transition-colors">
                                Cart
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center cart-count"><?php echo  $cartCount ?></span>
                            </a>
                        <?php else: ?>
                            <!-- Guest user menu -->
                            <a href="<?php echo  userUrl('/login/') ?>" class="text-sm hover:text-green-600 transition-colors">Login</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  url('/register/') ?>" class="text-sm hover:text-green-600 transition-colors">Register</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  userUrl('/orders/') ?>" class="text-sm hover:text-green-600 transition-colors">Orders</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  userUrl('/dashboard/') ?>" class="text-sm hover:text-green-600 transition-colors">My Account</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  url('/sell/') ?>" class="text-sm hover:text-green-600 transition-colors">Sell on <?php echo  htmlspecialchars($storeName); ?></a>
                            <span class="text-gray-400">|</span>
                            <a href="<?php echo  url('/cart/') ?>" class="relative text-sm hover:text-green-600 transition-colors">
                                Cart
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center cart-count"><?php echo  $cartCount ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row -->
        <div class="bg-green-600 text-white">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
                <div class="flex justify-between items-center h-16">
                    <!-- Search Bar -->
                    <div class="flex-1 max-w-4xl mx-4">
                        <form action="<?php echo  shopUrl('/') ?>" method="GET">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text"
                                       name="search"
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                       placeholder="Search products...">
                            </div>
                        </form>
                    </div>
                    
                    <!-- Quick Links -->
                    <?php $currentPage = $currentPage ?? ''; ?>
                    <div class="hidden lg:flex items-center space-x-4">
                        <a href="<?php echo  shopUrl('/') ?>" class="<?php echo  $currentPage === 'shop' ? 'bg-green-700' : '' ?> text-white hover:bg-green-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-shopping-bag mr-1"></i>Shop
                        </a>
                        <a href="<?php echo  shopUrl('/?sort=newest') ?>" class="<?php echo  $currentPage === 'new-arrivals' ? 'bg-green-700' : '' ?> text-white hover:bg-green-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-star mr-1"></i>New Arrivals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
