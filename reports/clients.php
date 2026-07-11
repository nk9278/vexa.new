<?php
// File: /reports/clients.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Only Super Admin, Agency Owner, and Manager have client-level report access
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
        c.client_name,
        COUNT(p.id) as total_projects,
        SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) as completed_projects,
        SUM(CASE WHEN p.status = 'Running' THEN 1 ELSE 0 END) as running_projects,
        SUM(CASE WHEN p.status = 'Pending' THEN 1 ELSE 0 END) as pending_projects,
        SUM(pp.project_amount) as total_value,
        SUM(pp.received_amount) as received_amount,
        SUM(pp.pending_amount) as pending_amount,
        MAX(pp.last_payment_date) as last_payment
    FROM clients c
    LEFT JOIN projects p ON c.id = p.client_id $date_sql AND p.deleted_at IS NULL
    LEFT JOIN project_payments pp ON p.id = pp.project_id
    WHERE c.agency_id = :agency_id AND c.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY c.client_name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['agency_id' => $agency_id]);
$clients = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
<!-- Load html2pdf and xlsx for client-side exporting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Client Reports</h1>

        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
            <form method="GET" action="clients.php" class="flex space-x-2">
                <select name="date_range" onchange="this.form.submit()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full shadow-sm text-sm font-medium outline-none">
                    <option value="All" <?php echo $date_filter === 'All' ? 'selected' : ''; ?>>All Time</option>
                    <option value="Today" <?php echo $date_filter === 'Today' ? 'selected' : ''; ?>>Today</option>
                    <option value="This Week" <?php echo $date_filter === 'This Week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="This Month" <?php echo $date_filter === 'This Month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="This Year" <?php echo $date_filter === 'This Year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </form>

            <a href="<?php echo BASE_URL; ?>/api/export.php?report=clients&date=<?php echo urlencode($date_filter); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium flex items-center">
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
        <a href="clients.php" class="text-indigo-600 font-bold border-b-2 border-indigo-600 pb-1 whitespace-nowrap">Client Reports</a>
        <a href="projects.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Project Reports</a>
        <a href="payments.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Payment Reports</a>
    </div>
</div>

<div class="px-4 sm:px-6 mb-16" id="report-content">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Name</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Projects</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Completed / Running</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pending Due</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($clients) > 0): ?>
                    <?php foreach ($clients as $client): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($client['client_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <?php echo $client['total_projects']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <span class="text-green-600 font-medium"><?php echo $client['completed_projects'] ?: 0; ?></span>
                                <span class="text-gray-400 mx-1">/</span>
                                <span class="text-blue-600 font-medium"><?php echo $client['running_projects'] ?: 0; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                ₹<?php echo number_format($client['total_value'] ?: 0, 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right font-medium">
                                ₹<?php echo number_format($client['received_amount'] ?: 0, 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right font-medium">
                                ₹<?php echo number_format($client['pending_amount'] ?: 0, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No client data available for the selected period.
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
        filename:     'vexa_client_report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

function exportToExcel() {
    const table = document.getElementById('reportTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Client Report"});
    XLSX.writeFile(wb, 'vexa_client_report.xlsx');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
