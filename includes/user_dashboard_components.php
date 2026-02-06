<?php
// User Dashboard Sidebar Navigation Component
require_once __DIR__ . '/url_helper.php';

function renderUserSidebar($currentSection = '') {
    $menuItems = [
        'dashboard' => [
            'icon' => 'fas fa-tachometer-alt',
            'label' => 'My Account',
            'url' => userUrl('/dashboard/'),
            'active' => $currentSection === 'dashboard'
        ],
        'orders' => [
            'icon' => 'fas fa-box',
            'label' => 'Orders',
            'submenu' => [
                ['label' => 'Orders', 'url' => userUrl('/orders/')],
                ['label' => 'Invoices', 'url' => userUrl('/invoices/')],
                ['label' => 'Returns', 'url' => userUrl('/returns/')]
            ],
            'active' => in_array($currentSection, ['orders', 'invoices', 'returns'])
        ],
        'reviews' => [
            'icon' => 'fas fa-star',
            'label' => 'Product Reviews',
            'url' => userUrl('/reviews/'),
            'active' => $currentSection === 'reviews'
        ],
        'payments' => [
            'icon' => 'fas fa-credit-card',
            'label' => 'Payments & Credit',
            'submenu' => [
                ['label' => 'Coupons & Offers', 'url' => userUrl('/coupons/')],
                ['label' => 'Credit & Refunds', 'url' => userUrl('/credits/')]
            ],
            'active' => in_array($currentSection, ['payments', 'coupons', 'credits'])
        ],
        'gift-voucher' => [
            'icon' => 'fas fa-gift',
            'label' => 'Redeem Gift Voucher',
            'url' => userUrl('/gift-voucher/'),
            'active' => $currentSection === 'gift-voucher'
        ],
        'takealot-more' => [
            'icon' => 'fas fa-crown',
            'label' => 'TakealotMORE',
            'url' => userUrl('/more/'),
            'active' => $currentSection === 'takealot-more'
        ],
        'subscription' => [
            'icon' => 'fas fa-sync-alt',
            'label' => 'Subscription Plan',
            'url' => userUrl('/subscription/'),
            'active' => $currentSection === 'subscription'
        ],
        'payment-history' => [
            'icon' => 'fas fa-history',
            'label' => 'Payment History',
            'url' => userUrl('/payment-history/'),
            'active' => $currentSection === 'payment-history'
        ],
        'profile' => [
            'icon' => 'fas fa-user',
            'label' => 'Profile',
            'submenu' => [
                ['label' => 'Personal Details', 'url' => userUrl('/profile/')],
                ['label' => 'Security Settings', 'url' => userUrl('/security/')],
                ['label' => 'Address Book', 'url' => userUrl('/addresses/')]
            ],
            'active' => in_array($currentSection, ['profile', 'security', 'addresses'])
        ],
        'newsletter' => [
            'icon' => 'fas fa-envelope',
            'label' => 'Newsletter Subscriptions',
            'url' => userUrl('/newsletter/'),
            'active' => $currentSection === 'newsletter'
        ],
        'lists' => [
            'icon' => 'fas fa-list',
            'label' => 'My Lists',
            'submenu' => [
                ['label' => 'My Lists', 'url' => userUrl('/lists/')],
                ['label' => 'Create a List', 'url' => userUrl('/lists/create/')]
            ],
            'active' => in_array($currentSection, ['lists', 'create-list'])
        ],
        'support' => [
            'icon' => 'fas fa-life-ring',
            'label' => 'Support',
            'submenu' => [
                ['label' => 'Help Centre', 'url' => url('/help/')]
            ],
            'active' => $currentSection === 'support'
        ]
    ];
    
    $html = '<div class="bg-white shadow-lg rounded-lg">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Account Dashboard</h2>
            <nav class="space-y-2">';
    
    foreach ($menuItems as $key => $item) {
        $isActive = $item['active'];
        $hasSubmenu = isset($item['submenu']);
        
        if ($hasSubmenu) {
            // Main menu item with submenu
            $html .= '<div class="menu-section">
                <button class="w-full flex items-center justify-between p-3 text-left rounded-lg transition-colors ' . 
                ($isActive ? 'bg-green-100 text-green-800' : 'text-gray-700 hover:bg-gray-100') . '" 
                onclick="toggleSubmenu(\'' . $key . '\')">
                    <div class="flex items-center">
                        <i class="' . $item['icon'] . ' mr-3 w-5"></i>
                        <span class="font-medium">' . $item['label'] . '</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transform transition-transform" id="' . $key . '-arrow"></i>
                </button>
                
                <div class="submenu mt-2 ml-8 space-y-1 ' . ($isActive ? 'block' : 'hidden') . '" id="' . $key . '-submenu">';
            
            foreach ($item['submenu'] as $subitem) {
                $subActive = $currentSection === str_replace(['/', '-'], '', parse_url($subitem['url'], PHP_URL_PATH));
                $html .= '<a href="' . $subitem['url'] . '" 
                         class="block p-2 text-sm rounded-md transition-colors ' . 
                         ($subActive ? 'text-green-600 bg-green-50' : 'text-gray-600 hover:text-green-600 hover:bg-green-50') . '">
                         ' . $subitem['label'] . '</a>';
            }
            
            $html .= '</div></div>';
        } else {
            // Simple menu item
            $html .= '<a href="' . $item['url'] . '" 
                     class="flex items-center p-3 rounded-lg transition-colors ' . 
                     ($isActive ? 'bg-green-100 text-green-800' : 'text-gray-700 hover:bg-gray-100') . '">
                     <i class="' . $item['icon'] . ' mr-3 w-5"></i>
                     <span class="font-medium">' . $item['label'] . '</span>
                     </a>';
        }
    }
    
    $html .= '            </nav>
        </div>
    </div>
    
    <script>
        function toggleSubmenu(sectionId) {
            const submenu = document.getElementById(sectionId + "-submenu");
            const arrow = document.getElementById(sectionId + "-arrow");
            
            if (submenu.classList.contains("hidden")) {
                submenu.classList.remove("hidden");
                submenu.classList.add("block");
                arrow.style.transform = "rotate(180deg)";
            } else {
                submenu.classList.add("hidden");
                submenu.classList.remove("block");
                arrow.style.transform = "rotate(0deg)";
            }
        }
    </script>';
    
    return $html;
}

// User Dashboard Content Wrapper
function renderUserDashboardContent($title, $content) {
    return '<div class="min-h-screen bg-gray-50">
        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">' . $title . '</h1>
                </div>
                
                <!-- Content -->
                ' . $content . '
            </div>
        </div>
    </div>';
}

// Quick Stats Card Component
function renderStatsCard($icon, $title, $value, $color = 'blue') {
    $colorClasses = [
        'blue' => 'text-blue-600 bg-blue-100',
        'green' => 'text-green-600 bg-green-100',
        'purple' => 'text-purple-600 bg-purple-100',
        'yellow' => 'text-yellow-600 bg-yellow-100',
        'red' => 'text-red-600 bg-red-100'
    ];
    
    $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
    
    return '<div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="' . $icon . ' text-2xl ' . $colorClass . '"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">' . $title . '</dt>
                        <dd class="text-lg font-medium text-gray-900">' . $value . '</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>';
}

// Action Button Component
function renderActionButton($text, $url, $icon = '', $color = 'blue') {
    $colorClasses = [
        'blue' => 'bg-blue-600 hover:bg-blue-700',
        'green' => 'bg-green-600 hover:bg-green-700',
        'purple' => 'bg-purple-600 hover:bg-purple-700',
        'yellow' => 'bg-yellow-600 hover:bg-yellow-700',
        'red' => 'bg-red-600 hover:bg-red-700'
    ];
    
    $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
    
    return '<a href="' . $url . '" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white ' . $colorClass . ' transition-colors">
           ' . ($icon ? '<i class="' . $icon . ' mr-2"></i>' : '') . '
           ' . $text . '
           </a>';
}
?>
