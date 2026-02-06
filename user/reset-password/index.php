<?php
/**
 * Reset Password Page
 *
 * Public page for resetting password with token
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

$message = '';
$error = '';
$success = false;
$user = null;

$db = Services::db();
$userAuth = Services::userAuth();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('/user/forgot-password/');
}

$tokenData = $userAuth->validatePasswordResetToken($token);

if (!$tokenData) {
    $error = "This password reset link is invalid or has expired.";
}

if ($_POST && $tokenData) {
    try {
        CsrfMiddleware::validate();

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        if ($userAuth->resetPassword($token, $password)) {
            $success = true;
            $message = "Your password has been reset successfully. You can now log in with your new password.";
        } else {
            throw new Exception('Failed to reset password. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CannaBuddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= url('/') ?>" class="inline-block">
                <h1 class="text-4xl font-bold text-gray-900 tracking-tight">Canna<span class="text-green-600">Buddy</span></h1>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Set New Password</h2>
            <p class="text-gray-600 mb-8">Please enter your new password below.</p>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mt-1"></i>
                        <p class="ml-3 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mt-1"></i>
                        <p class="ml-3 text-sm text-green-700"><?= htmlspecialchars($message) ?></p>
                    </div>
                </div>
                <div class="text-center">
                    <a href="<?= url('/login/') ?>" class="inline-flex items-center text-green-600 font-medium hover:text-green-700">
                        Log In to Your Account <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            <?php elseif ($tokenData): ?>
                <form method="POST" class="space-y-6">
                    <?= csrf_field() ?>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm transition-all"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm transition-all"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                        Reset Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <a href="<?= url('/user/forgot-password/') ?>" class="inline-flex items-center text-green-600 font-medium hover:text-green-700">
                        Request a New Link <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <p class="text-center text-gray-500 text-xs mt-8">
            &copy; <?= date('Y') ?> CannaBuddy.shop. All rights reserved.
        </p>
    </div>
</body>
</html>
