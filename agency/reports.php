<?php
// File: /agency/reports.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Reports Overview</h1>
    <p class="text-sm text-gray-500 mt-2">View high-level reports on agency performance.</p>
</div>

<div class="px-4 sm:px-6 mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php
    $reports = [
        ['title' => 'Clients Report', 'desc' => 'Overview of client acquisition and retention.'],
        ['title' => 'Projects Report', 'desc' => 'Analysis of project completion and timelines.'],
        ['title' => 'Employees Report', 'desc' => 'Employee performance and task completion stats.'],
        ['title' => 'Managers Report', 'desc' => 'Team management and oversight metrics.'],
        ['title' => 'Payments Report', 'desc' => 'Detailed financial transaction history.']
    ];
    foreach ($reports as $report):
    ?>
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo $report['title']; ?></h3>
        <p class="text-sm text-gray-500 mb-4"><?php echo $report['desc']; ?></p>
        <button class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Generate Report &rarr;</button>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
