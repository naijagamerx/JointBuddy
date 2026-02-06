<?php
/**
 * Admin Returns Dashboard - CannaBuddy
 * Manage all customer return requests
 */
require_once __DIR__ . '/../../includes/admin_error_catcher.php';

// Initialize error handling
setupAdminErrorHandling();

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get status counts
$statusCounts = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0,
    'received' => 0,
    'refunded' => 0,
    'rejected' => 0,
    'cancelled' => 0
];

$returns = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

if ($db) {
    try {
        // Get status counts
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM returns GROUP BY status");
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($counts as $count) {
            $statusCounts[$count['status']] = $count['count'];
            $statusCounts['all'] += $count['count'];
        }

        // Build query
        $sql = "
            SELECT r.*, o.order_number, u.name as customer_name, u.email as customer_email
            FROM returns r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON r.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (r.return_number LIKE ? OR o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($statusFilter)) {
            $sql .= " AND r.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Error fetching returns: " . $e->getMessage());
    }
}

// Helper function for status badge
function getAdminReturnStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'received' => 'bg-purple-100 text-purple-800',
        'refunded' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-800'
    ];
    $labels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'received' => 'Received',
        'refunded' => 'Refunded',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled'
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $label . '</span>';
}

$pageTitle = 'Returns Management';
$currentPage = 'returns';

// Build page content
$content = '';

if (function_exists('renderAdminErrors')) {
    $content .= renderAdminErrors();
}

// Stats Cards
$content .= '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">';
$content .= adminStatCard('Pending Review', $statusCounts['pending'], 'fas fa-clock', 'yellow');
$content .= adminStatCard('Approved', $statusCounts['approved'], 'fas fa-check', 'blue');
$content .= adminStatCard('Item Received', $statusCounts['received'], 'fas fa-box-open', 'purple');
$content .= adminStatCard('Refunded', $statusCounts['refunded'], 'fas fa-money-bill-wave', 'green');
$content .= '</div>';

// Search and Filter
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">';
$content .= '<div class="p-6">';
$content .= '<form method="GET" class="flex flex-col md:flex-row gap-4">';
$content .= '<div class="flex-1">';
$content .= '<div class="relative">';
$content .= '<i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>';
$content .= '<input type="text" name="search" value="' . safe_html($search) . '"';
$content .= ' placeholder="Search by return #, order #, customer name or email..."';
$content .= ' class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400">';
$content .= '</div>';
$content .= '</div>';
$content .= '<div class="w-full md:w-48">';
$content .= '<select name="status" onchange="this.form.submit()"';
$content .= ' class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-400 focus:border-green-400">';
$content .= '<option value="">All Statuses</option>';
$content .= '<option value="pending" ' . ($statusFilter === 'pending' ? 'selected' : '') . '>Pending</option>';
$content .= '<option value="approved" ' . ($statusFilter === 'approved' ? 'selected' : '') . '>Approved</option>';
$content .= '<option value="received" ' . ($statusFilter === 'received' ? 'selected' : '') . '>Received</option>';
$content .= '<option value="refunded" ' . ($statusFilter === 'refunded' ? 'selected' : '') . '>Refunded</option>';
$content .= '<option value="rejected" ' . ($statusFilter === 'rejected' ? 'selected' : '') . '>Rejected</option>';
$content .= '<option value="cancelled" ' . ($statusFilter === 'cancelled' ? 'selected' : '') . '>Cancelled</option>';
$content .= '</select>';
$content .= '</div>';
$content .= '<button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">';
$content .= '<i class="fas fa-search mr-2"></i>Search';
$content .= '</button>';
$content .= '</form>';
$content .= '</div>';
$content .= '</div>';

// Status Tabs
$content .= '<div class="flex flex-wrap gap-2 mb-6">';
$content .= '<a href="?' . http_build_query(array_merge($_GET, ['status' => '', 'page' => 1])) . '"';
$content .= ' class="px-4 py-2 rounded-lg text-sm font-medium ' . (empty($statusFilter) ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200') . ' transition-colors">';
$content .= 'All (' . $statusCounts['all'] . ')';
$content .= '</a>';
$content .= '<a href="?' . http_build_query(array_merge($_GET, ['status' => 'pending', 'page' => 1])) . '"';
$content .= ' class="px-4 py-2 rounded-lg text-sm font-medium ' . ($statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200') . ' transition-colors">';
$content .= 'Pending (' . $statusCounts['pending'] . ')';
$content .= '</a>';
$content .= '<a href="?' . http_build_query(array_merge($_GET, ['status' => 'approved', 'page' => 1])) . '"';
$content .= ' class="px-4 py-2 rounded-lg text-sm font-medium ' . ($statusFilter === 'approved' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200') . ' transition-colors">';
$content .= 'Approved (' . $statusCounts['approved'] . ')';
$content .= '</a>';
$content .= '<a href="?' . http_build_query(array_merge($_GET, ['status' => 'received', 'page' => 1])) . '"';
$content .= ' class="px-4 py-2 rounded-lg text-sm font-medium ' . ($statusFilter === 'received' ? 'bg-purple-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200') . ' transition-colors">';
$content .= 'Received (' . $statusCounts['received'] . ')';
$content .= '</a>';
$content .= '<a href="?' . http_build_query(array_merge($_GET, ['status' => 'refunded', 'page' => 1])) . '"';
$content .= ' class="px-4 py-2 rounded-lg text-sm font-medium ' . ($statusFilter === 'refunded' ? 'bg-green-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200') . ' transition-colors">';
$content .= 'Refunded (' . $statusCounts['refunded'] . ')';
$content .= '</a>';
$content .= '</div>';

// Returns Table
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">';

if (empty($returns)) {
    $content .= '<div class="p-12 text-center">';
    $content .= '<div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">';
    $content .= '<i class="fas fa-inbox text-4xl text-gray-400"></i>';
    $content .= '</div>';
    $content .= '<h3 class="text-lg font-medium text-gray-900 mb-2">No Returns Found</h3>';
    $content .= '<p class="text-gray-500">There are no return requests matching your criteria.</p>';
    $content .= '</div>';
} else {
    $content .= '<div class="overflow-x-auto">';
    $content .= '<table class="min-w-full divide-y divide-gray-200">';
    $content .= '<thead class="bg-gray-50">';
    $content .= '<tr>';
    $content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return #</th>';
    $content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>';
    $content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>';
    $content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>';
    $content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>';
    $content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>';
    $content .= '<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody class="bg-white divide-y divide-gray-200">';

    foreach ($returns as $ret) {
        $content .= '<tr class="hover:bg-gray-50">';
        $content .= '<td class="px-6 py-4 whitespace-nowrap">';
        $content .= '<span class="text-sm font-medium text-gray-900">' . safe_html($ret['return_number']) . '</span>';
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 whitespace-nowrap">';
        $content .= '<div class="text-sm text-gray-900">' . safe_html($ret['customer_name']) . '</div>';
        $content .= '<div class="text-sm text-gray-500">' . safe_html($ret['customer_email']) . '</div>';
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">';
        $content .= safe_html($ret['order_number']);
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">';
        $content .= safe_html(ucfirst(str_replace('_', ' ', $ret['reason_type'])));
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 whitespace-nowrap">';
        $content .= getAdminReturnStatusBadge($ret['status']);
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
        $content .= date('M j, Y', strtotime($ret['created_at']));
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">';
        $content .= '<a href="' . adminUrl('/returns/view.php?id=' . $ret['id']) . '"';
        $content .= ' class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors" title="View">';
        $content .= '<i class="fas fa-eye"></i>';
        $content .= '</a>';
        $content .= '</td>';
        $content .= '</tr>';
    }

    $content .= '</tbody>';
    $content .= '</table>';
    $content .= '</div>';
}

$content .= '</div>';

// Render the page
echo adminSidebarWrapper($pageTitle, $content, $currentPage);
