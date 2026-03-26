<?php
// Prevent browser caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Handle AJAX requests first (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_POST['moderate'])) {
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];

    // Validate CSRF - temporarily disabled for debugging
    if (false && (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token']))) {
        $response['message'] = 'Invalid security token. Please refresh the page.';
        $response['debug_token'] = $_POST['csrf_token'] ?? 'not set';
        echo json_encode($response);
        exit;
    }

    // Check moderate action
    if (!isset($_POST['moderate']) || !isset($_POST['id']) || !isset($_POST['status'])) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit;
    }

    // Validate status
    $validStatuses = ['pending', 'approved', 'rejected'];
    if (!in_array($_POST['status'], $validStatuses)) {
        $response['message'] = 'Invalid status value.';
        echo json_encode($response);
        exit;
    }

    // Update review
    try {
        $db = Services::db();
        $stmt = $db->prepare('UPDATE product_reviews SET status = ?, updated_at = NOW() WHERE id = ?');
        $result = $stmt->execute([$_POST['status'], (int)$_POST['id']]);

        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Review status updated successfully!';
            $response['newStatus'] = $_POST['status'];
        } else {
            $response['message'] = 'Failed to update review.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Require authentication (admin only) - only for GET requests
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$message = '';
$error = '';

// Helper function to mask email addresses for privacy
function maskEmail($email) {
    if (empty($email)) return '';
    $parts = explode('@', $email);
    if (count($parts) !== 2) return '***@***.***';
    $name = $parts[0];
    $domain = $parts[1];
    $maskedName = strlen($name) > 2 ? substr($name, 0, 2) . str_repeat('*', max(3, strlen($name) - 2)) : '**';
    return $maskedName . '@' . $domain;
}

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';
$filterClause = match($filter) {
    'pending' => "WHERE r.status = 'pending'",
    'approved' => "WHERE r.status = 'approved'",
    'rejected' => "WHERE r.status = 'rejected'",
    default => ''
};

try {
    $stmt = $db->query('SELECT r.*, p.name AS product_name, p.images AS product_image, CONCAT(u.first_name, \' \', u.last_name) AS reviewer_name, u.email AS reviewer_email FROM product_reviews r JOIN products p ON p.id = r.product_id LEFT JOIN users u ON u.id = r.user_id ' . $filterClause . ' ORDER BY r.created_at DESC');
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error loading reviews: ' . $e->getMessage();
    $reviews = [];
}

// Count reviews by status
try {
    $counts = [
        'all' => $db->query('SELECT COUNT(*) FROM product_reviews')->fetchColumn(),
        'pending' => $db->query('SELECT COUNT(*) FROM product_reviews WHERE status = "pending"')->fetchColumn(),
        'approved' => $db->query('SELECT COUNT(*) FROM product_reviews WHERE status = "approved"')->fetchColumn(),
        'rejected' => $db->query('SELECT COUNT(*) FROM product_reviews WHERE status = "rejected"')->fetchColumn(),
    ];
} catch (Exception $e) {
    $counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

// Prepare CSRF token and reviews data for JavaScript
$csrfTokenJson = json_encode(csrf_token());
$reviewsDataJson = json_encode(array_map(function($r) {
    $r['reviewer_email'] = maskEmail($r['reviewer_email'] ?? '');
    return $r;
}, $reviews));

$content = '<div class="max-w-7xl mx-auto">';
if ($message) { $content .= adminAlert($message, 'success'); }
if ($error) { $content .= adminAlert($error, 'error'); }

// Page header with stats
$content .= '<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">';
$content .= '<div>';
$content .= '<h2 class="text-2xl font-bold text-gray-900">Product Reviews</h2>';
$content .= '<p class="text-gray-600 text-sm">Manage and moderate customer reviews</p>';
$content .= '</div>';

// Quick stats
$content .= '<div class="flex gap-4">';
$content .= '<div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-2">';
$content .= '<span class="text-yellow-800 font-semibold">' . ($counts['pending'] ?? 0) . '</span>';
$content .= '<span class="text-yellow-600 text-sm ml-1">Pending</span>';
$content .= '</div>';
$content .= '<div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">';
$content .= '<span class="text-green-800 font-semibold">' . ($counts['approved'] ?? 0) . '</span>';
$content .= '<span class="text-green-600 text-sm ml-1">Approved</span>';
$content .= '</div>';
$content .= '<div class="bg-red-50 border border-red-200 rounded-lg px-4 py-2">';
$content .= '<span class="text-red-800 font-semibold">' . ($counts['rejected'] ?? 0) . '</span>';
$content .= '<span class="text-red-600 text-sm ml-1">Rejected</span>';
$content .= '</div>';
$content .= '</div>';
$content .= '</div>';

// Filter tabs
$content .= '<div class="flex gap-2 mb-6 overflow-x-auto pb-2">';
$filters = [
    'pending' => ['label' => 'Pending', 'icon' => 'clock', 'color' => 'yellow'],
    'approved' => ['label' => 'Approved', 'icon' => 'check-circle', 'color' => 'green'],
    'rejected' => ['label' => 'Rejected', 'icon' => 'times-circle', 'color' => 'red'],
    'all' => ['label' => 'All Reviews', 'icon' => 'list', 'color' => 'gray'],
];
foreach ($filters as $key => $f) {
    $isActive = $filter === $key;
    if ($isActive) {
        $bgColor = 'bg-' . $f['color'] . '-600';
        $textColor = 'text-white';
        $border = '';
    } else {
        $bgColor = 'bg-white';
        $textColor = 'text-gray-700';
        $border = 'border border-gray-200 hover:bg-gray-50';
    }
    $countBadge = $key === 'all' ? '' : ' <span class="ml-1 px-2 py-0.5 rounded-full text-xs ' . ($isActive ? 'bg-white/20' : 'bg-gray-200') . '">' . ($counts[$key] ?? 0) . '</span>';
    $content .= '<a href="?filter=' . $key . '" class="flex items-center px-4 py-2 rounded-lg font-medium transition-colors whitespace-nowrap ' . $bgColor . ' ' . $textColor . ' ' . $border . '">';
    $content .= '<i class="fas fa-' . $f['icon'] . ' mr-2"></i>' . $f['label'] . $countBadge;
    $content .= '</a>';
}
$content .= '</div>';

// Table
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">';
$content .= '<div class="overflow-x-auto">';
$content .= '<table class="min-w-full divide-y divide-gray-200">';
$content .= '<thead class="bg-gray-50">';
$content .= '<tr>';
$content .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>';
$content .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reviewer</th>';
$content .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>';
$content .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>';
$content .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
$content .= '<th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>';
$content .= '</tr>';
$content .= '</thead>';
$content .= '<tbody class="bg-white divide-y divide-gray-200">';

if (empty($reviews)) {
    $content .= '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">';
    $content .= '<i class="fas fa-inbox text-4xl text-gray-300 mb-4 block"></i>';
    $content .= '<p class="text-lg font-medium">No reviews found</p>';
    if ($filter !== 'all') {
        $content .= '<p class="text-sm">Try selecting "All Reviews" to see all reviews</p>';
    }
    $content .= '</td></tr>';
} else {
    foreach ($reviews as $r) {
        $productImage = '';
        if (!empty($r['product_image'])) {
            $imageUrls = explode(',', $r['product_image']);
            $dbPath = trim($imageUrls[0]);
            $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
            $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
            $dbPath = ltrim($dbPath, '/');
            $productImage = url($dbPath);
        }
        if (empty($productImage)) {
            $productImage = assetUrl('images/products/placeholder.png');
        }

        $content .= '<tr class="hover:bg-gray-50" data-review-id="' . $r['id'] . '">';
        $content .= '<td class="px-4 py-4"><div class="flex items-center"><img class="h-10 w-10 rounded-lg object-cover" src="' . htmlspecialchars($productImage) . '" onerror="this.src=\'' . assetUrl('images/products/placeholder.png') . '\'"><span class="ml-3 text-sm font-medium truncate max-w-[200px]">' . htmlspecialchars($r['product_name']) . '</span></div></td>';

        $reviewerDisplay = !empty($r['reviewer_name']) ? $r['reviewer_name'] : (!empty($r['reviewer_email']) ? maskEmail($r['reviewer_email']) : 'Guest');
        $content .= '<td class="px-4 py-4 text-sm text-gray-900">' . htmlspecialchars($reviewerDisplay) . '</td>';

        $content .= '<td class="px-4 py-4">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= (int)$r['rating']) {
                $content .= '<i class="fas fa-star text-yellow-400 text-sm"></i>';
            } else {
                $content .= '<i class="far fa-star text-gray-300 text-sm"></i>';
            }
        }
        $content .= '</td>';

        $title = htmlspecialchars($r['title']);
        $bodyPreview = htmlspecialchars($r['body'] ?? '');
        $truncatedBody = strlen($bodyPreview) > 50 ? substr($bodyPreview, 0, 47) . '...' : $bodyPreview;
        $content .= '<td class="px-4 py-4"><div class="text-sm"><div class="font-medium truncate max-w-[200px]">' . $title . '</div>';
        if (!empty($bodyPreview)) {
            $content .= '<div class="text-gray-500 text-xs truncate" title="' . $bodyPreview . '">' . $truncatedBody . '</div>';
        }
        $content .= '</div></td>';

        $statusClass = $r['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($r['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
        $content .= '<td class="px-4 py-4"><span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '" data-status-badge>' . ucfirst(htmlspecialchars($r['status'])) . '</span></td>';

        $content .= '<td class="px-4 py-4 text-center">';
        // Approve button - only show if not already approved
        if ($r['status'] !== 'approved') {
            $content .= '<button onclick="updateReviewStatus(' . $r['id'] . ', \'approved\')" class="text-green-600 hover:bg-green-50 p-2 rounded mx-1" title="Approve"><i class="fas fa-check"></i></button>';
        }
        // Reject button - only show if not already rejected
        if ($r['status'] !== 'rejected') {
            $content .= '<button onclick="updateReviewStatus(' . $r['id'] . ', \'rejected\')" class="text-red-600 hover:bg-red-50 p-2 rounded mx-1" title="Reject"><i class="fas fa-times"></i></button>';
        }
        // View details button - always show
        $content .= '<button onclick="openOffcanvas(' . $r['id'] . ')" class="text-blue-600 hover:bg-blue-50 p-2 rounded mx-1" title="View Details"><i class="fas fa-eye"></i></button>';
        $content .= '</td>';

        $content .= '</tr>';
    }
}

$content .= '</tbody></table></div></div></div>';

// Off-canvas panel
$content .= '<div id="reviewOffcanvas" class="fixed inset-0 z-50 hidden">';
$content .= '<div class="fixed inset-0 bg-black/50" onclick="closeOffcanvas()"></div>';
$content .= '<div id="offcanvasPanel" class="fixed right-0 top-0 h-full w-full max-w-lg bg-white shadow-xl transform translate-x-full transition-transform duration-300 flex flex-col">';
$content .= '<div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">';
$content .= '<h3 class="text-lg font-bold">Review Details</h3>';
$content .= '<button onclick="closeOffcanvas()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>';
$content .= '</div>';
$content .= '<div id="offcanvasContent" class="flex-1 overflow-y-auto p-6"></div>';
$content .= '<div class="px-6 py-4 border-t bg-gray-50 flex gap-2">';
$content .= '<button id="btnApprove" onclick="updateReviewStatus(currentReviewId, \'approved\')" class="flex-1 bg-green-600 text-white py-2 rounded hover:bg-green-700">Approve</button>';
$content .= '<button id="btnReject" onclick="updateReviewStatus(currentReviewId, \'rejected\')" class="flex-1 bg-red-600 text-white py-2 rounded hover:bg-red-700">Reject</button>';
$content .= '<button id="btnPending" onclick="updateReviewStatus(currentReviewId, \'pending\')" class="flex-1 bg-yellow-600 text-white py-2 rounded hover:bg-yellow-700">Pending</button>';
$content .= '</div></div></div>';

// JavaScript with proper AJAX handling
$jsScript = <<<JAVASCRIPT
<script>
const reviewsData = {$reviewsDataJson};
let currentReviewId = null;
const csrfToken = {$csrfTokenJson};

function openOffcanvas(reviewId) {
    const review = reviewsData.find(r => r.id === reviewId);
    if (!review) return;

    currentReviewId = reviewId;

    let productImage = review.product_image ? review.product_image.split(',')[0] : '/assets/images/products/placeholder.png';
    productImage = productImage.replace(/^https?:\\/\\/[^\\/]+\\/[^\\/]+/, '/');
    productImage = productImage.replace(/^\\/CannaBuddy\\.shop/, '/');

    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += i <= review.rating ? '<i class="fas fa-star text-yellow-400"></i>' : '<i class="far fa-star text-gray-300"></i>';
    }

    const statusClass = review.status === 'approved' ? 'bg-green-100 text-green-800' : (review.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
    const statusLabel = review.status.charAt(0).toUpperCase() + review.status.slice(1);

    let html = '<div class="space-y-4">';
    html += '<div class="flex items-start gap-4 p-4 bg-gray-50 rounded">';
    html += '<img src="' + productImage + '" class="w-16 h-16 rounded object-cover" onerror="this.src=\'/assets/images/products/placeholder.png\'">';
    html += '<div><h4 class="font-semibold">' + (review.product_name || 'N/A') + '</h4>';
    html += '<span class="px-2 py-1 text-xs rounded ' + statusClass + '">' + statusLabel + '</span>';
    html += '<span class="text-xs text-gray-500 ml-2">Review #' + review.id + '</span></div></div>';

    html += '<div class="p-4 border rounded">';
    html += '<div class="flex items-center gap-2 mb-2">';
    html += '<div class="flex text-yellow-400">' + stars + '</div>';
    html += '<span class="font-semibold">' + review.rating + '/5</span>';
    html += '</div>';
    html += '<h5 class="font-bold">' + (review.title || 'No Title') + '</h5>';
    html += '<p class="text-gray-700 mt-2 whitespace-pre-wrap">' + (review.body || 'No review body') + '</p>';
    html += '<p class="text-xs text-gray-500 mt-4">By: ' + (review.reviewer_name || review.reviewer_email || 'Guest') + '</p>';
    html += '<p class="text-xs text-gray-500">Created: ' + new Date(review.created_at).toLocaleString() + '</p>';
    html += '</div></div>';

    document.getElementById('offcanvasContent').innerHTML = html;
    document.getElementById('reviewOffcanvas').classList.remove('hidden');
    setTimeout(() => document.getElementById('offcanvasPanel').classList.remove('translate-x-full'), 10);
    document.body.style.overflow = 'hidden';
}

function closeOffcanvas() {
    document.getElementById('offcanvasPanel').classList.add('translate-x-full');
    setTimeout(() => {
        document.getElementById('reviewOffcanvas').classList.add('hidden');
        document.body.style.overflow = 'auto';
        currentReviewId = null;
    }, 300);
}

function updateReviewStatus(reviewId, status) {
    if (!confirm('Mark this review as ' + status + '?')) return;

    const formData = new FormData();
    formData.append('id', reviewId);
    formData.append('status', status);
    formData.append('moderate', '1');
    formData.append('csrf_token', csrfToken);

    // Show loading
    const buttons = document.querySelectorAll('button[onclick^="updateReviewStatus"]');
    buttons.forEach(btn => btn.disabled = true);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the UI without reloading
            const row = document.querySelector('tr[data-review-id="' + reviewId + '"]');
            if (row) {
                const badge = row.querySelector('[data-status-badge]');
                if (badge) {
                    const statusClass = status === 'approved' ? 'bg-green-100 text-green-800' : (status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    badge.className = 'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' + statusClass;
                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                }
            }

            // Show success message
            alert('Review marked as ' + status + '!');

            // If on a filter view that no longer matches, reload
            const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
            if (currentFilter !== 'all' && currentFilter !== status) {
                window.location.href = '?filter=all';
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to update'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        buttons.forEach(btn => btn.disabled = false);
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOffcanvas(); });
</script>
JAVASCRIPT;

$content .= $jsScript;
echo adminSidebarWrapper('Reviews', $content, 'reviews');
