<?php
// File: /notifications/activity.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Requires auth
checkAuth();

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];
$role_name = $_SESSION['role_name'] ?? '';

// Filters
$project_filter = $_GET['project_id'] ?? '';
$client_filter = $_GET['client_id'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query for activities tied to the agency
$query = "
    SELECT a.*, u.full_name, r.name as role_name, p.project_name, c.client_name
    FROM activity_logs a
    JOIN users u ON a.user_id = u.id
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN projects p ON a.project_id = p.id
    LEFT JOIN clients c ON a.client_id = c.id
    WHERE a.agency_id = :agency_id
";
$params = ['agency_id' => $agency_id];

// Security/Scoping by role
if ($role_name === 'CRM') {
    // CRMs only see activity for projects/clients they are assigned to
    $query .= " AND (p.crm_id = :user_id OR a.user_id = :user_id)";
    $params['user_id'] = $user_id;
} elseif ($role_name === 'Employee') {
    // Employees only see activity for projects they are assigned to
    $query .= " AND (a.project_id IN (SELECT project_id FROM project_assignments WHERE employee_id = :user_id) OR a.user_id = :user_id)";
    $params['user_id'] = $user_id;
}

// Apply Filters
if ($project_filter) {
    $query .= " AND a.project_id = :project_id";
    $params['project_id'] = $project_filter;
}
if ($client_filter) {
    $query .= " AND a.client_id = :client_id";
    $params['client_id'] = $client_filter;
}
if ($date_filter) {
    $query .= " AND DATE(a.created_at) = :date_filter";
    $params['date_filter'] = $date_filter;
}

// Count total
$count_query = preg_replace('/SELECT .*? FROM/s', 'SELECT COUNT(*) FROM', $query, 1);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$query .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Fetch Options for Filters
if (in_array($role_name, ['Super Admin', 'Agency Owner', 'Manager'])) {
    $proj_stmt = $pdo->prepare("SELECT id, project_name FROM projects WHERE agency_id = :agency_id AND deleted_at IS NULL");
    $proj_stmt->execute(['agency_id' => $agency_id]);

    $cli_stmt = $pdo->prepare("SELECT id, client_name FROM clients WHERE agency_id = :agency_id AND deleted_at IS NULL");
    $cli_stmt->execute(['agency_id' => $agency_id]);
} elseif ($role_name === 'CRM') {
    $proj_stmt = $pdo->prepare("SELECT id, project_name FROM projects WHERE crm_id = :crm_id AND deleted_at IS NULL");
    $proj_stmt->execute(['crm_id' => $user_id]);

    $cli_stmt = $pdo->prepare("SELECT DISTINCT c.id, c.client_name FROM clients c JOIN projects p ON c.id = p.client_id WHERE p.crm_id = :crm_id AND c.deleted_at IS NULL");
    $cli_stmt->execute(['crm_id' => $user_id]);
} else {
    // Employee
    $proj_stmt = $pdo->prepare("SELECT p.id, p.project_name FROM projects p JOIN project_assignments pa ON p.id = pa.project_id WHERE pa.employee_id = :employee_id AND p.deleted_at IS NULL");
    $proj_stmt->execute(['employee_id' => $user_id]);

    $cli_stmt = $pdo->prepare("SELECT DISTINCT c.id, c.client_name FROM clients c JOIN projects p ON c.id = p.client_id JOIN project_assignments pa ON p.id = pa.project_id WHERE pa.employee_id = :employee_id AND c.deleted_at IS NULL");
    $cli_stmt->execute(['employee_id' => $user_id]);
}
$filter_projects = $proj_stmt->fetchAll();
$filter_clients = $cli_stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">Activity Timeline</h1>
        <div class="mt-4 sm:mt-0 flex space-x-2">
            <a href="/notifications/index.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Notification Center</a>
            <a href="/notifications/audit.php" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium">Audit Logs</a>
        </div>
    </div>
</div>

<div class="px-4 sm:px-6 mb-6">
    <form method="GET" action="/notifications/activity.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">

        <select name="project_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Projects</option>
            <?php foreach($filter_projects as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo $project_filter == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['project_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="client_id" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
            <option value="">All Clients</option>
            <?php foreach($filter_clients as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $client_filter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['client_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">

        <div class="flex space-x-2">
            <button type="submit" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">Filter</button>
            <?php if($project_filter || $client_filter || $date_filter): ?>
                <a href="/notifications/activity.php" class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium text-center flex items-center justify-center">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8 relative">
        <?php if (count($activities) > 0): ?>
            <div class="absolute left-10 sm:left-12 top-8 bottom-8 w-px bg-gray-200 hidden sm:block"></div>

            <div class="space-y-8 relative">
                <?php foreach ($activities as $act): ?>
                    <div class="flex flex-col sm:flex-row items-start relative z-10">
                        <div class="hidden sm:flex flex-col items-center mr-6">
                            <div class="h-10 w-10 rounded-full bg-indigo-100 border-4 border-white flex items-center justify-center shadow-sm">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 flex-1 w-full hover:shadow-md transition">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-2">
                                <div>
                                    <span class="font-bold text-gray-900"><?php echo htmlspecialchars($act['full_name']); ?></span>
                                    <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded ml-2"><?php echo htmlspecialchars($act['role_name']); ?></span>
                                </div>
                                <span class="text-xs text-gray-400 mt-1 sm:mt-0"><?php echo date('M d, Y h:i A', strtotime($act['created_at'])); ?></span>
                            </div>

                            <p class="text-sm font-medium text-gray-800 mb-1"><?php echo htmlspecialchars($act['action']); ?></p>

                            <div class="text-xs text-gray-500 mb-3 flex flex-wrap gap-2">
                                <?php if ($act['project_name']): ?>
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded">Project: <?php echo htmlspecialchars($act['project_name']); ?></span>
                                <?php endif; ?>
                                <?php if ($act['client_name']): ?>
                                    <span class="bg-green-50 text-green-700 px-2 py-1 rounded">Client: <?php echo htmlspecialchars($act['client_name']); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($act['details']): ?>
                                <div class="bg-white p-3 rounded-lg border border-gray-100 text-sm text-gray-600 mb-3">
                                    <?php echo nl2br(htmlspecialchars($act['details'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($act['voice_note_path']): ?>
                                <div class="mt-2 bg-white p-3 rounded-lg border border-gray-100">
                                    <p class="text-xs font-medium text-gray-500 mb-2">Attached Voice Note</p>
                                    <audio controls class="h-8 w-full max-w-sm">
                                        <source src="<?php echo htmlspecialchars($act['voice_note_path']); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500 py-8">No activity recorded yet for these filters.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex justify-between items-center bg-white px-4 py-3 border border-gray-200 rounded-xl sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&project_id=<?php echo urlencode($project_filter); ?>&client_id=<?php echo urlencode($client_filter); ?>&date=<?php echo urlencode($date_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
