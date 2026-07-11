<?php
// File: /500.php
require_once __DIR__ . '/config/constants.php';
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Server Error - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="text-center px-4">
        <h1 class="text-9xl font-black text-gray-800 tracking-tight">500</h1>
        <p class="text-2xl font-bold text-gray-900 mt-4 mb-2">Internal Server Error</p>
        <p class="text-gray-500 mb-8 max-w-md mx-auto">Something went wrong on our end. Our team has been notified. Please try again later.</p>
        <a href="/" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded-full hover:bg-indigo-700 transition shadow-md inline-block">Go Back Home</a>
    </div>
</body>
</html>
