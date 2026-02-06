<?php
// Newsletter Subscription Handler
session_start();

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/url_helper.php';

$message = '';
$error = '';

// Handle POST request
if ($_POST && isset($_POST['email'])) {
    try {
        $email = trim($_POST['email']);

        if (empty($email)) {
            throw new Exception('Email address is required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }

        // Connect to database
        $database = new Database();
        $db = $database->getConnection();

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            // Email already subscribed - redirect with info message
            $_SESSION['newsletter_info'] = 'This email is already subscribed to our newsletter.';
            redirect(url('/'));
        }

        // Add new subscriber
        $stmt = $db->prepare("INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())");
        $result = $stmt->execute([$email]);

        if ($result) {
            $_SESSION['newsletter_success'] = 'Thank you for subscribing! You will receive our latest updates.';
            redirect(url('/'));
        } else {
            throw new Exception('Failed to subscribe. Please try again.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $_SESSION['newsletter_error'] = $error;
        error_log("Newsletter subscription error: " . $e->getMessage());
        redirect(url('/'));
    }
} else {
    // Invalid request - redirect to homepage
    redirect(url('/'));
}
?>
