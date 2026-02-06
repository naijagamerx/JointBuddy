<?php
// Personal Details Page - Profile Management
session_start();
require_once __DIR__ . '/../../includes/url_helper.php';

$currentUser = null;
$isLoggedIn = false;

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $isLoggedIn = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'phone' => $_SESSION['user_phone'] ?? '',
        'date_of_birth' => $_SESSION['user_dob'] ?? '',
        'address' => $_SESSION['user_address'] ?? '',
        'city' => $_SESSION['user_city'] ?? '',
        'state' => $_SESSION['user_state'] ?? '',
        'zip' => $_SESSION['user_zip'] ?? ''
    ];
}

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: ' . userUrl('/login/'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Details - CannaBuddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hover-green:hover { background-color: #f0fdf4; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <!-- First Row -->
        <div class="bg-white text-gray-800 border-b py-4">
            <div class="container mx-auto px-4 py-4 max-w-7xl">
                <div class="flex justify-between items-center h-10">
                    <!-- Left side - Logo -->
                    <div class="flex items-center">
                        <a href="<?= url('/" class="flex items-center space-x-3">
                            <span class="font-bold text-3xl text-green-600">CannaBuddy</span>
                        </a>
                    </div>
                    
                    <!-- Right side - User controls -->
                    <div class="flex items-center space-x-4">
                        <?php if ($isLoggedIn): ?>
                            <span class="text-sm font-medium text-green-600"><?= htmlspecialchars($currentUser['name']) ?></span>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/logout/" class="text-sm hover:text-green-600 transition-colors">Logout</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/orders/" class="text-sm hover:text-green-600 transition-colors">Orders</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/dashboard/" class="text-sm hover:text-green-600 transition-colors">My Account</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= url('/sell/" class="text-sm hover:text-green-600 transition-colors">Sell on CannaBuddy</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= url('/cart/" class="relative text-sm hover:text-green-600 transition-colors">
                                Cart
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                            </a>
                        <?php else: ?>
                            <a href="<?= userUrl('/login/" class="text-sm hover:text-green-600 transition-colors">Login</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/register/" class="text-sm hover:text-green-600 transition-colors">Register</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/orders/" class="text-sm hover:text-green-600 transition-colors">Orders</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= userUrl('/dashboard/" class="text-sm hover:text-green-600 transition-colors">My Account</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= url('/sell/" class="text-sm hover:text-green-600 transition-colors">Sell on CannaBuddy</a>
                            <span class="text-gray-400">|</span>
                            <a href="<?= url('/cart/" class="relative text-sm hover:text-green-600 transition-colors">
                                Cart
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
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
                    <div class="flex-1 max-w-4xl mx-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Shop by Categories">
                        </div>
                    </div>
                    
                    <div class="hidden lg:flex items-center space-x-4">
                        <a href="<?= shopUrl('/new" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-star mr-1 text-white"></i>New Arrivals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Back Card -->
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg shadow-md text-white p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Personal Details</h2>
                    <p class="text-indigo-100 text-sm">Update your personal information and contact details</p>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user-edit text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Navigation -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">My Account</h3>
                    <nav class="space-y-2">
                        <!-- Orders Section -->
                        <div>
                            <a href="<?= userUrl('/orders/" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50 rounded-md">
                                <i class="fas fa-box mr-3 text-gray-500"></i>
                                Orders
                            </a>
                            <div class="ml-6 space-y-1">
                                <a href="<?= userUrl('/invoices/" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Invoices</a>
                                <a href="<?= userUrl('/returns/" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Returns</a>
                                <a href="<?= userUrl('/reviews/" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Product Reviews</a>
                            </div>
                        </div>

                        <hr class="my-3 border-gray-200">

                        <!-- Payments & Credit -->
                        <div>
                            <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50 rounded-md">
                                <i class="fas fa-credit-card mr-3 text-gray-500"></i>
                                Payments & Credit
                            </a>
                            <div class="ml-6 space-y-1">
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Coupons & Offers</a>
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Credit & Refunds</a>
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Redeem Gift Voucher</a>
                            </div>
                        </div>

                        <hr class="my-3 border-gray-200">

                        <!-- CannaMore -->
                        <div>
                            <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50 rounded-md">
                                <i class="fas fa-crown mr-3 text-gray-500"></i>
                                CannaMore
                            </a>
                            <div class="ml-6 space-y-1">
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Subscription Plan</a>
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Payment History</a>
                            </div>
                        </div>

                        <hr class="my-3 border-gray-200">

                        <!-- Profile -->
                        <div>
                            <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-md">
                                <i class="fas fa-user-cog mr-3 text-green-600"></i>
                                Profile
                            </a>
                            <div class="ml-6 space-y-1">
                                <a href="#" class="block px-3 py-1 text-sm text-green-600 bg-green-50 border border-green-200 rounded-md">Personal Details</a>
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Security Settings</a>
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Address Book</a>
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Newsletter Subscriptions</a>
                            </div>
                        </div>

                        <hr class="my-3 border-gray-200">

                        <!-- My Lists -->
                        <div>
                            <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50 rounded-md">
                                <i class="fas fa-list mr-3 text-gray-500"></i>
                                My Lists
                            </a>
                            <div class="ml-6 space-y-1">
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Create a List</a>
                            </div>
                        </div>

                        <hr class="my-3 border-gray-200">

                        <!-- Support -->
                        <div>
                            <a href="#" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-green-600 hover:bg-gray-50 rounded-md">
                                <i class="fas fa-life-ring mr-3 text-gray-500"></i>
                                Support
                            </a>
                            <div class="ml-6 space-y-1">
                                <a href="#" class="block px-3 py-1 text-sm text-gray-600 hover:text-green-600">Help Centre</a>
                                <a href="<?= userUrl('/logout/" class="block px-3 py-1 text-sm text-red-600 hover:text-red-800">Logout</a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Main Content - Personal Details -->
            <div class="lg:w-3/4">
                <div class="bg-white rounded shadow-sm border border-gray-200">
                    <!-- Personal Details Header -->
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex justify-between items-center">
                            <h1 class="text-2xl font-bold text-gray-900">Personal Details</h1>
                            <div class="flex space-x-2">
                                <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100">
                                    Cancel
                                </button>
                                <button class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details Form -->
                    <div class="p-6">
                        <form class="space-y-6">
                            <!-- Profile Picture Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Picture</h3>
                                <div class="flex items-center space-x-6">
                                    <div class="flex-shrink-0">
                                        <img class="h-20 w-20 rounded-full object-cover" src="https://via.placeholder.com/80x80" alt="Profile">
                                    </div>
                                    <div>
                                        <button type="button" class="bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            Change Photo
                                        </button>
                                        <p class="text-xs text-gray-500 mt-1">JPG, GIF or PNG. 1MB max.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                        <input type="text" id="first_name" name="first_name" value="John" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" value="Doe" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($currentUser['phone']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($currentUser['date_of_birth']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                        <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                            <option value="prefer_not_to_say">Prefer not to say</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Address Information</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($currentUser['address']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                            <input type="text" id="city" name="city" value="<?= htmlspecialchars($currentUser['city']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        </div>
                                        <div>
                                            <label for="state" class="block text-sm font-medium text-gray-700">State/Province</label>
                                            <input type="text" id="state" name="state" value="<?= htmlspecialchars($currentUser['state']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        </div>
                                        <div>
                                            <label for="zip" class="block text-sm font-medium text-gray-700">ZIP/Postal Code</label>
                                            <input type="text" id="zip" name="zip" value="<?= htmlspecialchars($currentUser['zip']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cannabis Preferences -->
                            <div class="pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Cannabis Preferences (Optional)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="experience_level" class="block text-sm font-medium text-gray-700">Experience Level</label>
                                        <select id="experience_level" name="experience_level" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                            <option value="">Select Experience Level</option>
                                            <option value="beginner">Beginner</option>
                                            <option value="intermediate">Intermediate</option>
                                            <option value="advanced">Advanced</option>
                                            <option value="expert">Expert</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="preferred_products" class="block text-sm font-medium text-gray-700">Preferred Products</label>
                                        <select id="preferred_products" name="preferred_products" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                            <option value="">Select Preferred Products</option>
                                            <option value="flowers">Cannabis Flowers</option>
                                            <option value="edibles">Edibles</option>
                                            <option value="vapes">Vapes & Cartridges</option>
                                            <option value="concentrates">Concentrates</option>
                                            <option value="topicals">Topicals</option>
                                            <option value="pre_rolls">Pre-Rolls</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="favorite_strains" class="block text-sm font-medium text-gray-700">Favorite Strains (Optional)</label>
                                        <input type="text" id="favorite_strains" name="favorite_strains" placeholder="e.g., Blue Dream, OG Kush, Sour Diesel" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label for="medical_use" class="block text-sm font-medium text-gray-700">Medical Use</label>
                                        <div class="mt-1">
                                            <input type="checkbox" id="medical_use" name="medical_use" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                            <label for="medical_use" class="ml-2 text-sm text-gray-700">I use cannabis for medical purposes</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <button type="button" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-green-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="container mx-auto px-4 py-12 max-w-7xl">
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
                        <li><a href="<?= shopUrl('/" class="text-gray-300 hover:text-white transition-colors">All Products</a></li>
                        <li><a href="<?= shopUrl('/?category=flowers" class="text-gray-300 hover:text-white transition-colors">Cannabis Flowers</a></li>
                        <li><a href="<?= shopUrl('/?category=edibles" class="text-gray-300 hover:text-white transition-colors">Edibles</a></li>
                        <li><a href="<?= shopUrl('/?category=vapes" class="text-gray-300 hover:text-white transition-colors">Vapes & Cartridges</a></li>
                        <li><a href="<?= shopUrl('/?category=concentrates" class="text-gray-300 hover:text-white transition-colors">Concentrates</a></li>
                    </ul>
                </div>
                
                <!-- Column 3 - Support -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Help Centre</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Shipping Info</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Returns & Refunds</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Track Your Order</a></li>
                    </ul>
                </div>
                
                <!-- Column 4 - Company -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Company</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= url('/about/" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="<?= url('/careers/" class="text-gray-300 hover:text-white transition-colors">Careers</a></li>
                        <li><a href="<?= url('/press/" class="text-gray-300 hover:text-white transition-colors">Press</a></li>
                        <li><a href="<?= url('/affiliates/" class="text-gray-300 hover:text-white transition-colors">Affiliates</a></li>
                        <li><a href="<?= url('/terms/" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
                
            </div>
            
            <hr class="border-gray-700 my-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-300 text-sm">
                    &copy; 2024 CannaBuddy. All rights reserved.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="<?= url('/privacy/" class="text-gray-300 hover:text-white text-sm transition-colors">Privacy Policy</a>
                    <a href="<?= url('/terms/" class="text-gray-300 hover:text-white text-sm transition-colors">Terms of Service</a>
                    <a href="<?= url('/cookies/" class="text-gray-300 hover:text-white text-sm transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
