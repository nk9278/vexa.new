<?php
// File: /force-password-change.php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

$pdo = getDbConnection();

// Check if user still needs to force change
$stmt = $pdo->prepare("SELECT force_password_change FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['force_password_change']) {
    redirectUserByRole($_SESSION['role_name']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $error = "Both password fields are required.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password, force_password_change = 0 WHERE id = :id");
            $stmt->execute(['password' => $hashed, 'id' => $_SESSION['user_id']]);

            // Redirect to their dashboard
            redirectUserByRole($_SESSION['role_name']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
            <p class="text-gray-500 mt-2 text-sm">Please change your temporary password to continue.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-500 p-3 rounded-md mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="force-password-change.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-full hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
                Update Password
            </button>
        </form>
    </div>
</body>
</html>
