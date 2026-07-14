<?php
// File: /agency/manager-view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/agency/managers.php');
}

$user_id = (int)$_GET['id'];

// Fetch Manager
$stmt = $pdo->prepare("SELECT u.*, m.phone, m.designation FROM users u LEFT JOIN managers m ON u.id = m.user_id WHERE u.id = :id AND u.agency_id = :agency_id AND u.role_id = :role_id AND u.deleted_at IS NULL LIMIT 1");
$stmt->execute(['id' => $user_id, 'agency_id' => $agency_id, 'role_id' => ROLE_MANAGER]);
$manager = $stmt->fetch();

if (!$manager) {
    redirect('/agency/managers.php');
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'temporary suspend': return 'bg-yellow-100 text-yellow-800';
        case 'permanent suspend': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Manager Details</h1>
    <div>
        <a href="manager-edit.php?id=<?php echo $manager['id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-2">
            Edit
        </a>
        <a href="managers.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Back to List</a>
    </div>
</div>

<div class="px-4 sm:px-6 mt-4 max-w-3xl">
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Manager Information</h3>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($manager['account_status']); ?>">
                <?php echo htmlspecialchars($manager['account_status']); ?>
            </span>
        </div>
        <div class="px-6 py-5">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($manager['full_name']); ?></dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Designation</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($manager['designation'] ?? 'N/A'); ?></dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($manager['email_address']); ?></dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($manager['phone'] ?? 'N/A'); ?></dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Password Reset Pending?</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $manager['force_password_change'] ? 'Yes (Temporary Password Active)' : 'No'; ?></dd>
                </div>
                <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo date('d-m-Y h:i A', strtotime($manager['created_at'])); ?></dd>
                </div>
            </dl>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <form method="POST" action="manager-status.php" class="inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="id" value="<?php echo $manager['id']; ?>">
                <?php if ($manager['account_status'] === 'Active'): ?>
                    <input type="hidden" name="status" value="Temporary Suspend">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none">
                        Deactivate Manager
                    </button>
                <?php else: ?>
                    <input type="hidden" name="status" value="Active">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                        Activate Manager
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
