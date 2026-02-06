<?php
// Include error handler
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/url_helper.php';

function renderRegistrationPage() {
    global $db;
    $success = $_SESSION['registration_success'] ?? null;
    $error = $_SESSION['registration_error'] ?? null;

    if ($success) unset($_SESSION['registration_success']);
    if ($error) unset($_SESSION['registration_error']);

    $alerts = '';

    if ($success) {
        $alerts = '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-green-500">check_circle</span>
            <span class="text-sm">' . htmlspecialchars($success) . '</span>
        </div>';
    }

    if ($error) {
        $alerts = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-red-500">error</span>
            <span class="text-sm">' . htmlspecialchars($error) . '</span>
        </div>';
    }

    // Dynamic Site Name from DB
    $storeName = 'JointBuddy';
    if (isset($db)) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_name'");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['setting_value'])) {
                $storeName = $row['setting_value'];
            }
        } catch (Exception $e) {}
    }

    $bgImage = url('assets/images/heroes/hero_1_1765715477.png');
    $homeUrl = url('/');
    $loginUrl = url('/user/login/');
    $csrfField = csrf_field();
    $year = date('Y');

    return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'/>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'/>
    <title>Create Account | {$storeName}</title>
    <script src='https://cdn.tailwindcss.com?plugins=forms,container-queries'></script>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap' rel='stylesheet'/>
    <link href='https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap' rel='stylesheet'/>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css' />
    <script id='tailwind-config'>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a', 
                        accent: '#2D5A27', 
                        'neutral-gray': '#F8F9FA', 
                        'background-light': '#f6f8f7', 
                        'background-dark': '#112117'
                    }, 
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'], 
                        display: 'Inter'
                    }, 
                    borderRadius: {
                        DEFAULT: '1rem', 
                        lg: '2rem', 
                        xl: '3rem', 
                        full: '9999px'
                    }
                }
            }
        };
    </script>
    <style type='text/tailwindcss'>
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
<body class='min-h-screen flex flex-col md:flex-row'>
<main class='w-full md:w-1/2 flex flex-col min-h-screen p-8 md:p-12 lg:p-16 relative z-10 bg-white'>
    <header class='mb-10'>
        <div class='flex items-center gap-3'>
            <div class='size-10 text-primary'>
                <svg fill='none' viewbox='0 0 48 48' xmlns='http://www.w3.org/2000/svg'>
                    <path clip-rule='evenodd' d='M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z' fill='currentColor' fill-rule='evenodd'></path>
                </svg>
            </div>
            <a href='{$homeUrl}' class='text-gray-900 text-xl font-bold tracking-tight'>{$storeName}</a>
        </div>
    </header>

    <div class='max-w-md w-full mx-auto my-auto'>
        <div class='mb-8'>
            <h1 class='text-3xl font-semibold text-gray-900 mb-2'>Create an account</h1>
            <p class='text-gray-500'>Join our community of premium enthusiasts.</p>
        </div>

        {$alerts}

        <form action='{$homeUrl}register/' method='POST' class='space-y-4'>
            {$csrfField}
            <div class='grid grid-cols-2 gap-4'>
                <div class='space-y-1.5'>
                    <label class='block text-sm font-medium text-gray-700'>First Name</label>
                    <input class='input-field' name='first_name' placeholder='John' required type='text'/>
                </div>
                <div class='space-y-1.5'>
                    <label class='block text-sm font-medium text-gray-700'>Last Name</label>
                    <input class='input-field' name='last_name' placeholder='Doe' required type='text'/>
                </div>
            </div>
            <div class='space-y-1.5'>
                <label class='block text-sm font-medium text-gray-700'>Email address</label>
                <input class='input-field' name='email' placeholder='name@company.com' required type='email'/>
            </div>
            <div class='space-y-1.5'>
                <label class='block text-sm font-medium text-gray-700'>Phone <span class='text-gray-400 font-normal'>(Optional)</span></label>
                <input class='input-field' name='phone' placeholder='+27 XX XXX XXXX' type='tel'/>
            </div>
            <div class='space-y-1.5'>
                <label class='block text-sm font-medium text-gray-700'>Password</label>
                <div class='relative'>
                    <input class='input-field' id='password-field' name='password' placeholder='••••••••' required type='password'/>
                    <button class='absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600' type='button' onclick='togglePassword()'>
                        <span class='material-symbols-outlined text-[18px]' id='password-toggle-icon'>visibility</span>
                    </button>
                </div>
                <p class='text-[11px] text-gray-400'>Must be at least 8 characters long.</p>
            </div>
            <div class='flex items-start space-x-3 py-1'>
                <div class='flex items-center h-5'>
                    <input class='rounded border-gray-300 text-primary focus:ring-primary h-4 w-4' id='age-verification' required type='checkbox'/>
                </div>
                <label class='text-sm text-gray-600 leading-tight' for='age-verification'>
                    I verify that I am 21 years of age or older and agree to the <a class='text-primary font-semibold hover:underline' href='{$homeUrl}terms'>Terms of Service</a>.
                </label>
            </div>
            <button class='w-full bg-primary text-white font-semibold py-3 rounded-lg shadow-sm hover:bg-green-700 transition-all duration-200 mt-2 flex items-center justify-center gap-2' type='submit'>
                <span class='material-symbols-outlined text-[20px]'>person_add</span>
                Create Account
            </button>
        </form>



        <div class='mt-8 text-center'>
            <p class='text-gray-500 text-sm'>
                Already have an account? 
                <a class='text-primary font-bold hover:underline ml-1' href='{$loginUrl}'>Sign in</a>
            </p>
        </div>
    </div>

    <footer class='mt-8'>
        <p class='text-xs text-gray-400'>&copy; {$year} {$storeName} Premium Accessories. All rights reserved.</p>
    </footer>
</main>

<aside class='hidden md:block w-1/2 relative overflow-hidden bg-primary'>
    <div class='absolute inset-0 split-bg-gradient z-10 opacity-60'></div>
    <img alt='Premium Lifestyle Accessories' class='absolute inset-0 w-full h-full object-cover' src='{$bgImage}'/>
    <div class='absolute bottom-16 left-16 right-16 p-8 backdrop-blur-xl bg-white/10 border border-white/20 rounded-2xl text-white z-20 shadow-2xl'>
        <div class='flex items-center gap-1 mb-4 text-yellow-400'>
             <i class='fa-solid fa-star'></i>
             <i class='fa-solid fa-star'></i>
             <i class='fa-solid fa-star'></i>
             <i class='fa-solid fa-star'></i>
             <i class='fa-solid fa-star'></i>
        </div>
        <p class='text-lg font-medium leading-relaxed mb-6 italic'>\"The quality of accessories from {$storeName} is unmatched. Their attention to detail and clean aesthetic makes every experience premium. A true game changer for collectors.\"</p>
        <div class='flex items-center gap-3'>
             <div class='w-12 h-12 rounded-full bg-primary/20 border-2 border-white/30 flex items-center justify-center overflow-hidden'>
                <i class='fa-solid fa-user text-xl'></i>
            </div>
            <div>
                <p class='font-bold text-sm'>Marcus Chen</p>
                <p class='text-xs text-white/70'>Approved Customer Review</p>
            </div>
        </div>
    </div>
</aside>

<script>
    function togglePassword() {
        const field = document.getElementById('password-field');
        const icon = document.getElementById('password-toggle-icon');
        if (field.type === 'password') {
            field.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            field.type = 'password';
            icon.textContent = 'visibility';
        }
    }
</script>
</body>
</html>";
}
?>
