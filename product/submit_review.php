<?php
/**
 * Submit Product Review Handler
 * CannaBuddy.shop
 * 
 * Security Features:
 * - CSRF token validation
 * - Rate limiting (1 review per product per 24 hours)
 * - Input validation and sanitization
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/url_helper.php';

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to submit a review.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// ========== CSRF VALIDATION ==========
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $response['message'] = 'Invalid security token. Please refresh the page and try again.';
    echo json_encode($response);
    exit;
}
// ======================================

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get inputs
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    // Accept both 'content' (from form) and 'body' (legacy) field names
    $body = isset($_POST['content']) ? trim($_POST['content']) : (isset($_POST['body']) ? trim($_POST['body']) : '');
    $userId = $_SESSION['user_id'];
    
    // Validate inputs
    if ($productId <= 0) {
        throw new Exception('Invalid product.');
    }
    
    // Verify product exists
    $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        throw new Exception('Product not found.');
    }
    
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Please select a rating between 1 and 5 stars.');
    }
    
    if (empty($title)) {
        throw new Exception('Please enter a review title.');
    }
    
    if (empty($body)) {
        throw new Exception('Please enter review content.');
    }
    
    // ========== RATE LIMITING ==========
    // Check if user already reviewed this product in the last 24 hours
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM product_reviews 
        WHERE user_id = ? AND product_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$userId, $productId]);
    $rateCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rateCheck && $rateCheck['count'] > 0) {
        throw new Exception('You can only submit one review per product every 24 hours.');
    }
    // ====================================
    
    // Insert Review
    // Using 'body' column as discovered in DB check
    $stmt = $db->prepare("
        INSERT INTO product_reviews (product_id, user_id, rating, title, body, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $result = $stmt->execute([
        $productId,
        $userId,
        $rating,
        $title,
        $body
    ]);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Review submitted successfully! It will appear after moderation.';
    } else {
        throw new Exception('Failed to save review.');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
