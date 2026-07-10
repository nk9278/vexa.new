<?php
// File: /forgot-password.php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email_address = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            // Generate token
            $token = bin2hex(random_bytes(32));

            // Delete old tokens for this email to prevent clutter
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email_address = :email");
            $stmt->execute(['email' => $email]);

            // Store new token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email_address, token) VALUES (:email, :token)");
            $stmt->execute(['email' => $email, 'token' => $token]);

            // In a real scenario, we would send an email here.
            // Since we need to verify the workflow works without actual email server:
            $reset_link = BASE_URL . "/reset-password.php?token=" . $token;
            $success = "A password reset link has been generated for testing: <br><a href='$reset_link' class='underline'>$reset_link</a>";
        } else {
            // Do not reveal if email exists or not for security, but for workflow testing we can show generic success or show error.
            // Standard practice is to show generic success.
            $success = "If the email is registered, a password reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Forgot Password</h1>
            <p class="text-gray-500 mt-2 text-sm">Enter your email address to reset your password.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-500 p-3 rounded-md mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-700 p-3 rounded-md mb-6 text-sm break-all">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot-password.php">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email Address</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="you@example.com">
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-full hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
                Send Reset Link
            </button>

            <div class="text-center mt-6">
                <a href="login.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
