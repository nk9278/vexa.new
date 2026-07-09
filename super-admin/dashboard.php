<?php
// File: /super-admin/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Super Admin']);

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Super Admin Dashboard</h1>
</div>
<div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Placeholder Stats -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Agencies</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">12</dd>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">145</dd>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
