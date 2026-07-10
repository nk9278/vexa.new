<?php
// File: /reset-password.php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (empty($token)) {
    redirect('/login.php');
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT email_address FROM password_resets WHERE token = :token LIMIT 1");
$stmt->execute(['token' => $token]);
$reset_record = $stmt->fetch();

if (!$reset_record) {
    $error = "Invalid or expired reset token.";
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($password) || empty($confirm_password)) {
            $error = "Both password fields are required.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $email = $reset_record['email_address'];

            try {
                $pdo->beginTransaction();

                // Update user password and remove force reset if exists
                $stmt = $pdo->prepare("UPDATE users SET password = :password, force_password_change = 0 WHERE email_address = :email");
                $stmt->execute(['password' => $hashed, 'email' => $email]);

                // Delete the token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email_address = :email");
                $stmt->execute(['email' => $email]);

                $pdo->commit();
                $success = "Password reset successfully. You can now <a href='login.php' class='underline'>login</a>.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to reset password: " . $e->getMessage();
            }
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
            <div class="bg-green-50 text-green-700 p-3 rounded-md mb-6 text-sm">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <?php if ($reset_record): ?>
            <form method="POST" action="reset-password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
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
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
