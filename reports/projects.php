<?php
// File: /reports/projects.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Only Super Admin, Agency Owner, and Manager have agency-wide project report access.
checkAuth(['Super Admin', 'Agency Owner', 'Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$role_name = $_SESSION['role_name'];

$date_filter = $_GET['date_range'] ?? 'All';
$date_sql = "";

if ($date_filter === 'Today') {
    $date_sql = " AND DATE(p.created_at) = CURDATE() ";
} elseif ($date_filter === 'This Week') {
    $date_sql = " AND YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1) ";
} elseif ($date_filter === 'This Month') {
    $date_sql = " AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE()) ";
} elseif ($date_filter === 'This Year') {
    $date_sql = " AND YEAR(p.created_at) = YEAR(CURDATE()) ";
}

$query = "
    SELECT
        p.id,
        p.project_name,
        p.status,
        p.start_date,
        p.expected_completion_date,
        c.client_name,
        u.full_name as crm_name,
        COUNT(DISTINCT t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN t.status = 'Revision Required' THEN 1 ELSE 0 END) as revisions
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN users u ON p.crm_id = u.id
    LEFT JOIN tasks t ON p.id = t.project_id AND t.deleted_at IS NULL
    WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL $date_sql
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['agency_id' => $agency_id]);
$projects = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
<!-- Load html2pdf and xlsx for client-side exporting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Project Reports</h1>

        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
            <form method="GET" action="projects.php" class="flex space-x-2">
                <select name="date_range" onchange="this.form.submit()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full shadow-sm text-sm font-medium outline-none">
                    <option value="All" <?php echo $date_filter === 'All' ? 'selected' : ''; ?>>All Time</option>
                    <option value="Today" <?php echo $date_filter === 'Today' ? 'selected' : ''; ?>>Today</option>
                    <option value="This Week" <?php echo $date_filter === 'This Week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="This Month" <?php echo $date_filter === 'This Month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="This Year" <?php echo $date_filter === 'This Year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </form>

            <a href="<?php echo BASE_URL; ?>/api/export.php?report=projects&date=<?php echo urlencode($date_filter); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium flex items-center">
                CSV
            </a>
            <button onclick="exportToPDF()" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition shadow-sm text-sm font-medium flex items-center">
                PDF
            </button>
            <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-full hover:bg-green-700 transition shadow-sm text-sm font-medium flex items-center">
                Excel
            </button>
        </div>
    </div>

    <!-- Report Navigation -->
    <div class="flex space-x-4 mb-6 border-b border-gray-200 pb-4 overflow-x-auto">
        <a href="dashboard.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Dashboard</a>
        <a href="clients.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Client Reports</a>
        <a href="projects.php" class="text-indigo-600 font-bold border-b-2 border-indigo-600 pb-1 whitespace-nowrap">Project Reports</a>
        <a href="payments.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Payment Reports</a>
    </div>
</div>

<div class="px-4 sm:px-6 mb-16" id="report-content">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project / Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeline</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned CRM</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Completion</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Task Stats (C / P / R)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <?php
                            $percent = $project['total_tasks'] > 0
                                ? round(($project['completed_tasks'] / $project['total_tasks']) * 100)
                                : ($project['status'] === 'Completed' ? 100 : 0);
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($project['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($project['start_date'] ?: 'N/A'); ?> <br>to<br> <?php echo htmlspecialchars($project['expected_completion_date'] ?: 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($project['crm_name'] ?: 'Unassigned'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                    if($project['status'] === 'Completed') echo 'bg-green-100 text-green-800';
                                    elseif($project['status'] === 'Running') echo 'bg-blue-100 text-blue-800';
                                    elseif($project['status'] === 'Cancelled') echo 'bg-red-100 text-red-800';
                                    else echo 'bg-gray-100 text-gray-800';
                                    ?>
                                ">
                                    <?php echo htmlspecialchars($project['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $percent; ?>%</span>
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <span class="text-green-600 font-medium" title="Completed"><?php echo $project['completed_tasks'] ?: 0; ?></span> /
                                <span class="text-gray-500 font-medium" title="Pending"><?php echo $project['pending_tasks'] ?: 0; ?></span> /
                                <span class="text-red-600 font-medium" title="Revisions"><?php echo $project['revisions'] ?: 0; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No project data available for the selected period.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToPDF() {
    const element = document.getElementById('report-content');
    const opt = {
        margin:       0.5,
        filename:     'vexa_project_report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

function exportToExcel() {
    const table = document.getElementById('reportTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Project Report"});
    XLSX.writeFile(wb, 'vexa_project_report.xlsx');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
