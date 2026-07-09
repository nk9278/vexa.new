<?php
// File: /agency/manager-add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $designation = sanitizeInput($_POST['designation'] ?? '');

        if (empty($full_name) || empty($email)) {
            $error = "Name and Email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check unique email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email_address = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $error = "Email is already registered.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Generate a random temporary password (e.g., Mngr@1234!)
                    $temp_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 10);
                    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

                    // Insert User
                    $stmt = $pdo->prepare("INSERT INTO users (agency_id, role_id, full_name, email_address, password, force_password_change, account_status) VALUES (:agency_id, :role_id, :full_name, :email, :password, 1, 'Active')");
                    $stmt->execute([
                        'agency_id' => $agency_id,
                        'role_id' => ROLE_MANAGER,
                        'full_name' => $full_name,
                        'email' => $email,
                        'password' => $hashed_password
                    ]);
                    $user_id = $pdo->lastInsertId();

                    // Insert Manager Profile
                    $stmt = $pdo->prepare("INSERT INTO managers (user_id, phone, designation) VALUES (:user_id, :phone, :designation)");
                    $stmt->execute([
                        'user_id' => $user_id,
                        'phone' => $phone,
                        'designation' => $designation
                    ]);

                    $pdo->commit();
                    $success = "Manager added successfully. Temporary Password: <strong>$temp_password</strong> (Please copy this, it will not be shown again.)";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to add manager: " . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Add Manager</h1>
        <a href="managers.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Back to List</a>
    </div>
</div>

<div class="px-4 sm:px-6 mt-4 max-w-3xl">
    <?php if ($error): ?>
        <div class="bg-red-50 text-red-500 p-4 rounded-xl mb-6 text-sm border border-red-100"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm border border-green-200"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" action="manager-add.php" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" placeholder="e.g. Sales Manager" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="mt-6 border-t pt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    Save Manager
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
