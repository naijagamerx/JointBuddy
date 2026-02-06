<?php
/**
 * User Dashboard Header Component
 * Consistent header for all user dashboard pages
 */

// Include bootstrap if not already loaded
if (!function_exists('Services')) {
    require_once __DIR__ . '/../../includes/bootstrap.php';
}

// Get dynamic store name from database
$storeName = 'Your Store';
try {
    $db = Services::db();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['setting_value'])) {
        $storeName = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
}

// Get current user info
$currentUser = AuthMiddleware::getCurrentUser();
$isLoggedIn = AuthMiddleware::isUserLoggedIn();

// Calculate cart count
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

// Get page title if set
$pageTitle = $pageTitle ?? 'My Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Get Favicon from database
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
        <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <?php endif; ?>
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($storeName) ?></title>
    
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
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        .hover-green:hover {
            background-color: #f0fdf4;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <header class="bg-white shadow-md">
        <!-- First Row -->
        <div class="bg-white text-gray-800 border-b py-4">
            <div class="container mx-auto px-4 py-4 max-w-7xl">
                <div class="flex justify-between items-center h-10">
                    <!-- Left side - Logo -->
                    <div class="flex items-center">
                        <a href="<?= url('/') ?>" class="flex items-center space-x-3">
                            <span class="font-bold text-3xl text-green-600"><?= htmlspecialchars($storeName) ?></span>
                        </a>
                    </div>
                    
                    <!-- Right side - User controls -->
                    <div class="flex items-center space-x-4">
                        <?php if ($isLoggedIn): ?>
                            <!-- Logged in user menu -->
                            <span class="text-sm font-medium text-green-600"><?= htmlspecialchars($currentUser['name']) ?></span>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/logout/') ?>" class="text-sm hover:text-green-600 transition-colors">Logout</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/orders/') ?>" class="text-sm hover:text-green-600 transition-colors">Orders</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/dashboard/') ?>" class="text-sm hover:text-green-600 transition-colors">My Account</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= url('/cart/') ?>" class="relative text-sm hover:text-green-600 transition-colors">
                                Cart
                                <span class="absolute -top-2 -right-4 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center cart-count"><?= $cartCount ?></span>
                            </a>
                        <?php else: ?>
                            <!-- Guest user menu -->
                            <a href="<?= userUrl('/login/') ?>" class="text-sm hover:text-green-600 transition-colors">Login</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/register/') ?>" class="text-sm hover:text-green-600 transition-colors">Register</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= url('/cart/') ?>" class="relative text-sm hover:text-green-600 transition-colors">
                                Cart
                                <span class="absolute -top-2 -right-4 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center cart-count"><?= $cartCount ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row -->
        <div class="bg-green-600 text-white">
            <div class="container mx-auto px-4 py-8 max-w-7xl">
                <div class="flex justify-between items-center h-16">
                    <!-- Search Bar -->
                    <div class="flex-1 max-w-4xl mx-4">
                        <form action="<?= shopUrl('/') ?>" method="GET">
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
                    <div class="hidden lg:flex items-center space-x-4">
                        <a href="<?= shopUrl('/') ?>" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-shopping-bag mr-1 text-white"></i>Shop
                        </a>
                        <a href="<?= shopUrl('/?sort=newest') ?>" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-star mr-1 text-white"></i>New Arrivals
                        </a>
                        <a href="<?= userUrl('/dashboard/') ?>" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-user mr-1 text-white"></i>My Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
