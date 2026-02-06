<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="text-blue-500 text-6xl mb-4">
            <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">404</h1>
        <h2 class="text-xl font-semibold text-gray-600 mb-4">Page Not Found</h2>
        <p class="text-gray-600 mb-6">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="space-y-3">
            <a href="<?php echo rurl('/'); ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded transition duration-200">
                Go to Homepage
            </a>
            <br>
            <a href="javascript:history.back()" class="inline-block text-blue-600 hover:text-blue-800 underline">
                Go Back
            </a>
        </div>
    </div>
</body>
</html>
