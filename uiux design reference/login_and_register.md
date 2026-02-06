<!-- JointBuddy Login Screen -->
<html class="" lang="en"><head></head><body class="min-h-screen flex flex-col md:flex-row">```html



<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JointBuddy | White Theme Registration</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">tailwind.config = {theme: {extend: {colors: {primary: "#16a34a", accent: "#2D5A27", "neutral-gray": "#F8F9FA", "background-light": "#f6f8f7", "background-dark": "#112117"}, fontFamily: {sans: ["Inter", "sans-serif"], display: "Inter"}, borderRadius: {DEFAULT: "1rem", lg: "2rem", xl: "3rem", full: "9999px"}}}};</script>
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
            background: radial-gradient(circle at 50% 50%, rgba(26, 61, 23, 0.4) 0%, rgba(10, 25, 9, 0.9) 100%);
        }
    </style>
<main class="w-full md:w-1/2 flex flex-col min-h-screen p-8 md:p-12 lg:p-16 relative z-10 bg-white">
<header class="mb-8">
<div class="flex items-center gap-2.5">
<div class="size-7 text-primary">
<svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path clip-rule="evenodd" d="M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z" fill="currentColor" fill-rule="evenodd"></path>
</svg>
</div>
<span class="text-gray-900 text-lg font-bold tracking-tight">JointBuddy</span>
</div>
</header>
<div class="max-w-md w-full mx-auto my-auto">
<div class="mb-8">
<h1 class="text-3xl font-semibold text-gray-900 mb-2">Create an account</h1>
<p class="text-gray-500">Join our community of premium enthusiasts.</p>
</div>
<form action="#" class="space-y-4">
<div class="grid grid-cols-2 gap-4">
<div class="space-y-1.5">
<label class="block text-sm font-medium text-gray-700">First Name</label>
<input class="input-field" placeholder="John" required="" type="text"/>
</div>
<div class="space-y-1.5">
<label class="block text-sm font-medium text-gray-700">Last Name</label>
<input class="input-field" placeholder="Doe" required="" type="text"/>
</div>
</div>
<div class="space-y-1.5">
<label class="block text-sm font-medium text-gray-700">Email address</label>
<input class="input-field" placeholder="name@company.com" required="" type="email"/>
</div>
<div class="space-y-1.5">
<label class="block text-sm font-medium text-gray-700">Password</label>
<div class="relative">
<input class="input-field" placeholder="••••••••" required="" type="password"/>
<button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" type="button">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
</div>
<p class="text-[11px] text-gray-400">Must be at least 8 characters long.</p>
</div>
<div class="flex items-start space-x-3 py-1">
<div class="flex items-center h-5">
<input class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4" id="age-verification" required="" type="checkbox"/>
</div>
<label class="text-sm text-gray-600 leading-tight" for="age-verification">
                    I verify that I am 21 years of age or older and agree to the <a class="text-primary font-semibold hover:underline" href="#">Terms of Service</a>.
                </label>
</div>
<button class="w-full bg-primary text-white font-semibold py-3 rounded-lg shadow-sm hover:bg-[#132c11] transition-all duration-200 mt-2" type="submit">
                Create Account
            </button>
</form>
<div class="relative flex items-center my-6">
<div class="flex-grow border-t border-gray-100"></div>
<span class="flex-shrink mx-4 text-gray-400 text-[10px] font-bold uppercase tracking-widest">or sign up with</span>
<div class="flex-grow border-t border-gray-100"></div>
</div>
<div class="grid grid-cols-2 gap-4">
<button class="btn-social">
<img alt="Google" class="w-4 h-4" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBKltSWjiC-E1P8L0_femaJlqVYhngQv1NeDPSaWQT5no_nlJJHW70QSODoPoDLBTzni5G0jGE3zv7yHevgutz8E45UOvZCvJnL4ELzH_glJpWxj9njBwnLMkMUl-tW6kywLBTHoedzaxsv2hW-5p6iZ2JgRFlZSO3n1BsiOf3PnGC8wdsgNdaQkkRTAwDTBCVEdJN8pelR-Eyuh8jg0Q_jDWBgH7r3Ps8cDQ6qvUF9hqtVW7iXgOa_FIJZ8453J52F-ROxNLpclpc"/>
<span>Google</span>
</button>
<button class="btn-social">
<span class="material-symbols-outlined text-[20px] text-black">ios</span>
<span>Apple</span>
</button>
</div>
<div class="mt-8 text-center">
<p class="text-gray-500 text-sm">
                Already have an account? 
                <a class="text-primary font-bold hover:underline ml-1" href="#">Sign in</a>
</p>
</div>
</div>
<footer class="mt-8">
<p class="text-xs text-gray-400">© 2024 CannaBuddy Premium Accessories. All rights reserved.</p>
</footer>
</main>
<aside class="hidden md:block w-1/2 relative overflow-hidden bg-primary">
<div class="absolute inset-0 split-bg-gradient z-10 opacity-60"></div>
<img alt="Premium Lifestyle Accessories" class="absolute inset-0 w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAKpK_X3xp8R0SW3FDzrvfNTqbtzDSWJzbHXNtvB9NcVlWnczzJB8xuaM6KbHCyPIxeCAUFyQAqiKTg5SO2Y-pVUqVkZ0Z7Te8mCR8ro_kDae9qbV9gFwx-iKgzTnRUWLJFJtcv1GBgjAHp4lC4qJP4Wk6LtdFGd5VeL5B8JQrIM5dDdyBzmbUTrMDfZLjuU0F3JO-XPnK9Tra2JDzP1TdibGq_C8S3VgO9I1iQvIa7SCmRGVOmgozAHdh7wABFdNFsZfZJT0NgJJY"/>
<div class="absolute bottom-16 left-16 right-16 p-8 backdrop-blur-xl bg-white/10 border border-white/20 rounded-2xl text-white z-20 shadow-2xl">
<div class="flex items-center gap-1 mb-4 text-yellow-400">
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
</div>
<p class="text-lg font-medium leading-relaxed mb-6 italic">"The quality of JointBuddy accessories is unmatched. Their attention to detail and clean aesthetic makes every experience premium. A true game changer for collectors."</p>
<div class="flex items-center gap-3">
<img alt="User" class="w-12 h-12 rounded-full object-cover border-2 border-white/30" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCvaBGNrhP7FRYa8rPbsDBNMKweCm8waagHzQXkPZ6uZfzgzz5QExS0oQPwZ33C5YIpgJxFF8Divb5VOwpq5A_y4aKe9K1HX-9yGwNEoquzb7dKWYEgq8_re8WJGiKQeX2-DXNaJDmKHfYrZThPDZY5q3liUEjtRJTU6KBEywjxf-yJ1SfmfAAAASGnhe_BEyIrpKvedSLPSSd-L0oAb9bmKdUk1tV5MyfFxj39r_HYZ5I33c0_2TfqZYhgEYcKJpvDoYat2i2_hqE"/>
<div>
<p class="font-bold text-sm">Marcus Chen</p>
<p class="text-xs text-white/70">Verified Collector</p>
</div>
</div>
</div>
</aside>

```</body></html>

<!-- JointBuddy Login Screen -->
<!DOCTYPE html>

<html class="" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JointBuddy | White Theme Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">tailwind.config = {theme: {extend: {colors: {primary: "#16a34a", accent: "#2D5A27", "neutral-gray": "#F8F9FA", "background-light": "#f6f8f7", "background-dark": "#112117"}, fontFamily: {sans: ["Inter", "sans-serif"], display: "Inter"}, borderRadius: {DEFAULT: "1rem", lg: "2rem", xl: "3rem", full: "9999px"}}}};</script>
<style type="text/tailwindcss">
        @layer base {
            body {
                @apply antialiased text-slate-900 bg-white;
            }
        }
        .input-field {
            @apply w-full px-4 py-3 bg-transparent border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all duration-200 placeholder:text-gray-400 text-sm;
        }
        .btn-social {
            @apply flex items-center justify-center gap-3 w-full py-3 border border-gray-200 rounded-lg font-medium text-gray-600 hover:bg-gray-50 transition-all duration-200 text-sm;
        }
        .split-bg-gradient {
            background: radial-gradient(circle at 50% 50%, rgba(26, 61, 23, 0.03) 0%, transparent 100%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">
<main class="w-full md:w-1/2 flex flex-col min-h-screen p-8 md:p-16 lg:p-24 relative z-10 bg-white">
<header class="mb-auto">
<div class="flex items-center gap-2.5">
<div class="size-7 text-primary">
<svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path clip-rule="evenodd" d="M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z" fill="currentColor" fill-rule="evenodd"></path>
</svg>
</div>
<span class="text-gray-900 text-lg font-bold tracking-tight">JointBuddy</span>
</div>
</header>
<div class="max-w-md w-full mx-auto my-12">
<div class="mb-10">
<h1 class="text-3xl font-semibold text-gray-900 mb-2">Welcome back</h1>
<p class="text-gray-500">Please enter your details to access your account.</p>
</div>
<form action="#" class="space-y-4">
<div class="space-y-1.5">
<label class="block text-sm font-medium text-gray-700">Email address</label>
<input class="input-field" placeholder="Enter your email" required="" type="email"/>
</div>
<div class="space-y-1.5">
<div class="flex justify-between items-center">
<label class="block text-sm font-medium text-gray-700">Password</label>
<a class="text-xs font-semibold text-primary hover:underline" href="#">Forgot password?</a>
</div>
<div class="relative">
<input class="input-field" placeholder="••••••••" required="" type="password"/>
<button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" type="button">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
</div>
</div>
<div class="flex items-center space-x-2 py-1">
<input class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4" id="remember" type="checkbox"/>
<label class="text-sm text-gray-600" for="remember">Remember for 30 days</label>
</div>
<button class="w-full bg-primary text-white font-semibold py-3 rounded-lg shadow-sm hover:bg-[#132c11] transition-all duration-200 mt-2" type="submit">
                    Sign in
                </button>
</form>
<div class="relative flex items-center my-8">
<div class="flex-grow border-t border-gray-100"></div>
<span class="flex-shrink mx-4 text-gray-400 text-[10px] font-bold uppercase tracking-widest">or continue with</span>
<div class="flex-grow border-t border-gray-100"></div>
</div>
<div class="grid grid-cols-2 gap-4">
<button class="btn-social">
<img alt="Google" class="w-4 h-4" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBKltSWjiC-E1P8L0_femaJlqVYhngQv1NeDPSaWQT5no_nlJJHW70QSODoPoDLBTzni5G0jGE3zv7yHevgutz8E45UOvZCvJnL4ELzH_glJpWxj9njBwnLMkMUl-tW6kywLBTHoedzaxsv2hW-5p6iZ2JgRFlZSO3n1BsiOf3PnGC8wdsgNdaQkkRTAwDTBCVEdJN8pelR-Eyuh8jg0Q_jDWBgH7r3Ps8cDQ6qvUF9hqtVW7iXgOa_FIJZ8453J52F-ROxNLpclpc"/>
<span>Google</span>
</button>
<button class="btn-social">
<span class="material-symbols-outlined text-[20px] text-black">ios</span>
<span>Apple</span>
</button>
</div>
<div class="mt-10 text-center">
<p class="text-gray-500 text-sm">
                    Don't have an account? 
                    <a class="text-primary font-bold hover:underline ml-1" href="#">Sign up for free</a>
</p>
</div>
</div>
<footer class="mt-auto pt-8">
<p class="text-xs text-gray-400">© 2024 CannaBuddy Premium Accessories. All rights reserved.</p>
</footer>
</main>
<aside class="hidden md:block w-1/2 relative overflow-hidden bg-neutral-50">
<div class="absolute inset-0 split-bg-gradient"></div>
<img alt="Premium Cannabis Leaf" class="absolute inset-0 w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAKpK_X3xp8R0SW3FDzrvfNTqbtzDSWJzbHXNtvB9NcVlWnczzJB8xuaM6KbHCyPIxeCAUFyQAqiKTg5SO2Y-pVUqVkZ0Z7Te8mCR8ro_kDae9qbV9gFwx-iKgzTnRUWLJFJtcv1GBgjAHp4lC4qJP4Wk6LtdFGd5VeL5B8JQrIM5dDdyBzmbUTrMDfZLjuU0F3JO-XPnK9Tra2JDzP1TdibGq_C8S3VgO9I1iQvIa7SCmRGVOmgozAHdh7wABFdNFsZfZJT0NgJJY"/>
<div class="absolute bottom-16 left-16 right-16 p-8 backdrop-blur-md bg-white/10 border border-white/20 rounded-2xl text-white">
<div class="flex items-center gap-2 mb-4">
<div class="flex text-yellow-400">
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
<span class="material-symbols-outlined fill-1">star</span>
</div>
</div>
<p class="text-lg font-medium leading-relaxed mb-4 italic">"The quality of JointBuddy accessories is unmatched. Their attention to detail and clean aesthetic makes every experience premium."</p>
<div class="flex items-center gap-3">
<img alt="User" class="w-10 h-10 rounded-full object-cover border-2 border-white/30" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCvaBGNrhP7FRYa8rPbsDBNMKweCm8waagHzQXkPZ6uZfzgzz5QExS0oQPwZ33C5YIpgJxFF8Divb5VOwpq5A_y4aKe9K1HX-9yGwNEoquzb7dKWYEgq8_re8WJGiKQeX2-DXNaJDmKHfYrZThPDZY5q3liUEjtRJTU6KBEywjxf-yJ1SfmfAAAASGnhe_BEyIrpKvedSLPSSd-L0oAb9bmKdUk1tV5MyfFxj39r_HYZ5I33c0_2TfqZYhgEYcKJpvDoYat2i2_hqE"/>
<div>
<p class="font-bold text-sm">Marcus Chen</p>
<p class="text-xs text-white/70">Verified Collector</p>
</div>
</div>
</div>
</aside>
</body></html>