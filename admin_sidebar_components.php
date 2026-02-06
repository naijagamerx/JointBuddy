<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Admin Sidebar Components Library
 * Contains helper functions for admin dashboard
 */

require_once __DIR__ . '/includes/url_helper.php';
require_once __DIR__ . '/includes/error_handler.php';

/**
 * Generate admin statistics card
 */
function adminStatCard($title, $value, $icon, $color = 'blue', $change = null) {
    $colorClasses = [
        'blue' => 'text-blue-600 bg-blue-100',
        'green' => 'text-green-600 bg-green-100',
        'yellow' => 'text-yellow-600 bg-yellow-100',
        'red' => 'text-red-600 bg-red-100',
        'purple' => 'text-purple-600 bg-purple-100',
        'indigo' => 'text-indigo-600 bg-indigo-100',
        'orange' => 'text-orange-600 bg-orange-100'
    ];

    $bgColor = $colorClasses[$color] ?? $colorClasses['blue'];
    $changeClass = $change ? ($change['type'] === 'increase' ? 'text-green-600' : 'text-red-600') : '';

    return '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="p-3 rounded-lg ' . $bgColor . '">
                    <i class="' . $icon . ' text-xl"></i>
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">' . ($title ?? '') . '</dt>
                    <dd class="text-2xl font-bold text-gray-900">' . safe_html($value ?? '') . '</dd>
                    ' . ($change ? '<dd class="text-sm ' . $changeClass . '">
                        <i class="fas fa-' . ($change['type'] === 'increase' ? 'arrow-up' : 'arrow-down') . ' mr-1"></i>
                        ' . safe_html($change['value'] ?? '') . ' from last month
                    </dd>' : '') . '
                </dl>
            </div>
        </div>
    </div>';
}

/**
 * Admin sidebar wrapper with navigation
 */
function adminSidebarWrapper($title, $content, $activeItem = 'dashboard') {
    $currentPage = $activeItem;
    global $adminAuth, $db;
    $currentAdmin = $adminAuth ? $adminAuth->getCurrentAdmin() : null;

    $adminName = $currentAdmin ? $currentAdmin['full_name'] : 'Administrator';
    $adminRole = $currentAdmin ? $currentAdmin['role'] : 'admin';

    $siteName = 'Store';
    if (isset($db) && $db) {
        try {
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['setting_value'])) {
                $siteName = $result['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Error fetching site name: " . $e->getMessage());
        }
    }

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . safe_html($title ?? '') . ' - ' . safe_html($siteName ?? '') . '</title>
    ' . (!empty($db) ? (function() use ($db) {
        try {
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'favicon_url'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && !empty($result['setting_value']) ? '<link rel="icon" href="' . htmlspecialchars($result['setting_value']) . '">' : '';
        } catch (Exception $e) { return ''; }
    })() : '') . '
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .content-with-sidebar {
            margin-left: 256px;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .content-with-sidebar {
                margin-left: 0;
            }
        }
        @media (min-width: 1800px) {
            .content-with-sidebar .max-w-7xl,
            .content-with-sidebar .container {
                max-width: 100% !important;
            }
            .content-with-sidebar .mx-auto {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        }
        /* Hide scrollbar but keep functionality */
        #sidebar nav {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        #sidebar nav::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile menu backdrop -->
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 hidden md:hidden"></div>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform -translate-x-full sidebar-transition md:translate-x-0">
        <div class="flex items-center justify-center h-16 bg-gray-800">
            <h1 class="text-xl font-bold">
                <span class="text-green-400">' . safe_html($siteName ?? '') . '</span> Admin
            </h1>
        </div>

        <nav class="mt-5 overflow-y-auto flex-1" style="max-height: calc(100vh - 280px);">
            <div class="px-2">
                <a href="' . adminUrl('/') . '" class="' . ($currentPage === 'dashboard' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="' . adminUrl('/products') . '" class="' . ($currentPage === 'products' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-box mr-3"></i>
                    Products
                </a>
                <a href="' . adminUrl('/products/variations') . '" class="' . ($currentPage === 'variations' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-tags mr-3"></i>
                    Product Variations
                </a>
                <a href="' . adminUrl('/products/inquiries') . '" class="' . ($currentPage === 'inquiries' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-question-circle mr-3"></i>
                    Product Inquiries
                </a>
                <a href="' . adminUrl('/products/reviews') . '" class="' . ($currentPage === 'reviews' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-star mr-3"></i>
                    Product Reviews
                </a>
                <a href="' . adminUrl('/payment-methods') . '" class="' . ($currentPage === 'payment-methods' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-credit-card mr-3"></i>
                    Payment Methods
                </a>
                <a href="' . adminUrl('/qr-codes') . '" class="' . ($currentPage === 'qr-codes' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-qrcode mr-3"></i>
                    QR Codes
                </a>
                <a href="' . adminUrl('/delivery-methods') . '" class="' . ($currentPage === 'delivery-methods' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-truck mr-3"></i>
                    Delivery Methods
                </a>
                <a href="' . adminUrl('/orders') . '" class="' . ($currentPage === 'orders' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    Orders
                </a>
                <a href="' . adminUrl('/orders/create') . '" class="' . ($currentPage === 'orders-create' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-plus-circle mr-3"></i>
                    Create Order
                </a>
                <a href="' . adminUrl('/returns') . '" class="' . ($currentPage === 'returns' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-undo mr-3"></i>
                    Returns
                </a>
                <a href="' . adminUrl('/vouchers') . '" class="' . ($currentPage === 'vouchers' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-gift mr-3"></i>
                    Gift Vouchers
                </a>
                <a href="' . adminUrl('/coupons') . '" class="' . ($currentPage === 'coupons' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-ticket-alt mr-3"></i>
                    Coupons
                </a>
                <a href="' . adminUrl('/users') . '" class="' . ($currentPage === 'users' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
                <a href="' . adminUrl('/categories') . '" class="' . ($currentPage === 'categories' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-folder mr-3"></i>
                    Categories
                </a>
                <a href="' . adminUrl('/analytics') . '" class="' . ($currentPage === 'analytics' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Analytics
                </a>
                <a href="' . adminUrl('/slider') . '" class="' . ($currentPage === 'slider' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-images mr-3"></i>
                    Homepage Slider
                </a>
                <a href="' . adminUrl('/seo') . '" class="' . ($currentPage === 'seo' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1">
                    <i class="fas fa-search mr-3"></i>
                    SEO Tools
                </a>
                <a href="' . url('/') . '" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md mb-1" target="_blank">
                    <i class="fas fa-home mr-3"></i>
                    View Homepage
                </a>
                <a href="' . adminUrl('/settings') . '" class="' . ($currentPage === 'settings' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md">
                    <i class="fas fa-cog mr-3"></i>
                    Settings
                </a>
                <a href="' . adminUrl('/settings/currency') . '" class="' . ($currentPage === 'currency' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md">
                    <i class="fas fa-money-bill-wave mr-3"></i>
                    Currency
                </a>
                <a href="' . adminUrl('/users/newsletter') . '" class="' . ($currentPage === 'newsletter' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md">
                    <i class="fas fa-envelope mr-3"></i>
                    Newsletter
                </a>
                <a href="' . adminUrl('/tools') . '" class="' . ($currentPage === 'tools' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white') . ' group flex items-center px-2 py-2 text-base font-medium rounded-md">
                    <i class="fas fa-tools mr-3"></i>
                    Tools
                </a>
            </div>
        </nav>

        <div class="absolute bottom-0 w-full p-4">
            <div class="bg-gray-800 rounded-lg p-3">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">' . safe_html($adminName ?? '') . '</p>
                        <p class="text-xs text-gray-400">' . safe_html($adminRole ?? '') . '</p>
                    </div>
                </div>
            </div>
            <a href="' . adminUrl('/login/?logout=1') . '" class="mt-2 w-full flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main content -->
    <div class="content-with-sidebar">
        <!-- Top bar -->
        <div class="bg-white shadow-sm">
            <div class="px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">' . safe_html($title ?? '') . '</h1>
                    <button id="mobile-menu-button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            ' . $content . '
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        const sidebar = document.getElementById("sidebar");
        const backdrop = document.getElementById("sidebar-backdrop");
        const mobileButton = document.getElementById("mobile-menu-button");

        function toggleSidebar() {
            sidebar.classList.toggle("-translate-x-full");
            backdrop.classList.toggle("hidden");
        }

        mobileButton.addEventListener("click", toggleSidebar);
        backdrop.addEventListener("click", toggleSidebar);
    </script>
</body>
</html>';
}

/**
 * Form input component
 */
function adminFormInput($label, $name, $value = '', $type = 'text', $required = false, $placeholder = '', $options = []) {
    $requiredAttr = $required ? 'required' : '';
    $placeholderAttr = $placeholder ? 'placeholder="' . safe_html($placeholder ?? '') . '"' : '';
    $id = 'field_' . $name;

    $inputHtml = '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . safe_html($value ?? '') . '" ' . $requiredAttr . ' ' . $placeholderAttr;

    if (!empty($options)) {
        foreach ($options as $option => $val) {
            $inputHtml .= ' ' . $option . '="' . safe_html($val ?? '') . '"';
        }
    }

    $inputHtml .= ' class="mt-1 block w-full px-3 py-2 rounded-md border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm">';

    return '<div class="mb-4">
        <label for="' . $id . '" class="block text-sm font-medium text-gray-700 mb-1">' . $label . ($required ? ' *' : '') . '</label>
        ' . $inputHtml . '
    </div>';
}

/**
 * Form textarea component
 */
function adminFormTextarea($label, $name, $value = '', $rows = 4, $required = false, $placeholder = '', $options = []) {
    $requiredAttr = $required ? 'required' : '';
    $placeholderAttr = $placeholder ? 'placeholder="' . safe_html($placeholder ?? '') . '"' : '';
    $id = 'field_' . $name;

    $textareaHtml = '<textarea id="' . $id . '" name="' . $name . '" rows="' . $rows . '" ' . $requiredAttr . ' ' . $placeholderAttr;

    if (!empty($options)) {
        foreach ($options as $option => $val) {
            $textareaHtml .= ' ' . $option . '="' . safe_html($val ?? '') . '"';
        }
    }

    $textareaHtml .= ' class="mt-1 block w-full px-3 py-2 rounded-md border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 placeholder-gray-300 placeholder-opacity-60 sm:text-sm">' . safe_html($value ?? '') . '</textarea>';

    return '<div class="mb-4">
        <label for="' . $id . '" class="block text-sm font-medium text-gray-700 mb-1">' . $label . ($required ? ' *' : '') . '</label>
        ' . $textareaHtml . '
    </div>';
}

/**
 * Form select component
 */
function adminFormSelect($label, $name, $value = '', $options = [], $required = false, $placeholder = 'Select an option') {
    $requiredAttr = $required ? 'required' : '';
    $id = 'field_' . $name;

    $selectHtml = '<select id="' . $id . '" name="' . $name . '" ' . $requiredAttr . ' class="mt-1 block w-full px-3 py-2 rounded-md border-gray-200 shadow-sm focus:border-green-400 focus:ring-green-400 focus:ring-1 sm:text-sm">';
    $selectHtml .= '<option value="">' . safe_html($placeholder ?? '') . '</option>';

    foreach ($options as $optionValue => $optionLabel) {
        $selected = ($value == $optionValue) ? 'selected' : '';
        $selectHtml .= '<option value="' . safe_html($optionValue ?? '') . '" ' . $selected . '>' . safe_html($optionLabel ?? '') . '</option>';
    }

    $selectHtml .= '</select>';

    return '<div class="mb-4">
        <label for="' . $id . '" class="block text-sm font-medium text-gray-700 mb-1">' . $label . ($required ? ' *' : '') . '</label>
        ' . $selectHtml . '
    </div>';
}

/**
 * Admin alert component
 */
function adminAlert($message, $type = 'info', $dismissible = true) {
    $typeClasses = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];

    $iconClasses = [
        'success' => 'fas fa-check-circle text-green-400',
        'error' => 'fas fa-exclamation-circle text-red-400',
        'warning' => 'fas fa-exclamation-triangle text-yellow-400',
        'info' => 'fas fa-info-circle text-blue-400'
    ];

    $class = $typeClasses[$type] ?? $typeClasses['info'];
    $icon = $iconClasses[$type] ?? $iconClasses['info'];

    return '<div class="border-l-4 p-4 mb-4 ' . $class . '">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="' . $icon . '"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm">' . safe_html($message ?? '') . '</p>
            </div>
            ' . ($dismissible ? '<div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>' : '') . '
        </div>
    </div>';
}

/**
 * Admin button component
 */
function adminButton($text, $href = '#', $type = 'primary', $icon = '', $options = []) {
    $typeClasses = [
        'primary' => 'bg-green-600 text-white hover:bg-green-700',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700',
        'info' => 'bg-blue-600 text-white hover:bg-blue-700'
    ];

    $class = $typeClasses[$type] ?? $typeClasses['primary'];
    $iconHtml = $icon ? '<i class="' . $icon . ' mr-2"></i>' : '';
    $optionsStr = '';

    if (!empty($options)) {
        foreach ($options as $option => $val) {
            $optionsStr .= ' ' . $option . '="' . safe_html($val ?? '') . '"';
        }
    }

    return '<a href="' . safe_html($href ?? '') . '" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md ' . $class . '"' . $optionsStr . '>
        ' . $iconHtml . safe_html($text ?? '') . '
    </a>';
}

/**
 * Admin table component
 */
function adminTable($headers = [], $rows = [], $options = []) {
    $responsive = $options['responsive'] ?? true;
    $striped = $options['striped'] ?? true;
    $hover = $options['hover'] ?? true;

    $tableClass = 'min-w-full divide-y divide-gray-200';
    if ($striped) $tableClass .= ' divide-y';
    if ($hover) $tableClass .= ' hover';

    $html = '<div class="' . ($responsive ? 'overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg' : '') . '">
        <table class="' . $tableClass . '">';

    if (!empty($headers)) {
        $html .= '<thead class="bg-gray-50">
            <tr>';
        foreach ($headers as $header) {
            $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . safe_html($header ?? '') . '</th>';
        }
        $html .= '</tr>
        </thead>';
    }

    $html .= '<tbody class="bg-white divide-y divide-gray-200">';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $cell . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody>
        </table>
    </div>';

    return $html;
}
