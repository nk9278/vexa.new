<?php
// File: /agency/manager-edit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/agency/managers.php');
}

$user_id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch Manager to verify ownership
$stmt = $pdo->prepare("SELECT u.*, m.phone, m.designation FROM users u LEFT JOIN managers m ON u.id = m.user_id WHERE u.id = :id AND u.agency_id = :agency_id AND u.role_id = :role_id AND u.deleted_at IS NULL LIMIT 1");
$stmt->execute(['id' => $user_id, 'agency_id' => $agency_id, 'role_id' => ROLE_MANAGER]);
$manager = $stmt->fetch();

if (!$manager) {
    redirect('/agency/managers.php');
}

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
            // Check unique email excluding self
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email_address = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $user_id]);
            if ($stmt->fetch()) {
                $error = "Email is already registered by another user.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Update User
                    $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email_address = :email WHERE id = :id AND agency_id = :agency_id AND role_id = :role_id");
                    $stmt->execute([
                        'full_name' => $full_name,
                        'email' => $email,
                        'id' => $user_id,
                        'agency_id' => $agency_id,
                        'role_id' => ROLE_MANAGER
                    ]);

                    // Update Manager Profile
                    $stmt = $pdo->prepare("UPDATE managers SET phone = :phone, designation = :designation WHERE user_id = :user_id");
                    $stmt->execute([
                        'phone' => $phone,
                        'designation' => $designation,
                        'user_id' => $user_id
                    ]);

                    $pdo->commit();
                    $success = "Manager updated successfully.";

                    // Refresh manager data
                    $stmt = $pdo->prepare("SELECT u.*, m.phone, m.designation FROM users u LEFT JOIN managers m ON u.id = m.user_id WHERE u.id = :id AND u.agency_id = :agency_id AND u.role_id = :role_id AND u.deleted_at IS NULL LIMIT 1");
                    $stmt->execute(['id' => $user_id, 'agency_id' => $agency_id, 'role_id' => ROLE_MANAGER]);
                    $manager = $stmt->fetch();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to update manager: " . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Edit Manager: <?php echo htmlspecialchars($manager['full_name']); ?></h1>
        <div>
            <a href="manager-view.php?id=<?php echo $manager['id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-900 mr-4">Back to View</a>
        </div>
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
        <form method="POST" action="manager-edit.php?id=<?php echo $manager['id']; ?>" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($manager['full_name']); ?>" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($manager['email_address']); ?>" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($manager['phone'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" name="designation" value="<?php echo htmlspecialchars($manager['designation'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="mt-6 border-t pt-6 flex justify-end items-center">
                <span class="text-xs text-gray-500 mr-4">Note: Passwords cannot be changed by the Agency Owner.</span>
                <button type="submit" class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    Update Manager
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
