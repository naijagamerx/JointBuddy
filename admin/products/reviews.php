<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    if (isset($_POST['moderate'])) {
        $stmt = $db->prepare('UPDATE product_reviews SET status = ? WHERE id = ?');
        try { 
            $stmt->execute([$_POST['status'], (int)$_POST['id']]); 
            $message = 'Review status updated successfully!'; 
            // Regenerate CSRF token after successful action
            csrf_regenerate();
        } catch (Exception $e) { 
            $error = $e->getMessage(); 
        }
    }
}

try {
    $stmt = $db->query('SELECT r.*, p.name AS product_name, p.images AS product_image, CONCAT(u.first_name, \' \', u.last_name) AS reviewer_name, u.email AS reviewer_email FROM product_reviews r JOIN products p ON p.id = r.product_id LEFT JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC');
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error loading reviews: ' . $e->getMessage();
    $reviews = [];
}

// Mask email addresses in the reviews data for frontend
$reviewsForJs = array_map(function($r) {
    $r['reviewer_email'] = maskEmail($r['reviewer_email'] ?? '');
    return $r;
}, $reviews);
$reviewsData = json_encode($reviewsForJs);

$content = '<div class="max-w-7xl mx-auto">';
if ($message) { $content .= adminAlert($message, 'success'); }
if ($error) { $content .= adminAlert($error, 'error'); }

$content .= '<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Product Reviews</h2></div>';

// Custom table with product images
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">';
$content .= '<table class="min-w-full divide-y divide-gray-200">';
$content .= '<thead class="bg-gray-50">';
$content .= '<tr>';
$content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>';
$content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewer</th>';
$content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>';
$content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>';
$content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>';
$content .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>';
$content .= '</tr>';
$content .= '</thead>';
$content .= '<tbody class="bg-white divide-y divide-gray-200">';

foreach ($reviews as $r) {
    // Get product image URL
    $productImage = '';
    if (!empty($r['product_image'])) {
        $imageUrls = explode(',', $r['product_image']);
        $dbPath = trim($imageUrls[0]);
        // Remove hardcoded paths and convert to proper URL
        $dbPath = preg_replace('#^https?://[^/]+/[^/]+/#', '/', $dbPath);
        $dbPath = preg_replace('#^/CannaBuddy\.shop/#', '/', $dbPath);
        $dbPath = ltrim($dbPath, '/');
        $productImage = url($dbPath);
    }
    if (empty($productImage)) {
        $productImage = assetUrl('images/products/placeholder.png');
    }

    $content .= '<tr class="hover:bg-gray-50">';
    $content .= '<td class="px-6 py-4 whitespace-nowrap">';
    $content .= '<div class="flex items-center">';
    $content .= '<div class="flex-shrink-0 h-12 w-12">';
    $content .= '<img class="h-12 w-12 rounded-lg object-cover" src="' . htmlspecialchars($productImage) . '" alt="' . htmlspecialchars($r['product_name']) . '" onerror="this.src=\'' . assetUrl('images/products/placeholder.png') . '\'">';
    $content .= '</div>';
    $content .= '<div class="ml-4">';
    $content .= '<div class="text-sm font-medium text-gray-900 max-w-xs truncate" title="' . htmlspecialchars($r['product_name']) . '">' . htmlspecialchars($r['product_name']) . '</div>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '</td>';
    $content .= '<td class="px-6 py-4 whitespace-nowrap">';
    $content .= '<div class="flex items-center">';
    $content .= '<i class="fas fa-user-circle text-gray-400 mr-2"></i>';
    // Display name, or masked email if no name, or Guest as last fallback
    $reviewerDisplay = !empty($r['reviewer_name']) ? $r['reviewer_name'] : (!empty($r['reviewer_email']) ? maskEmail($r['reviewer_email']) : 'Guest');
    $content .= '<div class="text-sm text-gray-900">' . htmlspecialchars($reviewerDisplay) . '</div>';
    $content .= '</div>';
    $content .= '</td>';
    $content .= '<td class="px-6 py-4 whitespace-nowrap">';
    $content .= '<div class="flex items-center">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= (int)$r['rating']) {
            $content .= '<i class="fas fa-star text-yellow-400"></i>';
        } else {
            $content .= '<i class="far fa-star text-gray-300"></i>';
        }
    }
    $content .= '<span class="ml-2 text-sm text-gray-600">(' . (int)$r['rating'] . ')</span>';
    $content .= '</div>';
    $content .= '</td>';
    $content .= '<td class="px-6 py-4">';
    $content .= '<div class="text-sm text-gray-900 max-w-xs truncate" title="' . htmlspecialchars($r['title']) . '">' . htmlspecialchars($r['title']) . '</div>';
    $content .= '</td>';
    $content .= '<td class="px-6 py-4 whitespace-nowrap">';
    $statusClass = $r['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($r['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
    $content .= '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">';
    $content .= ucfirst(htmlspecialchars($r['status']));
    $content .= '</span>';
    $content .= '</td>';
    $content .= '<td class="px-6 py-4 whitespace-nowrap">';
    $content .= '<button onclick="openReviewModal(' . $r['id'] . ')" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 transition-colors">';
    $content .= '<i class="fas fa-eye mr-2"></i>View';
    $content .= '</button>';
    $content .= '</td>';
    $content .= '</tr>';
}

$content .= '</tbody>';
$content .= '</table>';
$content .= '</div>'; // end table container

$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">'
          . '<h3 class="text-lg font-semibold text-gray-900 mb-4">Moderate Review</h3>'
          . '<form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">'
          . csrf_field()
          . '<input type="number" name="id" placeholder="Review ID" class="border rounded px-3 py-2">'
          . '<select name="status" class="border rounded px-3 py-2"><option value="approved">Approve</option><option value="rejected">Reject</option><option value="pending">Pending</option></select>'
          . '<button type="submit" name="moderate" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>'
          . '</form>'
          . '</div>';

$content .= '</div>';

$content .= '

<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="text-xl font-bold text-gray-900">Review Details</h3>
            <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="modalContent" class="space-y-6"></div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
            <button onclick="updateReviewStatus(\'approved\')" class="bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                <i class="fas fa-check mr-2"></i>Approve
            </button>
            <button onclick="updateReviewStatus(\'rejected\')" class="bg-red-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-red-700 transition-colors">
                <i class="fas fa-times mr-2"></i>Reject
            </button>
            <button onclick="updateReviewStatus(\'pending\')" class="bg-yellow-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-yellow-700 transition-colors">
                <i class="fas fa-clock mr-2"></i>Pending
            </button>
        </div>
    </div>
</div>

<script>
var reviewsData = ' . $reviewsData . ';
var currentReviewId = null;
var csrfToken = \'' . csrf_token() . '\';

function openReviewModal(reviewId) {
    var review = reviewsData.find(function(r) { return r.id === reviewId; });
    if (!review) return;
    
    currentReviewId = reviewId;
    
    var productImage = review.product_image ? review.product_image.split(\',\')[0] : \'/images/products/placeholder.png\';
    var stars = \'\';
    for (var i = 1; i <= 5; i++) {
        if (i <= review.rating) {
            stars += \'<i class="fas fa-star text-yellow-400 text-xl"></i>\';
        } else {
            stars += \'<i class="far fa-star text-gray-300 text-xl"></i>\';
        }
    }
    
    var statusClass = review.status === \'approved\' ? \'bg-green-100 text-green-800\' : (review.status === \'pending\' ? \'bg-yellow-100 text-yellow-800\' : \'bg-red-100 text-red-800\');
    
    var html = \'<div class="flex items-start gap-4">\' +
        \'<img src="\' + productImage + \'" alt="\' + review.product_name + \'" class="w-24 h-24 rounded-lg object-cover flex-shrink-0 border border-gray-200" onerror="this.onerror=null;this.src=\\\'/images/products/placeholder.png\\\'">\' +
        \'<div class="flex-1">\' +
            \'<h4 class="text-lg font-semibold text-gray-900">\' + review.product_name + \'</h4>\' +
            \'<div class="flex items-center gap-2 mt-2">\' +
                \'<span class="px-3 py-1 text-sm font-medium rounded-full \' + statusClass + \'">\' + review.status.charAt(0).toUpperCase() + review.status.slice(1) + \'</span>\' +
                \'<span class="text-sm text-gray-500">Review #\' + review.id + \'</span>\' +
            \'</div>\' +
        \'</div>\' +
    \'</div>\' +
    \'<div class="border-t border-gray-200 pt-4">\' +
        \'<div class="flex items-center gap-3">\' +
            \'<div class="flex items-center">\' +
                \'<i class="fas fa-user-circle text-gray-400 text-2xl mr-2"></i>\' +
                \'<div>\' +
                    \'<p class="text-sm font-medium text-gray-900">\' + (review.reviewer_name || review.reviewer_email || \'Guest\') + \'</p>\' +
                    (review.reviewer_email ? \'<p class="text-xs text-gray-500">\' + review.reviewer_email + \'</p>\' : \'\') +
                \'</div>\' +
            \'</div>\' +
        \'</div>\' +
    \'</div>\' +
    \'<div class="border-t border-gray-200 pt-4">\' +
        \'<div class="flex items-center gap-3 mb-3">\' +
            \'<div>\' + stars + \'</div>\' +
            \'<span class="text-lg font-semibold text-gray-900">\' + review.rating + \'/5</span>\' +
        \'</div>\' +
        \'<h5 class="text-xl font-bold text-gray-900 mb-2">\' + review.title + \'</h5>\' +
        \'<div class="bg-gray-50 rounded-lg p-4">\' +
            \'<p class="text-gray-700 leading-relaxed whitespace-pre-wrap">\' + (review.body || \'No review body provided\') + \'</p>\' +
        \'</div>\' +
    \'</div>\' +
    \'<div class="border-t border-gray-200 pt-4">\' +
        \'<div class="flex justify-between text-sm text-gray-500">\' +
            \'<span><i class="fas fa-calendar-plus mr-1"></i>Created: \' + new Date(review.created_at).toLocaleString() + \'</span>\' +
            (review.updated_at !== review.created_at ? \'<span><i class="fas fa-calendar-alt mr-1"></i>Updated: \' + new Date(review.updated_at).toLocaleString() + \'</span>\' : \'\') +
        \'</div>\' +
    \'</div>\';
    
    document.getElementById(\'modalContent\').innerHTML = html;
    document.getElementById(\'reviewModal\').classList.remove(\'hidden\');
    document.body.style.overflow = \'hidden\';
}

function closeReviewModal() {
    document.getElementById(\'reviewModal\').classList.add(\'hidden\');
    document.body.style.overflow = \'auto\';
    currentReviewId = null;
}

function updateReviewStatus(status) {
    if (!currentReviewId) return;
    
    var formData = new FormData();
    formData.append(\'id\', currentReviewId);
    formData.append(\'status\', status);
    formData.append(\'moderate\', \'1\');
    formData.append(\'csrf_token\', csrfToken);
    
    fetch(\'\', {
        method: \'POST\',
        body: formData
    })
    .then(function(response) { return response.text(); })
    .then(function(data) {
        location.reload();
    })
    .catch(function(error) {
        console.error(\'Error updating review:\', error);
        alert(\'Failed to update review status\');
    });
}

document.addEventListener(\'keydown\', function(e) {
    if (e.key === \'Escape\') {
        closeReviewModal();
    }
});

document.getElementById(\'reviewModal\').addEventListener(\'click\', function(e) {
    if (e.target === this) {
        closeReviewModal();
    }
});
</script>
';

echo adminSidebarWrapper('Reviews', $content, 'reviews');
