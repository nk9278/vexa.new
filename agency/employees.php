<?php
// File: /agency/employees.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

// Employees list is Read-Only for Agency Owner
// Note: Since employee tables and assignments are not fully built, this acts as a placeholder structure
include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Employees Directory</h1>
    <p class="text-sm text-gray-500 mt-2">Read-only view of all employees in the agency.</p>
</div>

<div class="px-4 sm:px-6 mt-4">
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-10 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No Employees Found</h3>
            <p class="mt-1 text-sm text-gray-500">Employee records will be managed by Managers and CRM.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
