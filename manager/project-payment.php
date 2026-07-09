<?php
// File: /manager/project-payment.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Manager']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];

$project_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$project_id) {
    redirect('/manager/payments.php');
}

// Fetch existing project to ensure it belongs to this agency
$stmt = $pdo->prepare("SELECT project_name, status FROM projects WHERE id = :id AND agency_id = :agency_id AND deleted_at IS NULL");
$stmt->execute(['id' => $project_id, 'agency_id' => $agency_id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/manager/payments.php');
}

// Fetch existing payment record
$stmt = $pdo->prepare("SELECT * FROM project_payments WHERE project_id = :project_id");
$stmt->execute(['project_id' => $project_id]);
$payment = $stmt->fetch();

if (!$payment) {
    // If not found, create a blank one for the form context
    $payment = [
        'project_amount' => 0,
        'received_amount' => 0,
        'pending_amount' => 0,
        'payment_status' => 'Pending',
        'last_payment_date' => null
    ];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $project_amount = filter_var($_POST['project_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $received_amount = filter_var($_POST['received_amount'] ?? 0, FILTER_VALIDATE_FLOAT);

    if ($project_amount === false || $received_amount === false) {
        $error = "Please enter valid numerical amounts.";
    } else {
        $pending_amount = max(0, $project_amount - $received_amount);

        $payment_status = 'Pending';
        if ($received_amount >= $project_amount && $project_amount > 0) {
            $payment_status = 'Completed';
        } elseif ($received_amount > 0) {
            $payment_status = 'Partial';
        }

        $last_payment_date = date('Y-m-d'); // Update last payment date when modifying

        // UPSERT
        $stmt = $pdo->prepare("
            INSERT INTO project_payments (project_id, project_amount, received_amount, pending_amount, payment_status, last_payment_date)
            VALUES (:project_id, :project_amount, :received_amount, :pending_amount, :payment_status, :last_payment_date)
            ON DUPLICATE KEY UPDATE
                project_amount = :project_amount,
                received_amount = :received_amount,
                pending_amount = :pending_amount,
                payment_status = :payment_status,
                last_payment_date = :last_payment_date
        ");

        try {
            $stmt->execute([
                'project_id' => $project_id,
                'project_amount' => $project_amount,
                'received_amount' => $received_amount,
                'pending_amount' => $pending_amount,
                'payment_status' => $payment_status,
                'last_payment_date' => $last_payment_date
            ]);

            $_SESSION['success_msg'] = "Payment details updated successfully.";

            // Redirect based on where they came from
            if (isset($_GET['from']) && $_GET['from'] === 'project') {
                redirect('/manager/project-view.php?id=' . $project_id);
            } else {
                redirect('/manager/payments.php');
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center mb-4">
        <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Update Payment: <?php echo htmlspecialchars($project['project_name']); ?></h1>
    </div>
</div>

<div class="px-4 sm:px-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-2xl">
        <form method="POST" action="project-payment.php?id=<?php echo $project_id; ?><?php echo isset($_GET['from']) ? '&from=project' : ''; ?>" class="p-6 sm:p-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <?php if ($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Project Amount</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" step="0.01" name="project_amount" required class="w-full pl-7 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['project_amount'] ?? $payment['project_amount']); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Received Amount</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" step="0.01" name="received_amount" required class="w-full pl-7 px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none" value="<?php echo htmlspecialchars($_POST['received_amount'] ?? $payment['received_amount']); ?>">
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-500 mb-2">Calculated Values (Updated on Save)</p>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Pending Amount:</span>
                        <span class="text-lg font-bold text-red-600">$<?php echo number_format($payment['pending_amount'], 2); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Current Status:</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-800"><?php echo htmlspecialchars($payment['payment_status']); ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <a href="javascript:history.back()" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition mr-3">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
