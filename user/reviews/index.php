<?php
/**
 * Product Reviews Page - User Dashboard
 * Display and manage user product reviews
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

AuthMiddleware::requireUser();

$currentUser = AuthMiddleware::getCurrentUser();
$db = Services::db();

$reviews = [];
$reviewStats = [
    'total' => 0,
    'avg_rating' => 0,
    'published' => 0,
    'pending' => 0
];

$message = '';
$error = '';

// Handle delete review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    CsrfMiddleware::validate();
    try {
        $reviewId = intval($_POST['review_id'] ?? 0);

        // Verify the review belongs to this user and is pending (can only delete pending reviews)
        $stmt = $db->prepare("SELECT id, status FROM product_reviews WHERE id = ? AND user_id = ?");
        $stmt->execute([$reviewId, $currentUser['id']]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($review) {
            // Delete the review
            $stmt = $db->prepare("DELETE FROM product_reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$reviewId, $currentUser['id']]);
            $message = 'Review deleted successfully!';
        } else {
            $error = 'Review not found or you do not have permission to delete it.';
        }
    } catch (Exception $e) {
        error_log("Error deleting review: " . $e->getMessage());
        $error = 'An error occurred while deleting the review.';
    }
}

try {

    // Verify the session user exists in the database
    $stmt = $db->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$currentUser['id']]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If session user not found in database, redirect to login
    if (!$dbUser) {
        error_log("User reviews page: Session user ID {$currentUser['id']} not found in database");
        redirect(userUrl('/login/'));
    }
    
    // Update current user with database data
    $currentUser['name'] = trim($dbUser['first_name'] . ' ' . $dbUser['last_name']);

    // Get all reviews for this user from product_reviews table
    $stmt = $db->prepare("SELECT pr.*, p.name AS product_name, p.slug AS product_slug, p.images AS product_images FROM product_reviews pr LEFT JOIN products p ON pr.product_id = p.id WHERE pr.user_id = ? ORDER BY pr.created_at DESC");
    $stmt->execute([$currentUser['id']]);
    $allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $totalRating = 0;
    $publishedCount = 0;
    $pendingCount = 0;

    foreach ($allReviews as $review) {
        $totalRating += (int)$review['rating'];
        if ($review['status'] === 'approved') {
            $publishedCount++;
        } elseif ($review['status'] === 'pending') {
            $pendingCount++;
        }
    }

    $reviewStats['total'] = count($allReviews);
    $reviewStats['avg_rating'] = $reviewStats['total'] > 0 ? round($totalRating / $reviewStats['total'], 1) : 0;
    $reviewStats['published'] = $publishedCount;
    $reviewStats['pending'] = $pendingCount;

    // Get filter
    $filter = $_GET['filter'] ?? 'all';
    $filteredReviews = $allReviews;

    if ($filter === 'published') {
        $filteredReviews = array_filter($allReviews, function($review) {
            return $review['status'] === 'approved';
        });
    } elseif ($filter === 'pending') {
        $filteredReviews = array_filter($allReviews, function($review) {
            return $review['status'] === 'pending';
        });
    }

    $reviews = $filteredReviews;

} catch (Exception $e) {
    error_log("Error fetching reviews: " . $e->getMessage());
}

$pageTitle = "Product Reviews";
$currentPage = "reviews";

// Helper function to generate star rating HTML
function generateStars($rating, $interactive = false) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-yellow-400"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $stars .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        } else {
            $stars .= '<i class="far fa-star text-gray-300"></i>';
        }
    }
    return $stars;
}

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Welcome Back Card -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg shadow-md text-white p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-yellow-100 text-sm">Manage your product reviews and ratings</p>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-star text-2xl"></i>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <!-- Main Content - Product Reviews -->
        <div class="lg:w-3/4">
            <!-- Review Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-blue-100">
                            <i class="fas fa-star text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900"><?= $reviewStats['total'] ?></p>
                            <p class="text-sm text-gray-600">Total Reviews</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-yellow-100">
                            <i class="fas fa-star-half-alt text-yellow-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900"><?= $reviewStats['avg_rating'] ?></p>
                            <p class="text-sm text-gray-600">Average Rating</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900"><?= $reviewStats['published'] ?></p>
                            <p class="text-sm text-gray-600">Published</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-gray-100">
                            <i class="fas fa-clock text-gray-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-xl font-semibold text-gray-900"><?= $reviewStats['pending'] ?></p>
                            <p class="text-sm text-gray-600">Pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">My Reviews</h3>
                    <div class="flex space-x-2">
                        <a href="<?= shopUrl('/') ?>" class="px-4 py-2 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-md hover:bg-green-100 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Write Review
                        </a>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-4">
                    <a href="<?= userUrl('/reviews/') ?>"
                       class="py-2 px-4 rounded-lg text-sm font-medium <?= $filter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                        All Reviews (<?= $reviewStats['total'] ?>)
                    </a>
                    <a href="<?= userUrl('/reviews/?filter=published') ?>"
                       class="py-2 px-4 rounded-lg text-sm font-medium <?= $filter === 'published' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                        Published (<?= $reviewStats['published'] ?>)
                    </a>
                    <a href="<?= userUrl('/reviews/?filter=pending') ?>"
                       class="py-2 px-4 rounded-lg text-sm font-medium <?= $filter === 'pending' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition-colors">
                        Pending (<?= $reviewStats['pending'] ?>)
                    </a>
                </div>
            </div>

            <!-- Reviews List -->
            <?php if (empty($reviews)): ?>
                <!-- Empty State -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-star text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        <?= $filter === 'all' ? 'No reviews yet' : 'No ' . $filter . ' reviews' ?>
                    </h3>
                    <p class="text-gray-500 mb-6">
                        <?= $filter === 'all' ? 'When you review products, they will appear here.' : 'Reviews with this status will appear here.' ?>
                    </p>
                    <a href="<?= shopUrl('/') ?>" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                        <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start space-x-3">
                                    <div class="w-16 h-16 bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center">
                                        <?php if (!empty($review['product_images'])): ?>
                                            <?php
                                                try {
                                                    $imageUrls = explode(',', $review['product_images']);
                                                    $firstImage = trim($imageUrls[0]);
                                                    if (!empty($firstImage)):
                                                        $imagePath = ltrim(str_replace(rurl('/'), '', $firstImage), '/');
                                                        ?>
                                                        <img src="<?= url($imagePath) ?>"
                                                             alt="<?= htmlspecialchars($review['product_name'] ?? 'Product') ?>"
                                                             class="w-14 h-14 object-cover rounded">
                                                    <?php else: ?>
                                                        <i class="fas fa-cannabis text-green-600 text-xl"></i>
                                                    <?php endif;
                                                } catch (Exception $e) {
                                                    echo '<i class="fas fa-cannabis text-green-600 text-xl"></i>';
                                                }
                                            ?>
                                        <?php else: ?>
                                            <i class="fas fa-cannabis text-green-600 text-xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">
                                            <?php if (!empty($review['product_slug'])): ?>
                                                <a href="<?= productUrl($review['product_slug']) ?>" class="hover:text-green-600 transition-colors">
                                                    <?= htmlspecialchars($review['product_name'] ?? 'Product') ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($review['product_name'] ?? 'Product') ?>
                                            <?php endif; ?>
                                        </h4>
                                        <div class="flex items-center mt-1">
                                            <div class="flex text-yellow-400">
                                                <?= generateStars((int)$review['rating']) ?>
                                            </div>
                                            <span class="text-sm text-gray-600 ml-2">
                                                <?= number_format((float)$review['rating'], 1) ?> (<?= date('M j, Y', strtotime($review['created_at'])) ?>)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($review['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Published
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($review['title'])): ?>
                                <h5 class="font-medium text-gray-900 mb-2"><?= htmlspecialchars($review['title']) ?></h5>
                            <?php endif; ?>
                            <?php if (!empty($review['body'])): ?>
                                <p class="text-gray-700 mb-3"><?= htmlspecialchars($review['body']) ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-500">
                                    <?= (int)$review['helpful_count'] ?> people found this helpful
                                </p>
                                <div class="flex space-x-3 items-center">
                                    <?php if ($review['status'] !== 'approved'): ?>
                                        <button class="text-sm text-gray-600 hover:text-green-600 transition-colors" disabled title="Edit coming soon">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_review">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 transition-colors">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
