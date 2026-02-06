<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Get analytics data
$analytics = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'average_order_value' => 0,
    'total_customers' => 0,
    'orders_today' => 0,
    'revenue_this_month' => 0,
    'orders_this_month' => 0,
    'top_products' => []
];

if ($db) {
    try {
        // Total revenue
        $stmt = $db->query("SELECT COALESCE(SUM(total), 0) as revenue FROM orders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $analytics['total_revenue'] = $result['revenue'] ?? 0;

        // Total orders
        $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $analytics['total_orders'] = $result['count'] ?? 0;

        // Average order value
        if ($analytics['total_orders'] > 0) {
            $analytics['average_order_value'] = $analytics['total_revenue'] / $analytics['total_orders'];
        }

        // Total customers
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $analytics['total_customers'] = $result['count'] ?? 0;

        // Orders today
        $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $analytics['orders_today'] = $result['count'] ?? 0;

        // Revenue this month
        $stmt = $db->query("SELECT COALESCE(SUM(total), 0) as revenue FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $analytics['revenue_this_month'] = $result['revenue'] ?? 0;

        // Orders this month
        $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $analytics['orders_this_month'] = $result['count'] ?? 0;

        // Top products
        $analytics['top_products'] = [];
        try {
            $stmt = $db->query("
                SELECT p.name, SUM(oi.quantity) as sales, SUM(oi.price * oi.quantity) as revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status != 'cancelled'
                GROUP BY oi.product_id
                ORDER BY revenue DESC
                LIMIT 5
            ");
            $analytics['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting top products: " . $e->getMessage());
        }

    } catch (Exception $e) {
        error_log("Error getting analytics: " . $e->getMessage());
    }
}

// Generate analytics content
$content = '
<div class="w-full max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
        <p class="text-gray-600 mt-1">Track your store performance and key metrics</p>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">';

// Revenue card
$content .= adminStatCard('Total Revenue', 'R' . number_format($analytics['total_revenue'], 2), 'fas fa-dollar-sign', 'green', ['type' => 'increase', 'value' => '+12.5%']);

// Orders card
$content .= adminStatCard('Total Orders', number_format($analytics['total_orders']), 'fas fa-shopping-cart', 'blue', ['type' => 'increase', 'value' => '+5.2%']);

// Average order value
$content .= adminStatCard('Average Order Value', 'R' . number_format($analytics['average_order_value'], 2), 'fas fa-chart-line', 'purple', ['type' => 'increase', 'value' => '+2.1%']);

// Total customers
$content .= adminStatCard('Total Customers', number_format($analytics['total_customers']), 'fas fa-users', 'indigo', ['type' => 'increase', 'value' => '+8.3%']);

$content .= '
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Revenue Trend</h3>
                <p class="text-sm text-gray-600">Last 12 months</p>
            </div>
            <div class="p-6">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Orders Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Orders Trend</h3>
                <p class="text-sm text-gray-600">Last 12 months</p>
            </div>
            <div class="p-6">
                <div class="chart-container">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        ' . adminStatCard('Orders Today', number_format($analytics['orders_today']), 'fas fa-calendar-day', 'yellow', ['type' => 'increase', 'value' => 'Current day']) . '
        ' . adminStatCard('Revenue This Month', 'R' . number_format($analytics['revenue_this_month'], 2), 'fas fa-calendar-month', 'green', ['type' => 'increase', 'value' => 'Current month']) . '
        ' . adminStatCard('Orders This Month', number_format($analytics['orders_this_month']), 'fas fa-shopping-bag', 'blue', ['type' => 'increase', 'value' => 'Current month']) . '
    </div>

    <!-- Top Products and Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Selling Products</h3>
                <p class="text-sm text-gray-600">Best performers this month</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';

foreach ($analytics['top_products'] as $product) {
    $content .= '
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($product['name']) . '</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">' . $product['sales'] . '</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">R' . number_format($product['revenue'], 2) . '</div>
                            </td>
                        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Quick Insights</h3>
                <p class="text-sm text-gray-600">Key performance indicators</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">';

if ($analytics['total_orders'] > 0) {
    $conversion_rate = rand(2, 5); // Mock conversion rate
    $content .= '
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Conversion Rate</span>
                        <span class="text-sm font-medium text-green-600">' . $conversion_rate . '%</span>
                    </div>';
}

$repeat_customer_rate = rand(20, 40); // Mock repeat customer rate
$content .= '
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Repeat Customer Rate</span>
                        <span class="text-sm font-medium text-blue-600">' . $repeat_customer_rate . '%</span>
                    </div>';

$bounce_rate = rand(30, 60); // Mock bounce rate
$content .= '
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Site Bounce Rate</span>
                        <span class="text-sm font-medium text-orange-600">' . $bounce_rate . '%</span>
                    </div>';

$avg_session_time = rand(120, 300); // Mock average session time in seconds
$content .= '
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Avg. Session Duration</span>
                        <span class="text-sm font-medium text-purple-600">' . gmdate('i:s', $avg_session_time) . '</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Summary -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Performance Summary</h3>
            <p class="text-sm text-gray-600">Overall store health and trends</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">+15.2%</div>
                    <div class="text-sm text-gray-600">Revenue Growth</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">+8.7%</div>
                    <div class="text-sm text-gray-600">Order Volume</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">+12.3%</div>
                    <div class="text-sm text-gray-600">Customer Growth</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">4.8/5</div>
                    <div class="text-sm text-gray-600">Customer Rating</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const revenueCtx = document.getElementById("revenueChart").getContext("2d");
new Chart(revenueCtx, {
    type: "line",
    data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
            label: "Revenue",
            data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 32000, 30000, 35000, 38000, 42000],
            borderColor: "rgb(16, 185, 129)",
            backgroundColor: "rgba(16, 185, 129, 0.1)",
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return "R" + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Orders Chart
const ordersCtx = document.getElementById("ordersChart").getContext("2d");
new Chart(ordersCtx, {
    type: "bar",
    data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
            label: "Orders",
            data: [45, 67, 52, 89, 78, 95, 88, 102, 98, 115, 125, 140],
            backgroundColor: "rgba(59, 130, 246, 0.8)",
            borderColor: "rgb(59, 130, 246)",
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Analytics', $content, 'analytics');
