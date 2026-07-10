<?php
// File: /reports/payments.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

checkAuth(['Super Admin', 'Agency Owner', 'Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$role_name = $_SESSION['role_name'];

$date_filter = $_GET['date_range'] ?? 'All';
$date_sql = "";

if ($date_filter === 'Today') {
    $date_sql = " AND DATE(pp.updated_at) = CURDATE() ";
} elseif ($date_filter === 'This Week') {
    $date_sql = " AND YEARWEEK(pp.updated_at, 1) = YEARWEEK(CURDATE(), 1) ";
} elseif ($date_filter === 'This Month') {
    $date_sql = " AND MONTH(pp.updated_at) = MONTH(CURDATE()) AND YEAR(pp.updated_at) = YEAR(CURDATE()) ";
} elseif ($date_filter === 'This Year') {
    $date_sql = " AND YEAR(pp.updated_at) = YEAR(CURDATE()) ";
}

$query = "
    SELECT
        p.project_name,
        c.client_name,
        pp.project_amount,
        pp.received_amount,
        pp.pending_amount,
        pp.payment_status,
        pp.last_payment_date
    FROM project_payments pp
    JOIN projects p ON pp.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    WHERE p.agency_id = :agency_id AND p.deleted_at IS NULL $date_sql
    ORDER BY pp.updated_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['agency_id' => $agency_id]);
$payments = $stmt->fetchAll();

// Calculate Totals for top cards
$total_revenue = array_sum(array_column($payments, 'project_amount'));
$total_received = array_sum(array_column($payments, 'received_amount'));
$total_pending = array_sum(array_column($payments, 'pending_amount'));

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
<!-- Load html2pdf and xlsx for client-side exporting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Payment Reports</h1>

        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
            <form method="GET" action="payments.php" class="flex space-x-2">
                <select name="date_range" onchange="this.form.submit()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full shadow-sm text-sm font-medium outline-none">
                    <option value="All" <?php echo $date_filter === 'All' ? 'selected' : ''; ?>>All Time</option>
                    <option value="Today" <?php echo $date_filter === 'Today' ? 'selected' : ''; ?>>Today</option>
                    <option value="This Week" <?php echo $date_filter === 'This Week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="This Month" <?php echo $date_filter === 'This Month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="This Year" <?php echo $date_filter === 'This Year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </form>

            <a href="<?php echo BASE_URL; ?>/api/export.php?report=payments&date=<?php echo urlencode($date_filter); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium flex items-center">
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
        <a href="projects.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Project Reports</a>
        <a href="payments.php" class="text-indigo-600 font-bold border-b-2 border-indigo-600 pb-1 whitespace-nowrap">Payment Reports</a>
    </div>
</div>

<div class="px-4 sm:px-6 mb-6" id="report-content">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <p class="text-sm text-gray-500 font-medium">Total Billed Revenue</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">₹<?php echo number_format($total_revenue, 2); ?></p>
        </div>
        <div class="bg-green-50 p-6 rounded-xl shadow-sm border border-green-200">
            <p class="text-sm text-green-600 font-medium">Total Received Revenue</p>
            <p class="text-3xl font-bold text-green-700 mt-2">₹<?php echo number_format($total_received, 2); ?></p>
        </div>
        <div class="bg-red-50 p-6 rounded-xl shadow-sm border border-red-200">
            <p class="text-sm text-red-600 font-medium">Total Pending Revenue</p>
            <p class="text-3xl font-bold text-red-700 mt-2">₹<?php echo number_format($total_pending, 2); ?></p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project / Client</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Project Value</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Payment</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($payments) > 0): ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pay['project_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pay['client_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                ₹<?php echo number_format($pay['project_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right font-medium">
                                ₹<?php echo number_format($pay['received_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right font-medium">
                                ₹<?php echo number_format($pay['pending_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                    if($pay['payment_status'] === 'Completed') echo 'bg-green-100 text-green-800';
                                    elseif($pay['payment_status'] === 'Partial') echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-red-100 text-red-800';
                                    ?>">
                                    <?php echo htmlspecialchars($pay['payment_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($pay['last_payment_date'] ?: 'None'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No payment data available for the selected period.
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
        filename:     'vexa_payment_report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

function exportToExcel() {
    const table = document.getElementById('reportTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Payment Report"});
    XLSX.writeFile(wb, 'vexa_payment_report.xlsx');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
