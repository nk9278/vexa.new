<?php
// File: /agency/payments.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

// Placeholders since business logic for payments isn't built yet
$placeholders = [
    'total_client_value' => 250000.00,
    'total_received' => 150000.00,
    'pending_amount' => 75000.00,
    'overdue_amount' => 25000.00
];

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Payment Summary</h1>
    <p class="text-sm text-gray-500 mt-2">Read-only overview of agency financials.</p>
</div>

<div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 px-4 sm:px-6">
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Client Value</dt>
            <dd class="mt-1 text-2xl font-semibold text-indigo-600">₹<?php echo number_format($placeholders['total_client_value'], 2); ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Received</dt>
            <dd class="mt-1 text-2xl font-semibold text-green-600">₹<?php echo number_format($placeholders['total_received'], 2); ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Amount</dt>
            <dd class="mt-1 text-2xl font-semibold text-orange-500">₹<?php echo number_format($placeholders['pending_amount'], 2); ?></dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Overdue Amount</dt>
            <dd class="mt-1 text-2xl font-semibold text-red-600">₹<?php echo number_format($placeholders['overdue_amount'], 2); ?></dd>
        </div>
    </div>
</div>

<div class="px-4 sm:px-6 mt-8">
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Upcoming Payments</h3>
        </div>
        <div class="px-6 py-10 text-center">
            <p class="text-sm text-gray-500">No upcoming payments found.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
