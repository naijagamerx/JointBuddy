<?php
/**
 * User Logout
 *
 * Logs out the current user and redirects to login
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Logout the user using the service
$userAuth = Services::userAuth();
$userAuth->logout();

// Redirect to login page with success message
redirect('/user/login/?message=logged_out');
