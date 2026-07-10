<?php
// File: /super-admin/agency-add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Super Admin']);

$pdo = getDbConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        // Agency Data
        $name = sanitizeInput($_POST['name'] ?? '');
        $owner_name = sanitizeInput($_POST['owner_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $company_name = sanitizeInput($_POST['company_name'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        // Subscription Data
        $plan_name = sanitizeInput($_POST['plan_name'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $price = $_POST['price'] ?? 0.00;

        // Validations
        if (empty($name) || empty($owner_name) || empty($email) || empty($start_date) || empty($end_date)) {
            $error = "Name, Owner Name, Email, and Subscription dates are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check unique email in agencies
            $stmt = $pdo->prepare("SELECT id FROM agencies WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $agency_exists = $stmt->fetch();

            // Check unique email in users
            $stmt2 = $pdo->prepare("SELECT id FROM users WHERE email_address = :email");
            $stmt2->execute(['email' => $email]);
            $user_exists = $stmt2->fetch();

            if ($agency_exists || $user_exists) {
                $error = "Email is already in use.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Insert Agency
                    $stmt = $pdo->prepare("INSERT INTO agencies (name, owner_name, email, phone, company_name, address, status) VALUES (:name, :owner_name, :email, :phone, :company_name, :address, :status)");
                    $stmt->execute([
                        'name' => $name,
                        'owner_name' => $owner_name,
                        'email' => $email,
                        'phone' => $phone,
                        'company_name' => $company_name,
                        'address' => $address,
                        'status' => $status
                    ]);
                    $agency_id = $pdo->lastInsertId();

                    // Insert Subscription
                    $stmt = $pdo->prepare("INSERT INTO agency_subscriptions (agency_id, plan_name, start_date, end_date, price) VALUES (:agency_id, :plan_name, :start_date, :end_date, :price)");
                    $stmt->execute([
                        'agency_id' => $agency_id,
                        'plan_name' => $plan_name,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'price' => $price
                    ]);

                    // Generate a random temporary password
                    $temp_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 10);
                    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

                    // Insert Agency Owner User
                    $stmt = $pdo->prepare("INSERT INTO users (agency_id, role_id, full_name, email_address, password, force_password_change, account_status) VALUES (:agency_id, :role_id, :full_name, :email, :password, 1, 'Active')");
                    $stmt->execute([
                        'agency_id' => $agency_id,
                        'role_id' => ROLE_AGENCY_OWNER,
                        'full_name' => $owner_name,
                        'email' => $email,
                        'password' => $hashed_password
                    ]);

                    $pdo->commit();
                    $success = "Agency added successfully. Temporary Password: <strong>$temp_password</strong> (Please copy this, it will not be shown again.)";
                    // Optionally redirect to agencies list
                    // redirect('/super-admin/agencies.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to add agency: " . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Add Agency</h1>
        <a href="agencies.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Back to List</a>
    </div>
</div>

<div class="px-4 sm:px-6 mt-4">
    <?php if ($error): ?>
        <div class="bg-red-50 text-red-500 p-4 rounded-xl mb-6 text-sm border border-red-100"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 text-sm border border-green-100"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" action="agency-add.php" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Agency Details -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Agency Details</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agency Name *</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                        <input type="text" name="owner_name" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                        <input type="text" name="company_name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>
                </div>

                <!-- Subscription Details -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Subscription & Status</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="Active">Active</option>
                            <option value="Demo">Demo</option>
                            <option value="Hold">Hold</option>
                            <option value="Temporary Suspend">Temporary Suspend</option>
                            <option value="Permanent Suspend">Permanent Suspend</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subscription Plan Name</label>
                        <input type="text" name="plan_name" placeholder="e.g. Pro Plan" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                            <input type="date" name="start_date" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                            <input type="date" name="end_date" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                        <input type="number" step="0.01" name="price" value="0.00" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t pt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Save Agency
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
