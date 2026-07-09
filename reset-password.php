<?php
// File: /reset-password.php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Reset Password</h1>
            <p class="text-gray-500 mt-2 text-sm">Enter your new password below.</p>
        </div>

        <form method="POST" action="reset-password.php">
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password">New Password</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <button type="button" class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-full hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
                Reset Password
            </button>
        </form>
    </div>
</body>
</html>
