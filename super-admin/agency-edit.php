<?php
// File: /super-admin/agency-edit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Super Admin']);

$pdo = getDbConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/super-admin/agencies.php');
}

$agency_id = (int)$_GET['id'];
$error = '';
$success = '';

// Handle Form Submission
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

        // Validations
        if (empty($name) || empty($owner_name) || empty($email)) {
            $error = "Name, Owner Name, and Email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check unique email excluding self
            $stmt = $pdo->prepare("SELECT id FROM agencies WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $agency_id]);
            if ($stmt->fetch()) {
                $error = "Email is already in use by another agency.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Update Agency
                    $stmt = $pdo->prepare("UPDATE agencies SET name = :name, owner_name = :owner_name, email = :email, phone = :phone, company_name = :company_name, address = :address, status = :status WHERE id = :id");
                    $stmt->execute([
                        'name' => $name,
                        'owner_name' => $owner_name,
                        'email' => $email,
                        'phone' => $phone,
                        'company_name' => $company_name,
                        'address' => $address,
                        'status' => $status,
                        'id' => $agency_id
                    ]);

                    // Handle Extend Subscription if provided
                    if (!empty($_POST['extend_start_date']) && !empty($_POST['extend_end_date'])) {
                        $plan_name = sanitizeInput($_POST['extend_plan_name'] ?? 'Extended Plan');
                        $start_date = $_POST['extend_start_date'];
                        $end_date = $_POST['extend_end_date'];
                        $price = $_POST['extend_price'] ?? 0.00;

                        $stmt = $pdo->prepare("INSERT INTO agency_subscriptions (agency_id, plan_name, start_date, end_date, price) VALUES (:agency_id, :plan_name, :start_date, :end_date, :price)");
                        $stmt->execute([
                            'agency_id' => $agency_id,
                            'plan_name' => $plan_name,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'price' => $price
                        ]);
                    }

                    $pdo->commit();
                    $success = "Agency updated successfully.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to update agency: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Agency
$stmt = $pdo->prepare("SELECT * FROM agencies WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute(['id' => $agency_id]);
$agency = $stmt->fetch();

if (!$agency) {
    redirect('/super-admin/agencies.php');
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Edit Agency: <?php echo htmlspecialchars($agency['name']); ?></h1>
        <a href="agency-view.php?id=<?php echo $agency['id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Back to View</a>
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
        <form method="POST" action="agency-edit.php?id=<?php echo $agency_id; ?>" class="p-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Agency Details -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Agency Details</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agency Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($agency['name']); ?>" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                        <input type="text" name="owner_name" value="<?php echo htmlspecialchars($agency['owner_name']); ?>" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($agency['email']); ?>" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($agency['phone'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($agency['company_name'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none"><?php echo htmlspecialchars($agency['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Status and Subscription Extend -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Status & Validation</h3>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <?php
                            $statuses = ['Active', 'Demo', 'Hold', 'Temporary Suspend', 'Permanent Suspend', 'Expired'];
                            foreach ($statuses as $s) {
                                $selected = $agency['status'] === $s ? 'selected' : '';
                                echo "<option value=\"$s\" $selected>$s</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <h3 id="subscription" class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Extend Subscription / Demo Expiry</h3>
                    <p class="text-xs text-gray-500 mb-4">Leave dates empty if you do not wish to extend or create a new subscription record.</p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name</label>
                        <input type="text" name="extend_plan_name" placeholder="e.g. Pro Plan Renewal" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Start Date</label>
                            <input type="date" name="extend_start_date" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New End Date / Demo Expiry</label>
                            <input type="date" name="extend_end_date" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                        <input type="number" step="0.01" name="extend_price" value="0.00" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t pt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Update Agency
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
