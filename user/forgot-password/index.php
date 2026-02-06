<?php
/**
 * Forgot Password Page
 *
 * Public page for requesting password reset
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

$message = '';
$error = '';
$success = false;

if ($_POST) {
    try {
        CsrfMiddleware::validate();

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            throw new Exception('Email address is required');
        }

        $db = Services::db();
        $userAuth = Services::userAuth();
        
        $token = $userAuth->createPasswordResetToken($email);

        if ($token) {
            // Get user info for email
            $stmt = $db->prepare("SELECT first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            require_once INCLUDES_PATH . '/email_service.php';
            $emailService = new EmailService($db);
            $resetUrl = url('/user/reset-password/?token=' . $token);
            
            $emailService->send($email, $user['first_name'], 'Reset Your Password', '', '', 'password_reset', [
                'first_name' => $user['first_name'],
                'reset_url' => $resetUrl
            ]);
        }
        
        // Always show success message for security
        $success = true;
        $message = "If an account exists for $email, a reset link has been sent. Please check your inbox and spam folder.";
        
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
    <title>Forgot Password - CannaBuddy</title>
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
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Forgot Password?</h2>
            <p class="text-gray-600 mb-8">No worries, we'll send you reset instructions.</p>

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
                        <i class="fas fa-arrow-left mr-2"></i> Back to Login
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <?= csrf_field() ?>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" required 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm transition-all"
                                placeholder="Enter your email">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                        Reset Password
                    </button>

                    <div class="text-center mt-6">
                        <a href="<?= url('/login/') ?>" class="text-sm font-medium text-gray-500 hover:text-gray-700 flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <p class="text-center text-gray-500 text-xs mt-8">
            &copy; <?= date('Y') ?> CannaBuddy.shop. All rights reserved.
        </p>
    </div>
</body>
</html>
