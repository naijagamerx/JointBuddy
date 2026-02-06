<?php
/**
 * Admin Authentication Check
 * Include this file at the top of admin pages to verify admin is logged in
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($adminAuth)) {
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $adminAuth = new AdminAuth($db);
}

if (!$adminAuth || !$adminAuth->isLoggedIn()) {
    require_once __DIR__ . '/url_helper.php';
    header('Location: ' . adminUrl('/login/'));
    exit;
}
?>
