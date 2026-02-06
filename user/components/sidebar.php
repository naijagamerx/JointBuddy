<?php
/**
 * Universal Sidebar Navigation Component
 *
 * Parameters:
 * - $currentPage: The current active page to highlight
 */

// Include bootstrap if not already loaded
if (!function_exists('Services')) {
    require_once __DIR__ . '/../../includes/bootstrap.php';
}
?>

<div class="lg:w-1/4">
    <div class="bg-white rounded shadow-sm border border-gray-200 p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">My Account</h3>
        <nav class="space-y-2">
            <!-- Orders Section -->
            <div>
                <a href="<?= userUrl('/orders/') ?>" class="flex items-center px-3 py-2 text-sm font-medium <?= $currentPage === 'orders' ? 'text-green-600 bg-green-50 border border-green-200' : 'text-gray-700 hover:text-green-600 hover:bg-gray-50' ?> rounded-md">
                    <i class="fas fa-box mr-3 text-gray-500"></i>
                    Orders
                </a>
                <div class="ml-6 space-y-1">
                    <a href="<?= userUrl('/orders/track/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'track-order' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Track Your Order
                    </a>
                    <a href="<?= userUrl('/invoices/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'invoices' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Invoices
                    </a>
                    <a href="<?= userUrl('/returns/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'returns' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Returns
                    </a>
                    <a href="<?= userUrl('/reviews/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'reviews' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Product Reviews
                    </a>
                </div>
            </div>

            <hr class="my-3 border-gray-200">

            <!-- Payments & Credit -->
            <div>
                <a href="<?= userUrl('/payments-credit/') ?>" class="flex items-center px-3 py-2 text-sm font-medium <?= $currentPage === 'payments-credit' ? 'text-green-600 bg-green-50 border border-green-200' : 'text-gray-700 hover:text-green-600 hover:bg-gray-50' ?> rounded-md">
                    <i class="fas fa-credit-card mr-3 text-gray-500"></i>
                    Payments & Credit
                </a>
                <div class="ml-6 space-y-1">
                    <a href="<?= userUrl('/coupons-offers/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'coupons-offers' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Coupons & Offers
                    </a>
                    <a href="<?= userUrl('/credit-refunds/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'credit-refunds' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Credit & Refunds
                    </a>
                    <a href="<?= userUrl('/redeem-voucher/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'redeem-voucher' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Redeem Gift Voucher
                    </a>
                </div>
            </div>

            <hr class="my-3 border-gray-200">

            <!-- CannaMore -->
            <div>
                <a href="<?= userUrl('/subscription-plan/') ?>" class="flex items-center px-3 py-2 text-sm font-medium <?= $currentPage === 'subscription-plan' ? 'text-green-600 bg-green-50 border border-green-200' : 'text-gray-700 hover:text-green-600 hover:bg-gray-50' ?> rounded-md">
                    <i class="fas fa-crown mr-3 text-gray-500"></i>
                    CannaMore
                </a>
                <div class="ml-6 space-y-1">
                    <a href="<?= userUrl('/subscription-plan/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'subscription-plan' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Subscription Plan
                    </a>
                    <a href="<?= userUrl('/payment-history/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'payment-history' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Payment History
                    </a>
                </div>
            </div>

            <hr class="my-3 border-gray-200">

            <!-- Profile -->
            <div>
                <a href="<?= userUrl('/profile/') ?>" class="flex items-center px-3 py-2 text-sm font-medium <?= $currentPage === 'profile' ? 'text-green-600 bg-green-50 border border-green-200' : 'text-gray-700 hover:text-green-600 hover:bg-gray-50' ?> rounded-md">
                    <i class="fas fa-user-cog mr-3 text-gray-500"></i>
                    Profile
                </a>
                <div class="ml-6 space-y-1">
                    <a href="<?= userUrl('/personal-details/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'personal-details' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Personal Details
                    </a>
                    <a href="<?= userUrl('/security-settings/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'security-settings' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Security Settings
                    </a>
                    <a href="<?= userUrl('/address-book/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'address-book' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Address Book
                    </a>
                    <a href="<?= userUrl('/newsletter-subscriptions/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'newsletter-subscriptions' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Newsletter Subscriptions
                    </a>
                </div>
            </div>

            <hr class="my-3 border-gray-200">

            <!-- My Lists -->
            <div>
                <a href="<?= userUrl('/my-lists/') ?>" class="flex items-center px-3 py-2 text-sm font-medium <?= $currentPage === 'my-lists' ? 'text-green-600 bg-green-50 border border-green-200' : 'text-gray-700 hover:text-green-600 hover:bg-gray-50' ?> rounded-md">
                    <i class="fas fa-list mr-3 text-gray-500"></i>
                    My Lists
                </a>
                <div class="ml-6 space-y-1">
                    <a href="<?= userUrl('/my-lists/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'my-lists' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        My Lists
                    </a>
                    <a href="<?= userUrl('/create-list/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'create-list' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Create a List
                    </a>
                </div>
            </div>

            <hr class="my-3 border-gray-200">

            <!-- Support -->
            <div>
                <a href="<?= userUrl('/support/') ?>" class="flex items-center px-3 py-2 text-sm font-medium <?= $currentPage === 'support' ? 'text-green-600 bg-green-50 border border-green-200' : 'text-gray-700 hover:text-green-600 hover:bg-gray-50' ?> rounded-md">
                    <i class="fas fa-life-ring mr-3 text-gray-500"></i>
                    Support
                </a>
                <div class="ml-6 space-y-1">
                    <a href="<?= userUrl('/help-centre/') ?>" class="block px-3 py-1 text-sm <?= $currentPage === 'help-centre' ? 'text-green-600 bg-green-50 border border-green-200 rounded-md' : 'text-gray-600 hover:text-green-600' ?>">
                        Help Centre
                    </a>
                    <a href="<?= userUrl('/logout/') ?>" class="block px-3 py-1 text-sm text-red-600 hover:text-red-800">
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </div>
</div>
