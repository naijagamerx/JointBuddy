<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JointBuddy Admin Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#16a249",
                        "background-light": "#f6f8f7",
                        "background-dark": "#112117",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display min-h-screen flex flex-col transition-colors duration-300">
<!-- Top Navigation Bar (Logo / Title Only for Login Page) -->
<header class="flex items-center justify-between border-b border-solid border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-black/20 backdrop-blur-md px-6 py-4 lg:px-10">
<div class="flex items-center gap-4 text-[#0e1b13] dark:text-gray-100">
<div class="size-6 text-primary">
<svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path clip-rule="evenodd" d="M39.475 21.6262C40.358 21.4363 40.6863 21.5589 40.7581 21.5934C40.7876 21.655 40.8547 21.857 40.8082 22.3336C40.7408 23.0255 40.4502 24.0046 39.8572 25.2301C38.6799 27.6631 36.5085 30.6631 33.5858 33.5858C30.6631 36.5085 27.6632 38.6799 25.2301 39.8572C24.0046 40.4502 23.0255 40.7407 22.3336 40.8082C21.8571 40.8547 21.6551 40.7875 21.5934 40.7581C21.5589 40.6863 21.4363 40.358 21.6262 39.475C21.8562 38.4054 22.4689 36.9657 23.5038 35.2817C24.7575 33.2417 26.5497 30.9744 28.7621 28.762C30.9744 26.5497 33.2417 24.7574 35.2817 23.5037C36.9657 22.4689 38.4054 21.8562 39.475 21.6262ZM4.41189 29.2403L18.7597 43.5881C19.8813 44.7097 21.4027 44.9179 22.7217 44.7893C24.0585 44.659 25.5148 44.1631 26.9723 43.4579C29.9052 42.0387 33.2618 39.5667 36.4142 36.4142C39.5667 33.2618 42.0387 29.9052 43.4579 26.9723C44.1631 25.5148 44.659 24.0585 44.7893 22.7217C44.9179 21.4027 44.7097 19.8813 43.5881 18.7597L29.2403 4.41187C27.8527 3.02428 25.8765 3.02573 24.2861 3.36776C22.6081 3.72863 20.7334 4.58419 18.8396 5.74801C16.4978 7.18716 13.9881 9.18353 11.5858 11.5858C9.18354 13.988 7.18717 16.4978 5.74802 18.8396C4.58421 20.7334 3.72865 22.6081 3.36778 24.2861C3.02574 25.8765 3.02429 27.8527 4.41189 29.2403Z" fill="currentColor" fill-rule="evenodd"></path>
</svg>
</div>
<h2 class="text-lg font-bold leading-tight tracking-tight">JointBuddy Admin</h2>
</div>
<div class="flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">
<span class="size-2 bg-primary rounded-full animate-pulse"></span>
            Staff Portal
        </div>
</header>
<!-- Main Content: Login Form Card -->
<main class="flex-grow flex items-center justify-center p-6">
<div class="w-full max-w-[440px] bg-white dark:bg-background-dark border border-gray-200 dark:border-gray-800 shadow-xl rounded-xl overflow-hidden">
<div class="pt-8 pb-4 text-center">
<h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Welcome Back</h1>
<p class="text-primary dark:text-primary text-sm font-medium mt-1">Authorized personnel only</p>
</div>
<form class="px-8 pb-8 space-y-5" onsubmit="return false;">
<!-- Email Field -->
<div class="flex flex-col gap-2">
<label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Admin Email</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary text-[20px]">mail</span>
<input class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-gray-900 dark:text-white transition-all placeholder:text-gray-400" placeholder="e.g. admin@jointbuddy.com" required="" type="email"/>
</div>
</div>
<!-- Password Field -->
<div class="flex flex-col gap-2">
<div class="flex justify-between items-center">
<label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Secure Password</label>
<a class="text-xs text-primary hover:underline font-medium" href="#">Forgot Password?</a>
</div>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary text-[20px]">lock</span>
<input class="w-full pl-11 pr-12 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-gray-900 dark:text-white transition-all placeholder:text-gray-400" placeholder="••••••••" required="" type="password"/>
<button class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" type="button">
<span class="material-symbols-outlined text-[20px]">visibility</span>
</button>
</div>
</div>
<!-- 2FA Checkbox -->
<div class="flex items-start gap-3 py-2">
<input class="mt-1 size-4 rounded border-gray-300 dark:border-gray-700 text-primary focus:ring-primary" id="2fa" type="checkbox"/>
<label class="text-sm text-gray-600 dark:text-gray-400 leading-tight cursor-pointer" for="2fa">
                        Enable Two-Factor Authentication (2FA) prompt for this login session.
                    </label>
</div>
<!-- Action Button -->
<button class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-lg shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2" type="submit">
<span class="material-symbols-outlined text-[20px]">verified_user</span>
                    Secure Admin Login
                </button>
</form>
<!-- Bottom of Card Security Text -->
<div class="bg-gray-50 dark:bg-black/20 px-8 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-center gap-4">
<div class="flex items-center gap-1.5 opacity-60">
<span class="material-symbols-outlined text-[14px]">encrypted</span>
<span class="text-[11px] font-bold uppercase tracking-tighter text-gray-600 dark:text-gray-400">SSL v3 Encrypted</span>
</div>
<div class="flex items-center gap-1.5 opacity-60">
<span class="material-symbols-outlined text-[14px]">shield</span>
<span class="text-[11px] font-bold uppercase tracking-tighter text-gray-600 dark:text-gray-400">SOC2 Compliant</span>
</div>
</div>
</div>
</main>
<!-- Global Footer -->
<footer class="w-full px-6 py-6 border-t border-gray-200 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between text-gray-500 dark:text-gray-500 text-sm">
<div class="flex items-center gap-6 mb-4 md:mb-0">
<div class="flex items-center gap-2">
<span class="size-2 rounded-full bg-primary"></span>
<span>System Status: <span class="font-bold text-gray-700 dark:text-gray-300">Operational</span></span>
</div>
<div class="hidden md:block h-4 w-px bg-gray-300 dark:bg-gray-700"></div>
<p>© 2024 JointBuddy CannaBuddy. Internal Use Only.</p>
</div>
<div class="flex gap-4">
<a class="hover:text-primary transition-colors" href="#">Privacy Policy</a>
<a class="hover:text-primary transition-colors" href="#">Security Audit</a>
<button class="flex items-center gap-1 hover:text-primary transition-colors" onclick="document.documentElement.classList.toggle('dark')">
<span class="material-symbols-outlined text-[18px]">contrast</span>
</button>
</div>
</footer>
</body></html>