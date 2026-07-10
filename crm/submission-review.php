<?php
// File: /crm/submission-review.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['CRM']);

$pdo = getDbConnection();
$agency_id = $_SESSION['agency_id'];
$user_id = $_SESSION['user_id'];

$submission_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$submission_id) {
    redirect('/crm/tasks.php');
}

// Fetch Submission and Verify Ownership
$stmt = $pdo->prepare("
    SELECT ts.*, t.task_name, t.crm_id, u.full_name as employee_name
    FROM task_submissions ts
    JOIN tasks t ON ts.task_id = t.id
    JOIN users u ON ts.employee_id = u.id
    WHERE ts.id = :id AND t.crm_id = :crm_id AND t.deleted_at IS NULL
");
$stmt->execute(['id' => $submission_id, 'crm_id' => $user_id]);
$submission = $stmt->fetch();

if (!$submission) {
    redirect('/crm/tasks.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $comment_text = sanitizeInput($_POST['comment_text'] ?? '');
        $voice_note_path = null;

        // Handle voice note upload
        if (isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] === UPLOAD_ERR_OK) {
            $allowed_extensions = ['mp3', 'wav', 'ogg', 'm4a', 'webm'];
            $file_info = pathinfo($_FILES['voice_note']['name']);
            $file_extension = strtolower($file_info['extension'] ?? '');

            if (!in_array($file_extension, $allowed_extensions)) {
                $error = "Invalid file type. Only audio files are allowed.";
            } else {
                $upload_dir = __DIR__ . '/../uploads/voice_notes/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Secure filename avoiding user input
                $filename = uniqid('vn_', true) . '.' . $file_extension;
                $target_file = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['voice_note']['tmp_name'], $target_file)) {
                    // Try to upload to Google Drive
                    $drive_result = uploadToGoogleDrive($_SESSION['agency_id'], $submission['task_id'], $user_id, 'Other', $target_file, $_FILES['voice_note']['name'], $_FILES['voice_note']['size']);

                    if ($drive_result && isset($drive_result['drive_link'])) {
                        $voice_note_path = $drive_result['drive_link']; // Replace local path with drive link
                    } else {
                        $voice_note_path = '/uploads/voice_notes/' . $filename; // Fallback to local
                    }
                } else {
                    $error = "Failed to save voice note.";
                }
            }
        }

        if ($error) {
            // Stop processing if upload failed
        } elseif (empty($action) || !in_array($action, ['Approve', 'Request Changes'])) {
            $error = "Invalid action.";
        } else {
            $pdo->beginTransaction();
            try {
                $new_status = $action === 'Approve' ? 'Approved' : 'Revision Required';
                $task_status = $action === 'Approve' ? 'Completed' : 'Revision Required';

                // Update Submission
                $stmt = $pdo->prepare("
                    UPDATE task_submissions
                    SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(), revision_count = revision_count + :inc_revision
                    WHERE id = :id
                ");
                $inc_revision = $action === 'Request Changes' ? 1 : 0;
                $stmt->execute(['status' => $new_status, 'reviewed_by' => $user_id, 'inc_revision' => $inc_revision, 'id' => $submission_id]);

                // Update Task Status
                $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :task_id");
                $stmt->execute(['status' => $task_status, 'task_id' => $submission['task_id']]);

                // Insert Comment
                if (!empty($comment_text) || $voice_note_path) {
                    $stmt = $pdo->prepare("
                        INSERT INTO task_comments (task_id, submission_id, user_id, comment_text, voice_note_path)
                        VALUES (:task_id, :submission_id, :user_id, :comment_text, :voice_note_path)
                    ");
                    $stmt->execute([
                        'task_id' => $submission['task_id'],
                        'submission_id' => $submission_id,
                        'user_id' => $user_id,
                        'comment_text' => $comment_text,
                        'voice_note_path' => $voice_note_path
                    ]);
                }

                $pdo->commit();
                $_SESSION['success_msg'] = "Submission " . ($action === 'Approve' ? "approved" : "rejected with revision request") . ".";
                redirect('/crm/task-submissions.php?id=' . $submission['task_id']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch existing comments for this submission
$stmt = $pdo->prepare("
    SELECT tc.*, u.full_name, r.name as role_name
    FROM task_comments tc
    JOIN users u ON tc.user_id = u.id
    JOIN roles r ON u.role_id = r.id
    WHERE tc.submission_id = :submission_id
    ORDER BY tc.created_at ASC
");
$stmt->execute(['submission_id' => $submission_id]);
$comments = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center mb-4">
        <a href="task-submissions.php?id=<?php echo $submission['task_id']; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Review Submission: <?php echo htmlspecialchars($submission['task_name']); ?></h1>
    </div>
</div>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-2 gap-6 mb-16">

    <!-- Submission Content -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Employee Work</h3>
            <p class="text-sm text-gray-500 mb-2">Submitted by: <span class="font-medium text-gray-900"><?php echo htmlspecialchars($submission['employee_name']); ?></span> on <?php echo date('d-m-Y h:i A', strtotime($submission['created_at'])); ?></p>
            <p class="text-sm text-gray-500 mb-4">Current Status:
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                    <?php
                    if($submission['status'] === 'Approved') echo 'bg-green-100 text-green-800';
                    elseif($submission['status'] === 'Revision Required') echo 'bg-red-100 text-red-800';
                    else echo 'bg-yellow-100 text-yellow-800';
                    ?>
                ">
                    <?php echo htmlspecialchars($submission['status']); ?>
                </span>
            </p>

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mt-4 min-h-[150px]">
                <?php if ($submission['submission_text']): ?>
                    <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($submission['submission_text']); ?></p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic">No text provided.</p>
                <?php endif; ?>
            </div>

            <?php if ($submission['file_path']): ?>
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Attached File:</p>
                    <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        View Attachment
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- History/Comments -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Feedback History</h3>
            <?php if (count($comments) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comment['full_name']); ?> <span class="text-xs text-gray-500 font-normal">(<?php echo htmlspecialchars($comment['role_name']); ?>)</span></span>
                                <span class="text-xs text-gray-500"><?php echo date('d-m-Y h:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <?php if ($comment['comment_text']): ?>
                                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                            <?php endif; ?>
                            <?php if ($comment['voice_note_path']): ?>
                                <div class="mt-2">
                                    <audio controls class="h-8 w-full max-w-xs">
                                        <source src="<?php echo htmlspecialchars($comment['voice_note_path']); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500 text-center py-4">No comments or feedback yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Action Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Review Action</h3>
            </div>

            <?php if ($submission['status'] === 'Approved'): ?>
                <div class="p-6 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Submission Approved</h3>
                    <p class="text-sm text-gray-500 mt-1">This work has already been approved and the task is marked as completed.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="submission-review.php?id=<?php echo $submission_id; ?>" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <?php if ($error): ?>
                        <div class="bg-red-50 text-red-500 p-3 rounded-lg mb-4 text-sm">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Feedback Comment</label>
                            <textarea name="comment_text" rows="4" placeholder="Write your feedback or approval note here..." class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Voice Note (Optional)</label>
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-6 h-6 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                        <p class="text-xs text-gray-500">Click to upload audio file</p>
                                    </div>
                                    <input type="file" name="voice_note" accept="audio/*" class="hidden" />
                                </label>
                            </div>
                        </div>

                        <div class="pt-4 grid grid-cols-2 gap-4">
                            <button type="submit" name="action" value="Request Changes" class="w-full bg-red-50 text-red-700 border border-red-200 px-4 py-3 rounded-xl hover:bg-red-100 transition shadow-sm font-medium flex justify-center items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path></svg>
                                Request Revision
                            </button>
                            <button type="submit" name="action" value="Approve" class="w-full bg-green-600 text-white px-4 py-3 rounded-xl hover:bg-green-700 transition shadow-sm font-medium flex justify-center items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Approve Work
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
