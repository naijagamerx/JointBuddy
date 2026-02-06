<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Send security headers
sendLoginSecurityHeaders();

// Get services
$adminAuth = Services::adminAuth();
$db = Services::db();

// Check for various error sources
$error = sessionGetFlash('admin_login_error') ?: sessionGetFlash('error') ?: '';

// Fetch dynamic site name
$site_name = 'Store';
if ($db) {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name' LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['setting_value'])) {
            $site_name = $result['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Error fetching site name: " . $e->getMessage());
    }
}

// Handle logout GET request (from sidebar logout link)
if (isset($_GET['logout'])) {
    error_log("LOGOUT REQUEST received - Admin logout requested");

    // Clear session via service
    $result = $adminAuth->logout();
    error_log("Logout result: " . ($result ? 'SUCCESS' : 'FAILED'));

    header('Location: ' . adminUrl('/login/?logged_out=1'));
    exit;
}

// If already logged in, redirect to dashboard
if ($adminAuth && $adminAuth->isLoggedIn() && $adminAuth->verifySessionFingerprint()) {
    error_log("DEBUG: User already logged in, redirecting to dashboard");
    redirect('/admin/');
}

$debugInfo = '';
if (isset($_GET['debug']) || isset($_GET['show_debug'])) {
    $debugInfo = '<div style="background:#1a1a1a;color:#00ff00;padding:15px;font-family:monospace;font-size:12px;margin:10px 0;white-space:pre-wrap;max-height:400px;overflow:auto;border:2px solid #00ff00;">';
    $debugInfo .= "DEBUG SESSION INFO:\n";
    $debugInfo .= "===================\n\n";
    $debugInfo .= "Session ID: " . session_id() . "\n\n";
    $debugInfo .= "Session Data:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            $debugInfo .= "  $key: " . print_r($value, true) . "\n";
        } else {
            $debugInfo .= "  $key: " . (strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) . "\n";
        }
    }

    if ($adminAuth) {
        $debugInfo .= "\nisLoggedIn(): " . ($adminAuth->isLoggedIn() ? 'true' : 'false') . "\n";
        $debugInfo .= "admin_logged_in session: " . ($_SESSION['admin_logged_in'] ?? 'NOT SET') . "\n";
        $debugInfo .= "admin_id session: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "\n";
        $debugInfo .= "admin_fingerprint: " . ($_SESSION['admin_fingerprint'] ?? 'NOT SET') . "\n";
    }

    $debugInfo .= "\nServer Info:\n";
    $debugInfo .= "  REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    $debugInfo .= "  HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";

    $current_fp = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    $stored_fp = $_SESSION['admin_fingerprint'] ?? 'NOT SET';
    $debugInfo .= "\nFingerprint Check:\n";
    $debugInfo .= "  Current: $current_fp\n";
    $debugInfo .= "  Stored: $stored_fp\n";
    $debugInfo .= "  Match: " . ($current_fp === $stored_fp ? 'YES' : 'NO') . "\n";

    $debugInfo .= "\n</div>";
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password']) && $adminAuth) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security check failed. Please try again.';
        error_log('Admin login CSRF validation failed');
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $result = $adminAuth->login($_POST['username'], $_POST['password'], $ip_address);

        if ($result['success']) {
            csrf_regenerate(); // Regenerate CSRF token after successful login
            redirect('/admin/');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html class="" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo htmlspecialchars($site_name); ?> | Admin Portal Access</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <?php
    if (isset($db) && $db) {
        try {
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'favicon_url'");
            $favRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($favRow && !empty($favRow['setting_value'])) {
                echo '<link rel="icon" href="' . htmlspecialchars($favRow['setting_value']) . '">';
            }
        } catch (Exception $e) {}
    }
    ?>
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
                        display: ["Inter", "sans-serif"]
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
            @apply w-full px-4 py-3 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all duration-200 placeholder:text-gray-400 text-sm;
        }
        .btn-social {
            @apply flex items-center justify-center gap-3 w-full py-3 border border-gray-200 rounded-lg font-medium text-gray-600 hover:bg-gray-50 transition-all duration-200 text-sm;
        }
        .admin-glow {
            background: radial-gradient(circle at 50% 50%, rgba(22, 163, 74, 0.15) 0%, transparent 70%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">
    <!-- Left: Login Form -->
    <main class="w-full md:w-1/2 flex flex-col min-h-screen p-8 md:p-16 lg:p-24 relative z-10 bg-white">
        <header class="mb-auto">
            <div class="flex items-center gap-2.5">
                <div class="size-7 text-primary">
                    <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" d="M39.475 21.6262C40.358 21.4363 40.6863 21.5589 40.7581 21.5934C40.7876 21.655 40.8547 21.857 40.8082 22.3336C40.7408 23.0255 40.4502 24.0046 39.8572 25.2301C38.6799 27.6631 36.5085 30.6631 33.5858 33.5858C30.6631 36.5085 27.6632 38.6799 25.2301 39.8572C24.0046 40.4502 23.0255 40.7407 22.3336 40.8082C21.8571 40.8547 21.6551 40.7875 21.5934 40.7581C21.5589 40.6863 21.4363 40.358 21.6262 39.475C21.8562 38.4054 22.4689 36.9657 23.5038 35.2817C24.7575 33.2417 26.5497 30.9744 28.7621 28.762C30.9744 26.5497 33.2417 24.7574 35.2817 23.5037C36.9657 22.4689 38.4054 21.8562 39.475 21.6262ZM4.41189 29.2403L18.7597 43.5881C19.8813 44.7097 21.4027 44.9179 22.7217 44.7893C24.0585 44.659 25.5148 44.1631 26.9723 43.4579C29.9052 42.0387 33.2618 39.5667 36.4142 36.4142C39.5667 33.2618 42.0387 29.9052 43.4579 26.9723C44.1631 25.5148 44.659 24.0585 44.7893 22.7217C44.9179 21.4027 44.7097 19.8813 43.5881 18.7597L29.2403 4.41187C27.8527 3.02428 25.8765 3.02573 24.2861 3.36776C22.6081 3.72863 20.7334 4.58419 18.8396 5.74801C16.4978 7.18716 13.9881 9.18353 11.5858 11.5858C9.18354 13.988 7.18717 16.4978 5.74802 18.8396C4.58421 20.7334 3.72865 22.6081 3.36778 24.2861C3.02574 25.8765 3.02429 27.8527 4.41189 29.2403Z" fill="currentColor" fill-rule="evenodd"></path>
                    </svg>
                </div>
                <span class="text-gray-900 text-lg font-bold tracking-tight uppercase"><?php echo htmlspecialchars($site_name); ?> <span class="text-xs font-medium text-gray-400 tracking-widest ml-1">Admin</span></span>
            </div>
        </header>

        <div class="max-w-md w-full mx-auto my-12">
            <div class="mb-10">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-100 mb-4">
                    <span class="material-symbols-outlined text-[14px]">shield_person</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Staff Only</span>
                </div>
                <h1 class="text-3xl font-semibold text-gray-900 mb-2">Admin Portal Access</h1>
                <p class="text-gray-500 text-sm">Secure Administrator Access. Please verify your credentials to continue to the dashboard.</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-red-500">error</span>
                        <p class="text-sm text-red-800 font-medium"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($debugInfo): ?>
                <?php echo $debugInfo; ?>
            <?php endif; ?>

            <?php if (isset($_GET['logged_out'])): ?>
                <div id="logout-toast" class="mb-4 animate-in fade-in slide-in-from-top-4 duration-300">
                    <div class="bg-green-600 text-white rounded-lg p-4 flex items-center gap-3">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span class="text-sm font-bold">Logged out successfully</span>
                    </div>
                </div>
                <script>
                    setTimeout(() => {
                        const toast = document.getElementById('logout-toast');
                        if (toast) {
                            toast.classList.add('fade-out', 'translate-y-[-1rem]');
                            setTimeout(() => toast.remove(), 300);
                        }
                    }, 4000);
                </script>
            <?php endif; ?>

            <form action="" class="space-y-4" method="POST">
                <?php echo csrf_field(); ?>

                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Admin Email</label>
                    <input class="input-field" id="username" name="username" placeholder="admin@<?php echo strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $site_name)); ?>.com" required="" type="email"/>
                </div>

                <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                    </div>
                    <div class="relative">
                        <input class="input-field" id="password" name="password" placeholder="••••••••" required="" type="password"/>
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" id="togglePassword" type="button">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between py-1">
                    <div class="flex items-center space-x-2">
                        <input class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4" id="remember" name="remember" type="checkbox"/>
                        <label class="text-sm text-gray-600" for="remember">Keep me logged in</label>
                    </div>
                    <a class="text-xs font-semibold text-primary hover:underline" href="#">Forgot admin credentials?</a>
                </div>

                <button class="w-full bg-primary text-white font-semibold py-3.5 rounded-lg shadow-sm hover:bg-[#132c11] transition-all duration-200 mt-2 flex items-center justify-center gap-2" type="submit">
                    <span class="material-symbols-outlined text-[20px]">login</span>
                    Authorize Access
                </button>
            </form>

            <div class="relative flex items-center my-8">
                <div class="flex-grow border-t border-gray-100"></div>
                <span class="flex-shrink mx-4 text-gray-400 text-[10px] font-bold uppercase tracking-widest">or</span>
                <div class="flex-grow border-t border-gray-100"></div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <button class="btn-social" type="button">
                    <span class="material-symbols-outlined text-[20px]">passkey</span>
                    <span>Sign in with SSO</span>
                </button>
            </div>

            <div class="mt-10 text-center">
                <p class="text-gray-500 text-sm">
                    Need to shop?
                    <a class="text-primary font-bold hover:underline ml-1 inline-flex items-center gap-1" href="<?php echo url('/'); ?>">
                        Return to customer site
                        <span class="material-symbols-outlined text-[16px]">arrow_right_alt</span>
                    </a>
                </p>
            </div>
        </div>

        <footer class="mt-auto pt-8">
            <p class="text-[10px] text-gray-400 font-medium tracking-wide">SYSTEM ID: JB-ADM-001 | © <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?> MANAGEMENT SYSTEM</p>
        </footer>
    </main>

    <!-- Right: Decorative Sidebar -->
    <aside class="hidden md:block w-1/2 relative overflow-hidden bg-background-dark">
        <div class="absolute inset-0 admin-glow opacity-50"></div>
        <img alt="Premium Cannabis Background" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" src="<?php echo url('assets/images/admin_login_bg.png'); ?>"/>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-12">
            <div class="mb-6 rounded-full bg-white/5 border border-white/10 p-6 backdrop-blur-sm">
                <span class="material-symbols-outlined text-white text-[64px] font-thin">lock_open</span>
            </div>
            <h2 class="text-white text-2xl font-light tracking-widest uppercase mb-4">Secure Gateway</h2>
            <div class="w-12 h-0.5 bg-primary mb-8"></div>
            <p class="text-white/60 text-sm max-w-xs leading-relaxed uppercase tracking-widest font-medium">
                Authorized Personnel Access Only. All activities are logged and monitored.
            </p>
        </div>
        <div class="absolute top-12 right-12 text-white/10">
            <span class="material-symbols-outlined text-[120px]">admin_panel_settings</span>
        </div>
    </aside>

    <script>
        // Toggle Password Visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('span').textContent = type === 'password' ? 'visibility' : 'visibility_off';
            });
        }
    </script>
</body>
</html>
