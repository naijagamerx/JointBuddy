<?php
// Route: /user - redirect to dashboard or login
require_once __DIR__ . '/../includes/url_helper.php';
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    // Redirect to dashboard if logged in
    redirect('/user/dashboard/');
} else {
    // Redirect to login if not logged in
    redirect('/user/login/');
}
exit;
?>
