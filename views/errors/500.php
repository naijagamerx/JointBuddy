<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="text-red-500 text-6xl mb-4">
            <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">500</h1>
        <h2 class="text-xl font-semibold text-gray-600 mb-4">Internal Server Error</h2>
        <p class="text-gray-600 mb-6">
            Something went wrong on our end. Our team has been notified and we're working to fix the issue.
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
        <p class="text-sm text-gray-500 mt-6">
            If the problem persists, please contact our support team.
        </p>
    </div>
</body>
</html>
