<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JointBuddy Admin Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style type="text/tailwindcss">:root {
    --primary: #16a249;
    --bg-gradient-start: #0a110d;
    --bg-gradient-end: #1a2e21
    }
.bg-grid-pattern {
    background-image: linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 40px 40px
    }
.bg-topographic {
    background-image: url(https://lh3.googleusercontent.com/aida-public/AB6AXuDw34ujXL1TiSszW86uKUvK2svZsoDxNf5hOgdqAWOypqf-UZNao5p-wZzkBG4rDUVSioeDSKUUyFC5249yHAHZYaUxp1fEcLgN-FHM3s-mSjxq6Sk-dsT6XaUHWGzMibmjLVhypwAMBoKI89_GDywsWmLB8dVzS2s3afl3Ey6rgSmEuqhj_WlogwSK21A_DwqOhV1K_7MaG8EWglDlcuplcwxeKvvN0LJDJ5H2T9Tvu47c3NxhDecJhVcB4P2uEKAbM0NdxgbqrlA)
    }
.glass-leaf {
    filter: blur(2px);
    opacity: 0.15;
    transform: rotate(-15deg)
    }</style>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#16a249",
                        "background-light": "#f6f8f7",
                        "background-dark": "#0a110d",
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
<body class="bg-background-dark font-display min-h-screen flex flex-col transition-colors duration-300 relative overflow-hidden">
<div class="fixed inset-0 z-0 bg-gradient-to-br from-[#0a110d] via-[#112117] to-[#1a2e21]"></div>
<div class="fixed inset-0 z-0 bg-grid-pattern opacity-40"></div>
<div class="fixed inset-0 z-0 bg-topographic opacity-50"></div>
<div class="fixed -right-20 -bottom-20 z-0 glass-leaf pointer-events-none">
<svg fill="none" height="800" viewBox="0 0 100 100" width="800" xmlns="http://www.w3.org/2000/svg">
<path d="M50 95C50 95 45 75 45 60C45 45 50 35 50 35C50 35 55 45 55 60C55 75 50 95 50 95Z" fill="url(#leaf-grad)"></path>
<path d="M50 65C50 65 30 75 15 70C0 65 5 45 5 45C5 45 20 50 35 55C50 60 50 65 50 65Z" fill="url(#leaf-grad)"></path>
<path d="M50 65C50 65 70 75 85 70C100 65 95 45 95 45C95 45 80 50 65 55C50 60 50 65 50 65Z" fill="url(#leaf-grad)"></path>
<path d="M50 55C50 55 25 50 10 35C-5 20 10 10 10 10C10 10 20 20 35 35C50 50 50 55 50 55Z" fill="url(#leaf-grad)"></path>
<path d="M50 55C50 55 75 50 90 35C105 20 90 10 90 10C90 10 80 20 65 35C50 50 50 55 50 55Z" fill="url(#leaf-grad)"></path>
<defs>
<linearGradient gradientUnits="userSpaceOnUse" id="leaf-grad" x1="50" x2="50" y1="10" y2="95">
<stop stop-color="white" stop-opacity="0.8"></stop>
<stop offset="1" stop-color="#16a249" stop-opacity="0.2"></stop>
</linearGradient>
</defs>
</svg>
</div>
<header class="relative z-10 flex items-center justify-between border-b border-white/5 bg-white/5 backdrop-blur-xl px-6 py-4 lg:px-10">
<div class="flex items-center gap-4 text-white">
<div class="size-6 text-primary">
<svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path clip-rule="evenodd" d="M39.475 21.6262C40.358 21.4363 40.6863 21.5589 40.7581 21.5934C40.7876 21.655 40.8547 21.857 40.8082 22.3336C40.7408 23.0255 40.4502 24.0046 39.8572 25.2301C38.6799 27.6631 36.5085 30.6631 33.5858 33.5858C30.6631 36.5085 27.6632 38.6799 25.2301 39.8572C24.0046 40.4502 23.0255 40.7407 22.3336 40.8082C21.8571 40.8547 21.6551 40.7875 21.5934 40.7581C21.5589 40.6863 21.4363 40.358 21.6262 39.475C21.8562 38.4054 22.4689 36.9657 23.5038 35.2817C24.7575 33.2417 26.5497 30.9744 28.7621 28.762C30.9744 26.5497 33.2417 24.7574 35.2817 23.5037C36.9657 22.4689 38.4054 21.8562 39.475 21.6262ZM4.41189 29.2403L18.7597 43.5881C19.8813 44.7097 21.4027 44.9179 22.7217 44.7893C24.0585 44.659 25.5148 44.1631 26.9723 43.4579C29.9052 42.0387 33.2618 39.5667 36.4142 36.4142C39.5667 33.2618 42.0387 29.9052 43.4579 26.9723C44.1631 25.5148 44.659 24.0585 44.7893 22.7217C44.9179 21.4027 44.7097 19.8813 43.5881 18.7597L29.2403 4.41187C27.8527 3.02428 25.8765 3.02573 24.2861 3.36776C22.6081 3.72863 20.7334 4.58419 18.8396 5.74801C16.4978 7.18716 13.9881 9.18353 11.5858 11.5858C9.18354 13.988 7.18717 16.4978 5.74802 18.8396C4.58421 20.7334 3.72865 22.6081 3.36778 24.2861C3.02574 25.8765 3.02429 27.8527 4.41189 29.2403Z" fill="currentColor" fill-rule="evenodd"></path>
</svg>
</div>
<h2 class="text-lg font-bold leading-tight tracking-tight">JointBuddy Admin</h2>
</div>
<div class="flex items-center gap-2 px-3 py-1 rounded-full bg-primary/20 text-primary text-xs font-bold uppercase tracking-wider border border-primary/20">
<span class="size-2 bg-primary rounded-full animate-pulse"></span>
            Staff Portal
        </div>
</header>
<main class="relative z-10 flex-grow flex items-center justify-center p-6">
<div class="w-full max-w-[440px] bg-white shadow-2xl rounded-2xl overflow-hidden">
<div class="pt-10 pb-6 text-center">
<div class="inline-flex items-center justify-center size-12 rounded-full bg-primary/10 text-primary mb-4">
<span class="material-symbols-outlined text-[32px]">admin_panel_settings</span>
</div>
<h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
<p class="text-primary text-sm font-semibold mt-1 uppercase tracking-widest">Authorized Access Only</p>
</div>
<form class="px-8 pb-8 space-y-5" onsubmit="return false;">
<div class="flex flex-col gap-2">
<label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Admin Email</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary text-[20px]">mail</span>
<input class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none text-gray-900 transition-all placeholder:text-gray-400 font-medium" placeholder="admin@jointbuddy.com" required="" type="email"/>
</div>
</div>
<div class="flex flex-col gap-2">
<div class="flex justify-between items-center">
<label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Secure Password</label>
<a class="text-xs text-primary hover:underline font-bold" href="#">Forgot Password?</a>
</div>
<div class="relative group">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary text-[20px]">lock</span>
<input class="w-full pl-11 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none text-gray-900 transition-all placeholder:text-gray-400 font-medium" placeholder="••••••••" required="" type="password"/>
<button class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" type="button">
<span class="material-symbols-outlined text-[20px]">visibility</span>
</button>
</div>
</div>
<div class="flex items-start gap-3 py-2">
<input class="mt-0.5 size-4 rounded border-gray-300 text-primary focus:ring-primary/20" id="2fa" type="checkbox"/>
<label class="text-sm text-gray-600 leading-tight cursor-pointer font-medium" for="2fa">
                        Require Two-Factor Authentication (2FA) for this session.
                    </label>
</div>
<button class="w-full bg-primary hover:bg-[#128a3d] text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2 transform active:scale-[0.98]" type="submit">
<span class="material-symbols-outlined text-[20px]">verified_user</span>
                    Secure Admin Login
                </button>
</form>
<div class="bg-gray-50 px-8 py-5 border-t border-gray-100 flex justify-center gap-6">
<div class="flex items-center gap-1.5 opacity-60">
<span class="material-symbols-outlined text-[16px] text-gray-700">encrypted</span>
<span class="text-[10px] font-bold uppercase tracking-wider text-gray-600">SSL Encrypted</span>
</div>
<div class="flex items-center gap-1.5 opacity-60">
<span class="material-symbols-outlined text-[16px] text-gray-700">security</span>
<span class="text-[10px] font-bold uppercase tracking-wider text-gray-600">SOC2 Verified</span>
</div>
</div>
</div>
</main>
<footer class="relative z-10 w-full px-6 py-6 border-t border-white/5 bg-black/20 backdrop-blur-md flex flex-col md:flex-row items-center justify-between text-gray-400 text-xs">
<div class="flex items-center gap-6 mb-4 md:mb-0">
<div class="flex items-center gap-2">
<span class="size-2 rounded-full bg-primary animate-pulse"></span>
<span>System Status: <span class="font-bold text-gray-200">Operational</span></span>
</div>
<div class="hidden md:block h-3 w-px bg-white/10"></div>
<p>© 2024 JointBuddy CannaBuddy. Internal Use Only.</p>
</div>
<div class="flex gap-6 items-center">
<a class="hover:text-primary transition-colors font-medium" href="#">Privacy Protocol</a>
<a class="hover:text-primary transition-colors font-medium" href="#">Security Audit</a>
<div class="flex items-center gap-1 text-[10px] uppercase font-bold tracking-widest text-white/40">
<span class="material-symbols-outlined text-[14px]">terminal</span>
                V2.4.0-PROD
            </div>
</div>
</footer>

</body></html>