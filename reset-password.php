<?php
// File: /reset-password.php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = sanitizeInput($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } elseif (empty($token) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $pdo = getDbConnection();
        // Tokens are valid for 1 hour
        $stmt = $pdo->prepare("SELECT email_address FROM password_resets WHERE token = :token AND created_at > (NOW() - INTERVAL 1 HOUR) LIMIT 1");
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password, force_password_change = 0 WHERE email_address = :email");
            $stmt->execute(['password' => $hashed, 'email' => $reset['email_address']]);

            // Delete used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email_address = :email");
            $stmt->execute(['email' => $reset['email_address']]);

            $success = "Password reset successfully. You can now <a href='login.php' class='underline'>login</a>.";
        } else {
            $error = "Invalid or expired reset token.";
        }
    }
}
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

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-500 p-3 rounded-md mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-600 p-3 rounded-md mb-6 text-sm">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reset-password.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_POST['token'] ?? $_GET['token'] ?? ''); ?>">

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password">New Password</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-full hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
                Reset Password
            </button>
        </form>
    </div>
</body>
</html>
