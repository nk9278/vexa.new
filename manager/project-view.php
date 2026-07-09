<?php
// File: /manager/project-view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

$project_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$project_id) {
    redirect('/manager/projects.php');
}

// Fetch existing project details
$stmt = $pdo->prepare("
    SELECT p.*, c.client_name, u.full_name as crm_name
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN users u ON p.crm_id = u.id
    WHERE p.id = :id AND p.agency_id = :agency_id AND p.deleted_at IS NULL
");
$stmt->execute(['id' => $project_id, 'agency_id' => $agency_id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/manager/projects.php');
}

// Fetch Payment Info
$stmt = $pdo->prepare("SELECT * FROM project_payments WHERE project_id = :project_id");
$stmt->execute(['project_id' => $project_id]);
$payment = $stmt->fetch();

if (!$payment) {
    // If somehow payment record is missing, initialize a dummy one for display
    $payment = [
        'project_amount' => 0, 'received_amount' => 0, 'pending_amount' => 0,
        'payment_status' => 'Pending', 'last_payment_date' => null
    ];
}

// Fetch Team Assignments
$stmt = $pdo->prepare("
    SELECT pa.*, u.full_name as employee_name
    FROM project_assignments pa
    JOIN users u ON pa.employee_id = u.id
    WHERE pa.project_id = :project_id
");
$stmt->execute(['project_id' => $project_id]);
$team = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <a href="projects.php" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <h1 class="text-2xl font-bold leading-tight text-gray-900">Project: <?php echo htmlspecialchars($project['project_name']); ?></h1>
        </div>
        <div>
            <a href="project-edit.php?id=<?php echo $project_id; ?>" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 transition shadow-sm text-sm font-medium mr-2">Edit</a>
            <a href="project-team.php?id=<?php echo $project_id; ?>" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-full hover:bg-indigo-100 transition shadow-sm text-sm font-medium mr-2">Manage Team</a>
            <a href="project-payment.php?id=<?php echo $project_id; ?>" class="bg-green-50 text-green-600 px-4 py-2 rounded-full hover:bg-green-100 transition shadow-sm text-sm font-medium">Update Payment</a>
        </div>
    </div>
</div>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Project Overview</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Client Name</dt>
                    <dd class="mt-1 text-sm font-medium text-indigo-600"><a href="client-view.php?id=<?php echo $project['client_id']; ?>"><?php echo htmlspecialchars($project['client_name']); ?></a></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Assigned CRM</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($project['crm_name'] ?: 'Unassigned'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Project Type</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($project['project_type']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            <?php echo htmlspecialchars($project['status']); ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Priority</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($project['priority']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Start Date / Expected Completion</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php echo $project['start_date'] ? htmlspecialchars($project['start_date']) : 'N/A'; ?> -
                        <?php echo $project['expected_completion_date'] ? htmlspecialchars($project['expected_completion_date']) : 'N/A'; ?>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($project['description'] ?: '-')); ?></dd>
                </div>
            </dl>
        </div>

        <!-- Assigned Team -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Assigned Team</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($team) > 0): ?>
                            <?php foreach ($team as $member): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($member['employee_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-medium"><?php echo htmlspecialchars($member['role']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="px-6 py-4 text-sm text-center text-gray-500">No employees assigned yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Financial Summary -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Payment Information</h3>

            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Payment Status</p>
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                        <?php
                        if($payment['payment_status'] === 'Completed') echo 'bg-green-100 text-green-800';
                        elseif($payment['payment_status'] === 'Partial') echo 'bg-yellow-100 text-yellow-800';
                        else echo 'bg-red-100 text-red-800';
                        ?>">
                        <?php echo htmlspecialchars($payment['payment_status']); ?>
                    </span>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center border border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Project Value</span>
                    <span class="text-xl font-bold text-gray-900">$<?php echo number_format($payment['project_amount'], 2); ?></span>
                </div>

                <div class="bg-green-50 p-4 rounded-lg flex justify-between items-center border border-green-100">
                    <span class="text-sm font-medium text-green-700">Received Amount</span>
                    <span class="text-xl font-bold text-green-700">$<?php echo number_format($payment['received_amount'], 2); ?></span>
                </div>

                <div class="bg-red-50 p-4 rounded-lg flex justify-between items-center border border-red-100">
                    <span class="text-sm font-medium text-red-700">Pending Amount</span>
                    <span class="text-xl font-bold text-red-700">$<?php echo number_format($payment['pending_amount'], 2); ?></span>
                </div>

                <?php if ($payment['last_payment_date']): ?>
                <div>
                    <p class="text-xs text-gray-500 text-center mt-2">Last Payment: <?php echo htmlspecialchars($payment['last_payment_date']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
