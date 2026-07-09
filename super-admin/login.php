<?php
// File: /super-admin/login.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirectUserByRole($_SESSION['role_name']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // loginUser with auto_redirect = false
        $loginResult = loginUser($email, $password, false);
        if ($loginResult === true) {
            // Verify if actually Super Admin
            if ($_SESSION['role_name'] !== 'Super Admin') {
                // Not super admin: destroy session, reset error
                session_unset();
                session_destroy();
                $error = "Unauthorized access.";
            } else {
                redirect('/super-admin/dashboard.php');
            }
        } else {
            $error = $loginResult;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #111827; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900"><?php echo APP_NAME; ?> Admin</h1>
            <p class="text-gray-500 mt-2">Restricted Access</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-500 p-3 rounded-md mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email Address</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="admin@vexa.com">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white font-semibold py-3 rounded-full hover:bg-black transition shadow-md hover:shadow-lg">
                Secure Login
            </button>
        </form>
    </div>
</body>
</html>
