<?php
// Header Component - Reusable across website and user dashboard
require_once __DIR__ . '/url_helper.php';

function renderHeader($currentPage = '') {
    $isLoggedIn = isset($_SESSION['user_id']);
    $userName = $_SESSION['user_name'] ?? '';
    $cartCount = $_SESSION['cart_count'] ?? 0;
    
    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CannaBuddy - Your Cannabis Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }
        .hover-green:hover {
            background-color: #f0fdf4;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <!-- First Row -->
        <div class="bg-green-600 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-12">
                    <!-- Left side - Logo -->
                    <div class="flex items-center">
                        <a href="' . url('/') . '" class="flex items-center space-x-2">
                            <span class="font-bold text-lg">CannaBuddy</span>
                        </a>
                    </div>
                    
                    <!-- Center - Navigation -->
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="' . url('/help/') . '" class="text-sm hover:text-green-200 transition-colors">
                            <i class="fas fa-question-circle mr-1"></i>Help Centre
                        </a>
                        <a href="' . url('/sell/') . '" class="text-sm hover:text-green-200 transition-colors">
                            <i class="fas fa-store mr-1"></i>Sell on CannaBuddy
                        </a>
                    </div>
                    
                    <!-- Right side - User controls -->
                    <div class="flex items-center space-x-4">
                        ' . ($isLoggedIn ? '
                            <a href="' . userUrl('/orders/') . '" class="text-sm hover:text-green-200 transition-colors">
                                <i class="fas fa-box mr-1"></i>Orders
                            </a>
                            <a href="' . userUrl('/dashboard/') . '" class="text-sm hover:text-green-200 transition-colors">
                                <i class="fas fa-user mr-1"></i>My Account
                            </a>
                        ' : '
                            <a href="' . userUrl('/login/') . '" class="text-sm hover:text-green-200 transition-colors">
                                <i class="fas fa-sign-in-alt mr-1"></i>Login
                            </a>
                            <a href="' . userUrl('/register/') . '" class="text-sm hover:text-green-200 transition-colors">
                                <i class="fas fa-user-plus mr-1"></i>Register
                            </a>
                        ') . '
                        
                        <a href="' . url('/cart/') . '" class="relative text-sm hover:text-green-200 transition-colors">
                            <i class="fas fa-shopping-cart mr-1"></i>Cart
                            ' . ($cartCount > 0 ? '<span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">' . $cartCount . '</span>' : '') . '
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Search Bar -->
                    <div class="flex-1 max-w-lg mx-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Shop by Categories">
                        </div>
                    </div>
                    
                    <!-- Categories (placeholder for future use) -->
                    <div class="hidden lg:flex items-center space-x-4">
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-th-large mr-2"></i>Categories
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <div class="py-1">
                                    <a href="' . shopUrl('?category=flowers') . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Flowers</a>
                                    <a href="' . shopUrl('?category=edibles') . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Edibles</a>
                                    <a href="' . shopUrl('?category=vapes') . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Vapes</a>
                                    <a href="' . shopUrl('?category=concentrates') . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Concentrates</a>
                                    <a href="' . shopUrl('?category=topicals') . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Topicals</a>
                                </div>
                            </div>
                        </div>
                        
                        <a href="' . shopUrl('/deals') . '" class="text-red-600 hover:text-red-800 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-fire mr-1"></i>Deals
                        </a>
                        
                        <a href="' . shopUrl('/new') . '" class="text-blue-600 hover:text-blue-800 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-star mr-1"></i>New Arrivals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>';
}

// Footer Component
function renderFooter() {
    return '
    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Column 1 - Company -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        CannaBuddy
                    </h3>
                    <p class="text-gray-300 mb-4 text-sm">
                        Your trusted cannabis marketplace connecting consumers with quality products and verified vendors.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Column 2 - Shop -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Shop</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="' . shopUrl('/') . '" class="text-gray-300 hover:text-white transition-colors">All Products</a></li>
                        <li><a href="' . shopUrl('?category=flowers') . '" class="text-gray-300 hover:text-white transition-colors">Cannabis Flowers</a></li>
                        <li><a href="' . shopUrl('?category=edibles') . '" class="text-gray-300 hover:text-white transition-colors">Edibles</a></li>
                        <li><a href="' . shopUrl('?category=vapes') . '" class="text-gray-300 hover:text-white transition-colors">Vapes & Cartridges</a></li>
                        <li><a href="' . shopUrl('?category=concentrates') . '" class="text-gray-300 hover:text-white transition-colors">Concentrates</a></li>
                        <li><a href="' . shopUrl('/deals') . '" class="text-gray-300 hover:text-white transition-colors">Deals & Offers</a></li>
                        <li><a href="' . shopUrl('/new') . '" class="text-gray-300 hover:text-white transition-colors">New Arrivals</a></li>
                    </ul>
                </div>
                
                <!-- Column 3 - Account & Support -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Account & Support</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="' . userUrl('/login/') . '" class="text-gray-300 hover:text-white transition-colors">Login</a></li>
                        <li><a href="' . userUrl('/register/') . '" class="text-gray-300 hover:text-white transition-colors">Register</a></li>
                        <li><a href="' . userUrl('/orders/') . '" class="text-gray-300 hover:text-white transition-colors">Track Your Order</a></li>
                        <li><a href="' . userUrl('/dashboard/') . '" class="text-gray-300 hover:text-white transition-colors">My Account</a></li>
                        <li><a href="' . url('/help/') . '" class="text-gray-300 hover:text-white transition-colors">Help Centre</a></li>
                        <li><a href="' . url('/contact/') . '" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="' . url('/returns/') . '" class="text-gray-300 hover:text-white transition-colors">Returns & Refunds</a></li>
                        <li><a href="' . url('/shipping/') . '" class="text-gray-300 hover:text-white transition-colors">Shipping Info</a></li>
                    </ul>
                </div>
                
                <!-- Column 4 - Legal & Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Legal & Information</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="' . url('/about/') . '" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="' . url('/privacy/') . '" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="' . url('/terms/') . '" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="' . url('/age-verification/') . '" class="text-gray-300 hover:text-white transition-colors">Age Verification</a></li>
                        <li><a href="' . url('/compliance/') . '" class="text-gray-300 hover:text-white transition-colors">Compliance</a></li>
                        <li><a href="' . url('/vendor-application/') . '" class="text-gray-300 hover:text-white transition-colors">Become a Vendor</a></li>
                        <li><a href="' . url('/careers/') . '" class="text-gray-300 hover:text-white transition-colors">Careers</a></li>
                        <li><a href="' . url('/news/') . '" class="text-gray-300 hover:text-white transition-colors">News & Updates</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div class="border-t border-gray-700 mt-8 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-300 text-sm">
                        &copy; 2025 CannaBuddy. All rights reserved. Please consume responsibly.
                    </p>
                    <div class="flex items-center mt-4 md:mt-0 space-x-4">
                        <span class="text-gray-300 text-sm">We verify age for all purchases</span>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-shield-alt text-green-400"></i>
                            <span class="text-green-400 text-sm font-semibold">Secure & Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>';
}
?>
