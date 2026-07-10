<?php
// File: /employee/task-view.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Employee']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

$task_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$task_id) {
    redirect('/employee/tasks.php');
}

// Fetch Task Details (Verify assignment)
$stmt = $pdo->prepare("
    SELECT t.*, p.project_name, c.client_name, pa.role as my_role
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN project_assignments pa ON p.id = pa.project_id AND pa.employee_id = :employee_id
    WHERE t.id = :id AND ta.employee_id = :employee_id AND t.deleted_at IS NULL
");
$stmt->execute(['id' => $task_id, 'employee_id' => $user_id]);
$task = $stmt->fetch();

if (!$task) {
    redirect('/employee/tasks.php');
}

// Handle Status Update (Employee can only Start/Pause)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $new_status = $_POST['status'] ?? '';

        // Allowed transitions: Pending/Revision Required -> In Progress, In Progress -> Pending (Pause)
        $allowed = false;
        if ($new_status === 'In Progress' && in_array($task['status'], ['Pending', 'Revision Required'])) {
            $allowed = true;
        } elseif ($new_status === 'Pending' && $task['status'] === 'In Progress') {
            $allowed = true; // Pause
        }

        if ($allowed) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $task_id]);
            $_SESSION['success_msg'] = "Task status updated to " . htmlspecialchars($new_status) . ".";
            redirect('/employee/task-view.php?id=' . $task_id);
        } else {
            $_SESSION['error_msg'] = "Invalid status transition. You cannot set this status directly.";
        }
    } else {
        $_SESSION['error_msg'] = "Invalid CSRF token.";
    }
}

// Fetch Submission History
$stmt = $pdo->prepare("
    SELECT * FROM task_submissions
    WHERE task_id = :task_id AND employee_id = :employee_id
    ORDER BY created_at DESC
");
$stmt->execute(['task_id' => $task_id, 'employee_id' => $user_id]);
$submissions = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <a href="tasks.php" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <h1 class="text-2xl font-bold leading-tight text-gray-900">Task Details: <?php echo htmlspecialchars($task['task_name']); ?></h1>
        </div>
        <div>
            <?php if (in_array($task['status'], ['In Progress', 'Revision Required'])): ?>
                <a href="task-submit.php?id=<?php echo $task_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">Submit Work</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="mx-4 sm:mx-6 mb-4 bg-green-50 text-green-600 p-3 rounded-lg text-sm">
        <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="mx-4 sm:mx-6 mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm">
        <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
    </div>
<?php endif; ?>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-3 gap-6 mb-16">
    <!-- Left Column: Details & Actions -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Task Information</h3>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Project / Client</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php echo htmlspecialchars($task['project_name']); ?> <br>
                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($task['client_name']); ?></span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Priority</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            <?php echo $task['priority'] === 'High' ? 'bg-red-100 text-red-800' : ($task['priority'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                            <?php echo htmlspecialchars($task['priority']); ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                    <dd class="mt-1 text-sm font-medium <?php echo (strtotime($task['due_date']) < time() && $task['status'] !== 'Completed') ? 'text-red-600' : 'text-gray-900'; ?>">
                        <?php echo htmlspecialchars($task['due_date']); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Task Type</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($task['task_type']); ?></dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Status Update</dt>
                    <dd class="mt-2 text-sm text-gray-900 flex items-center">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full mr-4
                            <?php
                            if($task['status'] === 'Completed') echo 'bg-green-100 text-green-800';
                            elseif($task['status'] === 'Waiting For Approval') echo 'bg-purple-100 text-purple-800';
                            elseif($task['status'] === 'Revision Required') echo 'bg-red-100 text-red-800';
                            elseif($task['status'] === 'In Progress') echo 'bg-blue-100 text-blue-800';
                            else echo 'bg-gray-100 text-gray-800';
                            ?>
                        ">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>

                        <?php if (in_array($task['status'], ['Pending', 'Revision Required'])): ?>
                            <form method="POST" action="task-view.php?id=<?php echo $task_id; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="status" value="In Progress">
                                <button type="submit" class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-lg hover:bg-indigo-100 transition text-sm font-medium border border-indigo-200">Start Work</button>
                            </form>
                        <?php elseif ($task['status'] === 'In Progress'): ?>
                            <form method="POST" action="task-view.php?id=<?php echo $task_id; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="status" value="Pending">
                                <button type="submit" class="bg-gray-50 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-100 transition text-sm font-medium border border-gray-200">Pause Work</button>
                            </form>
                        <?php elseif ($task['status'] === 'Waiting For Approval'): ?>
                            <span class="text-xs text-gray-500 italic">Task is currently under review by CRM.</span>
                        <?php endif; ?>
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <?php echo nl2br(htmlspecialchars($task['description'] ?: 'No description provided.')); ?>
                    </dd>
                </div>

                <div class="sm:col-span-2 mt-4">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-indigo-800 mb-2">Workflow Instructions: <?php echo htmlspecialchars($task['my_role'] ?: 'Generic'); ?></h4>
                        <ul class="list-disc pl-5 text-sm text-indigo-700 space-y-1">
                            <?php
                            $role = $task['my_role'] ?? '';
                            if ($role === 'Sales') {
                                echo "<li>Update Visit Status in notes.</li><li>Add Meeting Notes or Voice Notes.</li><li>Upload required Documents.</li><li>Mark complete by submitting.</li>";
                            } elseif ($role === 'Photographer') {
                                echo "<li>Review Client Details.</li><li>Upload Edited Photos and RAW Files.</li><li>Upload Videos if applicable.</li><li>Add text or voice notes.</li>";
                            } elseif ($role === 'Graphic Designer') {
                                echo "<li>Review Design Task.</li><li>Upload Design Files (e.g. JPG, PNG).</li><li>Upload Source Files (e.g. PSD, AI).</li><li>Add explanatory text or voice notes.</li>";
                            } elseif ($role === 'Video Editor') {
                                echo "<li>Review Video Task.</li><li>Upload Edited Video.</li><li>Upload Project Files if required.</li><li>Add processing or rendering notes.</li>";
                            } elseif ($role === 'Content Writer') {
                                echo "<li>Write content based on task description.</li><li>Upload Content Documents (e.g. DOCX, PDF).</li><li>Add reference links or voice notes.</li>";
                            } elseif ($role === 'Content Approval') {
                                echo "<li>Review assigned content from writer.</li><li>Approve or Request Revision.</li><li>Add feedback text or voice notes.</li><li>Submit to forward to CRM.</li>";
                            } elseif ($role === 'Web Developer') {
                                echo "<li>Review Development Task.</li><li>Upload Code Package or Screenshots.</li><li>Upload Technical Documents.</li><li>Add deployment or testing notes.</li>";
                            } else {
                                echo "<li>View Task details.</li><li>Upload required files.</li><li>Add text or voice notes explaining work.</li><li>Submit for CRM review.</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </dl>
        </div>
    </div>

    <!-- Right Column: Submission History -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Submission History</h3>

            <?php if (count($submissions) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($submissions as $sub): ?>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 hover:shadow-sm transition">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-900">Submission #<?php echo $sub['revision_count'] + 1; ?></span>
                                <span class="text-xs text-gray-500"><?php echo date('d-m-Y h:i A', strtotime($sub['created_at'])); ?></span>
                            </div>

                            <p class="text-sm text-gray-500 mb-2">Status:
                                <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full
                                    <?php
                                    if($sub['status'] === 'Approved') echo 'bg-green-100 text-green-800';
                                    elseif($sub['status'] === 'Revision Required') echo 'bg-red-100 text-red-800';
                                    else echo 'bg-yellow-100 text-yellow-800';
                                    ?>
                                ">
                                    <?php echo htmlspecialchars($sub['status']); ?>
                                </span>
                            </p>

                            <div class="mt-3 text-right">
                                <a href="task-comment.php?sub_id=<?php echo $sub['id']; ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium bg-indigo-50 px-3 py-1 rounded">View Feedback</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500 mb-4">You haven't submitted any work for this task yet.</p>
                    <?php if (in_array($task['status'], ['In Progress', 'Revision Required'])): ?>
                        <a href="task-submit.php?id=<?php echo $task_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm text-sm font-medium">Submit Work Now</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
