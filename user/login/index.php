<?php
/**
 * User Login Page
 *
 * Public authentication page for user login
 */

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

$error500 = false;
$userAuth = null;
$db = null;

try {
    $db = Services::db();
    $userAuth = Services::userAuth();
} catch (Exception $e) {
    error_log("Database connection failed in login: " . $e->getMessage());
    $error500 = true;
}

// Dynamic Site Name
$storeName = 'JointBuddy';
if (!$error500) {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['setting_value'])) {
            $storeName = $row['setting_value'];
        }
    } catch (Exception $e) {}
}

$errorMessage = '';
$successMessage = '';

// Check for success messages
if (isset($_SESSION['registration_success'])) {
    $successMessage = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
} elseif (isset($_GET['message']) && $_GET['message'] === 'registered') {
    $successMessage = 'Registration successful! Please login to your new account.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error500 && $userAuth) {
    try {
        CsrfMiddleware::validate();
    } catch (Exception $csrfError) {
        $errorMessage = 'Security check failed. Please try again.';
        error_log('User login CSRF validation failed from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }

    if (!$errorMessage) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errorMessage = 'Please provide both email and password.';
        } else {
            $loginResult = $userAuth->login($email, $password);
            if ($loginResult['success']) {
                $redirect = $_SESSION['login_redirect'] ?? url('/user/dashboard/');
                unset($_SESSION['login_redirect']);
                header("Location: " . $redirect);
                exit;
            } else {
                    $errorMessage = $loginResult['message'];
            }
        }
    }
}

$pageTitle = "Login | " . $storeName;
sendSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <?php
    // Get Favicon from database
    $faviconUrl = '';
    if (isset($db)) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'favicon_url'");
            $stmt->execute();
            $favRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($favRow && !empty($favRow['setting_value'])) {
                $faviconUrl = $favRow['setting_value'];
            }
        } catch (Exception $e) {
            // Fail silently
        }
    }
    if (!empty($faviconUrl)): ?>
        <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <?php endif; ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#16a34a", 
                        accent: "#2D5A27", 
                        "neutral-gray": "#F8F9FA", 
                        "background-light": "#f6f8f7", 
                        "background-dark": "#112117"
                    }, 
                    fontFamily: {
                        sans: ["Inter", "sans-serif"], 
                        display: "Inter"
                    }, 
                    borderRadius: {
                        DEFAULT: "1rem", 
                        lg: "2rem", 
                        xl: "3rem", 
                        full: "9999px"
                    }
                }
            }
        };
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply antialiased text-slate-900 bg-white;
            }
        }
        .input-field {
            @apply w-full px-4 py-2.5 bg-transparent border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all duration-200 placeholder:text-gray-400 text-sm;
        }
        .btn-social {
            @apply flex items-center justify-center gap-3 w-full py-2.5 border border-gray-200 rounded-lg font-medium text-gray-600 hover:bg-gray-50 transition-all duration-200 text-sm;
        }
        .split-bg-gradient {
            background: radial-gradient(circle at 50% 50%, rgba(22, 163, 74, 0.05) 0%, transparent 100%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">
<main class="w-full md:w-1/2 flex flex-col min-h-screen p-8 md:p-12 lg:p-16 relative z-10 bg-white">
    <header class="mb-10">
        <div class="flex items-center gap-3">
            <div class="size-10 text-primary">
                <svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path clip-rule="evenodd" d="M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z" fill="currentColor" fill-rule="evenodd"></path>
                </svg>
            </div>
            <a href="<?= url('/') ?>" class="text-gray-900 text-xl font-bold tracking-tight"><?= htmlspecialchars($storeName) ?></a>
        </div>
    </header>

    <div class="max-w-md w-full mx-auto my-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-gray-900 mb-2">Welcome Back</h1>
            <p class="text-gray-500">Log in to manage your collection and premium orders.</p>
        </div>

        <?php if ($successMessage): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                <span class="material-symbols-outlined text-green-500">check_circle</span>
                <span class="text-sm"><?= htmlspecialchars($successMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                <span class="material-symbols-outlined text-red-500">error</span>
                <span class="text-sm"><?= htmlspecialchars($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error500): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg mb-6">
                System is temporarily unavailable. Please try again later.
            </div>
        <?php else: ?>
            <form action="<?= url('/user/login/') ?>" method="POST" class="space-y-5">
                <?= csrf_field() ?>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Email address</label>
                    <input class="input-field" name="email" placeholder="name@company.com" required type="email" value="<?= htmlspecialchars($email ?? '') ?>"/>
                </div>
                <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <a class="text-xs text-primary font-semibold hover:underline" href="<?= url('/user/forgot-password/') ?>">Forgot?</a>
                    </div>
                    <div class="relative">
                        <input class="input-field" id="password-field" name="password" placeholder="••••••••" required type="password"/>
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" type="button" onclick="togglePassword()">
                            <span class="material-symbols-outlined text-[18px]" id="password-toggle-icon">visibility</span>
                        </button>
                    </div>
                </div>
                <div class="flex items-center space-x-2 py-1">
                    <input class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4" id="remember" type="checkbox"/>
                    <label class="text-sm text-gray-600" for="remember">Keep me signed in</label>
                </div>
                <button class="w-full bg-primary text-white font-semibold py-3 rounded-lg shadow-sm hover:bg-green-700 transition-all duration-200 mt-2 flex items-center justify-center gap-2" type="submit">
                    <span class="material-symbols-outlined text-[20px]">login</span>
                    Sign in
                </button>
            </form>


        <?php endif; ?>

        <div class="mt-10 text-center">
            <p class="text-gray-500 text-sm">
                Don't have an account? 
                <a class="text-primary font-bold hover:underline ml-1" href="<?= url('/register/') ?>">Join now</a>
            </p>
        </div>
    </div>

    <footer class="mt-12">
        <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?> Lifestyle. All rights reserved.</p>
    </footer>
</main>

<aside class="hidden md:block w-1/2 relative overflow-hidden bg-neutral-50">
    <div class="absolute inset-0 split-bg-gradient"></div>
    <img alt="Premium Lifestyle" class="absolute inset-0 w-full h-full object-cover opacity-80" src="<?= url('assets/images/heroes/hero_1_1765715477.png') ?>"/>
    <div class="absolute bottom-16 left-16 right-16 p-8 backdrop-blur-md bg-white/10 border border-white/20 rounded-2xl text-white">
        <div class="flex items-center gap-2 mb-4">
            <div class="flex text-yellow-400">
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
            </div>
            <span class="text-xs font-bold uppercase tracking-wider opacity-70">Approved Client Review</span>
        </div>
        <p class="text-xl font-medium leading-relaxed mb-6 italic">"The attention to detail and curated selection at <?= htmlspecialchars($storeName) ?> is truly world-class. A premium experience from browsing to unboxing."</p>
        <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-full bg-white/20 border border-white/30 flex items-center justify-center">
                <i class="fa-solid fa-user text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm">James Wilson</p>
                <p class="text-xs text-white/60">Premium Member</p>
            </div>
        </div>
    </div>
</aside>

<script>
    function togglePassword() {
        const field = document.getElementById("password-field");
        const icon = document.getElementById("password-toggle-icon");
        if (field.type === "password") {
            field.type = "text";
            icon.textContent = "visibility_off";
        } else {
            field.type = "password";
            icon.textContent = "visibility";
        }
    }
</script>
</body>
</html>
