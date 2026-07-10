<?php
// File: /reports/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Disallow external unauthorized access
checkAuth();

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];
$role_name = $_SESSION['role_name'];

// Initial variables for charts and cards
$kpis = [];
$score = 0;
$score_label = '';

// Date Filtering for reports
$date_filter = $_GET['date_range'] ?? 'All';

// We define aliased date conditions for use in complex joins
$date_sql_agency = "";
$date_sql_tasks = "";
$date_params = [];

if ($date_filter === 'Today') {
    $date_sql_agency = " AND DATE(created_at) = CURDATE() ";
    $date_sql_tasks = " AND DATE(t.created_at) = CURDATE() ";
} elseif ($date_filter === 'This Week') {
    $date_sql_agency = " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) ";
    $date_sql_tasks = " AND YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1) ";
} elseif ($date_filter === 'This Month') {
    $date_sql_agency = " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) ";
    $date_sql_tasks = " AND MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE()) ";
} elseif ($date_filter === 'This Year') {
    $date_sql_agency = " AND YEAR(created_at) = YEAR(CURDATE()) ";
    $date_sql_tasks = " AND YEAR(t.created_at) = YEAR(CURDATE()) ";
}

// Scope Data by Role
if ($role_name === 'Agency Owner' || $role_name === 'Super Admin') {
    // Agency Reports
    $score = calculateAgencyProductivity($agency_id);
    $score_label = 'Agency Productivity';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE agency_id = :aid AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Total Clients'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :aid AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Total Projects'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :aid AND status = 'Completed' AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Completed Projects'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT SUM(project_amount) FROM project_payments pp JOIN projects p ON pp.project_id = p.id WHERE p.agency_id = :aid AND p.deleted_at IS NULL");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Total Revenue'] = '$' . number_format($stmt->fetchColumn() ?: 0, 2);

} elseif ($role_name === 'Manager') {
    // Manager Reports
    $score = calculateManagerProductivity($agency_id);
    $score_label = 'Manager Productivity';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE agency_id = :aid AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Clients Managed'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :aid AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Projects Managed'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE agency_id = :aid AND status = 'Completed' AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Projects Completed'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE p.agency_id = :aid AND p.deleted_at IS NULL");
    $stmt->execute(['aid' => $agency_id]);
    $kpis['Assigned Employees'] = $stmt->fetchColumn();

} elseif ($role_name === 'CRM') {
    // CRM Reports
    $score = calculateCRMProductivity($user_id);
    $score_label = 'CRM Productivity';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE crm_id = :uid AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Projects Assigned'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :uid AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Tasks Created'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE crm_id = :uid AND status = 'Completed' AND deleted_at IS NULL $date_sql_agency");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Tasks Approved'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_submissions ts JOIN tasks t ON ts.task_id = t.id WHERE t.crm_id = :uid AND ts.status = 'Revision Required' $date_sql_tasks");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Revision Requests'] = $stmt->fetchColumn();

} elseif ($role_name === 'Employee') {
    // Employee Reports
    $score = calculateEmployeeProductivity($user_id);
    $score_label = 'Employee Productivity';

    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = :uid AND t.deleted_at IS NULL $date_sql_tasks");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Assigned Tasks'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = :uid AND t.status = 'Completed' AND t.deleted_at IS NULL $date_sql_tasks");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Completed Tasks'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = :uid AND t.status = 'Revision Required' AND t.deleted_at IS NULL $date_sql_tasks");
    $stmt->execute(['uid' => $user_id]);
    $kpis['Revision Requests'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(t.id) FROM tasks t JOIN task_assignments ta ON t.id = ta.task_id WHERE ta.employee_id = :uid AND DATE(t.due_date) = CURDATE() AND t.deleted_at IS NULL");
    $stmt->execute(['uid' => $user_id]);
    $kpis["Today's Tasks"] = $stmt->fetchColumn();
}

// Chart Data (Mocking project status distribution based on role context)
$chart_labels = ['Pending', 'Running', 'On Hold', 'Completed', 'Cancelled'];
$chart_data = [0, 0, 0, 0, 0];

$status_query = "SELECT status, COUNT(*) as count FROM projects WHERE agency_id = :aid AND deleted_at IS NULL";
$status_params = ['aid' => $agency_id];

if ($role_name === 'CRM') {
    $status_query .= " AND crm_id = :uid";
    $status_params['uid'] = $user_id;
} elseif ($role_name === 'Employee') {
    $status_query .= " AND id IN (SELECT project_id FROM project_assignments WHERE employee_id = :uid)";
    $status_params['uid'] = $user_id;
}
$status_query .= " GROUP BY status";

$stmt = $pdo->prepare($status_query);
$stmt->execute($status_params);
$statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($chart_labels as $index => $label) {
    if (isset($statuses[$label])) {
        $chart_data[$index] = (int)$statuses[$label];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Load Chart.js for rendering responsive modern charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Load html2pdf and xlsx for client-side exporting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Analytics Dashboard</h1>

        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
            <form method="GET" action="dashboard.php" class="flex space-x-2">
                <select name="date_range" onchange="this.form.submit()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full shadow-sm text-sm font-medium outline-none">
                    <option value="All" <?php echo $date_filter === 'All' ? 'selected' : ''; ?>>All Time</option>
                    <option value="Today" <?php echo $date_filter === 'Today' ? 'selected' : ''; ?>>Today</option>
                    <option value="This Week" <?php echo $date_filter === 'This Week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="This Month" <?php echo $date_filter === 'This Month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="This Year" <?php echo $date_filter === 'This Year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </form>

            <button onclick="exportDashboardToPDF()" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition shadow-sm text-sm font-medium flex items-center">
                PDF
            </button>
            <button onclick="exportDashboardToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-full hover:bg-green-700 transition shadow-sm text-sm font-medium flex items-center">
                Excel
            </button>
        </div>
    </div>

    <!-- Report Navigation -->
    <div class="flex space-x-4 mb-6 border-b border-gray-200 pb-4 overflow-x-auto">
        <a href="dashboard.php" class="text-indigo-600 font-bold border-b-2 border-indigo-600 pb-1 whitespace-nowrap">Dashboard</a>
        <?php if (in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])): ?>
            <a href="clients.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Client Reports</a>
            <a href="projects.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Project Reports</a>
            <a href="payments.php" class="text-gray-500 hover:text-indigo-600 pb-1 whitespace-nowrap">Payment Reports</a>
        <?php endif; ?>
    </div>
</div>

<div class="px-4 sm:px-6 mb-6" id="dashboard-content">
    <!-- Performance Score Card -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 mb-6 flex flex-col md:flex-row items-center justify-between shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-indigo-900"><?php echo htmlspecialchars($score_label); ?></h2>
            <p class="text-sm text-indigo-700 mt-1">Based on project and task completion rates.</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center">
            <div class="relative w-24 h-24">
                <svg class="w-full h-full" viewBox="0 0 36 36">
                    <path class="text-indigo-200" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="100, 100"/>
                    <path class="text-indigo-600" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="<?php echo $score; ?>, 100"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center text-xl font-bold text-indigo-900">
                    <?php echo $score; ?>%
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <?php foreach ($kpis as $label => $value): ?>
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 hover:shadow-md transition p-5">
                <dt class="text-sm font-medium text-gray-500 truncate"><?php echo htmlspecialchars($label); ?></dt>
                <dd class="mt-2 text-3xl font-semibold text-gray-900"><?php echo htmlspecialchars($value); ?></dd>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts Area -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-16">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Project Status Distribution</h3>
            <div class="relative h-64 w-full">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Productivity Trend (Demo)</h3>
            <div class="relative h-64 w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Status Pie Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: [
                    '#f59e0b', // Pending (Orange)
                    '#3b82f6', // Running (Blue)
                    '#6b7280', // On Hold (Gray)
                    '#10b981', // Completed (Green)
                    '#ef4444'  // Cancelled (Red)
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });

    // Mock Trend Line Chart
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Score %',
                data: [65, 70, 68, 75, <?php echo max(50, $score - 10); ?>, <?php echo $score; ?>],
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            }
        }
    });
});

function exportDashboardToPDF() {
    const element = document.getElementById('dashboard-content');
    const opt = {
        margin:       0.5,
        filename:     'vexa_dashboard_report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

function exportDashboardToExcel() {
    // Collect KPI data manually since dashboard isn't a single table
    const kpiElements = document.querySelectorAll('#dashboard-content dt');
    const valElements = document.querySelectorAll('#dashboard-content dd');

    let data = [["Metric", "Value"]];

    // Add Score
    data.push(["<?php echo addslashes($score_label); ?>", "<?php echo $score; ?>%"]);

    // Add KPIs
    for(let i=0; i<kpiElements.length; i++) {
        data.push([kpiElements[i].innerText.trim(), valElements[i].innerText.trim()]);
    }

    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Dashboard Data");
    XLSX.writeFile(wb, "vexa_dashboard_report.xlsx");
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
