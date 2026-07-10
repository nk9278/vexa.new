<?php
// File: /super-admin/agency-view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Super Admin']);

$pdo = getDbConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/super-admin/agencies.php');
}

$agency_id = (int)$_GET['id'];

// Fetch Agency
$stmt = $pdo->prepare("SELECT * FROM agencies WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$stmt->execute(['id' => $agency_id]);
$agency = $stmt->fetch();

if (!$agency) {
    redirect('/super-admin/agencies.php');
}

// Fetch Subscriptions
$stmt = $pdo->prepare("SELECT * FROM agency_subscriptions WHERE agency_id = :id ORDER BY start_date DESC");
$stmt->execute(['id' => $agency_id]);
$subscriptions = $stmt->fetchAll();

$current_subscription = count($subscriptions) > 0 ? $subscriptions[0] : null;

// Calculate remaining time
$remaining_days = 0;
$remaining_months = 0;
if ($current_subscription && strtotime($current_subscription['end_date']) > time()) {
    $end = new DateTime($current_subscription['end_date']);
    $now = new DateTime();
    $diff = $now->diff($end);
    $remaining_days = $diff->days;
    $remaining_months = ($diff->y * 12) + $diff->m;
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'demo': return 'bg-blue-100 text-blue-800';
        case 'hold': return 'bg-orange-100 text-orange-800';
        case 'temporary suspend': return 'bg-yellow-100 text-yellow-800';
        case 'permanent suspend': return 'bg-red-100 text-red-800';
        case 'expired': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Agency Details</h1>
    <div>
        <a href="agency-edit.php?id=<?php echo $agency['id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition mr-2">
            Edit
        </a>
        <a href="agencies.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Back to List</a>
    </div>
</div>

<div class="px-4 sm:px-6 mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Agency Info Card -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Agency Information</h3>
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($agency['status']); ?>">
                    <?php echo htmlspecialchars($agency['status']); ?>
                </span>
            </div>
            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Agency Name</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($agency['name']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($agency['company_name'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Owner Name</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($agency['owner_name']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($agency['email']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($agency['phone'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('d-m-Y', strtotime($agency['created_at'])); ?></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($agency['address'] ?? 'N/A')); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Subscription Info Card -->
    <div class="lg:col-span-1">
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Current Subscription</h3>
            </div>
            <div class="px-6 py-5">
                <?php if ($current_subscription): ?>
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-500">Plan</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($current_subscription['plan_name']); ?></p>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-500">Remaining Time</p>
                        <p class="text-lg font-semibold <?php echo $remaining_days <= 7 ? 'text-red-600' : 'text-indigo-600'; ?>">
                            <?php if ($remaining_days > 0): ?>
                                <?php echo $remaining_months; ?> Months (<?php echo $remaining_days; ?> Days)
                            <?php else: ?>
                                Expired
                            <?php endif; ?>
                        </p>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-4">
                        <div class="col-span-1">
                            <dt class="text-xs font-medium text-gray-500 uppercase">Start Date</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo date('d-m-Y', strtotime($current_subscription['start_date'])); ?></dd>
                        </div>
                        <div class="col-span-1">
                            <dt class="text-xs font-medium text-gray-500 uppercase">End Date</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo date('d-m-Y', strtotime($current_subscription['end_date'])); ?></dd>
                        </div>
                    </dl>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No active subscription found.</p>
                <?php endif; ?>
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                <a href="agency-edit.php?id=<?php echo $agency['id']; ?>#subscription" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Extend Subscription &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
