<?php
// File: /super-admin/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Super Admin']);

include __DIR__ . '/header.php';
?>

<?php
// Fetch Statistics
$pdo = getDbConnection();

// Initialize stats
$stats = [
    'total' => 0,
    'active' => 0,
    'demo' => 0,
    'hold' => 0,
    'suspended' => 0,
    'expired' => 0
];

// Get status counts
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM agencies WHERE deleted_at IS NULL GROUP BY status");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $status = strtolower($row['status']);
    if (isset($stats[$status])) {
        $stats[$status] = $row['count'];
    } elseif ($status === 'temporary suspend' || $status === 'permanent suspend') {
        $stats['suspended'] += $row['count'];
    }
    $stats['total'] += $row['count'];
}

// Get Revenue
// Note: For simplicity, Total Revenue is sum of all prices, Monthly is sum of prices starting this month
$stmt = $pdo->prepare("SELECT SUM(price) as total_rev FROM agency_subscriptions JOIN agencies ON agency_subscriptions.agency_id = agencies.id WHERE agencies.deleted_at IS NULL");
$stmt->execute();
$total_revenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(price) as monthly_rev FROM agency_subscriptions JOIN agencies ON agency_subscriptions.agency_id = agencies.id WHERE agencies.deleted_at IS NULL AND MONTH(start_date) = MONTH(CURRENT_DATE()) AND YEAR(start_date) = YEAR(CURRENT_DATE())");
$stmt->execute();
$monthly_revenue = $stmt->fetchColumn() ?: 0;

// Get Expiring Plans (next 30 days)
$stmt = $pdo->prepare("SELECT COUNT(*) as expiring FROM agency_subscriptions JOIN agencies ON agency_subscriptions.agency_id = agencies.id WHERE agencies.deleted_at IS NULL AND end_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)");
$stmt->execute();
$expiring_plans = $stmt->fetchColumn() ?: 0;
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Super Admin Dashboard</h1>
</div>

<!-- Statistics Cards -->
<div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 px-4 sm:px-6">

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-indigo-600"><?php echo $stats['total']; ?></dd>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Active Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-green-600"><?php echo $stats['active']; ?></dd>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Demo Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-blue-600"><?php echo $stats['demo']; ?></dd>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Hold Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-orange-500"><?php echo $stats['hold']; ?></dd>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Suspended Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-red-600"><?php echo $stats['suspended']; ?></dd>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Expired Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-500"><?php echo $stats['expired']; ?></dd>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition transform hover:-translate-y-1">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Expiring Plans</dt>
            <dd class="mt-1 text-3xl font-semibold text-yellow-600"><?php echo $expiring_plans; ?></dd>
        </div>
    </div>

</div>

<!-- Financial Cards -->
<div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-2 px-4 sm:px-6">
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 overflow-hidden shadow-md rounded-xl text-white hover:shadow-lg transition">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium truncate opacity-80">Total Revenue</dt>
            <dd class="mt-1 text-4xl font-bold">$<?php echo number_format($total_revenue, 2); ?></dd>
        </div>
    </div>

    <div class="bg-gradient-to-r from-green-500 to-emerald-600 overflow-hidden shadow-md rounded-xl text-white hover:shadow-lg transition">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium truncate opacity-80">Monthly Revenue</dt>
            <dd class="mt-1 text-4xl font-bold">$<?php echo number_format($monthly_revenue, 2); ?></dd>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
