<?php
// File: /crm/client-view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['CRM']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

$client_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$client_id) {
    redirect('/crm/clients.php');
}

// Fetch existing client but verify CRM is assigned to at least one project for this client
$stmt = $pdo->prepare("
    SELECT c.*
    FROM clients c
    JOIN projects p ON c.id = p.client_id
    WHERE c.id = :id
    AND c.agency_id = :agency_id
    AND p.crm_id = :crm_id
    AND c.deleted_at IS NULL
    AND p.deleted_at IS NULL
    LIMIT 1
");
$stmt->execute(['id' => $client_id, 'agency_id' => $agency_id, 'crm_id' => $user_id]);
$client = $stmt->fetch();

if (!$client) {
    redirect('/crm/clients.php'); // Unauthorized or client doesn't exist
}

// Client Payment Summary
$stmt = $pdo->prepare("
    SELECT
        SUM(pp.project_amount) as total_project_value,
        SUM(pp.received_amount) as total_received,
        SUM(pp.pending_amount) as total_pending
    FROM projects p
    LEFT JOIN project_payments pp ON p.id = pp.project_id
    WHERE p.client_id = :client_id AND p.agency_id = :agency_id AND p.crm_id = :crm_id AND p.deleted_at IS NULL
");
$stmt->execute(['client_id' => $client_id, 'agency_id' => $agency_id, 'crm_id' => $user_id]);
$payment_summary = $stmt->fetch();

$total_value = $payment_summary['total_project_value'] ?? 0;
$total_received = $payment_summary['total_received'] ?? 0;
$total_pending = $payment_summary['total_pending'] ?? 0;

// Fetch Client's Projects assigned to this CRM
$stmt = $pdo->prepare("SELECT id, project_name, status, project_type FROM projects WHERE client_id = :client_id AND agency_id = :agency_id AND crm_id = :crm_id AND deleted_at IS NULL ORDER BY created_at DESC");
$stmt->execute(['client_id' => $client_id, 'agency_id' => $agency_id, 'crm_id' => $user_id]);
$projects = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <a href="clients.php" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <h1 class="text-2xl font-bold leading-tight text-gray-900">Client Profile: <?php echo htmlspecialchars($client['client_name']); ?></h1>
        </div>
    </div>
</div>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Client Details</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($client['company_name'] ?: '-'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($client['contact_person'] ?: '-'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($client['email']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($client['phone']); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php echo htmlspecialchars($client['address'] ?: '-'); ?><br>
                        <?php echo htmlspecialchars($client['city'] ?: ''); ?> <?php echo htmlspecialchars($client['state'] ?: ''); ?> <?php echo htmlspecialchars($client['country'] ?: ''); ?>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($client['notes'] ?: '-')); ?></dd>
                </div>
            </dl>
        </div>

        <!-- Associated Projects -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Assigned Projects</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($projects) > 0): ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="project-view.php?id=<?php echo $project['id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-900"><?php echo htmlspecialchars($project['project_name']); ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($project['project_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            <?php echo htmlspecialchars($project['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="px-6 py-4 text-sm text-center text-gray-500">No projects found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Financial Summary -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Payment Summary (Assigned Projects)</h3>

            <div class="space-y-4">
                <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center border border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Total Project Value</span>
                    <span class="text-xl font-bold text-gray-900">$<?php echo number_format($total_value, 2); ?></span>
                </div>

                <div class="bg-green-50 p-4 rounded-lg flex justify-between items-center border border-green-100">
                    <span class="text-sm font-medium text-green-700">Total Received</span>
                    <span class="text-xl font-bold text-green-700">$<?php echo number_format($total_received, 2); ?></span>
                </div>

                <div class="bg-red-50 p-4 rounded-lg flex justify-between items-center border border-red-100">
                    <span class="text-sm font-medium text-red-700">Total Due (Pending)</span>
                    <span class="text-xl font-bold text-red-700">$<?php echo number_format($total_pending, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
