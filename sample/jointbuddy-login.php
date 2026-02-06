<?php
/**
 * JointBuddy Admin Login Page
 * Retrieved from Stitch design - Split screen layout with secure gateway
 *
 * Design specifications:
 * - Split screen: 50/50 horizontal division
 * - Left panel: White background with login form
 * - Right panel: Dark green (#0A2F1A) with "Secure Gateway" message
 * - Accent color: #00A651 (bright green)
 * - Border radius: 8px
 */

// Include database and auth classes
require_once __DIR__ . '/../includes/database.php';

// Initialize AdminAuth
$adminAuth = new AdminAuth();

// Check if already logged in
if ($adminAuth->isLoggedIn()) {
    header('Location: ' . adminUrl('dashboard/'));
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Attempt login
        $result = $adminAuth->login($email, $password, $remember);

        if ($result['success']) {
            // Redirect to dashboard
            header('Location: ' . adminUrl('dashboard/'));
            exit;
        } else {
            $error = $result['message'] ?? 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Access - JointBuddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for elements not covered by Tailwind */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', Arial, sans-serif;
        }

        .login-container {
            display: flex;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        .left-panel {
            flex: 1;
            background: #FFFFFF;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .right-panel {
            flex: 1;
            background: #0A2F1A;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: #FFFFFF;
        }

        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            border-color: #00A651;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 166, 81, 0.1);
        }

        .primary-button {
            width: 100%;
            padding: 14px 24px;
            background: #00A651;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .primary-button:hover {
            background: #008F45;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 166, 81, 0.3);
        }

        .secondary-button {
            width: 100%;
            padding: 12px 20px;
            background: #F5F5F5;
            color: #333333;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .secondary-button:hover {
            background: #E8E8E8;
            border-color: #CCCCCC;
        }

        .link-green {
            color: #00A651;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .link-green:hover {
            text-decoration: underline;
            color: #008F45;
        }

        .lock-icon-circle {
            width: 80px;
            height: 80px;
            background: #FFFFFF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        /* Checkbox styling */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #00A651;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .right-panel {
                display: none;
            }

            .left-panel {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body class="m-0 p-0">
    <div class="login-container">
        <!-- Left Panel - Login Form -->
        <div class="left-panel">
            <div class="max-w-md w-full mx-auto">
                <!-- Logo -->
                <div class="flex items-center mb-6">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: #00A651;">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-semibold" style="color: #00A651;">JOINTBUDDY</span>
                        <div class="text-xs text-gray-500">ADMIN</div>
                    </div>
                </div>

                <!-- Badge -->
                <div class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white mb-6" style="background: #FF4D4F;">
                    Admin Portal
                </div>

                <!-- Title -->
                <h1 class="text-2xl font-semibold text-gray-800 mb-2">Admin Portal Access</h1>
                <p class="text-sm text-gray-500 mb-8">
                    Secure Administrator Access. Please verify your credentials to continue to the dashboard.
                </p>

                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Admin Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="input-field"
                            placeholder="admin@jointbuddy.com"
                            required
                            autofocus
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="input-field"
                            placeholder="••••••••"
                            required
                        >
                    </div>

                    <!-- Form Options -->
                    <div class="flex items-center justify-between">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="ml-2 text-sm text-gray-500">Keep me logged in</span>
                        </label>
                        <a href="<?php echo adminUrl('forgot-password/'); ?>" class="link-green text-sm">Forgot password?</a>
                    </div>

                    <!-- Primary Button -->
                    <button type="submit" class="primary-button">
                        Authorize Access
                    </button>

                    <!-- Secondary Button (SSO) -->
                    <button type="button" class="secondary-button">
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Sign in with SSO
                        </span>
                    </button>
                </form>

                <!-- Return Link -->
                <div class="mt-8">
                    <a href="<?php echo url('/'); ?>" class="link-green text-sm flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Return to customer site
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Panel - Secure Gateway -->
        <div class="right-panel">
            <div class="text-center">
                <!-- Lock Icon -->
                <div class="lock-icon-circle">
                    <svg class="w-10 h-10" style="color: #00A651;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>

                <!-- Title -->
                <h2 class="text-3xl font-bold text-white mb-4">SECURE GATEWAY</h2>

                <!-- Subtitle -->
                <p class="text-sm text-gray-300 max-w-xs mx-auto leading-relaxed">
                    AUTHORIZED PERSONNEL ACCESS ONLY. ALL ACTIVITIES ARE LOGGED AND MONITORED.
                </p>

                <!-- Security Badges -->
                <div class="mt-12 flex flex-wrap justify-center gap-4">
                    <div class="flex items-center text-xs text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        SSL Encrypted
                    </div>
                    <div class="flex items-center text-xs text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        24/7 Monitoring
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
